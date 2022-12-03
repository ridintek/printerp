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
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect('admin');
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
        'name'        => getPOST('name'),
        'code'        => getPOST('code'),
        'slug'        => getPOST('slug'),
        'description' => getPOST('description'),
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
    } elseif (getPOST('add_brand')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/brands');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addBrand($data)) {
      $this->session->set_flashdata('message', lang('brand_added'));
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
        'name'        => getPOST('name'),
        'code'        => getPOST('code'),
        'slug'        => getPOST('slug'),
        'description' => getPOST('description'),
        'parent_id'   => getPOST('parent'),
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
    } elseif (getPOST('add_category')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCategory($data)) {
      $this->session->set_flashdata('message', lang('category_added'));
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
        'code'   => getPOST('code'),
        'name'        => getPOST('name'),
        'rate'        => getPOST('rate'),
        'symbol'      => getPOST('symbol'),
        'auto_update' => getPOST('auto_update') ? getPOST('auto_update') : 0,
      ];
    } elseif (getPOST('add_currency')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/currencies');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCurrency($data)) { //check to see if we are creating the customer
      $this->session->set_flashdata('message', lang('currency_added'));
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
        'name' => getPOST('name'),
        'percent'   => getPOST('percent'),
      ];
    } elseif (getPOST('add_customer_group')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/customer_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addCustomerGroup($data)) {
      $this->session->set_flashdata('message', lang('customer_group_added'));
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
        'name' => getPOST('name'),
        'code' => getPOST('code'),
      ];
    } elseif (getPOST('add_expense_category')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/expense_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addExpenseCategory($data)) {
      $this->session->set_flashdata('message', lang('expense_category_added'));
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
        'name' => getPOST('name'),
        'code' => getPOST('code'),
      ];
    } elseif (getPOST('add_income_category')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/income_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addIncomeCategory($data)) {
      $this->session->set_flashdata('message', lang('income_category_added'));
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
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('add_price_group')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/price_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addPriceGroup($data)) {
      $this->session->set_flashdata('message', lang('price_group_added'));
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
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('add_price_range')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/price_ranges');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addPriceRange($data)) {
      $this->session->set_flashdata('message', lang('Price range added successfully.'));
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
        'name' => getPOST('name'),
        'code'      => getPOST('code'),
        'type'      => getPOST('type'),
        'rate'      => getPOST('rate'),
      ];
    } elseif (getPOST('add_tax_rate')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/tax_rates');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addTaxRate($data)) {
      $this->session->set_flashdata('message', lang('tax_rate_added'));
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
    if (getPOST('base_unit')) {
      $this->form_validation->set_rules('operator', lang('operator'), 'required');
      $this->form_validation->set_rules('operation_value', lang('operation_value'), 'trim|required');
    }

    if ($this->form_validation->run() == true) {
      $data = [
        'name'            => getPOST('name'),
        'code'            => getPOST('code'),
        'base_unit'       => getPOST('base_unit') ?? NULL,
        'operator'        => getPOST('base_unit') ?? NULL,
        'operation_value' => getPOST('operation_value') ?? NULL,
      ];
    } elseif (getPOST('add_unit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/units');
    }

    if ($this->form_validation->run()) {
      if ($this->settings_model->addUnit($data)) {
        $this->session->set_flashdata('message', lang('unit_added'));
      } else {
        $this->session->set_flashdata('error', 'Unit failed to add.');
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
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('add_variant')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/variants');
    }

    if ($this->form_validation->run() == true && $this->settings_model->addVariant($data)) {
      $this->session->set_flashdata('message', lang('variant_added'));
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
      //     $this->session->set_flashdata('message', $error);
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
        'code'           => getPOST('code'),
        'name'           => getPOST('name'),
        'phone'          => getPOST('phone'),
        'email'          => getPOST('email'),
        'address'        => getPOST('address'),
        'geolocation'    => getPOST('geolocation'),
        'price_group_id' => getPOST('price_group'),
        'json_data' => json_encode([
          'cycle_transfer' => getPOST('cycle_transfer'),
          'delivery_time'  => getPOST('delivery_time'),
          'visit_days'     => getPOST('visit_days'),
          'visit_weeks'    => getPOST('visit_weeks')
        ])
      ];
    } elseif (getPOST('add_warehouse')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/warehouses');
    }

    if ($this->form_validation->run()) {
      if ($this->site->addWarehouse($data)) {
        $this->session->set_flashdata('message', lang('warehouse_added'));
      } else {
        $this->session->set_flashdata('error', 'Failed to add warehouse.');
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
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
    $this->session->set_flashdata('messgae', lang('db_saved'));
    admin_redirect('system_settings/backups');
  }

  public function backup_files()
  {
    if (DEMO) {
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $name = 'file-backup-' . date('Y-m-d-H-i-s');
    $this->sma->zip('./', './files/backups/', $name);
    $this->session->set_flashdata('messgae', lang('backup_saved'));
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
        }
        $photo = $this->upload->file_name;
      }

      $this->session->set_flashdata('message', lang('logo_uploaded'));
      redirect($_SERVER['HTTP_REFERER']);
    } elseif (getPOST('upload_logo')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      $this->data['error']    = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->load->view($this->theme . 'settings/change_logo', $this->data);
    }
  }

  public function create_group()
  {
    $this->form_validation->set_rules('group_name', lang('group_name'), 'required|alpha_dash|is_unique[groups.name]');

    if ($this->form_validation->run() == true) {
      $data = ['name' => strtolower(getPOST('group_name')), 'description' => getPOST('description')];
    } elseif (getPOST('create_group')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/user_groups');
    }

    if ($this->form_validation->run() == true && ($new_group_id = $this->settings_model->addGroup($data))) {
      $this->session->set_flashdata('message', lang('group_added'));
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    unlink('./files/backups/' . $zipfile . '.zip');
    $this->session->set_flashdata('messgae', lang('backup_deleted'));
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    unlink('./files/backups/' . $dbfile . '.txt');
    $this->session->set_flashdata('messgae', lang('db_deleted'));
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
      $this->session->set_flashdata('error', lang('group_x_b_deleted'));
      admin_redirect('system_settings/user_groups');
    }

    if ($this->settings_model->deleteGroup($id)) {
      $this->session->set_flashdata('message', lang('group_deleted'));
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->helper('download');
    force_download('./files/backups/' . $zipfile . '.zip', null);
    exit();
  }

  public function download_database($dbfile)
  {
    if (DEMO) {
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
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
    if (getPOST('name') != $brand_details->name) {
      $this->form_validation->set_rules('name', lang('brand_name'), 'required|is_unique[brands.name]');
    }
    $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash');
    if (getPOST('slug') != $brand_details->slug) {
      $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash|is_unique[brands.slug]');
    }
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPOST('name'),
        'code'        => getPOST('code'),
        'slug'        => getPOST('slug'),
        'description' => getPOST('description'),
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
    } elseif (getPOST('edit_brand')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/brands');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateBrand($id, $data)) {
      $this->session->set_flashdata('message', lang('brand_updated'));
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
    if (getPOST('code') != $pr_details->code) {
      $this->form_validation->set_rules('code', lang('category_code'), 'required|is_unique[categories.code]');
    }
    $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash');
    if (getPOST('slug') != $pr_details->slug) {
      $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash|is_unique[categories.slug]');
    }
    $this->form_validation->set_rules('name', lang('category_name'), 'required|min_length[3]');
    $this->form_validation->set_rules('userfile', lang('category_image'), 'xss_clean');
    $this->form_validation->set_rules('description', lang('description'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $data = [
        'name'        => getPOST('name'),
        'code'        => getPOST('code'),
        'slug'        => getPOST('slug'),
        'description' => getPOST('description'),
        'parent_id'   => getPOST('parent'),
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
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
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
    } elseif (getPOST('edit_category')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCategory($id, $data)) {
      $this->session->set_flashdata('message', lang('category_updated'));
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
    if (getPOST('code') != $cur_details->code) {
      $this->form_validation->set_rules('code', lang('currency_code'), 'required|is_unique[currencies.code]');
    }
    $this->form_validation->set_rules('name', lang('currency_name'), 'required');
    $this->form_validation->set_rules('rate', lang('exchange_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'code'   => getPOST('code'),
        'name'        => getPOST('name'),
        'rate'        => getPOST('rate'),
        'symbol'      => getPOST('symbol'),
        'auto_update' => getPOST('auto_update') ? getPOST('auto_update') : 0,
      ];
    } elseif (getPOST('edit_currency')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/currencies');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCurrency($id, $data)) { //check to see if we are updateing the customer
      $this->session->set_flashdata('message', lang('currency_updated'));
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
    if (getPOST('name') != $pg_details->name) {
      $this->form_validation->set_rules('name', lang('group_name'), 'required|is_unique[tax_rates.name]');
    }
    $this->form_validation->set_rules('percent', lang('group_percentage'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPOST('name'),
        'percent'   => getPOST('percent'),
      ];
    } elseif (getPOST('edit_customer_group')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/customer_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateCustomerGroup($id, $data)) {
      $this->session->set_flashdata('message', lang('customer_group_updated'));
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
    if (getPOST('code') != $category->code) {
      $this->form_validation->set_rules('code', lang('category_code'), 'required|is_unique[expense_categories.code]');
    }
    $this->form_validation->set_rules('name', lang('category_name'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'code' => getPOST('code'),
        'name' => getPOST('name'),
      ];
    } elseif (getPOST('edit_expense_category')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/expense_categories');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateExpenseCategory($id, $data)) {
      $this->session->set_flashdata('message', lang('expense_category_updated'));
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
      $data         = ['name' => strtolower(getPOST('group_name')), 'description' => getPOST('description')];
      $group_update = $this->settings_model->updateGroup($id, $data);

      if ($group_update) {
        $this->session->set_flashdata('message', lang('group_udpated'));
      } else {
        $this->session->set_flashdata('error', lang('attempt_failed'));
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
    if (getPOST('name') != $pg_details->name) {
      $this->form_validation->set_rules('name', lang('group_name'), 'required|is_unique[price_groups.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('edit_price_group')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/price_groups');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePriceGroup($id, $data)) {
      $this->session->set_flashdata('message', lang('price_group_updated'));
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
    if (getPOST('name') != $pr_details->name) {
      $this->form_validation->set_rules('name', lang('Range Name'), 'required|is_unique[price_ranges.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('edit_price_range')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/price_ranges');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePriceRange($id, $data)) {
      $this->session->set_flashdata('message', lang('Price Range updated successfully.'));
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
    if (getPOST('name') != $tax_details->name) {
      $this->form_validation->set_rules('name', lang('name'), 'required|is_unique[tax_rates.name]');
    }
    $this->form_validation->set_rules('type', lang('type'), 'required');
    $this->form_validation->set_rules('rate', lang('tax_rate'), 'required|numeric');

    if ($this->form_validation->run() == true) {
      $data = [
        'name' => getPOST('name'),
        'code'      => getPOST('code'),
        'type'      => getPOST('type'),
        'rate'      => getPOST('rate'),
      ];
    } elseif (getPOST('edit_tax_rate')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/tax_rates');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateTaxRate($id, $data)) { //check to see if we are updateing the customer
      $this->session->set_flashdata('message', lang('tax_rate_updated'));
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
    if (getPOST('code') != $unit_details->code) {
      $this->form_validation->set_rules('code', lang('code'), 'required|is_unique[units.code]');
    }
    $this->form_validation->set_rules('name', lang('name'), 'trim|required');
    if (getPOST('base_unit')) {
      $this->form_validation->set_rules('operator', lang('operator'), 'required');
      $this->form_validation->set_rules('operation_value', lang('operation_value'), 'trim|required');
    }

    if ($this->form_validation->run() == true) {
      $data = [
        'name'            => getPOST('name'),
        'code'            => getPOST('code'),
        'base_unit'       => getPOST('base_unit'),
        'operator'        => getPOST('operator'),
        'operation_value' => getPOST('operation_value')
      ];
    } elseif (getPOST('edit_unit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/units');
    }

    if ($this->form_validation->run()) {
      if ($this->settings_model->updateUnit($id, $data)) {
        $this->session->set_flashdata('message', lang('unit_updated'));
      } else {
        $this->session->set_flashdata('error', 'Failed to update unit.');
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
    if (getPOST('name') != $tax_details->name) {
      $this->form_validation->set_rules('name', lang('name'), 'required|is_unique[variants.name]');
    }

    if ($this->form_validation->run() == true) {
      $data = ['name' => getPOST('name')];
    } elseif (getPOST('edit_variant')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/variants');
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateVariant($id, $data)) {
      $this->session->set_flashdata('message', lang('variant_updated'));
      admin_redirect('system_settings/variants');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['variant']  = $tax_details;
      $this->load->view($this->theme . 'settings/edit_variant', $this->data);
    }
  }

  public function edit_warehouse($warehouse_id = null)
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('code'), 'trim|required');
    $wh_details = $this->settings_model->getWarehouseByID($warehouse_id);
    if (getPOST('code') != $wh_details->code) {
      $this->form_validation->set_rules('code', lang('code'), 'required|is_unique[warehouses.code]');
    }
    $this->form_validation->set_rules('address', lang('address'), 'required');
    $this->form_validation->set_rules('geolocation', lang('geolocation'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      $data = [
        'code'           => getPOST('code'),
        'name'           => getPOST('name'),
        'phone'          => getPOST('phone'),
        'email'          => getPOST('email'),
        'address'        => getPOST('address'),
        'geolocation'    => getPOST('geolocation'),
        'price_group_id' => getPOST('price_group'),
        'active'         => (getPOST('active') ?? 0),
        'json_data' => json_encode([
          'cycle_transfer' => getPOST('cycle_transfer'),
          'delivery_time'  => getPOST('delivery_time'),
          'visit_days'     => getPOST('visit_days'),
          'visit_weeks'    => getPOST('visit_weeks')
        ])
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
      //     $this->session->set_flashdata('message', $error);
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
    } elseif (getPOST('edit_warehouse')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/warehouses');
    }

    if ($this->form_validation->run()) {
      if ($this->site->updateWarehouse(['id' => $warehouse_id], $data)) { //check to see if we are updateing the customer
        $update_ss = getPOST('update_ss');

        if ($update_ss == 1) {
          $all_items    = $this->site->getProducts(['type' => 'standard']);
          $settingsJSON = $this->site->getSettingsJSON();
          $opt          = getPastMonthPeriod($settingsJSON->safety_stock_period);
          $warehouse    = $this->site->getWarehouseByID($warehouse_id);

          foreach ($all_items as $item) {
            if (strcasecmp($item->iuse_type, 'sparepart') === 0) continue; // Ignore for sparepart.
            if (strcasecmp($warehouse->code, 'ADV') === 0) continue; // Ignore Advertising.
            if (strcasecmp($warehouse->code, 'LUC') === 0) continue; // Ignore Lucretia.

            $this->site->syncProductQty($item->id, $warehouse->id);
            $this->site->syncWarehouseProductSafetyStock($item->id, $warehouse->id, $opt);
          }
        }

        $this->session->set_flashdata('message', lang('warehouse_updated'));
      } else {
        $this->session->set_flashdata('error', 'Failed to update warehouse.');
      }
      admin_redirect('system_settings/warehouses');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['warehouse']    = $this->settings_model->getWarehouseByID($warehouse_id);
      $this->data['warehouse_js'] = json_decode($this->data['warehouse']->json_data);
      $this->data['price_groups'] = $this->settings_model->getAllPriceGroups();
      $this->data['id']           = $warehouse_id;
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
        $this->session->set_flashdata('message', lang('message_successfully_saved'));
        admin_redirect('system_settings/email_templates#' . $template);
      } else {
        $this->session->set_flashdata('error', lang('failed_to_save_message'));
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
      $this->session->set_flashdata('error', lang('no_price_group_selected'));
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
      $this->session->set_flashdata('error', lang('no_price_group_selected'));
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
          $this->session->set_flashdata('error', $error);
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
      $this->session->set_flashdata('message', lang('brands_added'));
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
          $this->session->set_flashdata('error', $error);
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
          $this->session->set_flashdata('error', 'File format is invalid.');
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
      $this->session->set_flashdata('message', lang('categories_added') . $updated);
      admin_redirect('system_settings/categories');
    } else {
      if ((isset($categories) && empty($categories)) || (isset($subcategories) && empty($subcategories))) {
        if ($updated) {
          $this->session->set_flashdata('message', $updated);
        } else {
          $this->session->set_flashdata('warning', lang('data_x_categories'));
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
          $this->session->set_flashdata('error', $error);
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
          $this->session->set_flashdata('error', 'File format is invalid.');
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
        $this->session->set_flashdata('message', lang('categories_added'));
      } else if ($updated) {
        $this->session->set_flashdata('message', lang('categories_added'));
      } else {
        $this->session->set_flashdata('error', 'Something error');
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
          $this->session->set_flashdata('error', $error);
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
          $this->session->set_flashdata('error', 'File format is invalid.');
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
        $this->session->set_flashdata('message', lang('categories_added'));
      } else if ($updated) {
        $this->session->set_flashdata('message', lang('categories_added'));
      } else {
        $this->session->set_flashdata('error', 'Something error.');
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
          $this->session->set_flashdata('error', $error);
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
              $this->session->set_flashdata('error', lang('check_category_code') . ' (' . $csv_ct['category_code'] . '). ' . lang('category_code_x_exist') . ' ' . lang('line_no') . ' ' . $rw);
              admin_redirect('system_settings/categories');
            }
          }
          $rw++;
        }
      }
    }

    if ($this->form_validation->run() == true && $this->settings_model->addSubCategories($data)) {
      $this->session->set_flashdata('message', lang('subcategories_added'));
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
    $command = getPOST('command');

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
            $this->session->set_flashdata('error', $error);
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
      } else if (getPOST('import')) {
        $this->session->set_flashdata('error', 'E1: ' . validation_errors());
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
          $this->session->set_flashdata('error', 'File format is invalid.');
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
        $this->session->set_flashdata('message', sprintf(lang('csv_units_imported'), $added, $updated));
        admin_redirect('system_settings/units');
      }
    } else {
      if (getPOST('import')) {
        $this->session->set_flashdata('error', 'E2: ' . validation_errors());
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
          $this->session->set_flashdata('error', $error);
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
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('system_settings/warehouses');
        }

        foreach ($arrResult as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $price_group = $this->site->getPriceGroupByName($csv['price_group']);

          if (!$price_group) {
            $this->session->set_flashdata('error', sprintf('Price Group [%s] is not found.', $csv['price_group']));
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
    } else if (getPOST('import')) {
      $this->session->set_flashdata('error', 'E1: ' . validation_errors());
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

      $this->session->set_flashdata('message', sprintf(lang('csv_warehouses_imported'), $added, $updated));
      admin_redirect('system_settings/warehouses');
    } else {
      if (getPOST('import')) {
        $this->session->set_flashdata('error', 'E2: ' . validation_errors());
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
    if (getPOST('protocol') == 'smtp') {
      $this->form_validation->set_rules('smtp_host', lang('smtp_host'), 'required');
      $this->form_validation->set_rules('smtp_user', lang('smtp_user'), 'required');
      $this->form_validation->set_rules('smtp_pass', lang('smtp_pass'), 'required');
      $this->form_validation->set_rules('smtp_port', lang('smtp_port'), 'required');
    }
    if (getPOST('protocol') == 'sendmail') {
      $this->form_validation->set_rules('mailpath', lang('mailpath'), 'required');
    }
    $this->form_validation->set_rules('decimals', lang('decimals'), 'trim|required');
    $this->form_validation->set_rules('decimals_sep', lang('decimals_sep'), 'trim|required');
    $this->form_validation->set_rules('thousands_sep', lang('thousands_sep'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $language = getPOST('language');

      if ((file_exists(APPPATH . 'language' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'sma_lang.php') && is_dir(APPPATH . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $language)) || $language == 'english') {
        $lang = $language;
      } else {
        $this->session->set_flashdata('error', lang('language_x_found'));
        admin_redirect('system_settings');
        $lang = 'english';
      }

      $tax1 = (getPOST('tax_rate') != 0) ? 1 : 0;
      $tax2 = (getPOST('tax_rate2') != 0) ? 1 : 0;

      $data = [
        'site_name' => DEMO ? 'Stock Manager Advance' : getPOST('site_name'),
        'rows_per_page'  => getPOST('rows_per_page'),
        'dateformat'     => getPOST('dateformat'),
        'timezone'       => DEMO ? 'Asia/Kuala_Lumpur' : getPOST('timezone'),
        'mmode'          => trim(getPOST('mmode')),
        'iwidth'         => getPOST('iwidth'),
        'iheight'        => getPOST('iheight'),
        'twidth'         => getPOST('twidth'),
        'theight'        => getPOST('theight'),
        'watermark'      => getPOST('watermark'),
        // 'reg_ver' => getPOST('reg_ver'),
        // 'allow_reg' => getPOST('allow_reg'),
        // 'reg_notification' => getPOST('reg_notification'),
        'accounting_method'    => getPOST('accounting_method'),
        'default_email'        => DEMO ? 'noreply@tecdiary.com' : getPOST('email'),
        'language'             => $lang,
        'default_warehouse'    => getPOST('warehouse'),
        'default_tax_rate'     => getPOST('tax_rate'),
        'default_tax_rate2'    => getPOST('tax_rate2'),
        'sales_prefix'         => getPOST('sales_prefix'),
        'quote_prefix'         => getPOST('quote_prefix'),
        'purchase_prefix'      => getPOST('purchase_prefix'),
        'transfer_prefix'      => getPOST('transfer_prefix'),
        'delivery_prefix'      => getPOST('delivery_prefix'),
        'payment_prefix'       => getPOST('payment_prefix'),
        'ppayment_prefix'      => getPOST('ppayment_prefix'),
        'tpayment_prefix'      => getPOST('tpayment_prefix'),
        'qa_prefix'            => getPOST('qa_prefix'),
        'return_prefix'        => getPOST('return_prefix'),
        'returnp_prefix'       => getPOST('returnp_prefix'),
        'expense_prefix'       => getPOST('expense_prefix'),
        'income_prefix'        => getPOST('income_prefix'),
        'mutation_prefix'      => getPOST('mutation_prefix'),
        'auto_detect_barcode'  => trim(getPOST('detect_barcode')),
        'theme'                => trim(getPOST('theme')),
        'product_serial'       => getPOST('product_serial'),
        'customer_group'       => getPOST('customer_group'),
        'product_expiry'       => getPOST('product_expiry'),
        'product_discount'     => getPOST('product_discount'),
        'default_currency'     => getPOST('currency'),
        'bc_fix'               => getPOST('bc_fix'),
        'tax1'                 => $tax1,
        'tax2'                 => $tax2,
        'overselling'          => getPOST('restrict_sale'),
        'reference_format'     => getPOST('reference_format'),
        'racks'                => getPOST('racks'),
        'attributes'           => getPOST('attributes'),
        'restrict_calendar'    => getPOST('restrict_calendar'),
        'captcha'              => getPOST('captcha'),
        'item_addition'        => getPOST('item_addition'),
        'protocol'             => DEMO ? 'mail' : getPOST('protocol'),
        'mailpath'             => getPOST('mailpath'),
        'smtp_host'            => getPOST('smtp_host'),
        'smtp_user'            => getPOST('smtp_user'),
        'smtp_port'            => getPOST('smtp_port'),
        'smtp_crypto'          => getPOST('smtp_crypto') ? getPOST('smtp_crypto') : null,
        'decimals'             => getPOST('decimals'),
        'decimals_sep'         => getPOST('decimals_sep'),
        'thousands_sep'        => getPOST('thousands_sep'),
        'default_biller'       => getPOST('biller'),
        'invoice_view'         => getPOST('invoice_view'),
        'rtl'                  => getPOST('rtl'),
        'each_spent'           => getPOST('each_spent') ? getPOST('each_spent') : null,
        'ca_point'             => getPOST('ca_point') ? getPOST('ca_point') : null,
        'each_sale'            => getPOST('each_sale') ? getPOST('each_sale') : null,
        'sa_point'             => getPOST('sa_point') ? getPOST('sa_point') : null,
        'sac'                  => getPOST('sac'),
        'qty_decimals'         => getPOST('qty_decimals'),
        'display_all_products' => getPOST('display_all_products'),
        'display_symbol'       => getPOST('display_symbol'),
        'symbol'               => getPOST('symbol'),
        'remove_expired'       => getPOST('remove_expired'),
        'barcode_separator'    => getPOST('barcode_separator'),
        'set_focus'            => getPOST('set_focus'),
        'disable_editing'      => getPOST('disable_editing'),
        'price_group'          => getPOST('price_group'),
        'barcode_img'          => getPOST('barcode_renderer'),
        'update_cost'          => getPOST('update_cost'),
        'apis'                 => getPOST('apis'),
        'pdf_lib'              => getPOST('pdf_lib'),
        'state'                => getPOST('state'),
        'settings_json'        => json_encode([
          'min_dp'              => filterDecimal(getPOST('min_dp') ?? 0),
          'min_dp_percent'      => filterDecimal(getPOST('min_dp_percent') ?? 0),
          'safety_stock_period' => filterDecimal(getPOST('safety_stock_period') ?? 0),
          'qms_expired_time'    => filterDecimal(getPOST('qms_expired_time') ?? 0)
        ])
      ];
      if (getPOST('smtp_pass')) {
        $data['smtp_pass'] = getPOST('smtp_pass');
      }
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateSetting($data)) {
      if (!DEMO && TIMEZONE != $data['timezone']) {
        if (!$this->write_index($data['timezone'])) {
          $this->session->set_flashdata('error', lang('setting_updated_timezone_failed'));
          admin_redirect('system_settings');
        }
      }

      $this->session->set_flashdata('message', lang('setting_updated'));
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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->load->helper('update');
    save_remote_file($file . '.zip');
    $this->sma->unzip('./files/updates/' . $file . '.zip');
    if ($m_version) {
      $this->load->library('migration');
      if (!$this->migration->latest()) {
        $this->session->set_flashdata('error', $this->migration->error_string());
        admin_redirect('system_settings/updates');
      }
    }
    $this->db->update('settings', ['version' => $version, 'update' => 0], ['setting_id' => 1]);
    unlink('./files/updates/' . $file . '.zip');
    $this->session->set_flashdata('success', lang('update_done'));
    admin_redirect('system_settings/updates');
  }

  public function mutasibank()
  {
    $this->form_validation->set_rules('active', $this->lang->line('activate'), 'trim');

    if ($this->form_validation->run() == true) {
      $api_keys = getPOST('api_keys');
      $keys = [];
      foreach ($api_keys as $key) {
        if (!empty($key)) $keys[] = $key;
      }
      $data = [
        'active'   => getPOST('active'),
        'api_keys' => json_encode($keys)
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updateMutasibank($data)) {
      $this->session->set_flashdata('message', lang('mutasibank_updated'));
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
    if (getPOST('active')) {
      $this->form_validation->set_rules('account_email', $this->lang->line('paypal_account_email'), 'required');
    }
    $this->form_validation->set_rules('fixed_charges', $this->lang->line('fixed_charges'), 'trim');
    $this->form_validation->set_rules('extra_charges_my', $this->lang->line('extra_charges_my'), 'trim');
    $this->form_validation->set_rules('extra_charges_other', $this->lang->line('extra_charges_others'), 'trim');

    if ($this->form_validation->run() == true) {
      $data = [
        'active'         => getPOST('active'),
        'account_email'       => getPOST('account_email'),
        'fixed_charges'       => getPOST('fixed_charges'),
        'extra_charges_my'    => getPOST('extra_charges_my'),
        'extra_charges_other' => getPOST('extra_charges_other'),
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePaypal($data)) {
      $this->session->set_flashdata('message', $this->lang->line('paypal_setting_updated'));
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
        'products-index'             => getPOST('products-index'),
        'products-edit'              => getPOST('products-edit'),
        'products-add'               => getPOST('products-add'),
        'products-delete'            => getPOST('products-delete'),
        'products-cost'              => getPOST('products-cost'),
        'products-price'             => getPOST('products-price'),
        'customers-index'            => getPOST('customers-index'),
        'customers-edit'             => getPOST('customers-edit'),
        'customers-add'              => getPOST('customers-add'),
        'customers-delete'           => getPOST('customers-delete'),
        'suppliers-index'            => getPOST('suppliers-index'),
        'suppliers-edit'             => getPOST('suppliers-edit'),
        'suppliers-add'              => getPOST('suppliers-add'),
        'suppliers-delete'           => getPOST('suppliers-delete'),
        'sales-index'                => getPOST('sales-index'),
        'sales-edit'                 => getPOST('sales-edit'),
        'sales-add'                  => getPOST('sales-add'),
        'sales-delete'               => getPOST('sales-delete'),
        'sales-email'                => getPOST('sales-email'),
        'sales-pdf'                  => getPOST('sales-pdf'),
        'purchases-index'            => getPOST('purchases-index'),
        'purchases-edit'             => getPOST('purchases-edit'),
        'purchases-add'              => getPOST('purchases-add'),
        'purchases-delete'           => getPOST('purchases-delete'),
        'purchases-email'            => getPOST('purchases-email'),
        'purchases-pdf'              => getPOST('purchases-pdf'),
        'transfers-index'            => getPOST('transfers-index'),
        'transfers-edit'             => getPOST('transfers-edit'),
        'transfers-add'              => getPOST('transfers-add'),
        'transfers-delete'           => getPOST('transfers-delete'),
        'transfers-email'            => getPOST('transfers-email'),
        'transfers-pdf'              => getPOST('transfers-pdf'),
        'reports-quantity_alerts'    => getPOST('reports-quantity_alerts'),
        'reports-expiry_alerts'      => getPOST('reports-expiry_alerts'),
        'reports-products'           => getPOST('reports-products'),
        'reports-daily_sales'        => getPOST('reports-daily_sales'),
        'reports-monthly_sales'      => getPOST('reports-monthly_sales'),
        'reports-payments'           => getPOST('reports-payments'),
        'reports-sales'              => getPOST('reports-sales'),
        'reports-purchases'          => getPOST('reports-purchases'),
        'reports-customers'          => getPOST('reports-customers'),
        'reports-suppliers'          => getPOST('reports-suppliers'),
        'sales-payments'             => getPOST('sales-payments'),
        'purchases-payments'         => getPOST('purchases-payments'),
        'purchases-expenses'         => getPOST('purchases-expenses'),
        'products-adjustments'       => getPOST('products-adjustments'),
        'bulk_actions'               => getPOST('bulk_actions'),
        'customers-deposits'         => getPOST('customers-deposits'),
        'customers-delete_deposit'   => getPOST('customers-delete_deposit'),
        'products-barcode'           => getPOST('products-barcode'),
        'purchases-return_purchases' => getPOST('purchases-return_purchases'),
        'reports-expenses'           => getPOST('reports-expenses'),
        'reports-daily_purchases'    => getPOST('reports-daily_purchases'),
        'reports-monthly_purchases'  => getPOST('reports-monthly_purchases'),
        'products-stock_count'       => getPOST('products-stock_count'),
        'edit_price'                 => getPOST('edit_price'),
        'reports-tax'                => getPOST('reports-tax'),
        'permissions_json'           => json_encode([ // Extended permissions.
          'banks-add'                 => getPOST('banks-add'),
          'banks-delete'              => getPOST('banks-delete'),
          'banks-edit'                => getPOST('banks-edit'),
          'banks-index'               => getPOST('banks-index'),
          'banks-reconciliation'      => getPOST('banks-reconciliation'),
          'reports-daily_performance' => getPOST('reports-daily_performance'),
          'dashboard-chart'           => getPOST('dashboard-chart'),
          'mutations-add'             => getPOST('mutations-add'),
          'mutations-delete'          => getPOST('mutations-delete'),
          'mutations-edit'            => getPOST('mutations-edit'),
          'mutations-index'           => getPOST('mutations-index'),
          'mutations-manual'          => getPOST('mutations-manual'),
          'edit-system'               => getPOST('edit-system'),
          'expenses-add'              => getPOST('expenses-add'),
          'expenses-approval'         => getPOST('expenses-approval'),
          'expenses-delete'           => getPOST('expenses-delete'),
          'expenses-edit'             => getPOST('expenses-edit'),
          'expenses-index'            => getPOST('expenses-index'),
          'expenses-payment'          => getPOST('expenses-payment'),
          'googlereview-add'          => getPOST('googlereview-add'),
          'googlereview-delete'       => getPOST('googlereview-delete'),
          'googlereview-edit'         => getPOST('googlereview-edit'),
          'googlereview-view'         => getPOST('googlereview-view'),
          'incomes-add'               => getPOST('incomes-add'),
          'incomes-delete'            => getPOST('incomes-delete'),
          'incomes-edit'              => getPOST('incomes-edit'),
          'incomes-index'             => getPOST('incomes-index'),
          'internal_uses-add'         => getPOST('internal_uses-add'),
          'internal_uses-approval'    => getPOST('internal_uses-approval'),
          'internal_uses-delete'      => getPOST('internal_uses-delete'),
          'internal_uses-edit'        => getPOST('internal_uses-edit'),
          'internal_uses-index'       => getPOST('internal_uses-index'),
          'internal_uses-consumable'  => getPOST('internal_uses-consumable'),
          'internal_uses-cmreport'    => getPOST('internal_uses-cmreport'),
          'internal_uses-sparepart'   => getPOST('internal_uses-sparepart'),
          'machine-assign'            => getPOST('machine-assign'),
          'machine-report_delete'     => getPOST('machine-report_delete'),
          'sales-add_qms_only'        => getPOST('sales-add_qms_only'),
          'sales-edit_operator'       => getPOST('sales-edit_operator'),
          'sales-edit_price'          => getPOST('sales-edit_price'),
          'sales-item_status'         => getPOST('sales-item_status'),
          'sales-skip_validation'     => getPOST('sales-skip_validation'),
          'sales-tb'                  => getPOST('sales-tb'),
          'notify-add'                => getPOST('notify-add'),
          'notify-delete'             => getPOST('notify-delete'),
          'notify-edit'               => getPOST('notify-edit'),
          'notify-index'              => getPOST('notify-index'),
          'operators-checkpoint'      => getPOST('operators-checkpoint'),
          'operators-orders'          => getPOST('operators-orders'),
          'trackingpod-add'           => getPOST('trackingpod-add'),
          'trackingpod-delete'        => getPOST('trackingpod-delete'),
          'trackingpod-edit'          => getPOST('trackingpod-edit'),
          'trackingpod-index'         => getPOST('trackingpod-index'),
          'transfers-add'             => getPOST('transfers-add'),
          'transfers-approval'        => getPOST('transfers-approval'),
          'transfers-delete'          => getPOST('transfers-delete'),
          'transfers-edit'            => getPOST('transfers-edit'),
          'transfers-index'           => getPOST('transfers-index'),
          'transfers-payment'         => getPOST('transfers-payment'),
          'transfers-received'        => getPOST('transfers-received'),
          'transfers-sent'            => getPOST('transfers-sent'),
          'purchases-add'             => getPOST('purchases-add'),
          'purchases-approval'        => getPOST('purchases-approval'),
          'purchases-delete'          => getPOST('purchases-delete'),
          'purchases-edit'            => getPOST('purchases-edit'),
          'purchases-index'           => getPOST('purchases-index'),
          'purchases-other_warehouse' => getPOST('purchases-other_warehouse'),
          'payments-add'              => getPOST('payments-add'),
          'payments-delete'           => getPOST('payments-delete'),
          'payments-edit'             => getPOST('payments-edit'),
          'payments-index'            => getPOST('payments-index'),
          'products-categories'       => getPOST('products-categories'),
          'products-history'          => getPOST('products-history'),
          'products-mutation_add'     => getPOST('products-mutation_add'),
          'products-mutation_delete'  => getPOST('products-mutation_delete'),
          'products-mutation_edit'    => getPOST('products-mutation_edit'),
          'products-mutation_view'    => getPOST('products-mutation_view'),
          'products-mutation_status'  => getPOST('products-mutation_status'),
          'products-quantity'         => getPOST('products-quantity'),
          'products-std_qty'          => getPOST('products-std_qty'),
          'products-so_quantity'      => getPOST('products-so_quantity'),
          'products-stock_opname'     => getPOST('products-stock_opname'),
          'products-transfer_view'    => getPOST('products-transfer_view'),
          'products-transfer_add'     => getPOST('products-transfer_add'),
          'products-transfer_delete'  => getPOST('products-transfer_delete'),
          'products-transfer_edit'    => getPOST('products-transfer_edit'),
          'products-transfer_status'  => getPOST('products-transfer_status'),
          'reports-income_statement'  => getPOST('reports-income_statement'),
          'reports-inventory_balance' => getPOST('reports-inventory_balance'),
          'reports-printerp'          => getPOST('reports-printerp'),
          'users-edit'                => getPOST('users-edit'),
          'validations-add'           => getPOST('validations-add'),
          'validations-cancel'        => getPOST('validations-cancel'),
          'validations-delete'        => getPOST('validations-delete'),
          'validations-edit'          => getPOST('validations-edit'),
          'validations-index'         => getPOST('validations-index'),
          'validations-manual'        => getPOST('validations-manual'),
          'warehouses-add'            => getPOST('warehouses-add'),
          'warehouses-delete'         => getPOST('warehouses-delete'),
          'warehouses-edit'           => getPOST('warehouses-edit'),
          'warehouses-index'          => getPOST('warehouses-index'),
        ])
      ];
    }

    if ($this->form_validation->run() == true && $this->settings_model->updatePermissions($group_id, $data)) {
      $this->session->set_flashdata('message', lang('group_permissions_updated'));
      redirect($_SERVER['HTTP_REFERER']);
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
        $this->session->set_flashdata('message', lang('disabled_in_demo'));
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
          $this->session->set_flashdata('error', $error);
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
            $this->session->set_flashdata('message', lang('check_product_code') . ' (' . $csv_pr['code'] . '). ' . lang('code_x_exist') . ' ' . lang('line_no') . ' ' . $rw);
            admin_redirect('system_settings/group_product_prices/' . $group_id);
          }
          $rw++;
        }
      }
    } elseif (getPOST('update_price')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('system_settings/group_product_prices/' . $group_id);
    }

    if ($this->form_validation->run() == true && !empty($data)) {
      $this->settings_model->updateGroupPrices($data);
      $this->session->set_flashdata('message', lang('price_updated'));
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

    $product_id = getPOST('product_id', true);
    $price      = getPOST('price', true);
    $price2      = getPOST('price2', true);
    $price3      = getPOST('price3', true);
    $price4      = getPOST('price4', true);
    $price5      = getPOST('price5', true);
    $price6      = getPOST('price6', true);

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
      $this->session->set_flashdata('warning', lang('disabled_in_demo'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    if (!$this->Owner) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect('welcome');
    }
    $this->form_validation->set_rules('purchase_code', lang('purchase_code'), 'required');
    $this->form_validation->set_rules('envato_username', lang('envato_username'), 'required');
    if ($this->form_validation->run() == true) {
      $this->db->update('settings', ['purchase_code' => getPOST('purchase_code', true), 'envato_username' => getPOST('envato_username', true)], ['setting_id' => 1]);
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
      $this->session->set_flashdata('error', lang('access_denied'));
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
