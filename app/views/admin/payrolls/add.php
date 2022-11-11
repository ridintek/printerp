<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
      <i class="fad fa-times"></i>
    </button>
    <h4 class="modal-title text-center" id="myModalLabel"><?= lang('add_payroll'); ?></h4>
  </div>
  <div class="modal-body">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('employee_name', 'user'); ?>
          <?php

          echo form_dropdown('user', ['' => ''], '', 'class="form-control user" id="user" data-placeholder="Select Employee" style="width:100%;"');
          ?>
        </div>
      </div><!-- /.col-md-6 -->
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('category', 'category'); ?>
          <?php
          $ct[''] = '';
          $categories = $this->site->getAllPayrollCategories();

          if ($categories) {
            foreach ($categories as $category) {
              $ct[$category->id] = $category->name;
            }
          }
          ?>
          <?= form_dropdown('category', $ct, '', 'class="form-control" id="category" data-placeholder="Select Category" required="required" style="width:100%;"'); ?>
        </div>
      </div><!-- /.col-md-6 -->
      <div class="col-md-12">
        <div class="form-group well well-sm">
          <div class="row">
            <div class="col-md-6">
              <?= lang('paid_by', 'paid_by') ?>
              <?php
              $bk[''] = lang('select') . ' ' . lang('paid_by');
              $banks = $this->site->getAllBanks();

              if ($banks) {
                foreach ($banks as $bank) {
                  $bk[$bank->id] = $bank->name;
                }
              }
              ?>
              <?= form_dropdown('paid_by', $bk, '', 'class="form-control select tip" id="paid_by" required="required" style="width:100%;"'); ?>
            </div>
            <div class="col-md-6">
              <?= lang('current_balance', 'balance_paid_by'); ?>
              <div id="balance_paid_by" class="form-control" style="padding: 7px"></div>
            </div>
          </div>
        </div>
      </div><!-- /.col-md-12 -->
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('amount', 'amount'); ?>
          <input type="text" class="form-control currency" id="amount" name="amount">
        </div>
      </div><!-- /.col-md-6 -->
      <div class="col-md-6">
        <div class="form-group">
          <?= lang('status', 'status'); ?>
          <?php
          $st = [
            '' => '',
            'paid' => 'Paid',
            'pending' => 'Pending'
          ];
          ?>
          <?= form_dropdown('status', $st, '', 'class="form-control" id="status" data-placeholder="Select Status" required="required" style="width:100%;"'); ?>
        </div>
      </div><!-- /.col-md-6 -->
    </div><!-- /.row -->
  </div>
  <div class="modal-footer">
    <?php echo form_button('add_payroll', lang('add_payroll'), 'class="btn btn-primary" id="submit"'); ?>
  </div>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function() {
    $('#submit').click(function() {
      let payroll_data = {
        user_id: $('#user').val(),
        category_id: $('#category').val(),
        amount: $('#amount').val()
      };

      payroll_data[security.csrf_token_name] = security.csrf_hash;

      $.ajax({
        data: payroll_data,
        method: 'POST',
        success: function(data) {
          console.log(data);
          if (typeof data === 'object' && !data.error) {
            addAlert(data.msg, 'success');
            if (oTable) oTable.fnDraw();
          } else if (typeof data === 'object' && data.error) {
            addAlert(data.msg, 'danger');
          }

          $('#myModal').modal('toggle');
        },
        url: site.base_url + 'payrolls/add'
      });
    });

    $('#paid_by').change(function() {
      <?php
      $bal = [];
      foreach ($banks as $bank) {
        $bal[$bank->id] = $bank->balance;
      }
      ?>
      let bal = JSON.parse('<?= json_encode($bal); ?>');
      $('#balance_paid_by').html(currencyFormat(bal[$(this).val()]));
    });
  });
</script>
<script async src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>