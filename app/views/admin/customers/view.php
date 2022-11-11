<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
        <i class="fad fa-print"></i> <?= lang('print'); ?>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel"><?= $customer->company && $customer->company != '-' ? $customer->company : $customer->name; ?></h4>
    </div>
    <div class="modal-body">
      <div class="table-responsive">
        <table class="table table-striped table-bordered" style="margin-bottom:0;">
          <tbody>
            <tr>
              <td class="col-md-3"><strong><?= lang('customer_group'); ?></strong></td>
              <td class="col-md-3"><?= $customer->customer_group_name; ?></strong></td>
              <td class="col-md-3"><strong><?= lang('price_group'); ?></strong></td>
              <td class="col-md-3"><?= $customer->price_group_name; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('company'); ?></strong></td>
              <td><?= $customer->company; ?></strong></td>
              <td><strong><?= lang('postal_code'); ?></strong></td>
              <td><?= $customer->postal_code; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('name'); ?></strong></td>
              <td><?= $customer->name; ?></strong></td>
              <td><strong><?= lang('country'); ?></strong></td>
              <td><?= $customer->country; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('email'); ?></strong></td>
              <td><?= $customer->email; ?></strong></td>
              <td><strong><?= lang('city'); ?></strong></td>
              <td><?= $customer->city; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('phone'); ?></strong></td>
              <td><?= $customer->phone; ?></strong></td>
              <td><strong><?= lang('state'); ?></strong></td>
              <td><?= $customer->state; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('address'); ?></strong></td>
              <td><?= $customer->address; ?></strong></td>
              <td><strong><?= lang('term_of_payment'); ?></strong></td>
              <td><?= $customer->payment_term; ?></strong></td>
            </tr>
            <tr>
              <td><strong><?= lang('ship_address'); ?></strong></td>
              <td><?= $customer->ship_address; ?></strong></td>
              <td><strong><?= lang('deposit'); ?></strong></td>
              <td><?= $this->sma->formatMoney($customer->deposit_amount); ?></strong></td>
            </tr>
            <tr>
              <td colspan="2"><strong><?= lang('award_points'); ?></strong></td>
              <td colspan="2"><?= $customer->award_points; ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer no-print">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?= lang('close'); ?></button>
        <?php if ($Owner || $Admin || $GP['reports-customers']) {
  ?>
          <a href="<?=admin_url('reports/customer_report/' . $customer->id); ?>" target="_blank" class="btn btn-primary"><?= lang('customers_report'); ?></a>
        <?php
} ?>
        <?php if ($Owner || $Admin || $GP['customers-edit']) {
    ?>
          <a href="<?=admin_url('customers/edit/' . $customer->id); ?>" data-toggle="modal" data-target="#myModal2" class="btn btn-primary"><?= lang('edit_customer'); ?></a>
        <?php
  } ?>
      </div>
      <div class="clearfix"></div>
    </div>
  </div>
