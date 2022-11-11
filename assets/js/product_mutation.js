
class ProductMutation {
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

$(function() {
  let lastValue = 0;

  $(document).on('change', 'input.pm-quantity', function () {
    if (parseFloat(this.value) > parseFloat(this.dataset.quantity)) {
      $(this).val(lastValue);
      toastr.error('Tidak boleh lebih dari stok yang ada.');
      return false;
    }

    if (parseFloat(this.value) < 0 || (lastValue > 0 && parseFloat(this.value) == 0)) {
      $(this).val(lastValue);
      toastr.error('Tidak boleh ada stok kosong atau minus.');
      return false;
    }
  });

  $(document).on('focus', 'input.pm-quantity', function () {
    lastValue = this.value;
  });
});