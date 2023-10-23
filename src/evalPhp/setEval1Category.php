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
//190306 조부광 추가
$insertRow = 0;
$updateRow = 0;
if ($totalCount > 0) {
    foreach ($q as $lk => $large) {
        if ($large['seq']) {
            if ($totalCount > 1) {
                $large['order'] = $lk + 1;
            }
            selectToUpdate($large);$updateRow++;
            if (is_array($large['sub']) && count($large['sub']) > 0) {
                foreach ($large['sub'] as $mk => $middle) {
                    if ($middle['seq']) {
                        $middle['order'] = $mk + 1;
                        selectToUpdate($middle);$updateRow++;
                        if (is_array($middle['sub']) && count($middle['sub']) > 0) {
                            foreach ($middle['sub'] as $sk => $small) {
                                $small['order'] = $sk + 1;
                                if ($small['seq']) {
                                    selectToUpdate($small);$updateRow++;
                                }
                                //190306 조부광 추가
                                else {
                                		$smallLastOrder =  midAndSmallLastInsertOrder($middle['seq']);
                                		$small['order'] = $smallLastOrder + 1;
                                		$small['upperSeq'] = $middle['seq'];
																		categoryInsert($small);$insertRow++;
																}
                            }
                        }
                    }
										//190306 조부광 추가
                    else {
												$middleLastOrder = midAndSmallLastInsertOrder($large['seq']);
												$middle['order'] = $middleLastOrder + 1;
												$middle['upperSeq'] = $large['seq'];
												$middleUpperSeq = categoryInsert($middle);$insertRow++;
												if ($middleUpperSeq > 0 && is_array($middle['sub']) && count($middle['sub']) > 0) {
													foreach ($middle['sub'] as $sk => $small) {
														$small['order'] = $sk + 1;
														$small['upperSeq'] = $middleUpperSeq;
														categoryInsert($small);$insertRow++;
													}
												}
										}
                }
            }
        } else {
            $lastOrder = lastInsertOrder();

            $large['order'] = $lastOrder + 1;
            $largeUpperSeq = categoryInsert($large);$insertRow++;
            if ($largeUpperSeq > 0 && is_array($large['sub']) && count($large['sub']) > 0) {
                foreach ($large['sub'] as $mk => $middle) {
                    $middle['order'] = $mk + 1;
                    $middle['upperSeq'] = $largeUpperSeq;
                    $middleUpperSeq = categoryInsert($middle);$insertRow++;

                    if ($middleUpperSeq > 0 && is_array($middle['sub']) && count($middle['sub']) > 0) {
                        foreach ($middle['sub'] as $sk => $small) {
                            $small['order'] = $sk + 1;
                            $small['upperSeq'] = $middleUpperSeq;
                            categoryInsert($small);$insertRow++;
                        }
                    }
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
//190306 조부광 추가
$result['insertRowNum'] = (int)$insertRow;
$result['updateRowNum'] = (int)$updateRow;
echo json_encode($result);
exit;


function selectToUpdate($value)
{
    global $db;
    $select_seq = (int)$value['seq'];
    $select_query = "SELECT `seq` FROM `category` ";
    $select_query .= "WHERE `seq` =  {$select_seq}";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $update_query = "UPDATE `category` SET `name` =  '{$value['name']}' ";
        if ($value['score'])
            $update_query .= ", `score` = '{$value['score']}' ";
        if ($value['upperSeq'])
            $update_query .= ", `upperSeq` = '{$value['upperSeq']}' ";
        if ($value['order'])
            $update_query .= ", `order` ='{$value['order']}' ";
        if ($value['isUse'])
            $update_query .= ", `isUse` ='{$value['isUse']}' ";
        $update_query .= "WHERE `seq` =  {$select_seq} ";
        $db->Query($update_query);
    }
}

function categoryInsert($value)
{
    global $db;
    $upperSeq = 0;
    if ($value['name']) {
        $insert['name'] = ClassStat::SQLValue($value['name']);
        if ($value['order'])
            $insert['order'] = ClassStat::SQLValue($value['order']);
        if ($value['score'])
            $insert['score'] = ClassStat::SQLValue($value['score']);
        if ($value['upperSeq'])
            $insert['upperSeq'] = ClassStat::SQLValue($value['upperSeq']);
        $db->InsertRow('category', $insert);
        $upperSeq = $db->GetLastInsertID();

    }
    return $upperSeq;
}

function lastInsertOrder()
{
    global $db;
    $order = 0;
    $select_query = "SELECT `order` FROM `category` ";
    $select_query .= "WHERE `upperSeq` IS NULL ORDER BY `order` DESC ";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $result = $db->RowArray(0, MYSQLI_ASSOC);
        $order = (int)$result['order'];
    }
    return $order;
}

//190306 조부광 추가
function midAndSmallLastInsertOrder($upperSeq)
{
	global $db;
	$order = 0;
	$select_query = "SELECT `order` FROM `category`";
	$select_query .= "WHERE `upperSeq` = {$upperSeq} ORDER BY `order` DESC ";
	$db->Query($select_query);
	if($db->RowCount() > 0) {
		$result = $db->RowArray(0, MYSQLI_ASSOC);
		$order = (int)$result['order'];
	}
	return $order;
}