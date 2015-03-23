/*

Script to hijack Millennium OPAC's "Request Button"
Redirect to appropriate services

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

*/

/* Silly Variables */

var newLink = "nope";
var dueDate, ddDay, ddMonth, ddYear, todayDate, millisPerDay, millisBetween, daysBetween;

/* Date Magic */

if($("td:contains('HOLD')").text()){

	newLink = "http://ill.rit.edu"; //GO TO ILL/CNY

} else if($("td:contains('DUE')").text()){

	dueDate = $("td:contains('DUE')").text().trim().replace("DUE ", "");
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
		newLink = "http://ill.rit.edu"; //GO TO ILL/CNY
	}
}


/* Link Switching Magic */

if(newLink != "nope"){
	$("[href*='request~']").attr("href", newLink);
}