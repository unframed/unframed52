<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

unframed_no_script(__FILE__);

/**
 * Infer the SQL type of a column from a $value.
 *
 * Requires the PDO database to support the NOT NULL constraint,
 * types TEXT, REAL and INTEGER.
 *
 * @param any $value
 * @param string $longtext the SQL type for text blobs, by default "TEXT" 
 */
function unframed_sql_json_type ($value, $text="TEXT", $longtext="TEXT") {
    if ($value==NULL) {
        // NULL is a nullable relation, usually an INTEGER row index.
        return "INTEGER";
    } elseif ($value==FALSE) {
        return "SMALLINT NOT NULL DEFAULT 0";
    } elseif ($value==TRUE) {
        return "SMALLINT NOT NULL DEFAULT 1";
    } elseif (is_int($value)) {
        // 4 bytes in MySQL & PostgreSQL, possibly more in SQLite.
        return "INTEGER NOT NULL";
    } elseif (is_numeric($value)) {
        // SQLite, MySQL & PostgreSQL.
        return "NUMERIC NOT NULL"; 
    } elseif (is_string($value)) {
        // one size fit all strings (as long as it's 256 character long in MySQL)
        return $text." NOT NULL"; 
    } elseif (is_array($value) || is_object($value)) {
        // arrays and objects are JSON in (LONG)TEXT
        return $longtext;
    } else {
        throw new Unframed('Type Error - do not store '.gettype($value).' in SQL');
    }
}

/**
 * Create a table from a name and a JSON model.
 */
function unframed_sql_json_table ($prefix, $name, $model) {
    $columns = array(unframed_sql_quote($name."_json")." TEXT");
    if (!array_key_exists($name, $model)) {
        array_push($columns, unframed_sql_quote($name)." INTEGER AUTOINCREMENT PRIMARY KEY");
    }
    foreach($model as $key => $value) {
        if (is_scalar($value)) {
            array_push($columns, unframed_sql_quote($key)." ".unframed_sql_json_type($value));
        }
    }
    return (
        "CREATE TABLE ".unframed_sql_quote($prefix.$name)." (\n    "
            .implode(",\n    ", $columns)
            .",\n    PRIMARY KEY (".unframed_sql_quote($name).")\n    )"
        );
}

/**
 * Return an SQL schema as an array of SQL strings for the given $models 
 * using a $factory to create tables, a $prefix to fully qualify their names, 
 * skipping tables that $exists.
 *
 * For each named relation in $models create a prefixed table if it does not exist yet
 * with indexes for all its scalar values, assert all column names are unique and use 
 * the data type found in the $models for columns.
 *
 */
function unframed_sql_json_schema ($prefix, $models, $factory, $exist) {
    $indexes = array();
    $statements = array();
    foreach($models as $name => $model) {
        if (!array_key_exists($prefix.$name, $exist)) {
            array_push($statements, $factory($prefix, $name, $model));
            foreach($model as $key => $value) {
                if (is_scalar($value) && $value != NULL) {
                    if (array_key_exists($key, $indexes)) {
                        throw new Unframed('Name Error - column name '.$key.' is not unique');
                    }
                    $indexes[$key] = $name;
                    array_push($statements, (
                        "CREATE INDEX ".unframed_sql_quote($prefix.$key)
                        ." ON ".unframed_sql_quote($prefix.$name)."(".unframed_sql_quote($key).")"
                        ));
                }
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
        if (is_scalar($value)) {
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
        unframed_sql_json_bind($st, $values[$keys[$index]], $index+1);
    }
    unframed_sql_execute($st);
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
function unframed_sql_json_insert ($pdo, $prefix, $name, $array, $verb='INSERT') {
    $values = unframed_sql_json_write($name, $array);
    $keys = array_keys($values);
    $L = count($keys);
    $columns = implode(', ', array_map('unframed_sql_quote', $keys));
    $parameters = implode(', ', array_fill(0, $L, '?'));
    $sql = (
        $verb." INTO ".unframed_sql_quote($prefix.$name)
        ." (".$columns.") VALUES (".$parameters.")"
        );
    return unframed_sql_json_execute($pdo->prepare($sql), $values, $keys);
}

function unframed_sql_json_replace ($pdo, $prefix, $name, $array) {
    return unframed_sql_json_insert ($pdo, $prefix, $name, $array, 'REPLACE');
}

function unframed_sql_json_select ($pdo, $prefix, $name, $parameters,
    $offset=0, $limit=30) {
    $where = array();
    $params = array();
    foreach ($parameters as $key => $value) {
        array_push($where, unframed_sql_quote($key)." = ?");
        array_push($params, $value);
    }
    return unframed_sql_select_column (
        $pdo, $prefix.$name, $name.'_json', implode(" AND ", $where), 
        $params, $offset, $limit 
        );
}

function unframed_sql_json ($pdo, $prefix, $models, $factory, $exist=NULL) {
    unframed_sql_declare($pdo, unframed_sql_json_schema(
        $prefix, $models, $factory, ($exist == NULL ? array() : $exist)
        ));
    foreach($models as $name => $value) {
        if ($exist===NULL && !array_key_exists($prefix.$name, search)) {
            unframed_sql_transaction($pdo, 'unframed_sql_json_insert', array(
                $pdo, $prefix.$name, $value
                ));
        }
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
function unframed_sqlite_json ($filename, $prefix, $models, $path='./') {
    $is_old = file_exists($path.$filename);
    $pdo = unframed_sqlite_open($filename, $path);
    if (!$is_old) {
        unframed_sql_json($pdo, $prefix, $models, 'unframed_sql_json_table');
    }
    return $pdo;
}
