<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Qms extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }
  }

  /**
   * Customer will call this when click register.
   */
  public function addQueueTicket()
  {
    $name         = getPOST('name');
    $phone        = getPOST('phone');
    $category_id  = getPOST('category');
    $warehouse_id = getPOST('warehouse');

    $ticket_data = [
      'name'  => $name,
      'phone' => $phone,
      'queue_category_id' => $category_id,
      'warehouse_id'      => $warehouse_id
    ];

    if ($newTicket = $this->Qms_model->addQueueTicket($ticket_data)) {
      sendJSON(['error' => 0, 'data' => $newTicket]);
    } else {
      sendJSON(['error' => 1, 'msg' => 'Cannot create ticket']);
    }
  }

  /**
   * Counter will call this when call.
   */
  public function callQueue($warehouse_id = NULL)
  {
    $call_data = [
      'user_id' => XSession::get('user_id'),
      'counter' => XSession::get('counter'),
      'warehouse_id' => $warehouse_id
    ];

    if ($response = $this->Qms_model->callQueue($call_data)) {
      sendJSON(['error' => 0, 'data' => $response]);
    }

    sendJSON(['error' => 1, 'msg' => 'No queue list available.']);
  }

  public function counter()
  {
    $meta = [
      'page_title' => lang('counter'),
      'bc' => [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => '#', 'page' => 'QMS'],
        ['link' => '#', 'page' => lang('counter')]
      ]
    ];
    $this->data = array_merge($this->data, $meta);

    $warehouse_id = (XSession::get('warehouse_id') ?? $this->Settings->default_warehouse);

    $this->data['warehouse'] = $this->site->getWarehouseByID($warehouse_id);

    $this->page_construct('qms/counter', $this->data);
  }

  public function delete($ticketId)
  {
    if (!$this->isAdmin) sendJSON(['success' => 0, 'message' => lang('access_denied')]);

    if ($this->Qms_model->deleteQueue($ticketId)) {
      sendJSON(['success' => 1, 'message' => 'Queue deleted successfully.']);
    }
    sendJSON(['success' => 0, 'message' => 'Failed to delete queue.']);
  }

  public function display($warehouse_id = NULL)
  {
    $active_display = (getGET('active') == 1 ? 1 : 0);
    $warehouse_id = ($warehouse_id ?? $this->Settings->default_warehouse);

    $this->data['active_display'] = $active_display;
    $this->data['warehouse'] = $this->site->getWarehouseByID($warehouse_id);

    $this->load->view($this->theme . 'qms/display', $this->data);
  }

  /**
   * Display will call this function if any ticket to be call.
   */
  public function displayResponse($ticket_id = NULL)
  {
    if ($ticket_id) {
      if ($this->Qms_model->updateQueueTicket($ticket_id, ['status' => 3])) {
        sendJSON(['error' => 0, 'msg' => 'Update success.']);
      } else {
        sendJSON(['error' => 1, 'msg' => 'Update failed.']);
      }
    }
    sendJSON(['error' => 1, 'msg' => 'Ticket id is not specified.']);
  }

  public function edit($queue_id)
  {
    sendJSON(['error' => 1, 'msg' => 'No Authorized']);
  }

  public function endQueue()
  {
    $ticket_id = getPOST('ticket');

    $queueData = [
      'serve_time' => (getPOST('serve_time') ?? NULL)
    ];

    if ($this->Qms_model->endQueue($ticket_id, $queueData)) {
      sendJSON(['error' => 0, 'msg' => 'OK']);
    }
    sendJSON(['error' => 1, 'msg' => 'Cannot end queue ticket.']);
  }

  /**
   * Auto-completion for register. Client side using select2 javascript framework library.
   */
  public function getCustomers()
  {
    $phone = getGET('q');

    $customers = $this->site->getCustomersByPhone($phone);
    $data = [];

    if ($customers) {
      foreach ($customers as $customer) {
        $data[] = ['id' => $customer->phone, 'text' => $customer->phone, 'name' => $customer->name];
      }
    }

    sendJSON([
      'results' => $data
    ]);
  }

  /**
   * Display will call this function intervally.
   */
  public function getDisplayData($warehouse_id = NULL)
  {
    if ($warehouse_id) {
      $display_data = [
        'call'       => [],
        'counter'    => [],
        'queue_list' => [],
        'skip_list'  => []
      ];

      $hMutex = mutexCreate('QMS_getDisplayData', TRUE);

      $call = $this->Qms_model->getTodayCallableQueueTicket($warehouse_id);

      if ($call) {
        $display_data['call'] = ['error' => 0, 'data' => $call];
      } else {
        $display_data['call'] = ['error' => 1, 'data' => NULL, 'msg' => 'No queue ticket to call.'];
      }

      $counters = $this->Qms_model->getTodayOnlineCounters($warehouse_id);

      if ($counters) {
        foreach ($counters as $counter) {
          $queue_category = $this->Qms_model->getQueueCategoryByID($counter->queue_category_id);

          $counter_list[] = [
            'counter' => $counter->counter,
            'name' => explode(' ', $counter->fullname)[0],
            'token' => $counter->token,
            'category_name' => (!empty($queue_category) ? $queue_category->name : NULL)
          ];
        }

        $display_data['counter'] = ['error' => 0, 'data' => $counter_list];
      } else {
        $display_data['counter'] = ['error' => 1, 'data' => [], 'msg' => 'No counter online.'];
      }

      $queue_lists = $this->Qms_model->getTodayQueueTicketList($warehouse_id);

      if ($queue_lists) {
        foreach ($queue_lists as $ticket) {
          $customer = $this->site->getCustomerByID($ticket->customer_id);

          $queue_list[] = [
            'customer_id' => intval($customer->id),
            'customer_name' => $customer->name,
            'est_call_date' => $ticket->est_call_date,
            'queue_category_id' => intval($ticket->queue_category_id),
            'queue_category_name' => $ticket->queue_category_name,
            'token' => $ticket->token,
            'user_id' => ($ticket->user_id ? intval($ticket->user_id) : $ticket->user_id),
            'warehouse_id' => intval($ticket->warehouse_id)
          ];
        }

        $display_data['queue_list'] = ['error' => 0, 'data' => $queue_list];
      } else {
        $display_data['queue_list'] = ['error' => 1, 'data' => [], 'msg' => 'No queue ticket available.'];
      }

      $skip_lists = $this->Qms_model->getTodaySkippedQueueList($warehouse_id);

      if ($skip_lists) {
        foreach ($skip_lists as $ticket) {
          $customer = $this->site->getCustomerByID($ticket->customer_id);

          $skip_list[] = [
            'customer_id' => intval($customer->id),
            'customer_name' => $customer->name,
            'est_call_date' => $ticket->est_call_date,
            'queue_category_id' => intval($ticket->queue_category_id),
            'queue_category_name' => $ticket->queue_category_name,
            'token' => $ticket->token,
            'user_id' => ($ticket->user_id ? intval($ticket->user_id) : $ticket->user_id),
            'warehouse_id' => intval($ticket->warehouse_id)
          ];
        }

        $display_data['skip_list'] = ['error' => 0, 'data' => $skip_list];
      } else {
        $display_data['skip_list'] = ['error' => 1, 'data' => [], 'msg' => 'No skipped ticket available.'];
      }

      mutexRelease($hMutex); // QMS_getDisplayData

      sendJSON($display_data);
    }
    sendJSON(['error' => 1, 'msg' => 'No warehouse id is specified.']);
  }

  public function getQueues()
  {
    $startDate = (getGET('start_date') ?? date('Y-m-') . '01');
    $endDate   = (getGET('end_date') ?? date('Y-m-d'));
    $warehouses = XSession::get('warehouse_id') ?? getGET('warehouse');
    $xls = getGET('xls');
    $whNames = [];

    if ($warehouses) {
      if (is_array($warehouses)) {
        foreach ($warehouses as $warehouse) {
          $wh = $this->site->getWarehouseByID($warehouse);

          $whNames[] = $wh->name;
        }
      } else {
        $whNames[] = $this->site->getWarehouseByID($warehouses)->name;
      }
    }

    if (!$xls) {
      $this->load->library('datatable');

      $this->datatable
        ->select("queue_tickets.id AS id, queue_tickets.id AS qid,
          date, call_date, serve_date, end_date,
          queue_tickets.counter AS queue_counter,
          (CASE
            WHEN customers.company IS NOT NULL AND customers.company <> ''
            THEN CONCAT(customers.name, ' (', customers.company, ')')
            ELSE customers.name
          END) AS customer_name,
          queue_category_name,
          queue_tickets.token AS queue_token,
          queue_tickets.status AS queue_status,
          queue_tickets.warehouse_name,
          users.fullname AS cs_name")
        ->from('queue_tickets')
        ->join('customers', 'customers.id = queue_tickets.customer_id', 'left')
        ->join('users', 'users.id = queue_tickets.user_id', 'left')
        ->editColumn('qid', function ($data) {
          return "
            <div class=\"text-center\">
              <a href=\"{$this->theme}qms/delete/{$data['id']}\"
                class=\"tip\"
                data-action=\"confirm\" data-title=\"Delete Queue\"
                data-message=\"Are you sure to delete this Queue?\"
                style=\"color:red;\" title=\"Delete Report\">
                  <i class=\"fad fa-fw fa-trash\"></i>
              </a>
              <a href=\"{$this->theme}qms/edit/{$data['id']}\"
                class=\"tip\"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal2\"
                title=\"Edit Queue\">
                  <i class=\"fad fa-fw fa-edit\"></i>
              </a>
              <a href=\"{$this->theme}qms/counter?recall={$data['id']}\"
                class=\"tip\"
                data-title=\"Recall Queue\"
                title=\"Recall Queue\">
                  <i class=\"fad fa-fw fa-megaphone\"></i>
              </a>
            </div>";
        }, 2)
        ->addColumn('queue_status', 'queue_status', function ($data) {
          // $data['status']:Integer

          switch ($data['queue_status']) {
            case '1': // Waiting to call.
              return 'waiting';
            case '2': // To be calling by display
              return 'calling';
            case '3': // Called.
              return 'called';
            case '4':
              return 'serving';
            case '5':
              return 'served';
            case '6':
              return 'skipped';
          }
        }, 11);

      $this->datatable->where("queue_tickets.date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'");

      if ($whNames) {
        $this->datatable->group_start();
        foreach ($whNames as $name) {
          $this->datatable->or_like('queue_tickets.warehouse_name', $name, 'none');
        }
        $this->datatable->group_end();
      }

      // echo $this->datatable->compile();
      $this->datatable->generate();
    } else if ($xls == 1) { // Export Excel.
      $this->db
        ->select("queue_tickets.id AS id, queue_tickets.id AS qid,
          date, call_date, serve_date, end_date,
          queue_tickets.counter AS queue_counter,
          (CASE
            WHEN customers.company IS NOT NULL AND customers.company <> ''
            THEN CONCAT(customers.name, ' (', customers.company, ')')
            ELSE customers.name
          END) AS customer_name,
          queue_category_name,
          queue_tickets.token AS queue_token,
          queue_tickets.status AS queue_status,
          queue_tickets.warehouse_name,
          users.fullname AS cs_name")
        ->from('queue_tickets')
        ->join('customers', 'customers.id = queue_tickets.customer_id', 'left')
        ->join('users', 'users.id = queue_tickets.user_id', 'left');

      $this->db->where("queue_tickets.date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'");

      if ($whNames) {
        $this->db->group_start();
        foreach ($whNames as $name) {
          $this->db->or_like('queue_tickets.warehouse_name', $name, 'none');
        }
        $this->db->group_end();
      }

      $this->db->order_by('queue_tickets.date', 'DESC');

      $rows = $this->db->get()->result();

      $sheet = $this->ridintek->spreadsheet();

      $sheet->setTitle('Queue Report');

      $sheet->setCellValue('A1', 'Date');
      $sheet->setCellValue('B1', 'Call Date');
      $sheet->setCellValue('C1', 'Serve Date');
      $sheet->setCellValue('D1', 'End Date');
      $sheet->setCellValue('E1', 'Counter');
      $sheet->setCellValue('F1', 'Customer');
      $sheet->setCellValue('G1', 'Category');
      $sheet->setCellValue('H1', 'Ticket');
      $sheet->setCellValue('I1', 'Status');
      $sheet->setCellValue('J1', 'Warehouse');
      $sheet->setCellValue('K1', 'CS');

      $sheet->setBold('A1:K1', TRUE);

      $r = 2;

      foreach ($rows as $row) {
        $sheet->setCellValue('A' . $r, $row->date);
        $sheet->setCellValue('B' . $r, $row->call_date);
        $sheet->setCellValue('C' . $r, $row->serve_date);
        $sheet->setCellValue('D' . $r, $row->end_date);
        $sheet->setCellValue('E' . $r, $row->queue_counter);
        $sheet->setCellValue('F' . $r, $row->customer_name);
        $sheet->setCellValue('G' . $r, $row->queue_category_name);
        $sheet->setCellValue('H' . $r, $row->queue_token);
        $sheet->setCellValue('I' . $r, lang(qmsStatus($row->queue_status)));
        $sheet->setCellValue('J' . $r, $row->warehouse_name);
        $sheet->setCellValue('K' . $r, $row->cs_name);

        $r++;
      }

      $sheet->setColumnAutoWidth('A');
      $sheet->setColumnAutoWidth('B');
      $sheet->setColumnAutoWidth('C');
      $sheet->setColumnAutoWidth('D');
      $sheet->setColumnAutoWidth('E');
      $sheet->setColumnAutoWidth('F');
      $sheet->setColumnAutoWidth('G');
      $sheet->setColumnAutoWidth('H');
      $sheet->setColumnAutoWidth('I');
      $sheet->setColumnAutoWidth('J');
      $sheet->setColumnAutoWidth('K');

      $name = XSession::get('fullname');

      $sheet->export('PrintERP-QueueReport-' . date('Ymd_His') . "-($name)");
    } else if ($xls == 2) {
      // Export Report

      $users = $this->Qms_model->getQueueUsers(['start_date' => $startDate, 'end_date' => $endDate]);

      $sheet = $this->ridintek->spreadsheet();

      $sheet->loadFile(FCPATH . 'files/templates/QMS_Report.xlsx');
      $sheet->getSheetByName('Sheet1');
      $sheet->setTitle('QMS Report');

      $sheet->setCellValue('A1', date('F Y', strtotime($startDate)));

      $a = 4; // First row index.

      $PG = 2000; // Penalty per minute.

      foreach ($users as $user) {
        if (!$user->id) continue;

        $warehouse = $this->site->getWarehouseByID($user->warehouse_id);

        if ($warehouse) {
          $warehouseName = $warehouse->name;
        } else {
          $warehouseName = $this->Settings->default_warehouse;
        }

        $queueSessions = $this->Qms_model->getQueueSessions([
          'user_id' => $user->id,
          'start_date' => $startDate,
          'end_date' => $endDate
        ]);

        $tickets = $this->Qms_model->getQueueTickets([
          'user_id' => $user->id, 'start_date' => $startDate, 'end_date' => $endDate
        ]);

        $sessOWC = 0;
        $sessOWS = 0;
        $sessOS = 0;
        $sessOR = 0;

        foreach ($queueSessions as $queueSession) {
          if (isOverTime($queueSession->over_wcall_time)) $sessOWC++;
          if (isOverTime($queueSession->over_wserve_time)) $sessOWS++;
          // if (isOverTime($queueSession->over_serve_time)) $sessOS++;
          if (isOverTime($queueSession->over_rest_time)) $sessOR++;
        }

        $ticketSC = 0;
        $ticketED = 0;
        $ticketWA = 0;
        $ticketCA = 0;
        $ticketCL = 0;
        $ticketSE = 0;
        $ticketSV = 0;
        $ticketSK = 0;

        foreach ($tickets as $ticket) {
          if (isOverTime($ticket->over_time)) $sessOS++; // Replace code above.
          if ($ticket->queue_category_id == 1) $ticketSC++;
          if ($ticket->queue_category_id == 2) $ticketED++;
          if ($ticket->status == 1) $ticketWA++;
          if ($ticket->status == 2) $ticketCA++;
          if ($ticket->status == 3) $ticketCL++;
          if ($ticket->status == 4) $ticketSE++;
          if ($ticket->status == 5) $ticketSV++;
          if ($ticket->status == 6) $ticketSK++;
        }

        $sheet->setCellValue('A' . $a, $user->fullname);
        $sheet->setCellValue('B' . $a, $warehouseName);
        $sheet->setCellValue('C' . $a, $ticketSC);
        $sheet->setCellValue('D' . $a, $ticketED);
        $sheet->setCellValue('E' . $a, "=C{$a}+D{$a}");
        $sheet->setCellValue('F' . $a, $ticketWA);
        $sheet->setCellValue('G' . $a, $ticketCA);
        $sheet->setCellValue('H' . $a, $ticketCL);
        $sheet->setCellValue('I' . $a, $ticketSE);
        $sheet->setCellValue('J' . $a, $ticketSV);
        $sheet->setCellValue('K' . $a, $ticketSK);
        $sheet->setCellValue('L' . $a, $sessOWC);
        $sheet->setCellValue('M' . $a, $sessOWS);
        $sheet->setCellValue('N' . $a, $sessOS);
        $sheet->setCellValue('O' . $a, $sessOR);
        $sheet->setCellValue('P' . $a, "=SUM(L{$a}:O{$a})");
        $sheet->setCellValue('Q' . $a, "=IF(P{$a}>0,P{$a}*-{$PG},(\$P$1*{$PG})/(LEFT(\$B$2, SEARCH(\":\",\$B\$2)-1)))");

        $a++;
      }

      $sheet->setColumnAutoWidth('A');
      $sheet->setColumnAutoWidth('B');
      $sheet->setColumnAutoWidth('C');
      $sheet->setColumnAutoWidth('D');
      $sheet->setColumnAutoWidth('E');
      $sheet->setColumnAutoWidth('F');
      $sheet->setColumnAutoWidth('G');
      $sheet->setColumnAutoWidth('H');
      $sheet->setColumnAutoWidth('I');
      $sheet->setColumnAutoWidth('J');
      $sheet->setColumnAutoWidth('K');
      $sheet->setColumnAutoWidth('L');
      $sheet->setColumnAutoWidth('M');
      $sheet->setColumnAutoWidth('N');
      $sheet->setColumnAutoWidth('O');
      $sheet->setColumnAutoWidth('P');
      $sheet->setColumnAutoWidth('Q');

      $sheet->getSheetByName('Sheet2');
      $sheet->setTitle('Ticket Report');

      $tickets = $this->Qms_model->getQueueTickets(['start_date' => $startDate, 'end_date' => $endDate]);

      $a = 3; // First row index.

      foreach ($tickets as $ticket) {
        $customer = $this->site->getCustomerByID($ticket->customer_id);
        $pic = $this->site->getUserByID($ticket->user_id);

        if (!$customer) continue;
        if (!$pic) continue;

        $customerName = (!empty($customer->company) ? $customer->name . ' (' . $customer->company . ')' : $customer->name);
        $picName = $pic->fullname;

        $sheet->setCellValue('A' . $a, $ticket->date);
        $sheet->setCellValue('B' . $a, $ticket->call_date);
        $sheet->setCellValue('C' . $a, $ticket->serve_date);
        $sheet->setCellValue('D' . $a, $ticket->end_date);
        $sheet->setCellValue('E' . $a, XTime($ticket->wait_time));
        $sheet->setCellValue('F' . $a, XTime($ticket->serve_time));
        $sheet->setCellValue('G' . $a, XTime($ticket->over_time));
        $sheet->setCellValue('H' . $a, $ticket->counter);
        $sheet->setCellValue('I' . $a, $customerName);
        $sheet->setCellValue('J' . $a, $ticket->queue_category_name);
        $sheet->setCellValue('K' . $a, $ticket->token);
        $sheet->setCellValue('L' . $a, qmsStatus($ticket->status));
        $sheet->setCellValue('M' . $a, $ticket->warehouse_name);
        $sheet->setCellValue('N' . $a, $picName);

        $a++;
      }

      $sheet->setColumnAutoWidth('A');
      $sheet->setColumnAutoWidth('B');
      $sheet->setColumnAutoWidth('C');
      $sheet->setColumnAutoWidth('D');
      $sheet->setColumnAutoWidth('E');
      $sheet->setColumnAutoWidth('F');
      $sheet->setColumnAutoWidth('G');
      $sheet->setColumnAutoWidth('H');
      $sheet->setColumnAutoWidth('I');
      $sheet->setColumnAutoWidth('J');
      $sheet->setColumnAutoWidth('K');
      $sheet->setColumnAutoWidth('L');
      $sheet->setColumnAutoWidth('M');
      $sheet->setColumnAutoWidth('N');

      $name = XSession::get('fullname');

      $sheet->export('PrintERP-QMS-' . date('Ymd_His') . "-($name)");
    }
  }

  public function index()
  {
    $meta['bc'] = [
      ['link' => '#', 'page' => lang('home')],
      ['link' => '#', 'page' => lang('qms')]
    ];
    $meta['page_title'] = lang('QMS');
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('qms/index', $this->data);
  }

  /**
   * Counter will call this when recall.
   */
  public function recallQueue($ticket_id = NULL)
  {
    if ($response = $this->Qms_model->recallQueue($ticket_id)) {
      sendJSON(['error' => 0, 'data' => $response]);
    }
    sendJSON(['error' => 1, 'msg' => 'Cannot recall queue ticket.']);
  }

  public function register($warehouse_id = NULL)
  {
    $warehouse_id = ($warehouse_id ?? 1);
    $this->data['warehouse'] = $this->site->getWarehouseByID($warehouse_id);
    $this->load->view($this->theme . 'qms/register', $this->data);
  }

  public function sendReport()
  {
    $user_id = XSession::get('user_id');

    $hMutex = mutexCreate('QMS_sendReport', TRUE);

    if ($queueSession = $this->Qms_model->getTodayQueueSession($user_id)) {
      $sessionData = [
        'over_wcall_time'  => getPOST('over_wait_call_time'),
        'over_wserve_time' => getPOST('over_wait_serve_time'),
        'over_serve_time'  => getPOST('over_serve_time'),
        'over_rest_time'   => getPOST('over_rest_time')
      ];

      if ($this->Qms_model->updateQueueSession($queueSession->id, $sessionData)) {
        mutexRelease($hMutex);
        sendJSON(['error' => 0, 'text' => 'success']);
      }
    }

    mutexRelease($hMutex);
    sendJSON(['error' => 1, 'text' => 'failed']);
  }

  public function serveQueue()
  {
    $ticket_id = getPOST('ticket');

    if ($this->Qms_model->serveQueue($ticket_id)) {
      sendJSON(['error' => 0, 'msg' => 'OK']);
    }
    sendJSON(['error' => 1, 'msg' => 'Cannot serving queue ticket.']);
  }

  public function setCounter()
  {
    $counter = intval(getPOST('counter'));
    $user_id = XSession::get('user_id');
    $warehouse_id = (XSession::get('warehouse_id') ?? $this->Settings->default_warehouse);

    if ($this->site->updateUser($user_id, ['counter' => $counter])) {
      $online_counters = $this->Qms_model->getTodayOnlineCounters($warehouse_id);

      if ($online_counters) {
        foreach ($online_counters as $online_counter) {
          if ($online_counter->counter == $counter && $online_counter->id != $user_id) { // Make offline to another user.
            // Set counter to offline.
            $this->site->updateUser($online_counter->id, ['counter' => 0, 'token' => NULL, 'queue_category_id' => 0]);
          }
        }
      }

      if ($counter > 0) {
        // Prevent duplicate queue session.
        if (!$this->Qms_model->getTodayQueueSession($user_id)) {
          $sessionData = [
            'user_id' => $user_id,
            'warehouse_id' => $warehouse_id
          ];

          $this->Qms_model->addQueueSession($sessionData); // Start Queue session.
        }
      }

      if (!$this->Owner && !$this->Admin) {
        $this->session->set_userdata('logout_access', ($counter ? FALSE : TRUE));
      }
      $this->session->set_userdata('counter', $counter);
      sendJSON(['error' => 0, 'msg' => 'Set counter to ' . $counter]);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to set counter']);
  }

  public function skipQueue()
  {
    $ticket_id = getPOST('ticket');

    if ($this->Qms_model->skipQueue($ticket_id)) {
      sendJSON(['error' => 0, 'msg' => 'OK']);
    }
    sendJSON(['error' => 1, 'msg' => 'Cannot skip queue ticket.']);
  }

  public function startRest()
  {
    if ($this->Qms_model->startRest(XSession::get('user_id'))) {
      sendJSON(['error' => 0, 'msg' => 'OK']);
    }
    sendJSON(['error' => 1, 'msg' => 'You cannot rest.']);
  }

  private function test()
  {
    $r = $this->Qms_model->getQueueTicketByID(1);
    dbgprint($r);
  }
}
