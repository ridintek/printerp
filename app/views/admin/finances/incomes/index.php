<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';
if (getPOST('reference')) {
  $q .= '&reference=' . getPOST('reference');
}
if (getPOST('category')) {
  $q .= '&category=' . getPOST('category');
}
if (getPOST('paid_by')) {
  $q .= '&paid_by=' . getPOST('paid_by');
}
if (getPOST('from_date')) {
  $q .= '&from_date=' . getPOST('from_date');
}
if (getPOST('to_date')) {
  $q .= '&to_date=' . getPOST('to_date');
}
?>
<script>
  $(document).ready(function () {
    function attachment(x) {
      if (x != null) {
        return '<a href="' + site.url + 'assets/uploads/' + x + '" target="_blank"><i class="fa fa-chain"></i></a>';
      }
      return x;
    }

    oTable = $('#INCData').dataTable({
      "aaSorting": [[1, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('finances/incomes/getIncomes' . $q); ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [{
        "bSortable": false,
        "mRender": checkbox
      }, {"mRender": fld}, null, null, null, {"mRender": currencyFormat}, null, null, null, {
        "bSortable": false,
        "mRender": attachment
      }, {"bSortable": false}],
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        var oSettings = oTable.fnSettings();
        nRow.id = aData[0];
        nRow.className = "income_link";
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var total = 0;
        for (var i = 0; i < aaData.length; i++) {
          total += parseFloat(aaData[aiDisplay[i]][5]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[5].innerHTML = currencyFormat(total);
      }
    }).fnSetFilteringDelay().dtFilter([
      {column_number: 1, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text", data: []},
      {column_number: 2, filter_default_label: "[<?=lang('reference');?>]", filter_type: "text", data: []},
      {column_number: 3, filter_default_label: "[<?=lang('payment_reference');?>]", filter_type: "text", data: []},
      {column_number: 4, filter_default_label: "[<?=lang('category');?>]", filter_type: "text", data: []},
      {column_number: 6, filter_default_label: "[<?=lang('note');?>]", filter_type: "text", data: []},
      {column_number: 7, filter_default_label: "[<?=lang('paid_by');?>]", filter_type: "text", data: []},
      {column_number: 8, filter_default_label: "[<?=lang('created_by');?>]", filter_type: "text", data: []}
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
    <h2 class="blue"><i class="fa-fw fa fa-arrow-alt-right"></i><?= lang('incomes_list'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fa fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('finances/incomes/add') ?>" data-toggle="modal" data-target="#myModal">
                <i class="fa fa-fw fa-plus-circle"></i> <?= lang('add_income') ?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fa fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_incomes') ?></b>"
                data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
                data-html="true" data-placement="left">
                <i class="fa fa-fw fa-trash"></i> <?= lang('delete_incomes') ?>
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" id="filter" title="Filter"><i class="icon fa fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?= lang('list_results'); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <?php echo admin_form_open('finances/incomes'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" class="form-control" name="reference" value="<?= (getPOST('reference') ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('category'); ?></label>
                <?php
                $categories = $this->site->getIncomeCategories();
                $cat = [];
                $cat[''] = lang('select') . ' ' . lang('category');
                if ($categories) {
                  foreach ($categories as $category) {
                    $cat[$category->id] = $category->name;
                  }
                }
                echo form_dropdown('category', $cat, (getPOST('category') ?? ''), 'class="form-control select2" id="category" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('paid_by'); ?></label>
                <?php
                $banks = $this->finances_model->getAllBanks();
                $biller = $this->site->getbillerByID($this->session->userdata('biller_id'));
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('paid_by');
                if ($banks) {
                  foreach ($banks as $bank) {
                    if ($biller) {
                      if ($biller->code == $bank->biller_code) {
                        $bk[$bank->id] = $bank->name;
                      }
                    } else {
                      $bk[$bank->id] = $bank->name;
                    }
                  }
                }
                echo form_dropdown('paid_by', $bk, (getPOST('paid_by') ?? ''), 'class="form-control select2" id="paid_by" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('from_date'); ?></label>
                <input name="from_date" class="form-control" type="date" value="<?= (getPOST('from_date') ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('to_date'); ?></label>
                <input name="to_date" class="form-control" type="date" value="<?= (getPOST('to_date') ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_submit('submit', 'Submit', 'class="btn btn-primary"'); ?>
                <a href="<?= admin_url('finances/incomes'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
<?php if ($Owner || $Admin) {
  echo admin_form_open('finances/incomes/user_actions', 'id="action-form"');
} ?>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="incomes" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="INCData" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('reference'); ?></th>
              <th><?= lang('payment_reference'); ?></th>
              <th><?= lang('category'); ?></th>
              <th><?= lang('amount'); ?></th>
              <th><?= lang('note'); ?></th>
              <th><?= lang('paid_by'); ?></th>
              <th><?= lang('created_by'); ?></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fa fa-chain"></i></th>
              <th style="width:100px;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="10" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
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
              <th></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fa fa-chain"></i></th>
              <th style="width:100px; text-align: center;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if ($Owner || $Admin) {
  ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>
<?php
} ?>
