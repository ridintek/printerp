<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
</script>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fa fa-times"></i>
    </button>
    <button type="button" id="export_xls" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;">
      <i class="fa fa-file-excel"></i> <?= lang('download_xls'); ?>
    </button>
    <h4 class="modal-title" id="myModalLabel"><?= lang('bank_histories') . ": "; ?><?= $bank->name . ' (' . $bank->code . ')' . ($biller_id ? ' [' . $biller->name . ']' : ''); ?></h4>
  </div>
  <div class="modal-body">
    <div class="table-responsive table-limit-height">
      <table id="mTable" class="table table-bordered table-condensed table-hover table-striped">
        <thead>
          <tr>
            <th>Payment ID</th>
            <th><?= lang('date'); ?></th>
            <th><?= lang('reference'); ?></th>
            <th><?= lang('bank_name'); ?></th>
            <th><?= lang('biller_name'); ?></th>
            <th><?= lang('method'); ?></th>
            <th><?= lang('created_by'); ?></th>
            <th><?= lang('attachment'); ?></th>
            <th><?= lang('increase'); ?></th>
            <th><?= lang('decrease'); ?></th>
            <th><?= lang('balance'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
            $total_balance = $beginning_amount; $total_decrease = 0; $total_increase = 0;
            $old_balance = 0; $old_decrease = 0; $old_increase = 0; $old_date = ''; $iold_date = 0;
            if ( ! empty($rows)) {
              if ($beginning_amount) {
          ?>
          <tr>
            <td>-</td>
            <td><strong><?= $start_date . ' 00:00:00'; ?></strong></td>
            <td colspan="6" class="text-center"><strong>BEGINNING</strong></td>
            <td class="text-right"><strong><?= formatCurrency($beginning_amount); ?></strong></td>
          </tr>
          <?php
              }
              foreach ($rows as $row) {
                if ($row->status != 'sent' && $row->status != 'received') continue;
                $idate = strtotime($row->date);
                $new_date = date('Y-m-d', $idate);
                if ($iold_date && (date('m', $idate) != date('m', $iold_date))) {
          ?>
          <tr>
            <td>-</td>
            <td><strong><?= $old_date . ' 23:59:59'; ?></strong></td>
            <td class="text-center" colspan="4"><strong>SUMMARY <?= strtoupper(getMonthName(date('m', $iold_date))); ?></strong></td>
            <td class="text-right"><strong><?= formatCurrency($old_increase); ?></strong></td>
            <td class="text-right"><strong><?= formatCurrency($old_decrease); ?></strong></td>
            <td class="text-right"><strong><?= formatCurrency($old_balance); ?></strong></td>
          </tr>
          <?php
                  $old_balance = 0; $old_decrease = 0; $old_increase = 0;
                }
          ?>
          <tr>
            <td><?= $row->id; ?></td>
            <td><?= $row->date; ?></td>
              <?php
                if ($row->expense_id != NULL) {
                  $reference = $this->site->getExpenseByID($row->expense_id)->reference;
                } else if ($row->income_id != NULL) {
                  $reference = $this->site->getIncomeByID($row->income_id)->reference;
                } else if ($row->mutation_id != NULL) {
                  $reference = $this->site->getBankMutationByID($row->mutation_id)->reference;
                } else if ($row->purchase_id != NULL) {
                  $reference = $this->site->getStockPurchaseByID($row->purchase_id)->reference;
                } else if ($row->sale_id != NULL) {
                  $reference = $this->site->getSaleByID($row->sale_id)->reference;
                } else if ($row->transfer_id != NULL) {
                  $reference = $this->site->getStockTransferByID($row->transfer_id)->reference;
                }
              ?>
            <td><?= $reference; ?></td>
            <td><?= $row->biller_name; ?></td>
            <td><?= $row->category_code; ?></td>
              <?php
                if ($row->adjustment_id != NULL) {
                  $user = $this->site->getUserByID($row->created_by);
                  $created_by = ($user ? $user->fullname : '');
                } else if ($row->purchase_id != NULL) {
                  $user = $this->site->getUserByID($row->created_by);
                  $created_by = ($user ? $user->fullname : '');
                } else if ($row->sale_id != NULL) {
                  $user = $this->site->getUserByID($row->created_by);
                  $created_by = ($user ? $user->fullname : '');
                } else if ($row->transfer_id != NULL) {
                  $user = $this->site->getUserByID($row->created_by);
                  $created_by = ($user ? $user->fullname : '');
                }
              ?>
            <td><?= $created_by; ?></td>
              <?php
                $dec = 0; $inc = 0;
                if ($row->status == 'received') {
                  $inc = $row->quantity;
                  $total_increase += $inc;
                } else if ($row->status == 'sent') {
                  $dec = $row->quantity;
                  $total_decrease += $dec;
                }
              ?>
            <td class="text-right"><?= ($inc ? $this->sma->formatQuantity($inc, 2) : ''); ?></td>
            <td class="text-right"><?= ($dec ? $this->sma->formatQuantity($dec, 2) : ''); ?></td>
              <?php
                if ($row->status == 'received') {
                  $total_balance += $row->quantity;
                } else if ($row->status == 'sent') {
                  $total_balance -= $row->quantity;
                }
                $iold_date = $idate;
                $old_date = date('Y-m-d', $iold_date);
                $old_balance = $total_balance;
                $old_decrease += $dec;
                $old_increase += $inc;
              ?>
            <td class="text-right"><?= $this->sma->formatQuantity($total_balance, 2); ?></td>
          </tr>
        <?php } // foreach ?>
          <tr>
            <td>-</td>
            <td><strong><?= ($end_date ? $end_date . date(' H:i:s') : ''); ?></strong></td>
            <td class="text-center" colspan="4"><strong>SUMMARY <?= strtoupper(getMonthName(date('m', $iold_date))); ?></strong></td>
            <td class="text-right"><strong><?= $this->sma->formatQuantity($old_increase, 2); ?></strong></td>
            <td class="text-right"><strong><?= $this->sma->formatQuantity($old_decrease, 2); ?></strong></td>
            <td class="text-right"><strong><?= $this->sma->formatQuantity($old_balance, 2); ?></strong></td>
          </tr>
      <?php } else { // ! empty($rows) ?>
          <tr><td colspan="8" class="dataTables_empty"><?=lang('no_data_available');?></td></tr>
      <?php } // ! empty($rows) ?>
        </tbody>
        <tfoot class="dtFilter">
          <tr class="active">
            <th>-</th>
            <th><?= ($end_date ? $end_date . date(' H:i:s') : ''); ?></th>
            <th colspan="4" class="text-center"><strong>SUMMARY TOTAL</strong></th>
            <th class="text-right"><?= $this->sma->formatQuantity($total_increase, 2); ?></th>
            <th class="text-right"><?= $this->sma->formatQuantity($total_decrease, 2); ?></th>
            <th class="text-right"><?= $this->sma->formatQuantity($total_balance, 2); ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('#export_xls').on('click', function () {
      let q = '';
      let product_id   = <?= ($product_id ? $product_id : 'null'); ?>;
      let start_date   = <?= ($start_date ? "'" . $start_date . "'" : 'null'); ?>;
      let end_date     = <?= ($end_date ? "'" . $end_date . "'" : 'null'); ?>;
      let warehouse_id = <?= ($warehouse_id ? $warehouse_id : 'null'); ?>;

      if (product_id)   q += '&product=' + product_id;
      if (start_date)   q += '&start_date=' + start_date;
      if (end_date)     q += '&end_date=' + end_date;
      if (warehouse_id) q += '&warehouse=' + warehouse_id;

      location.href = '<?= admin_url('finances/banks/history?xls=1'); ?>' + q;
    });
  });
</script>