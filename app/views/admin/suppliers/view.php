<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
			<i class="fad fa-times"></i>
		</button>
		<h4 class="modal-title" id="myModalLabel"><?= $supplier->company && $supplier->company != '-' ? $supplier->company : $supplier->name; ?></h4>
	</div>
	<div class="modal-body">
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom:0;">
				<tbody>
				<tr>
					<td><strong><?= lang('company'); ?></strong></td>
					<td><?= $supplier->company; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('name'); ?></strong></td>
					<td><?= $supplier->name; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('phone'); ?></strong></td>
					<td><?= $supplier->phone; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('email'); ?></strong></td>
					<td><?= $supplier->email; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('address'); ?></strong></td>
					<td><?= $supplier->address; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('city'); ?></strong></td>
					<td><?= $supplier->city; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('postal_code'); ?></strong></td>
					<td><?= $supplier->postal_code; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('country'); ?></strong></td>
					<td><?= $supplier->country; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('payment_term'); ?></strong></td>
					<td><?= $supplier->payment_term; ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('account_holder'); ?></strong></td>
					<td><?= ($json_data->acc_holder ?? ''); ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('account_no'); ?></strong></td>
					<td><?= ($json_data->acc_no ?? ''); ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('bank_name'); ?></strong></td>
					<td><?= ($json_data->acc_name ?? ''); ?></strong></td>
				</tr>
				<tr>
					<td><strong><?= lang('bic_code'); ?></strong></td>
					<td><?= ($json_data->acc_bic ?? ''); ?></strong></td>
				</tr>
				</tbody>
			</table>
		</div>
		<div class="modal-footer no-print">
			<button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?= lang('close'); ?></button>
			<?php if ($Owner || $Admin || $GP['reports-suppliers']) { ?>
				<a href="<?=admin_url('reports/supplier_report/' . $supplier->id); ?>" target="_blank" class="btn btn-primary"><?= lang('suppliers_report'); ?></a>
			<?php } ?>
			<?php if ($Owner || $Admin || $GP['suppliers-edit']) { ?>
				<a href="<?=admin_url('suppliers/edit/' . $supplier->id); ?>" data-toggle="modal" data-target="#myModal2" class="btn btn-primary"><?= lang('edit_supplier'); ?></a>
			<?php	} ?>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
