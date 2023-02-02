<?php

declare(strict_types=1);

class PaymentValidation
{
  /**
   * Add new PaymentValidation.
   * @param array $data [ name, code ]
   */
  public static function add(array $data)
  {
    $uniqueCode = 0;
    $uqcodes = [];

    if (empty($data['expired_date'])) {
      // Default expired: 2 days.
      $data['expired_date'] = date('Y-m-d H:i:s', strtotime('+2 day', strtotime($data['date'])));
      $data['expired_at'] = $data['expired_date'];
    } else {
      $data['expired_at'] = $data['expired_date'];
    }

    if (!empty($data['unique_code']) && is_numeric($data['unique_code'])) {
      $uniqueCode = $data['unique_code'];
    }

    if (!$uniqueCode) {
      $uniqueCode = generateUniquePaymentCode();

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
            $uniqueCode = generateUniquePaymentCode();
          }
        }
      }
    }

    if (isset($data['mutation_id'])) {
      $mutation = BankMutation::getRow(['id' => $data['mutation_id']]);
      $data['mutation'] = $mutation->reference;
    }

    if (isset($data['sale_id'])) {
      $sale = Sale::getRow(['id' => $data['sale_id']]);
      $data['sale'] = $sale->reference;
    }

    $data['unique_code']  = $uniqueCode;
    $data['unique']       = $uniqueCode;
    $data['status']       = 'pending';

    $biller = Biller::getRow(['id' => ($data['biller_id'] ?? XSession::get('biller_id'))]);

    if (!$biller) {
      setLastError('Biller is not found.');
      return false;
    }

    $data['biller_id']  = $biller->id;
    $data['biller']     = $biller->code;

    $data = setCreatedBy($data);

    DB::table('payment_validations')->insert($data);

    if (DB::affectedRows()) {
      return DB::insertID();
    }

    setLastError(DB::error()['message']);
    return FALSE;
  }

  /**
   * Delete PaymentValidation.
   * @param array $clause [ id, name, code ]
   */
  public static function delete(array $clause)
  {
    DB::table('payment_validations')->delete($clause);
    return DB::affectedRows();
  }

  /**
   * Get PaymentValidation collections.
   * @param array $clause [ id, name, code ]
   */
  public static function get($clause = [])
  {
    return DB::table('payment_validations')->get($clause);
  }

  /**
   * Get PaymentValidation row.
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
   * Select PaymentValidation.
   * @param string $columns Select columns.
   * @param bool $escape Escape string (Default: TRUE).
   */
  public static function select(string $columns, $escape = TRUE)
  {
    return DB::table('payment_validations')->select($columns, $escape);
  }

  public static function sync()
  {
    $synced = FALSE;

    $pending_payments = self::get(['status' => 'pending']);

    if ($pending_payments) {
      foreach ($pending_payments as $pp) {
        if (time() > strtotime($pp->expired_date)) { // Expired
          self::update((int)$pp->id, ['status' => 'expired']);
          if ($pp->sale_id) {
            Sale::update((int)$pp->sale_id, ['payment_status' => 'expired']);
            Sale::sync((int)$pp->sale_id);
          }
          if ($pp->mutation_id) {
            self::update((int)$pp->mutation_id, ['status' => 'expired']);
          }
          $synced = TRUE;
        }
      }
    }

    /* Set payment_status to pending or partial if sale payment_status == waiting_transfer but no payment validation. */
    $sales = Sale::get(['payment_status' => 'waiting_transfer']);

    if ($sales) {
      foreach ($sales as $sale) {
        $pv = self::getRow(['sale_id' => $sale->id]);

        if (!$pv && ($sale->paid == 0)) {
          Sale::update((int)$sale->id, ['payment_status' => 'pending']);
        } else if (!$pv && ($sale->paid > 0 && $sale->paid < $sale->grand_total)) {
          Sale::update((int)$sale->id, ['payment_status' => 'partial']);
        }

        Sale::sync(['sale_id' => $sale->id]);
      }
    }

    return $synced;
  }

  /**
   * Update PaymentValidation.
   * @param int $id PaymentValidation ID.
   * @param array $data [ name, code ]
   */
  public static function update(int $id, array $data)
  {
    DB::table('payment_validations')->update($data, ['id' => $id]);
    return DB::affectedRows();
  }

  public static function validate($response, $options = [])
  {
    $createdAt = date('Y-m-d H:i:s');

    $paymentValidated = FALSE;
    self::sync(); // Change pending payment to expired if any.
    $sale_id     = ($options['sale_id'] ?? NULL);
    $mutation_id = ($options['mutation_id'] ?? NULL);

    if (!empty($options['manual'])) {
      $status = ($sale_id || $mutation_id ? ['expired', 'pending'] : 'pending');
    } else {
      $status = ($sale_id || $mutation_id ? ['pending'] : 'pending'); // New
    }
    $paymentValidation = self::select('*')->whereIn('status', $status)->get();
    $validatedCount = 0;

    $mutasibanks = DB::table('mutasibank')->get(['status' => 'pending']);

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
            $bank = Bank::getRow(['number' => $accountNo, 'biller_id' => $pv->biller_id]);

            if (!$bank) {
              echo ("Bank {$accountNo}, biller id {$pv->biller_id} is not defined");
              die;
            }

            foreach ($mutasibanks as $mb) {
              $mbData = getJSON($mb->data);

              foreach ($mbData->data_mutasi as $dmb) {
                if ($dmb->amount == $dm->amount) {
                  DB::table('mutasibank')->update([
                    'status'    => 'validated',
                    'validated' => intval($mb->validated) + 1
                  ], ['id' => $mb->id]);
                }
              }
            }

            $pvData = [
              'bank'              => $bank->code,
              'bank_id'           => $bank->id,
              'transaction_at'    => $dm->transaction_date,
              'transaction_date'  => $dm->transaction_date,
              'description'       => $dm->description,
              'note'              => $dm->description,
              'status'            => 'verified'
            ];

            if (!empty($options['manual'])) {
              $pvData = setCreatedBy($pvData);
              $pvData['verified_at'] = NULL; // Manual not verified automatically.
              $pvData['description'] = '(MANUAL) ' . $pvData['description'];
              $pvData['note'] = $pvData['description'];
            } else {
              $pvData['verified_at'] = date('Y-m-d H:i:s');
            }

            if (self::update((int)$pv->id, $pvData)) {
              if ($pv->sale_id) { // If sale_id exists.
                $sale = Sale::getRow(['id' => $pv->sale_id]);

                $payment = [
                  'reference_date'  => $sale->created_at,
                  'reference'       => $sale->reference,
                  'sale_id'         => $pv->sale_id,
                  'amount'          => $pv->amount,
                  'method'          => 'Transfer',
                  'bank_id'         => $bank->id,
                  'created_at'      => $createdAt,
                  'created_by'      => $pv->created_by,
                  'type'            => 'received'
                ];

                if (isset($options['attachment'])) $payment['attachment'] = $options['attachment'];

                Sale::addPayment($payment); // Add real payment to sales.
                $customer = Customer::getRow(['id' => $sale->customer_id]);

                if ($customer && $amount_match) { // Restore unique code as deposit for customer if amount match.
                  Customer::update((int)$sale->customer_id, [
                    'deposit_amount' => $customer->deposit_amount + $pv->unique_code
                  ]);
                }

                $validatedCount++;
              }

              if ($pv->mutation_id) { // If mutation_id exists.
                $mutation = BankMutation::getRow(['id' => $pv->mutation_id]);
                $payment_from = [
                  'created_at'      => date('Y-m-d H:i:s'),
                  'reference_date'  => $mutation->date,
                  'mutation_id'     => $mutation->id,
                  'bank_id'         => $mutation->from_bank_id,
                  'method'          => 'Transfer',
                  'amount'          => $mutation->amount + $pv->unique_code,
                  'created_by'      => $mutation->created_by,
                  'type'            => 'sent',
                  'note'            => $mutation->note
                ];

                if (isset($options['attachment'])) $payment_from['attachment'] = $options['attachment'];

                if (Payment::add($payment_from)) {
                  $payment_to = [
                    'created_at'  => date('Y-m-d H:i:s'),
                    'date'        => $mutation->date,
                    'mutation_id' => $mutation->id,
                    'bank_id'     => $mutation->to_bank_id,
                    'method'      => 'Transfer',
                    'amount'      => $mutation->amount + $pv->unique_code,
                    'created_by'  => $mutation->created_by,
                    'type'        => 'received',
                    'note'        => $mutation->note
                  ];

                  if (isset($options['attachment'])) $payment_to['attachment'] = $options['attachment'];

                  if (Payment::add($payment_to)) {
                    BankMutation::update((int)$mutation->id, [
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
