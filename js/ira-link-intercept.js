/*
Script to hijack Millennium OPAC's "Request Button"
Redirect to appropriate services
Author: Greg Rozmarynowycz
Adapted from: Adam Traub
https://github.com/atraub/Millennium-Opac-Request-Button/blob/master/request.js
Special Thanks to:  Drew Filipski @ https://github.com/snixer724
*/

/* 
Pseudo-Code
	Checked Out:
		No Holds Present:  
			Less Than 5 Days:  Direct to Millennium Hold
			5 or More Days:  Direct to ILL/CNY
		Holds Present:  Direct to ILL/CNY
		
		FUTURE CONSIDERATION:  If checked out to ILL Patron, just ILL/CNY
	Other Statuses:  Direct to ILL/CNY
	ILL Sample Link:  https://ill.rit.edu/illiad/illiad.dll/OpenURL?sid=info%3Asid%2Fsersol%3ARefinerQuery&genre=book&title=The+hobbit%2C+or%2C+There+and+back+again+%2F+by+J.R.R.+Tolkien&atitle=&volume=&part=&issue=&date=&spage=&epage=&isbn=9780618002214&aulast=&aufirst=&espnumber=&LoanPublisher=&LoanPlace=&LoanEdition=
*/

/* Silly Variables */

var $j = jQuery.noConflict();

$j(document).ready(function() {
    
    //check for search results cells and request links
    var recordCells = $j("td.briefcitCell");
    var requestLinks = $j("[href*='request~'], [href*='requestbrowse~']");
    
    //don't go any further if there's no request links to intercept
    if(requestLinks && requestLinks.length > 0)
    {
        debugLog("req-links");
        //if we have search result cells, intercept the indivual links
        if(recordCells && recordCells.length > 0)
        {
            debugLog(recordCells);
            $j(recordCells).each(function(){
                //extract record info
                var isbn = $j(".gbs", this).text().trim().split(" ", 1)[0];
                var title = $j(".briefcitTitle", this).text().split("/", 1)[0];
                title = $j.trim(title);
                
                var recordReqLinks = $j("[href*='request~'], [href*='requestbrowse~']", this);

                if(isbn && title && recordReqLinks && recordReqLinks.length > 0)
                {
                    interceptLink(this, isbn, title);    
                }
            });
        }
        //otherwise this is a record page and we need can search the whole thing
        else {
            debugLog("single-page");
            //extract info
            var isbn = $j(".bibDisplayContentMore .bibInfoData:first").text();
            var title = $j(".bibDisplayTitle:first .bibInfoData").text().split("/",1);

            if(isbn && title)
            {
                interceptLink(document, isbn, title);
            }

        }
    }
   
});

function debugLog(statement)
{
    var logStatus = true;
    if(logStatus)
    {
        console.log(statement);   
    }
}

function interceptLink(context, isbn, title)
{
    debugLog("checking local item availibility");
    var altLink = "https://ill.rit.edu/illiad/illiad.dll/OpenURL?sid=ritcatalog&genre=book&title=" + encodeURI(title) + "&atitle=&volume=&part=&issue=&date=&spage=&epage=&isbn=" + encodeURI(isbn) + "&aulast=&aufirst=&espnumber=&LoanPublisher=&LoanPlace=&LoanEdition=";

	//Determine how far in the future the material is due
    var daysBetween = 0;
    var dueDate = $j("td:contains('DUE')", context).text().trim();
    
	if(dueDate){
        var dueStamp;
        
        //extract due date 
        //if browsers that support it (chrome), automatically extract timestamp
        if(!(dueStamp = new Date(dueDate).valueOf()))
        {
            //for browsers that aren't chrome...
            //split up pieces (dd-mm-yy)
            var datePcs = dueDate.replace("DUE ", "").split("-");
            
            var dueMo = datePcs[0];
            var dueDay = datePcs[1];
            var dueYr = "20" + datePcs[2];
            
            //generate date obj and get timestamp
            var dueStamp = new Date(dueYr, dueMo, dueDay).valueOf();
        }
        
		daysBetween = Math.ceil((dueStamp - Date.now())/1000/60/60/24);
	}
    
    //If the material won't be avialable for more than 4 days (or is already on HOLD)
    if($j("td:contains('HOLD')", context).text() || daysBetween > 4 ){
        debugLog("intercepting links");
		$j("[href*='request~'], [href*='requestbrowse~']", context).mouseup(function(){

            console.log(isbn + " " +  title);
            var requestDialog = new RequestDialog({
                cssPath: "http://librarydev.rit.edu/depts/assets/AlbertIRA/css/",
                servicePath: "https://librarydev.rit.edu/depts/assets/AlbertIRA/",
                isbn: isbn,
                itemTitle: title
            });
            requestDialog.ShowDialog();
            requestDialog.Init();
            
            
        }).click(function(){
            return false; 
        });

	}
}