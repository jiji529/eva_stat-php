<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/getConfigEval.php';

$result = array();

$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result['success'] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    exit(json_encode($result));
}

$config_eval = getConfigEval($db);
if (!$config_eval) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

$result['success'] = true;
$result['config_eval'] = $config_eval;

echo json_encode($result);
