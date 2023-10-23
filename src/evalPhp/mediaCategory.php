<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';

$q = $_REQUEST['q'] ? json_decode($_REQUEST['q'],true) : '';
$log;
$result = array();
$success = true;
$final_type = array();

$sql = "SELECT `kind_serial`, `paper_category`, COUNT(`paper_category`) AS `category_count` FROM `hnp_category` WHERE `media_name` NOT IN ('', '글상자', '이미지') GROUP BY `paper_category` ORDER BY if(`kind_serial` = '' OR `kind_serial` IS NULL,1,0),`kind_serial`, if(`paper_category` = '' OR `paper_category` IS NULL,1,0), `paper_category`, `media_name`";
$response = mysqli_query($db_conn, $sql) or die(json_encode(array("success"=>false, "message1"=>mysqli_errno($db_conn))));
while ($row = mysqli_fetch_assoc($response)) {
    $final[] = array(
        'kind_serial' => $row['kind_serial'],
        'paper_category' => $row['paper_category'],
        'category_count' => $row['category_count']
    );
}
$final[] = array(
    'kind_serial' => 'ETC',
    'paper_category' => '미분류',
    'category_count' => 0
);

$count_type_fail = 0;
$query_type_select = "SELECT `A`.`fKey`, `A`.`sKey`, `A`.`value`, `A`.`alias`, truncate(`B`.`value` / 1000, 3) AS `valueDefault` FROM `hnp_config` AS `A` LEFT OUTER JOIN `hnp_config` AS `B` ON `A`.`sKey` = `B`.`tKey` WHERE `A`.`fKey` = 'MEDIA_TYPE'";
$result_type_select = mysqli_query($db_conn, $query_type_select) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
while ($row = mysqli_fetch_assoc($result_type_select)) {
    if (!$row['valueDefault']) { // INSERT WHEN EMPTY
        $count_type_fail++;
    } else {
        $final_type[] = array(
            'fKey' => $row['fKey'],
            'sKey' => $row['sKey'],
            'value' => $row['value'],
            'alias' => $row['alias'],
            'valueDefault' => floatval($row['valueDefault'])
        );
    }
}

$result['count_type_fail'] = $count_type_fail;
if ($count_type_fail > 0) {
    // 먼저 총기본값 가져와야함 -- 이것도 없으면 어케해야하나 모르겠다
    $query_type_select_default = "SELECT value FROM `hnp_config` WHERE `fKey` = 'DEFAULT_VALUE' AND `sKey` = 'EVAL' AND `tKey` = ''";
    $result_type_select_default = mysqli_query($db_conn, $query_type_select_default) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    $result_type_select_default_single = mysqli_fetch_assoc($result_type_select_default);
    if (count($result_type_select_default_single) === 1) {
        $media_type_default_value = $result_type_select_default_single['value'];
        $query_type_select_empty = "SELECT `A`.`fKey`, `A`.`sKey`, `A`.`value`, `A`.`alias`, `B`.`value`  AS `valueDefault` FROM `hnp_config` AS `A` LEFT OUTER JOIN `hnp_config` AS `B` ON `A`.`sKey` = `B`.`tKey` WHERE `A`.`fKey` = 'MEDIA_TYPE' AND `B`.`value` IS null";
        $result_type_select_empty = mysqli_query($db_conn, $query_type_select_empty) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        while ($row = mysqli_fetch_assoc($result_type_select_empty)) {
            $query_type_insert = "INSERT INTO `hnp_config` (`fKey`, `sKey`, `tKey`, `value`, `description`) VALUES ('DEFAULT_VALUE', 'EVAL', '".$row['sKey']."', '".$media_type_default_value."', '평가가치 기본값 : 타입별 : ".$row['alias']."')";
            mysqli_query($db_conn, $query_type_insert) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        }
        $final_type = array();
        $result_type_select = mysqli_query($db_conn, $query_type_select) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
        while ($row = mysqli_fetch_assoc($result_type_select)) {
            if (!$row['valueDefault']) {
                $count_type_fail++;
            } else {
                $final_type[] = array(
                    'fKey' => $row['fKey'],
                    'sKey' => $row['sKey'],
                    'value' => $row['value'],
                    'alias' => $row['alias'],
                    'valueDefault' => $row['valueDefault']
                );
            }
        }
    } else {
        die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
    }
}

$query_mapmedia_select = "SELECT `fKey`, `value`, `alias` FROM `hnp_config` AS `A` WHERE `fKey` = 'EVAL_VALUE_INIT_MAPMEDIA'";
$response_mapmedia_select = mysqli_query($db_conn, $query_mapmedia_select) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
while ($row = mysqli_fetch_assoc($response_mapmedia_select)) {
    $final_mapmedia[] = array(
        'fKey' => $row['fKey'],
        'value' => $row['value'],
        'alias' => $row['alias']
    );
}

$query_paperkind_select = "SELECT `fKey`, `value`, `alias` FROM `hnp_config` AS `A` WHERE `fKey` = 'EVAL_VALUE_INIT_PAPERKIND'";
$response_paperkind_select = mysqli_query($db_conn, $query_paperkind_select) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
while ($row = mysqli_fetch_assoc($response_paperkind_select)) {
    $final_paperkind[] = array(
        'fKey' => $row['fKey'],
        'value' => $row['value'],
        'alias' => $row['alias']
    );
}

$result['success'] = $success;
$result['final'] = $final;
$result['final_type'] = $final_type;
$result['final_mapmedia'] = $final_mapmedia;
$result['final_paperkind'] = $final_paperkind;

if (!empty($log)) $result['log'] = $log;

mysqli_close($db_conn);

echo json_encode($result);
?>
