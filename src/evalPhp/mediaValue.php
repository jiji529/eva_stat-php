<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
$c = $_REQUEST['c'];
$result = array();
$success = true;
$final_group = array();
$log;

if ($q) { // $q에 담아서 보낸 데이터가 있으므로 '저장'의 결과 -- UPDATE 수행

    // 변경값 저장이 시도되면 무조건 모든 유효 레코드의 값 0으로 교체
    $query_update_all = "UPDATE `hnp_category` SET `evalInitCheck` = 0 WHERE `media_name` NOT IN ('', '글상자', '이미지') AND `evalInitCheck` = 1";
    mysqli_query($db_conn, $query_update_all) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));

    $query_select_to_update = "SELECT `media_id`, `media_name`, truncate(`evalValue` / 1000, 3) AS `evalValue`, `isUse` FROM `hnp_category` WHERE `media_name` NOT IN ('', '글상자', '이미지') ORDER BY if(`kind_serial` = '' OR `kind_serial` IS NULL,1,0),`kind_serial`, if(`paper_category` = '' OR `paper_category` IS NULL,1,0), `paper_category`, `media_name`";
    $result_select_to_update = mysqli_query($db_conn, $query_select_to_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    while ($row = mysqli_fetch_assoc($result_select_to_update)) {
        $final_stu[] = array(
            'mid' => $row['media_id'],
            'mn' => $row['media_name'],
            'evl' => floatval($row['evalValue']),
            'isUse' => $row['isUse']
        );
    }
    $toUpdateCount = 0;
    $loopCount = 0;
    $result['storedData'] = count($final_stu);
    $result['requestedData'] = count($q);
    foreach ($final_stu as $storedDatum) {
        foreach ($q as $requestedDatumKey => $requestedDatum) {
            $loopCount++;
            if (strcmp($storedDatum['mid'], $requestedDatum['media_id']) === 0) {
                if ($storedDatum['evl'] != $requestedDatum['evalValue']
                    || $storedDatum['evl'] !== $requestedDatum['evalValue']
                    || $storedDatum['isUse'] != $requestedDatum['isUse'] ) {
                    $toUpdateCount++;
                    $data_update[] = array(
                        'mid' => $storedDatum['mid'],
                        'use' => $requestedDatum['isUse'],
                        'evl' => $requestedDatum['evalValue']
                    );
                }
                unset($q[$requestedDatumKey]);
                break;
            }
        }
    }
    $query_update = '';
    $successCount = 0;
    $failCount = 0;
    foreach ($data_update as $datum_update) {
        $mediaId = $datum_update['mid'];
        $evalValue = $datum_update['evl'];
        $isUse = $datum_update['use'];
        if (empty($evalValue)) $evalValue = '0';
        $pattern_seq = '/^[0-9]+$/';
        $pattern_value = '/^[0-9][0-9.]*$/';
        if (!empty($mediaId) && preg_match($pattern_seq, $mediaId, $matches) == 1
                && preg_match($pattern_value, $evalValue, $matches) == 1
              ) {
            $successCount++;
            $evalValue = floatval($evalValue) * 1000;
            $query_update = "UPDATE `hnp_category` SET `evalValue` = $evalValue, `isUse` = $isUse, `evalInitCheck` = 0 WHERE `media_id` = $mediaId";
            mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        } else {
            $failCount++;
        }
    }
    $result['toUpdateCount'] = $toUpdateCount;
    $result['updateSuccessCount'] = $successCount;
    $result['updateFailCount'] = $failCount;
    $result['loopCount'] = $loopCount;
    $result['data_update'] = $data_update;
}

$result['c'] = $c;
if ($c) {
    $query_select_count = "SELECT COUNT(*) AS `uncheckedMediaCount` FROM `hnp_category` WHERE `media_name` NOT IN ('', '글상자', '이미지') AND `media_name` != `category_name` AND `evalInitCheck` = 1";
    $response_select_count = mysqli_query($db_conn, $query_select_count) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    $responseset = mysqli_fetch_assoc($response_select_count);
    if (count($responseset) === 1) {
        $final = $responseset['uncheckedMediaCount'];
    } else {
        $success = false;
    }
    $result['success'] = $success;
    $result['final'] = $final;
} else {
    $sql = "SELECT `HC`.`media_id`, `HC`.`media_name`, `HC`.`paper_category`, `HC`.`paper_code`, `HC`.`kind_serial`, `HC`.`isUse`, `HC`.`mediaType`, truncate(`HC`.`evalValue` / 1000, 3) AS `evalValue`, `HC`.`evalInitCheck`, (SELECT COUNT(*) FROM `hnp_category` WHERE (`paper_code` = `HC`.`mapCode` OR `HC`.`paper_code` = `mapCode` OR `HC`.`mapCode` = `mapCode`) AND `HC`.`mapCode` IS NOT NULL) AS mapCount, `HC`.`mapCode` FROM `hnp_category` AS `HC` WHERE `HC`.`media_name` NOT IN ('', '글상자', '이미지') ORDER BY if(`HC`.`kind_serial` = '' OR `HC`.`kind_serial` IS NULL,1,0), `HC`.`kind_serial`, if(`HC`.`paper_category` = '' OR `HC`.`paper_category` IS NULL,1,0), `HC`.`paper_category`, `HC`.`media_name`";
    $response = mysqli_query($db_conn, $sql) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    while ($row = mysqli_fetch_assoc($response)) {
        $paper_category_str = "'".strval($row['paper_category'])."'";
        $final_group[$paper_category_str][] = array(
            'media_id' => $row['media_id'],
            'media_name' => $row['media_name'],
            'paper_category' => $row['paper_category'],
            'paper_code' => $row['paper_code'],
            "kind_serial" => $row["kind_serial"],
            "isUse" => $row["isUse"],
            'mediaType' => $row['mediaType'],
            'evalValue' => floatval($row['evalValue']),
            'evalInitCheck' => $row['evalInitCheck'],
            'mapCount' => $row['mapCount'],
            'mapCode' => $row['mapCode']
        );
    }

    $result["success"] = $success;
    $result["final"] = $final_group;
}
$result['q'] = $q;

if (!empty($log)) $result["log"] = $log;

mysqli_close($db_conn);

echo json_encode($result);
?>
