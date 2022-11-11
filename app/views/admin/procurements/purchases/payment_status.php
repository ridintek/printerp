<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
			</button>
			<h4 class="modal-title" id="myModalLabel"><?php echo lang('purchase_payment_plan'); ?></h4>
		</div>
		<?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
		echo admin_form_open_multipart('procurements/purchases/payment_status/' . $payment_plan->purchase_id, $attrib); ?>
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
								<td><?= $payment_plan->date; ?></td>
							</tr>
							<tr>
								<td><?= lang('reference'); ?></td>
								<td><?= $payment_plan->reference; ?></td>
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
								<td><?= $this->sma->formatMoney($payment_plan->amount); ?></td>
							</tr>
							<tr>
								<td><?= lang('paid_by'); ?></td>
								<td><?= $bank->name; ?></td>
							</tr>
							<tr>
								<td><?= lang('status'); ?></td>
								<td><strong><?= lang($payment_plan->status); ?></strong></td>
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
					if ($payment_plan->status == 'need_approval') {
						if ($status == 'approved' || $status == 'need_approval') {
							$opts[$status] = lang($status);
						}
					} else if ($payment_plan->status == 'approved') {
						if ($status == 'approved' || $status == 'partial' || $status == 'paid') {
							$opts[$status] = lang($status);
						}
					} else if ($payment_plan->status == 'partial') {
						if ($status == 'partial' || $status == 'paid') {
							$opts[$status] = lang($status);
						}
					} else if ($payment_plan->status == 'paid') {
						if ($status == 'paid') {
							$opts[$status] = lang($status);
						}
					}
				}
				
				echo form_dropdown('status', $opts, $payment_plan->status, 'class="form-control" id="status" required="required" style="width:100%;"'); ?>
			</div>

			<div class="form-group">
				<?= lang('note', 'note'); ?>
				<?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : $this->sma->decode_html($payment_plan->note)), 'class="form-control" id="note"'); ?>
			</div>

		</div>
		<div class="modal-footer">
			<?php echo form_submit('update', lang('update'), 'class="btn btn-primary" id="update"'); ?>
		</div>
	</div>
	<?php echo form_close(); ?>
</div>
<script>
	let status = '<?= $payment_plan->status; ?>';
	if (status == 'paid') {
		$('#status').prop('disabled', true);
		$('#update').prop('disabled', true);
	}
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>