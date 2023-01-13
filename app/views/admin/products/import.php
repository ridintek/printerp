<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-upload"></i><?= lang('import_products'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <ul id="myTab" class="nav nav-tabs no-print">
          <li class=""><a href="#raw_material" class="tab-grey"><?= lang('import_raw') ?></a></li>
          <li class=""><a href="#service" class="tab-grey"><?= lang('import_service') ?></a></li>
          <li class=""><a href="#selling_product" class="tab-grey"><?= lang('import_sell') ?></a></li>
        </ul>

        <div class="tab-content">
          <div id="raw_material" class="tab-pane fade in">
            <!-- content -->
            <div class="box">
              <div class="box-header">
                <h2 class="blue"><i class="fa-fw fa fa-plus nb"></i><?= lang('add_raw_material_csv'); ?></h2>
              </div>
              <div class="box-content">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="row">
                      <div class="col-md-12">
                        <div class="well well-small">
                          <a href="https://docs.google.com/spreadsheets/d/1arv83XA2ySRAos6aFvhqLWIm804CjgUyChj7DsxaBj0/edit#gid=0" class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
                          <p>Change data from master file, then <b>Sync</b> it.
                          <p>
                        </div>
                        <div class="col-md-12">
                          <div class="form-group">
                            <a href="#" class="btn btn-primary sync-raw"><i class="fad fa-sync"></i> Sync</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- /content -->
          </div>
          <div id="service" class="tab-pane fade in">
            <!-- content -->
            <div class="box">
              <div class="box-header">
                <h2 class="blue"><i class="fa-fw fa fa-plus nb"></i><?= lang('add_service_csv'); ?></h2>
              </div>
              <div class="box-content">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="row">
                      <div class="col-md-12">
                        <div class="well well-small">
                          <a href="https://docs.google.com/spreadsheets/d/10UYqaF1eDeMc4qUDlK0UDbD5zr8Pv6aMDuKZ6RAQxb0/edit#gid=0" class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
                          <p>Change data from master file, then <b>Sync</b> it.
                          <p>
                        </div>
                        <div class="col-md-12">
                          <div class="form-group">
                            <a href="#" class="btn btn-primary sync-svc"><i class="fad fa-sync"></i> Sync</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- /content -->
          </div>
          <div id="selling_product" class="tab-pane fade in">
            <!-- content -->
            <div class="box">
              <div class="box-header">
                <h2 class="blue"><i class="fa-fw fa fa-plus nb"></i><?= lang('add_selling_product_csv'); ?></h2>
              </div>
              <div class="box-content">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="row">
                      <div class="col-md-12">
                        <div class="well well-small">
                          <a href="https://docs.google.com/spreadsheets/d/1VkkInHGgJdECp4Kma44eUrkzLaFc6ksLVAnBDklx6Vc/edit#gid=0" class="btn btn-primary pull-right" target="_blank"><i class="fa fa-link"></i> View Master File</a>
                          <p>Change data from master file, then <b>Sync</b> it.
                          <p>
                        </div>
                        <div class="col-md-12">
                          <div class="form-group">
                            <a href="#" class="btn btn-primary sync-spd"><i class="fad fa-sync"></i> Sync</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- /content -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    $('.sync-raw').click(function() {
      addConfirm({
        title: 'RAW Materials',
        message: 'Sync RAW Materials?',
        onok: () => {
          let formData = new FormData();

          formData.append('<?= csrf_token_name() ?>', '<?= csrf_hash() ?>');

          $.ajax({
            contentType: false,
            data: formData,
            error: (xhr) => {
              toastr.error(xhr.responseJSON.message);
            },
            method: 'POST',
            processData: false,
            success: (data) => {
              toastr.success(data.message);
            },
            url: site.base_url + 'products/import/sync/raw'
          });
        }
      });
    });

    $('.sync-svc').click(function() {
      addConfirm({
        title: 'Service Items',
        message: 'Sync Service Items?',
        onok: () => {
          let formData = new FormData();

          formData.append('<?= csrf_token_name() ?>', '<?= csrf_hash() ?>');

          $.ajax({
            contentType: false,
            data: formData,
            error: (xhr) => {
              toastr.error(xhr.responseJSON.message);
            },
            method: 'POST',
            processData: false,
            success: (data) => {
              toastr.success(data.message);
            },
            url: site.base_url + 'products/import/sync/svc'
          });
        }
      });
    });

    $('.sync-spd').click(function() {
      addConfirm({
        title: 'Selling Products',
        message: 'Sync Selling Products?',
        onok: () => {
          let formData = new FormData();

          formData.append('<?= csrf_token_name() ?>', '<?= csrf_hash() ?>');

          $.ajax({
            contentType: false,
            data: formData,
            error: (xhr) => {
              toastr.error(xhr.responseJSON.message);
            },
            method: 'POST',
            processData: false,
            success: (data) => {
              toastr.success(data.message);
            },
            url: site.base_url + 'products/import/sync/spd'
          });
        }
      });
    });
  });
</script>