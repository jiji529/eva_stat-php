<?php

include_once __DIR__ . '/common.php';
include __DIR__.'/dbConn.php';

$sql = "select LVALUE, SNAME FROM DISPLAY_INFO WHERE LTYPE=1 AND BUESSURE = 1 ORDER BY LVALUE ASC";

mysqli_set_charset($db_conn,"utf8");
$result = mysqli_query($db_conn, $sql);
if(! $result ) {
    $error = array(
        "success" => false,
        "notice_error" => "E001",
        "notice_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo json_encode($error);
    exit;
}
$rsArray=array();
while ($row = mysqli_fetch_assoc($result)){
    $rowArray = array(
        "lvalue" => $row['LVALUE'],
        "sname" =>  $row['SNAME']
    );
    $rsArray[$row['LVALUE']] = $rowArray;
    
}


if($rsArray==null){
    $error = array(
        "success" => false,
        "notice_code" => "N000",
        "notice_message" => urlencode("표시할 데이터가 없습니다."),
    );
    /*echo json_encode($error);
    exit;*/
}

echo json_encode($rsArray);
////db연결 해제
mysqli_close($db_conn);
?>