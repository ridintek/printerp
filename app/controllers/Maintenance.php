<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Maintenance extends MY_Controller // From MY_Shop_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index () {
    $meta = [
      'start_date' => $this->maintenance_start_date,
      'end_date' => $this->maintenance_end_date
    ];
    
    $this->load->view('maintenance/index', $meta);
  }
}
