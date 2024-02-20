<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassTypeUtil.php';
require_once __DIR__ . "/ClassSearch.php";

$evalConfigArr = $_REQUEST['evalConfigArr'] !== null
                ? json_decode($_REQUEST['evalConfigArr'], true) : '';// json
$ClassSearch = new ClassSearch($premiumID);
$db_conn = $ClassSearch->getDBConn();

$ctu = new ClassTypeUtil($db_conn);
/* 수정 전, DB 데이터 */
$resultList = $ctu->getEvalClassifyList(false);
if (is_array($resultList) && $evalConfigArr && count($evalConfigArr) > 0) {
    /* 검증 데이터 */
    $prevList = array();
    foreach ($resultList as $ec) {
        $prevList[$ec["seq"]] = $ec;
    }
    
    /* 수정 */
    foreach ($evalConfigArr as $idx => $evalConfig) {
        if ($prevList[$evalConfig["seq"]] != null) {
            $evalConfig['order'] = $idx + 1;
            $ctu->updateEvalClassify($evalConfig);
        }
    }
    
    /* 수정 후, DB 데이터 */
    $resultList = $ctu->getEvalClassifyList(false);
}
echo json_encode($resultList);
exit;