<?php
/**
 * Ftp Test
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
App::uses('ConnectionManager', 'Model');
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('Ftp', 'Ftp.Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('FtpSource', 'Ftp.Model/Datasource');
class FtpTestSource extends FtpSource {
/**
 * _ftp
 * Override ftp for testing
 * @param string $method
 * @param array $params
 * @return mixed 
 */
	protected function _ftp($method = null, $params = array()) {
		switch ($method) {
			case 'ftp_pwd':
				return '/some/test/path/';
			break;
			case 'ftp_rawlist':
				return array(
					"drwxr-x---   3 kyle  group      4096 Jul 12 12:16 public_ftp",
					"drwxr-x---  15 kyle  group      4096 Nov  3 21:31 public_html",
					"lrwxrwxrwx   1 kyle  group        11 Jul 12 12:16 www -> public_html",
				);
			break;
		}
		return true;
	}
}
class FtpTestModel extends Ftp {
/**
 * name
 * @var string
 */
	public $name = 'FtpTestModel';
	
/**
 * __construct
 * @param mixed $id
 * @param string $table
 * @param string $ds
 */
	public function __construct($id=null, $table=null, $ds=null) {
		ConnectionManager::create($this->useDbConfig, array(
			'datasource' => 'Ftp.FtpTestSource',
			'host' => 'localhost',
			'username' => 'user',
			'password' => '1234',
			'type' => 'ftp',
			'cache' => false,
		));
		parent::__construct($id, $table, $ds);
	}

/**
 * parseFtpResults
 * Override for custom parsing of FTP results
 * @param array $raw
 * @param string $path
 * @param array $config 
 * @return array
 */
	public function parseFtpResults($raw = array(), $path = null, $config = array()) {
		$out = array();
		foreach ($raw as $val) {
			$filename = trim(strrchr($val, ' '));
			$out[] = array(
				'path'		=> '/testpath',
				'filename'	=> $filename,
				'is_dir'	=> 0,
				'is_link'	=> 0,
				'size'		=> 0,
				'chmod'		=> '0644',
				'mtime'		=> 0,
				'raw'		=> $val,
			);
		}
		/**
		 * Could even parse based on config type or systype
		 * if ($config['type'] == 'ssh') { ... }
		 * if ($config['systype'] == 'UNIX') { ... }
		 */
		return $out;
	}
}
class FtpTest extends CakeTestCase {
/**
 * setUp
 */
	public function setUp() {
		parent::setUp();
		$this->Ftp = new FtpTestModel();
	}
	
/**
 * testParseFtpResults
 */
	public function testParseFtpResults() {
		$result = $this->Ftp->find('all');
		$this->assertEqual($result[0]['FtpTestModel']['path'], '/testpath');
		$this->assertEqual($result[0]['FtpTestModel']['filename'], 'public_ftp');
		$this->assertEqual($result[1]['FtpTestModel']['filename'], 'public_html');
	}

/**
 * tearDown method
 * @return void
 */
	public function tearDown() {
		Cache::clear(false, 'cakeftp');
		unset($this->Ftp);
		parent::tearDown();
	}
}