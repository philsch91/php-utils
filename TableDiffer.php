<?php

require_once("sitelib.php");
require_once(SITE_LIB."dbtools.php");
require_once("autoloader.php");

/**
 * [TableDiffer description]
 */
class TableDiffer extends Script{
    /**
     * DBWrap instance used for database communication
     * @var DBWrap
     */
    private $DB=null;
    /**
     * table name
     * @var string
     */
    private $table=null;
    /**
     * columns which are used to differentiate datasets in the table
     * @var array
     */
    private $keycolumns=null;
    private $valcolumns=null;
    private $currentDatasets=null;
    private $newDatasets=null;
    private $statementList=null;
    /*
    private $deleteTimespan=1800;
    private $avgDeleteDuration=0;
    */

    public function __construct($db,$table){
        $this->DB=$db;
        $this->table=$table;
    }

    public function getKeyColumns(){
        if(!isset($this->keycolumns)){
            $this->keycolumns=array();
        }
        return $this->keycolumns;
    }

    public function setKeyColumns(array $columns){
        $this->keycolumns=$columns;
        return $this;
    }

    public function addKeyColumn($col){
        $columns=$this->getColumns();
        $columns[]=$col;
        $this->keycolumns=$columns;
    }

    public function getValColumns(){
        if(!isset($this->valcolumns)){
            $this->valcolumns=array();
        }
        return $this->valcolumns;
    }

    public function setValColumns(array $columns){
        $this->valcolumns=$columns;
    }

    public function setNewDatasets(array $datasetDict){
        ////$datasetDict[$key0."-".$key1."-".$key2]["col"]="val"
        /*
        $newDatasets=array();
        foreach($datasetDict as $keystr => $vals){
            $isValid=true;
            $colKeys=explode("-",$keystr);
            foreach($colKeys as $key){
                if(!in_array($key,$this->keycolumns)){
                    $isValid=false;
                }
            }
            if($isValid){
                $newDatasets[$keystr]=$vals;
            }
        }
        $this->newDatasets=$newDatasets;
        */
        $this->newDatasets=$datasetDict;
    }

    public function getStatementList(){
        if(!isset($this->statementList)){
            $this->statementList = array();
        }
        return $this->statementList;
    }

    public function getDatasets(){
        $lindex = count($this->keycolumns)-1;

        $cols = implode(",",$this->keycolumns);
        $valcols = implode(",",$this->valcolumns);
        
        if(!empty($valcols)){
            $cols .= ",".$valcols;
        }

        $currentDatasets = array();

        $this->DB->select("select ".$cols." from ".$this->table." t");
    	$rows = $this->DB->GetRows();

    	foreach($rows as $i => $row){
            $key = "";
            for($i = 0; $i <= $lindex; $i++){
                $key .= "-".$row[$i];
            }

            $startindex = $lindex+1;
            $vals = array();
            /*
            $lastindex=count($this->valcolumns)-1;
            for($i=0,$j=$startindex;$i<=$lastindex;$i++,$j++){
                $key=$this->valcolumns[$i];
                $vals[$key]=$row[$j];
            }*/

            $i = $startindex;
            foreach($this->valcolumns as $key){
                $vals[$key] = $row[$i];
                $i++;
            }

    		$currentDatasets[$key] = $vals;
        }

        return $currentDatasets;
    }

    public function getSqlStringCondition($key,$whereInclude=true){
        $cols = explode("-",$key);
        $lindex = count($this->keycolumns)-1;
        $where = array();
        for($i = 0; $i <= $lindex; $i++){
            $where[] = $this->keycolumns[$i]."='".$cols[$i]."'";
        }

        $wherestr = implode(" and ",$where);
        if($whereInclude){
            $wherestr = "where ".$wherestr;
        }

        return $wherestr;
    }

    public function mergeStatements($statements,$statementsToAdd){
        foreach($statementsToAdd as $key => $valarr){
            if(!empty($statements[$key])){
                $statements[$key] = array_merge($statements[$key],$valarr);
            }else{
                $statements[$key] = $valarr;
            }
        }
        return $statements;
    }

    /**
     * getDiffDeletes returns a dictionary with SQL statements for deleting old datasets
     * which are not present in the new datasets
     * @return array statements for deleting old datasets
     */
    public function getDiffDeletes($currentDatasets,$newDatasets){
        $transactions = array();
    	$datasetsToDelete = array_diff_key($currentDatasets,$newDatasets);
    	foreach($datasetsToDelete as $key => $val){
            /*
    		list($depci,$srvci)=explode("-",$key);
    		$distance=$props["distance"];
    		$cutpoint=$props["cutpoint"];
            */
            $cols = explode("-",$key);
    		$lindex = count($this->keycolumns)-1;
            $where = array();
            for($i = 0; $i <= $lindex; $i++){
                $where[] = $this->keycolumns[$i]."='".$cols[$i]."'";
            }
            $wherestr = implode(" and ",$where);

    		//$sql="delete from ".$this->table." where DEPCI_='".$depci."' and SRVCI_='".$srvci."'";
    		$sql = "delete from ".$this->table." where ".$wherestr;
    		$transactions[$key][] = $sql;
    	}

        return $transactions;
    }

    /**
     * getDiffInserts returns a dictionary with SQL statements for inserting new datasets
     * which are not present in the current datasets
     * @return array statements for inserting new datasets
     */
    public function getDiffInserts($newDatasets,$currentDatasets){
        $transactions = array();
    	$datasetsToInsert = array_diff_key($newDatasets,$currentDatasets);
        foreach($datasetsToInsert as $key => $val){
    		//list($depci,$srvci)=explode("-",$key);
    		//$distance=$props["distance"];
    		//$cutpoint=$props["cutpoint"];
            //$lindex=count(explode("-",$key))-1;

            //'val1','val2'
            $cols = explode("-",$key);
            $vals = implode("','",$cols);
        	$valstr = "'".$vals."'";

            //key1,key2
            $colstr = implode(",",$this->keycolumns);
            foreach($val as $vkey => $vval){
                $colstr .= ",".$vkey;
                //extend valstr
                //$valstr=",'".$valstr."'";
                $valstr .= ",'".$vval."'";
            }

    		$sql = "insert into ".$this->table." (".$colstr.") values (".$valstr.")";
    		$transactions[$key][] = $sql;
    	}

        return $transactions;
    }
    
    /**
     * getDiffUpdates returns a dictionary with SQL statements
     * for datasets which need to be updated
     *
     * @param array $currentDatasets
     * @return array update statements
     */
    public function getDiffUpdates(array $currentDatasets){
        $updateStatements = array();

        foreach($this->newDatasets as $key => $valarr){
            if(!empty($currentDatasets[$key]) && is_array($currentDatasets[$key])/*&& $currentDatasets[$key]["distance"]!=$distance*/){
                $updcols = array();
                foreach($currentDatasets[$key] as $ikey => $ival){
                    if(!empty($newDatasets[$ikey]) && $newDatasets[$ikey]!==$ival){
                        $updcols[] = $ikey."='".$ival."'";
                    }
                }

                if(empty($updcols)){
                    continue;
                }
                //$sql="update TCIDEPDIST set DEPCI_='".$depci_."',SRVCI_='".$srv_ci."',CUTPOINT='".$cutpoint."',DISTANCE='".$distance."'";
                //$sql="update TCIDEPDIST set CUTPOINT='".$cutpoint."', DISTANCE='".$distance."'";
				//$sql=$sql." where DEPCI_='".$depci_."' and SRVCI_='".$srv_ci_."'";
                $colstr = implode(",",$updcols);

                $wherestr = getSqlStringCondition($key,false);

                $sql = "update ".$this->table." set ".$colstr." where ".$wherestr;
                $updateStatements[$key][] = $sql;
            }
        }

        return $updateStatements;
    }

    /**
     * getDiffStatements wraps calls to getDiffDeletes, getDiffInserts and getDiffUpdates
     * merges and finally returns a dictionary containing all statements
     *
     * @return array 
     */
    public function getDiffStatements(){
        $currentDatasets = $this->getDatasets();
        
        $statements = $this->getDiffDeletes($currentDatasets,$this->newDatasets);
        $insertStatements = $this->getDiffInserts($this->newDatasets,$currentDatasets);
        $updateStatements = $this->getDiffUpdates($currentDatasets);

        //merge statements
        $statements = $this->mergeStatements($statements,$insertStatements);
        $statements = $this->mergeStatements($statements,$updateStatements);

        return $statements;
    }

    public function reorderStatements(array $statements){
        $length = strlen("delete");
        $newStatements = array();
        
        foreach($statements as $key => $statementList){
            $newStatementList = array();    		
            
            foreach($statementList as $i => $stmt){
                $lstmt = strtolower($stmt);
                if(strpos($lstmt,"delete") === 0){
                    $newStatementList[] = $stmt;
                }
            }
            
            foreach($statementList as $i => $stmt){
                $lstmt = strtolower($stmt);
                if(strpos($lstmt,"delete") === FALSE){
                    $newStatementList[] = $stmt;
                }
            }

            $newStatements[$key] = $newStatementList;
        }
        
        return $newStatements;
    }

    public function execute(array $statements){
        foreach($statements as $key => $statementList){
    		$this->DB->BeginTrans();
    		foreach($statementList as $i => $stmt){
    			//say(date("d.m.Y H:i:s")." sleep");
    			//usleep(10000);	//10ms
    			say(date("d.m.Y H:i:s")." ".$stmt);
    			$this->DB->Execute($stmt." with cs");
    		}
    		$this->DB->EndTrans();
    	}
    }
}

?>
