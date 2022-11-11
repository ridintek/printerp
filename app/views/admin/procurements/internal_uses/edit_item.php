<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Edit Item [<?= $product->code ?>]</h4>
  </div>
  <div class="modal-body">
    <form action="<?= base_url('procurements/internal_uses/edit_item/' . $product->id) ?>" id="form" data-toggle="validator" enctype="multipart/form-data">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="price">Price</label>
            <input type="text" class="form-control currency" id="price" name="price" value=""<?= ($isAdmin ? '' : ' disabled') ?>>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="markon_price">Markon Price</label>
            <input type="text" class="form-control currency" id="markon_price" name="markon_price" value=""<?= ($isAdmin ? '' : ' disabled') ?>>
          </div>
        </div>
      </div>
      <input type="hidden" name="<?= csrf_token_name() ?>" value="<?= csrf_hash() ?>">
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <button id="submit" class="btn btn-primary">Edit</button>
  </div>
</div>
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    let itemId = <?= $product->id ?>;

    if (iuitems) {
      $.each(iuitems, function() {
        if (this.item_id == itemId) {
          $('#price').val(formatCurrency(this.row.price));
          $('#markon_price').val(formatCurrency(this.row.markon_price));
        }
      });
    }

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));

      $.ajax({
        contentType: false,
        data: form,
        method: 'POST',
        processData: false,
        success: function(data) {
          if (isObject(data)) {
            if (data.success) {
              for (let x in iuitems) {
                if (iuitems[x].item_id == itemId) {
                  console.log('updated');
                  iuitems[x].row.price = formatDecimal($('#price').val());
                  iuitems[x].row.markon_price = formatDecimal($('#markon_price').val());
                  localStorage.setItem('iuitems', JSON.stringify(iuitems));
                  loadItems();
                }
              }
              addAlert(data.message, 'success');
            } else {
              addAlert(data.message, 'danger');
            }
          } else {
            addAlert('Something wrong here.', 'danger');
          }

          $('#myModal').modal('hide');
        },
        url: site.base_url + 'procurements/internal_uses/edit_item/<?= $product->id ?>'
      })
    });
  });
</script>