<?php
	$tokenUrl = "https://web-viewer.scrapmaster.co.kr/admin/adminLoginTokenMake.do?smId=".$uid;
	$tokenRet = file_get_contents($tokenUrl, false, $ctx);

	var_dump($tokenRet);
?>