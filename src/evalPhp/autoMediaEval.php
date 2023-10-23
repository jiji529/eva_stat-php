<?php

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';


$media_id = $_REQUEST['media_id'];

$sql = "SELECT evalClassify_seq as value FROM mediaGroup WHERE hnp_category_media_id = {$media_id}";

mysqli_set_charset($db_conn,"utf8") or die(mysqli_error($db_conn) . 'E001');
$result = mysqli_query($db_conn, $sql);

$evalSeq = null;

while($row  = mysqli_fetch_assoc($result)) {
    $evalSeq = $row['value'];
}


echo json_encode($evalSeq);

//db연결 해제
mysqli_close($db_conn);

?>
