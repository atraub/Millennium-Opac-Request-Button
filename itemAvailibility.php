<?php
require_once("config.php");
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Item Availibility</title>
		<script src="https://code.jquery.com/jquery-2.0.3.min.js"></script>
		<script src="js/availibility-checker.js"></script>	
		<script>
            $j = jQuery.noConflict();
			$j(document).ready(function(){
				checker = new availibilityChecker({
					localLibName: "<?=$local['institution'] ?>",
					localSearchUrl: "<?=$local['search_url'] ?>",
					removeDuplicates: true,
					localOnly: false
				})
				checker.retrieveData();
                $j(heading).append("<i>"+decodeURI(checker.getUrlVars().title)+"</i>");
			});
		</script>
		<style>
            html, body {
                font-size: 1em;
                font-family: "Arial";
            }
            h3 {
                border-bottom: 1px #ccc solid;   
            }
		</style>
	</head>
	<body>
        <h3 id="heading">Item Availibility: </h3>
	</body>
</html>