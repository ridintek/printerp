<?php

declare(strict_types=1);

class User
{
  /**
   * Add new User.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('users')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete User.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('users')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get User collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('users')->get($clause);
  }

  /**
   * Get User row.
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
   * Update User.
   * @param int $id User ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('users')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
