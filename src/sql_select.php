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

function unframed_sql_columns ($names) {
    return (($names === NULL) || (count($names) === 0) ? "*" : implode(
        ",", array_map('unframed_sql_quote', $names)
        ));
}

function unframed_sql_order($order) {
    if (preg_match('/^(\S+)(?:$|\s+(DESC|ASC)$)/i', $order, $matches) !== 1) {
        throw new Unframed("Invalid SQL order by clause: ".$order."");
    }
    if (count($matches) === 3) {
        return unframed_sql_quote($matches[1]).' '.strtoupper($matches[2]);
    } else {
        return unframed_sql_quote($matches[1]).' ASC';
    }
}

function unframed_sql_orderBy($orders) {
    if ($orders === NULL || count($orders) === 0) {
        return "";
    }
    return " ORDER BY ".implode(", ", array_map('unframed_sql_order', $orders));
}

function unframed_sql_filterLike($filter, $like=NULL) {
    $whereFilter = array();
    $params = array();
    foreach ($filter as $column => $value) {
        if (!unframed_is_list($value)) {
            array_push($whereFilter, unframed_sql_quote($column)." = ?");
            array_push($params, $value);
        } elseif (count($value) > 0) {
            array_push($whereFilter, (
                unframed_sql_quote($column)
                ." IN (".implode(', ', array_fill(0, count($value), '?')).")"
                ));
            $params = array_merge($params, $value);
        }
    }
    if ($like !== NULL && count($like) > 0) {
        $whereLike = array();
        foreach ($like as $column => $value) {
            array_push($whereLike, unframed_sql_quote($column)." like ?");
            array_push($params, $value);
        }
        if (count($whereLike)>0) {
            array_push($whereFilter, "(".implode(" OR ", $whereLike).")");
        }
    }
    return array(implode(" AND ", $whereFilter), $params);
}

/**
 * List all non null values of $column in $table, eventually with a $where
 * clause, limited to 30 rows from offset 0 by default.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param string $column the name of the column to select
 *
 * @param string $where SQL clause by default NULL
 * @param array $params or NULL
 * @param int $offset default to 0
 * @param int $limit default to 30
 * @param array $orderBy
 *
 * @return array of values
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_column($pdo, $table, $column,
    $where=NULL, $params=NULL, $offset=0, $limit=30, $orderBy=NULL) {
    $st = $pdo->prepare(
        "SELECT ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." IS NOT NULL"
        .(($where === NULL) || ($where == "") ? "" : " AND ".$where)
        .unframed_sql_orderBy($orderBy)
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    return unframed_sql_fetchAll($st, $params, PDO::FETCH_COLUMN);
}

function unframed_sql_select_count($pdo, $table,
    $where=NULL, $params=NULL) {
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM ".unframed_sql_quote($table)
        .(($where === NULL) || ($where == "") ? "" : " WHERE ".$where)
        );
    return intval(unframed_sql_fetch($st, $params, PDO::FETCH_COLUMN));
}

/**
 * Select all distinct values for the $column in $table that is like $key.
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
    $like=NULL, $offset=0, $limit=30, $orderBy=NULL) {
    $st = $pdo->prepare(
        "SELECT DISTINCT ".unframed_sql_quote($column)
        ." FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($like==NULL?$column:$like)." like ? "
        .unframed_sql_orderBy($orderBy)
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
 * @param array $orderBy
 *
 * @return array of arrays
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_in($pdo, $table, $column, $values, $orderBy=NULL) {
    if (count($values) === 0) {
        return array();
    }
    $st = $pdo->prepare(
        "SELECT * FROM ".unframed_sql_quote($table)
        ." WHERE ".unframed_sql_quote($column)." IN ("
            .implode(', ', array_fill(0, count($values), '?'))
            .")"
        .unframed_sql_orderBy($orderBy)
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
 * @param array $columns names of the columns to select, means '*' if NULL or empty
 * @param string $where SQL expression, empty by default
 * @param array $params positional or keyed parameters
 * @param int $offset to select from, 0 by default
 * @param int $limit number of rows returned, 30 by default
 * @param array $orderBy
 *
 * @return array of rows
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_select_rows($pdo, $table,
    $columns=NULL, $where="", $params=NULL, $offset=0, $limit=30, $orderBy=NULL) {
    $sql = (
        "SELECT ".unframed_sql_columns($columns)
        ." FROM ".unframed_sql_quote($table)
        .($where === '' ? "" : " WHERE ".$where)
        .unframed_sql_orderBy($orderBy)
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    $st = $pdo->prepare($sql);
    return unframed_sql_fetchAll($st, $params, PDO::FETCH_ASSOC);
}

/**
 * Return $limit rows of $table from $offset where or fail.
 *
 * @param PDO $pdo the database connection to use
 * @param string $table the name of the table (or view) to select from
 * @param array $columns names of the columns to select, means '*' if NULL
 * @param string $filter
 * @param string $like
 * @param int $offset to select from, 0 by default
 * @param int $limit number of rows returned, 30 by default
 * @param array $orderBy
 *
 * @return array of rows
 *
 * @throws PDOException
 * @throws Unframed
 */
function unframed_sql_filter_rows($pdo, $table,
    $columns=NULL, $filter=NULL, $like=NULL, $offset=0, $limit=30, $orderBy=NULL) {
    list($where, $params) = unframed_sql_filterLike($filter, $like);
    $sql = (
        "SELECT ".unframed_sql_columns($columns)
        ." FROM ".unframed_sql_quote($table)
        .($where === '' ? '' : " WHERE ".$where)
        .unframed_sql_orderBy($orderBy)
        ." LIMIT ".strval($limit)." OFFSET ".strval($offset)
        );
    $st = $pdo->prepare($sql);
    return unframed_sql_fetchAll($st, $params, PDO::FETCH_ASSOC);
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
