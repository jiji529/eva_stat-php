<?php


include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$media_id = $_REQUEST['media_id'];
$reporter_name = $_REQUEST['reporter_name'];
$sql = "SELECT evalClassify_seq as value FROM reporterGroup WHERE hnp_category_media_id = {$media_id} AND reporterName = '{$reporter_name}'";

mysqli_set_charset($db_conn,"utf8");
$result = mysqli_query($db_conn, $sql);
if(! $result ) {
    $error = array(
        "error_code" => "E001",
        "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo urldecode(json_encode($error));
    exit;
}
$evalSeq = null;

while($row  = mysqli_fetch_assoc($result)) {
    $evalSeq = $row['value'];
}

$normalReporterSql = "SELECT seq FROM evalClassify WHERE VALUE='일반기자'; ";
$defaultSeq = 0;
$result = mysqli_query($db_conn, $normalReporterSql);
if(! $result ) {
    $error = array(
        "error_code" => "E001",
        "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
    );
    echo urldecode(json_encode($error));
    exit;
}
while($row  = mysqli_fetch_assoc($result)) {
    $defaultSeq = $row['seq'];
}

if($evalSeq == null){
    $evalSeq = $defaultSeq;
}
echo json_encode($evalSeq);

//db연결 해제
mysqli_close($db_conn);

?>
