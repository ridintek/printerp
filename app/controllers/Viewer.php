<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Viewer extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    $this->load->view('viewer/viewer', $this->data);
  }
}