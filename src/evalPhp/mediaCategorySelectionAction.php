<?php
/*
	2020-04-06 Lee J.W 매체 구분 설정 추가, 수정
	$updataCode = 'cate' =>	hnp_config  미디어 카테고리 추가, 수정
	$updataCode = 'mediaMove' =>	hnp_category  미디어 카테고리 수정
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

	$updateCode = $_REQUEST['updateCode'];
	$params = $_REQUEST['params'];

//	$updateCode = 'mediaMove';
//	$params ='{"mediaCategory":"1","mediaIds":"125"}';

	if($updateCode == 'cate'){	//카테고리 수정
		$categoryArray = json_decode($params,true);
		$categoryInsertQuery = "INSERT INTO hnp_config "
												."(fKey, sKey, value, alias) "
											."VALUES "
												."('MEDIA_CATEGORY', '{{sKey}}', '{{value}}', '{{alias}}') ";
		
		$categoryUpdateQuery = "UPDATE hnp_config SET "
													."sKey = '{{sKey}}' "
													.",alias = '{{alias}}' "
												."WHERE "
													."seq = '{{seq}}' ";
		
		$sortIdx = 0;
		foreach($categoryArray as $key => $obj){
			if(empty($obj["seq"])){
				$insertQuery = str_replace("{{sKey}}", $sortIdx++, $categoryInsertQuery);
				$insertQuery = str_replace("{{value}}", $obj["value"], $insertQuery);
				$insertQuery = str_replace("{{alias}}",  urldecode($obj["alias"]), $insertQuery);
				mysqli_query($db_conn, $insertQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));
			}else{
				$updateQuery = str_replace("{{sKey}}", $sortIdx++, $categoryUpdateQuery);
				$updateQuery = str_replace("{{alias}}",  urldecode($obj["alias"]), $updateQuery);
				$updateQuery = str_replace("{{seq}}", $obj["seq"], $updateQuery);
				mysqli_query($db_conn, $updateQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));
			}
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

		$rs = mysqli_query($db_conn, $getMediaCategoryNameQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));;
		while($rows = mysqli_fetch_assoc($rs)){
			$resultMediaCate[$rows["sort"]] = $rows;
		}
		
		mysqli_close($db_conn);
		echo json_encode($resultMediaCate);

	}elseif($updateCode == 'mediaMove'){		//매체 카테고리 이동
		
		$mediaMoveUpdateQuery = "UPDATE hnp_category SET "
														."mediaCategory = {{mediaCategory}} "
													."WHERE "
														."media_id in ({{mediaIds}}) ";
		
		$params = json_decode($params, true);
		$updateQuery = str_replace("{{mediaCategory}}", $params["mediaCategory"], $mediaMoveUpdateQuery);
		$updateQuery = str_replace("{{mediaIds}}", $params["mediaIds"], $updateQuery);
		mysqli_query($db_conn, $updateQuery) or die(array('success'=>false,'message'=> mysqli_error($db_conn) . 'query request error'));
	}

?>