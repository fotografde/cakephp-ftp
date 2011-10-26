<?php
/**
 * Ftp Source Test
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
App::uses('DataSource', 'Model/Datasource');
App::uses('FtpSource', 'Ftp.Model/Datasource');
class FtpSourceTest extends CakeTestCase {
	
/**
 * defaultConfig
 * @var array
 */
	public $defaultConfig = array(
		'datasource' => 'Ftp.Ftp',
		'host' => 'localhost',
		'username' => 'testuser',
		'password' => '1234',
		'type' => 'ftp',
		'port' => 21,
	);
	
/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
	}
	
/**
 * testInit
 */
	public function testInit() {
		$this->FtpSource = new FtpSource(array($this->defaultConfig));
		$data = array('type' => 'FTP', 'cache' => true);
		$this->assertTrue($this->FtpSource->init($data));
		$this->assertEqual($this->FtpSource->config['type'], 'ftp');
		$this->assertEqual($this->FtpSource->config['cache'], 'cakeftp');
	}

/**
 * testConnect
 */
	public function testConnect() {
		
		// FTP FAILED CONNECT
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', <<<END
			if (\$method == "ftp_connect") {
				return false;
			}
			return true;
END
		);
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		try {
			$this->assertFalse($this->FtpSource->connect());
		} catch (Exception $e) {
			$this->assertEqual($e->getMessage(), 'Failed to connect');
		}
		
		// FTP FAILED LOGIN
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', <<<END
			if (\$method == "ftp_login") {
				return false;
			}
			return true;
END
		);
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		try {
			$this->assertFalse($this->FtpSource->connect());
		} catch (Exception $e) {
			$this->assertEqual($e->getMessage(), 'Login failed');
		}
		
		// FTP SUCCESS
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', 'return true;');
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		$this->assertTrue($this->FtpSource->connect());
		
		// TODO: ADD SFTP
	}

/**
 * testRead
 */
	public function testRead() {
		
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