<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);

include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassSearch.php';

function getConfigEval($db) {
  $_CONF_INDEX_ALL = 128; // ?
  $config_eval = array(); // 기사가치 계산 통합설정
  $rtn = array();
  if ($db->Error()) {
    return false;
  }

  $query_config_eval_type = "SELECT `conf_type`.`value` AS `sequence`, `conf_type`.`sKey` AS `type`, `conf_type`.`alias` AS `name`, `conf_value`.`value` AS `evalValue` FROM hnp_config AS `conf_type` LEFT OUTER JOIN hnp_config AS `conf_value` ON `conf_type`.`sKey` = `conf_value`.`tKey` AND `conf_value`.`fKey` = 'DEFAULT_VALUE' WHERE `conf_type`.`fKey` = 'MEDIA_TYPE'";
  $db->Query($query_config_eval_type);
  if ($db->Error() || $db->RowCount() == 0) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      $config_eval['type'][$row['sequence']] = array(
        'category_id' => $row['sequence'], // compatible
        'category_name' => $row['name'], // compatible
        'category_eval_value' => $row['evalValue'], // compatible
        'type' => $row['type'],
        'name' => $row['name'],
        'evalValue' => $row['evalValue']
      );
    }
  }

  $query_config_eval_category = "SELECT `cat_meta`.`sKey` AS `category_order`, `cat_meta`.`value` AS `category_id`, `cat_meta`.`alias` AS `category_name`, `cat_eval`.`value` AS `category_eval_value` FROM (SELECT * FROM `hnp_config` WHERE `fKey` = 'MEDIA_CATEGORY' ORDER BY `sKey`) AS `cat_meta` LEFT OUTER JOIN (SELECT * FROM `hnp_config` WHERE fKey = 'DEFAULT_VALUE' AND `tKey` LIKE 'CAT_%') AS `cat_eval` ON `cat_meta`.`value` = REPLACE(`cat_eval`.`tKey`, 'CAT_', '')";
  $db->Query($query_config_eval_category);
  if ($db->Error() || $db->RowCount() == 0) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      $config_eval['category'][$row['category_id']] = $row;
    }
  }

  $query_config_eval_policy = "(SELECT 'US' AS `class`, `sKey` AS `seq`, `description` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE') UNION (SELECT 'AT' AS `class`, `seq`, `name`, `isUse` AS `value` FROM `evaluation` WHERE `seq` < 1000) UNION (SELECT 'SZ' AS `class`, `seq`, '기사면적적용' AS `name`, `value` AS `isEval` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE') UNION (SELECT 'RT' AS `class`, `seq`, '기사면적비율적용' AS `name`, `value` AS `isEval` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO') UNION (SELECT 'OA' AS `class`, `fKey` AS `seq`, '온라인 기사' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_AUTO' AND `sKey` = 'SIZE_INVALID') UNION (SELECT 'MD' AS `class`, `fKey` AS `seq`, '매체단가&평가결합' AS `name`, `value` FROM `hnp_config` WHERE `fKey` IN ('EVAL_VALUE_TYPE', 'EVAL_CALC_TYPE')) UNION (SELECT 'NR' AS `class`, 'NORMAL_REPORTER' AS `seq`, '일반기자고유번호' AS `name`, `seq` AS `value` FROM `evalClassify` WHERE `value` = '일반기자') UNION (SELECT 'SV' AS `class`, 'SINGLE_EVAL' AS `seq`, '평가가치기본값-전체단일' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'DEFAULT_VALUE' AND (`tKey` = '' OR `tKey` IS NULL)) UNION (SELECT 'CS' AS `class`, 'CALL_NAME_ENG' AS `seq`, '기본화면-기본정보표시' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'COLUMN_ITEM_DEFAULT') UNION (SELECT 'OY' AS `class`, 'OLDEST_NEWS_YEAR' AS `seq`, '최소년도' AS `name`, MIN(news_date) AS `value` FROM `hnp_news`) UNION (SELECT 'CL' AS `class`, 'CATEGORY_LAYOUT' AS `seq`, '평가목록분류구분사용' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CATE_USE') UNION (SELECT 'NU' AS `class`, 'NOUSE_ON_SEARCH' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tKey` = 'INC_SEARCH') UNION (SELECT 'NV' AS `class`, 'NOUSE_ON_RESULT' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tkey` = 'INC_EVAL') UNION (SELECT 'RO' AS `class`, `sKey` AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO_ONLINE') UNION (SELECT 'SO' AS `class`, `sKey` AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE_ONLINE') UNION (SELECT 'CV' AS `class`, 'MULTIPLY ALL' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_CORRECTION_VALUE') UNION (SELECT 'S3' AS `class`, 'STAT_3_MODE' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'VIEW_3.5CATEGORY')";
  $db->Query($query_config_eval_policy);
  if ($db->Error()) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      if (equals($row['class'], 'AT') || equals($row['class'], 'MD') || equals($row['class'], 'US')) {
        $config_eval['policy'][$row['class']][$row['seq']] = $row;
      } else {
        $config_eval['policy'][$row['class']] = $row;
      }
    }
  }
  $pattern_ymd = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
  if (preg_match($pattern_ymd, strval($config_eval['policy']['OY']['value']), $matches) === 1) {
    $news_date_oldest = explode('-', $config_eval['policy']['OY']['value']);
    $config_eval['policy']['OY']['value'] = $news_date_oldest[0];
  } else {
    $config_eval['policy']['OY']['value'] = date('Y');
  }

  $query_config_eval_group = "(SELECT 'AT' AS `class`, `seq`, `name`, `isEval`, `isUse`, `order` FROM `evaluation` WHERE `automatic` = 'Y') UNION (SELECT 'M1' AS `class`, `seq`, '평가1' AS `name`, `value` AS `isEval`, (SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE' AND `sKey` = 'M1') AS `isUse`, 0 AS `order` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'M1') UNION (SELECT 'M2' AS `class`, `seq`, `name`, `isEval`, `isUse`, `order` FROM `evaluation` WHERE `automatic` = 'N')";
  $db->Query($query_config_eval_group);
  if ($db->Error() || $db->RowCount() < 6) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      if (equals($row['class'], 'M1')) {
        $config_eval['group'][$row['class']] = $row;
      } else {
        $config_eval['group'][$row['class']][$row['seq']] = $row;
      }
    }
  }

  $query_config_eval_item = "(SELECT 'AT_M2' AS `class`, `E`.`seq`, `E`.`value`, `E`.`refValue`, `E`.`score`, `E`.`isUse`, `E`.`order`, `EG`.`seq` AS `group_seq`, `EG`.`name` AS `group_name`, `EG`.`isUse` AS `group_isUse`, `EG`.`isEval` AS `group_isEval`, `EG`.`automatic` AS `group_isAuto` FROM `evalClassify` AS `E` LEFT OUTER JOIN `evaluation` AS `EG` ON `EG`.`seq` = `E`.`evaluation_seq`) UNION (SELECT 'M1' AS `class`, `C`.`seq`, `C`.`name` AS `value`, NULL AS `refValue`, `C`.`score`, `C`.`isUse`, `C`.`order`, `C`.`upperSeq` AS `group_seq`, '평가항목1' AS `group_name`, `M1_IS_USE`.`value` AS `group_isUse`, `M1_IS_EVAL`.`value` AS `group_isEval`, NULL AS `group_isAuto` FROM category AS `C` LEFT OUTER JOIN `hnp_config` AS `M1_IS_USE` ON `M1_IS_USE`.`fKey` = 'EVAL_ACTIVATE' AND `M1_IS_USE`.`sKey` = 'M1' LEFT OUTER JOIN `hnp_config` AS `M1_IS_EVAL` ON `M1_IS_EVAL`.fKey = 'EVAL_USE' AND `M1_IS_EVAL`.sKey = 'M1')";
  $db->Query($query_config_eval_item);
  if ($db->Error()) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      $config_eval['item'][$row['class']][$row['seq']] = $row;
    }
  }

  $query_config_eval_item_by_group = "(SELECT `G`.`seq` AS `group_seq`, `G`.`name` AS `group_name`, `G`.`automatic` AS `group_use`, `I`.`seq`, `I`.`order`, `I`.`value`, `I`.`refValue`, `I`.`score`, `I`.`isUse` FROM `evalClassify` AS `I` LEFT OUTER JOIN `evaluation` AS `G` ON `G`.`seq` = `I`.`evaluation_seq` WHERE `G`.`automatic` = 'Y' ORDER BY `evalClassify`.`evaluation_seq`, `evalClassify`.`refValue`+0 DESC, `evalClassify`.`order`) UNION (SELECT G.seq AS `group_seq`, `G`.`name` AS `group_name`, `G`.`automatic` AS `group_use`, I.`seq`, I.`order`, I.`value`, I.refValue, I.score, I.isUse FROM `evalClassify` AS `I` LEFT OUTER JOIN `evaluation` AS `G` ON `I`.`evaluation_seq` = `G`.`seq` WHERE `G`.`automatic` = 'N' ORDER BY `I`.`evaluation_seq`, `I`.`refValue` DESC, `I`.`order`)";
  $db->Query($query_config_eval_item_by_group);
  if ($db->Error()) {
    return false;
  } else {
    while ($row = mysqli_fetch_array($db->Records(), MYSQLI_ASSOC)) {
      $config_eval['group_item'][$row['group_seq']][$row['seq']] = $row;
    }
  }

  return $config_eval;
}

// not mysql.class.php(Ultimate MySQL Wrapper Class)
function getConfigEvalClassic($db_classic) {
  $config_eval = array(); // 기사가치 계산 통합설정
  $rtn = array();

  $query_config_eval_type = "SELECT `conf_type`.`value` AS `sequence`, `conf_type`.`sKey` AS `type`, `conf_type`.`alias` AS `name`, `conf_value`.`value` AS `evalValue` FROM hnp_config AS `conf_type` LEFT OUTER JOIN hnp_config AS `conf_value` ON `conf_type`.`sKey` = `conf_value`.`tKey` AND `conf_value`.`fKey` = 'DEFAULT_VALUE' WHERE `conf_type`.`fKey` = 'MEDIA_TYPE'";
  $response = mysqli_query($db_classic, $query_config_eval_type) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_classic))));
  while ($row = mysqli_fetch_assoc($response)) {
    $config_eval['type'][$row['sequence']] = array(
      'category_id' => $row['sequence'], // compatible
      'category_name' => $row['name'], // compatible
      'category_eval_value' => $row['evalValue'], // compatible
      'type' => $row['type'],
      'name' => $row['name'],
      'evalValue' => $row['evalValue']
    );
  }

  $query_config_eval_category = "SELECT `cat_meta`.`sKey` AS `category_order`, `cat_meta`.`value` AS `category_id`, `cat_meta`.`alias` AS `category_name`, `cat_eval`.`value` AS `category_eval_value` FROM (SELECT * FROM `hnp_config` WHERE `fKey` = 'MEDIA_CATEGORY' ORDER BY `sKey`) AS `cat_meta` LEFT OUTER JOIN (SELECT * FROM `hnp_config` WHERE fKey = 'DEFAULT_VALUE' AND `tKey` LIKE 'CAT_%') AS `cat_eval` ON `cat_meta`.`value` = REPLACE(`cat_eval`.`tKey`, 'CAT_', '')";
  $response = mysqli_query($db_classic, $query_config_eval_category) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $config_eval['category'][$row['category_id']] = $row;
  }

  $query_config_eval_policy = "(SELECT 'US' AS `class`, `sKey` AS `seq`, `description` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE') UNION (SELECT 'AT' AS `class`, `seq`, `name`, `isUse` AS `value` FROM `evaluation` WHERE `seq` < 1000) UNION (SELECT 'SZ' AS `class`, `seq`, '기사면적적용' AS `name`, `value` AS `isEval` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE') UNION (SELECT 'RT' AS `class`, `seq`, '기사면적비율적용' AS `name`, `value` AS `isEval` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO') UNION (SELECT 'OA' AS `class`, `fKey` AS `seq`, '온라인 기사' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_AUTO' AND `sKey` = 'SIZE_INVALID') UNION (SELECT 'MD' AS `class`, `fKey` AS `seq`, '매체단가&평가결합' AS `name`, `value` FROM `hnp_config` WHERE `fKey` IN ('EVAL_VALUE_TYPE', 'EVAL_CALC_TYPE')) UNION (SELECT 'NR' AS `class`, 'NORMAL_REPORTER' AS `seq`, '일반기자고유번호' AS `name`, `seq` AS `value` FROM `evalClassify` WHERE `value` = '일반기자') UNION (SELECT 'SV' AS `class`, 'SINGLE_EVAL' AS `seq`, '평가가치기본값-전체단일' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'DEFAULT_VALUE' AND (`tKey` = '' OR `tKey` IS NULL)) UNION (SELECT 'CS' AS `class`, 'CALL_NAME_ENG' AS `seq`, '기본화면-기본정보표시' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'COLUMN_ITEM_DEFAULT') UNION (SELECT 'OY' AS `class`, 'OLDEST_NEWS_YEAR' AS `seq`, '최소년도' AS `name`, MIN(news_date) AS `value` FROM `hnp_news`) UNION (SELECT 'CL' AS `class`, 'CATEGORY_LAYOUT' AS `seq`, '평가목록분류구분사용' AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'CATE_USE') UNION (SELECT 'NU' AS `class`, 'NOUSE_ON_SEARCH' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tKey` = 'INC_SEARCH') UNION (SELECT 'NV' AS `class`, 'NOUSE_ON_RESULT' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'NOUSE_ITEM' AND `tkey` = 'INC_EVAL') UNION (SELECT 'RO' AS `class`, `sKey` AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'RATIO_ONLINE') UNION (SELECT 'SO' AS `class`, `sKey` AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'SIZE_ONLINE') UNION (SELECT 'CV' AS `class`, 'MULTIPLY ALL' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_CORRECTION_VALUE') UNION (SELECT 'S3' AS `class`, 'STAT_3_MODE' AS `seq`, `alias` AS `name`, `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_LAYOUT' AND `sKey` = 'VIEW_3.5CATEGORY')";
  $response = mysqli_query($db_classic, $query_config_eval_policy) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    if (equals($row['class'], 'AT') || equals($row['class'], 'MD') || equals($row['class'], 'US')) {
      $config_eval['policy'][$row['class']][$row['seq']] = $row;
    } else {
      $config_eval['policy'][$row['class']] = $row;
    }
  }
  $pattern_ymd = '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/';
  if (preg_match($pattern_ymd, strval($config_eval['policy']['OY']['value']), $matches) === 1) {
    $news_date_oldest = explode('-', $config_eval['policy']['OY']['value']);
    $config_eval['policy']['OY']['value'] = $news_date_oldest[0];
  } else {
    $config_eval['policy']['OY']['value'] = date('Y');
  }

  $query_config_eval_group = "(SELECT 'AT' AS `class`, `seq`, `name`, `isEval`, `isUse`, `order` FROM `evaluation` WHERE `automatic` = 'Y') UNION (SELECT 'M1' AS `class`, `seq`, '평가1' AS `name`, `value` AS `isEval`, (SELECT `value` FROM `hnp_config` WHERE `fKey` = 'EVAL_ACTIVATE' AND `sKey` = 'M1') AS `isUse`, 0 AS `order` FROM `hnp_config` WHERE `fKey` = 'EVAL_USE' AND `sKey` = 'M1') UNION (SELECT 'M2' AS `class`, `seq`, `name`, `isEval`, `isUse`, `order` FROM `evaluation` WHERE `automatic` = 'N')";
  $response = mysqli_query($db_classic, $query_config_eval_group) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    if (equals($row['class'], 'M1')) {
      $config_eval['group'][$row['class']] = $row;
    } else {
      $config_eval['group'][$row['class']][$row['seq']] = $row;
    }
  }

  $query_config_eval_item = "(SELECT 'AT_M2' AS `class`, `E`.`seq`, `E`.`value`, `E`.`refValue`, `E`.`score`, `E`.`isUse`, `E`.`order`, `EG`.`seq` AS `group_seq`, `EG`.`name` AS `group_name`, `EG`.`isUse` AS `group_isUse`, `EG`.`isEval` AS `group_isEval` FROM evalClassify AS `E` LEFT OUTER JOIN `evaluation` AS `EG` ON `EG`.`seq` = `E`.`evaluation_seq`) UNION (SELECT 'M1' AS `class`, `C`.`seq`, `C`.`name` AS `value`, NULL AS `refValue`, `C`.`score`, `C`.`isUse`, `C`.`order`, `C`.`upperSeq` AS `group_seq`, '평가항목1' AS `group_name`, `M1_IS_USE`.`value` AS `group_isUse`, `M1_IS_EVAL`.`value` AS `group_isEval` FROM category AS `C` LEFT OUTER JOIN `hnp_config` AS `M1_IS_USE` ON `M1_IS_USE`.`fKey` = 'EVAL_ACTIVATE' AND `M1_IS_USE`.`sKey` = 'M1' LEFT OUTER JOIN `hnp_config` AS `M1_IS_EVAL` ON `M1_IS_EVAL`.fKey = 'EVAL_USE' AND `M1_IS_EVAL`.sKey = 'M1')";
  $response = mysqli_query($db_classic, $query_config_eval_item) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $config_eval['item'][$row['class']][$row['seq']] = $row;
  }

  $query_config_eval_item_by_group = "(SELECT `G`.`seq` AS `group_seq`, `G`.`name` AS `group_name`, `G`.`automatic` AS `group_use`, `I`.`seq`, `I`.`order`, `I`.`value`, `I`.`refValue`, `I`.`score`, `I`.`isUse` FROM `evalClassify` AS `I` LEFT OUTER JOIN `evaluation` AS `G` ON `G`.`seq` = `I`.`evaluation_seq` WHERE `G`.`automatic` = 'Y' ORDER BY `evalClassify`.`evaluation_seq`, `evalClassify`.`refValue`+0 DESC, `evalClassify`.`order`) UNION (SELECT G.seq AS `group_seq`, `G`.`name` AS `group_name`, `G`.`automatic` AS `group_use`, I.`seq`, I.`order`, I.`value`, I.refValue, I.score, I.isUse FROM `evalClassify` AS `I` LEFT OUTER JOIN `evaluation` AS `G` ON `I`.`evaluation_seq` = `G`.`seq` WHERE `G`.`automatic` = 'N' ORDER BY `I`.`evaluation_seq`, `I`.`refValue` DESC, `I`.`order`)";
  $response = mysqli_query($db_classic, $query_config_eval_item_by_group) or die(json_encode(array('success'=>false, 'message'=>mysqli_errno($db_conn))));
  while ($row = mysqli_fetch_assoc($response)) {
    $config_eval['group_item'][$row['group_seq']][$row['seq']] = $row;
  }

  return $config_eval;
}
