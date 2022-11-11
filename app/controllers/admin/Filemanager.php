<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Filemanager extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
  }

  public function getFiles()
  {
    
  }

  public function index()
  {
    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('file_manager')]
    ];
    $meta = ['page_title' => lang('file_manager'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);
    
    $this->page_construct('filemanager/index', $this->data);
  }
}
