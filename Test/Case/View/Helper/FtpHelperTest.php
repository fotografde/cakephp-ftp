<?php

App::uses('FtpHelper', 'Ftp.View/Helper');
App::uses('View', 'View');
App::uses('CakeTestCase', 'TestSuite');

/**
 * FtpHelper test case
 */
class FtpHelperTest extends CakeTestCase {

	public $FtpHelper;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->FtpHelper = new FtpHelper(new View());
	}

	/**
	 * testListFiles method
	 *
	 * @return void
	 */
	public function testListFiles() {
		$result = $this->FtpHelper->listFiles();
		$this->assertNotEmpty($result);
	}

}
