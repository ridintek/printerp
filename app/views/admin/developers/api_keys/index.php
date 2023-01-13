<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function() {
    Table = $('#Table').DataTable({
      ajax: {
        data: {
          <?= $this->security->get_csrf_token_name(); ?>: '<?= $this->security->get_csrf_hash(); ?>'
        },
        method: 'POST',
        url: site.base_url + 'developers/api_keys/getApiKeys'
      },
      columnDefs: [{
        targets: 0,
        orderable: false,
        render: checkbox
      }, {
        targets: 4,
        render: renderActive
      }],
      footerCallback: function(row, data) {},
      lengthMenu: [
        [25, 50, 100, -1],
        [25, 50, 100, "<?= lang('all') ?>"]
      ],
      order: [
        [1, 'asc']
      ],
      pageLength: <?= $Settings->rows_per_page ?>,
      rowCallback: function(row, data) {
        row.classList.add('apikey_link');
        row.dataset.id = data[0];
        row.dataset.name = data[1];
      },
      serverSide: true
    });

    $('#dtfilter').dataTableFilter();
  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa fa-fw fa-key"></i><?= lang('api_keys'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle tip" data-toggle="dropdown" title="Menu">
            <i class="icon fad fa-bars"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus">
            <li>
              <a href="<?= admin_url('developers/api_keys/add') ?>" data-toggle="modal" data-target="#myModal" data-backdrop="static">
                <i class="fa fa-fw fa-plus-circle"></i> Add API Keys
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
  <div class="box-content">
    <div class="row">
      <div class="col-lg-12">
        <div class="row">
          <div class="col-sm-3 float-right">
            <div class="input-group">
              <input id="dtfilter" class="form-control dtfilter" data-name="api_keys" placeholder="<?= lang('search'); ?>">
              <div class="input-group-addon dtfilter-search" style="padding: 2px 8px; border-left:0;">
                <a href="#" class="tip" title="Search Items"><i class="fad fa-search"></i></a>
              </div>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table id="Table" class="table table-bordered table-hover table-condensed table-striped">
            <thead>
              <tr>
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th>Name</th>
                <th>Token</th>
                <th>Scopes</th>
                <th>Active</th>
                <th>Created Date</th>
                <th>Expired Date</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="7" class="dataTables_empty"><?= lang('loading_data_from_server') ?></td>
              </tr>
            </tbody>
            <tfoot class="dtFilter">
              <tr class="active">
                <th style="min-width:30px; width: 30px; text-align: center;">
                  <input class="checkbox checkft" type="checkbox" name="check" />
                </th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    reloadContextMenu();
  });

  function reloadContextMenu() {
    let privilege = '<?= $Owner ?? $Admin; ?>';
    let contextMenuOpt = {
      selector: '.apikey_link',
      callback: function(key, opt) {
        let apikey_id = opt.$trigger.data('id');
        let name = opt.$trigger.data('name');

        if (key == 'delete') {
          alertify.dialog('confirm').set({
            message: `Are you sure to delete API Key <b>${name}</b>?`,
            onok: function() {
              let data = {
                id: apikey_id
              };
              data[security.csrf_token_name] = security.csrf_hash;
              $.ajax({
                data: data,
                method: 'POST',
                success: function(data) {
                  if (typeof data == 'object' && !data.error) {
                    if (Table) Table.draw();
                    addAlert(data.msg, 'success');
                  } else if (typeof data == 'object' && data.error) {
                    addAlert(data.msg, 'danger');
                  } else {
                    addAlert('Unknown error', 'danger');
                  }
                },
                url: site.base_url + 'developers/api_keys/delete'
              });
            },
            title: 'Delete API Key',
            transition: 'zoom'
          }).show();
        }
        if (key == 'edit') {
          location.href = site.base_url + 'developers/api_keys/edit/' + opname_id;
        }
        if (key == 'view') {
          showModal(site.base_url + 'developers/api_keys/view/' + opname_id, 'modal-lg no-modal-header');
        }
      }
    };

    contextMenuOpt.items = {};
    if (privilege == 1 || privilege == 2) {
      contextMenuOpt.items['delete'] = {
        name: 'Delete API Key',
        icon: 'fas fa-trash-alt'
      };
      contextMenuOpt.items['edit'] = {
        name: 'Edit API Key',
        icon: 'fas fa-edit'
      };
    }
    contextMenuOpt.items['view'] = {
      name: 'View Details',
      icon: 'fas fa-search'
    };

    $.contextMenu(contextMenuOpt);
  }
</script>