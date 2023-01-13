<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cekmutasi extends MY_Model
{
  public $api_keys;

  public function __construct()
  {
    parent::__construct();
  }

  public function getApiSignature()
  {
    return 'W2BVjOJCPZDNLtduxV3rFnEwMFMbOVHX';
  }

  public function validate($cm_response)
  {
    $apiSignature = ($_SERVER['HTTP_API_SIGNATURE'] ?? NULL);
    $validated = FALSE;

    // Mulai manipulasi data.
    if (!empty($cm_response)) {
      foreach ($this->api_keys as $api_key) {
        if ($api_key == $apiSignature) {
          $validated = TRUE;
        }
      }

      if ($validated) {
        if ($valid = PaymentValidation::validate($cm_response)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}
