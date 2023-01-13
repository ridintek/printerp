<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    oTable = $('#CusData').dataTable({
      "aaSorting": [[1, "asc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('customers/getCustomers') ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "customer_details_link";
        return nRow;
      },
      "aoColumns": [{
        "bSortable": false,
        "mRender": checkbox
      }, null, null, null, null, null, null, {"mRender": currencyFormat}, null, {"bSortable": false}]
    }).dtFilter([
      {column_number: 1, filter_default_label: "[<?=lang('company');?>]", filter_type: "text", data: []},
      {column_number: 2, filter_default_label: "[<?=lang('name');?>]", filter_type: "text", data: []},
      {column_number: 3, filter_default_label: "[<?=lang('email_address');?>]", filter_type: "text", data: []},
      {column_number: 4, filter_default_label: "[<?=lang('phone');?>]", filter_type: "text", data: []},
      {column_number: 5, filter_default_label: "[<?=lang('price_group');?>]", filter_type: "text", data: []},
      {column_number: 6, filter_default_label: "[<?=lang('customer_group');?>]", filter_type: "text", data: []},
      {column_number: 7, filter_default_label: "[<?=lang('deposit');?>]", filter_type: "text", data: []},
      {column_number: 8, filter_default_label: "[<?=lang('award_points');?>]", filter_type: "text", data: []},
    ], "footer");

    $('#dtfilter').datatableFilter();
  });
</script>
<?php if ($Owner || $Admin || $GP['bulk_actions']) {
  echo admin_form_open('customers/customer_actions', 'id="action-form"');
} ?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-users"></i><?= lang('customers'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('customers/add'); ?>" data-toggle="modal" data-target="#myModal" id="add">
                <i class="fad fa-plus-circle"></i> <?= lang('add_customer'); ?>
              </a>
            </li>
            <li>
              <!-- customers/import_csv -->
              <a href="<?= admin_url('customers/import'); ?>" data-toggle="modal" data-target="#myModal">
                <i class="fad fa-plus-circle"></i> <?= lang('import_by_csv'); ?>
              </a>
            </li>
            <?php if ($Owner || $Admin) { ?>
            <li>
              <a href="<?= admin_url('customers/delete') ?>" class="form-control" data-toggle="delete-batch">
                <i class="fad fa-trash"></i> Delete Customers
              </a>
            </li>
            <?php } ?>
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
              <input id="dtfilter" class="form-control dtfilter" data-name="customers" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive table-limit-height">
          <table id="CusData" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr class="primary">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check"/>
              </th>
              <th><?= lang('company'); ?></th>
              <th><?= lang('name'); ?></th>
              <th><?= lang('email_address'); ?></th>
              <th><?= lang('phone'); ?></th>
              <th><?= lang('price_group'); ?></th>
              <th><?= lang('customer_group'); ?></th>
              <th><?= lang('deposit'); ?></th>
              <th><?= lang('award_points'); ?></th>
              <th style="min-width:135px !important;"><?= lang('actions'); ?></th>
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
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th style="min-width:135px !important;" class="text-center"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if ($Owner || $Admin || $GP['bulk_actions']) { ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>
<?php } ?>
<?php if ($action && $action == 'add') { ?>
    <script>$(document).ready(function(){$("#add").trigger("click");});</script>
<?php } ?>
