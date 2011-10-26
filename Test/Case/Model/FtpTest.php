<?php
/**
 * Ftp Test
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 */
App::uses('ConnectionManager', 'Model');
App::uses('Ftp', 'Ftp.Model');
App::uses('FtpSource', 'Ftp.Model/Datasource');
class FtpTest extends CakeTestCase {

/**
 * name
 */
	public $name = 'Ftp';
	
/**
 * Model
 * @var object
 */
	public $Model = null;

/**
 * Ds
 * @var object
 */
	public $Ds = null;

/**
 * dsName
 * @var string
 */
	public $dsName = 'a-non-existant-ds';

/**
 * setUp
 */
	function setUp() {
		if (!class_exists('MockFtpSource')) {
			//Mock::generatePartial('TestFtpSource', 'MockFtpSource', array('connect'));
		}
	}

/**
 * start
 */
	public function start() {
		$this->Ds =& ConnectionManager::create($this->dsName, array(
			'datasource' => 'ftp.ftp',
		));
		if ($this->Ds == null) {
			$this->Ds =& ConnectionManager::getDataSource($this->dsName);
		}
		$this->Model =& new $this->name(array(
			'alias' => $this->name,
		));
		$this->Model->useDbConfig = $this->dsName;
	}

/**
 * testConnect
 */
	public function testConnect() {
		
		// NO CONNECTION
		$this->assertFalse($this->Model->connect());

	}

/**
 * testFind
 */
	public function testFind() {
		
	}

/**
 * testSave
 */
	public function testSave() {
		
	}

/**
 * testDelete
 */
	public function testDelete() {
		
	}

/**
 * end
 */
	public function end() {
		Cache::clear(false, 'cakeftp');
	}

}