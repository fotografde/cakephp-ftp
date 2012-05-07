<?php
App::uses('ConnectionManager', 'Model');

/**
 * Ftp Model
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
class Ftp extends AppModel {

/**
 * name
 * @var string
 */
	public $name = 'Ftp';

/**
 * useDbConfig
 * @var string
 */
	public $useDbConfig = 'cakeftp';

/**
 * cache
 * Override the cache settings.
 * 
 * @var array
 */
	public $cache = false;

/**
 * __construct
 * Automatically connect to datasource if 
 * dynamic connections are wanted.
 * 
 * @param array $id
 * @param string $table
 * @param string $ds
 */
	public function __construct($id = null, $table = null, $ds = null) {
		if ($this->useDbConfig == 'cakeftp') {
			ConnectionManager::create($this->useDbConfig, array(
				'datasource' => 'Ftp.FtpSource',
			));
		}
		parent::__construct($id, $table, $ds);
	}

}