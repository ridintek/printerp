<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  var count = 1, an = 1, product_variant = 0, product_tax = 0, total = 0, toitems = {};
  $(document).ready(function () {
    <?php if ($transfer) { ?>
    localStorage.setItem('todate', '<?= date($dateFormats['php_ldate'], strtotime($transfer->date)) ?>');
    localStorage.setItem('from_warehouse', '<?= $transfer->from_warehouse_id ?>');
    localStorage.setItem('toref', '<?= $transfer->reference ?>');
    localStorage.setItem('to_warehouse', '<?= $transfer->to_warehouse_id ?>');
    localStorage.setItem('tostatus', '<?= $transfer->status ?>');
    localStorage.setItem('tonote', '<?= $this->sma->decode_html($transfer->note); ?>');
		localStorage.setItem('toitems', JSON.stringify(<?= $transfer_items; ?>));
    localStorage.setItem('update_mode', true); // Disable to_warehouse editing. See transfers.js.

    items = JSON.stringify(<?= $transfer_items; ?>);
    items = JSON.parse(items);
    localStorage.removeItem('toitems');

    $.each(items, function () {
      add_transfer_item(this);
    });
		<?php } ?>
    $(document).on('change', '#todate', function (e) {
      localStorage.setItem('todate', $(this).val());
    });
    if (todate = localStorage.getItem('todate')) {
      $('#todate').val(todate);
    }
    ItemnTotals();
    $("#add_item").autocomplete({
      //source: '<?= admin_url('transfers/suggestions'); ?>',
      source: function (request, response) {
        if (!$('#from_warehouse').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('<?=lang('select_above');?>');
          //response('');
          $('#add_item').focus();
          return false;
        }
        $.ajax({
          type: 'get',
          url: '<?= admin_url('transfers/suggestions'); ?>',
          dataType: "json",
          data: {
            term: request.term,
            warehouse_id: $("#from_warehouse").val()
          },
          success: function (data) {
            $(this).removeClass('ui-autocomplete-loading');
            response(data);
          }
        });
      },
      minLength: 1,
      autoFocus: false,
      delay: 250,
      response: function (event, ui) {
        if ($(this).val().length >= 16 && ui.content[0].id == 0) {
          //audio_error.play();
          if ($('#from_warehouse').val()) {
            bootbox.alert('<?= lang('no_match_found') ?>', function () {
              $('#add_item').focus();
            });
          } else {
            bootbox.alert('<?= lang('please_select_warehouse') ?>', function () {
              $('#add_item').focus();
            });
          }
          $(this).val('');
        }
        else if (ui.content.length == 1 && ui.content[0].id != 0) {
          ui.item = ui.content[0];
          $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
          $(this).autocomplete('close');
          $(this).removeClass('ui-autocomplete-loading');
        }
        else if (ui.content.length == 1 && ui.content[0].id == 0) {
          //audio_error.play();
          bootbox.alert('<?= lang('no_match_found') ?>', function () {
            $('#add_item').focus();
          });
          $(this).val('');

        }
      },
      select: function (event, ui) {
        event.preventDefault();
        if (ui.item.id !== 0) {
          var row = add_transfer_item(ui.item);
          if (row)
            $(this).val('');
        } else {
          //audio_error.play();
          bootbox.alert('<?= lang('no_match_found') ?>');
        }
      }
    });
    $('#add_item').bind('keypress', function (e) {
      if (e.keyCode == 13) {
        e.preventDefault();
        $(this).autocomplete("search");
      }
    });

    $(window).bind('beforeunload', function (e) {
      $.get('<?= admin_url('welcome/set_data/remove_tols/1'); ?>');
      if (count > 1) {
        var message = "You will loss data!";
        return message;
      }
    });
    $('#reset').click(function (e) {
      $(window).unbind('beforeunload');
    });
    $('#edit_transfer').click(function () {
      $(window).unbind('beforeunload');
      $('form.edit-to-form').submit();
    });
    var to_warehouse;
    $('#to_warehouse').on("select2-focus", function (e) {
      to_warehouse = $(this).val();
    }).on("select2-close", function (e) {
      if ($(this).val() == $('#from_warehouse').val()) {
        $(this).val(to_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
    });
    var from_warehouse;
    $('#from_warehouse').on("select2-focus", function (e) {
      from_warehouse = $(this).val();
    }).on("select2-close", function (e) {
      if ($(this).val() == $('#to_warehouse').val()) {
        $(this).val(from_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
    });

    let status = '<?= $transfer->status; ?>';
    if (status == 'received') {
      $('#edit_transfer, #reset').prop('disabled', true);
    }
    if (status != 'packing') {
      $('#reset').prop('disabled', true);
    }
		$('.rquantity').prop('readonly', true);
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-edit"></i><?= lang('update_transfer_status'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form', 'class' => 'edit-to-form'];
        echo admin_form_open_multipart('procurements/transfers/status/' . $transfer->id, $attrib)
        ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'todate'); ?>
                <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : $transfer->date), 'class="form-control input-tip" id="todate" required="required"'); ?>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('reference', 'ref'); ?>
                <?php echo form_input('reference', (isset($_POST['reference']) ? $_POST['reference'] : $transfer->reference), 'class="form-control input-tip" id="ref" required="required" readonly="readonly"'); ?>
              </div>
            </div>

            <div class="col-md-12">
              <?php if ($Owner || $Admin || ! XSession::get('warehouse_id')) { ?>
              <div class="panel panel-warning">
                <div class="panel-heading"><?= lang('please_select_these_before_adding_product') ?></div>
                <div class="panel-body" style="padding: 5px;">
                  <div class="col-md-4">
                    <div class="form-group">
                      <?= lang('from_warehouse', 'from_warehouse'); ?>
											<?php
											$wh[''] = '';
            			    foreach ($warehouses as $warehouse) {
            			      $wh[$warehouse->id] = $warehouse->name;
            			    }
                      echo form_dropdown('from_warehouse', $wh, (isset($_POST['from_warehouse']) ? $_POST['from_warehouse'] : $transfer->from_warehouse_id), 'id="from_warehouse" class="select2" data-placeholder="' . $this->lang->line('select') . ' ' . $this->lang->line('from_warehouse') . '" required="required" style="width:100%;" '); ?>
                    </div>
                  </div>
									<div class="col-md-4">
            			  <div class="form-group">
            			    <?= lang('to_warehouse', 'to_warehouse'); ?>
            			    <?php
            			    $wh[''] = '';
            			    foreach ($warehouses as $warehouse) {
            			      $wh[$warehouse->id] = $warehouse->name;
            			    }
            			    echo form_dropdown('to_warehouse', $wh, (isset($_POST['to_warehouse']) ? $_POST['to_warehouse'] : $Settings->default_warehouse), 'id="to_warehouse" class="select2" data-placeholder="' . $this->lang->line('select') . ' ' . $this->lang->line('to_warehouse') . '" required="required" style="width:100%;" ');
            			    ?>
            			  </div>
            			</div>
                </div>
              </div>
              <?php } else {
                  $warehouse_from = [
                    'type'  => 'hidden',
                    'name'  => 'from_warehouse',
                    'id'    => 'from_warehouse',
                    'value' => $transfer->from_warehouse_id,
                  ];
                  echo form_input($warehouse_from);

                  $warehouse_to = [
                    'type'  => 'hidden',
                    'name'  => 'to_warehouse',
                    'id'    => 'to_warehouse',
                    'value' => $transfer->to_warehouse_id,
                  ];
                  echo form_input($warehouse_to);
                    } ?>
              <div class="clearfix"></div>
            </div>

            <div class="col-md-12" id="sticker">
              <div class="well well-sm">
                <div class="form-group" style="margin-bottom:0;">
                  <div class="input-group wide-tip">
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <i class="fa fa-barcode addIcon"></i></a></div>
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . $this->lang->line('add_product_to_order') . '" disabled="disabled"'); ?>
                  </div>
                </div>
                <div class="clearfix"></div>
              </div>
            </div>

            <div class="clearfix"></div>
            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('order_items'); ?></label>

                <div class="table-responsive controls table-controls">
                  <table id="toTable" class="table items table-striped table-bordered table-condensed table-hover sortable_table">
                    <thead>
                    <tr>
                      <th class="col-md-4"><?= lang('product') . ' (' . lang('code') . ' - ' . lang('name') . ')'; ?></th>
                      <th class="col-md-2"><?= lang('spec'); ?></th>
                      <!--<th><?= lang('net_unit_cost'); ?></th>-->
                      <th><?= lang('unit'); ?></th>
                      <th class="xqty"><?= lang('quantity'); ?></th>
                      <!--<th><?= lang('min_order_qty'); ?></th>
                      <th><?= lang('safety_stock'); ?></th>
                      <th><?= lang('source_stock'); ?></th>
                      <th><?= lang('destination_stock'); ?></th>
                      <th><?= lang('subtotal'); ?> (<span class="currency"><?= $default_currency->symbol ?></span>)</th>-->
                      <th style="width: 30px !important; text-align: center;"><i
                          class="fa fa-trash"
                          style="opacity:0.5; filter:alpha(opacity=50);"></i></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
										<tfoot></tfoot>
                  </table>
                </div>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('status', 'tostatus'); ?>
                <?php
								$all_status = $this->sma->getAllStatus();
                $st[''] = '';

								foreach ($all_status as $status) {
                  if ($transfer->status == 'packing') {
                    if ($status == 'packing' || $status == 'sent') {
                      $st[$status] = lang($status);
                    }
                  }
                  if ($transfer->status == 'received') { // Ori: 'received' => 'completed'
										if ($status == 'received') {
											$st[$status] = lang($status);
										}
                  }
                  if ($transfer->status == 'sent') {
                    if ($status == 'sent' || $status == 'received') { // Ori: 'received' => 'completed'
                      $st[$status] = lang($status);
                    }
                  }
								}
                echo form_dropdown('status', $st, (isset($_POST['status']) ? $_POST['status'] : $transfer->status), 'id="tostatus" class="select2" data-placeholder="' . $this->lang->line('select') . ' ' . $this->lang->line('status') . '" required="required" style="width:100%;" ');
                ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false"
                     data-show-preview="false" class="form-control file">
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group">
                <?= lang('note', 'tonote'); ?>
                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'id="tonote" class="form-control" style="margin-top: 10px; height: 100px;"'); ?>
              </div>
            </div>
            <div class="col-md-12">
              <div
                class="from-group"><?php echo form_submit('edit_transfer', $this->lang->line('submit'), 'id="edit_transfer" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;"'); ?>
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
              <?php if ($Settings->tax1) { ?>
                <td><?= lang('product_tax') ?> <span class="totals_val pull-right" id="ttax1">0.00</span></td>
              <?php } ?>
              <td><?= lang('grand_total') ?> <span class="totals_val pull-right" id="gtotal">0.00</span>
              </td>
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
              class="fa fa-2x">&times;</i></span><span class="sr-only"><?=lang('close');?></span></button>
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
            <label for="pprice" class="col-sm-4 control-label"><?= lang('cost') ?></label>

            <div class="col-sm-8">
              <input type="text" class="form-control" id="pprice">
            </div>
          </div>
          <input type="hidden" id="old_tax" value=""/>
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
<script>
  $(document).ready(function () {
    $('#todate').datetimepicker({
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
<?php if (!$Owner || !$Admin || XSession::get('warehouse_id')) { ?>
<script class="procurements-transfers-status">
  $(document).ready(function() {
		$("#to_warehouse option[value='<?= XSession::get('warehouse_id'); ?>']").attr('disabled', 'disabled');
  });
</script>
<?php } ?>
