<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\DataType;

class Machines extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }
  }

  public function getMachines()
  {
    $xls        = (getGET('xls') == 1 ? TRUE : FALSE);
    $startDate  = (getGET('start_date') ?? date('Y-m-') . '01');
    $endDate    = (getGET('end_date') ?? date('Y-m-d'));
    $condition  = getGET('condition');
    $code       = getGET('code');
    $warehouses = XSession::get('warehouse_id') ?? getGET('warehouse');
    $whNames = [];

    if ($warehouses) {
      if (is_array($warehouses)) {
        foreach ($warehouses as $warehouse) {
          $wh = $this->site->getWarehouseByID($warehouse);

          $whNames[] = $wh->name;
        }
      } else {
        $whNames[] = $this->site->getWarehouseByID($warehouses)->name;
      }
    }

    if (!$xls) { // Datatable.
      $this->load->library('datatable');

      $this->datatable
        ->select("products.id AS product_id, products.id AS pid, products.code AS product_code,
          products.name AS product_name, categories.name AS category_name,
          subcategories.name AS subcategory_name, products.warehouses AS warehouses,
          products.json_data->>'$.condition' AS last_condition,
          products.json_data->>'$.updated_at' AS last_update,
          pic.fullname AS pic_name,
          products.json_data->>'$.updated_at' AS last_check", FALSE)
        ->from('products')
        ->join('categories', 'categories.id = products.category_id', 'left')
        ->join('categories AS subcategories', 'subcategories.id = products.subcategory_id', 'left')
        ->join("users AS pic", "pic.id = JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.pic_id'))", 'left')
        ->where('products.active', 1)
        ->group_start()
        ->like('categories.code', 'AST', 'none')
        ->or_like('categories.code', 'EQUIP', 'none')
        ->group_end()
        ->editColumn('pid', function ($data) use ($startDate, $endDate) {
          return "
            <div class=\"text-center\">
              <a href=\"{$this->theme}machines/report/add/{$data['product_id']}\"
                class=\"tip \"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
                style=\"color:green;\" title=\"Add Report\">
                  <i class=\"fad fa-fw fa-plus-square\"></i>
              </a>
              <a href=\"{$this->theme}machines/report/assign/{$data['product_id']}\"
                class=\"tip \"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
                style=\"color:red;\" title=\"Assign TS\">
                  <i class=\"fad fa-fw fa-plus-square\"></i>
              </a>
              <a href=\"{$this->theme}machines/report/view/{$data['product_id']}?start_date={$startDate}&end_date={$endDate}\"
                class=\"tip\"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
                data-modal-class=\"modal-lg\" title=\"View Report\">
                  <i class=\"fad fa-fw fa-chart-bar\"></i>
              </a>
              <a href=\"{$this->theme}machines/review/add/{$data['product_id']}\"
                class=\"tip \"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
                style=\"color:orange;\" title=\"Add Review\">
                  <i class=\"fad fa-fw fa-plus-square\"></i>
              </a>
              <a href=\"{$this->theme}machines/review/view/{$data['product_id']}?start_date={$startDate}&end_date={$endDate}\"
                class=\"tip\"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
                data-modal-class=\"modal-lg\" title=\"View Review\">
                  <i class=\"fas fa-fw fa-star\"></i>
              </a>
            </div>
          ";
        })
        ->editColumn('last_check', function ($data) {
          $todayCheck = date('Y-m-d', strtotime($data['last_check']));
          $todayDate  = date('Y-m-d');
          $hasUpdated = ($todayCheck == $todayDate ? TRUE : FALSE);

          return ($hasUpdated ? '<div class="text-center"><i class="fad fa-2x fa-check"></i></div>' : '');
        });

      if ($code) {
        $this->datatable->like('products.code', $code, 'none');
      }

      if ($condition) {
        $this->datatable->like("JSON_UNQUOTE(JSON_EXTRACT(products.json_data, '$.condition'))", $condition, 'none');
      }

      if ($whNames) {
        $this->datatable->group_start();
        foreach ($whNames as $name) {
          $this->datatable->or_like('products.warehouses', $name, 'none');
        }
        $this->datatable->group_end();
      }

      $this->datatable->generate();
    }
  }

  public function index()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('machines')]
    ];
    $meta['page_title'] = lang('machine_and_equipment');
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('machines/index', $this->data);
  }

  public function maintenance()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param warehouse.
      call_user_func_array([$this, $method], $params);
    }
  }

  protected function maintenance_edit($warehouseId)
  {
    checkPermission('machine-edit_schedule');

    $warehouse = $this->site->getWarehouseByID($warehouseId);
    $jsonData = getJSON($warehouse->json_data);

    $this->data['warehouse'] = $warehouse;

    if ($this->requestMethod == 'POST') {
      $groups = getPOST('group'); // Each group or Each warehouse.
      $g = [];

      // $group => [category: "ELEC", pic: 21, auto_assign: 1]

      foreach ($groups as $group) {
        $g[] = $group;
      }

      $jsonData->maintenances = $g;
      unset($g);

      $warehouseData = [
        'json_data' => json_encode($jsonData)
      ];

      if ($this->site->updateWarehouse(['id' => $warehouseId], $warehouseData)) {
        $this->response(200, ['message' => 'Jadwal berhasil diubah.']);
      }

      $this->response(400, ['message' => 'Gagal diubah. Kemungkinan tidak ada perubahan.']);
    }

    $this->load->view($this->theme . 'machines/maintenance/edit', $this->data);
  }

  protected function maintenance_getLogs()
  {
    $this->load->library('datatable');

    $this->datatable
      ->select("maintenance_logs.id AS id, maintenance_logs.id AS pid,
        maintenance_logs.product_code, maintenance_logs.assigned_at,
        assigner.fullname AS assigner_name,
        maintenance_logs.fixed_at,
        pic.fullname AS pic_name,
        warehouses.name AS location,
        maintenance_logs.note,
        maintenance_logs.created_at,
        creator.fullname AS creator_name,
        maintenance_logs.updated_at,
        updater.fullname AS updater_name,
        ")
      ->from('maintenance_logs')
      ->join('users assigner', 'assigner.id = maintenance_logs.assigned_by', 'left')
      ->join('users pic', 'pic.id = maintenance_logs.pic_id', 'left')
      ->join('users creator', 'creator.id = maintenance_logs.created_by', 'left')
      ->join('users updater', 'updater.id = maintenance_logs.updated_by', 'left')
      ->join('warehouses', 'warehouses.id = maintenance_logs.warehouse_id', 'left')
      ->editColumn('pid', function ($data) {
        return "";
        // return "
        //   <div class=\"text-center\">
        //     <a href=\"{$this->theme}machines/maintenance/edit/{$data['id']}\"
        //       class=\"tip \"
        //       data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
        //       style=\"color:green;\" title=\"Edit Log\">
        //         <i class=\"fad fa-fw fa-edit\"></i>
        //     </a>
        //     <a href=\"{$this->theme}machines/maintenance/delete/{$data['id']}\"
        //       class=\"tip \"
        //       data-action=\"confirm\" style=\"color:red;\" title=\"Delete Log\">
        //         <i class=\"fad fa-fw fa-trash\"></i>
        //     </a>
        //   </div>";
      });

    $this->datatable->generate();
  }

  protected function maintenance_getSchedules()
  {
    $this->load->library('datatable');

    $this->datatable
      ->select("warehouses.id AS id, warehouses.id AS pid, warehouses.name,
        warehouses.json_data AS tsname,
        warehouses.json_data AS auto_assign", FALSE)
      ->from('warehouses')
      ->where('warehouses.active', 1)
      ->editColumn('pid', function ($data) {
        return "
          <div class=\"text-center\">
            <a href=\"{$this->theme}machines/maintenance/edit/{$data['id']}\"
              class=\"tip \"
              data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal\"
              style=\"color:blue;\" title=\"Edit Schedule\">
                <i class=\"fad fa-fw fa-edit\"></i>
            </a>
          </div>
        ";
      })
      ->editColumn('tsname', function ($data) {
        $js = getJSON($data['tsname']);
        $maintenances = ($js->maintenances ?? []);
        $res = '<ul style="list-style:inside;">';

        foreach ($maintenances as $mt) {
          $category = $this->site->getProductCategoryByCode($mt->category);
          $tsname = '-';

          if (!$category) continue;

          if (!empty($mt->pic)) {
            $user = $this->site->getUserByID($mt->pic);
            $tsname = $user->fullname;
          }

          $res .= "<li>{$category->name}: {$tsname}</li>";
        }

        $res .= '</ul>';

        return trim($res);
      })
      ->editColumn('auto_assign', function ($data) {
        $js = getJSON($data['auto_assign']);
        $maintenances = ($js->maintenances ?? []);
        $res = '<ul style="list-style:inside;">';

        foreach ($maintenances as $mt) {
          $category = $this->site->getProductCategoryByCode($mt->category);

          if (!$category) continue;

          $auto_assign = (!empty($mt->auto_assign) && $mt->auto_assign == 1 ? 'Yes' : 'No');

          $res .= "<li>{$category->name}: {$auto_assign}</li>";
        }

        $res .= '</ul>';

        return trim($res);
      });

    $this->datatable->generate();
  }

  protected function maintenance_index()
  {
  }

  protected function maintenance_logs()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('machines'), 'page' => lang('machines')],
      ['link' => '#', 'page' => lang('maintenance_logs')]
    ];
    $meta['page_title'] = lang('maintenance_logs');
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('machines/maintenance/logs', $this->data);
  }

  protected function maintenance_schedules()
  {
    $meta['bc'] = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('machines'), 'page' => lang('machines')],
      ['link' => '#', 'page' => lang('maintenance_schedules')]
    ];
    $meta['page_title'] = lang('maintenance_schedules');
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('machines/maintenance/schedules', $this->data);
  }

  public function report()
  {
    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }
  }

  protected function report_add($productId)
  {
    $product = $this->site->getProductByID($productId);
    $productJS = getJSON($product->json_data);

    $this->data['product'] = $product;
    $this->data['creator'] = $this->site->getUserByID(XSession::get('user_id'));

    if ($this->requestMethod == 'POST') {
      $createdBy    = getPOST('created_by');
      $createdAt    = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $condition    = getPOST('condition');
      $note         = getPOST('note');
      $picNote      = getPOST('pic_note');
      $picId        = getPOST('pic');
      $warehouseId  = getPOST('warehouse');

      if (empty($picId)) $picId = NULL;

      if (empty($condition)) $this->response(400, ['message' => 'Condition harus di isi.']);

      if (($condition == 'off' || $condition == 'trouble') && empty($note)) {
        $this->response(400, ['message' => 'Note tidak boleh kosong.']);
      }

      $lastReports = $this->site->getProductReports([
        'product_id' => $productId,
        'order_by' => ['created_at', 'DESC'],
        'limit' => 1
      ]);

      $lastReport = ($lastReports ? $lastReports[0] : NULL);

      $reportData = [
        'product_id'   => $product->id,
        'warehouse_id' => $warehouseId,
        'created_by'   => $createdBy,
        'created_at'   => $createdAt,
        'condition'    => $condition,
        'note'         => $note,
        'pic_note'     => $picNote
      ];

      $upload = new FileUpload();

      if ($upload->has('attachment') && !$upload->isMoved()) {
        if ($upload->getSize('mb') > 2) {
          $this->response(400, ['message' => 'Ukuran attachment tidak boleh lebih dari 2MB.']);
        }

        $reportData['attachment'] = $upload->storeRandom();
      }

      if ($this->site->addProductReport($reportData)) {
        $this->site->updateProducts([[ // Update note ONLY.
          'product_id' => $product->id,
          'note' => $note,
          'pic_note' => $picNote
        ]]);

        $assigner = (!empty($productJS->assigned_by) ? User::getRow(['id' => $productJS->assigned_by]) : NULL);
        $warehouse  = $this->site->getWarehouseByID($warehouseId);
        $pic        = User::getRow(['id' => $productJS->pic_id]);
        $user       = User::getRow(['id' => $createdBy]);

        if ($condition == 'solved') {
          if (!empty($lastReport) && $lastReport->condition != 'good') {
            // Send report to CS/TL if status has been solved.
            if ($user->phone && $assigner) {
              $message = "Hi {$user->fullname},\n\n" .
                "Item berikut telah dilakukan perbaikan:\n\n" .
                "*Outlet*: {$warehouse->name}\n" .
                "*Assigned At*: " . dtLocal($productJS->assigned_at) . "\n" .
                "*Assigned By*: {$assigner->fullname}\n" .
                "*Item Code*: {$product->code}\n" .
                "*Item Name*: {$product->name}\n" .
                "*Fixed At*: " . dtLocal($createdAt) . "\n" .
                "*Fixed By*: {$pic->fullname}\n" .
                "*User Note*: " . htmlRemove($note) . "\n" .
                "*TS Note*: " . htmlRemove($picNote) . "\n\n" .
                "Silakan ubah status ke *Good* jika sudah benar.\n\n" .
                "Terima kasih.";

              $this->site->addWAJob([
                'phone'   => $user->phone,
                'message' => $message
              ]);
            }
          }
        }

        if ($condition == 'good') { // Reset if machine is good.
          // If last status is solved.
          if (!empty($lastReport) && $lastReport->condition == 'solved') {
            // Send report to PIC/TS if status has been good.
            if ($pic && $pic->phone && $assigner) {
              $message = "Hi {$pic->fullname},\n\n" .
                "Terima kasih telah melakukan perbaikan:\n\n" .
                "*Outlet*: {$warehouse->name}\n" .
                "*Assigned At*: " . dtLocal($productJS->assigned_at) . "\n" .
                "*Assigned By*: {$assigner->fullname}\n" .
                "*Item Code*: {$product->code}\n" .
                "*Item Name*: {$product->name}\n" .
                "*Fixed At*: " . dtLocal($createdAt) . "\n" .
                "*Fixed By*: {$pic->fullname}\n" .
                "*User Note*: " . htmlRemove($note) . "\n" .
                "*TS Note*: " . htmlRemove($picNote) . "\n";

              $this->site->addWAJob([
                'phone'   => $pic->phone,
                'message' => $message
              ]);
            }

            // Send report to CS/TL if status has been good.
            if ($user->phone && $assigner) {
              $message = "Hi {$user->fullname},\n\n" .
                "Item berikut telah berhasil dilakukan perbaikan:\n\n" .
                "*Outlet*: {$warehouse->name}\n" .
                "*Assigned At*: " . dtLocal($productJS->assigned_at) . "\n" .
                "*Assigned By*: {$assigner->fullname}\n" .
                "*Item Code*: {$product->code}\n" .
                "*Item Name*: {$product->name}\n" .
                "*Fixed At*: " . dtLocal($createdAt) . "\n" .
                "*Fixed By*: {$pic->fullname}\n" .
                "*User Note*: " . htmlRemove($note) . "\n" .
                "*TS Note*: " . htmlRemove($picNote) . "\n\n" .
                "Jangan lupa untuk memberikan review bintang 5 kepada TS pada link berikut:\n\n" .
                admin_url("machines?code={$product->code}\n\n") .
                "Terima kasih.";

              $this->site->addWAJob([
                'phone'   => $user->phone,
                'message' => $message
              ]);
            }

            // Add maintenance log.
            $mlog = $this->site->addMaintenanceLog([
              'product_id'      => $product->id,
              'product_code'    => $product->code,
              'assigned_at'     => (!empty($productJS->assigned_at) ? $productJS->assigned_at : $this->serverDateTime),
              'assigned_by'     => (!empty($productJS->assigned_by) ? $productJS->assigned_by : 1),
              'fixed_at'        => $createdAt,
              'pic_id'          => $productJS->pic_id,
              'warehouse_id'    => $warehouse->id,
              'warehouse_code'  => $warehouse->code,
              'note'            => $note,
              'pic_note'        => $picNote,
              'created_by'      => $createdBy
            ]);

            if (!$mlog) {
              $this->response(400, ['message' => getLastError()]);
            }
          } else if (!empty($lastReport) && $lastReport->condition != 'good') {
            $this->response(400, ['message' => 'Status harus <b>Solved</b> dahulu sebelum di <b>Good</b>.']);
          }

          // Reset product.
          $this->site->updateProducts([[
            'product_id' => $product->id,
            'pic_id' => '', // TS
            'assigned_at' => '', // Assigned date
            'assigned_by' => '',
            // 'note' => '',
            'pic_note' => ''
          ]]);
        }

        // Auto Assign TS.
        if ($condition == 'off' || $condition == 'trouble') {
          $warehouse = $this->site->getWarehouseByID($warehouseId);
          $whJS = getJSON($warehouse->json_data);
          $maintenances = ($whJS->maintenances ?? []);

          // If has maintenance schedule and pic is empty. Do not overwrite PIC if present!
          if ($maintenances && empty($productJS->pic_id)) {
            if ($subcat = $this->site->getProductCategoryByID($product->subcategory_id)) {
              foreach ($maintenances as $schedule) {
                if (empty($schedule->pic)) continue;

                if ($schedule->category == $subcat->code) {
                  $picId = ($picId ?? $schedule->pic);

                  // Send report to PIC/TS about problem.
                  if ($user = User::getRow(['id' => $picId])) {
                    if (!empty($user->phone)) {
                      $message = "Hi {$user->fullname},\n\n" .
                        "Outlet *{$warehouse->name}* membutuhkan perbaikan berikut:\n\n" .
                        "*Item Code*: {$product->code}\n" .
                        "*Item Name*: {$product->name}\n" .
                        "*Condition*: _*" . ucfirst($condition) . "*_\n" .
                        "*User Note*: _" . htmlRemove($note) . "_\n\n" .
                        "Mohon untuk segera diperbaiki. Terima kasih";

                      $this->site->addWAJob([
                        'phone' => $user->phone,
                        'message' => $message
                      ]);
                    }
                  }

                  if (isset($schedule->auto_assign) && $schedule->auto_assign == 1) {
                    $this->site->updateProducts([[
                      'product_id'  => $product->id,
                      'pic_id'      => $picId,
                      'assigned_at' => $createdAt,
                      'assigned_by' => $createdBy
                    ]]);
                  }
                }
              }
            }
          }
        }

        $this->response(201, ['message' => 'Product Report has been added successfully.']);
      }

      $this->response(400, ['message' => getLastError()]);
    }

    $reports = $this->site->getProductReports([
      'product_id' => $productId,
      'order_by' => ['created_at', 'DESC'],
      'limit' => 1
    ]);

    $this->data['lastReport'] = ($reports ? $reports[0] : NULL);

    $this->load->view($this->theme . 'machines/report/add', $this->data);
  }

  protected function report_assign($productId)
  {
    checkPermission('machine-assign');

    $product = $this->site->getProductByID($productId);
    $productJS = getJSON($product->json_data);

    $this->data['product'] = $product;

    if ($this->requestMethod == 'POST') {
      $picId = getPOST('pic');

      $productData = [
        'product_id'  => $product->id,
        'pic_id'      => intval($picId)
      ];

      if (empty($productJS->pic_id)) {
        $productData['assigned_at'] = $this->serverDateTime;
        $productData['assigned_by'] = XSession::get('user_id');
      }

      if ($this->site->updateProducts([$productData])) {
        $this->response(201, ['message' => 'Berhasil ditambahkan.']);
      }

      $this->response(400, ['message' => getLastError()]);
    }

    $this->load->view($this->theme . 'machines/report/assign', $this->data);
  }

  /**
   * Multi check for good to good condition.
   */
  protected function report_batch()
  {
    if ($this->requestMethod == 'POST') {
      $itemIds = getPOST('val');

      if (empty($itemIds)) {
        $this->response(400, ['message' => "Harap pilih salah satu item."]);
      }

      $problem = FALSE;
      $failed = 0;
      $success = 0;

      foreach ($itemIds as $itemId) {
        $product = $this->site->getProductByID($itemId);
        $warehouse = $this->site->getWarehouseByName($product->warehouses);

        $lastReport = $this->site->getProductReport([
          'product_id' => $itemId,
          'order_by' => ['created_at', 'DESC']
        ]);

        if (!$lastReport) continue;

        if ($lastReport->condition != 'good') {
          $problem = TRUE;
        }

        $reportData = [
          'product_id'   => $product->id,
          'warehouse_id' => $warehouse->id,
          'created_by'   => XSession::get('user_id'),
          'created_at'   => $this->serverDateTime,
          'condition'    => 'good',
          'note'         => 'OK'
        ];

        if (!$problem && $this->site->addProductReport($reportData)) {
          $success++;
        } else {
          $failed++;
          $problem = FALSE;
        }
      }

      if ($success) {
        $this->response(200, ['message' => "{$success} item berhasil dibuatkan report. {$failed} item gagal."]);
      }
      $this->response(400, ['message' => 'Gagal menambah report item yang dipilih. Pastikan item berstatus <b>Good</b>.']);
    }
  }

  protected function report_delete($reportId)
  {
    if (!getPermission('machine-report_delete')) {
      $this->response(403, ['message' => lang('access_denied')]);
    }

    $reports = $this->site->getProductReports(['id' => $reportId]);

    if ($this->site->deleteProductReport($reportId)) {
      if ($reports) {
        $this->site->syncProductReports($reports[0]->product_id);
      }

      $this->response(200, ['message' => 'Report telah dihapus']);
    }

    $this->response(400, ['message' => getLastError()]);
  }

  protected function report_edit($reportId)
  {
    if (!getPermission('machine-report_edit')) {
      if ($this->requestMethod == 'POST') {
        $this->response(402, ['message' => lang('access_denied')]);
      }

      $this->session->set_flashdata('error', lang('access_denied'));
      die('<script>
        $("#myModal").modal("hide");
        $("#myModal2").modal("hide");
        addAlert("' . lang('access_denied') . '", "danger");
        toastr.error("' . lang('access_denied') . '");
        </script>');
    }

    $report = $this->site->getProductReport(['id' => $reportId]);

    if ($report) {
      $product = $this->site->getProductByID($report->product_id);
    } else {
      $this->response(404, ['message' => 'Report not found']);
    }

    if ($this->requestMethod == 'POST') {
      $createdBy    = getPOST('created_by');
      $createdAt    = dtPHP(getPOST('created_at'));
      $condition    = getPOST('condition');
      $note         = getPOST('note');
      $picNote      = getPOST('pic_note');
      $warehouse_id = getPOST('warehouse');
      $picId        = getPOST('pic');

      if (empty($condition)) $this->response(400, ['message' => 'Condition must be set.']);

      $reportData = [
        'product_id'   => $product->id,
        'warehouse_id' => $warehouse_id,
        'created_by'   => $createdBy,
        'created_at'   => $createdAt,
        'condition'    => $condition,
        'note'         => $note,
        'pic_note'     => $picNote
      ];

      $upload = new FileUpload();

      if ($upload->has('attachment') && !$upload->isMoved()) {
        if ($upload->getSize('mb') > 2) {
          $this->response(400, ['message' => 'Ukuran attachment tidak boleh lebih dari 2MB.']);
        }

        Attachment::delete(['id' => $report->attachment_id]);

        $reportData['attachment'] = $upload->storeRandom();
      }

      if ($this->site->updateProductReport($reportId, $reportData)) {
        $this->site->updateProducts([[
          'product_id'  => $product->id,
          'note'        => $note,
          'pic_note'    => $picNote,
          'pic_id'      => ($condition == 'good' ? '' : intval($picId))
        ]]);

        $this->site->syncProductReports($product->id);

        $this->response(200, ['message' => 'Product Report has been updated successfully.']);
      }

      $this->response(400, ['message' => getLastError()]);
    }

    $this->data['product']    = $product;
    $this->data['productJS']  = json_decode($product->json_data);
    $this->data['report']     = $report;
    $this->data['creator']    = $this->site->getUserByID(XSession::get('user_id'));

    $this->load->view($this->theme . 'machines/report/edit', $this->data);
  }

  protected function report_getReports()
  {
    $productId  = getGET('product_id');
    $startDate = getGET('start_date');
    $endDate   = getGET('end_date');
    $xls = (getGET('xls') == 1 ? TRUE : FALSE);

    $period = getLastMonthPeriod(['start_date' => $startDate, 'end_date' => $endDate]);

    if (!$xls) {
      $this->load->library('datatable');

      $this->datatable
        ->select("product_report.id AS id, product_report.created_at AS created_at,
          condition, note, pic_note, creator.fullname AS creator_name, attachment_id")
        ->from('product_report')
        ->join('users creator', 'creator.id = product_report.created_by', 'left')
        ->where('product_report.product_id', $productId)
        ->where("product_report.created_at BETWEEN '{$period['start_date']} 00:00:00' AND '{$period['end_date']} 23:59:59'");

      $this->datatable
        ->addColumn('id', 'id', function ($data) {
          return "
            <div class=\"text-center\">
              <a href=\"{$this->theme}machines/report/delete/{$data['id']}\"
                class=\"tip\"
                data-action=\"confirm\" data-title=\"Delete Report\"
                data-message=\"Are you sure to delete this report?\"
                style=\"color:red;\" title=\"Delete Report\">
                  <i class=\"fad fa-fw fa-trash\"></i>
              </a>
              <a href=\"{$this->theme}machines/report/edit/{$data['id']}\"
                class=\"tip\"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal2\"
                title=\"Edit Report\">
                  <i class=\"fad fa-fw fa-edit\"></i>
              </a>
            </div>";
        }, 1)
        ->editColumn('attachment_id', function ($data) {
          return (!empty($data['attachment_id']) ? "<div class=\"text-center\">
            <a href=\"#\" data-remote=\"" .
            admin_url('gallery/attachment/' . $data['attachment_id'] . "?modal=1") . "\"
              data-toggle=\"modal\" data-modal-class=\"modal-lg\" data-target=\"#myModal2\">
            <i class=\"fad fa-file-download\"></i>
          </a>
        </div>" : '');
        });

      $this->datatable->generate();
    }
  }

  protected function report_view($productId)
  {
    $this->data['product'] = $this->site->getProductByID($productId);

    $this->load->view($this->theme . 'machines/report/view', $this->data);
  }

  public function review()
  {
    if ($argv = func_get_args()) {
      $method = __FUNCTION__ . '_' . $argv[0];

      if (method_exists($this, $method)) {
        array_shift($argv);
        return call_user_func_array([$this, $method], $argv);
      }
    }
  }

  protected function review_add($productId)
  {
    $product = $this->site->getProductByID($productId);

    $this->data['product'] = $product;
    $this->data['creator'] = $this->site->getUserByID(XSession::get('user_id'));

    if ($this->requestMethod == 'POST') {
      $createdBy    = getPOST('created_by');
      $createdAt    = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $note         = getPOST('note');
      $picId        = getPOST('pic');
      $rating       = getPOST('rating');
      $warehouseId  = getPOST('warehouse');

      if (!$rating) $this->response(400, ['message' => 'Rating bintang harus diberikan']);

      $reviewData = [
        'product_id'   => $product->id,
        'pic_id'       => $picId,
        'warehouse_id' => $warehouseId,
        'rating'       => $rating,
        'note'         => $note,
        'created_by'   => $createdBy,
        'created_at'   => $createdAt,
      ];

      $upload = new FileUpload();

      if ($upload->has('attachment') && !$upload->isMoved()) {
        if ($upload->getSize('mb') > 2) {
          $this->response(400, ['message' => 'Ukuran attachment tidak boleh lebih dari 2MB.']);
        }

        $reviewData['attachment'] = $upload->storeRandom();
      }

      if (ProductReview::add($reviewData)) {
        $this->response(201, ['message' => 'Product Review has been added successfully.']);
      }

      $this->response(400, ['message' => getLastError()]);
    }

    $this->data['maintenanceLog'] = MaintenanceLog::select('*')->orderBy('id', 'desc')->getRow();

    $this->load->view($this->theme . 'machines/review/add', $this->data);
  }

  protected function review_delete($reviewId)
  {
    if (!getPermission('machine-review_delete')) {
      $this->response(403, ['message' => lang('access_denied')]);
    }

    if (ProductReview::delete(['id' => $reviewId])) {
      $this->response(200, ['message' => 'Review telah dihapus']);
    }

    $this->response(400, ['message' => getLastError()]);
  }

  protected function review_edit($reviewId)
  {
    $review = ProductReview::getRow(['id' => $reviewId]);

    if (!$review) $this->response(404, ['message' => 'Google Review tidak ditemukan.']);

    $product = Product::getRow(['id' => $review->product_id]);

    if (!$product) $this->response(404, ['message' => 'Product tidak ditemukan.']);

    $maintenanceLog = MaintenanceLog::select('*')->where('product_id', $product->id)
      ->orderBy('id', 'desc')->getRow();

    if ($this->requestMethod == 'POST') {
      $createdAt    = ($this->isAdmin ? dtPHP(getPOST('created_at')) : $this->serverDateTime);
      $createdBy    = getPOST('created_by');
      $note         = getPOST('note');
      $picId        = getPOST('pic');
      $rating       = getPOST('rating');
      $warehouseId  = getPOST('warehouse');

      if (!$rating) $this->response(400, ['message' => 'Rating bintang harus diberikan']);

      $reviewData = [
        'created_at'    => $createdAt,
        'created_by'    => $createdBy,
        'note'          => $note,
        'pic_id'        => $picId,
        'rating'        => $rating,
        'warehouse_id'  => $warehouseId
      ];

      $upload = new FileUpload();

      if ($upload->has('attachment') && !$upload->isMoved()) {
        if ($upload->getSize('mb') > 2) {
          $this->response(400, ['message' => 'Ukuran attachment tidak boleh lebih dari 2MB.']);
        }

        Attachment::delete(['id' => $review->attachment_id]);

        $reportData['attachment'] = $upload->storeRandom();
      }

      if (ProductReview::update($reviewId, $reviewData)) {
        $this->response(200, ['message' => 'Product Review berhasil diubah.']);
      }
      $this->response(400, ['message' => 'Product Review gagal diubah.']);
    }

    $this->data['maintenanceLog'] = $maintenanceLog;
    $this->data['product'] = $product;
    $this->data['review'] = $review;

    $this->load->view($this->theme . 'machines/review/edit', $this->data);
  }

  protected function review_getReviews()
  {
    $productId  = getGET('product_id');
    $startDate = getGET('start_date');
    $endDate   = getGET('end_date');
    $xls = (getGET('xls') == 1 ? TRUE : FALSE);

    $period = getLastMonthPeriod(['start_date' => $startDate, 'end_date' => $endDate]);

    if (!$xls) {
      $this->load->library('datatable');

      $this->datatable
        ->select("product_review.id AS id, product_review.created_at AS created_at,
          rating, note, pic.fullname AS pic_name, creator.fullname AS creator_name, attachment_id")
        ->from('product_review')
        ->join('users creator', 'creator.id = product_review.created_by', 'left')
        ->join('users pic', 'pic.id = product_review.pic_id', 'left')
        ->where('product_id', $productId)
        ->where("created_at BETWEEN '{$period['start_date']} 00:00:00' AND '{$period['end_date']} 23:59:59'");

      $this->datatable
        ->addColumn('id', 'id', function ($data) {
          return "
            <div class=\"text-center\">
              <a href=\"{$this->theme}machines/review/delete/{$data['id']}\"
                class=\"tip\"
                data-action=\"confirm\" data-title=\"Delete Review\"
                data-message=\"Are you sure to delete this review?\"
                style=\"color:red;\" title=\"Delete Review\">
                  <i class=\"fad fa-fw fa-trash\"></i>
              </a>
              <a href=\"{$this->theme}machines/review/edit/{$data['id']}\"
                class=\"tip\"
                data-toggle=\"modal\" data-backdrop=\"false\" data-target=\"#myModal2\"
                title=\"Edit Review\">
                  <i class=\"fad fa-fw fa-edit\"></i>
              </a>
            </div>";
        }, 1)
        ->editColumn('rating', function ($data) {
          return rating2star($data['rating']);
        })
        ->editColumn('attachment_id', function ($data) {
          return (!empty($data['attachment_id']) ? "<div class=\"text-center\">
            <a href=\"#\" data-remote=\"" .
            admin_url('gallery/attachment/' . $data['attachment_id'] . "?modal=1") . "\"
              data-toggle=\"modal\" data-modal-class=\"modal-lg\" data-target=\"#myModal2\">
            <i class=\"fad fa-file-download\"></i>
          </a>
        </div>" : '');
        });

      $this->datatable->generate();
    }
  }

  protected function review_view($productId)
  {
    $this->data['product'] = $this->site->getProductByID($productId);

    $this->load->view($this->theme . 'machines/review/view', $this->data);
  }
}
