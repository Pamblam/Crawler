<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

// Parse cli inputs if running from command line
if(php_sapi_name() === 'cli') 
	parse_str(implode('&', array_slice($argv, 1)), $_GET);

// If debugging is turned on, turn on error reporting
if(isset($_GET['dbg'])){
	error_reporting(E_ALL);
	ini_set("display_errors", "1");
	ini_set('memory_limit','300M');	
}

// require the crawler
require_once("crawler/autoload.php");

// Get the crawler instance
$crawler = Crawler::getInstance(30); // will search for 5 seconds (plus any additional time it takes to process)

// Let the class echo output as it works
$crawler->dumpOutput = true;

// Let's have the crawler format the output in
// HTML for the browser but only if running in a browser
$crawler->formattedOutput = php_sapi_name() === 'cli' ? false : true;

// Crawl the website
$crawler->crawl();
