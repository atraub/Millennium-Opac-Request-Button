/*

Script to hijack Millennium OPAC's "Request Button"
Redirect to appropriate services

Authored by:  Adam Traub
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

var newLink = "nope";
var dueDate, ddDay, ddMonth, ddYear, todayDate, millisPerDay, millisBetween, daysBetween;
var bibTitle, bibDate, bibISBN, bibAuLast, bibPublisher;

bibTitle = "test";  //debugging

$j(window).load(function() {

	if ($j(".bibPager").length){
		bibTitle = $j(".bibDisplayTitle:first .bibInfoData").text().split("/",1);
		//bibAuLast = $j(".bibDetail:first .bibInfoData").text().split(","1);
		//bibDate = 
		bibISBN = $j(".bibDisplayContentMore .bibInfoData:first").text();
	} else if($j("tr .reqLink")){
		//$j(".briefcitCell").each
	}

	console.log(bibTitle);
	console.log(bibISBN);

	/* Date Magic */

	if($j("td:contains('HOLD')").text()){

		newLink = "https://ill.rit.edu/illiad/illiad.dll/OpenURL?sid=ritcatalog&genre=book&title=" + encodeURI(bibTitle) + "&atitle=&volume=&part=&issue=&date=&spage=&epage=&isbn=" + encodeURI(bibISBN) + "&aulast=&aufirst=&espnumber=&LoanPublisher=&LoanPlace=&LoanEdition="; //GO TO ILL/CNY

	} else if($j("td:contains('DUE')").text()){

		dueDate = $j("td:contains('DUE')").text().trim().replace("DUE ", "");
		ddMonth = dueDate.split("-")[0]-1; //Base 0
		ddDay = dueDate.split("-")[1];
		ddYear = 20+dueDate.split("-")[2]; //Concatenating "20" to make base year 2000

		dueDate = new Date(ddYear,ddMonth,ddDay);
		todayDate = new Date();

		console.log(dueDate);
		console.log(todayDate);

		/* 
			Days between method from:
				http://stackoverflow.com/questions/1036742/date-difference-in-javascript-ignoring-time-of-day
		*/

		dueDate = Date.UTC(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate());
		todayDate = Date.UTC(todayDate.getFullYear(), todayDate.getMonth(), todayDate.getDate());

		daysBetween = Math.abs(dueDate-todayDate)/1000/60/60/24;

		console.log(daysBetween);

		if(daysBetween > 4){
			newLink = "https://ill.rit.edu/illiad/illiad.dll/OpenURL?sid=ritcatalog&genre=book&title=" + encodeURI(bibTitle) + "&atitle=&volume=&part=&issue=&date=&spage=&epage=&isbn=" + encodeURI(bibISBN) + "&aulast=&aufirst=&espnumber=&LoanPublisher=&LoanPlace=&LoanEdition="; //GO TO ILL/CNY
		}
	}


	/* Link Switching Magic */

	if(newLink != "nope"){
		$j("[href*='request~']").attr("href", newLink);  //Changes link on individual display page
		$j("[href*='requestbrowse~']").attr("href", newLink);  //Changes link on search results page
	}

	/* Debugging Magic */

	console.log(newLink);
	console.log(bibTitle);
	console.log(bibISBN);

});