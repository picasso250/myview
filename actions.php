<?php
function index() {
	$tables = Service('db')->queryColumn('show tables');
	if ($sql = _get('sql')) {
		if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
			$table = $matches[1];
			$pkey = get_pkey($table);
		}
	} else {
		$sql = '';
	}
	try {
	$err = null;
	$rowCount = 0;
	if ($sql) {
		$db = Service('db');
		$stmt = $db->prepare($sql);
		if (!$stmt->execute()) {
			$err = $stmt->errorInfo();
		}
		if (is_read($sql)) {
			$table_data = $stmt->fetchAll(Pdo::FETCH_ASSOC);
		} else {
			$rowCount = $stmt->rowCount();
		}
	}
	} catch (PDOException $e) {
		$err = $e->errorInfo;
	}
	$fkt = build_forein_key_table(Service('config')['foreignkeys']);
	$data = compact('tables', 'table_data', 'table', 'sql', 'pkey', 'dbname', 'err', 'fkt', 'rowCount');
	render(__DIR__.'/view/index.html', $data, LAYOUT);
}
function table() {
	$tables = Service('db')->queryColumn('show tables');
	$order = _get('order');
	$asc = _get('asc', 0);
	$map = ['DESC', 'ASC'];
	if ($order) {
		$order = "ORDER BY `$order` $map[$asc]";
	} else {
		$order = '';
	}
	$pkey = get_pkey($table);
	$sql = "SELECT * FROM `$table` $order LIMIT 111";
	$db = Service('db');
	$table_data = $db->queryAll($sql);
	$fkt = build_forein_key_table(Service('config')['foreignkeys']);
	$data = compact('tables', 'table_data', 'table', 'sql', 'pkey', 'dbname', 'err', 'fkt', 'rowCount');
	render(__DIR__.'/view/index.html', $data, LAYOUT);
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
	$sql = _post('sql');
	if ($sql) {
		$count = Service('db')->exec($sql);
	}
	render(__DIR__.'/view/exec.html', compact('sql', 'count'), LAYOUT);
}
