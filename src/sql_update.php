<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

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
	$keys = array_keys($values);
	$updates = implode(', ', array_map(function($k) {
		return $k." = :".$k;
	}, $keys));
	$values['unframed_sql_update_key'] = $key;
	$sql = "UPDATE ".$table." SET ".$updates." WHERE ".$column." = :unframed_sql_update_key";
	$st = $pdo->prepare($sql);
	if ($st->execute($values)) {
		return $st->rowCount();
	}
	throw new Unframed($st->errorInfo()[2]);
}
