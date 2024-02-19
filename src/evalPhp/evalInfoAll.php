<?php

include __DIR__ . '/common.php';
include __DIR__ . '/dbConn.php';

$news_id = $_REQUEST['news_id'] ;

if (!$news_id) {
  $error=array(
    'success' => false,
    'message' => '인자 값이 부족합니다.'
  );
  echo json_encode($error); exit;
}

if (!is_array($news_id)) {
    $news_id_arr = explode(",", $news_id);
} else {
    $news_id_arr = $news_id;
}
$news_id_str = implode(',', $news_id_arr);

$evalInfo = array();

$query_select_m2evcnt = "SELECT COUNT(*) AS `m2evcnt` FROM `evaluation` WHERE `seq` > 1000 AND `isUse` = 'Y'";
$result_select_m2evcnt = mysqli_query($db_conn, $query_select_m2evcnt);
if ($result_select_m2evcnt) {
    $responseset = mysqli_fetch_assoc($result_select_m2evcnt);
    $result_m2evcnt = (int)$responseset['m2evcnt'];
} else {
    $result_m2evcnt = 0;
}

$sql = "SELECT hnews.news_id,
      hnews.category_seq AS `eval1_seq` ,
      eval1.upperSeq AS `eval1_upper`,
			eval1.NAME AS `eval1_name` ,
			eval1.isUse AS `eval1_use`,
			newsEval.evalClassify_seq AS `eval2_seq`,
			eval2.VALUE AS `eval2_name`,
			eval2.refValue AS `eval2_ref_value`,
			eval2.evaluation_seq AS `eval2_upper_seq`,
			eval2_upper.NAME AS `eval2_upper_name`,
			eval2_upper.isUse AS `eval2_upper_use`,
			eval2.isUse AS `eval2_use`
        FROM hnp_news AS hnews
        LEFT JOIN category AS eval1 ON hnews.category_seq = eval1.seq
        LEFT JOIN newsEval ON hnews.news_id = newsEval.hnp_news_seq
        LEFT JOIN evalClassify AS eval2  ON newsEval.evalClassify_seq = eval2.seq
        LEFT JOIN evaluation AS eval2_upper ON eval2.evaluation_seq = eval2_upper.seq
        WHERE hnews.news_id IN ($news_id_str) ";

$data = mysqli_query($db_conn, $sql);
$eval1_array = array ( 'eval1_name' => null , 'eval1_seq' => null, 'eval1_upper' => null);
while ($row = mysqli_fetch_assoc($data)) {

    $id = $row['news_id'];
    $upper_seq = $row['eval2_upper_seq'];
    $eval1_seq = $row['eval1_seq'];
    $eval2_seq = $row['eval2_seq'];
    $upper_use = $row['eval2_upper_use'];
    $eval1_use = $row['eval1_use'];
    $eval2_use = $row['eval2_use'];

    //새로운 news_id오브젝트 생성
    if(!$evalInfo[$id]) {
        //eval1 카테고리 사용, eval1 평가값 존재
        if($eval1_use === 'Y'){
            $eval1 = array ( 'eval1_name' => $row['eval1_name'] , 'eval1_seq' => $row['eval1_seq'] , 'eval1_upper' => $row['eval1_upper']);
        } else {
            $eval1 = $eval1_array;
        }

        $evalInfo[$id] = array(
          'eval1' => $eval1,
          'eval2Cnt' => 0,
          'eval2atCnt' => 0,
          'eval2m2Cnt' => 0,
          'eval2m2CntMax' => $result_m2evcnt,
          'eval2Value' => array()
        );
    }

    if($upper_use === 'Y' && $eval2_use === 'Y'  && $eval2_seq !== null && $upper_seq !==null) {
        if ($row['eval2_upper_seq'] == "7") {
            $eval2 = array('eval2_name' => ( $row['eval2_ref_value']."-".$row['eval2_name']), 'eval2_seq' => $row['eval2_seq']);
        } else {
            $eval2 = array('eval2_name' => $row['eval2_name'], 'eval2_seq' => $row['eval2_seq']);
        }
        
        $evalInfo[$id]['eval2Cnt']++;
        if ($upper_seq > 1000) {
            $evalInfo[$id]['eval2m2Cnt']++;
        } else {
            $evalInfo[$id]['eval2atCnt']++;
        }
        $evalInfo[$id]['eval2Value'][$upper_seq] = $eval2;
    }
}

foreach($news_id_arr as $key => $value) {
    if($evalInfo[$value] === null) {
        $evalInfo[$value] = array (
            'eval1' => $eval1_array,
            'eval2Cnt' => 0,
            'eval2atCnt' => 0,
            'eval2m2Cnt' => 0,
            'eval2m2CntMax' => $result_m2evcnt,
            'eval2Value' => null,
        );
    }
}

echo json_encode($evalInfo);


//db연결 해제
mysqli_close($db_conn);

?>
