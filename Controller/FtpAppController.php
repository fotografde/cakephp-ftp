<?php
App::uses('AppController', 'Controller');

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
		// The latter is deprecated
		if (!Configure::read('Ftp.enabled') && !Configure::read('Cakeftp.enabled')) {
			throw new InternalErrorException(__d('cakeftp', 'CakePHP FTP client/console are disabled by default for security. To enable put Configure::write(\'Ftp.enabled\', true); in your Config/bootstrap.php or config file.'));
		}
	}
}
