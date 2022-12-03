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
    if ($.cookie('soremove') == 1) {
      if (localStorage.getItem('soitems')) {
        localStorage.removeItem('soitems');
      }
      if (localStorage.getItem('socycle')) {
        localStorage.removeItem('socycle');
      }
      if (localStorage.getItem('sowarehouse')) {
        localStorage.removeItem('sowarehouse');
      }
      $.removeCookie('soremove', {
        path: '/'
      });
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
        url: site.base_url + 'finances/reconciliations/getReconciliations?<?= $q; ?>'
      },
      columnDefs: [{
        targets: 0,
        orderable: false,
        render: checkbox
      }, {
        targets: 3,
        render: formatCurrency
      }, {
        targets: 4,
        render: formatCurrency
      }, {
        targets: 5,
        render: formatCurrency
      }],
      footerCallback: function(row, data) {
        let total_erp = 0,
          total_mb = 0,
          total_balance = 0;
        for (let rowData of data) {
          total_mb += parseFloat(rowData[3]);
          total_erp+= parseFloat(rowData[4]);
          total_balance += parseFloat(rowData[5]);
        }

        tfoot = row.getElementsByTagName('th');
        $(tfoot[3]).html(`<div class="text-right">${formatCurrency(total_mb)}</div>`);
        $(tfoot[4]).html(`<div class="text-right">${formatCurrency(total_erp)}</div>`);
        $(tfoot[5]).html(`<div class="text-right">${formatCurrency(total_balance)}</div>`);
      },
      lengthMenu: [
        [25, 50, 100, -1],
        [25, 50, 100, "<?= lang('all') ?>"]
      ],
      order: [
        [1, 'desc']
      ],
      pageLength: <?= $Settings->rows_per_page ?>,
      rowCallback: function(row, data) {
        row.classList.add('reconciliation_link'); // Required by context Menu.
        row.dataset.id = data[0]; // Required by context Menu.
        // row.dataset.reference = data[4]; // Required by context Menu.
        // row.dataset.status = data[12]; // Required by context Menu.
        if (data[3]) {
          row.childNodes[3].innerHTML = `
            <div class="text-right">
              <a href="${site.base_url}finances/reconciliations/view?m=mb&no=${data[2]}" data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg">${formatCurrency(data[3])}</a>
            </div>`;
        }
        if (data[4]) {
          row.childNodes[4].innerHTML = `
            <div class="text-right">
              <a href="${site.base_url}finances/reconciliations/view?m=erp&no=${data[2]}" data-toggle="modal" data-target="#myModal" data-modal-class="modal-lg">${formatCurrency(data[4])}</a>
            </div>`;
        }
        if (data[5]) {
          row.childNodes[5].innerHTML = `<div class="text-right">${formatCurrency(data[5])}</div>`;
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
    <h2 class="blue"><i class="fad fa-sync-alt"></i>Bank Reconciliations<?= (isset($filter['start_date']) ? " From ({$filter['start_date']}) to ({$filter['end_date']})" : ''); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="#" id="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li>
              <a href="#" id="sync_reconciliation">
                <i class="fad fa-fw fa-sync"></i> Sync Reconciliations
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
                $warehouses = $this->site->getAllWarehouses();
                $whs = [];
                foreach ($warehouses as $wh) {
                  $whs[$wh->id] = $wh->name;
                }
                echo form_multiselect('warehouses', $whs, ($filter['warehouses'] ?? ''), 'class="select2" id="warehouses" data-placeholder="Select Category" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= ($filter['start_date'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-6">
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
                <th>MB Bank Name</th>
                <th>Account No</th>
                <th>MB Amount</th>
                <th>ERP Amount</th>
                <th>Balance</th>
                <th>MB Account Name</th>
                <th>ERP Account Name</th>
                <th>Last MB Sync</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="9" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
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

    $('#sync_reconciliation').click(function () {
      let data = {};

      addAlert('Synchronizing bank reconciliations. Please wait...', 'info');
      data[security.csrf_token_name] = security.csrf_hash;

      fetch(site.base_url + 'finances/reconciliations/sync', {
        body: serialize(data),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        method: 'POST'
      })
      .then(response => response.json())
      .then(data => {
        if (typeof data == 'object') {
          addAlert(data.msg, (data.error ? 'danger' : 'success'));
          if (Table) Table.draw();
        } else {
          addAlert('Unknown error.', 'danger');
        }
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
    let privilege = '<?= $Owner ?? $Admin; ?>';
    let contextMenuOpt = {
      selector: '.reconciliation_link',
      callback: function(key, opt) {
        let opname_id = opt.$trigger.data('id');
        let reference = opt.$trigger.data('reference');
        let status = opt.$trigger.data('status');

        if (key == 'view') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id, 'modal-lg no-modal-header');
        }
      }
    };

    contextMenuOpt.items = {};
    contextMenuOpt.items['view'] = {
      name: 'View Details',
      icon: 'fas fa-search'
    };

    $.contextMenu(contextMenuOpt);
  }
</script>