<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

/* CrawlerParser class
 * Responsible for Parsing HTML
 */
class CrawlerParser{
	
	
	/* Hold the raw HTML from the response
	 */
	private $html = "";
	
	
	/* Hold the current URL
	 */
	private $current_location;
	
	
	/* Constructor
	 */
	public function __construct($html, $current_location) {
		
		// Strip the whitespace from the HTML string
		$this->html = self::stripWhitespace($html);
		
		// Get the current URL, used to generate full URLs from links
		$this->current_location = self::trimUrl($current_location);
	}
	
	
	public function getImages(){
		
		// Create DOM object and load HTML
		@$dom = new DOMDocument();
		@$dom->loadHTML($this->html);
		$dom->preserveWhiteSpace = false;
		
		// Create DOMXpath and get images
		$xpath = new DOMXpath($dom);
		$imgs = $xpath->query("//img");
		
		// Return array
		$ret = array();
		
		// Loop results and return
		for ($i = 0; $i < $imgs->length; $i++) {
			$img = $imgs->item($i);
			$src = $img->getAttribute("src");
			
			// Make sure it's a complete URL
			$src = self::filterUrl($src);
			if($src === false) continue;
			
			array_push($ret, $src);
		}
		
		return $ret;
	}
	
	
	/* Strips the whitespace from a string
	 */
	public static function stripWhitespace($html){
		$replace = array(
			array("this"=>"\n", "that"=>" "),
			array("this"=>"\r", "that"=>" "),
			array("this"=>"\t", "that"=>" "),
			array("this"=>"  ", "that"=>" ")
		);
		foreach($replace as $r) while(strpos($html,$r['this'])!==false) $html = str_replace($r['this'], $r['that'], $html);
		return $html;
	}
	
	
	/* Get the text content
	 */
	public function getPlaintext(){
		
		// Create DOM object and load HTML
		@$dom = new DOMDocument();
		@$dom->loadHTML($this->html);
		$dom->preserveWhiteSpace = false;
		
		// Strip scripts
		while(($r = $dom->getElementsByTagName("script")) && $r->length){
            $r->item(0)->parentNode->removeChild($r->item(0));
		}
		
		// Strip styles
		while(($r = $dom->getElementsByTagName("style")) && $r->length){
            $r->item(0)->parentNode->removeChild($r->item(0));
		}
		
		$plainText = $dom->textContent;
		return self::stripWhitespace($plainText);
	}
	
	
	/* Get/generate the links complete URLs and titles
	 */
	public function getLinks(){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Create DOM object and load HTML
		@$dom = new DOMDocument();
		@$dom->loadHTML($this->html);
		$dom->preserveWhiteSpace = false;
		
		// Return array
		$ret = array();
		
		// Get links
		$links = $dom->getElementsByTagName('a');
		
		// Extract links data
		foreach ($links as $tag){
			
			// Get the hyperlink reference
			$url = trim($tag->getAttribute('href'));
			
			// Make sure it's a complete URL
			$url = self::filterUrl($url);
			if($url === false) continue;
			
			// Skip URL's that start with #
			if(substr($url, "0", 1) == "#") continue;
			
			// Skip mailto links
			if(strpos($url, "mailto:") !== false) continue;
			
			// Skip links that don't match the 'FOLLOW_LINKS_LIKE' config param
			if(
				!empty($CrawlerConfig['FOLLOW_LINKS_LIKE']) &&
				strpos($url, $CrawlerConfig['FOLLOW_LINKS_LIKE']) === false
			) continue;
			
			// Skip links that DO match the "IGNORE_LINKS_LIKE" config param
			if(!empty($CrawlerConfig['IGNORE_LINKS_LIKE'])){
				foreach($CrawlerConfig['IGNORE_LINKS_LIKE'] as $l){
					if(strpos($url, $l) !== false) continue 2;
				}
			}
			
			// Dump it to the array
			array_push($ret, array(
				"url" => $url,
				"title" => $tag->textContent ///$tag->childNodes->item(0)->nodeValue
			));			
        }
		
		$ret = self::doopScooper($ret, array("url"));
				
		return $ret;
	}
	
	
	/* Removes duplicates from an array
	 * @param array $dataArray - Array to be filtered
	 * @param array $uniqueKeys - Array of keys that need to be unique
	 */
	private static function doopScooper($dataArray, $uniqueKeys){
		$checked = array();
		foreach($dataArray as $k=> $row){
			$checkArray = array();
			foreach($uniqueKeys as $key) $checkArray[$key] = isset($row[$key]) ? $row[$key] : NULL;
			$checkArray = json_encode($checkArray);
			if(in_array($checkArray, $checked)) unset($dataArray[$k]);
			else $checked[] = $checkArray;
		}
		return $dataArray;
	}
	
	
	/* Make sure URL is complete path
	 */
	private function filterUrl($url){
		
		// Get the global config
		global $CrawlerConfig;
		
		// Make sure the URL isn't "#"
		if(substr($url, 0, 1) == "#") return false;
		
		if(substr($url, 0, 2) == "//") $url = "http:".$url;
		
		// If it's already valid, nothing else needs to be done
		if(filter_var($url, FILTER_VALIDATE_URL)) return $url;
		
		// If it's a partial path, generate full path
		if(substr($url, 0, 1) == "/"){
			
			// Glue pieces together
			$turl = $CrawlerConfig['BASE_URL'].$url;
			
			// If it worked, return it
			if(filter_var($turl, FILTER_VALIDATE_URL)) return $turl;
		}
		
		// Temporary URL var
		$turl = rtrim($this->current_location,"/")."/".ltrim($url,"/");
		
		// Try one more time
		if(filter_var($turl, FILTER_VALIDATE_URL)) return $turl;
		
		return false;
	}
	
	
	/* Trim everything after the last "/"
	 * @param String $url - The URL to trim
	 */
	public static function trimUrl($url){
		$end = substr(strrchr($url,'/'), 1);
		return substr($url, 0, - strlen($end));
	}
}

