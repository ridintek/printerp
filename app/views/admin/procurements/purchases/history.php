<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function() {
    let oTable = $('#HISTable').dataTable({
      "aaSorting": [[0, 'desc']],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?=lang('all')?>"]],
      "iDisplayLength": <?=$Settings->rows_per_page?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('procurements/purchases/getHistories/' . $purchase->id) ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?=$this->security->get_csrf_token_name()?>",
          "value": "<?=$this->security->get_csrf_hash()?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      "aoColumns": [null, null, null, null],
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        return nRow;
      },
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {

      }
    });
  });
</script>
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-2x">&times;</i>
      </button>
      <button type="button" class="btn btn-xs btn-default no-print pull-right" style="margin-right:15px;" onclick="window.print();">
        <i class="fa fa-print"></i> <?= lang('print'); ?>
      </button>
      <h4 class="modal-title" id="myModalLabel"><?= lang('purchase_histories') . ": {$purchase->supplier} ({$purchase->reference})"; ?></h4>
    </div>
    <div class="modal-body">
      <div class="table-responsive">
        <table id="HISTable" class="table table-bordered table-condensed table-hover table-striped">
          <thead>
          <tr>
            <th><?= lang('date'); ?></th>
            <th><?= lang('reference'); ?></th>
            <th><?= lang('description'); ?></th>
            <th><?= lang('created_by'); ?></th>
          </tr>
          </thead>
          <tbody>
            <tr><td colspan='4'><?=lang('no_data_available');?></td></tr>
          </tbody>
          <tfoot class="dtFilter">
            <tr class="active">
              <th></th><th></th><th></th><th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>