<?php
namespace Sung\Mvc;

class Http {

    public function getJSON($url) {
        $response = $this->getURL($url);
        return json_decode($response);
    }

    public function getURL($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (isset($data) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, count($data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->makePostParams($data));
        }

        $response = curl_exec ($ch);

        if (curl_errno($ch)) {
            return false;
        } else {
            curl_close($ch);
            return $response;
        }
    }
}