<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Whatsapp extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }
  }

  public function add()
  {
    if ($this->requestMethod == 'POST') {
      $endClicks   = getPOST('end_click'); // Array
      $mcRejects   = getPOST('mc_reject'); // Array
      $dateTime    = ($this->isAdmin ? getPOST('date') : $this->serverDateTime);
      $date        = date('Y-m-d', strtotime($dateTime));
      $warehouseId = getPOST('warehouse');
      $note        = getPOST('note');

      $endClick = 0;
      $mcReject = 0;

      foreach ($endClicks as $ecl) {
        $endClick += filterDecimal($ecl);
      }

      foreach ($mcRejects as $orj) {
        $mcReject += (!empty($orj) ? filterDecimal($orj) : 0);
      }

      $product = $this->site->getProductByCode(getPOST('category'));

      if (!$product) {
        sendJSON(['success' => 0, 'message' => "POD Category harap dipilih."]);
      }

      if (!$endClick) {
        sendJSON(['success' => 0, 'message' => "End Click diisi angka lebih dari 0."]);
      }

      $tracks = $this->site->getTrackingPODs([
        'pod_id' => $product->id,
        'warehouse_id' => $warehouseId,
        'order' => ['created_at', 'DESC']
      ]);

      $lastTrack = ($tracks[0] ?? NULL);
      unset($tracks);

      // Get current today complete click from PrintERP data.
      $erpClick = 0;
      $warehouseProduct = $this->site->getWarehouseProduct($product->id, $warehouseId);
      $erpClick = ceil($warehouseProduct->quantity);

      if (!$lastTrack) { // For first time use, we tolerance the start click.
        $startClick = $erpClick;
      } else {
        $startClick = $lastTrack->end_click; // End Click as Start Click.
      }

      $costClick = ($product->code == 'KLIKPOD' ? 1000 : 300); // Else 300 for KLIKPODBW.
      $tolerance = ($product->code == 'KLIKPOD' ? 10 : 10); // Else 10% for KLIKPODBW.

      if ($endClick < $startClick) {
        sendJSON([
          'success' => 0,
          'message' => "End Click ({$endClick}) tidak boleh kurang dari Start Click ({$startClick})."
        ]);
      }

      $trackData = [
        'pod_id'       => $product->id,
        'start_click'  => $startClick,
        'end_click'    => $endClick,
        'mc_reject'    => $mcReject,
        'erp_click'    => $erpClick,
        'tolerance'    => $tolerance,
        'cost_click'   => $costClick,
        'warehouse_id' => $warehouseId,
        'note'         => htmlEncode($note),
        'created_at'   => $dateTime,
        'created_by'   => getPOST('created_by')
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          sendJSON(['success' => 0, 'message' => 'Attachment tidak boleh lebih dari 2MB.']);
        }

        $trackData['attachment_id'] = $uploader->storeRandom();
      } else {
        sendJSON(['success' => 0, 'message' => 'Attachment berupa foto display mesin POD dibutuhkan.']);
      }

      if ($this->site->addTrackingPOD($trackData)) {
        sendJSON(['success' => 1, 'message' => 'Berhasil menambahkan Tracking POD.']);
      } else {
        sendJSON(['success' => 0, 'message' => getLastError()]);
      }
    }

    $this->load->view($this->theme . 'trackingpod/add', $this->data);
  }

  public function delete($jobId = NULL)
  {
    $jobIds = getPOST('val');

    if (!$this->isAdmin && !getPermission('wajob-delete')) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if ($jobIds && is_array($jobIds)) {
      foreach ($jobIds as $jobId) {
        $this->site->deleteWAJob($jobId);
      }
      sendJSON(['success' => 1, 'message' => 'Berhasil menghapus pesan yang terpilih.']);
    } else if ($jobId) {
      if ($this->site->deleteWAJob($jobId)) {
        sendJSON(['success' => 1, 'message' => 'Berhasil menghapus pesan.']);
      }
    }

    sendJSON(['success' => 0, 'message' => 'Gagal menghapus pesan.']);
  }

  /**
   * TRYING TO IMPLEMENT
   */
  public function edit($trackId)
  {
    checkPermission('trackingpod-edit');

    $track = $this->site->getTrackingPODByID($trackId);

    if ($this->requestMethod == 'POST') {
      $endClicks   = getPOST('end_click'); // Array
      $mcRejects   = getPOST('mc_reject'); // Array
      $erpClick    = filterDecimal(getPOST('erp_click'));
      $dateTime    = ($this->isAdmin ? getPOST('date') : $this->serverDateTime);
      $warehouseId = getPOST('warehouse');
      $note        = getPOST('note');

      $endClick = 0;
      $mcReject = 0;

      foreach ($endClicks as $ecl) {
        $endClick += filterDecimal($ecl);
      }

      foreach ($mcRejects as $orj) {
        $mcReject += (!empty($orj) ? filterDecimal($orj) : 0);
      }

      $product = $this->site->getProductByCode(getPOST('category'));

      if (!$product) {
        sendJSON(['success' => 0, 'message' => "POD Category harap dipilih."]);
      }

      if (!$endClick) {
        sendJSON(['success' => 0, 'message' => "End Click diisi angka lebih dari 0."]);
      }

      $trackData = [
        'pod_id'       => $product->id,
        // 'start_click'  => $startClick,
        'end_click'    => $endClick,
        'mc_reject'    => $mcReject,
        'erp_click'    => $erpClick,
        // 'tolerance'    => $tolerance,
        // 'cost_click'   => $costClick,
        'warehouse_id' => $warehouseId,
        'note'         => htmlEncode($note),
        'created_at'   => $dateTime,
        'created_by'   => getPOST('created_by')
      ];

      // print_r($trackData); die();

      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          sendJSON(['success' => 0, 'message' => 'Attachment tidak boleh lebih dari 2MB.']);
        }

        $trackData['attachment_id'] = $uploader->storeRandom();
      }

      if ($this->site->updateTrackingPOD($trackId, $trackData)) {
        sendJSON(['success' => 1, 'message' => 'Berhasil mengubah Tracking POD.']);
      } else {
        sendJSON(['success' => 0, 'message' => getLastError()]);
      }
    }

    $this->data['product'] = $this->site->getProductByID($track->pod_id);

    $this->data['track'] = $track;

    $this->load->view($this->theme . 'trackingpod/edit', $this->data);
  }


  public function getMessages()
  {
    $this->load->library('datatable');

    $this->datatable
      ->select("wa_job.id AS id, wa_job.id AS pid, wa_engine, wa_job.phone AS phone, message,
        send_date, wa_job.status AS status, reason, creator.first_name AS creator_name")
      ->from('wa_job')
      ->join('users creator', 'creator.id = wa_job.created_by', 'left')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}whatsapp/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Whatsapp\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}whatsapp/view/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"View Details\">
                <i class=\"fad fa-fw fa-chart-bar\"></i>
            </a>
          </div>";
      })
      ->editColumn('message', function ($data) {
        return str_replace("\n", '<br>', $data['message']);
      });

    $this->datatable->generate();
  }

  public function getProfiles()
  {
    $this->load->library('datatable');

    $this->datatable
      ->select("wa_profile.id AS id, wa_profile.id AS pid, engine, api_key, device_id,
        wa_profile.status, wa_profile.created_at, creator.fullname AS creator_name")
      ->from('wa_profile')
      ->join('users creator', 'creator.id = wa_profile.created_by', 'left')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}whatsapp/profile/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Whatsapp Profile\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}whatsapp/profile/view/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"View Details\">
                <i class=\"fad fa-fw fa-chart-bar\"></i>
            </a>
          </div>";
      });

    $this->datatable->generate();
  }

  public function index()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Whatsapp']
    ];
    $meta['page_title'] = 'Sent Messages';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('whatsapp/index', $this->data);
  }

  public function profile()
  {
    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }

    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('whatsapp'), 'page' => 'Whatsapp'],
      ['link' => '#', 'page' => 'Whatsapp Profile']
    ];
    $meta['page_title'] = 'Whatsapp Profile';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('whatsapp/profile/index', $this->data);
  }

  protected function profile_add()
  {
    if ($this->requestMethod == 'POST') {

    }

    $this->load->view($this->theme . 'whatsapp/profile/add', $this->data);
  }

  public function sync($trackId = NULL, $noReturn = FALSE)
  {
    $tracks = [];

    if ($trackId) {
      $tracks[] = $this->site->getTrackingPODByID($trackId);
    } else {
      $tracks = $this->site->getTrackingPODs();
    }

    if ($tracks) {
      $failed  = 0;
      $success = 0;

      foreach ($tracks as $track) {
        $res = $this->site->updateTrackingPOD($track->id, [
          'start_click' => $track->start_click,
          'end_click' => $track->end_click,
          'mc_reject' => $track->mc_reject
        ]);

        if ($res) {
          $success++;
        } else {
          $failed++;
        }
      }

      if (!$noReturn) sendJSON(['success' => 1, 'message' => "Tracking PODs {$success} updated and {$failed} failed."]);
    }
    if (!$noReturn) sendJSON(['success' => 0, 'message' => 'Failed to sync Tracking PODs.']);
  }

  public function view($trackId)
  {
    // $this->sync($trackId, TRUE);
    $track = $this->site->getTrackingPODByID($trackId);
    $this->data['track'] = $track;

    $this->load->view($this->theme . 'trackingpod/view', $this->data);
  }
}