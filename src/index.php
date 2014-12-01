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

require_once dirname(__FILE__).'/loop_json.php';
require_once dirname(__FILE__).'/mysql_json.php';
require_once dirname(__FILE__).'/sql_insert.php';
require_once dirname(__FILE__).'/sql_update.php';
require_once dirname(__FILE__).'/sql_delete.php';
require_once dirname(__FILE__).'/www_invalidate.php';
require_once dirname(__FILE__).'/jsbn.php';

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

/**
 * An Unframed Application Prototype
 */
class UnframedApplication {
	private $_relative;
	private $_domain;
	private $_options;
	private $_pdo;
	protected $_prefix;
	public function __construct ($relative = '', $domain = '_') {
		$this->_relative = $relative;
		$this->_domain = $domain;
	}
	/**
	 * Require $filename and return the result of $fun(), or throws an exception.
	 *
	 * @param string $filename to require
	 * @param string $fun to evaluate, 'unframed_application_options' by default
	 */
	protected function requireOptions ($filename, $fun = 'unframed_application_options') {
		if (file_exists($filename)) {
			require $filename;
			if (function_exists($fun)) {
				return call_user_func($fun);
			} else {
				throw new Unframed(
					'Missing '.$fun.'() in: '.$filename
					);
			}
		} else {
			throw new Unframed(
				'Application options file not found: '.$filename
				);
		}
	}
	/**
	 * Configure access to an SQL database and create a PDO connection or throw
	 * and exception.
	 *
	 * Mandatory options are :
	 *
	 * - 'sql_prefix', the global table prefix for this application
	 * - 'sql_dsn' or 'mysql_name'
	 *
	 * For MySQL connections, the 'mysql_user' and 'mysql_pass' are mandatory.
	 *
	 * You may also specify 'mysql_host' and 'mysql_port', the defaults are
	 * 'localhost' and '3306'.
	 *
	 * For other connections, the 'sql_username' and 'sql_password' are optionals
	 * and default to NULL.
	 *
	 * @param array $options of configuration
	 */
	protected function configureAndConnect ($options) {
		$m = unframed_message($options);
		$this->_options = $m;
		$this->_prefix = $m->getString('sql_prefix');
		if ($m->has('sql_dsn')) {
			$this->_pdo = unframed_sql_open(
				$m->getString('sql_dsn'),
				$m->getDefault('sql_username', NULL),
				$m->getDefault('sql_password', NULL)
				);
		} elseif ($m->has('mysql_name')) {
			$this->_pdo = unframed_mysql_open(
				$m->getString('mysql_name'),
				$m->getString('mysql_user'),
				$m->getString('mysql_pass'),
				$m->getString('mysql_host', 'localhost'),
				$m->getString('mysql_port', '3306')
				);
		} else {
			throw new Unframed(
				'Invalid configuration message in '.$configFilename
				.': '.json_encode($m->array)
				);
		}
	}
	/**
	 * Return this application's (configuration) options.
	 *
	 * @return UnframedMessage options.
	 */
	public function options () {
		return $this->_options;
	}
	/**
	 * Return this application's base filesystem path.
	 *
	 * @return string
	 */
	public function basePath() {
		return unframed_site_path($this->_relative);
	}
	/**
	 * Return this application's configuration filename.
	 *
	 * @return string
	 */
	public function configFilename() {
		return (
			dirname(__FILE__)
			.'/.config-'
			.sha1($this->basePath())
			.'.php'
			);
	}
	/**
	 * Return this application's base URI.
	 *
	 * @return string
	 */
	public function baseUri() {
		return unframed_site_uri($this->_relative);
	}
	/**
	 * Return this application's base URI key, a SHA1 hash of this site's URI.
	 *
	 * @return string
	 */
	public function baseUriKey() {
		return sha1($this->baseUri());
	}
	/**
	 * Return the PDO connection
	 */
	public function pdo () {
		return $this->_pdo;
	}
	/**
	 * Prefix a $name with this application's global database tables prefix.
	 *
	 * @param string $name to prefix
	 * @return string the prefixed name
	 */
	public function prefix ($name='') {
		return ($this->_prefix).$name;
	}
	/**
	 * Returns a table name in this application's domain, without prefix.
	 */
	public function table ($name) {
		return $this->_domain.$name;
	}
	/**
	 * Enclose a call to $fun with $array as arguments into an SQL transaction.
	 *
	 * @param callable $fun the function or method to apply
	 * @param array $arguments of the function, array($this) by default
	 * @return any the result of $fun
	 * @throws Unframed if the SQL transaction failed
	 */
	public function transaction ($fun, $arguments=NULL) {
		return unframed_sql_transaction($this->_pdo, $fun, (
			$arguments===NULL ? array($this): $arguments
			));
	}
	/**
	 * Execute the $sql statement, eventually with $parameters.
	 *
	 * @param string $sql statement to execute
	 * @param array $parameters, NULL by default
	 * @return any
	 */
	public function execute ($sql, $parameters=NULL) {
		return unframed_sql_statement($this->_pdo, $sql, $parameters);
	}
	/**
	 * Execute all the SQL $statements.
	 *
	 * @param string $statements to execute
	 */
	public function executeAll ($statements) {
		foreach($statements as $sql) {
			$this->execute($sql);
		}
	}
	/**
	 * Execute the $sql statement with $parameters and fetch the first result.
	 *
	 * @param string $sql statement
	 * @param array $parameters, by default NULL
	 * @param integer $mode, by default PDO::FETCH_ASSOC
	 * @return an array, an object or a value, depending on the $mode
	 */
	public function fetch ($sql, $parameters=NULL, $mode=PDO::FETCH_ASSOC) {
		return unframed_sql_fetch($this->_pdo->prepare($sql), $parameters, $mode);
	}
	/**
	 * Execute the $sql statement with $parameters and fetch all the results.
	 *
	 * @param string $sql statement
	 * @param array $parameters, by default NULL
	 * @param integer $mode, by default PDO::FETCH_ASSOC
	 * @return an array or arrays, objects or values, depending on the $mode
	 */
	public function fetchAll ($sql, $parameters=NULL, $mode=PDO::FETCH_ASSOC) {
		return unframed_sql_fetchAll($this->_pdo->prepare($sql), $parameters, $mode);
	}
	/**
	 * Execute the $sql statement with $parameters and fetch the first result as
	 * a single value.
	 *
	 * @param string $sql statement
	 * @param array $parameters, by default NULL
	 * @return string
	 */
	public function fetchColumn ($sql, $parameters=NULL) {
		return unframed_sql_fetch($this->_pdo->prepare($sql), $parameters, PDO::FETCH_COLUMN);
	}
	/**
	 * Execute the $sql statement with $parameters and fetch all the results as single
	 * values in a list.
	 *
	 * @param string $sql statement
	 * @param array $parameters, by default NULL
	 * @return array the list of selected values
	 */
	public function fetchAllColumn ($sql, $parameters=NULL) {
		return unframed_sql_fetchAll($this->_pdo->prepare($sql), $parameters, PDO::FETCH_COLUMN);
	}
	/**
	 * Return the count of all rows in the table with prefixed $name.
	 *
	 * @param string $name the unprefixed name of the table
	 * @return integer the count of all rows
	 */
	public function countRows ($name) {
		return intval($this->fetchColumn(
			"SELECT COUNT(*) FROM ".$this->prefix($name)
			));
	}
	/**
	 * Select from the table with prefixed $name a single row where $key = $column
	 *
	 * @param string $name the unprefixed name of the table
	 * @param integer $key to select
	 * @param string $column name to match, set to $name if not specified
	 */
	public function selectRow ($name, $key, $column=NULL) {
		return unframed_sql_select_row (
			$this->_pdo,
			$this->prefix($name),
			($column === NULL ? $name : $column),
			$key
			);
	}
	/**
	 * Select a limited set of rows from the table with prefixed $name,
	 * using the following $options:
	 *
	 * - 'columns', a list of column names to select or '*' if not specified
	 * - 'where', an SQL expression with parameters placeholders
	 * - 'params', the expression's parameters
	 * - 'offset', 0 if not specified
	 * - 'limit', 30 if not specified
	 * - 'order_by', a list of orders
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $options
	 * @return array of arrays
	 */
	public function selectRows ($name, $options=array()) {
		$m = new UnframedMessage($options);
		return unframed_sql_select_rows (
			$this->_pdo,
			$this->prefix($name),
			$m->getList('columns', array()),
			$m->getString('where', ''),
			$m->getArray('params', array()),
			$m->getInt('offset', 0),
			$m->getInt('limit', 30),
			$m->getList('order_by', array())
			);
	}
	/**
	 * Count the number of rows from the table with prefixed $name,
	 * using the $options:
	 *
	 * - 'where', an SQL expression with parameters placeholders
	 * - 'params', the expression's parameters
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $options
	 * @return integer
	 */
	public function selectCount ($name, $options = array()) {
		$m = new UnframedMessage($options);
		$where = $m->getString('where', '');
		$params = $m->getArray('params', array());
		return unframed_sql_select_count(
			$this->_pdo, $this->prefix($name), $where, $params
			);
	}
	/**
	 * Select a limited set of rows from the table with prefixed $name,
	 * using the following $options:
	 *
	 * - 'columns', a list of column names to select or '*' if not specified
	 * - 'filter', a map of column names to values
	 * - 'like', a map of column names to match expressions
	 * - 'offset', 0 if not specified
	 * - 'limit', 30 if not specified
	 * - 'order_by', a list of orders
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $options
	 * @return array of arrays
	 */
	public function filterRows ($name, $options = array()) {
		$m = new UnframedMessage($options);
		return unframed_sql_filter_rows (
			$this->_pdo,
			$this->prefix($name),
			$m->getList('columns', array()),
			$m->getMap('filter', array()),
			$m->getMap('like', array()),
			$m->getInt('offset', 0),
			$m->getInt('limit', 30),
			$m->getList('order_by', array())
			);
	}
	/**
	 * Count the number of rows from the table with prefixed $name,
	 * using the $options:
	 *
	 * - 'filter', a map of column names to values
	 * - 'like', a map of column names to match expressions
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $options
	 * @return integer
	 */
	public function filterCount ($name, $options = array()) {
		$m = new UnframedMessage($options);
		list($where, $params) = unframed_sql_filterLike(
			$m->getMap('filter', array()),
			$m->getMap('like', array())
			);
		return unframed_sql_select_count(
			$this->_pdo, $this->prefix($name), $where, $params
			);
	}
	/**
	 * Replace a $row in the table with prefixed $name, return the
	 * count of rows updated (ie: 1 on success).
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $row to replace
	 * @return integer count of rows updated
	 */
	public function replaceRow ($name, $row) {
		return unframed_sql_replace_values (
			$this->_pdo, $this->prefix($name), $row
			);
	}
	/**
	 * Insert a $row in the table with prefixed $name then return the
	 * key of the inserted relation on success or FALSE on failure.
	 *
	 * @param string $name the unprefixed name of the table
	 * @param array $row to insert
	 *
	 * @return any the inserted ID or FALSE
	 */
	public function insertRow ($name, $row) {
		if (unframed_sql_insert_values (
			$this->_pdo, $this->prefix($name), $row
			) === 1) {
			return $this->_pdo->lastInsertId();
		} else {
			return FALSE;
		}
	}
    /**
     * From MailPoet's table $name, fetch and decode all JSON as arrays
     *
     * @param string $name of the MailPoet's table
     * @return an array of associative arrays
     */
    public function fetchAllJSON ($name) {
        return array_map('unframed_json_decode', $this->fetchAllColumn(
            "SELECT ".$name."_json FROM ".$this->prefix($this->table($name))
            ));
    }
    /**
     * For a given primary $key, fetch the encoded JSON string found in the
     * `{$name}_json` column of the prefixed MailPoet table with $name.
     *
     * @param string $name of the MailPoet table
     * @param string $key of the row
     *
     * @return string encoded JSON
     */
	public function getJSONString ($name, $key) {
		$row = unframed_sql_select_row(
			$this->pdo(), $this->prefix($this->table($name)), $name, $key
			);
		return $row[$name.'_json'];
	}
    /**
     * For a given primary $key, fetch the decoded JSON array found in the
     * `{$name}_json` column of the prefixed MailPoet table with $name.
     *
     * @param string $name of the MailPoet table
     * @param string $key of the row
     *
     * @return array decoded JSON
     */
	public function getJSON ($name, $key) {
		$json = $this->getJSONString($name, $key);
		if (isset($json)) {
			return json_decode($json, TRUE);
		}
	}
    /**
     * Insert the given $values in the MailPoet table with $name and return
     * the inserted row primary key.
     *
     * @param string $name of the MailPoet table
     * @param array $values to insert
     *
     * @return int the inserted primary key
     */
    public function insertJSON ($name, $values) {
        return unframed_sql_json_insert(
            $this->pdo(), $this->prefix($this->_domain), $name, $values
            );
    }
    /**
     * Replace the given $values in the MailPoet table with $name.
     *
     * @param string $name of the MailPoet table
     * @param array $values to replace
     *
     * @return integer the number of rows updated
     */
	public function replaceJSON ($name, $values) {
		return unframed_sql_json_replace(
			$this->pdo(), $this->prefix($this->_domain), $name, $values
			);
	}
    /**
     * Update the given $values in the MailPoet table with $name.
     *
     * @param string $name of the MailPoet table
     * @param array $values to update
     *
     * @return integer the number of rows updated
     */
    public function updateJSON($name, $values) {
        if (!array_key_exists($name, $values)) {
            throw new Unframed("Missing primary key '".$name."' in values");
        }
        $key = $values[$name];
        $json = $this->getJSON($name, $key);
        if ($json !== NULL) {
            foreach ($values as $column => $value) {
                $json[$column] = $value;
            }
            return $this->replaceJSON($name, $json);
        } else {
            throw new Unframed("Primary key '".$key."'' not found in ".$name);
        }
    }
    /**
     * Select a limited set of JSON objects from the MailPoet table $name,
     * using the following $options:
     *
	 * - 'where', an SQL expression with parameters placeholders
	 * - 'params', the expression's parameters
     * - 'offset', 0 if not specified
     * - 'limit', 30 if not specified
     * - 'order_by', a list of orders
     *
     * @param string $name the unprefixed name of the table
     * @param array $options
     * @return array a list of JSON encoded string
     */
    public function selectJSON ($name, $options=array()) {
        $m = new UnframedMessage($options);
        return unframed_sql_json_select(
            $this->pdo(),
            $this->prefix($this->_domain),
            $name,
            $m->getString('where', ''),
            $m->getArray('params', array()),
            $m->getInt('offset', 0),
            $m->getInt('limit', 30),
            $m->getList('order_by', array())
            );
    }
    /**
     * Select a limited set of JSON objects from the MailPoet table $name,
     * using the following $options:
     *
     * - 'filter', a map of column names to values
     * - 'like', a map of column names to match expressions
     * - 'offset', 0 if not specified
     * - 'limit', 30 if not specified
     * - 'order_by', a list of orders
     *
     * @param string $name the unprefixed name of the table
     * @param array $options
     * @return array a list of JSON encoded string
     */
    public function filterJSON ($name, $options=array()) {
        $m = new UnframedMessage($options);
        return unframed_sql_json_filterLike(
            $this->pdo(),
            $this->prefix($this->_domain),
            $name,
            $m->getMap('filter', array()),
            $m->getMap('like', array()),
            $m->getInt('offset', 0),
            $m->getInt('limit', 30),
            $m->getList('order_by', array())
            );
    }
}
