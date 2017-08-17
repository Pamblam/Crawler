<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

/* autoload.php
 * Loads required files for Crawler.
 */
function CrawlerAutoload(){
	
	// The current, complete path
	$here = realpath(dirname(__FILE__));
	
	// Array of required files
	$required = array(
		"config.php",
		"crawlerrequest.php",
		"crawlerpdo.php",
		"crawlerparser.php",
		"crawler.php"
	);
	
	// Include all the required files
	foreach($required as $r) require_once "$here/$r";
}

CrawlerAutoload();
