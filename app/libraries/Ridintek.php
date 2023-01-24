<?php
defined('BASEPATH') or exit('No direct script access allowed');

define('MXPATH', FCPATH . 'mutex' . DIRECTORY_SEPARATOR);

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill}; // PHP 7.0
use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Writer;

/**
 * File Upload class.
 */
class FileUpload
{
  protected $file = NULL;
  /**
   * @var array
   */
  protected $files = [];
  /**
   * @var bool
   */
  protected $isMoved;

  public function __construct()
  {
    if (is_cli()) die("FileUpload() class cannot be run in CLI mode.");

    $this->files = $_FILES;
  }

  public function files()
  {
    return $this->files;
  }

  /**
   * Check if file has been uploaded and has size more than zero.
   * @param string $filename Filename.
   */
  public function has($filename)
  {
    $this->isMoved = FALSE;

    if (isset($this->files[$filename]) && $this->files[$filename]['size'] > 0) {
      $this->file = $this->files[$filename];
      return TRUE;
    }
    return FALSE;
  }

  public function getExtension()
  {
    if ($this->file) {
      if (strpos($this->getName(), '.') !== FALSE) {
        $s = explode('.', $this->getName());
        $len = count($s);

        return '.' . $s[$len - 1];
      }
    }
    return NULL;
  }

  public function getRandomName()
  {
    if ($this->file) {
      return bin2hex(random_bytes(16)) . $this->getExtension();
    }
    return NULL;
  }

  public function getName()
  {
    if ($this->file) {
      return $this->file['name'];
    }
    return NULL;
  }

  /**
   * Get file size.
   * @param string unit Unit to check. byte, kb, mb, gb
   */
  public function getSize($unit = 'byte')
  {
    if ($this->file) {
      switch ($unit) {
        case 'kb':
          $acc = 1024;
          break;
        case 'mb':
          $acc = (1024 * 1024);
          break;
        case 'gb':
          $acc = (1024 * 1024 * 1024);
          break;
        case 'byte':
        default:
          $acc = 1;
      }

      return ceil($this->file['size'] / $acc);
    }
    return NULL;
  }

  public function getTempName()
  {
    if ($this->file) {
      return $this->file['tmp_name'];
    }
    return NULL;
  }

  public function getType()
  {
    if ($this->file) {
      return $this->file['type'];
    }
    return NULL;
  }

  /**
   * Check if file has been moved or not.
   * @return bool
   */
  public function isMoved()
  {
    return $this->isMoved;
  }

  public function move($path, $newName = NULL)
  {
    if ($this->file) {
      $path = rtrim($path, '/') . '/';
      checkPath($path);
      $newName = ($newName ?? $this->getName());

      if (move_uploaded_file($this->getTempName(), $path . $newName)) {
        $this->isMoved = TRUE;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Store file to attachment table as BLOB.
   * @param string $filename Filename to store. Use default filename if omitted.
   */
  public function store($filename = NULL)
  {
    $insertId = Attachment::add([
      'filename'  => ($filename ?? $this->getName()),
      'hashname'  => uuid(),
      'mime'      => $this->getType(),
      'data'      => file_get_contents($this->getTempName()),
      'size'      => $this->getSize()
    ]);

    $attachment = Attachment::getRow(['id' => $insertId]);

    return strval($attachment->hashname);
  }

  /**
   * Store file with random name to attachment table as BLOB.
   */
  public function storeRandom()
  {
    return $this->store($this->getRandomName());
  }
}

class Ridintek
{
  private $cache;
  private $gsheet;
  private $qc;
  private $ssheet;
  private $mx;

  public function __construct()
  {
  }

  public function cache()
  {
    $this->cache = new RD_Cache();
    checkPath(FCPATH . 'files/caches/');
    $this->cache->setPath(FCPATH . 'files/caches/');
    return $this->cache;
  }

  public function googlesheet()
  {
    $this->gsheet = new RD_Googlesheet();
    $this->gsheet->setCredentialFile(FCPATH . 'assets/credentials/PrintERP-66452fc687c7.json');
    $this->gsheet->setScopes([Google\Service\Sheets::SPREADSHEETS]);
    $this->gsheet->getGoogleServiceSheet();

    return $this->gsheet;
  }

  public function logger()
  {
    $this->log = new RD_Logger();
    return $this->log;
  }

  public function qrcode($data)
  {
    $this->qc = new RD_QRCode($data);
    return $this->qc->generate();
  }

  public function spreadsheet()
  {
    $this->ssheet = new RD_Spreadsheet();
    return $this->ssheet;
  }

  public function mutex($name = NULL)
  {
    $this->mx = new RD_Mutex($name);
    return $this->mx;
  }
}

/**
 * Asynchronous Task Class.
 */
class RD_Async
{
  private $phpbin;

  public function __construct()
  {
    $this->phpbin = phpBinary();
  }

  public function on($event, $callback)
  {
  }

  public function run($data, $event)
  {
    $data = [
      'callback' => 'async/callback',
      'param' => ''
    ];

    if (isOSLinux()) {
      $h = popen($this->phpbin . ' ' . FCPATH . 'index.php ' . $data['callback'] . ' ' . $data['param'], 'r');
    } else if (isOSWindows()) {
      $h = popen('start /b ' . $this->phpbin . ' ' . FCPATH . 'index.php ' . $data['callback'] . ' ' . $data['param'], 'r');
    }
    if ($h) {

      pclose($h);
    }
  }
}

/**
 * Cache
 */
class RD_Cache
{
  private   $ds;
  private   $hFile;
  protected $path;

  public function __construct()
  {
    $this->ds = DIRECTORY_SEPARATOR;
  }

  private function filterKey($key)
  {
    return preg_replace('/([^a-zA-Z0-9_-])/', '', $key);
  }

  public function delete($key)
  {
    $filename = $this->path . $this->ds . $this->filterKey($key) . '.log';
    if (file_exists($filename)) {
      unlink($filename);
    }
  }

  public function get($key)
  {
    $filename = $this->path . $this->ds . $this->filterKey($key) . '.log';
    if (is_dir($this->path) && is_file($filename)) {
      $buff = '';
      $this->hFile = fopen($filename, 'r');
      $buff = fgets($this->hFile);
      fclose($this->hFile);
      return $buff;
    }
    return NULL;
  }

  public function set($key, $value)
  {
    $filename = $this->path . $this->ds . $this->filterKey($key) . '.log';
    if (is_dir($this->path)) {
      $this->hFile = fopen($filename, 'w');
      if ($this->hFile && flock($this->hFile, LOCK_SH | LOCK_EX)) {
        fwrite($this->hFile, $value);
        flock($this->hFile, LOCK_UN);
        fclose($this->hFile);
        return TRUE;
      }
    }
    return FALSE;
  }

  public function setPath($path)
  {
    $this->path = rtrim($path, $this->ds);
  }
}

/**
 * Googlesheet
 */
class RD_Googlesheet
{
  protected $client;
  protected $credentialFile;
  protected $service;
  protected $sheetId;
  protected $spreadsheetId;

  public function __construct()
  {
    $this->client = new Google\Client();
  }

  public function getGoogleServiceSheet()
  {
    $this->service = new Google\Service\Sheets($this->client);
    return $this;
  }

  public function read($range)
  {
    $res = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
    $values = $res->getValues();
    return $values;
  }

  /**
   * Set application name.
   * @param string $name Application name.
   */
  public function setApplicationName($name)
  {
    if ($this->client) $this->client->setApplicationName($name);
    return $this;
  }

  /**
   * Set Credential JSON file.
   * @param string $filename Full path of credential JSON file.
   */
  public function setCredentialFile($filename)
  {
    if ($this->client) $this->client->setAuthConfig($filename);
    return $this;
  }

  /**
   * Set application scopes.
   * @param array $scopes Application scopes.
   */
  public function setScopes($scopes)
  {
    if ($this->client) $this->client->setScopes($scopes);
    return $this;
  }

  public function setSheetId($sheetId)
  {
    $this->sheetId = $sheetId;
    return $this;
  }

  public function setSpreadsheetId($spreadsheetId)
  {
    $this->spreadsheetId = $spreadsheetId;
    return $this;
  }
}

/**
 * Logger
 */
class RD_Logger
{
  private $_filename;
  private $_pathname;

  public function __construct()
  {
    $this->_filename = 'dev'; // Default as dev.
  }

  public function error($data)
  {
    $this->write('error', $data);
  }

  public function info($data)
  {
    $this->write('info', $data);
  }

  public function setPath($pathname)
  {
    $ds = '';
    if (substr($pathname, -1) != DIRECTORY_SEPARATOR) $ds = DIRECTORY_SEPARATOR;
    $this->_pathname = $pathname . $ds;
  }

  public function setFileName($filename)
  {
    $this->_filename = $filename;
  }

  public function success($data)
  {
    $this->write('success', $data);
  }

  public function warning($data)
  {
    $this->write('warning', $data);
  }

  public function write($type, $data)
  {
    $filename = $this->_pathname . $this->_filename . date('-Y-m-d') . '.php';
    $dt = print_r($data, TRUE);
    $new_data = FALSE;

    if (!file_exists($filename)) $new_data = TRUE;
    if (file_exists($filename) && is_file($filename) && filesize($filename) == 0) $new_data = TRUE;

    $hfile = fopen($filename, 'a+b');

    if ($hfile && flock($hfile, LOCK_EX)) {
      if ($new_data) fwrite($hfile, "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\r\n\r\n");

      fwrite($hfile, date('Y-m-d H:i:s') . ' [ ' . strtoupper($type) . ' ]: ');
      fwrite($hfile, $dt . "\r\n");
      flock($hfile, LOCK_UN);
      fclose($hfile);
    }
  }
}

/**
 * QRCode
 */
class RD_QRCode
{ // Tested.
  private $data;

  public function __construct($data)
  {
    $this->data = $data;
  }
  public function generate()
  {
    $options = new QROptions([
      'eccLevel' => QRCode::ECC_H,
      'imageTransparent' => FALSE,
      'outputType' => QRCode::OUTPUT_IMAGE_PNG,
      'scale' => 2
    ]);
    $qr = new QRCode($options);
    echo '<img src="' . $qr->render($this->data) . '" alt="' . $this->data .  '" class="qrimg link" />';
  }
}

/**
 * Spreadsheet
 */
class RD_Spreadsheet
{
  /**
   * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
   */
  private $spreadsheet;
  /**
   * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
   */
  private $worksheet;

  public function __construct()
  {
    $this->spreadsheet = new Spreadsheet();
    $this->worksheet = $this->spreadsheet->getActiveSheet();
    return $this->spreadsheet;
  }

  public function createSheet($index = NULL)
  {
    $this->worksheet = $this->spreadsheet->createSheet($index);
    return $this;
  }

  public function export($filename)
  {
    if (empty($filename)) return FALSE;
    $exportPath = FCPATH . '/files/exports/';
    $writer = new Writer\Xlsx($this->spreadsheet);
    $filename = (strlen($filename) < 6 ? $filename . '.xlsx' : $filename);
    $filename = (strtolower(substr($filename, -5, 5)) == '.xlsx' ? $filename : $filename . '.xlsx');
    $writer->save($exportPath . $filename);

    if (!is_file($exportPath . $filename)) {
      die('Cannot export. File doesn\'t exist.');
    }

    // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    // header('Content-Disposition: attachment; filename="' . $filename . '"');
    // header('Content-Length: ' . filesize($exportPath . $filename));
    // Just redirect it. If headers above are used. Error 520 on cloudflare.
    if (!is_cli()) {
      header('Location: ' . base_url('files/exports/' . $filename));
      exit();
    }

    return 'https://printerp.indoprinting.co.id/files/exports/' . $filename;
  }

  public function export_($filename)
  {
    if (empty($filename)) return FALSE;
    $writer = new Writer\Xlsx($this->spreadsheet);
    $filename = (strlen($filename) < 6 ? $filename . '.xlsx' : $filename);
    $filename = (strtolower(substr($filename, -5, 5)) == '.xlsx' ? $filename : $filename . '.xlsx');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');
    exit();
  }

  public function getProperties()
  {
    return $this->spreadsheet->getProperties();
  }

  public function getActiveSheet()
  {
    $this->worksheet = $this->spreadsheet->getActiveSheet();
    return $this;
  }

  public function getActiveSheetIndex()
  {
    return $this->spreadsheet->getActiveSheetIndex();
  }

  public function getSheet($index)
  {
    $this->worksheet = $this->spreadsheet->getSheet($index);
    return $this;
  }

  public function getSheetByName($name)
  {
    $this->worksheet = $this->spreadsheet->getSheetByName($name);
    return $this;
  }

  public function loadFile($file)
  {
    $reader = new Reader\Xlsx();
    $this->spreadsheet = $reader->load($file);
    $this->getActiveSheet();
    return $this;
  }

  /**
   * Merging cells
   * @param string $ranges Ranges to merge, Ex: 'A1:A10'
   */
  public function mergeCells($ranges)
  {
    $this->worksheet->mergeCells($ranges);
    return $this;
  }

  /**
   * Save as file.
   * @param string $filename Filename to save.
   */
  public function save($filename)
  {
    if (empty($filename)) return FALSE;
    $writer = new Writer\Xlsx($this->spreadsheet);
    $filename = (strlen($filename) < 6 ? $filename . '.xlsx' : $filename);
    $filename = (strtolower(substr($filename, -5, 5)) == '.xlsx' ? $filename : $filename . '.xlsx');
    $writer->save($filename);
    $this->spreadsheet->disconnectWorksheets();
    return TRUE;
  }

  public function setActiveSheetIndex($index)
  {
    $this->worksheet = $this->spreadsheet->setActiveSheetIndex($index);
    return $this;
  }

  public function setActiveSheetIndexByName($name)
  {
    $this->worksheet = $this->spreadsheet->setActiveSheetIndexByName($name);
    return $this;
  }

  public function setAlignment($ranges, $align)
  {
    $this->worksheet->getStyle($ranges)->getAlignment()->setHorizontal($align);
    return $this;
  }

  /**
   * range = 'A1:E1'
   */
  public function setAutoFilter($ranges)
  {
    $this->worksheet->setAutoFilter($ranges);
    return $this;
  }

  public function setBold($ranges, $bold = TRUE)
  {
    $this->worksheet->getStyle($ranges)->getFont()->setBold($bold);
    return $this;
  }

  public function setCellValue($cell, $value, $type = NULL)
  {
    if ($type) {
      $this->setCellValueExplicit($cell, $value, $type);
    } else {
      $this->worksheet->setCellValue($cell, $value);
    }
    return $this;
  }

  public function setCellValueByColumnAndRow($col, $row, $value)
  {
    $this->worksheet->setCellValueByColumnAndRow($col, $row, $value);
    return $this;
  }

  public function setCellValueExplicit($cell, $value, $type)
  {
    $this->worksheet->setCellValueExplicit($cell, $value, $type);
    return $this;
  }

  public function setColor($ranges, $rgbColor)
  {
    $this->worksheet->getStyle($ranges)->getFont()->getColor()->setRGB($rgbColor);
    return $this;
  }

  public function setColumnAutoWidth($col)
  {
    $this->worksheet->getColumnDimension($col)->setAutoSize(TRUE);
    return $this;
  }

  public function setColumnWidth($col, $width)
  {
    $this->worksheet->getColumnDimension($col)->setWidth($width);
    return $this;
  }

  public function setComment($col, string $text)
  {
    $this->worksheet->getComment($col)->getText()->createText($text);
    return $this;
  }

  /**
   * $ranges = 'A1:C1'
   * $rgbColor [RGB] = 'FF0000' (Red)
   */
  public function setFillColor($ranges, $rgbColor)
  {
    $this->worksheet->getStyle($ranges)->getFill()
      ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgbColor);
    return $this;
  }

  public function setHorizontalAlign($ranges, $align = Alignment::HORIZONTAL_GENERAL)
  {
    $this->worksheet->getStyle($ranges)->getAlignment()->setHorizontal($align);
  }

  public function setWorkbookFontName($fontName)
  {
    $this->spreadsheet->getDefaultStyle()->getFont()->setName($fontName);
    return $this;
  }

  public function setWorkbookFontSize($fontSize)
  {
    $this->spreadsheet->getDefaultStyle()->getFont()->setSize($fontSize);
    return $this;
  }

  public function setItalic($ranges)
  {
    $this->worksheet->getStyle($ranges)->getFont()->setItalic(TRUE);
    return $this;
  }

  public function setTabColor($rgbColor)
  {
    $this->worksheet->getTabColor()->setRGB($rgbColor);
    return $this;
  }

  public function setTitle($title)
  {
    $this->worksheet->setTitle($title);
    return $this;
  }

  public function setUnderline($ranges)
  {
    $this->worksheet->getStyle($ranges)->getFont()->setUnderline(TRUE);
    return $this;
  }

  public function setUrl($cell, $url)
  {
    $this->worksheet->getCell($cell)->getHyperlink()->setUrl($url);
    return $this;
  }

  public function setVerticalAlign($ranges, $align = Alignment::VERTICAL_TOP)
  {
    $this->worksheet->getStyle($ranges)->getAlignment()->setHorizontal($align);
  }

  public function setWrapText($ranges, $enable = TRUE)
  {
    $this->worksheet->getStyle($ranges)->getAlignment()->setWrapText($enable);
    return $this;
  }
}

/**
 * Mutual Exclusion
 */
class RD_Mutex
{
  private $mxname;
  private $ev;
  private $ret;

  public function __construct($name = NULL)
  {
    $this->ev = [];
    if ($name) {
      $this->mxname = $name;
    }
  }

  public function close($return = NULL)
  {
    if ($this->hfile) {
      flock($this->hfile, LOCK_UN);
      fclose($this->hfile);
      if (file_exists(MXPATH . $this->mxname)) {
        @unlink(MXPATH . $this->mxname);
        $this->ret = ($return ?? $this->ret);
        return $this->ret;
      }
    }
    return NULL;
  }

  public function create($name = NULL)
  {
    $ret = NULL;
    if ($name) $this->mxname = $name;
    if (empty($this->mxname)) {
      throw new Exception('Mutex Name is not defined.');
    }
    $this->hfile = fopen(MXPATH . $this->mxname, 'w');
    if ($this->hfile && flock($this->hfile, LOCK_EX)) {
      foreach ($this->ev as $event) {
        if ($event['event'] == 'lock') {
          $this->ret = call_user_func_array($event['callback'], [$this]);
        }
      }
    }
    return $this;
  }

  public function on($event, $callback)
  {
    $this->ev[] = [
      'event' => $event,
      'callback' => $callback
    ];
    return $this;
  }
}
/* EOF */