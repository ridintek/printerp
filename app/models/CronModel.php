<?php

declare(strict_types=1);

class CronModel
{
  protected function deleteLogs()
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

  protected static function syncProducts()
  {
    $success = 0;
    $pstandard = Product::get(['type' => 'standard']);
    $pservice = Product::get(['type' => 'service']);

    $products = array_merge($pstandard, $pservice);

    $warehouses = Warehouse::get(['active' => '1']);

    foreach ($warehouses as $warehouse) {
      foreach ($products as $product) {
        if (Product::syncOld($product->id, $warehouse->id)) $success++;
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

    if ($stats = $ci->site->clearEmptyPayments()) {
      foreach ($stats as $stat) {
        log_message('info', sprintf("Payment for [%s id:%d] has been deleted.", $stat['type'], $stat['id']));
      }
    }

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
    if ($sales = $this->site->deleteOldNeedPaymentSales()) {
      foreach ($sales as $sale) {
        log_message('info', 
          sprintf("Old Need Payment Sale [id:%s, date:%s] has been deleted.", $sale->reference, $sale->date)
        );
      }
    }

    return TRUE;
  }

  protected static function syncSafetyStock()
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

  public function send_email($details)
  {
    if ($details) {
      $table_html = '';
      $tables     = $this->cron_model->yesterday_report();
      foreach ($tables as $table) {
        $table_html .= $table . '<div style="clear:both"></div>';
      }
      foreach ($details as $detail) {
        $table_html = $table_html . $detail;
      }
      $msg_with_yesterday_report = $table_html;
      $owners                    = $this->db->get_where('users', ['group_id' => 1])->result();
      $this->load->library('email');
      $config['useragent'] = 'Stock Manager Advance';
      $config['protocol']  = $this->Settings->protocol;
      $config['mailtype']  = 'html';
      $config['crlf']      = "\r\n";
      $config['newline']   = "\r\n";
      if ($this->Settings->protocol == 'sendmail') {
        $config['mailpath'] = $this->Settings->mailpath;
      } elseif ($this->Settings->protocol == 'smtp') {
        $config['smtp_host'] = $this->Settings->smtp_host;
        $config['smtp_user'] = $this->Settings->smtp_user;
        $config['smtp_pass'] = $this->Settings->smtp_pass;
        $config['smtp_port'] = $this->Settings->smtp_port;
        if (!empty($this->Settings->smtp_crypto)) {
          $config['smtp_crypto'] = $this->Settings->smtp_crypto;
        }
      }
      $this->email->initialize($config);

      foreach ($owners as $owner) {
        list($user, $domain) = explode('@', $owner->email);
        if ($domain != 'tecdiary.com') {
          $this->load->library('parser');
          $parse_data = [
            'name'      => $owner->first_name . ' ' . $owner->last_name,
            'email'     => $owner->email,
            'msg'       => $msg_with_yesterday_report,
            'site_link' => base_url(),
            'site_name' => $this->Settings->site_name,
            'logo'      => '<img src="' . base_url('assets/uploads/logos/' . $this->Settings->logo) . '" alt="' . $this->Settings->site_name . '"/>',
          ];
          $msg     = file_get_contents('./themes/' . $this->Settings->theme . '/admin/views/email_templates/cron.html');
          $message = $this->parser->parse_string($msg, $parse_data);
          $subject = lang('cron_job') . ' - ' . $this->Settings->site_name;

          $this->email->from($this->Settings->default_email, $this->Settings->site_name);
          $this->email->to($owner->email);
          $this->email->subject($subject);
          $this->email->message($message);
          $this->email->send();
        }
      }
    }
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

  private function db_backup()
  {
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

    $files = glob('./files/backups/*.txt', GLOB_BRACE);
    $now   = time();
    foreach ($files as $file) {
      if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * 30) {
          unlink($file);
        }
      }
    }

    return true;
  }

  private function gen_html($costing, $discount, $expenses, $returns, $purchases, $sales, $warehouse = null)
  {
    $html = '<div style="border:1px solid #DDD; padding:10px; margin:10px 0;"><h3>' . ($warehouse ? $warehouse->name . ' (' . $warehouse->code . ')' : lang('all_warehouses')) . '</h3>
    <table width="100%" class="stable">
    <tr>
      <td style="border-bottom: 1px solid #EEE;">' . lang('products_sale') . '</td>
      <td style="text-align:right; border-bottom: 1px solid #EEE;">' . $this->sma->formatMoney($costing->sales) . '</td>
    </tr>';
    if ($discount && $discount->order_discount > 0) {
      $html .= '
      <tr>
        <td style="border-bottom: 1px solid #DDD;">' . lang('order_discount') . '</td>
        <td style="text-align:right;border-bottom: 1px solid #DDD;">' . $this->sma->formatMoney($discount->order_discount) . '</td>
      </tr>';
    }
    $html .= '
    <tr>
      <td style="border-bottom: 1px solid #EEE;">' . lang('products_cost') . '</td>
      <td style="text-align:right; border-bottom: 1px solid #EEE;">' . $this->sma->formatMoney($costing->cost) . '</td>
    </tr>';
    if ($expenses && $expenses->total > 0) {
      $html .= '
      <tr>
        <td style="border-bottom: 1px solid #DDD;">' . lang('expenses') . '</td>
        <td style="text-align:right;border-bottom: 1px solid #DDD;">' . $this->sma->formatMoney($expenses->total) . '</td>
      </tr>';
    }
    $html .= '
    <tr>
      <td width="300px;" style="border-bottom: 1px solid #DDD;"><strong>' . lang('profit') . '</strong></td>
      <td style="text-align:right;border-bottom: 1px solid #DDD;">
        <strong>' . $this->sma->formatMoney($costing->sales - $costing->cost - ($discount ? $discount->order_discount : 0) - ($expenses ? $expenses->total : 0)) . '</strong>
      </td>
    </tr>';
    if (isset($returns->total)) {
      $html .= '
      <tr>
        <td width="300px;" style="border-bottom: 2px solid #DDD;"><strong>' . lang('return_sales') . '</strong></td>
        <td style="text-align:right;border-bottom: 2px solid #DDD;"><strong>' . $this->sma->formatMoney($returns->total) . '</strong></td>
      </tr>';
    }
    $html .= '</table><h4 style="margin-top:15px;">' . lang('general_ledger') . '</h4>
    <table width="100%" class="stable">';
    if ($sales) {
      $html .= '
      <tr>
        <td width="33%" style="border-bottom: 1px solid #DDD;">' . lang('total_sales') . ': <strong>' . $this->sma->formatMoney($sales->total_amount) . '(' . $sales->total . ')</strong></td>
        <td width="33%" style="border-bottom: 1px solid #DDD;">' . lang('received') . ': <strong>' . $this->sma->formatMoney($sales->paid) . '</strong></td>
        <td width="33%" style="border-bottom: 1px solid #DDD;">' . lang('taxes') . ': <strong>' . $this->sma->formatMoney($sales->tax) . '</strong></td>
      </tr>';
    }
    if ($purchases) {
      $html .= '
      <tr>
        <td width="33%">' . lang('total_purchases') . ': <strong>' . $this->sma->formatMoney($purchases->total_amount) . '(' . $purchases->total . ')</strong></td>
        <td width="33%">' . lang('paid') . ': <strong>' . $this->sma->formatMoney($purchases->paid) . '</strong></td>
        <td width="33%">' . lang('taxes') . ': <strong>' . $this->sma->formatMoney($purchases->tax) . '</strong></td>
      </tr>';
    }
    $html .= '</table></div>';
    return $html;
  }

  private function get_expired_products()
  {
    if ($this->Settings->remove_expired) {
      $date = date('Y-m-d');
      $this->db->where('expiry <=', $date)->where('expiry !=', null)->where('expiry !=', '0000-00-00')->where('quantity_balance >', 0);
      $q = $this->db->get('purchase_items');
      if ($q->num_rows() > 0) {
        foreach (($q->result()) as $row) {
          $data[] = $row;
        }
        return $data;
      }
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

  private static function resetOrderRef() // OK
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
