<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  var count = 1, an = 1, total = 0;
  //var audio_success = new Audio('<?=$assets?>sounds/sound2.mp3');
  //var audio_error = new Audio('<?=$assets?>sounds/sound3.mp3');
  $(document).ready(function () {
    window.noResetItems = false;

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
      if (localStorage.getItem('paid_by_1')) {
        localStorage.removeItem('paid_by_1');
      }
      if (localStorage.getItem('pcc_holder_1')) {
        localStorage.removeItem('pcc_holder_1');
      }
      if (localStorage.getItem('pcc_type_1')) {
        localStorage.removeItem('pcc_type_1');
      }
      if (localStorage.getItem('pcc_month_1')) {
        localStorage.removeItem('pcc_month_1');
      }
      if (localStorage.getItem('pcc_year_1')) {
        localStorage.removeItem('pcc_year_1');
      }
      if (localStorage.getItem('pcc_no_1')) {
        localStorage.removeItem('pcc_no_1');
      }
      if (localStorage.getItem('cheque_no_1')) {
        localStorage.removeItem('cheque_no_1');
      }
      if (localStorage.getItem('payment_note_1')) {
        localStorage.removeItem('payment_note_1');
      }
      if (localStorage.getItem('slpayment_term')) {
        localStorage.removeItem('slpayment_term');
      }
      localStorage.removeItem('remove_slls');
    }
    <?php if (getGET('customer')) { ?>
            if ( ! localStorage.getItem('slitems')) {
              localStorage.setItem('slcustomer', <?=getGET('customer'); ?>);
            }
    <?php } ?>

    if (extend_time = localStorage.getItem('extend_time')) { // If customer has extended time.
      customer_id = localStorage.getItem('slcustomer');
      extend_time = parseInt(extend_time);
      term = `term=TWP10M&warehouse_id=${warehouse_id}&customer_id=${customer_id}`;
      $.ajax({
        method: 'GET',
        success: function (data) {
          data[0].row.sqty = extend_time;
          add_invoice_item(data[0]);
          localStorage.removeItem('extend_time');
        },
        url: site.base_url + 'sales/suggestions?' + term
      });
    }

    if (edit_design = JSON.parse(localStorage.getItem('edit_design'))) { // If customer has edit design
      if (edit_design) {
        customer_id = localStorage.getItem('slcustomer');
        edit_design = JSON.parse(edit_design);
        term = `term=JSED20&warehouse_id=${warehouse_id}&customer_id=${customer_id}`;
        $.ajax({
          method: 'GET',
          success: function (data) {
            data[0].row.sqty = 1;
            add_invoice_item(data[0]);
            localStorage.removeItem('edit_design');
          },
          url: site.base_url + 'sales/suggestions?' + term
        });
      }
    }

    if (!localStorage.getItem('sldate')) {
      $("#sldate").datetimepicker({
        format: site.dateFormats.js_ldate,
        fontAwesome: true,
        language: 'sma',
        weekStart: 1,
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        minView: 2
      }).datetimepicker('update', new Date());
    }
    $(document).on('change', '#sldate', function (e) {
      localStorage.setItem('sldate', $(this).val());
    });
    if (sldate = localStorage.getItem('sldate')) {
      $('#sldate').val(sldate);
    }
    if (!localStorage.getItem('slref')) {
      localStorage.setItem('slref', '<?=$slnumber?>');
    }
    ItemnTotals();
    $("#add_item").autocomplete({
      source: function (request, response) {
        if (!$('#slcustomer').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('<?=lang('please_select_customer_warehouse');?>');
          return false;
        }
        if (!$('#slwarehouse').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('<?=lang('please_select_customer_warehouse');?>');
          return false;
        }
        $.ajax({
          type: 'get',
          url: '<?= admin_url('sales/suggestions'); ?>',
          dataType: "json",
          data: {
            term: request.term,
            use_standard: ($('#use_standard').is(':checked') ? 1 : 0),
            warehouse_id: $("#slwarehouse").val(),
            customer_id:  $("#slcustomer").val()
          },
          success: function (data) {
            response(data);
            $('#add_item').removeClass('ui-autocomplete-loading');
          }
        });
      },
      minLength: 1,
      autoFocus: false,
      delay: 1000,
      response: function (event, ui) {
        console.log(ui);
        if ($(this).val().length >= 16 && ui.content[0].id == 0) {
          bootbox.alert(ui.content[0].label, function () {
            //$('#add_item').focus();
          });
          $(this).removeClass('ui-autocomplete-loading');
          $(this).val('');
        } else if (ui.content.length == 1 && ui.content[0].id == 0) {
          console.log('NO STOCK');
          $(this).removeClass('ui-autocomplete-loading');
        }
      },
      select: function (event, ui) {
        event.preventDefault();
        //if (ui.item.id !== 0 && ui.item.row.quantity > 0) {
        if (ui.item.id !== 0) {
          var row = add_invoice_item(ui.item);
          if (row)
            $(this).val(''); // Empty product select.
        } else if (ui.item.id !== 0 && ui.item.row.quantity <= 0) {
          bootbox.alert('<?= lang('stock_empty'); ?>');
        } else {
          bootbox.alert('<?= lang('no_match_found') ?>');
        }
      }
    });
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-plus-circle"></i><?= lang('add_sale'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <?php $attrib = ['data-toggle' => 'validator', 'id' => 'form_add_sale', 'role' => 'form'];
        echo admin_form_open_multipart('sales/add', $attrib); ?>
        <input type="hidden" name="sale_options" value="<?= $sale_options; ?>">
        <input type="hidden" name="draft_type" value="0">
        <input type="hidden" name="uri_callback" value="<?= ($_SERVER['HTTP_REFERER'] ?? 'admin/sales') ?>">
        <input type="hidden" name="total_items" value="" id="total_items" required="required"/>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <?= lang('date', 'sldate'); ?>
              <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control input-tip" id="sldate" required="required"'); ?>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <?= lang('no_po', 'no_po'); ?>
              <?php echo form_input('no_po', (isset($_POST['no_po']) ? $_POST['no_po'] : ''), 'class="form-control input-tip" id="no_po" title="Purchase Order Number (Optional)"'); ?>
            </div>
          </div>
          <?php if ($Owner || $Admin || ! XSession::get('biller_id')) { ?>
          <div class="col-md-4">
            <div class="form-group">
              <?= lang('biller', 'slbiller'); ?>
              <?php
              $bl[''] = '';
              $billers = $this->site->getAllBillers();

              if ($billers) {
                foreach ($billers as $biller) {
                  $bl[$biller->id] = $biller->name;
                }
              }
              echo form_dropdown('biller', $bl, (XSession::get('biller_id') ?? $Settings->default_biller), 'id="slbiller" data-placeholder="' . lang('select') . ' ' . lang('biller') . '" required="required" class="select2" style="width:100%;"'); ?>
            </div>
          </div>
          <?php } else { ?>
          <input type="hidden" id="slbiller" name="biller" value="<?= XSession::get('biller_id'); ?>">
          <?php } ?>
        </div><!-- /.row -->
        <div class="row">
          <?php if ($Owner || $Admin) { ?>
          <div class="col-md-4">
            <div class="form-group">
              <?= lang('created_by', 'slpic'); ?>
              <?php
              $users = $this->site->getUsers();
              if ( ! empty($users)) {
                $usr = [];

                foreach ($users as $user) {
                  $group = $this->site->getUserGroup($user->id);
                  //if ($group->name != 'cs' && $group->name != 'tl') continue;
                  $usr[$user->id] = $user->fullname;
                  $blr[$user->id] = $user->biller_id;
                }
              }
              echo form_dropdown('created_by', $usr, XSession::get('user_id'), 'id="slpic" data-placeholder="' . lang('select') . ' ' . lang('pic_name') . '" required="required" class="select2" style="width:100%;"'); ?>
            </div>
          </div>
          <?php } else {
                  $blr[XSession::get('user_id')] = XSession::get('biller_id'); ?>
          <input type="hidden" id="slpic" name="created_by" value="<?= XSession::get('user_id'); ?>">
          <?php } ?>
          <div class="col-md-4">
            <div class="form-group">
              <?= lang('cashier_by', 'cashier'); ?>
              <?php
              $userData = [];

              if ($billerId = XSession::get('biller_id')) {
                $userData['biller_id'] = $billerId;
              }

              $users = $this->site->getUsers($userData);

              if ( ! empty($users)) {
                $usr = [];

                foreach ($users as $user) {
                  $usr[$user->id] = $user->fullname;
                }
              }
              echo form_dropdown('cashier_by', $usr, XSession::get('user_id'), 'id="cashier" data-placeholder="Select Cashier" required="required" class="select2" style="width:100%;"'); ?>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group">
              <input id="use_standard" type="checkbox" value="1">
              <label for="use_standard">
                 Include <em>standard</em> item
              </label>
            </div>
            <div class="form-group">
              <input id="approved" name="approved" type="checkbox" value="1">
              <label for="approved">
                 Approved Sale
              </label>
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="col-md-12">
            <div class="panel panel-warning">
              <div class="panel-heading"><?= lang('please_select_these_before_adding_product') ?></div>
              <div class="panel-body" style="padding: 5px;">
                <div class="col-md-6">
                  <div class="form-group">
                    <?= lang('customer', 'slcustomer'); ?>
                    <div class="input-group">
                      <?= form_dropdown('customer', [], '', 'id="slcustomer" class="select2" data-placeholder="Select Customer" style="width:100%;"'); ?>
                      <div class="input-group-addon no-print" style="padding: 2px 8px; border-left: 0;">
                        <a href="#" id="edit-email-customer" title="Edit Email Customer">
                          <i class="fa fa-fw fa-edit" id="addIcon" style="font-size: 1.2em;"></i>
                        </a>
                      </div>
                      <div class="input-group-addon no-print" style="padding: 2px 7px; border-left: 0;">
                        <a href="#" id="view-customer">
                          <i class="fa fa-fw fa-eye" id="addIcon" style="font-size: 1.2em;"></i>
                        </a>
                      </div>
                      <?php if ($Owner || $Admin || $GP['customers-add']) {
                        ?>
                      <div class="input-group-addon no-print" style="padding: 2px 8px;">
                        <a href="<?= admin_url('customers/add'); ?>" id="add-customer"class="external" data-toggle="modal" data-target="#myModal">
                          <i class="fa fa-fw fa-plus-circle" id="addIcon"  style="font-size: 1.2em;"></i>
                        </a>
                      </div>
                      <?php
                      } ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('warehouse', 'slwarehouse'); ?>
                    <?php
                    $wh[''] = '';
                    $warehouses = $this->site->getAllWarehouses();

                    if ($warehouses) { // Patched
                      foreach ($warehouses as $warehouse) {
                        if ($warehouse->code == 'ADV') continue; // No Advertising.
                        if ($warehouse->code == 'LUC') continue; // No Lucretia.

                        $wh[$warehouse->id] = $warehouse->name;
                      }
                    }
                    echo form_dropdown('warehouse', $wh, XSession::get('warehouse_id'), 'id="slwarehouse" class="select2" data-placeholder="' . lang('select') . ' ' . lang('warehouse') . '" required="required" style="width:100%;" '); ?>
                  </div>
                </div>
              </div>
            </div><!-- /.panel panel-warning -->
            <div class="col-md-12" id="sticker">
              <div class="well well-sm">
                <div class="form-group" style="margin-bottom:0;">
                  <div class="input-group wide-tip">
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <i class="fa fa-barcode addIcon"></i></a></div>
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . lang('add_product_to_order') . '"'); ?>
                    <?php if ($Owner || $Admin || $GP['products-add']) {
                          ?>
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <a href="#" id="addManually" class="tip" title="<?= lang('add_product_manually') ?>">
                        <i class="fa fa-plus-circle addIcon" id="addIcon"></i>
                      </a>
                    </div>
                    <?php } ?>
                  </div>
                </div>
                <div class="clearfix"></div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('order_items'); ?> *</label>
                <div class="controls table-controls">
                  <!-- MAIN TABLE -->
                  <div class="table-responsive">
                    <table id="slTable" class="table items table-striped table-bordered table-condensed table-hover">
                      <thead>
                      <tr>
                        <th class="col-md-2"><?= lang('product') . ' ' . lang('name') . ' (' . lang('code') . ')'; ?></th>
                        <th class="col-md-2">Spec</th>
                        <th class="col-md-1"><?= lang('net_unit_price'); ?></th>
                        <th class="col-md-1">Width</th>
                        <th class="col-md-1">Length</th>
                        <th class="col-md-1">Area (M²)</th>
                        <th class="col-md-1">Sub Quantity</th>
                        <th class="col-md-1">Total Quantity</th>
                        <th><?= lang('subtotal'); ?> (<?= $default_currency->code ?>)</th>
                        <th class="col-md-4">Operator & Due Date</th>
                        <th style="width: 30px !important; text-align: center;">
                          <i class="fa fa-trash" style="opacity:0.5; filter:alpha(opacity=50);"></i>
                        </th>
                      </tr>
                      </thead>
                      <tbody></tbody>
                      <tfoot></tfoot>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <?= lang('document', 'document') ?>
                    <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false"
                         data-show-preview="false" class="form-control file">
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <?= lang('status', 'slsale_status'); ?>
                    <?php $sst = ['need_payment' => lang('need_payment'), 'waiting_production' => lang('waiting_production')];
                    echo form_dropdown('status', $sst, '', 'class="select2" required="required" id="slsale_status" style="width:100%"'); ?>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <?= lang('payment_term', 'slpayment_term'); ?>
                    <?php echo form_input('payment_term', '', 'class="form-control tip" data-trigger="focus" data-placement="top" title="' . lang('payment_term_tip') . '" id="slpayment_term" readonly="true"'); ?>
                  </div>
                </div>
                <?php if ($Owner || $Admin || $GP['sales-payments']) { ?>
                <div class="col-sm-4">
                  <div class="form-group">
                    <?= lang('payment_status', 'slpayment_status'); ?>
                    <?php $pst = ['pending' => lang('pending')];
                      echo form_dropdown('payment_status', $pst, 'pending', 'class="select2" required="required" id="slpayment_status" style="width:100%"'); ?>
                  </div>
                </div>
                <?php } else {
                      echo form_hidden('payment_status', 'pending');
                    } ?>
                <!-- <div class="col-sm-4">
                  <div class="form-group">
                    <label for="production_pic">Production PIC</label>
                    <?= form_dropdown('production_pic', '', '', 'class="user select2" data-placeholder="Select Production PIC" style="width:100%;"'); ?>
                  </div>
                </div>
                <div class="col-sm-4">
                  <div class="form-group">
                    <?= lang('production_due_date', 'production_due_date'); ?>
                    <input type="text" class="form-control datetime" name="production_due_date" id="production_due_date">
                  </div>
                </div> -->
                <div class="clearfix"></div>
              </div>
            </div>
            <div class="row" id="bt">
              <div class="col-md-12">
                <div class="col-md-6">
                  <div class="form-group">
                    <?= lang('sale_note', 'slnote'); ?>
                    <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="slnote" style="margin-top: 10px; height: 100px;"'); ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-12">
                <div class="col-md-6">
                  <div class="form-group">
                    <input id="bank_transfer" class="form-control" name="bank_transfer" type="checkbox" value="1">
                    <label for="bank_transfer" class="padding05 tip" title="Membuat Payment Validation untuk Bank Transfer">Bank Transfer</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <input type="submit" id="add_sale" class="btn btn-primary" style="padding: 6px 15px;" value="<?= lang('save'); ?>">
                <button type="button" class="btn btn-warning" id="draft_sale" style="padding: 6px 15px;"><?= lang('draft'); ?></button>
                <button type="button" class="btn btn-danger" id="reset"><?= lang('reset') ?>
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
      </div>
    </div>
  </div>
</div>

<div class="modal" id="prModal" tabindex="-1" role="dialog" aria-labelledby="prModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i
              class="fad fa-times"></i></span><span class="sr-only"><?=lang('close');?></span></button>
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
            <label for="poption" class="col-sm-4 control-label"><?= lang('product_option') ?></label>
            <div class="col-sm-8">
              <div id="poptions-div"></div>
            </div>
          </div>
          <div class="form-group">
            <label for="pprice" class="col-sm-4 control-label"><?= lang('unit_price') ?></label>

            <div class="col-sm-8">
              <?php $biller = $this->site->getBillerByID(XSession::get('biller_id')); ?>
              <input type="text" class="form-control" id="pprice" <?= ($Owner || $Admin || getPermission('sales-edit_price')) ? '' : 'readonly'; ?>>
            </div>
          </div>
          <table class="table table-bordered table-striped">
            <tr>
              <th style="width:25%;"><?= lang('net_unit_price'); ?></th>
              <th style="width:25%;"><span id="net_price"></span></th>
            </tr>
          </table>

          <input type="hidden" id="punit_price" value=""/>
          <input type="hidden" id="old_qty" value=""/>
          <input type="hidden" id="old_price" value=""/>
          <input type="hidden" id="row_id" value=""/>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="editItem"><?= lang('submit') ?></button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="mModal" tabindex="-1" role="dialog" aria-labelledby="mModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i
              class="fad fa-times"></i></span><span class="sr-only"><?=lang('close');?></span></button>
        <h4 class="modal-title" id="mModalLabel"><?= lang('add_product_manually') ?></h4>
      </div>
      <div class="modal-body" id="pr_popover_content">
        <form class="form-horizontal" role="form">
          <div class="form-group">
            <label for="mcode" class="col-sm-4 control-label"><?= lang('product_code') ?> *</label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="mcode">
            </div>
          </div>
          <div class="form-group">
            <label for="mname" class="col-sm-4 control-label"><?= lang('product_name') ?> *</label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="mname">
            </div>
          </div>

          <div class="form-group">
            <label for="mquantity" class="col-sm-4 control-label"><?= lang('quantity') ?> *</label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="mquantity">
            </div>
          </div>
          <div class="form-group">
            <label for="munit" class="col-sm-4 control-label"><?= lang('unit') ?> *</label>

            <div class="col-sm-8">
              <?php
              $uts[''] = '';
              if ( ! empty($units)) {
                foreach ($units as $unit) {
                  $uts[$unit->id] = $unit->name;
                }
              }
              echo form_dropdown('munit', $uts, '', 'id="munit" class="select2" style="width:100%;"');
              ?>
            </div>
          </div>
          <div class="form-group">
            <label for="mprice" class="col-sm-4 control-label"><?= lang('unit_price') ?> *</label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="mprice">
            </div>
          </div>
          <table class="table table-bordered table-striped">
            <tr>
              <th style="width:25%;"><?= lang('net_unit_price'); ?></th>
              <th style="width:25%;"><span id="mnet_price"></span></th>
            </tr>
          </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="addItemManually"><?= lang('submit') ?></button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function () {
    let billers = JSON.parse('<?= json_encode($blr); ?>');
    var warehouse_id = <?= (XSession::get('warehouse_id') ?? $Settings->default_warehouse); ?>;

    $('#approved').change(function(e) {
      if (e.target.checked) {
        addConfirm({
          title: 'Persetujuan / Consent',
          labels: {ok: 'Setuju', cancel: 'Batal'},
          message: 'Mencentang <b>Approved Sale</b> berarti bertanggung jawab ' +
            'jika item sudah di complete oleh operator tidak dapat dikembalikan lagi ' +
            'ke <b>Waiting Production</b>.',
          oncancel: () => {
            $('#approved').iCheck('uncheck');
          }
        })
      }
    });

    $('#draft_sale').click(function () {
      $('input[name="draft_type"]').val(1);
      $('#form_add_sale')[0].submit();
    });

    $('#slcustomer').change(function() {
      if (noResetItems) {
        noResetItems = false;
        return true;
      }
      slitems = {};
      localStorage.setItem('slitems', JSON.stringify(slitems));
      loadItems();
    });

    $('#slwarehouse').change(function() {
      if (noResetItems) {
        noResetItems = false;
        return true;
      }
      slitems = {};
      localStorage.setItem('slitems', JSON.stringify(slitems));
      loadItems();
    });

    $('#slpic').change(function () {
      $('#slbiller').val(billers[$(this).val()]).trigger('change');
    });
    $('#slbiller').val(billers[$('#slpic').val()]).trigger('change');

    $("#sldate").datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      language: 'sma',
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      minView: 2
    });
  });
</script>
