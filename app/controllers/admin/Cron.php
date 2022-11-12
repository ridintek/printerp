<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    $this->rdlog->setFileName('cron');
  }

  public function index()
  {
    show_404();
  }

  public function test()
  {
    $date = date('Y-m-d H:i:s');
    $hfile = fopen(FCPATH . 'cron_job.txt', 'a');
    fwrite($hfile, '[' . $date .  '] Success' . "\r\n");
    fclose($hfile);
    echo ('OK');
  }

  public function run($mode)
  {
    $type = strtoupper($mode);

    $this->rdlog->info("[CRONJOB {$type}]");

    if ($mode == 'daily') {
      CronModel::runDaily();
    } else if ($mode == 'monthly') {
    } else if ($mode == 'weekly') {
    } else if ($mode == 'test') {
      echo "Cronjob Test\r\n";
    }

    $this->rdlog->info("[/CRONJOB {$type}]");
  }

  public function run_1()
  { // interval 1 minutes
    $filename = FCPATH . 'cron1.sqlite';

    $hMutex = mutexCreate('cron1', TRUE);

    $hSQL = new SQLite3($filename);

    if ($hSQL) {
      if (filesize($filename) === 0) {
        $hSQL->exec("CREATE TABLE sales (id INT, status TEXT)");

        $sales = $this->site->getAllSales();

        foreach ($sales as $sale) {
          $hSQL->exec("INSERT INTO sales (id, status) VALUES ({$sale->id}, 'pending')");
        }
      } else {
        $q = $hSQL->query("SELECT * FROM sales");
      }

      $hSQL->close();
    }

    mutexRelease($hMutex);
  }

  public function sync($type = 'all')
  {
    if ($type == 'all' || $type == 'products') {
      if ($total = CronModel::syncProducts()) {
        log_message('info', sprintf("%d products have been synced successfully.", $total));
      }
    } else if ($type == 'all' || $type == 'paymentValidations') {
      if ($this->site->syncPaymentValidations()) {
        log_message('info', 'Payment validations have been synced successfully.');
      }
    } else if ($type == 'all' || $type == 'resetOrderRef') {
      if (CronModel::resetOrderRef()) { // OK
        log_message('info', lang('order_ref_updated'));
      }
    } else if ($type == 'all' || $type == 'clearSessionStorage') {
      if ($sess = $this->site->clearSessionStorage()) {
        log_message('info', sprintf('%d session have been deleted.', $sess));
      }
    } else if ($type == 'all' || $type == 'clearOldWAJobs') {
      if ($jobs = $this->site->clearOldWAJobs()) {
        log_message('info', sprintf('%d WA Jobs have been deleted.', $jobs));
      }
    } else if ($type == 'all' || $type == 'safetyStock') {
      if ($ss = CronModel::syncSafetyStock()) {
        foreach ($ss as $msg) {
          log_message('info', $msg);
        }
      }
    } else if ($type == 'all' || $type == 'clearEmptyPayments') {
      if ($stats = $this->site->clearEmptyPayments()) {
        foreach ($stats as $stat) {
          log_message('info', sprintf("Payment for [%s id:%d] has been deleted.", $stat['type'], $stat['id']));
        }
      }
    }
  }
}
