<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?php echo lang('activate'); ?></h4>
  </div>
  <div class="modal-body">
    <p><?php echo sprintf(lang('activate_bank'), $notification->id); ?></p>
    <div class="form-group">
      <label class="checkbox" for="confirm">
        <input type="checkbox" name="confirm" value="1" id="confirm" /> <?= lang('yes') ?>
      </label>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_button('activate', lang('activate'), 'class="btn btn-success" id="submit"'); ?>
  </div>
</div>
<script>
  $(document).ready(function () {
    $('#submit').click(function () {
      let data = {
        <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>',
        activate: true,
        confirm: ($('#confirm')[0].checked ? 1 : 0)
      };
      $.ajax({
        data: data,
        error: function() {

        },
        method: 'POST',
        success: function (data) {
          if (typeof data == 'object' && ! data.error) {
            if (oTable) oTable.fnDraw(false);
            $('#myModal').modal('hide');
            addAlert(data.msg, 'success');
          } else {
            $('#myModal').modal('hide');
            addAlert(data.msg, 'danger');
          }
        },
        url: site.base_url + 'notifications/activate/<?= $notification->id; ?>'
      });
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
