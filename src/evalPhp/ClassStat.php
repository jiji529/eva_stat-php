<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-11-26
 * Time: 오후 7:37
 */
include_once __DIR__ . '/lib/mysql.class.php';

class ClassStat extends MySQL
{

	private $premiumID = "";

	// SET THESE VALUES TO MATCH YOUR DATA CONNECTION
	private $db_host = "211.233.16.3"; // server name
	private $db_user = "root";          // user name
	private $db_pass = "dlgksmlchlrh";          // password
	private $db_dbname = "";          // database name
	private $db_charset = "utf8";          // optional character set (i.e. utf8)
	private $db_pcon = false;      // use persistent connection?


	/**
	 * SMLog constructor.
	 * @param bool $connect
	 * @param null $database
	 * @param null $server
	 * @param null $username
	 * @param null $password
	 * @param null $charset
	 */
	public function __construct($premiumID)
	{
		if (!$premiumID)
			return;
		$this->premiumID = $premiumID;
		$this->db_dbname = "paper_management_" . $premiumID;
		parent::__construct(true, $this->db_dbname, $this->db_host, $this->db_user, $this->db_pass, $this->db_charset);
	}


	public function defaultCreateTable()
	{
		$this->alterHnpNewsTable();
		$this->createCategoryTable();
		$this->createEvaluationTable();
		$this->createEvalClassifyTable();
		$this->createMediaGroupTable();
		$this->createNewsEvalTable();
		$this->createReporterGroupTable();
		$this->createEvalConfigTable();
	}

	/**
	 * `hnp_news` > add `category_seq`
	 */
	private function alterHnpNewsTable()
	{
		$this->query("SHOW COLUMNS FROM `hnp_news` LIKE 'category_seq'");
		if (!$this->RowCount()) {
			$this->Query("ALTER TABLE `hnp_news` ADD COLUMN `category_seq` INT(11) NULL AFTER `headerInfo`");

		}
	}

	/**
	 * create `category`
	 * add `category`
	 */
	private function createCategoryTable()
	{
		$this->Query("SHOW TABLES LIKE 'category'");
		if (!$this->RowCount()) {
			$last_result = $this->Query("CREATE TABLE `category` (
                                  `seq` INT(11) NOT NULL AUTO_INCREMENT,
                                  `name` VARCHAR(200) NOT NULL,
                                  `isUse` CHAR(1) DEFAULT 'Y',
                                  `score` DOUBLE DEFAULT '1',
                                  `upperSeq` INT(11) DEFAULT NULL,
                                  `order` INT(11) DEFAULT NULL,
                                  `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                  PRIMARY KEY (`seq`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
			if ($last_result) {
				$this->Query("INSERT INTO `category` (`seq`,`name`,`isUse`,`score`,`upperSeq`,`order`) VALUES (1,'CEO','Y',2,NULL,1),(2,'사업부문','Y',1,NULL,2),(3,'토목','Y',1,2,3),(4,'SOC','Y',1,3,4),(5,'환경/플랜트','Y',1,3,5),(6,'건축','Y',1,2,6),(7,'건축','Y',1,6,7),(8,'전기','Y',1,6,8),(9,'기계','Y',1,6,9),(10,'주택','Y',1,2,10),(11,'주택','Y',1,10,11),(12,'도시정비','Y',1,10,12),(13,'지원부문','Y',1,NULL,13),(14,'경영지원','Y',1,13,14),(15,'전략혁신','Y',1,13,15),(16,'재무','Y',1,13,16),(17,'기술','Y',1,13,17),(18,'상품개발','Y',1,17,18),(19,'연구소','Y',1,17,19)");
			}
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}
	/**
	 * create `evaluation`
	 * add `evaluation`
	 */
	private function createEvaluationTable()
	{
		$this->Query("SHOW TABLES LIKE 'evaluation'");
		if (!$this->RowCount()) {
			$last_result = $this->Query("CREATE TABLE `evaluation` (
                                                  `seq` INT(11) NOT NULL AUTO_INCREMENT,
                                                  `name` VARCHAR(200) NOT NULL,
                                                  `isUse` CHAR(1) DEFAULT 'Y',
                                                  `automatic` CHAR(1) DEFAULT 'N',
                                                  `order` INT(11) DEFAULT NULL,
                                                  `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                  PRIMARY KEY (`seq`)
                                                ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
			if ($last_result) {
				$this->Query("INSERT INTO `evaluation` (`seq`,`name`,`automatic`,`order`) VALUES (1,'크기','Y',0),(2,'글자수','Y',0),(3,'매체 중요도','Y',0),(4,'출입기자','Y',0),(5,'수록지면','Y',0),(1001,'주목도','N',1),(1002,'기사독점성','N',3),(1003,'효과성','N',4),(1004,'기사유형','N',5),(1005,'기사호감도','N',6)");
			}
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}
	/**
	 * create `evalClassify`
	 * add `evalClassify`
	 */
	private function createEvalClassifyTable()
	{
		$this->Query("SHOW TABLES LIKE 'evalClassify'");
		if (!$this->RowCount()) {
			$last_result = $this->Query("CREATE TABLE `evalClassify` (
                                              `seq` INT(11) NOT NULL AUTO_INCREMENT,
                                              `value` VARCHAR(100) DEFAULT NULL,
                                              `refValue` VARCHAR(100) DEFAULT NULL,
                                              `score` DOUBLE DEFAULT '1',
                                              `isUse` CHAR(1) DEFAULT 'Y',
                                              `order` INT(11) DEFAULT NULL,
                                              `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                              `evaluation_seq` INT(11) NOT NULL,
                                              PRIMARY KEY (`seq`),
                                              KEY `fk_evalClassify_evaluation1_idx` (`evaluation_seq`),
                                              CONSTRAINT `fk_evalClassify_evaluation1` FOREIGN KEY (`evaluation_seq`) REFERENCES `evaluation` (`seq`) ON DELETE NO ACTION ON UPDATE NO ACTION
                                            ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
			if ($last_result) {
				$this->Query("INSERT INTO `evalClassify` (`seq`,`value`,`refValue`,`score`,`order`,`evaluation_seq`) VALUES (1,'대','300',2,1,1),(2,'중','100',1.5,2,1),(3,'소','0',1,3,1),(4,'대','500',2,1,2),(5,'중','100',1.5,2,2),(6,'소','0',1,3,2),(7,'A','mediaGroup',2,1,3),(8,'B','mediaGroup',1.5,2,3),(9,'C','mediaGroup',1,3,3),(10,'출입기자','reporterGroup',2,1,4),(11,'일반기자','reporterGroup',1,2,4),(12,'1','1',2,1,5),(13,'2','2',2,2,5),(14,'3','3',1.5,3,5),(15,'기타','0',1,4,5),(16,'사진+캡션(상)',NULL,3,1,1001),(17,'사진+캡션(하)',NULL,2,2,1001),(18,'사진/삽화/도표',NULL,1.5,3,1001),(19,'BOX/제목사명노출',NULL,1.3,4,1001),(20,'일반',NULL,1,5,1001),(21,'단독보도',NULL,1,1,1002),(22,'1/2공동',NULL,0.5,2,1002),(23,'1/3공동',NULL,0.3,3,1002),(24,'직접홍보기사',NULL,1,1,1003),(25,'간접홍보기사',NULL,0.3,2,1003),(26,'특집섹션기사',NULL,0.3,3,1003),(27,'PI(CEO)',NULL,1,1,1004),(28,'IR',NULL,1,2,1004),(29,'사업전략',NULL,1,3,1004),(30,'제품/서비스',NULL,1,4,1004),(31,'경영활동',NULL,1,5,1004),(32,'기업문화',NULL,1,6,1004),(33,'사회공헌',NULL,1,7,1004),(34,'기타',NULL,1,8,1004),(35,'유리',NULL,1,1,1005),(36,'불리',NULL,-1,2,1005),(37,'중립',NULL,1,3,1005)");
			}
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}
	/**
	 * create `mediaGroup`
	 * add `mediaGroup`
	 */
	private function createMediaGroupTable()
	{
		$this->Query("SHOW TABLES LIKE 'mediaGroup'");
		if (!$this->RowCount()) {
			$last_result = $this->Query("CREATE TABLE `mediaGroup` (
                                                  `seq` INT(11) NOT NULL AUTO_INCREMENT,
                                                  `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                                  `hnp_category_media_id` INT(11) NOT NULL,
                                                  `evalClassify_seq` INT(11) NOT NULL,
                                                  PRIMARY KEY (`seq`),
                                                  KEY `fk_mediaGroup_evalClassify1_idx` (`evalClassify_seq`),
                                                  CONSTRAINT `fk_mediaGroup_evalClassify1` FOREIGN KEY (`evalClassify_seq`) REFERENCES `evalClassify` (`seq`) ON DELETE NO ACTION ON UPDATE NO ACTION
                                                ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
			if ($last_result) {
				$this->Query("INSERT INTO mediaGroup (hnp_category_media_id, evalclassify_seq) SELECT media_id, 7 FROM hnp_category WHERE media_name IN ('조선일보', '중앙일보', '동아일보');");
				$this->Query("INSERT INTO mediaGroup (hnp_category_media_id, evalclassify_seq) SELECT media_id, 8 FROM hnp_category WHERE media_name IN ('매일경제', '한국경제');");
				$this->Query("INSERT INTO mediaGroup (hnp_category_media_id, evalclassify_seq) SELECT media_id, 9 FROM hnp_category WHERE media_name NOT IN ('조선일보', '중앙일보', '동아일보', '매일경제', '한국경제');");
			}
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}

	/**
	 * create `newsEval`
	 */
	private function createNewsEvalTable()
	{
		$this->Query("SHOW TABLES LIKE 'newsEval'");
		if (!$this->RowCount()) {
			$this->Query("CREATE TABLE `newsEval` (
                                              `seq` INT(11) NOT NULL AUTO_INCREMENT,
                                              `evalClassify_seq` INT(11) NOT NULL,
                                              `hnp_news_seq` INT(11) NOT NULL,
                                              `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                              PRIMARY KEY (`seq`),
                                              KEY `fk_newsEval_hnp_news1_idx` (`hnp_news_seq`),
                                              KEY `fk_newsEval_evalClassify1` (`evalClassify_seq`),
                                              CONSTRAINT `fk_newsEval_evalClassify1` FOREIGN KEY (`evalClassify_seq`) REFERENCES `evalClassify` (`seq`) ON DELETE NO ACTION ON UPDATE NO ACTION
                                            ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}

	/**
	 * create `reporterGroup`
	 */
	private function createReporterGroupTable()
	{
		$this->Query("SHOW TABLES LIKE 'reporterGroup'");
		if (!$this->RowCount()) {
			$this->Query("CREATE TABLE `reporterGroup` (
                              `seq` INT(11) NOT NULL AUTO_INCREMENT,
                              `reporterName` VARCHAR(254) NOT NULL,
                              `isUse` CHAR(1) DEFAULT 'Y',
                              `regDate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                              `hnp_category_media_id` INT(11) NOT NULL,
                              `evalClassify_seq` INT(11) NOT NULL,
                              PRIMARY KEY (`seq`),
                              KEY `fk_reporterGroup_evalClassify1_idx` (`evalClassify_seq`),
                              CONSTRAINT `fk_reporterGroup_evalClassify1` FOREIGN KEY (`evalClassify_seq`) REFERENCES `evalClassify` (`seq`) ON DELETE NO ACTION ON UPDATE NO ACTION
                            ) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=euckr;"
			);
		}
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}

	/**
	 * create `evalConfig`
	 */
	private function createEvalConfigTable(){
		$last_result = "";
		$this->Query("SHOW TABLES LIKE 'evalConfig'");
		if (!$this->RowCount()) {
			$last_result = $this->Query("CREATE TABLE `evalConfig` (
                              `seq` int(11) NOT NULL AUTO_INCREMENT,
															`layout` TINYINT(1) DEFAULT 0,
															`regDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
															PRIMARY KEY (`seq`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=euckr;"
			);
		}
		if ($last_result) {
			$this->Query("INSERT INTO evalConfig (`layout`) VALUES (0);");
			$this->Query("CREATE TRIGGER `hnp_news_AFTER_DELETE` AFTER DELETE ON hnp_news FOR EACH ROW DELETE FROM newsEval WHERE hnp_news_seq = OLD.news_id");
		}
		
		if ($this->Error()) {
			$result['success'] = false;
			$result['errno'] = $this->ErrorNumber();
			$result['message'] = $this->Error();
			$this->Close();
			echo json_encode($result);
			exit;
		}
	}
}