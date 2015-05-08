<?php
//error_reporting(0);
header("Content-type: text/javascript");

$scripts = glob("*.js");

echo <<<HEADER
/*
* ITEM REQUEST AGGREGATION - JS PROXY
* Loads all JavaScript associated with the IRA module
*
* RIT Wallace Library
* 4/15/15
* Greg Rozmarynowycz
*/
HEADER;

require_once("js-config.php");

foreach($scripts as $script)
{
    echo <<<FILEHEADER
    
//=========================================================================================
// $script
//=========================================================================================\r\n
FILEHEADER;
    readfile($script);
}
?>