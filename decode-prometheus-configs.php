<?php

print_r($argv);

// shell
if (!empty($argv) && empty($argv[1])) {
    echo("please provide a filename as argument"."\n");
    exit(1);
}

// api/v1/targets

if (!empty($argv[1])) {
    $filename = $argv[1];
    $json = file_get_contents($filename);
}

$dict = json_decode($json, true);
$csv = "Job;URL;Instance;Job2;Path;ScrapeURL"."\n";

foreach($dict["data"]["activeTargets"] as $i => $target){
    $job = $target["discoveredLabels"]["job"];
    $url = $target["discoveredLabels"]["__address__"];
    $instance = $target["labels"]["instance"];
    $job2 = $target["labels"]["job"];
    $path = $target["discoveredLabels"]["__metrics_path__"];
    $scrapeUrl = $target["scrapeUrl"];

    $csv .= $job.";".$url.";".$instance.";".$job2.";".$path.";".$scrapeUrl."\n";
}

$filename .= ".csv";

$bytes = file_put_contents($filename, $csv);

if ($bytes === false) {
    echo("FileWriteException: could not write ".$filename."\n");
    exit(4);
}

echo($filename." was successfully written"."\n");

// api/v1/rules

if(!empty($argv[2])) {
    $rulesFile = $argv[2];
    $rulesJson = file_get_contents($rulesFile);
}

$rulesDict = json_decode($rulesJson, true);
$rulesCsv = "Name;Query;Type;Summary;Severity"."\n";

foreach($rulesDict["data"]["groups"] as $i => $group) {
    foreach($group["rules"] as $j => $rule) {
        //print_r($rule);
        $name = $rule["name"];
        $query = $rule["query"];
        $type = $rule["type"];
        $summary = $rule["annotations"]["summary"];
        $severity = $rule["labels"]["severity"];

        $rulesCsv .= $name.";".$query.";".$type.";".$summary.";".$severity."\n";
    }
}

$rulesFile .= ".csv";

$bytes = file_put_contents($rulesFile, $rulesCsv);

if ($bytes === false) {
    echo("FileWriteException: could not write ".$rulesFile."\n");
    exit(4);
}

echo($rulesFile." was successfully written"."\n");

exit(0);

?>