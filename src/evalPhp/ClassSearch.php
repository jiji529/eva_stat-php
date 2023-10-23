<?php
/**
 * User: bugwang
 * Date: 2018-12-26
 * Time: 13:18
 */
class ClassSearch
{
    private $premiumID = "";
    // SET THESE VALUES TO MATCH YOUR DATA CONNECTION
    private $db_host = "211.233.16.3"; // server name
    private $db_id = "root";          // user name
    private $db_password = "dlgksmlchlrh";          // password
    private $db_dbname = "";          // database name
    private $db_charset = "utf8";          // optional character set (i.e. utf8)

    public function __construct($premiumID)
    {
        if(!$premiumID)
            return;
        $this->premiumID = $premiumID;
        $this->db_dbname = "paper_management_" . $premiumID;
    }

	function getDBConn() {
		//db information------------------------------------------------------------------------
		/*$db_host = "211.233.16.3";
		$db_id = "pierrotss";
		$db_password = "ekgkal4174";
		$db_dbname = "paper_management_pierrotss";*/

		//connect db
		$db_conn = mysqli_connect( $this->db_host, $this->db_id, $this->db_password, $this->db_dbname );
		if(!$db_conn){
			$error = array (
				"error_code" => "E000",
				"error_message" => "Fail to connect database (데이터베이스 연결에 실패했습니다.)"
			);
			echo json_encode($error);
			exit;
		}
        mysqli_query($db_conn, "SET CHARACTER SET '{$this->db_charset}'");
		return $db_conn;
	}

	function getSize($categoryOutput, $articleArea, $contents){
		$articleArea = explode("|", $articleArea);
		if($articleArea[($categoryOutput-1)] == 0) {
			$letter_cnt = mb_strlen($contents);
			$size_val = $letter_cnt * 0.125;
			$size =  $size_val."㎠";
		} else {
			$size =  $articleArea[($categoryOutput-1)]."㎠";
		}
		/*switch($categoryOutput) {
			case('1'):
				if($articleArea[0]==0) {
					$letter_cnt = mb_strlen($contents);
					$size_val = $letter_cnt * 0.125;
					$size =  $size_val."㎠";
				}else{
					$size =  $articleArea[0]."㎠";
				}
				break;
			case('2'):
				if($articleArea[1]==0) {
					$letter_cnt = mb_strlen($contents);
					$size_val = $letter_cnt * 0.125;
					$size =  $size_val."㎠";
				}else{
					$size =  $articleArea[1]."㎠";
				}
				break;
			case('3'):
				if($articleArea[2]==0) {
						$letter_cnt = mb_strlen($contents);
						$size_val = $letter_cnt * 0.125;
						$size =  $size_val."㎠";
				}else{
					$size =  $articleArea[2]."㎠";
				}
				break;
			case('4'):
				if($articleArea[3]==0) {
						$letter_cnt = mb_strlen($contents);
						$size_val = $letter_cnt * 0.125;
						$size =  $size_val."㎠";
				}else{
				$size =  $articleArea[3]."㎠";
				}
				break;
			default:  $size = "";  break;
		}*/
		//echo $size;
        return $size;
	}
    function getSize2($articleArea){
        $articleArea = explode("|", $articleArea);
        $size = $articleArea[0]."㎠";
        return $size;
    }
	/*function calculation($news_id,$articleArea, $categoryOutput, $unitCost, $eval1, $point, $media_point) {
		if($unitCost=="") $unitCost=1;
		if($point=="") $point=1;
		if($media_point=="") $media_point=1;
		if($articleArea=="") $articleArea=1;
		// echo "산출기준 :" .$categoryOutput . " / ";
		// echo "광고단가 :" . $unitCost . " / ";
		// echo "평가항목가중치 : " .$point. " / ";
		// echo "매체포인트 : " . $media_point. " / ";

		//산출기준
		$articleArea = explode("|",$articleArea);
		switch($categoryOutput) {
			case('1'):
				$size = $articleArea[0];
				$base = $size;
				break;
			case('2'):
                $size = $articleArea[0];
                $base = $size;
                break;
			case('3');
				$size = $articleArea[2];
				$base = $size;
				break;
			case('4');
				$size = $articleArea[3];
				$base = $size;
				break;
			case('0');
				$base=0;
				break;
		}
		if($base=="") $base=1;
		//echo "산출기준값 : " . $base ." / ";

		//평가항목 가중치
		switch($point) {
			case('1') : // 평가항목 1
				$eval1_total = $this->eval1_values();
				if($eval1 != "")
				{
					foreach($eval1_total as $val)
					{
						if($val['seq'] == $eval1 ) {$eval1 = $val['score'];}
					}
				} else
				{
					$eval1 = 1;
				}
				//echo "가중치-평가항목1: ".$eval1 ." / ";
				$articleValue = $base * $unitCost * $media_point * $eval1;
				break;
			case('2') : // 평가항목 2
				$sql = "select evalClassify_seq from newsEval where hnp_news_seq = {$news_id} ";
				$db_conn = $this->getDBConn();
				$result = mysqli_query( $db_conn, $sql );
				if ($result)
				{
					$evalClassify_seq = array();
					$i=0;
					while($row = mysqli_fetch_assoc($result))
					{
						$evalClassify_seq[$i] = $row['evalClassify_seq'];
						$i++;
					}
					//echo "result : " .$evalClassify_seq[0] . " / ";
					$eval2_total = $this->eval2_values();
					$eval2 = 1;
					for($i=0; $i<count($evalClassify_seq); $i++)
					{
						foreach($eval2_total as $val)
						{
							//echo $val['seq']. "-". $val['score'] . " / ";
							if($val['seq'] == $evalClassify_seq[$i] )
							{
								$eval2 *= $val['score'];
								//echo "after multiple : " . $eval2 . " / ";
							}
						}
					}
				} else
				{
					$eval2 = 1;
				}

				//echo "가중치-평가항목2: "."eval2 : ".$eval2 ." / ";
				$articleValue = $base * $unitCost * $media_point * $eval2;
				break;
			case('3') : // 평가항목 1 * 2
				$eval1_total = $this->eval1_values();
				if($eval1 != "")
				{
					foreach($eval1_total as $val)
					{
						if($val['seq'] == $eval1 )
						{
							$eval1 = $val['score'];
						}
					}
				} else
				{
					$eval1= 1;
				}
				$sql = "select evalClassify_seq from newsEval where hnp_news_seq = {$news_id} ";
				$db_conn = $this->getDBConn();
				$result = mysqli_query( $db_conn, $sql );
				$evalClassify_seq = array();
				$i=0;
				while($row = mysqli_fetch_assoc($result))
				{
					$evalClassify_seq[$i] = $row['evalClassify_seq'];
					$i++;
				}
				$eval2_total = $this->eval2_values();
				$eval2 = 1;
				for($i=0; $i<count($evalClassify_seq); $i++)
				{
					foreach($eval2_total as $val)
					{
						if($val['seq'] == $evalClassify_seq[$i] )
						{
							$eval2 *= $val['score'];
							//echo "eval2-after multiple({$i}번째) : " . $eval2 . " / ";
						}
					}
				}
				//echo "eval1 : ".$eval1 ." / " . "eval2 : " . $eval2 . " / ";
				$articleValue = $base * $unitCost * $media_point * $eval1 * $eval2;

				break;
		}

		//echo "기사가치:" .number_format(round($articleValue)) . "<br>";
		return round($articleValue);


	}*/

    function size_horizon($articleArea){
        $articleArea = explode("|", $articleArea);
        $horizon = $articleArea[1];
        $horizon = explode("x", $horizon);
        if($horizon[0] <=0) {
            $horizon[0] = null;
        }
        return $horizon[0];
    }

	function size_vertical($articleArea){
        $articleArea = explode("|", $articleArea);
        $horizon = $articleArea[1];
        $horizon = explode("x", $horizon);
        if($horizon[1] <=0) {
            $horizon[1] = null;
        }
        return $horizon[1];
	}


	/*function eval1_values(){
		$sql = "SELECT seq, score FROM category ";
		$db_conn = $this->getDBConn();
		mysqli_set_charset($db_conn,"utf8");
		$result = mysqli_query( $db_conn, $sql );
		$i=0;
		while($row = mysqli_fetch_assoc($result)){
			$eval1_values[$i]['seq'] = $row['seq'];
			$eval1_values[$i]['score'] = $row['score'];
			$i++;
		}
		return $eval1_values;
	}

	function eval2_values(){
		$sql = "SELECT seq, score FROM evalClassify ";
		$db_conn = $this->getDBConn();
		mysqli_set_charset($db_conn,"utf8");
		$result = mysqli_query($db_conn, $sql);
		$i=0;
		while($row = mysqli_fetch_assoc($result)){
			$eval2_values[$i]['seq'] = $row['seq'];
			$eval2_values[$i]['score'] = $row['score'];
			$i++;
		}
		return $eval2_values;
	}*/

	function getLocation($article_serial, $part_name) {
	    if($part_name === ''){
            $location = (int)substr($article_serial, -5,2);
            if($location > 50 || $location <= 0 ){
	            $location = '기타';
            }
            return $location;
        } else {
            return null;
        }

    }
    function getLetterCnt($contents) {
        $contents_space_remove = str_replace(' ','',$contents);
	    $letterCnt = mb_strlen($contents_space_remove, 'UTF-8');
	    return $letterCnt;
    }



}
?>
