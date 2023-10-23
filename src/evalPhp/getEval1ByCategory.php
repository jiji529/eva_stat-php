<?php
include_once __DIR__ . '/common.php';
include_once __DIR__.'/dbConn.php';

$type = $_REQUEST['t'];

$sql = "select `seq`, `name`, `score`, `upperSeq`, `order`,`isUse` from `category` ";
if($type!=='a'){
	$sql .="WHERE `isUse` = 'Y' ";
}
$sql .= "ORDER BY CASE WHEN `upperSeq` = NULL THEN 0 ELSE 2 END , `order` ";

$result = mysqli_query($db_conn, $sql) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
$cateList=array();
while ($row = mysqli_fetch_assoc($result)){
	$rowArray = array(
			"seq" => $row['seq'],
			"name" =>  $row['name'],
			"score" =>  $row['score'],
			"upperSeq" =>  $row['upperSeq'],
			"order" =>  $row['order'],
			"isUse" =>  $row['isUse'],
			"sub" => array()
	);
	array_push($cateList, $rowArray);
}

$majorCateList = array();
$middleCateList = array();
$minorCateList = array();

foreach($cateList as $key => $value) {
	if($value['upperSeq'] == null) {
		array_push($majorCateList, $value);
	}
}

foreach($majorCateList as $key => $value) {
	foreach($cateList as $k => $val) {
		if($value['seq'] == $val['upperSeq']) {
			array_push($middleCateList, $val);
		}
	}
}

foreach($middleCateList as $key => $value) {
	foreach($cateList as $k => $val) {
		if($value['seq'] == $val['upperSeq']) {
			array_push($middleCateList[$key]['sub'], $val);
			array_push($minorCateList, $val);
		}
	}
}

foreach($majorCateList as $key => $value) {
	foreach($middleCateList as $k => $val) {
		if($value['seq'] == $val['upperSeq']) {
			array_push($majorCateList[$key]['sub'], $val);
		}
	}
}

$allList = array();
foreach($majorCateList as $key => $value) {
	$allList[$value['seq']] = array('eval1_name'=> $value['name'] , 'eval1_seq' => $value['seq'], 'eval1_upper' => $value['upperSeq'], 'sub' => $value['sub'], 'flag' => 'major');
}
foreach($middleCateList as $key => $value) {
	$allList[$value['seq']] = array('eval1_name'=> $value['name'] , 'eval1_seq' => $value['seq'], 'eval1_upper' => $value['upperSeq'] , 'sub' => $value['sub'], 'flag' => 'middle');
}
foreach($minorCateList as $key => $value) {
	$allList[$value['seq']] = array('eval1_name'=> $value['name'] , 'eval1_seq' => $value['seq'], 'eval1_upper' => $value['upperSeq'] , 'sub' => $value['sub'] , 'flag' => 'minor');
}
$listByCategory = array();
$listByCategory['major'] = $majorCateList;
$listByCategory['middle'] = $middleCateList;
$listByCategory['minor'] = $minorCateList;
$listByCategory['all'] = $allList;





echo json_encode($listByCategory);
//db연결 해제
mysqli_close($db_conn);



?>
