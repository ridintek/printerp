<?php

declare(strict_types=1);

class ExpenseCategory
{
  /**
   * Add new ExpenseCategory.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('expense_categories')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete ExpenseCategory.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('expense_categories')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get ExpenseCategory collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('expense_categories')->get($clause);
  }

  /**
   * Get ExpenseCategory row.
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
   * Select ExpenseCategory.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('expense_categories')->select($columns, $escape);
  }

  /**
   * Update ExpenseCategory.
   * @param int $id ExpenseCategory ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('expense_categories')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
