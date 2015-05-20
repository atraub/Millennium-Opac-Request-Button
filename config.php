<?php
/* ===================================================================================
 * Millennium Item Request Aggregation (MIRA) Config
 * ===================================================================================
 * 
 * Any changes to adapt MIRA to your system should be made here.
 * 
 * Authored by: Greg Rozmarynowycz
 * Rochester Institute of Technology
 * Wallace Center
 * Date: 5/11/15
 */

/* Local Library & Instituion Information
 * ------------------------------------------------------------------------------------
 * Details about your library and institution for the UI and to request items
 */
$local = array(
    //Name of your institution
    "institution" => "RIT",
    //Name of the library
    "lib_name" => "Wallace Library",
    //URL to search the local catalog by ISBN (use "$1" to indicate where the isbn goes)
    //This url should land on an individual item page
    "search_url" => "http://albert.rit.edu/search/i?SEARCH=$1",
    //Base color to style the dialog with (must be rgb(0,0,0) - rgb(255,255,255))
    "dialog_color" => "rgb(243,110,36)",
    
    //request variables - these can be found by inspecting the Millenium request page
    //These must be valid to complete a request
    
    //The ID of your institution in the Millenium System
    "campus_id" => "9ritu",
    //Where the material goes
    "req_location" => "wcirc",
    );

/* Authentication Function
 * -----------------------------------------------------------------------------------
 * Modify the below function to integrate with your authentication system
 * 
 * The only requirements for this function to intergrate with MIRA are:
 *      1)  it accepts a plain-text username and password as parameters
 *      2)  it returns boolean value indicating if the given credentials were valid
 */

//don't change this parameter list
function login($username, $password) {
    
    // modify things here to use your authentication system
    $authenticated = false;
    $dn = 'uid=' . $username . ',ou=people,dc=rit,dc=edu';
    $domain = 'ldaps://ldap.rit.edu';
    $connection = ldap_connect($domain);
    if($connection && !empty($password)) {
        $authenticated = @ldap_bind($connection, $dn, $password);
        @ldap_close($connection);
    }
    
    //this must be a boolean
    return $authenticated;
}

/* Millennium Systems
 * ------------------------------------------------------------------------------------
 * Other systems to request materials from
 */
$systems = array(
    /*
     * abbr:            3-4 letter abbreviation (this will be displayed to user)
     * name:            Full Name of Service
     * search_url:      Catalog search URL format, indicating the position of item ISBN with $1
     * request_url:     Request URL format, indicating the position of item ISBN with $1 (must be secured with SSL)
     * fulfillment:     Number of days the request is expected to take to be completed
     * request_method:  "millennium", or the key to a custom defined request method (only millennium systems can be checked for availibility)
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
        "request_method" => "millennium"
        ),
     "nex" => array(
        "abbr" => "nex",
        "name" => "New England Express",
        "search_url" => "http://nexp.iii.com/search~S3?/i$1/i$1/1,1,1,E/detlframeset&FF=i$1&1,1,",
        "request_url" => "https://nexp.iii.com/search~S3?/i$1/i$1/1%2C1%2C1%2CE/request&FF=i$1&1%2C1%2C",
        "fulfillment" => "7-10",
        "request_method" => "millennium"
        ),
    
    //Fallback system (non-millennium, needs custom request function)
    "ill" => array(
        "abbr" => "ill",
        "name" => "InterLibrary Loan",
        "request_method" => "illiad",
        "info_url" => "https://ill.rit.edu/ILLiad/Logon.html",
        "fulfillment" => "3-7",
        "fallback" => true
        )
    );

/* Custom Request Methods
 * ------------------------------------------------------------------------------------
 * This array defines alternate functions to request materials
 * 
 * Creating these methods is slightly more complicated, refer to the below method
 * for requesting items from RIT's InterLibrary Loan system. It is customized to that
 * system, but you can probably probably modify it to meet your needs.
 * 
 * The below method users cURL to talk to the remote server; it acts almost
 * exactly as a browser was being used to make the request.
 */
$customRequestMethods = array(
    /*Authenticates and creates request with the RIT IDS Illiad portal*/
    "illiad" => function($isbn) {
        
        //this is the array of values returned to the client dialog
        global $ret;
        //Tell the user what system the item is requested from
        $ret['system'] = "InterLibrary Loan";
    
        /*
         * Set options and initialize cURL request
         */
        //This is the url that authentication details are sent to to establish a session
        $ILLAuthenticateURL = "https://ill.rit.edu/ILLiad/illiad.dll?Action%3D99";
        //The url that that item request is sent to (once authenticated)
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
        
        //Execute the authentication request (this established a session with the remote user)
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
                'ISSN' => $isbn,

                //Request Information
                'CitedPages' => "No",
                'NotWantedAfter' => date("m/D/Y", time() + 3600 * 24 * 60), //60 days from now
                'GISTWEb.PurchaseRecommendation' => "no",
                'AcceptNonEnglish' => "No",
                'AcceptAlternateEditition' => "Yes",
                'GISTWeb.AlternativeFormat' => "No",
                'GISTWeb.Importance' => "Unsure",
                'Notes' => "Generated through Millennium Item Request Aggregation",

                'SubmitButton' => "Submit Request");

            $result = CURLPost($ch, $requestURL, $fields);

            curl_close($ch);
            
            //Check for string indicating success
            if(stristr($result, "Book Request Received"))
            {
                //get the request id from the response
                $transactionMatches;
                preg_match('/Transaction Number ([\d]+)/im', $result, $transactionMatches);
                $transactionID = $transactionMatches[1];

                if(!empty($transactionID))
                {
                    /*
                     * Were'not actually using this anymore because although it didn't cause a specific issue,
                     * ILLiad would throw an auth error when showing the login page, which is potentially confusing the the user
                     */
                    //$ret['requestURL'] = "https://ill.rit.edu/ILLiad/illiad.dll?Action=10&Form=63&Value=" . $transactionID;
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