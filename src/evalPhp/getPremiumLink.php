<?php

include __DIR__ . '/common.php';

$smId = $_POST['smId'];

$success = false;
if($smId == $uid) {
	$success = true;
}

$result = array();
$result['success'] = $success;
$result['UID'] = $uid;
$result['PremiumID'] = $premiumID;

if($success) {
	$tokenUrl = "https://view.scrapmaster.co.kr/admin/adminLoginTokenMake.do?smId=".$uid;
	// $tokenUrl = "https://wv.scrapmaster.co.kr/admin/adminLoginTokenMake.do?smId=".$uid;

	$ctx = stream_context_create(array(
        'http'=> array(
			'timeout' => 20, // seconds
        ),
	    'ssl'=> array(
	        "verify_peer"=>false,
	        "verify_peer_name"=>false,
	    ) 
	));
	$tokenRet = json_decode(file_get_contents($tokenUrl, false, $ctx), true);

	if ($tokenRet && $tokenRet['success']) {
		$result['tokenResult'] = $tokenRet;
		$linkUrlBase = "https://view.scrapmaster.co.kr/admin/adminLoginFromEval.do?token=";
		// $linkUrlBase = "https://wv.scrapmaster.co.kr/admin/adminLoginFromEval.do?token=";
		//	$linkUrlBase = "http://localhost/admin/adminLoginFromEval.do?token=";
		$result['tgtUrl'] = $linkUrlBase.$tokenRet['token'];
	}
}
echo json_encode($result);
