<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$q = '';
$bils = $this->input->get('biller');
$start_date = $this->input->get('start_date');
$end_date = $this->input->get('end_date');

?>
<script>
  let billers = null;
  let start_date = null;
  let end_date = null;

  $(document).ready(function() {
    billers = <?= ($bils ? json_encode($bils) : 'null'); ?>;
    start_date = <?= ($start_date ? "'" . $start_date . "'" : 'null'); ?>;
    end_date = <?= ($end_date ? "'" . $end_date . "'" : 'null'); ?>;

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
    <h2 class="blue"><i class="fad fa-fw fa-dollar-sign"></i><?= lang('income_statement'); ?>
      <?php
      if ($start_date) {
        echo 'From ' . $start_date . ' to ' . $end_date;
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
        <?php
        $billerName = NULL;

        if ($bils) {
          foreach ($bils as $bil) {
            $biller = $this->site->getBillerByID($bil);
            $billNames[] = $biller->name;
          }

          $billerName = implode(', ', $billNames);
        }
        ?>
        <p class="introtext"><strong><?= lang('biller'); ?></strong>: <?= ($billerName ?? 'All Billers (Except Lucretia)'); ?></p>

        <div id="form_filter" class="closed well well-sm">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <?= lang('biller', 'biller'); ?>
                <?php
                $billers = $this->site->getBillers(['order' => ['name', 'ASC']]);
                $bills = [];

                foreach ($billers as $bill) {
                  $bills[$bill->id] = $bill->name;
                }

                echo form_multiselect('biller', $bills, $bils, 'class="select2" id="biller" style="width:100%;"');
                ?>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('start_date', 'start_date'); ?>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $start_date ?>">
              </div>
            </div>

            <div class="col-sm-2">
              <div class="form-group">
                <?= lang('end_date', 'end_date'); ?>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= $end_date ?>">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-12">
              <div class="form-group">
                <?php echo form_button('filter_submit', 'Submit', 'class="btn btn-primary" id="filter_submit"'); ?>
                <a href="<?= admin_url('reports/income_statement'); ?>" class="btn btn-danger">Reset</a>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="income_statement" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="clearfix"></div>
        <div class="table-responsive">
          <table id="Table" class="table table-striped table-bordered table-condensed table-hover dfTable reports-table" style="margin-bottom:5px;">
            <thead>
              <tr class="active">
                <th>Reference</th>
                <th>Value</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="dtFilter">
              <tr class="active">
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  class IncomeStatementReport {
    constructor() {
      this.tbody = $('#Table').find('tbody');
    }

    addRow(row, className = '') {
      if (isObject(row)) {
        this.tbody.append(`<tr><td class="${className}">${row.key}</td><td class="text-right ${className}">${formatMoney(row.value, 'Rp')}</td></tr>`);
      }
    }

    clean() {
      this.tbody.empty();
    }

    load() {
      let data = {};

      this.tbody.html(`<tr><td colspan="2" class="dataTables_empty">Loading data from server</td></tr>`);

      if (billers) {
        data.biller = billers;
      }

      if (start_date) {
        data.start_date = start_date;
      }

      if (end_date) {
        data.end_date = end_date;
      }

      $.ajax({
        method: 'GET',
        success: (data) => {
          if (isObject(data) && !data.error) {
            this.clean();

            for (let row of data.data) {
              if (row.name) {
                if (!isArray(row.data)) {
                  this.addRow({
                    key: row.name,
                    value: row.amount
                  }, 'bold');
                } else if (isArray(row.data)) {
                  this.addRow({
                    key: row.name,
                    value: row.amount
                  }, 'bold');

                  for (let subRow of row.data) {
                    this.addRow({
                      key: '--> ' + subRow.name,
                      value: subRow.amount
                    });
                  }
                }
              }
            }
          }
        },
        url: site.base_url + 'reports/getIncomeStatementReport?' + serialize(data)
      });
    }

    reload() {
      this.clean();
      this.load();
    }
  }

  $(document).ready(function() {
    let report = new IncomeStatementReport();

    report.load();

    $('#filter_submit').on('click', function() {
      let q = '';
      let billers = $('#biller').val();
      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();

      if (biller) {
        for (let bill of billers) {
          q += `&biller[]=${bill}`;
        }
      }

      if (start_date) q += `&start_date=${start_date}`;
      if (end_date) q += `&end_date=${end_date}`;

      location.href = '<?= admin_url('reports/income_statement?'); ?>' + q;
    });

    $('#export').click(function() {
      let q = '';
      let billers = $('#biller').val();
      let start_date = $('#start_date').val();
      let end_date = $('#end_date').val();

      if (biller) {
        for (let bill of billers) {
          q += `&biller[]=${bill}`;
        }
      }

      if (start_date) q += `&start_date=${start_date}`;
      if (end_date) q += `&end_date=${end_date}`;

      location.href = '<?= admin_url('reports/getIncomeStatementReport?xls=1'); ?>' + q;
    });
  });
</script>