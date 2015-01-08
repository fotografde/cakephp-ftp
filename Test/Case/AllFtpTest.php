<?php
/**
 * Group test - Ftp
 */
class AllFtpTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite method, defines tests for this suite.
	 *
	 * @return void
	 */
	public static function suite() {
		$Suite = new CakeTestSuite('All Ftp tests');
		$path = dirname(__FILE__);
		$Suite->addTestDirectory($path . DS . 'Controller');
		$Suite->addTestDirectory($path . DS . 'Model');
		$Suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');
		$Suite->addTestDirectory($path . DS . 'Model' . DS . 'Datasource');
		return $Suite;
	}
}
