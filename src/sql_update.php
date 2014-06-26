<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

unframed_no_script(__FILE__);

function unframed_sql_update_set ($key) {
    return unframed_sql_quote($key)." = :".$key;
}

/**
 * For the given PDO connection, update $values in $table where $column
 * equals $key.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to delete from
 * @param strong $column the name of the column
 * @param string $key the value of the key
 * @param array $values values to update by column names
 *
 * @return the number of row affected, -1 if the statement execution failed 
 *
 * @throws PDOException
 */
function unframed_sql_update_key($pdo, $table, $column, $key, $values) {
    $updates = implode(', ', array_map('unframed_sql_update_set', array_keys($values)));
    $values['unframed_sql_update_key'] = $key;
    $sql = "UPDATE ".unframed_sql_quote($table)
        ." SET ".$updates." WHERE ".unframed_sql_quote($column)." = :unframed_sql_update_key";
    $st = $pdo->prepare($sql);
    unframed_sql_execute($st, $values);
    return $st->rowCount();
}
