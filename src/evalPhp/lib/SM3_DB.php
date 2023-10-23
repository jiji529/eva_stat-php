<?php
/**
 * Created by IntelliJ IDEA.
 * User: tealight
 * Date: 2018-10-30
 * Time: 오후 12:41
 */

require_once __DIR__ . '/mysql.class.php';

class SM3_DB extends MySQL
{

    // SET THESE VALUES TO MATCH YOUR DATA CONNECTIONq
    private $db_host = "sm3.scrapmaster.co.kr"; // server name
    private $db_user = "test_sm3";          // user name
    private $db_pass = "ekgkal";          // password
    private $db_dbname = "sm3_service";          // database name
    private $db_charset = "utf8";          // optional character set (i.e. utf8)
    private $db_pcon = false;      // use persistent connection?


    //
    private $premiumID = "";
    private $smID = "";
    private $smPWD = "";


    public function __construct($connect = true, $database = null, $server = null, $username = null, $password = null, $charset = null)
    {
        if ($database !== null) $this->db_dbname = $database;
        if ($server !== null) $this->db_host = $server;
        if ($username !== null) $this->db_user = $username;
        if ($password !== null) $this->db_pass = $password;
        if ($charset !== null) $this->db_charset = $charset;
        parent::__construct(true, $this->db_dbname, $this->db_host, $this->db_user, $this->db_pass, $this->db_charset);
    }

    public function getCustomizeOption()
    {
        $sql = '';
        if ($this->premiumID)
            $sql .= "AND a.premiumID ='{$this->premiumID}' ";

        if ($this->smID)
            $sql .= "AND b.id ='{$this->smID}' ";

        if ($this->smPWD)
            $sql .= "AND a.pwd ='{$this->smPWD}' ";

        if ($sql) {
            $query = "SELECT b.customize FROM premiumInfo as a LEFT JOIN member as b ON  a.sm3No=b.member_num ";
            $query .= " WHERE  b.freeorcharged!='F' AND b.freeorcharged!='TH' {$sql} ORDER BY no ";
            $this->Query($query);
            if ($this->RowCount() > 0) {
                return $this->Row(0)->customize;
            }
        }
        return "";

    }


    /**
     * @param string $premiumID
     */
    public function setPremiumID($premiumID)
    {
        $this->premiumID = $premiumID;
    }


    /**
     * @param string $smID
     */
    public function setSmID($smID)
    {
        $this->smID = $smID;
    }


    /**
     * @param string $smPWD
     */
    public function setSmPWD($smPWD)
    {
        $this->smPWD = $smPWD;
    }


}

?>