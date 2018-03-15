<?php

namespace Sung\Mvc;

class Model {

  /**
   * Representative Table
   *
   * @var string
   */
  public $table;

  /**
   * Table Fields Information
   *
   * @var array
   */
  public $schema;

  /**
   * Additional Input Fields by Actions
   *
   * @var array
   */
  public $fields;

  /**
   * Merged Fields Configuration for an Action
   *
   * @var array
   * --------------------------------
   * Options for a field in config
   * --------------------------------
    $config = array(
      'key' => array (
        'label' => 'label of the field',
        'field' => 'databse field name', // if it's empty, it's not a database field
        'operator' => '=', // =(defult), %~(like '%~'), ~%(like '~%'), %~%(like '%~%')
        'pk' => TRUE,
        'type' => 'file', // Input Type and Value Type
        'required' => TRUE,
        'value' => '2000',
        'formatted' => '2,000.00',
        'error' => 'ERROR_CODE',
        'where' => TRUE, // if it's true, and there is value in config, then add where condition in query using operator
        'rules' => array (
          date => TRUE, // YYYY-MM-DD
          date_mdy => TRUE, // MM-DD-YY
          datetime => TRUE, // YYYY-MM-DD HH:MI:SS
          url => TRUE,
          slug => TRUE,
          extensions => array('jpg','gif'),
          file_type => array('text/csv', 'application/octet-stream', 'application/vnd.ms-excel'),
          mime_content_type => array('text/plain', 'application/vnd.ms-excel'),
          enum => array(M,F),
          email => TRUE,
          numeric => TRUE,
          array => TRUE,
          decimal => TRUE,
          length => 5,
          min_length => 5,
          max_length => 5,
          rgb_color => TRUE,
          uppercase => TRUE, // Transform the value
          lowercase => TRUE, // Transform the value
          striptags => TRUE,
          htmlencode => TRUE,
          htmldecode => TRUE, // by default, it runs htmlentities() for all parameters (at String > removeXSSFromStr)
                              // If you want to save HTML, you have to decode the value
        )
      )
    )
   */
  public $config;

  /**
   * Database handler
   *
   * @var object
   */
  public $db;

  /**
   * Database result storage
   *
   * @var object
   */
  public $data;

  /**
   * Controller/Action
   *
   * @var string
   */
  public $controller;
  public $action;

  /**
   * Default Args[]
   *
   * @var string
   */
  public $start;
  public $limit;
  public $orderby;
  public $direction;
  public $info;

  /**
   * @param string $action - action name
   *
   * @return void
   */
  public function __construct($action) {
    $this->action = $action;

    $this->mergeConfig();
    unset($this->fields);
  }

  /**
   * Default function - insert
   * 
   * @param array $options - insert options
   * @param string $mode - insert mode
   * @param boolean $not_allows_function - restriction of using mysql function
   *
   * @return boolean
   *
   * --------------------------
   * Insert Options
   * --------------------------
   * $options = array (
      'table' => 'table1',
      'id' => 'field1',
      'fields' => array(
        'field1' => 'value1',
        'field2' => 'value1',
        'field3' => 'value1'
      )
    );
   */
  public function insert($options = array(), $mode = '', $not_allows_function = false) {

    $options = $this->setDefaultTable($options);
    $options = $this->setDefaultValuesFromConfig($options);
    $options = $this->setIdFieldName($options);

    $tmp = $this->getFieldsValuesStatement($options, $not_allows_function);
    $fields = $tmp[0];
    $values = $tmp[1];

    $sql_cmd = ($mode == 'replace')? 'replace' : 'insert';
    $query = $sql_cmd.' into '.$options['table'].' ('.$fields.') values ('.$values.')';

    try {
      $stmt = $this->db->prepare($query);
      $stmt = $this->bindFieldsParams($stmt, $options);
      $stmt->execute();

      $this->setInfoFromDataResult($options, $mode);

      $row_count = $stmt->rowCount();
      if($row_count <= 0) return 'ERROR_DB_NO_AFFECTED';
      else $this->data['info']['row_count'] = $row_count;
    } catch (PDOException $exc) {
      return 'ERROR_DB_INSERT';
    }

    return true;
  }

  /**
   * Default function - update
   * 
   * @param array $options - update options
   *
   * @return boolean
   *
   * --------------------------
   * Update Options
   * --------------------------
   * $options = array (
      'table' => 'table1',
      'fields' => array(
        'field1' => 'value1',
        'field1' => 'value1',
        'field1' => 'value1'
      ),
      'where' => array (
        array ('where','field1','=','value1'),
        array ('and','field1','=','value1'),
        array ('or','field1','=','value1'),
        array ('or','field1','in',array())
      )
   );
   */
  public function update($options = array()) {
    $options = $this->setDefaultTable($options);
    $options = $this->setDefaultWhereConditions($options);
    $options = $this->setDefaultValuesFromConfig($options);

    if ($this->isWhereConditionEmpty($options)) 
      return 'ERROR_DB_NO_CONDITION';

    if (!isset($options['fields'])) 
      return 'ERROR_DB_NO_AFFECTED';

    $set = $this->getSetStatement($options);
    $where = $this->getWhereStatement($options);

    $query = 'update '.$options['table'].' set '.$set.' '.$where;

    try {
      $stmt = $this->db->prepare($query);
      $stmt = $this->bindFieldsParams($stmt, $options);
      $stmt = $this->bindWhereParams($stmt, $options);
      $stmt->execute();

      $row_count = $stmt->rowCount();
      if($row_count <= 0) return 'ERROR_DB_NO_AFFECTED';
      else $this->data['info']['row_count'] = $row_count;
    } catch (PDOException $exc) {
      return 'ERROR_DB_UPDATE';
    }

    return true;
  }

  /**
   * Default function - delete
   * 
   * @param array $options - delete options
   *
   * @return boolean
   *
   * --------------------------
   * Delete Options
   * --------------------------
   * $options = array (
      'table' => 'table1',
      'where' => array (
        array ('where','field1','=','value1'),
        array ('and','field1','=','value1'),
        array ('or','field1','=','value1')
      )
    );
   */
  public function delete($options = array()) {
    $options = $this->setDefaultTable($options);
    $options = $this->setDefaultWhereConditions($options);

    if ($this->isWhereConditionEmpty($options)) 
      return 'ERROR_DB_NO_CONDITION';

    $where = $this->getWhereStatement($options);

    $query = 'delete from '.$options['table'].' '.$where;

    try {
      $stmt = $this->db->prepare($query);
      $stmt = $this->bindWhereParams($stmt, $options);
      $stmt->execute();

      $row_count = $stmt->rowCount();
      if($row_count <= 0) return 'ERROR_DB_NO_AFFECTED';
      else $this->data['info']['row_count'] = $row_count;
    } catch (PDOException $exc) {
      return 'ERROR_DB_DELETE';
    }

    return true;
  }

  /**
   * Default function - select
   * 
   * @param array $options - select options
   *
   * @return boolean
   *
   * --------------------------
   * Select Options
   * --------------------------
   * $options = array (
     'select' => array (
       'a.field1',
       'field1',
       'field1',
       'format(field1)'
     ),
     'from' => array (
       'Lists l',
       'left join',
       'Items i',
       'on l.Id = i.listId'
     ),
     'where' => array (
       array ('where','field1','=','value1'),
       array ('and','field1','=','value1'),
       array ('or','field1','=','value1'),
       array ('or','field1','in',array())
     ),
     'groupby' => array (
       'field1',
       'field1'
     ),
     'orderby' => array (
       array ('field1','asc'),
       array ('field1','desc')
     ),
     'limit' => array (0,10)
  );
   */
  public function select($options = array()) {
    $options = $this->setDefaultWhereConditions($options);
    $options = $this->setDefaultSelectConditions($options);

    $select = $this->getSelectStatement($options);
    $from = $this->getFromStatement($options);
    $where = $this->getWhereStatement($options);
    $groupby = $this->getGroupByStatement($options);
    $orderby = $this->getOrgerByStatement($options);
    $limit = $this->getLimitStatement($options);

    $query  = ' select '.$select."\n";
    $query .= ' from '.$from."\n";
    $query .= ' '.$where."\n";
    $query .= ' '.$groupby."\n";
    $query .= ' '.$orderby."\n";
    $query .= ' '.$limit."\n";

    try {
      $stmt = $this->db->prepare($query);
      $stmt = $this->bindWhereParams($stmt, $options);
      $stmt->execute();
      $this->data['items'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (PDOException $exc) {
      return 'ERROR_DB_SELECT';
    }

    if(!isset($this->data['items']) 
      || !is_array($this->data['items']) 
      || count($this->data['items']) <= 0) 
      return 'ERROR_DB_NO_DATA';

    return true;
  }

  /**
   * @param string $query - query
   * @param array $bind - binding data
   *
   * @return mixed
   */
  public function query($query = '', $bind = array()) {
    if (empty($query)) return false;
    $command = strtoupper(substr(trim($query), 0, 6));
    if (isset($this->info) 
      && strpos($this->info, 't') !== false 
      && $command == 'SELECT' 
      && strpos($query, 'FOUND_ROWS') === false)
      $query = str_replace(substr(trim($query), 0, 6), substr(trim($query), 0, 6).' SQL_CALC_FOUND_ROWS', $query);
    
    try {
      $stmt = $this->db->prepare($query);
      if (isset($bind) && count($bind) > 0) {
        foreach ($bind as $key => $val) {
          if (strtoupper($val) == 'NULL') $val = NULL;
          $stmt->bindParam($key, $val);
        }
      }

      $stmt->execute();
      switch ($command) {
        case 'SELECT':
          $this->data['items'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
          break;
        default:
          $row_count = $stmt->rowCount();
          if($row_count <= 0) return 'ERROR_DB_NO_AFFECTED';
          else $this->data['info']['row_count'] = $row_count;
          break;
      }
    } catch (PDOException $exc) {
      return 'ERROR_DB_QUERY';
    }

    if($command == 'SELECT'
      && !isset($this->data['items']) 
      || !is_array($this->data['items']) 
      || count($this->data['items']) <= 0)
      return 'ERROR_DB_NO_DATA';

    return true;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function setDefaultTable($options) {
    if (!isset($options['table'])
      || isset($options['table'])
      && empty($options['table'])) 
      $options['table'] = $this->table;
    return $options;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function setDefaultSelectConditions($options) {
    if (!isset($options['select'])
      || (isset($options['select']) && count($options['select'])) <= 0) {
      foreach ($this->schema as $key => $item) {
        if (isset($item['field']) && !empty($item['field'])) $options['select'][] = $item['field'];
      }
    }
    return $options;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function setDefaultValuesFromConfig($options) {
    if (!isset($options['fields'])
      || isset($options['fields'])
      && count($options['fields']) <= 0) {
      foreach ($this->config as $key => $item) {
        if (isset($item['pk']) && $item['pk'] === true) continue;
        if (isset($item['field']) && isset($item['value'])) {
          $options['fields'][$item['field']] = $item['value'];
        }
      }
    }
    return $options;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function setIdFieldName($options) {
    if (!isset($options['id'])
      || (isset($options['id']) && empty($options['id']))) {
      foreach ($this->config as $key => $item) {
        if (isset($item['pk']) && $item['pk'] === true) {
          $options['id'] = $item['field'];
        }
      }
    }
    return $options;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function setDefaultWhereConditions($options) {
    if (!isset($options['where'])
      || isset($options['where'])
      && count($options['where']) <= 0
      && isset($this->config)
      && is_array($this->config)
      && count($this->config) > 0) {
      $conn = 'where';
      $config = $this->config;
      if (isset($config) && is_array($config)) {
        foreach ($config as $item) {
          if (isset($item['where']) && $item['where'] === true
            && isset($item['field']) && !empty($item['field'])
            && isset($item['value']) && !empty($item['value'])) {
            if (!isset($item['operator'])) {
              if (strtoupper($item['value']) == 'NULL') 
                $item['operator'] = 'is';
              else 
                $item['operator'] = '=';
            }
            $tmp = array($conn, $item['field'], $item['operator'], $item['value']);
            $tmp = $this->convertOperator($tmp);
            $options['where'][] = $tmp;
            $conn = 'and';
          }
        }
      }
    }    
    return $options;
  }

  /**
   * @param array $options
   *
   * @return array
   */
  private function getFieldsValuesStatement($options, $not_allows_function = false) {
    $fields = '';
    $values = '';
    foreach ($options['fields'] as $field => $value) {
      $fields .= $field.',';
      if ($not_allows_function === false && $this->isSQLFuncIn($value)) {
        $values .= $value.',';
      }else {
        $values .= ':'.str_replace('.','_',$field).',';
      }
    }
    $fields = substr($fields, 0, -1);
    $values = substr($values, 0, -1);

    return array($fields, $values);
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getSetStatement($options) {
    $set = '';
    foreach ($options['fields'] as $field => $value) {
      if ($this->isSQLFuncIn($value)) {
        $set .= $field.' = '.$value.',';
      }else {
        $alias = str_replace('.','_',$field);
        $set .= $field.' = :'.$alias.',';
      }
    }
    $set = substr($set, 0, -1);

    return $set;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getSelectStatement($options) {
    $select = '';
    if (isset($options['select'])) {
      for ($i=0; $i<count($options['select']); $i++) {
        $field = $options['select'][$i];

        if (preg_match('/^([^\.]*\.?[^ ]+)[ |\t]+as[ |\t]+(.+)$/i', $field, $matches)) {
          $field_name = $matches[1];
          $field_alias = $matches[2];
          $select .= ' '.$field;
        }else {
          $select .= ' '.$field;
        }
        if ($i != count($options['select']) - 1) $select .= ",";
        $select .= "\n";
      }
    }
    $select = substr($select, 0, -1);

    return $select;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getFromStatement($options) {
    $from = '';
    if (isset($options['from']))
      for ($i=0; $i<count($options['from']); $i++) $from .= ' '.$options['from'][$i].= "\n";
    else
      $from = $this->table;

    return $from;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getWhereStatement($options) {
    $where = '';
    if (isset($options['where'])) {
      for ($i=0; $i<count($options['where']); $i++) {
        $conjunction = $options['where'][$i][0];
        $field = $options['where'][$i][1];
        $operator = $options['where'][$i][2];
        $value = $options['where'][$i][3];

        $where .= ' '.$conjunction;
        $where .= ' '.$field.'';
        $where .= ' '.$operator;
        if (is_array($value)) {
          $where .= ' (';
          for ($ii=0; $ii<count($value); $ii++) $where .= ' :w'.$i.'a'.$ii.',';
          $where = substr($where, 0, -1);
          $where .= ') '."\n";
        }else {
          if ($this->isSQLFuncIn($value)) $where .= $value;
          else $where .= ' :w'.$i."\n";
        }
      }
    }
    return $where;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getGroupByStatement($options) {
    $groupby = '';
    if (isset($options['groupby']) && !empty($options['groupby'])) {
      for ($i=0; $i<count($options['groupby']); $i++) {
        if ($groupby == '') $groupby = ' group by ';
        else $groupby .= ',';
        $groupby .= $options['groupby'][$i]."\n";
      }
    }
    return $groupby;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getOrgerByStatement($options) {
    $orderby = '';
    if (isset($options['orderby']) && !empty($options['orderby'])) {
      for ($i=0; $i<count($options['orderby']); $i++) {
        if ($orderby == '') $orderby = ' order by ';
        else $orderby .= ',';
        $orderby .= $options['orderby'][$i][0].' '.$options['orderby'][$i][1];
      }
    }else if (isset($this->orderby)) {
      if (!isset($this->direction)) $this->direction = 'ASC';
      $orderby = ' order by '.$this->orderby.' '.$this->direction."\n";
    }
    return $orderby;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  private function getLimitStatement($options) {
    $limit = '';
    if (isset($options['limit'])) {
      if (!empty($options['limit'])) {
        $limit .= ' limit '.$options['limit'][0];
        if (isset($options['limit'][1])) $limit .= ', '.$options['limit'][1]."\n";
      }
    }else if (isset($this->start) && isset($this->limit)) {
      $limit = ' limit '.$this->start.', '.$this->limit."\n";
    }

    return $limit;
  }

  /**
   * @param array $options
   *
   * @return boolean
   */
  public function isWhereConditionEmpty($options) {
    if (!isset($options['where'])
      || isset($options['where'])
      && count($options['where']) <= 0) 
      return true;
    else 
      return false;
  }
    
  /**
   * @param object $stmt
   * @param array $options
   *
   * @return object
   */
  private function bindFieldsParams(&$stmt, $options) {
    foreach ($options['fields'] as $field => $value) {
      if ($this->isSQLFuncIn($value)) {
        // skip
      }else if (strtoupper($options['fields'][$field]) == 'NULL') {
        $stmt->bindValue(':'.str_replace('.','_',$field), NULL, \PDO::PARAM_NULL);
      }else {
        $stmt->bindParam(':'.str_replace('.','_',$field), $options['fields'][$field]);
      }
    }
    return $stmt;
  }

  /**
   * @param object $stmt
   * @param array $options
   *
   * @return object
   */
  private function bindWhereParams(&$stmt, $options) {
    if (isset($options['where'])) {
      for ($i = 0; $i < count($options['where']); $i++) {
        if (is_array($options['where'][$i][3])) {
          for ($ii = 0; $ii < count($options['where'][$i][3]); $ii++) {
            if (strtoupper($options['where'][$i][3][$ii]) == 'NULL') 
              $options['where'][$i][3][$ii] = NULL;
            $stmt->bindParam(':w'.$i.'a'.$ii, $options['where'][$i][3][$ii]);
          }
        }else {
          if (strtoupper($options['where'][$i][3]) == 'NULL') 
            $options['where'][$i][3] = NULL;
          if (strpos($options['where'][$i][3],'(') !== false 
            || strpos($options['where'][$i][3],')') !== false ) {
            if (!$this->isSQLFuncIn($options['where'][$i][3])) 
              $stmt->bindParam(':w'.$i, $options['where'][$i][3]);
          }else {
            $stmt->bindParam(':w'.$i, $options['where'][$i][3]);
          }
        }
      }
    }
    return $stmt;
  }

  /**
   * @param object $stmt
   * @param array $options
   * @param string $mode
   *
   * @return void
   */
  private function setInfoFromDataResult($options, $mode = '') {
    if ($mode == '' && isset($options['id']) && empty($options['fields'][$options['id']])) {
      $config_id = strtolower($options['id']);
      $this->config[$config_id]['value'] = $this->db->lastInsertId();
      $this->data['info'][$config_id] = $this->config[$config_id]['value'];
    }
  }

  /**
   * @param string $field - field id
   *
   * @return mixed
   */
  public function getValue($field) {
    return (isset($this->config[$field]['value']))? $this->config[$field]['value'] : '';
  }

  /**
   * @return array
   */
  public function getDataResult() {
    return $this->data;
  }

  /**
   * @return void
   */
  public function clearDataResult() {
    $this->data = array();
  }

  /**
   * @return array
   */
  public function getDataItems() {
    return $this->data['items'];
  }

  /**
   * @param string $field - field id
   *
   * @return void
   */
  public function setError($field, $error) {
    $this->config[$field]['error'] = $error;
  }

  /**
   * @param string $field - field id
   * @param string $value - value of field to set
   *
   * @return boolean
   */
  public function setValue($field, $value) {
    switch($field) {
      case 'controller':
        $this->controller = $value;
        break;
      case 'action':
        $this->action = $value;
        break;
      case 'start':
        $this->start = $value;
        break;
      case 'limit':
        $this->limit = $value;
        if (!isset($this->start)) $this->start = 0;
        break;
      case 'orderby':
        $this->orderby = $value;
        break;
      case 'direction':
        $this->direction = $value;
        break;
      default:
        if (!isset($this->config[$field])) return false;
        $this->config[$field]['value'] = $value;
        break;
    }

    return true;
  }

  /**
   * @param reference $args
   *
   * @return boolean
   */
  public function setValues(& $args) {
    if (!isset($this->config) || !is_array($this->config)) return false;
    if (isset($args['c'])) {
      $this->controller = $args['c'];
    }

    // Set Values
    foreach ($this->config as $key => $options){
      if (isset($args[$key])) {
        $this->config[$key]['value'] = $args[$key];
      }
    }

    // Set value from id
    foreach ($this->config as $key => $options){
      if(!isset($options['value']) && empty($options['value'])
        && isset($options['value_from']) && !empty($options['value_from'])
        && isset($this->config[$options['value_from']]['value']) && !empty($this->config[$options['value_from']]['value'])) {
        $this->config[$key]['value'] = $this->config[$options['value_from']]['value'];
      }
    }

    return true;
  }

  /**
   * @return boolean
   */
  public function mergeConfig() {
    if (empty($this->action)) return false;
    if (!isset($this->fields[$this->action]) || !is_array($this->fields[$this->action])) return false;
    foreach ($this->fields[$this->action] as $key => $val){
      if (isset($this->schema[$key])) {
        $this->config[$key] = array_merge($this->schema[$key], $this->fields[$this->action][$key]);
      }else {
        $this->config[$key] = $this->fields[$this->action][$key];
      }
    }
    return true;
  }

  /**
   * @param string $value - sql funciton name
   *
   * @return boolean
   */
  public function isSQLFuncIn($value) {
    if (strpos($value,'(') !== false || strpos($value,')') !== false ) {
      $matches = array();
      preg_match('/([A-Z|a-z|0-9|_]+)\(/', $value, $matches);
      if (isset($matches[1]) && $this->db->isMySQLFunc($matches[1])) {
        return true;
      }else {
        return false;
      }
    }
  }

  /**
   * @param string $item - internal operator
   *
   * @return string - real operator
   *
   * --------------------------
   * Type of Internal Operator
   * --------------------------
   * %~ - like '%...'
   * ~% - like '...%'
   * %~% - like '%...%'
   */
  public function convertOperator($item) {
    if (!isset($item[2])) return $item;

    switch ($item[2]) {
      case '%~':
        $item[2] = 'like';
        $item[3] = '%'.$item[3];
        break;
      case '~%':
        $item[2] = 'like';
        $item[3] = $item[3].'%';
        break;
      case '%~%':
        $item[2] = 'like';
        $item[3] = '%'.$item[3].'%';
        break;
    }
    return $item;
  }

  /**
   * @param string $config_key
   *
   * @return boolean
   */
  public function isSetVal($config_key) {
    if (isset($this->config[$config_key]['type']) && !empty($this->config[$config_key]['type'])) 
      $value_type = $this->config[$config_key]['type'];
    else
      $value_type = '';

    switch ($value_type) {
      case 'file':
        if (isset($this->config[$config_key]['value'])
          && is_array($this->config[$config_key]['value'])
          && $this->config[$config_key]['value']['size'] > 0
          && $this->config[$config_key]['value']['error'] == 0) 
          return true;
        break;
      default:
        if (isset($this->config[$config_key]['value']) && !empty($this->config[$config_key]['value'])) 
          return true;
    }

    return false;
  }

}