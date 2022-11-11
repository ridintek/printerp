<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('update_sales_status'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('sales/update_sales_status/' . $inv->id, $attrib); ?>
    <div class="modal-body">
      <p><?= lang('enter_info'); ?></p>
      <div class="panel panel-default">
        <div class="panel-heading">
          <?= lang('sale_details'); ?>
        </div>
        <div class="panel-body">
          <table class="table table-condensed table-striped table-borderless" style="margin-bottom:0;">
            <tbody>
              <tr>
                <td><?= lang('reference'); ?></td>
                <td><?= $inv->reference; ?></td>
              </tr>
              <tr>
                <td><?= lang('biller'); ?></td>
                <td><?= $inv->biller; ?></td>
              </tr>
              <tr>
                <td><?= lang('customer'); ?></td>
                <td><?= $inv->customer; ?></td>
              </tr>
              <tr>
                <td><?= lang('status'); ?></td>
                <td><strong><?= lang($inv->status); ?></strong></td>
              </tr>
              <tr>
                <td><?= lang('payment_status'); ?></td>
                <td><?= lang($inv->payment_status); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="form-group">
        <?= lang('status', 'status'); ?>
        <?php
        $stat = [];
        if ($inv->status == 'need_payment') {
          $stat['need_payment'] = lang('need_payment');

          if ($this->Owner || $this->Admin) {
            $stat['waiting_production'] = lang('waiting_production');
          }
        }
        if ($inv->status == 'waiting_production') {
          $stat['waiting_production'] = lang('waiting_production');
        }
        if ($inv->status == 'completed') {
          $stat['completed'] = lang('completed');
          $stat['finished'] = lang('finished');
        }
        if ($inv->status == 'finished') {
          $stat['delivered'] = lang('delivered');
          $stat['finished'] = lang('finished');
        }
        if ($inv->status == 'delivered') { // Final status
          $stat['delivered'] = lang('delivered');
        }
        if ($inv->status == 'draft') {
          $stat['draft'] = lang('draft');
        }
        ?>
        <?= form_dropdown('status', $stat, $inv->status, 'class="select2" id="status" required="required" style="width:100%;"'); ?>
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $this->sma->decode_html($inv->note)), 'class="form-control" id="note"'); ?>
      </div>

    </div>
    <div class="modal-footer">
      <label class="alert" style="display:none" id="msg"></label>
      <?php
      echo form_submit('update', lang('update'), 'class="btn btn-primary" id="update"'); ?>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<script>
  $(document).ready(function() {
    // Do not use 'payment_status', because it's global function.
    let pay_status = '<?= $inv->payment_status ?>';
    let status = '<?= $inv->status ?>';
    let customer_group = '<?= strtolower($customer->customer_group_name); ?>';
    let is_authorized = '<?= ($this->Owner || $this->Admin ? 1 : 0) ?>';

    if (status == 'need_payment' && !is_authorized) {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
      $('#msg').addClass('alert-danger');
      $('#msg').html('Nota harus dibayar sebagian atau lunas sebelum dapat diproduksi.').show();
    } else
    if (customer_group != 'top' && pay_status != 'paid' && !is_authorized) {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
      $('#msg').addClass('alert-danger');
      $('#msg').html('Pembayaran nota harus lunas sebelum dapat dikirim.').show();
    } else
    if (status == 'completed') {
      $('#msg').addClass('alert-info');
      $('#msg').html('Silakan ubah ke <strong>Finished</strong> jika siap untuk diambil pelanggan.').show();
    } else
    if (status == 'finished') {
      $('#msg').addClass('alert-info');
      $('#msg').html('Silakan ubah ke <strong>Delivered</strong> jika sudah diambil pelanggan.').show();
    } else
    if (status == 'delivered') {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
      $('#msg').addClass('alert-success');
      $('#msg').html('Item telah dikirimkan dan selesai.').show();
    } else
    if (status == 'waiting_production') {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
      $('#msg').addClass('alert-danger');
      $('#msg').html('Item nota harus diselesaikan oleh operator terlebih dahulu sebelum dikirim.').show();
    }
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>