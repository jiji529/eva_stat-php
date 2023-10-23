<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-12-19
 * Time: 오후 2:53
 */

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';


$q = $_REQUEST['q'] !== null ? $_REQUEST['q'] : '';// json
$q = json_decode($q, true);

if (!is_array($q)) {
    $rsArray = array(
        "success" => false,
        "notice_code" => "N001",
        "notice_message" => "매개변수가 올바르지 않습니다.",
    );
    echo json_encode($rsArray);
    exit;
}

$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}
$totalCount = count($q);
if ($totalCount > 0) {
    foreach ($q as $lk => $evaluation) {
        if ($evaluation['upper_cate_seq']) {
            if ($totalCount > 1) {
                $evaluation['upper_cate_order'] = $lk + 1;
            }
            evaluationUpdate($evaluation);
            if (is_array($evaluation['sub']) && count($evaluation['sub']) > 0) {
                foreach ($evaluation['sub'] as $mk => $classify) {
                    if ($classify['seq']) {
                        $classify['order'] = $mk + 1;
                        classifyUpdate($classify);
                    } else {
                    	//기존항목에 추가된 경우 - insert
												$classify['order'] = $mk + 1;
												$classify['upperSeq'] = $evaluation['upper_cate_seq'];
												classifyInsert($classify);
 										}
                }
            }
        } else {
            $lastOrder = lastInsertOrder();

            $evaluation['upper_cate_order'] = $lastOrder + 1;
            $evaluationUpperSeq = evaluationInsert($evaluation);
            if ($evaluationUpperSeq > 0 && is_array($evaluation['sub']) && count($evaluation['sub']) > 0) {
                foreach ($evaluation['sub'] as $mk => $classify) {
                    $classify['order'] = $mk + 1;
                    $classify['upperSeq'] = $evaluationUpperSeq;
                     classifyInsert($classify);

                }
            }
        }
    }
}
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

$db->Close();
$result['totalCount'] = (int)$totalCount;
$result["success"] = true;
echo json_encode($result);
exit;


function classifyUpdate($value)
{
    global $db;
    $select_seq = (int)$value['seq'];
    $select_query = "SELECT `seq` FROM `evalClassify` ";
    $select_query .= "WHERE `seq` =  {$select_seq}";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $update_query = "UPDATE `evalClassify` SET `value` =  '{$value['name']}' ";
        if ($value['refValue'])
            $update_query .= ", `refValue` = '{$value['value']}' ";
        if ($value['score'])
            $update_query .= ", `score` ='{$value['score']}' ";
        if ($value['use'])
            $update_query .= ", `isUse` ='{$value['use']}' ";
        if ($value['order'])
            $update_query .= ", `order` ='{$value['order']}' ";
        $update_query .= "WHERE `seq` =  {$select_seq} ";
        $db->Query($update_query);
    }
}

function evaluationUpdate($value)
{
    global $db;
    $select_seq = (int)$value['upper_cate_seq'];
    $select_query = "SELECT `seq` FROM `evaluation` ";
    $select_query .= "WHERE `seq` =  {$select_seq}";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $update_query = "UPDATE `evaluation` SET `name` =  '{$value['upper_cate_name']}' ";
        if ($value['upper_cate_order'])
            $update_query .= ", `order` ='{$value['upper_cate_order']}' ";
        if ($value['upper_cate_use'])
            $update_query .= ", `isUse` ='{$value['upper_cate_use']}' ";
        $update_query .= "WHERE `seq` =  {$select_seq} ";
        $db->Query($update_query);
    }
}

function evaluationInsert($value)
{
    global $db;
    $upperSeq = 0;
    if ($value['upper_cate_name']) {
        $insert['name'] = ClassStat::SQLValue($value['upper_cate_name']);
        if ($value['upper_cate_order'])
            $insert['order'] = ClassStat::SQLValue($value['upper_cate_order']);
        if ($value['upper_cate_use'])
            $insert['score'] = ClassStat::SQLValue($value['upper_cate_use']);

        $db->InsertRow('evaluation', $insert);
        $upperSeq = $db->GetLastInsertID();

    }
    return $upperSeq;
}

function classifyInsert($value)
{
    global $db;
    if ($value['name']) {
        $insert['value'] = ClassStat::SQLValue($value['name']);
        if ($value['value'])
            $insert['refValue'] = ClassStat::SQLValue($value['value']);
        if ($value['score'])
            $insert['score'] = ClassStat::SQLValue($value['score']);
        if ($value['use'])
            $insert['isUse'] = ClassStat::SQLValue($value['use']);
        if ($value['order'])
            $insert['order'] = ClassStat::SQLValue($value['order']);
        if ($value['upperSeq'])
            $insert['evaluation_seq'] = ClassStat::SQLValue($value['upperSeq']);
        $db->InsertRow('evalClassify', $insert);
        $db->GetLastInsertID();

    }
    return $upperSeq;
}

function lastInsertOrder()
{
    global $db;
    $order = 0;
    $select_query = "SELECT `order` FROM `evaluation` ";
    $select_query .= "ORDER BY `order` DESC ";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $result = $db->RowArray(0, MYSQLI_ASSOC);
        $order = (int)$result['order'];
    }
    return $order;
}