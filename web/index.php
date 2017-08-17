<!--
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

/*******************************************************************************
 * This is a basic search page used to search the crawler results
 ******************************************************************************/
-->
<!DOCTYPE html>
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
		<link href="css/common.css" rel="stylesheet">
        
        <!-- HTML5 Shim and Respond.js -->
        <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body style='background:#fff;'>
        
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
						
						<a class='btn btn-primary' href="emailMiner.php">Email Miner</a>
						
					</center>
					<div id='results'></div>
                </div>
            </div>
        </div>
		
        <!-- javascripts -->
        <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script src="js/functions.js"></script>
		<script src="js/index.js"></script>
    </body>
</html>
