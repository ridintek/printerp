<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
    <h4 class="modal-title text-center" id="myModalLabel">Edit Review [<?= $product->code ?>]</h4>
  </div>
  <div class="modal-body">
    <style>
      label {
        margin: 0;
        padding: 0;
      }

      /****** Style Star Rating Widget *****/
      .rating {
        border: none;
        margin: 0 auto;
        width: 180px;
      }

      .rating>input {
        display: none;
      }

      .rating>label:before {
        margin: 5px;
        font-size: 1.8em;
        font-family: FontAwesome;
        display: inline-block;
        content: "\f005";
      }

      .rating>label {
        color: #ddd;
        float: right;
      }

      /***** CSS Magic to Highlight Stars on Hover *****/

      .rating>input:checked~label,
      /* show gold star when clicked */
      .rating:not(:checked)>label:hover,
      /* hover current star */
      .rating:not(:checked)>label:hover~label {
        color: #FFD700;
      }

      /* hover previous stars in list */

      .rating>input:checked+label:hover,
      /* hover current star when changing rating */
      .rating>input:checked~label:hover,
      .rating>label:hover~input:checked~label,
      /* lighten current selection */
      .rating>input:checked~label:hover~label {
        color: #FFED85;
      }
    </style>
    <form id="form" data-toggle="validator" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_by">Created By</label>
            <select class="select2" id="created_by" name="created_by" style="width:100%;">
              <?php $users = User::get(['active' => '1']); ?>
              <?php foreach ($users as $user) :
                if (!$isAdmin) {
                  if ($user->id != XSession::get('user_id')) continue;
                }
              ?>
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="created_at">Created At</label>
            <input type="datetime-local" id="created_at" class="form-control" name="created_at" <?= ($isAdmin ? '' : ' disabled') ?>>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="warehouse_id">Warehouse</label>
            <select class="select2" id="warehouse_id" name="warehouse" style="width:100%;">
              <?php $warehouses = Warehouse::get(['active' => '1']); ?>
              <?php foreach ($warehouses as $warehouse) :
                if (!$isAdmin) {
                  if (XSession::get('warehouse_id')) {
                    if ($warehouse->id != XSession::get('warehouse_id')) continue;
                  }
                }

                $selected = (strcasecmp($warehouse->name, $product->warehouses) === 0 ? ' selected' : '');
              ?>
                <option value="<?= $warehouse->id ?>" <?= $selected ?>><?= $warehouse->name ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-group">
            <label>Rating</label>
            <div class="rating">
              <input type="radio" id="star5" class="skip" name="rating" value="5" /><label for="star5" title="Awesome"></label>
              <input type="radio" id="star4" class="skip" name="rating" value="4" /><label for="star4" title="Nice"></label>
              <input type="radio" id="star3" class="skip" name="rating" value="3" /><label for="star3" title="Good"></label>
              <input type="radio" id="star2" class="skip" name="rating" value="2" /><label for="star2" title="Bad"></label>
              <input type="radio" id="star1" class="skip" name="rating" value="1" /><label for="star1" title="Idiot"></label>
            </div>
          </div>
        </div>
      </div>

      <div class="row assign-ts" style="display:none">
        <div class="col-md-6">
          <div class="form-group">
            <label for="assigned_by">Assigned By</label>
            <select class="select2" id="assigned_by" name="assigned_by" style="width:100%;">
              <?php $users = User::get(['active' => '1']); ?>
              <?php foreach ($users as $user) :
                if (!$isAdmin) {
                  if ($user->id != XSession::get('user_id')) continue;
                }
              ?>
                <option value="<?= $user->id ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label>Assigned At</label>
            <input type="datetime-local" class="form-control" id="assigned_at" name="assigned_at">
          </div>
        </div>
      </div>

      <div class="row assign-ts">
        <div class="col-md-6">
          <div class="form-group">
            <label for="pic">Assign Team Support</label>
            <select class="select2" id="pic" name="pic" data-placeholder="Pilih TS" style="width:100%;">
              <option value=""></option>
              <?php $users = User::get(['active' => '1']); ?>
              <?php foreach ($users as $user) :
                $userGroup = $this->site->getUserGroup($user->id);

                if ($userGroup->name != 'support' && $userGroup->name != 'kurir') continue;
              ?>
                <option value="<?= $user->id ?>" data-group="<?= $userGroup->name ?>"><?= $user->fullname ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="attachment">Attachment</label>
            <input type="file" class="form-control file" name="attachment" data-browse-label="Browse" data-show-upload="false" data-show-preview="false">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group">
            <label for="note">User Note</label>
            <textarea class="form-control" id="note" name="note"></textarea>
          </div>
        </div>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-danger" data-dismiss="modal">Cancel</button>
    <button id="submit" class="btn btn-primary">Edit</button>
  </div>
</div>
<script defer src="<?= $assets ?>js/modal.js?v=<?= $res_hash ?>"></script>
<script>
  $(document).ready(function() {
    $('#assigned_at').val(dateTime('<?= $maintenanceLog->assigned_at ?>'));
    $('#created_at').val(dateTime('<?= $review->created_at ?>'));
    $('#created_by').val('<?= $review->created_by ?>').trigger('change');
    $('#note').val('<?= $review->note ?>');
    $('#pic').val('<?= $review->pic_id ?>').trigger('change');
    $('#star<?= $review->rating ?>').prop('checked', true);

    $('#submit').click(function() {
      let form = new FormData(document.getElementById('form'));

      $.ajax({
        contentType: false,
        data: form,
        error: (xhr) => {
          addAlert(xhr.responseJSON.message, 'danger');
          toastr.error(xhr.responseJSON.message);
        },
        method: 'POST',
        processData: false,
        success: function(data) {
          if (Table) Table.draw(false);
          if (Table2) Table2.draw(false);
          addAlert(data.message, 'success');
          toastr.success(data.message);

          $('#myModal2').modal('hide');
        },
        url: site.base_url + 'machines/review/edit/<?= $review->id ?>'
      });
    });
  });
</script>