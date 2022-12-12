<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Bank
{
  /**
   * Add new bank
   * @param array $data [ *code, *biller_id, *name, number, holder,
   * amount, type(cash|transfer), bic, active(1|0), balance ]
   */
  public function add($data)
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
        $payment = Payment::getRow(['bank_id' => $insertId, 'status' => 'beginning']);

        if ($balance > 0) {
          $paymentData = [
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
            Payment::update($payment->id, $paymentData);
          } else {
            Payment::add($paymentData);
          }
        } else if ($payment) {
          Payment::delete(['id' => $payment->id]);
        }
      }
      return $insertId;
    }
    return FALSE;
  }

  /**
   * Decrease amount.
   * @param int $bankId Bank ID.
   * @param float $amount Amount (positive) to decrease.
   */
  public static function amountDecrease(int $bankId, float $amount)
  {
    return self::update($bankId, ['amount' => self::getRow(['id' => $bankId])->amount - $amount]);
  }

  /**
   * Increase amount.
   * @param int $bankId Bank ID.
   * @param float $amount Amount (positive) to increase.
   */
  public static function amountIncrease(int $bankId, float $amount)
  {
    return self::update($bankId, ['amount' => self::getRow(['id' => $bankId])->amount + $amount]);
  }

  /**
   * Get bank.
   * @param array $clause [ id, code, biller_id, name, number, holder, type, bic, active ]
   */
  public static function getRow($clause = [])
  {
    if ($rows = self::get($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  /**
   * Get banks.
   * @param array $clause [ id, code, biller_id, name, number, holder, type, bic, active ]
   */
  public static function get($clause = [])
  {
    $qb = DB::table('banks');

    if (!empty($clause['holder'])) {
      $qb->like('holder', $clause['holder'], 'none');
      unset($clause['holder']);
    }
    if (!empty($clause['code'])) {
      $qb->like('code', $clause['code'], 'none');
      unset($clause['code']);
    }
    if (!empty($clause['name'])) {
      $qb->like('name', $clause['name'], 'none');
      unset($clause['name']);
    }

    return $qb->get($clause);
  }

  public static function syncBankAmount(int $bankId)
  {
    $balance = Payment::getPaidBalance($bankId);
  }

  /**
   * Add new bank
   * @param array $data [ *code, *biller_id, *name, number, holder,
   * amount, type(cash|transfer), bic, active(1|0) ]
   */
  public static function update($bankId, $data)
  {
    DB::table('banks')->update($data, ['id' => $bankId]);
    return DB::affectedRows();
  }
}
