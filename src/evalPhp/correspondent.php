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
$evaluation_seq = 4;
$rsArray = array('classList'=>array(),'reporterList'=>array());
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
    $classList = $q['classList'];
    if ($classList) {
        $totalCount = count($classList);
        if ($totalCount > 0) {
            foreach ($classList as $ei => $eval) {
                if ($eval['seq']) {
                    evalClassifyUpdate($eval);
                }
            }
        }
    }
    $reporterList = $q['reporterList'];
    if ($reporterList) {
        $totalCount = count($reporterList);
        if ($totalCount > 0) {
            foreach ($reporterList as $media) {
                if ($media['reporter']) {
                    foreach ($media['reporter'] as $reporter) {
                        $reporter['media_id'] = $media['media_id'];
                        $reporter['media_name'] = $media['media_name'];
                        mediaGroupUpdate($reporter);
                    }
                }
            }
        }
    }


}


function evalClassifyUpdate($value)
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
    }
}

function mediaGroupUpdate($reporter)
{
    global $db;
    if ($reporter) {
        $filter = array();
        $filter['seq'] = ClassStat::SQLValue($reporter['seq']);
        $db->SelectRows('reporterGroup', $filter, array('reporterName', 'isUse'));
        if ($db->RowCount() > 0) {
            $selector = $db->RowArray(0, MYSQLI_ASSOC);
            $update = array();
            // if ($selector['reporterName'] !== $reporter['reporterName'])
                $update['reporterName'] = ClassStat::SQLValue($reporter['reporterName']);
            // if ($selector['isUse'] !== $reporter['isUse'])
                $update['isUse'] = ClassStat::SQLValue($reporter['isUse']);
                $update['hnp_category_media_id'] = $db->SQLValue($reporter['media_id']);
            if (count($update) > 0)
                $db->UpdateRows('reporterGroup', $update, $filter);
        } else {
            $insert['evalClassify_seq'] = ClassStat::SQLValue($reporter['evalClassify']);
            $insert['hnp_category_media_id'] = ClassStat::SQLValue($reporter['media_id']);
            $insert['reporterName'] = ClassStat::SQLValue($reporter['reporterName']);
            $insert['isUse'] = ClassStat::SQLValue($reporter['isUse']);
            $db->InsertRow('reporterGroup', $insert);
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


$sql = "SELECT  `seq`,  `value`,  `refValue`,  `score`,  `isUse` FROM `evalClassify`  ";
$sql .= "WHERE `evaluation_seq`={$evaluation_seq} ";
$sql .= "ORDER BY `order` ";
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
    $rsArray['classList'] = $db->RecordsArray(MYSQLI_ASSOC);

    $sql = "SELECT `HC`.`media_id`, `HC`.`media_name`, `RG`.`seq`, `RG`.`reporterName`, `RG`.`isUse`, `RG`.`evalClassify_seq`, `HG`.`alias` AS `media_type_name` ";
    $sql .= "FROM `reporterGroup` AS `RG` ";
    $sql .= "LEFT JOIN `hnp_category` AS `HC` ON `HC`.`media_id` = `RG`.`hnp_category_media_id` ";
    $sql .= "LEFT JOIN `hnp_config` AS `HG` ON `HG`.`value` = `HC`.`mediaType` AND `HG`.`fKey` = 'MEDIA_TYPE' ";
//$sql .= "LEFT JOIN `evalClassify` AS `EC` ON `RG`.`evalClassify_seq` = `EC`.`seq` ";
//$sql .= "WHERE `HC`.`media_name` !='' AND `HC`.`media_name`!='글상자' AND `HC`.`media_name`!='이미지' ";
//$sql .= "AND `RG`.`seq`!='' ";
    $sql .= "ORDER BY `HC`.`media_name`, `HC`.`mediaType`";
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
        $reporter = array();
        $lastMediaId = null;
        $rowCount = $db->RowCount();
        while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
            if ($lastMediaId != $row['media_id']) {
                if ($lastMediaId !== NULL) {
                    // add this obj to the post array
                    $reporter[] = $tPost;
                }
                // start a new temp object
                $tPost = array();
                $tPost['media_id'] = $row['media_id'];
                $tPost['media_name'] = $row['media_name'];
                $tPost['media_type_name'] = $row['media_type_name'];
                $tPost['reporter'] = array();
            }

            // Add photo_id to current tPost obj
            $tPost['reporter'][] = array('seq' => $row['seq'], 'reporterName' => $row['reporterName'], 'evalClassify' => $row['evalClassify_seq'], 'isUse' => $row['isUse']);
            $lastMediaId = $row['media_id'];
            $rowCount--;
            if (!$rowCount) {
                $reporter[] = $tPost;
            }
        }
        $rsArray['reporterList'] = $reporter;
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
