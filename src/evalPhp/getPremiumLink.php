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
	// 토큰정보 가져오기 실패의 경우
	else {
	    // API 호출은 성공했으나 결과가 실패일때 / 메시지 전송
	    if($tokenRet) {
	       $result['errMsg'] = $tokenRet['msg'];
	    }
	    $result['success'] = false;
	}
}
echo json_encode($result);
