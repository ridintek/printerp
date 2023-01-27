<?php

declare(strict_types=1);

class Income
{
  /**
   * Add new incomes.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    $data['reference'] = OrderRef::getReference('income');

    $data = setCreatedBy($data);

    DB::table('incomes')->insert($data);

    if (DB::affectedRows()) {
      $incomeId = DB::insertID();

      $payment = [
        'date'        => $data['date'],
        'income_id'   => $incomeId,
        'reference'   => $data['reference'],
        'bank_id'     => $data['bank_id'],
        'method'      => 'Transfer', // Diganti jika ada opsi.
        'amount'      => $data['amount'],
        'created_by'  => ($data['created_by'] ?? XSession::get('user_id')),
        'type'        => 'received',
        'note'        => $data['note']
      ];

      if (Payment::add($payment)) {
        OrderRef::updateReference('income');
        return $incomeId;
      }
    }
    return FALSE;
  }

  /**
   * Delete incomes.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('incomes')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get incomes collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('incomes')->get($clause);
  }

  /**
   * Get incomes row.
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
   * Update incomes.
   * @param int $id incomes ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('incomes')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
