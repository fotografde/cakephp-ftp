<?php
/**
 * FTP/SFTP Source
 * DataSource for listing/uploading/downloading/deleting 
 * files remotely through FTP or SFTP.
 * 
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at kyletyoung.com>
 * @copyright 2010 Kyle Robinson Young
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
		'passive' => true,
		'timeout' => 5,
		'ls_cmd' => 'ls -l -A',
		'connection' => null,
		'systype' => 'unknown',
		'cache' => false,
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
	public function init($config=array()) {
		if (!empty($config['type'])) {
			$config['type'] = strtolower($config['type']);
		}
		$this->config = array_merge($this->config, (array)$config);
		if ($this->config['cache'] === true) {
			Cache::config('cakeftp', array('engine'=> 'File', 'prefix' => 'cakeftp_'));
			$this->config['cache'] = 'cakeftp';
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
 * @param array $data
 * @return array
 */
	public function read(&$Model, $data=array()) {
		if (isset($data['fields']['count'])) {
			return array(array(array('count' => 1)));
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect', true));
			return false;
		}
		$out = array();
		if (isset($data['conditions']['path'])) {
			$path = $data['conditions']['path'];
		} else {
			if (!empty($Model->id)) {
				$path = $Model->id;
			} else {
				$path = '.';
			}
		}
		if ($path != '.') {
			$path = realpath($path);
		}
		$recursive = (!empty($data['recursive']) && $data['recursive']) ? true : false;
		$hash = hash('md4', $path);
		if (($out = Cache::read($hash, $this->config['cache'])) === false) {
			switch ($this->config['type']) {
				case 'ftp':
					if (!ftp_chdir($this->config['connection'], $path)) {
						throw new Exception(__d('cakeftp', 'Folder does not exist', true));
						return false;
					}
					$path = ftp_pwd($this->config['connection']);
					$raw = ftp_rawlist($this->config['connection'], "-A .", $recursive);
					$out = $this->_parsels($raw, $path);
					break;
				case 'ssh':
					$cmd = $this->config['ls_cmd'].' ';
					if ($recursive) {
						$cmd .= '-R ';
					}
					$chdir = (strlen($path) > 1) ? $this->config['connection']->chdir($path) : true;
					if (empty($chdir)) {
						throw new Exception(__d('cakeftp', 'Folder does not exist', true));
						return false;
					}
					$path = $this->config['connection']->pwd();
					$raw = $this->config['connection']->exec($cmd.$path);
					$out = $this->_parsels($raw, $path);
					break;
			}
			if ($this->config['cache'] !== false) {
				if (isset($Model->cache)) {
					Cache::set($Model->cache);
				}
				Cache::write($hash, array('path' => $path, 'files' => $out), $this->config['cache']);
			}
		} else {
			$path = $out['path'];
			$out = $out['files'];
		}
		$Model->id = $path;

		$return = array();
		foreach ($out as $key => $val) {
			$return[] = array($Model->alias => $val);
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
 */
	public function create(&$Model, $fields=array(), $values=array()) {
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect', true));
			return false;
		}
		$data = array_combine($fields, $values);
		if (empty($data['remote']) || empty($data['local'])) {
			return false;
		}
		$data['direction'] = (!empty($data['direction'])) ? strtolower($data['direction']) : 'up';
		$Model->id = dirname($data['remote']);
		if ($this->config['type'] == "ftp") {
			if (!ftp_chdir($this->config['connection'], $Model->id)) {
				throw new Exception(__d('cakeftp', 'Could not change directory', true));
				return false;
			}
			switch ($data['direction']) {
				case 'up':
				case 'upload':
					$res = ftp_put($this->config['connection'], $data['remote'], $data['local'], FTP_BINARY);
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to upload', true));
					return false;
				
				case 'down':
				case 'download':
					touch($data['local']);
					if (ftp_get($this->config['connection'], $data['local'], $data['remote'], FTP_BINARY)) {
						return true;
					}
					unlink($data['local']);
					throw new Exception(__d('cakeftp', 'Failed to download', true));
					return false;
			}
		} elseif ($this->config['type'] == "ssh") {
			$this->config['connection']->chdir($Model->id);
			switch ($data['direction']) {
				case 'up':
				case 'upload':
					$res = $this->config['connection']->put(basename($data['remote']), $data['local'], NET_SFTP_LOCAL_FILE);
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to upload', true));
					return false;
				
				case 'down':
				case 'download':
					$res = $this->config['connection']->get(basename($data['remote']), $data['local']);
					if ($res) {
						return true;
					}
					throw new Exception(__d('cakeftp', 'Failed to download', true));
					return false;
			}
		}
		return false;
	}

/**
 * delete
 * Deletes a remote file
 * 
 * @param obj $Model
 * @param str $file
 * @return bool
 */
	public function delete(&$Model, $file=null) {
		if (empty($file)) {
			$file = $Model->id;
			if (empty($file)) {
				return false;
			}
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect', true));
			return false;
		}
		if ($this->config['type'] == "ftp") {
			if (ftp_delete($this->config['connection'], $file)) {
				return true;
			}
		} elseif ($this->config['type'] == "ssh") {
			if ($this->config['connection']->delete($file)) {
				return true;
			}
		}
		throw new Exception(__d('cakeftp', 'Failed to delete', true));
		return false;
	}

/**
 * query
 * Provides an interface to datasource methods.
 * 
 * @param str $query
 * @param array $data
 * @return mixed
*/
	public function query($query=null, $data=null) {
		if (strtolower($query) == 'connect') {
			return $this->connect(current($data));
		}
		if (strtolower($query) == 'connection') {
			return $this->config;
		}
		if (strtolower($query) == 'console') {
			return $this->console(current($data));
		}
		throw new Exception(__d('cakeftp', 'That method is not supported.', true));
	}

/**
 * listSources
 */
	public function listSources() {
		return false;
	}

	/**
	 * describe
	 * Dynamically describes _schema
	 * @param obj $model
	 */
	public function describe(&$Model) {
		$name = Inflector::underscore(Inflector::pluralize($Model->name));
		$this->_schema[$name] = current($this->_schema);
		unset($this->_schema['ftp']);
		return $this->_schema[$name];
	}

	/**
	* calculate
	*
	* @param Object $Model
	* @param mixed $func
	* @param array $params
	* @return array
	* @access public
	*/
	public function calculate(&$Model, $func, $params=array()) {
		return array('count' => 1);
	}

	/**
	 * connect
	 * @param array $config
	 * @return boolean
	 */
	public function connect($config=array()) {
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
				$host_ip = gethostbyname($this->config['host']);
				$this->config['connection'] = ftp_connect($host_ip);
				if (!$this->config['connection']) {
					throw new Exception(__d('cakeftp', 'Failed to connect', true));
					return false;
				}
				ftp_set_option($this->config['connection'], FTP_TIMEOUT_SEC, $this->config['timeout']);
				$login = ftp_login($this->config['connection'], $this->config['username'], $this->config['password']);
				if (!$login) {
					throw new Exception(__d('cakeftp', 'Login failed', true));
					unset($this->config['connection']);
					return false;
				}
				ftp_pasv($this->config['connection'], $this->config['passive']);
				$this->config['systype'] = ftp_systype($this->config['connection']);
				return true;

			case 'ssh':
				if (strpos(get_include_path(), 'phpseclib') === false) {
					set_include_path(APP.'vendors'.DS.'phpseclib'.DS);
				}
				if (!App::import('Vendor', 'Net_SFTP', array('file' => 'phpseclib' . DS . 'Net' . DS . 'SFTP.php'))) {
					throw new Exception(__d('cakeftp', 'Please upload the contents of the phpseclib (http://phpseclib.sourceforge.net/) to the app/vendors/phpseclib/ folder', true));
					exit;
				}
				$this->config['connection'] = new Net_SFTP($this->config['host']);
				if (!$this->config['connection']->login($this->config['username'], $this->config['password'])) {
					throw new Exception(__d('cakeftp', 'Login failed', true));
					unset($this->config['connection']);
					return false;
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
 */
	public function console($cmd=null) {
		if (empty($cmd)) {
			throw new Exception(__d('cakeftp', 'Invalid command', true));
			return false;
		}
		if (!$this->connect()) {
			throw new Exception(__d('cakeftp', 'Failed to connect', true));
			return false;
		}
		switch ($this->config['type']) {
			case 'ftp':
				return ftp_exec($this->config['connection'], $cmd);
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
		if ($this->config['connection']) {
			if ($this->config['type'] == "ftp") {
				ftp_close($this->config['connection']);
			}
			$this->config['connection'] = null;
		}
		return true;
	}

	/**
	 * _parsels
	 * Parses results from ls command into array
	 * 
	 * @access protected
	 * @param mixed $ls
	 * @param string $path
	 * @return array
	 */
	protected function _parsels($ls=null, $path='') {
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
					$date = date('Y').'-'.$date." ".$time;
				} else {
					$date = $time."-".$date." 00:00";
				}
			} else {
				$regs = preg_split('@[\s]+@', $line);
				if (sizeof($regs) > 9) {
					$regs = array_splice($regs, 0, 8)+array(8 => implode(' ', $regs));
				}
				if (sizeof($regs) == 9) {
					$raw = $line;
					list($perm, $hrdlnks, $user, $group, $bytes, $month, $day, $time, $filename) = $regs;
					$date = $month.' '.$day.' '.$time;
				}
			}
			if (isset($raw)) {
				$out[] = array(
					'path'		=> dirname($path.$thisPath).DS.basename($path.$thisPath).DS,
					'filename'	=> $filename,
					'is_dir'	=> ($perm{0}=='d')?1:0,
					'is_link'	=> ($perm{0}=='l')?1:0,
					'size'		=> $this->_byteconvert($bytes),
					'chmod'		=> $this->_chmodnum($perm),
					'mtime'		=> date('Y-m-d H:i:s', strtotime($date)),
					'raw'		=> $raw,
				);
			}
		}
		return $out;
	}

	/**
	 * _byteconvert
	 * @access protected
	 * @author tmp at gmx dot de
	 * @param string $bytes
	 * @return string
	 */
	protected function _byteconvert($bytes) {
		if ($bytes == 0) {
			return 0;
		}
		$symbol = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$exp = floor( log($bytes) / log(1024) );
		return sprintf( '%.2f ' . $symbol[ $exp ], ($bytes / pow(1024, floor($exp))) );
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
}