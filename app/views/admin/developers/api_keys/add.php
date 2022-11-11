<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel">Add API Key</h4>
  </div>
  <div class="modal-body">
    <div class="row">
      <div class="col-lg-12">
        <div class="panel panel-primary">
          <div class="panel-heading">
            Add API Key
          </div>
          <div class="panel-body">
            <div class="col-sm-12">
              <div class="form-group">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" name="name">
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="name">Token</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="token" name="token">
                  <div class="input-group-addon" style="padding: 2px 8px; border-left:0;">
                    <a href="#" class="tip" id="generate" title="Generate API Key">
                      <i class="fad fa-key"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="scopes">Scopes</label>
                <?php
                // Get = Index.
                $opt = [
                  'all' => 'All',
                  'email.send' => 'Email.Send',
                  'finances.banks.add' => 'Finances.Banks.Add',
                  'products.add' => 'Products.Add',
                  'products.get' => 'Products.Get',
                  'sales.add' => 'Sales.Add',
                  'sales.delete' => 'Sales.Delete',
                  'sales.edit' => 'Sales.Edit',
                  'sales.get' => 'Sales.Get'
                ];
                echo form_multiselect('scopes[]', $opt, [], 'class="form-control select" id="scopes"');
                ?>
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="active">
                  <input type="checkbox" id="active" class="form-control" value="1"> Active
                </label>
              </div>
            </div>
            <div class="col-sm-12">
              <div class="form-group">
                <label for="expired_date">Expired Date</label>
                <input type="text" id="expired_date" class="form-control datetimenow">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" id="submit" class="btn btn-primary"><i class="fad fa-plus-circle"></i> Add</button>
  </div>
</div>
<script async src="<?= $assets ?>js/modal.js<?= $res_hash ?>"></script>
<script async src="<?= $assets ?>js/custom.js"></script>
<script>
  $(document).ready(function (e) {
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

    $('#active').on('ifChanged', function (e) {
      if (e.target.checked) {
        $('#expired_date').val('').prop('disabled', true);
      } else {
        $('#expired_date').prop('disabled', false).datetimepicker('update', new Date());
      }
    });

    $('#generate').on('click', function (e) {
      e.preventDefault();

      $.ajax({
        success: function (data) {
          if (typeof data == 'object' && ! data.error) {
            $('#token').val(data.token);
          }
        },
        url: site.base_url + 'developers/api_keys/generate'
      });
    });

    $('#submit').on('click', function (e) {
      e.preventDefault();

      let name = $('#name').val();
      let tokens = $('#token').val();
      let scopes = $('#scopes').val();
      let active = $('#active').val();
      let expired_date = $('#expired_date').val();

      let data = {
        name: name,
        tokens: tokens,
        scopes: scopes.join(','),
        active: active,
        expired_date: expired_date
      };

      data[security.csrf_token_name] = security.csrf_hash;

      $.ajax({
        data: data,
        method: 'POST',
        success: function (data) {
          if (typeof data == 'object' && ! data.error) {
            addAlert(data.msg, 'success');
            if (Table) Table.draw();
          } else {
            addAlert(data.msg, 'danger');
          }
          $('#myModal').modal('hide');
        },
        url: site.base_url + 'developers/api_keys/add'
      });
    });
  });
</script>