<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  .table td:first-child {
    font-weight: bold;
  }

  label {
    margin-right: 10px;
  }
</style>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-folder-open"></i><?= lang('group_permissions'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext"><?= lang('set_permissions'); ?></p>
        <?php if (!empty($gp)) {
          echo admin_form_open('system_settings/permissions/' . $id); ?>
          <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped reports-table">
              <thead>
                <tr>
                  <th colspan="6" class="text-center"><?php echo $group->description . ' ( ' . $group->name . ' ) ' . $this->lang->line('group_permissions'); ?></th>
                </tr>
                <tr>
                  <th rowspan="2" class="text-center"><?= lang('module_name'); ?>
                  </th>
                  <th colspan="5" class="text-center"><?= lang('permissions'); ?></th>
                </tr>
                <tr>
                  <th class="text-center"><?= lang('view'); ?></th>
                  <th class="text-center"><?= lang('add'); ?></th>
                  <th class="text-center"><?= lang('edit'); ?></th>
                  <th class="text-center"><?= lang('delete'); ?></th>
                  <th class="text-center"><?= lang('misc'); ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Dashboard</td>
                  <td colspan="4"></td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="dashboard-chart" class="checkbox" name="dashboard-chart" <?php echo (($gp->{'dashboard-chart'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="dashboard-chart" class="padding05">Chart</label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('banks'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="banks-index" <?php echo (($gp->{'banks-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="banks-add" <?php echo (($gp->{'banks-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="banks-edit" <?php echo (($gp->{'banks-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="banks-delete" <?php echo (($gp->{'banks-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="banks-reconciliation" class="checkbox" name="banks-reconciliation" <?php echo (($gp->{'banks-reconciliation'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="banks-reconciliation" class="padding05"><?= lang('bank_reconciliation') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('bank_mutations'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="mutations-index" <?php echo (($gp->{'mutations-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="mutations-add" <?php echo (($gp->{'mutations-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="mutations-edit" <?php echo (($gp->{'mutations-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="mutations-delete" <?php echo (($gp->{'mutations-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="mutations-manual" class="checkbox" name="mutations-manual" <?php echo (($gp->{'mutations-manual'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="mutations-manual" class="padding05"><?= lang('manual_validation') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('expenses'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="expenses-index" <?php echo (($gp->{'expenses-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="expenses-add" <?php echo (($gp->{'expenses-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="expenses-edit" <?php echo (($gp->{'expenses-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="expenses-delete" <?php echo (($gp->{'expenses-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="expenses-approval" class="checkbox" name="expenses-approval" <?php echo (($gp->{'expenses-approval'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="expenses-approval" class="padding05"><?= lang('approval') ?></label>
                    </span>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="expenses-payment" class="checkbox" name="expenses-payment" <?php echo (($gp->{'expenses-payment'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="expenses-payment" class="padding05"><?= lang('payment') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('incomes'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="incomes-index" <?php echo (($gp->{'incomes-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="incomes-add" <?php echo (($gp->{'incomes-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="incomes-edit" <?php echo (($gp->{'incomes-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="incomes-delete" <?php echo (($gp->{'incomes-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('payments'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="payments-index" <?php echo (($gp->{'payments-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="payments-add" <?php echo (($gp->{'payments-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="payments-edit" <?php echo (($gp->{'payments-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="payments-delete" <?php echo (($gp->{'payments-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('finances') . ' ' . lang('payment_validations'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="validations-index" <?php echo (($gp->{'validations-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="validations-add" <?php echo (($gp->{'validations-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="validations-edit" <?php echo (($gp->{'validations-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="validations-delete" <?php echo (($gp->{'validations-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="validations-cancel" class="checkbox" name="validations-cancel" <?php echo (($gp->{'validations-cancel'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="validations-cancel" class="padding05"><?= lang('cancel_validation') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="validations-manual" class="checkbox" name="validations-manual" <?php echo (($gp->{'validations-manual'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="validations-manual" class="padding05"><?= lang('manual_validation') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td>Google Review</td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="googlereview-view" <?php echo (($gp->{'googlereview-view'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="googlereview-add" <?php echo (($gp->{'googlereview-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="googlereview-view" <?php echo (($gp->{'googlereview-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="googlereview-delete" <?php echo (($gp->{'googlereview-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('machine'); ?></td>
                  <td class="text-center" colspan="4">
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="machine-assign" class="checkbox" name="machine-assign" <?php echo (($gp->{'machine-assign'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="machine-assign" class="padding05"><?= lang('assign_machine') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="machine-report_delete" class="checkbox" name="machine-report_delete" <?php echo (($gp->{'machine-report_delete'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="machine-report_delete" class="padding05"><?= lang('delete_machine_report') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('notifications'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="notify-index" <?php echo (($gp->{'notify-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="notify-add" <?php echo (($gp->{'notify-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="notify-edit" <?php echo (($gp->{'notify-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="notify-delete" <?php echo (($gp->{'notify-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('operators'); ?></td>
                  <td class="text-center" colspan="4">
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="operators-orders" class="checkbox" name="operators-orders" <?php echo (($gp->{'operators-orders'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="operators-orders" class="padding05"><?= lang('ordered_items') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="operators-checkpoint" class="checkbox" name="operators-checkpoint" <?php echo (($gp->{'operators-checkpoint'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="operators-checkpoint" class="padding05"><?= lang('checkpoint') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('procurements') . ' ' . lang('internal_uses') ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="internal_uses-index" <?php echo (($gp->{'internal_uses-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="internal_uses-add" <?php echo (($gp->{'internal_uses-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="internal_uses-edit" <?php echo (($gp->{'internal_uses-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="internal_uses-delete" <?php echo (($gp->{'internal_uses-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="internal_uses-approval" class="checkbox" name="internal_uses-approval" <?php echo (($gp->{'internal_uses-approval'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="internal_uses-approval" class="padding05"><?= lang('approval') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="internal_uses-consumable" class="checkbox" name="internal_uses-consumable" <?php echo (($gp->{'internal_uses-consumable'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="internal_uses-consumable" class="padding05"><?= lang('consumable') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="internal_uses-cmreport" class="checkbox" name="internal_uses-cmreport" <?php echo (($gp->{'internal_uses-cmreport'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="internal_uses-cmreport" class="padding05"><?= lang('combo_report') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="internal_uses-sparepart" class="checkbox" name="internal_uses-sparepart" <?php echo (($gp->{'internal_uses-sparepart'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="internal_uses-sparepart" class="padding05"><?= lang('sparepart') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('procurements') . ' ' . lang('transfers'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-index" <?php echo (($gp->{'transfers-index'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-add" <?php echo (($gp->{'transfers-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-edit" <?php echo (($gp->{'transfers-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-delete" <?php echo (($gp->{'transfers-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="transfers-approval" class="checkbox" name="transfers-approval" <?php echo (($gp->{'transfers-approval'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="transfers-approval" class="padding05"><?= lang('approval') ?></label>
                    </span>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="transfers-payment" class="checkbox" name="transfers-payment" <?php echo (($gp->{'transfers-payment'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="transfers-payment" class="padding05"><?= lang('payment') ?></label>
                    </span>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="transfers-received" class="checkbox" name="transfers-received" <?php echo (($gp->{'transfers-received'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="transfers-received" class="padding05"><?= lang('received') ?></label>
                    </span>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="transfers-sent" class="checkbox" name="transfers-sent" <?php echo (($gp->{'transfers-sent'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="transfers-sent" class="padding05"><?= lang('sent') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('procurements') . ' ' . lang('purchases'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-index" <?php echo ($gp->{'purchases-index'} ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-add" <?php echo (($gp->{'purchases-add'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-edit" <?php echo (($gp->{'purchases-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-delete" <?php echo (($gp->{'purchases-delete'} ?? FALSE) ? 'checked' : ''); ?>>
                  </td>
                  <td>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="purchases-approval" class="checkbox" name="purchases-approval" <?php echo (($gp->{'purchases-approval'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="purchases-approval" class="padding05"><?= lang('approval') ?></label>
                    </span>
                    <span style="display: inline-block;">
                      <input type="checkbox" value="1" id="purchases-other_warehouse" class="checkbox" name="purchases-other_warehouse" <?php echo (($gp->{'purchases-other_warehouse'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="purchases-other_warehouse" class="padding05"><?= lang('select_other_warehouse') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('products'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-index" <?php echo $gp->{'products-index'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-add" <?php echo $gp->{'products-add'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-edit" <?php echo $gp->{'products-edit'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-delete" <?php echo $gp->{'products-delete'} ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-categories" class="checkbox" name="products-categories" <?php echo (($gp->{'products-categories'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-categories" class="padding05"><?= lang('product_categories') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-cost" class="checkbox" name="products-cost" <?php echo $gp->{'products-cost'} ? 'checked' : ''; ?>>
                      <label for="products-cost" class="padding05"><?= lang('product_cost') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-price" class="checkbox" name="products-price" <?php echo $gp->{'products-price'} ? 'checked' : ''; ?>>
                      <label for="products-price" class="padding05"><?= lang('product_price') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-adjustments" class="checkbox" name="products-adjustments" <?php echo $gp->{'products-adjustments'} ? 'checked' : ''; ?>>
                      <label for="products-adjustments" class="padding05"><?= lang('adjustments') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-barcode" class="checkbox" name="products-barcode" <?php echo $gp->{'products-barcode'} ? 'checked' : ''; ?>>
                      <label for="products-barcode" class="padding05"><?= lang('print_barcodes') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-history" class="checkbox" name="products-history" <?php echo (($gp->{'products-history'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-history" class="padding05"><?= lang('product_history') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-quantity" class="checkbox" name="products-quantity" <?php echo (($gp->{'products-quantity'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-quantity" class="padding05"><?= lang('show_quantity') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-std_qty" class="checkbox" name="products-std_qty" <?php echo (($gp->{'products-std_qty'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-std_qty" class="padding05">Show Standard Quantity</label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-stock_count" class="checkbox" name="products-stock_count" <?php echo $gp->{'products-stock_count'} ? 'checked' : ''; ?>>
                      <label for="products-stock_count" class="padding05"><?= lang('stock_counts') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-stock_opname" class="checkbox" name="products-stock_opname" <?php echo (($gp->{'products-stock_opname'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-stock_opname" class="padding05"><?= lang('stock_opname') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-so_quantity" class="checkbox" name="products-so_quantity" <?php echo (($gp->{'products-so_quantity'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-so_quantity" class="padding05"><?= lang('stock_opname_quantity') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-transfer_view" class="checkbox" name="products-transfer_view" <?php echo (($gp->{'products-transfer_view'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-transfer_view" class="padding05"><?= lang('transfer_view') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-transfer_add" class="checkbox" name="products-transfer_add" <?php echo (($gp->{'products-transfer_add'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-transfer_add" class="padding05"><?= lang('transfer_add') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-transfer_delete" class="checkbox" name="products-transfer_delete" <?php echo (($gp->{'products-transfer_delete'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-transfer_delete" class="padding05"><?= lang('transfer_delete') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-transfer_edit" class="checkbox" name="products-transfer_edit" <?php echo (($gp->{'products-transfer_edit'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-transfer_edit" class="padding05"><?= lang('transfer_edit') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-transfer_status" class="checkbox" name="products-transfer_status" <?php echo (($gp->{'products-transfer_status'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="products-transfer_status" class="padding05"><?= lang('transfer_status') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('products_mutations'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-mutation_view" <?php echo ($gp->{'products-mutation_view'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-mutation_add" <?php echo ($gp->{'products-mutation_add'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-mutation_edit" <?php echo ($gp->{'products-mutation_edit'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="products-mutation_delete" <?php echo ($gp->{'products-mutation_delete'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="products-mutation_status" class="checkbox" name="products-mutation_status" <?php echo ($gp->{'products-mutation_status'} ?? FALSE) ? 'checked' : ''; ?>>
                      <label for="products-mutation_status" class="padding05"><?= lang('mutation_status') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('sales'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="sales-index" <?php echo ($gp->{'sales-index'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="sales-add" <?php echo ($gp->{'sales-add'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="sales-edit" <?php echo ($gp->{'sales-edit'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="sales-delete" <?php echo ($gp->{'sales-delete'} ?? FALSE) ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-edit_operator" class="checkbox" name="sales-edit_operator" <?php echo (($gp->{'sales-edit_operator'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-edit_operator" class="padding05"><?= lang('edit_operator') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-edit_price" class="checkbox" name="sales-edit_price" <?php echo (($gp->{'sales-edit_price'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-edit_price" class="padding05"><?= lang('edit_price') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-email" class="checkbox" name="sales-email" <?php echo $gp->{'sales-email'} ? 'checked' : ''; ?>>
                      <label for="sales-email" class="padding05"><?= lang('email') ?></label>
                    </span>
                    <span style="display:inline-block;">
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-payments" class="checkbox" name="sales-payments" <?php echo $gp->{'sales-payments'} ? 'checked' : ''; ?>>
                      <label for="sales-payments" class="padding05"><?= lang('payments') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-skip_validation" class="checkbox" name="sales-skip_validation" <?php echo (($gp->{'sales-skip_validation'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-skip_validation" class="padding05"><?= lang('skip_validation') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-tb" class="checkbox" name="sales-tb" <?php echo (($gp->{'sales-tb'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-tb" class="padding05">Sales TB</label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-item_status" class="checkbox" name="sales-item_status" <?php echo (($gp->{'sales-item_status'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-item_status" class="padding05"><?= lang('sales_item_status') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="sales-add_qms_only" class="checkbox" name="sales-add_qms_only" <?php echo (($gp->{'sales-add_qms_only'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="sales-add_qms_only" class="padding05">Add Sale from QMS Only</label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('purchases'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-index" <?php echo $gp->{'purchases-index'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-add" <?php echo $gp->{'purchases-add'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-edit" <?php echo $gp->{'purchases-edit'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="purchases-delete" <?php echo $gp->{'purchases-delete'} ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="purchases-email" class="checkbox" name="purchases-email" <?php echo $gp->{'purchases-email'} ? 'checked' : ''; ?>>
                      <label for="purchases-email" class="padding05"><?= lang('email') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="purchases-pdf" class="checkbox" name="purchases-pdf" <?php echo $gp->{'purchases-pdf'} ? 'checked' : ''; ?>>
                      <label for="purchases-pdf" class="padding05"><?= lang('pdf') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="purchases-payments" class="checkbox" name="purchases-payments" <?php echo $gp->{'purchases-payments'} ? 'checked' : ''; ?>>
                      <label for="purchases-payments" class="padding05"><?= lang('payments') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="purchases-expenses" class="checkbox" name="purchases-expenses" <?php echo $gp->{'purchases-expenses'} ? 'checked' : ''; ?>>
                      <label for="purchases-expenses" class="padding05"><?= lang('expenses') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="purchases-return_purchases" class="checkbox" name="purchases-return_purchases" <?php echo $gp->{'purchases-return_purchases'} ? 'checked' : ''; ?>>
                      <label for="purchases-return_purchases" class="padding05"><?= lang('return_purchases') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('transfers'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-index" <?php echo $gp->{'transfers-index'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-add" <?php echo $gp->{'transfers-add'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-edit" <?php echo $gp->{'transfers-edit'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="transfers-delete" <?php echo $gp->{'transfers-delete'} ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="transfers-email" class="checkbox" name="transfers-email" <?php echo $gp->{'transfers-email'} ? 'checked' : ''; ?>>
                      <label for="transfers-email" class="padding05"><?= lang('email') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="transfers-pdf" class="checkbox" name="transfers-pdf" <?php echo $gp->{'transfers-pdf'} ? 'checked' : ''; ?>>
                      <label for="transfers-pdf" class="padding05"><?= lang('pdf') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('customers'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="customers-index" <?php echo $gp->{'customers-index'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="customers-add" <?php echo $gp->{'customers-add'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="customers-edit" <?php echo $gp->{'customers-edit'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="customers-delete" <?php echo $gp->{'customers-delete'} ? 'checked' : ''; ?>>
                  </td>
                  <td>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="customers-deposits" class="checkbox" name="customers-deposits" <?php echo $gp->{'customers-deposits'} ? 'checked' : ''; ?>>
                      <label for="customers-deposits" class="padding05"><?= lang('deposits') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" id="customers-delete_deposit" class="checkbox" name="customers-delete_deposit" <?php echo $gp->{'customers-delete_deposit'} ? 'checked' : ''; ?>>
                      <label for="customers-delete_deposit" class="padding05"><?= lang('delete_deposit') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('suppliers'); ?></td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="suppliers-index" <?php echo $gp->{'suppliers-index'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="suppliers-add" <?php echo $gp->{'suppliers-add'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="suppliers-edit" <?php echo $gp->{'suppliers-edit'} ? 'checked' : ''; ?>>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" value="1" class="checkbox" name="suppliers-delete" <?php echo $gp->{'suppliers-delete'} ? 'checked' : ''; ?>>
                  </td>
                  <td>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('reports'); ?></td>
                  <td colspan="5">
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="printerp_report" name="reports-printerp" <?php echo (($gp->{'reports-printerp'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="printerp_report" class="padding05">PrintERP Reports</label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="income_statement" name="reports-income_statement" <?php echo (($gp->{'reports-income_statement'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="income_statement" class="padding05"><?= lang('income_statement') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="inventory_balance" name="reports-inventory_balance" <?php echo (($gp->{'reports-inventory_balance'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="inventory_balance" class="padding05"><?= lang('inventory_balance') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="product_quantity_alerts" name="reports-quantity_alerts" <?php echo $gp->{'reports-quantity_alerts'} ? 'checked' : ''; ?>>
                      <label for="product_quantity_alerts" class="padding05"><?= lang('product_quantity_alerts') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="Product_expiry_alerts" name="reports-expiry_alerts" <?php echo $gp->{'reports-expiry_alerts'} ? 'checked' : ''; ?>>
                      <label for="Product_expiry_alerts" class="padding05"><?= lang('product_expiry_alerts') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="products" name="reports-products" <?php echo $gp->{'reports-products'} ? 'checked' : ''; ?>><label for="products" class="padding05"><?= lang('products') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="daily_sales" name="reports-daily_sales" <?php echo $gp->{'reports-daily_sales'} ? 'checked' : ''; ?>>
                      <label for="daily_sales" class="padding05"><?= lang('daily_sales') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="monthly_sales" name="reports-monthly_sales" <?php echo $gp->{'reports-monthly_sales'} ? 'checked' : ''; ?>>
                      <label for="monthly_sales" class="padding05"><?= lang('monthly_sales') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="sales" name="reports-sales" <?php echo $gp->{'reports-sales'} ? 'checked' : ''; ?>>
                      <label for="sales" class="padding05"><?= lang('sales') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="payments" name="reports-payments" <?php echo $gp->{'reports-payments'} ? 'checked' : ''; ?>>
                      <label for="payments" class="padding05"><?= lang('payments') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="tax" name="reports-tax" <?php echo $gp->{'reports-tax'} ? 'checked' : ''; ?>>
                      <label for="tax" class="padding05"><?= lang('tax_report') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="expenses" name="reports-expenses" <?php echo $gp->{'reports-expenses'} ? 'checked' : ''; ?>>
                      <label for="expenses" class="padding05"><?= lang('expenses') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="daily_purchases" name="reports-daily_purchases" <?php echo $gp->{'reports-daily_purchases'} ? 'checked' : ''; ?>>
                      <label for="daily_purchases" class="padding05"><?= lang('daily_purchases') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="monthly_purchases" name="reports-monthly_purchases" <?php echo $gp->{'reports-monthly_purchases'} ? 'checked' : ''; ?>>
                      <label for="monthly_purchases" class="padding05"><?= lang('monthly_purchases') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="purchases" name="reports-purchases" <?php echo $gp->{'reports-purchases'} ? 'checked' : ''; ?>>
                      <label for="purchases" class="padding05"><?= lang('purchases') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="customers" name="reports-customers" <?php echo $gp->{'reports-customers'} ? 'checked' : ''; ?>>
                      <label for="customers" class="padding05"><?= lang('customers') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="suppliers" name="reports-suppliers" <?php echo $gp->{'reports-suppliers'} ? 'checked' : ''; ?>>
                      <label for="suppliers" class="padding05"><?= lang('suppliers') ?></label>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td><?= lang('misc'); ?></td>
                  <td colspan="5">
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="bulk_actions" name="bulk_actions" <?php echo $gp->bulk_actions ? 'checked' : ''; ?>>
                      <label for="bulk_actions" class="padding05"><?= lang('bulk_actions') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="edit_price" name="edit_price" <?php echo $gp->edit_price ? 'checked' : ''; ?>>
                      <label for="edit_price" class="padding05"><?= lang('edit_price_on_sale') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="edit_system" name="edit-system" <?php echo (($gp->{'edit-system'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="edit_system" class="padding05"><?= lang('edit_system') ?></label>
                    </span>
                    <span style="display:inline-block;">
                      <input type="checkbox" value="1" class="checkbox" id="users_edit" name="users-edit" <?php echo (($gp->{'users-edit'} ?? FALSE) ? 'checked' : ''); ?>>
                      <label for="users_edit" class="padding05"><?= lang('edit_users') ?></label>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= lang('update') ?></button>
          </div>
        <?php echo form_close();
        } else {
          echo $this->lang->line('group_x_allowed');
        } ?>
      </div>
    </div>
  </div>
</div>