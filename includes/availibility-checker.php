<?php
/*
 * GIST AVAILIBILITY CHECKER
 * 
 * 12/5/2013
 * RIT Wallace Library
 * Greg Rozmarynowycz
 * 
 * VALID URL PARAMTERS:
 * isbn: 
 *		the isbn of the publication
 *		ex: 9781452226101
 * localLib [optional]: 
 *		name of the local library, *required if localUrl is given
 *		ex: "RIT"
 * localUrl [optional]: 
 *		format of url that searches catalog for isbn ('$1' goes where isbn would be)
 *		ex: http://albert.rit.edu/search/i?SEARCH=$1
 * getCNYifAvail [optional]: 
 *		tell the script to check CNY if the book is found available in the local library
 *		1 or leave empty
 * 
 * RECORD PAGE FORMATTING:
 * To check a local library catalog, availibility record pages MUST have the following format:
 * <tr class="bibItemsEntry">
 *		<td>[Location]</td>
 *		<td>[Call #]</td>
 *		<td>[Availibility]</td>
 * </tr>
 */
error_reporting(0);
$ret = array("reqStatus" => 'error');
header('Content-Type: text/javascript', 1);

if(isset($_GET['isbn'])){
	$ret['reqStatus'] = 'invalid isbn';
	
	$ret['isbn'] = $isbn = $_GET['isbn'];
	$localLib = trim($_GET['localLib']);
	$localUrl = trim($_GET['localUrl']);
	$removeDuplicates = trim($_GET['removeDuplicates']);
	
	$localAvail = false;
	if(is_valid_isbn($isbn)){
		
		$req['reqStatus'] = 'invalid library parameters';
		if((!empty($localLib) && !empty($localUrl)) || empty($localUrl)){
			
			$ret['reqStatus'] = 'script error';
			$ret['textContent2'] = '';
			
			$urls = array();
			if(!empty($localUrl)){
				$urls["local"] = $localISBNUrl = preg_replace('/\$1/', $isbn, $localUrl);
			}
            $urls["nex"] = "http://nexp.iii.com/search~S3?/i$isbn/i$isbn/1,1,1,E/detlframeset&FF=i$isbn&1,1,";
			$urls["cny"] = "http://connectny.info/search~S0/?searchtype=i&searcharg=$isbn";
			$ret['urls'] = $urls;

			$ret['records'] = array();
			$ret['altMultiple'] = false;
			$ret['altNotFound'] = false;
			$ret['localNotFound'] = false;
			$ret['localMultiple'] = false;
			$ret['classes'] = array();
			
			$ret['reqStatus'] = 'not found';
			//loop through each of the URLs provided and look for records
			foreach($urls as $system => $url){
				$dom = new DOMDocument();
				$dom->loadHTMLFile($url);
				
				//Loop through each of table rows on the page
				$trs = $dom->getElementsByTagName("tr");
				foreach($trs as $tr){
					$class = $tr->getAttribute('class');
					//array_push($ret['classes'], $class);
					$local = false;
					$electronicRes = false;
					//If a record is found a local library catalog page
					if($class == "bibItemsEntry"){
						$local = true;
						$tds = $tr->getElementsByTagName('td');

						$lib = $localLib;
						$loc = trim(DOMinnerHTML($tds->item(0)));

						if(stristr($loc, 'href')){
							preg_match("/<a href\=[\"\'](.*)?[\"\'].*>(.*)?<\/a>(.*)?/", $loc, $matches);
							//locUrl is unused
							list($junk, $locUrl, $loc, $loc2) = array_map(
									trim_all, 
									array_map(strip_tags, $matches));
							$loc .= $loc2;
						}

						$call = DOMinnerHTML($tds->item(1));

						if(stristr($call, 'href')){
							preg_match("/(.*)?<a href\=[\"\'](.*)?[\"\'].*>(.*)?<\/a>(.*)?/", $call, $matches);
							//$callUrl is unused
							list($call1, $junk, $junk1, $call, $call2) = array_map(
									trim_all, 
									array_map(strip_tags, $matches));
							$call = $call.$call2;
						}

						/*
						 * The library catalogs have weird formatting that causes issues with the PHP
						 * DOM parser, notably converting &nbsp; (non-breaking-spaces) that are used
						 * for formatting the catalog pages, into multiple characters: 
						 * 32, 194, 160 (these are the ascii character codes)
						 * These must be removed, because they make it impossible to compare data
						 */
						$chars = "";
						for($s = 0; $s < strlen($call); $s++){
							$charCode = ord($call[$s]);

							if($charCode == 194 && ord($call[$s+1]) == 160){
								$s++;
							}
							else {
								$chars .= $call[$s];
							}
						}
						$call = trim($chars);
						//End of character stripper

						$status = trim($tds->item(2)->nodeValue);
						$status = substr($status, 2);
						
						if($lib == $localLib && strtolower($status) == "available"){
							$localAvail = true;
						}
					}
                    //If multiple item records exists for the same ISBN
					elseif($class == "browseEntry"){
						if($url == $localISBNUrl){
							$ret['localMultiple'] = true;
							$localAvail = ($ret['localNotFound']) ? false : true;
						}
						else {
							$ret['altMultiple'] = true;
						}
					} 
                    //If the item cannot be found in the catalog
					elseif($class == "yourEntryWouldBeHere"){
						if($url == $localISBNUrl){
							$ret['localNotFound'] = true;
							$localAvail = false;
						}
						else {
							$ret['altNotFound'] = true;
						}
					}
					//If a record is found on the connectNY search page
					elseif(stristr($class, "holdings")){
						$tds = $tr->getElementsByTagName('td');
                        
                        //extract the data from the table row
						$lib = trim($tds->item(0)->nodeValue);
						$loc = trim($tds->item(1)->nodeValue);
                        $link = trim($tds->item(2)->nodeValue);
						$call = trim($tds->item(3)->nodeValue);
						$status = trim($tds->item(4)->nodeValue);
						
						if(strlen($link) > 3 && strtolower($status) != "available"){
							$status = "Electronic Resource";
						}

						if($lib == $localLib) $class = '';
					}
					else {
						$trValue = $tr->textContent;
						//$ret['textContent'].=$trValue;
						if(stristr($trValue, "Available electronically") && $url == preg_replace('/\$1/', $isbn, $localUrl)){
							$lib = $localLib;
							$loc = "";
							$call = "";
							$status = "Electronic Resource";
							$electronicRes = true;
						//	$ret['textContent2'] .= $class."XXX";
						}
					}
					
					if(stristr($class, "holdings") || $class == "bibItemsEntry" || $electronicRes){
						$data = array('lib' => $lib, 'loc'=>$loc, 'call'=>$call, 'status'=>$status, 'localCat' => $local, "system" => $system);
						$ret['records'][] = $data;
						$ret['reqStatus'] = 'success';
					}
				}
			}
		
			//determine how many non-local libraries records are present
			$localRecords = array();
			$otherLibs = array();
            $libAvailCount = 0;
			for($r = 0; $r < count($ret['records']); $r++){
				if($ret['records'][$r]['localCat']){
					$localRecords[] = $ret['records'][$r];
				}
				else {
					if($ret['records'][$r]['lib'] != $localLib){
						$otherLibs[] = $ret['records'][$r]['lib'];
                        if(strtolower($ret['records'][$r]['status']) == "available")
                        {
                            $libAvailCount++;   
                        }
					}
				}
			}
			$otherLibs = array_unique($otherLibs);
			if($localAvail && $_GET['localOnlyIfAvail']){
				$ret['records'] = $localRecords;
			}
			$ret['localAvail'] = $localAvail;
			$ret['totalOtherLibs'] = count($otherLibs);
            $ret['otherLibs'] = $libAvailCount;
			}
			
			//Remove duplicate records
			if($removeDuplicates){
				$tempArray = array_map(serialize, $ret['records']);
				$tempArray2 = array_unique($tempArray);
				$ret['records'] = array_map(unserialize, $tempArray2);
			}
			
			//Retrieve information about the records availibility at RIT Barnes & Noble
			$bookstoreUrl = 'http://' + $_SERVER['HTTP_HOST'] + '/depts/assets/worldcat/bncollege.php?isbn=$1';
			$requestUrl = preg_replace('/\$1/', $isbn, $bookstoreUrl);
			$response = json_decode(file_get_contents($requestUrl));

			$ret['bookstoreData'] = $response;
	}
}

echo "iraCallback(".json_encode($ret).")";

function DOMinnerHTML(DOMNode $element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
} 


function is_valid_isbn($isbn_number)
{
  $isbn_digits  = array_filter(preg_split('//', $isbn_number, -1, PREG_SPLIT_NO_EMPTY), '_is_numeric_or_x');
  $isbn_length  = count($isbn_digits);
  $isbn_sum     = 0;
 
  if((10 != $isbn_length) && (13 != $isbn_length))
  { return false; }
 
  if(10 == $isbn_length)
  {
    foreach(range(1, 9) as $weight)
    { $isbn_sum += $weight * array_shift($isbn_digits); }
 
    return (10 == ($isbn_mod = ($isbn_sum % 11))) ? ('x' == mb_strtolower(array_shift($isbn_digits), 'UTF-8')) : ($isbn_mod == array_shift($isbn_digits));
  }
 
  if(13 == $isbn_length)
  {
    foreach(array(1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 1, 3) as $weight)
    { $isbn_sum += $weight * array_shift($isbn_digits); }
 
    return (0 == ($isbn_mod = ($isbn_sum % 10))) ? (0 == array_shift($isbn_digits)) : ($isbn_mod == (10 - array_shift($isbn_digits)));
  }
 
  return false;
}
 
function _is_numeric_or_x($val)
{ return ('x' == mb_strtolower($val, 'UTF-8')) ? true : is_numeric($val); }

function trim_all( $str , $what = NULL , $with = ' ' )
{
    if( $what === NULL )
    {
        //  Character      Decimal      Use
        //  "\0"            0           Null Character
        //  "\t"            9           Tab
        //  "\n"           10           New line
        //  "\x0B"         11           Vertical Tab
        //  "\r"           13           New Line in Mac
        //  " "            32           Space
       
        $what   = "\\x00-\\x20";    //all white-spaces and control chars
    }
   
    return trim( preg_replace( "/[".$what."]+/" , $with , $str ) , $what );
}
?>
