<?php
/*
*   Script Class
*
*   philipp.schunker@allianz.at
*   copyright (c) Allianz Technology GmbH
*/

//require_once("sitelib.php");

//abstract class Script{
class Script{

    private $log = null;
    private $logSize = 32;   //MB
    private $logArchiveCount = 0;
    private $output = false;
    private $executionTime = 0;
    private $executionTimeUnit = "s";
    //private $isTeetimeAvailable = false;
    private $sayFunc = "writelog";

    public function __construct(){
        //set_error_handler(array(&$this,"errorHandler"));
        //set_error_handler("ScriptClass::errorHandler");
        //set_error_handler(array("ScriptClass","errorHandler"));
        $ds = DIRECTORY_SEPARATOR;
        //$this->setErrorHandling();
    }

    public function setLog($file){
        $dir = substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR));
        if(!file_exists($dir)){
            throw new Exception("Directory $dir does not exist");
        }
        $this->log = $file;
        
        $out = `which teetime`;
        $pos = strpos(trim($out), "which: no teetime");
        if($pos === false){
            //$this->isTeetimeAvailable = true;
            $this->sayFunc = "_say";
        }
    }

    public function setLogfile($file){
        return $this->setLog($file);
    }

    public function getLogfile(){
        return $this->log;
    }

    public function setMaxLogSize($size){
        if((!is_int($size) && !is_numeric($size)) || $size<=0){
            throw new Exception("Size must be an integer or numeric string greater than 0. Unit is MB.");
        }

        $this->logSize = $size;
    }

    public function setLogArchiveCount($cnt){
        if((!is_int($cnt) && !is_numeric($cnt)) || ($cnt<=0 || $cnt>10)){
            throw new Exception("Count of archived logs must be an integer or numeric string. Minimum count is 1 and maximum count is 10");
        }

        $this->logArchiveCount = $cnt;
    }

    public function setOutput($switch){
        if($switch !== false && $switch !== true){
            throw new Exception("parameter must be a boolean value");
        }

        $this->output = $switch;
    }

    public function getOutput(){
        return $this->output;
    }

    public function println($msg){
        //TODO: check for $this->output field
        if(isset($_SERVER["SERVER_PROTOCOL"])){
            echo($msg."<br />");
        }else{
            echo($msg."\n");
        }
    }

    public function logRotate(){
        if($this->log === null){
            throw new Exception("Logfile not set. Use setLog()");
        }

        if($this->logArchiveCount <= 0){
            throw new Exception("Log archive count not set or invalid");
        }

        if(!file_exists($this->log)){
            return 0;
        }

        //check max file size
        //check for synchronized flag (pending rotate in other process or thread)
        $flagfile = "pendingrotate.flg";
        if((filesize($this->log)/(1024*1024)) < $this->logSize || file_exists($flagfile)){
            return 0;
        }
        $this->say("Filesize above ".$this->logSize."MB limit");
        //write synchronized flag (pending rotate in other process or thread)
        file_put_contents($flagfile,getmypid());
        $logTmp = $this->log.".rot";
        if(file_exists($flagfile) && file_get_contents($flagfile) != getmypid()){
            return 0;
        }
        exec("mv ".$this->log." ".$logTmp);

        //$i = $this->logArchiveCount;
        //while(!file_exists($this->log.".tar.gz.".$i) && $i >= 2){$i--;}
        $i = 0;
        //$this->say($this->log.".tar.gz.".($i+1));
        while(file_exists($this->log.".tar.gz.".($i+1)) && ($i+1) <= $this->logArchiveCount){
            $this->say("checking for ".$this->log.".tar.gz.".($i+1));
            $i++;
        }
        $this->say("File archives: ".$i);
        //if(file_exists($this->log.".tar.gz.".$this->logArchiveCount)){
        if(file_exists($this->log.".tar.gz.".$i)){
            if($i == $this->logArchiveCount){
                exec("rm ".$this->log.".tar.gz.".$this->logArchiveCount);                   //test.log.tar.gz.10
                $i = $i-1;                                                                    //10-1=9
            }
            //for($j=$this->logArchiveCount-1;$j>=1;$j--){
            for($j = $i; $j >= 1; $j--){
                exec("mv ".$this->log.".tar.gz.".$j." ".$this->log.".tar.gz.".($j+1));    //test.log.tar.gz.9 --> test.log.tar.gz.10
            }
        }
        //$last = exec("tar -czf ".$this->log.".tar.gz.1 ".$this->log);
        $last = exec("tar -czf ".$this->log.".tar.gz.1 ".$logTmp);
        $this->say($last);
        //unlink($this->log);
        unlink($logTmp);
        unlink($flagfile);
    }

    public function say($msg){
        return $this->{$this->sayFunc}($msg);
    }

    private function _say($msg){
        if($this->log === null){
            throw new Exception("Logfile not set. Use setLog()");
        }

        if($msg === ""){
            return 1;
        }
        //echo($msg);
        //$out=`"{$msg}" | tee {$this->log} >/dev/null`;
        // see /usr/bin/phpstart
        $out=`echo "{$msg}" 2>&1 | teetime {$this->log} >/dev/null &`;
    }

    public function writelog($msg, $terminal=true){
        if($this->log === null){
            throw new Exception("Logfile not set. Use setLog()");
        }
        /*
        if($terminal){
            echo($msg);
        }*/
        //$date = date("d-m-Y H:i:s");
        file_put_contents($this->log, date("r")." | ".$msg."\n", FILE_APPEND);
    }

    public function setErrorHandling(){
        //error_reporting(E_ALL);
        //error_reporting(E_ERROR | E_WARNING | E_PARSE);
        error_reporting(E_ERROR | E_PARSE);
        set_error_handler(array(&$this,"errorHandler"));
    }

    public function errorHandler($errNo, $errMsg, $errFile, $errLine){
        if($this->log !== null){
            $this->say($errMsg." (".$errNo.") in ".$errFile." on line ".$errLine);
        }

        throw new Exception($errMsg." (".$errNo.") in".$errFile." on line ".$errLine);
    }

    public function start(){
        $this->executionTime = microtime(true);
        return $this->executionTime;
    }

    public function stop(){
        $this->executionTime = microtime(true)-$this->executionTime;
        return $this->executionTime;
    }

    public function getExecutionTime(){
        return $this->executionTime;
    }

    public function getExecutionTimeUnit(){
        return $this->executionTimeUnit;
    }

    public function setExecutionTimeUnit(){
        if($this->executionTime >= 3600){
        	$this->executionTime = round(($this->executionTime/3600), 2);
        	$unit = "h";
        }else if($this->executionTime >= 60){
        	$this->executionTime = round(($this->executionTime/60), 2);
        	$unit = "min";
        }else if($this->executionTime <= 60){
        	$this->executionTime = round($this->executionTime, 2);
        	$unit = "s";
        }

        $this->executionTimeUnit = $unit;
        return $this->executionTimeUnit;
    }

}

?>
