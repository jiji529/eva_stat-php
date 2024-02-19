<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-11-26
 * Time: 오후 7:32
 */
// edit test from new plugin
// set_time_limit(300);
// ini_set('max_execution_time', 0);
$time_start = microtime(true);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassPage.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/ClassSearch.php';
include_once __DIR__ . '/ClassTypeUtil.php';
include_once __DIR__ . '/autoEvaluate.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';

$result = array();
$db = new ClassStat($premiumID);
$ClassPage = new ClassPage();
$ClassSearch = new ClassSearch($premiumID);
$db_conn = $ClassSearch->getDBConn();
$ctu = new ClassTypeUtil($db_conn);
if ($db->Error()) {
    $result['success'] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    die(json_encode($result));
}

$_CONF_INDEX_ALL = 128;
$_CATEGORY_VALUE_META = -99;
$_MEDIA_TABLE_NAME = 'hnp_category';

$toYear = date('Y');
$dateType = $_REQUEST['dt'] ? $_REQUEST['dt'] : '1';//연간 월간 기간
$startDate = $_REQUEST['ds'] ? date('Y-m-d', strtotime($_REQUEST['ds'])) : date('Y-m-d', mktime(0, 0, 0, 1, 1, $toYear)); //시작일
$endDate = $_REQUEST['de'] ? date('Y-m-d', strtotime($_REQUEST['de'])) : date('Y-m-d', mktime(0, 0, 0, 12, 31, $toYear));//종료일
$news_me = trim($_REQUEST['nm'], ',');
$news_me = str_replace(',,', ',', $news_me);
$news_me = is_meaningful_rgx($news_me, '[0-9]+(,[0-9]+)*');    // 조석간
$searchField = $_REQUEST['so'] ? stripcslashes($_REQUEST['so']) : '1';    // 검색 영역(기본) 제목+내용 제목 내용
$keywordOption = $_REQUEST['ko'] ? stripcslashes($_REQUEST['ko']) : '1'; // 검색어 AND OR
$keyword = $_REQUEST['k'] ? stripcslashes($_REQUEST['k']) : ''; //검색어
$notKeywordOption = $_REQUEST['nko'] ? stripcslashes($_REQUEST['nko']) : '1'; //배제어 AND OR
$notKeyword = $_REQUEST['nk'] ? stripcslashes($_REQUEST['nk']) : ''; //배제어
$articleHidden = ($_REQUEST['ah'] === '1');    // 숨김 기사 포함
$doNotEval = ($_REQUEST['ne'] === '1');    // 평가제외 포함
$hiddenScrap = ($_REQUEST['hs'] === '1');    // 비공개스크랩 포함
$media = trim($_REQUEST['m']);
$media = str_replace(',,', ',', $media);
$media = is_meaningful_rgx($media, '[0-9]+(,[0-9]+)*'); // 매체
$eval0 = $_REQUEST['e0'] ? stripcslashes($_REQUEST['e0']) : ''; //자동평가
$eval1 = $_REQUEST['e1'] ? stripcslashes($_REQUEST['e1']) : ''; //평가 1
$eval2 = $_REQUEST['e2'] ? stripcslashes($_REQUEST['e2']) : ''; //평가 2
$eval0Option = $_REQUEST['e0o'] ? stripcslashes($_REQUEST['e0o']) : '2'; //자동평가 and or  옵션
$eval2Option = $_REQUEST['e2o'] ? stripcslashes($_REQUEST['e2o']) : '2'; //평가 2 and or  옵션

$column = "`hnews`.`news_id`, `hnews`.`media_id`, `hnews`.`category_name` AS `category_name_old`, `hcate`.`media_name`, `hcate`.`mediaCategory` AS `category_id`, `hcate`.`mediaCategory` AS `media_category`, `hcate`.`mediaType` AS `media_type`, `hnews`.`news_date`, if(`hnews`.`articleArea2` = '', `hnews`.`articleArea`, `hnews`.`articleArea2`) AS `article_area`, `hnews`.`news_contents`, `hnews`.`article_serial`, `eval1`.`seq` as `eval1_seq`, `hnews`.`newsTime`, `hnews`.`news_comment`, `hnews`.`coordinate`, `hcate`.`evalValue` AS `media_value`, `hcate`.`categoryOutput`, `hnews`.`case_name`, `hnews`.`part_name`, `eval2`.`seq` as `eval2_seq`, `eval2`.`evaluation_seq` AS `eval2_group_seq`, CONCAT(',', GROUP_CONCAT(`eval2`.`seq`), ',') AS `eval2_seqs`";
$query = "SELECT DISTINCT $column FROM `hnp_news` AS `hnews` ";
$query .= "LEFT JOIN `category` AS `eval1` ON `eval1`.`seq` = `hnews`.`category_seq` ";
$query .= "LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id` ";
$query .= "LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq` ";
$query .= "LEFT JOIN `evaluation` ON `evaluation`.`seq` = `eval2`.`evaluation_seq` ";
$query .= "LEFT JOIN `".$_MEDIA_TABLE_NAME."` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id` ";
$query .= "LEFT JOIN `scrapBook` AS `SB` ON `SB`.`no` = `hnews`.`scrapBookNo` ";
$query .= "WHERE `hnews`.`articleSequence` = '0' ";
$query .= "AND `hcate`.`media_name` NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실') ";
$query .= "AND `hcate`.`mediaType` != -98 ";
$query .= "AND `hcate`.`isUse` = '0' ";
$query .= "AND `hnews`.`news_me` IN (SELECT `LVALUE` FROM `DISPLAY_INFO` WHERE `LTYPE` = 1 AND `BUESSURE` = 1) ";

$query_auto = "SELECT DISTINCT `hnews`.`news_id` FROM `hnp_news` AS `hnews` ";
// $query_auto = "SELECT DISTINCT `hnews`.`news_id`, GROUP_CONCAT(`hnews`.`news_id`) AS `news_id_str` FROM `hnp_news` AS `hnews` ";
$query_auto .= "LEFT JOIN `".$_MEDIA_TABLE_NAME."` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id` ";
$query_auto .= "LEFT JOIN `scrapBook` AS `SB` ON `SB`.`no` = `hnews`.`scrapBookNo` ";
$query_auto .= "WHERE `hnews`.`articleSequence` = '0' ";
$query_auto .= "AND `hcate`.`media_name` NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실') ";
$query_auto .= "AND `hcate`.`mediaType` != -98 ";
$query_auto .= "AND `hcate`.`isUse` = '0' ";

$subQuery = '';

//ClassType---------------------------------------------------------------------------
//대소제목 자동평가 준비
$ctu->classTypeAutoEvaluation($startDate, $endDate, $news_me);

/**
 * 날짜 범위
 */
if ($startDate && $endDate) {
    $subQuery .= "AND `SB`.`scrapDate` BETWEEN  '$startDate' AND '$endDate' ";
}
/**
 * 스크랩 구분 - 조석간
 */
if ($news_me === false) {
    $subQuery .= " AND `hnews`.`news_me` in (-1) ";
} else {
    $subQuery .= " AND `hnews`.`news_me` in ($news_me) ";
}

/**
 * 기사 숨김
 */
if (!$articleHidden) {
    $subQuery .= "AND `hnews`.`isUse` != '1' ";
}
/**
 * 평가안함
 */
if (!$doNotEval) {
    $subQuery .= " AND `hnews`.`news_comment` != '1' ";
}
/**
 * 숨김기사
 */
if (!$hiddenScrap) {
    $subQuery .= "AND `SB`.`isUse` = '1' ";
}

/**
 * 매체
 */
if ($media === false) {
    $subQuery .= "AND `hnews`.`media_id` in (-1) ";
} else if ($media !== '0') {
    $subQuery .= "AND `hnews`.`media_id` in ($media) ";
}
$query_auto .= $subQuery;
/**
 * 평가1
 */
if ($eval1) {
    $subQuery .= "AND `eval1`.`seq` in ($eval1) ";
}


/*
 * 자동평가&평가2 사전
 */
$eval0Arr = $eval0 ? explode(',', $eval0) : array(); $eval0Cnt = count($eval0Arr);
$eval2Arr = $eval2 ? explode(',', $eval2) : array(); $eval2Cnt = count($eval2Arr);
$evalQuery = '';
if (count($eval0Arr) > 0 || count($eval2Arr) > 0) {
    $evalQuery = 'WHERE';
    if ($eval0Cnt > 0) {
        $evalQuery .= " (";
        for ($i = 0; $i < $eval0Cnt; $i++) {
            $evalQuery .= "`T`.`eval2_seqs` LIKE '%," . intval($eval0Arr[$i]) . ",%'";
            if ($i < $eval0Cnt - 1) $evalQuery .= (intval($eval0Option) === 1) ? ' AND ' : ' OR ';
        }
        $evalQuery .= ")";
    }
    if ($eval2Cnt > 0) {
        if ($eval0Cnt > 0) $evalQuery .= ' AND';
        $evalQuery .= " (";
        for ($i = 0; $i < $eval2Cnt; $i++) {
            $evalQuery .= "`T`.`eval2_seqs` LIKE '%," . intval($eval2Arr[$i]) . ",%'";
            if ($i < $eval2Cnt - 1) $evalQuery .= (intval($eval2Option) === 1) ? ' AND ' : ' OR ';
        }
        $evalQuery .= ")";
    }
}

/**
 * 키워드 및 배제어
 * $searchField : "1"(제목+내용), "2"(제목), "3"(내용)
 * $keywordOption : 1(AND), 2(OR)
 * $keyword : 검색어
 * $notKeyword : 배제어
 */
/* 검색어 */
if ($keyword) {
    $keyword_arr = explode(' ', $keyword);
    $length = count($keyword_arr);
    $keyword = " AND ( ";
    foreach ($keyword_arr as $key => $keyword_one) {
        
        switch ($searchField) {
            case '1':
                $keyword .= " ( `news_title` like '%{$keyword_one}%' OR `news_contents` like '%{$keyword_one}%' ) ";
                break;
            case '2':
                $keyword .= " ( `news_title` like '%{$keyword_one}%' ) ";
                break;
            case '3':
                $keyword .= " ( `news_contents` like '%{$keyword_one}%' ) ";
                break;
            default:
                $result['message'] = "Search Option error";
                break;
        }
        
        if ($key != ($length - 1)) { // last keyword
            switch ($keywordOption) {
                case '1':
                    $keyword .= " AND ";
                    break;
                case '2':
                    $keyword .= " OR ";
                    break;
                default:
                    $result['message'] = "Search Option error";
                    break;
            }            
        } //if
    } //for
    $subQuery .= $keyword . " ) ";
} //if keyword
/* 배제어 */
if ($notKeyword) {
    $notKeyword_arr = explode(' ', $notKeyword);
    $length = count($notKeyword_arr);
    $notKeyword = " AND ( ";
    foreach ($notKeyword_arr as $key => $keyword_one) {
        
        switch ($searchField) {
            case '1':
                $notKeyword .= " !( `news_title` like '%{$keyword_one}%' OR `news_contents` like '%{$keyword_one}%' ) ";
                break;
            case '2':
                $notKeyword .= " !( `news_title` like '%{$keyword_one}%' ) ";
                break;
            case '3':
                $notKeyword .= " !( `news_contents` like '%{$keyword_one}%' ) ";
                break;
            default:
                $result['message'] = "Search Option error";
                break;
        }
        
        if ($key != ($length - 1)) { // last keyword
            switch ($keywordOption) {
                case '1':
                    $notKeyword .= " OR ";
                    break;
                case '2':
                    $notKeyword .= " AND ";
                    break;
                default:
                    $result['message'] = "Search Option error";
                    break;
            }
        } //if
    } //for
    $subQuery .= $notKeyword . " ) ";
} //if notKeyword

// if ($keyword || $notKeyword) {
//     if ($keyword) {
//         if ($keywordOption === '1') {
//             $keyword = '+' . preg_replace('/ +/', '*+', $keyword) . '*';
//         } else {
//             $keyword = preg_replace('/ +/', '* ', $keyword) . '*';
//         }
//     }

//     if ($notKeyword) {
//         if ($notKeywordOption === '1') {
//             $keyword .= ' -' . preg_replace('/ +/', '* -', $notKeyword) . '*';
//         } else {
//             $notKeyword = '-' . preg_replace('/ +/', '* -', $notKeyword) . '*';
//             $notKeyword = explode(' ', $notKeyword);
//         }
//     }

//     switch ($searchField) {
//         case '2':
//             if (is_array($notKeyword)) {
//                 $subQuery .= "AND ( ";
//                 foreach ($notKeyword as $key => $nk) {
//                     if ($key !== 0) {
//                         $subQuery .= "OR ";
//                     }
//                     $subQuery .= "MATCH(`news_title`) AGAINST('$keyword $nk' IN BOOLEAN MODE) ";
//                 }
//                 $subQuery .= ") ";
//             } else {
//                 $subQuery .= "AND MATCH(`news_title`) AGAINST('$keyword' IN BOOLEAN MODE) ";
//             }
//             break;
//         case '3':
//             if (is_array($notKeyword)) {
//                 $subQuery .= "AND ( ";
//                 foreach ($notKeyword as $key => $nk) {
//                     if ($key !== 0) {
//                         $subQuery .= "OR ";
//                     }
//                     $subQuery .= "MATCH(`news_contents`) AGAINST('$keyword $nk' IN BOOLEAN MODE) ";
//                 }
//                 $subQuery .= ") ";
//             } else {
//                 $subQuery .= "AND MATCH(`news_contents`) AGAINST('$keyword' IN BOOLEAN MODE) ";
//             }

//             break;
//         default:
//             if (is_array($notKeyword)) {
//                 $subQuery .= "AND ( ";
//                 foreach ($notKeyword as $key => $nk) {
//                     if ($key !== 0) {
//                         $subQuery .= "OR ";
//                     }
//                     $subQuery .= "MATCH(`news_title`,`news_contents`) AGAINST('$keyword $nk' IN BOOLEAN MODE) ";
//                 }
//                 $subQuery .= ") ";
//             } else {
//                 $subQuery .= "AND MATCH(`news_title`,`news_contents`) AGAINST('$keyword' IN BOOLEAN MODE)  ";
//             }

//             break;
//     }
// }

// 공통 설정
$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

// 자동평가 준비 - news_id
logs('stat.queryAuto : ' . $query_auto);
$db->Query($query_auto);
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
} else {
    $news_id_cnt = 0; $news_id_arr = array();
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
        if ($news_id_cnt < 65535) {
            $news_id_arr[] = $row['news_id'];
            $news_id_cnt++;
        }
    }
}

// 자동평가 생성
$ck = autoEvaluate($db, $config_eval, $news_id_arr, $premiumID, -1);
if ($ck["success"]) $ctu->InsertClassType();

$query_full = 'SELECT * FROM (' . $query . $subQuery . ' GROUP BY `hnews`.`news_id`) `T` ' . $evalQuery . ' ORDER BY `T`.`media_name`';
logs('stat.query : ' . $query_full);
$db->Query($query_full);
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

$data = array();
$sortList = array(); $page_size_pixel = 0;
if ($db->RowCount() > 0) {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
        $news_id = $row['news_id'];
        if (!isset($data[$news_id])) {
            $sortList[] = $row['media_name']; // 매체 정렬
            if ($dateType === "1") {
                $target_date = date('m', strtotime($row['news_date']));
            } else if ($dateType === "2") {
                $target_date = date('Ymd', strtotime($row['news_date']));
            } else {
                $target_date = date('Ym', strtotime($row['news_date']));
                if (date_diff(new DateTime($startDate), new DateTime($endDate))->days < 31) {
                    $target_date = date('Ymd', strtotime($row['news_date']));
                }
            }
            $page_size_pixel = $ClassPage->getPagePdfSizeByArticleSerial($row['article_serial']);
            $data[$news_id] = array(
                'news_id' => $news_id,
                'media_name' => $row['media_name'],
                'category_name_old' => $row['category_name_old'],
                'scrap_date' => $row['news_date'],
                // article_date <= calcArticleValue
                // article_size <= calcArticleValue
                // article_page <= calcArticleValue
                // article_length <= calcArticleValue
                'media_value' => $row['media_value'],
                'media_category' => $row['media_category'],
                'media_type' => $row['media_type'],
                // 13 eval_score <= calcArticleValue

                'coordinate' => $row['coordinate'],
                'media_id' => $row['media_id'],
                'category_id' => $row['category_id'],
                'category_output' => $row['categoryOutput'],
                'target_date' => $target_date,
                'news_comment' => $row['news_comment'],
                'page_size_pixel' => $page_size_pixel ? $page_size_pixel : 2400,

                // temporary
                'tmp_article_area' => $row['article_area'],
                'tmp_article_datetime' => $row['newsTime'],
                'tmp_article_contents' => $row['news_contents'],
                'tmp_article_serial' => $row['article_serial'],
                'tmp_case_name' => $row['case_name'],
                'tmp_part_name' => $row['part_name'],

                'eval1_seq' => $row['eval1_seq'],
                'eval2' => array(),
                'eval2_seqs' => $row['eval2_seqs']
            );
/*
            if ($row['eval2_seq']) {
                $data[$news_id]['eval2'][] = array(
                    'eval2_seq' => $row['eval2_seq']
                );
            }*/
        } else {/*
            $data[$news_id]['eval2'][] = array(
                'eval2_seq' => $row['eval2_seq']
            );*/
        }
    }
}

// 기사가치 생성
foreach ($data as &$dv) {
  $calc_result = calcArticleValue($dv, $config_eval);
  $dv = array_merge($dv, $calc_result);
  unset($dv['tmp_article_area']);
  unset($dv['tmp_article_datetime']);
  unset($dv['tmp_article_contents']);
  unset($dv['tmp_article_serial']);
  unset($dv['tmp_case_name']);
  unset($dv['tmp_part_name']);
  unset($dv['category_output']);
}

array_multisort($sortList, SORT_ASC, $data);

$result['totalCount'] = count($sortList);
$result['data'] = $data;
$result['success'] = true;

$time_end = microtime(true);
$execution_time = ($time_end - $time_start);
logs('limit : ' . ini_get('memory_limit'));
logs('peak : ' . memory_get_peak_usage(true) . ' bytes');
logs('expire : ' . ini_get('max_execution_time'));
logs('elapsed : ' . $execution_time . ' s');

echo json_encode($result);
