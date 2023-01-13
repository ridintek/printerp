<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-lg no-modal-header">
  <div class="modal-content">
    <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-2x">&times;</i>
      </button>
      <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
        <i class="fa fa-print"></i> <?= lang('print'); ?>
      </button>
      <?php if ( ! empty($logo) && $logo) { ?>
      <div class="text-center" style="margin-bottom:20px;">
        <img src="<?= base_url() . 'assets/uploads/logos/logo-lucretia.png'; ?>"
           alt="<?= $Settings->site_name; ?>">
      </div>
      <?php } ?>
      <div class="row" style="margin-bottom:10px">
        <div class="col-xs-12 text-right">
          <h2>PURCHASE ORDER</h2>
        </div>
      </div>
      <hr>
      <div class="well well-sm">
        <div class="row bold">
          <div class="col-xs-6">
            <h2 style="margin-top: 5px;"><?= $warehouse->name; ?></h2>
            <?php
              echo $warehouse->address . '<br>';
              echo (lang('tel') . ': ' . $warehouse->phone . '<br>');
              echo (lang('email') . ': ' . $warehouse->email);
            ?>
          </div>
          <div class="col-xs-6">
            <table>
              <tbody class="table-sub-print">
                <tr>
                  <td><?= lang('date'); ?></td>
                  <td>: <?= $this->sma->hrld($inv->date); ?></td>
                </tr>
                <tr>
                  <td><?= lang('purchase_no'); ?></td>
                  <td>: <?= $inv->reference; ?></td>
                </tr>
                <tr>
                  <td><?= lang('status'); ?></td>
                  <td>: <?= lang($inv->status); ?></td>
                </tr>
                <tr>
                  <td><?= lang('payment_status'); ?></td>
                  <td>: <?= lang($inv->payment_status); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="clearfix"></div>
        </div>
        <div class="clearfix"></div>
      </div>

      <div class="row" style="margin-bottom:15px;">
        <!-- VENDOR -->
        <div class="col-xs-6">
          <div style="border: 1px solid black">
            <div class="invoice-head" style="padding: 5px">
              <strong><?php echo $this->lang->line('vendor'); ?>:</strong>
            </div>
            <div style="padding: 5px">
              <h2 style="margin-top:10px;"><?= $supplier->company && $supplier->company != '-' ? $supplier->company : $supplier->name; ?></h2>
              <?php
                echo $supplier->address . '<br />' . $supplier->city . ' ' . $supplier->postal_code . ' ' . $supplier->state . '<br />' . $supplier->country;
                echo lang('tel') . ': ' . $supplier->phone . '<br />' . lang('email') . ': ' . $supplier->email;
              ?>
            </div>
          </div>
        </div>
        <!-- SHIP TO -->
        <div class="col-xs-6">
          <div style="border: 1px solid black">
            <div class="invoice-head" style="padding: 5px">
              <strong><?php echo $this->lang->line('ship_to'); ?>:</strong>
            </div>
            <div style="padding: 5px">
              <h2 style="margin-top:10px;"><?= $warehouse->name; ?></h2>
              <?php
                echo $warehouse->address . 'Indonesia<br><br>';
                echo (lang('tel') . ': ' . $warehouse->phone . '<br>');
                echo (lang('email') . ': ' . $warehouse->email);
              ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12">
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped print-table order-table">
              <thead>
                <tr>
                  <th><?= lang('no.'); ?></th>
                  <th><?= lang('description'); ?></th>
                  <th><?= lang('quantity'); ?></th>
                  <?php
                    if ($inv->status == 'partial') {
                      echo '<th>' . lang('received') . '</th>';
                    }
                  ?>
                  <th><?= lang('unit_cost'); ?></th>
                  <th><?= lang('subtotal'); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php
              $r = 1;

              foreach ($rows as $row):
              ?>
                <tr>
                  <td style="text-align:center; width:40px; vertical-align:middle;"><?= $r; ?></td>
                  <td style="vertical-align:middle;">
                    <?= $row->product_code . ' - ' . $row->product_name . ($row->variant ? ' (' . $row->variant . ')' : ''); ?>
                    <?= $row->second_name ? '<br>' . $row->second_name : ''; ?>
                    <?= $row->supplier_part_no ? '<br>' . lang('supplier_part_no') . ': ' . $row->supplier_part_no : ''; ?>
                    <?= $row->details ? '<br>' . $row->details : ''; ?>
                    <?= ($row->expiry && $row->expiry != '0000-00-00') ? '<br>' . lang('expiry') . ': ' . $this->sma->hrsd($row->expiry) : ''; ?>
                  </td>
                  <td style="width: 80px; text-align:center; vertical-align:middle;"><?= ceil($row->unit_quantity) . ' ' . $row->product_unit_code; ?></td>
                  <?php
                  if ($inv->status == 'partial') {
                    echo '<td style="text-align:center;vertical-align:middle;width:80px;">' . ceil($row->quantity_received) . ' ' . $row->product_unit_code . '</td>';
                  }
                  ?>
                  <td style="text-align:right; width:100px;">
                    <?= $row->unit_cost != $row->real_unit_cost && $row->item_discount > 0 ? '<del>' . $this->sma->formatMoney($row->real_unit_cost) . '</del>' : ''; ?>
                    <?= $this->sma->formatMoney($row->unit_cost); ?>
                  </td>
                  <?php
                  if ($Settings->product_discount && $inv->product_discount != 0) {
                    echo '<td style="width: 100px; text-align:right; vertical-align:middle;">' . ($row->discount != 0 ? '<small>(' . $row->discount . ')</small> ' : '') . $this->sma->formatMoney($row->item_discount) . '</td>';
                  }
                  ?>
                  <td style="text-align:right; width:120px;"><?= $this->sma->formatMoney($row->subtotal); ?></td>
                </tr>
                <?php
                $r++;
              endforeach;
              ?>
              </tbody>
              <tfoot>
              <?php
              $col = 4;
              if ($inv->status == 'partial') {
                $col++;
              }
              if ($Settings->product_discount && $inv->product_discount != 0) {
                $col++;
              }
              if ($Settings->product_discount && $inv->product_discount != 0) {
                $tcol = $col - 2;
              } elseif ($Settings->product_discount && $inv->product_discount != 0) {
                $tcol = $col - 1;
              } else {
                $tcol = $col;
              }
              ?>
              <?php if ($inv->grand_total != $inv->total) {
                ?>
                <tr>
                  <td colspan="<?= $tcol; ?>" class="text-right"><?= lang('total'); ?>
                    (<?= $default_currency->code; ?>)
                  </td>
                  <?php
                  if ($Settings->tax1 && $inv->product_tax > 0) {
                    echo '<td class="text-right">' . $this->sma->formatMoney($return_purchase ? ($inv->product_tax + $return_purchase->product_tax) : $inv->product_tax) . '</td>';
                  }
                if ($Settings->product_discount && $inv->product_discount != 0) {
                  echo '<td class="text-right">' . $this->sma->formatMoney($return_purchase ? ($inv->product_discount + $return_purchase->product_discount) : $inv->product_discount) . '</td>';
                } ?>
                  <td class="text-right"><?= $this->sma->formatMoney($return_purchase ? (($inv->total + $inv->product_tax) + ($return_purchase->total + $return_purchase->product_tax)) : ($inv->total + $inv->product_tax)); ?></td>
                </tr>
              <?php
              } ?>
              <?php
              if ($return_purchase) {
                echo '<tr><td colspan="' . $col . '" class="text-right">' . lang('return_total') . ' (' . $default_currency->code . ')</td><td class="text-right">' . $this->sma->formatMoney($return_purchase->grand_total) . '</td></tr>';
              }
              if ($inv->surcharge != 0) {
                echo '<tr><td colspan="' . $col . '" class="text-right">' . lang('return_surcharge') . ' (' . $default_currency->code . ')</td><td class="text-right">' . $this->sma->formatMoney($inv->surcharge) . '</td></tr>';
              }
              ?>

              <?php if ($inv->order_discount != 0) {
                echo '<tr><td colspan="' . $col . '" class="text-right">' . lang('order_discount') . ' (' . $default_currency->code . ')</td><td class="text-right">' . ($inv->order_discount_id ? '<small>(' . $inv->order_discount_id . ')</small> ' : '') . $this->sma->formatMoney($return_purchase ? ($inv->order_discount + $return_purchase->order_discount) : $inv->order_discount) . '</td></tr>';
              }
              ?>
              <?php if ($Settings->tax2 && $inv->order_tax != 0) {
                echo '<tr><td colspan="' . $col . '" class="text-right">' . lang('order_tax') . ' (' . $default_currency->code . ')</td><td class="text-right">' . $this->sma->formatMoney($return_purchase ? ($inv->order_tax + $return_purchase->order_tax) : $inv->order_tax) . '</td></tr>';
              }
              ?>
              <?php if ($inv->shipping != 0) {
                echo '<tr><td colspan="' . $col . '" class="text-right">' . lang('shipping') . ' (' . $default_currency->code . ')</td><td class="text-right">' . $this->sma->formatMoney($inv->shipping) . '</td></tr>';
              }
              ?>
              <tr>
                <td colspan="<?= $col; ?>"
                  style="text-align:right; font-weight:bold;"><?= lang('total_amount'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney($return_purchase ? ($inv->grand_total + $return_purchase->grand_total) : $inv->grand_total); ?></td>
              </tr>
              <tr>
                <td colspan="<?= $col; ?>"
                  style="text-align:right; font-weight:bold;"><?= lang('paid'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney($return_purchase ? ($inv->paid + $return_purchase->paid) : $inv->paid); ?></td>
              </tr>
              <tr>
                <td colspan="<?= $col; ?>"
                  style="text-align:right; font-weight:bold;"><?= lang('balance'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"><?= $this->sma->formatMoney(($return_purchase ? ($inv->grand_total + $return_purchase->grand_total) : $inv->grand_total) - ($return_purchase ? ($inv->paid + $return_purchase->paid) : $inv->paid)); ?></td>
              </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <?= $Settings->invoice_view > 0 ? $this->gst->summary($rows, $return_rows, ($return_purchase ? $inv->product_tax + $return_purchase->product_tax : $inv->product_tax), true) : ''; ?>

      <div class="row">
        <div class="col-xs-12">
          <?php
            if ($inv->note || $inv->note != '') {
              ?>
              <div class="well well-sm">
                <p class="bold"><?= lang('note'); ?>:</p>
                <div><?= $this->sma->decode_html($inv->note); ?></div>
              </div>
            <?php
            }
            ?>
        </div>

        <div class="col-xs-2">
          <div class="well well-sm">
            <?= $this->sma->qrcode('link', urlencode(admin_url('procurements/purchases/view/' . $inv->id)), 2); ?>
          </div>
        </div>

        <div class="col-xs-5 pull-right">
          <table class="table-sub-print">
            <tbody>
              <tr>
                <td><?= lang('created_by'); ?></td>
                <td>: <?= $created_by->fullnam; ?></td>
              </tr>
              <tr>
                <td><?= lang('date'); ?></td>
                <td>: <?= $this->sma->hrld($inv->date); ?></td>
              </tr>
              <?php if ($inv->updated_by) { ?>
              <tr>
                <td><?= lang('created_by'); ?></td>
                <td>: <?= $created_by->fullname ?></td>
              </tr>
              <tr>
                <td><?= lang('update_at'); ?></td>
                <td>: <?= $this->sma->hrld($inv->updated_at); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php if (!$Supplier || !$Customer) {
              ?>
        <div class="buttons">
          <?php if ($inv->attachment) {
                ?>
            <div class="btn-group">
              <a href="<?= admin_url('welcome/download/' . $inv->attachment) ?>" class="tip btn btn-primary" title="<?= lang('attachment') ?>">
                <i class="fa fa-chain"></i>
                <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
              </a>
            </div>
          <?php
              } ?>
          <div class="btn-group btn-group-justified">
            <div class="btn-group">
              <a href="<?= admin_url('procurements/purchases/add_payment/' . $inv->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('add_payment') ?>">
                <i class="fa fa-dollar"></i>
                <span class="hidden-sm hidden-xs"><?= lang('add_payment') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="<?= admin_url('procurements/purchases/email/' . $inv->id) ?>" data-toggle="modal" data-target="#myModal2" class="tip btn btn-primary" title="<?= lang('email') ?>">
                <i class="fa fa-envelope-o"></i>
                <span class="hidden-sm hidden-xs"><?= lang('email') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="<?= admin_url('procurements/purchases/pdf/' . $inv->id) ?>" class="tip btn btn-primary" title="<?= lang('download_pdf') ?>">
                <i class="fa fa-download"></i>
                <span class="hidden-sm hidden-xs"><?= lang('pdf') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="<?= admin_url('procurements/procurements/purchases/edit/' . $inv->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
                <i class="fa fa-edit"></i>
                <span class="hidden-sm hidden-xs"><?= lang('edit') ?></span>
              </a>
            </div>
            <div class="btn-group">
              <a href="#" class="tip btn btn-danger bpo" title="<b><?= $this->lang->line('delete') ?></b>"
                data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('procurements/purchases/delete/' . $inv->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>"
                data-html="true" data-placement="top">
                <i class="fa fa-trash-alt"></i>
                <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
              </a>
            </div>
          </div>
        </div>
      <?php
            } ?>
    </div>
  </div>
</div>
<script type="text/javascript">
  $(document).ready( function() {
    $('.tip').tooltip();
  });
</script>
