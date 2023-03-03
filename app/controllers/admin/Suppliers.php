<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Suppliers extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // $this->session->set_userdata('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');
      loginPage();
    }
    if ($this->Customer || $this->Supplier) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    $this->lang->admin_load('suppliers', $this->Settings->user_language);
    $this->load->library('form_validation');
  }

  public function add()
  {
    $this->sma->checkPermissions(false, true);

    $this->form_validation->set_rules('name',         lang('name'), 'required');
    $this->form_validation->set_rules('company',      lang('company'), 'required');
    $this->form_validation->set_rules('phone',        lang('phone'), 'required');
    $this->form_validation->set_rules('address',      lang('address'), 'required');
    $this->form_validation->set_rules('city',         lang('city'), 'required');
    $this->form_validation->set_rules('payment_term', lang('payment_term'), 'required');
    $this->form_validation->set_rules('acc_name',     lang('bank_name'), 'required');
    $this->form_validation->set_rules('acc_bic',      lang('bic_code'), 'required');
    $this->form_validation->set_rules('acc_no',       lang('acc_no'), 'required');
    $this->form_validation->set_rules('acc_holder',   lang('acc_holder'), 'required');

    if ($this->form_validation->run('suppliers/add') == true) {
      $data = [
        'name'              => getPost('name'),
        'email'             => getPost('email'),
        'company'           => getPost('company'),
        'address'           => getPost('address'),
        'city'              => getPost('city'),
        'postal_code'       => getPost('postal_code'),
        'country'           => getPost('country'),
        'phone'             => preg_replace('/[^0-9]/', '', getPost('phone')),
        'payment_term'      => getPost('payment_term'),
        'json'              => json_encode([
          'acc_holder'     => getPost('acc_holder'),
          'acc_no'         => preg_replace('/[^0-9]/', '', getPost('acc_no')),
          'acc_name'       => getPost('acc_name'),
          'acc_bic'        => getPost('acc_bic'),
          'cycle_purchase' => getPost('cycle_purchase'),
          'delivery_time'  => getPost('delivery_time'),
          'visit_days'     => getPost('visit_days'),
          'visit_weeks'    => getPost('visit_weeks'),
        ]),
        'json_data'         => json_encode([
          'acc_holder'     => getPost('acc_holder'),
          'acc_no'         => preg_replace('/[^0-9]/', '', getPost('acc_no')),
          'acc_name'       => getPost('acc_name'),
          'acc_bic'        => getPost('acc_bic'),
          'cycle_purchase' => getPost('cycle_purchase'),
          'delivery_time'  => getPost('delivery_time'),
          'visit_days'     => getPost('visit_days'),
          'visit_weeks'    => getPost('visit_weeks'),
        ])
      ];
    } elseif (getPost('add_supplier')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('suppliers');
    }

    if ($this->form_validation->run() == true && $sid = $this->site->addSupplier($data)) {
      $this->session->set_flashdata('message', $this->lang->line('supplier_added'));
      $ref = isset($_SERVER['HTTP_REFERER']) ? explode('?', $_SERVER['HTTP_REFERER']) : null;
      admin_redirect($ref[0] . '?supplier=' . $sid);
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'suppliers/add', $this->data);
    }
  }

  public function add_user($supplier_id = null) // NOT USED.
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $supplier_id = getGET('id');
    }
    $supplier = $this->site->getSupplierByID($supplier_id);

    $this->form_validation->set_rules('email', $this->lang->line('email_address'), 'is_unique[users.email]');
    $this->form_validation->set_rules('password', $this->lang->line('password'), 'required|min_length[8]|max_length[20]|matches[password_confirm]');
    $this->form_validation->set_rules('password_confirm', $this->lang->line('confirm_password'), 'required');

    if ($this->form_validation->run('suppliers/add_user') == true) {
      $active                  = getPost('status');
      $notify                  = getPost('notify');
      list($username, $domain) = explode('@', getPost('email'));
      $email                   = strtolower(getPost('email'));
      $password                = getPost('password');
      $additional_data         = [
        'first_name' => getPost('first_name'),
        'last_name'  => getPost('last_name'),
        'phone'      => preg_replace('/[^0-9]/', '', getPost('phone')),
        'gender'     => getPost('gender'),
        'supplier_id' => $supplier->id,
        'company'    => $supplier->company
      ];
      $this->load->library('ion_auth');
    } elseif (getPost('add_user')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('suppliers');
    }

    if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $active, $notify)) {
      $this->session->set_flashdata('message', $this->lang->line('user_added'));
      admin_redirect('suppliers');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['company']  = $supplier;
      $this->load->view($this->theme . 'suppliers/add_user', $this->data);
    }
  }

  public function delete($id = null)
  {
    $this->sma->checkPermissions(null, true);

    if (getGET('id')) {
      $id = getGET('id');
    }

    if ($this->site->deleteSupplier($id)) {
      sendJSON(['error' => 0, 'msg' => lang('supplier_deleted')]);
    } else {
      sendJSON(['error' => 1, 'msg' => lang('supplier_x_deleted_have_purchases')]);
    }
  }

  public function edit($supplier_id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $supplier_id = getGET('id');
    }

    $supplier = $this->site->getSupplierByID($supplier_id);
    $update_ss = getPost('update_ss'); // Update safety stock.
    $this->form_validation->set_rules('name',         lang('name'), 'required');
    $this->form_validation->set_rules('company',      lang('company'), 'required');
    $this->form_validation->set_rules('phone',        lang('phone'), 'required');
    $this->form_validation->set_rules('address',      lang('address'), 'required');
    $this->form_validation->set_rules('city',         lang('city'), 'required');
    $this->form_validation->set_rules('payment_term', lang('payment_term'), 'required');
    $this->form_validation->set_rules('acc_name',     lang('bank_name'), 'required');
    $this->form_validation->set_rules('acc_bic',      lang('bic_code'), 'required');
    $this->form_validation->set_rules('acc_no',       lang('acc_no'), 'required');
    $this->form_validation->set_rules('acc_holder',   lang('acc_holder'), 'required');

    if ($this->form_validation->run('suppliers/add') == true) {
      $data = [
        'name'              => getPost('name'),
        'email'             => getPost('email'),
        'company'           => getPost('company'),
        'address'           => getPost('address'),
        'city'              => getPost('city'),
        'postal_code'       => getPost('postal_code'),
        'country'           => getPost('country'),
        'phone'             => preg_replace('/[^0-9]/', '', getPost('phone')),
        'payment_term'      => getPost('payment_term'),
        'json'              => json_encode([
          'acc_holder'     => getPost('acc_holder'),
          'acc_no'         => preg_replace('/[^0-9]/', '', getPost('acc_no')),
          'acc_name'       => getPost('acc_name'),
          'acc_bic'        => getPost('acc_bic'),
          'cycle_purchase' => getPost('cycle_purchase'),
          'delivery_time'  => getPost('delivery_time'),
          'visit_days'     => getPost('visit_days'),
          'visit_weeks'    => getPost('visit_weeks'),
        ]),
        'json_data'         => json_encode([
          'acc_holder'     => getPost('acc_holder'),
          'acc_no'         => preg_replace('/[^0-9]/', '', getPost('acc_no')),
          'acc_name'       => getPost('acc_name'),
          'acc_bic'        => getPost('acc_bic'),
          'cycle_purchase' => getPost('cycle_purchase'),
          'delivery_time'  => getPost('delivery_time'),
          'visit_days'     => getPost('visit_days'),
          'visit_weeks'    => getPost('visit_weeks'),
        ])
      ];
    } elseif (getPost('edit_supplier')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect_to($_SERVER['HTTP_REFERER']);
    }

    if ($this->form_validation->run()) {
      if ($this->site->updateSupplier($supplier_id, $data)) {
        if ($update_ss == 1) { // If update safety stock. Take a long time and dependent of item qty.
          $supplier_items = $this->site->getSupplierProducts($supplier_id);

          if ($supplier_items) {
            $settingsJSON = $this->site->getSettingsJSON();
            $opt = getPastMonthPeriod($settingsJSON->safety_stock_period);

            foreach ($supplier_items as $item) {
              $this->site->syncProductQty($item->id);
              $this->site->syncProductSafetyStock($item->id, $opt);
            }
          }
        }
        $this->session->set_flashdata('message', $this->lang->line('supplier_updated'));
      } else {
        $this->session->set_flashdata('error', 'Failed to update supplier.');
      }
      redirect_to($_SERVER['HTTP_REFERER']);
    } else {
      $this->data['supplier']   = $supplier;
      $this->data['json_data']  = json_decode($supplier->json_data);
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'suppliers/edit', $this->data);
    }
  }

  public function getSupplier($id = null)
  {
    // $this->sma->checkPermissions('index');
    $row = $this->site->getSupplierByID($id);
    sendJSON([['id' => $row->id, 'text' => $row->company]]);
  }

  public function getSuppliers()
  {
    $this->sma->checkPermissions('index');

    $this->load->library('datatables');
    $this->datatables
      ->select('id, company, name, email, phone, city, country')
      ->from('suppliers')
      ->add_column('Actions', "<div class=\"text-center\"><a class=\"tip\" title='" . $this->lang->line('list_products') . "' href='" . admin_url('products?supplier=$1') . "'><i class=\"fad fa-list\"></i></a> <a class=\"tip\" title='" . $this->lang->line('list_users') . "' href='" . admin_url('suppliers/users/$1') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-users\"></i></a> <a class=\"tip\" title='" . $this->lang->line('add_user') . "' href='" . admin_url('suppliers/add_user/$1') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-plus-circle\"></i></a> <a class=\"tip\" title='" . $this->lang->line('edit_supplier') . "' href='" . admin_url('suppliers/edit/$1') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . $this->lang->line('delete_supplier') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('suppliers/delete/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');
    echo $this->datatables->generate();
  }

  public function import()
  {
    $this->sma->checkPermissions('add', true);
    $this->load->helper('security');
    $this->form_validation->set_rules('csv_file', $this->lang->line('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');
        checkPath($this->upload_import_path);
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('suppliers');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->upload_import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5001, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id  = array_shift($arrResult);
        $titles  = array_shift($arrResult);
        $rw      = 3;
        $updated = '';
        $data    = [];
        if ($header_id[0] != 'SUPPL') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('suppliers');
        }
        $keys = [
          'no', 'use', 'company_name', 'supplier_name', 'phone', 'email', 'address', 'city',
          'postal_code', 'country', 'payment_term', 'acc_holder', 'acc_no', 'acc_name', 'acc_bic', 'visit_days',
          'visit_weeks', 'cycle_purchase', 'delivery_time'
        ];
        foreach ($arrResult as $value) {
          $csvs[] = array_combine($keys, $value);
        }
        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $supplier = [
            'company'       => rd_trim($csv['company_name']),
            'name'          => rd_trim($csv['supplier_name']),
            'phone'         => rd_trim($csv['phone']),
            'email'         => rd_trim($csv['email']),
            'address'       => rd_trim($csv['address']),
            'city'          => rd_trim($csv['city']),
            'postal_code'   => rd_trim($csv['postal_code']),
            'country'       => rd_trim($csv['country']),
            'payment_term'  => filterDecimal($csv['payment_term']),
            'json'          => json_encode([
              'acc_holder'     => rd_trim($csv['acc_holder']),
              'acc_no'         => rd_trim($csv['acc_no']),
              'acc_name'       => rd_trim($csv['acc_name']),
              'acc_bic'        => rd_trim($csv['acc_bic']),
              'cycle_purchase' => rd_trim($csv['cycle_purchase']),
              'delivery_time'  => rd_trim($csv['delivery_time']),
              'visit_days'     => rd_trim($csv['visit_days']),
              'visit_weeks'    => rd_trim($csv['visit_weeks']),
            ]),
            'json_data'     => json_encode([
              'acc_holder'     => rd_trim($csv['acc_holder']),
              'acc_no'         => rd_trim($csv['acc_no']),
              'acc_name'       => rd_trim($csv['acc_name']),
              'acc_bic'        => rd_trim($csv['acc_bic']),
              'cycle_purchase' => rd_trim($csv['cycle_purchase']),
              'delivery_time'  => rd_trim($csv['delivery_time']),
              'visit_days'     => rd_trim($csv['visit_days']),
              'visit_weeks'    => rd_trim($csv['visit_weeks']),
            ])
          ];

          if (empty($supplier['company']) || empty($supplier['name']) || empty($supplier['phone'])) {
            $this->session->set_flashdata('error', lang('company') . ', ' . lang('name') . ', ' . lang('phone') . ', ' .
              lang('are_required') . ' (' . lang('line_no') . ' ' . $rw . ')');
            admin_redirect('suppliers');
          } else {
            if ($supplier_details = $this->site->getSupplierByCompanyName($supplier['company'])) {
              // if ($supplier_details->group_id == 4) {
              //   $this->site->updateSupplier($supplier_details->id, $supplier);
              //   $updated .= '<p>' . lang('supplier_updated') . ' (' . $supplier['company'] . ')</p>';
              // }
            } else {
              $data[] = $supplier;
            }
            $rw++;
          }
        }
      }
    } elseif (getPost('import')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('suppliers');
    }

    if ($this->form_validation->run() == true && !empty($data)) {
      if ($this->site->addSuppliers($data)) {
        $added = '';
        foreach ($data as $new_supplier) {
          $added .= '<p>' . lang('supplier_added') . ' (' . $new_supplier['company'] . ')</p>';
        }
        $this->session->set_flashdata('message', $added . $updated);
        admin_redirect('suppliers');
      }
    } else {
      if (isset($data) && empty($data)) {
        if ($updated) {
          $this->session->set_flashdata('message', $updated);
        } else {
          $this->session->set_flashdata('warning', lang('data_x_suppliers'));
        }
        admin_redirect('suppliers');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'suppliers/import', $this->data);
    }
  }

  public function index($action = null)
  {
    $this->sma->checkPermissions();

    $this->data['error']  = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['action'] = $action;
    $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('suppliers')]];
    $meta = ['page_title' => lang('suppliers'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('suppliers/index', $this->data);
  }

  public function suggestions($term = null, $limit = null)
  {
    if (getGET('term')) {
      $term = getGET('term', true);
    }
    if (getGET('id')) {
      $term = [];
      $term['id'] = getGET('id', true);
    }
    $limit           = getGET('limit', true);
    $rows['results'] = $this->site->getSupplierSuggestions($term, $limit);
    sendJSON($rows);
  }

  public function supplier_actions()
  {
    if (!$this->Owner && !$this->GP['bulk_actions']) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPost('form_action') == 'delete') {
          $this->sma->checkPermissions('delete');
          $error = false;
          foreach ($_POST['val'] as $id) {
            if (!$this->site->deleteSupplier($id)) {
              $error = true;
            }
          }
          if ($error) {
            $this->session->set_flashdata('warning', lang('suppliers_x_deleted_have_purchases'));
          } else {
            $this->session->set_flashdata('message', $this->lang->line('suppliers_deleted'));
          }
          redirect_to($_SERVER['HTTP_REFERER']);
        }

        if (getPost('form_action') == 'export_excel') {
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
            $row++;
          }

          $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
          $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
          $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
          $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
          $filename = 'suppliers_' . date('Y_m_d_H_i_s');
          $this->excel->export($filename);
        }
      } else {
        $this->session->set_flashdata('error', $this->lang->line('no_supplier_selected'));
        redirect_to($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

  public function users($supplier_id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $supplier_id = getGET('id');
    }

    $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['company']  = $this->site->getSupplierByID($supplier_id);
    $this->data['users']    = $this->site->getSupplierUsers($supplier_id);
    $this->load->view($this->theme . 'suppliers/users', $this->data);
  }

  public function view($id = null)
  {
    $this->sma->checkPermissions('index', true);
    $supplier = $this->site->getSupplierByID($id);
    $this->data['error']    = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['supplier'] = $supplier;
    $this->data['json_data'] = json_decode($supplier->json_data);
    $this->load->view($this->theme . 'suppliers/view', $this->data);
  }
}
