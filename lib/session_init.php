<?php

if (defined('SHAREDANCE')) return TRUE;

ini_set('session.save_handler', 'user');
define('SESSION_HANDLER_HOST', $GLOBALS['config']['session_server']['host']);
define('SHAREDANCE_DEFAULT_PORT', $GLOBALS['config']['session_server']['port']);
define('SHAREDANCE_DEFAULT_TIMEOUT', $GLOBALS['config']['session_server']['timeout']);

require __DIR__.'/sharedance/session_handler.php';

session_name("SESSID");
