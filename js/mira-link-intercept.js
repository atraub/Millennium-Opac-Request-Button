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

(function(){
    if(jQuery.isReady)
    {
        analyzePage();
    }
 })();

$j(document).ready(function(){
    analyzePage();
});

function analyzePage()
{
    //disable logging ot the console for production version
    console.log = function() {};
    
    if(!MIRA_INTERCEPTED_LINKS)
    {
        MIRA_INTERCEPTED_LINKS = true;
        //check for search results cells and request links
        var recordCells = $j("td.briefcitCell");
        var requestLinks = $j("[href*='request~'], [href*='requestbrowse~']");

        //don't go any further if there's no request links to intercept
        if(requestLinks && requestLinks.length > 0)
        {

            //if we have search result cells, intercept the indivual links
            if(recordCells && recordCells.length > 0)
            {
                console.log(recordCells);
                $j(recordCells).each(function() {

                    //determine if there any request links to intercept
                    var recordReqLinks = $j("[href*='request~'], [href*='requestbrowse~']", this);

                    if(recordReqLinks && recordReqLinks.length > 0)
                    {
                        //extract record info
                        var isbn = $j(".gbs", this).text().trim().split(" ", 1)[0];

                        //some record rows don't include the isbn, attempt to extract it from the image url
                        if(isbn.length < 10)
                        {
                            if($j(".briefcitJacket img", this).attr("src"))
                            {
                                var urlMatches = $j(".briefcitJacket img", this).attr("src").match(/\?isbn=([\dXx]{10,13})/);

                                if(urlMatches && urlMatches[1])
                                {
                                    isbn = urlMatches[1]; 
                                }    
                            }
                            //if still couldn't find an isbn, just take the first ISBN-like # (very low chance this isn't an isbn anyway)
                            else {
                                isbn = $j(this).html().match(/([\dXx]{10,13})/g)[0];
                            }
                        }

                        //get the title of the item
                        var title = $j(".briefcitTitle", this).text().split("/", 1)[0];
                        title = $j.trim(title);

                        if(isbn && title)
                        {
                            interceptLink(this, isbn, title);
                        }
                    }
                });
            }
            //otherwise this is a record page and we need can search the whole thing
            else {
                console.log("single-page");
                //extract info
                //var  = $j(".bibInfoData:first").text();
                var isbn = $j("body").text().match(/([\dXx]{10,13})/g)[0];
                
                //if we couldn't get the title that way, try extracting from the bibInfo
                var bibInfoRows = $j(".bibInfoData");
                $j(bibInfoRows).each(function(){
                    
                    var labelElem = $j(".bibInfoLabel", $j(this).parent());
                    if(labelElem.text().toLowerCase().indexOf("title") != -1)
                    {
                        title = $j(this).text();
                    }
                });
                    
                title = title.split("/",1);

                if(isbn && title)
                {
                    interceptLink(document, isbn, title);
                }

            }
        }
    }
}

function interceptLink(context, isbn, title)
{
    console.log("checking local item availibility");

	//Determine how far in the future the material is due
    var minRemainingDays = 0;
    
    //Millenium generates request links for any item where at least on copy is unavailabe, even it there are copies available 
    if($j("td:contains('AVAILABLE')", context).length > 0 && false)
    {
        console.log("hiding unecessary link for: " + isbn);
        //$j("[href*='request~'], [href*='requestbrowse~']", context).hide();
    }
    else {
        //loop through each copy of the item, and calculate the minimum wait time
        $j("td:contains('DUE')", context).each(function(){
            var dueDate = $j(this).text().trim();

            if(dueDate){
                var dueStamp;

                //extract due date 
                //for browsers that support it (chrome), automatically extract timestamp
                if(!(dueStamp = new Date(dueDate).valueOf()))
                {
                    //if not, get it the old way (other browsers)
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
                
                if(!MIRA_DIALOG_OPEN)
                {
                    MIRA_DIALOG_OPEN =  true;
                    console.log(isbn + " " +  title);
                    var requestDialog = new RequestDialog({
                        cssPath: local.servicePath + "css/",
                        servicePath: local.servicePath,
                        isbn: isbn,
                        itemTitle: title
                    });
                    requestDialog.ShowDialog();
                    requestDialog.Init();
                }

            }).click(function(){
                return false; 
            });
        }
	}
}