<?php

declare(strict_types=1);

class ProductReview
{
  /**
   * Add new ProductReview.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    if (!empty($data['product_id'])) {
      if ($product = Product::getRow(['id' => $data['product_id']])) {
        $data['product_id']   = $product->id;
        $data['product_code'] = $product->code;
      } else {
        setLastError('ProductReview::add: Invalid product id.');
        return FALSE;
      }
    } else {
      setLastError('ProductReview::add: No product id.');
      return FALSE;
    }

    if (!empty($data['warehouse_id'])) {
      if ($warehouse = Warehouse::getRow(['id' => $data['warehouse_id']])) {
        $data['warehouse_id']   = $warehouse->id;
        $data['warehouse_code'] = $warehouse->code;
      } else {
        setLastError('ProductReview::add: Invalid warehouse id.');
        return FALSE;
      }
    } else {
      setLastError('ProductReview::add: No warehouse id.');
      return FALSE;
    }

    $data = setCreatedBy($data);

    DB::table('product_review')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }

    return FALSE;
  }

  /**
   * Delete ProductReview.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('product_review')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ProductReview collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('product_review')->get($clause);
  }

  /**
   * Get ProductReview row.
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
   * Update ProductReview.
   * @param int $id ProductReview ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('product_review')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
