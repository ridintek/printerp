<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel"><?= lang('view') . ' (' . ($mime_type ?? '404') . ')'; ?></h4>
  </div>
  <div class="modal-body">
    <?php if ($mime_type == 'image/jpg' || $mime_type == 'image/jpeg' || $mime_type == 'image/png') { ?>
    <img src="<?= admin_url('gallery/get?name=' . $name . '&path=' . $path); ?>" style="width: 100%;" />
    <?php } else if ($mime_type == 'application/pdf') { ?>
    <embed src="<?= admin_url('gallery/get?name=' . $name . '&path=' . $path); ?>" height="540" width="1024" />
    <?php } else if ($mime_type != '') { ?>
    <div>Unknown file type (<?= $mime_type; ?>).
      Please download <a href="<?= admin_url('gallery/get?name=' . $name . '&path=' . $path . '&download=true'); ?>" target="_blank">HERE</a></div>
    <?php } else { ?>
    <div class="text-center"><h2>404 File Not Found</h2></div>
    <?php } ?>
  </div>
</div>