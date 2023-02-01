<?php defined('BASEPATH') or exit('No direct script access allowed');

use \PhpOffice\PhpSpreadsheet\Cell\DataType;

class Procurements extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // $this->session->set_userdata('requested_page', $this->uri->uri_string());
      // $this->sma->md('login');
      loginPage();
    }

    if ($this->Supplier) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->load->helper('security');
    $this->load->library('form_validation');

    $this->data['status_mode'] = FALSE; // Used by purchases. Delete soon
    $this->po_mode = 'edit';
    $this->iuse_mode = 'edit';
  }

  public function index()
  {
    admin_redirect();
  }

  public function getSupplierCost($supplier_id, $product)
  {
    switch ($supplier_id) {
      case $product->supplier1:
        $cost = $product->supplier1price > 0 ? $product->supplier1price : $product->cost;
        break;
      case $product->supplier2:
        $cost = $product->supplier2price > 0 ? $product->supplier2price : $product->cost;
        break;
      case $product->supplier3:
        $cost = $product->supplier3price > 0 ? $product->supplier3price : $product->cost;
        break;
      case $product->supplier4:
        $cost = $product->supplier4price > 0 ? $product->supplier4price : $product->cost;
        break;
      case $product->supplier5:
        $cost = $product->supplier5price > 0 ? $product->supplier5price : $product->cost;
        break;
      default:
        $cost = $product->cost;
    }
    return $cost;
  }

  /**
   * INTERNAL USES
   */

  public function internal_uses()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param warehouse.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function internal_uses_add()
  {
    $this->form_validation->set_rules('to_warehouse', lang('warehouse') . ' (' . lang('to') . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang('warehouse') . ' (' . lang('from') . ')', 'required|is_natural_no_zero');

    if ($this->form_validation->run()) {
      $counter          = '';
      $createdAt        = $this->serverDateTime; // Using server date time.
      $grandTotal       = 0;
      $items            = '';
      $warehouseIdFrom  = getPOST('from_warehouse');
      $warehouseIdTo    = getPOST('to_warehouse');
      $note             = htmlEncode(getPOST('note'));
      $status           = getPOST('status'); // Must 'need_approval';
      $category         = getPOST('category'); // Sparepart, Consumable
      $supplierId       = getPOST('supplier');
      $tsId             = getPOST('ts');

      if (empty($category)) {
        $this->session->set_flashdata('error', "Harap pilih kategory, Consumable atau Sparepart.");
        admin_redirect('procurements/internal_uses/add');
      }

      $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;

      for ($r = 0; $r < $i; $r++) {
        $itemCode      = $_POST['product_code'][$r];
        $item_machine   = $_POST['machines'][$r];
        $item_price     = $_POST['price'][$r];
        $item_quantity  = $_POST['quantity'][$r];
        $itemSpec      = $_POST['spec'][$r]; // Counter.
        $itemUCR        = $_POST['ucr'][$r]; // Unique Code Replacement.

        // Prevent input lower counter than current counter.
        if (!empty($itemSpec)) {
          $whp = WarehouseProduct::getRow(['product_code' => 'KLIKPOD', 'warehouse_id' => $warehouseIdTo]);

          if ($whp) {
            $lastKLIKQty = intval($whp->quantity);

            if ($lastKLIKQty > intval($itemSpec)) {
              $this->session->set_flashdata('error', "Klik {$itemSpec} tidak sesuai klik terakhir {$lastKLIKQty}.");
              admin_redirect('procurements/internal_uses/add');
            }
          }
        }

        if (isset($itemCode) && isset($item_quantity)) {
          $product = Product::getRow(['code' => $itemCode]);
          $pcategory = Category::getRow(['id' => $product->category_id]);

          if (!$item_quantity) {
            $this->session->set_flashdata('error', "No quantity for item {$itemCode}");
            admin_redirect('procurements/internal_uses/add');
          }

          if (!$product) {
            $this->session->set_flashdata('error', lang('no_match_found') . ' (' . lang('product_name') . ' <strong>' . $product->name . '</strong> ' . lang('product_code') . ' <strong>' . $product->code . '</strong>)');
            admin_redirect('procurements/internal_uses/add');
          }

          if ($product->iuse_type == 'sparepart') { // If item sparepart and no machine. then error.
            if (empty($item_machine)) {
              $this->session->set_flashdata('error', "MESIN BELUM DIPILIH UNTUK ITEM <b>{$itemCode}</b>!");
              admin_redirect('procurements/internal_uses/add');
            }
          } else if ($product->iuse_type == 'consumable') {
            if (empty($item_machine)) {
              if ($pcategory->code == 'DPI' || $pcategory->code == 'POD') {
                $this->session->set_flashdata('error', "MESIN BELUM DIPILIH UNTUK ITEM <b>{$itemCode}</b>!");
                admin_redirect('procurements/internal_uses/add');
              }
            }
          }

          $whp = WarehouseProduct::getRow(['product_id' => $product->id, 'warehouse_id' => $warehouseIdFrom]);
          $warehouseQtyFrom = ($whp ? $whp->quantity : 0);
          $total_markon_price = (getMarkonPrice($product->cost, $product->markon) * $item_quantity);

          if ($warehouseQtyFrom < $item_quantity) {
            $this->session->set_flashdata('error', "Stok di outlet ({$warehouseQtyFrom}) kurang dari yang diperlukan ({$item_quantity}).");
            admin_redirect('procurements/internal_uses/add');
          }

          $productData = [
            'product_id'  => $product->id,
            'machine_id'  => $item_machine,
            'price'       => $item_price,
            'quantity'    => $item_quantity,
            'spec'        => $itemSpec,
            'ucr'         => $itemUCR
          ]; // unique_code generated in model.

          $items .= '- ' . getExcerpt($product->name) . '<br>';
          if ($itemSpec) $counter .= $itemSpec . '<br>'; // Item spec used as counter.
          $grandTotal += $total_markon_price;
          $products[] = $productData;
        }
      }

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $internalUseData = [
        'created_at'        => $createdAt,
        'category'          => $category, // Add new, consumable/sparepart.
        'biller_id'         => warehouseToBiller($category == 'sparepart' ? $warehouseIdFrom : $warehouseIdTo),
        'from_warehouse_id' => $warehouseIdFrom,
        'to_warehouse_id'   => $warehouseIdTo,
        'items'             => $items,
        'grand_total'       => $grandTotal,
        'counter'           => $counter,
        'note'              => $note,
        'supplier_id'       => $supplierId,
        'ts_id'             => $tsId,
        'created_by'        => XSession::get('user_id'),
        'status'            => $status, // new add = need_approval
      ];

      $upload = new FileUpload();

      if ($upload->has('document')) {
        if ($upload->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect('procurements/internal_uses/add');
        }
        $internalUseData['attachment'] = $upload->storeRandom();
      } else if ($category == 'consumable') {
        $this->session->set_flashdata('error', 'Attachment maks. 2MB harus disertakan.');
        admin_redirect('procurements/internal_uses/add');
      }
    }

    if ($this->form_validation->run()) {
      if ($this->site->addStockInternalUse($internalUseData, $products)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', 'Internal use berhasil ditambahkan.');
      } else {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('error', 'Gagal menambahkan internal use.');
      }
      admin_redirect('procurements/internal_uses');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['teamSupports'] = getTeamSupports();
      $this->data['machines']   = $this->site->getAllMachines();
      $this->data['warehouses'] = $this->site->getAllWarehouses();

      $bc   = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/internal_uses'), 'page' => lang('internal_uses')],
        ['link' => '#', 'page' => lang('add_internal_use')]
      ];

      $meta = ['page_title' => lang('add_internal_use'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/internal_uses/add', $this->data);
    }
  }

  private function internal_uses_delete($iuseId)
  {
    $this->sma->checkUserPermissions('internal_uses-delete');

    if (getGET('id')) {
      $iuseId = getGET('id');
    }

    if ($this->site->deleteStockInternalUse($iuseId)) {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 0, 'msg' => 'Internal Use has been deleted successfully.']);
      } else {
        $this->session->set_flashdata('message', 'Internal Use has been deleted successfully.');
        admin_redirect('procurements/internal_uses');
      }
    } else {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 1, 'msg' => 'Failed to delete Internal Use.']);
      } else {
        $this->session->set_flashdata('message', 'Failed to delete Internal Use.');
        admin_redirect('procurements/internal_uses');
      }
    }
  }

  private function internal_uses_edit($iuseId)
  {
    $iuse = $this->site->getStockInternalUseByID($iuseId);

    $this->form_validation->set_message('is_natural_no_zero', lang('no_zero_required'));
    $this->form_validation->set_rules('reference', lang('reference'), 'required');
    $this->form_validation->set_rules('to_warehouse', lang('warehouse') . ' (' . lang('to') . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang('warehouse') . ' (' . lang('from') . ')', 'required|is_natural_no_zero');

    if ($this->form_validation->run()) {
      $counter          = '';
      $date             = rd_trim(getPOST('date'));
      $grand_total      = 0;
      $items            = '';
      $warehouseIdFrom  = getPOST('from_warehouse');
      $warehouseIdTo    = getPOST('to_warehouse');
      $note             = htmlEncode(getPOST('note'));
      $status           = getPOST('status');
      $category         = getPOST('category');
      $supplierId       = getPOST('supplier');
      $tsId             = getPOST('ts');

      if ($this->iuse_mode == 'status') {
        if ($status == $iuse->status) {
          $this->session->set_flashdata('error', 'Status not changed');
          admin_redirect('procurements/internal_uses/status/' . $iuse->id);
        }
      }

      if (empty($category)) {
        $this->session->set_flashdata('error', 'Category is empty.');
        admin_redirect('procurements/internal_uses/status/' . $iuse->id);
      }

      $i = isset($_POST['product_id']) ? count($_POST['product_id']) : 0;

      for ($r = 0; $r < $i; $r++) {
        $itemCode       = getPOST('product_code')[$r];
        $item_machine   = getPOST('machines')[$r];
        $item_price     = getPOST('price')[$r];
        $item_quantity  = getPOST('quantity')[$r];
        $itemSpec       = getPOST('spec')[$r];
        $itemUCR        = getPOST('ucr')[$r];
        $itemUniqueCode = getPOST('unique_code')[$r];

        if (isset($itemCode) && isset($item_quantity)) {
          $product = Product::getRow(['code' => $itemCode]);

          if (!$product) {
            $this->session->set_flashdata('error', lang('no_match_found') . ' (' . lang('product_name') . ' <strong>' . $product->name . '</strong> ' . lang('product_code') . ' <strong>' . $product->code . '</strong>)');
            admin_redirect('procurements/internal_uses/edit/' . $iuse->id);
          }

          if ($product->iuse_type == 'sparepart') {
            if (empty($item_machine)) {
              $this->session->set_flashdata('error', "Machine is not selected for {$itemCode}.");
              admin_redirect('procurements/internal_uses/edit/' . $iuse->id);
            }
          }

          // $from_warehouse_qty = $this->site->getStockQuantity($product->id, $warehouseIdFrom) + $item_quantity; // Get source stock.
          $whp = $this->site->getWarehouseProduct($product->id, $warehouseIdFrom);
          $from_warehouse_qty = ($whp ? $whp->quantity : 0) + $item_quantity; // Get source stock.
          $total_markon_price = (getMarkonPrice($product->cost, $product->markon) * $item_quantity);

          if ($from_warehouse_qty < $item_quantity) {
            // $this->session->set_flashdata('error', 'Stock on warehouse is more than requested.');
            // admin_redirect('procurements/internal_uses/edit/' . $internal_use->id);
          }

          $product_data = [
            'product_id'  => $product->id,
            'machine_id'  => $item_machine,
            'price'       => $item_price,
            'quantity'    => $item_quantity,
            'spec'        => $itemSpec,
            'ucr'         => $itemUCR,
            'unique_code' => $itemUniqueCode
          ];

          $items .= '- ' . getExcerpt($product->name, 30) . '<br>';
          if ($itemSpec) $counter .= $itemSpec . '<br>'; // Item spec used as counter.
          $grand_total += $total_markon_price;
          $products[] = $product_data;
        }
      }

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $internalUseData = [
        'date'              => $date,
        'category'          => $category, // Add new, consumable/sparepart.
        'biller_id'         => warehouseToBiller($category == 'sparepart' ? $warehouseIdFrom : $warehouseIdTo),
        'from_warehouse_id' => $warehouseIdFrom,
        'to_warehouse_id'   => $warehouseIdTo,
        'items'             => $items,
        'grand_total'       => $grand_total,
        'counter'           => $counter,
        'note'              => $note,
        'supplier_id'       => $supplierId,
        'ts_id'             => $tsId,
        'updated_by'        => XSession::get('user_id'),
        'status'            => $status,
      ];

      $upload = new FileUpload();

      if ($upload->has('document')) {
        if ($upload->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect('procurements/internal_uses/status/' . $iuse->id);
        }

        $internalUseData['attachment'] = $upload->storeRandom();
      } else if ($status == 'installed' && empty($iuse->attachment_id)) {
        $this->session->set_flashdata('error', 'Attachment harus disertakan jika sudah selesai instalasi.');
        admin_redirect('procurements/internal_uses/status/' . $iuse->id);
      }
    }

    if ($this->form_validation->run() == true) {

      if ($this->site->updateStockInternalUse($iuse->id, $internalUseData, $products)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', 'Internal Use has been edited successfully.');
      } else {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('error', 'Failed to edit Internal Use.');
        admin_redirect('procurements/internal_uses/edit/' . $iuse->id);
      }

      admin_redirect('procurements/internal_uses');
    } else {
      $iu_items = [];
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['iuse'] = $iuse;
      $internal_use_items         = $this->site->getStockInternalUseItems($iuse->id);

      foreach ($internal_use_items as $item) {
        $row = $this->site->getProductByID($item->product_id);
        $hash = sha1($item->product_id + mt_rand(1, 1000));

        if (!$row) {
          $row = new stdClass();
        } else {
          unset($row->details, $row->product_details, $row->image, $row->barcode_symbology, $row->cf1, $row->cf2, $row->cf3, $row->cf4, $row->cf5, $row->cf6, $row->supplier1price, $row->supplier2price, $row->cfsupplier3price, $row->supplier4price, $row->supplier5price, $row->supplier1, $row->supplier2, $row->supplier3, $row->supplier4, $row->supplier5, $row->supplier1_part_no, $row->supplier2_part_no, $row->supplier3_part_no, $row->supplier4_part_no, $row->supplier5_part_no);
        }

        $whp_to               = $this->site->getWarehouseProduct($item->product_id, $iuse->to_warehouse_id);

        $row->source_qty      = $item->quantity;
        $row->destination_qty = $whp_to->quantity;
        $row->safety_stock    = $whp_to->safety_stock;
        $row->markon_price    = $row->markon_price;
        $row->quantity        = $item->quantity;
        $row->unit            = $item->unit_id;
        $row->spec            = ($item->spec ?? '');
        $row->machine_id      = ($item->machine_id ?? NULL);
        $row->ucr             = ($item->ucr ?? '');
        $row->unique_code     = ($item->unique_code ?? '');

        $units    = $this->site->getUnitsByBUID($row->unit);

        unset($row->json_data);

        $iu_items[$hash] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => $row->name . ' (' . $row->code . ')',
          'row'     => $row,
          'units'   => $units
        ];
      }

      $this->data['iuse_items']   = $iu_items;
      $this->data['iuse_mode']    = $this->iuse_mode;
      $this->data['id']           = $iuse->id;
      $this->data['teamSupports'] = getTeamSupports();
      $this->data['machines']     = $this->site->getAllMachines();
      $this->data['warehouses']   = $this->site->getAllWarehouses();

      if ($this->iuse_mode == 'status') {
        $page_name = lang('status_internal_uses');
      } else {
        $page_name = lang('edit_internal_uses');
      }

      $bc   = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/internal_uses'), 'page' => lang('internal_uses')],
        ['link' => '#', 'page' => $page_name]
      ];

      $meta = ['page_title' => $page_name, 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/internal_uses/edit', $this->data);
    }
  }

  private function internal_uses_edit_item($itemId)
  {
    if (!$this->isAdmin) {
      $this->session->set_flashdata('error', lang('access_denied'));
      die('<script>setTimeout(() => location.reload(), 0)</script>');
    }

    if ($this->requestMethod == 'POST') {
      $product = $this->site->getProductByID($itemId);
      $price        = filterDecimal(getPOST('price'));
      $markon_price = filterDecimal(getPOST('markon_price'));

      if ($product) {
        if ($this->site->updateProducts([[
          'product_id' => $product->id,
          'price' => $price,
          'markon_price' => $markon_price,
        ]])) {
          sendJSON(['success' => 1, 'message' => "Item {$product->name} has been updated."]);
        }
      }

      sendJSON(['success' => 0, 'message' => "Failed to update."]);
    }

    $this->data['product'] = $this->site->getProductByID($itemId);
    $this->load->view($this->theme . 'procurements/internal_uses/edit_item', $this->data);
  }

  private function internal_uses_getInternalUses()
  {
    $end_date = getGET('end_date');
    $item_name = getGET('item');
    $reference = getGET('reference');
    $start_date = getGET('start_date');
    $warehouse_to = getGET('warehouse');
    $category     = getGET('category');
    $xls = (getGET('xls') == 1 ? TRUE : FALSE);

    $warehouse_id = XSession::get('warehouse_id');

    if ($xls) { // EXCEL REPORT
      $query = "stocks.date, internal_uses.reference,
      creator.fullname AS created_by,
      warehouse_from.name AS from_warehouse, warehouse_to.name AS to_warehouse,
      product_code, product_name, machines.name AS machine_name, stocks.quantity AS quantity, subtotal, spec,
      internal_uses.note,
      updater.fullname AS updated_by,
      internal_uses.status";
      $this->db
        ->select($query)
        ->from('stocks')
        ->join('internal_uses', 'internal_uses.id = stocks.internal_use_id', 'left')
        // Since machines table is deprecated. We use products table as machine list.
        ->join('products AS machines', 'machines.id = stocks.machine_id', 'left')
        ->join('users AS creator', 'creator.id = internal_uses.created_by', 'left')
        ->join('users AS updater', 'updater.id = internal_uses.updated_by', 'left')
        ->join('warehouses AS warehouse_from', 'warehouse_from.id = internal_uses.from_warehouse_id', 'left')
        ->join('warehouses AS warehouse_to', 'warehouse_to.id = internal_uses.to_warehouse_id', 'left')
        ->where('internal_use_id IS NOT NULL');

      if ($warehouse_id) {
        $this->db->where('stocks.warehouse_id', $warehouse_id);
      }

      if ($start_date || $end_date) {
        $start_date = ($start_date ?? date('Y-m-') . '01');
        $end_date   = ($end_date   ?? date('Y-m-d'));
        $this->db->where("internal_uses.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      }

      $this->db->order_by("stocks.date", 'DESC');

      $q = $this->db->get();
      $rows = [];
      if ($q->num_rows() > 0) {
        foreach ($q->result() as $row) {
          $rows[] = $row;
        }
      }

      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('Internal Uses');
      $excel->setCellValue('A1', lang('date'));
      $excel->setCellValue('B1', lang('ref_no'));
      $excel->setCellValue('C1', 'PIC');
      $excel->setCellValue('D1', lang('warehouse') . ' (' . lang('from') . ')');
      $excel->setCellValue('E1', lang('warehouse') . ' (' . lang('to') . ')');
      $excel->setCellValue('F1', lang('items'));
      $excel->setCellValue('G1', lang('machine'));
      $excel->setCellValue('H1', lang('quantity'));
      $excel->setCellValue('I1', lang('subtotal'));
      $excel->setCellValue('J1', lang('counter'));
      $excel->setCellValue('K1', lang('note'));
      $excel->setCellValue('L1', lang('updated_by'));
      $excel->setCellValue('M1', lang('status'));

      $x = 2;

      if ($rows) {
        foreach ($rows as $row) {
          $excel->setCellValue('A' . $x, $row->date);
          $excel->setCellValue('B' . $x, $row->reference);
          $excel->setCellValue('C' . $x, $row->created_by);
          $excel->setCellValue('D' . $x, $row->from_warehouse);
          $excel->setCellValue('E' . $x, $row->to_warehouse);
          $excel->setCellValue('F' . $x, '(' . $row->product_code . ') ' . $row->product_name);
          $excel->setCellValue('G' . $x, (empty($row->machine_name) ? 'All Machines' : $row->machine_name));
          $excel->setCellValue('H' . $x, filterDecimal($row->quantity));
          $excel->setCellValue('I' . $x, filterDecimal($row->subtotal));
          $excel->setCellValue('J' . $x, filterDecimal($row->spec));
          $excel->setCellValue('K' . $x, htmlRemove(htmlDecode($row->note)));
          $excel->setCellValue('L' . $x, $row->updated_by);
          $excel->setCellValue('M' . $x, lang($row->status));

          $x++;
        }
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

      $excel->export('PrintERP-Internal_Uses-' . date('Ymd_His'));
    }

    $this->load->library('datatables');
    $this->datatables->select("internal_uses.id AS id,
      internal_uses.date, internal_uses.reference,
      users.fullname AS created_by,
      from_warehouse.name AS warehouse_from_name,
      to_warehouse.name AS warehouse_to_name,
      items, grand_total,
      internal_uses.counter AS counter_status, note,
      internal_uses.status AS internal_status, attachment")
      ->from('internal_uses');

    // JOIN TABLE
    $this->datatables
      ->join('warehouses AS from_warehouse', 'from_warehouse.id = internal_uses.from_warehouse_id', 'left')
      ->join('warehouses AS to_warehouse', 'to_warehouse.id = internal_uses.to_warehouse_id', 'left')
      ->join('users', 'users.id = internal_uses.created_by', 'left');

    // FILTER
    if ($item_name) {
      $this->datatables
        ->join('stocks', 'stocks.internal_use_id = internal_uses.id', 'left')
        ->group_start()
        ->like('stocks.product_code', $item_name, 'both')
        ->or_like('stocks.product_name', $item_name, 'both')
        ->group_end();
    }
    if ($reference) {
      $this->datatables->like('internal_uses.reference', $reference, 'both');
    }
    if ($warehouse_to) {
      $this->datatables->where('internal_uses.to_warehouse_id', $warehouse_to);
    }
    if ($start_date) {
      $start_date = ($start_date ?? date('Y-m-') . '01');
      $end_date   = ($end_date   ?? date('Y-m-d'));
      $this->datatables->where("internal_uses.date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
    }

    if ($warehouse_id) {
      $this->datatables->where('internal_uses.to_warehouse_id', $warehouse_id);
    }

    if ($category) {
      $this->datatables->like('internal_uses.category', $category, 'none');
    }

    // ACTIONS BUTTON
    $actions =
      '<div class="text-center">
        <div class="btn-group text-left">
          <button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
            ' . lang('actions') . ' <span class="caret"></span>
          </button>
          <ul class="dropdown-menu pull-right" role="menu">
            <li><a href="' . admin_url('procurements/internal_uses/edit/$1') . '"><i class="fad fa-edit"></i> ' . lang('edit') . '</li>
            <li class="divider"></li>
            <li><a href="#" class="tip po" title="' . lang('delete_internal_use') . '" data-content="<p>'
      . lang('r_u_sure') . '</p><a class=\'btn btn-danger po-delete\' id=\'a__$1\' href=\'' . admin_url('procurements/internal_uses/delete/$1') . '\'>'
      . lang('i_m_sure') . '</a> <button class=\'btn po-close\'>' . lang('no') . '</button>" rel="popover"><i class="fad fa-trash"></i> '
      . lang('delete_internal_use') . '</a></li>
          </ul>
        </div>
      </div>';

    $this->datatables->add_column('actions', $actions, 'id');

    echo $this->datatables->generate();
  }

  private function internal_uses_index()
  {
    $warehouse_id = XSession::get('warehouse_id');
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['warehouse_id'] = $warehouse_id;
    $this->data['warehouses']   = $this->site->getAllWarehouses();

    if ($warehouse_id) {
      $this->data['warehouse'] = $this->site->getWarehouseByID($warehouse_id);
    } else {
      $this->data['warehouse'] = NULL;
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('procurements')],
      ['link' => '#', 'page' => lang('internal_uses_list')]
    ];

    $meta = ['page_title' => lang('internal_uses_list'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/internal_uses/index', $this->data);
  }

  public function internal_uses_status($internal_use_id)
  {
    $this->iuse_mode = 'status';
    $this->internal_uses_edit($internal_use_id);
  }

  private function internal_uses_suggestions()
  {
    $term             = getGET('term', TRUE);
    $warehouseIdFrom  = getGET('from_warehouse_id', TRUE);
    $warehouseIdTo    = getGET('to_warehouse_id', TRUE);
    $category         = getGET('category', TRUE);

    if (strlen($term) < 1 || !$term) {
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
    }

    $opt = [];
    if ($category) {
      $opt['iuse_type'] = $category;
    }

    $rows = $this->site->getProductNames($term, 25, $opt);

    if ($rows) {
      $po_items = [];
      $r = 0;

      foreach ($rows as $row) {
        if ($row->active != 1) continue; // No inactive item.

        // Sync product quantity.
        Product::sync($row->id, $warehouseIdFrom);

        if ($warehouseIdFrom != $warehouseIdTo) {
          Product::sync($row->id, $warehouseIdTo);
        }

        $hash = generateUUID();
        $to_whp = $this->site->getWarehouseProduct($row->id, $warehouseIdTo);

        if (!$to_whp) continue;

        $destination_stock = $to_whp->quantity;
        $source_stock      = $this->site->getWarehouseProduct($row->id, $warehouseIdFrom)->quantity;
        $safe_stock        = getOrderStock($destination_stock, $row->min_order_qty, $to_whp->safety_stock);

        if ($safe_stock > $source_stock) $safe_stock = $source_stock; // If safe stock more then source stock.

        $row->cost            = filterDecimal($row->cost);
        $row->price           = filterDecimal($row->price);
        $row->markon_price    = filterDecimal($row->markon_price);
        $row->source_qty      = $source_stock; // Lucretia stock
        $row->destination_qty = $destination_stock; // Destination outlet stock.
        $row->min_order_qty   = $row->min_order_qty; // Min. order quantity.
        $row->safety_stock    = $to_whp->safety_stock; // Destination outlet safety stock.
        $row->quantity        = $safe_stock;
        $row->spec            = '';
        $row->base_unit       = $row->unit;
        $row->unit            = (!empty($row->purchase_unit) ? $row->purchase_unit : $row->unit);
        $row->machine_id      = 0;
        $row->unique_code     = '';
        $row->ucr             = '';
        $units                = $this->site->getUnitsByBUID($row->unit);

        if ($row->base_unit != $row->unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safe_stock, $unit));
            }
          }
        }

        $po_items[$hash] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => "({$row->code}) {$row->name}",
          'row'     => $row,
          'units'   => $units
        ];

        $r++;
      }

      if ($po_items) {
        sendJSON($po_items);
      } else {
        sendJSON([['id' => 0, 'label' => 'Item has no stock available.', 'value' => $term]]);
      }
    } else {
      sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
    }
  }

  public function internal_uses_sync()
  {
    if ($this->requestMethod == 'POST') {
      $ids = getPOST('val');

      if (is_array($ids) && !empty($ids)) {
        $count = 0;
        foreach ($ids as $iuseId) {
          $this->site->syncStockInternalUse($iuseId);
          $count++;
        }

        $this->response(200, ['message' => "{$count} internal use have been synced successfully."]);
      }

      $this->response(400, ['message' => 'Failed to sync.']);
    }
  }

  public function internal_uses_view($iuseId)
  {
    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $internal_use        = $this->site->getStockInternalUseByID($iuseId);

    $this->site->syncStockInternalUse($iuseId);

    $this->data['iuseItems']      = $this->site->getStockInternalUseItems($iuseId);
    $this->data['from_warehouse'] = $this->site->getWarehouseByID($internal_use->from_warehouse_id);
    $this->data['to_warehouse']   = $this->site->getWarehouseByID($internal_use->to_warehouse_id);
    $this->data['internal_use']   = $internal_use;
    $this->data['created_by']     = $this->site->getUser($internal_use->created_by);
    $this->data['updated_by']     = ($internal_use->updated_by ? $this->site->getUser($internal_use->updated_by) : NULL);
    $this->load->view($this->theme . 'procurements/internal_uses/view', $this->data);
  }

  /**
   * PURCHASES 2 (NEW)
   */
  public function purchases2()
  {
    checkPermission('purchases-index');

    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $meta = [
      'page_title' => lang('purchases'),
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => '#', 'page' => lang('purchases')]
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/purchases2/index', $this->data);
  }

  protected function purchases2_add()
  {
    checkPermission('purchases-add');

    if ($this->requestMethod == 'POST') {
      $createdAt   = getPOST('created_at');
      $createdBy   = getPOST('created_by');
      $warehouseId = getPOST('warehouse');
      $note        = getPOST('note');
      $products    = getPOST('product');

      $items = [];
      $productSize = count($products['id']);

      for ($a = 0; $a < $productSize; $a++) {
        $items[] = [
          'product_id' => $products['id'][$a],
          'price'      => $products['price'][$a],
          'quantity'   => $products['quantity'][$a],
          'status'     => 'pending'
        ];
      }

      $purchaseData = [
        'created_at'   => $createdAt,
        'created_by'   => $createdBy,
        'warehouse_id' => $warehouseId,
        'status'       => 'pending',
        'note'         => $note
      ];

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $purchaseData['attachment'] = $uploader->storeRandom();
      }

      if ($this->site->addPurchase($purchaseData, $items)) {
        $this->response(201, ['message' => 'Purchase berhasil dibuat.']);
      }
      $this->response(400, ['message' => 'Gagal membuat Purchase.']);
    }

    $this->load->view($this->theme . 'procurements/purchases2/add', $this->data);
  }

  protected function purchases2_getPurchases()
  {
    $startDate  = getGET('start_date');
    $endDate    = getGET('end_date');
    $billers    = getGET('biller');
    $warehouses = getGET('warehouse');

    $this->load->library('datatable');

    $this->datatable
      ->select("purchases.id AS id, purchases.id AS pid, purchases.attachment,
        purchases.reference, purchases.status AS status, purchases.payment_status AS payment_status,
        suppliers.company AS supplier_name, purchases.grand_total AS po_value,
        purchases.received_value AS received_value,
        purchases.paid AS paid, purchases.balance AS balance,
        purchases.received_date AS received_date,
        purchases.payment_date AS payment_date,
        purchases.due_date AS due_date,
        purchases.date AS created_at, creator.fullname AS creator_name,
        purchases.updated_at, updater.fullname AS updater_name")
      ->from('purchases')
      ->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left')
      ->join('users creator', 'creator.id = purchases.created_by', 'left')
      ->join('users updater', 'updater.id = purchases.updated_by', 'left');

    if ($startDate) {
      $this->datatable->where("purchases.date >= '{$startDate} 00:00:00'");
    }

    if ($endDate) {
      $this->datatable->where("purchases.date <= '{$endDate} 23:59:59'");
    }

    if ($billers) {
      $this->datatable->where_in('purchases.biller_id', $billers);
    }

    if ($warehouses) {
      $this->datatable->where_in('purchases.warehouse_id', $warehouses);
    }

    $this->datatable->editColumn('pid', function ($data) {
      return "
        <div class=\"text-center\">
          <a href=\"{$this->theme}procurements/purchases2/delete/{$data['id']}\"
            class=\"tip \"
            data-action=\"confirm\" style=\"color:red;\" title=\"Delete Purchase\">
              <i class=\"fad fa-fw fa-trash\"></i>
          </a>
          <a href=\"{$this->theme}procurements/purchases2/edit/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg\" title=\"Edit Purchase\">
              <i class=\"fad fa-fw fa-edit\"></i>
          </a>
          <a href=\"{$this->theme}procurements/purchases2/view/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg\"
            title=\"View Details\">
              <i class=\"fad fa-fw fa-chart-bar\"></i>
          </a>
          <a href=\"{$this->theme}procurements/purchases2/viewPayments/{$data['id']}\"
            class=\"tip\"
            data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
            data-modal-class=\"modal-lg\"
            title=\"View Payments\">
              <i class=\"fad fa-fw fa-money-bill-wave\"></i>
          </a>
        </div>";
    })
      ->editColumn('status', function ($data) {
        switch ($data['status']) {
          case 'need_approval':
            $type = 'danger';
            break;
          case 'approved':
            $type = 'success';
            break;
          case 'ordered':
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
        <a href=\"" . admin_url('procurements/purchases2/edit/' . $data['id'] . '?mode=status') . "\"
          class=\"label label-{$type} status\"
          data-toggle=\"modal\" data-target=\"#myModal\" data-modal-class=\"modal-lg\">{$status}
        </a>
      </div>
      ";
      })
      ->editColumn('payment_status', function ($data) {
        switch ($data['payment_status']) {
          case 'need_approval':
            $type = 'danger';
            break;
          case 'approved':
          case 'paid':
            $type = 'success';
            break;
          case 'paid_partial':
            $type = 'info';
            break;
          case 'pending':
          default:
            $type = 'warning';
        }

        $status = ucwords(str_replace('_', ' ', $data['payment_status']));

        return "
      <div class=\"text-center\">
        <a href=\"" . admin_url('procurements/purchases2/addPayment/' . $data['id']) . "\"
          class=\"label label-{$type} status\"
          data-toggle=\"modal\" data-target=\"#myModal\" data-modal-class=\"modal-lg\">{$status}
        </a>
      </div>
      ";
      });

    $this->datatable->generate();
  }

  /**
   * PURCHASES
   * @deprecated FUCK IT OFF!!!
   */
  public function purchases()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'warehouse' ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param warehouse.
      call_user_func_array([$this, $method], $params);
    }
  }

  public function purchases_index($warehouse_id = NULL)
  {
    $this->sma->checkPermissions('index', null, 'purchases');
    $this->lang->admin_load('purchases', $this->Settings->user_language);

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    if ($this->Owner || $this->Admin || !XSession::get('warehouse_id')) {
      $this->data['warehouses']   = $this->site->getAllWarehouses();
      $this->data['warehouse_id'] = $warehouse_id;
      $this->data['warehouse']    = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : null;
    } else {
      $this->data['warehouses']   = null;
      $this->data['warehouse_id'] = XSession::get('warehouse_id');
      $this->data['warehouse']    = XSession::get('warehouse_id') ? $this->site->getWarehouseByID(XSession::get('warehouse_id')) : null;
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('procurements')],
      ['link' => '#', 'page' => lang('purchases')]
    ];
    $meta = ['page_title' => lang('purchases'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/purchases/index', $this->data);
  }

  private function purchases_actions()
  { // purchases
    $form_action = getGET('form_action');
    $vals = getGET('val');

    if ($form_action == 'approve_send') {
      /*
      if ( empty($vals)) {
        $this->session->set_flashdata('error', 'Cannot approve and send email.');
        admin_redirect('procurements/purchases');
      }

      $antar_bank = []; $antar_rek = []; $row_ab = 1; $row_ar = 1;

      foreach ($vals as $purchase_id) {
        $payments    = $this->site->getStockPurchasePayments($purchase_id);
        $purchase    = $this->site->getStockPurchaseByID($purchase_id);
        $supplier    = $this->site->getSupplierByID($purchase->supplier_id);
        $supplier_js = ( ! empty($supplier->json_data) ? json_decode($supplier->json_data) : NULL);

        if ( ! $supplier_js) continue;

        if (stripos($supplier_js->acc_name, 'BNI') !== FALSE) { // acc_name == 'BNI'
          $antar_rek[] = [
            'no_id'         => '',
            'nama_penerima' => $supplier_js->acc_holder,
            'rek_debet'     => '465461984',
            'rek_penerima'  => filterDecimal($supplier_js->acc_no),
            'nominal'       => filterDecimal($payments[0]->amount),
            'keterangan'    => htmlRemove(htmlDecode($purchase->note))
          ];

          $row_ar++;
        } else {
          $antar_bank[] = [
            'no_referensi'       => 'IDP' . ($row_ab),
            'rek_debet'          => '465461984',
            'nama_pengirim'      => 'Anita Ratnasari',
            'residency_pengirim' => '1',
            'nominal'            => filterDecimal($payments[0]->amount),
            'pesan'              => htmlRemove(htmlDecode($purchase->note)),
            'bic'                => $supplier_js->acc_bic,
            'rek_penerima'       => filterDecimal($supplier_js->acc_no),
            'nama_penerima'      => $supplier_js->acc_holder,
            'jenis_penerima'     => '1',
            'residency_penerima' => '1',
            'bank_penerima'      => $supplier_js->acc_name
          ];

          $row_ab++;
        }
      }

      $date = date('Y-m-d H:i:s');

      if ($antar_bank) { // ANTAR BANK (BCA, MANDIRI, BRI)
        $excelAntarBank = $this->ridintek->spreadsheet();
        $excelAntarBank->setTitle(lang('purchases'));
        $excelAntarBank->SetCellValue('A1', 'No Referensi');
        $excelAntarBank->SetCellValue('B1', 'Rekening Debet');
        $excelAntarBank->SetCellValue('C1', 'Nama Pengirim');
        $excelAntarBank->SetCellValue('D1', 'Residency Pengirim');
        $excelAntarBank->SetCellValue('E1', 'Nominal Dikirim');
        $excelAntarBank->SetCellValue('F1', 'Pesan Pengirim');
        $excelAntarBank->SetCellValue('G1', 'Kode BIC');
        $excelAntarBank->SetCellValue('H1', 'Rekening Penerima');
        $excelAntarBank->SetCellValue('I1', 'Nama Penerima');
        $excelAntarBank->SetCellValue('J1', 'Jenis Nasabah Penerima');
        $excelAntarBank->SetCellValue('K1', 'Residency Penerima');
        $excelAntarBank->SetCellValue('L1', 'Nama Bank Penerima');

        $row = 2;
        foreach ($antar_bank as $data) {
          $excelAntarBank->SetCellValue('A' . $row, $data['no_referensi']);
          $excelAntarBank->SetCellValue('B' . $row, $data['rek_debet'], DataType::TYPE_STRING);
          $excelAntarBank->SetCellValue('C' . $row, $data['nama_pengirim']);
          $excelAntarBank->SetCellValue('D' . $row, $data['residency_pengirim']);
          $excelAntarBank->SetCellValue('E' . $row, $data['nominal']);
          $excelAntarBank->SetCellValue('F' . $row, $data['pesan']);
          $excelAntarBank->SetCellValue('G' . $row, $data['bic']);
          $excelAntarBank->SetCellValue('H' . $row, $data['rek_penerima'], DataType::TYPE_STRING);
          $excelAntarBank->SetCellValue('I' . $row, $data['nama_penerima']);
          $excelAntarBank->SetCellValue('J' . $row, $data['jenis_penerima']);
          $excelAntarBank->SetCellValue('K' . $row, $data['residency_penerima']);
          $excelAntarBank->SetCellValue('L' . $row, $data['bank_penerima']);

          $row++;
        }

        $excelAntarBank->setColumnAutoWidth('A');
        $excelAntarBank->setColumnAutoWidth('B');
        $excelAntarBank->setColumnAutoWidth('C');
        $excelAntarBank->setColumnAutoWidth('D');
        $excelAntarBank->setColumnAutoWidth('E');
        $excelAntarBank->setColumnAutoWidth('F');
        $excelAntarBank->setColumnAutoWidth('G');
        $excelAntarBank->setColumnAutoWidth('H');
        $excelAntarBank->setColumnAutoWidth('I');
        $excelAntarBank->setColumnAutoWidth('J');
        $excelAntarBank->setColumnAutoWidth('K');
        $excelAntarBank->setColumnAutoWidth('L');

        $excel_antar_bank = 'purchases-antar_bank-' . date('Y_m_d_H_i_s');
        $file_excel_antar_bank = FCPATH . 'files/procurements/purchases/' . $excel_antar_bank . '.xlsx';

        $excelAntarBank->save($file_excel_antar_bank);
      } else {
        $file_excel_antar_bank = NULL;
      }

      if ($antar_rek) { // ANTAR BNI / REKENING
        $excelAntarRek = $this->ridintek->spreadsheet();
        $excelAntarRek->setTitle(lang('purchases'));
        $excelAntarRek->SetCellValue('A1', 'NOPEG');
        $excelAntarRek->SetCellValue('B1', 'NAMAPEG');
        $excelAntarRek->SetCellValue('C1', 'NOREKDB');
        $excelAntarRek->SetCellValue('D1', 'NOREKKD');
        $excelAntarRek->SetCellValue('E1', 'JMLGAJI');
        $excelAntarRek->SetCellValue('F1', 'KETERANGAN1');
        $excelAntarRek->SetCellValue('G1', 'KETERANGAN2'); // Required for FUCKED BNI.
        $excelAntarRek->SetCellValue('H1', 'KETERANGAN3'); // Required for FUCKED BNI.

        $row = 2;
        foreach ($antar_rek as $data) {
          $excelAntarRek->SetCellValue('A' . $row, $data['no_id']);
          $excelAntarRek->SetCellValue('B' . $row, $data['nama_penerima']);
          $excelAntarRek->SetCellValue('C' . $row, $data['rek_debet'], DataType::TYPE_STRING);
          $excelAntarRek->SetCellValue('D' . $row, $data['rek_penerima'], DataType::TYPE_STRING);
          $excelAntarRek->SetCellValue('E' . $row, $data['nominal']);
          $excelAntarRek->SetCellValue('F' . $row, $data['keterangan']);

          $row++;
        }

        $excelAntarRek->setColumnAutoWidth('A');
        $excelAntarRek->setColumnAutoWidth('B');
        $excelAntarRek->setColumnAutoWidth('C');
        $excelAntarRek->setColumnAutoWidth('D');
        $excelAntarRek->setColumnAutoWidth('E');
        $excelAntarRek->setColumnAutoWidth('F');

        $excel_antar_rek = 'purchases-antar_rek-' . date('Y_m_d_H_i_s');
        $file_excel_antar_rek = FCPATH . 'files/procurements/purchases/' . $excel_antar_rek . '.xlsx';

        $excelAntarRek->save($file_excel_antar_rek);
      } else {
        $file_excel_antar_rek = NULL;
      }

      $attachments = [];
      if ($file_excel_antar_bank) $attachments[] = $file_excel_antar_bank;
      if ($file_excel_antar_rek)  $attachments[] = $file_excel_antar_rek;

      $msg = 'Dear ibu Sinta,<br><br>Berikut kami ajukan data pembayaran untuk segera ditransfer.';
      // TO: sinta.pramudyani@bni.co.id
      //$this->sma->send_email('sd@indoprinting.co.id', "Pembayaran {$date} - Indoprinting", $msg, null, null,
      //  $attachments, ['anita.ratnasari@indoprinting.co.id']);
      $this->sma->send_email('sd@indoprinting.co.id', "Pembayaran {$date} - Indoprinting", $msg, null, null,
        $attachments);

      $this->session->set_flashdata('message', 'Email has been sent successfully.');
      admin_redirect('procurements/purchases');*/
    } else if ($form_action == 'export_payments' || $form_action == 'send_payments') {
      if (empty($vals)) {
        $this->session->set_flashdata('error', 'Cannot export Purchase Order.');
        admin_redirect('procurements/purchases');
      }

      $antar_bank = [];
      $antar_rek = [];
      $row_ab = 1;
      $row_ar = 1;

      foreach ($vals as $purchase_id) {
        $payments = $this->site->getStockPurchasePayments($purchase_id);
        $purchase    = $this->site->getStockPurchaseByID($purchase_id);
        $supplier    = $this->site->getSupplierByID($purchase->supplier_id);
        $supplier_js = (!empty($supplier->json_data) ? json_decode($supplier->json_data) : NULL);

        if (!$supplier_js) continue;

        if (stripos($supplier_js->acc_name, 'BNI') !== FALSE) { // InHouse
          $antar_rek[] = [
            'rek_penerima'  => $supplier_js->acc_no,
            'nama_penerima' => $supplier_js->acc_holder,
            'nominal'       => filterDecimal($payments[0]->amount),
            'keterangan'    => htmlRemove(htmlDecode($purchase->note))
          ];

          $row_ar++;
        } else {
          $antar_bank[] = [
            'rek_penerima'  => $supplier_js->acc_no,
            'nama_penerima' => $supplier_js->acc_holder,
            'nominal'       => filterDecimal($payments[0]->amount),
            'pesan'         => htmlRemove(htmlDecode($purchase->note)),
            'pesan2'        => '',
            'bic'           => $supplier_js->acc_bic,
            'bank_penerima' => $supplier_js->acc_name
          ];

          $row_ab++;
        }
      }

      $excel = $this->ridintek->spreadsheet();
      $excel->setTitle('Purchases Kliring');
      $excel->createSheet();
      $excel->setTitle('Purchases InHouse');

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

      $excel_name = 'Purchases-' . date('Y_m_d_H_i_s');
      $excel->export($excel_name);
    }
  }

  private function purchases_add()
  { // purchases
    $this->form_validation->set_rules('supplier', $this->lang->line('supplier'), 'required');
    $this->form_validation->set_rules('warehouse', $this->lang->line('warehouse'), 'required');

    if ($this->form_validation->run() == true) {
      $date         = $this->serverDateTime;
      $status       = getPOST('status');
      $biller_id    = getPOST('biller');
      $category_id  = getPOST('category');
      $warehouse_id = getPOST('warehouse');
      $supplier_id  = getPOST('supplier');
      $supplier     = $this->site->getSupplierByID($supplier_id);
      $note         = htmlEncode(getPOST('note'));
      $payment_term = (getPOST('payment_term') > 0 ? getPOST('payment_term') : ($supplier->payment_term > 0 ? $supplier->payment_term : NULL) ?? 1);
      $total        = 0;
      $i            = count($_POST['product_id'] ?? []); // If not set product, use empty array.
      $purchase_items = [];

      for ($r = 0; $r < $i; $r++) {
        $itemCode         = $_POST['product'][$r];
        $itemCost         = round(filterDecimal($_POST['cost'][$r]));
        $itemPurchasedQty = $_POST['purchased_qty'][$r];
        $itemSpec         = $_POST['spec'][$r];
        $itemUnit         = $_POST['item_unit'][$r]; // lbr, rim

        if (isset($itemCode) && isset($itemCost) && isset($itemPurchasedQty)) {
          $product = $this->site->getProductByCode($itemCode);

          $purchase_items[] = [
            'date'              => $date,
            'product_id'        => $product->id,
            'cost'              => $itemCost,
            'quantity'          => 0,
            'purchased_qty'     => $itemPurchasedQty,
            'warehouse_id'      => $warehouse_id,
            'status'            => $status,
            'unit_id'           => $itemUnit,
            'spec'              => $itemSpec,
            'created_by'        => XSession::get('user_id')
          ];

          $total += round($itemCost * $itemPurchasedQty);
        }
      }

      if (empty($purchase_items)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $purchase_data = [
        'date'           => $date,
        'biller_id'      => $biller_id,
        'category_id'    => $category_id,
        'warehouse_id'   => $warehouse_id,
        'note'           => $note,
        'grand_total'    => $total,
        'created_by'     => XSession::get('user_id'),
        'payment_status' => 'pending', // Payment must pending after add stock purchase.
        'status'         => $status,
        'payment_term'   => $payment_term,
        'supplier_id'    => $supplier_id,
      ];
    }

    if ($this->form_validation->run() == true) {
      //rd_print('purchase_data:', $purchase_data, 'products:', $products); die();
      if ($this->site->addStockPurchase($purchase_data, $purchase_items)) {
        $this->session->set_userdata('remove_pols', 1);
        $this->session->set_flashdata('message', $this->lang->line('purchase_added'));
        admin_redirect('procurements/purchases');
      }
      admin_redirect('procurements/purchases/add');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['quote_id']   = NULL;
      $this->data['suppliers']  = $this->site->getAllSuppliers();
      $this->data['categories'] = $this->site->getCategories();
      $this->data['expenseCategories'] = $this->site->getExpenseCategories(['order' => ['name', 'ASC']]);
      $this->data['warehouses'] = $this->site->getAllWarehouses();
      $this->data['ponumber']   = ''; //$this->site->getReference('po');
      $this->load->helper('string');
      $value = random_string('alnum', 20);
      $this->session->set_userdata('user_csrf', $value);
      $this->data['csrf'] = XSession::get('user_csrf');
      $bc                 = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/purchases'), 'page' => lang('purchases')],
        ['link' => '#', 'page' => lang('add_purchase')]
      ];
      $meta = ['page_title' => lang('add_purchase'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/purchases/add', $this->data);
    }
  }

  private function purchases_addPurchasesFromPlan()
  {
    $supplier_ids = getPOST('supplier_ids');

    if ($supplier_ids) {
      $suppl_ids = explode(',', $supplier_ids);
      foreach ($suppl_ids as $supplier_id) {
        // Add stock purchase by each supplier id.
        $this->site->addStockPurchaseBySupplierID($supplier_id);
      }

      sendJSON(['error' => 0, 'msg' => 'Purchases have beed added successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to add purchases.']);
  }

  private function purchases_add_payment($purchase_id)
  { // purchases
    if (getGET('id')) {
      $purchase_id = getGET('id');
    }

    $purchase = $this->site->getStockPurchaseByID($purchase_id);
    $payments = $this->site->getStockPurchasePayments($purchase_id);

    if (!$purchase) {
      $this->session->set_flashdata('error', 'Purchase tidak ditemukan.');
      $this->sma->md();
    }

    if ($payments != NULL) {
      foreach ($payments as $pym) {
        if ($pym->status == 'need_approval') {
          $this->session->set_flashdata('error', lang('payment_need_approval'));
          $this->sma->md();
        }
        if ($pym->status == 'approved') {
          $this->session->set_flashdata('error', lang('paid_approved_payment'));
          $this->sma->md();
        }
      }
    }

    if ($purchase->payment_status == 'paid' && $purchase->grand_total == $purchase->paid) {
      $this->session->set_flashdata('error', lang('purchase_already_paid'));
      $this->sma->md();
    }
    if ($purchase->status == 'need_approval') {
      $this->session->set_flashdata('error', lang('purchase_need_approval'));
      $this->sma->md();
    }

    $this->form_validation->set_rules('amount-paid', lang('amount'), 'required');
    $this->form_validation->set_rules('paid_by', lang('paid_by'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      $date = $this->serverDateTime;
      $amount = getPOST('amount-paid');
      $bank = $this->site->getBankById(getPOST('paid_by'));
      $discount = (getPOST('discount') == 1 ? TRUE : FALSE);
      $discount_amount = 0;

      $payment = [
        'date'            => $date,
        'purchase_id'     => getPOST('purchase_id'),
        'reference'       => $purchase->reference,
        'bank_id'         => $bank->id,
        'method'          => $bank->type,
        'amount'          => round(filterDecimal($amount)),
        'created_by'      => XSession::get('user_id'),
        'status'          => 'need_approval',
        'type'            => 'pending', // will be received if paid, after approved.
        'note'            => htmlEncode(getPOST('note'))
      ];

      if ($discount) {
        // Ex. -200,000 + 180,000 = -20,000
        $discount_amount = $purchase->balance + round(filterDecimal($amount));
        $payment['discount_amount'] = $discount_amount;
      }

      if (floatval($purchase->grand_total) < floatval($payment['amount'])) {
        $this->session->set_flashdata('error', lang('paid_over_grandtotal'));
        $this->sma->md();
      }

      $uploader = new FileUpload();

      if ($uploader->has('payment_proof')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $payment['attachment'] = $uploader->storeRandom();
      }
    } elseif (getPOST('add_payment')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->addStockPurchasePayment($purchase->id, $payment)) { // New payment method.
        $this->session->set_flashdata('message', lang('payment_added'));
      } else {
        $this->session->set_flashdata('error', lang('payment_add_failed'));
      }
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      if (getPOST('add_payment')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER']);
      }
      $banks = $this->site->getBanks(['type' => ['Cash', 'EDC', 'INV', 'Transfer']]);
      for ($a = 0; $a < count($banks); $a++) {
        $banks[$a]->balance = $banks[$a]->amount;
        //$banks[$a]->balance = $this->site->getBankBalanceByID($banks[$a]->id);
      }
      $this->data['error']          = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['banks']          = $banks;
      $this->data['purchase']       = $purchase;
      $this->load->view($this->theme . 'procurements/purchases/add_payment', $this->data);
    }
  }

  private function purchases_delete($purchase_id)
  {
    if ($this->site->deleteStockPurchase($purchase_id)) {
      sendJSON(['error' => 0, 'msg' => lang('purchase_deleted')]);
    } else {
      sendJSON(['error' => 1, 'msg' => lang('purchase_delete_fail')]);
    }
  }

  private function purchases_delete_payment($payment_id)
  {
    $payment = $this->site->getPaymentByID($payment_id);
    if ($payment) {
      $purchase_id = $payment->purchase_id;
      if ($this->site->deletePayments(['id' => $payment_id])) {
        $payments = $this->site->getStockPurchasePayments($purchase_id);
        if (!$payments) {
          $this->site->updateStockPurchase($purchase_id, ['payment_status' => 'pending', 'paid' => 0]);
        }
        $this->session->set_flashdata('message', 'Payment deleted successfully.');
        admin_redirect('procurements/purchases');
      }
    }
    $this->session->set_flashdata('error', 'Delete payment failed.');
    admin_redirect('procurements/purchases');
  }

  private function purchases_edit($purchase_id)
  { // purchases
    if (getGET('id')) {
      $purchase_id = getGET('id');
    }

    $mode = $this->po_mode;

    $this->site->syncStockPurchase($purchase_id);

    $purchase = $this->site->getStockPurchaseByID($purchase_id);

    if (!$purchase) {
      $this->session->set_flashdata('error', 'Purchase tidak ditemukan.');
      redirect($_SERVER['HTTP_REFERER'] ?? admin_url('procurements/purchases'));
    }

    if (!XSession::get('edit_right')) {
      $this->sma->view_rights($purchase->created_by);
    }

    $this->form_validation->set_rules('reference', $this->lang->line('ref_no'), 'required');
    $this->form_validation->set_rules('supplier', $this->lang->line('supplier'), 'required');
    $this->form_validation->set_rules('warehouse', $this->lang->line('warehouse'), 'required');

    if ($this->form_validation->run() == true) {
      $date           = getPOST('date');
      $postatus       = getPOST('status');
      $biller_id      = getPOST('biller');
      $category_id    = getPOST('category');
      $warehouse_id   = getPOST('warehouse');
      $supplier_id    = getPOST('supplier');
      $supplier       = $this->site->getSupplierByID($supplier_id);
      $note           = htmlEncode(getPOST('note'));
      $payment_term   = ($supplier->payment_term > 0 ? $supplier->payment_term : 1);
      $due_date       = ($payment_term ? date('Y-m-d H:i:s', strtotime('+' . $payment_term . ' days', strtotime($date))) : NULL);
      $discount       = filterDecimal(getPOST('discount'));
      $total          = 0;
      $balance        = 0;
      $i              = count($_POST['product_id'] ?? []); // If not set product, use empty array.
      $is_partial     = FALSE;
      $received_date  = NULL;

      if ($postatus == 'received') {
        $received_date = $date; // date('Y-m-d H:i:s');
      }

      for ($r = 0; $r < $i; $r++) {
        $itemCode           = $_POST['product'][$r];
        $itemCost           = $_POST['cost'][$r];
        $itemPurchasedQty  = $_POST['purchased_qty'][$r];
        $item_received_qty_1 = filterDecimal($_POST['received_qty_1'][$r] ?? 0);
        $item_received_qty_2 = filterDecimal($_POST['received_qty_2'][$r] ?? 0);
        $item_received_qty_3 = filterDecimal($_POST['received_qty_3'][$r] ?? 0);
        $received_date_1     = $_POST['received_date_1'][$r];
        $received_date_2     = $_POST['received_date_2'][$r];
        $received_date_3     = $_POST['received_date_3'][$r];
        $item_quantity       = $_POST['quantity'][$r];
        $itemSpec           = $_POST['spec'][$r];
        $itemUnit           = $_POST['item_unit'][$r];

        if (isset($itemCode) && isset($itemCost) && isset($itemPurchasedQty)) {
          $product = $this->site->getProductByCode($itemCode);
          $item_total_received_qty = 0;

          if ($postatus == 'received') {
            $this->site->updateProducts([[
              'product_id'  => $product->id,
              'order_date'  => $date,
              'order_price' => $itemCost,
              'sn' => toSN($itemCode, $purchase->reference)
            ]]);

            // Received Qty Selector based on received date.
            if (empty($received_date_1)) {
              $received_date_1 = $received_date;
            } else if (empty($received_date_2)) {
              $received_date_2 = $received_date;
            } else if (empty($received_date_3)) {
              $received_date_3 = $received_date;
            }

            $item_total_received_qty = ($item_received_qty_1 + $item_received_qty_2 + $item_received_qty_3);

            if ($item_total_received_qty < $itemPurchasedQty) {
              $is_partial = TRUE;
            }
            $balance += round($itemCost * $item_total_received_qty);
          }

          $product_data = [
            'date'              => $date,
            'product_id'        => $product->id,
            'product_code'      => $product->code,
            'cost'              => $itemCost,
            'purchased_qty'     => $itemPurchasedQty,
            'quantity'          => ($postatus == 'received' ? $item_total_received_qty : 0), // For edit only.
            'warehouse_id'      => $warehouse_id,
            'status'            => $postatus,
            'unit_id'           => $itemUnit,
            'spec'              => $itemSpec,
            'json_data'         => json_encode([
              'received_qty_1' => $item_received_qty_1,
              'received_qty_2' => $item_received_qty_2,
              'received_qty_3' => $item_received_qty_3,
              'received_date_1' => $received_date_1,
              'received_date_2' => $received_date_2,
              'received_date_3' => $received_date_3
            ])
          ];

          // if ($this->Owner) d($product_data);

          $products[] = $product_data;
          $total += round($itemCost * $itemPurchasedQty);
        }
      }

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $grand_total = $total;

      $paid = 0;
      $payments = $this->site->getStockPurchasePayments($purchase_id);
      if ($payments) {
        foreach ($payments as $payment) {
          if ($payment->status == 'paid') $paid += $payment->amount;
        }
      }

      $purchase_data = [
        'date'           => $date,
        'biller_id'      => $biller_id,
        'category_id'    => $category_id,
        'warehouse_id'   => $warehouse_id,
        'note'           => $note,
        'grand_total'    => $grand_total,
        'discount'       => ($discount * -1), // Convert to minus.
        'balance'        => $paid - $balance,
        'created_by'     => $purchase->created_by,
        /*'payment_status' => 'pending', // NOT USED IN EDIT */
        'status'         => ($is_partial ? 'received_partial' : $postatus),
        'payment_term'   => $payment_term,
        'received_date'  => $received_date,
        'received_value' => $balance,
        'supplier_id'    => $supplier_id,
      ];

      if ($postatus == 'received') { // Show due date if received.
        $purchase_data['due_date'] = $due_date;
      }

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $purchase_data['attachment'] = $uploader->storeRandom();
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->updateStockPurchase($purchase_id, $purchase_data, $products)) {
        $this->session->set_flashdata('message', $this->lang->line('purchase_status_updated'));
        admin_redirect('procurements/purchases');
      }
      admin_redirect("procurements/purchases/{$this->po_mode}/" . $purchase_id);
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['purchase'] = $purchase;

      $po_items = [];
      $purchase_items = $this->site->getStockPurchaseItems($purchase_id);

      foreach ($purchase_items as $item) {
        $row = $this->site->getProductByID($item->product_id);
        $wh_product = $this->site->getWarehouseProduct($row->id, $purchase->warehouse_id);
        $current_stock = ($wh_product ? $wh_product->quantity : 0);

        if (!$row) {
          $row = new stdClass();
        } else {
          unset($row->details, $row->product_details, $row->image, $row->barcode_symbology, $row->cf1, $row->cf2, $row->cf3, $row->cf4, $row->cf5, $row->cf6, $row->supplier1price, $row->supplier2price, $row->cfsupplier3price, $row->supplier4price, $row->supplier5price, $row->supplier1, $row->supplier2, $row->supplier3, $row->supplier4, $row->supplier5, $row->supplier1_part_no, $row->supplier2_part_no, $row->supplier3_part_no, $row->supplier4_part_no, $row->supplier5_part_no);
        }

        $json_data = json_decode($item->json_data);

        $row->current_stock   = $current_stock;
        $row->cost            = $item->cost; // ->price.
        $row->purchased_qty   = ($item->purchased_qty ?? 0);
        $row->rest_qty        = $item->purchased_qty - $item->quantity; // 1 - 500
        $row->received_qty_1  = (isset($json_data->received_qty_1) ? $json_data->received_qty_1 : 0);
        $row->received_qty_2  = (isset($json_data->received_qty_2) ? $json_data->received_qty_2 : 0);
        $row->received_qty_3  = (isset($json_data->received_qty_3) ? $json_data->received_qty_3 : 0);
        $row->received_date_1 = (isset($json_data->received_date_1) ? $json_data->received_date_1 : '');
        $row->received_date_2 = (isset($json_data->received_date_2) ? $json_data->received_date_2 : '');
        $row->received_date_3 = (isset($json_data->received_date_3) ? $json_data->received_date_3 : '');
        $row->quantity        = $item->quantity;
        $row->spec            = ($item->spec ?? '');
        $row->base_unit       = $row->unit;
        $row->unit            = $item->unit_id;
        $row->min_order_qty   = $row->min_order_qty ?? 1;

        $units = $this->site->getUnitsByBUID($row->base_unit);

        if ($row->base_unit != $row->unit) {
          foreach ($units as $unit) {
            if ($unit->id == $row->unit) {
              $row->rest_qty = $row->purchased_qty - baseToUnitQty($row->quantity, $unit);
            }
          }
        }

        $hash = bin2hex(random_bytes(8));

        $po_items[$hash] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => $row->name . ' (' . $row->code . ')',
          'row'     => $row,
          'units'   => $units
        ];
      }

      $this->data['mode']           = $mode;
      $this->data['purchase_items'] = json_encode($po_items);
      $this->data['id']             = $purchase_id;
      $this->data['suppliers']      = $this->site->getAllSuppliers();
      $this->data['supplier_id']    = $purchase->supplier_id;
      $this->data['purchase']       = $this->site->getStockPurchaseByID($purchase_id);
      $this->data['categories']     = $this->site->getCategories();
      $this->data['warehouses']     = $this->site->getAllWarehouses();

      $bc = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/purchases'), 'page' => lang('purchases_list')],
        ['link' => '#', 'page' => ($mode == 'status' ? lang("purchase_{$mode}") : lang("{$mode}_purchase"))]
      ];

      $meta = ['page_title' => ($mode == 'status' ? lang("purchase_{$mode}") : lang("{$mode}_purchase")), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct("procurements/purchases/{$mode}", $this->data);
    }
  }

  private function purchases_edit_cost()
  {
    $product_id = getPOST('product_id');
    $cost       = getPOST('cost');

    if ($product = $this->site->getProductByID($product_id)) {
      $markOnPrice = getMarkonPrice($cost, $product->markon);

      if ($this->site->updateProducts([[
        'product_id' => $product_id, 'cost' => $cost, 'markon' => $markOnPrice
      ]])) {
        sendJSON(['error' => 0, 'msg' => 'Cost updated.']);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to update cost.']);
  }

  private function purchases_edit_payment($payment_id)
  {
    $payment = $this->site->getPaymentByID($payment_id);
    $purchase = $this->site->getStockPurchaseByID($payment->purchase_id);

    if (!$purchase) {
      $this->session->set_flashdata('error', 'Cannot find stock purchase id.');
      $this->sma->md();
    }

    // if ($payment->status == 'approved') {
    //   $this->session->set_flashdata('error', lang('paid_approved_payment'));
    //   $this->sma->md();
    // }

    $this->form_validation->set_rules('old_amount', lang('old_amount'), 'required');
    $this->form_validation->set_rules('new_amount', lang('new_amount'), 'required');
    $this->form_validation->set_rules('bank_id', lang('paid_by'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');

    $old_amount = round(filterDecimal(getPOST('old_amount')));
    $new_amount = round(filterDecimal(getPOST('new_amount')));

    if ($this->form_validation->run() == true) {
      $date = $this->sma->fld(trim(getPOST('date'))) . date(':s');
      $bank = $this->site->getBankById(getPOST('bank_id'));
      $data_payment = [
        'date'      => $date,
        'reference' => $payment->reference,
        'bank_id'   => getPOST('bank_id'),
        'method'    => $bank->type,
        'amount'    => $new_amount,
        'note'      => htmlEncode(getPOST('note'))
      ];

      $bank_balance = $this->site->getBankBalanceByID(getPOST('bank_id'));

      if (floatval($purchase->grand_total) < floatval($data_payment['amount'])) {
        $this->session->set_flashdata('error', lang('paid_over_grandtotal'));
        $this->sma->md();
      }

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $data_payment['attachment'] = $uploader->storeRandom();
      }
    } elseif (getPOST('edit_payment')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
    if ($this->form_validation->run() == true && $this->site->updatePayment($payment_id, $data_payment)) {
      // $this->procurements_model->addPurchaseHistory([
      //   'reference'         => $purchase->reference,
      //   'payment_reference' => $data_payment['reference'],
      //   'description'       => "Payment has been edited, from amount <b>{$this->sma->formatMoney($old_amount)}</b> to <b>{$this->sma->formatMoney($new_amount)}</b>, paid by <b>{$bank->name}</b>.",
      //   'user_id'           => XSession::get('user_id')
      // ]);
      $this->session->set_flashdata('message', lang('payment_added'));
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      if (getPOST('edit_payment')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER']);
      }
      $banks = $this->site->getBanksByType(['transfer', 'edc']);
      for ($a = 0; $a < count($banks); $a++) {
        $banks[$a]->balance = $this->site->getBankBalanceByID($banks[$a]->id);
      }
      $this->data['error']          = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['banks']          = $banks;
      $this->data['payment']        = $payment;
      $this->data['purchase']       = $purchase;
      $this->load->view($this->theme . 'procurements/purchases/edit_payment', $this->data);
    }
  }

  private function purchases_getHistories($purchase_id)
  { // purchases
    $purchase = $this->site->getStockPurchaseById($purchase_id);
    $this->load->library('datatables');
    $this->datatables->select("date, reference, description,
      (SELECT fullname FROM users) WHERE users.id = purchase_histories.user_id) as user")
      ->from('purchase_histories')
      ->like('reference', $purchase->reference);
    echo $this->datatables->generate();
  }

  private function purchases_getPurchases()
  { // purchases
    $reference          = getGET('reference');
    $supplier_name      = getGET('supplier'); // Supplier Name
    $warehouse          = getGET('warehouse');
    $status             = getGET('status');
    $payment_status     = getGET('payment_status');
    $start_date         = getGET('start_date');
    $end_date           = getGET('end_date');
    $start_payment_date = getGET('start_payment_date');
    $end_payment_date   = getGET('end_payment_date');
    $xls                = getGET('xls');

    $detail_link           = anchor('admin/procurements/purchases/view/$1', '<i class="fad fa-fw fa-file"></i> ' . lang('purchase_details'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg no-modal-header" data-target="#myModal"');
    $add_payment_link      = anchor('admin/procurements/purchases/add_payment/$1', '<i class="fad fa-fw fa-money-bill-wave"></i> ' . lang('add_payment'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $payments_link         = anchor('admin/procurements/purchases/payments/$1', '<i class="fad fa-fw fa-money-bill-alt"></i> ' . lang('view_payments'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg" data-target="#myModal"');
    $email_link            = anchor('admin/procurements/purchases/email/$1', '<i class="fad fa-fw fa-envelope"></i> ' . lang('email_purchase'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $edit_link             = anchor('admin/procurements/purchases/edit/$1', '<i class="fad fa-fw fa-edit"></i> ' . lang('edit_purchase'));
    $pdf_link              = anchor('admin/procurements/purchases/pdf/$1', '<i class="fad fa-fw fa-file-pdf"></i> ' . lang('download_pdf'));
    $print_barcode         = anchor('admin/products/print_barcodes/?purchase=$1', '<i class="fad fa-fw fa-print"></i> ' . lang('print_barcodes'));
    $print_purchase        = anchor('admin/procurements/purchases/modal_view/$1', '<i class="fad fa-fw fa-print"></i> ' . lang('print_purchase'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $receive_link          = anchor('admin/procurements/purchases/receive/$1', '<i class="fad fa-fw fa-print"></i> ' . lang('receive_items'), 'data-toggle="modal" data-backdrop="false" data-target="#myModal"');
    $return_link           = anchor('admin/procurements/purchases/return_purchase/$1', '<i class="fad fa-fw fa-angle-double-left"></i> ' . lang('return_purchase'));
    $delete_link           = "<a href='#' class='po' title='<b>" . $this->lang->line('delete_purchase') . "</b>' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('procurements/purchases/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-fw fa-trash\"></i> "
      . lang('delete_purchase') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
      <ul class="dropdown-menu pull-right" role="menu">
        <li>' . $detail_link . '</li>
        <li>' . $add_payment_link . '</li>
        <li>' . $edit_link . '</li>
        <li>' . $print_purchase . '</li>
        <li>' . $payments_link . '</li>
        <li class="divider"></li>
        <li>' . $delete_link . '</li>
      </ul>
      </div></div>';

    if (!$xls) {
      $this->load->library('datatables');

      $this->datatables
        ->select("purchases.id AS id,
          purchases.date AS date,
          purchases.reference AS reference,
          suppliers.company AS supplier_company,
          purchases.grand_total AS grand_total,
          purchases.status AS status,
          purchases.received_date AS received_date,
          purchases.received_value AS received_value,
          purchases.due_date AS due_date,
          purchases.attachment AS attachment,
          purchases.payment_status AS payment_status,
          purchases.payment_date AS payment_date,
          purchases.paid AS paid,
          purchases.balance AS balance")
        ->from('purchases');

      /* JOIN TABLES */
      $this->datatables
        ->join('suppliers', 'suppliers.id = purchases.supplier_id', 'left');

      /* REFERENCE */
      if ($reference) {
        $this->datatables->like("purchases.reference", $reference, 'both');
      }

      /* SUPPLIER */
      if ($supplier_name) {
        $this->datatables->like("purchases.supplier_company", $supplier_name, 'both');
      }

      /* WAREHOUSE */
      if ($warehouse) {
        $this->datatables->group_start();
        foreach ($warehouse as $wh) {
          $this->datatables->or_where('warehouse_id', $wh);
        }
        $this->datatables->group_end();
      }

      /* PURCHASE STATUS */
      if ($status) {
        $this->datatables->group_start();
        foreach ($status as $st) {
          $this->datatables->or_like("purchases.status", $st, 'none');
        }
        $this->datatables->group_end();
      }

      /* PURCHASE PAYMENT STATUS */
      if ($payment_status) {
        $this->datatables->group_start();
        foreach ($payment_status as $pst) {
          $this->datatables->or_like("purchases.payment_status", $pst, 'none');
        }
        $this->datatables->group_end();
      }

      /* START DATE & END DATE */
      if ($start_date && $end_date) {
        $this->datatables->where("date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      } else if ($start_date) {
        $this->datatables->where("date >= '{$start_date} 00:00:00'");
      } else if ($end_date) {
        $this->datatables->where("date <= '{$end_date} 23:59:59'");
      }

      /* START PAYMENT DATE & END PAYMENT DATE */
      if ($start_payment_date && $end_payment_date) {
        $this->datatables->where("payment_date BETWEEN '{$start_payment_date} 00:00:00' AND '{$end_payment_date} 23:59:59'");
      } else if ($start_payment_date) {
        $this->datatables->where("payment_date >= '{$start_payment_date} 00:00:00'");
      } else if ($end_payment_date) {
        $this->datatables->where("payment_date <= '{$end_payment_date} 23:59:59'");
      }

      $this->datatables->group_by('purchases.id');

      $this->datatables->add_column('Actions', $action, 'id');
      echo $this->datatables->generate();
    } else if ($xls) {
      $purchase_data = [];

      $this->db->select("date, reference, supplier_name, grand_total, status, received_date,
        received_value, due_date, payment_status, payment_date, warehouse_id, warehouse_name, note, paid, balance")
        ->from('purchases');

      if ($reference) {
        $this->db->like('reference', $reference, 'both');
      }

      if ($supplier_name) {
        $this->db->like("purchases.supplier_name", $supplier_name, 'both');
      }

      if ($warehouse) {
        $this->db->group_start();
        foreach ($warehouse as $whf) {
          $this->db->or_where('warehouse_id', $whf);
        }
        $this->db->group_end();
      }

      if ($status) {
        $this->db->group_start();
        foreach ($status as $st) {
          $this->db->or_like('status', $st, 'both');
        }
        $this->db->group_end();
      }

      if ($payment_status) {
        $this->db->group_start();
        foreach ($payment_status as $pst) {
          $this->db->or_like('payment_status', $pst, 'both');
        }
        $this->db->group_end();
      }

      if ($start_date && $end_date) {
        $this->db->where("date BETWEEN '{$start_date}' AND '{$end_date}'");
      } else if ($start_date) {
        $this->db->where("date >= '{$start_date}'");
      } else if ($end_date) {
        $this->db->where("date <= '{$end_date}'");
      }

      if ($start_payment_date && $end_payment_date) {
        $this->db->where("payment_date BETWEEN '{$start_payment_date}' AND '{$end_payment_date}'");
      } else if ($start_payment_date) {
        $this->db->where("payment_date >= '{$start_payment_date}'");
      } else if ($end_payment_date) {
        $this->db->where("payment_date <= '{$end_payment_date}'");
      }
      if (!$this->Owner && !$this->Admin && !XSession::get('view_right')) {
        $this->db->where('created_by', XSession::get('user_id'));
      }

      $q = $this->db->get();
      if ($q->num_rows() > 0) {
        foreach ($q->result() as $row) {
          $purchase_data[] = $row;
        }
      }

      if ($purchase_data) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle('Stock Transfers');
        $excel->setCellValue('A1', 'PO Date');
        $excel->setCellValue('B1', 'PO Number');
        $excel->setCellValue('C1', 'Supplier');
        $excel->setCellValue('D1', 'PO Value');
        $excel->setCellValue('E1', 'Status');
        $excel->setCellValue('F1', 'First Received Date');
        $excel->setCellValue('G1', 'Received Value');
        $excel->setCellValue('H1', 'Due Date');
        $excel->setCellValue('I1', 'Payment Status');
        $excel->setCellValue('J1', 'Payment Date');
        $excel->setCellValue('K1', 'Paid');
        $excel->setCellValue('L1', 'Balance');
        $excel->setCellValue('M1', 'Note');
        $excel->setBold('A1:M1');
        $excel->setFillColor('A1:M1', 'FFFF00');
        $excel->setHorizontalAlign('A1:L1', 'center');

        $x = 2;
        foreach ($purchase_data as $purchase) {
          $excel->setCellValue("A{$x}", $purchase->date);
          $excel->setCellValue("B{$x}", $purchase->reference);
          $excel->setCellValue("C{$x}", $purchase->supplier_name);
          $excel->setCellValue("D{$x}", $purchase->grand_total);
          $excel->setCellValue("E{$x}", lang($purchase->status));
          $excel->setCellValue("F{$x}", $purchase->received_date);
          $excel->setCellValue("G{$x}", $purchase->received_value);
          $excel->setCellValue("H{$x}", $purchase->due_date);
          $excel->setCellValue("I{$x}", lang($purchase->payment_status));
          $excel->setCellValue("J{$x}", $purchase->payment_date);
          $excel->setCellValue("K{$x}", $purchase->paid);
          $excel->setCellValue("L{$x}", $purchase->balance);
          $excel->setCellValue("M{$x}", htmlRemove($purchase->note));

          $x++;
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

        $excel->export('PrintERP - StockPurchasesList-' . date('Ymd_His'));
      }
    }
  }

  private function purchases_getPurchasesPlan()
  {
    $today = getDayName(date('w') + 1); // Get today name. Ex. senin, selasa, ...
    $week = getCurrentWeekOfMonth();

    $this->load->library('datatable');
    $this->datatable
      ->select("suppliers.id AS id, suppliers.company AS company_name,
        JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_days')) AS visit_days,
        JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_weeks')) AS visit_weeks,
        suppliers.id AS supplier_id")
      ->from('suppliers')
      ->like("LOWER(JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_days')))", $today, 'both');
    // ->like("LOWER(JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.visit_weeks')))", $week, 'both');

    echo $this->datatable->generate();
  }

  private function purchases_history($purchase_id)
  { // purchases
    if (getGET('id')) {
      $purchase_id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $purchase = $this->site->getStockPurchaseByID($purchase_id);
    $this->data['logo']     = TRUE;
    $this->data['purchase'] = $purchase;
    $this->load->view($this->theme . 'procurements/purchases/history', $this->data);
  }

  private function purchases_modal_view($purchase_id)
  { // purchases
    if (getGET('id')) {
      $purchase_id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $inv                 = $this->site->getStockPurchaseByID($purchase_id);
    if (!XSession::get('view_right')) {
      $this->sma->view_rights($inv->created_by, true);
    }
    $this->data['logo']            = TRUE;
    $this->data['rows']            = $this->site->getStockPurchaseItems($purchase_id);
    $this->data['supplier']        = $this->site->getSupplierByID($inv->supplier_id);
    $this->data['warehouse']       = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv']             = $inv;
    $this->data['payments']        = $this->site->getStockPurchasePayments($purchase_id);
    $this->data['created_by']      = $this->site->getUser($inv->created_by);
    $this->data['updated_by']      = $inv->updated_by ? $this->site->getUser($inv->updated_by) : null;
    $this->load->view($this->theme . 'procurements/purchases/modal_view', $this->data);
  }

  private function purchases_payments($purchase_id)
  { // purchases
    $id = $purchase_id;
    $this->data['payments'] = $this->site->getStockPurchasePayments($id);
    $this->data['inv']      = $this->site->getStockPurchaseByID($id);
    $this->site->syncStockPurchase($purchase_id);
    $this->load->view($this->theme . 'procurements/purchases/payments', $this->data);
  }

  private function purchases_plan()
  {
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Procurements'],
      ['link' => admin_url('purchases'), 'page' => 'Purchases'],
      ['link' => '#', 'page' => 'Purchases Plan']
    ];
    $meta = ['page_title' => 'Purchases Plan', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/purchases/plan', $this->data);
  }

  private function purchases_update_payment_status($payment_id)
  { // purchases
    $this->form_validation->set_rules('status', lang('status'), 'required');
    $payment      = $this->site->getPaymentById($payment_id);
    $purchase     = $this->site->getStockPurchaseById($payment->purchase_id);

    if ($this->form_validation->run() == true) {
      $status = getPOST('status');
      $note   = $this->sma->clear_tags(getPOST('note'));

      if ($payment->status == $status) {
        $this->session->set_flashdata('error', lang('status_not_changed'));
        $this->sma->md();
      }

      $payment_data = [
        'date'   => date('Y-m-d H:i:s'), // Update payment.
        'status' => $status, // approved or paid.
        'note'   => $note
      ];

      if ($status == 'approved') {
        $stat = 'Payment has been <strong>approved</strong>.';
      } else if ($status == 'paid') {
        $stat = 'Payment has been <strong>paid</strong>.';
      } else {
        $this->session->set_flashdata('error', lang('status_not_known'));
        $this->sma->md();
      }
    } elseif (getPOST('update')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'procurements/purchases');
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->updateStockPurchasePayment($payment->id, $payment_data)) {
        /*$this->procurements_model->addPurchaseHistory([
        'reference'         => $purchase->reference,
        'payment_reference' => $payment->reference,
        'description'       => $stat,
        'user_id'           => XSession::get('user_id')
      ]);*/
        $this->session->set_flashdata('message', $stat);
      } else {
        $this->session->set_flashdata('error', lang('update_payment_status_failed'));
      }
      admin_redirect($_SERVER['HTTP_REFERER'] ?? 'procurements/purchases');
    } else {
      $this->data['payment']      = $payment;
      $this->data['purchase']     = $purchase;
      $this->data['bank']         = $this->site->getBankById($payment->bank_id);
      $this->data['user']         = $this->site->getUserById($payment->created_by);
      $this->load->view($this->theme . 'procurements/purchases/update_payment_status', $this->data);
    }
  }

  private function purchases_updateSafetyStock()
  {
    if ($this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST') {
      ini_set('max_execution_time', 0);

      if ($this->site->syncPurchaseSafetyStock()) {
        sendJSON(['error' => 0, 'msg' => 'All products safety stock have been updated successfully.']);
      }

      sendJSON(['error' => 1, 'msg' => 'Failed to update safety stock.']);
    }
  }

  private function purchases_split()
  {
    $items = [];
    $purchase_ids = getGET('id');


    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $date        = getPOST('date');
      $po_items    = getPOST('item');
      $supplier_id = getPOST('supplier');

      $total     = 0;
      $warehouse = $this->site->getWarehouseByCode('LUC');

      if ($po_items) {
        foreach ($po_items as $item) {
          $items = $this->site->getStocks(['id' => $item['stock_id']]);
          $new_qty = filterDecimal($item['quantity']);

          $purchase_items[] = [
            'date'          => $date,
            'product_id'    => $items[0]->product_id,
            'cost'          => $items[0]->cost,
            'quantity'      => 0,
            'purchased_qty' => $new_qty,
            'warehouse_id'  => $warehouse->id, // Default Lucretia.
            'status'        => 'need_approval',
            'unit_id'       => $items[0]->unit_id,
            'spec'          => $items[0]->spec,
            'created_by'    => XSession::get('user_id')
          ];

          if ($items[0]->purchased_qty == $new_qty) { // If equal, remove stock from old po
            $this->site->deleteStockQuantity(['id' => $items[0]->id]);
            $this->site->syncStockPurchase($items[0]->purchase_id);
          } else if ($items[0]->purchased_qty > $new_qty) { // If new_qty less, decrease qty from old po
            $rest_qty = $items[0]->purchased_qty - $new_qty;
            $this->site->updateStockQuantity(['id' => $items[0]->id], ['purchased_qty' => $rest_qty]);
            $this->site->syncStockPurchase($items[0]->purchase_id);
          } else { // Cannot more than original qty.
            sendJSON([
              'error' => 1,
              'msg'   => "Item {$items[0]->product_code} max quantity {$items[0]->purchased_qty}."
            ]);
          }

          $total += roundDecimal($items[0]->price * $new_qty);
        }

        if ($purchase_items) {
          $purchase_data = [
            'date' => $date,
            'warehouse_id' => $warehouse->id,
            'note' => '',
            'grand_total' => $total,
            'created_by' => XSession::get('user_id'),
            'payment_status' => 'pending',
            'status' => 'need_approval',
            'payment_term' => 1,
            'supplier_id' => $supplier_id
          ];

          if ($this->site->addStockPurchase($purchase_data, $purchase_items)) {
            sendJSON(['error' => 0, 'msg' => 'Purchase has been created successfully.']);
          } else {
            sendJSON(['error' => 1, 'msg' => 'Failed to create purchase.']);
          }
        }
      }

      sendJSON(['error' => 1, 'msg' => 'No items selected.']);
    } // END POST REQUEST.

    if ($purchase_ids) {
      foreach ($purchase_ids as $purchase_id) {
        $purchase = $this->site->getStockPurchaseByID($purchase_id);
        $purchase_items = $this->site->getStockPurchaseItems($purchase_id);

        if ($purchase->status == 'received' || $purchase->status == 'received_partial') continue;

        if ($purchase_items) {
          foreach ($purchase_items as $purchase_item) {
            $items[] = $purchase_item;
          }
        }
      }
    }

    $this->data['items'] = $items;
    $this->load->view($this->theme . 'procurements/purchases/split', $this->data);
  }

  private function purchases_status($purchase_id)
  { // purchases
    $this->data['status_mode'] = TRUE;
    $this->po_mode = 'status';
    $this->purchases_edit($purchase_id);
  }

  private function purchases_stock_suggestions()
  { // Called by Product Quantity Alerts
    $action = getPOST('name');
    $data = (object)[
      'msg' => 'failed'
    ];

    $product_ids = getPOST('ids');
    $warehouse_id = $this->site->getWarehouseByName('Lucretia Enterprise')->id; // Destination stock to Lucretia Enterprise.

    if ($action == 'add_purchases' && !empty($product_ids)) {
      $po_items = [];
      foreach ($product_ids as $product_id) {
        $hash = sha1($product_id + rand(1, 1000));
        $row = $this->site->getProductByID($product_id);
        $current_stock = $this->site->getStockQuantity($row->id, $warehouse_id);
        $safe_stock = getOrderStock($row->quantity, $row->min_order_qty, $row->safety_stock);

        if ($current_stock >= $row->safety_stock) continue; // If destination stock more or equal alert quantity, then ignore it.

        $row->current_stock   = $current_stock;
        $row->quantity        = $safe_stock;
        $row->spec            = '';
        $row->base_unit       = $row->unit;
        $row->unit            = $row->purchase_unit ? $row->purchase_unit : $row->unit;

        $units = $this->site->getUnitsByBUID($row->base_unit);

        if ($row->unit != $row->base_unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safe_stock, $unit));
            }
          }
        }

        $po_items[] = [
          'id'        => $hash,
          'item_id'   => $row->id,
          'label'     => $row->name . ' (' . $row->code . ')',
          'row'       => $row,
          'units'     => $units
        ];
      }
      $data->items = $po_items;
      $data->warehouse_id = $warehouse_id;
      $data->msg = 'success';
    }
    sendJSON($data);
  }

  private function purchases_suggestions()
  { // purchases
    $term         = getGET('term', true);
    //$supplier_id  = getGET('supplier', true);
    $warehouse_id = getGET('warehouse'); // From warehouse_id

    if (strlen($term) < 1 || !$term) {
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
    }

    $rows = $this->site->getProductNames($term, 15); // standard product

    if ($rows) {
      $po_items = [];
      $r = 0;

      foreach ($rows as $row) {
        if ($row->active == 0) continue; //! Use it after products uploaded

        $hash = generateUUID();
        $wh_product = $this->site->getWarehouseProduct($row->id, $warehouse_id);
        $current_stock = ($wh_product ? $wh_product->quantity : 0);
        $row->min_order_qty = ($row->min_order_qty ?? 1);
        $safe_stock = getOrderStock($row->quantity, $row->min_order_qty, $row->safety_stock);

        $row->current_stock = $current_stock;
        $row->purchased_qty = $safe_stock;
        $row->received_qty_1 = 0;
        $row->received_qty_2 = 0;
        $row->received_qty_3 = 0;
        $row->received_date_1 = '';
        $row->received_date_2 = '';
        $row->received_date_3 = '';
        $row->rest_qty      = 0;
        $row->quantity      = 0;
        $row->spec          = '';
        $row->base_unit     = $row->unit;
        $row->unit          = $row->purchase_unit ? $row->purchase_unit : $row->unit;
        $units    = $this->site->getUnitsByBUID($row->base_unit);

        if ($row->unit != $row->base_unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->purchased_qty = round(baseToUnitQty($safe_stock, $unit)); // lbr => rim (1000 => 2)
            }
          }
        }

        $po_items[] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => $row->name . ' (' . $row->code . ')',
          'row'     => $row,
          'units'   => $units
        ];
        $r++;
      }

      sendJSON($po_items);
    } else {
      sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
    }
  }

  private function purchases_view($purchase_id)
  { // purchases
    if (getGET('id')) {
      $purchase_id = getGET('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $purchase = $this->site->getStockPurchaseByID($purchase_id);

    if (!XSession::get('view_right')) {
      $this->sma->view_rights($purchase->created_by);
    }

    // Sync Purchase.
    $this->site->syncStockPurchase($purchase_id);

    $this->data['rows']            = $this->site->getStockPurchaseItems($purchase_id);
    $this->data['supplier']        = $this->site->getSupplierByID($purchase->supplier_id);
    $this->data['warehouse']       = $this->site->getWarehouseByID($purchase->warehouse_id);
    $this->data['purchase']        = $purchase;
    $this->data['payments']        = $this->site->getStockPurchasePayments($purchase_id);
    $this->data['created_by']      = $this->site->getUser($purchase->created_by);
    $this->data['updated_by']      = ($purchase->updated_by ? $this->site->getUser($purchase->updated_by) : NULL);

    $this->load->view($this->theme . 'procurements/purchases/view', $this->data);
  }

  /**
   * STOCK TRANSFERS
   */
  public function transfers()
  {
    // die('obsolete'); // Still use 2022-12-19 09:30:55
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) || $params[0] == 'warehouse' ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function transfers_index($warehouse_id = NULL)
  { // transfers
    $wh_id = $warehouse_id ?? XSession::get('warehouse_id') ?? $this->Settings->default_warehouse;

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['warehouse_id'] = $wh_id;
    $this->data['warehouses']   = $this->site->getAllWarehouses();

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('procurements')],
      ['link' => '#', 'page' => lang('transfers_list')]
    ];

    $meta = ['page_title' => lang('transfers_list'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/transfers/index', $this->data);
  }

  private function transfers_actions()
  {
    $action = getPOST('form_action');
    $vals   = getPOST('val');

    if ($action && $action == 'delete') {
      if ($vals) {
        foreach ($vals as $val) {
          if (!$this->site->deleteStockTransfer($val)) {
            sendJSON(['error' => 1, 'msg' => "Cannot delete transfer id '{$val}'."]);
          }
        }
        sendJSON(['error' => 0, 'msg' => 'Stock Transfer have been deleted successfully.']);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Please select at least one.']);
  }

  private function transfers_add()
  {
    $this->form_validation->set_rules('to_warehouse', lang('warehouse') . ' (' . lang('to') . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang('warehouse') . ' (' . lang('from') . ')', 'required|is_natural_no_zero');

    if ($this->form_validation->run()) {
      $date              = $this->serverDateTime;
      $warehouseIdFrom = getPOST('from_warehouse');
      $warehouseIdTo   = getPOST('to_warehouse');
      $note              = $this->sma->clear_tags(getPOST('note'));
      $status            = (getPOST('status') == 'packing' ? getPOST('status') : 'packing'); // Status must 'packing'

      $total = 0;

      $i = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
      for ($r = 0; $r < $i; $r++) {
        $itemCode          = $_POST['product_code'][$r];
        $item_markon_price  = $_POST['markon_price'][$r];
        $item_quantity      = $_POST['quantity'][$r];
        $itemSpec          = $_POST['spec'][$r];

        if (isset($itemCode) && isset($item_quantity)) {
          $product = $this->site->getProductByCode($itemCode);
          $from_warehouse_qty = $this->site->getStockQuantity($product->id, $warehouseIdFrom); // Get source stock.

          if ($from_warehouse_qty < $item_quantity) {
            $this->session->set_flashdata('error', lang('no_match_found') . ' (' . lang('product_name') . ' <strong>' . $product->name . '</strong> ' . lang('product_code') . ' <strong>' . $product->code . '</strong>)');
            admin_redirect('procurements/transfers/add');
          }

          $productData[] = [
            'product_id' => $product->id,
            'quantity'   => $item_quantity,
            'price'      => round(filterDecimal($item_markon_price)),
            'spec'       => $itemSpec
          ];

          $subtotal = round(filterDecimal($item_markon_price * $item_quantity)); // Get sell price to warehouse.

          $total += $subtotal;
        }
      }

      if (empty($productData)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $grand_total = $total;

      $transferData = [
        'date'              => $date,
        'from_warehouse_id' => $warehouseIdFrom,
        'to_warehouse_id'   => $warehouseIdTo,
        'note'              => $note,
        'grand_total'       => $grand_total,
        'created_by'        => XSession::get('user_id'),
        'payment_status'    => 'pending',
        'status'            => $status, // new add = packing
      ];

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $transferData['attachment'] = $uploader->storeRandom();
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->addTransfer($transferData, $productData)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', lang('transfer_added'));
      } else {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('error', lang('transfer_add_failed'));
      }
      admin_redirect('procurements/transfers');
    } else {
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

      $this->data['name'] = [
        'name'  => 'name',
        'id'    => 'name',
        'type'  => 'text',
        'value' => $this->form_validation->set_value('name'),
      ];
      $this->data['quantity'] = [
        'name'  => 'quantity',
        'id'    => 'quantity',
        'type'  => 'text',
        'value' => $this->form_validation->set_value('quantity'),
      ];

      $this->data['warehouses'] = $this->site->getAllWarehouses();

      $bc   = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/transfers'), 'page' => lang('transfers')],
        ['link' => '#', 'page' => lang('add_transfer')]
      ];
      $meta = ['page_title' => lang('add_transfer'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/transfers/add', $this->data);
    }
  }

  private function transfers_add_payment($transfer_id)
  { // transfers
    $this->sma->checkPermissions('payment', TRUE, 'transfers');
    $this->form_validation->set_rules('date', lang('date'), 'required');
    $this->form_validation->set_rules('from_bank_id', lang('account') . ' ' . lang('from'), 'required');
    $this->form_validation->set_rules('to_bank_id', lang('account') . ' ' . lang('to'), 'required');
    $this->form_validation->set_rules('amount', lang('amount'), 'required');

    $transfer = $this->site->getStockTransferByID($transfer_id);

    if ($transfer->payment_status == 'paid') {
      $this->session->set_flashdata('error', lang('transfer_already_paid'));
      $this->sma->md();
    }

    if ($this->form_validation->run() == TRUE) {
      $date = $this->serverDateTime;

      $data = [
        'date' => $date,
        'from_bank_id'    => getPOST('from_bank_id'),
        'to_bank_id'      => getPOST('to_bank_id'),
        'note'            => getPOST('note'),
        'amount'          => round(filterDecimal(getPOST('amount'))),
        'created_by'      => XSession::get('user_id')
      ];

      if ($data['amount'] > ($transfer->grand_total - $transfer->paid)) {
        $this->session->set_flashdata('error', 'You cannot pay more than grand total.');
        $this->sma->md();
      }
      if ($data['amount'] == 0) {
        $this->session->set_flashdata('error', 'Are you kidding me to pay 0 rupiah?');
        $this->sma->md();
      }

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $data['attachment'] = $uploader->storeRandom();
      }

      $bank_from_balance = $this->site->getBankBalanceByID($data['from_bank_id']);

      if ($bank_from_balance < $data['amount']) {
        $this->session->set_flashdata('warning', lang('insufficient_funds'));
        admin_redirect('procurements/transfers');
      }
      if ($this->site->addStockTransferPayment($transfer_id, $data)) { // Transfer Payment as Bank Mutation.
        $this->session->set_flashdata('message', lang('stock_transfer_paid'));
        admin_redirect('procurements/transfers');
      } else {
        $this->session->set_flashdata('error', lang('stock_transfer_paid_fail'));
        admin_redirect('procurements/transfers');
      }
    } elseif (getPOST('add_bank_mutation')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('procurements/transfers');
    }

    $banks = $this->site->getBanks(['active' => 1]);
    // for ($a = 0; $a < count($banks); $a++) {
    //   // $banks[$a]->balance = $this->site->getBankBalanceByID($banks[$a]->id);
    // }

    $this->data['banks'] = $banks;
    $this->data['transfer'] = $transfer;
    $this->data['users'] = $this->site->getUsers(['active' => 1]);
    $this->load->view($this->theme . 'procurements/transfers/add_payment', $this->data);
  }

  private function transfers_addTransfersFromPlan()
  {
    $warehouse_ids = getPOST('warehouse_ids'); // ID: durian, fatmawati, tembalang...

    if ($warehouse_ids) {
      $wh_ids = explode(',', $warehouse_ids);
      foreach ($wh_ids as $warehouse_id) {
        // Add stock transfer by each warehouse id.
        $this->site->addStockTransferByWarehouseID($warehouse_id);
      }

      sendJSON(['error' => 0, 'msg' => 'Transfers have beed added successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to add transfers.']);
  }

  private function transfers_delete($transfer_id)
  {
    $this->sma->checkPermissions('delete', TRUE, 'transfers', TRUE);

    if (getGET('id')) {
      $transfer_id = getGET('id');
    }

    if ($this->site->deleteStockTransfer($transfer_id)) {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 0, 'msg' => lang('transfer_deleted')]);
      } else {
        $this->session->set_flashdata('message', lang('transfer_deleted'));
        admin_redirect('procurements/transfers');
      }
    } else {
      if ($this->input->is_ajax_request()) {
        sendJSON(['error' => 1, 'msg' => lang('transfer_delete_failed')]);
      } else {
        $this->session->set_flashdata('message', lang('transfer_delete_failed'));
        admin_redirect('procurements/transfers');
      }
    }
  }

  private function transfers_delete_payment($payment_id)
  {
    $payment = $this->site->getPaymentByID($payment_id);
    if ($payment) {
      $transfer_id = $payment->transfer_id;
      if ($this->site->deletePayments(['id' => $payment_id])) {
        $payments = $this->site->getStockTransferPayments($transfer_id);
        if (!$payments) {
          $this->site->updateStockTransfer($transfer_id, ['payment_status' => 'pending', 'paid' => 0]);
        }
        $this->session->set_flashdata('message', 'Payment deleted successfully.');
        admin_redirect('procurements/transfers');
      }
    }
    $this->session->set_flashdata('error', 'Delete payment failed.');
    admin_redirect('procurements/transfers');
  }

  private function transfers_edit($transferId)
  {
    $this->sma->checkPermissions('edit', NULL, 'transfers');

    if (getGET('id')) {
      $transferId = getGET('id');
    }

    // $this->site->syncStockTransfer($transferId);

    $transfer = $this->site->getTransfer(['id' => $transferId]);

    if (!$transfer) {
      $this->session->set_flashdata('error', 'Stock Transfer ID is not set.');
      admin_redirect('procurements/transfers');
    }

    if (!XSession::get('edit_right')) {
      $this->sma->view_rights($transfer->created_by);
    }

    $this->form_validation->set_rules('reference', lang('reference'), 'required');
    $this->form_validation->set_rules('to_warehouse', lang('warehouse') . ' (' . lang('to') . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang('warehouse') . ' (' . lang('from') . ')', 'required|is_natural_no_zero');

    if ($this->form_validation->run()) {
      $date              = rd_trim(getPOST('date'));
      $warehouseIdTo   = getPOST('to_warehouse');
      $warehouseIdFrom = getPOST('from_warehouse');
      $note              = htmlEncode(getPOST('note'));
      $status            = getPOST('status');
      $total             = 0;

      $i = isset($_POST['product_id']) ? count($_POST['product_id']) : 0;
      for ($r = 0; $r < $i; $r++) {
        $itemCode          = $_POST['product_code'][$r];
        $item_markon_price  = filterDecimal($_POST['markon_price'][$r]);
        $item_quantity      = filterDecimal($_POST['quantity'][$r]);
        $itemSpec          = $_POST['spec'][$r];

        if (isset($itemCode) && isset($item_quantity)) {
          $product = $this->site->getProductByCode($itemCode);
          $wh_product = $this->site->getWarehouseProduct($product->id, $warehouseIdFrom); // Get source stock.
          $from_warehouse_qty = ($wh_product ? $wh_product->quantity + $item_quantity : 0);

          if ($from_warehouse_qty < $item_quantity) {
            $this->session->set_flashdata('error', lang('no_match_found') . ' (' . lang('product_name') . ' <strong>' . $product->name . '</strong> ' . lang('product_code') . ' <strong>' . $product->code . '</strong>)');
            admin_redirect('procurements/transfers/edit/' . $transferId);
          }

          $productData = [
            'product_id'   => $product->id,
            'quantity'     => $item_quantity,
            'price'        => roundDecimal($item_markon_price),
            'spec'         => $itemSpec,
            'warehouse_id' => $warehouseIdTo
          ];

          $subtotal = roundDecimal($item_markon_price * $item_quantity);

          $items[] = $productData;
          $total += $subtotal;
        }
      }

      if (empty($items)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $grand_total = $total;

      $transferData = [
        'date'              => $date,
        'from_warehouse_id' => $warehouseIdFrom,
        'to_warehouse_id'   => $warehouseIdTo,
        'note'              => $note,
        'grand_total'       => $grand_total,
        'created_by'        => $transfer->created_by,
        'updated_by'        => XSession::get('user_id'),
        'status'            => $status,
      ];

      if ($status == 'sent') {
        $transferData['sent_date'] = $this->serverDateTime;
      }

      if ($status == 'received') {
        $transferData['received_date'] = $this->serverDateTime;
      }

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $transferData['attachment'] = $uploader->storeRandom();
      }
    }

    if ($this->form_validation->run() == true) {
      if ($this->site->updateTransfer($transferId, $transferData, $items)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', lang('stock_transfer_edited'));
      } else {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('error', lang('stock_transfer_edit_failed'));
        admin_redirect('procurements/transfers/edit/' . $transferId);
      }
      admin_redirect('procurements/transfers');
    } else { // VIEW
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['transfer'] = $transfer;

      $transfer_items = $this->site->getStockTransferItemsByTransferID($transferId);
      $warehouseIdFrom = $transfer->from_warehouse_id;
      $warehouseIdTo   = $transfer->to_warehouse_id;

      foreach ($transfer_items as $item) {
        $hash = sha1($item->product_id + mt_rand(1, 1000));
        $product_id = $item->product_id;

        $wh_item_to    = $this->site->getWarehouseProduct($product_id, $warehouseIdTo);
        $wh_item_from  = $this->site->getWarehouseProduct($product_id, $warehouseIdFrom);
        $row = $this->site->getProductByID($product_id);

        $destination_stock    = $wh_item_to->quantity;
        $source_stock         = $wh_item_from->quantity;

        $row->markon_price    = $item->price;
        $row->source_qty      = $source_stock + $item->quantity;
        $row->destination_qty = $destination_stock;
        $row->min_order_qty   = $row->min_order_qty; // Min. order quantity.
        $row->safety_stock    = $wh_item_to->safety_stock;
        $row->quantity        = $item->quantity;
        $row->unit            = (!empty($row->purchase_unit) ? $row->purchase_unit : $row->unit);
        $row->spec            = ($item->spec ?? '');

        $units    = $this->site->getUnitsByBUID($row->unit);
        /* DO NOT USE THIS.
        if ($row->unit != $row->base_unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safe_stock, $unit));
            }
          }
        }
*/
        $to_items[$hash] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => $row->name . ' (' . $row->code . ')',
          'row'     => $row,
          'units'   => $units
        ];
      }

      $this->data['transfer_items'] = json_encode($to_items);
      $this->data['id']             = $transferId;
      $this->data['warehouses']     = $this->site->getAllWarehouses();

      $bc = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => lang('procurements')],
        ['link' => admin_url('procurements/transfers'), 'page' => lang('transfers_list')],
        ['link' => '#', 'page' => lang('edit_transfer')]
      ];

      $meta = ['page_title' => lang('edit_transfer'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/transfers/edit', $this->data);
    }
  }

  private function transfers_edit_payment($payment_id)
  {
    $payment = $this->site->getPaymentByID($payment_id);
    $transfer = $this->site->getStockTransferByID($payment->transfer_id);

    if (!$transfer) {
      $this->session->set_flashdata('error', 'Cannot find stock transfer id.');
      $this->sma->md();
    }

    // if ($payment->status == 'approved') {
    //   $this->session->set_flashdata('error', lang('paid_approved_payment'));
    //   $this->sma->md();
    // }

    $this->form_validation->set_rules('old_amount', lang('old_amount'), 'required');
    $this->form_validation->set_rules('new_amount', lang('new_amount'), 'required');
    $this->form_validation->set_rules('bank_id', lang('paid_by'), 'required');
    $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');

    $old_amount = round(filterDecimal(getPOST('old_amount')));
    $new_amount = round(filterDecimal(getPOST('new_amount')));

    if ($this->form_validation->run() == true) {
      $date = $this->sma->fld(trim(getPOST('date'))) . date(':s');
      $bank = $this->site->getBankById(getPOST('bank_id'));
      $data_payment = [
        'date'      => $date,
        'reference' => $payment->reference,
        'bank_id'   => getPOST('bank_id'),
        'method'    => $bank->type,
        'amount'    => $new_amount,
        'note'      => htmlEncode(getPOST('note'))
      ];

      $bank_balance = $this->site->getBankBalanceByID(getPOST('bank_id'));

      if (floatval($transfer->grand_total) < floatval($data_payment['amount'])) {
        $this->session->set_flashdata('error', lang('paid_over_grandtotal'));
        $this->sma->md();
      }

      $uploader = new FileUpload();

      if ($uploader->has('userfile')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $data_payment['attachment'] = $uploader->storeRandom();
      }
    } elseif (getPOST('edit_payment')) {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
    if ($this->form_validation->run() == true) {
      if ($this->site->updatePayment($payment_id, $data_payment)) {
        $this->session->set_flashdata('message', lang('payment_added'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      if (getPOST('edit_payment')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER']);
      }
      $banks = $this->site->getBanks();
      // for ($a = 0; $a < count($banks); $a++) {
      //   $banks[$a]->balance = $this->site->getBankBalanceByID($banks[$a]->id);
      // }
      $this->data['error']          = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['banks']          = $banks;
      $this->data['payment']        = $payment;
      $this->data['transfer']       = $transfer;
      $this->load->view($this->theme . 'procurements/transfers/edit_payment', $this->data);
    }
  }

  private function transfers_getTransfersPlan()
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

  private function transfers_getTransfers()
  { // transfers
    die('obsolete');
    $reference      = getGET('reference');
    $warehouse_from = getGET('warehouse_from');
    $warehouse_to   = getGET('warehouse_to');
    $status         = getGET('status');
    $payment_status = getGET('payment_status');
    $start_date     = getGET('start_date');
    $end_date       = getGET('end_date');
    $xls            = getGET('xls');

    $detail_link   = anchor('admin/procurements/transfers/view/$1?price=1', '<i class="fad fa-file"></i> ' . lang('transfer_details'), 'data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg no-modal-header"');
    $email_link    = anchor('admin/transfers/email/$1', '<i class="fad fa-envelope"></i> ' . lang('email_transfer'), 'data-toggle="modal" data-target="#myModal"');
    $edit_link     = anchor('admin/procurements/transfers/edit/$1', '<i class="fad fa-edit"></i> ' . lang('edit_transfer'));
    $payments_link = anchor('admin/procurements/transfers/payments/$1', '<i class="fad fa-fw fa-money-bill-alt"></i> ' . lang('view_payments'), 'data-toggle="modal" data-backdrop="false" data-modal-class="modal-lg" data-target="#myModal"');
    $delete_link   = "<a href='#' class='tip po' title='" . lang('delete_transfer') . "' data-content=\"<p>"
      . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' id='a__$1' href='" . admin_url('procurements/transfers/delete/$1') . "'>"
      . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i> "
      . lang('delete_transfer') . '</a>';
    $action = '<div class="text-center"><div class="btn-group text-left">'
      . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
      . lang('actions') . ' <span class="caret"></span></button>
		<ul class="dropdown-menu pull-right" role="menu">
			<li>' . $detail_link . '</li>
			<li>' . $edit_link . '</li>
      <li>' . $payments_link . '</li>
      <li class="divider"></li>
			<li>' . $delete_link . '</li>
		</ul>
    </div></div>';

    if (XSession::get('warehouse_id')) { // Make sure to protect other warehouse.
      $warehouse_to = [];
      $warehouse_to[] = XSession::get('warehouse_id');
    }

    if (!$xls) { // View Web.
      $this->load->library('datatables');
      $this->datatables
        ->select('transfers.id AS id, date, reference, whfrom.name AS fname, whto.name AS tname,
          grand_total, paid, (grand_total - paid) AS balance, payment_status, transfers.status AS status,
          attachment')
        ->from('transfers')
        ->join('warehouses whfrom', 'whfrom.id = transfers.from_warehouse_id', 'left')
        ->join('warehouses whto', 'whto.id = transfers.to_warehouse_id', 'left');

      if ($reference) {
        $this->datatables->like('reference', $reference, 'both');
      }
      if ($warehouse_from) {
        $this->datatables->group_start();
        foreach ($warehouse_from as $whf) {
          $this->datatables->or_where('from_warehouse_id', $whf);
        }
        $this->datatables->group_end();
      }
      if ($warehouse_to) {
        $this->datatables->group_start();
        foreach ($warehouse_to as $whf) {
          $this->datatables->or_where('to_warehouse_id', $whf);
        }
        $this->datatables->group_end();
      }
      if ($status) {
        $this->datatables->group_start();
        foreach ($status as $st) {
          $this->datatables->or_like('status', $st, 'both');
        }
        $this->datatables->group_end();
      }
      if ($payment_status) {
        $this->datatables->group_start();
        foreach ($payment_status as $pst) {
          $this->datatables->or_like('payment_status', $pst, 'both');
        }
        $this->datatables->group_end();
      }
      if ($start_date && $end_date) {
        $this->datatables->where("date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      } else if ($start_date) {
        $this->datatables->where("date >= '{$start_date} 00:00:00'");
      } else if ($end_date) {
        $this->datatables->where("date <= '{$end_date} 23:59:59'");
      }
      if (!$this->Owner && !$this->Admin && !XSession::get('view_right')) {
        $this->datatables->where('created_by', XSession::get('user_id'));
      }
      $this->datatables->add_column('Actions', $action, 'id')
        ->unset_column('fcode')
        ->unset_column('tcode');
      echo $this->datatables->generate();
    } else if ($xls) { // Export Excel
      $transfer_data = [];

      $this->db->select("id, date, reference, from_warehouse_name, from_warehouse_code, to_warehouse_name,
        to_warehouse_code, grand_total, paid, (grand_total - paid) AS balance, payment_status, status")
        ->from('transfers');

      if ($reference) {
        $this->db->like('reference', $reference, 'both');
      }
      if ($warehouse_from) {
        $this->db->group_start();
        foreach ($warehouse_from as $whf) {
          $this->db->or_where('from_warehouse_id', $whf);
        }
        $this->db->group_end();
      }
      if ($warehouse_to) {
        $this->db->group_start();
        foreach ($warehouse_to as $whf) {
          $this->db->or_where('to_warehouse_id', $whf);
        }
        $this->db->group_end();
      }
      if ($status) {
        $this->db->group_start();
        foreach ($status as $st) {
          $this->db->or_like('status', $st, 'both');
        }
        $this->db->group_end();
      }
      if ($payment_status) {
        $this->db->group_start();
        foreach ($payment_status as $pst) {
          $this->db->or_like('payment_status', $pst, 'both');
        }
        $this->db->group_end();
      }
      if ($start_date && $end_date) {
        $this->db->where("date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      } else if ($start_date) {
        $this->db->where("date >= '{$start_date}'");
      } else if ($end_date) {
        $this->db->where("date <= '{$end_date}'");
      }
      if (!$this->Owner && !$this->Admin && !XSession::get('view_right')) {
        $this->db->where('created_by', XSession::get('user_id'));
      }

      $q = $this->db->get();
      if ($q->num_rows() > 0) {
        foreach ($q->result() as $row) {
          $transfer_data[] = $row;
        }
      }

      if ($transfer_data) {
        $excel = $this->ridintek->spreadsheet();
        $excel->setTitle('Stock Transfers');
        $excel->setCellValue('A1', 'Date');
        $excel->setCellValue('B1', 'Reference');
        $excel->setCellValue('C1', 'Warehouse (From)');
        $excel->setCellValue('D1', 'Warehouse (To)');
        $excel->setCellValue('E1', 'Cost Total');
        $excel->setCellValue('F1', 'Grand Total');
        $excel->setCellValue('G1', 'Paid');
        $excel->setCellValue('H1', 'Balance');
        $excel->setCellValue('I1', 'Payment Status');
        $excel->setCellValue('J1', 'Status');
        $excel->setBold('A1:J1');
        $excel->setFillColor('A1:J1', 'FFFF00');
        $excel->setHorizontalAlign('A1:J1', 'center');

        $x = 2;
        foreach ($transfer_data as $transfer) {
          $transferCost = 0;

          $transferItems = $this->site->getStocks(['transfer_id' => $transfer->id, 'status' => 'sent']);

          if ($transferItems) {
            foreach ($transferItems as $transferItem) {
              $transferCost += ($transferItem->cost * $transferItem->quantity);
            }
          }

          $excel->setCellValue("A{$x}", $transfer->date);
          $excel->setCellValue("B{$x}", $transfer->reference);
          $excel->setCellValue("C{$x}", "{$transfer->from_warehouse_name} ({$transfer->from_warehouse_code})");
          $excel->setCellValue("D{$x}", "{$transfer->to_warehouse_name} ({$transfer->to_warehouse_code})");
          $excel->setCellValue("E{$x}", $transferCost);
          $excel->setCellValue("F{$x}", $transfer->grand_total);
          $excel->setCellValue("G{$x}", $transfer->paid);
          $excel->setCellValue("H{$x}", $transfer->balance);
          $excel->setCellValue("I{$x}", lang($transfer->payment_status));
          $excel->setCellValue("J{$x}", lang($transfer->status));

          $x++;
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

        $excel->export('PrintERP - StockTransfersList-' . date('Ymd_His'));
      }
    }
  }

  private function transfers_payments($transferId)
  { // transfers
    $this->data['payments'] = $this->site->getStockTransferPayments($transferId);
    $this->data['inv']      = $this->site->getStockTransferByID($transferId);
    // $this->site->syncStockTransfer($transferId);
    $this->load->view($this->theme . 'procurements/transfers/payments', $this->data);
  }

  private function transfers_plan()
  {
    die('obsolete');
    $bc = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Procurements'],
      ['link' => admin_url('transfers'), 'page' => 'Transfers'],
      ['link' => '#', 'page' => 'Transfers Plan']
    ];
    $meta = ['page_title' => 'Transfers Plan', 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('procurements/transfers/plan', $this->data);
  }

  private function transfers_status($transfer_id)
  { // transfers
    if (getGET('id')) {
      $transfer_id = getGET('id');
    }

    if (empty($transfer_id)) {
      $this->session->set_flashdata('error', 'Stock Transfer ID is not set.');
      admin_redirect('procurements/transfers');
    }

    $transfer = $this->site->getStockTransferByID($transfer_id);

    if (!$transfer) {
      $this->session->set_flashdata('error', 'Stock Transfer ID is not set.');
      admin_redirect('procurements/transfers');
    }

    if (!XSession::get('edit_right')) {
      $this->sma->view_rights($transfer->created_by);
    }

    if ($transfer->status == 'packing') {
      $this->sma->checkPermissions('sent', null, 'transfers');
    }

    if ($transfer->status == 'sent') {
      $this->sma->checkPermissions('received', null, 'transfers');
    }

    $this->form_validation->set_rules('reference', lang('reference'), 'required');
    $this->form_validation->set_rules('to_warehouse', lang('warehouse') . ' (' . lang('to') . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang('warehouse') . ' (' . lang('from') . ')', 'required|is_natural_no_zero');

    if ($this->form_validation->run()) {
      if ($transfer->status == getPOST('status')) {
        $this->session->set_flashdata('error', lang('status_not_changed'));
        admin_redirect('procurements/transfers/status/' . $transfer_id);
      }

      $date                   = rd_trim(getPOST('date'));
      $warehouseIdTo        = getPOST('to_warehouse');
      $warehouseIdFrom      = getPOST('from_warehouse');
      $note                   = htmlEncode(getPOST('note'));
      $status                 = getPOST('status');
      $total = 0;

      $i = isset($_POST['product_id']) ? count($_POST['product_id']) : 0;
      for ($r = 0; $r < $i; $r++) {
        $itemCode          = $_POST['product_code'][$r];
        $item_markon_price  = filterDecimal($_POST['markon_price'][$r]);
        $item_quantity      = filterDecimal($_POST['quantity'][$r]);
        $itemSpec          = $_POST['spec'][$r];

        if (isset($itemCode) && isset($item_markon_price) && isset($item_quantity)) {
          $product  = $this->site->getProductByCode($itemCode);
          /*$from_warehouse_qty = $this->site->getStockQuantity($product->id, $warehouseIdFrom) + $item_quantity; // Get source stock.

		  		if ($from_warehouse_qty < $item_quantity) {
		  			$this->session->set_flashdata('error', lang('no_match_found') . ' (' . lang('product_name') . ' <strong>' . $product->name . '</strong> ' . lang('product_code') . ' <strong>' . $product->code . '</strong>)');
		  			admin_redirect('procurements/transfers/status/' . $transfer_id);
		  		}*/

          $product_data = [
            'product_id'   => $product->id,
            'quantity'     => $item_quantity,
            'price'        => round($item_markon_price),
            'spec'         => $itemSpec,
            'warehouse_id' => $warehouseIdTo
          ];

          $subtotal = round($item_markon_price * $item_quantity);

          $products[] = $product_data;
          $total += $subtotal;
        }
      } // for

      if (empty($products)) {
        $this->form_validation->set_rules('product', lang('order_items'), 'required');
      }

      $grand_total = $total;

      $transfer_data = [
        'date'              => $date,
        'from_warehouse_id' => $warehouseIdFrom,
        'to_warehouse_id'   => $warehouseIdTo,
        'note'              => $note,
        'grand_total'       => $grand_total,
        'created_by'        => $transfer->created_by,
        'updated_by'        => XSession::get('user_id'),
        'status'            => $status
      ];

      if ($status == 'sent') {
        $transfer_data['sent_date'] = $this->serverDateTime;
      }

      if ($status == 'received') {
        $transfer_data['received_date'] = $this->serverDateTime;
      }

      $uploader = new FileUpload();

      if ($uploader->has('document')) {
        if ($uploader->getSize('mb') > 2) {
          XSession::set('error', 'Attachment tidak boleh lebih dari 2MB.');
          admin_redirect($_SERVER['HTTP_REFERER']);
        }

        $transfer_data['attachment'] = $uploader->storeRandom();
      }
    }
    if ($this->form_validation->run() == true) {
      if ($this->site->updateStockTransfer($transfer_id, $transfer_data, $products)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', lang('stock_transfer_updated'));
        admin_redirect('procurements/transfers');
      } else {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('error', lang('stock_transfer_edit_failed'));
        admin_redirect('procurements/transfers/status/' . $transfer_id);
      }
      admin_redirect('procurements/transfers/transfers');
    } else { // VIEW
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['transfer'] = $transfer;
      $transfer_items    = $this->site->getStockTransferItemsByTransferID($transfer_id); // New
      $warehouseIdFrom = $transfer->from_warehouse_id;
      $warehouseIdTo   = $transfer->to_warehouse_id;

      foreach ($transfer_items as $item) {
        $hash = sha1($item->product_id + mt_rand(1, 1000));
        $product_id = $item->product_id;

        // Remove syncProductQty if it has stable.
        // $this->site->syncProductQty($product_id, $warehouseIdFrom);
        // $this->site->syncProductQty($product_id, $warehouseIdTo);
        $from_whp = $this->site->getWarehouseProduct($product_id, $warehouseIdFrom);
        $to_whp = $this->site->getWarehouseProduct($product_id, $warehouseIdTo);
        $row = $this->site->getProductByID($product_id);

        // $destination_stock = $this->site->getStockQuantity($product_id, $warehouseIdTo);
        // $source_stock      = $this->site->getStockQuantity($product_id, $warehouseIdFrom);
        $destination_stock = $to_whp->quantity;
        $source_stock      = $from_whp->quantity;
        $safe_stock        = getOrderStock($destination_stock, $row->min_order_qty, $to_whp->safety_stock);

        if ($transfer->status == 'sent') {
          $source_stock = $item->quantity;
        }

        $row->markon_price    = $item->price;
        $row->source_qty      = $source_stock + $item->quantity; // Lucretia stock
        $row->destination_qty = $destination_stock; // Destination outlet stock.
        $row->min_order_qty   = $row->min_order_qty; // Min. order quantity.
        $row->safety_stock    = $to_whp->safety_stock; // Destination outlet safety stock.
        $row->quantity        = $item->quantity;
        $row->spec            = ($item->spec ?? '');
        $row->base_unit       = $row->unit;
        $row->unit            = (!empty($row->purchase_unit) ? $row->purchase_unit : $row->unit);

        $units = $this->site->getUnitsByBUID($row->base_unit);

        if ($row->unit != $row->base_unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safe_stock, $unit));
            }
          }
        }

        $to_items[$hash] = [
          'id'        => $hash,
          'item_id'   => $row->id,
          'label'     => $row->name . ' (' . $row->code . ')',
          'row'       => $row,
          'units'     => $units
        ];
      }

      $this->data['transfer_items'] = json_encode($to_items);
      $this->data['id']             = $transfer_id;
      $this->data['warehouses']     = $this->site->getAllWarehouses();

      $bc = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('procurements/transfers'), 'page' => lang('transfers_list')],
        ['link' => '#', 'page' => lang('update_transfer_status')]
      ];

      $meta = ['page_title' => lang('update_transfer_status'), 'bc' => $bc];
      $this->data = array_merge($this->data, $meta);

      $this->page_construct('procurements/transfers/status', $this->data);
    }
  }

  private function transfers_stock_suggestions() // MANUAL
  { // Called by warehouse stock alert
    $action = getPOST('name');
    $data = (object)[
      'msg' => 'failed'
    ];
    $product_ids = json_decode(getPOST('ids'));
    $warehouseIdFrom = $this->site->getWarehouseByName('Lucretia Enterprise')->id; // Default from Lucretia Enterprise.
    $warehouseIdTo = 0;

    if ($action == 'add_transfers' && !empty($product_ids)) {
      $to_items = [];
      foreach ($product_ids as $product) {
        $hash = sha1($product->product_id + mt_rand(1, 1000));
        $product_id      = $product->product_id;
        $warehouseIdTo = $product->warehouse_id;

        // $this->site->syncProductQty($product_id, $warehouseIdFrom);
        // $this->site->syncProductQty($product_id, $warehouseIdTo);
        $from_whp = $this->site->getWarehouseProduct($product_id, $warehouseIdFrom);
        $to_whp   = $this->site->getWarehouseProduct($product_id, $warehouseIdTo);
        $row = $this->site->getProductByID($product_id);

        // $destination_stock = $this->site->getStockQuantity($row->id, $warehouseIdTo);
        // $source_stock      = $this->site->getStockQuantity($row->id, $warehouseIdFrom);
        $destination_stock = $to_whp->quantity;
        $source_stock      = $from_whp->quantity;
        $safe_stock = getOrderStock($destination_stock, $row->min_order_qty, $to_whp->safety_stock);

        if ($source_stock == 0) continue; // If source stock doesn't have stock, then ignore it.
        if ($destination_stock >= $to_whp->safety_stock) continue; // If destination stock more than or equal safety stock, then ignore it.
        if ($safe_stock > $source_stock) $safe_stock = $source_stock; // If safe stock more than source stock.

        $row->markon_price     = $row->markon_price;
        $row->source_qty       = $source_stock; // Lucretia stock
        $row->destination_qty  = $destination_stock; // Destination outlet stock.
        $row->safety_stock     = $to_whp->safety_stock; // Destination outlet safety stock.
        $row->quantity         = $safe_stock;
        $row->spec             = '';
        $row->base_unit        = $row->unit;
        $row->unit             = (!empty($row->purchase_unit) ? $row->purchase_unit : $row->unit);

        $units = $this->site->getUnitsByBUID($row->base_unit);

        if ($row->unit != $row->base_unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safe_stock, $unit));
            }
          }
        }

        $to_items[] = [
          'id'        => $hash,
          'item_id'   => $row->id,
          'label'     => $row->name . ' (' . $row->code . ')',
          'row'       => $row,
          'units'     => $units
        ];
      }
      $data->items = $to_items;
      $data->warehouse_id = $warehouseIdTo;
      $data->msg = 'success';
    }

    sendJSON($data);
  }

  private function transfers_suggestions()
  { // Procurements > Transfers > Add Item (Manual)
    $term               = getGET('term', true);
    $fromWarehouseId  = getGET('from_warehouse_id', true);
    $toWarehouseId    = getGET('to_warehouse_id', true);

    if (strlen($term) < 1 || !$term) {
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . admin_url('welcome') . "'; }, 10);</script>");
    }

    $sr = $term;
    $rows = $this->site->getProductNames($sr, 25);

    if ($rows) {
      $po_items = [];
      $r = 0;

      foreach ($rows as $row) {
        $hash = sha1($row->id + mt_rand(1, 1000));
        $whpFrom = $this->site->getWarehouseProduct($row->id, $fromWarehouseId);
        $whpTo   = $this->site->getWarehouseProduct($row->id, $toWarehouseId);

        if (!$whpFrom) {
          continue;
          // sendJSON([['id' => 0, 'label' => 'Produk origin tidak ditemukan', 'value' => $term]]);
        }
        if (!$whpTo) {
          continue;
          // sendJSON([['id' => 0, 'label' => 'Produk tujuan tidak ditemukan', 'value' => $term]]);
        }

        $destinationStock = $whpTo->quantity;
        $sourceStock      = $whpFrom->quantity;
        $safeStock = getOrderStock($destinationStock, $row->min_order_qty, $whpTo->safety_stock);

        if ($sourceStock <= 0) continue; // If source stock doesn't have stock, then ignore it.
        if ($safeStock > $sourceStock) $safeStock = $sourceStock; // If safe stock more then source stock.

        $row->markon_price     = $row->markon_price;
        $row->source_qty       = $sourceStock; // Warehouse From Stock.
        $row->destination_qty  = $destinationStock; // Destination outlet stock.
        $row->min_order_qty    = $row->min_order_qty; // Min. order quantity.
        $row->safety_stock     = $whpTo->safety_stock; // Destination outlet safety stock.
        $row->quantity         = $safeStock;
        $row->spec             = '';
        $row->base_unit        = $row->unit;
        $row->unit             = (!empty($row->purchase_unit) ? $row->purchase_unit : $row->unit);
        $units                 = $this->site->getUnitsByBUID($row->unit);

        if ($row->base_unit != $row->unit) {
          foreach ($units as $unit) { // For quantity alert stock. OK.
            if ($row->unit == $unit->id) {
              $row->quantity = round(baseToUnitQty($safeStock, $unit));
            }
          }
        }

        $po_items[] = [
          'id'      => $hash,
          'item_id' => $row->id,
          'label'   => $row->name . ' (' . $row->code . ')',
          'row'     => $row,
          'units'   => $units
        ];
        $r++;
      }
      sendJSON($po_items);
    } else {
      sendJSON([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
    }
  }

  private function transfers_updateSafetyStock()
  {
    if ($this->input->is_ajax_request() && $_SERVER['REQUEST_METHOD'] == 'POST') {
      ini_set('max_execution_time', 0);

      if ($this->site->syncTransferSafetyStock()) {
        sendJSON(['error' => 0, 'msg' => 'All warehouse products safety stock have been updated successfully.']);
      }

      sendJSON(['error' => 1, 'msg' => 'Failed to update safety stock.']);
    }
  }

  private function transfers_view($transfer_id)
  { // transfers
    if (getGET('id')) {
      $transfer_id = getGET('id');
    }

    $this->data['show_price'] = (getGET('price') == 1 ? TRUE : FALSE);

    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $transfer            = $this->site->getStockTransferByID($transfer_id);
    if (!XSession::get('view_right')) {
      $this->sma->view_rights($transfer->created_by, true);
    }
    $this->data['rows']           = $this->site->getStockTransferItemsByTransferID($transfer_id);
    $this->data['from_warehouse'] = $this->site->getWarehouseByID($transfer->from_warehouse_id);
    $this->data['to_warehouse']   = $this->site->getWarehouseByID($transfer->to_warehouse_id);
    $this->data['transfer']       = $transfer;
    $this->data['created_by']     = $this->site->getUser($transfer->created_by);
    $this->data['updated_by']     = $this->site->getUser($transfer->updated_by);
    $this->load->view($this->theme . 'procurements/transfers/view', $this->data);
  }
}
/* EOF */