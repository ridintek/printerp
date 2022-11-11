<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Settings_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function addBrand($data)
  {
    return NULL;
  }

  public function addBrands($data)
  {
    return NULL;
  }

  public function addCategories($categories, $subcategories)
  {
    $result = false;
    if (!empty($categories)) {
      foreach ($categories as $category) {
        $this->db->insert('categories', $category);
      }
      $result = true;
    }
    if (!empty($subcategories)) {
      foreach ($subcategories as $category) {
        if (!empty($category['parent_code'])) {
          $this->db->insert('categories', $category);
        } else {
          if ($pcategory = $this->getCategoryByCode($category['parent_code'])) {
            $category['parent_code'] = $pcategory->code;
            $this->db->insert('categories', $category);
          }
        }
      }
      $result = true;
    }
    return $result;
  }

  public function addCategory($data)
  {
    if ($this->db->insert('categories', $data)) {
      return true;
    }
    return false;
  }

  public function addCurrency($data)
  {
    if ($this->db->insert('currencies', $data)) {
      return true;
    }
    return false;
  }

  public function addCustomerGroup($data)
  {
    if ($this->db->insert('customer_groups', $data)) {
      return true;
    }
    return false;
  }

  public function addExpenseCategories($data)
  {
    if ($this->db->insert_batch('expense_categories', $data)) {
      return true;
    }
    return false;
  }

  public function addExpenseCategory($data)
  {
    if ($this->db->insert('expense_categories', $data)) {
      return true;
    }
    return false;
  }

  public function addIncomeCategories($data)
  {
    if ($this->db->insert_batch('income_categories', $data)) {
      return true;
    }
    return false;
  }

  public function addIncomeCategory($data)
  {
    if ($this->db->insert('income_categories', $data)) {
      return true;
    }
    return false;
  }

  public function addGroup($data)
  {
    if ($this->db->insert('groups', $data)) {
      $gid = $this->db->insert_id();
      $this->db->insert('permissions', ['group_id' => $gid]);
      return $gid;
    }
    return false;
  }

  public function addPriceGroup($data)
  {
    if ($this->db->insert('price_groups', $data)) {
      return true;
    }
    return false;
  }

  public function addPriceRange($data) // Add Price Range
  {
    if ($this->db->insert('price_ranges', $data)) {
      return true;
    }
    return false;
  }

  public function addTaxRate($data)
  {
    return NULL;
  }

  public function addUnit($data)
  {
    if ($this->db->insert('units', $data)) {
      return true;
    }
    return false;
  }

  public function addVariant($data)
  {
    return NULL;
  }

  public function addWarehouse($data)
  {
    if ($this->db->insert('warehouses', $data)) {
      return true;
    }
    return false;
  }

  public function brandHasProducts($brand_id)
  {
    return NULL;
  }

  public function checkGroupUsers($id)
  {
    $q = $this->db->get_where('users', ['group_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function deleteBrand($id)
  {
    return NULL;
  }

  public function deleteCategory($id)
  {
    if ($this->db->delete('categories', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteCurrency($id)
  {
    if ($this->db->delete('currencies', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteCustomerGroup($id)
  {
    if ($this->db->delete('customer_groups', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteExpenseCategory($id)
  {
    if ($this->db->delete('expense_categories', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteIncomeCategory($id)
  {
    if ($this->db->delete('income_categories', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteGroup($id)
  {
    if ($this->db->delete('groups', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteInvoiceType($id)
  {
    if ($this->db->delete('invoice_types', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deletePriceGroup($id)
  {
    if ($this->db->delete('price_groups', ['id' => $id]) && $this->db->delete('product_prices', ['price_group_id' => $id])) {
      return true;
    }
    return false;
  }

  public function deletePriceRange($id)
  {
    if ($this->db->delete('price_ranges', ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function deleteProductGroupPrice($product_id, $group_id)
  {
    if ($this->db->delete('product_prices', ['price_group_id' => $group_id, 'product_id' => $product_id])) {
      return true;
    }
    return false;
  }

  public function deleteTaxRate($id)
  {
    return NULL;
  }

  public function deleteUnit($id)
  {
    if ($this->db->delete('units', ['id' => $id])) {
      $this->db->delete('units', ['base_unit' => $id]);
      return true;
    }
    return false;
  }

  public function deleteVariant($id)
  {
    return NULL;
  }

  public function deleteWarehouse($id)
  {
    if ($this->db->delete('warehouses', ['id' => $id]) && $this->db->delete('warehouses_products', ['warehouse_id' => $id])) {
      return true;
    }
    return false;
  }

  public function getAllCurrencies()
  {
    $q = $this->db->get('currencies');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getAllCustomerGroups()
  {
    $q = $this->db->get('customer_groups');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getAllPriceGroups()
  {
    $q = $this->db->get('price_groups');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getAllTaxRates()
  {
    return NULL;
  }

  public function getAllVariants()
  {
    return NULL;
  }

  public function getAllWarehouses()
  {
    $this->db->order_by('name', 'asc');
    $q = $this->db->get('warehouses');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getBrandByName($name)
  {
    return NULL;
  }

  public function getCategoryByCode($code)
  {
    $q = $this->db->get_where('categories', ['code' => $code], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getCategoryByID($id)
  {
    $q = $this->db->get_where('categories', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getCurrencyByID($id)
  {
    $q = $this->db->get_where('currencies', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getCustomerGroupByID($id)
  {
    $q = $this->db->get_where('customer_groups', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getDateFormats()
  {
    $q = $this->db->get('date_format');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getExpenseCategories () {
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getExpenseCategoryByCode($code)
  {
    $this->db->like('code', $code, 'none');
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      rd_debug('settings_model', 'FUCKED');
      return $q->row();
    }
    return NULL;
  }

  public function getExpenseCategoryByID($id)
  {
    $q = $this->db->get_where('expense_categories', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getExpenseCategoryByName ($name)
  {
    $this->db->like('name', $name, 'none');
    $q = $this->db->get('expense_categories');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getIncomeCategories () {
    $q = $this->db->get('income_categories');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getIncomeCategoryByCode ($code)
  {
    $q = $this->db->get_where('income_categories', ['code' => $code], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getIncomeCategoryByID ($id)
  {
    $q = $this->db->get_where('income_categories', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getIncomeCategoryByName ($name)
  {
    $this->db->like('name', $name, 'none');
    $q = $this->db->get('income_categories');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getGroupByID($id)
  {
    $q = $this->db->get_where('groups', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getGroupPermissions($id)
  {
    $q = $this->db->get_where('permissions', ['group_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getGroupPrice($group_id, $product_id)
  {
    $q = $this->db->get_where('product_prices', ['price_group_id' => $group_id, 'product_id' => $product_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getGroups()
  {
    $this->db->where('id >', 4);
    $q = $this->db->get('groups');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getMutasibankSettings () {
    $q = $this->db->get('mutasibank');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getParentCategories()
  {
    $this->db->where('parent_code', null)->or_like('parent_code', '', 'none');

    $q = $this->db->get('categories');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getPaypalSettings()
  {
    return NULL;
  }

  public function getPriceGroupByID($id)
  {
    $q = $this->db->get_where('price_groups', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getPriceRangeByID($id)
  {
    $q = $this->db->get_where('price_ranges', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getPriceRanges() {
    $q = $this->db->get('price_ranges');
    if ($q->num_rows() > 0) {
      return $q->result();
    }
    return false;
  }

  public function getProductGroupPriceByPID($product_id, $group_id)
  {
    $pg = "(SELECT {$this->db->dbprefix('product_prices')}.price as price, {$this->db->dbprefix('product_prices')}.product_id as product_id FROM {$this->db->dbprefix('product_prices')} WHERE {$this->db->dbprefix('product_prices')}.product_id = {$product_id} AND {$this->db->dbprefix('product_prices')}.price_group_id = {$group_id}) GP";

    $this->db->select("{$this->db->dbprefix('products')}.id as id, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, GP.price", false)
    // ->join('products', 'products.id=product_prices.product_id', 'left')
    ->join($pg, 'GP.product_id=products.id', 'left');
    $q = $this->db->get_where('products', ['products.id' => $product_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getSettings()
  {
    $q = $this->db->get('settings');
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function getSkrillSettings()
  {
    return NULL;
  }

  public function getTaxRateByID($id)
  {
    return NULL;
  }

  public function getUnitChildren($base_unit)
  {
    $this->db->where('base_unit', $base_unit);
    $q = $this->db->get('units');
    if ($q->num_rows() > 0) {
      foreach (($q->result()) as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return false;
  }

  public function getVariantByID($id)
  {
    return NULL;
  }

  public function getWarehouseByID($id)
  {
    $q = $this->db->get_where('warehouses', ['id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return false;
  }

  public function GroupPermissions($id)
  {
    $q = $this->db->get_where('permissions', ['group_id' => $id], 1);
    if ($q->num_rows() > 0) {
      return $q->result_array();
    }
    return false;
  }

  public function hasExpenseCategoryRecord($id)
  {
    $this->db->where('category_id', $id);
    return $this->db->count_all_results('expenses');
  }

  public function hasIncomeCategoryRecord($id)
  {
    $this->db->where('category_id', $id);
    return $this->db->count_all_results('incomes');
  }

  public function setProductPriceForPriceGroup($product_id, $group_id, $price, $price2, $price3, $price4, $price5, $price6)
  {
    if ($this->getGroupPrice($group_id, $product_id)) {
      if ($this->db->update('product_prices', [
        'price' => $price,
        'price2' => $price2,
        'price3' => $price3,
        'price4' => $price4,
        'price5' => $price5,
        'price6' => $price6
      ], ['price_group_id' => $group_id, 'product_id' => $product_id])) {
        return true;
      }
    } else {
      if ($this->db->insert('product_prices', [
        'price' => $price,
        'price2' => $price2,
        'price3' => $price3,
        'price4' => $price4,
        'price5' => $price5,
        'price6' => $price6,
        'price_group_id' => $group_id, 'product_id' => $product_id
      ])) {
        return true;
      }
      return true;
    }
    return false;
  }

  public function updateBrand($id, $data = [])
  {
    return NULL;
  }

  public function updateCategory($id, $data = [])
  {
    if ($this->db->update('categories', $data, ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function updateCurrency($id, $data = [])
  {
    $this->db->where('id', $id);
    if ($this->db->update('currencies', $data)) {
      return true;
    }
    return false;
  }

  public function updateCustomerGroup($id, $data = [])
  {
    $this->db->where('id', $id);
    if ($this->db->update('customer_groups', $data)) {
      return true;
    }
    return false;
  }

  public function updateExpenseCategory($id, $data = [])
  {
    if ($this->db->update('expense_categories', $data, ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function updateIncomeCategory($id, $data = [])
  {
    if ($this->db->update('income_categories', $data, ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function updateGroup($id, $data = [])
  {
    $this->db->where('id', $id);
    if ($this->db->update('groups', $data)) {
      return true;
    }
    return false;
  }

  public function updateGroupPrices($data = []) // MODIFIED 2020-03-19 10:58
  {
    foreach ($data as $row) {
      if ($this->getGroupPrice($row['price_group_id'], $row['product_id'])) {
        $this->db->update('product_prices',
          ['price' => $row['price']],
          ['price2' => $row['price2']],
          ['price3' => $row['price3']],
          ['price4' => $row['price4']],
          ['price5' => $row['price5']],
          ['price6' => $row['price6']],
          ['product_id' => $row['product_id'], 'price_group_id' => $row['price_group_id']]);
      } else {
        $this->db->insert('product_prices', $row);
      }
    }
    return true;
  }

  public function updateLoginLogo($photo)
  {
    $logo = ['logo2' => $photo];
    if ($this->db->update('settings', $logo)) {
      return true;
    }
    return false;
  }

  public function updateLogo($photo)
  {
    $logo = ['logo' => $photo];
    if ($this->db->update('settings', $logo)) {
      return true;
    }
    return false;
  }

  public function updateMutasibank ($data) {
    $this->db->where('id', '1');
    $q = $this->db->get('mutasibank');
    if ($q->num_rows() > 0) {
      $this->db->trans_start();
      $this->db->update('mutasibank', $data, ['id' => 1]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    } else {
      $this->db->trans_start();
      $this->db->insert('mutasibank', $data);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function updatePaypal($data)
  {
    $this->db->where('id', '1');
    if ($this->db->update('paypal', $data)) {
      return true;
    }
    return false;
  }

  public function updatePermissions($id, $data = [])
  {
    if ($this->db->update('permissions', $data, ['group_id' => $id]) && $this->db->update('users', ['show_price' => $data['products-price'], 'show_cost' => $data['products-cost']], ['group_id' => $id])) {
      return true;
    }
    return false;
  }

  public function updatePriceGroup($id, $data = [])
  {
    $this->db->where('id', $id);
    if ($this->db->update('price_groups', $data)) {
      return true;
    }
    return false;
  }

  public function updatePriceRange($id, $data = []) // Update / Edit Price Range
  {
    $this->db->where('id', $id);
    if ($this->db->update('price_ranges', $data)) {
      return true;
    }
    return false;
  }

  public function updateSetting($data)
  {
    $this->db->where('setting_id', '1');
    if ($this->db->update('settings', $data)) {
      return true;
    }
    return false;
  }

  public function updateSkrill($data)
  {
    $this->db->where('id', '1');
    if ($this->db->update('skrill', $data)) {
      return true;
    }
    return false;
  }

  public function updateTaxRate($id, $data = [])
  {
    return NULL;
  }

  public function updateUnit($id, $data = [])
  {
    if ($this->db->update('units', $data, ['id' => $id])) {
      return true;
    }
    return false;
  }

  public function updateVariant($id, $data = [])
  {
    return NULL;
  }

  public function updateWarehouse($id, $data = [])
  {
    $this->db->where('id', $id);
    if ($this->db->update('warehouses', $data)) {
      return true;
    }
    return false;
  }
}
