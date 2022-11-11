<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($ref = $this->input->get('reference')) {
  $q .= '&reference=' . $ref;
}
if ($accFrom = $this->input->get('acc_from')) {
  $q .= '&acc_from=' . $accFrom;
}
if ($accTo = $this->input->get('acc_to')) {
  $q .= '&acc_to=' . $accTo;
}
if ($createdBy = $this->input->get('created_by')) {
  $q .= '&created_by=' . $createdBy;
}
if ($startDate = $this->input->get('start_date')) {
  $q .= '&from_date=' . $startDate;
}
if ($endDate = $this->input->get('end_date')) {
  $q .= '&end_date=' . $endDate;
}
if ($biller_ids = $this->input->get('biller')) {
  $biller_name = '';

  foreach ($biller_ids as $id) {
    $biller = $this->site->getBillerByID($id);
    $q .= '&biller[]=' . $id;
    $biller_name .= $biller->name . ', ';
  }

  $biller_name = rtrim($biller_name, ' ,');
}
?>
<script>
  $(document).ready(function() {
    oTable = $('#TFData').dataTable({
      "aaSorting": [
        [1, "desc"],
        [2, "desc"]
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': '<?= admin_url('finances/mutations/getMutations?' . $q) ?>',
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
        }, null, null, null, null,
        {
          "mRender": currencyFormat
        },
        null, null, null, {
          "mRender": pay_status
        }, {
          "bSortable": false,
          "mRender": attachmentExpense
        },
        {
          "bSortable": false
        }
      ],
      "fnRowCallback": function(nRow, aData, iDisplayIndex) {
        var oSettings = oTable.fnSettings();
        nRow.id = aData[0];
        nRow.className = "mutation_link"; // mutation_link
        return nRow;
      },
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        var total = 0;
        for (var i = 0; i < aaData.length; i++) {
          total += parseFloat(aaData[aiDisplay[i]][6]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[6].innerHTML = currencyFormat(formatMoney(total));
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
    <h2 class="blue"><i class="fa-fw fa fa-box-usd"></i><?= lang('bank_mutations_list'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fa fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?= admin_url('finances/mutations/add') ?>" data-toggle="modal" data-target="#myModal">
                <i class="fa fa-plus-circle"></i> <?= lang('add_bank_mutation') ?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fa fa-file-excel"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?= $this->lang->line('delete_bank_mutations') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left">
                <i class="fa fa-trash"></i> <?= lang('delete_bank_mutations') ?>
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
        <p class="introtext"><strong><?= lang('biller'); ?></strong>: <?= (isset($biller_name) ? $biller_name : lang('all_billers')); ?></p>
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <?php echo admin_form_open('finances/mutations'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('ref_no'); ?></label>
                <input type="text" class="form-control" id="reference" name="reference" value="<?= $ref; ?>" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('biller'); ?></label>
                <?php
                $billers = $this->site->getAllBillers();
                $bils = [];

                if ($billers) {
                  foreach ($billers as $biller) {
                    $bils[$biller->id] = $biller->name;
                  }
                }

                echo form_multiselect('biller', $bils, $biller_ids, 'class="select2" id="biller" data-placeholder="Select Billers" style="width:100%;"');
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('created_by'); ?></label>
                <?php
                $crb[''] = 'Select User';
                echo form_dropdown('created_by', $crb, $createdBy, 'class="form-control user" data-placeholder="Select User" id="created_by" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('acc_from'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('acc_from');
                if (!empty($all_banks)) {
                  foreach ($all_banks as $bank) {
                    $bk[$bank->name] = $bank->name;
                  }
                }
                echo form_dropdown('acc_from', $bk, $accFrom, 'class="select2" id="acc_from" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label><?= lang('acc_to'); ?></label>
                <?php
                $all_banks = $this->finances_model->getAllBanks();
                $bk = [];
                $bk[''] = lang('select') . ' ' . lang('acc_to');
                if (!empty($all_banks)) {
                  foreach ($all_banks as $bank) {
                    $bk[$bank->name] = $bank->name;
                  }
                }
                echo form_dropdown('acc_to', $bk, $accTo, 'class="select2" id="acc_to" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate; ?>" />
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate; ?>" />
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <a href="#" id="dofilter" class="btn btn-primary">
                  <i class="fad fa-filter"></i> Filter
                </a>
                <a href="<?= admin_url('finances/mutations'); ?>" class="btn btn-danger">
                  <i class="fad fa-undo"></i> Reset
                </a>
              </div>
            </div>
          </div>
          <?php echo form_close(); ?>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="mutations" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="TFData" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th><?= lang('date'); ?></th>
                <th><?= lang('ref_no'); ?></th>
                <th><?= lang('account') . ' (' . lang('from') . ')'; ?></th>
                <th><?= lang('account') . ' (' . lang('to') . ')'; ?></th>
                <th><?= lang('note'); ?></th>
                <th><?= lang('amount'); ?></th>
                <th><?= lang('created_by'); ?></th>
                <th><?= lang('paid_by'); ?></th>
                <th><?= lang('biller'); ?></th>
                <th><?= lang('status'); ?></th>
                <th style="min-width:30px; width: 30px; text-align: center;"><i class="fa fa-chain"></i></th>
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
<script>
  $(document).ready(function() {
    let created_by = '<?= $createdBy; ?>';

    if (created_by) {
      preSelectUser('#created_by', created_by);
    }

    $('#dofilter').click(function() {
      let q = '';

      let ref = $('#reference').val();
      let biller = $('#biller').val();
      let createdBy = $('#created_by').val();
      let accFrom = $('#acc_from').val();
      let accTo = $('#acc_to').val();
      let startDate = $('#start_date').val();
      let endDate = $('#end_date').val();

      if (ref) {
        q += '&ref=' + ref;
      }

      if (biller) {
        for (bill of biller) {
          q += '&biller[]=' + bill;
        }
      }

      if (createdBy) {
        q += '&created_by=' + createdBy;
      }

      if (accFrom) {
        q += '&acc_from=' + accFrom;
      }

      if (accTo) {
        q += '&acc_to=' + accTo;
      }

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'finances/mutations?' + q;
    });
  });
</script>