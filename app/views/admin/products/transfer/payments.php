<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel">Payments <?= $pt->reference ?></h4>
  </div>
  <div class="modal-body">
    <div class="table-responsive">
      <table id="TableModal" class="table table-bordered table-hover table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Payment Date</th>
            <th>Reference Date</th>
            <th>Reference</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Type</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($payments)) :
            foreach ($payments as $payment) :
              $bank = Bank::getRow(['id' => $payment->bank_id]); ?>
              <tr class="row<?= $payment->id ?>">
                <td><?= $payment->id; ?></td>
                <td><?= $payment->created_at; ?></td>
                <td><?= $payment->reference_date; ?></td>
                <td><?= $payment->reference; ?></td>
                <td><?= formatCurrency($payment->amount) . ' ' . ($payment->attachment ? '<a href="' . admin_url('welcome/download/' . $payment->attachment) . '"><i class="fad fa-chain"></i></a>' : ''); ?></td>
                <td><?= $bank->name; ?></td>
                <td><?= lang($payment->type); ?></td>
                <td>
                  <div class="text-center">
                    <a href="<?= admin_url('products/transfer/editPayment/' . $payment->id) ?>" data-toggle="modal" data-target="#myModal2"><i class="fad fa-edit"></i></a>
                    <a href="<?= admin_url('products/transfer/deletePayment/' . $payment->id) ?>" data-action="confirm" data-method="post" data-message="Hapus pembayaran?"><i class="fad fa-trash"></i></a>
                  </div>
                </td>
              </tr>
          <?php
            endforeach;
          else :
            echo '<tr><td class="text-center" colspan="6">' . lang('no_data_available') . '</td></tr>';
          endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {});
</script>