/*
 * GIST AVAILIBILITY CHECKER
 * 
 * 12/5/2013
 * RIT Wallace Library
 * Greg Rozmarynowycz
 * 
 * Params:
 *		localLib: plain text name of the library to find records relative to
 *			ex. "RIT"
 *		localUrl: the search url of the local library's catalog search, with the location of the ISBN replaced with a "$1"
 *			ex. "http://albert.rit.edu/search/i?SEARCH=$1"
 *		removeDuplicates: whether or not to retrieve duplicate records
 *			true or false
 *		localOnly: wheter to display results from additional libraries if is held by the localLib
 *			true or false
 *		container: the container that the resulting records should be displayed in
 *			Id of container, ex. "container", or [object HTMLDivElement], etc.
 *		
 */
function availibilityChecker(params){
	//register parameters
	params = params || {};
	this.localLib = params.localLibName || "";
	this.localUrl = params.localSearchUrl || "";
	this.localOnly = (typeof(params.localOnly) != "undefined") ? params.localOnly : true;
	this.removeDuplicates = (typeof(params.removeDuplicates) != "undefined") ? params.removeDuplicates : true;
	this.container = params.container || document.body;
	this.cssPath = params.cssPath || "";
	this.BNContainer = params.BNContainer || null;
    this.servicePath = params.servicePath || "";
	
	//Pull in the stylesheet for the records
	var link = document.createElement('link');
		link.type = "text/css";
		link.rel = "stylesheet";
		link.href= this.cssPath+"css/item-availibility.css";
	document.getElementsByTagName("head")[0].appendChild(link);
	
	//define other properties
	this.data = null;
	this.isbn = null;
	this.displayed = false;
}

/*
 * This function sends a request to the server to retrieve availbility information
 * Params:
 *		callback: the function to call before the data is displayed when using the default display
 *		displayCallback: alternative display call back function
 *		isbn: the isbn to lookup if not as a url parameter
 */
availibilityChecker.prototype.retrieveData = function(params){
	
	params = params || {};
	var dispCallback = params.displayCallback || this.displayData;
	var callback = params.callback || null;
	this.isbn = params.isbn || this.getUrlVars().isbn;
    
    
	var isbn = this.isbn;
    console.log(this.getUrlVars());
	
	//console.log(callback);
	
	/*
	 * Using JSONP to retrieve the data from the server because it does not have
	 * cross domain restrictions like AJAX. This basically requests a script which
	 *  passes the data through when it loads and is executed.
	 * 
	 * About JSONP: http://stackoverflow.com/questions/2067472/what-is-jsonp-all-about
	 */
	$j.getJSON(this.servicePath+"includes/availibility-checker.php?"+
		"isbn="+isbn+
		"&removeDuplicates="+(this.removeDuplicates << 0)+
		"&localLib="+encodeURIComponent(this.localLib)+
		"&localUrl="+encodeURIComponent(this.localUrl)+
		"&localOnlyIfAvail="+(this.localOnly << 0)+"&callback=?");
	
	//This function is called by the requested script when it loads
	iraCallback = (function(checker, dispCallback, callback){
		return function(data){
			checker.data = data;
			checker.callback = callback;
			
			if(checker.callback){
				checker.callback.call(checker);
			}
			
			dispCallback.call(checker);
		}
	})(this, dispCallback, callback)
	
}
//The default function to display data after it is retrieved, can be overriden
availibilityChecker.prototype.displayData = function(){
    console.log("rendering");
    
	if(!this.displayed){
		this.displayed = true;
		var resp = document.createElement('div');
			resp.className = 'ac-resp'

		var data = this.data;

		$j(resp).append('<h4>This item is:</h4>');

		if(this.data.reqStatus == 'success'){
            
			var altLibsDisplayed = false;

			//This only occurs if the search request returns a browse search results page
			if(data.localMultiple){

				//container
				var div = document.createElement('div');
					div.className = "ac-record ac-local";
				//header
				var label = document.createElement('label');
					label.innerHTML = this.localLib;

				var link = document.createElement('a');
					link.href = data.urls[0];
					link.className = "multiple";
					link.innerHTML = this.localLib+" owns mulitple versions/copies of this item";

				//if localNotFound is true, then because of similar page structures, localMultiple will also be true
				if(!data.localNotFound){
					div.appendChild(label);
					div.appendChild(link);
					resp.appendChild(div);
				}
			}
            
            registeredTags = {}
            
            console.log(this.displayed);
			for(r in data.records){
                
                record = data.records[r];

				//This displays the link to connectNY for alternate libraries to the local
				if(record.lib != this.localLib && !altLibsDisplayed){
					if(data.otherLibs){
						var and = (data.localAvail == true) ? "and " : "Held by ";
						$j(resp).append("<span class='alt-libs'>"+and+" <a target='_blank' href='http://connectny.info/search~/a?searchtype=i&searcharg="+this.isbn+"&SORT=A&x=19&y=7'>"+data.otherLibs+" other external libraries</a></span>")
					}
					altLibsDisplayed = true;
				}
                
				//the container for the record
                var div = $j("<div>").addClass("ac-record");
                
                //If this record is either from the local library or is available (don't bother with external unavailable)
				if(!(record.lib != this.localLib && record.status.toLowerCase() != "available")){
                    
                    //Mark as unavailable
					if(record.status.toLowerCase() != "available" && record.status.toLowerCase() != "electronic resource"){
						$j(div).addClass("ac-unavail");
					}

					//The header of the record
                    var label = $j("<label>").html(record.lib);
                    
					//table containing record data
                    var table = $j("<table>").append(
                        //Labels for the record data
                        $j("<tr>").append(
                            $j("<td>").html("Location"),
                            $j("<td>").html("Call Number"),
                            $j("<td>").html("Status")
                        ),
                        //record data
                        $j("<tr>").append(
                            $j("<td>").html(record.loc),
                            $j("<td>").html(record.call),
                            $j("<td>").html(record.status)
                        )
                    ).appendTo(div);
                    
					//Check if the last record is from the same library, and if so, don't display a library label
                    if(!(data.records[r-1] && record.lib == data.records[r-1].lib)){
                        $j(div).prepend(label);
                    }
                    
                    //link to the local catalog
					if(record.lib == this.localLib){
                        console.log(r);
                        
						$j(div).addClass("ac-local");
                        console.log(r);
						$j(label).html(
                            $j("<a>")
                                .html(this.localLib)
                                .attr("target","_blank")
                                .attr("href", this.localUrl.replace("$1",this.isbn))
                        );
                        console.log(r);
                        $j(resp).append(div);
					}
                    else {
                        console.log(r);
                        if(!registeredTags[record.system])
                        {
                            registeredTags[record.system] = $j("<section>")
                                .append("<h4>"+record.system.toUpperCase()+"</h4>")
                                .addClass(record.system);
                        }

                        $j(registeredTags[record.system]).append(div);
                    }
                    
                    
                    
                    for(var s in registeredTags)
                    {
                        $j(resp).append(registeredTags[s]);
                    }
					
                    
                    
				}
			}
            
            console.log(this.displayed);
		}
		//If no records were returned
		else {
			switch(data.reqStatus){
				case "not found":+
					$(resp).append("<div class='ac-record'>Not held in any external libraries</div>");
					break;
				default:
					resp.innerHTML = data.reqStatus;
					break;
			}
		}

		if(!altLibsDisplayed){
			if(data.otherLibs){
				var and = (data.localAvail == true) ? "and " : "Held by ";
				$j(resp).append("<span class='alt-libs'>"+and+" <a target='_blank' href='http://connectny.info/search~/a?searchtype=i&searcharg="+this.isbn+"&SORT=A&x=19&y=7'>"+data.otherLibs+" other external Libraries</a></span>")
				altLibsDisplayed = true;
			}
			else if(data.altMultiple && !data.altNotFound){
				$j(resp).append("<span class='alt-libs'>Multiple versions owned by <a target='_blank' href='http://connectny.info/search~/a?searchtype=i&searcharg="+this.isbn+"&SORT=A&x=19&y=7'>other external Libraries</a></span>")
				altLibsDisplayed = true;
			}
		}
		
		//check if the record is available at Barnes & Noble @ RIT
		if(data.bookStoreData && data.bookstoreData.available == true){
			var bnCont = (this.BNContainer) ? this.BNContainer : resp;
			$j(bnCont).append("<div class='ac-record'><a target='_blank' href='"+data.bookstoreData.link+"'>Barnes & Noble @RIT</a> ("+data.bookstoreData.price+")</div>");
		}
        
        console.log(resp);
		//Append the response to the body
		$j(this.container).append(resp);
	}
}
availibilityChecker.prototype.clearDisplay = function(){
	$j(this.container).html("");
	this.displayed = false;
}
availibilityChecker.prototype.getUrlVars = function() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}