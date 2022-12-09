<?php defined('BASEPATH') or exit('No direct script access allowed');
$bgs = glob('assets/images/login-bgs/*.jpg');

foreach ($bgs as &$bg) {
  $af = explode('assets/', $bg);
  $bg = $assets . $af[1];
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Login PrintERP</title>
  <script>
    if (parent.frames.length !== 0) {
      top.location = '<?= admin_url() ?>';
    }
  </script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <link rel="shortcut icon" href="<?= base_url('assets/pwa/images/favicon.ico'); ?>" />
  <link href="<?= base_url('manifest.json'); ?>" rel="manifest">
  <link href="<?= $assets ?>plugins/fontawesome6/css/all.min.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/helpers/old-bootstrap.min.css?<?= $res_hash ?>" rel="stylesheet" />
  <link href="<?= $assets ?>styles/helpers/jquery-ui.css?<?= $res_hash ?>" rel="stylesheet" />
  <link href="<?= $assets ?>styles/theme.css?<?= $res_hash ?>" rel="stylesheet" />
  <link href="<?= $assets ?>styles/style.css?<?= $res_hash ?>" rel="stylesheet" />
  <link href="<?= $assets ?>styles/helpers/login.css?<?= $res_hash ?>" rel="stylesheet" />
  <script>
    (function() {
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register("<?= base_url('sw.js'); ?>", {
          scope: "<?= base_url(); ?>"
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
  <!-- <script src="<?= $assets ?>js/jquery-2.0.3.min.js"></script> -->
  <script src="<?= $assets ?>js/jquery-3.5.1.js"></script>
  <!-- [if lt IE 9]>
  <script src="<?= $assets ?>js/respond.min.js"></script>
  <[endif] -->
  <style>
    body {
      min-width: 350px;
    }

    .bblue {
      background: #fff !important;
    }

    .login-page .page-back {
      display: flex;
      align-items: center;
      flex-direction: column;
      justify-content: center;
      background-size: cover !important;
      background-position: center !important;
      background-image: url("<?= $bgs[mt_rand(0, count($bgs) - 1)] ?>") !important;
    }

    .contents {
      margin: 16px;
      padding: 32px 16px;
      background: rgba(0, 0, 0, 0.5);
      /*border: 1px solid rgba(0, 0, 0, 0.2);*/
    }

    .login-content,
    .login-page .login-form-links {
      margin-top: 20px;
    }
  </style>
</head>

<body class="login-page">
  <noscript>
    <div class="global-site-notice noscript">
      <div class="notice-inner">
        <p>
          <strong>JavaScript seems to be disabled in your browser.</strong><br>You must have JavaScript enabled in
          your browser to utilize the functionality of this website.
        </p>
      </div>
    </div>
  </noscript>
  <div id="loading"></div>
  <div class="page-back">
    <div class="contents">
      <div class="text-center">
        <?php if ($Settings->logo2) :
          echo '<img src="' . base_url('assets/uploads/logos/' . $Settings->logo2) . '" alt="' . $Settings->site_name . '" style="margin-bottom:10px;" />';
        endif; ?>
      </div>
      <div id="login">
        <div class="container">
          <div class="login-form-div">
            <div class="login-content">
              <form action="<?= base_url() ?>admin/login" method="POST">
                <?= csrf_field() ?>
                <div class="div-title col-sm-12">
                  <h3 class="text-default"><?= lang('login_to_your_account') ?></h3>
                </div>
                <div class="col-sm-12">
                  <div class="textbox-wrap form-group">
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fad fa-user"></i></span>
                      <input type="text" value="" required="required" class="form-control" name="identity" placeholder="ID" />
                    </div>
                  </div>
                  <div class="textbox-wrap form-group">
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fad fa-key"></i></span>
                      <input type="password" value="" required="required" class="form-control " name="password" placeholder="Password" />
                    </div>
                  </div>
                </div>
                <?php
                if ($Settings->captcha) : ?>
                  <div class="col-sm-12">
                    <div class="textbox-wrap form-group">
                      <div class="row">
                        <div class="col-sm-6 div-captcha-left">
                          <span class="captcha-image"><?php echo $image; ?></span>
                        </div>
                        <div class="col-sm-6 div-captcha-right">
                          <div class="input-group">
                            <span class="input-group-addon">
                              <a href="<?= admin_url('auth/reload_captcha'); ?>" class="reload-captcha">
                                <i class="fad fa-refresh"></i>
                              </a>
                            </span>
                            <?php echo form_input($captcha); ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                <div class="form-action col-sm-12">
                  <div class="checkbox pull-left">
                    <div class="custom-checkbox">
                      <?php echo form_checkbox('remember', '1', FALSE, 'id="remember"'); ?>
                    </div>
                    <span class="checkbox-text pull-left"><label for="remember"><?= lang('remember_me') ?></label></span>
                  </div>
                  <button type="submit" id="login" class="btn btn-success pull-right"><?= lang('login') ?> &nbsp; <i class="fad fa-sign-in"></i></button>
                </div>
              </form>
              <div class="clearfix"></div>
            </div>
            <div class="row">
              <div class="col-sm-6">
                <div class="btn-group">
                  <button class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><i class="fad fa-desktop"></i> Display <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                    <?php foreach (Warehouse::get(['active' => 1]) as $wh) : ?>
                      <li><a href="<?= admin_url("qms/display/{$wh->id}?active=1"); ?>" target="_blank"><i class="fad fa-warehouse"></i> <?= $wh->name; ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="btn-group">
                  <button class="btn btn-success dropdown-toggle" data-toggle="dropdown"><i class="fad fa-file"></i> Form Registrasi <span class="caret"></span></button>
                  <ul class="dropdown-menu">
                    <?php foreach (Warehouse::get(['active' => 1]) as $wh) : ?>
                      <li><a href="<?= admin_url("qms/register/{$wh->id}"); ?>" target="_blank"><i class="fad fa-warehouse"></i> <?= $wh->name; ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- <script src="<?= $assets ?>js/jquery.js"></script> -->
  <script src="<?= $assets ?>js/bootstrap.bundle.min.js"></script>
  <script src="<?= $assets ?>js/jquery.cookie.js"></script>
  <script src="<?= $assets ?>js/login.js?<?= $res_hash ?>"></script>
  <script>
    $(document).ready(function() {
      localStorage.clear();
      var hash = window.location.hash;
      if (hash && hash != '') {
        $("#login").hide();
        $(hash).show();
      }
    });

    // $(document).on('click', '#login', function(e) {
      
    //   e.preventDefault();
    // });

    $(window).on('load', function() {
      $('#loading').fadeOut('slow');
    });
  </script>
</body>

</html>