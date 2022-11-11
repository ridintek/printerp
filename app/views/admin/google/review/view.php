<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
if ($startDate = $this->input->get('start_date')) {
  $q .= '&start_date=' . $startDate;
}
if ($endDate = $this->input->get('end_date')) {
  $q .= '&end_date=' . $endDate;
}
?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Google Review Details</h4>
  </div>
  <div class="modal-body">
    <table id="ReportTable" class="table table-bordered table-condensed table-hover table-right-left table-striped" style="width:100%;">
      <tbody>
        <tr>
          <td>PIC Name</td>
          <td><?= User::getRow(['id' => $review->pic_id])->fullname ?></td>
        </tr>
        <tr>
          <td>Biller</td>
          <td><?= Biller::getRow(['id' => $review->biller_id])->name ?></td>
        </tr>
        <tr>
          <td>Customer Name</td>
          <td><?= $review->customer_name ?></td>
        </tr>
        <tr>
          <td>Status</td>
          <?= renderStatus($review->status) ?>
        </tr>
        <tr>
          <td>Created At</td>
          <td><?= $review->created_at ?></td>
        </tr>
        <tr>
          <td>Created By</td>
          <td><?= User::getRow(['id' => $review->created_by])->fullname ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    'use strict';
  })
</script>