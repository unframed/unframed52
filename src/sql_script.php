<?php 

require_once(dirname(__FILE__).'/sql_transaction.php');

/**
 * Opens a database with PDO, prepare all statements begins a transaction, execute all premared
 * statements with their parameters, commit the transaction and return all statements' results
 * sets or rollback the transaction and throw an Unframed exception with the PDOException error
 * message.
 *
 * @param array $database { "dsn": "sqlite:...", "username": null, "password": null }
 * @param array $statements an array of statements and parameters, [["SELECT ...", [...]]]
 *
 * @return array of result sets as returned by `fetchAll(PDO::FETCH_NUM)`
 *
 * @throws Unframed exception with code 500 and the PDO driver's error message.
 */
function unframed_sql_script($database, $statements) {
	$transaction = FALSE;
	$response = array();
	try {
		$L = count($statements);
		$pdo = unframed_sql_open($database['dsn'], $database['username'], $database['password']);
		$prepared = array();
		for ($i=0; $i<$L; $i++) {
			array_push($prepared, $pdo->prepare($statements[$i][0]));
		}
		$transaction = $pdo->beginTransaction();
		if (!$transaction) {
			throw new Unframed($stmt->errorInfo()[2]);
		}
		for ($i=0; $i<$L; $i++) {
			$stmt = $prepared[$i];
			if($stmt->execute($statements[$i][1])) {
				array_push($response, $stmt->fetchAll(PDO::FETCH_NUM));
			} elseif (!$pdo->rollBack()) {
				throw new Unframed($stmt->errorInfo()[2]);
			} else {
				return $response;
			}
		}
		$pdo->commit();
	} catch (PDOException $e) {
		if ($transaction) {
			$pdo->rollBack();
		}
		throw new Unframed($e->getMessage());
	}
	return $response;
}
