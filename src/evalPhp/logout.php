<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-26
 * Time: 오후 3:10
 */
include_once __DIR__ . '/responseHeader.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/phpRedis.php';

$res = false;
if (!diffHttpUserAgent()) {
    session_unset();
    session_destroy();
    $res = true;
}

$result["success"] = $res;
echo json_encode($result);
