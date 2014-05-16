<?php

require_once(dirname(__FILE__).'/sql_transaction.php');

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
	$sql = "DELETE FROM ".$table." WHERE ".$column." = ?";
	$st = $pdo->prepare($sql);
	if ($st->bindParam(0, $key)) {
		if ($st->execute()) {
			return $st->rowCount();
		}
	}
	throw new Unframed($st->errorInfo()[2]);
}
