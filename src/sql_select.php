<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

unframed_no_script(__FILE__);

/**
 * Select all distinct values for the $column in $table
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param string $column the name of the column to select
 *
 * @return array of values
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_column($pdo, $table, $column, $constraint="DISTINCT") {
    $st = $pdo->prepare(
        "SELECT ".$constraint." ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        );
    if ($st->execute(array())) {
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Select all values for the $column in $table that match $like
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param string $column the name of the column to match
 * @param string $like the value to match
 *
 * @return array of values
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_like($pdo, $table, $column, $like, $constraint="DISTINCT") {
    $st = $pdo->prepare(
        "SELECT ".$constraint." ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." like ?"
        );
    $st->bindValue(1, $key);
    if ($st->execute()) {
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Return as an object the first row of $table where $column equals $key
 * or NULL if the SQL statement's execution failed.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param string $column the name of the column to equal
 * @param string $key the value to equal
 *
 * @return array of values by columns' names
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_object($pdo, $table, $column, $key) {
    $sql = (
        "SELECT * FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." = ?"
        );
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $key);
    if ($st->execute()) {
        return $st->fetch(PDO::FETCH_ASSOC);
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Return $limit rows of $table from $offset where or fail.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param int $offset to select from, 0 by default
 * @param int $limit number of rows returned, 30 by default
 *
 * @return array of rows
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_objects($pdo, $table, $offset=0, $limit=30) {
    $sql = (
        "SELECT * FROM ".unframed_sql_quote($table)
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    $st = $pdo->prepare($statement);
    if ($st->execute()) {
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}

/**
 * Return an array of objects mapping values by column names or NULL
 * if the SQL statement's execution failed.
 *
 * @param PDO $pdo the database connection to use
 * @param string $statement SQL select statement
 * @param array $parameters to use
 *
 * @return array of rows
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select($pdo, $statement, $parameters) {
    $st = $pdo->prepare($statement);
    if ($st->execute($parameters)) {
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    $info = $st->errorInfo();
    throw new Unframed($info[2]);
}
