<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$mode = $this->input->get('m'); // noprice
$warehouseFrom = Warehouse::getRow(['id' => $pt->warehouse_id_from]);
$warehouseTo   = Warehouse::getRow(['id' => $pt->warehouse_id_to]);
?>

<div class="modal-content">
  <div class="modal-body">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fa fa-print"></i> <?= lang('print'); ?>
    </button>
    <div class="text-center" style="margin-bottom:20px;">
      <img src="<?= base_url() . 'assets/uploads/logos/logo-lucretia.png'; ?>" alt="<?= $Settings->site_name; ?>">
    </div>
    <div class="well well-sm">
      <div class="row bold">
        <div class="col-md-6">
          <div class="row">
            <div class="col-md-4">Created At</div>
            <div class="col-md-8">: <?= $pt->created_at ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Created By</div>
            <div class="col-md-8">: <?= $this->site->getUserByID($pt->created_by)->fullname ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Updated At</div>
            <div class="col-md-8">: <?= ($pt->updated_at ? $pt->updated_at : '') ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Updated By</div>
            <div class="col-md-8">: <?= ($pt->updated_by ? $this->site->getUserByID($pt->updated_by)->fullname : '') ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Status</div>
            <div class="col-md-8">: <?= ucwords(str_replace('_', ' ', $pt->status)) ?></div>
          </div>
        </div>
        <div class="col-md-6 pull-right text-right order_barcodes">
          <?= $this->ridintek->qrcode(admin_url('products/mutations/view/' . $pt->id)); ?>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-6">
        Kepada:<br />
        <h3 style="margin-top:10px;"><?= $warehouseTo->name . ' ( ' . $warehouseTo->code . ' )'; ?></h3>
        <?= '<p>' . $warehouseTo->address . '</p><p>' . $warehouseTo->phone . '<br>' . $warehouseTo->email . '</p>';
        ?>
      </div>
      <div class="col-xs-6">
        Dari:
        <h3 style="margin-top:10px;"><?= $warehouseFrom->name . ' ( ' . $warehouseFrom->code . ' )'; ?></h3>
        <?= '<p>' . $warehouseFrom->address . '</p><p>' . $warehouseFrom->phone . '<br>' . $warehouseFrom->email . '</p>';
        ?>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped print-table order-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Code</th>
            <th>Name</th>
            <th>Spec</th>
            <?php if ($mode != 'noprice') : ?>
              <th>Markon Price</th>
            <?php endif; ?>
            <th>Total Qty</th>
            <th>Received Qty</th>
            <th>Rest Qty</th>
            <?php if ($mode != 'noprice') : ?>
              <th>Subtotal</th>
            <?php endif; ?>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $r = 1;
          $grandTotal = 0;

          foreach ($ptitems as $ptitem) :
            $grandTotal += $ptitem->markon_price * $ptitem->quantity; ?>
            <tr>
              <td style="text-align:center; vertical-align:middle;"><?= $r; ?></td>
              <td style="text-align:center"><?= $ptitem->product_code; ?></td>
              <td style="text-align:center"><?= (!empty($ptitem->spec) ? htmlDecode($ptitem->spec) : ''); ?></td>
              <td style="vertical-align:middle;"><?= $ptitem->product_name; ?></td>
              <?php if ($mode != 'noprice') : ?>
                <td style="text-align:right; vertical-align:middle;"><?= formatCurrency($ptitem->markon_price); ?></td>
              <?php endif; ?>
              <td style="text-align:right; vertical-align:middle;"><?= formatQuantity($ptitem->quantity); ?></td>
              <td style="text-align:right; vertical-align:middle;"><?= formatQuantity($ptitem->received_qty); ?></td>
              <td style="text-align:right; vertical-align:middle;"><?= formatQuantity($ptitem->quantity - $ptitem->received_qty); ?></td>
              <?php if ($mode != 'noprice') : ?>
                <td style="text-align:right; vertical-align:middle;"><?= formatCurrency($ptitem->markon_price * $ptitem->quantity); ?></td>
              <?php endif; ?>
              <?= renderStatus($ptitem->status) ?>
            </tr>
          <?php
            $r++;
          endforeach;
          ?>
        </tbody>
        <?php if ($mode != 'noprice') : ?>
          <tfoot>
            <tr>
              <th colspan="8" class="text-right">Grand Total</th>
              <th><?= formatCurrency($grandTotal) ?></th>
              <th></th>
            </tr>
          </tfoot>
        <?php endif; ?>
      </table>
    </div>

    <div class="row">
      <div class="col-md-7">
        <?php if (!empty($pt->note)) {
        ?>
          <div class="well well-sm">
            <p class="bold"><?= lang('note'); ?>:</p>
            <div><?= htmlDecode($pt->note); ?></div>
          </div>
        <?php
        } ?>
      </div>
    </div>
  </div>
</div>