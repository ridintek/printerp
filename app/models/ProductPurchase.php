<?php

declare(strict_types=1);

class ProductPurchase
{
  /**
   * Add new ProductPurchase.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('product_purchase')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete ProductPurchase.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('product_purchase')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ProductPurchase collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('product_purchase')->get($clause);
  }

  /**
   * Get ProductPurchase row.
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
   * Select ProductPurchase.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('product_purchase')->select($columns, $escape);
  }

  /**
   * Update ProductPurchase.
   * @param int $id ProductPurchase ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('product_purchase')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
