<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fad fa-times"></i>
      </button>
      <h4 class="modal-title text-center" id="myModalLabel">DISKON DAN PELUNASAN</h4>
    </div>
    <div class="modal-body">
      <form id="discpayForm">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label for="bank">Bank</label>
              <select id="bank" class="select2" name="bank" style="width:100%;">
                <?php $banks = $this->site->getBanks(['active' => 1]);
                foreach ($banks as $bank) : ?>
                  <option value="<?= $bank->id ?>"><?= $bank->name ?></option>
                <?php
                endforeach;
                ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label for="discount">Discount (%)</label>
              <input type="number" class="form-control" id="discount" name="discount" min="1" max="100" value="1">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <b>Note: Jika grand total per invoice Rp100.000,- dan discount 10%,
              maka pelunasan akan menjadi Rp90.000,-.</b>
          </div>
        </div>
        <?= csrf_field() ?>
      </form>
    </div>
    <div class="modal-footer">
      <input type="button" value="Diskon dan Lunaskan" class="btn btn-primary" id="add_payment" />
    </div>
  </div>
</div>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $('#add_payment').click(function () {
    let formData = new FormData($('#discpayForm')[0]);
    let vals     = $('input[name="val[]"]');

    vals.each((index, elm) => {
      if (elm.checked) {
        formData.append('val[]', elm.value);
      }
    });

    $.ajax({
      contentType: false,
      method: 'POST',
      data: formData,
      processData: false,
      success: function(data) {
        if (!data.error) {
          addAlert(data.message, 'success');
          if (oTable) oTable.fnDraw(false);
        } else if (data.error) {
          addAlert(data.message, 'danger');
        }

        $('#myModal').modal('hide');
      },
      url: site.base_url + 'sales/discpay'
    });
  });
</script>