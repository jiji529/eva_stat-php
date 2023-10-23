<?php
include_once __DIR__ . '/common.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/columnSettingFunc.php';

$result = array();

$db = new ClassStat($premiumID);
if ($db->Error()) {
    $result['success'] = false;
    $result['notice_code'] = $db->ErrorNumber();
    $result['notice_message'] = $db->Error();
    exit(json_encode($result));
}

$columnSetting = getColumnSetting($db, 'WEB');
if (!$columnSetting) {
    $result['success'] = false;
    $result['message'] = 'config_eval error';
    exit(json_encode($result));
}

$result['success'] = true;
$columnSettingVisible = array();

foreach($columnSetting['final'] as $ck => $cv) {
    if (equals($cv['use'], 'Y')) {
        $columnSettingVisible[] = $cv;
    }
}
$result['column_setting'] = $columnSettingVisible;

echo json_encode($result);
