<?php
$db_host = "211.233.16.3";
$db_id = "root";
$db_password = "dlgksmlchlrh";
$db_dbname = "paper_management_{$premiumID}";

//connect db
$db_conn = mysqli_connect($db_host, $db_id, $db_password, $db_dbname);
if (!$db_conn) {
    $error = array(
        "notice_code" => "E000",
        "notice_message" => "Fail to connect database (데이터베이스 연결에 실패했습니다.)",
				"success" => false
    );
    echo json_encode($error);
    exit;
}
mysqli_set_charset($db_conn, "utf8");
?>