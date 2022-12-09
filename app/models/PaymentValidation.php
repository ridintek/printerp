<?php

declare(strict_types=1);

class PaymentValidation
{
  /**
   * Add new PaymentValidation.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('payment_validations')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete PaymentValidation.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('payment_validations')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get PaymentValidation collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('payment_validations')->get($clause);
  }

  /**
   * Get PaymentValidation row.
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
   * Select PaymentValidation.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('payment_validations')->select($columns, $escape);
  }

  /**
   * Update PaymentValidation.
   * @param int $id PaymentValidation ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('payment_validations')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
