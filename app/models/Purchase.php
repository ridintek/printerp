<?php

declare(strict_types=1);

class Purchase
{
  /**
   * Add new purchases.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('purchases')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete purchases.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('purchases')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get purchases collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('purchases')->get($clause);
  }

  /**
   * Get purchases row.
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
   * Update purchases.
   * @param int $id purchases ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('purchases')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
