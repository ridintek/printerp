<?php

declare(strict_types=1);

class OrderRef
{
  /**
   * Add new order_ref.
   * @param array $data [ name, code ]
   */
  private static function add(array $data)
  {
    DB::table('order_ref')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete order_ref.
   * @param array $clause [ id, name, code ]
   */
  private static function delete(array $clause)
  {
    DB::table('order_ref')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get order_ref collections.
   * @param array $clause [ id, name, code ]
   */
  private static function get($clause = [])
  {
    if (isset($clause['id'])) {
      $clause['ref_id'] = $clause['id'];
      unset($clause['id']);
    }
    return DB::table('order_ref')->get($clause);
  }

  /**
   * Get order_ref row.
   * @param array $clause [ id, name, code ]
   */
  private static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }
 
  /**
   * Get reference value.
   * @param string $name Reference name.
   */
  public static function getReference($name)
  {
    switch ($name) {
      case 'adjustment':
        $prefix = 'QA-';
        break;
      case 'cmreport': // NOT USED ANYMORE.
        $prefix = 'CMR-';
        break;
      case 'expense':
        $prefix = 'EXP-';
        break;
      case 'income':
        $prefix = 'INC-';
        break;
      case 'iuse':
        $prefix = 'IUS-';
        break;
      case 'expense':
        $prefix = 'EXP-';
        break;
      case 'income':
        $prefix = 'INC-';
        break;
      case 'mutation':
        $prefix = 'MUT-';
        break;
      case 'opname':
        $prefix = 'SO-';
        break;
      case 'purchase':
        $prefix = 'PO-';
        break;
      case 'sale':
        $prefix = 'INV-';
        break;
      case 'transfer':
        $prefix = 'TRF-';
        break;
      default:
        $prefix = ''; // No prefix.
    }

    $yearDate = date('Y/m');
    return sprintf("{$prefix}{$yearDate}/%04s", self::getRow()->{$name});
  }

  /**
   * Update order_ref.
   * @param int $id order_ref ID.
   * @param array $data [ name, code ]
   */
  private static function update(int $id, array $data)
  {
    DB::table('order_ref')->update($data, ['ref_id' => $id]);
    return DB::affectedRows();
  }

  /**
   * Update reference to new number.
   * @param string $name Reference name.
   */
  public static function updateReference($name)
  {
    return self::update(1, [$name => self::getRow()->{$name} + 1]);
  }
}
