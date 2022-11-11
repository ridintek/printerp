<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<?php
  $hash = ( ! empty($hash) ? $hash : bin2hex(random_bytes(4)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Display</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if ($isLocal): // If local, then use indonesian flag. ?>
    <link rel="icon" href="<?= site_url(); ?>assets/pwa/images/favicon.ico" />
  <?php else: ?>
    <link rel="icon" href="<?= site_url(); ?>assets/pwa/images/favicon.ico" />
  <?php endif; ?>
  <link href="<?= $assets . 'qms/fonts/fontawesome/css/all.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/jquery-ui.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/jquery-ui.structure.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/jquery-ui.theme.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/bootstrap.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/bootstrap-table.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/themes/semantic.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/alertify.min.css'; ?>" rel="stylesheet" />
  <link href="<?= $assets . 'qms/css/ridintek.css?' . $hash; ?>" rel="stylesheet" />
</head>
<style>
</style>
<body class="overflow-hidden">
  <div class="display-container">
    <div class="row display-header-line">
      <div class="col-4">
        <div class="header-image"></div>
      </div>
      <div class="col">
        <div class="row">
          <div class="col">
            <div class="display-header-branch"><?= ( ! empty($warehouse) ? strtoupper($warehouse->name) : ''); ?></div>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <div id="header_datetime" class="display-header-datetime"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-4">
      <div class="table-header">SEDANG DILAYANI</div>
        <table id="CounterTable" class="counter-table">
          <thead>
            <tr>
              <th data-field="counter">COUNTER</th>
              <th data-field="ticket">TIKET</th>
              <th data-field="category">LAYANAN</th>
            </tr>
          </thead>
          <tbody class="counter-list"></tbody>
        </table>
      </div>
      <div class="col-4">
        <div class="table-header">DAFTAR TUNGGU</div>
        <table id="WaitingTable" class="waiting-table" >
          <thead>
            <tr>
              <th data-field="time">JAM</th>
              <th data-field="ticket">TIKET</th>
              <th data-field="name">NAMA</th>
            </tr>
          </thead>
          <tbody class="waiting-list"></tbody>
        </table>
      </div>
      <div class="col-4">
        <div class="table-header">DAFTAR TERLEWAT</div>
        <table id="SkipTable" class="skip-table" >
          <thead>
            <tr>
              <th data-field="time">JAM</th>
              <th data-field="ticket">TIKET</th>
              <th data-field="name">NAMA</th>
            </tr>
          </thead>
          <tbody class="skip-list"></tbody>
        </table>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <div class="marquee">
          <div class="marquee-content">
            <marquee scrollamount="10">SELAMAT DATANG DI INDOPRINTING.
              SILAKAN AMBIL NOMOR ANTRIAN ANDA.
              BATAS WAKTU PELAYANAN SIAP CETAK 10 MENIT DAN EDIT DESIGN 20 MENIT.
              ANTRIAN YANG TERLEWAT MEMILIKI PRIORITAS UNTUK DIPANGGIL TERLEBIH DAHULU.
              JIKA ANTRIAN ANDA TERLEWAT, SILAKAN KE CS UNTUK MEMANGGIL ANTRIAN ANDA.
              ANTRIAN YANG TERLEWAT MEMILIKI WAKTU 15 MENIT UNTUK DIPANGGIL.
              ANTRIAN YANG TERLEWAT LEBIH DARI 15 MENIT DIANGGAP TIDAK VALID DAN HARUS MENGAMBIL ANTRIAN ULANG.
              TERIMA KASIH.
            </marquee>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="popup popup-hidden">
    <div class="popup-content">
      <div class="popup-header">NOMOR ANTRIAN</div>
      <div class="popup-header">
        <span style="color:cyan" data-content="category"></span>
        <span class="text-lime" data-content="ticket"></span>
      </div>
      <div class="popup-body">DIPERSILAKAN KE</div>
      <div class="popup-footer"><span class="text-yellow" data-content="counter"></span></div>
    </div>
  </div>
  <script>window.security = {csrf_token_name: '<?= $this->security->get_csrf_token_name(); ?>', csrf_hash: '<?= $this->security->get_csrf_hash(); ?>'};</script>
  <script>window.site = {base_url: '<?= admin_url(); ?>', url: '<?= base_url(); ?>'};</script>
  <script src="<?= $assets . 'qms/js/jquery-3.4.1.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/jquery-ui.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/chart.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/bootstrap.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/bootstrap-table.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/alertify.min.js'; ?>"></script>
  <script src="<?= $assets . 'qms/js/ridintek.js?' . $hash; ?>"></script>
  <script src="<?= $assets . 'qms/js/tableExport.min.js'; ?>"></script>
  <script>
  "use strict";
  window.QHttp = new QueueHttp();
  window.QMS = new QueueManagementSystem();
  let active_display = <?= ( ! empty($active_display) ? $active_display : '0'); ?>;
  let bell = new Audio('<?= $assets . "qms/audio/airport-notification.mp3"; ?>');
  let counter_table = null;
  let waiting_table = null;
  let skip_table = null
  let on_call = false;
  let warehouse_id = <?= ( ! empty($warehouse) ? $warehouse->id : 'null'); ?>;

  async function DisplayMessage () {
    try {
      let display_data = await QMS.getDisplayData(warehouse_id);

      // console.log(display_data);

      if ( ! display_data.call.error && ! on_call) {
        let call = display_data.call.data;

        bell.play();
        on_call = true;

        $('[data-content="category"]').html(call.queue_category_name.toUpperCase());
        $('[data-content="ticket"]').html(call.token);
        $('[data-content="counter"]').html(`CS ${call.counter}`);

        $('.popup').show();

        if (active_display) QMS.sendDisplayResponse(call.id);

        setTimeout(() => {
          let utterance = new SpeechSynthesisUtterance();
          utterance.lang = 'id-ID';
          utterance.text = `Nomor antrian ${call.queue_category_name}. ${separate_char(call.token)}. Dipersilakan ke CS ${call.counter}`;
          utterance.rate = .7;
          utterance.pitch = 1;
          utterance.volume = 1;
          speechSynthesis.speak(utterance);
        }, 5000);

        setTimeout(() => {
          $('.popup').fadeOut();
          on_call = false;
        }, 10 * 1000);
      }

      if ( ! display_data.counter.error) {
        let counters = display_data.counter.data;

        counter_table.bootstrapTable('removeAll');

        for (let a in counters) {
          counter_table.bootstrapTable('append', [{counter: `CS ${counters[a].counter} (${counters[a].name})`, ticket: counters[a].token, category: counters[a].category_name}]);
        }
      } else if (counter_table.bootstrapTable('getData').length > 0) {
        counter_table.bootstrapTable('removeAll');
      }

      if ( ! display_data.queue_list.error) {
        let queue_list = display_data.queue_list.data;
        let count = 1;

        waiting_table.bootstrapTable('removeAll');

        for (let a in queue_list) {
          let estCallDate = new Date(queue_list[a].est_call_date);
          let call_date = append_zero(estCallDate.getHours()) + ':' + append_zero(estCallDate.getMinutes());
          waiting_table.bootstrapTable('append', [{time: call_date, ticket: queue_list[a].token, name: queue_list[a].customer_name.split(' ')[0]}]);
          count++;
        }
      } else if (waiting_table.bootstrapTable('getData').length > 0) {
        waiting_table.bootstrapTable('removeAll');
      }

      if ( ! display_data.skip_list.error) {
        let skip_list = display_data.skip_list.data;
        let count = 1;

        skip_table.bootstrapTable('removeAll');

        for (let a in skip_list) {
          let estCallDate = new Date(skip_list[a].est_call_date);
          let call_date = append_zero(estCallDate.getHours()) + ':' + append_zero(estCallDate.getMinutes());
          skip_table.bootstrapTable('append', [{time: call_date, ticket: skip_list[a].token, name: skip_list[a].customer_name.split(' ')[0]}]);
          count++;
        }
      } else if (skip_table.bootstrapTable('getData').length > 0) {
        skip_table.bootstrapTable('removeAll');
      }
    } catch (e) {
      console.groupCollapsed('%cDisplay Error', 'color:yellow; font-weight:bold');
      console.warn(e);
      console.groupEnd();
    }

    window.setTimeout(DisplayMessage, 5000);
  }

  $(document).ready(function () {
    counter_table = $('#CounterTable');
    waiting_table = $('#WaitingTable');
    skip_table = $('#SkipTable');

    counter_table.bootstrapTable();
    waiting_table.bootstrapTable();
    skip_table.bootstrapTable();

    if ( ! active_display) {
      /**
       * Indicator for Passive Display. Active Display is a display that will call and change
       * the ticket status while Passive Display will call a ticket only or just for monitoring status.
       */

      $('.display-header-branch').css('text-shadow', '3px 3px 5px red');
    }

    setInterval(() => { // For Clock
      let d = new Date();
      let month =['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL',
      'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
      let day = ['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];

      $('#header_datetime').html(
        `${day[d.getDay()]}, ${append_zero(d.getDate())}-${month[d.getMonth()]}-${d.getFullYear()}
        ${append_zero(d.getHours())}:${append_zero(d.getMinutes())}:${append_zero(d.getSeconds())}`
      );
    }, 500);

    $(document).dblclick(() => {
      if ( ! document.fullscreenElement) {
        document.documentElement.requestFullscreen();
      } else {
        document.exitFullscreen();
      }
    });

    window.setTimeout(DisplayMessage, 5000);
  });
  </script>
</body>
</html>