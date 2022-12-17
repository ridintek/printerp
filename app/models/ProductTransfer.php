<?php

declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

class ProductTransfer
{
  /**
   * Add new product transfer.
   * @param array $data [ attachment_id, *warehouse_id_from, *warehouse_id_to, note,
   *  created_at, created_by ]
   * @param array $items [[ *product_id, *markon_price, *quantity ]]
   */
  public static function add(array $data, array $items)
  {
    $data['reference'] = OrderRef::getReference('transfer');
    $data = setCreatedBy($data);

    $data['status'] = 'packing'; // Default status for new transfer
    $data['payment_status'] = 'pending'; // Default payment status for new transfer

    if ($items) {
      $data['items'] = '';
      $data['grand_total'] = 0;

      foreach ($items as $item) {
        $product = Product::getRow(['id' => $item['product_id']]);

        if ($product) {
          $data['items'] .= "- ({$product->code}) " . getExcerpt($product->name) . '<br>';

          $data['grand_total'] += $item['markon_price'] * $item['quantity'];
        }
      }
    }

    DB::table('product_transfer')->insert($data);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();

      OrderRef::updateReference('transfer');

      if ($items) {
        foreach ($items as $item) {
          $product = Product::getRow(['id' => $item['product_id']]);

          $item['transfer_id']        = $insertId;
          $item['product_code'] = $product->code;
          $item['status']       = 'packing';

          ProductTransferItem::add($item);
        }
      }
      return $insertId;
    }
    return FALSE;
  }

  public static function addByWarehouseId($warehouseId)
  {
    $whFrom = Warehouse::getRow(['code' => 'LUC']); // Default warehouse from.
    $whTo   = Warehouse::getRow(['id' => $warehouseId]);

    $settingsJSON     = Setting::json();
    // Return [start_date, end_date, days]
    $opt              = getPastMonthPeriod($settingsJSON->safety_stock_period);
    // Remove unnecessary 'days'
    unset($opt['days']);
    // Get sold items by warehouse id.
    $whStocks = Sale::getSoldItems((int)$warehouseId, $opt);

    if ($whStocks && $whTo) {
      $grand_total    = 0;
      $transferItems = [];
      $transferQty   = 0;

      foreach ($whStocks as $stock) {
        $item = Product::getRow(['id' => $stock->product_id]);
        // No transfer item if safety_stock is 0 or not valid integer > 0
        // If safety stock = 0 or
        if ($item->safety_stock <= 0 || !$item->safety_stock) continue;
        // Get warehouse products.
        $whpFrom = WarehouseProduct::getRow(['product_id' => $item->id, 'warehouse_id' => $whFrom->id]);
        $whpTo   = WarehouseProduct::getRow(['product_id' => $item->id, 'warehouse_id' => $whTo->id]);

        if ($whpFrom->quantity <= 0) continue; // Ignore if no stock available from source.

        // Calculate formula to get quantity of transfer.
        $transferQty = getOrderStock($whpTo->quantity, $item->min_order_qty, $whpTo->safety_stock);

        if ($transferQty <= 0) continue; // If transfer qty is 0 or less then ignore.

        // if ($item->code == 'POCT15') {
        //   sendJSON(['error' => 1, 'msg' => [
        //       'product_code' => $item->code,
        //       'whp_quantity' => $whp->quantity,
        //       'min_order' => $item->min_order_qty,
        //       'safety_stock' => $whp->safety_stock,
        //       'transfer_qty' => $transfer_qty
        //     ]
        //   ]);
        // }

        $transferItem = [
          'product_id'   => $item->id, // Required.
          'markon_price' => roundDecimal($item->markon_price),
          'quantity'     => $transferQty
        ];

        $transferItems[] = $transferItem;
        $grand_total += ($transferQty * $item->markon_price);
      }

      $transferData = [
        'warehouse_id_from' => $whFrom->id,
        'warehouse_id_to'   => $whTo->id,
        'note'              => '',
      ];

      // sendJSON(['error' => 1, 'msg' => [
      //   'transfer_data'  => $transfer_data,
      //   'transfer_items' => $transfer_items
      // ]]);

      if (self::add($transferData, $transferItems)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Add product transfer payment.
   * @param array $data [ *transfer_id, *bank_id_from, *bank_id_to, *amount, created_at, created_by ]
   */
  public static function addPayment($data)
  {
    $bankFrom = Bank::getRow(['id' => $data['bank_id_from']]);
    $bankTo   = Bank::getRow(['id' => $data['bank_id_to']]);
    $pt       = self::getRow(['id' => $data['transfer_id']]);

    if (!$pt) {
      setLastError("Product Transfer ID:{$data['transfer_id']} not found.");
      return FALSE;
    }

    $data = setCreatedBy($data); // created_at, created_by

    $paymentDataFrom = [
      'transfer_id' => $data['transfer_id'],
      'reference'   => $pt->reference,
      'bank_id'     => $bankFrom->id,
      'method'      => $bankFrom->type,
      'amount'      => floatval($data['amount']),
      'type'        => 'sent',
      'note'        => ($data['note'] ?? ''),
      'created_at' => $data['created_at'],
      'created_by' => $data['created_by']
    ];

    $paymentDataTo = [
      'transfer_id' => $data['transfer_id'],
      'reference'   => $pt->reference,
      'bank_id'     => $bankTo->id,
      'method'      => $bankTo->type,
      'amount'      => $data['amount'],
      'type'        => 'received',
      'note'        => ($data['note'] ?? ''),
      'created_at' => $data['created_at'],
      'created_by' => $data['created_by']
    ];

    if (Payment::add($paymentDataFrom) && Payment::add($paymentDataTo)) {
      return TRUE;
    }
    setLastError("Failed to add payment.");
    return FALSE;
  }

  /**
   * Delete product transfers.
   * @param array $clause [ reference, status, payment_status, warehouse_id_from, warehouse_id_to,
   * created_by, start_date, end_date ]
   */
  public static function delete($clause = [])
  {
    $pts = self::get($clause);
    $deleted = 0;

    foreach ($pts as $pt) {
      DB::table('product_transfer')->delete(['id' => $pt->id]);

      if (DB::affectedRows()) {
        $ptitems = ProductTransferItem::get(['transfer_id' => $pt->id]);

        ProductTransferItem::delete(['transfer_id' => $pt->id]);
        Stock::delete(['transfer_id' => $pt->id]);

        foreach ($ptitems as $ptitem) {
          Product::sync((int)$ptitem->product_id, (int)$pt->warehouse_id_from);
          Product::sync((int)$ptitem->product_id, (int)$pt->warehouse_id_to);
        }

        Attachment::delete(['id' => $pt->attachment_id]);

        $deleted++;
      }
    }

    return $deleted;
  }

  /**
   * Get product mutation.
   * @param array $clause [ id, status, payment_status warehouse_id_from, warehouse_id_to,
   *  created_by, updated_by, start_date, end_date, order ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get product transfers.
   * @param array $clause [ id, reference, status, payment_status, warehouse_id_from, warehouse_id_to,
   *  created_by, updated_by, start_date, end_date, order ]
   */
  public static function get($clause = [])
  {
    $pt = DB::table('product_transfer');

    if (!empty($clause['start_date'])) {
      $pt->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $pt->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $pt->orderBy($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    if (!empty($clause['warehouse_id_from']) && is_array($clause['warehouse_id_from'])) {
      $pt->whereIn('warehouse_id_from', $clause['warehouse_id_from']);
      unset($clause['warehouse_id_from']);
    }
    
    if (isset($clause['warehouse_id_from'])) { // Protect from error.
      unset($clause['warehouse_id_from']);
    }

    return $pt->get($clause);
  }

  /**
   * Sync product transfer payment.
   */
  public static function syncPayment($ptId)
  {
    $pt = self::getRow(['id' => $ptId]);
    $payments = Payment::get(['transfer_id' => $ptId]);
    $amount = 0;

    foreach ($payments as $payment) {
      // Since ProductTransfer using same transfer_id in payments. We filtered it.
      if ($payment->reference != $pt->reference) continue;
      if ($payment->type == 'received')
        $amount += $payment->amount;
    }

    $data['paid'] = $amount;
    $data['payment_status'] = $pt->payment_status;

    if ($amount == $pt->grand_total) {
      $data['payment_status'] = 'paid';
    } else if ($amount > 0 && $amount < $pt->grand_total) {
      $data['payment_status'] = 'partial';
    } else {
      $data['payment_status'] = 'pending';
    }

    if (self::update((int)$ptId, $data)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update product transfer.
   * @param int $ptId Product transfer ID.
   * @param array $data [ reference, status, payment_status, warehouse_id_from, warehouse_id_to,
   *  paid, created_by, updated_by, start_date, end_date, order ]
   * @param array $items [[ product_id, markon_price, quantity ]]
   */
  public static function update(int $ptId, array $data, $items = [])
  {
    $pt = self::getRow(['id' => $ptId]);

    $data = setUpdatedBy($data);

    $json = (json_decode($pt->json ?? '') ?? (object)[]);

    if (isset($data['send_date']))     $json->send_date     = $data['send_date'];
    if (isset($data['received_date'])) $json->received_date = $data['received_date'];

    $data['json'] = json_encode($json);

    if ($items) {
      $data['items'] = '';
      $data['grand_total'] = 0;

      foreach ($items as $item) {
        $product = Product::getRow(['id' => $item['product_id']]);

        if ($product) {
          $data['items'] .= "- ({$product->code}) " . getExcerpt($product->name) . '<br>';

          $data['grand_total'] += $item['markon_price'] * $item['quantity'];
        }
      }
    }

    DB::table('product_transfer')->update($data, ['id' => $ptId]);

    if (DB::affectedRows()) {
      if ($items) {
        ProductTransferItem::delete(['transfer_id' => $ptId]);
        Stock::delete(['transfer_id' => $ptId]);

        $receivedTotal = 0;
        $receivedPartialTotal = 0;

        foreach ($items as $item) {
          $product = Product::getRow(['id' => $item['product_id']]);

          if ($product) {
            $item['transfer_id'] = $pt->id;
            $item['product_code'] = $product->code;
            $item['status'] = ($item['status'] ?? $pt->status);

            if ($item['status'] == 'received' || $item['status'] == 'received_partial') {
              $balance = ($item['quantity'] - $item['received_qty']);

              // Change item status.
              $item['status'] = ($balance == 0 ? 'received' : 'received_partial');

              if ($item['status'] == 'received_partial') {
                $receivedPartialTotal++;
              } else if ($item['status'] == 'received') {
                $receivedTotal++;
              }
            }

            if (ProductTransferItem::add($item)) {
              if ($item['status'] == 'sent') {
                Stock::decrease([
                  'transfer_id'  => $ptId,
                  'product_id'   => $item['product_id'],
                  'quantity'     => $item['quantity'],
                  'warehouse_id' => $pt->warehouse_id_from,
                  'created_at'   => $pt->created_at
                ]);
              }

              if ($item['status'] == 'received' || $item['status'] == 'received_partial') {
                Stock::decrease([
                  'transfer_id'  => $ptId,
                  'product_id'   => $item['product_id'],
                  'quantity'     => $item['quantity'],
                  'warehouse_id' => $pt->warehouse_id_from,
                  'created_at'   => $pt->created_at
                ]);

                Stock::increase([
                  'transfer_id'  => $ptId,
                  'product_id'   => $item['product_id'],
                  'quantity'     => $item['quantity'],
                  'warehouse_id' => $pt->warehouse_id_to,
                  'created_at'   => $pt->created_at
                ]);
              }
            }

            Product::sync((int)$product->id, (int)$pt->warehouse_id_from);
            Product::sync((int)$product->id, (int)$pt->warehouse_id_to);
          }
        }

        if ($receivedTotal == count($items)) {
          self::update((int)$ptId, ['status' => 'received']);
        } else if ($receivedPartialTotal > 0) {
          self::update((int)$ptId, ['status' => 'received_partial']);
        }
      }
      return TRUE;
    }
    return FALSE;
  }
}
