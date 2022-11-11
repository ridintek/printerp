<?php

declare(strict_types=1);

class MaintenanceLog
{
  /**
   * Add new MaintenanceLog.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('maintenance_logs')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete MaintenanceLog.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('maintenance_logs')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get MaintenanceLog collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('maintenance_logs')->get($clause);
  }

  /**
   * Get MaintenanceLog row.
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
   * Select MaintenanceLog.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('maintenance_logs')->select($columns, $escape);
  }

  /**
   * Update MaintenanceLog.
   * @param int $id MaintenanceLog ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('maintenance_logs')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
