<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

//인자받기
$eval1 = $_REQUEST['eval1'];
$eval1Change = $_REQUEST['eval1Change'];
$eval2 = $_REQUEST['eval2'];
$eval2Change = $_REQUEST['eval2Change'];
$news_id = $_REQUEST['news_id'];

// eval1은 category_seq 업데이트
// eval2는 기존 것 삭제 후 새로 insert

if(!$news_id && !is_numeric($news_id)) {
    $error = array (
        "error_code" => "N000",
        "err_message" => "news_id 값이 반드시 필요합니다.",
        "success" => false
    );
    echo json_encode($error);
    exit;
}

$rs = array();
$news_id = trim($news_id);

$affected_row_count_by_update = 0;
if($eval1Change === 'true'){
    if($eval1) {
        $sql = "UPDATE hnp_news SET category_seq = {$eval1} WHERE news_id = {$news_id} ";
    } else {
        $sql = "UPDATE hnp_news SET category_seq = null WHERE news_id = {$news_id}  ";
    }
    $result = mysqli_query($db_conn, $sql) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

    $affected_row_count_by_update = mysqli_affected_rows($db_conn);
    $rs["affected rows (update) - eval1"] = $affected_row_count_by_update;
}

/** eval2저장 **/
//eval2 하나 or 다중
if ($eval2Change === 'true') {
    if (!is_array($eval2)) {
        $eval2_arr = explode(",", $eval2);
    } else {
        $eval2_arr = $eval2;
    }
    $eval2_arr = array_filter($eval2_arr);

    $query_select_reference = "SELECT A.*, group_concat(B.seq) AS `sibling` FROM (SELECT * FROM evalClassify WHERE evaluation_seq IN (SELECT seq FROM evaluation WHERE evaluation.automatic = 'Y')) A LEFT JOIN evalClassify AS `B` ON `B`.evaluation_seq = `A`.evaluation_seq GROUP BY A.seq";
    $result_select_reference = mysqli_query($db_conn, $query_select_reference) or die(mysqli_error($db_conn) . ' E001-2');
    $ev2_auto_reference = array();
    while($row = mysqli_fetch_assoc($result_select_reference)) {
        $ev2_auto_reference[$row['seq']] = $row;
    }

    $affected_row_count_by_update = 0;
    $affected_row_count_by_insert = 0;
    if (is_array($eval2_arr) && count($eval2_arr) > 0) {
        $auto_seqs = array();
        $ev2_seqs = array();
        $auto_ev_seqs = array();
        foreach ($eval2_arr as $ev2) {
            if ($ev2_auto_reference[$ev2]) {
                $auto_ev_seqs = array_merge($auto_ev_seqs, explode(',', $ev2_auto_reference[$ev2]['sibling']));
            }
            if (in_array($ev2, $auto_ev_seqs)) { // UPDATE
                array_push($auto_seqs, $ev2);
            } else { // DELETE & INSERT
                array_push($ev2_seqs, $ev2);
            }
        }

        $affected_row_count_by_delete = 0;
        //해당 news_id의 eval2 값 삭제
        $delete = "DELETE FROM `newsEval` WHERE `hnp_news_seq` IN ({$news_id})";
        // $delete = "DELETE FROM `newsEval` WHERE `hnp_news_seq` IN ({$news_id}) AND `evalClassify_seq` NOT IN (" . implode(',', $auto_ev_seqs) . ")";
        // $delete = "DELETE FROM newsEval WHERE hnp_news_seq IN ({$news_id}) AND evalClassify_seq IN (SELECT seq FROM evalClassify WHERE evaluation_seq IN (SELECT evaluation_seq FROM evalClassify LEFT OUTER JOIN evaluation ON evaluation.seq = evalClassify.evaluation_seq WHERE evalClassify.seq IN (" . implode(',', $eval2_arr) . ")))";
        $result = mysqli_query($db_conn, $delete) or die(mysqli_error($db_conn) . ' E001-2');
        $affected_row_count_by_delete = mysqli_affected_rows($db_conn);

//         $update_query = '';
//         foreach ($auto_seqs as $at) {
//             if ($ev2_auto_reference[$at]) {
//                 $affected_row_count_by_update++;
//                 $update = "UPDATE `newsEval` SET `evalClassify_seq` = '" . $at . "' WHERE `hnp_news_seq` = '" . $news_id . "' AND `evalClassify_seq` IN (" . $ev2_auto_reference[$at]['sibling'] . ")";
//                 $result = mysqli_query($db_conn, $update) or die(mysqli_error($db_conn) . ' E001-2');
//                 $update_query .= ' ; ' . $update;
//             }
//         }
        if (is_array($auto_seqs) && count($auto_seqs) > 0) {
            $insert = "INSERT INTO newsEval (evalClassify_seq, hnp_news_seq) VALUES ";
            foreach ($auto_seqs as $eval2_one) {
                $insert .= "( {$eval2_one}, {$news_id} ),";
                $affected_row_count_by_update++;
            }
            $insert = substr($insert, 0, -1);
            $result = mysqli_query($db_conn, $insert) or die(mysqli_error($db_conn) . ' E001-3' . $insert . ' val:' . json_encode($auto_seqs) . ' cnt:' . count($auto_seqs));
            $affected_row_count_by_insert = mysqli_affected_rows($db_conn);
        }
        
        if (is_array($ev2_seqs) && count($ev2_seqs) > 0) {
            $insert = "INSERT INTO newsEval (evalClassify_seq, hnp_news_seq) VALUES ";
            foreach ($ev2_seqs as $eval2_one) {
                $insert .= "( {$eval2_one}, {$news_id} ),";
            }
            $insert = substr($insert, 0, -1);
            $result = mysqli_query($db_conn, $insert) or die(mysqli_error($db_conn) . ' E001-3' . $insert . ' val:' . json_encode($ev2_seqs) . ' cnt:' . count($ev2_seqs));
            $affected_row_count_by_insert = mysqli_affected_rows($db_conn);
        }
    }
    $rs['affected rows (update) - auto'] = $affected_row_count_by_update;
    $rs['affected rows (delete) - eval2'] = $affected_row_count_by_delete;
    $rs['affected rows (insert) - eval2'] = $affected_row_count_by_insert;
}

echo json_encode($rs);

mysqli_close($db_conn);
?>
