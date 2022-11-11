<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * QMS Model.
 *
 * Status change:
 * - `1` Waiting. Ticket has been created.
 * - `2` Calling. Ticket is ready to be call by Display.
 * - `3` Called. Ticket is already being called by display.
 * - `4` Serving. Ticket is in serving mode.
 * - `5` Served. Ticket has been served.
 * - `6` Skipped. Ticket has been skipped after called.
 *
 */
class Qms_model extends CI_Model
{
  const STATUS_WAITING = 1;
  const STATUS_CALLING = 2;
  const STATUS_CALLED  = 3;
  const STATUS_SERVING = 4;
  const STATUS_SERVED  = 5;
  const STATUS_SKIPPED = 6;

  public function __construct()
  {
    parent::__construct();
  }

  public function addQueueSession($data)
  {
    if (empty($data['user_id']))              return FALSE;
    if (empty($data['warehouse_id']))         return FALSE;

    $sessionData = [
      'date' => date('Y-m-d H:i:s'),
      'user_id' => $data['user_id'],
      'warehouse_id' => $data['warehouse_id'],
      'over_wcall_time'  => ($data['over_wcall_time'] ?? '00:00:00'),
      'over_wserve_time' => ($data['over_wserve_time'] ?? '00:00:00'),
      'over_rest_time'   => ($data['over_rest_time'] ?? '00:00:00'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    $this->db->trans_start();
    $this->db->insert('queue_sessions', $sessionData);
    $insert_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return $this->getQueueSessionByID($insert_id);
    }
    return NULL;
  }

  /**
   * Add new customer queue ticket.
   * @param array $data [name*, phone*, queue_category_id*, warehouse_id*]
   */
  public function addQueueTicket($data)
  {
    if (empty($data['name']))              return FALSE;
    if (empty($data['phone']))             return FALSE;
    if (empty($data['queue_category_id'])) return FALSE;
    if (empty($data['warehouse_id']))      return FALSE;

    $customer       = $this->site->getCustomerByPhone($data['phone']);
    $queue_category = $this->getQueueCategoryByID($data['queue_category_id']);
    $warehouse      = $this->site->getWarehouseByID($data['warehouse_id']);

    if (!$customer) {
      $phone = preg_replace('/[^0-9]/', '', $data['phone']); // Filter phone number.

      $lastTicket = $this->getTodayLastQueueTicket(['warehouse_id' => $data['warehouse_id']]);

      // Prevent Duplicate entries.
      if ($lastTicket && $lastTicket->customer_id == $customer->id) {
        return FALSE;
      }

      $customer_id = $this->site->addCustomer([
        'group_id' => 3,
        'group_name' => 'customer',
        'customer_group_id' => 1,
        'customer_group_name' => 'Reguler',
        'name' => $data['name'],
        'company' => '',
        'phone' => $phone
      ]);

      if ($customer_id) {
        $customer = $this->site->getCustomerByID($customer_id);
      } else {
        return FALSE;
      }
    }

    // begin get estimated call date.
    $servingQueues = $this->getQueueTickets([
      'status' => self::STATUS_SERVING,
      'warehouse_id' => $data['warehouse_id'],
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d')
    ]);

    $waitingQueues = $this->getQueueTickets([
      'status' => self::STATUS_WAITING,
      'warehouse_id' => $data['warehouse_id'],
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d')
    ]);

    $waitTime = 0;

    if ($servingQueues && $servingQueues[0]->queue_category_name == 'Siap Cetak') {
      $waitTime += 10;
    } else if ($servingQueues && $servingQueues[0]->queue_category_name == 'Edit Design') {
      $waitTime += 20;
    }

    foreach ($waitingQueues as $waitQueue) {
      if ($waitQueue->queue_category_name == 'Siap Cetak') {
        $waitTime += 10;
      } else if ($waitQueue->queue_category_name == 'Edit Design') {
        $waitTime += 20;
      }
    };

    $di = new DateInterval("PT{$waitTime}M");

    $estCallDate = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // Current datetime.
    $estCallDate->add($di);

    $est_call_date = $estCallDate->format('Y-m-d H:i:s');
    // end get estimated call date.

    $ticket_data = [
      'date' => date('Y-m-d H:i:s'),
      'est_call_date' => getQueueDateTime($est_call_date),
      'customer_id' => $customer->id,
      'queue_category_id' => $data['queue_category_id'],
      'queue_category_name' => $queue_category->name,
      'status' => self::STATUS_WAITING, // 1 = Waiting
      'token' => $this->generateNewQueueTicketToken($data),
      'warehouse_id' => $data['warehouse_id'],
      'warehouse_name' => $warehouse->name
    ];

    $this->db->trans_start();
    $this->db->insert('queue_tickets', $ticket_data);
    $insert_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      $newTicket =  $this->getQueueTicketByID($insert_id);

      $estCallDate = date('d M y - H:i', strtotime($newTicket->est_call_date));
      $expMinute = $this->SettingsJSON->qms_expired_time;
      $expDate = date('d M y - H:i', strtotime("+{$expMinute} minute", strtotime($newTicket->est_call_date)));

      $msg = "Halo kak *{$newTicket->customer_name}* \u{1F48C}\n" .
      "Kaka telah berhasil Registrasi Pelayanan Outlet ".
      "Indoprinting {$newTicket->warehouse_name} \u{1F389}\n\n".
      "No. Antrian: *{$newTicket->token}* \u{1F3F7}\n".
      "Estimasi pelayanan: *{$estCallDate}*\n".
      "Tiket berlaku sampai: *{$expDate}*\n\n".
      "*Jika terlewat di display antrian, silakan tunjukkan tiket ini ke CS untuk segera dipanggil dan dilayani.*\n\n".
      "\u{1F4DD} Registrasi Pelayanan Outlet bisa darimana aja sehingga *tidak perlu antri di Outlet*, ".
      "cukup datang ke Outlet sesuai jadwal yg terkirim ke WhatsApp kakak.. ".
      "https://indoprinting.co.id/antrian-online\n\n".
      "\u{1F3AF} Order Praktis & Simple darimana aja.. ".
      "by Online indoprinting.co.id | by WhatsApp ".
      "wa.me/6282132003200\n\n".
      "\u{1F6E0} Beri kami masukkan ya.. bisa dikirim by WhatsApp wa.me/6281327043234\n\n".
      "Follow us https://www.instagram.com/indoprinting/ \u{1F4E3}\n\n".
      "Terima kasih \u{1F64F},\nIndoprinting Team\n\n".
      "_#PesanOtomatis_";

      $this->site->addWAJob([
        'phone'     => $newTicket->customer_phone,
        'message'   => $msg,
        'send_date' => date('Y-m-d H:i:s'),
        'status'    => 'pending'
      ]);

      return $newTicket;
    }
    return NULL;
  }

  /**
   * Call a queue ticket.
   * @param array $data [user_id*, warehouse_id*]
   */
  public function callQueue($data)
  {
    $queue_lists = $this->getTodayQueueTicketList($data['warehouse_id']);

    if ($queue_lists) {
      $user = $this->site->getUserByID($data['user_id']);

      foreach ($queue_lists as $list) {
        $ticket = $list;
        break;
      }

      $call_date   = date('Y-m-d H:i:s');
      $create_date = $ticket->date;

      $callDate = new DateTime($call_date);
      $createDate = new DateTime($create_date);
      $wait_time = $createDate->diff($callDate)->format('%H:%I:%S');

      $ticket_data = [
        'call_date' => $call_date,
        'wait_time' => $wait_time, // OK.
        'counter' => $user->counter,
        'status'  => self::STATUS_CALLING, // 2 = To be call by Display.
        'user_id' => $user->id,
      ];

      if ($this->updateQueueTicket($ticket->id, $ticket_data)) {
        $this->site->updateUser($user->id, ['token' => $ticket->token, 'queue_category_id' => $ticket->queue_category_id]);
        return $this->getQueueTicketByID($ticket->id);
      }
      return NULL;
    }
    return NULL;
  }

  /**
   * Change counter number
   */
  public function changeCounter($user_id, $counter)
  {
    if ($this->site->updateUser($user_id, ['counter' => $counter])) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteQueue($ticketId)
  {
    if ($this->db->delete('queue_tickets', ['id' => $ticketId])) {
      return TRUE;
    }
    return FALSE;
  }

  public function endQueue($ticket_id, $data = [])
  {
    $ticket = $this->getQueueTicketByID($ticket_id);
    $category = $this->getQueueCategoryByID($ticket->queue_category_id);

    if (!empty($data['serve_time'])) { // 00:05:00
      $st = explode(':', $data['serve_time']);

      if (!is_array($st) || (is_array($st) && count($st) != 3)) {
        setLastError('serve_time format is invalid.');
        return FALSE;
      }

      $di = new DateInterval('PT' . intval($st[0]) . 'H' . intval($st[1]) . 'M' . intval($st[2]) . 'S');
      $endDate = new DateTime($ticket->serve_date); // Calculate since serve_date + serve_time = end_date.
      $endDate->add($di);
      $end_date = $endDate->format('Y-m-d H:i:s');
    } else {
      $end_date = date('Y-m-d H:i:s');
      $endDate = new DateTime($end_date);
    }

    $serveDate = new DateTime($ticket->serve_date);

    $serve_time = $serveDate->diff($endDate)->format('%H:%I:%S');
    $limitDate = new DateTime(date('Y-m-d ') . $category->duration); // 00:10:00
    $overDate  = new DateTime(date('Y-m-d ') . $serve_time); // 00:12:00

    $diffOver = $overDate->diff($limitDate);

    // Check if minus then overtime.
    $over_time = ($diffOver->format('%r') == '-' ? $overDate->diff($limitDate)->format('%H:%I:%S') : '00:00:00');

    if ($this->updateQueueTicket($ticket_id, [
      'end_date' => $end_date,
      'over_time' => $over_time, // OK
      'serve_time' => $serve_time, // OK
      'status' => self::STATUS_SERVED
      ])) {
      return TRUE;
    }
    return FALSE;
  }

  public function formatTicket($number)
  {
    return ($number < 10 ? '00' . $number : ($number < 100 ? '0' . $number : $number));
  }

  /**
   * Generate new queue ticket token. Ex. C001 or D001.
   * @return array $data [queue_category_id*, warehouse_id*]
   */
  public function generateNewQueueTicketToken($data)
  {
    $queue_category = $this->getQueueCategoryByID($data['queue_category_id']);
    $last_ticket = $this->getTodayLastQueueTicket($data);

    if ($last_ticket) {
      $ticket_number = intval(str_replace($queue_category->prefix, '', $last_ticket->token));
      $ticket_number++;
      return $queue_category->prefix . $this->formatTicket($ticket_number);
    }
    // If not ticket present.
    return $queue_category->prefix . '001'; // For first ticket.
  }

  public function getQueueTickets($clause = [])
  {
    $clauses = [];

    if (!empty($clause['counter']))             $clauses['counter'] = $clause['counter'];
    if (!empty($clause['customer_id']))         $clauses['customer_id'] = $clause['customer_id'];
    if (!empty($clause['queue_category_name'])) $clauses['queue_category_name'] = $clause['queue_category_name'];
    if (!empty($clause['status']))              $clauses['status'] = $clause['status'];
    if (!empty($clause['token']))               $clauses['token'] = $clause['token'];
    if (!empty($clause['user_id']))             $clauses['user_id'] = $clause['user_id'];
    if (!empty($clause['warehouse_id']))        $clauses['warehouse_id'] = $clause['warehouse_id'];

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
    }

    $q = $this->db->get_where('queue_tickets', $clauses);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getQueueCategoryByID($queue_category_id)
  {
    $q = $this->db->get_where('queue_categories', ['id' => $queue_category_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getQueueTicketByID($queue_ticket_id)
  {
    $this->db->select("queue_tickets.*,
      queue_categories.prefix, queue_categories.attempt,
      queue_categories.duration, customers.name AS customer_name,
      customers.phone AS customer_phone")
      ->from('queue_tickets')
      ->join('queue_categories', 'queue_categories.id = queue_tickets.queue_category_id', 'left')
      ->join('customers', 'customers.id = queue_tickets.customer_id', 'left')
      ->where('queue_tickets.id', $queue_ticket_id);

    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getQueueSessionByID($session_id)
  {
    $q = $this->db->get_where('queue_sessions', ['id' => $session_id]);

    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getQueueSessions($clause = [])
  {
    if (isset($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (isset($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('queue_sessions', $clause);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getQueueUsers($opt = [])
  {
    $startDate = ($opt['start_date'] ?? date('Y-m-') . '01');
    $endDate = ($opt['end_date'] ?? date('Y-m-d'));

    unset($opt['start_date'], $opt['end_date']);

    $this->db->select("users.*")
      ->join('users', 'users.id = queue_tickets.user_id', 'left')
      ->where("queue_tickets.date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'")
      ->group_by('queue_tickets.user_id');

    $q = $this->db->get_where('queue_tickets', $opt);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    } else {
      die("Qms_model.php::getQueueUsers() {$this->db->error()['message']}");
    }
    return [];
  }

  /**
   * Get last queue ticket
   * @param array $data [queue_category_id*, warehouse_id*]
   */
  public function getTodayLastQueueTicket($data)
  {

    if (empty($data['queue_category_id'])) {
      setLastError('No queue category id');
      return NULL;
    }

    if (empty($data['warehouse_id'])) {
      setLastError('No warehouse id');
      return NULL;
    }

    $this->db->like('date', date('Y-m-d'), 'right');
    $this->db->where('warehouse_id', $data['warehouse_id']);
    $this->db->order_by('date', 'DESC');

    $q = $this->db->get_where('queue_tickets', ['queue_category_id' => $data['queue_category_id']], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getTodayCallableQueueTicket($warehouse_id)
  {
    $this->db->like('date', date('Y-m-d'), 'right');
    $this->db->where('warehouse_id', $warehouse_id);

    $q = $this->db->get_where('queue_tickets', ['status' => self::STATUS_CALLING], 1); // status = 2 = Ready to call.
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getTodayOnlineCounters($warehouse_id)
  {
    $this->db->where('warehouse_id', $warehouse_id); // Do not change this clause order.

    if ($this->Settings->default_warehouse == $warehouse_id) {
      $this->db->or_where('warehouse_id IS NULL'); // Do not change this clause order.
    }

    $this->db->where('counter > 0'); // Do not change this clause order.
    $this->db->order_by('counter', 'ASC');

    $q = $this->db->get('users');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get current user queue session.
   *
   * @param int $user_id User ID of current session.
   */
  public function getTodayQueueSession($user_id)
  {
    $this->db->like('date', date('Y-m-d'), 'right');
    $this->db->where('user_id', $user_id);

    $q = $this->db->get('queue_sessions');

    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getTodayQueueTicketList($warehouse_id)
  {
    $this->db->like('date', date('Y-m-d'), 'right');
    $this->db->where('warehouse_id', $warehouse_id);
    $this->db->order_by('date', 'ASC');

    $q = $this->db->get_where('queue_tickets', ['status' => self::STATUS_WAITING]); // status = 1 = Waiting.
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getTodaySkippedQueueList($warehouse_id)
  {
    $expMinute = $this->SettingsJSON->qms_expired_time; // minutes
    $date = date('Y-m-d H:i:s', strtotime("-{$expMinute} minute"));

    $this->db->like('date', date('Y-m-d'), 'right');
    $this->db->where("`est_call_date` > '{$date}'");
    $this->db->where('warehouse_id', $warehouse_id);
    $this->db->order_by('date', 'ASC');

    $q = $this->db->get_where('queue_tickets', ['status' => self::STATUS_SKIPPED]); // status = 1 = Waiting.

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function recallQueue($ticket_id)
  {
    if ($this->updateQueueTicket($ticket_id, ['status' => self::STATUS_CALLING])) {
      return $this->getQueueTicketByID($ticket_id);
    }
    return NULL;
  }

  public function serveQueue($ticket_id)
  {
    if ($this->updateQueueTicket($ticket_id, ['serve_date' => date('Y-m-d H:i:s'), 'status' => self::STATUS_SERVING])) {
      return TRUE;
    }
    return FALSE;
  }

  public function skipQueue($ticket_id)
  {
    if ($this->updateQueueTicket($ticket_id, ['end_date' => date('Y-m-d H:i:s'), 'status' => self::STATUS_SKIPPED])) {
      return TRUE;
    }
    return FALSE;
  }

  public function startRest($user_id)
  {
    $qsess = $this->getTodayQueueSession($user_id);

    if ($qsess) {

    }

    return TRUE;
  }

  public function updateQueueSession($session_id, $data)
  {
    $data['updated_at'] = ($data['updated_at'] ?? date('Y-m-d H:i:s'));

    $this->db->trans_start();
    $this->db->update('queue_sessions', $data, ['id' => $session_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateQueueTicket($queue_ticket_id, $data)
  {
    $this->db->trans_start();
    $this->db->update('queue_tickets', $data, ['id' => $queue_ticket_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }
}
