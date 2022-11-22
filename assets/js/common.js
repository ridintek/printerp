// Custom Javascript Code
$(document).on('click', '[href$="#"]', function (e) {
  e.preventDefault();
});
/**
 * Change this for new updated version.
 */
$.fn.PrintERP = {
  version: '2.3'
};

// My own jQuery plugins for extended filter. DataTables v1.9.0
$.fn.datatableFilter = function (opt = null) {
  let hDTFilterTimeOut;
  let filter_name = $(this).data('name') + '_dtfilter';
  let inpFilter = $(this);
  if (!$(this).parent().find('.dtfilter-clean').length) {
    $(this).after(
      `<div class="input-group-addon dtfilter-clean" style="padding: 2px 8px;border-left:0; display:none">
        <a href="#" class="tip" title="Clear Search">
          <i class="fad fa-times"></i>
        </a>
      </div>`);
  }
  $(this).parent().find('.dtfilter-clean').find('a').on('click', function (e) {
    e.preventDefault();
    localStorage.removeItem(filter_name);
    inpFilter.val('');
    hDTFilterTimeOut = window.setTimeout(() => {
      if (oTable) oTable.fnFilter('');
    }, 500);
    $(this).parents('.dtfilter-clean').toggle();
  });
  $(this).parent().find('.dtfilter-search').find('a').on('click', function (e) {
    e.preventDefault();
    if (oTable) oTable.fnFilter(inpFilter.val());
  });
  $(this).on('keyup', function (e) { // NEW ADDED.
    if (!acceptableChar(e.keyCode)) return false;
    if (hDTFilterTimeOut) window.clearTimeout(hDTFilterTimeOut);
    if ($(this).val().length > 0) {
      localStorage.setItem(filter_name, $(this).val());
    } else {
      localStorage.removeItem(filter_name);
    }
    if ($(this).val()) {
      $(this).parent().find('.dtfilter-clean').show();; // Toggle cross btn.
    } else {
      $(this).parent().find('.dtfilter-clean').hide(); // Toggle cross btn.
    }
    hDTFilterTimeOut = window.setTimeout(() => {
      if (oTable) oTable.fnFilter(inpFilter.val());
    }, 500);
    return false;
  });
  $(this).on('keypress', function (e) {
    if (!acceptableChar(e.keyCode)) e.preventDefault();
  });
  if (filter = localStorage.getItem(filter_name)) {
    $(this).val(filter).trigger('change');
    $(this).parent().find('.dtfilter-clean').show();
    if (oTable) oTable.fnFilter(filter);
  }
}
/**
 * New DataTable filter for v2 up.
 * @param {*} opt
 */
$.fn.dataTableFilter = function (opt = null) { // My own jQuery plugins for extended filter.
  let hDTFilterTimeOut;
  let filter_name = $(this).data('name') + '_dtfilter';
  let inpFilter = $(this);
  if (!$(this).parent().find('.dtfilter-clean').length) {
    $(this).after(
      `<div class="input-group-addon dtfilter-clean" style="padding: 2px 8px;border-left:0; display:none">
        <a href="#" class="tip" title="Clear Search">
          <i class="fad fa-times"></i>
        </a>
      </div>`);
  }
  $(this).parent().find('.dtfilter-clean').find('a').on('click', function (e) {
    e.preventDefault();
    localStorage.removeItem(filter_name);
    inpFilter.val('');
    hDTFilterTimeOut = window.setTimeout(() => {
      if (Table) Table.search('').draw();
    }, 500);
    $(this).parents('.dtfilter-clean').toggle();
  });

  $(this).parent().find('.dtfilter-search').find('a').on('click', function (e) {
    e.preventDefault();
    if (Table) Table.search(inpFilter.val()).draw();
  });

  $(this).on('keyup', function (e) { // NEW ADDED.
    if (!acceptableChar(e.keyCode)) return false;
    if (hDTFilterTimeOut) window.clearTimeout(hDTFilterTimeOut);
    if ($(this).val().length > 0) {
      localStorage.setItem(filter_name, $(this).val());
    } else {
      localStorage.removeItem(filter_name);
    }
    if ($(this).val()) {
      $(this).parent().find('.dtfilter-clean').show();; // Toggle cross btn.
    } else {
      $(this).parent().find('.dtfilter-clean').hide(); // Toggle cross btn.
    }
    hDTFilterTimeOut = window.setTimeout(() => {
      if (Table) Table.search(inpFilter.val()).draw();
    }, 500);
    return false;
  });

  $(this).on('keypress', function (e) {
    if (!acceptableChar(e.keyCode)) e.preventDefault();
  });

  if (filter = localStorage.getItem(filter_name)) {
    $(this).val(filter).trigger('change');
    $(this).parent().find('.dtfilter-clean').show();
    if (Table) Table.search(filter).draw();
  }
}

$(document).on('click', '[data-toggle="delete-row"]', function (e) {
  let tbody = this.closest('tbody');

  tbody.removeChild(this.closest('tr[data-id]'));
});

$(document).on('click', '[data-toggle="delete-batch"]', function (e) {
  e.preventDefault();

  let message = this.dataset?.message ?? 'Hapus semua item yang dipilih?';
  let title = this.dataset?.title ?? 'Hapus massal';
  let url = this?.href ?? this.dataset?.remoteUrl;

  addConfirm({
    title: title,
    message: message,
    onok: () => {
      let data = {};
      let vals = document.querySelectorAll('input[name="val[]"]');

      data.val = [];

      for (let val of vals) {
        if (val.checked) {
          data.val.push(val.value);
        }
      }

      data[security.csrf_token_name] = security.csrf_hash;

      $.ajax({
        data: data,
        error: (xhr) => {
          if (xhr.responseJSON) toastr.error(xhr.responseJSON.message);
        },
        method: 'POST',
        success: function (data) {
          if (isObject(data)) {
            if (data.status >= 200 && data.status < 300) {
              if (typeof oTable == 'object') oTable.fnDraw(false);
              if (typeof Table == 'object') Table.draw(false);
              if (typeof Table2 == 'object') Table2.draw(false);

              toastr.success(data.message);
            }
          } else {
            toastr.error('Something error');
          }
        },
        url: url
      });
    }
  });
});

/**
 * Clear all notifications.
 */
$(document).on('click', '.clearAllNotifications', function () {
  $.ajax({
    success: function (data) {
      $.each($('#content').find('.alert'), function () {
        $(this).remove();
      });
    },
    url: site.base_url + 'welcome/hideAllNotifications'
  });
});

$(document).on('click', '[data-action="confirm"]', function (e) {
  e.preventDefault();
  e.stopPropagation();

  let data = {};
  let labels = this.dataset.labels ?? null;
  let message = this.dataset.message ?? 'Are you sure?';
  let method = this.dataset.method ?? 'GET';
  let title = this.dataset.title ?? 'Confirmation';

  if (method.toUpperCase() == 'POST') {
    data[security.csrf_token_name] = security.csrf_hash;
  }

  addConfirm({
    labels: JSON.parse(labels),
    message: message,
    onok: () => {
      console.log(this.href);
      $.ajax({
        data: data,
        error: (xhr) => {
          addAlert(xhr.responseJSON.message, 'danger');
          toastr.error(xhr.responseJSON.message);
        },
        method: method,
        success: (data) => {
          if (isObject(data)) {
            if (data.status == 200 || data.success || (typeof data.error != 'undefined' && !data.error)) {
              if (typeof oTable == 'object') oTable.fnDraw(false);
              if (typeof Table == 'object') Table.draw(false);
              if (typeof Table2 == 'object') Table2.draw(false);

              if (data.msg) data.message = data.msg;

              addAlert(data.message, 'success');
              toastr.success(data.message);
            } else {
              addAlert(data.message, 'danger');
              toastr.error(data.message);
            }
          } else {
            addAlert('Unknown error.', 'danger');
            addAlert(data, 'danger');
          }
        },
        url: this.href
      })
    },
    title: title,
  })
});

var Notify = {
  error: function (text, position = 'top-right') {
    alertify.set('notifier', 'position', position);
    alertify.error(`<span style="color: white;">${text}</span>`);
  },
  message: function (text, position = 'top-right') {
    alertify.set('notifier', 'position', position);
    alertify.message(text);
  },
  success: function (text, position = 'top-right') {
    alertify.set('notifier', 'position', position);
    alertify.success(text);
  },
  warning: function (text, position = 'top-right') {
    alertify.set('notifier', 'position', position);
    alertify.warning(`<span style="color: white;">${text}</span>`);
  }
}

$(document).on('keyup', '.separator', function (e) {
  if (e.key != '.') $(this).val(formatSeparator(this.value));
});
/**
 * Currency class
 * Change format in edit and format back to currency after lost focus.
 * 1000000 => 1,000,000
 */
$(document).on('keyup', '.currency', function (e) {
  if (e.key != '.') $(this).val(formatCurrency($(this).val())).trigger('change');
});
$(document).on('blur', '.quantity', function () {
  if (isNaN(this.value)) {
    $(this).val(window._lastValue).trigger('change');
    return false;
  }

  $(this).val(parseFloat(this.value)).trigger('change');
});
$(document).on('focus', '.quantity', function () {
  window._lastValue = this.value;
  this.select();
});
let old_key = null;
$(document).on('keyup', function (e) {
  if (old_key == null && e.key == 'a') {
    old_key = e.key;
  } else if (old_key == 'a' && e.key == 'p') {
    old_key = e.key;
  } else if (old_key == 'p' && e.key == 'p') {
    old_key = e.key;
  } else if (old_key == 'p' && e.key == 'v') {
    old_key = e.key;
  } else if (old_key == 'v' && e.key == 'e') {
    old_key = e.key;
  } else if (old_key == 'e' && e.key == 'r') {
    old_key = e.key;
  } else if (old_key == 'r' && e.key == 's') {
    old_key = e.key;
  } else if (old_key == 's' && e.key == 'i') {
    old_key = e.key;
  } else if (old_key == 'i' && e.key == 'o') {
    old_key = e.key;
  } else if (old_key == 'o' && e.key == 'n') {
    bootbox.alert(`<div class="text-center"><strong>PrintERP v${$.fn.PrintERP.version}</strong></div><br>
    jQuery v${$.fn.jquery} (Custom)<br>
    Bootstrap v3.0.2<br>
    BootstrapValidator v0.5.2<br>
    DataTable v${$.fn.DataTable.version}<br><br>
    Copyright &copy; 2020 Indoprinting.`);
  } else {
    old_key = null;
  }
});

function acceptableChar(code) {
  if (code == 8 || code == 0x2E) return true; // Backspace
  if (code == 0x2D) return true; // -
  if (code == 0x5F) return true; // _
  if (code == 0x2F) return true; // /
  if (code == 0x20) return true; // Space
  if (code >= 0x30 && code <= 0x39) return true; // 0 - 9
  if (code >= 0x41 && code <= 0x5A) return true; // A-Z
  if (code == 0x56) return true; // on paste
  if (code == 0x5C) return true; // \
  if (code >= 0x61 && code <= 0x7A) return true; // a-z
  console.log('acceptableChar false');
  return false;
}

function addConfirm(opt) {
  let message = (opt.message ?? 'Are you sure?');
  let title = (opt.title ?? 'Confirm');
  let oncancel = (opt.oncancel ?? null);
  let onclose = (opt.onclose ?? null);
  let onok = (opt.onok ?? null);
  let labels = (opt.labels ?? null); // {ok: 'Ya', cancel: 'Batal'}

  let options = {
    message: message,
    title: title,
    transition: 'zoom'
  };

  if (oncancel) options.oncancel = oncancel;
  if (onclose) options.onclose = onclose;
  if (onok) options.onok = onok;
  if (labels) options.labels = labels;

  alertify.confirm().set(options).showModal();
}

function attachment(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachment2(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal2">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentAdjustment(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=adjustments" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentExpense(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=expenses" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentInternalUse(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=internal_uses" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentPurchase(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=purchases" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentSale(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=sales" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function attachmentStockOpname(x) {
  return (x == null
    ? ''
    : `<div class="text-center">
        <a href="#" data-remote="${site.base_url}gallery/view?name=${x}&path=stock_opnames" data-toggle="modal" data-modal-class="modal-lg" data-target="#myModal">
          <i class="fad fa-file-download"></i>
        </a>
      </div>`);
}

function dateTime(str = Date.now()) {
  let d = new Date(str);
  let Y = d.getFullYear();
  let M = append_zero(d.getMonth() + 1);
  let D = append_zero(d.getDate());
  let h = append_zero(d.getHours());
  let m = append_zero(d.getMinutes());
  return `${Y}-${M}-${D}T${h}:${m}`;
}

function filterQueryString(query) {

}

/**
 * Convert number string into float number.
 * @param {string} str Number string to convert.
 */
function filterDecimal(str) {
  if (str == null) str = 0;
  if (str.toString().length == 0) str = 0;
  if (typeof str == 'string') str = str.replaceAll(/([^0-9\.\-])/g, '');
  if (isNaN(parseFloat(str))) str = 0;

  return parseFloat(str);
}

/**
 * Formatting number to currency.
 * @param {string|number} str Amount to format.
 * @return {string} Return currency format string.
 *
 * Example Return:
 * - 12450.23 => 12,450
 */
function formatCurrency(str) { // Added 2020-05-15 09:50 +7
  return new Intl.NumberFormat('en-US', {
    style: 'currency', currency: 'IDR', currencyDisplay: 'narrowSymbol',
    maximumFractionDigits: 0, minimumFractionDigits: 0
  }).format(filterDecimal(str));
}

function formatNumber(str) {
  return `<div class="text-right">${formatQuantity(str)}</div>`;
}

/**
 * Formatting number to quantity string.
 * @param {string|number} str Quantity to format
 * @return {string} Return quantity format string.
 *
 * @example
 * formatQuantity(12450.238928); // Return 12,450.238928
 * formatQuantity(3958); // Return 3,958
 */
function formatQuantity(str) {
  if (str == null) return 0;
  if (str.toString().length == 0) return 0;
  if (typeof str == 'string') str = str.replaceAll(/([^0-9\.\-])/g, '');
  let round = parseFloat(str);

  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 6 }).format(round);
}

function formatSeparator(str) {
  return new Intl.NumberFormat('en-US', {
    maximumFractionDigits: 6, minimumFractionDigits: 0
  }).format(filterDecimal(str));
}

/**
 * Formatting number to stock.
 * @param {string|number} str Stock to format.
 *
 * @example
 * formatStock(1234.5677); // Return 1,234.57
 */
function formatStock(str) {
  if (str == null) return 0;
  if (str && str.toString().length == 0) return 0;
  let round = parseFloat(str);

  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(round);
}

function getDaysInMonth(year = null, month = null) {
  let y = (year ?? (new Date()).getFullYear());
  let m = (month ?? (new Date()).getMonth() + 1);
  return (new Date(y, m, 0)).getDate();
}

function getMarkonPrice(price, markon) {
  let mprice = (price / (1 - (markon / 100)));
  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(mprice);
}

/**
 * Get price by prices, ranges and quantity.
 * @param {array} ranges [3, 6, 10]
 * @param {array} prices [1000, 900, 800, 500]
 * @param {number} quantity
 * @returns {number} Return price
 */
function getPriceRanges(ranges, prices, quantity = 1) {
  let price = 0;

  for (let a = ranges.length - 1; a >= 0; a--) {
    if (quantity >= ranges[a]) {
      price = prices[a + 1];
      break;
    } else {
      price = prices[0]; // Default
    }
  }

  return price;
}

async function getUsersByWarehouseId(warehouse_id) {
  let prom = new Promise((resolve, reject) => {
    $.ajax({
      error: function (data) {
        reject(data);
      },
      success: function (data) {
        resolve(data);
      },
      url: site.url + 'api/v1/users?warehouse=' + warehouse_id
    });
  });

  return prom;
}

async function getProductInfo(params) {
  let prom = new Promise((resolve, reject) => {
    if (params instanceof Object && !Array.isArray(params)) {
      if (
        params.hasOwnProperty('id') || params.hasOwnProperty('ids') ||
        params.hasOwnProperty('code') || params.hasOwnProperty('name')) {
        $.ajax({
          data: params,
          error: function () {
            reject('ajax error');
          },
          method: 'GET',
          success: function (data) {
            resolve(data);
          },
          url: site.base_url + 'products/info'
        });
      }
    } else {
      reject('error');
    }
  });

  return prom;
}

function html_entity_decode(html) {
  let n;

  if (html) {
    n = html.replaceAll(/(\&lt\;)/g, '<');
    n = n.replaceAll(/(\&gt\;)/gi, '>');
    n = n.replaceAll(/(\&amp\;)/gi, "&");
    n = n.replaceAll(/(\&apos\;)/gi, "'");
    n = n.replaceAll(/(\&bsol\;)/gi, "\\");
    n = n.replaceAll(/(\&col\;)/gi, ":");
    n = n.replaceAll(/(\&copy\;)/gi, "©");
    n = n.replaceAll(/(\&equals\;)/gi, "=");
    n = n.replaceAll(/(\&nbsp\;)/gi, " ");
    n = n.replaceAll(/(\&NewLine\;)/gi, "\n");
    n = n.replaceAll(/(\&quot\;)/gi, '"');
    n = n.replaceAll(/(\&reg\;)/gi, '®');
    n = n.replaceAll(/(\&semi\;)/gi, ';');
    n = n.replaceAll(/(\&sol\;)/gi, '/');
  }

  return n;
}

function htmlentities(html) {
  let n;

  if (html) {
    n = html.replaceAll(/(\<)/g, '&lt;');
    n = n.replaceAll(/(\>)/g, '&gt;');
    n = n.replaceAll(/(\&)/g, '&amp;');
    n = n.replaceAll(/(\')/g, '&apos;');
    n = n.replaceAll(/(\\)/g, '&bsol;');
    n = n.replaceAll(/(\:)/g, '&col;');
    n = n.replaceAll(/(\©)/g, '&copy;');
    n = n.replaceAll(/(\=)/g, '&equals;');
    n = n.replaceAll(/(\ )/g, '&nbsp;');
    n = n.replaceAll(/(\n)/g, '&NewLine;');
    n = n.replaceAll(/(\")/g, '&quot;');
    n = n.replaceAll(/(\®)/g, '&reg;');
    n = n.replaceAll(/(\;)/g, '&semi;');
    n = n.replaceAll(/(\/)/g, '&sol;');
  }

  return n;
}

/**
 * HTTP GET Request.
 * @param {*} url
 * @param {*} data
 */
function httpGet(url, data = null, options = {}) {
  return httpRequest('GET', url, data, options);
}

/**
 * HTTP POST Request.
 * @param {*} url
 * @param {*} data
 */
function httpPost(url, data = null, options = {}) {
  return httpRequest('POST', url, data, options);
}

/**
 * Make HTTP Request.
 * @param {*} method
 * @param {*} url
 * @param {*} data
 * @param {*} options
 */
function httpRequest(method, url, data = null, options = {}) {
  let ajaxopt = {};

  if (data) ajaxopt.data = data;
  if (options.dataType) ajaxopt.dataType = options.dataType;
  if (options.error) ajaxopt.success = options.error;
  if (options.success) ajaxopt.success = options.success;

  ajaxopt.async = true;
  ajaxopt.method = method;
  ajaxopt.url = url;

  // console.log(method, url, options);

  $.ajax(ajaxopt);
}

function initiCheck() {
  $('input[type="checkbox"], [type="radio"]').not('.skip').iCheck({
    checkboxClass: 'icheckbox_square-blue',
    radioClass: 'iradio_square-blue',
    increaseArea: '20%' // optional
  });
}

function initControls() {
  initDateTimePicker();
  initiCheck();
  initSelect2();
}

function initDateTimePicker() {

}

function initSelect2() {
  $('select.select2')
    .not('.skip')
    .select2({ theme: 'classic' });

  $('select.select2-tags')
    .not('.skip')
    .select2({ tags: true, theme: 'classic' });

  $('select[name$="_length"]').not('.skip').select2({ theme: 'classic' }); // Datatable.

  $('#customer, #rcustomer, #slcustomer, .rcustomer, select.ssr-customer').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'customers/suggestions',
      dataType: 'json',
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

  $('select.product-combo').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'products/suggestion_select',
      dataType: 'json',
      delay: 1000,
      data: function (params) {
        return {
          term: params.term,
          type: 'combo',
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

  $('select.product-service').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'products/suggestion_select',
      dataType: 'json',
      delay: 1000,
      data: function (params) {
        return {
          term: params.term,
          type: 'service',
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

  $('select.product-service-standard').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'products/suggestion_select',
      dataType: 'json',
      delay: 1000,
      data: function (params) {
        return {
          term: params.term,
          type: 'service,standard',
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

  $('select.product-standard').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'products/suggestion_select',
      dataType: 'json',
      delay: 1000,
      data: function (params) {
        return {
          term: params.term,
          type: 'standard',
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

  $('#subcategories, select.subcategories').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'products/getSubCategories',
      dataType: 'json',
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

  $('#supplier, #rsupplier, select.rsupplier, select.supplier').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'suppliers/suggestions',
      dataType: 'json',
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

  $('#user, select.user').select2({
    minimumInputLength: 1,
    ajax: {
      url: site.base_url + 'users/suggestions',
      dataType: 'json',
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

function input_text(name, value = '', tags = '') {
  return `<input type="text" name="${name}" ${tags} value="${value}">`;
}

function isArray(a) {
  return Array.isArray(a);
}

function isNumber(a) {
  return (a === typeof 'number' || isFinite(a));
}

function isObject(a) {
  return (a instanceof Object && !Array.isArray(a));
}

function newParseFloat(str) {
  let s = str.replaceAll(/([^\-\.0-9Ee])/g, '');
  return parseFloat(s);
}

function newParseInt(str) {
  let s = str.replaceAll(/([^\-\.0-9Ee])/g, '');
  return parseInt(s);
}

function notes(note) {
  if (note) {
    let n = note.replaceAll(/\&NewLine\;/gi, '');
    n = html_entity_decode(n);
    return n;
  }
  return '';
}

/**
 * Pre-select select2 from AJAX with specified category id.
 * @param {object|string} elm Select2 element.
 * @param {string} category_code Category Code to fetch from server.
 */
function preSelectCategory(elm, category_code) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        code: category_code,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'products/getCategorySuggestions'
    });
  } else {
    console.warn('preSelectCategory: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified customer id.
 * @param {object|string} elm Select2 element.
 * @param {number} customer_id Customer ID to fetch from server.
 */
function preSelectCustomer(elm, customer_id) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        id: customer_id,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'customers/suggestions'
    });
  } else {
    console.warn('preSelectCustomer: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified category id.
 * @param {object|string} elm Select2 element.
 * @param {number} category_code Sub-Category ID to fetch from server.
 */
function preSelectSubCategory(elm, category_code) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        code: category_code,
        limit: 10
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'products/getSubCategorySuggestions'
    });
  } else {
    console.warn('preSelectSubCategory: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified subunit id.
 * @param {object|string} elm Select2 element.
 * @param {number} supplier_id Sub-Unit ID to fetch from server.
 */
function preSelectSubUnit(elm, subunit_id) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        id: subunit_id,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          let def = true;
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, (def ? true : false))).trigger('change');
            def = false;
          }
        }
      },
      url: site.base_url + 'products/getSubUnits'
    });
  } else {
    console.warn('preSelectSubUnit: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified supplier id.
 * @param {object|string} elm Select2 element.
 * @param {number} supplier_id Supplier ID to fetch from server.
 */
function preSelectSupplier(elm, supplier_id) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        id: supplier_id,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'suppliers/suggestions'
    });
  } else {
    console.warn('preSelectSupplier: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified unit id.
 * @param {object|string} elm Select2 element.
 * @param {number} unit_id Unit ID to fetch from server.
 */
function preSelectUnit(elm, unit_id) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        id: unit_id,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'products/unit_suggestions'
    });
  } else {
    console.warn('preSelectUnit: Element type is unknown.');
  }
}

/**
 * Pre-select select2 from AJAX with specified user id.
 * @param {object|string} elm Select2 element.
 * @param {number} user_id User ID to fetch from server.
 */
function preSelectUser(elm, user_id) {
  let e = (typeof elm === 'string' ? $(elm) : (typeof (elm) === 'object' && elm.length > 0 ? elm : null));
  if (e) {
    $.ajax({
      data: {
        id: user_id,
        limit: 1
      },
      method: 'GET',
      success: function (data) {
        if (typeof data === 'object' && data.results) {
          for (let result of data.results) {
            e.append(new Option(result.text, result.id, true, true)).trigger('change');
          }
        }
      },
      url: site.base_url + 'auth/suggestions'
    });
  } else {
    console.warn('preSelectUser: Element type is unknown.');
  }
}

/**
 * Generate random string.
 * @param {number} length Length of random string in bytes. Default 4.
 */
function randomString(length = 4) {
  let buff = new Uint8Array(length);
  let data = '';

  window.crypto.getRandomValues(buff);

  for (let a = 0; a < buff.length; a++) {
    data += buff[a].toString(16);
  }

  return data;
}

function renderActive(active) {
  let status = (active ? 'active' : 'inactive');
  let label = (status == 'active' ? 'success' : 'danger');
  return `<div class="text-center"><span class="label label-${label}">${ucwords(status)}</span></div>`;
}

function renderNumber(qty) {

}

/**
 * Rendering status as label. See ridintek_helper.php:renderStatus().
 * @param {string} status Status name
 */
function renderStatus(status) {
  if (!status) return null;
  if (status == 'null') return null;

  let label = 'default';
  let st = status.toLowerCase();
  let danger = [
    'bad', 'decrease', 'due', 'due_partial', 'expired', 'failed', 'need_approval', 'need_payment',
    'off', 'over_due', 'over_received', 'returned', 'skipped'
  ];
  let info = [
    'completed_partial', 'confirmed', 'installed_partial', 'ordered', 'partial',
    'preparing', 'received_partial', 'serving', 'solved'
  ];
  let primary = ['delivered', 'excellent', 'finished', 'received'];
  let success = ['approved', 'completed', 'increase', 'good', 'installed', 'paid', 'sent',
    'served', 'success', 'validated', 'verified'
  ];
  let warning = [
    'called', 'cancelled', 'checked', 'draft', 'packing', 'pending', 'slow', 'trouble',
    'waiting', 'waiting_production', 'waiting_transfer'
  ];

  if (danger.includes(st)) {
    label = 'danger';
  } else if (info.includes(st)) {
    label = 'info';
  } else if (primary.includes(st)) {
    label = 'primary';
  } if (success.includes(st)) {
    label = 'success';
  } else if (warning.includes(st)) {
    label = 'warning';
  }

  return `<div class="text-center"><span class="label label-${label} status">${ucwords(st)}</span></div>`;
}

function serialize(obj) {
  let result = '';
  if (typeof obj === 'object' && !Array.isArray(obj)) {
    for (let prop in obj) {
      result += `${prop}=${obj[prop]}&`;
    }
  }
  return trimFilter(result);
}

/**
 * Show ajax loader
 * @param {boolean} show If true, show the loader otherwise hide it. Default true.
 */
function showLoader(show = true) {
  if (show) {
    // $('#modal-loading').show(); // Show loading bar.
    $('#ajaxCall').show();
    $('.blackbg').css('zIndex', '1051');
    $('.loader').css('zIndex', '1052');
  } else {
    // $('#modal-loading').hide(); // Show loading bar.
    $('#ajaxCall').hide();
    $('.blackbg').css('zIndex', '3');
    $('.loader').css('zIndex', '4');
  }
}

/**
 * Show modal by URL content.
 * @param {string} url URL to get.
 * @param {string} className Add class to modal-dialog class
 * @param {function} callBack Callback function
 */
function showModal(url, className = '', callBack = null) {
  // $('#myModal').find('.modal-dialog').addClass(className).load(url, function () {
  //   $('#myModal').modal('show');
  //   if (typeof callBack === 'function') callBack.call();
  // });
  console.log('showModal');
  httpGet(url, null, {
    error: () => {
      addAlert(`Cannot show modal: ${url}`);
    },
    success: (data) => {
      console.log('show it');
      $('#myModal').find('.modal-dialog').addClass(className).html(data)
      $('#myModal').modal('show');
      if (typeof callBack === 'function') callBack.call();
    }
  });
}

/**
 * Retrieve time format from MySQL function TIMEDIFF.
 * @param {string} str return from TIMEDIFF.
 */
function SQLTime(str) {
  let time = '';

  if (typeof str === 'string') {
    time = str.split('.')[0];
  }

  return time;
}

/**
 * Trim string as filter string.
 * @param {string} str String to filter.
 * @example
 * // Return '1,2,3,4'
 * trimFilter('1,2,3,4, ');
 */
function trimFilter(str) {
  str = str.trim(); // First trim.
  if (str.charAt(0) == '&') {
    str = str.substring(1).trim();
  }
  if (str.charAt(str.length - 1) == '&') {
    str = str = str.substring(0, str.length - 1).trim();
  }
  if (str.charAt(str.length - 1) == ',') {
    str = str.substring(0, str.length - 1).trim();
  }
  return str;
}

function typing(str, callback) {
  let lastKey = '';
  let x = 0;

  $(document).on('keyup', function (e) {
    if (lastKey.length == 0 && e.key == str[x]) {
      lastKey = str[x];
    } else if (lastKey == str[x - 1] && e.key == str[x]) {
      if ((x + 1) == str.length) {
        callback.call();
        lastKey = '';
        x = -1;
      } else {
        lastKey = str[x];
      }
    } else {
      lastKey = '';
      x = -1;
    }

    x = ((x + 1) % str.length);
  });

  return true;
}

function upperCase(str) {
  return (str ? str.toString().toUpperCase() : str);
}

/**
 * Make a string's first character uppercase.
 * @param {string} str String to uppercase the first character.
 * @return {string} Return uppercase first character.
 */
function ucfirst(str) {
  let fc = str.charAt(0).toUpperCase();
  let rem = str.substr(1);
  return fc + rem;
}

/**
 * Uppercase the first character of each word in a string
 * @param {string} str Input string.
 * @param {string} delimiter Delimiter for each words.
 * @return {string} Return uppercase words.
 */
function ucwords(str, delimiter = "_,\t\r\n") {
  let s = '';
  let words = str.split(new RegExp('[' + delimiter + ']'));
  for (let word of words) {
    s += ucfirst(word) + ' ';
  }

  return s.trim();
}

/**
 * Wrapper for localStorage object.
 */
var stor = {
  delete: function (key) {
    if (localStorage.getItem(key)) {
      localStorage.removeItem(key);
      return true;
    }
    return false;
  },

  exists: function (key) {
    if (localStorage.getItem(key)) {
      return true;
    }
    return false;
  },

  get: function (key) {
    return localStorage.getItem(key);
  },

  set: function (key, value) {
    localStorage.setItem(key, value);
  }
};

class Timer {
  constructor(seconds) {
    this.raw_seconds = seconds;
  }

  getHours() {
    this.hours = Math.floor(this.raw_seconds / 3600);
    if (this.hours < 10) this.hours = '0' + this.hours;
    return this.hours;
  }

  getMinutes() {
    this.minutes = Math.floor((this.raw_seconds % 3600) / 60);
    if (this.minutes < 10) this.minutes = '0' + this.minutes;
    return this.minutes;
  }

  getSeconds() {
    this.seconds = Math.floor((this.raw_seconds % 3600) % 60);
    if (this.seconds < 10) this.seconds = '0' + this.seconds;
    return this.seconds;
  }

  setMiliseconds(miliseconds) {
    this.raw_seconds = Math.floor(miliseconds / 1000);
  }

  setSeconds(seconds) {
    this.raw_seconds = seconds;
  }
}

document.addEventListener('copy', function (event) {
  let data = document.getSelection().toString();
  event.clipboardData.setData('text/plain', data.trim());
  console.log('Clipboard data trimmed.');
  event.preventDefault();
});

$(document).on('click', '.slrevert', function (e) {
  e.preventDefault();

  let saleId = $(this).data('sale-id');

  addConfirm({
    title: 'Revert Sale',
    message: `Are you sure to revert Sale ID ${saleId} back to Waiting Production?`,
    onok: () => {
      let data = {};

      data[security.csrf_token_name] = security.csrf_hash;
      data['sale'] = saleId;

      $.ajax({
        data: data,
        method: 'POST',
        success: function (data) {
          if (typeof data == 'object' && !data.error) {
            if (oTable) oTable.fnDraw(false);
            addAlert(data.msg, 'success');
          } else if (typeof data == 'object' && data.error) {
            addAlert(data.msg, 'danger');
          } else {
            addAlert('Unknown error', 'danger');
          }
        },
        url: site.base_url + 'sales/revertSaleStatus'
      });
    }
  });
});

$(document).ready(function () {
  document.title = site.page_title + ' - ' + site.name + ' ' + $.fn.PrintERP.version;

  if (typeof toastr !== 'undefined' && isObject(toastr)) {
    toastr.options = {
      progressBar: true,
      timeOut: 2000
    };
  }

  if (navigator.geolocation) {
    // navigator.geolocation.getCurrentPosition(function (pos) {
    //   data = {
    //     cmd: 'set',
    //     lat: pos.coords.latitude,
    //     lon: pos.coords.longitude
    //   };
    //   data[security.csrf_token_name] = security.csrf_hash;

    //   let xhr = new XMLHttpRequest();
    //   xhr.addEventListener('load', function (ev) {
    //     if (xhr.status == 200 && xhr.readyState == 4) {
    //       //console.log(xhr.response);
    //     }
    //   });
    //   xhr.open('POST', site.url + 'api/v1/geolocation');
    //   xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    //   xhr.send(serialize(data));
    // }, function (error) {
    //   if (error.PERMISSION_DENIED) {
    //     console.warn('Geolocation require permission.');
    //     // if (user_id == 1) alertify.alert('Geolocation Access', 'Dimohon untuk mengizinkan akses lokasi kepada PrintERP. Terima kasih.');
    //   }
    // });
  }

  // Check if webapp is installed.
  if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('display-mode is standalone');
  }
});