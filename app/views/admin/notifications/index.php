<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    oTable = $('#NTTable').dataTable({
      "aaSorting": [[2, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('notifications/getNotifications') ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [
        {"bSortable": false,"mRender": checkbox}, null, {"mRender": fld}, {"mRender": fld}, {"mRender": fld},
        {"bSortable": false, "mRender": notification_status}, {"bSortable": false}
      ]
    });

    $('#dtfilter').datatableFilter();
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-info-circle"></i><?= lang('notifications'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?=lang('actions')?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?=admin_url('sales/add')?>">
                <i class="fad fa-fw fa-plus-circle"></i> <?=lang('add_sale')?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?=lang('export_to_excel')?>
              </a>
            </li>
            <li>
              <a href="#" id="combine" data-action="combine">
                <i class="fad fa-fw fa-file-pdf"></i> <?=lang('combine_to_pdf')?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?=lang('delete_sales')?></b>" data-content="<p><?=lang('r_u_sure')?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?=lang('i_m_sure')?></a> <button class='btn bpo-close'><?=lang('no')?></button>" data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?=lang('delete_sales')?>
              </a>
            </li>
          </ul>
        </li>
        <?php if ($Owner || $Admin || getPermission('notify-add')) { ?>
        <li class="dropdown"><a href="<?= admin_url('notifications/add'); ?>" data-toggle="modal" data-target="#myModal"><i class="icon fad fa-plus"></i></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <div class="row">
          <div class="col-sm-3 float-right">
            <input id="dtfilter" class="form-control dtfilter" data-name="notifications" placeholder="<?= lang('search'); ?>">
          </div>
        </div>    
        <div class="table-responsive table-overflow-y">
          <table id="NTTable" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-hover table-striped">
            <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?php echo $this->lang->line('notification'); ?></th>
              <th style="width: 140px;"><?php echo $this->lang->line('submitted_at'); ?></th>
              <th style="width: 140px;"><?php echo $this->lang->line('from'); ?></th>
              <th style="width: 140px;"><?php echo $this->lang->line('till'); ?></th>
              <th style="width: 140px;"><?= lang('status'); ?></th>
              <th style="width:80px;"><?php echo $this->lang->line('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="5" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
            </tr>

            </tbody>
          </table>
        </div>
        <!--<p><a href="<?php echo admin_url('notifications/add'); ?>" class="btn btn-primary" data-toggle="modal" data-target="#myModal"><?php echo $this->lang->line('add_notification'); ?></a></p>-->
      </div>
    </div>
  </div>
</div>

