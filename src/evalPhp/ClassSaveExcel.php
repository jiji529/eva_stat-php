<?php
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
include_once __DIR__ . '/lib/mysql.class.php';
include_once __DIR__ . '/ClassPage.php';
include_once __DIR__ . '/getConfigEval.php';
include_once __DIR__ . '/calcArticleValue.php';

class ClassSaveExcel
{

  private $premiumID = "";
  // SET THESE VALUES TO MATCH YOUR DATA CONNECTION
  private $db_host = "211.233.16.3"; // server name
  private $db_id = "root";          // user name
  private $db_password = "dlgksmlchlrh";          // password
  private $db_dbname = "";          // database name
  private $db_charset = "utf8";          // optional character set (i.e. utf8)

  public $mysql_link = 0;       // mysql link resource

  public function __construct($premiumID)
  {
    if (!$premiumID)
      return;
    $this->premiumID = $premiumID;
    $this->db_dbname = "paper_management_" . $premiumID;
  }

  function getDBConn()
  {
    //connect db
    $this->mysql_link = mysqli_connect($this->db_host, $this->db_id, $this->db_password, $this->db_dbname);
    if (!$this->mysql_link) {
      $error = array(
          "error_code" => "E000",
          "error_message" => "Fail to connect database (데이터베이스 연결에 실패했습니다.)"
      );
      echo json_encode($error);
      exit;
    }
    mysqli_query($this->mysql_link, "SET CHARACTER SET '{$this->db_charset}'");
    return $this->mysql_link;
  }

  function getSize($categoryOutput, $articleArea, $contents)
  {
    $articleArea = explode("|", $articleArea);
    if($articleArea[($categoryOutput-1)] == 0) {
      $letter_cnt = mb_strlen($contents);
      $size_val = $letter_cnt * 0.125;
      $size =  $size_val."㎠";
    } else {
      $size =  $articleArea[($categoryOutput-1)]."㎠";
    }
    return $size;
  }

  function getLocation($article_serial, $part_name, $case_name)
  {
    if ($part_name === '' && $case_name === '') {
      $location = (int)substr($article_serial, -5, 2);
      if ($location > 50 || $location <= 0) {
        $location = '기타';
      }
      return mb_convert_encoding($location, "utf-8", "euc-kr");
    } else {
      return null;
    }
  }

  function getPaperLocation($article_serial, $part_name, $case_name){
    if ($part_name === '' && $case_name === '') {
      $location = substr($article_serial, -6, 1);
      return mb_convert_encoding($location, "utf-8", "euc-kr");
    } else {
      return null;
    }
  }

  function size_horizon($articleArea)
  {
    $articleArea = explode("|", $articleArea);
    $horizon = $articleArea[1];
    $horizon = explode("x", $horizon);
    if ($horizon[0] <= 0) {
      $horizon[0] = null;
    }
    return $horizon[0];
  }

  function size_vertical($articleArea)
  {
    $articleArea = explode("|", $articleArea);
    $horizon = $articleArea[1];
    $horizon = explode("x", $horizon);
    if ($horizon[1] <= 0) {
      $horizon[1] = null;
    }
    return $horizon[1];
  }

  function getKind($case_name, $part_name) {
    if($case_name === '' && $part_name === '') {
      return mb_convert_encoding("지면", "utf-8", "euc-kr");
    } else {
      return mb_convert_encoding("온라인", "utf-8", "euc-kr");
    }
  }

  function getLetterCnt($contents) {
    $contents_space_remove = str_replace(' ','',$contents);
    $letterCnt = mb_strlen($contents_space_remove, 'UTF-8');
    return $letterCnt;
  }

  function getQueryForExtract($request) {
    $dateStand = $request['selectedDateStand'] ? $request['selectedDateStand'] : '0';
    $year = $request['selYear'] ?  $request['selYear']  : date('Y');
    $month = $request['selMon'] ? : null;
    $day = $request['selDay'] ? $request['selDay'] : null;
    //시작,끝 날짜 지정
    $toYear = date('Y');
    $sDate = $request['sDate'] ? date('Y-m-d', strtotime($request['sDate'])) : date('Y-m-d', mktime(0, 0, 0, 1, 1,$toYear));
    $eDate = $request['eDate'] ? date('Y-m-d', strtotime($request['eDate'])) : date('Y-m-d', mktime(0, 0, 0, 12, 31, $toYear));
    //2.사용자 설정 뷰어그룹
    $news_me = trim($request['selNewsMe'], ',');
    $news_me = str_replace(',,', ',', $news_me);
    $news_me = is_meaningful_rgx($news_me, '[0-9]+(,[0-9]+)*');
    //3.검색어 - 검색범위 - 제목+내용, 기자명
    $search_range = $request['search_range'] ? $request['search_range'] : '0' ;
    $keyword = trim($request['keyword']);
    $keyword_condition = $request['keyword_condition'] ? $request['keyword_condition']  : 'or';
    //배제어
    $ex_keyword = trim($request['ex_keyword']);
    $ex_keyword_condition = $request['ex_keyword_condition'] ? $request['ex_keyword_condition'] : 'or';

    $media = trim($request['media'], ',');
    $media = str_replace(',,', ',', $media);
    $media = is_meaningful_rgx($media, '[0-9]+(,[0-9]+)*');
    //4-1.매체구분
    $category_id = $request['category_id'];
    //4-4.자동평가항목선택
    $eval0 = $request['eval0'] ? stripcslashes($request['eval0']) : '';
    //4-2.평가항목1선택
    $eval1 = $request['eval1'] ? stripcslashes($request['eval1']) : '';
    //4-3.평가항목2선택
    $eval2 = $request['eval2'] ? stripcslashes($request['eval2']) : '';
    $eval0_condition = $request['eval0_condition'] ? stripcslashes($request['eval0_condition']) : 'OR';
    $eval2_condition = $request['eval2_condition'] ? stripcslashes($request['eval2_condition']) : 'OR';
    //5.기사감추기, 평가제외기사, 비공개스크랩
    $hUse = ($request['hUse'] === 'true') ? 'true' : 'false';
    $news_comment = ($request['news_comment'] === 'true') ? 'true' : 'false';
    $sbUse = ($request['sbUse'] === 'true') ? 'true' : 'false';

    //6.정렬 - 0:제목 ,1:매체 ,2:날짜, 3:매체순서
    $order_column = is_meaningful_rgx_or($request['order_column'], 'news_title|media_name|news_date|scrap_date|news_id', 'news_date');
    $order = is_meaningful_rgx_or($request['order'], 'asc|desc', 'asc');

    //7.표시제외기사
    $exclude_ids = trim($request['exclude_ids'], ',');
    $exclude_ids = is_meaningful_rgx($exclude_ids, '[1-9]+(,[1-9][0-9]*)*');

    // 인쇄 - 기사번호 지정

    $remove_news_serial = $_REQUEST['remove_news_serial'];

    if (!$year && (!$sDate || !$eDate)) return false;

    $query = '';
    $query1 = 'SELECT distinct ';
    $field = "`hnews`.`news_id`, `hnews`.`media_id`, `hcate`.`media_name`, `hcate`.`mediaCategory` AS `category_id`, `hcate`.`mediaCategory` AS `media_category`, `hcate`.`mediaType` AS `media_type`, `SB`.`scrapDate` AS `scrap_date`, `hnews`.`news_title` AS `article_title`, `hnews`.`news_reporter` AS `article_reporter`, `hnews`.`news_group`, `hnews`.`coordinate`, `hnews`.`news_file_name`, `hnews`.`guid`, `hnews`.`news_me`, if(`hnews`.`articleArea2` = '', `hnews`.`articleArea`, `hnews`.`articleArea2`) as `article_area` , `hnews`.`news_contents` AS `article_contents` , `hnews`.`newsTime` AS `article_datetime` , `hnews`.`part_name` , `hnews`.`case_name` , `hnews`.`article_serial` AS `article_serial` , `hnews`.`category_seq` AS `eval1_seq` , `hnews`.`news_comment`, `hcate`.`categoryOutput`, `hnews`.`articleSequence`, `hnews`.`etc1` AS `highLight`, `hnews`.`etc2` AS `highLightWord`, `reporterGroup`.`evalClassify_seq` AS `reporterAuto`, `mediaGroup`.`evalClassify_seq` AS `mediaAuto`, GROUP_CONCAT(`newsEval`.`evalClassify_seq`) AS `eval2_seqs`, `hcate`.`evalValue` AS `media_value` , hnews.isUse as hUse, hnews.orgLink, hnews.refLink ";
    $table_string = " FROM `hnp_news` AS `hnews` LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id` LEFT JOIN `reporterGroup` ON `reporterGroup`.`hnp_category_media_id` = `hnews`.`media_id` AND `hnews`.`news_reporter` LIKE CONCAT('%',  `reporterGroup`.`reporterName`, '%') AND `reporterGroup`.`isUse` = 'Y' LEFT JOIN `mediaGroup` ON `mediaGroup`.`hnp_category_media_id` = `hnews`.`media_id` LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id` LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq` LEFT JOIN `scrapBook` AS `SB` ON `SB`.`no` = `hnews`.`scrapBookNo`";
    $query2 = " WHERE hnews.articleSequence = '0' AND hcate.media_name NOT IN ('', '이미지', '글상자', '대제목', '소제목', '불확실') AND hcate.mediaType != -98 AND hcate.isUse = '0' AND `hnews`.`news_me` IN (SELECT `LVALUE` FROM `DISPLAY_INFO` WHERE `LTYPE` = 1 AND `BUESSURE` = 1)";

    $searchInfo = array();
    //1.날짜
    //년/월.일
    $subQueryDates = '';
    switch($dateStand) {
      case "0":
        // $query2 .= " AND substr(hnews.news_date,1,4)='{$year}' ";
        $subQueryDates = " AND substr(hnews.news_date,1,4)='{$year}' ";
        $searchInfo['date'] = "연간:" . $year; break;
      case "1":
        $month = $month < 10 ? '0'.$month : $month;
        $day = $day < 10 ? '0'.$day : $day;
        // $query2 .= " AND substr(hnews.news_date,1,7)='{$year}-{$month}' ";
        $subQueryDates = " AND substr(hnews.news_date,1,7)='{$year}-{$month}' ";
        $searchInfo['date'] = "월간:" . $year . "-" . $month; break;
      case "2":
        $month = $month < 10 ? '0'.$month : $month;
        $day = $day < 10 ? '0'.$day : $day;
        // $query2 .= " AND hnews.news_date = '{$year}-{$month}-{$day}' ";
        $subQueryDates = " AND hnews.news_date = '{$year}-{$month}-{$day}' ";
        break;
      case "3":
        // $query2 .= " AND hnews.news_date BETWEEN DATE('{$sDate}') AND DATE('{$eDate}') ";
        $subQueryDates = " AND hnews.news_date BETWEEN DATE('{$sDate}') AND DATE('{$eDate}') ";
        $searchInfo['date'] = "지정:" . $sDate . "~" . $eDate; break;
      default :
        // $query2 .= " AND substr(hnews.news_date,1,4)='{$year}' ";
        $subQueryDates = " AND substr(hnews.news_date,1,4)='{$year}' ";
        $searchInfo['date'] = "연간:" . $year; break;
    }
    $query2 .= $subQueryDates;

    //2.사용자 뷰어 그룹 선택
    if ($news_me === false) { // 입력형식 틀림(빈경우포함) -
      $query2 .= " AND `hnews`.`news_me` IN (-1)";
      $searchInfo['news_me'] = "][-1";
    } else {
      $query2 .= " AND `hnews`.`news_me` IN ($news_me)";
      $searchInfo['news_me'] = "][" . $news_me;
    }

    //3.키워드 검색
    //3-1.검색어
    if ($keyword != null) {
      $keyword_arr = explode(' ', $keyword);
      $length = count($keyword_arr);
      $query2 .= " AND ( ";
      foreach ($keyword_arr as $key => $keyword_one) {
        if ($key != ($length - 1)) {
          if ($search_range === '0') {
            $query2 .= " ( hnews.news_title like '%{$keyword_one}%' OR hnews.news_contents like '%{$keyword_one}%'  ) {$keyword_condition}";
          } else if ($search_range === '1') {
            $query2 .= " ( hnews.news_reporter like '%{$keyword_one}%'  ) {$keyword_condition}";
          }
        } else if ($key == ($length - 1)) {
          //마지막 요소 일 때
          if ($search_range === '0') {
            //마지막 요소 일 때
            $query2 .= " ( hnews.news_title like '%{$keyword_one}%' OR hnews.news_contents like '%{$keyword_one}%' ) ) ";
          } else if ($search_range === '1') {
            echo 'search_range 1';
            $query2 .= " ( hnews.news_reporter like '%{$keyword_one}%' ) )";
          }
        }
        $i++;
      }
      $searchInfo['keyword'] = "][" . $keyword;
      $searchInfo['keyword_co'] = "][" . $keyword_condition;
    } else {
      $searchInfo['keyword'] = "][";
      $searchInfo['keyword_co'] = "][";
    }

    //3-2.배제어
    if ($ex_keyword != null) {
      $ex_keyword_arr = explode(' ', $ex_keyword);
      $length = count($ex_keyword_arr);
      $query2 .= " AND ( ";
      foreach ($ex_keyword_arr as $key => $ex_keyword_one) {
        if ($key != ($length - 1)) {
          //마지막 요소가 아닐때
          if ($search_range === '0') {
            $query2 .= " !( hnews.news_title like '%{$ex_keyword_one}%' OR hnews.news_contents like '%{$ex_keyword_one}%'  ) {$ex_keyword_condition} ";
          } else if ($search_range === '1') {
            $query2 .= " !( hnews.news_reporter like '%{$ex_keyword_one}%'  ) {$ex_keyword_condition} ";
          }
        } else if ($key == ($length - 1)) {
          if ($search_range === '0') {
            //마지막 요소 일 때
            $query2 .= "  !( hnews.news_title like '%{$ex_keyword_one}%' OR hnews.news_contents like '%{$ex_keyword_one}%' ) )  ";
          } else if ($search_range === '1') {
            $query2 .= "  !( hnews.news_reporter like '%{$ex_keyword_one}%' ) ) ";
          }
        }
      }
      $searchInfo['ex_keyword'] = "][" . $ex_keyword;
      $searchInfo['ex_keyword_co'] = "][" . $ex_keyword_condition;
    } else {
      $searchInfo['ex_keyword'] = "][";
      $searchInfo['ex_keyword_co'] = "][";
    }


    //4-1.세부설정 - 매체선택 (배열로 받음)
    // if ($media != null) {
    //     $media_arr = array();
    //     foreach ($media as $cateK => $cate) {
    //         foreach ($cate as $mediaK => $mediaVal) {
    //             array_push($media_arr, $mediaVal['media_id']);
    //         }
    //     }
    //     $query2 .= "AND hnews.media_id IN ('";
    //     $media_string = implode("','", $media_arr);
    //     $query2 .= "{$media_string}')";
    //     $searchInfo['media'] = "][매체:-" . implode("-", $media_arr);
    // } else {
    //     $searchInfo['media'] = "][매체:";
    // }
    if ($media === false) {
      $query2 .= " AND `hnews`.`media_id` IN (-1)";
      $searchInfo['media'] = "][매체:-1";
    } else if ($media !== '0') {
      $query2 .= " AND `hnews`.`media_id` IN ($media)";
      $searchInfo['media'] = "][매체:" . $media;
    } else {
      $searchInfo['media'] = "][매체:";
    }

    //4-1.매체구분
    if ($category_id) {
      if (!is_array($category_id)) {
        $cate_id_arr = explode(",", $category_id);
      } else {
        $cate_id_arr = $category_id;
      }

      foreach ($cate_id_arr as $key => $one) { //cate_0, cate_1 , ... 형태로 넘어옴
        $cate_id_arr[$key] = substr($cate_id_arr[$key], 5, 1);
      }
      $query2 .= " AND hnews.category_id IN ('";
      $cate_id_string = implode("','", $cate_id_arr);
      $query2 .= "{$cate_id_string}')";
    }

    //4-2.세부설정 - 평가항목1
    if ($eval1 != null) {
      if (!is_array($eval1)) {
        $eval1_arr = explode(",", $eval1);
      } else {
        $eval1_arr = $eval1;
      }
      $eval1_str = implode(",", $eval1_arr);

      $query2 .= " AND hnews.category_seq IN ({$eval1_str})  ";
      //echo $query2;
      $searchInfo['eval1'] = "][평가1:" . implode("-", $eval1_arr);
    } else {
      $searchInfo['eval1'] = "][평가1:";
    }

    /*
     * 자동평가&평가2 사전
     */
    $eval0Option = ($eval0_condition === 'OR') ? 2 : 1;
    $eval2Option = ($eval2_condition === 'OR') ? 2 : 1;
    $evalOptions = $eval2Option * $eval0Option;
    $eval0Arr = $eval0 ? explode(',', $eval0) : array();
    $eval2Arr = $eval2 ? explode(',', $eval2) : array();
    $dictSeqs = '0';
    if (count($eval0Arr) > 0 || count($eval2Arr) > 0) {
      $dictQuery = "SELECT `news_id` FROM (SELECT `hnews`.`news_id`, CONCAT('+', GROUP_CONCAT(`eval2`.`evalClassify_seq` SEPARATOR '+'), '+') AS `eval2_seqs` FROM `hnp_news` AS `hnews` LEFT OUTER JOIN `newsEval` AS `eval2` ON `eval2`.`hnp_news_seq` = `hnews`.`news_id` WHERE 1=1 ";
      if ($subQueryDates) {
        $dictQuery .= $subQueryDates;
      }
      $dictQuery .= " GROUP BY `news_id`) T WHERE 1=1 ";
      if ($eval0Option === 1) { // 자동 AND
        foreach($eval0Arr as $ev0) {
          $dictQuery .= "AND `T`.`eval2_seqs` LIKE '%+" . intval($ev0) . "+%' ";
        }
      } else if (count($eval0Arr) > 0) { // 자동 OR
        $dictQuery .= "AND (";
        foreach($eval0Arr as $ev0) {
          $dictQuery .= "`T`.`eval2_seqs` LIKE '%+" . intval($ev0) . "+%' OR ";
        } $dictQuery .= "`T`.`eval2_seqs` = '0') ";
      }
      if ($eval2Option === 1) { // 평가2 AND
        foreach($eval2Arr as $ev2) {
          $dictQuery .= "AND `T`.`eval2_seqs` LIKE '%+" . intval($ev2) . "+%' ";
        }
      } else if (count($eval2Arr) > 0) { // 평가2 OR
        $dictQuery .= "AND (";
          foreach($eval2Arr as $ev2) {
            $dictQuery .= "`T`.`eval2_seqs` LIKE '%+" . intval($ev2) . "+%' OR ";
          } $dictQuery .= "`T`.`eval2_seqs` = '0')";
      }
      $query2 .= " AND `hnews`.`news_id` IN ({$dictQuery}) ";
    }

    //5.기사감추기포함,평가제외기사, 비공개스크랩(1:비포함, 0또는null:포함)
    if ($hUse === 'false') {
      $query2 .= " AND hnews.isUse != 1 ";
      $searchInfo['isUse'] = "][기사숨기기제외";
    } else {
      $searchInfo['isUse'] = "][기사숨기기포함";
    }
    if ($news_comment === 'false') {
      $query2 .= " AND hnews.news_comment != 1 ";
      $searchInfo['comment'] = "][평가제외 제외";
    } else {
      $searchInfo['comment'] = "][평가제외 포함";
    }
    if ($sbUse === 'false') {
      $query2 .= " AND `SB`.isUse != 0  ";
    }


    if ($remove_news_serial != null) {
      if (!is_array($remove_news_serial)) {
        $remove_news_serial_arr = explode(",", $remove_news_serial);
      } else {
        $remove_news_serial_arr = $remove_news_serial;
      }
      $remove_news_serial_str = implode(",", $remove_news_serial_arr);

      $query2 .= " AND hnews.article_serial NOT IN ({$remove_news_serial_str}) ";
      $searchInfo['remove']= "][있음";
    } else {
      $searchInfo['remove']= "][없음";
    }

    //7.표시제외기사
    if ($exclude_ids !== false) {
      $query2 .= " AND `hnews`.`news_id` NOT IN (${exclude_ids})";
    }

    $query2 .= ' GROUP BY `hnews`.`news_id` ';
    //6.정렬 - 0:제목 ,1:매체 ,2:날짜, 3:등록순서
    $query2 .= " ORDER BY ";
    if ($order_column === 'none') {
        $query2 .= " hnews.news_date desc, hnews.news_id desc ";
    } else if ($order_column == "news_title") {
        $query2 .= " hnews.news_title {$order} , hnews.news_date {$order}, hnews.news_id {$order} ";
    } else if ($order_column == "media_name") {
        $query2 .= " hnews.media_name {$order} , hnews.news_date {$order}, hnews.news_id {$order} ";
    } else if ($order_column == "news_date") {
        $query2 .= " hnews.newsTime {$order} , hnews.news_id {$order} ";
    } else if (equals($order_column, 'scrap_date')) {
        $query2 .= " `SB`.`scrapDate` {$order} , hnews.news_date {$order} , hnews.news_id {$order} ";
    } else if ($order_column == "news_id") {
        $query2 .= " hnews.news_id {$order} , hnews.news_date {$order} ";
    }

    logs('request : ' . json_encode($request));
    logs('exclude_ids : ' . $exclude_ids);
    logs('query2 : ' . $query2);
    //쿼리 합침
    $query = "{$query1}{$field}{$table_string}{$query2}";

    return $query;
  }

  // function getListForExcel($news_id_list, $order_column, $order)
  function getListForExcel($request)
  {  // IF(`hnews`.`newsTime` = '0000-00-00 00:00:00', NULL, `hnews`.`newsTime`) AS `newsTime`
    // search_API_wip.php와 결과물 맞추기 위하여 query 변경

    // $col = "`hnews`.`news_id`
    //   , `hnews`.`news_title`
    //   , `hcate`.`media_name`
    //   , `hnews`.`news_reporter`
    //   , `hnews`.`news_date`
    //   , `hnews`.`newsTime`
    //   , `hnews`.`media_id`
    //   , `hnews`.`article_serial`
    //   , `hnews`.`coordinate`
    //   , `hnews`.`news_contents`
    //   , `hcate`.`mediaCategory`
    //   , `hcate`.`mediaType`
    //   , `hcate`.`evalValue` AS `media_value`
    //   , `hnews`.`part_name`
    //   , `hnews`.`case_name`";
    // // `pmart`.`unitCost` AS `unit_cost`,`pmart`.`point` AS `point`
    // // `hnews`.`news_group`, `hcate`.`point` AS `media_point`
    // $col .= ", IF(`hnews`.`articleArea2` = '', `hnews`.`articleArea`, `hnews`.`articleArea2`) AS `article_area`, `hnews`.`news_comment`, `hcate`.`categoryOutput`, `eval1`.`seq` AS `eval1_seq`, `eval2`.`seq` AS `eval2_seq`";
    //
    // $query = "SELECT DISTINCT {$col} FROM `hnp_news` AS `hnews` ";
    // $query .= "LEFT JOIN `category` AS `eval1` ON `eval1`.`seq` = `hnews`.`category_seq` ";
    // $query .= "LEFT JOIN `newsEval` ON `newsEval`.`hnp_news_seq` = `hnews`.`news_id` ";
    // $query .= "LEFT JOIN `evalClassify` AS `eval2` ON `eval2`.`seq` = `newsEval`.`evalClassify_seq` ";
    // $query .= "LEFT JOIN `evaluation`  ON `evaluation`.`seq` = `eval2`.`evaluation_seq` ";
    // $query .= "LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id` ";
    // // $query .= "LEFT JOIN `pm_articleValue` AS `pmart`  ON `hcate`.`category_id` = `pmart`.`category_id` ";
    // $query .= "LEFT JOIN `scrapBook`  as `SB`  ON `SB`.`no` = `hnews`.`scrapBookNo` ";
    // //2020-02-10 Lee J.W `hnews`.`articleSequence` in ('0','-1') 로 수정 기존  = '0'
    // $query .= "WHERE `hnews`.`articleSequence` in ('0','-1') AND `hnews`.`media_name` != '글상자' AND `hnews`.`media_name` != '이미지' AND `hnews`.`media_name` != '' ";
    // $query .= "AND `hcate`.`isUse` = '0' AND `SB`.`isUse` = '1'";  /* AND `evaluation`.isUse = 'Y' AND eval2.isUse = 'Y' *//* remove 2019-04-26 jw*/
    // $query .= "AND `news_id` IN (" . $news_id_list . ") ";
    //
    // $query .= "ORDER BY ";
    // if (equals($order_column, 'none')) {
    //   $query .= "`hnews`.`news_id` ${order_type}, `hnews`.`news_id` desc";
    // } else if (equals($order_column, 'news_title')) {
    //   $query .= "`hnews`.`news_title` {$order}, `hnews`.`news_date` {$order}, `hnews`.`news_id` {$order}";
    // } else if (equals($order_column, 'media_name')) {
    //   $query .= "`hnews`.`media_name` {$order}, `hnews`.`news_date` {$order}, `hnews`.`news_id` {$order}";
    // } else if (equals($order_column, 'news_date')) {
    //   $query .= "`hnews`.`newsTime` {$order}, `hnews`.`news_id` {$order}";
    // } else if (equals($order_column, 'scrap_date')) {
    //   $query .= "`SB`.`scrapDate` {$order}, `hnews`.`news_date` {$order}, `hnews`.`news_id` {$order} ";
    // } else if (equals($order_column, 'news_id')) {
    //   $query .= "`hnews`.`news_id` {$order}, `hnews`.`news_date` {$order}";
    // }
    $query = $this->getQueryForExtract($request);
    logs('query : ' . $query); // DEV

    $db_conn = $this->getDBConn();

    $result = mysqli_query($db_conn, $query);
    $list = array();
    $i = 0;

    $ClassPage = new ClassPage();
    while ($row = mysqli_fetch_assoc($result)) {
      $news_id = $row['news_id'];
      $eval2_score = (float)$row['eval2_score'] != 0 ? (float)$row['eval2_score'] : 1;
      if (!$list[$news_id]) {
        // $categoryOutput = (int)$row['categoryOutput']; // 기사 가치 산출 기준
        // $articleArea = 1; // 면적 구하기 혹은 너비 값
        // if ($row['articleArea']) {
        //   $articleArea = explode("|", $row['articleArea']);
        //   if (count($articleArea) > 1) {
        //     if ($categoryOutput >= 1) {
        //       $articleArea = (float)$articleArea[0];
        //     } else {
        //       $articleArea = explode("x", $articleArea[1]);
        //       $articleArea = (float)$articleArea[0] * (float)$articleArea[1];
        //     }
        //     /*if ($categoryOutput === 1 || $categoryOutput === 2) {
        //       $articleArea = (float)$articleArea[0];
        //     } else if ($categoryOutput === 3) {
        //       $articleArea = (float)$articleArea[2];
        //     } else if ($categoryOutput === 4) {
        //       $articleArea = (float)$articleArea[3];
        //     }*/
        //   } else {
        //     $articleArea = 1;
        //   }
        //   if (!$articleArea) {
        //     $articleArea = 1;
        //   }
        // }
        // $news_comment = (int)$row['news_comment']; //평가 안함
        // $unit_cost = (float)$row['unit_cost'] > 0 ? (float)$row['unit_cost'] : 1; //광고 단가
        //$media_point = (float)$row['media_point'] > 0 ? (float)$row['media_point'] : 1; // 매체 가치
        // $media_point = 1;
        // $eval1_score = (float)$row['eval1_score'] != 0 ? (float)$row['eval1_score'] : 1; // 평가1 설정 가치
        // $eval_score = $articleArea * $media_point * $unit_cost; // 면적 *매체 가지 * 광고 단가
        // $eval_weight = $row['point'];
        // 가중치 설정값
        // switch ($eval_weight) {
        //   case "1":
        //     $eval_score *= $eval1_score; //평가 1
        //     break;
        //   case "2":
        //     $eval_score *= $eval2_score; //평가 2
        //     break;
        //   case "3":
        //     $eval_score *= $eval1_score * $eval2_score; // 평가1 * 평가2
        //     break;
        // }
        // if ($news_comment) {
        //   $eval_score = 0;  //평가 안함 가치값 0
        // }
        // if ($row['newsTime']) {
        //   $article_date_tmp = (string)$row['newsTime'];
        //   $pattern_datetime = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
        //   if (preg_match($pattern_datetime, $article_date_tmp, $matches) === 1) {
        //     $article_date_tmp_arr = explode(' ', $article_date_tmp);
        //     $article_date = $article_date_tmp_arr[0];
        //     if (equals($article_date, '0000-00-00')) $article_date = '-';
        //   } else {
        //     $article_date = '-';
        //   }
        // } else {
        //   $article_date = '-';
        // }
        $page_size_pixel = $ClassPage->getPagePdfSizeByArticleSerial($row['article_serial']);
        $page_size_pixel = $page_size_pixel ? $page_size_pixel : 2400;
        $list[$news_id] = array(
          // 표시정보
          'index' => ++$i,
          'news_id' => $news_id,
          'article_title' => $row['article_title'],
          'media_name' => $row['media_name'],
          'article_reporter' => $row['article_reporter'],
          'scrap_date' => $row['scrap_date'],
          // 'article_date' => $row['newsTime'],  //2019.07.09 Add
          'orgLink' => $row['orgLink'],
          'refLink' => $row['refLink'],

          'tmp_article_datetime' => $row['article_datetime'],
          'tmp_article_area' => $row['article_area'],
          'tmp_article_contents' => $row['article_contents'],
          'tmp_case_name' => $row['case_name'],
          'tmp_part_name' => $row['part_name'],
          'tmp_article_serial' => $row['article_serial'],

          'category_id' => $row['media_category'],
          'media_category' => $row['media_category'],
          'media_type' => $row['media_type'],
          'media_value' => intval($row['media_value']),
          'category_output' => $row['categoryOutput'],
          'news_comment' => $row['news_comment'],
          // 'article_area' => $row['article_area'],
          'page_size_pixel' => $page_size_pixel,
          'coordinate' => $row['coordinate'],
          // 'contents' => $row['news_contents'],
          'eval1_seq' => $row['eval1_seq'],
          'eval2_seqs' => $row['eval2_seqs'],
          'eval2' => array()
        );
        $eval2_seq_arr = explode(',', $row['eval2_seqs']);
        foreach ($eval2_seq_arr as $ek => $ev) {
          if ($ev) $list[$news_id]['eval2'][] = array('eval2_seq' => $ev);
        }
      } // 결과목록에 없으면 추가
      // array_push($list[$news_id]['eval2'], array('eval2_seq' => $row['eval2_seq']));
    }

    $configEval = getConfigEvalClassic($db_conn);
		$eval1Trees = array();

    foreach ($list as $dk => &$dv) {
      $calc_result = calcArticleValue($dv, $configEval);
      $dv = array_merge($dv, $calc_result);
      $dv['media_category_name'] = $configEval['category'][$dv['category_id']]['category_name'];
      $dv['media_type_name'] = $configEval['type'][$dv['media_type']]['category_name'];
      $dv['eval_auto'] = array();
      foreach ($configEval['group']['AT'] as $ck => $cv) {
        $cv_seq = $cv['seq'];
        $cv_val = '-';
        foreach ($dv['eval2'] as $ek => $ev) {
          if ((int)$ev['eval2_group_seq'] == (int)$cv_seq) {
            $cv_val = $ev['eval2_name'];
            unset($dv['eval2'][$ek]);
            break;
          }
        }
        $dv['eva_' . $ck] = $cv_val;
      }

      $eval1Trees = $this->getEval1NamesArray($configEval['item']['M1'], $dv['eval1_seq']);
      $dv['ev1_big'] = $eval1Trees[0] ? $eval1Trees[0] : '';
      $dv['ev1_mid'] = $eval1Trees[1] ? $eval1Trees[1] : '';
      $dv['ev1_sml'] = $eval1Trees[2] ? $eval1Trees[2] : '';

      foreach ($configEval['group']['M2'] as $ck => $cv) {
        $cv_seq = $cv['seq'];
        $cv_val = '-';
        foreach ($dv['eval2'] as $ek => $ev) {
          if ((int)$ev['eval2_group_seq'] == (int)$cv_seq) {
            $cv_val = $ev['eval2_name'];
            unset($dv['eval2'][$ek]);
            break;
          }
        }
        $dv['ev2_' . $ck] = $cv_val;
      }
    }

    return $list;
  }

  function getEval1NamesArray($configEvalItemM1, $ev1Seq) {
    $rtn = array(); $tmpObj = $configEvalItemM1[$ev1Seq];
    while ($tmpObj) {
      $rtn[] = $tmpObj['value'];
      if (!$tmpObj['group_seq']) break;
      $tmpObj = $configEvalItemM1[$tmpObj['group_seq']];
    }
    return array_reverse($rtn);
  }

  function getEval1Category_tree()
  {
    $sql = "select seq, name, score, upperSeq, cate.order from category as cate where isUse = 'Y' ";
    if (!$this->mysql_link)
      $db_conn = $this->getDBConn();
    else
      $db_conn = $this->mysql_link;
    $result = mysqli_query($db_conn, $sql);
    $rsArray = array();
    $cateList = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $rowArray = array(
          "seq" => $row['seq'],
          "name" => $row['name'],
          "score" => $row['score'],
          "upperSeq" => $row['upperSeq'],
          "order" => $row['order'],
          "sub" => null
      );
      array_push($cateList, $rowArray);
    }
    array_push($rsArray, array("totalList" => $cateList));
    $majorCateList = array();
    foreach ($cateList as $key => $value) {
      if ($value['upperSeq'] == null) {
        array_push($majorCateList, $value);
      }
    }
    $middleCateList = array();
    foreach ($majorCateList as $key => $value) {
      foreach ($cateList as $k => $val) {
        if ($value['seq'] == $val['upperSeq']) {
          array_push($middleCateList, $val);
        }
      }
    }


    $minorCateList = array();
    foreach ($middleCateList as $key => $value) {
      foreach ($cateList as $k => $val) {
        if ($value['seq'] == $val['upperSeq']) {
          array_push($minorCateList, $val);
        }
      }
    }

    $treeArr = $majorCateList;

    for ($i = 0; $i < count(is_countable($treeArr) ? $treeArr : []); $i++) {
      $k = 0;
      for ($j = 0; $j < count(is_countable($middleCateList) ? $middleCateList : []); $j++) {
        if ($treeArr[$i]['seq'] == $middleCateList[$j]['upperSeq']) {
          $treeArr[$i]['sub'][$k] = $middleCateList[$j];
          $k++;
        }
      }
    }

    for ($i = 0; $i < count(is_countable($treeArr) ? $treeArr : []); $i++) {
      for ($j = 0; $j < count(is_countable($treeArr[$i]['sub']) ? $treeArr[$i]['sub'] : []); $j++) {
        $easy = $treeArr[$i]['sub'][$j];
        $h = 0;
        for ($k = 0; $k < count($minorCateList); $k++) {
          if ($easy['seq'] == $minorCateList[$k]['upperSeq']) {
            $treeArr[$i]['sub'][$j]['sub'][$h] = $minorCateList[$k];
            $h++;
          }
        }
      }
    }

    return $treeArr;

  }

  function getEval2Cate()
  {
    $sql = "SELECT eval.seq, eval.name, eval.order FROM evaluation AS eval WHERE isuse = 'Y' ORDER BY eval.order";
    if (!$this->mysql_link) $db_conn = $this->getDBConn();
    else $db_conn = $this->mysql_link;
    $result = mysqli_query($db_conn, $sql);
    $getEval2Cate = array();
    $i = 0;
    while ($row = mysqli_fetch_assoc($result)) {
      $getEval2Cate[$i]['seq'] = $row['seq'];
      $getEval2Cate[$i]['name'] = $row['name'];
      $getEval2Cate[$i]['order'] = $row['order'];
      $i++;
    }
    return $getEval2Cate;
  }
}
?>
