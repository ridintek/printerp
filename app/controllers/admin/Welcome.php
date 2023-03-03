<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    try {
      $this->load->database();
    } catch (Exception $e) {
      die($e->getMessage());
    }

    if (!$this->loggedIn) {
      admin_redirect('login');
    }

    if ($this->Customer || $this->Supplier) {
      redirect_to('/');
    }

    $this->load->admin_model('db_model');
  }

  public function download($file)
  {
    if (file_exists('./files/' . $file)) {
      $this->load->helper('download');
      force_download('./files/' . $file, null);
      exit();
    }
    $this->session->set_flashdata('error', lang('file_x_exist'));
    redirect_to($_SERVER['HTTP_REFERER']);
  }

  public function hideAllNotifications()
  {
    $notifs = $this->site->getNotifications();
    if ($notifs) {
      foreach ($notifs as $notif) {
        $this->session->set_userdata('hidden' . $notif->id, 1);
      }
      echo 1;
    }
  }

  public function hideNotification($id = null)
  {
    $this->session->set_userdata('hidden' . $id, 1);
    echo true;
  }

  public function image_upload()
  {
    $this->security->csrf_verify();
    if (isset($_FILES['file'])) {
      $this->load->library('upload');
      $config['upload_path']   = 'assets/uploads/';
      $config['allowed_types'] = 'gif|jpg|png|jpeg';
      $config['max_size']      = '500';
      $config['max_width']     = $this->Settings->iwidth;
      $config['max_height']    = $this->Settings->iheight;
      $config['encrypt_name']  = true;
      $config['overwrite']     = false;
      $config['max_filename']  = 25;
      $this->upload->initialize($config);
      if (!$this->upload->do_upload('file')) {
        $error = $this->upload->display_errors();
        $error = ['error' => $error];
        sendJSON($error);
        exit;
      }
      $photo = $this->upload->file_name;
      $array = [
        'filelink' => base_url() . 'assets/uploads/images/' . $photo,
      ];
      echo stripslashes(json_encode($array));
      exit;
    } else {
      $error = ['error' => 'No file selected to upload!'];
      sendJSON($error);
      exit;
    }
  }

  /**
   * Load datatables data asynchronously
   */
  public function getTables()
  {
    if ($mode = getGET('mode')) {
      if ($mode == 'sales') {
        $this->data['sales'] = $this->db_model->getLatestSales();

        if ($sync = getGET('sync')) {
          if ($sync == 'sales') {
            foreach ($this->data['sales'] as $sale) {
              $this->site->syncSales(['sale_id' => $sale->id]);
            }

            $this->data['sales'] = $this->db_model->getLatestSales();
          }
        }
      } else if ($mode == 'purchases') {
        $this->data['purchases'] = $this->db_model->getLatestPurchases();
      } else if ($mode == 'transfers') {
        $this->data['transfers'] = $this->db_model->getLatestTransfers();
      }
    }
  }

  public function index()
  {
    // if ($this->Owner) die('NOP');
    $noChart = (getGET('nochart') ?? 0);

    if (!$this->isLocal)
      $this->data['sales'] = []; //$this->db_model->getLatestSales();

    if ($sync = getGET('sync')) {
      if ($sync == 'sales') {
        foreach ($this->data['sales'] as $sale) {
          $this->site->syncSales(['sale_id' => $sale->id]);
        }

        if (!$this->isLocal)
          $this->data['sales'] = $this->db_model->getLatestSales();
      }
    }

    $this->data['error']     = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['quotes']    = NULL;
    $this->data['customers'] = $this->db_model->getLatestCustomers();

    if ($this->isAdmin || getPermission('dashboard-chart')) {
      if (!$noChart) {
        $this->data['chartData'] = $this->db_model->getChartData();
        $this->data['stock']     = $this->db_model->getStockValue();
      } else {
        $this->data['chartData'] = NULL;
        $this->data['stock']     = NULL;
      }
    }
    if ($this->Admin || getPermission('suppliers-index')) {
      if (!$this->isLocal)
        $this->data['suppliers'] = NULL; // $this->db_model->getLatestSuppliers();
    }
    if ($this->Admin || getPermission('purchases-index')) {
      if (!$this->isLocal)
        $this->data['purchases'] = NULL; //$this->db_model->getLatestPurchases();
    }
    if ($this->Admin || getPermission('transfers-index')) {
      if (!$this->isLocal)
        $this->data['transfers'] = NULL; //$this->db_model->getLatestTransfers();
    }

    $this->data['bs']        = $this->db_model->getBestSeller();
    $lmsdate                 = date('Y-m-d', strtotime('first day of last month')) . ' 00:00:00';
    $lmedate                 = date('Y-m-d', strtotime('last day of last month')) . ' 23:59:59';
    $this->data['lmbs']      = $this->db_model->getBestSeller($lmsdate, $lmedate);
    $bc                      = [['link' => '#', 'page' => lang('dashboard')]];
    $meta                    = ['page_title' => lang('dashboard'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    if ($export = getGET('export')) {
      if ($export == 'sales') {
        $excel = $this->ridintek->spreadsheet();

        $excel->setTitle(lang('sales_dashboard'));
        $excel->SetCellValue('A1', lang('date'));
        $excel->SetCellValue('B1', lang('reference'));
        $excel->SetCellValue('C1', lang('customer'));
        $excel->SetCellValue('D1', lang('grand_total'));
        $excel->SetCellValue('E1', lang('status'));
        $excel->SetCellValue('F1', lang('biller'));
        $excel->SetCellValue('G1', lang('warehouse'));
        $excel->SetCellValue('H1', lang('due_date'));
        $excel->SetCellValue('I1', lang('pic'));
        $excel->SetCellValue('J1', lang('operator'));
        $excel->SetCellValue('K1', lang('production_status'));

        $r = 2;

        foreach ($this->data['sales'] as $sale) {
          $saleJS = getJSON($sale->json_data);
          $pic = $this->site->getUserByID($sale->created_by);
          $productionStatus = '';
          $operators = [];
          $saleItems = $this->site->getSaleItems(['sale_id' => $sale->id]);
          $isSaleCompleted = ($sale->status == 'completed' || $sale->status == 'delivered' ? TRUE : FALSE);

          foreach ($saleItems as $saleItem) {
            $saleItemJS = getJSON($saleItem->json_data);

            if (!empty($saleItemJS->operator_id)) {
              $operator = $this->site->getUserByID($saleItemJS->operator_id);
            } else {
              $operator = NULL;
            }

            $operatorName = ($operator ? $operator->fullname : '');

            if (!in_array($operatorName, $operators)) {
              $operators[] = $operatorName;
            }

            unset($saleItemJS, $operator, $operatorName);
          }

          if (!empty($saleJS->est_complete_date)) {
            if (!$isSaleCompleted) {
              if (strtotime(date('Y-m-d H:i:s')) > strtotime($saleJS->est_complete_date)) {
                $productionStatus = 'over_due';
              }
            }
          }

          $excel->setCellValue('A' . $r, $sale->date);
          $excel->setCellValue('B' . $r, $sale->reference);
          $excel->setCellValue('C' . $r, $sale->customer);
          $excel->setCellValue('D' . $r, filterDecimal($sale->grand_total));
          $excel->setCellValue('E' . $r, lang($sale->status));
          $excel->setCellValue('F' . $r, $sale->biller);
          $excel->setCellValue('G' . $r, $sale->warehouse);
          $excel->setCellValue('H' . $r, ($saleJS->est_complete_date ?? ''));
          $excel->setCellValue('I' . $r, $pic->fullname);
          $excel->setCellValue('J' . $r, implode(', ', $operators));
          $excel->setCellValue('K' . $r, $productionStatus);

          $r++;
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

        $excel->export('Sales_Dashboard-' . date('Ymd_His'));
      } else if ($export == 'purchases') {
      } else if ($export == 'transfers') {
        $excel = $this->ridintek->spreadsheet();

        $excel->setTitle(lang('transfer_dashboard'));
        $excel->SetCellValue('A1', lang('date'));
        $excel->SetCellValue('B1', lang('reference'));
        $excel->SetCellValue('C1', lang('from_warehouse'));
        $excel->SetCellValue('D1', lang('to_warehouse'));
        $excel->SetCellValue('E1', lang('status'));
        $excel->SetCellValue('F1', lang('sent_date'));
        $excel->SetCellValue('G1', lang('received_date'));
        $excel->SetCellValue('H1', lang('received_status'));
        $excel->SetCellValue('I1', lang('amount'));

        $r = 2;

        foreach ($this->data['transfers'] as $transfer) {
          $transferJS = getJSON($transfer->json);
          $receivedStatus = '';
          $sentDate     = ($transferJS->sent_date ?? NULL);
          $receivedDate = ($transferJS->received_date ?? NULL);


          if ($sentDate) {
            $compareDate = ($receivedDate ? strtotime($receivedDate) : now());

            if ($compareDate > strtotime('+2 hour', strtotime($sentDate))) {
              $receivedStatus = 'over_received';
            }
          }

          $excel->setCellValue('A' . $r, $transfer->date);
          $excel->setCellValue('B' . $r, $transfer->reference);
          $excel->setCellValue('C' . $r, $transfer->from_warehouse_name);
          $excel->setCellValue('D' . $r, $transfer->to_warehouse_name);
          $excel->setCellValue('E' . $r, lang($transfer->status));
          $excel->setCellValue('F' . $r, $sentDate);
          $excel->setCellValue('G' . $r, $receivedDate);
          $excel->setCellValue('H' . $r, lang($receivedStatus));
          $excel->setCellValue('I' . $r, filterDecimal($transfer->grand_total));

          $r++;
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

        $excel->export('Transfers_Dashboard-' . date('Ymd_His'));
      }
    }

    $this->page_construct('dashboard', $this->data);
  }

  public function language($lang = false)
  {
    if (getGET('lang')) {
      $lang = getGET('lang');
    }
    //$this->load->helper('cookie');
    $folder        = 'app/language/';
    $languagefiles = scandir($folder);
    if (in_array($lang, $languagefiles)) {
      $cookie = [
        'name'   => 'language',
        'value'  => $lang,
        'expire' => '31536000',
        'prefix' => 'sma_',
        'secure' => false,
      ];
      $this->input->set_cookie($cookie);
    }
    redirect_to($_SERVER['HTTP_REFERER']);
  }

  public function promotions()
  {
    $this->load->view($this->theme . 'promotions', $this->data);
  }

  public function set_data($ud, $value)
  {
    $this->session->set_userdata($ud, $value);
    echo true;
  }

  public function slug()
  {
    echo $this->sma->slug(getGET('title', true), getGET('type', true));
    exit();
  }

  public function toggle_rtl()
  {
    $cookie = [
      'name'   => 'rtl_support',
      'value'  => $this->Settings->user_rtl == 1 ? 0 : 1,
      'expire' => '31536000',
      'prefix' => 'sma_',
      'secure' => false,
    ];
    $this->input->set_cookie($cookie);
    redirect_to($_SERVER['HTTP_REFERER']);
  }
}
