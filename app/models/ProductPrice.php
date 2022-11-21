<?php

declare(strict_types=1);

class ProductPrice
{
  /**
   * Add new ProductPrice.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('product_prices')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete ProductPrice.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('product_prices')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ProductPrice collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('product_prices')->get($clause);
  }

  /**
   * Get ProductPrice row.
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
   * Select ProductPrice.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('product_prices')->select($columns, $escape);
  }

  /**
   * Update ProductPrice.
   * @param int $id ProductPrice ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('product_prices')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
