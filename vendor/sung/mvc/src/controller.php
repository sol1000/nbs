<?php

namespace Sung\Mvc;

use Sung\Mvc\DB;
use Sung\Mvc\Http;
use Sung\Mvc\Str;
use Sung\Mvc\Web;
use Sung\Mvc\Validate;
use Sung\Mvc\JSONEncode;

class Controller {
  protected $db;
  protected $args;
  protected $controller;
  protected $action;
  protected $model;
  protected $output;
  protected $messages;

  /**
   * @return void
   */
  public function __construct() {
    $this->setArgs();
  }

  /**
   * @return string
   */
  public function getArgs($name) {
    return (isset($this->args[$name]))? $this->args[$name] : '';
  }

  /**
   * @return mixed - The result of setExtraArgs()
   * 
   * --------------------------
   * Default parameters of API
   * --------------------------
   * c - controller
   * a - action
   * f - output format
   * v - view file name
   * cb - callback function name
   * s - start row
   * p - page number
   * l - limit per page
   * o - order by
   * d - order by direction
   * i - info(t:total)
   */
  public function setArgs() {
    $this->args = Web::getArgs();

    if (isset($this->args['v'])) {
      $this->args['v'] = $this->sanitizeFilename($this->args['v']);
    }

    if (isset($this->args['p']) && !isset($this->args['s'])) {
      $this->args['s'] = $this->args['p'] * $this->args['l'] - $this->args['l'];
    }else if (!isset($this->args['p']) && isset($this->args['s'])) {
      if ($this->args['s'] == 0) {
        $this->args['p'] = 1;
      } else {
        $this->args['p'] = floor($this->args['s']/$this->args['l']) + 1;
      }
    }else {
      $this->args['s'] = 0;
      $this->args['p'] = 1;
    }
    $this->controller = $this->args['c'];
    $this->action = $this->args['a'];

    return $this->setExtraArgs();
  }

  /**
   * @param string $filename - original view filename
   *
   * @return string - sanitized view filename
   */
  private function sanitizeFilename($filename) {
    return str_replace('..', '', $this->args['v']);
  }

  /**
   * @return mixed - Custom
   */
  public function setExtraArgs() {}

  /**
   * @return boolean
   */
  private function run() {
    try{
      if (!$this->setDB()) {
        $this->Error('DB_ERRORS', 'ERROR_DB_CONNECTION');
        return false;
      }
    }catch (PDOException $e) {
      $this->Error('DB_ERRORS', 'ERROR_DB_CONNECTION');
      return false;
    }
    if (USE_MEMCACHED === true) $this->setMemcached();
    $this->loadMessages();
    $this->setModel();
    if (!$this->Validate($this->model->config)) {
      $this->Error('INPUT_ERRORS');
      return false;
    }

    return true;
  }

  /**
   * @param string $action - action name
   *
   * @return boolean
   */
  public function runAction($action) {
    $result = true;
    if (method_exists($this, $action)) {
      // run set model
      $this->run();
      // if the action fields are not defined, do not execute action.
      if (isset($this->model->config)) {
        if (!$this->isError()) {
          try {
            eval('$this->'.$action.'();');
          }catch (ErrorException $e) {
            $result = 'ERROR_NO_ACTION';
          }
        }else {
          return $this->output['errors']['code'];
        }
      }else {
        $this->output['errors']['code'] = 'ERROR_NO_ACTION_FIELDS_DEFINED';
        return $this->output['errors']['code'];
      }
    }else {
      $result = false;
    }

    return $result;
  }

  /**
   * @return boolean
   */
  private function setDB() {
    try {
      $this->db = new DB(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
      if (!$this->db) {
        return false;
      }
      $stmt = $this->db->prepare("SET character_set_connection = 'utf8'");
      $stmt->execute();
    } catch (PDOException $e) {
      return false;
    }
    $this->clearDBInfo();
    return true;
  }

  /**
   * @return boolean
   */
  private function clearDBInfo() {
    $this->db->host = '';
    $this->db->port = '';
    $this->db->name = '';
    $this->db->user = '';
    $this->db->pass = '';

    return true;
  }

  /**
   * @param string $controller
   * @param string $action
   * @param reference $db
   * @param reference $args
   * 
   * @return Object - Model
   */
  public function getModel($controller, $action, &$db = NULL, &$args = NULL) {
    if (empty($action)) return false;

    $model_file = ROOT.'/src/models/'.$controller.'_model.php';
    if (!file_exists($model_file)) {
      return false;
    }
    require $model_file;

    try {
      $model_class = Web::getClassName($controller);
      $model_class .= 'Model';
      eval('$model = new '.$model_class.'(\''.$action.'\');');
      if ($args !== NULL) {
        $model->setValues($this->args);
      }
    } catch (Exception $exc) {
      return false;
    }

    if (is_null($db)) {
      $model->db = & $this->db;
    }else {
      $model->db = & $db;
    }

    return $model;
  }

  /**
   * @return boolean
   */
  public function setModel() {
    $this->model = $this->getModel($this->controller, $this->action, $this->db, $this->args);
    $this->setSLOD($this->args, $this->model);

    return true;
  }

  /**
   * @param reference $args
   * @param reference $model
   * 
   * @return boolean
   */
  private function setSLOD(& $args, & $model) {
    if (isset($args['s']) && !empty($args['s'])) $model->start = $args['s'];
    if (isset($args['l']) && !empty($args['l'])) $model->limit = $args['l'];
    if (isset($args['o']) && !empty($args['o'])) {
      if (isset($model->schema[$args['o']]['field']) && !empty($model->schema[$args['o']]['field'])) {
        $model->orderby = $model->schema[$args['o']]['field'];
      }else {
        $model->orderby = $args['o'];
      }
    }
    if (isset($args['d']) && !empty($args['d'])) $model->direction = $args['d'];
    if (isset($args['i']) && !empty($args['i'])) $model->info = $args['i'];

    if (!isset($model->start) || $model->start < 0) $model->start = 0;
    if (!isset($model->limit) || $model->limit > SELECT_LIMIT) $model->limit = SELECT_LIMIT;
    return true;
  }

  /**
   * @param reference $config
   * 
   * @return boolean
   */
  private function Validate(& $config) {
    if ($this->isError()) return false;
    if (empty($this->action)) return true;
    if (!isset($config)) return true;

    $ok = true;
    foreach($config as $field => $options) {

      // Required
      if (isset($options['required']) && $options['required'] !== false) {
        if (isset($options['rules']['file']) && $options['rules']['file'] === true) {
          if (!is_array($options['value'])
            ||!isset($options['value']['name'])
            ||empty($options['value']['name'])) {
            $config[$field]['error'] = 'ERROR_REQUIRED';
            $ok = false;
          }
        }else if(isset($options['value']) && is_array($options['value'])) {
          $tmp_ok = false;
          for($i=0; $i<count($options['value']); $i++) {
            if (isset($options['value'][$i]) && !empty($options['value'][$i])) $tmp_ok = true;
          }
          if (!$tmp_ok) {
            $config[$field]['error'] = 'ERROR_REQUIRED';
            $ok = false;
          }
        }else if (isset($options['value']) && $options['value'] === '0') {
          // value = 0 is not empty
        }else {
          if (!isset($options['value']) || empty($options['value'])) {
            $config[$field]['error'] = 'ERROR_REQUIRED';
            $ok = false;
          }
        }
      }

      // Check Next Field
      if (isset($config[$field]['error']) && !empty($config[$field]['error'])) continue;

      // Rules
      if (isset($options['rules']) && count($options['rules'])) {
        foreach ($options['rules'] as $rule => $rule_val) {
          if (!isset($rule_val) || !$rule_val) continue;
          if (!isset($options['value'])) continue;
          if (isset($config[$field]['error']) && !empty($config[$field]['error'])) continue;

          $validate = Validate::check($rule, $rule_val, $options['value']);

          if (!$validate['result']) {
            $config[$field]['error'] = $validate['error'];
            $ok = false;
            continue;
          }else if (isset($validate['value']) && !empty($validate['value'])){
            $config[$field]['value'] = $validate['value'];
          }
        }
      }
    }

    if ($this->extraValidate() === false) {
      $ok = false;
    }
    return $ok;
  }

  /**
   * @return boolean
   */
  public function extraValidate() {return true;}

  /**
   * @return boolean
   */
  public function loadMessages() {
    $ini_file = ROOT.'/lang/'.LANG.'/message.ini';
    $this->messages = parse_ini_file($ini_file);
    return true;
  }

  /**
   * @param string $filename - View filename
   * 
   * @return boolean
   */
  public function Render($filename = '') {
    if (isset($this->args['v']) && !empty($this->args['v'])) {
      $filename = $this->args['v'];
    }
    $this->setReturnArgs();
    $this->beforeRender();
    $this->exeRender($this->output, $filename);
    $this->afterRender();
    return true;
  }

  public function beforeRender() {}
  public function afterRender() {}

  /**
   * @return boolean
   */
  public function setReturnArgs() {
    if (!isset($this->model->config)) return false;

    if (defined('RETURN_ARGS') && RETURN_ARGS === TRUE) {
      $this->output['info']['args'] = $this->args;
    }

    return true;
  }

  /**
   * @param object $output - collected output object
   * @param string $filename - view filename
   * 
   * @return boolean
   */
  public function exeRender($output, $filename='') {
    if (Web::getArg('f') == 'path' && !empty($filename)) {
      header('Content-type: text/html; charset=utf-8');
      if((@include ROOT.'/src/views/'.$filename) === false) 
        Controller::StaticError('INIT_ERRORS', 'ERROR_NO_VIEW_FILE');
    }else if (!empty($filename)) {
      header('Content-type: text/html; charset=utf-8');
      if((@include ROOT.'/src/views/'.Web::getArg('c').'/'.Web::getArg('a').'/'.$filename) === false) 
        Controller::StaticError('INIT_ERRORS', 'ERROR_NO_VIEW_FILE');
    }else {
      header('Content-type: text/json; charset=utf-8');
      $je = new JSONEncode();
      echo $je->runEncode($output);
    }
    return true;
  }

  /**
   * @param string $cate - Error category
   * @param string $error - Error code
   * 
   * @return void
   */
  public function Error($cate, $error = '') {
    if ($this->isError()) return false; // to make sure call only one time

    $this->output['result'] = 0;

    switch ($cate) {
      case 'INPUT_ERRORS': // input error need to show multiple errors
        $this->output['errors']['code'] = 'ERROR_INPUT';
        $this->output['errors']['text'] = $this->getMessageFromINI($cate, $this->output['errors']['code']);
        if (isset($this->model->config)) {
          foreach ($this->model->config as $key => $options) {
            if (isset($options['error'])) {
              if (!isset($options['value'])) $options['value'] = '';
              $this->output['errors']['fields'][$key]['code'] = $options['error'];
              $this->output['errors']['fields'][$key]['text'] = $this->getMessage($cate, $options['error'], $options['value']);
            }
          }
        }
        break;
      default :
        if (!empty($error)) {
          $this->output['errors']['code'] = strtoupper($error);
        }else {
          $this->output['errors']['code'] = strtoupper($cate);
        }
        $this->output['errors']['text'] = $this->getMessageFromINI($cate, $error);
        break;
    }

    $this->Render();
    exit;
  }

  /**
   * @param string $cate - Message category
   * @param string $code - Message code
   * @param string $value - Custom message
   * 
   * @return string
   */
  public function getMessage($cate, $code, $value) {
    $message = $this->getMessageFromINI($cate, $code);
    if (!is_array($value)) {
      $message = str_replace('#value#', $value, $message);
    }
    return $message;
  }

  /**
   * @param string $cate - Message category
   * @param string $code - Message code
   * 
   * @return string
   */
  public function getMessageFromINI($cate, $code) {
    $ini_file = ROOT.'/lang/'.LANG.'/message.ini';
    $messages = parse_ini_file($ini_file);
    if (isset($messages[$code])) return $messages[$code];
    else return $code;
  }

  /**
   * @param string $cate - Error category
   * @param string $error - Error code
   * 
   * @return void
   */
  public static function StaticError($cate, $error = '') {
    $error_msg = Controller::getMessageFromINI($cate, $error);
    $output = array('result' => 0,
      'errors' => array(
        'code' => $error,
        'text' => $error_msg
      ));

    Controller::exeRender($output);

    exit;
  }

  /**
   * @return boolean
   */
  public function isError() {
    if (isset($this->output['result'])
      && $this->output['result'] == 0
      && isset($this->output['errors'])) {
      return true;
    }else {
      return false;
    }
  }

}