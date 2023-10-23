<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
include_once __DIR__ . '/lib/ExcelClasses/PHPExcel.php';

$premium_id = $_REQUEST['pid'];
include_once __DIR__ . '/common.php';
require_once __DIR__ . "/ClassSaveExcel.php";
include_once __DIR__ . '/columnSettingFunc.php';
include_once __DIR__ . '/ClassStat.php';
include_once __DIR__ . '/calcArticleValue.php';
include_once __DIR__ . '/ClassSearch.php';

//클래스 -----------------------------------------------------------------------
$ClassExcel = new ClassSaveExcel($premiumID);
$db_conn = $ClassExcel->getDBConn();
$db_stat = new ClassStat($premiumID);
$columnSetting = getColumnSetting($db_stat, 'XLS');
$columnSetting = $columnSetting['final'];

$ClassSearch = new ClassSearch($premiumID);

//1.날짜
$year = $_REQUEST['selYear'];
$month = $_REQUEST['selMon'];
$day = $_REQUEST['selDay'];
//시작,끝 날짜 지정
$sDate = $_REQUEST['sDate'];
$eDate = $_REQUEST['eDate'];
$selectedDateStand = $_REQUEST['selectedDateStand'];
//3.검색어 - 검색범위 - 제목+내용, 기자명
$keyword = trim($_REQUEST['keyword']);
$keyword_condition = $_REQUEST['keyword_condition'];
//배제어
$ex_keyword = trim($_REQUEST['ex_keyword']);
$ex_keyword_condition = trim($_REQUEST['ex_keyword_condition']);
//4-1.매체선택
$media = $_REQUEST['media'];
//4-2.평가항목1선택
$eval1Name = $_REQUEST['eval1Name'];
//4-3.평가항목2선택
$eval2Name = $_REQUEST['eval2Name'];
$news_id_list = $_REQUEST['news_id_list'];
// 정렬
$order_column = is_meaningful_rgx_or($_REQUEST['order_column'], 'news_title|media_name|news_date|news_id', 'news_date');
$order = is_meaningful_rgx_or($_REQUEST['order'], 'asc|desc', 'asc');

// $list = $ClassExcel->getListForExcel($news_id_list, $order_column, $order);
$list = $ClassExcel->getListForExcel($_REQUEST);
// $list = $ClassExcel->getListForExtract($_REQUEST);

$eval1TreeInfo = $ClassExcel->getEval1Category_tree();

//eval2항목 값 가져오기
$getEval2Cate = $ClassExcel->getEval2Cate();

/*
 * style
 */
$tempCnt = count($list);
$tmpArray = $list;
$columnCnt = 0;
foreach ($columnSetting as $column) {
	if (equals($column['use'], 'Y')) $columnCnt++;
}
$columnCnt--;
$columnCnt--;

//기사 크기가 3컬럼을 차지하므로 26이 아니라 24
if($columnCnt > 24) {
	$columnCnt = $columnCnt - 24;
	$rangeChar = 'A'.chr(64+$columnCnt);
} else {
	$rangeChar = chr(64+$columnCnt+2);
}
$rangeSeq = 8 + $tempCnt;
$range = $rangeChar . $rangeSeq;
$phpExcel = new PHPExcel();
$phpExcel->setActiveSheetIndex(0);
$sheet = $phpExcel->getActiveSheet();
$sheet->setTitle("검색결과");

//no grid
$sheet->setShowGridlines(false);

//가운데 정렬
$sheet->duplicateStyleArray(
    array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER)
    ),
    'A8:' . $range
);

//폰트 굵게
$header_font = array('font' => array('bold' => true, 'name' => '맑은 고딕'));


$sheet->duplicateStyleArray(
    $header_font, 'A1:A6'
);
$sheet->duplicateStyleArray(
    $header_font, 'A8:' . $rangeChar . '8'
);

//바탕 검정
$sheet->getStyle('A8:' . $rangeChar . '8')
    ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

//상단 검색 정보
$char = 'B';
$mergeChar = $rangeChar;
for ($seq = 1; $seq <= 6; $seq++) {
    $sheet->mergeCells($char . $seq . ":" . ($mergeChar) . $seq);
}

$defaultBorder = array(
    'style' => PHPExcel_Style_Border::BORDER_THIN,
    'color' => array('rgb' => 'C0C0C0')
);

//border
$phpExcel->getActiveSheet()
    ->getStyle('A1:'.$mergeChar.'6')
    ->applyFromArray(
        array(
            "borders" => array(
                "allborders" => $defaultBorder
            )
        )
    );
$phpExcel->getActiveSheet()
    ->getStyle('A8:' . $range)
    ->applyFromArray(
        array(
            "borders" => array(
                "allborders" => $defaultBorder
            )
        )
    );

/*
 * 상단 검색내용 표시
 */
$sheet->setCellValue('A1', "검색기간");
if ($selectedDateStand === '0') $sheet->setCellValue('B1', "(연간) {$year}년");
else if ($selectedDateStand === '1') $sheet->setCellValue('B1', "(월간) {$year}년 {$month}월");
else if ($selectedDateStand === '2') $sheet->setCellValue('B1', " {$year}년 {$month}월 {$day}일 ");
else if ($selectedDateStand === '3') $sheet->setCellValue('B1', $sDate . " ~ " . $eDate);

$sheet->setCellValue('A2', "검색어({$keyword_condition})");
$sheet->setCellValue('B2', $keyword);
$sheet->setCellValue('A3', "배제어({$ex_keyword_condition})");
$sheet->setCellValue('B3', $ex_keyword);
$sheet->setCellValue('A4', "매체");

$media = json_decode($media, true);
$media_arr = array();
$str_arr = array();
if (!empty($media)) {foreach ($media as $cateK => $cate) {
    foreach ($cate as $mediaK => $mediaVal) {
        array_push($media_arr, $mediaVal['media_name']);
    }
    $imp = implode(",", $media_arr);
    $str = "[{$cateK}] : " . $imp;
    array_push($str_arr, $str);
    $media_arr = array();
}}
$media_str = implode("  ", $str_arr);
$sheet->setCellValue('B4', $media_str);
$sheet->setCellValue('A5', "평가항목Ⅰ");
$sheet->setCellValue('B5', $eval1Name);
$sheet->setCellValue('A6', "평가항목Ⅱ");
$sheet->setCellValue('B6', $eval2Name);

$char = 'A';
/*
 * 컬럼 명 및 컬럼 크기
 */
$eval1BigCol = '';
$eval1MidCol = '';
$columnWidth = 0;

// 컬럼 크기 설정 - 10인것들은 디폴트로
foreach ($columnSetting as $key => $column) {
	$field = $column['field']; $use = equals($column['use'], 'Y');
	if ($use) {
		$columnWidth = mb_strlen($column['alias'], 'euc-kr') + 4;
		$sheet->setCellValue($char . "8", $column['alias']);
		switch ($field) {
			// case 'index':
			// 	$sheet->getColumnDimension($char)->setWidth(13);
			// 	break;
			case 'article_title':
				$sheet->duplicateStyleArray(
				array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT)),
				$char . "9:" . $char . $rangeSeq);
				$sheet->getColumnDimension($char)->setWidth(60); break;
			// case 'news_date':
			// 	$sheet->getColumnDimension($char)->setWidth(14); break;
			// case 'newsTime':
			// 	$sheet->getColumnDimension($char)->setWidth(18); break;	//2019.07.09 ADD
			// case 'media_name':
			// 	$sheet->getColumnDimension($char)->setWidth(12); break;
			// case 'reporter':
			// 	$sheet->getColumnDimension($char)->setWidth(10); break;
			// case 'value':
			// 	$sheet->getColumnDimension($char)->setWidth(12); break;
			// case 'eval1_large':
			// 	$sheet->getColumnDimension($char)->setWidth(12);
			// 	$eval1BigCol = $char; break;
			// case 'eval1_middle':
			// 	$sheet->getColumnDimension($char)->setWidth(12);
			// 	$eval1MidCol = $char; break;
			// case 'eval1_small':
			// 	$sheet->getColumnDimension($char)->setWidth(12); break;
			// case 'group':
			// 	$sheet->getColumnDimension($char)->setWidth(15); break;
			// case 'letter_cnt':
			// 	$sheet->getColumnDimension($char)->setWidth(13); break;
			// case 'size':
			// 	$mergeChar = $char;
			// 	$sheet->getColumnDimension($mergeChar)->setWidth(7);
			// 	$mergeChar++;
			// 	$sheet->getColumnDimension($mergeChar)->setWidth(7);
			// 	$mergeChar++;
			// 	$sheet->getColumnDimension($mergeChar)->setWidth(9);
			// 	$sheet->mergeCells($char . "8:" . ($mergeChar) . "8");
			// 	$char = $mergeChar; break;
			case 'ev1_big':
			case 'ev1_mid':
			case 'ev1_sml':
				$sheet->getColumnDimension($char)->setWidth(21);
				break;
			default:
				// $sheet->getColumnDimension($char)->setAutoSize(true);
				$sheet->getColumnDimension($char)->setWidth($columnWidth);
				break;
		}
		$char++;
	}
}

/*
 * 기사 넣어주기
 *
 */
$char = 'A';
$i = 0;

foreach ($columnSetting as $key => $column) {
	$seq = 9;
	$field = $column['field']; $use = equals($column['use'], 'Y');
	if ($use) {
		foreach ($list as $listKey => $listOne) {
			switch ($field) {
				case 'index':
					$sheet->setCellValue($char . $seq, ++$i); break;
				case 'page':
					$sheet->setCellValue($char . $seq, $listOne[$field]); break;
				case 'paper':
					$sheet->setCellValue($char . $seq, $listOne[$field]); break;
				case 'eval2':
					$evalCate = $listOne['eval2'];
					if ($evalCate) {
						foreach ($evalCate as $eval2Key => $eval2) {
							if ($column['seq'] == $eval2['eval2_upper_seq']) {
								$sheet
								->setCellValue($char . $seq, $eval2['eval2_name'] . '(' . $eval2['eval2_score'] . ')');
								break;
							}
						}
					}
					break;
				case 'eval1_large':
					foreach ($eval1TreeInfo as $bigKey => $bigCate) {
						if ($bigCate['seq'] == $listOne['eval1']['eval1_seq']) {
							$sheet->setCellValue($char . $seq, $bigCate['name'] . '(' .$bigCate['score'] . ')');
							break;
						}
					}
					break;
				case 'eval1_middle':
					foreach ($eval1TreeInfo as $keyF => $bigCate) {
						foreach ($bigCate['sub'] as $midKey => $midCate) {
							if ($midCate['seq'] == $listOne['eval1']['eval1_seq']) {
								$sheet->setCellValue($eval1BigCol . $seq, $bigCate['name']);
								$sheet->setCellValue($char . $seq, $midCate['name'] . '(' . $midCate['score'] . ')');
								break 2;
							}
						}
					}
					break;
				case 'eval1_small':
					foreach ($eval1TreeInfo as $keyF => $bigCate) {
						foreach ($bigCate['sub'] as $keyG => $midCate) {
							foreach ($midCate['sub'] as $keyH => $smallCate) {
								if ($smallCate['seq'] == $listOne['eval1']['eval1_seq']) {
									$sheet->setCellValue($eval1BigCol . $seq, $bigCate['name']);
									$sheet->setCellValue($eval1MidCol . $seq, $midCate['name']);
									$sheet->setCellValue($char . $seq, $smallCate['name'] . '(' . $smallCate['score'] . ')');
									break 3;
								}
							}
						}
					}
					break;
				case 'size':
					$sheet->setCellValue($char . $seq, $listOne['horizon']);
					$nextChar = $char;
					$nextChar++;
					$sheet->setCellValue($nextChar . $seq, $listOne['vertical']);
					$nextChar++;
					$sheet->setCellValue($nextChar . $seq, $listOne['size'] . "㎠");
					break;
				default:
					$sheet->setCellValue($char . $seq, $listOne[$field]);
					break;
			}
			$seq++;
		}
		if($field == 'size') $char = $nextChar;
		$char++;
	}
}

/**열너비 자동**/
for ($char = 66; $char < ord($rangeChar); $char++) {
    $sheet->getStyle(chr($char))->getAlignment()->setIndent(3);
}
$sheet->calculateColumnWidths();
//rowHeight
for ($i = 1; $i <= $rangeSeq; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(16.5);
}

/** 파일 형태 **/
$UpFileExt = ".xlsx";
$inputFileType = 'Excel2007';
$inputFileName = "검색결과" . $UpFileExt;
$inputFileName = iconv("UTF-8", "EUC-KR", $inputFileName);
/** 위에서 쓴 엑셀을 저장하고 다운로드 합니다. **/

Header("Content-type: application/octet-stream");
header("Content-Type: application/vnd.ms-excel;charset=utf-8");
header("Content-Disposition: attachment;filename=\"$inputFileName\"");
header("Cache-Control: max-age=0");
$objWriter = PHPExcel_IOFactory::createWriter($phpExcel, $inputFileType);
$objWriter->save('php://output');
exit;

?>
