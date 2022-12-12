<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function () {
    oTable = $('#SLData').dataTable({
      "aaSorting": [[1, "desc"], [2, "desc"]],
      "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?=lang('all')?>"]],
      "iDisplayLength": <?=$Settings->rows_per_page?>,
      'bProcessing': true, 'bServerSide': true,
      'sAjaxSource': '<?=admin_url('sales/getSalesItem' . ($warehouse_id ? '/' . $warehouse_id : '') . '?v=1' . (getGET('shop') ? '&shop=' . getGET('shop') : '') . (getGET('attachment') ? '&attachment=' . getGET('attachment') : '') . (getGET('delivery') ? '&delivery=' . getGET('delivery') : '')); ?>',
      'fnServerData': function (sSource, aoData, fnCallback) {
        aoData.push({
          "name": "<?=$this->security->get_csrf_token_name()?>",
          "value": "<?=$this->security->get_csrf_hash()?>"
        });
        $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
      },
      'fnRowCallback': function (nRow, aData, iDisplayIndex) {
        var oSettings = oTable.fnSettings();
        //$("td:first", nRow).html(oSettings._iDisplayStart+iDisplayIndex +1);
        nRow.id = aData[0];
        nRow.className = "sales_item_link";
        //if(aData[7] > aData[9]){ nRow.className = "product_link warning"; } else { nRow.className = "product_link"; }
        return nRow;
      },
      "aoColumns": [{"bSortable": false, "mRender": checkbox}, {"mRender": fld}, null, null, null, null, null,
        {"mRender": sales_item_status_properties}, {"mRender": pay_status}, {"bSortable": false}],
      "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {

      }
    }).fnSetFilteringDelay().dtFilter([
      {column_number: 1, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text", data: []},
      {column_number: 2, filter_default_label: "[<?=lang('reference');?>]", filter_type: "text", data: []},
      {column_number: 3, filter_default_label: "[<?=lang('biller');?>]", filter_type: "text", data: []},
      {column_number: 4, filter_default_label: "[<?=lang('customer');?>]", filter_type: "text", data: []},
      {column_number: 5, filter_default_label: "[<?=lang('product_code');?>]", filter_type: "text", data: []},
      {column_number: 6, filter_default_label: "[<?=lang('product_name');?>]", filter_type: "text", data: []},
      {column_number: 7, filter_default_label: "[<?=lang('sale_status');?>]", filter_type: "text", data: []},
      {column_number: 8, filter_default_label: "[<?=lang('payment_status');?>]", filter_type: "text", data: []},
    ], "footer");

    if (localStorage.getItem('remove_slls')) {
      if (localStorage.getItem('slitems')) {
        localStorage.removeItem('slitems');
      }
      if (localStorage.getItem('sldiscount')) {
        localStorage.removeItem('sldiscount');
      }
      if (localStorage.getItem('sltax2')) {
        localStorage.removeItem('sltax2');
      }
      if (localStorage.getItem('slref')) {
        localStorage.removeItem('slref');
      }
      if (localStorage.getItem('slshipping')) {
        localStorage.removeItem('slshipping');
      }
      if (localStorage.getItem('slwarehouse')) {
        localStorage.removeItem('slwarehouse');
      }
      if (localStorage.getItem('slnote')) {
        localStorage.removeItem('slnote');
      }
      if (localStorage.getItem('slinnote')) {
        localStorage.removeItem('slinnote');
      }
      if (localStorage.getItem('slcustomer')) {
        localStorage.removeItem('slcustomer');
      }
      if (localStorage.getItem('slbiller')) {
        localStorage.removeItem('slbiller');
      }
      if (localStorage.getItem('slcurrency')) {
        localStorage.removeItem('slcurrency');
      }
      if (localStorage.getItem('sldate')) {
        localStorage.removeItem('sldate');
      }
      if (localStorage.getItem('slsale_status')) {
        localStorage.removeItem('slsale_status');
      }
      if (localStorage.getItem('slpayment_status')) {
        localStorage.removeItem('slpayment_status');
      }
      if (localStorage.getItem('paid_by')) {
        localStorage.removeItem('paid_by');
      }
      if (localStorage.getItem('amount_1')) {
        localStorage.removeItem('amount_1');
      }
      if (localStorage.getItem('paid_by_1')) {
        localStorage.removeItem('paid_by_1');
      }
      if (localStorage.getItem('pcc_holder_1')) {
        localStorage.removeItem('pcc_holder_1');
      }
      if (localStorage.getItem('pcc_type_1')) {
        localStorage.removeItem('pcc_type_1');
      }
      if (localStorage.getItem('pcc_month_1')) {
        localStorage.removeItem('pcc_month_1');
      }
      if (localStorage.getItem('pcc_year_1')) {
        localStorage.removeItem('pcc_year_1');
      }
      if (localStorage.getItem('pcc_no_1')) {
        localStorage.removeItem('pcc_no_1');
      }
      if (localStorage.getItem('cheque_no_1')) {
        localStorage.removeItem('cheque_no_1');
      }
      if (localStorage.getItem('slpayment_term')) {
        localStorage.removeItem('slpayment_term');
      }
      localStorage.removeItem('remove_slls');
    }

    <?php if (XSession::get('remove_slls')) {
  ?>
    if (localStorage.getItem('slitems')) {
      localStorage.removeItem('slitems');
    }
    if (localStorage.getItem('sldiscount')) {
      localStorage.removeItem('sldiscount');
    }
    if (localStorage.getItem('sltax2')) {
      localStorage.removeItem('sltax2');
    }
    if (localStorage.getItem('slref')) {
      localStorage.removeItem('slref');
    }
    if (localStorage.getItem('slshipping')) {
      localStorage.removeItem('slshipping');
    }
    if (localStorage.getItem('slwarehouse')) {
      localStorage.removeItem('slwarehouse');
    }
    if (localStorage.getItem('slnote')) {
      localStorage.removeItem('slnote');
    }
    if (localStorage.getItem('slinnote')) {
      localStorage.removeItem('slinnote');
    }
    if (localStorage.getItem('slcustomer')) {
      localStorage.removeItem('slcustomer');
    }
    if (localStorage.getItem('slbiller')) {
      localStorage.removeItem('slbiller');
    }
    if (localStorage.getItem('slcurrency')) {
      localStorage.removeItem('slcurrency');
    }
    if (localStorage.getItem('sldate')) {
      localStorage.removeItem('sldate');
    }
    if (localStorage.getItem('slsale_status')) {
      localStorage.removeItem('slsale_status');
    }
    if (localStorage.getItem('slpayment_status')) {
      localStorage.removeItem('slpayment_status');
    }
    if (localStorage.getItem('paid_by')) {
      localStorage.removeItem('paid_by');
    }
    if (localStorage.getItem('amount_1')) {
      localStorage.removeItem('amount_1');
    }
    if (localStorage.getItem('paid_by_1')) {
      localStorage.removeItem('paid_by_1');
    }
    if (localStorage.getItem('pcc_holder_1')) {
      localStorage.removeItem('pcc_holder_1');
    }
    if (localStorage.getItem('pcc_type_1')) {
      localStorage.removeItem('pcc_type_1');
    }
    if (localStorage.getItem('pcc_month_1')) {
      localStorage.removeItem('pcc_month_1');
    }
    if (localStorage.getItem('pcc_year_1')) {
      localStorage.removeItem('pcc_year_1');
    }
    if (localStorage.getItem('pcc_no_1')) {
      localStorage.removeItem('pcc_no_1');
    }
    if (localStorage.getItem('cheque_no_1')) {
      localStorage.removeItem('cheque_no_1');
    }
    if (localStorage.getItem('slpayment_term')) {
      localStorage.removeItem('slpayment_term');
    }
    <?php $this->sma->unset_data('remove_slls');
}
    ?>

    $(document).on('click', '.sledit', function (e) {
      if (localStorage.getItem('slitems')) {
        e.preventDefault();
        var href = $(this).attr('href');
        bootbox.confirm("<?=lang('you_will_loss_sale_data')?>", function (result) {
          if (result) {
            window.location.href = href;
          }
        });
      }
    });
    $(document).on('click', '.slduplicate', function (e) {
      if (localStorage.getItem('slitems')) {
        e.preventDefault();
        var href = $(this).attr('href');
        bootbox.confirm("<?=lang('you_will_loss_sale_data')?>", function (result) {
          if (result) {
            window.location.href = href;
          }
        });
      }
    });
  });
</script>

<?php if ( ! empty($Owner) || ! empty($GP['bulk_actions'])) {
      echo admin_form_open('sales/sale_actions', 'id="action-form"');
    }
?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i
        class="fa-fw fa fa-list"></i><?=lang('sales_item_status') . ' (' . ($warehouse_id ? $warehouse->name : lang('all_warehouses')) . ')';?>
    </h2>

    <div class="box-icon">
      <ul class="btn-tasks">
      <!--<li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fa fa-tasks tip" data-placement="left" title="<?=lang('actions')?>"></i>
          </a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li>
              <a href="<?=admin_url('sales/add')?>">
                <i class="fa fa-plus-circle"></i> <?=lang('add_sale')?>
              </a>
            </li>
            <li>
              <a href="#" id="excel" data-action="export_excel">
                <i class="fa fa-file-excel-o"></i> <?=lang('export_to_excel')?>
              </a>
            </li>
            <li>
              <a href="#" id="combine" data-action="combine">
                <i class="fa fa-file-pdf-o"></i> <?=lang('combine_to_pdf')?>
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#" class="bpo" title="<b><?=lang('delete_sales')?></b>" data-content="<p><?=lang('r_u_sure')?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?=lang('i_m_sure')?></a> <button class='btn bpo-close'><?=lang('no')?></button>" data-html="true" data-placement="left">
                <i class="fa fa-trash-o"></i> <?=lang('delete_sales')?>
              </a>
            </li>
          </ul>
        </li>-->
        <?php if (!empty($warehouses)) {
  ?>
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fa fa-building-o tip" data-placement="left" title="<?=lang('warehouses')?>"></i></a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li><a href="<?=admin_url('sales/status')?>"><i class="fa fa-building-o"></i> <?=lang('all_warehouses')?></a></li>
            <li class="divider"></li>
            <?php
              foreach ($warehouses as $warehouse) {
                echo '<li><a href="' . admin_url('sales/status/' . $warehouse->id) . '"><i class="fa fa-building"></i>' . $warehouse->name . '</a></li>';
              } ?>
          </ul>
        </li>
        <?php
}
        ?>
        <?php if (SHOP) {
          ?>
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fa fa-list-alt tip" data-placement="left" title="<?=lang('sales')?>"></i></a>
          <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
            <li<?= getGET('shop') == 'yes' ? ' class="active"' : ''; ?>><a href="<?=admin_url('sales?shop=yes')?>"><i class="fa fa-shopping-cart"></i> <?=lang('shop_sales')?></a></li>
            <li<?= getGET('shop') == 'no' ? ' class="active"' : ''; ?>><a href="<?=admin_url('sales?shop=no')?>"><i class="fa fa-heart"></i> <?=lang('staff_sales')?></a></li>
            <li<?= !getGET('shop') ? ' class="active"' : ''; ?>><a href="<?=admin_url('sales')?>"><i class="fa fa-list-alt"></i> <?=lang('all_sales')?></a></li>
          </ul>
        </li>
        <?php
        } ?>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <p class="introtext"><?=lang('list_results');?></p>

        <div class="table-responsive">
          <table id="SLData" class="table table-bordered table-condensed table-hover table-striped" cellpadding="0" cellspacing="0" borders="0">
            <thead>
            <tr>
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th><?= lang('date'); ?></th>
              <th><?= lang('reference'); ?></th>
              <th><?= lang('biller'); ?></th>
              <th><?= lang('customer'); ?></th>
              <th><?= lang('product_code'); ?></th>
              <th><?= lang('product_name'); ?></th>
              <th><?= lang('item_status'); ?></th>
              <th><?= lang('payment_status'); ?></th>
              <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
              <td colspan="12" class="dataTables_empty"><?= lang('loading_data'); ?></td>
            </tr>
            </tbody>
            <tfoot class="dtFilter">
            <tr class="active">
              <th style="min-width:30px; width: 30px; text-align: center;">
                <input class="checkbox checkft" type="checkbox" name="check"/>
              </th>
              <th></th><th></th><th></th><th></th><th></th><th></th><th></th>
              <th></th>
              <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
            </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php if ( ! empty($Owner) || ! empty($GP['bulk_actions'])) {
          ?>
  <div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?=form_submit('performAction', 'performAction', 'id="action-form-submit"')?>
  </div>
  <?=form_close()?>
<?php
        }
?>
