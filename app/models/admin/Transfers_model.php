<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Transfers_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function getProductByCategoryID($id)
  {
    $q = $this->db->get_where('products', ['category_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return true;
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

  public function getProductComboItems($pid, $warehouse_id)
  {
    $this->db->select('products.id as id, combo_items.item_code as code, combo_items.quantity as qty, products.name as name, warehouses_products.quantity as quantity')
      ->join('products', 'products.code=combo_items.item_code', 'left')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->where('warehouses_products.warehouse_id', $warehouse_id)
      ->group_by('combo_items.id');
    $q = $this->db->get_where('combo_items', ['combo_items.product_id' => $pid]);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }

      return $data;
    }
    return false;
  }

  public function getProductNames($term, $warehouse_id, $limit = 5)
  {
    $this->db->select('products.id, code, name, warehouses_products.quantity, cost, tax_rate, type, unit, purchase_unit, tax_method')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->group_by('products.id');
    if ($this->Settings->overselling) {
      $this->db->where("type = 'standard' AND (name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
    } else {
      $this->db->where("type = 'standard' AND warehouses_products.warehouse_id = '" . $warehouse_id . "' AND warehouses_products.quantity > 0 AND "
        . "(name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
    }
    $this->db->limit($limit);
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

  public function getProductOptions($product_id, $warehouse_id, $zero_check = true)
  {
    $this->db->select('product_variants.id as id, product_variants.name as name, product_variants.cost as cost, product_variants.quantity as total_quantity, warehouses_products_variants.quantity as quantity')
      ->join('warehouses_products_variants', 'warehouses_products_variants.option_id=product_variants.id', 'left')
      ->where('product_variants.product_id', $product_id)
      ->where('warehouses_products_variants.warehouse_id', $warehouse_id)
      ->group_by('product_variants.id');
    if ($zero_check) {
      $this->db->where('warehouses_products_variants.quantity >', 0);
    }
    $q = $this->db->get('product_variants');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getProductQuantity($product_id, $warehouse = DEFAULT_WAREHOUSE)
  {
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse], 1);
    if ($q->num_rows() > 0) {
      return $q->row_array(); //$q->row();
    }
    return false;
  }

  public function getProductVariantByName($name, $product_id)
  {
    $q = $this->db->get_where('product_variants', ['name' => $name, 'product_id' => $product_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getProductWarehouseOptionQty($option_id, $warehouse_id)
  {
    $q = $this->db->get_where('warehouses_products_variants', ['option_id' => $option_id, 'warehouse_id' => $warehouse_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getTransferByID($id)
  {
    $q = $this->db->get_where('transfers', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }

    return false;
  }

  public function getWarehouseProduct($warehouse_id, $product_id, $variant_id)
  {
    if ($variant_id) {
      return $this->getProductWarehouseOptionQty($variant_id, $warehouse_id);
    } else {
      return $this->getWarehouseProductQuantity($warehouse_id, $product_id);
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

  public function getWHProduct($id)
  {
    $this->db->select('products.id, code, name, warehouses_products.quantity, cost, tax_rate')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->group_by('products.id');
    $q = $this->db->get_where('products', ['warehouses_products.product_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }

    return false;
  }
}
