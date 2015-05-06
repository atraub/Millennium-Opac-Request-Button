<?php

//Info about local library
$local = array(
    "id" => "rit",
    "search_url" => "",
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
     * request_url:     Request URL format, indicating the position of item ISBN with $1
     * request_method:  "millenium", or the key to a custom defined request method
     */
    
    //key must be the same as the abbreviation
    "cny" => array(
        "abbr" => "cny",
        "name" => "Connect NY",
        "search_url" => "http://connectny.info/search~S0/?searchtype=i&searcharg=$1",
        "request_url" => "https://connectny.info/search~S0?/i$1/i$1/1,1,1,B/request&FF=i$1&1,1,",
        "request_method" => "millenium"
        ),
    "nex" => array(
        "abbr" => "nex",
        "name" => "New England Express",
        "search_url" => "http://nexp.iii.com/search~S3?/i$1/i$1/1,1,1,E/detlframeset&FF=i$1&1,1,",
        "request_url" => "https://nexp.iii.com/search~S3?/i$1/i$1/1%2C1%2C1%2CE/request&FF=i$1&1%2C1%2C",
        "request_method" => "millenium"
        ),
    "ill" => array(
        "abbr" => "nex",
        "name" => "IDS Illiad",
        "request_method" => "illiad",
        "fallback" => true
        )
    );


/*This array defines alternate functions to request materials*/
$altRequestMethods = array(
    "illiad" => function($isbn) {
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
    );
?>