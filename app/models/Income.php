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
    $data['reference'] = OrderRef::getReference('expense');

    DB::table('incomes')->insert($data);

    if (DB::affectedRows()) {
      $insertID = DB::insertID();

      OrderRef::updateReference('expense');

      return $insertID;
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
