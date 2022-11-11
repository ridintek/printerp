<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Warehouse_model extends MY_Model {
  public function __construct () {
    parent::__construct();
  }

  public function getWarehouseProducts ($product_id, $warehouse_id = NULL)
  {
    if ($warehouse_id) {
      $this->db->where('warehouse_id', $warehouse_id);
    }
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id]);
    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return [];
  }
}