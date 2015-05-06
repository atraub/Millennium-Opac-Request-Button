/*
Script to intercept Millennium OPAC's "Request Button"
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
            Less Than 4 Days:  Direct to Millennium Hold
            4 or More Days:  Direct to Item Requestion Aggregation
            Holds Present:  Direct to Item Requestion Aggregation
*/

var $j = jQuery.noConflict();

$j(document).ready(function() {
    
    //check for search results cells and request links
    var recordCells = $j("td.briefcitCell");
    var requestLinks = $j("[href*='request~'], [href*='requestbrowse~']");
    
    //don't go any further if there's no request links to intercept
    if(requestLinks && requestLinks.length > 0)
    {
        console.log("req-links");
        //if we have search result cells, intercept the indivual links
        if(recordCells && recordCells.length > 0)
        {
            console.log(recordCells);
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
            console.log("single-page");
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

function interceptLink(context, isbn, title)
{
    console.log("checking local item availibility");
    var altLink = "https://ill.rit.edu/illiad/illiad.dll/OpenURL?sid=ritcatalog&genre=book&title=" + encodeURI(title) + "&atitle=&volume=&part=&issue=&date=&spage=&epage=&isbn=" + encodeURI(isbn) + "&aulast=&aufirst=&espnumber=&LoanPublisher=&LoanPlace=&LoanEdition=";

	//Determine how far in the future the material is due
    var minRemainingDays = 0;
    
    //Millenium generates request links for any item where at least on copy is unavailabe, even it there are copies available 
    if($j("td:contains('AVAILABLE')", context).length > 0)
    {
        console.log("hiding unecessary link for: " + isbn);
        $j("[href*='request~'], [href*='requestbrowse~']", context).hide();
    }
    else {
        //loop through each copy of the item, and calculate the minimum wait time
        $j("td:contains('DUE')", context).each(function(){
            var dueDate = $j(this).text().trim();

            if(dueDate){
                var dueStamp;

                //extract due date 
                //if browsers that support it (chrome), automatically extract timestamp
                if(!(dueStamp = new Date(dueDate).valueOf()))
                {
                    //split up pieces (dd-mm-yy)
                    var datePcs = dueDate.replace("DUE ", "").split("-");

                    var dueMo = datePcs[0],
                        dueDay = datePcs[1],
                        dueYr = "20" + datePcs[2];

                    //generate date obj and get timestamp
                    var dueStamp = new Date(dueYr, dueMo, dueDay).valueOf();
                }

                //determines the minimum wait time if there multiple copies
                var remainingDays = Math.ceil((dueStamp - Date.now())/1000/60/60/24);
                minRemainingDays = Math.min(minRemainingDays || Number.MAX_VALUE, remainingDays);
           }
        });

        //If the material won't be avialable for more than 4 days (or is already on HOLD)
        if($j("td:contains('HOLD')", context).text() || minRemainingDays > 4 ){
            console.log("intercepting links");
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
}