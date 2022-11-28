$(window).on('load', function () {
  $('#loading').fadeOut('slow');

});
function cssStyle() {
  if ($.cookie('sma_style') == 'light') {
    $('link[href="' + site.assets + 'styles/blue.css"]').attr('disabled', 'disabled');
    $('link[href="' + site.assets + 'styles/blue.css"]').remove();
    $('<link>')
      .appendTo('head')
      .attr({ type: 'text/css', rel: 'stylesheet' })
      .attr('href', site.assets + 'styles/light.css');
  } else if ($.cookie('sma_style') == 'blue') {
    $('link[href="' + site.assets + 'styles/light.css"]').attr('disabled', 'disabled');
    $('link[href="' + site.assets + 'styles/light.css"]').remove();
    $('<link>')
      .appendTo('head')
      .attr({ type: 'text/css', rel: 'stylesheet' })
      .attr('href', '' + site.assets + 'styles/blue.css');
  } else {
    $('link[href="' + site.assets + 'styles/light.css"]').attr('disabled', 'disabled');
    $('link[href="' + site.assets + 'styles/blue.css"]').attr('disabled', 'disabled');
    $('link[href="' + site.assets + 'styles/light.css"]').remove();
    $('link[href="' + site.assets + 'styles/blue.css"]').remove();
  }

  if ($('#sidebar-left').hasClass('minified')) {
    $.cookie('sma_theme_fixed', 'no', { path: '/' });
    $('#content, #sidebar-left, #header').removeAttr('style');
    $('#sidebar-left').removeClass('sidebar-fixed');
    $('#content').removeClass('content-with-fixed');
    $('#fixedText').text('Fixed');
    $('#main-menu-act')
      .addClass('full visible-md visible-lg')
      .show();
    $('#fixed').removeClass('fixed');
  } else {
    if (site.settings.rtl == 1) {
      $.cookie('sma_theme_fixed', 'no', { path: '/' });
    }
    if ($.cookie('sma_theme_fixed') == 'yes') {
      $('#content').addClass('content-with-fixed');
      $('#sidebar-left')
        .addClass('sidebar-fixed')
        .css('height', $(window).height() - 80);
      $('#header')
        .css('position', 'fixed')
        .css('top', '0')
        .css('width', '100%');
      $('#fixedText').text('Static');
      $('#main-menu-act')
        .removeAttr('class')
        .hide();
      $('#fixed').addClass('fixed');
      $('#sidebar-left').css('overflow', 'hidden');
      $('#sidebar-left').perfectScrollbar({ suppressScrollX: true });
    } else {
      $('#content, #sidebar-left, #header').removeAttr('style');
      $('#sidebar-left').removeClass('sidebar-fixed');
      $('#content').removeClass('content-with-fixed');
      $('#fixedText').text('Fixed');
      $('#main-menu-act')
        .addClass('full visible-md visible-lg')
        .show();
      $('#fixed').removeClass('fixed');
      $('#sidebar-left').perfectScrollbar('destroy');
    }
  }
  widthFunctions();
}

$('#csv_files').change(function (e) {
  v = $(this).val();
  if (v != '') {
    var validExts = new Array('.csv');
    var fileExt = v;
    fileExt = fileExt.substring(fileExt.lastIndexOf('.'));
    if (validExts.indexOf(fileExt) < 0) {
      e.preventDefault();
      bootbox.alert('Invalid file selected. Only .csv file is allowed. X');
      $(this).val('');
      $(this).fileinput('clear');
      $('form[data-toggle="validator"]').bootstrapValidator('updateStatus', 'csv_file', 'NOT_VALIDATED');
      return false;
    } else return true;
  }
});

$(document).ready(function () {

  if ($.cookie('sma_sidebar') == undefined) {
    //$.cookie('sma_sidebar', 'minified', { path: '/' }); // For permanent minified sidebar for first login.
  }
  $(document).on('keypress', '.editor:not(.editor[readonly], .editor[disabled])', function (e) { // NEW ADDED.
    if (e.keyCode == 13) e.preventDefault(); // Prevent submit on enter.
  });
  $(document).on('click', '.editor:not(.editor[readonly], .editor[disabled])', function () { // NEW ADDED.
    if (is_numeric($(this).val())) {
      $(this)[0].select();
    }
  });
  $(document).on('dblclick', '.editor:not(.editor[readonly], .editor[disabled])', function () { // NEW ADDED.
    let inp = prompt('Value:', $(this).val());
    if (inp !== null) {
      $(this).val(inp).trigger('change');
    }
  });
  $('#suggest_product').autocomplete({
    source: site.base_url + 'reports/suggestions',
    select: function (event, ui) {
      $('#report_product_id').val(ui.item.id);
    },
    minLength: 1,
    autoFocus: false,
    delay: 250,
    response: function (event, ui) {
      if (ui.content.length == 1 && ui.content[0].id != 0) {
        ui.item = ui.content[0];
        $(this).val(ui.item.label);
        $(this)
          .data('ui-autocomplete')
          ._trigger('select', 'autocompleteselect', ui);
        $(this).autocomplete('close');
        $(this).removeClass('ui-autocomplete-loading');
      }
    },
  });
  $(document).on('blur', '#suggest_product', function (e) {
    if (!$(this).val()) {
      $('#report_product_id').val('');
    }
  });
  $('#suggest_product2').autocomplete({
    source: site.base_url + 'reports/suggestions',
    select: function (event, ui) {
      $('#report_product_id2').val(ui.item.id);
    },
    minLength: 1,
    autoFocus: false,
    delay: 250,
    response: function (event, ui) {
      if (ui.content.length == 1 && ui.content[0].id != 0) {
        ui.item = ui.content[0];
        $(this).val(ui.item.label);
        $(this)
          .data('ui-autocomplete')
          ._trigger('select', 'autocompleteselect', ui);
        $(this).autocomplete('close');
        $(this).removeClass('ui-autocomplete-loading');
      }
    },
  });
  $(document).on('blur', '#suggest_product2', function (e) {
    if (!$(this).val()) {
      $('#report_product_id').val('');
    }
  });
  $('#random_num').click(function () {
    $(this)
      .parent('.input-group')
      .children('input')
      .val(generateCardNo(8));
  });
  $('#toogle-customer-read-attr').click(function () {
    var icus = $(this)
      .closest('.input-group')
      .find("select[name='customer']");
    var nst = icus.is('[readonly]') ? false : true;
    if (nst) {
      $(this).find('#addIcon').removeClass('fa-unlock').addClass('fa-lock');
    } else {
      $(this).find('#addIcon').removeClass('fa-lock').addClass('fa-unlock');
    }
    icus.prop('readonly', nst);
    return false;
  });
  $('.top-menu-scroll').perfectScrollbar();
  $('#fixed').click(function (e) {
    e.preventDefault();
    if ($('#sidebar-left').hasClass('minified')) {
      bootbox.alert('Unable to fix minified sidebar');
    } else {
      if ($(this).hasClass('fixed')) {
        $.cookie('sma_theme_fixed', 'no', { path: '/' });
      } else {
        $.cookie('sma_theme_fixed', 'yes', { path: '/' });
      }
      cssStyle();
    }
  });
});

function widthFunctions(e) {
  var l = $('#sidebar-left').outerHeight(true),
    c = $('#content').height(),
    co = $('#content').outerHeight(),
    h = $('header').height(),
    f = $('footer').height(),
    wh = $(window).height(),
    ww = $(window).width();
  if (ww < 992) {
    $('#main-menu-act')
      .removeClass('minified')
      .addClass('full')
      .find('i')
      .removeClass('fa-angle-double-right')
      .addClass('fa-angle-double-left');
    $('body').removeClass('sidebar-minified');
    $('#content').removeClass('sidebar-minified');
    $('#sidebar-left').removeClass('minified');
    if ($.cookie('sma_theme_fixed') == 'yes') {
      $.cookie('sma_theme_fixed', 'no', { path: '/' });
      $('#content, #sidebar-left, #header').removeAttr('style');
      $('#sidebar-left').css('overflow-y', 'visible');
      $('#fixedText').text('Fixed');
      $('#main-menu-act')
        .addClass('full visible-md visible-lg')
        .show();
      $('#fixed').removeClass('fixed');
      $('#sidebar-left').perfectScrollbar('destroy');
    }
  }
  if (ww < 998 && ww > 750) {
    $('#main-menu-act').hide();
    $('body').addClass('sidebar-minified');
    $('#content').addClass('sidebar-minified');
    $('#sidebar-left').addClass('minified');
    $('.dropmenu > .chevron')
      .removeClass('opened')
      .addClass('closed');
    $('.dropmenu')
      .parent()
      .find('ul')
      .hide();
    $('#sidebar-left > div > ul > li > a > .chevron')
      .removeClass('closed')
      .addClass('opened');
    $('#sidebar-left > div > ul > li > a').addClass('open');
    $('#fixed').hide();
  }
  if (ww > 1024 && $.cookie('sma_sidebar') != 'minified') {
    $('#main-menu-act')
      .removeClass('minified')
      .addClass('full')
      .find('i')
      .removeClass('fa-angle-double-right')
      .addClass('fa-angle-double-left');
    $('body').removeClass('sidebar-minified');
    $('#content').removeClass('sidebar-minified');
    $('#sidebar-left').removeClass('minified');
    $('#sidebar-left > div > ul > li > a > .chevron')
      .removeClass('opened')
      .addClass('closed');
    $('#sidebar-left > div > ul > li > a').removeClass('open');
    $('#fixed').show();
  }
  if ($.cookie('sma_theme_fixed') == 'yes') {
    $('#content').addClass('content-with-fixed');
    $('#sidebar-left')
      .addClass('sidebar-fixed')
      .css('height', $(window).height() - 80);
  }
  if (ww > 767) {
    wh - 80 > l && $('#sidebar-left').css('min-height', wh - h - f - 30);
    wh - 80 > c && $('#content').css('min-height', wh - h - f - 30);
  } else {
    $('#sidebar-left').css('min-height', '0px');
    $('.content-con').css('max-width', ww);
  }
  //$(window).scrollTop($(window).scrollTop() + 1);
}

jQuery(document).ready(function (e) {
  window.location.hash ? e('#myTab a[href="' + window.location.hash + '"]').tab('show') : e('#myTab a:first').tab('show');
  e('#myTab2 a:first, #dbTab a:first').tab('show');
  e('#myTab a, #myTab2 a, #dbTab a').click(function (t) {
    t.preventDefault();
    e(this).tab('show');
  });
  e('[rel="popover"],[data-rel="popover"],[data-toggle="popover"]').popover({
    trigger: 'hover'
  });
  e('#toggle-fullscreen')
    .button()
    .click(function () {
      var t = e(this),
        n = document.documentElement;
      if (!t.hasClass('active')) {
        e('#thumbnails').addClass('modal-fullscreen');
        n.webkitRequestFullScreen
          ? n.webkitRequestFullScreen(window.Element.ALLOW_KEYBOARD_INPUT)
          : n.mozRequestFullScreen && n.mozRequestFullScreen();
      } else {
        e('#thumbnails').removeClass('modal-fullscreen');
        (document.webkitCancelFullScreen || document.mozCancelFullScreen || e.noop).apply(document);
      }
    });
  e('.btn-close').click(function (t) {
    t.preventDefault();
    e(this)
      .parent()
      .parent()
      .parent()
      .fadeOut();
  });
  e('.btn-minimize').click(function (t) {
    t.preventDefault();
    var n = e(this)
      .parent()
      .parent()
      .next('.box-content');
    n.is(':visible')
      ? e('i', e(this))
        .removeClass('fa-chevron-up')
        .addClass('fa-chevron-down')
      : e('i', e(this))
        .removeClass('fa-chevron-down')
        .addClass('fa-chevron-up');
    n.slideToggle('slow', function () {
      widthFunctions();
    });
  });
});

jQuery(document).ready(function (e) {
  e('#main-menu-act').click(function () {
    if (e(this).hasClass('full')) {
      $.cookie('sma_sidebar', 'minified', { path: '/' });
      e(this)
        .removeClass('full')
        .addClass('minified')
        .find('i')
        .removeClass('fa-angle-double-left')
        .addClass('fa-angle-double-right');
      e('body').addClass('sidebar-minified');
      e('#content').addClass('sidebar-minified');
      e('#sidebar-left').addClass('minified');
      e('.dropmenu > .chevron')
        .removeClass('opened')
        .addClass('closed');
      e('.dropmenu')
        .parent()
        .find('ul')
        .hide();
      e('#sidebar-left > div > ul > li > a > .chevron')
        .removeClass('closed')
        .addClass('opened');
      e('#sidebar-left > div > ul > li > a').addClass('open');
      $('#fixed').hide();
    } else {
      $.cookie('sma_sidebar', 'full', { path: '/' });
      e(this)
        .removeClass('minified')
        .addClass('full')
        .find('i')
        .removeClass('fa-angle-double-right')
        .addClass('fa-angle-double-left');
      e('body').removeClass('sidebar-minified');
      e('#content').removeClass('sidebar-minified');
      e('#sidebar-left').removeClass('minified');
      e('#sidebar-left > div > ul > li > a > .chevron')
        .removeClass('opened')
        .addClass('closed');
      e('#sidebar-left > div > ul > li > a').removeClass('open');
      $('#fixed').show();
    }
    return false;
  });
  e('.dropmenu').click(function (t) {
    t.preventDefault();
    if (e('#sidebar-left').hasClass('minified')) {
      if (!e(this).hasClass('open')) {
        e(this)
          .parent()
          .find('ul')
          .slideToggle();
        e(this)
          .find('.chevron')
          .hasClass('closed')
          ? e(this)
            .find('.chevron')
            .removeClass('closed')
            .addClass('opened')
          : e(this)
            .find('.chevron')
            .removeClass('opened')
            .addClass('closed');
      }
    } else { // ACCORDION STATE SUCCEEDED.
      if ($(this).find('.chevron').hasClass('closed')) {
        $(this).closest('.nav')
          .find('.opened')
          .removeClass('opened')
          .addClass('closed')
          .closest('li')
          .find('ul')
          .slideUp();
        $(this).parent().find('ul').slideDown();
      } else if ($(this).find('.chevron').hasClass('opened')) {
        $(this).parent().find('ul').slideUp();
      }

      e(this)
        .find('.chevron')
        .hasClass('closed')
        ? e(this)
          .find('.chevron')
          .removeClass('closed')
          .addClass('opened')
        : e(this)
          .find('.chevron')
          .removeClass('opened')
          .addClass('closed');
    }
  });
  if (e('#sidebar-left').hasClass('minified')) {
    e('#sidebar-left > div > ul > li > a > .chevron')
      .removeClass('closed')
      .addClass('opened');
    e('#sidebar-left > div > ul > li > a').addClass('open');
    e('body').addClass('sidebar-minified');
  }
});

$(document).ready(function () {
  cssStyle();
  $('select.select2')
    .not('.skip')
    .select2({ theme: 'classic' });
  
  $('select.select2-tags')
    .not('.skip')
    .select2({ tags: true, theme: 'classic' });

  $('select[name$="_length"]').not('.skip').select2({ theme: 'classic' }); // Datatables.

  $('#customer, #rcustomer, .rcustomer, select.ssr-customer').select2({
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

  $('.input-tip').tooltip({
    placement: 'top',
    html: true,
    trigger: 'hover focus',
    container: 'body',
    title: function () {
      return $(this).attr('data-tip');
    },
  });
  $('.input-pop').popover({
    placement: 'top',
    html: true,
    trigger: 'hover',
    container: 'body',
    content: function () {
      return $(this).attr('data-tip');
    },
    title: function () {
      return '<b>' + $('label[for="' + $(this).attr('id') + '"]').text() + '</b>';
    },
  });
});

$(document).on('click', '*[data-toggle="lightbox"]', function (event) {
  event.preventDefault();
  $(this).ekkoLightbox();
});
$(document).on('click', '*[data-toggle="popover"]', function (event) {
  event.preventDefault();
  $(this).popover();
});

// FIXME: Cari solusi untuk tampilan loader.
$(document)
  .ajaxStart(function () {
    showLoader(true);
  })
  .ajaxStop(function () {
    showLoader(false);
  });

$(document).ready(function () {
  $('input[type="checkbox"],[type="radio"]')
    .not('.skip')
    .iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%',
    });
  $('textarea')
    .not('.skip')
    .redactor({
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
        /*'image', 'video',*/ 'link',
        '|',
        'html',
      ],
      formattingTags: ['p', 'pre', 'h3', 'h4'],
      minHeight: 50,
      changeCallback: function (e) {
        var editor = this.$editor.next('textarea');
        if ($(editor).attr('required')) {
          $('form[data-toggle="validator"]').bootstrapValidator('validateField', $(editor).attr('name'));
        }
      },
    });
  $(document).on('click', '.file-caption', function () {
    $(this)
      .next('.input-group-btn')
      .children('.btn-file')
      .children('input.file')
      .trigger('click');
  });
});

function suppliers(ele) {
  $(ele).select2({
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
}

$(function () {
  $('.datetime').datetimepicker({
    format: site.dateFormats.js_ldate,
    fontAwesome: true,
    language: 'sma',
    weekStart: 1,
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    minView: 2
  });
  $('.datetimenow').datetimepicker({
    format: site.dateFormats.js_ldate,
    fontAwesome: true,
    language: 'sma',
    weekStart: 1,
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    minView: 2
  }).datetimepicker('update', new Date());
  $('.date').datetimepicker({
    format: site.dateFormats.js_sdate,
    fontAwesome: true,
    language: 'sma',
    todayBtn: 1,
    autoclose: 1,
    minView: 2
  });
  $('.datenow').datetimepicker({
    format: site.dateFormats.js_sdate,
    fontAwesome: true,
    language: 'sma',
    todayBtn: 1,
    autoclose: 1,
    minView: 2
  }).datetimepicker('update', new Date());
  $(document).on('focus', '.date', function (t) {
    $(this).datetimepicker({ format: site.dateFormats.js_sdate, fontAwesome: true, todayBtn: 1, autoclose: 1, minView: 2 });
  });
  $(document).on('focus', '.datetime', function () {
    $(this).datetimepicker({
      format: site.dateFormats.js_ldate,
      fontAwesome: true,
      weekStart: 1,
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      startView: 2,
      forceParse: 0,
    });
  });
  var startDate = moment()
    .subtract(89, 'days')
    .format('YYYY-MM-DD');
  var endDate = moment().format('YYYY-MM-DD');
  $('#log-date').datetimepicker({
    startDate: startDate,
    endDate: endDate,
    format: site.dateFormats.js_sdate,
    fontAwesome: true,
    language: 'sma',
    todayBtn: 1,
    autoclose: 1,
    minView: 2,
  });
  $(document).on('focus', '#log-date', function (t) {
    $(this).datetimepicker({
      startDate: startDate,
      endDate: endDate,
      format: site.dateFormats.js_sdate,
      fontAwesome: true,
      todayBtn: 1,
      autoclose: 1,
      minView: 2,
    });
  });
  $('#log-date').on('changeDate', function (ev) {
    var date = moment(ev.date.valueOf()).format('YYYY-MM-DD');
    refreshPage(date);
  });
});

$(document).ready(function () {
  $('#dbTab a').on('shown.bs.tab', function (e) {
    var newt = $(e.target).attr('href');
    var oldt = $(e.relatedTarget).attr('href');
    $(oldt).hide();
    //$(newt).hide().fadeIn('slow');
    $(newt)
      .hide()
      .slideDown('slow');
  });
  $('.dropdown').on('show.bs.dropdown', function (e) {
    $(this)
      .find('.dropdown-menu')
      .first()
      .stop(true, true)
      .slideDown('fast');
  });
  $('.dropdown').on('hide.bs.dropdown', function (e) {
    $(this)
      .find('.dropdown-menu')
      .first()
      .stop(true, true)
      .slideUp('fast');
  });
  $('.hideComment').click(function () {
    // $.ajax({ url: site.base_url + 'welcome/hideNotification/' + $(this).attr('id') });
    httpGet(site.base_url + 'welcome/hideNotification/' + $(this).attr('id'));
  });
  $('.tip').tooltip();
  $('body').on('click', '#activate', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form').submit();
  });
  $('body').on('click', '#deactivate', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form').submit();
  });
  /*$('body').on('click', '#delete', function(e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form').submit();
  });*/
  /*$('body').on('click', '#sync_quantity', function(e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });*/
  $('body').on('click', '#approve_send', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });
  /*$('body').on('click', '#excel', function(e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });*/
  $('body').on('click', '#pdf', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });
  $('body').on('click', '#labelProducts', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });
  $('body').on('click', '#barcodeProducts', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });
  $('body').on('click', '#combine', function (e) {
    e.preventDefault();
    $('#form_action').val($(this).attr('data-action'));
    $('#action-form-submit').trigger('click');
  });
});

$(document).ready(function () {
  $('#product-search').click(function () {
    $('#product-search-form').submit();
  });
  fields = $('.form-control');
  $.each(fields, function () {
    var id = $(this).attr('id');
    var iname = $(this).attr('name');
    var iid = '#' + id;
    if (!!$(this).attr('data-bv-notempty') || !!$(this).attr('required')) {
      if (typeof (iname) != 'undefined') {
        $("label[for='" + id + "']").append(' *');
        $(document).on('change', iid, function () {
          $('form[data-toggle="validator"]').bootstrapValidator('validateField', iname);
        });
      }
    }
  });
  $('body').on('click', 'label', function (e) {
    var field_id = $(this).attr('for');
    if (field_id) {
      if ($('#' + field_id).hasClass('select')) {
        $('#' + field_id).select2('open');
        return false;
      }
    }
  });
  $('body').on('focus', 'select', function (e) {
    var field_id = $(this).attr('id');
    if (field_id) {
      if ($('#' + field_id).hasClass('select')) {
        $('#' + field_id).select2('open');
        return false;
      }
    }
  });
  // REMOTE MODAL
  $('#myModal').on('show.bs.modal', async function (e) {
    let dialogClass = '', remote = null;

    if (e.relatedTarget?.href && e.relatedTarget.href != '#' && e.relatedTarget.href.substr(-1, 1) != '#') {
      remote = e.relatedTarget.href;
    } else if ($(e.relatedTarget).data('remote')) {
      remote = $(e.relatedTarget).data('remote');
    }

    if ($(e.relatedTarget).data('modal-class')) {
      dialogClass = $(e.relatedTarget).data('modal-class');
      $(this).find('.modal-dialog').addClass(dialogClass);
    }

    if (remote) {
      let ret = await new Promise((resolve, reject) => {
        // $.ajax({
        //   async: true,
        //   error: () => {
        //     reject('Ajax Error');
        //   },
        //   method: 'GET',
        //   success: (data) => {
        //     resolve(data);
        //   },
        //   url: remote
        // });

        httpGet(remote, null, {
          error: () => {
            reject('Ajax Error');
          },
          success: (data) => {
            resolve(data);
          }
        });
      });

      $(this).find('.modal-dialog').html(ret);
    }

    if ($(this).find('.modal-header').length) {
      $(this).draggable({handle: '.modal-header'});
    }
  });
  $('#myModal2').on('show.bs.modal', async function (e) {
    let dialogClass = '', remote = null;

    if (e.relatedTarget?.href && e.relatedTarget.href != '#' && e.relatedTarget.href.substr(-1, 1) != '#') {
      remote = e.relatedTarget.href;
    } else if ($(e.relatedTarget).data('remote')) {
      remote = $(e.relatedTarget).data('remote');
    }

    if ($(e.relatedTarget).data('modal-class')) {
      dialogClass = $(e.relatedTarget).data('modal-class');
      $(this).find('.modal-dialog').addClass(dialogClass);
    }

    if (remote) {
      let ret = await new Promise((resolve, reject) => {
        // $.ajax({
        //   async: true,
        //   error: () => {
        //     reject('Ajax Error');
        //   },
        //   method: 'GET',
        //   success: (data) => {
        //     resolve(data);
        //   },
        //   url: remote
        // });

        httpGet(remote, null, {
          error: () => {
            reject('Ajax Error');
          },
          success: (data) => {
            resolve(data);
          }
        });
      });
      $(this).find('.modal-dialog').html(ret);
    }

    if ($(this).find('.modal-header').length) {
      $(this).draggable({handle: '.modal-header'});
    }
  });
  $('#myModal3').on('show.bs.modal', async function (e) {
    let dialogClass = '', remote = null;

    // Is related target has href property?
    if (e.relatedTarget?.href && e.relatedTarget.href != '#' && e.relatedTarget.href.substr(-1, 1) != '#') {
      remote = e.relatedTarget.href;
    } else if ($(e.relatedTarget).data('remote')) {
      remote = $(e.relatedTarget).data('remote');
    }

    if ($(e.relatedTarget).data('modal-class')) {
      dialogClass = $(e.relatedTarget).data('modal-class');
      $(this).find('.modal-dialog').addClass(dialogClass);
    }

    if (remote) {
      let ret = await new Promise((resolve, reject) => {
        // $.ajax({
        //   async: true,
        //   error: () => {
        //     reject('Ajax Error');
        //   },
        //   method: 'GET',
        //   success: (data) => {
        //     resolve(data);
        //   },
        //   url: remote
        // });

        httpGet(remote, null, {
          error: () => {
            reject('Ajax Error');
          },
          success: (data) => {
            resolve(data);
          }
        });
      });
      $(this).find('.modal-dialog').html(ret);
    }
  });
  // Modal Hide
  $('#myModal').on('hidden.bs.modal', function () {
    $(this).empty().append('<div class="modal-dialog"></div>');
  });
  $('#myModal2').on('hidden.bs.modal', function () {
    $(this).empty().append('<div class="modal-dialog"></div>');
    $('body').addClass('modal-open');
  });
  $('#myModal3').on('hidden.bs.modal', function () {
    $(this).empty().append('<div class="modal-dialog"></div>');
    $('body').addClass('modal-open');
  });
  $('#myModal').on('shown.bs.modal', function () {
    // showLoader(false);
  });
  $('#myModal2').on('shown.bs.modal', function () {
    // showLoader(false);
  });
  $('#myModal3').on('shown.bs.modal', function () {
    // showLoader(false);
  });
  $(document).on('click', '.po', function (e) {
    e.preventDefault();
    let place = site.settings.rtl == 1 ? 'right' : 'left';
    $('.po')
      .popover({ html: true, placement: place, trigger: 'manual', sanitize: false })
      .popover('show')
      .not(this)
      .popover('hide');
    return false;
  });
  $(document).on('click', '.po-close', function () {
    $('.po').popover('hide');
    return false;
  });
  $(document).on('click', '.po-delete', function (e) {
    var row = $(this).closest('tr');
    e.preventDefault();
    $('.po').popover('hide');
    var link = $(this).attr('href');
    // $.ajax({
    //   type: 'get',
    //   url: link,
    //   dataType: 'json',
    //   success: function(data) {
    //     if (typeof data == 'object' && data.error == 1) {
    //       addAlert(data.msg, 'danger');
    //     } else {
    //       addAlert(data.msg, 'success');
    //       if (oTable != '') {
    //         oTable.fnDraw();
    //       }
    //     }
    //   },
    //   error: function(data) {
    //     addAlert(`Ajax call failed on '${link}'`, 'danger');
    //   },
    // });

    httpGet(link, null, {
      error: () => {
        addAlert(`Ajax call failed on '${link}'`, 'danger');
      },
      success: (data) => {
        if (typeof data == 'object' && data.error == 1) {
          addAlert(data.msg, 'danger');
        } else {
          addAlert(data.msg, 'success');
          if (oTable) oTable.fnDraw();
        }
      }
    });
    return false;
  });
  $(document).on('click', '.po-delete1', function (e) {
    e.preventDefault();
    $('.po').popover('hide');
    var link = $(this).attr('href');
    var s = $(this).attr('id');
    var sp = s.split('__');
    // $.ajax({
    //   type: 'get',
    //   url: link,
    //   dataType: 'json',
    //   success: function(data) {
    //     if (data.error == 1) {
    //       addAlert(data.msg, 'danger');
    //     } else {
    //       addAlert(data.msg, 'success');
    //       if (oTable != '') {
    //         oTable.fnDraw();
    //       }
    //     }
    //   },
    //   error: function(data) {
    //     addAlert('Ajax call failed', 'danger');
    //   },
    // });

    httpGet(link, null, {
      error: () => {
        addAlert(`Ajax call failed on '${link}'`, 'danger');
      },
      success: (data) => {
        if (typeof data == 'object' && data.error == 1) {
          addAlert(data.msg, 'danger');
        } else {
          addAlert(data.msg, 'success');
          if (oTable) oTable.fnDraw();
        }
      }
    });
    return false;
  });
  $('body').on('click', '.bpo', function (e) {
    e.preventDefault();
    $(this)
      .popover({ html: true, trigger: 'manual', sanitize: false })
      .popover('toggle');
    return false;
  });
  $('body').on('click', '.bpo-close', function (e) {
    $('.bpo').popover('hide');
    return false;
  });
  $('body').on('click', '#delete', function () { // Patched.
    $('.bpo').popover('hide');
  });
  $('#genNo').click(function () {
    var no = generateCardNo();
    $(this)
      .parent()
      .parent('.input-group')
      .children('input')
      .val(no);
    return false;
  });
  $('#inlineCalc').calculator({ layout: ['_%+-CABS', '_7_8_9_/', '_4_5_6_*', '_1_2_3_-', '_0_._=_+'], showFormula: true });
  $('.calc').click(function (e) {
    e.stopPropagation();
  });
  $(document).on('click', '.sname', function (e) {
    var row = $(this).closest('tr');
    var itemid = row.find('.rid').val();
    showModal(site.base_url + 'products/modal_view/' + itemid, 'modal-lg no-header-modal');
  });
});

/**
 * Show alert notification.
 * @param {string} message Message of alert.
 * @param {string} type Type of alert (danger, info, success, warning).
 */
function addAlert(message, type) {
  $('.alerts-con')
    .empty()
    .append(
      '<div class="alert alert-' +
      type +
      '">' +
      '<button type="button" class="close" data-dismiss="alert"><i class="fad fa-times"></i></button>' +
      message +
      '</div>'
    );
}

function addModalAlert(message, type) {
  $('.modal-alerts-con')
    .empty()
    .append(
      '<div class="alert alert-' +
      type +
      '">' +
      '<button type="button" class="close" data-dismiss="alert"><i class="fad fa-times"></i></button>' +
      message +
      '</div>'
    );
}

$(document).ready(function () {
  if ($.cookie('sma_sidebar') == 'minified') {
    $('#main-menu-act')
      .removeClass('full')
      .addClass('minified')
      .find('i')
      .removeClass('fa-angle-double-left')
      .addClass('fa-angle-double-right');
    $('body').addClass('sidebar-minified');
    $('#content').addClass('sidebar-minified');
    $('#sidebar-left').addClass('minified');
    $('.dropmenu > .chevron')
      .removeClass('opened')
      .addClass('closed');
    $('.dropmenu')
      .parent()
      .find('ul')
      .hide();
    $('#sidebar-left > div > ul > li > a > .chevron')
      .removeClass('closed')
      .addClass('opened');
    $('#sidebar-left > div > ul > li > a').addClass('open');
    $('#fixed').hide();
  } else {
    $('#main-menu-act')
      .removeClass('minified')
      .addClass('full')
      .find('i')
      .removeClass('fa-angle-double-right')
      .addClass('fa-angle-double-left');
    $('body').removeClass('sidebar-minified');
    $('#content').removeClass('sidebar-minified');
    $('#sidebar-left').removeClass('minified');
    $('#sidebar-left > div > ul > li > a > .chevron')
      .removeClass('opened')
      .addClass('closed');
    $('#sidebar-left > div > ul > li > a').removeClass('open');
    $('#fixed').show();
  }
});

$(document).ready(function () {
  $('#daterange').daterangepicker(
    {
      timePicker: true,
      format: site.dateFormats.js_sdate.toUpperCase() + ' HH:mm',
      ranges: {
        Today: [
          moment()
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment(),
        ],
        Yesterday: [
          moment()
            .subtract('days', 1)
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment()
            .subtract('days', 1)
            .hours(23)
            .minutes(59)
            .seconds(59),
        ],
        'Last 7 Days': [
          moment()
            .subtract('days', 6)
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment()
            .hours(23)
            .minutes(59)
            .seconds(59),
        ],
        'Last 30 Days': [
          moment()
            .subtract('days', 29)
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment()
            .hours(23)
            .minutes(59)
            .seconds(59),
        ],
        'This Month': [
          moment()
            .startOf('month')
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment()
            .endOf('month')
            .hours(23)
            .minutes(59)
            .seconds(59),
        ],
        'Last Month': [
          moment()
            .subtract('month', 1)
            .startOf('month')
            .hours(0)
            .minutes(0)
            .seconds(0),
          moment()
            .subtract('month', 1)
            .endOf('month')
            .hours(23)
            .minutes(59)
            .seconds(59),
        ],
      },
    },
    function (start, end) {
      refreshPage(start.format('YYYY-MM-DD HH:mm'), end.format('YYYY-MM-DD HH:mm'));
    }
  );
});

function refreshPage(start, end) {
  if (end) {
    window.location.replace(CURI + '/' + encodeURIComponent(start) + '/' + encodeURIComponent(end));
  } else {
    window.location.replace(CURI + '/' + encodeURIComponent(start));
  }
}

function retina() {
  retinaMode = window.devicePixelRatio > 1;
  return retinaMode;
}

$(document).ready(function () {
  $('#cssLight').click(function (e) {
    e.preventDefault();
    $.cookie('sma_style', 'light', { path: '/' });
    cssStyle();
    return true;
  });
  $('#cssBlue').click(function (e) {
    e.preventDefault();
    $.cookie('sma_style', 'blue', { path: '/' });
    cssStyle();
    return true;
  });
  $('#cssBlack').click(function (e) {
    e.preventDefault();
    $.cookie('sma_style', 'black', { path: '/' });
    cssStyle();
    return true;
  });
  $('#toTop').click(function (e) {
    e.preventDefault();
    $('html, body').animate({ scrollTop: 0 }, 100);
  });
  $(document).on('click', '.delimg', function (e) {
    e.preventDefault();
    var ele = $(this),
      id = $(this).attr('data-item-id');
    bootbox.confirm(lang.r_u_sure, function (result) {
      if (result == true) {
        $.get(site.base_url + 'products/delete_image/' + id, function (data) {
          if (data.error === 0) {
            addAlert(data.msg, 'success');
            ele.parent('.gallery-image').remove();
          }
        });
      }
    });
    return false;
  });
});
$(document).ready(function () {
  $(document).on('click', '.approval_status', function (e) {
    e.preventDefault;
    var row = $(this).closest('tr');
    var id = row.attr('id');
    if (row.hasClass('expense_link')) {
      showModal(site.base_url + 'finances/expenses/approval/' + id);
    }
    return false;
  });
});
$(document).ready(function () {
  $(document).on('click', '.payment_status', function (e) {
    e.preventDefault;

    var row = $(this).parents('tr'); // .closest('tr');
    var id = row.attr('id');

    if (row.hasClass('expense_link')) {
      showModal(site.base_url + 'finances/expenses/payment/' + id);
    } else if (row.hasClass('purchase_link')) {
      showModal(site.base_url + 'procurements/purchases/payments/' + id, 'modal-lg', function () {
        if (oTable) oTable.fnDraw();
      });
    } else if (row.hasClass('transfer_link')) {
      showModal(site.base_url + 'procurements/transfers/add_payment/' + id);
    } else if (row.hasClass('invoice_link')) { // Added
      showModal(site.base_url + 'sales/add_payment/' + id);
    } else if (row.hasClass('mutation_link')) { // Added
      showModal(site.base_url + 'finances/mutations/status/' + id);
    }
    return false;
  });
});
$(document).ready(function () {
  $(document).on('click', '.status', function (e) {
    e.preventDefault;

    let row = $(this).closest('tr');
    let id = row.attr('id');

    if (row.hasClass('internal_use_link')) {
      document.location.href = site.base_url + 'procurements/internal_uses/status/' + id;
    } else if (row.hasClass('invoice_link')) {
      $('#myModal').find('.modal-dialog').load(site.base_url + 'sales/update_sales_status/' + id, function () {
        $('#myModal').modal('show');
      });
    } else if (row.hasClass('purchase_link')) {
      document.location.href = site.base_url + 'procurements/purchases/status/' + id;
    } else if (row.hasClass('transfer_link')) {
      document.location.href = site.base_url + 'procurements/transfers/status/' + id;
    }
    return false;
  });
});
$(document).ready(function () {
  $(document).on('click', '.sales_item_status', function (e) {
    e.preventDefault;
    var row = $(this).closest('tr');
    var id = row.attr('id');
    if (row.hasClass('sales_item_link')) {

    }
    return false;
  });
});
/*
 $(window).scroll(function() {
  if ($(this).scrollTop()) {
    $('#toTop').fadeIn();
  } else {
    $('#toTop').fadeOut();
  }
 });
*/
$(document).on('ifChecked', '.checkth, .checkft', function (event) {
  $('.checkth, .checkft').iCheck('check');
  $('.multi-select').each(function () {
    $(this).iCheck('check');
  });
});
$(document).on('ifUnchecked', '.checkth, .checkft', function (event) {
  $('.checkth, .checkft').iCheck('uncheck');
  $('.multi-select').each(function () {
    $(this).iCheck('uncheck');
  });
});
$(document).on('ifUnchecked', '.multi-select', function (event) {
  $('.checkth, .checkft').attr('checked', false);
  $('.checkth, .checkft').iCheck('update');
});

function check_add_item_val() {
  $('#add_item').bind('keypress', function (e) {
    if (e.keyCode == 13 || e.keyCode == 9) {
      e.preventDefault();
      $(this).autocomplete('search');
    }
  });
}
function fld(oObj) {
  return oObj;
}

function fsd(oObj) {
  return oObj;
}

function generateCardNo(x) {
  if (!x) {
    x = 16;
  }
  chars = '1234567890';
  no = '';
  for (var i = 0; i < x; i++) {
    var rnum = Math.floor(Math.random() * chars.length);
    no += chars.substring(rnum, rnum + 1);
  }
  return no;
}
function roundNumber(num, nearest) {
  if (!nearest) {
    nearest = 0.05;
  }
  return Math.round((num / nearest) * nearest);
}
function getNumber(x) {
  return accounting.unformat(x);
}
function sales_item_status_properties(x) {
  if (typeof x === 'string') {
    try {
      let ps = JSON.parse(x);
      return sales_item_status(ps.status);
    } catch (e) {
      console.error(e);
    }
  }
}
function qtyFormat(x) {
  return formatQuantityCenter(x);
}
/* BELOW USED */
function formatQuantityFix(x, fix = 2) {
  x = parseFloat(x);
  return (x != null ? x.toFixed(fix) : 0);
}
function formatQuantityCenter(x) {
  x = parseFloat(x);
  return (x != null ? `<div class="text-center">${formatQuantity(x)}</div>` : '<div class="text-center">0</div>');
}
function formatQuantityRight(x) {
  return (x != null ? `<div class="text-right">${formatQuantity(x)}</div>` : '<div class="text-right">0</div>');
}
function formatQuantityFixCenter(x, fix = 2) {
  return (x != null ? `<div class="text-center">${formatQuantityFix(x, fix)}</div>` : '<div class="text-center">0.00</div>');
}
function formatQuantityFixRight(x, fix = 2) {
  return (x != null ? `<div class="text-right">${formatQuantityFix(x, fix)}</div>` : '<div class="text-right">0.00</div>');
}
/* BELOW NOT USED */
function formatQuantity2(x) {
  return x != null ? formatQuantityNumber(x, site.settings.qty_decimals) : '';
}
function formatQuantity3(x) {
  return (x != null ? `<div class="text-right">${formatQuantity2(x)}</div>` : '<div class="text-right">0</div>');
}
function formatQuantity4(x) {
  return (x != null ? `<div class="text-center">${formatQuantity2(x)}</div>` : '<div class="text-center">0</div>');
}
function formatQuantityNumber(x, d) {
  if (!d) {
    d = site.settings.qty_decimals;
  }
  return parseFloat(accounting.formatNumber(x, d, '', '.'));
}
function formatQty(x) {
  return x != null ? formatNumber(x, site.settings.qty_decimals) : '';
}
function formatMoney(x, symbol) {
  if (!symbol) {
    symbol = '';
  }
  if (site.settings.sac == 1) {
    return (
      (site.settings.display_symbol == 1 ? site.settings.symbol : '') +
      '' +
      formatSA(parseFloat(x).toFixed(0)) +
      (site.settings.display_symbol == 2 ? site.settings.symbol : '')
    );
  }
  var fmoney = accounting.formatMoney(
    x,
    symbol,
    0,
    site.settings.thousands_sep == 0 ? ' ' : site.settings.thousands_sep,
    site.settings.decimals_sep,
    '%s%v'
  );
  return (
    (site.settings.display_symbol == 1 ? site.settings.symbol : '') +
    fmoney +
    (site.settings.display_symbol == 2 ? site.settings.symbol : '')
  );
}
function is_valid_discount(mixed_var) {
  return is_numeric(mixed_var) || /([0-9]%)/i.test(mixed_var) ? true : false;
}
function is_numeric(mixed_var) {
  var whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
  return (
    (typeof mixed_var === 'number' || (typeof mixed_var === 'string' && whitespace.indexOf(mixed_var.slice(-1)) === -1)) &&
    mixed_var !== '' &&
    !isNaN(mixed_var)
  );
}
function is_float(mixed_var) {
  return (!Number.isSafeInteger(rd_float(mixed_var)));
}
function decimalFormat(x) {
  return '<div class="text-center">' + formatNumber(x != null ? x : 0) + '</div>';
}
function currencyFormat(x) {
  return '<div class="text-right">' + formatMoney(x != null ? x : 0) + '</div>';
}
function formatDecimal(x, d) {
  if (!d) {
    d = site.settings.decimals;
  }
  return parseFloat(accounting.formatNumber(x, d, '', '.'));
}
function formatDecimals(x, d) {
  if (!d) {
    d = site.settings.decimals;
  }
  return parseFloat(accounting.formatNumber(x, d, '', '.')).toFixed(d);
}
/**
 * Convert string into decimal.
 * @param {string} s String to convert to decimal.
 */
function rd_decimal(s) {
  return parseInt(s.replace(/[^0-9]/g, ''));
}
/**
 * Convert string into float.
 * @param {string} s String to convert to float.
 */
function rd_float(s) {
  return parseFloat(s.replace(/[^0-9\.\-]/g, ''));
}
function rd_quantity(qty, align = 'right') {
  return `<div class="text-${align}">${qty}</div>`;
}
function pqFormat(x) {
  if (x != null) {
    var d = '',
      pqc = x.split('___');
    for (index = 0; index < pqc.length; ++index) {
      var pq = pqc[index];
      var v = pq.split('__');
      d += v[0] + ' (' + formatQuantity2(v[1]) + ')<br>';
    }
    return d;
  } else {
    return '';
  }
}
function checkbox(x) {
  return '<div class="text-center"><input class="checkbox multi-select" type="checkbox" name="val[]" value="' + x + '" /></div>';
}
function decode_html(value) {
  return $('<div/>')
    .html(value)
    .text();
}
function img_hl(x) {
  var image_link = x == null || x == '' ? 'no_image.png' : x;
  return (
    '<div class="text-center"><a href="' +
    site.url +
    'assets/uploads/' +
    image_link +
    '" data-toggle="lightbox"><img src="' +
    site.url +
    'assets/uploads/' +
    image_link +
    '" alt="" style="width:30px; height:30px;" /></a></div>'
  );
}
function attachment(x) {
  return x == null
    ? ''
    : '<div class="text-center"><a href="' +
    site.base_url +
    'welcome/view/' +
    x +
    '" class="tip" title="' +
    lang.download +
    '" data-toggle="modal" data-target="#myModal"><i class="fa fa-file"></i></a></div>';
}
function attachment2(x) {
  return x == null
    ? ''
    : '<div class="text-center"><a href="' +
    site.base_url +
    'welcome/download/' +
    x +
    '" class="tip" title="' +
    lang.download +
    '"><i class="fa fa-file"></i></a></div>';
}

function bank_status(x) {
  var y = x.split('__');
  if (y[0] == 1) {
    icon = 'check';
    label = 'success';
    method = 'deactivate';
    message = lang['active'];
    modal = 'data-toggle="modal" data-target="#myModal"';
  } else {
    icon = 'times';
    label = 'danger';
    method = 'activate';
    message = lang['inactive'];
    modal = 'data-toggle="modal" data-target="#myModal"';
  }
  status = `<div class="text-center"><a href="${site.base_url}finances/banks/${method}/${y[1]}" class="label label-${label}" ${modal}>
    <i class="fa fa-${icon}"></i> ${message}</a></div>`;

  return status;
}

function notification_status(x) {
  var y = x.split('__');
  if (y[0] == 1) {
    icon = 'check';
    label = 'success';
    method = 'deactivate';
    message = lang['active'];
    modal = 'data-toggle="modal" data-target="#myModal"';
  } else {
    icon = 'times';
    label = 'danger';
    method = 'activate';
    message = lang['inactive'];
    modal = 'data-toggle="modal" data-target="#myModal"';
  }
  status = `<div class="text-center"><a href="${site.base_url}notifications/${method}/${y[1]}" class="label label-${label}" ${modal}>
    <i class="fa fa-${icon}"></i> ${message}</a></div>`;

  return status;
}

function user_status(x) {
  var y = x.split('__');
  if (y[0] == 1) {
    icon = 'check';
    label = 'success';
    method = 'deactivate';
    message = lang['active'];
    modal = 'data-toggle="modal" data-target="#myModal"';
  } else {
    icon = 'times';
    label = 'danger';
    method = 'activate';
    message = lang['inactive'];
    modal = '';
  }
  status = `<div class="text-center"><a href="${site.base_url}auth/${method}/${y[1]}" ${modal}>
    <span class="label label-${label}"><i class="fa fa-${icon}"></i> ${message}</span></a></div>`;

  return status;
}
function approval_status(x) {
  if (x == null) {
    return '';
  } else if (x == 'need_approval' || x == 'not_approved') {
    return '<div class="text-center"><span class="approval_status label label-danger">' + lang[x] + '</span></div>';
  } else if (x == 'pending') {
    return '<div class="text-center"><span class="approval_status label label-warning">' + lang[x] + '</span></div>';
  } else if (x == 'approved') {
    return '<div class="text-center"><span class="approval_status label label-success">' + lang[x] + '</span></div>';
  } else if (x == 'partial') {
    return '<div class="text-center"><span class="approval_status label label-info">' + lang[x] + '</span></div>';
  } else if (x == 'due' || x == 'returned') {
    return '<div class="text-center"><span class="approval_status label label-danger">' + lang[x] + '</span></div>';
  } else {
    return '<div class="text-center"><span class="approval_status label label-default">' + x + '</span></div>';
  }
}
function payment_status(x) {
  if (x == null) {
    return '';
  } else if (x == 'need_payment' || x == 'need_approval' || x == 'expired') {
    return '<div class="text-center"><span class="payment_status label label-danger">' + lang[x] + '</span></div>';
  } else if (x == 'pending' || x == 'waiting_transfer' || x == 'waiting_payment') {
    return '<div class="text-center"><span class="payment_status label label-warning">' + lang[x] + '</span></div>';
  } else if (x == 'approved' || x == 'paid' || x == 'verified') {
    return '<div class="text-center"><span class="payment_status label label-success">' + lang[x] + '</span></div>';
  } else if (x == 'partial') {
    return '<div class="text-center"><span class="payment_status label label-info">' + lang[x] + '</span></div>';
  } else if (x == 'due' || x == 'due_partial' || x == 'returned') {
    return '<div class="text-center"><span class="payment_status label label-danger">' + lang[x] + '</span></div>';
  } else {
    return '<div class="text-center"><span class="payment_status label label-default">' + x + '</span></div>';
  }
}
function render_status(s, classStatus = 'status') { // DO NOT USE ANY FUNCTION STATUS, USE THIS INSTEAD.
  let type = 'default';
  if (s == 'due' || s == 'due_partial' || s == 'expired' || s == 'need_approval' || s == 'need_payment') type = 'danger';
  if (s == 'draft' || s == 'packing' || s == 'pending' || s == 'waiting_production' || s == 'waiting_transfer') type = 'warning';
  if (s == 'approved' || s == 'completed' || s == 'paid' || s == 'sent') type = 'success';
  if (s == 'completed_partial' || s == 'in_production' || s == 'ordered' || s == 'partial' || s == 'preparing') type = 'info';
  if (s == 'delivered' || s == 'received') type = 'primary';
  if (s == null) {
    type = 'default';
    msg = s;
  } else {
    msg = lang[s];
  }

  return `<div class="text-center"><span class="${classStatus} label label-${type}">${msg}</span></div>`;
}
function row_status(x) { // Please use common.js:renderStatus()
  if (x == null) {
    return '';
  } else if (x == 'due' || x == 'expired' || x == 'need_payment' || x == 'need_approval') {
    return '<div class="text-center"><span class="row_status label label-danger">' + lang[x] + '</span></div>';
  } else if (x == 'draft' || x == 'packing' || x == 'pending' || x == 'waiting_production') {
    return '<div class="text-center"><span class="row_status label label-warning">' + lang[x] + '</span></div>';
  } else if (x == 'completed' || x == 'installed' || x == 'paid' || x == 'sent' || x == 'approved') {
    return '<div class="text-center"><span class="row_status label label-success">' + lang[x] + '</span></div>';
  } else if (x == 'completed_partial' || x == 'in_production' || x == 'partial' || x == 'received_partial' || x == 'ordered' || x == 'preparing') {
    return '<div class="text-center"><span class="row_status label label-info">' + lang[x] + '</span></div>';
  } else if (x == 'delivered' || x == 'received') {
    return '<div class="text-center"><span class="row_status label label-primary">' + lang[x] + '</span></div>';
  } else {
    return '<div class="text-center"><span class="row_status label label-default">' + x + '</span></div>';
  }
}
function sales_item_status(x) {
  if (x == null) {
    return '';
  } else if (x == 'delivered') {
    return '<div class="text-center"><span class="sales_item_status label label-primary">' + lang[x] + '</span></div>';
  } else if (x == 'draft' || x == 'waiting_production') {
    return '<div class="text-center"><span class="sales_item_status label label-warning">' + lang[x] + '</span></div>';
  } else if (x == 'completed') {
    return '<div class="text-center"><span class="sales_item_status label label-success">' + lang[x] + '</span></div>';
  } else if (x == 'completed_partial' || x == 'in_production' || x == 'preparing') {
    return '<div class="text-center"><span class="sales_item_status label label-info">' + lang[x] + '</span></div>';
  } else if (x == 'canceled' || x == 'need_payment') {
    return '<div class="text-center"><span class="sales_item_status label label-danger">' + lang[x] + '</span></div>';
  } else {
    return '<div class="text-center"><span class="sales_item_status label label-default">' + x + '</span></div>';
  }
}
function pay_status(x) {
  return payment_status(x);
}
function formatSA(x) {
  x = x.toString();
  var afterPoint = '';
  if (x.indexOf('.') > 0) afterPoint = x.substring(x.indexOf('.'), x.length);
  x = Math.floor(x);
  x = x.toString();
  var lastThree = x.substring(x.length - 3);
  var otherNumbers = x.substring(0, x.length - 3);
  if (otherNumbers != '') lastThree = ',' + lastThree;
  var res = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThree + afterPoint;

  return res;
}

/**
 * Unit to Base Unit Qty.
 * @param {number} qty Unit Qty.
 * @param {object} unitObj Unit Object.
 *
 * @description Ex Convert 1 RIM to 500 Sheets
 */
function unitToBaseQty(qty, unitObj) {
  switch (unitObj.operator) {
    case '*':
      return parseFloat(qty) * parseFloat(unitObj.operation_value);
      break;
    case '/':
      return parseFloat(qty) / parseFloat(unitObj.operation_value);
      break;
    case '+':
      return parseFloat(qty) + parseFloat(unitObj.operation_value);
      break;
    case '-':
      return parseFloat(qty) - parseFloat(unitObj.operation_value);
      break;
    default:
      return parseFloat(qty);
  }
}

function baseToUnitQty(qty, unitObj) {
  switch (unitObj.operator) {
    case '*':
      return parseFloat(qty) / parseFloat(unitObj.operation_value);
      break;
    case '/':
      return parseFloat(qty) * parseFloat(unitObj.operation_value);
      break;
    case '+':
      return parseFloat(qty) - parseFloat(unitObj.operation_value);
      break;
    case '-':
      return parseFloat(qty) + parseFloat(unitObj.operation_value);
      break;
    default:
      return parseFloat(qty);
  }
}

function set_page_focus() {
  if (site.settings.set_focus == 1) {
    $('#add_item').attr('tabindex', 1);
    $('#add_item').focus();
  } else if (site.settings.set_focus == 2) {
    $('#add_item').attr('tabindex', an);
    $('[tabindex=' + (an - 1) + ']')
      .focus()
      .select();
  } else {
    // DISABLED
  }
  $('.rquantity').bind('keypress', function (e) {
    if (e.keyCode == 13) {
      $('#add_item').focus();
    }
  });
}

function calculateTax(tax, amt, met) {
  if (tax && tax_rates) {
    tax_val = 0;
    tax_rate = '';
    $.each(tax_rates, function () {
      if (this.id == tax) {
        tax = this;
        return false;
      }
    });
    if (tax.type == 1) {
      if (met == '0') {
        tax_val = formatDecimal((amt * parseFloat(tax.rate)) / (100 + parseFloat(tax.rate)), 4);
        tax_rate = formatDecimal(tax.rate) + '%';
      } else {
        tax_val = formatDecimal((amt * parseFloat(tax.rate)) / 100, 4);
        tax_rate = formatDecimal(tax.rate) + '%';
      }
    } else if (tax.type == 2) {
      tax_val = parseFloat(tax.rate);
      tax_rate = formatDecimal(tax.rate);
    }
    return [tax_val, tax_rate];
  }
  return false;
}

function calculateDiscount(val, amt) {
  if (val.indexOf('%') !== -1) {
    var pds = val.split('%');
    return formatDecimal(parseFloat((amt * parseFloat(pds[0])) / 100), 4);
  }
  return formatDecimal(val);
}

$(document).ready(function () {
  $('#edit-email-customer').click(function () {
    let customer = $('select[name=customer]').val();
    if (customer && customer.length > 0) {
      showModal(site.base_url + 'customers/edit_email/' + customer, 'modal-lg');
    }
    return false;
  });
  $('#view-customer').click(function () {
    let customer = $('select[name=customer]').val();
    if (customer && customer.length > 0) {
      showModal(site.base_url + 'customers/view/' + customer);
    }
    return false;
  });
  $(document).on('click', '#view-supplier', function () {
    let supplier = $('select[name=supplier]');
    if (supplier.val()) {
      $('#myModal2').find('.modal-dialog').load(site.base_url + 'suppliers/view/' + $('select[name=supplier]').val(), function () {
        $('#myModal2').modal('show');
      });
    }
    return false;
  });
  $('body').on('click', '.customer_details_link td:not(:first-child, :last-child)', function () {
    showModal(site.base_url + 'customers/view/' + $(this).parent('.customer_details_link').prop('id'));
  });
  $('body').on('click', '.supplier_details_link td:not(:first-child, :last-child)', function () {
    let supplier = $(this).parent('.supplier_details_link');
    showModal(site.base_url + 'suppliers/view/' + supplier.prop('id'));
  });
  $('body').on('click', '.item_history', function () {
    let product_id = $(this).prop('id');
    let start_date = $(this).data('start-date');
    let end_date = $(this).data('end-date');
    let warehouse_id = $(this).data('warehouse');
    let q = '';

    if (product_id) q += '&product=' + product_id;
    if (start_date) q += '&start_date=' + start_date;
    if (end_date) q += '&end_date=' + end_date;
    if (warehouse_id) q += '&warehouse=' + warehouse_id;

    showModal(site.base_url + 'products/history?' + q, 'modal-lg');
  });
  $('body').on('click', '.product_link td:not(:first-child, :nth-child(2), :last-child)', function () {
    let = startDate = $(this).closest('tr').data('start-date');
    let = endDate = $(this).closest('tr').data('end-date');

    if (startDate) {
      q = '?start_date=' + encodeURI(startDate) + '&end_date=' + encodeURI(endDate);
    } else {
      q = '';
    }

    showModal(site.base_url + 'products/modal_view/' + $(this).parent('.product_link').prop('id') + q, 'modal-lg');
  });
  $('body').on('click', '.internal_use_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
    if (oTable) oTable.fnDraw(false);
    showModal(site.base_url + 'procurements/internal_uses/view/' + $(this).closest('tr').prop('id'), 'modal-lg');
  });
  $('body').on('click', '.product_link2 td:first-child, .product_link2 td:nth-child(2)', function () {
    showModal(site.base_url + 'products/modal_view/' + $(this).closest('tr').prop('id'));
  });
  $('body').on('click', '.purchase_link td:not(:first-child, :nth-child(6), :nth-last-child(6), :nth-last-child(5), :last-child)', function () {
    if (oTable) oTable.fnDraw(false);
    showModal(site.base_url + 'procurements/purchases/view/' + $(this).parent('.purchase_link').prop('id'), 'modal-lg no-modal-header');
  });
  $('body').on('click', '.purchase_link2 td', function () {
    showModal(site.base_url + 'procurements/purchases/view/' + $(this).closest('tr').prop('id'), 'modal-lg no-modal-header');
  });
  $('body').on('click', '.transfer_link td:not(:first-child, :nth-last-child(4),:nth-last-child(3), :nth-last-child(2), :last-child)', function () {
    if (oTable) oTable.fnDraw(false);
    showModal(site.base_url + 'procurements/transfers/view/' + $(this).parent('.transfer_link').prop('id'), 'modal-lg no-modal-header');
  });
  $('body').on('click', '.transfer_link2', function () {
    showModal(site.base_url + 'procurements/transfers/view/' + $(this).prop('id'));
  });
  $('body').on('click', '.oreturn_link td:not(:first-child, :last-child)', function () {
    showModal(site.base_url + 'returns/view/' + $(this).parent('.oreturn_link').prop('id'));
  });
  // Change status on click
  $('body').on('click', '.sales_item_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
    showModal(site.base_url + 'sales/modal_status/' + $(this).parent('.sales_item_link').prop('id'), 'modal-lg no-modal-header');
  });
  // Invoice on click
  $('body').on('click', '.invoice_link td:not(:first-child, :nth-last-child(7), :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
    console.log('invoice click');
    if (oTable) oTable.fnDraw(false);
    showModal(site.base_url + 'sales/modal_view/' + $(this).parent('.invoice_link').prop('id'), 'modal-lg no-modal-header');
  });
  $('body').on('click', '.invoice_link2 td:not(:first-child, :last-child)', function () {
    showModal(site.base_url + 'sales/modal_view/' + $(this).closest('tr').prop('id'), 'modal-lg no-modal-header');
  });
  $('body').on('click', '.mutation_link td:not(:first-child, :nth-last-child(3), :nth-last-child(2), :last-child)', function () {
    showModal(site.base_url + 'finances/mutations/detail/' + $(this).parent('.mutation_link').prop('id'));
  });
  $('body').on('click', '.payment_link td', function () {
    showModal(site.base_url + 'sales/payment_note/' + $(this).parent('.payment_link').attr('id'));
  });
  $('body').on('click', '.payment_link2 td', function () {
    $('#myModal').find('.modal-dialog').load(
      site.base_url +
      'purchases/payment_note/' +
      $(this)
        .parent('.payment_link2')
        .attr('id'), function () {
          $('#myModal').modal('show');
        });
  });
  $('body').on('click', '.expense_link2 td:not(:last-child)', function () {
    $('#myModal').find('.modal-dialog').load(
      site.base_url +
      'procurements/purchases/expense_note/' +
      $(this)
        .closest('tr')
        .attr('id'), function () {
          $('#myModal').modal('show');
        });
  });
  $('body').on('click', '.customer_link td:not(:first-child)', function () {
    $('#myModal').find('.modal-dialog').load(
      site.base_url +
      'customers/edit/' +
      $(this)
        .parent('.customer_link')
        .attr('id'), function () {
          $('#myModal').modal('show');
        });
  });
  $('body').on('click', '.supplier_link td:not(:first-child)', function () {
    $('#myModal').find('.modal-dialog').load(
      site.base_url +
      'suppliers/edit/' +
      $(this)
        .parent('.supplier_link')
        .attr('id'), function () {
          $('#myModal').modal('show');
        });
  });
  $('body').on('click', '.adjustment_link td:not(:first-child, :nth-last-child(2), :last-child)', function () {
    showModal(site.base_url + 'products/view_adjustment/' + $(this).parent('.adjustment_link').attr('id'), 'modal-lg no-header-modal');
  });
  $('body').on('click', '.adjustment_link2', function () {
    showModal(site.base_url + 'products/view_adjustment/' + $(this).attr('id'), 'modal-lg no-header-modal');
  });
  $('#clearLS').click(function (event) {
    bootbox.confirm(lang.r_u_sure, function (result) {
      if (result == true) {
        localStorage.clear();
        location.reload();
      }
    });
    return false;
  });
  $('.sortable_rows')
    .sortable({
      items: '> tr',
      appendTo: 'parent',
      helper: 'clone',
      placeholder: 'ui-sort-placeholder',
      axis: 'x',
      update: function (event, ui) {
        var item_id = $(ui.item).attr('data-item-id');
      },
    })
    .disableSelection();
});

function fixAddItemnTotals() {
  /*var ai = $('#sticker');
  var aiTop = ai.position().top + 250;
  var bt = $('#bottom-total');
  $(window).scroll(function() {
    var windowpos = $(window).scrollTop();
    if (windowpos >= aiTop) {
      ai.addClass('stick')
        .css('width', ai.parent('form').width())
        .css('zIndex', 2);
      if ($.cookie('sma_theme_fixed') == 'yes') {
        ai.css('top', '40px');
      } else {
        ai.css('top', 0);
      }
      $('#add_item').removeClass('input-lg');
      //$('.addIcon').removeClass('fa-2x');
    } else {
      ai.removeClass('stick')
        .css('width', bt.parent('form').width())
        .css('zIndex', 2);
      if ($.cookie('sma_theme_fixed') == 'yes') {
        ai.css('top', 0);
      }
      $('#add_item').addClass('input-lg');
      //$('.addIcon').addClass('fa-2x');
    }
    if (windowpos <= $(document).height() - $(window).height() - 120) {
      bt.css('position', 'fixed')
        .css('bottom', 0)
        .css('width', bt.parent('form').width())
        .css('zIndex', 2);
    } else {
      bt.css('position', 'static')
        .css('width', ai.parent('form').width())
        .css('zIndex', 2);
    }
  });*/
}

function ItemnTotals() {
  fixAddItemnTotals();
  $(window).bind('resize', fixAddItemnTotals);
}

function getSlug(title, type) {
  var slug_url = site.base_url + 'welcome/slug';
  $.get(slug_url, { title: title, type: type }, function (slug) {
    $('#slug')
      .val(slug)
      .change();
  });
}

if (site.settings.auto_detect_barcode == 1) {
  $(document).ready(function () {
    var pressed = false;
    var chars = [];
    $(window).keypress(function (e) {
      chars.push(String.fromCharCode(e.which));
      if (pressed == false) {
        setTimeout(function () {
          if (chars.length >= 8) {
            var barcode = chars.join('');
            $('#add_item')
              .focus()
              .autocomplete('search', barcode);
          }
          chars = [];
          pressed = false;
        }, 200);
      }
      pressed = true;
    });
  });
}
$('.sortable_table tbody').sortable({
  containerSelector: 'tr'
});
$(window).bind('resize', widthFunctions);
$(window).on('load', widthFunctions);
