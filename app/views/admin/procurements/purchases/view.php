<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  if (localStorage.getItem('poitems')) {
    localStorage.removeItem('poitems');
  }
  if (localStorage.getItem('podiscount')) {
    localStorage.removeItem('podiscount');
  }
  if (localStorage.getItem('poref')) {
    localStorage.removeItem('poref');
  }
  if (localStorage.getItem('powarehouse')) {
    localStorage.removeItem('powarehouse');
  }
  if (localStorage.getItem('ponote')) {
    localStorage.removeItem('ponote');
  }
  if (localStorage.getItem('posupplier')) {
    localStorage.removeItem('posupplier');
  }
  if (localStorage.getItem('podate')) {
    localStorage.removeItem('podate');
  }
  if (localStorage.getItem('postatus')) {
    localStorage.removeItem('postatus');
  }
  if (localStorage.getItem('popayment_term')) {
    localStorage.removeItem('popayment_term');
  }
</script>
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
          <div class="col-xs-4"><?= lang('date'); ?>: <?= $this->sma->hrld($purchase->date); ?>
            <br><?= lang('ref'); ?>: <?= $purchase->reference; ?>
          </div>
          <div class="col-xs-6 pull-right text-right order_barcodes">
            <?= $this->ridintek->qrcode(admin_url('purchases/view/' . $purchase->id)); ?>
          </div>
          <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
      </div>

      <div class="row">
        <div class="col-xs-6">
          <?= lang('to'); ?>:
          <?php if (isset($supplier)) { ?>
          <h3 style="margin-top:10px;"><?= $supplier->company; ?></h3>
          <?= '<p>' . $supplier->address . '</p><p>' . $supplier->phone . '<br>' . $supplier->email . '</p>'; ?>
          <?php } else { ?>
          <h3 style="margin-top:10px;">Please edit PO and add Supplier</h3>
          <?php } ?>
        </div>
        <div class="col-xs-6">
          <?= lang('from'); ?>:<br/>
          <h3 style="margin-top:10px;"><?= $warehouse->name . ' ( ' . $warehouse->code . ' )'; ?></h3>
          <?= '<p>' . $warehouse->address . '</p><p>' . $warehouse->phone . '<br>' . $warehouse->email . '</p>'; ?>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped order-table">
          <thead>
            <tr>
              <th style="text-align:center; vertical-align:middle;"><?= lang('no.'); ?></th>
              <th style="vertical-align:middle;"><?= lang('description'); ?></th>
              <th style="vertical-align:middle;"><?= lang('spec'); ?></th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('purchased_quantity'); ?></th>
              <?php if ($Owner || $Admin || $this->session->userdata('view_right')) { ?>
              <th style="text-align:center; vertical-align:middle;"><?= lang('received_qty'); ?></th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('unit_cost'); ?></th>
              <th style="text-align:center; vertical-align:middle;"><?= lang('subtotal'); ?></th>
              <?php } ?>
            </tr>
          </thead>

          <tbody>
          <?php
          $grand_total = 0;
          $r = 1;
          if ( ! empty($rows)) {
            foreach ($rows as $row) {
              $total = $row->cost * $row->purchased_qty;
              $grand_total += $total;
          ?>
            <tr>
              <td class="text-center" style="width:25px;"><?= $r; ?></td>
              <td class="text-left">
                <?= $row->product_code . ' - ' . $row->product_name; ?>
              </td>
              <td class="col-md-4"><?= ( ! empty($row->spec) ? $row->spec : '-'); ?></td>
              <td class="text-center" style="width:80px; "><?= ceil($row->purchased_qty) . ' ' . $row->unit_code; ?></td>
              <?php if ($Owner || $Admin || $this->session->userdata('view_right')) { ?>
              <td class="text-right"><?= ceil($row->quantity) . ' ' . $row->unit_code; ?></td>
              <td class="text-right"><?= formatCurrency($row->cost); ?></td>
              <td class="text-right"><?= formatCurrency($total); ?></td>
              <?php } ?>
            </tr>
            <?php
              $r++;
            }
          } ?>
          </tbody>
          <?php if ($Owner || $Admin || $this->session->userdata('view_right')) { ?>
          <tfoot>
            <tr>
              <td class="text-right" colspan="6"><strong>Grand Total</strong></td>
              <td class="text-right"><strong><?= formatCurrency($grand_total); ?></strong></td>
            </tr>
          </tfoot>
          <?php } ?>
        </table>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <?php if ($purchase->note || $purchase->note != '') {
              ?>
          <div class="well well-sm">
            <p class="bold"><?= lang('note'); ?>:</p>

            <div><?= $this->sma->decode_html($purchase->note); ?></div>
          </div>
          <?php
            } ?>
        </div>
        <div class="col-xs-4 pull-left">
          <p><?= lang('created_by'); ?>: <?= $created_by->first_name . ' ' . $created_by->last_name; ?> </p>
          <?php
          if ($updated_by) {
            echo '<p>' . lang('updated_by') . ': ' . $updated_by->first_name . ' ' . $updated_by->last_name . ' </p>';
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
          <?php if ($purchase->attachment) { ?>
          <div class="btn-group">
            <a href="<?= admin_url('gallery/view?name=' . $purchase->attachment . '&path=purchases'); ?>" class="tip btn btn-primary" data-toggle="modal" data-target="#myModal3" title="<?= lang('attachment') ?>">
              <i class="fad fa-link"></i>
              <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
            </a>
          </div>
          <?php } ?>
          <div class="btn-group">
            <a href="<?= admin_url('purchases/email/' . $purchase->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
              <i class="fad fa-envelope"></i>
              <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="<?= admin_url('purchases/pdf/' . $purchase->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
              <i class="fad fa-download"></i>
              <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="<?= admin_url('procurements/purchases/edit/' . $purchase->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
              <i class="fad fa-edit"></i>
              <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
            </a>
          </div>
          <div class="btn-group">
            <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line('delete') ?></b>"
              data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('purchases/delete/' . $purchase->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>"
              data-html="true" data-placement="top">
              <i class="fad fa-trash-alt"></i>
              <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
            </a>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
<script type="text/javascript">
  $(document).ready( function() {
    $('.tip').tooltip();
  });
</script>
