<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>

<head>
  <script data-pace-options='{"ajax": false}' src="<?= $assets ?>plugins/pace-1.2.3/pace.min.js"></script>
  <link href="<?= $assets ?>plugins/pace-1.2.3/pace-theme-flash.css" rel="stylesheet">
  <meta charset="utf-8">
  <meta http-equiv="cache-control" content="no-cache">
  <base href="<?= site_url(); ?>" />
  <link href="<?= site_url(); ?>manifest.json" rel="manifest">
  <?php if ($isLocal) : // If local, then use indonesian flag.
  ?>
    <link rel="icon" href="<?= site_url(); ?>assets/pwa/images/favicon.ico" />
  <?php else : ?>
    <link rel="icon" href="<?= site_url(); ?>assets/pwa/images/favicon.ico" />
  <?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
  <title>Loading...</title>
  <link href="<?= $assets ?>plugins/fontawesome6/css/all.min.css" rel="stylesheet">
  <link href="<?= $assets ?>plugins/jquery-contextmenu-2.9.2/jquery.contextMenu.min.css" rel="stylesheet">
  <link href="<?= $assets ?>plugins/toastr/toastr.min.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/bootstrapValidator.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/helpers/old-bootstrap.min.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/helpers/jquery-ui.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/tabulator.min.css" rel="stylesheet">
  <link href="<?= $assets ?>styles/theme.css?<?= $res_hash ?>" rel="stylesheet">
  <link href="<?= $assets ?>styles/style.css?<?= $res_hash ?>" rel="stylesheet">
  <link href="<?= $assets ?>qms/css/alertify.min.css" rel="stylesheet">
  <link href="<?= base_url('assets/css/common.css?' . $res_hash) ?>" rel="stylesheet">
  <script>
    (function() {
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register("<?= base_url('sw.js'); ?>", {
          scope: "<?= base_url(); ?>"
        }).then((reg) => {
          if (reg.installing) {
            // console.log('Installing service worker.');
          } else if (reg.waiting) {
            // console.log('Service worker installed.');
          } else if (reg.active) {
            // console.log('Service worker active.');
          }
        });
      }
    })();
  </script>
  <script src="<?= $assets ?>js/jquery-3.5.1.js"></script>
  <script>
    window.security = {
      csrf_token_name: '<?= $this->security->get_csrf_token_name(); ?>',
      csrf_hash: '<?= $this->security->get_csrf_hash(); ?>'
    };
    window.site = <?= json_encode([
                    'assets' => $assets,
                    'base_url' => admin_url(),
                    'dateFormats' => $dateFormats,
                    'name' => $Settings->site_name,
                    'page_title' => $page_title,
                    'permissions' => ['gp' => $GP],
                    'settings' => $Settings,
                    'settingsJSON' => json_decode($Settings->settings_json),
                    'url' => base_url()
                  ]) ?>;
    window.biller_id = <?= (XSession::get('biller_id') ?? $Settings->default_biller ?? 'null'); ?>;
    window.user_id = <?= (XSession::get('user_id') ?? 'null'); ?>;
    window.session = <?= json_encode([
                        'group_id' => XSession::get('group_id')
                      ]) ?>;
    window.warehouse_id = <?= (XSession::get('warehouse_id') ?? $Settings->default_warehouse ?? 'null'); ?>;
  </script>
  <script src="<?= $assets ?>qms/js/ridintek.js?<?= $res_hash ?>"></script>
  <script src="<?= $assets ?>qms/js/counter.js?<?= $res_hash ?>"></script>
  <noscript>
    <style type="text/css">
      #loading {
        display: none;
      }
    </style>
  </noscript>
  <?php $warehouse_id = (XSession::get('warehouse_id') ?? $Settings->default_warehouse); ?>
  <?php if ($Settings->user_rtl) { ?>
    <link href="<?= $assets ?>styles/helpers/bootstrap-rtl.min.css" rel="stylesheet" />
    <link href="<?= $assets ?>styles/style-rtl.css" rel="stylesheet" />
    <script>
      $(document).ready(function() {
        $('.pull-right, .pull-left').addClass('flip');
      });
    </script>
  <?php } ?>
  <script>
    $(window).on('load', function() {
      $("#loading").fadeOut("slow");
    });

    let biller_name = '<?= XSession::get('biller_name'); ?>';
  </script>
</head>

<body>
  <div id="loading"></div>
  <div id="app_wrapper" class="no-print">
    <header id="header" class="navbar">
      <div class="container">
        <a class="navbar-brand" href="<?= admin_url() ?>">
          <span class="logo"><?= $Settings->site_name ?></span>
          <?php if (XSession::get('biller_name')) { ?>
            <span>(<?= XSession::get('biller_name'); ?>)</span>
          <?php } ?>
        </a>
        <div class="btn-group visible-xs btn-visible-sm">
          <button class="navbar-toggle btn" type="button" data-toggle="collapse" data-target="#sidebar_menu">
            <span class="fad fa-2x fa-bars"></span>
          </button>
          <a href="<?= admin_url('users/profile/' . XSession::get('user_id')); ?>" class="btn">
            <span class="fad fa-2x fa-user" style="color:#4080FF;"></span>
          </a>
          <a href="#" class="btn clearAllNotifications">
            <span class="fad fa-2x fa-exclamation" style="color:#f0e820;"></span>
          </a>
          <a href="<?= admin_url('logout'); ?>" class="btn">
            <span class="fad fa-2x fa-sign-out" style="color:#f04040;"></span>
          </a>
        </div>
        <div class="header-nav">
          <ul class="nav navbar-nav pull-right">
            <li class="dropdown">
              <a class="btn account dropdown-toggle" data-toggle="dropdown" href="#">
                <?php
                if (XSession::get('avatar')) {
                  $avatar_img = 'assets/uploads/avatars/thumbs/' . XSession::get('avatar');

                  if (file_exists(FCPATH . $avatar_img)) {
                    $avatar_img_url = base_url($avatar_img);
                  } else { // Default
                    $avatar_img_url = base_url('assets/images/' . XSession::get('gender') . '.png');
                  }
                } else { // Default
                  $avatar_img_url = base_url('assets/images/' . XSession::get('gender') . '.png');
                }
                ?>
                <img alt="" src="<?= $avatar_img_url; ?>" class="mini_avatar img-rounded">
                <div class="user">
                  <span><?= XSession::get('fullname'); ?> (<?= strtoupper(XSession::get('username')); ?>)</span>
                </div>
              </a>
              <ul class="dropdown-menu pull-right">
                <li>
                  <a href="<?= admin_url('users/profile/' . XSession::get('user_id')); ?>">
                    <i class="fad fa-user"></i> <?= lang('profile'); ?>
                  </a>
                </li>
                <li>
                  <a href="<?= admin_url('users/profile/' . XSession::get('user_id') . '/#cpassword'); ?>"><i class="fad fa-key"></i> <?= lang('change_password'); ?>
                  </a>
                </li>
                <li class="divider"></li>
                <li>
                  <a href="<?= admin_url('logout'); ?>">
                    <i class="fad fa-sign-out"></i> <?= lang('logout'); ?>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
          <ul class="nav navbar-nav pull-right">
            <li class="dropdown hidden-xs"><a class="btn tip" title="<?= lang('dashboard') ?>" data-placement="bottom" href="<?= admin_url('welcome') ?>"><i class="fad fa-tachometer"></i></a></li>
            <li class="dropdown hidden-xs">
              <a class="btn bblue tip" title="PrintERP User Guide" data-placement="bottom" href="https://docs.google.com/document/d/1hq0qPT56F3Lpj4SxPcAsbssjVb8PBstTVLVVJV2v2LQ/edit?usp=sharing" target="_blank">
                <i class="fad fa-question"></i>
              </a>
            </li>
            <?php if ($isAdmin || getPermission('edit-system')) { ?>
              <li class="dropdown hidden-sm">
                <a class="btn tip" title="<?= lang('settings') ?>" data-placement="bottom" href="<?= admin_url('system_settings') ?>">
                  <i class="fad fa-cogs"></i>
                </a>
              </li>
            <?php } ?>
            <li class="dropdown hidden-xs">
              <a class="btn tip" title="<?= lang('calculator') ?>" data-placement="bottom" href="#" data-toggle="dropdown">
                <i class="fad fa-calculator"></i>
              </a>
              <ul class="dropdown-menu pull-right calc">
                <li class="dropdown-content">
                  <span id="inlineCalc"></span>
                </li>
              </ul>
            </li>
            <?php if ($info) { ?>
              <li class="dropdown hidden-sm">
                <a class="btn bblue tip" title="<?= lang('notifications') ?>" data-placement="bottom" href="#" data-toggle="dropdown">
                  <i class="fad fa-info-circle"></i>
                  <span class="number blightOrange black"><?= sizeof($info) ?></span>
                </a>
                <ul class="dropdown-menu pull-right content-scroll">
                  <li class="dropdown-header"><i class="fad fa-info-circle"></i> <?= lang('notifications'); ?></li>
                  <li class="dropdown-content">
                    <div class="scroll-div">
                      <div class="top-menu-scroll">
                        <ol class="oe">
                          <?php foreach ($info as $n) { ?>
                            <li class="alert-<?= $n->type; ?>"><?= $n->comment; ?></li>
                          <?php } ?>
                        </ol>
                      </div>
                    </div>
                  </li>
                </ul>
              </li>
            <?php } ?>
            <li class="dropdown hidden-sm">
              <a class="btn tip" title="<?= lang('styles') ?>" data-placement="bottom" data-toggle="dropdown" href="#">
                <i class="fab fa-css3"></i>
              </a>
              <ul class="dropdown-menu pull-right">
                <li class="bwhite noPadding">
                  <a href="#" id="fixed" class="">
                    <i class="fad fa-angle-double-left"></i>
                    <span id="fixedText">Fixed</span>
                  </a>
                  <a href="#" id="cssLight" class="grey">
                    <i class="fad fa-stop"></i> Grey
                  </a>
                  <a href="#" id="cssBlue" class="blue">
                    <i class="fad fa-stop"></i> Blue
                  </a>
                  <a href="#" id="cssBlack" class="black">
                    <i class="fad fa-stop"></i> Black
                  </a>
                </li>
              </ul>
            </li>
            <li class="dropdown hidden-xs">
              <a class="btn tip" title="<?= lang('language') ?>" data-placement="bottom" data-toggle="dropdown" href="#">
                <img src="<?= base_url('assets/images/' . $Settings->user_language . '.png'); ?>" alt="">
              </a>
              <ul class="dropdown-menu pull-right">
                <?php $scanned_lang_dir = array_map(function ($path) {
                  return basename($path);
                }, glob(APPPATH . 'language/*', GLOB_ONLYDIR));
                foreach ($scanned_lang_dir as $entry) { ?>
                  <li>
                    <a href="<?= admin_url('welcome/language/' . $entry); ?>">
                      <img src="<?= base_url('assets/images/' . $entry . '.png'); ?>" class="language-img">
                      &nbsp;&nbsp;<?= ucwords($entry); ?>
                    </a>
                  </li>
                <?php } ?>
                <!-- <li class="divider"></li>
                <li>
                  <a href="<?= admin_url('welcome/toggle_rtl') ?>">
                    <i class="fad fa-align-<?= $Settings->user_rtl ? 'right' : 'left'; ?>"></i>
                    <?= lang('toggle_alignment') ?>
                  </a>
                </li> -->
              </ul>
            </li>
            <?php if (($isAdmin || $GP['reports-quantity_alerts'] || $GP['reports-expiry_alerts']) && ($qty_alert_num > 0 || $exp_alert_num > 0 || $shop_sale_alerts)) { ?>
              <!-- <li class="dropdown hidden-sm">
                <a class="btn blightOrange tip" title="<?= lang('alerts') ?>" data-placement="left" data-toggle="dropdown" href="#">
                  <i class="fad fa-exclamation-triangle"></i>
                  <span class="number bred black"><?= $qty_alert_num + $wh_stock_alert_num + (($Settings->product_expiry) ? $exp_alert_num : 0) + $shop_sale_alerts + $shop_payment_alerts; ?></span>
                </a>
                <ul class="dropdown-menu pull-right">
                  <?php if ($qty_alert_num > 0) { ?>
                    <li>
                      <a href="<?= admin_url('reports/quantity_alerts') ?>" class="">
                        <span style="padding-right: 20px;"><i class="fad fa-warehouse"></i> <?= lang('product_quantity_alerts') ?></span>
                        <span class="label label-danger" style="margin-top:3px;"><?= $qty_alert_num; ?></span>
                      </a>
                    </li>
                  <?php } ?>
                  <?php if ($wh_stock_alert_num) { ?>
                    <li>
                      <a href="<?= admin_url('reports/wh_stock_alert') ?>" class="">
                        <span style="padding-right: 20px;"><i class="fad fa-warehouse-alt"></i> <?= lang('warehouse_stock_alert'); ?></span>
                        <span class="label label-danger" style="margin-top:3px;"><?= $wh_stock_alert_num; ?></span>
                      </a>
                    </li>
                  <?php } ?>
                </ul>
              </li> -->
            <?php } ?>
            <li class="dropdown hidden-xs">
              <a class="btn bred tip" title="<?= lang('clear_ls') ?>" data-placement="bottom" id="clearLS" href="#">
                <i class="fad fa-eraser"></i>
              </a>
            </li>
            <li class="dropdown hidden-xs">
              <a class="btn bpurple tip clearAllNotifications" title="Clear Notifications" data-placement="bottom" href="#">
                <i class="fad fa-exclamation"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <!-- LEFT MENU BAR -->
    <div class="container" id="container">
      <div class="row" id="main-con">
        <table class="lt">
          <tr>
            <td class="sidebar-con">
              <div id="sidebar-left">
                <div class="sidebar-nav nav-collapse collapse navbar-collapse" id="sidebar_menu">
                  <ul class="nav main-menu">
                    <li class="mm_welcome">
                      <a href="<?= admin_url() ?>">
                        <i class="fad fa-tachometer"></i>
                        <span class="text"> <?= lang('dashboard'); ?></span>
                      </a>
                    </li>
                    <?php if ($Owner) : ?>
                      <li class="mm_file_manager">
                        <a href="<?= admin_url('filemanager') ?>">
                          <i class="fad fa-folder-open" style="color: yellow"></i>
                          <span class="text"> File Manager</span>
                        </a>
                      </li>
                    <?php endif; ?>
                    <?php if (
                      $isAdmin ||
                      $GP['banks-index'] || $GP['banks-add'] || $GP['mutations-index'] ||
                      $GP['mutations-add'] || $GP['expenses-index'] || $GP['expenses-add'] ||
                      $GP['incomes-index'] || $GP['incomes-add'] || $GP['validations-index'] ||
                      $GP['validations-add'] || $GP['validations-index'] || $GP['validations-edit']
                    ) : ?>
                      <li class="mm_finances">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-dollar-sign" style="color: lime"></i>
                          <span class="text"> <?= lang('finances'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || $GP['banks-index']) : ?>
                            <li id="finances_banks">
                              <a class="submenu" href="<?= admin_url('finances/banks'); ?>">
                                <i class="fad fa-landmark" style="color: #ff8000"></i><span class="text"> <?= lang('bank_accounts_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['banks-add']) : ?>
                            <li id="finances_banks_add">
                              <a class="submenu" href="<?= admin_url('finances/banks/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                                <i class="fad fa-plus-square" style="color: #80FF80"></i><span class="text"> <?= lang('add_bank_account'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['banks-reconciliation']) : ?>
                            <li id="finances_banks_reconciliations">
                              <a class="submenu" href="<?= admin_url('finances/reconciliations'); ?>">
                                <i class="fad fa-sync" style="color: #8040FF"></i><span class="text"> <?= lang('bank_reconciliations'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['mutations-index']) : ?>
                            <li id="finances_mutations">
                              <a class="submenu" href="<?= admin_url('finances/mutations'); ?>">
                                <i class="fad fa-box-usd" style="color: #FF80FF"></i><span class="text"> <?= lang('bank_mutations_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['mutations-add']) : ?>
                            <li id="finances_mutations_add">
                              <a class="submenu" href="<?= admin_url('finances/mutations/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                                <i class="fad fa-plus-square" style="color: #80FF80"></i><span class="text"> <?= lang('add_bank_mutation'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['expenses-index']) : ?>
                            <li id="finances_expenses">
                              <a class="submenu" href="<?= admin_url('finances/expenses'); ?>">
                                <i class="fad fa-arrow-alt-left" style="color: #FF8080"></i><span class="text"> <?= lang('expenses_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['expenses-add']) : ?>
                            <li id="finances_expenses_add">
                              <a class="submenu" href="<?= admin_url('finances/expenses/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                                <i class="fad fa-plus-square" style="color: #80FF80"></i><span class="text"> <?= lang('add_expense'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['incomes-index']) : ?>
                            <li id="finances_incomes">
                              <a class="submenu" href="<?= admin_url('finances/incomes'); ?>">
                                <i class="fad fa-arrow-alt-right" style="color: #8080FF"></i><span class="text"> <?= lang('incomes_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['incomes-add']) : ?>
                            <li id="finances_incomes_add">
                              <a class="submenu" href="<?= admin_url('finances/incomes/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                                <i class="fad fa-plus-square" style="color: #80FF80"></i><span class="text"> <?= lang('add_income'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['validations-index']) : ?>
                            <li id="finances_validations">
                              <a class="submenu" href="<?= admin_url('finances/validations'); ?>">
                                <i class="fad fa-check" style="color: #80FF80"></i><span class="text"> <?= lang('payment_validations'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (getPermission('googlereview-view')) : ?>
                      <li class="mm_google">
                        <a class="dropmenu" href="#">
                          <i class="fab fa-google" style="color: #8040FF"></i>
                          <span class="text"> Google</span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || getPermission('googlereview-view')) : ?>
                            <li id="google_review">
                              <a class="submenu" href="<?= admin_url('google/review'); ?>">
                                <i class="fas fa-star" style="color: yellow"></i><span class="text"> Google Review</span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || getPermission('googlereview-target')) : ?>
                            <li id="google_review_target">
                              <a class="submenu" href="<?= admin_url('google/review/target'); ?>">
                                <i class="fad fa-bullseye" style="color: #ff8000"></i><span class="text"> Review Target</span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                        </a>
                      </li>
                    <?php endif; ?>

                    <li class="mm_machines">
                      <a class="dropmenu" href="#">
                        <i class="fad fa-cog" style="color: #80FFFF"></i>
                        <span class="text"> <?= lang('machine_and_equipment'); ?> </span>
                        <span class="chevron closed"></span>
                      </a>
                      <ul>
                        <li id="machines_index">
                          <a class="submenu" href="<?= admin_url('machines'); ?>">
                            <i class="fad fa-cogs" style="color: #4080FF"></i><span class="text"> <?= lang('machine_and_equipment_list'); ?></span>
                          </a>
                        </li>
                        <li id="machines_maintenance_logs">
                          <a class="submenu" href="<?= admin_url('machines/maintenance/logs'); ?>">
                            <i class="fad fa-list" style="color: #FF4040"></i><span class="text"> <?= lang('maintenance_logs'); ?></span>
                          </a>
                        </li>
                        <li id="machines_maintenance_schedules">
                          <a class="submenu" href="<?= admin_url('machines/maintenance/schedules'); ?>">
                            <i class="fad fa-calendar" style="color: #80FF80"></i><span class="text"> <?= lang('maintenance_schedules'); ?></span>
                          </a>
                        </li>
                      </ul>
                    </li>

                    <?php if (
                      $isAdmin ||
                      $GP['operators-orders'] || $GP['operators-checkpoint']
                    ) : ?>
                      <li class="mm_operators">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-user-hard-hat" style="color: #FF8080"></i>
                          <span class="text"> <?= lang('operators'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || $GP['operators-orders']) : ?>
                            <li id="operators_orders">
                              <a class="submenu" href="<?= admin_url('operators/orders'); ?>">
                                <i class="fad fa-th-list" style="color: #80FF80"></i><span class="text"> <?= lang('ordered_items'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['operators-checkpoint']) : ?>
                            <li id="operators_checkpoint">
                              <a class="submenu" href="<?= admin_url('operators/checkpoint'); ?>">
                                <i class="fad fa-check" style="color: #80FF80"></i><span class="text"> <?= lang('checkpoint'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if ($Owner) : ?>
                      <li class="mm_payrolls">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-money-check" style="color: #4080FF"></i>
                          <span class="text"> <?= lang('payrolls'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="payrolls_index">
                            <a class="submenu" href="<?= admin_url('payrolls'); ?>">
                              <i class="fad fa-usd-square" style="color: #FFFF00"></i><span class="text"> <?= lang('payrolls'); ?></span>
                            </a>
                          </li>
                          <li id="payrolls_add">
                            <a class="submenu" href="<?= admin_url('payrolls/add'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                              <i class="fad fa-plus-octagon" style="color: #80FF80"></i><span class="text"> <?= lang('add_payroll'); ?></span>
                            </a>
                          </li>
                          <li id="payrolls_import">
                            <a class="submenu" href="<?= admin_url('payrolls/import'); ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                              <i class="fad fa-upload" style="color: #80FF80"></i><span class="text"> <?= lang('import_payroll'); ?></span>
                            </a>
                          </li>
                          <li id="payrolls_categories">
                            <a class="submenu" href="<?= admin_url('payrolls/categories'); ?>">
                              <i class="fad fa-layer-group" style="color: #FF8000"></i><span class="text"> <?= lang('payroll_categories'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if ($Owner) : ?>
                      <li class="mm_presence">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-clock" style="color: #FF40FF"></i>
                          <span class="text"> <?= lang('presence'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="presence_index">
                            <a class="submenu" href="<?= admin_url('presence'); ?>">
                              <i class="fad fa-user-clock" style="color: #FF8000"></i><span class="text"> <?= lang('presence_list'); ?></span>
                            </a>
                          </li>
                          <li id="presence_schedules">
                            <a class="submenu" href="<?= admin_url('presence/schedules'); ?>">
                              <i class="fad fa-calendar" style="color: #FF4040"></i><span class="text"> <?= lang('schedules'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (
                      $isAdmin ||
                      $GP['internal_uses-index'] || $GP['internal_uses-add'] || $GP['transfers-index'] ||
                      $GP['transfers-add'] || $GP['purchases-index'] || $GP['purchases-add']
                    ) : ?>
                      <li class="mm_procurements">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-shopping-cart" style="color: #8080FF"></i>
                          <span class="text"> <?= lang('procurements'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || $GP['internal_uses-index']) : ?>
                            <li id="procurements_internal_uses">
                              <a class="submenu" href="<?= admin_url('procurements/internal_uses'); ?>">
                                <i class="fad fa-hand-receiving" style="color: #FF80FF"></i><span class="text"> <?= lang('internal_uses_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['internal_uses-add']) : ?>
                            <li id="procurements_internal_uses_add">
                              <a class="submenu" href="<?= admin_url('procurements/internal_uses/add'); ?>">
                                <i class="fad fa-plus-circle" style="color: #80FF80"></i><span class="text"> <?= lang('add_internal_use'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['purchases-index']) : ?>
                            <li id="procurements_purchases2">
                              <a class="submenu" href="<?= admin_url('procurements/purchases2'); ?>">
                                <i class="fad fa-credit-card" style="color: #80FFFF"></i><span class="text"> Purchases</span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['purchases-index']) : ?>
                            <li id="procurements_purchases">
                              <a class="submenu" href="<?= admin_url('procurements/purchases'); ?>">
                                <i class="fad fa-credit-card" style="color: #80FFFF"></i><span class="text"> <?= lang('purchases_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['purchases-add']) : ?>
                            <li id="procurements_purchases_plan">
                              <a class="submenu" href="<?= admin_url('procurements/purchases/plan'); ?>">
                                <i class="fad fa-calendar-plus" style="color: #8080FF"></i><span class="text"> <?= lang('purchases_plan'); ?></span>
                              </a>
                            </li>
                            <li id="procurements_purchases_add">
                              <a class="submenu" href="<?= admin_url('procurements/purchases/add'); ?>">
                                <i class="fad fa-cart-plus" style="color: #80FF80"></i><span class="text"> <?= lang('add_purchase'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['transfers-index']) : ?>
                            <li id="procurements_transfers">
                              <a class="submenu" href="<?= admin_url('procurements/transfers'); ?>">
                                <i class="fad fa-exchange" style="color: #FFFF80"></i><span class="text"> <?= lang('transfers_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['transfers-add']) : ?>
                            <li id="procurements_transfers_plan">
                              <a class="submenu" href="<?= admin_url('procurements/transfers/plan'); ?>">
                                <i class="fad fa-calendar-plus" style="color: #FF4040"></i><span class="text"> <?= lang('transfers_plan'); ?></span>
                              </a>
                            </li>
                            <li id="procurements_transfers_add">
                              <a class="submenu" href="<?= admin_url('procurements/transfers/add'); ?>">
                                <i class="fad fa-plus-square" style="color: #80FF80"></i><span class="text"> <?= lang('add_transfer'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (
                      $isAdmin ||
                      $GP['products-index'] || $GP['products-add'] || $GP['products-barcode'] ||
                      $GP['products-adjustments'] || $GP['products-stock_count'] || $GP['products-mutation_view']
                    ) : ?>
                      <li class="mm_products">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-box-full" style="color: #FFFF00"></i>
                          <span class="text"> <?= lang('products'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || $GP['products-index']) : ?>
                            <li id="products_index">
                              <a class="submenu" href="<?= admin_url('products'); ?>">
                                <i class="fad fa-box-up" style="color: #80FFFF"></i>
                                <span class="text"> <?= lang('products_list'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['products-add']) : ?>
                            <li id="products_add">
                              <a class="submenu" href="<?= admin_url('products/add'); ?>">
                                <i class="fad fa-plus-circle" style="color: #80FF80"></i>
                                <span class="text"> <?= lang('add_product'); ?></span>
                              </a>
                            </li>
                            <li id="products_categories">
                              <a href="<?= admin_url('products/categories') ?>">
                                <i class="fad fa-folder-open" style="color: #FFFF80"></i>
                                <span class="text"> <?= lang('categories'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['products-mutation_view']) : ?>
                            <li id="products_mutation">
                              <a href="<?= admin_url('products/mutation') ?>">
                                <i class="fad fa-arrow-right-arrow-left" style="color: #FF8040"></i>
                                <span class="text"> <?= lang('mutation'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || getPermission('products-transfer_view')) : ?>
                            <li id="products_transfer">
                              <a href="<?= admin_url('products/transfer') ?>">
                                <i class="fad fa-arrow-right-arrow-left" style="color: #40FF80"></i>
                                <span class="text"> <?= lang('transfer'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || getPermission('products-transfer_add')) : ?>
                            <li id="products_transfer_plan">
                              <a href="<?= admin_url('products/transfer/plan') ?>">
                                <i class="fad fa-arrow-right-arrow-left" style="color: #40FF80"></i>
                                <span class="text"> <?= lang('transfer_plan'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['products-add']) : ?>
                            <li id="products_import">
                              <a class="submenu" href="<?= admin_url('products/import'); ?>">
                                <i class="fad fa-upload" style="color: #00FFFF"></i>
                                <span class="text"> <?= lang('import_products'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['products-adjustments']) : ?>
                            <li id="products_quantity_adjustments">
                              <a class="submenu" href="<?= admin_url('products/quantity_adjustments'); ?>">
                                <i class="fad fa-filter" style="color: #8080FF"></i>
                                <span class="text"> <?= lang('quantity_adjustments'); ?></span>
                              </a>
                            </li>
                            <li id="products_add_adjustment">
                              <a class="submenu" href="<?= admin_url('products/add_adjustment'); ?>">
                                <i class="fad fa-plus-circle" style="color: #80FF80"></i>
                                <span class="text"> <?= lang('add_adjustment'); ?></span>
                              </a>
                            </li>
                            <li id="products_add_adjustment_by_csv">
                              <a class="submenu" href="<?= admin_url('products/add_adjustment_by_csv'); ?>">
                                <i class="fad fa-file-import" style="color: #80FF80"></i>
                                <span class="text"> <?= lang('add_adjustment_by_csv'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <li id="products_stock_opname_add">
                            <a class="submenu" href="<?= admin_url('products/stock_opname/add'); ?>">
                              <i class="fad fa-plus-circle" style="color: #80FF80"></i>
                              <span class="text"> <?= lang('add_stock_opname'); ?></span>
                            </a>
                          </li>
                          <li id="products_stock_opname">
                            <a class="submenu" href="<?= admin_url('products/stock_opname'); ?>">
                              <i class="fad fa-list-ol" style="color: #4040FF"></i>
                              <span class="text"> <?= lang('stock_opname_list'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (
                      $isAdmin ||
                      $GP['sales-index'] || $GP['sales-add']
                    ) : ?>
                      <li class="mm_qms">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-users-class" style="color: #FF80FF"></i>
                          <span class="text">QMS</span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="qms_counter">
                            <a class="submenu" href="<?= admin_url('qms/counter'); ?>">
                              <i class="fad fa-user-headset" style="color: #FFFF80"></i>
                              <span class="text"> <?= lang('counter'); ?></span>
                            </a>
                          </li>
                          <li id="qms_display">
                            <a class="submenu" href="<?= admin_url('qms/display/' . $warehouse_id); ?>?active=1" target="_blank">
                              <i class="fad fa-desktop" style="color: #FF8080"></i>
                              <span class="text"> QMS Display</span>
                            </a>
                          </li>
                          <li id="qms_index">
                            <a class="submenu" href="<?= admin_url('qms'); ?>">
                              <i class="fad fa-list" style="color: #80FFFF"></i>
                              <span class="text"> <?= lang('qms_list'); ?></span>
                            </a>
                          </li>
                          <li id="qms_registration">
                            <a class="submenu" href="<?= admin_url('qms/register/' . $warehouse_id); ?>" target="_blank">
                              <i class="fad fa-file-alt" style="color: #80FF80"></i>
                              <span class="text"> <?= lang('qms_registration'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                      <li class="mm_sales">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-cash-register" style="color: #80FFFF"></i>
                          <span class="text"> <?= lang('sales'); ?>
                          </span> <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || $GP['sales-index']) { ?>
                            <li id="sales_index">
                              <a class="submenu" href="<?= admin_url('sales'); ?>">
                                <i class="fad fa-list" style="color: #FF8000"></i>
                                <span class="text"> <?= lang('sales_list'); ?></span>
                              </a>
                            </li>
                          <?php } ?>
                          <?php if (
                            $isAdmin ||
                            ($GP['sales-add'] && !$GP['sales-add_qms_only']) ||
                            ($GP['sales-add'] && XSession::get('biller_name') == 'Online')
                          ) : ?>
                            <li id="sales_add">
                              <a class="submenu" href="<?= admin_url('sales/add'); ?>">
                                <i class="fad fa-plus-circle" style="color: #80FF80"></i>
                                <span class="text"> <?= lang('add_sale'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if (
                            $isAdmin || $GP['sales-tb']
                          ) : ?>
                            <!-- <li id="sales_tb">
                              <a class="submenu" href="<?= admin_url('sales/tb'); ?>">
                                <i class="fad fa-exchange" style="color: #FF80FF"></i>
                                <span class="text"> Sales TB</span>
                              </a>
                            </li> -->
                          <?php endif; ?>
                          <?php if (
                            $isAdmin || $GP['sales-tb']
                          ) : ?>
                            <li id="sales_tb_payment">
                              <a class="submenu" href="<?= admin_url('sales/tb_payment'); ?>">
                                <i class="fad fa-exchange" style="color: #FF80FF"></i>
                                <span class="text"> Sales TB Payment</span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if ($isAdmin) : ?>
                      <li class="mm_schedule">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-calendar-alt" style="color: #80FF40"></i>
                          <span class="text"> Schedule
                          </span> <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="schedule_index">
                            <a class="submenu" href="<?= admin_url('schedule'); ?>">
                              <i class="fad fa-calendar-alt" style="color: #4080FF"></i>
                              <span class="text"> Schedule</span>
                            </a>
                          </li>
                          <li id="schedule_holiday">
                            <a class="submenu" href="<?= admin_url('schedule/holiday'); ?>">
                              <i class="fad fa-calendar" style="color: #FF4080"></i>
                              <span class="text"> Holiday</span>
                            </a>
                          </li>
                        </ul>
                        </a>
                      </li>
                    <?php endif; ?>

                    <li class="mm_trackingpod">
                      <a class="dropmenu" href="#">
                        <i class="fad fa-chart-network" style="color: #8080FF"></i>
                        <span class="text"> Tracking POD
                        </span> <span class="chevron closed"></span>
                      </a>
                      <ul>
                        <li id="trackingpod_index">
                          <a class="submenu" href="<?= admin_url('trackingpod'); ?>">
                            <i class="fad fa-list" style="color: #80FFFF"></i>
                            <span class="text"> Tracking POD List</span>
                          </a>
                        </li>
                        <li id="trackingpod_add">
                          <a class="submenu" href="<?= admin_url('trackingpod/add'); ?>" data-toggle="modal" data-target="#myModal" data-backdrop="false">
                            <i class="fad fa-plus-circle" style="color: #80FF80"></i>
                            <span class="text"> Add Tracking POD</span>
                          </a>
                        </li>
                      </ul>
                    </li>

                    <?php if ($isAdmin) : ?>
                      <li class="mm_whatsapp">
                        <a class="dropmenu" href="#">
                          <i class="fab fa-whatsapp" style="color: #40FF40"></i>
                          <span class="text"> Whatsapp
                          </span> <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="whatsapp_index">
                            <a class="submenu" href="<?= admin_url('whatsapp'); ?>">
                              <i class="fad fa-send" style="color: #FF40FF"></i>
                              <span class="text"> Sent Messages</span>
                            </a>
                          </li>
                          <li id="whatsapp_profile">
                            <a class="submenu" href="<?= admin_url('whatsapp/profile'); ?>">
                              <i class="fad fa-users-cog" style="color: #4080FF"></i>
                              <span class="text"> Profile</span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (
                      $isAdmin ||
                      $GP['customers-index'] || $GP['suppliers-index']
                    ) : ?>
                      <li class="mm_auth mm_customers mm_suppliers mm_billers">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-users"></i>
                          <span class="text"> <?= lang('people'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || getPermission('users-edit')) : ?>
                            <li id="auth_users">
                              <a class="submenu" href="<?= admin_url('users'); ?>">
                                <i class="fad fa-users"></i><span class="text"> <?= lang('list_users'); ?></span>
                              </a>
                            </li>
                            <li id="auth_create_user">
                              <a class="submenu" href="<?= admin_url('users/create_user'); ?>">
                                <i class="fad fa-user-plus"></i><span class="text"> <?= lang('new_user'); ?></span>
                              </a>
                            </li>
                            <li id="billers_index">
                              <a class="submenu" href="<?= admin_url('billers'); ?>">
                                <i class="fad fa-users"></i><span class="text"> <?= lang('list_billers'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['customers-index']) : ?>
                            <li id="customers_index">
                              <a class="submenu" href="<?= admin_url('customers'); ?>">
                                <i class="fad fa-users"></i><span class="text"> <?= lang('list_customers'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                          <?php if ($isAdmin || $GP['suppliers-index']) : ?>
                            <li id="suppliers_index">
                              <a class="submenu" href="<?= admin_url('suppliers'); ?>">
                                <i class="fad fa-users"></i><span class="text"> <?= lang('list_suppliers'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if ($isAdmin || getPermission('notify-index')) : ?>
                      <li class="mm_notifications">
                        <a class="submenu" href="<?= admin_url('notifications'); ?>">
                          <i class="fad fa-info-circle"></i><span class="text"> <?= lang('notifications'); ?></span>
                        </a>
                      </li>
                      <li class="mm_calendar">
                        <a class="submenu" href="<?= admin_url('calendar'); ?>">
                          <i class="fad fa-calendar-alt"></i><span class="text"> <?= lang('calendar'); ?></span>
                        </a>
                      </li>
                    <?php endif; ?>

                    <?php if ($isAdmin || getPermission('developer')) : ?>
                      <li class="mm_developers">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-code"></i><span class="text"> <?= lang('developers'); ?></span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="developers_api_keys">
                            <a href="<?= admin_url('developers/api_keys') ?>">
                              <i class="fad fa-key"></i><span class="text"> <?= lang('api_keys'); ?></span>
                            </a>
                          </li>
                          <li id="developers_tools">
                            <a href="<?= admin_url('developers/tools') ?>">
                              <i class="fad fa-tools"></i><span class="text"> <?= lang('tools'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>
                    <?php if ($isAdmin || getPermission('edit-system')) : ?>
                      <li class="mm_system_settings">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-cog"></i><span class="text"> <?= lang('settings'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <li id="system_settings_index">
                            <a href="<?= admin_url('system_settings') ?>">
                              <i class="fad fa-cogs"></i><span class="text"> <?= lang('system_settings'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_change_logo">
                            <a href="<?= admin_url('system_settings/change_logo') ?>" data-toggle="modal" data-backdrop="false" data-target="#myModal">
                              <i class="fad fa-upload"></i><span class="text"> <?= lang('change_logo'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_currencies">
                            <a href="<?= admin_url('system_settings/currencies') ?>">
                              <i class="fad fa-money-bill-wave"></i><span class="text"> <?= lang('currencies'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_customer_groups">
                            <a href="<?= admin_url('system_settings/customer_groups') ?>">
                              <i class="fad fa-users-class"></i><span class="text"> <?= lang('customer_groups'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_price_groups">
                            <a href="<?= admin_url('system_settings/price_groups') ?>">
                              <i class="fad fa-layer-group"></i><span class="text"> <?= lang('price_groups'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_price_ranges">
                            <a href="<?= admin_url('system_settings/price_ranges') ?>">
                              <i class="fad fa-dollar-sign"></i><span class="text"> <?= lang('price_ranges'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_categories">
                            <a href="<?= admin_url('system_settings/categories') ?>">
                              <i class="fad fa-folder-open"></i><span class="text"> <?= lang('product_categories'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_expense_categories">
                            <a href="<?= admin_url('system_settings/expense_categories') ?>">
                              <i class="fad fa-folder-open"></i><span class="text"> <?= lang('expense_categories'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_income_categories">
                            <a href="<?= admin_url('system_settings/income_categories') ?>">
                              <i class="fad fa-folder-open"></i><span class="text"> <?= lang('income_categories'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_units">
                            <a href="<?= admin_url('system_settings/units') ?>">
                              <i class="fad fa-wrench"></i><span class="text"> <?= lang('units'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_warehouses">
                            <a href="<?= admin_url('system_settings/warehouses') ?>">
                              <i class="fad fa-warehouse"></i><span class="text"> <?= lang('warehouses'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_user_groups">
                            <a href="<?= admin_url('system_settings/user_groups') ?>">
                              <i class="fad fa-key"></i><span class="text"> <?= lang('group_permissions'); ?></span>
                            </a>
                          </li>
                          <li id="system_settings_backups">
                            <a href="<?= admin_url('system_settings/backups') ?>">
                              <i class="fad fa-database"></i><span class="text"> <?= lang('backups'); ?></span>
                            </a>
                          </li>
                        </ul>
                      </li>
                    <?php endif; ?>

                    <?php if (
                      $isAdmin || $GP['reports-payments'] || $GP['reports-sales'] || getPermission('reports-printerp')
                    ) : ?>
                      <li class="mm_reports">
                        <a class="dropmenu" href="#">
                          <i class="fad fa-chart-bar"></i>
                          <span class="text"> <?= lang('reports'); ?> </span>
                          <span class="chevron closed"></span>
                        </a>
                        <ul>
                          <?php if ($isAdmin || getPermission('reports-daily_performance')) : ?>
                            <li id="reports_daily_performance">
                              <a href="<?= admin_url('reports/daily_performance') ?>">
                                <i class="fad fa-calendar-alt"></i><span class="text"> Daily Performance</span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || getPermission('reports-printerp')) : ?>
                            <li id="reports_printerp">
                              <a href="<?= admin_url('reports/printerp') ?>">
                                <i class="fad fa-file-excel"></i><span class="text"> PrintERP Reports</span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['reports-income_statement']) : ?>
                            <li id="reports_income_statement">
                              <a href="<?= admin_url('reports/income_statement') ?>">
                                <i class="fad fa-dollar-sign"></i><span class="text"> <?= lang('income_statement'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['reports-inventory_balance']) : ?>
                            <li id="reports_inventory_balance">
                              <a href="<?= admin_url('reports/inventory_balance') ?>">
                                <i class="fad fa-box-full"></i><span class="text"> <?= lang('inventory_balance'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['reports-payments']) : ?>
                            <li id="reports_payments">
                              <a href="<?= admin_url('reports/payments') ?>">
                                <i class="fad fa-money-bill-wave"></i><span class="text"> <?= lang('payments_report'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>

                          <?php if ($isAdmin || $GP['reports-sales']) : ?>
                            <li id="reports_sales_status">
                              <a href="<?= admin_url('reports/sales_status') ?>">
                                <i class="fad fa-chart-line"></i><span class="text"> <?= lang('sales_status'); ?></span>
                              </a>
                            </li>
                          <?php endif; ?>
                        </ul>
                      </li>
                    <?php endif; ?>
                  </ul>
                  <!-- /ul nav main-menu -->
                </div>
                <a href="#" id="main-menu-act" class="full visible-md visible-lg">
                  <i class="fad fa-angle-double-left"></i>
                </a>
              </div>
            </td>
            <td class="content-con">
              <div id="content" class="<?= ($isLocal ? 'bg-yellow' : '') ?>">
                <div class="row">
                  <div class="col-sm-12 col-md-12">
                    <ul class="breadcrumb">
                      <li><?= ($isLocal ? 'localhost' : $Settings->site_name) ?></li>
                      <?php
                      foreach ($bc as $b) :
                        if ($b['link'] === '#') {
                          echo '<li class="active">' . $b['page'] . '</li>';
                        } else {
                          echo '<li><a href="' . $b['link'] . '">' . $b['page'] . '</a></li>';
                        }
                      endforeach;
                      ?>
                      <li class="right_log hidden-xs">
                        <?= "<span class='hidden-sm'>" . lang('last_login_at') . ': ' . date($dateFormats['php_ldate'], XSession::get('old_last_login')) . '</span>'; ?>
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="row">
                  <div class="col-lg-12">
                    <?php if ($message) : ?>
                      <div class="alert alert-success">
                        <button data-dismiss="alert" class="close" type="button">x</button>
                        <?= $message; ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($error) : ?>
                      <div class="alert alert-danger">
                        <button data-dismiss="alert" class="close" type="button">x</button>
                        <?= $error; ?>
                      </div>
                    <?php endif; ?>
                    <?php if ($warning) : ?>
                      <div class="alert alert-warning">
                        <button data-dismiss="alert" class="close" type="button">x</button>
                        <?= $warning; ?>
                      </div>
                    <?php endif; ?>
                    <?php
                    if ($info) :
                      foreach ($info as $n) :
                        if (!XSession::get('hidden' . $n->id)) : ?>
                          <div class="alert alert-<?= $n->type; ?>">
                            <a href="#" id="<?= $n->id ?>" class="close hideComment external" data-dismiss="alert">&times;</a>
                            <?= $n->comment; ?>
                          </div>
                    <?php
                        endif;
                      endforeach;
                    endif; ?>
                    <div class="alerts-con"></div>
                    <?= $html_content ?>
                    <div class="clearfix"></div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <div class="clearfix"></div>
    <footer>
      <a href="#" id="toTop" class="blue" style="position: fixed; bottom: 30px; right: 30px; font-size: 30px; display: none;">
        <i class="fa fa-chevron-circle-up"></i>
      </a>

      <p style="text-align:center;">&copy; <?= date('Y') . ' ' . $Settings->site_name; ?>
        <?php
        $_microtime = (microtime(TRUE) - $microtime) * 1000;
        echo " - Loaded in <strong>{$_microtime}</strong> miliseconds"; ?>
      </p>
    </footer>
  </div>
  <div class="modal fade" id="myModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog"></div>
  </div>
  <div class="modal fade" id="myModal2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2" aria-hidden="true">
    <div class="modal-dialog"></div>
  </div>
  <div class="modal fade" id="myModal3" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="myModalLabel3" aria-hidden="true">
    <div class="modal-dialog"></div>
  </div>
  <div id="modal-loading" style="display: none;">
    <div class="blackbg"></div>
    <div class="loader"></div>
  </div>
  <div id="ajaxCall">
    <i class="fa fa-sync fa-spin"></i>
    <span class="ajax-call-label">Processing...</span>
  </div>
  <script type="text/javascript">
    var dt_lang = <?= $dt_lang ?>,
      dp_lang = <?= $dp_lang ?>;
    var lang = {
      paid: '<?= lang('paid'); ?>',
      pending: '<?= lang('pending'); ?>',
      completed: '<?= lang('completed'); ?>',
      ordered: '<?= lang('ordered'); ?>',
      received: '<?= lang('received'); ?>',
      partial: '<?= lang('partial'); ?>',
      sent: '<?= lang('sent'); ?>',
      r_u_sure: '<?= lang('r_u_sure'); ?>',
      due: '<?= lang('due'); ?>',
      returned: '<?= lang('returned'); ?>',
      active: '<?= lang('active'); ?>',
      inactive: '<?= lang('inactive'); ?>',
      unexpected_value: '<?= lang('unexpected_value'); ?>',
      select_above: '<?= lang('select_above'); ?>',
      download: '<?= lang('download'); ?>',
      required_invalid: '<?= lang('required_invalid'); ?>'
    };
    lang.completed_partial = "<?= lang('completed_partial'); ?>";
    lang.need_payment = "<?= lang('need_payment'); ?>";
    lang.waiting_production = "<?= lang('waiting_production'); ?>";
    lang.in_production = "<?= lang('in_production'); ?>";
    lang.installed = "<?= lang('installed'); ?>";
    lang.delivered = "<?= lang('delivered'); ?>";
    lang.approved = "<?= lang('approved'); ?>";
    lang.draft = "<?= lang('draft'); ?>";
    lang.need_approval = "<?= lang('need_approval'); ?>";
    lang.packing = "<?= lang('packing'); ?>";
    lang.quantity = "<?= lang('quantity'); ?>";
    lang.due_partial = "<?= lang('due_partial'); ?>";
    lang.expired = "<?= lang('expired'); ?>";
    lang.verified = "<?= lang('verified'); ?>";
    lang.waiting_transfer = "<?= lang('waiting_transfer'); ?>";
    lang.received_partial = "<?= lang('received_partial'); ?>";
    lang.preparing = "<?= lang('preparing'); ?>";
  </script>
  <script src="<?= $assets; ?>js/icheck.js"></script>
  <script src="<?= $assets; ?>js/bootstrap.bundle.js"></script>
  <script src="<?= $assets; ?>js/jquery.dataTables.min.js"></script>
  <script src="<?= $assets; ?>js/jquery.dataTables.dtFilter.min.js"></script>
  <?php if (
    ($m == 'products' && $v == 'stock_opname') ||
    ($m == 'products' && $v == 'mutation') ||
    ($m == 'products' && $v == 'transfer') ||
    ($m == 'finances' && $v == 'reconciliations') ||
    ($m == 'google') ||
    ($m == 'developers') ||
    ($m == 'machines') ||
    ($m == 'procurements' && $v == 'purchases2') ||
    ($m == 'procurements' && $v == 'purchases' && $x == 'plan') ||
    ($m == 'procurements' && $v == 'transfers' && $x == 'plan') ||
    ($m == 'qms') ||
    ($m == 'schedule') ||
    ($m == 'trackingpod') ||
    ($m == 'whatsapp')
  ) { ?>
    <!-- NEW DATATABLES -->
    <script type="text/javascript" src="<?= $assets; ?>plugins/datatables/datatables.min.js"></script>
  <?php }
  ?>
  <script src="<?= $assets; ?>js/select2.full.min.js"></script>
  <script src="<?= $assets; ?>plugins/bootstrap-validate-2.2.0/dist/bootstrap-validate.js"></script>
  <script src="<?= $assets; ?>plugins/ejs/ejs.min.js"></script>
  <script src="<?= $assets; ?>plugins/jquery-contextmenu-2.9.2/jquery.contextMenu.min.js"></script>
  <script src="<?= $assets; ?>plugins/toastr/toastr.min.js"></script>
  <script src="<?= $assets; ?>js/jquery-ui.js"></script>
  <script src="<?= $assets; ?>js/bootstrapValidator.js"></script>
  <script src="<?= $assets; ?>js/redactor.js"></script>
  <script src="<?= $assets; ?>js/custom.js"></script>
  <script src="<?= $assets; ?>js/jquery.calculator.min.js"></script>
  <script src="<?= $assets; ?>js/core.js?<?= $res_hash; ?>"></script>
  <script src="<?= $assets; ?>js/perfect-scrollbar.min.js"></script>
  <script src="<?= $assets; ?>js/tabulator.min.js"></script>
  <script src="<?= $assets; ?>qms/js/alertify.min.js"></script>
  <script src="<?= base_url() ?>websocket.js"></script>

  <?= ($m == 'purchases' && ($v == 'add' || $v == 'edit' || $v == 'purchase_by_csv')) ? '<script src="' . $assets . 'js/purchases.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'procurements' && ($v == 'internal_uses')) ? '<script src="' . $assets . 'js/internal_uses.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'procurements' && ($v == 'transfers')) ? '<script src="' . $assets . 'js/transfers.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'procurements' && ($v == 'purchases')) ? '<script src="' . $assets . 'js/purchases.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'sales' && ($v == 'add' || $v == 'edit' || $v == 'edit_operator' || $v == 'pick_pic')) ? '<script src="' . $assets . 'js/sales.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'products' && ($v == 'add_adjustment' || $v == 'edit_adjustment')) ? '<script src="' . $assets . 'js/adjustments.js?' . $res_hash . '"></script>' : ''; ?>
  <?= ($m == 'products' && $v == 'stock_opname') ? '<script src="' . $assets . 'js/stock_opname.js?' . $res_hash . '"></script>' : ''; ?>

  <script>
    var oTable = '';
    <?php $xm = (!empty($x) && !is_numeric($x) ? "_{$x}" : ''); ?>
    $(window).on('load', function() {
      // console.log(`menu <?= $m ?>_<?= $v ?><?= $xm ?>`);
      $('.mm_<?= $m ?>').addClass('active');
      $('.mm_<?= $m ?>').find("ul").first().slideToggle();
      $('#<?= $m ?>_<?= $v ?><?= $xm ?>').addClass('active'); // Custom PrintERP.
      $('.mm_<?= $m ?> a .chevron').removeClass("closed").addClass("opened");
    });
  </script>
  <script src="<?= base_url('assets/js/common.js?' . $res_hash); ?>"></script>
  <script src="<?= base_url('assets/js/product_mutation.js?' . $res_hash); ?>"></script>
  <script src="<?= base_url('assets/js/product_transfer.js?' . $res_hash); ?>"></script>
  <script src="<?= base_url('assets/js/notificator.js?' . $res_hash); ?>"></script>
</body>

</html>