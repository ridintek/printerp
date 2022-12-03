<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

if ($startDate = getGET('start_date')) {
  $q .= '&start_date=' . $startDate;
}
if ($endDate = getGET('end_date')) {
  $q .= '&end_date=' . $endDate;
}
?>
<script>
  $(document).ready(function() {
    oTable = $('#Table').dataTable({
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
      'sAjaxSource': '<?= admin_url('sales/getSalesTB?' . $q); ?>',
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
        // nRow.className = "invoice_link " + aData[11];
        return nRow;
      },
      "aoColumns": [{
        "bSortable": false,
        "mRender": checkbox
      }, {
        "mRender": fld
      }, null, null, {
        "mRender": fld
      }, {
        "mRender": fld
      }, {
        "mRender": currencyFormat
      }, {
        "mRender": renderStatus
      }, null],
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        let total = 0;

        for (let i = 0; i < aaData.length; i++) {
          total += parseFloat(aaData[aiDisplay[i]][6]);
        }

        let nCells = nRow.getElementsByTagName('th');
        nCells[6].innerHTML = currencyFormat(parseFloat(total));
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
    <h2 class="blue"><i class="fa fa-fw fa-exchange"></i>Sales TB</h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle tip" data-toggle="dropdown" title="Menu">
            <i class="icon fad fa-bars"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus">
            <li>
              <a href="#" id="processPayments">
                <i class="fa fa-fw fa-exchange"></i> Process Payments
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" id="deleteSalesTB">
                <i class="fa fa-fw fa-trash"></i> Delete Sales TB
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
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('start_date'); ?></label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= ($startDate ?? '') ?>" />
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label><?= lang('end_date'); ?></label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= ($endDate ?? '') ?>" />
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <a href="#" name="submit" class="btn btn-primary" id="doFilter"><i class="fad fa-filter"></i> Filter</a>
                <a href="#" class="btn btn-success" id="syncSalesTB"><i class="fad fa-sync"></i> Sync Sales TB</a>
                <a href="<?= current_url(); ?>" class="btn btn-danger"><i class="fad fa-undo"></i> Reset</a>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="api_keys" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive min-height-400">
          <table id="Table" class="table table-bordered table-condensed table-hover table-striped">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th><?= lang('last_sync_date'); ?></th>
                <th><?= lang('from_biller'); ?></th>
                <th><?= lang('to_warehouse'); ?></th>
                <th><?= lang('start_date'); ?></th>
                <th><?= lang('end_date'); ?></th>
                <th><?= lang('amount'); ?></th>
                <th><?= lang('status'); ?></th>
                <th><?= lang('created_by'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="9" class="dataTables_empty"><?= lang('loading_data'); ?></td>
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
    reloadContextMenu();

    $('#deleteSalesTB').click(function() {
      addConfirm({
        title: 'Delete Sales TB',
        message: 'Are you sure to delete selected Sales TB?',
        onok: () => {
          let data = {};
          data[security.csrf_token_name] = security.csrf_hash;
          data.val = [];

          $.each($('input[name="val[]"]'), function() {
            if (this.checked) data.val.push(this.value);
          });

          $.ajax({
            data: data,
            method: 'POST',
            success: function(data) {
              if (isObject(data) && ! data.error) {
                if (oTable) oTable.fnDraw(false);
                addAlert(data.msg, 'success');
              } else if (isObject(data) && data.error) {
                addAlert(data.msg, 'danger');
              } else {
                addAlert('Unknown error.', 'danger');
              }
            },
            url: site.base_url + 'sales/deleteSalesTB'
          });
        }
      });
    });

    $('#doFilter').click(function(e) {
      e.preventDefault();

      let end_date       = $('#end_date').val();
      let start_date     = $('#start_date').val();

      let q = '';

      if (end_date)       q += '&end_date=' + end_date;
      if (start_date)     q += '&start_date=' + start_date;

      location.href = site.base_url + 'sales/tb?' + q;
    });

    $('#processPayments').click(function(e) {
      addConfirm({
        title: 'Process Sales TB',
        message: 'Are you sure to process selected Sales TB?',
        onok: () => {
          let data = {};
          data[security.csrf_token_name] = security.csrf_hash;
          data.val = [];

          $.each($('input[name="val[]"]'), function() {
            if (this.checked) data.val.push(this.value);
          });

          $.ajax({
            data: data,
            method: 'POST',
            success: function(data) {
              if (isObject(data) && ! data.error) {
                if (oTable) oTable.fnDraw(false);
                addAlert(data.msg, 'success');
              } else if (isObject(data) && data.error) {
                addAlert(data.msg, 'danger');
              } else {
                addAlert('Unknown error.', 'danger');
              }
            },
            url: site.base_url + 'sales/processSalesTB'
          });
        }
      });
    });

    $('#syncSalesTB').click(function(e) {
      e.preventDefault();

      let end_date       = $('#end_date').val();
      let start_date     = $('#start_date').val();

      let q = '';

      if (end_date)       q += '&end_date=' + end_date;
      if (start_date)     q += '&start_date=' + start_date;

      $.ajax({
        method: 'GET',
        success: function() {
          if (oTable) oTable.fnDraw(false);
        },
        url: site.base_url + 'sales/syncSalesTB?' + q
      })
    });
  });

  function reloadContextMenu() {
    let privilege = '<?= $Owner ?? $Admin; ?>';
    let contextMenuOpt = {
      selector: '.apikey_link',
      callback: function(key, opt) {
        let apikey_id = opt.$trigger.data('id');
        let name = opt.$trigger.data('name');

        if (key == 'delete') {
          alertify.dialog('confirm').set({
            message: `Are you sure to delete API Key <b>${name}</b>?`,
            onok: function() {
              let data = {
                id: apikey_id
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
                url: site.base_url + 'developers/api_keys/delete'
              });
            },
            title: 'Delete API Key',
            transition: 'zoom'
          }).show();
        }
        if (key == 'edit') {
          location.href = site.base_url + 'developers/api_keys/edit/' + opname_id;
        }
        if (key == 'view') {
          showModal(site.base_url + 'developers/api_keys/view/' + opname_id, 'modal-lg no-modal-header');
        }
      }
    };

    contextMenuOpt.items = {};
    if (privilege == 1 || privilege == 2) {
      contextMenuOpt.items['delete'] = {
        name: 'Delete API Key',
        icon: 'fas fa-trash-alt'
      };
      contextMenuOpt.items['edit'] = {
        name: 'Edit API Key',
        icon: 'fas fa-edit'
      };
    }
    contextMenuOpt.items['view'] = {
      name: 'View Details',
      icon: 'fas fa-search'
    };

    $.contextMenu(contextMenuOpt);
  }
</script>