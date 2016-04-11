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

if(isset($_POST['action']) && $_POST['action'] == "search"){
	require realpath(dirname(__FILE__))."/crawler/autoload.php";
	$return = CrawlerPDO::doSearch($_POST['term']);
	echo json_encode($return);
	exit;
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
        <title>Crawler Search</title>
        
        <!-- styles -->
        <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { padding-top: 70px; /* adjust for the navbar */ }
			.resdiv:hover{
				border-left:2px solid blue;
				padding-left:8px;
			}
			.resdiv{
				border-left:0;
				padding-left:10px;
				padding-bottom: 25px;
			}
			@-moz-font-face { 
				font-family: Catull; 
				src: url(../font/Catull.ttf); 
				font-weight:400; 
			}
			@-webkit-font-face { 
				font-family: Catull; 
				src: url(../font/Catull.ttf); 
				font-weight:400; 
			}
			@-o-font-face { 
				font-family: Catull; 
				src: url(../font/Catull.ttf); 
				font-weight:400; 
			}
			@-ms-font-face { 
				font-family: Catull; 
				src: url(../font/Catull.ttf); 
				font-weight:400; 
			}
			@font-face { 
				font-family: Catull; 
				src: url(../font/Catull.ttf); 
				font-weight:400; 
			}
			.google-logo {
				font-family: Catull,Sans-Serif;
				font-size: 50px;
			}
			.google-logo-sm {
				font-family: Catull,Sans-Serif;
				font-size: 25px;
			}
			.google-G { 
				color:#0047F1; 
			}
			.google-o1 { 
				color:#DD172C; 
			}
			.google-o2 { 
				color:#F9A600; 
			}
			.google-g { 
				color:#0047F1; 
			}
			.google-l { 
				color:#00930E; 
			}
			.google-e { 
				color:#E61B31; 
			}
        </style>
        
        <!-- HTML5 Shim and Respond.js -->
        <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body style='background:#fff;'>
        
        <!-- navbar -->
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation" style="background:#F1F1F1; border:0;">
            <div class="container">
                <div class="navbar-header">
                    <a class="navbar-brand" href="#">
						<span class="google-logo-sm">
							<span class="google-G">C</span><span class="google-o1">r</span><span class="google-o2">a</span><span class="google-g">w</span><span class="google-l">l</span><span class="google-e">e</span><span class="google-o2">r</span>
						</span>
					</a>
                </div>
            </div>
        </nav>
        
        <!-- page content -->
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
					
					<span class="google-logo">
						<span class="google-G">C</span><span class="google-o1">r</span><span class="google-o2">a</span><span class="google-g">w</span><span class="google-l">l</span><span class="google-e">e</span><span class="google-o2">r</span>
					</span>
					
                </div>
            </div>
			
			<div class="row">
                <div class="col-lg-12">
					<center>
						
						<form method='POST' id='sform' action='search.php' style='display:block'>


							<div class="input-group">
								<input type="text" class="form-control" id='sinput' placeholder="Search for...">
								<span class="input-group-btn">
									<button class="btn btn-default btn-primary" type="submit" id="sbtn"><span class="glyphicon glyphicon-search"></span></button>
								</span>

							</div>
							<small style="float:right; padding-top:5px;">Press Enter to Search</small>
						</form>
						<Br>
					</center>
					<div id='results'></div>
                </div>
            </div>
        </div>
		
        <!-- javascripts -->
        <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script>
			
		function getParameterByName(name, url) {
			if (!url) url = window.location.href;
			name = name.replace(/[\[\]]/g, "\\$&");
			var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)", "i"),
				results = regex.exec(url);
			if (!results) return null;
			if (!results[2]) return '';
			return decodeURIComponent(results[2].replace(/\+/g, " "));
		}
		
		function strip(html){
		   var tmp = document.createElement("DIV");
		   tmp.innerHTML = html;
		   return tmp.textContent || tmp.innerText || "";
		}

		$(document).ready(function(){
			
			$("#sform").submit(function(e){
				if($("#sinput").val() == "") return false;
				var d = new Date();
				var starttime = d.getMilliseconds(); 
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
					
					var ee = new Date();
					var stoptime = ee.getMilliseconds(); 
					var seconds = Math.abs(stoptime - starttime) / 1000;
					var totalSeconds = Math.round(seconds * 100) / 100;
					
					$("#results").empty();
					$("#results").append("<div> About "+r.length+" results ("+totalSeconds+" seconds)</div><br>");
					for(var i=0; i<r.length; i++){
						item = r[i];
						div = $("<div class='resdiv'><a href='"+strip(item['url'])+"' style='color: blue'><big>"+item['title']+"</big></a> <small><span class='glyphicon glyphicon-signal' style='color:red'></span> Relevance Score: "+item['match_score']+"</small><br><a href='"+strip(item['url'])+"' style='color: green'><small><span class='glyphicon glyphicon-link'></span> <i>"+item['url']+"</i></small></a><div>"+item['body']+"</div></div>");
						$("#results").append(div);
					}
					$("#sbtn").html('<span class="glyphicon glyphicon-search"></span>');
				});
			});
			
			var q = getParameterByName("q");
			if(q && q.length > 0){
				$("#sinput").val(q)
				$("#sform").submit();
			}
			
		});	
		</script>
    </body>
</html>
