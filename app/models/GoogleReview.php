<?php

declare(strict_types=1);

class GoogleReview
{
  /**
   * Add new GoogleReview.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('google_review')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete GoogleReview.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('google_review')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get GoogleReview collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('google_review')->get($clause);
  }

  /**
   * Get GoogleReview row.
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
   * Update GoogleReview.
   * @param int $id GoogleReview ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('google_review')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
