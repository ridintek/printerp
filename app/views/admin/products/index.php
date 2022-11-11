<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';
$code 			    = $this->input->get('code');
$name 			    = $this->input->get('name');
$type 			    = $this->input->get('type');
$category_code  = $this->input->get('category');
$supplier_id    = $this->input->get('supplier');
$wh_id          = $this->input->get('warehouse');
$start_date     = $this->input->get('start_date');
$end_date       = $this->input->get('end_date');

if ($code) {
	$q .= '&code=' . $type;
}
if ($name) {
	$q .= '&name=' . $type;
}
if ($type) {
	$q .= '&type=' . $type;
}
if ($category_code) {
	$q .= '&category=' . $category_code;
}
if ($supplier_id) {
	$q .= '&supplier=' . $supplier_id;
}
if ($wh_id) {
	$q .= '&warehouse=' . $wh_id;
}
if ($start_date) {
	$q .= '&start_date=' . $start_date;
}
if ($end_date) {
	$q .= '&end_date=' . $end_date;
}
?>
<style type="text/css" media="screen">
	#PRData td:nth-child(7) {
		text-align: right;
	}

	<?php if ($isAdmin || $this->session->userdata('show_cost')) {
	?>#PRData td:nth-child(9) {
		text-align: right;
	}

	<?php
	}
	if ($isAdmin || $this->session->userdata('show_price')) {
	?>#PRData td:nth-child(8) {
		text-align: right;
	}

	<?php
	} ?>
</style>
<script>
	var oTable;
	$(document).ready(function() {
		oTable = $('#PRData').dataTable({
			"aaSorting": [
				[2, "asc"],
				[3, "asc"]
			],
			"aLengthMenu": [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, "<?= lang('all') ?>"]
			],
			"iDisplayLength": <?= $Settings->rows_per_page ?>,
			'bProcessing': true,
			'bServerSide': true,
			'sAjaxSource': '<?= admin_url('products/getProducts' . $q) ?>',
			'fnServerData': function(sSource, aoData, fnCallback) {
				aoData.push({
					"name": "<?= $this->security->get_csrf_token_name() ?>",
					"value": "<?= $this->security->get_csrf_hash() ?>"
				});
				$.ajax({
					'dataType': 'json',
					'type': 'POST',
					'url': sSource,
					'data': aoData,
					'success': fnCallback
				});
			},
			'fnRowCallback': function(nRow, aData, iDisplayIndex) {
				nRow.id = aData[0];
				nRow.className = "product_link";

				<?php if (!empty($start_date)) { ?>
					nRow.dataset.startDate = '<?= $start_date ?>';
					nRow.dataset.endDate = '<?= $end_date ?>';
				<?php } ?>

				if (aData[4] == 'standard' && aData[8] <= 0) { // If Quantity is zero
					nRow.classList.add('danger');
				} else if (aData[4] == 'standard' && (aData[8] <= aData[10])) { // If Quantity below Safety Stock
					nRow.classList.add('warning');
				}
				return nRow;
			},
			"aoColumns": [{
					"bSortable": false,
					"mRender": checkbox
				}, {
					"bSortable": false,
					"mRender": img_hl
				}, null, null, null, null,
				<?php if ($isAdmin) {
					echo '{"mRender": currencyFormat},';
					echo '{"mRender": currencyFormat},';
					echo '{"mRender": formatStock},';
				} else {
					if ($this->session->userdata('show_cost')) {
						echo '{"mRender": currencyFormat},';
					}
					if ($this->session->userdata('show_cost')) {
						echo '{"mRender": currencyFormat},';
					}
					if (getPermission('products-quantity')) {
						echo '{"mRender": formatStock},';
					}
				} ?> null, {
					"mRender": formatStock
				}, {
					"bSortable": false
				}
			]
		});

		$('#filter').click((e) => {
			if ($('#form_filter').hasClass('closed')) {
				$('#form_filter').removeClass('closed');
				$('#form_filter').addClass('opened');
				$('#form_filter').slideDown();
			} else if ($('#form_filter').hasClass('opened')) {
				$('#form_filter').removeClass('opened');
				$('#form_filter').addClass('closed');
				$('#form_filter').slideUp();
			}
			e.preventDefault();
		});

		$('#dtfilter').datatableFilter();

		$('body').on('click', '#delete', function(e) {
			e.preventDefault();
			let values = [];
			$('.bpo').popover('hide');
			$('input[name="val[]"]').each(function() {
				if (this.checked) values.push(this.value);
			});
			$.ajax({
				data: {
					<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
					form_action: 'delete',
					val: values
				},
				method: 'POST',
				success: function(data) {
					if (!data.error) {
						if (oTable) oTable.fnDraw(false);
						addAlert(data.msg, 'success');
					} else {
						addAlert(data.msg, 'danger');
					}
				},
				url: '<?= admin_url('products/product_actions'); ?>'
			});
		});

		$('#activate_product').click(function(e) {
			e.preventDefault();
			let values = [];

			$('input[name="val[]"]').each(function() {
				if (this.checked) values.push(this.value);
			});

			$.ajax({
				data: {
					<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
					form_action: 'activate',
					val: values
				},
				method: 'POST',
				success: function(data) {
					console.log(data);
					if (typeof data == 'object' && !data.error) {
						if (oTable) oTable.fnDraw(false);
						addAlert(data.msg, 'success');
					} else if (typeof data == 'object') {
						addAlert(data.msg, 'danger');
					} else {
						addAlert('Unknown error. Response is not an object.', 'danger');
					}
				},
				url: site.base_url + 'products/product_actions'
			});
		});

		$('#deactivate_product').click(function(e) {
			e.preventDefault();
			let values = [];

			$('input[name="val[]"]').each(function() {
				if (this.checked) values.push(this.value);
			});

			$.ajax({
				data: {
					<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
					form_action: 'deactivate',
					val: values
				},
				method: 'POST',
				success: function(data) {
					console.log(data);
					if (typeof data == 'object' && !data.error) {
						if (oTable) oTable.fnDraw(false);
						addAlert(data.msg, 'success');
					} else if (typeof data == 'object') {
						addAlert(data.msg, 'danger');
					} else {
						addAlert('Unknown error. Response is not an object.', 'danger');
					}
				},
				url: site.base_url + 'products/product_actions'
			});
		});

		$('#sync_quantity').click(function(e) {
			e.preventDefault();
			let values = [];
			$('input[name="val[]"]').each(function() {
				if (this.checked) values.push(this.value);
			});

			addConfirm({
				message: 'Are you sure to sync products?',
				onok: function() {
					$.ajax({
						data: {
							<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
							form_action: 'sync_quantity',
							val: values
						},
						method: 'POST',
						success: function(data) {
							console.log(data);
							if (typeof data == 'object' && !data.error) {
								if (oTable) oTable.fnDraw(false);
								addAlert(data.msg, 'success');
							} else if (typeof data == 'object') {
								addAlert(data.msg, 'danger');
							} else {
								addAlert('Unknown error. Response is not an object.', 'danger');
							}
						},
						url: '<?= admin_url('products/product_actions'); ?>'
					});
				},
				title: 'Confirm sync products',
			});
		});

		$('#myModal').on('hidden.bs.modal', function() {
			if (typeof mainTable != 'undefined') oTable = mainTable;
		});
	});
</script>

<div class="box">
	<div class="box-header">
		<h2 class="blue"><i class="fa-fw fa fa-box-up"></i><?= lang('products'); ?>
			<?= (isset($start_date) ? "({$start_date} To {$end_date})" : ''); ?>
		</h2>

		<div class="box-icon">
			<ul class="btn-tasks">
				<li class="dropdown">
					<a data-toggle="dropdown" class="dropdown-toggle" href="#">
						<i class="icon fa fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
					</a>
					<ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
						<li>
							<a href="<?= admin_url('products/add') ?>">
								<i class="fa fa-fw fa-plus-circle"></i> <?= lang('add_product') ?>
							</a>
						</li>
						<?php if ($isAdmin || !$this->session->userdata('warehouse_id')) { ?>
							<li>
								<a href="#" id="activate_product" data-action="activate_product">
									<i class="fa fa-fw fa-check"></i> <?= lang('activate_product') ?>
								</a>
							</li>
							<li>
								<a href="#" id="deactivate_product" data-action="deactivate_product">
									<i class="fa fa-fw fa-times"></i> <?= lang('deactivate_product') ?>
								</a>
							</li>
							<li>
								<a href="#" id="sync_quantity" data-action="sync_quantity">
									<i class="fa fa-fw fa-sync-alt"></i> <?= lang('sync_quantity') ?>
								</a>
							</li>
						<?php } ?>
						<li>
							<a href="#" id="excel" data-action="export_excel">
								<i class="fa fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
							</a>
						</li>
						<li class="divider"></li>
						<li>
							<a href="#" class="bpo" title="<b><?= $this->lang->line('delete_products') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
								<i class="fa fa-fw fa-trash"></i> <?= lang('delete_products') ?>
							</a>
						</li>
					</ul>
				</li>
				<li class="dropdown">
					<a href="#" id="filter" title="Filter"><i class="icon fa fa-filter"></i></a>
				</li>
			</ul>
		</div>
	</div>
	<div class="box-content">
		<div class="row">
			<div class="col-lg-12">
				<p class="introtext"><strong><?= lang('warehouse'); ?></strong>: <?= ($warehouse_id ? $warehouse->name : lang('all_warehouses')); ?></p>
				<div id="form_filter" class="closed well well-sm" style="display: none">
					<?php echo admin_form_open('products'); ?>
					<div class="row">
						<div class="col-sm-3">
							<div class="form-group">
								<label><?= lang('code'); ?></label>
								<input type="text" class="form-control" id="code" name="code" value="<?= ($code ?? '') ?>" />
							</div>
						</div>
						<div class="col-sm-3">
							<div class="form-group">
								<label><?= lang('name'); ?></label>
								<input type="text" class="form-control" id="name" name="name" value="<?= ($name ?? '') ?>" />
							</div>
						</div>
						<div class="col-sm-3">
							<div class="form-group">
								<label><?= lang('type'); ?></label>
								<?php
								$opt = [
									'' => lang('select') . ' ' . lang('type'),
									'standard' => 'Standard',
									'combo'    => 'Combo',
									'digital'  => 'Digital',
									'service'  => 'Service'
								];
								echo form_dropdown('type', $opt, ($type ?? ''), 'class="select2" id="type" style="width:100%;"'); ?>
							</div>
						</div>
						<div class="col-sm-3">
							<div class="form-group">
								<label><?= lang('category'); ?></label>
								<?php
								$categories = $this->site->getCategories();
								$cat = [];
								$cat[''] = lang('select') . ' ' . lang('category');
								foreach ($categories as $category) {
									$cat[$category->code] = $category->name . ' (' . $category->code . ')';
								}
								echo form_dropdown('category', $cat, ($category_code ?? ''), 'class="select2" id="category" style="width:100%;"'); ?>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-3">
							<div class="form-group">
								<label>Warehouse</label>
								<?php
								$opt = [];
								$opt[''] = 'Select Warehouse';
								$warehouses = $this->site->getAllWarehouses();
								foreach ($warehouses as $wh) {
									if ($wh_id = $this->session->userdata('warehouse_id')) {
										if ($wh->id != $wh_id) continue;
									}
									$opt[$wh->id] = $wh->name;
								}
								echo form_dropdown('warehouse', $opt, ($warehouse_id ?? ''), 'class="select2" id="warehouse" style="width:100%;"'); ?>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-2">
							<div class="form-group">
								<label><?= lang('start_date'); ?></label>
								<input type="date" class="form-control" id="start_date" name="start_date" value="<?= ($start_date ?? ''); ?>">
							</div>
						</div>
						<div class="col-sm-2">
							<div class="form-group">
								<label><?= lang('end_date'); ?></label>
								<input type="date" class="form-control" id="end_date" name="end_date" value="<?= ($end_date ?? ''); ?>">
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<a href="#" class="btn btn-primary" id="do_filter"><i class="fad fa-filter"></i> Filter</a>
								<a href="<?= admin_url('products'); ?>" class="btn btn-danger">Reset</a>
							</div>
						</div>
					</div>
					<?php echo form_close(); ?>
				</div>
				<div class="row">
					<div class="col-sm-3 float-right">
						<div class="input-group">
							<input id="dtfilter" class="form-control dtfilter" data-name="products" placeholder="<?= lang('search'); ?>">
							<div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
								<a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
							</div>
						</div>
					</div>
				</div>
				<?php if ($isAdmin) {
					echo admin_form_open('products/product_actions' . ($warehouse_id ? '/' . $warehouse_id : ''), 'id="action-form"');
				} ?>
				<div class="table-responsive">
					<table id="PRData" class="table table-bordered table-condensed table-hover table-striped">
						<thead>
							<tr class="primary">
								<th style="min-width:30px; width: 30px; text-align: center;">
									<input class="checkbox checkth" type="checkbox" name="check" />
								</th>
								<th style="min-width:40px; width: 40px; text-align: center;"><?php echo $this->lang->line('image'); ?></th>
								<th><?= lang('code') ?></th>
								<th><?= lang('name') ?></th>
								<th><?= lang('type') ?></th>
								<th><?= lang('category') ?></th>
								<?php
								if ($isAdmin) {
									echo '<th>' . lang('cost') . '</th>';
									echo '<th>Mark-On Price</th>';
									echo '<th>' . lang('quantity') . '</th>';
								} else {
									if ($this->session->userdata('show_cost')) {
										echo '<th>' . lang('cost') . '</th>';
									}
									if ($this->session->userdata('show_cost')) {
										echo '<th>Mark-On Price</th>';
									}
									if (getPermission('products-quantity')) {
										echo '<th>' . lang('quantity') . '</th>';
									}
								}
								?>
								<th><?= lang('unit') ?></th>
								<th>Safety Stock</th>
								<th style="min-width:65px; text-align:center;"><?= lang('actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<?php
								$span = 12;
								if ($isAdmin || getPermission('products-quantity')) $span++; ?>
								<td colspan="<?= $span; ?>" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
							</tr>
						</tbody>

						<tfoot class="dtFilter">
							<tr class="active">
								<th style="min-width:30px; width: 30px; text-align: center;">
									<input class="checkbox checkft" type="checkbox" name="check" />
								</th>
								<th style="min-width:40px; width: 40px; text-align: center;"><?php echo $this->lang->line('image'); ?></th>
								<th></th>
								<th></th>
								<th></th>
								<th></th>
								<?php
								if ($isAdmin) {
									echo '<th></th>';
									echo '<th></th>';
									echo '<th></th>';
								} else {
									if ($this->session->userdata('show_cost')) {
										echo '<th></th>';
									}
									if ($this->session->userdata('show_cost')) {
										echo '<th></th>';
									}
									if (getPermission('products-quantity')) {
										echo '<th></th>';
									}
								}
								?>
								<th></th>
								<th></th>
								<th style="width:65px; text-align:center;"><?= lang('actions') ?></th>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<?php if ($isAdmin) { ?>
	<div style="display: none;">
		<input type="hidden" name="form_action" value="" id="form_action" />
		<?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
	</div>
	<?= form_close() ?>
<?php } ?>
<script>
	$(document).ready(function() {
		$('#do_filter').click(function() {
			let code = $('#code').val();
			let name = $('#name').val();
			let type = $('#type').val();
			let category = $('#category').val();
			let warehouse = $('#warehouse').val();
			let start_date = $('#start_date').val();
			let end_date = $('#end_date').val();

			let q = '?';
			if (code) q += '&code=' + code;
			if (name) q += '&name=' + name;
			if (type) q += '&type=' + type;
			if (category) q += '&category=' + category;
			if (warehouse) q += '&warehouse=' + warehouse;
			if (start_date) q += '&start_date=' + start_date;
			if (end_date) q += '&end_date=' + end_date;

			location.href = site.base_url + 'products' + q;
		});
	});
</script>