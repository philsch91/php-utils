<?php
/*
*   TableCleaner
*
*   philipp.schunker@allianz.at
*/


require_once("sitelib.php");
require_once(SITE_LIB."dbtools.php");
//require_once("/srv/www/htdocs/smgmt/cmdb/classes/ScriptClass.php");
require_once("autoloader.php");

/**
 * TableCleaner for deleting rows in a table with a column
 * that holds a minimum and a maximum value
 */
class TableCleaner extends Script{
    /**
     * DBWrap instance used for database communication
     * @var DBWrap
     */
    private $DB=null;
    private $table="";
    private $column="";
    private $sqlCond="";
    private $deleteLimit=0;
    private $deleteTimespan=1800;
    private $avgDeleteDuration=0;

    public function __construct($table,$column){
        $this->table=$table;
        $this->column=$column;
    }

    public function setDatabase(DBWrap $db){
        $this->DB=$db;
    }

    public function setTable($table){
        $this->table=$table;
    }

    public function setColumn($col){
        $this->column=$col;
    }

    public function setCond($sql){
        $sqlcpy=strtolower($sql);
        if(strpos($sqlcpy,"=")===false && strpos($sqlcpy,"like")===false){
            throw new Exception("no valid SQL WHERE clause");
        }
        $sqlcpy=trim($sqlcpy);
        if(strpos($sqlcpy,"where")===0){
            $sql=substr($sql,strlen("where"));
            $sql=trim($sql);
        }
        $this->sqlCond=$sql;
    }

    public function setDeleteLimit($sec){
        $this->deleteLimit=$sec;
    }

    public function setDeleteTimespan($sec){
        $this->deleteTimespan=$sec;
    }

    public function getAverageDeleteDuration(){
        return $this->avgDeleteDuration;
    }

    /**
     * use this method to start the table cleanup with the provided table and column name
     * @return bool true if cleanup was succesful, otherwise false
     */
    public function run(){
        if($this->table==="" || $this->column===""){
            return false;
        }

        //$this->start();
        $where="";
        if($this->sqlCond!==""){
            $where=" where ".$this->sqlCond;
        }

        $this->DB->select("select min(".$this->column.") from ".$this->table.$where);
        //$firstrow=$this->DB->GetFirst();
        //$min=$firstrow[0];
        $row=$this->DB->GetRows();
        $min=$row[0][0];

        $this->writelog("min value: ".$min);

        $max=$this->deleteLimit;
        if($max===0){
            $this->DB->select("select max(".$this->column.") from ".$this->table.$where);
            //$firstrow=$this->DB->GetFirst();
            //$max=$firstrow[0];
            $row=$this->DB->GetRows();
            $max=$row[0][0];
        }

        $this->writelog("max value: ".$max);

        $and="";
        if($this->sqlCond!==""){
            $and=" and ".$this->sqlCond;
        }

        $execsum=0;
        $delCounter=0;
        $avg=0;

        while($min<=$max){
            $this->writelog("delete dataset < ".date("d-m-Y H:i:s",$min)." (".$min.")",false);
            $start=microtime(true);
            $this->DB->Execute("delete from ".$this->table." where ".$this->column."<".$min.$and);
            $end=round(microtime(true)-$start,2);
            $delCounter++;

            $execsum=$execsum+$end;
            $avg=round($execsum/$delCounter,2);

            $min=$min+$this->deleteTimespan;
        }

        //$this->stop();

        $this->writelog("average deletion duration: ".$avg."s");
        $this->writelog(__CLASS__." finished in file ".__FILE__);

        return true;
    }

    /**
     * returns the number of datasets in the table
     * @return int number of datasets
     */
    public function GetDatasetCount(){
        $this->DB->select("select count(*) from ".$this->table);
        //$row=$this->DB->GetFirst();
        $row=$this->DB->GetRows();
        $cntstr=$row[0][0];
        $cnt=intval($cntstr);
        return $cnt;
    }
}

?>
