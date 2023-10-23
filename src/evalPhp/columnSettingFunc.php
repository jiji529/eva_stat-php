<?php
include_once __DIR__ . '/common.php';

function prepareColumnSetting($db, $arg) {

}

function getColumnSetting($db, $arg) {
  if ($db->Error()) {
    return false;
  }
  $rtn = array(); $condition = '';
  if (equals($arg, 'WEB') || equals($arg, 'XLS')) {
    $condition = "`fKey` = 'COLUMN_ITEM_" . $arg . "'";
  } else {
    $condition = "`fKey` IN ('COLUMN_ITEM_WEB', 'COLUMN_ITEM_XLS')";
  }

  $query_select = "SELECT `seq`, REPLACE(`fKey`, 'COLUMN_ITEM_', '') AS `category`, `sKey` AS `field`, `value` AS 'order_use', `alias`, `description` AS `title` FROM `hnp_config` WHERE " . $condition . " AND `tKey` IS NULL ORDER BY `fKey`, `value`";
  $db->Query($query_select);
  if ($db->Error() || $db->RowCount() == 0) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      $order_use_arr = explode('_', $row['order_use']);
      if (count($order_use_arr) === 2) {
        $row['order'] = $order_use_arr[0];
        $row['use'] = $order_use_arr[1];
      } else {
        $row['order'] = 1;
        $row['use'] = Y;
      }
      unset($row['order_use']);
      $final[] = $row;
    }
  }

  $rtn['final'] = $final;

  return $rtn;
}

// not mysql.class.php(Ultimate MySQL Wrapper Class)
function getColumnSettingClassic($db_classic, $arg) {
  $rtn = array(); $condition = '';
  if (equals($arg, 'WEB') || equals($arg, 'XLS')) {
    $condition = "`fKey` = 'COLUMN_ITEM_" . $arg . "'";
  } else {
    $condition = "`fKey` IN ('COLUMN_ITEM_WEB', 'COLUMN_ITEM_XLS')";
  }

  $query_select = "SELECT `seq`, REPLACE(`fKey`, 'COLUMN_ITEM_', '') AS `category`, `sKey` AS `field`, `value` AS 'order_use', `alias`, `description` AS `title` FROM `hnp_config` WHERE " . $condition . " AND `tKey` IS NULL ORDER BY `fKey`, `value`";
  $response = mysqli_query($db_classic, $query_select) or die(json_encode(array('success'=>false, 'message'=>$query_select)));//mysqli_errno($db_classic)
  while ($row = mysqli_fetch_assoc($response)) {
    $order_use_arr = explode('_', $row['order_use']);
    if (count($order_use_arr) === 2) {
      $row['order'] = $order_use_arr[0];
      $row['use'] = $order_use_arr[1];
    } else {
      $row['order'] = 1;
      $row['use'] = Y;
    }
    unset($row['order_use']);
    $final[] = $row;
  }

  $rtn['final'] = $final;

  return $rtn;
}
