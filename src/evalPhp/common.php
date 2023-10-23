<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-30
 * Time: 오전 11:57
 */
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization');
header('Content-Type: application/json');
include_once __DIR__ . '/responseHeader.php';
include_once(__DIR__ . '/lib/DahamiToken.php');

$now_time = time();
$DahamiToken = new DahamiToken();
$headerAuth = $DahamiToken->getHeaderAuth();
$success = false;
$message = '회원 정보를 찾을 수 없습니다.';

if ($headerAuth !== null) {
    $headerData = $DahamiToken->getData($headerAuth);
    $auth_check = true;
    logs('common.curtime : ' . microtime());
    logs('common.header : ' . json_encode($headerData));
    if ($headerData) {
        if ($headerData->exp < $now_time) {
            $auth_check = false;
            $message = '세션 기간이 만료되었습니다.';
        }
        if ($headerData->claims->_SERVER_IP !== $_SERVER['REMOTE_ADDR']) {
            $auth_check = false;
            $message = '접속 정보가 변경되었습니다.';
        }
        if ($headerData->claims->_AGENT !== $_SERVER['HTTP_USER_AGENT']) {
            $auth_check = false;
            $message = '접속 정보가 변경되었습니다.';
        }
        if ($auth_check) {
            $uid = $headerData->claims->_USER_ID;
            $premiumID = $headerData->claims->_PREMIUM_ID;
        }
    }
}
//$premiumID = 'pierrotss'; //임시 개발용
if (!$premiumID) {
    $result['success'] = $success;
    $result['message'] = $message;
    $result['notice_code'] = 'E001';
    $result['notice_message'] = $message;
    $result['data'] = $data;
    echo json_encode($result);
    exit;
}

function logs($msg) {
    $dirname = 'log';
    $date = date("Y_m");
    $filename = $dirname . '/log_' . $date . '.log';

    if (file_exists($dirname) && !is_dir($dirname)) {
        chmod($dirname, 0777);
        rmdir($dirname);
    }

    if (!file_exists($dirname)) {
        mkdir($dirname);
    }
    chmod($dirname, 0777);

    if (!file_exists($filename)) {
        touch($filename);
    }

    $datetime = date("ymd_His");
    $output = '[' . $datetime . '] {' . $_SERVER['REMOTE_ADDR'] . '} ' . $msg . PHP_EOL . PHP_EOL;

    file_put_contents($filename, $output, FILE_APPEND);
}

function equals($str1, $str2) {
    return (is_string($str1) && is_string($str2) && (strlen($str1) == strlen($str2)) && (strcmp($str1, $str2) === 0));
}

function url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

function ready_lock($premiumID) {
  $rtn = false;
  if ($premiumID) {
    $dirname = 'lock';
    $filename = $dirname . '/lock_' . $premiumID;
    // dir 있음 + 디렉터리
    // dir 있음 + 디렉터리아님
    // dir 없음

    if (file_exists($dirname) && !is_dir($dirname)) {
      chmod($dirname, 0777);
      rmdir($dirname);
    }

    if (!file_exists($dirname)) {
      mkdir($dirname);
    }
    chmod($dirname, 0777);

    if (!file_exists($filename)) {
      touch($filename);
    }

    if (file_exists($filename)) {
      $rtn = $filename;
    }
  }
  return $rtn;
}

function is_meaningful($str) {
  return (is_string($str) && strlen($str) > 0) ? $str : false;
}

function is_meaningful_rgx($str, $rgx) {
  return ($str === is_meaningful($str) && preg_match('/^' . $rgx . '$/', $str, $matches) === 1) ? $str : false;
}

function is_meaningful_rgx_or($str, $rgx, $def) {
  return is_meaningful_rgx($str, $rgx) ? $str : $def;
}
