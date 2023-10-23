<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: 오후 3:10
 */
header('Content-Type: application/json');
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/lib/DahamiToken.php';

//request
if (isset($_POST['uid'])) $uid = $_POST['uid'];
if (isset($_POST['password'])) $txtPass = $_POST['password'];
if (isset($_POST['auth'])) $headerAuth = $_POST['auth']; 


//default setting
$success = false;
$data = array();

//token
$DahamiToken = new DahamiToken();

$now_date = date('YmdHis');
$now_time = time();

if (isset($headerAuth) && $headerAuth !== null) {
    $headerData = substr($headerAuth, 20, -20);

    $headerData = base64_decode($headerData);

    $headerData = explode('&', $headerData);

    $exp = explode('=', $headerData[0]);
    $exp = $exp[1];
    $user_id= explode('=', $headerData[1]);
    $user_id = $user_id[1];
    $premium_id = explode('=', $headerData[2]);
    $premium_id = $premium_id[1];

    $auth_check = true;
    if($headerData){
        if ($exp + (60*60) < $now_time) {
            $auth_check = false;
        }
        if ($auth_check) {
            $uid = $user_id;
            $premiumID = $premium_id;
        }
    }
}

$versionStat = '';
if (isset($uid) && isset($txtPass)) {
    $sm3_db = new MySQL(true, 'sm3_service', 'sm3.scrapmaster.co.kr', 'mangne83', 'mangne83','utf8');

    if ($sm3_db->Error()) {
        $result['success'] = $success;
        $result['errno'] = $sm3_db->ErrorNumber();
        $result['message'] = $sm3_db->Error();
        $sm3_db->Close();
        echo json_encode($result);
        exit;
    }
    $encID = $sm3_db->SQLValue($uid);
    $encPass = $sm3_db->SQLValue($txtPass);

    $sql = "SELECT a.`premiumID` FROM `premiumInfo` as a LEFT JOIN `member` as b on a.`sm3No` = b.`member_num` ";
    $sql .= "WHERE b.`id`={$encID} AND b.`pwd` ={$encPass} ";
    $premiumID = $sm3_db->QuerySingleValue($sql);

    if ($premiumID) {
        $sql = "SELECT `versionStat` FROM `premium_login` WHERE `premiumID` = '{$premiumID}'";
        $versionStat = $sm3_db->QuerySingleValue($sql);
    }

    $sm3_db->Close();
}

if (!empty($premiumID)) {
    $db = new ClassStat($premiumID);
    $db->defaultCreateTable();

    $session_data = array(
        "_USER_ID" => $uid,
        "_PREMIUM_ID" => $premiumID,
        "_AGENT" => $_SERVER['HTTP_USER_AGENT'],
        "_SERVER_IP" => $_SERVER['REMOTE_ADDR'],
        "_PE_USER" => (intval($versionStat) > 1) ? true : false
    );

    $DahamiToken->setData($session_data);
    $token = $DahamiToken->getToken();
    $data['accessToken'] = $token;
    $data['uid'] = $uid;
    $data['pid'] = $premiumID;
    $data['regDate'] = $now_date;
    $data['peUser'] = (intval($versionStat) > 1) ? true : false;

    $path = "https://".$premiumID.".scrapmaster.co.kr";
    $domain = @get_headers($path);
    if (!$domain || strpos($domain[0], '404')) {
			$domain = false;
    } else {
			$domain = true;
    }

    $data['domain'] = $domain;
    $success = true;
    $message = '로그인이 처리되었습니다.';
} else {
    $message = '아이디 혹은 패스워드가 잘못되었습니다.';
    if (isset($headerAuth) && $headerAuth !== null) {
        $message = '';
    }
}

// to log authenticate
$logmsg = '';
if (isset($premiumID,$uid)) {
	$logmsg = ($success) ? $premiumID : $uid;
}
$logmsg .= ' ' . $message;
$dirname = 'log';
$date = date("Y_m");
$filename = $dirname . '/auth_' . $date . '.log';
if (file_exists($dirname) && !is_dir($dirname)) {
    chmod($dirname, 0777);
    rmdir($dirname);
}
if (!file_exists($dirname)) { mkdir($dirname); }
chmod($dirname, 0777);
if (!file_exists($filename)) { touch($filename); }
$datetime = date("ymd_His");
$output = '[' . $datetime . '] {' . $_SERVER['REMOTE_ADDR'] . '} ' . $logmsg . PHP_EOL . PHP_EOL;
file_put_contents($filename, $output, FILE_APPEND);

$result['success'] = $success;
$result['message'] = $message;
$result['data'] = $data;

//var_dump($result);

echo json_encode($result);
