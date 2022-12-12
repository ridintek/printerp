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
   * @param array $data [ *quantity ]
   */
  public static function complete(int $id, array $data)
  {
    $saleItem = self::getRow(['id' => $id]);

    if ($saleItem) {
      $completedQty = $data['quantity']; // Quantity to complete.
      $sale         = Sale::getRow(['id' => $saleItem->sale_id]);
      $saleItemJS   = getJSON($saleItem->json_data);
      $status       = ($saleItemJS ? $saleItemJS->status : 'waiting_production'); // Default status.
      $date         = ($data['date'] ?? date('Y-m-d H:i:s')); // Current complete date.

      if (empty($data['quantity'])) sendJSON(['error' => 1, 'msg' => 'Cannot complete zero (0) quantity.']);

      // Get operator data.
      $data     = setCreatedBy($data);
      $operator = User::getRow(['id' => $data['created_by']]);

      // Set Completed date and Operator who completed it.
      $saleItemData = [];
      $saleItemData['completed_at'] = $date; // Completed date.
      $saleItemData['operator_id']  = $operator->id; // Change PIC who completed it.

      if (empty($saleItemJS->due_date)) { // Check if sale item has due date. If empty then restricted.
        log_message('error', "Item {$saleItem->product_code} doesn't have due date.");
        // die("Item {$saleItem->product_code} tidak memiliki due date");
      }

      if (($completedQty + $saleItem->finished_qty) < $saleItem->quantity) { // If completed partial.
        $status = 'completed_partial';
      } else if (($completedQty + $saleItem->finished_qty) == $saleItem->quantity) { // If fully completed.
        $status = 'completed';
      } else {
        log_message('error', "<b>completeSaleItem()</b>: Something wrong! Maybe you complete more quantity than requested. " .
          "Completed: {$completedQty}, Finished: {$saleItem->finished_qty}, Quantity: {$saleItem->quantity}");
      }

      $saleItemData['status'] = $status; // Restore status as completed or completed_partial.

      if (isset($data['spec'])) $saleItemData['spec'] = $data['spec'];

      $saleItemData['finished_qty'] = ($saleItem->finished_qty + $completedQty);
      // $saleItemData['json_data'] = json_encode($saleItemJS);

      $klikpod = Product::get(['code' => 'KLIKPOD']);

      if (self::update((int)$saleItem->id, $saleItemData)) {
        // Increase and Decrease item.

        if ($saleItem->product_type == 'combo') { // SALEITEM. (Decrement|Increment). POFF28
          $comboItems = ComboItem::get(['product_id' => $saleItem->product_id, 'warehouse_id' => $sale->warehouse_id]);

          if ($comboItems) {
            foreach ($comboItems as $comboItem) {
              $finalCompletedQty = filterDecimal($comboItem->qty) * filterDecimal($completedQty);

              if ($comboItem->type == 'standard') { // COMBOITEM. Decrement. POSTMN, POCT15, FFC280
                if ($comboItem->product_id == $klikpod->id) {
                  addEvent("CRITICAL: KLIKPOD KNOWN AS COMBO STANDARD TYPE MUST NOT BE DECREASED!", 'critical');
                }

                Stock::add([
                  'date'          => $date,
                  'sale_id'       => $sale->id,
                  'saleitem_id'   => $saleItem->id,
                  'product_id'    => $comboItem->product_id,
                  'price'         => $saleItem->unit_price,
                  'quantity'      => $finalCompletedQty,
                  'warehouse_id'  => $sale->warehouse_id, // Must sale->warehouse_id, NOT saleItem->warehouse_id
                  'status'        => 'sent',
                  'created_by'    => $operator->id
                ]);

                addEvent("Completed Sale [{$sale->id}: {$sale->reference}], {$saleItem->product_code}: {$finalCompletedQty}");
              } else if ($comboItem->type == 'service') { // COMBOITEM. Increment. KLIKPOD
                // Since no decimal point for KLIKPOD/KLIKPODBW, we must round it up without precision.
                switch ($saleItem->product_code) {
                  case 'KLIKPOD':
                  case 'KLIKPODBW':
                    $finalCompletedQty = ceil($finalCompletedQty);
                    break;
                }

                Stock::add([
                  'date'          => $date,
                  'sale_id'       => $sale->id,
                  'saleitem_id'   => $saleItem->id,
                  'product_id'    => $comboItem->product_id,
                  'price'         => $saleItem->unit_price,
                  'quantity'      => $finalCompletedQty,
                  'warehouse_id'  => $saleItem->warehouse_id,
                  'status'        => 'received',
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

          Stock::add([
            'date'          => $date,
            'sale_id'       => $sale->id,
            'saleitem_id'   => $saleItem->id,
            'product_id'    => $saleItem->product_id,
            'price'         => $saleItem->unit_price,
            'quantity'      => $completedQty,
            'warehouse_id'  => $saleItem->warehouse_id,
            'status'        => 'received',
            'created_by'    => $operator->id
          ]);

          addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$completedQty}");
        } else if ($saleItem->product_type == 'standard') { // SALEITEM. Decrement. FFC280, POCT15
          if ($saleItem->product_code == 'KLIKPOD') {
            addEvent("CRITICAL: KLIKPOD KNOWN AS STANDARD TYPE MUST NOT BE DECREASED!", 'critical');
          }

          Stock::add([
            'date'          => $date,
            'sale_id'       => $sale->id,
            'saleitem_id'   => $saleItem->id,
            'product_id'    => $saleItem->product_id,
            'price'         => $saleItem->unit_price,
            'quantity'      => $completedQty,
            'warehouse_id'  => $saleItem->warehouse_id,
            'status'        => 'sent',
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
