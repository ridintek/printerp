<?php defined('BASEPATH') or exit('No direct script access allowed');

class Payrolls extends MY_Controller
{
  public function __construct()
  {
    parent::__construct();

    if (!$this->loggedIn) {
      admin_redirect('login');
    }
  }

  public function add()
  {
    $this->load->view($this->theme . 'payrolls/add', $this->data);
  }

  public function categories()
  {
    $args = func_get_args();

    if ($args && $args[0] == 'add') {
      $this->categories_add();
      return TRUE;
    } else if ($args && $args[0] == 'delete') {
      $this->categories_delete();
      return TRUE;
    } else if ($args && $args[0] == 'edit') {
      $this->categories_edit();
      return TRUE;
    }

    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => admin_url('payrolls'), 'page' => lang('payrolls')],
      ['link' => '#', 'page' => lang('categories')]
    ];

    $meta = ['page_title' => lang('payroll_categories'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('payrolls/categories', $this->data);
  }

  private function categories_add()
  {
    if ($this->requestMethod == 'POST') {
      $code = getPost('code');
      $name = getPost('name');
      $type = getPost('type');

      $category_data = [
        'code' => $code,
        'name' => $name,
        'type' => $type
      ];

      if ($this->site->addPayrollCategory($category_data)) {
        sendJSON(['error' => 0, 'msg' => "Payroll category '{$code}' has been added successfully."]);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to add payroll category.']);
    }

    $this->load->view($this->theme . 'payrolls/add_category', $this->data);
  }

  private function categories_delete()
  {
    $id = getPost('id');

    if ($this->site->deletePayrollCategory($id)) {
      sendJSON(['error' => 0, 'msg' => 'Payroll Category has been deleted successfully.']);
    }
    sendJSON(['error' => 1, 'msg' => 'Failed to delete Payroll Category.']);
  }

  private function categories_edit()
  {
    if ($this->requestMethod == 'POST') {
      $id   = getPost('id');
      $code = getPost('code');
      $name = getPost('name');
      $type = getPost('type');

      $category_data = [
        'code' => $code,
        'name' => $name,
        'type' => $type
      ];

      if ($this->site->updatePayrollCategory($id, $category_data)) {
        sendJSON(['error' => 0, 'msg' => "Payroll category '{$code}' has been updated successfully."]);
      }
      sendJSON(['error' => 1, 'msg' => 'Failed to edit payroll category.']);
    }

    $id = getGET('id');

    $this->data['payroll_categories'] = $this->site->getPayrollCategoryByID($id);

    $this->load->view($this->theme . 'payrolls/edit_category', $this->data);
  }

  public function edit()
  {

  }

  public function getPayrolls()
  {
    $this->load->library('datatables');

    $this->datatables
      ->select("payrolls.id AS id, payrolls.date AS date,
        user.fullname AS employee_name,
        banks.name AS bank_name,
        payroll_categories.name AS category_name, payrolls.amount AS amount,
        payrolls.status AS status, payrolls.note AS note")
      ->from('payrolls')
      ->join('banks', 'banks.id = payrolls.bank_id', 'left')
      ->join('user', 'user.id = payrolls.user_id', 'left')
      ->join('payroll_categories', 'payroll_categories.id = payrolls.category_id', 'left');

    echo $this->datatables->generate();
  }

  public function index()
  {
    $bc   = [
      ['link' => base_url(), 'page' => lang('home')],
      ['link' => '#', 'page' => lang('payrolls')]
    ];

    $meta = ['page_title' => lang('payrolls'), 'bc' => $bc];
    $this->data = array_merge($this->data, $meta);

    $this->page_construct('payrolls/index', $this->data);
  }

  public function getPayrollCategories()
  {
    $this->load->library('datatables');

    $this->datatables
      ->select("payroll_categories.id, payroll_categories.code, payroll_categories.name,
        payroll_categories.type")
      ->from('payroll_categories');

    $action =
      '<div class="text-center">
        <div class="btn-group text-left">
          <button type="button" class="btn btn-xs btn-primary dropdown-toggle"
            data-toggle="dropdown">Actions<span class="caret"></span>
          </button>
          <ul class="dropdown-menu pull-right" role="menu">
            <li>
              <a href="' . admin_url('payrolls/categories/edit?id=$1'). '"
                data-toggle="modal" data-target="#myModal">
                <i class="fad fa-edit"></i> Edit
              </a>
            </li>
            <li>
              <a href="' . admin_url('payrolls/categories/delete?id=$1'). '"
                class="delete" data-category-id="$1" data-category-name="$2">
                <i class="fad fa-trash"></i> Delete
              </a>
            </li>
          </ul>
        </div>
      </div>';

    $this->datatables->add_column('Actions', $action, 'payroll_categories.id, payroll_categories.name');

    echo $this->datatables->generate();
  }
}
