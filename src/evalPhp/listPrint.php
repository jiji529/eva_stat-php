<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/dbConn.php';
include_once __DIR__ . '/getConfigEval.php';
require_once __DIR__ . "/ClassSaveExcel.php";
include_once __DIR__ . '/calcArticleValue.php';
include_once __DIR__ . '/columnSettingFunc.php';

if (is_null($_REQUEST['news_id_list'])) {
    exit;
}
$news_id_list = (string)stripslashes($_REQUEST['news_id_list']);

$ClassExcel = new ClassSaveExcel($premiumID);
$db_excel = $ClassExcel->getDBConn();
$columnSetting = getColumnSettingClassic($db_excel, 'WEB');
$columnSetting = $columnSetting['final'];

$listRaw = $ClassExcel->getListForExcel($_REQUEST);
logs('listRaw : ' . json_encode($listRaw));
$list = array();
$tmpRow = array();
$listHead = array();

foreach ($columnSetting as $key => $column) {
  $use = equals($column['use'], 'Y');
  if ($use) {
    $listHead[] = $column['alias'];
  }
}

foreach ($listRaw as $listKey => $listOne) {
  foreach ($columnSetting as $key => $column) {
    $field = $column['field']; $use = equals($column['use'], 'Y');
    if ($use) {
      $tmpRow[$field] = $listOne[$field];
    }
  }
  $list[] = $tmpRow;
  $tmpRow = array();
}

$rtn['head'] = $listHead;
$rtn['body'] = $list;
echo json_encode($rtn);
