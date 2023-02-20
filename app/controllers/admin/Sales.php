<?php defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};

class Sales extends MY_Controller
{
  protected $editMode;

  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // XSession::set('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');

      loginPage();
    }

    if (isset($this->Supplier) && $this->Supplier) {
      XSession::set('danger', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->load->library('form_validation');
    $this->digital_upload_path = 'files/';
    $this->upload_path         = 'assets/uploads/';
    $this->thumbs_path         = 'assets/uploads/thumbs/';
    $this->image_types         = 'gif|jpg|jpeg|png|tif';
    $this->digital_file_types  = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
    $this->allowed_file_size   = '1024';
    $this->data['logo']        = true;

    $this->editMode = 'edit';
  }

  /* ------------------------------------------------------------------ */

  public function actions()
  {
    if (!$this->isAdmin) {
      sendJSON(['error' => 1, 'msg' => lang('access_denied')]);
    }

    $action = getGET('form_action') ?? getPost('form_action');
    $vals   = getGET('val') ?? getPost('val');

    if ($action == 'delete' && $this->input->is_ajax_request()) {
      if (!empty($vals)) {
        $this->sma->checkPermissions('delete', TRUE, 'sales', TRUE);
        foreach ($vals as $id) {
          $sale = Sale::getRow(['id' => $id]);

          $firstMonthDate = strtotime(date('Y-m-') . '01 00:00:00');

          if ($firstMonthDate > strtotime($sale->date)) {
            sendJSON(['error' => 1, 'msg' => 'Invoice lama tidak bisa dihapus.']);
          }

          if ($sale && !$this->Owner) {
            if (isCompleted($sale->status)) {
              $msg = "Invoice {$sale->reference} gagal dihapus karena sudah atau sedang diproduksi.";

              addEvent($msg, 'warning');
              sendJSON([
                'error' => 1,
                'msg' => $msg
              ]);
            }
          }

          if (!Sale::delete(['id' => $id])) {
            sendJSON(['error' => 1, 'msg' => "Failed to delete sale id '{$id}'."]);
          }
        }
        sendJSON(['error' => 0, 'msg' => lang('sales_deleted')]);
      }
    } else if ($action == 'export_excel') {
      if (!empty($vals)) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle(lang('sales'));
        $excel->SetCellValue('A1', lang('date'));
        $excel->SetCellValue('B1', lang('reference'));
        $excel->SetCellValue('C1', lang('pic_username'));
        $excel->SetCellValue('D1', lang('pic_name'));
        $excel->SetCellValue('E1', lang('biller'));
        $excel->SetCellValue('F1', lang('warehouse'));
        $excel->SetCellValue('G1', lang('price_group'));
        $excel->SetCellValue('H1', lang('customer_group'));
        $excel->SetCellValue('I1', lang('customer'));
        $excel->SetCellValue('J1', lang('status'));
        $excel->SetCellValue('K1', lang('grand_total'));
        $excel->SetCellValue('L1', lang('paid'));
        $excel->SetCellValue('M1', lang('balance'));
        $excel->SetCellValue('N1', lang('payment_status'));

        $row = 2;
        foreach ($vals as $id) {
          $sale = $this->site->getSaleByID($id);
          $customer = $this->site->getCustomerByID($sale->customer_id);
          $user = $this->site->getUser($sale->created_by);
          $warehouse = $this->site->getWarehouseByID($sale->warehouse_id);
          $excel->SetCellValue('A' . $row, $this->sma->hrld($sale->date));
          $excel->SetCellValue('B' . $row, $sale->reference);
          $excel->SetCellValue('C' . $row, $user->username);
          $excel->SetCellValue('D' . $row, $user->fullname);
          $excel->SetCellValue('E' . $row, $sale->biller);
          $excel->SetCellValue('F' . $row, $warehouse->name);
          $excel->SetCellValue('G' . $row, $customer->price_group_name);
          $excel->SetCellValue('H' . $row, $customer->customer_group_name);
          $excel->SetCellValue('I' . $row, $sale->customer);
          $excel->SetCellValue('J' . $row, lang($sale->status));
          $excel->SetCellValue('K' . $row, strval($sale->grand_total));
          $excel->SetCellValue('L' . $row, strval($sale->paid));
          $excel->SetCellValue('M' . $row, strval($sale->grand_total - $sale->paid)); // Must STRING.
          $excel->SetCellValue('N' . $row, lang($sale->payment_status));
          $row++;
        }

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');
        $excel->setColumnAutoWidth('D');
        $excel->setColumnAutoWidth('E');
        $excel->setColumnAutoWidth('F');
        $excel->setColumnAutoWidth('G');
        $excel->setColumnAutoWidth('H');
        $excel->setColumnAutoWidth('I');
        $excel->setColumnAutoWidth('J');
        $excel->setColumnAutoWidth('K');
        $excel->setColumnAutoWidth('L');
        $excel->setColumnAutoWidth('M');
        $excel->setColumnAutoWidth('N');
        $filename = 'PrintERP - SalesList-' . date('Y_m_d_H_i_s');
        $excel->export($filename);
      }
    }
  }

  public function activate($id = null)
  {
    $sale = Sale::getRow(['id' => $id]);

    if (!$sale) {
      $this->response(404, ['message' => 'Sale is not found.']);
    }

    if ($sale->status != 'inactive') {
      Sale::update((int)$sale->id, ['status' => 'inactive']);

      $this->response(200, ['message' => 'Sale has been deactivated.']);
    } else {
      Sale::update((int)$sale->id, ['status' => 'need_payment']);
      Sale::sync(['id' => $sale->id]);

      $this->response(200, ['message' => 'Sale has been activated.']);
    }
  }

  public function add()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('sales-add')) {
      XSession::set('error', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    }

    $sale_id     = getGET('sale_id');
    $saleOptions = getGET('opt');

    $this->form_validation->set_rules('created_by', lang('created_by'), 'required');
    $this->form_validation->set_rules('customer', lang('customer'), 'required');
    $this->form_validation->set_rules('biller', lang('biller'), 'required');
    $this->form_validation->set_rules('status', lang('status'), 'required');
    $this->form_validation->set_rules('payment_status', lang('payment_status'), 'required');
    $this->form_validation->set_rules('warehouse', lang('warehouse'), 'required');

    if ($this->form_validation->run() == true) {
      // $no_attachment    = getPost('noattach');
      $approved         = (getPost('approved') == 1 ? 1 : 0);
      $saleOptions      = getPost('sale_options');
      $draft_type       = getPost('draft_type');
      $no_po            = getPost('no_po');
      $date             = $this->serverDateTime; // Using server time.
      $bank_transfer    = (getPost('bank_transfer') == 1 ? TRUE : FALSE);
      $created_by       = getPost('created_by');
      $cashier_by       = getPost('cashier_by');
      $warehouse_id     = getPost('warehouse');
      $customer_id      = getPost('customer');
      $biller_id        = getPost('biller');
      $status           = getPost('status');
      $payment_status   = getPost('payment_status');
      $payment_term     = getPost('payment_term');
      $customer         = $this->site->getCustomerByID($customer_id);
      $note             = htmlEncode(getPost('note', FALSE));
      $uriCallback      = getPost('uri_callback');
      $total            = 0;
      $i                = isset($_POST['product_code']) ? count($_POST['product_code']) : 0; // Size of ITEM inserted.

      $customerGroup     = $this->site->getCustomerGroupByCustomerID($customer->id);
      $isSpecialCustomer = isSpecialCustomer($customer->id);

      // If no customer registered. Then cancel add sale.
      if (empty($customer)) {
        XSession::set('error', 'Customer is not registered.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales/add');
      }

      // Overdate Protection.
      if (strtotime($date) > now()) {
        XSession::set('error', 'Do not try to cheating.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
      }

      // Double Sale Protection.
      $last_sale = $this->site->getLastSaleByCreatorID($created_by); // Is this creator has last sale.
      if ($last_sale) {
        $time_difference = now() - strtotime($last_sale->date);
        if ($time_difference < 30) { // If time difference between last sale and this sale is less than 30s then canceled.
          XSession::set('error', 'Anda punya invoice 30 detik yang lalu. Tunggu 30 detik lagi untuk buat nota baru.');
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        }
      }

      // Privilege customer/TOP customer can always producing item, so change status to always "waiting_production".
      if ($isSpecialCustomer) {
        $status = 'waiting_production';
      }

      // If draft type.
      if ($draft_type == 1) $status = 'draft';

      $sale_items = [];

      for ($r = 0; $r < $i; $r++) { // Products Iteration
        $item_id          = $_POST['product_id'][$r];
        $item_category    = $_POST['product_category'][$r];
        $item_type        = $_POST['product_type'][$r];
        $item_code        = $_POST['product_code'][$r];
        $unit_price       = filterDecimal($_POST['unit_price'][$r]);
        $item_quantity    = $_POST['quantity'][$r]; // Total Quantity
        $item_subquantity = $_POST['subquantity'][$r]; // Sub Quantity
        $item_spec        = $_POST['spec'][$r];
        $item_operator    = ($_POST['operator'][$r] ?? NULL);
        $item_status      = $status;

        if (empty($item_operator)) {
          XSession::set('error', 'Mohon masukkan Operator!');
          redirect($_SERVER['HTTP_REFERER']);
        }

        if ($item_category === 'DPI' && $item_type == 'combo') {
          $item_w = $_POST['w'][$r];
          $item_l = $_POST['l'][$r];
        } else {
          $item_w = 1;
          $item_l = 1;
        }

        if (isset($item_code) && isset($unit_price) && isset($item_subquantity)) {
          $sale_items[] = [
            'product_id'   => $item_id,
            'price'        => $unit_price,
            'quantity'     => $item_subquantity,
            'warehouse_id' => $warehouse_id,
            'width'        => $item_w,
            'length'       => $item_l,
            'spec'         => $item_spec,
            'status'       => $item_status,
            'operator_id'  => $item_operator,
          ];

          $total += round($unit_price * $item_quantity); // Used by transfer.
        }
      } // end for

      if (empty($sale_items)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $saleData = [
        'date'           => $date,
        'customer_id'    => $customer_id,
        'biller_id'      => $biller_id,
        'warehouse_id'   => $warehouse_id,
        'no_po'          => $no_po,
        'note'           => $note,
        'status'         => $status,
        'payment_status' => $payment_status,
        'payment_term'   => $payment_term,
        'created_by'     => $created_by,
        'cashier_by'     => $cashier_by,
        'source'         => 'PrintERP',
        'approved'       => $approved
      ];

      DB::transStart();

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Besar attachment tidak boleh lebih dari 2MB.');
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales/add');
        }

        $saleData['attachment'] = $uploader->storeRandom();
      } else if (!getPermission('sales-no_attachment')) {
        if ($customerGroup->name == 'TOP') { // Prevent CS create sale without attachment for Customer TOP.
          XSession::set('error', lang('top_no_attachment'));
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        } else if (XSession::get('group_name') == 'tl' && $saleOptions !== 'noattachment') {
          // If TL add sale not from counter. must include attachment.
          XSession::set('error', lang('attachment_required'));
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        }
      }
    }

    if ($this->form_validation->run()) {
      if ($sale_id = Sale::add($saleData, $sale_items)) {
        $sale = Sale::getRow(['id' => $sale_id]);

        $paymentDueDate = date('Y-m-d H:i:s', strtotime('+1 days')); // 1 day expired.

        if ($bank_transfer) { // Add new payment if bank_transfer selected.
          $pv_data = [
            'date'         => $date,
            'sale_id'      => $sale_id,
            'expired_date' => $paymentDueDate,
            'expired_at'   => $paymentDueDate,
            'reference'    => $sale->reference,
            'amount'       => $total,
            'created_by'   => $created_by
          ];

          if (PaymentValidation::add($pv_data)) {
            Sale::update((int)$sale_id, [
              'payment_due_date' => $paymentDueDate,
              'payment_status' => 'waiting_transfer'
            ]);

            $hMutex = mutexCreate('syncSales', TRUE);
            Sale::sync(['sale_id' => $sale_id]);
            mutexRelease($hMutex);
          }
        }

        if (!empty($customer->email)) { // If customer has email, then send invoice mail.
          // $this->email_sale($sale_id);
        }

        DB::transComplete();

        XSession::set('remove_slls', 1);

        if ($draft_type) {
          XSession::set('message', lang('draft_sale_saved'));
        } else {
          XSession::set('message', lang('sale_added'));
        }
      } else {
        XSession::set('error', 'Failed to add sale.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales/add');
      }

      redirect('admin/sales');
    } else {
      if ($sale_id) {
        if ($sale_id) {
          $items = $this->site->getSaleItemsBySaleID($sale_id);
        }
        krsort($items);
        $c = rand(100000, 9999999);
        foreach ($items as $item) {
          $row = $this->site->getProductByID($item->product_id);
          if (!$row) {
            $row = (object)[];
          } else {
            unset($row->cost);
          }
          $row->quantity = 0;
          /*$pis           = $this->site->getPurchasedItems($item->product_id, $item->warehouse_id, $item->option_id);
          if ($pis) {
            foreach ($pis as $pi) {
              $row->quantity += $pi->quantity_balance;
            }
          }*/
          $row->id              = $item->product_id;
          $row->code            = $item->product_code;
          $row->name            = $item->product_name;
          $row->type            = $item->product_type;
          $row->qty             = $item->quantity;
          $row->base_quantity   = $item->quantity;
          $row->base_unit       = isset($row->unit) ? $row->unit : NULL;
          $row->base_unit_price = isset($row->price) ? $row->price : $item->unit_price;
          $row->qty             = $item->quantity;
          $row->price           = filterDecimal($item->price);

          $combo_items = false;
          if ($row->type == 'combo') {
            $combo_items = $this->site->getProductComboItems($row->id, $item->warehouse_id);
          }

          $units    = $this->site->getUnitsByBUID($row->base_unit);
          $ri       = $this->Settings->item_addition ? $row->id : $c;

          $pr[$ri] = [
            'id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')',
            'row' => $row, 'combo_items' => $combo_items, 'units' => $units
          ];
          $c++;
        }
      }

      $this->data['error']      = (validation_errors() ? validation_errors() : XSession::get('error'));
      $this->data['units']      = $this->site->getAllBaseUnits();
      $this->data['sale_options'] = $saleOptions;
      $this->data['slnumber']    = ''; //$this->site->getReference('so');
      $this->data['payment_ref'] = ''; //$this->site->getReference('pay');
      $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('sales'), 'page' => lang('sales')], ['link' => '#', 'page' => lang('add_sale')]];
      $meta = ['page_title' => lang('add_sale'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('sales/add', $this->data);
    }
  }

  public function add_payment($sale_id = null) // Add Payment for Sale
  {
    if (XSession::get('biller_name') != 'Online') { // Online can always use transfer.
      // $this->sma->checkUserPermissions('sales-payments', 0, ['modal' => TRUE]); // New Check Permissions method.
    }

    if (getGET('sale_id')) {
      $sale_id = getGET('sale_id');
    }

    $hMutex = mutexCreate('syncSales', TRUE);
    Sale::sync(['sale_id' => $sale_id]);
    mutexRelease($hMutex);

    $sale = $this->site->getSaleByID($sale_id);

    if ($sale->payment_status == 'paid' && $sale->grand_total == $sale->paid) {
      XSession::set('error', lang('sale_already_paid'));
      $this->sma->md();
    }

    if ($sale->status == 'draft') {
      XSession::set('error', 'Sale is in Draft mode. Please save it as final format!');
      $this->sma->md();
    }

    $this->form_validation->set_rules('amount', lang('amount'), 'required');
    $this->form_validation->set_rules('payment_method', lang('payment_method'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');

    if ($this->form_validation->run() == true && $this->input->is_ajax_request()) {
      $bank_id                 = getPost('bank_id');
      $created_by              = getPost('created_by');
      $date                    = $this->serverDateTime;
      $payment_method          = getPost('payment_method');
      $skip_payment_validation = (getPost('skip_payment_validation') == 'true' ? TRUE : FALSE);
      $user                    = $this->site->getUserByID($created_by);
      $customer                = $this->site->getCustomerByID($sale->customer_id);

      $bank = $this->site->getBankByID($bank_id); // NULL if Transfer and Present if skip validation.

      if (!$user) { // Check if user present.
        sendJSON(['error' => 1, 'msg' => 'User not found.']);
      }

      if (!$customer) {
        sendJSON(['error' => 1, 'msg' => 'Customer not found.']);
      }

      // Double Payment Protection.
      $last_payment = $this->site->getLastPaymentBySaleID($sale_id); // Is this customer has latest payment?
      if ($last_payment) {
        $time_difference = time() - strtotime($last_payment->date);
        if ($time_difference < 30) { // If time difference between last payment and this payment is less than 30s then canceled.
          sendJSON(['error' => 1, 'msg' => 'Current sale is already has payment less than 30 seconds ago.']);
        }
      }

      $payment = [
        'date'       => $date,
        'sale_id'    => $sale_id,
        'amount'     => roundDecimal(getPost('amount')),
        'bank_id'    => $bank_id,
        'method'     => $payment_method, // Cash / EDC / Transfer
        'note'       => htmlEncode(getPost('note')),
        'created_by' => (!empty($created_by) ? $created_by : XSession::get('user_id')),
        'type'       => 'received' // Always received.
      ];

      if (($payment['amount'] + $sale->paid) > $sale->grand_total) {
        sendJSON(['error' => 1, 'msg' => 'Cannot pay more than grand total.']);
      }

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $payment['attachment'] = $uploader->storeRandom();
      }

      if ($payment['method'] == 'Transfer') { // Transfer will be validated automatically.
        if (empty($customer->payment_term)) $customer->payment_term = 1; // If empty customer payment term, set it to 1.
        $expiredDate = strtotime("+2 days", strtotime($date)); // Expired date always 2 days.

        $pvData = [
          'date'          => $date,
          'expired_date'  => date('Y-m-d H:i:s', $expiredDate),
          'expired_at'    => date('Y-m-d H:i:s', $expiredDate),
          'reference'     => $sale->reference,
          'sale_id'       => $payment['sale_id'],
          'amount'        => $payment['amount'],
          'created_by'    => $payment['created_by'],
          'biller_id'     => (isset($bank) ? $bank->biller_id : $sale->biller_id), // Do not change.
          'unique_code'   => (!empty(getPost('use_unique_code')) ? getPost('unique_code') : NULL)
        ];

        if (isset($payment['attachment'])) $pvData['attachment'] = $payment['attachment'];

        if ($pvId = PaymentValidation::add($pvData)) {
          $pv = PaymentValidation::getRow(['id' => $pvId]);

          Sale::update((int)$pvData['sale_id'], [
            'payment_status' => 'waiting_transfer',
            'attachment'     => ($payment['attachment'] ?? NULL)
          ]);

          $hMutex = mutexCreate('syncSales', TRUE);
          Sale::sync(['sale_id' => $pvData['sale_id']]);
          mutexRelease($hMutex);

          if ($skip_payment_validation) {
            $vpvData = (object)[
              'account_number' => $bank->number,
              'data_mutasi' => [
                (object)[
                  'transaction_date' => $date,
                  'type'             => 'CR',
                  'amount'           => floatval($pv->amount) + floatval($pv->unique_code),
                  'description'      => $payment['note']
                ]
              ]
            ];

            $vpvOpts = [
              'sale_id' => $sale_id,
              'manual'  => TRUE
            ];

            if (isset($payment['attachment_id']))  $vpvOpts['attachment_id'] = $payment['attachment_id'];
            if (isset($payment['attachment']))     $vpvOpts['attachment']    = $payment['attachment'];

            $ret = PaymentValidation::validate($vpvData, $vpvOpts);

            if ($ret) {
              sendJSON(['error' => 0, 'msg' => 'Manual payment validation has been added successfully.']);
            } else {
              sendJSON(['error' => 1, 'msg' => 'Manual payment validation has been failed to add.']);
            }
          }
          sendJSON(['error' => 0, 'msg' => 'Payment Validation berhasil ditambahkan.']);
        } else {
          sendJSON(['error' => 1, 'msg' => 'Payment Validation gagal ditambahkan']);
        }
      }
    } elseif (getPost('add_payment')) {
      sendJSON(['error' => 1, 'msg' => validation_errors()]);
    }

    if ($this->form_validation->run() == true) {
      if (Sale::addPayment($payment)) { // Add Sale payment.
        sendJSON(['error' => 0, 'msg' => lang('payment_added')]);
      } else {
        sendJSON(['error' => 1, 'msg' => 'Add Payment Failed']);
      }
    } else {
      if (getPost('add_payment')) {
        sendJSON(['error' => 1, 'msg' => 'Add Payment Failed']);
      }
      $paymentValidation = PaymentValidation::getRow(['sale_id' => $sale->id]);

      $this->data['customer']           = Customer::getRow(['id' => $sale->customer_id]);
      $this->data['payment_validation'] = ($paymentValidation ?? NULL);
      $this->data['waiting_transfer']   = ($sale->payment_status == 'waiting_transfer' ? 1 : 0);
      $this->data['inv']                = $sale;
      $this->data['settings_json']      = json_decode($this->site->get_setting()->settings_json);

      $this->load->view($this->theme . 'sales/add_payment', $this->data);
    }
  }

  public function approve($saleId = NULL)
  {
    if ($saleId) {
      if ($this->site->updateSale($saleId, ['approved' => 1])) {
        $this->response(200, ['message' => 'Nota berhasil di approve.']);
      }
    }
    $this->response(400, ['message' => 'Gagal menyetujui (approve) nota.']);
  }

  public function delete($id = null)
  {
    $this->sma->checkUserPermissions('sales-delete');

    if (getGET('id')) {
      $id = getGET('id');
    }

    $sale = $this->site->getSaleByID($id);

    $firstMonthDate = strtotime(date('Y-m-') . '01 00:00:00');

    if ($firstMonthDate > strtotime($sale->date)) {
      sendJSON(['success' => 0, 'message' => 'Invoice lama tidak bisa dihapus.']);
    }

    if ($sale && !$this->Owner) {
      if (isCompleted($sale->status)) {
        $msg = "Invoice {$sale->reference} gagal dihapus karena sudah atau sedang diproduksi.";

        addEvent($msg, 'warning');
        sendJSON([
          'success' => 0,
          'message' => $msg
        ]);
      }
    }

    if (Sale::delete(['id' => $id])) {
      PaymentValidation::delete(['sale_id' => $id]);
      Payment::delete(['sale_id' => $id]);
      Stock::delete(['sale_id' => $id]);

      if (isAJAX()) {
        sendJSON(['success' => 1, 'message' => lang('sale_deleted')]);
      }
      XSession::set('message', lang('sale_deleted'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    }
  }

  public function delete_payment($id = null)
  {
    if (!$this->Owner && !$this->Admin && !getPermission('sales-delete')) {
      XSession::set('error', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    }

    if (getGET('id')) {
      $id = getGET('id');
    }

    if ($this->site->deletePayment($id)) {
      XSession::set('message', lang('payment_deleted'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    } else {
      XSession::set('error', lang('payment_not_deleted'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    }
  }

  public function deletePayment()
  {
    if ($this->requestMethod == 'POST' && $this->input->is_ajax_request()) {
      $paymentId = getPost('id');

      $sale = $this->site->getSaleByPaymentID($paymentId);

      if ($this->site->deletePayment($paymentId) && $sale) {
        $hMutex = mutexCreate('syncSales', TRUE);
        Sale::sync(['sale_id' => $sale->id]);
        mutexRelease($hMutex);
        sendJSON(['error' => 0, 'msg' => 'Payment has been deleted successfully.']);
      }

      sendJSON(['error' => 1, 'msg' => 'Failed to delete payment.']);
    }
  }

  public function deleteSalesTBPayment()
  {
    if ($this->requestMethod == 'POST' && $this->input->is_ajax_request()) {
      $salesTBId = getPost('val');

      if ($salesTBId) {
        $success = 0;
        $failed = 0;

        foreach ($salesTBId as $saleTBId) {
          if ($this->site->deleteSaleTB($saleTBId)) {
            $success++;
          } else {
            $failed++;
          }
        }

        sendJSON(['error' => ($success ? 0 : 1), 'msg' => "Sale TB {$success} deleted and failed to delete {$failed}."]);
      } else {
        sendJSON(['error' => 1, 'msg' => 'No Sale TB selected.']);
      }
    }
  }

  public function details($sale_id)
  {
    $sale = $this->site->getSaleByID($sale_id);
    $this->data['sale'] = $sale;
    $this->data['sale_items'] = $this->site->getSaleItemsBySaleID($sale_id);
    $this->data['creator'] = $this->site->getUserByID($sale->created_by);
    $this->data['updater'] = ($sale->updated_by ? $this->site->getUserByID($sale->updated_by) : NULL);
    $this->data['payments'] = $this->site->getSalePayments($sale_id);
    $this->load->view($this->theme . 'sales/details', $this->data);
  }

  public function discpay()
  {
    checkPermission('sales-add_discount_payment');

    if ($this->requestMethod == 'POST') {
      $vals = getPost('val'); // val[]
      $disc = getPost('discount'); // 10
      $bankId = getPost('bank'); // bank_id

      if ($vals && is_array($vals)) {
        $failed  = 0;
        $success = 0;

        if ($disc == 0) {
          sendJSON([
            'error' => 1, 'message' => 'Discount harus lebih dari 0%'
          ]);
        }

        foreach ($vals as $saleId) {
          $sale = $this->site->getSaleByID($saleId);

          if ($sale && $sale->payment_status != 'paid') {
            $discount = ($sale->grand_total * $disc * 0.01);
            $this->site->updateSale($sale->id, ['discount' => $discount]);
            $this->site->addSalePayment([
              'sale_id'    => $sale->id,
              'amount'     => $sale->grand_total - $discount,
              'method'     => 'Transfer',
              'bank_id'    => $bankId,
              'created_by' => XSession::get('user_id'),
              'type'       => 'received'
            ]);
            $hMutex = mutexCreate('syncSales', TRUE);
            Sale::sync(['sale_id' => $sale->id]);
            mutexRelease($hMutex);
            $success++;
          } else {
            $failed++;
          }
        }

        sendJSON([
          'error' => 0,
          'message' => "Sales berhasil didiskon dan dilunasi. Success {$success} sale and Failed {$failed} sale."
        ]);
      }

      sendJSON([
        'error' => 1,
        'message' => 'Tidak ada sales yang dipilih'
      ]);
    }

    $this->load->view($this->theme . 'sales/discpay', $this->data);
  }

  public function edit($id = null)
  {
    if (getGET('id')) {
      $id = getGET('id');
    }

    $sale = $this->site->getSaleByID($id);
    $saleJS = getJSON($sale->json_data);

    if (!$this->Owner && !$this->Admin) { // PERMISSIONS.
      if (
        $this->editMode == 'edit' && !getPermission('sales-edit') ||
        $this->editMode == 'operator' && !getPermission('sales-edit_operator')
      ) {
        if ($sale->status != 'draft' || $sale->created_by != XSession::get('user_id')) {
          XSession::set('error', lang('access_denied'));
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        }
      }
    }

    if ($this->requestMethod == 'POST') {
      $approved         = (getPost('approved') == 1 ? 1 : 0);
      $draft_type       = (getPost('draft_type') == 1 ? TRUE : FALSE);
      $reference        = getPost('reference');
      $no_po            = getPost('no_po');
      $date             = filterDateTime(getPost('date'));
      $discount         = getPost('discount');
      $warehouse_id     = getPost('warehouse');
      $customer_id      = getPost('customer');
      $created_by       = getPost('created_by');
      $biller_id        = getPost('biller');
      $status           = getPost('status');
      $payment_status   = getPost('payment_status');
      $payment_term     = getPost('payment_term');
      $payment_term     = (!empty($payment_term) ? $payment_term : 1); // Default to 1 if not set.
      $customer = $this->site->getCustomerByID($customer_id);
      $note             = htmlEncode(getPost('note', FALSE));
      $uriCallback      = getPost('uri_callback');

      $total            = 0;
      $i                = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;

      $customer_group_name = strtolower($customer->customer_group_name);

      // Overdate Protection.
      if (strtotime($date) > now()) {
        // XSession::set('error', 'DO NOT TRY TO CHEATING.');
        // redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
      }

      // If draft sale.
      if ($draft_type) {
        $status = 'draft';
      } else if ($status == 'draft') {
        $status = 'need_payment';
      }

      $dates = [];
      $saleItems = [];

      for ($r = 0; $r < $i; $r++) {
        $item_id            = $_POST['product_id'][$r];
        $item_category      = $_POST['product_category'][$r];
        $item_type          = $_POST['product_type'][$r];
        $item_code          = $_POST['product_code'][$r];
        $unit_price         = filterDecimal($_POST['unit_price'][$r]);
        $item_quantity      = $_POST['quantity'][$r];
        $item_subquantity   = $_POST['subquantity'][$r];
        $item_spec          = $_POST['spec'][$r];
        $item_operator      = $_POST['operator'][$r];
        $item_due_date      = filterDateTime($_POST['due_date'][$r] ?? NULL);
        $item_finished_qty  = $_POST['finished_qty'][$r];
        $item_status        = ($_POST['status'][$r] ?? NULL);
        $item_completed_at  = $_POST['completed_at'][$r];

        if (empty($item_due_date) && !empty($saleJS->est_complete_date)) {
          XSession::set('error', 'Mohon masukkan Due Date!');
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        }

        // If current sale status == preparing and changed to waiting production, add due date.
        if ($sale->status == 'preparing' && $status == 'waiting_production') {
          addSaleDueDate($sale->id);
        }

        if ($status != $item_status) { // If status changed, item status changed too.
          $item_status = $status;
        }

        if (!$this->Owner && !$this->Admin && empty($item_operator)) {
          XSession::set('error', 'Mohon masukkan Operator!');
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
        }

        // If category Design and type Service then always completed. Ex. JASA EDIT DESIGN
        if ($item_category == 'DES' && $item_type == 'service') {
          $item_status = 'completed';
        }

        if ($item_category === 'DPI' && $item_type == 'combo') {
          $item_w = $_POST['w'][$r];
          $item_l = $_POST['l'][$r];
        } else {
          $item_w = 1;
          $item_l = 1;
        }

        if (isset($item_code) && isset($unit_price) && isset($item_quantity)) {
          $saleItem = [
            'product_id'   => $item_id,
            'price'        => $unit_price,
            'quantity'     => $item_subquantity,
            'finished_qty' => $item_finished_qty,
            'width'        => $item_w,
            'length'       => $item_l,
            'spec'         => $item_spec,
            'status'       => (!empty($item_status) ? $item_status : $status),
            'operator_id'  => $item_operator,
            'completed_at' => $item_completed_at,
            'due_date'     => $item_due_date
          ];

          $dates[]      = $item_due_date;
          $saleItems[] = $saleItem;

          $total += roundDecimal($unit_price * $item_quantity);
        }
      }

      $saleData = [
        'date'           => ($date ?? $sale->date),
        'created_by'     => $created_by,
        'customer_id'    => $customer_id,
        'biller_id'      => $biller_id,
        'warehouse_id'   => $warehouse_id,
        'no_po'          => $no_po,
        'note'           => $note,
        'status'         => $status,
        'discount'       => $discount,
        'payment_status' => $payment_status,
        'updated_by'     => XSession::get('user_id'),
        'updated_at'     => date('Y-m-d H:i:s'),
        'approved'       => $approved
      ];

      if ($this->Owner && !empty($reference)) {
        $saleData['reference'] = $reference;
      }

      if ($dates) $saleData['est_complete_date'] = getLongestDateTime($dates);

      if ($this->Owner) {
        // d($saleData);
        // d($sale_items);
        // die();
      }

      DB::transStart();

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Besar attachment tidak boleh lebih dari 2MB.');
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales/add');
        }

        $saleData['attachment'] = $uploader->storeRandom();
      } else if (!$this->Owner && !$this->Admin) {
        // Prevent CS create sale without attachment for Customer TOP.
        if ($customer_group_name == 'top' && !$sale->attachment) {
          XSession::set('error', lang('top_no_attachment'));
          redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales/add');
        }
      }

      if (Sale::update((int)$id, $saleData, $saleItems)) {
        $hMutex = mutexCreate('syncSales', TRUE);
        Sale::sync(['sale_id' => $id]);
        mutexRelease($hMutex);

        XSession::set('remove_slls', 1);
        if ($draft_type) {
          XSession::set('message', lang('draft_sale_saved'));
        } else {
          XSession::set('message', lang('sale_edited'));
        }
      }

      DB::transComplete();

      redirect('admin/sales');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : XSession::get('error'));
      $sale = $this->site->getSaleByID($id);
      $customer = Customer::getRow(['id' => $sale->customer_id]);

      $this->data['inv'] = $sale;
      $this->data['saleJS'] = json_decode($sale->json_data);

      if ($this->Settings->disable_editing) {
        if ($this->data['inv']->date <= date('Y-m-d', strtotime('-' . $this->Settings->disable_editing . ' days'))) {
          XSession::set('error', sprintf(lang('sale_x_edited_older_than_x_days'), $this->Settings->disable_editing));
          redirect($_SERVER['HTTP_REFERER']);
        }
      }

      $sale_items = $this->site->getSaleItemsBySaleID($id);
      // krsort($inv_items);
      $pr = [];

      if ($sale_items) {
        foreach ($sale_items as $item) {
          $rand = mt_rand(10000, 99999);

          // $row = $this->site->getProductByID($item->product_id);
          $row = $this->site->getWarehouseProduct($item->product_id, $sale->warehouse_id);

          // Get Product Group Prices
          $pr_group_price = $this->site->getProductGroupPrice($item->product_id, $customer->price_group_id);

          if (!$row) {
            $row = new stdClass();
          } else {
            unset($row->cost, $row->details, $row->product_details, $row->image, $row->barcode_symbology, $row->cf1, $row->cf2, $row->cf3, $row->cf4, $row->cf5, $row->cf6, $row->supplier1price, $row->supplier2price, $row->cfsupplier3price, $row->supplier4price, $row->supplier5price, $row->supplier1, $row->supplier2, $row->supplier3, $row->supplier4, $row->supplier5, $row->supplier1_part_no, $row->supplier2_part_no, $row->supplier3_part_no, $row->supplier4_part_no, $row->supplier5_part_no);
          }

          $row->id              = $item->product_id;
          $row->code            = $item->product_code;
          $row->name            = $item->product_name;
          $row->type            = $item->product_type;
          $row->category_code   = $item->category_code;
          $row->base_quantity   = $item->quantity;
          $row->base_unit       = !empty($row->unit) ? $row->unit : NULL;
          $row->base_unit_price = !empty($row->price) ? $row->price : NULL;
          $row->qty             = $item->quantity;
          $row->quantity        = $item->quantity;
          $row->finished_qty    = $item->finished_qty;
          $row->price           = filterDecimal($item->price);
          $row->operators       = $this->site->getUsers(['warehouse_id' => $sale->warehouse_id]);

          if (!empty($pr_group_price) && ($row->type == 'combo' || $row->type == 'service')) {
            $row->price1          = filterDecimal($pr_group_price->price);
            $row->price2          = filterDecimal($pr_group_price->price2);
            $row->price3          = filterDecimal($pr_group_price->price3);
            $row->price4          = filterDecimal($pr_group_price->price4);
            $row->price5          = filterDecimal($pr_group_price->price5);
            $row->price6          = filterDecimal($pr_group_price->price6);
          }

          $row->price_ranges_value = $item->price_ranges_value; // Price Ranges Value

          $pprop = (json_decode($item->json_data) !== NULL ? json_decode($item->json_data) : NULL);

          if ($pprop) {
            $operatorPresent = FALSE;

            $row->w            = (isset($pprop->w)            ? $pprop->w            : 0);
            $row->l            = (isset($pprop->l)            ? $pprop->l            : 0);
            $row->area         = (isset($pprop->area)         ? $pprop->area         : 0);
            $row->sqty         = (isset($pprop->sqty)         ? $pprop->sqty         : 0);
            $row->spec         = (isset($pprop->spec)         ? $pprop->spec         : '');
            $row->status       = (isset($pprop->status)       ? $pprop->status       : $sale->status);
            $row->operator_id  = (isset($pprop->operator_id)  ? $pprop->operator_id  : '');
            $row->due_date     = (isset($pprop->due_date)     ? $pprop->due_date     : '');
            $row->completed_at = ($pprop->completed_at ?? $pprop->updated_at ?? '');

            // Check if current operator present on operator list.
            foreach ($row->operators as $ops) {
              if ($ops->id == $row->operator_id) {
                $operatorPresent = TRUE;
              }
            }

            $op = $this->site->getUserByID($row->operator_id);

            // If current operator doesn't exist, then add it.
            if (!$operatorPresent && $op) {
              $row->operators[] = $op;
            }

            unset($op);
          }

          $combo_items = false;
          if ($row->type == 'combo') {
            $combo_items = $this->site->getProductComboItems($row->id, $sale->warehouse_id);
            foreach ($combo_items as $combo_item) {
              $combo_item->quantity = $combo_item->qty * $item->quantity;
            }
          }
          $units    = !empty($row->base_unit) ? $this->site->getUnitsByBUID($row->base_unit) : null;
          $ri       = $this->Settings->item_addition ? $row->id : $rand;

          $pr[$ri] = [
            'id'          => $rand,
            'item_id'     => $row->id,
            'label'       => $row->name . ' (' . $row->code . ')',
            'row'         => $row,
            'combo_items' => $combo_items,
            'units'       => $units
          ];
        } // for
      }

      $this->data['inv_items'] = json_encode($pr);

      $this->data['id']        = $id;
      $this->data['billers']    = ($this->Owner || $this->Admin || !XSession::get('biller_id')) ? $this->site->getAllBillers() : null;
      $this->data['units']      = $this->site->getAllBaseUnits();
      $this->data['warehouses'] = $this->site->getWarehouses();
      $this->data['edit_mode']  = $this->editMode;

      $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('sales'), 'page' => lang('sales')], ['link' => '#', 'page' => lang('edit_sale')]];
      $meta = ['page_title' => lang('edit_sale'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('sales/edit', $this->data);
    }
  }

  public function edit_operator($sale_id)
  {
    $sale = $this->site->getSaleByID($sale_id);
    $user = $this->site->getUserByID($sale->created_by);

    if (!$this->Owner && !$this->Admin && $user->username != 'w2p') {
      XSession::set('error', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/sales');
    }

    $this->editMode = 'operator';
    $this->edit($sale_id);
  }

  public function edit_payment($payment_id = null)
  {
    $this->sma->checkPermissions('payments', true);

    if (getGET('id')) {
      $payment_id = getGET('id');
    }
    $payment = $this->site->getPaymentByID($payment_id);
    $sale = $this->site->getSaleByPaymentID($payment_id);

    if ($this->requestMethod == 'POST' && isAJAX()) {
      $bank_id                 = getPost('bank_id');
      $created_by              = getPost('created_by');
      $date                    = $this->sma->fld(getPost('date'));
      $payment_method          = getPost('payment_method');
      $skip_payment_validation = (getPost('skip_payment_validation') ? TRUE : FALSE);
      $user                    = $this->site->getUserByID($created_by);
      $customer                = $this->site->getCustomerByID($sale->customer_id);

      if (!$user) {
        sendJSON(['error' => 1, 'msg' => 'User not found.']);
      }

      if (!$customer) {
        sendJSON(['error' => 1, 'msg' => 'Customer not found.']);
      }

      $data_payment = [
        'date'          => $date,
        'sale_id'       => $sale->id,
        'reference'     => $sale->reference,
        'amount'        => roundDecimal(getPost('amount')),
        'bank_id'       => $bank_id,
        'method'        => $payment_method, // Cash / EDC / Transfer
        'note'          => $this->sma->clear_tags(getPost('note')),
        'created_by'    => ($created_by ?? XSession::get('user_id')),
        'type'          => 'received'
      ];

      if ($data_payment['method'] == 'Transfer' && !$skip_payment_validation) { // Transfer will be validated automatically.
        $customer->payment_term = (!empty($customer->payment_term) ? $customer->payment_term : 1);
        $expire_time = (60 * 60 * 24 * $customer->payment_term); // Expire time based on customer payment term in day. Default 1 day.

        $data = [
          'date'          => $date,
          'expired_date'  => date('Y-m-d H:i:s', strtotime($date) + $expire_time), // 5 jam
          'expired_at'    => date('Y-m-d H:i:s', strtotime($date) + $expire_time), // 5 jam
          'reference'     => $sale->reference,
          'sale_id'       => $data_payment['sale_id'],
          'amount'        => $data_payment['amount'],
          'created_by'    => $created_by,
          'warehouse_id'  => $user->warehouse_id
        ];

        if (PaymentValidation::add($data)) {
          $this->site->updateSale($data['sale_id'], [
            'payment_status' => 'waiting_transfer'
          ]);
          $hMutex = mutexCreate('syncSales', TRUE);
          Sale::sync(['sale_id' => $data['sale_id']]);
          mutexRelease($hMutex);
          sendJSON(['error' => 0, 'msg' => lang('payment_validation_added')]);
        } else {
          sendJSON(['error' => 1, 'msg' => lang('payment_validation_add_fail')]);
        }
      }

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $payment['attachment'] = $uploader->storeRandom();
      }
    } elseif (getPost('edit_payment')) {
      sendJSON(['error' => 1, 'msg' => validation_errors()]);
    }

    if ($this->requestMethod == 'POST' && isAJAX()) {
      if ($this->site->updatePayment($payment_id, $data_payment)) { // Sales Add Payment.
        $hMutex = mutexCreate('syncSales', TRUE);
        Sale::sync(['sale_id' => $sale->id]);
        mutexRelease($hMutex);
        sendJSON(['error' => 0, 'msg' => lang('payment_edited')]);
      } else {
        sendJSON(['error' => 1, 'msg' => 'Failed to update payment']);
      }
    } else {
      if (getPost('edit_payment')) {
        sendJSON(['error' => 1, 'msg' => 'Failed to update payment.']);
      }
      $payment_validation = $this->site->getPaymentValidationBySaleID($sale->id);

      $this->data['customer']           = $this->site->getCustomerByID($sale->customer_id);
      $this->data['payment_validation'] = ($payment_validation ?? NULL);
      $this->data['waiting_transfer']    = ($sale->payment_status == 'waiting_transfer' ? 1 : 0);
      $this->data['inv']                = $sale;
      $this->data['payment']            = $payment;
      $this->data['settings_json']      = json_decode($this->site->get_setting()->settings_json);

      $this->load->view($this->theme . 'sales/edit_payment', $this->data);
    }
  }

  public function getSales() // warehouse_id
  {
    $this->sma->checkUserPermissions('sales-index', 0, ['datatables' => TRUE]);

    $reference      = getGET('reference');
    $billers        = (getGET('billers') ?? []);
    $customer       = getGET('customer');
    $status         = getGET('status');
    $created_by     = getGET('created_by');
    $payment_status = getGET('payment_status');
    $tb_account     = getGET('tb_account');
    $warehouses     = (getGET('warehouses') ?? []);
    $start_date     = getGET('start_date');
    $end_date       = getGET('end_date');
    $group_by       = getGET('group_by')   ?? 'sale';
    $xls            = (getGET('xls') == 1 ? TRUE : FALSE);

    $startDate  = getGET('start_date');
    $endDate    = getGET('end_date');

    if (!$startDate && !$endDate) {
      $period = getLastMonthPeriod();

      $startDate  = $period['start_date'];
      $endDate    = $period['end_date'];
    }

    if (!$this->Owner && !$this->Admin && XSession::get('biller_id')) {
      $user = $this->site->getUserByID(XSession::get('user_id'));
      $userJS = getJSON($user->json_data);

      if (isset($userJS->biller_access)) { // Add new access to specified billers.
        foreach ($userJS->biller_access as $bill) {
          $billers[] = $bill;
        }
      }

      $biller_id = XSession::get('biller_id');
      $billers[] = $biller_id;
    }

    if (!$this->Owner && !$this->Admin && XSession::get('warehouse_id')) {
      $warehouse_id = XSession::get('warehouse_id');
      $warehouses[] = $warehouse_id;
    }

    $cancel_link       = anchor('admin/finances/validations/cancel/$2', '<i class="fad fa-fw fa-undo"></i> ' . lang('cancel_validation'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $manual_link       = anchor('admin/finances/validations/manual/$2', '<i class="fad fa-fw fa-check"></i> ' . lang('manual_validation'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-sm" data-target="#myModal"');
    $print_invoice     = anchor('admin/sales/modal_view/$1', '<i class="fad fa-fw fa-print"></i> ' . lang('print_invoice'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg no-modal-header" data-target="#myModal"');
    $print_suratjalan  = anchor('admin/sales/surat_jalan/$1', '<i class="fad fa-fw fa-print"></i> ' . lang('print_surat_jalan'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg no-modal-header" data-target="#myModal"');
    $duplicate_link    = anchor('admin/sales/add?sale_id=$1', '<i class="fad fa-fw fa-plus-circle"></i> ' . lang('duplicate_sale'));
    $payments_link     = anchor('admin/sales/payments/$1', '<i class="fad fa-fw fa-money-bill-alt"></i> ' . lang('view_payments'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg no-modal-header" data-target="#myModal"');
    $add_payment_link  = anchor('admin/sales/add_payment/$1', '<i class="fad fa-fw fa-money-bill-wave"></i> ' . lang('add_payment'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $edit_link         = anchor('admin/sales/edit/$1', '<i class="fad fa-fw fa-edit"></i> ' . lang('edit_sale'), 'class="sledit"');
    $revert_link       = anchor('#', '<i class="fad fa-fw fa-undo"></i> ' . lang('revert_sale'), 'class="slrevert" data-sale-id="$1"');
    $edit_op_link      = anchor('admin/sales/edit_operator/$1', '<i class="fad fa-fw fa-user-edit"></i> ' . lang('edit_operator'), 'class="sledit"');
    $delete_link       = '
      <a href="' . admin_url('sales/delete/$1') . '" data-action="confirm" data-message="Hapus nota?">
        <i class="fad fa-fw fa-trash"></i> ' . lang('delete_sale') . '
      </a>';
    $approve_link      = '
      <a href="' . admin_url('sales/approve/$1') . '" data-action="confirm"
        data-labels=\'{"ok":"Setuju","cancel":"Batal"}\'
        data-message="Memilih <b>Approved Sale</b> berarti bertanggung jawab ' .
      'jika item sudah di complete oleh operator tidak dapat dikembalikan lagi ' .
      'ke <b>Waiting Production</b>." data-title="Persetujuan / Consent">' .
      '<i class="fad fa-fw fa-check"></i> ' . lang('approved_sale') . '
      </a>';

    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
    <ul class="dropdown-menu dropdown-menu-right" role="menu">
      <li>' . $add_payment_link . '</li>';

    if ($this->Owner || $this->Admin) {
      $action .= '<li><a href="' . admin_url('sales/activate/$1') . '" data-action="confirm"
        data-message="Nonaktifkan / Aktifkan Sale?" data-title="Activate / Deactivate Sale">
        <i class="fad fa-fw fa-check"></i> Activate/Deactivate</a></li>';
    }

    if ($this->isAdmin || getPermission('sales-add')) {
      $action .= '<li>' . $approve_link . '</li>';
    }

    if ($this->Owner || $this->Admin || getPermission('validations-cancel')) {
      $action .= '<li>' . $cancel_link . '</li>';
    }

    if ($this->Owner || $this->Admin || getPermission('sales-delete')) {
      $action .= '<li>' . $delete_link . '</li>';
    }

    if ($this->Owner || $this->Admin || getPermission('sales-edit')) {
      $action .= '<li class="edit_$1">' . $edit_link . '</li>';
    }

    if ($this->Owner || $this->Admin || getPermission('sales-index')) { // Temporary sales-index, old sales-edit_operator.
      $action .= '<li>' . $edit_op_link . '</li>';
    }

    if ($this->Owner || $this->Admin || getPermission('validations-manual')) {
      $action .= '<li>' . $manual_link . '</li>';
    }

    $action .= '<li>' . $print_invoice . '</li>
      <li>' . $print_suratjalan . '</li>';

    if ($this->Owner || $this->Admin) {
      $action .= '<li>' . $revert_link . '</li>';
    }

    $action .= '<li>' . $payments_link . '</li>';

    $action .= '</ul>
      </div></div>';

    if (!$xls) { // WEB
      $query = '';

      if ($group_by == 'sale') {
        $query = "sales.id AS id, sales.date AS date, sales.reference,
        users.fullname AS pic_name, billers.name AS biller_name,
        warehouses.name AS warehouse_name, customers.customer_group_name AS customer_group,
        (CASE
          WHEN
            customers.company IS NOT NULL AND customers.company <> ''
          THEN CONCAT(customers.name, ' (', customers.company, ')')
          ELSE customers.name
        END) AS customer_name,
        sales.status, sales.grand_total, sales.paid,
        sales.balance AS balance, sales.payment_status, sales.attachment,
        pv.id as pv_id";
      } else
      if ($group_by == 'biller') {
        $query = "sales.id AS id, '-' AS date, '-' AS reference, '-' pic_name, billers.name AS biller_name,
        '-' AS warehouse_name, '-' AS customer_group, '-' AS customer_name, '-' AS status,
        SUM(sales.grand_total) AS grand_total, SUM(sales.paid) AS paid, SUM(sales.balance) AS balance,
        '-' AS payment_status, '-' AS attachment, '-' AS pv_id";
      } else
      if ($group_by == 'warehouse') {
        $query = "sales.id AS id, '-' AS date, '-' AS reference, '-' pic_name, '-' AS biller_name,
        warehouses.name AS warehouse_name, '-' AS customer_group, '-' AS customer_name, '-' AS status,
        SUM(sales.grand_total) AS grand_total, SUM(sales.paid) AS paid, SUM(sales.balance) AS balance,
        '-' AS payment_status, '-' AS attachment, '-' AS pv_id";
      }

      $hMutex = mutexCreate('Sales_getSales', TRUE);

      $this->load->library('datatables');
      $this->datatables
        ->select($query)
        ->from('sales')
        ->join('users', 'users.id=sales.created_by', 'left')
        ->join('billers', 'billers.id=sales.biller_id', 'left')
        ->join('customers', 'customers.id=sales.customer_id', 'left')
        ->join("(
            SELECT id, sale_id FROM payment_validations
            WHERE status LIKE 'pending' OR status LIKE 'expired'
            ORDER BY date DESC
            ) pv", 'pv.sale_id=sales.id', 'left')
        ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');

      if ($reference) {
        $this->datatables->like('sales.reference', $reference, 'both');
      }

      if ($billers || $warehouses) {
        $this->datatables->group_start();
        foreach ($billers as $biller) {
          $this->datatables->or_where('billers.id', $biller);
        }

        foreach ($warehouses as $warehouse) {
          $this->datatables->or_where('warehouses.id', $warehouse);
        }
        $this->datatables->group_end();
      }

      if ($customer) {
        $this->datatables->where('sales.customer_id', $customer);
      }
      if ($payment_status) {
        $this->datatables->like('sales.payment_status', $payment_status, 'none');
      }
      if ($status) {
        $this->datatables->like('sales.status', $status);
      }
      if ($created_by) {
        $this->datatables->where('sales.created_by', $created_by);
      }

      if ($tb_account) {
        $this->datatables->where('billers.name NOT LIKE warehouses.name');
      }

      if ($start_date) {
        $this->datatables->where("sales.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      } else { // For optimizing Query.
        // $period = getCurrentMonthPeriod();
        $period = getLastMonthPeriod();
        $this->datatables->where("sales.date BETWEEN '{$period['start_date']} 00:00:00' AND '{$period['end_date']} 23:59:59'");
      }

      if (getGET('attachment') == 'yes') {
        $this->datatables->where('sales.payment_status !=', 'paid')->where('attachment !=', null);
      }

      if (!empty($group_by)) {
        switch ($group_by) {
          case 'sale': {
              $this->datatables->group_by('sales.id');
            }
          case 'biller': {
              $this->datatables->group_by('sales.biller_id');
            }
        }
      }

      $this->datatables->add_column('Actions', $action, "id, pv_id");

      mutexRelease($hMutex);

      echo $this->datatables->generate();
    } else if ($xls) { // Export Excel
      $this->db
        ->select("sales.id AS id, sales.date AS date, sales.reference, users.username AS pic_username,
          users.fullname AS pic_name, billers.name AS biller_name,
          warehouses.name AS warehouse_name, customers.price_group_name AS price_group,
          customers.customer_group_name AS customer_group,
          (CASE
            WHEN
              customers.company IS NOT NULL AND customers.company <> ''
            THEN CONCAT(customers.name, ' (', customers.company, ')')
            ELSE customers.name
          END) AS customer_name,
          sales.status, sales.grand_total, sales.paid,
          (CASE
            WHEN
              customers.customer_group_name LIKE 'top' OR
              customers.customer_group_name LIKE 'privilege' OR
              sales.payment_status LIKE 'paid' OR
              sales.payment_status LIKE 'partial' OR
              sales.payment_status LIKE 'due_partial'
              THEN (sales.grand_total - sales.paid)
            ELSE 0
          END) AS balance, sales.payment_status, sales.attachment,
          pv.id as pv_id")
        ->from('sales')
        ->join('users', 'users.id=sales.created_by', 'left')
        ->join('billers', 'billers.id=sales.biller_id', 'left')
        ->join('customers', 'customers.id=sales.customer_id', 'left')
        ->join("(
            SELECT id, sale_id FROM payment_validations
            WHERE status LIKE 'pending' OR status LIKE 'expired'
            GROUP BY sale_id ORDER BY date DESC
            ) pv", 'pv.sale_id=sales.id', 'left')
        ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left');

      if ($reference) {
        $this->db->like('sales.reference', $reference, 'both');
      }

      if ($billers || $warehouses) {
        $this->db->group_start();
        foreach ($billers as $biller) {
          $this->db->or_where('billers.id', $biller);
        }

        foreach ($warehouses as $warehouse) {
          $this->db->or_where('warehouses.id', $warehouse);
        }
        $this->db->group_end();
      }

      if ($customer) {
        $this->db->where('sales.customer_id', $customer);
      }
      if ($payment_status) {
        $this->db->like('sales.payment_status', $payment_status, 'none');
      }
      if ($status) {
        $this->db->like('sales.status', $status);
      }
      if ($created_by) {
        $this->db->where('sales.created_by', $created_by);
      }

      if ($tb_account) {
        $this->db->where('billers.name NOT LIKE warehouses.name');
      }

      if ($start_date) {
        $start_date = $start_date . ' 00:00:00';
        $end_date   = $end_date . ' 23:59:59';
        $this->db->where("sales.date BETWEEN '{$start_date}' AND '{$end_date}'");
      } else {
        // $period = getCurrentMonthPeriod();
        $period = getLastMonthPeriod();
        $start_date = $period['start_date'] . ' 00:00:00';
        $end_date   = $period['end_date'] . ' 23:59:59';
        $this->db->where("sales.date BETWEEN '{$start_date}' AND '{$end_date}'");
      }

      if (getGET('attachment') == 'yes') {
        $this->db->where('sales.payment_status !=', 'paid')->where('attachment !=', null);
      }

      if (!$this->Customer && !$this->Supplier && !$this->Owner && !$this->Admin && !XSession::get('view_right')) {
        $this->db->where('sales.created_by', XSession::get('user_id'));
      } elseif ($this->Customer) {
        $this->db->where('sales.customer_id', XSession::get('user_id'));
      }

      $this->db->order_by('sales.id', 'DESC');

      $q = $this->db->get();

      if ($q->num_rows() > 0) {
        $excel = $this->ridintek->spreadsheet();
        $reportDate = date('Y-m-d H:i:s');
        $rows  = $q->result();

        // SALES
        $excel->setTitle('Sales');

        $excel->setCellValue('A1', 'Sale ID');
        $excel->setCellValue('B1', 'Date');
        $excel->setCellValue('C1', 'Reference');
        $excel->setCellValue('D1', 'PIC ID');
        $excel->setCellValue('E1', 'PIC Name');
        $excel->setCellValue('F1', 'Biller');
        $excel->setCellValue('G1', 'Warehouse');
        $excel->setCellValue('H1', 'Price Group');
        $excel->setCellValue('I1', 'Customer Group');
        $excel->setCellValue('J1', 'Customer Name');
        $excel->setCellValue('K1', 'Sale Status');
        $excel->setCellValue('L1', 'Grand Total');
        $excel->setCellValue('M1', 'Paid');
        $excel->setCellValue('N1', 'Balance');
        $excel->setCellValue('O1', 'Payment Status');
        $excel->setBold('A1:O1');
        $excel->setFillColor('A1:O1', 'FFFF00');
        $excel->setHorizontalAlign('A1:O1', Alignment::HORIZONTAL_CENTER);

        // SALE ITEMS
        $excel->createSheet();
        $excel->setTitle('Sale Items');

        $excel->setCellvalue('A1', 'Sale Item ID');
        $excel->setCellvalue('B1', 'Sale Reference');
        $excel->setCellvalue('C1', 'Invoice Date');
        $excel->setCellvalue('D1', 'Due Date');
        $excel->setCellvalue('E1', 'Complete Date');
        $excel->setCellvalue('F1', 'Duration');
        $excel->setCellvalue('G1', 'Time Left');
        $excel->setCellvalue('H1', 'Production Status');
        $excel->setCellvalue('I1', 'Username');
        $excel->setCellvalue('J1', 'Operator');
        $excel->setCellvalue('K1', 'OP/NON OP');
        $excel->setCellvalue('L1', 'Biller');
        $excel->setCellvalue('M1', 'Warehouse');
        $excel->setCellvalue('N1', 'Customer');
        $excel->setCellvalue('O1', 'Product Code');
        $excel->setCellvalue('P1', 'Product Name');
        $excel->setCellvalue('Q1', 'Quantity');
        $excel->setCellvalue('R1', 'Subtotal');
        $excel->setCellvalue('S1', 'Item Status');
        $excel->setBold('A1:S1');
        $excel->setFillColor('A1:S1', 'FFFF00');
        $excel->setHorizontalAlign('A1:S1', Alignment::HORIZONTAL_CENTER);

        $a = 2;
        $b = 2;
        foreach ($rows as $sale) {
          // SALES
          $excel->getSheet(0); // Sales sheet.
          $excel->setCellValue('A' . $a, $sale->id);
          $excel->setCellValue('B' . $a, $sale->date);
          $excel->setCellValue('C' . $a, $sale->reference);
          $excel->setCellValue('D' . $a, strtoupper($sale->pic_username));
          $excel->setCellValue('E' . $a, $sale->pic_name);
          $excel->setCellValue('F' . $a, $sale->biller_name);
          $excel->setCellValue('G' . $a, $sale->warehouse_name);
          $excel->setCellValue('H' . $a, $sale->price_group);
          $excel->setCellValue('I' . $a, $sale->customer_group);
          $excel->setCellValue('J' . $a, $sale->customer_name);
          $excel->setCellValue('K' . $a, lang($sale->status));
          $excel->setCellValue('L' . $a, filterDecimal($sale->grand_total));
          $excel->setCellValue('M' . $a, filterDecimal($sale->paid));
          $excel->setCellValue('N' . $a, filterDecimal($sale->balance));
          $excel->setCellValue('O' . $a, lang($sale->payment_status));

          $sale_items = $this->site->getSaleItems(['sale_id' => $sale->id]);

          $excel->getSheet(1); // Sale Items sheet.

          foreach ($sale_items as $item) {
            $saleItemJS = getJSON($item->json_data);

            $currentDate  = new DateTime(); // NOW
            $createdDate  = new DateTime($sale->date);
            $completeDate = (!empty($saleItemJS->completed_at) ? new DateTime($saleItemJS->completed_at) : NULL);
            $dueDate      = (!empty($saleItemJS->due_date)    ? new DateTime($saleItemJS->due_date)    : NULL);
            $xDate        = ($completeDate ?? $currentDate);

            $duration = ($dueDate ? $createdDate->diff($dueDate)->format('%H:%I:%S') : '');
            $timeleft = ($dueDate ? $xDate->diff($dueDate)->format('%r%H:%I:%S') : '');
            $overdue  = ($dueDate && $xDate->diff($dueDate)->format('%r') == '-' ? 'OVER DUE' : '');

            $user = User::getRow(['id' => $saleItemJS->operator_id]);
            $op_username = ($user ? $user->username : '');
            $op_name     = ($user ? $user->fullname : '');
            $isOperator  = ($user && strcasecmp($user->groups, 'OPERATOR') === 0 ? TRUE : FALSE);

            $excel->setCellValue('A' . $b, $item->id);
            $excel->setCellValue('B' . $b, $sale->reference);
            $excel->setCellValue('C' . $b, $item->date);
            $excel->setCellValue('D' . $b, ($saleItemJS->due_date ?? ''));
            $excel->setCellValue('E' . $b, ($saleItemJS->completed_at ?? ''));
            $excel->setCellValue('F' . $b, $duration);
            $excel->setCellValue('G' . $b, ($saleItemJS->status != 'need_payment' && $saleItemJS->status != 'draft' ? $timeleft : ''));
            $excel->setCellValue('H' . $b, ($saleItemJS->status != 'need_payment' && $saleItemJS->status != 'draft' ? $overdue : ''));
            $excel->setCellValue('I' . $b, strtoupper($op_username));
            $excel->setCellValue('J' . $b, $op_name);
            $excel->setCellValue('K' . $b, ($isOperator ? 'OP' : 'NON OP'));
            $excel->setCellValue('L' . $b, $sale->biller_name);
            $excel->setCellValue('M' . $b, $sale->warehouse_name);
            $excel->setCellValue('N' . $b, $sale->customer_name);
            $excel->setCellValue('O' . $b, $item->product_code);
            $excel->setCellValue('P' . $b, $item->product_name);
            $excel->setCellValue('Q' . $b, $item->quantity);
            $excel->setCellValue('R' . $b, filterDecimal($item->subtotal));
            $excel->setCellValue('S' . $b, lang($saleItemJS->status));

            $b++;
          }

          $a++;
        }

        for ($sheet = 0; $sheet < 2; $sheet++) { // sheet 0, 1
          $excel->getSheet($sheet);
          $excel->setColumnAutoWidth('A');
          $excel->setColumnAutoWidth('B');
          $excel->setColumnAutoWidth('C');
          $excel->setColumnAutoWidth('D');
          $excel->setColumnAutoWidth('E');
          $excel->setColumnAutoWidth('F');
          $excel->setColumnAutoWidth('G');
          $excel->setColumnAutoWidth('H');
          $excel->setColumnAutoWidth('I');
          $excel->setColumnAutoWidth('J');
          $excel->setColumnAutoWidth('K');
          $excel->setColumnAutoWidth('L');
          $excel->setColumnAutoWidth('M');
          $excel->setColumnAutoWidth('N');
          $excel->setColumnAutoWidth('O');
          $excel->setColumnAutoWidth('P');
          $excel->setColumnAutoWidth('Q');
          $excel->setColumnAutoWidth('R');
        }

        $excel->export('PrintERP-Sales-' . date('Y_m_d'));
      }
    }
  }

  /**
   * NEW: get Sales Item.
   * Rewrite.
   */
  public function getSalesItem($warehouse_id = null)
  {
    $this->sma->checkPermissions('index');

    if ((!$this->Owner || !$this->Admin) && !$warehouse_id) {
      $user         = $this->site->getUser();
      $warehouse_id = $user->warehouse_id;
    }
    $detail_link       = anchor('admin/sales/view/$1', '<i class="fad fa-file-text"></i> ' . lang('sale_details'));
    $duplicate_link    = anchor('admin/sales/add?sale_id=$1', '<i class="fad fa-plus-circle"></i> ' . lang('duplicate_sale'));
    $payments_link     = anchor('admin/sales/payments/$1', '<i class="fad fa-money"></i> ' . lang('view_payments'), 'data-toggle="modal" data-target="#myModal"');
    $add_payment_link  = anchor('admin/sales/add_payment/$1', '<i class="fad fa-money"></i> ' . lang('add_payment'), 'data-toggle="modal" data-target="#myModal"');
    $email_link        = anchor('admin/sales/email/$1', '<i class="fad fa-envelope"></i> ' . lang('email_sale'), 'data-toggle="modal" data-target="#myModal"');
    $edit_link         = anchor('admin/sales/edit/$1', '<i class="fad fa-edit"></i> ' . lang('edit_sale'), 'class="sledit"');
    $delete_link       = "<a href='#' class='po' title='<b>" . lang('delete_sale') . "</b>' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('sales/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i> "
      . lang('delete_sale') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
      <ul class="dropdown-menu dropdown-menu-right" role="menu">
        <!--<li>' . $detail_link . '</li>
        <li>' . $duplicate_link . '</li>
        <li>' . $payments_link . '</li>
        <li>' . $add_payment_link . '</li>
        <li>' . $edit_link . '</li>
        <li>' . $email_link . '</li>
        <li>' . $delete_link . '</li>-->
      </ul>
    </div></div>';
    //$action = '<div class="text-center">' . $detail_link . ' ' . $edit_link . ' ' . $email_link . ' ' . $delete_link . '</div>';

    $this->load->library('datatables');
    if ($warehouse_id) {
      $this->datatables
        ->select(
          "sale_items.id as id,
          sales.date as date,
          sales.reference as reference,
          sales.biller as biller,
          sales.customer_name as customer,
          sale_items.product_code as product_code,
          sale_items.product_name as product_name,
          sale_items.json_data as json_data,
          sales.payment_status as payment_status"
        )
        ->from('sale_items')
        ->join('sales', 'sale_items.sale_id=sales.id', 'left')
        ->where('sale_items.warehouse_id', $warehouse_id);
    } else {
      $this->datatables
        ->select(
          "sale_items.id as id,
          sales.date as date,
          sales.reference as reference,
          sales.biller as biller,
          sales.customer_name as customer,
          sale_items.product_code as product_code,
          sale_items.product_name as product_name,
          sale_items.json_data as json_data,
          sales.payment_status as payment_status"
        )
        ->from('sale_items')
        ->join('sales', 'sale_items.sale_id=sales.id', 'left');
    }

    $this->datatables->add_column('Actions', $action, 'id');
    echo $this->datatables->generate();
  }

  public function getSalesTBPayment()
  {
    $startDate = getGET('start_date');
    $endDate = getGET('end_date');

    $this->load->library('datatables');

    $this->datatables->select("sales_tb.id AS id, sales_tb.last_sync_date AS last_sync_date,
      from_biller.name AS biller_name, to_warehouse.name AS warehouse_name,
      sales_tb.start_date AS start_date, sales_tb.end_date AS end_date, sales_tb.amount AS amount,
      sales_tb.status AS status, users.fullname AS creator")
      ->join('billers AS from_biller', 'from_biller.id = sales_tb.from_biller_id', 'left')
      ->join('warehouses AS to_warehouse', 'to_warehouse.id = sales_tb.to_warehouse_id', 'left')
      ->join('users', 'users.id = sales_tb.created_by', 'left')
      ->from('sales_tb');

    if ($startDate) {
      $this->datatables->where("sales_tb.start_date >= '{$startDate} 00:00:00'");
    }

    if ($endDate) {
      $this->datatables->where("sales_tb.end_date <= '{$endDate} 23:59:59'");
    }

    echo $this->datatables->generate();
  }

  public function getSalesTB()
  {
    $startDate = getGET('start_date');
    $endDate = getGET('end_date');

    $this->load->library('datatables');

    $this->datatables->select("sales.id AS id, sales.last_sync_date AS last_sync_date,
      from_biller.name AS biller_name, to_warehouse.name AS warehouse_name,
      sales.start_date AS start_date, sales.end_date AS end_date, sales.amount AS amount,
      sales.status AS status, users.fullname AS creator")
      ->join('billers AS from_biller', 'from_biller.id = sales.from_biller_id', 'left')
      ->join('warehouses AS to_warehouse', 'to_warehouse.id = sales.to_warehouse_id', 'left')
      ->join('users', 'users.id = sales.created_by', 'left')
      ->from('sales');

    if ($startDate) {
      $this->datatables->where("sales.start_date >= '{$startDate} 00:00:00'");
    }

    if ($endDate) {
      $this->datatables->where("sales.end_date <= '{$endDate} 23:59:59'");
    }

    echo $this->datatables->generate();
  }

  public function biller($biller_id)
  { // Index by Biller
    $this->index($biller_id);
  }

  public function index()
  {
    $this->sma->checkPermissions('index', NULL, 'sales');

    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');

    $biller_id    = getGET('biller');
    $warehouse_id = getGET('warehouse');

    $this->data['biller'] = $this->site->getBillerByID($biller_id);

    if (!$this->Owner && !$this->Admin) {
      if (XSession::get('biller_id')) {
        $this->data['billers'] = [$biller_id];
      }

      if (XSession::get('warehouse_id')) {
        $this->data['warehouses'] = [$warehouse_id];
      }
    }

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('sales')]
    ];

    $meta = ['page_title' => lang('sales'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('sales/index', $this->data);
  }

  /**
   * NEW: Item Status
   */
  public function sales_item_status($warehouse_id = null)
  {
    $this->sma->checkPermissions('index');

    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');
    if ($this->Owner || $this->Admin || !XSession::get('warehouse_id')) {
      $this->data['warehouses']   = $this->site->getAllWarehouses();
      $this->data['warehouse_id'] = $warehouse_id;
      $this->data['warehouse']    = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : null;
    } else {
      $warehouse_id = XSession::get('warehouse_id');
      $this->data['warehouses']   = null;
      $this->data['warehouse_id'] = $warehouse_id;
      $this->data['warehouse']    = XSession::get('warehouse_id') ? $this->site->getWarehouseByID($warehouse_id) : null;
    }

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('sales_item_status')]];
    $meta = ['page_title' => lang('sales_item_status'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('sales/sales_item_status', $this->data);
  }

  // Sale pop up on operator.
  public function modal_status($id = null)
  {
    $this->sma->checkPermissions('index', true);

    if (getGET('id')) {
      $id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');
    $sale_item           = $this->site->getSaleItemByID($id);
    $inv                 = $this->site->getSaleByID($sale_item->sale_id);
    if (!XSession::get('view_right')) {
      $this->sma->view_rights($inv->created_by, true);
    }
    $this->data['payments']    = $this->site->getPaymentsBySaleID($inv->id);
    $this->data['customer']    = $this->site->getCustomerByID($inv->customer_id);
    $this->data['biller']      = $this->site->getBillerByID($inv->biller_id);
    $this->data['created_by']  = $this->site->getUser($inv->created_by);
    $this->data['updated_by']  = $inv->updated_by ? $this->site->getUser($inv->updated_by) : null;
    $this->data['warehouse']   = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv']         = $inv;
    $this->data['rows']        = $this->site->getSaleItemsBySaleID($sale_item->sale_id);
    $this->data['saleJS']      = json_decode($inv->json_data);
    $this->data['return_sale'] = NULL;
    $this->data['return_rows'] = NULL;

    $this->load->view($this->theme . 'sales/modal_view', $this->data);
  }

  public function modal_view($sale_id = null)
  {
    $this->sma->checkPermissions('index', true);

    if (getGET('id')) {
      $sale_id = getGET('id');
    }

    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');
    $inv = $this->site->getSaleByID($sale_id);

    if (!XSession::get('view_right')) {
      $this->sma->view_rights($inv->created_by, true);
    }

    $hMutex = mutexCreate('syncSales', TRUE);
    Sale::sync(['id' => $sale_id]);
    mutexRelease($hMutex);

    $this->data['payments']    = $this->site->getPaymentsBySaleID($sale_id);
    $this->data['customer']    = $this->site->getCustomerByID($inv->customer_id);
    $this->data['biller']      = $this->site->getBillerByID($inv->biller_id);
    $this->data['warehouse']   = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['created_by']  = $this->site->getUser($inv->created_by);
    $this->data['updated_by']  = $inv->updated_by ? $this->site->getUser($inv->updated_by) : null;
    $this->data['inv']         = $inv;
    $this->data['rows']        = $this->site->getSaleItemsBySaleID($sale_id);
    $this->data['saleJS']      = json_decode($inv->json_data);

    $this->data['return_sale'] = NULL;
    $this->data['return_rows'] = NULL;

    $this->load->view($this->theme . 'sales/modal_view', $this->data);
  }

  public function payment_note($id = null)
  {
    $this->sma->checkPermissions('payments', true);
    $payment = $this->site->getPaymentByID($id);

    $this->data['payment'] = $payment;

    if ($payment) {
      if ($payment->sale_id) {
        $inv = $this->site->getSaleByID($payment->sale_id);
        $this->data['biller']   = $this->site->getBillerByID($inv->biller_id);
        $this->data['customer'] = $this->site->getCustomerByID($inv->customer_id);
      } else if ($payment->expense_id) {
        $inv = $this->site->getExpenseByID($payment->expense_id);
        $this->data['biller']   = $this->site->getBillerByID($inv->biller_id);
        $this->data['customer'] = $this->site->getSupplierByID($inv->supplier_id);
      } else if ($payment->income_id) {
        $inv = $this->site->getIncomeByID($payment->income_id);
        $this->data['biller']   = $this->site->getBillerByID($inv->biller_id);
        $this->data['customer'] = NULL;
      } else if ($payment->transfer_id) {
        $inv = $this->site->getStockTransferByID($payment->transfer_id);
        $this->data['warehouse_from'] = $this->site->getWarehouseByID($inv->from_warehouse_id);
        $this->data['warehouse_to']   = $this->site->getWarehouseByID($inv->to_warehouse_id);
      } else if ($payment->mutation_id) {
        $inv = $this->site->getBankMutationByID($payment->mutation_id);
        $this->data['customer'] = $this->site->getBillerByID($inv->biller_id);
      }
    }
    $this->data['page_title'] = lang('payment_note');

    $this->load->view($this->theme . 'sales/payment_note', $this->data);
  }

  /* -------------------------------------------------------------------------------- */

  public function payments($id = null)
  {
    $this->sma->checkPermissions(false, true);
    $payments = $this->site->getPayments(['sale_id' => $id]);

    if ($payments) {
      for ($a = 0; $a < count($payments); $a++) {
        $user = $this->site->getUserByID($payments[$a]->created_by);

        if ($user) {
          $payments[$a]->creator = $user->fullname;
        } else {
          $payments[$a]->creator = '';
        }
      }
    }

    $this->data['payments'] = $payments;
    $this->data['inv']      = $this->site->getSaleByID($id);
    $this->load->view($this->theme . 'sales/payments', $this->data);
  }

  public function processSalesTBPayment()
  {
    if ($this->requestMethod == 'POST' && $this->input->is_ajax_request()) {
      $salesTBId = getPost('val');

      if ($salesTBId) {
        $success = 0;
        $failed = 0;

        foreach ($salesTBId as $saleTBId) {
          if ($this->site->addSaleTBPayment($saleTBId)) {
            $success++;
          } else {
            $failed++;
          }
        }

        $reason = (getLastError() ? ' ' . getLastError() : '');

        $msg = "Sales TB {$success} have been paid and {$failed} failed to pay.{$reason}";

        sendJSON(['error' => ($success ? 0 : 1), 'msg' => $msg]);
      } else {
        sendJSON(['error' => 1, 'msg' => 'No Sale TB selected.']);
      }
    }
  }

  /**
   * Revert completed / delivered status to waiting production.
   */
  public function revertSaleStatus()
  {
    $sale_id = getPost('sale');

    if (!$this->Owner && !$this->Admin) {
      sendJSON(['error' => 1, 'msg' => 'You do not have permissions.']);
    }

    if ($sale_id) {
      $sale = Sale::getRow(['id' => $sale_id]);

      $firstMonthDate = strtotime(date('Y-m-') . '01 00:00:00');
      $invDate = strtotime($sale->date);

      if (!$this->Owner && $firstMonthDate > $invDate) {
        sendJSON(['error' => 1, 'msg' => 'Invoice lama tidak bisa di revert.']);
      }

      if ($this->Owner || $sale && ($sale->status == 'completed' ||
        $sale->status == 'delivered' ||
        $sale->status == 'completed_partial'
      )) {
        $this->site->updateSale($sale_id, ['status' => 'waiting_production']);

        $saleItems = $this->site->getSaleItemsBySaleID($sale_id);

        if ($saleItems) {
          $saleItemData = [];

          foreach ($saleItems as $saleItem) {
            $saleItemData['finished_qty'] = 0;
            $saleItemData['status'] = 'waiting_production';
            $saleItemData['completed_at'] = '';

            $this->site->updateSaleItem($saleItem->id, $saleItemData);
            $this->site->deleteStockQuantity(['saleitem_id' => $saleItem->id]);
          }

          $hMutex = mutexCreate('syncSales', TRUE);
          Sale::sync(['sale_id' => $sale_id]);
          mutexRelease($hMutex);

          sendJSON(['error' => 0, 'msg' => "Sale '{$sale->reference}' has been reverted successfully."]);
        }
        sendJSON(['error' => 1, 'msg' => "Failed to revert sale '{$sale->reference}'. Why no items?"]);
      }
      sendJSON(['error' => 1, 'msg' => "Failed to revert sale id '{$sale_id}'."]);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to revert sale.']);
  }

  public function suggestions()
  {
    $term           = getGET('term', true);
    $use_standard   = (getGET('use_standard') == 1 ? TRUE : FALSE);
    $warehouse_id   = getGET('warehouse_id', true);
    $customer_id    = getGET('customer_id', true);
    $customer_group = NULL;

    if (strlen($term) < 1 || !$term) {
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
    }

    $analyzed  = $this->sma->analyze_term($term);
    $sr        = $analyzed['term'];

    $warehouse      = $this->site->getWarehouseByID($warehouse_id);
    $customer       = $this->site->getCustomerByID($customer_id);

    if ($customer) {
      $customer_group = $this->site->getCustomerGroupByID($customer->customer_group_id);
    }

    if (!$customer_group) { // If has no customer group.
      sendJSON([['id' => 0, 'label' => 'No customer group', 'value' => $term]]);
    }

    $products = $this->site->getSaleProductSuggestions($term, $use_standard, 0);

    if ($products) {
      $pr = [];
      $r = 0;

      foreach ($products as $product) {
        if (!isProductWarehouses($product->warehouses, $warehouse->name)) { // Filter products by assigned warehouses. See ridintek_helper.php
          continue;
        }

        $hash = generateUUID();

        $product->quantity        = 0;
        $product->qty             = 1;
        $product->sqty            = 1;
        $product->discount        = 0;

        $opt        = json_decode('{}');
        $opt->price = 0;
        $option_id  = false;

        $product->option = $option_id;
        $pis         = NULL; //$this->site->getPurchasedItems($row->id, $warehouse_id);

        if ($pis) {
          $product->quantity = 0;
          foreach ($pis as $pi) {
            $product->quantity += $pi->quantity_balance;
          }
        }

        if ($customer->price_group_id) { // If customer price group set. Priority then Warehouse zone.
          if ($pr_group_price = $this->site->getProductGroupPrice($product->id, $customer->price_group_id)) {
            $product->price  = $pr_group_price->price;
            if ($product->type == 'combo' || $product->type == 'service') {
              $product->price1 = $pr_group_price->price;
              $product->price2 = $pr_group_price->price2;
              $product->price3 = $pr_group_price->price3;
              $product->price4 = $pr_group_price->price4;
              $product->price5 = $pr_group_price->price5;
              $product->price6 = $pr_group_price->price6;
            }
          }
        } elseif ($warehouse->price_group_id) { // If warehouse price group set.
          if ($pr_group_price = $this->site->getProductGroupPrice($product->id, $warehouse->price_group_id)) {
            $product->price  = $pr_group_price->price;
            if ($product->type == 'combo' || $product->type == 'service') {
              $product->price1 = $pr_group_price->price;
              $product->price2 = $pr_group_price->price2;
              $product->price3 = $pr_group_price->price3;
              $product->price4 = $pr_group_price->price4;
              $product->price5 = $pr_group_price->price5;
              $product->price6 = $pr_group_price->price6;
            }
          }
        }

        if (($product->type == 'combo' || $product->type == 'service') && !isset($product->price1)) {
          sendJSON([
            [
              'id' => 0,
              'label' => "Warehouse or Customer doesn't have price group for {$product->code}. Please add price group on 'Settings > Warehouses'."
            ]
          ]);
        }

        $product->price = $product->price  + (($product->price  * $customer_group->percent) / 100);
        if ($product->type == 'combo' || $product->type == 'service') {
          $product->price1 = $product->price1 + (($product->price1 * $customer_group->percent) / 100);
          $product->price2 = $product->price2 + (($product->price2 * $customer_group->percent) / 100);
          $product->price3 = $product->price3 + (($product->price3 * $customer_group->percent) / 100);
          $product->price4 = $product->price4 + (($product->price4 * $customer_group->percent) / 100);
          $product->price5 = $product->price5 + (($product->price5 * $customer_group->percent) / 100);
          $product->price6 = $product->price6 + (($product->price6 * $customer_group->percent) / 100);
        }

        $product->base_quantity   = 1;
        $product->base_unit       = $product->unit;
        $product->base_unit_price = $product->price;
        $product->unit            = ($product->sale_unit ? $product->sale_unit : ($product->unit ? $product->unit : 0));
        $product->comment         = '';
        $product->spec            = ''; // Added.
        $product->finished_qty    = 0; // Added.
        $product->status          = ''; // Added.
        $product->operator_id     = ''; // Added.
        $product->due_date        = ''; // Added.
        $product->completed_at    = ''; // Added.
        $product->operators       = $this->site->getUsers(['warehouse_id' => $warehouse_id]);
        $combo_items = [];

        if ($product->type == 'combo') {
          $combo_items = $this->site->getProductComboItems($product->id, $warehouse_id);
        }

        $units    = $this->site->getUnitsByBUID($product->base_unit);

        $row = $product;

        $pr[] = [
          'id' => $hash, 'item_id' => $row->id, 'label' => '(' . $row->code . ') ' . $row->name, 'category' => $row->category_id,
          'row'     => $row, 'combo_items' => $combo_items, 'units' => $units
        ];
        $r++;
      }
      if (count($pr)) {
        sendJSON($pr);
      } else {
        sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
      }
    } else {
      sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
    }
  }

  public function surat_jalan($id = null)
  {
    $this->sma->checkPermissions('index', true);

    if (getGET('id')) {
      $id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');
    $inv                 = $this->site->getSaleByID($id);
    if (!XSession::get('view_right')) {
      $this->sma->view_rights($inv->created_by, true);
    }
    $this->data['payments']    = $this->site->getPaymentsBySaleID($id);
    $this->data['customer']    = $this->site->getCustomerByID($inv->customer_id);
    $this->data['biller']      = $this->site->getBillerByID($inv->biller_id);
    $this->data['created_by']  = $this->site->getUser($inv->created_by);
    $this->data['updated_by']  = $inv->updated_by ? $this->site->getUser($inv->updated_by) : null;
    $this->data['warehouse']   = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv']         = $inv;
    $this->data['rows']        = $this->site->getSaleItemsBySaleID($id);
    $this->data['return_sale'] = NULL;
    $this->data['return_rows'] = NULL;

    $this->load->view($this->theme . 'sales/surat_jalan', $this->data);
  }

  public function syncSales($sale_id = NULL)
  {
    $startDate = (!empty(getGET('start_date')) ? getGET('start_date') : date('Y-m-') . '01');
    $endDate   = (!empty(getGET('end_date')) ? getGET('end_date') : date('Y-m-d H:i:s'));

    if ($sale_id) {
      $sale = $this->site->getSaleByID($sale_id);

      $hMutex = mutexCreate('syncSales', TRUE);
      Sale::sync(['sale_id' => $sale->id]);
      mutexRelease($hMutex);
      sendJSON(['error' => 0, 'msg' => 'Sync sale success.']);
    } else {
      $sales = $this->site->getSales(['start_date' => $startDate, 'end_date' => $endDate]);

      $hMutex = mutexCreate('syncSales', TRUE);

      foreach ($sales as $sale) {
        Sale::sync(['sale_id' => $sale->id]);
      }

      mutexRelease($hMutex);

      sendJSON(['error' => 0, 'msg' => 'Sync sales success.']);
    }
  }

  /**
   * Sync sales TB Payment
   */
  public function syncSalesTBPayment()
  {
    $startDate = getGET('start_date');
    $endDate   = getGET('end_date');

    if ($startDate) $period['start_date'] = $startDate;
    if ($endDate)   $period['end_date']   = $endDate;

    $date   = date('Y-m-d H:i:s');
    $period = getCurrentMonthPeriod(['start_date' => $startDate, 'end_date' => $endDate]);

    $clause = [];

    $clause = ['use_tb' => 1];
    $clause = array_merge($clause, $period);

    $salesTB = $this->site->getSales($clause);

    if ($salesTB) {
      $billers = $this->site->getAllBillers();
      $warehouses = $this->site->getAllWarehouses();

      foreach ($billers as $biller) {
        foreach ($warehouses as $warehouse) {
          // If biller name is not same like warehouse name then it TB Broo!!!
          if (strcasecmp($biller->name, $warehouse->name) != 0) {
            $this->db
              ->select("SUM(sales.grand_total) AS amount")
              ->where("date BETWEEN '{$period['start_date']} 00:00:00' AND '{$period['end_date']} 23:59:59'");

            $q = $this->db->get_where('sales', ['biller_id' => $biller->id, 'warehouse_id' => $warehouse->id]);

            if ($q && $q->num_rows() > 0) {
              $amount = $q->row('amount');
              if ($amount > 0) {
                $saleTBData = [
                  'last_sync_date' => $date,
                  'from_biller_id' => $biller->id,
                  'to_warehouse_id' => $warehouse->id,
                  'start_date' => $period['start_date'],
                  'end_date' => $period['end_date'],
                  'amount' => $amount,
                  'status' => 'pending'
                ];

                $this->site->addSaleTB($saleTBData);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Sales TB Index
   */
  public function tb()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('sales'), 'page' => lang('sales')],
      ['link' => '#', 'page' => 'Sales TB']
    ];
    $meta = ['page_title' => 'Sales TB', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('sales/tb', $this->data);
  }

  public function tb_payment()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('sales'), 'page' => lang('sales')],
      ['link' => '#', 'page' => 'Sales TB Payment']
    ];
    $meta = ['page_title' => 'Sales TB Payment', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('sales/tb_payment', $this->data);
  }

  public function update_sales_status($id) // $id = sale_id // CS ONLY
  {
    $this->form_validation->set_rules('status', lang('status'), 'required');

    $hMutex = mutexCreate('syncSales', TRUE);
    Sale::sync(['sale_id' => $id]); // New added to sync sales.
    mutexRelease($hMutex);

    if ($this->requestMethod == 'POST') {
      $status = getPost('status');
      $note   = $this->sma->clear_tags(getPost('note'));
    } elseif (getPost('update')) {
      XSession::set('error', validation_errors());
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'sales');
    }

    if ($this->requestMethod == 'POST' && $this->site->updateSaleStatus($id, $status, $note)) {
      XSession::set('message', lang('status_updated'));
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'sales');
    } else {
      $inv                      = $this->site->getSaleByID($id);
      $this->data['inv']        = $inv;
      $this->data['customer']   = $this->site->getCustomerByID($inv->customer_id);
      $this->data['returned'] = false;

      $this->load->view($this->theme . 'sales/update_sales_status', $this->data);
    }
  }

  public function view($id = null)
  {
    $this->sma->checkPermissions('index');

    if (getGET('id')) {
      $id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : XSession::get('error');
    $inv                 = $this->site->getSaleByID($id);
    if (!XSession::get('view_right')) {
      $this->sma->view_rights($inv->created_by);
    }
    $this->data['barcode']     = "<img src='" . admin_url('products/gen_barcode/' . $inv->reference) . "' alt='" . $inv->reference . "' class='pull-left' />";
    $this->data['customer']    = $this->site->getCustomerByID($inv->customer_id);
    $this->data['payments']    = $this->site->getSalePayments($id);
    $this->data['biller']      = $this->site->getBillerByID($inv->biller_id);
    $this->data['created_by']  = $this->site->getUser($inv->created_by);
    $this->data['updated_by']  = $inv->updated_by ? $this->site->getUser($inv->updated_by) : null;
    $this->data['warehouse']   = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv']         = $inv;
    $this->data['rows']        = $this->site->getSaleItemsBySaleID($id);
    $this->data['return_sale'] = NULL;
    $this->data['return_rows'] = NULL;

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('sales'), 'page' => lang('sales')], ['link' => '#', 'page' => lang('view')]];
    $meta = ['page_title' => lang('view_sales_details'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('sales/view', $this->data);
  }
}
/* EOF */