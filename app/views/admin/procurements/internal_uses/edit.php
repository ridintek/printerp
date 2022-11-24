<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script type="text/javascript">
  var count = 1,
    total = 0,
    iuitems = {};
  $(document).ready(function() {
    window.machines = JSON.parse('<?= json_encode($machines); ?>');
    <?php if ($internal_use) { ?>
      localStorage.setItem('iudate', '<?= date($dateFormats['php_ldate'], strtotime($internal_use->date)) ?>');
      localStorage.setItem('from_warehouse', '<?= $internal_use->from_warehouse_id ?>');
      localStorage.setItem('iuref', '<?= $internal_use->reference ?>');
      localStorage.setItem('to_warehouse', '<?= $internal_use->to_warehouse_id ?>');
      localStorage.setItem('iustatus', '<?= $internal_use->status ?>');
      localStorage.setItem('iunote', `<?= htmlDecode($internal_use->note); ?>`);
      localStorage.setItem('iuitems', '<?= json_encode($internal_use_items); ?>');
      localStorage.setItem('iuse_mode', '<?= $iuse_mode; ?>');
    <?php } ?>
    <?php if ($Owner || $Admin) { ?>
      $(document).on('change', '#iudate', function(e) {
        localStorage.setItem('iudate', $(this).val());
      });
      if (iudate = localStorage.getItem('iudate')) {
        $('#iudate').val(iudate);
      }
    <?php } ?>
    ItemnTotals();
    $("#add_item").autocomplete({
      source: function(request, response) {
        if (!$('#from_warehouse').val()) {
          $('#add_item').val('').removeClass('ui-autocomplete-loading');
          bootbox.alert('<?= lang('select_above'); ?>');
          //response('');
          $('#add_item').focus();
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
          ui.item = ui.content[0];
          $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
          $(this).autocomplete('close');
          $(this).removeClass('ui-autocomplete-loading');
        } else if (ui.content.length == 1 && ui.content[0].id == 0) {
          //audio_error.play();
          bootbox.alert('<?= lang('no_match_found') ?>', function() {
            $('#add_item').focus();
          });
          $(this).val('');

        }
      },
      select: function(event, ui) {
        event.preventDefault();
        if (ui.item.id !== 0) {
          console.log('SELECTED');
          console.log(ui.item);
          var row = add_internal_use_item(ui.item);
          if (row)
            $(this).val('');
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

    $(window).bind('beforeunload', function(e) {
      $.get('<?= admin_url('welcome/set_data/remove_tols/1'); ?>');
      if (count > 1) {
        var message = "You will loss data!";
        return message;
      }
    });
    $('#reset').click(function(e) {
      $(window).unbind('beforeunload');
    });
    $('#edit_internal_use').click(function() {
      $(window).unbind('beforeunload');
      $('form.edit-to-form').submit();
    });
    var to_warehouse;
    $('#to_warehouse').on("select2-focus", function(e) {
      to_warehouse = $(this).val();
    }).on("select2-close", function(e) {
      if ($(this).val() == $('#from_warehouse').val()) {
        $(this).val(to_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
    });
    var from_warehouse;
    $('#from_warehouse').on("select2-focus", function(e) {
      from_warehouse = $(this).val();
    }).on("select2-close", function(e) {
      if ($(this).val() == $('#to_warehouse').val()) {
        $(this).val(from_warehouse).trigger('change');
        bootbox.alert('<?= lang('please_select_different_warehouse') ?>');
      }
    });
    let status = '<?= $internal_use->status; ?>';
    let iuse_mode = '<?= $iuse_mode; ?>';

    if (status == 'completed' && iuse_mode == 'status') {
      $('#edit_internal_use, #reset').prop('disabled', true);
    }
    if (status != 'packing') {
      $('#reset').prop('disabled', true);
    }

    $('.rquantity').prop('readonly', true);
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-edit"></i><?= ($iuse_mode == 'status' ? lang('status_internal_use') : lang('edit_internal_use')); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?php echo lang('enter_info'); ?></p>
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form', 'class' => 'edit-to-form'];
        if ($iuse_mode == 'status') {
          echo admin_form_open_multipart('procurements/internal_uses/status/' . $internal_use->id, $attrib);
        } else {
          echo admin_form_open_multipart('procurements/internal_uses/edit/' . $internal_use->id, $attrib);
        }
        ?>
        <input type="hidden" name="callback_url" value="<?= ($_SERVER['HTTP_REFERER'] ?? 'procurements/internal_uses') ?>">
        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('date', 'iudate'); ?>
                <?php echo form_input('date', $internal_use->date, 'class="form-control input-tip date" id="iudate" required="required"'); ?>
              </div>
            </div>

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('reference', 'ref'); ?>
                <?php echo form_input('reference',  $internal_use->reference, 'class="form-control input-tip" id="ref" required="required" readonly="readonly"'); ?>
              </div>
            </div>

            <div class="col-md-12">
              <div class="panel panel-warning">
                <div class="panel-heading"><?= lang('please_select_these_before_adding_product') ?></div>
                <div class="panel-body" style="padding: 5px;">
                  <?php if ($Owner || $Admin || !$this->session->userdata('warehouse_id')) { ?>
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
                          $internal_use->from_warehouse_id,
                          'id="from_warehouse" class="form-control input-tip select2" data-placeholder="Select Warehouse From" required="required" style="width:100%;"'
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
                          $internal_use->to_warehouse_id,
                          'id="to_warehouse" class="form-control input-tip select2" data-placeholder="Select Warehouse To" required="required" style="width:100%;"'
                        );
                        ?>
                      </div>
                    </div>
                  <?php } else {
                    $warehouse_from = [
                      'type'  => 'hidden',
                      'name'  => 'from_warehouse',
                      'id'    => 'from_warehouse',
                      'value' => $internal_use->from_warehouse_id,
                    ];
                    echo form_input($warehouse_from);

                    $warehouse_to = [
                      'type'  => 'hidden',
                      'name'  => 'to_warehouse',
                      'id'    => 'to_warehouse',
                      'value' => $internal_use->to_warehouse_id,
                    ];
                    echo form_input($warehouse_to);
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
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="' . $this->lang->line('add_product_to_order') . '"'); ?>
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

            <div class="col-md-4">
              <div class="form-group">
                <?= lang('status', 'iustatus'); ?>
                <?php
                $st = [];
                $st[$internal_use->status] = lang($internal_use->status); // Current status.

                if ($internal_use->status == 'need_approval') {
                  $st['approved'] = 'Approved';
                }
                if ($internal_use->status == 'approved') {
                  $st['packing'] = 'Packing';
                }
                if ($internal_use->status == 'packing') {
                  $st['cancelled'] = 'Cancelled';
                  $st['installed'] = 'Installed';
                }
                if ($internal_use->status == 'cancelled') {
                  $st['returned'] = 'Returned';
                }
                if ($internal_use->status == 'installed') {
                  $st['completed'] = 'Completed';
                }
                if (empty($internal_use->status)) { // If empty, then need approval.
                  $st['need_approval'] = 'Need Approval';
                }

                echo form_dropdown('status', $st, $internal_use->status, 'id="iustatus" class="form-control input-tip select2" data-placeholder="Select Status" required="required" style="width:100%;"');
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
                <?= lang('note', 'tonote'); ?>
                <?php echo form_textarea('note', '', 'id="tonote" class="form-control" style="margin-top: 10px; height: 100px;"'); ?>
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group"><?php echo form_submit('edit_internal_use', $this->lang->line('submit'), 'id="edit_internal_use" class="btn btn-primary" style="padding: 6px 15px; margin:15px 0;"'); ?>
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
<script>
  $(document).ready(function() {
    let category = "<?= strtolower($internal_use->category); ?>";

    if (category) {
      $(`#category_${category}`).iCheck('check');
    }
  });
</script>
<?php if (!$Owner || !$Admin || $this->session->userdata('warehouse_id')) { ?>
  <script class="procurements-internal_uses-status">
    $(document).ready(function() {
      $("#to_warehouse option[value='<?= $this->session->userdata('warehouse_id'); ?>']").attr('disabled', 'disabled');
    });
  </script>
<?php } ?>