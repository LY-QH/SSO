<?php

require __DIR__ .'/init.php';

if (isset($_GET) && is_array($_GET) && !empty($_GET['action'])) {
  if ($_GET['action'] == 'login' && !empty($_GET['uid']) && !empty($_GET['token']) && !empty($_GET['time'])){
    if ($_GET['time'] >= $_SERVER['REQUEST_TIME'] - $config['sso_server']['timeout']
      && $_GET['time'] <= $_SERVER['REQUEST_TIME'] + $config['sso_server']['timeout']) {
        if($_GET['token'] == md5($config['sso_server']['token'].$_GET['uid']. $_GET['time'])){
          $ch = curl_init();
          $code = md5($config['sso_server']['token']. $_GET['token']. $_SERVER['REQUEST_TIME']);
          $post_data = array (
            'host' => $config['domain'],
            'token' => $_GET['token'],
            'time' => $_SERVER['REQUEST_TIME'],
            'code'	=> $code
          );
          curl_setopt($ch, CURLOPT_URL, $config['sso_server']['url'].'/user/auth');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
          $session_id = curl_exec($ch);
          curl_close($ch);

          if (is_numeric($session_id)) {
            session_id($session_id);
            session_start();
          }
        }
      }
  } elseif ($_GET['action'] == 'logout') {
    session_start();
    session_unset();
    session_destroy();
  }
}

header('location:'. $_GET['callback']);
