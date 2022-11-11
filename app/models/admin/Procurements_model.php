<?php defined('BASEPATH') or exit('No direct script access allowed');

class Procurements_model extends CI_Model
{
  public function __construct()
  {
    parent::__construct();
  }

  public function addPurchaseHistory ($data) {
    if ( ! empty($data)) {
      $this->db->trans_start();
      $this->db->insert('purchase_histories', $data);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function addTransferPayment ($transfer_id, $data = [])
  {
    $this->load->admin_model('finances_model');
    $this->load->admin_model('transfers_model');

    if ($this->finances_model->addBankMutation($data)) {
      $transfer = $this->site->getTransferByID($transfer_id);
      $paid = $transfer->paid + $data['amount'];

      if ($paid >= $transfer->grand_total) {
        $payment_status = 'paid';
      }
      if ($paid < $transfer->grand_total) {
        $payment_status = 'partial';
      }

      $this->db->trans_start();
      $this->db->update('transfers', ['paid' => $paid, 'payment_status' => $payment_status], ['id' => $transfer_id]);
      $this->db->trans_complete();

      if ($this->db->trans_status() !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function getPaymentsByPurchaseId ($purchase_id) {
    $q = $this->db->get_where('payments', ['purchase_id' => $purchase_id]);
    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return NULL;
  }

  public function getProductNames ($term, $warehouse_id, $limit = 5) { // Copied from transfers_model.php
    $this->db->select('products.id as id, code, name, warehouses_products.quantity, cost,
      price, markon_price, markon, min_order_qty, type, unit, purchase_unit')
      ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
      ->group_by('products.id');
    $this->db->where("type = 'standard' AND warehouses_products.warehouse_id = '" . $warehouse_id . "' AND (name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");

    $this->db->limit($limit);
    $q = $this->db->get('products');
    if ($q->num_rows() > 0) {
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
  }

  public function getPurchaseById ($purchase_id) {
    $q = $this->db->get_where('purchases', ['id' => $purchase_id], 1);
    if ($q->num_rows() > 0) {
      return $q->row();
    }
    return NULL;
  }

  public function getPurchasePayments ($purchase_id) {
    $q = $this->db->get_where('payments', ['purchase_id' => $purchase_id]);
    if ($q->num_rows() > 0) {
      $data = [];
      foreach ($q->result() as $row) {
        $data[] = $row;
      }
      return $data;
    }
    return NULL;
  }

  public function resetTransferActions($id, $delete = null)
  {
    $otransfer = $this->site->getTransferByID($id);
    $oitems    = $this->getAllTransferItems($id, $otransfer->status);
    $ostatus   = $otransfer->status; // old status
    if ($ostatus == 'sent' || $ostatus == 'received') { // ori: 'received' => 'completed'.
      if ( ! empty($oitems)) {
        foreach ($oitems as $item) {
          $option_id = (isset($item->option_id) && !empty($item->option_id)) ? $item->option_id : null;
          $clause    = ['purchase_id' => null, 'transfer_id' => null, 'product_id' => $item->product_id, 'warehouse_id' => $otransfer->from_warehouse_id, 'option_id' => $option_id];
          $this->site->setPurchaseItem($clause, $item->quantity);
          if ($delete) {
            $option_id = (isset($item->option_id) && !empty($item->option_id)) ? $item->option_id : null;
            $clause    = ['purchase_id' => null, 'transfer_id' => null, 'product_id' => $item->product_id, 'warehouse_id' => $otransfer->to_warehouse_id, 'option_id' => $option_id];
            $this->site->setPurchaseItem($clause, ($item->quantity_balance - $item->quantity));
          }
        }
      }
    }
    return $ostatus;
  }

  public function syncTransferedItem($product_id, $warehouse_id, $quantity, $option_id = null)
  {
    if ($pis = $this->site->getPurchasedItems($product_id, $warehouse_id, $option_id)) {
      $balance_qty = $quantity;
      foreach ($pis as $pi) {
        if ($balance_qty <= $quantity && $quantity > 0) {
          if ($pi->quantity_balance >= $quantity) {
            $balance_qty = $pi->quantity_balance - $quantity;
            $this->db->update('purchase_items', ['quantity_balance' => $balance_qty], ['id' => $pi->id]);
            $quantity = 0;
          } elseif ($quantity > 0) {
            $quantity    = $quantity - $pi->quantity_balance;
            $balance_qty = $quantity;
            $this->db->update('purchase_items', ['quantity_balance' => 0], ['id' => $pi->id]);
          }
        }
        if ($quantity == 0) {
          break;
        }
      }
    } else {
      $clause = ['purchase_id' => null, 'transfer_id' => null, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'option_id' => $option_id];
      $this->site->setPurchaseItem($clause, (0 - $quantity));
    }
    $this->site->syncQuantity(null, null, null, $product_id);
  }

  public function updatePurchase ($id, $data) {
    $this->db->trans_start();
    $this->db->update('purchases', $data, ['id' => $id]);
    $this->db->trans_complete();
    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  public function updatePurchasePayment ($id, $data) {
    if ($this->site->updatePayment($id, $data)) {
      return TRUE;
    }
    return FALSE;
  }

  public function updateTransfer ($id, $data = [], $items = [])
  {
    $this->db->trans_start();
    if ( ! empty($data)) {
      $ostatus = $this->resetTransferActions($id);
      $status  = $data['status'] ?? NULL;
    }
    if ($this->db->update('transfers', $data, ['id' => $id])) {
      if ( ! empty($items)) {
        $tbl = ($status != 'received' ? 'transfer_items' : ($ostatus == 'sent' ? 'transfer_items' : 'purchase_items'));
        if ($tbl) {
          $this->db->delete($tbl, ['transfer_id' => $id]);
        }
        foreach ($items as $item) {
          $item['transfer_id'] = $id;
          $item['option_id']   = !empty($item['option_id']) && is_numeric($item['option_id']) ? $item['option_id'] : null;
          if ($status == 'received') { // ori: 'completed'.
            $item['date']         = date('Y-m-d');
            $item['warehouse_id'] = $data['to_warehouse_id'];
            $item['status']       = 'received'; // ori: received.
            $item['purchase_json'] = $item['transfer_json'];
            unset($item['transfer_json']);
            $this->db->insert('purchase_items', $item);
            $this->site->addProductIncreaseHistory([
              'product_id' => $item['product_id'],
              'warehouse_id' => $data['to_warehouse_id'],
              'category' => 'Item Transfer Received',
              'quantity' => $item['quantity']
            ]);
          } else {
            $this->db->insert('transfer_items', $item);
            if ($status == 'sent') {
              $this->site->addProductDecreaseHistory([
                'product_id' => $item['product_id'],
                'warehouse_id' => $data['from_warehouse_id'],
                'category' => 'Item Transfer Sent',
                'quantity' => $item['quantity']
              ]);
            }
          }

          if ($data['status'] == 'sent' || $data['status'] == 'received') { // ori: 'completed'.
            $this->syncTransferedItem($item['product_id'], $data['from_warehouse_id'], $item['quantity'], $item['option_id']);
          }
        }
      }
    }
    $this->db->trans_complete();
    if ($this->db->trans_status() === FALSE) {
      log_message('error', 'An errors has been occurred while update transfers (Update:Procurements_model.php)');
    } else {
      return TRUE;
    }

    return FALSE;
  }

  // New Added: Without quantity sync.
  public function updateTransfer2 ($id, $data = []) {
    $this->db->trans_start();
    $this->db->update('transfers', $data, ['id' => $id]);
    $this->db->trans_complete();

    if ($this->db->trans_status() !== FALSE) {
      return TRUE;
    }
    return FALSE;
  }
}