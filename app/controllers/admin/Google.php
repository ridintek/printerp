<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Google extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }
  }

  public function review()
  {
    if ($args = func_get_args()) {
      $method = __FUNCTION__ . '_' . $args[0];
      if (method_exists($this, $method)) {
        array_shift($args);
        return call_user_func_array([$this, $method], $args);
      }
    }

    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Google Review']
    ];
    $meta['page_title'] = 'Google Review';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('google/review/index', $this->data);
  }

  public function review_add()
  {
    checkPermission('googlereview-add');

    if ($this->requestMethod == 'POST') {
      $billerId       = getPOST('biller');
      $picId          = getPOST('pic');
      $customerName   = getPOST('customer_name');
      $status         = getPOST('status');
      $createdAt      = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $createdBy      = getPOST('created_by');

      if (empty($customerName)) $this->response(400, ['message' => 'Nama pelanggan harus diisi.']);

      $reviewData = [
        'pic_id'          => $picId,
        'biller_id'       => $billerId,
        'customer_name'   => $customerName,
        'status'          => $status,
        'created_at'      => $createdAt,
        'created_by'      => $createdBy
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment') && !$uploader->isMoved()) {
        if ($uploader->getSize('mb') > 2) {
          $this->response(400, ['message' => "Ukuran Attachment tidak boleh lebih dari 2MB."]);
        }

        if ($attachmentId = $uploader->storeRandom()) {
          $reviewData['attachment_id'] = $attachmentId;
        }
      }

      if (GoogleReview::add($reviewData)) {
        $this->response(201, ['message' => 'Berhasil menambahkan Google Review.']);
      } else {
        $this->response(400, ['message' => getLastError()]);
      }
    }

    $this->load->view($this->theme . 'google/review/add', $this->data);
  }

  public function review_delete($reviewId = NULL)
  {
    $reviewIds = getPOST('val');

    if (!$this->isAdmin && !getPermission('googlereview-delete')) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if ($reviewIds && is_array($reviewIds)) {
      foreach ($reviewIds as $reviewId) {
        $review = GoogleReview::getRow(['id' => $reviewId]);

        if (GoogleReview::delete(['id' => $reviewId])) {
          if ($review->attachment_id) {
            Attachment::delete(['id' => $review->attachment_id]);
          }
        }
      }
      $this->response(200, ['message' => 'Berhasil menghapus Google Review yang terpilih.']);
    } else if ($reviewId) {
      $review = GoogleReview::getRow(['id' => $reviewId]);

      if (GoogleReview::delete(['id' => $reviewId])) {
        if ($review->attachment_id) {
          Attachment::delete(['id' => $review->attachment_id]);
        }
        $this->response(200, ['message' => 'Berhasil menghapus Google Review.']);
      }
    }

    $this->response(400, ['message' => 'Gagal menghapus Google Review.']);
  }

  public function review_edit($reviewId = NULL)
  {
    checkPermission('googlereview-edit');

    $review = GoogleReview::getRow(['id' => $reviewId]);

    if (!$review) {
      $this->response(404, ['message' => 'Google Review not found.']);
    }

    if ($this->requestMethod == 'POST') {
      $billerId       = getPOST('biller');
      $picId          = getPOST('pic');
      $customerName   = getPOST('customer_name');
      $status         = getPOST('status');
      $createdAt      = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $createdBy      = getPOST('created_by');

      if (empty($customerName)) $this->response(400, ['message' => 'Nama pelanggan harus diisi.']);

      $reviewData = [
        'pic_id'          => $picId,
        'biller_id'       => $billerId,
        'customer_name'   => $customerName,
        'status'          => $status,
        'created_at'      => $createdAt,
        'created_by'      => $createdBy
      ];

      $uploader = new FileUpload();

      if ($uploader->has('attachment') && !$uploader->isMoved()) {
        if ($uploader->getSize('mb') > 2) {
          $this->response(400, ['message' => "Ukuran Attachment tidak boleh lebih dari 2MB."]);
        }

        if ($attachmentId = $uploader->storeRandom()) {
          $reviewData['attachment_id'] = $attachmentId;
        }
      }

      if (GoogleReview::update($reviewId, $reviewData)) {
        $this->response(200, ['message' => 'Berhasil mengubah Google Review.']);
      } else {
        $this->response(400, ['message' => 'Failed update: ' . getLastError()]);
      }
    }

    $this->data['review'] = $review;

    $this->load->view($this->theme . 'google/review/edit', $this->data);
  }

  public function review_view($reviewId)
  {
    $review = GoogleReview::getRow(['id' => $reviewId]);

    $this->data['review'] = $review;

    $this->load->view($this->theme . 'google/review/view', $this->data);
  }

  public function getGoogleReviews()
  {
    $startDate  = getPOST('start_date');
    $endDate    = getPOST('end_date');
    $billers = [];

    $this->load->library('datatable');

    if ($billerId = $this->session->userdata('biller_id')) {
      $billers[] = $billerId;
    }

    $this->datatable
      ->select("google_review.id AS id, google_review.id AS pid, billers.name AS biller,
        pic.fullname AS pic, google_review.customer_name, google_review.status,
        google_review.created_at, creator.fullname AS creator,
        google_review.attachment_id")
      ->from('google_review')
      ->join('users creator', 'creator.id = google_review.created_by', 'left')
      ->join('users pic', 'pic.id = google_review.pic_id', 'left')
      ->join('billers', 'billers.id = google_review.biller_id', 'left')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}google/review/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Google Review\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}google/review/edit/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"Edit Google Review\">
                <i class=\"fad fa-fw fa-edit\"></i>
            </a>
            <a href=\"{$this->theme}google/review/view/{$data['id']}\"
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

    if ($billers) {
      foreach ($billers as $bl) {
        $this->datatable->or_where('google_review.biller_id', $bl);
      }
    }

    if ($startDate) {
      $this->datatable->where("google_review.created_at >= '{$startDate} 00:00:00'");
    }
    if ($endDate) {
      $this->datatable->where("google_review.created_at <= '{$startDate} 23:59:59'");
    }

    $this->datatable->generate();
  }

  public function index()
  {
  }
}
