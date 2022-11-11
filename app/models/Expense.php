<?php

declare(strict_types=1);

class Expense
{
  /**
   * Add new expenses.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    $data['reference'] = OrderRef::getReference('expense');

    DB::table('expenses')->insert($data);

    if (DB::affectedRows()) {
      $insertID = DB::insertID();

      OrderRef::updateReference('expense');

      return $insertID;
    }

    return FALSE;
  }

  public static function addPayment($id, $status, $note)
  {
    $expense = self::getRow(['id' => $id]);

    $payment = [
      'date'         => $expense->date,
      'expense_id'   => $id,
      'bank_id'      => $expense->bank_id,
      'method'       => Bank::getRow(['id' => $expense->bank_id])->type,
      'amount'       => ($expense->amount * -1), // Convert to minus as expense.
      'created_by'   => $expense->created_by,
      'type'         => 'sent',
      'note'         => $note
    ];

    if ($paymentId = Payment::add($payment)) {
      $expenseData = [
        'payment_date' => date('Y-m-d H:i:s'),
        'payment_status' => $status,
        'note' => $note
      ];

      self::update($id, $expenseData);

      if (DB::affectedRows()) {
        return $paymentId;
      } else {
        Payment::delete(['id' => $paymentId]);
      }
    }

    return FALSE;
  }

  /**
   * Delete expenses.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('expenses')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get expenses collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('expenses')->get($clause);
  }

  /**
   * Get expenses row.
   * @param array $clause [ id, name, code ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Update expenses.
   * @param int $id expenses ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('expenses')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}

class Expense_ extends MY_Model
{
  public function addPayment($id, $status, $note)
  {
    $expense = $this->getExpense(['id' => $id]);

    $payment = [
      'date'         => $expense->date,
      'expense_id'   => $id,
      'bank_id'      => $expense->bank_id,
      'method'       => $this->Bank->getBank(['id' => $expense->bank_id])->type,
      'amount'       => ($expense->amount * -1), // Convert to minus as expense.
      'created_by'   => $expense->created_by,
      'type'         => 'sent',
      'note'         => $note
    ];

    if ($paymentId = Payment::add($payment)) {
      $expenseData = [
        'payment_date' => date('Y-m-d H:i:s'),
        'payment_status' => $status,
        'note' => $note
      ];

      $this->db->update('expenses', $expenseData, ['id' => $id]);

      if ($this->db->affected_rows()) {
        return $paymentId;
      } else {
        $this->Payment->deletePayments(['id' => $paymentId]);
      }
    }

    return FALSE;
  }

  /**
   * Delete expenses.
   */
  public function deleteExpenses($clause = [])
  {
    $expenses = $this->getExpenses($clause);

    $this->db->delete('expenses', $clause);

    if ($ar = $this->db->affected_rows()) {
      foreach ($expenses as $expense) {
        $this->Payment->deletePayments(['expense_id' => $expense->id]);
      }
      return (int)$ar;
    }
    return 0;
  }

  /**
   * Get expense.
   * @param array $clause [ id, reference, approved_by, bank_id, biller_id, created_by, payment_status,
   *  status, supplier_id, start_date, end_date, order ]
   */
  public function getExpense($clause = [])
  {
    if ($rows = $this->getExpenses($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get expenses.
   * @param array $clause [ id, reference, approved_by, bank_id, biller_id, created_by, payment_status,
   *  status, supplier_id, start_date, end_date, order ]
   */
  public function getExpenses($clause)
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
    }

    if (!empty($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $this->db->where_in('biller_id', $clause['biller_id']);
        unset($clause['biller_id']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('expenses', $clause);

    if ($this->db->affected_rows()) {
      return $q->result();
    }
    return [];
  }

  /**
   * THE ONLY FUNCTION TO UPDATE EXPENSE.
   *
   * @param int $expense_id Expense ID.
   * @param array $data [ ]
   */
  public function updateExpense($expenseId, $data)
  {
    $oldExpense = $this->getExpense(['id' => $expenseId]);

    $this->db->update('expenses', $data, ['id' => $expenseId]);

    if ($this->db->affected_rows()) {
      $expense = $this->getExpense(['id' => $expenseId]);
      $payments = $this->Payment->getPayments(['expense_id' => $expenseId]);

      if ($payments) { // Update payments too.
        $paymentData = [
          'amount' => ($expense->amount * -1),
          'bank_id' => $expense->bank_id,
          'note' => $expense->note
        ];

        foreach ($payments as $payment) {
          $this->Payment->updatePayment($payment->id, $paymentData);
        }
      }

      if (
        $expense->status == 'approved' &&
        $oldExpense->payment_status == 'pending' &&
        $expense->payment_status == 'paid'
      ) {
        $bank = $this->Bank->getBank(['id' => $expense->bank_id]);

        $this->Payment->addPayment([
          'expense_id' => $expenseId,
          'bank_id'    => $expense->bank_id,
          'method'     => $bank->type,
          'amount'     => ($expense->amount * -1),
          'created_by' => $expense->created_by,
          'type'       => 'sent',
          'note'       => ($data['note'] ?? $expense->note)
        ]);

        $this->db->update('expenses', ['payment_date' => date('Y-m-d H:i:s')], ['id' => $expenseId]);
      }
      return TRUE;
    }
    return FALSE;
  }
}
