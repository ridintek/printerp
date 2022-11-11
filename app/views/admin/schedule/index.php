<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

if ($billers = $this->input->get('biller')) {
  foreach ($billers as $biller_id) {
    if (!empty($biller_id)) {
      $q .= '&biller[]=' . $biller_id;
    }
  }
}

if ($startDate = $this->input->get('start_date')) {
  $q .= '&start_date=' . $startDate;
}

if ($endDate = $this->input->get('end_date')) {
  $q .= '&end_date=' . $endDate;
}
?>
<script>
  $(document).ready(function() {
    'use strict';

    window.Table = $('#ScheduleTable').DataTable({
      ajax: {
        data: function(data) {
          data[security.csrf_token_name] = security.csrf_hash;
        },
        method: 'POST',
        url: site.base_url + 'schedule/getSchedules?<?= $q ?>'
      },
      columnDefs: [{
          targets: 0,
          orderable: false,
          render: checkbox
        },
        {
          targets: 1,
          orderable: false
        }
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'All']
      ],
      order: [
        [2, 'asc']
      ],
      pageLength: 50,
      processing: true,
      scrollX: true,
      serverSide: true,
      stateSave: false
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
      $bls = lang('all_billers');

      if (!empty($billers)) {
        $bls = '';
        foreach ($billers as $biller_id) {
          $bl = $this->site->getBiller(['id' => $biller_id]);
          $bls .= $bl->name . ', ';
        }

        $bls = substr(rd_trim($bls), 0, -1); // Trim and remove last character (,).
      }
      ?>
      <i class="fa-fw fad fa-cogs"></i><?= $page_title . ' (' . $bls . ')'; ?>
      <?= ($this->input->post('start_date') ? '(' . $this->input->post('start_date') . ')' : '') . ($this->input->post('end_date') ? ' to (' . $this->input->post('end_date') . ')' : ''); ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('schedule/add'); ?>" id="add_schedule" data-toggle="modal"
                  data-backdrop="false" data-target="#myModal">
                <i class="fad fa-fw fa-plus-square"></i> Add Schedule
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" id="batchDelete">
                <i class="fad fa-fw fa-trash"></i> Delete Schedule
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
          <?php echo admin_form_open('schedule'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label for="billers"><i class="fad fa-warehouse"></i> Billers</label>
                <?php
                $bls = $this->site->getBillers();
                $bl = [];

                if (!empty($bls)) {
                  foreach ($bls as $biller) {
                    if ($biller->code == 'ADV') continue;
                    $bl[$biller->id] = $biller->name;
                  }
                }
                ?>
                <?= form_multiselect('billers[]', $bl, ($billers ?? ''), 'class="select2" id="billers" data-placeholder="Select biller" style="width:100%;"'); ?>
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
                <a href="<?= admin_url('schedule'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="schedule" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <table id="ScheduleTable" class="table table-bordered table-condensed table-hover table-striped" cellpadding="0" cellspacing="0" borders="0" style="width:100%">
          <thead>
            <tr>
              <th style="text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check" />
              </th>
              <th>Action</th>
              <th>Biller</th>
              <th>Valid Date</th>
              <th>Minggu</th>
              <th>Senin</th>
              <th>Selasa</th>
              <th>Rabu</th>
              <th>Kamis</th>
              <th>Jum'at</th>
              <th>Sabtu</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="11" class="dataTables_empty"><?= lang('loading_data'); ?></td>
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
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    initControls();

    $('#batchDelete').click(function() {
      addConfirm({
        message: 'Hapus Schedule yang terpilih?',
        title: 'Hapus Schedule',
        onok: () => {
          let data = {};
          let vals = document.querySelectorAll('input[name="val[]"]');

          data.val = [];

          for (let val of vals) {
            if (val.checked) {
              data.val.push(val.value);
            }
          }

          data[security.csrf_token_name] = security.csrf_hash;

          $.ajax({
            data: data,
            method: 'POST',
            success: function(data) {
              if (isObject(data)) {
                if (data.success) {
                  if (typeof oTable == 'object') oTable.fnDraw(false);
                  if (typeof Table == 'object') Table.draw(false);
                  if (typeof Table2 == 'object') Table2.draw(false);

                  addAlert(data.message, 'success');
                } else {
                  addAlert(data.message, 'danger');
                }
              } else {
                addAlert('Unknown error', 'danger');
              }
            },
            url: site.base_url + 'schedule/delete'
          })
        }
      });
    });

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

      location.href = site.base_url + 'schedule' + q;
    });
  });
</script>