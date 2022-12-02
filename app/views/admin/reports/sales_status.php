<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
$biller     = getPOST('biller');
$customer   = getPOST('customer');
$categories = getPOST('categories'); // product categories
$group_by   = getPOST('group_by');
$product    = getPOST('product');
$reference  = getPOST('reference');
$warehouse  = getPOST('warehouse');
$users      = getPOST('users');
$start_date = getPOST('start_date');
$end_date   = getPOST('end_date');

if ($group_by) {
  $q .= '&group_by=' . $group_by;
}
if ($product) {
  $q .= '&product=' . $product;
}
if ($categories) {
  $pcs = $categories;
  foreach ($pcs as $pc) {
    $q .= '&categories[]=' . $pc;
  }
}
if ($reference) {
  $q .= '&reference=' . $reference;
}
if ($customer) {
  $q .= '&customer=' . $customer;
}
if ($biller) {
  $q .= '&biller=' . $biller;
}
if ($warehouse) {
  $q .= '&warehouse=' . $warehouse;
}
if ($users) {
  foreach ($users as $user) {
    $q .= '&users[]=' . $user;
  }
}
if ($start_date) {
  $q .= '&start_date=' . $start_date;
}
if ($end_date) {
  $q .= '&end_date=' . $end_date;
}
?>

<script>
  $(document).ready(function () {
    oTable = $('#SlRData').dataTable({
      "aaSorting": [[0, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('reports/getSalesStatus/?v=1' . $q) ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[16];
        nRow.className = (aData[10] > 0) ? "invoice_link2" : "invoice_link2 danger";
        <?php if ($group_by) {
          $group_index = [
            'biller' => 4,
            'category' => 8,
            'customer' => 5,
            'pic'      => 3,
            'product'  => 7,
            'sale'     => 1
          ]; ?>
          <?php if ($group_by == 'product') { ?>
          nRow.children[<?= $group_index[$group_by] - 1; ?>].style.backgroundColor = '#c0ffc0';
          <?php } ?>
          nRow.children[<?= $group_index[$group_by]; ?>].style.backgroundColor = '#c0ffc0';
        <?php } ?>
        return nRow;
      },
      "aoColumns": [{"mRender": fld}, null, {"mRender": upperCase}, null, null, null, null, null, null,
      {"mRender": formatQuantityRight}, {"mRender": currencyFormat},
      {"mRender": currencyFormat}, {"mRender": currencyFormat}, null, {"mRender": row_status},
      {"mRender": payment_status}],
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
        var gtotal = 0, paid = 0, balance = 0;
        for (var i = 0; i < aaData.length; i++) {
          gtotal  += parseFloat(aaData[aiDisplay[i]][10]);
          paid    += parseFloat(aaData[aiDisplay[i]][11]);
          balance += parseFloat(aaData[aiDisplay[i]][12]);
        }
        var nCells = nRow.getElementsByTagName('th');
        nCells[10].innerHTML = currencyFormat(parseFloat(gtotal));
        nCells[11].innerHTML = currencyFormat(parseFloat(paid));
        nCells[12].innerHTML = currencyFormat(parseFloat(balance));
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
  });
</script>
<script type="text/javascript">
  $(document).ready(function () {
    <?php if (getPOST('customer')) {
  ?>
    $('#customer').val(<?= getPOST('customer') ?>).select2({
      minimumInputLength: 1,
      data: [],
      initSelection: function (element, callback) {
        $.ajax({
          type: "get", async: false,
          url: site.base_url + "customers/suggestions/" + $(element).val(),
          dataType: "json",
          success: function (data) {
            callback(data.results[0]);
          }
        });
      },
      ajax: {
        url: site.base_url + "customers/suggestions",
        dataType: 'json',
        delay: 1000,
        data: function (term, page) {
          return {
            term: term,
            limit: 10
          };
        },
        results: function (data, page) {
          if (data.results != null) {
            return {results: data.results};
          } else {
            return {results: [{id: '', text: 'No Match Found'}]};
          }
        }
      }
    });

    $('#customer').val(<?= getPOST('customer') ?>);
    <?php
} ?>
    $('.toggle_down').click(function () {
      $("#form").slideDown();
      return false;
    });
    $('.toggle_up').click(function () {
      $("#form").slideUp();
      return false;
    });
  });
</script>


<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa fa-fw fa-chart-line"></i><?= lang('sales_status'); ?>
    <?php if ($group_by) { ?>
      <?= lang('group_by') . ' (' . lang($group_by) . ') '; ?>
    <?php } ?>
    <?php if ($start_date) { ?>
      <?= lang('from') . ' ' . $start_date . ' to ' . $end_date; ?>
    <?php } ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" id="xls" class="tip" title="<?= lang('download_xls') ?>">
            <i class="icon fa fa-file-excel"></i>
          </a>
        </li>
        <li class="dropdown">
          <a href="#" id="filter" class="tip" title="Filter"><i class="icon fa fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <div id="form_filter" class="closed well well-sm">
          <?php echo admin_form_open('reports/sales_status'); ?>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('group_by', 'group_by'); ?>
                <?php
                  $gb = [
                    'biller'    => 'Biller',
                    'customer'  => 'Customer',
                    'pic'       => 'PIC',
                    'product'   => 'Product',
                    'category'  => 'Product Category',
                    'sale'      => 'Sale'
                  ];
                ?>
                <?= form_dropdown('group_by', $gb, ($_POST['group_by'] ?? 'sale'), 'class="select2" style="width:100%;"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('product', 'suggest_product'); ?>
                <?php echo form_input('sproduct', (isset($_POST['sproduct']) ? $_POST['sproduct'] : ''), 'class="form-control" id="suggest_product"'); ?>
                <input type="hidden" name="product" value="<?= isset($_POST['product']) ? $_POST['product'] : '' ?>" id="report_product_id"/>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('product_category', 'product_category'); ?>
                <?php
                $pc = [];
                foreach ($product_categories as $product_category) {
                  $pc[$product_category->id] = $product_category->code;
                }
                echo form_multiselect('categories[]', $pc, (isset($_POST['categories']) ? $_POST['categories'] : ''), 'class="select2" id="categories" data-placeholder="Select Product Category" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="reference"><?= lang('reference'); ?></label>
                <?php echo form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : ''), 'class="form-control tip" id="reference"'); ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="user"><?= lang('created_by'); ?></label>
                <?php
                $users = $this->site->getUsers();
                foreach ($users as $user) {
                  $us[$user->id] = $user->first_name . ' ' . $user->last_name;
                }
                echo form_multiselect('users[]', $us, (isset($_POST['users']) ? $_POST['users'] : ''), 'class="select2" id="users" placeholder="Select Created By" style="width:100%;"');
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="customer"><?= lang('customer'); ?></label>
                <?php echo form_dropdown('customer', '', (isset($_POST['customer']) ? $_POST['customer'] : ''), 'class="select2" id="customer" data-placeholder="Select Customer" style="width:100%;"'); ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="biller"><?= lang('biller'); ?></label>
                <?php
                $bl[''] = lang('select') . ' ' . lang('biller');
                foreach ($billers as $biller) {
                  $bl[$biller->id] = $biller->name;
                }
                echo form_dropdown('biller', $bl, (isset($_POST['biller']) ? $_POST['biller'] : ''), 'class="select2" id="biller" data-placeholder="Select Biller" style="width:100%;"');
                ?>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="warehouse"><?= lang('warehouse'); ?></label>
                <?php
                $wh[''] = lang('select') . ' ' . lang('warehouse');
                foreach ($warehouses as $warehouse) {
                  $wh[$warehouse->id] = $warehouse->name;
                }
                echo form_dropdown('warehouse', $wh, ($_POST['warehouse'] ?? ''), 'class="select2" id="warehouse" data-placeholder="Select Warehouse" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('start_date', 'start_date'); ?>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $_POST['start_date'] ?? '' ?>">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('end_date', 'end_date'); ?>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $_POST['end_date'] ?? '' ?>">
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="controls">
              <?php echo form_submit('submit_report', $this->lang->line('submit'), 'class="btn btn-primary"'); ?>
              <a href="<?= admin_url('reports/sales_status'); ?>" class="btn btn-danger">Reset</a>
            </div>
          </div>
          <?php echo form_close(); ?>

        </div>
        <div class="clearfix"></div>

        <div class="table-responsive">
          <table id="SlRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
            <thead>
              <tr>
                <th><?= lang('date'); ?></th>
                <th><?= lang('reference'); ?></th>
                <th><?= lang('pic_id'); ?></th>
                <th><?= lang('pic_name'); ?></th>
                <th><?= lang('biller'); ?></th>
                <th><?= lang('customer'); ?></th>
                <th><?= lang('product_code'); ?></th>
                <th><?= lang('product_name'); ?></th>
                <th><?= lang('product_category'); ?></th>
                <th><?= lang('quantity'); ?></th>
                <th><?= lang('grand_total'); ?></th>
                <th><?= lang('paid'); ?></th>
                <th><?= lang('balance'); ?></th>
                <th><?= lang('operator'); ?></th>
                <th><?= lang('status'); ?></th>
                <th><?= lang('payment_status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="16" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
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
<script type="text/javascript">
  $(document).ready(function () {
    $('#xls').click(function (event) {
      event.preventDefault();
      window.location.href = "<?=admin_url('reports/getSalesStatus/xls/?v=1' . $q)?>";
      return false;
    });
  });
</script>
