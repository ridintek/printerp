<?php

declare(strict_types=1);

class Sale
{
  /**
   * Add new sales.
   * @param array $data [ *biller_id, *warehouse_id ]
   * @param array $items [ ]
   */
  public static function add(array $data, array $items)
  {
    $data = setCreatedBy($data);

    $data['reference'] = OrderRef::getReference('sale');

    // Doing subtotal here!

    DB::startTransaction();

    DB::table('sales')->insert($data);

    if (DB::affectedRows()) {
      $insertID = DB::insertID();

      OrderRef::updateReference('sale');

      foreach ($items as $item) {
        $item = setCreatedBy($item);

        if (!empty($item['width']) && !empty($item['length'])) {
          $area = filterDecimal($item['width']) * filterDecimal($item['length']);
          $quantity = ($area * filterDecimal($item['quantity']));
        } else {
          $area           = 0;
          $quantity       = $item['quantity'];
          $item['width']  = 0;
          $item['length'] = 0;
        }

        SaleItem::add([
          'sale_id'       => $insertID,
          'product_id'    => $item['product_id'],
          'price'         => $item['price'],
          'quantity'      => $quantity,
          'warehouse_id'  => $data['warehouse_id'],
          'json_data'     => json_encode([
            'w'             => $item['width'],
            'l'             => $item['length'],
            'area'          => $area,
            'sqty'          => $item['quantity'],
            'spec'          => ($item['spec'] ?? ''),
            'status'        => $data['status'],
            'operator_id'   => ($item['operator_id'] ?? ''),
            'due_date'      => ($item['due_date'] ?? ''),
            'completed_at'  => ($item['completed_at'] ?? '')
          ])
        ]);
      }

      DB::commitTransaction();

      return $insertID;
    }

    DB::rollbackTransaction();

    return FALSE;
  }

  /**
   * Delete sales.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('sales')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get sales collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    $db = DB::table('sales');

    if (!empty($clause['start_date'])) {
      $db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    return $db->get($clause);
  }

  /**
   * Get sales row.
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
   * Get sold items by warehouse id.
   * @param int $warehouseId Warehouse ID.
   * @param array $options [ start_date, end_date ]
   */
  public static function getSoldItems(int $warehouseId, $options = [])
  {
    $items = [];
    $clause = $options;
    $clause['not_null']     = 'sale_id';
    $clause['warehouse_id'] = $warehouseId;

    $stocks = Stock::get($clause);

    if ($stocks) {
      foreach ($stocks as $stock) {
        $product = Product::getRow(['id' => $stock->product_id]);

        // No sparepart. Sparepart always add in internal use.
        if ($product->iuse_type == 'sparepart') continue;

        if (!$stock->sale_id) continue; // Sale ID only.

        if ($stock->product_type !== 'standard') continue; // Standard only.
        if ($stock->status !== 'sent') continue; // Sent only.

        // It's safe to use product code. Because case-sensitive.
        // array_search('POCT15', ['POCT15A', 'LSPOCT15']) => return FALSE.
        if (array_search($stock->product_code, array_column($items, 'product_code')) === FALSE) {
          $items[] = $stock;
        }
      }
    }
    return $items;
  }

  public static function sync($saleId)
  {
  }

  /**
   * Update sales.
   * @param int $id sales ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('sales')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
