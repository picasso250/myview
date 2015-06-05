<?php

function echo_json($code, $msg = 'ok')
{
    header('Content-Type: application/json; charset=utf-8');
    if (is_int($code)) {
        $res = compact('code', 'msg');
    } else {
        $res = ['code' => 0, 'msg' => $msg, 'data' => $code];
    }
    echo json_encode($res);
}
function render($_inner_, $data = [])
{
    extract($data);
    include "view/layout.html";
}
function run($router, $args)
{
    header('Content-Type: text/html; charset=utf-8');
    $func = "\\action\\$router";
    if (!function_exists($func)) {
        $func = "\\action\\page404";
    }
    return call_user_func_array($func, $args);
}
function get_router()
{
    $REQUEST_URI = $_SERVER['REQUEST_URI'];
    $path = explode('?', $REQUEST_URI)[0];
    $args = [];
    if ($path === '/') {
        $router = 'index';
    } elseif (preg_match('#^/(\w+)$#', $path, $matches)) {
        $router = $matches[1];
    } elseif (preg_match('#^/(\w+)/(\w+)$#', $path, $matches)) {
        $router = $matches[1];
        $args[] = $matches[2];
    } else {
        $router = 'page404';
    }
    return [$router, $args];
}
function _get($name, $default = null)
{
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}
function _post($name, $default = null)
{
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}
function _req($name, $default = null)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}
function get_visit_file()
{
    return $f = 'runtime/visit-'.date('Ymd').'.json';
}
function get_visit_info()
{
    $f = get_visit_file();
    if (is_file($f)) {
        $info = unserialize(file_get_contents($f));
    } else {
        $info = array();
    }
    return $info;
}
function save_info($info) {
    $f = get_visit_file();
    file_put_contents($f, serialize($info));
}
function visit_count($vk)
{
    $info = get_visit_info();
    if (isset($info[$vk])) {
        $info[$vk]['cnt']++;
    } else {
        $info[$vk]['cnt'] = 1;
    }
    $info[$vk]['ua'] = $_SERVER['HTTP_USER_AGENT'];
    $info[$vk]['ip'] = ip2long($_SERVER['REMOTE_ADDR']);
    save_info($info);
}
function plant_vk()
{
    if (isset($_COOKIE['vk'])) { // visit key
        $vk = $_COOKIE['vk'];
    } else {
        $vk = 'k'.md5($_SERVER['HTTP_USER_AGENT'].uniqid());
        setcookie('vk', $vk, time()+3600*24*365*10, '/');
    }
    return $vk;
}
