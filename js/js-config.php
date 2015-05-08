<?php
//error_reporting(0);
require_once("../config.php");
header("Content-type: text/javascript");

//determine the server & path where MIRA is being hosting from
$servicePathPcs = explode("/", $_SERVER['REQUEST_URI']);
//get rit of the filename and js directory
array_pop($servicePathPcs);
array_pop($servicePathPcs);
//combine the pieces together
$servicePath = "https://" . $_SERVER['SERVER_NAME'] . implode("/", $servicePathPcs) . "/";

$fallback = array();
foreach($systems as $key => $system)
{
    if($system['fallback'])
    {
        $fallback = $system;   
    }
}

$systemsJSON = json_encode($systems);

echo <<<JAVASCRIPT
//Dynamic Configuration Properties
local = {
    name: "{$local['institution']}",
    searchUrl: "{$local['search_url']}",
    libName: "{$local['lib_name']}",
    
    servicePath: "{$servicePath}",
    fallback: "{$fallback['abbr']}"
}

systems = $systemsJSON
JAVASCRIPT;
?>