<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

/* config.php
 * Holds configuration data
 */
$GLOBALS['CrawlerConfig'] = array(
	
	/* "BASE_URL" index
	 *  String - The base URL of the website to crawl
	 *  Include scheme/protocol and no trailing "/"
	 */
	"BASE_URL" => "https://www.washingtonpost.com/",
	
	
	/* "INIT_PATH" index
	 * String - The relative path to the page that will be crawled first
	 * Should begin with a "/" and include the filename, ie "/index.php"
	 */
	"INIT_PATH" => "/",
	
	
	/* "AUTH" index
	 * Array of login details
	 * This array will be POSTed to the login endpoint after generating
     * a query string using http_build_query()
	 */
	"AUTH" => array(),
	
	
	/* "LOGIN_ENDPOINT" index
	 * String - the **relative** path to start the session
	 * Login details will be POSTed here
	 */
	"LOGIN_ENDPOINT" => "",
	
	
	/* "FAILED_LOGIN_INDICATOR" index
	 * String - A unique string that ONLY appears on the failed login response
	 * If this string is found in the response after attempting logging in,
	 * the script assumes that login failed and exits.
	 */
	"FAILED_LOGIN_INDICATOR" => "",
	
	
	/* "PDO_CONFIG" index
	 * Array of MySQL database access details 
	 */
	"PDO_CONFIG" => array(
		"HOST" => "localhost", // If using Oracle, use "127.0.0.1" instead of "localhost"
		"USER" => "root",
		"PASS" => "",
		"DB" => "crawler"
	),
	
	
	/* "DB_TYPE" index
	 * either "MySQL" or "Oracle"
	 */
	"DB_TYPE" => "MySQL",	
	
	/* "CRAWLER_URLS_TABLE" index
	 * String - The name of the table to store the results in
	 * This table will be automatically generated if it does not exist
	 */
	"CRAWLER_URLS_TABLE" => "crawler_urls",
	
	/* "CRAWLER_EMAILS_TABLE" index
	 * String - The name of the table to store emails in
	 * This table will be automatically generated if it does not exist
	 */
	"CRAWLER_EMAILS_TABLE" => "crawler_emails",
	
	
	/* "FOLLOW_LINKS_LIKE" index
	 * String - Only follow links that contain this substring
	 * Leave empty to follow all links
	 * This is useful if you only want to crawl one domain
	 */
	"FOLLOW_LINKS_LIKE" => "washingtonpost",
	
	
	/* "IGNORE_LINKS_LIKE" index
	 * Array of strings. If any of the links match or contain any of these
	 * strings, they will be ignored.
	 */
	"IGNORE_LINKS_LIKE" => array(
		".jsp",
		"javascript:"
	),
	
	
	/* "SAVE_IMAGES" index
	 * Boolean - Download images or not
	 */
	"SAVE_IMAGES" => false,
	
	
	/* "MIN_INDEX_SIZE" index
	 * No images larger than this will be downloaded
	 */
	"MIN_IMAGE_SIZE" => 110000
);
