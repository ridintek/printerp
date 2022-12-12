<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  localStorage.setItem('add_mode', true);
  localStorage.removeItem('edit_mode');
  localStorage.removeItem('received_mode');
  localStorage.removeItem('status_mode');

  if (localStorage.getItem('podiscount')) {
    localStorage.removeItem('podiscount');
  }
  if (localStorage.getItem('poref')) {
    localStorage.removeItem('poref');
  }
  if (localStorage.getItem('pobiller')) {
    localStorage.removeItem('pobiller');
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
  if (localStorage.getItem('podate')) {
    localStorage.removeItem('podate');
  }
  if (localStorage.getItem('postatus')) {
    localStorage.removeItem('postatus');
  }
  if (localStorage.getItem('popayment_term')) {
    localStorage.removeItem('popayment_term');
  }
  var count = 1,
    an = 1,
    po_edit = false,
    total = 0;

  $(document).ready(function() {
    <?php if (getGET('supplier')) {
    ?>
      if (!localStorage.getItem('poitems')) {
        localStorage.setItem('posupplier', <?= getGET('supplier'); ?>);
      }
    <?php
    } ?>

    $(document).on('change', '#podate', function(e) {
      localStorage.setItem('podate', $(this).val());
    });
    if (podate = localStorage.getItem('podate')) {
      $('#podate').val(podate);
    }
    ItemnTotals();
    $("#add_item").autocomplete({
      source: function(request, response) {
        $.ajax({
          type: 'get',
          url: '<?= admin_url('procurements/purchases/suggestions'); ?>',
          dataType: "json",
          data: {
            term: request.term,
            supplier: $("#posupplier").val(),
            warehouse: $('#powarehouse').val()
          },
          success: function(data) {
            $(this).removeClass('ui-autocomplete-loading');
            response(data);
          }
        });
      },
      minLength: 1,
      autoFocus: false,
      delay: 250,
      response: function(event, ui) {
        if ($(this).val().length >= 16 && ui.content[0].id == 0) {
          //audio_error.play();
          $(this).removeClass('ui-autocomplete-loading');
        } else if (ui.content.length == 1 && ui.content[0].id != 0) {
          //ui.item = ui.content[0];
          //$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
          //$(this).autocomplete('close');
          //$(this).removeClass('ui-autocomplete-loading');
        } else if (ui.content.length == 1 && ui.content[0].id == 0) {
          //audio_error.play();
          $(this).removeClass('ui-autocomplete-loading');
        }
      },
      select: function(event, ui) {
        event.preventDefault();
        if (ui.item.id !== 0) {
          //console.log('PRODUCT SELECTED: ', ui.item);
          var row = add_purchase_item(ui.item);
          if (row)
            $(this).val('');
        } else {
          //audio_error.play();
          bootbox.alert('<?= lang('no_match_found') ?>');
        }
      }
    });

    $(document).on('click', '#addItemManually', function(e) {
      if (!$('#mcode').val()) {
        $('#mError').text('<?= lang('product_code_is_required') ?>');
        $('#mError-con').show();
        return false;
      }
      if (!$('#mname').val()) {
        $('#mError').text('<?= lang('product_name_is_required') ?>');
        $('#mError-con').show();
        return false;
      }
      if (!$('#mcategory').val()) {
        $('#mError').text('<?= lang('product_category_is_required') ?>');
        $('#mError-con').show();
        return false;
      }
      if (!$('#munit').val()) {
        $('#mError').text('<?= lang('product_unit_is_required') ?>');
        $('#mError-con').show();
        return false;
      }
      if (!$('#mcost').val()) {
        $('#mError').text('<?= lang('product_cost_is_required') ?>');
        $('#mError-con').show();
        return false;
      }
      if (!$('#mprice').val()) {
        $('#mError').text('<?= lang('product_price_is_required') ?>');
        $('#mError-con').show();
        return false;
      }

      var msg, row = null,
        product = {
          type: 'standard',
          code: $('#mcode').val(),
          name: $('#mname').val(),
          category_id: $('#mcategory').val(),
          unit: $('#munit').val(),
          cost: $('#mcost').val(),
          price: $('#mprice').val()
        };

      $.ajax({
        type: "get",
        async: false,
        url: site.base_url + "products/addByAjax",
        data: {
          token: "<?= $csrf; ?>",
          product: product
        },
        dataType: "json",
        success: function(data) {
          if (data.msg == 'success') {
            row = add_purchase_item(data.result);
          } else {
            msg = data.msg;
          }
        }
      });
      if (row) {
        $('#mModal').modal('hide');
        //audio_success.play();
      } else {
        $('#mError').text(msg);
        $('#mError-con').show();
      }
      return false;

    });
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-cart-plus"></i><?= lang('add_purchase'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?php echo lang('enter_info'); ?></p>
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('procurements/purchases/add', $attrib); ?>

        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'podate'); ?>
                <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control input-tip datetimenow" id="podate" required="required"'); ?>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('biller', 'biller'); ?>
                <?php
                $bl[''] = '';

                $billers = $this->site->getAllBillers();

                foreach ($billers as $biller) {
                  if (XSession::get('biller_id')) {
                    if ($biller->id != XSession::get('biller_id')) continue;
                  }
                  $bl[$biller->id] = $biller->name;
                }

                echo form_dropdown('biller', $bl, $Settings->default_biller, 'class="select2" id="biller" data-placeholder="Select Biller" style="width:100%;" required="required"');
                ?>
              </div>
            </div>

            <?php if ($Owner || $Admin || !XSession::get('warehouse_id')) { ?>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('warehouse', 'powarehouse'); ?>
                  <?php
                  $wh[''] = '';
                  foreach ($warehouses as $warehouse) {
                    if ($Admin || $Owner || $GP['purchases-other_warehouse'] || $warehouse->id == $Settings->default_warehouse) {
                      $wh[$warehouse->id] = $warehouse->name;
                    }
                  }
                  echo form_dropdown('warehouse', $wh, $Settings->default_warehouse, 'id="powarehouse" class="select2" data-placeholder="' . lang('select') . ' ' . lang('warehouse') . '" required="required" style="width:100%;"'); ?>
                </div>
              </div>
            <?php } else {
              $warehouse_input = [
                'type'  => 'hidden',
                'name'  => 'warehouse',
                'id'    => 'powarehouse',
                'value' => XSession::get('warehouse_id'),
              ];
              echo form_input($warehouse_input);
            } ?>
            <div class="col-md-12">
              <div class="panel panel-primary">
                <div class="panel-heading"><?= lang('please_select_these_before_adding_product') ?></div>
                <div class="panel-body" style="padding: 5px;">
                  <div class="col-md-4">
                    <div class="form-group">
                      <?= lang('supplier', 'supplier'); ?>
                      <div class="input-group">
                        <select name="supplier" id="supplier" class="select2" style="width:100%;" data-placeholder="Select Supplier"></select>
                        <div class="input-group-addon no-print" style="padding: 2px 5px; border-left: 0;">
                          <a href="#" id="view-supplier">
                            <i class="fad fa-user" id="addIcon"></i>
                          </a>
                        </div>
                        <div class="input-group-addon no-print" style="padding: 2px 5px;">
                          <a href="<?= admin_url('suppliers/add'); ?>" id="add-supplier" class="external" data-toggle="modal" data-target="#myModal">
                            <i class="fad fa-plus-circle" id="addIcon"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <?= lang('category', 'category') ?>
                      <select name="category" id="category" class="select2" data-placeholder="Purchase Category" style="width:100%;">
                        <option value="0">General</option>
                        <?php foreach ($expenseCategories as $excat): ?>
                          <option value="<?= $excat->id ?>"><?= $excat->name ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              <div class="clearfix"></div>
            </div>

            <div class="col-md-12" id="sticker">
              <div class="well well-sm">
                <div class="form-group" style="margin-bottom:0;">
                  <div class="input-group wide-tip">
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <i class="fad fa-barcode addIcon"></i></a>
                    </div>
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . $this->lang->line('add_product_to_order') . '"'); ?>
                    <?php if ($Owner || $Admin || $GP['products-add']) { ?>
                      <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                        <a href="<?= admin_url('products/add') ?>" id="addManually1"><i class="fad fa-plus-circle addIcon" id="addIcon"></i></a>
                      </div>
                    <?php } ?>
                  </div>
                </div>
                <div class="clearfix"></div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('order_items'); ?></label>

                <div class="controls table-controls">
                  <table id="poTable" class="table items table-striped table-bordered table-condensed table-hover sortable_table">
                    <thead>
                      <tr>
                        <th class="col-md-4"><?= lang('product') . ' (' . lang('code') . ' - ' . lang('name') . ')'; ?></th>
                        <th class="col-md-2"><?= lang('spec'); ?></th>
                        <th class="col-md-1"><?= lang('cost'); ?></th>
                        <th class="col-md-1">UoM</th>
                        <th class="col-md-1"><?= lang('purchased_qty'); ?></th>
                        <th class="col-md-1"><?= lang('received_1'); ?></th>
                        <th class="col-md-1"><?= lang('received_2'); ?></th>
                        <th class="col-md-1"><?= lang('received_3'); ?></th>
                        <th class="col-md-1"><?= lang('received_total'); ?></th>
                        <th class="col-md-1"><?= lang('received_value'); ?></th>
                        <th class="col-md-1 receive_qty"><?= lang('rest_quantity'); ?></th>
                        <th><?= lang('min_order_qty'); ?></th>
                        <th class="col-md-1"><?= lang('quantity_alert'); ?></th>
                        <th><?= lang('current_stock'); ?></th>
                        <th style="width: 30px !important; text-align: center;">
                          <i class="fad fa-trash" style="opacity:0.5; filter:alpha(opacity=50);"></i>
                        </th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                  </table>
                </div>
              </div>
            </div>
            <div class="clearfix"></div>
            <input type="hidden" name="total_items" value="" id="total_items" required="required" />

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('status', 'postatus'); ?>
                <?php
                $post = ['need_approval' => lang('need_approval')];
                echo form_dropdown('status', $post, (isset($_POST['status']) ? $_POST['status'] : 'need_approval'), 'id="postatus" class="form-control input-tip select2" data-placeholder="' . $this->lang->line('select') . ' ' . $this->lang->line('status') . '" required="required" style="width:100%;" ');
                ?>
              </div>
            </div>
            <!--
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false"
                     data-show-preview="false" class="form-control file">
              </div>
            </div>
-->
            <div class="col-md-12">
              <div class="form-group">
                <input type="checkbox" class="checkbox" id="extras" value="" />
                <label for="extras" class="padding05"><?= lang('more_options') ?></label>
              </div>
              <div class="row" id="extras-con" style="display: none;">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('payment_term', 'popayment_term'); ?>
                    <?php echo form_input('payment_term', '', 'class="form-control tip" data-trigger="focus" data-placement="top" title="' . lang('payment_term_tip') . '" id="popayment_term"'); ?>
                  </div>
                </div>
              </div>
              <div class="clearfix"></div>
              <div class="form-group">
                <?= lang('note', 'ponote'); ?>
                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="ponote" style="margin-top: 10px; height: 100px;"'); ?>
              </div>

            </div>
            <div class="col-md-12">
              <div class="from-group"><?php echo form_submit('add_pruchase', $this->lang->line('submit'), 'id="add_pruchase" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;"'); ?>
                <button type="button" class="btn btn-danger" id="reset"><?= lang('reset') ?></button>
              </div>
            </div>
          </div>
        </div>
        <div id="bottom-total" class="well well-sm" style="margin-bottom: 0;">
          <table class="table table-bordered table-condensed totals" style="margin-bottom:0;">
            <tr class="warning">
              <td><?= lang('items') ?> <span class="totals_val pull-right" id="titems">0</span></td>
              <td><?= lang('total') ?> <span class="totals_val pull-right" id="total">0.00</span></td>
              <td><?= lang('grand_total') ?> <span class="totals_val pull-right" id="gtotal">0.00</span></td>
            </tr>
          </table>
        </div>

        <?php echo form_close(); ?>

      </div><!-- /col-lg-12 -->

    </div>
  </div>
</div>

<div class="modal" id="prModal" tabindex="-1" role="dialog" aria-labelledby="prModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fad fa-times"></i></span><span class="sr-only"><?= lang('close'); ?></span></button>
        <h4 class="modal-title" id="prModalLabel"></h4>
      </div>
      <div class="modal-body" id="pr_popover_content">
        <form class="form-horizontal" role="form">
          <div class="form-group">
            <label for="pquantity" class="col-sm-4 control-label"><?= lang('quantity') ?></label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="pquantity">
            </div>
          </div>
          <div class="form-group">
            <label for="punit" class="col-sm-4 control-label"><?= lang('product_unit') ?></label>
            <div class="col-sm-8">
              <div id="punits-div"></div>
            </div>
          </div>
          <div class="form-group">
            <label for="pcost" class="col-sm-4 control-label"><?= lang('unit_cost') ?></label>
            <div class="col-sm-8">
              <input type="text" class="form-control currency" id="pcost">
            </div>
          </div>
          <table class="table table-bordered table-striped">
            <tr>
              <th style="width:25%;"><?= lang('unit_cost'); ?></th>
              <th style="width:25%;"><span id="net_cost"></span></th>
            </tr>
          </table>
          <div class="panel panel-default">
            <div class="panel-heading"><?= lang('calculate_unit_cost'); ?></div>
            <div class="panel-body">

              <div class="form-group">
                <label for="pcost" class="col-sm-4 control-label"><?= lang('subtotal') ?></label>
                <div class="col-sm-8">
                  <div class="input-group">
                    <input type="text" class="form-control currency" id="psubtotal">
                    <div class="input-group-addon" style="padding: 2px 8px;">
                      <a href="#" id="calculate_unit_price" class="tip" title="<?= lang('calculate_unit_cost'); ?>">
                        <i class="fad fa-calculator"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" id="punit_cost" value="" />
          <input type="hidden" id="old_qty" value="" />
          <input type="hidden" id="old_cost" value="" />
          <input type="hidden" id="row_id" value="" />
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="editItem"><?= lang('submit') ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="mModal" tabindex="-1" role="dialog" aria-labelledby="mModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fad fa-times"></i></span><span class="sr-only"><?= lang('close'); ?></span></button>
        <h4 class="modal-title" id="mModalLabel"><?= lang('add_standard_product') ?></h4>
      </div>
      <div class="modal-body" id="pr_popover_content">
        <div class="alert alert-danger" id="mError-con" style="display: none;">
          <span id="mError"></span>
        </div>
        <div class="row">
          <div class="col-md-6 col-sm-6">
            <div class="form-group">
              <?= lang('product_code', 'mcode') ?> *
              <input type="text" class="form-control" id="mcode">
            </div>
            <div class="form-group">
              <?= lang('product_name', 'mname') ?> *
              <input type="text" class="form-control" id="mname">
            </div>
            <div class="form-group">
              <?= lang('category', 'mcategory') ?> *
              <?php
              $cat[''] = '';
              foreach ($categories as $category) {
                $cat[$category->id] = $category->name;
              }
              echo form_dropdown('category', $cat, '', 'class="form-control select2" id="mcategory" placeholder="' . lang('select') . ' ' . lang('category') . '" style="width:100%"')
              ?>
            </div>
            <div class="form-group">
              <?= lang('unit', 'munit') ?> *
              <input type="text" class="form-control" id="munit">
            </div>
          </div>
          <div class="col-md-6 col-sm-6">
            <div class="form-group">
              <?= lang('cost', 'mcost') ?> *
              <input type="text" class="form-control" id="mcost">
            </div>
            <div class="form-group">
              <?= lang('price', 'mprice') ?> *
              <input type="text" class="form-control" id="mprice">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="addItemManually"><?= lang('submit') ?></button>
      </div>
    </div>
  </div>
</div>