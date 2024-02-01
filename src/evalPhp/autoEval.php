<?php
// include_once __DIR__ . '/common.php';
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
ini_set('max_execution_time', 3600);

$result = array();

$premiumID = $_REQUEST['pid']; // validate
if (strlen($premiumID) > 4 && preg_match('/^[a-z_0-9]{5,}$/', $premiumID, $matches) === 1) {

} else {
  die('bad pid');
}

include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/autoEvaluate.php';

$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result['success'] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    exit(json_encode($result));
}

$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

// test run
$query = "select news_id from hnp_news";
$db->Query($query);
if ($db->Error()) {
    $result["success"] = false;
    $result["message"] = 'hnp_news.news_id error';
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $dictQuery . $db->Error();
    $db->Close();
    die(json_encode($result));
} else {
  $query_count = 0;
  $news_count = 0; $news_id_arr = array();
  while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
    $news_id_arr[] = $row['news_id'];
  }
  $result = autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, -1);
}

echo json_encode($result);
