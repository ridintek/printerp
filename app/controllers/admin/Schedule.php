<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Schedule extends MY_Controller
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
      $sun = getPOST('sun');
      $mon = getPOST('mon');
      $tue = getPOST('tue');
      $wed = getPOST('wed');
      $thu = getPOST('thu');
      $fri = getPOST('fri');
      $sat = getPOST('sat');

      $billers = implode(',', (getPOST('biller') ?? []));

      $scheduleData = [
        'billers'  => $billers,
        'hour_sun' => implode('-', $sun),
        'hour_mon' => implode('-', $mon),
        'hour_tue' => implode('-', $tue),
        'hour_wed' => implode('-', $wed),
        'hour_thu' => implode('-', $thu),
        'hour_fri' => implode('-', $fri),
        'hour_sat' => implode('-', $sat),
      ];

      if ($this->site->addSchedule($scheduleData)) {
        $this->response(201, ['message' => 'Schedule berhasil dibuat.']);
      }

      $this->response(400, ['message' => 'Schedule gagal dibuat.']);
    }

    $this->load->view($this->theme . 'schedule/add', $this->data);
  }

  public function delete($scheduleId = NULL)
  {
    $scheduleIds = getPOST('val');

    if (!getPermission('schedule-delete')) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if ($scheduleIds && is_array($scheduleIds)) {
      foreach ($scheduleIds as $scheduleId) {
        $this->site->deleteSchedule(['id' => $scheduleId]);
      }
      sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Schedule yang terpilih.']);
    } else if ($scheduleId) {
      if ($this->site->deleteSchedule(['id' => $scheduleId])) {
        sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Schedule.']);
      }
    }

    sendJSON(['success' => 0, 'message' => 'Gagal menghapus Schedule.']);
  }

  public function edit($scheduleId = NULL)
  {
    if ($this->requestMethod == 'POST') {
      $sun = getPOST('sun');
      $mon = getPOST('mon');
      $tue = getPOST('tue');
      $wed = getPOST('wed');
      $thu = getPOST('thu');
      $fri = getPOST('fri');
      $sat = getPOST('sat');

      $billers = implode(',', (getPOST('biller') ?? []));

      $scheduleData = [
        'billers'  => $billers,
        'hour_sun' => implode('-', $sun),
        'hour_mon' => implode('-', $mon),
        'hour_tue' => implode('-', $tue),
        'hour_wed' => implode('-', $wed),
        'hour_thu' => implode('-', $thu),
        'hour_fri' => implode('-', $fri),
        'hour_sat' => implode('-', $sat),
      ];

      if ($this->site->updateSchedule($scheduleId, $scheduleData)) {
        $this->response(200, ['message' => 'Schedule berhasil diubah.']);
      }

      $this->response(400, ['message' => 'Schedule gagal diubah.']);
    }

    $this->data['schedule'] = $this->site->getSchedule(['id' => $scheduleId]);

    $this->load->view($this->theme . 'schedule/edit', $this->data);
  }

  public function getHolidays()
  {
    $this->load->library('datatable');

    $billers = []; // by Name

    if ($billerId = XSession::get('biller_id')) {
      $billers[] = $this->site->getBiller(['id' => $billerId])->name;
    }

    $this->datatable
      ->select("id AS id, id AS pid, billers, description, start_date, end_date, start_work, end_work")
      ->from('holiday')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}schedule/holiday/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Holiday\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}schedule/holiday/edit/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"Edit Holiday\">
                <i class=\"fad fa-fw fa-edit\"></i>
            </a>
          </div>";
      })
      ->editColumn('billers', function ($data) {
        $billerIds = explode(',', $data['billers']);
        $billers = [];

        foreach ($billerIds as $billerId) {
          if ($billerId) $billers[] = $this->site->getBiller(['id' => $billerId])->name;
        }

        return implode(', ', $billers);
      })
      ->editColumn('description', function ($data) {
        return htmlDecode($data['description']);
      });

    if ($billers) {
      $this->datatable->where_in('billers', $billers);
    }

    $this->datatable->generate();
  }

  public function getSchedules()
  {
    $this->load->library('datatable');

    $billers = [];

    if ($billerId = XSession::get('biller_id')) {
      $billers[] = $billerId;
    }

    $this->datatable
      ->select("id AS id, id AS pid, billers, valid_date,
        hour_sun, hour_mon, hour_tue, hour_wed, hour_thu, hour_fri, hour_sat")
      ->from('schedule')
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}schedule/delete/{$data['id']}\"
              class=\"tip \"
              data-action=\"confirm\" style=\"color:red;\" title=\"Delete Schedule\">
                <i class=\"fad fa-fw fa-trash\"></i>
            </a>
            <a href=\"{$this->theme}schedule/edit/{$data['id']}\"
              class=\"tip\"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              title=\"Edit Schedule\">
                <i class=\"fad fa-fw fa-edit\"></i>
            </a>
          </div>";
      })
      ->editColumn('billers', function($data) {
        $billerIds = explode(',', $data['billers']);
        $billers = [];

        foreach ($billerIds as $billerId) {
          if ($billerId) $billers[] = $this->site->getBiller(['id' => $billerId])->name;
        }

        return implode(', ', $billers);
      });

    if ($billers) {
      $this->datatable->where_in('billers', $billers);
    }

    $this->datatable->generate();
  }

  public function index()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => 'Schedule']
    ];
    $meta['page_title'] = 'Schedule';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('schedule/index', $this->data);
  }

  public function holiday()
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
      ['link' => admin_url('schedule'), 'page' => 'Schedule'],
      ['link' => '#', 'page' => 'Holiday']
    ];
    $meta['page_title'] = 'Holiday';
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('schedule/holiday/index', $this->data);
  }

  public function holiday_add()
  {
    if ($this->requestMethod == 'POST') {
      $billers = implode(',', (getPOST('biller') ?? []));
      $holiday = getPOST('holiday');
      $working = getPOST('working');

      $holidayData = [
        'billers'     => $billers,
        'description' => htmlEncode(getPOST('description')),
        'start_date'  => (!empty($holiday[0]) ? $holiday[0] : NULL),
        'end_date'    => (!empty($holiday[1]) ? $holiday[1] : NULL),
        'start_work'  => (!empty($working[0]) ? $working[0] : NULL),
        'end_work'    => (!empty($working[1]) ? $working[1] : NULL),
      ];

      if ($this->site->addHoliday($holidayData)) {
        $this->response(201, ['message' => 'Holiday berhasil dibuat.']);
      }

      $this->response(400, ['message' => 'Holiday gagal dibuat.']);
    }

    $this->load->view($this->theme . 'schedule/holiday/add', $this->data);
  }

  public function holiday_delete($holidayId = NULL)
  {
    $holidayIds = getPOST('val');

    if (!$this->isAdmin && !getPermission('holiday-delete')) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if ($holidayIds && is_array($holidayIds)) {
      foreach ($holidayIds as $holidayId) {
        $this->site->deleteHoliday(['id' => $holidayId]);
      }
      sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Holiday yang terpilih.']);
    } else if ($holidayId) {
      if ($this->site->deleteHoliday(['id' => $holidayId])) {
        sendJSON(['success' => 1, 'message' => 'Berhasil menghapus Holiday.']);
      }
    }

    sendJSON(['success' => 0, 'message' => 'Gagal menghapus Holiday.']);
  }

  public function holiday_edit($holidayId = NULL)
  {
    if ($this->requestMethod == 'POST') {
      $billers = implode(',', (getPOST('biller') ?? []));
      $holiday = getPOST('holiday');
      $working = getPOST('working');

      $holidayData = [
        'billers'     => $billers,
        'description' => htmlEncode(getPOST('description')),
        'start_date'  => (!empty($holiday[0]) ? $holiday[0] : NULL),
        'end_date'    => (!empty($holiday[1]) ? $holiday[1] : NULL),
        'start_work'  => (!empty($working[0]) ? $working[0] : NULL),
        'end_work'    => (!empty($working[1]) ? $working[1] : NULL),
      ];

      if ($this->site->updateHoliday($holidayId, $holidayData)) {
        $this->response(200, ['message' => 'Schedule berhasil diubah.']);
      }

      $this->response(400, ['message' => 'Schedule gagal diubah.']);
    }

    $this->data['holiday'] = $this->site->getHoliday(['id' => $holidayId]);

    $this->load->view($this->theme . 'schedule/holiday/edit', $this->data);
  }
}