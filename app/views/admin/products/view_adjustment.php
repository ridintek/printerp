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
          <div class="col-xs-6">
            <div class="row">
              <div class="col-xs-3 "><?= lang('date'); ?></div>
              <div class="col-xs-9">: <?= $this->sma->hrld($inv->date); ?></div>
            </div>
            <div class="row">
              <div class="col-xs-3"><?= lang('ref'); ?></div>
              <div class="col-xs-9">: <?= $inv->reference; ?></div>
            </div>
            <div class="row">
              <div class="col-xs-3">Mode</div>
              <div class="col-xs-9">: <?= lang($inv->mode); ?></div>
            </div>
          </div>
          <div class="col-xs-6 pull-right text-right order_barcodes">
            <?= $this->ridintek->qrcode(admin_url('products/view_adjustment/' . $inv->id)); ?>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped print-table order-table">
          <thead>
            <tr>
              <th><?= lang('no'); ?></th>
              <th><?= lang('code'); ?></th>
              <th><?= lang('products'); ?></th>
              <th>Entered Quantity</th>
              <th>Adjusted Quantity</th>
            </tr>
          </thead>
          <tbody>

          <?php $r = 1;
          foreach ($rows as $row):
          ?>
            <tr>
              <td style="text-align:center; vertical-align:middle;"><?= $r; ?></td>
              <td style="text-align:center"><?= $row->product_code; ?></td>
              <td style="vertical-align:middle;"><?= $row->product_name; ?></td>
              <td style="text-align:center; vertical-align:middle;"><?= floatval($row->adjustment_qty); ?></td>
              <td style="text-align:center; vertical-align:middle;"><?= floatval($row->quantity); ?></td>
            </tr>
            <?php
            $r++;
          endforeach;
          ?>
          </tbody>
        </table>
      </div>

      <div class="row">
        <div class="col-xs-7">
          <?php if ($inv->note || $inv->note != '') {
            ?>
            <div class="well well-sm">
              <p class="bold"><?= lang('note'); ?>:</p>
              <div><?= $this->sma->decode_html($inv->note); ?></div>
            </div>
          <?php
          } ?>
        </div>

        <div class="col-xs-5 pull-right">
          <div class="well well-sm">
            <p>
              <?= lang('created_by'); ?>: <?= $created_by->fullname; ?> <br>
              <?= lang('date'); ?>: <?= $this->sma->hrld($inv->date); ?>
            </p>
            <?php if ($inv->updated_by) {
            ?>
            <p>
              <?= lang('updated_by'); ?>: <?= $updated_by->fullname; ?><br>
              <?= lang('update_at'); ?>: <?= $this->sma->hrld($inv->updated_at); ?>
            </p>
            <?php
          } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
