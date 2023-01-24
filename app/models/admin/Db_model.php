<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Db_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function getBestSeller($start_date = null, $end_date = null)
  {
    if (!$start_date) {
      $start_date = date('Y-m-d', strtotime('first day of this month')) . ' 00:00:00';
    }
    if (!$end_date) {
      $end_date = date('Y-m-d', strtotime('last day of this month')) . ' 23:59:59';
    }

    $this->db
      ->select('product_name, product_code')
      ->select_sum('quantity')
      ->from('sale_items')
      ->join('sales', 'sales.id = sale_items.sale_id', 'left')
      ->where('sales.date >=', $start_date)
      ->where('sales.date <', $end_date)
      ->group_by('product_name, product_code')
      ->order_by('sum(quantity)', 'desc')
      ->limit(10);

    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getChartData()
  {
    $debugMode = FALSE;
    $rows = [];

    // 12 = 12 month ago, if 24 then take data from 24 month ago.
    for ($a = 12; $a >= 0; $a--)
    {
      // $dateMonth = date('Y-m', strtotime('-' . $a * 31 . ' day'));
      $dateMonth = date('Y-m', strtotime('-' . $a . ' month', strtotime(date('Y-m-') . '01')));
      $row = NULL;

      if (!$debugMode) {
        $this->db
        ->select("COALESCE(SUM(grand_total), 0) AS total, COALESCE(SUM(paid), 0) AS total_paid, COALESCE(SUM(balance), 0) AS total_balance")
        ->from('sales')
        ->where("date LIKE '{$dateMonth}%'");

        $q = $this->db->get();

        if ($this->db->affected_rows()) {
          $row = $q->row();
        }
      }

      if ($row || $debugMode) {
        $total = ($debugMode ? 0 : $row->total);
        $total_paid = ($debugMode ? 0 : $row->total_paid);
        $total_balance = ($debugMode ? 0 : $row->total_balance);

        $rows[] = (object)[
          'bulan' => $dateMonth,
          'grand_total' => $total,
          'total_paid' => $total_paid,
          'total_balance' => $total_balance
        ];
      }
    }

    return $rows;
  }

  public function getChartData_old()
  {
    // if ($this->isLocal) { // If localhost.
      $myQuery = "SELECT Penjualan.bulan,
      '0' AS grand_total,
      '0' AS total_paid,
      '0' AS total_balance
      FROM (
        SELECT date_format(date, '%Y-%m') AS bulan,
        '0' AS grand_total,
        '0' AS total_paid,
        '0' AS total_balance
        FROM sales
        WHERE date >= date_sub( now(), INTERVAL 12 MONTH )
        GROUP BY date_format(date, '%Y-%m')
      ) AS Penjualan
      ORDER BY Penjualan.bulan ASC";
    // } else {
    //   $myQuery = "SELECT Penjualan.bulan,
    //   COALESCE(Penjualan.grand_total, 0) AS grand_total,
    //   COALESCE(Penjualan.total_paid, 0) AS total_paid,
    //   COALESCE(Penjualan.total_balance, 0) AS total_balance
    //   FROM (
    //     SELECT date_format(date, '%Y-%m') AS bulan,
    //     SUM(grand_total) AS grand_total,
    //     SUM(paid) AS total_paid,
    //     SUM(balance) AS total_balance
    //     FROM sales
    //     WHERE date >= date_sub( now(), INTERVAL 12 MONTH )
    //     GROUP BY date_format(date, '%Y-%m')
    //   ) AS Penjualan
    //   ORDER BY Penjualan.bulan ASC";
    // }

    $q = $this->db->query($myQuery);

    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getLatestCustomers()
  {
    $this->db->order_by('id', 'desc');
    $q = $this->db->get_where('customers', ['group_name' => 'customer'], 5);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getLatestPurchases()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('purchases-index')) {
      $this->db->where('created_by', XSession::get('user_id'));
    }

    $this->db->order_by('id', 'desc');

    $q = $this->db->get('purchases');

    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getLatestSales()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('sales-index')) {
      $this->db
      ->group_start()
        ->where('warehouse_id', XSession::get('warehouse_id'))
        ->or_where('created_by', XSession::get('user_id'))
      ->group_end();
    }

    $data = [];

    $opt = getCurrentMonthPeriod(getPastMonthPeriod(2));

    $this->db
    ->group_start()
      ->not_like('status', 'completed', 'none')
      ->not_like('status', 'delivered', 'none')
    ->group_end();

    $this->db->where("date BETWEEN '{$opt['start_date']} 00:00:00' AND '{$opt['end_date']} 23:59:59'");

    $this->db->order_by('id', 'desc');

    $q = $this->db->get('sales');

    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return [];
  }

  public function getLatestSuppliers()
  {
    $this->db->order_by('id', 'desc');
    $q = $this->db->get_where('suppliers', ['group_name' => 'supplier'], 5);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getLatestTransfers()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('transfers-index')) {
      $this->db->where('to_warehouse_id', XSession::get('warehouse_id'));
    }

    $this->db->order_by('id', 'desc');

    $q = $this->db->get('transfers');

    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getStockValue()
  {
    $q = $this->db->query('SELECT SUM(qty*price) as stock_by_price, SUM(qty*cost) as stock_by_cost
    FROM (
      Select sum(COALESCE(' . $this->db->dbprefix('warehouses_products') . '.quantity, 0)) as qty, price, cost
      FROM ' . $this->db->dbprefix('products') . '
      JOIN ' . $this->db->dbprefix('warehouses_products') . ' ON ' . $this->db->dbprefix('warehouses_products') . '.product_id=' . $this->db->dbprefix('products') . '.id
      GROUP BY ' . $this->db->dbprefix('warehouses_products') . '.id ) a');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }
}
