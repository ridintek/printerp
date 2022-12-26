<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    var ti = 0;
    $(document).on('change', '.price', function () {
      var row = $(this).closest('tr');
      row.first('td').find('input[type="checkbox"]').iCheck('check');
    });
    $(document).on('click', '.form-submit', function () {
      var btn = $(this);
      btn.html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>');
      var row = btn.closest('tr');
      var product_id = row.attr('id');
      var price = row.find('.price').val();
      var price2 = row.find('.price2').val();
      var price3 = row.find('.price3').val();
      var price4 = row.find('.price4').val();
      var price5 = row.find('.price5').val();
      var price6 = row.find('.price6').val();
      console.log('This running when click one button per row.');
      $.ajax({
        type: 'post',
        url: '<?= admin_url('system_settings/update_product_group_price/' . $price_group->id); ?>',
        dataType: "json",
        data: {
          <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>',
          product_id: product_id, price: price, price2: price2, price3: price3, price4: price4, price5: price5, price6: price6
        },
        success: function (data) {
          if (data.status != 1)
            btn.removeClass('btn-primary').addClass('btn-danger').html('<i class="fa fa-times"></i>');
          else
            btn.removeClass('btn-primary').removeClass('btn-danger').addClass('btn-success').html('<i class="fa fa-check"></i>');
        },
        error: function (data) {
          btn.removeClass('btn-primary').addClass('btn-danger').html('<i class="fa fa-times"></i>');
        }
      });
      // btn.html('<i class="fa fa-check"></i>');
    });
    function price_input(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }

    function price_input2(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price2_"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price2\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }
    function price_input3(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price3_"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price3\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }
    function price_input4(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price4_"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price4\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }
    function price_input5(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price5_"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price5\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }
    function price_input6(x) {
      ti = ti+1;
      var v = x.split('__');
      return "<div class=\"text-center\"><input type=\"text\" name=\"price6_"+v[0]+"\" value=\""+(v[1] != '' ? formatDecimals(v[1]) : '')+"\" class=\"form-control text-center price6\" style=\"padding:2px;height:auto;\"></div>"; // onclick=\"this.select();\"
    }
  
    oTable = $('#Table').dataTable({
      "aaSorting": [[1, "asc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
      "iDisplayLength": <?= $Settings->rows_per_page ?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?= admin_url('system_settings/getProductPrices/' . $price_group->id) ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?= $this->security->get_csrf_token_name() ?>",
          "value": "<?= $this->security->get_csrf_hash() ?>"
        });
        console.log('sSource: ', sSource);
        console.log('aoData: ', aoData);
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        nRow.id = aData[0];
        nRow.className = "product_group_price_id";
        return nRow;
      },
      "aoColumns": [{"bSortable": false, "mRender": checkbox},
        null, null, {"bSortable": false, "mRender": price_input}
        <?php if ($price_ranges) : // Create column property.
          $x = 2;
          foreach ($price_ranges as $price_range) {
            echo("{\"bSortable\": false, \"mRender\": price_input{$x}},");
            $x++;
          }
        endif;?>,
        {"bSortable": false}]
    }).fnSetFilteringDelay();

    $('#dtfilter').datatableFilter();

    $('#myModal').on('hidden.bs.modal', function () {
			if (typeof mainTable != 'undefined') oTable = mainTable;
		});
  });
</script>
<?= admin_form_open('system_settings/product_group_price_actions/' . $price_group->id, 'id="action-form"') ?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-building"></i><?= $page_title ?> (<?= $price_group->name; ?>)</h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
          <i class="icon fa fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="#" id="update_price" data-action="update_price">
              <i class="fa fa-dollar"></i> <?= lang('update_price') ?>
              </a>
            </li>
            <li>
              <a href="<?php echo admin_url('system_settings/update_prices_csv/' . $price_group->id); ?>" data-toggle="modal" data-target="#myModal">
              <i class="fa fa-upload"></i> <?= lang('update_prices_csv') ?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
              <i class="fa fa-file-excel-o"></i> <?= lang('export_to_excel') ?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" id="delete" data-action="delete">
              <i class="fa fa-trash-o"></i> <?= lang('delete_product_group_prices') ?>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <div class="row">
          <div class="col-sm-3">
            <input id="dtfilter" class="form-control dtfilter" data-name="group_product_price" placeholder="<?= lang('search'); ?>">
          </div>
        </div>
        <div class="table-responsive table-overflow">
          <table id="Table" class="table table-bordered table-hover table-striped reports-table">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkth" type="checkbox" name="check"/>
                </th>
                <th class="col-xs-3"><?= lang('product_code'); ?></th>
                <th class="col-xs-4"><?= lang('product_name'); ?></th>
                <th><?= lang('Price Range 1'); ?></th>
                <?php
                  if ($price_ranges) { // Create column header.
                    foreach ($price_ranges as $price_range) {
                      echo('<th>Price ' . $price_range->name . '</th>');
                    }
                  }
                ?>
                <th style="width:85px;"><?= lang('update'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="5" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<div style="display: none;">
  <input type="hidden" name="form_action" value="" id="form_action"/>
  <?= form_submit('submit', 'submit', 'id="action-form-submit"') ?>
</div>
<?= form_close() ?>
<script language="javascript">
  $(document).ready(function () {
  
    $('#delete').click(function (e) {
      e.preventDefault();
      $('#form_action').val($(this).attr('data-action'));
      $('#action-form-submit').trigger('click');
    });
  
    $('#excel').click(function (e) {
      e.preventDefault();
      $('#form_action').val($(this).attr('data-action'));
      $('#action-form-submit').trigger('click');
    });
  
    $('#pdf').click(function (e) {
      e.preventDefault();
      $('#form_action').val($(this).attr('data-action'));
      $('#action-form-submit').trigger('click');
    });
  
    $('#update_price').click(function (e) {
      e.preventDefault();
      $('#form_action').val($(this).attr('data-action'));
      $('#action-form-submit').trigger('click');
    });
  
  });
</script>