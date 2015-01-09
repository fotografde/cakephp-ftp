<?php
/**
 * FTP/SFTP Source
 * DataSource for listing/uploading/downloading/deleting
 * files remotely through FTP or SFTP.
 *
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
//define('NET_SSH2_LOGGING', 2); define('NET_SFTP_LOGGING', 2);
class FtpSource extends DataSource {

/**
 * description
 * @var string
 */
	public $description = 'FTP/SFTP DataSource';

/**
 * config
 * @var array
 */
	public $config = array(
		'host' => '',
		'username' => '',
		'password' => '',
		'type' => 'ftp',
		'port' => '',
		'passive' => true,
		'timeout' => 5,
		'ls_cmd' => 'ls -l -A --time-style=long-iso',
		'connection' => null,
		'systype' => 'unknown',
		'cache' => false,
		'ds' => '/',
	);

/**
 * _schema
 * Default schema
 * @var array
 */
	protected $_schema = array(
		'ftp' => array(
			'id' => array(
				'type' => 'string',
				'null' => false,
				'key' => 'primary',
				'length' => 255,
			),
			'direction' => array(
				'type' => 'string',
				'null' => false,
				'default' => 'up',
				'length' => 4,
			),
			'local' => array(
				'type' => 'string',
				'null' => false,
				'length' => 255,
			),
			'remote' => array(
				'type' => 'string',
				'null' => false,
				'length' => 255,
			),
		)
	);

/**
 * __construct
 * @param array $config
 */
	public function __construct($config) {
		$this->init($config);
		parent::__construct($config);
	}

/**
 * init
 * Merges config
 *
 * @param array $config
 * @return boolean
 */
	public function init($config = array()) {
		if (!empty($config['type'])) {
			$config['type'] = strtolower($config['type']);
		}
		$this->config = array_merge($this->config, (array)$config);
		if ($this->config['cache'] === true) {
			Cache::config('cakeftp', array('engine' => 'File', 'prefix' => 'cakeftp_'));
			$this->config['cache'] = 'cakeftp';
		}
		if (!empty($this->config['host'])) {
			$this->config['host'] = preg_replace('/(.*):\/\//i', '', $this->config['host']);
		}
		return true;
	}

/**
 * __destruct
 * Quits
 */
	public function __destruct() {
		$this->quit();
	}

/**
 * read
 * Find files on remote server
 *
 * @param object $model
 * @param array $queryData
 * @return array
 * @throws Exception
 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
		if (isset($queryData['fields']['count'])) {
			return array(array(array('count' => 1)));
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect'));
		}
		$out = array();
		if (isset($queryData['conditions']['path'])) {
			$path = $queryData['conditions']['path'];
		} else {
			if (!empty($model->id)) {
				$path = $model->id;
			} else {
				$path = '.';
			}
		}
		$recursive = (!empty($queryData['recursive']) && $queryData['recursive']) ? true : false;
		$hash = hash('md4', $path);
		if (($out = Cache::read($hash, $this->config['cache'])) === false || $this->config['cache'] === false) {
			switch ($this->config['type']) {
				case 'ftp':
					if (!$this->_ftp('ftp_chdir', array($this->config['connection'], $path))) {
						throw new Exception(__d('cakeftp', 'Folder does not exist'));
					}
					$path = $this->_ftp('ftp_pwd', array($this->config['connection']));
					$raw = $this->_ftp('ftp_rawlist', array($this->config['connection'], "-A .", $recursive));
					if (method_exists($model, 'parseFtpResults')) {
						$out = $model->parseFtpResults($raw, $path, $this->config);
					} else {
						$out = $this->_parsels($raw, $path);
					}
					break;
				case 'ssh':
					$cmd = $this->config['ls_cmd'] . ' ';
					if ($recursive) {
						$cmd .= '-R ';
					}
					$chdir = (strlen($path) > 1) ? $this->config['connection']->chdir($path) : true;
					if (empty($chdir)) {
						throw new Exception(__d('cakeftp', 'Folder does not exist'));
					}
					$path = $this->config['connection']->pwd();
					$raw = $this->config['connection']->rawlist($path);
					if (method_exists($model, 'parseFtpResults')) {
						$out = $model->parseFtpResults($raw, $path, $this->config);
					} else {
						$out = $this->_parseSSH($raw, $path);
					}
					break;
			}
			if ($this->config['cache'] !== false) {
				if (isset($model->cache)) {
					Cache::set($model->cache);
				}
				Cache::write($hash, array('path' => $path, 'files' => $out), $this->config['cache']);
			}
		} else {
			$path = $out['path'];
			$out = $out['files'];
		}
		$model->id = $path;

		$return = array();
		if (is_array($out)) {
			foreach ($out as $key => $val) {
				$return[] = array($model->alias => $val);
			}
		}
		return $return;
	}

/**
 * create
 * Upload/Download
 *
 * @param object $model
 * @param array $fields
 * @param array $values
 * @return boolean
 * @throws Exception
 */
	public function create(Model $model, $fields = null, $values = null) {
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect'));
		}
		$data = array_combine($fields, $values);
		if (empty($data['remote']) || empty($data['local'])) {
			return false;
		}
		$data['direction'] = (!empty($data['direction'])) ? strtolower($data['direction']) : 'up';
		$model->id = dirname($data['remote']);
		if ($this->config['type'] == "ftp") {
			if (!$this->_ftp('ftp_chdir', array($this->config['connection'], $model->id))) {
				throw new Exception(__d('cakeftp', 'Could not change directory'));
			}
			switch ($data['direction']) {
				case 'up':
				case 'upload':
					$res = $this->_ftp('ftp_put', array($this->config['connection'], $data['remote'], $data['local'], FTP_BINARY));
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to upload'));

				case 'down':
				case 'download':
					$this->_ftp('touch', array($data['local']));
					if ($this->_ftp('ftp_get', array($this->config['connection'], $data['local'], $data['remote'], FTP_BINARY))) {
						return true;
					}
					$this->_ftp('unlink', array($data['local']));
					throw new Exception(__d('cakeftp', 'Failed to download'));
			}
		} elseif ($this->config['type'] == "ssh") {
			$this->config['connection']->chdir($model->id);
			switch ($data['direction']) {
				case 'up':
				case 'upload':
					$res = $this->config['connection']->put(basename($data['remote']), $data['local'], NET_SFTP_LOCAL_FILE);
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to upload'));

				case 'down':
				case 'download':
					$res = $this->config['connection']->get(basename($data['remote']), $data['local']);
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to download'));
			}
		}
		return false;
	}

/**
 * update
 * Upload/Download after find
 *
 * @param object $model
 * @param array $fields
 * @param array $values
 * @param array $conditions
 * @return boolean
 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		return $this->create($model, $fields, $values);
	}

/**
 * delete
 * Deletes a remote file
 *
 * @param obj $model
 * @param str $file
 * @return bool
 * @throws Exception
 */
	public function delete(Model $model, $file = null) {
		if (empty($file) || is_array($file)) {
			$file = $model->id;
			if (empty($file)) {
				return false;
			}
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect'));
		}
		if ($this->config['type'] == "ftp") {
			if ($this->_ftp('ftp_delete', array($this->config['connection'], $file))) {
				return true;
			}
		} elseif ($this->config['type'] == "ssh") {
			if ($this->config['connection']->delete($file)) {
				return true;
			}
		}
		throw new Exception(__d('cakeftp', 'Failed to delete'));
	}

/**
 * query
 * Provides an interface to datasource methods.
 *
 * @param string $query
 * @param array $data
 * @return mixed
 * @throws Exception
 */
	public function query($query = null, $data = null) {
		if (strtolower($query) == 'connect') {
			return $this->connect(current($data));
		}
		if (strtolower($query) == 'connection') {
			return $this->config;
		}
		if (strtolower($query) == 'console') {
			return $this->console(current($data));
		}
		throw new Exception(__d('cakeftp', 'That method is not supported.'));
	}

/**
 * listSources
 */
	public function listSources($data = null) {
		return false;
	}

/**
 * describe
 * Dynamically describes _schema
 * @param obj $model
 */
	public function describe($model) {
		$name = Inflector::underscore(Inflector::pluralize($model->name));
		$this->_schema[$name] = current($this->_schema);
		unset($this->_schema['ftp']);
		return $this->_schema[$name];
	}

/**
 * calculate
 *
 * @param Object $model
 * @param mixed $func
 * @param array $params
 * @return array
 * @access public
 */
	public function calculate($model, $func, $params = array()) {
		return array('count' => 1);
	}

/**
 * connect
 * @param array $config
 * @return boolean
 * @throws Exception
 */
	public function connect($config = array()) {
		if (!empty($config)) {
			$this->init($config);
		} else {
			if (empty($this->config['host'])) {
				return false;
			}
		}
		if (isset($this->config['connection'])) {
			return true;
		}
		switch ($this->config['type']) {
			case 'ftp':
				$port = !empty($this->config['port']) ? $this->config['port'] : 21;
				$this->config['connection'] = $this->_ftp('ftp_connect', array($this->config['host'], $port));
				if (!$this->config['connection']) {
					throw new Exception(__d('cakeftp', 'Failed to connect'));
				}
				$this->_ftp('ftp_set_option', array($this->config['connection'], FTP_TIMEOUT_SEC, $this->config['timeout']));
				$login = $this->_ftp('ftp_login', array($this->config['connection'], $this->config['username'], $this->config['password']));
				if (!$login) {
					unset($this->config['connection']);
					throw new Exception(__d('cakeftp', 'Login failed'));
				}
				$this->_ftp('ftp_pasv', array($this->config['connection'], $this->config['passive']));
				$this->_ftp('ftp_systype', array($this->config['connection']));
				return true;

			case 'ssh':
				if (strpos(get_include_path(), 'phpseclib') === false) {
					set_include_path(App::pluginPath('Ftp') . DS . 'Vendor' . DS . 'phpseclib' . DS . 'phpseclib' . DS . PATH_SEPARATOR . get_include_path());
					if (!App::import('Vendor', 'Ftp.Net_SFTP', array('file' => 'phpseclib' . DS . 'phpseclib' . DS . 'Net' . DS . 'SFTP.php'))) {
						throw new Exception(__d('cakeftp', 'Please upload the contents of the phpseclib (https://github.com/phpseclib/phpseclib) to the app/Plugin/Ftp/Vendor/phpseclib/ folder'));
					}
				}
				$port = !empty($this->config['port']) ? $this->config['port'] : 22;
				$this->config['connection'] = new Net_SFTP($this->config['host'], $port);
				if (!$this->config['connection']->login($this->config['username'], $this->config['password'])) {
					unset($this->config['connection']);
					throw new Exception(__d('cakeftp', 'Login failed'));
				}
				$this->config['systype'] = $this->console('uname');
				return true;
		}
		return false;
	}

/**
 * console
 * @param string $cmd
 * @return string
 * @throws Exception
 */
	public function console($cmd = null) {
		if (empty($cmd)) {
			throw new Exception(__d('cakeftp', 'Invalid command'));
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect'));
		}
		switch ($this->config['type']) {
			case 'ftp':
				return $this->_ftp('ftp_exec', array($this->config['connection'], $cmd));
			case 'ssh':
				return $this->config['connection']->exec($cmd);
		}
		return false;
	}

/**
 * quit
 * Closes and cleans up
 * @return boolean
 */
	public function quit() {
		if (isset($this->config['connection']) && $this->config['connection']) {
			if ($this->config['type'] == "ftp") {
				$this->_ftp('ftp_close', array($this->config['connection']));
			}
			$this->config['connection'] = null;
		}
		return true;
	}

/**
 * _parseSSH
 * Normalizes the results from SFTP->rawlist
 *
 * @access protected
 * @param mixed $rawList
 * @param string $path
 * @return array
 */
	protected function _parseSSH($rawlist = array(), $path = '') {
		$out = array();
		foreach ($rawlist as $file => $data) {
			$out[] = array(
				'path'		=> $path . '/',
				'filename'	=> $file,
				'is_dir'	=> ($data['type'] === NET_SFTP_TYPE_DIRECTORY),
				'is_link'	=> ($data['type'] === NET_SFTP_TYPE_SYMLINK),
				'size'		=> $data['size'],
				'chmod'		=> decoct($data['permissions']),
				'mtime'		=> date('Y-m-d H:i:s', strtotime($data['mtime'])),
				'raw'		=> $data,
			);
		}
		return $out;
	}

/**
 * _parsels
 * Parses results from ls command into array
 *
 * You can override this in your Model by adding the method
 * parseFtpResults($raw = array(), $path = null, $config = array()) : array()
 *
 * @access protected
 * @param mixed $ls
 * @param string $path
 * @return array
 */
	protected function _parsels($ls = null, $path = '') {
		if (empty($ls)) {
			return array();
		}
		if (!is_array($ls)) {
			$ls = explode("\n", $ls);
		}
		$out = array();
		$thisPath = '';
		foreach ($ls as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			if (substr($line, -1) == ':') {
				$thisPath = substr($line, strlen($path), -1);
				continue;
			}
			$raw = null;
			if (preg_match("@([-dl][rwxst-]+).* ([0-9]+).* ([a-zA-Z0-9]+).* ([a-zA-Z0-9]+).* ([0-9]*) ([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{9} -[0-9]{4}) (.+)@i", $line, $regs)) {
				list($raw, $perm, $hrdlnks, $user, $group, $bytes, $date, $filename) = $regs;
			} elseif (preg_match("@([-dl][rwxst-]+).* ([0-9]+).* ([a-zA-Z0-9]+).* ([a-zA-Z0-9]+).* ([0-9]*) ([a-zA-Z]+[0-9: ]*[0-9])[ ]+(([0-9]{2}:[0-9]{2})|[0-9]{4}) (.+)@i", $line, $regs)) {
				list($raw, $perm, $hrdlnks, $user, $group, $bytes, $date, $time, $time2, $filename) = $regs;
				$date = date("m-d", strtotime($date));
				if (strpos($time, ":") !== false) {
					$date = date('Y') . '-' . $date . " " . $time;
				} else {
					$date = $time . "-" . $date . " 00:00";
				}
			} else {
				$regs = preg_split('@[\s]+@', $line);
				if (count($regs) > 9) {
					$regs = array_splice($regs, 0, 8) + array(8 => implode(' ', $regs));
				}
				if (count($regs) === 9) {
					$raw = $line;
					list($perm, $hrdlnks, $user, $group, $bytes, $month, $day, $time, $filename) = $regs;
					$date = $month . ' ' . $day . ' ' . $time;
				}
				if (count($regs) === 8) {
					$raw = $line;
					list($perm, $hrdlnks, $user, $group, $bytes, $date, $time, $filename) = $regs;
					$date = $date . ' ' . $time;
				}
			}
			if (isset($raw)) {
				$_path = dirname($path . $thisPath) . DS . basename($path . $thisPath) . DS;
				if (DS !== $this->config['ds']) {
					$_path = str_replace(DS, $this->config['ds'], $_path);
				}
				$out[] = array(
					'path'		=> $_path,
					'filename'	=> $filename,
					'is_dir'	=> ($perm{0} == 'd') ? 1 : 0,
					'is_link'	=> ($perm{0} == 'l') ? 1 : 0,
					'size'		=> $bytes,
					'chmod'		=> $this->_chmodnum($perm),
					'mtime'		=> date('Y-m-d H:i:s', strtotime($date)),
					'raw'		=> $raw,
				);
			}
		}
		return $out;
	}

/**
 * _chmodnum
 * @access protected
 * @author tmp at gmx dot de
 * @param string $chmod
 * @return string
 */
	protected function _chmodnum($chmod) {
		$trans = array('-' => '0', 'r' => '4', 'w' => '2', 'x' => '1');
		$chmod = substr(strtr($chmod, $trans), 1);
		$array = str_split($chmod, 3);
		return array_sum(str_split(@$array[0])) . array_sum(str_split(@$array[1])) . array_sum(str_split(@$array[2]));
	}

/**
 * _ftp
 * Wrapper for FTP methods for testing
 * @param string $method
 * @param array $params
 */
	protected function _ftp($method = null, $params = array()) {
		return @call_user_func_array($method, $params);
	}
}
