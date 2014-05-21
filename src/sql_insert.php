<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

function unframed_sql_quote ($identifier) {
    return "'".$identifier."'";
}

function unframed_sql_parameter ($key) {
    return ":".$key;
}

/**
 * For the given PDO connection, insert $values in $table, mapping keys to columns
 * and parameters, using the verb 'insert' as SQL command by default.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to insert into
 * @param array $values to insert, associates column names and values
 * @param string $verb the optional SQL command to use instead of 'insert'
 *
 * @return the number of row affected 
 *
 * @throws PDOException if the $pdo error mode was set to exceptions
 * @throws Unframed if the execution failed without throwing a PDOException
 */
function unframed_sql_insert_values($pdo, $table, $values, $verb='INSERT') {
    $keys = array_keys($values);
    $columns = implode(', ', array_map('unframed_sql_quote', $keys));
    $parameters = implode(', ', array_map('unframed_sql_parameter', $keys));
    $sql = $verb." INTO ".$table." (".$columns.") VALUES (".$parameters.")";
    $st = $pdo->prepare($sql);
    if ($st->execute($values)) {
        return $st->rowCount();
    }
    throw new Unframed($st->errorInfo()[2]);
}

/**
 * For the given PDO connection, replace $values in $table, mapping keys to columns
 * and parameters.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to replace into
 * @param array $values to replace, associates column names and values
 *
 * @return the number of row affected, -1 if the statement execution failed 
 *
 * @throws PDOException if the $pdo error mode was set to exceptions
 * @throws Unframed if the execution failed without throwing a PDOException
 */
function unframed_sql_replace_values($pdo, $table, $values) {
    return unframed_sql_insert_values($pdo, $table, $values, $verb='REPLACE');
}
