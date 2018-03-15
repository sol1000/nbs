<?php

namespace Sung\Mvc;

class Str {
    public static function removeXSSFromStr($str){
        $str = htmlspecialchars_decode($str);
        $str = htmlspecialchars_decode($str);
        return htmlentities($str);
    }
    public static function convertUnicodeToWeb($str){
        $encoding = mb_detect_encoding($str);
        if ($encoding != 'UTF-8') {
            $str = iconv('UTF-8', $encoding, $str);
            $str = mb_convert_encoding($str, 'UTF-8', $encoding);
        }

        return preg_replace('/\\\\u([0-9a-fA-F]{4})/', '&#x$1; ', $str);
    }
    public static function HtmlToTxt($html){
        if (is_array($html)) return $html;
        $html = htmlentities($html);
        $text = stripslashes($html);
        return $text;
    }
    public static function removeSpecialCharaters($str){
        $new_str = preg_replace('/[^A-Za-z0-9\-\.\_]/', '', $str);
        return $new_str;
    }
    public static function SpaceForWeb($text){
        $text = str_replace(' ', '&nbsp;', $text);
        $text = preg_replace('/\t/', '&nbsp;&nbsp;&nbsp;&nbsp;', $text);
        $html = preg_replace('/\n/', '<br>', $text);
        return $html;
    }
    public static function showHTMLCode($html) {
        return Str::SpaceForWeb(Str::HtmlToTxt($html));
    }
    public static function ArrayToXML($array) {
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><root></root>");
        Str::ArrayToXMLAddChild($array, $xml);
        return $xml->asXML();
    }
    public static function ArrayToXMLAddChild($array, & $xml) {
        if (!is_array($array)) return false;

        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(is_numeric($key)) $key = 'item';
                $subnode = $xml->addChild("$key");
                Str::ArrayToXMLAddChild($value, $subnode);
            } else {
                $xml->addChild($key, $value);
            }
        }
    }
    public static function FilterInputData($str) {
        $str = Str::removeXSSFromStr($str);
        return $str;
    }

}