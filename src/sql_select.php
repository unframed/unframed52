<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

unframed_no_script(__FILE__);

function unframed_sql_fetch($st, $parameters, $mode) {
    if (unframed_sql_execute($st, $parameters)) {
        return $st->fetch($mode);
    }
}

function unframed_sql_fetchAll($st, $parameters, $mode) {
    if (unframed_sql_execute($st, $parameters)) {
        return $st->fetchAll($mode);
    }
}

/**
 * List all (distinct) non null values of $column in $table, eventually $whereAndOrder
 * by some expression, limited to 30 rows from offset 0 by default.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param string $column the name of the column to select
 *
 * @param string $whereAndOrder SQL clause by default NULL
 * @param array $params or NULL
 * @param int $offset default to 0
 * @param int $limit default to 30
 * @param int $constraint default "DISTINCT"
 *
 * @return array of values
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_column($pdo, $table, $column,
    $whereAndOrder="", $params=NULL, $offset=0, $limit=30) {
    $st = $pdo->prepare(
        "SELECT ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." IS NOT NULL"
        .($whereAndOrder == "" ? "" : " AND ".$whereAndOrder)
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    return unframed_sql_fetchAll($st, $params, PDO::FETCH_COLUMN);
}

/**
 * Select all values for the $column in $table that is like $key.
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
function unframed_sql_select_like($pdo, $table, $column, $key,
    $like=NULL, $offset=0, $limit=30) {
    $st = $pdo->prepare(
        "SELECT DISTINCT ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($like==NULL?$column:$like)." like ? "
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    $st->bindValue(1, $key);
    return unframed_sql_fetchAll($st, NULL, PDO::FETCH_COLUMN);
}

/**
 * Select all rows in $table with $column in $val.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table (or view) to select from
 * @param string $column in wich to select values
 * @param string $values to select
 * @param string $offset to paginate from, defaults to 0
 * @param string $limit of the page, defaults to 30
 *
 * @return array of arrays
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_in($pdo, $table, $column, $values,
    $offset=0, $limit=30) {
    $st = $pdo->prepare(
        "SELECT * FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." IN ("
            .implode(', ', array_fill(0, count($values), '?'))
            .")"
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    return unframed_sql_fetchAll($st, $values, PDO::FETCH_ASSOC);
}

/**
 * Return the first row of $table where $column equals $key
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
function unframed_sql_select_row($pdo, $table, $column, $key) {
    $sql = (
        "SELECT * FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." = ?"
        );
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $key);
    return unframed_sql_fetch($st, NULL, PDO::FETCH_ASSOC);
}

/**
 * Return $limit rows of $table from $offset where or fail.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param array $columns names of the columns to select, means '*' if NULL
 * @param string $whereAndOrder SQL clause by default NULL
 * @param int $offset to select from, 0 by default
 * @param int $limit number of rows returned, 30 by default
 *
 * @return array of rows
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_rows($pdo, $table,
    $columns=NULL, $whereAndOrder="", $offset=0, $limit=30) {
    $sql = (
        "SELECT ".($columns==NULL ? "*" : implode(
            ",", array_map('unframed_sql_quote', $columns)
            ))
        ." FROM ".unframed_sql_quote($table)
        .$whereAndOrder
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    $st = $pdo->prepare($sql);
    return unframed_sql_fetchAll($st, NULL, PDO::FETCH_ASSOC);
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
    return unframed_sql_fetchAll($st, $parameters, PDO::FETCH_ASSOC);
}
