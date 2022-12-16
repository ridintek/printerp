<?php defined('BASEPATH') or exit('No direct script access allowed');

class Operators extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // $this->session->set_userdata('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');
      loginPage();
    }

    $this->load->library('form_validation');
  }

  public function index()
  {
    admin_redirect('operators/orders');
  }

  public function email_sale_status($id, $item_status)
  {
    $sale = $this->site->getSaleByID($id);
    $customer = $this->site->getCustomerByID($sale->customer_id);
    if (!$customer->email) {
      return FALSE;
    }
    $data_mail = [
      'customer_name'   => $customer->name,
      'invoice_no'      => $sale->reference,
      'item_status'     => $item_status,
      'trackorder_link' => 'https://indoprinting.co.id/trackorder?inv=' . $sale->reference . '&phone=' . $customer->phone . '&submit=1'
    ];
    if ($this->sma->sendMail('sale_status', [
      'to' => $customer->email,
      'subject' => 'Indoprinting Invoice ' . $sale->reference . ' [' . $item_status . ']'
    ], $data_mail)) {
      return TRUE;
    }
    return FALSE;
  }

  public function deliverySales()
  {
    $vals = getPOST('val');

    if (!empty($vals) && is_array($vals)) {
      $success = 0;
      $failed = 0;

      foreach ($vals as $val) {
        $saleItems = $this->site->getSaleItems(['id' => $val]);

        if ($saleItems) {
          $sale = $this->site->getSaleByID($saleItems[0]->sale_id);

          if ($sale->status == 'finished') {
            $this->site->updateSaleStatus($sale->id, 'delivered');
            $success++;
          } else {
            $failed++;
          }
        }
      }

      if ($success) {
        sendJSON(['error' => 0, 'msg' => "{$success} sale berhasil di Delivered dan {$failed} sale gagal di Delivered."]);
      }
      if ($failed) {
        sendJSON(['error' => 1, 'msg' => "{$failed} sale gagal di Delivered. Pastikan semua item telah di Finished."]);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Pilih dahulu sebelum di Delivered.']);
  }

  public function finishSales()
  {
    $vals = getPOST('val');

    if (!empty($vals) && is_array($vals)) {
      $success = 0;
      $failed = 0;

      foreach ($vals as $val) {
        $saleItems = $this->site->getSaleItems(['id' => $val]);

        if ($saleItems) {
          $sale = $this->site->getSaleByID($saleItems[0]->sale_id);

          if ($sale->status == 'completed') {
            $this->site->updateSaleStatus($sale->id, 'finished');
            $success++;
          } else {
            $failed++;
          }
        }
      }

      if ($success) {
        sendJSON(['error' => 0, 'msg' => "{$success} sale berhasil di Finish dan {$failed} sale gagal di Finish."]);
      }
      if ($failed) {
        sendJSON(['error' => 1, 'msg' => "{$failed} sale gagal di Finish. Pastikan semua item telah di Completed."]);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Pilih dahulu sebelum di Finish.']);
  }

  public function getItemsStatus()
  {
    $this->sma->checkPermissions('orders', NULL, 'operators', TRUE);
    $this->form_validation->set_rules('product_ids', 'Product IDs', 'required');

    $product_ids = json_decode(getPOST('product_ids'), TRUE); // as array

    if ($this->form_validation->run() && !empty($product_ids)) {
      $data_items = [];
      $ostatus = NULL;
      foreach ($product_ids as $product_id) {
        $item = $this->site->getSaleItemByID($product_id);
        if ($item) {
          $status = json_decode($item->json_data)->status;
          if ($status != 'waiting_production' && $status != 'completed_partial' && $status != 'in_production')
            sendJSON(['error' => 1, 'msg' => 'Status harus ' . lang('waiting_production') . ' atau ' . lang('completed_partial')]);
          if ($ostatus && $ostatus != $status) sendJSON(['error' => 1, 'msg' => 'Status tidak sama! Silakan pilih status yang sama.']);
          $ostatus = $status;
          $data_items[] = [
            'id' => $item->id,
            'code' => $item->product_code,
            'name' => $item->product_name,
            'quantity' => filterDecimal($item->quantity),
            'finished_qty' => filterDecimal($item->finished_qty),
            'process_qty' => filterDecimal($item->quantity - $item->finished_qty),
            'status' => $status
          ];
        }
      }
      sendJSON(['error' => 0, 'msg' => 'success', 'data' => $data_items]);
    } else {
      sendJSON(['error' => 1, 'msg' => 'Pilih status ' . lang('waiting_production') . ' atau ' . lang('completed_partial')]);
    }
  }

  public function getOrderedItems()
  { // Get ordered items based on Sale Items.
    $warehouses = [];

    $this->sma->checkPermissions('orders', null, 'operators');

    $created_by   = getGET('created_by');
    $customer     = getGET('customer');
    $item_status  = getGET('item_status');
    $start_date   = getGET('start_date');
    $end_date     = getGET('end_date');
    $warehouses   = getGET('warehouses');

    $warehouse_id = XSession::get('warehouse_id');


    $this->load->library('datatables');

    $this->datatables
      ->select(
        "sale_items.id as id,
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
        users.fullname AS operator_name,
        sales.biller AS biller_name,
        warehouses.name as warehouse_name,
        IF(
          customers.company IS NOT NULL AND customers.company NOT LIKE '',
          CONCAT(customers.name, ' (', customers.company, ')'),
          customers.name
        ) as customer,
        sale_items.product_code as product_code,
        sale_items.product_name as product_name,
        JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, '$.status')) as item_status"
      )
      ->from('sale_items')
      ->join('sales', 'sale_items.sale_id = sales.id', 'left')
      ->join('customers', 'customers.id = sales.customer_id', 'left')
      ->join('warehouses', 'warehouses.id = sales.warehouse_id', 'left')
      ->join('users', 'users.id = JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, "$.operator_id"))', 'left');

    $this->datatables
      ->where("JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, \"$.status\")) IN ('waiting_production', 'completed', 'completed_partial', 'finished')");
    // DO NOT USE BELOW. DECREASING PERFORMANCE. USE ABOVE INSTEAD.
    // $this->datatables
    //   ->group_start()
    //     ->like("JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, \"$.status\"))", 'waiting_production', 'none')
    //     ->or_like("JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, \"$.status\"))", 'completed', 'none')
    //     ->or_like("JSON_UNQUOTE(JSON_EXTRACT(sale_items.json_data, \"$.status\"))", 'completed_partial', 'none')
    //   ->group_end();

    if ($warehouse_id) {
      $this->datatables->where('sales.warehouse_id', $warehouse_id);
    }

    if ($created_by) { // Operator ID
      // $this->datatables->where('sales.created_by', $created_by);
      $this->datatables->like('JSON_EXTRACT(sale_items.json_data, "$.operator_id")', $created_by);
    }

    if ($customer) {
      $this->datatables->where('sales.customer_id', $customer);
    }

    if ($item_status) {
      $this->datatables->like("JSON_EXTRACT(sale_items.json_data, '$.status')", $item_status);
    }

    if ($start_date) {
      $start_date = ($start_date ?? date('Y-m-') . '01');
      $end_date = ($end_date     ?? date('Y-m-d'));
      $this->datatables->where("sales.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
    } else {
      // $period = getCurrentMonthPeriod();
      $period = getLastMonthPeriod();
      $start_date = $period['start_date'];
      $end_date   = $period['end_date'];
      $this->datatables->where("sales.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
    }

    if ($warehouses) {
      $this->datatables->group_start();
      foreach ($warehouses as $warehouse_id) {
        $this->datatables->or_where('sales.warehouse_id', $warehouse_id);
      }
      $this->datatables->group_end();
    }

    echo $this->datatables->generate();
  }

  public function completeSaleItems()
  { // Complete sale items.
    if ($this->requestMethod == 'POST') {
      $items      = json_decode(getPOST('items'));
      $created_by = getPOST('created_by');
      $date       = getPOST('date');
      $_pg        = getPOST('_pg');

      $error = 0;
      $errorCount = 0;
      $responseMsg = '';
      $isCompleteOverTime = FALSE;

      if ($saleItem = $this->site->getSaleItemByID($items[0]->id)) {
        if ($sale = $this->site->getSaleByID($saleItem->sale_id)) {
          $saleJS = getJSON($sale->json_data);
          $saleItemJS = getJSON($sale->json_data);
          if (isset($saleJS->approved) && $saleJS->approved == 0) {
            sendJSON(['error' => 1, 'msg' => "Nota <b>{$sale->reference}</b> belum di approved."]);
          }
          if (strtotime($this->serverDateTime) > strtotime($saleItemJS->est_complete_date)) {
            $isCompleteOverTime = TRUE;
          }
        }
      }

      $hMutex = mutexCreate('Operators_completeSaleItems', TRUE); // Create mutex.

      foreach ($items as $item) {
        $saleItem = SaleItem::getRow(['id' => $item->id]);
        $saleItemJS = getJSON($saleItem->json_data);

        if ($_pg && $isCompleteOverTime) {
          $minutes = rand(10, (60 * 5)); // 10 minute to 5 hours
          $date = date('Y-m-d H:i:s', strtotime("-{$minutes} minute", strtotime($saleItemJS->due_date)));
        }

        $data = [];
        $data['created_by'] = $created_by;
        $data['created_at'] = ($this->isAdmin || ($_pg && $isCompleteOverTime) ? $date : date('Y-m-d H:i:s'));
        $data['quantity']   = $item->quantity;

        if (!SaleItem::complete((int)$item->id, $data)) {
          $errorCount++;
          $responseMsg .= "<span class=\"text-danger bold\">Failed</span> to complete item '{$saleItem->product_code}'.<br>";
        } else {
          $responseMsg .= "Item '{$saleItem->product_code}' has been completed.<br>";
        }
      }

      mutexRelease($hMutex); // Release mutex Operators_completeSaleItems.

      if ($errorCount == count($items)) $error = 1;

      sendJSON(['error' => $error, 'msg' => $responseMsg]);
    } else {
      if (getPOST('update')) {
        sendJSON(['error' => 1, 'msg' => validation_errors()]);
      }

      $this->load->view($this->theme . 'operators/completeItems', $this->data);
    }
  }

  public function orders($mode = NULL, $warehouse_id = NULL)
  {
    $this->sma->checkPermissions('orders', null, 'operators');

    $this->data['reference'] = getGET('reference');
    $this->data['warehouses'] = (getGET('warehouses') ?? []);
    $this->data['created_by'] = getGET('created_by');
    $this->data['customer'] = getGET('customer');
    $this->data['item_status'] = getGET('item_status');
    $this->data['payment_status'] = getGET('payment_status');
    $this->data['start_date'] = getGET('start_date');
    $this->data['end_date'] = getGET('end_date');

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('operators')],
      ['link' => '#', 'page' => lang('ordered_items')]
    ];

    $meta = ['page_title' => lang('ordered_items'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('operators/orders', $this->data);
  }
}
/* EOF */