<?php

include 'PHP-tiny/autoload.php';

$dsn = 'mysql:host=localhost;dbname=meta';
$username = 'root';
$password = 'root';
Service('db', new DB($dsn, $username, $password));

define('LAYOUT', 'view/layout.html');


run([
	['%^/$%', function () {
		$tables = Service('db')->queryColumn('show tables');
		$order = _get('order');
		$asc = _get('asc', 0);
		$map = ['DESC', 'ASC'];
		if ($order) {
			$order = "order by $order $map[$asc]";
		} else {
			$order = '';
		}
		if ($sql = _get('sql')) {
			preg_match('/from\s+(\w+)/i', $sql, $matches);
			$table = $matches[1];
			$table_data = Service('db')->queryAll($sql);
			$pkey = get_pkey($table);
		} elseif ($table = _get('table')) {
			$pkey = get_pkey($table);
			$sql = "SELECT*from $table $order limit 11";
			$table_data = Service('db')->queryAll($sql);
		}
		render('view/index.html', compact('tables', 'table_data', 'table', 'sql', 'pkey'), LAYOUT);
	}],
	['%^/edit$%', function ($params) {
		$table = _get('table');
		$id = _get('id');
		$pkey = get_pkey($table);
		$row = Service('db')->queryRow("SELECT * from $table where $pkey = $id");
		$sets = [];
		foreach ($row as $key => $value) {
			if (isset($_POST[$key]) && $_POST[$key] != $value) {
				$sets[] = "`$key`=".$db->quoteValue($value);
			}
		}
		if ($sets) {
			$comfirm_sql = "UPDATE `$table` SET $sets where `$pkey`=".$db->quoteValue($id);
		}
		render('view/edit.html', compact('row', 'table', 'pkey', 'comfirm_sql'), LAYOUT);
	}],
	['%/']
]);

function get_pkey($table)
{
	$desc = Service('db')->queryAll("desc $table");
	$pkeys = array_filter($desc, function ($e) {
		return $e['Key'] === 'PRI';
	});
	if ($pkeys) {
		return $pkeys[0]['Field'];
	}
	return false;
}
