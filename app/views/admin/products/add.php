<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-plus"></i><?= lang('add_product'); ?></h2>
  </div>
  <div class="box-content">
    <?= admin_form_open_multipart('products/add', ['data-toggle' => 'validator']); ?>
    <div class="row">
      <!-- LEFT COLUMN -->
      <div class="col-sm-6">
        <div class="panel panel-primary">
          <div class="panel-heading">General</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_type', 'product_type'); ?>
                  <?php
                  $opt = ['standard' => 'Standard', 'combo' => 'Combo', 'service' => 'Service'];

                  echo form_dropdown('type', $opt, '', 'class="select2" id="type" style="width:100%;" required="required"')
                  ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_code', 'code'); ?>
                  <?= form_input('code', '', 'class="form-control" id="code" required="required"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_name', 'name'); ?>
                  <?= form_input('name', '', 'class="form-control" id="name" required="required"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_cost', 'cost'); ?>
                  <?= form_input('cost', '0', 'class="form-control currency" id="cost"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_price', 'price'); ?>
                  <?= form_input('price', '0', 'class="form-control currency" id="price" required="required"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('min_order_qty', 'min_order_qty'); ?>
                  <?= form_input('min_order_qty', '1', 'class="form-control" id="min_order_qty" required="required"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('available_warehouses', 'warehouses'); ?>
                  <?= form_input('warehouses', '', 'class="form-control tip" id="warehouses" title="-Durian = Exclude Durian. Durian = Durian only."'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('priority', 'priority'); ?>
                  <select id="priority" class="select2" name="priority" style="width:100%;">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('serial_number', 'sn'); ?>
                  <?= form_input('sn', '', 'class="form-control tip" id="sn" title="Serial Number"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('purchased_date', 'purchased'); ?>
                  <?= form_input('purchased_at', '', 'class="form-control tip date" id="purchased" title="Purchased Date"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('purchase_source', 'purchase_source'); ?>
                  <select id="purchase_source" class="select2" name="purchase_source" style="width:100%;">
                    <option value="import">Import</option>
                    <option value="local">Local</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary autocomplete">
          <div class="panel-heading">Autocomplete</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <input class="form-control" id="autocomplete" name="autocomplete" type="checkbox" value="1">
                  <?= lang('autocomplete', 'autocomplete'); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary active-status">
          <div class="panel-heading">Active Status</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <input class="form-control" id="active" name="active" type="checkbox" value="1">
                  <?= lang('active', 'active'); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary">
          <div class="panel-heading">Category</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_category', 'category'); ?>
                  <?php
                  $opt = [];
                  $categories = $this->site->getParentCategories();
                  if ($categories) {
                    $opt[''] = 'Select Product Category';
                    foreach ($categories as $cat) {
                      $opt[$cat->code] = $cat->name;
                    }
                  }

                  echo form_dropdown('category', $opt, '', 'class="select2" id="category" style="width:100%;" required="required"')
                  ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_subcategory', 'subcategory'); ?>
                  <?php
                  $opt = [];

                  echo form_dropdown('subcategory', $opt, '', 'class="select2" id="subcategory" style="width:100%;"')
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary internal-uses">
          <div class="panel-heading">Internal Use</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <?= lang('internal_use_type', 'iuse_type'); ?>
                <?php
                $opt = ['' => 'Select internal use type', 'consumable' => 'Consumable', 'report' => 'Combo Report', 'sparepart' => 'Sparepart'];
                echo form_dropdown('iuse_type', $opt, '', 'class="select2" id="iuse_type" style="width:100%;"');
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary combo-items">
          <div class="panel-heading">Combo Items</div>
          <div class="panel-body" style="overflow-x: auto;">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= form_dropdown('item', [], '', 'class="product-service-standard" id="item" data-placeholder="Select Items" style="width:100%;"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <table class="table table-bordered table-condensed table-hover table-striped" id="comboTable">
                  <thead>
                    <tr>
                      <th>Item (Code - Name)</th>
                      <th class="col-sm-3">Quantity</th>
                      <th><i class="fad fa-trash-alt"></i></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary mark-on">
          <div class="panel-heading">Mark-On Price</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('markon', 'markon'); ?>
                  <?= form_input('markon', '0', 'class="form-control" id="markon"'); ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('markon_price', 'markon_price'); ?>
                  <?= form_input('markon_price', '0', 'class="form-control currency" id="markon_price"'); ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary production">
          <div class="panel-heading">Production</div>
          <div class="panel-body" style="overflow-x: auto;">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <label for="min_prod_time">Min. Production Time (Hour)</label>
                  <input type="number" name="min_prod_time" class="form-control" id="min_prod_time" step="0.1">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <label for="prod_time_qty">Production Time per Qty (Hour)</label>
                  <input type="number" name="prod_time_qty" class="form-control" id="prod_time_qty" step="0.1">
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary supplier">
          <div class="panel-heading">Supplier</div>
          <div class="panel-body" style="overflow-x: auto;">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('supplier', 'supplier'); ?>
                  <div class="well well-sm">
                    <div class="input-group">
                      <?php
                      $opt = [];
                      echo form_dropdown('supplier', $opt, '', 'class="supplier" id="supplier" data-placeholder="Select Supplier" style="width:100%;"');
                      ?>
                      <div class="input-group-addon" style="padding:0 10px;">
                        <a href="#" class="tip" id="clearSupplier" title="Clear Supplier"><i class="fad fa-trash-alt"></i></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary unit">
          <div class="panel-heading">Unit</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('product_unit', 'unit'); ?>
                  <?php
                  $opt = [];
                  $units = $this->site->getAllProductUnits();
                  if ($units) {
                    $opt[''] = 'Select Product Unit';
                    foreach ($units as $unit) {
                      $opt[$unit->id] = $unit->name . ' (' . $unit->code . ')';
                    }
                  }

                  echo form_dropdown('unit', $opt, '', 'class="select2" id="unit" style="width:100%;" required="required"')
                  ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('sale_unit', 'sale_unit'); ?>
                  <?php
                  $opt = [];
                  echo form_dropdown('sale_unit', $opt, '', 'class="select2" id="sale_unit" style="width:100%;"')
                  ?>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('purchase_unit', 'purchase_unit'); ?>
                  <?php
                  $opt = [];
                  echo form_dropdown('purchase_unit', $opt, '', 'class="select2" id="purchase_unit" style="width:100%;"')
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary wh-safety-stock">
          <div class="panel-heading">Warehouses Safety Stock</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <div class="form-group">
                  <?= lang('safety_stock_ratio', 'safety_stock_ratio'); ?>
                  <input class="form-control" id="safety_stock_ratio" name="safety_stock_ratio" step="0.1" type="number">
                </div>
              </div>
            </div>
            <table class="table table-bordered table-condensed table-hover table-striped">
              <thead>
                <tr>
                  <th class="col-sm-6">Warehouse</th>
                  <th class="col-sm-6">Safety Stock</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $warehouses = $this->site->getAllWarehouses();

                if ($warehouses) {
                  foreach ($warehouses as $warehouse) { ?>
                    <tr>
                      <td><?= $warehouse->name; ?></td>
                      <td><input class="form-control text-center" name="safety_stock_<?= $warehouse->id; ?>" type="text" value="0"></td>
                    </tr>
                <?php
                  }
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- RIGHT COLUMN -->
      <div class="col-sm-6">
        <div class="panel panel-primary price-ranges">
          <div class="panel-heading">Price Ranges</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <table class="table table-bordered table-condensed table-hover table-striped">
                  <thead>
                    <tr>
                      <th class="col-sm-6">Price Range</th>
                      <th class="col-sm-6">Quantity</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Price Range 1</td>
                      <td><input class="form-control" placeholder="Min. Quantity" type="text" value="1" readonly="readonly"></td>
                    </tr>
                    <tr>
                      <td>Price Range 2</td>
                      <td><input class="form-control" name="price_ranges_value[]" placeholder="Min. Quantity" type="text" value=""></td>
                    </tr>
                    <tr>
                      <td>Price Range 3</td>
                      <td><input class="form-control" name="price_ranges_value[]" placeholder="Min. Quantity" type="text" value=""></td>
                    </tr>
                    <tr>
                      <td>Price Range 4</td>
                      <td><input class="form-control" name="price_ranges_value[]" placeholder="Min. Quantity" type="text" value=""></td>
                    </tr>
                    <tr>
                      <td>Price Range 5</td>
                      <td><input class="form-control" name="price_ranges_value[]" placeholder="Min. Quantity" type="text" value=""></td>
                    </tr>
                    <tr>
                      <td>Price Range 6</td>
                      <td><input class="form-control" name="price_ranges_value[]" placeholder="Min. Quantity" type="text" value=""></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary price-groups">
          <div class="panel-heading">Price Groups</div>
          <div class="panel-body">
            <div class="row">
              <div class="col-sm-12">
                <?php
                $price_groups = $this->site->getAllPriceGroups();
                if ($price_groups) {
                  foreach ($price_groups as $price_group) { ?>
                    <table class="table table-bordered table-condensed table-hover table-striped">
                      <thead>
                        <tr>
                          <th colspan="2"><?= $price_group->name; ?></th>
                        </tr>
                        <tr>
                          <th class="col-sm-6">Price Range</th>
                          <th class="col-sm-6">Price</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Price Range 1</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                        <tr>
                          <td>Price Range 2</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                        <tr>
                          <td>Price Range 3</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                        <tr>
                          <td>Price Range 4</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                        <tr>
                          <td>Price Range 5</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                        <tr>
                          <td>Price Range 6</td>
                          <td><input class="form-control currency" name="price_groups_<?= $price_group->id; ?>[]" type="text" value=""></td>
                        </tr>
                      </tbody>
                    </table>
                <?php
                  }
                } ?>
              </div>
            </div>
          </div>
        </div>
        <div class="panel panel-primary stock-opname">
          <div class="panel-heading">Stock Opname</div>
          <div class="panel-body" style="overflow-x: auto;">
            <?php
            $warehouses = $this->site->getAllWarehouses();
            if ($warehouses) {
              foreach ($warehouses as $warehouse) { ?>
                <table class="table table-bordered table-condensed table-hover table-striped">
                  <thead>
                    <tr>
                      <th colspan="2"><?= $warehouse->name; ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <div class="input-group">
                          <?php
                          $opt = [];
                          echo form_dropdown('pic_' . $warehouse->id, $opt, '', 'class="user" data-placeholder="Select PIC" style="width:100%;"');
                          ?>
                          <div class="input-group-addon" style="padding:0 10px;">
                            <a href="#" class="tip clear-pic" title="Clear PIC"><i class="fad fa-trash-alt"></i></a>
                          </div>
                        </div>
                      </td>
                      <td class="col-sm-4">
                        <div class="form-group">
                          <?= lang('sequence_cycle', 'cycle', 'class="tip" title="Urutan SO yang ke sekian yang akan ditampilkan"'); ?>
                          <input class="form-control" name="cycle_<?= $warehouse->id; ?>" min="1" type="number">
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
            <?php
              }
            }
            ?>
          </div>
        </div>
        <div class="panel panel-primary wh-stock">
          <div class="panel-heading">Warehouses Stocks</div>
          <div class="panel-body">
            <table class="table table-bordered table-condensed table-hover table-striped">
              <thead>
                <tr>
                  <th class="col-sm-6">Warehouse</th>
                  <th class="col-sm-6">Quantity</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $item_total = 0;
                $warehouses = $this->site->getAllWarehouses();
                if ($warehouses) {
                  foreach ($warehouses as $warehouse) { ?>
                    <tr>
                      <td><?= $warehouse->name; ?></td>
                      <?php
                      $warehouse_product = $this->site->getWarehouseProduct(NULL, $warehouse->id); // param2 = product->id
                      if ($warehouse_product) {
                        $item_total += $warehouse_product->quantity;
                      }
                      ?>
                      <td class="text-right"><?= ($warehouse_product ? $warehouse_product->quantity : 0); ?></td>
                    </tr>
                <?php
                  }
                }
                ?>
              </tbody>
              <tfoot>
                <tr>
                  <th>Total</th>
                  <th class="text-right"><?= $item_total; ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <button class="btn btn-primary" type="submit"><i class="fad fa-save"></i> Save</button>
          <button class="btn btn-danger" id="cancel" type="button"><i class="fad fa-undo"></i> Cancel</button>
        </div>
      </div>
    </div>
    <?= form_close(); ?>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('.combo-items').hide();
    $('.price-groups').hide();
    $('.price-ranges').hide();

    $('#item').on('select2:select', function() {
      let qty = $(this).val();
      getProductInfo({
        id: qty
      }).then(function(item) {
        if (item) {
          addComboItems(item);
        }
      });
      $(this).empty();
    });

    $('#cancel').click(function() {
      location.href = site.base_url + 'products';
    });

    $('#category').change(function() {
      $('#subcategory').empty();
      preSelectSubCategory('#subcategory', $(this).val());
    });

    $('.clear-pic').click(function() {
      $(this).parents('.input-group').find('.user').empty();
    });

    $(document).on('click', '.combo-delete', function() {
      let cbitems = JSON.parse(localStorage.getItem('cbitems'));
      let tr = $(this).parents('tr');
      let row_id = tr.data('row-id');

      if (cbitems) {
        if (cbitems.hasOwnProperty(row_id)) {
          delete cbitems[row_id];
          localStorage.setItem('cbitems', JSON.stringify(cbitems));
          loadComboItems();
        }
      }
    });

    $(document).on('change', '.combo-quantity', function() {
      let cbitems = JSON.parse(localStorage.getItem('cbitems'));
      let tr = $(this).parents('tr');
      let row_id = tr.data('row-id');

      if (cbitems) {
        if (cbitems.hasOwnProperty(row_id)) {
          cbitems[row_id].quantity = $(this).val();
          localStorage.setItem('cbitems', JSON.stringify(cbitems));
          loadComboItems();
        }
      }
    });

    $('#clearSupplier').click(function() {
      $('#supplier').empty();
    });

    $('#cost').change(function() {
      loadMarkonPrice();
    })

    $('#markon').change(function() {
      loadMarkonPrice();
    });

    $('#type').change(function() {
      let type = $(this).val();

      if (type == 'combo') {
        $('#unit').prop('required', false);
        $('.combo-items').slideDown();
        $('.internal-uses').slideUp();
        $('.mark-on').slideUp();
        $('.price-groups').slideDown();
        $('.price-ranges').slideDown();
        $('.stock-opname').slideUp();
        $('.supplier').slideUp();
        $('.unit').slideUp();
        $('.wh-safety-stock').slideUp();
        $('.wh-stock').slideUp();
      }
      if (type == 'standard') {
        $('#unit').prop('required', true);
        $('.combo-items').slideUp();
        $('.internal-uses').slideDown();
        $('.mark-on').slideDown();
        $('.price-groups').slideUp();
        $('.price-ranges').slideUp();
        $('.stock-opname').slideDown();
        $('.supplier').slideDown();
        $('.unit').slideDown();
        $('.wh-safety-stock').slideDown();
        $('.wh-stock').slideDown();
      }
      if (type == 'service') {
        $('#unit').prop('required', false);
        $('.combo-items').slideUp();
        $('.internal-uses').slideUp();
        $('.mark-on').slideUp();
        $('.price-groups').slideDown();
        $('.price-ranges').slideDown();
        $('.stock-opname').slideUp();
        $('.supplier').slideUp();
        $('.unit').slideUp();
        $('.wh-safety-stock').slideUp();
        $('.wh-stock').slideUp();
      }
    });

    $('#unit').change(function() {
      $('#sale_unit').empty();
      preSelectSubUnit('#sale_unit', $(this).val());
      $('#purchase_unit').empty();
      preSelectSubUnit('#purchase_unit', $(this).val());
    });

    loadComboItems();
  });

  function addComboItems(items) {
    let cbitems = JSON.parse(localStorage.getItem('cbitems')) ?? {};
    let rnd = randomString(8);

    if (Array.isArray(items)) {
      for (let item of items) {
        rnd = randomString(8);
        cbitems[rnd] = {
          row_id: rnd,
          code: item.code,
          name: item.name,
          quantity: 0
        };
      }
      localStorage.setItem('cbitems', JSON.stringify(cbitems));
    } else if (items instanceof Object) {
      cbitems[rnd] = {
        row_id: rnd,
        code: items.code,
        name: items.name,
        quantity: 0
      };
      localStorage.setItem('cbitems', JSON.stringify(cbitems));
    }

    loadComboItems();
  }

  function loadComboItems() {
    if (cbitems = localStorage.getItem('cbitems')) {
      let items = JSON.parse(cbitems);
      let tr_html = '';

      $.each(items, function() {
        let item = this;

        tr_html += `
          <tr data-row-id="${item.row_id}">
            <td>(${item.code}) ${item.name} </td>
            <td>
              <input type="hidden" name="combo_item_code[]" value="${item.code}">
              <input class="form-control text-center combo-quantity" name="combo_item_quantity[]" type="text" value="${item.quantity}">
            </td>
            <td><i class="fad fa-times combo-delete pointer"></i></td>
          </tr>
        `;
      });

      let comboTable = $('#comboTable tbody');

      comboTable.empty();
      comboTable.append(tr_html);
    }
  }

  function loadMarkonPrice() {
    $('#markon_price').val(getMarkonPrice(newParseInt($('#cost').val()), newParseInt($('#markon').val())));
  }
</script>