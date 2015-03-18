<?php

ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

include 'PHP-tiny/autoload.php';
include 'actions.php';
include 'logic.php';

$config = require __DIR__.'/config.php';
Service('config', new ArrayObject($config));

$dbname = _req('dbname');
if (empty($dbname)) {
	$dbname = key($config['dbnames']);
}
Service('dbname', $dbname);
$conf = $config['dbnames'][$dbname];
Service('db', new DB($conf['dsn'], $conf['username'], $conf['password']));

define('LAYOUT', __DIR__.'/view/layout.html');

run([
	['%^/(index)?$%',     'index'],
	['%^/t/(?<table>\w+)$%', 'table'],
	['%^/edit$%',         'edit',     'is_not_read_only'],
	['%^/insert$%',       'insert',   'is_not_read_only'],
	['%^/exec$%',         'exec_sql', 'is_not_read_only']
]);
