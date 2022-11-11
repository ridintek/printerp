<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Payments extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    show_404();
  }
}
