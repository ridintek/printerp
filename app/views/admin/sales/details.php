<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title text-center">Sale Details [<?= $sale->reference ?>]</h4>
    </div>
    <div class="modal-body">
      <table class="table table-condensed table-striped">
        <thead>
          <tr>
            <th colspan="2">Invoice Details</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="col-md-6 bold">Created by</td>
            <td class="col-md-6"><?= $creator->fullname; ?></td>
          </tr>
          <tr>
            <td class="bold">Created at</td>
            <td><?= $sale->date; ?></td>
          </tr>
          <tr>
            <td class="bold">Updated by</td>
            <td><?= ($updater ? $updater->fullname : '-'); ?></td>
          </tr>
          <tr>
            <td class="bold">Updated at</td>
            <td><?= ($sale->updated_at ? $sale->updated_at : '-'); ?></td>
          </tr>
        </tbody>
      </table>

      <table class="table table-condensed table-striped">
        <thead>
          <tr>
            <th colspan="2">Item Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($sale_items) :
            foreach ($sale_items as $sale_item) :
              $saleItemJS = getJSON($sale_item->json_data);
              $_cD = ($saleItemJS->completed_at ?? $saleItemJS->updated_at);
              $completedDate = (!empty($_cD) ? $_cD : NULL);
              $productionStatus = 'good';

              if (!empty($saleItemJS->due_date)) {
                if (empty($completedDate)) {
                  if (strtotime(date('Y-m-d H:i:s')) > strtotime($saleItemJS->due_date)) {
                    $productionStatus = 'over_due';
                  }
                } else {
                  if (strtotime($completedDate) > strtotime($saleItemJS->due_date)) {
                    $productionStatus = 'over_due';
                  }
                }
              } ?>
              <tr>
                <td class="bold text-center" colspan="2" style="background: #C0FFC0">(<?= $sale_item->product_code; ?>) <?= $sale_item->product_name; ?></td>
              </tr>
              <?php if ($Owner) { ?>
                <tr>
                  <td class="col-md-6 bold">Sale Item ID</td>
                  <td class="col-md-6"><?= $sale_item->id; ?></td>
                </tr>
              <?php } ?>
              <tr>
                <?php $due_date = ($saleItemJS->due_date ?? '-'); ?>
                <td class="col-md-6 bold">Due Date</td>
                <td class="col-md-6"><?= $due_date ?></td>
              </tr>
              <tr>
                <?php $date = ($saleItemJS->completed_at ?? $saleItemJS->updated_at); ?>
                <td class="bold">Completed Date</td>
                <td class=""><?= (!empty($date) ? $date : '-') ?></td>
              </tr>
              <?php
              $operator = NULL;
              $operator_id = ($saleItemJS->operator_id ?? '');
              $operatorName = '-';
              $operatorWarehouse = '-';

              if ($operator_id) {
                $operator = $this->site->getUserByID($operator_id);
                $operatorName = $operator->fullname;

                $wh = $this->site->getWarehouse(['id' => $operator->warehouse_id]);

                if ($wh) {
                  $operatorWarehouse = $wh->name;
                }
              }
              ?>
              <tr>
                <td class="bold">Operator</td>
                <td class=""><?= $operatorName ?></td>
              </tr>
              <tr>
                <td class="bold">Operator Warehouse</td>
                <td class=""><?= $operatorWarehouse ?></td>
              </tr>
              <tr>
                <?php $status = ($saleItem->status ?? '-'); ?>
                <td class="bold">Status</td>
                <?= renderStatus($status); ?>
              </tr>
              <tr>
                <td class="bold">Production Status</td>
                <?= renderStatus($productionStatus); ?>
              </tr>
              <?php
              $productType = $sale_item->product_type;
              ?>
              <?php if ($productType == 'combo') :
                $comboItems = $this->site->getProductComboItems($sale_item->product_id, $sale->warehouse_id); ?>
                <tr>
                  <td class="bold text-center" colspan="2" style="background: #FFFF40">RAW Materials Status</td>
                </tr>
                <?php
                $c = 1;
                foreach ($comboItems as $comboItem) :
                  $product = $this->site->getProductByCode($comboItem->code);
                  $stocks  = $this->site->getStocks(['saleitem_id' => $sale_item->id, 'product_id' => $product->id]); ?>
                  <tr>
                    <td colspan="2">
                      <div class="row">
                        <div class="col-md-12 bold">
                          <?= $c++ ?>. (<?= $product->code; ?>) <?= $product->name; ?>
                        </div>
                      </div>
                      <?php if (empty($stocks)) : ?>
                        <div class="row">
                          <div class="col-md-12 bold text-center alert-danger">BELUM DIPRODUKSI</div>
                        </div>
                      <?php endif; ?>
                      <?php foreach ($stocks as $stock) : ?>
                        <?php if ($Owner) : ?>
                          <div class="row">
                            <div class="col-md-6">Stock ID</div>
                            <div class="col-md-6"><?= $stock->id ?></div>
                          </div>
                        <?php endif; ?>
                        <div class="row">
                          <div class="col-md-6">
                            Completed Date
                          </div>
                          <div class="col-md-6">
                            <?= $stock->date ?>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-6">
                            Completed Qty
                          </div>
                          <div class="col-md-6">
                            <?= filterDecimal($stock->quantity) ?>
                          </div>
                        </div>

                        <div class="row">
                          <div class="col-md-6">
                            Warehouse
                          </div>
                          <div class="col-md-6">
                            <?= $stock->warehouse_name ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php elseif ($productType == 'service' || $productType == 'standard') :
                $stocks = $this->site->getStocks(['saleitem_id' => $sale_item->id, 'product_id' => $sale_item->product_id]); ?>

                <?php foreach ($stocks as $stock) : ?>
                  <?php if ($Owner) : ?>
                    <tr>
                      <td class="bold">Stock ID</td>
                      <td><?= $stock->id; ?></td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td class="bold">Completed Date</td>
                    <td><?= $stock->date; ?></td>
                  </tr>
                  <tr>
                    <td class="bold">Completed Qty</td>
                    <td><?= filterDecimal($stock->quantity); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <table class="table table-condensed table-striped">
        <thead>
          <tr>
            <th colspan="2">Payment Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($payments) {
            $count = 1;
            foreach ($payments as $payment) {
              $bank = $this->site->getBankByID($payment->bank_id);
              $cashier = $this->site->getUserByID($payment->created_by);
          ?>
              <tr>
                <td class="bold text-center" colspan="2" style="background: #C0FFC0">Payment <?= $count; ?></td>
              </tr>
              <tr>
                <td class="col-md-6 bold">Date</td>
                <td class="col-md-6"><?= $payment->date; ?></td>
              </tr>
              <tr>
                <td class="bold">Amount</td>
                <td><?= formatCurrency($payment->amount); ?></td>
              </tr>
              <tr>
                <td class="bold">Bank Account</td>
                <td><?= $bank->name; ?></td>
              </tr>
              <tr>
                <td class="bold">Cashier</td>
                <td><?= $cashier->fullname; ?></td>
              </tr>
              <tr>
                <td class="bold">Payment Method</td>
                <td><?= $payment->method; ?></td>
              </tr>
            <?php
              $count++;
            }
          } else { ?>
            <tr>
              <td class="bold text-center">No Payments</td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>