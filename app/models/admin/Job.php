<?php

declare(strict_types=1);

class Job
{
  /**
   * Add new Job.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('jobs')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Job.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('jobs')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Job collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('jobs')->get($clause);
  }

  /**
   * Get Job row.
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
   * Update Job.
   * @param int $id Job ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('jobs')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
