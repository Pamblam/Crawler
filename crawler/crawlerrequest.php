<?php
/* Crawler - A website indexer.
 * -Can log into a website, 
 * -Crawls a website,
 * -Saves data to database
 * -Saves Page title, URL, Body text, and depth level
 * @author Robert Parham <adelphia at protonmail dot ch>
 * @license Apache 2.0 Lic.
 */

/* CrawlerRequest class
 * Manages HTTP requests and cookies
 */
class CrawlerRequest {
	
	/* Hold the path to the cookie file
	 */
	private static $cookiejar;
	
	
	/* Starts a session on the remote server
	 */
	public static function startSession(){
		
		// Get config data
		global $CrawlerConfig;
		
		// Make sure session is started
		if(empty(self::$cookiejar)) self::setCookieJar();
			
		// The complete login endpoint
		$login_url = $CrawlerConfig['BASE_URL'].$CrawlerConfig['LOGIN_ENDPOINT'];

		// Attempt the login
		$resp = self::request($login_url, $CrawlerConfig['AUTH']);
		
		// Did it work?
		if(strpos($resp, $CrawlerConfig['FAILED_LOGIN_INDICATOR']) !== false) 
			die("Could not start session on remote server");

		return;
	}
	
	
	/* Sets the cookie jar
	 */
	private static function setCookieJar(){
		
		// Create the file
		$cj = realpath(dirname(__FILE__))."/tmp/cj".microtime();
		$fh = fopen($cj, "w+"); fclose($fh);
		
		// Save the cookiejar file
		self::$cookiejar = $cj;
		
		// Validate the cookiejar
		if(!file_exists($cj)) die("Could not create cookiejar file.");
		if(!is_readable($cj)) die("Cannot read cookiejar file.");
		if(!is_writable($cj)) die("Cannot write to cookiejar file.");
		
		return;
	}
	
	
	/* Makes an HTTP request
	 * @param String $url - The URL to request
	 * @param Mixed $params - string or array to POST
	 * @param String - filename to download
	 */
	public static function request($url, $params = array(), $filename = "") {
		
		// Initiate cURL
		$ch = curl_init();
		$curlOpts = array(
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 
				'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true
		);
		
		// Send the cookies if we're logged in
		if (!empty(self::$cookiejar)) {
			$curlOpts[CURLOPT_COOKIEJAR] = self::$cookiejar;
			$curlOpts[CURLOPT_COOKIEFILE] = self::$cookiejar;
		}
		
		// If $filename exists, save content to file
		if (!empty($filename)) {
			$file2 = fopen($filename, 'w+') or die("Error[" . __FILE__ . ":" . __LINE__ . "] Could not open file: $filename");
			$curlOpts[CURLOPT_FILE] = $file2;
		}

		// Send POST values if there are any
		if (!empty($params)) {
			$curlOpts[CURLOPT_POST] = true;
			$curlOpts[CURLOPT_POSTFIELDS] = is_array($params) ? 
				http_build_query($params) : $params;
		}
		
		// Send the request
		curl_setopt_array($ch, $curlOpts);
		$answer = curl_exec($ch);
		
		// Errors?
		if (curl_error($ch)) die($url . " || " . curl_error($ch));
		
		// Close connection and return response
		curl_close($ch);
		if(!empty($filename)) fclose($file2);
		return $answer;
	}
	
	/* Returns the content type header returned by a given URL
	 * @param $url - The URL to request
	 * @param $params - array of params to be POSTed 
	 */
	public static function getContentType($url, $params=array()){
		// Initiate cURL
		$ch = curl_init();
		$curlOpts = array(
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 
				'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => true,
			CURLOPT_NOBODY => true
		);
		
		// Send the cookies if we're logged in
		if (!empty(self::$cookiejar)) {
			$curlOpts[CURLOPT_COOKIEJAR] = self::$cookiejar;
			$curlOpts[CURLOPT_COOKIEFILE] = self::$cookiejar;
		}

		// Send POST values if there are any
		if (!empty($params)) {
			$curlOpts[CURLOPT_POST] = true;
			$curlOpts[CURLOPT_POSTFIELDS] = is_array($params) ? 
				http_build_query($params) : $params;
		}
		
		// Send the request
		curl_setopt_array($ch, $curlOpts);
		curl_exec($ch);
		
		// Errors?
		if (curl_error($ch)) die($url . " || " . curl_error($ch));
		
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		
		// Close connection and return response
		curl_close($ch);
		return $contentType;
	}
	
	
	/**
	 * Returns the size of a file without downloading it, or -1 if the file
	 * size could not be determined.
	 *
	 * @param $url - The location of the remote file to download. Cannot
	 * be null or empty.
	 *
	 * @return The size of the file referenced by $url, or -1 if the size
	 * could not be determined.
	 */
	public static function getFileSize($url){
		// Assume failure.
		$result = -1;

		$curl = curl_init($url);

		// Issue a HEAD request and follow any redirects.
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0');

		$data = curl_exec($curl);
		curl_close($curl);

		if ($data) {
			$content_length = "unknown";
			$status = "unknown";

			if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
				$status = (int) $matches[1];
			}

			if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
				$content_length = (int) $matches[1];
			}

			// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
			if ($status == 200 || ($status > 300 && $status <= 308)) {
				$result = $content_length;
			}
		}

		return $result;
	}

}
