<?php
/**
 * Ftp App Controller
 *
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
class FtpAppController extends AppController {

/**
 * __construct
 * Must manually enable to use.
 */
	public function __construct($request = null, $response = null) {
		parent::__construct($request, $response);
		if (Configure::read('Cakeftp.enabled') !== true) {
			user_error(__d('cakeftp', 'CakeFTP client/console are disabled by default for security. To enable put Configure::write(\'Cakeftp.enabled\', true); in your config/bootstrap.php or app_controller.php.', true));
			exit;
		}
	}
}
