<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';

?>
<script>
  $(document).ready(function() {
    $('#filter').click((e) => {
      if ($('#form_filter').hasClass('closed')) {
        $('#form_filter').removeClass('closed');
        $('#form_filter').addClass('opened');
        $('#form_filter').slideDown();
      } else if ($('#form_filter').hasClass('opened')) {
        $('#form_filter').removeClass('opened');
        $('#form_filter').addClass('closed');
        $('#form_filter').slideUp();
      }
      e.preventDefault();
    });

    $('#dtfilter').datatableFilter();

    $('#form_filter').hide();
  });
</script>

<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-fw fa-calendar-alt"></i><?= lang('daily_performance'); ?>
      <?php
      if ($period) {
        echo "Period {$period}";
      } ?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="icon fad fa-download tip" data-placement="left" title="Export Excel"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu">
            <li><a href="#" id="export"><i class="fa fa-file-excel"></i> Export Excel</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fa fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext"><strong><?= lang('biller'); ?></strong></p>

        <div id="form_filter" class="closed well well-sm">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label for="period">Period</label>
                <input type="month" id="period" name="period" class="form-control" value="<?= $period ?>">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_button('filter_submit', 'Submit', 'class="btn btn-primary" id="filter_submit"'); ?>
                <a href="<?= admin_url('reports/daily_performance'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="daily_performance" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="table-responsive" style="max-width: 75vw; overflow: auto;">
          <table id="Table" class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
            <thead>
              <tr class="active">
                <th rowspan="2">Biller</th>
                <th rowspan="2">Target</th>
                <th rowspan="2">Revenue</th>
                <th rowspan="2">Avg Revenue</th>
                <th rowspan="2">Forecast</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="dtFilter">
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  class DailyPerformanceReport {
    constructor() {
      this.tbody = $('#Table').find('tbody');
    }

    addRow(row, className = '') {
      if (isObject(row)) {
        let rows = `
        <tr>
          <td class="${className}">${row.biller}</td>
          <td class="text-right ${className}">${formatMoney(row.target, 'Rp')}</td>
          <td class="text-right ${className}">${formatMoney(row.revenue, 'Rp')}</td>
          <td class="text-right ${className}">${formatMoney(row.avg_revenue, 'Rp')}</td>
          <td class="text-right ${className}">${formatMoney(row.forecast, 'Rp')}</td>`;

        for (let a = 0; a < 31; a++) {
          if (typeof row.daily_data[a] == 'undefined') { // Protect overflow array.
            rows += `
              <td class="text-right ${className}">-</td>
              <td class="text-right ${className}">-</td>
              <td class="text-right ${className}">-</td>
              `;
            continue;
          }

          rows += `
            <td class="text-right ${className}">${formatMoney(row.daily_data[a].revenue, 'Rp')}</td>
            <td class="text-right ${className}">${formatMoney(row.daily_data[a].stock_value, 'Rp')}</td>
            <td class="text-right ${className}">${formatMoney(row.daily_data[a].piutang, 'Rp')}</td>
            `;
        }

        rows += '</tr>';

        this.tbody.append(rows);
      }
    }

    clean() {
      this.tbody.empty();
    }

    load() {
      let data = {};
      // colTotal(31 days * 3 sub-columns[revenue,stock value,...]) + general columns[biller,target,...].
      let colTotal = (31 * 3) + 5;

      data.period = $('#period').val();

      this.tbody.html(`<tr><td colspan="${colTotal}" class="dataTables_empty">Loading data from server</td></tr>`);

      $.ajax({
        method: 'GET',
        success: (data) => {
          if (isObject(data) && data.status == 200) {
            this.clean();

            console.log(data);

            for (let row of data.data) {
              this.addRow(row);
            }
          }
        },
        url: site.base_url + 'reports/getDailyPerformanceReport?' + serialize(data)
      });
    }

    reload() {
      this.clean();
      this.load();
    }
  }

  $(document).ready(function() {
    let headers = '',
      tr2 = '',
      footers = '';
    let report = new DailyPerformanceReport();

    tr2 = '<tr>';

    for (let a = 1; a <= 31; a++) {
      headers += `<th colspan="3">Day ${a}</th>`;
      tr2 += `
        <th class="text-center">Revenue</th>
        <th class="text-center">Stock Value</th>
        <th class="text-center">Piutang</th>
        `;
    }

    tr2 += '</tr>';

    if (headers.length > 0) {
      $('#Table thead tr').append(headers);
      $('#Table thead').append(tr2);
    }

    report.load(); // First load.

    $('#filter_submit').on('click', function() {
      let q = '';
      let period = $('#period').val();

      if (period) q += 'period=' + period;

      location.href = '<?= admin_url('reports/daily_performance?'); ?>' + q;
    });

    $('#export').click(function() {
      let q = '';
      let period = $('#period').val();

      if (period) q += '&period=' + period;

      location.href = '<?= admin_url('reports/getDailyPerformanceReport?xls=1'); ?>' + q;
    });
  });
</script>