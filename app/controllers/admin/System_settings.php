<?php
defined('BASEPATH') or exit('No direct script access allowed');

class system_settings extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // $this->session->set_userdata('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');
      loginPage();
    }

    if (!$this->Owner && !$this->Admin && !getPermission('edit-system')) {
      XSession::set_flash('warning', lang('access_denied'));
      redirect_to('admin');
    }

    $this->lang->admin_load('settings', $this->Settings->user_language);
    $this->load->library('form_validation');
    $this->load->admin_model('settings_model');
    $this->import_path        = 'files/import/';
    $this->upload_path        = 'assets/uploads/';
    $this->thumbs_path        = 'assets/uploads/thumbs/';
    $this->image_types        = 'gif|jpg|jpeg|png|tif';
    $this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif';
    $this->allowed_file_size  = '1024';
  }

  public function add_brand()
  {
    $this->form_validation->set_rules('name', lang('brand_name'), 'trim|required|is_unique[brands.name]|alpha_numeric_spaces');
    $this->form_validation->set_rules('slug', lang('slug'), 'trim|required|is_unique[brands.slug]|alpha_dash');
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPost('name'),
        'code'        => getPost('code'),
        'slug'        => getPost('slug'),
        'description' => getPost('description'),
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path;
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = $this->Settings->iwidth;
        $config['max_height']    = $this->Settings->iheight;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $photo         = $this->upload->file_name;
        $data['image'] = $photo;
        $this->load->library('image_lib');
        $config['image_library']  = 'gd2';
        $config['source_image']   = $this->upload_path . $photo;
        $config['new_image']      = $this->thumbs_path . $photo;
        $config['maintain_ratio'] = true;
        $config['width']          = $this->Settings->twidth;
        $config['height']         = $this->Settings->theight;
        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        if (!$this->image_lib->resize()) {
          echo $this->image_lib->display_errors();
        }
        $this->image_lib->clear();
      }
    } elseif (getPost('add_brand')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/brands');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addBrand($data)) {
      XSession::set_flash('message', lang('brand_added'));
      admin_redirect('system_settings/brands');
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->load->view($this->theme . 'settings/add_brand', $this->data);
    }
  }

  public function add_category()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|is_unique[categories.code]|required');
    $this->form_validation->set_rules('name', lang('name'), 'required|min_length[3]');
    $this->form_validation->set_rules('slug', lang('slug'), 'required|is_unique[categories.slug]|alpha_dash');
    $this->form_validation->set_rules('userfile', lang('category_image'), 'xss_clean');
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPost('name'),
        'code'        => getPost('code'),
        'slug'        => getPost('slug'),
        'description' => getPost('description'),
        'parent_id'   => getPost('parent'),
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path;
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = $this->Settings->iwidth;
        $config['max_height']    = $this->Settings->iheight;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $photo         = $this->upload->file_name;
        $data['image'] = $photo;
        $this->load->library('image_lib');
        $config['image_library']  = 'gd2';
        $config['source_image']   = $this->upload_path . $photo;
        $config['new_image']      = $this->thumbs_path . $photo;
        $config['maintain_ratio'] = true;
        $config['width']          = $this->Settings->twidth;
        $config['height']         = $this->Settings->theight;
        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        if (!$this->image_lib->resize()) {
          echo $this->image_lib->display_errors();
        }
        if ($this->Settings->watermark) {
          $this->image_lib->clear();
          $wm['source_image']     = $this->upload_path . $photo;
          $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
          $wm['wm_type']          = 'text';
          $wm['wm_font_path']     = 'system/fonts/texb.ttf';
          $wm['quality']          = '100';
          $wm['wm_font_size']     = '16';
          $wm['wm_font_color']    = '999999';
          $wm['wm_shadow_color']  = 'CCCCCC';
          $wm['wm_vrt_alignment'] = 'top';
          $wm['wm_hor_alignment'] = 'left';
          $wm['wm_padding']       = '10';
          $this->image_lib->initialize($wm);
          $this->image_lib->watermark();
        }
        $this->image_lib->clear();
        $config = null;
      }
    } elseif (getPost('add_category')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCategory($data)) {
      XSession::set_flash('message', lang('category_added'));
      admin_redirect('system_settings/categories');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['categories'] = $this->settings_model->getParentCategories();
      $this->load->view($this->theme . 'settings/add_category', $this->data);
    }
  }

  public function add_currency()
  {
    $this->form_validation->set_rules('code', lang('currency_code'), 'trim|is_unique[currencies.code]|required');
    $this->form_validation->set_rules('name', lang('name'), 'required');
    $this->form_validation->set_rules('rate', lang('exchange_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'code'   => getPost('code'),
        'name'        => getPost('name'),
        'rate'        => getPost('rate'),
        'symbol'      => getPost('symbol'),
        'auto_update' => getPost('auto_update') ? getPost('auto_update') : 0,
      ];
    } elseif (getPost('add_currency')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/currencies');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCurrency($data)) { //check to see if we are creating the customer
      XSession::set_flash('message', lang('currency_added'));
      admin_redirect('system_settings/currencies');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['page_title'] = lang('new_currency');
      $this->load->view($this->theme . 'settings/add_currency', $this->data);
    }
  }

  public function add_customer_group()
  {
    $this->form_validation->set_rules('name', lang('group_name'), 'trim|is_unique[customer_groups.name]|required');
    $this->form_validation->set_rules('percent', lang('group_percentage'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'percent'   => getPost('percent'),
      ];
    } elseif (getPost('add_customer_group')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/customer_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCustomerGroup($data)) {
      XSession::set_flash('message', lang('customer_group_added'));
      admin_redirect('system_settings/customer_groups');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->load->view($this->theme . 'settings/add_customer_group', $this->data);
    }
  }

  public function add_expense_category()
  {
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|is_unique[categories.code]|required');
    $this->form_validation->set_rules('name', lang('name'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'code' => getPost('code'),
      ];
    } elseif (getPost('add_expense_category')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/expense_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addExpenseCategory($data)) {
      XSession::set_flash('message', lang('expense_category_added'));
      admin_redirect('system_settings/expense_categories');
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->load->view($this->theme . 'settings/add_expense_category', $this->data);
    }
  }

  public function add_income_category()
  {
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|is_unique[categories.code]|required');
    $this->form_validation->set_rules('name', lang('name'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'code' => getPost('code'),
      ];
    } elseif (getPost('add_income_category')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/income_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addIncomeCategory($data)) {
      XSession::set_flash('message', lang('income_category_added'));
      admin_redirect('system_settings/income_categories');
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->load->view($this->theme . 'settings/add_income_category', $this->data);
    }
  }

  public function add_price_group()
  {
    $this->form_validation->set_rules('name', lang('group_name'), 'trim|is_unique[price_groups.name]|required|alpha_numeric_spaces');

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('add_price_group')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/price_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addPriceGroup($data)) {
      XSession::set_flash('message', lang('price_group_added'));
      admin_redirect('system_settings/price_groups');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->load->view($this->theme . 'settings/add_price_group', $this->data);
    }
  }

  public function add_price_range()
  {
    $this->form_validation->set_rules('name', lang('Range Name'), 'trim|is_unique[price_ranges.name]|required|alpha_numeric_spaces');

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('add_price_range')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/price_ranges');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addPriceRange($data)) {
      XSession::set_flash('message', lang('Price range added successfully.'));
      admin_redirect('system_settings/price_ranges');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->load->view($this->theme . 'settings/add_price_range', $this->data);
    }
  }

  public function add_tax_rate()
  {
    $this->form_validation->set_rules('name', lang('name'), 'trim|is_unique[tax_rates.name]|required');
    $this->form_validation->set_rules('type', lang('type'), 'required');
    $this->form_validation->set_rules('rate', lang('tax_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'code'      => getPost('code'),
        'type'      => getPost('type'),
        'rate'      => getPost('rate'),
      ];
    } elseif (getPost('add_tax_rate')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/tax_rates');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addTaxRate($data)) {
      XSession::set_flash('message', lang('tax_rate_added'));
      admin_redirect('system_settings/tax_rates');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->load->view($this->theme . 'settings/add_tax_rate', $this->data);
    }
  }

  public function add_unit()
  {
    $this->form_validation->set_rules('code', lang('unit_code'), 'trim|is_unique[units.code]|required');
    $this->form_validation->set_rules('name', lang('unit_name'), 'trim|required');
    if (getPost('base_unit')) {
      $this->form_validation->set_rules('operator', lang('operator'), 'required');
      $this->form_validation->set_rules('operation_value', lang('operation_value'), 'trim|required');
    }

    if ($this->form_validation->run() == true) {
      $data = [
        'name'            => getPost('name'),
        'code'            => getPost('code'),
        'base_unit'       => getPost('base_unit') ?? NULL,
        'operator'        => getPost('base_unit') ?? NULL,
        'operation_value' => getPost('operation_value') ?? NULL,
      ];
    } elseif (getPost('add_unit')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/units');
    }

    if ($this->form_validation->run()) {
      if ($this->settings_model->addUnit($data)) {
        XSession::set_flash('message', lang('unit_added'));
      } else {
        XSession::set_flash('error', 'Unit failed to add.');
      }
      admin_redirect('system_settings/units');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['base_units'] = $this->site->getAllBaseUnits();
      $this->load->view($this->theme . 'settings/add_unit', $this->data);
    }
  }

  public function add_variant()
  {
    $this->form_validation->set_rules('name', lang('name'), 'trim|is_unique[variants.name]|required');

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('add_variant')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/variants');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addVariant($data)) {
      XSession::set_flash('message', lang('variant_added'));
      admin_redirect('system_settings/variants');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'settings/add_variant', $this->data);
    }
  }

  public function add_warehouse()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('code'), 'trim|is_unique[warehouses.code]|required');
    $this->form_validation->set_rules('name', lang('name'), 'required');
    $this->form_validation->set_rules('userfile', lang('map_image'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      // if ($_FILES['userfile']['size'] > 0) {
      //   $this->load->library('upload');

      //   $config['upload_path']   = 'assets/uploads/';
      //   $config['allowed_types'] = 'gif|jpg|png|jpeg';
      //   $config['max_size']      = $this->allowed_file_size;
      //   $config['max_width']     = '2000';
      //   $config['max_height']    = '2000';
      //   $config['overwrite']     = false;
      //   $config['encrypt_name']  = true;
      //   $config['max_filename']  = 25;
      //   $this->upload->initialize($config);

      //   if (!$this->upload->do_upload()) {
      //     $error = $this->upload->display_errors();
      //     XSession::set_flash('message', $error);
      //     admin_redirect('system_settings/warehouses');
      //   }

      //   $map = $this->upload->file_name;

      //   $this->load->helper('file');
      //   $this->load->library('image_lib');
      //   $config['image_library']  = 'gd2';
      //   $config['source_image']   = 'assets/uploads/' . $map;
      //   $config['new_image']      = 'assets/uploads/thumbs/' . $map;
      //   $config['maintain_ratio'] = true;
      //   $config['width']          = 76;
      //   $config['height']         = 76;

      //   $this->image_lib->clear();
      //   $this->image_lib->initialize($config);

      //   if (!$this->image_lib->resize()) {
      //     echo $this->image_lib->display_errors();
      //   }
      // } else {
      //   $map = null;
      // }
      $data = [
        'code'           => getPost('code'),
        'name'           => getPost('name'),
        'phone'          => getPost('phone'),
        'email'          => getPost('email'),
        'address'        => getPost('address'),
        'geolocation'    => getPost('geolocation'),
        'price_group_id' => getPost('price_group'),
        'json_data' => json_encode([
          'cycle_transfer' => getPost('cycle_transfer'),
          'delivery_time'  => getPost('delivery_time'),
          'visit_days'     => getPost('visit_days'),
          'visit_weeks'    => getPost('visit_weeks')
        ])
      ];
    } elseif (getPost('add_warehouse')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/warehouses');
    }

    if ($this->form_validation->run()) {
      if ($this->site->addWarehouse($data)) {
        XSession::set_flash('message', lang('warehouse_added'));
      } else {
        XSession::set_flash('error', 'Failed to add warehouse.');
      }
      admin_redirect('system_settings/warehouses');
    } else {
      $this->data['error']        = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['price_groups'] = $this->settings_model->getAllPriceGroups();
      $this->load->view($this->theme . 'settings/add_warehouse', $this->data);
    }
  }

  public function backup_database()
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->dbutil();
    $prefs = [
      'format'   => 'txt',
      'filename' => 'sma_db_backup.sql',
    ];
    $back    = $this->dbutil->backup($prefs);
    $backup  = &$back;
    $db_name = 'db-backup-on-' . date('Y-m-d-H-i-s') . '.txt';
    $save    = './files/backups/' . $db_name;
    $this->load->helper('file');
    write_file($save, $backup);
    XSession::set_flash('messgae', lang('db_saved'));
    admin_redirect('system_settings/backups');
  }

  public function backup_files()
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $name = 'file-backup-' . date('Y-m-d-H-i-s');
    $this->sma->zip('./', './files/backups/', $name);
    XSession::set_flash('messgae', lang('backup_saved'));
    admin_redirect('system_settings/backups');
    exit();
  }

  public function categories()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc                  = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('system_settings'), 'page' => lang('system_settings')],
      ['link' => '#', 'page' => lang('product_categories')]
    ];
    $meta = ['page_title' => lang('product_categories'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/categories', $this->data);
  }

  public function change_logo()
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      $this->sma->md();
    }
    $this->load->helper('security');
    $this->form_validation->set_rules('site_logo', lang('site_logo'), 'xss_clean');
    $this->form_validation->set_rules('login_logo', lang('login_logo'), 'xss_clean');
    $this->form_validation->set_rules('biller_logo', lang('biller_logo'), 'xss_clean');
    if ($this->form_validation->run() == true) {
      if ($_FILES['site_logo']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path . 'logos/';
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = 300;
        $config['max_height']    = 80;
        $config['overwrite']     = false;
        $config['max_filename']  = 25;
        //$config['encrypt_name'] = TRUE;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('site_logo')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $site_logo = $this->upload->file_name;
        $this->db->update('settings', ['logo' => $site_logo], ['setting_id' => 1]);
      }

      if ($_FILES['login_logo']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path . 'logos/';
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = 300;
        $config['max_height']    = 80;
        $config['overwrite']     = false;
        $config['max_filename']  = 25;
        //$config['encrypt_name'] = TRUE;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('login_logo')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $login_logo = $this->upload->file_name;
        $this->db->update('settings', ['logo2' => $login_logo], ['setting_id' => 1]);
      }

      if ($_FILES['biller_logo']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path . 'logos/';
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = 300;
        $config['max_height']    = 80;
        $config['overwrite']     = false;
        $config['max_filename']  = 25;
        //$config['encrypt_name'] = TRUE;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('biller_logo')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $photo = $this->upload->file_name;
      }

      XSession::set_flash('message', lang('logo_uploaded'));
      redirect_to($_SERVER['HTTP_REFERER']);
    } elseif (getPost('upload_logo')) {
      XSession::set_flash('error', validation_errors());
      redirect_to($_SERVER['HTTP_REFERER']);
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->load->view($this->theme . 'settings/change_logo', $this->data);
    }
  }

  public function create_group()
  {
    $this->form_validation->set_rules('group_name', lang('group_name'), 'required|alpha_dash|is_unique[groups.name]');

    if ($this->form_validation->run() == true) {
      $data = ['name' => strtolower(getPost('group_name')), 'description' => getPost('description')];
    } elseif (getPost('create_group')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/user_groups');
    }

    if ($this->form_validation->run() == true && ($new_group_id = $this->settings_model->addGroup($data))) {
      XSession::set_flash('message', lang('group_added'));
      admin_redirect('system_settings/permissions/' . $new_group_id);
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['group_name'] = [
        'name'  => 'group_name',
        'id'    => 'group_name',
        'type'  => 'text',
        'class' => 'form-control',
        'value' => $this->form_validation->set_value('group_name'),
      ];
      $this->data['description'] = [
        'name'  => 'description',
        'id'    => 'description',
        'type'  => 'text',
        'class' => 'form-control',
        'value' => $this->form_validation->set_value('description'),
      ];
      $this->load->view($this->theme . 'settings/create_group', $this->data);
    }
  }

  public function currencies()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('currencies')]];
    $meta = ['page_title' => lang('currencies'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/currencies', $this->data);
  }

  public function customer_groups()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('customer_groups')]];
    $meta = ['page_title' => lang('customer_groups'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/customer_groups', $this->data);
  }

  public function delete_backup($zipfile)
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    unlink('./files/backups/' . $zipfile . '.zip');
    XSession::set_flash('messgae', lang('backup_deleted'));
    admin_redirect('system_settings/backups');
  }

  public function delete_brand($id = null)
  {
    if ($this->settings_model->brandHasProducts($id)) {
      sendJSON(['error' => 1, 'msg' => lang('brand_has_products')]);
    }

    if ($this->settings_model->deleteBrand($id)) {
      sendJSON(['error' => 0, 'msg' => lang('brand_deleted')]);
    }
  }

  public function delete_category($id = null)
  {
    if ($this->site->getSubCategories($id)) {
      sendJSON(['error' => 1, 'msg' => lang('category_has_subcategory')]);
    }

    if ($this->settings_model->deleteCategory($id)) {
      sendJSON(['error' => 0, 'msg' => lang('category_deleted')]);
    }
  }

  public function delete_currency($id = null)
  {
    if ($this->settings_model->deleteCurrency($id)) {
      sendJSON(['error' => 0, 'msg' => lang('currency_deleted')]);
    }
  }

  public function delete_customer_group($id = null)
  {
    if ($this->settings_model->deleteCustomerGroup($id)) {
      sendJSON(['error' => 0, 'msg' => lang('customer_group_deleted')]);
    }
  }

  public function delete_database($dbfile)
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    unlink('./files/backups/' . $dbfile . '.txt');
    XSession::set_flash('messgae', lang('db_deleted'));
    admin_redirect('system_settings/backups');
  }

  public function delete_expense_category($id = null)
  {
    if ($this->settings_model->hasExpenseCategoryRecord($id)) {
      sendJSON(['error' => 1, 'msg' => lang('category_has_expenses')]);
    }

    if ($this->settings_model->deleteExpenseCategory($id)) {
      sendJSON(['error' => 0, 'msg' => lang('expense_category_deleted')]);
    }
  }

  public function delete_income_category($id = null)
  {
    if ($this->settings_model->hasIncomeCategoryRecord($id)) {
      sendJSON(['error' => 1, 'msg' => lang('category_has_incomes')]);
    }

    if ($this->settings_model->deleteIncomeCategory($id)) {
      sendJSON(['error' => 0, 'msg' => lang('income_category_deleted')]);
    }
  }

  public function delete_purchase_category($id = null)
  {
    if ($this->settings_model->hasPurchaseCategoryRecord($id)) {
      sendJSON(['error' => 1, 'msg' => lang('category_has_purchases')]);
    }

    if ($this->settings_model->deletePurchaseCategory($id)) {
      sendJSON(['error' => 0, 'msg' => lang('purchase_category_deleted')]);
    }
  }

  public function delete_group($id = null)
  {
    if ($this->settings_model->checkGroupUsers($id)) {
      XSession::set_flash('error', lang('group_x_b_deleted'));
      admin_redirect('system_settings/user_groups');
    }

    if ($this->settings_model->deleteGroup($id)) {
      XSession::set_flash('message', lang('group_deleted'));
      admin_redirect('system_settings/user_groups');
    }
  }

  public function delete_price_group($id = null)
  {
    if ($this->settings_model->deletePriceGroup($id)) {
      sendJSON(['error' => 0, 'msg' => lang('price_group_deleted')]);
    }
  }

  public function delete_price_range($id = null)
  {
    if ($this->settings_model->deletePriceRange($id)) {
      sendJSON(['error' => 0, 'msg' => lang('Price Range deleted.')]);
    }
  }

  public function delete_tax_rate($id = null)
  {
    if ($this->settings_model->deleteTaxRate($id)) {
      sendJSON(['error' => 0, 'msg' => lang('tax_rate_deleted')]);
    }
  }

  public function delete_unit($id = null)
  {
    if ($this->settings_model->getUnitChildren($id)) {
      sendJSON(['error' => 1, 'msg' => lang('unit_has_subunit')]);
    }

    if ($this->settings_model->deleteUnit($id)) {
      sendJSON(['error' => 0, 'msg' => lang('unit_deleted')]);
    }
  }

  public function delete_variant($id = null)
  {
    if ($this->settings_model->deleteVariant($id)) {
      sendJSON(['error' => 0, 'msg' => lang('variant_deleted')]);
    }
  }

  public function delete_warehouse($id = null)
  {
    if ($this->settings_model->deleteWarehouse($id)) {
      sendJSON(['error' => 0, 'msg' => lang('warehouse_deleted')]);
    }
  }

  public function download_backup($zipfile)
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->helper('download');
    force_download('./files/backups/' . $zipfile . '.zip', null);
    exit();
  }

  public function download_database($dbfile)
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->library('zip');
    $this->zip->read_file('./files/backups/' . $dbfile . '.txt');
    $name = $dbfile . '.zip';
    $this->zip->download($name);
    exit();
  }

  public function edit_brand($id = null)
  {
    $this->form_validation->set_rules('name', lang('brand_name'), 'trim|required|alpha_numeric_spaces');
    $brand_details = $this->site->getBrandByID($id);
    if (getPost('name') != $brand_details->name) {
      $this->form_validation->set_rules('name', lang('brand_name'), 'required|is_unique[brands.name]');
    }
    $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash');
    if (getPost('slug') != $brand_details->slug) {
      $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash|is_unique[brands.slug]');
    }
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPost('name'),
        'code'        => getPost('code'),
        'slug'        => getPost('slug'),
        'description' => getPost('description'),
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path;
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = $this->Settings->iwidth;
        $config['max_height']    = $this->Settings->iheight;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $photo         = $this->upload->file_name;
        $data['image'] = $photo;
        $this->load->library('image_lib');
        $config['image_library']  = 'gd2';
        $config['source_image']   = $this->upload_path . $photo;
        $config['new_image']      = $this->thumbs_path . $photo;
        $config['maintain_ratio'] = true;
        $config['width']          = $this->Settings->twidth;
        $config['height']         = $this->Settings->theight;
        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        if (!$this->image_lib->resize()) {
          echo $this->image_lib->display_errors();
        }
        $this->image_lib->clear();
      }
    } elseif (getPost('edit_brand')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/brands');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateBrand($id, $data)) {
      XSession::set_flash('message', lang('brand_updated'));
      admin_redirect('system_settings/brands');
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['brand']    = $brand_details;
      $this->load->view($this->theme . 'settings/edit_brand', $this->data);
    }
  }

  public function edit_category($id = null)
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|required');
    $pr_details = $this->settings_model->getCategoryByID($id);
    if (getPost('code') != $pr_details->code) {
      $this->form_validation->set_rules('code', lang('category_code'), 'required|is_unique[categories.code]');
    }
    $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash');
    if (getPost('slug') != $pr_details->slug) {
      $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash|is_unique[categories.slug]');
    }
    $this->form_validation->set_rules('name', lang('category_name'), 'required|min_length[3]');
    $this->form_validation->set_rules('userfile', lang('category_image'), 'xss_clean');
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPost('name'),
        'code'        => getPost('code'),
        'slug'        => getPost('slug'),
        'description' => getPost('description'),
        'parent_id'   => getPost('parent'),
      ];

      if ($_FILES['userfile']['size'] > 0) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_path;
        $config['allowed_types'] = $this->image_types;
        $config['max_size']      = $this->allowed_file_size;
        $config['max_width']     = $this->Settings->iwidth;
        $config['max_height']    = $this->Settings->iheight;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          redirect_to($_SERVER['HTTP_REFERER']);
        }
        $photo         = $this->upload->file_name;
        $data['image'] = $photo;
        $this->load->library('image_lib');
        $config['image_library']  = 'gd2';
        $config['source_image']   = $this->upload_path . $photo;
        $config['new_image']      = $this->thumbs_path . $photo;
        $config['maintain_ratio'] = true;
        $config['width']          = $this->Settings->twidth;
        $config['height']         = $this->Settings->theight;
        $this->image_lib->clear();
        $this->image_lib->initialize($config);
        if (!$this->image_lib->resize()) {
          echo $this->image_lib->display_errors();
        }
        if ($this->Settings->watermark) {
          $this->image_lib->clear();
          $wm['source_image']     = $this->upload_path . $photo;
          $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
          $wm['wm_type']          = 'text';
          $wm['wm_font_path']     = 'system/fonts/texb.ttf';
          $wm['quality']          = '100';
          $wm['wm_font_size']     = '16';
          $wm['wm_font_color']    = '999999';
          $wm['wm_shadow_color']  = 'CCCCCC';
          $wm['wm_vrt_alignment'] = 'top';
          $wm['wm_hor_alignment'] = 'left';
          $wm['wm_padding']       = '10';
          $this->image_lib->initialize($wm);
          $this->image_lib->watermark();
        }
        $this->image_lib->clear();
        $config = null;
      }
    } elseif (getPost('edit_category')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCategory($id, $data)) {
      XSession::set_flash('message', lang('category_updated'));
      admin_redirect('system_settings/categories');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['category']   = $this->settings_model->getCategoryByID($id);
      $this->data['categories'] = $this->settings_model->getParentCategories();
      $this->load->view($this->theme . 'settings/edit_category', $this->data);
    }
  }

  public function edit_currency($id = null)
  {
    $this->form_validation->set_rules('code', lang('currency_code'), 'trim|required');
    $cur_details = $this->settings_model->getCurrencyByID($id);
    if (getPost('code') != $cur_details->code) {
      $this->form_validation->set_rules('code', lang('currency_code'), 'required|is_unique[currencies.code]');
    }
    $this->form_validation->set_rules('name', lang('currency_name'), 'required');
    $this->form_validation->set_rules('rate', lang('exchange_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'code'   => getPost('code'),
        'name'        => getPost('name'),
        'rate'        => getPost('rate'),
        'symbol'      => getPost('symbol'),
        'auto_update' => getPost('auto_update') ? getPost('auto_update') : 0,
      ];
    } elseif (getPost('edit_currency')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/currencies');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCurrency($id, $data)) { //check to see if we are updateing the customer
      XSession::set_flash('message', lang('currency_updated'));
      admin_redirect('system_settings/currencies');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['currency'] = $this->settings_model->getCurrencyByID($id);
      $this->data['id']       = $id;
      $this->load->view($this->theme . 'settings/edit_currency', $this->data);
    }
  }

  public function edit_customer_group($id = null)
  {
    $this->form_validation->set_rules('name', lang('group_name'), 'trim|required');
    $pg_details = $this->settings_model->getCustomerGroupByID($id);
    if (getPost('name') != $pg_details->name) {
      $this->form_validation->set_rules('name', lang('group_name'), 'required|is_unique[tax_rates.name]');
    }
    $this->form_validation->set_rules('percent', lang('group_percentage'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'percent'   => getPost('percent'),
      ];
    } elseif (getPost('edit_customer_group')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/customer_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCustomerGroup($id, $data)) {
      XSession::set_flash('message', lang('customer_group_updated'));
      admin_redirect('system_settings/customer_groups');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['customer_group'] = $this->settings_model->getCustomerGroupByID($id);

      $this->data['id']       = $id;
      $this->load->view($this->theme . 'settings/edit_customer_group', $this->data);
    }
  }

  public function edit_expense_category($id = null)
  {
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|required');
    $category = $this->settings_model->getExpenseCategoryByID($id);
    if (getPost('code') != $category->code) {
      $this->form_validation->set_rules('code', lang('category_code'), 'required|is_unique[expense_categories.code]');
    }
    $this->form_validation->set_rules('name', lang('category_name'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'code' => getPost('code'),
        'name' => getPost('name'),
      ];
    } elseif (getPost('edit_expense_category')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/expense_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateExpenseCategory($id, $data)) {
      XSession::set_flash('message', lang('expense_category_updated'));
      admin_redirect('system_settings/expense_categories');
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['category'] = $category;
      $this->load->view($this->theme . 'settings/edit_expense_category', $this->data);
    }
  }

  public function edit_group($id)
  {
    if (!$id || empty($id)) {
      admin_redirect('system_settings/user_groups');
    }

    $group = $this->settings_model->getGroupByID($id);

    $this->form_validation->set_rules('group_name', lang('group_name'), 'required|alpha_dash');

    if ($this->form_validation->run() === true) {
      $data         = ['name' => strtolower(getPost('group_name')), 'description' => getPost('description')];
      $group_update = $this->settings_model->updateGroup($id, $data);

      if ($group_update) {
        XSession::set_flash('message', lang('group_udpated'));
      } else {
        XSession::set_flash('error', lang('attempt_failed'));
      }
      admin_redirect('system_settings/user_groups');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['group'] = $group;

      $this->data['group_name'] = [
        'name'  => 'group_name',
        'id'    => 'group_name',
        'type'  => 'text',
        'class' => 'form-control',
        'value' => $this->form_validation->set_value('group_name', $group->name),
      ];
      $this->data['group_description'] = [
        'name'  => 'group_description',
        'id'    => 'group_description',
        'type'  => 'text',
        'class' => 'form-control',
        'value' => $this->form_validation->set_value('group_description', $group->description),
      ];
      $this->load->view($this->theme . 'settings/edit_group', $this->data);
    }
  }

  public function edit_price_group($id = null)
  {
    $this->form_validation->set_rules('name', lang('group_name'), 'trim|required|alpha_numeric_spaces');
    $pg_details = $this->settings_model->getPriceGroupByID($id);
    if (getPost('name') != $pg_details->name) {
      $this->form_validation->set_rules('name', lang('group_name'), 'required|is_unique[price_groups.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('edit_price_group')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/price_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePriceGroup($id, $data)) {
      XSession::set_flash('message', lang('price_group_updated'));
      admin_redirect('system_settings/price_groups');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['price_group'] = $pg_details;
      $this->data['id']          = $id;
      $this->load->view($this->theme . 'settings/edit_price_group', $this->data);
    }
  }

  public function edit_price_range($id = null)
  {
    $this->form_validation->set_rules('name', lang('Range Name'), 'trim|required|alpha_numeric_spaces');

    $pr_details = $this->settings_model->getPriceRangeByID($id);
    if (getPost('name') != $pr_details->name) {
      $this->form_validation->set_rules('name', lang('Range Name'), 'required|is_unique[price_ranges.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('edit_price_range')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/price_ranges');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePriceRange($id, $data)) {
      XSession::set_flash('message', lang('Price Range updated successfully.'));
      admin_redirect('system_settings/price_ranges');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['price_range'] = $pr_details;
      $this->data['id']          = $id;
      $this->load->view($this->theme . 'settings/edit_price_range', $this->data);
    }
  }

  public function edit_tax_rate($id = null)
  {
    $this->form_validation->set_rules('name', lang('name'), 'trim|required');
    $tax_details = $this->settings_model->getTaxRateByID($id);
    if (getPost('name') != $tax_details->name) {
      $this->form_validation->set_rules('name', lang('name'), 'required|is_unique[tax_rates.name]');
    }
    $this->form_validation->set_rules('type', lang('type'), 'required');
    $this->form_validation->set_rules('rate', lang('tax_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPost('name'),
        'code'      => getPost('code'),
        'type'      => getPost('type'),
        'rate'      => getPost('rate'),
      ];
    } elseif (getPost('edit_tax_rate')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/tax_rates');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateTaxRate($id, $data)) { //check to see if we are updateing the customer
      XSession::set_flash('message', lang('tax_rate_updated'));
      admin_redirect('system_settings/tax_rates');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['tax_rate'] = $this->settings_model->getTaxRateByID($id);

      $this->data['id']       = $id;
      $this->load->view($this->theme . 'settings/edit_tax_rate', $this->data);
    }
  }

  public function edit_unit($id)
  {
    $this->form_validation->set_rules('code', lang('code'), 'trim|required');
    $unit_details = $this->site->getUnitByID($id);
    if (getPost('code') != $unit_details->code) {
      $this->form_validation->set_rules('code', lang('code'), 'required|is_unique[units.code]');
    }
    $this->form_validation->set_rules('name', lang('name'), 'trim|required');
    if (getPost('base_unit')) {
      $this->form_validation->set_rules('operator', lang('operator'), 'required');
      $this->form_validation->set_rules('operation_value', lang('operation_value'), 'trim|required');
    }

    if ($this->form_validation->run() == true) {
      $data = [
        'name'            => getPost('name'),
        'code'            => getPost('code'),
        'base_unit'       => getPost('base_unit'),
        'operator'        => getPost('operator'),
        'operation_value' => getPost('operation_value')
      ];
    } elseif (getPost('edit_unit')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/units');
    }

    if ($this->form_validation->run()) {
      if ($this->settings_model->updateUnit($id, $data)) {
        XSession::set_flash('message', lang('unit_updated'));
      } else {
        XSession::set_flash('error', 'Failed to update unit.');
      }
      admin_redirect('system_settings/units');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['unit']       = $unit_details;
      $this->data['base_units'] = $this->site->getAllBaseUnits();
      $this->load->view($this->theme . 'settings/edit_unit', $this->data);
    }
  }

  public function edit_variant($id = null)
  {
    $this->form_validation->set_rules('name', lang('name'), 'trim|required');
    $tax_details = $this->settings_model->getVariantByID($id);
    if (getPost('name') != $tax_details->name) {
      $this->form_validation->set_rules('name', lang('name'), 'required|is_unique[variants.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPost('name')];
    } elseif (getPost('edit_variant')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/variants');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateVariant($id, $data)) {
      XSession::set_flash('message', lang('variant_updated'));
      admin_redirect('system_settings/variants');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['variant']  = $tax_details;
      $this->load->view($this->theme . 'settings/edit_variant', $this->data);
    }
  }

  public function edit_warehouse($warehouseId = null)
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('code'), 'trim|required');
    $this->form_validation->set_rules('address', lang('address'), 'required');
    $this->form_validation->set_rules('geolocation', lang('geolocation'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      $warehouse  = Warehouse::getRow(['id' => $warehouseId]);
      $whJS       = getJSON($warehouse->json_data);

      $whJS->cycle_transfer = getPost('cycle_transfer');
      $whJS->delivery_time  = getPost('delivery_time');
      $whJS->visit_days     = getPost('visit_days');
      $whJS->visit_weeks    = getPost('visit_weeks');

      $data = [
        'code'           => getPost('code'),
        'name'           => getPost('name'),
        'phone'          => getPost('phone'),
        'email'          => getPost('email'),
        'address'        => getPost('address'),
        'geolocation'    => getPost('geolocation'),
        'price_group_id' => getPost('price_group'),
        'active'         => (getPost('active') ?? 0),
        'json_data'      => json_encode($whJS)
      ];

      // if ($_FILES['userfile']['size'] > 0) {
      //   $this->load->library('upload');

      //   $config['upload_path']   = 'assets/uploads/';
      //   $config['allowed_types'] = 'gif|jpg|png|jpeg';
      //   $config['max_size']      = $this->allowed_file_size;
      //   $config['max_width']     = '2000';
      //   $config['max_height']    = '2000';
      //   $config['overwrite']     = false;
      //   $config['encrypt_name']  = true;
      //   $config['max_filename']  = 25;
      //   $this->upload->initialize($config);

      //   if (!$this->upload->do_upload()) {
      //     $error = $this->upload->display_errors();
      //     XSession::set_flash('message', $error);
      //     admin_redirect('system_settings/warehouses');
      //   }

      //   $data['map'] = $this->upload->file_name;

      //   $this->load->helper('file');
      //   $this->load->library('image_lib');
      //   $config['image_library']  = 'gd2';
      //   $config['source_image']   = 'assets/uploads/' . $data['map'];
      //   $config['new_image']      = 'assets/uploads/thumbs/' . $data['map'];
      //   $config['maintain_ratio'] = true;
      //   $config['width']          = 76;
      //   $config['height']         = 76;

      //   $this->image_lib->clear();
      //   $this->image_lib->initialize($config);

      //   if (!$this->image_lib->resize()) {
      //     echo $this->image_lib->display_errors();
      //   }
      // }
    } elseif (getPost('edit_warehouse')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/warehouses');
    }

    if ($this->form_validation->run()) {
      if (Warehouse::update((int)$warehouseId, $data)) { //check to see if we are updateing the customer
        $update_ss = getPost('update_ss');

        if ($update_ss == 1) {
          $all_items    = $this->site->getProducts(['type' => 'standard']);
          $settingsJSON = $this->site->getSettingsJSON();
          $opt          = getPastMonthPeriod($settingsJSON->safety_stock_period);
          $warehouse    = $this->site->getWarehouseByID($warehouseId);

          foreach ($all_items as $item) {
            if (strcasecmp($item->iuse_type, 'sparepart') === 0) continue; // Ignore for sparepart.
            if (strcasecmp($warehouse->code, 'ADV') === 0) continue; // Ignore Advertising.
            if (strcasecmp($warehouse->code, 'LUC') === 0) continue; // Ignore Lucretia.

            $this->site->syncProductQty($item->id, $warehouse->id);
            $this->site->syncWarehouseProductSafetyStock($item->id, $warehouse->id, $opt);
          }
        }

        XSession::set_flash('message', lang('warehouse_updated'));
      } else {
        XSession::set_flash('error', 'Failed to update warehouse.');
      }
      admin_redirect('system_settings/warehouses');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['warehouse']    = $this->settings_model->getWarehouseByID($warehouseId);
      $this->data['warehouse_js'] = json_decode($this->data['warehouse']->json_data);
      $this->data['price_groups'] = $this->settings_model->getAllPriceGroups();
      $this->data['id']           = $warehouseId;
      $this->load->view($this->theme . 'settings/edit_warehouse', $this->data);
    }
  }

  public function email_templates($template = 'credentials')
  {
    $this->form_validation->set_rules('mail_body', lang('mail_message'), 'trim|required');
    $this->load->helper('file');
    $temp_path = is_dir('./themes/' . $this->theme . 'email_templates/');
    $theme     = $temp_path ? $this->theme : 'default';
    if ($this->form_validation->run() == true) {
      $data = $_POST['mail_body'];
      if (write_file('./themes/' . $this->theme . 'email_templates/' . $template . '.html', $data)) {
        XSession::set_flash('message', lang('message_successfully_saved'));
        admin_redirect('system_settings/email_templates#' . $template);
      } else {
        XSession::set_flash('error', lang('failed_to_save_message'));
        admin_redirect('system_settings/email_templates#' . $template);
      }
    } else {
      $this->data['credentials']     = file_get_contents('./themes/' . $this->theme . 'email_templates/credentials.html');
      $this->data['sale']            = file_get_contents('./themes/' . $this->theme . 'email_templates/sale.html');
      $this->data['sale_status']     = file_get_contents('./themes/' . $this->theme . 'email_templates/sale_status.html');
      $this->data['quote']           = file_get_contents('./themes/' . $this->theme . 'email_templates/quote.html');
      $this->data['purchase']        = file_get_contents('./themes/' . $this->theme . 'email_templates/purchase.html');
      $this->data['transfer']        = file_get_contents('./themes/' . $this->theme . 'email_templates/transfer.html');
      $this->data['payment']         = file_get_contents('./themes/' . $this->theme . 'email_templates/payment.html');
      $this->data['forgot_password'] = file_get_contents('./themes/' . $this->theme . 'email_templates/forgot_password.html');
      $this->data['activate_email']  = file_get_contents('./themes/' . $this->theme . 'email_templates/activate_email.html');
      $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('email_templates')]];
      $meta = ['page_title' => lang('email_templates'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/email_templates', $this->data);
    }
  }

  public function expense_categories()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('expense_categories')]];
    $meta = ['page_title' => lang('categories'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/expense_categories', $this->data);
  }

  public function income_categories()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('income_categories')]];
    $meta = ['page_title' => lang('categories'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/income_categories', $this->data);
  }

  public function getBrands()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, image, code, name, slug')
      ->from('brands')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_brand/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang('edit_brand') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_brand') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_brand/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getCategories()
  {
    $print_barcode = anchor('admin/products/print_barcodes/?category=$1', '<i class="fad fa-print"></i>', 'title="' . lang('print_barcodes') . '" class="tip"');

    $this->load->library('datatables');
    $this->datatables
      ->select("categories.id as id, categories.image, categories.code, categories.name, categories.slug, c.name as parent")
      ->from('categories')
      ->join('categories c', 'c.code LIKE categories.parent_code', 'left')
      ->group_by('categories.id')
      ->add_column('Actions', '<div class="text-center">' . $print_barcode . " <a href='" . admin_url('system_settings/edit_category/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang('edit_category') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_category') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_category/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getCurrencies()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, code, name, rate, symbol')
      ->from('currencies')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_currency/$1') . "' class='tip' title='" . lang('edit_currency') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_currency') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_currency/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');

    echo $this->datatables->generate();
  }

  public function getCustomerGroups()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, name, percent')
      ->from('customer_groups')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_customer_group/$1') . "' class='tip' title='" . lang('edit_customer_group') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_customer_group') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_customer_group/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');

    echo $this->datatables->generate();
  }

  public function getExpenseCategories()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, code, name')
      ->from('expense_categories')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_expense_category/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang('edit_expense_category') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_expense_category') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_expense_category/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getIncomeCategories()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, code, name')
      ->from('income_categories')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_income_category/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang('edit_income_category') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_income_category') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_income_category/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getPriceGroups()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, name')
      ->from('price_groups')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/group_product_prices/$1') . "' class='tip' title='" . lang('group_product_prices') . "'><i class=\"fad fa-eye\"></i></a>  <a href='" . admin_url('system_settings/edit_price_group/$1') . "' class='tip' title='" . lang('edit_price_group') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_price_group') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_price_group/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');

    echo $this->datatables->generate();
  }

  public function getPriceRanges()
  { // Get Price Ranges
    $this->load->library('datatables');
    $this->datatables->select('id, name')
      ->from('price_ranges')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_price_range/$1') . "' class='tip' title='" . lang('Edit Price Range') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('Delete Price Range') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_price_range/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getProductPrices($group_id = null)
  {
    if (!$group_id) {
      XSession::set_flash('error', lang('no_price_group_selected'));
      admin_redirect('system_settings/price_groups');
    }

    $pp = "( SELECT product_prices.product_id as product_id,
    product_prices.price as price,
    product_prices.price2 as price2,
    product_prices.price3 as price3,
    product_prices.price4 as price4,
    product_prices.price5 as price5,
    product_prices.price6 as price6
    FROM product_prices WHERE price_group_id = {$group_id} ) PP";

    $this->load->library('datatables');
    $this->datatables->select("products.id as id, products.code as product_code, products.name as product_name,
    PP.price as price,
    PP.price2 as price2,
    PP.price3 as price3,
    PP.price4 as price4,
    PP.price5 as price5,
    PP.price6 as price6 ");
    $this->datatables->from('products');
    $this->datatables->join($pp, 'PP.product_id=products.id', 'left');
    $this->datatables->edit_column('price', '$1__$2', 'id, price');
    $this->datatables->edit_column('price2', '$1__$2', 'id, price2');
    $this->datatables->edit_column('price3', '$1__$2', 'id, price3');
    $this->datatables->edit_column('price4', '$1__$2', 'id, price4');
    $this->datatables->edit_column('price5', '$1__$2', 'id, price5');
    $this->datatables->edit_column('price6', '$1__$2', 'id, price6');
    $this->datatables->add_column('Actions', '<div class="text-center"><button class="btn btn-primary btn-xs form-submit" type="button"><i class="fad fa-check"></i></button></div>', 'id');

    echo $this->datatables->generate();
  }

  public function getTaxRates()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, name, code, rate, type')
      ->from('tax_rates')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_tax_rate/$1') . "' class='tip' title='" . lang('edit_tax_rate') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_tax_rate') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_tax_rate/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');

    echo $this->datatables->generate();
  }

  public function getUnits() // 2021-02-15 14:34, Modified base_unit from int to varchar(10)
  {
    $this->load->library('datatables');
    $this->datatables
      ->select("units.id as id, units.code, units.name, bunits.code as base_unit, units.operator, units.operation_value", false)
      ->from('units')
      ->join('units AS bunits', 'bunits.code LIKE units.base_unit', 'left')
      ->group_by('units.id')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_unit/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang('edit_unit') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_unit') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_unit/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function getVariants()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, name')
      ->from('variants')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_variant/$1') . "' class='tip' title='" . lang('edit_variant') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang('delete_variant') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_variant/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');

    echo $this->datatables->generate();
  }

  public function getWarehouses()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select("warehouses.id as id, map, code, warehouses.name as name,
        price_groups.name as price_group, phone, email, address")
      ->from('warehouses')
      ->join('price_groups', 'price_groups.id=warehouses.price_group_id', 'left')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('system_settings/edit_warehouse/$1') . "' class='tip' title='" . lang('edit_warehouse') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='" . lang('delete_warehouse') . "' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('system_settings/delete_warehouse/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function group_product_prices($group_id = null)
  {
    if (!$group_id) {
      XSession::set_flash('error', lang('no_price_group_selected'));
      admin_redirect('system_settings/price_groups');
    }

    $this->data['price_group'] = $this->settings_model->getPriceGroupByID($group_id);
    $this->data['price_ranges'] = $this->settings_model->getPriceRanges(); // For header only.
    $this->data['error']       = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')],  ['link' => admin_url('system_settings/price_groups'), 'page' => lang('price_groups')], ['link' => '#', 'page' => lang('group_product_prices')]];
    $meta = ['page_title' => lang('group_product_prices'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/group_product_prices', $this->data);
  }

  public function import_brands()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        $this->load->library('upload');
        $config['upload_path']   = 'files/';
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/brands');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen('files/' . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $titles = array_shift($arrResult);
        $keys   = ['name', 'code', 'image'];
        $final  = [];
        foreach ($arrResult as $key => $value) {
          $final[] = array_combine($keys, $value);
        }

        foreach ($final as $csv_ct) {
          if (!$this->settings_model->getBrandByName(trim($csv_ct['name']))) {
            $data[] = [
              'code'  => trim($csv_ct['code']),
              'name'  => trim($csv_ct['name']),
              'image' => trim($csv_ct['image']),
            ];
          }
        }
      }

      // $this->sma->print_arrays($data);
    }

    if ($this->form_validation->run() == true && !empty($data) && $this->settings_model->addBrands($data)) {
      XSession::set_flash('message', lang('brands_added'));
      admin_redirect('system_settings/brands');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->load->view($this->theme . 'settings/import_brands', $this->data);
    }
  }

  public function import_categories()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        $this->load->library('upload');
        $config['upload_path']   = $this->import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);
        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/categories');
        }
        $csv       = $this->upload->file_name;
        $arrResult = [];
        $handle    = fopen($this->import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id  = array_shift($arrResult);
        $titles     = array_shift($arrResult);
        $updated    = '';
        $categories = $subcategories = [];
        if ($header_id[0] != 'ID' || $header_id[1] != 'PRODUCT CATEGORY') {
          XSession::set_flash('error', 'File format is invalid.');
          admin_redirect('products/categories');
        }
        foreach ($arrResult as $key => $value) {
          $code  = trim($value[0]);
          $name  = trim($value[1]);
          $pcode = isset($value[4]) ? trim($value[4]) : null;
          if ($code && $name && trim($value[2])) {
            $category = [
              'code'        => $code,
              'name'        => $name,
              'slug'        => isset($value[2]) ? trim($value[2]) : $code,
              'image'       => isset($value[3]) ? trim($value[3]) : 'no_image.png',
              'parent_id'   => $pcode,
              'description' => isset($value[5]) ? trim($value[5]) : null,
            ];
            if (!empty($pcode) && ($pcategory = $this->settings_model->getCategoryByCode($pcode))) {
              $category['parent_id'] = $pcategory->id;
            }
            if ($c = $this->settings_model->getCategoryByCode($code)) {
              $updated .= '<p>' . lang('category_updated') . ' (' . $code . ')</p>';
              $this->settings_model->updateCategory($c->id, $category);
            } else {
              if ($category['parent_id']) {
                $subcategories[] = $category;
              } else {
                $categories[] = $category;
              }
            }
          }
        }
      }
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCategories($categories, $subcategories)) {
      XSession::set_flash('message', lang('categories_added') . $updated);
      admin_redirect('system_settings/categories');
    } else {
      if ((isset($categories) && empty($categories)) || (isset($subcategories) && empty($subcategories))) {
        if ($updated) {
          XSession::set_flash('message', $updated);
        } else {
          XSession::set_flash('warning', lang('data_x_categories'));
        }
        admin_redirect('system_settings/categories');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->load->view($this->theme . 'settings/import_categories', $this->data);
    }
  }

  public function import_expense_categories()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        checkPath($this->upload_import_path);
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/expense_categories');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id = array_shift($arrResult);
        $titles = array_shift($arrResult);
        $keys = ['no', 'use', 'code', 'name'];
        $csvs = [];
        $data_expense = [];
        $updated = 0;

        if ($header_id[0] != 'EXCA') {
          XSession::set_flash('error', 'File format is invalid.');
          admin_redirect('system_settings/expense_categories');
        }
        foreach ($arrResult as $key => $value) {
          $csvs[] = array_combine($keys, $value);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          if ($xcat = $this->site->getExpenseCategoryByCode(trim($csv['code']))) {
            if ($this->site->updateExpenseCategory($xcat->id, ['name' => $csv['name']])) {
              $updated++;
            }
          } else {
            $data_expense[] = [
              'code' => trim($csv['code']),
              'name' => trim($csv['name']),
            ];
          }
        }
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->settings_model->addExpenseCategories($data_expense)) {
        XSession::set_flash('message', lang('categories_added'));
      } else if ($updated) {
        XSession::set_flash('message', lang('categories_added'));
      } else {
        XSession::set_flash('error', 'Something error');
      }
      admin_redirect('system_settings/expense_categories');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'settings/import_expense_categories', $this->data);
    }
  }

  public function import_income_categories()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');
        $config['upload_path']   = $this->import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/income_categories');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id = array_shift($arrResult);
        $titles = array_shift($arrResult);
        $keys   = ['no', 'use', 'code', 'name'];
        $csvs  = [];
        $data = [];
        $updated = 0;

        if ($header_id[0] != 'INCA') {
          XSession::set_flash('error', 'File format is invalid.');
          admin_redirect('system_settings/income_categories');
        }
        foreach ($arrResult as $key => $value) {
          $csvs[] = array_combine($keys, $value);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;

          if ($incCat = $this->site->getIncomeCategoryByCode(trim($csv['code']))) {
            if ($this->site->updateIncomeCategory($incCat->id, ['name' => $csv['name']])) {
              $updated++;
            }
          } else {
            $data[] = [
              'code' => trim($csv['code']),
              'name' => trim($csv['name']),
            ];
          }
        }
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->addIncomeCategories($data)) {
        XSession::set_flash('message', lang('categories_added'));
      } else if ($updated) {
        XSession::set_flash('message', lang('categories_added'));
      } else {
        XSession::set_flash('error', 'Something error.');
      }
      admin_redirect('system_settings/income_categories');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->load->view($this->theme . 'settings/import_income_categories', $this->data);
    }
  }

  public function import_subcategories()
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        $this->load->library('upload');
        $config['upload_path']   = $this->import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = true;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/categories');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $titles = array_shift($arrResult);
        $keys   = ['code', 'name', 'category_code', 'image'];
        $final  = [];
        foreach ($arrResult as $key => $value) {
          $final[] = array_combine($keys, $value);
        }

        $rw = 2;
        foreach ($final as $csv_ct) {
          if (!$this->settings_model->getSubcategoryByCode(trim($csv_ct['code']))) {
            if ($parent_actegory = $this->settings_model->getCategoryByCode(trim($csv_ct['category_code']))) {
              $data[] = [
                'code'        => trim($csv_ct['code']),
                'name'        => trim($csv_ct['name']),
                'image'       => trim($csv_ct['image']),
                'category_id' => $parent_actegory->id,
              ];
            } else {
              XSession::set_flash('error', lang('check_category_code') . ' (' . $csv_ct['category_code'] . '). ' . lang('category_code_x_exist') . ' ' . lang('line_no') . ' ' . $rw);
              admin_redirect('system_settings/categories');
            }
          }
          $rw++;
        }
      }
    }

    if ($this->form_validation->run() == true && $this->settings_model->addSubCategories($data)) {
      XSession::set_flash('message', lang('subcategories_added'));
      admin_redirect('system_settings/categories');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->load->view($this->theme . 'settings/import_subcategories', $this->data);
    }
  }

  public function import_units()
  {
    $arrResult = [];
    $command = getPost('command');

    if ($command == 'syncGoogleSheet') {
      $gsheet = $this->ridintek->googlesheet();
      $arrResult = $gsheet
        ->setSpreadsheetId('183mGcCxbAsEDAmo2Xs9RgSi_Dn8UlVX4_sqV0TgqM0s')
        ->read('Master!A1:G');
    } else {
      $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

      if ($this->form_validation->run()) {
        if (isset($_FILES['csv_file'])) {
          $this->load->library('upload');
          checkPath($this->upload_import_path);
          $config['upload_path']   = $this->upload_import_path;
          $config['allowed_types'] = $this->upload_csv_type;
          $config['max_size']      = $this->upload_allowed_size;
          $config['overwrite']     = true;
          $config['encrypt_name']  = true;
          $config['max_filename']  = 25;
          $this->upload->initialize($config);

          if (!$this->upload->do_upload('csv_file')) {
            $error = $this->upload->display_errors();
            XSession::set_flash('error', $error);
            admin_redirect('system_settings/units');
          }

          $csv = $this->upload->file_name;

          $handle = fopen($this->import_path . $csv, 'r');
          if ($handle) {
            while (($row = fgetcsv($handle, 5000, ',')) !== false) {
              $arrResult[] = $row;
            }
            fclose($handle);
          }
          unset($csv);
          $csvs = [];
        }
      } else if (getPost('import')) {
        XSession::set_flash('error', 'E1: ' . validation_errors());
        admin_redirect('system_settings/units');
      }
    }

    if ($command == 'syncGoogleSheet' || $this->form_validation->run()) {
      $header_id = array_shift($arrResult);
      $title     = array_shift($arrResult);
      $updated = 0;
      $items   = [];
      $keys    = [
        'no', 'use', 'code', 'name', 'base_unit', 'operator', 'value'
      ];
      if ($header_id[0] != 'PRUN') {
        if ($command == 'syncGoogleSheet') {
          sendJSON(['error' => 1, 'msg' => 'File format is invalid on Googlesheet.']);
        } else {
          XSession::set_flash('error', 'File format is invalid.');
          admin_redirect('system_settings/units');
        }
      }
      foreach ($arrResult as $csv_data) {
        $csvs[] = arrayCombine($keys, $csv_data);
      }
      foreach ($csvs as $csv) {
        if ($csv['use'] != 1) continue;
        $base_unit = $this->site->getUnitByCode($csv['base_unit']);
        $data_unit = [
          'code'            => $csv['code'],
          'name'            => $csv['name'],
          'base_unit'       => ($base_unit ? $base_unit->code : NULL),
          'operator'        => $csv['operator'],
          'operation_value' => $csv['value']
        ];
        if ($data_unit) {
          $units[] = $data_unit;
        }
      } // foreach
    }

    if (($command == 'syncGoogleSheet' || $this->form_validation->run()) && !empty($units)) {
      $added = 0;
      $updated = 0;
      foreach ($units as $un) {
        $unit = $this->site->getUnitByCode($un['code']);
        if ($unit) { // If present, updated it
          if ($this->site->updateUnit($unit->id, $un)) {
            $updated++;
          }
        } else { // Else add unit.
          if ($this->site->addUnit($un)) {
            $added++;
          }
        }
      }

      if ($command == 'syncGoogleSheet') {
        sendJSON(['error' => 0, 'msg' => sprintf(lang('csv_units_imported'), $added, $updated)]);
      } else {
        XSession::set_flash('message', sprintf(lang('csv_units_imported'), $added, $updated));
        admin_redirect('system_settings/units');
      }
    } else {
      if (getPost('import')) {
        XSession::set_flash('error', 'E2: ' . validation_errors());
        admin_redirect('system_settings/units');
      }
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'settings/import_units', $this->data);
    }
  }

  public function import_warehouses()
  {
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = FALSE;
        $config['encrypt_name']  = TRUE;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/warehouses');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        unset($csv);
        $csvs = [];
        $header_id = array_shift($arrResult);
        $title     = array_shift($arrResult);
        $updated = 0;
        $items   = [];
        $keys    = [
          'no', 'use', 'code', 'name', 'price_group', 'phone', 'email', 'address', 'cycle_transfer',
          'delivery_time', 'visit_days', 'visit_weeks'
        ];

        if ($header_id[0] != 'WRHS') {
          XSession::set_flash('error', 'File format is invalid.');
          admin_redirect('system_settings/warehouses');
        }

        foreach ($arrResult as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $price_group = $this->site->getPriceGroupByName($csv['price_group']);

          if (!$price_group) {
            XSession::set_flash('error', sprintf('Price Group [%s] is not found.', $csv['price_group']));
            admin_redirect('system_settings/warehouses');
          }

          $warehouse_data = [
            'code'           => $csv['code'],
            'name'           => $csv['name'],
            'price_group_id' => $price_group->id,
            'phone'          => $csv['phone'],
            'email'          => $csv['email'],
            'address'        => $csv['address'],
            'json_data'      => json_encode([
              'cycle_transfer' => $csv['cycle_transfer'],
              'delivery_time'  => $csv['delivery_time'],
              'visit_days'     => $csv['visit_days'],
              'visit_weeks'    => $csv['visit_weeks']
            ])
          ];

          if ($warehouse_data) {
            $warehouses[] = $warehouse_data;
          }
        } // foreach
      }
    } else if (getPost('import')) {
      XSession::set_flash('error', 'E1: ' . validation_errors());
      admin_redirect('system_settings/warehouses');
    }

    if ($this->form_validation->run() == true && !empty($warehouses)) {
      $added = 0;
      $updated = 0;
      foreach ($warehouses as $wh) {
        $warehouse = $this->site->getWarehouseByCode($wh['code']); // Find warehouse by code
        if ($warehouse) { // If present, updated it
          if ($this->site->updateWarehouse(['id' => $warehouse->id], $wh)) {
            $updated++;
          }
        } else { // Else add warehouse.
          if ($this->site->addWarehouse($wh)) {
            $added++;
          }
        }
      }

      XSession::set_flash('message', sprintf(lang('csv_warehouses_imported'), $added, $updated));
      admin_redirect('system_settings/warehouses');
    } else {
      if (getPost('import')) {
        XSession::set_flash('error', 'E2: ' . validation_errors());
        admin_redirect('system_settings/warehouses');
      }
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'settings/import_warehouses', $this->data);
    }
  }

  public function index()
  {
    $this->form_validation->set_rules('site_name', lang('site_name'), 'trim|required');
    $this->form_validation->set_rules('dateformat', lang('dateformat'), 'trim|required');
    $this->form_validation->set_rules('timezone', lang('timezone'), 'trim|required');
    $this->form_validation->set_rules('mmode', lang('maintenance_mode'), 'trim|required');
    //$this->form_validation->set_rules('logo', lang('logo'), 'trim');
    $this->form_validation->set_rules('iwidth', lang('image_width'), 'trim|numeric|required');
    $this->form_validation->set_rules('iheight', lang('image_height'), 'trim|numeric|required');
    $this->form_validation->set_rules('twidth', lang('thumbnail_width'), 'trim|numeric|required');
    $this->form_validation->set_rules('theight', lang('thumbnail_height'), 'trim|numeric|required');
    $this->form_validation->set_rules('display_all_products', lang('display_all_products'), 'trim|numeric|required');
    $this->form_validation->set_rules('watermark', lang('watermark'), 'trim|required');
    $this->form_validation->set_rules('currency', lang('default_currency'), 'trim|required');
    $this->form_validation->set_rules('email', lang('default_email'), 'trim|required');
    $this->form_validation->set_rules('language', lang('language'), 'trim|required');
    $this->form_validation->set_rules('warehouse', lang('default_warehouse'), 'trim|required');
    $this->form_validation->set_rules('biller', lang('default_biller'), 'trim|required');
    $this->form_validation->set_rules('tax_rate', lang('product_tax'), 'trim|required');
    $this->form_validation->set_rules('tax_rate2', lang('invoice_tax'), 'trim|required');
    $this->form_validation->set_rules('sales_prefix', lang('sales_prefix'), 'trim');
    $this->form_validation->set_rules('quote_prefix', lang('quote_prefix'), 'trim');
    $this->form_validation->set_rules('purchase_prefix', lang('purchase_prefix'), 'trim');
    $this->form_validation->set_rules('transfer_prefix', lang('transfer_prefix'), 'trim');
    $this->form_validation->set_rules('delivery_prefix', lang('delivery_prefix'), 'trim');
    $this->form_validation->set_rules('payment_prefix', lang('payment_prefix'), 'trim');
    $this->form_validation->set_rules('return_prefix', lang('return_prefix'), 'trim');
    $this->form_validation->set_rules('expense_prefix', lang('expense_prefix'), 'trim');
    $this->form_validation->set_rules('income_prefix', lang('income_prefix'), 'trim');
    $this->form_validation->set_rules('mutation_prefix', lang('mutation_prefix'), 'trim');
    $this->form_validation->set_rules('detect_barcode', lang('detect_barcode'), 'trim|required');
    $this->form_validation->set_rules('theme', lang('theme'), 'trim|required');
    $this->form_validation->set_rules('rows_per_page', lang('rows_per_page'), 'trim|required');
    $this->form_validation->set_rules('accounting_method', lang('accounting_method'), 'trim|required');
    $this->form_validation->set_rules('product_serial', lang('product_serial'), 'trim|required');
    $this->form_validation->set_rules('product_discount', lang('product_discount'), 'trim|required');
    $this->form_validation->set_rules('bc_fix', lang('bc_fix'), 'trim|numeric|required');
    $this->form_validation->set_rules('protocol', lang('email_protocol'), 'trim|required');
    if (getPost('protocol') == 'smtp') {
      $this->form_validation->set_rules('smtp_host', lang('smtp_host'), 'required');
      $this->form_validation->set_rules('smtp_user', lang('smtp_user'), 'required');
      $this->form_validation->set_rules('smtp_pass', lang('smtp_pass'), 'required');
      $this->form_validation->set_rules('smtp_port', lang('smtp_port'), 'required');
    }
    if (getPost('protocol') == 'sendmail') {
      $this->form_validation->set_rules('mailpath', lang('mailpath'), 'required');
    }
    $this->form_validation->set_rules('decimals', lang('decimals'), 'trim|required');
    $this->form_validation->set_rules('decimals_sep', lang('decimals_sep'), 'trim|required');
    $this->form_validation->set_rules('thousands_sep', lang('thousands_sep'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $language = getPost('language');

      if ((file_exists(APPPATH . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'sma_lang.php') && is_dir(APPPATH . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language)) || $language == 'english') {
        $lang = $language;
      } else {
        XSession::set_flash('error', lang('language_x_found'));
        admin_redirect('system_settings');
        $lang = 'english';
      }

      $tax1 = (getPost('tax_rate') != 0) ? 1 : 0;
      $tax2 = (getPost('tax_rate2') != 0) ? 1 : 0;

      $data = [
        'site_name' => DEMO ? 'Stock Manager Advance' : getPost('site_name'),
        'rows_per_page'  => getPost('rows_per_page'),
        'dateformat'     => getPost('dateformat'),
        'timezone'       => DEMO ? 'Asia/Kuala_Lumpur' : getPost('timezone'),
        'mmode'          => trim(getPost('mmode')),
        'iwidth'         => getPost('iwidth'),
        'iheight'        => getPost('iheight'),
        'twidth'         => getPost('twidth'),
        'theight'        => getPost('theight'),
        'watermark'      => getPost('watermark'),
        // 'reg_ver' => getPost('reg_ver'),
        // 'allow_reg' => getPost('allow_reg'),
        // 'reg_notification' => getPost('reg_notification'),
        'accounting_method'    => getPost('accounting_method'),
        'default_email'        => DEMO ? 'noreply@tecdiary.com' : getPost('email'),
        'language'             => $lang,
        'default_warehouse'    => getPost('warehouse'),
        'default_tax_rate'     => getPost('tax_rate'),
        'default_tax_rate2'    => getPost('tax_rate2'),
        'sales_prefix'         => getPost('sales_prefix'),
        'quote_prefix'         => getPost('quote_prefix'),
        'purchase_prefix'      => getPost('purchase_prefix'),
        'transfer_prefix'      => getPost('transfer_prefix'),
        'delivery_prefix'      => getPost('delivery_prefix'),
        'payment_prefix'       => getPost('payment_prefix'),
        'ppayment_prefix'      => getPost('ppayment_prefix'),
        'tpayment_prefix'      => getPost('tpayment_prefix'),
        'qa_prefix'            => getPost('qa_prefix'),
        'return_prefix'        => getPost('return_prefix'),
        'returnp_prefix'       => getPost('returnp_prefix'),
        'expense_prefix'       => getPost('expense_prefix'),
        'income_prefix'        => getPost('income_prefix'),
        'mutation_prefix'      => getPost('mutation_prefix'),
        'auto_detect_barcode'  => trim(getPost('detect_barcode')),
        'theme'                => trim(getPost('theme')),
        'product_serial'       => getPost('product_serial'),
        'customer_group'       => getPost('customer_group'),
        'product_expiry'       => getPost('product_expiry'),
        'product_discount'     => getPost('product_discount'),
        'default_currency'     => getPost('currency'),
        'bc_fix'               => getPost('bc_fix'),
        'tax1'                 => $tax1,
        'tax2'                 => $tax2,
        'overselling'          => getPost('restrict_sale'),
        'reference_format'     => getPost('reference_format'),
        'racks'                => getPost('racks'),
        'attributes'           => getPost('attributes'),
        'restrict_calendar'    => getPost('restrict_calendar'),
        'captcha'              => getPost('captcha'),
        'item_addition'        => getPost('item_addition'),
        'protocol'             => DEMO ? 'mail' : getPost('protocol'),
        'mailpath'             => getPost('mailpath'),
        'smtp_host'            => getPost('smtp_host'),
        'smtp_user'            => getPost('smtp_user'),
        'smtp_port'            => getPost('smtp_port'),
        'smtp_crypto'          => getPost('smtp_crypto') ? getPost('smtp_crypto') : null,
        'decimals'             => getPost('decimals'),
        'decimals_sep'         => getPost('decimals_sep'),
        'thousands_sep'        => getPost('thousands_sep'),
        'default_biller'       => getPost('biller'),
        'invoice_view'         => getPost('invoice_view'),
        'rtl'                  => getPost('rtl'),
        'each_spent'           => getPost('each_spent') ? getPost('each_spent') : null,
        'ca_point'             => getPost('ca_point') ? getPost('ca_point') : null,
        'each_sale'            => getPost('each_sale') ? getPost('each_sale') : null,
        'sa_point'             => getPost('sa_point') ? getPost('sa_point') : null,
        'sac'                  => getPost('sac'),
        'qty_decimals'         => getPost('qty_decimals'),
        'display_all_products' => getPost('display_all_products'),
        'display_symbol'       => getPost('display_symbol'),
        'symbol'               => getPost('symbol'),
        'remove_expired'       => getPost('remove_expired'),
        'barcode_separator'    => getPost('barcode_separator'),
        'set_focus'            => getPost('set_focus'),
        'disable_editing'      => getPost('disable_editing'),
        'price_group'          => getPost('price_group'),
        'barcode_img'          => getPost('barcode_renderer'),
        'update_cost'          => getPost('update_cost'),
        'apis'                 => getPost('apis'),
        'pdf_lib'              => getPost('pdf_lib'),
        'state'                => getPost('state'),
        'settings_json'        => json_encode([
          'min_dp'              => filterDecimal(getPost('min_dp') ?? 0),
          'min_dp_percent'      => filterDecimal(getPost('min_dp_percent') ?? 0),
          'safety_stock_period' => filterDecimal(getPost('safety_stock_period') ?? 0),
          'qms_expired_time'    => filterDecimal(getPost('qms_expired_time') ?? 0)
        ])
      ];
      if (getPost('smtp_pass')) {
        $data['smtp_pass'] = getPost('smtp_pass');
      }
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateSetting($data)) {
      if (!DEMO && TIMEZONE != $data['timezone']) {
        if (!$this->write_index($data['timezone'])) {
          XSession::set_flash('error', lang('setting_updated_timezone_failed'));
          admin_redirect('system_settings');
        }
      }

      XSession::set_flash('message', lang('setting_updated'));
      admin_redirect('system_settings');
    } else {
      $settings                      = $this->site->getSettings();
      $this->data['error']           = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['billers']         = $this->site->getAllBillers();
      $this->data['settings']        = $settings;
      $this->data['settings_json']   = json_decode($settings->settings_json);
      $this->data['currencies']      = $this->settings_model->getAllCurrencies();
      $this->data['date_formats']    = $this->settings_model->getDateFormats();
      $this->data['tax_rates']       = $this->settings_model->getAllTaxRates();
      $this->data['customer_groups'] = $this->settings_model->getAllCustomerGroups();
      $this->data['price_groups']    = $this->settings_model->getAllPriceGroups();
      $this->data['warehouses']      = $this->site->getWarehouses();
      $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('system_settings')]];
      $meta = ['page_title' => lang('system_settings'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/index', $this->data);
    }
  }

  public function install_update($file, $m_version, $version)
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->helper('update');
    save_remote_file($file . '.zip');
    $this->sma->unzip('./files/updates/' . $file . '.zip');
    if ($m_version) {
      $this->load->library('migration');
      if (!$this->migration->latest()) {
        XSession::set_flash('error', $this->migration->error_string());
        admin_redirect('system_settings/updates');
      }
    }
    $this->db->update('settings', ['version' => $version, 'update' => 0], ['setting_id' => 1]);
    unlink('./files/updates/' . $file . '.zip');
    XSession::set_flash('success', lang('update_done'));
    admin_redirect('system_settings/updates');
  }

  public function mutasibank()
  {
    $this->form_validation->set_rules('active', $this->lang->line('activate'), 'trim');

    if ($this->form_validation->run() == true) {
      $api_keys = getPost('api_keys');
      $keys = [];
      foreach ($api_keys as $key) {
        if (!empty($key)) $keys[] = $key;
      }
      $data = [
        'active'   => getPost('active'),
        'api_keys' => json_encode($keys)
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateMutasibank($data)) {
      XSession::set_flash('message', lang('mutasibank_updated'));
      admin_redirect('system_settings/mutasibank');
    } else {
      $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

      $this->data['mutasibank'] = $this->settings_model->getMutasibankSettings();

      $bc = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('system_settings'), 'page' => lang('system_settings')],
        ['link' => '#', 'page' => lang('mutasibank_settings')]
      ];
      $meta = ['page_title' => lang('mutasibank'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/mutasibank', $this->data);
    }
  }

  public function paypal()
  {
    $this->form_validation->set_rules('active', $this->lang->line('activate'), 'trim');
    $this->form_validation->set_rules('account_email', $this->lang->line('paypal_account_email'), 'trim|valid_email');
    if (getPost('active')) {
      $this->form_validation->set_rules('account_email', $this->lang->line('paypal_account_email'), 'required');
    }
    $this->form_validation->set_rules('fixed_charges', $this->lang->line('fixed_charges'), 'trim');
    $this->form_validation->set_rules('extra_charges_my', $this->lang->line('extra_charges_my'), 'trim');
    $this->form_validation->set_rules('extra_charges_other', $this->lang->line('extra_charges_others'), 'trim');

    if ($this->form_validation->run() == true) {
      $data = [
        'active'         => getPost('active'),
        'account_email'       => getPost('account_email'),
        'fixed_charges'       => getPost('fixed_charges'),
        'extra_charges_my'    => getPost('extra_charges_my'),
        'extra_charges_other' => getPost('extra_charges_other'),
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePaypal($data)) {
      XSession::set_flash('message', $this->lang->line('paypal_setting_updated'));
      admin_redirect('system_settings/paypal');
    } else {
      $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

      $this->data['paypal'] = $this->settings_model->getPaypalSettings();

      $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('paypal_settings')]];
      $meta = ['page_title' => lang('paypal_settings'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/paypal', $this->data);
    }
  }

  public function permissions($group_id = null)
  {
    $this->form_validation->set_rules('group', lang('group'), 'is_natural_no_zero');
    if ($this->form_validation->run() == true) {
      $data = [
        'products-index'             => getPost('products-index'),
        'products-edit'              => getPost('products-edit'),
        'products-add'               => getPost('products-add'),
        'products-delete'            => getPost('products-delete'),
        'products-cost'              => getPost('products-cost'),
        'products-price'             => getPost('products-price'),
        'customers-index'            => getPost('customers-index'),
        'customers-edit'             => getPost('customers-edit'),
        'customers-add'              => getPost('customers-add'),
        'customers-delete'           => getPost('customers-delete'),
        'suppliers-index'            => getPost('suppliers-index'),
        'suppliers-edit'             => getPost('suppliers-edit'),
        'suppliers-add'              => getPost('suppliers-add'),
        'suppliers-delete'           => getPost('suppliers-delete'),
        'sales-index'                => getPost('sales-index'),
        'sales-edit'                 => getPost('sales-edit'),
        'sales-add'                  => getPost('sales-add'),
        'sales-delete'               => getPost('sales-delete'),
        'sales-email'                => getPost('sales-email'),
        'sales-pdf'                  => getPost('sales-pdf'),
        'purchases-index'            => getPost('purchases-index'),
        'purchases-edit'             => getPost('purchases-edit'),
        'purchases-add'              => getPost('purchases-add'),
        'purchases-delete'           => getPost('purchases-delete'),
        'purchases-email'            => getPost('purchases-email'),
        'purchases-pdf'              => getPost('purchases-pdf'),
        'transfers-index'            => getPost('transfers-index'),
        'transfers-edit'             => getPost('transfers-edit'),
        'transfers-add'              => getPost('transfers-add'),
        'transfers-delete'           => getPost('transfers-delete'),
        'transfers-email'            => getPost('transfers-email'),
        'transfers-pdf'              => getPost('transfers-pdf'),
        'reports-quantity_alerts'    => getPost('reports-quantity_alerts'),
        'reports-expiry_alerts'      => getPost('reports-expiry_alerts'),
        'reports-products'           => getPost('reports-products'),
        'reports-daily_sales'        => getPost('reports-daily_sales'),
        'reports-monthly_sales'      => getPost('reports-monthly_sales'),
        'reports-payments'           => getPost('reports-payments'),
        'reports-sales'              => getPost('reports-sales'),
        'reports-purchases'          => getPost('reports-purchases'),
        'reports-customers'          => getPost('reports-customers'),
        'reports-suppliers'          => getPost('reports-suppliers'),
        'sales-payments'             => getPost('sales-payments'),
        'purchases-payments'         => getPost('purchases-payments'),
        'purchases-expenses'         => getPost('purchases-expenses'),
        'products-adjustments'       => getPost('products-adjustments'),
        'bulk_actions'               => getPost('bulk_actions'),
        'customers-deposits'         => getPost('customers-deposits'),
        'customers-delete_deposit'   => getPost('customers-delete_deposit'),
        'products-barcode'           => getPost('products-barcode'),
        'purchases-return_purchases' => getPost('purchases-return_purchases'),
        'reports-expenses'           => getPost('reports-expenses'),
        'reports-daily_purchases'    => getPost('reports-daily_purchases'),
        'reports-monthly_purchases'  => getPost('reports-monthly_purchases'),
        'products-stock_count'       => getPost('products-stock_count'),
        'edit_price'                 => getPost('edit_price'),
        'reports-tax'                => getPost('reports-tax'),
        'permissions_json'           => json_encode([ // Extended permissions.
          'banks-add'                 => getPost('banks-add'),
          'banks-delete'              => getPost('banks-delete'),
          'banks-edit'                => getPost('banks-edit'),
          'banks-index'               => getPost('banks-index'),
          'banks-reconciliation'      => getPost('banks-reconciliation'),
          'reports-daily_performance' => getPost('reports-daily_performance'),
          'dashboard-chart'           => getPost('dashboard-chart'),
          'mutations-add'             => getPost('mutations-add'),
          'mutations-delete'          => getPost('mutations-delete'),
          'mutations-edit'            => getPost('mutations-edit'),
          'mutations-index'           => getPost('mutations-index'),
          'mutations-manual'          => getPost('mutations-manual'),
          'edit-system'               => getPost('edit-system'),
          'expenses-add'              => getPost('expenses-add'),
          'expenses-approval'         => getPost('expenses-approval'),
          'expenses-delete'           => getPost('expenses-delete'),
          'expenses-edit'             => getPost('expenses-edit'),
          'expenses-index'            => getPost('expenses-index'),
          'expenses-payment'          => getPost('expenses-payment'),
          'googlereview-add'          => getPost('googlereview-add'),
          'googlereview-delete'       => getPost('googlereview-delete'),
          'googlereview-edit'         => getPost('googlereview-edit'),
          'googlereview-view'         => getPost('googlereview-view'),
          'incomes-add'               => getPost('incomes-add'),
          'incomes-delete'            => getPost('incomes-delete'),
          'incomes-edit'              => getPost('incomes-edit'),
          'incomes-index'             => getPost('incomes-index'),
          'internal_uses-add'         => getPost('internal_uses-add'),
          'internal_uses-approval'    => getPost('internal_uses-approval'),
          'internal_uses-delete'      => getPost('internal_uses-delete'),
          'internal_uses-edit'        => getPost('internal_uses-edit'),
          'internal_uses-index'       => getPost('internal_uses-index'),
          'internal_uses-consumable'  => getPost('internal_uses-consumable'),
          'internal_uses-cmreport'    => getPost('internal_uses-cmreport'),
          'internal_uses-sparepart'   => getPost('internal_uses-sparepart'),
          'machine-assign'            => getPost('machine-assign'),
          'machine-report_delete'     => getPost('machine-report_delete'),
          'sales-add_qms_only'        => getPost('sales-add_qms_only'),
          'sales-edit_operator'       => getPost('sales-edit_operator'),
          'sales-edit_price'          => getPost('sales-edit_price'),
          'sales-item_status'         => getPost('sales-item_status'),
          'sales-skip_validation'     => getPost('sales-skip_validation'),
          'sales-tb'                  => getPost('sales-tb'),
          'notify-add'                => getPost('notify-add'),
          'notify-delete'             => getPost('notify-delete'),
          'notify-edit'               => getPost('notify-edit'),
          'notify-index'              => getPost('notify-index'),
          'operators-checkpoint'      => getPost('operators-checkpoint'),
          'operators-orders'          => getPost('operators-orders'),
          'trackingpod-add'           => getPost('trackingpod-add'),
          'trackingpod-delete'        => getPost('trackingpod-delete'),
          'trackingpod-edit'          => getPost('trackingpod-edit'),
          'trackingpod-index'         => getPost('trackingpod-index'),
          'transfers-add'             => getPost('transfers-add'),
          'transfers-approval'        => getPost('transfers-approval'),
          'transfers-delete'          => getPost('transfers-delete'),
          'transfers-edit'            => getPost('transfers-edit'),
          'transfers-index'           => getPost('transfers-index'),
          'transfers-payment'         => getPost('transfers-payment'),
          'transfers-received'        => getPost('transfers-received'),
          'transfers-sent'            => getPost('transfers-sent'),
          'purchases-add'             => getPost('purchases-add'),
          'purchases-approval'        => getPost('purchases-approval'),
          'purchases-delete'          => getPost('purchases-delete'),
          'purchases-edit'            => getPost('purchases-edit'),
          'purchases-index'           => getPost('purchases-index'),
          'purchases-other_warehouse' => getPost('purchases-other_warehouse'),
          'payments-add'              => getPost('payments-add'),
          'payments-delete'           => getPost('payments-delete'),
          'payments-edit'             => getPost('payments-edit'),
          'payments-index'            => getPost('payments-index'),
          'products-categories'       => getPost('products-categories'),
          'products-history'          => getPost('products-history'),
          'products-mutation_add'     => getPost('products-mutation_add'),
          'products-mutation_delete'  => getPost('products-mutation_delete'),
          'products-mutation_edit'    => getPost('products-mutation_edit'),
          'products-mutation_view'    => getPost('products-mutation_view'),
          'products-mutation_status'  => getPost('products-mutation_status'),
          'products-quantity'         => getPost('products-quantity'),
          'products-std_qty'          => getPost('products-std_qty'),
          'products-so_quantity'      => getPost('products-so_quantity'),
          'products-stock_opname'     => getPost('products-stock_opname'),
          'products-transfer_view'    => getPost('products-transfer_view'),
          'products-transfer_add'     => getPost('products-transfer_add'),
          'products-transfer_delete'  => getPost('products-transfer_delete'),
          'products-transfer_edit'    => getPost('products-transfer_edit'),
          'products-transfer_status'  => getPost('products-transfer_status'),
          'reports-income_statement'  => getPost('reports-income_statement'),
          'reports-inventory_balance' => getPost('reports-inventory_balance'),
          'reports-printerp'          => getPost('reports-printerp'),
          'users-edit'                => getPost('users-edit'),
          'validations-add'           => getPost('validations-add'),
          'validations-cancel'        => getPost('validations-cancel'),
          'validations-delete'        => getPost('validations-delete'),
          'validations-edit'          => getPost('validations-edit'),
          'validations-index'         => getPost('validations-index'),
          'validations-manual'        => getPost('validations-manual'),
          'warehouses-add'            => getPost('warehouses-add'),
          'warehouses-delete'         => getPost('warehouses-delete'),
          'warehouses-edit'           => getPost('warehouses-edit'),
          'warehouses-index'          => getPost('warehouses-index'),
        ])
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePermissions($group_id, $data)) {
      XSession::set_flash('message', lang('group_permissions_updated'));
      redirect_to($_SERVER['HTTP_REFERER']);
    } else {
      $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
      $permissions = $this->settings_model->getGroupPermissions($group_id);

      $this->data['id']    = $group_id;
      $this->data['p']     = $this->settings_model->getGroupPermissions($group_id);
      $pj                  = (!empty($permissions) ? (property_exists($permissions, 'permissions_json') ? $permissions->permissions_json : NULL) : NULL);
      $this->data['pj']    = (!empty($pj) ? json_decode($pj) : NULL);
      $this->data['gp']    = $this->site->getGroupPermissions($group_id);
      unset($pj);
      $this->data['group'] = $this->settings_model->getGroupByID($group_id);

      $bc   = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('system_settings'), 'page' => lang('system_settings')],
        ['link' => admin_url('system_settings/user_groups'), 'page' => lang('groups')],
        ['link' => '#', 'page' => lang('group_permissions')]
      ];
      $meta = ['page_title' => lang('group_permissions'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/permissions', $this->data);
    }
  }

  public function price_groups()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('price_groups')]];
    $meta = ['page_title' => lang('price_groups'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/price_groups', $this->data);
  }

  public function price_ranges()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('price_ranges')]];
    $meta = ['page_title' => lang('price_ranges'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/price_ranges', $this->data);
  }

  public function units()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('units')]];
    $meta = ['page_title' => lang('units'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/units', $this->data);
  }

  public function update_prices_csv($group_id = null) // MODIFIED 2020-03-19 11:00
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (DEMO) {
        XSession::set_flash('message', lang('disabled_in_demo'));
        admin_redirect('welcome');
      }

      if (isset($_FILES['userfile'])) {
        $this->load->library('upload');
        $config['upload_path']   = 'files/';
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          XSession::set_flash('error', $error);
          admin_redirect('system_settings/group_product_prices/' . $group_id);
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen('files/' . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $titles = array_shift($arrResult);

        $keys = ['code', 'price', 'price2', 'price3', 'price4', 'price5', 'price6'];

        $final = [];

        foreach ($arrResult as $key => $value) {
          $final[] = array_combine($keys, $value);
        }
        $rw = 2;
        foreach ($final as $csv_pr) {
          if ($product = $this->site->getProductByCode(trim($csv_pr['code']))) {
            $data[] = [
              'product_id'     => $product->id,
              'price'          => $csv_pr['price'],
              'price2'          => $csv_pr['price2'],
              'price3'          => $csv_pr['price3'],
              'price4'          => $csv_pr['price4'],
              'price5'          => $csv_pr['price5'],
              'price6'          => $csv_pr['price6'],
              'price_group_id' => $group_id,
            ];
          } else {
            XSession::set_flash('message', lang('check_product_code') . ' (' . $csv_pr['code'] . '). ' . lang('code_x_exist') . ' ' . lang('line_no') . ' ' . $rw);
            admin_redirect('system_settings/group_product_prices/' . $group_id);
          }
          $rw++;
        }
      }
    } elseif (getPost('update_price')) {
      XSession::set_flash('error', validation_errors());
      admin_redirect('system_settings/group_product_prices/' . $group_id);
    }

    if ($this->form_validation->run() == true && !empty($data)) {
      $this->settings_model->updateGroupPrices($data);
      XSession::set_flash('message', lang('price_updated'));
      admin_redirect('system_settings/group_product_prices/' . $group_id);
    } else {
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];
      $this->data['group']    = $this->site->getPriceGroupByID($group_id);
      $this->load->view($this->theme . 'settings/update_price', $this->data);
    }
  }

  public function update_product_group_price($group_id = null)
  {
    if (!$group_id) {
      sendJSON(['status' => 0]);
    }

    $product_id = getPost('product_id', true);
    $price      = getPost('price', true);
    $price2      = getPost('price2', true);
    $price3      = getPost('price3', true);
    $price4      = getPost('price4', true);
    $price5      = getPost('price5', true);
    $price6      = getPost('price6', true);

    if (!empty($product_id) && !empty($price)) {
      if ($this->settings_model->setProductPriceForPriceGroup(
        $product_id,
        $group_id,
        $price,
        $price2,
        $price3,
        $price4,
        $price5,
        $price6
      )) {
        sendJSON(['status' => 1]);
      }
    }

    sendJSON(['status' => 0]);
  }

  public function updates()
  {
    if (DEMO) {
      XSession::set_flash('warning', lang('disabled_in_demo'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->form_validation->set_rules('purchase_code', lang('purchase_code'), 'required');
    $this->form_validation->set_rules('envato_username', lang('envato_username'), 'required');
    if ($this->form_validation->run() == true) {
      $this->db->update('settings', ['purchase_code' => getPost('purchase_code', true), 'envato_username' => getPost('envato_username', true)], ['setting_id' => 1]);
      admin_redirect('system_settings/updates');
    } else {
      $fields = ['version' => $this->Settings->version, 'code' => $this->Settings->purchase_code, 'username' => $this->Settings->envato_username, 'site' => base_url()];
      $this->load->helper('update');
      $protocol              = is_https() ? 'https://' : 'http://';
      $updates               = get_remote_contents($protocol . 'api.tecdiary.com/v1/update/', $fields);
      $this->data['updates'] = json_decode($updates);
      $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('updates')]];
      $meta = ['page_title' => lang('updates'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('settings/updates', $this->data);
    }
  }

  public function user_groups()
  {
    if (!$this->Owner) {
      XSession::set_flash('error', lang('access_denied'));
      admin_redirect('auth');
    }

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

    $this->data['groups'] = $this->settings_model->getGroups();
    $bc = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('groups')]];
    $meta = ['page_title' => lang('groups'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/user_groups', $this->data);
  }

  public function variants()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('variants')]];
    $meta = ['page_title' => lang('variants'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/variants', $this->data);
  }

  public function warehouses()
  {
    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('system_settings'), 'page' => lang('system_settings')], ['link' => '#', 'page' => lang('warehouses')]];
    $meta = ['page_title' => lang('warehouses'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('settings/warehouses', $this->data);
  }

  public function write_index($timezone)
  {
    $template_path = FCPATH . 'assets/config_dumps/index.php';
    $output_path   = FCPATH . 'index.php';
    $index_file    = file_get_contents($template_path);
    $new           = str_replace('%TIMEZONE%', $timezone, $index_file);
    $handle        = fopen($output_path, 'w+');
    @chmod($output_path, 0777);

    if (is_writable($output_path)) {
      if (fwrite($handle, $new)) {
        @chmod($output_path, 0644);
        return true;
      }
      @chmod($output_path, 0644);
      return false;
    }
    @chmod($output_path, 0644);
    return false;
  }
}
