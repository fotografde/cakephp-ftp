<?php
/**
 * Ftp Source Test
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
//App::uses('ConnectionManager', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('FtpSource', 'Ftp.Model/Datasource');
class FtpTestSource extends FtpSource {
}
class FtpSourceTest extends CakeTestCase {

/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
		$this->FtpSource = new FtpTestSource(array(
			'datasource' => 'Ftp.Ftp',
			'host' => 'example.com',
			'username' => 'testuser',
			'password' => '1234',
			'type' => 'ftp',
			'port' => 21,
		));
	}

/**
 * testConnect
 */
	public function testConnect() {

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
 * tearDown method
 * @return void
 */
	public function tearDown() {
		Cache::clear(false, 'cakeftp');
		unset($this->FtpSource);
		parent::tearDown();
	}

}