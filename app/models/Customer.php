<?php

declare(strict_types=1);

class Customer
{
  /**
   * Add new Customer.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    if (isset($data['phone'])) {
      // Filtering phone number.
      $data['phone'] = preg_replace('/[^0-9]/', '', $data['phone']);
    }

    if (isset($data['name'])) {
      // Filtering name
      $data['name'] = preg_replace('/[^A-Za-z\ \']/', '', $data['name']);
    }

    if (isset($data['company'])) {
      // Filtering company name
      $data['company'] = preg_replace('/[^A-Za-z\ \']/', '', $data['company']);
    }

    $customer = self::getRow(['phone' => $data['phone']]);

    if ($customer) {
      setLastError('Phone customer already registered.');
      return FALSE;
    }

    DB::table('customers')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete Customer.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('customers')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get Customer collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('customers')->get($clause);
  }

  /**
   * Get Customer row.
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
   * Update Customer.
   * @param int $id Customer ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('customers')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
