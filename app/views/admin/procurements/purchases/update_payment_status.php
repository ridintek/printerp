<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
				<i class="fad fa-times"></i>
			</button>
			<h4 class="modal-title" id="myModalLabel"><?php echo lang('update_payment_status'); ?></h4>
		</div>
		<?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
		echo admin_form_open_multipart('procurements/purchases/update_payment_status/' . $payment->id, $attrib); ?>
		<div class="modal-body">
			<p><?= lang('enter_info'); ?></p>
			<div class="panel panel-default">
				<div class="panel-heading">
					<?= lang('purchase_details'); ?>
				</div>
				<div class="panel-body">
					<table class="table table-condensed table-striped table-borderless" style="margin-bottom:0;">
						<tbody>
						<tr>
								<td><?= lang('date'); ?></td>
								<td><?= $payment->date; ?></td>
							</tr>
							<tr>
								<td><?= lang('reference'); ?></td>
								<td><?= $payment->reference; ?></td>
							</tr>
							<tr>
								<td><?= lang('purchase_reference'); ?></td>
								<td><?= $purchase->reference; ?></td>
							</tr>
							<tr>
								<td><?= lang('created_by'); ?></td>
								<td><?= $user->first_name . ' ' . $user->last_name; ?></td>
							</tr>
							<tr>
								<td><?= lang('amount'); ?></td>
								<td><?= $this->sma->formatMoney($payment->amount); ?></td>
							</tr>
							<?php if ($purchase->discount) { ?>
							<tr>
								<td><?= lang('discount'); ?></td>
								<td><?= $this->sma->formatMoney($purchase->discount); ?></td>
							</tr>
							<?php } ?>
							<tr>
								<td><?= lang('paid_by'); ?></td>
								<td><?= $bank->name; ?></td>
							</tr>
							<tr>
								<td><?= lang('status'); ?></td>
								<td><strong><?= lang($payment->status); ?></strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="form-group">
				<?= lang('status', 'status'); ?>
				<?php
				$all_status = $this->sma->getAllStatus();

				$opts[''] = '';
				foreach ($all_status as $status) {
					if ($payment->status == 'need_approval') {
						if ($Admin || $Owner || $GP['purchases-approval']) {
							if ($status == 'approved') {
								$opts[$status] = lang($status);
							}
						}
						if ($status == 'need_approval') {
							$opts[$status] = lang($status);
						}
					} else if ($payment->status == 'approved') {
						if ($status == 'approved' || $status == 'paid') {
							$opts[$status] = lang($status);
						}
					} else if ($payment->status == 'paid') {
						if ($status == 'paid') {
							$opts[$status] = lang($status);
						}
					}
				}
				
				echo form_dropdown('status', $opts, $payment->status, 'class="select2" id="status" required="required" style="width:100%;"'); ?>
			</div>

			<div class="form-group">
				<?= lang('note', 'note'); ?>
				<?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $this->sma->decode_html($payment->note)), 'class="form-control" id="note"'); ?>
			</div>

		</div>
		<div class="modal-footer">
			<?php echo form_submit('update', lang('update'), 'class="btn btn-primary" id="update"'); ?>
		</div>
	</div>
	<?php echo form_close(); ?>
</div>
<script>
	$(document).ready(function () {
		let status = '<?= $payment->status; ?>';
		if (status == 'paid') {
			$('#status').prop('disabled', true);
			$('#update').prop('disabled', true);
		}
	});
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>