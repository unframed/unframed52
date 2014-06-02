<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

/**
 * Infer the SQL type of a column from a $value.
 *
 * Requires the PDO database to support the NOT NULL constraint,
 * types TEXT, REAL and INTEGER.
 *
 * @param any $value
 */
function unframed_sql_json_type ($value) {
    if (is_bool($value)) {
        // there is no SQL boolean data type, 2 bytes everywhere.
        return "SMALLINT DEFAULT 0";
    } elseif (is_int($value)) {
        // 8 bytes in MySQL & PostgreSQL, possibly more in SQLite.
        return "BIGINT NOT NULL";
    } elseif (is_numeric($value)) {
        // SQLite, MySQL & PostgreSQL.
        return "NUMERIC NOT NULL"; 
    } elseif (is_string($value)) {
        // one size fit all strings.
        return "TEXT NOT NULL"; 
    } elseif ($value==NULL || is_array($value) || is_object($value)) {
        // NULL, arrays and objects are JSON in TEXT
        return "TEXT"; 
    } else {
        throw new Unframed('Type Error - do not store '.gettype($value).' in SQL');
    }
}

/**
 * Create a table from a name and a JSON model.
 */
function unframed_sql_json_table ($name, $model) {
    $columns = array($name."_json TEXT");
    if (!array_key_exists($name, $model)) {
        array_push($columns, $name." INTEGER PRIMARY KEY AUTOINCREMENT");
    }
    foreach($model as $key => $value) {
        if (is_scalar($value)) {
            array_push($columns, $key." ".unframed_sql_json_type($value));
        }
    }
    return ("CREATE TABLE ".$name." ("
        .implode(', ', $columns).", PRIMARY KEY (".$name."))");
}

function unframed_sql_json_index ($name, $column) {
    return ;
}

/**
 * Create indexes for all scalar values, assert all column names are unique.
 */
function unframed_sql_json_schema ($models) {
    $indexes = array();
    $statements = array();
    foreach($models as $name => $model) {
        array_push($statements, unframed_sql_json_table($name, $model));
        foreach($model as $key => $value) {
            if (is_scalar($value)) {
                if (array_key_exists($key, $indexes)) {
                    throw new Unframed('Name Error - '.$key.' is not unique');
                }
                $indexes[$key] = $name;
                array_push($statements, "CREATE INDEX ".$key." ON ".$name."(".$key.")");
            }
        }
    }
    return $statements;
}

/**
 * Map the scalar values in an associative $array into 
 * a result row, plus its JSON encoded string as $name.'_json'.
 */
function unframed_sql_json_write ($name, $array) {
    $row = array($name.'_json' => json_encode($array));
    foreach ($array as $key => $value) {
        if (is_scalar($array)) {
            $row[$key] = $value;
        }
    }
    return $row;
}

function unframed_sql_json_bind ($st, $value, $index) {
    if (!is_scalar($value)) {
        throw new Unframed('cannot bind non scalar '.var_export($value)); // unreachable ?
    } elseif (is_int($value)) {
        return $st->bindValue($index, $value, PDO::PARAM_INT);
    } elseif (is_bool($value)) {
        return $st->bindValue($index, $value, PDO::PARAM_BOOL);
    } elseif (is_null($value)) {
        return $st->bindValue($index, $value, PDO::PARAM_NULL);
    } else {
        return $st->bindValue($index, $value); // String
    }
}

function unframed_sql_json_execute ($st, $values, $keys) {
    $L = count($keys);
    for ($index = 0; $index < $L; $index++) {
        unframed_sql_json_bind($st, $values[$keys[$index]], $index);
    }
    if (!$st->execute()) {
        throw new Unframed($st->errorInfo()[2]);
    }
    return $st->rowCount();
}

/**
 * Prepare an SQL statement to insert (or replace) $values in $table, encode 
 * non-scalars parameters as JSON, execute the statements or fail.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to delete from
 * @param string $values to insert or replace, indexed by column names
 *
 * @return the number of rows affected, 1 on success.
 *
 * @throws Unframed if the statement failed without throwing a PDO exception
 */
function unframed_sql_json_insert ($pdo, $table, $array, $verb='INSERT') {
    $values = unframed_sql_json_write($table, $array);
    $keys = array_keys($values);
    $L = count($keys);
    $columns = implode(', ', $keys);
    $parameters = implode(', ', array_fill(0, $L, '?'));
    $sql = $verb." INTO ".$table." (".$columns.") VALUES (".$parameters.")";
    return unframed_sql_json_execute($pdo->prepare($sql), $values, $keys);
}

function unframed_sql_json_replace ($pdo, $table, $array) {
    return unframed_sql_json_insert ($pdo, $table, $array, 'REPLACE');
}

function unframed_sql_json ($pdo, $models, $path='./') {
    unframed_sql_declare($pdo, unframed_sql_json_schema($models));
    foreach($models as $table => $value) {
        unframed_sql_transaction($pdo, 'unframed_sql_json_insert', array(
            $pdo, $name, $value
            ));
    }
}

/**
 * Open an SQLite database, if new declare a schema from the JSON $models
 * before returning an opened PDO connection of fail.
 *
 * @param string $filename of the SQLite database to open
 * @param array $models to map to an SQL schema
 * @param string $path to prefix the database name, defaults to './'
 *
 * @return PDO connection
 * @throws Unframed or PDOException
 */
function unframed_sqlite_json ($filename, $models, $path='./') {
    $is_old = file_exists($path.$filename);
    $pdo = unframed_sqlite_open($filename, $path);
    if (!$is_old) {
        unframed_sql_json($pdo, $models);
    }
    return $pdo;
}