<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

// The global array used by all other functions
$CrawlerConfig = array();

class CrawlerConfig{
	
	public static function init($ini = null){
		$base_dir = realpath(dirname(dirname(__FILE__)));
		if(empty($ini)) $ini = self::findIni();
		$GLOBALS['CrawlerConfig'] = parse_ini_file("$base_dir/instances/$ini");
		$GLOBALS['CrawlerConfig']["BASE_DIR"] = $base_dir;
		$GLOBALS['CrawlerConfig']["INI_NAME"] = $ini;
	}
	
	private static function findIni(){
		$base_dir = realpath(dirname(dirname(__FILE__)))."/instances";
		if(file_exists("$base_dir/default.ini")) return "$base_dir/default.ini";
		$files = scandir($base_dir);
		foreach($files as $file)
			if($file !== "." && $file !== "..") return $file;
		return false;
	}
	
}

CrawlerConfig::init();