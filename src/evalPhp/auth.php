<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: 오후 3:10
 */
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization');
header('Content-Type: application/json');
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/lib/mysql.class.php';
include_once(__DIR__ . '/lib/DahamiToken.php');


//default setting
$success = false;
$data = array();

//token
$DahamiToken = new DahamiToken();
$now_date = date('YmdHis');
$now_time = time();

$exp_dt = date("Y-m-d H:i:s", time());
$auth_exp = $DahamiToken->getExp();
$headerAuth = $DahamiToken->getHeaderAuth();
$message = '회원 정보를 찾을 수 없습니다.';
if ($headerAuth !== null) {
    $headerData = $DahamiToken->getData($headerAuth);
    $auth_check = true;
    if ($headerData) {
        if ($headerData->exp < $now_time) {
            $auth_check = false;
            $message = '기간이 만료되었습니다.';
        }
        // if ($headerData->claims->_SERVER_IP !== $_SERVER['REMOTE_ADDR']) {
        //     $auth_check = false;
        //     $message = '접속 정보가 변경되었습니다.';
        // }
        if ($headerData->claims->_AGENT !== $_SERVER['HTTP_USER_AGENT']) {
            $auth_check = false;
            $message = '접속 정보가 변경되었습니다.';
        }
        if ($auth_check) {
            $uid = $headerData->claims->_USER_ID;
            $premiumID = $headerData->claims->_PREMIUM_ID;
            $isPeUser = $headerData->claims->_PE_USER;
            $isEmbedded = $headerData->claims->_EMBEDDED;
        }
    }
}

if ($premiumID) {
    $session_data = array(
        "_USER_ID" => $uid,
        "_PREMIUM_ID" => $premiumID,
        "_AGENT" => $_SERVER['HTTP_USER_AGENT'],
        "_SERVER_IP" => $_SERVER['REMOTE_ADDR'],
        "_PE_USER" => $isPeUser
    );
    if ($isEmbedded) { // SM embedded mode
        $session_data['_EMBEDDED'] = $isEmbedded;
        $DahamiToken->setExp(512640); // exp:minutes
    }
    $DahamiToken->setData($session_data);
    $token = $DahamiToken->getToken();

    $data['accessToken'] = $token;
    $data['uid'] = $uid;
    $data['pid'] = $premiumID;
    $data['regDate'] = $now_date;
    $data['peUser'] = $isPeUser;

    $path = "https://".$premiumID.".scrapmaster.co.kr";
    $domain = @get_headers($path);
    $domain = (!$domain || strpos($domain[0], '404')) ? false : true ;

    $data['domain'] = $domain;
    $success = true;
    $message = '로그인이 처리되었습니다.';
    $auth_exp = $DahamiToken->getExp();
    $exp_dt = date("Y-m-d H:i:s", time() + (60 * $auth_exp)); // exp:minutes
}

$result['success'] = $success;
$result['message'] = $message;
$result['data'] = $data;
//190312 09:19 bg 추가
$result['exp_dt'] = $exp_dt;
$result['exp_dr'] = $auth_exp;

echo json_encode($result);
