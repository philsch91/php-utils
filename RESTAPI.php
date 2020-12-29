<?php
/*
*   Class for usage in ReSTful API scripts and routines
*
*   philipp.schunker@allianz.at
*/

require_once("sitelib.php");
require_once(SITE_LIB."dbtools.php");
require_once("autoloader.php");

class RESTAPI{

    public $method="";
    public $request="";
    public $endpoint="";
    public $argv=array();
    public $requestBody="";
    public $callfunction;
    protected $contentType="application/json";
    private $authDelegate=null;
    private $newFormat=false;

    public function __construct($request=""){
        if($request!==""){
            $this->endpoint=$request;
            $this->argv=explode("/",rtrim($request,"/"));
        }

        $this->method=$_SERVER["REQUEST_METHOD"];
        //if($this->method=="POST" && array_key_exists("HTTP_X_HTTP_METHOD",$_SERVER)){
        if($this->method=="POST" && isset($_SERVER["HTTP_X_HTTP_METHOD"])){
            if($_SERVER["HTTP_X_HTTP_METHOD"]!="DELETE" && $_SERVER["HTTP_X_HTTP_METHOD"]!="PUT"){
                throw new Exception("Unexpected Header");
            }
            $this->method=$_SERVER["HTTP_X_HTTP_METHOD"];
        }

        if($this->method=="GET"){
            $this->request=$this->cleanData($_GET);
        }else if($this->method=="POST"){
            $this->request=$this->cleanData($_POST);
            $this->requestBody=file_get_contents("php://input");
        }else if($this->method=="PUT"){
            $this->request=$this->cleanData($_GET);
            $this->requestBody=file_get_contents("php://input");
        }
    }

    protected function setFormat($val){
        if(!is_bool($val)){
            return false;
        }
        $this->newFormat=$val;
        return true;
    }

    public function getFormat(){
        return $this->newFormat;
    }

    private function cleanData($data){
        $cleanData=array();
        if(!is_array($data)){
            return trim($data);
        }
        foreach($data as $key=>$val){
            $cleanData[$key]=$this->cleanData($val);
        }
        return $cleanData;
    }

    protected function response($msg,$status=200,array $headers=null){
        $statuscodes=array(
            200=>"OK",
            401=>"Unauthorized",
            403=>"Forbidden",
            404=>"Not Found",
            405=>"Method Not Allowed",
            500=>"Internal Server Error"
        );
        header("HTTP/1.1 ".$status." ".$statuscodes[$status]);
        if($headers!==null){
            foreach($headers as $i => $header){
                header($header);
            }
        }

        header("Content-Type: ".$this->contentType);

        $response=$msg;

        if($this->newFormat===false){
            $response=array();
            $response["status"]=$status;
            if($msg===""){
                $response["message"]=$statuscodes[$status];
            }
            if($msg!==""){
                //print_r("message");
                $response["message"]=$msg;
            }
        }

        if($this->contentType==="application/json"){
            $response=json_encode($response);
        }
        //ob_clean();
        //ob_end_clean();
        $length=strlen($response);
        header("Content-Length: ".$length);
        echo($response);

		return $response;
    }

    public function processAPI($function=""){
        $data=null;
        if($this->authDelegate!==null){
            if($this->authDelegate->checkAuth()===false){
                $headers=array();
                $headers[]=$this->authDelegate->getAuthHeader();
                return $this->response("",401,$headers);
            }
        }
        $response=array();
        if($function!==""){
            //API in Script
            if(!function_exists($function)){
                return $this->response("Endpoint Not Implemented",500);
            }
            $data=$function($this->request);
        }else{
			//$api->processAPI()
            if(!empty($this->argv) && $this->argv[0]!==""){
				//echo(print_r($this->argv,true));        //TODO: delete
				foreach($this->argv as $key => $val){
					$function=$function."/".$val;
					$function=ltrim($function,"/");
					//print_r($function);              //TODO: delete
					//API in Class
					if(method_exists($this,$function)){
                        //echo("method/endpoint exists"); //TODO: delete
						try{
							$data=$this->{$function}($this->request);
                            //echo("data: ".$data);        //TODO: delete
						}catch(Exception $e){
							return $this->response($e->getMessage(),500);
						}
						break;
					}elseif(function_exists($function)){
                        //API in File
						try{
							$data=$function($this->request);
						}catch(Exception $e){
							return $this->response($e->getMessage(),501);
						}
						break;
					}
				}
			}else{
				// GET/POST/PUT/DELETE
				//return $this->response($this->method,200);
				//return $this->response($this->argv,200);
				if(method_exists($this,$this->method)){
					try{
						$data=$this->{$this->method}($this->request);
					}catch(Exception $e){
						return $this->response($e->getMessage(),502);
					}
				}else if(function_exists($this->method)){
					$function=$this->method;
					try{
						$data=$function($this->request);
					}catch(Exception $e){
						return $this->response($e->getMessage(),503);
					}
				}
			}

            if(!isset($data)){
                return $this->response("Endpoint Not Implemented",504);
            }
        }

        if(is_array($data) && count($data)===0){
            return $this->response("Not Found",404);
        }
        if(is_array($data) && isset($data["message"]) && isset($data["status"])){
            return $this->response($data["message"],$data["status"]);
            //return $this->response("Internal Server Error",500);
        }
        if(is_array($data) && isset($data["message"])){
            return $this->response($data["message"]);
        }

        return $this->response($data);
    }

    public function setAuthDelegate(ISessionAuth $delegate){
        $this->authDelegate=$delegate;
    }

}

?>
