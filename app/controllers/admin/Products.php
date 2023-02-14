<?php

use App\Libraries\DataTables;

defined('BASEPATH') or exit('No direct script access allowed');

class Products extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!$this->loggedIn) {
      // admin_redirect('login');
      loginPage();
    }
    $this->lang->admin_load('products', $this->Settings->user_language);
    $this->load->library('form_validation');
    // $this->load->admin_model('products_model');
    // $this->load->admin_model('settings_model');
    $this->digital_upload_path = 'files/';
    $this->import_path         = 'files/import/';
    $this->upload_path         = 'assets/uploads/';
    $this->thumbs_path         = 'assets/uploads/thumbs/';
    $this->image_types         = 'gif|jpg|jpeg|png|tif';
    $this->digital_file_types  = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
    $this->allowed_file_size   = '1024';
    $this->popup_attributes    = ['width' => '900', 'height' => '600', 'window_name' => 'sma_popup', 'menubar' => 'yes', 'scrollbars' => 'yes', 'status' => 'no', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0'];

    $this->so_mode = 'edit'; // edit, confirm
  }

  /* ------------------------------------------------------- */

  public function add()
  { // New rewrite Add method.
    $this->form_validation->set_rules('code', lang('product_code'), 'is_unique[products.code]|required');
    $this->form_validation->set_rules('name', lang('product_name'), 'required');
    $this->form_validation->set_rules('type', 'Type required', 'required');

    if (getPOST('type') == 'standard') {
      $this->form_validation->set_rules('cost', lang('product_cost'), 'required');
      $this->form_validation->set_rules('unit', lang('product_unit'), 'required');
    }

    if ($this->form_validation->run()) {
      $cat = getPOST('category');
      $category = $this->site->getProductCategoryByCode($cat);

      $scat = getPOST('subcategory');
      $subcategory = $this->site->getProductCategoryByCode($scat);

      $product_type = getPOST('type');
      $product_data = [
        'code'               => getPOST('code'),
        'name'               => getPOST('name'),
        'unit'               => getPOST('unit'),
        'cost'               => filterDecimal(getPOST('cost')),
        'price'              => filterDecimal(getPOST('price')),
        'warehouses'         => getPOST('warehouses'),
        'markon_price'       => filterDecimal(getPOST('markon_price')),
        'markon'             => getPOST('markon'),
        'safety_stock_ratio' => getPOST('safety_stock_ratio'),
        'min_order_qty'      => getPOST('min_order_qty'),
        'iuse_type'          => getPOST('iuse_type'),
        'active'             => getPOST('active'),
        'autocomplete'       => getPOST('autocomplete'),
        'category_id'        => ($category ? $category->id : NULL),
        'subcategory_id'     => ($subcategory ? $subcategory->id : NULL),
        'type'               => getPOST('type'),
        'supplier_id'        => getPOST('supplier'),
        'sale_unit'          => getPOST('sale_unit'),
        'purchase_unit'      => getPOST('purchase_unit'),
        'price_ranges_value' => getPOST('price_ranges_value'),
        'min_prod_time'      => getPOST('min_prod_time'),
        'prod_time_qty'      => getPOST('prod_time_qty'),
        'sn'                 => getPOST('sn'),
        'priority'           => getPOST('priority'),
        'purchased_at'       => getPOST('purchased_at'),
        'purchase_source'    => getPOST('purchase_source')
      ];

      if ($product_type == 'combo') {
        $item = getPOST('combo_item_code');
        $total = count($item);

        for ($a = 0; $a < $total; $a++) {
          $combo_item_code     = getPOST('combo_item_code');
          $combo_item_quantity = getPOST('combo_item_quantity');

          $product_data['combo_items'][] = [
            'item_code' => $combo_item_code[$a],
            'quantity'  => $combo_item_quantity[$a]
          ];
        }
      }

      $warehouses = $this->site->getAllWarehouses();
      if ($warehouses) {
        foreach ($warehouses as $wh) {
          if (getPOST('safety_stock_' . $wh->id)) {
            $product_data['safety_stock'][] = [
              'quantity' => getPOST('safety_stock_' . $wh->id),
              'warehouse_id' => $wh->id
            ];
          }

          if (getPOST('pic_' . $wh->id) && getPOST('cycle_' . $wh->id)) {
            $product_data['stock_opname'][] = [
              'user_id'      => getPOST('pic_' . $wh->id),
              'so_cycle'     => getPOST('cycle_' . $wh->id),
              'warehouse_id' => $wh->id,
            ];
          }
        }
      }

      $price_groups = $this->site->getAllPriceGroups();
      if ($price_groups) {
        foreach ($price_groups as $pg) {
          if (getPOST('price_groups_' . $pg->id)) {
            $price_ranges = [];
            foreach (getPOST('price_groups_' . $pg->id) as $price_range) {
              $price_ranges[] = filterDecimal($price_range);
            }
            $product_data['price_groups'][] = [
              'price_group_id' => $pg->id,
              'price_ranges' => $price_ranges
            ];
          }
        }
      }

      if ($this->site->addProducts([$product_data])) {
        $this->session->set_flashdata('message', lang('product_added'));
        admin_redirect('products');
      }
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    }

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => lang('add_product')]
    ];
    $meta = ['page_title' => lang('add_product'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/add', $this->data);
  }

  public function add_adjustment($count_id = null) // Add Adjustment manually.
  {
    $this->sma->checkPermissions('adjustments', true);
    $this->form_validation->set_rules('mode', lang('adjustment_mode'), 'required');
    $this->form_validation->set_rules('warehouse', lang('warehouse'), 'required');

    if ($this->form_validation->run() == true) {
      $date = $this->sma->fld(getPOST('date'));
      $mode = getPOST('mode');
      $warehouse_id = getPOST('warehouse');
      $note         = $this->sma->clear_tags(getPOST('note'));

      $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
      for ($r = 0; $r < $i; $r++) {
        $product_id   = $_POST['product_id'][$r];
        $quantity = $_POST['quantity'][$r]; // Original quantity/ Adjustment quantity.

        if (!is_numeric($quantity)) continue;

        // if ($mode == 'formula') {
        //   $quantity = $adj_quantity;
        //   $type     = 'received';
        // } else
        // if ($mode == 'overwrite') {
        //   $current_qty = $this->site->getStockQuantity($product_id, $warehouse_id, ['end_date' => $date]);

        //   if ($current_qty == $adj_quantity) continue;

        //   $adjusted = getAdjustedQty($current_qty, $adj_quantity); // Get Adjusted Quantity.
        //   $quantity = $adjusted['quantity'];
        //   $type     = $adjusted['type'];
        // }

        $products[] = [
          'product_id'     => $product_id,
          'quantity'       => $quantity
        ];
      }

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('products'), 'required');
      } else {
        krsort($products);
      }

      $adjustmentData = [
        'date'         => $date,
        'warehouse_id' => $warehouse_id,
        'mode'         => $mode, // overwrite|formula
        'note'         => $note,
        'end_date'     => $date
      ];

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }

        $adjustmentData['attachment'] = $uploader->storeRandom();
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->addAdjustmentStock($adjustmentData, $products)) {
        $this->session->set_userdata('remove_qals', 1);
        $this->session->set_flashdata('message', lang('quantity_adjusted'));
        admin_redirect('products/quantity_adjustments');
      }
    } else {
      $this->data['adjustment_items'] = FALSE;
      $this->data['warehouse_id']     = FALSE;
      $this->data['count_id']         = NULL;
      $this->data['error']            = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['warehouses']       = $this->site->getAllWarehouses();

      $bc                             = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => admin_url('products/quantity_adjustments'), 'page' => lang('quantity_adjustments')],
        ['link' => '#', 'page' => lang('add_adjustment')]
      ];
      $meta = ['page_title' => lang('add_adjustment'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/add_adjustment', $this->data);
    }
  }

  public function add_adjustment_by_csv() // Current CSV Adjustment
  {
    $this->sma->checkPermissions('adjustments', true);
    $this->form_validation->set_rules('mode', lang('adjustment_mode'), 'required');
    $this->form_validation->set_rules('warehouse', lang('warehouse'), 'required');

    if ($this->form_validation->run() == true) {
      $date = getPOST('date');
      $mode = getPOST('mode');
      $warehouse_id = getPOST('warehouse');
      $note         = $this->sma->clear_tags(getPOST('note'));

      $adjustmentData = [
        'date'         => $date,
        'warehouse_id' => $warehouse_id,
        'mode'         => $mode,
        'note'         => $note,
        'created_by'   => XSession::get('user_id'),
        'end_date'     => $date
      ];

      if ($_FILES['csv_file']['size'] > 0) {
        checkPath($this->upload_adjustments_import_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_adjustments_import_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = false;
        $config['encrypt_name']  = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
        }

        $csv = $this->upload->file_name;
        $adjustmentData['attachment'] = $csv;

        $arrResult = [];
        $handle    = fopen($this->upload_adjustments_import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }

        $header_id = array_shift($arrResult);
        $titles    = array_shift($arrResult);

        if ($header_id[0] != 'QTAJ') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('products/add_adjustment_by_csv');
        }

        $csvs = [];
        $keys = ['no', 'use', 'product_code', 'product_name', 'quantity'];
        foreach ($arrResult as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }

        $final  = [];
        foreach ($csvs as $csv) {
          if ($csv['use'] == 0) continue;
          if (empty($csv['quantity']) && floatval($csv['quantity']) != 0) continue; // Ignore empty quantity, but not zero.

          $final[] = [
            'code'     => $csv['product_code'],
            'quantity' => ($csv['quantity'] != 0 ? $csv['quantity'] : 0)
          ];
        }

        $err_msg = '';
        $rw = 3;

        foreach ($final as $pr) {
          if ($product = $this->site->getProductByCode(trim($pr['code']))) { // Check if product exists.
            $product_id = $product->id;
            $quantity = trim($pr['quantity']); // Original quantity/ Adjustment quantity.

            if (!is_numeric($quantity)) continue;

            $products[] = [
              'product_id' => $product_id,
              'quantity'   => $quantity
            ];
          } else {
            $err_msg .= lang('check_product_code') . ' (' . $pr['code'] . '). ' . lang('product_code_x_exist') . ' Column: ' . $rw . '<br>';
          }
          $rw++;
        }
      } else {
        $this->form_validation->set_rules('csv_file', lang('upload_file'), 'required');
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->addAdjustmentStock($adjustmentData, $products)) {
        $msg = lang('quantity_adjusted') . ($err_msg ? '<br>' . $err_msg : '');
        $this->session->set_flashdata('message', $msg);
        admin_redirect('products/quantity_adjustments');
      }
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['warehouses'] = $this->site->getAllWarehouses();

      $bc                       = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => admin_url('products/quantity_adjustments'), 'page' => lang('quantity_adjustments')],
        ['link' => '#', 'page' => lang('add_adjustment')]
      ];
      $meta = ['page_title' => lang('add_adjustment_by_csv'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/add_adjustment_by_csv', $this->data);
    }
  }

  // public function addByAjax()
  // {
  //   return FALSE;
  //   if (getGET('token') && getGET('token') == XSession::get('user_csrf') && $this->input->is_ajax_request()) {
  //     $product = getGET('product');
  //     if (!isset($product['code']) || empty($product['code'])) {
  //       exit(json_encode(['msg' => lang('product_code_is_required')]));
  //     }
  //     if (!isset($product['name']) || empty($product['name'])) {
  //       exit(json_encode(['msg' => lang('product_name_is_required')]));
  //     }
  //     if (!isset($product['category_id']) || empty($product['category_id'])) {
  //       exit(json_encode(['msg' => lang('product_category_is_required')]));
  //     }
  //     if (!isset($product['unit']) || empty($product['unit'])) {
  //       exit(json_encode(['msg' => lang('product_unit_is_required')]));
  //     }
  //     if (!isset($product['price']) || empty($product['price'])) {
  //       exit(json_encode(['msg' => lang('product_price_is_required')]));
  //     }
  //     if (!isset($product['cost']) || empty($product['cost'])) {
  //       exit(json_encode(['msg' => lang('product_cost_is_required')]));
  //     }
  //     if ($this->site->getProductByCode($product['code'])) {
  //       exit(json_encode(['msg' => lang('product_code_already_exist')]));
  //     }
  //     if ($row = $this->products_model->addAjaxProduct($product)) {
  //       $pr       = [
  //         'id' => $row->id,
  //         'label' => $row->name . ' (' . $row->code . ')',
  //         'code' => $row->code,
  //         'qty' => 1,
  //         'cost' => $row->cost,
  //         'name' => $row->name,
  //         'discount' => '0'
  //       ];
  //       sendJSON(['msg' => 'success', 'result' => $pr]);
  //     } else {
  //       exit(json_encode(['msg' => lang('failed_to_add_product')]));
  //     }
  //   } else {
  //     json_encode(['msg' => 'Invalid token']);
  //   }
  // }

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
        'parent_code' => getPOST('parent'),
      ];

      if ($_FILES['userfile']['size'] > 0) {
        checkPath($this->upload_import_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
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
      admin_redirect('products/categories');
    }

    if ($this->form_validation->run() == true && $this->site->addProductCategory($data)) {
      $this->session->set_flashdata('message', lang('product_categories_added'));
      admin_redirect('products/categories');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['categories'] = $this->site->getParentCategories();
      $this->load->view($this->theme . 'products/add_category', $this->data);
    }
  }

  public function adjustment_actions()
  {
    if (!$this->Owner && !$this->GP['bulk_actions']) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPOST('form_action') == 'delete') {
          $this->sma->checkPermissions('delete');
          foreach ($_POST['val'] as $id) {
            $this->site->deleteStockAdjustment($id);
          }
          $this->session->set_flashdata('message', $this->lang->line('adjustment_deleted'));
          redirect($_SERVER['HTTP_REFERER']);
        } elseif (getPOST('form_action') == 'export_excel') {
          // ON PROGRESS
        }
      } else {
        $this->session->set_flashdata('error', $this->lang->line('no_record_selected'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function barcode($product_code = null, $bcs = 'code128', $height = 40)
  {
    if ($this->Settings->barcode_img) {
      header('Content-Type: image/png');
    } else {
      header('Content-type: image/svg+xml');
    }
    echo $this->sma->barcode($product_code, $bcs, $height, true, false, true);
  }

  public function categories()
  {
    $this->sma->checkPermissions('categories');

    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc                  = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => lang('product_categories')]
    ];
    $meta = ['page_title' => lang('product_categories'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/categories', $this->data);
  }

  public function category_actions()
  {
    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPOST('form_action') == 'delete') {
          foreach ($_POST['val'] as $id) {
            $this->site->deleteProductCategory($id);
          }
          $this->session->set_flashdata('message', lang('categories_deleted'));
          redirect($_SERVER['HTTP_REFERER']);
        }

        if (getPOST('form_action') == 'export_excel') {
        }
      } else {
        $this->session->set_flashdata('error', lang('no_record_selected'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function getCategorySuggestions()
  {
    $term  = getGET('term');
    $limit = (getGET('limit') ?? 10);

    if ($category_code = getGET('code')) {
      $term = [];
      $term['code'] = $category_code;
    }

    if ($rows = $this->site->getCategorySuggestions($term, $limit)) {
      sendJSON(['results' => $rows]);
    }
    sendJSON(['results' => [['id' => '', 'text' => 'No Category found.']]]);
  }

  // public function count_stock($page = null)
  // {
  //   $this->sma->checkPermissions('stock_count');
  //   $this->form_validation->set_rules('warehouse', lang('warehouse'), 'required');
  //   $this->form_validation->set_rules('type', lang('type'), 'required');

  //   if ($this->form_validation->run() == true) {
  //     $warehouse_id = getPOST('warehouse');
  //     $type         = getPOST('type');
  //     $categories   = getPOST('category') ? getPOST('category') : null;
  //     $brands       = getPOST('brand') ? getPOST('brand') : null;
  //     $this->load->helper('string');
  //     $name     = random_string('md5') . '.csv';
  //     $products = $this->products_model->getStockCountProducts($warehouse_id, $type, $categories, $brands);
  //     $pr       = 0;
  //     $rw       = 0;
  //     foreach ($products as $product) {
  //       if ($variants = $this->products_model->getStockCountProductVariants($warehouse_id, $product->id)) {
  //         foreach ($variants as $variant) {
  //           $items[] = [
  //             'product_code' => $product->code,
  //             'product_name' => $product->name,
  //             'variant'      => $variant->name,
  //             'expected'     => $variant->quantity,
  //             'counted'      => '',
  //           ];
  //           $rw++;
  //         }
  //       } else {
  //         $items[] = [
  //           'product_code' => $product->code,
  //           'product_name' => $product->name,
  //           'variant'      => '',
  //           'expected'     => $product->quantity,
  //           'counted'      => '',
  //         ];
  //         $rw++;
  //       }
  //       $pr++;
  //     }
  //     if (!empty($items)) {
  //       $csv_file = fopen('./files/' . $name, 'w');
  //       fprintf($csv_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
  //       fputcsv($csv_file, [lang('product_code'), lang('product_name'), lang('variant'), lang('expected'), lang('counted')]);
  //       foreach ($items as $item) {
  //         fputcsv($csv_file, $item);
  //       }
  //       // file_put_contents('./files/'.$name, $csv_file);
  //       // fwrite($csv_file, $txt);
  //       fclose($csv_file);
  //     } else {
  //       $this->session->set_flashdata('error', lang('no_product_found'));
  //       redirect($_SERVER['HTTP_REFERER']);
  //     }

  //     if ($this->Owner || $this->Admin) {
  //       $date = $this->sma->fld(getPOST('date'));
  //     } else {
  //       $date = date('Y-m-d H:s:i');
  //     }
  //     $category_ids   = '';
  //     $brand_ids      = '';
  //     $category_names = '';
  //     $brand_names    = '';
  //     if ($categories) {
  //       $r = 1;
  //       $s = sizeof($categories);
  //       foreach ($categories as $category_id) {
  //         $category = $this->site->getProductCategoryByID($category_id);
  //         if ($r == $s) {
  //           $category_names .= $category->name;
  //           $category_ids   .= $category->id;
  //         } else {
  //           $category_names .= $category->name . ', ';
  //           $category_ids   .= $category->id . ', ';
  //         }
  //         $r++;
  //       }
  //     }

  //     if ($brands) {
  //       $r = 1;
  //       $s = sizeof($brands);
  //       foreach ($brands as $brand_id) {
  //         $brand = $this->site->getBrandByID($brand_id);
  //         if ($r == $s) {
  //           $brand_names .= $brand->name;
  //           $brand_ids   .= $brand->id;
  //         } else {
  //           $brand_names .= $brand->name . ', ';
  //           $brand_ids   .= $brand->id . ', ';
  //         }
  //         $r++;
  //       }
  //     }

  //     $data = [
  //       'date'           => $date,
  //       'warehouse_id'   => $warehouse_id,
  //       'reference'      => getPOST('reference'),
  //       'type'           => $type,
  //       'categories'     => $category_ids,
  //       'category_names' => $category_names,
  //       'brands'         => $brand_ids,
  //       'brand_names'    => $brand_names,
  //       'initial_file'   => $name,
  //       'products'       => $pr,
  //       'rows'           => $rw,
  //       'created_by'     => XSession::get('user_id'),
  //     ];
  //   }

  //   if ($this->form_validation->run() == true && $this->products_model->addStockCount($data)) {
  //     $this->session->set_flashdata('message', lang('stock_count_intiated'));
  //     admin_redirect('products/stock_counts');
  //   } else {
  //     $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
  //     $this->data['warehouses'] = $this->site->getAllWarehouses();
  //     $this->data['categories'] = $this->site->();
  //     $this->data['brands']     = NULL;
  //     $bc                       = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('count_stock')]];
  //     $meta                     = ['page_title' => lang('count_stock'), 'bc' => $bc];
  //     $this->data = array_merge($this->data, $meta);

  //     $this->page_construct('products/count_stock', $this->data);
  //   }
  // }

  /* ------------------------------------------------------------------------------- */

  public function delete($id = null)
  {
    $this->sma->checkPermissions(null, true);

    if (getGET('id')) {
      $id = getGET('id');
    }

    if ($this->site->deleteProduct($id)) {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 0, 'msg' => lang('product_deleted')]);
      }
      $this->session->set_flashdata('message', lang('product_deleted'));
      admin_redirect('welcome');
    }
  }

  public function delete_adjustment($id = null)
  {
    $this->sma->checkPermissions('delete', true);
    if ($this->site->deleteStockAdjustment($id)) {
      sendJSON(['error' => 0, 'msg' => lang('adjustment_deleted')]);
    } else {
      sendJSON(['error' => 1, 'msg' => 'Failed to delete adjustment']);
    }
  }

  public function delete_category($id = null)
  {
    if ($this->site->getSubCategories($id)) {
      sendJSON(['error' => 1, 'msg' => lang('category_has_subcategory')]);
    }

    if ($this->site->deleteProductCategory($id)) {
      sendJSON(['error' => 0, 'msg' => lang('category_deleted')]);
    }
  }

  public function delete_image($id = null)
  {
    $this->sma->checkPermissions('edit', true);
    if ($id && $this->input->is_ajax_request()) {
      header('Content-Type: application/json');
      $this->db->delete('product_photos', ['id' => $id]);
      sendJSON(['error' => 0, 'msg' => lang('image_deleted')]);
    }
    sendJSON(['error' => 1, 'msg' => lang('ajax_error')]);
  }

  /* -------------------------------------------------------- */

  public function edit($product_id)
  { // New rewrite Edit method.

    if (!$this->Owner && !$this->Admin && !getPermission('products-edit')) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'products');
    }

    $this->form_validation->set_rules('code', lang('product_code'), 'required');
    $this->form_validation->set_rules('name', lang('product_name'), 'required');
    $this->form_validation->set_rules('type', 'Type required', 'required');

    if (getPOST('type') == 'standard') {
      $this->form_validation->set_rules('cost', lang('product_cost'), 'required');
      $this->form_validation->set_rules('unit', lang('product_unit'), 'required');
    }

    $product = $this->site->getProductByID($product_id);

    $cat = getPOST('category');
    $category = $this->site->getProductCategoryByCode($cat);

    $scat = getPOST('subcategory');
    $subcategory = $this->site->getProductCategoryByCode($scat);

    if ($this->form_validation->run()) {
      $product_type = getPOST('type');
      $product_data = [
        'product_id'          => $product_id,
        'code'                => getPOST('code'),
        'name'                => getPOST('name'),
        'unit'                => getPOST('unit'),
        'cost'                => filterDecimal(getPOST('cost')),
        'price'               => filterDecimal(getPOST('price')),
        'warehouses'          => getPOST('warehouses'),
        'markon_price'        => filterDecimal(getPOST('markon_price')),
        'markon'              => getPOST('markon'),
        'safety_stock_ratio'  => getPOST('safety_stock_ratio'),
        'min_order_qty'       => getPOST('min_order_qty'),
        'iuse_type'           => getPOST('iuse_type'),
        'active'              => (getPOST('active') ?? 0),
        'autocomplete'        => (getPOST('autocomplete') ?? 0),
        'category_id'         => ($category ? $category->id : NULL),
        'subcategory_id'      => ($subcategory ? $subcategory->id : NULL),
        'type'                => getPOST('type'),
        'supplier_id'         => getPOST('supplier'),
        'sale_unit'           => getPOST('sale_unit'),
        'purchase_unit'       => getPOST('purchase_unit'),
        'price_ranges_value'  => getPOST('price_ranges_value'),
        'min_prod_time'       => getPOST('min_prod_time'),
        'prod_time_qty'       => getPOST('prod_time_qty'),
        'sn'                  => getPOST('sn'),
        'priority'            => getPOST('priority'),
        'purchased_at'        => getPOST('purchased_at'),
        'purchase_source'     => getPOST('purchase_source')
      ];

      if ($product_type == 'combo') {
        $item = getPOST('combo_item_code');
        $total = (is_array($item) ? count($item) : 0);

        for ($a = 0; $a < $total; $a++) {
          $combo_item_code     = getPOST('combo_item_code');
          $combo_item_quantity = getPOST('combo_item_quantity');

          $product_data['combo_items'][] = [
            'item_code' => $combo_item_code[$a],
            'quantity'  => $combo_item_quantity[$a]
          ];
        }
      }

      $warehouses = $this->site->getAllWarehouses();
      if ($warehouses) {
        foreach ($warehouses as $wh) {
          if (getPOST('safety_stock_' . $wh->id)) {
            $product_data['safety_stock'][] = [
              'quantity' => getPOST('safety_stock_' . $wh->id),
              'warehouse_id' => $wh->id
            ];
          }

          if (getPOST('pic_' . $wh->id) && getPOST('cycle_' . $wh->id)) {
            $product_data['stock_opname'][] = [
              'user_id'      => getPOST('pic_' . $wh->id),
              'so_cycle'     => getPOST('cycle_' . $wh->id),
              'warehouse_id' => $wh->id,
            ];
          }
        }
      }

      $price_groups = $this->site->getAllPriceGroups();
      if ($price_groups) {
        foreach ($price_groups as $pg) {
          if (getPOST('price_groups_' . $pg->id)) {
            $price_ranges = [];
            foreach (getPOST('price_groups_' . $pg->id) as $price_range) {
              $price_ranges[] = filterDecimal($price_range);
            }
            $product_data['price_groups'][] = [
              'price_group_id' => $pg->id,
              'price_ranges' => $price_ranges
            ];
          }
        }
      }

      //rd_print($product_data); die();
      if ($this->site->updateProducts([$product_data])) {
        $this->session->set_flashdata('message', lang('product_updated'));
        admin_redirect('products');
      }
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    }

    $this->data['product'] = $product;
    $this->data['productJS'] = getJSON($product->json_data);

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => lang('edit_product')]
    ];
    $meta = ['page_title' => lang('edit_product'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/edit', $this->data);
  }

  public function edit_adjustment($id)
  {
    $this->sma->checkPermissions('adjustments', true);

    $this->form_validation->set_rules('mode', lang('adjustment_mode'), 'required');
    $this->form_validation->set_rules('warehouse', lang('warehouse'), 'required');

    $adjustment = $this->site->getStockAdjustmentByID($id);

    if (!$id || !$adjustment) {
      $this->session->set_flashdata('error', lang('adjustment_not_found'));
      $this->sma->md();
    }

    if ($this->form_validation->run() == true) {
      $date = getPOST('date');
      $mode = getPOST('mode');
      $reference = getPOST('reference');
      $warehouse_id = getPOST('warehouse');
      $note         = $this->sma->clear_tags(getPOST('note'));

      $i = isset($_POST['product_id']) ? count($_POST['product_id']) : 0;
      for ($r = 0; $r < $i; $r++) {
        $product_id   = $_POST['product_id'][$r];
        $adj_quantity = $_POST['quantity'][$r];
        // $end_date -1s in order to prevent original stock.
        $end_date = date('Y-m-d H:i:s', strtotime($date . ' -1 second'));
        $current_stock = $this->site->getStockQuantity($product_id, $warehouse_id, ['end_date' => $end_date]);

        if (!is_numeric($adj_quantity)) continue;

        if ($mode == 'formula') {
          $quantity = $adj_quantity;
          $type     = 'received';
        } else if ($mode == 'overwrite') {
          if ($current_stock == $adj_quantity) continue;

          $adjusted = getAdjustedQty($current_stock, $adj_quantity); // Get Adjusted Quantity.
          $quantity = $adjusted['quantity'];
          $type     = $adjusted['type'];
        } else {
          die('Error');
        }

        $products[] = [
          'product_id'     => $product_id,
          'quantity'       => $quantity,
          'adjustment_qty' => $adj_quantity,
          'type'           => $type,
          'warehouse_id'   => $warehouse_id,
        ];
      }

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('products'), 'required');
      } else {
        krsort($products);
      }

      $adjustment_data = [
        'date'         => $date,
        'warehouse_id' => $warehouse_id,
        'mode'         => $mode,
        'note'         => $note,
      ];

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $adjustment_data['attachment'] = $uploader->storeRandom();
      }
    }

    if ($this->form_validation->run()) {
      if ($this->site->updateStockAdjustment($id, $adjustment_data, $products)) {
        $this->session->set_userdata('remove_qals', 1);
        $this->session->set_flashdata('message', lang('quantity_adjusted'));
      }
      admin_redirect('products/quantity_adjustments');
    } else {
      $inv_items = $this->site->getStockAdjustmentItems($id);

      foreach ($inv_items as $item) {
        $c           = sha1(uniqid(mt_rand(), true));
        $product     = $this->site->getProductByID($item->product_id);

        if ($product) {
          $row         = (object)[];
          $row->id     = $item->product_id;
          $row->code   = $product->code;
          $row->name   = $product->name;
          $row->qty    = $item->adjustment_qty;
          $row->source_qty = $this->site->getStockQuantity($product->id, $adjustment->warehouse_id);
          $ri          = $this->Settings->item_addition ? $product->id : $c;

          $pr[$ri] = [
            'id'      => $c,
            'item_id' => $row->id,
            'label'   => $row->name . ' (' . $row->code . ')',
            'row'     => $row
          ];
          $c++;
        }
      }

      $this->data['adjustment']       = $adjustment;
      $this->data['adjustment_items'] = json_encode($pr);
      $this->data['error']            = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['warehouses']       = $this->site->getAllWarehouses();

      $bc = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => admin_url('products/quantity_adjustments'), 'page' => lang('quantity_adjustments')],
        ['link' => '#', 'page' => lang('edit_adjustment')]
      ];
      $meta = ['page_title' => lang('edit_adjustment'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/edit_adjustment', $this->data);
    }
  }

  public function edit_category($id = null)
  {
    $this->load->helper('security');
    $this->form_validation->set_rules('code', lang('category_code'), 'trim|required');
    $pr_details = $this->site->getProductCategoryByID($id);
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
        'parent_code' => getPOST('parent'),
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
        if (!$this->upload->do_upload('csv_file')) {
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
      admin_redirect('products/categories');
    }

    if ($this->form_validation->run() == true && $this->site->updateProductCategory($id, $data)) {
      $this->session->set_flashdata('message', lang('category_updated'));
      admin_redirect('products/categories');
    } else {
      $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->data['category']   = $this->site->getProductCategoryByID($id);
      $this->data['categories'] = $this->site->getParentCategories();
      $this->load->view($this->theme . 'products/edit_category', $this->data);
    }
  }

  // public function get_suggestions()
  // {
  //   $term = getGET('term', true);
  //   if (strlen($term) < 1 || !$term) {
  //     die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
  //   }

  //   $rows = $this->products_model->getProductsForPrinting($term);
  //   if ($rows) {
  //     foreach ($rows as $row) {
  //       $pr[]     = ['id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'code' => $row->code, 'name' => $row->name, 'price' => $row->price, 'qty' => 1];
  //     }
  //     sendJSON($pr);
  //   } else {
  //     sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
  //   }
  // }

  public function getAdjustments($warehouse_id = null)
  {
    $this->sma->checkPermissions('adjustments');

    $delete_link = "<a href='#' class='tip po' title='" . $this->lang->line('delete_adjustment') . "' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('products/delete_adjustment/$1') . "'>"
      . lang('i_m_sure') . "</a><button class='btn po-close'>" . lang('no') . "</button>\" rel='popover'><i class=\"fad fa-trash\"></i></a>";

    $this->load->library('datatables');
    $this->datatables
      ->select("adjustments.id as id, date, reference, warehouses.name as wh_name, users.fullname as created_by, note, attachment")
      ->from('adjustments')
      ->join('warehouses', 'warehouses.id=adjustments.warehouse_id', 'left')
      ->join('users', 'users.id=adjustments.created_by', 'left')
      ->group_by('adjustments.id');
    if ($warehouse_id) {
      $this->datatables->where('adjustments.warehouse_id', $warehouse_id);
    }
    $this->datatables->add_column('Actions', "<div class='text-center'><a href='" . admin_url('products/edit_adjustment/$1') . "' class='tip' title='" . lang('edit_adjustment') . "'><i class='fad fa-edit'></i></a> " . $delete_link . '</div>', 'id');

    echo $this->datatables->generate();
  }

  public function getCategories()
  {
    $print_barcode = anchor('admin/products/print_barcodes/?category=$1', '<i class="fad fa-print"></i>', 'title="' . lang('print_barcodes') . '" class="tip"');

    $this->load->library('datatables');
    $this->datatables
      ->select("categories.id as id, categories.image, categories.code, categories.name, categories.slug, c.name as parent", false)
      ->from('categories')
      ->join('categories c', 'c.code=categories.parent_code', 'left')
      ->group_by('categories.id')
      ->add_column('Actions', '
        <div class="text-center">
          <a href="' . admin_url('products/edit_category/$1') . '" data-toggle="modal" data-target="#myModal" class="tip" title="' . lang('edit_category') . '">
            <i class="fad fa-edit"></i>
          </a>
          <a href="' . admin_url('products/delete_category/$1') . '" data-action="confirm"
            data-message="Are you sure to delete this category?" data-title="Delete Product Category">
            <i class="fad fa-trash"></i>
          </a>
        </div>', 'id');

    echo $this->datatables->generate();
  }

  public function getCounts($warehouse_id = null)
  {
    $this->sma->checkPermissions('stock_count', true);

    if ((!$this->Owner || !$this->Admin) && !$warehouse_id) {
      $user         = $this->site->getUser();
      $warehouse_id = $user->warehouse_id;
    }
    $detail_link = anchor('admin/products/view_count/$1', '<label class="label label-primary pointer">' . lang('details') . '</label>', 'class="tip" title="' . lang('details') . '" data-toggle="modal" data-target="#myModal"');

    $this->load->library('datatables');
    $this->datatables
      ->select("stock_counts.id as id, date, reference, warehouses.name as wh_name, type, brand_names, category_names, initial_file, final_file")
      ->from('stock_counts')
      ->join('warehouses', 'warehouses.id=stock_counts.warehouse_id', 'left');
    if ($warehouse_id) {
      $this->datatables->where('warehouse_id', $warehouse_id);
    }

    $this->datatables->add_column('Actions', '<div class="text-center">' . $detail_link . '</div>', 'id');
    echo $this->datatables->generate();
  }

  public function getProducts()
  {
    $this->sma->checkPermissions('index', TRUE, 'products');

    // $this->isAdmin = FALSE;

    $code         = (getGET('code') ?? NULL);
    $name         = (getGET('name') ?? NULL);
    $type         = (getGET('type') ?? NULL);
    $category_code = (getGET('category') ?? NULL);
    $supplier_id  = (getGET('supplier') ?? NULL);
    $warehouse_id = (getGET('warehouse') ?? NULL);
    $start_date   = (getGET('start_date') ?? NULL);
    $end_date     = (getGET('end_date') ?? date('Y-m-d'));

    $stockClause = '';

    if (!$this->isAdmin && !$warehouse_id) {
      $warehouse_id = XSession::get('warehouse_id');
    }

    $warehouse = ($warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL);

    if ($warehouse) {
      $stockClause .= "AND warehouse_id = {$warehouse->id} ";
    }

    if ($start_date) {
      $date_range = "AND `date` BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'";
      $stockClause .= "AND `date` BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'";
    } else {
      $date_range = '';
    }

    $category = $this->site->getProductCategoryByCode($category_code);

    if (empty($date_range)) {
      $dateRange = '&start_date=' . date('Y-m-') . '01&end_date=' . date('Y-m-d');
    } else {
      $dateRange = "&start_date={$start_date}&end_date={$end_date}";
    }

    if ($warehouse_id) {
      $historyWH = "&warehouse={$warehouse_id}";
    } else {
      $historyWH = '';
    }

    $detail_link = anchor('admin/products/view/$1', '<i class="fad fa-fw fa-file"></i> ' . lang('product_details'));
    $delete_link = "<a href='#' class='tip po' title='" . $this->lang->line('delete_product') . "' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete1' id='a__$1' href='" . admin_url('products/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
      . lang('delete_product') . '</a>';
    $history_link = '<a href="' . admin_url('products/history?product=$1' . $dateRange . $historyWH) .
      '" data-target="#myModal" data-toggle="modal" data-modal-class="modal-lg">
      <i class="fad fa-fw fa-history"></i> ' . lang('product_history') . '</a>';

    $action =
      "<div class=\"text-center\">
        <div class=\"btn-group text-left\">
          <button type=\"button\" class=\"btn btn-default btn-xs btn-primary dropdown-toggle\"
            data-toggle=\"dropdown\">" . lang('actions') . "<span class=\"caret\"></span></button>
          <ul class=\"dropdown-menu pull-right\" role=\"menu\">";
    $action .= "<li>{$detail_link}</li>";
    // Add Product
    $action .=
      '<li>
        <a href="' . admin_url('products/add/$1') . '">
          <i class="fad fa-fw fa-plus-square"></i> ' . lang('duplicate_product') . '</a>
      </li>';
    // Edit Product
    $action .=
      '<li><a href="' . admin_url('products/edit/$1') . '">
        <i class="fad fa-fw fa-edit"></i> ' . lang('edit_product') . '</a>
      </li>';

    // if ($this->isAdmin || getPermission('products-history')) {
      // History Product
      $action .= "<li>{$history_link}</li>";
    // }

    $action .= '<li class="divider"></li>';
    // Delete Product
    $action .= "<li>{$delete_link}</li>";

    $action .=
      '</ul>
      </div>
    </div>';

    $this->load->library('datatables');

    // getPermission here to prevent sql query collision
    $hasQtyPermission = getPermission('products-quantity');

    $query = "products.id as product_id, products.image as image,
      products.code as code, products.name as name,
      products.type as type, categories.name as cname,
      products.cost as cost, products.markon_price AS markon_price,";

    if ($this->isAdmin || $hasQtyPermission) {
      if ($start_date && $end_date) {
        $query .= "(COALESCE(stock_in.quantity, 0) - COALESCE(stock_out.quantity, 0)) AS quantity,";
      } else { // Default
        $query .= "products.quantity as quantity,";
      }
    }

    $query .= "units.code as unit, products.safety_stock";

    $this->datatables
      ->select($query)
      ->from('products')
      ->join('categories', 'products.category_id=categories.id', 'left')
      ->join('units', 'products.unit=units.id', 'left');

    if ($start_date && $end_date) {
      $this->datatables
        ->join("(SELECT product_id, SUM(quantity) AS quantity FROM stocks WHERE status LIKE 'received' {$stockClause} GROUP BY product_id) stock_in", "stock_in.product_id = products.id", 'left')
        ->join("(SELECT product_id, SUM(quantity) AS quantity FROM stocks WHERE status LIKE 'sent' {$stockClause} GROUP BY product_id) stock_out", "stock_out.product_id = products.id", 'left');
    }

    if ($code) {
      $this->datatables->like('products.code', $code, 'both');
    }

    if ($name) {
      $this->datatables->like('products.name', $name, 'both');
    }

    if ($type) {
      $this->datatables->like('products.type', $type, 'none');
    }

    if ($category) {
      $this->datatables->where('products.category_id', $category->id);
    }

    if (!$this->isAdmin) {
      if (!XSession::get('show_cost')) {
        $this->datatables->unset_column('cost');
        $this->datatables->unset_column('markon_price');
      }

      if (!XSession::get('show_price')) {
        $this->datatables->unset_column('price');
      }
    }

    if ($supplier_id) {
      $this->datatables->where('supplier_id', $supplier_id);
    }

    if ($warehouse) {
      $this->datatables
        ->group_start()
        ->like('products.warehouses', $warehouse->name, 'none')
        ->or_like('products.warehouses', '', 'none')
        ->group_end();
    }

    $this->datatables->add_column('Actions', $action, 'product_id, image, code, name');
    // echo $this->datatables->generate(['returnCompiled' => TRUE]);
    echo $this->datatables->generate();
  }

  public function getSubCategorySuggestions()
  {
    $term  = getGET('term');
    $limit = (getGET('limit') ?? 10);

    if ($category_code = getGET('code')) {
      $term = [];
      $term['code'] = $category_code;
    }

    if ($rows = $this->site->getSubCategorySuggestions($term, $limit)) {
      sendJSON(['results' => $rows]);
    }
    sendJSON(['results' => [['id' => '', 'text' => 'No Subcategory found.']]]);
  }

  public function getSubUnits()
  {
    $unit_id = getGET('id');

    if ($rows = $this->site->getUnitSuggestionsByBUID($unit_id)) {
      sendJSON(['results' => $rows]);
    }
    sendJSON(['results' => [['id' => '', 'text' => 'No Units found.']]]);
  }

  public function history()
  {
    $product_id   = getGET('product');
    $start_date   = getGET('start_date');
    $end_date     = getGET('end_date');
    $warehouse_id = getGET('warehouse');
    $export_xls   = (getGET('xls') == 1 ? TRUE : FALSE);

    $this->data['product_id']   = $product_id;
    $this->data['product']      = $this->site->getProductByID($product_id);
    $this->data['start_date']   = $start_date;
    $this->data['end_date']     = $end_date;
    $this->data['warehouse_id'] = $warehouse_id;
    $this->data['warehouse']    = $this->site->getWarehouseByID($warehouse_id);

    $clause = [];

    if ($product_id)   $clause['product_id']   = $product_id;
    if ($start_date)   $clause['start_date']   = $start_date;
    if ($end_date)     $clause['end_date']     = $end_date;
    if ($warehouse_id) $clause['warehouse_id'] = $warehouse_id;

    $clause['order'] = [
      'date', 'ASC'
    ];

    $rows = $this->site->getStocks($clause);
    unset($clause['start_date'], $clause['end_date'], $clause['order']);
    
    $beginning_qty  = ($start_date ? $this->site->getStockBeginningQuantity($clause, $start_date) : 0);

    $this->data['beginning_qty'] = $beginning_qty;
    $this->data['rows']          = $rows;

    if ($export_xls) {
      $total_balance = filterQuantity($beginning_qty);
      $total_decrease = 0;
      $total_increase = 0;
      $old_balance = 0;
      $old_decrease = 0;
      $old_increase = 0;
      $old_date = '';
      $iold_date = 0;
      $x = 2;

      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('Product History');
      $excel->setBold('A1:I1');
      $excel->setHorizontalAlign('A1:I1', 'center');
      $excel->setFillColor('A1:I1', 'FFFF00');
      $excel->setCellValue('A1', 'Stock ID');
      $excel->setCellValue('B1', 'Date');
      $excel->setCellValue('C1', 'Reference');
      $excel->setCellValue('D1', 'Warehouse');
      $excel->setCellValue('E1', 'Category');
      $excel->setCellValue('F1', 'Created By');
      $excel->setCellValue('G1', 'Increase');
      $excel->setCellValue('H1', 'Decrease');
      $excel->setCellValue('I1', 'Balance');

      if (!empty($rows)) {
        if ($beginning_qty > 0 || $beginning_qty < 0) {
          $excel->setBold("A2:I2");
          $excel->setCellValue('A2', '-');
          $excel->setCellValue('B2', $start_date . ' 00:00:00');
          $excel->mergeCells('C2:H2');
          $excel->setHorizontalAlign('C2', 'center');
          $excel->setCellValue('C2', 'BEGINNING');
          $excel->setCellValue('I2', filterQuantity($beginning_qty));

          $x++;
        }

        foreach ($rows as $row) {
          if ($row->status != 'received' && $row->status != 'sent') continue;

          $idate = strtotime($row->date);

          if ($iold_date && (date('m', $idate) != date('m', $iold_date))) { // Monthly Summary
            $excel->setBold("A{$x}:I{$x}");
            $excel->setCellValue("A{$x}", '-');
            $excel->setCellValue("B{$x}", $old_date . ' 23:59:59');
            $excel->mergeCells("C{$x}:F{$x}");
            $excel->setHorizontalAlign("C{$x}", 'center');
            $excel->setCellValue("C{$x}", 'SUMMARY ' . strtoupper(getMonthName(date('n', $iold_date)))); // Ex. SUMMARY JANUARY
            $excel->setCellValue("G{$x}", $old_increase);
            $excel->setCellValue("H{$x}", $old_decrease);
            $excel->setCellValue("I{$x}", $old_balance);
            $old_balance = 0;
            $old_decrease = 0;
            $old_increase = 0;
            $x++;
          }

          // BEGIN DATA
          $excel->setCellValue("A{$x}", $row->id);
          $excel->setCellValue("B{$x}", $row->date);

          $reference = '';
          if ($row->adjustment_id != NULL) {
            $reference = $this->site->getStockAdjustmentByID($row->adjustment_id)->reference;
          } else if ($row->internal_use_id != NULL) {
            $reference = $this->site->getStockInternalUseByID($row->internal_use_id)->reference;
          } else if ($row->purchase_id != NULL) {
            $reference = $this->site->getStockPurchaseByID($row->purchase_id)->reference;
          } else if ($row->sale_id != NULL) {
            $reference = $this->site->getSaleByID($row->sale_id)->reference;
          } else if ($row->transfer_id != NULL) {
            $transfer2 = ProductTransfer::getRow(['id' => $row->transfer_id]);

            if ($transfer2) {
              $reference = str_replace('TRF', 'TRF2', $transfer2->reference);
            } else {
              $reference = $this->site->getStockTransferByID($row->transfer_id)->reference;
            }
          }

          $excel->setCellValue("C{$x}", $reference);
          $excel->setCellValue("D{$x}", $row->warehouse_name);
          $excel->setCellValue("E{$x}", $row->category_code);

          $created_by = '';
          if ($row->created_by != NULL) {
            $user = $this->site->getUserByID($row->created_by);
            $created_by = ($user ? $user->fullname : '');
          }

          $excel->setCellValue("F{$x}", $created_by);

          $dec = 0;
          $inc = 0;

          if ($row->status == 'received') {
            $inc = $row->quantity;
            $total_increase = filterQuantity($total_increase + $inc);
          } else if ($row->status == 'sent') {
            $dec = $row->quantity;
            $total_decrease = filterQuantity($total_decrease + $dec);
          }

          $excel->setCellValue("G{$x}", ($inc ? $inc : ''));
          $excel->setCellValue("H{$x}", ($dec ? $dec : ''));

          if ($row->status == 'received') {
            $total_balance = filterQuantity($total_balance + $row->quantity);
          } else if ($row->status == 'sent') {
            $total_balance = filterQuantity($total_balance - $row->quantity);
          }

          $iold_date = $idate;
          $old_date = date('Y-m-d', $iold_date);
          $old_balance = $total_balance;
          $old_decrease += $dec;
          $old_increase += $inc;

          $excel->setCellValue("I{$x}", $total_balance);
          // END DATA

          $x++;
        }

        // LAST MONTHLY SUMMARY
        $excel->setBold("A{$x}:I{$x}");
        $excel->setCellValue("A{$x}", '-');
        $excel->setCellValue("B{$x}", ($end_date ? $end_date . date(' H:i:s') : ''));
        $excel->mergeCells("C{$x}:F{$x}");
        $excel->setHorizontalAlign("C{$x}", 'center');
        $excel->setCellValue("C{$x}", 'SUMMARY ' . strtoupper(getMonthName(date('m', $iold_date)))); // Ex. SUMMARY JANUARY
        $excel->setCellValue("G{$x}", $old_increase);
        $excel->setCellValue("H{$x}", $old_decrease);
        $excel->setCellValue("I{$x}", $old_balance);

        $x++;
      } else { // If no data available.
        $excel->mergeCells('A2:I2');
        $excel->setCellValue('A2', lang('no_data_available'));
        $excel->setHorizontalAlign('A2', 'center');
      }

      $excel->setBold("A{$x}:I{$x}");
      $excel->setCellValue("A{$x}", '-');
      $excel->setCellValue("B{$x}", ($end_date ? $end_date . date(' H:i:s') : ''));
      $excel->mergeCells("C{$x}:F{$x}");
      $excel->setHorizontalAlign("C{$x}", 'center');
      $excel->setCellValue("C{$x}", 'SUMMARY TOTAL');
      $excel->setCellValue("G{$x}", $total_increase);
      $excel->setCellValue("H{$x}", $total_decrease);
      $excel->setCellValue("I{$x}", $total_balance);

      // Set Auto Width
      $excel->setColumnAutoWidth('A');
      $excel->setColumnAutoWidth('B');
      $excel->setColumnAutoWidth('C');
      $excel->setColumnAutoWidth('D');
      $excel->setColumnAutoWidth('E');
      $excel->setColumnAutoWidth('F');
      $excel->setColumnAutoWidth('G');
      $excel->setColumnAutoWidth('H');
      $excel->setColumnAutoWidth('I');

      $excel->export('PrintERP - Product_History-' . date('Ymd_His'));
    }

    $this->load->view($this->theme . 'products/history', $this->data);
  }

  public function import()
  {
    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => lang('import_products')]
    ];
    $meta = ['page_title' => lang('import_products'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/import', $this->data);
  }

  public function import_categories()
  {
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        checkPath($this->upload_import_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->allowed_file_size;
        $config['overwrite']     = FALSE;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('products/categories');
        }

        $csv       = $this->upload->file_name;
        $arrResult = [];
        $handle    = fopen($this->upload_import_path . $csv, 'r');

        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== FALSE) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }

        $header_id  = array_shift($arrResult);
        $titles     = array_shift($arrResult);
        $updated    = '';
        $categories = $subcategories = [];

        if ($header_id[0] != 'PCAT') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('products/categories');
        }

        $keys = ['no', 'use', 'code', 'name', 'parent_code', 'description'];
        foreach ($arrResult as $value) {
          $csvs[] = array_combine($keys, $value);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] == 0) continue;
          $code  = trim($csv['code']);
          $name  = trim($csv['name']);
          $pcode = !empty($csv['parent_code']) ? trim($csv['parent_code']) : null;

          if ($code && $name) {
            $category = [
              'code'        => $code,
              'name'        => $name,
              'slug'        => $code,
              'image'       => 'no_image.png',
              'parent_code' => $pcode,
              'description' => isset($csv['description']) ? trim($csv['description']) : null,
            ];

            if (!empty($pcode) && ($pcategory = $this->site->getProductCategoryByCode($pcode))) {
              $category['parent_code'] = $pcategory->code;
            }

            if ($c = $this->site->getProductCategoryByCode($code)) {
              $updated .= '<p>' . lang('product_categories_updated') . ' (' . $code . ')</p>';
              $this->site->updateProductCategory($c->id, $category);
            } else {
              if ($category['parent_code']) {
                $subcategories[] = $category;
              } else {
                $categories[] = $category;
              }
            }
          }
        }
      }
    }

    if ($this->form_validation->run() == true && $this->site->addProductCategories($categories, $subcategories)) {
      $this->session->set_flashdata('message', lang('product_categories_added') . $updated);
      admin_redirect('products/categories');
    } else {
      if ((isset($categories) && empty($categories)) || (isset($subcategories) && empty($subcategories))) {
        if ($updated) {
          $this->session->set_flashdata('message', $updated);
        } else {
          $this->session->set_flashdata('warning', lang('data_x_categories'));
        }
        admin_redirect('products/categories');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['csv_file'] = [
        'name' => 'csv_file',
        'id'                          => 'csv_file',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('csv_file'),
      ];
      $this->load->view($this->theme . 'products/import_categories', $this->data);
    }
  }

  /**
   * Import CSV RAW Materials.
   */
  public function import_csv_raw() // PASSED.
  {
    ini_set('max_execution_time', 0);
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        checkPath($this->upload_products_imports_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_products_imports_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 50;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('products/import#raw_material');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->upload_products_imports_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        $header_id = array_shift($arrResult);
        $header    = array_shift($arrResult); // Ignored.
        $updated = 0;
        $items   = [];
        $keys    = [ // ss = safety_stock
          'no', 'use', 'code', 'name', 'base_unit', 'sale_unit', 'purchase_unit',
          'category_code', 'subcategory_code',
          'iuse_type', 'active', 'cost', 'markon', 'ss_ratio', 'min_order_qty', 'supplier',
          'warehouses', 'sn', 'priority', 'purchased_at',
          'lucretia_pic', 'lucretia_cycle', 'durian_pic', 'durian_cycle', 'fatmawati_pic', 'fatmawati_cycle',
          'gajah_pic', 'gajah_cycle', 'ngesrep_pic', 'ngesrep_cycle', 'pleburan_pic', 'pleburan_cycle',
          'salatiga_pic', 'salatiga_cycle', 'tembalang_pic', 'tembalang_cycle',
          'tlogosari_pic', 'tlogosari_cycle', 'ungaran_pic', 'ungaran_cycle', 'weleri_pic', 'weleri_cycle'
        ];

        if ($header_id[0] != 'RXMT') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('products/import#raw_material');
        }

        foreach ($arrResult as $value) {
          $csvs[] = arrayCombine($keys, $value);
        }

        $line = 1;

        $warehouses = $this->site->getWarehouses();

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $supplier = $this->site->getSupplierByCompanyName(rd_trim($csv['supplier']));

          $item = [
            'code'               => rd_trim($csv['code']),
            'name'               => rd_trim($csv['name']),
            'unit'               => rd_unit($csv['base_unit']),
            'cost'               => filterDecimal($csv['cost']),
            'price'              => filterDecimal($csv['cost']),
            'warehouses'         => rd_trim($csv['warehouses']),
            'markon_price'       => getMarkonPrice($csv['cost'], $csv['markon']), // Mark On Price
            'markon'             => filterDecimal($csv['markon']),
            'safety_stock_ratio' => filterDecimal($csv['ss_ratio']),
            'min_order_qty'      => filterDecimal($csv['min_order_qty']),
            'image'              => 'no_image.png',
            'iuse_type'          => strtolower(rd_trim($csv['iuse_type'])),
            'active'             => filterDecimal($csv['active']),
            'sale_unit'          => rd_unit($csv['sale_unit']),
            'purchase_unit'      => rd_unit($csv['purchase_unit']),
            'category_code'      => rd_trim($csv['category_code']),
            'barcode_symbology'  => 'code128',
            'type'               => 'standard',
            'subcategory_code'   => rd_trim($csv['subcategory_code']),
            'supplier_id'        => (!empty($supplier) ? $supplier->id : NULL),
            'sn'                 => rd_trim($csv['sn']),
            'priority'           => strtolower($csv['priority']),
            'purchased_at'       => trim($csv['purchased_at'])
          ];

          // Safety Stock and Stock Opname for every warehouse. No safety stock anymore!
          // $item['safety_stock'] = [];
          $item['stock_opname'] = [];
          $total_qty_alert = 0;

          foreach ($warehouses as $warehouse) {
            $wh_name = strtolower(explode(' ', $warehouse->name)[0]); // Get first name of warehouse.

            if ($wh_name == 'advertising') continue;
            // if (isset($csv[$wh_name . '_ss'])) {
            //   $item['safety_stock'][] = [
            //     'quantity'     => (filterDecimal($csv[$wh_name . '_ss']) ?? 0), // Default to 0 if empty.
            //     'warehouse_id' => $warehouse->id
            //   ];
            //   $total_qty_alert += filterDecimal($csv[$wh_name . '_ss']);
            // }

            // Stock Opname
            if (isset($csv[$wh_name . '_pic'])) {
              $user = $this->site->getUserByName($csv[$wh_name . '_pic']);

              $item['stock_opname'][] = [
                'user_id'      => (!empty($user) ? $user->id : 0),
                'so_cycle'     => (!empty($user) ? $csv[$wh_name . '_cycle'] : 1),
                'warehouse_id' => $warehouse->id
              ];
            }
          }

          unset($total_qty_alert);

          if ($catd = $this->site->getProductCategoryByCode($item['category_code'])) { // Check Product Unit.
            $base_unit     = $this->site->getUnitByCode($item['unit']);
            $sale_unit     = $this->site->getUnitByCode($item['sale_unit']);
            $purchase_unit = $this->site->getUnitByCode($item['purchase_unit']);
            $subcategory   = $this->site->getProductCategoryByCode($item['subcategory_code']);

            $base_unit_id     = $base_unit     ? $base_unit->id : NULL;
            $sale_unit_id     = $sale_unit     ? $sale_unit->id : NULL;
            $purchase_unit_id = $purchase_unit ? $purchase_unit->id : NULL;

            if ($base_unit_id) {
              $units = $this->site->getUnitsByBUID($base_unit_id);

              foreach ($units as $u) {
                if ($u->code == $item['sale_unit']) {
                  $sale_unit_id = $u->id;
                }

                if ($u->code == $item['purchase_unit']) {
                  $purchase_unit_id = $u->id;
                }
              }
            } else {
              $this->session->set_flashdata('error', lang('check_unit') . ' (' . $item['unit'] . '). ' . lang('unit_code_x_exist') . ' ' . lang('line_no') . ' ' . ($line + 1));
              admin_redirect('products/import#raw_material');
            }

            unset($item['category_code'], $item['subcategory_code']);
            // Assign unit as id.
            $item['unit']           = $base_unit_id;
            $item['sale_unit']      = $sale_unit_id;
            $item['category_id']    = $catd->id;
            $item['subcategory_id'] = ($subcategory ? $subcategory->id : NULL);
            $item['purchase_unit']  = $purchase_unit_id;

            if ($product = $this->site->getProductByCode($item['code'])) { // If product code exists.
              if ($product->type == 'standard') { // If Product type Standard present.
                $item['product_id'] = $product->id; // Required by update.

                if ($this->site->updateProducts([$item])) {
                  $updated++;
                }
              }

              $item = false;
            }
          } else {
            $this->session->set_flashdata('error', lang('check_category_code') . ' (' . $item['category_code'] . '). ' . lang('category_code_x_exist') . ' ' . lang('line_no') . ' ' . ($line + 1));
            admin_redirect('products/import#raw_material');
          }

          if ($item) {
            $items[] = $item; // For add new.
          }

          $line++;
        } // foreach
      } // isset($_FILES['userfile'])
    }

    if ($this->form_validation->run() && !empty($items)) {
      if ($this->site->addProducts($items)) { // csv_raw
        $updated = $updated ? '<p>' . sprintf(lang('products_updated'), $updated) . '</p>' : '';
        $this->session->set_flashdata('message', sprintf(lang('products_added'), count($items)) . $updated);
        admin_redirect('products');
      }
    } else {
      if (isset($items) && empty($items)) {
        if ($updated) {
          $this->session->set_flashdata('message', sprintf(lang('products_updated'), $updated));
          admin_redirect('products');
        } else {
          $this->session->set_flashdata('warning', lang('csv_issue'));
        }
        admin_redirect('products/import#raw_material');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'    => 'userfile',
        'type'  => 'text',
        'value' => $this->form_validation->set_value('userfile'),
      ];

      $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('import_products_by_csv')]];
      $meta = ['page_title' => lang('import_products_by_csv'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/import_csv_raw', $this->data);
    }
  }

  /**
   * Import CSV Selling Products.
   */
  public function import_csv_spd() // PASSED. 2020-08-18
  {
    //$this->sma->checkPermissions('csv');
    ini_set('max_execution_time', 0);
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        checkPath($this->upload_products_imports_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_products_imports_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 50;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('products/import#selling_product');
        }

        $csv = $this->upload->file_name;

        $csv_rows = [];
        $handle    = fopen($this->upload_products_imports_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 10000, ',')) !== false) {
            $csv_rows[] = $row;
          }
          fclose($handle);
        }

        $header_id    = array_shift($csv_rows);
        $zone_id      = array_shift($csv_rows);
        $price_ranges = array_shift($csv_rows);
        $header = array_shift($csv_rows);
        $added = 0;
        $updated = 0;

        if ($header_id[0] != 'SLPRD') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('products/import#selling_product');
        }

        /**
         * Column: warehouses
         * empty or (*) = all warehouses.
         * tembalang, pleburan = not all warehouses, tembalang and pleburan only.
         * -durian, -ungaran = all warehouses except durian and ungaran.
         * -gajah, weleri = all warehouses except gajah, weleri ignored.
         * fatmawati, -salatiga = not all warehouses, fatmawati only, salatiga ignored.
         */
        $keys = [
          'code', 'use', 'name', 'category_code', 'warehouses', 'unit', 'raw_code', 'raw_qty',
          'active', 'autocomplete', 'min_prod_time', 'prod_time_qty',
          'price_range_1', 'price_range_2', 'price_range_3', 'price_range_4', 'price_range_5', 'price_range_6',
          'zone_1_price_1', 'zone_1_price_2', 'zone_1_price_3', 'zone_1_price_4', 'zone_1_price_5', 'zone_1_price_6',
          'zone_2_price_1', 'zone_2_price_2', 'zone_2_price_3', 'zone_2_price_4', 'zone_2_price_5', 'zone_2_price_6',
          'zone_3_price_1', 'zone_3_price_2', 'zone_3_price_3', 'zone_3_price_4', 'zone_3_price_5', 'zone_3_price_6',
          'zone_4_price_1', 'zone_4_price_2', 'zone_4_price_3', 'zone_4_price_4', 'zone_4_price_5', 'zone_4_price_6',
          'zone_5_price_1', 'zone_5_price_2', 'zone_5_price_3', 'zone_5_price_4', 'zone_5_price_5', 'zone_5_price_6',
          'zone_6_price_1', 'zone_6_price_2', 'zone_6_price_3', 'zone_6_price_4', 'zone_6_price_5', 'zone_6_price_6',
          'priv_a_price_1', 'priv_a_price_2', 'priv_a_price_3', 'priv_a_price_4', 'priv_a_price_5', 'priv_a_price_6',
          'priv_b_price_1', 'priv_b_price_2', 'priv_b_price_3', 'priv_b_price_4', 'priv_b_price_5', 'priv_b_price_6',
          'priv_c_price_1', 'priv_c_price_2', 'priv_c_price_3', 'priv_c_price_4', 'priv_c_price_5', 'priv_c_price_6',
          'priv_d_price_1', 'priv_d_price_2', 'priv_d_price_3', 'priv_d_price_4', 'priv_d_price_5', 'priv_d_price_6',
          'priv_e_price_1', 'priv_e_price_2', 'priv_e_price_3', 'priv_e_price_4', 'priv_e_price_5', 'priv_e_price_6'
        ];

        foreach ($csv_rows as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }

        $raw_items = [];
        $sell_item = [];
        $price_groups = [];
        $use_product = FALSE;

        foreach ($csvs as $csv) {
          // Begin columns initialization.
          $col_code          = rd_trim($csv['code']);
          $col_use           = ($csv['use'] == 1 ? TRUE : FALSE);
          $col_name          = rd_trim($csv['name']);
          $col_category_code = rd_trim($csv['category_code']);
          $col_unit          = rd_trim($csv['unit']);
          $col_raw_code      = rd_trim($csv['raw_code']);
          $col_raw_qty       = floatval(rd_trim($csv['raw_qty']));
          $col_min_prod_time = rd_trim($csv['min_prod_time']);
          $col_prod_time_qty = rd_trim($csv['prod_time_qty']);
          $col_price_ranges  = [ // 6, 11, 51, 101, 201
            intval(rd_trim($csv['price_range_2'])),
            intval(rd_trim($csv['price_range_3'])),
            intval(rd_trim($csv['price_range_4'])),
            intval(rd_trim($csv['price_range_5'])),
            intval(rd_trim($csv['price_range_6']))
          ];
          $col_price_groups = [ // index: 0 - 5, group: 1 - 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
            [rd_trim($csv['zone_1_price_1']), rd_trim($csv['zone_1_price_2']), rd_trim($csv['zone_1_price_3']), rd_trim($csv['zone_1_price_4']), rd_trim($csv['zone_1_price_5']), rd_trim($csv['zone_1_price_6'])], // 1
            [rd_trim($csv['zone_2_price_1']), rd_trim($csv['zone_2_price_2']), rd_trim($csv['zone_2_price_3']), rd_trim($csv['zone_2_price_4']), rd_trim($csv['zone_2_price_5']), rd_trim($csv['zone_2_price_6'])], // 2
            [rd_trim($csv['zone_3_price_1']), rd_trim($csv['zone_3_price_2']), rd_trim($csv['zone_3_price_3']), rd_trim($csv['zone_3_price_4']), rd_trim($csv['zone_3_price_5']), rd_trim($csv['zone_3_price_6'])], // 3
            [rd_trim($csv['zone_4_price_1']), rd_trim($csv['zone_4_price_2']), rd_trim($csv['zone_4_price_3']), rd_trim($csv['zone_4_price_4']), rd_trim($csv['zone_4_price_5']), rd_trim($csv['zone_4_price_6'])], // 4
            [rd_trim($csv['zone_5_price_1']), rd_trim($csv['zone_5_price_2']), rd_trim($csv['zone_5_price_3']), rd_trim($csv['zone_5_price_4']), rd_trim($csv['zone_5_price_5']), rd_trim($csv['zone_5_price_6'])], // 5
            [rd_trim($csv['zone_6_price_1']), rd_trim($csv['zone_6_price_2']), rd_trim($csv['zone_6_price_3']), rd_trim($csv['zone_6_price_4']), rd_trim($csv['zone_6_price_5']), rd_trim($csv['zone_6_price_6'])], // 6
            [rd_trim($csv['priv_a_price_1']), rd_trim($csv['priv_a_price_2']), rd_trim($csv['priv_a_price_3']), rd_trim($csv['priv_a_price_4']), rd_trim($csv['priv_a_price_5']), rd_trim($csv['priv_a_price_6'])], // A
            [rd_trim($csv['priv_b_price_1']), rd_trim($csv['priv_b_price_2']), rd_trim($csv['priv_b_price_3']), rd_trim($csv['priv_b_price_4']), rd_trim($csv['priv_b_price_5']), rd_trim($csv['priv_b_price_6'])], // B
            [rd_trim($csv['priv_c_price_1']), rd_trim($csv['priv_c_price_2']), rd_trim($csv['priv_c_price_3']), rd_trim($csv['priv_c_price_4']), rd_trim($csv['priv_c_price_5']), rd_trim($csv['priv_c_price_6'])], // C
            [rd_trim($csv['priv_d_price_1']), rd_trim($csv['priv_d_price_2']), rd_trim($csv['priv_d_price_3']), rd_trim($csv['priv_d_price_4']), rd_trim($csv['priv_d_price_5']), rd_trim($csv['priv_d_price_6'])], // D
            [rd_trim($csv['priv_e_price_1']), rd_trim($csv['priv_e_price_2']), rd_trim($csv['priv_e_price_3']), rd_trim($csv['priv_e_price_4']), rd_trim($csv['priv_e_price_5']), rd_trim($csv['priv_e_price_6'])]  // E
          ];
          // End columns initialization.

          // Begin Parsing.
          $is_selling_item = (!empty($col_code) && !empty($col_raw_code) ? TRUE : FALSE);
          $is_raw_item     = (empty($col_code) && !empty($col_raw_code)    ? TRUE : FALSE);

          if ($is_selling_item) {
            if ($use_product && !empty($sell_item)) {
              $product = $this->site->getProductByCode($sell_item['code']);
              $sell_item['price'] = $this->site->getTotalComboPricesByRawItems($raw_items);

              if (!$product) { // Check if product not exist then ADD else UPDATE.
                $sell_item['combo_items']   = $raw_items;
                // $sell_item['min_prod_time'] = $col_min_prod_time;
                // $sell_item['prod_time_qty'] = $col_prod_time_qty;

                if ($this->site->addProducts([$sell_item])) { // Insert selling product.
                  $group_id = 1; // Begin group from 1 to 6

                  $new_product = $this->site->getProductByCode($sell_item['code']); // Get new added product.

                  foreach ($price_groups as $price_group) {
                    $ppData = [
                      'product_id' => $new_product->id,
                      'price_group_id' => $group_id,
                      'price'  => $price_group[0],
                      'price2' => $price_group[1],
                      'price3' => $price_group[2],
                      'price4' => $price_group[3],
                      'price5' => $price_group[4],
                      'price6' => $price_group[5],
                    ];

                    $this->site->addProductPrices($ppData);

                    $group_id++;
                  }

                  $added++;
                }
              } else { // !product
                $sell_item['combo_items'] = $raw_items;
                $sell_item['product_id'] = $product->id;
                if ($this->site->updateProducts([$sell_item])) {
                  $group_id = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)

                  foreach ($price_groups as $price_group) {
                    $ppData = [
                      'product_id' => $product->id,
                      'price_group_id' => $group_id,
                      'price'  => $price_group[0],
                      'price2' => $price_group[1],
                      'price3' => $price_group[2],
                      'price4' => $price_group[3],
                      'price5' => $price_group[4],
                      'price6' => $price_group[5],
                    ];

                    $this->site->addProductPrices($ppData);

                    $group_id++;
                  }

                  $updated++;
                }
              } // !product

              // Reset parsing.
              $price_groups = [];
              $raw_items = [];
              $sell_item = [];
            } else {
              // Reset parsing.
              $price_groups = [];
              $raw_items = [];
              $sell_item = [];
            }

            $use_product = $col_use;
            if (!$use_product) continue;

            $unit = $this->site->getUnitByCode($col_unit);
            if (!$unit) {
              $this->session->set_flashdata('warning', sprintf("Unit '%s' (%s) cannot be found. Product '%s' skipped", $col_unit, rd_unit($col_unit), $col_code));
              continue;
            }

            $sell_item = [
              'code'               => $col_code,
              'name'               => $col_name,
              'category_id'        => $this->site->getCategoryByCode($col_category_code)->id,
              'subcategory_id'     => 0,
              'barcode_symbology'  => 'code128',
              'type'               => 'combo',
              'cost'               => 0,
              'price'              => 0,
              'active'             => filterDecimal($csv['active']),
              'warehouses'         => $csv['warehouses'],
              'unit'               => $unit->id,
              'sale_unit'          => $unit->id,
              'purchase_unit'      => 0,
              'image'              => 'no_image.png',
              'safety_stock'       => 0,
              'price_ranges_value' => $col_price_ranges,
              'quantity'           => 0,
              'autocomplete'       => filterDecimal($csv['autocomplete']),
              'min_prod_time'      => $col_min_prod_time,
              'prod_time_qty'      => $col_prod_time_qty
            ];

            $product = $this->site->getProductByCode($col_raw_code);

            $raw_items[] = [
              'item_code'  => $col_raw_code,
              'quantity'   => $col_raw_qty,
              'unit_price' => ($product ? $product->price : 0)
            ];

            $price_groups = $col_price_groups;
          } else if ($is_raw_item && $use_product) {
            // Parse RAW material to be included.
            if ($product = $this->site->getProductByCode($col_raw_code)) { // Check if RAW item exists.
              $raw_items[] = [
                'item_code'  => $col_raw_code, // rd_trim() function see app/helper/ridintek_helper.php
                'quantity'   => $col_raw_qty,
                'unit_price' => $product->price
              ];
            }
          }
        } // End foreach
      }
    }

    if ($this->form_validation->run() == TRUE && $added) {
      $updated = $updated ? '<p>' . sprintf(lang('products_updated'), $updated) . '</p>' : '';
      $this->session->set_flashdata('message', sprintf(lang('products_added'), $added) . $updated);
      admin_redirect('products');
    } else {
      if (isset($sell_item)) {
        if ($updated) {
          $this->session->set_flashdata('message', sprintf(lang('products_updated'), $updated));
          admin_redirect('products');
        } else {
          $this->session->set_flashdata('warning', lang('csv_issue'));
        }
        admin_redirect('products/import#selling_product');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name'  => 'userfile',
        'id'    => 'userfile',
        'type'  => 'text',
        'value' => $this->form_validation->set_value('userfile'),
      ];

      $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('import_products_by_csv')]];
      $meta = ['page_title' => lang('import_products_by_csv'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/import_csv_spd', $this->data);
    }
  }

  /**
   * Import CSV Services.
   */
  public function import_csv_svc() // PASSED.
  {
    //$this->sma->checkPermissions('csv');
    ini_set('max_execution_time', 0);
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['userfile'])) {
        checkPath($this->upload_products_imports_path);

        $this->load->library('upload');
        $config['upload_path']   = $this->upload_products_imports_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 50;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload()) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('products/import#service');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->upload_products_imports_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }

        $header_id    = array_shift($arrResult);
        $zone_id      = array_shift($arrResult);
        $price_ranges = array_shift($arrResult);
        $header = array_shift($arrResult);
        $updated = 0;
        $items   = [];
        $group_price_groups = [];
        $price_groups = [];

        if ($header_id[0] != 'SRVCX') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('products/import#service');
        }

        $key  = 0;
        $keys = [
          'no', 'use', 'code', 'name', 'category_code', 'warehouses', 'active', 'autocomplete', 'min_prod_time', 'prod_time_qty',
          'price_range_1', 'price_range_2', 'price_range_3', 'price_range_4', 'price_range_5', 'price_range_6',
          'zone_1_price_1', 'zone_1_price_2', 'zone_1_price_3', 'zone_1_price_4', 'zone_1_price_5', 'zone_1_price_6',
          'zone_2_price_1', 'zone_2_price_2', 'zone_2_price_3', 'zone_2_price_4', 'zone_2_price_5', 'zone_2_price_6',
          'zone_3_price_1', 'zone_3_price_2', 'zone_3_price_3', 'zone_3_price_4', 'zone_3_price_5', 'zone_3_price_6',
          'zone_4_price_1', 'zone_4_price_2', 'zone_4_price_3', 'zone_4_price_4', 'zone_4_price_5', 'zone_4_price_6',
          'zone_5_price_1', 'zone_5_price_2', 'zone_5_price_3', 'zone_5_price_4', 'zone_5_price_5', 'zone_5_price_6',
          'zone_6_price_1', 'zone_6_price_2', 'zone_6_price_3', 'zone_6_price_4', 'zone_6_price_5', 'zone_6_price_6',
          'priv_a_price_1', 'priv_a_price_2', 'priv_a_price_3', 'priv_a_price_4', 'priv_a_price_5', 'priv_a_price_6',
          'priv_b_price_1', 'priv_b_price_2', 'priv_b_price_3', 'priv_b_price_4', 'priv_b_price_5', 'priv_b_price_6',
          'priv_c_price_1', 'priv_c_price_2', 'priv_c_price_3', 'priv_c_price_4', 'priv_c_price_5', 'priv_c_price_6',
          'priv_d_price_1', 'priv_d_price_2', 'priv_d_price_3', 'priv_d_price_4', 'priv_d_price_5', 'priv_d_price_6',
          'priv_e_price_1', 'priv_e_price_2', 'priv_e_price_3', 'priv_e_price_4', 'priv_e_price_5', 'priv_e_price_6'
        ];

        foreach ($arrResult as $value) {
          $csvs[] = array_combine($keys, $value);
        }

        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue;
          $key++;
          $item = [
            'code'               => rd_trim($csv['code']),
            'name'               => rd_trim($csv['name']),
            'unit'               => 0,
            'sale_unit'          => 0,
            'purchase_unit'      => 0,
            'category_code'      => rd_trim($csv['category_code']),
            'cost'               => 0,
            'price'              => filterDecimal($csv['zone_1_price_1']),
            'warehouses'         => rd_trim($csv['warehouses']),
            'markon_price'       => 0,
            'markon'             => 0,
            'safety_stock'       => 0,
            'active'             => filterDecimal($csv['active']),
            'barcode_symbology'  => 'code128',
            'type'               => 'service',
            'image'              => 'no_image.png',
            'supplier_id'        => '',
            'autocomplete'       => $csv['autocomplete'],
            'min_prod_time'      => filterDecimal($csv['min_prod_time']),
            'prod_time_qty'      => filterDecimal($csv['prod_time_qty']),
            'price_ranges_value' => [ // 6, 11, 51, 101, 201
              intval(rd_trim($csv['price_range_2'])),
              intval(rd_trim($csv['price_range_3'])),
              intval(rd_trim($csv['price_range_4'])),
              intval(rd_trim($csv['price_range_5'])),
              intval(rd_trim($csv['price_range_6']))
            ]
          ];

          $price_groups = [ // index: 0 - 5, group: 1 - 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
            [rd_trim($csv['zone_1_price_1']), rd_trim($csv['zone_1_price_2']), rd_trim($csv['zone_1_price_3']), rd_trim($csv['zone_1_price_4']), rd_trim($csv['zone_1_price_5']), rd_trim($csv['zone_1_price_6'])], // 1
            [rd_trim($csv['zone_2_price_1']), rd_trim($csv['zone_2_price_2']), rd_trim($csv['zone_2_price_3']), rd_trim($csv['zone_2_price_4']), rd_trim($csv['zone_2_price_5']), rd_trim($csv['zone_2_price_6'])], // 2
            [rd_trim($csv['zone_3_price_1']), rd_trim($csv['zone_3_price_2']), rd_trim($csv['zone_3_price_3']), rd_trim($csv['zone_3_price_4']), rd_trim($csv['zone_3_price_5']), rd_trim($csv['zone_3_price_6'])], // 3
            [rd_trim($csv['zone_4_price_1']), rd_trim($csv['zone_4_price_2']), rd_trim($csv['zone_4_price_3']), rd_trim($csv['zone_4_price_4']), rd_trim($csv['zone_4_price_5']), rd_trim($csv['zone_4_price_6'])], // 4
            [rd_trim($csv['zone_5_price_1']), rd_trim($csv['zone_5_price_2']), rd_trim($csv['zone_5_price_3']), rd_trim($csv['zone_5_price_4']), rd_trim($csv['zone_5_price_5']), rd_trim($csv['zone_5_price_6'])], // 5
            [rd_trim($csv['zone_6_price_1']), rd_trim($csv['zone_6_price_2']), rd_trim($csv['zone_6_price_3']), rd_trim($csv['zone_6_price_4']), rd_trim($csv['zone_6_price_5']), rd_trim($csv['zone_6_price_6'])], // 6
            [rd_trim($csv['priv_a_price_1']), rd_trim($csv['priv_a_price_2']), rd_trim($csv['priv_a_price_3']), rd_trim($csv['priv_a_price_4']), rd_trim($csv['priv_a_price_5']), rd_trim($csv['priv_a_price_6'])], // A
            [rd_trim($csv['priv_b_price_1']), rd_trim($csv['priv_b_price_2']), rd_trim($csv['priv_b_price_3']), rd_trim($csv['priv_b_price_4']), rd_trim($csv['priv_b_price_5']), rd_trim($csv['priv_b_price_6'])], // B
            [rd_trim($csv['priv_c_price_1']), rd_trim($csv['priv_c_price_2']), rd_trim($csv['priv_c_price_3']), rd_trim($csv['priv_c_price_4']), rd_trim($csv['priv_c_price_5']), rd_trim($csv['priv_c_price_6'])], // C
            [rd_trim($csv['priv_d_price_1']), rd_trim($csv['priv_d_price_2']), rd_trim($csv['priv_d_price_3']), rd_trim($csv['priv_d_price_4']), rd_trim($csv['priv_d_price_5']), rd_trim($csv['priv_d_price_6'])], // D
            [rd_trim($csv['priv_e_price_1']), rd_trim($csv['priv_e_price_2']), rd_trim($csv['priv_e_price_3']), rd_trim($csv['priv_e_price_4']), rd_trim($csv['priv_e_price_5']), rd_trim($csv['priv_e_price_6'])]  // E
          ];

          if ($catd = $this->site->getCategoryByCode($item['category_code'])) { // Product Category must be present.
            unset($item['category_code']);
            $item['category_id']    = $catd->id;

            if ($product = $this->site->getProductByCode($item['code'])) { // If product code exists then UPDATE.

              if ($product->type == 'service') { // If Product type Service present.
                $item['product_id'] = $product->id;
                if ($this->site->updateProducts([$item])) {
                  $group_id = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)

                  foreach ($price_groups as $price_group) {
                    $ppData = [
                      'product_id' => $product->id,
                      'price_group_id' => $group_id,
                      'price'  => $price_group[0],
                      'price2' => $price_group[1],
                      'price3' => $price_group[2],
                      'price4' => $price_group[3],
                      'price5' => $price_group[4],
                      'price6' => $price_group[5],
                    ];

                    $this->site->addProductPrices($ppData);

                    $group_id++;
                  }
                  $updated++;
                }
              }
              $item = []; // Empty the product.
            }
          } else {
            $this->session->set_flashdata('error', lang('check_category_code') . ' (' . $item['category_code'] . '). ' . lang('category_code_x_exist') . ' ' . lang('line_no') . ' ' . ($key + 1));
            admin_redirect('products/import#service');
          }

          if ($item) {
            $group_price_groups[] = $price_groups;
            $items[] = $item;
          }
        } // foreach
      } // isset($_FILES['userfile'])
    }

    if ($this->form_validation->run() == true && !empty($items)) {
      unset($price_groups);
      if ($this->site->addProducts($items)) { // csv_service
        $item_index = 0;
        foreach ($items as $item) {
          $group_id = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
          $new_product = $this->site->getProductByCode($item['code']); // Get new added product.

          $price_groups = $group_price_groups[$item_index]; // Fixed.
          foreach ($price_groups as $price_group) {
            $ppData = [
              'product_id' => $new_product->id,
              'price_group_id' => $group_id,
              'price'  => $price_group[0],
              'price2' => $price_group[1],
              'price3' => $price_group[2],
              'price4' => $price_group[3],
              'price5' => $price_group[4],
              'price6' => $price_group[5],
            ];

            $this->site->addProductPrices($ppData);

            // $this->settings_model->setProductPriceForPriceGroup(
            //   $new_product->id,
            //   $group_id,
            //   $price_group[0],
            //   $price_group[1],
            //   $price_group[2],
            //   $price_group[3],
            //   $price_group[4],
            //   $price_group[5]
            // ); // Insert price group.
            $group_id++;
          }
          $item_index++;
        }

        $updated = $updated ? '<p>' . sprintf(lang('products_updated'), $updated) . '</p>' : '';
        $this->session->set_flashdata('message', sprintf(lang('products_added'), count($items)) . $updated);
        admin_redirect('products');
      }
    } else {
      if (isset($items) && empty($items)) {
        if ($updated) {
          $this->session->set_flashdata('message', sprintf(lang('products_updated'), $updated));
          admin_redirect('products');
        } else {
          $this->session->set_flashdata('warning', lang('csv_issue'));
        }
        admin_redirect('products/import#service');
      }

      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name' => 'userfile',
        'id'                          => 'userfile',
        'type'                        => 'text',
        'value'                       => $this->form_validation->set_value('userfile'),
      ];

      $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('import_products_by_csv')]];
      $meta = ['page_title' => lang('import_products_by_csv'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('products/import_csv_svc', $this->data);
    }
  }

  protected function import_sync()
  {
    if ($this->requestMethod != 'POST') {
      $this->response(400, ['message' => 'Request method is not supported.']);
    }

    ini_set('max_execution_time', 0);

    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $this->response(400, ['message' => 'Sync mode is not supported.']);
  }

  /**
   * Sync raw materials.
   */
  protected function import_sync_raw()
  {
    $sheets = getGoogleSheet('1arv83XA2ySRAos6aFvhqLWIm804CjgUyChj7DsxaBj0', 'A3:AN');

    if (!$sheets) $this->response(400, ['message' => 'Something wrong with google sheets.']);

    $headers = [ // ss = safety_stock
      'action', 'code', 'name', 'unit', 'category_code', 'subcategory_code',
      'iuse_type', 'active', 'cost', 'markon', 'ss_ratio', 'min_order_qty', 'supplier',
      'warehouses', 'sn', 'priority', 'purchased_at', 'purchase_source',
      'lucretia_pic', 'lucretia_cycle', 'durian_pic', 'durian_cycle', 'fatmawati_pic', 'fatmawati_cycle',
      'gajah_pic', 'gajah_cycle', 'ngesrep_pic', 'ngesrep_cycle', 'pleburan_pic', 'pleburan_cycle',
      'salatiga_pic', 'salatiga_cycle', 'tembalang_pic', 'tembalang_cycle',
      'tlogosari_pic', 'tlogosari_cycle', 'ungaran_pic', 'ungaran_cycle', 'weleri_pic', 'weleri_cycle'
    ];

    $csvs = [];
    $added = 0;
    $deleted = 0;
    $updated = 0;
    $addProductsData = [];
    $updateProductsData = [];

    foreach ($sheets as $sheet) {
      $csvs[] = arrayCombine($headers, $sheet);
    }

    foreach ($csvs as $csv) {
      if ($csv['action'] == 2064) {
        if ($item = $this->site->getProduct(['code' => $csv['code']])) {
          if ($this->site->deleteProduct($item->id)) {
            $deleted++;
          }
        }
      }

      // Add/Update items.
      if ($csv['action'] != 1) continue;

      // If item exists.
      if ($item = $this->site->getProduct(['code' => $csv['code']])) {
        $category = $this->site->getCategory(['code' => $csv['category_code']]);
        $subcategory  = $this->site->getCategory(['code' => $csv['subcategory_code']]);
        $supplier     = $this->site->getSupplierByCompanyName($csv['supplier']);
        $unit         = $this->site->getUnit(['code' => rd_unit($csv['unit'])]);

        if (!$category) {
          $this->response(404, ['message' => "Category {$csv['category_code']} not found."]);
        }
        if (!$unit) {
          $this->response(404, ['message' => "Unit {$csv['unit']} not found."]);
        }

        if ($warehouses = $this->site->getWarehouses(['active' => 1])) {
          $stockOpname = [];

          foreach ($warehouses as $warehouse) {
            $whName = strtolower(explode(' ', $warehouse->name)[0]);

            if (isset($csv[$whName . '_pic'])) {
              $pic = $this->site->getUserByName($csv[$whName . '_pic']);

              $stockOpname[] = [
                'user_id' => ($pic ? $pic->id : 0),
                'so_cycle' => ($pic ? $csv[$whName . '_cycle'] : 1),
                'warehouse_id' => $warehouse->id
              ];
            }
          }
        }

        $cost = filterDecimal($csv['cost']);

        $updateProductsData[] = [
          'product_id'          => $item->id, // Required for update.
          'code'                => $csv['code'],
          'name'                => $csv['name'],
          'category_id'         => $category->id,
          'subcategory_id'      => ($subcategory ? $subcategory->id : NULL),
          'unit'                => $unit->id,
          'cost'                => $cost,
          'warehouses'          => trim($csv['warehouses']),
          'markon_price'        => getMarkonPrice($cost, $csv['markon']),
          'markon'              => filterDecimal($csv['markon']),
          'safety_stock_ratio'  => filterDecimal($csv['ss_ratio']),
          'min_order_qty'       => (!empty($csv['min_order_qty']) ? $csv['min_order_qty'] : 1),
          'iuse_type'           => trim(strtolower($csv['iuse_type'])),
          'active'              => ($csv['active'] == 1 ? 1 : 0),
          'type'                => 'standard',
          'supplier_id'         => ($supplier ? $supplier->id : NULL),
          'sn'                  => trim($csv['sn']),
          'priority'            => strtolower($csv['priority']),
          'purchased_at'        => trim($csv['purchased_at']),
          'purchase_source'     => strtolower($csv['purchase_source']),
          'stock_opname'        => $stockOpname
        ];
      } else { // Add new items.
        $category = $this->site->getCategory(['code' => $csv['category_code']]);
        $subcategory  = $this->site->getCategory(['code' => $csv['subcategory_code']]);
        $supplier     = $this->site->getSupplierByCompanyName($csv['supplier']);
        $unit         = $this->site->getUnit(['code' => $csv['unit']]);

        if (!$category) continue;
        if (!$unit) continue;

        if ($warehouses = $this->site->getWarehouses(['active' => 1])) {
          $stockOpname = [];

          foreach ($warehouses as $warehouse) {
            $whName = strtolower(explode(' ', $warehouse->name)[0]);

            if (isset($csv[$whName . '_pic'])) {
              $pic = $this->site->getUserByName($csv[$whName . '_pic']);

              $stockOpname[] = [
                'user_id' => ($pic ? $pic->id : 0),
                'so_cycle' => ($pic ? $csv[$whName . '_cycle'] : 1),
                'warehouse_id' => $warehouse->id
              ];
            }
          }
        }

        $cost = filterDecimal($csv['cost']);

        $addProductsData[] = [
          'code'                => $csv['code'],
          'name'                => $csv['name'],
          'category_id'         => $category->id,
          'subcategory_id'      => ($subcategory ? $subcategory->id : NULL),
          'unit'                => $unit->id,
          'cost'                => $cost,
          'warehouses'          => trim($csv['warehouses']),
          'markon_price'        => getMarkonPrice($cost, $csv['markon']),
          'markon'              => filterDecimal($csv['markon']),
          'safety_stock_ratio'  => filterDecimal($csv['ss_ratio']),
          'min_order_qty'       => (!empty($csv['min_order_qty']) ? $csv['min_order_qty'] : 1),
          'iuse_type'           => trim(strtolower($csv['iuse_type'])),
          'active'              => ($csv['active'] == 1 ? 1 : 0),
          'type'                => 'standard',
          'supplier_id'         => ($supplier ? $supplier->id : NULL),
          'sn'                  => trim($csv['sn']),
          'priority'            => strtolower($csv['priority']),
          'purchased_at'        => trim($csv['purchased_at']),
          'purchase_source'     => strtolower($csv['purchase_source']),
          'stock_opname'        => $stockOpname
        ];
      }
    }

    if ($addProductsData) {
      $added = $this->site->addProducts($addProductsData);
    }
    if ($updateProductsData) {
      $updated = $this->site->updateProducts($updateProductsData);
    }

    $this->response(200, ['message' => "{$added} added, {$updated} updated, {$deleted} deleted."]);
  }

  /**
   * Sync selling products.
   */
  protected function import_sync_spd()
  {
    $sheets = getGoogleSheet('1VkkInHGgJdECp4Kma44eUrkzLaFc6ksLVAnBDklx6Vc', 'A5:CF');

    if (!$sheets) $this->response(404, ['message' => 'Something wrong with google sheets.']);

    $added   = 0;
    $deleted = 0;
    $updated = 0;
    $csvs    = [];

    /**
     * Column: warehouses
     * empty or (*) = all warehouses.
     * tembalang, pleburan = not all warehouses, tembalang and pleburan only.
     * -durian, -ungaran = all warehouses except durian and ungaran.
     * -gajah, weleri = all warehouses except gajah, weleri ignored.
     * fatmawati, -salatiga = not all warehouses, fatmawati only, salatiga ignored.
     */
    $headers = [
      'action', 'code', 'name', 'category_code', 'warehouses', 'unit', 'raw_code', 'raw_qty',
      'active', 'autocomplete', 'min_prod_time', 'prod_time_qty',
      'price_range_1', 'price_range_2', 'price_range_3', 'price_range_4', 'price_range_5', 'price_range_6',
      'zone_1_price_1', 'zone_1_price_2', 'zone_1_price_3', 'zone_1_price_4', 'zone_1_price_5', 'zone_1_price_6',
      'zone_2_price_1', 'zone_2_price_2', 'zone_2_price_3', 'zone_2_price_4', 'zone_2_price_5', 'zone_2_price_6',
      'zone_3_price_1', 'zone_3_price_2', 'zone_3_price_3', 'zone_3_price_4', 'zone_3_price_5', 'zone_3_price_6',
      'zone_4_price_1', 'zone_4_price_2', 'zone_4_price_3', 'zone_4_price_4', 'zone_4_price_5', 'zone_4_price_6',
      'zone_5_price_1', 'zone_5_price_2', 'zone_5_price_3', 'zone_5_price_4', 'zone_5_price_5', 'zone_5_price_6',
      'zone_6_price_1', 'zone_6_price_2', 'zone_6_price_3', 'zone_6_price_4', 'zone_6_price_5', 'zone_6_price_6',
      'priv_a_price_1', 'priv_a_price_2', 'priv_a_price_3', 'priv_a_price_4', 'priv_a_price_5', 'priv_a_price_6',
      'priv_b_price_1', 'priv_b_price_2', 'priv_b_price_3', 'priv_b_price_4', 'priv_b_price_5', 'priv_b_price_6',
      'priv_c_price_1', 'priv_c_price_2', 'priv_c_price_3', 'priv_c_price_4', 'priv_c_price_5', 'priv_c_price_6',
      'priv_d_price_1', 'priv_d_price_2', 'priv_d_price_3', 'priv_d_price_4', 'priv_d_price_5', 'priv_d_price_6',
      'priv_e_price_1', 'priv_e_price_2', 'priv_e_price_3', 'priv_e_price_4', 'priv_e_price_5', 'priv_e_price_6'
    ];

    foreach ($sheets as $sheet) {
      $csvs[] = arrayCombine($headers, $sheet);
    }

    $rawItems = [];
    $ItemData = [];
    $priceGroups = [];
    $useProduct = FALSE;

    foreach ($csvs as $csv) {
      if ($csv['action'] == 2064) {
        $product = Product::getRow(['code' => $csv['code']]);

        if ($product && Product::delete(['id' => $product->id])) {
          $deleted++;
        }

        continue;
      }

      // Begin columns initialization.
      $colCode          = trim($csv['code']);
      $colAction        = floatval($csv['action']);
      $colName          = trim($csv['name']);
      $colCategoryCode  = trim($csv['category_code']);
      $colUnit          = trim($csv['unit']);
      $colRawCode       = trim($csv['raw_code']);
      $colRawQty        = floatval(trim($csv['raw_qty']));
      $colMinProdTime   = floatval(trim($csv['min_prod_time']));
      $colProdTimeQty   = floatval(trim($csv['prod_time_qty']));
      $colPriceRanges   = [ // 6, 11, 51, 101, 201
        intval(trim($csv['price_range_2'])),
        intval(trim($csv['price_range_3'])),
        intval(trim($csv['price_range_4'])),
        intval(trim($csv['price_range_5'])),
        intval(trim($csv['price_range_6']))
      ];
      $colPriceGroups = [ // index: 0 - 5, group: 1 - 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
        [trim($csv['zone_1_price_1']), trim($csv['zone_1_price_2']), trim($csv['zone_1_price_3']), trim($csv['zone_1_price_4']), trim($csv['zone_1_price_5']), trim($csv['zone_1_price_6'])], // 1
        [trim($csv['zone_2_price_1']), trim($csv['zone_2_price_2']), trim($csv['zone_2_price_3']), trim($csv['zone_2_price_4']), trim($csv['zone_2_price_5']), trim($csv['zone_2_price_6'])], // 2
        [trim($csv['zone_3_price_1']), trim($csv['zone_3_price_2']), trim($csv['zone_3_price_3']), trim($csv['zone_3_price_4']), trim($csv['zone_3_price_5']), trim($csv['zone_3_price_6'])], // 3
        [trim($csv['zone_4_price_1']), trim($csv['zone_4_price_2']), trim($csv['zone_4_price_3']), trim($csv['zone_4_price_4']), trim($csv['zone_4_price_5']), trim($csv['zone_4_price_6'])], // 4
        [trim($csv['zone_5_price_1']), trim($csv['zone_5_price_2']), trim($csv['zone_5_price_3']), trim($csv['zone_5_price_4']), trim($csv['zone_5_price_5']), trim($csv['zone_5_price_6'])], // 5
        [trim($csv['zone_6_price_1']), trim($csv['zone_6_price_2']), trim($csv['zone_6_price_3']), trim($csv['zone_6_price_4']), trim($csv['zone_6_price_5']), trim($csv['zone_6_price_6'])], // 6
        [trim($csv['priv_a_price_1']), trim($csv['priv_a_price_2']), trim($csv['priv_a_price_3']), trim($csv['priv_a_price_4']), trim($csv['priv_a_price_5']), trim($csv['priv_a_price_6'])], // A
        [trim($csv['priv_b_price_1']), trim($csv['priv_b_price_2']), trim($csv['priv_b_price_3']), trim($csv['priv_b_price_4']), trim($csv['priv_b_price_5']), trim($csv['priv_b_price_6'])], // B
        [trim($csv['priv_c_price_1']), trim($csv['priv_c_price_2']), trim($csv['priv_c_price_3']), trim($csv['priv_c_price_4']), trim($csv['priv_c_price_5']), trim($csv['priv_c_price_6'])], // C
        [trim($csv['priv_d_price_1']), trim($csv['priv_d_price_2']), trim($csv['priv_d_price_3']), trim($csv['priv_d_price_4']), trim($csv['priv_d_price_5']), trim($csv['priv_d_price_6'])], // D
        [trim($csv['priv_e_price_1']), trim($csv['priv_e_price_2']), trim($csv['priv_e_price_3']), trim($csv['priv_e_price_4']), trim($csv['priv_e_price_5']), trim($csv['priv_e_price_6'])]  // E
      ];
      // End columns initialization.

      // Begin Parsing.
      $isSellingItem = (!empty($csv['code']) && !empty($csv['raw_code']) ? TRUE : FALSE);
      $isRAWItem     = (empty($csv['code']) && !empty($csv['raw_code'])  ? TRUE : FALSE);

      if ($isSellingItem) {
        if ($useProduct && !empty($ItemData)) {
          $product = Product::getRow(['code' => $ItemData['code']]);
          $ItemData['price'] = $this->site->getTotalComboPricesByRawItems($rawItems);

          if (!$product) { // Check if product not exist then ADD else UPDATE.
            $ItemData['combo_items']   = $rawItems;
            // $sell_item['min_prod_time'] = $col_min_prod_time;
            // $sell_item['prod_time_qty'] = $col_prod_time_qty;

            if ($this->site->addProducts([$ItemData])) { // Insert selling product.
              $groupId = 1; // Begin group from 1 to 6

              $product = Product::getRow(['code' => $ItemData['code']]); // Get new added product.

              foreach ($priceGroups as $priceGroup) {
                $pp = ProductPrice::getRow(['product_id' => $product->id, 'price_group_id' => $groupId]);

                $ppData = [
                  'product_id' => $product->id,
                  'price_group_id' => $groupId,
                  'price'  => $priceGroup[0],
                  'price2' => $priceGroup[1],
                  'price3' => $priceGroup[2],
                  'price4' => $priceGroup[3],
                  'price5' => $priceGroup[4],
                  'price6' => $priceGroup[5],
                ];

                if ($pp) {
                  ProductPrice::update($pp->id, $ppData);
                } else {
                  ProductPrice::add($ppData);
                }

                $groupId++;
              }

              $added++;
            }
          } else { // !product
            $ItemData['combo_items'] = $rawItems;
            $ItemData['product_id'] = $product->id;
            $this->site->updateProducts([$ItemData]);

            $groupId = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)

            foreach ($priceGroups as $priceGroup) {
              $pp = ProductPrice::getRow(['product_id' => $product->id, 'price_group_id' => $groupId]);

              $ppData = [
                'product_id' => $product->id,
                'price_group_id' => $groupId,
                'price'  => $priceGroup[0],
                'price2' => $priceGroup[1],
                'price3' => $priceGroup[2],
                'price4' => $priceGroup[3],
                'price5' => $priceGroup[4],
                'price6' => $priceGroup[5],
              ];

              if ($pp) {
                ProductPrice::update($pp->id, $ppData);
              } else {
                ProductPrice::add($ppData);
              }

              $groupId++;
            }

            $updated++;
          } // !product

          // Reset parsing.
          $priceGroups  = [];
          $rawItems     = [];
          $ItemData     = [];
        } else {
          // Reset parsing.
          $priceGroups  = [];
          $rawItems     = [];
          $ItemData     = [];
        }

        $useProduct = ($colAction == 1 ? TRUE : FALSE);
        if (!$useProduct) continue;

        $unit = $this->site->getUnit(['code' => rd_unit($colUnit)]);

        if (!$unit) {
          $this->response(404, ['message' => "Unit '{$colUnit}' cannot be found. Product '{$colCode}' skipped"]);
          continue;
        }

        $category = $this->site->getCategory(['code' => $colCategoryCode]);

        if (!$category) {
          $this->response(404, ['message' => "Category {$colCategoryCode} not found."]);
        }

        $ItemData = [
          'code'               => $colCode,
          'name'               => $colName,
          'category_id'        => $category->id,
          'subcategory_id'     => 0,
          'type'               => 'combo',
          'cost'               => 0,
          'price'              => 0,
          'active'             => filterDecimal($csv['active']),
          'warehouses'         => $csv['warehouses'],
          'unit'               => $unit->id,
          'price_ranges_value' => $colPriceRanges,
          'quantity'           => 0,
          'autocomplete'       => filterDecimal($csv['autocomplete']),
          'min_prod_time'      => $colMinProdTime,
          'prod_time_qty'      => $colProdTimeQty
        ];

        $product = $this->site->getProductByCode($colRawCode);

        $rawItems[] = [
          'item_code'  => $colRawCode,
          'quantity'   => $colRawQty,
          'unit_price' => ($product ? $product->price : 0)
        ];

        $priceGroups = $colPriceGroups;
      } else if ($isRAWItem && $useProduct) {
        // Parse RAW material to be included.
        if ($product = $this->site->getProductByCode($colRawCode)) { // Check if RAW item exists.
          $rawItems[] = [
            'item_code'  => $colRawCode, // rd_trim() function see app/helper/ridintek_helper.php
            'quantity'   => $colRawQty,
            'unit_price' => $product->price
          ];
        }
      }
    } // End foreach

    $this->response(200, ['message' => "{$added} added, {$updated} updated, {$deleted} deleted."]);
  }

  /**
   * Sync Service Items.
   */
  protected function import_sync_svc()
  {
    $sheets = getGoogleSheet('10UYqaF1eDeMc4qUDlK0UDbD5zr8Pv6aMDuKZ6RAQxb0', 'A4:CC');

    if (!$sheets) $this->response(404, ['message' => 'Something wrong with google sheets.']);

    $added              = 0;
    $deleted            = 0;
    $updated            = 0;
    $item               = [];
    $priceGroups       = [];

    $key  = 0;
    $keys = [
      'action', 'code', 'name', 'category_code', 'warehouses', 'active', 'autocomplete', 'min_prod_time', 'prod_time_qty',
      'price_range_1', 'price_range_2', 'price_range_3', 'price_range_4', 'price_range_5', 'price_range_6',
      'zone_1_price_1', 'zone_1_price_2', 'zone_1_price_3', 'zone_1_price_4', 'zone_1_price_5', 'zone_1_price_6',
      'zone_2_price_1', 'zone_2_price_2', 'zone_2_price_3', 'zone_2_price_4', 'zone_2_price_5', 'zone_2_price_6',
      'zone_3_price_1', 'zone_3_price_2', 'zone_3_price_3', 'zone_3_price_4', 'zone_3_price_5', 'zone_3_price_6',
      'zone_4_price_1', 'zone_4_price_2', 'zone_4_price_3', 'zone_4_price_4', 'zone_4_price_5', 'zone_4_price_6',
      'zone_5_price_1', 'zone_5_price_2', 'zone_5_price_3', 'zone_5_price_4', 'zone_5_price_5', 'zone_5_price_6',
      'zone_6_price_1', 'zone_6_price_2', 'zone_6_price_3', 'zone_6_price_4', 'zone_6_price_5', 'zone_6_price_6',
      'priv_a_price_1', 'priv_a_price_2', 'priv_a_price_3', 'priv_a_price_4', 'priv_a_price_5', 'priv_a_price_6',
      'priv_b_price_1', 'priv_b_price_2', 'priv_b_price_3', 'priv_b_price_4', 'priv_b_price_5', 'priv_b_price_6',
      'priv_c_price_1', 'priv_c_price_2', 'priv_c_price_3', 'priv_c_price_4', 'priv_c_price_5', 'priv_c_price_6',
      'priv_d_price_1', 'priv_d_price_2', 'priv_d_price_3', 'priv_d_price_4', 'priv_d_price_5', 'priv_d_price_6',
      'priv_e_price_1', 'priv_e_price_2', 'priv_e_price_3', 'priv_e_price_4', 'priv_e_price_5', 'priv_e_price_6'
    ];

    foreach ($sheets as $sheet) {
      $csvs[] = arrayCombine($keys, $sheet);
    }

    foreach ($csvs as $csv) {
      if ($csv['action'] == 2064) {
        if ($item = $this->site->getProduct(['code' => $csv['code']])) {
          if ($this->site->deleteProduct($item->id)) {
            $deleted++;
          }
        }
      }

      if ($csv['action'] != 1) continue;

      $item = [
        'code'               => trim($csv['code']),
        'name'               => trim($csv['name']),
        'unit'               => 0,
        'category_code'      => trim($csv['category_code']),
        'cost'               => 0,
        'price'              => filterDecimal($csv['zone_1_price_1']),
        'warehouses'         => trim($csv['warehouses']),
        'markon_price'       => 0,
        'markon'             => 0,
        'safety_stock'       => 0,
        'active'             => filterDecimal($csv['active']),
        'type'               => 'service',
        'supplier_id'        => '',
        'autocomplete'       => trim($csv['autocomplete']),
        'min_prod_time'      => filterDecimal($csv['min_prod_time']),
        'prod_time_qty'      => filterDecimal($csv['prod_time_qty']),
        'price_ranges_value' => [ // 6, 11, 51, 101, 201
          intval(trim($csv['price_range_2'])),
          intval(trim($csv['price_range_3'])),
          intval(trim($csv['price_range_4'])),
          intval(trim($csv['price_range_5'])),
          intval(trim($csv['price_range_6']))
        ]
      ];

      $priceGroups = [ // index: 0 - 5, group: 1 - 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
        [trim($csv['zone_1_price_1']), trim($csv['zone_1_price_2']), trim($csv['zone_1_price_3']), trim($csv['zone_1_price_4']), trim($csv['zone_1_price_5']), trim($csv['zone_1_price_6'])], // 1
        [trim($csv['zone_2_price_1']), trim($csv['zone_2_price_2']), trim($csv['zone_2_price_3']), trim($csv['zone_2_price_4']), trim($csv['zone_2_price_5']), trim($csv['zone_2_price_6'])], // 2
        [trim($csv['zone_3_price_1']), trim($csv['zone_3_price_2']), trim($csv['zone_3_price_3']), trim($csv['zone_3_price_4']), trim($csv['zone_3_price_5']), trim($csv['zone_3_price_6'])], // 3
        [trim($csv['zone_4_price_1']), trim($csv['zone_4_price_2']), trim($csv['zone_4_price_3']), trim($csv['zone_4_price_4']), trim($csv['zone_4_price_5']), trim($csv['zone_4_price_6'])], // 4
        [trim($csv['zone_5_price_1']), trim($csv['zone_5_price_2']), trim($csv['zone_5_price_3']), trim($csv['zone_5_price_4']), trim($csv['zone_5_price_5']), trim($csv['zone_5_price_6'])], // 5
        [trim($csv['zone_6_price_1']), trim($csv['zone_6_price_2']), trim($csv['zone_6_price_3']), trim($csv['zone_6_price_4']), trim($csv['zone_6_price_5']), trim($csv['zone_6_price_6'])], // 6
        [trim($csv['priv_a_price_1']), trim($csv['priv_a_price_2']), trim($csv['priv_a_price_3']), trim($csv['priv_a_price_4']), trim($csv['priv_a_price_5']), trim($csv['priv_a_price_6'])], // A
        [trim($csv['priv_b_price_1']), trim($csv['priv_b_price_2']), trim($csv['priv_b_price_3']), trim($csv['priv_b_price_4']), trim($csv['priv_b_price_5']), trim($csv['priv_b_price_6'])], // B
        [trim($csv['priv_c_price_1']), trim($csv['priv_c_price_2']), trim($csv['priv_c_price_3']), trim($csv['priv_c_price_4']), trim($csv['priv_c_price_5']), trim($csv['priv_c_price_6'])], // C
        [trim($csv['priv_d_price_1']), trim($csv['priv_d_price_2']), trim($csv['priv_d_price_3']), trim($csv['priv_d_price_4']), trim($csv['priv_d_price_5']), trim($csv['priv_d_price_6'])], // D
        [trim($csv['priv_e_price_1']), trim($csv['priv_e_price_2']), trim($csv['priv_e_price_3']), trim($csv['priv_e_price_4']), trim($csv['priv_e_price_5']), trim($csv['priv_e_price_6'])]  // E
      ];

      if ($category = $this->site->getCategoryByCode($item['category_code'])) { // Product Category must be present.
        $item['category_id'] = $category->id;

        if ($product = $this->site->getProductByCode($item['code'])) { // If product code exists then UPDATE.

          if ($product->type == 'service') { // If Product type Service present.
            $item['product_id'] = $product->id;
            if ($this->site->updateProducts([$item])) {
              $group_id = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)

              foreach ($priceGroups as $priceGroup) {
                $ppData = [
                  'product_id'      => $product->id,
                  'price_group_id'  => $group_id,
                  'price'           => $priceGroup[0],
                  'price2'          => $priceGroup[1],
                  'price3'          => $priceGroup[2],
                  'price4'          => $priceGroup[3],
                  'price5'          => $priceGroup[4],
                  'price6'          => $priceGroup[5],
                ];

                $this->site->addProductPrices($ppData);

                $group_id++;
              }

              $updated++;
            }
          }
        } else { // Add new service item.
          if ($this->site->addProducts($item)) { // csv_service
            $groupId = 1; // Begin group from 1 to 6 + (7: Privilge A, 8: Privilege B, 9: Privilege C)
            $newService = $this->site->getProductByCode($item['code']); // Get new added product.

            foreach ($priceGroups as $priceGroup) {
              $ppData = [
                'product_id'      => $newService->id,
                'price_group_id'  => $groupId,
                'price'           => $priceGroup[0],
                'price2'          => $priceGroup[1],
                'price3'          => $priceGroup[2],
                'price4'          => $priceGroup[3],
                'price5'          => $priceGroup[4],
                'price6'          => $priceGroup[5],
              ];

              $this->site->addProductPrices($ppData);

              $groupId++;
            }

            $added++;
          }
        }
      }
    } // foreach

    $this->response(200, ['message' => "{$added} added, {$updated} updated, {$deleted} deleted."]);
  }

  public function index()
  {
    $this->sma->checkPermissions('index', FALSE, 'products');

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

    $warehouse_id = getGET('warehouse');

    // $this->isAdmin = FALSE;
    // $this->data['isAdmin'] = FALSE;

    if ($this->isAdmin || !XSession::get('warehouse_id')) {
      $this->data['warehouses']   = $this->site->getAllWarehouses();
      $this->data['warehouse_id'] = $warehouse_id;
      $this->data['warehouse']    = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : null;
    } else {
      $this->data['warehouses']   = null;
      $this->data['warehouse_id'] = XSession::get('warehouse_id');
      $this->data['warehouse']    = XSession::get('warehouse_id') ? $this->site->getWarehouseByID(XSession::get('warehouse_id')) : null;
    }

    $this->data['supplier'] = (getGET('supplier') ? $this->site->getSupplierByID(getGET('supplier')) : NULL);
    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('products')]];
    $meta = ['page_title' => lang('products'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/index', $this->data);
  }

  /**
   * Get product informations. Used by Stock Opname
   */
  public function info()
  {
    $info         = [];
    $id           = getGET('id');
    $ids          = getGET('ids'); // ids must be string with comma delimiter. "1,4,25,40,98,..."
    $code         = getGET('code');
    $name         = getGET('name');
    $warehouse_id = getGET('warehouse');

    if ($id) {
      $info = $this->site->getProductByID($id);
    } else if ($ids) {
      $idss = explode(',', $ids);
      foreach ($idss as $id1) {
        $info[] = $this->site->getProductByID(trim($id1));
      }
    } else if ($code) {
      $info = $this->site->getProductByCode($code);
    } else if ($name) {
      $info = $this->site->getProductNames($name);
    }

    if (!empty($info) && !empty($warehouse_id)) { // Get warehouse quantity.
      for ($a = 0; $a < count($info); $a++) {
        $whp = $this->site->getWarehouseProduct($info[$a]->id, $warehouse_id);

        if ($whp) {
          $info[$a]->quantity = $whp->quantity;
        }
      }
    }

    sendJSON($info);
  }

  /**
   * INTERNAL USES
   */
  public function internal_use()
  {
    checkPermission('products-internal_use_view');

    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $meta = [
      'page_title' => lang('internal_use'),
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => '#', 'page' => lang('internal_use')]
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/internal_use/index', $this->data);
  }

  private function internal_use_add()
  {
    if (!getPermission('products-internal_use_add')) {
      $this->response(401, ['message' => 'Anda tidak memiliki akses untuk menambahkan.']);
    }

    if ($this->requestMethod == 'POST') {
      $createdAt       = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $createdBy       = getPOST('created_by');
      $warehouseIdFrom = getPOST('from_warehouse');
      $warehouseIdTo   = getPOST('to_warehouse');
      $note            = getPOST('note');
      $products        = getPOST('product');

      $items = [];

      if (!$products) $this->response(400, ['message' => 'Item tidak boleh kosong.']);

      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $items[] = [
          'product_id'   => $products['id'][$a],
          'markon_price' => filterDecimal($products['markon_price'][$a]),
          'quantity'     => filterDecimal($products['quantity'][$a]),
          'spec'         => htmlEncode($products['spec'][$a])
        ];
      }

      $ptData = [
        'created_at'        => $createdAt,
        'created_by'        => $createdBy,
        'warehouse_id_from' => $warehouseIdFrom,
        'warehouse_id_to'   => $warehouseIdTo,
        'note'              => $note
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $ptData['attachment'] = $uploader->storeRandom();
      }

      if (ProductTransfer::add($ptData, $items)) {
        $this->response(201, ['message' => 'Product Transfer berhasil dibuat.']);
      }
      $this->response(400, ['message' => 'Gagal membuat Product Transfer.']);
    }

    $this->load->view($this->theme . 'products/transfer/add', $this->data);
  }

  private function internal_use_delete()
  {
  }

  private function internal_use_edit($iuseId = NULL)
  {
  }

  private function internal_use_getInternalUses()
  {
  }

  private function internal_use_index()
  {
    echo "OKE";
  }

  /* --------------------------------------------------------------------------------------------- */

  public function modal_view($product_id = null)
  {
    $this->sma->checkPermissions('index', true, 'products', true);

    $startDate = getGET('start_date');
    $endDate   = getGET('end_date');

    $product = Product::getRow(['id' => $product_id]);

    if (!$product_id || !$product) {
      $this->session->set_flashdata('error', lang('prduct_not_found'));
      $this->sma->md();
    }

    $this->data['barcode'] = "<img src='" . admin_url('products/gen_barcode/' . $product->code . '/' . $product->barcode_symbology . '/40/0') . "' alt='" . $product->code . "' class='pull-left' />";

    if ($product->type == 'combo') {
      $this->data['combo_items'] = $this->site->getProductComboItems($product_id);
    }

    // Sync products warehouses.
    // $this->site->syncProductQty($product_id);
    if ($warehouseId = XSession::has('warehouse_id')) {
      Product::sync($product->id, $warehouseId);
    } else {
      $warehouses = Warehouse::get(['active' => 1]);

      foreach ($warehouses as $warehouse) {
        Product::sync($product->id, $warehouse->id);
      }
    }

    $this->data['product']       = $product;
    $this->data['productJS']     = getJSON($product->json_data);
    $this->data['unit']          = $this->site->getUnitByID($product->unit);
    $this->data['sale_unit']     = $this->site->getUnitByID($product->sale_unit);
    $this->data['purchase_unit'] = $this->site->getUnitByID($product->purchase_unit);
    $this->data['images']        = NULL; //$this->products_model->getProductPhotos($product_id);
    $this->data['category']      = $this->site->getProductCategoryByID($product->category_id);
    $this->data['subcategory']   = $product->subcategory_id ? $this->site->getProductCategoryByID($product->subcategory_id) : null;
    $this->data['warehouses']    = $this->site->getAllWarehousesWithPQ($product_id, ['start_date' => $startDate, 'end_date' => $endDate]); // PQ = Product Quantity

    $this->load->view($this->theme . 'products/modal_view', $this->data);
  }

  public function mutation()
  {
    checkPermission('products-mutation_view');

    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $meta = [
      'page_title' => lang('products_mutation'),
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => '#', 'page' => lang('mutation')]
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/mutation/index', $this->data);
  }

  protected function mutation_add()
  {
    checkPermission('products-mutation_add');

    if ($this->requestMethod == 'POST') {
      $createdAt       = dtPHP(getPOST('created_at'));
      $createdBy       = getPOST('created_by');
      $fromWarehouseId = getPOST('from_warehouse');
      $toWarehouseId   = getPOST('to_warehouse');
      $note            = getPOST('note');
      $products        = getPOST('product');

      $items = [];
      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $items[] = [
          'product_id' => $products['id'][$a],
          'quantity'   => $products['quantity'][$a],
          'status'     => 'pending'
        ];
      }

      $pmData = [
        'created_at'        => $createdAt,
        'created_by'        => $createdBy,
        'from_warehouse_id' => $fromWarehouseId,
        'to_warehouse_id'   => $toWarehouseId,
        'status'            => 'pending',
        'note'              => $note
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $pmData['attachment'] = $uploader->storeRandom();
      }

      if ($this->site->addProductMutation($pmData, $items)) {
        $this->response(201, ['message' => 'Product Mutation berhasil dibuat.']);
      }
      $this->response(400, ['message' => 'Gagal membuat Product Mutation.']);
    }

    $this->load->view($this->theme . 'products/mutation/add', $this->data);
  }

  protected function mutation_delete($pmId = NULL)
  {
    if (!getPermission('products-mutation_delete')) {
      $this->response(401, ['message' => 'Anda tidak punya akses untuk menghapus.']);
    }

    if ($vals = getPOST('val')) {
      $deleted = 0;

      foreach ($vals as $pmId) {
        if ($this->site->deleteProductMutations(['id' => $pmId])) {
          $deleted++;
        }
      }

      if ($deleted) $this->response(200, ['message' => "{$deleted} Product Mutation berhasil dihapus."]);
      $this->response(400, ['message' => 'Gagal menghapus Product Mutation.']);
    }

    if ($pmId) {
      if ($deleted = $this->site->deleteProductMutations(['id' => $pmId])) {
        $this->response(200, ['message' => "{$deleted} Product Mutation berhasil dihapus."]);
      }
      $this->response(400, ['message' => 'Gagal menghapus Product Mutation.']);
    }

    $this->response(400, ['message' => 'Tidak ada Product Mutation yang terpilih.']);
  }

  protected function mutation_edit($pmId = NULL)
  {
    $pm = $this->site->getProductMutation(['id' => $pmId]);
    $mode = (getGET('mode') ?? getPOST('mode') ?? 'edit');

    if ($mode == 'edit') {
      checkPermission('products-mutation_edit');
    } else if ($mode == 'status') {
      checkPermission('products-mutation_status');
    }

    if ($this->requestMethod == 'POST') {
      $createdAt       = dtPHP($this->isAdmin ? getPOST('created_at') : $pm->created_at);
      $createdBy       = getPOST('created_by');
      $fromWarehouseId = getPOST('from_warehouse');
      $toWarehouseId   = getPOST('to_warehouse');
      $note            = getPOST('note');
      $products        = getPOST('product');
      $status          = getPOST('status');

      if (empty($status)) {
        $this->response(400, ['message' => 'Status tidak boleh kosong.']);
      }

      $items = [];
      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $receivedQty = (floatval($products['received_qty'][$a]) + floatval($products['quantity'][$a]));

        $items[] = [
          'product_id'   => floatval($products['id'][$a]),
          'quantity'     => floatval($products['total_qty'][$a]), // Total qty
          'received_qty' => $receivedQty,
          'status'       => $status
        ];
      }

      $pmData = [
        'created_at'        => $createdAt,
        'created_by'        => $createdBy,
        'from_warehouse_id' => $fromWarehouseId,
        'to_warehouse_id'   => $toWarehouseId,
        'status'            => $status,
        'note'              => $note
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $pmData['attachment'] = $uploader->storeRandom();
      }

      if ($this->site->updateProductMutation($pmId, $pmData, $items)) {
        $this->response(200, ['message' => 'Product Mutation has been updated.']);
      }
      $this->response(400, ['message' => 'Failed to update Product Mutation.']);
    }

    $pmitems = $this->site->getProductMutationItems(['pm_id' => $pmId]);
    $items = [];

    foreach ($pmitems as $pmitem) {
      $product = $this->site->getProductById($pmitem->product_id);

      $items[] = [
        'id'           => $pmitem->product_id,
        'code'         => $pmitem->product_code,
        'name'         => $product->name,
        'quantity'     => $pmitem->quantity,
        'received_qty' => $pmitem->received_qty,
      ];
    }

    $this->data['mode'] = $mode;
    $this->data['pm'] = $pm;
    $this->data['pmitems'] = $items;

    $this->load->view($this->theme . 'products/mutation/edit', $this->data);
  }

  protected function mutation_getMutations()
  {
    $startDate  = getGET('start_date');
    $endDate    = getGET('end_date');
    $warehouses = getGET('warehouse');

    $this->load->library('datatable');

    $this->datatable
      ->select("product_mutation.id AS id, product_mutation.id AS pid, product_mutation.attachment,
        product_mutation.items, product_mutation.status AS status,
        from_warehouse.name AS from_wh_name, to_warehouse.name AS to_wh_name,
        product_mutation.created_at, creator.fullname AS creator_name,
        product_mutation.updated_at, updater.fullname AS updater_name")
      ->from('product_mutation')
      ->join('warehouses from_warehouse', 'from_warehouse.id = product_mutation.from_warehouse_id', 'left')
      ->join('warehouses to_warehouse', 'to_warehouse.id = product_mutation.to_warehouse_id', 'left')
      ->join('users creator', 'creator.id = product_mutation.created_by', 'left')
      ->join('users updater', 'updater.id = product_mutation.updated_by', 'left');

    if ($startDate) {
      $this->datatable->where("created_at >= '{$startDate} 00:00:00'");
    }

    if ($endDate) {
      $this->datatable->where("created_at <= '{$endDate} 23:59:59'");
    }

    if ($warehouses) {
      $this->datatable->where_in('product_mutation.from_warehouse_id', $warehouses);
    }

    $this->datatable->editColumn('pid', function ($data) {
      return "
        <div class=\"text-center\">
          <a href=\"{$this->theme}products/mutation/delete/{$data['id']}\"
            class=\"tip \"
            data-action=\"confirm\" style=\"color:red;\" title=\"Delete Product Mutation\">
              <i class=\"fad fa-fw fa-trash\"></i>
          </a>
          <a href=\"{$this->theme}products/mutation/edit/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg\" title=\"Edit Product Mutation\">
              <i class=\"fad fa-fw fa-edit\"></i>
          </a>
          <a href=\"{$this->theme}products/mutation/view/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg\"
            title=\"View Details\">
              <i class=\"fad fa-fw fa-chart-bar\"></i>
          </a>
        </div>";
    })
      ->editColumn('status', function ($data) {
        switch ($data['status']) {
          case 'pending':
            $type = 'warning';
            break;
          case 'sent':
            $type = 'success';
            break;
          case 'received_partial':
            $type = 'info';
            break;
          case 'received':
            $type = 'primary';
            break;
          default:
            $type = 'warning';
        }

        $status = ucwords(str_replace('_', ' ', $data['status']));

        return "
        <div class=\"text-center\">
          <a href=\"" . admin_url('products/mutation/edit/' . $data['id'] . '?mode=status') . "\"
            class=\"label label-{$type} status\"
            data-toggle=\"modal\" data-target=\"#myModal\" data-modal-class=\"modal-lg\">{$status}
          </a>
        </div>
        ";
      });

    $this->datatable->generate();
  }

  public function mutation_view($pmId = NULL)
  {
    $pmitems = $this->site->getProductMutationItems(['pm_id' => $pmId]);
    $items = [];

    foreach ($pmitems as $pmitem) {
      $product = $this->site->getProductById($pmitem->product_id);

      $items[] = (object)[
        'product_id'   => $pmitem->product_id,
        'product_code' => $pmitem->product_code,
        'product_name' => $product->name,
        'quantity'     => $pmitem->quantity,
        'received_qty' => $pmitem->received_qty,
        'status'       => $pmitem->status
      ];
    }

    $this->data['pm']      = $this->site->getProductMutation(['id' => $pmId]);
    $this->data['pmitems'] = $items;

    $this->load->view($this->theme . 'products/mutation/view', $this->data);
  }

  public function product_actions($wh = null)
  {
    if (!$this->Owner && !$this->Admin && !$this->GP['bulk_actions']) {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 1, 'msg' => lang('access_denied')]);
      } else {
        $this->session->set_flashdata('error', lang('access_denied'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (empty($_POST['val'])) { // If empty val then all products synced.
        if (getPOST('form_action') == 'sync_quantity') {
          $products = $this->site->getProducts();

          if ($products) {
            foreach ($products as $product) {
              if ($product->type == 'combo') continue; // Ignore combo product.

              if ($product->type == 'standard') {
                $avgCost = getProductAvgCost($product->id);
                Product::update((int)$product->id, ['avg_cost' => $avgCost]);
                unset($avgCost);
              }

              $this->site->syncQuantity(['product_id' => $product->id]);
            }

            if ($this->input->is_ajax_request()) {
              sendJSON(['error' => 0, 'msg' => $this->lang->line('products_quantity_sync')]);
            } else {
              $this->session->set_flashdata('message', $this->lang->line('products_quantity_sync'));
              redirect($_SERVER['HTTP_REFERER']);
            }
          }
        }
      } else if (!empty($_POST['val'])) { // If not empty val then val products synced.
        if (getPOST('form_action') == 'sync_quantity') { // Sync quantity.
          foreach ($_POST['val'] as $id) {
            $product = Product::getRow(['id' => $id]);

            if ($product->type == 'combo') continue; // Ignore combo product.

            if ($product->type == 'standard') {
              $avgCost = getProductAvgCost($product->id);
              Product::update((int)$product->id, ['avg_cost' => $avgCost]);
              unset($avgCost);
            }

            $this->site->syncQuantity(['product_id' => $id]);
          }
          if ($this->input->is_ajax_request()) {
            sendJSON(['error' => 0, 'msg' => $this->lang->line('products_quantity_sync')]);
          } else {
            $this->session->set_flashdata('message', $this->lang->line('products_quantity_sync'));
            redirect($_SERVER['HTTP_REFERER']);
          }
        } elseif (getPOST('form_action') == 'activate') {
          $msg = '';

          foreach ($_POST['val'] as $id) {
            $product = Product::getRow(['id' => $id]);

            if (Product::update((int)$product->id, ['active' => 1])) {
              $msg .= "Item '{$product->code}' has been activated.<br>";
            } else {
              $msg .= "<span class=\"text-danger bold\">Failed</span> to activate '{$product->code}'.";
            }
          }

          sendJSON(['error' => 0, 'msg' => $msg]);
        } elseif (getPOST('form_action') == 'deactivate') {
          $msg = '';

          foreach ($_POST['val'] as $id) {
            $product = $this->site->getProductByID($id);

            if (Product::update((int)$product->id, ['active' => 0])) {
              $msg .= "Item '{$product->code}' has been deactivated.<br>";
            } else {
              $msg .= "<span class=\"text-danger bold\">Failed</span> to deactivate '{$product->code}'.";
            }
          }

          sendJSON(['error' => 0, 'msg' => $msg]);
        } elseif (getPOST('form_action') == 'delete') {
          $this->sma->checkPermissions('delete');
          foreach ($_POST['val'] as $id) {
            Product::delete(['id' => $id]);
          }
          if ($this->input->is_ajax_request()) {
            sendJSON(['error' => 0, 'msg' => $this->lang->line('products_deleted')]);
          } else {
            $this->session->set_flashdata('message', $this->lang->line('products_deleted'));
            redirect($_SERVER['HTTP_REFERER']);
          }
        } elseif (getPOST('form_action') == 'export_excel') {
          $sheet = $this->ridintek->spreadsheet();
          $sheet->setTitle('Products');
          $sheet->setTabColor('#008000');
          $sheet->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Code')
            ->setCellValue('C1', 'Name')
            ->setCellValue('D1', 'Type')
            ->setCellValue('E1', 'Category Code')
            ->setCellValue('F1', 'Category Name')
            ->setCellValue('G1', 'Cost')
            ->setCellValue('H1', 'Quantity')
            ->setCellValue('I1', 'Quantity Alert')
            ->setCellValue('J1', 'Unit Code')
            ->setCellValue('K1', 'Unit Name');
          $sheet->setBold('A1:K1');
          $sheet->setFillColor('A1:K1', 'FFFF00');
          $sheet->setAlignment('A1:K1', 'center');

          $row = 2;
          foreach ($_POST['val'] as $product_id) {
            $product   = $this->site->getProductByID($product_id);
            $pcategory = $this->site->getProductCategoryByID($product->category_id);
            $punit     = $this->site->getProductUnitByID($product->unit);
            $sheet->setCellValue('A' . $row, $product_id)
              ->setCellValue('B' . $row, $product->code)
              ->setCellValue('C' . $row, $product->name)
              ->setCellValue('D' . $row, ucfirst($product->type))
              ->setCellValue('E' . $row, $pcategory->code)
              ->setCellValue('F' . $row, $pcategory->name)
              ->setCellValue('G' . $row, $product->cost)
              ->setCellValue('H' . $row, $product->quantity)
              ->setCellValue('I' . $row, $product->safety_stock)
              ->setCellValue('J' . $row, $punit->code)
              ->setCellValue('K' . $row, $punit->name);
            $row++;
          }

          $sheet->setColumnAutoWidth('A')
            ->setColumnAutoWidth('B')
            ->setColumnAutoWidth('C')
            ->setColumnAutoWidth('D')
            ->setColumnAutoWidth('E')
            ->setColumnAutoWidth('F')
            ->setColumnAutoWidth('G')
            ->setColumnAutoWidth('H')
            ->setColumnAutoWidth('I')
            ->setColumnAutoWidth('J')
            ->setColumnAutoWidth('K');
          $sheet->export('Products-' . date('Y_m_d_H_i_s'));
        }
      } else {
        if ($this->input->is_ajax_request()) {
          sendJSON(['error' => 1, 'msg' => $this->lang->line('no_product_selected')]);
        } else {
          $this->session->set_flashdata('error', $this->lang->line('no_product_selected'));
          redirect($_SERVER['HTTP_REFERER']);
        }
      }
    } else {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 1, 'msg' => validation_errors()]);
      } else {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/products');
      }
    }
  }

  public function qa_suggestions()
  {
    $term = getGET('term', true);
    $warehouse_id = getGET('warehouse_id');

    if (strlen($term) < 1 || !$term) {
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
    }

    $analyzed  = $this->sma->analyze_term($term);
    $sr        = $analyzed['term'];

    // $rows = $this->products_model->getQASuggestions($sr, 10);
    $rows = $this->site->getProductNames($sr, 10);

    if ($rows) {
      foreach ($rows as $row) {
        $whp = $this->site->getWarehouseProduct($row->id, $warehouse_id);

        $row->qty        = 1;
        $row->source_qty = $whp->quantity;

        $pr[]        = [
          'id'      => generateUUID(),
          'item_id' => $row->id,
          'label'   => '(' . $row->code . ') ' . $row->name,
          'row'     => $row
        ];
      }

      sendJSON($pr);
    } else {
      sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
    }
  }

  /* ----------------------------------------------------------------------------- */

  public function quantity_adjustments($warehouse_id = null)
  {
    $this->sma->checkPermissions('adjustments');

    if ($this->Owner || $this->Admin || !XSession::get('warehouse_id')) {
      $this->data['warehouses'] = $this->site->getAllWarehouses();
      $this->data['warehouse']  = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : null;
    } else {
      $this->data['warehouses'] = null;
      $this->data['warehouse']  = XSession::get('warehouse_id') ? $this->site->getWarehouseByID(XSession::get('warehouse_id')) : null;
    }

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('quantity_adjustments')]];
    $meta = ['page_title' => lang('quantity_adjustments'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/quantity_adjustments', $this->data);
  }

  // public function set_rack($product_id = null, $warehouse_id = null)
  // {
  //   $this->sma->checkPermissions('edit', true);

  //   $this->form_validation->set_rules('rack', lang('rack_location'), 'trim|required');

  //   if ($this->form_validation->run() == true) {
  //     $data = [
  //       'rack'    => getPOST('rack'),
  //       'product_id'   => $product_id,
  //       'warehouse_id' => $warehouse_id,
  //     ];
  //   } elseif (getPOST('set_rack')) {
  //     $this->session->set_flashdata('error', validation_errors());
  //     admin_redirect('products/' . $warehouse_id);
  //   }

  //   if ($this->form_validation->run() == true && $this->products_model->setRack($data)) {
  //     $this->session->set_flashdata('message', lang('rack_set'));
  //     admin_redirect('products/' . $warehouse_id);
  //   } else {
  //     $this->data['error']        = validation_errors() ? validation_errors() : $this->session->flashdata('error');
  //     $this->data['warehouse_id'] = $warehouse_id;
  //     $this->data['product']      = $this->site->getProductByID($product_id);
  //     $wh_pr                      = $this->products_model->getProductQuantity($product_id, $warehouse_id);
  //     $this->data['rack']         = $wh_pr['rack'];
  //     $this->load->view($this->theme . 'products/set_rack', $this->data);
  //   }
  // }

  public function stock_opname()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function stock_opname_add() // so_add
  {
    $this->form_validation->set_rules('date', 'Date', 'required');
    $this->form_validation->set_rules('warehouse', 'Warehouse', 'required');

    if ($this->form_validation->run()) {
      $cycle        = (getPOST('cycle') ?? 1);
      $date         = dtPHP(getPOST('date'));
      $item_ids     = getPOST('item_id');
      $real_qtys    = getPOST('real_qty');
      $first_qtys   = getPOST('first_qty');
      $reject_qtys  = getPOST('reject_qty');
      $note         = htmlEncode(getPOST('note'));
      $warehouse_id = getPOST('warehouse');

      $pic_id = getPOST('pic');

      if (empty($item_ids)) {
        $this->session->set_flashdata('error', 'No items.');
        admin_redirect('products/stock_opname/add');
      }

      $item_count = count($item_ids);
      $items_data = [];

      // Looping SO items.
      for ($a = 0; $a < $item_count; $a++) {
        $showQty = ($this->isAdmin || getPermission('products-so_quantity') ? TRUE : FALSE);
        $item_id    = $item_ids[$a];
        $first_qty  = $first_qtys[$a];
        $reject_qty = $reject_qtys[$a];

        $warehouseProduct = $this->site->getWarehouseProduct($item_id, $warehouse_id);

        if (!$warehouseProduct) {
          $this->session->set_flashdata('error', "Product ID [{$item_id}] not found.");
          admin_redirect('products/stock_opname/add');
        }

        $items_data[] = [
          'product_id' => $item_id,
          'quantity'   => $warehouseProduct->quantity,  // Real qty.
          'first_qty'  => (!empty($first_qty) ? $first_qty : 0), // If quantity not set then make as zero.
          'reject_qty' => (!empty($reject_qty) ? $reject_qty : 0)
        ];
      }

      $so_data = [
        'date'         => $date,
        'cycle'        => $cycle,
        'note'         => $note,
        'warehouse_id' => $warehouse_id,
        'created_by'   => $pic_id
      ];

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $so_data['attachment'] = $uploader->storeRandom();
      } else {
        $this->session->set_flashdata('error', 'Attachment harus dilampirkan.');
        admin_redirect('products/stock_opname/add');
      }

      if ($this->site->addStockOpname($so_data, $items_data)) {
        setcookie('soremove', 1, 0, '/');
        $this->session->set_flashdata('message', 'Stock opname berhasil ditambahkan.');
        admin_redirect('products/stock_opname');
      }
      $this->session->set_flashdata('error', 'Stock opname gagal ditambahkan.');
      admin_redirect('products/stock_opname');
    } else {
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $this->session->set_flashdata('error', validation_errors());
      }
    }

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => admin_url('products/stock_opname'), 'page' => lang('stock_opname')],
      ['link' => '#', 'page' => lang('add_stock_Opname')]
    ];
    $meta = ['page_title' => lang('add_stock_opname'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/stock_opname/add', $this->data);
  }

  private function stock_opname_confirm($opname_id)
  {
    $this->so_mode = 'confirm';
    $this->stock_opname_edit($opname_id);
  }

  private function stock_opname_delete()
  {
    $id = getPOST('id');
    if ($opname = $this->site->getStockOpnameByID($id)) {
      if ($this->site->deleteStockOpname($opname->id)) {
        sendJSON(['error' => 0, 'msg' => 'Stock opname has been delete successfully.']);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to delete stock opname.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Stock opname is not found.']);
  }

  private function stock_opname_edit($opname_id) // so_edit
  {
    $items = [];
    $this->data['mode'] = $this->so_mode;
    $granted = (XSession::get('group_id') == 6 ? TRUE : FALSE);

    $this->form_validation->set_rules('warehouse', 'Warehouse', 'required');

    $opname = $this->site->getStockOpnameByID($opname_id);

    if ($opname->status == 'verified' && $this->so_mode == 'confirm') {
      $this->session->set_flashdata('error', 'Stock Opname has been verified.');
      admin_redirect("products/stock_opname");
    }

    if ($opname->status == 'confirmed' && (!$this->Owner && !$this->Admin && !$granted)) {
      $this->session->set_flashdata('error', lang('access_denied'));
      admin_redirect("products/stock_opname");
    }

    if ($this->form_validation->run()) {
      $product_ids = getPOST('product_id');
      $prices      = getPOST('price');
      $quantities  = getPOST('quantity');
      $first_qtys  = getPOST('first_qty');
      $reject_qtys = getPOST('reject_qty');
      $last_qtys   = getPOST('last_qty');

      $so_data = [
        'reference'    => getPOST('reference'),
        'note'         => htmlEncode(getPOST('note')),
        'status'       => getPOST('status'),
        'warehouse_id' => getPOST('warehouse'),
        'updated_by'   => XSession::get('user_id'),
        'updated_at'   => date('Y-m-d H:i:s')
      ];

      $item_count = (is_array($product_ids) ? count($product_ids) : NULL); // Get total items from products array.

      if ($item_count) {
        for ($a = 0; $a < $item_count; $a++) {
          $product_id = $product_ids[$a];
          $price      = $prices[$a];
          $quantity   = $quantities[$a]; // Real qty on PrintERP.
          $first_qty  = $first_qtys[$a]; // First stock opname.
          $reject_qty = $reject_qtys[$a]; // Reject stock opname.
          $last_qty   = $last_qtys[$a]; // Second stock opname.

          $items[] = [
            'product_id' => $product_id,
            'price'      => $price,
            'quantity'   => $quantity,
            'first_qty'  => $first_qty,
            'reject_qty' => $reject_qty,
            'last_qty'   => $last_qty
          ];
        }
      } else {
        $this->session->set_flashdata('error', 'Cancelled. Why no items present?');
        admin_redirect("products/stock_opname/{$this->so_mode}/{$opname_id}");
      }

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $so_data['attachment'] = $uploader->storeRandom();
      } else if ($opname->status == 'checked' && $this->so_mode == 'confirm') { // If confirm checked, must include attachment.
        $this->session->set_flashdata('error', lang('attachment_required'));
        admin_redirect("products/stock_opname/{$this->so_mode}/{$opname_id}");
      }

      if ($this->site->updateStockOpname($opname_id, $so_data, $items)) {
        setcookie('soremove', 1, 0, '/');
        $this->session->set_flashdata('message', "Stock opname has been {$this->so_mode}ed successfully.");
        admin_redirect('products/stock_opname');
      }

      $this->session->set_flashdata('error', "Stock opname failed to {$this->so_mode}.");
      admin_redirect('products/stock_opname');
    } else {
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $this->session->set_flashdata('error', validation_errors());
      }
    }

    $opname = $this->site->getStockOpnameByID($opname_id);
    $this->data['opname'] = $opname;

    $opname_items = $this->site->getStockOpnameItems($opname_id);

    if ($opname_items) {
      foreach ($opname_items as $item) {
        if ($this->so_mode == 'confirm' && $item->first_qty >= $item->quantity) continue; // Minus only. Ignore plus or equal.
        $items[] = $item;
      }
    }
    $this->data['items'] = $items;

    $title = ($this->so_mode == 'confirm' ? 'Confirm' : 'Edit') . ' Stock Opname';
    $this->data['title'] = $title;

    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => admin_url('products/stock_opname'), 'page' => lang('stock_opname')],
      ['link' => '#', 'page' => $title]
    ];
    $meta = ['page_title' => $title, 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/stock_opname/edit', $this->data);
  }

  private function stock_opname_getStockOpname()
  { // Summary
    $group_by = getGET('group_by');
    $warehouses = getGET('warehouses');
    $start_date = getGET('start_date');
    $end_date   = getGET('end_date');
    $xls = (getGET('xls') == 1 ? TRUE : FALSE);

    if ($xls) { // EXPORT EXCEL
      $this->db->select("stock_opnames.id AS id, stock_opnames.adjustment_plus_id AS adjustment_plus_id,
      stock_opnames.adjustment_min_id AS adjustment_min_id, stock_opnames.date AS date,
      stock_opnames.reference AS reference,
      adj_plus.reference AS adjustment_plus_ref, adj_min.reference AS adjustment_min_ref,
      creator.fullname AS created_by, warehouses.name AS warehouse_name,
      stock_opnames.total_lost, stock_opnames.total_plus, stock_opnames.total_edited,
      stock_opnames.status AS status, stock_opnames.note AS note, stock_opnames.attachment AS attachment")
        ->from('stock_opnames')
        ->join('adjustments AS adj_plus', 'adj_plus.id = stock_opnames.adjustment_plus_id', 'left')
        ->join('adjustments AS adj_min', 'adj_min.id = stock_opnames.adjustment_min_id', 'left')
        ->join('warehouses', 'warehouses.id = stock_opnames.warehouse_id', 'left')
        ->join('(SELECT id, fullname FROM users) AS creator', 'creator.id = stock_opnames.created_by', 'left');

      if ($warehouse_id = XSession::get('warehouse_id')) {
        $this->db->where('stock_opnames.warehouse_id', $warehouse_id);
      }

      if ($warehouses) {
        $this->db->group_start();
        foreach ($warehouses as $wh) {
          $this->db->or_where('stock_opnames.warehouse_id', $wh);
        }
        $this->db->group_end();
      }

      if ($start_date) {
        $end_date = ($end_date ?? date('Y-m-d'));
        $this->db->where("stock_opnames.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      }

      if ($group_by == 'opname') {
        $this->db->group_by('id');
      } else if ($group_by == 'pic') {
        $this->db->group_by('created_by');
      } else if ($group_by == 'warehouse') {
        $this->db->group_by('stock_opnames.warehouse_id');
      }

      $this->db->order_by('id', 'DESC');

      $q = $this->db->get();
      $opnames = [];

      if ($q->num_rows() > 0) {
        foreach ($q->result() as $row) {
          $opnames[] = $row;
        }
      }

      $excel = $this->ridintek->spreadsheet();
      $excel->setCellValue('A1', 'No');
      $excel->setCellValue('B1', 'Date');
      $excel->setCellValue('C1', 'Reference');
      $excel->setCellValue('D1', 'Adjustment Plus Ref');
      $excel->setCellValue('E1', 'Adjustment Min Ref');
      $excel->setCellValue('F1', 'PIC');
      $excel->setCellValue('G1', 'Warehouse');
      $excel->setCellValue('H1', 'Total Lost');
      $excel->setCellValue('I1', 'Total Plus');
      $excel->setCellValue('J1', 'Total Edited');
      $excel->setCellValue('K1', 'Status');
      $excel->setCellValue('L1', 'Note');
      $excel->setCellValue('M1', 'Attachment');

      $excel->setBold('A1:M1');
      $excel->setFillColor('A1:M1', 'FFFF00');

      if ($opnames) {
        $a = 2;
        foreach ($opnames as $opname) {
          // $opnameItems = $this->site->getStockOpnameItems($opname->id);
          // $total_plus = 0;

          // if ($opnameItems) {
          //   foreach ($opnameItems as $opnameItem) {
          //     if ($opnameItem->subtotal > 0) {
          //       $total_plus += $opnameItem->subtotal;
          //     }
          //   }
          // }

          // unset($opnameItems);

          $excel->setCellValue("A{$a}", $a - 1);
          $excel->setCellValue("B{$a}", $opname->date);
          $excel->setCellValue("C{$a}", $opname->reference);
          $excel->setCellValue("D{$a}", $opname->adjustment_plus_ref);
          $excel->setCellValue("E{$a}", $opname->adjustment_min_ref);
          $excel->setCellValue("F{$a}", $opname->created_by);
          $excel->setCellValue("G{$a}", $opname->warehouse_name);
          $excel->setCellValue("H{$a}", $opname->total_lost);
          $excel->setCellValue("I{$a}", $opname->total_plus);
          $excel->setCellValue("J{$a}", $opname->total_edited);
          $excel->setCellValue("K{$a}", $opname->status);
          $excel->setCellValue("L{$a}", htmlRemove($opname->note));
          if ($opname->attachment) {
            $excel->setCellValue("M{$a}", "Image");
            $excel->setUrl("M{$a}", 'https://erp.indoprinting.co.id/admin/gallery/get?name=' . $opname->attachment);
          }
          $a++;
        }
      } else {
        $excel->setCellValue('A2', 'No data available');
        $excel->mergeCells('A2:M2');
        $excel->setHorizontalAlign('A2', 'center');
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
      // $excel->setColumnAutoWidth('L'); // Notes
      $excel->setColumnAutoWidth('M');

      $excel->setTitle('Stock Opname');
      $excel->export('PrintERP-StockOpname-' . date('Ymd_His'));
    }

    $this->load->library('datatable');

    $this->datatable->select("stock_opnames.id AS id, stock_opnames.adjustment_plus_id AS adjustment_plus_id,
    stock_opnames.adjustment_min_id AS adjustment_min_id, stock_opnames.date AS date,
    stock_opnames.reference AS reference,
    adj_plus.reference AS adjustment_plus_ref, adj_min.reference AS adjustment_min_ref,
    creator.fullname AS created_by, warehouses.name AS warehouse_name,
    stock_opnames.total_lost, stock_opnames.total_plus, stock_opnames.total_edited,
    stock_opnames.status AS status, stock_opnames.note AS note, stock_opnames.attachment AS attachment")
      ->from('stock_opnames')
      ->join('adjustments AS adj_plus', 'adj_plus.id = stock_opnames.adjustment_plus_id', 'left')
      ->join('adjustments AS adj_min', 'adj_min.id = stock_opnames.adjustment_min_id', 'left')
      ->join('warehouses', 'warehouses.id = stock_opnames.warehouse_id', 'left')
      ->join('(SELECT id, fullname FROM users) AS creator', 'creator.id = stock_opnames.created_by', 'left');

    if ($warehouse_id = XSession::get('warehouse_id')) {
      $this->datatable->where('stock_opnames.warehouse_id', $warehouse_id);
    }

    if ($warehouses) {
      $this->datatable->group_start();
      foreach ($warehouses as $wh) {
        $this->datatable->or_where('stock_opnames.warehouse_id', $wh);
      }
      $this->datatable->group_end();
    }

    if ($start_date) {
      $end_date = ($end_date ?? date('Y-m-d'));
      $this->datatable->where("stock_opnames.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
    }

    if ($group_by == 'opname') {
      $this->datatable->group_by('id');
    } else if ($group_by == 'pic') {
      $this->datatable->group_by('created_by');
    } else if ($group_by == 'warehouse') {
      $this->datatable->group_by('stock_opnames.warehouse_id');
    }

    echo $this->datatable->generate();
  }

  /**
   * Automatic Stock Opname Suggestions.
   */
  private function stock_opname_getStockOpnameSuggestions()
  {
    $user_id      = getGET('user');
    $warehouse_id = getGET('warehouse'); // warehouse_id

    if ($warehouse_id) {
      $user = $this->site->getUserByID($user_id);
      $warehouse = $this->site->getWarehouseByID($warehouse_id);

      $userJS = getJSON($user->json_data);

      if (!$user) {
        sendJSON(['error' => 1, 'msg' => 'User not found.']);
      }

      if (isset($userJS->so_cycle)) {
        $so_cycle = $userJS->so_cycle;
      } else {
        $so_cycle = 1;

        $userJS->so_cycle = $so_cycle;

        $userData = [
          'json_data' => json_encode($userJS)
        ];

        $this->site->updateUser($user->id, $userData);
      }

      $itemSync = TRUE; // Enable item sync quantity.
      $items = $this->site->getStockOpnameSuggestions($user->id, $warehouse->id, $so_cycle);

      if (empty($items)) {
        $so_cycle = 1; // Reset Cycle to 1 if item not present.
        $items = $this->site->getStockOpnameSuggestions($user->id, $warehouse->id, $so_cycle);
      }

      if ($items) {
        if ($itemSync) {
          // Sync item stock before sent to browser.
          foreach ($items as $item) {
            // $this->site->syncProductQty($item->id, $warehouse->id);
            Product::sync($item->id, $warehouse->id);
          }

          // Get items with updated stock.
          $items = $this->site->getStockOpnameSuggestions($user->id, $warehouse->id, $so_cycle);
        }

        sendJSON(['error' => 0, 'items' => $items, 'so_cycle' => $so_cycle]);
      }
      sendJSON(['error' => 1, 'msg' => 'Tidak ada item yang disarankan.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Warehouse tidak ditemukan.']);
  }

  private function stock_opname_index()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => 'Stock Opname List']
    ];
    $meta = ['page_title' => 'Stock Opname List', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/stock_opname/index', $this->data);
  }

  private function stock_opname_formula()
  {
    $items = [];
    $productId = getGET('product'); // product id

    $product = $this->site->getProduct(['id' => $productId]);

    view($this->theme . 'products/stock_opname/formula', $this->data);
  }

  private function stock_opname_suggestions()
  {
    $term         = getGET('term');
    $warehouse_id = getGET('warehouse');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $this->load->library('datatable'); // My own Datatable Library

      $this->datatable->select("id, CONCAT('(', code, ') ', name) AS productName")
        ->from('products')
        ->like('type', 'standard');

      echo $this->datatable->generate();
    } else {
      $this->data['term'] = $term;
      $this->data['warehouse_id'] = $warehouse_id;
      view($this->theme . 'products/stock_opname/suggestion', $this->data);
    }
  }

  private function stock_opname_syncStockOpname()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->input->is_ajax_request()) {
      if ($values = getPOST('val')) {
        foreach ($values as $opname_id) {
          $this->site->syncStockOpname($opname_id);
        }
      } else {
        $this->site->syncStockOpname();
      }
    }
  }

  private function stock_opname_view($opname_id = NULL) // so_view
  {
    $items = [];
    $mode  = getGET('mode'); // minus or plus or empty.

    $opname_items = ($opname_id ? $this->site->getStockOpnameItems($opname_id) : []);

    $this->data['opname'] = $this->site->getStockOpnameByID($opname_id);
    $this->data['items'] = $opname_items;
    $this->load->view($this->theme . 'products/stock_opname/view', $this->data);
  }

  // public function suggestions()
  // {
  //   $term = getGET('term', true);

  //   if (strlen($term) < 1 || !$term) {
  //     die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
  //   }

  //   // $rows = $this->products_model->getProductNames($term);
  //   $rows = $this->site->getProductNames($term);
  //   if ($rows) {
  //     foreach ($rows as $row) {
  //       $pr[] = ['id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'code' => $row->code, 'name' => $row->name, 'price' => $row->price, 'qty' => 1];
  //     }
  //     sendJSON($pr);
  //   } else {
  //     sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
  //   }
  // }

  public function select2()
  {
    $term             = getGET('term');
    $type             = getGET('type') ?? 'standard';
    $limit            = getGET('limit') ?? 10;
    $warehouseId      = getGET('warehouse_id');
    $warehouseIdFrom  = getGET('warehouse_from_id');
    $warehouseIdTo    = getGET('warehouse_to_id');

    if (getGET('id')) {
      $term = [];
      $term['id'] = getGET('id', TRUE);
    }

    $opt = [
      'limit' => $limit,
      'type' => $type
    ];

    if ($warehouseId) {
      $opt['warehouse_id'] = $warehouseId;
    }
    if ($warehouseIdFrom) {
      $opt['warehouse_id_from'] = $warehouseIdFrom;
    }
    if ($warehouseIdTo) {
      $opt['warehouse_id_to'] = $warehouseIdTo;
    }

    $items = $this->site->getProductSelect2($term, $opt);
    $this->response(200, ['results' => $items]);
  }

  public function suggestion_select()
  {
    $term = getGET('term');
    $type = getGET('type') ?? 'standard';
    $limit = getGET('limit') ?? 10;

    if (getGET('id')) {
      $term = [];
      $term['id'] = getGET('id', TRUE);
    }

    $items = $this->site->getProductSuggestions($term, $type, $limit);
    sendJSON(['results' => $items]);
  }

  public function transfer()
  {
    checkPermission('products-transfer_view');

    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $meta = [
      'page_title' => lang('products_transfer'),
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('products'), 'page' => lang('products')],
        ['link' => '#', 'page' => lang('transfer')]
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/transfer/index', $this->data);
  }

  protected function transfer_add()
  {
    if (!getPermission('products-transfer_add')) {
      $this->response(401, ['message' => 'Anda tidak memiliki akses untuk menambahkan.']);
    }

    if ($this->requestMethod == 'POST') {
      $createdAt       = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $createdBy       = getPOST('created_by');
      $warehouseIdFrom = getPOST('from_warehouse');
      $warehouseIdTo   = getPOST('to_warehouse');
      $note            = getPOST('note');
      $products        = getPOST('product');

      $items = [];

      if (!$products) $this->response(400, ['message' => 'Item tidak boleh kosong.']);

      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $items[] = [
          'product_id'   => $products['id'][$a],
          'markon_price' => filterDecimal($products['markon_price'][$a]),
          'quantity'     => filterDecimal($products['quantity'][$a]),
          'spec'         => htmlEncode($products['spec'][$a])
        ];
      }

      $ptData = [
        'created_at'        => $createdAt,
        'created_by'        => $createdBy,
        'warehouse_id_from' => $warehouseIdFrom,
        'warehouse_id_to'   => $warehouseIdTo,
        'note'              => $note
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $ptData['attachment'] = $uploader->storeRandom();
      }

      if (ProductTransfer::add($ptData, $items)) {
        $this->response(201, ['message' => 'Product Transfer berhasil dibuat.']);
      }
      $this->response(400, ['message' => 'Gagal membuat Product Transfer.']);
    }

    $this->load->view($this->theme . 'products/transfer/add', $this->data);
  }

  protected function transfer_addPayment($ptId = NULL)
  {
    $pt = ProductTransfer::getRow(['id' => $ptId]);

    if ($this->requestMethod == 'POST') {
      $createdAt   = dtPHP(getPOST('created_at'));
      $createdBy   = getPOST('created_by');
      $bankIdFrom  = getPOST('bank_id_from');
      $bankIdTo    = getPOST('bank_id_to');
      $amount      = filterDecimal(getPOST('amount'));
      $note        = htmlEncode(getPOST('note'));

      $paymentData = [
        'transfer_id'        => $pt->id,
        'bank_id_from' => $bankIdFrom,
        'bank_id_to'   => $bankIdTo,
        'amount'       => $amount,
        'note'         => $note,
        'created_at'   => $createdAt,
        'created_by'   => $createdBy,
      ];

      if (ProductTransfer::addPayment($paymentData)) {
        // Change payment status by sync it.
        ProductTransfer::syncPayment($pt->id);

        $this->response(201, ['message' => 'Berhasil dibayar.']);
      }

      $this->response(400, ['message' => GetLastError()]);
    }

    $this->data['pt'] = $pt;

    $this->load->view($this->theme . 'products/transfer/addPayment', $this->data);
  }

  protected function transfer_addProductTransferFromPlan()
  {
    $warehouses = getPOST('warehouse'); // ID: durian, fatmawati, tembalang...

    if ($warehouses) {
      $failed  = 0;
      $success = 0;

      foreach ($warehouses as $whId) {
        // Add product transfer by each warehouse id.
        if (ProductTransfer::addByWarehouseId($whId)) {
          $success++;
        } else {
          $failed++;
        }
      }

      $this->response(201, ['message' => "Transfer produk {$success} berhasil ditambahkan. {$failed} gagal ditambahkan."]);
    }
    $this->response(400, ['message' => 'Gagal menambahkan transfer produk.']);
  }

  protected function transfer_delete($pmId = NULL)
  {
    if (!getPermission('products-transfer_delete')) {
      $this->response(401, ['message' => 'Anda tidak punya akses untuk menghapus.']);
    }

    if ($vals = getPOST('val')) {
      $deleted = 0;

      foreach ($vals as $pmId) {
        if (ProductTransfer::delete(['id' => $pmId])) {
          $deleted++;
        }
      }

      if ($deleted) $this->response(200, ['message' => "{$deleted} Product Transfer berhasil dihapus."]);

      $this->response(400, ['message' => 'Gagal menghapus Product Transfer.']);
    } else if ($pmId) {
      if ($deleted = ProductTransfer::delete(['id' => $pmId])) {
        $this->response(200, ['message' => "{$deleted} Product Transfer berhasil dihapus."]);
      }
      $this->response(400, ['message' => 'Gagal menghapus Product Transfer.']);
    }

    $this->response(400, ['message' => 'Tidak ada Product Transfer yang terpilih.']);
  }

  protected function transfer_deletePayment($paymentId)
  {
    if ($this->requestMethod == 'POST') {
      $payment = Payment::getRow(['id' => $paymentId]);

      if (Payment::delete(['id' => $paymentId])) {
        $pt = ProductTransfer::getRow(['id' => $payment->transfer_id]);

        ProductTransfer::syncPayment($pt->id);

        $this->response(200, ['message' => 'Pembayaran berhasil dihapus.']);
      }
      $this->response(400, ['message' => 'Pembayaran gagal dihapus.']);
    }
  }

  protected function transfer_edit($ptId = NULL)
  {
    $pt = ProductTransfer::getRow(['id' => $ptId]);
    $mode = (getGET('mode') ?? getPOST('mode') ?? 'edit');

    if ($mode == 'edit') {
      checkPermission('products-transfer_edit');
    } else if ($mode == 'status') {
      checkPermission('products-transfer_status');
    }

    if ($this->requestMethod == 'POST') {
      $createdAt       = dtPHP($this->isAdmin ? getPOST('created_at') : $pt->created_at);
      $createdBy       = getPOST('created_by');
      $warehouseIdFrom = getPOST('from_warehouse');
      $warehouseIdTo   = getPOST('to_warehouse');
      $note            = getPOST('note');
      $products        = getPOST('product');
      $status          = getPOST('status');

      $isReceived = ($status == 'received' || $status == 'received_partial');

      if (empty($status)) {
        $this->response(400, ['message' => 'Status tidak boleh kosong.']);
      }

      $items = [];
      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $receivedQty = (filterDecimal($products['received_qty'][$a]) + filterDecimal($products['rest_qty'][$a]));

        $items[] = [
          'product_id'   => floatval($products['id'][$a]),
          'markon_price' => filterDecimal($products['markon_price'][$a]),
          'quantity'     => filterDecimal($products['quantity'][$a]),
          'received_qty' => ($isReceived ? $receivedQty : 0),
          'spec'         => htmlEncode($products['spec'][$a]),
          'status'       => $status
        ];
      }

      $ptData = [
        'created_at'        => $createdAt,
        'created_by'        => $createdBy,
        'warehouse_id_from' => $warehouseIdFrom,
        'warehouse_id_to'   => $warehouseIdTo,
        'status'            => $status,
        'note'              => htmlEncode($note)
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $ptData['attachment'] = $uploader->storeRandom();
      }

      if (ProductTransfer::update($ptId, $ptData, $items)) {
        $this->response(200, ['message' => 'Product Transfer has been updated.']);
      }
      $this->response(400, ['message' => 'Failed to update Product Transfer.']);
    }

    $ptitems = ProductTransferItem::get(['transfer_id' => $ptId]);
    $items = [];

    foreach ($ptitems as $ptitem) {
      $product = Product::getRow(['id' => $ptitem->product_id]);
      $whProduct = WarehouseProduct::getRow(['product_id' => $product->id, 'warehouse_id' => $pt->warehouse_id_from]);

      $items[] = [
        'id'           => $ptitem->product_id,
        'code'         => $ptitem->product_code,
        'name'         => $product->name,
        'markon_price' => $ptitem->markon_price,
        'quantity'     => $ptitem->quantity,
        'real_qty'     => $whProduct->quantity,
        'received_qty' => $ptitem->received_qty,
        'spec'         => $ptitem->spec
      ];
    }

    $this->data['mode'] = $mode;
    $this->data['pt'] = $pt;
    $this->data['ptitems'] = $items;

    $this->load->view($this->theme . 'products/transfer/edit', $this->data);
  }

  protected function transfer_editPayment($paymentId = NULL)
  {
  }

  protected function transfer_getTransfers()
  {
    $startDate      = getGET('start_date');
    $endDate        = getGET('end_date');
    $warehousesFrom = getGET('warehouse_id_from');
    $warehousesTo   = getGET('warehouse_id_to');

    if ($whId = XSession::get('warehouse_id')) {
      $warehousesTo = [];
      $warehousesTo[] = $whId;
    }

    $this->load->library('datatable');

    $this->datatable
      ->select("product_transfer.id AS id, product_transfer.id AS pid, reference, attachment_id,
        items, whfrom.name AS wh_name_from, whto.name AS wh_name_to,
        product_transfer.status AS status, product_transfer.payment_status AS payment_status,
        product_transfer.grand_total, product_transfer.paid, product_transfer.note,
        product_transfer.created_at, creator.fullname AS creator_name")
      ->from('product_transfer')
      ->join('warehouses whfrom', 'whfrom.id = product_transfer.warehouse_id_from', 'left')
      ->join('warehouses whto', 'whto.id = product_transfer.warehouse_id_to', 'left')
      ->join('users creator', 'creator.id = product_transfer.created_by', 'left')
      ->editColumn('pid', function ($data) {
        return "
        <div class=\"text-center\">
          <a href=\"{$this->theme}products/transfer/delete/{$data['id']}\"
            class=\"tip \"
            data-action=\"confirm\" style=\"color:red;\" title=\"Delete Product Transfer\">
              <i class=\"fad fa-fw fa-trash\"></i>
          </a>
          <a href=\"{$this->theme}products/transfer/edit/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg modal-xl\" title=\"Edit Product Transfer\">
              <i class=\"fad fa-fw fa-edit\"></i>
          </a>
          <a href=\"{$this->theme}products/transfer/view/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg modal-xl\"
            title=\"View Details\">
              <i class=\"fad fa-fw fa-chart-bar\"></i>
          </a>
          <a href=\"{$this->theme}products/transfer/view/{$data['id']}?m=noprice\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg modal-xl\"
            title=\"View Invoice\">
              <i class=\"fad fa-fw fa-book\"></i>
          </a>
          <a href=\"{$this->theme}products/transfer/payments/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg modal-xl\"
            title=\"View Payments\">
              <i class=\"fad fa-fw fa-money-bill-alt\"></i>
          </a>
        </div>";
      })
      ->editColumn('status', function ($data) {
        switch ($data['status']) {
          case 'packing':
            $type = 'warning';
            break;
          case 'sent':
            $type = 'success';
            break;
          case 'received_partial':
            $type = 'info';
            break;
          case 'received':
            $type = 'primary';
            break;
          default:
            $type = 'warning';
        }

        $status = ucwords(str_replace('_', ' ', $data['status']));

        return "
        <div class=\"text-center\">
          <a href=\"" . admin_url('products/transfer/edit/' . $data['id'] . '?mode=status') . "\"
            class=\"label label-{$type} status\"
            data-toggle=\"modal\" data-target=\"#myModal\" data-modal-class=\"modal-lg\">{$status}
          </a>
        </div>
        ";
      })
      ->editColumn('payment_status', function ($data) {
        switch ($data['payment_status']) {
          case 'pending':
            $type = 'warning';
            break;
          case 'paid':
            $type = 'success';
            break;
          case 'paid_partial':
            $type = 'info';
            break;
          default:
            $type = 'warning';
        }

        $status = ucwords(str_replace('_', ' ', $data['payment_status']));

        return "
        <div class=\"text-center\">
          <a href=\"" . admin_url('products/transfer/addPayment/' . $data['id']) . "\"
            class=\"label label-{$type} status\"
            data-toggle=\"modal\" data-target=\"#myModal\">{$status}
          </a>
        </div>
        ";
      });

    if ($startDate) {
      $this->datatable->where("created_at >= '{$startDate} 00:00:00'");
    }

    if ($endDate) {
      $this->datatable->where("created_at <= '{$endDate} 23:59:59'");
    }

    if ($warehousesFrom) {
      $this->datatable->where_in('warehouse_id_from', implode(',', $warehousesFrom));
    }

    if ($warehousesTo) {
      $this->datatable->where_in('warehouse_id_to', implode(',', $warehousesTo));
    }

    $this->datatable->generate();
  }

  protected function transfer_getTransferPlan()
  {
    $today = getDayName(date('w') + 1); // Get today name. Ex. senin, selasa, ...

    $this->load->library('datatable');
    $this->datatable
      ->select("warehouses.id AS id, warehouses.code AS warehouse_code,
        warehouses.name AS warehouse_name,
          JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_days')) AS visit_days,
          JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_weeks')) AS visit_weeks,
        warehouses.id AS warehouses_id") // FALSE required for disable escaping column.
      ->from('warehouses')
      ->where('active', 1)
      ->like("LOWER(JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_days')))", $today, 'both');

    echo $this->datatable->generate();
  }

  protected function transfer_payments($ptId = NULL)
  {
    ProductTransfer::syncPayment($ptId);

    $this->data['payments'] = Payment::get(['transfer_id' => $ptId]);
    $this->data['pt']       = ProductTransfer::getRow(['id' => $ptId]);

    $this->load->view($this->theme . 'products/transfer/payments', $this->data);
  }

  protected function transfer_plan()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => 'Products'],
      ['link' => admin_url('products/transfer'), 'page' => 'Transfer'],
      ['link' => '#', 'page' => 'Transfer Plan']
    ];
    $meta = ['page_title' => 'Product Transfer Plan', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/transfer/plan', $this->data);
  }

  protected function transfer_updateSafetyStock()
  {
    if ($this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST') {
      ini_set('max_execution_time', 0);

      if ($this->site->syncTransferSafetyStock()) {
        sendJSON(['error' => 0, 'msg' => 'Safety stock produk berhasil disinkron.']);
      }

      sendJSON(['error' => 1, 'msg' => 'Gagal menyinkronkan safety stock.']);
    }
  }

  protected function transfer_view($ptId = NULL)
  {
    checkPermission('products-transfer_view');

    $ptitems = ProductTransferItem::get(['transfer_id' => $ptId]);
    $items = [];

    foreach ($ptitems as $ptitem) {
      $product = Product::getRow(['id' => $ptitem->product_id]);

      $items[] = (object)[
        'product_id'   => $ptitem->product_id,
        'product_code' => $ptitem->product_code,
        'product_name' => $product->name,
        'markon_price' => $ptitem->markon_price,
        'quantity'     => $ptitem->quantity,
        'received_qty' => $ptitem->received_qty,
        'spec'         => $ptitem->spec,
        'status'       => $ptitem->status
      ];
    }

    $this->data['pt']      = ProductTransfer::getRow(['id' => $ptId]);
    $this->data['ptitems'] = $items;

    $this->load->view($this->theme . 'products/transfer/view', $this->data);
  }

  public function view($id = null)
  {
    $this->sma->checkPermissions('index');

    $pr_details = $this->site->getProductByID($id);
    if (!$id || !$pr_details) {
      $this->session->set_flashdata('error', lang('prduct_not_found'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    $this->data['barcode'] = "<img src='" . admin_url('products/gen_barcode/' . $pr_details->code . '/' . $pr_details->barcode_symbology . '/40/0') . "' alt='" . $pr_details->code . "' class='pull-left' />";
    if ($pr_details->type == 'combo') {
      $this->data['combo_items'] = $this->site->getProductComboItems($id);
    }
    $this->data['product']          = $pr_details;
    $this->data['unit']             = $this->site->getUnitByID($pr_details->unit);
    $this->data['images']           = NULL; //$this->products_model->getProductPhotos($id);
    $this->data['category']         = $this->site->getProductCategoryByID($pr_details->category_id);
    $this->data['subcategory']      = $pr_details->subcategory_id ? $this->site->getProductCategoryByID($pr_details->subcategory_id) : null;
    $this->data['warehouses']       = $this->site->getAllWarehousesWithPQ($id);
    $this->data['sold']             = NULL; //$this->products_model->getSoldQty($id);
    $this->data['purchased']        = NULL; //$this->products_model->getPurchasedQty($id);

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('products'), 'page' => lang('products')],
      ['link' => '#', 'page' => $pr_details->name]
    ];
    $meta = ['page_title' => $pr_details->name, 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('products/view', $this->data);
  }

  public function view_adjustment($id)
  {
    $adjustment = $this->site->getStockAdjustmentByID($id);

    if (!$id || !$adjustment) {
      $this->session->set_flashdata('error', lang('adjustment_not_found'));
      $this->sma->md();
    }

    $this->data['inv']        = $adjustment;
    $this->data['rows']       = $this->site->getStockAdjustmentItems($id);
    $this->data['created_by'] = $this->site->getUser($adjustment->created_by);
    $this->data['updated_by'] = $this->site->getUser($adjustment->updated_by);
    $this->data['warehouse']  = $this->site->getWarehouseByID($adjustment->warehouse_id);
    $this->load->view($this->theme . 'products/view_adjustment', $this->data);
  }

  // public function view_count($id)
  // {
  //   $this->sma->checkPermissions('stock_count', true);
  //   $stock_count = $this->products_model->getStouckCountByID($id);
  //   if (!$stock_count->finalized) {
  //     $this->sma->md('admin/products/finalize_count/' . $id);
  //   }

  //   $this->data['stock_count']       = $stock_count;
  //   $this->data['stock_count_items'] = $this->products_model->getStockCountItems($id);
  //   $this->data['warehouse']         = $this->site->getWarehouseByID($stock_count->warehouse_id);
  //   $this->data['adjustment']        = $this->products_model->getAdjustmentByCountID($id);
  //   $this->load->view($this->theme . 'products/view_count', $this->data);
  // }
}
