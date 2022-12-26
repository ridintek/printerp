<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">

  <div class="col-sm-2">
    <div class="row">
      <div class="col-sm-12 text-center">
        <div style="max-width:200px; margin: 0 auto;">
          <?=
          $user->avatar ? '<img alt="" src="' . base_url() . 'assets/uploads/avatars/thumbs/' . $user->avatar . '" class="avatar">' :
            '<img alt="" src="' . base_url() . 'assets/images/' . $user->gender . '.png" class="avatar">';
          ?>
        </div>
        <h4><?= $user->fullname ?></h4>
      </div>
    </div>
  </div>

  <div class="col-sm-10">

    <ul id="myTab" class="nav nav-tabs">
      <li class=""><a href="#edit" class="tab-grey"><?= lang('edit') ?></a></li>
      <li class=""><a href="#cpassword" class="tab-grey"><?= lang('change_password') ?></a></li>
      <li class=""><a href="#avatar" class="tab-grey"><?= lang('avatar') ?></a></li>
    </ul>

    <div class="tab-content">
      <div id="edit" class="tab-pane fade in">

        <div class="box">
          <div class="box-header">
            <h2 class="blue"><i class="fa-fw fad fa-edit nb"></i><?= lang('edit_profile'); ?></h2>
          </div>
          <div class="box-content">
            <div class="row">
              <div class="col-lg-12">

                <?php $attrib = ['class' => 'form-horizontal', 'data-toggle' => 'validator', 'role' => 'form'];
                echo admin_form_open('auth/edit_user/' . $user->id, $attrib);
                ?>
                <div class="row">
                  <div class="col-md-12">
                    <div class="col-md-5">
                      <div class="form-group">
                        <?php echo lang('full_name', 'fullname'); ?>
                        <div class="controls">
                          <?php echo form_input('fullname', $user->fullname, 'class="form-control" id="fullname" required="required"'); ?>
                        </div>
                      </div>

                      <?php if (!$this->ion_auth->in_group('customer', $id) && !$this->ion_auth->in_group('supplier', $id)) {
                      ?>
                        <div class="form-group">
                          <?php echo lang('company', 'company'); ?>
                          <div class="controls">
                            <?php echo form_input('company', $user->company, 'class="form-control" id="company" required="required"'); ?>
                          </div>
                        </div>
                      <?php
                      } else {
                        echo form_hidden('company', $user->company);
                      } ?>
                      <div class="form-group">

                        <?php echo lang('phone', 'phone'); ?>
                        <div class="controls">
                          <input type="tel" name="phone" class="form-control" id="phone" value="<?= $user->phone ?>" />
                        </div>
                      </div>

                      <div class="form-group">
                        <?= lang('gender', 'gender'); ?>
                        <div class="controls">
                          <?php
                          $ge[''] = ['male' => lang('male'), 'female' => lang('female')];
                          echo form_dropdown('gender', $ge, (isset($_POST['gender']) ? $_POST['gender'] : $user->gender), 'class="tip form-control select2" id="gender" required="required" style="width:100%;"');
                          ?>
                        </div>
                      </div>

                      <div class="form-group">
                        <?= lang('account_no', 'acc_no'); ?>
                        <?php
                        $json_data = json_decode($user->json_data);
                        $acc_no = ($json_data->acc_no ?? '');
                        ?>
                        <input type="number" class="form-control" name="acc_no" value="<?= $acc_no; ?>">
                      </div>

                      <?php if ($Owner || $Admin || $id == XSession::get('user_id')) { ?>
                        <div class="form-group">
                          <?php echo lang('username', 'username'); ?>
                          <input type="text" name="username" class="form-control" id="username" value="<?= $user->username ?>" autocomplete="off" required="required" />
                        </div>
                        <?php if ($Owner || $Admin) { ?>
                          <div class="row">
                            <div class="panel panel-warning">
                              <div class="panel-heading"><?= lang('if_you_need_to_rest_password_for_user') ?></div>
                              <div class="panel-body" style="padding: 5px;">
                                <div class="col-md-12">
                                  <div class="col-md-12">
                                    <div class="form-group">
                                      <?php echo lang('password', 'password'); ?>
                                      <?php echo form_input($password, '', 'autocomplete="off"'); ?>
                                    </div>

                                    <div class="form-group">
                                      <?php echo lang('confirm_password', 'password_confirm'); ?>
                                      <?php echo form_input($password_confirm); ?>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        <?php } ?>
                      <?php } ?>

                    </div>
                    <div class="col-md-6 col-md-offset-1">
                      <?php if (($Owner || $Admin) && $id != XSession::get('user_id')) { ?>
                        <div class="row">
                          <div class="panel panel-warning">
                            <div class="panel-heading"><?= lang('user_options') ?></div>
                            <div class="panel-body" style="padding: 5px;">
                              <div class="col-md-12">
                                <div class="col-md-12">
                                  <div class="form-group">
                                    <?= lang('status', 'status'); ?>
                                    <?php
                                    $opt = [1 => lang('active'), 0 => lang('inactive')];
                                    echo form_dropdown('status', $opt, (isset($_POST['status']) ? $_POST['status'] : $user->active), 'id="status" required="required" class="form-control input-tip select2" style="width:100%;"'); ?>
                                  </div>
                                  <div class="form-group">
                                    <?= lang('group', 'group'); ?>
                                    <?php
                                    $gp[''] = '';
                                    foreach ($groups as $group) {
                                      if ($group['name'] != 'customer' && $group['name'] != 'supplier') {
                                        $gp[$group['id']] = $group['name'];
                                      }
                                    }
                                    echo form_dropdown('group', $gp, (isset($_POST['group']) ? $_POST['group'] : $user->group_id), 'id="group" data-placeholder="' . $this->lang->line('select') . ' ' . $this->lang->line('group') . '" required="required" class="form-control input-tip select2" style="width:100%;"'); ?>
                                  </div>
                                  <div class="clearfix"></div>
                                  <div class="no">
                                    <div class="form-group">
                                      <?= lang('biller', 'biller'); ?>
                                      <?php
                                      $bl[''] =  lang('select') . ' ' . lang('biller');
                                      foreach ($billers as $biller) {
                                        $bl[$biller->id] = $biller->name;
                                      }
                                      echo form_dropdown('biller', $bl, $user->biller_id, 'id="biller" class="form-control select2" style="width:100%;"'); ?>
                                    </div>

                                    <div class="form-group">
                                      <?= lang('warehouse', 'warehouse'); ?>
                                      <?php
                                      $wh[''] = lang('select') . ' ' . lang('warehouse');
                                      foreach ($warehouses as $warehouse) {
                                        $wh[$warehouse->id] = $warehouse->name;
                                      }
                                      echo form_dropdown('warehouse', $wh, $user->warehouse_id, 'id="warehouse" class="form-control select2" style="width:100%;"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <?= lang('view_right', 'view_right'); ?>
                                      <?php
                                      $vropts = [1 => lang('all_records'), 0 => lang('own_records')];
                                      echo form_dropdown('view_right', $vropts, (isset($_POST['view_right']) ? $_POST['view_right'] : $user->view_right), 'id="view_right" class="form-control select2" style="width:100%;"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <?= lang('edit_right', 'edit_right'); ?>
                                      <?php
                                      $opts = [1 => lang('yes'), 0 => lang('no')];
                                      echo form_dropdown('edit_right', $opts, (isset($_POST['edit_right']) ? $_POST['edit_right'] : $user->edit_right), 'id="edit_right" class="form-control select2" style="width:100%;"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <?= lang('allow_discount', 'allow_discount'); ?>
                                      <?= form_dropdown('allow_discount', $opts, (isset($_POST['allow_discount']) ? $_POST['allow_discount'] : $user->allow_discount), 'id="allow_discount" class="form-control select2" style="width:100%;"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <?= lang('add_sale', 'add_sale'); ?>
                                      <?php
                                      $add_sale_opts = [
                                        'group' => 'By Group',
                                        '1' => 'Yes',
                                        '0' => 'No'
                                      ];
                                      ?>
                                      <?= form_dropdown('add_sale', $add_sale_opts, $this->site->getUserPermission('sales-add', $user->id), 'class="form-control select2" id="add_sale"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <?= lang('stock_opname_sequence_cycle', 'so_cycle'); ?>
                                      <?= form_input('so_cycle', ($json_data && property_exists($json_data, 'so_cycle') ? $json_data->so_cycle : 0), 'class="form-control" id="so_cycle"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <label for="biller_access">Biller Access</label>
                                      <?php
                                      $userJS = getJSON($user->json_data);
                                      $billers = $this->site->getAllBillers();

                                      if ($billers) {
                                        foreach ($billers as $biller) {
                                          if (
                                            $biller->name == 'Advertising' ||
                                            $biller->name == 'Baltis Inn' ||
                                            $biller->name == 'Lucretia Enterprise'
                                          ) continue;

                                          $bls[$biller->id] = $biller->name;
                                        }
                                      }

                                      echo form_multiselect('biller_access[]', $bls, ($userJS->biller_access ?? []), 'class="form-control select2" id="biller_access"'); ?>
                                    </div>
                                    <div class="form-group">
                                      <label for="user_permissions">User Permissions</label>
                                      <?php
                                      $json_data = json_decode($user->json_data, TRUE);
                                      $permissions = [];
                                      $user_permissions = ($json_data['permissions'] ?? NULL);

                                      if ($user_permissions) {
                                        foreach ($user_permissions as $name => $value) {
                                          $permissions[] = $name;
                                        }
                                      }
                                      // Custom individual privilege
                                      $perms = [
                                        'development'         => 'Development',
                                        'products-history'    => 'Products.History',
                                        'sales-add'           => 'Sales.Add',
                                        'sales-delete'        => 'Sales.Delete',
                                        'sales-edit'          => 'Sales.Edit',
                                        'sales-edit_price'    => 'Sales.EditPrice',
                                        'sales-index'         => 'Sales.Index',
                                        'sales-no_attachment' => 'Sales.NoAttachment'
                                      ];
                                      echo form_multiselect('user_permissions[]', $perms, ($permissions ?? []), 'class="form-control select2" id="user_permissions"'); ?>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php } ?>
                      <?php echo form_hidden('id', $id); ?>
                      <?php echo form_hidden($csrf); ?>
                    </div>
                  </div>
                </div>
                <p><?php echo form_submit('update', lang('update'), 'class="btn btn-primary"'); ?></p>
                <?php echo form_close(); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div id="cpassword" class="tab-pane fade">
        <div class="box">
          <div class="box-header">
            <h2 class="blue"><i class="fa-fw fad fa-key nb"></i><?= lang('change_password'); ?></h2>
          </div>
          <div class="box-content">
            <div class="row">
              <div class="col-lg-12">
                <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
                echo admin_form_open('auth/change_password', $attrib); ?>
                <div class="row">
                  <div class="col-md-12">
                    <div class="col-md-5">
                      <div class="form-group">
                        <?php echo lang('old_password', 'curr_password'); ?> <br />
                        <?php echo form_password('old_password', '', 'class="form-control" id="curr_password" required="required"'); ?>
                      </div>

                      <div class="form-group">
                        <label for="new_password"><?php echo sprintf(lang('new_password'), $min_password_length); ?></label>
                        <br />
                        <?php echo form_password('new_password', '', 'class="form-control" id="new_password" required="required" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" data-bv-regexp-message="' . lang('pasword_hint') . '"'); ?>
                        <span class="help-block"><?= lang('pasword_hint') ?></span>
                      </div>

                      <div class="form-group">
                        <?php echo lang('confirm_password', 'new_password_confirm'); ?> <br />
                        <?php echo form_password('new_password_confirm', '', 'class="form-control" id="new_password_confirm" required="required" data-bv-identical="true" data-bv-identical-field="new_password" data-bv-identical-message="' . lang('pw_not_same') . '"'); ?>
                      </div>
                      <?php echo form_input($user_id); ?>
                      <p><?php echo form_submit('change_password', lang('change_password'), 'class="btn btn-primary"'); ?></p>
                    </div>
                  </div>
                </div>
                <?php echo form_close(); ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="avatar" class="tab-pane fade">
        <div class="box">
          <div class="box-header">
            <h2 class="blue"><i class="fa-fw fad fa-file-picture-o nb"></i><?= lang('change_avatar'); ?>
              (Max: <?= $this->Settings->iwidth; ?> x <?= $this->Settings->iheight; ?>px)</h2>
          </div>
          <div class="box-content">
            <div class="row">
              <div class="col-lg-12">
                <div class="col-md-5">
                  <div style="position: relative;">
                    <?php if ($user->avatar) { ?>
                      <img alt="" src="<?= base_url() ?>assets/uploads/avatars/<?= $user->avatar ?>" class="profile-image img-thumbnail">
                      <a href="#" class="btn btn-danger btn-xs po" style="position: absolute; top: 0;" title="<?= lang('delete_avatar') ?>" data-content="<p><?= lang('r_u_sure') ?></p><a class='btn btn-block btn-danger po-delete' href='<?= admin_url('auth/delete_avatar/' . $id . '/' . $user->avatar) ?>'> <?= lang('i_m_sure') ?></a> <button class='btn btn-block po-close'> <?= lang('no') ?></button>" data-html="true"><i class="fad fa-trash-o"></i></a><br>
                      <br>
                    <?php } ?>
                  </div>
                  <?php echo admin_form_open_multipart('auth/update_avatar'); ?>
                  <div class="form-group">
                    <?= lang('change_avatar', 'change_avatar'); ?>
                    <input type="file" data-browse-label="<?= lang('browse'); ?>" name="avatar" id="product_image" data-show-upload="false" data-show-preview="false" accept="image/*" class="form-control file" />
                  </div>
                  <div class="form-group">
                    <?php echo form_hidden('id', $id); ?>
                    <?php echo form_hidden($csrf); ?>
                    <?php echo form_submit('update_avatar', lang('update_avatar'), 'class="btn btn-primary"'); ?>
                    <?php echo form_close(); ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    $(document).ready(function() {
      /*$('#change-password-form').bootstrapValidator({
        message: 'Please enter/select a value',
        submitButtons: 'input[type="submit"]'
      });*/
    });
  </script>
  <?php if (($Owner || $Admin) && $id != XSession::get('user_id')) { ?>
    <script type="text/javascript" charset="utf-8">
      $(document).ready(function() {
        $('#group').change(function(event) {
          var group = $(this).val();
          if (group == 1 || group == 2) {
            $('.no').slideUp();
          } else {
            $('.no').slideDown();
          }
        });
        var group = <?= $user->group_id ?>;
        if (group == 1 || group == 2) {
          $('.no').slideUp();
        } else {
          $('.no').slideDown();
        }
      });
    </script>
  <?php } ?>