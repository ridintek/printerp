<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Customers extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // admin_redirect('login');
      loginPage();
    }

    $this->lang->admin_load('customers', $this->Settings->user_language);
    $this->load->library('form_validation');
  }

  public function add()
  {
    $this->sma->checkPermissions(false, true);

    $this->form_validation->set_rules('name', lang('contact_person'), 'required|trim');
    $this->form_validation->set_rules('email', lang('email'), 'required|trim|valid_email');

    if ($this->form_validation->run() == true) {
      $cg   = $this->site->getCustomerGroupByID(getPOST('customer_group'));
      $pg   = $this->site->getPriceGroupByID(getPOST('price_group'));
      $data = [
        'name'                => getPOST('name'),
        'email'               => getPOST('email'),
        'group_id'            => '3',
        'group_name'          => 'customer',
        'customer_group_id'   => getPOST('customer_group'),
        'customer_group_name' => $cg->name,
        'price_group_id'      => getPOST('price_group') ? getPOST('price_group') : null,
        'price_group_name'    => getPOST('price_group') ? $pg->name : null,
        'company'             => getPOST('company'),
        'address'             => getPOST('address'),
        'city'                => getPOST('city'),
        'state'               => getPOST('state'),
        'postal_code'         => getPOST('postal_code'),
        'country'             => getPOST('country'),
        'phone'               => getPOST('phone'),
        'payment_term'        => (!empty(getPOST('payment_term')) ? getPOST('payment_term') : 1),
        'json_data' => json_encode([
          'notify_wa' => getPOST('notify_wa'),
          'shipaddr' => getPOST('shipaddr')
        ])
      ];
      if (!$this->Owner && !$this->Admin && stripos($data['company'], 'INDOPRINTING') !== FALSE) {
        $this->session->set_flashdata('error', "Forbidden. You cannot use 'INDOPRINTING' as company.");;
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/customers');
      }
      $cust = $this->site->getCustomerByPhone($data['phone']);
      if ($cust) {
        $this->session->set_flashdata('error', "Phone number {$data['phone']} sudah terdaftar sebelumnya.");;
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/customers');
      }
    } elseif (getPOST('add_customer')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin/customers');
    }

    if ($this->form_validation->run() == true && $cid = Customer::add($data)) {
      $this->session->set_flashdata('message', lang('customer_added'));
      $ref = isset($_SERVER['HTTP_REFERER']) ? explode('?', $_SERVER['HTTP_REFERER']) : null;
      admin_redirect($ref[0] . '?customer=' . $cid);
    } else {
      $this->data['error']           = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['customer_groups'] = $this->site->getAllCustomerGroups();
      $this->data['price_groups']    = $this->site->getAllPriceGroups();
      $this->load->view($this->theme . 'customers/add', $this->data);
    }
  }

  public function add_address($customer_id = null)
  {
    $this->sma->checkPermissions('add', true);
    $customer = $this->site->getCustomerByID($customer_id);

    $this->form_validation->set_rules('line1', lang('line1'), 'required');
    $this->form_validation->set_rules('city', lang('city'), 'required');
    $this->form_validation->set_rules('state', lang('state'), 'required');
    $this->form_validation->set_rules('country', lang('country'), 'required');
    $this->form_validation->set_rules('phone', lang('phone'), 'required');

    if ($this->form_validation->run() == true) {
      $data = [
        'line1'       => getPOST('line1'),
        'line2'       => getPOST('line2'),
        'city'        => getPOST('city'),
        'postal_code' => getPOST('postal_code'),
        'state'       => getPOST('state'),
        'country'     => getPOST('country'),
        'phone'       => getPOST('phone'),
        'customer_id' => $customer->id,
      ];
    } elseif (getPOST('add_address')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && $this->site->addAddress($data)) {
      $this->session->set_flashdata('message', lang('address_added'));
      admin_redirect('customers');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['company']  = $customer;
      $this->load->view($this->theme . 'customers/add_address', $this->data);
    }
  }

  public function add_deposit($customer_id = null)
  {
    $this->sma->checkPermissions('deposits', true);

    if (getGET('id')) {
      $customer_id = getGET('id');
    }
    $customer = $this->site->getCustomerByID($customer_id);

    if ($this->Owner || $this->Admin) {
      $this->form_validation->set_rules('date', lang('date'), 'required');
    }
    $this->form_validation->set_rules('amount', lang('amount'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      if ($this->Owner || $this->Admin) {
        $date = $this->sma->fld(trim(getPOST('date')));
      } else {
        $date = date('Y-m-d H:i:s');
      }
      $data = [
        'date'        => $date,
        'amount'      => getPOST('amount'),
        'paid_by'     => getPOST('paid_by'),
        'note'        => getPOST('note'),
        'customer_id' => $customer->id,
        'created_by'  => XSession::get('user_id'),
      ];

      $cdata = [
        'deposit_amount' => ($customer->deposit_amount + getPOST('amount')),
      ];
    } elseif (getPOST('add_deposit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && $this->site->addCustomerDeposit($data, $cdata)) {
      $this->session->set_flashdata('message', lang('deposit_added'));
      admin_redirect('customers');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['company']  = $customer;
      $this->load->view($this->theme . 'customers/add_deposit', $this->data);
    }
  }

  public function add_user($customer_id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $customer_id = getGET('id');
    }
    $customer = $this->site->getCustomerByID($customer_id);

    $this->form_validation->set_rules('email', lang('email_address'), 'is_unique[users.email]');
    $this->form_validation->set_rules('password', lang('password'), 'required|min_length[8]|max_length[20]|matches[password_confirm]');
    $this->form_validation->set_rules('password_confirm', lang('confirm_password'), 'required');

    if ($this->form_validation->run('customers/add_user') == true) {
      $active                  = getPOST('status');
      $notify                  = getPOST('notify');
      list($username, $domain) = explode('@', getPOST('email'));
      $email                   = strtolower(getPOST('email'));
      $password                = getPOST('password');
      $additional_data         = [
        'first_name'  => getPOST('first_name'),
        'last_name'   => getPOST('last_name'),
        'phone'       => getPOST('phone'),
        'gender'      => getPOST('gender'),
        'customer_id' => $customer->id,
        'company'     => $customer->company,
        'group_id'    => 3,
      ];
      $this->load->library('ion_auth');
    } elseif (getPOST('add_user')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $active, $notify)) {
      $this->session->set_flashdata('message', lang('user_added'));
      admin_redirect('customers');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['company']  = $customer;
      $this->load->view($this->theme . 'customers/add_user', $this->data);
    }
  }

  public function addresses($customer_id = null)
  {
    $this->sma->checkPermissions('index', true);
    $this->data['company']   = $this->site->getCustomerByID($customer_id);
    $this->data['addresses'] = $this->site->getCustomerAddressByID($customer_id);
    $this->load->view($this->theme . 'customers/addresses', $this->data);
  }

  public function customer_actions()
  {
    if (!$this->Owner && !$this->GP['bulk_actions']) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPOST('form_action') == 'delete') {
          $this->sma->checkPermissions('delete', NULL, NULL, TRUE);
          $error = false;
          foreach ($_POST['val'] as $id) {
            if (!$this->site->deleteCustomer($id)) {
              $error = true;
            }
          }
          if ($error) {
            $this->session->set_flashdata('warning', lang('customers_x_deleted_have_sales'));
          } else {
            $this->session->set_flashdata('message', lang('customers_deleted'));
          }
          redirect($_SERVER['HTTP_REFERER']);
        }

        if (getPOST('form_action') == 'export_excel') {
          $this->load->library('excel');
          $this->excel->setActiveSheetIndex(0);
          $this->excel->getActiveSheet()->setTitle(lang('customer'));
          $this->excel->getActiveSheet()->SetCellValue('A1', lang('company'));
          $this->excel->getActiveSheet()->SetCellValue('B1', lang('name'));
          $this->excel->getActiveSheet()->SetCellValue('C1', lang('email'));
          $this->excel->getActiveSheet()->SetCellValue('D1', lang('phone'));
          $this->excel->getActiveSheet()->SetCellValue('E1', lang('address'));
          $this->excel->getActiveSheet()->SetCellValue('F1', lang('city'));
          $this->excel->getActiveSheet()->SetCellValue('G1', lang('state'));
          $this->excel->getActiveSheet()->SetCellValue('H1', lang('postal_code'));
          $this->excel->getActiveSheet()->SetCellValue('I1', lang('country'));
          $this->excel->getActiveSheet()->SetCellValue('J1', lang('deposit_amount'));

          $row = 2;
          foreach ($_POST['val'] as $id) {
            $customer = $this->site->getCustomerByID($id);
            $this->excel->getActiveSheet()->SetCellValue('A' . $row, $customer->company);
            $this->excel->getActiveSheet()->SetCellValue('B' . $row, $customer->name);
            $this->excel->getActiveSheet()->SetCellValue('C' . $row, $customer->email);
            $this->excel->getActiveSheet()->SetCellValue('D' . $row, $customer->phone);
            $this->excel->getActiveSheet()->SetCellValue('E' . $row, $customer->address);
            $this->excel->getActiveSheet()->SetCellValue('F' . $row, $customer->city);
            $this->excel->getActiveSheet()->SetCellValue('G' . $row, $customer->state);
            $this->excel->getActiveSheet()->SetCellValue('H' . $row, $customer->postal_code);
            $this->excel->getActiveSheet()->SetCellValue('I' . $row, $customer->country);
            $this->excel->getActiveSheet()->SetCellValue('J' . $row, $customer->deposit_amount);
            $row++;
          }

          $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
          $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
          $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
          // $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
          $filename = 'customers_' . date('Y_m_d_H_i_s');
          $this->excel->export($filename);
        }
      } else {
        $this->session->set_flashdata('error', lang('no_customer_selected'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function delete($id = null)
  {
    checkPermission('customer-delete');

    if ($this->requestMethod == 'POST') {
      $vals = getPOST('val'); // Array val[]

      if (!empty($vals) && is_array($vals)) {
        $success = 0;

        foreach ($vals as $customerId) {
          if (Customer::delete(['id' => $customerId])) {
            $success++;
          }
        }

        if ($success) $this->response(200, ['message' => "{$success} customers have been deleted."]);
      }

      $this->response(400, ['message' => "Failed to delete customers"]);
    }

    if (getGET('id')) {
      $id = getGET('id');
    }

    if (empty($id)) $this->response(400, ['message' => "Id is empty."]);

    if (getGET('id') == 1) {
      sendJSON(['error' => 1, 'msg' => lang('customer_x_deleted')]);
    }

    if ($this->site->deleteCustomer($id)) {
      sendJSON(['error' => 0, 'msg' => lang('customer_deleted')]);
    } else {
      sendJSON(['error' => 1, 'msg' => lang('customer_x_deleted_have_sales')]);
    }
  }

  public function delete_address($id)
  {
    $this->sma->checkPermissions('delete', true);

    if ($this->site->deleteAddress($id)) {
      $this->session->set_flashdata('message', lang('address_deleted'));
      admin_redirect('customers');
    }
  }

  public function delete_deposit($id)
  {
    $this->sma->checkPermissions(null, true);

    if ($this->site->deleteCustomerDeposit($id)) {
      sendJSON(['error' => 0, 'msg' => lang('deposit_deleted')]);
    }
  }

  public function deposit_note($id = null)
  {
    $this->sma->checkPermissions('deposits', true);
    $deposit                  = $this->site->getCustomerDepositByID($id);
    $this->data['customer']   = $this->site->getCustomerByID($deposit->customer_id);
    $this->data['deposit']    = $deposit;
    $this->data['page_title'] = $this->lang->line('deposit_note');
    $this->load->view($this->theme . 'customers/deposit_note', $this->data);
  }

  public function deposits($customer_id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $customer_id = getGET('id');
    }

    $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['company']  = $this->site->getCustomerByID($customer_id);
    $this->load->view($this->theme . 'customers/deposits', $this->data);
  }

  public function edit($id = null)
  {
    $this->sma->checkPermissions(false, true);

    $this->form_validation->set_rules('name', lang('name'), 'required|trim');
    $this->form_validation->set_rules('phone', lang('phone'), 'required|trim');

    if (getGET('id')) {
      $id = getGET('id');
    }

    $customer = $this->site->getCustomerByID($id);
    if (getPOST('email') != $customer->email) {
      $this->form_validation->set_rules('code', lang('email_address'), 'is_unique[customers.email]');
    }

    if ($this->form_validation->run() == true) {
      $cg   = $this->site->getCustomerGroupByID(getPOST('customer_group'));
      $pg   = $this->site->getPriceGroupByID(getPOST('price_group'));
      $data = [
        'name'                => getPOST('name'),
        'email'               => getPOST('email'),
        'group_id'            => '3',
        'group_name'          => 'customer',
        'customer_group_id'   => getPOST('customer_group'),
        'customer_group_name' => $cg->name,
        'price_group_id'      => getPOST('price_group') ? getPOST('price_group') : null,
        'price_group_name'    => getPOST('price_group') ? $pg->name : null,
        'company'             => getPOST('company'),
        'address'             => getPOST('address'),
        'city'                => getPOST('city'),
        'state'               => getPOST('state'),
        'postal_code'         => getPOST('postal_code'),
        'country'             => getPOST('country'),
        'phone'               => getPOST('phone'),
        'payment_term'        => (!empty(getPOST('payment_term')) ? getPOST('payment_term') : 1),
        'award_points'        => getPOST('award_points'),
        'json_data' => json_encode([
          'notify_wa' => getPOST('notify_wa'),
          'shipaddr' => getPOST('ship_address')
        ])
      ];
    } elseif (getPOST('edit_customer')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }

    if ($this->form_validation->run() == true && $this->site->updateCustomer($id, $data)) {
      $this->session->set_flashdata('message', lang('customer_updated'));
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      $jsdata = json_decode($customer->json_data);
      $customer->ship_address = (!empty($jsdata->shipaddr) ? $jsdata->shipaddr : NULL);
      $this->data['customer']        = $customer;
      $this->data['customerJS']      = getJSON($customer->json_data);
      $this->data['error']           = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['customer_groups'] = $this->site->getAllCustomerGroups();
      $this->data['price_groups']    = $this->site->getAllPriceGroups();
      $this->load->view($this->theme . 'customers/edit', $this->data);
    }
  }

  public function edit_address($id = null)
  {
    $this->sma->checkPermissions('edit', true);

    $this->form_validation->set_rules('line1', lang('line1'), 'required');
    $this->form_validation->set_rules('city', lang('city'), 'required');
    $this->form_validation->set_rules('state', lang('state'), 'required');
    $this->form_validation->set_rules('country', lang('country'), 'required');
    $this->form_validation->set_rules('phone', lang('phone'), 'required');

    if ($this->form_validation->run() == true) {
      $data = [
        'line1'       => getPOST('line1'),
        'line2'       => getPOST('line2'),
        'city'        => getPOST('city'),
        'postal_code' => getPOST('postal_code'),
        'state'       => getPOST('state'),
        'country'     => getPOST('country'),
        'phone'       => getPOST('phone'),
        'updated_at'  => date('Y-m-d H:i:s'),
      ];
    } elseif (getPOST('edit_address')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && $this->site->updateCustomerAddress($id, $data)) {
      $this->session->set_flashdata('message', lang('address_updated'));
      admin_redirect('customers');
    } else {
      $this->data['address']  = $this->site->getAddressByID($id);
      $this->load->view($this->theme . 'customers/edit_address', $this->data);
    }
  }

  public function edit_deposit($id = null)
  {
    $this->sma->checkPermissions('deposits', true);

    if (getGET('id')) {
      $id = getGET('id');
    }
    $deposit = $this->site->getCustomerDepositByID($id);
    $customer = $this->site->getCustomerByID($deposit->customer_id);

    if ($this->Owner || $this->Admin) {
      $this->form_validation->set_rules('date', lang('date'), 'required');
    }
    $this->form_validation->set_rules('amount', lang('amount'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      if ($this->Owner || $this->Admin) {
        $date = $this->sma->fld(trim(getPOST('date')));
      } else {
        $date = $deposit->date;
      }
      $data = [
        'date'       => $date,
        'amount'     => getPOST('amount'),
        'paid_by'    => getPOST('paid_by'),
        'note'       => getPOST('note'),
        'customer_id' => $deposit->customer_id,
        'updated_by' => XSession::get('user_id'),
        'updated_at' => $date = date('Y-m-d H:i:s'),
      ];

      $cdata = [
        'deposit_amount' => (($customer->deposit_amount - $deposit->amount) + getPOST('amount')),
      ];
    } elseif (getPOST('edit_deposit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && $this->site->updateCustomerDeposit($id, $data, $cdata)) {
      $this->session->set_flashdata('message', lang('deposit_updated'));
      admin_redirect('customers');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['company']  = $customer;
      $this->data['deposit']  = $deposit;
      $this->load->view($this->theme . 'customers/edit_deposit', $this->data);
    }
  }

  public function edit_email($id = null)
  { // Added
    if (getGET('id')) {
      $id = getGET('id');
    }

    $customer = $this->site->getCustomerByID($id);
    if (getPOST('email') != $customer->email) {
      $this->form_validation->set_rules('code', lang('email_address'), 'is_unique[customers.email]');
    }

    if ($this->form_validation->run('customers/add') == true) {
      $cg   = $this->site->getCustomerGroupByID(getPOST('customer_group'));
      $pg   = $this->site->getPriceGroupByID(getPOST('price_group'));
      $data = [
        'name'           => getPOST('name'),
        'email'               => getPOST('email'),
        'group_id'            => '3',
        'group_name'          => 'customer',
        'customer_group_id'   => getPOST('customer_group'),
        'customer_group_name' => $cg->name,
        'price_group_id'      => getPOST('price_group') ? getPOST('price_group') : null,
        'price_group_name'    => getPOST('price_group') ? $pg->name : null,
        'company'             => getPOST('company'),
        'address'             => getPOST('address'),
        'city'                => getPOST('city'),
        'state'               => getPOST('state'),
        'postal_code'         => getPOST('postal_code'),
        'country'             => getPOST('country'),
        'phone'               => getPOST('phone'),
        'payment_term'        => getPOST('payment_term'),
        'award_points'        => getPOST('award_points'),
        'json_data' => json_encode([
          'shipaddr' => getPOST('ship_address')
        ])
      ];
    } elseif (getPOST('edit_customer')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }

    if ($this->form_validation->run() == true && $this->site->updateCustomer($id, $data)) {
      $this->session->set_flashdata('message', lang('customer_updated'));
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      $jsdata = json_decode($customer->json_data);
      $customer->ship_address = (isset($jsdata->shipaddr) ? $jsdata->shipaddr : NULL);
      $this->data['customer']        = $customer;
      $this->data['error']           = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['customer_groups'] = $this->site->getAllCustomerGroups();
      $this->data['price_groups']    = $this->site->getAllPriceGroups();
      $this->load->view($this->theme . 'customers/edit_email', $this->data);
    }
  }

  public function get_award_points($id = null)
  {
    $this->sma->checkPermissions('index');
    $row = $this->site->getCustomerByID($id);
    sendJSON(['ca_points' => $row->award_points]);
  }

  public function get_customer_details($id = null)
  {
    sendJSON($this->site->getCustomerByID($id));
  }

  public function get_deposits($customer_id = null)
  {
    $this->sma->checkPermissions('deposits');
    $this->load->library('datatables');
    $this->datatables
      ->select("deposits.id as id, date, amount, paid_by, CONCAT(users.first_name, ' ', users.last_name) as created_by", false)
      ->from('deposits')
      ->join('users', 'users.id=deposits.created_by', 'left')
      ->where('deposits.customer_id', $customer_id)
      ->add_column('Actions', "<div class=\"text-center\"><a class=\"tip\" title='" . lang('deposit_note') . "' href='" . admin_url('customers/deposit_note/$1') . "' data-toggle='modal' data-target='#myModal2'><i class=\"fad fa-file-text\"></i></a> <a class=\"tip\" title='" . lang('edit_deposit') . "' href='" . admin_url('customers/edit_deposit/$1') . "' data-toggle='modal' data-target='#myModal2'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_deposit') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('customers/delete_deposit/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id')
      ->unset_column('id');
    echo $this->datatables->generate();
  }

  public function getCustomer($id = null)
  {
    // $this->sma->checkPermissions('index');
    $row = $this->site->getCustomerByID($id);
    if ($row) {
      sendJSON([['id' => $row->id, 'text' => ($row->company && $row->company != '-' ? $row->company : $row->name)]]);
    }
    sendJSON([['id' => 0, 'text' => 'Customer not found']]);
  }

  public function getCustomers()
  {
    $this->sma->checkPermissions('index');

    $action = '<div class="text-center">
      <a class="tip" title="' . lang('list_deposits') . '" href="' . admin_url('customers/deposits/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-money"></i></a>
      <a class="tip" title="' . lang('add_deposit') . '" href="' . admin_url('customers/add_deposit/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-plus"></i></a>
      <a class="tip" title="' . lang('list_addresses') . '" href="' . admin_url('customers/addresses/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-location-arrow"></i></a>
      <a class="tip" title="' . lang('list_users') . '" href="' . admin_url('customers/users/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-users"></i></a>
      <a class="tip" title="' . lang('add_user') . '" href="' . admin_url('customers/add_user/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-user-plus"></i></a>
      <a class="tip" title="' . lang('edit_customer') . '" href="' . admin_url('customers/edit/$1') . '"' .
      ' data-toggle="modal" data-target="#myModal"><i class="fad fa-edit"></i></a>
      <a href="#" class="tip po" title="' . lang('delete_customer') . '" ' .
      ' data-content="<p>' . lang('r_u_sure') . '</p><a class=\'btn btn-danger po-delete\' href=\'' .
      admin_url('customers/delete/$1') . '\'>' . lang('i_m_sure') . '</a>' .
      '<button class=\'btn po-close\'>' . lang('no') . '</button>"  rel="popover"><i class="fad fa-trash"></i></a></div>';

    $this->load->library('datatables');
    $this->datatables
      ->select('id, company, name, email, phone, price_group_name, customer_group_name, deposit_amount, award_points')
      ->from('customers')
      ->where('group_name', 'customer')
      ->add_column('Actions', $action, 'id');
    //->unset_column('id');
    echo $this->datatables->generate();
  }

  public function import()
  { // NEW. Duplicate of import_csv
    $this->sma->checkPermissions('add', true);
    $this->load->helper('security');
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');

        $config['upload_path']   = 'files/import/';
        $config['allowed_types'] = 'csv';
        $config['max_size']      = '2000';
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('customers');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen('files/import/' . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5001, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id = array_shift($arrResult);
        $titles    = array_shift($arrResult);
        $rw        = 2;
        $updated   = '';
        $data      = [];
        $keys      = [
          'no', 'use', 'name', 'company', 'phone', 'email', 'address', 'city', 'state', 'postal_code', 'country', 'payment_term',
          'customer_group', 'price_group'
        ];

        if ($header_id[0] != 'ID' || $header_id[1] != 'CUSTOMER') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('customers');
        }
        foreach ($arrResult as $value) {
          $csvs[] = array_combine($keys, $value);
        }
        foreach ($csvs as $csv) {
          if ($csv['use'] == 0) continue;
          $cs_group = ($csv['customer_group'] ?? 'Reguler');
          $pc_group = ($csv['price_group']    ?? NULL);
          $customer_group = $this->site->getCustomerGroupByName($cs_group);
          $price_group    = $this->site->getPriceGroupByName($pc_group);
          $customer = [
            'name'                => rd_trim($csv['name']),
            'company'             => rd_trim($csv['company']),
            'phone'               => rd_trim($csv['phone']),
            'email'               => rd_trim($csv['email']),
            'address'             => rd_trim($csv['address']),
            'city'                => rd_trim($csv['city']),
            'state'               => rd_trim($csv['state']),
            'postal_code'         => rd_trim($csv['postal_code']),
            'country'             => rd_trim($csv['country']),
            'payment_term'        => (rd_trim($csv['payment_term']) ?? 1),
            'group_id'            => 3,
            'group_name'          => 'customer',
            'customer_group_id'   => (!empty($customer_group)) ? $customer_group->id : 1,
            'customer_group_name' => (!empty($customer_group)) ? $customer_group->name : 'Reguler',
            'price_group_id'      => (!empty($price_group)) ? $price_group->id : NULL,
            'price_group_name'    => (!empty($price_group)) ? $price_group->name : NULL,
          ];

          if (empty($customer['company']) || empty($customer['name']) || empty($customer['phone'])) {
            $this->session->set_flashdata('error', lang('company') . ', ' . lang('name') . ', ' . lang('phone') . ' ' . lang('are_required') . ' (' . lang('line_no') . ' ' . $rw . ')');
            admin_redirect('customers');
          } else {
            if ($customer_details = $this->site->getCustomerByPhone($customer['phone'])) { // Get customer by phone
              if ($customer_details->group_id == 3) { // If customer
                $updated .= '<p>' . lang('customer_updated') . ' (' . $customer['name'] . ') as ' . $customer['price_group_name'] . '</p>';
                if (!$this->site->updateCustomer($customer_details->id, $customer)) {
                  $this->session->set_flashdata('error', 'Failed to update');
                  admin_redirect('customers');
                }
              }
            } else {
              $data[] = $customer;
            }
            $rw++;
          }
        }
      }
    } elseif (getPOST('import')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && !empty($data)) {
      if ($this->site->addCustomers($data)) {
        $this->session->set_flashdata('message', lang('customers_added') . $updated);
        admin_redirect('customers');
      }
    } else {
      if (isset($data) && empty($data)) {
        if ($updated) {
          $this->session->set_flashdata('message', $updated);
        } else {
          $this->session->set_flashdata('warning', lang('data_x_customers'));
        }
        admin_redirect('customers');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'customers/import', $this->data);
    }
  }

  public function import_csv() // NOT USED.
  {
    $this->sma->checkPermissions('add', true);
    $this->load->helper('security');
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');

        $config['upload_path']   = 'files/';
        $config['allowed_types'] = 'csv';
        $config['max_size']      = '2000';
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('customers');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen('files/' . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5001, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id      = array_shift($arrResult);
        $titles         = array_shift($arrResult);
        $rw             = 2;
        $updated        = '';
        $data           = [];
        $customer_group = $this->site->getCustomerGroupByID($this->Settings->customer_group);
        $price_group    = $this->site->getPriceGroupByID($this->Settings->price_group);
        if ($header_id[0] != 'ID' || $header_id[1] != 'CUSTOMER') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('customers');
        }
        foreach ($arrResult as $key => $value) {
          $customer = [
            'company'             => isset($value[0]) ? trim($value[0]) : '',
            'name'                => isset($value[1]) ? trim($value[1]) : '',
            'email'               => isset($value[2]) ? trim($value[2]) : '',
            'phone'               => isset($value[3]) ? trim($value[3]) : '',
            'address'             => isset($value[4]) ? trim($value[4]) : '',
            'city'                => isset($value[5]) ? trim($value[5]) : '',
            'state'               => isset($value[6]) ? trim($value[6]) : '',
            'postal_code'         => isset($value[7]) ? trim($value[7]) : '',
            'country'             => isset($value[8]) ? trim($value[8]) : '',
            'group_id'            => 3,
            'group_name'          => 'customer',
            'customer_group_id'   => (!empty($customer_group)) ? $customer_group->id : null,
            'customer_group_name' => (!empty($customer_group)) ? $customer_group->name : null,
            'price_group_id'      => (!empty($price_group)) ? $price_group->id : null,
            'price_group_name'    => (!empty($price_group)) ? $price_group->name : null,
          ];
          if (empty($customer['company']) || empty($customer['name']) || empty($customer['email'])) {
            $this->session->set_flashdata('error', lang('company') . ', ' . lang('name') . ', ' . lang('email') . ' ' . lang('are_required') . ' (' . lang('line_no') . ' ' . $rw . ')');
            admin_redirect('customers');
          } else {
            if ($customer_details = $this->site->getCustomerByEmail($customer['email'])) {
              if ($customer_details->group_id == 3) {
                $updated .= '<p>' . lang('customer_updated') . ' (' . $customer['email'] . ')</p>';
                $this->site->updateCustomer($customer_details->id, $customer);
              }
            } else {
              $data[] = $customer;
            }
            $rw++;
          }
        }

        // $this->sma->print_arrays($data, $updated);
      }
    } elseif (getPOST('import')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('customers');
    }

    if ($this->form_validation->run() == true && !empty($data)) {
      if ($this->site->addCustomers($data)) {
        $this->session->set_flashdata('message', lang('customers_added') . $updated);
        admin_redirect('customers');
      }
    } else {
      if (isset($data) && empty($data)) {
        if ($updated) {
          $this->session->set_flashdata('message', $updated);
        } else {
          $this->session->set_flashdata('warning', lang('data_x_customers'));
        }
        admin_redirect('customers');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'customers/import', $this->data);
    }
  }

  public function index($action = null)
  {
    $this->sma->checkPermissions();

    $this->data['error']  = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['action'] = $action;
    $bc                   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('customers')]];
    $meta                 = ['page_title' => lang('customers'), 'bc' => $bc];

    $this->data = array_merge($this->data, $meta);

    $this->page_construct('customers/index', $this->data);
  }

  public function suggestions($term = null, $limit = null, $a = null)
  {
    if (getGET('term')) {
      $term = getGET('term', true);
    }
    if (getGET('id')) {
      $term = [];
      $term['id'] = getGET('id', true);
    }
    $limit  = getGET('limit', true);
    $result = $this->site->getCustomerSuggestions($term, $limit);
    if ($a) {
      sendJSON($result);
    }
    $rows['results'] = $result;
    sendJSON($rows);
  }

  public function users($customer_id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $customer_id = getGET('id');
    }

    $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['company']  = $this->site->getCustomerByID($customer_id);
    $this->data['users']    = $this->site->getCustomerUsers($customer_id);
    $this->load->view($this->theme . 'customers/users', $this->data);
  }

  public function view($id = null)
  {
    $this->sma->checkPermissions('index', true);
    if (!$id) {
      $this->sma->md();
    }
    $customer = $this->site->getCustomerByID($id);
    $jsdata   = json_decode($customer->json_data);
    $customer->ship_address = (isset($jsdata->shipaddr) ? $jsdata->shipaddr : NULL);
    $this->data['error']    = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['customer'] = $customer;
    $this->load->view($this->theme . 'customers/view', $this->data);
  }

  public function debug()
  {
    $customer_group = $this->site->getCustomerGroupByName('Privilege');
    $price_group    = $this->site->getPriceGroupByName('Privilege B');
    //rd_debug($customer_group, $price_group);
  }
}
