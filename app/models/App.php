<?php declare(strict_types=1);

/**
 * @deprecated 2022-07-14 13:54:37
 */
class App extends MY_Model
{
  protected $apiEndPoint = 'https://api.indoprinting.co.id/v1/{method}';
  protected $apiToken    = 'R3v15lT3R0zY4b0Z81A12nAm84HBAn74kK3R7AaN';

  /**
   * Call PrintERP API
   *
   */
  protected function callAPI(string $method, string $api, $data = [])
  {
    $httpHeader = [
      "Authorization: Bearer {$this->apiToken}"
    ];

    $method = strtoupper($method);

    $curlOpts = [
      CURLOPT_CUSTOMREQUEST  => $method,
      CURLOPT_HTTPHEADER     => $httpHeader,
      CURLOPT_RETURNTRANSFER => TRUE
    ];

    $url = str_replace('{method}', $api, $this->apiEndPoint);

    if ($method == 'GET') {
      $url .= '?' . http_build_query($data);
    } else {
      $curlOpts[CURLOPT_POSTFIELDS] = $data;
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, $curlOpts);

    $res = curl_exec($curl);

    if (!$res) {
      setLastError(curl_error($curl));
      return FALSE;
    }

    curl_close($curl);

    return $res;
  }

  public function getWarehouse($clause = [])
  {
    if ($rows = $this->getWarehouses($clause)) {
      return $rows[0];
    }
    return NULL;
  }

  public function getWarehouses($clause = [])
  {
    $res = $this->callAPI('GET', 'warehouses', $clause);

    $json = json_decode($res);

    if ($json && isset($json->data)) {
      return $json->data;
    }

    return [];
  }
}