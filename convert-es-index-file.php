<?php

print_r($argv);

if (empty($argv[1])) {
    echo("please provide a filename as argument");
    exit(1);
}

$filename = $argv[1];
$json = file_get_contents($filename);
//print_r($json);
$dict = json_decode($json, true);
//print_r($dict);
$hitsList = $dict["hits"]["hits"];
$str = "";

foreach ($hitsList as $i => $hit) {
    //$timestamp = trim($hit["_source"]["@timestamp"]);
    $message = trim($hit["_source"]["message"]);

    //$str .= $timestamp." ".$message."\n";
    $str .= $message."\n";
}

$filename .= ".log";

if (!empty($argv[2])) {
    $filename = $argv[2];
}

if (file_exists($filename)) {
    $s = file_get_contents($filename);
    $str = $s.$str;
}

file_put_contents($filename, $str);

exit(0);

?>