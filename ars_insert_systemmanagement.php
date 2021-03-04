<?php
/*
*	import-systemmanagement.php
*
*	written and maintained by philipp.schunker@allianz.at
*
*/

set_time_limit(0);

require_once("sitelib.php");
//require_once('../sitelib/incs/sitelib.php');
require_once(SITE_LIB."dbwrap.php");
require_once(SITE_LIB."dbtools.php");
require_once(SITE_ROOT."smgmt/cmdb/iu_ci.php");
require_once(SITE_LIB."astyle.php");
require_once(SITE_LIB."siteform.php");

if (empty($argv[1])) {
    echo("please provide a filename as argument");
    exit(1);
}

$filename = trim($argv[1]);

/*
$string = file_get_contents($filename);
$array = str_getcsv($string, "\n");

foreach($array as $line) {
    $array1 = str_getcsv($line, ";", "");
    //print_r($array1);

    if (!empty($array1[2])) {
        echo($array1[2]);
    }

    echo("-");

    if (!empty($array1[3])) {
        echo($array1[3]);
    }

    echo("\n");
    usleep(200000);
}
*/

$array = array();
$fh = fopen($filename, "r");

if ($fh === false) {
    echo("$filename could not be opened");
    exit(2);
}

$DB = GetDB();

$primaryKeys = array("Servername", "CfgParameter"); //"CFGPARAMETERTYPE", "CFGVALUE");
$i = 0;

while (($csv = fgetcsv($fh, 0, ";")) !== false) {
    echo($i);
    if ($i == 0) {
        $i++;
        continue;
    }

    print_r($csv);

    if (!empty($csv[2])) {
        echo($csv[2]);

        $data = array();
        $data['Servername'] = $csv[0];
        $data['CfgParameterType'] = "Report";
        $data["CfgParameter"] = "ReportCustomer";
        $data['CfgValue'] = $csv[2];
        $data["Status"]="active";
        //$data["Submitter"]=__FILE__;

        try {
            AR_IU_Table($DB, "SMGT:SystemManagement_CfgParameter", $primaryKeys, $data);
        } catch (Exception $e) {
            echo("\n");
            echo($e->getMessage());
        }
    }

    echo("-");

    if (!empty($csv[3])) {
        echo($csv[3]);

        $data = array();
        $data['Servername'] = $csv[0];
        $data['CfgParameterType'] = "Report";
        $data["CfgParameter"] = "ReportType";
        $data['CfgValue'] = $csv[3];
        $data["Status"]="active";
        //$data["Submitter"]=__FILE__;

        try {
            AR_IU_Table($DB, "SMGT:SystemManagement_CfgParameter", $primaryKeys, $data);
        } catch (Exception $e) {
            echo("\n");
            echo($e->getMessage());
        }
    }

    echo("\n");
    $i++;
    //usleep(400000); //400ms
}

fclose($fh);

exit(0);

?>
