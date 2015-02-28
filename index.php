<?php

include 'PHP-tiny/autoload.php';

$config = require 'config.php';
Service('config', $config);

if ($dbname = _get('dbname')) {
	setcookie('dbname', $dbname);
} else {
	$dbname = isset($_COOKIE['dbname']) ? $_COOKIE['dbname'] : $config['dbnames'][0];
}
$dsn = $config['dsn'].";dbname=$dbname";
Service('db', new DB($dsn, $config['username'], $config['password']));

define('LAYOUT', __DIR__.'/view/layout.html');

run([
	['%^/(index)?$%', function () use ($dbname) {
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
			$sql = "SELECT * FROM $table $order LIMIT 11";
			$table_data = Service('db')->queryAll($sql);
		}
		render(__DIR__.'/view/index.html', compact('tables', 'table_data', 'table', 'sql', 'pkey', 'dbname'), LAYOUT);
	}],
	['%^/edit$%', function ($params) {
		$table = _get('table');
		$id = _get('id');
		$pkey = get_pkey($table);
		$row = Service('db')->queryRow("SELECT * FROM $table WHERE $pkey = $id");
		$sets = [];
		$db = Service('db');
		foreach ($row as $key => $value) {
			if (isset($_POST[$key]) && $_POST[$key] != $value) {
				$row[$key] = $_POST[$key];
				$sets[] = "`$key`=".$db->quote($_POST[$key]);
			}
		}
		if ($sets) {
			$sets = implode(',', $sets);
			$confirm_sql = "UPDATE `$table` SET $sets WHERE `$pkey`=".$db->quote($id);
		}
		render(__DIR__.'/view/edit.html', compact('row', 'table', 'pkey', 'confirm_sql'), LAYOUT);
	}],
	['%^/insert$%', function ($params) {
		$table = _get('table');
		$id = _get('id');
		$pkey = get_pkey($table);
		if ($id) {
			$row = Service('db')->queryRow("SELECT * FROM $table WHERE $pkey = $id");
		}
		$desc = get_desc($table);
		foreach ($desc as $d) {
			$Field = $d['Field'];
			if (filter_input(INPUT_POST, $Field.'_is_null')) {
				$values[$Field] = null;
			} elseif (isset($_POST[$Field])) {
				$values[$Field] = $_POST[$Field];
			} elseif ($id) {
				$values[$Field] = $row[$Field];
			}
		}
		$keys = implode(',', array_map(function($key) {
			return "`$key`";
		}, array_keys($values)));
		$val = implode(',', array_map(function ($value) {
			return $value === null ? 'NULL' : Service('db')->quote($value);
		}, $values));
		$confirm_sql = "INSERT INTO `$table` ($keys) VALUES ($val)";
		render(__DIR__.'/view/insert.html', compact('values', 'table', 'pkey', 'confirm_sql'), LAYOUT);
	}],
	['%^/exec$%', function () {
		$sql = _post('sql');
		if ($sql) {
			$count = Service('db')->exec($sql);
		}
		render(__DIR__.'/view/exec.html', compact('sql', 'count'), LAYOUT);
	}]
]);

function get_desc($table)
{
	$desc = Service('db')->queryAll("desc $table");
	return $desc;
}
function get_pkey($table)
{
	$desc = get_desc($table);
	$pkeys = array_filter($desc, function ($e) {
		return $e['Key'] === 'PRI';
	});
	if ($pkeys) {
		return $pkeys[0]['Field'];
	}
	return false;
}
