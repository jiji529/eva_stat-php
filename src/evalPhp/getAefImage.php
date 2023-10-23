<?php
include_once __DIR__ . '/common.php';

$url = $_REQUEST['url'];
$contents =(file_get_contents($url));
$data = iconv('Windows-1252', 'UTF-8', urldecode($contents));
//var_dump($data);
$sub = explode("<image>", $data);

//var_dump($sub);

$cut = explode("¢Ì", $sub[1]);

$getImage = $cut[2];
//var_dump($cut);

echo $getImage;

?>