<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-sm no-modal-header">
  <div class="modal-content">
    <div class="modal-body">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-2x">&times;</i>
      </button>
      <?php if ($mime == 'application/pdf') { ?>
      <object data="<?= $link; ?>" type="application/pdf" width="100%"  height="90%"></object>
      <?php } else if (preg_match('/image\/.*/', $mime)) { ?>
      <img class="popup-image" src="<?= $link; ?>" />
      <?php } else { ?>
      <div>Unknown file type, you can download <a href="<?= $link; ?>">HERE</a>.</div>
      <?php } ?>
    </div>
  </div>
</div>
<script type="text/javascript">

</script>
