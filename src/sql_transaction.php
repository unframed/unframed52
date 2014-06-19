<?php 

require_once(dirname(__FILE__).'/Unframed.php');

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
function unframed_sql_open($dsn, $username=NULL, $password=NULL) {
    $pdo = new PDO($dsn, $username, $password);
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
    return unframed_sql_open('sqlite:'.$path.$filename, NULL, NULL);
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
 * Prepare, bind, execute and return a PDOStatement or throw 
 * an Unframed exception if the SQL statement's execution failed 
 * without PDOException.
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
function unframed_sql_execute($pdo, $sql, $parameters) {
    $st = $pdo->prepare($sql);
    if ($st->execute($parameters)) {
        return $st;
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Prepare and execute a parametrized SQL statement, then either: fetch and return
 * all SELECTed results; return the number of row INSERTed, UPDATEd or DELETEd;
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
    if ($st->execute($parameters)) {
        if (preg_match('/^select/i', $statement)>0) {
            return array("fetchAll"=>$st->fetchAll());
        } elseif (preg_match('/^(insert|replace|update|delete)/i', $statement)>0) {
            return array("rowCount"=>$st->rowCount());
        }
        return array();
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
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
        $st = $pdo->prepare($sql);
        if (!$st->execute()) {
            $info = $st->errorInfo();
            throw new Unframed($info[2]);
        }
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
