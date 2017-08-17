<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

/* Crawler class
 * The base class that exposes all functionality
 */
class Crawler {
	
	/* Holds the configuration options
	 * Includes MySQL details, website login details, etc.
	 * See: config.php
	 */
	private $config;
	
	
	/* Output the output in realtime or wait till it's called for
	 */
	public $dumpOutput = false;
	
	
	/* Output the output in HTML or plaintext
	 */
	public $formattedOutput = false;
	
	
	/* Holds output
	 */
	public $output = array();
	
	
	/* Holds the instance of the class
	 */
	private static $instance;
	
	
	/* An array of rows of URLs to be crawled next
	 */
	private $queue = array();
	
	/* What time was it instatiated
	 */
	private $started = 0;
	
	/* How many seconds to wait
	 */
	private $timelimit = 0;
	
	/* Returns the Crawler object
	 */
	public static function getInstance($seconds = 15){
        if (empty(self::$instance)){
			$class = __CLASS__;
            self::$instance = new $class($seconds);
        }
        return self::$instance;
    }
	
	
	/**
	 * Constructor - sets configuration
	 * @global type $CrawlerConfig
	 */
	private function __construct($seconds) {
		
		// Set the config property
		global $CrawlerConfig;
		$this->config = $CrawlerConfig;
		
		$this->started = time();
		$this->timelimit = $seconds;
		
		
		// Start a session
		if( !empty($this->config['AUTH']) && 
			!empty($this->config['LOGIN_ENDPOINT'])
		) CrawlerRequest::startSession();
		
		// Make sure the table exists,
		// or create it if it doesn't
		CrawlerPDO::checkTable();
		
		// Set queue of URLs to crawl
		$this->queue = CrawlerPDO::getNextURLs();
		
		return;
	}
	
	/* Adds output to the output array
	 * @param string $str - The text to add
	 */
	public function addOutput($str){
		
		// Add output to output array
		array_push($this->output, $str);
		
		// Dump the output if that option is set
		if($this->dumpOutput){
			echo $this->formattedOutput ? "<pre style='display:block;'>$str</pre>" : "$str\n";
		}
	}
	
	
	/* Returns (and echos, if configured) the ouput generated by the crawler
	 */
	public function getOutput(){
		
		// Contains the output to be returned/echoed
		$ret = array();
		
		// Loop through the output
		foreach($this->output as $o){
			
			// Formatted or plaintext?
			$o = $this->formattedOutput ? "<pre style='display:block;'>$o</pre>" : "$o";
			
			// Add it to $ret
			array_push($ret, $o);
		}
		
		// Implode into a string
		$ret = implode("\n",$ret);
		
		// Echo the ouput if configured
		if($this->dumpOutput) echo $ret;
		
		// Return the ouput
		return $ret;
	}
	
	public function isCrawling(){
		$instance = CrawlerPDO::getInstance();
		return 0 != $instance['running'];
	}
	
	/* Starts the process of crawling the URLs in the queue
	 * @param int $max_depth - if zero, crawls until it can't find any more links
	 *   otherwise, $depth determines the number of times the queue will refresh
	 * @param int $current_depth - Not to be set! This paramter is only used for
	 *   recursion purposes. It counts the number of times the queue has been 
	 *   refreshed.
	 */
	public function crawl($max_depth=0, $current_depth=0){
		
		if($this->isCrawling) return;
		CrawlerPDO::updateInstance(array("running" => 1));
		
		// Begin the loop through each URL row
		foreach($this->queue as $k=>$page){
			
			// Make sure it's a crawlable format
			$ctype = CrawlerRequest::getContentType($page['url']);
			if(strpos($ctype, "text/") === false){
				$bn = array_pop(explode("/", $page['url'])); 
				$this->addOutput("Skipping $bn - ($ctype).");
				// Update the record for the page we just crawled
				CrawlerPDO::updateURLRow(array(
					"title" => $page['title'],
					"url" => $page['url'],
					"body" => "skipped",
					"depth" => CrawlerPDO::getDepthOfUrl($page['url']),
					"crawled" => 1
				));
				continue;
			}
			
			// Get the depth of the current item
			$depth = CrawlerPDO::getDepthOfUrl($page['url']);
			
			// Get the page body
			$body = CrawlerRequest::request($page['url']);
			
			// Get an new instance of our HTML parser
			$parser = new CrawlerParser($body, $page['url']);
			
			// Add images to database
			$images = $parser->getImages();
			CrawlerPDO::addImages($images, $page['url']);
			
			// Download images if configured
			if($this->config['SAVE_IMAGES'] === true){
				
				foreach($images as $image){
					
					// Check download size
					if(!empty($this->config['MIN_IMAGE_SIZE'])){
						$size = CrawlerRequest::getFileSize($image);
						if($size < $this->config['MIN_IMAGE_SIZE']) continue;
					}
					
					$ctype = CrawlerRequest::getContentType($image);
					
					// skip files that don't have explicit contetn type
					if(strpos($ctype, "image/") === false) continue;
					
					// get extention
					$ext = explode("/", $ctype);
					$ext = $ext[1];
					
					// save the file
					$fn = preg_replace("/[^A-Za-z0-9 ]/", '', $image);
					$filename = realpath(dirname(__FILE__))."/media/cj_$fn.$ext";
					
					// Get the image if we don't already have it
					if(!file_exists($filename))
						CrawlerRequest::request($image, $params = array(), $filename);
				}
			}
			
			/* Crawl result contains two things we need...
			 *   - 1) Info needed to update the current $page in the $queue, and
			 *   - 2) A new list of links
			 *  Each of the new links will be checked to see if they exist in 
			 *  the table yet, if they do they will be updated with referrer 
			 *  information, etc. If the new link doesn't exist it will be added
			 *  to the table to be crawled next time the queue is updated.
			 */
			$crawlResult = array(
				"body" => $parser->getPlaintext(),
				"links" => $parser->getLinks(),
				"depth" => ($depth+1)
			);
			
			// Loop through and check and add new emails
			$emails = $parser->getEmails();
			foreach($emails as $email){
				
				// If the email was already discovered
				if(CrawlerPDO::emailDiscovered($email)){
					CrawlerPDO::updateEmailRow(array(
						"email" => $email,
						"url_ids" => CrawlerPDO::getURLID($page['url'])
					));
				}else{
					CrawlerPDO::insertEmailRow(array(
						"email" => $email,
						"url_ids" => CrawlerPDO::getURLID($page['url'])
					));
				}
				
			}
			
			// Loop thru and check and update or insert each new link
			foreach($crawlResult['links'] as $link){
				
				// If the URL was already discovered
				if(CrawlerPDO::URLDiscovered($link['url'])){
					CrawlerPDO::updateURLRow(array(
						"title" => $link['title'],
						"url" => $link['url'],
						"linked_from" => CrawlerPDO::getURLID($page['url']),
						"depth" => $crawlResult['depth']
					));
				}else{
					CrawlerPDO::insertURLRow(array(
						"url" => $link['url'],
						"title" => $link['title'],
						"linked_from" => CrawlerPDO::getURLID($page['url']),
						"depth" => $crawlResult['depth']
					));
				}
				
			}
			
			// Update the record for the page we just crawled
			CrawlerPDO::updateURLRow(array(
				"title" => $page['title'],
				"url" => $page['url'],
				"body" => $crawlResult['body'],
				"depth" => $depth,
				"crawled" => 1
			));
			
			// Add some output
			$this->addOutput("Found ".count($crawlResult['links'])." links on {$page['url']}.");
			
			// pop this item off the queue
			unset($this->queue[$k]);
			
			CrawlerPDO::updateInstance(array("running" => 0));
		}
		
		// Queue is empty!
		// Incremenent the depth counter
		$current_depth++;
		
		if(time() > ($this->started+$this->timelimit) && $this->timelimit > 0){
			$this->addOutput("Ran for ".(time()-$this->started)." seconds, timeout set to ".$this->timelimit.".");
			return;
		}
		
		// Refresh the queue and keep going?
		if($max_depth == 0 || $max_depth > $current_depth){
			$this->queue = CrawlerPDO::getNextURLs();
			if(!empty($this->queue)) $this->crawl($max_depth, $current_depth);
		}
	}
}
