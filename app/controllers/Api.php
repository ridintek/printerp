<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* Change this version as you need. */
const API_VERSION = 'v1';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;

class Api extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('cekmutasi');
    $this->load->model('mutasibank');

    $this->requestMethod = $_SERVER['REQUEST_METHOD'];
  }

  protected function authKey()
  {
    $api = NULL;
    $headers = getallheaders();
    foreach ($headers as $name => $value) {
      if (strcasecmp($name, 'Authorization') === 0) {
        $api = $this->site->getApiKeyByToken($value);
        return $api;
      }
    }

    sendJSON(['error' => 1, 'msg' => 'Invalid Api Key']);
  }

  public function clear_cookies_v1()
  {
    Authentication::logout();

    set_cookie(['name' => 'identity', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'remember_code', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'sess', 'value' => '', 'expire' => 1]);
    set_cookie(['name' => 'erp_token_cookie', 'value' => '', 'expire' => 1]);

    $this->response(200, ['message' => 'Cookies have been cleared.']);
  }

  private function http_get($url, $header = [])
  {
    if (!function_exists('curl_init')) {
      throw new Exception('CURL is not installed.');
      die();
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    if (!empty($header)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($res = curl_exec($ch)) {
      return $res;
    } else {
      return curl_error($ch);
    }
  }

  private function http_post($url, $param = NULL, $header = [])
  {
    if (!function_exists('curl_init')) {
      throw new Exception('CURL is not installed.');
      die();
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    if (!empty($header)) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($ch, CURLOPT_POST, TRUE);
    if (!empty($param)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $url);
    return curl_exec($ch);
  }

  private function language_v1()
  {
    $lang = $_GET['lang'];
    $response = [
      'error' => 1,
      'message' => 'No language specified.'
    ];

    if ($lang) {
      $response['data'] = lang($lang);
      sendJSON($response);
    }

    sendJSON($response);
  }

  private function barcode_v1()
  {
    $text = strtoupper($_GET['text']) ?? NULL;
    if ($text) {
      $this->ridintek->barcode($text);
    }
    echo ('No Barcode');
  }

  private function cekmutasi_v1($mode = NULL)
  {
    if ($mode == 'accounts') {
      $this->cekmutasi_accounts();
    }

    $cm_response = json_decode(file_get_contents('php://input'));

    if (empty($cm_response)) {
      $this->redirect();
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->redirect();
    }

    if ($cm_response->action !== 'payment_report') {
      $this->redirect();
    }

    header('Content-Type: text/html');

    if ($this->cekmutasi->validate($cm_response)) { // Segala pengecekan dan validasi data di sini.
      http_response_code(200); // OK
      echo ('VALIDATED');
    } else {
      http_response_code(406); // Not Acceptable
      echo ('NOT VALIDATED');
    }
  }

  private function cekmutasi_accounts()
  {
    $data = [];
    $account1 = $this->http_get('https://mutasibank.co.id/api/v1/accounts', [
      'Authorization: WlA5V1dONHk3VVdFalFLcmNzaldJMGluWEh3NkY3SDMwaDd3dmxINUQ1bUplSVhnNk5ZYmZCNDB6Mllp5dba5f95d2748'
    ]);

    $account2 = $this->http_get('https://mutasibank.co.id/api/v1/accounts', [
      'Authorization: Z3FJMXRrM2k4am1CMVl6dDZMUWp3N1E2VUdpc21kcmRKaWdPZ1l6dldGaE5KWjExcG5hYzRCQXBBR2NY606d88e273aa6'
    ]);

    $acc1Res = json_decode($account1);

    if (!empty($acc1Res) && !$acc1Res->error) {
      foreach ($acc1Res->data as $row) {
        $data[] = [
          'id'                => $row->id,
          'account_name'      => $row->account_name,
          'account_no'        => $row->account_no,
          'balance'           => $row->balance,
          'bank'              => $row->bank,
          'module'            => $row->module,
          'is_active'         => $row->is_active,
          'last_bot_activity' => $row->last_bot_activity,
          'next_bot_process'  => $row->next_bot_process
        ];
      }
    }

    $acc2Res = json_decode($account2);

    if (!empty($acc2Res) && !$acc2Res->error) {
      foreach ($acc2Res->data as $row) {
        $data[] = [
          'id'                => $row->id,
          'account_name'      => $row->account_name,
          'account_no'        => $row->account_no,
          'balance'           => $row->balance,
          'bank'              => $row->bank,
          'module'            => $row->module,
          'is_active'         => $row->is_active,
          'last_bot_activity' => $row->last_bot_activity,
          'next_bot_process'  => $row->next_bot_process
        ];
      }
    }

    sendJSON($data);
  }

  private function customers_v1($mode = NULL)
  {
    if ($mode == 'edit') {
      if ($this->requestMethod == 'POST') {
        return $this->customers_edit();
      } else {
        http_response_code(403);
        $this->response(404, ['message' => 'Not Found']);
      }
    }

    if ($this->requestMethod == 'GET') {
      $phone = getGET('phone');

      if ($phone) {
        $customer = $this->site->getCustomerByPhone($phone);

        if (!$customer) {
          sendJSON(['error' => 1, 'msg' => 'Customer is not found.']);
        }

        $customerData = [
          'customer_group_id'   => $customer->customer_group_id,
          'customer_group_name' => $customer->customer_group_name,
          'name'                => $customer->name,
          'company'             => $customer->company,
          'address'             => $customer->address,
          'city'                => $customer->city,
          'state'               => $customer->state,
          'postal_code'         => $customer->postal_code,
          'country'             => $customer->country,
          'phone'               => $customer->phone,
          'email'               => $customer->email,
          'price_group_id'      => $customer->price_group_id,
          'price_group_name'    => $customer->price_group_name
        ];

        sendJSON(['error' => 0, 'customer' => $customerData]);
      } else {
        $customers = $this->site->getCustomers();

        if ($customers) {
          sendJSON(['error' => 0, 'customers' => $customers]);
        } else {
          sendJSON(['error' => 1, 'msg' => 'No customers have found.']);
        }
      }
    } else if ($this->requestMethod == 'POST') {
      $phone = getPOST('phone');
      $name  = getPOST('name');
      $company = getPOST('company');
      $email   = getPOST('email');

      $customer_data = [
        'customer_group_id' => 1,
        'customer_group_name' => 'Reguler',
        'name' => $name,
        'company' => $company,
        'phone' => $phone,
        'email' => $email
      ];

      if ($this->site->addCustomer($customer_data)) {
        sendJSON(['error' => 0, 'message' => 'Customer has been added successfully.']);
      }
      sendJSON(['error' => 1, 'message' => 'Failed to add customer.']);
    }
  }

  private function customers_edit()
  {
    $phone   = getPOST('phone');
    $name    = getPOST('name');
    $company = getPOST('company');
    $email = getPOST('email');

    $customer = $this->site->getCustomerByPhone($phone);

    if (!$customer) sendJSON(['error' => 1, 'msg' => 'Customer not found.']);

    $custData = [];

    if (!empty($name))    $custData['name']    = $name;
    if (!empty($company)) $custData['company'] = $company;
    if (!empty($email))   $custData['email']   = $email;

    if ($this->site->updateCustomer($customer->id, $custData)) {
      sendJSON(['error' => 0, 'msg' => 'Customer has been updated successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to update customer.']);
  }

  private function geolocation_v1()
  {
    $cmd = getPOST('cmd');
    $lat = getPOST('lat');
    $lon = getPOST('lon');

    $user = $this->site->getUserByID(XSession::get('user_id'));
    if ($user) {
      $geo_data = [
        'user_id' => $user->id,
        'lat' => $lat,
        'lon' => $lon
      ];

      $this->site->addGeolocation($geo_data);
      sendJSON(['error' => 0, 'cmd' => $cmd, 'lat' => $lat, 'lon' => $lon, 'url' => "https://google.com/maps?q={$lat},{$lon}"]);
    }

    sendJSON(['error' => 1, 'cmd' => $cmd, 'lat' => $lat, 'lon' => $lon]);
  }

  private function google_v1()
  {
    if ($argv = func_get_args()) {
      $method = 'google_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }
  }

  private function google_review()
  {
    $warehouse = getGET('warehouse');

    if (!$warehouse) $this->response(400, ['messsage' => 'Warehouse is not specified.']);

    echo "Warehouse $warehouse";
  }

  /**
   * Ketika ada transaksi debet/kredit di salah satu akun, server mutasibank.co.id akan
   * mengirimkan data ke method ini melalui link: https://printerp.indoprinting.co.id/api/v1/mutasibank
   *
   * https://github.com/daffigusti/mutasi_api_sample/blob/master/callback.php
   */
  private function mutasibank_v1($mode = NULL) // Callback untuk mutasibank.co.id
  {
    if ($mode == 'accounts') {
      $this->mutasibank_accounts();
      die();
    }

    if ($mode == 'accountStatements') {
      $this->mutasibank_accountStatements();
      die();
    }

    if ($mode == 'manualValidation') {
      $this->mutasibank_manualValidation();
      die();
    }

    $mb_response = getJSON(file_get_contents('php://input'));

    if (empty($mb_response)) {
      $this->redirect();
    }

    header('Content-Type: text/html');

    if ($total = PaymentValidation::validate($mb_response)) { // Segala pengecekan dan validasi data di sini.
      $this->response(200, ['message' => sprintf('Success validate %d payment validations.', $total)]);
    } else {
      $this->response(404, ['message' => 'No validated payment validations']);
    }
  }

  private function mutasibank_accounts()
  {
    $data = [];

    $account = $this->http_get('https://mutasi.indoprinting.co.id/api/accounts_list', [
      'Authorization: Bearer tikXCBSpl2JGVr49ILhme7dHfbaQuOPFYNozMEc6'
    ]);

    $acc = json_decode($account);

    if ($acc && $acc->status == TRUE) {
      foreach ($acc->data as $row) {
        $data[] = [
          'id'                => $row->id,
          'account_name'      => $row->account_name,
          'account_no'        => $row->account_number,
          'balance'           => $row->balance,
          'bank'              => $row->bank_name,
          'module'            => $row->module_name,
          'last_bot_activity' => $row->last_run
        ];
      }
    }

    sendJSON($data);
  }

  private function mutasibank_accountStatements()
  {
  }

  private function mutasibank_manualValidation()
  {
    $amount    = getPOST('amount');
    $accountNo = getPOST('account_no');
    $invoice   = getPOST('invoice');
    $note      = getPOST('note');
    $trDate    = getPOST('transaction_date');

    $sale = $this->site->getSaleByReference($invoice);

    if (!$sale) sendJSON(['error' => 0, 'message' => 'Sale is not valid.']);

    if (empty($trDate)) $this->response(400, ['message' => 'Transaction date is invalid.']);

    $transDate = new DateTime($trDate);

    $data = (object)[
      'account_number' => $accountNo,
      'data_mutasi' => [
        (object)[
          'transaction_date' => ($transDate ? $transDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s')),
          'type'             => 'CR',
          'amount'           => filterDecimal($amount),
          'description'      => $note
        ]
      ]
    ];

    $validationOptions = [
      'manual' => TRUE, /* Optional, but required for manual validation. */
      'sale_id' => $sale->id
    ];

    $uploader = new FileUpload();

    if ($uploader->has('attachment_id')) {
      if ($uploader->getSize('mb') > 2) {
        $this->response(400, ['message' => 'Attachment size is exceed more than 2MB.']);
      }

      $validationOptions['attachment_id'] = $uploader->storeRandom();
    }

    if (PaymentValidation::validate($data, $validationOptions)) {
      sendJSON(['error' => 0, 'msg' => 'Payment has been validated successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to validate payment.']);
  }


  private function paymentValidation_v1()
  {
    if ($this->requestMethod == 'POST') {
      $amount = getPOST('amount');

      if (empty($amount)) $this->response(400, ['message' => 'Amount is not specified.']);

      $date = date('Y-m-d H:i:s');
      $user   = User::getRow(['username' => 'SYSTEM']);
      $biller = Biller::getRow(['code' => 'ONL']); // Online.

      $pv_data = [
        'created_at'    => date('Y-m-d H:i:s'),
        'expired_date'  => date('Y-m-d H:i:s', strtotime("+1 day", strtotime($date))),
        'reference'     => uuid(),
        'amount'        => $amount,
        'biller_id'     => $biller->id,
        'created_by'    => $user->id
      ];

      if ($pvId = PaymentValidation::add($pv_data)) {
        $paymentValidation = PaymentValidation::getRow(['id' => $pvId]);

        $this->response(201, ['message' => 'Payment validation created successfully.',
          'data' => $paymentValidation
        ]);
      }
      
      $this->response(400, ['message' => 'Cannot create payment validation.']);
    } else if ($this->requestMethod == 'GET') {
      $id = getGET('id');

      $paymentValidation = PaymentValidation::getRow(['id' => $id]);

      if ($paymentValidation) {
        $this->response(200, ['data' => $paymentValidation]);
      }

      $this->response(404, ['message' => 'Payment validation is not found.']);
    }
  }

  private function products_v1()
  {
    if ($this->requestMethod == 'GET') {
      $clause   = [];
      $products = [];
      $code           = getGET('code');
      $qty            = getGET('qty') ?? 1;
      $type           = getGET('type');
      $price_group_id = getGET('price_group') ?? 1; // Default Price Group 1 = Zona 1

      if (empty($price_group_id)) $price_group_id = 1; // Make sure price_group_id is not null or empty.

      $clause['type'] = ($type ? $type : ['combo', 'service', 'standard']);

      if ($code) {
        $clause['code'] = $code;
      }

      // $warehouse = $this->site->getWarehouseByCode('DUR'); // Default to Durian. Next Online zone.
      $items     = $this->site->getProducts($clause);

      if ($items && $qty) {
        foreach ($items as $item) {
          $prices = [];
          $category = $this->site->getProductCategoryByID($item->category_id);
          $product_prices = $this->site->getProductPrices($item->id, $price_group_id);

          if ($product_prices) {
            for ($a = 1; $a <= 6; $a++) {
              $prices[] = filterDecimal($product_prices->{'price' . ($a == 1 ? '' : $a)});
            }
          }
          // sendJSON(['product_id' => $item->id, 'warehouse_id' => $warehouse->id]);
          $item->price = getProductPriceByQty(['product_id' => $item->id, 'price_group_id' => $price_group_id, 'quantity' => $qty]);
          $item->product_prices = json_encode($prices); // Group 1/Zone 1.

          if ($category) {
            $item->category_code = $category->code;
          }

          $products[] = $item;
        }
      }

      sendJSON(['error' => 0, 'products' => $products]);
    } else if ($this->requestMethod == 'POST') {
      // Not implemented yet. For add new product.
    }
  }

  private function qms_v1()
  {
    $name          = getPOST('name'); // Mbahmu Waras
    $phone         = getPOST('phone'); // 0823....
    $categoryCode  = getPOST('category'); // siap_cetak (default), edit_design
    $warehouseCode = getPOST('warehouse'); // DUR, TEM

    if ($this->requestMethod == 'POST') {
      if (!$warehouse = $this->site->getWarehouseByCode($warehouseCode)) {
        sendJSON(['error' => 1, 'msg' => 'Warehouse is invalid.']);
      }

      $categoryId = ($categoryCode == 'edit_design' ? 2 : 1);

      $ticket_data = [
        'name'  => $name,
        'phone' => $phone,
        'queue_category_id' => $categoryId,
        'warehouse_id'      => $warehouse->id
      ];

      if ($newTicket = $this->Qms_model->addQueueTicket($ticket_data)) {
        sendJSON(['error' => 0, 'data' => $newTicket]);
      } else {
        sendJSON(['error' => 1, 'msg' => 'Cannot create ticket']);
      }
    }
  }

  private function qrcode_v1()
  {
    echo $this->ridintek->qrcode('https://indoprinting.co.id/trackorder?inv=INV-2020/08/9883&phone=085641258879&submit=1');
  }

  private function redirect()
  {
    admin_redirect('./'); // For security reason. DO NOT ERASE !!! Comment it for debugging purpose.
  }

  private function saleitems_v1()
  {
    if ($this->requestMethod == 'POST') {
      $saleItems = json_decode(file_get_contents('php://input'));
      $success = 0;
      $failed = 0;

      if (!$saleItems || !is_array($saleItems)) sendJSON(['error' => 1, 'msg' => 'Invalid JSON format.']);

      foreach ($saleItems as $saleItem) {
        if (!empty($saleItem->id)) {
          $saleItemData = [];

          if (!empty($saleItem->due_date))    $saleItemData['due_date']    = $saleItem->due_date;
          if (!empty($saleItem->operator_id)) $saleItemData['operator_id'] = $saleItem->operator_id;
          if (!empty($saleItem->spec))        $saleItemData['spec']        = $saleItem->spec;

          if ($this->site->updateSaleItem($saleItem->id, $saleItemData)) {
            $success++;
          } else {
            $failed++;
          }
        }
      }

      sendJSON(['error' => 0, 'msg' => sprintf('Sale items %d updated and %d failed.', $success, $failed)]);
    }
  }

  /**
   * Add transfer mode for sale.
   */
  private function sales_add_transfer()
  {
    $inv   = getPOST('invoice');
    $phone = getPOST('phone');

    $customer = $this->site->getCustomerByPhone($phone);
    $sale     = $this->site->getSaleByReference($inv);

    if (!$customer) sendJSON(['error' => 1, 'msg' => 'Customer not found.']);

    // Validate invoice and customer.
    if ($sale->customer_id != $customer->id) sendJSON(['error' => 1, 'msg' => 'Data is not match.']);

    if ($sale) {
      $expired_date = strtotime("+2 days"); // Expired date always 2 days.

      $pv_data = [
        'created_at'    => date('Y-m-d H:i:s'),
        'expired_date'  => date('Y-m-d H:i:s', $expired_date),
        'reference'     => $sale->reference,
        'sale_id'       => $sale->id,
        'amount'        => $sale->grand_total,
        'created_by'    => $sale->created_by,
        'biller_id'     => $sale->biller_id,
      ];

      if (PaymentValidation::add($pv_data)) {
        $this->site->updateSale($sale->id, ['payment_status' => 'waiting_transfer']);

        sendJSON(['error' => 0, 'msg' => 'Payment Validation has been added.', 'data' => [
          'url' => "https://indoprinting.co.id/trackorder?inv={$sale->reference}&phone={$customer->phone}&submit=1"
        ]]);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to add payment validation.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Sale not found.']);
  }

  private function sales_cancel_transfer()
  {
    $inv   = getPOST('invoice');
    $phone = getPOST('phone');

    $customer = $this->site->getCustomerByPhone($phone);
    $sale     = $this->site->getSaleByReference($inv);

    if (!$customer) sendJSON(['error' => 1, 'msg' => 'Customer not found.']);

    // Validate invoice and customer.
    if ($sale->customer_id != $customer->id) sendJSON(['error' => 1, 'msg' => 'Data is not match.']);

    if ($sale) {
      $pv = $this->site->getPaymentValidationBySaleID($sale->id);

      if (!$pv) sendJSON(['error' => 1, 'msg' => 'Payment validation not found.']);

      if ($this->site->deletePaymentValidation($pv->id)) {
        sendJSON(['error' => 0, 'msg' => 'Sale Payment Validation has been cancelled.']);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to cancel Sale Payment Validation.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Sale not found.']);
  }

  private function sales_delete()
  {
    $inv   = getPOST('invoice');
    $phone = getPOST('phone');
    $sale = $this->site->getSaleByReference($inv);

    if ($sale && $phone) {
      $customer = $this->site->getCustomerByPhone($phone);

      if ($sale->customer_id != $customer->id) sendJSON(['error' => 1, 'msg' => 'Data is not match.']);

      if ($this->site->deleteSale($sale->id)) {
        sendJSON(['error' => 0, 'msg' => "Sale {$inv} has been deleted successfully."]);
      }
      sendJSON(['error' => 1, 'msg' => "Failed to delete sale {$inv}."]);
    }
    sendJSON(['error' => 1, 'msg' => 'Sale not found.']);
  }

  private function sales_edit()
  {
    $approved = getPOST('approved');
    $inv = getPOST('invoice');
    $note = getPOST('note');
    $bl_code = getPOST('biller');
    $wh_code = getPOST('warehouse');
    $PICId = getPOST('pic_id');
    $estCompleteDate = getPOST('est_complete_date');

    $saleData = [];
    $warehouse = NULL;

    if (!empty($note)) $saleData['note'] = $note;
    if (!empty($estCompleteDate)) $saleData['est_complete_date'] = $estCompleteDate;

    if ($approved == 0 || $approved == 1) $saleData['approved'] = $approved;

    if ($wh_code) {
      $warehouse = $this->site->getWarehouse(['code' => $wh_code]);

      if ($warehouse) {
        $saleData['warehouse_id'] = $warehouse->id;
      } else {
        sendJSON(['error' => 1, 'msg' => 'Warehouse is not found.']);
      }
    }

    if ($bl_code) {
      $biller = $this->site->getBiller(['code' => $bl_code]);

      if ($biller) {
        $saleData['biller_id'] = $biller->id;
      } else {
        sendJSON(['error' => 1, 'msg' => 'Biller is not found.']);
      }
    }

    if (!empty($PICId)) $saleData['created_by'] = $PICId;

    if ($sale = $this->site->getSaleByReference($inv)) {
      if ($this->site->updateSale($sale->id, $saleData)) {
        $this->site->syncSales(['sale_id' => $sale->id]);
        sendJSON(['error' => 0, 'msg' => "Sale {$inv} has been updated successfully."]);
      }
      sendJSON(['error' => 1, 'msg' => "Failed to update sale {$inv}"]);
    }
    sendJSON(['error' => 1, 'msg' => 'Sale not found.']);
  }

  private function sales_status()
  {
    $inv    = getPOST('invoice');
    $status = getPOST('status');
    $note   = getPOST('note');

    if ($status != 'finished' && $status != 'delivered') {
      sendJSON(['error' => 1, 'message' => 'Status is not allowed.']);
    }

    if ($sale = $this->site->getSaleByReference($inv)) {
      if ($this->site->updateSaleStatus($sale->id, $status, $note)) {
        sendJSON(['error' => 0, 'message' => 'Sale status has been updated successfully.']);
      }
    }
    sendJSON(['error' => 1, 'message' => 'Failed to update sale status.']);
  }

  private function sales_v1($mode = NULL)
  {
    if ($this->requestMethod == 'GET') {
      $invoice = getGET('invoice');
      $phone   = getGET('phone');
      $response = [
        'error'   => 1,
        'message' => 'Harap masukkan data.'
      ];

      // if ($invoice && $phone) {
      if ($invoice) {
        $salesClause = [
          'reference' => $invoice
        ];

        if ($phone) {
          $customer = $this->site->getCustomerByPhone($phone);

          if ($customer) {
            $salesClause['customer_id'] = $customer->id;
          }
        }

        $sales = $this->site->getSales($salesClause);

        if (empty($sales)) {
          $response['message'] = 'Data tidak benar. Harap coba lagi.';
          sendJSON($response);
        }

        foreach ($sales as $sale) {
          $saleJS = getJSON($sale->json_data);
          $saleItems = $this->site->getSaleItemsBySaleID($sale->id);
          $pic = $this->site->getUserByID($sale->created_by);

          if ($sale && $saleItems) {
            $customer = $this->site->getCustomerByID($sale->customer_id);
            $payments = $this->site->getPayments(['sale_id' => $sale->id]);
            $payment_validation = $this->site->getPaymentValidationBySaleID($sale->id);

            if ($customer) {
              $sale->status = lang($sale->status);
              $response['error'] = 0;
              $response['message'] = 'OK';

              $response['data'] = [];
              $response['data']['customer'] = [
                'company' => $customer->company,
                'name'  => $customer->name,
                'phone' => $customer->phone
              ];

              if ($payments) {
                foreach ($payments as $payment) {
                  $bank = $this->site->getBankByID($payment->bank_id);
                  $biller = $this->site->getBillerByID($bank->biller_id);

                  $response['data']['payments'][] = [
                    'date' => $payment->date,
                    'reference' => $payment->reference,
                    'biller'    => $biller->name,
                    'bank_name' => $bank->name,
                    'account_no' => $bank->number,
                    'method' => $payment->method,
                    'amount' => floatval($payment->amount)
                  ];
                }
              }

              $response['data']['pic'] = [
                'id'   => intval($pic->id),
                'name' => $pic->fullname
              ];

              $warehouse = $this->site->getWarehouse(['id' => $sale->warehouse_id]);

              $response['data']['sale'] = [
                'no'                      => $sale->reference,
                'date'                    => $sale->date,
                'est_complete_date'       => ($saleJS->est_complete_date ?? ''),
                'payment_due_date'        => ($saleJS->payment_due_date ?? ''),
                'waiting_production_date' => ($saleJS->waiting_production_date ?? ''),
                'grand_total'             => floatval($sale->grand_total),
                'paid'                    => floatval($sale->paid),
                'balance'                 => floatval($sale->grand_total - $sale->paid),
                'source'                  => ($saleJS->source ?? '-'),
                'status'                  => lang($sale->status),
                'payment_status'          => lang($sale->payment_status),
                'paid_by'                 => ($sale->payment_method ?? '-'),
                'outlet'                  => $sale->biller,
                'note'                    => htmlDecode($sale->note),
                'warehouse'               => $warehouse->name,
                'warehouse_code'          => $warehouse->code,
                'approved'                => ($saleJS->approved ?? 0)
              ];

              $response['data']['sale_items'] = [];

              foreach ($saleItems as $saleItem) {
                $saleItemJS   = json_decode($saleItem->json_data);
                $product      = $this->site->getProductByCode($saleItem->product_code);
                $operator     = $this->site->getUserByID($saleItemJS->operator_id ?? NULL);
                $operatorName = ($operator ? $operator->fullname : '');

                $response['data']['sale_items'][] = [
                  'id'           => intval($saleItem->id),
                  'product_code' => $saleItem->product_code,
                  'product_name' => $saleItem->product_name,
                  'price'        => floatval($saleItem->price),
                  'subtotal'     => floatval($saleItem->subtotal),
                  'width'        => floatval($saleItemJS->w),
                  'length'       => floatval($saleItemJS->l),
                  'area'         => floatval($saleItemJS->area),
                  'quantity'     => floatval($saleItemJS->sqty),
                  'spec'         => $saleItemJS->spec,
                  'status'       => lang($saleItemJS->status),
                  'due_date'     => ($saleItemJS->due_date ?? ''),
                  'completed_at' => ($saleItemJS->completed_at ?? ''),
                  'operator'     => $operatorName
                ];
              }

              if ($payment_validation) {
                $response['data']['payment_validation'] = [
                  'amount'           => floatval($payment_validation->amount),
                  'unique_code'      => intval($payment_validation->unique_code),
                  'transfer_amount'  => floatval($payment_validation->amount + $payment_validation->unique_code),
                  'expired_date'     => $payment_validation->expired_date,
                  'transaction_date' => $payment_validation->transaction_date,
                  'description'      => $payment_validation->description,
                  'status'           => lang($payment_validation->status)
                ];
              }

              sendJSON($response);
            }
          }
        }
        $response['message'] = 'Data tidak benar. Harap coba lagi.';
        sendJSON($response);
      }

      sendJSON($response);
    } else if ($this->requestMethod == 'POST') { // Create Sales/Invoice.
      if ($mode == 'delete') {
        $this->sales_delete();
      } else if ($mode == 'add_transfer') {
        $this->sales_add_transfer();
      } else if ($mode == 'cancel_transfer') {
        $this->sales_cancel_transfer();
      } else if ($mode == 'edit') {
        $this->sales_edit();
      } else if ($mode == 'status') {
        $this->sales_status();
      }

      $body = file_get_contents('php://input');
      $api  = json_decode($body);

      if (!$api) sendJSON(['error' => 1, 'message' => 'Request is invalid.']);

      $phone        = ($api->phone ?? NULL);
      $note         = ($api->note  ?? NULL);
      // $due_date     = ($api->due_date ?? NULL);
      $discount     = ($api->discount ?? 0);
      $items        = ($api->items ?? NULL); // [code, price, width, length, quantity, note]
      $use_transfer = ($api->use_transfer ?? 0);
      $bl_code      = ($api->biller ?? 'ONL'); // Default ONL.
      $wh_code      = ($api->warehouse ?? 'DUR'); // Default DUR.

      if ($phone && $items) {
        $customer = $this->site->getCustomerByPhone($phone);
        $status   = 'need_payment'; // Default
        $payment_status = ($use_transfer == 1 ? 'waiting_transfer' : 'pending');

        $isSpecialCustomer = isSpecialCustomer($customer->id);

        if (!$customer) sendJSON(['error' => 1, 'message' => 'Customer not found.']);

        if ($isSpecialCustomer) {
          $status = 'preparing'; // Default for Privilege or TOP.
        }

        $user      = $this->site->getUserByUsername('w2p'); // Web2Print
        $biller    = $this->site->getBiller(['code' => $bl_code]); // Default Online.
        $warehouse = $this->site->getWarehouse(['code' => $wh_code]); // Default Durian.

        if (!$biller)    sendJSON(['error' => 1, 'message' => 'Biller is not found.']);
        if (!$warehouse) sendJSON(['error' => 1, 'message' => 'Warehouse is not found.']);

        $total = 0.0;

        foreach ($items as $item) {
          if (empty($item->code)) sendJSON(['error' => 1, 'msg' => 'Product code is required.']);

          $product = $this->site->getProductByCode($item->code);
          if (!$product) sendJSON(['error' => 1, 'msg' => "Product {$item->code} is not found."]);

          $category = $this->site->getProductCategoryByID($product->category_id);
          if (!$category) sendJSON(['error' => 1, 'msg' => "Product Category is not found."]);

          if (strcasecmp($category->code, 'DPI') === 0) {
            if (empty($item->width) || empty($item->length)) {
              sendJSON(['error' => 1, 'message' => "Width and Length are required for {$item->code}."]);
            }
          }

          if (empty($item->quantity)) sendJSON(['error' => 1, 'message' => 'Quantity is required.']);

          $item->width  = ($item->width  ?? 0);
          $item->length = ($item->length ?? 0);
          $item->note   = getExcerpt(strip_tags($item->note ?? ''), 50);

          // Price calculation based on quantity.
          $area = $item->width * $item->length;
          $qty   = ($area > 0 ? $area * $item->quantity : $item->quantity);

          $priceGroupId = ($customer->price_group_id ?? $warehouse->price_group_id ?? 1); // Default 1 (Zona 1).
          // $product->id, $priceGroupId, $qty
          $price = ($item->price ?? getProductPriceByQty([
            'product_id' => $product->id,
            'price_group_id' => $priceGroupId,
            'quantity' => $qty
          ]));

          $sale_items[] = [
            'product_id'   => $product->id,
            'price'        => $price,
            'quantity'     => $item->quantity,
            'warehouse_id' => $warehouse->id,
            'operator_id'  => $user->id,
            'width'        => $item->width,
            'length'       => $item->length,
            'spec'         => $item->note,
            'status'       => $status
          ];

          $total += round($price * $qty);
        }

        unset($area, $price, $qty);

        $paymentDueDate = date('Y-m-d H:i:s', strtotime("+1 day")); // Create expired 1 day.

        $sale_data = [
          'date'             => date('Y-m-d H:i:s'),
          'customer_id'      => $customer->id,
          'biller_id'        => $biller->id,
          'warehouse_id'     => $warehouse->id,
          'note'             => $note,
          'discount'         => $discount,
          'status'           => $status,
          'payment_due_date' => $paymentDueDate,
          'payment_status'   => $payment_status,
          'created_by'       => $user->id,
          'source'           => 'W2P' // Signature if invoice created by Web2Print.
        ];

        if ($sale_id = $this->site->addSale($sale_data, $sale_items)) {
          $date = $sale_data['date'];
          $sale = $this->site->getSaleByID($sale_id);

          if ($sale) {
            if ($use_transfer) { // If using bank transfer. Add new payment validation.
              $pv_data = [
                'created_at'    => $date,
                'expired_date'  => $paymentDueDate,
                'reference'     => $sale->reference,
                'sale_id'       => $sale->id,
                'amount'        => $total,
                'biller_id'     => $biller->id,
                'created_by'    => $sale_data['created_by']
              ];

              PaymentValidation::add($pv_data);
            }

            sendJSON([
              'error'    => 0,
              'message'  => 'Sale has been added successfully.',
              'sale'     => [
                'reference' => $sale->reference,
              ],
              'customer' => [
                'name'    => $customer->name,
                'company' => $customer->company,
                'phone'   => $customer->phone
              ],
              'url' => "https://indoprinting.co.id/trackorder?inv={$sale->reference}&phone={$customer->phone}&submit=1"
            ]);
          }
        }
        sendJSON(['error' => 1, 'message' => 'Failed to add sale.']);
      }

      sendJSON(['error' => 1, 'message' => 'Phone or Items is not correct.']);
    }
  }

  private function sendmail_v1()
  {
    if ($this->requestMethod != 'POST') {
      $this->redirect();
    }

    $from         = ($_POST['from'] ?? 'sd@indoprinting.co.id');
    $from_name    = ($_POST['from_name'] ?? 'Indoprinting Support');
    $replyto      = ($_POST['replyto'] ?? 'sd@indoprinting.co.id');
    $replyto_name = ($_POST['replyto_name'] ?? 'Indoprinting Support');
    $to           = ($_POST['to'] ?? NULL);
    $to_name      = ($_POST['to_name'] ?? '');
    $subject      = ($_POST['subject'] ?? 'From Indoprinting Support');
    $body         = ($_POST['body'] ?? 'From <b>Indoprinting Support</b>');

    if (!$to) {
      die('No recipient');
    }

    $mail = new PHPMailer(TRUE);
    $mail->setFrom($from, $from_name, FALSE);
    $mail->addReplyTo($replyto, $replyto_name);
    $mail->addAddress($to, $to_name);
    $mail->isHTML(TRUE);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = TRUE;
    $mail->Username = 'sd@indoprinting.co.id';
    $mail->Password = 'Durian100$';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    //$mail->SMTPDebug = 4;

    try {
      if ($mail->send()) {
        sendJSON(['success' => 1, 'text' => 'Sent']);
      } else {
        sendJSON(['success' => 0, 'text' => 'Failed']);
      }
    } catch (Exception $e) {
      echo ('Error: ' . $mail->ErrorInfo);
    }
  }

  private function sendwa_v1()
  {
    if ($this->requestMethod == 'POST') {
      $api_key  = getPOST('api_key');
      $phone    = getPOST('phone');
      $message  = getPOST('message');
      $sendDate = getPOST('send_date');

      if ($this->site->addWAJob([
        'api_key' => $api_key,
        'phone' => $phone,
        'message' => $message,
        'send_date' => $sendDate
      ])) {
        sendJSON(['error' => 0, 'message' => 'Sent successfully.']);
      }
      sendJSON(['error' => 1, 'message' => getLastError()]);
    }

    http_response_code(405);
    sendJSON(['error' => 1, 'message' => 'Method Not Allowed']);
  }

  private function telegram_v1()
  {
    $api = 'https://api.telegram.org/bot740017721:AAHbiVM_9k_VqPJDCa301tmHlJrvYLZZgoE/{method}';
    $method = '';
    $tg_response = json_decode(file_get_contents('php://input'));
    if (empty($tg_response)) {
      $this->redirect();
    }

    $command = $tg_response->message->text;

    switch ($command) {
      case '/list': {
          $method = 'sendmessage';
          $text = 'Response OK.';
          break;
        }
      default: {
          $method = 'sendmessage';
          $text = 'Unknown command.';
        }
    }

    if ($method) {
      $api_url = preg_replace('/\{method\}/i', $method, $api);
      $this->http_post($api_url, json_encode([
        'chat_id' => $tg_response->message->from->id,
        'text' => $text
      ]), ['Content-Type: application/json']);
    }
  }

  private function users_v1()
  {
    if ($this->requestMethod == 'GET') {
      $whCode = getGET('warehouse');
      $groupName  = getGET('group');

      $clauses = [];

      if ($whCode) {
        $warehouse = $this->site->getWarehouseByCode($whCode);

        if ($warehouse) {
          $clauses['warehouse_id'] = $warehouse->id;
        }
      }

      if ($groupName) {
        $group = $this->site->getUserGroupByName($groupName);

        if ($group) {
          $clauses['group_id'] = $group->id;
        }
      }

      $users = $this->site->getUsers($clauses);

      if ($users) {
        sendJSON(['error' => 0, 'users' => $users]);
      }

      sendJSON(['error' => 1, 'msg' => 'Users are not available.']);
    } else if ($this->requestMethod == 'POST') {
    }
  }

  // private function users_v1()
  // {
  //   if ($this->requestMethod == 'GET') {
  //     $warehouse_id = getGET('warehouse');

  //     $clauses = [];

  //     if ($warehouse_id) $clauses['warehouse_id'] = $warehouse_id;

  //     $users = $this->site->getUsers($clauses);

  //     if ($users) {
  //       sendJSON(['error' => 0, 'users' => $users]);
  //     }

  //     sendJSON(['error' => 1, 'msg' => 'Users are not available.']);
  //   } else if ($this->requestMethod == 'POST') {

  //   }
  // }

  private function validateQRIS_v1()
  {
    $accountNo = getPOST('account_no');
    $amount    = getPOST('amount');
    $invoice   = getPOST('invoice');

    if ($amount <= 0) sendJSON(['error' => 1, 'message' => 'Amount must be greater than zero.']);

    $sale = $this->site->getsaleByReference($invoice);

    if (!$sale) sendJSON(['error' => 1, 'message' => 'Sale not found.']);

    $bank = $this->site->getBank(['number' => $accountNo, 'biller_id' => $sale->biller_id]);

    if (!$bank) sendJSON(['error' => 1, 'message' => 'Bank not found.']);

    $creator = $this->site->getUserByUsername('w2p'); // By Web2Print

    $payment = [
      'date'       => date('Y-m-d H:i:s'), // $pv_updated->transaction_date,
      'sale_id'    => $sale->id,
      'amount'     => $amount,
      'method'     => 'Transfer',
      'bank_id'    => $bank->id,
      'created_by' => $creator->id,
      'type'       => 'received'
    ];

    if ($this->site->addSalePayment($payment)) {
      sendJSON(['error' => 0, 'message' => "Invoice {$sale->reference} has been paid."]);
    }
    sendJSON(['error' => 1, 'message' => "Failed to pay invoice {$sale->reference}."]);
  }

  private function viewerLocator_v1()
  {
    if ($this->requestMethod == 'POST') {
      $this->load->model('viewer_model');

      $geoData = [
        'referral' => getPOST('ref'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'lat' => getPOST('lat'),
        'lon' => getPOST('lon')
      ];

      if ($this->viewer_model->addGeolocator($geoData)) {
        sendJSON(['error' => 0, 'msg' => 'OK']);
      }
      sendJSON(['error' => 1, 'msg' => 'NOK']);
    }
  }

  private function warehouses_v1()
  {
    if ($this->requestMethod == 'GET') {
      $warehouses = [];
      $whCode = getGET('code');

      if ($whCode) {
        $warehouses[] = $this->site->getWarehouseByCode($whCode);
      } else {
        $warehouses = $this->site->getWarehouses();
      }

      foreach ($warehouses as $warehouse) {
        if ($warehouse->code == 'ADV' || $warehouse->code == 'LUC' || $warehouse->code == 'BAL')
          continue; // Ignore.

        $result[] = [
          'id'   => $warehouse->id,
          'code' => $warehouse->code,
          'name' => $warehouse->name
        ];
      }

      sendJSON(['error' => 0, 'data' => $result]);
    } else if ($this->requestMethod == 'POST') {
      // For update.
    }
  }

  public function index()
  {
    //$this->redirect();
    echo ('A$$ h0l3');
  }

  /**
   * API Version 1
   */
  public function v1($module = NULL)
  {
    $args = func_get_args();
    if (method_exists($this, $module . '_' . API_VERSION)) {
      array_shift($args); // Important to remove $module from args.
      call_user_func_array(array($this, $module . '_' . API_VERSION), $args);
    } else {
      $this->redirect();
    }
  }
}
/* EOF */