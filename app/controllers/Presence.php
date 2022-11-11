<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Presence extends MY_Controller {
  public function __construct()
  {
    parent::__construct();
  }

  public function camera()
  {
    $this->load->view($this->theme . 'presence/camera');
  }

  public function index()
  {
    $this->camera();
  }

  public function register()
  {
    $this->load->view($this->theme . 'presence/register');
  }

  public function selector()
  {
    $this->load->view($this->theme . 'presence/selector');
  }
}