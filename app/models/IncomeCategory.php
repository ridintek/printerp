<?php

declare(strict_types=1);

class IncomeCategory
{
  /**
   * Add new IncomeCategory.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('income_categories')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete IncomeCategory.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('income_categories')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get IncomeCategory collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('income_categories')->get($clause);
  }

  /**
   * Get IncomeCategory row.
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
   * Select IncomeCategory.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('income_categories')->select($columns, $escape);
  }

  /**
   * Update IncomeCategory.
   * @param int $id IncomeCategory ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('income_categories')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
