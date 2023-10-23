<?php
/**
 * Created by IntelliJ IDEA.
 * User: bugwang
 * Date: 2019-04-09
 * Time: 오전 11:23
 */

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$q = is_meaningful_rgx($_REQUEST['q'], '[01]{1}');
$qc = is_meaningful_rgx($_REQUEST['qc'], '[01]{1}');
$result = array();

insertIfNotExists();

if ($q !== false) {
	$success = updateAsInput($q);
}
if ($qc !== false) {
	$success = updateAsInputCategory($qc);
}

$query = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CHOICE_TYPE'";
$response = mysqli_query($db_conn, $query);
if ($response) {
	$row = mysqli_fetch_assoc($response);
	$layout = $row['value'];
} else {
	$layout = '0';
}
$result['layout'] = $layout;

$query = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CATE_USE'";
$response = mysqli_query($db_conn, $query);
if ($response) {
	$row = mysqli_fetch_assoc($response);
	$category = $row['value'];
} else {
	$category = '1';
}
$result['category'] = $category;

if ($q !== false || $qc !== false) {
	$result['success'] = $success;
}

echo json_encode($result);

function insertIfNotExists() {
	global $db_conn;

	$query = "SELECT COUNT(*) AS `cnt` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CHOICE_TYPE'";
	$response = mysqli_query($db_conn, $query) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
	if ($response !== false) {
		$tmp = mysqli_fetch_assoc($response);
		$exists = $tmp ? intval($tmp['cnt']) : false;
	} else {
		$exists = false;
	}

	if ($exists === 0) {
		$query_insert = "INSERT INTO `hnp_config` (`fKey`, `sKey`, `value`, `alias`, `regDate`, `description`) VALUES ('EVAL_LAYOUT', 'CHOICE_TYPE', '0', '평가화면설정-선택타입', NOW(), '평가화면 레이아웃 선택 : 0:콤보박스 / 1:라디오버튼')";
		$response = mysqli_query($db_conn, $query_insert) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
		$rtn = $response;
	} else {
		$rtn = true;
	}

	$query = "SELECT COUNT(*) AS `cnt` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CATE_USE'";
	$response = mysqli_query($db_conn, $query) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
	if ($response !== false) {
		$tmp = mysqli_fetch_assoc($response);
		$exists = $tmp ? intval($tmp['cnt']) : false;
	} else {
		$exists = false;
	}

	if ($exists === 0) {
		$query_insert = "INSERT INTO `hnp_config` (`fKey`, `sKey`, `value`, `alias`, `regDate`, `description`) VALUES ('EVAL_LAYOUT', 'CATE_USE', '1', '평가/검색 기사목록 분류별로 표시 : 0:미사용 / 1:사용')";
		$response = mysqli_query($db_conn, $query_insert) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
		$rtn = $rtn && $response;
	} else {
		$rtn = $rtn && true;
	}

	return $rtn;
}

function updateAsInput($layout) {
	global $db_conn;

	$update = "UPDATE `hnp_config` SET `value` = " . (int)$layout . " WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CHOICE_TYPE'";
	$response = mysqli_query($db_conn, $update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

	return $response;
}

function updateAsInputCategory($category) {
	global $db_conn;

	$update = "UPDATE `hnp_config` SET `value` = " . (int)$category . " WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CATE_USE'";
	$response = mysqli_query($db_conn, $update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

	return $response;
}
