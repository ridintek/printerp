<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style type="text/css">
  @media print {
    #myModal .modal-content {
      display: none !important;
    }
  }
</style>
<div class="modal-dialog modal-lg no-modal-header">
  <div class="modal-content">
    <div class="modal-body print">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <div class="clearfix"></div>
      <div class="row padding10">
        <?php if (isset($customer)) { ?>
          <div class="col-xs-5">
            <?php echo $this->lang->line('to'); ?>:<br />
            <h2 class=""><?= $customer->company ? $customer->company : $customer->name; ?></h2>
            <?= $customer->company ? '' : 'Attn: ' . $customer->name ?>
            <?php
            echo $customer->address . '<br />' . $customer->city . ' ' . $customer->postal_code . ' ' . $customer->state . '<br />' .
              $customer->country . '<br>';
            echo lang('tel') . ': ' . $customer->phone . '<br />' . lang('email') . ': ' . $customer->email;
            ?>
          </div>
        <?php } ?>

        <?php if (isset($biller)) { ?>
          <div class="col-xs-5">
            <?php echo $this->lang->line('from'); ?>:<br />
            <h2 class=""><?= $biller->company != '-' ? $biller->company : $biller->name; ?></h2>
            <?= $biller->company ? '' : 'Attn: ' . $biller->name ?>
            <?php
            echo $biller->address . '<br />' . $biller->city . ' ' . $biller->postal_code . ' ' . $biller->state . '<br />' .
              $biller->country . '<br>';
            echo lang('tel') . ': ' . $biller->phone . '<br />' . lang('email') . ': ' . $biller->email;
            ?>
            <div class="clearfix"></div>
          </div>
        <?php } ?>

        <?php if (isset($warehouse_from) && isset($warehouse_to)) { ?>
          <div class="col-xs-5">
            <?php echo $this->lang->line('to'); ?>:<br />
            <h2 class=""><?= $warehouse_to->name; ?></h2>
            <?php
            echo $warehouse_to->address . '<br />';
            echo lang('tel') . ': ' . $warehouse_to->phone . '<br />' . lang('email') . ': ' . $warehouse_to->email;
            ?>
            <div class="clearfix"></div>
          </div>

          <div class="col-xs-5">
            <?php echo $this->lang->line('from'); ?>:<br />
            <h2 class=""><?= $warehouse_from->name; ?></h2>
            <?php
            echo $warehouse_from->address . '<br />';
            echo lang('tel') . ': ' . $warehouse_from->phone . '<br />' . lang('email') . ': ' . $warehouse_from->email;
            ?>
            <div class="clearfix"></div>
          </div>
        <?php } ?>

      </div>
      <hr>
      <div class="row">
        <div class="col-sm-12">
          <p style="font-weight:bold;"><?= lang('date'); ?>: <?= $this->sma->hrsd($payment->date); ?></p>
          <p style="font-weight:bold;"><?= lang('reference'); ?>: <?= $payment->reference; ?></p>
        </div>
      </div>
      <div class="well well-sm">
        <table class="table table-borderless" style="margin-bottom:0;">
          <tbody>
            <tr>
              <td>
                <strong><?= lang('payment_received'); ?></strong>
              </td>
              <td class="text-right">
                <strong class="text-right"><?php echo $this->sma->formatMoney($payment->amount); ?></strong>
              </td>
            </tr>
            <tr>
              <td><strong><?= lang('paid_by'); ?></strong></td>
              <td class="text-right"><strong class="text-right"><?= $payment->method; ?></strong></td>
            </tr>

            <tr>
              <td colspan="2"><?= html_entity_decode($payment->note); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div style="clear: both;"></div>
      <div class="row">
        <div class="col-sm-4 pull-left">
          <p>&nbsp;</p>

          <p>&nbsp;</p>

          <p>&nbsp;</p>

          <p style="border-bottom: 1px solid #666;">&nbsp;</p>

          <p><?= lang('stamp_sign'); ?></p>
        </div>
      </div>
      <div class="clearfix"></div>
    </div>
  </div>
</div>