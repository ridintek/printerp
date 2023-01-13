<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?= lang('search_items'); ?></h4>
  </div>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-12">
        <table id="Table2" class="table table-bordered table-condensed table-hovered table-striped" style="width:100%;">
          <thead>
            <tr>
              <th style="text-align:center; width:30px;">
                <input class="checkbox checkth" type="checkbox" name="check">
              </th>
              <th>(Item Code) Item Name</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php echo form_button('add_items', lang('add_items'), 'class="btn btn-primary" id="add_items"'); ?>
  </div>
</div>
<script src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function (e) {
    let hSearch = null;

    window.Table2 = $('#Table2').DataTable({
      ajax: {
        data: {
          <?= csrf_token(); ?>: '<?= csrf_hash(); ?>'
        },
        type: 'POST',
        url: site.base_url + 'products/stock_opname/suggestions'
      },
      columnDefs: [
        {targets: 0, orderable: false, render: checkbox}
      ],
      lengthMenu: [[10, 25], [10, 25]],
      order: [[1, 'asc']],
      pageLength: 10,
      serverSide: true
    });

    $('#add_items').click(function () {
      let items = $('input[name="val[]"]');
      let params = {};

      params.ids = '';

      for (let a = 0; a < items.length; a++) {
        if (items[a].checked) {
          params.ids += items[a].value + ',';
        }
      }

      params.ids = params.ids.substr(0, params.ids.length - 1);
      params.warehouse = $('#warehouse').val();
      getProductInfo(params).then(function (data) {
        if (Array.isArray(data)) {
          addItems(data);
          $('#myModal').modal('hide');
        }
      });
    });

    $('#searchItems').click(function (e) {
      e.preventDefault();
      Table2.search($('#so_search').val()).draw();
    });

    $('#so_search').on('keyup', function (e) {
      if (Table2 && e.keyCode == 13) {
        if (hSearch) clearTimeout(hSearch);
        hSearch = setTimeout(() => {
          Table2.search($(this).val()).draw();
        }, 100);
      }
    });
  });
</script>