<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

/* CrawlerPDO class
 * Manages Crawler database interaction
 */
class CrawlerPDO {
	
	/* Holds the PDO instance
	 */
	private static $pdo_instance;
	
	
	/* Hold an array of URLs that are known to exist
	 * prevents unneccesary checks
	 */
	private static $discovered = array();
	
	
	/* The SQL to generate the table.
	 * The table name is replaced with whatever is in config.php
	 */
	const TABLE_SQL = <<<SQL
		CREATE TABLE IF NOT EXISTS `crawler` (
			`id` int(11) NOT NULL,
			`title` varchar(500) NOT NULL,
			`url` varchar(500) NOT NULL,
			`body` longtext NOT NULL,
			`depth` int(11) NOT NULL DEFAULT '1',
			`updated` int(11) NOT NULL,
			`linked_from` varchar(500) NOT NULL,
			`crawled` int(1) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			
		ALTER TABLE `crawler`
			ADD PRIMARY KEY (`id`),
			ADD UNIQUE KEY `url` (`url`);

		ALTER TABLE `crawler`
			MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
SQL;
	
	
	/* Get (and create if needed) the PDO connection
	 * @global type $CrawlerConfig
	 * @return PDO instance
	 */
	private static function pdo(){
		
		// Get the global config
		global $CrawlerConfig;
		
		// If the $pdo_instance isn't set...
		if(empty(self::$pdo_instance)){
			
			// ...Create the $pdo_instance
			self::$pdo_instance = new PDO(
				'mysql:host='.$CrawlerConfig['PDO_CONFIG']['HOST'].';'.
				'dbname='.$CrawlerConfig['PDO_CONFIG']['DB'].';'.
				'charset=utf8', 
				$CrawlerConfig['PDO_CONFIG']['USER'], 
				$CrawlerConfig['PDO_CONFIG']['PASS']
			);
		}
		
		self::$pdo_instance->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		return self::$pdo_instance;
	}
	
	/* Checks to see if the crawler table exists
	 * @return boolean
	 */
	private static function tableExists(){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Determine if table exists
		try{
			$q = $db->query("SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} LIMIT 1");
			$tableExists = $q !== false;
		}catch(PDOException $e){
			$tableExists = false;
		}
		
		return $tableExists;
	}
	
	
	/* Checks if the table exists in the database
	 * and if not, creates it.
	 */
	public static function checkTable(){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// If the table doesn't exist...
		if(!self::tableExists()){
			
			// Generate the Create Table SQL
			$SQL = self::TABLE_SQL;
			if($CrawlerConfig['CRAWLER_TABLE'] !== "crawler")
				$SQL = str_replace("crawler", $CrawlerConfig['CRAWLER_TABLE'], $SQL);
			
			// Run SQL, one query at a time
			$queries = explode(";", $SQL);
			foreach($queries as $query) 
				if(!empty(trim($query))) $q = $db->query($query);
			
			// Make sure it worked
			if(!self::tableExists()) die("Failed to create table");
			
		}
	}
	
	
	/* Determines if a URL exists in the database
	 */
	public static function URLDiscovered($url){
		
		// Check if it's already been validated
		if(in_array($url, self::$discovered)) return true;
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		$found = false;
		try{
			$q = $db->prepare("SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE `url` = :url LIMIT 1");
			if($q !== false){
				$z = $q->execute(array(":url"=>$url));
				if($z !== false) $found = !!$q->rowCount();
				else $found = false;
			}else $found = false;
		}catch(PDOException $e){
			$found = false;
		}
		
		// If it's validated, add it to the array so we don't have to check again
		if($found) array_push(self::$discovered, $url);
		
		return $found;
	}
	
	
	/* Returns a string of comma seperated numbers that represent the URLs that
	 * link to the given url. If the second parameter is set, it will append
	 * that value to the string as well.
	 * @param String $url - The URL to get
	 * @param String $addlink - Adds the given link to the result
	 */
	public static function getLinkedFrom($url, $addlink=null){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Get the value from the database
		$q = $db->prepare("SELECT linked_from FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$res = $q->fetch(PDO::FETCH_ASSOC);
		$linkedfrom = $res['linked_from'];
		
		// Add the $addlink if it is passed
		if(!empty($addlink)){
			$lf = explode(",",$linkedfrom);
			if(!in_array($addlink, $lf)) array_push($lf, $addlink);
			$linkedfrom = implode(",",$lf);
		}
		
		return $linkedfrom;
	}
	
	
	/* Updates a row in the table
	 */
	public static function updateRow($row){
		
		// Make sure the URL exists
		if(!isset($row['url'])) die("Can't update a row without the URL.");
		
		// Make sure the URL has been discovered first
		if(!self::URLDiscovered($row['url'])) die("Can't update a row that was not discovered yet. {$row['url']}.");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		$params = array(
			":url" => $row['url'],
			":title" => !empty($row['title']) ? $row['title'] : self::getParam($row['url'], 'title'),
			":body" => !empty($row['body']) ? $row['body'] : self::getParam($row['url'], 'body'),
			":depth" => isset($row['depth']) ? $row['depth'] : self::getParam($row['url'], 'depth'),
			":updated" => time(),
			":linked_from" => self::getLinkedFrom($row['url'], isset($row['linked_from']) ? $row['linked_from'] : 0),
			":crawled" => isset($row['crawled']) ? $row['crawled'] : self::getParam($row['url'], 'crawled'),
		);
		
		$q = $db->prepare("UPDATE {$CrawlerConfig['CRAWLER_TABLE']} SET title = :title, body = :body, depth = :depth, updated = :updated, linked_from = :linked_from, crawled = :crawled WHERE url = :url");
		$z = $q->execute($params);
		
		if($z === false) die("Could not update record.");
	}
	
	
	/* Inserts a row in the table
	 */
	public static function insertRow($row){
		
		// Make sure the URL exists
		if(!isset($row['url'])) die("Can't insert a row without the URL.");
		
		// Make sure the URL has been discovered first
		if(self::URLDiscovered($row['url'])) die("Can't insert a row that was already discovered.");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		$params = array(
			":url" => $row['url'],
			":title" => !empty($row['title']) ? $row['title'] : "Unknown Title",
			":body" => !empty($row['body']) ? $row['body'] : "<html></html>",
			":depth" => isset($row['depth']) ? $row['depth'] : 0,
			":updated" => time(),
			":linked_from" => isset($row['linked_from']) ? $row['linked_from'] : 0
		);
		
		$q = $db->prepare("INSERT INTO {$CrawlerConfig['CRAWLER_TABLE']} (title, url, body, depth, updated, linked_from) VALUES (:title, :url, :body, :depth, :updated, :linked_from)");
		$z = $q->execute($params);
		
		if($z === false) die("Could not insert record.");
	}
	
	
	/* Gets the provided parameter for the provided row
	 */
	public static function getParam($url, $param){
		
		// Make sure the URL has been discovered first
		if(!self::URLDiscovered($url)) die("Can't get param from a row that was not discovered yet. $url.");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Get and return the requested param
		$q = $db->prepare("SELECT $param as param FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$q->execute(array(":url"=>$url));
		$res = $q->fetch(PDO::FETCH_ASSOC);
		return $res['param'];
	}


	/* Gets the ID of the URL from the Database
	 */
	public static function getURLID($url){
		
		// Make sure the URL has been discovered first
		if(!self::URLDiscovered($url)) die("Can't get ID from a row that was not discovered yet. $url.");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Get and return the ID
		$q = $db->prepare("SELECT id FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$q->execute(array(":url"=>$url));
		$res = $q->fetch(PDO::FETCH_ASSOC);
		return $res['id'];
	}
	
	
	/* Determines if the table is empty
	 */
	private static function isTableEmpty(){
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Return boolean
		$ret = true;
		
		$q = $db->query("SELECT count(id) as cnt FROM {$CrawlerConfig['CRAWLER_TABLE']}");
		if($q !== false){
			$res = $q->fetch(PDO::FETCH_ASSOC);
			if($res !== false) $ret = !($res['cnt'] > 0);
		}
		return $ret;
	}
	
	
	/* Generates an array of URL to crawl next
	 * @return Array of rows of URLs to be crawled next
	 */
	public static function getNextURLs(){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Return array
		$ret = array();
		
		/* Check if table is empty, if so, create a row in the table for 
		 * the starting URL from the config file then return an array containing
		 * that single row.
		 * 
		 * If there are rows in the table, get and return an array containing
		 * all rows of URLs that have not been crawled yet.
		 */
		
		// If the table is empty
		if(self::isTableEmpty()){
			
			// Generate the starting row
			$row = array(
				"url" => $CrawlerConfig["BASE_URL"].$CrawlerConfig["INIT_PATH"],
				"title" => !empty($row['title']) ? $row['title'] : "Unknown Title",
				"body" => !empty($row['body']) ? $row['body'] : "<html></html>",
				"depth" => isset($row['depth']) ? $row['depth'] : 0,
				"updated" => time(),
				"linked_from" => isset($row['linked_from']) ? $row['linked_from'] : 0
			);
			
			self::insertRow($row);
			array_push($ret, $row);
		}
		
		// If the table is not empty
		else{
			
			// Get all rows that have not been crawled
			$q = $db->query("SELECT * FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE crawled = 0");
			$ret = $q->fetchAll(PDO::FETCH_ASSOC);
		}
		
		return $ret;
	}
	
	
	/* Get the depth of an item from the database
	 */
	public static function getDepthOfUrl($url){
		
		// Make sure the URL has been discovered first
		if(!self::URLDiscovered($url)) die("Can't get the depth of a URL that was not discovered yet.");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Return the depth
		$q = $db->prepare("SELECT depth FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$res = $q->fetch(PDO::FETCH_ASSOC);
		return $res['depth'];
	}
	
}