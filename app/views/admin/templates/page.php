<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fad fa-book"></i>Page Template</h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            <i class="icon fad fa-fw fa-tasks tip" data-placement="left" title="<?= lang('actions') ?>"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus" role="menu">
            <li>
              <a href="#">
                <i class="fad fa-fw fa-plus-circle"></i> Add Something
              </a>
            </li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-check"></i> Action Something
              </a>
            </li>
            <li class="divider"></li>
            <li>
              <a href="#">
                <i class="fad fa-fw fa-trash"></i> Delete Something
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="tip" id="filter" title="Filter"><i class="icon fad fa-filter"></i></a>
        </li>
      </ul>
    </div>
  </div>
  <script>
    $('#filter').click((e) => {
      e.preventDefault();
      if ($('#form_filter').hasClass('closed')) {
        $('#form_filter').removeClass('closed').addClass('opened').slideDown();
      } else if ($('#form_filter').hasClass('opened')) {
        $('#form_filter').removeClass('opened').addClass('closed').slideUp();
      }
    });
  </script>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <p class="introtext">Your information text will be here</p>
        <!-- Filter Form -->
        <div id="form_filter" class="closed well well-sm" style="display: none">
          <div class="row">
            <div class="col-md-4">
              <label for="input1"> Input 1</label>
              <input class="form-control" id="input1">
            </div>
          </div>
        </div>
        <!-- Filter Box -->
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <!-- TODO: Change data-name as page name without space -->
              <input id="dtfilter" class="form-control dtfilter" data-name="changeAsYouWant" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- /.box-content -->
</div>