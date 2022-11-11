<?php defined('BASEPATH') or exit('No direct script access allowed');

class Finances_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function addBank ($data) {
    if ( ! empty($data)) {
      $balance = ( ! empty($data['balance']) ? $data['balance'] : NULL);
      $date    = ($data['date'] ?? NULL);
      unset($data['balance'], $data['date']);
      $this->db->trans_start();
      $this->db->insert('banks', $data);
      $bank_id = $this->db->insert_id();
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        if ( ! empty($balance)) {
          $q = $this->db->get_where('payments', ['bank_id' => $bank_id, 'status' => 'beginning', 'type' => 'received'], 1);
          $payment = ($q->num_rows() > 0 ? $q->row() : NULL);
          if ($balance > 0) {
            $payment_data = [
              'date'       => ($date ?? date('Y-m-d H:i:s')),
              'bank_id'    => $bank_id,
              'method'     => $data['type'],
              'amount'     => $balance,
              'created_by' => $this->session->userdata('user_id'),
              'status'     => 'beginning',
              'type'       => 'received',
              'note'       => 'BEGINNING OF BANK'
            ];
            if ($payment) {
              $this->site->updatePayment($payment->id, $payment_data);
            } else {
              $this->site->addPayment($payment_data);
            }
          } else if ($payment) {
            $this->site->deletePayment($payment->id);
          }
        }
        return TRUE;
      }
      $this->session->set_flashdata('error', $this->db->error()['message']);
    }
    return FALSE;
  }

  public function addBanks ($data) {
    if ( ! empty($data)) {
      foreach ($data as $bank) {
        if ( ! $this->addBank($bank)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  public function addBankMutation ($data, $use_payment_validation = FALSE) {
    if ( ! empty($data)) {
      if (empty($data['date'])) $data['date'] = date('Y-m-d H:i:s');
      $data['reference'] = $this->site->getReference('mutation');

      $this->db->trans_start();
      $this->db->insert('bank_mutations', $data);
      $insert_id = $this->db->insert_id();
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        if ($this->site->getReference('mutation') == $data['reference']) {
          $this->site->updateReference('mutation');
        }

        if ($use_payment_validation) { // As known, use_payment_validation = TRUE = Transfer with unique id
          $pv_data = [
            'date'          => $data['date'],
            'expired_date'  => date('Y-m-d H:i:s', strtotime($data['date']) + (60 * 60 * 24)), // 24 jam
            'reference'     => $data['reference'],
            'mutation_id'   => $insert_id,
            'amount'        => $data['amount'],
            'description'   => $data['note']
          ];

          if ($this->site->addPaymentValidation($pv_data)) { // Add Payment Validation.
            $this->db->update('bank_mutations', ['status' => 'waiting_transfer'], ['id' => $insert_id]);
          }
        } else {
          // Payment Sent by From Bank ID
          $payment_sent = [
            'date'         => $data['date'],
            'mutation_id'  => $insert_id,
            'bank_id'      => $data['from_bank_id'],
            'method'       => $data['paid_by'],
            'amount'       => $data['amount'],
            'created_by'   => $data['created_by'],
            'type'         => 'sent',
            'note'         => $data['note']
          ];
          $this->site->addPayment($payment_sent);
          // Payment Received by To Bank ID
          $payment_recv = [
            'date'         => $data['date'],
            'mutation_id'  => $insert_id,
            'bank_id'      => $data['to_bank_id'],
            'method'       => $data['paid_by'],
            'amount'       => $data['amount'],
            'created_by'   => $data['created_by'],
            'type'         => 'received',
            'note'         => $data['note']
          ];
          $this->site->addPayment($payment_recv);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function addExpense ($data = [])
  {
    $this->db->trans_start();
    $this->db->insert('expenses', $data);
    $insert_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      if ($this->site->getReference('expense') == $data['reference']) {
        $this->site->updateReference('expense');
      }
      // updateExpense: Add Payment after approved.
      return TRUE;
    }
    return FALSE;
  }

  public function addExpensePayment ($id, $status, $note)
  {
    if ($id > 0 && ! empty($status)) {
      $this->db->trans_start();
      $this->db->update('expenses', ['payment_status' => $status, 'note' => $note], ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        $expense = $this->getExpenseByID($id);
        $payment = [
          'date'         => $expense->date,
          'expense_id'   => $id,
          'bank_id'      => $expense->bank_id,
          'method'      => 'Transfer', // Diganti jika ada opsi.
          'amount'       => $expense->amount,
          'created_by'   => $expense->created_by,
          'type'         => 'sent',
          'note'         => $note
        ];
        if ($insert_id = $this->site->addPayment($payment)) {
          return $insert_id;
        }
      }
    }
    return FALSE;
  }

  public function addIncome ($data = [])
  {
    $this->db->trans_start();
    $this->db->insert('incomes', $data);
    $income_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      if ($this->site->getReference('income') == $data['reference']) {
        $this->site->updateReference('income');
      }

      $payment = [
        'date'       => $data['date'],
        'income_id'  => $income_id,
        'reference'  => $data['reference'],
        'bank_id'    => $data['bank_id'],
        'method'     => 'Transfer', // Diganti jika ada opsi.
        'amount'     => $data['amount'],
        'created_by' => $data['created_by'],
        'type'       => 'received',
        'note'       => $data['note']
      ];
      if ($this->site->addPayment($payment)) {
        return $income_id;
      }
    }
    return FALSE;
  }

  public function addPaymentApproval ($data = []) {
    $this->db->trans_start();
    $this->db->insert('payment_approvals', $data);
    $insert_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return $insert_id;
    }
    return FALSE;
  }

  public function bankActivate ($id) {
    $this->db->trans_start();
    $this->db->update('banks', ['active' => 1], ['id' => $id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function bankDeactivate ($id) {
    $this->db->trans_start();
    $this->db->update('banks', ['active' => 0], ['id' => $id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteBank ($id) {
    $this->db->trans_start();
    $this->db->delete('banks', ['id' => $id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteBankMutation ($id) {
    $this->db->trans_start();
    $this->db->delete('bank_mutations', ['id' => $id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      $payments = $this->site->getBankMutationPayments($id);
      if ( ! empty($payments)) {
        foreach ($payments as $pay) {
          if ( ! $this->site->deletePaymentByID($pay->id)) {
            return FALSE;
          }
        }
      }
      $payment_validation = $this->site->getPaymentValidationByMutationID($id);
      if ($payment_validation) {
        $this->site->deletePaymentValidation($payment_validation->id);
      }
      return TRUE;
    }
    return FALSE;
  }

  public function deleteExpense ($id)
  {
    $this->db->trans_start();
    $this->db->delete('expenses', ['id' => $id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      if ($this->site->deleteExpensePayment($id)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteIncome ($id)
  {
    $this->db->where('id=', $id);
    $inc = $this->db->get('incomes')->row();

    if ( ! empty($inc)) {
      $this->db->trans_start();
      $this->db->delete('incomes', ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        if ($this->site->deleteIncomePayment($id)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function getAllBanks () {
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        if ($row->active == 0) continue;
        $row->balance = $row->amount;
        $data[] = $row;
      }
      return $data;
    }
    return NULL;
  }

  public function getBanks () {
    return $this->getAllBanks();
  }

  public function getBankByCode ($code) {
    $this->db->like('code', $code, 'both');
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBankById ($id) {
    $this->db->where('id=', $id);
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBankMethods ($empty = FALSE) {
    return $this->getBankTypes($empty);
  }

  public function getBanksByType ($type) { // Bank Types: Cash, EDC, Transfer
    $this->db->like('type', $type, 'both');
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      $rows = [];
      foreach ($q->result() as $data) {
        $rows[] = $data;
      }
      return $rows;
    }
    return NULL;
  }

  public function getBankTypes ($empty = FALSE) { // Bank Types: Cash, EDC, Transfer
    $this->db->select('type');
    $this->db->group_by('type');
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      $rows = [];
      if ($empty) $rows[''] = '';
      foreach ($q->result_array() as $data) {
        $rows[] = $data['type'];
      }
      return $rows;
    }
  }

  public function getBankMutationById ($id) {
    $this->db->where('id', $id);
    $q = $this->db->get('bank_mutations');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getExpenseByID ($id)
  {
    $q = $this->db->get_where('expenses', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getExpenseCategories ()
  {
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getExpenseCategoryByID ($id)
  {
    $q = $this->db->get_where('expense_categories', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getIncomeByID ($id)
  {
    $q = $this->db->get_where('incomes', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getIncomeCategories ()
  {
    $q = $this->db->get('income_categories');
    if ($q->num_rows() > 0) {
      $data = [];
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getIncomeCategoryByID ($id)
  {
    $q = $this->db->get_where('income_categories', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getPaymentApprovals () {
    $q = $this->db->get('payment_approvals');
    if ($q->num_rows() > 0) {
      $rows = [];
      foreach ($q->result() as $row) {
        $rows[] = $row;
      }
      return $rows;
    }
    return NULL;
  }

  public function updateBank ($id, $data) {
    if ( ! empty($data)) {
      if (isset($data['balance'])) {
        $q = $this->db->get_where('payments', ['bank_id' => $id, 'status' => 'beginning', 'type' => 'received'], 1);
        $payment = ($q->num_rows() > 0 ? $q->row() : NULL);
        if ($data['balance'] > 0) {
          $data_payment = [
            'date'       => ($data['date'] ?? date('Y-m-d H:i:s')),
            'bank_id'    => $id,
            'method'     => $data['type'],
            'amount'     => $data['balance'],
            'created_by' => $this->session->userdata('user_id'),
            'status'     => 'beginning',
            'type'       => 'received',
            'note'       => 'BEGINNING OF BANK'
          ];
          if ($payment) {
            $this->site->updatePayment($payment->id, $data_payment);
          } else {
            $this->site->addPayment($data_payment);
          }
        } else if ($payment) {
          $this->site->deletePayment($payment->id);
        }
        unset($data['balance']);
      }
      unset($data['date']);
      $this->db->trans_start();
      $this->db->update('banks', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateBankMutation ($mutation_id, $data) {
    if ( ! empty($data)) {
      $payments = $this->site->getBankMutationPayments($mutation_id);
      foreach ($payments as $payment) {
        if ($payment->bank_id == $data['from_bank_id'] && $payment->type == 'sent') {
          $this->site->updatePayment($payment->id, [
            'amount' => $data['new_amount']
          ]);
        } else if ($payment->bank_id == $data['to_bank_id'] && $payment->type == 'received') {
          $this->site->updatePayment($payment->id, [
            'amount' => $data['new_amount']
          ]);
        }
      }
      $data['amount'] = $data['new_amount'];
      unset($data['new_amount'], $data['old_amount']);
      $this->db->trans_start();
      $this->db->update('bank_mutations', $data, ['id' => $mutation_id]);
      $this->db->trans_complete();

      if ($this->db->trans_status()) {

        $payments = $this->site->getPayments(['mutation_id' => $mutation_id]);

        if ($payments) {
          $payment = $payments[0];

          $payment_data = [
            'date'        => $data['date'],
            'mutation_id' => $mutation_id,
            'reference'   => $data['reference'],
            'bank_id'     => $data['bank_id'],
            'method'      => 'Transfer', // Diganti jika ada opsi.
            'amount'      => $data['amount'],
            'created_by'  => $data['created_by'],
            'type'        => 'received',
            'note'        => $data['note']
          ];

          if ($this->site->updatePayment($payment->id, $payment_data)) {
            return TRUE;
          }
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateExpense ($id, $data = [])
  {
    if ( ! empty($data)) {
      $this->db->trans_start();
      $this->db->update('expenses', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        $expense = $this->site->getExpenseByID($id);
        $bank    = $this->site->getBankByID($expense->bank_id);
        if (isset($data['status']) && $data['status'] == 'approved') {
          $exp_payment = [
            'date'       => $data['date'],
            'expense_id' => $id,
            'bank_id'    => $expense->bank_id,
            'method'     => $bank->type,
            'amount'     => $expense->amount,
            'created_by' => $expense->created_by,
            'type'       => 'sent',
            'note'       => $data['note']
          ];
          $this->site->addPayment($exp_payment);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateIncome ($income_id, $data = [])
  {
    if ( ! empty($data)) {
      $this->db->trans_start();
      $this->db->update('incomes', $data, ['id' => $income_id]);
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        $payments = $this->site->getPayments(['income_id' => $income_id]);

        if ($payments) {
          $payment = $payments[0];

          $payment_data = [
            'date'       => $data['date'],
            'income_id'  => $income_id,
            'reference'  => $data['reference'],
            'bank_id'    => $data['bank_id'],
            'method'     => 'Transfer', // Diganti jika ada opsi.
            'amount'     => $data['amount'],
            'created_by' => $data['created_by'],
            'type'       => 'received',
            'note'       => $data['note']
          ];

          if ($this->site->updatePayment($payment->id, $payment_data)) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  public function updatePaymentApprovals ($id, $data = [])
  {
    if ( ! empty($data)) {
      $this->db->trans_start();
      $this->db->update('payment_approvals', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        return TRUE;
      }
    }
    return FALSE;
  }
}