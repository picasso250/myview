<?php

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
	if (empty($config)) {
		return $fkt;
	}
	foreach ($config as $real => $shadows) {
		$real = explode('.', $real);
		foreach ($shadows as $shadow) {
			list($st, $sk) = explode('.', $shadow);
			$fkt[$st][$sk] = $real;
		}
	}
	return $fkt;
}

function append_query($querys)
{
	$g = $_GET;
	return http_build_query(array_merge($g, $querys));
}

function is_read($sql)
{
	$sql = trim($sql);
	if (preg_match('/^(select|desc|explain)\b/i', $sql)) {
		return true;
	}
	return false;
}

function build_table_sql($table, $where = null)
{
	$order = _get('order');
	$asc = _get('asc', 0);
	$map = ['DESC', 'ASC'];
	if ($order) {
		$order = "ORDER BY `$order` $map[$asc]";
	} else {
		$order = '';
	}
	$where_str = '';
	$db = Service('db');
	if ($where) {
		$where = array_filter($where, function ($v) {
			return $v !== '';
		});
		$where_str = array();
		foreach ($where as $key => $value) {
			$where_str[] = "`$key`=".$db->quote($value);
		}
		$where_str = $where_str ? 'WHERE '.implode(' AND ', $where_str) : '';
	}
	$sql = "SELECT * FROM `$table` $where_str $order LIMIT 111";
	return $sql;
}
