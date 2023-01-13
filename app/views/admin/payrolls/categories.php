<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "asc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('payrolls/getPayrollCategories') ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [
        {"bSortable": false, "mRender": checkbox}, null, null, {"mRender": renderStatus},
        {"bSortable": false}]
    });
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-layer-group"></i> Payroll Categories</h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu">
            <li>
              <a href="<?= admin_url('payrolls/categories/add'); ?>" data-toggle="modal" data-target="#myModal">
                <i class="fad fa-fw fa-plus-circle"></i> Add Payroll Category
              </a>
            </li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-check"></i> Action Something
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-trash"></i> Delete Something
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
  <script>
    $(document).ready(function() {
      $('#filter').click((e) => {
        e.preventDefault();
        if ($('#form_filter').hasClass('closed')) {
          $('#form_filter').removeClass('closed').addClass('opened').slideDown();
        } else if ($('#form_filter').hasClass('opened')) {
          $('#form_filter').removeClass('opened').addClass('closed').slideUp();
        }
      });

      $('#dtfilter').datatableFilter();
    });
  </script>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext">Your information text will be here</p>
        <!-- Filter Form -->
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-md-4">
              <label for="input1"> Input 1</label>
              <input class="form-control" id="input1">
            </div>
          </div>
        </div>
        <!-- Filter Box -->
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <!-- TODO: Change data-name as page name without space -->
              <input id="dtfilter" class="form-control dtfilter" data-name="changeAsYouWant" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <table id="Table" class="table table-bordered table-hover table-striped reports-table">
          <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkth" type="checkbox" name="check"/>
              </th>
              <th>Code</th>
              <th>Name</th>
              <th>Type</th>
              <th style="width:100px;"><?= lang('actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="5" class="dataTables_empty">
                <?= lang('loading_data_from_server') ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- /.box-content -->
</div>
<script>
  $(document).on('click', '.delete', function (e) {
    e.preventDefault();

    let id = $(this).data('category-id');
    let name = $(this).data('category-name');

    addConfirm({
      title: 'Delete Payroll Category',
      message: `Are you sure to delete Payroll Category '${name}?'`,
      onok: () => {
        let data = {};

        data[security.csrf_token_name] = security.csrf_hash;
        data['id'] = id;

        $.ajax({
          data: data,
          method: 'POST',
          success: function (data) {
            if (typeof data == 'object' && !data.error) {
              if (oTable) oTable.fnDraw();
              addAlert(data.msg, 'success');
            } else if (typeof data == 'object' && data.error) {
              addAlert(data.msg, 'danger');
            } else {
              addAlert('Unknown error', 'danger');
            }
          },
          url: site.base_url + 'payrolls/categories/delete'
        });
      }
    });
  });
</script>