<?php

include 'PHP-tiny/autoload.php';

$dsn = 'mysql:host=localhost;dbname=meta';
$username = 'root';
$password = 'root';
Service('db', new DB($dsn, $username, $password));

run([
	['%^/$%', function () {
		$tables = Service('db')->queryColumn('show tables');
		render('view/index.html', compact('tables'));
	}],
	['%^/table/(?<table>\w+)$%', function ($params) {
		$table = $params['table'];
		$order = _get('order');
		$asc = _get('asc', 0);
		$map = ['DESC', 'ASC'];
		if ($order) {
			$order = "order by $order $map[$asc]";
		} else {
			$order = '';
		}
		$data = Service('db')->queryAll("SELECT*from $table $order limit 11");
		render('view/data.html', compact('data', 'table'));
	}]
]);
