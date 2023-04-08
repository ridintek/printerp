<?php

declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

class Debug extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    echo "Index";
  }

  /**
   * Restore complete after edit sale where status is waiting_production.
   */
  public function fix_complete_20230329()
  {
    $sales = DB::table('sales')->whereIn('status', ['waiting_production'])->get();

    $success = 0;

    DB::transStart();

    foreach ($sales as $sale) {
      // if ($sale->id != 43055) continue;

      // dbgprint($sale); die;

      $saleItems = SaleItem::get(['sale_id' => $sale->id]);

      foreach ($saleItems as $saleItem) {
        $saleItemJS = getJSON($saleItem->json_data);

        // dbgprint($saleItem, $saleItemJS); die;

        if (!empty($saleItemJS->completed_at)) {
          try {
            $completeDate = new DateTime($saleItemJS->completed_at);

            $res = SaleItem::complete((int)$saleItem->id, [
              'quantity'    => $saleItem->quantity,
              'created_at'  => $completeDate->format('Y-m-d H:i:s'),
              'created_by'  => $saleItemJS->operator_id
            ]);

            if (!$res) {
              die(getLastError());
            }

            $success++;
          } catch (Exception $e) {
            die($e->getMessage());
          }
        }
      }
    }

    DB::transComplete();

    dbgprint("Success: {$success}");
  }

  public function dbtrans()
  {
    DB::transStart();

    $res = DB::table('test1')->insert(['name' => 'HAMNAH']);

    if (!$res) {
      echo "Error 1: " . print_r(DB::error()['message'], true);
    }

    $res = DB::table('test2')->insert(['namex' => 'DONO']);

    if (!$res) {
      echo "Error 2: " . print_r(DB::error()['message'], true);
    }

    DB::transComplete();

    if (DB::transStatus()) {
      echo "Success";
    }
  }

  public function writesheet()
  {
    $res = setGoogleSheet('1arv83XA2ySRAos6aFvhqLWIm804CjgUyChj7DsxaBj0_', 'B1414', [['CODE1', 'NAME1'], ['CODE2', 'NAME2']]);
    echo $res;
  }

  public function sendpayload()
  {
    /**
     * Forward to IDP Studio for Topup Validation.
     */
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => 'https://studio.indoprinting.co.id/account/request/deposit',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => '
      {
        "api_key": "NHlUZE9oc2w5dTEzemNNbElZNmJ0dlpaMzd4bFdCc0JXZmhaZGlxMjBMcEVSVUN4WU1vMnlMUjczTlZy61ad7a3fab24d",
        "account_id": 1737,
        "module": "bca",
        "account_name": "ANITA RATNASARI R ST",
        "account_number": "8030200234",
        "balance": 12720562,
        "data_mutasi": [{
          "transaction_date": "2021-11-13 17:10:00",
          "description": "TRSF E-BANKING CR05/06 95031 RIYAN WIDIYANTO TEST",
          "type": "CR",
          "amount": 0,
          "balance": 0
        }]
      }
      ',
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
      ]
    ]);
    $r = curl_exec($curl);
    var_dump($r);
    echo '<br>' . $r;
  }

  public function complete_sale()
  {
    $r = SaleItem::complete(32361, ['quantity' => 1]);

    var_dump($r);
  }

  public function customer()
  {
    $cust = $this->site->getCustomerSuggestions('08231166');

    print_r($cust);
  }

  public function log()
  {
    dbglog('warning', 'WHAT IS YOU?');
    dbglog('error', 'WHAT IS YOU?');
    dbglog('success', 'WHAT IS YOU?');
    dbglog('info', 'WHAT IS YOU?');
    log_message('error', 'GO FUCK YOURSELF');
  }

  public function null_safe()
  {
    $obj = new stdClass;
    $obj->message = NULL;

    echo $obj?->message?->text . '<br>';
  }

  public function fix_complete_20221214()
  {
    $sales = DB::table('sales')->whereIn('status', ['completed', 'completed_partial'])->get();

    $success = 0;
    foreach ($sales as $sale) {
      $saleItems = SaleItem::get(['sale_id' => $sale->id]);

      foreach ($saleItems as $saleItem) {
        $saleItemJS = getJSON($saleItem->json_data);
        $stock = Stock::getRow(['saleitem_id' => $saleItem->id]);
        if ($sale->id == 18600) {
          // dbgprint($stock);die;
        }
        if (!$stock && ($saleItemJS->status == 'completed' || $saleItemJS->status == 'completed_partial')) {
          $this->site->completeSaleItem($saleItem->id, ['quantity' => $saleItem->finished_qty, 'created_by' => $saleItemJS->operator_id]);
          $success++;
        }
      }
    }

    dbgprint("Success: {$success}");
  }

  public function fix_sales_20221212()
  {
    $sales = DB::table('sales')->orderBy('id', 'DESC')->get();
    $oldRef = '';

    foreach ($sales as $sale) {
      // if (strcasecmp($oldRef, $sale->reference) == 0) {
      //   // INV-2022/12/2009
      //   $num = intval(explode('/', $sale->reference)[2]);
      //   $num++;
      //   Sale::update((int)$sale->id, ['reference' => substr($sale->reference, 0, 12)]);
      //   continue;
      // }

      if (strlen($sale->reference) == 12) {
        if (strlen($oldRef) <= 12) continue;

        $num = intval(explode('/', $oldRef)[2]);
        $num++;

        $ref = $sale->reference . $num;
        $oldRef = $ref;

        Sale::update((int)$sale->id, ['reference' => $ref]);
      }

      if (strlen($sale->reference) > 12) $oldRef = $sale->reference;
    }

    echo "OK";
  }

  public function gencode()
  {
    $r = generateInternalUseUniqueCode('sparepart');

    dbgprint($r);
  }

  public function stockvalue()
  {
    $opt = [
      'start_date' => '2022-10-01',
      'end_date' => '2022-11-17'
    ];

    $warehouseId = (int)Warehouse::getRow(['code' => 'LUC'])->id;
    $r = getWarehouseStockValue($warehouseId, $opt);

    dbgprint($r);
  }

  public function daily()
  {
    $period = "2022-11";
    $a = '02';

    $dailyRevenue = DB::table('sales')
      ->selectSum('grand_total', 'total')
      ->where('biller_id', 2)
      ->like("date", $period . "-{$a}", 'right')
      ->getRow()->total;

    dbgprint($dailyRevenue);
  }

  public function dailyperformance()
  {
    $opt = [];
    $res = getDailyPerformanceReport($opt);

    dbgprint($res);
  }

  public function datetime_local()
  {
    echo dtLocal("2022-09-26 23:21:30");
  }

  public function jobs()
  {
    if ($args = func_get_args()) {
      $method = __FUNCTION__ . '_' . $args[0];

      if (method_exists($this, $method)) {
        array_shift($args);
        return call_user_func_array([$this, $method], $args);
      }
    }

    $args = func_get_args();
  }

  protected function jobs_second()
  {
    $args = func_get_args();

    echo "SECOND\r\n";
    print_r($args);
    echo "\r\n";
  }

  public function sapi()
  {
    $sapi = php_sapi_name();

    echo "You are in '{$sapi}' environment.\r\n";
  }

  public function upref()
  {
    $r = OrderRef::updateReference('transfer');
    var_dump($r);
  }

  public function session()
  {
    $r = XSession::all();

    echo '<pre>';
    print_r($r);
    echo '</pre>';
  }

  public function gsheet()
  {
    $values = getGoogleSheet('1arv83XA2ySRAos6aFvhqLWIm804CjgUyChj7DsxaBj0', 'B1');

    echo '<pre>';
    print_r($values);
    echo '</pre>';
  }

  public function totalQuantity()
  {
    $warehouse = Warehouse::getRow(['name' => 'Durian']);
    $product = Product::getRow(['code' => 'KLIKPOD']);

    $total = Stock::totalQuantity((int)$product->id, (int)$warehouse->id);

    print_r($total);
  }

  public function model()
  {
    $items = $this->site->getProductNames('stamp');

    echo ('<pre>');
    print_r($items);
    echo ('</pre>');
  }

  public function clearcache()
  {
    $caches = $this->cache->getAllKeys();

    foreach ($caches as $key) {
      // $this->cache->delete($key);
    }

    echo '<pre>';
    print_r($caches);
    echo '</pre>';
  }

  public function memcache()
  {
    $cache = new Memcached();

    $cache->addServer('127.0.0.1', 11211);

    $data = $cache->get('clouder');

    if (!$data) {
      $data = 'HELLO BOSSY';
      $cache->set('clouder', $data);
      echo 'Cached<br>';
    }

    echo $data;
  }

  public function upload_file()
  {
    $upload = new FileUpload();

    if ($this->requestMethod == 'POST') {
      if ($upload->has('attachment') && $upload->getSize('mb') < 2) {
        if ($hashname = $upload->storeRandom()) {
          $this->response(201, ['message' => "Success {$hashname}"]);
        }
        $this->response(400, ['message' => 'Failed']);
      }
    } else {
      echo "Upload files";
    }
  }

  public function bank()
  {
    $q = $this->Bank->getBanks(['active ' => 0]);

    echo '<pre>';
    print_r($q);
    echo '</pre>';
  }

  public function user()
  {
    $u = getUser(['username' => 'system']);
    // echo getLastError();
    print_r($u);
  }

  public function classObject()
  {
    $obj = (object)[
      'name' => 'R'
    ];

    echo gettype($obj);
  }

  public function explode1()
  {
    $str = '';
    $res = explode(',', $str);
    var_dump($res);
  }

  public function payments()
  {
    $payments = $this->site->getPayments([
      'start_date' => '2022-03-01',
      'end_date'   => '2022-03-31',
      'method' => 'Cash'
    ]);

    $total = 0;

    foreach ($payments as $payment) {
      if ($payment->type == 'received') $total += $payment->amount;
      if ($payment->type == 'sent')     $total -= $payment->amount;
    }

    echo count($payments);
  }

  /**
   * Check system executions.
   */
  public function exec()
  {
    $ex = [];

    if (function_exists('exec')) {
      $e = exec('echo "exec() OK"');
      echo $e . '<br>';
      $ex[] = 'exec';
    }

    if (function_exists('passthru')) {
      passthru('echo "passthru() OK"');
      echo '<br>';
      $ex[] = 'passthru';
    }

    if (function_exists('shell_exec')) {
      $e = exec('echo "shell_exec() OK"');
      echo $e . '<br>';
      $ex[] = 'shell_exec';
    }

    if (function_exists('system')) {
      system('echo "system() OK"');
      echo '<br>';
      $ex[] = 'system';
    }

    echo '<br>';

    if (count($ex)) {
      echo "Available commands: " . implode(',', $ex);
    } else {
      echo "No commands available.";
    }
  }

  public function ocr()
  {
    $target = FCPATH . 'files/trackingpod/attachments/sample.jpeg';

    echo implode('<br>', ocr($target));
  }

  /**
   * Mutex dengan delay.
   */
  public function mutex1()
  {
    $hMutex = mutexCreate('test');

    sleep(10);

    if ($hMutex && mutexRelease($hMutex)) {
      echo "Success";
    } else {
      echo "Failed";
    }
  }

  /**
   * Mutex tanpa delay.
   */
  public function mutex2()
  {
    $hMutex = mutexCreate('test', FALSE);

    if ($hMutex && mutexRelease($hMutex)) {
      echo "Success";
    } else {
      echo "Failed";
    }
  }

  public function productvalue()
  {
    $pvs = getProductStockValue(['warehouse_id' => 5]);

    d($pvs);
    die();
  }

  public function call_api()
  {
    $res = $this->app->getWarehouses(['active' => 1]);

    if (!$res) {
      die(getLastError());
    }

    d($res);
  }

  public function dbconvert()
  {
    $payments = $this->site->getPayments();
    $pay = 0;

    foreach ($payments as $payment) {
      if ($payment->type == 'sent' && $payment->amount > 0) {
        $this->site->updatePayment($payment->id, ['amount' => ($payment->amount * -1)]);
        $pay++;
      }
    }

    echo "{$pay} payments converted to minus.<br>";
  }

  public function html_flush()
  {
    // FAILED
    include(VIEWPATH . '/debug.php');
  }

  public function pdf()
  {
    if (exportPDF('<b>Bismillah</b>', 'DomPDF.pdf')) :
      echo "Success";
    else :
      echo "Failed";
    endif;
  }

  public function pdo_drivers()
  {
    $pdo = new PDO('mysql:host=localhost;dbname=idp_erp;charset=utf8mb4', 'idp_erp', 'IdpDuri4n100$');

    print_r(PDO::getAvailableDrivers());
    $pdo = NULL;
  }

  public function array_function()
  {
    $payments = [];
    $data = [["no" => 1]];

    $r = array_merge($payments, $data);

    d($r);
  }

  public function emoji()
  {
    echo "\u{1F6AB}";
  }

  public function sendmail()
  {
    if (sendMail()) {
      echo "Success";
    } else {
      $error = getLastError();
      echo "Failed: {$error}";
    }
  }

  public function sendwa2()
  {
    $message1 = "Hi Juan Amirullah,\n\n" .
      "Item berikut telah dilakukan perbaikan:\n\n" .
      "*Outlet*: Durian\n" .
      "*Assigned By*: Juan Amirullah\n" .
      "*Item Code*: ACDURPODAI2\n" .
      "*Item Name*: AC POD Daikin 2 pk\n" .
      "*Fixed At*: 2022-09-26 12:33:21\n" .
      "*Fixed By*: Yudhit Adi Ivan Prasetyo\n" .
      "*User Note*: AC panas\n" .
      "*TS Note*: Sudah dibersihkan\n\n" .
      "Jangan lupa untuk memberikan review bintang 5 kepada TS pada link berikut:\n\n" .
      admin_url("machines?code=ACDURPODAI2\n\n") .
      "Terima kasih.";

    $message2 = "Hi Yudhit Adi Ivan Prasetyo,\n\n" .
      "Terima kasih telah melakukan perbaikan:\n\n" .
      "*Outlet*: Durian\n" .
      "*Assigned By*: Juan Amirullah\n" .
      "*Item Code*: ACDURPODAI2\n" .
      "*Item Name*: AC POD Daikin 2 pk\n" .
      "*Fixed At*: 2022-09-26 12:33:21\n" .
      "*Fixed By*: Yudhit Adi Ivan Prasetyo\n" .
      "*User Note*: AC panas\n" .
      "*TS Note*: Sudah dibersihkan\n";

    $this->site->addWAJob(['phone' => '082311662064', 'message' => $message2]);
  }

  public function status()
  {
    $date = date('Y-m-d H:i:s');
    $microtime = microtime(TRUE);

    $rest = ($microtime - $this->microtime) * 1000;

    if ($this->isAJAX) {
      sendJSON(['err' => 0, 'date' => $date, 'load_time' => $rest, 'text' => 'Server is working properly']);
    } else {
      echo ("<pre>[{$date}] Server is working properly. Total load time {$rest} ms.</pre>");
    }
  }

  public function cli()
  {
    if (isCLI()) {
      echo "Yes it's CLI.\r\n";
    } else {
      echo "It's not CLI.\r\n";
    }
  }

  public function complete_item()
  {
    $this->site->completeSaleItem(234293, ['quantity' => 1]);
  }

  public function datetime_takeorder()
  {
    getWorkingDateTime('2021-06-18 03:00:00');
  }

  public function datetime()
  {
    $minute = round(273.6);
    $time = strtotime("+{$minute} minutes");
    $r = date('Y-m-d H:i:s', strtotime("+{$minute} minutes"));
    d($time, $r);
  }

  public function datetime2()
  {
    try {
      $datetime = new DateTime('sembarang');
    } catch (Exception $err) {
      print_r($err->getMessage());
    }
  }

  public function datetime3()
  {
    for ($a = 24; $a >= 0; $a--) {
      $dateMonth = date('Y-m', strtotime('-' . $a . ' month', strtotime(date('Y-m-') . '01')));
      d($dateMonth);
    }
  }

  public function datetime4()
  {
    $date = new DateTime('2022-04-15 23:00:00');
    $inv  = new DateInterval('PT8H'); // 8 jam

    $date->add($inv);

    echo $date->format('Y-m-d H:i:s');
  }

  public function duration_time()
  {
    $current = new DateTime();
    $endDate = new DateTime('2022-11-18 00:00:00');

    $timeleft = $current->diff($endDate)->format('%R');

    echo $timeleft;
  }

  public function excel_readwrite()
  {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $sheet  = $reader->load(FCPATH . 'files/templates/QMS_Report.xlsx');
    $workSheet = $sheet->getActiveSheet();
    $workSheet->setTitle('QMS Report');
    $workSheet->setCellValue('H5', '=SUM(V5,AE5)');
    $workSheet->setCellValue('V5', '=TIME(0,10,0)');
    $workSheet->setCellValue('AE5', '=TIME(1,20,00)');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
    // $writer->save('php://output');
    $writer->save(FCPATH . 'files/exports/Test.xlsx');
    echo "OK";
  }

  public function filter_datetime()
  {
    $r = filterDateTime('2021-05-05 22:23:40');
    d($r);
  }

  public function fadhilah()
  {
    if ($this->requestMethod == 'GET') {
      $this->site->addWAJob(['phone' => '089506862869', 'message' => "I \u{2764} YOU \u{1F618}"]);
      die("<h1>Dicek WA nya ya \u{1F618}</h1>");
    }
  }

  public function hamnah()
  {
    if ($this->requestMethod == 'GET') {
      $this->site->addWAJob(['phone' => '088226339696', 'message' => "I \u{2764} YOU HAMNAH \u{1F618}"]);
      die("<h1>Dicek WA nya ya sayang \u{1F618}</h1>");
    }
  }

  public function longest_datetime()
  {
    $dates = [
      '2021-05-10 22:00:00', '2021-05-22 04:00:00'
    ];

    $r = getLongestDateTime($dates);
    d($r);
  }

  public function queue_datetime()
  {
    // $date = getQueueDateTime();
  }

  public function balance_stock()
  {
    $product = $this->site->getProductByCode('TN619-C');
    $warehouse = $this->site->getWarehouseByCode('SAL');
    $endDate = '2021-05-18';

    $balance = $this->site->getStockQuantity($product->id, $warehouse->id, ['end_date' => $endDate]);

    d($balance);
  }

  public function begin_stock()
  {
    $product = $this->site->getProductByCode('TN619-Y');
    $warehouse = $this->site->getWarehouseByCode('DUR');

    $clause = [
      'product_id' => $product->id,
      'warehouse_id' => $warehouse->id
    ];
    $startDate = '2021-04-01';

    $begin_qty = $this->site->getStockBeginningQuantity($clause, $startDate);

    d($begin_qty);
  }

  public function duration()
  {
    $r = getDaysInPeriod('2021-10-27', '2021-10-30');

    echo $r;
  }

  public function fix()
  {
    $files = scandir(FCPATH . 'files/');

    if ($files) {
      $name = '';
      foreach ($files as $file) {
        $q = $this->db->get_where('sales', ['attachment' => $file]);
        if ($q->num_rows() > 0) {
          rename(FCPATH . 'files/' . $file, $this->upload_sales_path . $file);
          $name .= $file . '<br>';
        }

        // $q = $this->db->get_where('payments', ['attachment' => $file]);
        // if ($q->num_rows() > 0) {
        //   rename(FCPATH . 'files/' . $file, $this->upload_sales_path . $file);
        //   $name .= $file . '<br>';
        // }
      }
      echo $name;
    }
  }

  public function fix_machines()
  {
    $stocks = $this->site->getStocks(['not_null' => 'internal_use_id']);
    $recorded = [];

    foreach ($stocks as $stock) {
      if ($stock->machine_id) {
        $machine = $this->site->getMachineByID($stock->machine_id);
        if (!isset($machine->warehouse_id)) continue;
        $warehouse = $this->site->getWarehouseByID($machine->warehouse_id);
        if (!$warehouse) continue;
        $whName = $warehouse->name;
        $products = $this->site->getProducts(['name' => $machine->name, 'warehouses' => $whName]);

        if ($products) {
          $this->site->updateStockQuantity(['id' => $stock->id], ['machine_id' => $products[0]->id]);

          $recorded[] = $stock->id;
        }

        unset($machine, $warehouse, $whName, $products);
      }
    }

    foreach ($recorded as $rec) {
      echo "Stock [{$rec}] has been updated.<br>";
    }
  }

  public function fix_payments()
  {
    ini_set("memory_limit", "1024M"); // Required.

    $payments = $this->site->getPayments(['nul' => 'biller_id']);
    $counter = 0;
    $processCount = 0;
    $total   = count($payments);

    foreach ($payments as $payment) {
      if ($processCount >= 10) {
        $processCount = 0;
        sleep(2);
      }

      if (!$payment->biller_id) {
        $bank = $this->site->getBankByID($payment->bank_id);

        if ($bank) {
          $rest = $total - $counter;
          $donep = intval($counter / ($total / 100));
          echo "\rTotal: {$total}, Processed: {$counter}, Rest: {$rest}, Done: {$donep}%, Payment ID: {$payment->id}";
          $this->site->updatePayment($payment->id, ['biller_id' => $bank->biller_id]);
        }

        $counter++;
        $processCount++;
      }
    }

    echo "\r\nDone\r\n";
  }

  public function fix_sales_due_date()
  {
  }

  public function fix_stock_date()
  { // SAFE
    $count = 0;
    $failedCount = 0;
    $successCount = 0;

    $this->db->like('date', '0000-00-00', 'after');
    $q = $this->db->get('stocks');

    if ($q && $q->num_rows()) {
      $count = $q->num_rows();
      $stocks = $q->result();

      foreach ($stocks as $stock) {
        $sale = $this->site->getSaleByID($stock->sale_id);

        if ($sale) {
          $this->site->updateStockQuantity(['sale_id' => $sale->id], ['date' => $sale->date]);
          $successCount++;
        } else {
          $this->site->deleteStockQuantity(['sale_id' => $stock->sale_id]);
          $failedCount++;
        }

        unset($sale);
      }
    }
    echo ("Result: {$count}; Success: {$successCount}; Failed: {$failedCount}");
  }

  public function fix_klikpod()
  {
    $stocks = $this->site->getStocks(NULL, ['start_date' => '2021-06-01', 'end_date' => '2021-06-26']);

    if ($stocks) {
      foreach ($stocks as $stock) {
        if ($stock->product_type == 'service' && $stock->status == 'sent') {
          $this->site->updateStockQuantity(['id' => $stock->id], ['status' => 'received']);
        }
      }
      echo "DONE";
    }
  }

  public function strposit()
  {
    $r = strpos('2021-06-26 13:13:46', '2021-06-26 13');
    d($r);
  }

  public function find_saleitems()
  {
    $sales = $this->site->getSales(['start_date' => '2021-06-01', 'end_date' => '2021-06-26']);

    $str = '';
    echo "SELECT * FROM `sale_items` WHERE sale_id IN(";

    if ($sales) {
      foreach ($sales as $sale) {
        if ($sale->payment_status != 'due_partial') continue;
        // if (($sale->grand_total / 2) != $sale->paid && ($sale->grand_total / 2) != $sale->balance) continue;

        $saleItems = $this->site->getSaleItems(['sale_id' => $sale->id]);

        if ($saleItems) {
          // foreach ($saleItems as $saleItem) {
          //   if ($saleItem->unit_price) continue;
          //   if (strpos($saleItem->date, '2021-06-26 13') === FALSE) continue;

          //   $saleItemJS = json_decode($saleItem->json_data);

          //   if ($saleItemJS->status != 'completed' && $saleItemJS->status != 'delivered') continue;

          //   if ($this->site->deleteSaleItems(['id' => $saleItem->id])) {
          //     dbglog("Success delete sale item {$saleItem->id} for sale id {$saleItem->sale_id}");
          //   }
          // }
        } else {
          $str .= $sale->id . ',';
        }
      }

      echo rtrim($str, ',') . ');';
    }
  }

  public function _fix_sales()
  {
    $sales = $this->site->getSales(['start_date' => '2021-06-01', 'end_date' => '2021-06-26']);

    if ($sales) {
      foreach ($sales as $sale) {
        if ($sale->payment_status != 'due_partial') continue;
        // if (($sale->grand_total / 2) != $sale->paid && ($sale->grand_total / 2) != $sale->balance) continue;

        $saleItems = $this->site->getSaleItems(['sale_id' => $sale->id]);

        if ($saleItems) {
          foreach ($saleItems as $saleItem) {
            if ($saleItem->unit_price) continue;
            if (strpos($saleItem->date, '2021-06-26 13') === FALSE) continue;

            $saleItemJS = json_decode($saleItem->json_data);

            if ($saleItemJS->status != 'completed' && $saleItemJS->status != 'delivered') continue;

            if ($this->site->deleteSaleItems(['id' => $saleItem->id])) {
              // dbglog("Success delete sale item {$saleItem->id} for sale id {$saleItem->sale_id}");
            }
          }
        }
      }
      echo ("Process finished.");
    }
  }

  /**
   * Fungsi yang menyebabkan kekacauan pada 26 Jun 2021 17:00.
   * Dibuat protected agar tidak terjadi problem yg sama.
   */
  protected function __fix_sales()
  {
    $sales = $this->site->getSales(['start_date' => '2021-06-01', 'end_date' => '2021-06-26']);

    if ($sales) {
      foreach ($sales as $sale) {
        $saleItems = $this->site->getSaleItems(['sale_id' => $sale->id]);
        $saleItemsData = [];

        if ($saleItems) {
          foreach ($saleItems as $saleItem) {
            $saleItemJS = json_decode($saleItem->json_data);

            if (isCompleted($sale->status) && isCompleted($saleItemJS->status)) {
              $dueDate = ($saleItemJS->due_date ?? '2021-06-28 23:00:00');
              $completedAt = !empty($saleItemJS->updated_at) ? $saleItemJS->updated_at : date('Y-m-d H:i:s', strtotime('-3 hour', strtotime($dueDate)));

              $saleItemsData[] = [
                'product_id'   => $saleItem->product_id,
                'price'        => $saleItem->price,
                'quantity'     => $saleItem->quantity,
                'finished_qty' => $saleItem->finished_qty,
                'warehouse_id' => $saleItem->warehouse_id,
                'width'        => $saleItemJS->w,
                'length'       => $saleItemJS->l,
                'spec'         => $saleItemJS->spec,
                'status'       => $saleItemJS->status,
                'operator_id'  => $saleItemJS->operator_id,
                'due_date'     => $saleItemJS->due_date,
                'completed_at' => $completedAt
              ];
            }
          }

          if ($saleItemsData) {
            $this->site->updateSaleItems($sale->id, $saleItemsData);
          }
        }
      }
      echo ("Process finished.");
    }
  }

  public function cleanPayments()
  {
    if ($stats = $this->site->cleanEmptyPayments()) {
      $m = [];

      foreach ($stats as $stat) {
        $m[] = sprintf("Payment for [%s id:%d] has been deleted.", $stat['type'], $stat['id']);
      }

      if ($m) {
        echo ('<pre>');
        foreach ($m as $s) {
          echo $s;
        }
        echo ('</pre>');
      }
    }
  }

  public function barcode()
  {
    \Laminas\Barcode\Barcode::render('code39', 'image', ['text' => 'HALO']);
  }

  public function day_month()
  {
    // echo getDaysInMonth(2021, 2);
    $minute = 0.25 * 60;
    echo date('Y-m-d H:i:s', strtotime("+{$minute} minutes")) . '<br>';
    echo substr('802021', -4);
  }

  public function datatable()
  {
    $this->load->library('datatable');

    $this->datatable->select("warehouses.id AS id, code, name, users.fullname AS user_name")
      ->from('warehouses')
      ->join('users', 'users.warehouse_id = warehouses.id')
      ->where("code LIKE 'FAT'");

    echo ('<pre>');
    echo $this->datatable->generate();
    echo ('</pre>');
  }

  public function deflate()
  {
    $a = gzcompress('Riyan Widiyanto');
    echo $a . '<br>';
    echo strlen($a) . '<br>';
    echo gzuncompress($a);
  }

  public function mutasi_account_list()
  {
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_HEADER => FALSE,
      CURLOPT_HTTPHEADER => [
        'Authorization: Bearer tikXCBSpl2JGVr49ILhme7dHfbaQuOPFYNozMEc6'
      ],
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_URL => 'https://mutasi.indoprinting.co.id/api/accounts_list'
    ]);

    $res = curl_exec($curl);

    if ($res) {
      header('Content-Type: application/json');
      print_r($res);
    }
  }

  public function new_datatables()
  {
    $this->db->select("warehouses.id AS id, code, name, users.fullname AS user_name")
      ->from('warehouses')
      ->join('users', 'users.warehouse_id = warehouses.id')
      ->where("code LIKE 'FAT'");

    generateDatatables($this->db, ['warehouses.code', 'warehouses.name']);
  }

  public function fix_spec_html()
  {
    $sales = $this->site->getAllSales();
    foreach ($sales as $sale) {
      $sale_items = $this->site->getSaleItemsBySaleID($sale->id);
      if ($sale_items) {
        foreach ($sale_items as $sale_item) {
          $jsd = json_decode($sale_item->json_data);
          $this->site->updateSaleItem($sale_item->id, ['spec' => htmlRemove($jsd->spec)]);
        }
      }
    }
  }

  public function googlesheet()
  {
    $gsheet = $this->ridintek->googlesheet();
    $values = $gsheet->setSpreadsheetId('1bxvkn-DiTuV_oDekIJezrNKo0QuBIFSX8j5ZSpZdv1E')
      ->read('Master!B3:Q');
    $result = [];
    $keys = [
      'use', 'company', 'pic', 'phone', 'email', 'address', 'city', 'postal', 'country', 'payment_term',
      'acc_holder', 'acc_no', 'bank_name', 'bic', 'visit_day', 'visit_week'
    ];
    foreach ($values as $row) {
      $result[] = arrayCombine($keys, $row);
    }
    dbgprint($result);
  }

  public function holidays()
  {
    $r = isTodayHoliday('2022-05-17');

    echo ($r ? 'Holiday' : 'Not Holiday');
  }

  public function memcached()
  {
    $cache = $this->ridintek->cache();
    if ($q = $cache->get('quanti:ty/\\')) {
      echo 'You quantity: ' . $q;
      $cache->delete('qu\\an:tit/y');
    } else {
      echo 'Set quantity';
      $cache->set('qua:n\\t/ity', 20133);
    }
  }

  public function recon()
  {
    $this->db
      ->select('banks.number, banks.name, banks.holder, banks.type')
      ->where('active', 1)
      ->group_start()
      ->like('banks.type', 'Transfer')
      ->or_like('banks.type', 'EDC')
      ->group_end()
      ->group_by('banks.number');

    $q = $this->db->get('banks');

    if ($q->num_rows() > 0) {
      $data = file_get_contents(base_url('api/v1/mutasibank/accounts'));

      $res = json_decode($data);

      foreach ($q->result() as $row) { // Grouped by bank number.
        if ($row->number != '8030200234') continue;

        $banks = $this->site->getAllBanks();
        $mutasi_bank = NULL;
        $total_balance = 0;

        foreach ($banks as $bank) { // Collect balance.
          if (strcmp($row->number, $bank->number) === 0) {
            $total_balance += $bank->balance;
            $bankData[] = $bank;
          }
        }

        dd($bankData);

        foreach ($res as $mb) {
          if (strcmp($mb->account_no, $row->number) === 0) {
            $mutasi_bank = $mb;
            break;
          }
        }

        $recon = $this->site->getBankReconciliationByAccountNo($row->number);

        if ($recon) { // If exist, then update.
          $recon_data = [
            'erp_acc_name' => $row->holder,
            'account_no'   => $row->number,
            'amount_erp'   => $total_balance
          ];

          if ($mutasi_bank) {
            $recon_data['mb_acc_name']    = $mutasi_bank->account_name;
            $recon_data['mb_bank_name']   = $mutasi_bank->bank;
            $recon_data['amount_mb']      = $mutasi_bank->balance;
            $recon_data['last_sync_date'] = $mutasi_bank->last_bot_activity;
          }

          $this->site->updateBankReconciliation($recon->id, $recon_data);
        } else { // If not exist, insert new.
          $recon_data = [
            'erp_acc_name' => $row->holder,
            'account_no'   => $row->number,
            'amount_erp'   => $total_balance
          ];

          if ($mutasi_bank) {
            $recon_data['mb_acc_name']    = $mutasi_bank->account_name;
            $recon_data['mb_bank_name']   = $mutasi_bank->bank;
            $recon_data['amount_mb']      = $mutasi_bank->balance;
            $recon_data['last_sync_date'] = $mutasi_bank->last_bot_activity;
          }

          $this->db->insert('bank_reconciliations', $recon_data);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  public function safetystock()
  {
    $options = [
      'start_date' => date('Y-m-', strtotime('-1 month')) . '01',
      'end_date' => date('Y-m-d')
    ];

    $product = $this->site->getProductByCode('TN615/616/612K');
    $warehouse = $this->site->getWarehouseByCode('LUC');
    $current_stock = $this->site->getStockQuantity($product->id, $warehouse->id);

    $this->site->syncProductSafetyStock($product->id, $options);
    $safety_stock = $this->site->getProductByID($product->id)->safety_stock;

    dbgprint($current_stock, $product->min_order_qty, $safety_stock);

    $r = getOrderStock($current_stock, $product->min_order_qty, $safety_stock);
    dbgprint($r);
  }

  protected function sendWA($phone, $text, $engine = 'whacenter')
  {
    $query = [];
    $url   = '';

    $ph = phoneCode($phone);

    if ($engine == 'whacenter') {
      $query['device_id'] = '05fb9e0b23d2ef3f0b21eef5ba3a1f89';
      $query['message']   = $text;
      $query['number']    = $ph;
      $url                = 'https://app.whacenter.com/api/send';
    } else if ($engine == 'watsap') {
      $query['api-key']   = 'a66d60ee436b0861c28353611d089dc872629d09';
      $query['id_device'] = 1163;
      $query['pesan']     = $text;
      $query['no_hp']     = $ph;
      $url                = 'https://api.watsap.id/send-message';
      $query = json_encode($query);
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HEADER => FALSE,
      CURLOPT_POSTFIELDS => $query
    ]);

    $res = curl_exec($curl);

    if (!$res) {
      setLastError(curl_error($curl));
    }
    curl_close($curl);

    return $res;
  }

  public function send_wa()
  {
    $date = date('Y-m-d H:i:s');

    $direct = (getGET('direct') ?? 0);
    $engine = (getGET('engine') ?? 'whacenter');
    $hp     = (getGET('phone') ?? '082311662064');

    $text = "Pesan ini dikirim oleh {$engine}";

    if ($direct) {
      $r = $this->sendWA($hp, $text, $engine);
    } else {
      $r = $this->site->addWAJob(['phone' => $hp, 'message' => $text, 'send_date' => date('Y-m-d H:i:s')]);
    }

    echo $r;
  }

  public function sync_sales()
  {
    ini_set('max_execution_time', '0');
    $client = new GuzzleHttp\Client();
    $client->getAsync(base_url())->then(function ($response) {
      $this->site->syncSales(['start_date' => '2021-06-01', 'end_date' => '2021-06-29']);
    });
    echo ("JOBS DONE");
  }

  public function syncSalesBalance()
  {
    ini_set('max_execution_time', '0');

    $startDate = getGET('start_date');
    $endDate = getGET('end_date');

    $sales = $this->site->getSales([
      'start_date' => $startDate,
      'end_date' => $endDate
    ]);

    foreach ($sales as $sale) {
      if (isSpecialCustomer($sale->customer_id)) {
        $this->site->syncSales(['sale_id' => $sale->id]);
      } else {
        if ($sale->paid > 0) {
          $this->site->syncSales(['sale_id' => $sale->id]);
        }
      }
    }

    die("syncSalesBalance() Done! {$startDate} to {$endDate}");
  }

  public function sync_transfer()
  {
    $product = $this->site->getProductByCode('PL35');
    $warehouse = $this->site->getWarehouseByCode('TLO');

    $this->site->syncTransferSafetyStock($product->id, $warehouse->id);
  }

  public function trackingpod()
  {
    $startDate = (getGET('start_date') ?? date('Y-m-') . '01');
    $endDate   = (getGET('end_date') ?? date('Y-m-d'));

    d($startDate, $endDate);

    $tracks = $this->site->getTrackingPODs(['start_date' => $startDate, 'end_date' => $endDate]);

    d($tracks);
  }

  public function trackingpod_users()
  {
    $tracks = $this->site->getTrackingPODUsers();

    foreach ($tracks as $track) {
      $user = $this->site->getUserByID($track->created_by);
      $users[] = $user->first_name . ' ' . $user->last_name;
    }

    d($users);
  }

  public function transfer_plan()
  {
    $product = $this->site->getProductByCode('TN615/616/612K');
    $warehouse = $this->site->getWarehouseByCode('DUR');
    $opt = getPastMonthPeriod(1);
    $sold_items = $this->site->getSoldItemsByWarehouseID($warehouse->id, $opt);

    if ($sold_items) {
      foreach ($sold_items as $item) {
        if ($item->product_id == $product->id) {
        }
      }
    }
  }

  public function settings()
  {
    var_dump($this->SettingsJSON);
  }

  public function site_method()
  {
    $site = new ReflectionClass('Site');
    $methods = $site->getMethods();
    foreach ($methods as $method) {
      dbgprint($method->name);
    }
  }

  public function split_column()
  {
    $s = "sale_items.id as id,
    sales.date as date,
    JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.due_date')) AS due_date,
    TIMEDIFF(JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.due_date')), sales.date) AS duration,
    TIMEDIFF(
      JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.due_date')),
      CASE
        WHEN
          JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.completed_at')) IS NOT NULL AND
          JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.completed_at')) NOT LIKE ''
        THEN
          JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.completed_at'))
        ELSE
          DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')
      END
    ) AS time_left,
    sales.reference as reference,
    CONCAT(users.first_name, ' ', users.last_name) AS operator_name,
    sales.biller AS biller_name,
    warehouses.name as warehouse_name,
    IF(
      customers.company IS NOT NULL AND customers.company NOT LIKE '',
      CONCAT(customers.name, ' (', customers.company, ')'),
      customers.name
    ) as customer,
    sale_items.product_code as product_code,
    sale_items.product_name as product_name,
    sale_items.json_data->>'$.status' as item_status";
    echo $s . '<br>';
    function getColumns($columns)
    {
      $len = strlen($columns);
      $brackets = 0;
      $res = [];
      $word = '';

      if ($len > 0) {
        for ($a = 0; $a < $len; $a++) {
          $char = substr($columns, $a, 1);

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

    dbgprint(getColumns($s));
  }

  public function supplier()
  {
    $supplier = $this->site->getSupplierByCompanyName('Agung Rainbow');
    dbgprint($supplier);
  }

  public function sync_product()
  {
    $items = $this->site->getAllProducts(['type' => ['service', 'standard']]);
    if ($items) {
      foreach ($items as $item) {
        $this->site->syncProductQty($item->id);
      }
      return TRUE;
    }
    return FALSE;
  }

  public function sync_product_report()
  {
    if ($this->site->syncProductReports()) {
      echo "SUCCESS";
    } else {
      echo "FAILED";
    }
  }

  public function mark_on()
  {
    $r = getMarkonPrice(8200, 30);
    echo $r;
  }

  public function order_qty()
  {
    $r = getOrderStock(1000, 224, 603);
    d($r);
  }

  public function getsolditems()
  {
    $warehouse = $this->site->getWarehouseByCode('DUR');
    $opt = getPastMonthPeriod(1);
    $r = $this->site->getSoldItemsByWarehouseID($warehouse->id, $opt);
    // d($r);
    dbgprint($r);
  }

  public function float_precision()
  {
    // $a= 0.1;
    // $b= 0.2;
    // $c= 0.3;
    // $d= 0.4;
    // $e= 0.5;
    // $f= 0.6;
    // $g= 0.7;
    // $h= 0.8;
    // $i= 0.9;
    // $j= 1.0;

    // $str = [
    //   'a' => filterQuantity($a),
    //   'b' => filterQuantity($b),
    //   'c' => filterQuantity($c),
    //   'd' => filterQuantity($d),
    //   'e' => filterQuantity($e),
    //   'f' => filterQuantity($f),
    //   'g' => filterQuantity($g),
    //   'h' => filterQuantity($h),
    //   'i' => filterQuantity($i),
    //   'j' => filterQuantity($j),
    // ];
    // $s = json_encode($str);
    // d(filterQuantity('1.5'));
    // d($s);
    // return true;

    $product = $this->site->getProductByCode('TN619-Y');
    $warehouse = $this->site->getWarehouseByCode('DUR');

    $clause = [
      'product_id' => $product->id,
      'warehouse_id' => $warehouse->id,
    ];

    $startDate = '2021-04-01';

    $beginStock = $this->site->getStockBeginningQuantity($clause, $startDate);

    d($beginStock);
    return true;

    $product = $this->site->getProductByCode('PL35');
    $warehouse = $this->site->getWarehouseByCode('TLO');
    $this->site->syncTransferSafetyStock($product->id, $warehouse->id);
  }

  public function callback()
  {
    if ($this->requestMethod == 'POST') {
      $data = file_get_contents('php://input');

      sendJSON(['success' => 1, 'message' => 'Success']);
    }

    http_response_code(405);
    sendJSON(['success' => 0, 'message' => 'Method Not Allowed']);
  }

  public function check_stock()
  {
    $product = $this->site->getProductByCode('PL35');
    $warehouse = $this->site->getWarehouseByCode('TLO');
    $opt = [
      'end_date' => date('Y-m-d H:i:s', strtotime('+1 day', strtotime('2021-03-02 23:59:59')))
    ];

    $qty = $this->site->getStockQuantity($product->id, $warehouse->id, $opt);

    d($qty);
  }

  public function timediff2()
  {
    $date = '2021-04-26 10:00:00';
    $item_due_date = '2021-04-26 12:00:00';
    $item_code = 'POFF280';

    if (strtotime('+2 hour', strtotime($date)) > strtotime($item_due_date)) {
      echo ("Produksi item {$item_code} minimal 2 jam.");
    } else {
      echo ("OK");
    }
  }

  public function timediff_()
  {
    $proc_date = '2021-04-27 13:30:00';
    $inv_date = new DateTime('2021-04-26 12:00:00');
    $due_date  = new DateTime('2021-04-27 12:30:00');
    $com_date = new DateTime($proc_date ?? date('Y-m-d H:i:s'));

    $invDiff = $inv_date->diff($due_date);
    $comDiff = $com_date->diff($due_date);

    echo ($invDiff->format('%r') . ($invDiff->d * 24 + $invDiff->h) . $invDiff->format(':%I:%S'));
    echo ('<br>');
    echo ($comDiff->format('%r') . ($comDiff->d * 24 + $comDiff->h) . $comDiff->format(':%I:%S'));
  }

  public function fix_expenses()
  {
    $expenses = $this->site->getAllExpenses();

    if ($expenses) {
      $data = [];

      foreach ($expenses as $expense) {
        $payments = $this->site->getPayments(['expense_id' => $expense->id]);

        if ($payments) {
          $paymentCount = count($payments);

          if ($paymentCount > 1) {
            $data[] = [
              'reference' => $expense->reference,
              'payments' => $payments
            ];
          }
        }
      }

      if ($data) {
        d($data);
      } else {
        d('No Duplicated');
      }
    }
  }

  public function fix_incomes()
  {
    $incomes = $this->site->getAllIncomes();

    if ($incomes) {
      $data = [];

      foreach ($incomes as $income) {
        $payments = $this->site->getPayments(['income_id' => $income->id]);

        if ($payments) {
          $paymentCount = count($payments);

          if ($paymentCount > 1) {
            $data[] = [
              'reference' => $income->reference,
              'payments' => $payments
            ];
          }
        }
      }

      if ($data) {
        d($data);
      } else {
        d('No Duplicated');
      }
    }
  }

  public function fix_purchases()
  {
    $this->db->where("reference = ''");

    $q = $this->db->get('payments');

    if ($q->num_rows() > 0) {
      $payments = $q->result();

      foreach ($payments as $payment) {
        if ($payment->purchase_id) {
          $purchase = $this->site->getStockPurchaseByID($payment->purchase_id);
          if ($purchase) {
            $this->db->update('payments', ['reference' => $purchase->reference], ['purchase_id' => $purchase->id]);
          }
        }
      }
    }

    echo ('OK x');
  }

  public function fix_purchase_supplier()
  {
    $purchases = $this->site->getAllStockPurchases();
    if ($purchases) {
      foreach ($purchases as $purchase) {
        $warehouse = $this->site->getWarehouseByID($purchase->warehouse_id);
        $data = [
          'warehouse_code' => $warehouse->code,
          'warehouse_name' => $warehouse->name
        ];

        $this->db->update('purchases', $data, ['id' => $purchase->id]);
      }
    }
    echo ('OK');
  }

  public function perm()
  {
    var_dump(getPermission('sales-skip_validation'));
  }

  public function price_ranges()
  {
    $product = $this->site->getProductByCode('W2PPOFF28');
    $warehouse = $this->site->getWarehouseByCode('DUR');

    $price = getProductPriceByQty(['product_id' => $product->id, 'price_group_id' => $warehouse->price_group_id, 6]);

    d($price);
  }

  public function safetystock2()
  {
    $r = getPastMonthPeriod(2);
    d($r);
  }

  public function sale_due_date()
  {
    addSaleDueDate(105651);
  }

  public function selfDestruct()
  {
  }

  public function sql_select()
  {
    $q = "sales.id AS id, CONCAT(first_name, ' ', last_name) AS creator, JSON_UNQUOTE(JSON_EXTRACT(sales.json_data, '$.no')) as nc, c.number";

    $r = getSQLSelects($q);

    d($r);
  }

  public function sync_bank()
  {
    if ($this->site->syncBankAmount()) {
      echo "SUCCESS";
    } else {
      echo "FAILED";
    }
  }

  public function sync_saleitems()
  {
    $sales = $this->site->getAllSales();

    if ($sales) {
      foreach ($sales as $sale) {
        $saleitems = $this->site->getSaleItemsBySaleID($sale->id);
        if ($saleitems) {
          foreach ($saleitems as $saleitem) {
            if ($saleitem->price > 0) continue;
            $this->site->updateSaleItem($saleitem->id, ['price' => $saleitem->unit_price]);
          }
        }
      }
      echo "OK";
    }
  }

  public function sync_ss()
  {
    $product = $this->site->getProductByCode('TN615/616/612Y');

    $this->site->syncProductSafetyStock($product->id, [
      'start_date' => '2021-01-01',
      'end_date' => '2021-02-28',
      'days' => 59
    ]);

    echo "OK";
  }

  public function w2p_dispatch()
  {
    $ref = getGET('invoice');

    $sale = $this->site->getSaleByReference($ref);

    if ($sale) {
      $r = dispatchW2PSale($sale->id);
      if (!$r) {
        die(getLastError());
      }
      die($r);
    }
    die('Sale is not valid.');
  }

  public function webinfo()
  {
    phpinfo();
  }

  public function week()
  {
    $r = getCurrentWeekOfMonth();
    var_dump($r);
  }

  public function xss()
  {
    //echo "Your name is " . ($_GET['name'] ?? 'Unknown');
  }
}
