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
