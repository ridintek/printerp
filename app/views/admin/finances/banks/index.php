<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';

if ($bank_code)  $q .= '&code=' . $bank_code;
if ($biller_id)  $q .= '&biller=' . $biller_id;
if ($bank_name)  $q .= '&name=' . $bank_name;
if ($acc_holder) $q .= '&holder=' . $acc_holder;
if ($acc_no)     $q .= '&no=' . $acc_no;
if ($type)       $q .= '&type=' . $type;
if ($start_date) $q .= '&start_date=' . $start_date;
if ($end_date)   $q .= '&end_date=' . $end_date;

?>
<script>
  $(document).ready(function () {
    'use strict';
    oTable = $('#BNData').dataTable({
      aaSorting: [[1, "asc"]],
      aLengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      iDisplayLength: <?= $Settings->rows_per_page ?>,
      bProcessing: true,
      bServerSide: true,
      sAjaxSource: '<?= admin_url('finances/banks/getBanks' . $q); ?>',
      fnServerData: function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      aoColumns: [{
        bSortable: false,
        mRender: checkbox
      }, null, null, null, null, null, null, null,
      {bSortable: false, mRender: currencyFormat}, {bSortable: false, mRender: bank_status}, {bSortable: false}],
      fnFooterCallback: function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var row_total = 0;
        for (var i = 0; i < aaData.length; i++) {
          row_total += Math.ceil(parseFloat(aaData[aiDisplay[i]][8]));
          // console.log(`Before: ${aaData[aiDisplay[i]][8]}, Ceil: ${Math.ceil(parseFloat(aaData[aiDisplay[i]][8]))}`);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[8].innerHTML = currencyFormat(formatMoney(row_total));
      }
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

    $('#dtfilter').datatableFilter();

    $('#myModal').on('hidden.bs.modal', function () {
      if (typeof mainTable != 'undefined') oTable = mainTable;
    });
  });
</script>
<style>.table td:nth-child(6) {
    text-align: right;
    width: 10%;
  }

  .table td:nth-child(8) {
    text-align: center;
  }</style>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-landmark"></i><?= lang('bank_accounts_list'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i></a>
          <ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="dLabel">
            <li class="dropdown-item"><a href="<?= admin_url('finances/banks/add'); ?>" data-toggle="modal" data-target="#myModal"><i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_bank_account'); ?></a></li>
            <li class="dropdown-item"><a href="#" id="excel" data-action="export_excel"><i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?></a></li>
            <li class="dropdown-item"><a href="<?= admin_url('finances/banks/import'); ?>" data-toggle="modal" data-target="#myModal"><i class="fad fa-fw fa-upload"></i> <?= lang('add_banks_csv'); ?></a></li>
            <li class="dropdown-item">
              <a href="#" id="sync_bank_amount">
                <i class="fad fa-fw fa-sync"></i> Sync Bank Amount
              </a>
            </li>
            <li class="divider"></li>
            <li class="dropdown-item">
              <a href="#" class="bpo" title="<?= $this->lang->line('activate_banks') ?>"
                data-content="<p><?= lang('r_u_sure') ?></p><a href='#' class='btn btn-success' id='activate' data-action='activate'><?= lang('i_m_sure') ?></a> <a href='#' class='btn btn-danger bpo-close'><?= lang('no') ?></a>"
                data-html="true" data-placement="left"><i class="fad fa-fw fa-check"></i> <?= lang('activate_banks') ?>
              </a>
            </li>
            <li class="dropdown-item">
              <a href="#" class="bpo" title="<?= $this->lang->line('deactivate_banks') ?>"
                data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='deactivate' data-action='deactivate'><?= lang('i_m_sure') ?></button> <button class='btn bpo-close'><?= lang('no') ?></button>"
                data-html="true" data-placement="left"><i class="fad fa-fw fa-times"></i> <?= lang('deactivate_banks') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li class="dropdown-item">
              <a href="#" class="bpo" title="<?= $this->lang->line('delete_banks') ?>"
                data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></button> <button class='btn bpo-close'><?= lang('no') ?></button>"
                data-html="true" data-placement="left"><i class="fad fa-fw fa-trash"></i> <?= lang('delete_banks') ?>
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
        <p class="introtext"><strong><?= lang('biller'); ?></strong>: <?= ($biller_id ? $biller->name : lang('all_billers')); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('bank_code'); ?></label>
                <input type="text" class="form-control" id="bank_code" name="bank_code" value="<?= $bank_code; ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('bank_name'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('bank_name');
                if ($all_banks) {
                  foreach ($all_banks as $bank) {
                      $bk[$bank->name] = $bank->name;
                  }
                }
                echo form_dropdown('bank_name', $bk, $bank_name, 'class="form-control select2" id="bank_name" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('biller'); ?></label>
                <?php
                $billers = $this->site->getAllBillers();
                $bl = [];
                $bl[''] = lang('select') . ' ' . lang('biller');
                if ($billers) {
                  foreach ($billers as $bill) {
                    $bl[$bill->id] = $bill->name;
                  }
                }
                echo form_dropdown('biller', $bl, $biller_id, 'class="form-control select2" id="biller" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('account_holder'); ?></label>
                <input name="account_holder" class="form-control" id="account_holder" type="text" value="<?= $acc_holder; ?>">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('account_no'); ?></label>
                <input name="account_no" class="form-control" id="account_no" type="text" value="<?= $acc_no; ?>">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('type'); ?></label>
                <?php
                $ty = [];
                $ty[''] = lang('select') . ' ' . lang('type');
                $types = $this->site->getBankTypes(); // Cash, EDC, Transfer
                if ( ! empty($types)) {
                  foreach ($types as $t) {
                    $ty[$t] = $t;
                  }
                }
                echo form_dropdown('type', $ty, $type, 'class="form-control select2" id="type" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input name="start_date" class="form-control" id="start_date" type="date" value="<?= $start_date; ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input name="end_date" class="form-control" id="end_date" type="date" value="<?= $end_date; ?>">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_button('filter_submit', 'Submit', 'class="btn btn-primary" id="filter_submit"'); ?>
                <a href="<?= admin_url('finances/banks'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
<?php if ($Owner || $Admin) {
  echo admin_form_open('finances/banks/actions', 'id="action-form"');
} ?>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="banks" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="BNData" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check"/>
              </th>
              <th><?php echo lang('bank_code'); ?></th>
              <th><?php echo lang('biller'); ?></th>
              <th><?php echo lang('bank_name'); ?></th>
              <th><?php echo lang('account_holder'); ?></th>
              <th><?php echo lang('account_no'); ?></th>
              <th><?php echo lang('type'); ?></th>
              <th><?php echo lang('bic_code'); ?></th>
              <th><?php echo lang('balance'); ?></th>
              <th><?php echo lang('status'); ?></th>
              <th style="width:80px; text-align:center;"><?php echo lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="11" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
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
              <th></th>
              <th style="width:85px;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('#myModal').on('shown.bs.modal', function(e) {
      $('#bank-form').prop('action', '<?=admin_url('banks/add');?>');
    });

    $('#filter_submit').on('click', function () {
      let q = '';
      let bank_code      = $('#bank_code').val();
      let bank_name      = $('#bank_name').val();
      let biller_id      = $('#biller').val();
      let account_holder = $('#account_holder').val();
      let account_no     = $('#account_no').val();
      let type           = $('#type').val();
      let start_date = $('#start_date').val();
      let end_date   = $('#end_date').val();

      if (bank_code)      q += `&code=${bank_code}`;
      if (bank_name)      q += `&name=${bank_name}`;
      if (biller_id)      q += `&biller=${biller_id}`;
      if (account_holder) q += `&holder=${account_holder}`;
      if (account_no)     q += `&no=${account_no}`;
      if (type)           q += `&type=${type}`;
      if (start_date) q += `&start_date=${start_date}`;
      if (end_date)   q += `&end_date=${end_date}`;

      location.href = '<?= admin_url('finances/banks?'); ?>' + q;
    });

    $('#sync_bank_amount').click(function() {
      addConfirm({
        message: 'Are you sure to sync bank amount?',
        title: 'Sync Bank Amount',
        onok: function() {
          let data = {};
          let bank_ids = $('[name="val[]"]:checked'); // Get checked bank id.

          if (bank_ids.length) {
            data.val = [];

            for (let a = 0; a < bank_ids.length; a++) {
              data.val.push(bank_ids[a].value);
            }
          }

          data[security.csrf_token_name] = security.csrf_hash;

          $.ajax({
            data: data,
            method: 'POST',
            success: function (data) {
              if (typeof data == 'object' && !data.error) {
                addAlert(data.msg, 'success');
                if (oTable) oTable.fnDraw();
              } else {
                addAlert(data.msg, 'danger');
              }
            },
            url: site.base_url + 'finances/banks/syncBankAmount'
          });
        }
      })
    });
  });
</script>
<?php if ($Owner || $Admin) {
  ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>
<?php } ?>