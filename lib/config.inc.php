<?php 
/**
 * Configure
 *
 * @author Andrew Lee<tinray1024@gmail.com>
 * @since 15:08 08/07/2013
 */
defined('SYS_ROOT') || die('Access denied');

return array_merge(
  array(
    'page_config' => array(
      'title' => 'SSO',
      'description' => 'SSO',
      'keyword' => 'SSO'
    ),
    'modules' => array(
      'index' => array(
        'type' => 0, // 0-normal, 1-rest
        'username' => '',
        'passwd' => ''
      )
    ),
    'languages' => array(
      'zh-cn', 'en-us', 'zh-tr'
    ),
    'login_fail_time_limit' => 600,
    'session' => array(
      'token_prefix' => 'TK_',
      'request_timeout' => 300,
    ),
    'api_time_offset' => 300,
    'register_signature' => '_Cs#mJNQb7%[ 1*"-B',
    'register_expire' => 1800,
    'resetpassword_signature' => '7m]sc<n+_%!G-Hw<',
    'resetpassword_expire' => 1800,
  ), 

  require __DIR__.'/config_static.inc.php'
);
