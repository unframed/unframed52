<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

unframed_no_script(__FILE__);

/**
 * For the given PDO connection, delete $object in $table where $column
 * equals $key.
 *
 * @param PDO $pdo the database connection
 * @param string $table the name of the table to delete from
 * @param strong $column the name of the column
 * @param string $key the value of the key
 *
 * @return the number of row affected, -1 if the statement execution failed 
 *
 * @throws PDOException
 */
function unframed_sql_delete_key($pdo, $table, $column, $key) {
    $sql = (
    	"DELETE FROM ".unframed_sql_quote($table)
    	." WHERE ".unframed_sql_quote($column)." = ?"
    	);
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $key);
    unframed_sql_execute($st);
    return $st->rowCount();
}
