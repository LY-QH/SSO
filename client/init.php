<?php

if (defined('SHAREDANCE')) return TRUE;

$config = array(
  'domain' => 'dev-client.sso.com', 
  'session_server' => array(
    'host' => '172.16.1.201',
    'port' => '1042',
    'timeout' => 10
  ),
  'sso_server' => array(
    'timeout' => '300',
    'token' => '',
    'url' => ''
  ),
  'saas_server' => array(
    'timeout' => '300',
    'token' => ''
  )
);

ini_set('session.save_handler', 'user');
define('SESSION_HANDLER_HOST', $config['session_server']['host']);
define('SHAREDANCE_DEFAULT_PORT', $config['session_server']['port']);
define('SHAREDANCE_DEFAULT_TIMEOUT', $config['session_server']['timeout']);

require __DIR__.'/sharedance/session_handler.php';

session_name("SESSID");
