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
        $validSystems = array("cny","nex","ill");
        $system = $_REQUEST['system'];
        
        if(!in_array($system, $validSystems)) {
            $ret['status'] = "invalid system";
        }

        if($system == "cny")
            $requestURL = "https://connectny.info/search~S0?/i{$isbn}/i{$isbn}/1,1,1,B/request&FF=i{$isbn}&1,1,";
        elseif($system = "nex")
            $requestURL = "https://nexp.iii.com/search~S3?/i{$isbn}/i{$isbn}/1%2C1%2C1%2CE/request&FF=i{$isbn}&1%2C1%2C";

        $isbn = trim(stripslashes($_REQUEST['isbn']));
        
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
                $ret['status'] = "cny auth fail";
            }
            elseif(stristr($result, "success string")) {
                $ret['status'] = "request complete";
            }
            else {
                $ret['status'] = "unknown error";
            }

            echo $result;

            break;
        case "ill":
            $ILLAuthenticateURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action%3D99";
            $requestURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action=10&Form=20&Value=GenericRequestGIST";
            
            /*
             * Get a session ID from the ILL Server
             */
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, $ILLAuthenticateURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            
            $result = curl_exec($ch);
            
            //get cookie sessionID
            $cookies;
            preg_match('/Set-Cookie:\s*([^;*])/mi', $result, $cookies);
            $ILLsessionID = $cookies['ILLiadSessionID'];
            
            echo $ILLsessionID;
            echo $cookies;
            echo $result;
            
            /*
             * Perform Item Request
             */
            if(!empty($ILLsessionID)) {
            
                /*
                 * Authenticate with the ILL Server
                 */
                $fields = array();
                $fields['ILLiadForm'] = "Logon";
                $fields['Username'] = $_REQUEST['user'];
                //$fields['Password'] = $_REQUEST['password'];
                $fields['Password'] = "asdfa";
                $fields['SubmitButton'] = "Logon to Illiad";
                
                $sessionCookie = "ILLiadSessionID=".$ILLsessionID;

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $ILLAuthenticateURL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_COOKIE, $sessionCookie);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

                $result = curl_exec($ch);

                curl_close($ch);


                //Authentication Fields (hidden)
                $fields = array();
                $fields['IlliadForm'] = "GenericRequestGIST";
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
                $fields['ISSN'] = $isbn;

                //Request Information
                $fields['CitedPages'] = "No";
                $fields['NotWantedAfter'] = date("m/D/Y", time() + 3600 * 24 * 60);
                $fields['GISTWEb.PurchaseRecommendation'] = "no";
                $fields['AcceptNonEnglish'] = "No";
                $fields['AcceptAlternateEditition'] = "Yes";
                $fields['GISTWeb.AlternativeFormat'] = "No";
                $fields['GISTWeb.Importance'] = "Unsure";
                $fields['Notes'] = "Generated through Albert Item Request Aggregation";

                $fields['SubmitButton'] = "Submit Request";
                
                

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $reqeustURL);
                curl_setopt($ch, CURLOPT_COOKIE, $sessionCookie);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));

                //$result = curl_exec($ch);

                curl_close();
            }

            break;
        }
    }
}

echo "requestCallback(".json_encode($ret).")";
?>