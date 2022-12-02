<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

if ($warehouses = getGET('warehouse')) {
  foreach ($warehouses as $warehouse_id) {
    if (!empty($warehouse_id)) {
      $q .= '&warehouse[]=' . $warehouse_id;
    }
  }
}
if ($start_date = getGET('start_date')) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date = getGET('end_date')) {
  $q .= '&end_date=' . $end_date;
}
?>
<script>
  $(document).ready(function() {
    'use strict';

    window.Table = $('#QueueTable').DataTable({
      ajax: {
        data: function(data) {
          data[security.csrf_token_name] = security.csrf_hash;
        },
        method: 'POST',
        url: site.base_url + 'qms/getQueues?<?= $q ?>'
      },
      columnDefs: [{
          targets: 0,
          orderable: false,
          render: checkbox
        },
        {
          targets: 1,
          orderable: false
        },
        {
          targets: 10,
          render: renderStatus
        }
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'All']
      ],
      order: [
        [2, 'desc']
      ],
      pageLength: 50,
      processing: true,
      scrollX: true,
      serverSide: true,
      stateSave: true
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
    <h2 class="blue">
      <?php
      $whs = lang('all_warehouses');

      if (!empty($warehouses)) {
        $whs = '';
        foreach ($warehouses as $warehouse_id) {
          if (!empty($warehouse_id)) {
            $wh = $this->site->getWarehouseByID($warehouse_id);
            $whs .= $wh->name . ', ';
          }
        }

        $whs = substr(rd_trim($whs), 0, -1); // Trim and remove last character (,).
      }
      ?>
      <i class="fa-fw fad fa-cogs"></i><?= $page_title . ' (' . $whs . ')'; ?>
      <?= (getGET('start_date') ? '(' . getGET('start_date') . ')' : '') . (getGET('end_date') ? ' to (' . getGET('end_date') . ')' : ''); ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('qms/getQueues?xls=1' . $q); ?>" id="export_table">
                <i class="fad fa-fw fa-table"></i> Export Table
              </a>
            </li>
            <li>
              <a href="<?= admin_url('qms/getQueues?xls=2' . $q); ?>" id="export_report">
                <i class="fad fa-fw fa-chart-bar"></i> Export Report
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
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <?php echo admin_form_open('qms'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('warehouse', 'warehouses'); ?>
                <?php
                $whs = $this->site->getAllwarehouses();

                if (!empty($whs)) {
                  foreach ($whs as $wh) {
                    if ($wh->code == 'ADV') continue;
                    $bl[$wh->id] = $wh->name;
                  }
                }
                ?>
                <?= form_multiselect('warehouses[]', $bl, ($warehouses ?? ''), 'class="select2" id="warehouses" data-placeholder="Select warehouse" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= ($start_date ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= ($end_date ?? '') ?>" />
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <button type="button" class="btn btn-primary" id="btn_filter"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('qms'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="qms" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <table id="QueueTable" class="table table-bordered table-condensed table-hover table-striped" cellpadding="0" cellspacing="0" borders="0" style="width:100%">
          <thead>
            <tr>
              <th style="text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check" />
              </th>
              <th><?= lang('action'); ?></th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('call_date'); ?></th>
              <th><?= lang('serve_date'); ?></th>
              <th><?= lang('end_date'); ?></th>
              <th><?= lang('counter'); ?></th>
              <th><?= lang('customer'); ?></th>
              <th><?= lang('category'); ?></th>
              <th><?= lang('ticket'); ?></th>
              <th><?= lang('status'); ?></th>
              <th><?= lang('warehouse'); ?></th>
              <th>CS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="13" class="dataTables_empty"><?= lang('loading_data'); ?></td>
            </tr>
          </tbody>
          <tfoot class="dtFilter">
            <tr class="active">
              <th style="text-align: center;">
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
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#btn_filter').click(function() {
      let end_date   = $('#end_date').val();
      let start_date = $('#start_date').val();
      let warehouses = $('#warehouses').val();
      let q = '?';

      if (end_date)   q += '&end_date=' + end_date;
      if (start_date) q += '&start_date=' + start_date;

      if (warehouses) {
        for (let x in warehouses) {
          q += '&warehouse[]=' + warehouses[x];
        }
      }

      location.href = site.base_url + 'qms' + q;
    });
  });
</script>