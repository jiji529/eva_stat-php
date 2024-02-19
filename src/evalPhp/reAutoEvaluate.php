<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassPage.php';
include_once __DIR__ . '/ClassStat.php';
require_once __DIR__ . "/ClassSearch.php";
include_once __DIR__ . '/ClassTypeUtil.php';
require_once __DIR__ . '/autoEvaluate.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';
include_once __DIR__ . '/columnSettingFunc.php';

$evaluationSeq = $_REQUEST['evaluationSeq'];

if (!is_numeric($evaluationSeq) || $evaluationSeq < 0) {
    if ($message) $rtn_meta['msg'] = $message;
    $rtn_meta['pid'] = $premiumID;
    $rtn_meta['success'] = false;
    $rtn_meta['evaluationSeq'] = $evaluationSeq;
    echo json_encode($json);
    exit;
}

//클래스 --------------------------------------------------------------------------------
$db = new ClassStat($premiumID);
$ClassPage = new ClassPage();
$ClassSearch = new ClassSearch($premiumID);
$columnSetting = getColumnSetting($db, 'WEB');
$columnSetting = $columnSetting['final'];
//검색조건 받기----------------------------------------------------------------------------
$db_conn = $ClassSearch->getDBConn();

//query-------------------------------------------------------------------------------
$query1 = "SELECT * FROM (SELECT distinct ";

$field_auto = "hnews.news_id";

$table_string = " FROM `hnp_news` AS `hnews`
LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id`
LEFT JOIN `reporterGroup` ON `reporterGroup`.`hnp_category_media_id` = `hnews`.`media_id`
  AND `hnews`.`news_reporter` LIKE CONCAT('%',  `reporterGroup`.`reporterName`, '%') AND `reporterGroup`.`isUse` = 'Y'
LEFT JOIN `mediaGroup` ON `mediaGroup`.`hnp_category_media_id` = `hnews`.`media_id`
LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id`
LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq`
LEFT JOIN `scrapBook` AS `SB` ON `SB`.`no` = `hnews`.`scrapBookNo`";

$query2 = " WHERE hnews.articleSequence = '0'
AND hcate.media_name NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실')
AND hcate.mediaType != -98
AND hcate.isUse = '0'
AND `hnews`.`news_me` IN (SELECT `LVALUE` FROM `DISPLAY_INFO`
WHERE `LTYPE` = 1 AND `BUESSURE` = 1)";

$query2 .= ' GROUP BY `hnews`.`news_id`) `T` ';
// logs('query2 : ' . $query2);

if (!is_null($con)) { /* $con이라는 변수가 존재하는지는 확인 못함. */
    mysqli_set_charset($con, 'utf8');
}

// 공통 설정
$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

/* 전체 조회 */
$query_auto = "{$query1}{$field_auto}{$table_string}{$query2}";
logs('search.qry_auto : ' . $query_auto);
$result_auto = mysqli_query($db_conn, $query_auto) or die(mysqli_error($db_conn) . ' E001-E');
$news_id_arr = array();
while ($row = mysqli_fetch_assoc($result_auto)) {
    $news_id_arr[] = $row['news_id'];
}

/* 자동평가 카테고리 목록 찾기 */
// $autoSeq = array();
// foreach ($config_eval['policy']['AT'] as $key) {
//     array_push($autoSeq, $key['seq']);
// }

$query_delete = "
    DELETE FROM `newsEval`
    WHERE 1=1
    AND evalClassify_seq IN (
    	SELECT seq
    	FROM `evalClassify`
    	WHERE evaluation_seq IN (
    		".$evaluationSeq."
    	)
    )
";
mysqli_query($db_conn, $query_delete) or die(mysqli_error($db_conn) . ' E001-E');

/* 자동평가 미등록 기사 아이디 찾기 */
/*
$query_auto = null;
$query_auto = "
    SELECT news_id
    FROM `hnp_news`
    WHERE 1=1 
    AND `hnp_news`.`news_id` IN (". implode(',',$news_id_arr) .")
    AND news_id NOT IN (
    	SELECT 
    		`hnp_news`.`news_id`
    	FROM `hnp_news`
        LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnp_news`.`news_id`
        LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq` 
    	WHERE 1=1
    	AND `eval2`.`evaluation_seq` IN (". implode(',',$autoSeq) .")
    	GROUP BY `hnp_news`.`news_id`
    	ORDER BY `hnp_news`.`news_id`
    )
";
$list = mysqli_query($db_conn, $query_auto) or die(mysqli_error($db_conn) . ' E001-E');
$news_id_arr = array();
while ($row = mysqli_fetch_assoc($list)) {
    $news_id_arr[] = $row['news_id'];
}
*/

// 자동평가 생성
if(intval($evaluationSeq) == 7) {
    $ctu = new ClassTypeUtil($db_conn);
    $ctu->classTypeAutoEvaluation(null, null, null);
    $ctu->InsertClassType();
} else {
    echo json_encode(autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, $evaluationSeq));    
}
exit;