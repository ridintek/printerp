<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Products_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function add_products ($products = [])
	{
		if (!empty($products)) {
			foreach ($products as $product) {
				if (isset($product['safety_stock'])) {
					$safety_stock = $product['safety_stock'];
					unset($product['safety_stock']);
				}
				if (isset($product['variants'])) {
					unset($product['variants']);
				}

				if ($this->db->insert('products', $product)) {
					$product_id = $this->db->insert_id();

					if ($product['type'] == 'standard') {
						$warehouses = $this->site->getAllWarehouses();

						foreach ($warehouses as $warehouse) {
							$this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0]);
						}

						if ( ! empty($safety_stock)) { // Added
							$warehouses = $this->site->getAllWarehouses();
							foreach ($warehouses as $warehouse) {
								foreach ($safety_stock as $key => $val) {
									$sstock = 0; $warehouse_id = 0;
									if (stripos($warehouse->code, $key) !== FALSE) {
										$sstock = $val;
										$warehouse_id = $warehouse->id;
										break;
									}
								}
								$this->db->update('warehouses_products', ['safety_stock' => $sstock], ['product_id' => $product_id, 'warehouse_id' => $warehouse_id]);
							}
							$this->site->syncProductSafetyStock($product_id); // Added
							unset($safety_stock);
						}
					}
				}
			}
			return true;
		}
		return false;
	}

	public function addAdjustment($data, $products)
	{
		if ($this->db->insert('adjustments', $data)) {
			$adjustment_id = $this->db->insert_id();

			foreach ($products as $product) {
				$product['adjustment_id'] = $adjustment_id;
				$this->db->insert('adjustment_items', $product);
				//$this->syncAdjustment($product);

				if ($product['type'] == 'addition')    $status = 'received';
				if ($product['type'] == 'subtraction') $status = 'sent';

				$this->site->addStockQuantity([
					'date'          => $data['date'],
					'adjustment_id' => $adjustment_id,
					'product_id'    => $product['product_id'],
					'warehouse_id'  => $product['warehouse_id'],
					'quantity'      => $product['quantity'],
					'status'        => $status
				]);

				$this->site->syncProductQty($product['product_id'], $product['warehouse_id']);
			}
			if ($this->site->getReference('qa') == $data['reference']) {
				$this->site->updateReference('qa');
			}
			return true;
		}
		return false;
	}

	public function addAjaxProduct($data)
	{
		if ($this->db->insert('products', $data)) {
			$product_id = $this->db->insert_id();
			return $this->getProductByID($product_id);
		}
		return false;
	}

	public function addProduct ($data, $items, $warehouse_qty, $product_attributes, $photos)
	{
		if ( ! empty($data['safety_stock'])) {
			$safety_stock = $data['safety_stock'];
		}
		unset($data['safety_stock']);

		if ($this->db->insert('products', $data)) {
			$product_id = $this->db->insert_id();

			if ($items) {
				foreach ($items as $item) {
					$item['product_id'] = $product_id;
					$this->db->insert('combo_items', $item);
				}
			}

			$warehouses = $this->site->getAllWarehouses();
			if ($data['type'] != 'standard') {
				foreach ($warehouses as $warehouse) {
					$this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0]);
				}
			}
			if ($data['type'] == 'standard') { // Added.
				foreach ($warehouses as $warehouse) {
					$safety_alert = $safety_stock[$warehouse->code];
					$this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0, 'safety_stock' => $safety_alert]);
					// warehouses_products.quantity will be synced by $this->site->syncProductQty below.
				}
				$this->site->syncProductSafetyStock($product_id); // Added: Sync quantity_alert in products.
			}

			if ($warehouse_qty && ! empty($warehouse_qty)) { // NOT USED.
				foreach ($warehouse_qty as $wh_qty) {
					if (isset($wh_qty['quantity']) && !empty($wh_qty['quantity'])) {
						$this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $wh_qty['warehouse_id'], 'quantity' => $wh_qty['quantity'], 'rack' => $wh_qty['rack'], 'avg_cost' => $data['cost']]);

						if ( ! $product_attributes) {
							$unit_cost   = $data['cost'];

							$net_item_cost = $data['cost'];

							$subtotal = (($net_item_cost * $wh_qty['quantity']));

							$item = [
								'product_id'        => $product_id,
								'product_code'      => $data['code'],
								'product_name'      => $data['name'],
								'net_unit_cost'     => $net_item_cost,
								'unit_cost'         => $unit_cost,
								'real_unit_cost'    => $unit_cost,
								'quantity'          => $wh_qty['quantity'],
								'quantity_balance'  => $wh_qty['quantity'],
								'quantity_received' => $wh_qty['quantity'],
								'subtotal'          => $subtotal,
								'warehouse_id'      => $wh_qty['warehouse_id'],
								'date'              => date('Y-m-d'),
								'status'            => 'received',
							];
							$this->db->insert('purchase_items', $item);
							$this->site->syncProductQty($product_id, $wh_qty['warehouse_id']);
						}
					}
				}
			}

			if ($photos) {
				foreach ($photos as $photo) {
					$this->db->insert('product_photos', ['product_id' => $product_id, 'photo' => $photo]);
				}
			}

			$this->site->syncQuantity(null, null, null, $product_id);
			return true;
		}
		return false;
	}

	public function addQuantity($product_id, $warehouse_id, $quantity, $rack = null)
	{
		if ($this->getProductQuantity($product_id, $warehouse_id)) {
			if ($this->updateQuantity($product_id, $warehouse_id, $quantity, $rack)) {
				return true;
			}
		} else {
			if ($this->insertQuantity($product_id, $warehouse_id, $quantity, $rack)) {
				return true;
			}
		}

		return false;
	}

	public function addStockCount($data)
	{
		if ($this->db->insert('stock_counts', $data)) {
			return true;
		}
		return false;
	}

	public function deleteAdjustment($id)
	{
		$adjustment = $this->getAdjustmentByID($id);
		$adj_items = $this->getAdjustmentItems($id);
		$created_by = XSession::get('user_id');
		$date = date('Y-m-d H:i:s');
		$this->reverseAdjustment($id); // Zeroing quantity
		if ($this->db->delete('adjustments', ['id' => $id]) && $this->db->delete('adjustment_items', ['adjustment_id' => $id])) {
			foreach ($adj_items as $adj_item) {
				$data = [
					'date'     => $date,
					'reference'    => $adjustment->reference,
					'product_id'   => $adj_item->product_id,
					'warehouse_id' => $adj_item->warehouse_id,
					'category'   	 => 'Delete Adjustment',
					'created_by' 	 => $created_by,
					'quantity'   	 => $adj_item->quantity
				];
				$this->site->addProductDecreaseHistory($data);
			}
			return TRUE;
		}
		return false;
	}

	public function deleteProduct($id)
	{
		if ($this->db->delete('products', ['id' => $id]) && $this->db->delete('warehouses_products', ['product_id' => $id])) {
			$this->db->delete('product_photos', ['product_id' => $id]);
			$this->db->delete('product_prices', ['product_id' => $id]);
			return true;
		}
		return false;
	}

	public function fetch_products($category_id, $limit, $start, $subcategory_id = null)
	{
		$this->db->limit($limit, $start);
		if ($category_id) {
			$this->db->where('category_id', $category_id);
		}
		if ($subcategory_id) {
			$this->db->where('subcategory_id', $subcategory_id);
		}
		$this->db->order_by('id', 'asc');
		$query = $this->db->get('products');

		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function finalizeStockCount($id, $data, $products)
	{
		if ($this->db->update('stock_counts', $data, ['id' => $id])) {
			foreach ($products as $product) {
				$this->db->insert('stock_count_items', $product);
			}
			return true;
		}
		return false;
	}

	public function getAdjustmentByCountID($count_id)
	{
		$q = $this->db->get_where('adjustments', ['count_id' => $count_id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getAdjustmentByID($id)
	{
		$q = $this->db->get_where('adjustments', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getAdjustmentItem ($adjustment_id, $product_id) {
		$this->db->select('adjustment_items.*, products.code as product_code, products.name as product_name, products.image, products.details as details')
			->join('products', 'products.id=adjustment_items.product_id', 'left')
			->group_by('adjustment_items.id')
			->order_by('id', 'asc');

		$this->db->where('adjustment_id', $adjustment_id);
		$this->db->where('product_id', $product_id);

		$q = $this->db->get('adjustment_items');
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return NULL;
	}

	public function getAdjustmentItems ($adjustment_id)
	{
		$this->db->select('adjustment_items.*, products.code as product_code, products.name as product_name, products.image, products.details as details')
			->join('products', 'products.id=adjustment_items.product_id', 'left')
			->group_by('adjustment_items.id')
			->order_by('id', 'asc');

		$this->db->where('adjustment_id', $adjustment_id);

		$q = $this->db->get('adjustment_items');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getAllProducts()
	{
		$q = $this->db->get('products');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getAllVariants()
	{
		return NULL;
	}

	public function getAllWarehousesWithPQ($product_id)
	{
		$this->db->select("warehouses.*, warehouses_products.quantity, warehouses_products.safety_stock")
			->join('warehouses_products', 'warehouses_products.warehouse_id=warehouses.id', 'left')
			->where('warehouses_products.product_id', $product_id)
			->group_by('warehouses.id')
			->order_by('warehouses.name', 'ASC');
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
		return false;
	}

	public function getCategoryByCode($code)
	{
		$q = $this->db->get_where('categories', ['code' => $code], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getCategoryProducts($category_id)
	{
		$q = $this->db->get_where('products', ['category_id' => $category_id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	// ADDED: 2020-03-28 21:06:07
	public function getComboItemByCode ($item_code) {
		$q = $this->db->get_where('combo_items', ['item_code' => $item_code], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return NULL;
	}

	// ADDED: 2020-03-30 13:39:00
	public function getTotalPriceGroups () {
		$q = $this->db->count_all('price_groups');
		return $q;
	}

	public function getProductVariantByPIDandName($product_id, $name)
	{
		$q = $this->db->get_where('product_variants', ['product_id' => $product_id, 'name' => $name], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductByCategoryID($id)
	{
		$q = $this->db->get_where('products', ['category_id' => $id], 1);
		if ($q->num_rows() > 0) {
			return true;
		}
		return false;
	}

	public function getProductByCode($code)
	{
		$q = $this->db->get_where('products', ['code' => $code], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductByID($id)
	{
		$q = $this->db->get_where('products', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductComboItems($pid)
	{
		$this->db->select($this->db->dbprefix('products') . '.id as id, ' . $this->db->dbprefix('products') . '.code as code, ' . $this->db->dbprefix('combo_items') . '.quantity as qty, ' . $this->db->dbprefix('products') . '.name as name, ' . $this->db->dbprefix('combo_items') . '.unit_price as price')->join('products', 'products.code=combo_items.item_code', 'left')->group_by('combo_items.id');
		$q = $this->db->get_where('combo_items', ['product_id' => $pid]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}

			return $data;
		}
		return false;
	}

	public function getProductDetail($id)
	{
		$this->db->select($this->db->dbprefix('products') . '.*, ' . $this->db->dbprefix('tax_rates') . '.name as tax_rate_name, ' . $this->db->dbprefix('tax_rates') . '.code as tax_rate_code, c.code as category_code, sc.code as subcategory_code', false)
			->join('tax_rates', 'tax_rates.id=products.tax_rate', 'left')
			->join('categories c', 'c.id=products.category_id', 'left')
			->join('categories sc', 'sc.id=products.subcategory_id', 'left');
		$q = $this->db->get_where('products', ['products.id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductDetails($id)
	{
		$this->db->select($this->db->dbprefix('products') . '.code, ' . $this->db->dbprefix('products') . '.name, ' . $this->db->dbprefix('categories') . '.code as category_code, cost, price, quantity, safety_stock')
			->join('categories', 'categories.id=products.category_id', 'left');
		$q = $this->db->get_where('products', ['products.id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductNames($term, $limit = 5)
	{
		$this->db->select('' . $this->db->dbprefix('products') . '.id, code, ' . $this->db->dbprefix('products') . '.name as name, ' . $this->db->dbprefix('products') . '.price as price')
			->where("type != 'combo' AND "
				. '(' . $this->db->dbprefix('products') . ".name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR
				concat(" . $this->db->dbprefix('products') . ".name, ' (', code, ')') LIKE '%" . $term . "%')")
			->group_by('products.id')->limit($limit);
		$q = $this->db->get('products');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getProductPhotos($id)
	{
		$q = $this->db->get_where('product_photos', ['product_id' => $id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
	}

	public function getProductQuantity($product_id, $warehouse)
	{
		$q = $this->db->get_where('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse], 1);
		if ($q->num_rows() > 0) {
			return $q->row_array();
		}
		return false;
	}

	public function getProductsForPrinting($term, $limit = 5)
	{
		$this->db->select('' . $this->db->dbprefix('products') . '.id, code, ' . $this->db->dbprefix('products') . '.name as name, ' . $this->db->dbprefix('products') . '.price as price')
			->where('(' . $this->db->dbprefix('products') . ".name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR
				concat(" . $this->db->dbprefix('products') . ".name, ' (', code, ')') LIKE '%" . $term . "%')")
			->limit($limit);
		$q = $this->db->get('products');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getProductVariantByID($product_id, $id)
	{
		$q = $this->db->get_where('product_variants', ['product_id' => $product_id, 'id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductVariantByName($product_id, $name)
	{
		$q = $this->db->get_where('product_variants', ['product_id' => $product_id, 'name' => $name], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductVariantID($product_id, $name)
	{
		$q = $this->db->get_where('product_variants', ['product_id' => $product_id, 'name' => $name], 1);
		if ($q->num_rows() > 0) {
			$variant = $q->row();
			return $variant->id;
		}
		return false;
	}

	public function getProductWarehouseOptionQty($option_id, $warehouse_id)
	{
		$q = $this->db->get_where('warehouses_products_variants', ['option_id' => $option_id, 'warehouse_id' => $warehouse_id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getProductWarehouseOptions($option_id)
	{
		$q = $this->db->get_where('warehouses_products_variants', ['option_id' => $option_id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getProductWithCategory($id)
	{
		$this->db->select($this->db->dbprefix('products') . '.*, ' . $this->db->dbprefix('categories') . '.name as category')
		->join('categories', 'categories.id=products.category_id', 'left');
		$q = $this->db->get_where('products', ['products.id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getPurchasedQty($id)
	{
		return false;
	}

	public function getPurchaseItems($purchase_id)
	{
		return false;
	}

	public function getQASuggestions($term, $limit = 5)
	{
		$this->db->select('' . $this->db->dbprefix('products') . '.id, code, ' . $this->db->dbprefix('products') . '.name as name')
			->where("type != 'combo' AND "
				. '(' . $this->db->dbprefix('products') . ".name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR
				concat(" . $this->db->dbprefix('products') . ".name, ' (', code, ')') LIKE '%" . $term . "%')")
			->limit($limit);
		$q = $this->db->get('products');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getSoldQty($id)
	{
		$this->db->select('date_format(' . $this->db->dbprefix('sales') . ".date, '%Y-%M') month, SUM( " . $this->db->dbprefix('sale_items') . '.quantity ) as sold, SUM( ' . $this->db->dbprefix('sale_items') . '.subtotal ) as amount')
			->from('sales')
			->join('sale_items', 'sales.id=sale_items.sale_id', 'left')
			->group_by('date_format(' . $this->db->dbprefix('sales') . ".date, '%Y-%m')")
			->where($this->db->dbprefix('sale_items') . '.product_id', $id)
			//->where('DATE(NOW()) - INTERVAL 1 MONTH')
			->where('DATE_ADD(curdate(), INTERVAL 1 MONTH)')
			->order_by('date_format(' . $this->db->dbprefix('sales') . ".date, '%Y-%m') desc")->limit(3);
		$q = $this->db->get();
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getStockCountItems($stock_count_id)
	{
		$q = $this->db->get_where('stock_count_items', ['stock_count_id' => $stock_count_id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return null;
	}

	public function getStockCountProducts($warehouse_id, $type, $categories = null, $brands = null)
	{
		$this->db->select("{$this->db->dbprefix('products')}.id as id, {$this->db->dbprefix('products')}.code as code, {$this->db->dbprefix('products')}.name as name, {$this->db->dbprefix('warehouses_products')}.quantity as quantity")
		->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
		->where('warehouses_products.warehouse_id', $warehouse_id)
		->where('products.type', 'standard')
		->order_by('products.code', 'asc');
		if ($categories) {
			$r = 1;
			$this->db->group_start();
			foreach ($categories as $category) {
				if ($r == 1) {
					$this->db->where('products.category_id', $category);
				} else {
					$this->db->or_where('products.category_id', $category);
				}
				$r++;
			}
			$this->db->group_end();
		}
		if ($brands) {
			$r = 1;
			$this->db->group_start();
			foreach ($brands as $brand) {
				if ($r == 1) {
					$this->db->where('products.brand', $brand);
				} else {
					$this->db->or_where('products.brand', $brand);
				}
				$r++;
			}
			$this->db->group_end();
		}

		$q = $this->db->get('products');
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getStockCountProductVariants($warehouse_id, $product_id)
	{
		$this->db->select("{$this->db->dbprefix('product_variants')}.name, {$this->db->dbprefix('warehouses_products_variants')}.quantity as quantity")
			->join('warehouses_products_variants', 'warehouses_products_variants.option_id=product_variants.id', 'left');
		$q = $this->db->get_where('product_variants', ['product_variants.product_id' => $product_id, 'warehouses_products_variants.warehouse_id' => $warehouse_id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
	}

	public function getStouckCountByID($id)
	{
		$q = $this->db->get_where('stock_counts', ['id' => $id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getSubCategories($parent_id)
	{
		if ($parent_id) {
			$this->db
				->select('id as id, name as text')
				->where('parent_id', $parent_id)->order_by('name');
			$q = $this->db->get('categories');
			if ($q->num_rows() > 0) {
				foreach ($q->result() as $row) {
					$data[] = $row;
				}
				return $data;
			}
		}
		return [];
	}

	public function getSubCategoryProducts($subcategory_id)
	{
		$q = $this->db->get_where('products', ['subcategory_id' => $subcategory_id]);
		if ($q->num_rows() > 0) {
			foreach (($q->result()) as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getSupplierByName($name)
	{
		$q = $this->db->get_where('suppliers', ['name' => $name, 'group_name' => 'supplier'], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	// ADDED: 2020-03-28 22:32:45
	public function getTotalComboPricesByRawItems ($raw_items) {
		$price = 0;
		if ( ! empty($raw_items)) {
			foreach ($raw_items as $item) {
				if (isset($item['item_code'])) {
					$q = $this->db->get_where('products', ['code' => $item['item_code']], 1);
					if ($q->num_rows() > 0) {
						$price += floatval($q->row()->price) * floatval($item['quantity']);
					}
				}
			}
			return $price;
		}
		return NULL;
	}

	public function getTransferItems($transfer_id)
	{
		return false;
	}

	public function getUnitByCode($code)
	{
		$q = $this->db->get_where('units', ['code' => $code], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function getWarehouseProductVariant($warehouse_id, $product_id, $option_id = null)
	{
		$q = $this->db->get_where('warehouses_products_variants', ['product_id' => $product_id, 'option_id' => $option_id, 'warehouse_id' => $warehouse_id], 1);
		if ($q->num_rows() > 0) {
			return $q->row();
		}
		return false;
	}

	public function has_purchase($product_id, $warehouse_id = null)
	{
		return false;
	}

	public function insertQuantity($product_id, $warehouse_id, $quantity, $rack = null)
	{
		$product = $this->site->getProductByID($product_id);
		if ($this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity, 'rack' => $rack, 'avg_cost' => $product->cost])) {
			$this->site->syncProductQty($product_id, $warehouse_id);
			return true;
		}
		return false;
	}

	public function products_count($category_id, $subcategory_id = null)
	{
		if ($category_id) {
			$this->db->where('category_id', $category_id);
		}
		if ($subcategory_id) {
			$this->db->where('subcategory_id', $subcategory_id);
		}
		$this->db->from('products');
		return $this->db->count_all_results();
	}

	public function reverseAdjustment ($id)
	{
		if ($products = $this->getAdjustmentItems($id)) {
			foreach ($products as $adjustment) {
				$clause = ['product_id' => $adjustment->product_id, 'warehouse_id' => $adjustment->warehouse_id, 'status' => 'received'];
				$qty    = $adjustment->type == 'subtraction' ? (0 + $adjustment->quantity) : (0 - $adjustment->quantity);
				$this->site->setPurchaseItem($clause, $qty);
				$this->site->syncProductQty($adjustment->product_id, $adjustment->warehouse_id);
			}
		}
	}

	public function setRack($data)
	{
		if ($this->db->update('warehouses_products', ['rack' => $data['rack']], ['product_id' => $data['product_id'], 'warehouse_id' => $data['warehouse_id']])) {
			return true;
		}
		return false;
	}

	public function syncAdjustment($data = [])
	{
		if ( ! empty($data)) {
			$clause = ['product_id' => $data['product_id'], 'warehouse_id' => $data['warehouse_id'], 'status' => 'received'];
			$qty    = ($data['type'] == 'subtraction' ? 0 - $data['quantity'] : 0 + $data['quantity']);

			$this->site->addStockQuantity([

			]);
			$this->site->setPurchaseItem($clause, $qty);

			$this->site->syncProductQty($data['product_id'], $data['warehouse_id']);
		}
	}

	public function syncVariantQty($option_id)
	{
		$wh_pr_vars = $this->getProductWarehouseOptions($option_id);
		$qty        = 0;
		foreach ($wh_pr_vars as $row) {
			$qty += $row->quantity;
		}
		if ($this->db->update('product_variants', ['quantity' => $qty], ['id' => $option_id])) {
			return true;
		}
		return false;
	}

	public function totalCategoryProducts($category_id)
	{
		$q = $this->db->get_where('products', ['category_id' => $category_id]);
		return $q->num_rows();
	}

	public function updateAdjustment ($adj_id, $data, $products)
	{
		if ($this->db->update('adjustments', $data, ['id' => $adj_id])) {
			$adj_items = $this->getAdjustmentItems($adj_id); // Old Items
			$delete_products = [];
			foreach ($products as $product) {
				foreach ($adj_items as $aitem) {
					if ($aitem->product_id == $product['product_id']) {

					}
				}
			}
			foreach ($products as $product) {
				$adj_item = $this->getAdjustmentItem($adj_id, $product['product_id']);
				if ($adj_item) {
					$this->db->update('adjustment_items', $product, ['adjustment_id' => $adj_item->adjustment_id, 'product_id' => $adj_item->product_id]);
				} else {
					$product['adjustment_id'] = $adj_id;
					$this->db->insert('adjustment_items', $product);
				}
				$this->site->syncProductQty($product['product_id'], $product['warehouse_id']);
			}
			if ($delete_products) {

			}
			return true;
		}
		return false;
	}

	public function updatePrice($data = [])
	{
		if ($this->db->update_batch('products', $data, 'code')) {
			return true;
		}
		return false;
	}

	public function updateProduct($id, $data, $items, $warehouse_qty, $product_attributes, $photos, $update_variants = NULL)
	{
		if (isset($data['safety_stock']) && ! empty($data['safety_stock'])) {
			$warehouses = $this->site->getAllWarehouses();
			foreach ($warehouses as $warehouse) {
				foreach ($data['safety_stock'] as $key => $val) {
					$safety_stock = 0; $warehouse_id = 0;
					if (stripos($warehouse->code, $key) !== FALSE) {
						$safety_stock = $val;
						$warehouse_id = $warehouse->id;
						break;
					}
				}
				$this->db->update('warehouses_products', ['safety_stock' => $safety_stock], ['product_id' => $id, 'warehouse_id' => $warehouse_id]);
			}
		}

		unset($data['safety_stock']);

		if ($this->db->update('products', $data, ['id' => $id])) { // Do update products.
			if ($items) {
				$this->db->delete('combo_items', ['product_id' => $id]);
				foreach ($items as $item) {
					$item['product_id'] = $id;
					$this->db->insert('combo_items', $item);
				}
			}

			if ($photos) {
				foreach ($photos as $photo) {
					$this->db->insert('product_photos', ['product_id' => $id, 'photo' => $photo]);
				}
			}

			$this->site->syncQuantity(null, null, null, $id);
			return true;
		}
		return false;
	}

	public function updateProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id)
	{
		if ($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
			if ($this->db->update('warehouses_products_variants', ['quantity' => $quantity], ['option_id' => $option_id, 'warehouse_id' => $warehouse_id])) {
				$this->site->syncVariantQty($option_id, $warehouse_id);
				return true;
			}
		} else {
			if ($this->db->insert('warehouses_products_variants', ['option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity])) {
				$this->site->syncVariantQty($option_id, $warehouse_id);
				return true;
			}
		}
		return false;
	}

	public function updateQuantity($product_id, $warehouse_id, $quantity, $rack = null)
	{
		$data = $rack ? ['quantity' => $quantity, 'rack' => $rack] : $data = ['quantity' => $quantity];
		if ($this->db->update('warehouses_products', $data, ['product_id' => $product_id, 'warehouse_id' => $warehouse_id])) {
			$this->site->syncProductQty($product_id, $warehouse_id);
			return true;
		}
		return false;
	}
}
