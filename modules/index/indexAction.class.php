<?php

/**
 * Index action
 *
 * @author Andrew Lee<tinray1024@gmail.com>
 * @since 15:08 08/07/2013
 */
defined('SYS_ROOT') || die('Access deined');

class indexAction extends publicAction {

	public function showIndex() {
    !action('user')->isLogged()? action('user')->showLogin(): action('user')->showCenter();
      //(!empty($_GET['referer'])? die(header('location:'. $_GET['referer'])): action('user')->showCenter());
	}

}
