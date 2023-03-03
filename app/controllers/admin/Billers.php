<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Billers extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      loginPage();
    }

    if (!$this->Owner) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }
    $this->lang->admin_load('billers', $this->Settings->user_language);
    $this->load->library('form_validation');
  }

  public function add ()
  {
    $this->sma->checkPermissions(false, true);

    $this->form_validation->set_rules('email', $this->lang->line('email_address'), 'is_unique[billers.email]');

    if ($this->form_validation->run('billers/add') == true) {
      $data = [
        'name'      => getPost('name'),
        'email'     => getPost('email'),
        'company'   => getPost('company'),
        'address'   => getPost('address'),
        'city'      => getPost('city'),
        'phone'     => getPost('phone'),
        'logo'      => getPost('logo'),
        'json_data' => json_encode([
          'target'    => filterDecimal(getPost('target')),
          'whatsapp'  => getPost('whatsapp')
        ])
      ];
    } elseif (getPost('add_biller')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('billers');
    }

    if ($this->form_validation->run() == true && $this->site->addBiller($data)) {
      $this->session->set_flashdata('message', $this->lang->line('biller_added'));
      admin_redirect('billers');
    } else {
      $this->data['logos']    = $this->getLogoList();
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'billers/add', $this->data);
    }
  }

  public function biller_actions ()
  {
    if (!$this->Owner && !$this->GP['bulk_actions']) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect_to($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('form_action', lang('form_action'), 'required');

    if ($this->form_validation->run() == true) {
      if (!empty($_POST['val'])) {
        if (getPost('form_action') == 'delete') {
          $this->sma->checkPermissions('delete');
          $error = false;
          foreach ($_POST['val'] as $id) {
            if (!$this->site->deleteBiller($id)) {
              $error = true;
            }
          }
          if ($error) {
            $this->session->set_flashdata('warning', lang('billers_x_deleted_have_sales'));
          } else {
            $this->session->set_flashdata('message', $this->lang->line('billers_deleted'));
          }
          redirect_to($_SERVER['HTTP_REFERER']);
        }
      } else {
        $this->session->set_flashdata('error', $this->lang->line('no_biller_selected'));
        redirect_to($_SERVER['HTTP_REFERER']);
      }
    } else {
      $this->session->set_flashdata('error', validation_errors());
      redirect_to($_SERVER['HTTP_REFERER']);
    }
  }

  public function delete ($id = null)
  {
    $this->sma->checkPermissions(null, true);

    if (getGET('id')) {
      $id = getGET('id');
    }

    if ($this->site->deleteBiller($id)) {
      sendJSON(['error' => 0, 'msg' => lang('biller_deleted')]);
    } else {
      sendJSON(['error' => 1, 'msg' => lang('biller_x_deleted_have_sales')]);
    }
  }

  public function edit ($id = null)
  {
    $this->sma->checkPermissions(false, true);

    if (getGET('id')) {
      $id = getGET('id');
    }

    $biller = $this->site->getBillerByID($id);
    if (getPost('email') != $biller->email) {
      $this->form_validation->set_rules('code', lang('email_address'), 'is_unique[billers.email]');
    }

    if ($this->form_validation->run('billers/add') == true) {
      $data = [
        'name'      => getPost('name'),
        'email'     => getPost('email'),
        'company'   => getPost('company'),
        'address'   => getPost('address'),
        'city'      => getPost('city'),
        'phone'     => getPost('phone'),
        'logo'      => getPost('logo'),
        'json_data' => json_encode([
          'target'    => filterDecimal(getPost('target')),
          'whatsapp'  => getPost('whatsapp')
        ])
      ];
    } elseif (getPost('edit_biller')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('billers');
    }

    if ($this->form_validation->run() == true && $this->site->updateBiller($id, $data)) {
      $this->session->set_flashdata('message', $this->lang->line('biller_updated'));
      admin_redirect('billers');
    } else {
      if (getPost('edit_biller')) {
        $this->session->set_flashdata('error', 'Failed to save');
        admin_redirect('billers');
      }
      $this->data['biller']   = $biller;
      $this->data['billerJS'] = json_decode($biller->json_data);
      $this->data['error']    = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->data['logos']    = $this->getLogoList();
      $this->load->view($this->theme . 'billers/edit', $this->data);
    }
  }

  public function getBiller($id = null)
  {
    $this->sma->checkPermissions('index');

    $row = $this->site->getBillerByID($id);
    sendJSON([['id' => $row->id, 'text' => $row->company]]);
  }

  public function getBillers()
  {
    $this->sma->checkPermissions('index');

    $this->load->library('datatables');
    $this->datatables
      ->select('id, company, name, phone, email, city')
      ->from('billers')
      ->add_column('Actions', "<div class=\"text-center\"><a class=\"tip\" title='" . $this->lang->line('edit_biller') . "' href='" . admin_url('billers/edit/$1') . "' data-toggle='modal' data-target='#myModal'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . $this->lang->line('delete_biller') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('billers/delete/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');
    //->unset_column('id');
    echo $this->datatables->generate();
  }

  public function getLogoList()
  {
    $this->load->helper('directory');
    $dirname = 'assets/uploads/logos';
    $ext     = ['jpg', 'png', 'jpeg', 'gif'];
    $files   = [];
    if ($handle = opendir($dirname)) {
      while (false !== ($file = readdir($handle))) {
        for ($i = 0; $i < sizeof($ext); $i++) {
          if (stristr($file, '.' . $ext[$i])) { //NOT case sensitive: OK with JpeG, JPG, ecc.
            $files[] = $file;
          }
        }
      }
      closedir($handle);
    }
    sort($files);
    return $files;
  }

  public function import () { // Added
    $this->sma->checkPermissions('csv', TRUE);
    $this->form_validation->set_rules('csv_file', lang('upload_file'), 'xss_clean');

    if ($this->form_validation->run() == true) {
      if (isset($_FILES['csv_file'])) {
        $this->load->library('upload');
        $config['upload_path']   = $this->upload_import_path;
        $config['allowed_types'] = $this->upload_csv_type;
        $config['max_size']      = $this->upload_allowed_size;
        $config['overwrite']     = true;
        $config['encrypt_name']  = true;
        $config['max_filename']  = 25;
        $this->upload->initialize($config);

        if ( ! $this->upload->do_upload('csv_file')) {
          $error = $this->upload->display_errors();
          $this->session->set_flashdata('error', $error);
          admin_redirect('billers');
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
          'no', 'use', 'company', 'name', 'email', 'phone', 'whatsapp', 'address', 'city', 'target'
        ];

        if ($header_id[0] != 'BILR') {
          $this->session->set_flashdata('error', 'File format is invalid.');
          admin_redirect('billers');
        }
        foreach ($arrResult as $csv_data) {
          $csvs[] = array_combine($keys, $csv_data);
        }
        foreach ($csvs as $csv) {
          if ($csv['use'] == 0) continue;
          $data_biller = [
            'company'   => $csv['company'],
            'name'      => $csv['name'],
            'email'     => $csv['email'],
            'phone'     => $csv['phone'],
            'address'   => $csv['address'],
            'logo'      => 'logo-indoprinting-300.png',
            'json_data' => json_encode([
              'target'    => $csv['target'],
              'whatsapp'  => $csv['whatsapp']
            ])
          ];

          if ($data_biller) {
            $billers[] = $data_biller;
          }
        } // foreach
      }
    } else if (getPost('import')) {
      $this->session->set_flashdata('error', 'E1: ' . validation_errors());
      admin_redirect('billers');
    }

    if ($this->form_validation->run() == true && ! empty($billers)) {
      $added = 0; $updated = 0;
      foreach ($billers as $bill) {
        $biller = $this->site->getBillerByName($bill['name']); // Find biller by name
        if ($biller) { // If present, updated it
          if ($this->site->updateBiller($biller->id, $bill)) {
            $updated++;
          }
        } else { // Else add biller.
          if ($this->site->addBiller($bill)) {
            $added++;
          }
        }
      }

      $this->session->set_flashdata('message', sprintf(lang('csv_billers_imported'), $added, $updated));
      admin_redirect('billers');
    } else {
      if (getPost('import')) {
        $this->session->set_flashdata('error', 'E2: ' . validation_errors());
        admin_redirect('billers');
      }
      $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
      $this->load->view($this->theme . 'billers/import', $this->data);
    }
  }

  public function index ($action = null)
  {
    $this->sma->checkPermissions();

    $this->data['error']  = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $this->data['action'] = $action;

    $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('billers')]];
    $meta = ['page_title' => lang('billers'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('billers/index', $this->data);
  }

  public function suggestions($term = null, $limit = null)
  {
    $this->sma->checkPermissions('index');

    if (getGET('term')) {
      $term = getGET('term', true);
    }
    $limit           = getGET('limit', true);
    $rows['results'] = $this->site->getBillerSuggestions($term, $limit);
    sendJSON($rows);
  }
}
