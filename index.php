<?php

ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

include 'xiaochi-db/src/DB.php';
include 'lib.php';
include 'actions.php';
include 'logic.php';

date_default_timezone_set('PRC');

$app_begin_time = microtime(true);

$config = get_config();

$dbname = _req('dbname');
if (empty($dbname)) {
	$dbname = key($config['dbnames']);
}
$conf = $config['dbnames'][$dbname];
$db = new xiaochi\DB($conf['dsn'], $conf['username'], $conf['password']);

$vk = plant_vk();

define('LAYOUT', __DIR__.'/view/layout.html');

list($router, $args) = get_router();

run($router, $args);
