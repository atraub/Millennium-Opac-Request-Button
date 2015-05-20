<?php

echo <<<HEADER
/*
* MILLENNIUM ITEM REQUEST AGGREGATION - INITIALIZE
*
* RIT Wallace Library
* 4/15/15
* Greg Rozmarynowycz
*/
HEADER;

require_once("js-config.php");

?>

if(typeof(jQuery) == "undefined")
{
    var script = document.createElement("script");
        script.type = "text/javascript";
        script.onload = loadMIRA;
        script.src = "https://code.jquery.com/jquery-2.1.3.min.js";
               
    document.getElementsByTagName("body")[0].appendChild(script);
}
else {
    loadMIRA();
}

function loadMIRA()
{
    console.log("loading MIRA");
    $j = jQuery.noConflict();
    MIRA_INTERCEPTED_LINKS = false;
    MIRA_DIALOG_OPEN = false;
    
    $j("body").append(
        $j("<script>")
            .attr("type","text/javascript")
            .attr("src",local.servicePath + "/MIRA-JS")
        );
}
    