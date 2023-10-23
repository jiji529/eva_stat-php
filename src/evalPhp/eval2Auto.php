<?php
/**
 * Created by IntelliJ IDEA.
 * User: bugwang
 * Date: 2019-04-09
 * Time: 오후 8:47
 */

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$q = $_REQUEST['q'] !== null ? json_decode($_REQUEST['q']  , true): '';// json
$result = array();

if($q) {
	if($q['isUse'] === false) $auto = "N";
	else $auto = "Y";
	updateAutomatic($q['seq'], $auto);
}

$sql = "SELECT `seq`, `name`, `order`,`isUse`,`automatic` FROM `evaluation` WHERE `automatic` = 'Y' ORDER BY `order` ";

$response = mysqli_query($db_conn, $sql) or die (json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

$str =mb_convert_encoding('수록지면','utf-8' , 'euc-kr');

$eval2 = array();
$name_key_eval2 = array();
if($response) {
	while($row=mysqli_fetch_assoc($response)){
		if($row['isUse'] === 'Y') $row['checkbox'] = true;
		else $row['checkbox'] = false;
		$name = $row['name'];
		
		if($name === $str) $row['name'] = mb_convert_encoding('기사위치','utf-8', 'euc-kr');
		$eval2[] = $row;
		$name_key_eval2[$row['name']] = $row;
	}
}

mysqli_close($db_conn);

$result['eval2'] = $eval2;
$result['name_key_eval2'] = $name_key_eval2;
$result['success'] = true;
$result['isUse'] = $auto;

echo json_encode($result); exit;

function updateAutomatic($seq , $auto){
	global $db_conn;
	$sql = "UPDATE `evaluation` SET `isUse` =  '{$auto}' WHERE `seq` = {$seq} ; ";
	$response = mysqli_query($db_conn, $sql) or die (json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
}
?>