<?php

declare(strict_types=1);

class WarehouseProduct
{
  /**
   * Add new warehouses_products.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('warehouses_products')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete warehouses_products.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('warehouses_products')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get warehouses_products collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('warehouses_products')->get($clause);
  }

  /**
   * Get warehouses_products row.
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
   * Update warehouses_products.
   * @param int $id warehouses_products ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('warehouses_products')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
