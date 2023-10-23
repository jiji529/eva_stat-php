<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-12-17
 * Time: 오후 1:34
 */
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';
$q = $_REQUEST['q'] !== null ? json_decode($_REQUEST['q'], true) : '';// json
$evaluation_seq = 3;
$rsArray = null;
$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

if ($q) {
    $totalCount = count($q);
    if ($totalCount > 0) {
        foreach ($q as $ei => $eval) {
            if ($eval['seq']) {
                $lastOrder = $eval['seq'];
                if ($totalCount > 1) {
                    $eval['order'] = $ei + 1;
                }
                selectToUpdate($eval);

                if (is_array($eval['media']) && count($eval['media']) > 0) {

                    foreach ($eval['media'] as $mi => $media) {

                        if ($lastOrder !== $media['seq'] && $media['media_id'] > 0) {
                            mediaGroupInsert($lastOrder, $media['media_id']);
                        }
                    }
                }
            } else {
                $lastOrder = lastInsertOrder();
                $eval['order'] = $lastOrder + 1;
                $evalSeq = evalInsert($eval);
                if ($evalSeq > 0 && is_array($eval['media']) && count($eval['media']) > 0) {
                    foreach ($eval['media'] as $mi => $media) {
                        if ($media['media_id'] > 0) {
                            mediaGroupInsert($evalSeq, $media['media_id']);
                        }

                    }
                }
            }
        }
    }
}

function selectToUpdate($value)
{
    global $db;

    $select_seq = (int)$value['seq'];
    $select_query = "SELECT `seq` FROM `evalClassify` ";
    $select_query .= "WHERE `seq` =  {$select_seq}";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $update_query = "UPDATE `evalClassify` SET `value` =  '{$value['value']}' ";
        if ($value['score'])
            $update_query .= ", `score` = '{$value['score']}' ";
        if ($value['refValue'] !== null)
            $update_query .= ", `refValue` = '{$value['refValue']}' ";
        if ($value['order'] !== null)
            $update_query .= ", `order` ='{$value['order']}' ";
        if ($value['isUse'])
            $update_query .= ", `isUse` ='{$value['isUse']}' ";
        $update_query .= "WHERE `seq` =  {$select_seq} ";
        $db->Query($update_query);
        if ($db->Error()) {
            $result["success"] = false;
            $result["db"] = $db;
            $result['notice_code'] = $db->ErrorNumber();
            $result['notice_message'] = $db->Error();
            $db->Close();
            echo json_encode($result);
            exit;
        }

        mediaGroupDelete($value);

    }
}


function evalInsert($value)
{
    global $db, $evaluation_seq;
    $upperSeq = 0;
    if ($value['value']) {
        $insert['value'] = ClassStat::SQLValue($value['value']);
        $insert['evaluation_seq'] = ClassStat::SQLValue($evaluation_seq);
        if ($value['order'] !== null)
            $insert['order'] = ClassStat::SQLValue($value['order']);
        if ($value['score'])
            $insert['score'] = ClassStat::SQLValue($value['score']);
        if ($value['isUse'])
            $insert['isUse'] = ClassStat::SQLValue($value['isUse']);
        if ($value['refValue'] !== null)
            $insert['refValue'] = ClassStat::SQLValue($value['refValue']);
        $db->InsertRow('evalClassify', $insert);
        $upperSeq = $db->GetLastInsertID();
    }
    return $upperSeq;
}

function mediaGroupDelete($value)
{
    global $db;
    if ((int)$value['seq'] > 0) {
        $query = "DELETE FROM `mediaGroup` WHERE `evalClassify_seq`={$value['seq']} ";
        if (is_array($value['media']) && count($value['media']) > 0) {
            $media_array = array();
            foreach ($value['media'] as $media) {
                if ($media['media_id']) {
                    $media_array[] = $media['media_id'];
                }
            }
            $media_array = implode(",", $media_array);
            $query .= "AND `hnp_category_media_id` NOT IN ({$media_array})";
        }
        $db->Query($query);
        if ($db->Error()) {
            $result["success"] = false;
            $result["db"] = $db;
            $result['notice_code'] = $db->ErrorNumber();
            $result['notice_message'] = $db->Error();
            $db->Close();
            echo json_encode($result);
            exit;
        }

    }
}

function mediaGroupInsert($seq, $media_id)
{
    global $db;
    if ($seq && $media_id) {
        $insert['evalClassify_seq'] = ClassStat::SQLValue($seq);
        $insert['hnp_category_media_id'] = ClassStat::SQLValue($media_id);
        $db->SelectRows('mediaGroup', $insert, 'seq');
        if (!$db->RowCount()) {
            $db->InsertRow('mediaGroup', $insert);
        }

        if ($db->Error()) {
            $result["success"] = false;
            $result["db"] = $db;
            $result['notice_code'] = $db->ErrorNumber();
            $result['notice_message'] = $db->Error();
            $db->Close();
            echo json_encode($result);
            exit;
        }

    }
}

function lastInsertOrder()
{
    global $db, $evaluation_seq;
    $order = 0;
    $select_query = "SELECT `order` FROM `evalClassify` ";
    $select_query .= "WHERE  `evaluation_seq`={$evaluation_seq}  ORDER BY `order` DESC ";
    $db->Query($select_query);
    if ($db->RowCount() > 0) {
        $result = $db->RowArray(0, MYSQLI_ASSOC);
        $order = (int)$result['order'];
    }
    return $order;
}


$sql = "SELECT `HC`.`media_id`,`HC`.`media_name`,`EC`.`seq`,`HC`.`paper_code`,`CF`.`alias` AS `media_type_name` FROM `hnp_category` AS `HC` ";
$sql .= "LEFT JOIN `mediaGroup` AS `MG` ON `HC`.`media_id` = `MG`.`hnp_category_media_id` ";
$sql .= "LEFT JOIN `evalClassify` AS `EC` ON `MG`.`evalClassify_seq` = `EC`.`seq` ";
$sql .= "LEFT JOIN `hnp_config` AS `CF` ON `CF`.`value` = `HC`.`mediaType` AND `CF`.`fKey` = 'MEDIA_TYPE' ";
$sql .= "WHERE `HC`.`media_name` NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실') AND `HC`.`mediaType` != -98 AND `HC`.`isUse` = '0'";

$db->Query($sql);
if ($db->Error()) {
    $result["success"] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    $db->Close();
    echo json_encode($result);
    exit;
}

if ($db->RowCount() > 0) {
    //$classArray
    $unSelected = array();
    $selected = array();
    $onlyMediaName = array();
    $notUniqueMediaName = array();
    while ($rowMedia = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
        if (!$rowMedia['seq']) {
            $unSelected[] = $rowMedia;
        } else {
            $selected[$rowMedia['seq']][] = $rowMedia;
            if(in_array($rowMedia['media_name'], $onlyMediaName)) {
							$notUniqueMediaName[] = $rowMedia['media_name'];
						} else {
							$onlyMediaName[] = $rowMedia['media_name'];
						}
        }
    }

    foreach($selected as $group => $groupVal) { //매체중요도 seq 별로 묶여있음
    	foreach($groupVal as $key => $val) { //미디어 하나
				if(in_array($val['media_name'], $notUniqueMediaName)) {
					// if($val['paper_code'] !== '') {
					// 	$selected[$group][$key]['media_name'] = $selected[$group][$key]['media_name']."(지면)";
					// } else {
					// 	$selected[$group][$key]['media_name'] = $selected[$group][$key]['media_name']."(온라인)";
					// }
          // $selected[$group][$key]['media_name'] = $selected[$group][$key]['media_name']."("..")";
				}
			}
		}

    $sql = "SELECT  `e`.`seq`,  `e`.`value`,  `e`.`refValue`,  `e`.`score`,  `e`.`isUse` FROM `evalClassify` AS `e`  ";
    $sql .= "WHERE `e`.`evaluation_seq`={$evaluation_seq} ";
    $db->Query($sql);
    if ($db->Error()) {
        $result["success"] = false;
        $result['notice_code'] = $db->ErrorNumber();
        $result['notice_message'] = $db->Error();
        $db->Close();
        echo json_encode($result);
        exit;
    }
    if ($db->RowCount() > 0) {
        $selectMedia = array();
        while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
            $row['media'] = array();
            if ($selected[$row['seq']]) {
                $row['media'] = $selected[$row['seq']];
            }
            $selectMedia[] = $row;
        }
        $rsArray['selected'] = $selectMedia;
        $rsArray['unselected'] = $unSelected;
    }

}


$db->Close();
if ($rsArray === null) {
    $rsArray = array(
        "success" => false,
        "notice_code" => "N000",
        "notice_message" => "표시할 데이터가 없습니다.",
    );
}


echo json_encode($rsArray);
exit;
