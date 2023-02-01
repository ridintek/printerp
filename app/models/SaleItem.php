<?php

declare(strict_types=1);

class SaleItem
{
  /**
   * Add new sale_items.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    DB::table('sale_items')->insert($data);
    return DB::insertID();
  }

  /**
   * Complete sale item.
   * @param int $id Sale item ID.
   * @param array $data [ *quantity, spec, created_at, created_by ]
   */
  public static function complete(int $id, array $data)
  {
    $data = setCreatedBy($data);
    $saleItem = self::getRow(['id' => $id]);

    if ($saleItem) {
      $completedQty = $data['quantity']; // Quantity to complete.
      $sale         = Sale::getRow(['id' => $saleItem->sale_id]);
      $saleItemJS   = getJSON($saleItem->json_data);
      $status       = ($saleItemJS ? $saleItemJS->status : 'waiting_production'); // Default status.

      if (empty($data['quantity'])) {
        setLastError("SaleItem::complete(): Quantity is missing?");
        return FALSE;
      }

      // Get operator data.
      $operator = User::getRow(['id' => $data['created_by']]);

      if (empty($saleItemJS->due_date)) { // Check if sale item has due date. If empty then restricted.
        setLastError("Item {$saleItem->product_code} doesn't have due date.");
        return FALSE;
      }

      if (($completedQty + $saleItem->finished_qty) < $saleItem->quantity) { // If completed partial.
        $status = 'completed_partial';
      } else if (($completedQty + $saleItem->finished_qty) == $saleItem->quantity) { // If fully completed.
        $status = 'completed';
      } else {
        setLastError("<b>completeSaleItem()</b>: Something wrong! Maybe you complete more quantity than requested. " .
          "Completed: {$completedQty}, Finished: {$saleItem->finished_qty}, Quantity: {$saleItem->quantity}");
        return FALSE;
      }

      // Set Completed date and Operator who completed it.

      $saleItemJS->completed_at = $data['created_at']; // Completed date.
      $saleItemJS->operator_id  = $operator->id; // Change PIC who completed it.
      $saleItemJS->status       = $status; // Restore status as completed or completed_partial.

      if (isset($data['spec'])) {
        $saleItemJS->spec = $data['spec'];
        unset($data['spec']);
      }

      $klikpod = Product::getRow(['code' => 'KLIKPOD']);

      $saleItemData = [
        'finished_qty'  => ($saleItem->finished_qty + $completedQty),
        'json'          => json_encode($saleItemJS),
        'json_data'     => json_encode($saleItemJS)
      ];

      if (self::update((int)$saleItem->id, $saleItemData)) {
        // Increase and Decrease item.

        if ($saleItem->product_type == 'combo') { // SALEITEM. (Decrement|Increment). POFF28
          $comboItems = ComboItem::get(['product_id' => $saleItem->product_id]);

          if ($comboItems) {
            foreach ($comboItems as $comboItem) {
              $rawItem  = Product::getRow(['code' => $comboItem->item_code]);

              if (!$rawItem) {
                setLastError("SaleItem::complete(): RAW item is not found.");
                continue;
              }

              $finalCompletedQty = filterDecimal($comboItem->quantity) * filterDecimal($completedQty);

              if ($rawItem->type == 'standard') { // COMBOITEM. Decrement. POSTMN, POCT15, FFC280
                if ($rawItem->id == $klikpod->id) {
                  setLastError("CRITICAL: KLIKPOD KNOWN AS COMBO STANDARD TYPE MUST NOT BE DECREASED!");
                  return FALSE;
                }

                Stock::decrease([
                  'sale_id'       => $sale->id,
                  'saleitem_id'   => $saleItem->id,
                  'product_id'    => $rawItem->id,
                  'price'         => $saleItem->price,
                  'quantity'      => $finalCompletedQty,
                  'warehouse_id'  => $sale->warehouse_id, // Must sale->warehouse_id, NOT saleItem->warehouse_id
                  'created_at'    => $data['created_at'],
                  'created_by'    => $operator->id
                ]);

                addEvent("Completed Sale [{$sale->id}: {$sale->reference}], {$saleItem->product_code}: {$finalCompletedQty}");
              } else if ($rawItem->type == 'service') { // COMBOITEM. Increment. KLIKPOD
                // Since no decimal point for KLIKPOD/KLIKPODBW, we must round it up without precision.
                switch ($rawItem->code) {
                  case 'KLIKPOD':
                  case 'KLIKPODBW':
                    $finalCompletedQty = ceil($finalCompletedQty);
                    break;
                }

                Stock::increase([
                  'sale_id'       => $sale->id,
                  'saleitem_id'   => $saleItem->id,
                  'product_id'    => $rawItem->id,
                  'price'         => $saleItem->price,
                  'quantity'      => $finalCompletedQty,
                  'warehouse_id'  => $sale->warehouse_id,
                  'created_at'    => $data['created_at'],
                  'created_by'    => $operator->id
                ]);

                addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$finalCompletedQty}");
              }
            }
          }
        } else if ($saleItem->product_type == 'service') { // SALEITEM. Increment. JASA POTONG
          // Since no decimal point for KLIKPOD/KLIKPODBW, we must round it up without precision.
          switch ($saleItem->product_code) {
            case 'KLIKPOD':
            case 'KLIKPODBW':
              $completedQty = ceil($completedQty);
              break;
          }

          Stock::increase([
            'sale_id'       => $sale->id,
            'saleitem_id'   => $saleItem->id,
            'product_id'    => $saleItem->product_id,
            'price'         => $saleItem->price,
            'quantity'      => $completedQty,
            'warehouse_id'  => $sale->warehouse_id,
            'created_at'    => $data['created_at'],
            'created_by'    => $operator->id
          ]);

          addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$completedQty}");
        } else if ($saleItem->product_type == 'standard') { // SALEITEM. Decrement. FFC280, POCT15
          if ($saleItem->product_code == 'KLIKPOD') {
            setLastError("CRITICAL: KLIKPOD KNOWN AS STANDARD TYPE MUST NOT BE DECREASED!", 'critical');
            return FALSE;
          }

          Stock::decrease([
            'sale_id'       => $sale->id,
            'saleitem_id'   => $saleItem->id,
            'product_id'    => $saleItem->product_id,
            'price'         => $saleItem->price,
            'quantity'      => $completedQty,
            'warehouse_id'  => $sale->warehouse_id,
            'created_at'    => $data['created_at'],
            'created_by'    => $operator->id
          ]);

          addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$completedQty}");
        }

        // Sync sale after operator complete the item.
        Sale::sync(['sale_id' => $sale->id]);

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Delete sale_items.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('sale_items')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get sale_items collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('sale_items')->get($clause);
  }

  /**
   * Get sale_items row.
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
   * Update sale_items.
   * @param int $id sale_items ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('sale_items')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
