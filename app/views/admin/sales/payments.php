<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
        <i class="fa fa-print"></i> <?= lang('print'); ?>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?= lang('view_payments') . ' (' . lang('sale') . ' ' . lang('reference') . ': ' . $inv->reference . ')'; ?></h4>
    </div>
    <div class="modal-body">
      <div class="table-responsive">
        <table id="CompTable" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped">
          <thead>
            <tr>
              <th style="width:30%;"><?= $this->lang->line('date'); ?></th>
              <th style="width:30%;"><?= $this->lang->line('reference'); ?></th>
              <th style="width:30%;"><?= $this->lang->line('bank_name'); ?></th>
              <th style="width:30%;"><?= $this->lang->line('account_no'); ?></th>
              <th style="width:30%;"><?= $this->lang->line('account_type'); ?></th>
              <th style="width:30%;"><?= $this->lang->line('created_by'); ?></th>
              <th style="width:15%;"><?= $this->lang->line('amount'); ?></th>
              <th style="width:15%;"><?= $this->lang->line('method'); ?></th>
              <th style="width:10%;"><?= $this->lang->line('attachment'); ?></th>
              <th style="width:10%;"><?= $this->lang->line('actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($payments)) {
              foreach ($payments as $payment) {
                $bank = $this->site->getBankByID($payment->bank_id);
            ?>
                <tr class="row<?= $payment->id ?>">
                  <td class="col-md-2"><?= $this->sma->hrld($payment->date); ?></td>
                  <td class="col-md-2"><?= $payment->reference; ?></td>
                  <td class="col-md-2"><?= $bank->name; ?></td>
                  <td><?= $bank->number; ?></td>
                  <td><?= $bank->type; ?></td>
                  <td><?= $payment->creator; ?></td>
                  <td><?= $this->sma->formatMoney($payment->amount); ?></td>
                  <td><?= lang(strtolower($payment->method)); ?></td>
                  <td>
                    <?php if ($payment->attachment) { ?>
                      <a href="<?= admin_url('gallery/view?name=' . $payment->attachment . '&path=sales_payments'); ?>" class="fas fa-paperclip" data-toggle="modal" data-target="#myModal2"></a>
                    <?php } ?>
                  </td>
                  <td>
                    <div class="text-center">
                      <!-- <a href="<?= admin_url('sales/payment_note/' . $payment->id) ?>" data-toggle="modal" data-target="#myModal2"><i class="fa fa-file"></i></a> -->
                      <a href="<?= admin_url('sales/edit_payment/' . $payment->id) ?>" data-toggle="modal" data-target="#myModal2"><i class="fa fa-edit"></i></a>
                      <a href="#" class="deletePayment" data-payment-id="<?= $payment->id; ?>"><i class="fad fa-trash"></i></a>
                    </div>
                  </td>
                </tr>
            <?php
              }
            } else {
              echo '<tr><td colspan="10" class="text-center">' . lang('no_data_available') . '</td></tr>';
            } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    $(document).on('click', '.deletePayment', function(e) {
      e.preventDefault();

      addConfirm({
        title: 'Delete Payment',
        message: 'Are you sure to delete payment?',
        onok: () => {
          let data = {};

          data[security.csrf_token_name] = security.csrf_hash;
          data.id = $(this).data('payment-id');

          $.ajax({
            data: data,
            method: 'POST',
            success: (data) => {
              if (isObject(data) && !data.error) {
                addAlert(data.msg, 'success');
                if (oTable) oTable.fnDraw(false);
              } else if (isObject(data) && data.error) {
                addAlert(data.msg, 'danger');
              } else {
                addAlert('Unknown error', 'danger');
              }
            },
            url: site.base_url + 'sales/deletePayment'
          });
        }
      });
    });

    $(document).on('click', '.email_payment', function(e) {
      e.preventDefault();
      var link = $(this).attr('href');
      $.get(link, function(data) {
        bootbox.alert(data.msg);
      });
      return false;
    });
  });
</script>