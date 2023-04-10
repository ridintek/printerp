<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
  .msg-editor {
    border: 1px solid #ccc;
    min-height: 25px;
    padding: 5px;
    width: 100%;
  }

  .msg-editor:focus {
    border-color: #66afe9;
    box-shadow: inset 0 1px 1px rgb(0 0 0 / 8%), 0 0 8px rgb(102 175 233 / 60%);
    outline: 0;
  }
</style>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa fa-fw fa-tools"></i><?= lang('tools'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fa fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <!-- SEND WHATSAPP -->
      <div class="col-sm-4">
        <div class="panel panel-primary">
          <div class="panel-heading">Send Whatsapp</div>
          <div class="panel-body">
            <form id="form_wa">
              <div class="col-sm-12">
                <div class="form-group">
                  <div class="row">
                    <div class="col-sm-12">
                      <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input class="form-control" placeholder="Phone Number" type="text" id="phone" name="phone">
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <label for="server">Server</label>
                        <select class="select2" id="server" name="server" style="width:100%">
                          <option value="jobs">Jobs</option>
                          <option value="watsap">Watsap</option>
                          <option value="whacenter">Whacenter</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group deviceid" style="display:none">
                        <label for="server">Device ID</label>
                        <select class="select2-tags" id="deviceid" name="deviceid" style="width:100%">
                          <option value="f34af955cc4daf71795012e58231b069">Whacenter 085712413114</option>
                          <option value="931ee27761187dc51a65326a8751c40a">Whacenter 085877992444</option>
                          <option value="8a9eb82b0ca70dbbcae5b269966c9631">Whacenter 089660044234</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12 apikey" style="display:none">
                      <div class="form-group">
                        <label for="apikey">API Key</label>
                        <select class="select2-tags" id="apikey" name="apikey" style="width:100%">
                          <option value="a66d60ee436b0861c28353611d089dc872629d09">a66d60ee436b0861c28353611d089dc872629d09</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <div class="form-group">
                        <label for="msg-editor">Message</label>
                        <div class="msg-editor" id="msg-editor" contenteditable></div>
                        <textarea class="skip" style="display:none" name="message"></textarea>
                      </div>
                    </div>
                    <div class="col-sm-12">
                      <button class="btn btn-primary form-control" id="send_wa"><i class="fa fa-send"></i> Send</button>
                    </div>
                  </div>
                </div>
              </div>
              <?= csrf_field() ?>
            </form>
          </div>
        </div>
      </div>
      <!-- OCR SCANNER -->
      <div class="col-sm-4">
        <div class="panel panel-primary">
          <div class="panel-heading">OCR Scanner</div>
          <div class="panel-body">
            <form id="form_ocr">
              <div class="col-sm-12">
                <label for="attachment">Upload Image</label>
                <div class="row">
                  <div class="col-sm-12 overflow-x">
                    <div class="form-group">
                      <input class="form-control file" type="file" id="attachment" name="attachment" data-browse-label="Browse" data-show-upload="false" data-show-preview="false">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <div class="msg-editor" id="ocr_response"></div>
                      <button class="btn btn-danger form-control" id="ocr_clear"><i class="fa fa-broom"></i> Clear</button>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <button class="btn btn-primary form-control" id="ocr_scan"><i class="fa fa-magnifying-glass"></i> Scan</button>
                    </div>
                  </div>
                </div>
              </div>
          </div>
          <?= csrf_field() ?>
          </form>
        </div>
      </div>
      <!-- FIND SALE -->
      <div class="col-sm-4">
        <div class="panel panel-primary">
          <div class="panel-heading">Find Sales</div>
          <div class="panel-body">
            <form id="form_findsale">
              <div class="col-sm-12">
                <label for="attachment">Upload CSV</label>
                <div class="row">
                  <div class="col-sm-12 overflow-x">
                    <div class="form-group">
                      <input class="form-control file" accept=".csv" type="file" name="attachment" data-browse-label="Browse" data-show-upload="false" data-show-preview="false">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <div class="msg-editor" id="findsale_response"></div>
                      <button class="btn btn-danger form-control" id="findsale_clear"><i class="fa fa-broom"></i> Clear</button>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-12">
                    <div class="form-group">
                      <button class="btn btn-primary form-control" id="findsale_find"><i class="fa fa-magnifying-glass"></i> Find</button>
                    </div>
                  </div>
                </div>
              </div>
          </div>
          <?= csrf_field() ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<script>
  $(document).ready(function() {
    $('#msg-editor').on('keyup', function() {
      console.log('edit');
      $('[name="message"]').html(this.innerHTML);
    });

    $('#findsale_find').click(function(e) {
      e.preventDefault();
      $('#findsale_find').prop('disabled', true);

      let formData = new FormData(document.querySelector('#form_findsale'));

      $.ajax({
        contentType: false,
        data: formData,
        error: (xhr) => {
          console.log(xhr);
          if (xhr.responseJSON) {
            toastr.error(xhr.responseJSON.message, 'Find Sales');
          } else {
            toastr.error(xhr.response, 'Find Sales');
          }
          $('#findsale_find').prop('disabled', false);
        },
        method: 'POST',
        processData: false,
        success: (data) => {
          toastr.success(data.message, 'Find Sales');
          $('#findsale_response').html(data.data);
          $('#findsale_find').prop('disabled', false);
        },
        url: site.base_url + 'developers/findSales'
      });
    });

    $('#findsale_clear').click(function(e) {
      e.preventDefault();

      $('#findsale_response').empty();
    });

    $('#ocr_clear').click(function(e) {
      e.preventDefault();

      $('#ocr_response').empty();
    });

    $('#ocr_scan').click(function(e) {
      e.preventDefault();
      $('#ocr_scan').prop('disabled', true);

      let formData = new FormData(document.querySelector('#form_ocr'));

      $.ajax({
        contentType: false,
        data: formData,
        error: (xhr) => {
          toastr.error(xhr.responseJSON.message, 'OCR Scanner');
          $('#ocr_scan').prop('disabled', false);
        },
        method: 'POST',
        processData: false,
        success: (data) => {
          toastr.success(data.message, 'OCR Scanner');
          $('#ocr_response').html(data.data);
          $('#ocr_scan').prop('disabled', false);
        },
        url: site.base_url + 'developers/ocr'
      });
    });

    $('#send_wa').click(function(e) {
      e.preventDefault();
      $('#send_wa').prop('disabled', true);

      let formData = new FormData(document.querySelector('#form_wa'));

      $.ajax({
        contentType: false,
        data: formData,
        error: (xhr) => {
          toastr.error(xhr.responseJSON.message, 'Send Whatsapp');
          $('#send_wa').prop('disabled', false);
        },
        method: 'POST',
        processData: false,
        success: (data) => {
          toastr.success(data.message, 'Send Whatsapp');
          $('#send_wa').prop('disabled', false);
        },
        url: site.base_url + 'developers/sendWA'
      });
    });

    $('#server').change(function() {
      if (this.value == 'jobs') {
        $('.apikey').slideUp();
        $('.deviceid').slideUp();
      } else if (this.value == 'watsap') {
        $('.apikey').slideDown();
        $('.deviceid').slideDown();
      } else if (this.value == 'whacenter') {
        $('.apikey').slideUp();
        $('.deviceid').slideDown();
      }
    });
  })
</script>