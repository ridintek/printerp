<?php

declare(strict_types=1);

class ComboItem
{
  /**
   * Add new ComboItem.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('combo_items')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete ComboItem.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('combo_items')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ComboItem collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('combo_items')->get($clause);
  }

  /**
   * Get ComboItem row.
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
   * Select ComboItem.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('combo_items')->select($columns, $escape);
  }

  /**
   * Update ComboItem.
   * @param int $id ComboItem ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('combo_items')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
