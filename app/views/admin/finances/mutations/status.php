<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('mutation_detail'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open_multipart('mutations/status/' . $mutation->id, $attrib); ?>
  <div class="modal-body">
    <div class="panel panel-default">
      <div class="panel-heading">
        <?= lang('mutation_details'); ?>
      </div>
      <div class="panel-body">
        <table class="table table-condensed table-striped table-borderless" style="margin-bottom:0;">
          <tbody>
            <tr>
              <td><?= lang('reference'); ?></td>
              <td><strong><?= $mutation->reference; ?></strong></td>
            </tr>
            <tr>
              <td><?= lang('created_date'); ?></td>
              <td><?= $mutation->date; ?></td>
            </tr>
            <tr>
              <td><?= lang('paid_by'); ?></td>
              <td><?= $mutation->paid_by; ?></td>
            </tr>
            <tr>
              <td><?= lang('from') . ' ' . lang('bank'); ?></td>
              <td><?= $mutation->from_bank_name; ?></td>
            </tr>
            <tr>
              <td><?= lang('to') . ' ' . lang('bank'); ?></td>
              <td><?= $mutation->to_bank_name; ?></td>
            </tr>
            <tr>
              <td><?= lang('amount'); ?></td>
              <td><?= $this->sma->formatMoney($mutation->amount, 'none'); ?></td>
            </tr>
            <?php if ($payment_validation) { ?>
              <tr>
                <td><?= lang('unique_code'); ?></td>
                <td><?= $payment_validation->unique_code; ?></td>
              </tr>
              <tr>
                <td><?= lang('transfer_amount'); ?></td>
                <td><strong><?= $this->sma->formatMoney($payment_validation->amount + $payment_validation->unique_code, 'none'); ?></strong></td>
              </tr>
              <tr>
                <td><?= lang('expired_date'); ?></td>
                <td><?= $payment_validation->expired_date; ?></td>
              </tr>
              <?php if ($payment_validation->status == 'verified') { ?>
                <tr>
                  <td><?= lang('transaction_date'); ?></td>
                  <td><?= $payment_validation->transaction_date; ?></td>
                </tr>
              <?php } ?>
              <?php if ($payment_validation->status == 'pending') { ?>
                <tr>
                  <td><?= lang('expired_in'); ?></td>
                  <td><span id="expired_timer"></span></td>
                </tr>
            <?php }
            } ?>
            <tr>
              <td><?= lang('status'); ?></td>
              <td><strong><?= lang($mutation->status); ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="modal-footer">
  </div>
</div>
<?php echo form_close(); ?>
<script>
  $(document).ready(function() {
    let status = '<?= $mutation->status ?>';
    if (status == 'waiting_transfer') {
      <?php if ($payment_validation && $payment_validation->status == 'pending') { ?>
        let current_date = Date.now();
        let expired_date = Date.parse('<?= $payment_validation->expired_date; ?>');
        let timer = new Timer(Math.floor(expired_date - current_date) / 1000);

        document.getElementById('expired_timer').innerHTML = timer.getHours() + ':' + timer.getMinutes() + ':' + timer.getSeconds();

        hExpired = window.setInterval(() => {
          current_date = Date.now();
          timer.setMiliseconds(expired_date - current_date);
          document.getElementById('expired_timer').innerHTML = timer.getHours() + ':' + timer.getMinutes() + ':' + timer.getSeconds();
        }, 500);

        $('#myModal').on('hide.bs.modal', (e) => {
          clearInterval(hExpired);
        });
      <?php } ?>
    }
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>