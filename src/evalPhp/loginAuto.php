<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: �삤�썑 3:10
 */
header('Content-Type: application/json');
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/phpRedis.php';

//request
$_3DES_KEY = 'dAHamIdAHamIdAHamIdAHamI';
$ev = $_POST['ev'];
$ev_decrypted = decrypt($ev, $_3DES_KEY);
$ev_arr = explode('#', $ev_decrypted); // decode된 것을 '#'으로 쪼개기
if (count($ev_arr) === 3) {
  $ev_dt = $ev_arr[0];
  $ev_id = $ev_arr[1];
  $ev_pw = $ev_arr[2];
} else {
  header($_SERVER['SERVER_PROTOCOL'].' 400'); exit;
}

$uid = $ev_id;
$txtPass = $ev_pw;

//default setting
$success = false;
$data = array();

$now_date = date('YmdHis');

if ($uid && $txtPass) {
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

if ($premiumID) {
    $db = new ClassStat($premiumID);
    $db->defaultCreateTable();

    $session_data = array(
        "USER_ID" => $uid,
        "PREMIUM_ID" => $premiumID,
        "AGENT" => $_SERVER['HTTP_USER_AGENT'],
        "PE_USER" => (intval($versionStat) > 1) ? true : false,
        'EMBEDDED' => 'DAHAMI_SCRAPMASTER_NATIVE_APP'
    );

    /******************** Redis ********************/
    setRedisSessionData($session_data);
    
    $data['uid'] = $uid;
    $data['pid'] = $premiumID;
    $data['regDate'] = $now_date;
    $data['peUser'] = (intval($versionStat) > 1) ? true : false;
    $data['embedded'] = 'DAHAMI_SCRAPMASTER_NATIVE_APP';

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
    $message = '스크랩마스터 평가통계 진입에 실패했습니다.';
}

// to log authenticate
$logmsg = ($success) ? $premiumID : $uid;
$logmsg .= ' [SM] ' . $message;
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

echo json_encode($result);

function decrypt($data, $secret) {
  $data = base64_decode($data);
  $data = mcrypt_decrypt('tripledes', $secret, $data, 'ecb');
  $block = mcrypt_get_block_size('tripledes', 'ecb');
  $len = strlen($data);
  $pad = ord($data[$len-1]);
  return substr($data, 0, strlen($data) - $pad);
}
