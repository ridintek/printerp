<?php

declare(strict_types=1);

class _Template
{
  /**
   * Add new _Template.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('tableName')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete _Template.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('tableName')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get _Template collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('tableName')->get($clause);
  }

  /**
   * Get _Template row.
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
   * Select _Template.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('tableName')->select($columns, $escape);
  }

  /**
   * Update _Template.
   * @param int $id _Template ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('tableName')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
