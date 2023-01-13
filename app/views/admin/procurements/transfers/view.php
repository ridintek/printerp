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
      <?php //if ($logo) { ?>
      <div class="text-center" style="margin-bottom:20px;">
        <img src="<?= base_url() . 'assets/uploads/logos/logo-lucretia.png'; ?>"
        alt="<?= $Settings->site_name; ?>">
      </div>
      <?php //} ?>
      <div class="well well-sm">
        <div class="row bold">
          <div class="col-xs-4"><?= lang('date'); ?>: <?= $this->sma->hrld($transfer->date); ?>
            <br><?= lang('ref'); ?>: <?= $transfer->reference; ?>
          </div>
          <div class="col-xs-6 pull-right text-right order_barcodes">
            <?= $this->ridintek->qrcode(admin_url('transfers/view/' . $transfer->id)); ?>
          </div>
          <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
      </div>

      <div class="row">
        <div class="col-xs-6">
          <?= lang('to'); ?>:<br/>
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
              <th style="text-align:center; vertical-align:middle;"><?= lang('no.'); ?></th>
              <th style="vertical-align:middle;"><?= lang('description'); ?></th>
              <th style="vertical-align:middle;"><?= lang('spec'); ?></th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('quantity'); ?></th>
              <?php if ($Owner || $Admin || $show_price) { ?>
              <th style="text-align:center; vertical-align:middle;"><?= lang('unit_cost'); ?></th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('subtotal'); ?></th>
              <?php } ?>
            </tr>
          </thead>

          <tbody>
          <?php $r = 1;
          if ( ! empty($rows)) {
            $grand_total = 0;
            foreach ($rows as $row) {
              $total = $row->price * $row->quantity;
              $grand_total += $total;
          ?>
            <tr>
              <td class="text-center" style="width:25px;"><?= $r; ?></td>
              <td class="text-left">
                <?= $row->product_code . ' - ' . $row->product_name; ?>
              </td>
              <td class="col-md-4"><?= ( ! empty($row->spec) ? $row->spec : '-'); ?></td>
              <td class="text-center" style="width:80px; "><?= floatval($row->quantity) . ' ' . $row->unit_code; ?></td>
              <?php if ($Owner || $Admin || $show_price) { ?>
              <td class="text-right"><?= formatQuantity($row->price); ?></td>
              <td class="text-right"><?= formatQuantity($total); ?></td>
              <?php } ?>
            </tr>
            <?php
              $r++;
            }
          } ?>
          </tbody>
          <?php if ($Owner || $Admin || $show_price) { ?>
          <tfoot>
            <tr>
              <td class="text-right" colspan="5"><strong>Grand Total</strong></td>
              <td class="text-right"><strong><?= formatCurrency($grand_total); ?></strong></td>
            </tr>
          </tfoot>
          <?php } ?>
        </table>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <?php if ($transfer->note || $transfer->note != '') {
              ?>
          <div class="well well-sm">
            <p class="bold"><?= lang('note'); ?>:</p>

            <div><?= $this->sma->decode_html($transfer->note); ?></div>
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
          <?php if ($transfer->attachment) { ?>
          <div class="btn-group">
            <a href="<?= admin_url('welcome/download/' . $transfer->attachment) ?>" class="tip btn btn-primary" title="<?= lang('attachment') ?>">
              <i class="fad fa-chain"></i>
              <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
            </a>
          </div>
          <?php } ?>
          <div class="btn-group">
            <a href="<?= admin_url('transfers/email/' . $transfer->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
              <i class="fad fa-envelope-o"></i>
              <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="<?= admin_url('transfers/pdf/' . $transfer->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
              <i class="fad fa-download"></i>
              <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="<?= admin_url('transfers/edit/' . $transfer->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
              <i class="fad fa-edit"></i>
              <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line('delete') ?></b>"
              data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('transfers/delete/' . $transfer->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>"
              data-html="true" data-placement="top">
              <i class="fad fa-trash-o"></i>
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
  $(document).ready( function() {
    $('.tip').tooltip();
  });
</script>
