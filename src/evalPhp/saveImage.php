<?php

$agent = $_SERVER['HTTP_USER_AGENT'];

$filepath = $_REQUEST['filepath'];
$title =$_REQUEST['title'];
$media = $_REQUEST['media'];
$date = $_REQUEST['date'];



if(!$title){
	$title='지면기사';
}

$title = str_replace(",", " ", $title);
$filename = $media.'_'.$title.'_'.$date.'.png';

if(strpos($agent,"MSIE") || strpos($agent, "Trident")) {
	$filename = iconv('utf-8', 'cp949', $filename);
}


header("Content-Type: image/png");
//header("Content-Length: " . filesize($filepath));
header("Content-Disposition: attachment; filename={$filename}" );
header("Cache-Control: cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen($filepath, 'r');
fpassthru($output);

exit;
/*echo ($output);*/


?>


