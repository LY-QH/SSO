<?php
require __DIR__. '/init.php';
if (isset($_POST) && is_array($_POST)) {
  if (!empty($_POST['uid']) && !empty($_POST['token']) && !empty($_POST['time']) && !empty($_POST['action'])
    && in_array($_POST['action'], array('add', 'remove'))
  ){
    if ($_POST['time'] >= $_SERVER['REQUEST_TIME'] - $config['saas_server']['timeout']
      && $_POST['time'] <= $_SERVER['REQUEST_TIME'] + $config['saas_server']['timeout']) {
        if($_POST['token'] == md5($config['saas_server']['token'].$_POST['uid']. $_POST['time'])){
          $customer_dir = __DIR__.'/customers';
          $customer_file = $customer_dir. '/'. $_POST['uid'];
          if ($_POST['action'] == 'add') {
            if (!is_dir($customer_dir)) {
              mkdir($customer_dir) || die('Create customers dir failed');
            }
            if (!file_exists($customer_file)) {
              is_writeable($customer_dir) || die('Write file failed');
              file_put_contents($customer_file, '');
            }
          } else {
            if (file_exists($customer_file)) {
              unlink($customer_file) || die('Remove file failed');
            }
          }
          die('success');
        }
      }
  }
}
die('fail');
