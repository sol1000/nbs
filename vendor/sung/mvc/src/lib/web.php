<?php

namespace Sung\Mvc;

class Web {

  public static $args = array();

  public static function getArgsFromUri($uri) {
    $dir_params = preg_replace('#/([^/]+)/([^/]+)/?#', '', $uri);
    $dir_params = preg_replace('#\?.*$#', '', $dir_params);
    if (isset($dir_params) && !empty($dir_params)) {
      $dir_params = explode('/', $dir_params);
      for ($i=0; $i<count($dir_params); $i = $i+2) {
        if (isset($dir_params[$i]) && isset($dir_params[$i+1])) {
          $_GET[$dir_params[$i]] = urldecode($dir_params[$i+1]);
        }
      }
    }
    return $_GET;
  }

  public static function setArgs() {
    global $_GET;
    global $_POST;
    global $_FILES;

    if (isset($_GET)) {
      foreach ($_GET as $key => $val) {
        self::$args[$key] = self::FilterInputData($val);
      }
    }
    if (isset($_POST)) {
      foreach ($_POST as $key => $val) {
        self::$args[$key] = self::FilterInputData($val);
      }
    }
    if (isset($_FILES)) {
      foreach ($_FILES as $key => $val) {
        self::$args[$key] = self::FilterInputData($val);
      }
    }
    return true;
  }

  private static function FilterInputData($str) {
    if (is_array($str)) {
      foreach($str as $key => $val) {
        $str[$key] = self::FilterInputData($val);
      }
    }else {
      $str = Str::FilterInputData($str);
    }
    return $str;
  }

  public static function getArgs($var = '') {
    $result = false;
    if (count(self::$args) <= 0) {
      self::setArgs();
    }
    if (isset($var) && !empty($var)) {
      if (isset(self::$args[$var])) {
        $result = self::$args[$var];
      }

    }else {
      $result = self::$args;
    }
    return $result;
  }

  public static function getArg($var) {
    return self::getArgs($var);
  }
  
  public static function getClassName($filename) {
    $arr_filename = explode('_', $filename);
    $class_name = '';
    for ($i=0; $i < count($arr_filename); $i++) {
      $arr_filename[$i] = ucfirst($arr_filename[$i]);
      $class_name .= $arr_filename[$i];
    }
    return $class_name;
  }
}