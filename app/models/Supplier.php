<?php

declare(strict_types=1);

class Supplier
{
  /**
   * Add new Supplier.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('suppliers')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Supplier.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('suppliers')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Supplier collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('suppliers')->get($clause);
  }

  /**
   * Get Supplier row.
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
   * Select Supplier.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('suppliers')->select($columns, $escape);
  }

  /**
   * Update Supplier.
   * @param int $id Supplier ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('suppliers')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
