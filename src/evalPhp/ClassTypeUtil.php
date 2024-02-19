<?php 
class ClassTypeUtil {
    
    private $db_conn = null;
    private $evaluation_seq = 7;
    private $insertArrData = array();
    
    public function __construct($db_conn) {
        $this->db_conn = $db_conn;
    }
    public static function classTypeUtilWithEvalSeq($db_conn, $evaluation_seq) {
        $object = new ClassTypeUtil($db_conn);
        $object.setEvaluationSeq($evaluation_seq);
        return $object;
    }
    public function setEvaluationSeq($evaluation_seq) {
        $this->evaluation_seq = $evaluation_seq;
    }
    
    private function runSql($sql, $message) {
        $resultSet = mysqli_query($this->db_conn, $sql) or die(mysqli_error($this->db_conn)." ".$message);
        if (!$resultSet) {
            echo 'MySQL Error: ' . mysqli_error($this->db_conn);
            exit;
        }
        return $resultSet;
    }
    
    /**
     * 기사 대-소제목 설정 API인 articleClassType.php에서 사용된다
     * (self::setNewEvalClassify에서도 사용)
     * @return NULL|Array
     */
    public function getEvalClassifyList() {
        $SQL = "
            SELECT  `seq`,  `value`, `order`, `refValue`,  `score`,  `isUse`
            FROM `evalClassify`
            WHERE `evaluation_seq` = {$this->evaluation_seq}
            ORDER BY `order` ASC
        ";
        $rs = self::runSql($SQL, "SELECT ERROR");
        $list = null;
        while ($row = mysqli_fetch_assoc($rs)) {
            $list[] = $row;
        }
        return $list;
    }
    
    /**
     * 기사 대-소제목 설정 API인 articleClassType.php에서 사용된다
     * @param $value
     * @return NULL|Array
     */
    public function updateEvalClassify($value) {
        if ($value['seq'] == null &&
            ($value['score'] == null && $value['refValue'] == null)
        ) return null;
        $seq = (int) $value['seq'];
        $SQL = "UPDATE `evalClassify` SET ";
        if ($value['score'] != null)
            $SQL .= " `score` = '{$value['score']}',";
        if ($value['order'] !== null)
            $SQL .= " `order` = '{$value['order']}',";
        $SQL = substr($SQL, 0, -1);
        $SQL .= " WHERE `seq` =  {$seq} ";
        self::runSql($SQL, "UPDATE ERROR");
    }
    
    private function delEvalClassifyList($titleList) {
        $SQL = "
            DELETE FROM `evalClassify`
            WHERE `evaluation_seq` = {$this->evaluation_seq}
            AND `value` NOT IN (". (($titleList == null) ? "''" : implode(",", $titleList)) .")
        ";
        self::runSql($SQL, "DELETE ERROR");
    }
    
    
    /**
     * self::setNewEvalClassify에서 사용되고 hnp_news에서 데이터를 찾는다.
     * @param $titleList
     * @return string[]
     */
    private function getClassTypeFromHnpNews($titleList) {
        /* hnp_news에서 미등록된 대-소제목 찾기 */
        $SQL = "
            SELECT DISTINCT `hnews`.news_title, `hnews`.classType
            FROM `hnp_news` AS `hnews`
            LEFT JOIN `hnp_category` AS `hcate` ON `hcate`.`media_id` = `hnews`.`media_id`
            LEFT JOIN `scrapBook` AS `SB` ON `SB`.`no` = `hnews`.`scrapBookNo`
            WHERE 1=1
            AND hcate.media_name IN ('대제목','소제목')
            AND hnews.news_title NOT IN (". (($titleList == null) ? "''" : implode(",", $titleList)) .")
        ";
        $rs = self::runSql($SQL, "SELECT hnp_news ERROR");
        
        /* 대-소제목 등록 준비 */
        $insertValues = [];
        $total_rows = mysqli_num_rows($rs) + ($titleList != null ? count($titleList) : 0);
        while ($row = mysqli_fetch_assoc($rs)) {
            $total_rows++;
            $typeStr = $row['classType'] == 1 ? "대제목" : "소제목";
            $insertValues["'".$row['news_title']."'"] = " ('{$row['news_title']}', '{$typeStr}', 1, 'Y', {$total_rows}, {$this->evaluation_seq}) ";
            $titleList[] = "'".$row['news_title']."'";
        }
        return array($insertValues, $titleList);
    }
    
    /**
     * self::setNewEvalClassify에서 사용되고 subtitle에서 데이터를 찾는다.
     * @param $titleList
     * @return string[]
     */
    private function getClassTypeFromSubtitle($titleList) {
        /* smallTitle에서 미등록된 대-소제목 찾기 */
        $SQL = "
            SELECT DISTINCT subtitle
            FROM subTitleInfo
            WHERE subtitle NOT IN (".(($titleList == null) ? "''" : implode(",", $titleList)).")
        ";
        $rs = self::runSql($SQL, "SELECT subTitleInfo ERROR");
        
        /* 대-소제목 등록 준비 */
        $insertValues = [];
        /* $total_rows은 위에서 카운트된 데이터를 그대로 따른다. */
        while ($row = mysqli_fetch_assoc($rs)) {
            $total_rows++;
            $insertValues["'".$row['subtitle']."'"] = " ('{$row['subtitle']}', '소제목', 1, 'Y', {$total_rows}, {$this->evaluation_seq}) ";
            $titleList[] = "'".$row['subtitle']."'";
        }
        return array($insertValues, $titleList);
    }
    
    /**
     * 평가통계 초기 진입 시, 새로 추가할 대-소제목을 찾아 evalclassify에 등록한다. 
     * @return boolean
     */
    public function setNewEvalClassify() {
        /* 등록된 데이터를 통헤 미등록 데이터를 준비한다. */
        $insertDataAndTitleList = self::getClassTypeFromHnpNews(null);
        $insertDataAndTitleList2 = self::getClassTypeFromSubtitle($insertDataAndTitleList[1]);
        $insertData = array_merge($insertDataAndTitleList[0], $insertDataAndTitleList2[0]);
        $titleList = $insertDataAndTitleList2[1];
        
        self::delEvalClassifyList($titleList);
        
        $oldList = self::getEvalClassifyList();
        $result = null;
        if ($insertData != null) {
            foreach ($insertData as $key => $value) {
                if ($oldList != null && in_array($key, $oldList)) continue;
                $result[] = $value;
            }
        }
        
        /* 미등록 데이터 삽입 */
        if ($result == null || count($result) < 1) return false;
        $SQL = "
            INSERT INTO `evalClassify` (`value`, refValue, `score`, `isUse`, `order`, `evaluation_seq`)  
            VALUES ".implode(',', $result)."  
        ";
        self::runSql($SQL, "INSERT ERROR");
        return true;
    }
    
    private function getArticleList($scrapDateStart, $scrapDateEnd, $newsMe) {
        $SQL = "";
        if (is_null($scrapDateStart) && is_null($scrapDateEnd) && is_null($newsMe)) {
            $SQL = "
                SELECT `A`.`news_id`, `A`.news_title, `A`.classType, `A`.scrapBookNo
                FROM `hnp_news` AS `A` RIGHT JOIN (
                	SELECT `no`, `keywords`
                	FROM scrapBook
                ) AS `B` ON `A`.scrapBookNo = `B`.`no`
                WHERE `articleSequence` = 0
                ORDER BY news_id ASC
            ";
        } else {
            $SQL = "
                SELECT `A`.`news_id`, `A`.news_title, `A`.classType, `A`.scrapBookNo
                FROM `hnp_news` AS `A` RIGHT JOIN (
                	SELECT `no`, `keywords`
                	FROM scrapBook
                	WHERE scrapDate BETWEEN '{$scrapDateStart}' AND '{$scrapDateEnd}'
                	AND newsMe IN (". $newsMe .")
                ) AS `B` ON `A`.scrapBookNo = `B`.`no`
                WHERE `articleSequence` = 0
                ORDER BY news_id ASC
            ";
        }
        $resultSet = self::runSql($SQL, "Article Error");
        $articles = array();
        $newsIdArr = array();
        while ($row = mysqli_fetch_assoc($resultSet)) {
            $articles[] = $row;
            $newsIdArr[] = $row["news_id"];
        } 
        return array($articles, $newsIdArr);
    }
    
    private function getClassTypeList() {
        /* 등록된 대-소제목 불러오기 */
        $SQL = "
            SELECT *
            FROM evalClassify
            WHERE evaluation_seq = {$this->evaluation_seq}
        ";
        $resultSet = self::runSql($SQL, "ClassType Error");
        $classTypeList = array();
        while($row = mysqli_fetch_assoc($resultSet)) {
            $classTypeList[$row["value"]] = $row;
        }
        return $classTypeList;
    }
    
    private function getEvaluatedItem($newsIdArr) {
        if (count($newsIdArr) < 1) return array();
        /* 등록된 대-소제목 불러오기 */
        $SQL = "
            SELECT hnp_news_seq  
            FROM `newsEval` AS `ne` LEFT JOIN `evalClassify` AS `ec`   
            ON `ec`.`seq` = `ne`.`evalClassify_seq`  
            WHERE ec.evaluation_seq = {$this->evaluation_seq}  
            AND hnp_news_seq IN (". implode(',', $newsIdArr) .")  
        ";
        $resultSet = self::runSql($SQL, "NewsEval Error");
        $reuslt = array();
        while($row = mysqli_fetch_assoc($resultSet)) {
            $reuslt[$row["hnp_news_seq"]] = 1;
        }
        return $reuslt;
    }
    
    private function getSubtitleList($scrapDateStart, $scrapDateEnd) {
        /* subtitleInfo */
        if (is_null($scrapDateStart) && is_null($scrapDateEnd)) {
            $SQL = "
                SELECT scrapBookNo, subtitle, `offset`
                FROM subTitleInfo AS s LEFT JOIN scrapBook AS sc
                ON s.scrapBookNo = sc.`no`
                AND isUse = 1
            ";
        } else {
            $SQL = "
                SELECT scrapBookNo, subtitle, `offset`  
                FROM subTitleInfo AS s LEFT JOIN scrapBook AS sc  
                ON s.scrapBookNo = sc.`no`  
                WHERE sc.scrapDate BETWEEN '{$scrapDateStart}' AND '{$scrapDateEnd}'  
                AND isUse = 1  
            ";            
        }
        $resultSet = self::runSql($SQL, "SubTitleInfo Error");
        $titleInfo = array();
        while($resultSet != null && $row = mysqli_fetch_assoc($resultSet)) {
            $titleInfo[$row["scrapBookNo"]][$row["offset"]] = $row["subtitle"];
        }
        return $titleInfo;
    }
    
    public function classTypeAutoEvaluation($scrapDateStart, $scrapDateEnd, $newsMe) {
        /* 기사 불러오기 */
        $info = self::getArticleList($scrapDateStart, $scrapDateEnd, $newsMe);
        $articles = $info[0];
        $newsIdArr = $info[1];

        /* 등록된 대-소제목 불러오기 */
        $evalClassifyList = self::getClassTypeList();
        
        /* subTitleInfo */
        $titleInfo = self::getSubtitleList($scrapDateStart, $scrapDateEnd);
        
        $evaluatedNewsId = self::getEvaluatedItem($newsIdArr);
        
        /* 기사 대-소제목 자동평가 */
        $insArrData = array();
        $currentScrapBook = -1;
        $cnt = 0;
        $subtitle = null;
        
        $titleSeq = null;
        $TC = count($titleInfo) > 0;
        $EC = count($evalClassifyList) > 0;
        foreach ($articles as $art) {
            if ($currentScrapBook != $art["scrapBookNo"]) {
                $currentScrapBook = $art["scrapBookNo"];
                $cnt = 0;
                $titleSeq = null;
            }
            
            /* subTitleInfo에서 대-소제목 찾기 */
            if ($TC && ($subtitle = $titleInfo[$currentScrapBook][$cnt++]) != null) {
                $titleSeq = $evalClassifyList[$subtitle]["seq"];
            }
            
            /* hnp_news에서 대-소제목 찾기 */
            if ($EC && intval($art["classType"]) > 0) {
                $titleSeq = $evalClassifyList[$art["news_title"]]["seq"];
                continue;
            }
            
            if ($titleSeq != null && $evaluatedNewsId[$art["news_id"]] == null) {
                $insArrData[] = " ({$titleSeq}, {$art["news_id"]}) ";
            }
            
        } //foreach
        
        $this->insertArrData = $insArrData;
        return true;
    }
    
    public function InsertClassType() {
        if (count($this->insertArrData) < 1) return false;
        $SQL = "
            INSERT INTO `newsEval` (`evalClassify_seq`, `hnp_news_seq`)  
            VALUES " .implode(',', $this->insertArrData). "  
        ";
        self::runSql($SQL, "INSERT ERROR");
        return true;
    }
    
}
