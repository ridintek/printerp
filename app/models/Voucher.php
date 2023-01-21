<?php

declare(strict_types=1);

class Voucher
{
  /**
   * Add new Voucher.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('voucher')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Voucher.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('voucher')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Voucher collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('voucher')->get($clause);
  }

  /**
   * Get Voucher row.
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
   * Select Voucher.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('voucher')->select($columns, $escape);
  }

  /**
   * Update Voucher.
   * @param int $id Voucher ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('voucher')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
