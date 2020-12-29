<?php
/*
*   Class PushNotifier
*
*   philipp.schunker@allianz.at
*/

require_once("sitelib.php");
require_once(SITE_LIB."dbtools.php");

class PushNotifier{

    /*
	private $logfile="/srv/www/htdocs/smgmt/batch/log/PushNotifier.log";
	private $logging=false;
	private $notify=false;
	private $rec="monitoring-betrieb@allianz.at";
	private $stopwatch=0;
    */
    private $mem=null;
    private $mailGroups=null;
    private $DB=null;

    /*
	function __construct(){
        //$$this->mem=json_decode(file_get_contents("/srv/www/htdocs/api/pn/pns.mem"),true);
	    //$this->mailgroups=json_decode(file_get_contents("/srv/www/htdocs/api/pn/groups.json"),true);
	}*/

    function __construct($userTable="TPUSH_USERS",$groupsTable="TPUSH_GROUPS"){
        $this->DB=GetDB();
        //print_r($this->DB);
        $this->DB->useException();

        $this->DB->Select("SELECT USERID FROM $userTable");
        $allUserIDs=$this->DB->GetRows();       //GetFirst()
        $userIDs=array();
        foreach($allUserIDs as $i => $id){
            $userIDs[$i]=$id[0];
        }

        foreach($userIDs as $i => $id){
            $this->DB->Select("SELECT IP,PORT,MACHINE,TS,EMAIL FROM $userTable WHERE USERID='".$id."'");
            $rows=$this->DB->GetRows();

            foreach($rows as $i => $row){
                $machine=$row[2];
                $this->mem[$id][$machine]["ip"]=$row[0];
                $this->mem[$id][$machine]["port"]=$row[1];
                $this->mem[$id][$machine]["ts"]=$row[3];
                $this->mem[$id][$machine]["email"]=$row[4];
            }
        }

        //print_r($this->mem);

        $this->DB->Select("SELECT distinct(NAME) FROM $groupsTable");
        $gnames=$this->DB->GetRows();
        $groups=array();
        foreach($gnames as $i => $name){
            $groups[$i]=$name[0];
        }

        foreach($groups as $i => $gname){
            $this->DB->Select("SELECT EMAIL FROM $groupsTable WHERE NAME='".$gname."'");
            $maddresses=$this->DB->GetRows();
            /*
            $maddresses=GetRows()
            $maddresses[0][0]=test1@allianz.at
            $maddresses[1][0]=test2@allianz.at
            */

            foreach($maddresses as $j => $addr){
                $this->mailGroups[$gname][]=$addr[0];
            }
            /*
            $maddress=$this->DB->GetFirst();
            $this->mailGroups[$gname][]=$maddress;
            */
        }
        //print_r($this->mailGroups);
        $this->DB->Disconnect();
    }

    public function LoadMemory($memPath){
        if(!is_readable($memPath)){
            echo("$memPath is not readable\n");
            return false;
        }
        $this->mem=json_decode(file_get_contents($memPath),true);
        if($this->mem===false){
            echo("could not parse $memPath as JSON\n");
            return false;
        }
        return true;
    }

    public function LoadGroups($mailGrpsPath){
        if(!is_readable($mailGrpsPath)){
            echo("$mailGrpsPath is not readable\n");
            return false;
        }
        $this->mailGroups=json_decode(file_get_contents($mailGrpsPath),true);
        //print_r($this->mailGroups);
        if($this->mailGroups===false){
            echo("could not parse $mailGrpsPath as JSON\n");
            return false;
        }
        return true;
    }

    public function Ready(){
        if($this->mem===null || $this->mailGroups===null){
            return false;
        }
        if($this->mem===false || $this->mailGroups===false){
            return false;
        }
        if( !count($this->mem) || !count($this->mailGroups) ){
            return false;
        }

        return true;
    }

    public function Push(&$pushMsg,$out=true){
        $receivers=array();
        //$oriReceivers=$pushMsg->GetReceiver();

        $receivers=$this->setReceivers($pushMsg->GetReceiver());

        $data=array();
        $data["msg"]=$pushMsg->GetMessage();
	    $data["level"]=$pushMsg->GetLevel();

        $json=json_encode($data);

        if($out){
            say(__FUNCTION__." | JSON: ".$json);
        }

        foreach($receivers as $i => $id){
            foreach($this->mem[$id] as $machine => $client){
                $socket=socket_create(AF_INET,1,SOL_TCP);
                socket_set_block($socket);
                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>0, 'usec'=>750));
                socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>0, 'usec'=>750));
                //if(!@socket_connect($socket,$client["ip"],$client["port"])){
                if(!@socket_connect($socket,$this->mem[$id][$machine]["ip"],$this->mem[$id][$machine]["port"])){
                    $sockErrCode=socket_last_error();
                    $sockErrMsg=socket_strerror($sockErrCode);
                    $sockInProgress=false;

                    if(strpos(strtolower($sockErrMsg),"in progress")!==false){
                        $sockInProgress=true;
                    }

                    if(!$sockInProgress){
                        if($out){
                            say(__FUNCTION__ ." | can not contact ".$id." IP: ".$this->mem[$id][$machine]["ip"]." Port: ".$this->mem[$id][$machine]["port"]."\n($sockErrCode) $sockErrMsg");
                        }
                        socket_close($socket);
                        continue;
                    }
                }

                $bytes=socket_send($socket,$json,strlen($json),MSG_EOF);

                if(!$bytes && $out){
                    say(__FUNCTION__ ." | error sending notification to ".$id);
                }

                if($bytes && $out){
                    say(__FUNCTION__ ." | sent notification to ".$id);
                }

                socket_close($socket);
            }
        }

        return 0;
    }

    private function setReceivers($recs){
        $receivers = array();

        foreach($recs as $i => $addr){
            if(strpos($addr,"@")>0){
                //addr is a email address

                //$addr=at-portale-betrieb@allianz.at
                //echo($addr);
                if(strpos($addr,"at-")===0){
			        $addr=substr($addr,3);
		        }
                //$addr=portale-betrieb@allianz.at

                /*
                $addr=substr($addr,0,strpos($addr,"@"));
                if(isset($this->mailGroups[$addr]) || isset($this->mailGroups["at-".$addr])){
                    //email address is mailgroup
                }
                */

                foreach($this->mailGroups as $grpname => $members){
                    if(strpos($grpname,"at-")===0){
                        $grpname=substr($grpname,3);
                    }
                    if(strpos($addr,$grpname)!==false){
                        //strpos(portale-betrieb@allianz.at,portale-betrieb)
                        //email address is current mailgroup
                        foreach($members as $memberAddr){
                            $userids=$this->getUserIDs($memberAddr);

                            if(!empty($userids)){
                                foreach($userids as $ix => $uid){
                                    if(!in_array($uid,$receivers)){
                                        $receivers[]=$uid;
                                    }
                                }
                            }
                        }
                    }
		        }

                //add non-group email address
                $userids=$this->getUserIDs($addr);

                if(!empty($userids)){
                    foreach($userids as $ix => $uid){
                        if(!in_array($uid,$receivers)){
                            $receivers[]=$uid;
                        }
                    }
                }
                //end of email to id conversion
            }else if( isset($this->mem[strtoupper($addr)]) ){
                //addr is a userID
                if(!in_array(strtoupper($addr),$receivers)){
                    $receivers[]=strtoupper($addr);
                }
            }
        }

        return $receivers;
    }

    private function getUserIDs($address){
        $uids=array();

        if($this->mem === null){
            say(__FUNCTION__." | Internal memory not set");
            return false;
        }

        //description: $this->mem[$id][$i]["email"]=$dataset[4];
        foreach($this->mem as $id => $client){
            foreach($client as $machine => $prop){
                if($prop["email"]===$address){
                    $uids[]=$id;
                }
            }
        }

        return $uids;
    }

    //TODO: Implement function for setting ExceptionHandling

}

?>
