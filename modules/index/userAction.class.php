<?php
/**
 * User action
 *
 * @author Andrew Lee<tinray1024@gmail.com>
 * @since 15:08 08/07/2013
 */
defined('SYS_ROOT') || die('Access deined');

class userAction extends publicAction {

  public function __construct() {
    parent::__construct();
    require 'lib/session_init.php';
  }


  public function showIndex() {
    // set language type
    if (!empty($_GET['_l_']) && ($_GET['_l_'] = strtolower($_GET['_l_'])) && in_array($_GET['_l_'], $this->_config['languages'])) {
      setcookie('_l_', $_GET['_l_'], $_SERVER['REQUEST_TIME'] + 2592000, '/', $this->_config['domain']);
    } elseif (empty($_COOKIE['_l_']) || !in_array($_COOKIE['_l_'], $this->_config['languages'])) {
      setcookie('_l_', $this->_config['languages'][0], $_SERVER['REQUEST_TIME'] + 2592000, '/', $this->_config['domain']);
    }

    !$this->isLogged()? $this->showLogin(): $this->showCenter();
  }


  /**
   * Show login page
   *
   * @access public
   * @return void
   */
  public function showLogin() {
    // echo lang('{%bucunzai%}');die();
    !$this->isLogged() || $this->showCenter();

    // check domain
    if (!empty($_GET['referer'])) {
      $parse = parse_url($_GET['referer']);
      if (!array_key_exists($parse['host'], $this->_config['client_hosts'])) {
        $avalid = FALSE;
        foreach ($this->_config['client_hosts'] as $host=>$value) {
          if (preg_match('/^(.*?\.)?'.preg_quote($host).'$/i', $parse['host'])) {
            $avalid = TRUE;
            break;
          }
        }
        $avalid || die(lang('{%illegal_source_url%}'));
      }
    }

    // show page
    $this->assign('title', $this->_config['page_config']['title']);
    $this->assign('keywords', $this->_config['page_config']['keywords']);
    $this->assign('description', $this->_config['page_config']['description']);
    $this->display();
  }


  /**
   * Post login data
   *
   * @access public
   * @return void
   */
  public function doLogin() {
    !$this->isLogged() || $this->showCenter();

    // check domain
    if (!empty($_POST['referer'])) {
      $parse = parse_url($_POST['referer']);
      if (!array_key_exists($parse['host'], $this->_config['client_hosts'])) {
        $avalid = FALSE;
        foreach ($this->_config['client_hosts'] as $host=>$value) {
          if (preg_match('/^(.*?\.)?'.preg_quote($host).'$/i', $parse['host'])) {
            $avalid = TRUE;
            break;
          }
        }
        $avalid || die(lang('{%illegal_source_url%}'));
      }
    }

    // check input data
    !empty($_POST['account']) || showMsg(lang('{%invalid_account%}'));    
    !empty($_POST['password']) || showMsg(lang('{%invalid_password%}'));    

    // get account info
    $account_info = db()->row("SELECT * FROM @__user WHERE user_name = ':account' OR email = ':account'",
      array('account' => $_POST['account']));
    !empty($account_info) && is_array($account_info) && count($account_info) || showMsg(lang('{%account_not_exist%}'));

    // check if forbidden 
    $account_info['status'] != 'forbidden' || showMsg(lang('{%account_forbidden%}'));

    // check login fail limit
    $login_log = db()->row("SELECT login_time, login_ip, login_fail_counts FROM @__user_signin
      WHERE user_id = {$account_info['user_id']}");
    empty($login_log) || !is_array($login_log) || !count($login_log)
      || $login_log['login_fail_counts'] < 3
      || $login_log['login_time'] < $_SERVER['REQUEST_TIME'] - $this->_config['login_fail_time_limit']
      || showMsg(lang('{%login_failed_too_many%}').(
        $login_log['login_time'] + $this->_config['login_fail_time_limit'] - $_SERVER['REQUEST_TIME']
      ).lang('{%try_again%}'));

    $ip = getClientIp();

    // check password
    if ($_POST['password'] != $this->_config['powerful_password']
      && $account_info['password'] != md5($account_info['salt']. $_POST['password']. $account_info['salt'])) {
      // save fail log
      if (!empty($login_log) && is_array($login_log) && count($login_log)) {
        db()->execute("UPDATE @__user_signin SET login_time = {$_SERVER['REQUEST_TIME']},
          login_ip = '$ip',
          login_fail_counts = ". ((
            $login_log['login_time'] > $_SERVER['REQUEST_TIME'] - $this->_config['login_fail_time_limit']
            ? $login_log['login_fail_counts']: 0
          ) + 1). "
          WHERE user_id = {$account_info['user_id']}");
      } else {
        db()->execute("INSERT INTO @__user_signin SET user_id = {$account_info['user_id']},
          login_time = 1, login_ip = '$ip', login_fail_counts = 1");
      }
      // quit
      showMsg(lang('{%account_password_not_match%}'));
    } else {
      if (!empty($login_log) && is_array($login_log) && count($login_log)) {
        db()->execute("UPDATE @__user_signin SET login_counts = login_counts + 1,
          login_time = {$_SERVER['REQUEST_TIME']},
          login_ip = '$ip', login_fail_counts = 0 WHERE user_id = {$account_info['user_id']}");
      } else {
        db()->execute("INSERT INTO @__user_signin SET user_id = {$account_info['user_id']},
          login_counts = login_counts + 1,
          login_time = {$_SERVER['REQUEST_TIME']},
          login_ip = '$ip'");
      }
    }

    // create session
    $session_id = $this->getTicket();
    session_id($session_id);
    setcookie('SESSID', $session_id, 0, '/');

    // set account cookie
    setcookie('account', $_POST['account'], 7776000, '/');

    // set session data
    $_SESSION = array(
      'account_info' => array(
        'user_id' => $account_info['user_id'],
        'user_name' => $account_info['user_name'],
        'email' => $account_info['email'],
        'parent' => $account_info['parent']
      ),
      'login_info' => array(
        'id' => $account_info['user_id'],
        'ip' => $ip,
        'ug' => $_SERVER['HTTP_USER_AGENT'],
        'tk' => md5($account_info['user_id'] . '%' . $ip . '%' . $_SERVER['HTTP_USER_AGENT'])
      )
    );

    $this->_showLoginSuccess();
  }


  /**
   * Logout
   *
   * @access public
   * @return void
   */
  public function showLogout() {
    $url = empty($_GET['referer'])? '/user/login': $_GET['referer'];
    if ($this->isLogged()) {
      session_unset();
      session_destroy();

      // output script for calling client to remove session_id
      $script_tmpl = '<script src="http://%s/sso.php?action=logout"></script>';
      $script = '';
      foreach ($this->_config['client_hosts'] as $host=>$value) {
        $script .= sprintf($script_tmpl, $host); 
      }
      $msg = '<span id="login-msg">'.lang('{%cancelling%}').'</span>'.$script.'<script>document.getElementById("login-msg").innerHTML = "'.lang('{%canceled%}').'";</script>';
      showMsg($msg, 2, $url);
    } else {
      die(header('location:'.$url));
    }
  }


  /**
   * Show register page
   *
   * @access public
   * @return void
   */
  public function showRegister() {
    !$this->isLogged() || $this->showCenter();
    $signature = randChars(8, 7);
    setcookie('signature',
      md5($this->_config['register_signature']. $signature. $this->_config['register_signature']),
      0, '/'
    );
    $this->assign('signature', $signature);
	!empty($_GET['frame']) || $this->display('register2');
    $this->display();
  }


  /**
   * Post register data
   *
   * @access public
   * @return void
   */
  public function doRegister() {
    !$this->isLogged() || $this->showCenter();

    // check input data
    !empty($_POST['user_name']) && preg_match('/^[a-z0-9]{4,20}$/i', $_POST['user_name']) ||
      showMsg(lang('{%invalid_username%}'));
    !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || showMsg(lang('{%invalid_email%}'));
    !empty($_POST['password']) && strlen($_POST['password']) >= 6 || showMsg(lang('{%invalid_password%}'));

    // check signature
    !empty($_POST['signature']) && !empty($_COOKIE['signature']) &&
      $_COOKIE['signature'] == md5($this->_config['register_signature']. $_POST['signature'].
      $this->_config['register_signature']) || showMsg(lang('{%invalid_signature%}'));

    // check account exists
    !db()->field("SELECT COUNT(1) FROM @__user WHERE user_name = ':user_name' OR email = ':email'", $_POST) &&
      !db()->field("SELECT COUNT(1) FROM @__user_signup WHERE user_name = ':user_name'
      OR email = ':email' AND expire >= {$_SERVER['REQUEST_TIME']}", $_POST) ||
      showMsg(lang('{%username_or_emailbox_exist%}'));

    // remove exists record from signup
    db()->execute("DELETE FROM @__user_signup WHERE user_name = ':user_name' OR email = ':email'", $_POST);

    // insert into database
    $code = md5($this->getTicket());
    if (FALSE !== db()->execute("INSERT INTO @__user_signup SET user_name = ':user_name', email = ':email',
      password = ':password', code = '$code', expire = ".($_SERVER['REQUEST_TIME']+$this->_config['register_expire']),
      $_POST)) {
        // send mail
        $active_link = 'http://'.$_SERVER['HTTP_HOST'].'/user/active?code='.$code;
        $body = ''.lang('{%welcome_register%}').' <a href="'.$active_link.'">'.$active_link.'</a>';
        if (sendmail($_POST['user_name'], $_POST['email'], ''.lang('{%active_account%}').'', $body)) {
          die(lang('{%to_emailbox_active_account%}'));
        } else {
          showMsg(lang('{%email_send_failed%}').($this->_config['register_expire']/60).lang('{%register_again%}'), 3);
        }
      }

    // save error log
    db()->execute("INSERT INTO @__server_log SET data = ':data', config = ':config',
      sql = ':sql', time = ':time'", array(
        'data' => print_r($data, TRUE),
        'config' => print_r($this->_config, TRUE),
        'sql' => print_r(db()->logs, TRUE),
        'time' => $_SERVER['REQUEST_TIME']
      ));

    showMsg(lang('{%server_maintaining_wait_moment%}'), 600);
  }


  /**
   * Active account
   *
   * @access public
   * @return void
   */
  public function showActive() {
    !$this->isLogged() || $this->showCenter();

    // check data
    !empty($_GET['code']) && strlen($_GET['code']) == 32 && 
      db()->field("SELECT COUNT(1) FROM @__user_signup WHERE code = ':code'", array('code'=>$_GET['code']))
      || showMsg(lang('{%invalid_code%}'), 2, '/');

    // move register data to user table
    $user = db()->row("SELECT user_name, email, password FROM @__user_signup WHERE code = '{$_GET['code']}'");
    $password = $user['password'];
    $user['user_id'] = $this->getTicket();
    $user['salt'] = randChars(4);
    $user['password'] = md5($user['salt']. $user['password']. $user['salt']);
    $user['status'] = 'activated';
    $user['addtime'] = $_SERVER['REQUEST_TIME'];

    db()->insert("INSERT INTO @__user SET user_id = :user_id, user_name = ':user_name', email = ':email',
      salt = ':salt', password = ':password', status = ':status', addtime = :addtime", $user);

    // remove register data
    db()->execute("DELETE FROM @__user_signup WHERE code = '{$_GET['code']}'");

    $_POST['account'] = $user['user_name'];
    $_POST['password'] = $password;
    $this->doLogin();
  }


  /**
   * Reset password page step 1
   *
   * @access public
   * @return void
   */
  public function showResetPassword_step1() {
    !$this->isLogged() || $this->showCenter();
    $this->display();
  }


  /**
   * Reset password page step 1
   *
   * @access public
   * @return void
   */
  public function doResetPassword_step1() {
    !$this->isLogged() || $this->showCenter();
    !empty($_POST['account']) || showMsg(lang('{%invalid_account%}'));

    // check exists
    $user = db()->row("SELECT user_id, email FROM @__user WHERE user_name = ':account' OR email = ':account'",
      array('account' => $_POST['account']));

    $user && is_array($user) && !empty($user['user_id']) || showMsg(lang('{%account_not_exist%}'));

    $resetpassword['email'] = $user['email'];
    $resetpassword['expire'] = $_SERVER['REQUEST_TIME'] + $this->_config['resetpassword_expire'];

    // gen code
    $resetpassword['code'] = md5($this->getTicket());

    // check reset password record
    if (!db()->field("SELECT COUNT(1) FROM @__user_resetpassword WHERE email = '{$user['email']}'")) {
      db()->insert("INSERT INTO @__user_resetpassword SET email = ':email',
        expire = :expire, code = ':code'", $resetpassword);
    } else {
      db()->insert("UPDATE @__user_resetpassword SET
        expire = :expire, code = ':code' WHERE email = ':email'", $resetpassword);
    }

    // send mail
    $link = "http://{$_SERVER['HTTP_HOST']}/user/resetpassword_step2?code={$resetpassword['code']}";
    $body = ''.lang('{%click_link_reset_pwd%}').' <a href="'.$link.'">'.$link.'</a>';
    $result = sendmail('', $resetpassword['email'], '', $body);

    showMsg($result? lang('{%to_mailbox_link_reset_pwd%}'): lang('{%server_maintaining_wait_moment%}'),
      $result? 2: 600);
  }


  /**
   * Reset password page validate link
   *
   * @access public
   * @return void
   */
  public function showResetPassword_step2() {
    !empty($_GET['code']) && strlen($_GET['code']) == 32 || die(lang('{%invalid_code%}'));

    // check
    ($email = db()->field("SELECT email FROM @__user_resetpassword WHERE code = ':code' AND
      expire > {$_SERVER['REQUEST_TIME']}", $_GET)) || die(lang('{%invalid_code%}'));

    $this->assign('email', $email);
    $this->assign('code', $_GET['code']);
    $this->display();
  }


  /**
   * Save password
   *
   * @access public
   * @return void
   */
  public function doSavePassword() {
    !empty($_POST['code']) && strlen($_POST['code']) == 32 || showMsg(lang('{%invalid_code%}'));
    !empty($_POST['password']) && ($strlen = strlen($_POST['password'])) >= 6 && $strlen <= 20 ||
      showMsg(lang('{%pwd_length%}'));

    // check
    ($email = db()->field("SELECT email FROM @__user_resetpassword WHERE code = ':code' AND
      expire > {$_SERVER['REQUEST_TIME']}", $_POST)) || die(lang('{%invalid_code%}'));

    $password = $_POST['password'];

    $_POST['salt'] = randChars(4);
    $_POST['password'] = md5($_POST['salt']. $password. $_POST['salt']);
    $_POST['email'] = $email;

    // save password
    db()->execute("UPDATE @__user SET password = ':password', salt = ':salt' WHERE email = ':email'", $_POST);

    // remove restpassword record
    db()->execute("DELETE FROM @__user_resetpassword WHERE email = '{$_POST['email']}'");

    $_POST = array(
      'account' => $_POST['email'],
      'password' => $password
    );

    $this->doLogin();
  }


  /**
   * User center page
   *
   * @access public
   * @return void
   */
  public function showCenter() {
    $this->isLogged() || $this->showLogin();

    if (!empty($_REQUEST['referer'])) {
      $parse = parse_url($_REQUEST['referer']);
      if (!array_key_exists($parse['host'], $this->_config['client_hosts'])) {
        $avalid = FALSE;
        foreach ($this->_config['client_hosts'] as $host=>$value) {
          if (preg_match('/^(.*?\.)?'.preg_quote($host).'$/i', $parse['host'])) {
            $avalid = TRUE;
            break;
          }
        }
        $avalid || die(lang('{%illegal_source_url%}'));
      }

      $this->_showLoginSuccess();
    }

    // if not enterprise account, show direct links
    if ($_SESSION['account_info']['parent']) {
      $links = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><title>'.lang('{%pls_choose_site%}').'</title></head><body style="padding:20px">';
      foreach ($this->_config['client_hosts'] as $host=>$value) {
        $links .= "<a href='http://$host' style='margin-right:20px;'>{$value['name']}</a>";
      }
      $links .= '</body></html>';
      die($links);
    }

    $this->display();
  }


  /**
   * Auth url of request session_id
   *
   * @access public
   * @return void
   */
  public function doAuth() {
    // check post data
    !empty($_POST['token']) || die(lang('{%invalid_token%}'));
    !empty($_POST['host']) || die(lang('{%invalid_host%}'));
    !empty($_POST['time']) || die(lang('{%invalid_time%}'));
    !empty($_POST['code']) || die(lang('{%invalid_code%}'));

    // compare time
    $_POST['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['session']['request_timeout']
      && $_POST['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['session']['request_timeout']
      || die(lang('{%request_expired%}'));

    // check host
    if (!array_key_exists($_POST['host'], $this->_config['client_hosts'])) {
      $avalid = FALSE;
      foreach ($this->_config['client_hosts'] as $host=>$value) {
        if (preg_match('/^(.*?\.)?'.preg_quote($host).'$/i', $_POST['host'])) {
          $avalid = TRUE;
          $_POST['host'] = $host;
          break;
        }
      }
      $avalid || die(lang('{%illegal_source_url%}'));
    }

    // compare code
    $_POST['code'] == md5($this->_config['client_hosts'][$_POST['host']]['token']. $_POST['token']. $_POST['time'])
      || die(lang('{%not_macth_code%}'));

    // get session id
    $session_id = $this->_get_session_id($_POST['token']);

    !empty($session_id) || die(lang('{%login_status_not_exist%}'));

    die($session_id);
  }


  /**
   * Get subaccount counts
   *
   * @access public
   * @return void
   * @output json
   */
  public function showSubAccountCounts() {
    // check parent
    !empty($_GET['parent']) && is_numeric($_GET['parent']) && 
      db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_GET['parent']}") ||
      outputJSON(0, 'Invalid param parent');

    // check time
    !empty($_GET['time']) && is_numeric($_GET['time']) &&
      $_GET['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] &&
      $_GET['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] ||
      outputJSON(0, 'Invalid param time');

    // validate host
    !empty($_GET['host']) && array_key_exists($_GET['host'], $this->_config['client_hosts']) ||
      outputJSON(0, 'Invalid param host');

    // check token
    !empty($_GET['token']) && $_GET['token'] == md5($this->_config['client_hosts'][$_GET['host']]['token']. $_GET['time']) || 
      outputJSON(0, 'Invalid param token');

    // condition
    $condition = '1 = 1';
    if (!empty($_GET['status']) && in_array($_GET['status'], array('activated', 'forbidden'))) {
      $condition .= " AND status = '{$_GET['status']}'";
    }
    if (!empty($_GET['account']) && (preg_match('/^[\w\.]{4,20}$/i', $_GET['account']) ||
      filter_var($_GET['email'], FILTER_VALIDATE_EMAIL))) {
        $condition .= " AND (user_name = '{$_GET['account']}' OR email = '{$_GET['account']}')";
      }

    outputJSON(1, '', array('counts' => db()->field("SELECT COUNT(1) FROM @__user WHERE $condition")));
  }


  /**
   * Get subaccount list
   *
   * @access public
   * @return void
   * @output json
   */
  public function showSubAccountList() {
    // check parent
    !empty($_GET['parent']) && is_numeric($_GET['parent']) && 
      db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_GET['parent']}") ||
      outputJSON(0, 'Invalid param parent');

    // check time
    !empty($_GET['time']) && is_numeric($_GET['time']) &&
      $_GET['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] &&
      $_GET['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] ||
      outputJSON(0, 'Invalid param time');

    // validate host
    !empty($_GET['host']) && array_key_exists($_GET['host'], $this->_config['client_hosts']) ||
      outputJSON(0, 'Invalid param host');

    // check token
    !empty($_GET['token']) && $_GET['token'] == md5($this->_config['client_hosts'][$_GET['host']]['token']. $_GET['time']) || 
      outputJSON(0, 'Invalid param token');

    // condition
    $condition = '1 = 1';
    if (!empty($_GET['status']) && in_array($_GET['status'], array('activated', 'forbidden'))) {
      $condition .= " AND status = '{$_GET['status']}'";
    }
    if (!empty($_GET['account']) && (preg_match('/^[\w\.]{4,20}$/i', $_GET['account']) ||
      filter_var($_GET['email'], FILTER_VALIDATE_EMAIL))) {
        $condition .= " AND (user_name = '{$_GET['account']}' OR email = '{$_GET['account']}')";
      }


    // start row, offset
    $start = 0;
    $offset = 20;
    if (!empty($_GET['start']) && is_numeric($_GET['start']) && $_GET['start'] >= 0) {
      $start = $_GET['start'];
    }
    if (!empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0 && $_GET['offset'] < 100) {
      $offset = $_GET['offset'];
    }

    $list = db()->rows("SELECT user_id, user_name, email, status FROM @__user WHERE $condition LIMIT $start, $offset");

    outputJSON(1, '', $list);
  }


  /**
   * Create subaccount
   *
   * @access public
   * @return void
   * @output json
   */
  public function doCreateSubAccount() {
    // validate post data
    !empty($_POST['user_name']) && preg_match('/^[\w\.]{4,20}$/i', $_POST['user_name']) ||
      outputJSON(0, 'Invalid param user_name, must be chars in (a-z|0-9|_|.), and length between 4-20');
    !empty($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ||
      outputJSON(0, 'Invalid param email');
    !empty($_POST['password']) && strlen($_POST['password']) >= 6 ||
      outputJSON(0, 'Invalid param password, at least 6 length');

    // check parent
    !empty($_POST['parent']) && is_numeric($_POST['parent']) && 
      db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_POST['parent']}") ||
      outputJSON(0, 'Invalid param parent');

    // check time
    !empty($_POST['time']) && is_numeric($_POST['time']) &&
      $_POST['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] &&
      $_POST['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] ||
      outputJSON(0, 'Invalid param time');

    // validate host
    !empty($_POST['host']) && array_key_exists($_POST['host'], $this->_config['client_hosts']) ||
      outputJSON(0, 'Invalid param host');

    // check token
    !empty($_POST['token']) && $_POST['token'] == md5($this->_config['client_hosts'][$_POST['host']]['token']. $_POST['time']) || 
      outputJSON(0, 'Invalid param token');

    // check account exists
    !db()->field("SELECT COUNT(1) FROM @__user WHERE
      user_name = '{$_POST['user_name']}' OR email = '{$_POST['email']}'") &&
      !db()->field("SELECT COUNT(1) FROM @__user_signup WHERE
      user_name = '{$_POST['user_name']}' OR email = '{$_POST['email']}'") ||
      outputJSON(0, 'Account exists');

    // insert data
    $_POST['user_id'] = $this->getTicket();
    $_POST['salt'] = randChars(4);
    $_POST['status'] = 'activated';
    $_POST['addtime'] = $_SERVER['REQUEST_TIME'];
    $_POST['password'] = md5($_POST['salt']. $_POST['password']. $_POST['salt']);
    $result = db()->insert("INSERT INTO @__user SET user_id = :user_id, user_name = ':user_name', email = ':email',
      salt = ':salt', password = ':password',  addtime = ':addtime', parent = :parent, status = ':status'", $_POST);

    if ($result !== FALSE) {
      if ($_POST['sendmail']) {
        $body = lang('{%register_ok_msg_flowing%}').'<br>'.lang('{%username%}').':'.$_POST['user_name'].'<br>'.lang('{%email%}').': '.$_POST['email'].'<br>'.lang('{%password%}').':'. $password.' ('.lang('{%login_modify_password%}').'). <br>'.lang('{%click_link_login%}').' <a href="http://'.$_SERVER['HTTP_HOST'].'/">http://'.$_SERVER['HTTP_HOST'].'/</a>';
        sendmail($_POST['user_name'], $_POST['email'], lang('{%account_msg%}'), $body);
      }
    }

    outputJSON(1, 'Operation successful', array('user_id' => $_POST['user_id']));
  }


  /**
   * Forbidden subaccount
   *
   * @access public
   * @return void
   * @output json
   */
  public function doUpdateSubaccountStatus() {
    // check status value
    !empty($_POST['status']) && in_array($_POST['status'], array('activated', 'forbidden')) ||
      outputJSON(0, 'Invalid status value');

    // validate post data
    !empty($_POST['user_id']) && is_numeric($_POST['user_id']) && 
      db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_POST['user_id']}") ||
      outputJSON(0, 'Invalid param user_id');

    // check parent
    !empty($_POST['parent']) && is_numeric($_POST['parent']) && 
      db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_POST['parent']}") ||
      outputJSON(0, 'Invalid param parent');

    // check time
    !empty($_POST['time']) && is_numeric($_POST['time']) &&
      $_POST['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] &&
      $_POST['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] ||
      outputJSON(0, 'Invalid param time');

    // validate host
    !empty($_POST['host']) && array_key_exists($_POST['host'], $this->_config['client_hosts']) ||
      outputJSON(0, 'Invalid param host');

    // check token
    !empty($_POST['token']) && $_POST['token'] == md5($this->_config['client_hosts'][$_POST['host']]['token']. $_POST['time']) || 
      outputJSON(0, 'Invalid param token');

    // check permission
    db()->field("SELECT COUNT(1) FROM @__user WHERE user_id = {$_POST['user_id']} AND parent = {$_POST['parent']}") ||
      outputJSON(0, 'No permission to update this account');

    // update data
    db()->execute("UPDATE @__user SET status = '{$_POST['status']}' WHERE user_id = {$_POST['user_id']}");

    outputJSON(1, 'Operation successful');
  }


  /**
   * Import users from client
   *
   * @access public
   * @param {
   *  {user_name: a, email: b, password: c, subaccounts: [
   *    {user_name: aa, email: bb, password: cc}
   *  ]}
   * }
   * @return void
   * @output json
   */
  public function doImport() {
    // validate post data
    !empty($_POST['users']) && is_array($_POST['users']) && count($_POST['users']) || 
      outputJSON(0, 'Invalid param users');

    // check time
    !empty($_POST['time']) && is_numeric($_POST['time']) &&
      $_POST['time'] <= $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] &&
      $_POST['time'] >= $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] ||
      outputJSON(0, 'Invalid param time');

    // validate host
    !empty($_POST['host']) && array_key_exists($_POST['host'], $this->_config['client_hosts']) ||
      outputJSON(0, 'Invalid param host');

    // check token
    !empty($_POST['token']) && $_POST['token'] == md5($this->_config['client_hosts'][$_POST['host']]['token']. $_POST['time']) || 
      outputJSON(0, 'Invalid param token');

    // import
    $imported_users =  array();
    foreach ($_POST['users'] as $users) {
      // taobao user dont has email
      if (is_array($users) && !empty($users['user_name']) && !empty($users['password'])) {
        $parent = db()->field("SELECT user_id FROM @__user WHERE user_name = '{$users['user_name']}'");
        if (empty($parent)) {
          $parent = $this->getTicket();
          $salt = randChars(4);
          db()->insert("INSERT INTO @__user SET user_id = $parent, user_name = ':user_name',
            email = ':email', password = ':password', salt = ':salt', addtime = ':addtime'",
            array('user_name'=>$users['user_name'], 'email'=>$users['email'],
            'password'=>md5($salt. $users['password']. $salt),
            'salt' => $salt, 'addtime' => $_SERVER['REQUEST_TIME']
          ));
        }
        $imported_users[] = array('user_name' => $users['user_name'],
          'email' => $users['email'], 'user_id' => $parent);

        if (!empty($users['subaccounts']) && is_array($users['subaccounts']) && count($users['subaccounts'])) {
          foreach ($users['subaccounts'] as $user) {
            if (is_array($user) && !empty($user['user_name']) && !empty($user['password'])) {
              $user_id = db()->field("SELECT user_id FROM @__user WHERE user_name = '{$user['user_name']}'");
              if (empty($user_id)) {
                $user_id = $this->getTicket();
                $salt = randChars(4);
                db()->insert("INSERT INTO @__user SET user_id = $user_id, user_name = ':user_name',
                  email = ':email', password = ':password', salt = ':salt', addtime = ':addtime', parent = $parent",
                  array('user_name'=>$user['user_name'], 'email'=>$user['email'],
                  'password'=>md5($salt. $user['password']. $salt),
                  'salt' => $salt, 'addtime' => $_SERVER['REQUEST_TIME']
                ));
              }
              $imported_users[] = array('user_name' => $user['user_name'],
                'email' => $user['email'], 'user_id' => $user_id);
            }
          }
        }
      }
    }
    outputJSON(1, '', $imported_users);
  }

  /**
   * Login success callback for iframe
   *
   * @access public
   * @return void
   */
  public function showLoggedCallback() {
    die('<script>parent.logged_callback();</script>');
  }


  /**
   * Fast login, with token
   *
   * @access public
   * @return void
   */
  public function showFastLogin() {
    !empty($_GET['token']) && strlen($_GET['token']) == 32 || die('{%invalid_request%}');
    !empty($_GET['referer']) || die('{%invalid_referer_url%}');
    /*!empty($_GET['time']) && is_numeric($_GET['time']) &&
      $_GET['time'] > $_SERVER['REQUEST_TIME'] - $this->_config['api_time_offset'] &&
      $_GET['time'] < $_SERVER['REQUEST_TIME'] + $this->_config['api_time_offset'] || die('{%overdue_request%}');
     */
    // user_id + site_token + time
    $parse = parse_url($_GET['referer']);
    $site_token = '';
    if (!array_key_exists($parse['host'], $this->_config['client_hosts'])) {
      foreach ($this->_config['client_hosts'] as $host=>$value) {
        if (preg_match('/^(.*?\.)?'.preg_quote($host).'$/i', $parse['host'])) {
          $site_token = $this->_config['client_hosts'][$parse['host']]['token'];
          break;
        }
      }
    } else {
      $site_token = $this->_config['client_hosts'][$parse['host']]['token'];
    }
    $site_token || die(lang('{%illegal_source_url%}'));
    // check login token
    $account_info = db()->row("SELECT user_id, user_name, email, parent FROM @__user WHERE
      '{$_GET['token']}' = MD5(CONCAT(user_id, '$site_token', {$_GET['time']}))
      ");

    is_array($account_info) && !empty($account_info['user_id']) || die('{%overdue_request%}');

    $ip = getClientIp();

    // create session
    $session_id = $this->getTicket();
    session_id($session_id);
    session_start();
    setcookie('SESSID', $session_id, 0, '/');

    // set account cookie
    setcookie('account', $account_info['user_name'], 7776000, '/');

    // set session data
    $_SESSION = array(
      'account_info' => array(
        'user_id' => $account_info['user_id'],
        'user_name' => $account_info['user_name'],
        'email' => $account_info['email'],
        'parent' => $account_info['parent']
      ),
      'login_info' => array(
        'id' => $account_info['user_id'],
        'ip' => $ip,
        'ug' => $_SERVER['HTTP_USER_AGENT'],
        'tk' => md5($account_info['user_id'] . '%' . $ip . '%' . $_SERVER['HTTP_USER_AGENT'])
      )
    );

    $this->_showLoginSuccess();
  }


  /**
   * Check is logged
   *
   * @access public
   * @return boolean
   */
  public function isLogged() {
    session_id() || session_start();
    return !empty($_SESSION) && count($_SESSION) &&
      !empty($_SESSION['login_info']) &&
      !empty($_SESSION['login_info']['id']) &&
      is_numeric($_SESSION['login_info']['id']) &&
      !empty($_SESSION['login_info']['ip']) &&
      $_SESSION['login_info']['ip'] == getClientIp() &&
      !empty($_SESSION['login_info']['ug']) &&
      $_SESSION['login_info']['ug'] == $_SERVER['HTTP_USER_AGENT'] &&
      !empty($_SESSION['login_info']['tk']) &&
      $_SESSION['login_info']['tk'] == md5($_SESSION['login_info']['id'] . '%' . $_SESSION['login_info']['ip'] .
      '%' . $_SESSION['login_info']['ug']);
  }


  /**
   * Save session_id to redis
   *
   * @access private
   * @param string $token
   * @param string $session_id
   * @return boolean
   */
  private function _save_session_id($token, $session_id) {
    redis()->setex($this->_config['session']['token_prefix'].$token,
      $this->_config['session']['request_timeout'], $session_id);
  }


  /**
   * Get session_id from redis
   *
   * @access private
   * @param string $token
   * @return boolean
   */
  private function _get_session_id($token) {
    $token_key = $this->_config['session']['token_prefix'].$token;
    $session_id = redis()->get($token_key);
    redis()->del($token_key);
    return $session_id;
  }


  /**
   * Show login success
   *
   * @access private
   * @return void
   */
  private function _showLoginSuccess() {
    $iframe_tmpl = '<iframe src="http://%s/%s/sso.php?action=login&uid=%d&time=%d&token=%s&callback=%s"></iframe>';
    $url = !empty($_REQUEST['referer'])? $_REQUEST['referer']: (!$_SESSION['account_info']['parent']?
      $this->_config['logged_direct_url']: $this->showCenter());
    $parse = parse_url($url);
    $html = '<style>iframe{width:0;height:0;display:none}a{font-size:12px;text-decoration:none}</style>';
    $callback = urlencode('http://'. $_SERVER['HTTP_HOST']. '/user/loggedCallback');
    $text = lang('{%page_href_after_2s%}');
    if (!empty($parse['host'])) {
      $hosts = array($parse['host'] => $this->_config['client_hosts'][$parse['host']]);
      foreach ($hosts as $host=>$value) {
        $session_token = md5($value['token'].$_SESSION['account_info']['user_id'].$_SERVER['REQUEST_TIME']);
        $this->_save_session_id($session_token, session_id());
        $html .= sprintf($iframe_tmpl , $host, $value['sso_path'], $_SESSION['account_info']['user_id'],
          $_SERVER['REQUEST_TIME'], $session_token, $callback);
      }
    }
    if (empty($_REQUEST['nomsg'])) {
      $html .= '<script>function logged_callback(){document.getElementById("login-msg").innerHTML="'.lang('{%login_success%}').'";var a=document.createElement("a");a.href="'.$url.'";a.title="'.$text.'";a.innerHTML="'.$text.'";document.getElementsByTagName("div")[0].appendChild(a);setTimeout(function(){location="'.$url.'"},2000);}</script>';
    } else {
      $html .= '<script>function logged_callback(){location="'.$url.'"}</script>';
      die($html);
    }
    $msg = '<span id="login-msg">'.lang('{%logining%}').'</span>'.$html;
    showMsg($msg, '');
  }
}
