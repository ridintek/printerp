<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa-fw fa fa-plus"></i><?= lang('add_selling_product_csv'); ?></h2>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">

        <?php
        $attrib = ['class' => 'form-horizontal', 'data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('products/import_csv_spd', $attrib)
        ?>
        <div class="row">
          <div class="col-md-12">

            <div class="well well-small">
              <a href="<?php echo base_url(); ?>assets/csv/sample_selling_products.csv"
                 class="btn btn-primary pull-right"><i
                  class="fa fa-download"></i> <?= lang('download_sample_file') ?></a>
              <p>
                <span class="text-warning"><?= lang('csv1'); ?></span><br/><?= lang('csv2'); ?> <span
                class="text-info">(<?= lang('product_code') . ', ' . lang('product_name') . ', ' . lang('category_code') . ', ' . lang('raw_code') . ', ' . lang('raw_qty') . ', ' . lang('price_range_1') .
                ', ' . lang('price_range_2') . ', ' . lang('price_range_3') . ', ' . lang('price_range_4') . ', ' . lang('price_range_5') . ', ' . lang('price_range_6') . ', ' .
                lang('zone_1_price_1') . ', ' . lang('zone_1_price_2') . ', ' . lang('zone_1_price_3') . ', ' . lang('zone_1_price_4') . ', ' . lang('zone_1_price_5') . ', ' . lang('zone_1_price_6') . ', ' .
                lang('zone_2_price_1') . ', ' . lang('zone_2_price_2') . ', ' . lang('zone_2_price_3') . ', ' . lang('zone_2_price_4') . ', ' . lang('zone_2_price_5') . ', ' . lang('zone_2_price_6') . ', ' .
                lang('zone_3_price_1') . ', ' . lang('zone_3_price_2') . ', ' . lang('zone_3_price_3') . ', ' . lang('zone_3_price_4') . ', ' . lang('zone_3_price_5') . ', ' . lang('zone_3_price_6') . ', ' .
                lang('zone_4_price_1') . ', ' . lang('zone_4_price_2') . ', ' . lang('zone_4_price_3') . ', ' . lang('zone_4_price_4') . ', ' . lang('zone_4_price_5') . ', ' . lang('zone_4_price_6') . ', ' .
                lang('zone_5_price_1') . ', ' . lang('zone_5_price_2') . ', ' . lang('zone_5_price_3') . ', ' . lang('zone_5_price_4') . ', ' . lang('zone_5_price_5') . ', ' . lang('zone_5_price_6') . ', ' .
                lang('zone_6_price_1') . ', ' . lang('zone_6_price_2') . ', ' . lang('zone_6_price_3') . ', ' . lang('zone_6_price_4') . ', ' . lang('zone_6_price_5') . ', ' . lang('zone_6_price_6'); ?>
                )</span> <?= lang('csv4'); ?>
              </p>
              <p><?= lang('images_location_tip'); ?></p>
              <span class="text-primary"><?= lang('csv_update_tip'); ?></span>
            </div>

            <div class="col-md-12">
              <div class="form-group">
                <label for="csv_file"><?= lang('upload_file'); ?></label>
                <input type="file" data-browse-label="<?= lang('browse'); ?>" name="userfile" class="form-control file" data-show-upload="false" data-show-preview="false" id="csv_file" required="required"/>
              </div>

              <div class="form-group">
                <?php echo form_submit('import', $this->lang->line('import'), 'class="btn btn-primary"'); ?>
              </div>
            </div>
          </div>
        </div>
        <?= form_close(); ?>
      </div>
    </div>
  </div>
</div>
