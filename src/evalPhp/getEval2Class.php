<?php
/**
 * Created by IntelliJ IDEA.
 * User: bugwang
 * Date: 2018-12-21
 * Time: 오전 10:35
 */
include_once __DIR__ . '/common.php';
include __DIR__ . '/dbConn.php';
$type = $_REQUEST['t'];
$sql = "SELECT `eval2`.`seq`, `eval2`.`value`, `eval2`.`score`, `eval2`.`refValue`, `eval2`.`isUse`, `eval2`.`order`, `eval2`.`evaluation_seq` FROM `evalClassify` AS eval2  ";
if ($type !== 'a'  && $type !== 'b') {
    $sql .= "WHERE `isUse` = 'Y' ";
}
$sql .= "ORDER BY  `eval2`.`order`";

mysqli_set_charset($db_conn, "utf8");
$result = mysqli_query($db_conn, $sql) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
/*if(! $result ) {
    $error = array(
        "error_code" => "E001",
        "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo urldecode(json_encode($error));
    exit;
}*/
$evalClassify = array();
while ($row = mysqli_fetch_assoc($result)){
    $rowArray = array(
        "seq" => $row['seq'],
        "name" =>  $row['value'],
        "score" =>  $row['score'],
        "order" =>  $row['order'],
        "upperSeq" =>  $row['evaluation_seq'],
        "refValue" =>  $row['refValue'],
        "use" =>  $row['isUse'],
    );
    array_push($evalClassify, $rowArray);
}

$get_evaluation_sql = "SELECT `seq`, `name`, `order`,`isUse`,`automatic` FROM `evaluation` ";
if ($type === 'a') {
    $get_evaluation_sql .= "WHERE `automatic`='N'   ";
}else{
    $get_evaluation_sql .= "WHERE isUse='Y'  ";
}

$get_evaluation_sql .= "ORDER BY  `order` ";
mysqli_set_charset($db_conn, "utf8");
$result = mysqli_query($db_conn, $get_evaluation_sql) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
/*if(! $result ) {
    $error = array(
        "error_code" => "E001",
        "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo urldecode(json_encode($error));
    exit;
}*/
$eval2Class = array();
while ($row = mysqli_fetch_assoc($result)){
    $rowArray = array(
        "upper_cate_seq" => $row['seq'],
        "upper_cate_name" =>  $row['name'],
        "upper_cate_order" =>  $row['order'],
        "auto" => $row['automatic'],
        "upper_cate_use" =>  $row['isUse'],
        "sub" => array()
    );
    array_push($eval2Class, $rowArray);
}
//echo json_encode($eval2Class);
$group = array();

foreach($evalClassify as $key => $one) {
    foreach($eval2Class as $key2 => $one2){
        if($one2['upper_cate_seq'] == $one['upperSeq']) {
            array_push($eval2Class[$key2]['sub'], $one);
        }
    }
}

echo json_encode($eval2Class);

//db연결 해제
mysqli_close($db_conn);



?>
