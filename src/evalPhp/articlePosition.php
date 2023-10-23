<?php
/**
 * Created by IntelliJ IDEA.
 * User: bugwang
 * Date: 2019-04-09
 * Time: 오후 1:48
 */

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';

//수록지면
$q = $_REQUEST['q'] !== null ? json_decode($_REQUEST['q'], true) : '';// json
$evaluation_seq = 5;
$rsArray = null;
$db = new ClassStat($premiumID);
if ($db->Error()) {
	$result["success"] = false;
	$result['notice_code'] = $db->ErrorNumber();
	$result['notice_message'] = $db->Error();
	$db->Close();
	echo json_encode($result);
	exit;
}

if ($q) {
	$totalCount = count($q);
	if ($totalCount > 0) {
		foreach ($q as $ei => $eval) {
			if ($eval['seq']) {
				if ($totalCount > 1) {
					$eval['order'] = $ei + 1;
				}
				selectToUpdate($eval);
			} else {
				$lastOrder = lastInsertOrder();
				$eval['order'] = $lastOrder + 1;
				evalInsert($eval);
			}
		}
	}
}

function selectToUpdate($value)
{
	global $db;
	$select_seq = (int)$value['seq'];
	$select_query = "SELECT `seq` FROM `evalClassify` ";
	$select_query .= "WHERE `seq` =  {$select_seq}";
	$db->Query($select_query);
	if ($db->RowCount() > 0) {
		$update_query = "UPDATE `evalClassify` SET `value` =  '{$value['value']}' ";
		if ($value['score'])
			$update_query .= ", `score` = '{$value['score']}' ";
		if ($value['refValue'] !== null)
			$update_query .= ", `refValue` = '{$value['value']}' ";
		if ($value['order'] !== null)
			$update_query .= ", `order` ='{$value['order']}' ";
		if ($value['isUse'])
			$update_query .= ", `isUse` ='{$value['isUse']}' ";
		$update_query .= "WHERE `seq` =  {$select_seq} ";
		$db->Query($update_query);
	}
}

function evalInsert($value)
{
	global $db, $evaluation_seq;
	if ($value['value']) {
		$insert['value'] = ClassStat::SQLValue($value['value']);
		$insert['evaluation_seq'] = ClassStat::SQLValue($evaluation_seq);
		if ($value['order'] !== null)
			$insert['order'] = ClassStat::SQLValue($value['order']);
		if ($value['score'])
			$insert['score'] = ClassStat::SQLValue($value['score']);
		if ($value['isUse'])
			$insert['isUse'] = ClassStat::SQLValue($value['isUse']);
		if (!$value['refValue'])
			$insert['refValue'] = ClassStat::SQLValue($value['value']);
		$db->InsertRow('evalClassify', $insert);
		$db->GetLastInsertID();

	}
}


function lastInsertOrder()
{
	global $db, $evaluation_seq;
	$order = 0;
	$select_query = "SELECT `order` FROM `evalClassify` ";
	$select_query .= "WHERE  `evaluation_seq`={$evaluation_seq}  ORDER BY `order` DESC ";
	$db->Query($select_query);
	if ($db->RowCount() > 0) {
		$result = $db->RowArray(0, MYSQLI_ASSOC);
		$order = (int)$result['order'];
	}
	return $order;
}


$sql = "SELECT  `seq`,  `value`,  `refValue`,  `score`,  `isUse` FROM `evalClassify`  ";
$sql .= "WHERE `evaluation_seq`={$evaluation_seq} ";
$sql .= "ORDER BY `order` ";
$db->Query($sql);
if ($db->Error()) {
	$result["success"] = false;
	$result['notice_code'] = $db->ErrorNumber();
	$result['notice_message'] = $db->Error();
	$db->Close();
	echo json_encode($result);
	exit;
}
if ($db->RowCount() > 0) {
	$rsArray['list']= $db->RecordsArray(MYSQLI_ASSOC);
	/*$etcList =array();
	$positionList = array();
	while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
		$name = mb_convert_encoding($row['value'], "utf-8" , "ecu-kr");
		if($name === "기타") {
			$etcList[] = $row;
		} else {
			$positionList[] = $row;
		}
	}*/
}
$rsArray['success'] = true;
$db->Close();

if ($rsArray === null) {
	$rsArray = array(
			"success" => false,
			"notice_code" => "N000",
			"notice_message" => "표시할 데이터가 없습니다.",
	);
}
echo json_encode($rsArray);
exit;


?>