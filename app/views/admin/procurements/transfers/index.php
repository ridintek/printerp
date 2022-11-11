<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($reference = $this->input->get('reference')) {
  $q .= "&reference=" . $reference;
}
if ($warehouse_from = $this->input->get('warehouse_from')) {
  foreach ($warehouse_from as $whf) {
    $q .= "&warehouse_from[]=" . $whf;
  }
}
if ($warehouse_to = $this->input->get('warehouse_to')) {
  foreach ($warehouse_to as $wht) {
    $q .= "&warehouse_to[]=" . $wht;
  }
}
if ($status = $this->input->get('status')) {
  foreach ($status as $st) {
    $q .= "&status[]=" . $st;
  }
}
if ($payment_status = $this->input->get('payment_status')) {
  foreach ($payment_status as $pst) {
    $q .= "&payment_status[]=" . $pst;
  }
}
if ($start_date = $this->input->get('start_date')) {
  $q .= "&start_date=" . $start_date;
}
if ($end_date = $this->input->get('end_date')) {
  $q .= "&end_date=" . $end_date;
}
?>
<script>
  $(document).ready(function () {
    if (localStorage.getItem('toitems')) {
      localStorage.removeItem('toitems');
    }
    if (localStorage.getItem('toshipping')) {
      localStorage.removeItem('toshipping');
    }
    if (localStorage.getItem('toref')) {
      localStorage.removeItem('toref');
    }
    if (localStorage.getItem('to_warehouse')) {
      localStorage.removeItem('to_warehouse');
    }
    if (localStorage.getItem('tonote')) {
      localStorage.removeItem('tonote');
    }
    if (localStorage.getItem('from_warehouse')) {
      localStorage.removeItem('from_warehouse');
    }
    if (localStorage.getItem('todate')) {
      localStorage.removeItem('todate');
    }
    if (localStorage.getItem('tostatus')) {
      localStorage.removeItem('tostatus');
    }
    if (localStorage.getItem('transfer_add')) {
      localStorage.removeItem('transfer_add');
    }


    oTable = $('#TOData').dataTable({
      "aaSorting": [[1, "desc"], [2, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('procurements/transfers/getTransfers?') . $q ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [
        {"bSortable": false,"mRender": checkbox}, {"mRender": fld}, null, null, null, {"mRender": currencyFormat},
        {"mRender": currencyFormat}, {"mRender": currencyFormat}, {"mRender": payment_status}, {"mRender": renderStatus},
        {"bSortable": false,"mRender": attachment}, {"bSortable": false}
      ],
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        var oSettings = oTable.fnSettings();
        nRow.id = aData[0];
        nRow.className = "transfer_link";
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var row_total = 0, tax = 0, gtotal = 0, gpaid = 0, gbalance = 0;
        for (var i = 0; i < aaData.length; i++) {
          gtotal   += parseFloat(aaData[aiDisplay[i]][5]);
          gpaid    += parseFloat(aaData[aiDisplay[i]][6]);
          gbalance += parseFloat(aaData[aiDisplay[i]][7]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[5].innerHTML = currencyFormat(formatMoney(gtotal));
        nCells[6].innerHTML = currencyFormat(formatMoney(gpaid));
        nCells[7].innerHTML = currencyFormat(formatMoney(gbalance));
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

    $('body').on('click', '#delete', function (e) {
			e.preventDefault();
			let values = [];
			$('.bpo').popover('hide');
			$('input[name="val[]"]').each(function () {
				if (this.checked) values.push(this.value);
			});
			$.ajax({
				data: {
					<?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
					form_action: 'delete',
					val: values
				},
				method: 'POST',
				success: function (data) {
					if (typeof data == 'object' && ! data.error) {
						if (oTable) oTable.fnDraw(false);
						addAlert(data.msg, 'success');
					} else if (typeof data == 'object') {
						addAlert(data.msg, 'danger');
					} else {
            addAlert('Response data is not valid.', 'danger');
          }
				},
				url: '<?= admin_url('procurements/transfers/actions'); ?>'
			});
		});
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-exchange"></i><?= lang('transfers_list'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip"  data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('procurements/transfers/add') ?>">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_transfer') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_excel" data-action="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li>
              <a href="#" id="combine" data-action="combine">
                <i class="fad fa-fw fa-file-pdf"></i> <?=lang('combine_to_pdf')?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_transfers') ?></b>"
               data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
               data-html="true" data-placement="left">
               <i class="fad fa-fw fa-trash"></i> <?= lang('delete_transfers') ?>
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

        <p class="introtext"><strong><?= lang('to') . ' ' . lang('warehouse'); ?></strong>: <?= (isset($warehouse) ? $warehouse->name : lang('all_warehouses'));?></p>
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
                <label><?= lang('warehouse_from'); ?></label>
                <?php
                  $warehouses = $this->site->getAllWarehouses();
                  if ($warehouses) {
                    $whs = [];
                    foreach ($warehouses as $wh) {
                      $whs[$wh->id] = $wh->name;
                    }
                    echo form_multiselect('warehouse_from', $whs, ($warehouse_from ?? ''), 'class="select2" id="warehouse_from" style="width:100%;"');
                  }
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('warehouse_to'); ?></label>
                <?php
                  if ($warehouses) {
                    $whs = [];
                    foreach ($warehouses as $wh) {
                      $whs[$wh->id] = $wh->name;
                    }
                    echo form_multiselect('warehouse_to', $whs, ($warehouse_to ?? ''), 'class="select2" id="warehouse_to" style="width:100%;"');
                  }
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('transfer_status'); ?></label>
                <?php
                $all_status = $this->sma->getAllStatus();
                $stat = [];
                foreach ($all_status as $st) {
                  if ($st == 'packing' || $st == 'partial' || $st == 'pending' || $st == 'received' || $st == 'sent') {
                    $stat[$st] = lang($st);
                  }
                }
                echo form_multiselect('status', $stat, ($status ?? ''), 'class="select2" id="status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('payment_status'); ?></label>
                <?php
                $stat = [];
                foreach ($all_status as $status) {
                  if ($status == 'need_approval' || $status == 'partial' || $status == 'paid' || $status == 'pending') {
                    $stat[$status] = lang($status);
                  }
                }
                echo form_multiselect('paystatus', $stat, ($payment_status ?? ''), 'class="select2" id="payment_status" style="width:100%;"'); ?>
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
            <div class="col-sm-12">
              <div class="form-group">
                <button type="button" class="btn btn-primary" id="btn_filter"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('procurements/transfers'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="transfers" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php if ($Owner || $Admin || $GP['bulk_actions']) {
          echo admin_form_open('procurements/transfers/transfer_actions', 'id="action-form"');
        } ?>
        <div class="table-responsive">
          <table id="TOData" cellpadding="0" cellspacing="0" borders="0"
               class="table table-bordered table-condensed table-hover table-striped">
            <thead>
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('ref_no'); ?></th>
              <th><?= lang('warehouse') . ' (' . lang('from') . ')'; ?></th>
              <th><?= lang('warehouse') . ' (' . lang('to') . ')'; ?></th>
              <th><?= lang('grand_total'); ?></th>
              <th><?= lang('paid'); ?></th>
              <th><?= lang('balance'); ?></th>
              <th><?= lang('payment_status'); ?></th>
              <th><?= lang('status'); ?></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
              <th style="width:100px;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="11" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
              <th style="width:100px; text-align: center;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
        <?php if ($Owner || $Admin || $GP['bulk_actions']) { ?>
        <div style="display: none;">
          <input type="hidden" name="form_action" value="" id="form_action"/>
          <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
        </div>
        <?= form_close() ?>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('#btn_filter').click(function () {
      filter_transfer();
    });

    $('#export_excel').click(function () {
      filter_transfer(1);
    });

    function filter_transfer (xls = false) {
      let q = '';
      let reference      = $('#reference').val();
      let warehouse_from = $('#warehouse_from').val();
      let warehouse_to   = $('#warehouse_to').val();
      let status         = $('#status').val();
      let payment_status = $('#payment_status').val();
      let start_date     = $('#start_date').val();
      let end_date       = $('#end_date').val();

      if (reference)      q += '&reference=' + reference;
      if (warehouse_from) {
        $.each(warehouse_from, function (index, value) {
          q += '&warehouse_from[]=' + value;
        });
      }
      if (warehouse_to) {
        $.each(warehouse_to, function (index, value) {
          q += '&warehouse_to[]=' + value;
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
      if (start_date) q += '&start_date=' + start_date;
      if (end_date)   q += '&end_date=' + end_date;

      if (xls) {
        location.href = site.base_url + 'procurements/transfers/getTransfers?xls=1' + q;
      } else {
        location.href = site.base_url + 'procurements/transfers?' + q;
      }
    }
  });
</script>