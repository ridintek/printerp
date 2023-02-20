<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '?';

if ($reference) {
  $q .= '&reference=' . $reference;
}
if ($warehouses) {
  foreach ($warehouses as $wh) {
    $q .= '&warehouses[]=' . $wh;
  }
}
if ($created_by) {
  $q .= '&created_by=' . $created_by;
}
if ($customer) {
  $q .= '&customer=' . $customer;
}
if ($item_status) {
  $q .= '&item_status=' . $item_status;
}
if ($payment_status) {
  $q .= '&payment_status=' . $payment_status;
}
if ($start_date) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date) {
  $q .= '&end_date=' . $end_date;
}

?>
<script>
  $(document).ready(function() {
    oTable = $('#SLData').dataTable({
      "aaSorting": [
        [1, "desc"],
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': '<?= admin_url('operators/getOrderedItems' . $q); ?>',
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
      'fnRowCallback': function(nRow, aData, iDisplayIndex) {
        //$("td:first", nRow).html(oSettings._iDisplayStart+iDisplayIndex +1);
        nRow.id = aData[0];
        nRow.className = "sales_item_link";
        //if(aData[7] > aData[9]){ nRow.className = "product_link warning"; } else { nRow.className = "product_link"; }
        return nRow;
      },
      "aoColumns": [{
          "bSortable": false,
          "mRender": checkbox
        }, {
          "mRender": fld
        }, {
          "mRender": fld
        }, {
          "mRender": SQLTime
        }, {
          "mRender": SQLTime
        },
        null, null, null, null, null, null, null,
        {
          "mRender": renderStatus
        }
      ],
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {

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

    if (localStorage.getItem('remove_slls')) {
      if (localStorage.getItem('slitems')) {
        localStorage.removeItem('slitems');
      }
      if (localStorage.getItem('sldiscount')) {
        localStorage.removeItem('sldiscount');
      }
      if (localStorage.getItem('sltax2')) {
        localStorage.removeItem('sltax2');
      }
      if (localStorage.getItem('slref')) {
        localStorage.removeItem('slref');
      }
      if (localStorage.getItem('slshipping')) {
        localStorage.removeItem('slshipping');
      }
      if (localStorage.getItem('slnote')) {
        localStorage.removeItem('slnote');
      }
      if (localStorage.getItem('slinnote')) {
        localStorage.removeItem('slinnote');
      }
      if (localStorage.getItem('slcustomer')) {
        localStorage.removeItem('slcustomer');
      }
      if (localStorage.getItem('slwarehouse')) {
        localStorage.removeItem('slwarehouse');
      }
      if (localStorage.getItem('slcurrency')) {
        localStorage.removeItem('slcurrency');
      }
      if (localStorage.getItem('sldate')) {
        localStorage.removeItem('sldate');
      }
      if (localStorage.getItem('slsale_status')) {
        localStorage.removeItem('slsale_status');
      }
      if (localStorage.getItem('slpayment_status')) {
        localStorage.removeItem('slpayment_status');
      }
      if (localStorage.getItem('paid_by')) {
        localStorage.removeItem('paid_by');
      }
      if (localStorage.getItem('amount_1')) {
        localStorage.removeItem('amount_1');
      }
      if (localStorage.getItem('paid_by_1')) {
        localStorage.removeItem('paid_by_1');
      }
      if (localStorage.getItem('pcc_holder_1')) {
        localStorage.removeItem('pcc_holder_1');
      }
      if (localStorage.getItem('pcc_type_1')) {
        localStorage.removeItem('pcc_type_1');
      }
      if (localStorage.getItem('pcc_month_1')) {
        localStorage.removeItem('pcc_month_1');
      }
      if (localStorage.getItem('pcc_year_1')) {
        localStorage.removeItem('pcc_year_1');
      }
      if (localStorage.getItem('pcc_no_1')) {
        localStorage.removeItem('pcc_no_1');
      }
      if (localStorage.getItem('cheque_no_1')) {
        localStorage.removeItem('cheque_no_1');
      }
      if (localStorage.getItem('slpayment_term')) {
        localStorage.removeItem('slpayment_term');
      }
      localStorage.removeItem('remove_slls');
    }

    <?php
    if ($this->session->userdata('remove_slls')) { ?>
      if (localStorage.getItem('slitems')) {
        localStorage.removeItem('slitems');
      }
      if (localStorage.getItem('sldiscount')) {
        localStorage.removeItem('sldiscount');
      }
      if (localStorage.getItem('sltax2')) {
        localStorage.removeItem('sltax2');
      }
      if (localStorage.getItem('slref')) {
        localStorage.removeItem('slref');
      }
      if (localStorage.getItem('slshipping')) {
        localStorage.removeItem('slshipping');
      }
      if (localStorage.getItem('slwarehouse')) {
        localStorage.removeItem('slwarehouse');
      }
      if (localStorage.getItem('slnote')) {
        localStorage.removeItem('slnote');
      }
      if (localStorage.getItem('slinnote')) {
        localStorage.removeItem('slinnote');
      }
      if (localStorage.getItem('slcustomer')) {
        localStorage.removeItem('slcustomer');
      }
      if (localStorage.getItem('slcurrency')) {
        localStorage.removeItem('slcurrency');
      }
      if (localStorage.getItem('sldate')) {
        localStorage.removeItem('sldate');
      }
      if (localStorage.getItem('slsale_status')) {
        localStorage.removeItem('slsale_status');
      }
      if (localStorage.getItem('slpayment_status')) {
        localStorage.removeItem('slpayment_status');
      }
      if (localStorage.getItem('paid_by')) {
        localStorage.removeItem('paid_by');
      }
      if (localStorage.getItem('amount_1')) {
        localStorage.removeItem('amount_1');
      }
      if (localStorage.getItem('paid_by_1')) {
        localStorage.removeItem('paid_by_1');
      }
      if (localStorage.getItem('pcc_holder_1')) {
        localStorage.removeItem('pcc_holder_1');
      }
      if (localStorage.getItem('pcc_type_1')) {
        localStorage.removeItem('pcc_type_1');
      }
      if (localStorage.getItem('pcc_month_1')) {
        localStorage.removeItem('pcc_month_1');
      }
      if (localStorage.getItem('pcc_year_1')) {
        localStorage.removeItem('pcc_year_1');
      }
      if (localStorage.getItem('pcc_no_1')) {
        localStorage.removeItem('pcc_no_1');
      }
      if (localStorage.getItem('cheque_no_1')) {
        localStorage.removeItem('cheque_no_1');
      }
      if (localStorage.getItem('slpayment_term')) {
        localStorage.removeItem('slpayment_term');
      }
    <?php
      $this->sma->unset_data('remove_slls');
    } ?>

    $(document).on('click', '.sledit', function(e) {
      if (localStorage.getItem('slitems')) {
        e.preventDefault();
        var href = $(this).attr('href');
        bootbox.confirm("<?= lang('you_will_loss_sale_data') ?>", function(result) {
          if (result) {
            window.location.href = href;
          }
        });
      }
    });
    $(document).on('click', '.slduplicate', function(e) {
      if (localStorage.getItem('slitems')) {
        e.preventDefault();
        var href = $(this).attr('href');
        bootbox.confirm("<?= lang('you_will_loss_sale_data') ?>", function(result) {
          if (result) {
            window.location.href = href;
          }
        });
      }
    });
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue">
      <?php
      $whs = lang('all_warehouses');

      if ($warehouses) {
        $whs = '';
        foreach ($warehouses as $warehouse_id) {
          $wh = $this->site->getWarehouseByID($warehouse_id);
          $whs .= $wh->name . ', ';
        }

        $whs = substr(rd_trim($whs), 0, -1);
      }
      ?>
      <i class="fa-fw fad fa-list"></i><?= lang('ordered_items') . ' (' . $whs . ')'; ?>
      <?= (getPost('from_date') ? '(' . getPost('from_date') . ')' : '') . (getPost('to_date') ? ' to (' . getPost('to_date') . ')' : ''); ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('operators/completeSaleItems'); ?>" id="change_status" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
                <i class="fad fa-fw fa-hexagon-check"></i> Complete Sale Items
              </a>
            </li>
            <li>
              <a href="#" id="finish_sales">
                <i class="fad fa-fw fa-box-check"></i> Finish Sales
              </a>
            </li>
            <li>
              <a href="#" id="delivery_sales">
                <i class="fad fa-fw fa-hand-holding-box"></i> Delivery Sales
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
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <?php echo admin_form_open('operators/orders'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" id="reference" class="form-control" name="reference" value="<?= ($reference ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('warehouse', 'warehouses'); ?>
                <?php
                $bl[''] = '';
                $warehouses = $this->site->getAllwarehouses();
                if (!empty($warehouses)) {
                  foreach ($warehouses as $warehouse) {
                    $bl[$warehouse->id] = $warehouse->name;
                  }
                }
                ?>
                <?= form_multiselect('warehouses[]', $bl, ($warehouses ?? ''), 'class="form-control select2" id="warehouses" data-placeholder="Select Warehouses" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('created_by'); ?></label>
                <?= form_dropdown('created_by', [], $created_by, 'id="created_by" class="form-control user" style="width:100%"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('customer', 'customer'); ?>
                <select name="customer" id="customer" class="form-control" type="text" value="<?= ($customer ?? ''); ?>" style="width:100%;"></select>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('item_status', 'item_status'); ?>
                <?php
                $all_status = $this->sma->getAllStatus();
                $st = [];
                $st[''] = lang('select') . ' ' . lang('item_status');
                foreach ($all_status as $status) {
                  if (
                    $status == 'completed' || $status == 'delivered' || $status == 'need_payment' ||
                    $status == 'in_production' || $status == 'waiting_production'
                  ) {
                    $st[$status] = lang($status);
                  }
                }
                echo form_dropdown('item_status', $st, ($item_status ?? ''), 'class="select2" id="item_status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('payment_status'); ?></label>
                <?php
                $st = [];
                $st[''] = lang('select') . ' ' . lang('payment_status');
                foreach ($all_status as $status) {
                  if ($status == 'due' || $status == 'paid' || $status == 'partial' || $status == 'pending') {
                    $st[$status] = lang($status);
                  }
                }
                echo form_dropdown('pay_status', $st, ($payment_status ?? ''), 'class="select2" id="pay_status" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('from_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="from_date" value="<?= ($start_date ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('to_date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="to_date" value="<?= ($end_date ?? '') ?>" />
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <button type="button" class="btn btn-primary" id="btn_filter"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('operators/orders'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="orders" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php if (!empty($Owner) || !empty($GP['bulk_actions'])) {
          echo admin_form_open('sales/sale_actions', 'id="action-form"');
        } ?>
        <div class="table-responsive">
          <table id="SLData" class="table table-bordered table-condensed table-hover table-striped" cellpadding="0" cellspacing="0" borders="0">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkth" type="checkbox" name="check" />
                </th>
                <th><?= lang('date'); ?></th>
                <th><?= lang('due_date'); ?></th>
                <th><?= lang('duration'); ?></th>
                <th><?= lang('time_left'); ?></th>
                <th><?= lang('reference'); ?></th>
                <th><?= lang('operator'); ?></th>
                <th><?= lang('biller'); ?></th>
                <th><?= lang('warehouse'); ?></th>
                <th><?= lang('customer'); ?></th>
                <th><?= lang('product_code'); ?></th>
                <th><?= lang('product_name'); ?></th>
                <th><?= lang('item_status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="13" class="dataTables_empty"><?= lang('loading_data'); ?></td>
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
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php if (!empty($Owner) || !empty($GP['bulk_actions'])) { ?>
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
    $('#btn_filter').click(function() {
      let created_by = $('#created_by').val();
      let customer = $('#customer').val();
      let end_date = $('#end_date').val();
      let item_status = $('#item_status').val();
      let pay_status = $('#pay_status').val();
      let reference = $('#reference').val();
      let start_date = $('#start_date').val();
      let warehouses = $('#warehouses').val();
      let q = '?';

      if (created_by) {
        q += '&created_by=' + created_by;
      }
      if (customer) {
        q += '&customer=' + customer;
      }
      if (end_date) {
        q += '&end_date=' + end_date;
      }
      if (item_status) {
        q += '&item_status=' + item_status;
      }
      if (pay_status) {
        q += '&pay_status=' + pay_status;
      }
      if (reference) {
        q += '&reference=' + reference;
      }
      if (start_date) {
        q += '&start_date=' + start_date;
      }
      if (warehouses) {
        for (let x in warehouses) {
          q += '&warehouses[]=' + warehouses[x];
        }
      }

      location.href = site.base_url + 'operators/orders' + q;
    });

    $('#finish_sales').click(function() {
      addConfirm({
        title: 'Finished Sale Items',
        message: 'Finished sale item yang terpilih?',
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
            processData: false,
            data: formData,
            method: 'POST',
            success: function(data) {
              if (data.error == 0) {
                if (typeof oTable == 'object') oTable.fnDraw(false);
                addAlert(data.msg, 'success');
              } else {
                addAlert(data.msg, 'danger');
              }
            },
            url: site.base_url + '/operators/finishSales'
          })
        }
      })
    });

    $('#delivery_sales').click(function() {
      addConfirm({
        title: 'Delivered Sale Items',
        message: 'Delivered sale item yang terpilih?',
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
            processData: false,
            data: formData,
            method: 'POST',
            success: function(data) {
              if (data.error == 0) {
                if (typeof oTable == 'object') oTable.fnDraw(false);
                addAlert(data.msg, 'success');
              } else {
                addAlert(data.msg, 'danger');
              }
            },
            url: site.base_url + '/operators/deliverySales'
          })
        }
      })
    });

    typing('nopgboss', function() {
      if ($('#nopg').length) {
        Notify.success('Cheat activated', 'top-left');
        $('#nopg').val(1);
      } else {
        Notify.error('Harus pilih item dulu.', 'top-left');
      }
    });
  });
</script>