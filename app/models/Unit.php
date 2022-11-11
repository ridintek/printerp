<?php

declare(strict_types=1);

class Unit
{
  /**
   * Add new Unit.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('units')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Unit.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('units')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Unit collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('units')->get($clause);
  }

  /**
   * Get Unit row.
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
   * Update Unit.
   * @param int $id Unit ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('units')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
