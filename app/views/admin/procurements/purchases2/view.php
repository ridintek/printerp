<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
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
            <div class="col-md-8">: <?= $pm->created_at ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Created By</div>
            <div class="col-md-8">: <?= $this->site->getUserByID($pm->created_by)->fullname ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Updated At</div>
            <div class="col-md-8">: <?= ($pm->updated_at ? $pm->updated_at : '') ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Updated By</div>
            <div class="col-md-8">: <?= ($pm->updated_by ? $this->site->getUserByID($pm->updated_by)->fullname : '') ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">From Warehouse</div>
            <div class="col-md-8">: <?= $this->site->getWarehouseByID($pm->from_warehouse_id)->name ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">To Warehouse</div>
            <div class="col-md-8">: <?= $this->site->getWarehouseByID($pm->to_warehouse_id)->name ?></div>
          </div>
          <div class="row">
            <div class="col-md-4">Status</div>
            <div class="col-md-8">: <?= ucwords(str_replace('_', ' ', $pm->status)) ?></div>
          </div>
        </div>
        <div class="col-md-6 pull-right text-right order_barcodes">
          <?= $this->ridintek->qrcode(admin_url('products/mutations/view/' . $pm->id)); ?>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover table-striped print-table order-table">
        <thead>
          <tr>
            <th>No</th>
            <th>Code</th>
            <th>name</th>
            <th>Total Qty</th>
            <th>Received Qty</th>
            <th>Rest Qty</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $r = 1;
          foreach ($pmitems as $pmitem) :
          ?>
            <tr>
              <td style="text-align:center; vertical-align:middle;"><?= $r; ?></td>
              <td style="text-align:center"><?= $pmitem->product_code; ?></td>
              <td style="vertical-align:middle;"><?= $pmitem->product_name; ?></td>
              <td style="text-align:center; vertical-align:middle;"><?= floatval($pmitem->quantity); ?></td>
              <td style="text-align:center; vertical-align:middle;"><?= floatval($pmitem->received_qty); ?></td>
              <td style="text-align:center; vertical-align:middle;"><?= floatval($pmitem->quantity - $pmitem->received_qty); ?></td>
              <?= renderStatus($pmitem->status) ?>
            </tr>
          <?php
            $r++;
          endforeach;
          ?>
        </tbody>
      </table>
    </div>

    <div class="row">
      <div class="col-md-7">
        <?php if (!empty($pm->note)) {
        ?>
          <div class="well well-sm">
            <p class="bold"><?= lang('note'); ?>:</p>
            <div><?= $pm->note; ?></div>
          </div>
        <?php
        } ?>
      </div>
    </div>
  </div>
</div>