<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';

$end_date  = getGET('end_date');
$item_name = getGET('item');
$reference = getGET('reference');
$start_date = getGET('start_date');
$warehouse_to = getGET('warehouse');

if ($end_date)     $q .= '&end_date=' . $end_date;
if ($item_name)    $q .= '&item=' . $item_name;
if ($reference)    $q .= '&reference=' . $reference;
if ($start_date)   $q .= '&start_date=' . $start_date;
if ($warehouse_to) $q .= '&warehouse=' . $warehouse_to;

?>
<script>
  $(document).ready(function() {
    if (localStorage.getItem('iuitems')) {
      localStorage.removeItem('iuitems');
    }
    if (localStorage.getItem('to_warehouse')) {
      localStorage.removeItem('to_warehouse');
    }
    if (localStorage.getItem('note')) {
      localStorage.removeItem('note');
    }
    if (localStorage.getItem('from_warehouse')) {
      localStorage.removeItem('from_warehouse');
    }
    if (localStorage.getItem('date')) {
      localStorage.removeItem('date');
    }
    if (localStorage.getItem('status')) {
      localStorage.removeItem('status');
    }

    oTable = $('#IUData').dataTable({
      "aaSorting": [
        [1, "DESC"]
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': '<?= admin_url('procurements/internal_uses/getInternalUses' . $q) ?>',
      'fnServerData': function(sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({
          'dataType': 'json',
          'type': 'POST',
          'url': sSource,
          'data': aoData,
          'success': fnCallback
        });
      },
      "aoColumns": [{
          "bSortable": false,
          "mRender": checkbox
        }, {
          "mRender": fld
        }, null, null, null, null, null,
        {
          "mRender": currencyFormat
        },
        null, {
          "mRender": notes
        }, {
          "mRender": renderStatus
        },
        {
          "bSortable": false,
          "mRender": attachment
        }, {
          "bSortable": false
        }
      ],
      'fnRowCallback': function(nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "internal_use_link";
        return nRow;
      },
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        var row_total = 0,
          tax = 0,
          gtotal = 0,
          gpaid = 0,
          gbalance = 0;
        for (var i = 0; i < aaData.length; i++) {
          gtotal += parseFloat(aaData[aiDisplay[i]][7]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[7].innerHTML = currencyFormat(formatMoney(gtotal));
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

    $('body').on('click', '#delete', function(e) {
      e.preventDefault();
      let values = [];
      $('.bpo').popover('hide');
      $('input[name="val[]"]').each(function() {
        if (this.checked) values.push(this.value);
      });
      $.ajax({
        data: {
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
          form_action: 'delete',
          val: values
        },
        method: 'POST',
        success: function(data) {
          if (typeof data == 'object' && !data.error) {
            if (oTable) oTable.fnDraw(false);
            addAlert(data.msg, 'success');
          } else if (typeof data == 'object') {
            addAlert(data.msg, 'danger');
          } else {
            addAlert('Response data is not valid.', 'danger');
          }
        },
        url: '<?= admin_url('procurements/internal_uses/actions'); ?>'
      });
    });
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-hand-receiving"></i><?= lang('internal_uses_list'); ?></h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('procurements/internal_uses/add') ?>">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_internal_use') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li>
              <a href="#" id="sync_iuse">
                <i class="fad fa-fw fa-sync"></i> Sync Internal Uses
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_internal_uses') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?= lang('delete_internal_uses') ?>
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
        <p class="introtext"><strong><?= lang('to') . ' ' . lang('warehouse'); ?></strong>: <?= ($warehouse ? $warehouse->name : lang('all_warehouses')); ?></p>
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
                <label><?= lang('warehouse') . ' (' . lang('to') . ')'; ?></label>
                <?php
                $all_warehouses = $this->site->getAllWarehouses();
                $wh[''] = 'Select Warehouse To';
                foreach ($all_warehouses as $warehouse) {
                  $wh[$warehouse->id] = $warehouse->name;
                }
                ?>
                <?= form_dropdown('warehouse', $wh, ($warehouse_to ?? ''), 'class="form-control select2" id="warehouse_to" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('item_name'); ?></label>
                <input type="text" class="form-control" id="item_name" placeholder="Search item name" value="<?= ($item_name ?? ''); ?>">
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
                <a href="#" name="filter_submit" id="filter_submit" class="btn btn-primary"><i class="fad fa-filter"></i> Filter</a>
                <a href="<?= admin_url('procurements/internal_uses'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="internal_uses" placeholder="<?= lang('search'); ?>">
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
          <table id="IUData" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkth" type="checkbox" name="check" />
                </th>
                <th><?= lang('date'); ?></th>
                <th><?= lang('ref_no'); ?></th>
                <th>PIC</th>
                <th><?= lang('warehouse') . ' (' . lang('from') . ')'; ?></th>
                <th><?= lang('warehouse') . ' (' . lang('to') . ')'; ?></th>
                <th><?= lang('items'); ?></th>
                <th><?= lang('grand_total'); ?></th>
                <th><?= lang('counter'); ?></th>
                <th><?= lang('note'); ?></th>
                <th><?= lang('status'); ?></th>
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
                <th style="width:100px;"><?= lang('actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="13" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
              </tr>
            </tbody>
            <tfoot class="dtFilter">
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
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
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
                <th style="width:100px; text-align: center;"><?= lang('actions'); ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php if ($Owner || $Admin || $GP['bulk_actions']) { ?>
          <div style="display: none;">
            <input type="hidden" name="form_action" value="" id="form_action" />
            <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
          </div>
          <?= form_close() ?>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#sync_iuse').click(function() {
      addConfirm({
        title: 'Sync Internal Use',
        message: 'Sinkronkan Internal Use?',
        onok: () => {
          let formData = new FormData();
          let vals = $('input[name="val[]"]');

          formData.append('<?= csrf_token_name() ?>', '<?= csrf_hash() ?>');

          for (let x in vals) {
            if (vals[x].checked) {
              formData.append('val[]', vals[x].value);
            }
          }

          $.ajax({
            contentType: false,
            data: formData,
            error: (xhr) => {
              addAlert(xhr.responseJSON.message, 'danger');
            },
            processData: false,
            method: 'POST',
            success: function(data) {
              if (typeof oTable == 'object') oTable.fnDraw(false);
              if (typeof Table == 'object') Table.draw(false);

              addAlert(data.message, 'success');
            },
            url: site.base_url + 'procurements/internal_uses/sync'
          })
        }
      })
    });

    $('#export_excel').click(function() {
      event.preventDefault();
      let q = '?xls=1';
      let end_date = $('#end_date').val();
      let item_name = $('#item_name').val();
      let reference = $('#reference').val();
      let start_date = $('#start_date').val();
      let warehouse_to = $('#warehouse_to').val();

      if (end_date) q += '&end_date=' + end_date;
      if (item_name) q += '&item=' + item_name;
      if (reference) q += '&reference=' + reference;
      if (start_date) q += '&start_date=' + start_date;
      if (warehouse_to) q += '&warehouse=' + warehouse_to;

      location.href = site.base_url + 'procurements/internal_uses/getInternalUses' + q;
    });

    $('#filter_submit').click(function() {
      event.preventDefault();
      let q = '?';
      let end_date = $('#end_date').val();
      let item_name = $('#item_name').val();
      let reference = $('#reference').val();
      let start_date = $('#start_date').val();
      let warehouse_to = $('#warehouse_to').val();

      if (end_date) q += '&end_date=' + end_date;
      if (item_name) q += '&item=' + item_name;
      if (reference) q += '&reference=' + reference;
      if (start_date) q += '&start_date=' + start_date;
      if (warehouse_to) q += '&warehouse=' + warehouse_to;

      location.href = site.base_url + 'procurements/internal_uses' + q;
    });
  })
</script>