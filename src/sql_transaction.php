<?php

require_once(dirname(__FILE__).'/Unframed.php');

unframed_no_script(__FILE__);

function unframed_sql_quote ($identifier) {
    if (strlen($identifier) > 64) {
        // MySQL identifiers are limited to 64 characters ...
        throw new Unframed("Error - too long SQL identifier: ".$identifier);
    } elseif (strpos($identifier, "`")) {
        // possible SQL injection !
        throw new Unframed("Error - Quote found in SQL identifier: ".$identifier);
    }
    return "`".$identifier."`";
}

/**
 * Opens a database connection, sets its error mode to PDO::ERRMODE_EXCEPTION
 * and return a PDO object.
 *
 * @param string $dsn the distinguished name of the database
 * @param string $username
 * @param string $password
 *
 * @return PDO
 */
function unframed_sql_open($dsn, $username=NULL, $password=NULL, $options=NULL) {
    $pdo = new PDO($dsn, $username, $password, (
        $options === NULL ? array() : $options
        ));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

/**
 * Opens a PDO connection to an SQLite `$database` in the application's sql path
 * sets its error mode to PDO::ERRMODE_EXCEPTION and return a PDO object.
 *
 * @param string $filename of the database to open
 *
 * @return PDO
 */
function unframed_sqlite_open ($filename, $path='./') {
    return unframed_sql_open('sqlite:'.$path.$filename);
}

/**
 * Open a MySQL database and return a PDO of fail.
 *
 * @param string $name of the MySQL database connection to open
 * @param string $user
 * @param string $password
 * @param string $host, by default 'localhost'
 *
 * @return PDO connection
 * @throws Unframed or PDOException
 */
function unframed_mysql_open ($name, $user, $password, $host='localhost', $port='3306') {
    $dsn = 'mysql:host='.$host.';port='.$port.';dbname='.$name;
    $pdo = unframed_sql_open(
        $dsn, $user, $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
    return $pdo;
}

/**
 * Begin a transaction on $pdo, apply ($fun, $array), commit the transaction
 * and return the result or catch any Exception, roll back the transaction and
 * throw an Unframed exception.
 *
 * @param PDO $pdo the database connection to use
 * @param function $fun to apply
 * @param array $array of arguments, default to array($pdo)
 *
 * @return any $fun result
 *
 * @throws Unframed
 */
function unframed_sql_transaction($pdo, $fun, $array) {
    $transaction = FALSE;
    if (!isset($array)) {
        $array = array($pdo);
    }
    try {
        $transaction = $pdo->beginTransaction();
        $result = unframed_call($fun, $array);
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        if ($transaction) {
            $pdo->rollBack();
        }
        throw new Unframed($e->getMessage(), 500, $e);
    }
}

/**
 * Execute a prepared statement eventually with parameters and return TRUE
 * on success or throw an Unframed exception if the SQL statement's execution
 * failed without PDOException.
 *
 * @param PDO $pdo the database connection to use
 * @param string $statement to execute
 * @param array $parameters to apply
 *
 * @return PDOStatement
 *
 * @throws PDOException if $pdo error mode was set to exceptions
 * @throws Unframed if the execution failed without PDOException
 */
function unframed_sql_execute($st, $parameters=NULL) {
    if ($parameters != NULL) {
        foreach ($parameters as $index => $value) {
            if (is_int($index)) {
                $index =+ 1;
            }
            if ($value == NULL) {
                $st->bindValue($index, $value, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $st->bindValue($index, $value, PDO::PARAM_INT);
            } else {
                $st->bindValue($index, $value);
            }
        }
    }
    if ($st->execute()) {
        return TRUE;
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Prepare and execute a parametrized SQL statement, then either: fetch and return
 * all SELECTed or SHOWed results; return the number of row INSERTed, UPDATEd or DELETEd;
 * or return an empty array for any other type of SQL statement.
 *
 * @param PDO $pdo connection to the SQL database
 * @param string $statement to prepare and execute
 * @param array $parameters to apply
 *
 * @return any
 * @throws PDOException
 */
function unframed_sql_statement ($pdo, $statement, $parameters) {
    $st =  $pdo->prepare($statement);
    if (unframed_sql_execute($st, $parameters)) {
        if (preg_match('/^(select|show)/i', $statement)>0) {
            return array("fetchAll"=>$st->fetchAll());
        } elseif (preg_match('/^(insert|replace|update|delete)/i', $statement)>0) {
            return array("rowCount"=>$st->rowCount());
        }
        return array();
    }
}

/**
 * Prepare and execute many SQL statements without parameters
 *
 * @param PDO $pdo connection to the SQL database
 * @param array $statements to prepare and execute
 *
 * @return TRUE
 * @throws PDOException
 */
function unframed_sql_statements ($pdo, $statements) {
    foreach ($statements as $sql) {
        unframed_sql_execute($pdo->prepare($sql));
    }
    return TRUE;
}

/**
 * Prepare and execute an array of SQL statements or throw an Unframed
 * exception if an execution failed without PDOException.
 *
 * @param PDO $pdo the database connection to use
 * @param array $statements to execute
 *
 * @return TRUE
 *
 * @throws PDOException if $pdo error mode was set to exceptions
 * @throws Unframed if the execution failed without PDOException
 */
function unframed_sql_declare($pdo, $statements) {
    return unframed_sql_transaction($pdo, 'unframed_sql_statements', array($pdo, $statements));
}
