<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-11-02
 * Time: 오후 6:36
 */

use Firebase\JWT\JWT;

include_once(__DIR__ . '/../vendor/autoload.php');
include_once __DIR__ . '/../vendor/firebase/php-jwt/src/ExpiredException.php';
//include_once(__DIR__ . '/../common.php');
class DahamiToken
{
    private $user_id = '';
    private $token = '';
    private $secret_key = 'dahami';
    private $exp = 60;
    private $data = array();

    public function __construct()
    {
    }

    private function getKey(){
        $key = $this->secret_key;
        while (strlen($key) < 24) {
            $key .= $key;
        }
        if (strlen($key) > 24) {
            $key = substr($key, 0, 24);
        }
        return $key;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        $now_seconds = time();
        $payload = array(
            "iss" => $this->data['_USER_ID'], //토큰 발행자 정보
            "iat" => $now_seconds,  // 발급시간
            "exp" => $now_seconds + (60 * $this->getExp()),  // 만료 시간  : 1시간 - def:60
            "claims" => $this->data
        );
        // logs('getToken.exp : ' . $this->getExp());
        // logs('getToken.rst : ' . $now_seconds + (1 * $this->getExp()));
        return JWT::encode($payload, $this->getKey());
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }


    /**
     * @param string $token
     */
    public function getData($token)
    {
        if(!$token)
            return '';

        try {
            $rst = JWT::decode($token, $this->getKey(), array('HS256'));
        } catch (Exception $e) {
            return '';
        }
        return $rst;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getExp()
    {
        return $this->exp;
    }

    /**
     * @param int $exp
     */
    public function setExp($exp)
    {
        $this->exp = $exp;
    }




    public function getHeaderAuth(){
        $token = null;
        if (function_exists('apache_request_headers')) {
          $headers = apache_request_headers();
        } else {
          $arh = array();
          $rx_http = '/\AHTTP_/';
          foreach ($_SERVER as $key => $val) {
            if ( preg_match($rx_http, $key) ) {
              $arh_key = preg_replace($rx_http, '', $key);
              $rx_matches = array();
              $rx_matches = explode('_', $arh_key);
              if ( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                foreach ($rx_matches as $ak_key => $ak_val)
                  $rx_matches[$ak_key] = ucfirst($ak_val);
                $arh_key = implode('-', $rx_matches);
              }
              $arh[$arh_key] = $val;
            }
          }
          $headers = $arh;
        }
        if (isset($headers['Authorization'])) {
            $token = $headers['Authorization'];
        } else if (isset($headers['authorization'])) {
            $token = $headers['authorization'];
        } else if (isset($headers['AUTHORIZATION'])) {
            $token = $headers['AUTHORIZATION'];
        }
        return $token;

    }
}
