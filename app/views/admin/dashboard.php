<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!function_exists('row_status')) {
  function row_status($status, $elm = NULL)
  {
    $classStatus = 'default';
    $danger  = ['due', 'need_approval', 'need_payment', 'expired'];
    $info    = ['partial', 'preparing', 'completed_partial', 'ordered'];
    $primary = ['delivered', 'received'];
    $success = ['completed', 'paid', 'sent', 'approved'];
    $warning = ['draft', 'packing', 'pending', 'waiting_production', 'waiting_transfer'];

    if (in_array($status, $danger)) {
      $classStatus = 'danger';
    } else if (in_array($status, $info)) {
      $classStatus = 'info';
    } else if (in_array($status, $primary)) {
      $classStatus = 'primary';
    } else if (in_array($status, $success)) {
      $classStatus = 'success';
    } else if (in_array($status, $warning)) {
      $classStatus = 'warning';
    }

    $status = ucwords(str_replace('_', ' ', $status));

    return "<div class=\"text-center\"><span class=\"label label-{$classStatus}\">{$status}</span></div>";
  }
}
?>
<?php if (($Owner || $Admin || getPermission('dashboard-chart')) && !empty($chartData)) :
  foreach ($chartData as $month_sale) :
    $months[]         = date('M-Y', strtotime($month_sale->bulan));
    $msales[]         = $month_sale->grand_total;
    $mpaidsales[]     = $month_sale->total_paid;
    $mbalance[]       = $month_sale->total_balance;
    /*$mpending_sales[] = $month_sale->pending_sales;
    $mduesales[]      = $month_sale->due_sales;*/
  endforeach; ?>
  <div class="box" style="margin-bottom: 15px;">
    <div class="box-header">
      <h2 class="blue"><i class="fa-fw fad fa-chart-bar"></i><?= lang('overview_chart'); ?></h2>
    </div>
    <div class="box-content">
      <div class="row">
        <div class="col-md-12">
          <p class="introtext"><?php echo lang('overview_chart_heading'); ?></p>

          <div id="ov-chart" style="width:100%; height:450px;"></div>
          <p class="text-center"><?= lang('chart_lable_toggle'); ?></p>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
<?php if ($isAdmin) :
?>
  <div class="row" style="margin-bottom: 15px;">
    <div class="col-lg-12">
      <div class="box">
        <div class="box-header">
          <h2 class="blue"><i class="fad fa-th"></i><span class="break"></span><?= lang('quick_links') ?></h2>
        </div>
        <div class="box-content">
          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bblue white quick-button small" href="<?= admin_url('products') ?>">
              <i class="fad fa-barcode"></i>

              <p><?= lang('products') ?></p>
            </a>
          </div>
          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bdarkGreen white quick-button small" href="<?= admin_url('sales') ?>">
              <i class="fad fa-heart"></i>

              <p><?= lang('sales') ?></p>
            </a>
          </div>

          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bred white quick-button small" href="<?= admin_url('procurements/purchases') ?>">
              <i class="fad fa-star"></i>

              <p><?= lang('purchases') ?></p>
            </a>
          </div>

          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bpink white quick-button small" href="<?= admin_url('procurements/transfers') ?>">
              <i class="fad fa-star"></i>

              <p><?= lang('transfers') ?></p>
            </a>
          </div>

          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bgrey white quick-button small" href="<?= admin_url('customers') ?>">
              <i class="fad fa-users"></i>

              <p><?= lang('customers') ?></p>
            </a>
          </div>

          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="bgrey white quick-button small" href="<?= admin_url('suppliers') ?>">
              <i class="fad fa-users"></i>

              <p><?= lang('suppliers') ?></p>
            </a>
          </div>

          <div class="col-lg-1 col-md-2 col-xs-6">
            <a class="blightBlue white quick-button small" href="<?= admin_url('notifications') ?>">
              <i class="fad fa-comments"></i>

              <p><?= lang('notifications') ?></p>
              <!--<span class="notification green">4</span>-->
            </a>
          </div>

          <?php if ($Owner) {
          ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bblue white quick-button small" href="<?= admin_url('auth/users') ?>">
                <i class="fad fa-users-cog"></i>
                <p><?= lang('users') ?></p>
              </a>
            </div>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bblue white quick-button small" href="<?= admin_url('system_settings') ?>">
                <i class="fad fa-cogs"></i>

                <p><?= lang('settings') ?></p>
              </a>
            </div>
          <?php
          } ?>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>
  </div>
<?php else : ?>
  <div class="row" style="margin-bottom: 15px;">
    <div class="col-lg-12">
      <div class="box">
        <div class="box-header">
          <h2 class="blue"><i class="fad fa-th"></i><span class="break"></span><?= lang('quick_links') ?></h2>
        </div>
        <div class="box-content">
          <?php if (getPermission('products-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bblue white quick-button small" href="<?= admin_url('products') ?>">
                <i class="fad fa-barcode"></i>
                <p><?= lang('products') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <?php if (getPermission('sales-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bdarkGreen white quick-button small" href="<?= admin_url('sales') ?>">
                <i class="fad fa-heart"></i>
                <p><?= lang('sales') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <?php if (getPermission('purchases-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bred white quick-button small" href="<?= admin_url('procurements/purchases') ?>">
                <i class="fad fa-star"></i>
                <p><?= lang('purchases') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <?php if (getPermission('transfers-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bpink white quick-button small" href="<?= admin_url('procurements/transfers') ?>">
                <i class="fad fa-star"></i>
                <p><?= lang('transfers') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <?php if (getPermission('customers-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bgrey white quick-button small" href="<?= admin_url('customers') ?>">
                <i class="fad fa-users"></i>
                <p><?= lang('customers') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <?php if (getPermission('suppliers-index')) : ?>
            <div class="col-lg-1 col-md-2 col-xs-6">
              <a class="bgrey white quick-button small" href="<?= admin_url('suppliers') ?>">
                <i class="fad fa-users"></i>

                <p><?= lang('suppliers') ?></p>
              </a>
            </div>
          <?php endif; ?>
          <div class="clearfix"></div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row" style="margin-bottom: 15px;">
  <div class="col-md-12">
    <div class="box">
      <div class="box-header">
        <h2 class="blue"><i class="fa-fw fad fa-tasks"></i> Quick Tables</h2>
      </div>
      <div class="box-content">
        <div class="row">
          <div class="col-md-12">

            <ul id="dbTab" class="nav nav-tabs">
              <?php if ($isAdmin || getPermission('sales-index')) : ?>
                <li class=""><a href="#sales" class="tab-sales"><?= lang('sales') ?></a></li>
              <?php endif; ?>
              <?php if ($isAdmin || getPermission('purchases-index')) : ?>
                <li class=""><a href="#purchases" class="tab-purchases"><?= lang('purchases') ?></a></li>
              <?php endif; ?>
              <?php if ($isAdmin || getPermission('transfers-index')) : ?>
                <li class=""><a href="#transfers" class="tab-transfers"><?= lang('transfers') ?></a></li>
              <?php endif; ?>
              <?php if ($isAdmin || getPermission('customers-index')) : ?>
                <li class=""><a href="#customers"><?= lang('customers') ?></a></li>
              <?php endif; ?>
              <?php if ($isAdmin || getPermission('suppliers-index')) : ?>
                <li class=""><a href="#suppliers"><?= lang('suppliers') ?></a></li>
              <?php endif; ?>
            </ul>

            <div class="tab-content">
              <?php if ($Owner || $Admin || getPermission('sales-index')) : ?>

                <div id="sales" class="tab-pane fade in">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="row">
                        <div class="col-sm-3">
                          <a class="btn btn-primary sales-export" href="#"><i class="fad fa-file-excel"></i> Export</a>
                          <a class="btn btn-primary sales-sync" href="#"><i class="fad fa-sync"></i> Sync Sales</a>
                        </div>
                        <div class="col-sm-3 float-right">
                          <div class="input-group">
                            <input id="dtsales" class="form-control dtfilter" data-name="sales" placeholder="<?= lang('search'); ?>">
                            <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                              <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table id="sales-tbl" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped" style="margin-bottom: 0;">
                          <thead>
                            <tr>
                              <th style="width:30px !important;">#</th>
                              <th><?= lang('date'); ?></th>
                              <th><?= lang('reference'); ?></th>
                              <th><?= lang('customer'); ?></th>
                              <th>Grand Total</th>
                              <th>Status</th>
                              <th>Biller</th>
                              <th>Warehouse</th>
                              <th>Due Date</th>
                              <th>PIC</th>
                              <th>Operator</th>
                              <th>Production Status</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($sales)) :
                              $r = 1;
                              foreach ($sales as $order) :
                                $saleJS = getJSON($order->json_data);
                                $operators = [];
                                $biller = $this->site->getBillerByID($order->biller_id);
                                $pic = $this->site->getUserByID($order->created_by);
                                $productionStatus = '';
                                $warehouse = $this->site->getWarehouseByID($order->warehouse_id);

                                $saleItems = $this->site->getSaleItems(['sale_id' => $order->id]);
                                $isSaleCompleted = (isCompleted($order->status) ? TRUE : FALSE);

                                foreach ($saleItems as $saleItem) {
                                  $saleItemJS = getJSON($saleItem->json_data);

                                  if (!empty($saleItemJS->operator_id)) {
                                    $operator = $this->site->getUserByID($saleItemJS->operator_id);
                                  } else {
                                    $operator = NULL;
                                  }

                                  $operatorName = ($operator ? $operator->first_name . ' ' . $operator->last_name : '');

                                  if (!in_array($operatorName, $operators)) {
                                    $operators[] = $operatorName;
                                  }

                                  unset($saleItemJS, $operator, $operatorName);
                                }

                                if (!empty($saleJS->est_complete_date)) {
                                  if (!$isSaleCompleted) {
                                    if (strtotime(date('Y-m-d H:i:s')) > strtotime($saleJS->est_complete_date)) {
                                      $productionStatus = 'over_due';
                                    }
                                  }
                                }

                            ?>
                                <tr id="<?= $order->id ?>" class="invoice_link">
                                  <td><?= $r ?></td>
                                  <td><?= $order->date ?></td>
                                  <td><?= $order->reference ?></td>
                                  <td><?= $order->customer ?></td>
                                  <td class="text-right"><?= formatCurrency($order->grand_total) ?></td>
                                  <?= renderStatus($order->status) ?>
                                  <td><?= $biller->name ?></td>
                                  <td><?= $warehouse->name ?></td>
                                  <td><?= ($saleJS->est_complete_date ?? '') ?></td>
                                  <td><?= $pic->first_name . ' ' . $pic->last_name; ?></td>
                                  <td><?= '- ' . implode('<br>-', $operators) ?></td>
                                  <?= renderStatus($productionStatus) ?>
                                </tr>
                            <?php
                                $r++;
                              endforeach;
                            endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                  $(document).ready(function() {
                    salesTable = $('#sales-tbl').dataTable({
                      aLengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "<?= lang('all') ?>"]
                      ],
                      iDisplayLength: 50
                    });

                    oTable = salesTable;

                    $('#dtsales').datatableFilter();

                    $('.sales-export').click(function() {
                      location.href = site.base_url + '?export=sales';
                    });

                    $('.sales-sync').click(function() {
                      location.href = site.base_url + '?sync=sales';
                    });
                  });
                </script>

              <?php endif; ?>
              <?php if ($Owner || $Admin || getPermission('purchases-index')) : ?>

                <div id="purchases" class="tab-pane fade in">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="row">
                        <div class="col-sm-3">
                          <a class="btn btn-primary purchases-export" href="#"><i class="fad fa-file-excel"></i> Export</a>
                        </div>
                        <div class="col-sm-3 float-right">
                          <div class="input-group">
                            <input id="dtpurchases" class="form-control dtfilter" data-name="purchases" placeholder="<?= lang('search'); ?>">
                            <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                              <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table id="purchases-tbl" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped" style="margin-bottom: 0;">
                          <thead>
                            <tr>
                              <th style="width:30px !important;">#</th>
                              <th><?= $this->lang->line('date'); ?></th>
                              <th><?= $this->lang->line('reference'); ?></th>
                              <th><?= $this->lang->line('supplier'); ?></th>
                              <th><?= $this->lang->line('status'); ?></th>
                              <th><?= $this->lang->line('amount'); ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($purchases)) :
                              $r = 1;
                              foreach ($purchases as $purchase) : ?>
                                <tr id="<?= $purchase->id ?>" class="purchase_link">
                                  <td><?= $r ?></td>
                                  <td><?= $this->sma->hrld($purchase->date) ?></td>
                                  <td><?= $purchase->reference ?></td>
                                  <td><?= $purchase->supplier_name ?></td>
                                  <?= renderStatus($purchase->status) ?>
                                  <td class="text-right"><?= formatCurrency($purchase->grand_total) ?></td>
                                </tr>
                            <?php
                                $r++;
                              endforeach;
                            endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                  $(document).ready(function() {
                    purchasesTable = $('#purchases-tbl').dataTable({
                      aLengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "<?= lang('all') ?>"]
                      ],
                      iDisplayLength: 50
                    });

                    $('.purchases-export').click(function() {
                      location.href = site.base_url + '?export=purchases';
                    });

                    $('#dtpurchases').datatableFilter();
                  });
                </script>

              <?php endif; ?>
              <?php if ($Owner || $Admin || getPermission('transfers-index')) : ?>

                <div id="transfers" class="tab-pane fade">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="row">
                        <div class="col-sm-3">
                          <a class="btn btn-primary transfers-export" href="#"><i class="fad fa-file-excel"></i> Export</a>
                        </div>
                        <div class="col-sm-3 float-right">
                          <div class="input-group">
                            <input id="dttransfers" class="form-control dtfilter" data-name="transfers" placeholder="<?= lang('search'); ?>">
                            <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                              <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table id="transfers-tbl" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped" style="margin-bottom: 0;">
                          <thead>
                            <tr>
                              <th style="width:30px !important;">#</th>
                              <th><?= lang('date'); ?></th>
                              <th><?= lang('reference'); ?></th>
                              <th><?= lang('from') . ' ' . lang('warehouse'); ?></th>
                              <th><?= lang('to') . ' ' . lang('warehouse'); ?></th>
                              <th><?= lang('status'); ?></th>
                              <th><?= lang('sent_date'); ?></th>
                              <th><?= lang('received_date'); ?></th>
                              <th><?= lang('received_status'); ?></th>
                              <th><?= lang('amount'); ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($transfers)) :
                              $r = 1;
                              foreach ($transfers as $transfer) :
                                $transferJS = getJSON($transfer->json);
                                $receivedStatus = '';
                                $sentDate     = ($transferJS->sent_date ?? NULL);
                                $receivedDate = ($transferJS->received_date ?? NULL);

                                if ($sentDate) {
                                  $compareDate = ($receivedDate ? strtotime($receivedDate) : now());

                                  if ($compareDate > strtotime('+2 hour', strtotime($sentDate))) {
                                    $receivedStatus = 'over_received';
                                  }
                                } ?>
                                <tr id="<?= $transfer->id ?>" class="transfer_link">
                                  <td><?= $r ?></td>
                                  <td><?= $transfer->date ?></td>
                                  <td><?= $transfer->reference ?></td>
                                  <td><?= $transfer->from_warehouse_name ?></td>
                                  <td><?= $transfer->to_warehouse_name ?></td>
                                  <?= renderStatus($transfer->status) ?>
                                  <td><?= $sentDate ?></td>
                                  <td><?= $receivedDate ?></td>
                                  <?= renderStatus($receivedStatus); ?>
                                  <td class="text-right"><?= formatCurrency($transfer->grand_total) ?></td>
                                </tr>
                            <?php
                                $r++;
                              endforeach;
                            endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <script>
                  $(document).ready(function() {
                    transfersTable = $('#transfers-tbl').dataTable({
                      aLengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "<?= lang('all') ?>"]
                      ],
                      iDisplayLength: 50
                    });

                    $('#dttransfers').datatableFilter();

                    $('.transfers-export').click(function() {
                      location.href = site.base_url + '?export=transfers';
                    });
                  });
                </script>

              <?php endif; ?>
              <?php if ($Owner || $Admin || getPermission('customers-index')) : ?>

                <div id="customers" class="tab-pane fade in">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="table-responsive">
                        <table id="customers-tbl" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped" style="margin-bottom: 0;">
                          <thead>
                            <tr>
                              <th style="width:30px !important;">#</th>
                              <th><?= $this->lang->line('company'); ?></th>
                              <th><?= $this->lang->line('name'); ?></th>
                              <th><?= $this->lang->line('email'); ?></th>
                              <th><?= $this->lang->line('phone'); ?></th>
                              <th><?= $this->lang->line('address'); ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($customers)) :
                              $r = 1;
                              foreach ($customers as $customer) : ?>
                                <tr id="<?= $customer->id ?>" class="customer_link pointer">
                                  <td><?= $r ?></td>
                                  <td><?= $customer->company ?></td>
                                  <td><?= $customer->name ?></td>
                                  <td><?= $customer->email ?></td>
                                  <td><?= $customer->phone ?></td>
                                  <td><?= $customer->address ?></td>
                                </tr>
                            <?php
                                $r++;
                              endforeach;
                            endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

              <?php endif; ?>
              <?php if ($Owner || $Admin || getPermission('suppliers-index')) : ?>
                <div id="suppliers" class="tab-pane fade">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="table-responsive">
                        <table id="suppliers-tbl" cellpadding="0" cellspacing="0" borders="0" class="table table-bordered table-hover table-striped" style="margin-bottom: 0;">
                          <thead>
                            <tr>
                              <th style="width:30px !important;">#</th>
                              <th><?= $this->lang->line('company'); ?></th>
                              <th><?= $this->lang->line('name'); ?></th>
                              <th><?= $this->lang->line('email'); ?></th>
                              <th><?= $this->lang->line('phone'); ?></th>
                              <th><?= $this->lang->line('address'); ?></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($suppliers)) :
                              $r = 1;
                              foreach ($suppliers as $supplier) : ?>
                                <tr id="<?= $supplier->id ?>" class="supplier_link pointer">
                                  <td><?= $r ?></td>
                                  <td><?= $supplier->company ?></td>
                                  <td><?= $supplier->name ?></td>
                                  <td><?= $supplier->email ?></td>
                                  <td><?= $supplier->phone ?></td>
                                  <td><?= $supplier->address ?></td>
                                </tr>
                            <?php
                                $r++;
                              endforeach;
                            endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<script type="text/javascript">
  $(document).ready(function() {
    $('.tab-sales').click(function() {
      oTable = salesTable;

      oTable.fnDraw(false);
    });
    $('.tab-purchases').click(function() {
      oTable = purchasesTable;

      oTable.fnDraw(false);
    });
    $('.tab-transfers').click(function() {
      oTable = transfersTable;

      oTable.fnDraw(false);
    });

    $('.order').click(function() {
      window.location.href = '<?= admin_url() ?>orders/view/' + $(this).attr('id') + '#comments';
    });
    $('.invoice').click(function() {
      window.location.href = '<?= admin_url() ?>orders/view/' + $(this).attr('id');
    });
  });
</script>

<?php if (($isAdmin || getPermission('dashboard-chart')) && !empty($chartData)) : ?>
  <style type="text/css" media="screen">
    .tooltip-inner {
      max-width: 500px;
    }
  </style>
  <script src="<?= $assets; ?>js/hc/highcharts.js"></script>
  <script type="text/javascript">
    $(function() {
      Highcharts.getOptions().colors = Highcharts.map(Highcharts.getOptions().colors, function(color) {
        return {
          radialGradient: {
            cx: 0.5,
            cy: 0.3,
            r: 0.7
          },
          stops: [
            [0, color],
            [1, Highcharts.Color(color).brighten(-0.3).get('rgb')]
          ]
        };
      });

      $('#ov-chart').highcharts({
        chart: {},
        credits: {
          enabled: false
        },
        title: {
          text: ''
        },
        xAxis: {
          categories: <?= json_encode($months); ?>
        },
        yAxis: {
          min: 0,
          title: ""
        },
        tooltip: {
          shared: true,
          followPointer: true,
          formatter: function() {
            if (this.key) {
              return '<div class="tooltip-inner hc-tip" style="margin-bottom:0;">' + this.key + '<br><strong>' + currencyFormat(this.y) + '</strong> (' + formatNumber(this.percentage) + '%)';
            } else {
              var s = '<div class="well well-sm hc-tip" style="margin-bottom:0;"><h2 style="margin-top:0;">' + this.x + '</h2><table class="table table-striped"  style="margin-bottom:0;">';
              $.each(this.points, function() {
                s += '<tr><td style="color:{series.color};padding:0">' + this.series.name + ': </td><td style="color:{series.color};padding:0;text-align:right;"> <b>' +
                  currencyFormat(this.y) + '</b></td></tr>';
              });
              s += '</table></div>';
              return s;
            }
          },
          useHTML: true,
          borderWidth: 0,
          shadow: false,
          valueDecimals: site.settings.decimals,
          style: {
            fontSize: '14px',
            padding: '0',
            color: '#000000'
          }
        },
        series: [{
          color: '#4040FF',
          type: 'column',
          name: 'Grand Total',
          data: [<?php echo implode(', ', $msales); ?>]
        }, {
          color: '#40FF40',
          type: 'column',
          name: 'Paid',
          data: [<?php echo implode(', ', $mpaidsales); ?>]
        }, {
          color: '#FF4040',
          type: 'column',
          name: 'Balance',
          data: [<?php echo implode(', ', $mbalance); ?>]
        }, {
          type: 'pie',
          name: '<?= lang('stock_value'); ?>',
          data: [
            ['', 0],
            ['', 0],
            ['<?= lang('stock_value_by_price'); ?>', <?php echo $stock->stock_by_price; ?>],
            ['<?= lang('stock_value_by_cost'); ?>', <?php echo $stock->stock_by_cost; ?>],
          ],
          center: [80, 42],
          size: 80,
          showInLegend: false,
          dataLabels: {
            enabled: false
          }
        }]
      });
    });
  </script>

  <script type="text/javascript">
    $(function() {
      <?php if ($lmbs) : ?>
        $('#lmbschart').highcharts({
          chart: {
            type: 'column'
          },
          title: {
            text: ''
          },
          credits: {
            enabled: false
          },
          xAxis: {
            type: 'category',
            labels: {
              rotation: -60,
              style: {
                fontSize: '13px'
              }
            }
          },
          yAxis: {
            min: 0,
            title: {
              text: ''
            }
          },
          legend: {
            enabled: false
          },
          series: [{
            name: '<?= lang('sold'); ?>',
            data: [<?php
                    foreach ($lmbs as $r) :
                      if ($r->quantity > 0) :
                        echo "['" . addSlashes($r->product_name) . '<br>(' . $r->product_code . ")', " . $r->quantity . '],';
                      endif;
                    endforeach; ?>],
            dataLabels: {
              enabled: true,
              rotation: 0,
              color: '#000',
              align: 'right',
              y: -25,
              style: {
                fontSize: '12px'
              }
            }
          }]
        });
      <?php endif; ?>
      <?php if ($bs) : ?>
        $('#bschart').highcharts({
          chart: {
            type: 'column'
          },
          title: {
            text: ''
          },
          credits: {
            enabled: false
          },
          xAxis: {
            type: 'category',
            labels: {
              rotation: -60,
              style: {
                fontSize: '13px'
              }
            }
          },
          yAxis: {
            min: 0,
            title: {
              text: ''
            }
          },
          legend: {
            enabled: false
          },
          series: [{
            name: '<?= lang('sold'); ?>',
            data: [<?php
                    foreach ($bs as $r) :
                      if ($r->quantity > 0) :
                        echo "['" . addSlashes($r->product_name) . '<br>(' . $r->product_code . ")', " . $r->quantity . '],';
                      endif;
                    endforeach; ?>],
            dataLabels: {
              enabled: true,
              rotation: -90,
              color: '#000',
              align: 'right',
              y: -25,
              style: {
                fontSize: '12px'
              }
            }
          }]
        });
      <?php endif; ?>
    });
  </script>
  <div class="row" style="margin-bottom: 15px;">
    <div class="col-sm-6">
      <div class="box">
        <div class="box-header">
          <h2 class="blue"><i class="fa-fw fad fa-line-chart"></i><?= lang('best_sellers'), ' (' . date('M-Y', time()) . ')'; ?>
          </h2>
        </div>
        <div class="box-content">
          <div class="row">
            <div class="col-md-12">
              <div id="bschart" style="width:100%; height:450px;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="box">
        <div class="box-header">
          <h2 class="blue"><i class="fa-fw fad fa-line-chart"></i><?= lang('best_sellers') . ' (' . date('M-Y', strtotime('-1 month')) . ')'; ?>
          </h2>
        </div>
        <div class="box-content">
          <div class="row">
            <div class="col-md-12">
              <div id="lmbschart" style="width:100%; height:450px;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>