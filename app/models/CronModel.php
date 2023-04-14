<?php

declare(strict_types=1);

class CronModel
{
  public function deleteLogs()
  {
    $logs = [
      APPPATH . 'logs',
      FCPATH . 'files/exports',
      FCPATH . 'files/import',
      FCPATH . 'files/products/imports',
      FCPATH . 'files/sales/attachments',
      FCPATH . 'files/sales/payments',
      FCPATH . 'files/trackingpod/attachments',
      '/home/idp/logs'
    ];

    foreach ($logs as $log) {
      $files = array_diff(scandir($log), array('.', '..'));

      foreach ($files as $file) {
        if (filemtime("$log/$file") < strtotime('-1 month')) {
          unlink("$log/$file");
        }
      }
    }
  }

  public static function syncProducts()
  {
    $success = 0;
    $pstandard = Product::get(['type' => 'standard']);
    $pservice = Product::get(['type' => 'service']);

    $products = array_merge($pstandard, $pservice);

    $warehouses = Warehouse::get(['active' => '1']);

    foreach ($warehouses as $warehouse) {
      foreach ($products as $product) {
        if (Product::sync((int)$product->id, (int)$warehouse->id)) $success++;
      }
    }

    return $success;
  }

  /**
   * Please set cronjob for everyday at 00:00.
   */
  public static function runDaily()
  {
    $ci = &get_instance();

    if ($total = self::syncProducts()) {
      log_message('info', sprintf("%d products have been synced successfully.", $total));
    }

    if ($ci->site->syncPaymentValidations()) {
      log_message('info', 'Payment validations have been synced successfully.');
    }

    if (self::resetOrderRef()) { // OK
      log_message('info', lang('order_ref_updated'));
    }

    if ($sess = $ci->site->clearSessionStorage()) {
      log_message('info', sprintf('%d session have been deleted.', $sess));
    }

    if ($jobs = $ci->site->clearOldWAJobs()) {
      log_message('info', sprintf('%d WA Jobs have been deleted.', $jobs));
    }

    // if ($stats = $this->site->checkStockOpnameStatus()) {
    //   foreach ($stats as $stat) {
    //     $m[] = sprintf("Status for [%s] has been changed from '%s' to '%s'.", $stat['reference'], $stat['old_status'], $stat['new_status']);
    //   }
    // }

    if ($ss = self::syncSafetyStock()) {
      foreach ($ss as $msg) {
        log_message('info', $msg);
      }
    }

    // if ($stats = $ci->site->clearEmptyPayments()) {
    //   foreach ($stats as $stat) {
    //     log_message('info', sprintf("Payment for [%s id:%d] has been deleted.", $stat['type'], $stat['id']));
    //   }
    // }

    return TRUE;
  }

  public function run_monthly()
  {
    $this->deleteLogs();
    return TRUE;
  }

  public function run_weekly()
  {
    // default: 90 days ago
    // if ($sales = $this->site->deleteOldNeedPaymentSales()) {
    //   foreach ($sales as $sale) {
    //     log_message('info', 
    //       sprintf("Old Need Payment Sale [id:%s, date:%s] has been deleted.", $sale->reference, $sale->date)
    //     );
    //   }
    // }

    return TRUE;
  }

  public static function syncSafetyStock()
  {
    $ci = &get_instance();
    $m = [];

    if ($ci->site->syncPurchaseSafetyStock()) {
      $m[] = 'Purchase safety stock has been updated successfully.';
    }

    if ($ci->site->syncTransferSafetyStock()) {
      $m[] = 'Transfer safety stock has been updated successfully.';
    }

    return $m;
  }

  private function checkUpdate()
  {
    $fields = ['version' => $this->Settings->version, 'code' => $this->Settings->purchase_code, 'username' => $this->Settings->envato_username, 'site' => base_url()];
    $this->load->helper('update');
    $protocol = is_https() ? 'https://' : 'http://';
    $updates  = get_remote_contents($protocol . 'tecdiary.com/api/v1/update/', $fields);
    $response = json_decode($updates);
    if (!empty($response->data->updates)) {
      $this->db->update('settings', ['update' => 1], ['setting_id' => 1]);
      return true;
    }
    return false;
  }

  private function getAllPendingInvoices()
  {
    $today    = date('Y-m-d');
    $paid     = $this->lang->line('paid');
    $q        = $this->db->get_where('sales', ['due_date <=' => $today, 'due_date !=' => '1970-01-01', 'due_date !=' => null, 'payment_status' => 'pending']);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  private function getAllPPInvoices()
  {
    $today    = date('Y-m-d');
    $paid     = $this->lang->line('paid');
    $q        = $this->db->get_where('sales', ['due_date <=' => $today, 'due_date !=' => '1970-01-01', 'due_date !=' => null, 'payment_status' => 'partial']);
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  private function getCosting($date, $warehouse_id = null)
  {
    $this->db->select('SUM( COALESCE( purchase_unit_cost, 0 ) * quantity ) AS cost, SUM( COALESCE( sale_unit_price, 0 ) * quantity ) AS sales, SUM( COALESCE( purchase_net_unit_cost, 0 ) * quantity ) AS net_cost, SUM( COALESCE( sale_net_unit_price, 0 ) * quantity ) AS net_sales', false);
    $this->db->where('costing.date', $date);
    if ($warehouse_id) {
      $this->db->join('sales', 'sales.id=costing.sale_id')
        ->where('sales.warehouse_id', $warehouse_id);
    }

    $q = $this->db->get('costing');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  private function getExpenses($sdate, $edate, $warehouse_id = null)
  {
    $this->db->select('SUM( COALESCE( amount, 0 ) ) AS total', false);
    $this->db->where('date >=', $sdate)->where('date <=', $edate);
    if ($warehouse_id) {
      $this->db->where('warehouse_id', $warehouse_id);
    }

    $q = $this->db->get('expenses');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  private function getOrderDiscount($sdate, $edate, $warehouse_id = null)
  {
    $this->db->select('SUM( COALESCE( order_discount, 0 ) ) AS order_discount', false);
    $this->db->where('date >=', $sdate)->where('date <=', $edate);
    if ($warehouse_id) {
      $this->db->where('warehouse_id', $warehouse_id);
    }

    $q = $this->db->get('sales');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  private static function getOrderRef()
  {
    $ci = &get_instance();
    $q = $ci->db->get_where('order_ref', ['ref_id' => 1], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  private function getTotalSales($sdate, $edate, $warehouse_id = null)
  {
    $this->db->select('count(id) as total, sum(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid, SUM(COALESCE(total_tax, 0)) as tax', false)
      ->where('status !=', 'pending')
      ->where('date >=', $sdate)->where('date <=', $edate);
    if ($warehouse_id) {
      $this->db->where('warehouse_id', $warehouse_id);
    }
    $q = $this->db->get('sales');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public static function resetOrderRef() // OK
  {
    $ci = &get_instance();

    if ($ci->Settings->reference_format == 1 || $ci->Settings->reference_format == 2) {
      $month = date('Y-m') . '-01';
      $year  = date('Y') . '-01-01';
      if ($ref = self::getOrderRef()) {
        $reset_ref = [
          'adjustment' => 1, 'cmreport' => 1, 'expense' => 1, 'income' => 1, 'iuse' => 1,
          'mutation' => 1, 'opname' => 1, 'purchase' => 1, 'sale' => 1, 'transfer' => 1
        ];
        if ($ci->Settings->reference_format == 1 && strtotime($ref->date) < strtotime($year)) {
          $reset_ref['date'] = $year;
          $ci->db->update('order_ref', $reset_ref, ['ref_id' => 1]);
          return true;
        } elseif ($ci->Settings->reference_format == 2 && strtotime($ref->date) < strtotime($month)) { // Current configuration.
          $reset_ref['date'] = $month;
          $ci->db->update('order_ref', $reset_ref, ['ref_id' => 1]);
          return true;
        }
      }
    }
    return false;
  }

  public function syncAllProductsQuantity()
  {
    $items = $this->site->getProducts(['type' => ['service', 'standard']]);
    if ($items) {
      foreach ($items as $item) {
        $this->site->syncProductQty($item->id);
      }
      return TRUE;
    }
    return FALSE;
  }

  private function updateInvoiceStatus($id)
  {
    if ($this->db->update('sales', ['payment_status' => 'due'], ['id' => $id])) {
      return true;
    }
    return false;
  }
}
