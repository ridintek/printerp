<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Site extends MY_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Add new API Keys.
   * @param array $data Array of array [name, token, scopes, active, created_date, valid_until]
   */
  public function addApiKeys($data = [])
  {
    if (!empty($data) && is_array($data)) {
      $key_ids = [];
      foreach ($data as $key) {
        DB::table('api_keys')->insert($key);

        if (DB::affectedRows()) {
          $key_ids[] = DB::insertID();
        }
      }

      return $key_ids;
    }
    return FALSE;
  }

  /**
   * New Adjustment Stock.
   * @param array $data [ *warehouse_id, *mode(formula|overwrite), note, end_date ]
   * @param array $products [[ *product_id, *quantity ]]
   */
  public function addAdjustmentStock(array $data, array $products)
  {
    $data['date'] = ($data['date'] ?? date('Y-m-d H:i:s'));
    $data['mode'] = ($data['mode'] ?? 'overwrite');

    if ($warehouse = $this->getWarehouseByID($data['warehouse_id'])) {
      if (!$warehouse) {
        setLastError('Warehouse is not exists.');
        return FALSE;
      }

      if (!is_array($products)) {
        setLastError('Products is not an array.');
        return FALSE;
      }

      $adjustmentData = [
        'date'          => ($data['date'] ?? date('Y-m-d H:i:s')),
        'reference'     => $this->getReference('adjustment'),
        'mode'          => $data['mode'],
        'note'          => ($data['note'] ?? ''),
        'warehouse_id'  => $warehouse->id,
        'warehouse'     => $warehouse->code,
        'created_by'    => ($data['created_by'] ?? NULL)
      ];

      $adjustmentData = setCreatedBy($adjustmentData);

      DB::table('adjustments')->insert($adjustmentData);

      if (DB::affectedRows()) {
        $adjustmentId = DB::insertID();

        $this->updateReference('adjustment');

        foreach ($products as $product) {
          $clause = ['product_id' => $product['product_id'], 'warehouse_id' => $warehouse->id];

          if (isset($data['end_date'])) $clause['end_date'] = $data['end_date'];

          $quantity = 0;
          $stocks = $this->getStocks($clause);

          foreach ($stocks as $stock) {
            // Will be deprecated.
            if ($stock->status == 'received') {
              $quantity += $stock->quantity;
            } else if ($stock->status == 'sent') {
              $quantity -= $stock->quantity;
            }
            // NEW: Using these if not using status again. Replace above.
            // $quantity += $stock->quantity;
          }

          if ($data['mode'] == 'overwrite') {
            // $adjusted['quantity'] = 12345, $adjusted['type'] = 'received|sent';
            $adjusted = getAdjustedQty($quantity, $product['quantity']);
          } else if ($data['mode'] == 'formula') {
            $adjusted = [
              'quantity' => $product['quantity'],
              'type'     => 'received'
            ];
          }

          $this->addStockQuantity([
            'date'           => $data['date'],
            'adjustment_id'  => $adjustmentId,
            'product_id'     => $product['product_id'],
            'quantity'       => $adjusted['quantity'],
            'adjustment_qty' => $product['quantity'],
            'status'         => $adjusted['type'],
            'warehouse_id'   => $data['warehouse_id'],
            'created_by'     => ($data['created_by'] ?? XSession::get('user_id'))
          ]);
        }

        return $adjustmentId;
      }
    }
    return FALSE;
  }

  /**
   * Add new bank
   * @param array $data [ *code, *biller_id, *name, number, holder,
   * amount, type(Transfer|Cash), bic, active(1|0) ]
   */
  public function addBank($data)
  {
    if (isset($data['balance'])) {
      $balance = $data['balance'];
      unset($data['balance']);
    }

    if (isset($data['date'])) {
      $date = $data['date'];
      unset($data['date']);
    }

    DB::table('banks')->insert($data);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();

      if (!empty($balance)) {
        $payment = DB::table('payments')->where([
          'bank_id' => $insertId, 'status' => 'beginning', 'type' => 'received'
        ])->getRow();

        if ($balance > 0) {
          $payment_data = [
            'date'       => ($date ?? date('Y-m-d H:i:s')),
            'bank_id'    => $insertId,
            'method'     => $data['type'],
            'amount'     => $balance,
            'created_by' => XSession::get('user_id'),
            'status'     => 'beginning',
            'type'       => 'received',
            'note'       => 'BEGINNING OF BANK'
          ];

          if ($payment) {
            $this->updatePayment($payment->id, $payment_data);
          } else {
            Payment::add($payment_data);
          }
        } else if ($payment) {
          $this->deletePayment($payment->id);
        }
      }
      return $insertId;
    }
    return FALSE;
  }

  public function addBanks($data)
  {
    $ids = [];

    if (is_array($data)) {
      foreach ($data as $bankData) {
        if ($id = $this->addBank($bankData)) {
          $ids[] = $id;
        } else {
          return FALSE;
        }
      }
    }
    return $ids;
  }

  /**
   * THE ONLY FUNCTION TO ADD BANK MUTATION.
   * @param array $data [ date, amount, from_bank_id, to_bank_id, paid_by(Cash, Transfer),
   *  note, created_by ]
   * @param bool $usePaymentValidation Use Payment Validation.
   */
  public function addBankMutation($data, $usePaymentValidation = FALSE)
  {
    if (!empty($data)) {
      if (empty($data['date'])) $data['date'] = date('Y-m-d H:i:s');
      $data['reference'] = $this->getReference('mutation');

      DB::table('bank_mutations')->insert($data);

      if (DB::affectedRows()) {
        $insertID = DB::insertID();

        if ($this->getReference('mutation') == $data['reference']) {
          $this->updateReference('mutation');
        }

        if ($usePaymentValidation) { // As known, usePaymentValidation = TRUE = Transfer with unique id
          $pv_data = [
            'date'          => $data['date'],
            'expired_date'  => date('Y-m-d H:i:s', strtotime('+1 day', strtotime($data['date']))), // 24 jam
            'reference'     => $data['reference'],
            'mutation_id'   => $insertID,
            'amount'        => $data['amount'],
            'description'   => $data['note']
          ];

          if (PaymentValidation::add($pv_data)) { // Add Payment Validation.
            DB::table('bank_mutations')->update(['status' => 'waiting_transfer'], ['id' => $insertID]);
          }
        } else {
          // Payment Sent by From Bank ID
          $payment_sent = [
            'date'         => $data['date'],
            'mutation_id'  => $insertID,
            'bank_id'      => $data['from_bank_id'],
            'method'       => $data['paid_by'],
            'amount'       => $data['amount'],
            'created_by'   => $data['created_by'],
            'type'         => 'sent',
            'note'         => $data['note']
          ];

          Payment::add($payment_sent);
          // Payment Received by To Bank ID
          $payment_recv = [
            'date'         => $data['date'],
            'mutation_id'  => $insertID,
            'bank_id'      => $data['to_bank_id'],
            'method'       => $data['paid_by'],
            'amount'       => $data['amount'],
            'created_by'   => $data['created_by'],
            'type'         => 'received',
            'note'         => $data['note']
          ];

          Payment::add($payment_recv);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function addBiller($data = [])
  {
    DB::table('billers')->insert($data);
    $this->db->insert('billers', $data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  public function addCustomer($data)
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

    $customer = $this->getCustomerByPhone($data['phone']);

    if ($customer) {
      setLastError('Phone customer already registered.');
      return FALSE;
    }

    DB::table('customers')->insert($data);

    if (!$customer && DB::affectedRows()) {
      $cid = DB::insertID();

      addEvent("Created User [{$cid}: {$data['name']}, {$data['phone']}]", 'info');
      return $cid;
    }
    return FALSE;
  }

  public function addCustomers($data)
  {
    if (is_array($data)) {
      foreach ($data as $d) {
        $this->addCustomer($d);
      }

      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD EXPENSE.
   * @param array $data [ date, payment_date, amount, note, approved_by, created_by, attachment, category_id,
   *  biller_id, bank_id, payment_status(paid/pending), status(approved/need_approval), supplier_id ]
   */
  public function addExpense($data = [])
  {
    $data['reference'] = $this->getReference('expense');

    if (isset($data['bank_id'])) {
      $bank = Bank::getRow(['id' => $data['bank_id']]);

      $data['bank'] = $bank->code;
    }

    if (isset($data['biller_id'])) {
      $biller = Biller::getRow(['id' => $data['biller_id']]);

      $data['biller'] = $biller->code;
    }

    DB::table('expenses')->insert($data);

    if (DB::affectedRows()) {
      $expenseId = DB::insertID();

      if ($this->getReference('expense') == $data['reference']) {
        $this->updateReference('expense');
      }
      // updateExpense: Add Payment after paid (not approved).
      return $expenseId;
    }
    return FALSE;
  }

  public function addExpensePayment($id, $status, $note)
  {
    if ($id > 0 &&  !empty($status)) {
      $expense = $this->getExpenseByID($id);

      $payment = [
        'date'         => $expense->date,
        'expense_id'   => $id,
        'bank_id'      => $expense->bank_id,
        'method'       => $this->getBankByID($expense->bank_id)->type,
        'amount'       => $expense->amount,
        'created_by'   => $expense->created_by,
        'type'         => 'sent',
        'note'         => $note
      ];

      if ($insertId = Payment::add($payment)) {
        $expense_data = ['payment_date' => date('Y-m-d H:i:s'), 'payment_status' => $status, 'note' => $note];
        DB::table('expenses')->update($expense_data, ['id' => $id]);

        if (DB::affectedRows()) {
          return $insertId;
        } else {
          $this->deletePayment($insertId);
        }
      }
    }

    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD GEOLOCATION.
   * @param array $data [ date, *(user_id, customer_id), biller_id, warehouse_id, *lat, *lon ]
   */
  public function addGeolocation($data)
  {
    $geoData = [
      'date'         => ($data['date']         ?? date('Y-m-d H:i:s')),
      'user_id'      => ($data['user_id']      ?? XSession::get('user_id')      ?? NULL),
      'customer_id'  => ($data['customer_id']  ?? NULL),
      'biller_id'    => ($data['biller_id']    ?? XSession::get('biller_id')    ?? NULL),
      'warehouse_id' => ($data['warehouse_id'] ?? XSession::get('warehouse_id') ?? NULL),
      'type'         => ($data['type']         ?? 'log'), // log, presence
      'lat'          => ($data['lat']          ?? 0),
      'lon'          => ($data['lon']          ?? 0)
    ];

    DB::table('geolocation')->insert($geoData);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  public function addHoliday($data)
  {
    DB::table('holiday')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD INCOME.
   * @param array $data [ date, amount, note, created_by, attachment, category_id, biller_id, bank_id ]
   */
  public function addIncome($data)
  {
    $data['reference'] = $this->getReference('income');

    DB::table('incomes')->insert($data);

    if (DB::affectedRows()) {
      $incomeId = DB::insertID();

      if ($this->getReference('income') == $data['reference']) {
        $this->updateReference('income');
      }

      $payment = [
        'created_at' => $data['date'],
        'income_id'  => $incomeId,
        'reference'  => $data['reference'],
        'bank_id'    => $data['bank_id'],
        'method'     => 'Transfer', // Diganti jika ada opsi.
        'amount'     => $data['amount'],
        'created_by' => ($data['created_by'] ?? XSession::get('user_id')),
        'type'       => 'received',
        'note'       => $data['note']
      ];

      if (Payment::add($payment)) {
        return $incomeId;
      }
    }
    return FALSE;
  }

  public function addIncomeCategories($data)
  {
    if ($this->db->insert_batch('income_categories', $data)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add new job.
   * @param array $data [ controller, method, param ]
   */
  public function addJob($data)
  {
    DB::table('jobs')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  public function addMaintenanceLog($data)
  {
    $data = setCreatedBy($data);

    DB::table('maintenance_logs')->insert($data);

    if ($insertId = DB::insertID()) {
      return $insertId;
    }

    setLastError(DB::error()['message']);
    return NULL;
  }

  /**
   * Add Payment Validation.
   * @param array $data [ date, *(sale_id, mutation_id), *reference, created_by, biller_id, *amount,
   * unique_code, expired_date ]
   */
  public function addPaymentValidation($data)
  { // Add New: For payment transfer validation.
    $uniqueCode = 0;
    $uqcodes = [];

    $data['date'] = ($data['date'] ?? date('Y-m-d H:i:s'));

    if (empty($data['expired_date'])) {
      // Default expired: 2 days.
      $date['expired_date'] = date('Y-m-d H:i:s', strtotime('+2 day', strtotime($data['date'])));
    }

    if (!empty($data['unique_code']) && is_numeric($data['unique_code'])) {
      $uniqueCode = $data['unique_code'];
    }

    if (!$uniqueCode) {
      $uniqueCode = $this->generateUniqueCode();

      $pvPendings = DB::table('payment_validations')->where(['status' => 'pending'])->get();

      if ($pvPendings) {
        foreach ($pvPendings as $row) {
          $uqcodes[] = $row->unique_code;
        }
      }

      if ($uqcodes) {
        while (TRUE) {
          if (array_search($uniqueCode, $uqcodes) === FALSE) {
            break;
          } else {
            $uniqueCode = $this->generateUniqueCode();
          }
        }
      }
    }

    $data['unique_code'] = $uniqueCode;
    $data['status']      = 'pending';

    $data['biller_id'] = ($data['biller_id'] ?? XSession::get('biller_id'));

    $data = setCreatedBy($data);

    DB::table('payment_validations')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  /**
   * Add product category.
   *
   * @param array $data Product Category Data
   *
   * [ *code, *name, image, parent_code, *slug, description ]
   */
  public function addProductCategory($data)
  {
    DB::table('categories')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }
    return FALSE;
  }

  public function addProductCategories($categories, $subcategories)
  {
    $result = false;
    if (!empty($categories)) {
      foreach ($categories as $category) {
        DB::table('categories')->insert($category);
      }
      $result = true;
    }
    if (!empty($subcategories)) {
      foreach ($subcategories as $category) {
        if (!empty($category['parent_code'])) {
          DB::table('categories')->insert($category);
        } else {
          if ($pcategory = $this->getCategoryByCode($category['parent_code'])) {
            $category['parent_code'] = $pcategory->code;
            DB::table('categories')->insert($category);
          }
        }
      }
      $result = true;
    }
    return $result;
  }

  /**
   * Add product mutation
   * @param array $data [ attachment, *status, *from_warehouse_id, *to_warehouse_id, note,
   *  created_at, created_by ]
   * @param array $items [ *product_id, *quantity, received_qty, *status ]
   */
  public function addProductMutation(array $data, array $items)
  {
    $data = setCreatedBy($data);

    if ($items) {
      $data['items'] = '';

      foreach ($items as $item) {
        $product = Product::getRow(['id' => $item['product_id']]);

        if ($product) {
          $data['items'] .= "- ({$product->code}) " . getExcerpt($product->name) . '<br>';
        }
      }
    }

    DB::table('product_mutation')->insert($data);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();

      if ($items) {
        foreach ($items as $item) {
          $product = $this->site->getProductByID($item['product_id']);
          $item['pm_id'] = $insertId;
          $item['product_code'] = $product->code;

          $this->db->insert('product_mutation_item', $item);
        }
      }
      return $insertId;
    }
    return FALSE;
  }

  /**
   * Add product report.
   *
   * @param array $data
   * [ *product_id, *warehouse_id, *condition (good | problem | off), note, attachment,
   *  created_at, created_by ]
   */
  public function addProductReport($data)
  {
    $reportData = [];

    if (!empty($data['condition'])) {
      $reportData['condition'] = $data['condition'];
    } else {
      setLastError('addProductReport: No condition.');
      return FALSE;
    }

    if (!empty($data['note']))        $reportData['note']       = $data['note'];
    if (!empty($data['pic_note']))    $reportData['pic_note']   = $data['pic_note'];
    if (!empty($data['attachment_id']))  $reportData['attachment_id'] = $data['attachment_id'];
    if (!empty($data['created_at']))  $reportData['created_at'] = $data['created_at'];
    if (!empty($data['created_by']))  $reportData['created_by'] = $data['created_by'];

    if (!empty($data['product_id'])) {
      if ($product = $this->getProductByID($data['product_id'])) {
        $reportData['product_id']   = $product->id;
        $reportData['product_code'] = $product->code;
      } else {
        setLastError('addProductReport: Invalid product id.');
        return FALSE;
      }
    } else {
      setLastError('addProductReport: No product id.');
      return FALSE;
    }

    if (!empty($data['warehouse_id'])) {
      if ($warehouse = $this->getWarehouseByID($data['warehouse_id'])) {
        $reportData['warehouse_id']   = $warehouse->id;
        $reportData['warehouse_code'] = $warehouse->code;
      } else {
        setLastError('addProductReport: Invalid warehouse id.');
        return FALSE;
      }
    } else {
      setLastError('addProductReport: No warehouse id.');
      return FALSE;
    }

    $reportData = setCreatedBy($reportData);

    $this->db->trans_start();
    $this->db->insert('product_report', $reportData);
    $insertId = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      addEvent("Created Product Report [{$insertId}]: {$product->code}, {$data['condition']}, {$data['note']}", 'info');

      $this->updateProducts([
        [
          'product_id' => $reportData['product_id'],
          'condition' => $reportData['condition'],
          'updated_at' => $reportData['created_at'],
          'updated_by' => $reportData['created_by']
        ]
      ]);

      setLastError();
      return $insertId;
    }
    setLastError('Database: ' . $this->db->error()['message']);
    return FALSE;
  }

  /**
   * @param array $product Array of product
   *
   * [ *code, *name, *cost, *price, *markon, safety_stock_ratio,
   *  min_order_qty, image, category, track_quantity, *type(combo/service/standard), *sale_unit,
   *  *purchase_unit, price_ranges_value ]
   */
  public function addProducts($products)
  { // PASSED
    if (!empty($products) && is_array($products)) {
      $product_ids = [];
      $success = 0;

      foreach ($products as $product) {
        $product_data = [
          'code'               => $product['code'],
          'name'               => $product['name'],
          'unit'               => $product['unit'],
          'avg_cost'           => ($product['avg_cost'] ?? 0),
          'cost'               => $product['cost'],
          'price'              => ($product['price'] ?? 0),
          'warehouses'         => $product['warehouses'],
          'markon_price'       => ($product['markon_price'] ?? 0),
          'markon'             => ($product['markon'] ?? 0),
          'safety_stock_ratio' => ($product['safety_stock_ratio'] ?? 1),
          'min_order_qty'      => ($product['min_order_qty'] ?? 1),
          'image'              => ($product['image'] ?? 'no_image.png'),
          'iuse_type'          => ($product['iuse_type'] ?? NULL),
          'active'             => ($product['active'] ?? 0),
          'category_id'        => $product['category_id'],
          'subcategory_id'     => ($product['subcategory_id'] ?? NULL),
          // 'quantity'        => 0, //! NOT USED. Updated by ::syncProductQty
          'type'               => ($product['type'] ?? 'standard'),
          'supplier_id'        => ($product['supplier_id'] ?? NULL),
          'sale_unit'          => ($product['sale_unit'] ?? $product['unit']),
          'purchase_unit'      => ($product['purchase_unit'] ?? $product['unit']),
          'json_data'          => json_encode([
            'assigned_at'       => ($product['assigned_at'] ?? ''),
            'assigned_by'       => ($product['assigned_by'] ?? ''),
            'autocomplete'      => ($product['autocomplete'] ?? 0),
            'condition'         => ($product['condition'] ?? ''),
            'min_prod_time'     => ($product['min_prod_time'] ?? ''),
            'note'              => ($product['note'] ?? ''),
            'pic_note'          => ($product['pic_note'] ?? ''),
            'priority'          => ($product['priority'] ?? ''),
            'prod_time_qty'     => ($product['prod_time_qty'] ?? ''),
            'disposal_date'     => ($product['disposal_date'] ?? ''),
            'disposal_price'    => ($product['disposal_price'] ?? ''),
            'maintenance_qty'   => ($product['maintenance_qty'] ?? ''),
            'maintenance_cost'  => ($product['maintenance_cost'] ?? ''),
            'order_date'        => ($product['order_date'] ?? ''),
            'order_price'       => ($product['order_price'] ?? ''),
            'pic_id'            => ($product['pic_id'] ?? ''), // AKA ts_id (TS=Team Support)
            'priority'          => ($product['priority'] ?? ''),
            'purchased_at'      => ($product['purchased_at'] ?? $this->serverDateTime),
            'purchase_source'   => ($product['purchase_source'] ?? 'local'), // Default local.
            'sn'                => ($product['sn'] ?? ''),
            'updated_at'        => ($product['updated_at'] ?? ''),
            'updated_by'        => ($product['updated_by'] ?? ''),
          ])
        ];

        if (!empty($product['price_ranges_value'])) { // Price Ranges
          $ranges = [];
          foreach ($product['price_ranges_value'] as $price_range) {
            if (strlen($price_range) > 0) {
              $ranges[] = $price_range;
            }
          }
          $product_data['price_ranges_value'] = json_encode($ranges);
          unset($ranges);
        }

        $this->db->trans_start();
        $this->db->insert('products', $product_data);
        $product_id = $this->db->insert_id();
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
          $product_ids[] = $product_id;
          $success++;

          if ($product['type'] == 'combo') {
            if (!empty($product['combo_items']) && is_array($product['combo_items'])) { // Combo Items
              foreach ($product['combo_items'] as $combo_item) {
                $cb_data = [
                  'product_id' => $product_id,
                  'item_code'  => $combo_item['item_code'],
                  'quantity'   => filterQuantity($combo_item['quantity']),
                  'unit_price' => $product['price']
                ];

                $this->db->insert('combo_items', $cb_data);
              }
            }

            if (!empty($product['price_groups'])) { // Price Groups
              foreach ($product['price_groups'] as $price_group) {
                $key = ['price', 'price2', 'price3', 'price4', 'price5', 'price6'];
                $price_ranges = array_combine($key, $price_group['price_ranges']);

                $pp_data = [
                  'product_id' => $product_id,
                  'price_group_id' => $price_group['price_group_id'],
                  'price'  => $price_ranges['price'],
                  'price2' => $price_ranges['price2'],
                  'price3' => $price_ranges['price3'],
                  'price4' => $price_ranges['price4'],
                  'price5' => $price_ranges['price5'],
                  'price6' => $price_ranges['price6'],
                ];

                $this->addProductPrices($pp_data);
              }
            }
          }

          // Generate Warehouses Products for standard item or service item.
          if ($product['type'] == 'standard' || $product['type'] == 'service') {
            $warehouses = $this->getAllWarehouses();

            if ($warehouses) {
              foreach ($warehouses as $warehouse) {
                $whp_data = [
                  'product_id'   => $product_id,
                  'product_code' => $product['code'],
                  'warehouse_id' => $warehouse->id,
                  'warehouse_code' => $warehouse->code
                ];

                $this->addWarehouseProduct($whp_data);
              }
            }
          }

          if ($product['type'] == 'standard') {
            if (!empty($product['safety_stock'])) { // Safety Stocks
              $total_safety_stock = 0;

              foreach ($product['safety_stock'] as $safety_stock) {
                $wh_product_data = [
                  'safety_stock' => $safety_stock['quantity']
                ];

                $clause = ['product_id' => $product_id, 'warehouse_id' => $safety_stock['warehouse_id']];

                $this->updateWarehouseProduct($clause, $wh_product_data);

                $total_safety_stock += floatval($safety_stock['quantity']);
              }

              $clause = ['id' => $product_id];
              $this->db->update('products', ['safety_stock' => filterQuantity($total_safety_stock)], $clause);
            }

            if (!empty($product['stock_opname'])) {
              foreach ($product['stock_opname'] as $stock_opname) { // Stock Opname
                $wh_product_data = [
                  'user_id'  => $stock_opname['user_id'],
                  'so_cycle' => $stock_opname['so_cycle']
                ];
                $clause = ['product_id' => $product_id, 'warehouse_id' => $stock_opname['warehouse_id']];
                $this->updateWarehouseProduct($clause, $wh_product_data);
              }
            }
          }
        }
      }
      return $success;
    }
    return FALSE;
  }

  /**
   * Add product prices.
   * @param array $data [ *product_id, *price_group_id, *price, *price2, *price3, *price4, *price5,
   *  *price6 ]
   */
  public function addProductPrices($data)
  {
    $this->db->trans_start();
    $this->db->insert('product_prices', $data);
    $insert_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return $insert_id;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD PAYROLL.
   * @param array $data [ date, *user_id, *category_id, *amount, *status(pending|paid), note ]
   */
  public function addPayroll($data)
  {
    $payroll_data = [];

    $payroll_data['date'] = ($data['date'] ?? date('Y-m-d H:i:s'));
    $payroll_data['user_id'] = $data['user_id'];
    $payroll_data['category_id'] = $data['category_id'];
    $payroll_data['amount'] = $data['amount'];
    $payroll_data['status'] = $data['status'];

    if (!empty($data['note'])) $payroll_data['note'] = $data['note'];

    $this->db->trans_start();
    $this->db->insert('payrolls', $payroll_data);
    $payroll_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return $payroll_id;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD PAYROLL CATEGORY.
   * @param array $data [ *code, *name, *type(decrease|increase)].
   */
  public function addPayrollCategory($data)
  {
    $category_data = [];

    if (!empty($data['code'])) $category_data['code'] = $data['code'];
    if (!empty($data['name'])) $category_data['name'] = $data['name'];
    if (!empty($data['type'])) $category_data['type'] = $data['type'];

    $this->db->trans_start();
    $this->db->insert('payroll_categories', $category_data);
    $paycat_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return $paycat_id;
    }
    return FALSE;
  }

  /**
   * Add new stock purchase (NEW)
   * @param array $data []
   * @param array $items [ product_id, cost, purchased_qty, quantity ]
   */
  public function addPurchase($data, $items = [])
  {
    $supplier = $this->getSupplierByID($data['supplier_id']);

    $data['reference']     = $this->getReference('purchase');
    $data['supplier_name'] = $supplier->name;

    if ($items) {
      $grandTotal = 0;

      foreach ($items as $item) {
        $grandTotal += ($item['cost'] * $item['purchased_qty']);
      }

      $data['grand_total'] = $grandTotal;
    }

    $data = setCreatedBy($data);

    $this->db->insert('purchases', $data);

    if ($this->db->affected_rows()) {
      $purchaseId = $this->db->insert_id();

      if ($this->getReference('purchase') == $data['reference']) {
        $this->updateReference('purchase');
      }

      foreach ($items as $item) {
        $this->addStockQuantity([
          'date'          => $data['date'],
          'purchase_id'   => $purchaseId,
          'product_id'    => $item['product_id'],
          'cost'          => $item['cost'],
          'purchased_qty' => $item['purchased_qty'],
          'quantity'      => $item['quantity'],
          'warehouse_id'  => $data['warehouse_id'],
          'status'        => $data['status'],
          'spec'          => ($item['spec'] ?? '')
        ]);
      }
      return $purchaseId;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD SALE.
   * @param array $data [ date, *customer_id, *biller_id, *warehouse_id, no_po, note,
   *  discount, shipping, status, payment_status, payment_term, created_by,
   *  paid, attachment, payment_method ]
   * @param array $items [ product_id ]
   */
  public function addSale($data = [], $items = [])
  {
    $biller = $this->getBillerByID($data['biller_id']);
    if (!$biller) return FALSE;

    $customer = $this->getCustomerByID($data['customer_id']);
    if (!$customer) return FALSE;

    $isSpecialCustomer = isSpecialCustomer($customer->id);

    $warehouse = $this->getWarehouseByID($data['warehouse_id']);
    if (!$warehouse) return FALSE;

    $grandTotal  = 0;
    $totalPrice  = 0;
    $total_items = 0.0;
    $date = filterDateTime($data['date'] ?? $this->serverDateTime);
    $reference = $this->getReference('sale');

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

    // Discount.
    $grandTotal = ($totalPrice - ($data['discount'] ?? 0));

    // Get balance.
    $balance = ($isSpecialCustomer ? $grandTotal : 0);

    // Determine use TB by biller and warehouse, if both different, then use tb (1).
    $use_tb = isTBSale((int)$data['biller_id'], (int)$data['warehouse_id']);
    // $use_tb = (strcasecmp($biller->name, $warehouse->name) === 0 ? 0 : 1);

    // Get payment term.
    $payment_term = filterDecimal($data['payment_term'] ?? 1);
    $payment_term = ($payment_term > 0 ? $payment_term : 1);

    $sale_data = [
      'date'            => $date,
      'reference'       => $reference,
      'customer_id'     => $customer->id,
      'customer_name'   => $customer->name,
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
      'created_by'      => ($data['created_by'] ?? XSession::get('user_id')),
      'total_items'     => $total_items,
      'paid'            => filterDecimal($data['paid'] ?? 0),
      'attachment_id'   => ($data['attachment_id'] ?? NULL),
      'attachment'      => ($data['attachment'] ?? NULL),
      'payment_method'  => ($data['payment_method'] ?? NULL),
      'use_tb'          => $use_tb,
      'json'            => json_encode([
        'approved'          => ($data['approved'] ?? 0),
        'cashier_by'        => ($data['cashier_by'] ?? ''),
        'source'            => ($data['source'] ?? ''),
        'est_complete_date' => ($data['est_complete_date'] ?? ''),
        'payment_due_date'  => ($data['payment_due_date'] ?? getWorkingDateTime(date('Y-m-d H:i:s', strtotime('+1 days'))))
      ]),
      'json_data'       => json_encode([
        'approved'          => ($data['approved'] ?? 0),
        'cashier_by'        => ($data['cashier_by'] ?? ''),
        'source'            => ($data['source'] ?? ''),
        'est_complete_date' => ($data['est_complete_date'] ?? ''),
        'payment_due_date'  => ($data['payment_due_date'] ?? getWorkingDateTime(date('Y-m-d H:i:s', strtotime('+1 days'))))
      ])
    ];

    $this->db->trans_start();
    $this->db->insert('sales', $sale_data);
    $sale_id = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      $sale = $this->getSaleByID($sale_id);

      if ($this->getReference('sale') == $sale_data['reference']) {
        $this->updateReference('sale');
      }

      if ($this->addSaleItems($sale_id, $items)) {
        // Cannot add due date if not have sale item first.
        if ($isSpecialCustomer) addSaleDueDate($sale->id);

        addEvent("Created Sale [{$sale->id}: {$sale->reference}]");
        return $sale_id;
      }
    }

    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD SALE ITEMS.
   * @param int $sale_id Sale ID.
   * @param array $items [[ *product_id, *price, spec, status, width, length, *quantity, *warehouse_id ]]
   */
  public function addSaleItems($sale_id, $items)
  {
    $sale = $this->getSaleByID($sale_id);
    $isSpecialCustomer = isSpecialCustomer($sale->customer_id);

    if ($sale) {
      foreach ($items as $item) {
        $product      = $this->getProductByID($item['product_id']);
        $productJS    = getJSON($product->json_data);
        $price        = filterDecimal($item['price']);
        $qty          = filterDecimal($item['quantity']);
        $finished_qty = (!empty($item['finished_qty']) ? filterDecimal($item['finished_qty']) : 0);

        // json_data
        $item_w            = filterQuantity($item['width']  ?? 0);
        $item_l            = filterQuantity($item['length'] ?? 0);
        $item_area         = filterQuantity($item_w * $item_l);
        $item_spec         = ($item['spec']        ?? '');
        $item_operator     = ($item['operator_id'] ?? '');
        $item_due_date     = ($item['due_date']    ?? '');
        $item_status       = ($item['status']      ?? $sale->status);
        $item_completed_at = ($item['completed_at']  ?? '');

        $total_qty = ($item_area > 0 ? $item_area * $qty : $qty);

        $sale_items_data = [
          'date'         => ($item['date'] ?? $sale->date),
          'sale'         => $sale->reference,
          'sale_id'      => $sale->id,
          'product_id'   => $product->id,
          'product_code' => $product->code,
          'product_name' => $product->name,
          'product_type' => $product->type,
          'price'        => $price,
          'quantity'     => filterQuantity($total_qty), // Total Quantity.
          'finished_qty' => filterQuantity($finished_qty),
          // 'warehouse_id' => $item['warehouse_id'],
          'subtotal'     => round($price * $total_qty),
          'json'    => json_encode([ // filterQuantity() is the MOST IMPORTANT THING !!!
            'w'            => filterQuantity($item_w),
            'l'            => filterQuantity($item_l),
            'area'         => filterQuantity($item_area),
            'sqty'         => filterQuantity($qty), // Quantity unit.
            'spec'         => $item_spec,
            'status'       => $item_status,
            'operator_id'  => $item_operator,
            'due_date'     => (filterDateTime($item_due_date) ?? ''),
            'completed_at' => (filterDateTime($item_completed_at) ?? '')
          ]),
          'json_data'    => json_encode([ // filterQuantity() is the MOST IMPORTANT THING !!!
            'w'            => filterQuantity($item_w),
            'l'            => filterQuantity($item_l),
            'area'         => filterQuantity($item_area),
            'sqty'         => filterQuantity($qty), // Quantity unit.
            'spec'         => $item_spec,
            'status'       => $item_status,
            'operator_id'  => $item_operator,
            'due_date'     => (filterDateTime($item_due_date) ?? ''),
            'completed_at' => (filterDateTime($item_completed_at) ?? '')
          ])
        ];

        $this->db->trans_start();
        $this->db->insert('sale_items', $sale_items_data);
        $saleitem_id = $this->db->insert_id();
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
          $saleItemJS = getJSON($sale_items_data['json_data']);

          // AUTO COMPLETE ENGINE
          if (
            $sale->status != 'draft' && $isSpecialCustomer &&
            (isset($productJS->autocomplete) && $productJS->autocomplete == 1)
          ) {
            $this->completeSaleItem($saleitem_id, ['quantity' => $qty, 'created_by' => $item_operator]);
            continue; // Prevent double complete.
          }

          // if ($item_status == 'completed' || $item_status == 'completed_partial' || $item_status == 'delivered') {
          if (isCompleted($item_status)) {
            if ($product->type == 'combo') {
              $combo_items = $this->getProductComboItems($product->id);

              if ($combo_items) {
                foreach ($combo_items as $combo_item) {
                  $raw_item = $this->getProductByCode($combo_item->code);

                  $finished_qty = ($finished_qty > 0 ? $finished_qty : $total_qty);

                  if ($raw_item->type == 'standard') {
                    $this->decreaseStockQuantity([
                      'date'         => ($item_completed_at ?? $sale->date),
                      'sale_id'      => $sale_id,
                      'saleitem_id'  => $saleitem_id,
                      'product_id'   => $raw_item->id,
                      'quantity'     => filterQuantity($combo_item->qty * $finished_qty),
                      'warehouse_id' => $item['warehouse_id'],
                      'created_by'   => $item_operator
                    ]);
                  } else if ($raw_item->type == 'service') {
                    $this->increaseStockQuantity([
                      'date'         => ($item_completed_at ?? $sale->date),
                      'sale_id'      => $sale_id,
                      'saleitem_id'  => $saleitem_id,
                      'product_id'   => $raw_item->id,
                      'quantity'     => filterQuantity($combo_item->qty * $finished_qty),
                      'warehouse_id' => $item['warehouse_id'],
                      'created_by'   => $item_operator
                    ]);
                  }
                }
              }
            }

            if ($product->type == 'service') {
              $this->increaseStockQuantity([
                'date'         => ($item_completed_at ?? $sale->date),
                'sale_id'      => $sale_id,
                'saleitem_id'  => $saleitem_id,
                'product_id'   => $item['product_id'],
                'quantity'     => filterQuantity($finished_qty),
                'warehouse_id' => $item['warehouse_id'],
                'created_by'   => $item_operator
              ]);
            }

            if ($product->type == 'standard') {
              $this->decreaseStockQuantity([
                'date'         => ($item_completed_at ?? $sale->date),
                'sale_id'      => $sale_id,
                'saleitem_id'  => $saleitem_id,
                'product_id'   => $item['product_id'],
                'quantity'     => filterQuantity($finished_qty),
                'warehouse_id' => $item['warehouse_id'],
                'created_by'   => $item_operator
              ]);
            }

            $saleItemJS->status = $item_status; // Last item status.
            $sale_items_data['json_data'] = json_encode($saleItemJS);
          }

          $this->db->update('sale_items', $sale_items_data, ['id' => $saleitem_id]);
        }
      }

      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD PAYMENT FOR SALE.
   * @param array $data [*sale_id, *amount, *method(Cash|Transfer)]
   * @param bool $by_validation Is using payment validation or not. Default FALSE.
   */
  public function addSalePayment($data, $by_validation = FALSE)
  {
    // Double/Over Payment Protection
    $sale    = $this->getSaleByID($data['sale_id']);
    $saleJS  = getJSON($sale->json_data);
    $balance = floatval($sale->grand_total) - floatval($sale->paid);
    if (floatval($data['amount']) > floatval($balance)) return FALSE;

    $data['created_at']     = ($data['created_at'] ?? $data['date'] ?? date('Y-m-d H:i:s'));
    $data['reference_date'] = $sale->created_at;

    if ($sale && Payment::add($data)) {
      // Update sale payment
      $sale_data = [
        'payment_method' => $data['method']
      ];

      if ($cashierBy = XSession::get('user_id')) {
        $sale_data['cashier_by'] = $cashierBy;
      }

      if (!empty($data['attachment_id'])) $sale_data['attachment_id'] = $data['attachment_id'];

      // For W2P change order date if no payments before.
      // if (!$payments && $saleJS->source == 'W2P') {
      //   $sale_data['date'] = date('Y-m-d H:i:s');
      // }

      if ($by_validation) {
        // $sale_data['payment_status'] = 'paid'; // paid will become paid or partial by syncSales.
      }

      $saleJS = getJSON($sale->json_data);

      if (!$saleJS || empty($saleJS->est_complete_date)) {
        addSaleDueDate($sale->id);
      }

      if ($saleItems = $this->getSaleItems(['sale_id' => $sale->id])) {
        foreach ($saleItems as $saleItem) {
          $saleItemJS = getJSON($saleItem->json_data);
          $product = $this->getProductByID($saleItem->product_id);
          $productJS = getJSON($product->json_data);

          if (isCompleted($saleItemJS->status)) continue; // Ignore if already completed.

          // AUTOCOMPLETE ENGINE IF ANY PAID AND NOT WEB2PRINT TYPE.
          if (!isWeb2Print($sale->id) && !empty($productJS->autocomplete) && $productJS->autocomplete == 1) {
            $this->completeSaleItem($saleItem->id, [
              'quantity' => $saleItem->quantity,
              'created_by' => $saleItemJS->operator_id
            ]);
          }
        }
      }

      $this->updateSale($sale->id, $sale_data);
      $this->syncSales(['sale_id' => $sale->id]); // Update status.
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD SALE TB.
   *
   * @param array $data [ *last_sync_date, *from_biller_id, *to_warehouse_id, *start_date, *end_date,
   *  *amount, created_by ]
   */
  public function addSaleTB($data)
  {
    $saleTBData = $data;

    $saleTBData['created_by'] = ($data['created_by'] ?? XSession::get('user_id'));

    $this->db->trans_start();
    $this->db->insert('sales_tb', $saleTBData);
    $insertId = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return $insertId;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD SALE TB PAYMENT.
   */
  public function addSaleTBPayment($saleTBId)
  {
    $salesTB = $this->getSalesTB(['id' => $saleTBId]);

    if ($salesTB) {
      $date = date('Y-m-d H:i:s');

      if ($salesTB[0]->status == 'paid') {
        setLastError('Sales TB Payment is already paid.');
        return FALSE;
      }

      $warehouse = $this->getWarehouseByID($salesTB[0]->to_warehouse_id);
      if (!$warehouse) {
        return FALSE;
      }

      $billerIn = $this->getBillerByName($warehouse->name);
      if (!$billerIn) {
        return FALSE;
      }

      $billerOut = $this->getBillerByID($salesTB[0]->from_biller_id);
      if (!$billerOut) {
        return FALSE;
      }

      $banksIn  = $this->getBanks(['biller_id' => $billerIn->id, 'number' => '465461984']);
      if (!$banksIn) {
        return FALSE;
      }

      $banksOut = $this->getBanks(['biller_id' => $billerOut->id, 'number' => '465461984']);
      if (!$banksOut) {
        return FALSE;
      }

      $sysUser = $this->getUserByUsername('system');
      if (!$sysUser) {
        return FALSE;
      }

      $noteMsg = "Pembayaran Sales TB dari {$billerOut->name} ke {$billerIn->name} berhasil.";

      $expenseCategory = $this->getExpenseCategoryByCode('K039'); // Sales TB.

      $expenseData = [
        'date' => $date,
        'bank_id' => $banksOut[0]->id,
        'biller_id' => $billerOut->id,
        'amount' => $salesTB[0]->amount,
        'note' => $noteMsg,
        'created_by' => XSession::get('user_id'),
        'category_id' => $expenseCategory->id,
        'payment_status' => 'pending',
        'status' => 'need_approval'
      ];

      if ($expenseId = $this->addExpense($expenseData)) {
        if ($this->updateExpense($expenseId, ['approved_by' => $sysUser->id, 'status' => 'approved'])) {
          if ($this->updateExpense($expenseId, ['payment_status' => 'paid'])) { // Do Expense Payment.
            $incomeCategory = $this->getIncomeCategoryByCode('M008'); // Sales TB.

            $incomeData = [
              'date' => $date,
              'amount' => $salesTB[0]->amount,
              'note' => $noteMsg,
              'created_by' => XSession::get('user_id'),
              'category_id' => $incomeCategory->id,
              'biller_id' => $billerIn->id,
              'bank_id' => $banksIn[0]->id
            ];

            if ($this->addIncome($incomeData)) { // Do Income Payment.
              $this->updateSaleTB($saleTBId, ['status' => 'paid']);
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Add new schedule.
   * @param array $data [ *biller_id, *valid_date, hour_sun, hour_mon, hour_tue, hour_wed, hour_thu,
   *  hour_fri, hour_sat ]
   */
  public function addSchedule($data)
  {
    $this->db->insert('schedule', $data);

    if ($this->db->affected_rows()) {
      return $this->db->insert_id();
    }
    return FALSE;
  }

  /**
   * Add new stock data.
   * @param array $data [ *(adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id),
   * saleitem_id, *product_id, cost, price, *quantity, adjustment_qty, spec, status(sent/received),
   * *warehouse_id, created_at, created_by, *updated_at, updated_by ]
   */
  public function addStock($data)
  {
    $data = setCreatedBy($data);

    $this->db->insert('stocks', $data);

    if ($this->db->affected_rows()) {
      $insertId = $this->db->insert_id();

      return $insertId;
    }
    return FALSE;
  }

  /**
   * @deprecated Remove soon
   */
  public function addStockAdjustment($data, $products)
  {
    $data['reference'] = $this->getReference('adjustment');
    $this->db->insert('adjustments', $data);
    $adjustment_id = $this->db->insert_id();

    if ($this->db->affected_rows()) {
      if ($data['reference'] == $this->getReference('adjustment')) {
        $this->updateReference('adjustment');
      }

      foreach ($products as $product) {
        $warehouse = Warehouse::getRow(['id' => $product['warehouse_id']]);

        $this->addStockQuantity([
          'date'           => $data['date'],
          'adjustment_id'  => $adjustment_id,
          'product_id'     => $product['product_id'],
          'quantity'       => $product['quantity'],
          'adjustment_qty' => $product['adjustment_qty'],
          'status'         => $product['type'],
          'warehouse_id'   => $product['warehouse_id'],
          'warehouse'      => $warehouse->code,
          'created_by'     => ($data['created_by'] ?? XSession::get('user_id'))
        ]);
      }

      return $adjustment_id;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD INTERNAL USE.
   */
  public function addStockInternalUse($data, $items = [])
  {
    if (stripos($data['category'], 'report') === 0) { // Legacy, do not erase!
      $ref = $this->getReference('cmreport'); // As report only.
    } else {
      $ref = $this->getReference('iuse'); // Default internal use.
    }

    $data['reference'] = $ref;

    if ($data['category'] == 'consumable') {
      $data['status'] = 'completed';
    }

    $data = setCreatedBy($data);

    DB::table('internal_uses')->insert($data);

    if ($insertId = DB::insertID()) {
      if ($this->getReference('iuse') == $data['reference']) {
        $this->updateReference('iuse');
      }

      if ($items) {
        if ($data['category'] == 'consumable') {
          $data['status'] = 'sent';
        }

        foreach ($items as $item) {
          $product  = Product::getRow(['id' => $item['product_id']]);
          $category = Category::getRow(['id' => $product->category_id]);

          if ($data['category'] == 'consumable') { // Consumable = Disposal.
            if ($category->code == 'AST' || $category->code == 'EQUIP') {
              $this->updateProducts([[
                'product_id'      => $product->id,
                'disposal_date'   => $data['created_at'],
                'disposal_price'  => $item['price']
              ]]);
            }
          } else if ($data['category'] == 'sparepart') { // Maintenance for machine.
            $machine = $this->getMachineByID($item['machine_id']);

            if ($machine) {
              $maintain = (!empty($machine->maintenance_qty) ? $machine->maintenance_qty : 0);
              $mainCost = (!empty($machine->maintenance_cost) ? $machine->maintenance_cost : 0);

              $maintain++;
              $mainCost += $item['price'];

              $this->updateProducts([[
                'product_id'        => $machine->id,
                'maintenance_qty'   => $maintain,
                'maintenance_cost'  => $mainCost,
              ]]);
            }
          }

          Stock::add([
            'internal_use_id' => $insertId,
            'product_id'      => $item['product_id'],
            'price'           => $item['price'],
            'quantity'        => $item['quantity'],
            'spec'            => $item['spec'],
            'status'          => $data['status'],
            'warehouse_id'    => $data['from_warehouse_id'],
            'machine_id'      => $item['machine_id'],
            'unique_code'     => generateInternalUseUniqueCode($data['category']),
            'ucr'             => ($item['ucr'] ?? NULL),
            'created_at'      => $data['created_at'],
            'created_by'      => $data['created_by']
          ]);
        }
      }

      return $insertId;
    }

    return FALSE;
  }

  /**
   * Add New Stock Opname
   * @param array $data []
   * @param array $items []
   */
  public function addStockOpname($data, $items)
  {
    if (!empty($data) && !empty($items)) {
      $adjustment_plus_items = [];
      $adjustment_min_items = []; // For reject qty only.
      $total_lost = 0;
      $total_plus = 0;
      $items2 = [];
      $status = '';
      $warehouse = $this->getWarehouseByID($data['warehouse_id']);

      $data['warehouse_code'] = $warehouse->code;

      foreach ($items as $item) {
        $item_quantity   = filterDecimal($item['quantity']); // Real qty.
        $item_first_qty  = filterDecimal($item['first_qty']);
        $item_reject_qty = filterDecimal($item['reject_qty']);
        // $item_last_qty   = filterDecimal($item['last_qty']); No last qty on ADD SO.

        $product = $this->getProductByID($item['product_id']);

        $rest_qty = ($item_first_qty - $item_quantity) + $item_reject_qty; // If minus, then become debt.
        $item['price'] = ($warehouse->code == 'LUC' ? $product->cost : $product->markon_price); // By Cost.
        // $item['price'] = $product->avg_cost; // By Average cost.
        // $item['price'] = $product->markon_price; // Get warehouse product price.
        $item['subtotal'] = ceil($item['price'] * $rest_qty);

        if ($rest_qty > 0) { // If rest_qty > 0 then add as adjustment plus and status Good.
          $adjustment_plus_items[] = [
            'product_id'     => $item['product_id'],
            'quantity'       => $item['first_qty']
          ];

          $total_plus += $item['subtotal'];
        } else if ($item_first_qty < $item_quantity) { // If minus
          if (($item_first_qty + $item_reject_qty) == $item_quantity) { // Adjustment min and status Good.
            $adjustment_min_items[] = [
              'product_id'     => $item['product_id'],
              'quantity'       => $item['first_qty']
            ];
          } else { // Status Checked.
            if (empty($status)) $status = 'checked';
          }

          $total_lost += $item['subtotal'];
        } else if ($item['first_qty'] == $item['quantity']) { // No adjustment and status Excellent.
          // Nothing to do. Ignored.
        }

        $items2[] = $item;
      }
      $items = $items2;
      unset($items2);

      $data['reference'] = $this->getReference('opname');
      $data['total_lost'] = $total_lost;
      $data['total_plus'] = $total_plus;

      if ($status == 'checked') {
        $data['status'] = $status;
      } else if (empty($status) && ($adjustment_plus_items || $adjustment_min_items)) {
        $data['status'] = 'good';
      } else if (empty($status) && empty($adjustment_plus_items)) {
        $data['status'] = 'excellent';
      }

      if ($this->Owner) { // Debugging only.
        // rd_print('$data', $data, '$items', $items,
        //   '$adjustment_plus_items', $adjustment_plus_items,
        //   '$adjustment_min_items', $adjustment_min_items);
        // die();
      }

      $this->db->trans_start();
      $this->db->insert('stock_opnames', $data);
      $opname_id = $this->db->insert_id();
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        if ($this->getReference('opname') == $data['reference']) {
          $this->updateReference('opname');
        }

        if ($adjustment_plus_items) { // Adjustment Plus.
          $adjustmentData = [
            'date'         => $data['date'],
            'warehouse_id' => $warehouse->id,
            'mode'         => 'overwrite',
            'note'         => $data['reference'],
            'created_by'   => $data['created_by']
          ];

          if ($adjustment_id = $this->addAdjustmentStock($adjustmentData, $adjustment_plus_items)) {
            $this->db->update('stock_opnames', ['adjustment_plus_id' => $adjustment_id], ['id' => $opname_id]);
          }
        }

        if ($adjustment_min_items) { // Adjustment Min.
          $adjustmentData = [
            'date'         => $data['date'],
            'warehouse_id' => $warehouse->id,
            'mode'         => 'overwrite',
            'note'         => $data['reference'],
            'created_by'   => $data['created_by']
          ];

          if ($adjustment_id = $this->addAdjustmentStock($adjustmentData, $adjustment_min_items)) {
            // adjustment_min_id used by confirmed SO.
            // $this->db->update('stock_opnames', ['adjustment_min_id' => $adjustment_id], ['id' => $opname_id]);
          }
        }

        foreach ($items as $item) {
          $product = $this->getProductByID($item['product_id']);

          $item['opname_id']      = $opname_id;
          $item['product_code']   = $product->code;
          $item['warehouse_id']   = $warehouse->id;
          $item['warehouse_code'] = $warehouse->code;

          $this->db->insert('stock_opname_items', $item);
        }

        $user = $this->getUserByID($data['created_by']);
        $json_data = json_decode($user->json_data);
        $json_data->so_cycle = filterDecimal($data['cycle']) + 1; // Increment Stock Opname Cycle.

        $this->updateUser($data['created_by'], ['json_data' => json_encode($json_data)]);

        return $opname_id;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD STOCK PURCHASES.
   * @param array $data [date, warehouse_id*, note, grand_total*, created_by]
   */
  public function addStockPurchase($data, $items = [])
  {
    if (empty($data) || empty($items)) return FALSE;
    $supplier = $this->getSupplierByID($data['supplier_id']);

    $data['reference']     = $this->getReference('purchase');
    $data['created_by']    = ($data['created_by'] ?? XSession::get('user_id'));
    $data['supplier_name'] = $supplier->name;

    $this->db->insert('purchases', $data);

    if ($this->db->affected_rows()) {
      $purchaseId = $this->db->insert_id();

      if ($this->getReference('purchase') == $data['reference']) {
        $this->updateReference('purchase');
      }

      foreach ($items as $item) {
        $this->addStockQuantity([
          'date'          => $data['date'],
          'purchase_id'   => $purchaseId,
          'product_id'    => $item['product_id'],
          'cost'          => $item['cost'],
          'purchased_qty' => $item['purchased_qty'],
          'quantity'      => $item['quantity'],
          'warehouse_id'  => $item['warehouse_id'],
          'status'        => $item['status'],
          'unit_id'       => $item['unit_id'],
          'spec'          => $item['spec']
        ]);
      }
      return $purchaseId;
    }
    return FALSE;
  }

  /**
   * Add stock purchase by supplier id.
   * @param int $supplier_id Supplier ID.
   */
  public function addStockPurchaseBySupplierID($supplier_id)
  {
    if ($supplier_id) {
      $supplier       = $this->getSupplierByID($supplier_id);
      $supplier_items = $this->getSupplierProducts($supplier_id); // Get supplier items.

      if ($supplier_items && $supplier) {
        $date = date('Y-m-d H:i:s');
        $grand_total = 0;
        $purchase_items = [];
        $purchase_qty = 0;
        $biller    = $this->getBillerByName('Lucretia Enterprise'); // Default biller to purchase.
        $warehouse = $this->getWarehouseByCode('LUC'); // Default warehouse to purchase.

        foreach ($supplier_items as $item) {
          // No purchase item if safety_stock is 0 or not valid integer > 0
          if ($item->safety_stock <= 0 || !$item->safety_stock) continue;
          // Get warehouse products.
          $whp = $this->getWarehouseProduct($item->id, $warehouse->id);
          // Calculate formula to get quantity of purchase.
          $purchase_qty = getOrderStock($whp->quantity, $item->min_order_qty, $item->safety_stock);

          if ($purchase_qty <= 0) continue; // If purchase qty is 0 or less then ignore.

          $purchase_item = [
            'code'          => $item->code, // Debugging purpose only. Ignored by addStockPurchase.
            'date'          => $date,
            'biller_id'     => $biller->id,
            'product_id'    => $item->id,
            'cost'          => $item->cost,
            'quantity'      => 0,
            'purchased_qty' => $purchase_qty,
            'warehouse_id'  => $warehouse->id,
            'status'        => 'need_approval',
            'unit_id'       => $item->purchase_unit,
            'spec'          => '',
            'created_by'    => XSession::get('user_id')
          ];

          $purchase_items[] = $purchase_item;

          $grand_total += ($purchase_qty * $item->cost);
        }

        $purchase_data = [
          'date'           => $date,
          'warehouse_id'   => $warehouse->id,
          'note'           => '',
          'grand_total'    => $grand_total,
          'created_by'     => XSession::get('user_id'),
          'payment_status' => 'pending', // Payment must pending after add stock purchase.
          'status'         => 'need_approval',
          'payment_term'   => $supplier->payment_term,
          'supplier_id'    => $supplier_id,
        ];

        if ($this->addStockPurchase($purchase_data, $purchase_items)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function addStockPurchasePayment($purchase_id, $data)
  {
    $this->db->trans_start();

    $discount_amount = ($data['discount_amount'] ?? 0);
    unset($data['discount_amount']);

    if ($this->db->insert('payments', $data)) {
      $paymentId = $this->db->insert_id();

      $purchase_data = [
        'payment_status' => 'need_approval',
        'discount' => $discount_amount
      ];

      if (isset($data['attachment_id'])) $purchase_data['attachment_id'] = $data['attachment_id']; // Payment attachment.

      $this->db->update('purchases', $purchase_data, ['id' => $purchase_id]);

      addEvent("Created Purchase Payment [{$paymentId}]");
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return $this->getPaymentByID($paymentId);
    }
    return NULL;
  }

  /**
   * THE ONLY STOCK GATEWAY FUNCTION FOR ANY QUANTITY INCREASE/DECREASE. DO NOT USE ANY STOCK GATEWAY FUNCTION EXCEPT THIS !!!
   * Status: sent = Decrease qty, received = Increase qty.
   * @param array $data
   * [date, *(adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id), saleitem_id,
   * *product_id, cost, price, *quantity, adjustment_qty, spec, *status(sent/received),
   * *warehouse_id, created_at, created_by, updated_by]
   */
  public function addStockQuantity($data)
  {
    if (empty($data['product_id']))   return FALSE;
    if (empty($data['quantity']) && $data['quantity'] != 0) return FALSE;
    if (empty($data['status']))       return FALSE;
    if (empty($data['warehouse_id'])) return FALSE;

    $ci = $this;

    $ret = $this->ridintek->mutex('stock')->on('lock', function ($mutex) use ($ci, $data) {
      $stock_data = [];

      $stock_data['date']       = ($data['created_at'] ?? $data['date'] ?? date('Y-m-d H:i:s'));
      $stock_data['created_at'] = $stock_data['date'];
      $stock_data['created_by'] = getUserCreator($data['created_by'] ?? NULL);

      if (!empty($data['adjustment_id']))   $stock_data['adjustment_id']   = $data['adjustment_id'];
      if (!empty($data['internal_use_id'])) $stock_data['internal_use_id'] = $data['internal_use_id'];
      if (!empty($data['pm_id']))           $stock_data['pm_id']           = $data['pm_id'];
      if (!empty($data['purchase_id']))     $stock_data['purchase_id']     = $data['purchase_id'];
      if (!empty($data['sale_id']))         $stock_data['sale_id']         = $data['sale_id'];
      if (!empty($data['transfer_id']))     $stock_data['transfer_id']     = $data['transfer_id'];
      if (!empty($data['saleitem_id']))     $stock_data['saleitem_id']     = $data['saleitem_id'];

      if (isset($data['cost']))           $stock_data['cost']           = $data['cost'];
      if (isset($data['price']))          $stock_data['price']          = $data['price'];
      if (isset($data['quantity']))       $stock_data['quantity']       = $data['quantity'];
      if (isset($data['adjustment_qty'])) $stock_data['adjustment_qty'] = $data['adjustment_qty'];
      if (isset($data['purchased_qty']))  $stock_data['purchased_qty']  = $data['purchased_qty'];
      if (isset($data['spec']))           $stock_data['spec']           = $data['spec'];
      if (!empty($data['status']))        $stock_data['status']         = $data['status'];
      if (isset($data['subtotal']))       $stock_data['subtotal']       = $data['subtotal'];

      if (isset($data['price']) && isset($data['quantity'])) {
        $stock_data['subtotal'] = filterDecimal($data['price']) * filterDecimal($data['quantity']);
      }

      if (!empty($data['machine_id']))  $stock_data['machine_id']   = $data['machine_id'];
      if (!empty($data['ucr']))         $stock_data['ucr']          = $data['ucr'];
      if (!empty($data['unique_code'])) $stock_data['unique_code']  = $data['unique_code'];
      if (isset($data['json_data']))    $stock_data['json_data']    = $data['json_data'];

      $product = $ci->getProductByID($data['product_id']);

      if ($product) {
        $category = $ci->getProductCategoryByID($product->category_id);
        $unit     = $ci->getUnitByID($product->unit);

        $stock_data['product_id']   = $product->id;
        $stock_data['product_code'] = $product->code;
        $stock_data['product_name'] = $product->name;
        $stock_data['product_type'] = $product->type;

        // Both for addStockQuantity only.
        // Cost = Vendor price (Purchase). Price = Mark On Price (Transfer).
        if (!isset($data['cost']))  $stock_data['cost']  = $product->cost;
        if (!isset($data['price'])) $stock_data['price'] = $product->price;

        $stock_data['category_id']   = $category->id;
        $stock_data['category_code'] = $category->code;
        $stock_data['category_name'] = $category->name;

        if ($unit) {
          $stock_data['unit_id']   = $unit->id;
          $stock_data['unit_code'] = $unit->code;
          $stock_data['unit_name'] = $unit->name;
        }
      } else return FALSE;

      $warehouse = $ci->getWarehouseByID($data['warehouse_id']);

      if ($warehouse) {
        $stock_data['warehouse_id']   = $warehouse->id;
        $stock_data['warehouse_code'] = $warehouse->code;
        $stock_data['warehouse_name'] = $warehouse->name;
      } else return FALSE;

      $ci->db->trans_start();
      $ci->db->insert('stocks', $stock_data);
      $insert_id = $ci->db->insert_id();
      $ci->db->trans_complete();

      if ($ci->db->trans_status() !== FALSE) {
        // Increase and decrease Warehouse Product Quantity.
        if (strtolower($data['status']) == 'received') {
          $this->increaseWarehouseQty($data['product_id'], $data['warehouse_id'], $data['quantity']);
        } else if (strtolower($data['status']) == 'sent') {
          $this->decreaseWarehouseQty($data['product_id'], $data['warehouse_id'], $data['quantity']);
        }
        return $insert_id;
      }
      return FALSE;
    })->create()->close();
    return $ret;
  }

  public function addSupplier($data)
  {
    if ($this->db->insert('suppliers', $data)) {
      return TRUE;
    }
    return FALSE;
  }

  public function addSuppliers($data)
  {
    if ($this->db->insert_batch('suppliers', $data)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add new Tracking POD.
   * @param array $data [ *pod_id, *start_click, *end_click, *mc_reject, *erp_click, *tolerance,
   *  *cost_click, adjustment_id, *warehouse_id, attachment, note, created_at *created_by ]
   */
  public function addTrackingPOD($data)
  {
    $dateTime       = ($data['created_at'] ?? date('Y-m-d H:i:s'));
    $todayClick = 0;

    $tracks = $this->getTrackingPODs(
      ['pod_id' => $data['pod_id'], 'warehouse_id' => $data['warehouse_id']],
      [
        'start_date' => date('Y-m-d', strtotime($dateTime)), 'end_date'   => date('Y-m-d', strtotime($dateTime))
      ]
    );

    foreach ($tracks as $track) {
      $todayClick += $track->usage_click;
    }

    // Convert to minus.
    $mcReject = ($data['mc_reject'] > 0 ? $data['mc_reject'] * -1 : $data['mc_reject']);

    $usageClick     = $data['end_click'] - $data['start_click'];
    $opReject       = $data['erp_click'] - $data['end_click'] - $mcReject;
    // If opReject minus then opReject else if plus then 0.
    $opReject       = ($opReject < 0 ? $opReject : 0);
    $toleranceClick = round(($mcReject + $opReject) * 0.01 * $data['tolerance']); // 0.01 == 100%
    $balance        = ($mcReject + $opReject) - $toleranceClick;
    $totalPenalty   = ($balance < 0 ? $balance * $data['cost_click'] : 0);
    $note           = ($data['note'] ?? '');

    $data['usage_click']     = $usageClick;
    $data['mc_reject']       = $mcReject;
    $data['op_reject']       = $opReject;
    $data['tolerance_click'] = $toleranceClick;
    $data['balance']         = $balance;
    $data['total_penalty']   = $totalPenalty;

    $this->db->insert('trackingpod', $data); // ORIGINAL INSERT.
    $errDB = $this->db->error()['message'];
    $trackId = $this->db->insert_id();

    if ($this->db->affected_rows()) {
      if ($data['end_click'] != $data['erp_click']) { // Adjustment if end_click != erp_click.
        // TEST WITHOUT QTY ADJUSTMENT. BETTER. DO NOT ADJUSTMENT AGAIN!
        // $adjustmentData = [
        //   'date'         => ($data['created_at'] ?? date('Y-m-d H:i:s')),
        //   'warehouse_id' => $data['warehouse_id'],
        //   'mode'         => 'overwrite',
        //   'note'         => 'Tracking POD Rejected' . (empty($note) ? '.' : ': ' . $note),
        //   'created_by'   => $data['created_by'],
        //   'end_date'     => $dateTime
        // ];

        // $adjustmentItems[] = [
        //   'product_id'     => $data['pod_id'],
        //   'quantity'       => $data['end_click']
        // ];

        // // Add adjustment.
        // if ($adjustmentId = $this->addAdjustmentStock($adjustmentData, $adjustmentItems)) {
        //   $this->db->update('trackingpod', ['adjustment_id' => $adjustmentId], ['id' => $trackId]);
        // }

        // Test with new adjustment.
        $adjustmentData = [
          'date'         => ($data['created_at'] ?? date('Y-m-d H:i:s')),
          'warehouse_id' => $data['warehouse_id'],
          'mode'         => 'formula',
          'note'         => 'Tracking POD Rejected' . (empty($note) ? '.' : ': ' . $note),
          'created_by'   => $data['created_by'],
          'end_date'     => $dateTime
        ];

        $adjustmentItems[] = [
          'product_id'     => $data['pod_id'],
          'quantity'       => $data['mc_reject'] * -1
        ];

        // Add adjustment.
        if ($adjustmentId = $this->addAdjustmentStock($adjustmentData, $adjustmentItems)) {
          $this->db->update('trackingpod', ['adjustment_id' => $adjustmentId], ['id' => $trackId]);
        }
      }

      addEvent("Created Tracking POD [{$trackId}: start_click: {$data['start_click']}; end_click: {$data['end_click']}]", 'info');
      return $trackId;
    }
    setLastError($errDB);
    return FALSE;
  }

  public function addUnit($data)
  {
    if ($this->db->insert('units', $data)) {
      return TRUE;
    }
    return FALSE;
  }

  public function addUserCustomer($user_id)
  {
    $user = $this->getUserByID($user_id);
    if ($user) {
      $customer_data = [
        'group_id' => 3,
        'group_name' => 'customer',
        'customer_group_id' => 1,
        'customer_group_name' => 'Reguler',
        'name' => $user->fullname,
        'company' => 'INDOPRINTING', /* INDOPRINTING reserved for internal customer (employee). */
        'phone' => $user->phone,
        'json_data' => json_encode([
          'user_id' => $user_id
        ])
      ];

      if ($this->addCustomer($customer_data)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Send WA by job service.
   * @param array $data [ sale_id, api_key, *phone, *message, send_date ]
   */
  public function addWAJob($data)
  {
    if (empty($data['phone'])) {
      setLastError('Phone cannot be empty');
      return NULL;
    }

    if (empty($data['message'])) {
      setLastError('Message cannot be empty.');
      return NULL;
    }

    $data['send_date'] = date('Y-m-d H:i:s');
    $data['status'] = 'pending'; // Default as pending.

    $data = setCreatedBy($data);

    if ($this->db->insert('wa_job', $data)) {
      return $this->db->insert_id();
    }

    setLastError($this->db->error()['message']);
    return NULL;
  }

  /**
   * THE ONLY FUNCTION TO ADD WAREHOUSE.
   * @param array $data [ *code, *name, address, geolocation, phone, email,
   *  price_group_id, max_counter, queue_attempt_json, json_data ]
   */
  public function addWarehouse($data)
  {
    $wh_data = [
      'code' => $data['code'],
      'name' => $data['name'],
    ];

    if (!empty($data['address']))            $wh_data['address']            = $data['address'];
    if (!empty($data['geolocation']))        $wh_data['geolocation']        = $data['geolocation'];
    if (!empty($data['phone']))              $wh_data['phone']              = $data['phone'];
    if (!empty($data['email']))              $wh_data['email']              = $data['email'];
    if (!empty($data['price_group_id']))     $wh_data['price_group_id']     = $data['price_group_id'];
    if (!empty($data['max_counter']))        $wh_data['max_counter']        = $data['max_counter'];
    if (!empty($data['queue_attempt_json'])) $wh_data['queue_attempt_json'] = $data['queue_attempt_json'];
    if (!empty($data['active']))             $wh_data['active']             = $data['active'];
    if (!empty($data['json_data']))          $wh_data['json_data']          = $data['json_data'];

    $this->db->trans_start();
    $this->db->insert('warehouses', $wh_data); // ORIGINAL INSERT.
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO ADD WAREHOUSE PRODUCT.
   * @param array $data [ *product_id, *warehouse_id, quantity, rack, safety_stock, user_id, so_cycle ]
   */
  public function addWarehouseProduct($data)
  {
    $product   = $this->getProductByID($data['product_id']);
    $warehouse = $this->getWarehouseByID($data['warehouse_id']);

    if ($product && $warehouse) {
      $whp_data = [
        'product_id'     => $product->id,
        'product_code'   => $product->code,
        'warehouse_id'   => $warehouse->id,
        'warehouse_code' => $warehouse->code
      ];

      if (!empty($data['quantity']))     $whp_data['quantity']     = $data['quantity'];
      if (!empty($data['rack']))         $whp_data['rack']         = $data['rack'];
      if (!empty($data['safety_stock'])) $whp_data['safety_stock'] = $data['safety_stock'];
      if (!empty($data['user_id']))      $whp_data['user_id']      = $data['user_id'];
      if (!empty($data['so_cycle']))     $whp_data['so_cycle']     = $data['so_cycle'];

      $this->db->trans_start();
      $this->db->insert('warehouses_products', $whp_data); // ORIGINAL INSERT.
      $insert_id = $this->db->insert_id();
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        return $insert_id;
      }
    }
    return FALSE;
  }

  public function bankActivate($id)
  {
    $this->db->trans_start();
    $this->db->update('banks', ['active' => 1], ['id' => $id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function bankDeactivate($id)
  {
    $this->db->trans_start();
    $this->db->update('banks', ['active' => 0], ['id' => $id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * (NEW) THE ONLY FUNCTION TO COMPLETE SALE ITEMS.
   * @param int $saleItemId Sale Item ID
   * @param array $data [ *quantity, created_by ]
   */
  public function completeSaleItem($saleItemId, $data)
  {
    $saleItem = $this->getSaleItemByID($saleItemId);

    if ($saleItem) {
      $completedQty = $data['quantity']; // Quantity to complete.
      $createdBy    = ($data['created_by'] ?? XSession::get('user_id'));
      $sale         = $this->getSaleByID($saleItem->sale_id);
      $saleItemData = [];
      $saleItemJS   = getJSON($saleItem->json_data);
      $status       = ($saleItemJS ? $saleItemJS->status : 'waiting_production'); // Default status.
      $date         = ($data['date'] ?? $data['created_at'] ?? date('Y-m-d H:i:s')); // Current complete date.

      if (empty($data['quantity'])) sendJSON(['error' => 1, 'msg' => 'Cannot complete zero (0) quantity.']);

      // Get operator data.
      $op = $this->getUserByID($createdBy);

      // Set Completed date and Operator who completed it.
      $saleItemData['completed_at'] = $date; // Completed date.
      $saleItemData['operator_id']  = $op->id; // Change PIC who completed it.

      if (empty($saleItemJS->due_date)) { // Check if sale item has due date. If empty then restricted.
        setLastError("Item {$saleItem->product_code} doesn't have due date.");
      }

      if (($completedQty + $saleItem->finished_qty) < $saleItem->quantity) { // If completed partial.
        $status = 'completed_partial';
      } else if (($completedQty + $saleItem->finished_qty) == $saleItem->quantity) { // If fully completed.
        $status = 'completed';
      } else {
        setLastError("<b>completeSaleItem()</b>: Something wrong! Maybe you complete more quantity than requested. " .
          "Completed: {$completedQty}, Finished: {$saleItem->finished_qty}, Quantity: {$saleItem->quantity}");
      }

      $saleItemData['status'] = $status; // Restore status as completed or completed_partial.

      if (isset($data['spec'])) $saleItemData['spec'] = $data['spec'];

      $saleItemData['finished_qty'] = ($saleItem->finished_qty + $completedQty);
      // $saleItemData['json_data'] = json_encode($saleItemJS);

      $klikpod = $this->getProductByCode('KLIKPOD');

      if ($this->updateSaleItem($saleItemId, $saleItemData)) {
        // Increase and Decrease item.

        if ($saleItem->product_type == 'combo') { // SALEITEM. (Decrement|Increment). POFF28
          $comboItems = $this->getProductComboItems($saleItem->product_id, $sale->warehouse_id);

          if ($comboItems) {
            foreach ($comboItems as $comboItem) {
              $finalCompletedQty = filterDecimal($comboItem->qty) * filterDecimal($completedQty);

              if ($comboItem->type == 'standard') { // COMBOITEM. Decrement. POSTMN, POCT15, FFC280
                if ($comboItem->product_id == $klikpod->id) {
                  addEvent("CRITICAL: KLIKPOD KNOWN AS COMBO STANDARD TYPE MUST NOT BE DECREASED!", 'critical');
                }

                $this->decreaseStockQuantity([
                  'date'         => $date,
                  'sale_id'      => $sale->id,
                  'saleitem_id'  => $saleItem->id,
                  'product_id'   => $comboItem->product_id,
                  'price'        => $saleItem->price,
                  'quantity'     => $finalCompletedQty,
                  'warehouse_id' => $sale->warehouse_id, // Must sale->warehouse_id, NOT saleItem->warehouse_id
                  'created_by'   => $op->id
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

                $this->increaseStockQuantity([
                  'date'         => $date,
                  'sale_id'      => $sale->id,
                  'saleitem_id'  => $saleItem->id,
                  'product_id'   => $comboItem->product_id,
                  'price'        => $saleItem->price,
                  'quantity'     => $finalCompletedQty,
                  'warehouse_id' => $sale->warehouse_id,
                  'created_by'   => $op->id
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

          $this->increaseStockQuantity([
            'date'         => $date,
            'sale_id'      => $sale->id,
            'saleitem_id'  => $saleItem->id,
            'product_id'   => $saleItem->product_id,
            'price'        => $saleItem->price,
            'quantity'     => $completedQty,
            'warehouse_id' => $sale->warehouse_id,
            'created_by'   => $op->id
          ]);

          addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$completedQty}");
        } else if ($saleItem->product_type == 'standard') { // SALEITEM. Decrement. FFC280, POCT15
          if ($saleItem->product_code == 'KLIKPOD') {
            addEvent("CRITICAL: KLIKPOD KNOWN AS STANDARD TYPE MUST NOT BE DECREASED!", 'critical');
          }

          $this->decreaseStockQuantity([
            'date'         => $date,
            'sale_id'      => $sale->id,
            'saleitem_id'  => $saleItem->id,
            'product_id'   => $saleItem->product_id,
            'price'        => $saleItem->price,
            'quantity'     => $completedQty,
            'warehouse_id' => $sale->warehouse_id,
            'created_by'   => $op->id
          ]);

          addEvent("Completed Sale [{$sale->id}: {$sale->reference}]; {$saleItem->product_code}: {$completedQty}");
        }

        // Sync sale after operator complete the item.
        $this->syncSales(['sale_id' => $sale->id]);

        return TRUE;
      }
    }
    return FALSE;
  }

  public function check_customer_deposit($customer_id, $amount)
  {
    $customer = $this->getCustomerByID($customer_id);
    return $customer->deposit_amount >= $amount;
  }

  public function checkPermissions()
  {
    $q = $this->db->get_where('permissions', ['group_id' => XSession::get('group_id')], 1);
    if ($q->num_rows() > 0) {
      return $q->result_array();
    }
    return false;
  }

  public function checkSlug($slug, $type = null)
  {
    return false;
  }

  /**
   * Check Stock Opname status. Run by CRONJOBS.
   */
  public function checkStockOpnameStatus()
  {
    $result = [];
    // Find 'checked' stock opnames.
    $stock_opnames = $this->getStockOpnames(['status' => 'checked']);

    if ($stock_opnames) {
      foreach ($stock_opnames as $opname) {
        // if so date > yesterday
        if (strtotime($opname->date) > strtotime('-1 day')) continue;

        $so_items = $this->getStockOpnameItems($opname->id);

        if ($so_items) {
          foreach ($so_items as $so_item) {
            $so_item_data[] = [
              'product_id' => $so_item->product_id,
              'price'      => $so_item->price,
              'quantity'   => $so_item->quantity,
              'first_qty'  => $so_item->first_qty,
              'reject_qty' => $so_item->reject_qty,
              'last_qty'   => $so_item->first_qty, // Since no last_qty, then use first_qty. DO NOT CHANGE!
            ];
          }
        }

        $so_data = [
          'reference'    => $opname->reference,
          'note'         => $opname->note,
          'status'       => 'confirmed',
          'warehouse_id' => $opname->warehouse_id,
          'updated_by'   => $opname->updated_by,
          'updated_at'   => date('Y-m-d H:i:s'),
          'created_by'   => $opname->created_by
        ];

        if ($this->updateStockOpname($opname->id, $so_data, $so_item_data)) {
          $result[] = [
            'reference' => $opname->reference,
            'old_status' => 'checked',
            'new_status' => 'verified'
          ];
        }
      }
    }

    return $result;
  }

  /**
   * @deprecated 2021-05-11
   */
  public function checkUserPermissions($permission_name, $user_id = 0)
  {
    if ($user_id) {
      $user = $this->getUserByID($user_id);
    } else {
      $user = $this->getUserByID(XSession::get('user_id')); // Default as current user.
    }

    if ($user->group_id == 1 || $user->group_id == 2) return TRUE; // Owner || Admin always TRUE

    $permissions = $this->getGroupPermissions($user->group_id, TRUE);

    if (isset($permissions[$permission_name]) && $permissions[$permission_name] == 1) return TRUE;

    return FALSE;
  }

  /**
   * Clear for payments that not bind to any id.
   */
  public function clearEmptyPayments()
  {
    $payments = $this->getPayments();
    $result = [];

    if ($payments) {
      foreach ($payments as $payment) {
        $deletePayment = FALSE;
        $type = NULL;

        if ($payment->expense_id) {
          $expense = $this->getExpenseByID($payment->expense_id);

          if (empty($expense)) {
            $deletePayment = TRUE;
            $type = 'Expense';
          }
        }

        if ($payment->income_id) {
          $income = $this->getIncomeByID($payment->income_id);

          if (empty($income)) {
            $deletePayment = TRUE;
            $type = 'Income';
          }
        }

        if ($payment->mutation_id) {
          $mutation = $this->getBankMutationByID($payment->mutation_id);

          if (empty($mutation)) {
            $deletePayment = TRUE;
            $type = 'Mutation';
          }
        }

        if ($payment->purchase_id) {
          $purchase = $this->getStockPurchaseByID($payment->purchase_id);

          if (empty($purchase)) {
            $deletePayment = TRUE;
            $type = 'Purchase';
          }
        }

        if ($payment->sale_id) {
          $sale = $this->getSaleByID($payment->sale_id);

          if (empty($sale)) {
            $deletePayment = TRUE;
            $type = 'Sale';
          }
        }

        if ($payment->transfer_id) {
          $transfer = ProductTransfer::getRow(['id' => $payment->transfer_id]);

          if (empty($transfer)) {
            $deletePayment = TRUE;
            $type = 'Transfer';
          }
        }

        if ($deletePayment) {
          if ($this->deletePaymentByID($payment->id)) {
            $result[] = [
              'id' => $payment->id,
              'type' => $type
            ];
          }
        }
      }

      return $result;
    }
    return [];
  }

  /**
   * Clear all old WA Jobs message.
   */
  public function clearOldWAJobs($days = 7)
  {
    $success = 0;
    $endDate = date('Y-m-d', strtotime("-{$days} day"));
    $jobs = $this->getWAJobs(['end_date' => $endDate]);

    foreach ($jobs as $job) {
      if ($job->status == 'failed' || $job->status == 'failed') {
        if ($this->deleteWAJob($job->id)) $success++;
      }
    }

    return $success;
  }

  /**
   * Clear session storage.
   */
  public function clearSessionStorage()
  {
    $count = 0;
    $path = FCPATH . 'sess/';

    $files = scandir($path);
    if ($files) {
      $count = 0;
      foreach ($files as $file) {
        if (substr($file, 0, 5) == 'sess_') {
          unlink($path . $file);
          $count++;
        }
      }
    }
    return $count;
  }

  public function convertToBase($unit, $value)
  {
    switch ($unit->operator) {
      case '*':
        return $value / $unit->operation_value;
        break;
      case '/':
        return $value * $unit->operation_value;
        break;
      case '+':
        return $value - $unit->operation_value;
        break;
      case '-':
        return $value + $unit->operation_value;
        break;
      default:
        return $value;
    }
  }

  /**
   * Decrease sale stocks.
   * @param int $sale_id Sale ID
   * @param array $items Sale items to decrease the quantity.
   *
   * $items = [
   *  ['sale_item_id' => 1, 'quantity' => 10],
   *  ['sale_item_id' => 2, 'quantity' => 30]
   * ]
   */
  public function decreaseSaleStocks($sale_id, $items)
  {
    $sale = $this->getSaleByID($sale_id);
    if ($sale) {
      $sale_items = $this->getSaleItemsBySaleID($sale->id);
      if ($sale_items) {
        foreach ($sale_items as $sale_item) {
          if ($sale_item->product_type == 'combo') {
            $combo_items = $this->getProductComboItems($sale_item->product_id, $sale->warehouse_id);
            if ($combo_items) {
              foreach ($combo_items as $combo_item) {
                if ($combo_item->type == 'standard') { // Decrement. FFC280, POSTMN, POCT15

                }
                if ($combo_item->type == 'service') { // Increment. KLIKPOD

                }
              }
            }
          }
          if ($sale_item->product_type === 'service') { // Increment. JASA EDIT DESIGN

          }
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DECREASE STOCK QUANTITY. See increaseStockQuantity().
   * @param array [ date, *(adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id), saleitem_id,
   *  *product_id, cost, price, *warehouse_id, *quantity, adjustment_qty, spec, created_by ]
   */
  public function decreaseStockQuantity($data)
  {
    $stockData = $data;
    $stockData['status'] = 'sent';

    if ($this->addStockQuantity($stockData)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Decrease current bank amount. See increaseBankAmount.
   * @param int $bank_id Bank ID.
   * @param float $amount Amount to decrease.
   */
  public function decreaseBankAmount($bank_id, $amount)
  {
    $ci = $this;

    $result = $this->ridintek->mutex('bank')->on('lock', function () use ($ci, $bank_id, $amount) {
      $real_amount = filterDecimal($amount);
      $bank = $ci->getBankByID($bank_id);

      if ($bank) {
        if ($ci->updateBank($bank_id, ['amount' => $bank->amount - $real_amount])) {
          return TRUE;
        }
      }
      return FALSE;
    })->create()->close();

    return $result;
  }

  /**
   * Decrease current warehouse stock quantity. See increaseWarehouseQty.
   * @param int $product_id Product ID.
   * @param int $warehouse_id Warehouse ID.
   * @param float $qty Quantity to decrease.
   */
  public function decreaseWarehouseQty($product_id, $warehouse_id, $qty)
  {
    $quantity = filterDecimal($qty);
    $whp = $this->getWarehouseProduct($product_id, $warehouse_id);

    if ($whp) {
      if ($this->updateWarehouseProduct(['id' => $whp->id], ['quantity' => $whp->quantity - $quantity])) {
        return TRUE;
      }
    } else {
      if ($this->addWarehouseProduct([
        'product_id' => $product_id,
        'warehouse_id' => $warehouse_id,
        'quantity' => (0 - $quantity)
      ])) {
        return TRUE;
      }
    }
    return FALSE;
  }


  public function deleteApiKey($api_id)
  {
    if ($this->db->delete('api_keys', ['id' => $api_id])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE BANK MUTATION
   */
  public function deleteBankMutation($mutation_id)
  {
    $this->db->trans_start();
    $this->db->delete('bank_mutations', ['id' => $mutation_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      $this->deletePayments(['mutation_id' => $mutation_id]);

      $payment_validation = $this->getPaymentValidationByMutationID($mutation_id);

      if ($payment_validation) {
        $this->deletePaymentValidation($payment_validation->id);
      }
      return TRUE;
    }
    return FALSE;
  }

  public function deleteAttachment($filename)
  {
    $paths = getAttachmentPaths();

    foreach ($paths as $path) {
      $fullfilename = $path . $filename;
      if (file_exists($fullfilename) && is_file($fullfilename)) {
        @unlink($fullfilename);
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteBiller($biller_id)
  {
    if ($this->db->delete('billers', ['id' => $biller_id, 'group_name' => 'biller'])) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteCalendar($calendarId)
  {
    if ($this->db->delete('calendar', ['id' => $calendarId])) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteCustomer($customer_id)
  {
    if ($this->getCustomerSales($customer_id)) {
      return FALSE;
    }
    if ($this->db->delete('customers', ['id' => $customer_id, 'group_name' => 'customer'])) {
      $users = $this->getCustomerUsers($customer_id);
      if ($users) {
        $this->db->delete('users', ['customer_id' => $customer_id]);
      }
      return TRUE;
    }
    return FALSE;
  }

  public function deleteCustomerAddress($address_id)
  {
    if ($this->db->delete('addresses', ['id' => $address_id])) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteCustomerDeposit($id)
  {
    $deposit = $this->getCustomerDepositByID($id);
    $customer = $this->getCustomerByID($deposit->customer_id);
    $cdata   = [
      'deposit_amount' => ($customer->deposit_amount - $deposit->amount),
    ];
    if ($this->db->update('customers', $cdata, ['id' => $deposit->customer_id]) && $this->db->delete('deposits', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteExpense($expenseId)
  {
    if ($expenseId) {
      if ($this->db->delete('expenses', ['id' => $expenseId])) {
        $this->deleteExpensePayment($expenseId);
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteExpensePayment($expense_id)
  {
    if ($expense_id) {
      if ($this->deletePayments(['expense_id' => $expense_id])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteHoliday($clause)
  {
    $this->db->delete('holiday', $clause);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteGeolocations($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))           $clauses['id']           = $clause['id'];
    if (!empty($clause['user_id']))      $clauses['user_id']      = $clause['user_id'];
    if (!empty($clause['customer_id']))  $clauses['customer_id']  = $clause['customer_id'];
    if (!empty($clause['biller_id']))    $clauses['biller_id']    = $clause['biller_id'];
    if (!empty($clause['warehouse_id'])) $clauses['warehouse_id'] = $clause['warehouse_id'];
    if (!empty($clause['type'])) { // log, presence
      $this->db->like('type', $clause['type'], 'none');
    }

    if ($this->db->delete('geolocation', $clauses)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete Income.
   * @param int $income_id Income ID
   */
  public function deleteIncome($income_id)
  {
    if ($this->db->delete('incomes', ['id' => $income_id])) {
      if ($this->deletePayments(['income_id' => $income_id])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Delete job.
   * @param array $clause [ controller, method, param, result, status ]
   */
  public function deleteJobs($clause = [])
  {
    $this->db->delete('jobs', $clause);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * DANGER !!!
   * Delete old need payment sales. Default 90 days ago. (3 months)
   *
   * @param int $daysAgo Days ago to delete sales.
   */
  // public function deleteOldNeedPaymentSales($daysAgo = 90)
  // {
  //   $sales = [];

  //   $date = date('Y-m-d', strtotime("-{$daysAgo} days"));

  //   $this->db
  //     ->like('status', 'need_payment', 'none')
  //     ->where("date <= '{$date} 00:00:00'");

  //   $q = $this->db->get('sales');

  //   if ($q->num_rows() > 0) {
  //     foreach ($q->result() as $row) {
  //       $sales[] = (object)[
  //         'id' => $row->id,
  //         'reference' => $row->reference
  //       ];
  //     }
  //   }

  //   if ($sales) {
  //     foreach ($sales as $sale) {
  //       $this->deleteSale($sale->id);
  //     }
  //   }

  //   return $sales;
  // }

  /**
   * THE ONLY FUNCTION TO DELETE PAYMENT. ALIAS OF deletePaymentByID.
   * @param int $payment_id Payment ID.
   */
  public function deletePayment($payment_id)
  {
    return $this->deletePaymentByID($payment_id);
  }

  /**
   * THE ONLY FUNCTION TO DELETE PAYMENT.
   * @param int $payment_id Payment ID.
   */
  public function deletePaymentByID($payment_id)
  {
    $this->db->delete('payments', ['id' => $payment_id]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
  }

  /**
   * THE ONLY FUNCTION TO DELETE PAYMENTS.
   * @param array $clause [ *(id, expense_id, income_id, mutation_id, purchase_id, sale_id,
   *  transfer_id), bank_id, method ]
   */
  public function deletePayments($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))          $clauses['id']          = $clause['id'];
    if (!empty($clause['expense_id']))  $clauses['expense_id']  = $clause['expense_id'];
    if (!empty($clause['income_id']))   $clauses['income_id']   = $clause['income_id'];
    if (!empty($clause['mutation_id'])) $clauses['mutation_id'] = $clause['mutation_id'];
    if (!empty($clause['purchase_id'])) $clauses['purchase_id'] = $clause['purchase_id'];
    if (!empty($clause['sale_id']))     $clauses['sale_id']     = $clause['sale_id'];
    if (!empty($clause['transfer_id'])) $clauses['transfer_id'] = $clause['transfer_id'];

    if (!empty($clause['bank_id'])) $clauses['bank_id'] = $clause['bank_id'];
    if (!empty($clause['method']))  $clauses['method']  = $clause['method'];

    $payments = $this->getPayments($clauses);

    $this->db->delete('payments', $clauses); // ORIGINAL DELETE.

    if ($this->db->affected_rows()) {
      if ($payments) {
        foreach ($payments as $payment) {
          if ($payment->type == 'received') {
            $this->decreaseBankAmount($payment->bank_id, $payment->amount);
          } else if ($payment->type == 'sent') {
            $this->increaseBankAmount($payment->bank_id, $payment->amount);
          }
        }
        return TRUE;
      }
    }

    return FALSE;
  }

  public function deletePaymentValidation($pv_id)
  {
    if ($pv_id) {
      $opv = $this->getPaymentValidationByID($pv_id);
      if ($this->db->delete('payment_validations', ['id' => $pv_id])) {
        if ($opv->sale_id) {
          $this->updateSale($opv->sale_id, ['payment_status' => 'pending']);
          $this->syncSales(['sale_id' => $opv->sale_id]);
        } elseif ($opv->mutation_id) {
          $this->updateBankMutation($opv->mutation_id, ['status' => 'cancelled']);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteProduct($id)
  {
    if ($this->db->delete('products', ['id' => $id]) && $this->deleteWarehouseProduct(['product_id' => $id])) {
      $this->db->delete('product_photos', ['product_id' => $id]);
      $this->db->delete('product_prices', ['product_id' => $id]);
      return true;
    }
    return false;
  }

  public function deleteProductCategory($categoryId)
  {
    $this->db->delete('categories', ['id' => $categoryId]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  public function deleteProductMutations($clause = [])
  {
    $pms = $this->getProductMutations($clause);
    $deleted = 0;

    foreach ($pms as $pm) {
      $pmitems = $this->getProductMutationItems(['pm_id' => $pm->id]);

      $this->db->delete('product_mutation', ['id' => $pm->id]);

      if ($this->db->affected_rows()) {
        $this->db->delete('product_mutation_item', ['pm_id' => $pm->id]);
        $this->db->delete('stocks', ['pm_id' => $pm->id]);

        foreach ($pmitems as $pmitem) {
          $this->syncProductQty($pmitem->product_id, $pm->from_warehouse_id);
          $this->syncProductQty($pmitem->product_id, $pm->to_warehouse_id);
        }

        $attachment = getAttachmentPaths('products_mutation') . $pm->attachment;

        if (is_file($attachment)) unlink($attachment);

        $deleted++;
      }
    }

    return $deleted;
  }

  public function deleteProductReport($reportId)
  {
    if ($this->db->delete('product_report', ['id' => $reportId])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE PAYROLL.
   */
  public function deletePayroll($payroll_id)
  {
    if ($payroll_id) {
      if ($this->db->delete('payrolls', ['id' => $payroll_id])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE PAYROLL CATEGORY.
   */
  public function deletePayrollCategory($category_id)
  {
    if ($category_id) {
      if ($this->db->delete('payroll_categories', ['id' => $category_id])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE SALE.
   */
  public function deleteSale($sale_id)
  {
    $sale = $this->getSaleByID($sale_id);
    if ($sale) {
      $sale_items = $this->getSaleItemsBySaleID($sale_id);
      $this->db->delete('sales', ['id' => $sale_id]);
      if ($sale_items) {
        if ($this->deleteSaleItems(['sale_id' => $sale_id])) {
          $this->deleteStockQuantity(['sale_id' => $sale_id]); // Delete stocks. Stocks are not always present.
          $opayments = $this->getSalePayments($sale_id);

          if ($opayments) {
            $this->deletePayments(['sale_id' => $sale_id]);
          }

          $payment_validation = $this->getPaymentValidationBySaleID($sale_id);

          if ($payment_validation) {
            $this->deletePaymentValidation($payment_validation->id);
          }

          if ($sale->attachment) {
            $this->deleteAttachment($sale->attachment);
          }
        }
      }

      addEvent("Deleted Sale [{$sale->id}: {$sale->reference}]", 'warning');

      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE SALE ITEMS.
   * @param array $clause [ *(id, sale_id) ]
   */
  public function deleteSaleItems($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))      $clauses['id']      = $clause['id'];
    if (!empty($clause['sale_id'])) $clauses['sale_id'] = $clause['sale_id'];

    $oldSaleItems = $this->getSaleItems($clauses);

    if ($oldSaleItems && $this->db->delete('sale_items', $clauses)) { // ORIGINAL DELETE.
      foreach ($oldSaleItems as $sale_item) {
        $this->deleteStockQuantity(['saleitem_id' => $sale_item->id]);
      }
      return TRUE;
    }
    return FALSE;
  }

  public function deleteSaleTB($saleTBId)
  {
    if ($this->db->delete('sales_tb', ['id' => $saleTBId])) {
      addEvent("Deleted Sale TB [{$saleTBId}]", 'warning');
      return TRUE;
    }
    return FALSE;
  }

  public function deleteSchedule($clause)
  {
    $this->db->delete('schedule', $clause);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE ADJUSTMENT.
   */
  public function deleteStockAdjustment($adjustment_id)
  {
    $adjustment = $this->getStockAdjustmentByID($adjustment_id);
    if ($adjustment && $this->deleteStockQuantity(['adjustment_id' => $adjustment_id])) {
      if ($this->db->delete('adjustments', ['id' => $adjustment_id])) {
        if ($adjustment->attachment) {
          $this->deleteAttachment($adjustment->attachment);
        }

        addEvent("Deleted Adjustment [{$adjustment->id}: {$adjustment->reference}]", 'warning');
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE INTERNAL USE.
   */
  public function deleteStockInternalUse($iuseId)
  {
    $iuse = $this->getStockInternalUseByID($iuseId);
    if ($iuse && $this->deleteStockQuantity(['internal_use_id' => $iuseId])) {
      if ($this->db->delete('internal_uses', ['id' => $iuseId])) {
        if ($iuse->attachment_id) {
          Attachment::delete(['id' => $iuseId]);
        }

        addEvent("Deleted Internal Use [{$iuse->id}: {$iuse->reference}]", 'warning');
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE STOCK OPNAME.
   */
  public function deleteStockOpname($opname_id)
  {
    $opname = $this->getStockOpnameByID($opname_id);

    if ($opname) {
      $adjustment_plus = $this->getStockAdjustmentByID($opname->adjustment_plus_id);
      if ($adjustment_plus) {
        $this->deleteStockAdjustment($adjustment_plus->id);
      }

      $adjustment_min = $this->getStockAdjustmentByID($opname->adjustment_min_id);
      if ($adjustment_min) {
        $this->deleteStockAdjustment($adjustment_min->id);
      }

      $opname_items = $this->getStockOpnameItems($opname->id);
      if ($opname_items) {
        $this->db->delete('stock_opname_items', ['opname_id' => $opname->id]);
      }

      if ($opname->attachment) {
        $this->deleteAttachment($opname->attachment);
      }

      // Since SO deleted. It's not required to revert back to last cycle.
      // $this->updateUser($opname->created_by, ['so_cycle' => ($opname->cycle - 1)]);

      if ($this->db->delete('stock_opnames', ['id' => $opname->id])) {
        addEvent("Deleted Stock Opname [{$opname->id}: $opname->reference]", 'warning');

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE PURCHASE.
   */
  public function deleteStockPurchase($purchase_id)
  {
    $purchase = $this->getStockPurchaseByID($purchase_id);

    if ($purchase && $this->deleteStockQuantity(['purchase_id' => $purchase_id])) {
      if ($this->db->delete('purchases', ['id' => $purchase_id])) {
        $opayments = $this->getStockPurchasePayments($purchase_id);
        if ($opayments) {
          $this->deletePayments(['purchase_id' => $purchase_id]);
        }
        if ($purchase->attachment) {
          $this->deleteAttachment($purchase->attachment);
        }

        addEvent("Deleted Purchase [{$purchase->id}: $purchase->reference]", 'warning');
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * FOR DELETE STOCK ONLY
   * @param array $clause [ *(id, adjustment_id, purchase_id, sale_id, transfer_id, saleitem_id),
   *  product_id, warehouse_id ]
   */
  public function deleteStockQuantity($clause)
  {
    $ci = $this;
    $ret = $this->ridintek->mutex('stock')->on('lock', function ($mutex) use ($ci, $clause) {
      $clauses = [];

      if (!empty($clause['id']))              $clauses['id']              = $clause['id'];

      if (!empty($clause['adjustment_id']))   $clauses['adjustment_id']   = $clause['adjustment_id'];
      if (!empty($clause['internal_use_id'])) $clauses['internal_use_id'] = $clause['internal_use_id'];
      if (!empty($clause['purchase_id']))     $clauses['purchase_id']     = $clause['purchase_id'];
      if (!empty($clause['sale_id']))         $clauses['sale_id']         = $clause['sale_id'];
      if (!empty($clause['transfer_id']))     $clauses['transfer_id']     = $clause['transfer_id'];
      if (!empty($clause['saleitem_id']))     $clauses['saleitem_id']     = $clause['saleitem_id'];

      if (!empty($clause['product_id']))      $clauses['product_id']      = $clause['product_id'];
      if (!empty($clause['warehouse_id']))    $clauses['warehouse_id']    = $clause['warehouse_id'];

      $stocks = $this->getStocks($clauses); // Get current stocks before deletion.

      $ci->db->trans_start();
      $ci->db->delete('stocks', $clauses); // ORIGINAL DELETE.
      $ci->db->trans_complete();
      if ($ci->db->trans_status() !== FALSE) {
        if ($stocks) {
          foreach ($stocks as $stock) {
            if (strtolower($stock->status) == 'received') {
              $this->decreaseWarehouseQty($stock->product_id, $stock->warehouse_id, $stock->quantity);
            } else if (strtolower($stock->status) == 'sent') {
              $this->increaseWarehouseQty($stock->product_id, $stock->warehouse_id, $stock->quantity);
            }
          }
        }

        return TRUE;
      }
      return FALSE;
    })->create()->close();
    return $ret;
  }

  public function deleteSupplier($supplier_id)
  {
    $supplier = $this->getSupplierByID($supplier_id);

    if ($supplier) {
      if ($this->db->delete('suppliers', ['id' => $supplier->id])) {

        addEvent("Deleted Supplier [{$supplier->id}: $supplier->name ($supplier->company)]", 'warning');
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteTrackingPOD($trackId)
  {
    $track = $this->getTrackingPODByID($trackId);

    if ($track) {
      if ($this->db->delete('trackingpod', ['id' => $trackId])) {
        if (!empty($track->attachment)) {
          $this->deleteAttachment($track->attachment);
        }
        if (!empty($track->adjustment_id)) {
          $this->deleteStockAdjustment($track->adjustment_id);
        }

        addEvent("Deleted Tracking POD [{$track->id}]", 'warning');
        return TRUE;
      }
    }
    return FALSE;
  }

  public function deleteWAJob($jobId)
  {
    if ($this->db->delete('wa_job', ['id' => $jobId])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE WAREHOUSE.
   * @param array $clause [ id, code, name, email ]
   */
  public function deleteWarehouse($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))    $clauses['id']    = $clause['id'];
    if (!empty($clause['code']))  $clauses['code']  = $clause['code'];
    if (!empty($clause['name']))  $clauses['name']  = $clause['name'];
    if (!empty($clause['email'])) $clauses['email'] = $clause['email'];

    if (isset($clauses['code'])) {
      $this->db->like('code', $clauses['code']);
      unset($clauses['code']);
    }

    if (isset($clauses['name'])) {
      $this->db->like('name', $clauses['name']);
      unset($clauses['name']);
    }

    if (isset($clauses['email'])) {
      $this->db->like('email', $clauses['email']);
      unset($clauses['email']);
    }

    $this->db->trans_start();
    $this->db->delete('warehouses', $clauses); // ORIGINAL DELETE.
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO DELETE WAREHOUSE PRODUCT.
   * @param array $clause [ id, product_id, product_code, warehouse_id, warehouse_code, user_id ]
   */
  public function deleteWarehouseProduct($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))             $clauses['id']             = $clause['id'];
    if (!empty($clause['product_id']))     $clauses['product_id']     = $clause['product_id'];
    if (!empty($clause['product_code']))   $clauses['product_code']   = $clause['product_code'];
    if (!empty($clause['warehouse_id']))   $clauses['warehouse_id']   = $clause['warehouse_id'];
    if (!empty($clause['warehouse_code'])) $clauses['warehouse_code'] = $clause['warehouse_code'];
    if (!empty($clause['user_id']))        $clauses['user_id']        = $clause['user_id'];

    if (isset($clauses['product_code'])) {
      $this->db->like('product_code', $clauses['product_code']);
      unset($clauses['product_code']);
    }

    if (isset($clauses['warehouse_code'])) {
      $this->db->like('warehouse_code', $clauses['warehouse_code']);
      unset($clauses['warehouse_code']);
    }

    $this->db->trans_start();
    $this->db->delete('warehouses_products', $clauses); // ORIGINAL DELETE.
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  public function generateApiKeys($length = 32)
  {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
    $data = '';
    if (!$length) $length = 32;
    for ($a = 0; $a < $length; $a++) {
      $data .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $data;
  }

  public function generateUniqueCode()
  { // To generate transfer unique codes.
    return mt_rand(1, 200); // 1 to 200
  }

  public function get_expiring_qty_alerts()
  {
    return NULL;
  }

  public function get_setting()
  {
    $q = $this->db->get('settings');
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    } else {
      setLastError($this->db->error()['message']);
    }
    return NULL;
  }

  public function get_shop_payment_alerts()
  {
    $this->db->where('shop', 1)->where('attachment !=', null)->where('payment_status !=', 'paid');
    return $this->db->count_all_results('sales');
  }

  public function get_shop_sale_alerts()
  {
    $this->db->join('deliveries', 'deliveries.sale_id=sales.id', 'left')
      ->where('sales.shop', 1)->where('sales.status', 'completed')->where('sales.payment_status', 'paid')
      ->group_start()->where('deliveries.status !=', 'delivered')->or_where('deliveries.status IS NULL', null)->group_end();
    return $this->db->count_all_results('sales');
  }

  public function get_total_qty_alerts() // Used by Notification button
  {
    $this->db->where('safety_stock > quantity')
      ->where('safety_stock != 0')
      ->where('safety_stock IS NOT NULL')
      ->like('type', 'standard');
    return $this->db->count_all_results('products');
  }

  public function get_total_wh_stock_alerts()
  { // Used by Notification button
    $this->db->where('quantity < safety_stock')
      ->where('safety_stock != 0')
      ->where('safety_stock IS NOT NULL');
    return $this->db->count_all_results('warehouses_products');
  }

  public function getAddressByID($id)
  {
    return $this->db->get_where('addresses', ['id' => $id], 1)->row();
  }

  public function getAllBanks()
  {
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $row->balance = $row->amount; // Remove soon.
        $data[] = $row;
      }
      return $data;
    }
    return [];
  }

  public function getAllBaseUnits()
  {
    $q = $this->db->get_where('units', ['base_unit' => null]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * @deprecated Use getBillers() instead.
   */
  public function getAllBillers()
  {
    $this->db->order_by('name', 'ASC');
    $q = $this->db->get('billers');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getParentCategories()
  {
    $this->db->where('parent_code', null)->or_like('parent_code', '', 'none')->order_by('name', 'ASC');

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllCurrencies()
  {
    $q = $this->db->get('currencies');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllCustomerGroups()
  {
    $q = $this->db->get('customer_groups');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllExpenseCategories()
  {
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllExpenses()
  {
    $q = $this->db->get('expenses');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllIncomes()
  {
    $q = $this->db->get('incomes');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllMachines() // NEW
  {
    // Machine Category Code MUST BE "MACH" or "COMP"
    $this->db
      ->select("products.id AS id, products.code AS code, products.name AS name,
        warehouses.id AS warehouse_id")
      ->join('categories', 'categories.id = products.subcategory_id', 'left')
      ->join('warehouses', 'warehouses.name LIKE products.warehouses', 'left')
      ->like('categories.code', 'MACH', 'none')
      ->or_like('categories.code', 'COMP', 'none')
      // ->where_in('categories.code', ['BUILD', 'COMP', 'ELC', 'MACH'])
      ->order_by('code', 'ASC');

    $q = $this->db->get('products');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * @deprecated Will be replaced SOON!
   */
  public function _getAllMachines()
  {
    $q = $this->db->get('machines');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllPriceGroups()
  {
    $q = $this->db->get('price_groups');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllPriceRanges()
  {
    $q = $this->db->get('price_ranges');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllProductCategories()
  {
    $this->db->where('parent_code IS NULL');
    $this->db->order_by('name', 'ASC');

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllProductUnits()
  {
    $this->db->group_start();
    $this->db->where('base_unit IS NULL');
    $this->db->or_where('base_unit', 0);
    $this->db->group_end();
    $q = $this->db->get('units');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllPayrollCategories()
  {
    $q = $this->db->get('payroll_categories');

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllSales($limit = 0)
  {
    if ($limit > 0) {
      $this->db->limit($limit);
    }
    $q = $this->db->get('sales');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllStockPurchases()
  {
    $this->db->order_by('date', 'ASC');
    $q = $this->db->get('purchases');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllStocks()
  {
    $q = $this->db->get('stocks');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllSuppliers()
  {
    $q = $this->db->get('suppliers');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllUsers()
  {
    $this->db->order_by('fullname', 'ASC');
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getAllWarehouses()
  {
    $this->db->order_by('name', 'ASC'); // Sort by name ASC

    $q = $this->db->get('warehouses');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get all warehouses with product quantity.
   */
  public function getAllWarehousesWithPQ($product_id, $options = [])
  {
    $this->db->select('warehouses.*, warehouses_products.quantity AS quantity,
      warehouses_products.safety_stock AS safety_stock')
      ->join('warehouses_products', 'warehouses_products.warehouse_id=warehouses.id', 'left')
      ->where('warehouses_products.product_id', $product_id)
      ->group_by('warehouses.id')
      ->order_by('warehouses.name', 'ASC');

    $this->db->where('warehouses.active', 1);

    $q = $this->db->get('warehouses');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getApiKeyByToken($token)
  {
    $q = $this->db->get_where('api_keys', ['token' => $token]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBankBalanceByID($bank_id)
  {
    $this->db->select("(COALESCE(pay_recv.total, 0) - COALESCE(pay_sent.total, 0)) AS total_balance")
      ->join("(SELECT bank_id, SUM(amount) AS total FROM payments WHERE type LIKE 'received' GROUP BY bank_id)
      AS pay_recv", 'pay_recv.bank_id=banks.id', 'left')
      ->join("(SELECT bank_id, SUM(amount) AS total FROM payments WHERE type LIKE 'sent' GROUP BY bank_id)
      AS pay_sent", 'pay_sent.bank_id=banks.id', 'left')
      ->where('banks.id', $bank_id);
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      return $q->row('total_balance');
    }
    return 0;
  }

  public function getBank($clause = [])
  {
    if ($rows = $this->getBanks($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * THE ONLY FUNCTION TO GET BANKS.
   * @param array $clause [ active, bic, biller_id, code, holder, name, number, type(Cash/EDC/INV/Transfer) ]
   */
  public function getBanks($clause = [])
  {
    if (!empty($clause['bic'])) {
      $this->db->like('bic', $clause['bic'], 'none');
      unset($clause['bic']);
    }

    if (!empty($clause['code'])) {
      $this->db->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }

    if (!empty($clause['holder'])) {
      $this->db->like('holder', $clause['holder'], 'none');
      unset($clause['holder']);
    }

    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'both');
      unset($clause['name']);
    }

    if (!empty($clause['type'])) {
      if (is_array($clause['type'])) {
        $this->db->where_in('type', $clause['type']);
      } else {
        $this->db->like('type', $clause['type'], 'none');
      }
      unset($clause['type']);
    }

    if (isset($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $this->db->where_in('biller_id', $clause['biller_id']);
        unset($clause['biller_id']);
      }
    }

    $q = $this->db->get_where('banks', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getBanksByAccountNo($accountNo)
  {
    $q = $this->db->get_where('banks', ['number' => $accountNo]);
    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getBankByID($bank_id)
  {
    $q = $this->db->get_where('banks', ['id' => $bank_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBankMutationByID($id)
  {
    $q = $this->db->get_where('bank_mutations', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBankMutationPayments($mutation_id)
  {
    $q = $this->db->get_where('payments', ['mutation_id' => $mutation_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getBankMutations($clause = [])
  {
    if (!empty($clause['order']) && gettype($clause['order']) == 'array') {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }
    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('bank_mutations', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get bank reconciliation by account no.
   */
  public function getBankReconciliationByAccountNo($account_no)
  {
    $this->db->like('account_no', $account_no);
    $q = $this->db->get('bank_reconciliations');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }


  public function getBanksByType($type)
  {
    if (gettype($type) == 'array') {
      foreach ($type as $typ) {
        $this->db->or_like('type', $typ, 'none');
      }
    } else {
      $this->db->like('type', $type, 'none');
    }
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getBankTypes($empty = FALSE, $placeholder = '')
  {
    $this->db->select('type');
    $this->db->group_by('type');
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      if ($empty) $data[''] = $placeholder;
      foreach ($q->result_array() as $row) {
        $data[] = $row['type'];
      }
      return $data;
    }
    return ['Cash', 'Transfer'];
  }

  public function getBillerByID($id)
  {
    $q = $this->db->get_where('billers', ['id' => $id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBillerByName($name)
  {
    $this->db->like('name', $name, 'none');
    $q = $this->db->get('billers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getBiller($clause = [])
  {
    if ($row = $this->getBillers($clause)) {
      return $row[0];
    }
    return NULL;
  }

  public function getBillers($clause = [])
  {
    if (!empty($clause['code'])) {
      $this->db->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }
    if (!empty($clause['email'])) {
      $this->db->like('email', $clause['email'], 'none');
      unset($clause['email']);
    }
    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'none');
      unset($clause['name']);
    }

    if (!empty($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('billers', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getBillerSuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];
    $this->db->select('id, company as text');
    $this->db->where(" (id LIKE '%" . $term . "%' OR name LIKE '%" . $term . "%' OR company LIKE '%" . $term . "%') ");
    $q = $this->db->get_where('billers', ['group_name' => 'biller'], $limit);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCalendarByID($calendarId)
  {
    $q = $this->db->get_where('calendar', ['id' => $calendarId]);
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCalendars($clause = [])
  {
    if (isset($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
    }
    if (isset($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
    }

    $q = $this->db->get_where('calendar', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCategory($clause = [])
  {
    if ($rows = $this->getCategories($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getCategories($clause = [])
  { // Added
    $q = $this->db->get_where('categories', $clause);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('categories');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCategoryByProductID($id)
  {
    $this->db->select('categories.*')
      ->from('products')
      ->join('categories', 'products.category_id=categories.id')
      ->where('products.id', $id)
      ->order_by('products.name', 'ASC');
    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCategorySuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];

    $this->db->select("code AS id, CONCAT('(', code, ') ', name) AS text");

    if (!empty($term['code'])) {
      $this->db->like('code', $term['code'], 'none');
    } else {
      $this->db->where("(id LIKE '{$term}' OR code LIKE '%{$term}%' OR name LIKE '%{$term}%')");
    }

    $this->db->limit($limit);

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getComboItems($product_id, $item_code = NULL)
  {
    $this->db->where('product_id', $product_id);
    if ($item_code) {
      $this->db->like('item_code', $item_code);
    }
    $q = $this->db->get('combo_items');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getComboItemsByProductID($product_id)
  {
    $q = $this->db->get_where('combo_items', ['product_id' => $product_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCurrencyByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('currencies');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerAddressByID($address_id)
  {
    $q = $this->db->get_where('addresses', ['id' => $address_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerByEmail($email)
  {
    $this->db->where('email', $email);
    $q = $this->db->get('customers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerByID($customer_id)
  {
    $q = $this->db->get_where('customers', ['id' => $customer_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerByPhone($phone)
  {
    $phone = preg_replace('/[^0-9]/', '', $phone); // Trim any whitespace and except digit.
    $this->db->where('phone', $phone);
    $q = $this->db->get('customers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerDepositByID($deposit_id)
  {
    $q = $this->db->get_where('deposits', ['id' => $deposit_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerGroupByCustomerID($customer_id)
  {
    $this->db->select('customer_groups.*');
    $this->db->join('customers', 'customers.customer_group_id=customer_groups.id', 'left');
    $this->db->where('customers.id', $customer_id);
    $q = $this->db->get('customer_groups');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerGroupByID($id)
  {
    $q = $this->db->get_where('customer_groups', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomerGroupByName($name)
  {
    $this->db->like('name', $name, 'both');
    $q = $this->db->get('customer_groups');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getCustomers($clause = [], $options = [])
  {
    $clauses = [];

    if (!empty($clause['phone'])) $clauses['phone'] = $clause['phone'];

    if (!empty($options['limit'])) {
      $this->db->limit($options['limit']);
    } else {
      $this->db->limit(50);
    }

    $q = $this->db->get_where('customers', $clauses);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCustomerSales($customer_id)
  {
    $this->db->where('customer_id', $customer_id)->from('sales');
    return $this->db->count_all_results();
  }

  public function getCustomersByGroupName($group_name)
  {
    $this->db->select("customers.*")
      ->from('customers')
      ->join('customer_groups', 'customer_groups.id=customers.customer_group_id', 'left')
      ->like("customer_groups.name", $group_name, 'none');
    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getCustomersByPhone($phone)
  {
    $phone = preg_replace('/[^0-9]/', '', $phone); // Trim any whitespace and except digit.

    return DB::table('customers')->where('phone', $phone, 'both')->limit(20)->get();
  }

  public function getCustomerSuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];

    $db = DB::table('customers')->select(
      "id,
      (CASE WHEN company = '' THEN name ELSE CONCAT(company, ' (', name, ')') END) as text,
      (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', name, ')') END) as value,
      phone"
    );

    if (isset($term['id'])) {
      $db->where('id', $term['id']);
    } else {
      $db->like('name', $term, 'both')
        ->orLike('company', $term, 'both')
        ->orLike('phone', $term, 'none')
        ->limit($limit);
    }

    return $db->get();
  }

  public function getCustomerUsers($customer_id)
  {
    $q = $this->db->get_where('users', ['customer_id' => $customer_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getDateFormat($id)
  {
    $q = $this->db->get_where('date_format', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getExpenseByID($expense_id)
  {
    $q = $this->db->get_where('expenses', ['id' => $expense_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getExpenseCategory($clause = [])
  {
    if ($category = $this->getExpenseCategories($clause)) {
      return $category[0];
    }
    return NULL;
  }

  public function getExpenseCategories($clause = [])
  {
    if (!empty($clause['order'])) { // ORDER BY name ASC
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('expense_categories', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getExpenseCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getExpensePayments($expense_id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payments', ['expense_id' => $expense_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get expense by conditions.
   * @param array $clause [ id, reference, approved_by, bank_id, biller_id, created_by, payment_status,
   *  status, supplier_id, start_date, end_date, order ]
   */
  public function getExpenses($clause)
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference']);
    }

    if (!empty($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $this->db->where_in('biller_id', $clause['biller_id']);
        unset($clause['biller_id']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('expenses', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getGroupByID($id)
  {
    $this->db->where('id', $id);
    $q = $this->db->get('groups');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getGroupByName($name)
  {
    $this->db->like('name', $name);
    $q = $this->db->get('groups');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get group permissions.
   * @param int $group_id Group ID.
   * @param bool $assoc Return as associative array.
   */
  public function getGroupPermissions($group_id, $assoc = FALSE)
  { // New Added
    $q = $this->db->get_where('permissions', ['group_id' => $group_id]);

    if ($q->num_rows() > 0) {
      if ($assoc) { // As ARRAY
        $data = [];
        $pj   = [];

        foreach ($q->row_array() as $key => $val) {
          if ($key == 'id' || $key == 'group_id') continue;
          if ($key == 'permissions_json') {
            $pj = json_decode($val, $assoc);
            continue;
          }
          $data[$key] = $val;
        }

        if ($pj) {
          foreach ($pj as $key => $val) {
            $data[$key] = $val;
          }
        }
      } else { // As OBJECT
        $data = (object)[];
        $pj   = (object)[];

        foreach ($q->row() as $key => $val) {
          if ($key == 'id' || $key == 'group_id') continue;
          if ($key == 'permissions_json') {
            $pj = json_decode($val);
            continue;
          }
          $data->{$key} = $val;
        }

        if ($pj) {
          foreach ($pj as $key => $val) {
            $data->{$key} = $val;
          }
        }
      }

      return $data;
    }
    return [];
  }

  public function getHoliday($clause = [])
  {
    if ($rows = $this->getHolidays($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getHolidays($clause = [])
  {
    if (!empty($clause['biller_id'])) {
    }

    $q = $this->db->get_where('holiday', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getIncomeByID($income_id)
  {
    $q = $this->db->get_where('incomes', ['id' => $income_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getIncomeCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');

    $q = $this->db->get('income_categories');

    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return [];
  }

  public function getIncomeCategory($clause = [])
  {
    if ($category = $this->getIncomeCategories($clause)) {
      return $category[0];
    }
    return NULL;
  }

  public function getIncomeCategories($clause = [])
  {
    if (!empty($clause['order'])) { // ORDER BY name ASC
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
    }

    $q = $this->db->get_where('income_categories', $clause);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * THE ONLY FUNCTION TO GET INCOMES.
   *
   */
  public function getIncomes($clause)
  {
    $clauses = [];

    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference']);
    }

    if (!empty($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $this->db->where_in('biller_id', $clause['biller_id']);
        unset($clause['biller_id']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('incomes', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getJob($clause = [])
  {
    if ($row = $this->getJobs($clause)) {
      return $row[0];
    }
    return NULL;
  }

  public function getJobs($clause = [])
  {
    $this->db->order_by('id', 'ASC');
    $q = $this->db->get_where('jobs', $clause);
    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getLastPaymentBySaleID($sale_id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getLastPaymentValidationByID($pv_id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payment_validations', ['id' => $pv_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getLastSaleByCreatorID($created_by)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('sales', ['created_by' => $created_by]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getLastSaleByCustomerID($customer_id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('sales', ['customer_id' => $customer_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getMachineByCode($code)
  {
    $this->db
      ->select("products.id AS id, products.code AS code, products.name AS name,
        warehouses.id AS warehouse_id,
        JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_qty')) AS maintenance_qty,
        JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_cost')) AS maintenance_cost")
      ->join('categories', 'categories.id = products.subcategory_id', 'left')
      ->join('warehouses', 'warehouses.name LIKE products.warehouses', 'left')
      ->like('categories.code', 'MACH', 'none');

    $this->db->like('products.code', $code, 'none');

    $q = $this->db->get('products');

    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getMachineByID($id)
  {
    $this->db
      ->select("products.id AS id, products.code AS code, products.name AS name,
        warehouses.id AS warehouse_id,
        JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_qty')) AS maintenance_qty,
        JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.maintenance_cost')) AS maintenance_cost")
      ->join('categories', 'categories.id = products.subcategory_id', 'left')
      ->join('warehouses', 'warehouses.name LIKE products.warehouses', 'left')
      ->like('categories.code', 'MACH', 'none');

    $this->db->where('products.id', $id);

    $q = $this->db->get('products');

    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getMachineCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('machine_categories');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getMaintenanceLogs($clause = [])
  {
    $rows = [];

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('maintenance_logs', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }

    return $rows;
  }

  public function getNotificationByID($notify_id)
  {
    $q = $this->db->get_where('notifications', ['id' => $notify_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getNotifications()
  {
    $date = date('Y-m-d H:i:s');
    $this->db->where('from_date <=', $date);
    $this->db->where('till_date >=', $date);

    if (!$this->Owner) {
      if ($this->Customer) {
        $this->db->group_start();
        $this->db->where('scope', 1)->or_where('scope', 3);
        $this->db->group_end();
      } elseif (!$this->Customer) {
        $this->db->group_start();
        $this->db->where('scope', 2)->or_where('scope', 3);
        $this->db->group_end();
      }
    }

    $this->db->where('active', 1);
    $this->db->order_by('date', 'DESC'); // New notification at first row.
    $q = $this->db->get('notifications');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPaymentBeginningAmount($clause, $date)
  {
    $clauses = [];

    if (!empty($clause['bank_id']))   $clauses['bank_id']   = $clause['bank_id'];
    if (!empty($clause['biller_id'])) $clauses['biller_id'] = $clause['biller_id'];

    $clauses['end_date'] = date('Y-m-d', strtotime('-1 day', strtotime($date)));

    $payments   = $this->getPayments($clauses);

    $beginning_amount = 0;

    foreach ($payments as $payment) {
      if ($payment->status == 'received') {
        $beginning_amount += $payment->amount;
      } else if ($payment->status == 'sent') {
        $beginning_amount -= $payment->amount;
      }
    }

    return $beginning_amount;
  }

  public function getPaymentByID($id)
  {
    $q = $this->db->get_where('payments', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * GET PAYMENTS.
   * @param object $clause [ id, expense_id, income_id, mutation_id, purchase_id, sale_id, transfer_id,
   *   bank_id, biller_id, method, start_date, end_date, order(column|sort) ]
   */
  public function getPayments($clause = [])
  {
    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['has'])) {
      $this->db->where("{$clause['has']} IS NOT NULL");
      unset($clause['has']);
    }

    if (!empty($clause['nul'])) {
      $this->db->where("{$clause['nul']} IS NULL");
      unset($clause['nul']);
    }

    if (!empty($clause['method'])) {
      $this->db->like('method', $clause['method'], 'none');
      unset($clause['method']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('payments', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPaymentsByBankAccountNumber($accountNo)
  {
    $this->db
      ->select("payments.*, banks.holder AS holder, banks.number AS number")
      ->join('banks', 'banks.id = payments.bank_id', 'left')
      ->order_by('payments.date', 'ASC');

    $q = $this->db->get_where('payments', ['banks.number' => $accountNo]);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPaymentsByReference($reference)
  {
    $this->db->like('reference', $reference, 'none');
    $q = $this->db->get('payments');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPaymentsBySaleID($sale_id)
  {
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPaymentValidationBank($number, $biller_id)
  {
    $this->db->like('number', $number, 'none');
    $this->db->where('biller_id', $biller_id);
    $q = $this->db->get('banks');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getPaymentValidationByID($id)
  {
    $q = $this->db->get_where('payment_validations', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getPaymentValidationByMutationID($id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payment_validations', ['mutation_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * MASALAH
   */
  public function getPaymentValidationBySaleID($id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payment_validations', ['sale_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * MASALAH
   */
  public function getPaymentValidationsByStatus($status)
  {
    if (gettype($status) == 'string')
      $this->db->like('status', $status, 'both');
    if (gettype($status) == 'array') {
      $this->db->where_in('status', $status);
    }

    $q = $this->db->get('payment_validations');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getPriceGroupByID($id)
  {
    $q = $this->db->get_where('price_groups', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getPriceGroupByName($name)
  {
    if ($name) {
      $this->db->like('name', $name, 'both');
      $q = $this->db->get('price_groups');
      if ($q->num_rows() > 0) {
        return $q->row();
      }
    }
    return NULL;
  }

  public function getProduct($clause = [])
  {
    if ($rows = $this->getProducts($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getProductByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get product by id.
   * @param int $id Product ID.
   */
  public function getProductByID($id)
  {
    $this->db->select("products.*, units.code AS unit_name");
    $this->db->join('units', 'units.id = products.unit', 'left');

    $q = $this->db->get_where('products', ['products.id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getProductByName($name)
  {
    $this->db->like('name', $name, 'none');
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getProductCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getProductCategoryByID($category_id)
  {
    $q = $this->db->get_where('categories', ['id' => $category_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get combo items (standard) by product id (combo item).
   * @param int $product_id Combo item ID.
   * @param int $warehouse_id Warehouse ID.
   */
  public function getProductComboItems($product_id, $warehouse_id = null)
  {
    $this->db->select('combo_items.id as id, products.id as product_id,
      combo_items.item_code as code, combo_items.quantity as qty, products.name as name,
      products.type as type, combo_items.unit_price as unit_price,
      warehouses_products.quantity as quantity')
      ->join('products', 'products.code=combo_items.item_code', 'left')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->group_by('combo_items.id');
    if ($warehouse_id) {
      $this->db->where('warehouses_products.warehouse_id', $warehouse_id);
    }
    $q = $this->db->get_where('combo_items', ['combo_items.product_id' => $product_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getProductGroupPrice($product_id, $group_id)
  {
    $q = $this->db->get_where('product_prices', ['price_group_id' => $group_id, 'product_id' => $product_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getProductHistories($clauses)
  {
    return [];
  }

  /**
   * Get product mutation.
   * @param array $clause [ id, status, from_warehouse_id, to_warehouse_id, created_by, updated_by,
   *  start_date, end_date, order ]
   */
  public function getProductMutation($clause = [])
  {
    if ($rows = $this->getProductMutations($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get product mutation items.
   * @param array $clause [ id, pm_id, product_id, product_code, status ]
   */
  public function getProductMutationItems($clause = [])
  {
    $q = $this->db->get_where('product_mutation_item', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get product mutations.
   * @param array $clause [ id, status, from_warehouse_id, to_warehouse_id, created_by, updated_by,
   *  start_date, end_date, order ]
   */
  public function getProductMutations($clause = [])
  {

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at >= '{$clause['end_date']} 00:00:00'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('product_mutation', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  /**
   * To find product for Stock Transfer or Stock Purchase. Standard Only.
   */
  public function getProductNames($term, $limit = 10, $options = [])
  {
    if (empty($term)) return [];

    $this->db->select("products.id AS id, code, name, unit, cost, price, warehouses, markon_price,
      markon, safety_stock, min_order_qty, iuse_type, active, quantity, type, purchase_unit");

    if (!empty($options['iuse_type'])) {
      $this->db->like('iuse_type', $options['iuse_type']);
    }

    $this->db->where_in('type', ['service', 'standard']);
    $this->db->where("code LIKE '%{$term}%' OR name LIKE '%{$term}%'");
    $this->db->limit($limit);

    $q = $this->db->get('products');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getProductPrices($product_id, $price_group_id)
  {
    $q = $this->db->get_where('product_prices', ['product_id' => $product_id, 'price_group_id' => $price_group_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get product reports.
   *
   * @param array $clause [ int id, str condition, id product_id, str product_code, int warehouse_id,
   *  str warehouse_code, int created_by, int updated_by, str start_date, str end_date,
   *  array order_by['created_at', 'ASC'], int limit ]
   */
  public function getProductReport($clause = [])
  {
    if ($rows = $this->getProductReports($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get product reports.
   *
   * @param array $clause [ int id, str condition, id product_id, str product_code, int warehouse_id,
   *  str warehouse_code, int created_by, int updated_by, str start_date, str end_date,
   *  array order_by['created_at', 'ASC'], int limit ]
   */
  public function getProductReports($clause = [])
  {
    $clauses = [];

    if (!empty($clause['id']))           $clauses['id']           = $clause['id'];
    if (!empty($clause['product_id']))   $clauses['product_id']   = $clause['product_id'];
    if (!empty($clause['warehouse_id'])) $clauses['warehouse_id'] = $clause['warehouse_id'];
    if (!empty($clause['created_by']))   $clauses['created_by']   = $clause['created_by'];
    if (!empty($clause['updated_by']))   $clauses['updated_by']   = $clause['updated_by'];

    if (!empty($clause['condition'])) {
      $this->db->like('condition', $clause['condition'], 'none');
    }

    if (!empty($clause['product_code'])) {
      $this->db->like('product_code', $clause['product_code'], 'none');
    }

    if (!empty($clause['warehouse_code'])) {
      $this->db->like('warehouse_code', $clause['warehouse_code'], 'none');
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
    }

    if (!empty($clause['order_by']) && is_array($clause['order_by'])) {
      $this->db->order_by($clause['order_by'][0], $clause['order_by'][1]);
    }

    if (!empty($clause['limit']) && is_numeric($clause['limit'])) {
      $this->db->limit($clause['limit']);
    }

    $q = $this->db->get_where('product_report', $clauses);

    if ($q->num_rows() > 0) {
      return $q->result();
    }

    return [];
  }

  /**
   * Get products.
   * @param array $opt Options
   * - type: [ combo, service, standard ]
   */
  public function getProducts($clause = [])
  {
    if (!empty($clause['code'])) {
      $this->db->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }

    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'none'); // IF ANY ERROR CHANGE TO 'both'
      unset($clause['name']);
    }

    if (!empty($clause['type'])) {
      if (gettype($clause['type']) == 'string') { // If type is String.
        $this->db->like('type', $clause['type'], 'none');
      } else if (gettype($clause['type']) == 'array') { // If type is Array.
        $this->db->where_in('type', $clause['type']);
      }
      unset($clause['type']);
    }

    if (!empty($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('products', $clause);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get products for select2. (NEW)
   * @param string $term Search terms.
   * @param array $opt [ type:(standard|combo|service), limit:10,
   *  warehouse_id, warehouse_id_from, warehouse_id_to ]
   */
  public function getProductSelect2($term, $opt = [])
  {
    $hasWarehouse     = isset($opt['warehouse_id']);
    $hasWarehouseFrom = isset($opt['warehouse_id_from']);
    $hasWarehouseTo   = isset($opt['warehouse_id_to']);

    $quantity    = ($hasWarehouse ? 'whp.quantity, whp.warehouse_id' : 'products.quantity');

    if ($hasWarehouseFrom && $hasWarehouseTo) {
      $quantity = 'whp_from.quantity AS quantity_from, whp_from.warehouse_id warehouse_id_from';
      $quantity .= ', whp_to.quantity AS quantity_to, whp_to.warehouse_id warehouse_id_to';
    } else if ($hasWarehouseFrom) {
      $quantity = 'whp_from.quantity AS quantity_from, whp_from.warehouse_id warehouse_id_from';
    } else if ($hasWarehouseTo) {
      $quantity = 'whp_to.quantity AS quantity_to, whp_to.warehouse_id warehouse_id_to';
    }

    $safetyStock = ($hasWarehouse ? 'whp.safety_stock' : 'products.safety_stock');

    $this->db
      ->select("products.id, CONCAT('(', products.code, ') ', products.name) AS text, products.code,
        products.name, cost, markon, markon_price, {$quantity}, {$safetyStock}")
      ->from('products');

    if ($hasWarehouse) {
      $this->db->join('warehouses_products whp', 'whp.product_id = products.id', 'left');
      $this->db->where('whp.warehouse_id', $opt['warehouse_id']);
    }

    if ($hasWarehouseFrom) {
      $this->db->join('warehouses_products whp_from', 'whp_from.product_id = products.id', 'left');
      $this->db->where('whp_from.warehouse_id', $opt['warehouse_id_from']);
    }

    if ($hasWarehouseTo) {
      $this->db->join('warehouses_products whp_to', 'whp_to.product_id = products.id', 'left');
      $this->db->where('whp_to.warehouse_id', $opt['warehouse_id_to']);
    }

    if ($term && gettype($term) == 'string') {
      $this->db->where(
        "(products.code LIKE '%{$term}%' OR products.name LIKE '%{$term}%')"
      );
    }

    if (isset($opt['type'])) {
      if (gettype($opt['type']) == 'string') {
        $this->db->like('products.type', $opt['type'], 'none');
      }
      if (is_array($opt['type'])) {
        $this->db->where_in('products.type', $opt['type']);
      }
    }

    if (isset($opt['order'])) {
      $this->db->order_by($opt['order'][0], $opt['order'][1]);
    }

    if (isset($opt['limit'])) {
      $this->db->limit($opt['limit']);
    }

    $this->db->group_by('products.id');

    $q = $this->db->get();

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  /**
   * @deprecated Replace with getProductSelect2()
   */
  public function getProductSuggestions($term, $type = 'standard', $limit = 10)
  {
    $this->db->select("id, CONCAT('(', code, ') ', name) AS text, code, name, cost, quantity");

    if (is_array($term)) {
      if (isset($term['id'])) {
        $this->db->where('id', $term['id']);
      }

      if (isset($term['warehouse_id'])) {
      }
    } else if (gettype($term) == 'string') {
      $this->db->where("(id LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR name LIKE '%" . $term . "%')");
    }

    if ($type) {
      if (stripos($type, ',') > 0) { // 'service,standard'
        $ty = explode(',', $type);
        $this->db->group_start();
        foreach ($ty as $t) {
          $this->db->or_like('type', $t);
        }
        $this->db->group_end();
      } else {
        $this->db->like('type', $type);
      }
    }

    $this->db->limit($limit); // Default to 10.

    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getProductUnitByID($unit_id)
  {
    $q = $this->db->get_where('units', ['id' => $unit_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getProductVariants($product_id)
  {
    return NULL;
  }

  /**
   * Get stock purchase (NEW)
   * @param array $clause [ id, reference, biller_id, category_id, warehouse_id, supplier_id,
   *  payment_status, status, start_date, end_date, order ]
   */
  public function getPurchase($clause = [])
  {
    if ($rows = $this->getPurchases($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get stock purchase (NEW)
   * @param array $clause [ id, reference, biller_id, category_id, warehouse_id, supplier_id,
   *  payment_status, status, start_date, end_date, order ]
   */
  public function getPurchases($clause = [])
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
      unset($clause['reference']);
    }

    if (!empty($clause['payment_status'])) {
      $this->db->like('payment_status', $clause['payment_status'], 'none');
      unset($clause['payment_status']);
    }

    if (!empty($clause['status'])) {
      $this->db->like('status', $clause['status'], 'none');
      unset($clause['status']);
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('purchases', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getQuantityAlertProducts()
  {
    $this->db
      ->select('products.id as id, products.code as code, products.name as name,
      products.quantity as current_quantity,
      products.safety_stock as quantity_alert, min_order_qty, purchase_unit, cost, type, units.code as unit_code, units.name as unit_name')
      ->join('units', 'units.id=products.purchase_unit', 'left')
      ->like('type', 'standard')
      ->limit(10)
      ->where('safety_stock !=', 0)
      ->where('safety_stock IS NOT NULL')
      ->where('track_quantity', 1);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getRandomReference($len = 12)
  {
    $result = '';
    for ($i = 0; $i < $len; $i++) {
      $result .= mt_rand(0, 9);
    }

    if ($this->getSaleByReference($result)) {
      $this->getRandomReference();
    }

    return $result;
  }

  public function getReference($field)
  {
    $q = $this->db->get_where('order_ref', ['ref_id' => '1'], 1);
    if ($q->num_rows() > 0) {
      $ref = $q->row();
      switch ($field) {
        case 'iuse':
          $prefix = $this->Settings->internaluse_prefix;
          break;
        case 'sale':
          $prefix = $this->Settings->sales_prefix;
          break;
        case 'opname':
          $prefix = 'SO-';
          break;
        case 'cmreport': // NOT USED ANYMORE.
          $prefix = 'CMR-';
          break;
        case 'purchase':
          $prefix = $this->Settings->purchase_prefix;
          break;
        case 'transfer':
          $prefix = $this->Settings->transfer_prefix;
          break;
        case 'expense':
          $prefix = $this->Settings->expense_prefix;
          break;
        case 'income':
          $prefix = $this->Settings->income_prefix;
          break;
        case 'mutation':
          $prefix = $this->Settings->mutation_prefix;
          break;
        case 'adjustment':
          $prefix = $this->Settings->qa_prefix;
          break;
        default:
          $prefix = ''; // No prefix.
      }

      $ref_no = $prefix;

      if ($this->Settings->reference_format == 1) {
        $ref_no .= date('Y') . '/' . sprintf('%04s', $ref->{$field});
      } elseif ($this->Settings->reference_format == 2) { // Use this.
        $ref_no .= date('Y') . '/' . date('m') . '/' . sprintf('%04s', $ref->{$field});
      } elseif ($this->Settings->reference_format == 3) {
        $ref_no .= sprintf('%04s', $ref->{$field});
      } else {
        $ref_no .= $this->getRandomReference();
      }

      return $ref_no;
    }
    return NULL;
  }

  public function getPayrollCategoryByID($id)
  {
    $q = $this->db->get_where('payroll_categories', ['id' => $id]);

    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSaleByID($id)
  {
    $q = $this->db->get_where('sales', ['id' => $id], 1);
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSaleByPaymentID($paymentId)
  {
    $q = $this->db->get_where('payments', ['id' => $paymentId]);

    if ($q->num_rows() > 0) {
      $sale = $this->getSaleByID($q->row('sale_id'));

      if ($sale) {
        return $sale;
      }
    }

    return NULL;
  }

  public function getSaleByReference($ref)
  {
    $this->db->like('reference', $ref, 'none');
    $q = $this->db->get('sales', 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSaleBySaleItemID($saleitem_id)
  {
    $q1 = $this->db->get_where('sale_items', ['id' => $saleitem_id], 1);
    if ($q1->num_rows() > 0) {
      $q2 = $this->db->get_where('sales', ['id' => $q1->row()->sale_id], 1);
      if ($q2->num_rows() > 0) {
        return $q2->row();
      }
    }
    return NULL;
  }

  // My Own Function to get Sale Item
  public function getSaleItem($sale_params)
  {
    if (
      isset($sale_params['product_id']) &&  !empty($sale_params['product_id']) &&
      isset($sale_params['sale_id']) &&  !empty($sale_params['sale_id'])
    ) {
      $product_id = $sale_params['product_id'];
      $sale_id = $sale_params['sale_id'];

      $this->db->select('*')
        ->where('product_id =', $product_id)
        ->where('sale_id =', $sale_id);
      $q = $this->db->get('sale_items');
      if ($q->num_rows() > 0) {
        return $q->row();
      }
    }
    return NULL;
  }

  public function getSaleItemByID($saleitem_id)
  {
    $q = $this->db->get_where('sale_items', ['id' => $saleitem_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get Sale Items.
   * @param array $clause [ id, sale_id ]
   */
  public function getSaleItems($clause)
  {
    $clauses = [];

    if (isset($clause['id']))      $clauses['id']      = $clause['id'];
    if (isset($clause['sale_id'])) $clauses['sale_id'] = $clause['sale_id'];

    if (isset($clause['operator_id'])) {
      $this->db->like("JSON_UNQUOTE(JSON_EXTRACT(json_data, '$.operator_id'))", $clause['operator_id'], 'none');
    }

    if (!empty($clause['start_date']) || !empty($clause['end_date'])) {

      $startDate = ($clause['start_date'] ?? date('Y-m-') . '01');
      $endDate   = ($clause['end_date'] ?? date('Y-m-d'));

      $this->db->where("date BETWEEN '{$startDate} 00:00:00' AND '{$endDate} 23:59:59'");
    }

    $q = $this->db->get_where('sale_items', $clauses);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    } else if (!$q) {
      echo 'Database error . ' . $this->db->error()['message'];
      die();
    }
    return [];
  }

  public function getSaleItemsBySaleID($sale_id, $options = [])
  {
    $this->db->select('sale_items.*, products.unit as base_unit_id,
      products.price_ranges_value as price_ranges_value, units.code as base_unit_code,
      categories.code as category_code')
      ->join('products', 'products.id = sale_items.product_id', 'left')
      ->join('units', 'units.id = products.unit', 'left')
      ->join('categories', 'categories.id = products.category_id', 'left');

    if ($options) {
      if (!empty($options['product_id'])) $this->db->where('sale_items.product_id', $options['product_id']);
      if (!empty($options['quantity']))   $this->db->where('sale_items.quantity', $options['quantity']);
    }

    $q = $this->db->get_where('sale_items', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSalePayments($sale_id)
  {
    $q = $this->db->get_where('payments', ['sale_id' => $sale_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSaleProductSuggestions($term, $use_standard = FALSE, $limit = 30)
  {
    $wp = "(SELECT product_id, warehouse_id, quantity as quantity from warehouses_products) WHP";

    $this->db->select('products.*, WHP.quantity as quantity, categories.id as category_id, categories.code as category_code, categories.name as category_name')
      ->join($wp, 'WHP.product_id=products.id', 'left')
      ->join('categories', 'categories.id=products.category_id', 'left')
      ->group_by('products.id');

    $this->db->where("(products.name LIKE '%" . $term . "%' OR products.code LIKE '%" . $term . "%' OR  concat(products.name, ' (', products.code, ')') LIKE '%" . $term . "%')");
    $this->db->where("products.code NOT LIKE 'W2P%'"); // No W2P products.
    $this->db->where("products.active", 1); // Active product only.

    $this->db->group_start();
    $this->db->where("products.type LIKE 'combo' OR products.type LIKE 'service'"); // FILTER for show COMBO and SERVICE only.

    if ($use_standard) {
      $this->db->or_where("products.type LIKE 'standard'");
    }
    $this->db->group_end();

    $this->db->order_by('products.code', 'ASC');

    if ($limit) {
      $this->db->limit($limit);
    }

    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
  }

  /**
   * Get sales with conditions.
   * @param array $clause [ biller_id, customer_id, payment_status, status, warehouse_id,
   *  start_date (yyyy-mm-dd), end_date (yyyy-mm-dd) ]
   */
  public function getSales($clause = [])
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
      unset($clause['reference']);
    }

    if (isset($clause['warehouse_id'])) {
      if (gettype($clause['warehouse_id']) == 'array') {
        $this->db->where_in('warehouse_id', $clause['warehouse_id']);
        unset($clause['warehouse_id']);
      }
    }

    if (isset($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $this->db->where_in('biller_id', $clause['biller_id']);
        unset($clause['biller_id']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('sales', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getSalesByCustomerGroupName($group_name)
  {
    $this->db->select("sales.*")
      ->from('sales')
      ->join('customers', 'customers.id=sales.customer_id', 'left')
      ->join('customer_groups', 'customer_groups.id=customers.customer_group_id', 'left')
      ->like("customer_groups.name", $group_name, 'none');
    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSalesByPaymentStatus($status)
  {
    $q = $this->db->get_where('sales', ['payment_status' => $status]);
    if ($q->num_rows() > 0) {
      $data = [];
      return $q->result();
    }
  }

  public function getSalesByReference($ref)
  {
    $this->db->like('reference', $ref, 'both');
    $q = $this->db->get('sales');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * THE ONLY FUNCTION TO GET SALES TB.
   * @param array $clause [ id, from_biller_id, to_warehouse_id, created_by, start_date, end_date ]
   */
  public function getSalesTB($clause)
  {
    $clauses = [];

    if (!empty($clause['id']))              $clauses['id']              = $clause['id'];
    if (!empty($clause['from_biller_id']))  $clauses['from_biller_id']  = $clause['from_biller_id'];
    if (!empty($clause['to_warehouse_id'])) $clauses['to_warehouse_id'] = $clause['to_warehouse_id'];
    if (!empty($clause['created_by']))      $clauses['created_by']      = $clause['created_by'];

    if (!empty($clause['start_date']) || !empty($clause['end_date'])) {
      $start_date = ($clause['start_date'] ?? NULL);
      $end_date   = ($clause['end_date']   ?? date('Y-m-d'));

      if ($start_date && $end_date) {
        $this->db->where("date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
      } else if ($start_date) {
        $this->db->where("date >= '{$start_date} 00:00:00'");
      } else if ($end_date) {
        $this->db->where("date <= '{$end_date} 23:59:59'");
      }
    }

    if (!empty($clause['status'])) {
      $this->db->like('status', $clause['status']);
    }

    $q = $this->db->get_where('sales_tb', $clauses);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSchedule($clause = [])
  {
    if ($rows = $this->getSchedules($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getSchedules($clause = [])
  {
    $q = $this->db->get_where('schedule', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }


  public function getSettings()
  {
    $q = $this->db->get('settings');
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSettingsJSON()
  {
    $settings = $this->getSettings();
    if ($settings) {
      return json_decode($settings->settings_json);
    }
    return NULL;
  }

  /**
   * Get sold items by warehouse id.
   * @param int $warehouse_id Warehouse ID.
   * @param object $options [start_date, end_date]
   */
  public function getSoldItemsByWarehouseID($warehouse_id, $options)
  {
    $items = [];
    $clause = $options;
    $clause['warehouse_id'] = $warehouse_id;

    $stocks = $this->getStocks($clause);

    if ($stocks) {
      foreach ($stocks as $stock) {
        $product = $this->getProductByID($stock->product_id);

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

  public function getStockAdjustmentByID($adjustment_id)
  {
    $q = $this->db->get_where('adjustments', ['id' => $adjustment_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getStockAdjustmentItems($adjustment_id)
  {
    return $this->getStocks(['adjustment_id' => $adjustment_id]);
  }

  /**
   * THE ONLY FUNCTION TO GET STOCK BEGINNING.
   */
  public function getStockBeginningQuantity($clause, $date)
  {
    $clauses = [];

    if (!empty($clause['product_id']))   $clauses['product_id']   = $clause['product_id'];
    if (!empty($clause['warehouse_id'])) $clauses['warehouse_id'] = $clause['warehouse_id'];

    //$this->db->where("date < '{$date} 00:00:00'");

    $end_date = date('Y-m-d', strtotime('-1 day', strtotime($date ?? date('Y-m-d'))));
    $clauses['end_date'] = $end_date;

    $stocks   = $this->getStocks($clauses);

    $beginning_qty = 0.0;

    foreach ($stocks as $stock) {
      if ($stock->status == 'received') {
        $beginning_qty += $stock->quantity;
      } else if ($stock->status == 'sent') {
        $beginning_qty -= $stock->quantity;
      }
    }

    return $beginning_qty;
  }

  public function getStockByID($stock_id)
  {
    $q = $this->db->get_where('stocks', ['id' => $stock_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getStockInternalUseByID($iuseId)
  {
    $q = $this->db->get_where('internal_uses', ['id' => $iuseId]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getStockInternalUseItems($iuseId)
  {
    return $this->getStocks(['internal_use_id' => $iuseId]);
  }

  /**
   * THE ONLY FUNCTION TO GET INTERNAL USES.
   */
  public function getStockInternalUses($clause)
  {
    $clauses = [];

    if (!empty($clause['id'])) $clauses['id'] = $clause['id'];
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
    }

    if (!empty($clause['from_warehouse_id']) && gettype($clause['from_warehouse_id']) == 'array') {
      $from_warehouses = implode(',', $clause['from_warehouse_id']);
      $this->db->where("from_warehouse_id IN ({$from_warehouses})");
    } else if (!empty($clause['from_warehouse_id'])) {
      $clauses['from_warehouse_id'] = $clause['from_warehouse_id'];
    }

    if (!empty($clause['to_warehouse_id']) && gettype($clause['to_warehouse_id']) == 'array') {
      $to_warehouses = implode(',', $clause['to_warehouse_id']);
      $this->db->where("to_warehouse_id IN ({$to_warehouses})");
    } else if (!empty($clause['to_warehouse_id'])) {
      $clauses['to_warehouse_id'] = $clause['to_warehouse_id'];
    }

    if (!empty($clause['category'])) {
      $this->db->like('category', $clause['category'], 'none');
    }
    if (!empty($clause['created_by'])) $clauses['created_by'] = $clause['created_by'];
    if (!empty($clause['updated_by'])) $clauses['updated_by'] = $clause['updated_by'];
    if (!empty($clause['status'])) {
      $this->db->like('category', $clause['status'], 'none');
    }

    if (!empty($clause['biller_id']) && gettype($clause['biller_id']) == 'array') {
      $billers = implode(',', $clause['biller_id']);
      $this->db->where("biller_id IN ({$billers})");
    } else if (!empty($clause['biller_id'])) {
      $clauses['biller_id'] = $clause['biller_id'];
    }

    if (!empty($clause['start_date']) || !empty($clause['end_date'])) {
      $start_date = ($clause['start_date'] ?? date('Y-m-') . '01');
      $end_date   = ($clause['end_date']   ?? date('Y-m-d'));

      $this->db->where("date BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'");
    }

    $q = $this->db->get_where('internal_uses', $clauses);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getStockOpnameByID($opname_id)
  {
    $this->db
      ->select("stock_opnames.id AS id, stock_opnames.date AS date, stock_opnames.reference AS reference,
        stock_opnames.adjustment_plus_id AS adjustment_plus_id,
        stock_opnames.adjustment_min_id AS adjustment_min_id,
        stock_opnames.attachment AS attachment, stock_opnames.cycle AS cycle, stock_opnames.note AS note,
        stock_opnames.total_lost AS total_lost, stock_opnames.total_plus AS total_plus,
        stock_opnames.total_edited AS total_edited,
        stock_opnames.status AS status,
        stock_opnames.warehouse_id AS warehouse_id, stock_opnames.warehouse_code AS warehouse_code,
        warehouses.name AS warehouse_name, stock_opnames.created_by AS created_by,
        stock_opnames.updated_at AS updated_at, stock_opnames.updated_by AS updated_by")
      ->from('stock_opnames')
      ->join('warehouses', 'warehouses.id = stock_opnames.warehouse_id', 'left')
      ->where('stock_opnames.id', $opname_id);

    $q = $this->db->get();
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getStockOpnameItems($opname_id)
  {
    $this->db
      ->select("stock_opname_items.*, products.name AS product_name, units.code AS unit_name,
        warehouses.name AS warehouse_name")
      ->from('stock_opname_items')
      ->join('products', 'products.id = stock_opname_items.product_id', 'left')
      ->join('warehouses', 'warehouses.id = stock_opname_items.warehouse_id', 'left')
      ->join('units', 'units.id = products.unit', 'left')
      ->where('stock_opname_items.opname_id', $opname_id);

    $q = $this->db->get();

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get Stock Opnames.
   * @param array $clause [ id, adjustment_plus_id, adjustment_min_id, status, warehouse_id, warehouse_code,
   *  created_by, updated_by ]
   */
  public function getStockOpnames($clause = [])
  {
    if (!empty($clause['status'])) {
      $this->db->like('status', $clause['status']);
    }

    if (isset($clause['warehouse_id'])) {
      if (gettype($clause['warehouse_id']) == 'array') {
        $whIds = implode(',', $clause['warehouse_id']);
        $this->db->where("warehouse_id IN ({$whIds})");
        unset($clause['warehouse_id']);
      }
    }

    if (isset($clause['warehouse_code'])) {
      if (gettype($clause['warehouse_code']) == 'array') {
        $whCodes = implode(',', $clause['warehouse_code']);
        $this->db->where("warehouse_code IN ({$whCodes})");
        unset($clause['warehouse_code']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('stock_opnames', $clause);
    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Get Stock Opname Suggestion Items.
   * @param int $user_id User ID.
   * @param int $warehouse_id Warehouse ID.
   * @param int $cycle SO Cycle.
   */
  public function getStockOpnameSuggestions($user_id, $warehouse_id, $cycle)
  {
    $this->db
      ->select('products.id AS id, products.code AS code, products.name AS name,
        units.code AS unit_name, warehouses_products.quantity AS quantity');
    $this->db
      ->join('products', 'products.id = warehouses_products.product_id', 'left')
      ->join('units', 'units.id = products.unit', 'left');
    $this->db
      ->where('products.active', 1)
      ->where('warehouses_products.user_id', $user_id)
      ->where('warehouses_products.warehouse_id', $warehouse_id)
      ->where('warehouses_products.so_cycle', $cycle)
      ->like('products.type', 'standard'); // Important !!!
    $this->db
      ->group_by('products.id');
    $this->db
      ->order_by('name', 'ASC');

    $q = $this->db->get('warehouses_products');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getStockPurchaseByID($purchase_id)
  {
    $q = $this->db->get_where('purchases', ['id' => $purchase_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * THE ONLY FUNCTION TO GET STOCK PURCHASE ITEMS BY PURCHASE ID.
   */
  public function getStockPurchaseItems($purchase_id)
  {
    return $this->getStocks(['purchase_id' => $purchase_id]);
  }

  public function getStockPurchasePayments($purchase_id)
  {
    $this->db->order_by('date', 'DESC');
    $q = $this->db->get_where('payments', ['purchase_id' => $purchase_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * THE ONLY FUNCTION TO GET STOCK PURCHASES.
   * @param array $clause [ id, warehouse_id, start_date, end_date, order[column, sort]]
   */
  public function getStockPurchases($clause = [])
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
      unset($clause['reference']);
    }

    if (isset($clause['biller_id'])) {
      if (gettype($clause['biller_id']) == 'array') {
        $billers = implode(',', $clause['biller_id']);
        $this->db->where("biller_id IN ({$billers})");
        unset($clause['biller_id']);
      }
    }

    if (isset($clause['warehouse_id'])) {
      if (gettype($clause['warehouse_id']) == 'array') {
        $warehouses = implode(',', $clause['warehouse_id']);
        $this->db->where("warehouse_id IN ({$warehouses})");
        unset($clause['warehouse_id']);
      }
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("date >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("date <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && gettype($clause['order']) == 'array') {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('purchases', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * To get real stock quantity.
   * @param int $product_id Product ID
   * @param int $warehouse_id Warehouse ID
   * @param array $options Options: start_date, end_date
   * @return float
   */
  public function getStockQuantity($product_id, $warehouse_id = NULL, $options = [])
  {
    $opts = '';
    if ($warehouse_id) {
      $opts = "AND warehouse_id = {$warehouse_id} ";
    }

    // Last Options
    if (!empty($options['start_date']) &&  !empty($options['end_date'])) {
      $opts .= "AND date BETWEEN '{$options['start_date']} 00:00:00' AND '{$options['end_date']} 23:59:59'";
    } else if (!empty($options['start_date'])) {
      $opts .= "AND date >= '{$options['start_date']} 00:00:00'";
    } else if (!empty($options['end_date'])) {
      $opts .= "AND date <= '{$options['end_date']} 23:59:59'";
    }

    if (!empty($options['status'])) {
      $status = $options['status'];
      $this->db->select("COALESCE(qty_in.total_balance, 0) as stock")
        ->from('stocks')
        ->join("(SELECT product_id, SUM(quantity) as total_balance FROM
          stocks WHERE status LIKE '{$status}' {$opts}
          GROUP BY product_id) as qty_in", 'qty_in.product_id=stocks.product_id', 'left');
    } else { // Default
      $this->db->select("(COALESCE(qty_in.total_balance, 0) - COALESCE(qty_out.total_balance, 0)) as stock")
        ->from('stocks')
        ->join("(SELECT product_id, SUM(quantity) as total_balance FROM
          stocks WHERE status LIKE 'received' {$opts}
          GROUP BY product_id) as qty_in", 'qty_in.product_id=stocks.product_id', 'left')
        ->join("(SELECT product_id, SUM(quantity) as total_balance FROM
          stocks WHERE status LIKE 'sent' {$opts}
          GROUP BY product_id) as qty_out", 'qty_out.product_id=stocks.product_id', 'left');
    }

    if (!empty($options['quantity'])) {
      $this->db->where('stocks.quantity', $options['quantity']);
    }

    $this->db->where('stocks.product_id', $product_id);
    $q = $this->db->get();

    if ($q->num_rows() > 0) {
      return filterDecimal($q->row('stock'));
    }
    return 0.0;
  }

  /**
   * THE ONLY FUNCTION TO GET STOCKS.
   * @param array $clause
   * $clause [ id, (adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id),
   * product_id, warehouse_id, saleitem_id, status, start_date, end_date, order(column,sort) ]
   */
  public function getStocks($clause = [])
  {
    if (!empty($clause['not_null'])) {
      $this->db->where("{$clause['not_null']} IS NOT NULL");
      unset($clause['not_null']);
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order'])) {
      // $clause['order'][0] = 'created_at | $clause['order'][1] = 'ASC'
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('stocks', $clause);

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSubCategories($parent_code)
  {
    $this->db->like('parent_code', $parent_code, 'none')->order_by('name');

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSubCategorySuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];

    $this->db->select("code AS id, CONCAT('(', code, ') ', name) AS text");

    if (!empty($term['code'])) {
      $this->db->like('parent_code', $term['code'], 'none');
    } else {
      $this->db->where("(id LIKE '{$term}' OR code LIKE '%{$term}%' OR name LIKE '%{$term}%')");
    }

    $this->db->limit($limit);

    $q = $this->db->get('categories');

    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSubUnitsByUnitID($unit_id)
  {
    $unit = $this->getUnitByID($unit_id);
    $this->db->like('base_unit', $unit->code);
    $q = $this->db->get('units');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSupplier($clause = [])
  {
    if ($rows = $this->getSuppliers($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getSuppliers($clause = [])
  {
    if (!empty($clause['company'])) {
      $this->db->like('company', $clause['company'], 'none');
      unset($clause['company']);
    }
    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'none');
      unset($clause['name']);
    }

    $q = $this->db->get_where('suppliers', $clause);
    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getSupplierByCompanyName($company)
  {
    if (empty($company)) return NULL;
    $this->db->like('company', $company, 'both');
    $q = $this->db->get('suppliers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSupplierByID($supplier_id)
  {
    $q = $this->db->get_where('suppliers', ['id' => $supplier_id]);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSupplierByName($name)
  {
    $this->db->like('name', $name, 'both');
    $q = $this->db->get('suppliers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getSupplierByProductID($product_id)
  {
  }

  /**
   * Get Supplier products.
   * @param int $supplier_id Supplier ID.
   */
  public function getSupplierProducts($supplier_id)
  {
    $this->db->where('supplier_id', $supplier_id);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSupplierSuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];
    $this->db->select("id, (CASE WHEN company = '-' THEN name ELSE CONCAT(company, ' (', name, ')') END) as text", false);
    if (isset($term['id'])) {
      $this->db->where('id', $term['id']);
    } else {
      $this->db->where(" (id LIKE '%" . $term . "%' OR name LIKE '%" . $term . "%' OR company LIKE '%" . $term . "%' OR email LIKE '%" . $term . "%' OR phone LIKE '%" . $term . "%') ");
    }
    $q = $this->db->get('suppliers', $limit);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getSupplierUsers($supplier_id)
  {
    $q = $this->db->get_where('users', ['supplier_id' => $supplier_id]);
    if ($q->num_rows() > 0) {
      $data = [];
      return $q->result();
    }
    return [];
  }

  public function getTotalComboPricesByRawItems($raw_items)
  {
    $price = 0;
    if (!empty($raw_items)) {
      foreach ($raw_items as $item) {
        if (isset($item['item_code'])) {
          $q = $this->db->get_where('products', ['code' => $item['item_code']], 1);
          if ($q->num_rows() > 0) {
            $price += floatval($q->row()->price) * floatval($item['quantity']);
          }
        }
      }
      return $price;
    }
    return NULL;
  }

  public function getTrackingPODByID($id)
  {
    $q = $this->db->get_where('trackingpod', ['id' => $id]);
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * @param array $clause [ pod_id, warehouse_id, order, start_date, end_date ]
   */
  public function getTrackingPODs($clause = [])
  {
    $res = [];

    if (!empty($clause['order']) && gettype($clause['order']) == 'array') {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }
    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('trackingpod', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    return $res;
  }

  public function getTrackingPODUsers($clause = [])
  {
    $res = [];

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $this->db->group_by('created_by');

    $q = $this->db->get_where('trackingpod', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }

    return $res;
  }

  /**
   * Get transfered warehouse product
   */
  public function getTransferedWarehouseStocks($warehouse_id, $options = [])
  {
    $items = [];

    $clause = (gettype($options) != 'array' ? [] : $options);
    $clause['warehouse_id'] = $warehouse_id;

    $stocks = $this->getStocks($clause);

    if ($stocks) {
      foreach ($stocks as $stock) {
        if (!$stock->transfer_id) continue; // Transfer ID only.
        // It's safe to use product code.
        // array_search('POCT15', ['POCT15A', 'LSPOCT15']) => return FALSE.
        if (array_search($stock->product_code, array_column($items, 'product_code')) === FALSE) {
          $items[] = $stock;
        }
      }
    }
    return $items;
  }

  public function getTransfer($clause = [])
  {
    if ($rows = $this->getTransfers($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get stock transfers (NEW)
   * @param array $clause [ id, reference, from_warehouse_id, to_warehouse_id,
   *  payment_status, status, start_date, end_date, order ]
   */
  public function getTransfers($clause = [])
  {
    if (!empty($clause['reference'])) {
      $this->db->like('reference', $clause['reference'], 'none');
      unset($clause['reference']);
    }

    if (!empty($clause['payment_status'])) {
      $this->db->like('payment_status', $clause['payment_status'], 'none');
      unset($clause['payment_status']);
    }

    if (!empty($clause['status'])) {
      $this->db->like('status', $clause['status'], 'none');
      unset($clause['status']);
    }

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }

    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    if (!empty($clause['order']) && is_array($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    $q = $this->db->get_where('transfers', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getUnit($clause = [])
  {
    if ($rows = $this->getUnits($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getUnits($clause = [])
  {
    if (!empty($clause['code'])) {
      $this->db->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }
    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'none');
      unset($clause['name']);
    }

    $q = $this->db->get_where('units', $clause);
    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return [];
  }

  public function getUnitByCode($code)
  {
    $this->db->like('code', rd_unit($code), 'none');
    $q = $this->db->get('units');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUnitByID($id)
  {
    $q = $this->db->get_where('units', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get units by base unit id.
   * @param int $base_unit Base Unit ID
   * @example 1 getUnitsByBUID(1) // 1 = Lbr, return [Object Lbr, Object RIM]
   */
  public function getUnitsByBUID($base_unit)
  {
    $unit = $this->getUnitByID($base_unit);
    if ($unit) {
      $this->db
        ->select("id, code, name, base_unit, operator, operation_value")
        ->group_start()
        ->like('code', $unit->code)
        ->or_like('base_unit', $unit->code)
        ->group_end();
      $q = $this->db->get('units');
      if ($q->num_rows() > 0) {
        return $q->result();
      }
    }
    return [];
  }

  public function getUnitSuggestionsByBUID($base_unit)
  {
    if ($base_unit) {
      $unit = $this->getUnitByID($base_unit);
      $this->db
        ->select("id AS id, CONCAT(name, ' (', code, ')') AS text")
        ->group_start()
        ->like('code', $unit->code)
        ->or_like('base_unit', $unit->code)
        ->group_end()
        ->group_by('id')
        ->order_by('id', 'ASC');
      $q = $this->db->get('units');
      if ($q->num_rows() > 0) {
        return $q->result();
      }
    }
    return [];
  }

  public function getUser($id = NULL)
  {
    $this->db->select('users.*, groups.name AS group_name');
    $this->db->join('groups', 'groups.id = users.group_id', 'left');

    $q = $this->db->get_where('users', ['users.id' => $id]);

    if (!$q) {
      dbgprint($this->db->error()['message']);
    }
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserByID($id = 0)
  {
    return $this->getUser($id);
  }

  public function getUserByName($name)
  {
    if (empty($name)) return NULL;

    $this->db->like("fullname", $name, 'both');
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserByPhone($phone)
  {
    $this->db->like('phone', $phone, 'both');
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserByUsername($username)
  {
    $this->db->like('username', $username, 'none');
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserCustomerByID($user_id)
  {
    $this->db->where("JSON_UNQUOTE(JSON_EXTRACT(json_data, \"$.user_id\")) =", $user_id);
    $this->db->like('company', 'INDOPRINTING');
    $q = $this->db->get('customers');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserGroup($user_id = 0)
  {
    if (!$user_id) {
      $groupId = XSession::get('group_id');
    } else {
      $user = $this->getUserByID($user_id);
      $groupId = $user->group_id;
    }

    $q = $this->db->get_where('groups', ['id' => $groupId]);
    if ($q && $q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserGroupByName($name)
  {
    if (empty($name)) return FALSE;

    $this->db->like('name', $name, 'none');

    $q = $this->db->get('groups');
    if ($q && $q->num_rows()) {
      return $q->row();
    }
    return NULL;
  }

  public function getUserGroupID($user_id = 0)
  {
    $user = $this->getUser($user_id);
    return $user->group_id;
  }

  /**
   * Get user permissions by permission name globally.
   * @param string $permission_name Permission name
   * @param integer $user_id User ID
   */
  public function getUserPermission($permission_name, $user_id = 0)
  { // OBSOLETE. Use getGroupPermissions instead.
    if ($user_id) {
      $user = $this->getUserByID($user_id);
    } else {
      $user = $this->getUserByID(XSession::get('user_id'));
    }

    $perms = $this->getGroupPermissions($user->group_id, TRUE);

    $ret = (isset($perms[$permission_name]) ? $perms[$permission_name] : NULL);

    return $ret;
  }

  public function getUsers($clause = [])
  {
    $this->db->select('users.id AS id, users.*, groups.name AS group_name');
    $this->db->join('groups', 'groups.id = users.group_id', 'left');

    if (isset($clause['id'])) {
      $this->db->where('users.id', $clause['id']);
      unset($clause['id']);
    }

    $q = $this->db->get_where('users', $clause);

    if ($q && $q->num_rows() > 0) {
      return $q->result();
    }
    setLastError($this->db->error()['message']);
    return [];
  }

  public function getUserSuggestions($term, $limit = 10)
  {
    if (empty($term)) return [];
    $this->db->select("id, fullname AS text");
    if (!empty($term['id'])) {
      $this->db->where('id', $term['id']);
    } else {
      $this->db->where(" (id LIKE '%" . $term . "%' OR fullname LIKE '%" . $term .
        "%' OR email LIKE '%" . $term . "%' OR phone LIKE '%" .
        $term . "%') ");
    }
    $q = $this->db->get('users', $limit);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getWAJobs($clause = [])
  {
    $rows = [];

    if (!empty($clause['start_date'])) {
      $this->db->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $this->db->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }

    $q = $this->db->get_where('wa_job', $clause);

    if ($q && $q->num_rows()) {
      return $q->result();
    }
    return $rows;
  }

  public function getWarehouseByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('warehouses');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getWarehouseByID($id)
  {
    $q = $this->db->get_where('warehouses', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getWarehouseByName($name)
  {
    $this->db->like('name', $name, 'none');
    $q = $this->db->get('warehouses');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  /**
   * Get warehouse product.
   */
  public function getWarehouseProduct($product_id, $warehouse_id)
  {
    if ($rows = $this->getWarehouseProducts($product_id, $warehouse_id)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get warehouse products.
   */
  public function getWarehouseProducts($product_id, $warehouse_id = NULL)
  {
    if ($warehouse_id) {
      $this->db->where('warehouse_id', $warehouse_id);
    }
    $q = $this->db->get_where('warehouses_products', ['product_id' => $product_id]);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  public function getWarehouse($clause = [])
  {
    if ($row = $this->getWarehouses($clause)) {
      return $row[0];
    }
    return NULL;
  }

  public function getWarehouses($clause = [])
  {
    if (!empty($clause['code'])) {
      $this->db->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }
    if (!empty($clause['name'])) {
      $this->db->like('name', $clause['name'], 'none');
      unset($clause['name']);
    }
    if (!empty($clause['order'])) {
      $this->db->order_by($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    if (empty($clause['active'])) {
      $this->db->where('active', 1);
    }

    $q = $this->db->get_where('warehouses', $clause);
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return [];
  }

  /**
   * Increase current bank amount. See decreaseBankAmount.
   * @param int $bank_id Bank ID.
   * @param float $amount Amount to increase.
   */
  public function increaseBankAmount($bank_id, $amount)
  {
    $ci = $this;

    $result = $this->ridintek->mutex('bank')->on('lock', function () use ($ci, $bank_id, $amount) {
      $real_amount = filterDecimal($amount);
      $bank = $ci->getBankByID($bank_id);

      if ($bank) {
        if ($ci->updateBank($bank_id, ['amount' => $bank->amount + $real_amount])) {
          return TRUE;
        }
      }
      return FALSE;
    })->create()->close();

    return $result;
  }

  /**
   * THE ONLY FUNCTION TO INCREASE STOCK QUANTITY. See decreaseStockQuantity().
   * @param array [ date, *(adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id), saleitem_id,
   *  *product_id, cost, price, *warehouse_id, *quantity, adjustment_qty, spec, created_by ]
   */
  public function increaseStockQuantity($data)
  {
    $stockData = $data;
    $stockData['status'] = 'received';

    if ($this->addStockQuantity($stockData)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Increase current warehouse stock quantity. See decreaseWarehouseQty.
   * @param int $product_id Product ID.
   * @param int $warehouse_id Warehouse ID.
   * @param float $qty Quantity to increase.
   */
  public function increaseWarehouseQty($product_id, $warehouse_id, $qty)
  {
    $quantity = filterDecimal($qty);
    $whp = $this->getWarehouseProduct($product_id, $warehouse_id);

    if ($whp) {
      if ($this->updateWarehouseProduct(['id' => $whp->id], ['quantity' => $whp->quantity + $quantity])) {
        return TRUE;
      }
    } else {
      if ($this->addWarehouseProduct([
        'product_id' => $product_id,
        'warehouse_id' => $warehouse_id,
        'quantity' => $quantity
      ])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function notificationActivate($id)
  {
    if ($this->db->update('notifications', ['active' => 1], ['id' => $id])) {
      return TRUE;
    }
    return FALSE;
  }

  public function notificationDeactivate($id)
  {
    if ($this->db->update('notifications', ['active' => 0], ['id' => $id])) {
      return TRUE;
    }
    return FALSE;
  }

  public function resetAll()
  {
    $tables = [
      'addresses', 'adjustments', 'banks', 'bank_histories', 'bank_mutations', 'billers', 'categories', 'combo_items',
      'costing', 'customers', 'deposits', 'expenses', 'expense_categories', 'incomes', 'income_categories',
      'payments', 'payment_validations', 'products', 'product_histories', 'product_prices', 'purchases', 'purchase_histories',
      'sales', 'sale_items',
      'stock_counts', 'stock_count_items', 'suppliers', 'transfers', 'warehouses_products'
    ];

    foreach ($tables as $table) {
      if (!$this->db->truncate($table)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Sync bank amount (balance).
   */
  public function syncBankAmount($bank_id = NULL)
  {
    $banks = [];

    if ($bank_id) {
      $banks[] = $this->getBankByID($bank_id);
    } else {
      $banks = $this->getAllBanks();
    }

    if ($banks) {
      foreach ($banks as $bank) {
        $bank->balance = $this->getBankBalanceByID($bank->id);
        $this->updateBank($bank->id, ['amount' => $bank->balance]);
      }

      return TRUE;
    }
    return FALSE;
  }

  public function syncBankReconciliations()
  {
    $this->db
      ->select('banks.number, banks.name, banks.holder, banks.type')
      ->where('active', 1)
      ->where("banks.number <> '2222004005'")
      ->group_start()
      ->like('banks.type', 'Transfer')
      ->or_like('banks.type', 'EDC')
      ->group_end()
      ->group_by('banks.number');

    $q = $this->db->get('banks');

    if ($q && $q->num_rows() > 0) {

      $curl = curl_init(base_url('api/v1/mutasibank/accounts'));

      curl_setopt_array($curl, [
        CURLOPT_HEADER => FALSE,
        CURLOPT_RETURNTRANSFER => TRUE
      ]);

      $data = curl_exec($curl);

      if (!$data) {
        return FALSE;
      }

      $res = json_decode($data);

      if (!$res) {
        setLastError('Failed get data from api mutasibank accounts.');
        return FALSE;
      }

      foreach ($q->result() as $row) { // Grouped by bank number.
        $banks = $this->getBanks();
        $mutasi_bank = NULL;
        $totalBalance = 0;

        foreach ($banks as $bank) { // Collect balance.
          if (strcmp($row->number, $bank->number) === 0) {
            $totalBalance += $bank->amount;
          }
        }

        foreach ($res as $mb) {
          if (strcmp($mb->account_no, $row->number) === 0) {
            $mutasi_bank = $mb;
            break;
          }
        }

        $recon = $this->getBankReconciliationByAccountNo($row->number);

        if ($recon) { // If exist, then update.
          $recon_data = [
            'erp_acc_name' => $row->holder,
            'account_no'   => $row->number,
            'amount_erp'   => $totalBalance
          ];

          if ($mutasi_bank) {
            $recon_data['mb_acc_name']    = $mutasi_bank->account_name;
            $recon_data['mb_bank_name']   = $mutasi_bank->bank;
            $recon_data['amount_mb']      = $mutasi_bank->balance;
            $recon_data['last_sync_date'] = $mutasi_bank->last_bot_activity;
          }

          $this->updateBankReconciliation($recon->id, $recon_data);
        } else { // If not exist, insert new.
          $recon_data = [
            'erp_acc_name' => $row->holder,
            'account_no'   => $row->number,
            'amount_erp'   => $totalBalance
          ];

          if ($mutasi_bank) {
            $recon_data['mb_acc_name']    = $mutasi_bank->account_name;
            $recon_data['mb_bank_name']   = $mutasi_bank->bank;
            $recon_data['amount_mb']      = $mutasi_bank->balance;
            $recon_data['last_sync_date'] = $mutasi_bank->last_bot_activity;
          }

          $this->db->insert('bank_reconciliations', $recon_data);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  public function syncPaymentValidations()
  {
    $synced = FALSE;

    $pending_payments = $this->getPaymentValidationsByStatus('pending');
    if ($pending_payments) {
      foreach ($pending_payments as $pp) {
        if (time() > strtotime($pp->expired_date)) { // Expired
          $this->updatePaymentValidation($pp->id, [
            'status' => 'expired'
          ]);
          if ($pp->sale_id) {
            $this->updateSale($pp->sale_id, [
              'payment_status' => 'expired'
            ]);
            $this->syncSales(['sale_id' => $pp->sale_id]);
          }
          if ($pp->mutation_id) {
            $this->updateBankMutation($pp->mutation_id, [
              'status' => 'expired'
            ]);
          }
          $synced = TRUE;
        }
      }
    }

    /* Set payment_status to pending or partial if sale payment_status == waiting_transfer but no payment validation. */
    $waiting_transfers = $this->getSalesByPaymentStatus('waiting_transfer');
    if ($waiting_transfers) {
      foreach ($waiting_transfers as $wt) {
        $pv = $this->getPaymentValidationBySaleID($wt->id);
        if (!$pv && ($wt->paid == 0)) {
          $this->updateSale($wt->id, ['payment_status' => 'pending']);
        } else if (!$pv && ($wt->paid > 0 && $wt->paid < $wt->grand_total)) {
          $this->updateSale($wt->id, ['payment_status' => 'partial']);
        }

        $this->syncSales(['sale_id' => $wt->id]);
      }
    }

    return $synced;
  }

  /**
   * Sync product quantity from stock to warehouse product.
   */
  public function syncProductQty($product_id, $warehouse_id = NULL)
  {
    $all_warehouses = FALSE;
    $total_quantity = 0;
    $warehouses = [];

    if ($warehouse_id) {
      $warehouses[] = $this->getWarehouseByID($warehouse_id);
    } else {
      $all_warehouses = TRUE;
      $warehouses = $this->getWarehouses();
    }

    $product = $this->getProductByID($product_id);

    if ($product->type == 'combo') return FALSE; // Sync only for service and standard.

    foreach ($warehouses as $warehouse) {
      $wh_balance_qty = $this->getStockQuantity($product_id, $warehouse->id); // Warehouse balance (Selected warehouse).
      $total_quantity += $wh_balance_qty;

      if ($this->getWarehouseProduct($product_id, $warehouse->id)) {
        $clause = ['product_id' => $product_id, 'warehouse_id' => $warehouse->id];
        $this->updateWarehouseProduct($clause, ['quantity' => $wh_balance_qty]);
      } else {
        if (!$wh_balance_qty) $wh_balance_qty = 0; // Make sure to zero if balance qty not present.
        $product = $this->getProductByID($product_id);
        if ($product) {
          $whp_data = [
            'product_id' => $product_id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $wh_balance_qty
          ];
          $this->addWarehouseProduct($whp_data);
        } else {
          return FALSE;
        }
      }
    }

    if ($all_warehouses) {
      $this->db->update('products', ['quantity' => $total_quantity], ['id' => $product_id]);
    }
    return TRUE;
  }

  public function syncProducts()
  {
    $products = $this->getProducts();
    $success = 0;

    foreach ($products as $product) {
      if ($this->syncProductQty($product->id)) {
        $success++;
      }
    }

    return $success;
  }

  /**
   * Sync product reports. Currently only ASSET and EQUIPMENT is supported.
   *
   * @param int $productId Product ID to sync.
   */
  public function syncProductReports($productId = NULL)
  {
    $success = 0;
    $products = [];

    if ($productId) {
      $products[] = $this->getProductByID($productId);
    } else {
      $category1 = $this->getProductCategoryByCode('AST');
      $category2 = $this->getProductCategoryByCode('EQUIP');

      $this->db
        ->group_start()
        ->where('products.category_id', $category1->id)
        ->or_where('products.category_id', $category2->id)
        ->group_end();

      $q = $this->db->get('products');

      if ($q && $q->num_rows() > 0) {
        $products = $q->result();
      }
    }

    foreach ($products as $product) {
      $reports = $this->getProductReports([
        'product_id' => $product->id,
        'order_by' => ['created_at', 'DESC'],
        'limit' => 1
      ]);

      if ($reports) {
        $report = $reports[0];

        $this->updateProducts([[
          'product_id' => $report->product_id,
          'condition' => $report->condition,
          'updated_at' => $report->created_at
        ]]);

        if (!$success) $success = 1;
      }

      unset($reports);
    }

    unset($products);

    return ($success ? TRUE : FALSE);
  }

  /**
   * Synchronizing 'products' safety_stock based on stock transfer.
   * @param int $product_id Product ID.
   * @param array $options [ *start_date, *end_date ]
   * - start_date: '2021-02-01'
   * - end_date: '2021-03-16'
   */
  public function syncProductSafetyStock($product_id, $options)
  {
    if ($product_id && isset($options['start_date']) && isset($options['end_date'])) {
      $safety_stock = 0;
      $product = $this->getProductByID($product_id);
      $warehouse = $this->getWarehouseByCode('LUC');

      // Get all sent item quantity from lucretia.
      $this->db->select('stocks.date, stocks.quantity');

      if ($product->iuse_type == 'sparepart') {
        $this->db->where('stocks.internal_use_id IS NOT NULL');
      } else {
        $this->db->where('stocks.transfer_id IS NOT NULL');
      }

      $this->db->where('stocks.product_id', $product_id)
        ->like('stocks.status', 'sent');

      // Time periode to get sent item quantity.
      if (isset($options['start_date']) && isset($options['end_date'])) {
        $this->db
          ->where("stocks.date BETWEEN '{$options['start_date']} 00:00:00' AND '{$options['end_date']} 23:59:59'");
      }

      $q = $this->db->get('stocks');

      if ($q->num_rows() > 0) {
        $stocks    = $q->result();
        $supplier  = $this->getSupplierByID($product->supplier_id);
        $total_qty = 0;

        if ($stocks) {
          foreach ($stocks as $stock) {
            $total_qty = filterQuantity($total_qty + $stock->quantity);
          }
        }

        if ($supplier) {
          $stock_data = [
            'product_id'   => $product_id,
            'warehouse_id' => $warehouse->id,
            'start_date'   => $options['start_date'],
            'end_date'     => $options['end_date'],
            'days'         => $options['days']
          ];

          $sent_qty       = $total_qty; // Jumlah seluruh stock yg dikirim ke outlet.
          $supplier_json  = json_decode($supplier->json_data);
          $total_days     = $options['days'];
          // $total_days     = getActiveStockDays($stock_data);
          $cycle_purchase = filterDecimal($supplier_json->cycle_purchase);
          $delivery_time  = filterDecimal($supplier_json->delivery_time);
          // Total quantity / $total_days
          $daily_qty = floatval($sent_qty) / floatval($total_days);
          // Get safety stock for purchase.
          $safety_stock = getSafetyStock($daily_qty, ($cycle_purchase + $delivery_time), $product->safety_stock_ratio);
        }
      }

      // if ($product->code == 'FFC34') { // DEBUG ONLY.
      //   sendJSON([
      //     'sent_qty'       => $sent_qty,
      //     'total_days'     => $total_days,
      //     'cycle_purchase' => $cycle_purchase,
      //     'delivery_time'  => $delivery_time,
      //     'daily_qty'      => $daily_qty,
      //     'safety_stock'   => $safety_stock
      //   ]);
      // }

      $this->db->trans_start();
      $this->db->update('products', ['safety_stock' => $safety_stock], ['id' => $product_id]);
      $this->db->trans_complete();
      if ($this->db->trans_status() !== FALSE) {
        // Update Warehouse Product Safety Stock for Lucretia.
        $this->updateWarehouseProduct([
          'product_id' => $product_id,
          'warehouse_id' => $warehouse->id
        ], [
          'safety_stock' => $safety_stock
        ]);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Sync purchase safety stock.
   * @param int $product_id Product ID.
   */
  public function syncPurchaseSafetyStock($product_id = NULL)
  {
    $all_items = [];

    if ($product_id) {
      $product = $this->getProductByID($product_id);
      $all_items[] = $product;
    } else {
      $all_items = $this->getProducts(['active' => 1, 'type' => 'standard']);
    }

    if ($all_items) {
      $count = 0;
      $settingsJSON = $this->getSettingsJSON();
      $opt = getPastMonthPeriod($settingsJSON->safety_stock_period);

      foreach ($all_items as $item) {
        $this->syncProductSafetyStock($item->id, $opt);
        $count++;
      }
      return $count;
    }
    return NULL;
  }

  /**
   * Sync Quantity
   * @param array $data Array of data to sync.
   * - [ adjustment_id, internal_use_id, product_id, purchase_id, sale_id, transfer_id ]
   * - warehouse_id (Optional. Used by product_id only)
   */
  public function syncQuantity($data)
  {
    if (isset($data['adjustment_id'])) {
      $adjustment = $this->getStockAdjustmentByID($data['adjustment_id']);
      if ($adjustment) {
        $adjustment_items = $this->getStockAdjustmentItems($adjustment->id);
        if ($adjustment_items) {
          foreach ($adjustment_items as $adjustment_item) {
            $this->syncProductQty($adjustment_item->product_id, $adjustment_item->warehouse_id);
          }
        }
        return TRUE;
      }
    } else if (isset($data['internal_use_id'])) { // Internal Use
      $internal = $this->getStockInternalUseByID($data['internal_use_id']);
      if ($internal) {
        $internal_items = $this->getStockInternalUseItems($internal->id);
        if ($internal_items) {
          foreach ($internal_items as $internal_item) {
            $this->syncProductQty($internal_item->product_id, $internal_item->warehouse_id);
          }
          return TRUE;
        }
      }
    } else if (isset($data['product_id'])) { // Individual item.
      $product = $this->getProductByID($data['product_id']);
      if ($product) {
        $warehouse_id = ($data['warehouse_id'] ?? NULL);
        $this->syncProductQty($product->id, $warehouse_id);
        return TRUE;
      }
    } else if (isset($data['purchase_id'])) { // Purchase
      $purchase = $this->getStockPurchaseByID($data['purchase_id']);
      if ($purchase) {
        $purchase_items = $this->getStockPurchaseItems($purchase->id);
        if ($purchase_items) {
          foreach ($purchase_items as $purchase_item) {
            $this->syncProductQty($purchase_item->product_id, $purchase_item->warehouse_id);
          }
          return TRUE;
        }
      }
    } else if (isset($data['sale_id'])) { // Sale
      $sale = $this->getSaleByID($data['sale_id']);
      if ($sale) {
        $sale_items = $this->getSaleItemsBySaleID($sale->id);
        if ($sale_items) {
          foreach ($sale_items as $sale_item) {
            if ($sale_item->product_type == 'combo') {
              $combo_items = $this->getProductComboItems($sale_item->product_id);
              if ($combo_items) {
                foreach ($combo_items as $combo_item) {
                  if ($combo_item->type == 'standard' || $combo_item->type == 'service') {
                    $this->syncProductQty($combo_item->id, $sale->warehouse_id);
                  }
                }
              }
            } else if ($sale_item->product_type == 'service') {
              $this->syncProductQty($sale_item->product_id, $sale->warehouse_id);
            }
          }
          return TRUE;
        }
      }
    } else if (isset($data['transfer_id'])) { // Transfer
      $transfer = ProductTransfer::getRow(['id' => $data['transfer_id']]);
      if ($transfer) {
        $transfer_items = ProductTransferItem::get(['transfer_id' => $transfer->id]);
        if ($transfer_items) {
          foreach ($transfer_items as $transfer_item) {
            $this->syncProductQty($transfer_item->product_id, $transfer->warehouse_id_from);
            $this->syncProductQty($transfer_item->product_id, $transfer->warehouse_id_to);
          }
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function syncSaleQuantity($sale_id)
  {
    if ($sale_id) {
      $sale_items = $this->getSaleItemsBySaleID($sale_id);
      if ($sale_items) {
        foreach ($sale_items as $item) {
          if ($item->product_type == 'combo') { // Since selling item combo or service.
            $combo_items = $this->getProductComboItems($item->product_id, $item->warehouse_id);
            foreach ($combo_items as $combo_item) {
              if ($combo_item->type == 'standard') {
                $this->syncProductQty($combo_item->id, $item->warehouse_id);
              }
            }
          } else if ($item->product_type == 'service') {
            $this->syncProductQty($item->product_id, $item->warehouse_id);
          } else if ($item->product_type == 'standard') {
            $this->syncProductQty($item->product_id, $item->warehouse_id);
          }
        }

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * NEW 2021-08-16
   */
  public function syncSales($clause = [])
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
        die("syncSales: unknown data type '" . gettype($clause['sale_id']) . "'");
      }
    } else { // Default if sale_id is NULL.
      $sales = $this->getSales();
    }

    if (empty($sales)) return FALSE;

    foreach ($sales as $sale) {
      if (empty($sale->json_data)) {
        die("Got sale id {$sale->id} for invalid json_data");
      }

      $saleJS = getJSON($sale->json_data ?? '{}');
      $saleData = [];

      if (!$saleJS) {
        continue;
      }

      $isDuePayment      = isDueDate($saleJS->payment_due_date ?? $sale->due_date);
      $isW2PUser         = isW2PUser($sale->created_by); // Is sale created_by user is w2p?
      $isSpecialCustomer = isSpecialCustomer($sale->customer_id); // Special customer (Privilege, TOP)
      $payments          = $this->getPayments(['sale_id' => $sale->id]);
      $paymentValidation = $this->getPaymentValidationBySaleID($sale->id);
      $saleItems         = $this->getSaleItemsBySaleID($sale->id);

      if (empty($saleItems)) {
        continue;
      }

      $completedItems = 0;
      $deliveredItems = 0;
      $finishedItems  = 0;
      $grandTotal     = 0;
      $hasPartial     = FALSE;
      $totalSaleItems = 0;
      $saleStatus     = $sale->status;

      foreach ($saleItems as $saleItem) {
        $saleItemData = [];
        $saleItemJS = getJSON($saleItem->json_data);
        $saleItemStatus = $saleItemJS->status;
        $totalSaleItems++;
        $grandTotal += round($saleItem->price * $saleItem->quantity);
        $isItemFinished = ($saleItem->quantity == $saleItem->finished_qty ? TRUE : FALSE);
        $isItemFinishedPartial = ($saleItem->finished_qty > 0 && $saleItem->quantity > $saleItem->finished_qty ? TRUE : FALSE);

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
          $hasPartial = TRUE;
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

        $saleItemData['status'] = $saleItemStatus;

        $this->updateSaleItem($saleItem->id, $saleItemData);
      }

      $grandTotal = round($grandTotal - $sale->discount);

      $saleData['grand_total'] = $grandTotal;

      $isSaleCompleted        = ($completedItems == $totalSaleItems ? TRUE : FALSE);
      $isSaleCompletedPartial = (($completedItems > 0 && $completedItems < $totalSaleItems) || $hasPartial ? TRUE : FALSE);
      $isSaleDelivered        = ($deliveredItems == $totalSaleItems ? TRUE : FALSE);
      $isSaleFinished         = ($finishedItems == $totalSaleItems ? TRUE : FALSE);

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

      $isPaid        = FALSE;
      $isPaidPartial = FALSE;
      $totalPaid     = 0;
      $balance       = 0;
      $paymentStatus = $sale->payment_status;

      if ($payments) {
        foreach ($payments as $payment) {
          $totalPaid += $payment->amount;
        }

        $balance = ($grandTotal - $totalPaid);

        $isPaid        = ($balance == 0 ? TRUE : FALSE);
        $isPaidPartial = ($balance > 0  ? TRUE : FALSE);

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
        $isPVPending  = ($paymentValidation->status == 'pending'  ? TRUE : FALSE);
        $isPVExpired  = ($paymentValidation->status == 'expired'  ? TRUE : FALSE);
        // $isPVVerified = ($paymentValidation->status == 'verified' ? TRUE : FALSE);

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

      $saleData['paid']           = $totalPaid;
      $saleData['balance']        = $balance;
      $saleData['status']         = $saleStatus;
      $saleData['payment_status'] = $paymentStatus;

      $this->updateSale($sale->id, $saleData);

      // If any change of sale status or payment status for W2P sale then dispatch W2P sale info.
      if (isset($saleJS->source) && $saleJS->source == 'W2P') {
        if ($sale->status != $saleStatus || $sale->payment_status != $paymentStatus) {
          dispatchW2PSale($sale->id);
        }
      }
    }
  }

  public function syncStockInternalUse($iuseId)
  {
    $iuse = $this->getStockInternalUseByID($iuseId);
    $items = $this->getStockInternalUseItems($iuseId);

    $grandTotal = 0;

    foreach ($items as $item) {
      $itemPrice = roundDecimal($item->price * $item->quantity);

      $grandTotal += $itemPrice;
    }

    $iuseData = [
      'grand_total' => $grandTotal
    ];

    $this->updateStockInternalUse($iuseId, $iuseData);
  }

  public function syncStockOpnames($opname_id = NULL)
  {
    $opnames = [];

    if ($opname_id) {
      if (is_array($opname_id)) {
        foreach ($opname_id as $opid) {
          $opnames[] = $this->getStockOpnameByID($opid);
        }
      } else {
        $opnames = $this->getStockOpnames(['id' => $opname_id]);
      }
    } else {
      $opnames = $this->getStockOpnames();
    }

    if ($opnames) {
      foreach ($opnames as $opid) {
        $soItems = $this->getStockOpnameItems($opid);

        if ($soItems) {
          foreach ($soItems as $soItem) {
            $product = $this->getProductByID($soItem->product_id);
          }
        }
      }
    }
  }

  public function syncStockPurchase($purchase_id = NULL)
  {
    if ($purchase_id) {
      $purchases[] = $this->getStockPurchaseByID($purchase_id);
    } else {
      $purchases = $this->getAllStockPurchases();
    }

    foreach ($purchases as $purchase) {
      $purchase_items = $this->getStockPurchaseItems($purchase->id);
      $payments = $this->getPayments(['purchase_id' => $purchase->id]);
      $paid = 0;
      $grandTotal = 0;
      $receivedValue = 0;

      /**
       *# DO NOT USING Price.
       *! Cost is latest price from purchase.
       */

      if ($purchase_items) {
        foreach ($purchase_items as $item) {
          $cost = ($item->cost > 0 ? $item->cost : $item->price);
          $receivedValue += round($item->quantity * $cost);
          $grandTotal += round($item->purchased_qty * $cost);
        }
      } else {
        die('WHY NO PURCHASE ITEMS?');
      }

      if ($payments) {
        foreach ($payments as $payment) {
          if ($payment->status == 'paid') $paid += $payment->amount;
        }
      }

      $purchase_data = [
        'grand_total' => $grandTotal,
        'received_value' => $receivedValue,
        'paid' => $paid
      ];

      if ($purchase->payment_status != 'need_approval' && $purchase->payment_status != 'approved') {
        if ($paid > 0 && ($receivedValue + $purchase->discount) == $paid) {
          $purchase_data['payment_status'] = 'paid';
        } else if ($paid > 0 && ($receivedValue + $purchase->discount) > $paid) {
          $purchase_data['payment_status'] = 'partial';
        }
      }

      // (0 - 12000) + 10000 = -2000]
      if (
        $purchase->status == 'received' ||
        $purchase->status == 'received_partial' ||
        $purchase->payment_status == 'paid' ||
        $purchase->payment_status == 'partial'
      ) {
        // Balance must be minus. Ex. -5000
        $purchase_data['balance'] = (0 - $receivedValue) + $paid - $purchase->discount;
      }

      $this->db->update('purchases', $purchase_data, ['id' => $purchase->id]);
    }
  }

  /**
   * Sync transfer safety stock.
   * @param int $product_id Product ID.
   * @param int $warehouse_id Warehouse ID (Optional).
   */
  public function syncTransferSafetyStock($product_id = NULL, $warehouse_id = NULL)
  {
    $all_items  = [];
    $warehouses = [];

    if ($product_id) {
      $product = $this->getProductByID($product_id);
      $all_items[] = $product;
    } else {
      $all_items = $this->getProducts(['active' => 1, 'type' => 'standard']);
    }

    $settingsJSON = $this->getSettingsJSON();
    $opt = getPastMonthPeriod($settingsJSON->safety_stock_period);

    if ($warehouse_id) {
      $warehouse = $this->getWarehouseByID($warehouse_id);
      $warehouses[] = $warehouse;
    } else {
      $warehouses = $this->getAllWarehouses();
    }

    if ($all_items && $warehouses) {
      $count = 0;
      foreach ($all_items as $item) {
        // Ignore for sparepart. Decrease sparepart by Internal Use only.
        if (strcasecmp($item->iuse_type, 'sparepart') === 0) continue;

        foreach ($warehouses as $warehouse) {
          if (strcasecmp($warehouse->code, 'ADV') === 0) continue; // Ignore Advertising.
          if (strcasecmp($warehouse->code, 'LUC') === 0) continue; // Ignore Lucretia.

          $this->syncWarehouseProductSafetyStock($item->id, $warehouse->id, $opt);
          $count++;
        }
      }
      return $count;
    }
    return NULL;
  }

  /**
   * Sync warehouse product safety stock based on sold items.
   * @param int $product_id Product ID.
   * @param int $warehouse_id Warehouse ID.
   * @param array $options [ *start_date, *end_date, *days ]
   */
  public function syncWarehouseProductSafetyStock($product_id, $warehouse_id, $options)
  {
    $warehouse = $this->getWarehouseByID($warehouse_id);

    if ($product_id && $warehouse && isset($options['start_date']) && isset($options['end_date'])) {
      $safety_stock = 0;
      $warehouse_js = json_decode($warehouse->json_data);

      // Get all sent items from warehouse.
      $this->db->select('stocks.date, stocks.quantity');

      $this->db->where('stocks.adjustment_id IS NULL') // Adjustment not included.
        ->where('stocks.warehouse_id', $warehouse_id)
        ->where('stocks.product_id', $product_id)
        ->like('stocks.product_type', 'standard', 'none') // No service.
        ->like('stocks.status', 'sent');

      // Time periode to get sent item quantity.
      if (isset($options['start_date']) && isset($options['end_date'])) {
        $this->db
          ->where("stocks.date BETWEEN '{$options['start_date']} 00:00:00' AND '{$options['end_date']} 23:59:59'");
      }

      $q = $this->db->get('stocks');

      if ($q->num_rows() > 0) {
        $stocks = $q->result();
        $total_qty = 0;

        if ($stocks) {
          foreach ($stocks as $stock) {
            $total_qty = filterQuantity($total_qty + $stock->quantity);
          }
        }

        if ($warehouse_js) {
          $stock_data = [
            'product_id'   => $product_id,
            'warehouse_id' => $warehouse_id,
            'start_date'   => $options['start_date'],
            'end_date'     => $options['end_date'],
            'days'         => $options['days']
          ];

          $sent_qty       = round($total_qty); // Jumlah seluruh stock yg digunakan outlet.
          $total_days     = $options['days'];
          // $total_days     = getActiveStockDays($stock_data);
          $cycle_transfer = filterDecimal($warehouse_js->cycle_transfer);
          $delivery_time  = filterDecimal($warehouse_js->delivery_time);
          // Total quantity / $total_days
          $daily_qty = floatval($sent_qty) / floatval($total_days);
          // Get safety stock for transfer.
          $safety_stock = getSafetyStock($daily_qty, ($cycle_transfer + $delivery_time), 1);
        }

        // $product = $this->getProductByID($product_id);
        // if ($product->code == 'PL35') {
        //   sendJSON([
        //     'sent_qty' => $sent_qty,
        //     'total_days' => $total_days,
        //     'cycle_transfer' => $cycle_transfer,
        //     'delivery_time' => $delivery_time,
        //     'daily_qty' => $daily_qty,
        //     'safety_stock' => $safety_stock
        //   ]);
        // }
      }

      $clause = ['product_id' => $product_id, 'warehouse_id' => $warehouse_id];
      if ($this->updateWarehouseProduct($clause, ['safety_stock' => $safety_stock])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE BANK.
   */
  public function updateBank($bank_id, $data)
  {
    $bank_data = [];

    if (!empty($data['code']))      $bank_data['code']      = $data['code'];
    if (!empty($data['name']))      $bank_data['name']      = $data['name'];
    if (!empty($data['biller_id'])) $bank_data['biller_id'] = $data['biller_id'];
    if (!empty($data['number']))    $bank_data['number']    = $data['number'];
    if (!empty($data['holder']))    $bank_data['holder']    = $data['holder'];
    if (isset($data['amount']))     $bank_data['amount']    = $data['amount'];
    if (!empty($data['type']))      $bank_data['type']      = $data['type'];
    if (!empty($data['bic']))       $bank_data['bic']       = $data['bic'];
    if (!empty($data['active']))    $bank_data['active']    = $data['active'];

    $this->db->trans_start();
    $this->db->update('banks', $bank_data, ['id' => $bank_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE BANK MUTATION.
   * @param int $mutation_id Bank Mutation ID.
   * @param array $data [ date, from_bank_id, to_bank_id, paid_by(Transfer), amount, note, created_by,
   *  updated_by, status, attachment ]
   */
  public function updateBankMutation($mutation_id, $data)
  {
    if (!empty($data)) {
      $mutation_data = [];

      if (!empty($data['date'])) $mutation_data['date'] = $data['date'];

      if (!empty($data['from_bank_id'])) {
        $bank = $this->getBankByID($data['from_bank_id']);
        $mutation_data['from_bank_id']   = $bank->id;
        $mutation_data['from_bank_name'] = $bank->name;
        unset($bank);
      }

      if (!empty($data['to_bank_id'])) {
        $bank = $this->getBankByID($data['to_bank_id']);
        $mutation_data['to_bank_id']   = $bank->id;
        $mutation_data['to_bank_name'] = $bank->name;
        unset($bank);
      }

      if (!empty($data['note']))          $mutation_data['note']          = $data['note'];
      if (isset($data['amount']))         $mutation_data['amount']        = $data['amount'];
      if (!empty($data['created_by']))    $mutation_data['created_by']    = $data['created_by'];
      if (!empty($data['updated_by']))    $mutation_data['updated_by']    = $data['updated_by'];
      if (!empty($data['paid_by']))       $mutation_data['paid_by']       = $data['paid_by'];
      if (!empty($data['biller_id']))     $mutation_data['biller_id']     = $data['biller_id'];
      if (!empty($data['status']))        $mutation_data['status']        = $data['status'];
      if (!empty($data['attachment_id'])) $mutation_data['attachment_id'] = $data['attachment_id'];

      $this->db->trans_start();
      $this->db->update('bank_mutations', $mutation_data, ['id' => $mutation_id]);
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        $payments = $this->getPayments(['mutation_id' => $mutation_id]);

        if ($payments) {
          foreach ($payments as $payment) {
            $payment_data = [];

            if (isset($data['amount']))     $payment_data['amount']     = $data['amount'];
            if (isset($data['created_by'])) $payment_data['created_by'] = $data['created_by'];
            if (isset($data['updated_by'])) $payment_data['updated_by'] = $data['updated_by'];

            if (!empty($data['from_bank_id']) && $payment->type == 'sent') {
              $payment_data['bank_id'] = $data['from_bank_id'];
            }

            if (!empty($data['to_bank_id']) && $payment->type == 'received') {
              $payment_data['bank_id'] = $data['to_bank_id'];
            }

            $this->updatePayment($payment->id, $payment_data);
          }
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateBankReconciliation($id, $data)
  {
    $this->db->trans_start();
    $this->db->update('bank_reconciliations', $data, ['id' => $id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateBiller($id, $data)
  {
    if (!empty($data)) {
      $this->db->trans_start();
      $this->db->update('billers', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateCalendar($calendarId, $data)
  {
    $data = setUpdatedBy($data);

    if ($this->db->update('calendar', $data, ['id' => $calendarId])) {
      return $this->db->insert_id();
    }
    return FALSE;
  }

  public function updateCustomer($id, $data)
  {
    if (!empty($data)) {
      $this->db->trans_start();
      $this->db->update('customers', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateCustomerAddress($address_id, $data)
  {
    if ($this->db->update('addresses', $data, ['id' => $address_id])) {
      return true;
    }
    return false;
  }

  public function updateCustomerDeposit($id, $data, $cdata)
  {
    if ($this->db->update('deposits', $data, ['id' => $id]) && $this->db->update('customers', $cdata, ['id' => $data['customer_id']])) {
      return true;
    }
    return false;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE EXPENSE.
   *
   * @param int $expense_id Expense ID.
   * @param array $data [ ]
   */
  public function updateExpense($expense_id, $data)
  {
    if (!empty($data)) {
      $oldExpense = $this->getExpenseByID($expense_id);

      if (isset($data['bank_id'])) {
        $bank = Bank::getRow(['id' => $data['bank_id']]);

        $data['bank'] = $bank->code;
      }

      if (isset($data['biller_id'])) {
        $biller = Biller::getRow(['id' => $data['biller_id']]);

        $data['biller'] = $biller->code;
      }

      $this->db->trans_start();
      $this->db->update('expenses', $data, ['id' => $expense_id]);
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        $expense = $this->getExpenseByID($expense_id);
        $payments = $this->getPayments(['expense_id' => $expense_id]);

        if ($payments) { // Update payments too.
          $paymentData = [
            'amount' => $expense->amount,
            'bank_id' => $expense->bank_id,
            'note' => $expense->note
          ];

          foreach ($payments as $payment) {
            $this->updatePayment($payment->id, $paymentData);
          }
        }

        if (
          $expense->status == 'approved' &&
          $oldExpense->payment_status == 'pending' &&
          $expense->payment_status == 'paid'
        ) {
          $bank = $this->getBankByID($expense->bank_id);

          Payment::add([
            'expense_id' => $expense_id,
            'bank_id'    => $expense->bank_id,
            'method'     => $bank->type,
            'amount'     => $expense->amount,
            'created_by' => $expense->created_by,
            'type'       => 'sent',
            'note'       => ($data['note'] ?? $expense->note)
          ]);

          $this->db->update('expenses', ['payment_date' => date('Y-m-d H:i:s')], ['id' => $expense_id]);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updateExpenseCategory($category_id, $data)
  {
    if ($this->db->update('expense_categories', $data, ['id' => $category_id])) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateHoliday($id, $data)
  {
    $this->db->update('holiday', $data, ['id' => $id]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateIncome($income_id, $data)
  {
    $this->db->trans_start();
    $this->db->update('incomes', $data, ['id' => $income_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      $payments = $this->getPayments(['income_id' => $income_id]);

      if ($payments) {
        $payment = $payments[0];

        $payment_data = [
          'date'       => $data['date'],
          'income_id'  => $income_id,
          'bank_id'    => $data['bank_id'],
          'method'     => 'Transfer', // Diganti jika ada opsi.
          'amount'     => $data['amount'],
          'type'       => 'received',
          'note'       => $data['note']
        ];

        if ($this->updatePayment($payment->id, $payment_data)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  public function updateIncomeCategory($category_id, $data)
  {
    if ($this->db->update('income_categories', $data, ['id' => $category_id])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update job.
   * @param int $jobId Job ID.
   * @param array $data [ controller, method, param, result, status ]
   */
  public function updateJob($jobId, $data)
  {
    $this->db->update('jobs', $data, ['id' => $jobId]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE PAYMENT.
   * @param int $id Payment ID.
   * @param array $data [ date, expense_id, income_id, mutation_id, purchase_id, sale_id, transfer_id,
   *  reference, bank_id, method, amount, created_by, updated_by, attachment, status, type, note ]
   */
  public function updatePayment($id, $data)
  {
    if (!empty($data)) {
      $opayment = $this->getPaymentByID($id);

      $new_bank_id = ($data['bank_id'] ?? $opayment->bank_id);
      $old_bank_id = $opayment->bank_id;

      $isBankDifferent = ($new_bank_id == $old_bank_id ? FALSE : TRUE);

      $payment_data = [];

      if (!empty($data['date']))          $payment_data['date']           = $data['date'];
      if (!empty($data['expense_id']))    $payment_data['expense_id']     = $data['expense_id'];
      if (!empty($data['income_id']))     $payment_data['income_id']      = $data['income_id'];
      if (!empty($data['mutation_id']))   $payment_data['mutation_id']    = $data['mutation_id'];
      if (!empty($data['purchase_id']))   $payment_data['purchase_id']    = $data['purchase_id'];
      if (!empty($data['sale_id']))       $payment_data['sale_id']        = $data['sale_id'];
      if (!empty($data['transfer_id']))   $payment_data['transfer_id']    = $data['transfer_id'];
      if (!empty($data['reference']))     $payment_data['reference']      = $data['reference'];
      if (!empty($data['bank_id']))       $payment_data['bank_id']        = $data['bank_id'];
      if (!empty($data['biller_id']))     $payment_data['biller_id']      = $data['biller_id'];
      if (!empty($data['method']))        $payment_data['method']         = $data['method'];
      if (isset($data['amount']))         $payment_data['amount']         = $data['amount'];
      if (!empty($data['created_by']))    $payment_data['created_by']     = $data['created_by'];
      if (!empty($data['updated_by']))    $payment_data['updated_by']     = $data['updated_by'];
      if (isset($data['attachment_id']))  $payment_data['attachment_id']  = $data['attachment_id'];
      if (isset($data['status']))         $payment_data['status']         = $data['status'];
      if (!empty($data['type']))          $payment_data['type']           = $data['type'];
      if (isset($data['note']))           $payment_data['note']           = $data['note'];

      $this->db->trans_start();
      $this->db->update('payments', $payment_data, ['id' => $id]); // ORIGINAL UPDATE
      $this->db->trans_complete();

      if ($this->db->trans_status()) {
        if ($isBankDifferent) {
          $this->syncBankAmount($new_bank_id);
          $this->syncBankAmount($old_bank_id);
        } else {
          $this->syncBankAmount($old_bank_id);
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updatePaymentValidation($id, $data)
  {
    if (!empty($data)) {
      $this->db->trans_start();
      $this->db->update('payment_validations', $data, ['id' => $id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Update product category.
   *
   * @param array $data Product Category Data
   *
   * [ *code, *name, image, parent_code, *slug, *description ]
   */
  public function updateProductCategory($id, $data)
  {
    if ($this->db->update('categories', $data, ['id' => $id])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update product mutation
   * @param int $pmId Product Mutation ID
   * @param array $data [ attachment, status, from_warehouse_id, to_warehouse_id, note ]
   * @param array $items Optional: [ *product_id, *quantity, received_qty, *status ]
   */
  public function updateProductMutation($pmId, array $data, array $items = [])
  {
    $pm = $this->getProductMutation(['id' => $pmId]);

    $data = setUpdatedBy($data);

    $this->db->update('product_mutation', $data, ['id' => $pmId]);

    if ($this->db->affected_rows()) {
      if ($items) {
        $this->db->delete('product_mutation_item', ['pm_id' => $pmId]);
        $this->db->delete('stocks', ['pm_id' => $pmId]);

        $receivedTotal = 0;

        foreach ($items as $item) {
          $product = $this->site->getProductByID($item['product_id']);

          if ($product) {
            $item['pm_id'] = $pm->id;
            $item['product_code'] = $product->code;

            if ($item['status'] == 'received' || $item['status'] == 'received_partial') {
              $balance = ($item['quantity'] - $item['received_qty']);

              // Change item status.
              $item['status'] = ($balance == 0 ? 'received' : 'received_partial');

              if ($item['status'] == 'received_partial') {
                $this->db->update('product_mutation', ['status' => 'received_partial'], ['id' => $pmId]);
              } else if ($item['status'] == 'received') {
                $receivedTotal++;
              }
            }

            $this->db->insert('product_mutation_item', $item);

            if ($this->db->affected_rows()) {
              if ($item['status'] == 'sent') {
                $this->addStockQuantity([
                  'pm_id'        => $pmId,
                  'product_id'   => $item['product_id'],
                  'quantity'     => $item['quantity'],
                  'status'       => 'sent',
                  'warehouse_id' => $pm->from_warehouse_id
                ]);
              }

              if ($item['status'] == 'received' || $item['status'] == 'received_partial') {
                $this->addStockQuantity([
                  'pm_id'      => $pmId,
                  'product_id' => $item['product_id'],
                  'quantity'   => $item['received_qty'],
                  'status'     => 'sent',
                  'warehouse_id' => $pm->from_warehouse_id
                ]);
                $this->addStockQuantity([
                  'pm_id'      => $pmId,
                  'product_id' => $item['product_id'],
                  'quantity'   => $item['received_qty'],
                  'status'     => 'received',
                  'warehouse_id' => $pm->to_warehouse_id
                ]);
              }
            }
          }
        }

        if ($receivedTotal == count($items)) {
          $this->db->update('product_transfer', ['status' => 'received'], ['id' => $pmId]);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  public function updateProductReport($reportId, $reportData)
  {
    if ($this->db->update('product_report', $reportData, ['id' => $reportId])) {
      addEvent("Updated Product Report [{$reportId}]: product_id: {$reportData['product_id']}", 'info');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param array $product [*product_id, code, name, *cost, price, markon, safety_stock, min_order_qty,
   * image, category, active, type(combo/service/standard), sale_unit, purchase_unit, price_ranges_value ]
   */
  public function updateProducts($products)
  { // PASSED
    if (!empty($products) && is_array($products)) {
      $product_data = [];
      $product_ids = [];
      $success = 0;

      foreach ($products as $product) {
        if (!empty($product['code']))               $product_data['code']               = $product['code'];
        if (!empty($product['name']))               $product_data['name']               = $product['name'];
        if (!empty($product['unit']))               $product_data['unit']               = $product['unit'];
        if (isset($product['avg_cost']))            $product_data['avg_cost']           = $product['avg_cost'];
        if (isset($product['cost']))                $product_data['cost']               = $product['cost'];
        if (isset($product['price']))               $product_data['price']              = $product['price'];
        if (isset($product['warehouses']))          $product_data['warehouses']         = $product['warehouses'];
        if (!empty($product['markon_price']))       $product_data['markon_price']       = $product['markon_price'];
        if (!empty($product['markon']))             $product_data['markon']             = $product['markon'];
        if (!empty($product['safety_stock_ratio'])) $product_data['safety_stock_ratio'] = $product['safety_stock_ratio'];
        if (!empty($product['min_order_qty']))      $product_data['min_order_qty']      = $product['min_order_qty'];
        if (!empty($product['image']))              $product_data['image']              = $product['image'];
        if (isset($product['iuse_type']))           $product_data['iuse_type']          = $product['iuse_type'];
        if (isset($product['active']))              $product_data['active']             = $product['active'];
        if (!empty($product['category_id']))        $product_data['category_id']        = $product['category_id'];
        if (!empty($product['subcategory_id']))     $product_data['subcategory_id']     = $product['subcategory_id'];
        if (!empty($product['type']))               $product_data['type']               = $product['type'];
        if (!empty($product['supplier_id']))        $product_data['supplier_id']        = $product['supplier_id'];
        if (!empty($product['purchase_unit']))      $product_data['purchase_unit']      = $product['purchase_unit'];

        if (empty($product['product_id'])) {
          return FALSE;
        }

        if (!empty($product['price_ranges_value'])) { // Price Ranges
          $ranges = [];

          foreach ($product['price_ranges_value'] as $price_range) {
            if (strlen($price_range) > 0) {
              $ranges[] = filterDecimal($price_range);
            }
          }

          $product_data['price_ranges_value'] = json_encode($ranges);
          unset($ranges);
        }

        $item = $this->getProductByID($product['product_id']);
        $productJS = getJSON($item->json_data);

        if (isset($product['assigned_at'])) $productJS->assigned_at = trim($product['assigned_at']);
        // A person who assign a PIC. Ex. Admin (Eko) assign TS (Thomas), Eko as assigned_by.
        if (isset($product['assigned_by'])) $productJS->assigned_by = trim($product['assigned_by']);
        if (isset($product['autocomplete'])) $productJS->autocomplete = filterDecimal($product['autocomplete']);
        if (isset($product['condition'])) $productJS->condition = trim($product['condition']);
        if (!empty($product['min_prod_time'])) $productJS->min_prod_time = filterDecimal($product['min_prod_time']);
        if (isset($product['note'])) $productJS->note = trim($product['note']);
        if (isset($product['pic_note'])) $productJS->pic_note = trim($product['pic_note']);
        if (isset($product['priority'])) $productJS->priority = filterDecimal($product['priority']);
        if (!empty($product['prod_time_qty'])) $productJS->prod_time_qty = filterDecimal($product['prod_time_qty']);
        if (isset($product['disposal_date'])) $productJS->disposal_date = trim($product['disposal_date']);
        if (isset($product['disposal_price'])) $productJS->disposal_price = filterDecimal($product['disposal_price']);
        if (isset($product['maintenance_qty'])) $productJS->maintenance_qty = filterDecimal($product['maintenance_qty']);
        if (isset($product['maintenance_cost'])) $productJS->maintenance_cost = trim($product['maintenance_cost']);
        if (isset($product['order_date'])) $productJS->order_date = trim($product['order_date']);
        if (isset($product['order_price'])) $productJS->order_price = filterDecimal($product['order_price']);
        // AKA pic_id (TS=Team Support)
        if (isset($product['pic_id'])) $productJS->pic_id = filterDecimal($product['pic_id']);
        if (isset($product['priority'])) $productJS->priority = trim($product['priority']);
        if (isset($product['purchased_at'])) $productJS->purchased_at = trim($product['purchased_at']);
        if (isset($product['purchase_source'])) $productJS->purchase_source = $product['purchase_source'];
        if (isset($product['sn'])) $productJS->sn = trim($product['sn']);
        if (isset($product['updated_at'])) $productJS->updated_at = trim($product['updated_at']);
        if (!empty($product['updated_by'])) $productJS->updated_by = filterDecimal($product['updated_by']);

        $product_data['json_data'] = json_encode($productJS);
        unset($item, $productJS);

        $this->db->trans_start();
        $this->db->update('products', $product_data, ['id' => $product['product_id']]);
        $this->db->trans_complete();

        if ($this->db->trans_status()) {
          $product_ids[] = $product['product_id'];
          $success++;

          if (!empty($product['type']) && $product['type'] == 'combo') {
            if (!empty($product['combo_items']) && is_array($product['combo_items'])) { // Combo Items
              $this->db->delete('combo_items', ['product_id' => $product['product_id']]);

              foreach ($product['combo_items'] as $combo_item) {
                $cb_data = [
                  'product_id' => $product['product_id'],
                  'item_code'  => $combo_item['item_code'],
                  'quantity'   => $combo_item['quantity'],
                  'unit_price' => $product['price']
                ];

                $this->db->insert('combo_items', $cb_data);
              }
            }

            if (!empty($product['price_groups'])) {
              $this->db->delete('product_prices', ['product_id' => $product['product_id']]);

              foreach ($product['price_groups'] as $price_group) {
                $key = ['price', 'price2', 'price3', 'price4', 'price5', 'price6'];
                $price_ranges = array_combine($key, $price_group['price_ranges']);

                $pp_data = [
                  'product_id' => $product['product_id'],
                  'price_group_id' => $price_group['price_group_id'],
                  'price'  => $price_ranges['price'],
                  'price2' => $price_ranges['price2'],
                  'price3' => $price_ranges['price3'],
                  'price4' => $price_ranges['price4'],
                  'price5' => $price_ranges['price5'],
                  'price6' => $price_ranges['price6'],
                ];

                $this->addProductPrices($pp_data);
              }
            }
          }

          if (!empty($product['type']) && $product['type'] == 'standard') {
            if (!empty($product['safety_stock'])) { // Safety Stocks
              $total_safety_stock = 0;

              foreach ($product['safety_stock'] as $safety_stock) {
                $whp_data = [
                  'product_id'   => $product['product_id'],
                  'warehouse_id' => $safety_stock['warehouse_id'],
                  'safety_stock' => $safety_stock['quantity']
                ];

                $clause = [
                  'product_id'   => $product['product_id'],
                  'warehouse_id' => $safety_stock['warehouse_id']
                ];

                $this->updateWarehouseProduct($clause, $whp_data);

                $total_safety_stock += floatval($safety_stock['quantity']);
              }

              $clause = ['id' => $product['product_id']];
              $this->db->update('products', ['safety_stock' => $total_safety_stock], $clause);
            }

            if (!empty($product['stock_opname'])) { // Stock Opname
              foreach ($product['stock_opname'] as $stock_opname) {
                $whp_data = [
                  'user_id'  => $stock_opname['user_id'],
                  'so_cycle' => $stock_opname['so_cycle']
                ];

                $clause = [
                  'product_id' => $product['product_id'],
                  'warehouse_id' => $stock_opname['warehouse_id']
                ];

                // d($clause); dd($whp_data);

                $this->updateWarehouseProduct($clause, $whp_data);
              }
            }

            //$this->syncProductQty($product['product_id']); // Sync quantity after update.
          }
        }
      }
      return $success;
    }
    return FALSE;
  }

  public function updateReference($field)
  {
    $q = $this->db->get_where('order_ref', ['ref_id' => '1'], 1);
    if ($q->num_rows() > 0) {
      $ref = $q->row();
      $this->db->update('order_ref', [$field => $ref->{$field} + 1], ['ref_id' => '1']);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE PAYROLL.
   */
  public function updatePayroll($payroll_id, $data)
  {
    $payroll_data = [];

    if (!empty($data['date']))        $payroll_data['date']        = $data['date'];
    if (!empty($data['user_id']))     $payroll_data['user_id']     = $data['user_id'];
    if (!empty($data['category_id'])) $payroll_data['category_id'] = $data['category_id'];
    if (isset($data['amount']))       $payroll_data['amount']      = $data['amount'];
    if (!empty($data['status']))      $payroll_data['status']      = $data['status'];
    if (isset($data['note']))         $payroll_data['note']        = $data['note'];

    $this->db->trans_start();
    $this->db->update('payrolls', $payroll_data, ['id' => $payroll_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE PAYROLL CATEGORY.
   */
  public function updatePayrollCategory($category_id, $data)
  {
    $category_data = [];

    if (!empty($data['code'])) $category_data['code'] = $data['code'];
    if (!empty($data['name'])) $category_data['name'] = $data['name'];
    if (!empty($data['type'])) $category_data['type'] = $data['type'];

    $this->db->trans_start();
    $this->db->update('payroll_categories', $category_data, ['id' => $category_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  public function updatePurchase($purchaseId, $data, $items = [])
  {
    if (!empty($data['supplier_id'])) {
      $supplier = $this->getSupplierByID($data['supplier_id']);
      $data['supplier_name']  = $supplier->name;
    }

    if (!empty($data['warehouse_id'])) {
      $warehouse = $this->getWarehouseByID($data['warehouse_id']);
      $data['warehouse_code'] = $warehouse->code;
    }

    if ($items && is_array($items)) {
      $grandTotal = 0;
      $receivedValue = 0;

      foreach ($items as $item) {
        $grandTotal += ($item['cost'] * $item['purchased_qty']);
        $receivedValue += ($item['cost'] * $item['quantity']);
      }

      $data['grand_total']    = $grandTotal;
      $data['received_value'] = $receivedValue;
    }

    $data = setUpdatedBy($data);

    $this->db->update('purchases', $data, ['id' => $purchaseId]);

    if ($this->db->affected_rows()) {
      if ($items && is_array($items)) {
        $purchase = $this->getStockPurchaseByID($purchaseId); // Load new purchase.
        $this->deleteStockQuantity(['purchase_id' => $purchaseId]);

        $data['status'] = ($data['status'] == 'received_partial' ? 'received' : $data['status']);

        foreach ($items as $item) {
          $this->addStockQuantity([ // Add new item if not exists.
            'date'          => $data['date'],
            'purchase_id'   => $purchaseId,
            'product_id'    => $item['product_id'],
            'cost'          => $item['cost'],
            'purchased_qty' => $item['purchased_qty'],
            'quantity'      => $item['quantity'],
            'spec'          => $item['spec'],
            'status'        => $data['status'],
            'warehouse_id'  => $data['warehouse_id'],
            'created_by'    => ($data['created_by'] ?? $purchase->created_by),
            'updated_by'    => ($data['updated_by'] ?? XSession::get('user_id')),
            'json_data'     => $item['json_data']
          ]);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE SALE.
   * @param int $sale_id Sale ID.
   * @param array $data []
   * @param array $items []
   */
  public function updateSale($sale_id, $data, $items = [])
  {
    if (!empty($data)) {
      $sale_data = [];

      $sale = $this->getSaleByID($sale_id);

      if ($sale) {
        if (!empty($data['date']))          $sale_data['date']            = $data['date'];
        if (isset($data['reference']))      $sale_data['reference']       = $data['reference'];
        if (isset($data['no_po']))          $sale_data['no_po']           = $data['no_po'];
        if (isset($data['note']))           $sale_data['note']            = $data['note'];
        if (isset($data['discount']))       $sale_data['discount']        = $data['discount'];
        if (isset($data['shipping']))       $sale_data['shipping']        = $data['shipping'];
        if (isset($data['total']))          $sale_data['total']           = $data['total'];
        if (isset($data['grand_total']))    $sale_data['grand_total']     = $data['grand_total'];
        if (isset($data['balance']))        $sale_data['balance']         = $data['balance'];
        if (isset($data['status']))         $sale_data['status']          = $data['status'];
        if (isset($data['payment_status'])) $sale_data['payment_status']  = $data['payment_status'];
        if (isset($data['due_date']))       $sale_data['due_date']        = $data['due_date'];

        if (isset($data['created_by']))     $sale_data['created_by']      = $data['created_by'];
        if (isset($data['paid']))           $sale_data['paid']            = $data['paid'];
        if (isset($data['attachment_id']))  $sale_data['attachment_id']   = $data['attachment_id'];
        if (isset($data['payment_method'])) $sale_data['payment_method']  = $data['payment_method'];

        if (!empty($data['updated_by'])) $sale_data['updated_by'] = $data['updated_by'];
        if (!empty($data['updated_at'])) $sale_data['updated_at'] = $data['updated_at'];

        if (!empty($data['customer_id'])) {
          $customer = $this->getCustomerByID($data['customer_id']);
          $sale_data['customer_id'] = $customer->id;
          $sale_data['customer']    = $customer->phone;
        }

        if (!empty($data['biller_id'])) {
          $biller = $this->getBillerByID($data['biller_id']);
          $sale_data['biller_id'] = $biller->id;
          $sale_data['biller']    = $biller->code;
        }

        if (!empty($data['warehouse_id'])) {
          $warehouse = $this->getWarehouseByID($data['warehouse_id']);
          $sale_data['warehouse_id'] = $warehouse->id;
          $sale_data['warehouse']    = $warehouse->code;
        }

        // Sale JSON
        $saleJS = getJSON($sale->json_data);

        if (!empty($data['approved']))                $saleJS->approved                = $data['approved'];
        if (!empty($data['cashier_by']))              $saleJS->cashier_by              = $data['cashier_by'];
        if (!empty($data['source']))                  $saleJS->source                  = $data['source'];
        if (!empty($data['est_complete_date']))       $saleJS->est_complete_date       = $data['est_complete_date'];
        if (!empty($data['payment_due_date']))        $saleJS->payment_due_date        = $data['payment_due_date'];
        if (!empty($data['waiting_production_date'])) $saleJS->waiting_production_date = $data['waiting_production_date'];

        $sale_data['json']      = json_encode($saleJS);
        $sale_data['json_data'] = json_encode($saleJS);

        $this->db->trans_start();
        $this->db->update('sales', $sale_data, ['id' => $sale_id]); // ORIGINAL UPDATE SALE #1.
        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
          addEvent("Updated Sale [{$sale_id}: {$sale->reference}]", 'warning');

          if ($items) { // Executed if items is present. Optional.
            $sale_items = [];
            $discount = filterDecimal($data['discount'] ?? 0);
            $total_price = 0;
            $total_qty = 0;

            foreach ($items as $item) {
              if (isset($data['warehouse_id'])) {
                $item['warehouse_id'] = $data['warehouse_id'];
              }

              $item['date'] = $sale->date;
              $item_w    = filterQuantity($item['width']  ?? 0);
              $item_l    = filterQuantity($item['length'] ?? 0);
              $item_area = ($item_w * $item_l);
              $price     = filterDecimal($item['price']);
              $quantity  = filterQuantity($item['quantity']);

              $qty = ($item_area > 0 ? $item_area * $quantity : $quantity);

              $total_price  += round($price * $qty);
              $total_qty    += $qty;
              $sale_items[]  = $item;

              $_item = $this->getProductByID($item['product_id']);
              addEvent("Updated Sale Item [{$_item->name}], W:{$item_w}, L:{$item_l}, " .
                "Price:{$price}, Qty:{$quantity}", 'warning');
            }

            if ($sale_items) $this->updateSaleItems($sale_id, $sale_items);

            $this->db->update(
              'sales',
              [
                'total' => roundDecimal($total_price),
                'grand_total' => roundDecimal($total_price - $discount),
                'total_items' => $total_qty
              ],
              ['id' => $sale_id]
            ); // ORIGINAL UPDATE SALE #2.
          }
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * UPDATE SALE ITEM BY ID
   */
  public function updateSaleItem($saleitem_id, $data)
  {
    $saleItemData = [];

    $saleItem = $this->getSaleItemByID($saleitem_id);

    if (!$saleItem) {
      setLastError('Invalid sale item.');
      return FALSE;
    }

    if (!empty($data['date']))       $saleItemData['date']    = $data['date'];
    if (!empty($data['sale_id'])) {
      $sale = Sale::getRow(['id' => $data['sale_id']]);

      $saleItemData['sale_id'] = $sale->id;
      $saleItemData['sale'] = $sale->reference;
    }

    if (!empty($data['product_id'])) {
      $product = $this->getProductByID($data['product_id']);

      if ($product) {
        $saleItemData['product_id']   = $product->id;
        $saleItemData['product_code'] = $product->code;
        $saleItemData['product_name'] = $product->name;
        $saleItemData['product_type'] = $product->type;
      }
    }
    if (isset($data['price']))        $saleItemData['price']        = $data['price'];
    if (isset($data['quantity']))     $saleItemData['quantity']     = $data['quantity'];
    if (isset($data['finished_qty'])) $saleItemData['finished_qty'] = $data['finished_qty'];
    // if (!empty($data['warehouse_id'])) {
    //   $warehouse = $this->getWarehouseByID($data['warehouse_id']);

    //   if ($warehouse) {
    //     $saleItemData['warehouse_id'] = $warehouse->id;
    //   }
    // }
    if (isset($data['price']) || isset($data['quantity'])) {
      $price    = ($data['price']    ?? $saleItem->price);
      $quantity = ($data['quantity'] ?? $saleItem->quantity);

      $saleItemData['subtotal'] = $price * $quantity;
    }
    if (
      isset($data['width']) || isset($data['length']) || isset($data['spec']) ||
      isset($data['status']) || isset($data['quantity']) || isset($data['due_date']) ||
      isset($data['completed_at'])
    ) {
      $jsonData = json_decode($saleItem->json_data);

      if (!$jsonData) $jsonData = (object)[];

      if (isset($data['completed_at'])) $jsonData->completed_at = $data['completed_at']; // Completed date.
      if (isset($data['due_date']))     $jsonData->due_date     = $data['due_date']; // Production due date.
      if (isset($data['length']))       $jsonData->l            = $data['length'];
      if (isset($data['operator_id']))  $jsonData->operator_id  = $data['operator_id'];
      if (isset($data['quantity']))     $jsonData->l            = $data['quantity'];
      if (isset($data['spec']))         $jsonData->spec         = $data['spec'];
      if (isset($data['status']))       $jsonData->status       = $data['status'];
      if (isset($data['width']))        $jsonData->w            = $data['width'];

      $saleItemData['json_data']  = json_encode($jsonData); // Required.
      $saleItemData['json']       = json_encode($jsonData); // Required.
    }

    $this->db->trans_start();
    $this->db->update('sale_items', $saleItemData, ['id' => $saleItem->id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE SALE ITEMS.
   * @param int $sale_id Sale ID.
   * @param array $items [[ *product_id, *price, width, length, spec, *quantity, *warehouse_id ]]
   */
  public function updateSaleItems($sale_id, $items)
  {
    $sale = $this->getSaleByID($sale_id);

    if ($sale && $items) {
      $this->deleteSaleItems(['sale_id' => $sale->id]);
      if ($this->addSaleItems($sale->id, $items)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * USED BY CS ONLY.
   */
  // public function updateSaleItemStatus($saleitem_id, $status, $note = NULL)
  // {
  //   $sale_item = $this->getSaleItemByID($saleitem_id);
  //   if ($sale_item) {
  //     $json_data = json_decode($sale_item->json_data);
  //     $json_data->status = $status;

  //     $this->db->trans_start();
  //     $this->db->update('sale_items', ['json_data' => json_encode($json_data)], ['id' => $saleitem_id]);
  //     $this->db->trans_complete();

  //     if ($this->db->trans_status() !== FALSE) {
  //       $all_completed = FALSE;
  //       $completed = 0;
  //       $sale_items = $this->getSaleItemsBySaleID($sale_item->sale_id);
  //       $item_count = count($sale_items);
  //       foreach ($sale_items as $slitem) {
  //         if (json_decode($slitem->json_data)->status == 'completed') $completed++;
  //         if ($completed == $item_count) {
  //           $all_completed = TRUE;
  //           break;
  //         }
  //       }

  //       // Waiting Production > Completed > Finished
  //       if ($status != 'completed' || ($all_completed && $status == 'completed')) {
  //         if ($this->updateSale($sale_item->sale_id, ['status' => $status, 'note' => $note])) {
  //           return TRUE;
  //         }
  //       } else if (!$all_completed && $status == 'completed') { // Waiting Production > Completed.
  //         return TRUE;
  //       }
  //     }
  //   }
  //   return FALSE;
  // }

  /**
   * Update sale status. Used by CS only.
   */
  public function updateSaleStatus($saleId, $status, $note = NULL)
  {
    $sale = $this->getSaleByID($saleId);
    $saleItems = $this->getSaleItems(['sale_id' => $sale->id]);

    if ($sale) {
      $saleData = ['status' => $status];

      if (!empty($note)) $saleData['note'] = $note;

      // If old status == completed and new status == finished then send WA.
      if ($sale->status == 'completed' && $status == 'finished') {
        $customer = $this->getCustomerByID($sale->customer_id);
        $customerJS = getJSON($customer->json_data);

        if (!isset($customerJS->notify_wa) || $customerJS->notify_wa != 0) {
          $text = "Halo kak {$customer->name} \u{1F48C}\n" .
            "Pesanan dengan no. invoice: *{$sale->reference}* telah terselesaikan, " .
            "silahkan bisa diambil/dikirim (abaikan jika kakak tidak merasa pesan).\n\n" .
            "\u{2708} Pantau Status Orderan kakak (produksi s.d pengiriman) dengan klik link berikut:\n" .
            "https://indoprinting.co.id/trackorder?invoice={$sale->reference}\n\n" .
            "\u{1F947} Suka dengan produknya? Bantu kami ulas produk ini ya.. " .
            "Ulasan bintang 5 akan mengaktifkan " .
            "garansi 1 minggu secara otomatis \u{1F601}..\n" .
            getURLRating($sale->warehouse_id) . "\n\n" .
            "\u{1F3AF} Order Praktis & Simple darimana aja.. " .
            "by Online indoprinting.co.id | by WhatsApp " .
            "wa.me/6282132003200\n\n" .
            "\u{1F6E0} Beri kami masukkan ya.. bisa dikirim by WhatsApp wa.me/6281327043234\n\n" .
            "Follow us https://www.instagram.com/indoprinting/ \u{1F4E3}\n\n" .
            "Terima kasih \u{1F64F},\nIndoprinting Team\n\n" .
            "_#PesanOtomatis_";

          $this->addWAJob([
            'sale_id'   => $sale->id,
            'phone'     => $customer->phone,
            'message'   => $text,
            'send_date' => $this->serverDateTime,
            'status'    => 'pending'
          ]);
        }
      }

      $this->updateSale($sale->id, $saleData);

      foreach ($saleItems as $saleItem) {
        $this->updateSaleItem($saleItem->id, ['status' => $status]);
      }

      $this->syncSales(['sale_id' => $sale->id]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE SALES TB.
   *
   * @param array $data [ *last_sync_date, *from_biller_id, *to_warehouse_id, *start_date, *end_date,
   *  *amount, *status, created_by ]
   */
  public function updateSaleTB($saleTBId, $data)
  {
    $this->db->trans_start();
    $this->db->update('sales_tb', $data, ['id' => $saleTBId]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateSchedule($id, $data)
  {
    $this->db->update('schedule', $data, ['id' => $id]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY UPDATE ADJUSTMENT STOCK QUANTITY FUNCTIONS.
   */
  public function updateStockAdjustment($adjustment_id, $data, $products)
  {
    $this->db->trans_start();
    $this->db->update('adjustments', $data, ['id' => $adjustment_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      $adjustment = $this->getStockAdjustmentByID($adjustment_id);
      $this->deleteStockQuantity(['adjustment_id' => $adjustment_id]);

      // Update all new items.
      foreach ($products as $product) {
        $warehouse = Warehouse::getRow(['id' => $product['warehouse_id']]);

        $this->addStockQuantity([ // Add if not present.
          'date' => $data['date'],
          'adjustment_id'   => $adjustment_id,
          'product_id'      => $product['product_id'],
          'quantity'        => $product['quantity'],
          'adjustment_qty'  => $product['adjustment_qty'],
          'status'          => $product['type'],
          'warehouse_id'    => $product['warehouse_id'],
          'warehouse'       => $warehouse->code,
          'created_by'      => ($data['created_by'] ?? $adjustment->created_by),
          'updated_by'      => XSession::get('user_id')
        ]);
      }

      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE INTERNAL USE.
   */
  public function updateStockInternalUse($iuseId, $data, $items = [])
  {
    $oldIUse = $this->getStockInternalUseByID($iuseId);

    if ($oldIUse->category == 'consumable') {
      $data['status'] = 'completed';
    }

    DB::table('internal_uses')->update($data, ['id' => $iuseId]);

    if (DB::affectedRows()) {
      if (isset($data['status'])) {
        if ($data['status'] == 'packing' || $data['status'] == 'installed' || $data['status'] == 'completed') {
          $data['status'] = 'sent'; // Change from packing to sent for stocks.
        }
      }

      if ($items) {
        $iuse = $this->getStockInternalUseByID($iuseId);
        $this->deleteStockQuantity(['internal_use_id' => $iuseId]);

        foreach ($items as $item) {
          // Generate unique code if add new item from edit.
          $item['unique_code'] = (empty($item['unique_code']) ? generateInternalUseUniqueCode($data['category']) : $item['unique_code']);

          $this->addStockQuantity([
            'date'            => $data['date'],
            'internal_use_id' => $iuseId,
            'product_id'      => $item['product_id'],
            'price'           => $item['price'],
            'quantity'        => $item['quantity'],
            'spec'            => $item['spec'],
            'status'          => $data['status'],
            'warehouse_id'    => $data['from_warehouse_id'],
            'machine_id'      => $item['machine_id'],
            'ucr'             => $item['ucr'],
            'unique_code'     => $item['unique_code'],
            'created_by'      => ($data['created_by'] ?? $iuse->created_by),
            'updated_by'      => XSession::get('user_id')
          ]);
        }
      }

      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE STOCK OPNAME.
   * @param int $opname_id Stock opname id.
   * @param array $data Stock opname data.
   * @param array $items Stock opname item data.
   */
  public function updateStockOpname($opname_id, $data, $items = [])
  {
    $status = NULL;
    $subtotal = 0.0;
    $total_edited = 0;
    $total_plus = 0.0;
    $total_lost = 0.0;

    $opname = $this->getStockOpnameByID($opname_id);

    for ($a = 0; $a < count($items); $a++) {
      $first_qty  = filterDecimal($items[$a]['first_qty']);
      $reject_qty = filterDecimal($items[$a]['reject_qty']);
      $last_qty   = filterDecimal($items[$a]['last_qty']);
      $quantity   = filterDecimal($items[$a]['quantity']);
      $price      = filterDecimal($items[$a]['price']);

      $rest_qty = (($last_qty - $quantity) + $reject_qty);
      $subtotal = ceil($rest_qty * $price);

      $items[$a]['first_qty']  = $first_qty;
      $items[$a]['reject_qty'] = $reject_qty;
      $items[$a]['last_qty']   = $last_qty;
      $items[$a]['quantity']   = $quantity;
      $items[$a]['price']      = $price;
      $items[$a]['subtotal']   = $subtotal;

      if ($subtotal < 0) {
        $total_lost += filterDecimal($subtotal);
      }

      if ($subtotal > 0) {
        $total_plus += filterDecimal($subtotal);
      }

      if ($data['status'] == 'checked') {
        $status = 'confirmed';
      } else if ($data['status'] == 'confirmed') {
        $status = 'verified';
      }

      if ($last_qty !== $first_qty) {
        $total_edited++;
      }
    }

    if ($total_edited) {
      $data['total_edited'] = $total_edited;
    } else {
      $status = 'verified'; // If not edited, got verified.
    }

    $data['status'] = ($status ?? $data['status']);
    $data['total_lost'] = $total_lost;
    $data['total_plus'] = $total_plus;

    $this->db->trans_start();
    $this->db->update('stock_opnames', $data, ['id' => $opname_id]);
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      if ($items) { // If has items.
        $warehouse = $this->getWarehouseByID($data['warehouse_id']);

        foreach ($items as $item) { // Update stock opname items.
          $product = $this->getProductByID($item['product_id']);

          $this->db->delete('stock_opname_items', ['opname_id' => $opname_id, 'product_id' => $product->id]);

          $item_data = [
            'opname_id'      => $opname_id,
            'product_id'     => $product->id,
            'product_code'   => $product->code,
            'quantity'       => $item['quantity'],
            'first_qty'      => $item['first_qty'],
            'reject_qty'     => $item['reject_qty'],
            'last_qty'       => $item['last_qty'],
            'price'          => $item['price'],
            'subtotal'       => $item['subtotal'],
            'warehouse_id'   => $warehouse->id,
            'warehouse_code' => $warehouse->code
          ];

          $this->db->insert('stock_opname_items', $item_data);
        }
      }

      // If old status not verified before and current status verified. Add Adjustment Minus if present.
      if ($opname->status != 'verified' && $data['status'] == 'verified') {
        $adjustment_min_items = [];

        foreach ($items as $item) {
          if ($item['quantity'] > $item['last_qty']) { // If quantity > last_qty, adjustment minus.
            $product = $this->getProductByID($item['product_id']);

            $adjustment_min_items[] = [
              'product_id' => $item['product_id'],
              'quantity'   => ($item['last_qty'] ?? $item['first_qty'])
            ];
          }
        }

        if ($adjustment_min_items) {
          $adjustmentData = [
            'date'         => $data['updated_at'],
            'warehouse_id' => $opname->warehouse_id,
            'mode'         => 'overwrite', // MANDATORY!. It CANNOT be empty. overwrite or formula.
            'note'         => $opname->reference,
            'created_by'   => $opname->created_by,
            'end_date'     => $opname->date
          ];

          if ($adjustment_id = $this->addAdjustmentStock($adjustmentData, $adjustment_min_items)) {
            $this->db->update('stock_opnames', ['adjustment_min_id' => $adjustment_id], ['id' => $opname_id]);
          }
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  public function updateStockOpnameItem($soItemId, $soItemData)
  {
    if ($this->db->update('stock_opname_items', $soItemData, ['id' => $soItemId])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO EDIT/UPDATE STOCK PURCHASE.
   */
  public function updateStockPurchase($purchase_id, $data, $items = [])
  {
    if (!empty($data['supplier_id'])) {
      $supplier = $this->getSupplierByID($data['supplier_id']);
      $data['supplier_name']  = $supplier->name;
    }

    if (!empty($data['warehouse_id'])) {
      $warehouse = $this->getWarehouseByID($data['warehouse_id']);
      $data['warehouse_code'] = $warehouse->code;
      $data['warehouse_name'] = $warehouse->name;
    }

    $this->db->update('purchases', $data, ['id' => $purchase_id]);

    if ($this->db->affected_rows()) {
      if ($items && is_array($items)) {
        $purchase = $this->getStockPurchaseByID($purchase_id); // Load new purchase.
        $this->deleteStockQuantity(['purchase_id' => $purchase_id]);

        foreach ($items as $item) {
          $this->addStockQuantity([ // Add new item if not exists.
            'date'          => $data['date'],
            'purchase_id'   => $purchase_id,
            'product_id'    => $item['product_id'],
            'cost'          => $item['cost'],
            'purchased_qty' => $item['purchased_qty'],
            'quantity'      => $item['quantity'],
            'spec'          => $item['spec'],
            'status'        => $item['status'],
            'unit_id'       => $item['unit_id'],
            'warehouse_id'  => $data['warehouse_id'],
            'created_by'    => ($data['created_by'] ?? $purchase->created_by),
            'updated_by'    => ($data['updated_by'] ?? XSession::get('user_id')),
            'json_data'     => $item['json_data']
          ]);
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update stock purchase payment.
   * @param integer $payment_id Payment ID.
   * @param array $data [status*, note]
   */
  public function updateStockPurchasePayment($payment_id, $data)
  {
    $payment = $this->getPaymentByID($payment_id);
    $purchase = $this->getStockPurchaseByID($payment->purchase_id);

    $payment_data  = ['status' => $data['status'], 'note' => $data['note']];
    $purchase_data = ['payment_status' => $data['status'], 'note' => $data['note']];

    if ($data['status'] == 'paid') {
      if (($purchase->grand_total + $purchase->discount) == ($purchase->paid + $payment->amount)) {
        $pay_status = 'paid';
      } else if (($purchase->grand_total + $purchase->discount) > ($purchase->paid + $payment->amount)) {
        $pay_status = 'partial';
      }

      $payment_data['date'] = $data['date'];
      $payment_data['type'] = 'sent';
      $purchase_data['balance'] = $purchase->balance + ($purchase->paid + $payment->amount - $purchase->discount);
      $purchase_data['payment_date']   = $data['date'];
      $purchase_data['payment_status'] = $pay_status;
      $purchase_data['paid'] = $purchase->paid + $payment->amount;
    }

    if ($this->updatePayment($payment->id, $payment_data)) {
      if ($this->updateStockPurchase($purchase->id, $purchase_data)) {
        $this->syncStockPurchase($purchase->id);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE STOCK QUANTITY.
   * @param array $clause [id, adjustment_id, internal_use_id, purchase_id, sale_id, transfer_id]* | [product_id, warehouse_id]
   * @param array $item [date, (adjustment_id, internaluse_id, purchase_id, sale_id, transfer_id), product_id*, quantity*,
   * warehouse_id, created_by, updated_by]
   */
  public function updateStockQuantity($clause, $item)
  {
    if (isset($item['quantity']) && $item['quantity'] != 0) return FALSE;
    $ci = $this;

    $ret = $this->ridintek->mutex('stock')->on('lock', function ($mutex) use ($ci, $clause, $item) {
      $clauses    = [];
      $stock_data = [];

      $stock_data['updated_by'] = ($item['updated_by'] ?? $ci->session->userdata('user_id'));
      $stock_data['updated_at'] = ($item['updated_at'] ?? date('Y-m-d H:i:s'));

      if (!empty($clause['id']))              $clauses['id']               = $clause['id'];

      if (!empty($clause['adjustment_id']))   $clauses['adjustment_id']    = $clause['adjustment_id'];
      if (!empty($clause['internal_use_id'])) $clauses['internal_use_id']  = $clause['internal_use_id'];
      if (!empty($clause['purchase_id']))     $clauses['purchase_id']      = $clause['purchase_id'];
      if (!empty($clause['sale_id']))         $clauses['sale_id']          = $clause['sale_id'];
      if (!empty($clause['transfer_id']))     $clauses['transfer_id']      = $clause['transfer_id'];
      if (!empty($clause['saleitem_id']))     $clauses['saleitem_id']      = $clause['saleitem_id'];

      if (!empty($clause['product_id']))      $clauses['product_id']       = $clause['product_id'];
      if (!empty($clause['warehouse_id']))    $clauses['warehouse_id']     = $clause['warehouse_id'];

      if (!empty($item['id']))              $stock_data['id']              = $item['id'];
      if (!empty($item['date']))            $stock_data['date']            = $item['date'];
      if (!empty($item['adjustment_id']))   $stock_data['adjustment_id']   = $item['adjustment_id'];
      if (!empty($item['internal_use_id'])) $stock_data['internal_use_id'] = $item['internal_use_id'];
      if (!empty($item['purchase_id']))     $stock_data['purchase_id']     = $item['purchase_id'];
      if (!empty($item['sale_id']))         $stock_data['sale_id']         = $item['sale_id'];
      if (!empty($item['transfer_id']))     $stock_data['transfer_id']     = $item['transfer_id'];
      if (!empty($item['saleitem_id']))     $stock_data['saleitem_id']     = $item['saleitem_id'];
      if (isset($item['cost']))             $stock_data['cost']            = $item['cost'];
      if (isset($item['price']))            $stock_data['price']           = $item['price'];
      if (isset($item['quantity']))         $stock_data['quantity']        = $item['quantity'];
      if (isset($item['adjustment_qty']))   $stock_data['adjustment_qty']  = $item['adjustment_qty'];
      if (isset($item['purchased_qty']))    $stock_data['purchased_qty']   = $item['purchased_qty'];
      if (isset($item['spec']))             $stock_data['spec']            = $item['spec'];
      if (!empty($item['status']))          $stock_data['status']          = $item['status'];
      if (isset($item['subtotal']))         $stock_data['subtotal']        = $item['subtotal'];
      if (isset($item['json_data']))        $stock_data['json_data']       = $item['json_data'];

      if (isset($item['price']) && isset($item['quantity'])) {
        $stock_data['subtotal'] = filterDecimal($item['price']) * filterDecimal($item['quantity']);
      }

      if (isset($item['machine_id']))    $stock_data['machine_id'] = $item['machine_id'];
      if (!empty($item['created_by'])) $stock_data['created_by'] = $item['created_by'];

      if (!empty($item['product_id'])) { // Update Product
        $product = $ci->getProductByID($item['product_id']);

        if ($product) {
          $category = $ci->getProductCategoryByID($product->category_id);
          $unit     = $ci->getProductUnitByID($product->unit);

          $stock_data['product_id']   = $product->id;
          $stock_data['product_code'] = $product->code;
          $stock_data['product_name'] = $product->name;
          $stock_data['product_type'] = $product->type;

          $stock_data['category_id']   = $category->id;
          $stock_data['category_code'] = $category->code;
          $stock_data['category_name'] = $category->name;

          if ($unit) {
            $stock_data['unit_id']   = $unit->id;
            $stock_data['unit_code'] = $unit->code;
            $stock_data['unit_name'] = $unit->name;
          }
        }
      }

      if (!empty($item['warehouse_id'])) { // Update Warehouse
        $warehouse = $ci->getWarehouseByID($item['warehouse_id']);

        if ($warehouse) {
          $stock_data['warehouse_id'] = $warehouse->id;
          $stock_data['warehouse_code'] = $warehouse->code;
          $stock_data['warehouse_name'] = $warehouse->name;
        }
      }

      $stocks = $this->getStocks($clauses); // Get current stock before updated.

      $ci->db->trans_start();
      $ci->db->update('stocks', $stock_data, $clauses);
      $ci->db->trans_complete();

      if ($ci->db->trans_status()) {
        if ($stocks) {
          foreach ($stocks as $stock) {
            if (isset($item['quantity'])) {
              $diff = ($stock->quantity - filterDecimal($item['quantity']));

              if ($diff > 0) {
                $this->decreaseWarehouseQty($stock->product_id, $stock->warehouse_id, $diff);
              } else if ($diff < 0) {
                $this->increaseWarehouseQty($stock->product_id, $stock->warehouse_id, ($diff * -1));
              }
            }
          }
        }

        return TRUE;
      }

      return FALSE;
    })->create()->close();
    return $ret;
  }

  public function updateSupplier($supplier_id, $data)
  {
    $this->db->update('suppliers', $data, ['id' => $supplier_id]);

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add new Tracking POD.
   * @param int $trackId Tracking ID.
   * @param array $data [ *pod_id, *start_click, *end_click, *mc_reject, *erp_click, *tolerance,
   *  *cost_click, *pic_id ]
   */
  public function updateTrackingPOD($trackId, $data)
  {
    $track = $this->getTrackingPODByID($trackId);

    if (!$track) {
      setLastError('TrackingPOD is not found.');
      return FALSE;
    }

    $TData = [
      'pod_id'        => ($data['pod_id']        ?? $track->pod_id),
      'start_click'   => ($data['start_click']   ?? $track->start_click),
      'end_click'     => ($data['end_click']     ?? $track->end_click),
      'mc_reject'     => ($data['mc_reject']     ?? $track->mc_reject),
      'erp_click'     => ($data['erp_click']     ?? $track->erp_click),
      'tolerance'     => ($data['tolerance']     ?? $track->tolerance),
      'cost_click'    => ($data['cost_click']    ?? $track->cost_click),
      'adjustment_id' => ($data['adjustment_id'] ?? $track->adjustment_id),
      'warehouse_id'  => ($data['warehouse_id']  ?? $track->warehouse_id),
      'attachment_id' => ($data['attachment_id'] ?? $track->attachment_id),
      'note'          => ($data['note']          ?? $track->note),
      'created_at'    => ($data['created_at']    ?? $track->created_at),
      'created_by'    => ($data['created_by']    ?? $track->created_by),
      'updated_at'    => ($data['updated_at']    ?? $track->updated_at),
      'updated_by'    => ($data['updated_by']    ?? $track->updated_by),
    ];

    $mcReject = ($TData['mc_reject'] > 0 ? $TData['mc_reject'] * -1 : $TData['mc_reject']);

    $usageClick     = $TData['end_click'] - $TData['start_click'];
    $opReject       = $TData['erp_click'] - $TData['end_click'] - $mcReject;
    // If opReject minus then opReject else if plus then 0.
    $opReject       = ($opReject < 0 ? $opReject : 0);
    $toleranceClick = round(($mcReject + $opReject) * 0.01 * $TData['tolerance']);
    $balance        = ($mcReject + $opReject) - $toleranceClick;
    $totalPenalty   = ($balance < 0 ? $balance * $TData['cost_click'] : 0);

    $TData['usage_click']     = $usageClick;
    $TData['mc_reject']       = $mcReject;
    $TData['op_reject  ']     = $opReject;
    $TData['tolerance_click'] = $toleranceClick;
    $TData['balance']         = $balance;
    $TData['total_penalty']   = $totalPenalty;

    $this->db->update('trackingpod', $TData, ['id' => $trackId]); // ORIGINAL UPDATE #1

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateUnit($unit_id, $data)
  {
    $this->db->trans_start();
    $this->db->update('units', $data, ['id' => $unit_id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateUser($user_id, $data)
  {
    $this->db->trans_start();
    $this->db->update('users', $data, ['id' => $user_id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Update WA Jobs service.
   * @param int $jobId Job ID.
   * @param array $data [ sale_id, phone, message, send_date, status ]
   */
  public function updateWAJob($jobId, $data)
  {
    $data = setUpdatedBy($data);

    if ($this->db->update('wa_job', $data, ['id' => $jobId])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE WAREHOUSE.
   * @param array $clause [ id, code, name, email ]
   * @param array $data Warehouse Data.
   */
  public function updateWarehouse($clause, $data)
  {
    if (isset($clause['code'])) {
      $this->db->like('code', $clause['code']);
      unset($clause['code']);
    }

    if (isset($clause['name'])) {
      $this->db->like('name', $clause['name']);
      unset($clause['name']);
    }

    if (isset($clause['email'])) {
      $this->db->like('email', $clause['email']);
      unset($clause['email']);
    }

    $this->db->update('warehouses', $data, $clause); // ORIGINAL UPDATE.

    if ($this->db->affected_rows()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * THE ONLY FUNCTION TO UPDATE WAREHOUSE PRODUCT.
   * @param array $clauses [ id, product_id, product_code, warehouse_id, warehouse_code, user_id ]
   * @param array $data [ product_id, product_code, warehouse_id, warehouse_code, quantity, rack,
   *  safety_stock, user_id, so_cycle ]
   */
  public function updateWarehouseProduct($clause, $data)
  {
    $clauses = [];
    if (!empty($clause['id']))             $clauses['id']             = $clause['id'];
    if (!empty($clause['product_id']))     $clauses['product_id']     = $clause['product_id'];
    if (!empty($clause['product_code']))   $clauses['product_code']   = $clause['product_code'];
    if (!empty($clause['warehouse_id']))   $clauses['warehouse_id']   = $clause['warehouse_id'];
    if (!empty($clause['warehouse_code'])) $clauses['warehouse_code'] = $clause['warehouse_code'];
    if (!empty($clause['user_id']))        $clauses['user_id']        = $clause['user_id'];

    $whp_data = [];
    if (!empty($data['product_id']))     $whp_data['product_id']     = $data['product_id'];
    if (!empty($data['product_code']))   $whp_data['product_code']   = $data['product_code'];
    if (!empty($data['warehouse_id']))   $whp_data['warehouse_id']   = $data['warehouse_id'];
    if (!empty($data['warehouse_code'])) $whp_data['warehouse_code'] = $data['warehouse_code'];
    if (isset($data['quantity']))        $whp_data['quantity']       = $data['quantity'];
    if (!empty($data['rack']))           $whp_data['rack']           = $data['rack'];
    if (isset($data['safety_stock']))    $whp_data['safety_stock']   = $data['safety_stock'];
    if (isset($data['user_id']))         $whp_data['user_id']        = $data['user_id'];
    if (!empty($data['so_cycle']))       $whp_data['so_cycle']       = $data['so_cycle'];

    if (!empty($clauses['product_code'])) {
      $this->db->like('product_code', $clauses['product_code']);
      unset($clauses['product_code']);
    }

    if (!empty($clauses['warehouse_code'])) {
      $this->db->like('warehouse_code', $clauses['warehouse_code']);
      unset($clauses['warehouse_code']);
    }

    $this->db->trans_start();
    $this->db->update('warehouses_products', $whp_data, $clauses); // ORIGINAL UPDATE.
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate payment validation.
   * @param object $response Mutasibank response.
   *
   * {
   *   account_number,
   *
   * }
   * @param array $options Options.
   *
   */
  public function validatePaymentValidation($response, $options = [])
  { // Added
    $paymentValidated = FALSE;
    $this->syncPaymentValidations(); // Change pending payment to expired if any.
    $sale_id     = ($options['sale_id'] ?? NULL);
    $mutation_id = ($options['mutation_id'] ?? NULL);
    $status = ($sale_id || $mutation_id ? ['expired', 'pending'] : 'pending');
    // $status = ($sale_id || $mutation_id ? ['pending'] : 'pending'); // New
    $paymentValidation = $this->getPaymentValidationsByStatus($status);
    $validatedCount = 0;

    if ($paymentValidation) {
      foreach ($paymentValidation as $pv) {
        $accountNo  = $response->account_number;
        $dataMutasi = $response->data_mutasi;

        foreach ($dataMutasi as $dm) { // DM = Data Mutasi.
          $amount_match = ((floatval($pv->amount) + floatval($pv->unique_code)) == floatval($dm->amount) ? TRUE : FALSE);
          // If amount same as unique_code + amount OR sale_id same OR mutation_id same
          // Executed by CRON or Manually.
          // CR(mutasibank) = Masuk ke rekening.
          // DB(mutasibank) = Keluar dari rekening.
          if (
            ($amount_match && $dm->type == 'CR') ||
            ($sale_id && $sale_id == $pv->sale_id) || ($mutation_id && $mutation_id == $pv->mutation_id)
          ) {
            //if ($this->Owner) rd_print($response, $pv);
            $bank = $this->getPaymentValidationBank($accountNo, $pv->biller_id);

            if (!$bank) {
              die('Bank not defined');
            }
            //if ($this->Owner) rd_print($bank); die();
            $pv_data = [
              'bank_id'           => $bank->id,
              'transaction_date'  => $dm->transaction_date,
              'description'       => $dm->description,
              'status'            => 'verified'
            ];

            if (!empty($options['manual'])) {
              $pv_data = setCreatedBy($pv_data);
              $pv_data['description'] = '(MANUAL) ' . $pv_data['description'];
            }

            if ($this->updatePaymentValidation($pv->id, $pv_data)) {
              if ($pv->sale_id) { // If sale_id exists.
                $sale = $this->getSaleByID($pv->sale_id);
                $payment = [
                  'date'            => $this->serverDateTime, // $pv_updated->transaction_date,
                  'reference_date'  => $sale->created_at,
                  'sale_id'         => $pv->sale_id,
                  'amount'          => $pv->amount,
                  'method'          => 'Transfer',
                  'bank_id'         => $bank->id,
                  'created_by'      => $pv->created_by,
                  'type'            => 'received'
                ];

                if (isset($options['attachment_id'])) $payment['attachment_id'] = $options['attachment_id'];

                $this->addSalePayment($payment, TRUE); // Add real payment to sales. 2nd param must be TRUE if payment validation automated.
                $customer = $this->getCustomerByID($sale->customer_id);

                if ($customer && $amount_match) { // Restore unique code as deposit for customer if amount match.
                  $this->updateCustomer($sale->customer_id, [
                    'deposit_amount' => $customer->deposit_amount + $pv->unique_code
                  ]);
                }

                $sale = $this->getSaleByID($pv->sale_id);

                $validatedCount++;
              }

              if ($pv->mutation_id) { // If mutation_id exists.
                $mutation = $this->getBankMutationByID($pv->mutation_id);
                $payment_from = [
                  'date'            => $this->serverDateTime,
                  'reference_date'  => $mutation->date,
                  'mutation_id'     => $mutation->id,
                  'bank_id'         => $mutation->from_bank_id,
                  'method'          => $mutation->paid_by,
                  'amount'          => $mutation->amount + $pv->unique_code,
                  'created_by'      => $mutation->created_by,
                  'type'            => 'sent',
                  'note'            => $mutation->note
                ];

                if (isset($options['attachment_id'])) $payment_from['attachment_id'] = $options['attachment_id'];

                if (Payment::add($payment_from)) {
                  $payment_to = [
                    'date'        => $mutation->date,
                    'mutation_id' => $mutation->id,
                    'bank_id'     => $mutation->to_bank_id,
                    'method'      => $mutation->paid_by,
                    'amount'      => $mutation->amount + $pv->unique_code,
                    'created_by'  => $mutation->created_by,
                    'type'        => 'received',
                    'note'        => $mutation->note
                  ];

                  if (isset($options['attachment_id'])) $payment_to['attachment_id'] = $options['attachment_id'];

                  if (Payment::add($payment_to)) {
                    $this->updateBankMutation($mutation->id, [
                      'status' => 'paid'
                    ]);
                  }
                }

                $validatedCount++;
              }

              $paymentValidated = TRUE;
            }
          }
        }
      }

      if ($paymentValidated) return $validatedCount;
    }
    return FALSE;
  }
}
/* EOF */