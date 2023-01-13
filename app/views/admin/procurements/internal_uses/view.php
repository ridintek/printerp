<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-lg no-modal-header">
  <div class="modal-content">
    <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
        <i class="fad fa-print"></i> <?= lang('print'); ?>
      </button>
      <?php //if ($logo) { 
      ?>
      <div class="text-center" style="margin-bottom:20px;">
        <img src="<?= base_url() . 'assets/uploads/logos/logo-lucretia.png'; ?>" alt="<?= $Settings->site_name; ?>">
      </div>
      <?php //} 
      ?>
      <div class="well well-sm">
        <div class="row bold">
          <div class="col-xs-6">
            <div class="row">
              <div class="col-md-2">Reference</div>
              <div class="col-md-6">: <?= $internal_use->reference; ?></div>
            </div>
            <div class="row">
              <div class="col-md-2">Date</div>
              <div class="col-md-6">: <?= $internal_use->date; ?></div>
            </div>
            <div class="row">
              <div class="col-md-2">Biller</div>
              <div class="col-md-6">: <?= ($internal_use->biller_id ? $this->site->getBillerByID($internal_use->biller_id)->name : ''); ?></div>
            </div>
            <?php if ($internal_use->supplier_id) : ?>
              <div class="row"><?php $supplier = Supplier::getRow(['id' => $internal_use->supplier_id]); ?>
                <div class="col-md-2">Supplier</div>
                <div class="col-md-8">: <?= $supplier->name . (!empty($supplier->company) ? " ({$supplier->company})" : '') ?></div>
              </div>
            <?php endif; ?>
            <?php if ($internal_use->ts_id) : ?>
              <div class="row">
                <div class="col-md-2">Support</div>
                <div class="col-md-6">: <?= User::getRow(['id' => $internal_use->ts_id])->fullname ?></div>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-xs-6 pull-right text-right order_barcodes">
            <?= $this->ridintek->qrcode(admin_url('procurements/internal_uses/view/' . $internal_use->id)); ?>
          </div>
          <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
      </div>

      <div class="row">
        <div class="col-xs-6">
          <?= lang('to'); ?>:<br />
          <h3 style="margin-top:10px;"><?= $to_warehouse->name . ' ( ' . $to_warehouse->code . ' )'; ?></h3>
          <?= '<p>' . $to_warehouse->address . '</p><p>' . $to_warehouse->phone . '<br>' . $to_warehouse->email . '</p>';
          ?>
        </div>
        <div class="col-xs-6">
          <?= lang('from'); ?>:
          <h3 style="margin-top:10px;"><?= $from_warehouse->name . ' ( ' . $from_warehouse->code . ' )'; ?></h3>
          <?= '<p>' . $from_warehouse->address . '</p><p>' . $from_warehouse->phone . '<br>' . $from_warehouse->email . '</p>';
          ?>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped order-table">
          <thead>
            <tr>
              <th style="text-align:center; vertical-align:middle;">No</th>
              <th style="vertical-align:middle;">Description</th>
              <th style="vertical-align:middle;">Machine</th>
              <th style="vertical-align:middle;">Unique Code</th>
              <th style="vertical-align:middle;">Unique Code Replacement</th>
              <th style="vertical-align:middle;">Counter</th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('quantity'); ?></th>
              <?php if ($Owner || $Admin) { ?>
                <th style="text-align:center; vertical-align:middle;"><?= lang('unit_cost'); ?></th>
                <th style="text-align:center; vertical-align:middle;"><?= lang('subtotal'); ?></th>
              <?php } ?>
            </tr>
          </thead>

          <tbody>
            <?php
            $r = 1;
            $grandTotal = 0;

            if (!empty($iuseItems)) {
              foreach ($iuseItems as $iuseItem) {
                $product = $this->site->getProductByID($iuseItem->product_id);
                $machine = $this->site->getMachineByID($iuseItem->machine_id);
                $price = $iuseItem->price;
                $total = ($price * $iuseItem->quantity);
                $grandTotal += $total;
            ?>
                <tr>
                  <td class="text-center" style="width:25px;"><?= $r; ?></td>
                  <td class="text-left">
                    <?= $iuseItem->product_code . ' - ' . $iuseItem->product_name; ?>
                  </td>
                  <td class="text-center" style="width:25px;"><?= ($machine ? $machine->name : 'All Machines'); ?></td>
                  <td class="text-center"><?= (!empty($iuseItem->unique_code) ? $iuseItem->unique_code : '-') ?></td>
                  <td class="text-center"><?= (!empty($iuseItem->ucr) ? $iuseItem->ucr : '-') ?></td>
                  <td class="text-right"><?= (!empty($iuseItem->spec) ? $iuseItem->spec : '-'); ?></td>
                  <td class="text-center" style="width:80px; "><?= formatStock($iuseItem->quantity) . ' ' . $iuseItem->unit_code; ?></td>
                  <?php if ($Owner || $Admin) { ?>
                    <td class="text-right"><?= formatCurrency($price); ?></td>
                    <td class="text-right"><?= formatCurrency($total); ?></td>
                  <?php } ?>
                </tr>
            <?php
                $r++;
              }
            } ?>
          </tbody>
          <?php if ($Owner || $Admin) { ?>
            <tfoot>
              <tr>
                <td class="text-right" colspan="8"><strong>Grand Total</strong></td>
                <td class="text-right"><strong><?= formatCurrency($grandTotal); ?></strong></td>
              </tr>
            </tfoot>
          <?php } ?>
        </table>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <?php if ($internal_use->note || $internal_use->note != '') {
          ?>
            <div class="well well-sm">
              <p class="bold"><?= lang('note'); ?>:</p>

              <div><?= $this->sma->decode_html($internal_use->note); ?></div>
            </div>
          <?php
          } ?>
        </div>
        <div class="col-xs-4 pull-left">
          <p><?= lang('created_by'); ?>: <?= $created_by->fullname; ?> </p>
          <?php
          if ($updated_by) {
            echo '<p>' . lang('updated_by') . ': ' . $updated_by->fullname . ' </p>';
          } else {
            echo '<p>&nbsp;</p>';
          } ?>
          <p>&nbsp;</p>
          <hr>
          <p><?= lang('stamp_sign'); ?></p>
        </div>
        <div class="col-xs-4 col-xs-offset-1 pull-right">
          <p><?= lang('received_by'); ?>: </p>
          <p>&nbsp;</p>
          <p>&nbsp;</p>
          <hr>
          <p><?= lang('stamp_sign'); ?></p>
        </div>
      </div>
      <?php if (!$Supplier || !$Customer) { ?>
        <div class="buttons">
          <div class="btn-group btn-group-justified">
            <?php if ($internal_use->attachment_id) { ?>
              <div class="btn-group">
                <a href="<?= admin_url("gallery/attachment/{$internal_use->attachment_id}/?modal=1") ?>" class="tip btn btn-primary" data-toggle="modal" data-target="#myModal3" title="<?= lang('attachment') ?>">
                  <i class="fad fa-chain"></i>
                  <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
                </a>
              </div>
            <?php } ?>
            <div class="btn-group">
              <a href="<?= admin_url('transfers/email/' . $internal_use->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
                <i class="fad fa-envelope"></i>
                <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="<?= admin_url('transfers/pdf/' . $internal_use->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
                <i class="fad fa-download"></i>
                <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="<?= admin_url('transfers/edit/' . $internal_use->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
                <i class="fad fa-edit"></i>
                <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line('delete') ?></b>" data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('transfers/delete/' . $internal_use->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>" data-html="true" data-placement="top">
                <i class="fad fa-trash"></i>
                <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
              </a>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
<script type="text/javascript">
  $(document).ready(function() {
    $('.tip').tooltip();
  });
</script>