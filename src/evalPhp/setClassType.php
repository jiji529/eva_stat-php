<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassTypeUtil.php';
require_once __DIR__ . "/ClassSearch.php";

$ClassSearch = new ClassSearch($premiumID);
$db_conn = $ClassSearch->getDBConn();

$ctu = new ClassTypeUtil($db_conn);
// 없는 대-소제목을 찾아서 evalClassify에 세팅한다.
$success = $ctu->setNewEvalClassify();

$result_final = array();
$result_final['success'] = $success;
echo json_encode($result_final);