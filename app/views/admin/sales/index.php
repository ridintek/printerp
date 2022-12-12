<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

if ($reference = getGET('reference')) {
  $q .= '&reference=' . $reference;
}
if ($billers = getGET('billers')) {
  foreach ($billers as $bl) {
    $q .= '&billers[]=' . $bl;
  }
}
if ($customer = getGET('customer')) {
  $q .= '&customer=' . $customer;
}
if ($status = getGET('status')) {
  $q .= '&status=' . $status;
}
if ($created_by = getGET('created_by')) {
  $q .= '&created_by=' . $created_by;
}
if ($payment_status = getGET('payment_status')) {
  $q .= '&payment_status=' . $payment_status;
}
if ($tb_account = getGET('tb_account')) {
  $q .= '&tb_account=' . $tb_account;
}
if ($warehouses = getGET('warehouses')) {
  foreach ($warehouses as $wh) {
    $q .= '&warehouses[]=' . $wh;
  }
}
if ($start_date = getGET('start_date')) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date = getGET('end_date')) {
  $q .= '&end_date=' . $end_date;
}
if ($group_by = getGET('group_by')) {
  $q .= '&group_by=' . $group_by;
}
?>
<script>
  $(document).ready(function() {
    oTable = $('#SLData').dataTable({
      "aaSorting": [
        [1, "desc"]
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': site.base_url + '<?= 'sales/getSales?' . $q; ?>',
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
        nRow.id = aData[0];
        // console.log(aData);
        nRow.className = "invoice_link";
        return nRow;
      },
      "aoColumns": [{
          "bSortable": false,
          "mRender": checkbox
        }, {
          "mRender": fld
        }, null, null, null, null, null, null, {
          "mRender": renderStatus
        }, {
          "mRender": currencyFormat
        }, {
          "mRender": currencyFormat
        }, {
          "mRender": currencyFormat
        }, {
          "mRender": pay_status
        }, {
          "bSortable": false,
          "mRender": attachment
        }, {
          "bVisible": false
        }, {
          "bSortable": false
        }
      ],
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        var gtotal = 0, paid = 0, balance = 0;

        for (var i = 0; i < aaData.length; i++) {
          gtotal  += parseFloat(aaData[aiDisplay[i]][9]);
          paid    += parseFloat(aaData[aiDisplay[i]][10]);
          balance += parseFloat(aaData[aiDisplay[i]][11]);
        }

        var nCells = nRow.getElementsByTagName('th');
        nCells[9].innerHTML = currencyFormat(parseFloat(gtotal));
        nCells[10].innerHTML = currencyFormat(parseFloat(paid));
        nCells[11].innerHTML = currencyFormat(parseFloat(balance));
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

    if (localStorage.getItem('edit_mode')) {
      localStorage.removeItem('edit_mode');
    }

    <?php if (XSession::get('remove_slls')) { ?>
    localStorage.setItem('remove_slls', 1);
    <?php   $this->sma->unset_data('remove_slls'); ?>
    <?php } ?>

    if (localStorage.getItem('remove_slls')) {
      if (localStorage.getItem('slitems')) {
        localStorage.removeItem('slitems');
      }
      if (localStorage.getItem('slref')) {
        localStorage.removeItem('slref');
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
      if (localStorage.getItem('slbiller')) {
        localStorage.removeItem('slbiller');
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
      if (localStorage.getItem('slpayment_term')) {
        localStorage.removeItem('slpayment_term');
      }
      if (localStorage.getItem('sltb_account')) {
        localStorage.removeItem('sltb_account');
      }
      localStorage.removeItem('remove_slls');
    }

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
      <i class="fa-fw fad fa-list"></i><?= lang('sales') . ' (' . ($biller ? $biller->name : lang('all_billers')) . ')'; ?>
      <?= ($start_date ? '(' . $start_date . ')' : '') . ($end_date ? ' to (' . $end_date . ')' : ''); ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('sales/add') ?>">
                <i class="fad fa-plus-circle"></i> <?= lang('add_sale') ?>
              </a>
            </li>
            <li>
              <a href="<?= admin_url('sales/discpay') ?>" id="discpay" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                <i class="fad fa-fw fa-dollar"></i> Diskon &amp; Pelunasan
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li>
              <a href="#" id="sync">
                <i class="fad fa-fw fa-sync"></i> <?= lang('sync_sales') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= lang('delete_sales') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?= lang('delete_sales') ?>
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
        <p class="introtext"><?= lang('list_results'); ?></p>
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
                <label><?= lang('created_by'); ?></label>
                <select name="created_by" class="select2 user" id="created_by" value="<?= ($created_by ?? ''); ?>" style="width:100%;">
                </select>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('customer'); ?></label>
                <select name="customer" class="select2" id="customer" value="<?= ($customer ?? ''); ?>" style="width:100%;">
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('sale_status'); ?></label>
                <?php
                $st = [
                  '' => lang('select_sale_status'),
                  'completed' => lang('completed'),
                  'completed_partial' => lang('completed_partial'),
                  'delivered' => lang('delivered'),
                  'need_payment' => lang('need_payment'),
                  'preparing' => lang('preparing'),
                  'waiting_production' => lang('waiting_production')
                ];

                echo form_dropdown('status', $st, ($status ?? ''), 'class="select2" id="status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('payment_status'); ?></label>
                <?php
                $st = [
                  '' => lang('select_payment_status'),
                  'due' => lang('due'),
                  'due_partial' => lang('due_partial'),
                  'expired' => lang('expired'),
                  'paid' => lang('paid'),
                  'partial' => lang('partial'),
                  'pending' => lang('pending'),
                  'waiting_transfer' => lang('waiting_transfer')
                ];

                echo form_dropdown('payment_status', $st, ($payment_status ?? ''), 'class="select2" id="payment_status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label for="tb_account">TB Account</label>
                <?php
                $tb = [
                  '' => 'Select use TB Account',
                  '0' => 'No',
                  '1' => 'Yes'
                ];
                echo form_dropdown('tb_account', $tb, ($tb_account ?? ''), 'class="select2" id="tb_account" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('biller'); ?></label>
                <?php
                $bl = [];
                $blrs = $this->site->getAllBillers();
                if (!empty($blrs)) {
                  foreach ($blrs as $blr) {
                    $bl[$blr->id] = $blr->name;
                  }
                }
                ?>
                <?= form_multiselect('billers[]', $bl, ($billers ?? ''), 'class="select2" id="billers" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('warehouse'); ?></label>
                <?php
                $wh = [];
                $whs = $this->site->getAllWarehouses();
                if (!empty($whs)) {
                  foreach ($whs as $wrh) {
                    $wh[$wrh->id] = $wrh->name;
                  }
                }
                ?>
                <?= form_multiselect('warehouses[]', $wh, ($warehouses ?? ''), 'class="select2" id="warehouses" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('group_by'); ?></label>
                <?php
                $groupBy = [
                  'sale'             => 'Sale',
                  'biller'           => 'Biller',
                  'customer'         => 'Customer',
                  'operator'         => 'Operator',
                  'pic'              => 'PIC',
                  'product'          => 'Product',
                  'product_category' => 'Product Category',
                  'warehouse'        => 'Warehouse',
                ];
                ?>
                <?= form_dropdown('group_by', $groupBy, '', 'class="select2" id="group_by" style="width:100%;"'); ?>
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
                <a href="#" name="submit" class="btn btn-primary" id="do_filter"><i class="fad fa-filter"></i> Filter</a>
                <a href="<?= currentUrl(); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
        <?php if (!empty($Owner) || !empty($Admin) || !empty($GP['bulk_actions'])) {
          echo admin_form_open('sales/actions', 'id="action-form"');
        } ?>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="sales" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive min-height-400">
          <table id="SLData" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th><?= lang('date'); ?></th>
                <th><?= lang('reference'); ?></th>
                <th><?= lang('pic_name'); ?></th>
                <th><?= lang('biller'); ?></th>
                <th><?= lang('warehouse'); ?></th>
                <th><?= lang('customer_group'); ?></th>
                <th><?= lang('customer'); ?></th>
                <th><?= lang('sale_status'); ?></th>
                <th><?= lang('grand_total'); ?></th>
                <th><?= lang('paid'); ?></th>
                <th><?= lang('balance'); ?></th>
                <th><?= lang('payment_status'); ?></th>
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
                <th></th>
                <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="16" class="dataTables_empty"><?= lang('loading_data'); ?></td>
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
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
                <th></th>
                <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if (!empty($Owner) || !empty($Admin) || !empty($GP['bulk_actions'])) { ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action" />
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
  </div>
  <?= form_close() ?>
<?php } ?>
<script>
  $(document).ready(function(e) {
    let created_by = '<?= $created_by; ?>';
    let customer = '<?= $customer; ?>';

    if (created_by) {
      preSelectUser('#created_by', created_by);
    }
    if (customer) {
      preSelectCustomer('#customer', customer);
    }

    $('#sync').click(function () {
      addConfirm({
        message: 'Sync some sales?',
        title: 'Sync sales',
        onok: () => {
          let formData = {};
          formData.start_date = $('#start_date').val();
          formData.end_date = $('#end_date').val();

          addAlert(`Synchronizing sales from ${formData.start_date} to ${formData.end_date}`, 'info');

          $.ajax({
            data: formData,
            success: (data) => {
              if (isObject(data)) {
                if (!data.error) {
                  addAlert(data.msg, 'success');
                } else {
                  addAlert(data.msg, 'danger');
                }
              } else {
                if (typeof data == 'string') addAlert(data, 'danger');
              }
            },
            url: site.base_url + 'sales/syncSales'
          });
        }
      });
    });

    $('#do_filter').click(function(e) {
      e.preventDefault();

      let billers        = $('#billers').val();
      let created_by     = $('#created_by').val();
      let customer       = $('#customer').val();
      let end_date       = $('#end_date').val();
      let payment_status = $('#payment_status').val();
      let tb_account     = $('#tb_account').val();
      let reference      = $('#reference').val();
      let start_date     = $('#start_date').val();
      let status         = $('#status').val();
      let warehouses     = $('#warehouses').val();
      let group_by       = $('#group_by').val();

      let q = '';

      if (billers) {
        for (let x of billers) {
          q += '&billers[]=' + x;
        }
      }

      if (warehouses) {
        for (let x of warehouses) {
          q += '&warehouses[]=' + x;
        }
      }

      if (created_by)     q += '&created_by=' + created_by;
      if (customer)       q += '&customer=' + customer;
      if (end_date)       q += '&end_date=' + end_date;
      if (payment_status) q += '&payment_status=' + payment_status;
      if (tb_account)     q += '&tb_account=' + tb_account;
      if (reference)      q += '&reference=' + reference;
      if (start_date)     q += '&start_date=' + start_date;
      if (status)         q += '&status=' + status;
      if (group_by)       q += '&group_by=' + group_by;

      location.href = site.base_url + 'sales?' + q;
    });

    $('body').on('click', '#delete', function() {
      let sale_ids = [];
      let vals = $('input[name="val[]"]');
      $.each(vals, function() {
        if (this.checked) sale_ids.push(this.value);
      });

      if (sale_ids.length == 0) {
        addAlert('No sale selected.', 'danger');
        return false;
      }

      $.ajax({
        data: {
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
          form_action: 'delete',
          val: sale_ids
        },
        method: 'POST',
        success: function(data) {
          if (typeof(data) == 'object') {
            if (!data.error) {
              addAlert(data.msg, 'success');
              if (oTable) oTable.fnDraw();
            } else {
              addAlert(data.msg, 'danger');
            }
          } else {
            addAlert('Response is not valid.', 'danger');
          }
        },
        url: '<?= current_url() . '/actions'; ?>'
      });
    });

    $('body').on('click', '#excel', function(e) {
      e.preventDefault();

      let billers        = $('#billers').val();
      let created_by     = $('#created_by').val();
      let customer       = $('#customer').val();
      let end_date       = $('#end_date').val();
      let payment_status = $('#payment_status').val();
      let tb_account     = $('#tb_account').val();
      let reference      = $('#reference').val();
      let start_date     = $('#start_date').val();
      let status         = $('#status').val();
      let warehouses     = $('#warehouses').val();

      let q = '';

      if (billers) {
        for (let x of billers) {
          q += '&billers[]=' + x;
        }
      }

      if (warehouses) {
        for (let x of warehouses) {
          q += '&warehouses[]=' + x;
        }
      }

      if (created_by)     q += '&created_by=' + created_by;
      if (customer)       q += '&customer=' + customer;
      if (end_date)       q += '&end_date=' + end_date;
      if (payment_status) q += '&payment_status=' + payment_status;
      if (tb_account)     q += '&tb_account=' + tb_account;
      if (reference)      q += '&reference=' + reference;
      if (start_date)     q += '&start_date=' + start_date;
      if (status)         q += '&status=' + status;

      location.href = site.base_url + 'sales/getSales?xls=1' + q;
    });

    // reloadContextMenu();
  });

  function reloadContextMenu() {
    let privilege = '<?= $Owner ?? $Admin ?? XSession::get('group_id'); ?>';
    let contextMenuOpt = {
      selector: '.invoice_link',
      callback: function(key, opt) {
        let opname_id = opt.$trigger.data('id');
        let reference = opt.$trigger.data('reference');
        let status = opt.$trigger.data('status');

        if (key == 'confirm') {
          if (status == 'checked' || status == 'confirmed') {
            location.href = site.base_url + 'products/stock_opname/confirm/' + opname_id;
          } else {
            addAlert(`Tidak dapat di 'confirm', status telah '${status}'.`, 'danger');
          }
        }
        if (key == 'delete') {
          alertify.dialog('confirm').set({
            message: `Are you sure to delete Stock Opname <b>${reference}</b>?`,
            onok: function() {
              let data = {
                id: opname_id
              };
              data[security.csrf_token_name] = security.csrf_hash;
              $.ajax({
                data: data,
                method: 'POST',
                success: function(data) {
                  if (typeof data == 'object' && !data.error) {
                    if (oTable) oTable.draw();
                    addAlert(data.msg, 'success');
                  } else if (typeof data == 'object' && data.error) {
                    addAlert(data.msg, 'danger');
                  } else {
                    addAlert('Unknown error', 'danger');
                  }
                },
                url: site.base_url + 'products/stock_opname/delete'
              });
            },
            title: 'Delete Stock Opname',
            transition: 'zoom'
          }).show();
        }
        if (key == 'edit') {
          location.href = site.base_url + 'products/stock_opname/edit/' + opname_id;
        }
        if (key == 'view') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id, 'modal-lg no-modal-header');
        }
        if (key == 'view_minus') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id + '?mode=minus', 'modal-lg no-modal-header');
        }
        if (key == 'view_plus') {
          showModal(site.base_url + 'products/stock_opname/view/' + opname_id + '?mode=plus', 'modal-lg no-modal-header');
        }
      }
    };

    contextMenuOpt.items = {};
    contextMenuOpt.items['confirm'] = {
      name: 'Confirm Stock Opname',
      icon: 'fas fa-box-check'
    };
    if (privilege == 1 || privilege == 2 || privilege == 6) { // 6 = finance
      contextMenuOpt.items['delete'] = {
        name: 'Delete Stock Opname',
        icon: 'fas fa-trash-alt'
      };
      contextMenuOpt.items['edit'] = {
        name: 'Edit Stock Opname',
        icon: 'fas fa-edit'
      };
    }
    contextMenuOpt.items['view'] = {
      name: 'View Details',
      icon: 'fas fa-search'
    };
    contextMenuOpt.items['view_minus'] = {
      name: 'View Minus Details',
      icon: 'fas fa-search-minus'
    };
    contextMenuOpt.items['view_plus'] = {
      name: 'View Plus Details',
      icon: 'fas fa-search-plus'
    };

    $.contextMenu(contextMenuOpt);
  }
</script>