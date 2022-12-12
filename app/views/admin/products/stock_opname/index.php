<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($filter = getGET('f')) {
  $filter = getUriFilter64($filter);
  if (!empty($filter['group_by'])) {
    $q .= '&group_by=' . $filter['group_by'];
  }
  if (!empty($filter['category'])) {
    $q .= '&category=' . $filter['category'];
  }
  if (!empty($filter['warehouses'])) {
    foreach ($filter['warehouses'] as $wh) {
      $q .= '&warehouses[]=' . $wh;
    }
  }
  if (!empty($filter['start_date'])) {
    $q .= '&start_date=' . $filter['start_date'];
    if (!isset($filter['end_date'])) {
      $filter['end_date'] = date('Y-m-d');
      $q .= '&end_date=' . $filter['end_date'];
    }
  }
  if (!empty($filter['end_date'])) {
    $q .= '&end_date=' . $filter['end_date'];
  }
}
?>
<script>
  $(document).ready(function() {
    if (localStorage.getItem('so_items')) {
      localStorage.removeItem('so_items');
    }
    if (localStorage.getItem('so_cycle')) {
      localStorage.removeItem('so_cycle');
    }
    if (localStorage.getItem('so_pic')) {
      localStorage.removeItem('so_pic');
    }
    if (localStorage.getItem('so_warehouse')) {
      localStorage.removeItem('so_warehouse');
    }
  });
</script>
<script>
  $(document).ready(function() {
    Table = $('#Table').DataTable({
      ajax: {
        data: {
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
        },
        method: 'POST',
        url: site.base_url + 'products/stock_opname/getStockOpname?<?= $q; ?>'
      },
      columnDefs: [{
          targets: 0,
          orderable: false,
          render: checkbox
        },
        {
          targets: 1,
          visible: false
        },
        {
          targets: 2,
          visible: false
        },
        {
          targets: 9,
          render: formatQuantityRight
        },
        {
          targets: 10,
          render: formatQuantityRight
        },
        {
          targets: 11,
          render: formatQuantityRight
        },
        {
          targets: 12,
          render: renderStatus
        },
        {
          targets: 13,
          orderable: false,
          render: notes
        },
        {
          targets: 14,
          orderable: false,
          render: attachmentStockOpname
        }
      ],
      footerCallback: function(row, data) {
        let total_lost = 0,
          total_edited = 0,
          total_plus = 0;
        for (let rowData of data) {
          total_lost += parseFloat(rowData[9]);
          total_plus += parseFloat(rowData[10]);
          total_edited += parseFloat(rowData[11]);
        }

        tfoot = row.getElementsByTagName('th');
        $(tfoot[7]).html(`<div class="text-right">${formatCurrency(total_lost)}</div>`);
        $(tfoot[8]).html(`<div class="text-right">${formatCurrency(total_plus)}</div>`);
        $(tfoot[9]).html(`<div class="text-right">${formatCurrency(total_edited)}</div>`);
      },
      lengthMenu: [
        [25, 50, 100, -1],
        [25, 50, 100, "<?= lang('all') ?>"]
      ],
      order: [
        [3, 'desc']
      ],
      pageLength: <?= $Settings->rows_per_page ?>,
      rowCallback: function(row, data) {
        row.classList.add('opname_link'); // Required by context Menu.
        row.dataset.id = data[0]; // Required by context Menu.
        row.dataset.reference = data[4]; // Required by context Menu.
        row.dataset.status = data[12]; // Required by context Menu.
        if (data[4]) {
          row.childNodes[2].innerHTML = `<a href="${site.base_url}products/stock_opname/view/${data[0]}" data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg">${data[4]}</a>`;
        }
        if (data[5]) {
          row.childNodes[3].innerHTML = `<a href="${site.base_url}products/view_adjustment/${data[1]}" data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg">${data[5]}</a>`;
        }
        if (data[6]) {
          row.childNodes[4].innerHTML = `<a href="${site.base_url}products/view_adjustment/${data[2]}" data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg">${data[6]}</a>`;
        }
      },
      serverSide: true
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

    $('#dtfilter').dataTableFilter();
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-list-ol"></i>Stock Opname List<?= (isset($filter['start_date']) ? " From ({$filter['start_date']}) to ({$filter['end_date']})" : ''); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('products/stock_opname/add') ?>">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_stock_opname') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li>
              <a href="#" id="sync">
                <i class="fad fa-fw fa-sync"></i> <?= lang('sync_stock_opname') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_products') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?= lang('delete_products') ?>
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" id="filter" title="Filter"><i class="icon fad fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext">Silakan klik kanan pada table untuk menampilkan <strong>Action</strong>.</p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('group_by'); ?></label>
                <?php
                $group = [
                  '' => '',
                  'opname'    => 'Stock Opname',
                  'pic'       => 'PIC',
                  'warehouse' => 'Warehouse'
                ];
                echo form_dropdown('group_by', $group, ($filter['group_by'] ?? ''), 'class="select2" id="group_by" data-placeholder="Select Group By" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label for="warehouse">Warehouse</label>
                <?php
                $warehouses = $this->site->getWarehouses(['active' => 1]);
                $whs = [];
                foreach ($warehouses as $wh) {
                  $whs[$wh->id] = $wh->name;
                }
                echo form_multiselect('warehouses', $whs, ($filter['warehouses'] ?? ''), 'class="select2" id="warehouses" data-placeholder="Select Category" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= ($filter['start_date'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= ($filter['end_date'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <button type="button" id="btn_filter" class="btn btn-primary"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('products/stock_opname'); ?>" class="btn btn-danger"><i class="fad fa-undo"></i> Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="stock_opname" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="Table" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th>Adjustment ID</th>
                <th>Sale ID</th>
                <th><?= lang('date'); ?></th>
                <th><?= lang('reference'); ?></th>
                <th>Adjustment Plus Ref</th>
                <th>Adjustment Min Ref</th>
                <th>PIC</th>
                <th><?= lang('warehouse'); ?></th>
                <th><?= lang('total_lost'); ?></th>
                <th><?= lang('total_plus'); ?></th>
                <th><?= lang('total_edited'); ?></th>
                <th><?= lang('status'); ?></th>
                <th><?= lang('note'); ?></th>
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fas fa-paperclip"></i></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="13" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
              </tr>
            </tbody>
            <tfoot class="dtFilter">
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-paperclip"></i></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#btn_filter').click(function() {
      filterStockOpname();
    });

    $('#export_excel').click(function() {
      filterStockOpname(true);
    });

    $('#sync').click(function(e) {
			e.preventDefault();
			let values = [];

			$('input[name="val[]"]').each(function() {
				if (this.checked) values.push(this.value);
			});

			addConfirm({
				message: 'Are you sure to sync stock opname?',
				onok: function() {
					$.ajax({
						data: {
							<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
							val: values
						},
						method: 'POST',
						success: function(data) {
							console.log(data);
							if (typeof data == 'object' && !data.error) {
								if (Table) Table.draw(false);
								addAlert(data.msg, 'success');
							} else if (typeof data == 'object') {
								addAlert(data.msg, 'danger');
							} else {
								addAlert('Unknown error. Response is not an object.', 'danger');
							}
						},
						url: '<?= admin_url('products/stock_opname/syncStockOpname'); ?>'
					});
				},
				title: 'Confirm sync stock opname',
			});
		});

    reloadContextMenu();
  });

  function filterStockOpname(xls = false) {
    let q = '';
    let group_by = $('#group_by').val();
    let warehouses = $('#warehouses').val();
    let start_date = $('#start_date').val();
    let end_date = $('#end_date').val();

    if (group_by) q += '&group_by=' + group_by;

    if (warehouses) {
      for (wh of warehouses) {
        q += '&warehouses[]=' + wh;
      }
    }
    if (start_date) q += '&start_date=' + start_date;
    if (end_date) q += '&end_date=' + end_date;
    if (xls) q += '&xls=1';

    if (xls) {
      location.href = site.base_url + 'products/stock_opname/getStockOpname?' + trimFilter(q);
    } else {
      location.href = site.base_url + 'products/stock_opname?f=' + btoa(trimFilter(q));
    }
  }

  function reloadContextMenu() {
    // 6 = finance
    let hasAccess = <?= (($isAdmin || XSession::get('group_id') == 6) ? 1 : 0); ?>;
    console.log(hasAccess);
    let contextMenuOpt = {
      selector: '.opname_link',
      callback: function(key, opt) {
        let opname_id = opt.$trigger.data('id');
        let reference = opt.$trigger.data('reference');
        let status = opt.$trigger.data('status');

        if (key == 'confirm') {
          if (status == 'checked' || status == 'confirmed') {
            location.href = site.base_url + 'products/stock_opname/confirm/' + opname_id;
          } else {
            addAlert(`Tidak dapat di 'confirm', status telah '${status}'.`, 'danger');
          }
        }
        if (key == 'delete') {
          alertify.dialog('confirm').set({
            message: `Are you sure to delete Stock Opname <b>${reference}</b>?`,
            onok: function() {
              let data = {
                id: opname_id
              };
              data[security.csrf_token_name] = security.csrf_hash;
              $.ajax({
                data: data,
                method: 'POST',
                success: function(data) {
                  if (typeof data == 'object' && !data.error) {
                    if (Table) Table.draw();
                    addAlert(data.msg, 'success');
                  } else if (typeof data == 'object' && data.error) {
                    addAlert(data.msg, 'danger');
                  } else {
                    addAlert('Unknown error', 'danger');
                  }
                },
                url: site.base_url + 'products/stock_opname/delete'
              });
            },
            title: 'Delete Stock Opname',
            transition: 'zoom'
          }).show();
        }
        if (key == 'edit') {
          location.href = site.base_url + 'products/stock_opname/edit/' + opname_id;
        }
        if (key == 'view') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id, 'modal-lg no-modal-header');
        }
        if (key == 'view_minus') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id + '?mode=minus', 'modal-lg no-modal-header');
        }
        if (key == 'view_plus') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id + '?mode=plus', 'modal-lg no-modal-header');
        }
      }
    };

    contextMenuOpt.items = {};
    contextMenuOpt.items['confirm'] = {
      name: 'Confirm Stock Opname',
      icon: 'fas fa-box-check'
    };
    if (hasAccess) { // 6 = finance
      contextMenuOpt.items['delete'] = {
        name: 'Delete Stock Opname',
        icon: 'fas fa-trash-alt'
      };
      contextMenuOpt.items['edit'] = {
        name: 'Edit Stock Opname',
        icon: 'fas fa-edit'
      };
    }
    contextMenuOpt.items['view'] = {
      name: 'View Details',
      icon: 'fas fa-search'
    };
    contextMenuOpt.items['view_minus'] = {
      name: 'View Minus Details',
      icon: 'fas fa-search-minus'
    };
    contextMenuOpt.items['view_plus'] = {
      name: 'View Plus Details',
      icon: 'fas fa-search-plus'
    };

    $.contextMenu(contextMenuOpt);
  }
</script>