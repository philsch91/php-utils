<?php

class Neo4jTransaction{

    private $id=null;
    private $expiryDate=null;
    private $statements=array();
    private $completed=false;
    private $errors=array();
    //private $states=array();

    protected function __construct(){
    }

    public static function create(){
        $instance=new Neo4jTransaction();

        return $instance;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id=$id;
    }

    public function isOpen(){
        $utcOffset=exec('date +"%:z"',$output,$rc);
        $adjustedExpiryDate=strtotime($utcOffset,$expiryDate);
        $now=time();
        if($adjustedExpiryDate<$now){
            return false;
        }
        return true;
    }

    public function isCompleted(){
        return $this->completed;
    }

    public function setCompleted($state){
        $this->completed=$state;
    }

    public function add($stmt){
        $this->statements[]=$stmt;
        //$this->states[]=false;
    }

    public function getStatements(){
        return $this->statements;
    }

    public function getErrors(){
        return $this->errors;
    }

    public function setErrors(array $errors){
        $this->errors=$errors;
    }

    /*
    public function getOpenStatements(){
        $statements=$this->statements;
        $states=$this->states;
        $openStatements=array();
        foreach($states as $i => $state){
            if($state===false && !empty($statements[$i])){
                $openStatements[]=$statements[$i];
            }
        }

        return $openStatements;
    }*/

    /*
    public function setState($index){
        $this->statements[$index]=true;
    }*/
}

?>
