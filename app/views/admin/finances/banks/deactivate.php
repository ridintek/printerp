<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('deactivate'); ?></h4>
  </div>
  <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
  echo admin_form_open('finances/banks/deactivate/' . $bank->id, $attrib); ?>
  <div class="modal-body">
    <p><?php echo sprintf(lang('deactivate_bank'), $bank->name); ?></p>
    <div class="form-group">
      <label class="checkbox" for="confirm">
        <input type="checkbox" name="confirm" value="1" id="confirm" /> <?= lang('yes') ?>
      </label>
    </div>
    <?php echo form_hidden($csrf); ?>
    <?php echo form_hidden(['id' => $bank->id]); ?>
  </div>
  <div class="modal-footer">
    <?php echo form_button('deactivate', lang('deactivate'), 'class="btn btn-danger" id="submit"'); ?>
  </div>
</div>
<?php echo form_close(); ?>
<script>
  $(document).ready(function () {
    $('#submit').click(function () {
      let data = {
        <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
        deactivate: true,
        confirm: ($('#confirm')[0].checked ? 1 : 0)
      };
      $.ajax({
        data: data,
        error: function() {

        },
        method: 'POST',
        success: function (data) {
          if ( ! data.error) {
            if (oTable) oTable.fnDraw(false);
            $('#myModal').modal('hide');
            addAlert(data.msg, 'success');
          } else {
            $('#myModal').modal('hide');
            addAlert(data.msg, 'danger');
          }
        },
        url: site.base_url + 'finances/banks/deactivate/<?= $bank->id; ?>'
      });
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
