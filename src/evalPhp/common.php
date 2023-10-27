<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-30
 * Time: 오전 11:57
 */
header('Access-Control-Allow-Headers: Authorization');
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/phpRedis.php';

$message = '유효하지 않은 접근입니다.';
if (diffHttpUserAgent()) {
    $result['success'] = false;
    $result['message'] = $message;
    $result['notice_code'] = 'E001';
    $result['notice_message'] = $message;
    $result['data'] = [];
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
    $output = '[' . $datetime . ']' . $msg . PHP_EOL . PHP_EOL;

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
