<?php

declare(strict_types=1);

class SaleItem
{
  /**
   * Add new sale_items.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('sale_items')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete sale_items.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('sale_items')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get sale_items collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('sale_items')->get($clause);
  }

  /**
   * Get sale_items row.
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
   * Update sale_items.
   * @param int $id sale_items ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('sale_items')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
