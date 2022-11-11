<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($category_id)  $q .= '&category=' . $category_id;
if ($item_name)    $q .= '&item_name=' . $item_name;
if ($start_date)   $q .= '&start_date=' . $start_date;
if ($end_date)     $q .= '&end_date=' . $end_date;
if ($warehouse_id) $q .= '&warehouse=' . $warehouse_id;
//if ($xls)          $q .= '&xls=' . $xls;
?>
<script>
  $(document).ready(function() {
    let start_date = <?= ($start_date ? "'" . $start_date . "'" : 'null'); ?>;
    let end_date = <?= ($end_date ? "'" . $end_date . "'" : 'null'); ?>;
    let warehouse = <?= ($warehouse_id ? $warehouse_id : 'null'); ?>;

    oTable = $('#Table').dataTable({
      "aaSorting": [
        [1, "asc"]
      ],
      "aLengthMenu": [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "<?= lang('all') ?>"]
      ],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true,
      'bServerSide': true,
      'sAjaxSource': '<?= admin_url('reports/getInventoryBalance?' . $q); ?>',
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
        nRow.id = aData[0]; // Product ID.
        nRow.classList.add('item_history');

        if (start_date) nRow.dataset.startDate = start_date;
        if (end_date) nRow.dataset.endDate = end_date;
        if (warehouse) nRow.dataset.warehouse = warehouse;

        return nRow;
      },
      "aoColumns": [{
          "bVisible": false
        }, null, null, null, {
          "mRender": formatStock
        }, {
          "mRender": formatStock
        },
        {
          "mRender": formatStock
        }, {
          "mRender": formatStock
        }, {
          "mRender": currencyFormat
        },
        {
          "mRender": currencyFormat
        }
      ],
      "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
        let stock_value = 0;
        total_stock_value = 0;

        for (let i = 0; i < aaData.length; i++) {
          stock_value = aaData[aiDisplay[i]][9];

          if (stock_value !== null) {
            total_stock_value += parseFloat(stock_value);
          } else {
            console.log(`Product: ${aaData[aiDisplay[i]][1]}. Stock: ${stock_value}`);
          }
        }

        let nCells = nRow.getElementsByTagName('th');
        nCells[8].innerHTML = currencyFormat(parseFloat(total_stock_value));
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

    $('#form_filter').hide();
  });
</script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#category').change(function() {
      var v = $(this).val();
      if (v) {
        $.ajax({
          type: "GET",
          url: "<?= admin_url('products/getSubCategories') ?>/" + v,
          dataType: "json",
          success: function(scdata) {
            if (scdata != null) {
              $("#subcategory").select2("destroy").empty().attr("placeholder", "<?= lang('select_subcategory') ?>").select2({
                allowClear: true,
                placeholder: "<?= lang('select_category_to_load') ?>",
                data: scdata
              });
            } else {
              $("#subcategory").select2("destroy").empty().attr("placeholder", "<?= lang('no_subcategory') ?>").select2({
                allowClear: true,
                placeholder: "<?= lang('no_subcategory') ?>",
                data: [{
                  id: '',
                  text: '<?= lang('no_subcategory') ?>'
                }]
              });
            }
          },
          error: function() {
            bootbox.alert('<?= lang('ajax_error') ?>');
          }
        });
      } else {
        $("#subcategory").select2("destroy").empty().attr("placeholder", "<?= lang('select_category_to_load') ?>").select2({
          allowClear: true,
          placeholder: "<?= lang('select_category_to_load') ?>",
          data: [{
            id: '',
            text: '<?= lang('select_category_to_load') ?>'
          }]
        });
      }
    });
    <?php if (isset($_POST['category']) && !empty($_POST['category'])) { ?>
      $.ajax({
        type: "get",
        async: false,
        url: "<?= admin_url('products/getSubCategories') ?>/" + <?= $_POST['category'] ?>,
        dataType: "json",
        success: function(scdata) {
          if (scdata != null) {
            $("#subcategory").select2("destroy").empty().attr("placeholder", "<?= lang('select_subcategory') ?>").select2({
              allowClear: true,
              placeholder: "<?= lang('no_subcategory') ?>",
              data: scdata
            });
          }
        }
      });
    <?php
    } ?>
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-fw fa-box-full"></i><?= lang('inventory_balance'); ?>
      <?php
      if ($start_date) {
        echo 'From ' . $start_date . ' to ' . $end_date;
      } ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="icon fad fa-download tip" data-placement="left" title="Export Excel"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu">
            <li><a href="#" id="export_ivb_xls"><i class="fa fa-file-excel"></i> Item Details</a></li>
            <li><a href="#" id="export_ivb_wh_xls"><i class="fa fa-file-excel"></i> Warehouse Summary Details</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fa fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><strong><?= lang('warehouse'); ?></strong>: <?= ($warehouse_id ? $warehouse->name : 'All Warehouses'); ?></p>

        <div id="form_filter" class="closed well well-sm">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('product', 'suggest_product'); ?>
                <?php echo form_input('item_name', $item_name, 'class="form-control" id="item_name"'); ?>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('category', 'category') ?>
                <?php
                $cat[''] = lang('select') . ' ' . lang('category');
                if (!empty($categories)) {
                  foreach ($categories as $category) {
                    $cat[$category->id] = '(' . $category->code . ') ' . $category->name;
                  }
                }
                echo form_dropdown('category', $cat, $category_id, 'class="select2" id="category" style="width:100%"')
                ?>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('subcategory', 'subcategory') ?>
                <div class="controls" id="subcat_data">
                  <?= form_input('subcategory', (isset($_POST['subcategory']) ? $_POST['subcategory'] : ''), 'class="select2" id="subcategory" style="width:100%;"'); ?>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="warehouse"><?= lang('warehouse'); ?></label>
                <?php
                $wh = [];
                $wh[''] = lang('select') . ' ' . lang('warehouse');
                if (!empty($warehouses)) {
                  foreach ($warehouses as $warehouse) {
                    $wh[$warehouse->id] = $warehouse->name;
                  }
                }
                echo form_dropdown('warehouse', $wh, $warehouse_id, 'class="select2" id="warehouse" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('start_date', 'start_date'); ?>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $start_date ?>">
              </div>
            </div>

            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('end_date', 'end_date'); ?>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $end_date ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_button('filter_submit', 'Submit', 'class="btn btn-primary" id="filter_submit"'); ?>
                <a href="<?= admin_url('reports/inventory_balance'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="inventory_balance" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="table-responsive">
          <table id="Table" class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
            <thead>
              <tr class="active">
                <th></th>
                <th><?= lang('product_code'); ?></th>
                <th><?= lang('product_name'); ?></th>
                <th><?= lang('unit'); ?></th>
                <th><?= lang('beginning'); ?></th>
                <th><?= lang('increase'); ?></th>
                <th><?= lang('decrease'); ?></th>
                <th><?= lang('balance'); ?></th>
                <th><?= lang('average_cost'); ?></th>
                <th><?= lang('stock_value'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="9" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
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
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  $(document).ready(function() {
    $('#export_ivb_xls').click(function() {
      event.preventDefault();

      let q = 'xls=1';
      let category = $('#category').val();
      let item_name = $('#item_name').val();
      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();
      let warehouse = $('#warehouse').val();

      if (category) q += `&category=${category}`;
      if (item_name) q += `&item_name=${item_name}`;
      if (start_date) q += `&start_date=${start_date}`;
      if (end_date) q += `&end_date=${end_date}`;
      if (warehouse) q += `&warehouse=${warehouse}`;

      location.href = '<?= admin_url('reports/getInventoryBalanceReport?'); ?>' + q;
    });

    $('#export_ivb_wh_xls').click(function() {
      event.preventDefault();

      let q = 'xls=2';

      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();

      if (start_date) q += `&start_date=${start_date}`;
      if (end_date) q += `&end_date=${end_date}`;

      location.href = '<?= admin_url('reports/getInventoryBalanceReport?'); ?>' + q;
    });

    $('#filter_submit').on('click', function() {
      let q = '';
      let category = $('#category').val();
      let item_name = $('#item_name').val();
      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();
      let warehouse = $('#warehouse').val();

      if (category) q += `&category=${category}`;
      if (item_name) q += `&item_name=${item_name}`;
      if (start_date) q += `&start_date=${start_date}`;
      if (end_date) q += `&end_date=${end_date}`;
      if (warehouse) q += `&warehouse=${warehouse}`;

      location.href = '<?= admin_url('reports/inventory_balance?'); ?>' + q;
    });
  });
</script>