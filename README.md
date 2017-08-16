# Crawler

Crawler is a highly flexible PHP web crawler library with a few cool features.

### Features
 - Can log into a website to crawl pages that require authentication.
 - Uses two table in a MySQL or Oracle database to store crawled website data, and creates the table automatically the first time the crawler is run.
 - Can be configured to skip pages that match a given string.
 - Saves body text with all HTML stripped for easy searching.
 - Mines Emails
 - Browser based UI & CLI isage

### Usage
 1. Download and unzip the source files.
 2. Open /crawler/config.php and add your configuration details. (see below)
 3. Upload the files to your server and run
 4. To start crawling, run doCrawl.php in your in your browser or console, or to continue crawling indefinitely, open search.php in the browser and click the “crawl” button in the top right.
 5. After you’ve crawled some pages you can search the results via the search.php script.

### Configuration
The crawler/config.php file needs to be populated before trying to crawl any websites. This is an explanation of the options.
 - **BASE_URL** – The base URL of the website to crawl. Include scheme/protocol and no trailing “/”
 - **INIT_PATH** – The relative path to the page that will be crawled first. Should begin with a “/” and include the filename, ie “/index.php.”
 - **AUTH** – This array will be POSTed to the login endpoint after generating a query string using http_build_query()
 - **LOGIN_ENDPOINT** – String – the relative path to start the session. Login details will be POSTed here.
 - **FAILED_LOGIN_INDICATOR** – String – A unique string that ONLY appears on the failed login response If this string is found in the response after attempting logging in, the script assumes that login failed and exits.
 - **PDO_CONFIG** – Array of MySQL database access details, including host, username, password, database.
 - **CRAWLER_URLS_TABLE** – The name of the table to store the results in. This table will be automatically generated with this name the first time you run the script.
 - **FOLLOW_LINKS_LIKE** – Only follow links that contain this substring. Leave empty to follow all links. This is useful if you only want to crawl one domain.
 - **IGNORE_LINKS_LIKE** – Array of strings. If any of the links match or contain any of these strings, they will be ignored.

### Version
1.2

### Legal
Released under [Apache 2.0] License - Copywrite 2016 Robert Parham, All rights reserved.

   [Apache 2.0]: <http://www.apache.org/licenses/LICENSE-2.0>


