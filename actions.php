<?php
function index() {
	$tables = Service('db')->queryColumn('show tables');
	$sql = null;
	$table = _get('table');
	$where = _get('where', array());
	if ($table) {
		$sql = build_table_sql($table, $where);
		$pkey = get_pkey($table);
	}
	if (empty($sql)) {
		$sql = _get('sql');
		if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
			$table = $matches[1];
			$pkey = get_pkey($table);
		}
	}
	if (empty($sql) || is_read($sql)) {
		$err = null;
		try {
			$table_data = $sql ? Service('db')->queryAll($sql, $where) : [];
		} catch (PdoException $e) {
			$err = $e->errorInfo;
		}
		$fkt = (Service('config')['foreignkeys']);
		$dbname = Service('dbname');
		$data = compact('tables', 'table_data', 'table', 'sql', 'pkey', 'dbname', 'err', 'fkt', 'rowCount', 'where');
		render(__DIR__.'/view/index.html', $data, LAYOUT);
	} else {
		exec_sql();
	}
}

function edit($params) {
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
}
function insert($params) {
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
}

function exec_sql() {
	$sql = _req('sql');
	$errorInfo = null;
	if ($sql) {
		try {
			$count = Service('db')->exec($sql);
		} catch (PdoException $e) {
			$errorInfo = $e->errorInfo;
		}
	}
	render(__DIR__.'/view/exec.html', compact('sql', 'count', 'errorInfo'), LAYOUT);
}
