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

    $biller = Biller::getRow(['id' => $data['biller_id']]);
    if (!$biller) return false;

    $customer = Customer::getRow(['id' => $data['customer_id']]);
    if (!$customer) return false;

    $isSpecialCustomer = isSpecialCustomer($customer->id);

    $warehouse = Warehouse::getRow(['id' => $data['warehouse_id']]);
    if (!$warehouse) return false;

    $grandTotal  = 0;
    $totalPrice  = 0;
    $total_items = 0.0;
    $date = filterDateTime($data['date'] ?? date('Y-m-d H:i:s'));
    $reference = OrderRef::getReference('sale');

    for ($a = 0; $a < count($items); $a++) {
      $price    = filterDecimal($items[$a]['price']);
      $quantity = filterQuantity($items[$a]['quantity']);
      $width    = filterQuantity($items[$a]['width'] ?? 0);
      $length   = filterQuantity($items[$a]['length'] ?? 0);
      $area     = ($width * $length);

      $qty         = ($area > 0 ? $area * $quantity : $quantity);
      $totalPrice  += round($price * $qty);
      $total_items += $qty;
    }

    // Tax
    $tax = (isset($data['tax']) ? floatval($data['tax']) * 0.01 * $totalPrice : 0);

    // Discount.
    $grandTotal = ($totalPrice + $tax - ($data['discount'] ?? 0));

    // Get balance.
    $balance = ($isSpecialCustomer ? $grandTotal : 0);

    // Determine use TB by biller and warehouse, if both different, then use tb (1).
    $useTB = isTBSale((int)$data['biller_id'], (int)$data['warehouse_id']);
    // $use_tb = (strcasecmp($biller->name, $warehouse->name) === 0 ? 0 : 1);

    // Get payment term.
    $payment_term = filterDecimal($data['payment_term'] ?? 1);
    $payment_term = ($payment_term > 0 ? $payment_term : 1);
    $defaultDueDate = date('Y-m-d H:i:s', strtotime('+7 day'));

    $dueDate = ($data['due_date'] ?? $data['est_complete_date'] ?? $defaultDueDate);

    $saleJS = json_encode([
      'approved'          => ($data['approved'] ?? 0),
      'cashier_by'        => ($data['cashier_by'] ?? ''),
      'source'            => ($data['source'] ?? ''),
      'est_complete_date' => $dueDate,
      'payment_due_date'  => ($data['payment_due_date'] ?? getWorkingDateTime(date('Y-m-d H:i:s', strtotime('+1 days'))))
    ]);

    $saleData = [
      'date'            => $date,
      'reference'       => $reference,
      'customer_id'     => $customer->id,
      'customer'        => $customer->phone,
      'biller_id'       => $biller->id,
      'biller'          => $biller->code,
      'warehouse_id'    => $warehouse->id,
      'warehouse'       => $warehouse->code,
      'no_po'           => ($data['no_po'] ?? NULL),
      'note'            => ($data['note'] ?? NULL),
      'discount'        => filterDecimal($data['discount'] ?? 0),
      'total'           => roundDecimal($totalPrice),
      'shipping'        => filterDecimal($data['shipping'] ?? 0),
      'grand_total'     => roundDecimal($grandTotal), // IMPORTANT roundDecimal !!
      'balance'         => $balance,
      'status'          => ($data['status'] ?? 'need_payment'),
      'payment_status'  => ($data['payment_status'] ?? 'pending'),
      'payment_term'    => $payment_term,
      'due_date'        => $dueDate,
      'created_by'      => ($data['created_by'] ?? XSession::get('user_id')),
      'total_items'     => $total_items,
      'paid'            => filterDecimal($data['paid'] ?? 0),
      'attachment_id'   => ($data['attachment_id'] ?? NULL),
      'attachment'      => ($data['attachment'] ?? NULL),
      'payment_method'  => ($data['payment_method'] ?? NULL),
      'use_tb'          => $useTB,
      'json'            => $saleJS,
      'json_data'       => $saleJS,
    ];

    DB::startTransaction();

    DB::table('sales')->insert($saleData);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();
      $sale = self::getRow(['id' => $insertId]);

      foreach ($items as $item) {
        $product = Product::getRow(['id' => $item['product_id']]);

        if (!empty($item['width']) && !empty($item['length'])) {
          $area = filterDecimal($item['width']) * filterDecimal($item['length']);
          $quantity = ($area * filterDecimal($item['quantity']));
        } else {
          $area           = 0;
          $quantity       = $item['quantity'];
          $item['width']  = 0;
          $item['length'] = 0;
        }

        $saleItemJS = json_encode([
          'w'             => $item['width'],
          'l'             => $item['length'],
          'area'          => $area,
          'sqty'          => $item['quantity'],
          'spec'          => ($item['spec'] ?? ''),
          'status'        => $data['status'],
          'operator_id'   => ($item['operator_id'] ?? ''),
          'due_date'      => ($item['due_date'] ?? ''),
          'completed_at'  => ($item['completed_at'] ?? '')
        ]);

        SaleItem::add([
          'sale'          => $sale->reference,
          'sale_id'       => $insertId,
          'product'       => $product->code,
          'product_id'    => $product->id,
          'product_code'  => $product->code,
          'product_name'  => $product->name,
          'product_type'  => $product->type,
          'price'         => $item['price'],
          'quantity'      => $quantity,
          'status'        => $data['status'],
          'subtotal'      => ($item['price'] * $quantity),
          'json'          => $saleItemJS,
          'json_data'     => $saleItemJS
        ]);
      }

      OrderRef::updateReference('sale');

      if ($isSpecialCustomer) addSaleDueDate($sale->id);

      addEvent("Created Sale [{$sale->id}: {$sale->reference}]");

      DB::commitTransaction();

      return $sale->id;
    }

    DB::rollbackTransaction();

    return false;
  }

  public static function addPayment(array $data)
  {
    // Double/Over Payment Protection
    $sale    = self::getRow(['id' => $data['sale_id']]);
    $saleJS  = getJSON($sale->json_data);
    $balance = floatval($sale->grand_total) - floatval($sale->paid);
    if (floatval($data['amount']) > floatval($balance)) return false;

    $data['created_at']     = ($data['created_at'] ?? $data['date'] ?? date('Y-m-d H:i:s'));
    $data['reference_date'] = $sale->created_at;
    $data['reference']      = ($data['reference'] ?? $sale->reference);

    if (empty($data['method'])) {
      setLastError('Method is empty.');
      return false;
    }

    if ($data['method'] != 'Cash' && $data['method'] != 'EDC' && $data['method'] != 'Transfer') {
      setLastError("Invalid payment method {$data['method']}.");
      return false;
    }

    if ($sale && Payment::add($data)) {
      // Update sale payment

      $saleData = [
        'payment_method' => $data['method']
      ];

      if (!empty($data['attachment_id'])) $saleData['attachment_id']  = $data['attachment_id'];
      if (!empty($data['attachment']))    $saleData['attachment']     = $data['attachment'];

      $saleJS = getJSON($sale->json_data);

      if (!$saleJS || empty($saleJS->est_complete_date)) {
        addSaleDueDate($sale->id);
      }

      if ($cashierBy = XSession::get('user_id')) {
        $saleJS->cashier_by = $cashierBy;
      }

      if ($saleItems = SaleItem::get(['sale_id' => $sale->id])) {
        foreach ($saleItems as $saleItem) {
          $saleItemJS = getJSON($saleItem->json_data);
          $product    = Product::getRow(['id' => $saleItem->product_id]);
          $productJS  = getJSON($product->json_data);

          if (isCompleted($saleItem->status)) continue; // Ignore if already completed.

          // AUTOCOMPLETE ENGINE IF ANY PAID AND NOT WEB2PRINT TYPE.
          if (!isWeb2Print($sale->id) && !empty($productJS->autocomplete) && $productJS->autocomplete == 1) {
            SaleItem::complete((int)$saleItem->id, [
              'quantity' => $saleItem->quantity,
              'created_by' => $saleItemJS->operator_id
            ]);
          }
        }
      }

      self::update((int)$sale->id, $saleData);
      self::sync(['sale_id' => $sale->id]); // Update status.
      return true;
    }
    return false;
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
        // array_search('POCT15', ['POCT15A', 'LSPOCT15']) => return false.
        if (array_search($stock->product_code, array_column($items, 'product_code')) === false) {
          $items[] = $stock;
        }
      }
    }
    return $items;
  }

  /**
   * Select _Template.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('sales')->select($columns, $escape);
  }

  public static function sync($clause)
  {
    $sales = [];

    // $this->syncPaymentValidations(); // Cause memory crash (looping).

    if (!empty($clause['sale_id'])) {
      $saleType = gettype($clause['sale_id']);

      if ($saleType == 'array') {
        $sales = $clause['sale_id'];
      } else if ($saleType == 'integer' || $saleType == 'string') {
        if ($sale = Sale::getRow(['id' => $clause['sale_id']])) {
          $sales[] = $sale;
        }
      } else {
        setLastError("Sale::sync() Unknown data type '" . gettype($clause['sale_id']) . "'");
        return false;
      }
    } else if (!empty($clause['id'])) {
      $saleType = gettype($clause['id']);

      if ($saleType == 'array') {
        $sales = $clause['id'];
      } else if ($saleType == 'integer' || $saleType == 'string') {
        if ($sale = Sale::getRow(['id' => $clause['id']])) {
          $sales[] = $sale;
        }
      } else {
        setLastError("Sale::sync() Unknown data type '" . gettype($clause['id']) . "'");
        return false;
      }
    } else { // Default if sale_id is NULL.
      $sales = Sale::get();
    }

    if (empty($sales)) {
      setLastError('Sale::sync() Why sales is empty?');
      return false;
    }

    foreach ($sales as $sale) {
      if (empty($sale->json_data)) {
        setLastError("Models\Sale::sync() Sale ID {$sale->id} has invalid json_data");
        continue;
      }

      $saleJS = getJSON($sale->json_data ?? '{}');
      $saleData = [];

      if (!$saleJS) {
        setLastError("Sale::sync() Invalid sales->json_data in sale id {$sale->id}, {$sale->reference}");
        log_message('error', $sale->json_data);
        continue;
      }

      $isDuePayment      = isDueDate($saleJS->payment_due_date ?? $sale->due_date);
      $isW2PUser         = isW2PUser($sale->created_by); // Is sale created_by user is w2p?
      $isSpecialCustomer = isSpecialCustomer($sale->customer_id); // Special customer (Privilege, TOP)
      $payments          = Payment::get(['sale_id' => $sale->id]);
      $paymentValidation = PaymentValidation::select('*')
        ->orderBy('id', 'DESC')
        ->where('sale_id', $sale->id)
        ->getRow();

      $saleItems  = SaleItem::get(['sale_id' => $sale->id]);

      $completedItems = 0;
      $deliveredItems = 0;
      $finishedItems  = 0;
      $total     = 0;
      $hasPartial     = false;
      $totalSaleItems = 0;
      $saleStatus     = $sale->status;

      foreach ($saleItems as $saleItem) {
        $saleItemJS = getJSON($saleItem->json_data);
        $saleItemStatus = $saleItem->status;
        $totalSaleItems++;
        $total += round($saleItem->price * $saleItem->quantity);
        $isItemFinished = ($saleItem->quantity == $saleItem->finished_qty ? true : false);
        $isItemFinishedPartial = ($saleItem->finished_qty > 0 && $saleItem->quantity > $saleItem->finished_qty ? true : false);

        if ($saleItemStatus == 'delivered') {
          $completedItems++;
          $deliveredItems++;
        } else if ($saleItemStatus == 'finished') {
          $completedItems++;
          $finishedItems++;
        } else if ($isItemFinished) {
          $completedItems++;
          $saleItemStatus = 'completed';
        } else if ($isItemFinishedPartial) {
          $hasPartial = true;
          $saleItemStatus = 'completed_partial';
        } else if ($isSpecialCustomer || $payments) {
          if ($isW2PUser) {
            $saleItemStatus = 'preparing';
          } else {
            $saleItemStatus = 'waiting_production';
          }
        } else {
          $saleItemStatus = 'need_payment';
        }

        if ($sale->status == 'inactive') {
          $saleItemStatus = 'inactive';
        }

        $saleItemJS->status = $saleItemStatus;

        SaleItem::update((int)$saleItem->id, [
          'status'    => $saleItemStatus,
          'json'      => json_encode($saleItemJS),
          'json_data' => json_encode($saleItemJS)
        ]);
      }

      if ($sale->discount > $total) {
        $sale->discount = $total;
      }

      $tax        = ($sale->tax * 0.01 * $total);
      $grandTotal = round($total + $tax - $sale->discount);

      $saleData['total']        = $total;
      $saleData['grand_total']  = $grandTotal;

      $isSaleCompleted        = ($completedItems == $totalSaleItems ? true : false);
      $isSaleCompletedPartial = (($completedItems > 0 && $completedItems < $totalSaleItems) || $hasPartial ? true : false);
      $isSaleDelivered        = ($deliveredItems == $totalSaleItems ? true : false);
      $isSaleFinished         = ($finishedItems == $totalSaleItems ? true : false);

      if ($isSaleCompleted) {
        if ($isSaleDelivered) {
          $saleStatus = 'delivered';
        } else if ($isSaleFinished) {
          $saleStatus = 'finished';
        } else {
          $saleStatus = 'completed';
        }
      } else if ($isSaleCompletedPartial) {
        if ($isW2PUser) { // Important !!!
          $saleStatus = 'preparing';
        } else {
          $saleStatus = 'completed_partial';
        }
      } else if ($isSpecialCustomer || $payments) {
        if ($isW2PUser) {
          $saleStatus = 'preparing';
        } else {
          $saleStatus = 'waiting_production';
        }
      } else if (!$payments) {
        $saleStatus = 'need_payment';
      }

      $isPaid        = false;
      $isPaidPartial = false;
      $totalPaid     = 0;
      $balance       = 0;
      $paymentStatus = $sale->payment_status;

      if ($payments) {
        foreach ($payments as $payment) {
          $totalPaid += $payment->amount;
        }

        $balance = ($grandTotal - $totalPaid);

        $isPaid        = ($balance == 0 ? true : false);
        $isPaidPartial = ($balance > 0  ? true : false);

        if ($isPaid) {
          $paymentStatus = 'paid';
        } else if ($isPaidPartial) {
          $paymentStatus = ($isDuePayment ? 'due_partial' : 'partial');
        }
      } else {
        if ($isSpecialCustomer) {
          $balance = $grandTotal;
        }
        $paymentStatus = ($isDuePayment ? 'due' : 'pending');
      }

      if ($paymentValidation) { // If any transfer.
        $isPVPending  = ($paymentValidation->status == 'pending'  ? true : false);
        $isPVExpired  = ($paymentValidation->status == 'expired'  ? true : false);

        if ($isPaid) {
          $paymentStatus = 'paid';
        } else if ($isPVPending) {
          $paymentStatus = 'waiting_transfer';
        } else if ($isPVExpired) {
          $paymentStatus = 'expired';
        }
      }

      if ($saleStatus == 'waiting_production' && empty($saleJS->waiting_production_date)) {
        $saleData['waiting_production_date'] = date('Y-m-d H:i:s');
      }

      if ($sale->status == 'inactive') {
        $saleStatus = 'inactive';
      }

      $saleData['paid']           = $totalPaid;
      $saleData['balance']        = $balance;
      $saleData['status']         = $saleStatus;
      $saleData['payment_status'] = $paymentStatus;

      Sale::update((int)$sale->id, $saleData);

      // If any change of sale status or payment status for W2P sale then dispatch W2P sale info.
      if (isset($saleJS->source) && $saleJS->source == 'W2P') {
        if ($sale->status != $saleStatus || $sale->payment_status != $paymentStatus) {
          dispatchW2PSale($sale->id);
        }
      }
    }
  }

  /**
   * Update sales.
   * @param int $id sales ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data, $items = [])
  {
    $saleData = [];

    $sale = self::getRow(['id' => $id]);

    if ($sale) {
      if (!empty($data['date']))          $saleData['date']           = $data['date'];
      if (isset($data['reference']))      $saleData['reference']      = $data['reference'];
      if (isset($data['no_po']))          $saleData['no_po']          = $data['no_po'];
      if (isset($data['note']))           $saleData['note']           = $data['note'];
      if (isset($data['discount']))       $saleData['discount']       = $data['discount'];
      if (isset($data['shipping']))       $saleData['shipping']       = $data['shipping'];
      if (isset($data['tax']))            $saleData['tax']            = $data['tax'];
      if (isset($data['total']))          $saleData['total']          = $data['total'];
      if (isset($data['grand_total']))    $saleData['grand_total']    = $data['grand_total'];
      if (isset($data['balance']))        $saleData['balance']        = $data['balance'];
      if (isset($data['status']))         $saleData['status']         = $data['status'];
      if (isset($data['payment_status'])) $saleData['payment_status'] = $data['payment_status'];
      if (isset($data['due_date']))       $saleData['due_date']       = $data['due_date'];

      if (isset($data['created_by']))     $saleData['created_by']     = $data['created_by'];
      if (isset($data['paid']))           $saleData['paid']           = $data['paid'];
      if (isset($data['attachment_id']))  $saleData['attachment_id']  = $data['attachment_id'];
      if (isset($data['attachment']))     $saleData['attachment']     = $data['attachment'];
      if (isset($data['payment_method'])) $saleData['payment_method'] = $data['payment_method'];
      if (isset($data['use_tb']))         $saleData['use_tb']         = $data['use_tb'];

      if (!empty($data['updated_by'])) $saleData['updated_by'] = $data['updated_by'];
      if (!empty($data['updated_at'])) $saleData['updated_at'] = $data['updated_at'];

      if (!empty($data['customer_id'])) {
        $customer = Customer::getRow(['id' => $data['customer_id']]);
        $saleData['customer_id']    = $customer->id;
        $saleData['customer_name']  = $customer->name . ($customer->company ? " ({$customer->company})" : '');
        $saleData['customer']       = $customer->phone;
      }

      if (!empty($data['biller_id'])) {
        $biller = Biller::getRow(['id' => $data['biller_id']]);
        $saleData['biller_id'] = $biller->id;
        $saleData['biller']    = $biller->code;
      }

      if (!empty($data['warehouse_id'])) {
        $warehouse = Warehouse::getRow(['id' => $data['warehouse_id']]);
        $saleData['warehouse_id'] = $warehouse->id;
        $saleData['warehouse']    = $warehouse->code;
      }

      $totalPrice = 0;
      $totalItems = 0;

      if ($items) {
        for ($a = 0; $a < count($items); $a++) {
          $price    = filterDecimal($items[$a]['price']);
          $quantity = filterQuantity($items[$a]['quantity']);
          $width    = filterQuantity($items[$a]['width'] ?? 0);
          $length   = filterQuantity($items[$a]['length'] ?? 0);
          $area     = ($width * $length);

          $qty         = ($area > 0 ? $area * $quantity : $quantity);
          $totalPrice += round($price * $qty);
          $totalItems += $qty;
        }

        $tax      = ($sale->tax * 0.01 * $totalPrice);
        $discount = ($data['discount'] ?? $sale->discount);

        $saleData['total'] = $totalPrice;
        $saleData['grand_total'] = ($totalPrice + $tax - $discount);
      }

      // Sale JSON
      $saleJS = getJSON($sale->json_data);

      if (!empty($data['approved']))                $saleJS->approved                = $data['approved'];
      if (!empty($data['cashier_by']))              $saleJS->cashier_by              = $data['cashier_by'];
      if (!empty($data['source']))                  $saleJS->source                  = $data['source'];
      if (!empty($data['est_complete_date']))       $saleJS->est_complete_date       = $data['est_complete_date'];
      if (!empty($data['payment_due_date']))        $saleJS->payment_due_date        = $data['payment_due_date'];
      if (!empty($data['waiting_production_date'])) $saleJS->waiting_production_date = $data['waiting_production_date'];

      $saleData['json'] = json_encode($saleJS);
      $saleData['json_data'] = json_encode($saleJS);

      DB::table('sales')->update($saleData, ['id' => $id]);

      if (DB::affectedRows()) {
        addEvent("Updated Sale [{$id}: {$sale->reference}]", 'warning');

        // Update sale items.
        if ($items) {
          SaleItem::delete(['sale_id' => $id]);

          foreach ($items as $item) {
            $product = Product::getRow(['id' => $item['product_id']]);

            if (!empty($item['width']) && !empty($item['length'])) {
              $area = filterDecimal($item['width']) * filterDecimal($item['length']);
              $quantity = ($area * filterDecimal($item['quantity']));
            } else {
              $area           = 1;
              $quantity       = $item['quantity'];
              $item['width']  = 1;
              $item['length'] = 1;
            }

            SaleItem::add([
              'sale'          => $sale->reference,
              'sale_id'       => $sale->id,
              'product'       => $product->code,
              'product_id'    => $product->id,
              'product_code'  => $product->code,
              'product_name'  => $product->name,
              'product_type'  => $product->type,
              'price'         => $item['price'],
              'quantity'      => $quantity,
              'subtotal'      => ($item['price'] * $quantity),
              'json'          => json_encode([
                'w'             => $item['width'],
                'l'             => $item['length'],
                'area'          => $area,
                'sqty'          => $item['quantity'],
                'spec'          => ($item['spec'] ?? ''),
                'status'        => $data['status'],
                'operator_id'   => ($item['operator_id'] ?? ''),
                'due_date'      => ($item['due_date'] ?? ''),
                'completed_at'  => ($item['completed_at'] ?? '')
              ]),
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
        }
        return true;
      }
    }
    return false;
  }
}
