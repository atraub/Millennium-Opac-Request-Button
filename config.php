<?php

//Info about local library
$local = array(
    "id" => "rit",
    "institution" => "RIT",
    "lib_name" => "Wallace Library",
    "search_url" => "http://albert.rit.edu/search/i?SEARCH=$1",
    //request varialbes
    "campus_id" => "rit9",
    "req_location" => "wcirc",
    );

//Millenium Systems
$systems = array(
    /*
     * abbr:            3-4 letter abbreviation, this will be displayed to user
     * name:            Full Name of Service
     * search_url:      Catalog search URL format, indicating the position of item ISBN with $1
     * request_url:     Request URL format, indicating the position of item ISBN with $1 (must be secured with SSL)
     * fulfillment:     Number of days the request is expected to take to be completed
     * request_method:  "millenium", or the key to a custom defined request method (only millenium systems can be checked for availibility)
     * fallback:        Indicates which system should be used if the item is not found or if a request fails
     * info_url:        (Only for fallback) URL where user can get more information about the service
     */
    
    //key must be the same as the abbreviation
    "cny" => array(
        "abbr" => "cny",
        "name" => "Connect NY",
        "search_url" => "http://connectny.info/search~S0/?searchtype=i&searcharg=$1",
        "request_url" => "https://connectny.info/search~S0?/i$1/i$1/1,1,1,B/request&FF=i$1&1,1,",
        "fulfillment" => "7-10",
        "request_method" => "millenium"
        ),
    "nex" => array(
        "abbr" => "nex",
        "name" => "New England Express",
        "search_url" => "http://nexp.iii.com/search~S3?/i$1/i$1/1,1,1,E/detlframeset&FF=i$1&1,1,",
        "request_url" => "https://nexp.iii.com/search~S3?/i$1/i$1/1%2C1%2C1%2CE/request&FF=i$1&1%2C1%2C",
        "fulfillment" => "7-10",
        "request_method" => "millenium"
        ),
    "ill" => array(
        "abbr" => "ill",
        "name" => "InterLibrary Loan",
        "request_method" => "illiad",
        "info_url" => "https://ill.rit.edu/ILLiad/Logon.html",
        "fulfillment" => "3-7",
        "fallback" => true
        )
    );


/*This array defines alternate functions to request materials*/
$customRequestMethods = array(
    /*Authenticates and creates request with the RIT IDS Illiad portal*/
    "illiad" => function($isbn) {
        global $ret;
    
        /*
         * Set options and initialize cURL request
         */
        $ILLAuthenticateURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action%3D99";
        $requestURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action=10&Form=20&Value=GenericRequestGIST";
        
        //Open a cURL request
        $ch = openCURLRequest();

        /*
         * Authenticate with the ILL Server
         */
        $fields = array(
            'ILLiadForm' => "Logon",
            'Username' => $_REQUEST['user'],
            'Password' => $_REQUEST['password'],
            'SubmitButton' => "Logon to ILLiad" //This is crucial to making a successful ILL request
        );    
        
        $result = CURLPost($ch, $ILLAuthenticateURL, $fields);
        
        //capture the sessionID
        $cookies = extractCookies($result);
        $ILLsessionID = $cookies['ILLiadSessionID'];

        /*
         * Perform Item Request
         */
        if(!empty($ILLsessionID)) {
            
            $fields = array(
                //Authentication Fields
                'ILLiadForm' => "GenericRequestGIST",
                'Username' => $_REQUEST['user'],
                'SessionID' => $ILLsessionID,

                //Hidden fields
                'RequestType' => "Loan",
                'CallNumer' => "",
                'GISTWeb.Group3Libraries' => "",
                'GISTWeb.Group2Libraries' => "",
                'GISTWeb.FullTextURL' => "",
                'GISTWeb.LocallyHeld' => "no",
                'GISTWeb.AmazonPrice' => "",
                'GISTWeb.BetterWorldBooksPrice' => "",
                'EPSNumber' => "",
                'GISTWEb.Delivery' => "Hold at Service Desk",
                'CitedIn' => "",

                //Item information
                'LoanTitle' => "",   
                'LoanAuthor' => "",
                'LoanPublisher' => "",
                'LoanDate' => "",
                'LoanEdition' => "",
                'DocumentType' => "Book",
                //'ISSN' = $isbn;

                //Request Information
                'CitedPages' => "No",
                'NotWantedAfter' => date("m/D/Y", time() + 3600 * 24 * 60),
                'GISTWEb.PurchaseRecommendation' => "no",
                'AcceptNonEnglish' => "No",
                'AcceptAlternateEditition' => "Yes",
                'GISTWeb.AlternativeFormat' => "No",
                'GISTWeb.Importance' => "Unsure",
                'Notes' => "TEST REQUEST - Generated through Albert Item Request Aggregation",

                'SubmitButton' => "Submit Request");

            $result = CURLPost($ch, $requestURL, $fields);

            curl_close($ch);
            
            //Check for string indicating success
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
    );


?>