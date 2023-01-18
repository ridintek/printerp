<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

/**
 * Add new event because I NEVER TRUST USER INPUT
 * @param string $message Message
 * @param string $type info|warning|danger|error
 */
function addEvent($message, string $type = 'info')
{
  $system     = User::getRow(['username' => 'system']); // Default system.
  $warehouse  = Warehouse::getRow(['code' => 'LUC']); // Default Lucretai

  $eventData  = [
    'warehouse_id' => (XSession::get('warehouse_id') ?? $warehouse->id),
    'type'         => $type,
    'message'      => $message,
    'created_by'   => (XSession::get('user_id') ?? $system->id)
  ];

  DB::table('events')->insert($eventData);
  return DB::insertID();
}

/**
 * Add sale due date. You must have sale items first before add sale due date.
 * @param int $sale_id Sale ID.
 */
function addSaleDueDate($sale_id)
{
  $ci = &get_instance();
  $sale = $ci->site->getSaleByID($sale_id);
  $payments = $ci->site->getSalePayments($sale_id);
  $paymentDate = date('Y-m-d H:i:s'); // Default current datetime.

  if ($payments) {
    foreach ($payments as $payment) {
      $paymentsDate[] = $payment->date;
    }

    $paymentDate = getLongestDateTime($paymentsDate);
  }

  if ($sale) {
    $dates = [];
    $saleItems = $ci->site->getSaleItems(['sale_id' => $sale->id]);

    if ($saleItems) {
      foreach ($saleItems as $saleItem) {
        // Default +32 hours. 1 day 8 hours.
        $dueDateItem   = date('Y-m-d H:i:s', strtotime('+32 hours', strtotime($paymentDate)));
        $product       = $ci->site->getProductByID($saleItem->product_id);
        $productJS     = json_decode($product->json_data);

        if ($productJS) {
          $min_prod_time = (!empty($productJS->min_prod_time) ? $productJS->min_prod_time : 32);
          $prod_time_qty = (!empty($productJS->prod_time_qty) ? $productJS->prod_time_qty : 1);
          $prod_time_qty = (is_numeric($prod_time_qty) ? $prod_time_qty : 1); // non-numeric problem. So we patch it.
          $time_qty      = ($saleItem->quantity * $prod_time_qty); // Problem non-numeric value encountered.
          // Make sure no decimal point on 'minute' using round() otherwise will return '1970-01-01 07:00:00'.
          $minute        = roundDecimal($time_qty < $min_prod_time ? $min_prod_time : $time_qty) * 60;
          $dueDateItem   = date('Y-m-d H:i:s', strtotime("+{$minute} minutes", strtotime($paymentDate)));
        }

        $dueDate = getWorkingDateTime($dueDateItem);

        $ci->site->updateSaleItem($saleItem->id, ['due_date' => $dueDate]);
        $dates[] = $dueDateItem;
      }

      $estCompleteDate = getWorkingDateTime(getLongestDateTime($dates));

      if ($dates && $ci->site->updateSale($sale->id, ['est_complete_date' => $estCompleteDate])) {
        return TRUE;
      }
    }
  }
  return FALSE;
}

/**
 * Combine two arrays even when values length less then keys length.
 * @param array $keys Array keys.
 * @param array $values Array values.
 */
function arrayCombine(array $keys, array $values)
{
  $key_length = count($keys);
  $val_length = count($values);
  if ($key_length == $val_length) {
    return array_combine($keys, $values);
  } else if ($key_length > $val_length) {
    $min = $key_length - $val_length;
    $rest = array_fill($key_length, $min, NULL);
    $merged = array_merge($values, $rest);
    return array_combine($keys, $merged);
  }
  return NULL;
}

function baseToUnitCost($cost, $unit)
{
  switch ($unit->operator) {
    case '*': {
        return floatval($cost) * floatval($unit->operation_value);
      }
    case '/': {
        return floatval($cost) / floatval($unit->operation_value);
      }
    case '+': {
        return floatval($cost) + floatval($unit->operation_value);
      }
    case '-': {
        return floatval($cost) - floatval($unit->operation_value);
      }
    default: {
        return floatval($cost);
      }
  }
}

function baseToUnitQty($qty_received, $unit)
{
  switch ($unit->operator) {
    case '*': {
        return floatval($qty_received) / floatval($unit->operation_value);
      }
    case '/': {
        return floatval($qty_received) * floatval($unit->operation_value);
      }
    case '-': {
        return floatval($qty_received) + floatval($unit->operation_value);
      }
    case '+': {
        return floatval($qty_received) - floatval($unit->operation_value);
      }
    default: {
        return floatval($qty_received);
      }
  }
}

/**
 * Convert Biller ID to Warehouse ID. See warehouseToBiller.
 * @param int|array $billerId Biller ID. It can be biller id or array of biller id.
 * @return int|array Return Warehouse ID.
 */
function billerToWarehouse($billerId)
{
  if (gettype($billerId) == 'array') {
    $data = [];

    foreach ($billerId as $biller_id) {
      $biller = Biller::getRow(['id' => $biller_id]);

      if ($biller) {
        $warehouse = Warehouse::getRow(['code' => $biller->code]);

        if ($warehouse) {
          $data[] = $warehouse->id;
        }
      }
    }

    return $data;
  } else {
    $biller = Biller::getRow(['id' => $billerId]);

    if ($biller) {
      $warehouse = Warehouse::getRow(['code' => $biller->code]);

      if ($warehouse) {
        return $warehouse->id;
      }
    }
  }
  return NULL;
}

function cache(string $name = '')
{
}

/**
 * Check path existence, create directory if not exist.
 * @param string $path Path to check.
 * @return bool Always return TRUE.
 */
function checkPath(string $path): bool
{
  if (!file_exists($path)) {
    mkdir($path, 0755, TRUE);
  }
  return TRUE;
}

/**
 * Check for permission access. Redirect or return AJAX if not granted.
 * @param string $perms Permission name.
 */
function checkPermission($perms)
{
  $ci = &get_instance();

  if (!getPermission($perms)) {
    $ci->session->set_flashdata('error', lang('access_denied'));

    if ($ci->input->is_ajax_request()) {
      echo ("<script>location.reload()</script>");
      die();
    } else {
      $url = current_url();
      if (isset($_SERVER['HTTP_REFERER'])) $url = $_SERVER['HTTP_REFERER'];

      header("Location: {$url}");
      die;
    }
  }

  return TRUE;
}

/**
 * Get current url.
 */
function currentUrl()
{
  return current_url();
}

function dbgprint($data)
{
  $args = func_get_args();

  foreach ($args as $arg) {
    $str = print_r($arg, TRUE);
    echo ('<pre>');
    echo ($str);
    echo ('</pre>');
  }
}

function dd($data)
{
  if (function_exists('d')) {
    d($data);
    die();
  }
  dbgprint($data);
  die();
}

function dispatchW2PSale($saleId = NULL)
{
  $curl = curl_init('https://admin.indoprinting.co.id/api/v1/printerp-sales');
  $key = 'g4Jlk3cILfITrbN74kwFHD1p9R3v15lmuLU_l3N9k4psUd4hD3rltAL03';
  $res = '';

  $ci = &get_instance();

  if ($sale = $ci->site->getSaleByID($saleId)) {
    $saleJS = getJSON($sale->json_data);

    if ($saleJS->source != 'W2P') {
      setLastError('Sale ID is not from Web2Print.');
      return FALSE;
    }

    $sale_items = $ci->site->getSaleItemsBySaleID($sale->id);
    $pic = $ci->site->getUserByID($sale->created_by);

    if ($sale && $sale_items) {
      $customer = $ci->site->getCustomerByID($sale->customer_id);
      $payments = $ci->site->getPayments(['sale_id' => $sale->id]);
      $payment_validation = $ci->site->getPaymentValidationBySaleID($sale->id);

      if ($customer) {
        $sale->status = lang($sale->status);
        $response['error'] = 0;
        $response['message'] = 'OK';
        $response['key'] = $key;

        $response['data'] = [];
        $response['data']['customer'] = [
          'company' => $customer->company,
          'name'  => $customer->name,
          'phone' => $customer->phone
        ];

        if ($payments) {
          foreach ($payments as $payment) {
            $response['data']['payments'][] = [
              'date' => $payment->date,
              'reference' => $payment->reference,
              'method' => $payment->method,
              'amount' => $payment->amount
            ];
          }
        }

        $response['data']['pic'] = [
          'name' => $pic->fullname
        ];

        $warehouse = $ci->site->getWarehousebyID($sale->warehouse_id);

        $response['data']['sale'] = [
          'no'                      => $sale->reference,
          'date'                    => $sale->date,
          'est_complete_date'       => ($saleJS->est_complete_date ?? ''),
          'payment_due_date'        => ($saleJS->payment_due_date ?? ''),
          'waiting_production_date' => ($saleJS->waiting_production_date ?? ''),
          'grand_total'             => $sale->grand_total,
          'paid'                    => $sale->paid,
          'balance'                 => ($sale->grand_total - $sale->paid),
          'status'                  => lang($sale->status),
          'payment_status'          => lang($sale->payment_status),
          'paid_by'                 => ($sale->payment_method ?? '-'),
          'outlet'                  => $sale->biller,
          'note'                    => htmlDecode($sale->note),
          'warehouse'               => $warehouse->name,
          'warehouse_code'          => $warehouse->code
        ];

        $response['data']['sale_items'] = [];

        foreach ($sale_items as $sale_item) {
          $saleItemJS   = json_decode($sale_item->json_data);
          $operator     = $ci->site->getUserByID($saleItemJS->operator_id ?? NULL);
          $operatorName = ($operator ? $operator->fullname : '');

          $response['data']['sale_items'][] = [
            'product_code' => $sale_item->product_code,
            'product_name' => $sale_item->product_name,
            'price'        => $sale_item->price,
            'subtotal'     => $sale_item->subtotal,
            'width'        => $saleItemJS->w,
            'length'       => $saleItemJS->l,
            'area'         => $saleItemJS->area,
            'quantity'     => $saleItemJS->sqty,
            'spec'         => $saleItemJS->spec,
            'status'       => lang($saleItemJS->status),
            'due_date'     => ($saleItemJS->due_date ?? ''),
            'completed_at' => ($saleItemJS->completed_at ?? ''),
            'operator'     => $operatorName
          ];
        }

        if ($payment_validation) {
          $response['data']['payment_validation'] = [
            'amount'           => $payment_validation->amount,
            'unique_code'      => $payment_validation->unique_code,
            'transfer_amount'  => ($payment_validation->amount + $payment_validation->unique_code),
            'expired_date'     => $payment_validation->expired_date,
            'transaction_date' => $payment_validation->transaction_date,
            'description'      => $payment_validation->description,
            'status'           => lang($payment_validation->status)
          ];
        }
      }
    }

    $body = json_encode($response);

    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    $res = curl_exec($curl);

    if (!$res) {
      setLastError(curl_error($curl));
    }
    curl_close($curl);
  }

  return $res;
}

/**
 * Convert PHP datetime into javascript datetime-local format.
 * @param string $dtPHP PHP datetime format.
 */
function dtJS($dtPHP)
{
  return str_replace(' ', 'T', $dtPHP);
}

function dtLocal(string $dateTime)
{
  $dt = new DateTime($dateTime);
  return $dt->format('d M Y H:i:s');
}

/**
 * Convert javascript datetime-local into PHP datetime format.
 * @param string $dtJS Javascript datetime format.
 */
function dtPHP($dtJS)
{
  return str_replace('T', ' ', $dtJS);
}

/**
 * Complete sale item.
 * @param int $product_id Product ID.
 * @param array $data [ created_by ]
 */
function completeSaleItem($product_id, $data = [])
{
  $ci = &get_instance();

  if ($product = $ci->site->getProductByID($product_id)) {
    $productJS = getJSON($product->json_data);
  }
  return FALSE;
}

/**
 * Return hidden input with csrf token and hash.
 */
function csrf_field()
{
  $ci = &get_instance();

  $csrfToken = $ci->security->get_csrf_token_name();
  $csrfHash  = $ci->security->get_csrf_hash();

  return "<input type=\"hidden\" name=\"{$csrfToken}\" value=\"{$csrfHash}\">";
}

/**
 * Get token hash string.
 * @return string.
 */
function csrf_hash()
{
  $ci = &get_instance();
  return $ci->security->get_csrf_hash();
}

/**
 * Alias of csrf_token_name().
 */
function csrf_token()
{
  return csrf_token_name();
}

/**
 * Get token name.
 * @return string.
 */
function csrf_token_name()
{
  $ci = &get_instance();
  return $ci->security->get_csrf_token_name();
}

/**
 * Export PDF to file or stream (download).
 * @param string $html HTML string data.
 * @param string $filename Filename to export.
 * @param boolean $isDownload Will download PDF by browser if TRUE, export to file otherwhise.
 */
function exportPDF($html, $filename = 'document.pdf', $isDownload = FALSE)
{
  $dompdf = new Dompdf([
    'isHtml5ParserEnabled' => TRUE
  ]);

  $dompdf->loadHtml($html);
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();

  if ($isDownload) {
    $dompdf->stream($filename);
  } else {
    return (file_put_contents(FCPATH . '/assets/uploads/' . $filename, $dompdf->output()) !== FALSE);
  }
}

/**
 * Filter date string.
 * @param string $date Date string. Ex. '2021-05-22 09:00:00'
 * @example 1 filterDateTime('\u832021-05-22 09:30:00\n'); // Return '2021-05-22 09:30:00'
 */
function filterDateTime($date)
{
  if (!$date) return NULL; // If false, always return NULL DO NOT EMPTY STRING !!!

  $obj = explode(' ', $date);
  $result = NULL;

  if ($obj && count($obj) > 1) {
    $date = $obj[0];
    $time = $obj[1];

    if (strlen($date) > 10) { // Length '2021-01-01' == 10
      $dateObj = explode('-', $date);
      $year = (strlen($dateObj[0]) == 4 ? $dateObj[0] : date('Y'));
      $mont = (strlen($dateObj[1]) == 2 ? $dateObj[1] : date('m'));
      $day  = (strlen($dateObj[2]) == 2 ? $dateObj[2] : date('d'));
      $result = "{$year}-{$mont}-{$day}";
    } else if (strlen($date) == 10) {
      $result = $date;
    }

    if (strlen($time) > 8) { // Length '23:00:00' == 8
      $timeObj = explode(':', $time);
      $hour = (strlen($timeObj[0]) == 2 ? $timeObj[0] : date('H'));
      $min  = (strlen($timeObj[1]) == 2 ? $timeObj[1] : date('i'));
      $sec  = (strlen($timeObj[2]) == 2 ? $timeObj[2] : date('s'));
      $result .= " {$hour}:{$min}:{$sec}";
    } else if (strlen($time) == 8) {
      $result .= " {$time}";
    }
  }
  return $result;
}

/**
 * Filter number as decimal with or without floating point.
 * @param mixed $num Number to filter.
 * @example 1 filterDecimal('20,30.2'); // Return 2030.2
 * @return float Return 0 if parameter null or empty string.
 */
function filterDecimal($num)
{
  return (float)preg_replace('/([^\-\.0-9Ee])/', '', $num);
}

/**
 * Filter number as quantity with (max. 6 fp) or without floating point.
 * @param mixed $num Number to filter.
 * @example 1 filterQuantity('35,836.924'); // Return "35836.924000"
 * @example 1 filterQuantity('1.5'); // Return "1.500000"
 * @return string
 */
function filterQuantity($num)
{
  $decimals = (isNumberFloated($num) ? 6 : 0);
  return number_format(filterDecimal($num), $decimals, '.', '');
}

/**
 * Format number as currency.
 * @param mixed $num Number to format.
 * @example 1 formatCurrency('20,3249,2.25'); // Return "Rp2,032,492"
 */
function formatCurrency($num): string
{
  return 'Rp' . number_format(filterDecimal($num));
}

/**
 * Format number as decimal with or without floating point.
 * @param mixed $num Number to format.
 * @example 1 formatDecimal('49,30,3.5'); // Return "49,303.5"
 * @example 2 formatDecimal('35,65,2'); // Return "34,652"
 */
function formatDecimal($num)
{
  $dec = filterDecimal($num);
  $decimals = (isNumberFloated($dec) ? 6 : 0);
  return number_format(filterDecimal($num), $decimals);
}

/**
 * Format number as quantity.
 * @param mixed $qty Number to format.
 * @example 1 formatQuantity(1234.567); // Return "1,234.57"
 */
function formatQuantity($qty): string
{
  return number_format(filterDecimal($qty), (isNumberFloated($qty) ? 2 : 0));
}

/**
 * Format number as stock. Alias for formatQuantity.
 * @param mixed $qty Number to format.
 * @example 1 formatStock(12345.6789); // Return "12,345.68"
 */
function formatStock($qty): string
{
  return formatQuantity($qty);
}

function generateDatatables($db, $columns)
{
  $data  = [];
  $sort = [];
  $ci = &get_instance();

  // Request from client (javascript).
  $iColumns       = $ci->input->get('iColumns') ?? count($columns);
  $iDisplayStart  = $ci->input->get('iDisplayStart');
  $iDisplayLength = $ci->input->get('iDisplayLength');
  $iSortingCols   = $ci->input->get('iSortingCols');
  $sEcho          = $ci->input->get('sEcho');
  $sSearch        = $ci->input->get('sSearch');

  if ($iSortingCols) {
    for ($a = 0; $a < $iSortingCols; $a++) {
      $iSortCol = $ci->input->get("iSortCol_{$a}");
      $sSortDir = $ci->input->get("sSortDir_{$a}");
      $sort[] = ['col' => $iSortCol, 'order' => $sSortDir];
    }

    if ($sort) {
      foreach ($sort as $s) {
        $db->order_by($s['col'], $s['order']);
      }
    }
  }

  $q = $db->get();

  if ($q && $q->num_rows() > 0) {
    foreach ($q->result() as $row) {
      $data[] = $row;
    }
  } else {
  }

  sendJSON([
    'aaData' => $data,
    'iTotalDisplayRecords' => 0,
    'iTotalRecords' => 0,
    'sColumns' => '',
    'sEcho' => 1
  ]);
}

function generateInternalUseUniqueCode(string $category)
{
  $code = '';
  $noCode = true;
  $prefix = [
    'consumable'  => 'C',
    'sparepart'   => 'S'
  ];

  $iuseItems = DB::table('stocks')->isNotNull('internal_use_id')
    ->like('unique_code', $prefix[$category], 'right')
    ->orderBy('internal_use_id', 'DESC')->get(); // Find Cxxxx or Sxxxx

  foreach ($iuseItems as $item) {
    if (!empty($item->unique_code)) {
      $noCode = false;
      $uniqueCode = $item->unique_code;

      $prf = substr($uniqueCode, 0, 1); // Prefix C,S
      $alp = substr($uniqueCode, 1, 1); // Alphabet A,B,C,...
      $idx = substr($uniqueCode, 2); // Index 0001,0002,0003,...

      if (intval($idx) == 9999) {
        $a = ord($alp);

        if ($a == 90) { // if Z reset to A
          $a = 65;
        } else {
          $a++;
        }

        $code = $prf . chr($a) . '0001';
      } else {
        $i = intval($idx);
        $i++;

        // Prepend zero.
        $id = strval($i);
        $id = ($i < 1000 ? ($i < 100 ? ($i < 10 ? '000' . $id : '00' . $id) : '0' . $id) : $id);

        $code = $prf . $alp . $id;
      }

      break;
    }
  }

  return ($noCode ? $prefix[$category] . 'A0001' : $code);
}

/**
 * Generate payment code for payment validation.
 * @return int Return payment code.
 */
function generateUniquePaymentCode()
{
  return mt_rand(1, 100);
}

function uuid()
{
  return generateUUID();
}

/**
 * Generate Universally Unique Identifier.
 * @return string
 */
function generateUUID(): string
{
  $time_low    = bin2hex(random_bytes(4));
  $time_mid    = bin2hex(random_bytes(2));
  $time_hi_ver = bin2hex(random_bytes(2));
  $clock_seq   = bin2hex(random_bytes(2));
  $node        = bin2hex(random_bytes(6));

  $uuid = strtoupper("{$time_low}-{$time_mid}-{$time_hi_ver}-{$clock_seq}-{$node}");

  return $uuid;
}

/**
 * Determine active stock days.
 * @param array $data [ *product_id, *warehouse_id, *days, *end_date ]
 * @return int Return days of active stock. Default 1 day if any problem.
 */
function getActiveStockDays(array $data)
{
  if (!empty($data)) {
    $ci = &get_instance();
    $days = 0;

    $opt = [
      'end_date' => $data['end_date']
    ];

    for ($a = 0; $a < $data['days']; $a++) {
      $qty = $ci->site->getStockQuantity($data['product_id'], $data['warehouse_id'], $opt);

      $opt['end_date'] = date('Y-m-d', strtotime('+1 day', strtotime($opt['end_date'])));

      if ($qty > 0) $days++;
    }

    return ($days > 0 ? $days : 1);
  }
  return 1;
}

/**
 * Get adjusted quantity for Overwrite mode.
 * @param float $current_qty Current Quantity.
 * @param float $adjustment_qty Quantity to adjust.
 * @return array Return adjusted quantity object.
 */
function getAdjustedQty($current_qty, $adjustment_qty): array
{
  $data = ['quantity' => 0, 'type' => 'received'];
  $adj_qty = filterDecimal($adjustment_qty);
  $cur_qty = filterDecimal($current_qty);

  if ($adj_qty > $cur_qty) {
    $data['quantity'] = filterQuantity($adj_qty - $cur_qty);
    $data['type']     = 'received';
  } else if ($adj_qty < $cur_qty) {
    $data['quantity'] = filterQuantity($cur_qty - $adj_qty);
    $data['type']     = 'sent';
  }
  return $data;
}

/**
 * Get Attachment paths.
 * @param string $pathName Path name to get. Default NULL.
 * @return array|mixed Return array if pathName is not specified or string otherwise.
 */
function getAttachmentPaths($pathName = NULL)
{
  $dir = FCPATH . 'files/';
  $paths = [
    'adjustments'        => "{$dir}products/adjustments/attachments/",
    'expenses'           => "{$dir}finances/expenses/attachments/",
    'incomes'            => "{$dir}finances/incomes/attachments/",
    'internal_uses'      => "{$dir}procurements/internal_uses/attachments/",
    'mutations'          => "{$dir}finances/mutations/attachments/",
    'products'           => "{$dir}products/attachments/",
    'products_import'    => "{$dir}products/imports/",
    'products_mutation'  => "{$dir}products/mutations/attachments/",
    'products_report'    => "{$dir}products/reports/",
    'products_transfer'  => "{$dir}products/transfers/attachments/",
    'purchases'          => "{$dir}procurements/purchases/",
    'purchases_payments' => "{$dir}procurements/purchases/payments/",
    'sales'              => "{$dir}sales/attachments/",
    'sales_payments'     => "{$dir}sales/payments/",
    'stock_opnames'      => "{$dir}products/stock_opnames/attachments/",
    'transfers'          => "{$dir}procurements/transfers/",
    'transfers_payments' => "{$dir}procurements/transfers/payments/",
    'trackingpod'        => "{$dir}trackingpod/attachments/"
  ];
  if ($pathName) {
    return ($paths[$pathName] ?? NULL);
  } else {
    foreach ($paths as $name => $path) {
      $data[] = $path;
    }
    return $data;
  }
}

/**
 * Get current month period.
 * @param array $period [ start_date, end_date ]
 * @return array ['start_date', 'end_date']
 */
function getCurrentMonthPeriod($period = [])
{
  $period['start_date'] = ($period['start_date'] ?? date('Y-m-') . '01');
  $period['end_date']   = ($period['end_date']   ?? date('Y-m-d'));

  return $period;
}

/**
 * Get current week of month.
 */
function getCurrentWeekOfMonth(): int
{
  return intval(date('W')) % 4;
  // return intval(date('W')) - intval(date('W', strtotime(date('Y-m') . '-01'))) + 1;
}

/**
 * Get daily performance report. biller_id MUST BE Array (PROGRESS). period = yyyy-mm
 * @param array $opt [ biller_id[], period ]
 * @return array Return daily performance data.
 */
function getDailyPerformanceReport($opt)
{
  // We need biller to warehouse because ONLY warehouse has 'active' column.
  $dailyPerformanceData = [];
  $billers    = [];
  $warehouses = [];

  if (!empty($opt['biller_id']) && is_array($opt['biller_id'])) {
    foreach ($opt['biller_id'] as $billerId) {
      $billers[] = Biller::getRow(['id' => $billerId, 'active' => '1']);
    }

    if ($warehouseIds = billerToWarehouse($opt['biller_id'])) {
      foreach ($warehouseIds as $warehouseId) {
        $warehouses[] = Warehouse::getRow(['id' => $warehouseId, 'active' => '1']);
      }
    }
  } else if (empty($opt['biller_id'])) {
    $billers    = Biller::get(['active' => '1']);
    $warehouses = Warehouse::get(['active' => '1']);
  }

  if ($opt['period']) {
    $period = new DateTime($opt['period'] . '-01');
    unset($opt['period']);
  } else {
    $period = new DateTime(date('Y-m-') . '01'); // Current month and date.
  }

  $currentDate  = new DateTime();
  $beginDate    = new DateTime('2022-01-01 00:00:00'); // First data date of begin date.
  $startDate    = new DateTime($period->format('Y-m-d')); // First date of current period.
  $endDate      = new DateTime($period->format('Y-m-t')); // Date must be end of month. (28 to 31)
  $activeDays   = intval($startDate->diff($currentDate)->format('%a'));

  $firstDate  = 1; // First date of month.
  $lastDate   = intval($endDate->format('j')); // Date only. COUNTABLE
  $ymPeriod   = $period->format('Y-m'); // 2022-11

  foreach ($billers as $biller) {
    if ($biller->active != 1) continue;
    // Hide FUCKED IDS
    // if ($biller->code == 'BALINN') continue;
    if ($biller->code == 'IDSUNG') continue;
    if ($biller->code == 'IDSLOS') continue;
    if ($biller->code == 'BALINT') continue;

    $dailyData = [];

    $billerJS = getJSON($biller->json_data);
    $warehouse = Warehouse::getRow(['code' => $biller->code]);

    if ($biller->code == 'LUC') { // Lucretia method is different.
      $revenue = round(DB::table('product_transfer')
        ->selectSum('grand_total', 'total')
        ->where('warehouse_id_from', $warehouse->id)
        ->where("created_at BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'")
        ->getRow()->total ?? 0);

      for ($a = $firstDate; $a <= $lastDate; $a++) {
        $dt       = prependZero($a);
        $dtDaily  = new DateTime("{$ymPeriod}-{$dt}");

        $overTime = ($currentDate->diff($dtDaily)->format('%R') == '+' ? TRUE : FALSE);

        if (!$overTime) {
          $dailyRevenue = round(DB::table('product_transfer')
            ->selectSum('grand_total', 'total')
            ->where('warehouse_id_from', $warehouse->id)
            ->where("created_at LIKE '{$ymPeriod}-{$dt}%'")
            ->getRow()->total ?? 0);
        } else {
          $dailyRevenue = 0;
        }

        $stockValue = getWarehouseStockValue($warehouse->id, [
          'start_date'  => $beginDate->format('Y-m-d'),
          'end_date'    => "{$ymPeriod}-{$dt}"
        ]); // sql

        if (!$overTime) {
          $piutang  = round(DB::table('product_transfer')
            ->selectSum('(grand_total - paid)', 'total')
            ->where('warehouse_id_from', $warehouse->id)
            ->where("created_at BETWEEN '{$beginDate->format('Y-m-d')} 00:00:00' AND '{$ymPeriod}-{$dt}%'")
            ->getRow()->total ?? 0);
        } else {
          $piutang = 0;
        }

        $dailyData[] = [
          'revenue'     => $dailyRevenue,
          'stock_value' => $stockValue,
          'piutang'     => $piutang
        ];
      }
    } else { // All warehouses except Lucretia.
      $sale = DB::table('sales')
        ->selectSum('grand_total', 'total')
        ->where('biller_id', $biller->id)
        ->where("date BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'");

      // I/O MANIP: Tanggal lebih dari 2023-01-01 00:00:00, maka jangan include sale.status = need_payment.
      if (strtotime($startDate->format('Y-m-d')) >= strtotime('2023-01-01 00:00:00') || strtotime($endDate->format('Y-m-d')) >= strtotime('2023-01-01 00:00:00')) {
        $sale->notLike('status', 'need_payment', 'none');
      }

      $revenue = round($sale->getRow()->total ?? 0);

      for ($a = $firstDate; $a <= $lastDate; $a++) {
        $dt = prependZero($a);
        $dtDaily = new DateTime("{$ymPeriod}-{$dt}");

        $overTime = ($currentDate->diff($dtDaily)->format('%R') == '+' ? TRUE : FALSE);

        if (!$overTime) {
          $dailyRevenue = round(DB::table('sales')
            ->selectSum('grand_total', 'total')
            ->where('biller_id', $biller->id)
            ->where("date LIKE '{$ymPeriod}-{$dt}%'")
            ->getRow()->total ?? 0);
        } else {
          $dailyRevenue = 0;
        }

        if ($warehouse) {
          $stockValue = getWarehouseStockValue($warehouse->id, [
            'start_date'  => $beginDate->format('Y-m-d'),
            'end_date'    => "{$ymPeriod}-{$dt}"
          ]); // sql
        } else {
          $stockValue = 0;
        }

        if (!$overTime) {
          $piutang  = round(DB::table('sales')
            ->selectSum('balance', 'total')
            ->notLike('payment_status', 'paid')
            ->where('biller_id', $biller->id)
            ->whereIn('status', ['waiting_production', 'completed_partial', 'completed'])
            ->where("date BETWEEN '{$beginDate->format('Y-m-d')} 00:00:00' AND '{$ymPeriod}-{$dt}%'")
            ->getRow()->total ?? 0);
        } else {
          $piutang = 0;
        }

        $dailyData[] = [
          'revenue'     => $dailyRevenue,
          'stock_value' => $stockValue,
          'piutang'     => $piutang
        ];
      }
    }

    // $activeDays     = intval($startDate->diff($currentDate)->format('%d'));
    $daysInMonth    = getDaysInMonth($startDate->format('Y'), $startDate->format('n'));
    $averageRevenue = ($revenue / $activeDays);

    $dailyPerformanceData[] = [
      'biller_id'   => $biller->id,
      'biller'      => $biller->name,
      'avg_revenue' => round($averageRevenue),
      'forecast'    => round($averageRevenue * $daysInMonth),
      'revenue'     => round($revenue), // total sales even not paid.
      'target'      => ($billerJS->target ?? 0), // set on biller
      'daily_data'  => $dailyData // [['revenue' => 100, 'stock_value' => 200, 'piutang' => 300]]
    ];
  }

  return $dailyPerformanceData;
}

/**
 * Get day name from index.
 * @param int $index Index of the day. 1 = Minggu, 7 = Sabtu.
 * @example 1 getDayName(2); // Return "senin".
 */
function getDayName(int $index): string
{
  if ($index == 0) return NULL;

  $days = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
  $x = filterDecimal($index);
  return $days[($x - 1) % 7];
}

/**
 * Get days between two period.
 * @param string $startDate Start Date.
 * @param string $endDate End Date.
 * @return int Return days.
 */
function getDaysInPeriod($startDate, $endDate)
{
  $sdate = new DateTime($startDate);
  $edate = new DateTime($endDate);

  $diff = $sdate->diff($edate);

  return (int)$diff->format('%r%a');
}

/**
 * Get total days in a month.
 * @param int $year Year.
 * @param int $month Month.
 * @example 1 getDaysInMonth(2021, 2); // Return 28
 */
function getDaysInMonth($year, $month)
{
  return cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year));
}

/**
 * Get excerpt text.
 * @param string $text Text to excerpt.
 * @param int $length Return text length include '...'. Default: 20
 */
function getExcerpt($text, $length = 20)
{
  $len = filterDecimal($length);
  $text_len = strlen($text);
  if ($len < 3 || !$len) $len = 3;
  if ($text_len <= ($length - 3)) {
    return $text;
  }
  return substr($text, 0, $len - 3) . '...';
}

/**
 * Get GET request.
 * @param string $name Request name.
 */
function getGet($name)
{
  return (isset($_GET[$name]) ? $_GET[$name] : NULL);
}

/**
 * Get google spreadsheet.
 * @param int $sheetId Sheet ID.
 * @param string $ranges A1 Notation ranges. Ex. A1:C4
 * @return array Return spreadsheet values.
 */
function getGoogleSheet($sheetId, $ranges)
{
  // Service Account: gsheet@indoprinting-20221007.iam.gserviceaccount.com
  $tokenFile = FCPATH . 'app/credentials/indoprinting-20221007-ea745a0d9354.json';
  $client = new Google\Client();

  $client->setApplicationName('PrintERP');
  $client->setAuthConfig($tokenFile);
  $client->setAccessType('offline');
  $client->setScopes([Google\Service\Sheets::SPREADSHEETS]);

  $service = new Google\Service\Sheets($client);

  $res = $service->spreadsheets_values->get($sheetId, $ranges);

  return $res->getValues();
}

/**
 * Get income statement report.
 * @param array $opt [ biller_id[], start_date, end_date ]
 * @return array Return income statement data.
 */
function getIncomeStatementReport($opt)
{
  // Lucretia gunakan harga average cost.
  // Outlet gunakan harga mark-on.

  $ci = &get_instance();
  $lucretaiMode = FALSE;

  // BEGIN COLLECT DATA.
  $expenses     = $ci->site->getExpenses($opt);
  $expenseGroup = $ci->site->getExpenseCategories(['order' => ['name', 'ASC']]);
  $incomes      = $ci->site->getIncomes($opt);
  $incomeGroup  = $ci->site->getIncomeCategories();
  $internalUses = $ci->site->getStockInternalUses($opt);
  $sales        = $ci->site->getSales($opt);
  // d($opt); die();
  $warehouse_ids = billerToWarehouse($opt['biller_id']); // Convert biller to warehouse.

  $billerLucretai = $ci->site->getBillerByName('Lucretia Enterprise');

  if (gettype($opt['biller_id']) !== 'array' && $opt['biller_id'] == $billerLucretai->id) {
    $lucretaiMode = TRUE;
  } else if (is_array($opt['biller_id'])) {
    foreach ($opt['biller_id'] as $biller_id) {
      if ($biller_id == $billerLucretai->id) $lucretaiMode = TRUE;
    }
  }

  $opt['warehouse_id'] = $warehouse_ids; // Assign warehouse_id.
  $billerIds = $opt['biller_id'];
  unset($opt['biller_id']);

  $purchases    = $ci->site->getStockPurchases($opt);
  $stockOpnames = $ci->site->getStockOpnames($opt);

  $opt['from_warehouse_id'] = $warehouse_ids;
  unset($opt['warehouse_id']);

  // $internalUses = $ci->site->getStockInternalUses($opt);
  // Old Transfers.
  // $transfers    = $ci->site->getStockTransfers($opt);
  unset($opt['from_warehouse_id']);

  // New Product Transfers.
  $opt['warehouse_id_from'] = $warehouse_ids;

  $transfers = ProductTransfer::get($opt);

  $startDate  = ($opt['start_date'] ?? date('Y-m-') . '01');
  $endDate    = ($opt['end_date'] ?? date('Y-m-d'));

  unset($opt); // Filter options end here.

  $capInvAmount = 0;
  $invCost = [
    '32. Purchase of Vehicle', '33. Purchase of Land and Building', '34. Purchase of Production Machine',
    '35. Purchase of Finishing Machine', '36. Purchase of Computers and Supporting Equipment',
    '37. Purchase of Building Construction', '38. Purchase of Another Investation Cost',
  ];
  $invCostData = [];
  $invCostAmount = 0;
  $expenseAmount = 0;
  $expenseData   = [];
  $incomeAmount  = 0;
  $incomeData    = [];
  $internalUseAmount = 0;
  $internalUseData = [];
  $priveAmount = 0;
  $purchaseAmount = 0;
  $revenue       = 0;
  $soldItemCost  = 0;
  $soAmount = 0;
  $transferAmount = 0;
  $transferItemCost = 0;

  // EXPENSES
  foreach ($expenseGroup as $exgroup) {
    $amount = 0;

    if (strcasecmp($exgroup->name, 'Capital Investment (Not used)') === 0) continue; // Ignored.
    if (strcasecmp($exgroup->name, 'Sales TB') === 0) continue; // Ignore Sales TB.

    foreach ($expenses as $expense) {
      if ($expense->category_id == $exgroup->id) {
        $amount += $expense->amount;
      }
    }

    if (strcasecmp($exgroup->name, 'Prive') === 0) {
      $priveAmount += $amount;

      continue;
    }

    if (array_search($exgroup->name, $invCost) !== FALSE) { // Biaya Investasi.
      $invCostAmount += $amount;

      $invCostData[] = [
        'name' => $exgroup->name,
        'amount' => $amount
      ];

      continue;
    }

    $expenseAmount += $amount;

    $expenseData[] = [
      'name'   => $exgroup->name,
      'amount' => $amount
    ];
  }

  // INCOMES
  foreach ($incomeGroup as $ingroup) {
    $amount = 0;

    if (strcasecmp($ingroup->name, 'Sales TB') === 0) continue; // Ignore Sales TB.
    if (strcasecmp($ingroup->name, 'Penanaman Modal') === 0) continue; // Ignored.
    if (strcasecmp($ingroup->name, 'Pendapatan Baltis Inn') === 0) continue; // Ignored.
    if (strcasecmp($ingroup->name, 'Setoran Kewajiban IDS') === 0) continue; // Ignored.

    foreach ($incomes as $income) {
      if ($income->category_id == $ingroup->id) {
        $amount += $income->amount;
      }
    }

    if (strcasecmp($ingroup->name, 'Capital Investment') === 0) {
      $capInvAmount += $amount;

      continue;
    }

    $incomeAmount += $amount;

    $incomeData[] = [
      'name'   => $ingroup->name,
      'amount' => $amount
    ];
  }

  // INTERNAL USES.
  $iuCategories = ['consumable', 'sparepart'];

  foreach ($iuCategories as $iuCategory) {
    // if ($iuCategory == 'sparepart' && !$lucretaiMode) continue; // Ignore sparepart if not lucretai.

    $amount = 0;

    foreach ($internalUses as $internalUse) {
      if ($internalUse->category == $iuCategory) {
        $amount += $internalUse->grand_total;
      }
    }

    $internalUseAmount += $amount;

    $internalUseData[] = [
      'name'   => ucfirst($iuCategory),
      'amount' => $amount
    ];
  }

  if ($purchases) {
    foreach ($purchases as $purchase) {
      $purchaseAmount += $purchase->grand_total;
    }
  }

  $saleCount = 0;

  // SALES
  foreach ($sales as $sale) {
    // I/O MANIP: Tanggal lebih dari 2023-01-01 00:00:00, maka jangan include sale.status = need_payment.
    if (strtotime($startDate) >= strtotime('2023-01-01 00:00:00') || strtotime($endDate) >= strtotime('2023-01-01 00:00:00')) {
      if (strcasecmp($sale->status, 'need_payment') === 0) continue;
    }

    // #1 Revenue.
    $revenue += $sale->grand_total;
    $saleCount++;

    $saleItems = SaleItem::get(['sale_id' => $sale->id]);

    if ($saleItems) {
      foreach ($saleItems as $saleItem) {
        if ($saleItem->product_type == 'combo') {
          // Selling item to raw materials;
          $comboItems = ComboItem::get(['product_id' => $saleItem->product_id]);

          foreach ($comboItems as $comboItem) {
            // Raw material.
            $item = Product::getRow(['code' => $comboItem->item_code]);

            // #2 Cost of Goods > Sold Items Cost.
            $soldItemCost += round($item->markon_price * $comboItem->quantity * $saleItem->finished_qty);
          }
        }
      }
    }
  }

  // STOCK OPNAMES
  foreach ($stockOpnames as $stockOpname) {
    $soAmount += ($stockOpname->total_lost + $stockOpname->total_plus);
  }

  // If SO Amount plus then make minus, if minus make plus.
  $soAmount = ($soAmount * -1);

  // TRANSFERS
  foreach ($transfers as $transfer) {
    $transferItems = $ci->site->getStocks(['transfer_id' => $transfer->id, 'status' => 'sent']);

    if ($transferItems) {
      foreach ($transferItems as $transferItem) {
        $product = $ci->site->getProductByID($transferItem->product_id);
        // $transferItemCost += ($transferItem->price * $transferItem->quantity);
        // $transferItemCost += ($product->avg_cost * $transferItem->quantity);
        $transferItemCost += ($product->cost * $transferItem->quantity);
      }
    }

    $transferAmount += $transfer->grand_total;
  }

  if ($lucretaiMode) { // Change revenue if lucretai mode enabled.
    $revenue = $transferAmount;
    $soldItemCost = $transferItemCost;
  }

  $costOfGoodsData   = [['name' => 'RAW Materials', 'amount' => $soldItemCost]];
  $costOfGoodsData   = array_merge($costOfGoodsData, $internalUseData);
  $costOfGoodsData   = array_merge($costOfGoodsData, [['name' => 'Lost of Goods', 'amount' => $soAmount]]);
  $costOfGoodsAmount = getTotalAmountIncomeStatement($costOfGoodsData); // Sold Item Cost, Internal Use.

  $grossProfit = ($revenue - $costOfGoodsAmount);
  $netProfit   = ($grossProfit + $incomeAmount - $expenseAmount);
  $balanceSheetAmount = ($netProfit - $invCostAmount + $capInvAmount - $priveAmount);

  $incomeStatementData = [
    ['name' => 'Revenue', 'amount' => $revenue],
    ['name' => 'Cost of Goods', 'amount' => $costOfGoodsAmount, 'data' => $costOfGoodsData],
    ['name' => 'Gross Profit', 'amount' => $grossProfit],
    ['name' => 'Other Income', 'amount' => $incomeAmount, 'data' => $incomeData],
    ['name' => 'Operational Cost', 'amount' => $expenseAmount, 'data' => $expenseData],
    ['name' => 'Net Profit', 'amount' => $netProfit],
    ['name' => 'Investation Cost', 'amount' => $invCostAmount, 'data' => $invCostData],
    ['name' => 'Capital Investment', 'amount' => $capInvAmount],
    ['name' => 'Prive', 'amount' => $priveAmount],
    ['name' => 'Balance Sheet', 'amount' => $balanceSheetAmount]
  ];

  return $incomeStatementData;
}

/**
 * Get and parse JSON string.
 * @param string $jsonStr JSON string.
 * @param bool $assoc Return as associative array. Default FALSE.
 * @return object Return JSON object.
 */
function getJSON($jsonStr, $assoc = FALSE)
{
  $json = json_decode($jsonStr, $assoc);
  $json = (!$json && !$assoc ? (object)[] : (!$json && $assoc ? [] : $json));
  return $json;
}

/**
 * Get last error.
 *
 * @return string|null Return last error message or NULL if no last error.
 */
function getLastError()
{
  return ($_SESSION['lastErrorMsg'] ?? NULL);
}

/**
 * Get last 30 days period.
 *
 * This function created due F\*CKED ST\*PID `Felix Angga Asiskin` DIDN'T UNDERSTAND ABOUT FILTERING.
 */
function getLastMonthPeriod($period = [])
{
  $data['start_date'] = ($period['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
  $data['end_date']   = ($period['end_date']   ?? date('Y-m-d'));

  return $data;
}

function getProductReportDuration($machineId)
{
  $ci = &get_instance();
}

/**
 * Get Mark-On percent from cost and markon price.
 * @param float $cost Item cost.
 * @param float $markon_price Mark-on Price or Warehouse price.
 */
function getMarkon($cost, $markon_price)
{
  return round(1 - (filterDecimal($cost) / filterDecimal($markon_price))) * 100;
}

/**
 * Get Mark-on price or Warehouse price from cost and mark-on.
 * @param float $cost Item cost.
 * @param float $markon Mark-on percent.
 */
function getMarkonPrice($cost, $markon)
{
  return round(filterDecimal($cost) / (1 - (filterDecimal($markon) / 100)));
}

/**
 * Get month name by index.
 * @param int $index Month index.
 * @example 1 getMonthName(8); // Return 'agustus'.
 */
function getMonthName($index)
{
  $months = [
    NULL, 'januari', 'februari', 'maret', 'april', 'mei', 'juni',
    'juli', 'agustus', 'september', 'oktober', 'november', 'desember'
  ];
  $x = filterDecimal($index);
  return $months[$x % 13];
}

/**
 * Get longest date and time from datetime array.
 * @param array $dateTimes Array of datetime string.
 * @example 1 getLongestDateTime(['2021-05-21 10:00:00', '2021-05-22 06:00:00']);
 * @return string|null Return '2021-05-22 06:00:00' or NULL if error.
 */
function getLongestDateTime($dateTimes)
{
  if ($dateTimes && is_array($dateTimes)) {
    $longestDateTime = NULL;

    foreach ($dateTimes as $dateTime) {
      if (!$longestDateTime) {
        $longestDateTime = $dateTime;
      } else {
        if (strtotime($dateTime) > strtotime($longestDateTime)) {
          $longestDateTime = $dateTime;
        }
      }
    }
    return $longestDateTime;
  }
  return NULL;
}

/**
 * Get order stock quantity by current stock, min order and safety stock.
 * @param float $current_stock Current stock of item.
 * @param float $min_order_qty Min. order of item.
 * @param float $safety_stock Safety stock of item.
 * @example 1 getOrderStock(4, 3, 15); // Return 12.
 *
 */
function getOrderStock($current_stock, $min_order_qty, $safety_stock)
{
  $curr_stock  = filterDecimal($current_stock);
  $min_order   = filterDecimal($min_order_qty);
  $safe_stock  = filterDecimal($safety_stock);
  $order_stock = 0;

  if ($curr_stock < $safe_stock) { // Safe stock (current stock < safe_stock)
    $rest_stock = round($safe_stock - $curr_stock); // 400 - (-10) = 410 < 224 = false
    $order_stock = (ceil($rest_stock / $min_order) * $min_order); // (410 / 224) * 224
  }
  return $order_stock;
}

/**
 * Get past month period.
 * @param int $month How many past month.
 * @example 1 getPastMonthPeriod(1);
 * // Return ['start_date' => '2020-01-01', 'end_date' => '2020-01-31', 'days' => 31]
 */
function getPastMonthPeriod($month)
{
  $mn   = intval($month);
  $base = strtotime(date('Y-m-') . '01'); // Current year and month with date 1.
  $y    = date('Y', strtotime('-1 month', $base));
  $m    = date('n', strtotime('-1 month', $base));
  $days = 0;

  $start_date = date('Y-m', strtotime("-{$mn} month", $base)) . '-01';
  $end_date   = date('Y-m', strtotime('-1 month', $base)) . '-' . getDaysInMonth($y, $m);

  for ($a = 1; $a <= $mn; $a++) {
    $days += getDaysInMonth(date('Y', strtotime("-{$a} month", $base)), date('n', strtotime("-{$a} month", $base)));
  }

  return [
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'days'       => $days // Total days.
  ];
}

/**
 * Get user permission from permission name.
 * @param string $permission_name Permission Name.
 * @param int $user_id (Optional) Specify user id. Default logged in user id.
 * @return bool
 */
function getPermission($permission_name, $user_id = NULL)
{
  $ci = &get_instance();

  // If user_id = NULL then use user_id session.
  if (!$user_id) {
    $user_id = XSession::get('user_id');
  }

  $user = $ci->site->getUserByID($user_id);
  $userGroup = $ci->site->getUserGroup($user_id);

  // Always grant for OWNER or ADMIN group.
  if (strcasecmp($userGroup->name, 'Owner') == 0 || strcasecmp($userGroup->name, 'Admin') == 0) {
    return TRUE;
  }

  if ($user) {
    $jsdata = json_decode($user->json_data);
    $user_perms = (!empty($jsdata->{'permissions'}) ? $jsdata->permissions : NULL);

    if (!empty($user_perms->{$permission_name})) {
      $perms = $user_perms; // Get permission by individual user.
    } else if (XSession::get('permissions')) {
      $perms = XSession::get('permissions'); // Get permission by session.
    } else {
      $perms = $ci->site->getGroupPermissions($user->group_id); // Get permission by group.
    }

    return (empty($perms->{$permission_name}) ? FALSE : TRUE);
  }
  return FALSE;
}

/**
 * Get POST request.
 * @param string $name Request name.
 */
function getPOST($name)
{
  return (isset($_POST[$name]) ? $_POST[$name] : NULL);
}

/**
 * Get product price by quantity.
 *
 * Ex. getProductPriceByQty(['product_id' => 1, 'price_group_id' => 3, 'quantity' => 10]);
 * // Return 20000.
 *
 * @param array $productData [ *product_id, *price_group_id, *quantity ]
 * @return float Return final price of product.
 */
function getProductPriceByQty($productData)
{
  $ci = &get_instance();
  $products  = $ci->site->getProductByID($productData['product_id']);

  if ($products) {
    $price_group = $ci->site->getProductGroupPrice($products->id, $productData['price_group_id']);
    $price_ranges = json_decode($products->price_ranges_value, TRUE);


    if ($price_group && $price_ranges) {
      $price = 0;
      $price_group->price1 = $price_group->price;

      for ($a = (count($price_ranges) - 1); $a >= 0; $a--) {
        if ($productData['quantity'] >= $price_ranges[$a]) {
          return filterDecimal($price_group->{'price' . ($a + 2)});
        } else {
          $price = filterDecimal($price_group->price1); // Default if below price_ranges[0].
        }
      }

      return $price;
    }
  }
  return NULL;
}

/**
 * Get purchase item average cost.
 * @param int $product_id Product ID.
 * @param array $period [ start_date, end_date ]
 * @return float Average cost.
 */
function getProductAvgCost($product_id, $period = [])
{
  $ci = &get_instance();
  // $periode = ($period ? getCurrentMonthPeriod($period) : getPastMonthPeriod()); // 6 Months ago.
  $subTotal = 0;
  $totalQty = 0;
  $avgCost = 0;

  // unset($periode['days']); // Remove from getPastMonthPeriod().

  $purchases = $ci->site->getStockPurchases($period);

  foreach ($purchases as $purchase) {
    $purchaseItems = $ci->site->getStockPurchaseItems($purchase->id);

    foreach ($purchaseItems as $purchaseItem) {
      if ($purchaseItem->product_id != $product_id) continue; // If product id is not match, ignore it.

      $subTotal += ($purchaseItem->cost * $purchaseItem->quantity);
      $totalQty += $purchaseItem->quantity;
    }
  }

  if ($subTotal && $totalQty) {
    $avgCost = round($subTotal / $totalQty); // Must execute this.
  } else { // Not recommended.
    $product = $ci->site->getProductByID($product_id);

    if ($product) {
      $avgCost = round($product->cost);
    }
  }

  unset($purchases, $purchaseItems, $subTotal, $totalQty, $periode, $product);

  return $avgCost;
}

function getPurchaseQty($qty, $qty_alert, $unit)
{
  $qty = (empty($qty) ? 0 : $qty);
  switch ($unit->operator) {
    case '*': {
        $r = ceil(($qty_alert / $unit->operation_value) - ($qty / $unit->operation_value)) * $unit->operation_value;
        return $r;
      }
  }
  return $qty_alert;
}

/**
 * Get product stock value data.
 * @param array $clause [ product_id, warehouse_id, category_id, product_name,
 *  is_asset:FALSE, start_date, end_date ]
 * @return array [[ product_code, product_name, unit, beginning, increase, decrease, balance, cost, value ]]
 */
function getProductStockValue($clause = [])
{
  $ci = &get_instance();

  $categoryId  = ($clause['category_id'] ?? NULL);
  $productName = ($clause['product_name'] ?? NULL);
  $warehouseId = ($clause['warehouse_id'] ?? NULL);
  $startDate   = ($clause['start_date'] ?? NULL);
  $endDate     = ($clause['end_date'] ?? NULL);

  $beginClause = '';
  $currentClause = '';

  $isAsset = ($clause['is_asset'] ?? FALSE);
  $lucretaiMode = FALSE;

  if ($startDate) {
    $endDate = ($endDate ?? date('Y-m-d'));

    $beginClause   .= "AND date < '{$startDate} 00:00:00'";
    $currentClause .= "AND date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'";
  } else {
    $endDate = ($endDate ?? date('Y-m-d'));
    $beginClause .= "AND date < '{$endDate} 00:00:00'";
  }

  if ($warehouseId) {
    if (gettype($warehouseId) == 'array') {
      foreach ($warehouseId as $whId) {
        $warehouse = $ci->site->getWarehouseByID($whId);

        if ($warehouse->code == 'LUC') $lucretaiMode = TRUE;
      }

      $wh = implode(',', $warehouseId);
      $beginClause   .= " AND warehouse_id IN ({$wh})";
      $currentClause .= " AND warehouse_id IN ({$wh})";
      unset($wh);
    } else {
      $warehouse = $ci->site->getWarehouseByID($warehouseId);

      if ($warehouse && $warehouse->code == 'LUC') $lucretaiMode = TRUE;

      $beginClause   .= " AND warehouse_id = {$warehouseId}";
      $currentClause .= " AND warehouse_id = {$warehouseId}";

      unset($warehouse);
    }
  }

  if ($categoryId) {
    $beginClause   .= " AND category_id = {$categoryId}";
    $currentClause .= " AND category_id = {$categoryId}";
  }

  $query = "products.id AS product_id,
    products.code AS product_code,
    products.name AS product_name,
    units.code AS product_unit,
    categories.name AS category_name, products.type AS product_type, products.iuse_type AS iuse_type,";

  //* QUERY BEGINNING
  if ($startDate) {
    $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0)) AS beginning,";
  } else {
    $query .= "'0' AS beginning,";
  }

  //* QUERY INCREASE
  $query .= "COALESCE(stock_recv.total, 0) AS increase,";

  //* QUERY DECREASE
  $query .= "COALESCE(stock_sent.total, 0) AS decrease,";

  //* QUERY BALANCE
  if ($startDate) {
    $query .= "(COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
  } else {
    $query .= "(COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS balance,";
  }

  //* QUERY AVG COST / MARK-ON PRICE
  if ($lucretaiMode) { // If Lucretai mode.
    $query .= "products.cost AS cost,";
    // $query .= "products.avg_cost AS new_cost,";
  } else {
    $query .= "products.markon_price AS cost,"; // All outlet except Lucretai.
  }

  //* QUERY STOCK VALUE
  $cost = ($lucretaiMode ? 'products.cost' : 'products.markon_price');

  if ($startDate) {
    $query .= "{$cost} * (COALESCE(stock_begin_recv.total, 0) - COALESCE(stock_begin_sent.total, 0) + COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS value";
  } else {
    $query .= "{$cost} * (COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS value";
  }

  /* EXECUTE QUERIES */
  $ci->db->select($query)->from('products');

  // JOIN BEGINNING
  if ($startDate) {
    $ci->db
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$beginClause} GROUP BY product_id) stock_begin_recv", 'stock_begin_recv.product_id = products.id', 'left')
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$beginClause} GROUP BY product_id) stock_begin_sent", 'stock_begin_sent.product_id = products.id', 'left');
  }

  // JOIN INCREASE OR BALANCE
  $ci->db
    ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'received' {$currentClause} GROUP BY product_id) stock_recv", 'stock_recv.product_id = products.id', 'left');

  // JOIN DECREASE OR BALANCE
  $ci->db
    ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks WHERE status LIKE 'sent' {$currentClause} GROUP BY product_id) stock_sent", 'stock_sent.product_id = products.id', 'left');

  // JOIN UNIT
  $ci->db
    ->join('units', 'units.id=products.unit', 'left');

  // JOIN CATEGORY
  $ci->db
    ->join('categories', 'categories.id = products.category_id', 'left');

  if ($productName) {
    $ci->db
      ->group_start()
      ->like('products.code', $productName, 'both')
      ->or_like('products.name', $productName, 'both')
      ->group_end();
  }

  if ($categoryId) {
    $ci->db->where('products.category_id', $categoryId);
  }

  $ci->db
    ->where_in('products.type', ['standard']); // Only standard allowed.

  if ($isAsset) {
    $ci->db->where_in('products.category_id', [2, 14, 16, 17, 18]); // Only Assets and Sub-Assets.
  } else {
    $ci->db->where_not_in('products.category_id', [2, 14, 16, 17, 18]); // No Assets and Sub-Assets.
  }

  $q = $ci->db->get();

  if ($q && $q->num_rows()) {
    return $q->result();
  }

  return [];
}


/**
 * Get queue date time for customer who commit ticket registration.
 * @param string $dateTime Initial datetime string.
 * @return string return Working date for customer who commit ticket registration.
 */
function getQueueDateTime($dateTime)
{
  $dt = new DateTime($dateTime);
  $hour   = $dt->format('H');
  $day    = $dt->format('D');
  $holiday = FALSE;
  $h = 0;

  if (strcasecmp($day, 'Sun') === 0 || strcasecmp($day, 'Sat') === 0) {
    $holiday = TRUE;
  }

  if ($hour >= 23 || $hour < 7) {
    $h = ($holiday ? 9 : 7);
  }

  // if ($hour >= 23 && $minute <= 59) { // Off time.
  //   $h = (24 - $hour + 8);
  // } elseif ($hour >= 0 && $hour < 7 && $minute <= 59) { // Next day.
  //   $h = (7 - $hour);
  // } else {
  //   $h = 0;
  // }

  if ($h) $dt->add(new DateInterval("PT{$h}H")); // Period Time $h Hour

  return $dt->format('Y-m-d H:i:s');
}

/**
 * Get safety stock.
 * @param int $daily_qty Daily quantity.
 * @param int $required_days Required days.
 * @param float $ratio Safety stock ratio.
 */
function getSafetyStock($daily_qty, $required_days, $ratio)
{
  return ceil(floatval($daily_qty) * intval($required_days) * floatval($ratio)); // Round Up.
}

/**
 * Get Sale Item Sub-Total with minimal price.
 * @param float $price Item Price.
 * @param float $quantity Item Quantity.
 */
function getSaleItemSubTotal($price, $quantity)
{
  // $isReachThreshold = (($price * $quantity) >= $price ? TRUE : FALSE);
  // return roundDecimal($quantity < 0.5 && !$isReachThreshold ? $price : $price * $quantity);
  return round($price * $quantity);
}

/**
 * Get sold items report based by completed sale.
 * @param array $clause [ biller_id, warehouse_id, start_date, end_date ]
 */
function getSoldItems($clause = [])
{
  $stocks = Stock::get($clause);
  $items = [];

  foreach ($stocks as $stock) {
    if (!$stock->sale_id) continue;
  }

  return $items;
}

/**
 * Get SQL Select string.
 * @example 1 getSQLSelects("sales.id AS id, number, CONCAT(f_name, ' ', l_name)"); // Return ['sales.id', 'number', "CONCAT(f_name, ' ', l_name)"]
 */
function getSQLSelects($select)
{
  $len = strlen($select);
  $brackets = 0;
  $res = [];
  $word = '';

  if ($len > 0) {
    for ($a = 0; $a < $len; $a++) {
      $char = substr($select, $a, 1);

      if ($char == ',' && !$brackets) {
        $res[] = trim(preg_split('/ as /i', $word)[0]);
        $word = '';
      } else {
        if ($char == '(') {
          $brackets++;
        }

        if ($char == ')') {
          $brackets--;
        }

        $word .= $char;
      }
    }

    $res[] = trim(preg_split('/ as /i', $word)[0]);
  }

  return $res;
}

function getTeamSupports()
{
  return DB::table('users')->select('users.*, groups.name as group_name, groups.description as group_desc')
    ->join('groups', 'groups.id = users.group_id', 'left')
    ->like('groups.name', 'support', 'none')
    ->where('users.active', 1)
    ->get();
}

function getTotalAmountIncomeStatement($data)
{
  if (is_array($data)) {
    $amount = 0;

    foreach ($data as $d) {
      $amount += $d['amount'];
    }

    return $amount;
  }
  return 0;
}

/**
 * Get Warehouse stock value.
 * @param int $warehouseId Warehouse ID.
 * @param array $opt [ start_date, end_date ]
 */
function getWarehouseStockValue(int $warehouseId, array $opt = [])
{
  $currentDate  = new DateTime();
  $startDate    = new DateTime($opt['start_date'] ?? date('Y-m-') . '01');
  $endDate      = new DateTime($opt['end_date'] ?? date('Y-m-t'));
  $warehouse    = Warehouse::getRow(['id' => $warehouseId]);

  if (!$warehouse) {
    setLastError("getWarehouseStockValue(): Cannot find warehouse [id:{$warehouseId}]");
    return NULL;
  }

  // If end date is more than current date then 0.
  if ($currentDate->diff($endDate)->format('%R') == '+') {
    return 0;
  }

  if ($warehouse->code == 'LUC') { // Lucretai mode.
    $value = DB::table('products')->selectSum('products.cost * (COALESCE(recv.total, 0) - COALESCE(sent.total, 0))', 'total')
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks
        WHERE status LIKE 'received' AND warehouse_id = {$warehouse->id}
        AND date BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'
        GROUP BY product_id) recv", 'recv.product_id = products.id', 'left')
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks
      WHERE status LIKE 'sent' AND warehouse_id = {$warehouse->id}
      AND date BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'
      GROUP BY product_id) sent", 'sent.product_id = products.id', 'left')
      ->whereIn('products.type', ['standard']) // Standard only
      ->whereNotIn('products.category_id', [2, 14, 16, 17, 18]) // Not Assets and Sub-Assets.
      ->getRow();

    return floatval($value->total);
  } else {
    $value = DB::table('products')->selectSum('products.markon_price * (COALESCE(recv.total, 0) - COALESCE(sent.total, 0))', 'total')
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks
        WHERE status LIKE 'received' AND warehouse_id = {$warehouse->id}
        AND date BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'
        GROUP BY product_id) recv", 'recv.product_id = products.id', 'left')
      ->join("(SELECT product_id, SUM(quantity) AS total FROM stocks
      WHERE status LIKE 'sent' AND warehouse_id = {$warehouse->id}
      AND date BETWEEN '{$startDate->format('Y-m-d')} 00:00:00' AND '{$endDate->format('Y-m-d')} 23:59:59'
      GROUP BY product_id) sent", 'sent.product_id = products.id', 'left')
      ->whereIn('products.type', ['standard']) // Standard only
      ->whereNotIn('products.category_id', [2, 14, 16, 17, 18]) // Not Assets and Sub-Assets.
      ->getRow();

    return floatval($value->total);
  }
}

/**
 * Get working date and time for employee and customer. (PROGRESS)
 * @param int $billerid Biller ID
 * @param string $dateTime Date Time
 */
function getWorkingDateTime2($billerId, $dateTime)
{
  $ci = &get_instance();
  $holidays = $ci->site->getHolidays(['biller_id' => $billerId]);
}

/**
 * Get working date time for customer who take an order.
 * @param string $dateTime Initial datetime string.
 * @return string return Working date for customer who take an order.
 */
function getWorkingDateTime($dateTime)
{
  $dt = new DateTime($dateTime);
  $hour   = $dt->format('H');
  $minute = $dt->format('i');

  if ($hour >= 17 && $hour <= 23 && $minute <= 59) { // After office hour.
    $h = (24 - $hour + 9); // Return must hour 9.
  } elseif ($hour >= 0 && $hour < 9 && $minute <= 59) {
    $h = (9 - $hour);
  } else {
    $h = 0;
  }

  if ($h) $dt->add(new DateInterval("PT{$h}H")); // Period Time $h Hour

  return $dt->format('Y-m-d H:i:s');
}

/**
 * Get uploaded files.
 * @param string $file File name defined on form.
 */
function getUploads($file) // PROGRESS
{
  $FileUpload = (object)[];

  if (isset($_FILES[$file]) && $_FILES[$file]['size'] > 0) {
  }

  return [];
}

/**
 * Get filter from encoded base 64 string.
 * @param string $filter64 Encoded uri base 64 string.
 */
function getUriFilter64($filter64)
{
  $result = [];

  if ($filter64) {
    $filter = base64_decode($filter64);
    $filters = explode('&', $filter); // warehouses[]=1&warehouses[]=2

    foreach ($filters as $f) {
      $fil = explode('=', $f); // warehouses[]=1

      if (strpos($fil[0], '[]') !== FALSE) { // As Array
        $name = str_replace('[]', '', $fil[0]); // Remove '[]'
        $result[$name][] = $fil[1];
      } else {
        $result[$fil[0]] = $fil[1];
      }
    }
  }
  return $result;
}

function getURLRating($warehouseId)
{
  $ci = &get_instance();

  $url = '';
  $warehouse = $ci->site->getWarehouseByID($warehouseId);

  if (!$warehouse) {
    setLastError('Warehouse id is invalid.');
    return NULL;
  }

  switch ($warehouse->code) {
    case 'DUR':
      $url = 'https://g.page/IDPDurian?#lkt=LocalPoiReviews';
      break;
    case 'FAT':
      $url = 'https://g.page/IDPFatmawati?#lkt=LocalPoiReviews';
      break;
    case 'GAJ':
      $url = 'https://g.page/IDPGajah?#lkt=LocalPoiReviews';
      break;
    case 'NGE':
      $url = 'https://g.page/IDPNgesrep?#lkt=LocalPoiReviews';
      break;
    case 'PLE':
      $url = 'https://goo.gl/maps/SmLcXPtY5vwST2PWA?#lkt=LocalPoiReviews';
      break;
    case 'TEM':
      $url = 'https://g.page/IDPTembalang?#lkt=LocalPoiReviews';
      break;
    case 'TLO':
      $url = 'https://g.page/IDPTlogosari?#lkt=LocalPoiReviews';
      break;
    case 'UNG':
      $url = 'https://g.page/IDPUngaran?#lkt=LocalPoiReviews';
      break;
    case 'WEL':
      $url = 'https://goo.gl/maps/2iYnSRD1HQPx24JKA?#lkt=LocalPoiReviews';
      break;
  }

  return $url;
}

function getUser($clause = [])
{
  $ci = &get_instance();

  if ($rows = $ci->site->getUsers($clause)) {
    return $rows[0];
  }

  return NULL;
}

/**
 * Get user creator in current session. If no session, system user is selected.
 * @param int $user_id User ID (optional).
 * @return int Return User ID.
 */
function getUserCreator($user_id = NULL)
{
  $ci = &get_instance();

  return ($user_id ?? XSession::get('user_id') ?? $ci->site->getUserByUsername('system')->id);
}

function getWarehouse($clause = [])
{
  $ci = &get_instance();

  if ($rows = $ci->site->getWarehouses($clause)) {
    return $rows[0];
  }

  return NULL;
}

/**
 * Convert html string into readable note.
 */
function html2Note($html)
{
  $str = str_replace('<br>', "\r\n", $html);
  return htmlRemove($str);
}

/**
 * Decode HTML string.
 * @param string $html HTML string to decode.
 * @return string Return decoded HTML string.
 * @example 1 htmlDecode('&lt;b&gt;OK&lt;/b&gt;'); // Return '<b>OK</b>'.
 */
function htmlDecode($html)
{
  return html_entity_decode(trim($html), ENT_HTML5 | ENT_QUOTES | ENT_XHTML, 'UTF-8');
}

/**
 * Encode HTML string.
 * @param string $html HTML string to encode.
 * @return string Return encoded HTML string.
 * @example 1 htmlEncode('<b>OK</b>'); // Return '&lt;b&gt;OK&lt;/b&gt;'.
 */
function htmlEncode($html)
{
  $allowed = '<a><span><div><a><br><p><b><i><u><img><blockquote><small><ul><ol><li><hr><pre>
  <code><strong><em><table><tr><td><th><tbody><thead><tfoot><h3><h4><h5><h6>';
  $stripped = strip_tags($html, $allowed);
  return htmlentities(trim($stripped), ENT_HTML5 | ENT_QUOTES | ENT_XHTML, 'UTF-8');
}

/**
 * Remove HTML tag.
 * @param string $html HTML string to remove.
 * @example 1 htmlRemove('<b>OK</b>'); // Return 'OK'.
 */
function htmlRemove($html)
{
  $decoded = html_entity_decode(trim($html), ENT_HTML5 | ENT_QUOTES | ENT_XHTML, 'UTF-8');
  return preg_replace('/\<(.*?)\>/', '', $decoded);
}

/**
 * Test to see if a request was made from the command line.
 */
function isCLI()
{
  return (PHP_SAPI === 'cli' or defined('STDIN'));
}

/**
 * Check if status completed. Currently 'completed', 'completed_partial' or 'delivered' as completed.
 * @param string $status Status to check.
 */
function isCompleted($status)
{
  return ($status == 'completed' || $status == 'completed_partial' ||
    $status == 'delivered' || $status == 'finished' ? TRUE : FALSE);
}

/**
 * Check if due date has happened.
 * @param string $due_date Due date
 * @example 1 isDueDate('2020-01-20 20:40:11'); // Return FALSE if current time less then due date.
 */
function isDueDate($due_date)
{
  return (strtotime($due_date) > time() ? FALSE : TRUE);
}

/**
 * Check if number has floated point.
 * @param string $num Number to check.
 */
function isNumberFloated($num)
{
  return (strpos(floatval($num), '.') !== FALSE ? TRUE : FALSE);
}

/**
 * Determine OS Host is Linux or not.
 */
function isOSLinux()
{
  return (strcasecmp(PHP_OS, 'linux') === 0 ? TRUE : FALSE);
}

/**
 * Determine OS Host is Windows or not.
 */
function isOSWindows()
{
  return (strcasecmp(PHP_OS, 'winnt') === 0 ? TRUE : FALSE);
}

function isOverTime($time)
{
  if (!$time) return FALSE;

  $t = explode(':', $time);
  if (count($t) == 3 && intval($t[0]) == 0 && intval($t[1]) == 0 && intval($t[2]) == 0) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Check assigned product warehouse by warehouse name.
 * @param string $product_warehouse Assigned product warehouse name.
 * Ex. "Durian, Tembalang" or "-Tlogosari, -Ngesrep".
 * @param string $warehouse_name Warehouse name to check assign.
 * Ex. "Durian", "Ngesrep", ...
 */
function isProductWarehouses($product_warehouse, $warehouse_name)
{
  if (!empty($product_warehouse)) {
    $negated = FALSE;
    $pwhs = explode(',', trim($product_warehouse));

    if (substr($pwhs[0], 0, 1) == '-') $negated = TRUE;

    foreach ($pwhs as $pwh) {
      $pwh = trim($pwh);

      if ($negated) {
        if (strcasecmp(substr($pwh, 1), $warehouse_name) === 0) {
          return FALSE;
        }
      } else {
        if (strcasecmp($pwh, $warehouse_name) === 0) {
          return TRUE;
        }
      }
    }

    if (!$negated) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Determine special customer (Privilege or TOP) by customer id.
 * @param int $customerId Customer ID.
 */
function isSpecialCustomer($customerId)
{
  $ci = &get_instance();
  $custGroup = $ci->site->getCustomerGroupByCustomerID($customerId);

  if ($custGroup) {
    return (strtolower($custGroup->name) == 'privilege' || strtolower($custGroup->name) == 'top' ? TRUE : FALSE);
  }
  return FALSE;
}

function isTBSale(int $billerId, int $warehouseId)
{
  return (strcasecmp(Biller::getRow(['id' => $billerId])->name, Warehouse::getRow(['id' => $warehouseId])->name) != 0);
}

function isTodayHoliday($checkDate = NULL)
{
  $ci = &get_instance();

  $holidays = $ci->holidays;
  $current  = ($checkDate ? strtotime($checkDate) : time());

  foreach ($holidays as $day) {
    if (gettype($day) == 'array') {
      $from = strtotime($day[0] . ' 00:00:00');
      $to = strtotime($day[1] . ' 23:59:59');

      if ($current >= $from && $current <= $to) return TRUE;
    } else if (date('Y-m-d', $current) == date('Y-m-d', strtotime($day))) {
      return TRUE;
    }
  }

  return FALSE;
}

function isW2PUser($user_id)
{
  $ci = &get_instance();
  $user = $ci->site->getUserByID($user_id);

  if ($user) {
    return ($user->username == 'w2p' ? TRUE : FALSE);
  }
  return FALSE;
}

function isWeb2Print($sale_id)
{
  $sale = Sale::getRow(['id' => $sale_id]);

  if ($sale) {
    $saleJS = getJSON($sale->json_data);

    return (($saleJS->source ?? NULL) == 'W2P' ? TRUE : FALSE);
  }
  return FALSE;
}

/**
 * Debug logging
 * @param string $type debug, error, info, ...
 * @param mixed $msg Message to log.
 */
function dbglog($type, $msg = '')
{
  $filename = FCPATH . 'logs/log-' . date('Y-m-d') . ".log";
  $hFile  = fopen($filename, 'a'); // Appending and write
  if (!$hFile) return FALSE;
  $dt     = date('Y-m-d H:i:s');
  $tx     = print_r($msg, TRUE);
  $ty     = strtoupper($type);
  $data   = "{$dt} [{$ty}]: {$tx}\r\n";
  fwrite($hFile, $data);
  setLastError($data);
  return fclose($hFile);
}

function loginPage()
{
  $ci = &get_instance();

  $ci->load->library('form_validation');

  $ci->data['error']   = (validation_errors() ? validation_errors() : '');
  // $ci->data['error']   = (validation_errors()) ? validation_errors() : $ci->session->flashdata('error');
  $ci->data['message'] = $ci->session->flashdata('message');

  $ci->data['identity'] = [
    'name' => 'identity',
    'id'                          => 'identity',
    'type'                        => 'text',
    'class'                       => 'form-control',
    'placeholder'                 => lang('email'),
    'value'                       => $ci->form_validation->set_value('identity'),
  ];

  $ci->data['password'] = [
    'name' => 'password',
    'id'                          => 'password',
    'type'                        => 'password',
    'class'                       => 'form-control',
    'required'                    => 'required',
    'placeholder'                 => lang('password'),
  ];

  $ci->data['allow_reg'] = $ci->Settings->allow_reg;

  $ci->data['title'] = 'Login';
  $ci->data['url'] = $ci->input->get('url');
  $ci->data['warehouses'] = $ci->site->getAllWarehouses();
  echo $ci->load->view($ci->theme . 'auth/login', $ci->data, TRUE);

  die();
}

/**
 * Create mutual exclusion.
 * @param string $name Mutex name.
 * @param bool $wait Waiting for mutex to finish.
 */
function mutexCreate($name = NULL, $wait = FALSE)
{
  $name = ($name ?? 'default');

  $hMutex = fopen(FCPATH . 'mutex/' . $name, 'w');

  $param = LOCK_EX; // Lock exclusive

  if (!$wait) $param |= LOCK_NB;

  if ($hMutex && flock($hMutex, $param)) {
    return $hMutex;
  }
  return FALSE;
}

/**
 * Release mutual exclusion.
 * @param resource $hMutex Handle from mutexCreate.
 */
function mutexRelease($hMutex)
{
  if ($hMutex) {
    $meta_data = stream_get_meta_data($hMutex); // Get absolute file name from resource/stream.
    $filename = $meta_data['uri'];

    flock($hMutex, LOCK_UN);
    fclose($hMutex);

    if (file_exists($filename)) {
      @unlink($filename);
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Optical Character Recognition. Get readable text from image.
 * @param string $image Image to read as text.
 * @return array|false Return array of string data or false if error.
 */
function ocr($image)
{
  setLastError();

  $exe = "tesseract";
  $output = [];
  $retval = 0;

  exec("$exe --version", $output, $retval);

  if ($retval != 0) {
    setLastError("Tesseract is not found.");
    return FALSE;
  }

  if (is_file($image)) {
    $output = [];
    exec("$exe $image stdout", $output);
  } else {
    setLastError('Image file is not found.');
    return FALSE;
  }

  return $output;
}

/**
 * Add 62 to phone number.
 * @param string $phone Phone number.
 */
function phoneCode($phone)
{
  if (substr($phone, 0, 2) == '08') {
    return '62' . substr($phone, 1);
  }
  if (substr($phone, 0, 3) == '+62') {
    return substr($phone, 1);
  }
  return $phone;
}

/** Get current PHP executable binary.
 *
 */
function phpBinary()
{
  if (defined('PHP_BINARY')) {
    $phppath = dirname(PHP_BINARY);
    $phpbin = str_replace('sbin', 'bin', $phppath) . DIRECTORY_SEPARATOR . 'php';
    return $phpbin;
  }
  return NULL;
}

/**
 * Prepend zero for number.
 * @param int $num Number to prepend with zero.
 */
function prependZero($num)
{
  return ($num < 10 ? '0' . $num : $num);
}

/**
 * Convert QMS integer status to readable QMS status.
 * @param int $status QMS Status.
 */
function qmsStatus($status)
{
  switch ($status) {
    case '1':
      return 'waiting';
    case '2':
      return 'calling';
    case '3':
      return 'called';
    case '4':
      return 'serving';
    case '5':
      return 'served';
    case '6':
      return 'skipped';
  }
  return NULL;
}

function rating2star(int $rating)
{
  $data = '<div style="color:#FFD700">';

  if (empty($rating)) $rating = 1;

  for ($x = 0; $x < $rating; $x++) {
    $data .= '<i class="fas fa-star"></i>';
  }

  $data .= '</div>';

  return $data;
}

function renderStatus(string $status, $elm = 'td')
{
  if (empty($status)) return "<{$elm}></{$elm}>";

  $label = 'default';
  $st = strtolower($status);

  $danger = [
    'bad', 'decrease', 'due', 'due_partial', 'expired', 'need_approval', 'need_payment', 'off', 'over_due',
    'over_received', 'returned'
  ];
  $info = [
    'completed_partial', 'confirmed', 'delivered', 'excellent', 'finished', 'installed_partial', 'ordered',
    'partial', 'preparing', 'received', 'received_partial'
  ];
  // Since doesn't have alert-primary, we are using alert-info instead. See common.js:renderStatus().
  // $primary = ['delivered', 'excellent', 'received'];
  $success = ['approved', 'completed', 'increase', 'good', 'installed', 'paid', 'sent', 'verified'];
  $warning = [
    'cancelled', 'checked', 'draft', 'packing', 'pending', 'slow', 'trouble',
    'waiting_production', 'waiting_transfer'
  ];

  if (array_search($st, $danger) !== FALSE) {
    $label = 'danger';
  } elseif (array_search($st, $info) !== FALSE) {
    $label = 'info';
  } elseif (array_search($st, $success) !== FALSE) {
    $label = 'success';
  } elseif (array_search($st, $warning) !== FALSE) {
    $label = 'warning';
  }

  $name = ucwords(preg_replace('/([\-\_])/', ' ', $st));

  return "<{$elm} class=\"alert-{$label}\"><strong>{$name}</strong></{$elm}>";
}

/**
 * Round decimal floating point with filtering.
 * @param mixed $num Number to round.
 * @example 1 roundDecimal('2,34,30.20'); // Return 23430
 * @example 2 roundDecimal('25.5'); // Return 26
 */
function roundDecimal($num)
{
  return round(filterDecimal($num));
}

/**
 * Send WA Message.
 * @param string $phone Phone number.
 * @param string $text Message to send.
 * @param array $opt Options [ api, engine:[ rapiwha | whacenter ] ]
 */
function sendWA($phone, $text, $opt = [])
{
  $ph = phoneCode($phone);
  $curl = curl_init();
  $query = ['number' => $ph];
  $defaultEngine = 'whacenter';

  $engine = (!empty($opt['engine']) ? $opt['engine'] : $defaultEngine);

  if ($engine == 'rapiwha') {
    $url = 'https://panel.rapiwha.com/send_message.php';
    $query['apikey'] = (!empty($opt['api']) ? $opt['api'] : '55L5E5BJQ2FPNK2LNEQQ');
    $query['text'] = $text;
  } else if ($engine == 'whacenter') {
    $url = 'https://app.whacenter.com/api/send';
    $query['device_id'] = (!empty($opt['api']) ? $opt['api'] : '673ea656d0817067be340f5f5a29eb18');
    $query['message'] = $text;
  }

  curl_setopt_array($curl, [
    CURLOPT_URL             => $url,
    CURLOPT_HEADER          => FALSE,
    CURLOPT_POST            => TRUE,
    CURLOPT_POSTFIELDS      => http_build_query($query),
    CURLOPT_RETURNTRANSFER  => TRUE,
  ]);

  $res = curl_exec($curl);

  if (!$res) {
    setLastError(curl_error($curl));
  }
  curl_close($curl);

  return $res;
}

/**
 * Set created by data.
 *
 * @param array $data [ created_at, created_by, date ]
 */
function setCreatedBy($data)
{
  $data['created_at'] = ($data['date'] ?? $data['created_at'] ?? date('Y-m-d H:i:s'));
  $system = User::getRow(['username' => 'system']);

  $data['created_by'] = ($data['created_by'] ?? XSession::get('user_id') ?? $system->id);

  return $data;
}

/**
 * Set last error message.
 *
 * @param string $msg Error message. Reset last error message if omitted.
 */
function setLastError(string $msg = NULL)
{
  if ($msg && strlen($msg)) {
    log_message('error', $msg);
    XSession::set('lastErrorMsg', $msg);
  } else {
    XSession::delete('lastErrorMsg');
  }

  if (XSession::has('lastErrorMsg')) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Set created by data.
 *
 * @param array $data [ updated_at ]
 */
// function setUpdatedAt($data)
// {
//   if (!empty($data['updated_at'])) {
//     try {
//       $date = new DateTime($data['updated_at']);

//       $data['updated_at'] = $date->format('Y-m-d H:i:s');

//       unset($date);
//     } catch (\Exception $err) {
//       setLastError($err->getMessage());
//     }
//   } else {
//     $data['updated_at'] = date('Y-m-d H:i:s');
//   }

//   return $data;
// }

/**
 * Set updated by data.
 *
 * @param array $data [ updated_by ]
 */
function setUpdatedBy($data)
{
  $ci = &get_instance();

  $data['updated_at'] = ($data['updated_at'] ?? date('Y-m-d H:i:s'));

  if (!empty($data['updated_by'])) {
    if ($updater = $ci->site->getUserByID($data['updated_by'])) {
      $data['updated_by'] = $updater->id;
    }
  } else {
    if ($updaterId = $ci->session->userdata('user_id')) {
      $data['updated_by'] = $updaterId;
    } else {
      $user = $ci->site->getUserByUsername('system');
      $data['updated_by'] = $user->id;
    }
  }

  return $data;
}

/**
 * Convert Reference to Serial Number.
 * @param string $code Product Code.
 * @param string $reference PO Reference.
 */
function toSN(string $code, string $reference)
{
  if (empty($code) || strlen($code) < 4) return NULL;

  if (strpos($reference, '-')) {
    $poDate = explode('-', $reference)[1];

    return substr($code, 0, 4) . str_replace('/', '', $poDate);
  }

  return NULL;
}

/**
 * Convert Warehouse ID to Biller ID See billerToWarehouse.
 * @param int|array $warehouseId Warehouse ID. It can be warehouse id or array of warehouse id.
 * @return int Return Biller ID.
 */
function warehouseToBiller($warehouseId)
{
  if (gettype($warehouseId) == 'array') {
    $data = [];

    foreach ($warehouseId as $warehouse_id) {
      $warehouse = Warehouse::getRow(['id' => $warehouse_id]);

      if ($warehouse) {
        $biller = Biller::getRow(['code' => $warehouse->code]);

        if ($biller) {
          $data[] = $biller->id;
        }
      }
    }

    return $data;
  } else {
    $warehouse = Warehouse::getRow(['id' => $warehouseId]);

    if ($warehouse) {
      $biller = Biller::getRow(['code' => $warehouse->code]);

      if ($biller) {
        return $biller->id;
      }
    }
    return NULL;
  }
}

/**
 * Convert time string to excel format.
 */
function XTime($time)
{
  if (!$time) return "=TIME(0,0,0)";

  $t = explode(':', $time);
  if (count($t) != 3) return "=TIME(0,0,0)";

  return "=TIME({$t[0]},{$t[1]},{$t[2]})";
}

/**
 * Generic File Loader Helper
 *
 * @param	string $path File path
 * @param	bool $return Whether to return the file output
 * @return object|string
 */
function view(string $view, $vars = [], $return = FALSE)
{
  $ci = &get_instance();
  return $ci->load->view($view, $vars, $return);
}

// **************************************************************

if (!function_exists('rd_debug')) {
  function rd_debug()
  {
    $filename = APPPATH . 'logs/dev-' . date('Y-m-d') . '.php';
    $hFile = fopen($filename, 'a'); // Appending and write
    $args = func_get_args();
    $type = array_shift($args);
    $type = (gettype($type) == 'string' ? strtoupper($type) : print_r($type, TRUE));

    $data = date('Y-m-d H:i:s') . " [{$type}]:";

    foreach ($args as $arg) {
      $str = print_r($arg, TRUE);
      fwrite($hFile, $data . $str . "\r\n");
    }
    fclose($hFile);
  }
}

if (!function_exists('rd_error')) {
  function rd_error($reason, $return = FALSE)
  {
    $ex = new \Exception($reason);
    rd_debug('error', $ex->getMessage(), $ex->getTrace());
    return $return;
  }
}

if (!function_exists('rd_trim')) {
  function rd_trim($str, $default_value = '')
  {
    if (isset($str) && gettype($str) === 'string') {
      return trim($str);
    } else {
      return $default_value;
    }
  }
}

if (!function_exists('rd_unit')) {
  /**
   * Convert common unit code into unit code.
   * @param string unit Common unit code.
   * @example 1 rd_unit('m2'); // Return m².
   */
  function rd_unit($unit)
  {
    if (strlen($unit) > 0) {
      $suffix = (object)[
        'square' => '²',
        'cubic'  => '³'
      ];

      $sfx = substr($unit, -1);
      $un  = substr($unit, 0, strlen($unit) - 1);
      if ($sfx == '2') {
        return $un . $suffix->square;
      }
      if ($sfx == '3') {
        return $un . $suffix->cubic;
      }
    }
    return $unit;
  }
}

/**
 * Send JSON response.
 * @param mixed $data Data to send.
 */
function sendJSON($data)
{
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  die(json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * Send Email.
 * @param array $data [ *to, *from, from_name, *subject, *body ]
 */
function sendMail($data = [])
{
  $mail = new PHPMailer();

  try {
    $mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
    $mail->isSMTP();
    $mail->Host = 'smtppro.zoho.com';
    $mail->SMTPAuth = TRUE;
    $mail->Username = 'buyer@indostoreku.com';
    $mail->Password = 'Dur14n100$';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 30;

    if (empty($data['from_name'])) $data['from_name'] = 'Indoprinting';

    $mail->setFrom($data['from'], $data['from_name']);
    $mail->addAddress($data['to']);

    $mail->isHTML(TRUE);
    $mail->Subject = $data['subject'];
    $mail->Body = $data['body'];
    if ($mail->send()) {
      return TRUE;
    }
    // setLastError($mail->ErrorInfo);
    return FALSE;
  } catch (Exception $e) {
    echo "Error could not be sent. Mailer Error: {$mail->ErrorInfo}";
  }
}

if (!function_exists('unitToBaseQty')) {
  function unitToBaseQty($qty, $unit)
  {
    switch ($unit->operator) {
      case '*': {
          return (filterDecimal($qty) * $unit->operation_value);
        }
      case '/': {
          return (filterDecimal($qty) / $unit->operation_value);
        }
    }
  }
}
/* EOF */