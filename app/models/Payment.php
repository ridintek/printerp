<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payment
{
  /**
   * Add new payment.
   * If **amount** is plus the type is received, sent if minus.
   * 
   * @param array $data [date, *(expense_id, income_id, mutation_id, sale_id, purchase_id, transfer_id),
   *  *bank_id, *method(cash/transfer), *amount, created_by, attachment, *status(paid/pending),
   *  type(sent/received), note ]
   */
  public static function add($data)
  {
    $data = setCreatedBy($data);

    $hMutex = mutexCreate('Payment_Add', TRUE);

    if (isset($data['expense_id'])) {
      $inv = Expense::getRow(['id' => $data['expense_id']]);
    } else if (isset($data['income_id'])) {
      $inv = Income::getRow(['id' => $data['income_id']]);
    } else if (isset($data['sale_id'])) {
      $inv = Sale::getRow(['id' => $data['sale_id']]);
    } else if (isset($data['purchase_id'])) {
      $inv = Purchase::getRow(['id' => $data['purchase_id']]);
    } else if (isset($data['transfer_id'])) {
      $inv = ProductTransfer::getRow(['id' => $data['transfer_id']]);
    } else if (isset($data['mutation_id'])) {
      $inv = BankMutation::getRow(['id' => $data['mutation_id']]);
    }

    $bank = Bank::getRow(['id' => $data['bank_id']]);

    if (!$bank) {
      setLastError('Bank is not valid.');
      mutexRelease($hMutex);
      return FALSE;
    }

    // If type is not defined, sent or received depended on amount.
    $data['type'] = ($data['type'] ?? ($data['amount'] < 0 ? 'sent' : 'received'));

    $data['reference_date'] = ($data['reference_date'] ?? $inv->created_at);
    $data['reference']      = $inv->reference;
    $data['biller_id']      = $bank->biller_id;

    DB::table('payments')->insert($data);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();

      if ($data['type'] == 'received') {
        Bank::amountIncrease((int)$bank->id, floatval($data['amount']));
      } else if ($data['type'] == 'sent') {
        Bank::amountDecrease((int)$bank->id, floatval($data['amount']));
      } else {
        setLastError('Amount is zero.');
      }

      mutexRelease($hMutex);
      return $insertId;
    }

    mutexRelease($hMutex);
    return FALSE;
  }

  /**
   * Add new payment. DON'T USE IT!
   * If **amount** is plus the type is received, sent if minus.
   * 
   * @param array $data [date, *(expense_id, income_id, mutation_id, sale_id, purchase_id, transfer_id),
   *  *bank_id, *method(cash/transfer), *amount, created_by, attachment, *status(paid/pending),
   *  type(sent/received), note ]
   */
  public static function addOld($data)
  {
    $data = setCreatedBy($data);

    if (isset($data['expense_id'])) {
      $inv = Expense::getRow(['id' => $data['expense_id']]);
    } else if (isset($data['income_id'])) {
      $inv = Income::getRow(['id' => $data['income_id']]);
    } else if (isset($data['sale_id'])) {
      $inv = Sale::getRow(['id' => $data['sale_id']]);
    } else if (isset($data['purchase_id'])) {
      $inv = Purchase::getRow(['id' => $data['purchase_id']]);
    } else if (isset($data['pt_id'])) {
      // Since ProductTransfer using same transfer_id in payments. We filtered it.
      $inv = ProductTransfer::getRow(['id' => $data['pt_id']]);
      $data['transfer_id'] = $data['pt_id'];
      unset($data['pt_id']);
    } else if (isset($data['transfer_id'])) {
      $inv = Transfer::getRow(['id' => $data['transfer_id']]);
    } else if (isset($data['mutation_id'])) {
      $inv = BankMutation::getRow(['id' => $data['mutation_id']]);
    }

    $bank = Bank::getRow(['id' => $data['bank_id']]);

    $data['type'] = ($data['type'] ?? ($data['amount'] > 0 ? 'received' : 'sent')); // As info only.

    $data['reference'] = $inv->reference;
    $data['biller_id'] = $bank->biller_id;
    // $data['created_by'] = ($data['created_by'] ?? );

    DB::table('payments')->insert($data);

    if (DB::affectedRows()) {
      $insertId = DB::insertID();

      if ($data['type'] == 'received') {
        Bank::amountIncrease((int)$bank->id, floatval($data['amount']));
      } else if ($data['type'] == 'sent') {
        Bank::amountDecrease((int)$bank->id, floatval($data['amount']));
      }

      return $insertId;
    }
    return FALSE;
  }

  /**
   * Delete payments.
   * @param array $clause [ id ]
   * @return int Return total deleted rows. 0 if no rows deleted.
   */
  public static function delete($clause = [])
  {
    DB::table('payments')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get payments.
   * @param array $clause [ id, expense_id, income_id, mutation_id, purchase_id, sale_id, transfer_id,
   *   bank_id, biller_id, method, status, start_date, end_date, order[column,sort(asc|desc)] ]
   */
  public static function get($clause = [])
  {
    $qb = DB::table('payments');

    if (!empty($clause['start_date'])) {
      $qb->where("created_at >= '{$clause['start_date']} 00:00:00'");
      unset($clause['start_date']);
    }
    if (!empty($clause['end_date'])) {
      $qb->where("created_at <= '{$clause['end_date']} 23:59:59'");
      unset($clause['end_date']);
    }
    if (!empty($clause['has'])) {
      $qb->db->where("{$clause['has']} IS NOT NULL");
      unset($clause['has']);
    }
    if (!empty($clause['method'])) {
      $qb->db->like('method', $clause['method'], 'none');
      unset($clause['method']);
    }
    if (!empty($clause['order'])) {
      $qb->orderBy($clause['order'][0], $clause['order'][1]);
      unset($clause['order']);
    }

    return $qb->get($clause);
  }

  /**
   * Get total paid amount on bank.
   */
  public static function getPaidBalance(int $bankId)
  {
    $payment = DB::table('payments')->selectSum('amount', 'balance')->where(['bank_id' => $bankId])
      ->where('status', 'paid')->getRow();
    return floatval($payment ? $payment->balance : 0);
  }

  /**
   * Get payment.
   * @param array $clause [ id, expense_id, income_id, mutation_id, purchase_id, sale_id, transfer_id,
   *   bank_id, biller_id, method, status, start_date, end_date, order(column|sort) ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public static function update(int $id, array $data)
  {
    DB::table('payments')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }
}
