<?php

ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

include 'PHP-tiny/autoload.php';
include 'actions.php';
include 'logic.php';

date_default_timezone_set('PRC');

$config = require __DIR__.'/config.php';
Service('config', new ArrayObject($config));

$dbname = _req('dbname');
if (empty($dbname)) {
	$dbname = key($config['dbnames']);
}
Service('dbname', $dbname);
$conf = $config['dbnames'][$dbname];
Service('db', new DB($conf['dsn'], $conf['username'], $conf['password']));

if (isset($_COOKIE['vk'])) { // visit key
    $vk = $_COOKIE['vk'];
} else {
    $vk = 'k'.md5($_SERVER['HTTP_USER_AGENT'].uniqid());
    setcookie('vk', $vk, 0, '/');
}

define('LAYOUT', __DIR__.'/view/layout.html');

run([
	['%^/(index)?$%',     'index'],
	['%^/t/(?<table>\w+)$%', 'table'],
	['%^/edit$%',         'edit',     'is_not_read_only'],
	['%^/insert$%',       'insert',   'is_not_read_only'],
	['%^/exec$%',         'exec_sql', 'is_not_read_only']
]);

$f = 'runtime/visit-'.date('Ymd').'.json';
if (is_file($f)) {
    $info = file_get_contents($f);
} else {
    $info = array();
}
if (isset($info[$vk])) {
    $info[$vk]['cnt']++;
} else {
    $info[$vk]['ua'] = $_SERVER['HTTP_USER_AGENT'];
    $info[$vk]['cnt'] = 1;
}
