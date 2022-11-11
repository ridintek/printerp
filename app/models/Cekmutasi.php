<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cekmutasi extends MY_Model {
  public $api_keys;

  public function __construct () {
    parent::__construct();

    // Ganti API Key sesuai akun mutasibank.
    $this->api_keys = $this->getApiKeys();
    $this->rdlog->setFileName('cekmutasi');
  }

  public function getApiKeys () {
    $q = $this->db->get('mutasibank');
    if ($q->num_rows() > 0) {
      $row = $q->row();
      $api_keys = ($row->active ? json_decode($row->api_keys, TRUE) : NULL);
      return $api_keys;
    }
    return NULL;
  }

  public function getApiSignature()
  {
    return 'W2BVjOJCPZDNLtduxV3rFnEwMFMbOVHX';
  }

  public function validate ($cm_response) {
    $apiSignature = ($_SERVER['HTTP_API_SIGNATURE'] ?? NULL);
    $validated = FALSE;

    // Mulai manipulasi data.
    if ( ! empty($cm_response)) {
      foreach ($this->api_keys as $api_key) {
        if ($api_key == $apiSignature) {
          $validated = TRUE;
        }
      }

      $this->rdlog->info($cm_response);

      if ($validated) {
        if ($valid = $this->site->validatePaymentValidation($cm_response)) {
          $this->rdlog->info(sprintf('VALIDATED %dx', $valid));
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}