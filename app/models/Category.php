<?php

declare(strict_types=1);

class Category
{
  /**
   * Add new Category.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('categories')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Category.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('categories')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Category collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('categories')->get($clause);
  }

  /**
   * Get Category row.
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
   * Update Category.
   * @param int $id Category ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('categories')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
