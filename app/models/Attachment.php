<?php

declare(strict_types=1);

class Attachment
{
  /**
   * Add new attachment.
   * @param array $data [ *filename, *mime, *data, *size, created_at, created_by ]
   */
  public static function add(array $data)
  {
    $data = setCreatedBy($data);

    DB::table('attachment')->insert($data);
    return DB::insertId();
  }

  /**
   * Delete attachment.
   * @param array $clause [ id, filename, mime, created_by, updated_by ]
   */
  public static function delete(array $clause)
  {
    DB::table('attachment')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get attachments collection.
   * @param array $clause [ id, filename, mime, created_by, updated_by ]
   */
  public static function get($clause = [])
  {
    return DB::table('attachment')->get($clause);
  }

  /**
   * Get attachment row.
   * @param array $clause [ id, filename, mime, created_by, updated_by ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Update attachment.
   * @param array $clause [ id, filename, mime, created_by, updated_by ]
   */
  public static function update(int $id, array $data)
  {
    $db = get_instance()->db;
    $db->update('attachment', $data, ['id' => $id]);
    DB::table('attachment')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
