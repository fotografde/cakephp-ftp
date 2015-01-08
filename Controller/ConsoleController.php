<?php
App::uses('FtpAppController', 'Ftp.Controller');

/**
 * Console Controller
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
class ConsoleController extends FtpAppController {

	public $uses = array('Ftp.Ftp');

	public function index() {
	}

}