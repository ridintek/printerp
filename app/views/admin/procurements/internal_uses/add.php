<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  <?php if (XSession::get('remove_tols')) { ?>
    if (localStorage.getItem('iuitems')) {
      localStorage.removeItem('iuitems');
    }
    if (localStorage.getItem('to_warehouse')) {
      localStorage.removeItem('to_warehouse');
    }
    if (localStorage.getItem('iunote')) {
      localStorage.removeItem('iunote');
    }
    if (localStorage.getItem('from_warehouse')) {
      localStorage.removeItem('from_warehouse');
    }
    <?php XSession::delete('remove_tols'); ?>
  <?php } ?>
  var count = 1,
    an = 1,
    product_variant = 0,
    shipping = 0,
    total = 0,
    iuitems = {};
  $(document).ready(function() {
    window.machines = JSON.parse('<?= json_encode($machines); ?>');

    if (!localStorage.getItem('todate')) {
      $("#todate").datetimepicker({
        format: site.dateFormats.js_ldate,
        fontAwesome: true,
        language: 'sma',
        todayBtn: 1,
        autoclose: 1,
        minView: 2
      }).datetimepicker('update', new Date());
    }
    $(document).on('change', '#todate', function(e) {
      localStorage.setItem('todate', $(this).val());
    });
    if (todate = localStorage.getItem('todate')) {
      $('#todate').val(todate);
    }
    if (localStorage.getItem('update_mode')) { // Enable to_warehouse editing. See transfers.js.
      localStorage.removeItem('update_mode');
    }

    ItemnTotals();

    $("#add_item").autocomplete({
      source: function(request, response) {
        if (!$('#from_warehouse').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('Please select <strong>From Warehouse</strong>.');
          $('#from_warehouse').focus();
          return false;
        }
        if (!$('#to_warehouse').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('Please select <strong>To Warehouse</strong>.');
          $('#to_warehouse').focus();
          return false;
        }

        $.ajax({
          type: 'get',
          url: '<?= admin_url('procurements/internal_uses/suggestions'); ?>',
          dataType: "json",
          data: {
            term: request.term,
            to_warehouse_id: $('#to_warehouse').val(),
            from_warehouse_id: $("#from_warehouse").val(),
            category: $('input[name="category"]:checked').val()
          },
          success: function(data) {
            $(this).removeClass('ui-autocomplete-loading');
            response(data);
          }
        });
      },
      minLength: 1,
      autoFocus: false,
      delay: 1000,
      response: function(event, ui) {
        if ($(this).val().length >= 16 && ui.content[0].id == 0) {
          //audio_error.play();
          if ($('#from_warehouse').val()) {
            bootbox.alert('<?= lang('no_match_found') ?>', function() {
              $('#add_item').focus();
            });
          } else {
            bootbox.alert('<?= lang('please_select_warehouse') ?>', function() {
              $('#add_item').focus();
            });
          }
          $(this).val('');
        } else if (ui.content.length == 1 && ui.content[0].id != 0) {
          //ui.item = ui.content[0];
          //$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
          //$(this).autocomplete('close');
        } else if (ui.content.length == 1 && ui.content[0].id == 0) {
          //audio_error.play();
        }
        $(this).removeClass('ui-autocomplete-loading');
      },
      select: function(event, ui) {
        let to_warehouse = $('#to_warehouse').val();
        event.preventDefault();
        if (!to_warehouse) {
          bootbox.alert('Please select To Warehouse first.');
        } else if (ui.item.id !== 0) {
          var row = add_internal_use_item(ui.item);
          if (row)
            $(this).val('');
          $('#add_internal_use').prop('disabled', false);
        } else {
          //audio_error.play();
          bootbox.alert('<?= lang('no_match_found') ?>');
        }
      }
    });
    $('#add_item').bind('keypress', function(e) {
      if (e.keyCode == 13) {
        e.preventDefault();
        $(this).autocomplete("search");
      }
    });

    var to_warehouse;
    $('#to_warehouse').on("select2-focus", function(e) {
      to_warehouse = $(this).val();
    }).on("select2-close", function(e) {
      if ($(this).val() != '' && $(this).val() == $('#from_warehouse').val()) {
        $(this).val(to_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
      $('#add_internal_use').prop('disabled', false);
    });
    var from_warehouse;
    $('#from_warehouse').on("select2-focus", function(e) {
      from_warehouse = $(this).val();
    }).on("select2-close", function(e) {
      if ($(this).val() != '' && $(this).val() == $('#to_warehouse').val()) {
        $(this).val(from_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
      $('#add_internal_use').prop('disabled', false);
    });
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-plus-circle"></i><?= lang('add_internal_use'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?php echo lang('enter_info'); ?></p>
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('procurements/internal_uses/add', $attrib) ?>

        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'todate'); ?>
                <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control input-tip date" id="todate" required="required"'); ?>
              </div>
            </div>

            <div class="col-md-4 support" style="display: none">
              <div class="form-group">
                <label for="ts">Team Support</label>
                <select class="select2" id="ts" name="ts" style="width:100%" disabled>
                  <?php foreach ($teamSupports as $ts) : ?>
                    <option value="<?= $ts->id ?>"><?= $ts->fullname ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="col-md-12">
              <div class="panel panel-warning">
                <div class="panel-heading"><?= lang('please_select_these_before_adding_product') ?></div>
                <div class="panel-body" style="padding: 5px;">
                  <?php if ($Owner || $Admin || !XSession::get('warehouse_id')) { ?>
                    <div class="col-md-4">
                      <div class="form-group">
                        <?= lang('from_warehouse', 'from_warehouse'); ?>
                        <?php
                        $wh[''] = '';
                        foreach ($warehouses as $warehouse) {
                          $wh[$warehouse->id] = $warehouse->name;
                        }
                        echo form_dropdown(
                          'from_warehouse',
                          $wh,
                          $Settings->default_warehouse,
                          'id="from_warehouse" class="select2" data-placeholder="Select Warehouse From" required="required" style="width:100%;"'
                        ); ?>
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
                        echo form_dropdown(
                          'to_warehouse',
                          $wh,
                          '',
                          'id="to_warehouse" class=" select2" data-placeholder="Select Warehouse To" required="required" style="width:100%;"'
                        );
                        ?>
                      </div>
                    </div>
                  <?php } else {
                    $from_warehouse = [
                      'type'  => 'hidden',
                      'name'  => 'from_warehouse',
                      'id'    => 'from_warehouse',
                      'value' => XSession::get('warehouse_id'),
                    ];
                    echo form_input($from_warehouse);

                    $to_warehouse = [
                      'type'  => 'hidden',
                      'name'  => 'to_warehouse',
                      'id'    => 'to_warehouse',
                      'value' => XSession::get('warehouse_id'),
                    ];
                    echo form_input($to_warehouse);
                  } ?>
                  <div class="col-md-4">
                    <div class="form-group">
                      <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <?php if ($Owner || $Admin || $GP['internal_uses-consumable']) { ?>
                          <label class="btn">
                            <input type="radio" name="category" id="category_consumable" value="consumable"> Consumable
                          </label>
                        <?php } ?>
                        <?php if ($Owner || $Admin || $GP['internal_uses-cmreport']) { ?>
                          <!-- <label class="btn">
                            <input type="radio" name="category" id="category_report" value="report"> Combo Report
                          </label> -->
                        <?php } ?>
                        <?php if ($Owner || $Admin || $GP['internal_uses-sparepart']) { ?>
                          <label class="btn">
                            <input type="radio" name="category" id="category_sparepart" value="sparepart"> Sparepart
                          </label>
                        <?php } ?>
                      </div>
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
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . lang('add_product_to_order') . '"'); ?>
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
                        <th class="col-md-4"><?= lang('machine'); ?></th>
                        <th class="col-md-2"><?= lang('counter'); ?></th>
                        <th><?= lang('unit'); ?></th>
                        <th><?= lang('quantity'); ?></th>
                        <th><?= lang('source_stock'); ?></th>
                        </th>
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
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('status', 'iustatus'); ?>
                <?php
                $post = ['need_approval' => lang('need_approval')];
                echo form_dropdown('status', $post, 'need_approval', 'id="iustatus" class="form-control input-tip select2" data-placeholder="' . lang('select') . ' ' . lang('status') . '" required="required" style="width:100%;" ');
                ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false" data-show-preview="false" class="form-control file">
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group">
                <?= lang('note', 'iunote'); ?>
                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'id="iunote" class="form-control" style="margin-top: 10px; height: 100px;"'); ?>
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group"><?php echo form_submit('add_internal_use', lang('submit'), 'id="add_internal_use" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;"'); ?>
                <button type="button" class="btn btn-danger" id="reset"><?= lang('reset') ?></button>
              </div>
            </div>
          </div>
        </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    let sparepart = "<?= ($GP['internal_uses-sparepart'] ?? NULL) ?>";
    let consumable = "<?= ($GP['internal_uses-consumable'] ?? NULL) ?>";
    let from_warehouse = $('#from_warehouse').val();
    let to_warehouse = $('#to_warehouse').val();

    if (sparepart) {
      $(`#category_sparepart`).iCheck('check');
    } else if (consumable) {
      $(`#category_consumable`).iCheck('check');
    }

    $('input[name="category"]').on('ifChecked', function(e) {
      if (e.target.checked) {
        localStorage.setItem('socategory', e.target.value);

        if (e.target.value == 'sparepart') {
          $('div.support').slideDown();
          $('#ts').prop('disabled', false);
        } else {
          $('div.support').slideUp();
          $('#ts').prop('disabled', true);
        }
      }
    });

    if (from_warehouse) localStorage.setItem('from_warehouse', from_warehouse);
    if (to_warehouse) localStorage.setItem('to_warehouse', to_warehouse);
  });
</script>