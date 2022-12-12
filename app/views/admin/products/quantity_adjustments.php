<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript" src="<?= $assets ?>js/html2canvas.min.js"></script>
<script>
  $(document).ready(function () {
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('products/getAdjustments/' . ($warehouse ? $warehouse->id : '')); ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [{"bSortable": false, "mRender": checkbox}, {"mRender": fld}, null, null, null, {"mRender": decode_html},
      {"bSortable": false,"mRender": attachmentAdjustment}, {"bSortable": false}],
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "adjustment_link";
        return nRow;
      },
    });

    $('#dtfilter').datatableFilter();

    if (localStorage.getItem('remove_qals')) {
      if (localStorage.getItem('qaitems')) {
        localStorage.removeItem('qaitems');
      }
      if (localStorage.getItem('qaref')) {
        localStorage.removeItem('qaref');
      }
      if (localStorage.getItem('qawarehouse')) {
        localStorage.removeItem('qawarehouse');
      }
      if (localStorage.getItem('qanote')) {
        localStorage.removeItem('qanote');
      }
      if (localStorage.getItem('qadate')) {
        localStorage.removeItem('qadate');
      }
      localStorage.removeItem('remove_qals');
    }

    <?php if (XSession::get('remove_qals')) {
  ?>
      if (localStorage.getItem('qaitems')) {
        localStorage.removeItem('qaitems');
      }
      if (localStorage.getItem('qaref')) {
        localStorage.removeItem('qaref');
      }
      if (localStorage.getItem('qawarehouse')) {
        localStorage.removeItem('qawarehouse');
      }
      if (localStorage.getItem('qanote')) {
        localStorage.removeItem('qanote');
      }
      if (localStorage.getItem('qadate')) {
        localStorage.removeItem('qadate');
      }
    <?php $this->sma->unset_data('remove_qals');
}
    ?>
  });
</script>

<?php if ($Owner || $Admin || $GP['bulk_actions']) {
      echo admin_form_open('products/adjustment_actions', 'id="action-form"');
    }
?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-filter"></i><?= lang('quantity_adjustments') . ' (' . ($warehouse ? $warehouse->name : lang('all_warehouses')) . ')'; ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fa fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('products/add_adjustment') ?>">
                <i class="fa fa-plus-circle"></i> <?= lang('add_adjustment') ?>
              </a>
            </li>
            <li>
              <a href="<?= admin_url('products/add_adjustment_by_csv') ?>">
                <i class="fa fa-plus-circle"></i> <?= lang('add_adjustment_by_csv') ?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fa fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_products') ?></b>"
                data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
                data-html="true" data-placement="left">
              <i class="fa fa-trash"></i> <?= lang('delete_products') ?>
               </a>
             </li>
          </ul>
        </li>
        <?php if (!empty($warehouses)) {
  ?>
          <li class="dropdown">
            <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fa fa-warehouse tip" data-placement="left" title="<?= lang('warehouses') ?>"></i></a>
            <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
              <li><a href="<?= admin_url('products/quantity_adjustments') ?>"><i class="fa fa-warehouse"></i><?= lang('all_warehouses') ?></a></li>
              <li class="divider"></li>
              <?php
              foreach ($warehouses as $warehouse) {
                echo '<li><a href="' . admin_url('products/quantity_adjustments/' . $warehouse->id) . '"><i class="fa fa-warehouse-alt"></i>' . $warehouse->name . '</a></li>';
              } ?>
            </ul>
          </li>
        <?php
} ?>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext"><?= lang('list_results'); ?></p>

        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="adjustments" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive table-limit-height">
          <table id="Table" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th class="col-xs-2"><?= lang('date'); ?></th>
              <th class="col-xs-2"><?= lang('reference'); ?></th>
              <th class="col-xs-2"><?= lang('warehouse'); ?></th>
              <th class="col-xs-2"><?= lang('created_by'); ?></th>
              <th><?= lang('note'); ?></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fa fa-chain"></i></th>
              <th style="min-width:75px; text-align:center;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="8" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th><th></th><th></th><th></th><th></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fa fa-chain"></i></th>
              <th style="width:75px; text-align:center;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($Owner || $Admin || $GP['bulk_actions']) {
                ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?=form_submit('performAction', 'performAction', 'id="action-form-submit"')?>
  </div>
  <?=form_close()?>
<?php
              }
?>
