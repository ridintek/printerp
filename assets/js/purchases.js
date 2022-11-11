
class Purchase {
  constructor(elm) {
    this._elm = document.querySelector(elm);
    this.mode = 'add';
  }

  addItem(item) {
    if (item) {
      let tbody = this._elm.querySelector('tbody');
      let tr = document.createElement('tr');

      tr.dataset.id = item.id;

      item.received_qty = (item.received_qty ?? 0); // Required or NaN.

      tr.innerHTML = `
        <input type="hidden" name="product[id][]" value="${item.id}">`;

      if (this.mode == 'add' || this.mode == 'edit') { // Action
        tr.innerHTML += `<td class="text-center">
          <a href="#" data-toggle="delete-row" title="Delete ${item.name}">
            <i class="fad fa-trash" style="color:red"></i>
          </a>
        </td>`;
      } else {
        tr.innerHTML += '<td></td>';
      }

      tr.innerHTML += `
        <td>${item.code}</td>
        <td>${item.name}</td>`;

      if (this.mode == 'edit' || this.mode == 'status') {
        let readonly = (this.mode == 'status' ? ' readonly' : '');

        tr.innerHTML += `
          <td><input class="form-control text-right" name="product[total_qty][]"
            value="${parseFloat(item.quantity)}"${readonly}></td>
          <td><input class="form-control text-right" name="product[received_qty][]"
            value="${parseFloat(item.received_qty)}"${readonly}></td>`;
      }

      tr.innerHTML += `
        <td><input class="form-control pm-quantity quantity text-right"
          name="product[quantity][]" data-quantity="${parseFloat(item.quantity)}"
          value="${parseFloat(item.quantity - item.received_qty)}"></td>`;

      tbody.appendChild(tr);
    }
  }

  setMode(mode) {
    this.mode = mode;
  }
}

$(function () {
  let lastValue = 0;

  $(document).on('change', 'input.pm-quantity', function () {
    if (parseFloat(this.value) > parseFloat(this.dataset.quantity)) {
      $(this).val(lastValue);
      toastr.error('Tidak boleh lebih dari stok yang ada.');
      return false;
    }

    if (parseFloat(this.value) < 0) {
      $(this).val(lastValue);
      toastr.error('Tidak boleh ada stok minus.');
      return false;
    }
  });

  $(document).on('focus', 'input.pm-quantity', function () {
    lastValue = this.value;
  });
});
// -- END

$(document).ready(function () {
  if (pqaitems = localStorage.getItem('pqaitems')) { // Check if items from product quantity alert.
    let items = JSON.parse(pqaitems);
    $.each(items, function () {
      add_purchase_item(this);
    });
    localStorage.removeItem('pqaitems');
  }
  $('body a, body button').attr('tabindex', -1);
  check_add_item_val();
  if (site.settings.set_focus != 1) {
    $('#add_item').focus();
  }
  $('#postatus').change(function (e) { // Change postatus.
    let postatus = $(this).val();
    let poitems = JSON.parse(localStorage.getItem('poitems'));

    if (postatus == 'received') {
      localStorage.setItem('received_mode', true);
    } else {
      localStorage.setItem('received_mode', false);
    }

    localStorage.setItem('postatus', postatus);
    localStorage.setItem('poitems', JSON.stringify(poitems));
    loadItems();
  });

  if (postatus = localStorage.getItem('postatus')) {
    if (postatus.length > 0) {
      $('#postatus').val(postatus).trigger('change');
    }

    if (postatus == 'received') {
      localStorage.setItem('received_mode', true);
    } else {
      localStorage.setItem('received_mode', false);
    }
  } else {
    let postatus = $('#postatus').val();
    if (postatus && postatus.length > 0) {
      localStorage.setItem('postatus', postatus);
    }
  }

  $('#popayment_term').change(function (e) {
    localStorage.setItem('popayment_term', $(this).val());
  });
  if ((popayment_term = localStorage.getItem('popayment_term'))) {
    $('#popayment_term').val(popayment_term);
  }

  // If there is any item in localStorage
  if (localStorage.getItem('poitems')) {
    let poitems = JSON.parse(localStorage.getItem('poitems'));

    if (poitems) {
      for (let item_id in poitems) {
        if (typeof (poitems[item_id].row.last_received) == 'undefined') {
          poitems[item_id].row.last_received = poitems[item_id].row.received;
        }
      }
      localStorage.setItem('poitems', JSON.stringify(poitems));
      loadItems();
    }
  }

  // clear localStorage and reload
  $('#reset').click(function (e) {
    bootbox.confirm(lang.r_u_sure, function (result) {
      if (result) {
        if (localStorage.getItem('poitems')) {
          localStorage.removeItem('poitems');
        }
        if (localStorage.getItem('poref')) {
          localStorage.removeItem('poref');
        }
        if (localStorage.getItem('powarehouse')) {
          localStorage.removeItem('powarehouse');
        }
        if (localStorage.getItem('ponote')) {
          localStorage.removeItem('ponote');
        }
        if (localStorage.getItem('posupplier')) {
          localStorage.removeItem('posupplier');
        }
        if (localStorage.getItem('pocurrency')) {
          localStorage.removeItem('pocurrency');
        }
        if (localStorage.getItem('poextras')) {
          localStorage.removeItem('poextras');
        }
        if (localStorage.getItem('podate')) {
          localStorage.removeItem('podate');
        }
        if (localStorage.getItem('postatus')) {
          localStorage.removeItem('postatus');
        }
        if (localStorage.getItem('popayment_term')) {
          localStorage.removeItem('popayment_term');
        }

        $('#modal-loading').show();
        location.reload();
      }
    });
  });

  // save and load the fields in and/or from localStorage
  $('#poref').change(function (e) {
    localStorage.setItem('poref', $(this).val());
  });
  if ((poref = localStorage.getItem('poref'))) {
    $('#poref').val(poref).trigger('change');
  }
  $('#powarehouse').change(function (e) {
    localStorage.setItem('powarehouse', $(this).val());
  });
  if ((powarehouse = localStorage.getItem('powarehouse'))) {
    $('#powarehouse').val(powarehouse).trigger('change');
  }

  $('#ponote').redactor('destroy');
  $('#ponote').redactor({
    buttons: [
      'formatting',
      '|',
      'alignleft',
      'aligncenter',
      'alignright',
      'justify',
      '|',
      'bold',
      'italic',
      'underline',
      '|',
      'unorderedlist',
      'orderedlist',
      '|',
      'link',
      '|',
      'html',
    ],
    formattingTags: ['p', 'pre', 'h3', 'h4'],
    minHeight: 100,
    changeCallback: function (e) {
      var v = this.get();
      localStorage.setItem('ponote', v);
    },
  });
  if ((ponote = localStorage.getItem('ponote'))) {
    $('#ponote').redactor('set', ponote);
  }
  if (posupplier = localStorage.getItem('posupplier')) { // Pre-selected select2.
    preSelectSupplier('#posupplier', posupplier);
  }
  $('#posupplier').change(function (e) {
    localStorage.setItem('posupplier', $(this).val());
    $('#supplier_id').val($(this).val());
  });

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

  $(document).on('click', '.podel', function () {
    row = $(this).closest('tr');
    var item_id = row.prop('id');
    delete poitems[item_id];
    row.remove();
    if (poitems.hasOwnProperty(item_id)) {
    } else {
      localStorage.setItem('poitems', JSON.stringify(poitems));
      loadItems();
      return;
    }
  });

  /* -----------------------
   * Edit Row Modal Hanlder
   ----------------------- */
  $(document).on('click', '.edit', function () {
    var row = $(this).closest('tr');
    var row_id = row.attr('id');
    item_id = row.data('item-id');
    item = poitems[row_id];
    console.log(row_id);
    if (!item) {
      return false;
    }

    var qty = row
      .children()
      .children('.purchased_qty')
      .val();
    $('#prModalLabel').html(item.row.name + ' (' + item.row.code + ')');
    let item_cost = item.row.cost;

    uopt = $('<select id="punit" name="punit" class="form-control select" style="width:100%;">');
    let current_unit = 0;

    $.each(item.units, function () {
      if (this.id == item.row.unit) {
        current_unit = this.id;
        $('<option>', { value: this.id, text: this.name, selected: true }).appendTo(uopt);
      } else {
        $('<option>', { value: this.id, text: this.name }).appendTo(uopt);
      }
    });

    if (item.row.unit && current_unit != item.row.unit) {
      console.log('unit not same');
      $.each(item.units, function () {
        if (this.id == current_unit) {
          $('#pcost')
            .val(formatCurrency(parseFloat(item.row.cost) * unitToBaseQty(1, this), 4))
            .change();
        }
      });
    } else {
      console.log('not unit');
      $('#pcost')
        .val(formatCurrency(item.row.cost))
        .change();
    }

    $('#punits-div').html(uopt);
    $('select.select').select2({ minimumResultsForSearch: 7, theme: 'classic' });
    $('#pquantity').val(qty);
    $('#old_qty').val(qty);
    $('#row_id').val(row_id);
    $('#item_id').val(item_id);
    $('#net_cost').text(formatCurrency(item_cost));
    $('#psubtotal').val('');
    //$('#pcost').val(formatMoney(item_cost));
    $('#prModal')
      .appendTo('body')
      .modal('show');
  });

  $(document).on('change', '#punit', function () {
    var row = $('#' + $('#row_id').val());
    var item_id = row.attr('data-item-id');
    var item = poitems[item_id];
    if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    var unit = $('#punit').val();
    if (unit != poitems[item_id].row.base_unit) {
      $.each(item.units, function () {
        if (this.id == unit) {
          $('#pcost')
            .val(formatDecimal(parseFloat(item.row.cost) * unitToBaseQty(1, this), 4))
            .change();
        }
      });
    } else {
      $('#pcost')
        .val(formatDecimal(item.row.cost))
        .change();
    }
  });

  $(document).on('click', '#calculate_unit_price', function () {
    if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }

    var subtotal = rd_float($('#psubtotal').val()),
      qty = parseFloat($('#pquantity').val());
    $('#pcost')
      .val(formatCurrency(subtotal / qty))
      .change();
    return false;
  });

  /* -----------------------
   * Edit Row Method
   ----------------------- */
  $(document).on('click', '#editItem', function () {
    var row = $('#' + $('#row_id').val());
    var row_id = row.attr('id');
    var item_id = row.attr('data-item-id');
    var item_cost = rd_float($('#pcost').val());

    console.log(row);
    console.log('row_id: ' + row_id);
    console.log('item_id: ' + item_id);
    console.log(poitems[row_id]);

    if (!is_numeric($('#pquantity').val()) || parseFloat($('#pquantity').val()) < 0) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }

    var unit = $('#punit').val();
    var base_quantity = rd_float($('#pquantity').val());
    if (unit != poitems[row_id].row.base_unit) {
      $.each(poitems[row_id].units, function () {
        if (this.id == unit) {
          base_quantity = unitToBaseQty($('#pquantity').val(), this);
        }
      });
    }

    // Update Product Cost.

    let form = {};

    form[security.csrf_token_name] = security.csrf_hash;
    form.product_id = item_id;
    form.cost = item_cost;

    $.ajax({
      data: form,
      method: 'POST',
      success: function (data) {
        console.log(data);
      },
      url: site.base_url + 'procurements/purchases/edit_cost'
    });

    poitems[row_id].row.cost = item_cost;
    poitems[row_id].row.purchased_qty = rd_float($('#pquantity').val());
    poitems[row_id].row.unit = unit;
    localStorage.setItem('poitems', JSON.stringify(poitems));
    $('#prModal').modal('hide');
    loadItems();
    return;
  });

  /* ------------------------------
   * Show manual item addition modal
   ------------------------------- */
  $(document).on('click', '#addManually', function (e) {
    $('#mModal')
      .appendTo('body')
      .modal('show');
    return false;
  });

  let old_row_purchased_qty;
  $(document).on('focus', '.purchased_qty', function () {
    old_row_purchased_qty = $(this).val();
  }).on('change', '.purchased_qty', function () {
    let new_qty = $(this).val();
    let row_id = $(this).closest('tr').prop('id');

    poitems[row_id].row.purchased_qty = new_qty;
    localStorage.setItem('poitems', JSON.stringify(poitems));
    loadItems();
  });

  /* --------------------------
   * Edit Row Quantity Method
   -------------------------- */
  var old_row_qty;
  $(document)
    .on('focus', '.rquantity', function () {
      old_row_qty = $(this).val();
    })
    .on('change', '.rquantity', function () {
      var row = $(this).closest('tr');
      if (!is_numeric($(this).val()) || parseFloat($(this).val()) < 0) {
        $(this).val(old_row_qty);
        bootbox.alert(lang.unexpected_value);
        return;
      }
      var new_qty = parseFloat($(this).val()),
        item_id = row.attr('data-item-id');

      if ($(this).val() > (poitems[item_id].row.purchased_qty - poitems[item_id].row.received_qty)) {
        console.warn(`${$(this).val()} > ${poitems[item_id].row.purchased_qty} - ${poitems[item_id].row.received_qty}`);
        bootbox.alert('Cannot receive more than purchased.');
        $(this).val(old_row_qty);
        return;
      }

      poitems[item_id].row.quantity = new_qty;
      localStorage.setItem('poitems', JSON.stringify(poitems));
      loadItems();
    });

  /*
   * Edit Spec
   */
  $(document).on('change', '.spec', function () {
    let row = $(this).closest('tr');
    let hash = row.attr('id');

    poitems[hash].row.spec = $(this).val();
    localStorage.setItem('poitems', JSON.stringify(poitems));
    loadItems();
  });

  /*
   * Edit Received Qty
   */
  var old_val = 0;
  $(document).on('focus', '.editor', function () {
    old_val = $(this).val();
  }).on('change', '.editor', function () {
    let received_qty_class = '';

    if ($(this).hasClass('received_qty_1')) received_qty_class = 'received_qty_1';
    if ($(this).hasClass('received_qty_2')) received_qty_class = 'received_qty_2';
    if ($(this).hasClass('received_qty_3')) received_qty_class = 'received_qty_3';

    if (received_qty_class && is_numeric($(this).val())) {
      let row = $(this).closest('tr');
      let row_id = row.prop('id');
      let received_qty_1 = parseFloat(row.find('.received_qty_1').val());
      let received_qty_2 = parseFloat(row.find('.received_qty_2').val());
      let received_qty_3 = parseFloat(row.find('.received_qty_3').val());
      let total_received_qty = received_qty_1 + received_qty_2 + received_qty_3;
      console.log('qty_1:', received_qty_1, ', qty_2:', received_qty_2, ', qty_3:', received_qty_3);

      if (total_received_qty > poitems[row_id].row.purchased_qty) {
        console.warn(`${total_received_qty} > ${poitems[row_id].row.purchased_qty}`);
        bootbox.alert('Cannot receive more than purchased.');
        $(this).val(old_val);
        return;
      }
      poitems[row_id].row[received_qty_class] = parseFloat($(this).val());
      localStorage.setItem('poitems', JSON.stringify(poitems));
      loadItems();
    }
  });

  /* --------------------------
   * Edit Row Cost Method
   -------------------------- */
  var old_cost;
  $(document)
    .on('focus', '.rcost', function () {
      old_cost = $(this).val();
    })
    .on('change', '.rcost', function () {
      var row = $(this).closest('tr');
      if (!is_numeric($(this).val())) {
        $(this).val(old_cost);
        bootbox.alert(lang.unexpected_value);
        return;
      }
      var new_cost = parseFloat($(this).val()),
        item_id = row.attr('data-item-id');
      poitems[item_id].row.cost = new_cost;
      localStorage.setItem('poitems', JSON.stringify(poitems));
      loadItems();
    });

  $(document).on('click', '#removeReadonly', function () {
    let supplier = $('#posupplier');
    if (supplier.hasClass('lock')) {
      supplier.select2('readonly', false);
      $(this).html('<i class="fa fa-fw fa-unlock" id="unLock"></i>');
      supplier.removeClass('lock');
    } else {
      supplier.select2('readonly', true);
      $(this).html('<i class="fa fa-fw fa-lock" id="unLock"></i>');
      supplier.addClass('lock');
    }
    return false;
  });

  if (typeof po_edit !== 'undefined' && po_edit) {
    $('#posupplier').select2('readonly', true);
    $('#posupplier').addClass('lock');
  }
});

/* -----------------------
 * Misc Actions
 ----------------------- */
function loadItems() {
  if (localStorage.getItem('poitems')) {
    let total_received_qty = 0, total_received_value = 0, total_rest_qty = 0;
    $('#poTable tbody').empty();
    poitems = JSON.parse(localStorage.getItem('poitems'));

    var order_no = new Date().getTime();
    var postatus = localStorage.getItem('postatus');

    add_mode = (localStorage.getItem('add_mode') == 'true' ? true : false);
    edit_mode = (localStorage.getItem('edit_mode') == 'true' ? true : false);
    status_mode = (localStorage.getItem('status_mode') === 'true' ? true : false);
    received_mode = (localStorage.getItem('received_mode') === 'true' ? true : false);

    $.each(poitems, function () {
      var item = this;
      var item_id = item.item_id; //site.settings.item_addition == 1 ? item.item_id : item.id;
      item.order = item.order ? item.order : order_no++;
      var product_id = item.row.id,
        item_cost = item.row.cost,
        item_qty = item.row.quantity, // sma_stocks.quantity
        item_purchased_qty = item.row.purchased_qty, // sma_stocks.purchased_qty
        item_received_qty_1 = item.row.received_qty_1, // sma_stocks.json_data.received_qty_1
        item_received_qty_2 = item.row.received_qty_2, // sma_stocks.json_data.received_qty_2
        item_received_qty_3 = item.row.received_qty_3, // sma_stocks.json_data.received_qty_3
        item_received_date_1 = item.row.received_date_1, // sma_stocks.json_data.received_date_1
        item_received_date_2 = item.row.received_date_2, // sma_stocks.json_data.received_date_2
        item_received_date_3 = item.row.received_date_3, // sma_stocks.json_data.received_date_3
        item_rest_qty = item.row.rest_qty,
        item_qty_alert = item.row.safety_stock,
        current_stock = item.row.current_stock,
        min_order_qty = item.row.min_order_qty,
        item_spec = item.row.spec,
        item_code = item.row.code,
        item_name = item.row.name.replace(/"/g, '&#034;').replace(/'/g, '&#039;');

      var item_supplier_part_no = item.row.supplier_part_no ? item.row.supplier_part_no : '';
      var unit_code = '';
      var item_unit = item.row.unit;
      var item_base_unit = item.row.base_unit;

      if (item_unit != item_base_unit) {
        for (let unit of item.units) {
          if (unit.id == item_unit) {
            item_qty = baseToUnitQty(item_qty, unit);
          }
        }
      }

      for (let unit of item.units) {
        if (item_unit == unit.id) { // Change unit code to display.
          unit_code = unit.code;
        }
      }

      var rowId = item.id;
      var newTr = $(`<tr id="${rowId}" class="purchase_row" data-item-id="${item_id}"></tr>`);

      // Product Code - Name
      tr_html =
        `<td>
          <input name="product_id[]" type="hidden" class="rid" value="${product_id}">
          <input name="product[]" type="hidden" class="rcode" value="${item_code}">
          <input name="product_name[]" type="hidden" class="rname" value="${item_name}">
          <span class="sname">${item_code} - ${item_name}
            <span class="label label-default">${item_supplier_part_no}</span>
          </span>`;

      let display_none = (!edit_mode && status_mode && postatus != 'need_approval' ? 'display-none' : '');

      // Edit Button
      tr_html += `<i class="pull-right fa fa-edit tip edit ${display_none}" data-item="${item_id}" title="Edit"
        style="cursor:pointer;"></i>`;

      tr_html += '</td>'; // end Product Code - Name

      // Spec
      tr_html += `<td><input class="form-control editor spec" name="spec[]" type="text" value="${item_spec}"></td>`;

      // Cost
      tr_html +=
        `<td class="text-right">
          <input class="form-control input-sm text-right rcost" name="cost[]" type="hidden" value="${item_cost}">
          <span class="text-right scost">${formatCurrency(item_cost)}</span>
        </td>`;

      // Unit (UoM)
      tr_html +=
        `<td class="text-center">
          <input name="item_unit[]" type="hidden" class="runit" value="${item_unit}">
          <span class="text-center">${unit_code}</span>
        </td>`;

      // Disable quantity editing in update mode.
      readonly = (status_mode && postatus != 'need_approval' ? 'readonly="readonly"' : '');

      // Purchased Qty
      tr_html +=
        `<td>
          <input class="form-control text-center purchased_qty" name="purchased_qty[]" type="text" value="${formatQuantity2(item_purchased_qty)}"
            data-item="${item_id}" onClick="this.select();" ${readonly}>
        </td>`;

      readonly = 'readonly="readonly"';
      _readonly = readonly;

      // Received Qty 1
      if (edit_mode) readonly = '';
      if (received_mode && !item_received_date_1) readonly = '';
      tr_html +=
        `<td>
          <input class="form-control editor text-center received_qty_1" type="text" name="received_qty_1[]" value="${item_received_qty_1}" ${readonly}>
          <input type="hidden" name="received_date_1[]" value="${item_received_date_1}">
        </td>`;

      // Received Qty 2
      readonly = _readonly;
      if (edit_mode) readonly = '';
      if (received_mode && !item_received_date_2 && item_received_date_1) readonly = '';
      tr_html +=
        `<td>
          <input class="form-control editor text-center received_qty_2" type="text" name="received_qty_2[]" value="${item_received_qty_2}" ${readonly}>
          <input type="hidden" name="received_date_2[]" value="${item_received_date_2}">
        </td>`;

      // Received Qty 3
      readonly = _readonly;
      if (edit_mode) readonly = '';
      if (received_mode && !item_received_date_3 && item_received_date_2) readonly = '';
      tr_html +=
        `<td>
          <input class="form-control editor text-center received_qty_3" type="text" name="received_qty_3[]" value="${item_received_qty_3}" ${readonly}>
          <input type="hidden" name="received_date_3[]" value="${item_received_date_3}">
        </td>`;

      // Received Total
      tr_html +=
        `<td>
          <div class="text-center">${formatQuantity2(item_qty)}</div>
          <input type="hidden" name="quantity[]" value="${item_qty}">
        </td>`;

      // Received Value
      tr_html +=
        `<td>
          <div class="text-center">${formatCurrency(Math.round(item_qty * item_cost))}</div>
        </td>`;

      readonly = (status_mode && !received_mode || add_mode || edit_mode ? 'readonly="readonly"' : '');

      // Rest Quantity
      tr_html +=
        `<td>
          <div class="text-center">${formatQuantity2(item_rest_qty)}</div>
        </td>`;

      // Min Order Qty
      tr_html += `<td class="text-center">${formatQuantity(min_order_qty)}</td>`;

      // Quantity Alert
      tr_html += `<td class="text-center">${formatQuantity(item_qty_alert)}</td>`;

      // Current Stock
      tr_html += `<td class="text-right">${formatQuantityFix(current_stock)}</td>`;

      let no_remove = (!edit_mode && status_mode && postatus != 'need_approval' ? 'display-none' : '');
      tr_html +=
        `<td class="text-center">
          <i class="fa fa-times tip podel ${no_remove}" title="Remove" style="cursor:pointer;"></i>
        </td>`;

      newTr.html(tr_html);
      newTr.prependTo('#poTable');
      total_received_qty += parseFloat(item_qty);
      total_received_value += (parseFloat(item_cost) * parseFloat(item_qty));
      total_rest_qty += parseFloat(item_rest_qty);
    });

    var col = 8;

    // Total
    var tfoot =
      `<tr id="tfoot" class="tfoot active">
        <th colspan="${col}">Total</th>`;

    // Received Total
    tfoot += `<th class="text-right">${formatQuantity2(total_received_qty)}</th>`;

    // Received Value
    tfoot += `<th class="text-right">${formatCurrency(Math.round(total_received_value))}</th>`;

    // Rest Quantity
    tfoot += `<th class="text-right">${formatCurrency(total_rest_qty)}</th>`;

    // Received Value
    tfoot += `<th class="text-right" colspan="3"></th>`;

    tfoot += '<th class="text-center"><i class="fa fa-trash" style="opacity:0.5; filter:alpha(opacity=50);"></i></th></tr>';
    $('#poTable tfoot').html(tfoot);

    // Totals calculations after item addition
    //var gtotal = total;
    //$('#total').text(formatMoney(total));
    //$('#titems').text(an - 1 + ' (' + (parseFloat(count) - 1) + ')');

    //$('#gtotal').text(formatMoney(gtotal));
    /*if (an > parseInt(site.settings.bc_fix) && parseInt(site.settings.bc_fix) > 0) {
      $('html, body').animate({ scrollTop: $('#sticker').offset().top }, 500);
      $(window).scrollTop($(window).scrollTop() + 1);
    }*/
    set_page_focus();
  }
}

/* -----------------------------
 * Add Purchase Item Function
 * @param {json} item
 * @returns {Boolean}
 ---------------------------- */
function add_purchase_item(item) {
  console.groupCollapsed('add_purchase_item');
  if (item == null) return;
  row_id = item.id;

  if (localStorage.getItem('poitems')) {
    poitems = JSON.parse(localStorage.getItem('poitems'));
  } else {
    poitems = {};
  }

  poitems[row_id] = item;
  poitems[row_id].order = new Date().getTime();
  console.warn(poitems);
  localStorage.removeItem('poitems');
  localStorage.setItem('poitems', JSON.stringify(poitems));
  console.warn(poitems);
  console.warn(localStorage.getItem('poitems'));
  console.groupEnd();
  loadItems();
  return true;
}

if (typeof Storage === 'undefined') {
  $(window).bind('beforeunload', function (e) {

  });
}
