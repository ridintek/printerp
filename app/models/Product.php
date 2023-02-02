<?php

declare(strict_types=1);

class Product
{
  /**
   * Add new products.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('products')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete products.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('products')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get products collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('products')->get($clause);
  }

  /**
   * Get products row.
   * @param array $clause [ id, name, code ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public static function sync(int $productId, int $warehouseId)
  {
    $whp = WarehouseProduct::getRow(['product_id' => $productId, 'warehouse_id' => $warehouseId]);

    if (!$whp) return FALSE;

    return WarehouseProduct::update(
      (int)$whp->id,
      ['quantity' => Stock::totalQuantity($productId, $warehouseId)]
    );
  }

  /**
   * Update products.
   * @param int $id products ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('products')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
