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
