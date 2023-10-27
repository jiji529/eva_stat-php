<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: 오후 3:10
 */
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/phpRedis.php';

//request
if (isset($_POST['uid'])) $uid = $_POST['uid'];
if (isset($_POST['password'])) $txtPass = $_POST['password'];
if (isset($_POST['auth'])) $headerAuth = $_POST['auth']; 

//default setting
$success = false;
$data = array();

$now_date = date('YmdHis');
$now_time = time();

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
        "USER_ID" => $uid,
        "PREMIUM_ID" => $premiumID,
        "AGENT" => $_SERVER['HTTP_USER_AGENT'],
        "PE_USER" => (intval($versionStat) > 1) ? true : false
    );
    
    /******************** Redis ********************/
    setRedisSessionData($session_data);
    
    $data['uid'] = $_SESSION['USER_ID'];
    $data['pid'] = $premiumID;
    $data['regDate'] = $now_date;
    $data['peUser'] = $_SESSION['PE_USER'];

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
