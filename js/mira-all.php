<?php
//error_reporting(0);
header("Content-type: text/javascript");

echo <<<HEADER
/*
* MILLENNIUM ITEM REQUEST AGGREGATION - JS PROXY
* Loads all JavaScript associated with the IRA module
*
* RIT Wallace Library
* 4/15/15
* Greg Rozmarynowycz
*/
HEADER;

$scripts = glob("*.js");
foreach($scripts as $script)
{
    //skip the initializiation script
    if(stristr($script, "mira-init"))
    {
        continue;   
    }
    
    echo <<<FILEHEADER
    
//=========================================================================================
// $script
//=========================================================================================\r\n
FILEHEADER;
    readfile($script);
}
?>