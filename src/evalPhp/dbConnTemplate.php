<?php
$db_host_template = "211.233.16.3";
$db_id_template = "root";
$db_password_template = "dlgksmlchlrh";
$db_dbname_template = "paper_management_template";

//connect db
$db_conn_template = mysqli_connect($db_host_template, $db_id_template, $db_password_template, $db_dbname_template);
if (!$db_conn_template) {
    $error_template = array(
        "notice_code" => "E000",
        "notice_message" => "Fail to connect database (데이터베이스 연결에 실패했습니다.)",
				"success" => false
    );
    die(json_encode($error_template));
}
mysqli_set_charset($db_conn_template, "utf8");
?>
