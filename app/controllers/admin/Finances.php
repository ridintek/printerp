<?php defined('BASEPATH') or exit('No direct script access allowed');

use \PhpOffice\PhpSpreadsheet\Cell\DataType;

class Finances extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }

    if ($this->Supplier) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->load->helper('security');
    $this->load->library('form_validation');
  }

  public function index()
  {
    admin_redirect();
  }

  /**
   * BANKS
   */
  public function banks()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'biller' ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function banks_index()
  {
    $this->sma->checkPermissions('index', NULL, 'banks');

    $biller_id = getGET('biller') ?? $this->session->userdata('biller_id');

    if ($biller_id) {
      $this->data['biller']  = $this->site->getBillerByID($biller_id);
    }

    $this->data['bank_code']  = getGET('code');
    $this->data['biller_id']  = getGET('biller');
    $this->data['bank_name']  = getGET('name');
    $this->data['acc_holder'] = getGET('holder');
    $this->data['acc_no']     = getGET('no');
    $this->data['type']       = getGET('type');
    $this->data['start_date'] = getGET('start_date');
    $this->data['end_date']   = getGET('end_date');

    $bc   = [ // Breadcrumbs
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('bank_accounts_list')]
    ];

    $meta = ['page_title' => lang('bank_accounts_list'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/banks/index', $this->data);
  }

  private function banks_actions()
  {
    $action = getPOST('form_action');
    $error  = FALSE;
    $vals   = getPOST('val');
    if ($action == 'activate') {
      if ($vals) {
        foreach ($vals as $val) {
          if (!$this->site->bankActivate($val)) {
            $this->session->set_flashdata('error', lang('bank_activate_failed'));
            $error = TRUE;
            break;
          }
        }
        if (!$error) $this->session->set_flashdata('message', lang('bank_activated'));
      } else {
        $this->session->set_flashdata('error', lang('check_one'));
      }
    } else if ($action == 'deactivate') {
      if ($vals) {
        foreach ($vals as $val) {
          if (!$this->site->bankDeactivate($val)) {
            $this->session->set_flashdata('error', lang('bank_deactivate_failed'));
            $error = TRUE;
            break;
          }
        }
        if (!$error) $this->session->set_flashdata('message', lang('bank_deactivated'));
      } else {
        $this->session->set_flashdata('error', lang('check_one'));
      }
    } else if ($action == 'delete') {
      $this->session->set_flashdata('error', lang('function_disabled'));
    } else if ($action == 'export_excel') {
      $this->session->set_flashdata('warning', lang('function_underdevelopment'));
    }
    admin_redirect('finances/banks');
  }

  private function banks_activate($bank_id)
  {
    $this->form_validation->set_rules('confirm', lang('confirm'), 'required');

    $confirmed = (getPOST('confirm') == 1 ? TRUE : FALSE);

    if ($this->form_validation->run() == TRUE && $confirmed) {
      if ($this->site->bankActivate($bank_id)) {
        sendJSON(['error' => 0, 'msg' => lang('bank_activated')]);
      } else {
        sendJSON(['error' => 1, 'msg' => lang('bank_activate_failed')]);
      }
    } else if (getPOST('activate')) {
      sendJSON(['error' => 1, 'msg' => lang('bank_activate_failed')]);
    }

    $this->data['bank'] = $this->site->getBankByID($bank_id);
    $this->data['csrf'] = [
      'name' => $this->security->get_csrf_token_name(),
      'value' => $this->security->get_csrf_hash()
    ];

    $this->load->view($this->theme . 'finances/banks/activate', $this->data);
  }

  private function banks_add()
  { // banks
    $this->sma->checkPermissions('add', TRUE, 'banks');
    $this->form_validation->set_rules('name', lang('name'), 'required');
    $this->form_validation->set_rules('code', lang('code'), 'required');
    $this->form_validation->set_rules('type', lang('type'), 'required');
    if ($this->form_validation->run() == TRUE) {
      $data = [
        'code'      => getPOST('code'),
        'biller_id' => getPOST('biller_id'),
        'name'      => getPOST('name'),
        'holder'    => getPOST('holder'),
        'number'    => getPOST('number'),
        'type'      => getPOST('type'),
        'bic'       => getPOST('bic'),
        'active'    => 1
      ];
      if ($this->site->addBank($data)) {
        $this->session->set_flashdata('message', lang('bank_added'));
        admin_redirect('finances/banks');
      } else {
        $this->session->set_flashdata('error', lang('bank_add_failed'));
        admin_redirect('finances/banks');
      }
    } else if (getPOST('add_bank_account')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/banks');
    }
    $this->data['billers'] = $this->site->getAllBillers();
    $this->load->view($this->theme . 'finances/banks/add', $this->data);
  }

  private function banks_deactivate($bank_id)
  {
    $this->form_validation->set_rules('confirm', lang('confirm'), 'required');

    $confirmed = (getPOST('confirm') == 1 ? TRUE : FALSE);

    if ($this->form_validation->run() == TRUE && $confirmed) {
      if ($this->site->bankDeactivate($bank_id)) {
        sendJSON(['error' => 0, 'msg' => lang('bank_deactivated')]);
      } else {
        sendJSON(['error' => 1, 'msg' => lang('bank_deactivate_failed')]);
      }
    } else if (getPOST('deactivate')) {
      sendJSON(['error' => 1, 'msg' => lang('bank_deactivate_failed')]);
    }

    $this->data['bank'] = $this->site->getBankByID($bank_id);
    $this->data['csrf'] = [
      'name' => $this->security->get_csrf_token_name(),
      'value' => $this->security->get_csrf_hash()
    ];

    $this->load->view($this->theme . 'finances/banks/deactivate', $this->data);
  }

  private function banks_delete($bank_id)
  { // banks
    $bank_id = $bank_id;
    $this->sma->checkPermissions('delete', NULL, 'banks', TRUE);
    if (getGET('id')) {
      $bank_id = getGET('id');
    }
    if ($this->site->deleteBank($bank_id)) {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 0, 'msg' => lang('bank_deleted')]);
      }
      $this->session->set_flashdata('message', lang('bank_deleted'));
      admin_redirect('finances/banks');
    } else {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 1, 'msg' => lang('bank_delete_failed')]);
      }
      $this->session->set_flashdata('error', lang('bank_delete_failed'));
      admin_redirect('finances/banks');
    }
  }

  private function banks_edit($bank_id)
  { // banks
    $id = $bank_id;
    $this->sma->checkPermissions('edit', TRUE, 'banks');
    $this->form_validation->set_rules('name', lang('name'), 'required');
    $this->form_validation->set_rules('code', lang('code'), 'required');
    $this->form_validation->set_rules('type', lang('type'), 'required');
    if ($this->form_validation->run() == TRUE) {
      $data = [
        'code'      => getPOST('code'),
        'biller_id' => getPOST('biller_id'),
        'name'      => getPOST('name'),
        'number'    => getPOST('number'),
        'holder'    => getPOST('holder'),
        'type'      => getPOST('type'),
        'bic'       => getPOST('bic')
      ];
      if ($this->site->updateBank($id, $data)) {
        $this->session->set_flashdata('message', lang('bank_edited'));
        admin_redirect('finances/banks');
      } else {
        $this->session->set_flashdata('error', lang('bank_edit_failed'));
        admin_redirect('finances/banks');
      }
    }
    $this->data['bank'] = $this->site->getBankById($id);
    $this->data['billers'] = $this->site->getAllbillers();
    $this->load->view($this->theme . 'finances/banks/edit', $this->data);
  }

  private function banks_getBanks($biller_id = NULL)
  { // banks
    $this->sma->checkPermissions('index', TRUE, 'banks');
    $bank_code      = getGET('code') ?? NULL;
    $bank_name      = getGET('name') ?? NULL;
    $biller_id      = getGET('biller') ?? NULL;
    $account_holder = getGET('holder') ?? NULL;
    $account_no     = getGET('no') ?? NULL;
    $type           = getGET('type') ?? NULL;
    $startDate     = getGET('start_date') ?? NULL;
    $endDate       = getGET('end_date') ?? date('Y-m-d');

    $q = '';
    if ($startDate) $q .= '&start_date=' . $startDate;
    if ($endDate)   $q .= '&end_date=' . $endDate;

    $this->load->library('datatables');

    $links = [
      'history' => '<a href="' . admin_url('finances/banks/history?bank=$1' . $q) . '" data-toggle="modal" data-target="#myModal">
        <i class="fad fa-fw fa-history"></i> ' . lang('bank_history') . '</a>',
      'delete' => "<a href='#' class='po' title='<b>" . lang('delete_bank') . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('finances/banks/delete/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
        . lang('delete_bank') . '</a>',
      'edit' => '<a href="admin/finances/banks/edit/$1" data-toggle="modal" data-target="#myModal"><i class="fad fa-fw fa-edit"></i> ' . lang('edit_bank') . '</a>'
    ];
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
      <ul class="dropdown-menu pull-right" role="menu">
        <li>' . $links['history'] . '</li>
        <li>' . $links['delete'] . '</li>
        <li>' . $links['edit'] . '</li>
      </ul>
    </div></div>';
    // DO NOT USE '*' TO FETCH ALL COLUMNS !!! FILTERING WILL ERROR.
    $this->datatables
      ->select("banks.id as id, banks.code as code,
        billers.name as biller_name, banks.name as acc_name,
        banks.holder as acc_holder, banks.number as acc_number,
        banks.type, banks.bic as acc_bic,
        (COALESCE(pay_recv.total, 0) - COALESCE(pay_sent.total, 0)) AS payment_balance, banks.active as active")
      ->from('banks');

    // New method
    // if ($startDate && $endDate) {
    //   $this->datatables
    //     ->join("(SELECT bank_id, SUM(amount) as total FROM payments WHERE date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59' GROUP BY bank_id) AS payment", "banks.id=payment.bank_id", 'left');
    // } else {
    //   $this->datatables
    //     ->join("(SELECT bank_id, SUM(amount) as total FROM payments GROUP BY bank_id) AS payment", "banks.id=payment.bank_id", 'left');
    // }
    if ($startDate && $endDate) {
      $startDate = $startDate . ' 00:00:00';
      $endDate   = $endDate . ' 23:59:59';
      $this->datatables
        ->join("(SELECT bank_id, SUM(amount) as total FROM payments WHERE type LIKE 'received' AND date BETWEEN '{$startDate}' AND '{$endDate}' GROUP BY bank_id) AS pay_recv", "banks.id=pay_recv.bank_id", 'left')
        ->join("(SELECT bank_id, SUM(amount) as total FROM payments WHERE type LIKE 'sent' AND date BETWEEN '{$startDate}' AND '{$endDate}' GROUP BY bank_id) AS pay_sent", "banks.id=pay_sent.bank_id", 'left');
    } else {
      $this->datatables
        ->join("(SELECT bank_id, SUM(amount) as total FROM payments WHERE type LIKE 'received' GROUP BY bank_id) AS pay_recv", "banks.id=pay_recv.bank_id", 'left')
        ->join("(SELECT bank_id, SUM(amount) as total FROM payments WHERE type LIKE 'sent' GROUP BY bank_id) AS pay_sent", "banks.id=pay_sent.bank_id", 'left');
    }

    $this->datatables->join('billers', 'billers.id=banks.biller_id', 'left');

    if ($biller_id) {
      $this->datatables->where('banks.biller_id', $biller_id);
    }
    if ($bank_code) {
      $this->datatables->like('banks.code', $bank_code, 'both');
    }
    if ($bank_name) {
      $this->datatables->like('banks.name', $bank_name, 'both');
    }
    if ($account_holder) {
      $this->datatables->like('banks.holder', $account_holder, 'both');
    }
    if ($account_no) {
      $this->datatables->like('banks.number', $account_no, 'both');
    }
    if ($type) {
      $this->datatables->like('banks.type', $type, 'both');
    }

    $this->datatables->edit_column('active', '$1__$2', 'active, id');
    $this->datatables->add_column('Actions', $action, 'id');

    echo $this->datatables->generate();
  }

  private function banks_getHistories($bank_id)
  {
    $this->load->library('datatables');
    $this->datatables
      ->select("bank_histories.date as date, bank_histories.reference as reference,
        users.username as pic_id,
        users.fullname as pic_name,
        banks.name as bank_name, banks.number as account_no,
        bank_histories.type, increase, decrease, balance, description")
      ->from('bank_histories')
      ->join('banks', 'banks.id=bank_histories.bank_id', 'left')
      ->join('users', 'users.id=bank_histories.created_by', 'left')
      ->where('bank_histories.bank_id', $bank_id);

    echo $this->datatables->generate();
  }

  private function banks_history()
  {
    $bank_id      = getGET('bank');
    $startDate    = getGET('start_date') ?? date('Y-m-') . '01';
    $endDate      = getGET('end_date') ?? date('Y-m-d');
    $biller_id    = getGET('biller');
    $export_xls   = getGET('xls');

    $this->data['bank_id']    = $bank_id;
    $this->data['bank']       = $this->site->getBankByID($bank_id);
    $this->data['start_date'] = $startDate;
    $this->data['end_date']   = $endDate;
    $this->data['biller_id']  = $biller_id;
    $this->data['biller']     = $this->site->getBillerByID($biller_id);

    $clauses = [];
    $options = [];

    if ($bank_id)     $clauses['bank_id']    = $bank_id;
    if ($startDate)   $options['start_date'] = $startDate;
    if ($endDate)     $options['end_date']   = $endDate;
    if ($biller_id)   $clauses['biller_id']  = $biller_id;

    $options['order'] = 'ASC';

    $beginning_amount = $this->site->getPaymentBeginningAmount($clauses, $startDate);
    $row              = $this->site->getPayments($clauses, $options);

    $this->data['beginning_amount'] = $beginning_amount;
    $this->data['rows']             = $row;

    if ($export_xls && $export_xls == '1') {
      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('InventoryBalance');
      $excel->setCellValue('A1', 'Mohon maaf sedang maintenance');
      // PROGRESS
      $excel->export('InventoryBalanceHistory_' . date('Ymd_His'));
    }

    $this->load->view($this->theme . 'finances/banks/history', $this->data);
  }

  private function banks_import()
  { // banks
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        checkPath($this->upload_banks_path);

        $date = (getPOST('date') ?? date('Y-m-d H:i:s'));
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_banks_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/banks');
        }

        $csv = $this->upload->file_name;
        $arrResult = [];
        $handle    = fopen($this->upload_banks_path . $csv, 'r');

        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }

        $header_id = array_shift($arrResult);
        $titles    = array_shift($arrResult);
        $data_banks = [];
        $updated = 0;
        $keys    = ['no', 'use', 'code', 'biller_name', 'name', 'holder', 'number', 'type', 'bic', 'balance', 'active'];
        $csvs   = [];

        if ($header_id[0] != 'BKAC') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('finances/banks');
        }

        foreach ($arrResult as $value) {
          $csvs[] = array_combine($keys, $value);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $bank   = $this->site->getBank(['code' => trim($csv['code'])]);
          $biller = $this->site->getBillerByName(trim($csv['biller_name']));

          $bank_data = [
            'date'      => $date, // date for Payments.
            'code'      => trim($csv['code']),
            'biller_id' => $biller->id,
            'name'      => trim($csv['name']),
            'holder'    => trim($csv['holder']),
            'number'    => trim($csv['number']),
            'type'      => trim($csv['type']),
            'bic'       => trim($csv['bic']),
            'balance'   => filterDecimal($csv['balance']),
            'active'    => trim($csv['active'])
          ];

          if (!$bank && $biller) { // Add bank if bank_code is not exist.
            $data_banks[] = $bank_data;
          } else if ($bank && $biller) { // Update bank if bank_code is exist.
            $this->site->updateBank($bank->id, $bank_data);
            $updated++;
          }
        }
      }
    }

    if ($this->form_validation->run() == true) {
      if (!empty($data_banks)) {
        $this->site->addBanks($data_banks);
      }

      $this->session->set_flashdata('message', sprintf(lang('banks_added_success'), count($data_banks), $updated));
      admin_redirect('finances/banks');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->load->view($this->theme . 'finances/banks/import', $this->data);
    }
  }

  private function banks_syncBankAmount()
  {
    $vals = getPOST('val');

    if ($vals) {
      foreach ($vals as $val) {
        $this->site->syncBankAmount($val); // Bank ID.
      }
    } else {
      if (!$this->site->syncBankAmount()) { // Sync all banks.
        sendJSON(['error' => 0, 'msg' => 'Failed to synchronized Banks amount.']);
      }
    }

    sendJSON(['error' => 0, 'msg' => 'Banks amount have been synchronized successfully.']);
  }

  /**
   * EXPENSES
   */
  public function expenses()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'biller' ? 'index' : $params[0]); // If not param or param[0] == biller then index

    if (method_exists($this, $method)) {
      if (!empty($params)) array_shift($params); // Remove original method as param.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function expenses_index($biller_id = NULL)
  {
    $this->sma->checkPermissions('index', NULL, 'expenses');
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

    if ($biller_id) {
      $this->data['biller_id'] = $biller_id;
      $this->data['biller']    = $this->site->getBillerByID($biller_id);
    } else {
      $this->data['biller_id'] = NULL;
      $this->data['biller']    = NULL;
    }

    $this->data['billers'] = $this->site->getAllBillers();

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('expenses_list')]
    ];
    $meta = ['page_title' => lang('expenses'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/expenses/index', $this->data);
  }

  private function expenses_actions()
  { // expenses
    $form_action = getGET('form_action');

    if ($form_action == 'send_email') { // Send Email
      // if (empty($_GET['val'])) {
      //   $this->session->set_flashdata('error', 'Cannot send email.');
      //   admin_redirect('finances/expenses');
      // }

      // $antar_bank = [];
      // $antar_rek = [];
      // $row_ab = 1;
      // $row_ar = 1;

      // foreach ($_GET['val'] as $expense_id) {
      //   $payments    = $this->site->getExpensePayments($expense_id);
      //   $expense     = $this->site->getExpenseByID($expense_id);
      //   $supplier    = $this->site->getSupplierByID($expense->supplier_id);
      //   $supplier_js = (!empty($supplier->json_data) ? json_decode($supplier->json_data) : NULL);

      //   if (!$supplier_js) continue;

      //   if ($supplier_js->acc_name == 'BNI') { // InHouse
      //     $antar_rek[] = [
      //       'rek_penerima'  => $supplier_js->acc_no,
      //       'nama_penerima' => $supplier_js->acc_holder,
      //       'nominal'       => filterDecimal($payments[0]->amount),
      //       'keterangan'    => htmlRemove(htmlDecode($expense->note))
      //     ];

      //     $row_ar++;
      //   } else { // Kliring
      //     $antar_bank[] = [
      //       'rek_penerima'  => $supplier_js->acc_no,
      //       'nama_penerima' => $supplier_js->acc_holder,
      //       'nominal'       => filterDecimal($payments[0]->amount),
      //       'pesan'         => htmlRemove(htmlDecode($expense->note)),
      //       'pesan2'        => '',
      //       'bic'           => $supplier_js->acc_bic,
      //       'bank_penerima' => $supplier_js->acc_name
      //     ];

      //     $row_ab++;
      //   }
      // }

      // $excel = $this->ridintek->spreadsheet();
      // $excel->setTitle('Purchases Kliring');
      // $excel->createSheet();
      // $excel->setTitle('Purchases InHouse');
      // $excel->getSheet(0);

      // if ($antar_bank) { // ANTAR BANK (BCA, MANDIRI, BRI)
      //   $excel->setTitle(lang('purchases'));
      //   $excel->SetCellValue('A1', 'No Referensi');
      //   $excel->SetCellValue('B1', 'Rekening Debet');
      //   $excel->SetCellValue('C1', 'Nama Pengirim');
      //   $excel->SetCellValue('D1', 'Residency Pengirim');
      //   $excel->SetCellValue('E1', 'Nominal Dikirim');
      //   $excel->SetCellValue('F1', 'Pesan Pengirim');
      //   $excel->SetCellValue('G1', 'Kode BIC');
      //   $excel->SetCellValue('H1', 'Rekening Penerima');
      //   $excel->SetCellValue('I1', 'Nama Penerima');
      //   $excel->SetCellValue('J1', 'Jenis Nasabah Penerima');
      //   $excel->SetCellValue('K1', 'Residency Penerima');
      //   $excel->SetCellValue('L1', 'Nama Bank Penerima');

      //   $row = 2;
      //   foreach ($antar_bank as $data) {
      //     $excel->SetCellValue('A' . $row, $data['no_referensi']);
      //     $excel->SetCellValue('B' . $row, $data['rek_debet'], DataType::TYPE_STRING);
      //     $excel->SetCellValue('C' . $row, $data['nama_pengirim']);
      //     $excel->SetCellValue('D' . $row, $data['residency_pengirim']);
      //     $excel->SetCellValue('E' . $row, $data['nominal']);
      //     $excel->SetCellValue('F' . $row, $data['pesan']);
      //     $excel->SetCellValue('G' . $row, $data['bic']);
      //     $excel->SetCellValue('H' . $row, $data['rek_penerima'], DataType::TYPE_STRING);
      //     $excel->SetCellValue('I' . $row, $data['nama_penerima']);
      //     $excel->SetCellValue('J' . $row, $data['jenis_penerima']);
      //     $excel->SetCellValue('K' . $row, $data['residency_penerima']);
      //     $excel->SetCellValue('L' . $row, $data['bank_penerima']);

      //     $row++;
      //   }

      //   $excel->setColumnAutoWidth('A');
      //   $excel->setColumnAutoWidth('B');
      //   $excel->setColumnAutoWidth('C');
      //   $excel->setColumnAutoWidth('D');
      //   $excel->setColumnAutoWidth('E');
      //   $excel->setColumnAutoWidth('F');
      //   $excel->setColumnAutoWidth('G');
      //   $excel->setColumnAutoWidth('H');
      //   $excel->setColumnAutoWidth('I');
      //   $excel->setColumnAutoWidth('J');
      //   $excel->setColumnAutoWidth('K');
      //   $excel->setColumnAutoWidth('L');

      //   $excel_antar_bank = 'Expenses-Payments-' . date('Y_m_d_H_i_s');
      //   $file_excel_antar_bank = FCPATH . 'files/finances/expenses/' . $excel_antar_bank . '.xlsx';

      //   $excel->save($file_excel_antar_bank);
      // } else {
      //   $file_excel_antar_bank = NULL;
      // }

      // if ($antar_rek) { // ANTAR BNI / REKENING
      //   $excelAntarRek = $this->ridintek->spreadsheet();
      //   $excelAntarRek->setTitle(lang('expenses'));
      //   $excelAntarRek->SetCellValue('A1', 'NOPEG');
      //   $excelAntarRek->SetCellValue('B1', 'NAMAPEG');
      //   $excelAntarRek->SetCellValue('C1', 'NOREKDB');
      //   $excelAntarRek->SetCellValue('D1', 'NOREKKD');
      //   $excelAntarRek->SetCellValue('E1', 'JMLGAJI');
      //   $excelAntarRek->SetCellValue('F1', 'KETERANGAN1');
      //   $excelAntarRek->SetCelLValue('G1', 'KETERANGAN2'); // Required for FUCKED BNI.
      //   $excelAntarRek->SetCelLValue('H1', 'KETERANGAN3'); // Required for FUCKED BNI.

      //   $row = 2;
      //   foreach ($antar_rek as $data) {
      //     $excelAntarRek->SetCellValue('A' . $row, $data['no_id']);
      //     $excelAntarRek->SetCellValue('B' . $row, $data['nama_penerima']);
      //     $excelAntarRek->SetCellValue('C' . $row, $data['rek_debet'], DataType::TYPE_STRING2);
      //     $excelAntarRek->SetCellValue('D' . $row, $data['rek_penerima'], DataType::TYPE_STRING2);
      //     $excelAntarRek->SetCellValue('E' . $row, $data['nominal']);
      //     $excelAntarRek->SetCellValue('F' . $row, $data['keterangan']);

      //     $row++;
      //   }

      //   $excelAntarRek->setColumnAutoWidth('A');
      //   $excelAntarRek->setColumnAutoWidth('B');
      //   $excelAntarRek->setColumnAutoWidth('C');
      //   $excelAntarRek->setColumnAutoWidth('D');
      //   $excelAntarRek->setColumnAutoWidth('E');
      //   $excelAntarRek->setColumnAutoWidth('F');

      //   $excel_antar_rek = 'purchases-antar_rek-' . date('Y_m_d_H_i_s');
      //   $file_excel_antar_rek = FCPATH . 'files/finances/expenses/' . $excel_antar_rek . '.xlsx';

      //   $excelAntarRek->save($file_excel_antar_rek);
      // } else {
      //   $file_excel_antar_rek = NULL;
      // }

      // $attachments = [];
      // if ($file_excel_antar_bank) $attachments[] = $file_excel_antar_bank;
      // if ($file_excel_antar_rek)  $attachments[] = $file_excel_antar_rek;

      // $msg = 'Dear ibu Sinta,<br><br>Berikut kami ajukan data pembayaran untuk segera ditransfer.';
      // // TO: sinta.pramudyani@bni.co.id
      // /*$this->sma->send_email('sd@indoprinting.co.id', "Pembayaran {$date} - Indoprinting", $msg, null, null,
      //   $attachments, ['anita.ratnasari@indoprinting.co.id']);*/
      // $date = date('Y-m-d H:i:s');
      // $this->sma->send_email(
      //   'sd@indoprinting.co.id',
      //   "Pembayaran {$date} - Indoprinting",
      //   $msg,
      //   null,
      //   null,
      //   $attachments
      // );

      // $this->session->set_flashdata('message', 'Email has been sent successfully.');
    } else if ($form_action == 'export_payment') { // EXPORT PAYMENTS
      if (empty($_GET['val'])) {
        sendJSON(['error' => 1, 'msg' => lang('no_expense_selected')]);
      }

      $antar_bank = [];
      $antar_rek = [];
      $row_ab = 1;
      $row_ar = 1;

      foreach ($_GET['val'] as $expense_id) {
        $payments    = $this->site->getExpensePayments($expense_id);
        $expense     = $this->site->getExpenseByID($expense_id);
        $supplier    = $this->site->getSupplierByID($expense->supplier_id);
        $supplier_js = (!empty($supplier->json_data) ? json_decode($supplier->json_data) : NULL);

        if (!$supplier_js) continue;

        if (stripos($supplier_js->acc_name, 'BNI') !== FALSE) { // InHouse
          $antar_rek[] = [
            'rek_penerima'  => $supplier_js->acc_no,
            'nama_penerima' => $supplier_js->acc_holder,
            'nominal'       => filterDecimal($payments[0]->amount),
            'keterangan'    => htmlRemove(htmlDecode($expense->note))
          ];

          $row_ar++;
        } else {
          $antar_bank[] = [
            'rek_penerima'  => $supplier_js->acc_no,
            'nama_penerima' => $supplier_js->acc_holder,
            'nominal'       => filterDecimal($payments[0]->amount),
            'pesan'         => htmlRemove(htmlDecode($expense->note)),
            'pesan2'        => '',
            'bic'           => $supplier_js->acc_bic,
            'bank_penerima' => $supplier_js->acc_name
          ];

          $row_ab++;
        }
      }

      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('Expense Kliring');
      $excel->createSheet();
      $excel->setTitle('Expense InHouse');

      if ($antar_bank) { // ANTAR BANK (BCA, MANDIRI, BRI) (KLIRING)
        $excel->getSheet(0);
        $excel->SetCellValue('A1', 'Rek. Tujuan');
        $excel->SetCellValue('B1', 'Nama Penerima');
        $excel->SetCellValue('C1', 'Amount');
        $excel->SetCellValue('D1', 'Remark');
        $excel->SetCellValue('E1', 'Remark2');
        $excel->SetCellValue('F1', 'Remark3');
        $excel->SetCellValue('G1', 'Clearing Code');
        $excel->SetCellValue('H1', 'Bank Tujuan');
        $excel->SetCellValue('I1', 'Email');
        $excel->SetCellValue('J1', 'Reff Num');

        $row = 2;
        foreach ($antar_bank as $data) {
          $excel->SetCellValue('A' . $row, $data['rek_penerima'], DataType::TYPE_STRING);
          $excel->SetCellValue('B' . $row, $data['nama_penerima']);
          $excel->SetCellValue('C' . $row, $data['nominal']);
          $excel->SetCellValue('D' . $row, getExcerpt($data['pesan'], 33));
          $excel->SetCellValue('E' . $row, '');
          $excel->SetCellValue('F' . $row, '');
          $excel->SetCellValue('G' . $row, $data['bic'], DataType::TYPE_STRING);
          $excel->SetCellValue('H' . $row, $data['bank_penerima']);
          $excel->SetCellValue('I' . $row, '');
          $excel->SetCellValue('J' . $row, '');

          $row++;
        }

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
      }

      if ($antar_rek) { // ANTAR BNI / REKENING (INHOUSE)
        $excel->getSheet(1);
        $excel->SetCellValue('A1', 'Rek. Tujuan');
        $excel->SetCellValue('B1', 'Nama Penerima');
        $excel->SetCellValue('C1', 'Amount');
        $excel->SetCellValue('D1', 'Remark1');
        $excel->SetCellValue('E1', 'Remark2');
        $excel->SetCellValue('F1', 'Email');
        $excel->SetCellValue('G1', 'Reff Num');

        $row = 2;
        foreach ($antar_rek as $data) {
          $excel->SetCellValue('A' . $row, $data['rek_penerima'], DataType::TYPE_STRING);
          $excel->SetCellValue('B' . $row, $data['nama_penerima']);
          $excel->SetCellValue('C' . $row, $data['nominal']);
          $excel->SetCellValue('D' . $row, getExcerpt($data['keterangan'], 33));
          $excel->SetCellValue('E' . $row, '');
          $excel->SetCellValue('F' . $row, '');
          $excel->SetCellValue('G' . $row, '');

          $row++;
        }

        $excel->setColumnAutoWidth('A');
        $excel->setColumnAutoWidth('B');
        $excel->setColumnAutoWidth('C');
        $excel->setColumnAutoWidth('D');
        $excel->setColumnAutoWidth('E');
        $excel->setColumnAutoWidth('F');
        $excel->setColumnAutoWidth('G');
      }

      $excel_name = 'Expenses-' . date('Y_m_d_H_i_s');
      $excel->export($excel_name);
    }

    admin_redirect('finances/expenses');
  }

  private function expenses_add()
  { // expenses
    $this->sma->checkPermissions('add', TRUE, 'expenses');
    $this->form_validation->set_rules('amount', lang('amount'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
    if ($this->form_validation->run() == true) {
      $date = $this->sma->fld(trim(getPOST('date')));
      $data = [
        'date'            => $date,
        'reference'       => $this->site->getReference('expense'),
        'amount'          => roundDecimal(getPOST('amount')),
        'created_by'      => $this->session->userdata('user_id'),
        'note'            => getPOST('note', TRUE),
        'category_id'     => getPOST('category', TRUE),
        'biller_id'       => getPOST('biller', TRUE),
        'bank_id'         => getPOST('paid_by', TRUE),
        'status'          => 'need_approval',
        'payment_status'  => 'pending',
        'supplier_id'     => (getPOST('supplier') ?? 0)
      ];
      if ($_FILES['userfile']['size'] > 0) {
        checkPath($this->upload_expenses_path);
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_expenses_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = 5120; // Request mba Erik jadi 5MB. Sebelumnya 1MB.
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/expenses');
        }
        $photo              = $this->upload->file_name;
        $data['attachment'] = $photo;
      }
    } elseif (getPOST('add_expense')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/expenses');
    }
    if ($this->form_validation->run() == true) {
      if ($this->site->addExpense($data)) {
        $this->session->set_flashdata('message', lang('expense_added'));
      } else {
        $this->session->set_flashdata('error', lang('expense_add_failed'));
      }
      admin_redirect('finances/expenses');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['billers']    = $this->site->getAllBillers();
      $this->data['categories'] = $this->site->getExpenseCategories();
      $this->data['banks']      = $this->site->getAllBanks();
      $this->load->view($this->theme . 'finances/expenses/add', $this->data);
    }
  }

  private function expenses_approval($expense_id)
  { // expenses
    $this->sma->checkPermissions('approval', TRUE, 'expenses');
    $this->form_validation->set_rules('status', lang('status'), 'required');
    $expense = $this->site->getExpenseById($expense_id);

    if ($this->form_validation->run() == true) {
      $data = [
        'status'      => getPOST('status'),
        'note'        => getPOST('note'),
        'approved_by' => $this->session->userdata('user_id')
      ];
      if ($data['status'] == 'need_approval') {
        $this->session->set_flashdata('error', lang('status_not_changed'));
        $this->sma->md();
      }
      if ($this->site->updateExpense($expense_id, $data)) {
        $this->session->set_flashdata('message', lang('payment_approval_success'));
      } else {
        $this->session->set_flashdata('error', lang('payment_approval_failed'));
      }
      admin_redirect('finances/expenses');
    } else if (getPOST('update')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/expenses');
    }

    $bank = $this->site->getBankById($expense->bank_id);
    $bank->balance = $bank->amount;
    $this->data['expense']      = $expense;
    $this->data['bank']         = $bank;
    $this->data['user_create']  = $this->site->getUserById($expense->created_by);
    $this->data['user_approve'] = $this->site->getUserById($expense->approved_by);
    $this->load->view($this->theme . 'finances/expenses/approval', $this->data);
  }

  private function expenses_delete($expense_id)
  { // expenses
    $id = $expense_id;
    $this->sma->checkPermissions('delete', NULL, 'expenses', TRUE);
    if (getGET('id')) {
      $id = getGET('id');
    }
    $expense = $this->site->getExpenseByID($id);
    if ($this->site->deleteExpense($id)) {
      if ($expense->attachment) {
        unlink($this->upload_expenses_path . $expense->attachment);
      }
      sendJSON(['error' => 0, 'msg' => lang('expense_deleted')]);
    }
    sendJSON(['error' => 1, 'msg' => lang('expense_delete_fail')]);
  }

  private function expenses_edit($expense_id)
  { // expenses
    $id = $expense_id;
    $this->sma->checkPermissions('edit', TRUE, 'expenses');

    if (getGET('id')) {
      $id = getGET('id');
    }

    $this->form_validation->set_rules('reference', lang('reference'), 'required');
    $this->form_validation->set_rules('new_amount', lang('new_amount'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
    $expense = $this->site->getExpenseById($id);
    $bank = $this->site->getBankById($expense->bank_id);

    /* if ($expense->payment_status == 'paid') {
      $this->session->set_flashdata('error', lang('expense_paid'));
      $this->sma->md();
    } */

    if ($this->form_validation->run() == true) {
      $data = [
        'date'         => rd_trim(getPOST('date')),
        'amount'       => round(filterDecimal(getPOST('new_amount'))),
        'note'         => getPOST('note'),
        'category_id'  => getPOST('category'),
        'biller_id'    => getPOST('biller'),
        'bank_id'      => getPOST('paid_by'),
        'supplier_id'  => getPOST('supplier')
      ];
      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_expenses_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          $this->sma->md();
        }
        $photo              = $this->upload->file_name;
        $data['attachment'] = $photo;
      }
    } elseif (getPOST('edit_expense')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/expenses');
    }
    if ($this->form_validation->run() == true && $this->site->updateExpense($id, $data)) {
      $this->session->set_flashdata('message', lang('expense_updated'));
      admin_redirect('finances/expenses');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['expense']    = $this->site->getExpenseByID($id);
      $this->data['billers']    = $this->site->getAllBillers();
      $this->data['categories'] = $this->site->getExpenseCategories();
      $this->data['banks']      = $this->site->getAllBanks();
      $this->load->view($this->theme . 'finances/expenses/edit', $this->data);
    }
  }

  private function expenses_export_excel()
  { // expenses

  }

  private function expenses_getExpenses($biller_id = NULL)
  { // expenses
    $this->sma->checkPermissions('index', TRUE, 'expenses');

    $reference          = getGET('reference') ?? NULL;
    $status             = getGET('status')    ?? NULL;
    $payment_status     = getGET('payment_status') ?? NULL;
    $start_date         = getGET('start_date') ?? NULL;
    $end_date           = getGET('end_date')   ?? NULL;
    $start_payment_date = getGET('start_payment_date') ?? NULL;
    $end_payment_date   = getGET('end_payment_date')   ?? NULL;
    $excel              = (getGET('xls') == 1 ? TRUE : FALSE);

    $detail_link = anchor('admin/finances/expenses/note/$1', '<i class="fad fa-fw fa-file-text"></i> ' . lang('expense_note'), 'data-toggle="modal" data-target="#myModal2"');
    $edit_link   = anchor('admin/finances/expenses/edit/$1', '<i class="fad fa-fw fa-edit"></i> ' . lang('edit_expense'), 'data-toggle="modal" data-target="#myModal"');
    $delete_link = "<a href='#' class='po' title='<b>" . $this->lang->line('delete_expense') . "</b>' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('finances/expenses/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
      . lang('delete_expense') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
      <ul class="dropdown-menu pull-right" role="menu">
        <!--<li>' . $detail_link . '</li>-->
        <li>' . $edit_link . '</li>
        <li>' . $delete_link . '</li>
      </ul>
    </div></div>';

    if (!$excel) {
      $this->load->library('datatables');
      $this->datatables
        ->select("expenses.id as id, expenses.date, expenses.reference,
          expense_categories.name as category, expenses.amount, expenses.note,
          banks.name as bank_name, users.fullname as created_by,
          expenses.status, expenses.payment_date, expenses.payment_status,
          suppliers.company as supplier_name, expenses.attachment")
        ->from('expenses')
        ->join('banks', 'banks.id=expenses.bank_id', 'left')
        ->join('expense_categories', 'expense_categories.id=expenses.category_id', 'left')
        ->join('suppliers', 'suppliers.id=expenses.supplier_id', 'left')
        ->join('users', 'users.id=expenses.created_by', 'left')
        ->group_by('expenses.id');

      if ($reference) {
        $this->datatables->like('expenses.reference', $reference, 'both');
      }

      if ($biller_id) {
        $this->datatables->where('expenses.biller_id', $biller_id);
      }

      if ($status) {
        $this->datatables->group_start();
        foreach ($status as $st) {
          $this->datatables->or_like('expenses.status', $st, 'none'); // none = pending; both = %pending%; left = %pending; ...
        }
        $this->datatables->group_end();
      }

      if ($payment_status) {
        $this->datatables->group_start();
        foreach ($payment_status as $pst) {
          $this->datatables->or_like('expenses.payment_status', $pst, 'none');
        }
        $this->datatables->group_end();
      }

      if ($start_date) {
        $start_date = $start_date . ' 00:00:00';
        $end_date   = $end_date . ' 23:59:59';
        $this->datatables->where('expenses.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
      }

      if ($start_payment_date) {
        $start_payment_date = $start_payment_date . ' 00:00:00';
        $end_payment_date   = $end_payment_date . ' 23:59:59';
        $this->datatables->where('expenses.payment_date BETWEEN "' . $start_payment_date . '" AND "' . $end_payment_date . '"');
      }

      if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
        $this->datatables->where('expenses.created_by', $this->session->userdata('user_id'));
      }

      $this->datatables->add_column('Actions', $action, 'id');
      echo $this->datatables->generate();
    } else { // Export excel.
      $this->db
        ->select("expenses.id as id, expenses.date AS date, expenses.reference AS reference,
          expense_categories.name as category, expenses.amount AS amount, expenses.note AS note,
          banks.name as bank_name, billers.name AS biller_name,
          users.fullname as created_by,
          expenses.status AS status,
          expenses.payment_date AS payment_date,
          expenses.payment_status AS payment_status,
          suppliers.company as supplier_name, expenses.attachment AS attachment")
        ->from('expenses')
        ->join('banks', 'banks.id=expenses.bank_id', 'left')
        ->join('billers', 'billers.id = expenses.biller_id', 'left')
        ->join('expense_categories', 'expense_categories.id=expenses.category_id', 'left')
        ->join('suppliers', 'suppliers.id=expenses.supplier_id', 'left')
        ->join('users', 'users.id=expenses.created_by', 'left')
        ->group_by('expenses.id');

      if ($reference) {
        $this->db->like('expenses.reference', $reference, 'both');
      }

      if ($biller_id) {
        $this->db->where('expenses.biller_id', $biller_id);
      }

      if ($status) {
        $this->db->group_start();
        foreach ($status as $st) {
          $this->db->or_like('expenses.status', $st, 'none'); // none = pending; both = %pending%; left = %pending; ...
        }
        $this->db->group_end();
      }

      if ($payment_status) {
        $this->dba_delete->group_start();
        foreach ($payment_status as $pst) {
          $this->db->or_like('expenses.payment_status', $pst, 'none');
        }
        $this->db->group_end();
      }

      if ($start_date) {
        $this->db->where("expenses.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      }

      if ($start_payment_date) {
        $start_payment_date = $start_payment_date . ' 00:00:00';
        $end_payment_date   = $end_payment_date . ' 23:59:59';
        $this->db->where('expenses.payment_date BETWEEN "' . $start_payment_date . '" AND "' . $end_payment_date . '"');
      }

      if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
        $this->db->where('expenses.created_by', $this->session->userdata('user_id'));
      }

      $this->db->order_by('expenses.id', 'DESC');

      $q = $this->db->get();

      if ($q->num_rows() > 0) {
        $excel = $this->ridintek->spreadsheet();

        $excel->setTitle('Expenses');
        $excel->setCellValue('A1', 'ID');
        $excel->setCellValue('B1', 'Date');
        $excel->setCellValue('C1', 'Reference');
        $excel->setCellValue('D1', 'Category');
        $excel->setCellValue('E1', 'Amount');
        $excel->setCellValue('F1', 'Note');
        $excel->setCellValue('G1', 'Bank');
        $excel->setCellValue('H1', 'Biller');
        $excel->setCellValue('I1', 'PIC');
        $excel->setCellValue('J1', 'Status');
        $excel->setCellValue('K1', 'Payment Date');
        $excel->setCellValue('L1', 'Payment Status');
        $excel->setCellValue('M1', 'Supplier');

        $rows = $q->result();
        $r = 2;

        // dd($rows);

        foreach ($rows as $row) {
          $excel->setCellValue('A' . $r, $row->id);
          $excel->setCellValue('B' . $r, $row->date);
          $excel->setCellValue('C' . $r, $row->reference);
          $excel->setCellValue('D' . $r, $row->category);
          $excel->setCellValue('E' . $r, $row->amount);
          $excel->setCellValue('F' . $r, htmlRemove($row->note));
          $excel->setCellValue('G' . $r, $row->bank_name);
          $excel->setCellValue('H' . $r, $row->biller_name);
          $excel->setCellValue('I' . $r, $row->created_by);
          $excel->setCellValue('J' . $r, $row->status);
          $excel->setCellValue('K' . $r, $row->payment_date);
          $excel->setCellValue('L' . $r, $row->payment_status);
          $excel->setCellValue('M' . $r, $row->supplier_name);

          $r++;
        }

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

        $excel->export('PrintERP-Expenses-' . date('Ymd-His'));
      }
    }
  }

  private function expenses_payment($expense_id)
  { // expenses
    $this->sma->checkPermissions('payment', TRUE, 'expenses');

    $this->form_validation->set_rules('status', lang('payment_status'), 'required');
    $expense = $this->site->getExpenseById($expense_id);
    $bank    = $this->site->getBankById($expense->bank_id);
    $bank->balance = $bank->amount;

    if (!$this->form_validation->run() && getPOST('update')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/expenses');
    }

    if ($this->form_validation->run() == true) {
      $status  = getPOST('status');
      $note    = getPOST('note');
      /* if (floatval($bank->balance) < floatval($expense->amount)) {
        $this->session->set_flashdata('warning', lang('insufficient_funds'));
        admin_redirect('finances/expenses');
      } */
      if ($status == 'pending') {
        $this->session->set_flashdata('error', lang('status_not_changed'));
        $this->sma->md();
      }

      if ($this->site->addExpensePayment($expense_id, $status, $note)) {
        $this->session->set_flashdata('message', lang('expense_paid_success'));
      } else {
        $this->session->set_flashdata('error', lang('expense_paid_failed'));
      }
      admin_redirect('finances/expenses');
    } else {
      $this->data['expense']       = $expense;
      $this->data['bank']          = $this->site->getBankById($expense->bank_id);
      $this->data['user_create']   = $this->site->getUserById($expense->created_by);
      $this->data['user_approve']  = $this->site->getUserById($expense->approved_by);
      $this->load->view($this->theme . 'finances/expenses/payment', $this->data);
    }
  }

  public function getBankBalance($bankId)
  {
    $bank = $this->site->getBank(['id' => $bankId]);

    if ($bank) {
      $this->response(200, ['data' => ['balance' => floatval($bank->amount)]]);
    }
    $this->response(404, ['message' => 'Bank is not found.']);
  }

  public function incomes()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'biller' ? 'index' : $params[0]); // If not param or param[0] == biller then index

    if (method_exists($this, $method)) {
      if (!empty($params)) array_shift($params); // Remove original method as param.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function incomes_index()
  {
    $this->sma->checkPermissions('index', NULL, 'incomes');
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $bc                  = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('incomes_list')]
    ];
    $meta = ['page_title' => lang('incomes_list'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/incomes/index', $this->data);
  }

  private function incomes_actions()
  { // incomes

  }

  private function incomes_add()
  { // incomes
    $this->sma->checkPermissions('add', TRUE, 'incomes');
    $this->form_validation->set_rules('amount', lang('amount'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
    if ($this->form_validation->run() == true) {
      $income_data = [
        'date'         => rd_trim(getPOST('date')),
        'reference'    => $this->site->getReference('income'),
        'amount'       => round(filterDecimal(getPOST('amount'))),
        'created_by'   => $this->session->userdata('user_id'),
        'note'         => getPOST('note'),
        'category_id'  => getPOST('category', true),
        'biller_id'    => getPOST('biller', true),
        'bank_id'      => getPOST('transfer_to', TRUE)
      ];

      if ($_FILES['userfile']['size'] > 0) {
        checkPath($this->upload_incomes_path);
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_incomes_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/incomes');
        }

        $photo = $this->upload->file_name;
        $income_data['attachment'] = $photo;
      }
    } elseif (getPOST('add_income')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/incomes');
    }
    if ($this->form_validation->run() == true) {
      if ($this->site->addIncome($income_data)) {
        $this->session->set_flashdata('message', lang('income_added'));
      } else {
        $this->session->set_flashdata('error', 'Failed to add income.');
      }
      admin_redirect('finances/incomes');
    } else {
      $banks = $this->site->getAllBanks();
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['exnumber']   = ''; //$this->site->getReference('ex');
      $this->data['billers']    = $this->site->getAllBillers();
      $this->data['categories'] = $this->site->getIncomeCategories();
      $this->data['banks']      = $banks;
      $this->load->view($this->theme . 'finances/incomes/add', $this->data);
    }
  }

  private function incomes_delete($income_id)
  { // incomes
    $id = $income_id;
    $this->sma->checkPermissions('delete', TRUE, 'incomes');
    $income = $this->site->getIncomeByID($id);
    if ($this->site->deleteIncome($id)) {
      if ($income->attachment) {
        unlink($this->upload_incomes_path . $income->attachment);
      }
      sendJSON(['error' => 0, 'msg' => lang('income_deleted')]);
    }
    sendJSON(['error' => 1, 'msg' => lang('income_delete_failed')]);
  }

  private function incomes_edit($income_id)
  { // incomes
    $this->sma->checkPermissions('edit', TRUE, 'expenses');

    if (getGET('id')) {
      $income_id = getGET('id');
    }

    $this->form_validation->set_rules('amount', 'New amount required.', 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      $income_data = [
        'date'         => rd_trim(getPOST('date')),
        'amount'       => filterDecimal(getPOST('amount')),
        'note'         => getPOST('note', true),
        'category_id'  => getPOST('category', true),
        'biller_id'    => getPOST('biller', true),
        'bank_id'      => getPOST('transfer_to', TRUE)
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_incomes_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/incomes');
        }
        $photo              = $this->upload->file_name;
        $income_data['attachment'] = $photo;
      }
    } elseif (getPOST('edit_income')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/incomes');
    }
    if ($this->form_validation->run()) {
      if ($this->site->updateIncome($income_id, $income_data)) {
        $this->session->set_flashdata('message', lang('income_updated'));
      }
      admin_redirect('finances/incomes');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['banks']      = $this->site->getAllBanks();
      $this->data['income']     = $this->site->getIncomeByID($income_id);
      $this->data['billers']    = $this->site->getAllbillers();
      $this->data['categories'] = $this->site->getIncomeCategories();
      $this->load->view($this->theme . 'finances/incomes/edit', $this->data);
    }
  }

  private function incomes_getIncomes($biller_id = NULL)
  { // incomes
    $this->sma->checkPermissions('index', TRUE, 'incomes');
    $biller_id = $this->session->userdata('biller_id') ?? $biller_id;
    $reference = getGET('reference') ?? NULL;
    $category  = getGET('category') ?? NULL;
    $paid_by   = getGET('paid_by') ?? NULL;
    $from_date = getGET('from_date') ?? NULL;
    $to_date   = getGET('to_date')   ?? NULL;
    $detail_link = anchor('admin/finances/incomes/note/$1', '<i class="fad fa-file-text"></i> ' . lang('income_note'), 'data-toggle="modal" data-target="#myModal2"');
    $edit_link   = anchor('admin/finances/incomes/edit/$1', '<i class="fad fa-edit"></i> ' . lang('edit_income'), 'data-toggle="modal" data-target="#myModal"');
    $delete_link = "<a href='#' class='po' title='<b>" . $this->lang->line('delete_income') . "</b>' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('finances/incomes/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i> "
      . lang('delete_income') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
      <ul class="dropdown-menu pull-right" role="menu">
        <li>' . $detail_link . '</li>
        <li>' . $edit_link   . '</li>
        <li>' . $delete_link . '</li>
      </ul>
      </div></div>';
    $this->load->library('datatables');
    $this->datatables
      ->select("incomes.id as id, incomes.date, incomes.reference, incomes.payment_reference,
        income_categories.name as category, incomes.amount, incomes.note,
        banks.name as bank_name,
        users.fullname as created_by,
        incomes.attachment")
      ->from('incomes')
      ->join('banks', 'banks.id=incomes.bank_id', 'left')
      ->join('income_categories', 'income_categories.id=incomes.category_id', 'left')
      ->join('users', 'users.id=incomes.created_by', 'left')
      ->group_by('incomes.id');
    if ($reference) {
      $this->datatables->like('incomes.reference', $reference, 'both');
    }
    if ($category) {
      $this->datatables->where('incomes.category_id', $category);
    }
    if ($paid_by) {
      $this->datatables->where('incomes.bank_id', $paid_by);
    }
    if ($from_date) {
      $from_date = $this->sma->fsd($from_date) . ' 00:00:00';
      $to_date   = $this->sma->fsd($to_date) . ' 23:59:59';
      $this->datatables->where('incomes.date BETWEEN "' . $from_date . '" AND "' . $to_date . '"');
    }
    if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
      $this->datatables->where('created_by', $this->session->userdata('user_id'));
    }
    if (!$this->Owner && !$this->Admin && $biller_id) {
      $this->datatables->where('biller_id', $biller_id);
    }
    $this->datatables->add_column('Actions', $action, 'id');
    echo $this->datatables->generate();
  }

  /**
   * BANK MUTATIONS
   */
  public function mutations()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'biller' ? 'index' : $params[0]); // If not param or param[0] == biller then index

    if (method_exists($this, $method)) {
      if (!empty($params)) array_shift($params); // Remove original method as param.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function mutations_index($biller_id = NULL)
  {
    $bc   = [ // Breadcrumbs
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('bank_mutations_list')]
    ];
    $meta = ['page_title' => lang('bank_mutations_list'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/mutations/index', $this->data);
  }

  private function mutations_actions()
  { // mutations
    $vals = getPOST('val');
    $action = getPOST('form_action');
    $error = FALSE;
    if ($action == 'delete') { // mutations
      foreach ($vals as $val) {
        if (!$this->site->deleteBankMutation($val)) {
          $this->session->set_flashdata('error', 'Failed to delete mutation');
          $error = TRUE;
          break;
        }
      }
      if (!$error) {
        $this->session->set_flashdata('message', 'bank_mutation_deleted');
      }
    } else if ($action == 'export_excel') {
      $this->session->set_flashdata('warning', lang('function_underdevelopment'));
    }
    admin_redirect('finances/mutations');
  }

  private function mutations_add()
  { // mutations
    $use_payment_validation = FALSE;
    $this->sma->checkPermissions('add', TRUE, 'mutations');
    $this->form_validation->set_rules('from_bank_id', lang('account') . ' ' . lang('from'), 'required');
    $this->form_validation->set_rules('to_bank_id', lang('account') . ' ' . lang('to'), 'required');
    $this->form_validation->set_rules('amount', lang('amount'), 'required');
    if ($this->form_validation->run() == TRUE) {
      $date = $this->sma->fld(getPOST('date'));
      $data = [
        'date'           => $date,
        'from_bank_id'   => getPOST('from_bank_id'),
        'from_bank_name' => $this->site->getBankById(getPOST('from_bank_id'))->name,
        'to_bank_id'     => getPOST('to_bank_id'),
        'to_bank_name'   => $this->site->getBankById(getPOST('to_bank_id'))->name,
        'note'           => getPOST('note'),
        'amount'         => round(filterDecimal(getPOST('amount'))),
        'created_by'     => $this->session->userdata('user_id'),
        'paid_by'        => getPOST('paid_by'),
        'biller_id'      => getPOST('biller'),
        'status'         => 'paid'
      ];

      $skip_payment_validation = (getPOST('skip_pv') ? TRUE : FALSE);
      // $bank_from_balance = $this->site->getBankBalanceByID($data['from_bank_id']);
      /*
      if ($bank_from_balance < $data['amount']) {
        $this->session->set_flashdata('warning', lang('insufficient_funds'));
        admin_redirect('finances/mutations');
      }*/
      // Payment validations in addBankMutation() since it must be created first before make payment validation.
      if ($data['paid_by'] == 'Transfer' && !$skip_payment_validation) {
        $use_payment_validation = TRUE;
      }
      if ($_FILES['userfile']['size'] > 0) {
        checkPath($this->upload_mutations_path);
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_mutations_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/mutations');
        }
        $uploaded_file      = $this->upload->file_name;
        $data['attachment'] = $uploaded_file;
      }

      if ($this->site->addBankMutation($data, $use_payment_validation)) {
        $this->session->set_flashdata('message', lang('bank_mutation_added'));
        admin_redirect('finances/mutations');
      } else {
        $this->session->set_flashdata('error', lang('bank_mutation_add_fail'));
        admin_redirect('finances/mutations');
      }
    } elseif (getPOST('add_bank_mutation')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/mutations');
    }

    $banks = $this->site->getAllBanks();

    $biller_id = ($this->session->userdata('biller_id') ?? NULL);
    $this->data['billers']   = (!$this->session->userdata('biller_id') ? $this->site->getAllBillers() : NULL);
    $this->data['biller']    = $this->site->getBillerByID($biller_id);
    $this->data['biller_id'] = $biller_id;
    $this->data['banks']        = $banks;
    $this->load->view($this->theme . 'finances/mutations/add', $this->data);
  }

  private function mutations_delete($mutation_id)
  { // mutations
    $mutation = $this->site->getBankMutationByID($mutation_id);
    if ($mutation) {
      if ($this->site->deleteBankMutation($mutation->id)) {
        sendJSON(['error' => 0, 'msg' => 'Bank mutation has been delete successfully.']);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Bank mutation has been failed to delete.']);
  }

  private function mutations_detail($mutation_id)
  {
    $mutation = $this->site->getBankMutationById($mutation_id);
    $payment_validation = $this->site->getPaymentValidationByMutationID($mutation_id);
    $this->data['mutation']   = $mutation;
    $this->data['payment_validation'] = $payment_validation;
    $this->load->view($this->theme . 'finances/mutations/detail', $this->data);
  }

  private function mutations_edit($mutation_id)
  { // mutations
    $this->sma->checkPermissions('edit', TRUE, 'mutations');
    $this->form_validation->set_rules('date', lang('date'), 'required');
    $this->form_validation->set_rules('from_bank_id', lang('account') . ' ' . lang('from'), 'required');
    $this->form_validation->set_rules('to_bank_id', lang('account') . ' ' . lang('to'), 'required');
    $this->form_validation->set_rules('old_amount', lang('old_amount'), 'required');
    $this->form_validation->set_rules('new_amount', lang('new_amount'), 'required');

    $mutation = $this->site->getBankMutationById($mutation_id);

    if ($this->form_validation->run() == TRUE) {
      $date = $this->sma->fld(trim(getPOST('date')));
      $data = [
        'date' => $date,
        'reference'      => getPOST('reference'),
        'from_bank_id'   => getPOST('from_bank_id'),
        'from_bank_name' => $this->site->getBankById(getPOST('from_bank_id'))->name,
        'to_bank_id'     => getPOST('to_bank_id'),
        'to_bank_name'   => $this->site->getBankById(getPOST('to_bank_id'))->name,
        'note'           => getPOST('note'),
        'new_amount'     => round(filterDecimal(getPOST('new_amount'))),
        'old_amount'     => round(filterDecimal(getPOST('old_amount'))),
        'updated_by'     => $this->session->userdata('user_id'),
        'biller_id'      => getPOST('biller')
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_mutations_path;
        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('finances/mutations');
        }
        $uploaded_file      = $this->upload->file_name;
        $data['attachment'] = $uploaded_file;
      }

      $bank_from = $this->site->getBankByID($data['from_bank_id']);
      $bank_to   = $this->site->getBankByID($data['to_bank_id']);

      if (($bank_from->amount + $data['old_amount']) < $data['new_amount']) { // Check if balance sufficient.
        // $this->session->set_flashdata('warning', lang('insufficient_funds'));
        // admin_redirect('finances/mutations');
      }
      if ($this->site->updateBankMutation($mutation_id, $data)) { // Edit Bank Mutation.
        $this->session->set_flashdata('message', lang('bank_mutation_edited'));
        admin_redirect('finances/mutations');
      } else {
        $this->session->set_flashdata('error', lang('bank_mutation_failed'));
        admin_redirect('finances/mutations');
      }
    } elseif (getPOST('edit_bank_mutation')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/mutations');
    }

    $bank_from = $this->site->getBankByID($mutation->from_bank_id);
    $bank_to   = $this->site->getBankByID($mutation->to_bank_id);

    $this->data['banks']        = $this->site->getAllBanks();
    $this->data['mutation']     = $mutation;
    $this->data['balance_from'] = $bank_from->amount;
    $this->data['balance_to']   = $bank_to->amount;
    $this->data['billers']      = $this->site->getAllBillers();
    $this->load->view($this->theme . 'finances/mutations/edit', $this->data);
  }

  private function mutations_getMutations()
  { // mutations
    $this->sma->checkPermissions('index', TRUE, 'mutations');
    $ref        = getGET('ref');
    $billers    = getGET('biller');
    $acc_from   = getGET('acc_from');
    $acc_to     = getGET('acc_to');
    $created_by = getGET('created_by');
    $start_date = getGET('start_date');
    $end_date   = getGET('end_date');

    $edit_link     = anchor('admin/finances/mutations/edit/$1', '<i class="fad fa-fw fa-edit"></i> ' . lang('edit_bank_mutation'), 'data-toggle="modal" data-target="#myModal"');
    $delete_link   = "<a href='#' class='tip po' title='" . lang('delete_bank_mutation') . "' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' id='a__$1' href='" . admin_url('finances/mutations/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
      . lang('delete_bank_mutation') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
          <li>' . $edit_link . '</li>
          <li>' . $delete_link . '</li>
        </ul>
      </div></div>';

    $this->load->library('datatables');
    $this->datatables
      ->select("bank_mutations.id as id, date, reference, from_bank_name, to_bank_name, note, amount,
        users.fullname as creator, paid_by,
        billers.name as biller_name, status,
        attachment")
      ->from('bank_mutations')
      ->join('users', 'users.id=bank_mutations.created_by', 'left')
      ->join('billers', 'billers.id=bank_mutations.biller_id', 'left');
    if ($ref) {
      $this->datatables->like('bank_mutations.reference', $ref, 'both');
    }
    if ($acc_from) {
      $this->datatables->like('bank_mutations.from_bank_name', $acc_from, 'both');
    }
    if ($acc_from) {
      $this->datatables->like('bank_mutations.to_bank_name', $acc_to, 'both');
    }
    if ($created_by) {
      $this->datatables->where('bank_mutations.created_by', $created_by);
    }
    if ($billers) {
      $this->datatables->group_start();

      foreach ($billers as $biller_id) {
        $this->datatables->or_where('billers.id', $biller_id);
      }

      $this->datatables->group_end();
    }
    if ($start_date) {
      $start_date = $this->sma->fsd($start_date) . ' 00:00:00';
      $end_date   = $this->sma->fsd($end_date) . ' 23:59:59';
      $this->datatables->where('bank_mutations.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
    }
    if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
      $this->datatables->where('created_by', $this->session->userdata('user_id'));
    }
    if (!$this->Owner && !$this->Admin && $this->session->userdata('biller_id')) {
      $this->datatables->where('billers.id', $this->session->userdata('biller_id'));
    }
    $this->datatables->add_column('Actions', $action, 'id');
    echo $this->datatables->generate();
  }

  private function mutations_status($mutation_id)
  {
    $mutation = $this->site->getBankMutationById($mutation_id);
    $payment_validation = NULL;
    $pp = $this->site->getPaymentValidationsByStatus('pending');
    if ($pp) {
      foreach ($pp as $pv) {
        if ($pv->mutation_id == $mutation->id) {
          $payment_validation = $pv;
          break;
        }
      }
    }
    $this->data['mutation']   = $mutation;
    $this->data['payment_validation'] = $payment_validation;
    $this->load->view($this->theme . 'finances/mutations/status', $this->data);
  }

  /**
   * PAYMENTS (TRIAL)
   */
  public function payments()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'biller' ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }


  /**
   * RECONCILIATION
   */
  public function reconciliations()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function reconciliations_getReconciliations()
  {

    $this->load->library('datatable');

    // Balance = Amount Mutasibank - Amount ERP
    $this->datatable->select("bank_reconciliations.id AS id,
        bank_reconciliations.mb_bank_name AS mb_bank_name,
        bank_reconciliations.account_no, bank_reconciliations.amount_mb AS amount_mb,
        bank_reconciliations.amount_erp AS amount_erp,
        (bank_reconciliations.amount_mb - bank_reconciliations.amount_erp) AS balance,
        bank_reconciliations.mb_acc_name AS mb_acc_name,
        bank_reconciliations.erp_acc_name AS erp_acc_name,
        bank_reconciliations.last_sync_date AS last_sync_date")
      ->from('bank_reconciliations');

    echo $this->datatable->generate();
  }

  private function reconciliations_index()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => 'Bank Reconciliations']
    ];
    $meta = ['page_title' => 'Bank Reconciliations', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/reconciliations/index', $this->data);
  }

  private function reconciliations_sync()
  {
    if (!$this->site->syncBankAmount()) { // Important!
      sendJSON(['error' => 1, 'msg' => 'Failed to sync bank amount.']);
    }

    if ($this->site->syncBankReconciliations()) {
      sendJSON(['error' => 0, 'msg' => 'Bank Reconciliation has been synced successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to sync bank reconciliation.']);
  }

  private function reconciliations_view()
  {
    $module = getGET('m');
    $accNo  = getGET('no');

    $this->data['module']   = $module;
    $this->data['payments'] = [];

    if ($module == 'erp') {
      $payments = $this->site->getPaymentsByBankAccountNumber($accNo);

      $this->data['payments'] = $payments;
    } else if ($module == 'mb') {
    }

    $this->load->view($this->theme . 'finances/reconciliations/view', $this->data);
  }

  /**
   * VALIDATIONS
   */
  public function validations()
  {
    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $this->sma->checkPermissions('index', NULL, 'validations');
    $biller_id = (getGET('biller') ?? $this->session->userdata('biller_id'));
    $this->site->syncPaymentValidations(); // Sync all payment validations.
    $this->data['billers']   = (!$this->session->userdata('biller_id') ? $this->site->getAllbillers() : NULL);
    $this->data['biller']    = $this->site->getbillerByID($biller_id);
    $this->data['biller_id'] = $biller_id;

    $bc   = [ // Breadcrumbs
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('payment_validations')]
    ];

    $meta = ['page_title' => lang('payment_validations'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/validations/index', $this->data);
  }

  private function validations_add($biller_id = NULL)
  { // validations
    $this->sma->checkPermissions('add', TRUE, 'validations');
    $this->form_validation->set_rules('amount', lang('amount'), 'required');
    if ($this->form_validation->run() == TRUE) {
      $date = date('Y-m-d H:i:s');
      $data = [
        'date'         => $date,
        'expired_date' => date('Y-m-d H:i:s', strtotime($date) + (60 * 60 * 24)), // 24 jam
        'reference'    => (!empty(getPOST('reference')) ? getPOST('reference') : ''),
        'amount'       => round(filterDecimal(getPOST('amount')))
      ];
      if ($this->site->addPaymentValidation($data)) {
        $this->session->set_flashdata('message', lang('payment_validation_added'));
        admin_redirect('finances/validations');
      } else {
        $this->session->set_flashdata('error', lang('payment_validation_add_fail'));
        admin_redirect('finances/validations');
      }
    } elseif (getPOST('add_payment_validation')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('finances/validations');
    }
    $biller_id = ($this->session->userdata('biller_id') ?? $biller_id);
    $this->load->view($this->theme . 'finances/validations/add', $this->data);
  }

  private function validations_cancel($id = NULL)
  {
    $this->sma->checkPermissions('cancel', TRUE, 'validations');
    if (!$id) {
      $this->session->set_flashdata('error', lang('no_payment_validation'));
      $this->sma->md();
    }
    $payment_validation = $this->site->getPaymentValidationByID($id);
    if ($payment_validation->status == 'verified') {
      $this->session->set_flashdata('error', lang('payment_already_verified'));
      $this->sma->md();
    }
    if ($payment_validation->status == 'expired') {
      $this->session->set_flashdata('error', lang('validation_already_expired'));
      $this->sma->md();
    }
    if ($this->site->deletePaymentValidation($id)) {
      $this->site->updateSale($payment_validation->sale_id, ['payment_status' => 'pending']);
      $this->session->set_flashdata('message', lang('validation_cancel_success'));
      $this->sma->md();
    } else {
      $this->session->set_flashdata('error', lang('validation_cancel_failed'));
      $this->sma->md();
    }
  }

  private function validations_delete($id = NULL)
  {
    $this->sma->checkPermissions('delete', NULL, 'validations');
    if (!$id) sendJSON(['error' => 1, 'msg' => 'No payment id specified.']);

    if ($this->site->deletePaymentValidation($id)) {
      sendJSON(['error' => 0, 'msg' => lang('payment_validation_deleted')]);
    }
    sendJSON(['error' => 1, 'msg' => lang('payment_validation_del_fail')]);
  }

  private function validations_index($biller_id = NULL)
  {
    $this->sma->checkPermissions('index', NULL, 'validations');
    $biller_id = ($this->session->userdata('biller_id') ?? $biller_id);
    $this->site->syncPaymentValidations(); // Sync all payment validations.
    $this->data['billers']   = (!$this->session->userdata('biller_id') ? $this->site->getAllbillers() : NULL);
    $this->data['biller']    = $this->site->getbillerByID($biller_id);
    $this->data['biller_id'] = $biller_id;
    $bc   = [ // Breadcrumbs
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('finances')],
      ['link' => '#', 'page' => lang('payment_validations')]
    ];
    $meta = ['page_title' => lang('payment_validations'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('finances/validations/index', $this->data);
  }

  private function validations_getValidations($biller_id = NULL)
  {
    $xls              = getGET('xls');
    $reference        = getGET('reference');
    $bank             = getGET('bank');
    $pic              = getGET('pic');
    $customer         = getGET('customer');
    $start_date       = getGET('start_date');
    $end_date         = getGET('end_date');
    $status           = getGET('status');
    $verify_status    = getGET('verify_status');

    if (!$xls) { // Web View
      $cancel_link   = "<a href='#' class='tip po' title='<b>" . lang('cancel_payment_validation') . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' id='a_$1' href='" . admin_url('finances/validations/cancel/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-cancel\"></i> "
        . lang('cancel_payment_validation') . '</a>';
      $manual_link   = anchor('admin/finances/validations/manual/$1', '<i class="fad fa-fw fa-file"></i> ' . lang('manual_validation'), 'data-toggle="modal" data-target="#myModal"');
      $reactivate_link = anchor('admin/finances/validations/reactivate/$1', '<i class="fad fa-fw fa-redo"></i> ' . lang('reactivate_validation'), 'data-toggle="modal" data-target="#myModal"');
      $delete_link   = "<a href='#' class='tip po' title='<b>" . lang('delete_payment_validation') . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' id='a_$1' href='" . admin_url('finances/validations/delete/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
        . lang('delete_payment_validation') . '</a>';
      $action = '<div class="text-center"><div class="btn-group text-left">'
        . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
        . lang('actions') . ' <span class="caret"></span></button>
          <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $manual_link . '</li>
            <li>' . $reactivate_link . '</li>
            <li class="divider"></li>
            <li>' . $delete_link . '</li>
          </ul>
        </div></div>';
      $this->load->library('datatables');
      $this->datatables
        ->select("payment_validations.id as id, payment_validations.date,
          payment_validations.reference, users.username as pic_id,
          users.fullname as pic_name,
          billers.name as biller_name,
          (CASE
            WHEN customers.company IS NOT NULL AND customers.company NOT LIKE ''
            THEN CONCAT(customers.name, ' (', customers.company, ')')
            WHEN customers.company LIKE '' OR customers.company IS NULL
            THEN customers.name
          END) as customer_name,
          banks.name as bank_name,
          banks.number as bank_number,
          payment_validations.amount, payment_validations.unique_code,
          (payment_validations.amount + payment_validations.unique_code) as total,
          payment_validations.transaction_date,
          (CASE
            WHEN payment_validations.mutation_id IS NOT NULL THEN payment_mutation.attachment
            WHEN payment_validations.sale_id IS NOT NULL THEN payment_sale.attachment
          END) AS attachment,
          payment_validations.description, payment_validations.status")
        ->from('payment_validations')
        ->join('sales', 'payment_validations.sale_id=sales.id', 'left')
        ->join('bank_mutations', 'bank_mutations.id = payment_validations.mutation_id', 'left')
        ->join('customers', 'sales.customer_id=customers.id', 'left')
        ->join('users', 'users.id=payment_validations.created_by', 'left')
        ->join('billers', 'billers.id=payment_validations.biller_id', 'left')
        ->join('banks', 'banks.id=payment_validations.bank_id', 'left')
        ->join('(SELECT mutation_id, attachment FROM payments GROUP BY id) payment_mutation', 'payment_mutation.mutation_id = payment_validations.mutation_id', 'left')
        ->join('(SELECT sale_id, attachment FROM payments GROUP BY id) payment_sale', 'payment_sale.sale_id = payment_validations.sale_id', 'left');
      if ($reference) {
        $this->datatables->like('payment_validations.reference', $reference, 'both');
      }
      if ($bank) {
        $this->datatables->where('banks.id', $bank);
      }
      if ($pic) {
        $this->datatables
          ->group_start()
          ->like('users.fullname', $pic, 'both')
          ->or_like('users.username', $pic, 'both')
          ->group_end();
      }
      if ($customer) {
        $this->datatables
          ->group_start()
          ->like('customers.name', $customer, 'both')
          ->or_like('customers.company', $customer, 'both')
          ->group_end();
      }
      if ($biller_id) {
        $this->datatables->where('billers.id', $biller_id);
      }
      if ($verify_status) {
        if ($verify_status == 'manual') {
          $this->datatables->like('payment_validations.description', '(MANUAL)', 'right');
        } else if ($verify_status == 'auto') {
          $this->datatables->not_like('payment_validations.description', '(MANUAL)', 'right');
        }
      }
      if ($start_date) {
        $start_date = $start_date . ' 00:00:00';
        $end_date   = $end_date . ' 23:59:59';
        $this->datatables->where('payment_validations.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
      }
      if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
        $this->datatables->where('created_by', $this->session->userdata('user_id'));
      }
      if (!$this->Owner && !$this->Admin && $this->session->userdata('biller_id')) {
        $this->datatables->where('billers.id', $this->session->userdata('biller_id'));
      }
      $this->datatables->add_column('Actions', $action, 'id');
      echo $this->datatables->generate();
    } else if ($xls == 1) { // Export Excel
      $this->db
        ->select("payment_validations.id AS id, payment_validations.date,
          payment_validations.reference, users.username AS pic_id,
          users.fullname as pic_name,
          billers.name AS biller_name, customers.name AS customer_name,
          customers.company AS customer_company,
          banks.name AS bank_name,
          banks.number AS bank_number,
          payment_validations.amount, payment_validations.unique_code,
          (payment_validations.amount + payment_validations.unique_code) AS total,
          payment_validations.expired_date, payment_validations.transaction_date,
          payment_validations.description, payment_validations.status")
        ->from('payment_validations')
        ->join('sales', 'payment_validations.sale_id=sales.id', 'left')
        ->join('customers', 'sales.customer_id=customers.id', 'left')
        ->join('users', 'users.id=payment_validations.created_by', 'left')
        ->join('billers', 'billers.id=payment_validations.biller_id', 'left')
        ->join('banks', 'banks.id=payment_validations.bank_id', 'left');
      if ($reference) {
        $this->db->like('payment_validations.reference', $reference, 'both');
      }
      if ($bank) {
        $this->db->where('banks.id', $bank);
      }
      if ($pic) {
        $this->db
          ->group_start()
          ->like('users.fullname', $pic, 'both')
          ->or_like('users.username', $pic, 'both')
          ->group_end();
      }
      if ($customer) {
        $this->db
          ->group_start()
          ->like('customers.name', $customer, 'both')
          ->or_like('customers.company', $customer, 'both')
          ->group_end();
      }
      if ($biller_id) {
        $this->db->where('billers.id', $biller_id);
      }
      if ($verify_status) {
        if ($verify_status == 'manual') {
          $this->db->like('payment_validations.description', '(MANUAL)', 'right');
        } else if ($verify_status == 'auto') {
          $this->db->not_like('payment_validations.description', '(MANUAL)', 'right');
        }
      }
      if ($start_date) {
        $start_date = $start_date . ' 00:00:00';
        $end_date   = $end_date . ' 23:59:59';
        $this->db->where('payment_validations.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"');
      }
      if (!$this->Owner && !$this->Admin && !$this->session->userdata('view_right')) {
        $this->db->where('created_by', $this->session->userdata('user_id'));
      }
      if (!$this->Owner && !$this->Admin && $this->session->userdata('biller_id')) {
        $this->db->where('billers.id', $this->session->userdata('biller_id'));
      }

      $this->db->order_by('payment_validations.date', 'DESC');

      $q = $this->db->get();

      if ($q->num_rows() > 0) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle('Payment Validations');
        $excel->setCellValue('A1', 'ID');
        $excel->setCellValue('B1', 'Date');
        $excel->setCellValue('C1', 'Reference');
        $excel->setCellValue('D1', 'PIC ID');
        $excel->setCellValue('E1', 'PIC Name');
        $excel->setCellValue('F1', 'Biller');
        $excel->setCellValue('G1', 'Customer');
        $excel->setCellValue('H1', 'Company');
        $excel->setCellValue('I1', 'Bank Name');
        $excel->setCellValue('J1', 'Account No');
        $excel->setCellValue('K1', 'Amount');
        $excel->setCellValue('L1', 'Unique Code');
        $excel->setCellValue('M1', 'Total');
        $excel->setCellValue('N1', 'Expired Date');
        $excel->setCellValue('O1', 'Transaction Date');
        $excel->setCellValue('P1', 'Description');
        $excel->setCellValue('Q1', 'Status');

        $rowid = 2;
        foreach ($q->result() as $row) {
          $excel->setCellValue('A' . $rowid, $row->id);
          $excel->setCellValue('B' . $rowid, $row->date);
          $excel->setCellValue('C' . $rowid, $row->reference);
          $excel->setCellValue('D' . $rowid, $row->pic_id);
          $excel->setCellValue('E' . $rowid, $row->pic_name);
          $excel->setCellValue('F' . $rowid, $row->biller_name);
          $excel->setCellValue('G' . $rowid, $row->customer_name);
          $excel->setCellValue('H' . $rowid, $row->customer_company);
          $excel->setCellValue('I' . $rowid, $row->bank_name);
          $excel->setCellValue('J' . $rowid, $row->bank_number, DataType::TYPE_STRING);
          $excel->setCellValue('K' . $rowid, $row->amount);
          $excel->setCellValue('L' . $rowid, $row->unique_code);
          $excel->setCellValue('M' . $rowid, $row->total);
          $excel->setCellValue('N' . $rowid, $row->expired_date);
          $excel->setCellValue('O' . $rowid, $row->transaction_date);
          $excel->setCellValue('P' . $rowid, $row->description);
          $excel->setCellValue('Q' . $rowid, $row->status);

          $rowid++;
        }

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

        $excel->export('PrintERP - Payment Validations');
      }
    }
  }

  private function validations_manual($id = NULL)
  { // Manual Validation
    $this->sma->checkPermissions('manual', TRUE, 'validations');

    if (!$id) {
      $this->session->set_flashdata('error', lang('no_payment_validation'));
      $this->sma->md();
    }

    $paymentValidation = $this->site->getPaymentValidationByID($id);
    $this->form_validation->set_rules('amount', lang('lang'), 'required');

    if (!$paymentValidation) {
      $this->session->set_flashdata('error', lang('no_payment_validation'));
      $this->sma->md();
    }

    if ($paymentValidation->status == 'verified') {
      $this->session->set_flashdata('error', lang('payment_already_verified'));
      $this->sma->md();
    }

    if ($this->form_validation->run() == TRUE) {
      $validate_manual = (getPOST('manual_validation') ? TRUE : FALSE);

      if (!$validate_manual) {
        $this->session->set_flashdata('error', lang('agree_validate_manually'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      }

      $amount = round(filterDecimal(getPOST('amount')));
      $bank_id = getPOST('to_bank');
      $transaction_date = getPOST('trans_date');
      $description = rd_trim(getPOST('description'));
      $bank = $this->site->getBankByID($bank_id);

      $data = (object)[
        'account_number' => $bank->number,
        'data_mutasi' => [
          (object)[
            'transaction_date' => $transaction_date,
            'type'             => 'CR',
            'amount'           => $amount,
            'description'      => $description
          ]
        ]
      ];

      $pv_options = [
        'manual' => TRUE, /* Optional, but required for manual validation. */
        'mutation_id' => $paymentValidation->mutation_id,
        'sale_id' => $paymentValidation->sale_id,
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');

        if ($paymentValidation->sale_id) {
          checkPath($this->upload_sales_payments_path);
          $config['upload_path']   = $this->upload_sales_payments_path;
        } else if ($paymentValidation->mutation_id) {
          checkPath($this->upload_mutations_path);
          $config['upload_path']   = $this->upload_mutations_path;
        }

        $config['allowed_types'] = $this->upload_digital_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
        }

        $photo = $this->upload->file_name;
        $pv_options['attachment'] = $photo;
      } else {
        $this->session->set_flashdata('error', lang('attachment_required'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      }

      if ($this->site->validatePaymentValidation($data, $pv_options)) { // Validate manually.
        $this->session->set_flashdata('message', lang('payment_verified'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      } else {
        $this->session->set_flashdata('error', lang('payment_not_verified'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      }
    } elseif (getPOST('manual_validation')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
    }
    $biller_id = $paymentValidation->biller_id;
    $this->data['payment_validation'] = $paymentValidation;
    $this->data['biller_id'] = $biller_id;
    $this->load->view($this->theme . 'finances/validations/manual', $this->data);
  }

  private function validations_reactivate($id = NULL)
  {
    $this->sma->checkPermissions('manual', TRUE, 'validations');
    if (!$id) {
      $this->session->set_flashdata('error', lang('no_payment_validation'));
      $this->sma->md();
    }
    $payment_validation = $this->site->getPaymentValidationByID($id);
    $this->form_validation->set_rules('amount', lang('lang'), 'required');
    if ($payment_validation->status == 'verified') {
      $this->session->set_flashdata('error', lang('payment_already_verified'));
      $this->sma->md();
    }
    if ($this->form_validation->run() == TRUE) {
      $validate_manual = (getPOST('manual_validation') ? TRUE : FALSE);
      if (!$validate_manual) {
        $this->session->set_flashdata('error', lang('agree_validate_manually'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      }
      $amount = round(filterDecimal(getPOST('amount')));
      $bank_id = getPOST('to_bank');
      $transaction_date = $this->sma->fld(rd_trim(getPOST('trans_date')));
      $description = rd_trim(getPOST('description'));
      $bank = $this->site->getBankByID($bank_id);

      $data = (object)[
        'account_number' => $bank->number,
        'data_mutasi' => [
          (object)[
            'transaction_date' => $transaction_date,
            'type'             => 'CR',
            'amount'           => $amount,
            'description'      => $description
          ]
        ]
      ];
      $pv_options = [
        'mutation_id' => $payment_validation->mutation_id,
        'sale_id' => $payment_validation->sale_id,
      ];
      if ($this->site->validatePaymentValidation($data, $pv_options)) { // Validate manually.
        $this->session->set_flashdata('message', lang('payment_verified'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      } else {
        $this->session->set_flashdata('error', lang('payment_not_verified'));
        admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
      }
    } elseif (getPOST('add_manual_verification')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'finances/validations');
    }
    $biller_id = $payment_validation->biller_id;
    $this->data['banks']        = $this->site->getAllBanks();
    $this->data['payment_validation'] = $payment_validation;
    $this->data['biller_id'] = $biller_id;
    $this->load->view($this->theme . 'finances/validations/reactivate', $this->data);
  }
}
