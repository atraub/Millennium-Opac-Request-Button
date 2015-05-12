<?php
//error_reporting(0);

require_once("request-functions.php");
require_once("cookie-write.php");
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

        //$system = "ill";
        //Create request
        if($systems[$system]["request_method"] == "millennium")
        {
            MillenniumRequest($isbn, $requestURL); 
        }
        else {
            call_user_func($customRequestMethods[$systems[$system]["request_method"]], $isbn);
        }
    }
}

echo "requestCallback(".json_encode($ret).")";


function MillenniumRequest($isbn, $requestURL) {
    global $local, $ret, $systems, $customRequestMethods;
    
    $ch = openCURLRequest();
    $result = CURLPost($ch, $requestURL, array('campus' => $local['campus_id']));
    $cookies = extractCookies($result);
    curl_close($ch);
    
    //make sure we got a session ID back
    if(!empty($cookies['III_SESSION_ID']))
    {

        $cookieWriter = new CookieJarWriter(dirname(__FILE__)."/cookie.txt", "connectny.info");
        $cookieStatus = $cookieWriter->addCookie("III_ENCORE_PATRON", "connectny.info");
        //echo $cookieStatus . "\r\n";
        
         $fields = array(
            'extpatid' => $_REQUEST['user'],
            'extpatpw' => $_REQUEST['password'],
            'name' => "",
            'code' => "",
            'pin' => "",
            'campus' => $local['campus_id'],
            'loc' => $local['req_location'],
            'pat_submit' => "xxx");
        
        $ch = openCURLRequest();
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__)."/cookie.txt");
        $result = CURLPost($ch, $requestURL, $fields);
    }

    //parse response
    if(stristr($result, "ID number is not valid")) {
        $ret['status'] = "error";
        $ret['error'] = "Could not authenticate with the " . strtoupper($_REQUEST['system']) . " request server";
    }
    elseif(stristr($result, "Item requested from")) {
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
        foreach($systems as $key => $system)
        {
            if(isset($system['fallback']))
            {
                $fallback = $system['request_method'];
            }
        }
        if($fallback)
        {
            //call_user_func($customRequestMethods[$fallback], $isbn);
        }
    }

}
?>