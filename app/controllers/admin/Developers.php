<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Developers extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      // admin_redirect('login');
    }
  }

  public function api_keys()
  {
    $params = func_get_args();
    $method = __FUNCTION__ . '_' . (empty($params) ? 'index' : $params[0]);

    if (method_exists($this, $method)) {
      if (!empty($params[0])) array_shift($params); // Remove original method as param if first param not numeric.
      call_user_func_array([$this, $method], $params);
    }
  }

  private function api_keys_add()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $name         = getPOST('name');
      $token        = getPOST('tokens');
      $scopes       = getPOST('scopes');
      $active       = getPOST('active');
      $expired_date = getPOST('expired_date');

      if (!$name) {
        sendJSON(['error' => 1, 'msg' => 'Name must be specified.']);
      }

      if (!$token) {
        sendJSON(['error' => 1, 'msg' => 'Invalid token. ' . $token]);
      }

      $this->site->addApiKeys([[
        'name'         => $name,
        'token'        => $token,
        'scopes'       => $scopes,
        'active'       => ($expired_date && strtotime($expired_date) > time() ? 1 : ($expired_date ? 0 : $active)),
        'created_date' => date('Y-m-d H:i:s'),
        'expired_date'  => (!empty($expired_date) ? $expired_date : NULL)
      ]]);
      sendJSON(['error' => 0, 'msg' => 'API Key has been added successfully.']);
    }
    $this->load->view($this->theme . 'developers/api_keys/add', $this->data);
  }

  private function api_keys_delete()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $api_id = getPOST('id');
      if ($this->site->deleteApiKey($api_id)) {
        sendJSON(['error' => 0, 'msg' => 'API Key has been deleted successfully.']);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to delete API Key.']);
    }
  }

  private function api_keys_edit()
  {
  }

  private function api_keys_generate()
  {
    if (!$this->Owner) sendJSON(['error' => 1, 'msg' => lang('access_denied')]);

    $token = $this->site->generateApiKeys(64);

    sendJSON(['error' => 0, 'token' => $token]);
  }

  private function api_keys_index()
  {
    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('developers')],
      ['link' => '#', 'page' => lang('api_keys')]
    ];
    $meta = ['page_title' => lang('api_keys'), 'bc' => $bc];

    $this->data = array_merge($this->data, $meta);

    $this->page_construct('developers/api_keys/index', $this->data);
  }

  private function api_keys_getApiKeys()
  {
    $this->load->library('datatable');

    $this->datatable
      ->select('id, name, token, scopes, active, created_date, expired_date')
      ->from('api_keys');

    echo $this->datatable->generate();
  }

  public function findSales()
  {
    $upload = new FileUpload();

    $keys = [
      'id', 'date', 'reference', 'username', 'fullname', 'biller', 'warehouse',
      'price_group', 'customer_group', 'customer_name', 'status', 'grand_total', 'paid', 'balance',
      'payment_status'
    ];

    if ($upload->has('attachment')) {
      $file = fopen($upload->getTempName(), 'r');

      if (!$file) {
        $this->response(400, ['message' => 'Failed to open file.']);
      }

      $rows = [];

      while ($row = fgetcsv($file, 5000, ',')) {
        $rows[] = array_combine($keys, $row);
      }

      fclose($file);

      array_shift($rows);

      $msg = '<ol>';

      foreach ($rows as $row) {
        $sale = Sale::getRow(['id' => $row['id']]);

        if ($sale) {
          $user = User::getRow(['id' => $sale->updated_by]);

          if (date('Y-m', strtotime($sale->date)) != '2022-12') {
            $msg .= "
              <li><b>Beda tanggal:</b>
                <ul>
                  <li><b>Reference: {$sale->reference}</b>
                    <ul>
                      <li>Date: {$sale->date}</li>
                      <li>CSV Date: {$row['date']}</li>
                      <li>Grand Total: {$sale->grand_total}</li>
                      <li>Modified by: {$user->fullname}</li>
                      <li>Modified at: {$sale->updated_at}</li>
                    </ul>
                  </li>
                </ul>
              </li>";
          }

          if (floatval($sale->grand_total) != floatval($row['grand_total'])) {
            $msg .= "
              <li><b>Beda Grand Total:</b>
                <ul>
                  <li><b>Reference: {$sale->reference}</b>
                    <ul>
                      <li>Date: {$sale->date}</li>
                      <li>CSV Date: {$row['date']}</li>
                      <li>Grand Total: {$sale->grand_total}</li>
                      <li>CSV Grand Total: {$row['grand_total']}</li>
                      <li>Modified by: {$user->fullname}</li>
                      <li>Modified at: {$sale->updated_at}</li>
                    </ul>
                  </li>
                </ul>
              </li>";
          }
        } else {
          $msg .= "
            <li><b>Terhapus:</b>
              <ul>
                <li><b>Reference: {$row['reference']}</b>
                  <ul>
                    <li>CSV Date: {$row['date']}</li>
                    <li>CSV Grand Total: {$row['grand_total']}</li>
                  </ul>
                </li>
              </ul>
            </li>";
        }
      }

      $msg .= '</ol>';

      $this->response(200, ['data' => $msg]);
    }

    $this->response(400, ['message' => 'Failed']);
  }

  public function index()
  {
  }

  public function ocr()
  {
    if ($this->requestMethod == 'POST') {
      $uploader = new FileUpload();

      if ($uploader->has('attachment')) {
        if ($uploader->getSize('mb') > 2) {
          $this->response(400, ['message' => 'Size cannot exceed more than 2MB.']);
        }

        $text = ocr($uploader->getTempName());

        if ($text) {
          $data = '<pre>' . print_r($text, TRUE) . '</pre>';

          $this->response(200, ['data' => $data, 'message' => 'OCR scan success.']);
        }

        $this->response(400, ['message' => getLastError()]);
      }
    }
  }

  public function sendWA()
  {
    if ($this->requestMethod == 'POST') {
      $phone    = getPOST('phone');
      $server   = getPOST('server');
      $deviceId = getPOST('deviceid');
      $apiKey   = getPOST('apikey');
      $message  = getPOST('message');

      $data = [];
      $url  = '';

      if ($message) {
        // $message = preg_replace('\<div\>|\<\/div\>', '', $message);
      }

      if ($server == 'watsap') {
        $url = 'https://api.watsap.id/send-message';
        $data['id_device'] = $deviceId;
        $data['api-key']   = $apiKey;
        $data['no_hp']     = $phone;
        $data['pesan']     = $message;

        $data = json_encode($data);
      } else if ($server == 'whacenter') {
        $url = 'https://app.whacenter.com/api/send';
        $data['device_id'] = $deviceId;
        $data['number']    = $phone;
        $data['message']   = $message;
      } else if ($server == 'jobs') {
        if ($insertId = $this->site->addWAJob(['phone' => $phone, 'message' => $message])) {
          $this->response(200, ['message' => "Message has been queued with ID {$insertId}."]);
        }
        $this->response(400, ['message' => getLastError()]);
      }

      $curl = curl_init();

      curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HEADER => FALSE,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_URL => $url
      ]);

      $res = curl_exec($curl);

      $errMsg = curl_error($curl);

      curl_close($curl);

      $json = json_decode($res);

      if ($json) {
        if ($json->status) {
          $this->response(200, ['message' => "Message has been sent by {$server}.", 'data' => $json]);
        }
        $this->response(400, ['message' => 'Failed to send message.', 'data' => $json]);
      }

      $this->response(400, ['message' => 'JSON cannot be decoded. Failed to send message', 'data' => $res]);
    }
  }

  public function tools()
  {
    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('developers')],
      ['link' => '#', 'page' => lang('tools')]
    ];
    $meta = ['page_title' => lang('tools'), 'bc' => $bc];

    $this->data = array_merge($this->data, $meta);

    $this->page_construct('developers/tools', $this->data);
  }
}
