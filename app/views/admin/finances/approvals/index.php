<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';
if (getPost('reference')) {
  $q .= '&reference=' . getPost('reference');
}
if (getPost('transfer_ref')) {
  $q .= '&transfer_ref=' . getPost('transfer_ref');
}
if (getPost('acc_from')) {
  $q .= '&acc_from=' . getPost('acc_from');
}
if (getPost('acc_to')) {
  $q .= '&acc_to=' . getPost('acc_to');
}
if (getPost('created_by')) {
  $q .= '&created_by=' . getPost('created_by');
}
if (getPost('from_date')) {
  $q .= '&from_date=' . getPost('from_date');
}
if (getPost('to_date')) {
  $q .= '&to_date=' . getPost('to_date');
}
?>
<script>
  $(document).ready(function () {
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "desc"], [2, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('finances/approvals/getApprovals' . ($biller_id ? '/biller/' . $biller_id : '') . $q) ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [{"bSortable": false,"mRender": checkbox}, {"mRender": fld}, null, null, null, null, null, null, null, null,
      {"mRender": currencyFormat}, null, {"mRender": currencyFormat}, {"mRender": fld}, {"mRender": fld}, null,
      {"mRender": payment_status}, {"bSortable": false}],
      "fnRowCallback": function (nRow, aData, iDisplayIndex) {
        var oSettings = oTable.fnSettings();
        nRow.id = aData[0];
        nRow.className = "validation_link"; // validation link
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var amount = 0, total = 0;
        for (var i = 0; i < aaData.length; i++) {
          amount += parseFloat(aaData[aiDisplay[i]][10]);
          total += parseFloat(aaData[aiDisplay[i]][12]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[10].innerHTML = currencyFormat(formatMoney(amount));
        nCells[12].innerHTML = currencyFormat(formatMoney(total));
      }
    }).fnSetFilteringDelay().dtFilter([
      {column_number: 1, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text", data: []},
      {column_number: 2, filter_default_label: "[<?=lang('ref_no');?>]", filter_type: "text", data: []},
      {column_number: 3, filter_default_label: "[<?=lang('pic_id');?>]", filter_type: "text", data: []},
      {column_number: 4, filter_default_label: "[<?=lang('pic_name');?>]", filter_type: "text", data: []},
      {column_number: 5, filter_default_label: "[<?=lang('biller');?>]", filter_type: "text", data: []},
      {column_number: 6, filter_default_label: "[<?=lang('customer');?>]", filter_type: "text", data: []},
      {column_number: 7, filter_default_label: "[<?=lang('company');?>]", filter_type: "text", data: []},
      {column_number: 8, filter_default_label: "[<?=lang('bank_name');?>]", filter_type: "text", data: []},
      {column_number: 9, filter_default_label: "[<?=lang('account_no');?>]", filter_type: "text", data: []},
      {column_number: 10, filter_default_label: "[<?=lang('unique_code');?>]", filter_type: "text", data: []},
      {column_number: 11, filter_default_label: "[<?=lang('expired_date');?>]", filter_type: "text", data: []},
      {column_number: 12, filter_default_label: "[<?=lang('transaction_date');?>]", filter_type: "text", data: []},
      {column_number: 13, filter_default_label: "[<?=lang('description');?>]", filter_type: "text", data: []},
      {column_number: 14, filter_default_label: "[<?=lang('status');?>]", filter_type: "text", data: []}
    ], "footer");

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
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-check"></i><?= lang('payment_validations'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip"  data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('finances/validations/add') ?>" data-toggle="modal" data-target="#myModal">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_payment_validation') ?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_validation') ?></b>"
               data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
               data-html="true" data-placement="left">
               <i class="fad fa-fw fa-trash"></i> <?= lang('delete_payment_validations') ?>
             </a>
           </li>
         </ul>
       </li>
       <?php if (!empty($billers)) { ?>
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fad fa-building tip" data-placement="left" title="<?=lang('billers')?>"></i></a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li><a href="<?=admin_url('finances/validations')?>"><i class="fad fa-building"></i><?=lang('all_billers')?></a></li>
            <li class="divider"></li>
            <?php
              foreach ($billers as $wh) {
                echo '<li ' . ($biller_id && $biller_id == $wh->id ? 'class="active"' : '') . '><a href="' . admin_url('finances/validations/biller/' . $wh->id) . '"><i class="fad fa-building"></i>' . $wh->name . '</a></li>';
              } ?>
          </ul>
        </li>
        <?php } ?>
       <li class="dropdown">
          <a href="#" id="filter" title="Filter"><i class="icon fad fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
      <p class="introtext"><strong><?= lang('biller'); ?></strong>: <?= ($biller_id ? $biller->name : lang('all_billers')); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <?php echo admin_form_open('finances/validations'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" class="form-control" name="reference" value="<?= (getPost('reference') ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('transfer_reference'); ?></label>
                <input type="text" class="form-control" name="transfer_ref" value="<?= (getPost('transfer_ref') ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('created_by'); ?></label>
                <input name="created_by" class="form-control" type="text" value="<?= (getPost('created_by') ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('acc_from'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('acc_from');
                if ($all_banks) {
                  foreach ($all_banks as $bank) {
                      $bk[$bank->name] = $bank->name;
                  }
                }
                echo form_dropdown('acc_from', $bk, (getPost('acc_from') ?? ''), 'class="form-control select2"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('acc_to'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('acc_to');
                if ($all_banks) {
                  foreach ($all_banks as $bank) {
                      $bk[$bank->name] = $bank->name;
                  }
                }
                echo form_dropdown('acc_to', $bk, (getPost('acc_to') ?? ''), 'class="form-control select2"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label><?= lang('from_date'); ?></label>
                <input type="text" class="form-control date" name="from_date" value="<?= (getPost('from_date') ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label><?= lang('to_date'); ?></label>
                <input type="text" class="form-control date" name="to_date" value="<?= (getPost('to_date') ?? '') ?>" />
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_submit('submit', 'Submit', 'class="btn btn-primary"'); ?>
                <a href="<?= admin_url('finances/validations'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
<?php if ($Owner || $Admin || $GP['bulk_actions']) {
  echo admin_form_open('finances/validations/actions', 'id="action-form"');
} ?>
        <div class="row">
          <div class="col-sm-3 float-right">
            <input id="dtfilter" class="form-control dtfilter" data-name="validations" placeholder="<?= lang('search'); ?>">
          </div>
        </div>
        <div class="table-responsive table-limit-height">
          <table id="Table" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('reference'); ?></th>
              <th><?= lang('pic_id'); ?></th>
              <th><?= lang('pic_name'); ?></th>
              <th><?= lang('biller'); ?></th>
              <th><?= lang('customer'); ?></th>
              <th><?= lang('company'); ?></th>
              <th><?= lang('bank_name'); ?></th>
              <th><?= lang('account_no'); ?></th>
              <th><?= lang('amount'); ?></th>
              <th><?= lang('unique_code'); ?></th>
              <th><?= lang('total'); ?></th>
              <th><?= lang('expired_date'); ?></th>
              <th><?= lang('transaction_date'); ?></th>
              <th><?= lang('description'); ?></th>
              <th><?= lang('status'); ?></th>
              <th style="width:100px;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="14" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th style="width:100px; text-align: center;"><?= lang('actions'); ?></th>
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
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>
<?php
} ?>