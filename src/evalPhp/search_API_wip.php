<?php
/**
 * User: bugwang
 * Date: 2018-12-26
 * Time: 13:18
 */
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassPage.php';
include_once __DIR__ . '/ClassStat.php';
require_once __DIR__ . "/ClassSearch.php";
include_once __DIR__ . '/ClassTypeUtil.php';
require_once __DIR__ . '/autoEvaluate.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';
include_once __DIR__ . '/columnSettingFunc.php';
//클래스 --------------------------------------------------------------------------------
$db = new ClassStat($premiumID);
$ClassPage = new ClassPage();
$ClassSearch = new ClassSearch($premiumID);
$columnSetting = getColumnSetting($db, 'WEB');
$columnSetting = $columnSetting['final'];
//검색조건 받기----------------------------------------------------------------------------
//dbconn
$db_conn = $ClassSearch->getDBConn();
$ctu = new ClassTypeUtil($db_conn);
//1.날짜
$dateStand = $_REQUEST['selectedDateStand'] ? $_REQUEST['selectedDateStand'] : "0";
$year = $_REQUEST['selYear'] ?  $_REQUEST['selYear']  : date('Y');
$month = $_REQUEST['selMon'] ? : null;
$day = $_REQUEST['selDay'] ? $_REQUEST['selDay'] : null;
//시작,끝 날짜 지정
$toYear = date('Y');
$sDate = $_REQUEST['sDate'] ? date('Y-m-d', strtotime($_REQUEST['sDate'])) : date('Y-m-d', mktime(0, 0, 0, 1, 1,$toYear));
$eDate = $_REQUEST['eDate'] ? date('Y-m-d', strtotime($_REQUEST['eDate'])) : date('Y-m-d', mktime(0, 0, 0, 12, 31, $toYear));
//2.사용자 설정 뷰어그룹
$news_me = trim($_REQUEST['selNewsMe'], ',');
$news_me = str_replace(',,', ',', $news_me);
$news_me = is_meaningful_rgx($news_me, '[0-9]+(,[0-9]+)*');
//3.검색어 - 검색범위 - 제목+내용, 기자명
$search_range = $_REQUEST['search_range'] ? $_REQUEST['search_range'] : '0' ;
$keyword = trim($_REQUEST['keyword']);
$keyword_condition = $_REQUEST['keyword_condition'] ? $_REQUEST['keyword_condition']  : 'or';
//배제어
$ex_keyword = trim($_REQUEST['ex_keyword']);
$ex_keyword_condition = $_REQUEST['ex_keyword_condition'] ? $_REQUEST['ex_keyword_condition'] : 'or';

//4.세부설정
//4-1.매체선택
$media = trim($_REQUEST['media'], ',');
$media = str_replace(',,', ',', $media);
$media = is_meaningful_rgx($media, '[0-9]+(,[0-9]+)*');
//4-1.매체구분
$category_id = $_REQUEST['category_id'];
//4-4.자동평가항목선택
$eval0 = $_REQUEST['eval0'] ? stripcslashes($_REQUEST['eval0']) : '';
//4-2.평가항목1선택
$eval1 = $_REQUEST['eval1'] ? stripcslashes($_REQUEST['eval1']) : '';
//4-3.평가항목2선택
$eval2 = $_REQUEST['eval2'] ? stripcslashes($_REQUEST['eval2']) : '';
$eval0_condition = $_REQUEST['eval0_condition'] ? stripcslashes($_REQUEST['eval0_condition']) : 'OR';
$eval2_condition = $_REQUEST['eval2_condition'] ? stripcslashes($_REQUEST['eval2_condition']) : 'OR';
//5.기사감추기, 평가제외기사, 비공개스크랩
$hUse = ($_REQUEST['hUse'] === 'true');
$news_comment = ($_REQUEST['news_comment'] === 'true');
$sbUse = ($_REQUEST['sbUse'] === 'true');

//6.정렬 - 0:제목 ,1:매체 ,2:날짜, 3:매체순서
$order_column = is_meaningful_rgx_or($_REQUEST['order_column'], 'news_title|media_name|news_date|scrap_date|news_id', 'news_date');
$order = is_meaningful_rgx_or($_REQUEST['order'], 'asc|desc', 'asc');

//7.디스플레이 수 및 페이지
$pageSize = is_meaningful_rgx_or($_REQUEST['pageSize'], '[0-9]+', '50');
$pageNo = is_meaningful_rgx($_REQUEST['pageNo'], '[0-9]+');
if (!$pageNo) {
    $pageNo = 1;
    $doTotalCount = true;
} else {
    $doTotalCount = false;
}
if ($pageSize && $pageNo) {
    $pageSize = intval($pageSize);
    $pageNo = intval($pageNo);
    $limit_back = min($pageSize, 250);
    $limit_front = $limit_back * ($pageNo - 1);
} else {
    $limit_front = 0;
    $limit_back = 0;
}

$remove_news_serial = $_REQUEST['remove_news_serial'];

//에러-------------------------------------------------------------------------------
if (!$year && (!$sDate || !$eDate)) exit;
// if ($limit_back > 50) exit;

//ClassType---------------------------------------------------------------------------
//대소제목 자동평가 준비
$ctu->classTypeAutoEvaluation($sDate, $eDate, $news_me);

//query-------------------------------------------------------------------------------
$query = "";
$query1 = "SELECT * FROM (SELECT distinct ";

$field = "`hnews`.`news_id`, `hnews`.`media_id`, `hcate`.`media_name`, `hcate`.`mediaCategory` AS `category_id`, `hcate`.`mediaCategory` AS `media_category`, `hcate`.`mediaType` AS `media_type`, `SB`.`scrapDate` AS `scrap_date`, `hnews`.`news_title` AS `article_title`, `hnews`.`news_reporter` AS `article_reporter`, `hnews`.`news_group`, `hnews`.`coordinate`, `hnews`.`news_file_name`, `hnews`.`guid`, `hnews`.`news_me`, `orgLink`, `refLink`

, if(`hnews`.`articleArea2` = '', `hnews`.`articleArea`, `hnews`.`articleArea2`) as `article_area`
, `hnews`.`news_contents` AS `article_contents`
, `hnews`.`newsTime` AS `article_datetime`
, `hnews`.`part_name`
, `hnews`.`case_name`
, `hnews`.`article_serial` AS `article_serial`
, `hnews`.`category_seq` AS `eval1_seq`

, `hnews`.`news_comment`, `hcate`.`categoryOutput`, `hnews`.`articleSequence`, `hnews`.`etc1` AS `highLight`, `hnews`.`etc2` AS `highLightWord`, `reporterGroup`.`evalClassify_seq` AS `reporterAuto`, `mediaGroup`.`evalClassify_seq` AS `mediaAuto`, CONCAT(',', GROUP_CONCAT(`newsEval`.`evalClassify_seq`), ',') AS `eval2_seqs`, `hcate`.`evalValue` AS `media_value`

, hnews.isUse as hUse
";
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

$searchInfo = array();
//1.날짜
//년/월.일
$subQueryDates = '';
switch($dateStand) {
    case "0":
        // $query2 .= " AND substr(hnews.news_date,1,4)='{$year}' ";
        // $subQueryDates = " AND substr(SB.scrapDate,1,4)='{$year}' ";
        $subQueryDates = " AND `SB`.`scrapDate` BETWEEN '{$year}-01-01' AND '{$year}-12-31' ";
        $searchInfo['date'] = "연간:" . $year; break;
    case "1":
        $month = $month < 10 ? '0'.$month : $month;
        $day = $day < 10 ? '0'.$day : $day;
        // $query2 .= " AND substr(hnews.news_date,1,7)='{$year}-{$month}' ";
        // $subQueryDates = " AND substr(SB.scrapDate,1,7)='{$year}-{$month}' ";
        $subQueryDates = " AND `SB`.`scrapDate` BETWEEN '{$year}-{$month}-01' AND '{$year}-{$month}-31' ";
        $searchInfo['date'] = "월간:" . $year . "-" . $month; break;
    case "2":
        $month = $month < 10 ? '0'.$month : $month;
        $day = $day < 10 ? '0'.$day : $day;
        // $query2 .= " AND hnews.news_date = '{$year}-{$month}-{$day}' ";
        $subQueryDates = " AND `SB`.`scrapDate` = '{$year}-{$month}-{$day}' ";
        break;
    case "3":
        // $query2 .= " AND hnews.news_date BETWEEN DATE('{$sDate}') AND DATE('{$eDate}') ";
        // $subQueryDates = " AND SB.scrapDate BETWEEN DATE('{$sDate}') AND DATE('{$eDate}') ";
        $subQueryDates = " AND `SB`.`scrapDate` BETWEEN DATE('{$sDate}') AND DATE('{$eDate}') ";
        $searchInfo['date'] = "지정:" . $sDate . "~" . $eDate; break;
    default :
        // $query2 .= " AND substr(hnews.news_date,1,4)='{$year}' ";
        // $subQueryDates = " AND substr(SB.scrapDate,1,4)='{$year}' ";
        $subQueryDates = " AND `SB`.`scrapDate` BETWEEN '{$year}-01-01' AND '{$year}-12-31' ";
        $searchInfo['date'] = "연간:" . $year; break;
}
$query2 .= $subQueryDates;

//2.사용자 뷰어 그룹 선택
if ($news_me === false) { // 입력형식 틀림(빈경우포함) -
    $query2 .= " AND `hnews`.`news_me` IN (-1)";
    $searchInfo['news_me'] = "][-1";
} else {
    $query2 .= " AND `hnews`.`news_me` IN ($news_me)";
    $searchInfo['news_me'] = "][" . $news_me;
}

//3.키워드 검색
//3-1.검색어
if ($keyword != null) {
    $keyword_arr = explode(' ', $keyword);
    $length = count($keyword_arr);
    $query2 .= " AND ( ";
    foreach ($keyword_arr as $key => $keyword_one) {
        if ($key != ($length - 1)) {
            if ($search_range === '0') {
                $query2 .= " ( hnews.news_title like '%{$keyword_one}%' OR hnews.news_contents like '%{$keyword_one}%'  ) {$keyword_condition}";
            } else if ($search_range === '1') {
                $query2 .= " ( hnews.news_reporter like '%{$keyword_one}%'  ) {$keyword_condition}";
            }
        } else if ($key == ($length - 1)) {
            //마지막 요소 일 때
            if ($search_range === '0') {
                //마지막 요소 일 때
                $query2 .= " ( hnews.news_title like '%{$keyword_one}%' OR hnews.news_contents like '%{$keyword_one}%' ) ) ";
            } else if ($search_range === '1') {
                echo 'search_range 1';
                $query2 .= " ( hnews.news_reporter like '%{$keyword_one}%' ) )";
            }
        }
        $i++;
    }
    $searchInfo['keyword'] = "][" . $keyword;
    $searchInfo['keyword_co'] = "][" . $keyword_condition;
} else {
    $searchInfo['keyword'] = "][";
    $searchInfo['keyword_co'] = "][";
}

//3-2.배제어
if ($ex_keyword != null) {
    $ex_keyword_arr = explode(' ', $ex_keyword);
    $length = count($ex_keyword_arr);
    $query2 .= " AND ( ";
    foreach ($ex_keyword_arr as $key => $ex_keyword_one) {
        if ($key != ($length - 1)) {
            //마지막 요소가 아닐때
            if ($search_range === '0') {
                $query2 .= " !( hnews.news_title like '%{$ex_keyword_one}%' OR hnews.news_contents like '%{$ex_keyword_one}%'  ) {$ex_keyword_condition} ";
            } else if ($search_range === '1') {
                $query2 .= " !( hnews.news_reporter like '%{$ex_keyword_one}%'  ) {$ex_keyword_condition} ";
            }
        } else if ($key == ($length - 1)) {
            if ($search_range === '0') {
                //마지막 요소 일 때
                $query2 .= "  !( hnews.news_title like '%{$ex_keyword_one}%' OR hnews.news_contents like '%{$ex_keyword_one}%' ) )  ";
            } else if ($search_range === '1') {
                $query2 .= "  !( hnews.news_reporter like '%{$ex_keyword_one}%' ) ) ";
            }
        }

    }
    $searchInfo['ex_keyword'] = "][" . $ex_keyword;
    $searchInfo['ex_keyword_co'] = "][" . $ex_keyword_condition;

} else {
    $searchInfo['ex_keyword'] = "][";
    $searchInfo['ex_keyword_co'] = "][";
}


//4-1.세부설정 - 매체선택 (배열로 받음)
// if ($media != null) {
//     $media_arr = array();
//     foreach ($media as $cateK => $cate) {
//         foreach ($cate as $mediaK => $mediaVal) {
//             array_push($media_arr, $mediaVal['media_id']);
//         }
//     }
//     $query2 .= "AND hnews.media_id IN ('";
//     $media_string = implode("','", $media_arr);
//     $query2 .= "{$media_string}')";
//     $searchInfo['media'] = "][매체:-" . implode("-", $media_arr);
// } else {
//     $searchInfo['media'] = "][매체:";
// }
if ($media === false) {
    $query2 .= " AND `hnews`.`media_id` IN (-1)";
    $searchInfo['media'] = "][매체:-1";
} else if ($media !== '0') {
    $query2 .= " AND `hnews`.`media_id` IN ($media)";
    $searchInfo['media'] = "][매체:" . $media;
} else {
    $searchInfo['media'] = "][매체:";
}

//4-1.매체구분
if ($category_id) {
    if (!is_array($category_id)) {
        $cate_id_arr = explode(",", $category_id);
    } else {
        $cate_id_arr = $category_id;
    }

    foreach ($cate_id_arr as $key => $one) { //cate_0, cate_1 , ... 형태로 넘어옴
        $cate_id_arr[$key] = substr($cate_id_arr[$key], 5, 1);
    }
    $query2 .= " AND hnews.category_id IN ('";
    $cate_id_string = implode("','", $cate_id_arr);
    $query2 .= "{$cate_id_string}')";
}

//4-2.세부설정 - 평가항목1
if ($eval1 != null) {
    if (!is_array($eval1)) {
        $eval1_arr = explode(",", $eval1);
    } else {
        $eval1_arr = $eval1;
    }
    $eval1_str = implode(",", $eval1_arr);

    $query2 .= " AND hnews.category_seq IN ({$eval1_str})  ";

    $searchInfo['eval1'] = "][평가1:" . implode("-", $eval1_arr);
} else {
    $searchInfo['eval1'] = "][평가1:";
}

/*
 * 자동평가&평가2 사전
 */
$eval0Option = ($eval0_condition === 'OR') ? 2 : 1;
$eval2Option = ($eval2_condition === 'OR') ? 2 : 1;
$evalOptions = $eval2Option * $eval0Option;
$eval0Arr = $eval0 ? explode(',', $eval0) : array(); $eval0Cnt = count($eval0Arr);
$eval2Arr = $eval2 ? explode(',', $eval2) : array(); $eval2Cnt = count($eval2Arr);
$evalQuery = '';
if (count($eval0Arr) > 0 || count($eval2Arr) > 0) {
    $evalQuery = 'WHERE';
    if ($eval0Cnt) {
        $evalQuery .= " (";
        for ($i = 0; $i < $eval0Cnt; $i++) {
            $evalQuery .= "`T`.`eval2_seqs` LIKE '%," . intval($eval0Arr[$i]) . ",%'";
            if ($i < $eval0Cnt - 1) $evalQuery .= (intval($eval0Option) === 1) ? ' AND ' : ' OR ';
        }
        $evalQuery .= ")";
    }
    if ($eval2Cnt) {
        if ($eval0Cnt > 0) $evalQuery .= ' AND';
        $evalQuery .= " (";
        for ($i = 0; $i < $eval2Cnt; $i++) {
            $evalQuery .= "`T`.`eval2_seqs` LIKE '%," . intval($eval2Arr[$i]) . ",%'";
            if ($i < $eval2Cnt - 1) $evalQuery .= (intval($eval2Option) === 1) ? ' AND ' : ' OR ';
        }
        $evalQuery .= ")";
    }
}

//5.기사감추기포함,평가제외기사, 비공개스크랩(1:비포함, 0또는null:포함)
if ($hUse === false) {
    $query2 .= " AND hnews.isUse != 1 ";
    $searchInfo['isUse'] = "][기사숨기기제외";
} else {
    $searchInfo['isUse'] = "][기사숨기기포함";
}
if ($news_comment === false) {
    $query2 .= " AND hnews.news_comment != 1 ";
    $searchInfo['comment'] = "][평가제외 제외";
} else {
    $searchInfo['comment'] = "][평가제외 포함";
}
if ($sbUse === false) {
    $query2 .= " AND `SB`.isUse != 0  ";
}

if ($remove_news_serial != null) {
    if (!is_array($remove_news_serial)) {
        $remove_news_serial_arr = explode(",", $remove_news_serial);
    } else {
        $remove_news_serial_arr = $remove_news_serial;
    }
    $remove_news_serial_str = implode(",", $remove_news_serial_arr);

    $query2 .= " AND hnews.article_serial NOT IN ({$remove_news_serial_str}) ";
    $searchInfo['remove']= "][있음";
}else{
    $searchInfo['remove']= "][없음";
}

$query2 .= ' GROUP BY `hnews`.`news_id`) `T` ';
logs('query2 : ' . $query2);

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

// 자동평가 준비 - news_id
$query_auto = "{$query1}{$field_auto}{$table_string}{$query2}";
logs('search.qry_auto : ' . $query_auto);
$result_auto = mysqli_query($db_conn, $query_auto) or die(mysqli_error($db_conn) . ' E001-E');
$news_id_arr = array();
while ($row = mysqli_fetch_assoc($result_auto)) {
    $news_id_arr[] = $row['news_id'];
}

// 자동평가 생성
$ck = autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, -1);
if ($ck["success"]) $ctu->InsertClassType();

$query2 .= $evalQuery;

//6.정렬 - 0:제목 ,1:매체 ,2:날짜, 3:등록순서
$query2 .= " ORDER BY ";
if ($order_column === 'none') {
    $query2 .= " `article_datetime desc`, `news_id desc` ";
} else if ($order_column == "news_title") {
    $query2 .= " `article_title` {$order} , `article_datetime` {$order}, `news_id` {$order} ";
} else if ($order_column == "media_name") {
    $query2 .= " `media_name` {$order} , `article_datetime` {$order}, `news_id` {$order} ";
} else if ($order_column == "news_date") {
    $query2 .= " `article_datetime` {$order} , `news_id` {$order} ";
} else if (equals($order_column, 'scrap_date')) {
    $query2 .= " `scrap_date` {$order} , `article_datetime` {$order} , `news_id` {$order} ";
} else if ($order_column == "news_id") {
    $query2 .= " `news_id` {$order} , `article_datetime` {$order} ";
}

//7.limit 및 page
if ($limit_back > 0) {
    $query2 .= " limit {$limit_front}, {$limit_back} ";
}

// entire record without paging
if ($doTotalCount) {
    $queryCount = "{$query1}{$field}{$table_string}{$query2}";
    $queryCount = str_replace('SELECT * FROM ', 'SELECT COUNT(*) AS `total` FROM ', $queryCount);
    $resultCount = mysqli_query($db_conn, $queryCount) or die(mysqli_error($db_conn) . ' E001-C');
    $resultCountRow = mysqli_fetch_assoc($resultCount);
    $resultCountRow = intval($resultCountRow['total']);
}

//쿼리 합침
$query = "{$query1}{$field}{$table_string}{$query2}";
logs('search.qry : ' . $query);

//쿼리 결과 받아올 변수
$article = array();

//쿼리 결과 받아오기
logs('query.start : ' . time()); // DEBUG
$result = mysqli_query($db_conn, $query) or die(mysqli_error($db_conn) . ' E001-M');
logs('query.end : ' . time()); // DEBUG

//쿼리 결과 담기
$article = array();
$evals = array(); $config_tmp; $eval2m2CntMax = 0;
foreach ($config_eval['group']['M2'] as $k => $v) {
    if (equals($v['isUse'], 'Y')) $eval2m2CntMax++;
}
$i = 0; $eval2_seq_arr = array(); $page_size_pixel = 0; $j = 0;
logs('ready.start : ' . time()); // DEBUG
while ($row = mysqli_fetch_assoc($result)) {
    // common
    foreach ($columnSetting as $ck => $cv) {
        $field = $cv['field']; $use = equals($cv['use'], 'Y');
        if ($use) {
            $article[$i][$field] = $row[$field];
        }
    }

    $article[$i]['index'] = ($i+1);
    // $article[$i]['index'] = 0;
    // $article[$i]['article_title'] = $row['news_title'];
    // $article[$i]['media_name'] = $row['media_name'];
    $article[$i]['article_reporter'] = $row['article_reporter'];
    // $article[$i]['scrap_date'] = $row['news_date'];
    // article_date <= calcArticleValue
    // article_size <= calcArticleValue
    // article_page <= calcArticleValue
    // article_length <= calcArticleValue
    // $article[$i]['media_value'] = $row['media_value'];
    // $article[$i]['media_category'] = $row['category_id'];
    // $article[$i]['media_type'] = $row['media_type'];
    // 13 eval_score <= calcArticleValue
    // 14 eva1~5 : reference
    // 15 ev1_big|mid|sml : reference
    // 16 ev2_1001~ : reference

    // custom (+legacy)
    $article[$i]['category_id'] = $row['media_category'];
    $article[$i]['media_category'] = $row['media_category'];
    $article[$i]['media_type'] = $row['media_type'];
    $article[$i]['news_group'] = $row['news_group'];
    $article[$i]['highLight'] = $row['highLight'];
    $article[$i]['highLightWord'] = $row['highLightWord'];
    $article[$i]['news_id'] = $row['news_id'];
    $article[$i]['media_id'] = $row['media_id'];
      // category_name
    $article[$i]['coordinate'] = $row['coordinate'];
    $article[$i]['news_file_name'] = $row['news_file_name'];
    $article[$i]['news_comment'] = $row['news_comment'];
    $article[$i]['guid'] = $row['guid'];
    $article[$i]['part_name'] = $row['part_name'];
    $article[$i]['case_name'] = $row['case_name'];
    $article[$i]['article_serial'] = $row['article_serial'];

    $page_size_pixel = $ClassPage->getPagePdfSizeByArticleSerial($row['article_serial']);
    $article[$i]['page_size_pixel'] = $page_size_pixel ? $page_size_pixel : 2400;

    $article[$i]['category_output'] = $row['categoryOutput'];
    $article[$i]['mediaAuto'] = $row['mediaAuto'];
    $article[$i]['reporterAuto'] = $row['reporterAuto'];

    // dead
    $article[$i]['news_me'] = $row['news_me'];
    $article[$i]['articleSequence'] = $row['articleSequence'];
    $article[$i]['sbUse'] = $row['sbUse'];
    $article[$i]['hUse'] = $row['hUse'];

    // temporary
    $article[$i]['tmp_article_area'] = $row['article_area'];
    $article[$i]['tmp_article_datetime'] = $row['article_datetime'];
    $article[$i]['tmp_article_contents'] = $row['article_contents'];
    $article[$i]['tmp_article_serial'] = $row['article_serial'];
    $article[$i]['tmp_case_name'] = $row['case_name'];
    $article[$i]['tmp_part_name'] = $row['part_name'];

    // unknown
    $article[$i]['eval1_seq'] = $row['eval1_seq'];
    $article[$i]['eval2_seqs'] = $row['eval2_seqs'];

    $tmp_eval2_seq_arr = explode(',', $row['eval2_seqs']);
    $eval2_seq_arr = array();
    foreach ($tmp_eval2_seq_arr as $v) {
      if (intval($v) > 0) $eval2_seq_arr[] = $v;
    }

    // foreach ($eval2_seq_arr as $ek => $ev) {
    //     if ($ev) $article[$i]['eval2'][] = array('eval2_seq' => $ev);
    // }

    $article[$i]['hidden'] = false;

    $j = $row['news_id'];
    // wip + evals
    $evals[$j] = array();
    $evals[$j]['eval1'] = array();
    $evals[$j]['eval1']['eval1_seq'] = $row['eval1_seq'];
    $config_tmp = $config_eval['item']['M1'][$row['eval1_seq']];
    if ($row['eval1_seq'] && $config_tmp) {
        $evals[$j]['eval1']['eval1_name'] = $config_tmp['value'];
        $evals[$j]['eval1']['eval1_upper'] = $config_tmp['group_seq'];
    } else {
        $evals[$j]['eval1']['eval1_name'] = null;
        $evals[$j]['eval1']['eval1_upper'] = null;
    }
    $evals[$j]['eval2Cnt'] = count($eval2_seq_arr);
    $evals[$j]['eval2Value'] = array();
    $evals[$j]['eval2atCnt'] = 0;
    $evals[$j]['eval2m2Cnt'] = 0;
    $evals[$j]['eval2m2CntMax'] = $eval2m2CntMax;
    // wip keepgoing
    foreach ($eval2_seq_arr as $ek => $ev) {
        $config_tmp = $config_eval['item']['AT_M2'][$ev];
        if (!$config_tmp) continue;
        if (equals($config_tmp['group_isAuto'], 'Y')) {
            $evals[$j]['eval2atCnt']++;
        } else {
            $evals[$j]['eval2m2Cnt']++;
        }
        $evals[$j]['eval2Value'][$config_tmp['group_seq']] = array(
            'eval2_name' => $config_tmp['value'],
            'eval2_seq' => $ev,
        );
    }

    $i++;
}
logs('ready.end : ' . time()); // DEBUG

if (count($searchInfo) > 0) {
// 어드민 뷰어 관련 DB입력 tealight 201890110 평가 >검색 > 검색기사 관리 출력용
    $rQuery = str_replace("'", "\'", $query);
    $rQuery = str_replace('"', '\"', $rQuery);


    $qry = "REPLACE INTO `queryTemp`  ( `no`, `qry` ) VALUES ('1','{$rQuery}')";
    // mysqli_query($db_conn, $qry) or die(mysqli_error($db_conn) . ' E001-1');
    mysqli_query($db_conn, $qry);

    $searchInfo['reporter'] =  "][";

    $searchStr = $searchInfo['date'].$searchInfo['isUse'].$searchInfo['comment'].$searchInfo['news_me'].$searchInfo['media'].$searchInfo['eval1'].$searchInfo['eval2'];
    $searchStr .=$searchInfo['keyword'].$searchInfo['keyword_co'].$searchInfo['ex_keyword'].$searchInfo['ex_keyword_co'].$searchInfo['reporter'].$searchInfo['remove'];
    $qry = "REPLACE INTO `queryTemp` ( `no`, `qry` ) VALUES ('2','{$searchStr}')";
    // mysqli_query($db_conn, $qry) or die(mysqli_error($db_conn) . ' E001-2');
    mysqli_query($db_conn, $qry);
}

//기사크기 및 기사가치 계산
foreach($article as $ak => &$av) {
    $calc_result = calcArticleValue($av, $config_eval);
    $av = array_merge($av, $calc_result);
    $av['media_category_name'] = $config_eval['category'][$av['media_category']]['category_name'];
    $av['media_type_name'] = $config_eval['type'][$av['media_type']]['name'];

    $eval1Tree = getEval1NamesArray($config_eval['item']['M1'], $av['eval1_seq']);
    $av['ev1_big'] = $eval1Tree[0] ? $eval1Tree[0] : '';
    $av['ev1_mid'] = $eval1Tree[1] ? $eval1Tree[1] : '';
    $av['ev1_sml'] = $eval1Tree[2] ? $eval1Tree[2] : '';

    $av = array_merge($av, getEval2Names($av['eval2'], $config_eval));

    $article[$ak]['media_value'] = intval($article[$ak]['media_value']);
    if ($article[$ak]['part_name'] === 'aef') {
      $article[$ak]['article_contents'] = $article[$ak]['tmp_article_contents'];
    }
    unset($article[$ak]['tmp_article_contents']);
    unset($article[$ak]['tmp_article_area']);
    unset($article[$ak]['tmp_article_datetime']);
    unset($article[$ak]['tmp_article_serial']);
    unset($article[$ak]['tmp_case_name']);
    unset($article[$ak]['tmp_part_name']);
    unset($article[$ak]['category_output']);
}

/* 폴더명 */
$articles = array();
foreach($article as $ak => &$av)
{
    $foldername = $config_eval['category'][$av['media_category']]['category_name'];
    $av['media_category_name'] = $foldername;
    $av['media_type_name'] = $config_eval['type'][$av['media_type']]['name'];
    
    if (!is_array($articles[$foldername])) {
        $articles[$foldername] = array();
    }
    array_push($articles[$foldername], $av);
}

$result_final = array();
$result_final['articles'] = $articles;
$result_final['config'] = $config_eval;
$result_final['evals'] = $evals;
$result_final['doTotalCount'] = $doTotalCount; // DEBUG
if ($doTotalCount) {
    $result_final['resultCountRow'] = $resultCountRow; // DEBUG
}
$result_final['count'] = $i; // DEBUG - WIP
echo json_encode($result_final);