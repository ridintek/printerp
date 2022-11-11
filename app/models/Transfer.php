<?php

declare(strict_types=1);

class Transfer
{
  /**
   * Add new transfers.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('transfers')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete transfers.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('transfers')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get transfers collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('transfers')->get($clause);
  }

  /**
   * Get transfers row.
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
   * Update transfers.
   * @param int $id transfers ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('transfers')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
