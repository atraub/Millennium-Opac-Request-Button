<?php
//error_reporting(0);

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
        $validSystems = array(
            "cny" => "Connect NY",
            "nex" => "New England Express",
            "ill" => "IDS ILLiad"
        );
        $system = $_REQUEST['system'];
        
        if(!array_key_exists($system, $validSystems)) {
            $ret['status'] = "error";
            $ret['status'] = "Service Error: preferred request system not provided.";
        }
        else {
            $ret['system'] = $validSystems[$system];   
        }
        
        $isbn = trim(stripslashes($_REQUEST['isbn']));
        
        if($system == "cny")
            $requestURL = "https://connectny.info/search~S0?/i{$isbn}/i{$isbn}/1,1,1,B/request&FF=i{$isbn}&1,1,";
        elseif($system = "nex")
            $requestURL = "https://nexp.iii.com/search~S3?/i{$isbn}/i{$isbn}/1%2C1%2C1%2CE/request&FF=i{$isbn}&1%2C1%2C";
        
        $system = "ill";
        //Create request
        switch($system)
        {
            
        case "cny":
        case "nex":

            $fields = array();
            $fields['extpatid'] = $_REQUEST['user'];
            //$fields['extpatpw'] = $_REQUEST['password'];
            $fields['extpatpw'] = "asdfa";
            $fields['name'] = "";
            $fields['code'] = "";
            $fields['pin'] = "";
            $fields['campus'] = "9ritu";
            $fields['loc'] = "wcirc";
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
                $ret['error'] = "Could not authenticate with the CNY/NEX server";
            }
            elseif(stristr($result, "success string")) {
                $ret['status'] = "complete";
            }
            else {
                $ret['status'] = "error";
                $ret['error'] = "Unexpected Error";
            }
            
            if($ret['status'] == "error")
            {
                IlliadRequest($isbn);
            }

            echo $result;

            break;
        case "ill":
            IlliadRequest($isbn);
            break;
        }
    }
}

echo "requestCallback(".json_encode($ret).")";

function ILLiadRequest($isbn)
{
    global $ret;
    
    /*
     * Set options and initialize cURL request
     */
    $ILLAuthenticateURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action%3D99";
    $requestURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action=10&Form=20&Value=GenericRequestGIST";

    $curl_options = array(
        CURLOPT_RETURNTRANSFER => true,     /* return web page */
        CURLOPT_HEADER         => true,     /* don't return headers */
        CURLOPT_FOLLOWLOCATION => true,     /* follow redirects */
        CURLOPT_ENCODING       => "",       /* handle all encodings */
        CURLOPT_AUTOREFERER    => true,     /* set referer on redirect */
        CURLOPT_CONNECTTIMEOUT => 120,      /* timeout on connect */
        CURLOPT_TIMEOUT        => 120,      /* timeout on response */
        CURLOPT_MAXREDIRS      => 10,       /* stop after 10 redirects */
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_COOKIEJAR      => "cookie.txt",
        //were going to be acting as a Firefox client to eliminate any possible issues
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0'
    );

    $ch = curl_init();
    curl_setopt_array($ch, $curl_options);

    /*
     * Authenticate with the ILL Server
     */
    $fields = array();
    $fields['ILLiadForm'] = "Logon";
    $fields['Username'] = $_REQUEST['user'];
    $fields['Password'] = $_REQUEST['password'];
    $fields['SubmitButton'] = "Logon to ILLiad";    //This is crucial to making a successful request

    //Setup Headers 
    curl_setopt($ch, CURLOPT_URL, $ILLAuthenticateURL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Connection: keep-alive',
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate'
    ));
    curl_setopt($ch, CURLOPT_REFERER, $ILLAuthenticateURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    //curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $result = curl_exec($ch);

    //$headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    //echo http_build_query($fields);
    //echo "Request Headers: " . $headers;

    /*
     * Extract the sessionID cookie from the headers
     */
    //Get the string of cookies from headers
    $cookieMatches;
    preg_match_all('/^Set-Cookie:(.*)/im', $result, $cookieMatches);
    //var_dump($cookieMatches);
    $cookieString = $cookieMatches[1][0];

    //extract the cookie kes and values
    $rawCookies;
    preg_match_all('/(\s?([^;]+)=([^;]+);?)/i', $cookieString, $rawCookies);

    //make an associative array of the cookies
    $cookies = array();
    for($c = 0; $c < count($rawCookies[2]); $c++)
    {
        $cookies[$rawCookies[2][$c]] = $rawCookies[3][$c];
    }
    //capture the sessionID
    $ILLsessionID = $cookies['ILLiadSessionID'];

    //echo $ILLsessionID;
    //var_dump($cookies);
    //echo $result;

    /*
     * Perform Item Request
     */
    if(!empty($ILLsessionID)) {
        //Authentication Fields (hidden)
        $fields = array();
        $fields['ILLiadForm'] = "GenericRequestGIST";
        $fields['Username'] = $_REQUEST['user'];
        $fields['SessionID'] = $ILLsessionID;

        //Hidden fields
        $fields['RequestType'] = "Loan";
        $fields['CallNumer'] = "";
        $fields['GISTWeb.Group3Libraries'] = "";
        $fields['GISTWeb.Group2Libraries'] = "";
        $fields['GISTWeb.FullTextURL'] = "";
        $fields['GISTWeb.LocallyHeld'] = "no";
        $fields['GISTWeb.AmazonPrice'] = "";
        $fields['GISTWeb.BetterWorldBooksPrice'] = "";
        $fields['EPSNumber'] = "";
        $fields['GISTWEb.Delivery'] = "Hold at Service Desk";
        $fields['CitedIn'] = "";

        //Item information
        $fields['LoanTitle'] = "";
        $fields['LoanAuthor'] = "";
        $fields['LoanPublisher'] = "";
        $fields['LoanDate'] = "";
        $fields['LoanEdition'] = "";
        $fields['DocumentType'] = "Book";
        //$fields['ISSN'] = $isbn;

        //Request Information
        $fields['CitedPages'] = "No";
        $fields['NotWantedAfter'] = date("m/D/Y", time() + 3600 * 24 * 60);
        $fields['GISTWEb.PurchaseRecommendation'] = "no";
        $fields['AcceptNonEnglish'] = "No";
        $fields['AcceptAlternateEditition'] = "Yes";
        $fields['GISTWeb.AlternativeFormat'] = "No";
        $fields['GISTWeb.Importance'] = "Unsure";
        $fields['Notes'] = "TEST REQUEST - Generated through Albert Item Request Aggregation";

        $fields['SubmitButton'] = "Submit Request";

        //set additional request headers (were still using the same cURL connection)
        curl_setopt($ch, CURLOPT_URL, $requestURL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

        $result = curl_exec($ch);

        curl_close($ch);

        if(stristr($result, "Book Request Received"))
        {
            $transactionMatches;
            preg_match('/Transaction Number ([\d]+)/im', $result, $transactionMatches);
            $transactionID = $transactionMatches[1];

            if(!empty($transactionID))
            {
                $ret['requestURL'] = "https://ill.rit.edu/ILLiad/illiad.dll?Action=10&Form=63&Value=" . $transactionID;
                $ret['requestURL'] = "https://ill.rit.edu/ILLiad/illiad.dll?Action=99";
                $ret['status'] = "complete";
            }
            else {
                $ret['error'] = "Could not extract Transaction ID from ILLiad response.";
            }
        }
        else {
            $ret['error'] = "Error creating ILLiad Request";
            $ret['responseText'] = $result;
        }
    }
    else {
        $ret['error'] = "User could not be authenticated with ILLiad service";
    }

}
?>