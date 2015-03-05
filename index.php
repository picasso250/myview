<?php

include 'PHP-tiny/autoload.php';

$config = require __DIR__.'/config.php';
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
			$order = "ORDER BY `$order` $map[$asc]";
		} else {
			$order = '';
		}
		if ($sql = _get('sql')) {
			if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
				$table = $matches[1];
				$pkey = get_pkey($table);
			}
		} elseif ($table = _get('table')) {
			$pkey = get_pkey($table);
			$sql = "SELECT * FROM `$table` $order LIMIT 11";
		}
		$err = null;
		if ($sql) {
			$db = Service('db');
	        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			$stmt = $db->prepare($sql);
			if (!$stmt->execute()) {
				$err = $stmt->errorInfo();
			}
			$table_data = $stmt->fetchAll(Pdo::FETCH_ASSOC);
		}
		$fkt = build_forein_key_table(Service('config')['foreignkeys']);
		render(__DIR__.'/view/index.html', compact('tables', 'table_data', 'table', 'sql', 'pkey', 'dbname', 'err', 'fkt'), LAYOUT);
	}],
	['%^/edit$%', function ($params) {
		$table = _get('table');
		$id = _get('id');
		$desc = get_desc($table, true);
		$pkey = get_pkey($table);
		$row = Service('db')->queryRow("SELECT * FROM $table WHERE $pkey = $id");
		$sets = [];
		$db = Service('db');
		foreach ($desc as $key => $d) {
			if (filter_input(INPUT_POST, $key.'_is_null')) {
				$row[$key] = null;
				$sets[] = "`$key`=NULL";
			} elseif (isset($_POST[$key]) && $_POST[$key] !== $row[$key]) {
				$row[$key] = $_POST[$key];
				$sets[] = "`$key`=".$db->quote($_POST[$key]);
			}
		}
		if ($sets) {
			$sets = implode(',', $sets);
			$confirm_sql = "UPDATE `$table` SET $sets WHERE `$pkey`=".$db->quote($id);
		}
		render(__DIR__.'/view/edit.html', compact('row', 'table', 'pkey', 'confirm_sql', 'desc'), LAYOUT);
	}, 'is_not_read_only'],
	['%^/insert$%', function ($params) {
		$table = _get('table');
		$id = _get('id');
		$pkey = get_pkey($table);
		if ($id) {
			$row = Service('db')->queryRow("SELECT * FROM $table WHERE $pkey = $id");
		}
		$desc = get_desc($table, true);
		foreach ($desc as $Field => $d) {
			if (filter_input(INPUT_POST, $Field.'_is_null')) {
				$values[$Field] = null;
			} elseif (isset($_POST[$Field])) {
				$values[$Field] = $_POST[$Field];
			} elseif ($id) {
				$values[$Field] = $row[$Field];
			} else {
				$values[$Field] = '';
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
	}, 'is_not_read_only'],
	['%^/exec$%', function () {
		$sql = _post('sql');
		if ($sql) {
			$count = Service('db')->exec($sql);
		}
		render(__DIR__.'/view/exec.html', compact('sql', 'count'), LAYOUT);
	}, 'is_not_read_only']
]);

function is_not_read_only()
{
	return !Service('config')['readonly'];
}
function get_desc($table, $key = false)
{
	$desc = Service('db')->queryAll("DESC `$table`");
	if ($key) {
		foreach ($desc as $d) {
			$ret[$d['Field']] = $d;
		}
		return $ret;
	}
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

function build_forein_key_table($config)
{
	foreach ($config as $real => $shadows) {
		$real = explode('.', $real);
		foreach ($shadows as $shadow) {
			list($st, $sk) = explode('.', $shadow);
			$fkt[$st][$sk] = $real;
		}
	}
	return $fkt;
}
