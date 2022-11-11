<?php

declare(strict_types=1);

class ProductTransferItem
{
  /**
   * Add new ProductTransferItem.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('product_transfer_item')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete ProductTransferItem.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('product_transfer_item')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ProductTransferItem collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('product_transfer_item')->get($clause);
  }

  /**
   * Get ProductTransferItem row.
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
   * Update ProductTransferItem.
   * @param int $id ProductTransferItem ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('product_transfer_item')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
