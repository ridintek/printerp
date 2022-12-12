<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sales_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
    $this->load->admin_model('finances_model');
    $this->load->admin_model('settings_model');
  }

  /* ----------------- Gift Cards --------------------- */

  public function addGiftCard($data = [], $ca_data = [], $sa_data = [])
  {
    if ($this->db->insert('gift_cards', $data)) {
      if (!empty($ca_data)) {
        $this->db->update('customers', ['award_points' => $ca_data['points']], ['id' => $ca_data['customer']]);
      } elseif (!empty($sa_data)) {
        $this->db->update('users', ['award_points' => $sa_data['points']], ['id' => $sa_data['user']]);
      }
      return true;
    }
    return false;
  }

  public function addOptionQuantity($option_id, $quantity)
  {
    if ($option = $this->getProductOptionByID($option_id)) {
      $nq = $option->quantity + $quantity;
      if ($this->db->update('product_variants', ['quantity' => $nq], ['id' => $option_id])) {
        return true;
      }
    }
    return false;
  }

  public function addPayment ($data = [], $customer_id = null) // All Sale must be paid with this function.
  {
    /* Double/Over Payment Protection */
    $sale = $this->getInvoiceByID($data['sale_id']);
    $balance = floatval($sale->grand_total) - floatval($sale->paid);
    if (floatval($data['amount']) > floatval($balance)) return FALSE;
    //if ($data['amount'] == 0) return FALSE;

    if (Payment::add($data)) {
      // Update sale payment
      $this->db->update('sales', ['payment_method' => $data['method']], ['id' => $data['sale_id']]);

      $this->site->syncSales($data['sale_id']); // Update status.
      return true;
    }
    return false;
  }

  public function getAllInvoiceItems($sale_id, $return_id = null)
  {
    $this->db->select(
      "sale_items.*,
      products.image, products.unit as base_unit_id,
      products.price_ranges_value as price_ranges_value, units.code as base_unit_code")
      ->join('products', 'products.id=sale_items.product_id', 'left')
      ->join('units', 'units.id=products.unit', 'left')
      ->order_by('sale_items.id', 'asc');
    if ($sale_id) {
      $this->db->where('sale_items.sale_id', $sale_id);
    }
    $q = $this->db->get('sale_items');

    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getAllInvoiceItemsWithDetails($sale_id)
  {
    $this->db->select('sale_items.*, products.details, product_variants.name as variant');
    $this->db->join('products', 'products.id=sale_items.product_id', 'left')
    ->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
    ->group_by('sale_items.id');
    $this->db->order_by('id', 'asc');
    $q = $this->db->get_where('sale_items', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getAllQuoteItems($quote_id)
  {
    $q = $this->db->get_where('quote_items', ['quote_id' => $quote_id]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getCostingLines($sale_item_id, $product_id, $sale_id = null)
  {
    if ($sale_id) {
      $this->db->where('sale_id', $sale_id);
    }
    $orderby = ($this->Settings->accounting_method == 1) ? 'asc' : 'desc';
    $this->db->order_by('id', $orderby);
    $q = $this->db->get_where('costing', ['sale_item_id' => $sale_item_id, 'product_id' => $product_id]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getDeliveryByID($id)
  {
    $q = $this->db->get_where('deliveries', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getDeliveryBySaleID($sale_id)
  {
    $q = $this->db->get_where('deliveries', ['sale_id' => $sale_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getInvoiceByID($id)
  {
    $q = $this->db->get_where('sales', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getInvoicePayments($sale_id)
  {
    $this->db->select("{$this->db->dbprefix('payments')}.id as id, {$this->db->dbprefix('payments')}.date as date,
    {$this->db->dbprefix('payments')}.reference as reference, {$this->db->dbprefix('banks')}.name as bank_name,
    {$this->db->dbprefix('banks')}.number as acc_no, {$this->db->dbprefix('banks')}.type as acc_type,
    {$this->db->dbprefix('payments')}.amount as amount, method, created_by,
    attachment")
    ->from('payments')
    ->join('banks', 'banks.id=payments.bank_id', 'left')
    ->where('payments.sale_id', $sale_id)
    ->order_by('id', 'asc');
    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getItemByID($id)
  {
    $q = $this->db->get_where('sale_items', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }

    return false;
  }

  public function getItemRack($product_id, $warehouse_id)
  {
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse_id], 1);
    if ($q->num_rows() > 0) {
      $wh = $q->row();
      return $wh->rack;
    }
    return false;
  }

  public function getPaymentByID($id)
  {
    $q = $this->db->get_where('payments', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getPaymentsForSale($sale_id)
  {
    $this->db->select('payments.date, payments.method, payments.amount, payments.reference, users.first_name, users.last_name, type')
      ->join('users', 'users.id=payments.created_by', 'left');
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getPaypalSettings()
  {
    $q = $this->db->get_where('paypal', ['id' => 1]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getProductByCode($code)
  {
    $q = $this->db->get_where('products', ['code' => $code], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getProductByName($name)
  {
    $q = $this->db->get_where('products', ['name' => $name], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getProductComboItems($pid, $warehouse_id = null) // Belum benar
  {
    $this->db->select('products.id as id, combo_items.item_code as code, combo_items.quantity as qty, products.name as name,
      products.type as type, warehouses_products.quantity as quantity, warehouses_products.safety_stock as safety_stock')
      ->join('products', 'products.code=combo_items.item_code', 'left')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->group_by('combo_items.id');
    if ($warehouse_id) {
      $this->db->where('warehouses_products.warehouse_id', $warehouse_id);
    }
    $q = $this->db->get_where('combo_items', ['combo_items.product_id' => $pid]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }

      return $data;
    }
    return false;
  }

  public function getProductNames ($term, $warehouse_id, $pos = false, $limit = 16)
  {
    $wp = "( SELECT product_id, warehouse_id, quantity as quantity from {$this->db->dbprefix('warehouses_products')} ) FWP";

    $this->db->select('products.*, FWP.quantity as quantity, categories.id as category_id, categories.code as category_code, categories.name as category_name', false)
      ->join($wp, 'FWP.product_id=products.id', 'left')
      ->join('categories', 'categories.id=products.category_id', 'left')
      ->group_by('products.id');
    if ($this->Settings->overselling) {
      $this->db->where("({$this->db->dbprefix('products')}.name LIKE '%" . $term . "%' OR {$this->db->dbprefix('products')}.code LIKE '%" . $term . "%' OR  concat({$this->db->dbprefix('products')}.name, ' (', {$this->db->dbprefix('products')}.code, ')') LIKE '%" . $term . "%')");
    } else {
      $this->db->where("FWP.quantity > 0 AND FWP.warehouse_id = '" . $warehouse_id . "' AND " .
      "({$this->db->dbprefix('products')}.name LIKE '%" . $term . "%' OR {$this->db->dbprefix('products')}.code LIKE '%" .
      $term . "%' OR  concat({$this->db->dbprefix('products')}.name, ' (', {$this->db->dbprefix('products')}.code, ')') LIKE '%" . $term . "%')");
    }
    $this->db->order_by('products.name ASC');

    $this->db->where("(products.type LIKE 'combo' OR products.type LIKE 'service')"); // FILTER for show COMBO and SERVICE only.
    $this->db->order_by('products.name', 'ASC');

    //$this->db->limit($limit);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getProductOptionByID($id)
  {
    $q = $this->db->get_where('product_variants', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getProductOptions($product_id, $warehouse_id, $all = null)
  {
    return false;
  }

  public function getProductQuantity($product_id, $warehouse)
  {
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse], 1);
    if ($q->num_rows() > 0) {
      return $q->row_array(); //$q->row();
    }
    return false;
  }

  public function getProductVariantByName($name, $product_id)
  {
    return false;
  }

  public function getProductVariants($product_id)
  {
    return false;
  }

  public function getProductWarehouseOptionQty($option_id, $warehouse_id)
  {
    return false;
  }

  public function getPurchaseItemByID($id)
  {
    $q = $this->db->get_where('purchase_items', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getQuoteByID($id)
  {
    $q = $this->db->get_where('quotes', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getReturnByID($id)
  {
    $q = $this->db->get_where('sales', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getReturnBySID($sale_id)
  {
    $q = $this->db->get_where('sales', ['sale_id' => $sale_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getSaleByItemID ($sale_item_id) {
    $q = $this->db->get_where('sale_items', ['id' => $sale_item_id], 1);
    if ($q->num_rows() > 0) {
      return $this->getInvoiceByID($q->row('sale_id'));
    }
    return NULL;
  }

  public function getSaleByPaymentID ($payment_id) {
    $q0 = $this->db->get_where('payments', ['id' => $payment_id], 1);
    if ($q0->num_rows() > 0) {
      $q1 = $this->db->get_where('sales', ['id' => $q0->row('sale_id')], 1);
      if ($q1->num_rows() > 0) {
        return $q1->row();
      }
    }
    return NULL;
  }

  public function getSaleCosting($sale_id)
  {
    return NULL;
    $q = $this->db->get_where('costing', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getSaleItemByID($id)
  {
    $q = $this->db->get_where('sale_items', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getSkrillSettings()
  {
    $q = $this->db->get_where('skrill', ['id' => 1]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getStaff()
  {
    if (!$this->Owner) {
      $this->db->where('group_id !=', 1);
    }
    $this->db->where('group_id !=', 3)->where('group_id !=', 4);
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getTaxRateByName($name)
  {
    $q = $this->db->get_where('tax_rates', ['name' => $name], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getWarehouseProduct($pid, $wid)
  {
    $this->db->select("{$this->db->dbprefix('products')}.*, {$this->db->dbprefix('warehouses_products')}.quantity as quantity,
    {$this->db->dbprefix('categories')}.code as category_code")
      ->join('categories', 'products.category_id=categories.id', 'left')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left');
    $q = $this->db->get_where('products', ['warehouses_products.product_id' => $pid, 'warehouses_products.warehouse_id' => $wid]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getWarehouseProductQuantity($warehouse_id, $product_id)
  {
    $q = $this->db->get_where('warehouses_products', ['warehouse_id' => $warehouse_id, 'product_id' => $product_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function updateSale($id, $data, $items = []) // In USE.
  {
    $this->db->trans_start();
    if ($this->db->update('sales', $data, ['id' => $id]) && $this->db->delete('sale_items', ['sale_id' => $id])) {
      foreach ($items as $item) {
        $item['sale_id'] = $id;
        $this->db->insert('sale_items', $item);
      }

      $this->site->syncSales($id);
    }
    $this->db->trans_complete();
    if ($this->db->trans_status() === false) {
      log_message('error', 'An errors has been occurred while adding the sale (Update:Sales_model.php)');
    } else {
      return true;
    }
    return false;
  }

  public function updateSalesStatus($id, $status, $note) // NO Completing item.
  {
    $sale  = $this->getInvoiceByID($id);
    $items = $this->getAllInvoiceItems($id);

    $this->db->trans_start();
    if ($this->db->update('sales', ['status' => $status, 'note' => $note], ['id' => $id])) {
      if ($status == 'delivered') { // CS does this on delivered. Set all sale items as delivered.
        foreach ($items as $item) {
          $js = json_decode($item->json_data);
          $js->status = $status;
          $this->db->update('sale_items', ['json_data' => json_encode($js)], ['id' => $item->id]);
        }
      }
    }
    $this->db->trans_complete();

    if ($this->db->trans_status() === false) {
      log_message('error', 'An errors has been occurred while adding the sale (UpdataStatus:Sales_model.php)');
    } else {
      return true;
    }
    return false;
  }

  /**
   * Update sale item. NOT invoice. Used by OPERATOR ONLY. (NOT USED ANYMORE)
   */
  public function updateSalesItemStatus ($id, $status, $note) // $id = sale_item_id, NOT sale_id
  {
    $sale_item = $this->getItemByID($id);
    $completed_item = 0; $all_completed = FALSE;
    $sale      = $this->getSaleByItemID($id);

    if ($status == 'completed') { // For Product History only. OK.
      if ($sale_item->product_type == 'combo') {
        $combo_items = $this->site->getProductComboItems($sale_item->product_id);
        foreach ($combo_items as $combo_item) {
          if ($combo_item->type == 'standard') {
            $this->site->addProductDecreaseQty([ // Decrease RAW Item Quantity and write history.
              'reference' => $sale->reference,
              'category'  => 'Sale',
              'product_id' => $combo_item->id,
              'quantity'   => floatval($combo_item->qty) * floatval($sale_item->quantity),
              'warehouse_id' => $sale_item->warehouse_id
            ]);
          }
          if ($combo_item->type == 'service') { // For Combo Service Counter. KLIKPOD
            $this->site->addProductIncreaseHistory([
              'reference'  => $sale->reference,
              'product_id' => $combo_item->id,
              'warehouse_id' => $sale_item->warehouse_id,
              'category' => 'Sale',
              'quantity' => floatval($combo_item->qty) * floatval($sale_item->quantity)
            ]);
          }
        }
        $this->site->addProductIncreaseHistory([ // For Selling Item Counter.
          'reference'  => $sale->reference,
          'product_id' => $sale_item->product_id,
          'warehouse_id' => $sale_item->warehouse_id,
          'category' => 'Sale',
          'quantity' => floatval($sale_item->quantity)
        ]);
      } else if ($sale_item->product_type == 'service') {
        $this->site->addProductIncreaseHistory([ // For Service Item Counter. JASA POTONG
          'reference'  => $sale->reference,
          'product_id' => $sale_item->product_id,
          'warehouse_id' => $sale_item->warehouse_id,
          'category' => 'Sale',
          'quantity' => floatval($sale_item->quantity)
        ]);
      }
    }

    $json_data = json_decode($sale_item->json_data);
    $json_data->status = $status; // Change sale item status.

    $this->db->trans_start();
    $this->db->update('sale_items', ['json_data' => json_encode($json_data)], ['id' => $id]); // Update Sale Item Status.
    $this->db->trans_complete();

    $items = $this->getAllInvoiceItems($sale_item->sale_id);
    $row_count = count($items);

    // Check if all sale items status completed. If all completed then change sales status.
    foreach ($items as $item) {
      if (json_decode($item->json_data)->status == 'completed') $completed_item++;
      if ($completed_item === $row_count) {
        $all_completed = TRUE;
        break;
      }
    }

    if ($status != 'completed' && $all_completed) { // Waiting Production > In Production > Completed.
      $this->db->trans_start();
      $this->db->update('sales', ['status' => $status, 'note' => $note], ['id' => $sale_item->sale_id]); // Update sales notes.
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
