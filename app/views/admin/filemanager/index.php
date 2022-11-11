<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
  $(document).ready(function() {

  });
</script>
<div class="box">
  <div class="box-header">
    <h2 class="blue"><i class="fa fa-fw fa-key"></i><?= lang('file_manager'); ?></h2>
    <div class="box-icon">
      <ul class="btn-tasks">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle tip" data-toggle="dropdown" title="Menu">
            <i class="icon fad fa-bars"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right tasks-menus">
            <li>
              <a href="<?= admin_url('developers/api_keys/add') ?>" data-toggle="modal" data-target="#myModal" data-backdrop="static">
                <i class="fa fa-fw fa-plus-circle"></i> Add New File
              </a>
            </li>
            <li>
              <a href="<?= admin_url('developers/api_keys/add') ?>" data-toggle="modal" data-target="#myModal" data-backdrop="static">
                <i class="fa fa-fw fa-plus-square"></i> Add New Folder
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
        <div>
          <ul class="filemanager" id="filemanager">
            <li>.</li>
            <li>..</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(document).ready(function() {
    reloadContextMenu();

    let fm = new FileManager('#filemanager');
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
                    if (oTable) oTable.draw();
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