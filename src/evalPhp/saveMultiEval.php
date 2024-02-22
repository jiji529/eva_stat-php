<?php


include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

//인자받기
$eval1 = $_REQUEST['eval1'];
$eval1Change = $_REQUEST['eval1Change'];
$eval2 = $_REQUEST['eval2']; 
$eval2Change = $_REQUEST['eval2Change'];
$news_id = $_REQUEST['news_id'];
$autoCateSubSeq = $_REQUEST['autoCateSubSeq'];

// eval1은 category_seq 업데이트
// eval2는 기존 것 삭제 후 새로 insert

if(!$news_id) {
    $error = array (
        "error_code" => "N000",
        "err_message" => "news_id 값이 반드시 필요합니다.",
    );
    echo json_encode($error);
    exit;
}

$rs = array();
$news_id = trim($news_id);
//news_id 하나 or 다중
//eval1 저장
if(!is_array($news_id)) {
    $news_id_arr = explode(",", $news_id);
} else {
    $news_id_arr = $news_id;
}

$news_id_string = implode(",", $news_id_arr);

$affected_row_count_by_update = 0;
if($eval1Change == "true"){
    if($eval1) {
        $sql = "UPDATE hnp_news SET category_seq = {$eval1} WHERE news_id IN({$news_id_string})";
    } else {
        $sql = "UPDATE hnp_news SET category_seq = null WHERE news_id IN({$news_id_string})";
    }
    $result = mysqli_query($db_conn, $sql);
    if(! $result ) {
        $error = array(
            "error_code" => "E001-1",
            "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
        );
        echo urldecode(json_encode($error));
        exit;
    }
    
    $affected_row_count_by_update += mysqli_affected_rows($db_conn);
    $rs["affected rows (update) - eval1"] = $affected_row_count_by_update;
}


/** eval2저장 **/
//eval2 하나 or 다중
if($eval2Change) {
    if (!is_array($eval2)) {
        $eval2_arr = explode(",", $eval2);
    } else {
        $eval2_arr = $eval2;
    }

    $affected_row_count_by_delete = 0;
//해당 news_id의 eval2 값 삭제
    $delete = "DELETE FROM newsEval WHERE hnp_news_seq IN ({$news_id_string}) AND evalClassify_seq not in ({$autoCateSubSeq}) ";

    $result = mysqli_query($db_conn, $delete);
    if (!$result) {
        $error = array(
            "error_code" => "E001-2",
            "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
        );
        echo urldecode(json_encode($error));
        exit;
    }
    $affected_row_count_by_delete += mysqli_affected_rows($db_conn);

    $affected_row_count_by_insert = 0;
    if ($eval2 != "") {
        $insert = "INSERT INTO newsEval (evalClassify_seq, hnp_news_seq) VALUES ";
        foreach ($news_id_arr as $id_key => $id_one) {
            foreach ($eval2_arr as $eval_key => $eval2_one) {
                $insert .= "( {$eval2_one}, {$id_one} ),";
            }
        }
        $insert = substr($insert, 0,-1);
        $result = mysqli_query($db_conn, $insert);
        if (!$result) {
            $error = array(
                "error_code" => "E001-3",
                "error_message" => urlencode("Incorrect main-query request (잘못된 쿼리요청입니다.)"),
            );
            echo urldecode(json_encode($error));
            exit;
        }
        $affected_row_count_by_insert += mysqli_affected_rows($db_conn);
    }
    $rs['affected rows (delete) - eval2'] = $affected_row_count_by_delete;
    $rs['affected rows (insert) - eval2'] = $affected_row_count_by_insert;
}

echo json_encode($rs);


mysqli_close($db_conn);
?>
