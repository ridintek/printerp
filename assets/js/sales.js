var slitems = {};
var submitted = false;

$(document).ready(function (e) {
  $('#form_add_sale').submit(function (event) {
    if (submitted) {
      $('#add_sale').prop('disabled', true); // Protect double submit.
      $('#draft_sale').prop('disabled', true); // Protect double submit.
      event.preventDefault();
    } else {
      $(this)[0].submit(); // SUBMIT FORM ADD SALE.
      submitted = true;
    }
  });
  /*
    $('#edit_sale').click(function (event) {
      //$('#edit_sale').prop('disabled', true); // Protect double submit.

      if (submitted) {
        console.log('DISABLED');
        $('#edit_sale').prop('disabled', true); // Protect double submit.
        event.preventDefault();
      } else {
        console.log('SUBMITTED');
        $('#form_edit_sale')[0].submit(); // SUBMIT FORM EDIT SALE.
        submitted = true;
      }
    });
  */
  $('body a, body button').attr('tabindex', -1);
  check_add_item_val();
  if (site.settings.set_focus != 1) {
    $('#add_item').focus();
  }

  var $customer = $('#slcustomer');
  $customer.change(function (e) {
    localStorage.setItem('slcustomer', $(this).val());
    //$('#slcustomer_id').val($(this).val());
  });

  if (site.permissions.pj && !site.permissions.pj['sales-select_customer']) {
    //$customer.prop('readonly', true); // Activate soon.
  }

  if (slcustomer = localStorage.getItem('slcustomer')) {
    $customer.val(slcustomer).select2({
      ajax: {
        url: site.base_url + 'customers/suggestions',
        delay: 1000,
        data: function (params) {
          return {
            term: params.term,
            limit: 10,
          };
        },
        processResults: function (data) {
          if (data.results) {
            return { results: data.results };
          } else {
            return { results: [{ id: '', text: 'No Match Found' }] };
          }
        },
      },
      minimumInputLength: 1,
      theme: 'classic'
    });

    $.ajax({
      data: {
        id: slcustomer,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (data.results.length) {
          $customer.append(new Option(data.results[0].text, slcustomer, true, true)).trigger('change');
        }
      },
      url: site.base_url + 'customers/suggestions'
    });
  } else {
    nsCustomer();
  }

  // Order level shipping and discount localStorage
  $('#slsale_status').change(function (e) {
    localStorage.setItem('slsale_status', $(this).val());
  });
  if ((slsale_status = localStorage.getItem('slsale_status'))) {
    $('#slsale_status').val(slsale_status).trigger('change');
  }
  $('#slpayment_status').change(function (e) { // On:Payment Status change
    var ps = $(this).val();
    localStorage.setItem('slpayment_status', ps);
    if (ps == 'partial' || ps == 'paid') {
      if (ps == 'paid') {
        $('#amount_1').val(formatDecimal(parseFloat(total)));
        localStorage.setItem('amount_1', $('#amount_1').val());
        $('#amount_1').attr('readonly', 'true');
      } else {
        $('#amount_1').removeAttr('readonly');
      }
      $('#slsale_status').val('waiting_production').trigger('change');
      localStorage.setItem('slsale_status', 'waiting_production');
      $('#payments').slideDown();
      $('#pcc_no_1').focus();
    } else {
      $('#slsale_status').val('need_payment').trigger('change');
      localStorage.setItem('slsale_status', 'need_payment');
      $('#payments').slideUp();
    }
  });
  if ((slpayment_status = localStorage.getItem('slpayment_status'))) {
    $('#slpayment_status').val(slpayment_status).trigger('change');
    var ps = slpayment_status;
    if (ps == 'partial' || ps == 'paid') {
      if (ps == 'paid') {
        if (amount_1 = localStorage.getItem('amount_1')) {
          $('#amount_1').val(amount_1);
        }
        $('#amount_1').attr('readonly', true);
      }
      $('#payments').slideDown();
      $('#pcc_no_1').focus();
    } else {
      $('#payments').slideUp();
    }
  }

  if ((paid_by = localStorage.getItem('paid_by'))) {
    var p_val = paid_by;
    $('.paid_by').val(paid_by).trigger('change');
    $('#rpaidby').val(p_val);
    if (p_val == 'cash' || p_val == 'other') {
      $('.pcheque_1').hide();
      $('.pcc_1').hide();
      $('.pcash_1').show();
      $('#payment_note_1').focus();
    } else if (p_val == 'CC') {
      $('.pcheque_1').hide();
      $('.pcash_1').hide();
      $('.pcc_1').show();
      $('#pcc_no_1').focus();
    } else if (p_val == 'Cheque') {
      $('.pcc_1').hide();
      $('.pcash_1').hide();
      $('.pcheque_1').show();
      $('#cheque_no_1').focus();
    } else {
      $('.pcheque_1').hide();
      $('.pcc_1').hide();
      $('.pcash_1').hide();
    }
    if (p_val == 'gift_card') {
      $('.gc').show();
      $('.ngc').hide();
      $('#gift_card_no').focus();
    } else {
      $('.ngc').show();
      $('.gc').hide();
      $('#gc_details').html('');
    }
  }

  if ((amount_1 = localStorage.getItem('amount_1'))) {
    $('#amount_1').val(amount_1);
  }
  $('#amount_1').change(function (e) {
    localStorage.setItem('amount_1', $(this).val());
  });

  $('#payment_note_1').redactor('destroy');
  $('#payment_note_1').redactor({
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
    minHeight: 50,
    changeCallback: function (e) {
      var v = this.get();
      localStorage.setItem('payment_note_1', v);
    },
  });

  var old_payment_term;
  $('#slpayment_term')
    .focus(function () {
      old_payment_term = $(this).val();
    })
    .change(function (e) {
      var new_payment_term = $(this).val() ? parseFloat($(this).val()) : 0;
      if (!is_numeric($(this).val())) {
        $(this).val(old_payment_term);
        bootbox.alert(lang.unexpected_value);
        return;
      } else {
        localStorage.setItem('slpayment_term', new_payment_term);
        $('#slpayment_term').val(new_payment_term);
      }
    });
  if ((slpayment_term = localStorage.getItem('slpayment_term'))) {
    $('#slpayment_term').val(slpayment_term);
  }
  $('#add_sale, #draft_sale, #edit_sale').prop('disabled', true);

  // If there is any item in localStorage
  if (localStorage.getItem('slitems')) {
    loadItems();
  }

  // clear localStorage and reload
  $('#reset').click(function (e) {
    bootbox.confirm(lang.r_u_sure, function (result) {
      if (result) {
        if (localStorage.getItem('slitems')) {
          localStorage.removeItem('slitems');
        }
        if (localStorage.getItem('slref')) {
          localStorage.removeItem('slref');
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
        if (localStorage.getItem('slcurrency')) {
          localStorage.removeItem('slcurrency');
        }
        if (localStorage.getItem('sldate')) {
          localStorage.removeItem('sldate');
        }
        if (localStorage.getItem('slstatus')) {
          localStorage.removeItem('slstatus');
        }
        if (localStorage.getItem('slbiller')) {
          localStorage.removeItem('slbiller');
        }
        if (localStorage.getItem('sltb_account')) {
          localStorage.removeItem('sltb_account');
        }

        $('#modal-loading').show();
        location.reload();
      }
    });
  });

  // save and load the fields in and/or from localStorage

  $('#slref').change(function (e) {
    localStorage.setItem('slref', $(this).val());
  });

  if ((slref = localStorage.getItem('slref'))) {
    $('#slref').val(slref);
  }

  if (slbiller = localStorage.getItem('slbiller')) {
    noResetItems = true;
    $('#slbiller').val(slbiller).trigger('change');
  }

  if (slwarehouse = localStorage.getItem('slwarehouse')) {
    noResetItems = true;
    $('#slwarehouse').val(slwarehouse).trigger('change');
  }

  $('#slbiller').change(function (e) {
    localStorage.setItem('slbiller', $(this).val());
    loadItems();
  });

  let oldDisc = 0;
  $('#sldiscount').focus(function() {
    oldDisc = $(this).val();
    console.log('set old discount ' + oldDisc);
  }).change(function() {
    console.log($(this).val());
    if (!isNumber($(this).val())) {
      $(this).val(oldDisc);
      return false;
    }
    console.log('ok');
    localStorage.setItem('sldiscount', $(this).val());
    loadItems();
  });

  $('#slwarehouse').change(function (e) {
    localStorage.setItem('slwarehouse', $(this).val());

    if (Object.keys(slitems).length > 0) {
      getUsersByWarehouseId($(this).val()).then((response) => {
        if ( ! response.error) {

          for (let x in slitems) {
            slitems[x].row.operators = response.users;
          }

          localStorage.setItem('slitems', JSON.stringify(slitems));
          loadItems();
        }
      });
    } else {
      loadItems();
    }
  });

  $('#slnote').redactor('destroy');
  $('#slnote').redactor({
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
    minHeight: 50,
    changeCallback: function (e) {
      var v = this.get();
      localStorage.setItem('slnote', v);
    },
  });
  if ((slnote = localStorage.getItem('slnote'))) {
    $('#slnote').redactor('set', slnote);
  }
  $('#slinnote').redactor('destroy');
  $('#slinnote').redactor({
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
    minHeight: 50,
    changeCallback: function (e) {
      var v = this.get();
      localStorage.setItem('slinnote', v);
    },
  });
  if ((slinnote = localStorage.getItem('slinnote'))) {
    $('#slinnote').redactor('set', slinnote);
  }

  // prevent default action usln enter
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
  $(document).on('click', '.sldel', function () {
    var row = $(this).closest('tr');
    var item_id = row.attr('data-item-id');
    delete slitems[item_id];
    row.remove();
    if (slitems.hasOwnProperty(item_id)) {
    } else {
      localStorage.setItem('slitems', JSON.stringify(slitems));
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
    item_id = row.attr('data-item-id');
    item = slitems[item_id];
    var qty = row
      .children()
      .children('.rquantity')
      .val(),
      unit_price = formatDecimal(
        row
          .children()
          .children('.ruprice')
          .val()
      );

    var net_price = unit_price;
    $('#prModalLabel').text(item.row.name + ' (' + item.row.code + ')');

    uopt = '<p style="margin: 12px 0 0 0;">n/a</p>';
    if (item.units) {
      uopt = $('<select id="punit" name="punit" class="select2" style="width:100%">');
      $.each(item.units, function () {
        if (this.id == item.row.unit) {
          $('<option />', { value: this.id, text: this.name, selected: true }).appendTo(uopt);
        } else {
          $('<option />', { value: this.id, text: this.name }).appendTo(uopt);
        }
      });
    }

    $('#punits-div').html(uopt);
    $('select.select').select2({ minimumResultsForSearch: 7, theme: 'classic' });
    $('#pquantity').val(qty);
    $('#old_qty').val(qty);
    $('#pprice').val(unit_price);
    $('#punit_price').val(formatDecimal(parseFloat(unit_price)));
    $('#old_price').val(unit_price);
    $('#row_id').val(row_id);
    $('#item_id').val(item_id);

    $('#padiscount').val('');
    $('#psubt').val(row.find('.ssubtotal').text());
    $('#net_price').text(formatMoney(net_price));
    $('#prModal')
      .appendTo('body')
      .modal('show');
  });

  $(document).on('change', '#pprice', function () {
    var unit_price = parseFloat($('#pprice').val());

    $('#net_price').text(formatMoney(unit_price));
  });

  $(document).on('change', '#punit', function () {
    var row = $('#' + $('#row_id').val());
    var item_id = row.attr('data-item-id');
    var item = slitems[item_id];
    if (!is_numeric($('#pquantity').val())) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }
    var unit = $('#punit').val(),
      base_quantity = $('#pquantity').val(),
      aprice = 0;

    if (item.units && unit != slitems[item_id].row.base_unit) {
      $.each(item.units, function () {
        if (this.id == unit) {
          base_quantity = unitToBaseQty($('#pquantity').val(), this);
          $('#pprice')
            .val(formatDecimal(parseFloat(item.row.base_unit_price + aprice) * unitToBaseQty(1, this), 4))
            .change();
        }
      });
    } else {
      $('#pprice')
        .val(formatDecimal(item.row.base_unit_price + aprice))
        .change();
    }
  });

  /* -----------------------
   * Edit Row Method
   ----------------------- */
  $(document).on('click', '#editItem', function () {
    var row = $('#' + $('#row_id').val());
    var item_id = row.attr('data-item-id');
    var price = parseFloat($('#pprice').val());
    var unit = $('#punit').val();
    var base_quantity = parseFloat($('#pquantity').val());
    if (unit != slitems[item_id].row.base_unit) {
      $.each(slitems[item_id].units, function () {
        if (this.id == unit) {
          base_quantity = unitToBaseQty($('#pquantity').val(), this);
        }
      });
    }

    if (site.settings.product_discount == 1 && $('#pdiscount').val()) {
      if (!is_valid_discount($('#pdiscount').val()) || ($('#pdiscount').val() != 0 && $('#pdiscount').val() > price)) {
        bootbox.alert(lang.unexpected_value);
        return false;
      }
    }

    if (!is_numeric($('#pquantity').val())) {
      $(this).val(old_row_qty);
      bootbox.alert(lang.unexpected_value);
      return;
    }

    var quantity = parseFloat($('#pquantity').val());
    // if (site.settings.product_discount == 1 && $('#padiscount').val()) {
    //     if (!is_numeric($('#padiscount').val()) || $('#padiscount').val() > price * quantity) {
    //         bootbox.alert(lang.unexpected_value);
    //         return false;
    //     }
    //     discount = formatDecimal(parseFloat($('#padiscount').val()) / quantity, 4);
    // }
    // console.log(discount);

    slitems[item_id].row.fup = 1;
    slitems[item_id].row.qty = quantity;
    slitems[item_id].row.base_quantity = parseFloat(base_quantity);
    slitems[item_id].row.price = price;
    slitems[item_id].row.unit = unit;
    localStorage.setItem('slitems', JSON.stringify(slitems));
    $('#prModal').modal('hide');

    loadItems();
    return;
  });

  /* ------------------------------
   * Show manual item addition modal
   ------------------------------- */
  $(document).on('click', '#addManually', function (e) {
    if (count == 1) {
      slitems = {};
      if ($('#slwarehouse').val() && $('#slcustomer').val()) {

      } else {
        bootbox.alert(lang.select_above);
        item = null;
        return false;
      }
    }
    $('#mnet_price').text('0.00');
    $('#mModal')
      .appendTo('body')
      .modal('show');
    return false;
  });

  $(document).on('click', '#addItemManually', function (e) {
    var mid = new Date().getTime(),
      mcode = $('#mcode').val(),
      mname = $('#mname').val(),
      munit = parseInt($('#munit').val()),
      mqty = parseFloat($('#mquantity').val()),
      mdiscount = $('#mdiscount').val() ? $('#mdiscount').val() : '0',
      unit_price = parseFloat($('#mprice').val());
    if (mcode && mname && mqty && unit_price) {
      slitems[mid] = {
        id: mid,
        item_id: mid,
        label: mname + ' (' + mcode + ')',
        row: {
          id: mid,
          code: mcode,
          name: mname,
          quantity: mqty,
          base_quantity: mqty,
          price: unit_price,
          unit_price: unit_price,
          real_unit_price: unit_price,
          unit: munit,
          qty: mqty,
          type: 'manual',
          discount: mdiscount,
          serial: '',
          option: '',
        },
        units: false,
        options: false,
      };
      localStorage.setItem('slitems', JSON.stringify(slitems));
      loadItems();
    }
    $('#mModal').modal('hide');
    $('#mcode').val('');
    $('#mname').val('');
    $('#munit').val('');
    $('#mquantity').val('');
    $('#mdiscount').val('');
    $('#mprice').val('');
    return false;
  });

  /* --------------------------
  * Edit Row Quantity Method
 --------------------------- */
  var old_row_qty;
  $(document)
    .on('focus', '.squantity, .width, .length, .area, .spec, .operator, .due-date', function () {
      old_row_qty = $(this).val();
    })
    .on('change', '.squantity, .width, .length, .area, .spec, .operator, .due-date', function () {
      let mode = '', new_value, new_w, new_l, new_sqty, new_spec, new_operator, new_due_date;

      if (this.classList.contains('operator')) { // For string value.
        new_operator = $(this).val();
        mode = 'operator';
      } else if (this.classList.contains('due-date')) { // For string value.
        new_due_date = $(this).val();
        mode = 'due_date';
      } else if (this.classList.contains('spec')) { // For string value.
        new_spec = $(this).val();
        mode = 'spec';
      } else {
        new_value = parseFloat($(this).val());

        if (this.classList.contains('width')) {
          mode = 'w';
          new_w = new_value;
        } else if (this.classList.contains('length')) {
          mode = 'l';
          new_l = new_value;
        } else {
          mode = 'sqty';
          new_sqty = new_value;
        }
      }

      var row = $(this).closest('tr');

      if (!is_numeric($(this).val()) && (mode !== 'spec' && mode !== 'operator' && mode !== 'due_date')) {
        $(this).val(old_row_qty);
        bootbox.alert(lang.unexpected_value);
        return;
      }

      var item_id = row.attr('data-item-id');

      //slitems[item_id].row.base_quantity = new_qty;
      /*
            if (slitems[item_id].row.unit != slitems[item_id].row.base_unit) {
              $.each(slitems[item_id].units, function() {
                if (this.id == slitems[item_id].row.unit) {
                  slitems[item_id].row.base_quantity = unitToBaseQty(new_qty, this);
                }
              });
            }
      */
      if (slitems[item_id].row.category_code === 'DPI' && slitems[item_id].row.type == 'combo') {
        if (mode === 'sqty')
          slitems[item_id].row.sqty = new_sqty;
        if (mode === 'w')
          slitems[item_id].row.w = new_w;
        if (mode === 'l')
          slitems[item_id].row.l = new_l;

        slitems[item_id].row.area = slitems[item_id].row.w * slitems[item_id].row.l;
        slitems[item_id].row.qty = slitems[item_id].row.sqty * slitems[item_id].row.area;
        slitems[item_id].row.base_quantity = slitems[item_id].row.qty;
      } else { // IF NOT DPI
        if (mode === 'sqty')
          slitems[item_id].row.sqty = new_sqty;
        slitems[item_id].row.qty = slitems[item_id].row.sqty;
        slitems[item_id].row.base_quantity = slitems[item_id].row.qty;
      }

      if (mode === 'spec') {
        slitems[item_id].row.spec = new_spec;
      }
      if (mode == 'operator') {
        console.log('OPERATOR');
        slitems[item_id].row.operator_id = new_operator;
      }
      if (mode == 'due_date') {
        slitems[item_id].row.due_date = new_due_date;
      }
      localStorage.setItem('slitems', JSON.stringify(slitems));
      loadItems();
    });

  /* --------------------------
   * Edit Row Price Method
   -------------------------- */
  var old_price;
  $(document)
    .on('focus', '.rprice', function () {
      old_price = $(this).val();
    })
    .on('change', '.rprice', function () {
      var row = $(this).closest('tr');
      if (!is_numeric($(this).val())) {
        $(this).val(old_price);
        bootbox.alert(lang.unexpected_value);
        return;
      }
      var new_price = parseFloat($(this).val()),
        item_id = row.attr('data-item-id');
      slitems[item_id].row.price = new_price;
      localStorage.setItem('slitems', JSON.stringify(slitems));
      loadItems();
    });

  $(document).on('click', '#removeReadonly', function () {
    $('#slcustomer').prop('readonly', false);
    $(this).html('<i class="fa fa-fw fa-unlock"></i>');
    //$('#slwarehouse').select2('readonly', false);
    return false;
  });
  $(document).on('click', '#unlock_warehouse', function () {
    $('#slwarehouse').prop('readonly', false);
    $(this).html('<i class="fa fa-fw fa-unlock"></i>');
    return false;
  });

}); // $(document).ready();

/* -----------------------
 * Misc Actions
 ----------------------- */

function nsCustomer() {
  $('#slcustomer').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'customers/suggestions',
      delay: 1000,
      data: function (params) {
        return {
          term: params.term,
          limit: 10,
        };
      },
      processResults: function (data) {
        if (data.results) {
          return { results: data.results };
        } else {
          return { results: [{ id: '', text: 'No Match Found' }] };
        }
      },
    },
    theme: 'classic'
  });
}
//localStorage.clear();
/**
 * Load data tables.
 */
function loadItems() {
  if (localStorage.getItem('slitems')) {
    let edit_mode = localStorage.getItem('edit_mode');
    total = 0;
    count = 1;
    an = 1;

    $('#slTable tbody').empty();
    sldiscount = (localStorage.getItem('sldiscount') ?? 0);
    slitems = JSON.parse(localStorage.getItem('slitems'));

    sortedItems =
      site.settings.item_addition == 1
        ? _.sortBy(slitems, function (o) {
          return [parseInt(o.order)];
        })
        : slitems;
    $('#add_sale, #draft_sale, #edit_sale').prop('disabled', false);

    $.each(sortedItems, function () {
      var item = this;
      var item_id = site.settings.item_addition == 1 ? item.item_id : item.id;
      item.order = item.order ? item.order : new Date().getTime();

      var product_id = item.row.id,
        item_category = item.row.category_code,
        item_type = item.row.type,
        combo_items = item.combo_items,
        item_price = item.row.price,
        item_prefix_code = item.row.code.substr(0, 3),
        item_spec = item.row.spec, // Spec
        item_sqty = item.row.sqty, // Sub-Quantity
        item_finished_qty = item.row.finished_qty,
        item_status = item.row.status,
        item_qty  = item.row.qty,
        item_aqty = item.row.quantity,
        item_name = item.row.name.replace(/"/g, '&#034;').replace(/'/g, '&#039;'),
        item_code = item.row.code,
        item_operator = item.row.operator_id,
        item_due_date = item.row.due_date,
        item_completed_at = item.row.completed_at,
        item_operators = item.row.operators;

      var product_unit = item.row.unit, base_quantity = item.row.base_quantity;
      var unit_price = item.row.price;

      if (item_type == 'combo' || item_type == 'service') {
        var price_ranges_value = JSON.parse(item.row.price_ranges_value);
        if (item_type === 'combo') {
          var
            item_w = item.row.w, // P (Panjang)
            item_l = item.row.l, // L (Lebar)
            item_area = item.row.area; // Area
        }
      }
      if (item.units && item.row.fup != 1 && product_unit != item.row.base_unit) {
        $.each(item.units, function () {
          if (this.id == product_unit) {
            console.log('unit changed.');
            base_quantity = formatDecimal(unitToBaseQty(item.row.qty, this), 4);
            unit_price = formatDecimal(parseFloat(item.row.base_unit_price) * unitToBaseQty(1, this), 4);
          }
        });
      }

      // PRICE RANGE VALUE MANIPULATOR. ( >1 = 19000; >6 = 18000; ...)
      if ((item_type === 'combo' || item_type == 'service') && item.row.fup != 1 && unit_price == item.row.price1) {
        for (let a = price_ranges_value.length - 1; a >= 0; a--) {
          if (item_qty >= price_ranges_value[a]) {
            item_price = item.row['price' + (a + 2)];
            unit_price = item_price;
            break;
          } else {
            item_price = item.row.price1;
            unit_price = item_price;
          }
        }
      }

      unit_price = formatDecimal(unit_price);

      item_price = formatDecimal(unit_price);
      unit_price = formatDecimal(unit_price, 4);

      var row_no = item.id;
      var newTr = $(`<tr id="row_${row_no}" class="row_${item_id}" data-item-id="${item_id}"></tr>`);

      let readonly = (edit_mode == 'operator' ? ' readonly' : '');
      let hidden   = (edit_mode == 'operator' ? 'display-none' : '');
      // GENERATE TABLE DATA

      // PRODUCT CODE - NAME
      tr_html =
        `<td>
          <input name="product_id[]" type="hidden" class="rid" value="${product_id}">
          <input name="product_category[]" type="hidden" class="rcat" value="${item_category}">
          <input name="product_type[]" type="hidden" class="rtype" value="${item_type}">
          <input name="product_code[]" type="hidden" class="rcode" value="${item_code}">
          <input name="product_name[]" type="hidden" class="rname" value="${item_name}">
          <input name="finished_qty[]" type="hidden" value="${item_finished_qty}">
          <input name="status[]" type="hidden" value="${item_status}">
          <input name="completed_at[]" type="hidden" value="${item_completed_at}">
          <span class="sname input-sm" id="name_${row_no}">(${item_code}) ${item_name}</span>
          <i class="pull-right fa fa-edit tip pointer edit ${hidden}" id="${row_no}" data-item="${item_id}" title="Edit" style="cursor:pointer;"></i>
        </td>`;

      // SPEC
      tr_html +=
        `<td>
          <input class="form-control input-sm editor spec input-tip" id="spec_${row_no}" name="spec[]"
            title="Product specification" type="text" value="${item_spec}"${readonly}>
        </td>`;

      // NET UNIT PRICE
      tr_html +=
        `<td class="text-right">
          <input class="form-control input-sm text-right rprice" name="net_price[]" type="hidden" id="price_${row_no}" value="${item_price}">
          <input class="ruprice" name="unit_price[]" type="hidden" value="${unit_price}">
          <input class="realuprice" name="real_unit_price[]" type="hidden" value="${unit_price}">
          <span class="text-right input-sm sprice" id="sprice_${row_no}">${formatMoney(item_price)}</span>
        </td>`;

      // W x L = AREA (M2)
      if (item_category === 'DPI' && item_type == 'combo') {
        tr_html +=
          `<td><input class="form-control editor input-sm text-center tip width" id="w_${row_no}" name="w[]"
          title="Product width"
          type="text" value="${formatQuantity2(item_w)}"${readonly}></td>`;
        tr_html +=
          `<td><input class="form-control editor input-sm text-center tip length" id="l_${row_no}" name="l[]"
          title="Product length"
          type="text" value="${formatQuantity2(item_l)}"${readonly}></td>`;
        tr_html +=
          `<td><input class="form-control input-sm text-center tip area" id="area_${row_no}" name="area[]"
          title="Product area (total size)"
          type="text" value="${formatQuantity2(item_area)}" readonly></td>`;
      } else { // All categories except DPI && combo.
        tr_html +=
          `<td><input class="form-control input-sm text-center" id="w_${row_no}" name="w[]" type="text" value="" readonly></td>`;
        tr_html +=
          `<td><input class="form-control input-sm text-center" id="l_${row_no}" name="l[]" type="text" value="" readonly></td>`;
        tr_html +=
          `<td><input class="form-control input-sm text-center" id="area_${row_no}" name="area[]" type="text" value="" readonly></td>`;
      }

      // SUB QUANTITY
      tr_html +=
        `<td><input class="form-control editor input-sm text-center tip squantity" id="squantity_${row_no}" name="subquantity[]"
        title="Product sub quantity"
        data-prefix="${item_prefix_code}"
        value="${formatQuantity2(item_sqty)}"${readonly}></td>`;

      // TOTAL QUANTITY
      tr_html +=
        `<td>
          <input class="form-control input-sm text-center rquantity tip" name="quantity[]" type="text"
            value="${formatQuantity2(item_qty)}" data-id="${row_no}" data-item="${item_id}" id="quantity_${row_no}"
            title="Product total quantity" readonly>
          <input name="product_unit[]" type="hidden" class="runit" value="${product_unit}">
          <input name="product_base_quantity[]" type="hidden" class="rbase_quantity" value="${base_quantity}">
        </td>`;

      // SUB TOTAL
      // let isReachThreshold = ((item_qty * item_price) >= item_price ? true : false);
      // let subtotal = (parseFloat(item_qty) < 0.5 && !isReachThreshold ? item_price : parseFloat(item_price) * parseFloat(item_qty));
      let subtotal = Math.round(parseFloat(item_price) * parseFloat(item_qty));

      tr_html +=
        `<td class="text-right">
          <span class="text-right input-sm ssubtotal" id="subtotal_${row_no}">
            ${formatMoney(subtotal)}
          </span>
        </td>`;

      // OPERATOR & DUE DATE
      tr_html +=
        `<td class="text-center">
          <select class="select2 operator operator-${item_operator}" style="width:100%;"
            name="operator[]" data-placeholder="Operator" value="${item_operator}">
            <option value=""></option>`;

      if (item_operators) {
        let selected = '';
        let group_name = '';

        for (let operator of item_operators) {
          // console.log(operator);
          if (!operator) continue;
          if (operator.id === item_operator) selected = ' selected';

          group_name = operator.group_name;

          if (group_name.length > 2) group_name = group_name.substr(0, 2); // operator -> op

          tr_html +=
            `<option value="${operator.id}"${selected}>
              ${operator.fullname} (${group_name})
            </option>`;
          if (selected.length > 0) selected = '';
        }
      }

      // Owner or Admin only. name="due_date[]" must be always posted.
      let readonly1 = (!site.permissions.gp || site.permissions.gp['sales-edit'] ? '' : ' readonly');

      tr_html += `</select>
          <input type="text" class="form-control text-center datetime due-date" name="due_date[]"
            placeholder="Due Date" value="${item_due_date}"${readonly1}>
        </td>`;

      // DELETE
      tr_html +=
        `<td class="text-center"><i class="fa fa-times tip pointer sldel ${hidden}" id="${row_no}"
        title="Remove" style="cursor:pointer;"></i></td>`;
      newTr.html(tr_html);
      newTr.prependTo('#slTable');
      total += formatDecimal(subtotal, 4);
      count += parseFloat(item_qty);
      an++;

      if (item_type == 'standard' && base_quantity > item_aqty) {
        $('#row_' + row_no).addClass('danger');
        if (site.settings.overselling != 1) {
          $('#add_sale, #draft_sale, #edit_sale').prop('disabled', true);
        }
      } else if (item_type == 'combo') {
        if (combo_items === false) {
          console.log('%cNo combo items', 'color:red');
          $('#row_' + row_no).addClass('danger');
          if (site.settings.overselling != 1) {
            $('#add_sale, #draft_sale, #edit_sale').prop('disabled', true);
          }
        } else {
          $.each(combo_items, function () {
            if (parseFloat(this.quantity) < parseFloat(this.qty) * base_quantity && this.type == 'standard') {
              $('#row_' + row_no).addClass('danger');
              if (site.settings.overselling != 1) {
                $('#add_sale, #draft_sale, #edit_sale').prop('disabled', true);
              }
            } else if (parseFloat(this.quantity) < parseFloat(this.safety_stock)) {
              $('#row_' + row_no).addClass('warning');
            }
          });
        }
      }
    });

    total -= sldiscount;

    var col = 3;

    var tfoot =
      `<tr id="tfoot" class="tfoot active">
      <th colspan="${col}">Total</th>`;

    tfoot +=
      '<th class="text-center"></th>';

    tfoot +=
      '<th class="text-center"></th>';

    tfoot +=
      '<th class="text-center"></th>';

    tfoot +=
      '<th class="text-center"></th>';

    // Total Quantity
    tfoot += `<th class="text-center">${formatQuantity2(count - 1)}</th>`;

    // Subtotal
    tfoot +=
      `<th class="text-right">${formatMoney(total)}</th>`;

    // Operator & Due Date
    tfoot += '<th></th>';

    // Delete
    tfoot +=
      `<th class="text-center"><i class="fa fa-trash" style="opacity:0.5; filter:alpha(opacity=50);"></i>
      </th></tr>`;
    $('#slTable tfoot').html(tfoot);

    // Totals calculations after item addition
    var gtotal = parseFloat(total);
    $('#total').text(formatMoney(total));
    $('#titems').text(an - 1 + ' (' + (parseFloat(count) - 1) + ')');
    $('#total_items').val(parseFloat(count) - 1);

    $('#gtotal').text(formatMoney(gtotal));
    if (an > parseInt(site.settings.bc_fix) && parseInt(site.settings.bc_fix) > 0) {
      // $('html, body').animate({ scrollTop: $('#sticker').offset().top }, 500);
      // $(window).scrollTop($(window).scrollTop() + 1);
    }

    if (Object.keys(slitems).length) {
      initSelect2();
    }

    set_page_focus();

    $('.tip').tooltip();
    $('.input-tip').tooltip();
  }
}

/* -----------------------------
 * Add Sale Order Item Function
 * @param {json} item
 * @returns {Boolean}
 ---------------------------- */
function add_invoice_item(item) {
  if (count == 1) {
    if ($('#slwarehouse').val() && $('#slcustomer').val()) {

    } else {
      bootbox.alert(lang.select_above);
      item = null;
      return;
    }
  }
  if (item == null) return;

  let item_id = item.id;

  if (!isObject(slitems)) { // See common.js
    window.slitems = {}; // Must be instance of object and not instance of array.
  }

  if (slitems[item_id]) {
    let new_qty = parseFloat(slitems[item_id].row.qty) + 1;
    slitems[item_id].row.base_quantity = new_qty;
    if (slitems[item_id].row.unit != slitems[item_id].row.base_unit) {
      $.each(slitems[item_id].units, function () {
        if (this.id == slitems[item_id].row.unit) {
          slitems[item_id].row.base_quantity = unitToBaseQty(new_qty, this);
        }
      });
    }
    slitems[item_id].row.qty = new_qty;
    slitems[item_id].row.sqty = new_qty;
  } else {
    slitems[item_id] = item;
    slitems[item_id].row.qty = slitems[item_id].row.sqty;
  }

  if (slitems[item_id].row.category_code === 'DPI' && slitems[item_id].row.type == 'combo') {
    slitems[item_id].row.w = 1;
    slitems[item_id].row.l = 1;
    slitems[item_id].row.area = 1;
  }

  slitems[item_id].order = new Date().getTime();
  localStorage.setItem('slitems', JSON.stringify(slitems));
  loadItems();
  return true;
}

if (typeof Storage === 'undefined') {
  $(window).bind('beforeunload', function (e) {
    if (count > 1) {
      var message = 'You will loss your data!';
      return message;
    }
  });
}
// EOF