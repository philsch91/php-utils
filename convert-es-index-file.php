<?php



print_r($argv);

// shell
if (!empty($argv) && empty($argv[1])) {
    echo("please provide a filename as argument");
    exit(1);
}

if (!empty($argv[1])) {
    $filename = $argv[1];
    $json = file_get_contents($filename);
}

// webserver
if (!empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents("php://input");
}

if ($json == false || empty($json)) {
    echo("Error: missing input");
    exit(1);
}

if (!isset($json)) {
    echo("Exception: missing JSON string");
    exit(2);
}

//print_r($json);
$dict = json_decode($json, true);

if ($dict === null) {
    echo("DeserializationError: invalid JSON string");
    exit(3);
}

//print_r($dict);
$hitsList = $dict["hits"]["hits"];
$str = "";

foreach ($hitsList as $i => $hit) {
    //$timestamp = trim($hit["_source"]["@timestamp"]);
    $message = trim($hit["_source"]["message"]);

    //$str .= $timestamp." ".$message."\n";
    $str .= $message."\n";
}

if (!empty($filename)) {
    // shell
    $filename .= ".log";
} else {
    // webserver
    $filename = time().".log";
}

echo("filename is ".$filename);

if (!empty($argv[2])) {
    $filename = $argv[2];
}

if (file_exists($filename)) {
    $s = file_get_contents($filename);
    $str = $s.$str;
}

$bytes = file_put_contents($filename, $str);

if ($bytes === false) {
    echo("FileWriteException: could not write ".$filename);
    exit(4);
}

echo($filename." was successfully written");

exit(0);

?>