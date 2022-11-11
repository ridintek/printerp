<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Datatable library for DataTables v1.10.22 Server-Side Processing.
 * Codeigniter: 3.x
 * Author: Riyan Widiyanto
 */
class Datatable
{
  const FIRST_COLUMN = 0;
  const LAST_COLUMN = -1;

  /**
   * @var array
   */
  private $addColumns;
  private $editColumns;
  protected $draw;
  protected $columns;
  protected $isFiltered = FALSE;
  protected $joins;
  protected $order;
  protected $returnObject = FALSE;
  protected $search;
  protected $start;
  protected $table;

  public function __construct()
  {
    $this->ci = &get_instance();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->draw   = $this->ci->input->get('draw') ?? 0;
      $this->length = $this->ci->input->get('length') ?? 0;
      $this->order  = $this->ci->input->get('order') ?? [];
      $this->start  = $this->ci->input->get('start') ?? 0;
      $this->search = $this->ci->input->get('search');
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->draw   = $this->ci->input->post('draw') ?? 0;
      $this->length = $this->ci->input->post('length') ?? 0;
      $this->order  = $this->ci->input->post('order') ?? [];
      $this->start  = $this->ci->input->post('start') ?? 0;
      $this->search = $this->ci->input->post('search');
    }

    $this->draw = intval($this->draw);
  }

  /**
   * Add new column or overwrite existing column.
   * @param string $name New column name or replace existing column.
   * @param string $columns Existing column name to manipulate. Columns separated by comma.
   * @param \Closure $callback Callback to manipulate existing columns.
   * @param int $offset Offset position to insert new column. Add one (1) to replace existing column.
   *
   * @example 1
   * DataTables::addColumn('new_column', 'id, username', function ($data) {
   *  return "ID {$data['id']} is {$data['username']}";
   * }, 1); // Now column 'new_column will return ID 1 is admin at offset 1.
   */
  public function addColumn(string $name, string $columns, \Closure $callback, int $offset = self::LAST_COLUMN)
  {
    $this->addColumns[] = [
      'name'     => $name,
      'columns'  => $columns,
      'callback' => $callback,
      'offset'   => $offset
    ];
    return $this;
  }

  /**
   * Send response as object.
   */
  public function asObject()
  {
    $this->returnObject = TRUE;
    return $this;
  }

  public function compile()
  {
    return $this->generate(['returnCompiled' => TRUE]);
  }

  public function editColumn($name, \Closure $callback)
  {
    $this->editColumns[] = [
      'name'     => $name,
      'callback' => $callback,
    ];
    return $this;
  }

  /**
   * Filter available columns.
   * @param string $type Filter type [like, or_like, where, or_where]. Default 'like'.
   * @param mixed $clauses Filter clauses.
   * @param string $val Filter value to search.
   * @param string $wildcard Wildcard only available for 'like', 'not_like' and 'or_like'.
   */
  public function filter($type, $clauses, $val = NULL, $wildcard = 'none')
  {
    $this->isFiltered = TRUE;
    if ( ! $type) $type = 'like';
    if ($type == 'like') {
      $this->like($clauses, $val, $wildcard, FALSE);
    }
    if ($type == 'not_like') {
      $this->not_like($clauses, $val, $wildcard, FALSE);
    }
    if ($type == 'or_like') {
      $this->or_like($clauses, $val, $wildcard, FALSE);
    }
    if ($type == 'or_where') {
      $this->or_where($clauses, $val, FALSE);
    }
    if ($type == 'where') {
      $this->where($clauses, $val, FALSE);
    }
    if ($type == 'where_in') {
      $this->where_in($clauses, $val, FALSE);
    }
    if ($type == 'where_not_in') {
      $this->where_not_in($clauses, $val, FALSE);
    }
    return $this;
  }

  public function from($table)
  {
    $this->table = $table;
    $this->ci->db->from($table);
    return $this;
  }

  /**
   * Generate output for datatables.
   *
   * @param array $options [ bool returnArray, bool returnCompiled]
   */
  public function generate($options = [])
  {
    $data = [];
    $recordsTotal = 0;
    $recordsFiltered = 0;
    $result = [];

    try {
      $recordsTotal = $this->ci->db->count_all_results(NULL, FALSE); // Execute Query.
    } catch (Exception $err) {
      die($err->getMessage());
    }


    // If search activated.
    if ($this->search && $this->search['value']) {
      $this->isFiltered = TRUE;
      $src = $this->search['value'];

      $this->ci->db->group_start();

      foreach ($this->getColumns($this->columns) as $col) {
        if (!empty($col)) { // Important!
          $this->ci->db->or_like($col, $src, 'both', FALSE);
        }
      }

      $this->ci->db->group_end();
    }

    $recordsFiltered = $this->ci->db->count_all_results(NULL, FALSE); // Execute Query.

    if ($this->order) {
      $col = 0;
      $ord = '';

      foreach ($this->order as $sort) {
        $col = intval($sort['column']) + 1; // Since 1 is same as first column for MySQL.
        if ($sort['dir'] === 'asc') {
          $ord = 'ASC';
        } else if ($sort['dir'] === 'desc') {
          $ord = 'DESC';
        }
      }

      $this->ci->db->order_by($col, $ord);
    }

    if ($this->length > 0) {
      $this->ci->db->limit($this->length, $this->start);
    }

    if (isset($options['returnCompiled']) && $options['returnCompiled']) {
      return $this->ci->db->get_compiled_select();
    }

    $q = $this->ci->db->get();

    if (!$q) {
      echo('DataTable Error: ');
      print_r($this->ci->db->error());
      die();
    }

    $rows = $q->result_array();

    foreach ($rows as $row) {
      if ($this->addColumns) {
        foreach ($this->addColumns as $addColumn) {
          if ($addColumn['offset'] < 0) $addColumn['offset'] = count($row);

          $front = array_slice($row, 0, $addColumn['offset']);
          $back  = array_slice($row, $addColumn['offset']);

          $str = '';
          $cols = explode(',', $addColumn['columns']);

          foreach ($cols as $col) {
            $col = trim($col);
            $data[$col] = $row[$col];
          }

          $str = $addColumn['callback']($data);

          $cleanStr = preg_replace('/([\n\r\t])/', '', $str);

          $row = array_merge($front, [$addColumn['name'] => trim($cleanStr)], $back);
        }
      }

      if ($this->editColumns) {
        foreach ($this->editColumns as $editColumn) {
          if (array_key_exists($editColumn['name'], $row)) {
            if (is_callable($editColumn['callback'])) {
              $row[$editColumn['name']] = $editColumn['callback']($row);
            }
          }
        }
      }

      $result[] = ($this->returnObject ? $row : array_values($row)); // Default as Array.
    }

    if (isset($options['returnArray']) && $options['returnArray']) {
      return $result;
    }

    die(json_encode([
      'data' => $result,
      'draw' => $this->draw,
      'recordsFiltered' => $recordsFiltered,
      'recordsTotal' => $recordsTotal
    ], JSON_PRETTY_PRINT));
  }

  /**
   * Retrieve columns name from sql query.
   * @param string $columns SQL Query from select statement.
   */
  protected function getColumns($columns)
  {
    $len = strlen($columns);
    $brackets = 0;
    $res = []; $word = '';

    if ($len > 0) {
      for ($a = 0; $a < $len; $a++) {
        $char = substr($columns, $a, 1);

        if ($char == ',' && !$brackets) {
          $res[] = trim(preg_split('/ as /i', $word)[0]);
          $word = '';
        } else {
          if ($char == '(') {
            $brackets++;
          }

          if ($char == ')') {
            $brackets--;
          }

          $word .= $char;
        }
      }

      $res[] = trim(preg_split('/ as /i', $word)[0]);
    }

    return $res;
  }

  public function group_by($column)
  {
    $this->ci->db->group_by($column);
    return $this;
  }

  public function group_end()
  {
    $this->ci->db->group_end();
    return $this;
  }

  public function group_start()
  {
    $this->ci->db->group_start();
    return $this;
  }

  public function join($table, $on, $type = 'left')
  {
    $this->joins[] = [$table, $on, $type];
    $this->ci->db->join($table, $on, $type);
    return $this;
  }

  public function like($clauses, $val = NULL, $wildcard = 'none', $backtick_protect = TRUE)
  {
    $this->ci->db->like($clauses, $val, $wildcard, $backtick_protect);
    return $this;
  }

  public function limit($limit, $offset = NULL)
  {
    if ($offset) {
      $this->ci->db->limit($limit, $offset);
    } else if ( ! $offset) {
      $this->ci->db->limit($limit);
    }
    return $this;
  }

  public function not_like($clauses, $val = NULL, $wildcard = 'none', $backtick_protect = TRUE)
  {
    $this->ci->db->not_like($clauses, $val, $wildcard, $backtick_protect);
    return $this;
  }

  public function or_like($clauses, $val = NULL, $wildcard = 'none', $backtick_protect = TRUE)
  {
    $this->ci->db->or_like($clauses, $val, $wildcard, $backtick_protect);
    return $this;
  }

  public function or_where($clauses, $val = NULL, $backtick_protect = TRUE)
  {
    if ($val) {
      $this->ci->db->or_where($clauses, $val, $backtick_protect);
    } else if ( ! $val) {
      $this->ci->db->or_where($clauses, NULL, $backtick_protect);
    }
    return $this;
  }

  public function select($columns, $backtick_protect = TRUE)
  {
    $this->columns = $columns;
    $this->ci->db->select($columns, $backtick_protect);
    return $this;
  }

  public function where($clauses, $val = NULL, $backtick_protect = TRUE)
  {
    if ($val) {
      $this->ci->db->where($clauses, $val, $backtick_protect);
    } else if ( ! $val) {
      $this->ci->db->where($clauses, NULL, $backtick_protect);
    }
    return $this;
  }

  public function where_in($column, $items = [], $escape = TRUE)
  {
    $this->ci->db->where_in($column, $items, $escape);
    return $this;
  }

  public function where_not_in($column, $items = [], $escape = TRUE)
  {
    $this->ci->db->where_not_in($column, $items, $escape);
    return $this;
  }
}
