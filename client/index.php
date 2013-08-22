<?php
header('Content-type: text/html; charset=utf-8');
require 'init.php';
session_start();

$sso_server_url = 'http://dev-server.sso.com/';

$current_url = urlencode(strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, 
  strpos($_SERVER['SERVER_PROTOCOL'], '/'))). '://' . $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI']);

if (!count($_SESSION)) {
  header("location:$sso_server_url?referer=$current_url");
} else {
  echo "欢迎您：".$_SESSION['account_info']['user_id']. " <a href='{$sso_server_url}user/logout?referer=$current_url'>注销</a>";
}

