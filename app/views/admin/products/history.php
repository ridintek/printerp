<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
</script>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <button type="button" id="export_history_xls" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;">
      <i class="fad fa-file-excel"></i> <?= lang('export_excel'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?= lang('product_histories') . ": "; ?><?= $product->name . ' (' . $product->code . ')' . ($warehouse_id ? ' [' . $warehouse->name . ']' : ''); ?></h4>
  </div>
  <div class="modal-body">
    <div class="table-responsive table-limit-height">
      <table id="mTable" class="table table-bordered table-condensed table-hover table-striped">
        <thead>
          <tr>
            <th>Stock ID</th>
            <th><?= lang('date'); ?></th>
            <th><?= lang('reference'); ?></th>
            <th><?= lang('warehouse'); ?></th>
            <th><?= lang('category'); ?></th>
            <th><?= lang('created_by'); ?></th>
            <th><?= lang('increase'); ?></th>
            <th><?= lang('decrease'); ?></th>
            <th><?= lang('balance'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $total_balance = filterQuantity($beginning_qty);
          $total_decrease = 0.0;
          $total_increase = 0.0;
          $old_balance = 0.0;
          $old_decrease = 0.0;
          $old_increase = 0.0;
          $old_date = '';
          $iold_date = 0.0;

          if (!empty($rows)) { ?>
            <tr>
              <td>-</td>
              <td><strong><?= $start_date . ' 00:00:00'; ?></strong></td>
              <td colspan="6" class="text-center"><strong>BEGINNING</strong></td>
              <td class="text-right"><strong><?= formatDecimal($beginning_qty); ?></strong></td>
            </tr>
            <?php
            foreach ($rows as $row) {
              if ($row->status != 'sent' && $row->status != 'received') continue;

              $idate = strtotime($row->date);
              $quantity = filterDecimal($row->quantity);

              if ($iold_date && (date('m', $idate) != date('m', $iold_date))) { // Monthly Summary ?>
                <tr>
                  <td>-</td>
                  <td><strong><?= $old_date . ' 23:59:59'; ?></strong></td>
                  <td class="text-center" colspan="4"><strong>SUMMARY <?= strtoupper(getMonthName(date('m', $iold_date))); ?></strong></td>
                  <td class="text-right"><strong><?= formatDecimal($old_increase); ?></strong></td>
                  <td class="text-right"><strong><?= formatDecimal($old_decrease); ?></strong></td>
                  <td class="text-right"><strong><?= formatDecimal($old_balance); ?></strong></td>
                </tr>
              <?php
                $old_balance = 0.0;
                $old_decrease = 0.0;
                $old_increase = 0.0;
              }
              ?>
              <tr>
                <td><?= $row->id; ?></td>
                <td><?= $row->date; ?></td>
                <?php
                $reference = '';
                $this->load->model('ProductTransfer');

                if ($row->adjustment_id != NULL) {
                  $reference = $this->site->getStockAdjustmentByID($row->adjustment_id)->reference;
                } else if ($row->internal_use_id != NULL) {
                  $reference = $this->site->getStockInternalUseByID($row->internal_use_id)->reference;
                } else if ($row->purchase_id != NULL) {
                  $reference = $this->site->getStockPurchaseByID($row->purchase_id)->reference;
                } else if ($row->sale_id != NULL) {
                  if ($sale = $this->site->getSaleByID($row->sale_id)) {
                    $reference = $sale->reference;
                  } else {
                    $reference = '[ DELETED ]';
                  }
                } else if ($row->transfer_id != NULL) {
                  if ($pt = ProductTransfer::getRow(['id' => $row->transfer_id])) {
                    $reference = $pt->reference;
                  } else if ($transfer = $this->site->getStockTransferByID($row->transfer_id)) {
                    $reference = $transfer->reference;
                  } else {
                    $reference = '[ DELETED ]';
                  }
                }
                ?>
                <td><?= $reference; ?></td>
                <td><?= $row->warehouse_name; ?></td>
                <td><?= $row->category_code; ?></td>
                <?php
                if ($row->created_by) {
                  $user = $this->site->getUserByID($row->created_by);
                  $created_by = ($user ? $user->fullname : '');
                } else {
                  $created_by = '';
                }
                ?>
                <td><?= $created_by; ?></td>
                <?php
                $dec = NULL;
                $inc = NULL;

                if ($row->status == 'received') {
                  $inc = $quantity;
                  $total_increase = filterQuantity($total_increase + $inc);
                } else if ($row->status == 'sent') {
                  $dec = $quantity;
                  $total_decrease = filterQuantity($total_decrease + $dec);
                }
                ?>
                <td class="text-right" data-desc="increase"><?= ($inc ? formatDecimal($inc) : ''); ?></td>
                <td class="text-right" data-desc="decrease"><?= ($dec ? formatDecimal($dec) : ''); ?></td>
                <?php

                if ($row->status == 'received') {
                  $total_balance = filterQuantity($total_balance + $quantity);
                } else if ($row->status == 'sent') {
                  $total_balance = filterQuantity($total_balance - $quantity);
                }

                $iold_date = $idate;
                $old_date = date('Y-m-d', $iold_date);
                $old_balance = $total_balance;
                $old_decrease = filterQuantity($old_decrease + ($dec ?? 0));
                $old_increase = filterQuantity($old_increase + ($inc ?? 0));
                ?>
                <td class="text-right" data-desc="balance"><?= formatDecimal($total_balance); ?></td>
              </tr>
            <?php } // foreach ($rows as $row)
            ?>
            <tr>
              <td>-</td>
              <td><strong><?= ($end_date ? $end_date . date(' H:i:s') : ''); ?></strong></td>
              <td class="text-center" colspan="4"><strong>SUMMARY <?= strtoupper(getMonthName(date('m', $iold_date))); ?></strong></td>
              <td class="text-right"><strong><?= formatDecimal($old_increase); ?></strong></td>
              <td class="text-right"><strong><?= formatDecimal($old_decrease); ?></strong></td>
              <td class="text-right"><strong><?= formatDecimal($old_balance); ?></strong></td>
            </tr>
          <?php } else { // ! empty($rows)
          ?>
            <tr>
              <td colspan="9" class="dataTables_empty"><?= lang('no_data_available'); ?></td>
            </tr>
          <?php } // ! empty($rows)
          ?>
        </tbody>
        <tfoot class="dtFilter">
          <tr class="active">
            <th>-</th>
            <th><?= ($end_date ? $end_date . date(' H:i:s') : ''); ?></th>
            <th colspan="4" class="text-center"><strong>SUMMARY TOTAL</strong></th>
            <th class="text-right"><?= formatDecimal($total_increase); ?></th>
            <th class="text-right"><?= formatDecimal($total_decrease); ?></th>
            <th class="text-right"><?= formatDecimal($total_balance); ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('#export_history_xls').on('click', function() {
      let q = '';
      let product_id = <?= ($product_id ? $product_id : 'null'); ?>;
      let start_date = <?= ($start_date ? "'" . $start_date . "'" : 'null'); ?>;
      let end_date = <?= ($end_date ? "'" . $end_date . "'" : 'null'); ?>;
      let warehouse_id = <?= ($warehouse_id ? $warehouse_id : 'null'); ?>;

      if (product_id) q += '&product=' + product_id;
      if (start_date) q += '&start_date=' + start_date;
      if (end_date) q += '&end_date=' + end_date;
      if (warehouse_id) q += '&warehouse=' + warehouse_id;

      location.href = '<?= admin_url('products/history?xls=1'); ?>' + q;
    });
  });
</script>