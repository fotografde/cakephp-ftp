<?php
/**
 * Ftp App Controller
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at kyletyoung.com>
 * @copyright 2010 Kyle Robinson Young
 */
class FtpAppController extends AppController {

/**
 * __construct
 * Must manually enable to use.
 * 
 */
	public function __construct() {
		parent::__construct();
		if (Configure::read('Cakeftp.enabled') !== true) {
			user_error(__d('cakeftp', 'CakeFTP client/console are disabled by default for security. To enable put Configure::write(\'Cakeftp.enabled\'); in your config/bootstrap.php or app_controller.php.', true));
			exit;
		}
	}
}