<?php
//error_reporting(0);

require_once("./../config.php");

//force HTTPS
if($_SERVER['HTTPS'] != "on")
{
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

header("Content-type: text/javascript");
session_start();
$ret = array("status"=>"not authenticated", "reqId" => $_REQUEST['reqId']);

//ensure the user has been pre-authenticated
if(!empty($_SESSION['user']) && $_SESSION['user'] == $_REQUEST['user']) {
    $ret['status'] = "no data";
    
    if(isset($_REQUEST['isbn']) && isset($_REQUEST['system'])) {
        
        $ret['status'] = "recieved data";
        //Determine which system to request the item from

        $system = $_REQUEST['system'];
        
        if(!array_key_exists($system, $systems)) {
            $ret['status'] = "error";
            $ret['status'] = "Service Error: preferred request system not provided.";
        }
        else {
            $ret['system'] = $systems[$system]['name'];   
        }
        
        $isbn = trim(stripslashes($_REQUEST['isbn']));
        $requestURL = str_ireplace("$1", $isbn, $systems[$system]["request_url"]);

        $system = "ill";
        //Create request
        if($systems[$system]["request_method"] == "millenium")
        {
            MilleniumRequest($isbn, $requestURL); 
        }
        else {
            call_user_func($altRequestMethods[$systems[$system]["request_method"]], $isbn);
        }
    }
}

echo "requestCallback(".json_encode($ret).")";


function MilleniumRequest($isbn, $requestURL) {
    global $local, $ret;
    
    $fields = array();
    $fields['extpatid'] = $_REQUEST['user'];
    //$fields['extpatpw'] = $_REQUEST['password'];
    $fields['extpatpw'] = "asdfa";
    $fields['name'] = "";
    $fields['code'] = "";
    $fields['pin'] = "";
    $fields['campus'] = $local['campus_id'];
    $fields['loc'] = $local['req_location'];
    $fields['pat_submit'] = "xxx";

    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded",
            'method' => "POST",
            //'content' => "extpatid=gjr8050&extpatpw=jkd%3Bakasl%3B&name=&code=&pin=&campus=9ritu&loc=wcirc&pat_submit=xxx",
            'content' => http_build_query($fields)
        ),
    );

    echo http_build_query($fields);

    $context = stream_context_create($options);
    $result = file_get_contents($requestURL, false, $context);

    //parse response
    if(stristr($result, "ID number is not valid")) {
        $ret['status'] = "error";
        $ret['error'] = "Could not authenticate with the request server";
    }
    elseif(stristr($result, "success string")) {
        $ret['status'] = "complete";
    }
    else {
        $ret['status'] = "error";
        $ret['error'] = "Unexpected Error";
    }
    
    //attempt to execute the request through the fallback system
    if($ret['status'] == "error")
    {
        $fallback = false;
        foreach($system as $key => $system)
        {
            if(isset($system['fallback']))
            {
                $fallback = $system['request_method'];
            }
        }
        if($fallback)
        {
            call_user_func($altRequestMethods[$fallback], $isbn);
        }
    }
    
    echo $result;
}
?>