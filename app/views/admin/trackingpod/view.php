<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($startDate = getGET('start_date')) {
  $q .= '&start_date=' . $startDate;
}
if ($endDate = getGET('end_date')) {
  $q .= '&end_date=' . $endDate;
}

$user = $this->site->getUserByID($track->created_by);
$product = $this->site->getProductByID($track->pod_id);
$warehouse = $this->site->getWarehouseByID($track->warehouse_id);
?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Tracking POD Details</h4>
  </div>
  <div class="modal-body">
    <table id="ReportTable" class="table table-bordered table-condensed table-hover table-right-left table-striped" style="width:100%;">
      <tbody>
        <tr>
          <td>PIC Name</td>
          <td><?= $user->fullname ?></td>
        </tr>
        <tr>
          <td>Warehouse</td>
          <td><?= $warehouse->name ?></td>
        </tr>
        <tr>
          <td>Category</td>
          <td><?= $product->code ?></td>
        </tr>
        <tr>
          <td>Current Click</td>
          <td><?= formatDecimal($klikpod_qty) ?></td>
        </tr>
        <tr>
          <td>Start Click</td>
          <td><?= formatDecimal($track->start_click) ?></td>
        </tr>
        <tr>
          <td>End Click</td>
          <td><?= formatDecimal($track->end_click) ?></td>
        </tr>
        <tr>
          <td>Usage Click</td>
          <td><?= formatDecimal($track->usage_click) ?></td>
        </tr>
        <tr>
          <td>Reject by Machine</td>
          <td><?= formatDecimal($track->mc_reject) ?></td>
        </tr>
        <tr>
          <td>Reject by Operator</td>
          <td><?= formatDecimal($track->op_reject) ?></td>
        </tr>
        <tr>
          <td>Total Reject</td>
          <td><?= formatDecimal($track->mc_reject + $track->op_reject) ?></td>
        </tr>
        <tr>
          <td>ERP Click</td>
          <td><?= formatDecimal($track->erp_click) ?></td>
        </tr>
        <tr>
          <td>Tolerance (%)</td>
          <td><?= filterDecimal($track->tolerance) ?>%</td>
        </tr>
        <tr>
          <td>Tolerance Click</td>
          <td><?= formatDecimal($track->tolerance_click) ?></td>
        </tr>
        <tr>
          <td>Cost Click</td>
          <td><?= formatCurrency($track->cost_click) ?></td>
        </tr>
        <tr>
          <td>Balance</td>
          <td><?= formatDecimal($track->balance) ?></td>
        </tr>
        <tr>
          <td>Total Penalty</td>
          <td class="<?= ($track->balance >= 0 ?: 'red') ?>"><?= formatCurrency($track->total_penalty) ?></td>
        </tr>
        <tr>
          <td>Note</td>
          <td><?= htmlDecode($track->note) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function () {
    'use strict';
  })
</script>