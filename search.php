<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

// ini_set('memory_limit','300M');

/*******************************************************************************
 * This is an extremely basic sample search page used to search the crawler results
 ******************************************************************************/

################################################################################
############################## AJAX STUFF ######################################
################################################################################

function getSummary($str, $term){
    $start = strpos($str, $term); 
    return substr($str, $start, 150);
}

if(isset($_POST['action'])){
	
	// require the crawler
	require_once("crawler/autoload.php");

	switch($_POST['action']){
		case "search":
			
			$pdo = new PDO(
				'mysql:host='.$CrawlerConfig['PDO_CONFIG']['HOST'].';'.
				'dbname='.$CrawlerConfig['PDO_CONFIG']['DB'].';'.
				'charset=utf8', 
				$CrawlerConfig['PDO_CONFIG']['USER'], 
				$CrawlerConfig['PDO_CONFIG']['PASS']
			);
			
			$q = $pdo->prepare("SELECT * FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE body LIKE :t OR title LIKE :t");
			$q->execute(array(":t"=>"%{$_POST['term']}%"));
			
			$return = array();
			
			while($res = $q->fetch(PDO::FETCH_ASSOC)){
				$a = array();
				$a['url'] = $res['url'];
				$a['title'] = str_replace($_POST['term'], "<b>{$_POST['term']}</b>", $res['title']);
				$a['body'] = str_replace($_POST['term'], "<b>{$_POST['term']}</b>", getSummary($res['body'], $_POST['term']));
				array_push($return, $a);
			}
			
			echo json_encode($return);
			exit;
			
			break;
		case "crawl":
			
			break;
		default:
			die("Invalid action");
	}
	
}

################################################################################
############################## END AJAX STUFF ##################################
################################################################################

?><!DOCTYPE html>
<html lang="en">
    <head>
        
        <!-- metas -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Search Crawler Results">
        <meta name="author" content="Rob Parham">
        <title>Search Rockwell</title>
        
        <!-- styles -->
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { padding-top: 70px; /* adjust for the navbar */ }
        </style>
        
        <!-- HTML5 Shim and Respond.js -->
        <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body style='background:#fafcdf;'>
        
        <!-- navbar -->
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation" style="background:#195a8b;">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#">Search Crawler Results</a>
                </div>
            </div>
        </nav>
        
        <!-- page content -->
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <img src="//rockwell.ourtownamerica.com/images/intra/ot_home_logo_new.jpg" />
                </div>
            </div>
			
			<div class="row">
                <div class="col-lg-12">
					<center>
						<h3>Search Crawler Results</h3>
						<div class="input-group">
							<form method='POST' id='sform' action='search.php' style='display:block'>
								<input type="text" class="form-control" id='sinput' placeholder="Search for...">
								<span class="input-group-btn">
									<button class="btn btn-default" type="submit" id="sbtn">Go!</button>
								</span>
							</form>
						</div>
					</center>
					<div id='results'></div>
                </div>
            </div>
        </div>
        
		<div style='position:fixed; top: 0; right:0; z-index:9999999999999999999999999999999999; padding:2em; background: rgba(200,200,200,0.7)'>
			<button type="button" class="btn btn-primary" id='cbtn'>Start Crawl</button>
		</div>
		
        <!-- javascripts -->
        <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script>
		$(document).ready(function(){
			$("#sform").submit(function(e){
				e.preventDefault();
				var term = $("#sinput").val();
				$("#sbtn").html("loading..");
				$.ajax({
					url: "<?php echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: {action:"search", term:term}
				}).done(function(r){
					r = JSON.parse(r);
					var item, div;
					$("#results").empty();
					for(var i=0; i<r.length; i++){
						item = r[i];
						div = $("<div><a href='"+item['url']+"'><big><span class='glyphicon glyphicon-link'></span> "+item['title']+"</big></a><br><small><i>"+item['url']+"</i></small><div>"+item['body']+"</div></div><hr>");
						$("#results").append(div);
					}
					$("#sbtn").html("Go!");
				});
			});
			
			$(window).data("crawling", false);
			$("#cbtn").click(function doCrawl(e){
				if(typeof e != 'undefined') e.preventDefault();
				$(window).data("crawling", !$(window).data("crawling"));
				
				$("#cbtn").html($(window).data("crawling") ? "Stop Crawl" : "Start Crawl");
				
				$.ajax({
					url:"doCrawl.php",
					type:"post",
					data: {}
				}).done(function(r){
					if($(window).data("crawling")) doCrawl();
				});
			});
			
		});	
		</script>
    </body>
</html>
