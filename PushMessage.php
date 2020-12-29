<?php

class PushMessage{

    private $rec;
    private $msg;
    private $level;

    function __construct(){
        $this->rec=array();
        $this->msg="";
        $this->level="";
    }

    public function &GetReceiver(){
        return $this->rec;
    }

    public function SetReceiver($index,$addr){
        $this->$rec[$index]=$addr;
    }

    public function GetMessage(){
        return $this->msg;
    }

    public function GetLevel(){
        return $this->level;
    }

    public function AddReceiver($emailAddr){
        /*if(strpos($emailAddr,"@")===false){
            echo("Address should be a valid email adress");
            return false;
        }*/
        $this->rec[]=$emailAddr;
        return true;
    }

    public function RemoveReceiver($keyOrVal){
        if(isset($this->rec[$keyOrVal])){
            unset($this->rec[$keyOrVal]);
            return true;
        }
        foreach($this->rec as $i => $val){
            if($val==$keyOrVal){
                unset($this->rec[$i]);
                return true;
            }
        }
        return false;
    }

    public function SetMessage($msg){
        $this->msg=str_replace("@@","@",$msg);
        //v0.1.5 if(strlen($this->msg>210))
        if(strlen($msg>180)){
    	    $this->msg=substr($msg,0,180);
        }
        return true;
    }

    public function SetLevel($level){
        if(is_int($level)){
            if($level<1 || $level>3){
                echo("Level is not valid (1=INFO,2=WARNING,3=CRITICAL)\n");
                return false;
            }
        }
        if(!is_numeric($level)){
            echo("Level is not numeric (1=INFO,2=WARNING,3=CRITICAL)\n");
            return false;
        }
        $level="$level";
        $this->level=$level;
        return true;
    }
}

?>
