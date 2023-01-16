<?php

declare(strict_types=1);

class BankMutation
{
  /**
   * Add new BankMutation.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    if (empty($data['date'])) $data['date'] = date('Y-m-d H:i:s');
    $data['reference'] = OrderRef::getReference('mutation');

    DB::table('bank_mutations')->insert($data);

    if (DB::affectedRows()) {
      $insertID = DB::insertID();

      OrderRef::updateReference('mutation');

      $pv_data = [
        'date'          => $data['date'],
        'expired_date'  => date('Y-m-d H:i:s', strtotime('+1 day', strtotime($data['date']))), // 24 jam
        'reference'     => $data['reference'],
        'mutation_id'   => $insertID,
        'amount'        => $data['amount'],
        'description'   => $data['note']
      ];

      if (PaymentValidation::add($pv_data)) { // Add Payment Validation.
        DB::table('bank_mutations')->update(['status' => 'waiting_transfer'], ['id' => $insertID]);
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Delete BankMutation.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('bank_mutations')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get BankMutation collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('bank_mutations')->get($clause);
  }

  /**
   * Get BankMutation row.
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
   * Update BankMutation.
   * @param int $id BankMutation ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('bank_mutations')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
