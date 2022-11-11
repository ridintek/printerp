<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?= lang('view_payments') . ' (' . lang('transfer') . ' ' . lang('reference') . ': ' . $inv->reference . ')'; ?></h4>
  </div>
  <div class="modal-body">
    <div class="table-responsive">
      <table id="CompTable" class="table table-bordered table-hover table-striped">
        <thead>
          <tr>
            <th><?= $this->lang->line('date'); ?></th>
            <th><?= $this->lang->line('reference'); ?></th>
            <th><?= $this->lang->line('amount'); ?></th>
            <th><?= $this->lang->line('method'); ?></th>
            <th><?= $this->lang->line('type'); ?></th>
            <th><?= $this->lang->line('actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($payments)) {
            foreach ($payments as $payment) {
              $bank = $this->finances_model->getBankById($payment->bank_id);
          ?>
              <tr class="row<?= $payment->id ?>">
                <td><?= $this->sma->hrld($payment->date); ?></td>
                <td><?= $payment->reference; ?></td>
                <td><?= $this->sma->formatMoney($payment->amount) . ' ' . (($payment->attachment) ? '<a href="' . admin_url('welcome/download/' . $payment->attachment) . '"><i class="fad fa-chain"></i></a>' : ''); ?></td>
                <td><?= $bank->name; ?></td>
                <td><?= lang($payment->type); ?></td>
                <td>
                  <div class="text-center">
                    <a href="<?= admin_url('procurements/transfers/edit_payment/' . $payment->id) ?>" data-toggle="modal" data-target="#myModal2"><i class="fad fa-edit"></i></a>
                    <a href="#" class="po" title="<b><?= $this->lang->line('delete_payment') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' id='<?= $payment->id ?>' href='<?= admin_url('procurements/transfers/delete_payment/' . $payment->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn po-close'><?= lang('no') ?></button>" rel="popover"><i class="fad fa-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
          <?php
            }
          } else {
            echo '<tr><td class="text-center" colspan="6">' . lang('no_data_available') . '</td></tr>';
          } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    $(document).on('click', '.po-delete', function() {
      var id = $(this).attr('id');
      $(this).closest('tr').remove();
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