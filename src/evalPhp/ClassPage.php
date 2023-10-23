<?php

include_once __DIR__ . '/lib/mysql.class.php';

class ClassPage extends MySQL {

  // SET THESE VALUES TO MATCH YOUR DATA CONNECTION
  private $db_host = "222.231.4.32"; // server name
  private $db_user = "sm3_article";          // user name
  private $db_pass = "sm3_article!@#$";          // password
  private $db_dbname = "sm3_article";          // database name
  private $db_charset = "utf8";          // optional character set (i.e. utf8)
  private $db_pcon = false;      // use persistent connection?
	private $LEN_ARTICLE_SERIAL = 18;
  private $RGX_ARTICLE_SERIAL = '/^[0-9]{8}[a-z0-9]{10}$/';
// $pattern_value_value1 = '/^[A-Z]{3,9}+$/';

  /**
   * SMLog constructor.
   * @param bool $connect
   * @param null $database
   * @param null $server
   * @param null $username
   * @param null $password
   * @param null $charset
   */
  public function __construct() {
    parent::__construct(true, $this->db_dbname, $this->db_host, $this->db_user, $this->db_pass, $this->db_charset);
  }

  public function getPagePdfSizeByArticleSerial($articleSerial) {
    $rtn = 0;
    $articleSerial = strval($articleSerial);
    if (mb_strlen($articleSerial, 'UTF-8') === $this->LEN_ARTICLE_SERIAL
        && preg_match($this->RGX_ARTICLE_SERIAL, $articleSerial, $matches) == 1) {
      $pageSerial = substr($articleSerial, 0, 15);
      $query = "SELECT `pdf_width`, `pdf_height` FROM `xml_page` WHERE `page_serial` = '" . $pageSerial . "'";
      $this->query($query);
      if (!$this->Error() && $this->RowCount() == 1) {
        while ($row = mysqli_fetch_assoc($this->Records())) {
          $size = $row;
        }
        if (is_numeric($size['pdf_width']) && is_numeric($size['pdf_height'])) {
          $rtn = intval($size['pdf_width']) * intval($size['pdf_height']);
        }
      }
    }
    return $rtn;
  } // 20200602[8] kh00[4] 001[3] 001[3]

  public function defaultCreateTable() {
    $this->alterHnpNewsTable();
    $this->createCategoryTable();
  }

  /**
   * `hnp_news` > add `category_seq`
   */
  private function alterHnpNewsTable() {
    $this->query("SHOW COLUMNS FROM `hnp_news` LIKE 'category_seq'");
    if (!$this->RowCount()) {
      $this->Query("ALTER TABLE `hnp_news` ADD COLUMN `category_seq` INT(11) NULL AFTER `headerInfo`");
    }
  }

  /**
   * create `category`
   * add `category`
   */
  private function createCategoryTable() {
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
}
