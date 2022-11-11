<?php defined('BASEPATH') or exit('No direct script access allowed');

class Users_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function getUserById ($id) {
    $this->db->where('id=', $id);
    $q = $this->db->get('users');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }
}