<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$whFrom = $this->site->getWarehouse(['id' => $pt->warehouse_id_from]);
$whTo   = $this->site->getWarehouse(['id' => $pt->warehouse_id_to]);
$billerFrom = $this->site->getBiller(['code' => $whTo->code]); // Swap
$billerTo   = $this->site->getBiller(['code' => $whFrom->code]); // Swap
$users = $this->site->getUsers(['active' => 1]);
$banksFrom = $this->site->getBanks(['biller_id' => $billerFrom->id]);
$banksTo   = $this->site->getBanks(['biller_id' => $billerTo->id]);
?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title" id="myModalLabel">Add Product Transfer Payment</h4>
  </div>
  <form id="form">
    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_at">Created At</label>
            <input type="datetime-local" name="created_at" class="form-control" id="created_at">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_by">Created By</label>
            <select id="created_by" name="created_by" class="select2" style="width:100%">
              <?php foreach ($users as $user) : ?>
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="well well-sm">
        <div class="form-group">
          <div class="row">
            <div class="col-md-6">
              <label for="bank_id_from">From Bank</label>
              <select id="bank_id_from" name="bank_id_from" class="select2" data-placeholder="Transfer dari Bank" style="width:100%">
                <option value=""></option>
                <?php foreach ($banksFrom as $bankFrom) :
                  $number = (!empty($bankFrom->number) ? " ({$bankFrom->number})" : '');
                ?>
                  <option value="<?= $bankFrom->id ?>"><?= $bankFrom->name . $number ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_from'); ?>
              <div id="balance_from" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <div class="row">
            <div class="col-md-6">
              <label for="bank_id_to">To Bank</label>
              <select id="bank_id_to" name="bank_id_to" class="select2" data-placeholder="Transfer ke Bank" style="width:100%">
                <option value=""></option>
                <?php foreach ($banksTo as $bankTo) :
                  $number = (!empty($bankTo->number) ? " ({$bankTo->number})" : '');
                ?>
                  <option value="<?= $bankTo->id ?>"><?= $bankTo->name . $number ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_to'); ?>
              <div id="balance_to" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <?= lang('amount', 'amount'); ?>
        <input name="amount" type="text" id="amount" value="<?= ($pt->grand_total - $pt->paid); ?>" class="pa form-control kb-pad currency" required="required" />
      </div>

      <div class="form-group">
        <?= lang('attachment', 'attachment') ?>
        <input id="attachment" type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" data-show-upload="false" data-show-preview="false" class="form-control file">
      </div>

      <div class="form-group">
        <?= lang('note', 'note'); ?>
        <?php echo form_textarea('note', htmlDecode($pt->note), 'class="form-control" id="note"'); ?>
      </div>

    </div>
    <div class="modal-footer">
      <button id="add_payment" class="btn btn-primary">Add Payment</button>
    </div>
  </form>
  <script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
  <script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
  <script type="text/javascript" charset="UTF-8">
    $(document).ready(function() {
      let payment_status = '<?= $pt->payment_status; ?>';

      $('#created_at').val('<?= dtJS($this->serverDateTime) ?>');
      $('#created_by').val('<?= $this->session->userdata('user_id') ?>').trigger('change');
      $('#amount').val(formatCurrency('<?= $pt->grand_total - $pt->paid ?>'));

      $('#add_payment').click(function(e) {
        e.preventDefault();

        let formData = new FormData(document.querySelector('#form'));

        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        $.ajax({
          contentType: false,
          data: formData,
          error: (xhr) => {
            toastr.error(xhr.responseJSON.message);
          },
          method: 'POST',
          processData: false,
          success: (data) => {
            toastr.success(data.message);

            if (typeof Table == 'object') Table.draw(false);

            $('#myModal').modal('hide');
          },
          url: site.base_url + 'products/transfer/addPayment/<?= $pt->id ?>'
        });
      });

      $('#bank_id_from').change(function() {
        $.ajax({
          success: (data) => {
            $('#balance_from').html(formatCurrency(data.data.balance));
          },
          url: site.base_url + 'finances/getBankBalance/' + this.value
        });
      });

      $('#bank_id_to').change(function() {
        $.ajax({
          success: (data) => {
            $('#balance_to').html(formatCurrency(data.data.balance));
          },
          url: site.base_url + 'finances/getBankBalance/' + this.value
        });
      });
    });
  </script>