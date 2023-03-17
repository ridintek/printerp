<?php

defined('BASEPATH') or exit('No direct script access allowed');
// #[AllowDynamicProperties]
class MY_Controller extends CI_Controller
{
  /**
   * @var Memcached
   */
  protected $cache;

  /**
   * @var array
   */
  public $data;

  /**
   * @var bool
   */
  protected $isAJAX;

  /**
   * @var bool
   */
  protected $isLocal;

  /**
   * @var bool
   */
  protected $isDevServer;

  /**
   * @var string
   */
  protected $requestMethod;

  public $Owner;
  public $Admin;
  public $Customer;
  public $Supplier;
  public $Settings;
  public $SettingsJSON;
  public $microtime;
  public $res_hash;
  public $errorMsg;
  public $site;
  public $serverDateTime;
  public $serverDateTimeInput;
  public $theme;
  public $loggedIn;
  public $default_currency;
  public $sma;
  public $isAdmin;
  public $GP;
  public $schedules;
  public $input;
  public $config;
  public $lang;

  public function __construct()
  {
    parent::__construct();

    // die('<h2>Maintenance</h2>');
    $this->microtime = microtime(TRUE);
    $this->data['microtime'] = $this->microtime;

    // if (getGET('dbg') == 1) {
    //   $rest = (microtime(TRUE) - $this->microtime) * 1000;
    //   die("NORMAL {$rest} ms");
    // }

    Kint\Renderer\RichRenderer::$folder = FALSE;
    $this->res_hash = 'v=' . date('YmdH'); // For resource hash.
    // $this->res_hash = 'v=' . bin2hex(random_bytes(4)); // For resource hash.
    // $this->cache = $this->ridintek->cache();

    $this->errorMsg = 'success';

    $this->Settings = $this->site->getSettings();

    if (!$this->Settings) {
      echo '<b>Settings is not an object.</b><br>';
      die('Error: ' . getLastError());
    }

    // Reset last Error.
    setLastError();

    $this->SettingsJSON = $this->site->getSettingsJSON();

    if (!is_cli()) {
      if ($_SERVER['HTTP_HOST'] == 'erp.indoprinting.co.id') {
        $msg = 'Domain <b>erp.indoprinting.co.id</b> sudah tidak digunakan, domain baru <b>printerp.indoprinting.co.id</b>.<br>';
        $msg .= 'Anda akan segera dialihkan.. Matursuwun';
        $msg .= '<script>setTimeout(()=>location.href="https://printerp.indoprinting.co.id", 5000)</script>';
        die($msg);
      }

      $this->isAJAX = (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') == 0 ? TRUE : FALSE);
      $this->isLocal = (preg_match('/(.*)localhost$/i', $_SERVER['SERVER_NAME']) ? TRUE : FALSE);
      $this->isDevServer = (preg_match('/^derp\.(.*)/i', $_SERVER['SERVER_NAME']) ? TRUE : FALSE);
      $this->requestMethod = $_SERVER['REQUEST_METHOD'];

      // Memcached server.
      if (class_exists('Memcached')) {
        try {
          $this->cache = new Memcached();
          $this->cache->addServer('127.0.0.1', 11211);
        } catch (Exception $e) {
          die($e->getMessage());
        }
      }
    } else {
      $this->isAJAX = FALSE;
      $this->isLocal = FALSE;
      $this->isDevServer = FALSE;
      $this->requestMethod = NULL;
    }

    $this->data['isLocal'] = $this->isLocal;
    $this->data['isDevServer'] = $this->isDevServer;

    $this->serverDateTime = date('Y-m-d H:i:s');
    $this->serverDateTimeInput = date('Y-m-d H:i');

    $this->data['serverDateTime'] = $this->serverDateTime;
    $this->data['serverDateTimeInput'] = $this->serverDateTimeInput;

    if ($sma_language = $this->input->cookie('sma_language', true)) {
      $this->config->set_item('language', $sma_language);
      $this->lang->admin_load('sma', $sma_language);
      $this->Settings->user_language = $sma_language;
    } else {
      $this->config->set_item('language', $this->Settings->language);
      $this->lang->admin_load('sma', $this->Settings->language);
      $this->Settings->user_language = $this->Settings->language;
    }

    if ($rtl_support = $this->input->cookie('sma_rtl_support', true)) {
      $this->Settings->user_rtl = $rtl_support;
    } else {
      $this->Settings->user_rtl = $this->Settings->rtl;
    }

    $this->theme = 'admin/';
    $this->data['assets']   = base_url() . 'assets/';
    $this->data['res_hash'] = $this->res_hash;
    $this->data['Settings'] = $this->Settings;
    $this->data['SettingsJSON'] = $this->SettingsJSON; // Remove soon
    $this->data['SettingsJS'] = $this->SettingsJSON;
    $this->loggedIn         = XSession::has('user_id');

    if ($this->loggedIn) {
      $group = Group::getRow(['id' => XSession::get('group_id')]);

      if (!$group) {
        $group = Group::getRow(['name' => XSession::get('group_name')]);
      }

      if (!$group) {
        die('Session is logged in, but Group is not found.');
      }

      $this->default_currency         = $this->site->getCurrencyByCode($this->Settings->default_currency);
      $this->data['default_currency'] = $this->default_currency;
      $this->Owner                    = (strcasecmp($group->name, 'owner') === 0 ? TRUE : NULL);
      $this->data['Owner']            = $this->Owner;
      $this->Admin                    = (strcasecmp($group->name, 'admin') === 0 ? TRUE : NULL);
      $this->data['Admin']            = $this->Admin;
      $this->Customer                 = $this->sma->in_group('customer') ? TRUE : NULL;
      $this->data['Customer']         = $this->Customer;
      $this->Supplier                 = $this->sma->in_group('supplier') ? TRUE : NULL;
      $this->data['Supplier']         = $this->Supplier;

      $this->isAdmin = ($this->Owner || $this->Admin ? TRUE : FALSE);
      $this->data['isAdmin'] = $this->isAdmin;

      // $this->maintenance = FALSE; // Change this for maintenance mode.
      // $this->maintenance_by_time = FALSE; // Do not change this! Default: FALSE

      // $this->maintenance_start_date = '2022-10-06 11:00:00';
      // $this->maintenance_end_date   = '2022-10-06 23:00:00';

      // if (now() >= strtotime($this->maintenance_start_date) && now() < strtotime($this->maintenance_end_date)) {
      //   $this->maintenance_by_time = TRUE;
      // }

      // if (($this->maintenance || $this->maintenance_by_time) && !$this->Owner && $this->uri->segment(1) !== 'maintenance') {
      //   redirect_to('/maintenance');
      // }

      if ($sd = $this->site->getDateFormat($this->Settings->dateformat)) { // Always use this.
        $dateFormats = [
          'js_sdate'    => $sd->js,
          'php_sdate'   => $sd->php,
          'mysq_sdate'  => $sd->sql,
          'js_ldate'    => $sd->js . ' hh:ii:ss',
          'php_ldate'   => $sd->php . ' H:i:s',
          'mysql_ldate' => $sd->sql . ' %T',
        ];
      } else {
        $dateFormats = [
          'js_sdate'    => 'mm-dd-yyyy',
          'php_sdate'   => 'm-d-Y',
          'mysq_sdate'  => '%m-%d-%Y',
          'js_ldate'    => 'mm-dd-yyyy hh:ii:ss',
          'php_ldate'   => 'm-d-Y H:i:s',
          'mysql_ldate' => '%m-%d-%Y %T',
        ];
      }

      define('POS', 0);
      define('SHOP', 0);

      if (!$this->Owner && !$this->Admin) { // Other user group.
        $gp = $this->site->getGroupPermissions(XSession::get('group_id'), TRUE); // NEW, include PJ
        if ($gp) {
          $this->GP          = $gp;
          $this->data['GP']  = $gp;
        } else {
          die('NO PERMISSIONS');
        }
      } else { // Admin or Owner.
        $this->data['GP'] = NULL;
      }

      // Schedule IDP
      $this->schedules = [
        [
          'warehouse' => 'DUR',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'FAT',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'GAJ',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'PLE',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'TEM',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'UNG',
          'sun'       => ['09:00', '18:00'],
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => ['09:00', '18:00'],
        ],
        [
          'warehouse' => 'WEL',
          'sun'       => NULL,
          'mon'       => ['07:00', '22:00'],
          'tue'       => ['07:00', '22:00'],
          'wed'       => ['07:00', '22:00'],
          'thu'       => ['07:00', '22:00'],
          'fri'       => ['07:00', '22:00'],
          'sat'       => NULL,
        ],
      ];

      // Add holidays manually.
      $this->holidays = [
        ['2022-05-01', '2022-05-08'],
        '2022-05-17'
      ];

      $this->dateFormats         = $dateFormats;
      $this->data['dateFormats'] = $dateFormats;
      $this->load->language('calendar');

      $this->m                    = strtolower($this->router->fetch_class());
      $this->v                    = strtolower($this->router->fetch_method());
      $this->data['m']            = $this->m;
      $this->data['v']            = $this->v;
      $this->data['x']            = $this->uri->segment(4);
      $this->data['dt_lang']      = json_encode(lang('datatables_lang'));
      $this->data['dp_lang']      = json_encode(['days' => [lang('cal_sunday'), lang('cal_monday'), lang('cal_tuesday'), lang('cal_wednesday'), lang('cal_thursday'), lang('cal_friday'), lang('cal_saturday'), lang('cal_sunday')], 'daysShort' => [lang('cal_sun'), lang('cal_mon'), lang('cal_tue'), lang('cal_wed'), lang('cal_thu'), lang('cal_fri'), lang('cal_sat'), lang('cal_sun')], 'daysMin' => [lang('cal_su'), lang('cal_mo'), lang('cal_tu'), lang('cal_we'), lang('cal_th'), lang('cal_fr'), lang('cal_sa'), lang('cal_su')], 'months' => [lang('cal_january'), lang('cal_february'), lang('cal_march'), lang('cal_april'), lang('cal_may'), lang('cal_june'), lang('cal_july'), lang('cal_august'), lang('cal_september'), lang('cal_october'), lang('cal_november'), lang('cal_december')], 'monthsShort' => [lang('cal_jan'), lang('cal_feb'), lang('cal_mar'), lang('cal_apr'), lang('cal_may'), lang('cal_jun'), lang('cal_jul'), lang('cal_aug'), lang('cal_sep'), lang('cal_oct'), lang('cal_nov'), lang('cal_dec')], 'today' => lang('today'), 'suffix' => [], 'meridiem' => []]);

      // Added: Global Upload Config
      $this->upload_allowed_size            = 2048; // Max upload 2MB.
      $this->upload_adjustments_import_path = 'files/products/adjustments/import/';
      $this->upload_banks_path              = 'files/finances/banks/attachments/';
      $this->upload_expenses_path           = 'files/finances/expenses/attachments/';
      $this->upload_incomes_path            = 'files/finances/incomes/attachments/';
      $this->upload_mutations_path          = 'files/finances/mutations/attachments/';
      $this->upload_import_path             = 'files/import/';
      $this->upload_internal_uses_path      = 'files/procurements/internal_uses/attachments/';
      $this->upload_machine_path            = 'files/machines/attachments/';
      $this->upload_products_path           = 'files/products/attachments/';
      $this->upload_products_imports_path   = 'files/products/imports/';
      $this->upload_products_reports_path   = 'files/products/reports/';
      $this->upload_products_so_path        = 'files/products/stock_opnames/attachments/';
      $this->upload_purchases_path          = 'files/procurements/purchases/attachments/';
      $this->upload_purchases_payments_path = 'files/procurements/purchases/payments/';
      $this->upload_transfers_path          = 'files/procurements/transfers/attachments/';
      $this->upload_transfers_payment_path  = 'files/procurements/transfers/payments/';
      $this->upload_trackingpod_path        = 'files/trackingpod/attachments/';
      $this->upload_sales_path              = getAttachmentPaths('sales');
      $this->upload_sales_payments_path     = getAttachmentPaths('sales_payments');
      $this->upload_archive_type            = '7z|rar|tar|tgz|tgzip|zip|zipx';
      $this->upload_image_type              = 'gif|jpg|jpeg|png|tiff';
      $this->upload_csv_type                = 'csv';
      $this->upload_document_type           = 'doc|docx|pdf|txt|xls|xlsx';
      $this->upload_digital_type            = "{$this->upload_image_type}|{$this->upload_csv_type}|{$this->upload_document_type}";
    } else { // If not logged in.
      define('SHOP', 0);
    }
  }

  protected function page_construct($page, $data = [])
  {
    $data['message'] = isset($data['message']) ? $data['message'] : XSession::get('message');
    $data['error']   = isset($data['error'])   ? $data['error']   : XSession::get('error');
    $data['warning'] = isset($data['warning']) ? $data['warning'] : XSession::get('warning');

    $data['isLocal']             = $this->isLocal;
    $data['res_hash']            = $this->res_hash;
    $data['info']                = $this->site->getNotifications();
    $data['ip_address']          = $this->input->ip_address();
    $data['Owner']               = $data['Owner'];
    $data['Admin']               = $data['Admin'];
    $data['Supplier']            = $data['Supplier'];
    $data['Customer']            = $data['Customer'];
    $data['Settings']            = $data['Settings'];
    $data['Settings_JSON']       = getJSON($data['Settings']->settings_json); // Remove soon.
    $data['SettingsJS']          = getJSON($data['Settings']->settings_json);
    $data['dateFormats']         = $data['dateFormats'];
    $data['assets']              = $data['assets'];
    $data['GP']                  = $data['GP']; // Group Permissions.
    $data['qty_alert_num']       = $this->site->get_total_qty_alerts();
    $data['wh_stock_alert_num']  = $this->site->get_total_wh_stock_alerts();
    $data['exp_alert_num']       = $this->site->get_expiring_qty_alerts();
    $data['shop_sale_alerts']    = SHOP ? $this->site->get_shop_sale_alerts() : 0;
    $data['shop_payment_alerts'] = SHOP ? $this->site->get_shop_payment_alerts() : 0;

    $htmlContent = '';

    if (isset($this->cache)) {
      $cached = $this->cache->get('page:' . $page);

      if (!$cached) {
        $htmlContent = $this->load->view('admin/' . $page, $data, TRUE);
        $this->cache->set('page:' . $page, $htmlContent, 30);
      } else {
        $htmlContent = $cached;
      }
    }

    $htmlContent = $this->load->view('admin/' . $page, $data, TRUE);

    $data['html_content'] = $htmlContent;

    $this->load->view('admin/content', $data);
  }

  protected function response(int $code, array $data)
  {
    http_response_code($code);
    $data = array_merge(['status' => intval($code)], $data);
    sendJSON($data);
  }
}
