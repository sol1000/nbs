<?php
namespace Sung\Mvc;

class JSONEncode {

    public function getMode($arr) {
        $mode = 'array';
        $keys = array_keys($arr);
        foreach($keys as $key) {
            if (!is_numeric($key)) {
                $mode = 'object';
                break;
            }
        }
        return $mode;
    }

    public function addItem($json, $key, $val, $mode) {
        if ($json != '') $json .= ',';
        $key = $this->checkVal($key);
        $val = $this->checkVal($val);

        if ($mode == 'object') {
            $json .= '"'.$key.'"';
            $json .= ':';
            if (is_numeric($val)) {
                $json .= $val;
            }else {
                $json .= '"'.$val.'"';
            }
        }else {
            $json .= '"'.$val.'"';
        }
        return $json;
    }

    public function addSubJSON($json, $key, $mode, $sub_json) {
        if ($json != '') $json .= ',';
        if ($mode == 'object') {
            $json .= '"'.$key.'"'.':'.$sub_json;
        }else {
            $json .= $sub_json;
        }
        return $json;
    }

    public function addWrapping($json, $mode) {
        if ($mode == 'object') {
            $json = '{'.$json.'}';
        }else {
            $json = '['.$json.']';
        }

        return $json;
    }

    public function runEncode($arr) {
        try {
            $json = json_encode($arr);
        }catch (ErrorException $e) {
            $json = '';
        }

        if (!empty($json)) {
            return $json;
        }

        $json = '';
        $mode = $this->getMode($arr);
        foreach($arr as $key => $val) {
            if (is_array($val)) {
                $sub_json = $this->runEncode($val);
                $json = $this->addSubJSON($json, $key, $mode, $sub_json);
            }else {
                $json = $this->addItem($json, $key, $val, $mode);
            }
        }
        $json = $this->addWrapping($json, $mode);
        return $json;
    }

    public function checkVal($val) {
        $val =  str_replace ('"', '\"', $val);
        $val =  preg_replace ("/\r/", "\\r", $val);
        $val =  preg_replace ("/\n/", "\\n", $val);

        $val = mb_convert_encoding($val,'UTF-8','UTF-8');
        return $val;
    }
}