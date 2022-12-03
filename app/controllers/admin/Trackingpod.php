<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Trackingpod extends MY_Controller
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
        // sendJSON([
        //   'success' => 0,
        //   'message' => "End Click ({$endClick}) tidak boleh kurang dari Start Click ({$startClick})."
        // ]);
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

      if ($uploader->has('attachment') && !$uploader->isMoved()) {
        if ($uploader->getSize('mb') > 2) {
          sendJSON([
            'success' => 0,
            'message' => "Ukuran Attachment tidak boleh lebih dari 2MB."
          ]);
        }

        $ocr = ocr($uploader->getTempName());
        $fullColor = 0;

        if ($ocr) {
          // Multi Full Color Counter in one image. Must be Vertical Ordered.
          for ($x = 0; $x < count($ocr); $x++) {
            if (strcasecmp($ocr[$x], 'Full Color Counter') === 0) {
              $fullColor += filterDecimal($ocr[$x + 1]);
            }
          }
        }

        if ($endClick != $fullColor) {
          sendJSON([
            'success' => 0,
            'message' => "End Click ($endClick) tidak sesuai attachment Full Color Counter ($fullColor)."
          ]);
        }

        if ($attachmentId = $uploader->storeRandom()) {
          $trackData['attachment_id'] = $attachmentId;
        } else {
          sendJSON(['success' => 0, 'message' => 'Attachment gagal di upload.']);
        }
      } else {
        if (strip_tags($note) != '-')
          sendJSON(['success' => 0, 'message' => 'Attachment berupa foto display mesin POD dibutuhkan.']);
      }

      if ($this->site->addTrackingPOD($trackData)) {
        if ($endClick < $startClick) {
          sendJSON([
            'success' => 1,
            'message' => "PERHATIAN! End Click ({$endClick}) kurang dari Start Click ({$startClick})."
          ]);
        } else {
          sendJSON(['success' => 1, 'message' => 'Berhasil menambahkan Tracking POD.']);
        }
      } else {
        sendJSON(['success' => 0, 'message' => getLastError()]);
      }
    }

    $this->load->view($this->theme . 'trackingpod/add', $this->data);
  }

  public function delete($trackId = NULL)
  {
    $trackIds = getPOST('val');

    if (!$this->isAdmin && !getPermission('trackingpod-delete')) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if ($trackIds && is_array($trackIds)) {
      foreach ($trackIds as $trackId) {
        $this->site->deleteTrackingPOD($trackId);
      }
      sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Tracking POD yang terpilih.']);
    } else if ($trackId) {
      if ($this->site->deleteTrackingPOD($trackId)) {
        sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Tracking POD.']);
      }
    }

    sendJSON(['success' => 0, 'message' => 'Gagal menghapus Tracking POD.']);
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

      if ($uploader->has('attachment') && !$uploader->isMoved()) {
        checkPath($this->upload_trackingpod_path);

        $attachment = $uploader->getRandomName();

        if ($uploader->move($this->upload_trackingpod_path, $attachment)) {
          $trackData['attachment'] = $attachment;
        } else {
          sendJSON(['success' => 0, 'message' => 'Attachment gagal di upload.']);
        }
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


  public function getTrackingPODs()
  {
    $startDate  = getPOST('start_date');
    $endDate    = getPOST('end_date');
    $warehouses = [];

    $this->load->library('datatable');

    if ($whId = $this->session->userdata('warehouse_id')) {
      $warehouses[] = $whId;
    }

    $this->datatable
      ->select("trackingpod.id AS id, trackingpod.id AS pid, products.code AS category,
        trackingpod.start_click, trackingpod.end_click, trackingpod.usage_click,
        (trackingpod.mc_reject + trackingpod.op_reject) AS total_reject,
        trackingpod.erp_click, trackingpod.balance, warehouses.name AS warehouse_name,
        trackingpod.created_at, users.fullname AS creator,
        trackingpod.attachment_id")
      ->from('trackingpod')
      ->join('products', 'products.id = trackingpod.pod_id', 'left')
      ->join('users', 'users.id = trackingpod.created_by', 'left')
      ->join('warehouses', 'warehouses.id = trackingpod.warehouse_id', 'left')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}trackingpod/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Tracking POD\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}trackingpod/edit/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"Edit TrackingPOD\">
                <i class=\"fad fa-fw fa-edit\"></i>
            </a>
            <a href=\"{$this->theme}trackingpod/view/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"View Details\">
                <i class=\"fad fa-fw fa-chart-bar\"></i>
            </a>
          </div>";
      })
      ->editColumn('attachment_id', function ($data) {
        return "<div class=\"text-center\">
          <a href=\"#\" data-remote=\"" .
          admin_url('gallery/attachment/' . $data['attachment_id'] . "?modal=1") . "\"
            data-toggle=\"modal\" data-modal-class=\"modal-lg\" data-target=\"#myModal\">
          <i class=\"fad fa-file-download\"></i>
        </a>
      </div>";
      });

    if ($warehouses) {
      foreach ($warehouses as $wh) {
        $this->datatable->or_where('trackingpod.warehouse_id', $wh);
      }
    }

    if ($startDate) {
      $this->datatable->where("trackingpod.created_at >= '{$startDate} 00:00:00'");
    }
    if ($endDate) {
      $this->datatable->where("trackingpod.created_at <= '{$startDate} 23:59:59'");
    }

    $this->datatable->generate();
  }

  public function index()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Tracking POD']
    ];
    $meta['page_title'] = 'Tracking POD';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('trackingpod/index', $this->data);
  }

  /**
   * Sync TrackingPOD.
   */
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
    $this->sync($trackId, TRUE);
    $track = $this->site->getTrackingPODByID($trackId);
    $whProduct = $this->site->getWarehouseProduct($track->pod_id, $track->warehouse_id);

    $this->data['track'] = $track;
    $this->data['klikpod_qty'] = ($whProduct ? $whProduct->quantity : 0);

    $this->load->view($this->theme . 'trackingpod/view', $this->data);
  }
}
