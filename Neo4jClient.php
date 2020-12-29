<?php

set_time_limit(0);

class Neo4jClient{
    private $curlHandle=null;
    private $host=null;
    private $port=7474;
    private $user=null;
    private $password=null;

    function __construct(){
        $this->curlHandle = curl_init();
        $headers=array(
            "Cache-Control: no-cache",
            "Accept: application/json; charset=UTF-8",
            "Content-Type: application/json; charset=UTF-8"
        );

        curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($this->curlHandle, CURLOPT_URL, "http://lx-neo001:7474/db/data");
        //curl_setopt($this->curlHandle, CURLOPT_PORT, 7474);
        //curl_setopt($this->curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($this->curlHandle, CURLOPT_USERPWD, "neo4j:123");
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
    }

    public static function create(){
        $instance=new Neo4jClient();
        $instance->setHost("lx-neo001");
        $instance->setPort(7474);
        $instance->setUser("neo4j");
        $instance->setPassword("123");
        $instance->updateAuthentication();

        return $instance;
    }

    public function select($cypher,&$stats=null,&$debug=null){
        if(empty($cypher)){
            throw new Exception("cypher statement(s) as parameter is missing");
        }
        $data=array();
        if(is_array($cypher)){
            foreach($cyper as $i => $stmt){
                //$data["statements"][0]["statement"]="CREATE (n {props}) RETURN n"
                //$data["statements"][0]["parameters"]["props"]["name"]="MyNode"
                $data["statements"][]=$stmt;
            }
        }else if(is_string($cypher)){
            $data["statements"][0]["statement"]=$cypher;
            if($stats!==null){
                $data["statements"][0]["includeStats"]=true;
            }
        }

        //curl_setopt($curlHandle, CURLOPT_URL, "http://lx-neo001:7474/db/data/transaction/commit");
        $json=json_encode($data);
        $response="";
        $response=$this->curlPost("http://".$this->host.":".$this->port."/db/data/transaction",$json);
        $response=json_decode($response,true);

        $debug=$response;

        if(!empty($response["errors"])){
            return $response["errors"];
        }
        if(empty($response["results"][0]["data"])){
            return $response["results"][0]["data"];
        }
        $data=array();
        foreach($response["results"][0]["data"] as $i => $dataset){
            foreach($dataset["row"] as $j => $node){
                if(empty($node["name"]) && $dataset["meta"][$j]["type"]==="relationship"){
                    $node["name"]=$dataset["meta"][$j]["id"];
                }

                $data[$i][$j]=$node["name"];
            }
        }

        if(!empty($response["results"][0]["stats"])){
            $stats=$response["results"][0]["stats"];
        }

        return $data;
    }

    public function execute($cypher,&$stats=null,&$debug=null){
        if(empty($cypher)){
            throw new Exception("cypher statement(s) as parameter is missing");
        }
        $data=array();
        if(is_array($cypher)){
            foreach($cypher as $i => $stmt){
                //$data["statements"][0]["statement"]="CREATE (n {props}) RETURN n"
                //$data["statements"][0]["parameters"]["props"]["name"]="MyNode"
                $data["statements"][]=$stmt;
            }
        }else if(is_string($cypher)){
            $data["statements"][0]["statement"]=$cypher;
            if($stats!==null){
                $data["statements"][0]["includeStats"]=true;
            }
        }

        //curl_setopt($curlHandle, CURLOPT_URL, "http://lx-neo001:7474/db/data/transaction/commit");
        $json=json_encode($data);
        $response="";
        $response=$this->curlPost("http://".$this->host.":".$this->port."/db/data/transaction/commit",$json);
        $response=json_decode($response,true);

        $debug=$response;

        if(!empty($response["errors"])){
            return $response["errors"];
        }
        if(empty($response["results"][0]["data"])){
            return $response["results"][0]["data"];
        }
        $data=array();
        foreach($response["results"][0]["data"] as $i => $dataset){
            foreach($dataset["row"] as $j => $node){
                if(empty($node["name"]) && $dataset["meta"][$j]["type"]==="relationship"){
                    $node["name"]=$dataset["meta"][$j]["id"];
                }

                $data[$i][$j]=$node["name"];
            }
        }

        if(!empty($response["results"][0]["stats"])){
            $stats=$response["results"][0]["stats"];
        }

        return $response;
    }

    public function send(Neo4jTransaction $transaction){
        $url="http://".$this->host.":".$this->port."/db/data/transaction";
        $id=$transaction->getId();
        if($id!==null){
            $url=$url."/".$id;
        }

        //$statements=$transaction->getOpenStatements();
        $statements=$transaction->getStatements();
        //$successfulIndices=array();
        $data=array();
        foreach($statements as $i => $cypher){
            $data["statements"][]["statement"]=$cypher;
        }

        $json=json_encode($data);
        $response=$this->curlPost($url,$json);
        $response=json_decode($response,true);

        if($id===null){
            $uri=$response["commit"];
            //$uri=http://localhost:7474/db/data/transaction/10/commit
            $uri=substr($uri,0,strrpos("/"));
            $id=substr($uri,strrpos("/")+1);
            $transaction->setId($id);
        }

        if(count($response["errors"])){
            $errors=$transaction->getErrors();
            $errors=array_merge($errors,$response["errors"]);
            $transaction->setErrors($errors);
        }

        return $transaction;
    }

    public function refresh(Neo4jTransaction &$transaction){
        $id=$transaction->getId();
        $statements=array();
        $data=array("statements"=>$statements);
        $json=json_encode($data);
        $response=$this->curlPost("http://".$this->host.":".$this->port."/db/data/transaction/".$id,$json);
        return $response;
    }

    public function commit(Neo4jTransaction &$transaction){
        $id=$transaction->getId();
        $response=$this->curlPost("http://".$this->host.":".$this->port."/db/data/transaction/".$id."/commit");

        if(count($response["errors"])){
            $errors=$transaction->getErrors();
            $errors=array_merge($errors,$response["errors"]);
            $transaction->setErrors($errors);
            $transaction->setCompleted(false);
            return $transaction;
        }

        $transaction->setCompleted(true);
        return $transaction;
    }

    public function rollback(Neo4jTransaction &$transaction){
        $id=$transaction->getId();
        $response=$this->curlDelete("http://".$this->host.":".$this->port."/db/data/transaction/".$id);
        return $response;
    }

    public function setHost($host){
        $this->host=$host;
        return $this->host;
    }

    public function setPort($port){
        if(!is_numeric($port)){
            throw new Exception('parameter $port must be numeric');
        }

        $this->port=$port;
        curl_setopt($this->curlHandle, CURLOPT_PORT, $this->port);
        return $this->port;
    }

    public function setUser($user){
        $this->user=$user;
    }

    public function setPassword($password){
        $this->password=$password;
    }

    public function updateAuthentication(){
        curl_setopt($this->curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->curlHandle, CURLOPT_USERPWD, $this->user.":".$this->password);
    }

    private function curlGet($uri,$getoptions=null,array $options=array()){
        if(!empty($getoptions) && is_array($getoptions)){
            if(strpos($uri,"?")===false){
                $uri=$uri."?";
            }
            $uri=$uri.http_build_query($getoptions);
        }

        $defaults=array(
            CURLOPT_URL=>$uri,
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_TIMEOUT=>10
        );

        $headers=array(
            "Cache-Control: no-cache",
            "Accept: application/json; charset=UTF-8",
            "Content-Type: application/json; charset=UTF-8",
            "Content-Length: ".strlen($data),
            "X-Stream: true"
        );
        $defaults[CURLOPT_HTTPHEADER]=$headers;

        curl_setopt_array($this->curlHandle, ($defaults+$options));

        $response=curl_exec($this->curlHandle);
        if($response===false){
            trigger_error(curl_error($this->curlHandle));
        }
        return $response;
    }

    private function curlPost($uri,$data=null,array $options=array()){
        $defaults=array(
            CURLOPT_URL=>$uri,
            CURLOPT_POST=>1,
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FRESH_CONNECT=>1,
            CURLOPT_TIMEOUT=>10
            //CURLOPT_POSTFIELDS=>$postValues
        );

        $defaults[CURLOPT_CUSTOMREQUEST]="POST";

        if(!empty($data) && is_array($data)){
            $data=http_build_query($data);
            $defaults[CURLOPT_POSTFIELDS]=$data;
        }else if(!empty($data) && is_string($data)){
            //$defaults[CURLOPT_CUSTOMREQUEST]="POST";
            $defaults[CURLOPT_POSTFIELDS]=$data;
        }

        $headers=array(
            "Cache-Control: no-cache",
            "Accept: application/json; charset=UTF-8",
            "Content-Type: application/json; charset=UTF-8",
            "Content-Length: ".strlen($data),
            "X-Stream: true"
        );
        $defaults[CURLOPT_HTTPHEADER]=$headers;

        curl_setopt_array($this->curlHandle, ($defaults+$options));

        $response=curl_exec($this->curlHandle);
        if($response===false){
            trigger_error(curl_error($this->curlHandle));
        }
        return $response;
    }

    private function curlDelete($uri,$data=null,array $options=array()){
        $defaults=array(
            CURLOPT_URL=>$uri,
            //CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FRESH_CONNECT=>1,
            CURLOPT_TIMEOUT=>10
            //CURLOPT_POSTFIELDS=>$postValues
        );

        $defaults[CURLOPT_CUSTOMREQUEST]="DELETE";
        curl_setopt_array($this->curlHandle, ($defaults+$options));

        $response=curl_exec($this->curlHandle);
        if($response===false){
            trigger_error(curl_error($this->curlHandle));
        }
        return $response;
    }
}

?>
