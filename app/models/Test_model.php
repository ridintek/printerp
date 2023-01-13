<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Test_model extends MY_Model {
  public function __construct () {
    parent::__construct();
    $this->rdlog->setFileName('Test');
  }

  public function testmsg () {
    $this->rdlog->info('Okbro');
  }
}