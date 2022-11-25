<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-book"></i>PrintERP Reports</h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu">
            <li>
              <a href="#">
                <i class="fad fa-fw fa-plus-circle"></i> Add Something
              </a>
            </li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-check"></i> Action Something
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-trash"></i> Delete Something
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fad fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <script>
    $('#filter').click((e) => {
      e.preventDefault();
      if ($('#form_filter').hasClass('closed')) {
        $('#form_filter').removeClass('closed').addClass('opened').slideDown();
      } else if ($('#form_filter').hasClass('opened')) {
        $('#form_filter').removeClass('opened').addClass('closed').slideUp();
      }
    });
  </script>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext">Your information text will be here</p>
        <!-- Filter Form -->
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label for="input1"><i class="fad fa-building"></i> Billers</label>
                <select id="biller" class="select2" name="biller[]" style="width:100%;" multiple>
                  <?php foreach ($this->site->getAllBillers() as $biller) : ?>
                    <option value="<?= $biller->id ?>"><?= $biller->name ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label for="input1"><i class="fad fa-warehouse"></i> Warehouses</label>
                <select id="warehouse" class="select2" name="warehouse[]" style="width:100%;" multiple>
                  <?php foreach ($this->site->getAllWarehouses() as $warehouse) : ?>
                    <option value="<?= $warehouse->id ?>"><?= $warehouse->name ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-2">
              <div class="form-group">
                <label for="startDate"><i class="fad fa-clock"></i> Start Date</label>
                <input class="form-control" id="startDate" type="date">
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <label for="endDate"><i class="fad fa-clock"></i> End Date</label>
                <input class="form-control" id="endDate" type="date">
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-danger btn-block" href="#" id="XlsMachine"><i class="fad fa-fw fa-file-excel"></i> Report Machine &amp; Equipment</a>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-success btn-block" href="#" id="XlsQMS"><i class="fad fa-fw fa-file-excel"></i> Report QMS</a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-primary btn-block" href="#" id="XlsSales"><i class="fad fa-fw fa-file-excel"></i> Report Sales &amp; Production</a>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-warning btn-block" href="#" id="XlsSalesPiutang"><i class="fad fa-fw fa-file-excel"></i> Report Piutang Sales</a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-info btn-block" href="#" id="XlsTrackingPOD"><i class="fad fa-fw fa-file-excel"></i> Report Tracking POD</a>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-default btn-block" href="#" id="XlsTransfers"><i class="fad fa-fw fa-file-excel"></i> Report Transfers</a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-danger btn-block" href="#" id="XlsCOH"><i class="fad fa-fw fa-file-excel"></i> Report Setoran COH</a>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-success btn-block" href="#" id="XlsBalanceSheet"><i class="fad fa-fw fa-file-excel"></i> Report Balance Sheet</a>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-primary btn-block" href="#" id="XlsSoldItem"><i class="fad fa-fw fa-file-excel"></i> Report Sold Items</a>
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <a class="btn btn-warning btn-block" href="#" id="XlsUsability"><i class="fad fa-fw fa-file-excel"></i> Report Sparepart Usability</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /.box-content -->
</div>
<script>
  $(document).ready(function() {
    $('#XlsMachine').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/machines?' + q;
    });

    $('#XlsQMS').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'qms/getQueues?xls=2' + q;
    });

    $('#XlsSales').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/sales?' + q;
    });

    $('#XlsSalesPiutang').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/sales/piutang?' + q;
    });

    $('#XlsTrackingPOD').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/trackingPODs?' + q;
    });

    $('#XlsTransfers').click(function() {

    });

    $('#XlsCOH').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/cohs?' + q;
    });

    $('#XlsBalanceSheet').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/balancesheet?' + q;
    });

    $('#XlsSoldItem').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();
      let billerId = $('#biller').val();

      // console.log(billerId);

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      if (billerId) {
        q += '&biller=' + billerId;
      }

      // console.log(q);
      // return false;

      location.href = site.base_url + 'reports/getSoldItems?' + q;
    });

    $('#XlsUsability').click(function() {
      let q = '';
      let startDate = $('#startDate').val();
      let endDate = $('#endDate').val();

      if (startDate) {
        q += '&start_date=' + startDate;
      }

      if (endDate) {
        q += '&end_date=' + endDate;
      }

      location.href = site.base_url + 'reports/getUsabilityReport?' + q;
    });
  });
</script>