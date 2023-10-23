<?php

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/getConfigEval.php';

$year = $_REQUEST['year'];
$month = $_REQUEST['month'];
$days = $_REQUEST['days'];

$startDate = date("Y-m-d", mktime(0, 0, 0, $month, 1, $year));
$endDate = date("Y-m-d", mktime(0, 0, 0, $month, $days, $year));

$dayArr = array();
$dayOnlyNum = array();

$db = new ClassStat($premiumID);

// 공통 설정
$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}
$eval2SeqsCntMax = 0;
foreach ($config_eval['group']['M2'] as $ck => $cv) {
    if (equals($cv['isUse'], 'Y')) {
        $eval2SeqsCntMax++;
    }
}
$eval1Use = equals($config_eval['policy']['US']['M1']['value'], 'Y');
$eval2Use = equals($config_eval['policy']['US']['M2']['value'], 'Y');

$query = "SELECT A.*, B.`evalClassify_seq`, B.`eval_count`, B.`eval2_seqs` FROM (
  SELECT
    `hnews`.`news_id`, `hnews`.`category_seq` , `hnews`.`news_comment`, `hnews`.`news_date`
  FROM `hnp_news` AS `hnews`
    LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id`
  WHERE `hnews`.`isUse` = 0 AND `hnews`.`articleSequence` in ('0', '-1')
    AND `hnews`.`news_date` BETWEEN '$startDate' AND '$endDate'
    AND `hcate`.`media_name` NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실')
    AND `hcate`.`mediaType` != -98
  GROUP BY `hnews`.`news_id` ORDER BY `hnews`.`news_id`
) A LEFT OUTER JOIN (
  SELECT
    `hnews`.`news_id`, `newsEval`.`evalClassify_seq`, COUNT(`hnews`.`news_id`) AS `eval_count`, GROUP_CONCAT(`newsEval`.`evalClassify_seq`) AS `eval2_seqs`
  FROM `hnp_news` AS `hnews`
    LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id`
    LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id`
    LEFT JOIN `evalClassify` ON `evalClassify`.`seq` = `newsEval`.`evalClassify_seq`
    LEFT JOIN `evaluation` AS `group_item` ON `group_item`.seq = `evalClassify`.`evaluation_seq`
  WHERE 1=1
    AND `hnews`.`isUse` = 0 AND `hnews`.`articleSequence` in ('0', '-1')
    AND `hnews`.`news_date` BETWEEN '$startDate' AND '$endDate'
    AND (`hnews`.`news_comment` = 1 OR `evalClassify`.`evaluation_seq` >= 1000)
    AND `hcate`.`media_name` NOT IN ('', '이미지', '글상자')
    AND `group_item`.`isUse` = 'Y'
  GROUP BY `hnews`.`news_id` ORDER BY `hnews`.`news_id`
) B ON A.news_id = B.news_id ";
$result = mysqli_query($db_conn, $query);
$rsArray = array();
$eval2Seqs; $eval2SeqsCnt;

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

    $eval2SeqsCnt = 0;
    $eval2Seqs = explode(',', $row['eval2_seqs']);
    foreach($eval2Seqs as $sk => $sv) {
        if (equals($config_eval['item']['AT_M2'][$sv]['isUse'], 'Y') && equals($config_eval['item']['AT_M2'][$sv]['group_isUse'], 'Y')) {
            $eval2SeqsCnt++;
        }
    }

    $news_day = substr($row['news_date'], -2);
    $key = 'day' . (int)$news_day;
    logs('popCalendar.key: '.$key);

    $cntExclude = 0; //기본값 평가제외
    $cntDoneBoth = 0; //기본값 완료 개수
    $cntDone1 = 0; //기본값 평가1완료
    $cntDone2 = 0; //기본값 평가2완료
    $cntOngoingEither = 0; //기본값 평가1미완 || 평가2미완
    $cntOngoing1 = 0; //기본값 평가1미완
    $cntOngoing2 = 0; //기본값 평가2미완 < MAX

    if ($row['news_comment'] === '1') {
        $cntExclude = 1;
    } else {
        if ($row['category_seq']) {
            $cntDone1 = 1;
        } else {
            $cntOngoing1 = 1;
        }
        if ($eval2SeqsCnt == $eval2SeqsCntMax) {
            $cntDone2 = 1;
        } else {
            $cntOngoing2 = 1;
        }

        if ($eval1Use && $eval2Use) { // 둘 다 켜져 있으면
            $cntDoneBoth = min($cntDone1, $cntDone2);
            $cntOngoingEither = max($cntOngoing1, $cntOngoing2);
        } else if ($eval1Use && !$eval2Use) { // 평가1
            $cntDoneBoth = $cntDone1;
            $cntOngoingEither = $cntOngoing1;
        } else if (!$eval1Use && $eval2Use) { // 평가2
            $cntDoneBoth = $cntDone2;
            $cntOngoingEither = $cntOngoing2;
        }
    }
    if (!$rsArray[$key]) {
        //평가 배열에 없는경우 생성 후 기본값 추가
        $rsArray[$key] = array(
            'cnt' => 1,
            'cntExclude' => $cntExclude,
            'cntDoneBoth' => $cntDoneBoth,
            'cntDone1' => $cntDone1,
            'cntDone2' => $cntDone2,
            'cntOngoingEither' => $cntOngoingEither,
            'cntOngoing1' => $cntOngoing1,
            'cntOngoing2' => $cntOngoing2
            , 'eval2_seqs' => $eval2SeqsCnt // DEBUG
            , 'eval2SeqsCntMax' => $eval2SeqsCntMax // DEBUG
        );
    } else {
        //평가 배열에 있는경우 조건에 따라 기본값에 1씩 증가
        $rsArray[$key]['cnt']++;
        $rsArray[$key]['cntExclude'] += $cntExclude;
        $rsArray[$key]['cntDoneBoth'] += $cntDoneBoth;
        $rsArray[$key]['cntDone1'] += $cntDone1;
        $rsArray[$key]['cntDone2'] += $cntDone2;
        $rsArray[$key]['cntOngoingEither'] += $cntOngoingEither;
        $rsArray[$key]['cntOngoing1'] += $cntOngoing1;
        $rsArray[$key]['cntOngoing2'] += $cntOngoing2;
    }
}

/**
 * 비어있는 날짜 채우기용
 */
for ($i = 1; $i <= $days; $i++) {
    if (!$rsArray['day' . $i]) {
        $rsArray['day' . $i] = array(
            'cnt' => 0,
            'cntExclude' => 0,
            'cntDoneBoth' => 0,
            'cntDone1' => 0,
            'cntDone2' => 0,
            'cntOngoingEither' => 0,
            'cntOngoing1' => 0,
            'cntOngoing2' => 0
        );
    }
}

$result = array();
$result['cntInfo'] = $rsArray;
$result['cfgUse1'] = $eval1Use;
$result['cfgUse2'] = $eval2Use;

echo json_encode($result);
