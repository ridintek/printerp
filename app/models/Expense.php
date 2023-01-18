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
    $lastExpense = self::getRow(['id' => $id]);

    if (isset($data['bank_id'])) {
      $bank = Bank::getRow(['id' => $data['bank_id']]);

      $data['bank'] = $bank->code;
    }

    if (isset($data['biller_id'])) {
      $biller = Biller::getRow(['id' => $data['biller_id']]);

      $data['biller'] = $biller->code;
    }

    DB::table('expenses')->update($data, ['id' => $id]);

    if (DB::affectedRows()) {
      $expense = self::getRow(['id' => $id]);
      $payments = Payment::get(['expense_id' => $id]);

      if ($payments) { // Update payments too.
        $paymentData = [
          'amount'  => $expense->amount,
          'bank_id' => $expense->bank_id,
          'note'    => $expense->note
        ];

        foreach ($payments as $payment) {
          Payment::update((int)$payment->id, $paymentData);
        }
      }

      if (
        $expense->status == 'approved' &&
        $lastExpense->payment_status == 'pending' &&
        $expense->payment_status == 'paid'
      ) {
        $bank = Bank::getRow(['id' => $expense->bank_id]);

        Payment::add([
          'expense_id' => $id,
          'bank_id'    => $expense->bank_id,
          'method'     => $bank->type,
          'amount'     => $expense->amount,
          'created_by' => $expense->created_by,
          'type'       => 'sent',
          'note'       => ($data['note'] ?? $expense->note)
        ]);

        DB::table('expenses')->update(['payment_date' => date('Y-m-d H:i:s')], ['id' => $id]);
      }

      return TRUE;
    }

    return FALSE;
  }
}
