<?php

declare(strict_types=1);

class Adjustment
{
  /**
   * Add new adjustments.
   * @param array $data [ attachment_id, *warehouse_id, note, created_at, created_by ]
   * @param array $item [[ *product_id, *quantity ]]
   */
  public static function add(array $data, array $items)
  {
    $data['reference'] = OrderRef::getReference('adjustment');

    $data = setCreatedBy($data);

    $warehouse  = Warehouse::getRow(['id' => $data['warehouse_id']]);

    if ($warehouse) {
      $data['warehouse'] = $warehouse->code;
    }

    DB::startTransaction();

    foreach ($items as $item) {
      Product::sync(intval($item['product_id']), intval($data['warehouse_id']));
    }

    DB::table('adjustments')->insert($data);

    if (DB::affectedRows()) {
      $insertID = DB::insertID();

      DB::commitTransaction();

      return $insertID;
    }

    DB::rollbackTransaction();

    return FALSE;
  }

  /**
   * Delete adjustments.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('adjustments')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get adjustments collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('adjustments')->get($clause);
  }

  /**
   * Get adjustments row.
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
   * Update adjustments.
   * @param int $id adjustments ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    if (isset($data['warehouse_id'])) {
      $warehouse  = Warehouse::getRow(['id' => $data['warehouse_id']]);

      if ($warehouse) {
        $data['warehouse'] = $warehouse->code;
      }
    }

    DB::table('adjustments')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
