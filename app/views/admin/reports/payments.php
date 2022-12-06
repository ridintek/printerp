<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$v = '';
if ($gpayment_ref = getGET('payment_ref')) {
  $v .= '&payment_ref=' . $gpayment_ref;
}
if ($gnumber = getGET('number')) {
  $v .= '&number=' . $gnumber;
}
if ($gbanks = getGET('bank')) {
  foreach ($gbanks as $bank) {
    $v .= '&bank[]=' . $bank;
  }
}
if ($gpaid_by = getGET('paid_by')) {
  $v .= '&paid_by=' . $gpaid_by;
}
if ($gbillers = getGET('biller')) {
  foreach ($gbillers as $biller) {
    $v .= '&biller[]=' . $biller;
  }
}
if ($gusers = getGET('user')) {
  foreach ($gusers as $user) {
    $v .= '&user[]=' . $user;
  }
}
if ($startDate = getGET('start_date')) {
  $v .= '&start_date=' . $startDate;
}
if ($endDate = getGET('end_date')) {
  $v .= '&end_date=' . $endDate;
}
if ($startRefDate = getGET('start_ref_date')) {
  $v .= '&start_ref_date=' . $startRefDate;
}
if ($endRefDate = getGET('end_ref_date')) {
  $v .= '&end_ref_date=' . $endRefDate;
}
?>
<script>
  $(document).ready(function() {
    oTable = $('#PayRData').dataTable({
      "aaSorting": [
        [0, "desc"]
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': '<?= admin_url('reports/getPaymentsReport/?v=1' . $v) ?>',
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
          "mRender": fld
        }, {
          "mRender": fld
        }, null, {
          "mRender": upperCase
        }, null, null, null, null, null, null, {
          "mRender": notes
        },
        {
          "mRender": currencyFormat
        }, {
          "mRender": renderStatus
        }, {
          "bVisible": false
        }
      ],
      'fnRowCallback': function(nRow, aData, iDisplayIndex) {
        // nRow.id = aData[12];
        // nRow.className = "payment_link";
        return nRow;
      },
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        var total = 0;
        for (var i = 0; i < aaData.length; i++) {
          let amount = parseFloat(aaData[aiDisplay[i]][10]);
          let type = aaData[aiDisplay[i]][11];

          if (type == 'sent') {
            total -= amount;
          } else if (type == 'received') {
            total += amount;
          }
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[10].innerHTML = currencyFormat(parseFloat(total));
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

    $('#form_filter').hide();

    $('#dtfilter').datatableFilter();
  });
</script>
<script type="text/javascript">
  $(document).ready(function() {
    <?php if (getPOST('biller')) {
    ?>
      $('#rbiller').select2({
        allowClear: true
      });
    <?php
    } ?>
    <?php if (getPOST('supplier')) {
    ?>
      $('#rsupplier').val(<?= getPOST('supplier') ?>).select2({
        minimumInputLength: 1,
        allowClear: true,
        initSelection: function(element, callback) {
          $.ajax({
            type: "get",
            async: false,
            url: "<?= admin_url('suppliers/getSupplier') ?>/" + $(element).val(),
            dataType: "json",
            success: function(data) {
              callback(data[0]);
            }
          });
        },
        ajax: {
          url: site.base_url + "suppliers/suggestions",
          dataType: 'json',
          delay: 1000,
          data: function(term, page) {
            return {
              term: term,
              limit: 10
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
          }
        }
      });
      $('#rsupplier').val(<?= getPOST('supplier') ?>);
    <?php
    } ?>
    <?php if (getPOST('customer')) {
    ?>
      $('#rcustomer').val(<?= getPOST('customer') ?>).select2({
        minimumInputLength: 1,
        allowClear: true,
        initSelection: function(element, callback) {
          $.ajax({
            type: "get",
            async: false,
            url: "<?= admin_url('customers/getCustomer') ?>/" + $(element).val(),
            dataType: "json",
            success: function(data) {
              callback(data[0]);
            }
          });
        },
        ajax: {
          url: site.base_url + "customers/suggestions",
          dataType: 'json',
          delay: 1000,
          data: function(term, page) {
            return {
              term: term,
              limit: 10
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
          }
        }
      });
    <?php
    } ?>
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-money-bill"></i><?= lang('payments_report'); ?>
      <?php
      if (getPOST('start_date')) {
        echo 'From ' . getPOST('start_date') . ' to ' . getPOST('end_date');
      } ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" id="xls" class="tip" title="<?= lang('download_xls') ?>">
            <i class="icon fa fa-file-excel"></i>
          </a>
        </li>
        <li class="dropdown">
          <a href="#" id="image" class="tip" title="<?= lang('save_image') ?>">
            <i class="icon fa fa-file-image"></i>
          </a>
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

        <p class="introtext"><?= lang('customize_report'); ?></p>

        <div id="form_filter" class="closed well well-sm">
          <?php echo admin_form_open('reports/payments'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('payment_ref', 'payment_ref'); ?>
                <?php echo form_input('payment_ref', $gpayment_ref, 'class="form-control tip" id="payment_ref"'); ?>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('account_no', 'number'); ?>
                <?php echo form_input('number', $gnumber, 'class="form-control" id="number"'); ?>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="bank"><?= lang('bank'); ?></label>
                <?php
                $bk = [];
                $all_banks = $this->site->getAllBanks();

                foreach ($all_banks as $bank) {
                  $bk[$bank->id] = $bank->name . ($bank->type != 'Cash' ? ' (' . $bank->number . ')' : '');
                }
                echo form_multiselect('bank[]', $bk, $gbanks, 'class="select2" id="bank" data-placeholder="Select Bank" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="biller"><?= lang('biller'); ?></label>
                <?php
                $billers = $this->site->getAllBillers();

                foreach ($billers as $biller) {
                  $bl[$biller->id] = $biller->name;
                }
                echo form_multiselect('biller[]', $bl, $gbillers, 'class="select2" id="biller" data-placeholder="Select Biller" style="width:100%;"');
                ?>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="user"><?= lang('created_by'); ?></label>
                <?php
                $us = [];
                $users = $this->site->getAllUsers();

                foreach ($users as $user) {
                  $us[$user->id] = $user->first_name . ' ' . $user->last_name;
                }

                echo form_multiselect('user[]', '', $gusers, 'class="select2" id="user" data-placeholder="Select User" style="width:100%;"');
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('paid_by', 'paid_by'); ?>
                <select name="paid_by" id="paid_by" class="paid_by select2" style="width:100%;">
                  <?= $this->sma->paid_opts(NULL, NULL, TRUE); ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('start_date', 'start_date'); ?>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $startDate ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('end_date', 'end_date'); ?>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $endDate ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('start_reference_date', 'start_ref_date'); ?>
                <input type="date" id="start_ref_date" name="start_ref_date" class="form-control" value="<?= $startRefDate ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('end_reference_date', 'end_ref_date'); ?>
                <input type="date" id="end_ref_date" name="end_ref_date" class="form-control" value="<?= $endRefDate ?>">
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="controls">
              <a href="#" class="btn btn-primary" id="dofilter"><i class="fad fa-filter"></i> Filter</a>
              <a href="<?= admin_url('reports/payments'); ?>" class="btn btn-danger"><i class="fad fa-undo"></i> Reset</a>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="clearfix"></div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="payments" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive table-overflow">
          <table id="PayRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
            <thead>
              <tr>
                <th>Payment Date</th>
                <th>Reference Date</th>
                <th>Reference</th>
                <th>PIC ID</th>
                <th>PIC Name</th>
                <th>Biller</th>
                <th>Bank Name</th>
                <th>Account Holder</th>
                <th>Account No</th>
                <th>Paid By</th>
                <th>Note</th>
                <th>Amount</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="13" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
              </tr>
            </tbody>
            <tfoot class="dtFilter">
              <tr class="active">
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
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/html2canvas.min.js"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#dofilter').click(function() {
      let payment_ref = $('#payment_ref').val();
      let number = $('#number').val();
      let banks = $('#bank').val();
      let billers = $('#biller').val();
      let users = $('#user').val();
      let paid_by = $('#paid_by').val();
      let startDate = $('#start_date').val();
      let endDate = $('#end_date').val();
      let startRefDate = $('#start_ref_date').val();
      let endRefDate = $('#end_ref_date').val();
      let q = '';

      if (payment_ref) {
        q += '&payment_ref=' + payment_ref;
      }

      if (number) {
        q += '&number=' + number;
      }

      if (banks) {
        for (bank of banks) {
          q += '&bank[]=' + bank;
        }
      }

      if (billers) {
        for (biller of billers) {
          q += '&biller[]=' + biller;
        }
      }

      if (users) {
        for (user of users) {
          q += '&user[]=' + user;
        }
      }

      if (paid_by) {
        q += '&paid_by=' + paid_by;
      }

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      if (startRefDate) {
        q += '&start_ref_date=' + startRefDate;
      }

      if (endRefDate) {
        q += '&end_ref_date=' + endRefDate;
      }

      location.href = site.base_url + 'reports/payments?' + q;
    });
    $('#xls').click(function(event) {
      event.preventDefault();
      window.location.href = "<?= admin_url('reports/getPaymentsReport?xls=1' . $v) ?>";
      return false;
    });
    $('#image').click(function(event) {
      event.preventDefault();
      html2canvas($('.box'), {
        onrendered: function(canvas) {
          openImg(canvas.toDataURL());
        }
      });
      return false;
    });
    <?php
    if (isset($_POST['paid_by'])) { ?>
      $('#paid_by').val('<?= $_POST['paid_by']; ?>').trigger('change');
    <?php } ?>

  });
</script>