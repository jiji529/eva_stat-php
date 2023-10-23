<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';
include_once __DIR__ . '/dbConnTemplate.php';
include_once __DIR__ . '/ClassSearch.php';

$m = $_REQUEST['m'] ? $_REQUEST['m'] : 'v';
// m:v -- 기본항목 우선표시
// m:c -- 표시항목
$pattern_mode = '/^[vc]{1}$/';
if (preg_match($pattern_mode, $m, $matches) != 1) {
    header("HTTP/1.1 400");
    die(json_encode(array('success'=>false, 'message'=>'bad request')));
}
$q = $_REQUEST['q'] ? json_decode($_REQUEST['q'], true) : null;
$rtn = array(); $final = array();

if (equals($m, 'v')) {

  // INSERT...
  $query_count = "SELECT COUNT(*) AS `cfg_cnt` FROM `hnp_config` WHERE `fKey` = 'COLUMN_ITEM_DEFAULT'";
  $response = mysqli_query($db_conn, $query_count) or die(json_encode(array('success'=>false, 'message'=>'1')));
  if ($response) {
    $responseset = mysqli_fetch_assoc($response);
    $result_count = (int)$responseset['cfg_cnt'];
  } else {
    $result_count = 0;
  }
  $rtn['count'] = $result_count;
  if ($result_count > 1) {
    $query_delete = "DELETE FROM `hnp_config` WHERE `fKey` = 'COLUMN_ITEM_DEFAULT'";
    mysqli_query($db_conn, $query_delete) or die(json_encode(array('success'=>false, 'message'=>'2')));
    $rtn['flush'] = true;
  }
  if ($result_count !== 1) {
    $query_insert = "INSERT INTO `hnp_config` (`fKey`, `value`, `alias`, `description`) VALUES ('COLUMN_ITEM_DEFAULT', 'Y', '기본화면구성-기본정보표시', '기본화면구성-기본정보표시')";
    mysqli_query($db_conn, $query_insert) or die(json_encode(array('success'=>false, 'message'=>'3')));
    $rtn['add'] = true;
  }

  // 0 -> INSERT
  // 1 -> UPDATE
  // 2~ -> DELETE & INSERT
  if ($q) {
    $value_update = equals($q['value'], 'Y') ? 'Y' : 'N';
    $query_update = "UPDATE `hnp_config` SET `value` = '" . $value_update . "' WHERE `fKey` = 'COLUMN_ITEM_DEFAULT'";
    mysqli_query($db_conn, $query_update) or die(json_encode(array('success'=>false, 'message'=>'4')));
    $rtn['edit'] = true;
  }

  $query_select = "SELECT `value`, `alias` FROM `hnp_config` WHERE `fKey` = 'COLUMN_ITEM_DEFAULT'";
  $response = mysqli_query($db_conn, $query_select) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));;
  if ($response) {
    $responseset = mysqli_fetch_assoc($response);
    // $result_final = equals($responseset['value'], 'Y') ? true : false;
    $result_final = $responseset;
  } else {
    $result_final = false;
  }

  $rtn['success'] = true;
  $rtn['data'] = $result_final;
  echo json_encode($rtn);
} else if (equals($m, 'c')) {
  // PRE-CHECK - INSERT
  $_COLUMNS = array(); $order_index = 1;
  $_COLUMNS['mode'] = array('WEB', 'XLS');

  // templateDB.hnp_config 에서 기본정보 가져옴
  $query_select_template = "SELECT DISTINCT(`sKey`) AS `code`, `description` AS `name` FROM `hnp_config` WHERE `fKey` IN ('COLUMN_ITEM_WEB', 'COLUMN_ITEM_XLS') AND `tKey` IS NULL ORDER BY `fKey`, `value`";
  $response_select_template = mysqli_query($db_conn_template, $query_select_template) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn_template))));
  while ($row = mysqli_fetch_assoc($response_select_template)) {
    $_COLUMNS['data'][] = array(
      'order' => $order_index++, 'code' => $row['code'], 'name' => $row['name']
    );
  }
/*
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'eva_1', 'name' => '평가-자동1'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'eva_2', 'name' => '평가-자동2'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'eva_3', 'name' => '평가-자동3'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'eva_4', 'name' => '평가-자동4'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'eva_5', 'name' => '평가-자동5'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'ev1_big', 'name' => '평가-수동1-대분류'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'ev1_mid', 'name' => '평가-수동1-중분류'
  );
  $_COLUMNS['data'][] = array(
    'order' => $order_index++, 'code' => 'ev1_sml', 'name' => '평가-수동1-소분류'
  );*/

  $query_select = "SELECT CONCAT('ev2_', `seq`) AS `code`, CONCAT('평가-수동2-', `name`) AS `name` FROM `evaluation` WHERE `seq` > 1000";
  $response = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $_COLUMNS['data'][] = array(
      'order' => $order_index++, 'code' => $row['code'], 'name' => $row['name']
    );
  } // 사용자 DB에서 수동평가2 정보 가져옴

  $rtn['col'] = $_COLUMNS;

  $msg = ''; $code = ''; $order_max_exist = array('WEB' => 0, 'XLS' => 0);
  $query_select = "SELECT REPLACE(`fKey`, 'COLUMN_ITEM_', '') AS `category`, `sKey` AS `code`, `value` FROM `hnp_config` WHERE `fKey` IN ('COLUMN_ITEM_WEB', 'COLUMN_ITEM_XLS') AND `tKey` IS NULL ORDER BY `fKey`, `value`";
  $response = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $tmpArr[$row['category'] . '+' . $row['code']] = $row;
    // 기존 레코드 돌리는 여기서 value에 들어간 order 최대값 알아내야 함
    $order_max_exist[$row['category']] = max($order_max_exist[$row['category']], intval($row['value']));
  }

  $haveToInsert = true;
  foreach ($_COLUMNS['mode'] as $mk => $mv) { // WEB | XLS
    foreach ($_COLUMNS['data'] as $dk => $dv) {
      $code = $dv['code'];
      $haveToInsert = true;
      foreach ($tmpArr as $tk => $tv) {
        if (equals($mv, $tv['category']) && equals($dv['code'], $tv['code'])) {
          $haveToInsert = false; break;
        }
      }
      if ($haveToInsert) {
        $msg .= $code . '_';
        $query_insert = "INSERT INTO `hnp_config` (`fKey`, `sKey`, `value`, `alias`, `description`) VALUES ('COLUMN_ITEM_" . $mv . "', '" . $code . "', '" . str_pad(++$order_max_exist[$mv], 2, '0', STR_PAD_LEFT) . "_Y', '" . $dv['name'] . "', '" . $dv['name'] . "')";
        mysqli_query($db_conn, $query_insert);
        $msg .= $query_insert . ";";
      }
      unset($tmpArr[$mv . '+' . $dv['code']]);
    }
  }

  if ($stmt = mysqli_prepare($db_conn, 'DELETE FROM `hnp_config` WHERE `tKey` IS NULL AND `fKey` = ? AND `sKey` = ?')) {
    $del_fkey = null; $del_skey = null;
    if (mysqli_stmt_bind_param($stmt, 'ss', $del_fkey, $del_skey)) {
      foreach ($tmpArr as $tv) {
        $del_fkey = 'COLUMN_ITEM_' . $tv['category'];
        $del_skey = $tv['code'];
        mysqli_stmt_execute($stmt);
      }
    }
  }

  if ($q) { // UPDATE
    // $pattern = '/^[0-4]{1}[0-4,]*$/';
    // order,seq : [0-9]+
    // use : YN
    // title : 한글~
    // category: WEB|XLS
    foreach ($q as $key => $value) {
      $query_update = "UPDATE `hnp_config` SET `value` = '" . str_pad($value['order'], 2, '0', STR_PAD_LEFT) . '_' . $value['use'] . "', `alias` = '" . $value['title'] . "' WHERE `fKey` = CONCAT('COLUMN_ITEM_', '" . $value['category'] . "') AND `seq` = " . $value['seq'] . ';';
      mysqli_query($db_conn, $query_update) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
    }
  }

  $query_select = "SELECT `seq`, REPLACE(`fKey`, 'COLUMN_ITEM_', '') AS `category`, `sKey` AS `code`, `value` AS 'order_use', `alias` AS `title`, `description` AS `desc` FROM `hnp_config` WHERE `fKey` IN ('COLUMN_ITEM_WEB', 'COLUMN_ITEM_XLS') AND `tKey` IS NULL ORDER BY `fKey`, `value`";
  $response = mysqli_query($db_conn, $query_select) or die(json_encode(array("success"=>false, "message"=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $order_use_arr = explode('_', $row['order_use']);
    if (count($order_use_arr) === 2) {
      $row['order'] = (int)$order_use_arr[0];
      $row['use'] = $order_use_arr[1];
    } else {
      $row['order'] = 1;
      $row['use'] = Y;
    }
    unset($row['order_use']);
    $final[] = $row;
  }

  $rtn['success'] = true;
  $rtn['data'] = $final;

  echo json_encode($rtn);
}
