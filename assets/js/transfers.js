$(document).ready(function () {
  if (wsaitems = localStorage.getItem('wsaitems')) { // Check if items from warehouse stock alert.
    let items = JSON.parse(wsaitems);
    $.each(items, function() {
      add_transfer_item(this);
    });
    localStorage.removeItem('wsaitems');
  }
  $('body a, body button').attr('tabindex', -1);
  check_add_item_val();
  if (site.settings.set_focus != 1) {
    $('#add_item').focus();
  }

  $('#tostatus').change(function (e) {
    let tostatus = $(this).val();

    localStorage.setItem('tostatus', tostatus);

    if (tostatus == 'received') { // ori: 'completed'
      $('.rquantity').prop('readonly', false);
      $('.xqty').html(lang.received);
    } else if (tostatus != 'packing') {
      $('.rquantity').prop('readonly', true);
      $('.todel').addClass('display-none', true);
      $('.tointer').addClass('display-none', true);
      $('.xqty').html(lang.quantity);
    }
    if (tostatus == 'packing') {
      $('.rquantity').prop('readonly', false);
      $('.todel').removeClass('display-none');
      $('.tointer').removeClass('display-none');
    }
  });

  if (tostatus = localStorage.getItem('tostatus')) {
    $('#tostatus').val(tostatus).trigger('change');
    if(tostatus == 'received') { // ori: 'completed'
      $('#tostatus').prop("readonly", true);
    }
  }

  //localStorage.clear();
  // If there is any item in localStorage
  if (localStorage.getItem('toitems')) {
    loadItems();
  }

  // clear localStorage and reload
  $('#reset').click(function (e) {
    bootbox.confirm(lang.r_u_sure, function (result) {
      if (result) {
        if (localStorage.getItem('toitems')) {
          localStorage.removeItem('toitems');
        }
        if (localStorage.getItem('toref')) {
          localStorage.removeItem('toref');
        }
        if (localStorage.getItem('to_warehouse')) {
          localStorage.removeItem('to_warehouse');
        }
        if (localStorage.getItem('tonote')) {
          localStorage.removeItem('tonote');
        }
        if (localStorage.getItem('from_warehouse')) {
          localStorage.removeItem('from_warehouse');
        }
        if (localStorage.getItem('todate')) {
          localStorage.removeItem('todate');
        }
        if (localStorage.getItem('tostatus')) {
          localStorage.removeItem('tostatus');
        }
        $('#modal-loading').show();
        location.reload();
      }
    });
  });

  // save and load the fields in and/or from localStorage

  $('#toref').change(function (e) {
    localStorage.setItem('toref', $(this).val());
  });
  if (toref = localStorage.getItem('toref')) {
    $('#toref').val(toref);
  }
  $('#to_warehouse').change(function (e) {
    localStorage.setItem('to_warehouse', $(this).val());
  });
  if (to_warehouse = localStorage.getItem('to_warehouse')) {
    $('#to_warehouse').val(to_warehouse).trigger('change');

    if (localStorage.getItem('update_mode')) { // Disable to_warehouse editing.
      $('#to_warehouse').prop('readonly', true);
    }
  }
  $('#from_warehouse').change(function (e) {
    localStorage.setItem('from_warehouse', $(this).val());
  });
  if (from_warehouse = localStorage.getItem('from_warehouse')) {
    $('#from_warehouse').val(from_warehouse).trigger('change');
    if (count > 1) {
      $('#from_warehouse').prop("readonly", true);
    }
  }

    //$(document).on('change', '#tonote', function (e) {
  $('#tonote').redactor('destroy');
  $('#tonote').redactor({
    buttons: ['formatting', '|', 'alignleft', 'aligncenter', 'alignright', 'justify', '|', 'bold', 'italic', 'underline', '|', 'unorderedlist', 'orderedlist', '|', 'link', '|', 'html'],
    formattingTags: ['p', 'pre', 'h3', 'h4'],
    minHeight: 50,
    changeCallback: function (e) {
      var v = this.get();
      localStorage.setItem('tonote', v);
    }
  });
  if (tonote = localStorage.getItem('tonote')) {
    $('#tonote').redactor('set', tonote);
  }

  // prevent default action upon enter
  $('body').bind('keypress', function (e) {
    if ($(e.target).hasClass('redactor_editor')) {
      return true;
    }
    if (e.keyCode == 13) {
      e.preventDefault();
      return false;
    }
  });


  /* ----------------------
   * Delete Row Method
   * ---------------------- */
  $(document).on('click', '.todel', function () {
    var row = $(this).closest('tr');
    var row_id = row.attr('data-row-id');
    delete toitems[row_id];
    row.remove();
    if(toitems.hasOwnProperty(row_id)) { } else {
      localStorage.setItem('toitems', JSON.stringify(toitems));
      loadItems();
      return;
    }
  });

  /* --------------------------
   * Edit Row Quantity Method
   -------------------------- */
   var old_row_qty, original_qty;
   $(document).on("focus", '.rquantity', function () {
    old_row_qty = $(this).val();
    if (typeof(original_qty) == 'undefined') original_qty = old_row_qty;
  }).on("change", '.rquantity', function () {
    let tostatus = localStorage.getItem('tostatus');
    let row = $(this).closest('tr');
    if (!is_numeric($(this).val()) || parseFloat($(this).val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    if (parseFloat($(this).val()) > original_qty && tostatus == 'received') {
      $(this).val(old_row_qty);
      bootbox.alert('Maaf tidak bisa menerima lebih banyak dari item yang telah dikirim.');
      return;
    }
/*
    if (tostatus == 'received') {
      if ($(this).val() < original_qty) {
        $(this).val(original_qty);
        bootbox.alert('Jumlah item yang diterima kurang dari yang dipesan. Silakan cek surat purchase order dan tanyakan kembali kepada kurir anda.');
        return;
      }
    }
*/
    let new_qty = parseFloat($(this).val());
    let row_id = row.attr('data-row-id');

    console.log('toitems:', toitems);
    if(toitems[row_id].row.unit != toitems[row_id].row.base_unit) {
      $.each(toitems[row_id].units, function(){
        if (this.id == toitems[row_id].row.unit) {
          toitems[row_id].row.quantity = unitToBaseQty(new_qty, this);
        }
      });
    }
    $('#add_transfer, #edit_transfer').attr('disabled', false);

    toitems[row_id].row.quantity = new_qty;
    localStorage.setItem('toitems', JSON.stringify(toitems));
    loadItems();
  });

  /* --------------------------
   * Edit Spec Item Method
   -------------------------- */
   var old_row_qty;
   $(document).on("change", '.spec', function () {
    let row = $(this).parents('tr');
    let row_id = row.attr('data-row-id');

    toitems[row_id].row.spec = $(this).val();
    localStorage.setItem('toitems', JSON.stringify(toitems));
    loadItems();
  });

  /* --------------------------
   * Edit Row Cost Method
   -------------------------- */
   var old_cost;
   $(document).on("focus", '.rcost', function () {
    old_cost = $(this).val();
  }).on("change", '.rcost', function () {
    var row = $(this).closest('tr');
    if (!is_numeric($(this).val())) {
      $(this).val(old_cost);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    var new_cost = parseFloat($(this).val()),
    item_id = row.attr('data-item-id');
    toitems[item_id].row.cost = new_cost;
    localStorage.setItem('toitems', JSON.stringify(toitems));
    loadItems();
  });
  $(document).on("click", '#removeReadonly', function () {
   $('#from_warehouse').prop('readonly', false);
   return false;
  });
});

  /* -----------------------
   * Edit Row Modal Hanlder
   ----------------------- */
   $(document).on('click', '.edit', function () {
    let row = $(this).closest('tr');
    let item_id = row.attr('data-item-id');
    let row_id = row.attr('data-row-id');
    item = toitems[row_id];
    let markon_price = parseFloat(item.row.markon_price);
    let qty = row.children().children('.rquantity').val();
    $('#prModalLabel').text(item.row.name + ' (' + item.row.code + ')');

    let opt = '<p style="margin: 12px 0 0 0;">n/a</p>';

    uopt = $("<select id=\"punit\" name=\"punit\" class=\"form-control select\" />");
    $.each(item.units, function () {
      if(this.id == item.row.unit) {
        $("<option />", {value: this.id, text: this.name, selected:true}).appendTo(uopt);
      } else {
        $("<option />", {value: this.id, text: this.name}).appendTo(uopt);
      }
    });
    $('#poptions-div').html(opt);
    $('#punits-div').html(uopt);
    $('select.select').select2({minimumResultsForSearch: 7, theme: 'classic'});
    $('#pquantity').val(qty);
    $('#old_qty').val(qty);
    $('#pprice').val(markon_price);
    $('#old_price').val(markon_price);
    $('#row_id').val(row_id);
    $('#item_id').val(item_id);
    $('#prModal').appendTo("body").modal('show');
  });

  $('#prModal').on('shown.bs.modal', function (e) {
    if($('#poption').val() != '') {
      $('#poption').val(product_variant).trigger('change');
      product_variant = 0;
    }
  });

  $(document).on('change', '#punit', function () {
    var row = $('#' + $('#row_id').val());
    var item_id = row.attr('data-item-id');
    var item = toitems[item_id];
    if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    var unit = $('#punit').val();
    if(unit != toitems[item_id].row.base_unit) {
      $.each(item.units, function() {
        if (this.id == unit) {
          $('#pprice').val(formatDecimal((parseFloat(item.row.base_unit_cost)*(unitToBaseQty(1, this))), 4)).change();
        }
      });
    } else {
      $('#pprice').val(formatDecimal(item.row.base_unit_cost)).change();
    }
  });

  /* -----------------------
   * Edit Row Method
   ----------------------- */
   $(document).on('click', '#editItem', function () {
    var row = $('#row_' + $('#row_id').val());
    var row_id = row.attr('data-row-id');
    if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    var unit = $('#punit').val();
    if(unit != toitems[row_id].row.base_unit) {
      $.each(toitems[row_id].units, function(){
        if (this.id == unit) {
          quantity = unitToBaseQty($('#pquantity').val(), this);
        }
      });
    }
    toitems[row_id].row.fup = 1,
    toitems[row_id].row.quantity = parseFloat($('#pquantity').val()),
    toitems[row_id].row.unit = unit,
    toitems[row_id].row.markon_price = parseFloat($('#pprice').val()),
    localStorage.setItem('toitems', JSON.stringify(toitems));
    $('#prModal').modal('hide');

    loadItems();
    return;
  });

  /* -----------------------
   * Misc Actions
   ----------------------- */

  function loadItems () {
    if (localStorage.getItem('toitems')) {
      total = 0;
      count = 1;
      an = 1;
      $("#toTable tbody").empty();
      let stock_missing = false;
      toitems = JSON.parse(localStorage.getItem('toitems'));
      tostatus = localStorage.getItem('tostatus') ?? $('#tostatus').val();
      // Update mode and Edit is different. If you need change item, it's edit mode not update mode.
      update_mode = (localStorage.getItem('update_mode') === 'true' ? true : false);

      $.each(toitems, function () {
        var item = this,
          item_id   = item.row.id,
          item_qty  = item.row.quantity,
          item_code = item.row.code,
          item_type = item.row.type,
          item_unit = item.row.unit,
          item_name = item.row.name.replace(/"/g, "&#034;").replace(/'/g, "&#039;"),
          item_markon_price = item.row.markon_price,
          item_spec = item.row.spec,
          row_no    = item.id;

        // Source and Destination Stock
        var source_stock = item.row.source_qty, destination_stock = item.row.destination_qty;
        // Min Order Quantity and Safety Stock
        var min_order_qty = item.row.min_order_qty, safety_stock  = item.row.safety_stock;

        let unit_name = '';
        $.each(item.units, function () {
          if (item.row.unit == this.id) {
            unit_name = this.code;
          }
        });

        var newTr = $(`<tr id="row_${row_no}" data-item-id="${item_id}" data-row-id="${row_no}"></tr>`);
        let display_none = (update_mode ? 'display-none' : '');
        let readonly     = (tostatus != 'packing' && tostatus != 'received' && update_mode ? 'readonly="readonly"' : '');

        // First Column Table
        tr_html = `<td><input name="product_id[]" type="hidden" class="rid" value="${item_id}">
          <input name="product_code[]" type="hidden" class="rcode" value="${item_code}">
          <input name="product_type[]" type="hidden" class="rtype" value="${item_type}">
          <input name="product_unit[]" type="hidden" class="runit" value="${item_unit}">
          <input class="form-control input-sm text-right rcost" name="markon_price[]" type="hidden" value="${formatDecimal(item_markon_price)}">
          <span class="sname" id="name_${row_no}">${item_code} - ${item_name}</span>
            <i class="pull-right fa fa-edit tip tointer edit ${display_none}" data-item="${item_id}" title="Edit" style="cursor:pointer;"></i>
          </td>`;

        // Spec
        tr_html += `<td class="text-center"><input class="form-control editor spec" name="spec[]" value="${item_spec}"></td>`;

        if ( ! update_mode) {
          // Mark On Price
          tr_html +=
            `<td class="text-right">
              <span class="text-right scost">${formatMoney(item_markon_price)}</span>
            </td>`;
        }

        // Unit
        tr_html += `<td class="text-center">${unit_name}</td>`;

        // Quantity
        tr_html += `<td><input class="form-control text-center rquantity editor" name="quantity[]" type="text" value="${formatQuantity2(item_qty)}" ${readonly}></td>`;

        if ( ! update_mode) {
          // Min Order Qty
          tr_html += `<td class="text-center">${formatQuantity(min_order_qty)}</td>`;

          // Safety Stock
          tr_html += `<td class="text-center">${formatQuantity(safety_stock)}</td>`;

          // Source Stock
          tr_html += `<td class="text-right">${formatQuantity(source_stock)}</td>`;

          // Destination Stock
          tr_html += `<td class="text-right">${formatQuantity(destination_stock)}</td>`;

          // Sub Total
          tr_html +=
            `<td class="text-right">
              <span class="text-right ssubtotal" id="subtotal_${row_no}">${formatMoney((parseFloat(item_markon_price) * parseFloat(item_qty)))}</span>
            </td>`;
        }

        tr_html +=
          `<td class="text-center">
            <i class="fa fa-times tip todel ${display_none}" id="${row_no}" title="Remove" style="cursor:pointer;"></i>
          </td>`;
        newTr.html(tr_html);
        newTr.prependTo("#toTable");
        total += formatDecimal((parseFloat(item_markon_price) * parseFloat(item_qty)), 4);
        count += parseFloat(item_qty);
        an++;

        console.log(`${item_code}: item_qty[${item_qty}] > source_stock[${source_stock}]`);
        if (parseFloat(item_qty) > parseFloat(source_stock)) {
          console.log('%cSource stock less than item quantity.', 'color:red');
          $('#row_' + row_no).addClass('danger');
          stock_missing = true;
        }

        if (stock_missing) {
          $('#add_transfer, #edit_transfer').prop('disabled', true);
        }
      });

      var col = 9; // For add/edit mode.
      if (update_mode) {
        col -= 5; // For update mode/status mode.
      }
      var tfoot =
        `<tr id="tfoot" class="tfoot active"><th colspan="${col}">Total</th>`;

      if ( ! update_mode) {
        tfoot += `<th class="text-right">${formatMoney(total)}</th>`;
      }

      tfoot += `<th class="text-center"><i class="fa fa-trash" style="opacity:0.5; filter:alpha(opacity=50);"></i></th>
        </tr>`;
      $('#toTable tfoot').html(tfoot);

      // Totals calculations after item addition
      var gtotal = total;
      $('#total').text(formatMoney(total));
      $('#titems').text((an-1)+' ('+(formatQty(parseFloat(count) - 1))+')');

      $('#gtotal').text(formatMoney(gtotal));
      // if (an > parseInt(site.settings.bc_fix) && parseInt(site.settings.bc_fix) > 0) {
      //   $("html, body").animate({scrollTop: $('#sticker').offset().top}, 500);
      //   $(window).scrollTop($(window).scrollTop() + 1);
      // }
      set_page_focus();
    }
  }

  /* -----------------------------
   * Add Purchase Iten Function
   * @param {json} item
   * @returns {Boolean}
   ---------------------------- */
  function add_transfer_item(item) {
    if (count == 1) {
      toitems = {};
      if ($('#from_warehouse').val()) {
        $('#from_warehouse').prop("readonly", true);
      } else {
        bootbox.alert('Please select from warehouse.');
        item = null;
        return;
      }
    }
    if (item == null)
      return;

    toitems[item.id] = item;

    localStorage.setItem('toitems', JSON.stringify(toitems));
    loadItems();
    return true;
  }

  if (typeof (Storage) === "undefined") {
    $(window).bind('beforeunload', function (e) {
      if (count > 1) {
        var message = "You will loss data!";
        return message;
      }
    });
  }
