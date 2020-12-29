<?php
require_once("sitelib.php");
require_once("autoloader.php");

class FileUpdater{

    protected $filepath;
    public $createBackup = true;
    
    public function __construct($filepath){
        //php supports no constructor overloading
        $this->setFilePath($filepath);
    }

    public function setFilePath($filepath){
        if(!file_exists($filepath)){
            throw new Exception('$filepath does not exist');
        }

        $this->filepath = $filepath;
    }

    protected function readFile($filepath){
        if(!file_exists($filepath)){
            throw new Exception('$filepath does not exist');
        }

        $contentList = file($filepath);
        return $contentList;
    }

    public static function convertArrayToString(array $object){
    	$string = 'array(';
    	$size = count($object);
        $index = 0;
        
        foreach($object as $key => $val){
            if(!is_int($key)){
                $key = '"'.$key.'"';
            }

    		if($index%4 === 0){
    			$string = $string."\n\t";
            }

            $string = $string.$key." => ".'"'.$val.'",';
            
            if($index+1 === $size){
				//last element
                $string = substr($string, 0, -1);
    		}
            
            $index++;
        }
        
        $string = $string.");"."\n\n";
        
        return $string;
    }

    public function replace($placeholder, $val){
        /// $placeholder = '//flagsection'

        if(is_int($val)){
            $val = strval($val);
        }

        $lines = $this->readFile($this->filepath);
        $newLines = array();
        
        foreach($lines as $line){
            $line = preg_replace("#.*".$placeholder.".*#", $val, $line);
            $newLines[] = $line;
        }
        
        if($this->createBackup){
            copy($this->filepath, $this->filepath.".bak");
        }
        
        $newLines = join("\n",$newLines);
        file_put_contents($this->filepath, $newLines);
    }
}
?>