<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$log;
$m = $_REQUEST['m'] ? $_REQUEST['m'] : 'p';
// m:p -- policy -- 광고단가사용유형
// m:f -- policy -- 유형0-전체단일 에서 값1개만 꺼내거나 업뎃
// m:$ -- policy -- 유형1-그룹고정 에서 값3개를 꺼내거나 업뎃
$pattern_mode = '/^[putmcvelsaonxrz3]{1}$/';
if (preg_match($pattern_mode, $m, $matches) != 1) {
    die(json_encode(array('success'=>false, 'message'=>'bad request')));
}
$q = $_REQUEST['q'];
$pattern_value = '/^[0-3]{1}$/';
$pattern_value_value0 = '/^[0-9][0-9.]*$/';
$pattern_value_value1 = '/^[A-Z]{3,9}+$/';
$pattern_value_1234 = '/^[1-4]{1}$/';
$result = array();
$success = true;
$final = -1;
$finalarr = array();

if (strcmp($m, 'p') === 0) {
    if (preg_match($pattern_value, $q, $matches) == 1) {
        $query_update = "UPDATE `hnp_config` SET `value` = $q WHERE `fKey` = 'EVAL_VALUE_TYPE'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    } // q에 정보를 담아서 policy를 바꿔달라고 요청했을 경우에

    $sql = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_VALUE_TYPE'";
    $response = mysqli_query($db_conn, $sql) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    $responseset = mysqli_fetch_assoc($response);
    if (count($responseset) === 1) {
        $final = $responseset["value"];
    } else {
        $success = false;
    }
    $result["final"] = $final;
} else if (strcmp($m, 'u') === 0) {
    if (preg_match($pattern_value_value0, $q, $matches) == 1) {
        $q = floatval($q) * 1000;
        $query_update = "UPDATE `hnp_config` SET `value` = $q WHERE `fKey` = 'DEFAULT_VALUE' AND (`tKey` = '' OR `tKey` IS NULL)";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }
    $query_select = "SELECT truncate(`value` / 1000, 3) AS `value` FROM `hnp_config` WHERE `fKey` = 'DEFAULT_VALUE' AND (`tKey` = '' OR `tKey` IS NULL)";
    $response = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    $responseset = mysqli_fetch_assoc($response);
    if (count($responseset) === 1) {
        $final = floatval($responseset["value"]);
    } else {
        $success = false;
    }
    $result["final"] = $final;
} else if (strcmp($m, 't') === 0) { 
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
    if (!empty($q)) { foreach ($q as &$data) {
        if (preg_match($pattern_value_value0, $data['value'], $matches) == 1
                && preg_match($pattern_value_value1, $data['tkey'], $matches) == 1) {
            $data['value'] = floatval($data['value']) * 1000;
            $query_update = "UPDATE `hnp_config` SET `value` = ".$data['value']." WHERE `fKey` = 'DEFAULT_VALUE' AND `tKey` = '".$data['tkey']."'";
            mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
        }
    }}
    $query_select = "SELECT `confValue`.`tKey`, truncate(`confValue`.`value` / 1000, 3) AS `value`, `confType`.`alias` FROM `hnp_config` AS `confValue` LEFT OUTER JOIN `hnp_config` AS `confType` ON `confValue`.`tKey` = `confType`.`sKey` WHERE `confValue`.`fKey` = 'DEFAULT_VALUE' AND `confValue`.`tKey` IS NOT NULL AND `confValue`.`tKey` != '' AND `confValue`.`tKey` NOT LIKE 'CAT_%'";
    $response = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    while ($row = mysqli_fetch_assoc($response)) {
        $row['value'] = floatval($row['value']); 
        $finalarr[] = $row;
    }
    $result['final'] = $finalarr; 

    $mm = $_REQUEST['mm'] ? json_decode($_REQUEST['mm'], true) : '';
    $result['mm_arg'] = $mm; // DEBUG
    $query_update = "UPDATE `hnp_config` SET `value` = " . (is_array($mm) ? intval($mm['value']): intval($mm)) . " WHERE `fKey` = 'EVAL_VALUE_INIT_MAPMEDIA'";
    mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));

    $pk = $_REQUEST['pk'] ? json_decode($_REQUEST['pk'], true) : '';
    $result['pk_arg'] = $pk; 
    $query_update = "UPDATE `hnp_config` SET `value` = " . (is_array($pk) ? intval($pk['value']): intval($mm)) . " WHERE `fKey` = 'EVAL_VALUE_INIT_PAPERKIND'";
    mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
} else if (strcmp($m, 'm') === 0) {
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
    if (!empty($q)) { foreach ($q as $data) {
        if (preg_match($pattern_value_value0, $data['value'], $matches) == 1
                && preg_match($pattern_value_value1, $data['key'], $matches) == 1) {
            $query_update = "UPDATE `hnp_config` SET `value` = ".$data['value']." WHERE `fKey` = 'DEFAULT_VALUE' AND `tKey` = '".$data['key']."'";
            mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        }
    }}
    $o = $_REQUEST['o'] ? json_decode($_REQUEST['o'],true) : '';
    if (strcmp($o['fKey'], 'EVAL_VALUE_ONLINE_INIT_SAME_PAPER') === 0) {
        $query_update = "UPDATE `hnp_config` SET `value` = '".$o['value']."' WHERE `fKey` = '".$o['fKey']."'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    }
} else if (strcmp($m, 'c') === 0) {
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
    if (!empty($q)) { foreach ($q as &$data) {
        if (is_numeric($data['category_id']) && preg_match($pattern_value_value0, $data['eval_value'], $matches) == 1) {
            $data['eval_value'] = floatval($data['eval_value']) * 1000;
            $query_update = "UPDATE `hnp_config` SET `value` = ".$data['eval_value']." WHERE `fKey` = 'DEFAULT_VALUE' AND `tKey` = CONCAT('CAT_', ".$data['category_id'].")";
            mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        }
    }}
    $query_select = "SELECT `cat_meta`.`value` AS `category_id`, `cat_meta`.`alias` AS `category_name`, truncate(`cat_eval`.`value` / 1000, 3) AS `eval_value` FROM (SELECT * FROM `hnp_config` WHERE `fKey` = 'MEDIA_CATEGORY' ORDER BY `sKey`) AS `cat_meta` LEFT OUTER JOIN (SELECT * FROM `hnp_config` WHERE fKey = 'DEFAULT_VALUE' AND `tKey` LIKE 'CAT_%') AS `cat_eval` ON `cat_meta`.`value` = REPLACE(`cat_eval`.`tKey`, 'CAT_', '')";
    $response = mysqli_query($db_conn, $query_select) or die();
    while ($row = mysqli_fetch_assoc($response)) {
        $row['eval_value'] = floatval($row['eval_value']);
        $finalarr[] = $row;
    }
    $result['final'] = $finalarr;
} else if (strcmp($m, 'v') === 0) {
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
    if (preg_match($pattern_value_1234, $q, $matches) == 1) {
        $query_update = "UPDATE `hnp_config` SET `value` = $q WHERE `fKey` = 'EVAL_CALC_TYPE'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    }

    $query_select = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_CALC_TYPE'";
    $response = mysqli_query($db_conn, $query_select);
    if ($response) {
        $responseset = mysqli_fetch_assoc($response);
        if (count($responseset) === 1) {
            $final = $responseset['value'];
        } else {
            $success = false;
        }
        $result['final'] = $final;
    } else {
        $result['final'] = 4;
    }

    $final_list = array();
    //$final_list[1] = array('short' => '평가1', 'long' => '통계의 기사가치 평가에서 평가1 항목만 사용합니다.');
    //$final_list[2] = array('short' => '평가2', 'long' => '통계의 기사가치 평가에서 평가2 항목만 사용합니다.');
    $final_list[1] = array('short' => '평균', 'long' => '통계의 기사가치 평가에서 평가1 과 평가2 항목 가치의 평균을 적용합니다.');
    $final_list[2] = array('short' => '곱', 'long' => '통계의 기사가치 평가에서 평가1 과 평가2 항목 가치의 곱을 적용합니다.');
    $result['final_list'] = $final_list;
} else if (equals($m, 'e')) {
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
    if (!empty($q)) { foreach ($q as $key => $value) {
        $insert_value = is_bool($value['value']) ? ($value['value'] ? 'Y' : 'N') : 'N';
        $insert_key = (equals('M1', $value['sKey']) || equals('M2', $value['sKey'])) ? $value['sKey'] : 'UNREACHABLE';
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $insert_value . "' WHERE `fKey` = 'EVAL_ACTIVATE' AND `sKey` = '" . $value['sKey'] . "'";
        mysqli_query($db_conn, $query_update);
    }}

    $query_count = "SELECT COUNT(`seq`) AS `cfg_cnt` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE'";
    $response = mysqli_query($db_conn, $query_count);
    if ($response) {
        $responseset = mysqli_fetch_assoc($response);
        $result_count = $responseset['cfg_cnt'];
    } else {
        $result_count = 0;
    }

    if ($result_count != 2) {
        mysqli_query($db_conn, "DELETE FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE'");
        mysqli_query($db_conn, "INSERT INTO `hnp_config` (`fKey`, `sKey`, `value`, `description`) VALUES ('EVAL_ACTIVATE', 'M1', 'Y', '평가항목 사용여부 / 수동평가1')");
        mysqli_query($db_conn, "INSERT INTO `hnp_config` (`fKey`, `sKey`, `value`, `description`) VALUES ('EVAL_ACTIVATE', 'M2', 'Y', '평가항목 사용여부 / 수동평가2')");
    }

    $query_select = "SELECT `sKey`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE' ORDER BY `seq`";
    $response = mysqli_query($db_conn, $query_select);
    while ($row = mysqli_fetch_assoc($response)) {
        $finalarr[] = array(
            'sKey' => $row['sKey'],
            'value' => strcmp($row['value'], 'Y') === 0 ? true : false
        );
    }

    $success = true;
    $result['final'] = $finalarr;
} else if (equals($m, 'l')) {
    $q = $_REQUEST['q'] ? json_decode($_REQUEST['q'], true) : '';
    if (!empty($q)) { foreach ($q as $key => $value) {
        foreach ($value['item'] as $innerkey => $innervalue) {
            if (is_numeric($innervalue['seq']) && is_bool($innervalue['isEval'])) {
                if (equals($key, 'AT') || equals($key, 'M2')) {
                  $query_update = "UPDATE `evaluation` SET `isEval` = '" . (($innervalue['isEval']) ? "Y" : "N") . "' WHERE `seq` = {$innervalue['seq']};";
                } else if (equals($key, 'M1')) {
                  $query_update = "UPDATE `hnp_config` SET `value` = '" . (($innervalue['isEval']) ? "Y" : "N") . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'M1'";
                }
                mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
            }
        }
    }}

    $query_select = "(SELECT `seq`, '자동평가' AS `alias`, 'AT' AS `class`, `name`, `isEval` FROM `evaluation` WHERE `seq` < 1000) UNION (SELECT `seq`, '수동평가1' AS `alias`, 'M1' AS `class`, '평가1' AS `name`, `value` AS `isEval` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'M1') UNION (SELECT `seq`, '수동평가2' AS `alias`, 'M2' AS `class`, `name`, `isEval` FROM `evaluation` WHERE `seq` > 1000)";
    $result_select = mysqli_query($db_conn, $query_select);
    while ($row = mysqli_fetch_assoc($result_select)) {
        $finalarr[$row['class']]['alias'] = $row['alias'];
        $finalarr[$row['class']]['item'][] = array(
            'seq' => $row['seq'],
            'name' => $row['name'],
            'isEval' => equals($row['isEval'], 'Y')
        );
    }
    $success = true;
    $result['final'] = $finalarr;
} else if (equals($m, 's') || equals($m, 'a')) { // 기사면적 // 면적비율
	$sKey = "SIZE";
	if(equals($m, 'a')) {
		$sKey = "RATIO";
	}
    $q = $_REQUEST['q'];
    if (preg_match('/^[YN]{1}$/', $q, $matches) == 1) {
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $q . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = '" . $sKey . "'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    $query_select = "SELECT `value`, `description` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = '" . $sKey . "'";
    $result_select = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    if ($result_select) {
        $result_select_final = mysqli_fetch_assoc($result_select);
        $result_value = $result_select_final['value'];
    } else {
        $result_value = 'Y';
    }

    $success = true;
    $result['final'] = $result_value;
} else if (equals($m, 'o')) { // O:online
    $q = $_REQUEST['q'];
    if (preg_match('/^[0-9]+$/', $q, $matches)) {
        $setSeq = 0;
        $query_select = "SELECT `seq` FROM `evalClassify` WHERE `evaluation_seq` = 1";
        $result_select = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
        while ($row = mysqli_fetch_assoc($result_select)) {
            if ($q == $row['seq']) $setSeq = $q;
        }
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $setSeq . "' WHERE `fKey` = 'EVAL_AUTO' AND `sKey` = 'SIZE_INVALID'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    $query_select = "SELECT `value`, `description` FROM `hnp_config` WHERE `fKey` = 'EVAL_AUTO' AND `sKey` = 'SIZE_INVALID'";
    $result_select = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    if ($result_select) {
        $result_select_final = mysqli_fetch_assoc($result_select);
        $result_value = $result_select_final['value'];
    } else {
        $result_value = '0';
    }
    $result['final'] = $result_value;
} else if (equals($m, 'n')) { // N:nouse
    $qs = is_meaningful_rgx($_REQUEST['qs'], '[YN]{1}');
    if ($qs !== false) {
        $query_update = "UPDATE `hnp_config` SET `value` = '$qs' WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tkey` = 'INC_SEARCH'";
        @mysqli_query($db_conn, $query_update);
    }
    $qr = is_meaningful_rgx($_REQUEST['qr'], '[YN]{1}');
    if ($qr !== false) { // update or insert
        $query_update = "UPDATE `hnp_config` SET `value` = '$qr' WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tkey` = 'INC_EVAL'";
        @mysqli_query($db_conn, $query_update);
    }

    $result['final'] = array('INC_SEARCH' => 'Y', 'INC_EVAL' => 'Y');
    $query_select = "SELECT `tKey` AS `k`, `value` AS `v` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM'";
    $response = mysqli_query($db_conn, $query_select);
    if ($response) {
        for ($set = array(); $row = mysqli_fetch_assoc($response); $set[$row['k']] = $row['v']);
        if (count($set) === 2) {
            $result['final'] = $set;
        }
    }
} else if (equals($m, 'x')) { // X:multiply, correction value
    $q = is_meaningful_rgx($_REQUEST['q'], '[0-9]+(\.[0-9]+)*');
    if ($q !== false) {
        $query_update = "UPDATE `hnp_config` SET `value` = '$q' WHERE `fKey` = 'EVAL_CORRECTION_VALUE'";
        @mysqli_query($db_conn, $query_update);
    }

    $query_select = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_CORRECTION_VALUE'";
    $response = mysqli_query($db_conn, $query_select);
    if ($response) {
        $responseset = mysqli_fetch_assoc($response);
        if (count($responseset) === 1) {
            $final = $responseset['value'];
        } else {
            $success = false;
        }
        $result['final'] = $final;
    } else {
        $result['final'] = '1.25';
    }
} else if (equals($m, 'r')) { // R:ratio, ratio_online
    $q = is_meaningful_rgx($_REQUEST['q'], '[YN]{1}');
    if ($q !== false) {
        $query_update = "UPDATE `hnp_config` SET `value` = '$q' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO_ONLINE'";
        @mysqli_query($db_conn, $query_update);
    }

    $query_select = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO_ONLINE'";
    $response = mysqli_query($db_conn, $query_select);
    if ($response) {
        $responseset = mysqli_fetch_assoc($response);
        if (count($responseset) === 1) {
            $final = $responseset['value'];
        } else {
            $success = false;
        }
        $result['final'] = $final;
    } else {
        $result['final'] = 'Y';
    }
} else if (equals($m, 'z')) { // R:ratio, ratio_online
    // 기사면적 as
    $as = $_REQUEST['as'];
    if ($as === 'Y' || $as === 'N') {
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $as . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    // 기사비율 ar
    $ar = $_REQUEST['ar'];
    if ($ar === 'Y' || $ar === 'N') {
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $ar . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    // 기사면적온라인 aso
    $aso = $_REQUEST['aso'];
    if ($aso === 'Y' || $aso === 'N') {
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $aso . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE_ONLINE'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    // 기사비율온라인 aro
    $aro = $_REQUEST['aro'];
    if ($aro === 'Y' || $aro === 'N') {
        $query_update = "UPDATE `hnp_config` SET `value` = '" . $aro . "' WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO_ONLINE'";
        mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }

    // 조회
    $eval_use = array( 'SIZE' => 'N', 'RATIO' => 'N', 'SIZE_ONLINE' => 'N', 'RATIO_ONLINE' => 'N' );
    $query_select = "SELECT `sKey`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `fKey` = 'EVAL_USE' AND `sKey` IN ('SIZE', 'SIZE_ONLINE', 'RATIO', 'RATIO_ONLINE')";
    $result_select = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    while ($row = mysqli_fetch_assoc($result_select)) {
        $eval_use[$row['sKey']] = $row['value']; // db에 값 없었으면 기본값으로 남는다
    }

    $success = true;
    $result['final'] = $eval_use;
} else if (equals($m, '3')) {
    $q = is_meaningful_rgx($_REQUEST['q'], '[YN]{1}');
    $final = 'N';
    if ($q !== false) { // update or insert
        $query_update = "UPDATE `hnp_config` SET `value` = '$q' WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'VIEW_3.5CATEGORY'";
        @mysqli_query($db_conn, $query_update);
    }

    $query_select = "SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'VIEW_3.5CATEGORY'";
    $response = mysqli_query($db_conn, $query_select);
    if ($response) {
        $responseset = mysqli_fetch_assoc($response);
        if (count($responseset) === 1) {
            $final = $responseset['value'];
        }
    }
    $result['final'] = $final;
}

$result['success'] = $success;

if (!empty($log)) $result['log'] = $log;

echo json_encode($result);
?>
