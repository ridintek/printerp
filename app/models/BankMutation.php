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
    DB::table('bank_mutations')->insert($data);
    return DB::insertID();
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
