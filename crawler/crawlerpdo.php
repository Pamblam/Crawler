<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

/* Crawler PDO interface for both MySQL and Oracle
 */
class CrawlerPDO{
	
	/* Holds the PDO instance
	 */
	private static $pdo_instance;
	
	
	/* Hold an array of URLs that are known to exist
	 * prevents unneccesary checks
	 */
	private static $discovered = array();
	
	
	/* The SQL to generate the table for MySQL.
	 * The table name is replaced with whatever is in config.php
	 */
	const TABLE_SQL_MYSQL = '
		CREATE TABLE IF NOT EXISTS `crawler` (
			`id` int(11) NOT NULL,
			`title` varchar(2000) NOT NULL,
			`url` varchar(700) NOT NULL,
			`body` longtext NOT NULL,
			`mainimage` longtext NOT NULL,
			`depth` int(11) NOT NULL DEFAULT \'1\',
			`updated` int(11) NOT NULL,
			`linked_from` varchar(500) NOT NULL,
			`crawled` int(1) NOT NULL DEFAULT \'0\'
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			
		ALTER TABLE `crawler`
			ADD PRIMARY KEY (`id`),
			ADD UNIQUE KEY `url` (`url`);

		ALTER TABLE `crawler`
			MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;';
	
	
	/* The SQL to generate the table.
	 * The table name is replaced with whatever is in config.php
	 */
	const TABLE_SQL_ORACLE = '
		
		CREATE TABLE crawler (
			ID NUMBER(10,0) NOT NULL ENABLE,
			TITLE VARCHAR2(2000) NOT NULL ENABLE,
			URL VARCHAR2(2000) NOT NULL ENABLE,
			BODY VARCHAR2(2000) NOT NULL ENABLE,
			MAINIMAGE VARCHAR2(4000) NOT NULL ENABLE,
			DEPTH NUMBER(10,0) DEFAULT 1 NOT NULL ENABLE,
			UPDATED NUMBER(10,0) NOT NULL ENABLE,
			LINKED_FROM VARCHAR2(2000) NOT NULL ENABLE,
			CRAWLED NUMBER(10,0) DEFAULT 0 NOT NULL ENABLE,
			CONSTRAINT crawler_pk PRIMARY KEY (id),
			CONSTRAINT crawler_uni UNIQUE (url)
		);
		
		CREATE SEQUENCE crawler_seq;';
	
	
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
			if($CrawlerConfig['DB_TYPE'] === "MySQL"){
				self::$pdo_instance = new PDO(
					'mysql:host='.$CrawlerConfig['PDO_CONFIG']['HOST'].';'.
					'dbname='.$CrawlerConfig['PDO_CONFIG']['DB'].';'.
					'charset=utf8', 
					$CrawlerConfig['PDO_CONFIG']['USER'], 
					$CrawlerConfig['PDO_CONFIG']['PASS']
				);
			}else{
				self::$pdo_instance = new PDO(
					'oci:dbname=//'.$CrawlerConfig['PDO_CONFIG']['HOST'].'/'.$CrawlerConfig['PDO_CONFIG']['DB'], 
					$CrawlerConfig['PDO_CONFIG']['USER'], 
					$CrawlerConfig['PDO_CONFIG']['PASS']
				);
				self::$pdo_instance->setAttribute( PDO::ATTR_CASE, PDO::CASE_LOWER );
			}
		}
		
		self::$pdo_instance->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		return self::$pdo_instance;
	}
	
	/* Adds image links to the database
	 */
	public static function addImages($images, $url){
		// Get the global config
		global $CrawlerConfig;
		if(empty($images)) return;
		
		$db = self::pdo();
		$sql = "UPDATE {$CrawlerConfig['CRAWLER_TABLE']} SET mainimage = substr(:img, 1, 32767) WHERE url = :url";
		$q = $db->prepare($sql);
		
		$imgs = array_unique($images);
		$largest = array("url"=>"", "size"=>0);
		foreach($images as $img){
			$s = getimagesize($img);
			$t = $s[0] * $s[1];
			if($t > $largest['size']){
				$largest['url'] = $img;
				$largest['size'] = $t;
			}
		}
		
		try{
			$q->execute(array(":img"=>$largest['url'], ":url"=>$url));
		} catch (PDOException $ex) {
			echo $ex->getMessage();
			exit;
		}
		
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
			$SQL = $CrawlerConfig['DB_TYPE'] === "MySQL" ? 
					"SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} LIMIT 1" : 
					"SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE rownum = 0" ;
			$q = $db->query($SQL);
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
			$SQL = $CrawlerConfig['DB_TYPE'] === "MySQL" ? self::TABLE_SQL_MYSQL : self::TABLE_SQL_ORACLE;
			if($CrawlerConfig['CRAWLER_TABLE'] !== "crawler")
				$SQL = str_replace("crawler", $CrawlerConfig['CRAWLER_TABLE'], $SQL);
			
			// Run SQL, one query at a time
			$queries = explode(";", $SQL);
			foreach($queries as $query){
				$query = trim($query);
				if(!empty($query)){
					try{
						$q = $db->query($query);
					}catch(PDOException $e){
						die("Error: {$e->getMessage()} || $query");
					}
				}
			}
			
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
			$SQL = $CrawlerConfig['DB_TYPE'] === "MySQL" ? 
				"SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url LIMIT 1" :
				"SELECT 1 FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url AND rownum = 1" ;
			$q = $db->prepare($SQL);
			if($q !== false){
				$z = $q->execute(array(":url"=>$url));
				if($z !== false){
					$f = $q->fetchAll(PDO::FETCH_ASSOC);
					$found = !!count($f);
				}
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
		$q->execute(array(":url"=>$url));
		$res = $q->fetch(PDO::FETCH_ASSOC);
		
		$linkedfrom = $res['linked_from'];
		
		if(empty($linkedfrom)) $linkedfrom =0;
		
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
		
		$lf = isset($row['linked_from']) ? $row['linked_from'] : 0;
		$glf = self::getLinkedFrom($row['url'], $lf);
		$params = array(
			":url" => $row['url'],
			":title" => !empty($row['title']) ? $row['title'] : self::getParam($row['url'], 'title'),
			":body" => !empty($row['body']) ? $row['body'] : self::getParam($row['url'], 'body'),
			":depth" => isset($row['depth']) ? $row['depth'] : self::getParam($row['url'], 'depth'),
			":updated" => time(),
			":linked_from" => $glf,
			":crawled" => isset($row['crawled']) ? $row['crawled'] : self::getParam($row['url'], 'crawled'),
		);
		
		if($CrawlerConfig['DB_TYPE'] !== "MySQL" && !empty($row['body'])){
			$path = realpath(dirname(__FILE__))."/blobs";
			if(!is_writable($path)) die("Please ensure that $path is writable.");
			$basename = self::getParam($row['url'], 'body');
			$filename = file_exists("$path/$basename") ? $basename : basename(tempnam($path, "BLOB_"));
			chmod("$path/$filename", 0777);
			$fh = fopen("$path/$filename", "w+");
			fwrite($fh, $row['body']);
			fclose($fh);
			$params[':body'] = $filename;
		}
		
		$sql = "UPDATE {$CrawlerConfig['CRAWLER_TABLE']} SET title = :title, body = :body, depth = :depth, updated = :updated, linked_from = :linked_from, crawled = :crawled WHERE url = :url";
		$q = $db->prepare($sql);
		
		try{
			$z = $q->execute($params);
		}catch(PDOException $e){
			
			$sql = "UPDATE {$CrawlerConfig['CRAWLER_TABLE']} SET updated = :updated, crawled = 1 WHERE url = :url";
			$q = $db->prepare($sql);
			$z = $q->execute(array(":updated"=>time(), ":url"=>$params[":url"]));
			
		}
		
		if($z === false) die("Could not update record.");
	}
	
	/* Drop a row
	 */
	public static function dropRow($url){
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		$here = realpath(dirname(__FILE__));
				
		// Get the filename
		$q = $db->prepare("SELECT * FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$q->execute(array(":url"=>$url));
		$r = $q->fetch(PDO::FETCH_ASSOC);
		
		if($CrawlerConfig['DB_TYPE'] !== "MySQL" && file_exists("$here/blobs/{$r['body']}"))
			unlink("$here/blobs/{$r['body']}");
		
		$q = $pdo->prepare("DELETE FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$q->execute(array(":url"=>$url));
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
		
		$SEQ = str_replace("crawler", $CrawlerConfig['CRAWLER_TABLE'], "crawler_seq");
		
		$params = array(
			":url" => $row['url'],
			":title" => !empty($row['title']) ? $row['title'] : "Unknown Title",
			":body" => !empty($row['body']) ? $row['body'] : "<html></html>",
			":img" => "{}",
			":depth" => isset($row['depth']) ? $row['depth'] : 0,
			":updated" => time(),
			":linked_from" => isset($row['linked_from']) ? $row['linked_from'] : 0
		);
		
		if($CrawlerConfig['DB_TYPE'] !== "MySQL"){
			$path = realpath(dirname(__FILE__))."/blobs";
			if(!is_writable($path)) die("Please ensure that $path is writable.");
			$filename = basename(tempnam($path, "BLOB_"));
			chmod("$path/$filename", 0777);
			$fh = fopen("$path/$filename", "w+");
			fwrite($fh, empty($row['body']) ? "<html></html>" : $row['body']);
			fclose($fh);
			$params[':body'] = $filename;
		}
		
		$sql = $CrawlerConfig['DB_TYPE'] == "MySQL" ?
			"INSERT INTO {$CrawlerConfig['CRAWLER_TABLE']} (title, url, body, mainimage, depth, updated, linked_from) VALUES (:title, :url, :body, :img, :depth, :updated, :linked_from)" :
			"INSERT INTO {$CrawlerConfig['CRAWLER_TABLE']} (id, title, url, body, mainimage, depth, updated, linked_from) VALUES ($SEQ.NEXTVAL, :title, :url, :body, :img, :depth, :updated, :linked_from)" ;
			
		$q = $db->prepare($sql);
		$z = $q->execute($params);
		
		if($z === false) die("Could not insert record.");
	}
	
	
	/* Gets the provided parameter for the provided row
	 * In Oracle, for "BODY" param, return filename, not text
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
				"title" => "Unknown Title",
				"body" => "<html></html>",
				"depth" => 0,
				"updated" => time(),
				"linked_from" => 0
			);
			
			self::insertRow($row);
			array_push($ret, $row);
		}
		
		// If the table is not empty
		else{
			
			// Get all rows that have not been crawled
			$q = $db->query("SELECT * FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE crawled = 0");
			$ret = $q->fetchAll(PDO::FETCH_ASSOC);
			
			if($CrawlerConfig['DB_TYPE'] !== "MySQL"){
				foreach($ret as $r){
					$path = realpath(dirname(__FILE__))."/blobs";
					if(file_exists("$path/{$r['body']}"))
						$body = file_get_contents("$path/{$r['body']}");
					else $body = "<html></html>";
					$r['body'] = $body;
				}
			}
		}
		
		return $ret;
	}
	
	/* Get a formatted snippet of the summary text
	 */
	public static function getSummary($str, $term){
		$start = strpos(strtoupper($str), strtoupper($term));
		$start = $start > 15 ? $start-15 : 0;
		$summary = substr($str, $start, 150);
		return str_ireplace($term, "<b>$term</b>", $summary);
	}
	
	/* Get the depth of an item from the database
	 */
	public static function getDepthOfUrl($url){
		
		// Make sure the URL has been discovered first
		if(!self::URLDiscovered($url)) die("Can't get the depth of a URL that was not discovered yet: $url");
		
		// Get the global config
		global $CrawlerConfig;
		
		// Get the PDO object
		$db = self::pdo();
		
		// Return the depth
		$q = $db->prepare("SELECT depth FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE url = :url");
		$res = $q->fetch(PDO::FETCH_ASSOC);
		return $res['depth'];
	}
	
	
	/* Search for a keyword in the crawler results
	 */
	public function doSearch($term){
		
		// Get the global config
		global $CrawlerConfig;
		
		$return = array();
		$bpath = realpath(dirname(__FILE__))."/blobs";
		$pdo = self::pdo();

		// Get title results
		$upperTermm = strtoupper($term);
		
		$sql = 'SELECT * FROM '.$CrawlerConfig['CRAWLER_TABLE'].' WHERE UPPER("TITLE") LIKE :t OR UPPER("URL") LIKE :u';
		$q = $pdo->prepare($sql);
		$q->execute(array(":t"=>"%$upperTermm%", ":u"=>"%$upperTermm%"));
		
		while($res = $q->fetch(PDO::FETCH_ASSOC)){

			$a = array();
			$a['match_score'] = 100;
			if(strpos($res['title'], $term) === false) $a['match_score'] -= 200;
			$a['url'] = str_ireplace($term, "<b>$term</b>", $res['url']);
			$a['title'] = str_ireplace($term, "<b>$term</b>", $res['title']);
			
			if($CrawlerConfig['DB_TYPE'] !== "MySQL")
				$body = file_exists("$bpath/{$res['body']}") ? file_get_contents("$bpath/{$res['body']}") : "";
			else $body = $res['body'];
			
				
			$a['body'] = self::getSummary($body, $term);
			array_push($return, $a);
		}

		// Get all crawled pages
		
		$q = $pdo->query("SELECT * FROM {$CrawlerConfig['CRAWLER_TABLE']} WHERE crawled = 1");
		while($res = $q->fetch(PDO::FETCH_ASSOC)){
			foreach($return as $r) if($r['url'] == $res['url']){
				$r['match_score'] += count(explode(",", $res['linked_from']));
				$r['match_score'] -= ($res['depth'] - 1);
				continue 2;
			}
			if($CrawlerConfig['DB_TYPE'] !== "MySQL") 
				$body = file_exists("$bpath/{$res['body']}") ? file_get_contents("$bpath/{$res['body']}") : "";
			else $body = $res['body'];
			
			$substr = substr_count(strtoupper($body), strtoupper($term));
			if($substr > 0){
				$a = array();
				$a['match_score'] = $substr * 25;
				$a['url'] = str_ireplace($term, "<b>$term</b>", $res['url']);
				$a['title'] = str_replace($term, "<b>$term</b>", $res['title']);
				$a['body'] = self::getSummary($body, $term);
				array_push($return, $a);
			}
		}

		function cmp($a, $b){
			return $a['match_score'] < $b['match_score'] ? 1 : -1;
		}
		
		usort($return, 'cmp');
		
		return $return;
	}
}