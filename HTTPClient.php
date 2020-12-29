<?php

class HTTPClient {

    protected $url;
    protected $headers;
    protected $options;
    public $runtime;
    public $token;
    public $tokenKey;
    public $body;
    public $contentType;
    public $response;
    public $responseType;
    public $responseStatus;
    public $findingsset;
    public $findings;
    public $searchexceptions;
    public $debug;

    public function __construct(){
        $this->headers=array();
        $this->addHeaderEntry("Cache-Control","no-cache");
        $this->options=array();
        $this->options["ignore_errors"]=true;
        $this->options["timeout"]=60;
        $this->options["user_agent"]="cMon Agent/1.0 (Allianz Technology)";
        $this->findings=array();
        $this->debug=false;
    }

    public function initWithArray(array $arr){
        //prepare HTTP header
        if(empty($arr["header"])){
            //return array(ASEND_INFO,"HTTP header not defined");
            //throw new Exception("HTTP header not defined");
            $this->options["header"]=array();
        }

        if(is_array($arr["header"])){
            //$this->headers=array();
            foreach($arr["header"] as $key => $val){
                $this->addHeaderEntry($key,$val);
            }

            if(empty($arr["header"]["Cache-Control"]) && empty($this->headers["Cache-Control"])){
                $this->addHeaderEntry("Cache-Control","no-cache");
            }

            if(!empty($arr["header"]["Authorization"]) && is_string($arr["header"]["Authorization"])){
                $this->addHeaderEntry("Authorization",$arr["header"]["Authorization"]);
            }

        }else if(is_string($arr["header"])){
            if(strpos($arr["header"],"Cache-Control")===false && empty($this->headers["Cache-Control"])){
                //$this->addHeaderEntry("Cache-Control","no-cache");
                $arr["header"]=$arr["header"]."Cache-Control=no-cache\r\n";
            }

            if(strpos($param["header"],"#TOKEN#")!==false && isset($this->token)){
                //$paramRequest["header"]=str_replace("#TOKEN#",$authToken,$paramRequest["header"]);
                $arr["header"]=str_replace("#TOKEN#",$this->token,$arr["header"]);
            }

            if(strpos($arr["header"],"\r\n")!==false){
                $newHeaders=explode("\r\n",$arr["header"]);
                $this->headers=array_merge($this->headers,$newHeaders);
            }
            if(strpos($arr["header"]."\n")!==false){
                $newHeaders=explode("\n",$arr["header"]);
                $this->headers=array_merge($this->headers,$newHeaders);
            }
            /*
            if(!empty($param["response-status"]) && empty($param["ignore_errors"])){
                $param["header"]=$param["header"]."ignore_errors=true\r\n";
            }
            if(!empty($param["response-status"]) && empty($param["timeout"])){
                $param["header"]=$param["header"]."timeout=120\r\n";
            }
            */
        }

        if(!empty($arr["method"])){
            $this->options["method"] = strtoupper($arr["method"]);
        }

        //Content-Type
        //if(!empty($arr["header"]["Content-Type"])){}

        if(!empty($arr["body"])){
            $this->body=$arr["body"];
        }

        if(!empty($arr["response-status"])){
            $this->responseStatus=$arr["response-status"];

            if(empty($this->options["ignore_errors"])){
                $this->options["ignore_errors"]=true;
            }

            if(empty($this->options["timeout"])){
                $this->options["timeout"]=120;
            }
        }

        if(!empty($arr["response-type"])){
            $this->responseType=$arr["response-type"];

            if(empty($this->options["ignore_errors"])){
                $this->options["ignore_errors"]=true;
            }

            if(empty($this->options["timeout"])){
                $this->options["timeout"]=120;
            }
        }

        if(!empty($arr["proxy"])){
            if(empty($this->options["proxy"])){
                if(strpos($arr["proxy"],"tcp://")!==0){
                    $arr["proxy"]="tcp://".$arr["proxy"];
                }
                $this->options["proxy"]=$arr["proxy"];
            }

            if(empty($this->options["request_fulluri"])){
                $this->options["request_fulluri"]=false;
            }
        }

        if(!empty($arr["tokenkey"])){
            $this->tokenKey=$arr["tokenkey"];
        }

        //=== findings ===

        if(!empty($arr["findings"]) && is_array($arr["findings"])){
            $this->findingsset=$arr["findings"];
        }

        if(!empty($arr["findings"]) && is_string($arr["findings"])){
            if(substr($arr["findings"],0,1)==="[" && substr($arr["findings"],-1)==="]"){
                $this->findingsset=json_decode($arr["findings"],true);
            }

            if(substr($arr["findings"],0,1)==="{" && substr($arr["findings"],-1)==="}"){
                $this->findingsset=json_decode($arr["findings"],true);
            }
        }

        //=== searchexceptions ===

        if(!empty($arr["searchexceptions"]) && is_array($arr["searchexceptions"])){
            $this->searchexceptions=$arr["searchexceptions"];
        }

        if(!empty($arr["searchexceptions"]) && is_string($arr["searchexceptions"])){
            $this->searchexceptions=array();
            $this->searchexceptions=$arr["searchexceptions"];

            if(substr($arr["searchexceptions"],0,1)==="[" && substr($arr["searchexceptions"],-1)==="]"){
                $this->searchexceptions=json_decode($arr["searchexceptions"],true);
            }

            if(substr($arr["searchexceptions"],0,1)==="{" && substr($arr["searchexceptions"],-1)==="}"){
                $this->searchexceptions=json_decode($arr["searchexceptions"],true);
            }
        }

        if(!empty($arr["timeout"])){
            if(is_string($arr["timeout"])){
                $arr["timeout"] = intval($arr["timeout"]);
            }

            if(is_int($arr["timeout"]) || is_float($arr["timeout"])){
                $this->options["timeout"] = $arr["timeout"];
            }
        }

        if(isset($arr["request_fulluri"])){
            if(is_string($arr["request_fulluri"])){
                $strFullUri = strtolower($arr["request_fulluri"]);
                if($strFullUri === "false"){
                    $arr["request_fulluri"] = false;
                } else if($strFullUri === "true"){
                    $arr["request_fulluri"] = true;
                }
            }

            if(is_bool($arr["request_fulluri"])){
                $this->options["request_fulluri"] = $arr["request_fulluri"];
            }
        }

    }

    public function addHeaderEntry($key,$value){
        if(strpos($key,"Authorization")!==false && strpos($value,"Basic")!==false){
            $cred=substr($value,strlen("Basic "));
            $cred=base64_encode($cred);		//"Monitoring:HGQEbagT9WCf"
            $value="Basic ".$cred;
        }

        if(strpos(strtolower($key),"content-type")!==false){
            $this->contentType=$value;
        }

        $this->headers[$key]=$value;
    }

    public function get($url, &$headerRef=null){
        $this->url=$url;
        $this->options["method"]="GET";
        return $this->request($url, $headerRef);
    }

    public function post($url, &$headerRef=null){
        $this->url=$url;
        $this->options["method"]="POST";
        return $this->request($url, $headerRef);
    }

    public function request($url, &$headerRef=null){
        $urlCopy=$url;

        if(substr(strtolower($urlCopy),0,4)!=="http"){
            //$auth["address"]="http://".$auth["address"];
            $url="http://".$urlCopy;
        }
        if(substr(strtolower($urlCopy),0,1)===":"){
            $url="http://127.0.0.1".$urlCopy;
        }

        say(__FUNCTION__." | URL: ".$url);

        if(!empty($this->token)){
            $this->updateHeader("TOKEN",$this->token);
        }

        if(!empty($this->findings)){
            foreach($this->findings as $key => $value){
                $this->updateHeader($key,$value);
            }
        }

        $header="";
        foreach($this->headers as $key => $val){
            $header=$header.$key.":".$val."\r\n";
        }
        //$paramRequest["header"]=$header;
        $this->options["header"]=$header;

        if(!empty($this->body)){
            if(strpos($this->contentType,"application/x-www-form-urlencoded")!==false && is_array($this->body)){
                //TODO: set $this->options["header"]["Accept"]/$this->header["Accept"]
                $this->options["content"]=http_build_query($this->body);
            }else if(strpos($this->contentType,"application/json")!==false && is_array($this->body)){
                //TODO: set $this->options["header"]["Accept"]/$this->header["Accept"]
                $this->options["content"]=json_encode($this->body);
            }else if(is_array($this->body)){
                $requestBody="";
                foreach($this->body as $key => $val){
                    $requestBody=$requestBody.$key.":".$val."\n";
                }
                $this->options["content"]=trim($requestBody);
            }else{
                $this->options["content"]=$this->body;
            }
        }

        if(strpos($url,"[")!==false && strpos($url,"]")!==false && preg_match_all("#\[(.*?)\]#",$url,$matches)){
            say(__FUNCTION__." | found dynamic parts for URI: ".print_r($matches,true));
            foreach($matches[1] as $i => $s){
                if(!isset($this->findings[$s])){
                    return array(ASEND_INFO,"$s not found in prefetched data");
                }
                $replace=$this->findings[$s];
                $url=str_replace($matches[0][$i],$replace,$url);
            }
            say(__FUNCTION__." | override URI ".$url);
        }

        //$opts=array("http"=>$param);
        $opts["http"]=$this->options;

        if(strpos($url,"https://")===0){
            $opts["ssl"]=array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
                "SNI_enabled"=>false,
                "allow_self_signed"=>true
            );
        }

        if($this->debug){
            say(__FUNCTION__." | options: ".print_r($opts,true));
        }

        $ctx=stream_context_create($opts);

        $statime=microtime(true);
        $this->response=@file_get_contents($url,false,$ctx);
        $this->runtime=round(((microtime(true)-$statime)*1000),2);

        /*if($checkvalues==="" && !empty($this->responseStatus) && strpos($http_response_header[0],$this->responseStatus)!==false){
            //setMaintenanceFlag(false);
            //say(__FUNCTION__." | ".$endtime."ms");
            return $this->response;
        }*/

        if(!empty($http_response_header)){
            say(__FUNCTION__." | HTTP response header: ".print_r($http_response_header,true));
            $headerRef = $http_response_header;
        }else{
            say(__FUNCTION__." | HTTP response header is empty");
        }

        if($this->response===false){
            say(__FUNCTION__." | HTTP response is FALSE");
            return $this->response;
        }

        if($this->debug){
            say(__FUNCTION__." | response: ".print_r($this->response,true));
        }

        if(!empty($this->responseStatus) && strpos($http_response_header[0],$this->responseStatus)===false){
            $statusCode=false;
            $code=0;
            foreach($http_response_header as $i => $headerString){
                if(strpos($headerString,$this->responseStatus)!==false){
                    $statusCode=true;
                }
                if(strpos($headerString,"HTTP")!==false || is_numeric($headerString)){
                    $code=$this->strlonnumsubstr($headerString);
                    //$code=intval($code);
                    if($code=="" || $code==0) {
                        if(preg_match("/(\d\d\d)/i",$headerString,$match)===1) $code=$match[1];
                    }
                }
            }
            if(!$statusCode){
                say(__FUNCTION__ . " | HTTP response status: ".$http_response_header[0]." is not ".$this->responseStatus);

                //return array(ASEND_CRITICAL, "Request ".$url." failed - ".$http_response_header[0]);
                //return false;
                return $code;
            }
        }

        if(!empty($this->responseStatus) && strpos($this->responseStatus,"204")!==false && strpos($http_response_header[0],$this->responseStatus)!==false){
            say(__FUNCTION__." | ".$this->runtime."ms");
            return $this->response;
        }

        //save authorization

        if(!empty($this->responseType) && !empty($this->tokenKey) && !isset($this->token) && (strpos($this->responseType,"json")!==false || strpos($this->responseType,"xml")!==false) ){
            if(strpos($this->responseType,"xml")!==false){
                $xml=simplexml_load_string($this->response);
                if($xml===false){
                    say(__FUNCTION__." | error at load xml string");
                    //return array(ASEND_INFO,"No expected response at $url");
                    return false;
                }
                $this->response=json_encode($xml);
            }

            $response=json_decode($this->response,true);
            say(__FUNCTION__." | authentication JSON response: ".print_r($response,true));
            if(!empty($response[$this->tokenKey])){
                $this->token=$response[$this->tokenKey];
                $this->updateHeader("TOKEN",$this->token);
                //say(__FUNCTION__." | Updated header with found tokenkey:".print_r($this->headers,true));
                say(__FUNCTION__." | updated header with token ".$this->token);
            }
        }

        if($this->debug){
            say(__FUNCTION__." | headers: ".print_r($this->headers,true));
        }

        //auto configuration with findings

        if(!empty($this->findingsset) && (strpos($this->responseType,"json")!==false || strpos($this->responseType,"xml")!==false)){
            if(strpos($this->responseType,"xml")!==false){
                $xml=simplexml_load_string($this->response);
                if($xml===false){
                    //return array(ASEND_INFO,"No expected response at $url");
                    return false;
                }
                $this->response=json_encode($xml);
            }

            $response=json_decode($this->response,true);
            $exceptions=false;
            if(!empty($this->searchexceptions) && !empty($this->searchexceptions["val"])){
                $exceptions=$this->searchexceptions["val"];
                if($this->debug){
                    say(__FUNCTION__." | exceptions: ".print_r($exceptions,true));
                }
            }

            foreach($this->findingsset as $i => $searchKey){
                //==== use of pre-defined function recursiveSearch ====
                $this->findings=array_merge($this->findings,recursiveSearch($response,$searchKey,false,$exceptions));
                //=====================================================
            }

            if($this->debug){
                say(__FUNCTION__." | findings: ".print_r($this->findings,true));
            }

            if(!empty($this->findings)){
                //$headers=$this->headers;
                foreach($this->findings as $key => $value){
                    /*
                    foreach($headers as $i => $header){
                        if(strpos($header,"#$key#")!==false){
                            $this->headers[$i]=str_replace("#$key#",$value,$header);
                        }
                    }*/
                    $this->updateHeader($key,$value);
                }
            }
        }

        return $this->response;
    }

    protected function updateHeader($search,$replace){
        $headers=$this->headers;
        foreach($headers as $i => $header){
            if(strpos($header,"#$search#")!==false){
                $this->headers[$i]=str_replace("#$search#",$replace,$header);
            }
        }
    }

    private function strlonnumsubstr($string){
        $chars=str_split($string);
        $str="";

        foreach($chars as $i => $c){
            $s="";
            while($i < count($chars) && is_numeric($chars[$i])){
                $s=$s.$chars[$i];
                $i++;
            }
            if(strlen($s)>strlen($str)){
                $str=$s;
            }
        }

        return $str;
    }
}

/**
 * recursiveSearch searches recursive in @param arr (an array)
 * for a specific value definied by @param needle and @param $prop
 * @param array $arr
 * @param string or int $needle
 * @param array $prop
 *
 */
function recursiveSearch($arr,$needle,$prop=false,$exceptions=null){
    $holder = array();
    //if($exceptions !== false && !is_array($exceptions)){ $exceptions = array(); }
    //say(__FUNCTION__." | ".print_r($arr,true));

    if(!is_array($arr)){
        return $holder;
    }

    foreach($arr as $key => $val){
        //if(is_array($val) && !isset($prop["key"])){
        if(is_array($val) && !empty($exceptions) && is_array($exceptions)){
            $isException = false;
            foreach($exceptions as $excKey => $excVal){
                if($excKey === $key){
                    $isException = true;
                }
            }
            if(!$isException){
                $holder = array_merge($holder,internalRecSearch($val,$needle,$prop,$exceptions));
            }
        } else if(is_array($val)){
            $holder = array_merge($holder,recursiveSearch($val,$needle,$prop,$exceptions));
            //if(is_array($prop["key"]) && (array_key_exists($needle,$holder) || in_array($needle,$holder)) ){
            //if((is_array($prop["key"]) && in_array($prop["key"],$arr)) || (is_array($needle) && in_array($needle,$arr)) ){
            if((is_array($needle) && in_array($needle,$arr)) ){
                if(!isset($prop["value"])){
                    say(__FUNCTION__." | "."prop[\"value\"] parameter must be set if needle is an array");
                    return $holder;
                }
                //say(__FUNCTION__." | found needle in array");
                //say(__FUNCTION__." | ".$arr[$prop["value"]]);
                //$holder[] = $arr[$prop["value"]];
                $holder[$prop["value"]] = $arr[$prop["value"]];
                return $holder;
            }
        } else if(is_array($val) && isset($prop["key"]) && isset($val[$prop["key"]])){
            //wrong: {"OK":{"ldap":{"status":"UP"},"saml":{"status":"UP"},"diskSpace":{"status":"UP"},"db":{"status":"UP"}},"OBJECTS":{"status":"UP"}}
            //correct: {"OK":{"ldap":"UP","saml":"UP","diskSpace":"UP","db":"UP"},"OBJECTS":{"key":"status"}}
            //["ldap"]["status"] = "UP"
            //$holder[$key][$prop["key"]] = $val[$prop["key"]];
            $holder[$key] = $val[$prop["key"]];
        } else if($prop !== false && (isset($prop["key"]) && isset($prop["value"]))){
            //if($val === $needle && !is_array($prop["key"])){
            if($val === $needle && !is_array($needle)){
                //say(__FUNCTION__." | ".$val." = ".$needle);
                //$holder[$arr[$checkvalues["OBJECTS"]["key"]]] = $arr[$checkvalues["OBJECTS"]["value"]];
                $holder[$arr[$prop["key"]]] = $arr[$prop["value"]];
            }
            //continue;
        } else if($key === $needle && !empty($exceptions) && is_array($exceptions)){
            $ex = false;
            //say(__FUNCTION__." | ".$key."==".$needle);
            foreach($exceptions as $exKey => $exVal){
                if($exKey === $key || $exVal === $val){
                    $ex = true;
                }
            }
            if(!$ex){
                $holder[$key] = $val;
            }
        } else if($key === $needle){
            $holder[$key] = $val;
        }
    }

    return $holder;
}

?>