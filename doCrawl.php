<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

//error_reporting(E_ALL);
//ini_set("display_errors", "1");
ini_set('memory_limit','300M');

// require the crawler
require_once("crawler/autoload.php");

// Get the crawler instance
$crawler = Crawler::getInstance();

// Let the class echo output as it works
$crawler->dumpOutput = true;

// Let's have the crawler format the output in HTML for the browser too
$crawler->formattedOutput = true;

// Crawl the website
$crawler->crawl();

//echo "<pre>"; var_dump($crawler);
