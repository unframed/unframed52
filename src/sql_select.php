<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

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
function unframed_sql_select_column($pdo, $table, $column, $constraint="") {
    $st = $pdo->prepare("SELECT ".$constraint." ".$column." FROM ".$table);
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
function unframed_sql_select_like($pdo, $table, $column, $like) {
    $st = $pdo->prepare("SELECT ".$column." FROM ".$table." WHERE ".$column." = ?");
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
    $sql = "SELECT * FROM ".$table." WHERE ".$column." = ?";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $key);
    if ($st->execute()) {
        return $st->fetch(PDO::FETCH_ASSOC);
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
 * @return arrays of values by columns' names
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
