<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$news_id = $_REQUEST['news_id'] ? $_REQUEST['news_id'] : null;
$flag = $_REQUEST['flag'] ? $_REQUEST['flag'] : '';

$error = array (
		"error_code" => "N000",
		"err_message" => "news_id 값이 반드시 필요합니다.",
		"success" => false
);

if(!$news_id) {
	echo json_encode($error);
	exit;
}

if(!is_array($news_id)) {
	$news_id_arr = explode(",", $news_id);
} else {
	$news_id_arr = $news_id;
}

$news_id_string = implode(",", $news_id_arr);

if($flag === 'in') {
	$sql = "UPDATE hnp_news SET news_comment = '' WHERE hnp_news.news_id IN ({$news_id_string})";
} else {
	$sql = "UPDATE hnp_news SET news_comment = 1 WHERE hnp_news.news_id IN ({$news_id_string})";
}

$response = mysqli_query($db_conn, $sql)  or die (json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

$result =array();
$result["update_count"] = mysqli_affected_rows($db_conn);
$result["flag"] = $flag;

echo json_encode($result);

mysqli_close($db_conn);
?>