<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mutasibank extends MY_Model
{
  public $api_keys;

  public function __construct()
  {
    parent::__construct();
  }

  public function validate($mb_response)
  {
    $validatedAPI = TRUE;
    // Mulai manipulasi data.
    if (!empty($mb_response)) {

      dbglog('mutasibank', $mb_response);

      if ($valid = PaymentValidation::validate($mb_response)) {
        dbglog('mutasibank', sprintf('VALIDATED %dx', $valid));
        return TRUE;
      }
    }

    return FALSE;
  }
}
