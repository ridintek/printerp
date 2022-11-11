<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Notifications extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      admin_redirect('login');
    }

    if (!$this->Owner && !$this->Admin && !getPermission('notify-index')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }
    $this->lang->admin_load('notifications', $this->Settings->user_language);
    $this->load->library('form_validation');
    $this->load->admin_model('cmt_model');
  }

  public function activate($id)
  {
    $this->form_validation->set_rules('confirm', lang('confirm'), 'required');

    $confirmed = ($this->input->post('confirm') == 1 ? TRUE : FALSE);

    if ($this->form_validation->run() == TRUE && $confirmed) {
      if ($this->site->notificationActivate($id)) {
        sendJSON(['error' => 0, 'msg' => lang('bank_activated')]);
      } else {
        sendJSON(['error' => 1, 'msg' => lang('bank_activate_failed')]);
      }
    } else if ($this->input->post('activate')) {
      sendJSON(['error' => 1, 'msg' => validation_errors()]);
    }

    $this->data['notification'] = $this->site->getNotificationByID($id);

    $this->load->view($this->theme . 'notifications/activate', $this->data);
  }

  public function deactivate($id)
  {
    $this->form_validation->set_rules('confirm', lang('confirm'), 'required');

    $confirmed = ($this->input->post('confirm') == 1 ? TRUE : FALSE);

    if ($this->form_validation->run() == TRUE && $confirmed) {
      if ($this->site->notificationDeactivate($id)) {
        sendJSON(['error' => 0, 'msg' => lang('bank_deactivated')]);
      } else {
        sendJSON(['error' => 1, 'msg' => lang('bank_deactivate_failed')]);
      }
    } else if ($this->input->post('activate')) {
      sendJSON(['error' => 1, 'msg' => validation_errors()]);
    }

    $this->data['notification'] = $this->site->getNotificationByID($id);

    $this->load->view($this->theme . 'notifications/deactivate', $this->data);
  }

  public function add()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('notify-add')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->form_validation->set_rules('comment', lang('comment'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'comment'   => $this->input->post('comment'),
        'from_date' => $this->input->post('from_date') ? $this->sma->fld($this->input->post('from_date')) : null,
        'till_date' => $this->input->post('to_date') ? $this->sma->fld($this->input->post('to_date')) : null,
        'scope'     => $this->input->post('scope'),
        'type'      => $this->input->post('type')
      ];
    } elseif ($this->input->post('submit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('notifications');
    }

    if ($this->form_validation->run() == true && $this->cmt_model->addNotification($data)) {
      $this->session->set_flashdata('message', lang('notification_added'));
      admin_redirect('notifications');
    } else {
      $this->data['comment'] = [
        'name' => 'comment',
        'id'       => 'comment',
        'type'     => 'textarea',
        'class'    => 'form-control',
        'required' => 'required',
        'value'    => $this->form_validation->set_value('comment'),
      ];

      $this->data['error']    = validation_errors();
      $this->load->view($this->theme . 'notifications/add', $this->data);
    }
  }

  public function delete($id = null)
  {
    if (!$this->Owner && !$this->Admin && !getPermission('notify-delete')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    if ($this->cmt_model->deleteComment($id)) {
      sendJSON(['error' => 0, 'msg' => lang('notifications_deleted')]);
    }
  }

  public function edit($id = null)
  {
    if (!$this->Owner && !$this->Admin && getPermission('notify-edit')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    if ($this->input->post('id')) {
      $id = $this->input->post('id');
    }

    $this->form_validation->set_rules('comment', lang('notifications'), 'required|min_length[3]');

    if ($this->form_validation->run() == true) {
      $data = [
        'date'      => date('Y-m-d H:i:s'),
        'comment'   => $this->input->post('comment'),
        'from_date' => $this->input->post('from_date') ? $this->sma->fld($this->input->post('from_date')) : null,
        'till_date' => $this->input->post('to_date') ? $this->sma->fld($this->input->post('to_date')) : null,
        'scope'     => $this->input->post('scope'),
        'type'      => $this->input->post('type')
      ];
    } elseif ($this->input->post('submit')) {
      $this->session->set_flashdata('error', validation_errors());
      admin_redirect('notifications');
    }

    if ($this->form_validation->run() == true) {
      if ($this->cmt_model->updateNotification($id, $data)) {
        $this->session->unset_userdata('hidden' . $id);
        $this->session->set_flashdata('message', lang('notification_updated'));
      }
      admin_redirect('notifications');
    } else {
      $comment = $this->cmt_model->getCommentByID($id);

      $this->data['comment'] = [
        'name' => 'comment',
        'id'       => 'comment',
        'type'     => 'textarea',
        'class'    => 'form-control',
        'required' => 'required',
        'value'    => $this->form_validation->set_value('comment', $comment->comment),
      ];

      $this->data['notification'] = $comment;
      $this->data['id']           = $id;
      $this->data['error']        = validation_errors();
      $this->load->view($this->theme . 'notifications/edit', $this->data);
    }
  }

  public function getNotifications()
  {
    $this->load->library('datatables');
    $this->datatables
      ->select('id, comment, date, from_date, till_date, active')
      ->from('notifications')
      ->edit_column('active', '$1__$2', 'active, id')
      ->add_column('Actions', "<div class=\"text-center\"><a href='" . admin_url('notifications/edit/$1') . "' data-toggle='modal' data-target='#myModal' data-backdrop='static' class='tip' title='" . lang('edit_notification') . "'><i class=\"fad fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . $this->lang->line('delete_notification') . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . admin_url('notifications/delete/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fad fa-trash\"></i></a></div>", 'id');

    echo $this->datatables->generate();
  }

  public function index()
  {
    if (!$this->Owner && !$this->Admin && !getPermission('notify-index')) {
      $this->session->set_flashdata('warning', lang('access_denied'));
      redirect($_SERVER['HTTP_REFERER']);
    }

    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
    $bc                  = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('notifications')]];
    $meta                = ['page_title' => lang('notifications'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('notifications/index', $this->data);
  }
}
