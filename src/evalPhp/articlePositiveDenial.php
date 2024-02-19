<?php
/**
 * User: changmin
 * Date: 2023-02-13
 * Time: 오전 10:41
 */
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';

function checkDbError($db) {
    if (!$db->Error()) return false;
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

function getEvalClassifyList($db, $evaluation_seq) {
    $sql = "
        SELECT  `seq`,  `value`,  `refValue`,  `score`,  `isUse` 
        FROM `evalClassify` 
        WHERE `evaluation_seq` = {$evaluation_seq} 
    ";
    $db->Query($sql);
    return ($db->RowCount() > 0) ? $db->RecordsArray(MYSQLI_ASSOC): null;
}

function selectToUpdate($db, $value) {
    if ($value['seq'] == null &&
        ($value['score'] == null && $value['refValue'] == null)
    ) return false;
    $seq = (int) $value['seq'];
    $update_query = "UPDATE `evalClassify` SET ";
    if ($value['score'] != null)
        $update_query .= " `score` = '{$value['score']}',";
    if ($value['refValue'] !== null)
        $update_query .= " `refValue` = '{$value['refValue']}',";
    $update_query = substr($update_query, 0, -1);
    $update_query .= " WHERE `seq` =  {$seq} ";
    $db->Query($update_query);
}

$evalConfigArr = $_REQUEST['evalConfigArr'] !== null 
                ? json_decode($_REQUEST['evalConfigArr'], true) : '';// json
$evaluation_seq = 6;

$db = new ClassStat($premiumID);
checkDbError($db);

/* 수정 전, DB 데이터 */
$resultList = getEvalClassifyList($db, $evaluation_seq);
if (is_array($resultList) && $evalConfigArr && count($evalConfigArr) > 0) {
    /* 검증 데이터 */
    $prevList = array();
    foreach ($resultList as $ec) {
        $prevList[$ec["seq"]] = $ec;
    }
    
    /* 수정 */
    foreach ($evalConfigArr as $idx => $evalConfig) {
        if ($prevList[$evalConfig["seq"]] != null) {
            $evalConfig['order'] = $idx + 1;
            selectToUpdate($db, $evalConfig);
        }
    }
    
    /* 수정 후, DB 데이터 */
    $resultList = getEvalClassifyList($db, $evaluation_seq);
}
echo json_encode($resultList);
exit;