<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- SALES INVOICE -->
<div class="modal-content">
  <div class="modal-body">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" title="Print this Invoice" onclick="window.print();">
      <i class="fad fa-print"></i> <?= lang('print'); ?>
    </button>
    <div class="row" style="margin-bottom:10px">
      <div class="col-xs-12 text-right">
        <h2><?= ($Owner ? $inv->id . ' ': ''); ?>SURAT JALAN</h2>
      </div>
    </div>
    <hr>

    <div class="well well-sm">
      <div class="row">
        <div class="col-xs-6">
          <div style="padding: 5px">
            <h2 style="margin-top:0px;"><?= $biller->company && $biller->company != '-' ? $biller->company : $biller->name; ?></h2>
            <?php
            echo $biller->address . ', ' . $biller->city . ' ' . $biller->postal_code . ' ' . $biller->state  . (!empty($biller->country) ? ', ' . $biller->country : '') . '<br>';

            echo lang('tel') . ': ' . $biller->phone . '<br>' .
              (!empty($biller->json_data) ? lang('whatsapp') . ': ' . json_decode($biller->json_data)->whatsapp : '') . '<br>' .
              lang('email') . ': ' . $biller->email;
            ?>
          </div>
        </div>
        <div class="col-xs-4">
          <p class="bold">
            <?= lang('invoice'); ?>: <?= $inv->reference; ?><br>
            <?= lang('date'); ?>: <?= $this->sma->hrld($inv->date); ?><br>
            <?= lang('sale_status'); ?>: <?= lang($inv->status); ?><br>
            <?= lang('payment_method'); ?>:
            <?php
            if (!empty($payments)) {
              $x = 0;
              foreach ($payments as $pay) {
                echo ((($x % 2) == 1 ? ', ' : '') . $pay->method);
                $x++;
              }
            }
            ?>
            <br>
            <?= lang('payment_status'); ?>: <?= lang($inv->payment_status); ?><br>
            <?= (isset($saleJS->by_w2p) && $saleJS->by_w2p ? 'Source: Web2Print' : ''); ?>
          </p>
        </div>
        <div class="col-xs-2 text-right order_barcodes">
          <!--<img src="<?= admin_url('misc/barcode/' . $this->sma->base64url_encode($inv->reference) . '/code128/74/0/1'); ?>" alt="<?= $inv->reference; ?>" class="bcimg" />-->
          <div style="font-size: 11px; padding-right: 5px">Scan to Track Order</div>
          <?= $this->ridintek->qrcode("https://indoprinting.co.id/trackorder?inv={$inv->reference}&phone={$customer->phone}&submit=1"); ?><br>
          <div>
            <input id="track_url" type="text" style="display:none" value="<?= "https://indoprinting.co.id/trackorder?inv={$inv->reference}&phone={$customer->phone}&submit=1"; ?>">
            <button id="copy_url" type="button" title="Click to copy link or Double Click to open in a new tab" class="btn btn-xs btn-default no-print tip" style="margin-right:10px;">
              <i class="fad fa-copy"></i> <?= lang('copy_url'); ?>
            </button>
            <button id="view_details" type="button" title="View sale details" class="btn btn-xs btn-default no-print tip" style="margin-right:10px;"><i class="fad fa-history"></i> View Details</button>
          </div>
        </div>
        <div class="clearfix"></div>
      </div>
      <div class="clearfix"></div>
    </div>
    <div class="row" style="margin-bottom:15px;">
      <!-- BILL TO -->
      <div class="col-xs-6">
        <div style="border: 1px solid black">
          <div class="invoice-head" style="padding: 5px">
            <strong><?php echo $this->lang->line('bill_to'); ?>:</strong>
          </div>
          <div style="padding: 5px">
            <h2 style="margin-top:10px;"><?= $customer->company && $customer->company != '-' ? $customer->name . ' (' . $customer->company . ')' : $customer->name; ?></h2>
            <?php
            echo $customer->address . '<br>' . $customer->city . ' ' . $customer->postal_code . ' ' . $customer->state . '<br>' . $customer->country;
            echo lang('tel') . ': ' . $customer->phone . '<br>' . lang('email') . ': ' . $customer->email;
            ?>
          </div>
        </div>
      </div>
      <!-- FROM -->
      <div class="col-xs-6">
        <div style="border: 1px solid black">
          <div class="invoice-head" style="padding: 5px">
            <strong><?php echo $this->lang->line('ship_to'); ?>:</strong>
          </div>
          <div style="padding: 5px">
            <h2 style="margin-top:10px;"><?= $customer->company && $customer->company != '-' ? $customer->name . ' (' . $customer->company . ')' : $customer->name; ?></h2>
            <?php
            echo $customer->address . '<br>' . $customer->city . ' ' . $customer->postal_code . ' ' . $customer->state . '<br>' . $customer->country;
            echo lang('tel') . ': ' . $customer->phone . '<br>' . lang('email') . ': ' . $customer->email;
            ?>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive" style="margin-bottom:15px;">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th class="col-xs-3"><?= lang('sales'); ?></th>
                <th class="col-xs-2">No. PO</th>
                <th class="col-xs-3"><?= lang('note'); ?></th>
                <th class="col-xs-2"><?= lang('payment_due_date'); ?></th>
                <th class="col-xs-2">Est. Complete Date</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="text-center"><?= $inv->created_by ? $created_by->fullname : $customer->name; ?></td>
                <td class="text-center"><?= $inv->no_po; ?></td>
                <td class="text-center">
                  <?php
                  if ($inv->note || $inv->note != '') {
                    echo $this->sma->decode_html($inv->note);
                  }
                  ?>
                </td>
                <td class="text-center">
                  <?= ($saleJS->payment_due_date ?? '-'); ?>
                </td>
                <td class="text-center">
                  <?= ($saleJS->est_complete_date ?? '-'); ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php
    $POS = [];
    ?>
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <img src="https://erp.indoprinting.co.id/assets/uploads/logos/logo-indoprinting-300.png" class="print-only" style="opacity: .1;position:absolute;width:900px">
          <table class="table table-bordered table-condensed table-hover table-striped print-table order-table">
            <thead>
              <tr>
                <th class="col-xs-1"><?= lang('no.'); ?></th>
                <th class="col-xs-2"><?= lang('description'); ?></th>
                <th class="col-xs-2"><?= lang('spec'); ?></th>
                <th class="col-xs-1"><?= lang('width'); ?></th>
                <th class="col-xs-1"><?= lang('length'); ?></th>
                <th class="col-xs-1"><?= lang('area'); ?></th>
                <!--<th>Sub Quantity</th>-->
                <th class="col-xs-1"><?= lang('quantity'); ?></th>
                <!-- <th class="col-xs-1"><?= lang('unit_price'); ?></th>
                <th class="col-xs-2"><?= lang('subtotal'); ?></th> -->
              </tr>
            </thead>
            <tbody>
              <?php $r = 1;
              if ($rows) {
                foreach ($rows as $row) :
              ?>
                  <tr>
                    <td style="text-align:center; width:40px; vertical-align:middle;"><?= $r; ?></td>
                    <td style="vertical-align:middle;">
                      <?= '(' . $row->product_code . ') ' . $row->product_name; ?>
                    </td>
                    <?php
                    $slitem = (json_decode($row->json_data) !== NULL ? json_decode($row->json_data) : NULL);
                    ?>
                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= ($slitem->spec ?? ''); ?></td>
                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= (isset($slitem->w) ? formatQuantity($slitem->w) : '-'); ?></td>
                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= (isset($slitem->l) ? formatQuantity($slitem->l) : '-'); ?></td>
                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= (isset($slitem->area) ? formatQuantity($slitem->area) : '-'); ?></td>
                    <td style="width: 80px; text-align:center; vertical-align:middle;"><?= (isset($slitem->sqty) ? formatQuantity($slitem->sqty) : '-'); ?></td>
                    <!-- <td style="text-align:right; width:100px;"><?= $this->sma->formatMoney($row->price); ?></td> -->
                    <!-- <td style="text-align:right; width:120px;"><?= $this->sma->formatMoney($row->subtotal); ?></td> -->
                  </tr>
              <?php
                  $r++;
                endforeach;
              }
              ?>
            </tbody>
            <tfoot>
              <!-- PAYMENT INFORMATION -->
              <tr>
                <td colspan="3" rowspan="4">
                  Pembayaran dengan transfer dianggap sah jika
                  ditransfer dengan kode unik ke Rekening <strong>INDOPRINTING</strong> dengan nomor.<br><br>
                  <table class="table-condensed table-sub-print table-watermark">
                    <tbody>
                      <tr>
                        <td>BCA</td>
                        <td>8030 200234</td>
                        <td>Mandiri</td>
                        <td>1360 0005 5532 3</td>
                      </tr>
                      <tr>
                        <td>BNI</td>
                        <td>5592 09008</td>
                        <td>BRI</td>
                        <td>0083 01 001092 56 5</td>
                      </tr>
                    </tbody>
                  </table>
                </td><td class="td-own" colspan="3" style="text-align:right; font-weight:bold;"><?= lang('discount'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"></td>
                <!-- <td style="text-align:right; font-weight:bold;"><?= ($inv->discount > 0 ? '-' . formatCurrency($inv->discount): formatCurrency(0)); ?></td> -->
              </tr>
              <tr>
              <td class="td-own" colspan="3" style="text-align:right; font-weight:bold;"><?= lang('grand_total'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"></td>
                <!-- <td style="text-align:right; font-weight:bold;"><?= formatCurrency($inv->grand_total); ?></td> -->
              </tr>
              <tr>
                <td class="td-own" colspan="3" style="text-align:right; font-weight:bold;"><?= lang('paid'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"></td>
                <!-- <td style="text-align:right; font-weight:bold;"><?= formatCurrency($inv->paid); ?></td> -->
              </tr>
              <tr>
                <td class="td-own" colspan="3" style="text-align:right; font-weight:bold;"><?= lang('balance'); ?>
                  (<?= $default_currency->code; ?>)
                </td>
                <td style="text-align:right; font-weight:bold;"></td>
                <!-- <td style="text-align:right; font-weight:bold;"><?= formatCurrency($inv->grand_total - $inv->paid); ?></td> -->
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    <!-- /.ROW -->
    <div class="row">
      <div class="col-xs-12">
        <table class="table-sign">
          <tbody>
            <tr>
              <td>Customer</td>
              <td>Customer Service</td>
              <td>Operator</td>
            </tr>
            <tr>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>..............................</td>
              <td>..............................</td>
              <td>..............................</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div><!-- /.ROW -->
    <div class="row">
      <div class="col-xs-12 text-center">
        <div>Mohon cermati text, ukuran dan quantity pesanan anda, karena <strong>nota tidak bisa dilakukan revisi setelah dicetak</strong>.<br>
          Barang pesanan dalam waktu 1 bulan tidak diambil akan disumbangkan kepada yang membutuhkan.<br>
          <strong><em>Terima kasih telah menjadi pelanggan kami, jika ada masukkan silakan WhatsApp ke 081 327 043 234</em></strong>
        </div>
      </div>
    </div>
    <div class="buttons">
      <div class="btn-group btn-group-justified">
        <div class="btn-group">
          <a href="<?= admin_url('sales/add_payment/' . $inv->id) ?>" class="tip btn btn-primary" title="<?= lang('add_payment') ?>" data-toggle="modal" data-target="#myModal2">
            <i class="fad fa-dollar-sign"></i>
            <span class="hidden-sm hidden-xs"><?= lang('add_payment') ?></span>
          </a>
        </div>
        <?php if ($inv->attachment) {        ?>
          <div class="btn-group">
            <a href="<?= admin_url('welcome/download/' . $inv->attachment) ?>" class="tip btn btn-primary" title="<?= lang('attachment') ?>">
              <i class="fad fa-link"></i>
              <span class="hidden-sm hidden-xs"><?= lang('attachment') ?></span>
            </a>
          </div>
        <?php } ?>
        <div class="btn-group">
          <a href="<?= admin_url('sales/edit/' . $inv->id) ?>" class="tip btn btn-warning sledit" title="<?= lang('edit') ?>">
            <i class="fad fa-edit"></i>
            <span class="hidden-sm hidden-xs"><?= ($inv->status == 'draft' ? lang('edit_draft') : lang('edit')); ?></span>
          </a>
        </div>
        <div class="btn-group">
          <a href="#" class="tip btn btn-danger bpo" title="<?= $this->lang->line('delete_sale') ?>" data-content="<div style='width:150px;'><p><?= lang('r_u_sure') ?></p><a class='btn btn-danger' href='<?= admin_url('sales/delete/' . $inv->id) ?>'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button></div>" data-html="true" data-placement="top">
            <i class="fad fa-trash"></i>
            <span class="hidden-sm hidden-xs"><?= lang('delete') ?></span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#copy_url').click(function() {
      track_url = $('#track_url');
      track_url.css('display', 'inline-block');
      track_url.select();
      document.execCommand('copy');
      track_url.css('display', 'none');
    });

    $('#copy_url').dblclick(function() {
      window.open($('#track_url').val());
    });

    $('.qrimg').click(function() {
      window.open($('#track_url').val());
    });

    $('#view_details').click(function() {
      $('#myModal2').load(site.base_url + 'sales/details/<?= $inv->id ?>');
      $('#myModal2').modal('show');
    });
  });
</script>
<script type="text/javascript">
  $(document).ready(function() {
    $('.tip').tooltip();
  });
</script>