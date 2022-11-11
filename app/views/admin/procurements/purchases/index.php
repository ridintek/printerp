<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($reference = $this->input->get('reference')) {
  $q .= '&reference=' . $reference;
}
if ($supplier_name = $this->input->get('supplier')) {
  $q .= '&supplier=' . $supplier_name;
}
if ($warehouse = $this->input->get('warehouse')) {
  foreach ($warehouse as $wh) {
    $q .= '&warehouse[]=' . $wh;
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
  $(document).ready(function() {
    oTable = $('#POData').dataTable({
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
      'sAjaxSource': '<?= admin_url('procurements/purchases/getPurchases?') . $q ?>',
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
        }, null, null, {
          "mRender": currencyFormat
        },
        {
          "mRender": renderStatus
        }, {
          "mRender": fld
        }, {
          "mRender": currencyFormat
        }, {
          "mRender": fld
        },
        {
          "bSortable": false,
          "mRender": attachmentPurchase
        }, {
          "mRender": payment_status
        },
        {
          "mRender": fld
        },
        {
          "mRender": currencyFormat
        }, {
          "mRender": currencyFormat
        },
        {
          "bSortable": false
        }
      ],
      'fnRowCallback': function(nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "purchase_link";
        return nRow;
      },
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        var po_value = 0,
          received_value = 0,
          paid = 0,
          balance = 0;
        for (var i = 0; i < aaData.length; i++) {
          po_value += parseFloat(aaData[aiDisplay[i]][4]);
          received_value += parseFloat(aaData[aiDisplay[i]][7]);
          paid += parseFloat(aaData[aiDisplay[i]][12]);
          balance += parseFloat(aaData[aiDisplay[i]][13]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[4].innerHTML = currencyFormat(po_value);
        nCells[7].innerHTML = currencyFormat(received_value);
        nCells[12].innerHTML = currencyFormat(paid);
        nCells[13].innerHTML = currencyFormat(balance);
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

    <?php if ($supp = $this->input->post('supplier')) { ?>
      $('#supplier_x').val(<?= $supp; ?>).select2({
        minimumInputLength: 1,
        initSelection: function(element, callback) {
          $.ajax({
            type: 'get',
            url: site.base_url + 'suppliers/getSupplier/' + $(element).val(),
            dataType: 'json',
            success: function(data) {
              console.log(data[0]);
              callback(data[0]);
            },
          });
        },
        ajax: {
          url: site.base_url + 'suppliers/suggestions',
          dataType: 'json',
          delay: 1000,
          data: function(term, page) {
            return {
              term: term,
              limit: 10,
            };
          },
          results: function(data, page) {
            if (data.results != null) {
              return {
                results: data.results
              };
            } else {
              return {
                results: [{
                  id: '',
                  text: 'No Match Found'
                }]
              };
            }
          },
        }
      });
    <?php } else { ?>
      $('#supplier_x').select2({
        minimumInputLength: 1,
        ajax: {
          url: site.base_url + 'suppliers/suggestions',
          dataType: 'json',
          delay: 1000,
          data: function(term, page) {
            return {
              term: term,
              limit: 10,
            };
          },
          results: function(data, page) {
            if (data.results != null) {
              return {
                results: data.results
              };
            } else {
              return {
                results: [{
                  id: '',
                  text: 'No Match Found'
                }]
              };
            }
          },
        }
      });
    <?php } ?>

    if (localStorage.getItem('poitems')) {
      localStorage.removeItem('poitems');
    }
    if (localStorage.getItem('poref')) {
      localStorage.removeItem('poref');
    }
    if (localStorage.getItem('powarehouse')) {
      localStorage.removeItem('powarehouse');
    }
    if (localStorage.getItem('ponote')) {
      localStorage.removeItem('ponote');
    }
    if (localStorage.getItem('posupplier')) {
      localStorage.removeItem('posupplier');
    }
    if (localStorage.getItem('pocurrency')) {
      localStorage.removeItem('pocurrency');
    }
    if (localStorage.getItem('podate')) {
      localStorage.removeItem('podate');
    }
    if (localStorage.getItem('postatus')) {
      localStorage.removeItem('postatus');
    }
    if (localStorage.getItem('popayment_term')) {
      localStorage.removeItem('popayment_term');
    }
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-fw fa-credit-card"></i><?= lang('purchases_list'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i></a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('procurements/purchases/add') ?>">
                <i class="fad fa-fw fa-plus-circle"></i> <?= lang('add_purchase') ?>
              </a>
            </li>
            <li>
              <a href="#" id="approve_send" data-action="approve_send">
                <i class="fad fa-fw fa-check"></i> <?= lang('approve_and_send') ?>
              </a>
            </li>
            <li>
              <a href="#" id="export_bni_format">
                <i class="fad fa-fw fa-file-excel"></i> Export BNI Format
              </a>
            </li>
            <li>
              <a href="#" id="export_excel">
                <i class="fad fa-fw fa-file-excel"></i> Export Excel
              </a>
            </li>
            <li>
              <a href="#" id="split_po">
                <i class="fad fa-fw fa-code-branch"></i> Split PO
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= lang('delete_purchases') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete_purchases'><?= lang('i_m_sure') ?></button> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
                <i class="fad fa-fw fa-trash"></i> <?= lang('delete_purchases') ?>
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fad fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><strong><?= lang('warehouse'); ?></strong>: <?= ($warehouse_id ? $warehouse->name : lang('all_warehouses')); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('reference'); ?></label>
                <input type="text" class="form-control" id="reference" name="reference" value="<?= ($reference ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('supplier'); ?></label>
                <input type="text" class="form-control" id="supplier_name" name="supplier" value="<?= ($supplier_name ?? '') ?>">
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('warehouse'); ?></label>
                <?php
                $warehouses = $this->site->getAllWarehouses();
                if ($warehouses) {
                  $whs = [];
                  foreach ($warehouses as $wh) {
                    $whs[$wh->id] = $wh->name;
                  }
                  echo form_multiselect('warehouse', $whs, ($warehouse ?? ''), 'class="select2" id="warehouse" data-placeholder="Select Warehouse" style="width:100%;"');
                }
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('purchase_status'); ?></label>
                <?php
                $stat = [
                  'approved' => lang('approved'),
                  'need_approval' => lang('need_approval'),
                  'ordered' => lang('ordered'),
                  'received' => lang('received'),
                  'received_partial' => lang('received_partial'),
                ];
                echo form_multiselect('status', $stat, ($status ?? ''), 'class="select2" id="status" data-placeholder="Select Purchase Status" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('payment_status'); ?></label>
                <?php
                $paystat = [
                  'approved' => lang('approved'),
                  'need_approval' => lang('need_approval'),
                  'paid' => lang('paid'),
                  'partial' => lang('partial'),
                  'pending' => lang('pending'),
                ];
                echo form_multiselect('payment_status[]', $paystat, ($payment_status ?? ''), 'class="select2" id="payment_status" data-placeholder="Select Payment Status" style="width:100%;"'); ?>
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
                <button type="button" id="btn_filter" class="btn btn-primary"><i class="fad fa-filter"></i> Filter</button>
                <a href="<?= admin_url('procurements/purchases'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="purchases" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <?php if ($Owner || $Admin || $GP['bulk_actions']) {
          echo admin_form_open('procurements/purchases/actions', 'id="action-form"');
        } ?>
        <div class="table-responsive">
          <table id="POData" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkth" type="checkbox" name="check" />
                </th>
                <th>PO Date</th>
                <th>PO Number</th>
                <th><?= lang('supplier'); ?></th>
                <th>PO Value</th>
                <th><?= lang('purchase_status'); ?></th>
                <th>Last Received Date</th>
                <th>Received Value</th>
                <th><?= lang('due_date'); ?></th>
                <th style="text-align: center;"><i class="fad fa-link"></i></th>
                <th><?= lang('payment_status'); ?></th>
                <th><?= lang('payment_date'); ?></th>
                <th><?= lang('paid'); ?></th>
                <th><?= lang('balance'); ?></th>
                <th style="width:100px;"><?= lang('actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="15" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
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
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fad fa-link"></i></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
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
    $('#btn_filter').click(function() {
      filterPurchase();
    });

    $('#delete_purchases').click(function() {
      let vals = $('[name="val[]"]');
      console.log(vals);
    });

    $('#export_excel').click(function() {
      filterPurchase(1);
    });

    function filterPurchase(xls = false) {
      let q = '';
      let reference = $('#reference').val();
      let supplier_name = $('#supplier_name').val();
      let status = $('#status').val();
      let warehouse = $('#warehouse').val();
      let payment_status = $('#payment_status').val();
      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();
      let start_payment_date = $('#start_payment_date').val();
      let end_payment_date = $('#end_payment_date').val();

      if (reference) q += '&reference=' + reference;
      if (supplier_name) q += '&supplier=' + supplier_name;
      if (warehouse) {
        $.each(warehouse, function(index, value) {
          q += '&warehouse[]=' + value;
        });
      }
      if (status) {
        $.each(status, function(index, value) {
          q += '&status[]=' + value;
        });
      }
      if (payment_status) {
        $.each(payment_status, function(index, value) {
          q += '&payment_status[]=' + value;
        });
      }
      if (start_date) q += '&start_date=' + start_date;
      if (end_date) q += '&end_date=' + end_date;
      if (start_payment_date) q += '&start_payment_date=' + start_payment_date;
      if (end_payment_date) q += '&end_payment_date=' + end_payment_date;

      if (xls) {
        location.href = site.base_url + 'procurements/purchases/getPurchases?xls=1' + q;
      } else {
        //console.log(q);
        location.href = site.base_url + 'procurements/purchases?' + q;
      }
    }

    $('#export_bni_format').click(function() {
      event.preventDefault();

      let data = 'form_action=export_payments';
      $('input[name="val[]"]').each(function() {
        if ($(this).is(':checked')) {
          data += `&val[]=${$(this).val()}`;
        }
      });

      $.ajax({
        method: 'GET',
        xhrFields: {
          responseType: 'blob'
        },
        success: function(data) {
          console.log(data);
          if (typeof data == 'object' && data.error) {
            addAlert(data.msg, 'danger');
            return false;
          }

          let a = document.createElement('a');
          let url = window.URL.createObjectURL(data);
          a.href = url;
          a.download = 'New_Payments_Format.xlsx';
          a.click();
          a.remove();

          window.URL.revokeObjectURL(url);
        },
        url: site.base_url + 'procurements/purchases/actions?' + data
      });
    });

    $('#split_po').click(function() {
      event.preventDefault();

      let data = '';

      $('input[name="val[]"]').each(function() {
        if ($(this).is(':checked')) {
          data += `&id[]=${$(this).val()}`;
        }
      });

      showModal(site.base_url + 'procurements/purchases/split?' + data, 'modal-lg');
    })
  });
</script>