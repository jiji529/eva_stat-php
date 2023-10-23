<?php
/*
	2020-04-06 Lee J.W 매체 구분 설정 페이지 정보
	1. 순서
		- 보여줄 리스트
		- 미디어 카테고리 리스트
		- 매체 분류 리스트(중앙지, 경제지 등)
		- 매체 전체 리스트
*/
	include_once __DIR__ . '/common.php';
	include_once __DIR__ . '/dbConn.php';
/*
	$db_host = "192.168.1.11";
	$db_id = "root";
	$db_password = "dlgksmlchlrh";
	$db_dbname = "paper_management_hgjeon";

	//connect db
	$db_conn = mysqli_connect($db_host, $db_id, $db_password, $db_dbname);
	if (!$db_conn) {
		$error = array(
			"notice_code" => "E000",
			"notice_message" => "Fail to connect database (데이터베이스 연결에 실패했습니다.)",
					"success" => false
		);
		echo json_encode($error);
		exit;
	}
	mysqli_set_charset($db_conn, "utf8");
*/

	$requestMediaCategory = $_REQUEST["mediaCategory"];
	if(is_meaningful_rgx($requestMediaCategory, '[0-9]+(,[0-9]+)*') === false){
		$requestMediaCategoryDoneStr = '';
	} else {
		// $requestMediaCategory .= ",1,2,'a'";
		$tmpArr = explode(',', strval($requestMediaCategory));
		$requestMediaCategoryDone = array();
		foreach($tmpArr as $tmp) {
			$requestMediaCategoryDone[] = strval(intval($tmp));
		}
		$requestMediaCategoryDoneStr = " WHERE mediaCategory IN (" . implode(',', $requestMediaCategoryDone) . ")";
	}

	$listQuery = "SELECT "
							."media_id "
							.",paper_code "
							.",cpCode "
							.",sKey "
							.",media_name "
							.",paper_category "
							.",mediaCateName "
							.",mediaCategory "
						."FROM ( "
							."SELECT "
								."cType.sKey "
								.",cate.media_name "
								.",cate.media_id "
								.",cate.paper_category "
								.",cate.paper_code "
								.",child.paper_code AS cpCode "
								.",cCate.alias as mediaCateName "
								.",cCate.value as mediaCategory "
							."FROM "
								."hnp_category cate "
							."LEFT JOIN "
								."hnp_config cType "
							."ON cate.mediaType = cType.value "
							."LEFT JOIN "
								."hnp_config cCate "
							."ON cate.mediaCategory = cCate.value "
							."LEFT JOIN "
								."hnp_category child "
							."ON cate.mapCode = child.paper_code "
							."WHERE "
								."cate.mediaType > -1 "
							."AND cType.fKey = 'MEDIA_TYPE' "
							."AND cCate.fKey = 'MEDIA_CATEGORY' "
							."ORDER BY "
								."(case "
									."when cate.kind_serial = '' then '0' "
									."else '1' "
								."end) desc, "
								."cate.kind_serial asc, "
								."cate.media_name asc "
						.") a ";
	$listWhereQuery = $listQuery . $requestMediaCategoryDoneStr;
	//뿌려줄 메인 리스트
	$rs = mysqli_query($db_conn,$listWhereQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
	while($rows = mysqli_fetch_assoc($rs)){
		$media_id[] = $rows["media_id"];
		$paper_code[] = $rows["paper_code"];
		$cpCode[] = $rows["cpCode"];
		$sKey[] = $rows["sKey"];
		$media_name[] = $rows["media_name"].iconv('EUC-KR', 'UTF-8', ($rows["sKey"] == 'ONLINE' ? "(온라인)" : ($rows["sKey"] == 'PAPER' ? "(지면)" : "(사용자입력)")));
		$paper_category[] = $rows["paper_category"];
		$mediaCateName[] = $rows["mediaCateName"];
		$mediaCategory[] = $rows["mediaCategory"];
		$mapCode[] = array();	//자신 외의 같은 온라인, 지면이 있으면 모아두기 위해서
	}
	//연관된 매체들을 묶어 준다.
	for($i = 0; $i < sizeof($paper_code); $i++){
		$mapCode = childAdd($paper_code, $cpCode, $i, $mapCode);
	}

	function childAdd($paperCodeArr = array(), $cpCodeArr = array(), $i = 0, $mapCodeArr = array()){
		if(!empty($cpCodeArr[$i])){
			$idx = array_search($cpCodeArr[$i],$paperCodeArr);
			if($idx === false){$idx = "is-not";}	// $idx  === false의 경우 array[false] => array[0] 으로 인식한다.
			$mapCodeArr[$i][] = $cpCodeArr[$i];	// 자신의 것에 연관된 애의 것을 넣어준다.
			$mapCodeArr[$i][] = $cpCodeArr[$idx];	// 자신의 것에 연관된 애의 연관을 넣어준다.
			$mapCodeArr[$idx][] = $paperCodeArr[$i];	//연관된 애의 것에 나의 것을 넣어준다.
			$mapCodeArr[$i] = array_merge($mapCodeArr[$i], $mapCodeArr[$idx]);	//합쳐준다.
			$mapCodeArr[$i] = array_unique($mapCodeArr[$i]);	//중복 제거해준다.
			$mapCodeArr[$i] = array_filter($mapCodeArr[$i]);	//빈값 제거
			$mapCodeArr[$idx] = $mapCodeArr[$i];
			foreach($mapCodeArr[$i] as $key => $code){
				$x = array_search($code,$paperCodeArr);
				if($x === false){$x = "is-not";}
				$mapCodeArr[$x] = $mapCodeArr[$i];
			}
			if($idx == "is-not"){	// 다른 array와 겹치면 안됨으로 일회성으로 쓰고 버려준다.
				unset($mapCodeArr["is-not"]);
				return $mapCodeArr;
			}

			if($paperCodeArr[$i] != $cpCodeArr[$idx]){
				childAdd($paperCodeArr, $cpCodeArr, $idx, $mapCodeArr);
			}
		}
		return $mapCodeArr;
	}
	//매체 분류 목록을 만든다.
	$getMediaCategoryNameQuery = "SELECT "
															."seq "
															.",sKey as sort"
															.",alias "
															.",value "
															.",IFNULL(cnt, 0) as cnt "
														."FROM "
															."hnp_config conf "
														."LEFT JOIN "
															."(select mediaCategory,count(mediaCategory) as cnt from hnp_category group by mediaCategory) cate "
														."ON conf.value = cate.mediaCategory "
														."WHERE "
															."fKey = 'MEDIA_CATEGORY' "
														."ORDER BY "
															."sKey asc ";
	//매체 구분 정보를 가져온다.
	$rs = mysqli_query($db_conn, $getMediaCategoryNameQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
	while($rows = mysqli_fetch_assoc($rs)){
		$resultMediaCate[$rows["sort"]] = $rows;
	}

	//전달할 리스트를 정리한다.
	for($i = 0; $i < sizeof($paper_code); $i++){
		$sortMapCode = array();
		$array1 = array_diff($mapCode[$i], array($paper_code[$i]));
		//배열 인덱스 재정렬
		foreach($array1 as $key=>$val)
		{
			$sortMapCode[] = $val;
		}
		$row = array(
				'mediaId' => $media_id[$i]
				,'paperCode' => $paper_code[$i]
				,'paperType' => $sKey[$i]
				,'mediaName' => $media_name[$i]
				,'paperCategory' => $paper_category[$i]
				,'mapCode' => $sortMapCode		//자신의 paperCode는 삭제해준다.
				,'checked' => false
				,'mediaCateName' => $mediaCateName[$i] // TEST
				,'mediaCategory' => $mediaCategory[$i] // TEST
			);
		$resultList[] = $row;
	}
	//카테고리를 만든다. sql 정렬이 잘되어 있어서 사용 가능한 방법
	$pcDump = array_unique($paper_category);
	$pcDumpKeys = array_keys($pcDump);
	for($i=0; $i<sizeof($pcDumpKeys); $i++){
		$start = $pcDumpKeys[$i];
		$end = (empty($pcDumpKeys[$i+1]) ? sizeof($paper_code) : $pcDumpKeys[$i+1]);
		$gap = (3-(($end - $start)%3))%3;
		$row = array(
				'start' => $start
				,'name' => $pcDump[$pcDumpKeys[$i]]
				,'end' => $end
				,'gap' => $gap
			);
		$resultPaperCategoryList[] = $row;
	}

	$rs = mysqli_query($db_conn,$listQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
	while($rows = mysqli_fetch_assoc($rs)){
		$row = array(
				'mediaId' => $rows["media_id"]
				,'paperCode' => $rows["paper_code"]
				,'paperType' => $rows["sKey"]
				,'mediaName' => $rows["media_name"].iconv('EUC-KR', 'UTF-8', ($rows["sKey"] == 'ONLINE' ? "(온라인)" : ($rows["sKey"] == 'PAPER' ? "(지면)" : "(사용자입력)")))
				,'paperCategory' => $rows["paper_category"]
				,'checked' => false
				,'mediaCateName' => $rows['mediaCateName']
				,'mediaCategory' => $rows['mediaCategory']
			);
		$allList[] = $row;
	}

	$resultArray = array(
					'list' => $resultList
					,'allList' => $allList
					,'paperCategorys' => $resultPaperCategoryList
					,'mediaCategorys' =>  $resultMediaCate
					,'success' => true
			);

	mysqli_close($db_conn);
	echo json_encode($resultArray);
?>
