<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?php echo lang('payment_status'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('finances/expenses/payment/' . $expense->id, $attrib); ?>
  <div class="modal-body">
    <p><?= lang('enter_info'); ?></p>
    <div class="panel panel-default">
      <div class="panel-heading">
        <?= lang('expense_details'); ?>
      </div>
      <div class="panel-body">
        <table class="table table-condensed table-striped table-borderless" style="margin-bottom:0;">
          <tbody>
            <tr>
              <td><?= lang('reference'); ?></td>
              <td><?= $expense->reference; ?></td>
            </tr>
            <tr>
              <td><?= lang('paid_by'); ?></td>
              <td><?= $bank->name; ?></td>
            </tr>
            <tr>
              <td><?= lang('amount'); ?></td>
              <td><?= $this->sma->formatMoney($expense->amount); ?></td>
            </tr>
            <tr>
              <td><?= lang('created_by'); ?></td>
              <td><?= $user_create->first_name . ' ' . $user_create->last_name; ?></td>
            </tr>
            <tr>
              <td><?= lang('approved_by'); ?></td>
              <td><?= ( ! empty($user_approve) ? $user_approve->first_name . ' ' . $user_approve->last_name : ''); ?></td>
            </tr>
            <tr>
              <td><?= lang('approval_status'); ?></td>
              <td><strong><?= lang($expense->status); ?></strong></td>
            </tr>
            <tr>
              <td><?= lang('payment_status'); ?></td>
              <td><strong><?= lang($expense->payment_status); ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="form-group">
      <?= lang('payment_status', 'payment_status'); ?>
      <?php
      $disabled = FALSE; $msg = '';
      if ($expense->payment_status  == 'paid') {
        $disabled = TRUE;
      }
      if ($expense->status == 'need_approval') {
        $disabled = TRUE;
        $msg = 'Waiting for approval status.';
      }
      $opts = ['pending' => lang('pending'), 'paid' => lang('paid')];
      $dsb = ($disabled ? ' disabled="true"' : '');
      ?>
      <?= form_dropdown('status', $opts, $expense->payment_status, 'class="form-control select2" id="status" required="required" style="width:100%;"' . $dsb); ?>
    </div>
    <div class="form-group">
      <?= lang('note', 'note'); ?>
      <?php echo form_textarea('note', $this->sma->decode_html($expense->note), 'class="form-control" id="note"'); ?>
    </div>
  </div>
  <div class="modal-footer">
  <?php
    if ( ! empty($msg)) {
      echo '<label class="alert alert-danger">' . $msg . '</label> ';
    }
    $dsb = ($disabled ? ' disabled="true"' : '');
    echo form_submit('update', lang('update'), 'class="btn btn-primary"' . $dsb); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
