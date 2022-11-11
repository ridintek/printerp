<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Viewer_model extends CI_Model {
  public function __construct () {
    parent::__construct();
  }

  public function addGeolocator($data)
  {
    $this->db->trans_start();
    $this->db->insert('geolocator', $data);
    $insertId = $this->db->insert_id();
    $this->db->trans_complete();

    if ($this->db->trans_status()) {
      return $insertId;
    }
    return FALSE;
  }
}