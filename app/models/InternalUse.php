<?php

declare(strict_types=1);

class InternalUse
{
  /**
   * Add new InternalUse.
   * @param array $data [ *category(consumable|sparepart), *items, *warehouse_id, created_at, created_by ]
   * @param array $items [[ *product_id, *quantity, machine_id, price, spec ]]
   */
  public static function add(array $data, array $items)
  {
    $data = setCreatedBy($data);

    DB::table('internal_uses')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete InternalUse.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('internal_uses')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get InternalUse collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('internal_uses')->get($clause);
  }

  /**
   * Get InternalUse row.
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
   * Select InternalUse.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('internal_uses')->select($columns, $escape);
  }

  /**
   * Update InternalUse.
   * @param int $id InternalUse ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('internal_uses')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
