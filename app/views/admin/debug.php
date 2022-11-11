<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<input type="file" id="file" name="file">
<input type="button" onclick="upload()" value="Upload">

<script>
  function upload () {
    let xhr = new XMLHttpRequest();
    let form_data = new FormData();
    form_data.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
    form_data.append('file', document.getElementById('file').files[0]);
    xhr.addEventListener('load', function() {
      console.log(xhr.response);
    });
    xhr.open('POST', 'file', true);
    xhr.send(form_data);
  }
</script>