<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: 오후 3:10
 */
header('Access-Control-Allow-Headers: Authorization');
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/lib/mysql.class.php';
include_once __DIR__ . '/phpRedis.php';


//default setting
$now_date = date('YmdHis');
$exp_dt = date("Y-m-d H:i:s", time());
$message = '유효하지 않은 접근입니다.';
$data = array();


if (diffHttpUserAgent()) {
    $result['success'] = false;
    $result['message'] = $message;
    $result['notice_code'] = 'E001';
    $result['notice_message'] = $message;
    $result['data'] = $data;
    echo json_encode($result);
    exit;
}

/* domain */
$path = "https://". $premiumID .".scrapmaster.co.kr";
$domain = @get_headers($path);
$domain = (!$domain || strpos($domain[0], '404')) ? false : true ;

/* default */
$data['uid'] = $_SESSION["USER_ID"];
$data['pid'] = $_SESSION["PREMIUM_ID"];
$data['peUser'] = $_SESSION["PE_USER"];
$data['regDate'] = $now_date;
$data['domain'] = $domain;

/* time */
$auth_exp = 60;
$exp_dt = date("Y-m-d H:i:s", time() + (60 * $auth_exp)); // exp:minutes

/* output */
$result['success'] = true;
$result['message'] = '로그인이 처리되었습니다.';
$result['data'] = $data;
//190312 09:19 bg 추가
$result['exp_dt'] = $exp_dt;
$result['exp_dr'] = $auth_exp;

echo json_encode($result);
