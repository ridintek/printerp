<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function() {
    if ($.cookie('so_remove') == 1) {
      if (localStorage.getItem('so_items')) {
        localStorage.removeItem('so_items');
      }
      if (localStorage.getItem('so_cycle')) {
        localStorage.removeItem('so_cycle');
      }
      if (localStorage.getItem('so_warehouse')) {
        localStorage.removeItem('so_warehouse');
      }
      $.removeCookie('so_remove', {
        path: '/'
      });
    }
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fad fa-plus"></i><?= $title; ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart("products/stock_opname/{$mode}/{$opname->id}", $attrib); ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-3">
              <div class="form-group">
                <?= lang('date', 'sodate'); ?>
                <?php echo form_input('date', $opname->date, 'class="form-control input-tip" id="sodate" readonly="readonly"'); ?>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <?= lang('ref', 'reference'); ?>
                <?php echo form_input('reference', $opname->reference, 'class="form-control input-tip" id="reference" readonly="readonly"'); ?>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <?= lang('pic', 'so_pic'); ?>
                <?php
                $allUsers = $this->site->getUsers(['active' => 1]);

                if ($allUsers) {
                  foreach ($allUsers as $user) {
                    if ( ! $Owner && ! $Admin && XSession::get('user_id') != $user->id) continue;
                    $users[$user->id] = $user->fullname;
                  }
                }
                echo form_dropdown('pic', $users, $opname->created_by, 'class="select2" id="so_pic" style="width:100%;" required="required"'); ?>
              </div>
            </div>

            <?php if ($Owner || $Admin || !XSession::get('warehouse_id')) { ?>
              <div class="col-md-3">
                <div class="form-group">
                  <?= lang('warehouse', 'so_warehouse'); ?>
                  <?php
                  $wh[''] = '';
                  $warehouses = $this->site->getWarehouses();
                  foreach ($warehouses as $warehouse) {
                    if ($mode == 'confirm' && $warehouse->id != $opname->warehouse_id) continue;
                    $wh[$warehouse->id] = $warehouse->name;
                  }
                  echo form_dropdown('warehouse', $wh, $opname->warehouse_id, 'id="so_warehouse" class="select2" data-placeholder="Select Warehouse" required="required" style="width:100%;"'); ?>
                </div>
              </div>
            <?php } else { ?>
              <input type="hidden" id="so_warehouse" name="warehouse" value="<?= XSession::get('warehouse_id'); ?>">
            <?php } ?>
            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('products'); ?></label>

                <div class="controls table-controls">
                  <table id="soTable" class="table items table-striped table-bordered table-condensed table-hover">
                    <thead>
                      <tr>
                        <th><?= 'Product (' . lang('code') . ') ' . lang('name') ?></th>
                        <th class="col-sm-2">UoM</th>
                        <th>Stock Qty</th>
                        <th>First SO Qty</th>
                        <th>Reject SO Qty</th>
                        <th>Update SO Qty</th>
                        <th>Difference Qty</th>
                        <th>Price</th>
                        <th>Sub Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if ($items) {
                        foreach ($items as $item) {
                          $insert_stock = ($item->last_qty ?? $item->first_qty);
                      ?>
                          <tr>
                            <td>
                              <input type="hidden" name="product_id[]" value="<?= $item->product_id; ?>">(<?= $item->product_code; ?>) <?= $item->product_name; ?>
                            </td>
                            <td class="col-sm-2 text-center"><?= $item->unit_name; ?></td>
                            <td class="text-center">
                              <input type="hidden" name="quantity[]" value="<?= formatDecimal($item->quantity); ?>"><?= formatDecimal($item->quantity); ?>
                            </td>
                            <td class="text-center">
                              <input type="hidden" name="first_qty[]" value="<?= formatDecimal($item->first_qty); ?>"><?= formatDecimal($item->first_qty); ?>
                            </td>
                            <td class="text-center">
                              <input type="hidden" name="reject_qty[]" value="<?= formatDecimal($item->reject_qty); ?>"><?= formatDecimal($item->reject_qty); ?>
                            </td>
                            <td class="col-sm-2"><input class="form-control editor text-center" name="last_qty[]" type="number" step="0.000001" value="<?= filterDecimal($insert_stock); ?>"></td>
                            <td class="text-center"><?= formatDecimal($insert_stock - $item->quantity); ?></td>
                            <td class="text-right"><input type="hidden" name="price[]" value="<?= $item->price; ?>"><?= formatDecimal($item->price); ?></td>
                            <td class="text-right"><?= formatDecimal($item->subtotal); ?></td>
                          </tr>
                      <?php
                        }
                      }
                      ?>
                    </tbody>
                    <tfoot></tfoot>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('status', 'status'); ?>
                <?php
                $opt = [];
                $opt[$opname->status] = lang($opname->status);
                ?>
                <?= form_dropdown('status', $opt, $opname->status, 'class="form-control select2"'); ?>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false" data-show-preview="false" class="form-control file">
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <?= lang('note', 'sonote'); ?>
                <?php echo form_textarea('note', htmlDecode($opname->note), 'class="form-control" id="sonote" style="margin-top: 10px; height: 100px;"'); ?>
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group">
                <button class="btn btn-primary" type="submit" style="padding-left: 20px; padding-right: 20px;"><i class="fad fa-check"></i> <?= lang($mode); ?></button>
                <button class="btn btn-danger" id="cancel" type="button"><i class="fad fa-undo"></i> Cancel</button>
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
    let so_status = '<?= $opname->status; ?>';

    $('#add_item').on('keypress', function(e) {
      if (e.keyCode === 13) {
        e.preventDefault();
        searchItems($('#add_item').val());
      }
    });

    $('#so_warehouse').change(function() {
      localStorage.setItem('so_warehouse', $(this).val());
    });

    $('#add_item_suggestion').click(function() {
      let warehouse_id = $('#so_warehouse').val();
      $.ajax({
        data: {
          user: '<?= XSession::get('user_id'); ?>',
          warehouse: warehouse_id
        },
        method: 'GET',
        success: function(data) {
          if (typeof data == 'object' && !data.error) {
            localStorage.removeItem('soitems');
            localStorage.setItem('socycle', data.so_cycle);
            $('#so_cycle').val(data.so_cycle);
            addItems(data.data);
          } else if (typeof data == 'object' && data.error) {
            bootbox.alert(data.msg);
          } else {
            bootbox.alert('Unknown error. Response is not an object.');
          }
        },
        url: site.base_url + 'products/stock_opname/getStockOpnameSuggestions'
      });
    });

    $('#cancel').click(function() {
      location.href = site.base_url + 'products/stock_opname';
    });

    $('#search_item').click(function() {
      searchItems($('#add_item').val());
    });

    let so_warehouse = null;

    if (so_warehouse = localStorage.getItem('so_warehouse')) {
      $('#so_warehouse').val(so_warehouse).trigger('change');
    }

    function searchItems(query) {
      if (typeof query === 'string' && query.length > 0) {
        showModal(site.base_url + 'products/stock_opname/suggestions?term=' +
          encodeURIComponent(query) + '&warehouse=' + $('#so_warehouse').val());
      } else {
        bootbox.alert('Harap input item!');
      }
    }
  });
</script>