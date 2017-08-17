<?php
/* Crawler - A website indexer (and stuff)
 * @author Robert Parham
 * @license Apache 2.0 Lic.
 */

class CrawlerConfig{
	public static function init(){
		$GLOBALS['CrawlerConfig'] = parse_ini_file(realpath(dirname(dirname(__FILE__)))."/crawler.ini");
	}
}

CrawlerConfig::init();