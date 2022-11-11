
function addItems (items) {
  let rnd = '';
  let so_items = {};

  if (soit = localStorage.getItem('so_items')) {
    so_items = JSON.parse(soit);
  }

  if (Array.isArray(items)) { // Multiple items.
    for (let item of items) {
      rnd = randomString(8);
      item.row_id = rnd;
      item.first_qty = 0;
      item.reject_qty = 0;
      so_items[rnd] = item;
    }
  } else if (items instanceof Object && ! items instanceof Array) { // Single item.
    rnd = randomString(8);
    items.row_id = rnd;
    items.first_qty = 0;
    item.reject_qty = 0;
    so_items[rnd] = items;
  }

  localStorage.setItem('so_items', JSON.stringify(so_items));

  loadItems();
}

/**
 * Load items for add SO only. NOT edit/update.
 * @returns {boolean}
 */
function loadItems () {
  let so_items = {};
  so_status = localStorage.getItem('so_status');

  if (items = localStorage.getItem('so_items')) {
    so_items = JSON.parse(items);

    $('#soTable tbody').empty();

    let deleteButton = (session.group_id == 1 || session.group_id == 2 ? '<i class="fad fa-times dt-delete pointer"></i>' : '');

    $.each(so_items, function () {
      let item = this;
      let itemQty = (!site.permissions.gp || site.permissions.gp['products-so_quantity'] ? parseFloat(item.quantity) : btoa(item.quantity));

      let tr_html =
        `<tr data-row-id="${item.row_id}">
          <td><input type="hidden" name="item_id[]" value="${item.id}">(${item.code}) ${item.name}</td>
          <td class="text-center">${item.unit_name}</td>
          <td>
            <input type="hidden" name="real_qty[]" value="${itemQty}">
            <input type="number" name="first_qty[]" class="form-control so-formula text-center" step="0.1" value="${item.first_qty}">
          </td>
          <td>
            <input type="number" name="reject_qty[]" class="form-control text-center" step="0.1" value="${item.reject_qty}">
          </td>
          <td class="text-center">${deleteButton}</td>
        </tr>`;

      $('#soTable tbody').append(tr_html);
    });

    return true;
  }

  return false;
}

$(document).on('dblclick', '.so-formula', function() {
  showModal(site.base_url + 'products/stock_opname/formula', 'modal-lg')
});

$(document).on('change', '[name="first_qty[]"], [name="reject_qty[]"]', function (e) {
  console.log('changed');
  let row_id = $(this).closest('tr').data('row-id');
  let so_items = JSON.parse(localStorage.getItem('so_items'));

  if ($(this).prop('name') == 'first_qty[]') {
    so_items[row_id].first_qty = $(this).val();
  } else if ($(this).prop('name') == 'reject_qty[]') {
    so_items[row_id].reject_qty = $(this).val();
  }
  localStorage.setItem('so_items', JSON.stringify(so_items));
  loadItems();
});

$(document).on('click', '.dt-delete', function (e) {
  e.preventDefault();
  let tr = $(this).parents('tr');
  let row_id = $(tr).data('row-id');
  let items = localStorage.getItem('so_items');
  let so_items = JSON.parse(items);

  if (so_items.hasOwnProperty(row_id)) {
    delete so_items[row_id];
  }

  localStorage.setItem('so_items', JSON.stringify(so_items));
  loadItems();
});

$(document).ready(function () {
  loadItems();
})