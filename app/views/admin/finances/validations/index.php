<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';
$reference  = $this->input->get('reference');
$bank_id    = $this->input->get('bank');
$pic        = $this->input->get('pic'); // Person In Charge.
$customer   = $this->input->get('customer'); // Person In Charge.
$start_date = $this->input->get('start_date');
$end_date   = $this->input->get('end_date');
$verify_status = $this->input->get('verify_status');

if ($reference) {
  $q .= '&reference=' . $reference;
}
if ($bank_id) {
  $q .= '&bank=' . $bank_id;
}
if ($pic) {
  $q .= '&pic=' . $pic;
}
if ($customer) {
  $q .= '&customer=' . $customer;
}
if ($start_date) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date) {
  $q .= '&end_date=' . $end_date;
}
if ($verify_status) {
  $q .= '&verify_status=' . $verify_status;
}
?>
<script>
  $(document).ready(function () {
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "desc"], [2, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('finances/validations/getValidations' . $q) ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [{"bSortable": false,"mRender": checkbox}, {"mRender": fld}, null, null, null, null, null, null, null,
      {"mRender": currencyFormat}, null, {"mRender": currencyFormat}, {"mRender": fld},
      {"mRender": attachment}, null,
      {"mRender": payment_status}, {"bSortable": false}],
      "fnRowCallback": function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "validation_link"; // validation link
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var amount = 0, total = 0;
        for (var i = 0; i < aaData.length; i++) {
          amount += parseFloat(aaData[aiDisplay[i]][9]);
          total += parseFloat(aaData[aiDisplay[i]][11]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[9].innerHTML = currencyFormat(formatMoney(amount));
        nCells[11].innerHTML = currencyFormat(formatMoney(total));
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
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-check"></i><?= lang('payment_validations'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fa fa-tasks tip"  data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('finances/validations/add') ?>" data-toggle="modal" data-target="#myModal">
                <i class="fa fa-fw fa-plus-circle"></i> <?= lang('add_payment_validation') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_excel">
                <i class="fa fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_validation') ?></b>"
               data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
               data-html="true" data-placement="left">
               <i class="fa fa-fw fa-trash"></i> <?= lang('delete_payment_validations') ?>
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
      <p class="introtext"><strong><?= lang('biller'); ?></strong>: <?= ($biller_id ? $biller->name : lang('all_billers')); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-3">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" class="form-control" id="reference" name="reference" value="<?= ($reference ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label><?= lang('bank_account'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('bank_account');
                if ($all_banks) {
                  foreach ($all_banks as $bank) {
                    $bk[$bank->id] = $bank->name . ($bank->number ? ' (' . $bank->number . ')' : '');
                  }
                }
                echo form_dropdown('bank', $bk, ($bank_id ?? ''), 'class="form-control select2" id="bank" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label for="pic">PIC</label>
                <input name="pic" id="pic" class="form-control" type="text" value="<?= ($pic ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <label for="customer">Customer</label>
                <input name="customer" id="customers" class="form-control" type="text" value="<?= ($customer ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-3">
              <div class="form-group">
                <label for="verify_status">Verify Status</label>
                <?php
                $opt = [];
                $opt = [
                  '' => 'Select Verify Status',
                  'auto'   => 'Automatic',
                  'manual' => 'Manual'
                ];
                echo form_dropdown('verify_status', $opt, ($verify_status ?? ''), 'class="form-control select2" id="verify_status" style="width:100%;"'); ?>
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
                <a href="#" class="btn btn-primary" id="do_filter"><i class="fad fa-filter"></i> Filter</a>
                <a href="<?= admin_url('finances/validations'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="validations" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
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
              <th><?= lang('bank_name'); ?></th>
              <th><?= lang('account_no'); ?></th>
              <th><?= lang('amount'); ?></th>
              <th><?= lang('unique_code'); ?></th>
              <th><?= lang('total'); ?></th>
              <!-- <th><?= lang('expired_date'); ?></th> -->
              <th><?= lang('transaction_date'); ?></th>
              <th><?= lang('attachment'); ?></th>
              <th><?= lang('description'); ?></th>
              <th><?= lang('status'); ?></th>
              <th style="width:100px;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="17" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th></th>
              <th style="width:100px; text-align: center;"><?= lang('actions'); ?></th>
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
    $('#do_filter').click(function (e) {
      e.preventDefault();
      filterValidation();
    });

    $('#export_excel').click(function (e) {
      e.preventDefault();
      filterValidation(1);
    });

    function filterValidation (xls = false)
    {
      let q = '?';
      let pic        = $('#pic').val();
      let bank       = $('#bank').val();
      let customer   = $('#customers').val();
      let end_date   = $('#end_date').val();
      let reference  = $('#reference').val();
      let start_date = $('#start_date').val();
      let verify_status = $('#verify_status').val();

      if (pic)          q += '&pic=' + pic;
      if (bank)         q += '&bank=' + bank;
      if (customer)     q += '&customer=' + customer;
      if (end_date)     q += '&end_date=' + end_date;
      if (reference)    q += '&reference=' + reference;
      if (start_date)   q += '&start_date=' + start_date;
      if (verify_status) q += '&verify_status=' + verify_status;

      if (xls) {
        q += '&xls=1';
        location.href = site.base_url + 'finances/validations/getValidations' + q;
      } else {
        location.href = site.base_url + 'finances/validations' + q;
      }
    }
  });

</script>