<?php
include __DIR__ . '/common.php';
include __DIR__ . '/dbConn.php';



$sql = "SELECT `seq` FROM `evalClassify` WHERE `value` = '일반기자'; ";
$result = mysqli_query($db_conn, $sql);
$assoc = mysqli_fetch_assoc($result);

echo $assoc['seq'];

mysqli_close($db_conn);
?>