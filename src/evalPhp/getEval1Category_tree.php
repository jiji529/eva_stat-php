<?php
include_once __DIR__ . '/common.php';
include_once __DIR__.'/dbConn.php';

$type = $_REQUEST['t'];

$sql = "select `seq`, `name`, `score`, `upperSeq`, `order`,`isUse` from `category` ";
if($type!=='a'){
    $sql .="WHERE `isUse` = 'Y' ";
}
$sql .= "ORDER BY CASE WHEN `upperSeq` = NULL THEN 0 ELSE 2 END , `order` ";

$result = mysqli_query($db_conn, $sql) or die(mysqli_error($db_conn) . 'query request error');
$rsArray= array();
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
array_push($rsArray,array("totalList" => $cateList));
$majorCateList = array();
foreach($cateList as $key => $value) {
    if($value['upperSeq'] == null) {
        array_push($majorCateList, $value);
    }
}
$middleCateList = array();
foreach($majorCateList as $key => $value) {
    foreach($cateList as $k => $val) {
        if($value['seq'] == $val['upperSeq']) {
            array_push($middleCateList, $val);
        }
    }
}


$minorCateList = array();
foreach($middleCateList as $key => $value) {
    foreach($cateList as $k => $val) {
        if($value['seq'] == $val['upperSeq']) {
            array_push($minorCateList, $val);
        }
    }
}

$treeArr = array();
$treeArr = $majorCateList;

//print_r($treeArr);

for ($i=0; $i<count($treeArr); $i++){
    $k=0;
    for($j=0; $j<count($middleCateList); $j++){
        if($treeArr[$i]['seq'] == $middleCateList[$j]['upperSeq']){
            $treeArr[$i]['sub'][$k] = $middleCateList[$j];
            $k++;
        }
    }
}

for($i=0; $i<count($treeArr); $i++){
    for($j=0; $j<count($treeArr[$i]['sub']); $j++){
        $easy = $treeArr[$i]['sub'][$j];
        $h=0;
        for($k=0; $k<count($minorCateList); $k++){
            if($easy['seq'] == $minorCateList[$k]['upperSeq']){
                $treeArr[$i]['sub'][$j]['sub'][$h] = $minorCateList[$k];
                $h++;
            }
        }
    }
}





echo json_encode($treeArr);
//db연결 해제
mysqli_close($db_conn);



?>
