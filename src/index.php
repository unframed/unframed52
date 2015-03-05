<?php

/**
 * Includes all the unframed application conveniences and wrap an `UnframedApplication`
 * class with methods for :
 *
 * - fail-fast PDO connection and transactions.
 * - common SQL statements with prefixed table names.
 * - insert, replace, update and select JSON objects with an SQL database.
 *
 */

require_once dirname(__FILE__).'/Unframed.php';

unframed_no_script(__FILE__);

require_once dirname(__FILE__).'/get_json.php';
require_once dirname(__FILE__).'/post_json.php';
require_once dirname(__FILE__).'/cast_json.php';
require_once dirname(__FILE__).'/loop_json.php';
require_once dirname(__FILE__).'/www_invalidate.php';

/**
 * Get the file system "base" path to a $relative path.
 *
 * For instance :
 *
 *   unframed_site_path ('/wp-content/plugin/');
 *
 * Yields this on our <who-is-your-daddy.com> test site :
 *
 *   "/var/chroot/home/content/p3pnexwpnas03_data03/28/2265028/html/"
 *
 * @param string $relative path to search for in the SCRIPT_FILENAME.
 * @return string
 */
function unframed_site_path ($relative='') {
	$path = $_SERVER['SCRIPT_FILENAME'];
	return (
		$relative == '' ?
		dirname($path).'/':
		substr($path, 0, strpos($path, $relative)).'/'
		);
}

/**
 * Return the HTTP site's domain and path, using the REQUEST_URI, SERVER_NAME and
 * SERVER_PORT, eventually up to a $relative path.
 *
 * For instance :
 *
 *   unframed_site_uri ('/wp-content/plugin/');
 *
 * Will yield the strings :
 *
 * - "127.0.0.1:8089"
 * - "yoursite.com:80"
 * - "subdomain.yoursite.com:80"
 * - "yoursite.com:80/subdomain"
 *
 * Respectively when called from requests to :
 *
 * - http://127.0.0.1:8089/wp-content/plugin/whatever/script.php
 * - https://yoursite.com/wp-content/plugin/whatever/script.php
 * - http://subdomain.yoursite.com/wp-content/plugin/whatever/script.php
 * - http://yoursite.com/subdomain/wp-content/plugin/whatever/script.php
 *
 * @param string $relative default to ''
 * @return string
 */
function unframed_site_uri ($relative='') {
	$uri = $_SERVER['REQUEST_URI'];
	$path = (
		$relative == '' ?
		dirname($uri) :
		substr($uri, 0, strpos($uri, $relative))
		);
	return $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$path;
}
