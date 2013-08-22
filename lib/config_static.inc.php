<?php
define('DEV', 'develop');
define('DEBUG', 'debug');
define('PROD', 'production');

// run mod
define('MOD', DEV);

return array(
  'powerful_password' => 'u$5w"o/-_yTphJ@X@K2^',
  'database' => array(
    'separate' => TRUE,
    'default' => array(
      array(
        'host' => '172.16.1.201',
        'db' => 'sso',
        'user' => 'ocmweb',
        'pwd' => 'ocmweb',
        'charset' => 'utf8',
        'prefix' => ''
      )
    ),
    'masters' => array(
      array(
        'host' => '172.16.1.201',
        'db' => 'sso',
        'user' => 'ocmweb',
        'pwd' => 'ocmweb',
        'charset' => 'utf8',
        'prefix' => ''
      )
    ),
    'slaves' => array(
      array(
        'host' => '172.16.1.201',
        'db' => 'sso',
        'user' => 'ocmweb',
        'pwd' => 'ocmweb',
        'charset' => 'utf8',
        'prefix' => ''
      )
    )
  ),
  'redis' => array(
    'host' => '172.16.1.201',
    'port' => '6379',
    'timeout' => 2
  ),
  'sendmail' => array(
    'host' => 'smtp.126.com',
    'port' => 25,
    'auth' => TRUE,
    'debug' => 0,
    'charset' => 'utf-8',
    'username' => 'webtester',
    'password' => 'leelee',
    'from' => 'webtester@126.com'
  ),
  'ticket_server' => array(
    'host' => '172.16.1.201',
    'port' => '3843'
  ),
  'session_server' => array(
    'host' => '172.16.1.201',
    'port' => '1042',
    'timeout' => 10
  ),
  'client_hosts' => array(
    'dev-core.ocm.com' => array(
      'name' => 'OCM 平台',
      'token' => '~-m+_qw(b?9xHebR',
      'sso_path' => 'sso',
      'pic' => '/static/index/images/7a082c0dde36eac2205a088397aaf292.jpg'
    ),
    'dev-saas.channelrapid.com' => array(
      'name' => 'SAAS 平台',
      'token' => '$p+^d2f9s!3@$%)C2_K',
      'sso_path' => 'passport',
    ),
    'dev-pps.channelrapid.com' => array(
      'name' => 'ERP 平台',
      'token' => 'n(z rXZ!ofN.-Lov7_',
      'sso_path' => 'passport',
      'pic' => '/static/index/images/797ae640e8d609db5d205661ea1198f0.jpg'
    )
  ),
  'open_id_logins' => array(
    array(
      'name' => '淘宝卖家登录',
      'url' => 'http://api-ocm.channelrapid.com/taobao/Index/login',
      'pic' => '/static/index/images/20130821060404558_easyicon_net_48.png'
    )
  ),
  'logged_direct_url' => 'http://dev-core.ocm.com', 
);
