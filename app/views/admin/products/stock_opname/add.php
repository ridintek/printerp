<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function() {
    if (!localStorage.getItem('so_pic')) {
      localStorage.setItem('so_pic', "<?= $this->session->userdata('user_id'); ?>");
    }

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
    <h2 class="blue"><i class="fa-fw fad fa-plus"></i><?= lang('add_stock_opname'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <?php
        $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('products/stock_opname/add', $attrib); ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="col-md-3">
              <div class="form-group">
                <?= lang('date', 'so_date'); ?>
                <input type="datetime-local" class="form-control" id="so_date" name="date" value="<?= dtJS(date('Y-m-d H:i')) ?>">
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <?= lang('pic', 'so_pic'); ?>
                <?php
                $allUsers = $this->site->getUsers(['active' => 1]);

                if ($allUsers) {
                  foreach ($allUsers as $user) {
                    if (!$isAdmin && $this->session->userdata('user_id') != $user->id) continue;
                    $users[$user->id] = $user->fullname;
                  }
                }
                echo form_dropdown('pic', $users, '', 'class="select2" id="so_pic" style="width:100%;" required="required"'); ?>
              </div>
            </div>

            <?php if ($Owner || $Admin || !$this->session->userdata('warehouse_id')) { ?>
              <div class="col-md-3">
                <div class="form-group">
                  <?= lang('warehouse', 'so_warehouse'); ?>
                  <?php
                  $wh[''] = '';
                  $warehouses = $this->site->getWarehouses(['active' => 1]);
                  foreach ($warehouses as $warehouse) {
                    $wh[$warehouse->id] = $warehouse->name;
                  }
                  echo form_dropdown('warehouse', $wh, $this->Settings->default_warehouse, 'id="so_warehouse" class="select2" data-placeholder="Select Warehouse" required="required" style="width:100%;"'); ?>
                </div>
              </div>
            <?php } else { ?>
              <input type="hidden" id="so_warehouse" name="warehouse" value="<?= $this->session->userdata('warehouse_id'); ?>">
            <?php } ?>
            <div class="col-md-3">
              <div class="form-group">
                <?= lang('cycle', 'cycle'); ?>
                <input type="text" class="form-control" id="so_cycle" name="cycle" value="1" readonly="readonly">
              </div>
            </div>
            <div class="col-md-12">
              <div class="well well-sm">
                <div class="form-group" style="margin-bottom:0;">
                  <div class="input-group">
                    <div class="input-group-addon" style="padding-left: 10px; padding-right: 10px;">
                      <i class="fad fa-barcode"></i>
                    </div>
                    <?php echo form_input('add_item', '', 'class="form-control input-lg" id="add_item" placeholder="Add Stock Opname items"'); ?>
                    <div class="input-group-addon" style="padding: 2px 8px; border-left:0;">
                      <a href="#" class="tip" id="search_item" title="Search for items"><i class="fad fa-search"></i></a>
                    </div>
                    <div class="input-group-addon" style="padding: 2px 8px; border-left:0;">
                      <a href="#" class="tip" id="add_item_suggestion" title="Add Stock Opname Items Automatically"><i class="fad fa-plus-hexagon"></i></a>
                    </div>
                  </div>
                </div>
                <div class="clearfix"></div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="control-group table-group">
                <label class="table-label"><?= lang('products'); ?></label>

                <div class="controls table-controls">
                  <table id="soTable" class="table items table-striped table-bordered table-condensed table-hover">
                    <thead>
                      <tr>
                        <th><?= 'Product (' . lang('code') . ') ' . lang('name') ?></th>
                        <th class="col-sm-2">UoM</th>
                        <th class="col-sm-2"><?= lang('quantity'); ?></th>
                        <th class="col-sm-2"><?= lang('reject'); ?></th>
                        <th style="text-align: center; width:30px;">
                          <i class="fad fa-trash-alt" style="opacity:0.5; filter:alpha(opacity=50);"></i>
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
                <?= lang('document', 'document') ?>
                <input id="document" type="file" data-browse-label="<?= lang('browse'); ?>" name="document" data-show-upload="false" data-show-preview="false" class="form-control file">
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <?= lang('note', 'so_note'); ?>
                <?php echo form_textarea('note', (isset($_POST['note']) ? $_POST['note'] : ''), 'class="form-control" id="so_note" style="margin-top: 10px; height: 100px;"'); ?>
              </div>
            </div>
            <div class="col-md-12">
              <div class="from-group">
                <button class="btn btn-primary" type="submit" style="padding-left: 20px; padding-right: 20px;"><i class="fad fa-save"></i> Add</button>
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
    $('#add_item').on('keypress', function(e) {
      if (e.keyCode === 13) {
        e.preventDefault();
        searchItems($('#add_item').val());
      }
    });

    $('#so_pic').change(function() {
      localStorage.setItem('so_pic', $(this).val());
    });

    $('#so_warehouse').change(function() {
      localStorage.setItem('so_warehouse', $(this).val());
    });

    $('#add_item_suggestion').click(function() {
      let warehouse_id = $('#so_warehouse').val();
      $.ajax({
        data: {
          user: $('#so_pic').val(),
          warehouse: warehouse_id
        },
        method: 'GET',
        success: function(data) {
          if (typeof data == 'object' && !data.error) {
            localStorage.removeItem('so_items');
            localStorage.setItem('so_cycle', data.so_cycle);
            $('#so_cycle').val(data.so_cycle);
            addItems(data.items);
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

    let so_cycle = null; // Default 1.
    let so_pic = null;
    let so_warehouse = null;

    if (so_cycle = localStorage.getItem('so_cycle')) {
      $('#so_cycle').val(so_cycle);
    }

    if (so_pic = localStorage.getItem('so_pic')) {
      $('#so_pic').val(so_pic).trigger('change');
    }

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