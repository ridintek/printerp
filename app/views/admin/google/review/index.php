<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

if ($gbillers = getGET('biller')) {
  foreach ($gbillers as $billerId) {
    if (!empty($billerId)) {
      $q .= '&biller[]=' . $billerId;
    }
  }
}

if ($startDate = getGET('start_date')) {
  $q .= '&start_date=' . $startDate;
}

if ($endDate = getGET('end_date')) {
  $q .= '&end_date=' . $endDate;
}
?>
<script>
  $(document).ready(function() {
    'use strict';

    window.Table = $('#GoogleReviewTable').DataTable({
      ajax: {
        data: function(data) {
          data[security.csrf_token_name] = security.csrf_hash;
        },
        method: 'POST',
        url: site.base_url + 'google/getGoogleReviews?<?= $q ?>'
      },
      columnDefs: [{
          targets: 0,
          orderable: false,
          render: checkbox
        },
        {
          targets: 5,
          render: renderStatus
        },
        {
          targets: [1, 8],
          orderable: false
        }
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'All']
      ],
      order: [
        [6, 'desc']
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
      $bills = lang('all_billers');

      if ($gbillers) {
        if ($billers = Biller::get(['active' => '1'])) {
          $bls = '';
          foreach ($billers as $biller) {
            if (in_array($biller->id, $gbillers)) {
              $bls .= $biller->name . ', ';
            }
          }

          $bills = substr(rd_trim($bls), 0, -1); // Trim and remove last character (,).
        }
      }
      ?>
      <i class="fa-fw fad fa-star"></i><?= $page_title . ' (' . $bills . ')'; ?>
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
              <a href="<?= admin_url('google/review/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                <i class="fad fa-fw fa-plus-square"></i> Add Google Review
              </a>
            </li>
            <!-- <li>
              <a href="<?= admin_url('google/review/sync'); ?>" data-action="confirm">
                <i class="fad fa-fw fa-sync"></i> Sync Google Review
              </a>
            </li> -->
            <li class="divider"></li>
            <li>
              <a href="<?= admin_url('google/review/delete') ?>" data-toggle="delete-batch" data-message="Hapus Google Review yang terpilih?">
                <i class="fad fa-fw fa-trash"></i> Delete Google Review
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
          <?php echo admin_form_open('trackingpod'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label for="billers"><i class="fad fa-building"></i> Billers</label>
                <?php
                $billers = Biller::get(['active' => '1']);
                $bills = [];

                foreach ($billers as $biller) {
                  if ($biller->code == 'ADV') continue;
                  $bills[$biller->id] = $biller->name;
                }
                ?>
                <?= form_multiselect('billers[]', $bills, ($gbillers ?? ''), 'class="select2" id="billers" data-placeholder="Select Biller" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label for="startDate"><i class="fad fa-clock"></i> Start Date</label>
                <input class="form-control" id="startDate" name="start_date" type="date">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label for="endDate"><i class="fad fa-clock"></i> End Date</label>
                <input class="form-control" id="endDate" name="end_date" type="date">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <button type="button" class="btn btn-primary" id="btn_filter"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('trackingpod'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="trackingpod" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <table id="GoogleReviewTable" class="table table-bordered table-condensed table-hover table-striped" cellpadding="0" cellspacing="0" borders="0" style="width:100%">
          <thead>
            <tr>
              <th style="text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check" />
              </th>
              <th>Action</th>
              <th>Biller</th>
              <th>PIC Name</th>
              <th>Customer Name</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Created By</th>
              <th>Attachment</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="9" class="dataTables_empty"><?= lang('loading_data'); ?></td>
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
      let created_by = $('#created_by').val();
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();
      let billers = $('#billers').val();
      let q = '?';

      if (created_by) {
        q += '&created_by=' + created_by;
      }
      if (startDate) {
        q += '&start_date=' + startDate;
      }
      if (endDate) {
        q += '&end_date=' + endDate;
      }
      if (billers) {
        for (let x in billers) {
          q += '&biller[]=' + billers[x];
        }
      }

      location.href = site.base_url + 'google/review' + q;
    });
  });
</script>