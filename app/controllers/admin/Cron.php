<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cron extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->admin_model('cron_model');

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
      $this->cron_model->run_monthly();
    } else if ($mode == 'weekly') {
      $this->cron_model->run_weekly();
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
}
