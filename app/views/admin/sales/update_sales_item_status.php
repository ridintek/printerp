<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- TIDAK DIGUNAKAN -->
<!-- INI UNTUK SETTING STATUS PER ITEM, BUKAN PER INVOICE. UNTUK UBAH PER INVOICE PILIH DI LIST SALES. -->
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?php echo lang('update_sales_item_status'); ?></h4>
    </div>
    <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
    echo admin_form_open_multipart('sales/update_sales_item_status/' . $sale_item->id, $attrib); ?>
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
      <?php if ($returned) {
      ?>
        <h4><?= lang('sale_x_action'); ?></h4>
      <?php
    } else {
      ?>
      <div class="form-group">
        <?= lang('sales_item_status', 'sales_item_status'); ?>
        <?php
        $slitem = json_decode($sale_item->json_data);

        $all_status = $this->sma->getAllStatus();

        $stat = [];
        foreach ($all_status as $status) {
          if ($slitem->status == 'need_payment') {
            if ($status == 'need_payment') {
              $stat[$status] = lang($status);
            }
          }
          if ($slitem->status == 'waiting_production') {
            if ($status == 'waiting_production' || $status == 'in_production') {
              $stat[$status] = lang($status);
            }
          }
          if ($slitem->status == 'in_production') {
            if ($status == 'in_production' || $status == 'completed') {
              $stat[$status] = lang($status);
            }
          }
          if ($slitem->status == 'completed') {
            if ($status == 'completed') {
              $stat[$status] = lang($status);
            }
          }
          if ($slitem->status == 'delivered') {
            if ($status == 'delivered') {
              $stat[$status] = lang($status);
            }
          }
        }
        ?>
        <?= form_dropdown('status', $stat, $slitem->status, 'class="select2" id="status" required="required" style="width:100%;"'); ?>
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $this->sma->decode_html($inv->note)), 'class="form-control" id="note"'); ?>
      </div>
      <?php
    } ?>

    </div>
    <?php if (!$returned) {
      ?>
    <div class="modal-footer">
      <label class="alert alert-danger" style="display:none" id="msg"></label>
    <?php
      echo form_submit('update', lang('update'), 'class="btn btn-primary" id="update"'); ?>
    </div>
    <?php
    } ?>
  </div>
  <?php echo form_close(); ?>
</div>
<script>
  $(document).ready(function () {
    let item_status = '<?= $slitem->status ?>';
    let payment_status = '<?= $inv->payment_status; ?>';

    if (item_status == 'need_payment' || payment_status == 'pending') {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
      $('#msg').html('Sales payment must be paid full or partial before proceed it to production.').show();
    }

    if (item_status == 'completed') {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
    }
    if (item_status == 'delivered') {
      $('#status').prop('disabled', true);
      $('#update').prop('disabled', true);
    }
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
