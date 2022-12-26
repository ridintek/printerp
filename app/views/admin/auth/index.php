<script>
  $(document).ready(function () {
    'use strict';
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "asc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('auth/getUsers') ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= csrf_token() ?>",
          "value": "<?= csrf_hash()?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [{
        "bSortable": false,
        "mRender": checkbox
      }, null, null, null, null, null, null, null, {"mRender": user_status}, {"bSortable": false}]
    });

    $('#dtfilter').datatableFilter();
  });
</script>
<style>.table td:nth-child(6) {
    text-align: right;
    width: 10%;
  }

  .table td:nth-child(8) {
    text-align: center;
  }</style>
<?php if ($Owner || $Admin || getPermission('users-edit')) {
  echo admin_form_open('auth/user_actions', 'id="action-form"');
} ?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-users"></i><?= lang('users'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i></a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li><a href="<?= admin_url('auth/create_user'); ?>"><i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_user'); ?></a></li>
            <li><a href="<?= admin_url('auth/import_users'); ?>" id="import" data-toggle="modal" data-target="#myModal"><i class="fad fa-fw fa-upload"></i> <?= lang('add_users_csv') ?></a></li>
            <li><a href="#" id="excel" data-action="export_excel"><i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?></a></li>
            <li class="divider"></li>
            <li><a href="<?= admin_url('auth/delete_user') ?>" data-action="confirm"><i class="fad fa-fw fa-trash"></i> Delete User</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="users" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive table-limit-height">
          <table id="Table" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-hover table-striped">
            <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check"/>
              </th>
              <th class="col-xs-1"><?php echo lang('username'); ?></th>
              <th class="col-xs-2"><?php echo lang('fullname'); ?></th>
              <th class="col-xs-2"><?php echo lang('biller'); ?></th>
              <th class="col-xs-2"><?php echo lang('warehouse'); ?></th>
              <th class="col-xs-2"><?php echo lang('phone'); ?></th>
              <th class="col-xs-2"><?php echo lang('company'); ?></th>
              <th class="col-xs-1"><?php echo lang('group'); ?></th>
              <th style="width:100px;"><?php echo lang('status'); ?></th>
              <th style="width:80px;"><?php echo lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="10" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th></th>
              <th style="width:100px;"></th>
              <th style="width:85px;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if ($Owner || $Admin || getPermission('users-edit')) {
  ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>

  <script language="javascript">
    $(document).ready(function () {
      $('#set_admin').click(function () {
        $('#usr-form-btn').trigger('click');
      });

    });
  </script>

<?php
} ?>