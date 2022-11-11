<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<!DOCTYPE html>
<html>
<head>
  <title>Presence Camera</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="<?= base_url('assets/presence/images/favicon_camera.ico'); ?>" rel="icon">
  <link href="<?= base_url('assets/presence/css/style.css'); ?>" rel="stylesheet">
  <link href="<?= base_url('assets/plugins/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <script src="<?= base_url('assets/plugins/jquery/jquery-3.5.1.min.js'); ?>"></script>
  <script>
    window.site = <?= json_encode([
      'base_url' => base_url()
    ]) ?>;
  </script>
</head>
<body>
  <main class="container">
    <div class="row">
      <div class="col-md-12 mt-3 mb-3">
        <video id="camera" autoplay style="width:100%;"></video>
      </div>
    </div>
  </main>
  <script src="<?= base_url('assets/presence/js/camera.js'); ?>"></script>
</body>
</html>