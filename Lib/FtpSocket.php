<?php
App::uses('CakeSocket', 'Network');

/**
 * FtpSocket
 *
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2012 Kyle Robinson Young
 * 
 * TODO: This is a work in progress
 */
class FtpSocket extends CakeSocket {

/**
 * Object description
 *
 * @var string
 */
	public $description = 'Ftp Socket Interface';

/**
 * Base configuration settings for the socket connection
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent'		=> false,
		'host'				=> 'localhost',
		'protocol'			=> 'ftp',
		'port'				=> 21,
		'timeout'			=> 30,
		'username'			=> '',
		'password'			=> '',
		'eol'				=> "\r\n",
		'responsesLength'	=> 10,
		'path'				=> '/',
		'files'				=> array(),
	);

/**
 * Available FTP Commands
 *
 * @var array
 */
	protected $_commands = array(
		'ABOR', 'PWD', 'CDUP', 'FEAT', 'NOOP', 'QUIT', 'PASV', 'SYST',
		'CWD', 'DELE', 'LIST', 'MDTM', 'MKD', 'MODE', 'NLST', 'RETR', 'RMD',
		'RNFR', 'RNTO', 'SITE', 'STAT', 'STOR', 'TYPE', 'USER', 'PASS',
		'SYST', 'CHMOD', 'SIZE'
	);

/**
 * Hold last x responseLength responses
 *
 * @var array
 */
	protected $_responses = array();

/**
 * Connect
 *
 * @param string $host
 * @param integer $port
 * @return FtpSocket
 */
	public function connect($host = null, $port = null) {
		if (isset($host)) {
			$this->config['host'] = $host;
		}
		if (isset($port)) {
			$this->config['port'] = $port;
		}
		parent::connect();
		return $this;
	}

/**
 * Login
 *
 * @param string $username
 * @param string $password
 * @return FtpSocket
 */
	public function login($username = null, $password = null) {
		if (isset($user)) {
			$this->config['username'] = $username;
		}
		if (isset($password)) {
			$this->config['password'] = $password;
		}
		if (!$this->connected) {
			$this->connect();
		}
		$this->user($this->config['username']);
		$this->pass($this->config['password']);
		return $this;
	}

/**
 * Call raw FTP commands
 *
 * @param string $name
 * @param array $args
 * @return FtpSocket
 */
	public function __call($name, $args) {
		$name = strtoupper($name);
		if (!in_array($name, $this->_commands)) {
			throw new CakeException(__d('ftp', 'Unknown command: %s', $name));
		}
		$cmd = $name . ' ' . implode(' ', $args);
		if ($this->write($cmd . $this->config['eol'])) {
			$this->_response($this->read());
		}
		return $this;
	}

/**
 * Get properties
 *
 * @param string $name
 * @return mixed
 */
	public function __get($name) {
		switch (strtolower($name)) {
			case 'responses':
				$this->_response($this->read());
				return $this->_responses;
			break;
			default:
				if (in_array($name, $this->config)) {
					return $this->config[$name];
				}
			break;
		}
		return $this->{$name};
	}

/**
 * Manage responses buffer
 *
 * @param string $data
 * @return boolean
 */
	protected function _response($data = null) {
		array_unshift($this->_responses, trim($data));
		if (count($this->_responses) > $this->config['responsesLength']) {
			array_pop($this->_responses);
		}
		return true;
	}

}