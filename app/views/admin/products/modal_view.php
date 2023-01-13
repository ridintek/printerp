<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?= $product->name . (SHOP && $product->hide != 1 ? ' (' . lang('shop_views') . ': ' . $product->views . ')' : ''); ?></h4>
  </div>
  <div class="modal-body">
    <div class="row">
      <div class="col-xs-5">
        <img id="pr-image" src="<?= base_url() ?>assets/uploads/<?= $product->image ?>" alt="<?= $product->name ?>" class="img-responsive img-thumbnail" />
        <div id="multiimages" class="padding10">
          <?php if (!empty($images)) {
            echo '<a class="img-thumbnail change_img" href="' . base_url() . 'assets/uploads/' . $product->image . '" style="margin-right:5px;"><img class="img-responsive" src="' . base_url() . 'assets/uploads/thumbs/' . $product->image . '" alt="' . $product->image . '" style="width:' . $Settings->twidth . 'px; height:' . $Settings->theight . 'px;" /></a>';
            foreach ($images as $ph) {
              echo '<div class="gallery-image"><a class="img-thumbnail change_img" href="' . base_url() . 'assets/uploads/' . $ph->photo . '" style="margin-right:5px;"><img class="img-responsive" src="' . base_url() . 'assets/uploads/thumbs/' . $ph->photo . '" alt="' . $ph->photo . '" style="width:' . $Settings->twidth . 'px; height:' . $Settings->theight . 'px;" /></a>';
              if ($Owner || $Admin || $GP['products-edit']) {
                echo '<a href="#" class="delimg" data-item-id="' . $ph->id . '"><i class="fad fa-times"></i></a>';
              }
              echo '</div>';
            }
          }
          ?>
          <div class="clearfix"></div>
        </div>
      </div>
      <div class="col-xs-7">
        <div class="table-responsive">
          <table class="table table-borderless table-striped table-right-left">
            <tbody>
              <tr>
                <td colspan="2" style="background-color:#FFF;"></td>
              </tr>
              <tr>
                <td style="width:30%;"><?= lang('barcode_qrcode'); ?></td>
                <td style="width:70%;">
                  <img src="<?= admin_url('gallery/barcode?data=' . $product->code . '&type='); ?>" alt="<?= $product->code; ?>" class="bcimg" />
                  <?= $this->ridintek->qrcode(admin_url('products/view/' . $product->id)); ?>
                </td>
              </tr>
              <tr>
                <td><?= lang('active'); ?></td>
                <td><?= ($product->active ? 'Yes' : 'No'); ?></td>
              </tr>
              <?php if ($product->type == 'combo' || $product->type == 'service') { ?>
                <tr>
                  <td><?= lang('auto_complete'); ?></td>
                  <td><?= (!empty($productJS->autocomplete) ? 'Yes' : 'No'); ?></td>
                </tr>
              <?php } ?>
              <tr>
                <td><?= lang('type'); ?></td>
                <td><?= lang($product->type); ?></td>
              </tr>
              <tr>
                <td><?= lang('name'); ?></td>
                <td><?= $product->name; ?></td>
              </tr>
              <tr>
                <td><?= lang('code'); ?></td>
                <td><?= $product->code; ?></td>
              </tr>
              <?php if (!empty($productJS->priority)) { ?>
                <tr>
                  <td><?= lang('priority'); ?></td>
                  <td><?= $productJS->priority; ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->sn)) { ?>
                <tr>
                  <td><?= lang('serial_number'); ?></td>
                  <td><?= $productJS->sn; ?></td>
                </tr>
              <?php } ?>
              <tr>
                <td><?= lang('category'); ?></td>
                <td><?= $category->name; ?></td>
              </tr>
              <?php if ($product->subcategory_id) { ?>
                <tr>
                  <td><?= lang('subcategory'); ?></td>
                  <td><?= $subcategory->name; ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->condition)) { ?>
                <tr>
                  <td><?= lang('condition'); ?></td>
                  <?= renderStatus($productJS->condition); ?>
                </tr>
              <?php } ?>
              <tr>
                <td><?= lang('assigned_at'); ?></td>
                <?= (!empty($productJS->assigned_at) ? $productJS->assigned_at : ''); ?>
              </tr>
              <tr>
                <td><?= lang('assigned_by'); ?></td>
                <?= (!empty($productJS->assigned_by) ? getUser(['id' => $productJS->assigned_by])->fullname : ''); ?>
              </tr>
              <?php if (!empty($productJS->disposal_date)) { ?>
                <tr>
                  <td><?= lang('disposal_date'); ?></td>
                  <td><?= $productJS->disposal_date; ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->disposal_price)) { ?>
                <tr>
                  <td><?= lang('disposal_price'); ?></td>
                  <td><?= formatCurrency($productJS->disposal_price); ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->maintenance_qty)) { ?>
                <tr>
                  <td><?= lang('maintenance_qty'); ?></td>
                  <td><?= $productJS->maintenance_qty; ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->maintenance_cost)) { ?>
                <tr>
                  <td><?= lang('maintenance_cost'); ?></td>
                  <td><?= formatCurrency($productJS->maintenance_cost); ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->order_date)) { ?>
                <tr>
                  <td><?= lang('order_date'); ?></td>
                  <td><?= $productJS->order_date; ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->order_price)) { ?>
                <tr>
                  <td><?= lang('order_price'); ?></td>
                  <td><?= formatCurrency($productJS->order_price); ?></td>
                </tr>
              <?php } ?>
              <?php if (!empty($productJS->updated_at)) { ?>
                <tr>
                  <td><?= lang('updated_at'); ?></td>
                  <td><?= $productJS->updated_at; ?></td>
                </tr>
              <?php } ?>
              <tr>
                <td><?= lang('unit'); ?></td>
                <td><?= $unit ? $unit->name . ' (' . $unit->code . ')' : ''; ?></td>
              </tr>
              <tr>
                <td><?= lang('sale_unit'); ?></td>
                <td><?= $sale_unit ? $sale_unit->name . ' (' . $sale_unit->code . ')' : ''; ?></td>
              </tr>
              <?php if ($product->type == 'standard') { ?>
                <tr>
                  <td><?= lang('purchased_date'); ?></td>
                  <td><?= ($productJS->purchased_at ?? ''); ?></td>
                </tr>
                <tr>
                  <td><?= lang('purchase_unit'); ?></td>
                  <td><?= $purchase_unit ? $purchase_unit->name . ' (' . $purchase_unit->code . ')' : ''; ?></td>
                </tr>
                <tr>
                  <td><?= lang('min_order_qty'); ?></td>
                  <td><?= formatQuantity($product->min_order_qty); ?></td>
                </tr>
              <?php } ?>
              <tr>
                <td>Min. Production Time</td>
                <td><?= (json_decode($product->json_data)->min_prod_time ?? '-'); ?></td>
              </tr>
              <tr>
                <td>Production Time Per Qty</td>
                <td><?= (json_decode($product->json_data)->prod_time_qty ?? '-'); ?></td>
              </tr>
              <?php
              $supplier = $this->site->getSupplierByID($product->supplier_id);
              if ($supplier) { ?>
                <tr>
                  <td><?= lang('supplier'); ?></td>
                  <td><?= $supplier->company . ' (' . $supplier->name . ')'; ?></td>
                </tr>
              <?php
              } ?>
              <?php if (($Owner || $Admin) && $product->type == 'standard') {

                echo '<tr><td>Average Cost</td><td>' . formatCurrency($product->avg_cost) . '</td></tr>';
                echo '<tr><td>' . lang('cost') . '</td><td>' . formatCurrency($product->cost) . '</td></tr>';
                echo '<tr><td>' . lang('markon_percent') . '</td><td>' . formatDecimal($product->markon) . '</td></tr>';
                echo '<tr><td>' . lang('price') . '</td><td>' . formatCurrency($product->price) . '</td></tr>';
                echo '<tr><td>' . lang('markon_price') . '</td><td>' . formatCurrency($product->markon_price) . '</td></tr>';
              } else if ($product->type == 'standard') {
                if ($this->session->userdata('show_cost')) {
                  echo '<tr><td>Average Cost</td><td>' . formatCurrency($product->avg_cost) . '</td></tr>';
                  echo '<tr><td>' . lang('cost') . '</td><td>' . formatCurrency($product->cost) . '</td></tr>';
                  echo '<tr><td>' . lang('markon_percent') . '</td><td>' . formatDecimal($product->markon_price) . '</td></tr>';
                }
                if ($this->session->userdata('show_price')) {
                  echo '<tr><td>' . lang('price') . '</td><td>' . formatCurrency($product->price) . '</td></tr>';
                  echo '<tr><td>' . lang('markon_price') . '</td><td>' . formatCurrency($product->markon_price) . '</td></tr>';
                }
              } ?>
              <tr>
                <td><?= lang('warehouses'); ?></td>
                <td><?= (!$product->warehouses ? lang('all') : $product->warehouses); ?></td>
              </tr>
              <?php if ($product->safety_stock != 0) { ?>
                <tr>
                  <td><?= lang('safety_stock'); ?></td>
                  <td><?= formatStock($product->safety_stock); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="clearfix"></div>
      <div class="col-xs-12">
        <div class="row">
          <?php if (
            $product->type == 'standard' && getPermission('products-std_qty') ||
            $product->type == 'service'
          ) { ?>
            <!-- WAREHOUSE STOCK -->
            <div class="col-xs-5">
              <?php if ($product->type == 'standard' || $product->type == 'service') { ?>
                <h3 class="bold"><?= lang('warehouse_stock') ?></h3>
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-condensed dfTable two-columns">
                    <thead>
                      <tr>
                        <th><?= lang('warehouse_name'); ?></th>
                        <th><?= lang('quantity'); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($warehouses as $warehouse) {
                        if ($this->session->userdata('warehouse_id')) {
                          if ($this->session->userdata('warehouse_id') != $warehouse->id) continue;
                        }
                        echo '<tr><td>' . $warehouse->name . ' (' . $warehouse->code . ')</td>';
                        echo '<td class="text-right"><strong>' . formatStock($warehouse->quantity) . '</strong>' . '</td></tr>';
                      } ?>
                    </tbody>
                  </table>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
          <!-- WAREHOUSE STOCK ALERT -->
          <div class="col-xs-7">
            <?php if ($product->type == 'standard') { ?>
              <h3 class="bold"><?= lang('warehouse_stock_alert'); ?></h3>
              <table class="table table-bordered table-condensed table-hover table-striped">
                <thead>
                  <tr>
                    <th class="col-xs-6"><?= lang('warehouse'); ?></th>
                    <th class="col-xs-6"><?= lang('safety_stock'); ?></th>
                  </tr>
                  </head>
                <tbody>
                  <?php
                  if (!empty($warehouses)) {
                    foreach ($warehouses as $wh) {
                      if ($this->session->userdata('warehouse_id')) {
                        if ($this->session->userdata('warehouse_id') != $wh->id) continue;
                      } ?>
                      <tr>
                        <td><?= $wh->name; ?></td>
                        <td class="text-center">
                          <strong><?= formatStock($wh->safety_stock); ?></strong>
                        </td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
            <?php } ?>
            <?php if ($product->type == 'combo') { ?>
              <h3 class="bold"><?= lang('combo_items') ?></h3>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-condensed dfTable two-columns">
                  <thead>
                    <tr>
                      <th><?= lang('product_name') ?></th>
                      <th><?= lang('quantity') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (!empty($combo_items)) { // Patched
                      foreach ($combo_items as $combo_item) {
                        echo '<tr><td>' . $combo_item->name . ' (' . $combo_item->code . ') </td><td>' . $this->sma->formatQuantity($combo_item->qty) . '</td></tr>';
                      }
                    } ?>
                  </tbody>
                </table>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="col-xs-12">
        <?= isset($product->details) ? '<div class="panel panel-success"><div class="panel-heading">' . lang('product_details_for_invoice') . '</div><div class="panel-body">' . $product->details . '</div></div>' : ''; ?>
        <?= isset($product->product_details) ? '<div class="panel panel-primary"><div class="panel-heading">' . lang('product_details') . '</div><div class="panel-body">' . $product->product_details . '</div></div>' : ''; ?>
      </div>
    </div>
    <div class="buttons">
      <div class="btn-group btn-group-justified">
        <div class="btn-group">
          <a href="<?= admin_url('products/print_barcodes/' . $product->id) ?>" class="tip btn btn-primary" title="<?= lang('print_barcode_label') ?>">
            <i class="fad fa-print"></i>
            <span class="hidden-sm hidden-xs"><?= lang('print_barcode_label') ?></span>
          </a>
        </div>
        <div class="btn-group">
          <a href="<?= admin_url('products/pdf/' . $product->id) ?>" class="tip btn btn-primary" title="<?= lang('pdf') ?>">
            <i class="fad fa-download"></i>
            <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
          </a>
        </div>
        <div class="btn-group">
          <a href="<?= admin_url('products/edit/' . $product->id) ?>" class="tip btn btn-warning tip" title="<?= lang('edit_product') ?>">
            <i class="fad fa-edit"></i>
            <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
          </a>
        </div>
        <div class="btn-group">
          <a href="#" class="tip btn btn-danger bpo" title="<b><?= lang('delete_product') ?></b>" data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('products/delete/' . $product->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>" data-html="true" data-placement="top">
            <i class="fad fa-trash"></i>
            <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
          </a>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      $(document).ready(function() {
        $('.tip').tooltip();
      });
    </script>
  </div>
</div>
<?php
  $warehouseId = XSession::get('warehouse_id') ?? NULL;
  $qty = ($warehouseId ? WarehouseProduct::getRow(['product_id' => $product->id, 'warehouse_id' => $warehouseId])->quantity : 'null');
?>
<script type="text/javascript">
  $(document).ready(function() {
    let qty = <?= $qty ?>;
    // For debugging purpose only.
    typing('stokpiro', () => {
      if (qty != null) {
        toastr.success('<?= $product->code ?>: <?= $qty ?>');
      } else {
        toastr.error('Harus non-Admin');
      }
    });
  });
</script>