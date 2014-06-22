<?php

require_once(dirname(__FILE__).'/sql_json.php');

unframed_no_script(__FILE__);

/**
 * Create a MySQL table from a name and a JSON model.
 */
function unframed_mysql_json_table ($prefix, $name, $model) {
	if (strlen($prefix.$name) > 64) {
		throw new Unframed("MySQL Error - table name too long: ".$prefix.$name);
	}
	if (strlen($name."_json") > 64) {
		throw new Unframed("MySQL Error - column name too long: ".$name."_json");
	}
    $columns = array($name."_json LONGTEXT");
    if (!array_key_exists($name, $model)) { // 8 bytes integers, assumes 64bits 
        array_push($columns, $name." BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
    }
    foreach($model as $key => $value) {
    	if (strlen($key) > 64) {
    		throw new Unframed('MySQL Error - column name too long: '.$key);
    	}
        if (is_scalar($value)) {
            array_push($columns, $key." ".unframed_sql_json_type(
            	$value, "VARCHAR(256)", "LONGTEXT"
            	));
        }
    }
    return (
        "CREATE TABLE ".$prefix.$name." (\n    "
            .implode(",\n    ", $columns)
            .",\n    PRIMARY KEY (".$name
        	.")\n    ) ENGINE=MyISAM /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/"
        );
}

/**
 * For an opened PDO connection to a MySQL database, declare a schema from the JSON $models
 * or fail.
 *
 * @param PDO $pdo of an opened MySQL database connection
 * @param array $models to eventually map to an SQL schema
 * @param string $path to prefix the database name, defaults to './'
 *
 * @return PDO connection
 * @throws Unframed or PDOException
 */
function unframed_mysql_json ($pdo, $prefix, $models) {
    $exist = unframed_sql_select_like($pdo, 'INFORMATION_SCHEMA.TABLES', 'TABLE_NAME', $prefix.'%');
    unframed_sql_json($pdo, $prefix, $models, 'unframed_mysql_json_table', $exist);
    return $pdo;
}
