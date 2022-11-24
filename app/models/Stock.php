<?php

declare(strict_types=1);

class Stock
{
  /**
   * Add new stocks.
   * 
   * @param array $data [
   * *(adjustment_id, internal_use_id, pm_id, purchase_id, sale_id, transfer_id, saleitem_id),
   * *product_id, *warehouse_id, price, *quantity[+/-], adjustment_qty, purchased_qty,
   *  machine_id, spec, created_at, created_by ]
   * 
   * **Data.Quantity**. If minus, status = *sent*, else if plus status = *received*.
   */
  public static function add(array $data)
  {
    if (!empty($data['product_id'])) {
      $product = Product::getRow(['id' => $data['product_id']]);
      $data = setCreatedBy($data);

      if ($product) {
        $data['product_code'] = $product->code;
        $data['product_name'] = $product->name;
        $data['product_type'] = $product->type;

        $data['cost']   = ($data['cost'] ?? $product->cost);
        $data['price']  = ($data['price'] ?? $product->price);

        $category = Category::getRow(['id' => $product->category_id]);

        if ($category) {
          $data['category_id']    =  $category->id;
          $data['category_code']  =  $category->code;
          $data['category_name']  =  $category->name;
        } else {
          setLastError("Category id {$product->category_id} is not found.");
          return FALSE;
        }

        $unit = Unit::getRow(['id' => $product->unit]);

        if ($unit) { // optional
          $data['unit_id']    = $unit->id;
          $data['unit_code']  = $unit->code;
          $data['unit_name']  = $unit->name;
        }

        $warehouse = Warehouse::getRow(['id' => $data['warehouse_id']]);

        if ($warehouse) {
          $data['warehouse_id']   = $warehouse->id;
          $data['warehouse_code'] = $warehouse->code;
          $data['warehouse_name'] = $warehouse->name;
        } else {
          setLastError("Warehouse id {$data['warehouse_id']} is not found.");
          return FALSE;
        }
      } else {
        setLastError("Product id {$data['product_id']} is not found.");
        return FALSE;
      }
    }

    if (!empty($data['quantity'])) {
      $qty = filterDecimal($data['quantity']);

      // For status only.
      if ($qty > 0) $data['status'] = 'received';
      if ($qty < 0) {
        $data['quantity'] = $data['quantity'] * -1; // Back to plus. Sementara.
        $data['status'] = 'sent';
      }
    }

    DB::table('stocks')->insert($data);
    return DB::insertID();
  }

  /**
   * Delete stocks.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('stocks')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get stocks collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    $stock = DB::table('stocks');

    if (!empty($clause['not_null'])) {
      $stock->isNotNull($clause['not_null']);
      unset($clause['not_null']);
    }
    if (!empty($clause['start_date'])) {
      $stock->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $stock->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }
    if (!empty($clause['order'])) {
      // $clause['order'][0] = 'created_at | $clause['order'][1] = 'ASC'
      $stock->orderBy($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    return $stock->get($clause);
  }

  /**
   * Get stocks row.
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
   * Get total quantity based by product and warehouse.
   * 
   * @param int $productId Product ID.
   * @param int $warehouseId Warehouse ID.
   * @param array $opt [ start_date, end_date, order_by(column,ASC|DESC) ]
   * @return float Return total quantity.
   */
  public static function totalQuantity(int $productId, int $warehouseId, $opt = [])
  {
    return (float)DB::table('stocks')->selectSum('quantity', 'total')
      ->getRow(['product_id' => $productId, 'warehouse_id' => $warehouseId])->total;
  }

  /**
   * (OLD) Get total quantity based by product and warehouse.
   * 
   * @param int $productId Product ID.
   * @param int $warehouseId Warehouse ID.
   * @param array $opt [ start_date, end_date, order_by(column,ASC|DESC) ]
   * @return float Return total quantity.
   */
  public static function totalQuantityOld(int $productId, int $warehouseId, $opt = [])
  {
    $result = DB::table('stocks')
      ->select('(COALESCE(stock_recv.total, 0) - COALESCE(stock_sent.total, 0)) AS total')
      ->join("(
        SELECT product_id, SUM(quantity) total FROM stocks
        WHERE product_id = {$productId} AND warehouse_id = {$warehouseId}
        AND status LIKE 'received') stock_recv",
        'stock_recv.product_id = stocks.product_id', 'left')
      ->join("(
        SELECT product_id, SUM(quantity) total FROM stocks
        WHERE product_id = {$productId} AND warehouse_id = {$warehouseId}
        AND status LIKE 'sent') stock_sent",
        'stock_sent.product_id = stocks.product_id', 'left')
      ->groupBy('stocks.product_id')
      ->getRow(['stocks.product_id' => $productId, 'stocks.warehouse_id' => $warehouseId]);

    return $result?->total;
  }

  /**
   * Update stocks.
   * @param int $id stocks ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('stocks')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
