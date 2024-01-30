<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassPage.php';
include_once __DIR__ . '/ClassStat.php';
require_once __DIR__ . "/ClassSearch.php";
require_once __DIR__ . '/autoEvaluate.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';
include_once __DIR__ . '/columnSettingFunc.php';
//클래스 --------------------------------------------------------------------------------
$ClassPage = new ClassPage();
$ClassSearch = new ClassSearch($premiumID);
$db_conn = $ClassSearch->getDBConn();
$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result['success'] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

$columnSetting = getColumnSetting($db, 'WEB');
$columnSetting = $columnSetting['final'];

$scrapDate = $_REQUEST['scrapDate'];
$newsMe = $_REQUEST['newsMe'];

mysqli_set_charset($db_conn, "utf8");

$query = "SELECT `no`, `keywords` FROM scrapBook WHERE scrapDate = '{$scrapDate}' AND newsMe = {$newsMe};";

$query_result = mysqli_query($db_conn, $query)or die(mysqli_error($db_conn) . ' E001-0');

$scrapBookNo = 0;
$scrapBookKeywords='';	//2022.02.10 Lee JW - 추가

while ($row = mysqli_fetch_assoc($query_result))
{
    if($row['no'] !== '-1') {
        $scrapBookNo = $row['no'];
		$scrapBookKeywords = $row['keywords'];
    }
}

// 공통 설정
$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

// 자동평가 준비 - news_id
$query_auto = "SELECT `news_id` FROM `hnp_news` WHERE `scrapBookNo` = ${scrapBookNo} AND `articleSequence` = 0";
$result_auto = mysqli_query($db_conn, $query_auto) or die(mysqli_error($db_conn) . ' E001-0');
$news_id_cnt = 0; $news_id_arr = array();
while ($row = mysqli_fetch_assoc($result_auto)) {
    if ($news_id_cnt < 65535) {
        $news_id_arr[] = $row['news_id'];
        $news_id_cnt++;
    }
}

// 자동평가 생성
autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, null);

$query = "SELECT `folderID`, `folderName` FROM FolderInfo WHERE scrapBookNo = {$scrapBookNo} ORDER BY ForS;";

$query_result = mysqli_query($db_conn, $query) or die(mysqli_error($db_conn) . ' E001-1');

$folder = array();
$i = 0;

while ($row = mysqli_fetch_assoc($query_result))
{
    $folder[$i]['folderID'] = $row['folderID'];
    $folder[$i]['folderName'] = $row['folderName'];

    $i++;
}

$query = "SELECT subtitle, folderName, `offset` FROM subTitleInfo WHERE scrapBookNo = $scrapBookNo ORDER BY folderID, offset;";

$query_result = mysqli_query($db_conn, $query)or die(mysqli_error($db_conn) . ' E001-2');

$subtitle = array();
$i = 0;

while ($row = mysqli_fetch_assoc($query_result))
{
    $subtitle[$row['folderName']][$row['offset']] = $row['subtitle'];
}

$query = "SELECT `news_id`, `hnews`.`media_id`, `hcate`.`media_name`, `hcate`.`mediaCategory` AS `category_id`, `hcate`.`mediaCategory` AS `media_category`, `hcate`.`mediaType` AS `media_type`, `hnews`.`category_name`, `news_date` AS `scrap_date`, `news_title` AS `article_title`
, `news_reporter` AS `article_reporter`, `folder`, `news_group`, `coordinate`, `news_file_name`, `zorder`, `rect`
, `guid`, `rectArticle`, `pageName`, `userCutInfo`, `news_me`, `orgLink`, `refLink`

, if(`articleArea2`='', `articleArea`, `articleArea2`) as `article_area`, `news_me`
, `news_contents` as `article_contents`
, `newsTime` AS `article_datetime`
, `part_name`
, `case_name`
, `article_serial` AS `article_serial`
, `category_seq` as `eval1_seq`

    , `news_comment` ,`hcate`.`categoryOutput`, `articleSequence`, `hnews`.`etc1` as `highLight`, `hnews`.`etc2` as `highLightWord`,
    `reporterGroup`.`evalClassify_seq` AS `reporterAuto`,
    `mediaGroup`.`evalClassify_seq` AS `mediaAuto`,
    GROUP_CONCAT(`newsEval`.`evalClassify_seq`) AS `eval2_seqs`,
    `hcate`.`evalValue` AS `media_value`, `hnews`.`keywords`
FROM `hnp_news` as `hnews`
LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id`
LEFT JOIN `reporterGroup` ON `reporterGroup`.`hnp_category_media_id` = `hnews`.`media_id`
    AND `hnews`.`news_reporter` LIKE CONCAT('%',  `reporterGroup`.`reporterName`, '%') AND `reporterGroup`.`isUse` = 'Y'
LEFT JOIN `mediaGroup` ON `mediaGroup`.`hnp_category_media_id` = `hnews`.`media_id`
LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id`
WHERE `hnews`.`scrapBookNo` = {$scrapBookNo} AND `hcate`.`media_name` NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실') AND `hcate`.`mediaType` != -98 AND `hcate`.`isUse` = '0' GROUP BY `hnews`.`news_id` ORDER BY `scrappage`, `zorder`"; /* AND `hnews`.`isUse` = 0 */ /* remove 2019-04-26 jw*/
//GROUP BY hnp_news.article_serial
$query_result = mysqli_query($db_conn, $query) or die(mysqli_error($db_conn) . ' E001-3');

$article = array();
$i = 0; $eval2_seq_arr = array(); $page_size_pixel = 0;
while ($row = mysqli_fetch_assoc($query_result))
{
    // common
    foreach ($columnSetting as $ck => $cv) {
        $field = $cv['field']; $use = equals($cv['use'], 'Y');
        if ($use) {
            $article[$i][$field] = $row[$field];
        }
    }

    $article[$i]['index'] = ($i+1);
    // $article[$i]['article_title'] = $row['news_title'];
    // $article[$i]['media_name'] = $row['media_name'];
    $article[$i]['article_reporter'] = $row['article_reporter'];
    // $article[$i]['scrap_date'] = $row['news_date'];
    // article_date <= calcArticleValue
    // article_size <= calcArticleValue
    // article_page <= calcArticleValue
    // article_length <= calcArticleValue
    $article[$i]['media_value'] = $row['media_value'];
    // $article[$i]['media_category'] = $row['category_id'];
    // $article[$i]['media_type'] = $row['media_type'];
    // 13 eval_score <= calcArticleValue
    // 14 eva1~5 : reference
    // 15 ev1_big|mid|sml : reference
    // 16 ev2_1001~ : reference

    // custom (+legacy)
    $article[$i]['media_category'] = $row['media_category'];
    $article[$i]['media_type'] = $row['media_type'];
    $article[$i]['news_group'] = $row['news_group'];
    $article[$i]['highLight'] = $row['highLight'];
    $article[$i]['highLightWord'] = $row['highLightWord'];
    $article[$i]['news_id'] = $row['news_id'];
    $article[$i]['media_id'] = $row['media_id'];
    $article[$i]['category_id'] = $row['category_id'];
    $article[$i]['category_name'] = $row['category_name'];
    $article[$i]['category_name_new'] = $config_eval['category'][$row['category_id']]['category_name'];
    $article[$i]['coordinate'] = $row['coordinate'];
    $article[$i]['news_file_name'] = $row['news_file_name'];
    $article[$i]['news_comment'] = $row['news_comment'];
    $article[$i]['guid'] = $row['guid'];
    $article[$i]['part_name'] = $row['part_name'];
    $article[$i]['article_serial'] = $row['article_serial'];
	$article[$i]['keywords'] = $scrapBookKeywords ? $scrapBookKeywords : $row['keywords'];

    $page_size_pixel = $ClassPage->getPagePdfSizeByArticleSerial($row['article_serial']);
    $article[$i]['page_size_pixel'] = $page_size_pixel ? $page_size_pixel : 2400;
    $article[$i]['category_output'] = $row['categoryOutput'];
    $article[$i]['mediaAuto'] = $row['mediaAuto']; // 3매체중요도
    $article[$i]['reporterAuto'] = $row['reporterAuto']; // 4취재원

    // dead
    $article[$i]['pageName'] = $row['pageName'];
    $article[$i]['rect'] = $row['rect'];
    $article[$i]['zorder'] = $row['zorder'];
    $article[$i]['rectArticle'] = $row['rectArticle'];
    $article[$i]['userCutInfo'] = $row['userCutInfo'];
    $article[$i]['folder'] = $row['folder'];

    // temporary
    $article[$i]['tmp_article_area'] = $row['article_area'];
    $article[$i]['tmp_article_datetime'] = $row['article_datetime'];
    $article[$i]['tmp_article_contents'] = $row['article_contents'];
    $article[$i]['tmp_article_serial'] = $row['article_serial'];
    $article[$i]['tmp_case_name'] = $row['case_name'];
    $article[$i]['tmp_part_name'] = $row['part_name'];

    // unknown
    $article[$i]['news_me'] = $row['news_me'];
    $article[$i]['articleSequence'] = $row['articleSequence'];
    $article[$i]['eval1_seq'] = $row['eval1_seq'];
    $article[$i]['eval2_seqs'] = $row['eval2_seqs'];
    $eval2_seq_arr = explode(',', $row['eval2_seqs']);
    foreach ($eval2_seq_arr as $ek => $ev) {
        if ($ev) $article[$i]['eval2'][] = array('eval2_seq' => $ev);
    }
    $i++;
}

foreach($article as $ak => &$av)
{
    $calc_result = calcArticleValue($av, $config_eval);
    $av = array_merge($av, $calc_result);
    $av['media_category_name'] = $config_eval['category'][$av['media_category']]['category_name'];
    $av['media_type_name'] = $config_eval['type'][$av['media_type']]['name'];

    $eval1Tree = getEval1NamesArray($config_eval['item']['M1'], $av['eval1_seq']);
    $av['ev1_big'] = $eval1Tree[0] ? $eval1Tree[0] : '';
    $av['ev1_mid'] = $eval1Tree[1] ? $eval1Tree[1] : '';
    $av['ev1_sml'] = $eval1Tree[2] ? $eval1Tree[2] : '';

    $av = array_merge($av, getEval2Names($av['eval2'], $config_eval));

    $article[$ak]['article_contents'] = $article[$ak]['tmp_article_contents'];
    unset($article[$ak]['tmp_article_contents']);
    unset($article[$ak]['tmp_article_area']);
    unset($article[$ak]['tmp_article_datetime']);
    unset($article[$ak]['tmp_article_serial']);
    unset($article[$ak]['tmp_case_name']);
    unset($article[$ak]['tmp_part_name']);
    unset($article[$ak]['category_output']);
}

// die(json_encode($article));

$rev_sync = array();

//이전기사와 현재기사가 시리얼이 같으면 이전기사 제거 , 현재기사 인덱스 뒤에 있는 소제목들 인덱스 번호 -1 씩 (subtitle has index >  x  ?  $subtitle_name = $subtitle[index] , [array_splice($subtitle, index, 1); $subtitle[index-1]= $subtitle_name

$delete_info = array();
$limitOffset = 0;
$subString = '';
$subtitleFlag = 0;

if ($config_eval['policy']['CL']['value'] === '1') {
    foreach($article as $ak => &$av) {
        $rev_sync[$av['category_name_new']][] = $av;
    }
} else {
    foreach($article as $ak => &$av) {
        $rev_sync['ALL'][] = $av;
    }
}

$temp = $rev_sync;
$delete_info = array();
foreach($temp as $key => $val) {
    foreach($val as $k => $one) {
        if($k === 0) {
            $delete_info[$key] = array();
        }
        if($one['articleSequence'] !== '0' && $one['articleSequence'] !== '-1') {
            array_push($delete_info[$key], $k);
        }
    }
}
//2022.02.08 Lee JW - 주석 처리된 내용 부활 시킴, 뷰어 등록시 잘린 동일기사를 하나만 나오게 하기위해
foreach($delete_info as $key=> $val) {
     $cutCnt = 0;
     foreach($val as $i => $delNum) {
         foreach($temp[$key] as $artKey => $artVal){
             if($artKey == ($delNum - $cutCnt)){
                 if($artVal['subtitle']) {
                     $temp[$key][$artKey+1]['subtitle'] =$artVal['subtitle'];
                 }
                 array_splice($temp[$key], $artKey,1);
                 $cutCnt++;
             }
         }
     }
}

echo json_encode($temp);
