<?php

require_once(dirname(__FILE__).'/sql_insert.php');

/**
 * Infer the SQL type of a column from a $value.
 *
 * Requires the PDO database to support the following SQL types:
 * TEXT, REAL and INTEGER.
 *
 * @param any $value
 */
function unframed_sql_type ($value) {
    if (is_float($value)) {
        return "REAL NOT NULL";
    } elseif (is_numeric($value)) {
        return "INTEGER NOT NULL";
    } elseif (is_string($value)) {
        return "TEXT NOT NULL";
    } else {
        return "TEXT";
    } 
}

/**
 * For the given PDO connection : if it does not exist create a 
 * $table with $primary key, $values's column names and types ;
 * insert or replace the given $values in the $table.
 *
 * Note that if a $primary key is left unspecified, the table name
 * is used instead. Also, scalar values will be encoded as JSON for
 * the database.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to delete from
 * @param string $values to insert or replace, indexed by column names
 * @param string $primary the name of the primary key column
 *
 * @return the number of rows affected, 1 on success.
 *
 * @throws PDOException, Unframed
 */
function unframed_sql_post($pdo, $table, $values, $primary) {
    $keys = array_keys($values);
    //
    $columns = implode(', ', array_map(function($key) use ($values) {
        $type = unframed_sql_type($values[$key]);
        return $key." ".$type;
    }, $keys));
    $sql = "CREATE TABLE ".$table." IF NOT EXIST (".$columns.", PRIMARY KEY (".$primary."));";
    $st = $pdo->prepare($sql);
    if (!$st->execute()) {
        throw new Unframed($st->errorInfo()[2]);
    }
    //
    $columns = implode(', ', $keys);
    $parameters = implode(', ', array_map(function($key) {
         return "?";
    }, $keys));
    $sql = $verb." INTO ".$table." (".$columns.") VALUES (".$parameters.")";
    $st = $pdo->prepare($sql);
    for ($index = 0, $L = count($keys); $index < $L; $index++) {
        $value = $values[$keys[$index]];
        if (is_scalar($value)) {
            $st->bindValue($index, $value); // flat is better ...
        } else {
            $st->bindValue($index, json_encode($value)); // ... than nested
        }
    }
    if (!$st->execute()) {
        throw new Unframed($st->errorInfo()[2]);
    }
    return $st->rowCount();
}
