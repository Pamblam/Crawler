<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Create the return array
$return = array(
	"response" => "Success",
	"success" => true,
	"data" => array()
);

// Make sure an action parameter has been sent to this endpoint
checkParams(array("action"));

switch($_REQUEST['action']){
		
	case "search":
		checkParams(array("term"));
		require realpath(dirname(dirname(dirname(__FILE__))))."/crawler/autoload.php";
		$return['data'] = CrawlerPDO::doSearch($_REQUEST['term']);
		output();
		break;
	
	case "emails":
		require realpath(dirname(dirname(dirname(__FILE__))))."/crawler/autoload.php";
		$return['data'] = CrawlerPDO::getAllEmails();
		output();
		break;
	
	default: oops("Error: invalid action parameter");
}

function checkParams($reqd){
	foreach($reqd as $param)
		if(!isset($_REQUEST[$param])) 
			oops("Error: Missing $param parameter.");
}

function oops($oopsie){
	$GLOBALS['return']['response'] = $oopsie;
	$GLOBALS['return']['success'] = false;
	$GLOBALS['return']['data'] = array();;
	output();
}

function output(){
	header("Content-Type: application/json");
	echo json_encode($GLOBALS['return']);
	exit;
}