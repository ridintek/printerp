<?php

declare(strict_types=1);

class Setting
{
  /**
   * Add new settings.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('settings')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete settings.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('settings')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get settings collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('settings')->get($clause);
  }

  /**
   * Get settings row.
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
   * Get JSON settings.
   */
  public static function json()
  {
    return json_decode(self::getRow()->settings_json ?? '');
  }

  /**
   * Update settings.
   * @param int $id settings ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('settings')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
