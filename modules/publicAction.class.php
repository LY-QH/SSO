<?php
/**
 * public action
 *
 * @author Andrew Lee<tinray1024@gmail.com>
 * @since 15:08 08/07/2013
 */

defined('SYS_ROOT') || die('Access deined');

class publicAction extends action {
  public function publicAction() {
    parent::__construct();
  }

  /**
   * Get ticket
   * 
   * @return integer
   */
  protected function getTicket() {
    $url = "http://{$this->_config['ticket_server']['host']}:{$this->_config['ticket_server']['port']}";
    $res = '';
    if (function_exists('curl_init')) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $res = curl_exec($ch);
      curl_close($ch);
    } else
      $res = file_get_contents($url);
    if (!$res)
      $res = getTicket ();
    return $res;
  }

}
