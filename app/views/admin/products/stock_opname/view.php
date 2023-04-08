<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-body">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <div class="text-center" style="margin-bottom:20px;">
      <img src="<?= base_url() . 'assets/uploads/logos/logo-indoprinting-300.png'; ?>" alt="<?= $Settings->site_name; ?>">
    </div>
    <div class="well well-sm">
      <div class="row bold">
        <div class="col-xs-6">
          <div class="row">
            <div class="col-sm-3">Date</div>
            <div class="col-sm-8">: <?= $opname->date; ?></div>
          </div>
          <?php
          if ($opname->created_by) {
            $creator = $this->site->getUserByID($opname->created_by);
          ?>
            <div class="row">
              <div class="col-sm-3">Created by</div>
              <div class="col-sm-8">: <?= $creator->fullname; ?></div>
            </div>
            <div class="row">
              <div class="col-sm-3">Created at</div>
              <div class="col-sm-8">: <?= $opname->date; ?></div>
            </div>
          <?php } ?>
          <?php if ($opname->updated_by) {
            $updater = $this->site->getUserByID($opname->updated_by);
          ?>
            <div class="row">
              <div class="col-sm-3">Updated by</div>
              <div class="col-sm-8">: <?= $updater->fullname; ?></div>
            </div>
            <div class="row">
              <div class="col-sm-3">Updated at</div>
              <div class="col-sm-8">: <?= $opname->updated_at; ?></div>
            </div>
          <?php } ?>
          <div class="row">
            <div class="col-sm-3">Reference</div>
            <div class="col-sm-8">: <?= $opname->reference; ?></div>
          </div>
          <div class="row">
            <div class="col-sm-3">Cycle</div>
            <div class="col-sm-8">: <?= $opname->cycle; ?></div>
          </div>
        </div>
        <div class="col-xs-6 pull-right text-right order_barcodes">
          <?= $this->ridintek->qrcode(admin_url('purchases/view/')); ?>
        </div>
        <div class="clearfix"></div>
      </div>
      <div class="clearfix"></div>
    </div>
    <div class="row">
      <?php if (isset($supplier)) { ?>
        <div class="col-xs-6">
          <?= lang('to'); ?>:
          <h3 style="margin-top:10px;"><?= $supplier->name; ?></h3>
          <?= '<p>' . $supplier->address . '</p><p>' . $supplier->phone . '<br>' . $supplier->email . '</p>';
          ?>
        </div>
      <?php } ?>
      <?php if (isset($warehouse)) { ?>
        <div class="col-xs-6">
          <?= lang('from'); ?>:<br />
          <h3 style="margin-top:10px;"><?= $warehouse->name . ' ( ' . $warehouse->code . ' )'; ?></h3>
          <?= '<p>' . $warehouse->address . '</p><p>' . $warehouse->phone . '<br>' . $warehouse->email . '</p>';
          ?>
        </div>
      <?php } ?>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped order-table">
        <thead>
          <tr>
            <th style="text-align:center; vertical-align:middle;">No</th>
            <th style="vertical-align:middle;">Description</th>
            <th style="vertical-align:middle;">Stock Qty</th>
            <th style="vertical-align:middle;">First Qty</th>
            <th style="vertical-align:middle;">Reject Qty</th>
            <th style="vertical-align:middle;">Update Qty</th>
            <th style="vertical-align:middle;">Difference Qty</th>
            <th style="vertical-align:middle;">Price</th>
            <th style="vertical-align:middle;">Sub Total</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $grand_total = 0;
          $r = 1;
          if (!empty($items)) {
            $class = '';
            foreach ($items as $item) {
              $item_first_qty  = filterDecimal($item->first_qty);
              $item_reject_qty = filterDecimal($item->reject_qty);
              $item_last_qty   = filterDecimal($item->last_qty);
              $item_quantity   = filterDecimal($item->quantity);

              $rest_qty = (($item->last_qty ?? $item->first_qty) - $item->quantity) + $item_reject_qty;
              $grand_total += $item->subtotal;
              $is_edited = ($item->last_qty !== NULL && ($item->first_qty != $item->last_qty) ? TRUE : FALSE);
              $is_plus   = ($item->first_qty > $item->quantity ? TRUE : FALSE);
              $is_minus  = ($rest_qty < 0 ? TRUE : FALSE);
              $is_equal  = ($rest_qty == 0 ? TRUE : FALSE);

              if ($is_edited) {
                $class = ' class="warning" '; // Yellow
              } else
            if ($is_plus) {
                $class = ' class="primary" '; // Blue
              } else
            if ($is_minus) {
                $class = ' class="danger" '; // Red
              } else
            if ($is_equal) {
                $class = ' class="success" '; // Green
              }
          ?>
              <tr<?= $class; ?>>
                <td class="text-center" style="width:25px;"><?= $r; ?></td>
                <td>(<?= $item->product_code; ?>) <?= $item->product_name; ?></td>
                <td class="text-center"><?= formatQuantity($item->quantity); ?></td>
                <td class="text-center"><?= formatQuantity($item->first_qty); ?></td>
                <td class="text-center"><?= formatQuantity($item->reject_qty); ?></td>
                <td class="text-center"><?= ($item->last_qty !== NULL ? formatQuantity($item->last_qty) : ''); ?></td>
                <td class="text-center"><?= formatQuantity($rest_qty); ?></td>
                <td class="text-right"><?= formatDecimal($item->price); ?></td>
                <td class="text-right"><?= formatDecimal($item->subtotal); ?></td>
                </tr>
            <?php
              $r++;
            }
          } ?>
        </tbody>
        <tfoot>
          <tr>
            <td class="text-right" colspan="8"><strong>Grand Total</strong></td>
            <td class="text-right"><strong><?= formatDecimal($grand_total); ?></strong></td>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="row">
      <div class="col-xs-12">

      </div>
      <div class="col-xs-4 pull-left">
        <?php
        $creator = $this->site->getUserByID($opname->created_by);
        ?>
        <p><?= lang('created_by'); ?>:</p>
        <p><?= $creator->fullname; ?></p>
        <p>&nbsp;</p>
        <hr>
        <p><?= lang('stamp_sign'); ?></p>
      </div>
      <div class="col-xs-4 col-xs-offset-1 pull-right">
        <?php
        $updater = $this->site->getUserByID($opname->updated_by);
        ?>
        <p><?= lang('updated_by'); ?>: </p>
        <p><?= (isset($updater) ? $updater->fullname : ''); ?></p>
        <p>&nbsp;</p>
        <hr>
        <p><?= lang('stamp_sign'); ?></p>
      </div>
    </div>
  </div>
</div>