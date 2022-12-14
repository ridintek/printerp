<?php

use App\Http\Middleware\Authenticate;

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->lang->admin_load('auth', $this->Settings->user_language);
    $this->load->library('form_validation');
    $this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));
    // $this->load->admin_model('auth_model');
    $this->load->library('ion_auth');

    $this->digital_upload_path = 'files/';
    $this->upload_path         = 'assets/uploads/';
    $this->thumbs_path         = 'assets/uploads/thumbs/';
    $this->image_types         = 'gif|jpg|jpeg|png|tif';
    $this->digital_file_types  = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
    $this->allowed_file_size   = '1024';
  }

  public function _get_csrf_nonce()
  {
    $this->load->helper('string');
    $key   = random_string('alnum', 8);
    $value = random_string('alnum', 20);
    $this->session->set_flashdata('csrfkey', $key);
    $this->session->set_flashdata('csrfvalue', $value);

    return [$key => $value];
  }

  public function _render_page($view, $data = null, $render = false)
  {
    $this->viewdata = (empty($data)) ? $this->data : $data;
    $view_html      = $this->load->view('header', $this->viewdata, $render);
    $view_html .= $this->load->view($view, $this->viewdata, $render);
    $view_html = $this->load->view('footer', $this->viewdata, $render);

    if (!$render) {
      return $view_html;
    }
  }

  public function _valid_csrf_nonce()
  {
    if (
      getPOST($this->session->flashdata('csrfkey')) !== false && getPOST($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue')
    ) {
      return true;
    }
    return false;
  }

  public function activate($id, $code = false)
  {
    if ($code !== false) {
      $activation = $this->ion_auth->activate($id, $code);
    } elseif ($this->Owner || $this->Admin) {
      $activation = $this->ion_auth->activate($id);
    }

    if ($activation) {
      $this->session->set_flashdata('message', $this->ion_auth->messages());
      if ($this->Owner || $this->Admin) {
        redirect($_SERVER['HTTP_REFERER']);
      } else {
        admin_redirect('auth/login');
      }
    } else {
      $this->session->set_flashdata('error', $this->ion_auth->errors());
      admin_redirect('forgot_password');
    }
  }

  public function captcha_check($cap)
  {
    $expiration = time() - 300; // 5 minutes limit
    $this->db->delete('captcha', ['captcha_time <' => $expiration]);

    $this->db->select('COUNT(*) AS count')
      ->where('word', $cap)
      ->where('ip_address', $this->input->ip_address())
      ->where('captcha_time >', $expiration);

    if ($this->db->count_all_results('captcha')) {
      return true;
    }
    $this->form_validation->set_message('captcha_check', lang('captcha_wrong'));
    return false;
  }

  public function change_password()
  {
    if (!$this->ion_auth->logged_in()) {
      admin_redirect('login');
    }
    $this->form_validation->set_rules('old_password', lang('old_password'), 'required');
    $this->form_validation->set_rules('new_password', lang('new_password'), 'required|min_length[8]|max_length[25]');
    $this->form_validation->set_rules('new_password_confirm', lang('confirm_password'), 'required|matches[new_password]');

    $user = $this->ion_auth->user()->row();

    if ($this->form_validation->run() == false) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('auth/profile/' . $user->id . '/#cpassword');
    } else {
      $identity = XSession::get($this->config->item('identity', 'ion_auth'));

      $change = $this->ion_auth->change_password($identity, getPOST('old_password'), getPOST('new_password'));

      if ($change) {
        $this->session->set_flashdata('message', $this->ion_auth->messages());
        $this->logout();
      } else {
        $this->session->set_flashdata('error', $this->ion_auth->errors());
        admin_redirect('auth/profile/' . $user->id . '/#cpassword');
      }
    }
  }

  public function create_user()
  {
    if (!$this->Owner && !$this->Admin) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->data['title'] = 'Create User';
    $this->form_validation->set_rules('username', lang('username'), 'trim|is_unique[users.username]');
    $this->form_validation->set_rules('email', lang('email'), 'trim|is_unique[users.email]');
    $this->form_validation->set_rules('status', lang('status'), 'trim|required');
    $this->form_validation->set_rules('group', lang('group'), 'trim|required');

    if ($this->form_validation->run() == true) {
      $username = strtolower(getPOST('username'));
      $email    = strtolower(getPOST('email'));
      $password = getPOST('password');
      $notify   = getPOST('notify');

      $additional_data = [
        'fullname'       => getPOST('fullname'),
        'company'        => getPOST('company'),
        'phone'          => getPOST('phone'),
        'gender'         => getPOST('gender'),
        'group_id'       => getPOST('group') ? getPOST('group') : '3',
        'biller_id'      => getPOST('biller'),
        'warehouse_id'   => getPOST('warehouse'),
        'view_right'     => getPOST('view_right'),
        'edit_right'     => getPOST('edit_right'),
        'allow_discount' => getPOST('allow_discount'),
      ];
      $active = getPOST('status');
    }
    if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $active, $notify)) {
      $this->session->set_flashdata('message', $this->ion_auth->messages());
      admin_redirect('auth/users');
    } else {
      $this->data['error']      = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('error')));
      $this->data['groups']     = $this->ion_auth->groups()->result_array();
      $this->data['billers']    = $this->site->getAllBillers();
      $this->data['warehouses'] = $this->site->getAllWarehouses();
      $bc                       = [['link' => admin_url('home'), 'page' => lang('home')], ['link' => admin_url('auth/users'), 'page' => lang('users')], ['link' => '#', 'page' => lang('create_user')]];
      $meta                     = ['page_title' => lang('users'), 'bc' => $bc];

      $this->data = array_merge($this->data, $meta);

      $this->page_construct('auth/create_user', $this->data);
    }
  }

  public function deactivate($id = null)
  {
    $this->sma->checkPermissions('users', true);
    $id = $this->config->item('use_mongodb', 'ion_auth') ? (string)$id : (int)$id;
    $this->form_validation->set_rules('confirm', lang('confirm'), 'required');

    if ($this->form_validation->run() == false) {
      if (getPOST('deactivate')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER']);
      } else {
        $this->data['csrf']     = $this->_get_csrf_nonce();
        $this->data['user']     = $this->ion_auth->user($id)->row();
        $this->load->view($this->theme . 'auth/deactivate_user', $this->data);
      }
    } else {
      if (getPOST('confirm') == 'yes') {
        if ($id != getPOST('id')) {
          show_error(lang('error_csrf'));
        }

        if ($this->ion_auth->logged_in() && ($this->Owner || $this->Admin)) {
          $this->ion_auth->deactivate($id);
          $this->session->set_flashdata('message', $this->ion_auth->messages());
        }
      }

      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function delete_user($id = null)
  {
    if (!$this->isAdmin) {
      sendJSON(['success' => 0, 'message' => lang('access_denied')]);
    }

    if (User::delete(['id' => $id])) {
      sendJSON(['success' => 1, 'message' => 'User has been deleted successfully.']);
    }
    sendJSON(['success' => 0, 'message' => 'Failed to delete user.']);
  }

  public function delete_avatar($id = null, $avatar = null)
  {
    if (!$this->ion_auth->logged_in() || (!$this->Owner && !$this->Admin) && $id != XSession::get('user_id')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . $_SERVER['HTTP_REFERER'] . "'; }, 0);</script>");
      redirect($_SERVER['HTTP_REFERER']);
    } else {
      unlink('assets/uploads/avatars/' . $avatar);
      unlink('assets/uploads/avatars/thumbs/' . $avatar);
      if ($id == XSession::get('user_id')) {
        $this->session->unset_userdata('avatar');
      }
      $this->db->update('user', ['avatar' => null], ['id' => $id]);
      $this->session->set_flashdata('message', lang('avatar_deleted'));
      die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . $_SERVER['HTTP_REFERER'] . "'; }, 0);</script>");
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function edit_user($id = null)
  {
    if (getPOST('id')) {
      $id = getPOST('id');
    }
    $this->data['title'] = lang('edit_user');

    if (!$this->loggedIn || (!$this->Admin && !$this->Owner) && $id != XSession::get('user_id')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $user = $this->ion_auth->user($id)->row();

    if ($user->username != getPOST('username')) {
      $this->form_validation->set_rules('username', lang('username'), 'trim|is_unique[users.username]');
    }
    if ($user->email != getPOST('email')) {
      $this->form_validation->set_rules('email', lang('email'), 'trim|is_unique[users.email]');
    }

    if ($this->form_validation->run() === true) {
      $userJS = getJSON($user->json_data);

      if ($this->Owner || $this->Admin) {
        if ($id == XSession::get('user_id')) {
          $userJS->acc_no = getPOST('acc_no');

          $data = [
            'fullname'   => getPOST('fullname'),
            'company'    => getPOST('company'),
            'email'      => getPOST('email'),
            'phone'      => getPOST('phone'),
            'gender'     => getPOST('gender'),
            'json_data'  => json_encode($userJS)
          ];
        } else {
          $user_perms = getPOST('user_permissions');
          $permissions = [];

          if ($user_perms) {
            foreach ($user_perms as $perm) {
              $permissions[$perm] = 1;
            }
          }

          $userJS->acc_no = getPOST('acc_no');
          $userJS->biller_access = getPOST('biller_access');
          $userJS->permissions = $permissions;
          $userJS->so_cycle = getPOST('so_cycle');

          $data = [
            'fullname'       => getPOST('fullname'),
            'company'        => getPOST('company'),
            'username'       => getPOST('username'),
            'email'          => getPOST('email'),
            'phone'          => getPOST('phone'),
            'gender'         => getPOST('gender'),
            'active'         => getPOST('status'),
            'group_id'       => getPOST('group'),
            'biller_id'      => getPOST('biller') ? getPOST('biller') : null,
            'warehouse_id'   => getPOST('warehouse') ? getPOST('warehouse') : null,
            'view_right'     => getPOST('view_right'),
            'edit_right'     => getPOST('edit_right'),
            'allow_discount' => getPOST('allow_discount'),
            'json_data'      => json_encode($userJS)
          ];
        }
      } else {
        $data = [
          'fullname'   => getPOST('fullname'),
          'company'    => getPOST('company'),
          'phone'      => getPOST('phone'),
          'gender'     => getPOST('gender')
        ];
      }

      if ($this->Owner || $this->Admin) {
        if (getPOST('password')) {
          $this->form_validation->set_rules('password', lang('edit_user_validation_password_label'), 'required|min_length[8]|max_length[25]|matches[password_confirm]');
          $this->form_validation->set_rules('password_confirm', lang('edit_user_validation_password_confirm_label'), 'required');

          $data['password'] = getPOST('password');
        }
      }
    }

    if ($this->form_validation->run() === true) {
      if ($this->ion_auth->update($user->id, $data)) {
        $this->session->set_flashdata('message', lang('user_updated'));
        redirect(admin_url('users'));
      } else {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function forgot_password()
  {
    $this->form_validation->set_rules('forgot_email', lang('email_address'), 'required|valid_email');

    if ($this->form_validation->run() == false) {
      $error = validation_errors() ? validation_errors() : $this->session->flashdata('error');
      $this->session->set_flashdata('error', $error);
      admin_redirect('login#forgot_password');
    } else {
      $identity = $this->ion_auth->where('email', strtolower(getPOST('forgot_email')))->users()->row();
      if (empty($identity)) {
        $this->ion_auth->set_message('forgot_password_email_not_found');
        $this->session->set_flashdata('error', $this->ion_auth->messages());
        admin_redirect('login#forgot_password');
      }

      $forgotten = $this->ion_auth->forgotten_password($identity->email);

      if ($forgotten) {
        $this->session->set_flashdata('message', $this->ion_auth->messages());
        admin_redirect('login#forgot_password');
      } else {
        $this->session->set_flashdata('error', $this->ion_auth->errors());
        admin_redirect('login#forgot_password');
      }
    }
  }

  public function getUserLogins($id = null)
  {
    // if (!$this->ion_auth->in_group(['owner', 'admin'])) {
    //   $this->session->set_flashdata('warning', lang('access_denied'));
    //   admin_redirect('welcome');
    // }
    // $this->load->library('datatables');
    // $this->datatables
    //   ->select('login, ip_address, time')
    //   ->from('user_logins')
    //   ->where('user_id', $id);

    // echo $this->datatables->generate();
  }

  public function getUsers()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('users-edit')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      $this->sma->md();
    }

    $this->load->library('datatables');
    $this->datatables
      ->select('users.id as id, username, users.fullname, billers.name as biller_name,
        warehouses.name as warehouse_name, users.phone as phone, users.email as email,
        users.company as company, groups.name, users.active')
      ->from('users')
      ->join('groups', 'groups.id = users.group_id', 'left')
      ->join('billers', 'billers.id = users.biller_id', 'left')
      ->join('warehouses', 'warehouses.id = users.warehouse_id', 'left')
      ->group_by('users.id')
      ->edit_column('users.active', '$1__$2', 'users.active, id')
      ->add_column('Actions', "
        <div class=\"text-center\">
          <a href='" . admin_url('auth/profile/$1') . "' class='tip' title='" . lang('edit_user') . "'>
            <i class=\"fad fa-edit\"></i>
          </a>
          <a href=\"" . admin_url('auth/delete_user/$1') . "\" data-action=\"confirm\">
            <i class=\"fad fa-trash\"></i>
          </a>
        </div>", 'id');

    echo $this->datatables->generate();
  }

  public function import_users()
  { // New Added
    $this->sma->checkPermissions('csv');
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $update_pass = (!empty(getPOST('update_pass')) ? TRUE : FALSE);
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = 'csv';
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('users');
        }

        $csv = $this->upload->file_name;

        $arrResult = [];
        $handle    = fopen($this->upload_import_path . $csv, 'r');
        if ($handle) {
          while (($row = fgetcsv($handle, 5000, ',')) !== false) {
            $arrResult[] = $row;
          }
          fclose($handle);
        }
        unset($csv);
        $csvs = [];
        $header_id = array_shift($arrResult);
        $title     = array_shift($arrResult);
        $updated = 0;
        $items   = [];
        $keys    = [
          'no', 'use', 'username', 'password', 'email', 'fullname', 'company', 'phone',
          'gender', 'group', 'warehouse', 'biller', 'view_right', 'edit_right', 'active', 'note'
        ];
        if ($header_id[0] != 'USRACN') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('users');
        }
        foreach ($arrResult as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }
        foreach ($csvs as $csv) {
          if ($csv['use'] != 1) continue; // Ignore if not 1.
          $group     = $this->site->getGroupByName(strtolower(rd_trim($csv['group'])));
          $warehouse = (!empty($csv['warehouse']) ? $this->site->getWarehouseByName(strtolower(rd_trim($csv['warehouse']))) : NULL);
          $biller    = (!empty($csv['biller']) ? $this->site->getBillerByName(strtolower(rd_trim($csv['biller']))) : NULL);

          $user = [
            'username' => strtolower(rd_trim($csv['username'])),
            'password' => rd_trim($csv['password']),
            'email'    => strtolower(rd_trim($csv['email'])),
            'active'   => rd_trim($csv['active']),
            'notify'   => 0, // Send an email to user. Disabled.
            'data'     => [
              'fullname'       => rd_trim($csv['fullname']),
              'company'        => rd_trim($csv['company']),
              'phone'          => rd_trim($csv['phone']),
              'gender'         => strtolower(rd_trim($csv['gender'])),
              'group_id'       => ($group ? $group->id : NULL),
              'warehouse_id'   => ($warehouse ? $warehouse->id : NULL),
              'biller_id'      => ($biller ? $biller->id : NULL),
              'view_right'     => rd_trim($csv['view_right']),
              'edit_right'     => rd_trim($csv['edit_right']),
              'allow_discount' => 0
            ]
          ];

          if (!empty($csv['password']) && $update_pass) $user['data']['password'] = $csv['password'];

          if ($user) {
            $users[] = $user;
          }
        } // foreach
      }
    }

    if ($this->form_validation->run() == true && !empty($users)) {
      $add_count = 0;
      $update_count = 0;
      foreach ($users as $user) {
        $usr = $this->site->getUserByUsername($user['username']);
        if ($usr && $user) {
          if (!$this->ion_auth->update($usr->id, $user['data'])) {
            $this->session->set_flashdata('error', $this->ion_auth->errors());
            admin_redirect('users');
          }
          $update_count++;
        } else {
          if (!$this->ion_auth->register($user['username'], $user['password'], $user['email'], $user['data'], $user['active'], $user['notify'])) {
            $this->session->set_flashdata('error', $this->ion_auth->errors());
            admin_redirect('users');
          }
          $add_count++;
        }
      }

      $this->session->set_flashdata('message', sprintf(lang('csv_users_imported'), $add_count, $update_count));
      admin_redirect('users');
    } else {
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['userfile'] = [
        'name'  => 'userfile',
        'id'    => 'userfile',
        'type'  => 'text',
        'value' => $this->form_validation->set_value('userfile'),
      ];

      $bc   = [
        ['link' => base_url(), 'page' => lang('home')],
        ['link' => admin_url('users'), 'page' => lang('users')],
        ['link' => '#', 'page' => lang('import_users')]
      ];
      $meta = ['page_title' => lang('import_users'), 'bc' => $bc];
      $this->load->view($this->theme . 'auth/import_users', $this->data);
    }
  }

  public function index()
  {
    if (!$this->loggedIn) {
      admin_redirect('login');
    } else {
      $this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  /**
   * New login method 2022-12-09 16:57:39
   */
  public function login()
  {
    $identity = (getPOST('identity') ?? '');
    $password = (getPOST('password') ?? '');
    $remember = ((getPOST('remember') == 1 ? TRUE : FALSE) ?? FALSE);

    if ($this->requestMethod == 'POST') {
      if (Authentication::login($identity, $password, $remember)) {
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
      }
    } else if (XSession::has('user_id')) {
      admin_redirect();
    }

    $this->load->view($this->theme . 'auth/login', $this->data);
  }

  public function login_old()
  {
    if ($this->loggedIn) {
      $this->session->set_flashdata('error', $this->session->flashdata('error'));
      admin_redirect('welcome');
    }
    $this->data['title'] = lang('login');

    if ($this->form_validation->run() == true) {
      $remember = getPOST('remember');

      // if ($this->auth_model->login(getPOST('identity'), getPOST('password'), $remember)) {
      //   $this->session->set_flashdata('message', $this->ion_auth->messages());
      //   admin_redirect($_SERVER['HTTP_REFERER']);
      // } else {
      //   $this->session->set_flashdata('error', $this->ion_auth->errors());
      //   admin_redirect($_SERVER['HTTP_REFERER']);
      // }
      admin_redirect();
    } else {
      $this->data['error']   = (validation_errors() ? validation_errors() : '');
      // $this->data['error']   = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
      $this->data['message'] = $this->session->flashdata('message');

      $this->data['identity'] = [
        'name' => 'identity',
        'id'                          => 'identity',
        'type'                        => 'text',
        'class'                       => 'form-control',
        'placeholder'                 => lang('email'),
        'value'                       => $this->form_validation->set_value('identity'),
      ];
      $this->data['password'] = [
        'name' => 'password',
        'id'                          => 'password',
        'type'                        => 'password',
        'class'                       => 'form-control',
        'required'                    => 'required',
        'placeholder'                 => lang('password'),
      ];
      $this->data['allow_reg'] = $this->Settings->allow_reg;
      $this->data['url'] = getGET('url');
      $this->data['warehouses'] = $this->site->getAllWarehouses();
      $this->load->view($this->theme . 'auth/login', $this->data);
    }
  }

  public function logout($m = null)
  {
    Authentication::logout();

    admin_redirect('login/' . $m);
  }

  public function profile($id = null)
  {
    if (!$this->ion_auth->logged_in() || (!$this->Owner && !$this->Admin) && $id != XSession::get('user_id')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER'] ?? 'admin');
    }
    if (!$id || empty($id)) {
      admin_redirect('auth');
    }

    $this->data['title'] = lang('profile');

    $user                     = $this->ion_auth->user($id)->row();
    $groups                   = $this->ion_auth->groups()->result_array();
    $this->data['csrf']       = $this->_get_csrf_nonce();
    $this->data['user']       = $user;
    $this->data['json_data']  = json_decode($user->json_data);
    $this->data['groups']     = $groups;
    $this->data['billers']    = $this->site->getAllBillers();
    $this->data['warehouses'] = $this->site->getAllWarehouses();

    $this->data['error']    = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['password'] = [
      'name'  => 'password',
      'id'    => 'password',
      'class' => 'form-control',
      'type'  => 'password',
      'value' => '',
    ];
    $this->data['password_confirm'] = [
      'name'  => 'password_confirm',
      'id'    => 'password_confirm',
      'class' => 'form-control',
      'type'  => 'password',
      'value' => '',
    ];
    $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
    $this->data['old_password']        = [
      'name'  => 'old',
      'id'    => 'old',
      'class' => 'form-control',
      'type'  => 'password',
    ];
    $this->data['new_password'] = [
      'name'    => 'new',
      'id'      => 'new',
      'type'    => 'password',
      'class'   => 'form-control',
      'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
    ];
    $this->data['new_password_confirm'] = [
      'name'    => 'new_confirm',
      'id'      => 'new_confirm',
      'type'    => 'password',
      'class'   => 'form-control',
      'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
    ];
    $this->data['user_id'] = [
      'name'  => 'user_id',
      'id'    => 'user_id',
      'type'  => 'hidden',
      'value' => $user->id,
    ];

    $this->data['id'] = $id;

    $this->data['page_title'] = lang('profile');
    $this->data['bc'] = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('auth/users'), 'page' => lang('users')], ['link' => '#', 'page' => lang('profile')]];

    $this->page_construct('auth/profile', $this->data);
  }

  public function register()
  {
    $this->data['title'] = 'Register';
    if (!$this->allow_reg) {
      $this->session->set_flashdata('error', lang('registration_is_disabled'));
      admin_redirect('login');
    }

    $this->form_validation->set_message('is_unique', lang('account_exists'));
    $this->form_validation->set_rules('fullname', lang('fullname'), 'required');
    $this->form_validation->set_rules('email', lang('email_address'), 'required|valid_email|is_unique[users.email]');
    $this->form_validation->set_rules('usernam', lang('usernam'), 'required|is_unique[users.username]');
    $this->form_validation->set_rules('password', lang('password'), 'required|min_length[8]|max_length[25]|matches[password_confirm]');
    $this->form_validation->set_rules('password_confirm', lang('confirm_password'), 'required');
    if ($this->Settings->captcha) {
      $this->form_validation->set_rules('captcha', lang('captcha'), 'required|callback_captcha_check');
    }

    if ($this->form_validation->run() == true) {
      $username = strtolower(getPOST('username'));
      $email    = strtolower(getPOST('email'));
      $password = getPOST('password');

      $additional_data = [
        'fullname' => getPOST('fullname'),
        'company'  => getPOST('company'),
        'phone'    => getPOST('phone'),
      ];
    }
    if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data)) {
      $this->session->set_flashdata('message', $this->ion_auth->messages());
      admin_redirect('login');
    } else {
      $this->data['error']  = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('error')));
      $this->data['groups'] = $this->ion_auth->groups()->result_array();

      $this->load->helper('captcha');
      $vals = [
        'img_path'   => './assets/captcha/',
        'img_url'    => admin_url() . 'assets/captcha/',
        'img_width'  => 150,
        'img_height' => 34,
      ];
      $cap     = create_captcha($vals);
      $capdata = [
        'captcha_time' => $cap['time'],
        'ip_address'   => $this->input->ip_address(),
        'word'         => $cap['word'],
      ];

      $query = $this->db->insert_string('captcha', $capdata);
      $this->db->query($query);
      $this->data['image']   = $cap['image'];
      $this->data['captcha'] = [
        'name' => 'captcha',
        'id'                         => 'captcha',
        'type'                       => 'text',
        'class'                      => 'form-control',
        'placeholder'                => lang('type_captcha'),
      ];

      $this->data['last_name'] = [
        'name'     => 'last_name',
        'id'       => 'last_name',
        'type'     => 'text',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('last_name'),
      ];
      $this->data['email'] = [
        'name'     => 'email',
        'id'       => 'email',
        'type'     => 'text',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('email'),
      ];
      $this->data['company'] = [
        'name'     => 'company',
        'id'       => 'company',
        'type'     => 'text',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('company'),
      ];
      $this->data['phone'] = [
        'name'     => 'phone',
        'id'       => 'phone',
        'type'     => 'text',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('phone'),
      ];
      $this->data['password'] = [
        'name'     => 'password',
        'id'       => 'password',
        'type'     => 'password',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('password'),
      ];
      $this->data['password_confirm'] = [
        'name'     => 'password_confirm',
        'id'       => 'password_confirm',
        'type'     => 'password',
        'required' => 'required',
        'class'    => 'form-control',
        'value'    => $this->form_validation->set_value('password_confirm'),
      ];

      $this->load->view('auth/register', $this->data);
    }
  }

  public function reload_captcha()
  {
    $this->load->helper('captcha');
    $vals = [
      'img_path'    => './assets/captcha/',
      'img_url'     => base_url('assets/captcha/'),
      'img_width'   => getGET('width') ? getGET('width') : 150,
      'img_height'  => getGET('height') ? getGET('height') : 34,
      'word_length' => 5,
      'colors'      => ['background' => [255, 255, 255], 'border' => [204, 204, 204], 'text' => [102, 102, 102], 'grid' => [204, 204, 204]],
    ];
    $cap     = create_captcha($vals);
    $capdata = [
      'captcha_time' => $cap['time'],
      'ip_address'   => $this->input->ip_address(),
      'word'         => $cap['word'],
    ];
    $query = $this->db->insert_string('captcha', $capdata);
    $this->db->query($query);
    //$this->data['image'] = $cap['image'];

    echo $cap['image'];
  }

  public function reset_password($code = null)
  {
    if (!$code) {
      show_404();
    }

    $user = $this->ion_auth->forgotten_password_check($code);

    if ($user) {
      $this->form_validation->set_rules('new', lang('password'), 'required|min_length[8]|max_length[25]|matches[new_confirm]');
      $this->form_validation->set_rules('new_confirm', lang('confirm_password'), 'required');

      if ($this->form_validation->run() == false) {
        $this->data['error']               = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['message']             = $this->session->flashdata('message');
        $this->data['title']               = lang('reset_password');
        $this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
        $this->data['new_password']        = [
          'name'                   => 'new',
          'id'                     => 'new',
          'type'                   => 'password',
          'class'                  => 'form-control',
          'pattern'                => '(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}',
          'data-bv-regexp-message' => lang('pasword_hint'),
          'placeholder'            => lang('new_password'),
        ];
        $this->data['new_password_confirm'] = [
          'name'                      => 'new_confirm',
          'id'                        => 'new_confirm',
          'type'                      => 'password',
          'class'                     => 'form-control',
          'data-bv-identical'         => 'true',
          'data-bv-identical-field'   => 'new',
          'data-bv-identical-message' => lang('pw_not_same'),
          'placeholder'               => lang('confirm_password'),
        ];
        $this->data['user_id'] = [
          'name'  => 'user_id',
          'id'    => 'user_id',
          'type'  => 'hidden',
          'value' => $user->id,
        ];
        $this->data['csrf']           = $this->_get_csrf_nonce();
        $this->data['code']           = $code;
        $this->data['identity_label'] = $user->email;
        //render
        $this->load->view($this->theme . 'auth/reset_password', $this->data);
      } else {
        // do we have a valid request?
        if ($user->id != getPOST('user_id')) {
          //something fishy might be up
          $this->ion_auth->clear_forgotten_password_code($code);
          show_error(lang('error_csrf'));
        } else {
          // finally change the password
          $identity = $user->email;

          $change = $this->ion_auth->reset_password($identity, getPOST('new'));

          if ($change) {
            //if the password was successfully changed
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            //$this->logout();
            admin_redirect('login');
          } else {
            $this->session->set_flashdata('error', $this->ion_auth->errors());
            admin_redirect('auth/reset_password/' . $code);
          }
        }
      }
    } else {
      //if the code is invalid then send them back to the forgot password page
      $this->session->set_flashdata('error', $this->ion_auth->errors());
      admin_redirect('login#forgot_password');
    }
  }

  public function suggestions($term = NULL, $limit = 10)
  {
    if (getGET('term')) {
      $term = getGET('term', true);
    }
    if ($id = getGET('id')) {
      $term = [];
      $term['id'] = $id;
    }
    $limit           = getGET('limit', true);
    $rows['results'] = $this->site->getUserSuggestions($term, $limit);
    sendJSON($rows);
  }

  public function test()
  {
    $this->auth->login('ok');
  }

  /**
   * @param null $id
   */
  public function update_avatar($id = null)
  {
    if (getPOST('id')) {
      $id = getPOST('id');
    }

    if (!$this->ion_auth->logged_in() || (!$this->Owner && !$this->Admin) && $id != XSession::get('user_id')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    //validate form input
    $this->form_validation->set_rules('avatar', lang('avatar'), 'trim');

    if ($this->form_validation->run() == true) {
      if ($_FILES['avatar']['size'] > 0) {
        $this->load->library('upload');

        $config['upload_path']   = 'assets/uploads/avatars';
        $config['allowed_types'] = 'gif|jpg|png';
        //$config['max_size'] = '500';
        $config['max_width']    = $this->Settings->iwidth;
        $config['max_height']   = $this->Settings->iheight;
        $config['overwrite']    = false;
        $config['encrypt_name'] = true;
        $config['max_filename'] = 25;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('avatar')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          redirect($_SERVER['HTTP_REFERER']);
        }

        $photo = $this->upload->file_name;

        $this->load->helper('file');
        $this->load->library('image_lib');
        $config['image_library']  = 'gd2';
        $config['source_image']   = 'assets/uploads/avatars/' . $photo;
        $config['new_image']      = 'assets/uploads/avatars/thumbs/' . $photo;
        $config['maintain_ratio'] = true;
        $config['width']          = 150;
        $config['height']         = 150;

        $this->image_lib->clear();
        $this->image_lib->initialize($config);

        if (!$this->image_lib->resize()) {
          echo $this->image_lib->display_errors();
        }
        $user = $this->ion_auth->user($id)->row();
      } else {
        $this->form_validation->set_rules('avatar', lang('avatar'), 'required');
      }
    }

    if ($this->form_validation->run() == true) {
      User::update((int)$id, ['avatar' => $photo]);
      unlink('assets/uploads/avatars/' . $user->avatar);
      unlink('assets/uploads/avatars/thumbs/' . $user->avatar);
      $this->session->set_userdata('avatar', $photo);
      $this->session->set_flashdata('message', lang('avatar_updated'));
      admin_redirect('users');
    } else {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('auth/profile/' . $id);
    }
  }

  public function user_actions()
  {
    if (!$this->Owner && !$this->Admin) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPOST('form_action') == 'delete') {
          if (!$this->Owner && !$this->Admin) {
            $this->session->set_flashdata('warning', lang('access_denied'));
          } else {
            foreach ($_POST['val'] as $id) {
              if ($id != XSession::get('user_id')) {
                User::delete(['id' => $id]);
              }
            }
            $this->session->set_flashdata('message', lang('users_deleted'));
          }
          redirect($_SERVER['HTTP_REFERER']);
        }
      } else {
        $this->session->set_flashdata('error', lang('no_user_selected'));
        redirect($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect($_SERVER['HTTP_REFERER']);
    }
  }

  public function users()
  {
    if (!$this->loggedIn) {
      admin_redirect('login');
    }

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('users')]];
    $meta = ['page_title' => lang('users'), 'bc' => $bc];

    $this->data = array_merge($this->data, $meta);
    $this->page_construct('auth/index', $this->data);
  }
}
