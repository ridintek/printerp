<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<!DOCTYPE html>
<html>
<head>
  <title>Register Device</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <link href="<?= base_url('assets/presence/images/favicon_device.ico'); ?>" rel="icon">
  <link href="<?= base_url('manifest_presence.json'); ?>" rel="manifest">
  <link href="<?= base_url('assets/presence/css/style.css'); ?>" rel="stylesheet">
  <link href="<?= base_url('assets/plugins/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
  <script>
    (function() {
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../sw.js', {
          scope: '../'
        }).then((reg) => {
          if (reg.installing) {
            console.log('Installing service worker.');
          } else if (reg.waiting) {
            console.log('Service worker installed.');
          } else if (reg.active) {
            console.log('Service worker active.');
          }
        });
      }
    })();
  </script>
  <script src="<?= base_url('assets/plugins/jquery/jquery-3.5.1.min.js'); ?>"></script>
</head>
<body>
  <main class="container">
    <div class="row">
      <div class="col-md-12 d-flex justify-content-center mb-3 mt-2">
        <div class="form-group">
          <label class="token">ZNGMP</label>
        </div>
      </div>
      <div class="col-md-12 d-flex justify-content-center mb-3">
        <div class="form-group">
          <input class="form-control" type="text" id="device_token" name="device_token" maxlength="8">
        </div>
      </div>
      <div class="col-md-12 d-flex justify-content-center mb-3">
        <div class="form-group">
          <button class="form-control btn btn-success btn-reg" type="button">REGISTER</button>
        </div>
      </div>
    </div>
  </main>
  <script>
    $(document).ready(function() {
      
    });
  </script>
</body>
</html>