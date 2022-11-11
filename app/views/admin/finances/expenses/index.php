<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($reference = $this->input->get('reference')) {
  $q .= '&reference=' . $reference;
}
if ($bank = $this->input->get('bank')) {
  foreach ($bank as $bk) {
    $q .= '&bank[]=' . $bk;
  }
}
if ($category = $this->input->get('category')) {
  foreach ($category as $cat) {
    $q .= '&category[]=' . $cat;
  }
}
if ($status = $this->input->get('status')) {
  foreach ($status as $st) {
    $q .= '&status[]=' . $st;
  }
}
if ($payment_status = $this->input->get('payment_status')) {
  foreach ($payment_status as $pst) {
    $q .= '&payment_status[]=' . $pst;
  }
}
if ($supplier = $this->input->get('supplier')) {
  $q .= '&supplier=' . $supplier;
}
if ($start_date = $this->input->get('start_date')) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date = $this->input->get('end_date')) {
  $q .= '&end_date=' . $end_date;
}
if ($start_payment_date = $this->input->get('start_payment_date')) {
  $q .= '&start_payment_date=' . $start_payment_date;
}
if ($end_payment_date = $this->input->get('end_payment_date')) {
  $q .= '&end_payment_date=' . $end_payment_date;
}
?>
<script>
  $(document).ready(function () {
    function attachment(x) {
      if (x != null) {
        return '<div class="text-center"><a href="' + site.url + 'assets/uploads/' + x + '" target="_blank"><i class="fad fa-file-download"></i></a></div>';
      }
      return x;
    }

    oTable = $('#EXPData').dataTable({
      "aaSorting": [[1, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('finances/expenses/getExpenses?' . $q); ?>',
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
      }, {"mRender": fld}, null, null, {"mRender": currencyFormat}, null, null, null,
      {"mRender": approval_status}, {"mRender": fld}, {"mRender": payment_status}, null,
      {
        "bSortable": false,
        "mRender": attachmentExpense
      },
      {"bSortable": false}],
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "expense_link";
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var total = 0;
        for (var i = 0; i < aaData.length; i++) {
          total += parseFloat(aaData[aiDisplay[i]][4]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[4].innerHTML = currencyFormat(total);
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
    <h2 class="blue"><i class="fa-fw fad fa-arrow-alt-left"></i><?= lang('expenses_list'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('finances/expenses/add') ?>" data-toggle="modal" data-target="#myModal">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_expense') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_payment">
                <i class="fad fa-fw fa-file-excel"></i> Export to BNI format
              </a>
            </li>
            <li>
              <a href="#" id="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> Export to Excel
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_expenses') ?></b>"
                data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
                data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?= lang('delete_expenses') ?>
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" id="filter" title="Filter"><i class="icon fad fa-fw fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext">
          <strong><?= lang('biller'); ?></strong>: <?= ($biller_id ? $biller->name : 'All Billers'); ?>
        </p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" class="form-control" id="reference" name="reference" value="<?= ($reference ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('expense_status'); ?></label>
                <?php
                $all_status = $this->sma->getAllStatus();
                $stat = [];
                if ($all_status) {
                  foreach ($all_status as $st) {
                    if ($st == 'need_approval' || $st == 'approved') {
                      $stat[$st] = lang($st);
                    }
                  }
                }
                echo form_multiselect('status', $stat, ($status ?? ''), 'class="form-control select2" id="status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('payment_status'); ?></label>
                <?php
                $stat = [];
                if ($all_status) {
                  foreach ($all_status as $status) {
                    if ($status == 'pending' || $status == 'paid' || $status == 'partial') {
                      $stat[$status] = lang($status);
                    }
                  }
                }
                echo form_multiselect('payment_status', $stat, ($payment_status ?? ''), 'class="form-control select2" id="payment_status" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input name="start_date" class="form-control" id="start_date" type="date" value="<?= ($start_date ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input name="end_date" class="form-control" id="end_date" type="date" value="<?= ($end_date ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_payment_date'); ?></label>
                <input name="start_payment_date" class="form-control" id="start_payment_date" type="date" value="<?= ($start_payment_date ?? ''); ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_payment_date'); ?></label>
                <input name="end_payment_date" class="form-control" id="end_payment_date" type="date" value="<?= ($end_payment_date ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <button class="btn btn-primary" id="btn_filter"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('finances/expenses'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
<?php if ($Owner || $Admin) {
  echo admin_form_open('finances/expenses/actions', 'id="action-form"');
} ?>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="expenses" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="EXPData" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('ref_no'); ?></th>
              <th><?= lang('category'); ?></th>
              <th><?= lang('amount'); ?></th>
              <th><?= lang('note'); ?></th>
              <th><?= lang('paid_by'); ?></th>
              <th><?= lang('created_by'); ?></th>
              <th><?= lang('approval_status'); ?></th>
              <th><?= lang('payment_date'); ?></th>
              <th><?= lang('payment_status'); ?></th>
              <th><?= lang('supplier'); ?></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i>
              </th>
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
              <th></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
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
<script>
  $(document).ready(function () {
    $('#export_excel').click(function () {
      filterExpense(1);
    });

    $('#export_payment').click(function () {
      let data = 'form_action=export_payment';
      $('input[name="val[]"]').each(function () {
        if ($(this).is(':checked')) {
          data += `&val[]=${$(this).val()}`;
        }
      });

      $.ajax({
        method: 'GET',
        xhrFields: {
          responseType: 'blob'
        },
        success: function (data) {
          console.log(data);
          if (typeof data == 'object' && data.error) {
            addAlert(data.msg, 'danger');
            return false;
          }

          let a = document.createElement('a');
          let url = window.URL.createObjectURL(data);
          a.href = url;
          a.download = 'Expense_Payments.xlsx';
          a.click();
          a.remove();

          window.URL.revokeObjectURL(url);
        },
        url: site.base_url + 'finances/expenses/actions?' + data
      });

      event.preventDefault();
    });

    $('#btn_filter').click(function () {
      filterExpense();
    });

    function filterExpense (xls = false) {
      let q = '';
      let reference          = $('#reference').val();
      let supplier_name      = $('#supplier_name').val();
      let status             = $('#status').val();
      let warehouse          = $('#warehouse').val();
      let payment_status     = $('#payment_status').val();
      let start_date         = $('#start_date').val();
      let end_date           = $('#end_date').val();
      let start_payment_date = $('#start_payment_date').val();
      let end_payment_date   = $('#end_payment_date').val();

      if (reference)      q += '&reference=' + reference;
      if (supplier_name)  q += '&supplier=' + supplier_name;
      if (warehouse) {
        $.each(warehouse, function (index, value) {
          q += '&warehouse[]=' + value;
        });
      }  
      if (status) {
        $.each(status, function (index, value) {
          q += '&status[]=' + value;
        });
      }         
      if (payment_status) {
        $.each(payment_status, function (index, value) {
          q += '&payment_status[]=' + value;
        });
      }
      if (start_date)         q += '&start_date=' + start_date;
      if (end_date)           q += '&end_date=' + end_date;
      if (start_payment_date) q += '&start_payment_date=' + start_payment_date;
      if (end_payment_date)   q += '&end_payment_date=' + end_payment_date;

      if (xls) {
        location.href = site.base_url + 'finances/expenses/getExpenses?xls=1' + q;
      } else {
        //console.log(q);
        location.href = site.base_url + 'finances/expenses?' + q;
      }
    }
  });
</script>