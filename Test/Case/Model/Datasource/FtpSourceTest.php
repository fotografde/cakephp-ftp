<?php
/**
 * Ftp Source Test
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
App::uses('ConnectionManager', 'Model');
App::uses('Model', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('FtpSource', 'Ftp.Model/Datasource');
class FtpTestModel extends Model {
	public $name = 'Ftp';
	public $useDbConfig = 'anonexistantsource';
	public $cache = false;
	public function __construct($id=null, $table=null, $ds=null) {
		ConnectionManager::create($this->useDbConfig, array(
			'datasource' => 'Ftp.FtpSource',
		));
		parent::__construct($id, $table, $ds);
	}
}
class FtpSourceTest extends CakeTestCase {
	
/**
 * defaultConfig
 * @var array
 */
	public $defaultConfig = array(
		'datasource' => 'Ftp.FtpSource',
		'host' => 'localhost',
		'username' => 'testuser',
		'password' => '1234',
		'type' => 'ftp',
		'port' => 21,
		'cache' => false,
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
		$Model = new FtpTestModel();
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', <<<END
			if (\$method == 'ftp_pwd') {
				return '/path/to/remote/folder/';
			}
			if (\$method == 'ftp_rawlist') {
				return array(
					"drwxr-x---   3 kyle  group      4096 Jul 12 12:16 public_ftp",
					"drwxr-x---  15 kyle  group      4096 Nov  3 21:31 public_html",
					"lrwxrwxrwx   1 kyle  group        11 Jul 12 12:16 www -> public_html",
				);
			}
			return true;
END
		);
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		
		// FTP GET LIST OF FILES
		try {
			$data = array(
				'conditions' => array('path' => '.'),
			);
			$result = $this->FtpSource->read($Model, $data);
			$this->assertEqual($result[0]['Ftp']['path'], '/path/to/remote/folder/');
			$this->assertEqual($result[0]['Ftp']['filename'], 'public_ftp');
			$this->assertEqual($result[0]['Ftp']['is_dir'], '1');
			
			$this->assertEqual($result[1]['Ftp']['is_link'], '0');
			$this->assertEqual($result[1]['Ftp']['size'], '4.00 KB');
			$this->assertEqual($result[1]['Ftp']['chmod'], '750');
			
			$this->assertEqual($result[2]['Ftp']['is_link'], '1');
			$this->assertEqual($result[2]['Ftp']['mtime'], '2011-07-12 12:16:00');
			$this->assertEqual($result[2]['Ftp']['raw'], 'lrwxrwxrwx   1 kyle  group        11 Jul 12 12:16 www -> public_html');
		} catch (Exception $e) {
			//debug($e->getMessage());
		}
		
		// TODO: ADD SFTP
	}

/**
 * testCreate
 */
	public function testCreate() {
		$Model = new FtpTestModel();
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', <<<END
			if (\$method == 'ftp_put') {
				return false;
			}
			if (\$method == 'ftp_get') {
				return false;
			}
			return true;
END
		);
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		
		// FTP FAILED TO UPLOAD
		try {
			$fields = array('remote', 'local');
			$values = array('/path/to/remote/folder/', '/path/to/local/file.zip');
			$this->assertFalse($this->FtpSource->create($Model, $fields, $values));
		} catch (Exception $e) {
			$this->assertEqual($e->getMessage(), 'Failed to upload');
		}
		
		// FTP FAILED TO DOWNLOAD
		try {
			$fields = array('remote', 'local', 'direction');
			$values = array('/path/to/remote/folder/', '/path/to/local/file.zip', 'down');
			$this->assertFalse($this->FtpSource->create($Model, $fields, $values));
		} catch (Exception $e) {
			$this->assertEqual($e->getMessage(), 'Failed to download');
		}
		
		// TODO: ADD SFTP
	}

/**
 * testDelete
 */
	public function testDelete() {
		$Model = new FtpTestModel();
		$this->FtpSource = $this->getMock('FtpSource', array('_ftp'), array($this->defaultConfig));
		$callback = create_function('$method,$params', <<<END
			if (\$method == 'ftp_delete') {
				return true;
			}
			if (\$method == 'ftp_connect' || \$method == 'ftp_login') {
				return true;
			}
			return false;
END
		);
		$this->FtpSource->expects($this->any())
			->method('_ftp')
			->will($this->returnCallback($callback));
		
		// FTP DELETE
		$this->assertTrue($this->FtpSource->delete($Model, '/path/to/remote/folder/file.zip'));
		
		// TODO: ADD SFTP
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