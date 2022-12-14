<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  var count = 1,
    an = 1,
    total = 0;
  $(document).ready(function() {
    <?php if ($inv): ?>
      localStorage.setItem('sldate', '<?= $this->sma->hrld($inv->date) ?>');
      localStorage.setItem('slcustomer', '<?= $inv->customer_id ?>');
      localStorage.setItem('slbiller', '<?= $inv->biller_id ?>');
      localStorage.setItem('slref', '<?= $inv->reference ?>');
      localStorage.setItem('slno_po', '<?= $inv->no_po ?>');
      localStorage.setItem('slwarehouse', '<?= $inv->warehouse_id ?>');
      localStorage.setItem('slsale_status', '<?= $inv->status ?>');
      localStorage.setItem('slpayment_status', '<?= $inv->payment_status ?>');
      localStorage.setItem('slpayment_term', '<?= $inv->payment_term ?>');
      localStorage.setItem('slnote', `<?= str_replace(["\r", "\n"], '', htmlDecode($inv->note)); ?>`);
      localStorage.setItem('slitems', JSON.stringify(<?= $inv_items; ?>));
      localStorage.setItem('edit_mode', '<?= $edit_mode; ?>');
    <?php endif; ?>

    $(document).on('change', '#sldate', function(e) {
      localStorage.setItem('sldate', $(this).val());
    });
    if (sldate = localStorage.getItem('sldate')) {
      $('#sldate').val(sldate);
    }

    ItemnTotals();
    $("#add_item").autocomplete({
      source: function(request, response) {
        if (!$('#slcustomer').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('<?= lang('select_above'); ?>');
          $('#add_item').focus();
          return false;
        }
        $.ajax({
          type: 'get',
          url: '<?= admin_url('sales/suggestions'); ?>',
          dataType: "json",
          data: {
            term: request.term,
            warehouse_id: $("#slwarehouse").val(),
            customer_id: $("#slcustomer").val()
          },
          success: function(data) {
            $(this).removeClass('ui-autocomplete-loading');
            //console.log('sales/edit/suggestion: ', data);
            response(data);
          }
        });
      },
      minLength: 1,
      autoFocus: false,
      delay: 1000,
      response: function(event, ui) {
        if ($(this).val().length >= 16 && ui.content[0].id == 0) {
          bootbox.alert('<?= lang('no_match_found') ?>', function() {
            $('#add_item').focus();
          });
          $(this).removeClass('ui-autocomplete-loading');
          $(this).val('');
        } else if (ui.content.length == 1 && ui.content[0].id != 0) {
          ui.item = ui.content[0];
          $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
          $(this).autocomplete('close');
          $(this).removeClass('ui-autocomplete-loading');
        } else if (ui.content.length == 1 && ui.content[0].id == 0) {
          bootbox.alert('<?= lang('no_match_found') ?>', function() {
            $('#add_item').focus();
          });
          $(this).removeClass('ui-autocomplete-loading');
          $(this).val('');
        }
      },
      select: function(event, ui) {
        event.preventDefault();
        if (ui.item.id !== 0) {
          var row = add_invoice_item(ui.item);
          if (row)
            $(this).val('');
        } else {
          bootbox.alert('<?= lang('no_match_found') ?>');
        }
      }
    });

    $(window).bind('beforeunload', function(e) {
      localStorage.setItem('remove_slls', true);
      if (count > 1) {
        var message = "You will loss data!";
        return message;
      }
    });

    $('#reset').click(function(e) {
      $(window).unbind('beforeunload');
    });

    $('#edit_sale').click(function() {
      $(window).unbind('beforeunload');
    });
  });
</script>


<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-plus"></i><?= lang('edit_sale'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?php echo lang('enter_info'); ?></p>
        <?php
        $creator = $this->site->getUserByID($inv->created_by);
        $editMode = ($edit_mode == 'operator' ? 'edit_operator' : 'edit');
        $readOnly = ($edit_mode == 'operator' ? ' readonly' : '');
        $attrib = ['data-toggle' => 'validator', 'id' => 'form_edit_sale', 'role' => 'form', 'class' => 'edit-so-form'];
        echo admin_form_open_multipart("sales/{$editMode}/" . $inv->id, $attrib)
        ?>
        <input type="hidden" name="draft_type" value="0">
        <input type="hidden" name="uri_callback" value="<?= ($_SERVER['HTTP_REFERER'] ?? 'admin/sales') ?>">
        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'sldate'); ?>
                <?php echo form_input('date', $inv->date, "class=\"form-control input-tip{$readOnly}\" id=\"sldate\" required=\"required\""); ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('reference', 'slref'); ?>
                <?php echo form_input('reference', $inv->reference, "class=\"form-control input-tip{$readOnly}\" id=\"slref\" required=\"required\""); ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('no_po', 'no_po'); ?>
                <?php echo form_input('no_po', $inv->no_po, "class=\"form-control input-tip{$readOnly}\" id=\"no_po\""); ?>
              </div>
            </div>
            <?php if ($Owner || $Admin || !XSession::get('biller_id')) { ?>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('biller', 'slbiller'); ?>
                  <?php
                  $bl[''] = '';
                  foreach ($billers as $biller) {
                    if (
                      $edit_mode != 'edit' &&
                      !$this->Owner && !$this->Admin &&
                      XSession::get('biller_id') != $biller->id
                    ) continue;
                    $bl[$biller->id] = $biller->name;
                  }
                  echo form_dropdown('biller', $bl, $inv->biller_id, 'id="slbiller" data-placeholder="' . lang('select') . ' ' . lang('biller') . '" required="required" class="select2" style="width:100%;"'); ?>
                </div>
              </div>
            <?php } else { ?>
              <input type="hidden" name="biller" value="<?= XSession::get('biller_id'); ?>">
            <?php  } ?>
            <?php if ($Owner || $Admin || ($creator->username == 'w2p') || getPermission('sales-edit')) { ?>
              <div class="col-md-4">
                <div class="form-group">
                  <?= lang('created_by', 'slpic'); ?>
                  <?php
                  $users = $this->site->getUsers();
                  foreach ($users as $user) {
                    if ($creator->username == 'w2p' && XSession::get('user_id') != $user->id) continue;
                    $group = $this->site->getUserGroup($user->id);
                    //if ($group->name != 'cs' && $group->name != 'tl') continue;
                    $usr[$user->id] = $user->fullname;
                    $blr[$user->id] = $user->biller_id;
                  }
                  echo form_dropdown('created_by', $usr, $inv->created_by, 'id="slpic" data-placeholder="' . lang('select') . ' ' . lang('pic_name') . '" required="required" class="select2" style="width:100%;"'); ?>
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

                if (!empty($users)) {
                  $usr = [];

                  foreach ($users as $user) {
                    $usr[$user->id] = $user->fullname;
                  }
                }
                echo form_dropdown('cashier_by', $usr, ($saleJS->cashier_by ?? ''), 'id="cashier" data-placeholder="Select Cashier" required="required" class="select2" style="width:100%;"'); ?>
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
                  <?php if ($Owner || $Admin || !XSession::get('warehouse_id')) { ?>
                    <div class="col-md-4">
                      <div class="form-group">
                        <?= lang('warehouse', 'slwarehouse'); ?>
                        <div class="input-group">
                          <?php
                          $wh[''] = '';
                          foreach ($warehouses as $warehouse) {
                            if ($warehouse->code == 'ADV') continue;
                            if ($warehouse->code == 'LUC') continue;

                            if (
                              $edit_mode != 'edit' &&
                              !$this->Owner && !$this->Admin &&
                              XSession::get('warehouse_id') != $warehouse->id &&
                              $warehouse->id != $inv->warehouse_id
                            ) continue;

                            $wh[$warehouse->id] = $warehouse->name;
                          }

                          echo form_dropdown('warehouse', $wh, $inv->warehouse_id, 'id="slwarehouse" class="select2" data-placeholder="' . lang('select') . ' ' . lang('warehouse') . '" required="required" style="width:100%;" ');
                          ?>
                          <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                            <a href="#" id="unlock_warehouse">
                              <i class="fa fa-fw fa-lock"></i>
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php } else { ?>
                    <input id="slwarehouse" name="warehouse" type="hidden" value="<?= $inv->warehouse_id; ?>">
                  <?php } ?>
                  <div class="col-md-4">
                    <div class="form-group">
                      <?= lang('customer', 'slcustomer'); ?>
                      <div class="form-group">
                        <?php echo form_dropdown('customer', '', $inv->customer_id, 'class="select2 rcustomer" id="slcustomer" data-placeholder="Select Customer" required="required" style="width:100%;"'); ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-12" id="sticker">
              <div class="well well-sm">
                <div class="form-group" style="margin-bottom:0;">
                  <div class="input-group wide-tip">
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <i class="fa fa-barcode addIcon"></i></a>
                    </div>
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . lang('add_product_to_order') . '"'); ?>
                    <?php if ($Owner || $Admin || $GP['products-add']) {
                    ?>
                      <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                        <a href="#" id="addManually">
                          <i class="fa fa-plus-circle addIcon" id="addIcon"></i>
                        </a>
                      </div>
                    <?php
                    } ?>
                  </div>
                </div>
                <div class="clearfix"></div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('order_items'); ?> *</label>

                <div class="controls table-controls">
                  <table id="slTable" class="table items table-striped table-bordered table-condensed table-hover">
                    <thead>
                      <tr>
                        <th class="col-md-2"><?= lang('product') . ' (' . lang('code') . ' - ' . lang('name') . ')'; ?></th>
                        <th class="col-md-2">Spec</th>
                        <th class="col-md-1"><?= lang('net_unit_price'); ?></th>
                        <th class="col-md-1">Width</th>
                        <th class="col-md-1">Length</th>
                        <th class="col-md-1">Area (M²)</th>
                        <th class="col-md-1">Sub Quantity</th>
                        <th class="col-md-1">Total Quantity</th>
                        <th><?= lang('subtotal'); ?> (<?= $default_currency->code ?>)</th>
                        <th class="col-md-4">Operator & Due Date</th>
                        <th style="width: 30px !important; text-align: center;"><i class="fa fa-trash-o" style="opacity:0.5; filter:alpha(opacity=50);"></i></th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot></tfoot>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false" data-show-preview="false" class="form-control file">
              </div>
            </div>

            <div class="col-sm-3">
              <div class="form-group">
                <?= lang('sale_status', 'slsale_status'); ?>
                <?php $sst = [];

                $sst[$inv->status] = lang($inv->status);

                if ($inv->status == 'preparing') {
                  $sst['waiting_production'] = lang('waiting_production');
                }

                echo form_dropdown('status', $sst, $inv->status, 'class="select2" required="required" id="slsale_status" style="width:100%"');
                ?>
              </div>
            </div>

            <div class="col-sm-3">
              <div class="form-group">
                <?= lang('payment_term', 'slpayment_term'); ?>
                <?php echo form_input('payment_term', $inv->payment_term, 'class="form-control tip" data-trigger="focus" data-placement="top" title="' . lang('payment_term_tip') . '" id="slpayment_term"'); ?>
              </div>
            </div>

            <div class="col-sm-3">
              <div class="form-group">
                <?= lang('discount', 'sldiscount'); ?>
                <?php echo form_input('discount', $inv->discount, 'class="form-control tip" data-trigger="focus" data-placement="top" title="' . lang('discount') . '" id="sldiscount"'); ?>
              </div>
            </div>
            <?= form_hidden('payment_status', $inv->payment_status); ?>
            <div class="clearfix"></div>

            <input type="hidden" name="total_items" value="" id="total_items" required="required" />

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
            <div class="col-md-12">
              <div class="form-group">
                <input type="submit" name="edit_sale" id="edit_sale" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;" value="<?= lang('save'); ?>">
                <button type="button" class="btn btn-warning" id="draft_sale" style="padding: 6px 15px;"><?= lang('draft'); ?></button>
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

      </div>

    </div>
  </div>
</div>

<div class="modal" id="prModal" tabindex="-1" role="dialog" aria-labelledby="prModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fa fa-2x">&times;</i></span><span class="sr-only">Close</span></button>
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
              <input type="text" class="form-control" id="pprice" <?= ($Owner || $Admin || getPermission('sales-edit_price')) ? '' : 'readonly'; ?>>
            </div>
          </div>
          <table class="table table-bordered table-striped">
            <tr>
              <th style="width:25%;"><?= lang('net_unit_price'); ?></th>
              <th style="width:25%;"><span id="net_price"></span></th>
            </tr>
          </table>
          <input type="hidden" id="punit_price" value="" />
          <input type="hidden" id="old_qty" value="" />
          <input type="hidden" id="old_price" value="" />
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
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true"><i class="fa fa-2x">&times;</i></span><span class="sr-only">Close</span></button>
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
              if (!empty($units)) {
                foreach ($units as $unit) {
                  $uts[$unit->id] = $unit->name;
                }
              }
              echo form_dropdown('munit', $uts, '', 'id="munit" class="select2" style="width:100%;"');
              ?>
            </div>
          </div>
          <?php if ($Settings->product_serial) {
          ?>
            <div class="form-group">
              <label for="mserial" class="col-sm-4 control-label"><?= lang('product_serial') ?></label>

              <div class="col-sm-8">
                <input type="text" class="form-control" id="mserial">
              </div>
            </div>
          <?php
          } ?>

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
<script>
  $(document).ready(function() {
    let approved = '<?= $saleJS->approved ?>';
    let billers = JSON.parse('<?= json_encode($blr); ?>');
    let customer = '<?= $inv->customer_id; ?>';
    let editMode = '<?= $edit_mode; ?>';
    let sales_status = '<?= $inv->status; ?>';

    if (approved == 1) {
      $('#approved').iCheck('check');
    }

    if (customer) {
      preSelectCustomer('#slcustomer', customer);
    }

    if (editMode == 'operator') {
      $('#sldate').prop('disabled', true);
      $('#slref').prop('readonly', true);
      $('#no_po').prop('readonly', true);
    }

    $('#draft_sale').click(function() {
      $(window).unbind('beforeunload');
      $('input[name="draft_type"]').val(1);
      $('#form_edit_sale')[0].submit();
    });

    if (sales_status == 'draft') {
      $('#slcustomer').prop('readonly', true);
      $('#slref').prop('readonly', true);
    }

    $('#slpic').change(function() {
      let user_id = $(this).val();
      let biller_id = (billers[user_id] ?? 7); // 7 = Online
      console.log(user_id);
      console.log(biller_id);
      console.log(billers);
      $('#slbiller').val(biller_id).trigger('change');
    });

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