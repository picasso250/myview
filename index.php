<?php

ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

include 'xiaochi-db/src/DB.php';
include 'lib.php';
include 'actions.php';
include 'logic.php';

date_default_timezone_set('PRC');

$config = require __DIR__.'/config.php';

$dbname = _req('dbname');
if (empty($dbname)) {
	$dbname = key($config['dbnames']);
}
$conf = $config['dbnames'][$dbname];
$db = new xiaochi\DB($conf['dsn'], $conf['username'], $conf['password']);

if (isset($_COOKIE['vk'])) { // visit key
    $vk = $_COOKIE['vk'];
} else {
    $vk = 'k'.md5($_SERVER['HTTP_USER_AGENT'].uniqid());
    setcookie('vk', $vk, time()+3600*24*365*10, '/');
}

define('LAYOUT', __DIR__.'/view/layout.html');

list($router, $args) = get_router();

run($router, $args);

$f = 'runtime/visit-'.date('Ymd').'.json';
$info = get_visit_info();
if (isset($info[$vk])) {
    $info[$vk]['cnt']++;
} else {
    $info[$vk]['cnt'] = 1;
}
$info[$vk]['ua'] = $_SERVER['HTTP_USER_AGENT'];
$info[$vk]['ip'] = ip2long($_SERVER['REMOTE_ADDR']);
file_put_contents($f, serialize($info));
