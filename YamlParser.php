<?php

require_once("sitelib.php");
require_once(SITE_LIB."dbtools.php");
require_once("autoloader.php");

class YamlParser{

    public function __construct(){
    }

    protected function getLineEnding($string){
        $lineEnd = "\n";

        if(substr($string,strlen($string)-2)=="\n\r"){
            $lineEnd = "\n\r";
        }else if(substr($string,strlen($string)-2)=="\r\n"){
            $lineEnd = "\r\n";
        }else if(substr($string,strlen($string)-1)=="\r"){
            $lineEnd = "\r";
        }else if(substr($string,strlen($string)-1)=="\n"){
            $lineEnd = "\n";
        }

        return $lineEnd;
    }

    public function decode($string, $depth=0){
        if(!is_string($string)){
            throw new Exception('$string is not a string');
        }

        $lineEnd = $this->getLineEnding($string);
        $lines = explode($lineEnd,$string);
        $lines = array_filter($lines);
        $lindex = count($lines) - 1;
        $i = 0;
        $sectionIndex = 0;

        $data = array();

        while($i <= $lindex){
            $line = $lines[$i];
            
            if(strpos($line,":")){
                $lineArr = explode(":",$line);
                $lineArrCopy = $lineArr;
                $key = array_shift($lineArrCopy);
                $key = trim($key);
                $val = implode(":",$lineArrCopy);
                $val = trim($val);

                if($val === ""){
                    //$lineChars = str_split($lineArr[0]);
                    $nextLine = $lines[$i+1];
                    $lineChars = str_split($nextLine);
                    $j = 0;
                    $charCount = count($lineChars) - 1;
                    $prefix = "";
                    
                    while($j <= $charCount && ($lineChars[$j] === "\t" || $lineChars[$j] === " ")){
                        $j++;
                    }
                    
                    //used $lineArr[0] previously
                    $prefix = substr($nextLine,0,$j);
                    //say(__FUNCTION__." | prefix.length: ".strlen($prefix));

                    $str = "";
                    $k = $i + 1;
                    //say(__FUNCTION__." | substr: ".substr($lines[$k],0,strlen($prefix)));

                    //used !== previously
                    while(substr($lines[$k],0,strlen($prefix)) === $prefix){
                        $str .= $lines[$k]."\n";
                        $k++;
                    }

                    $val = $this->decode($str,$depth+1);   //used $k previously
                    $i = $k - 1;
                }

                if(is_array($val) && $depth === 0){
                    //say(__FUNCTION__." | val: ".print_r($val,true));
                    /*
                    $dataCopy = $data[$key];
                    unset($data[$key]);
                    $data[][$key] = $dataCopy;
                    $data[][$key] = $val; */
                    
                    //$data[][$key] = $val;

                    $data[$sectionIndex][$key] = $val;
                } else {
                    $data[$key] = $val;
                }

                //$data[$key] = $val;
            }

            if(strpos($lines[$i],"---") === 0 && $depth === 0){
                $sectionIndex++;
            }

            $i++;
        }
        
        return $data;
    }
}

?>