<?php


$filepath = $_REQUEST['filepath'];
$userid = $_REQUEST['userid'];
$url = "https://search.solr.api.dahami.com/nsearchDev/paperDown";

$postdata = http_build_query(
    array(
        'userid' => $userid,
        'filepath' => $filepath
    )
);

$opts = array('http'=>
    array(
        'method' => 'POST',
        'content' => $postdata
    )
);

$context = stream_context_create($opts);

$result = file_get_contents($url , false, $context);
/*
$params = array (
    "userid" => $userid,
    "filepath" => $filepath
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec($ch);

curl_close ($ch);*/
header("content-type: image/png");
echo ($result);


?>


