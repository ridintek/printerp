<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mutasibank extends MY_Model {
  public $api_keys;

  public function __construct () {
    parent::__construct();

    // Ganti API Key sesuai akun mutasibank.
    $this->api_keys = $this->getApiKeys();
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

  public function validate ($mb_response) {
    $validatedAPI = TRUE;
    // Mulai manipulasi data.
    if ( ! empty($mb_response)) {
      // foreach ($this->api_keys as $api_key) {
      //   if ($api_key == $mb_response->api_key) {
      //     $validated = TRUE;
      //   }
      // }

      dbglog('mutasibank', $mb_response);

      if ($validatedAPI) {
        if ($valid = $this->site->validatePaymentValidation($mb_response)) {
          dbglog('mutasibank', sprintf('VALIDATED %dx', $valid));
          return TRUE;
        }
      } else {
        dbglog('mutasibank', "Invalid Api Key.");
      }
    }

    return FALSE;
  }
}