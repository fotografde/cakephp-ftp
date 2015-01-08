<?php
App::uses('AppHelper', 'View/Helper');

/**
 * Ftp Helper
 *
 * @package cakeftp
 * @author Kyle Robinson Young <kyle at dontkry.com>
 * @copyright 2011 Kyle Robinson Young
 */
class FtpHelper extends AppHelper {

	public $helpers = array('Html', 'Form');

/**
 * listFiles
 * Prints list of files
 *
 * @param array $data
 * @return string
 */
	public function listFiles($data = null) {
		$data = array_merge(array(
			'files' => array(),
			'parent' => '',
		), (array)$data);
		$out = '';
		if (!empty($data['parent'])) {
			$parent = $this->Html->link(__d('cakeftp', 'Up Folder'), array(
				'action' => 'index', $data['parent'],
			));
		} else {
			$parent = '&nbsp;';
		}
		$out .= $this->Html->tableHeaders(array(
			$parent,
			__d('cakeftp', 'Last Modified'),
			__d('cakeftp', 'Size'),
			__d('cakeftp', 'Permissions'),
			'&nbsp;',
		));
		$cells = array();
		if (!empty($data['files'])) {
			foreach ($data['files'] as $file) {
				extract($file['Ftp']);
				if ($is_link == '1') {
					$safe = substr($filename, strpos($filename, '-> ') + 3);
				} else {
					$safe = $path . $filename;
				}
				$safe = urlencode(base64_encode($safe));
				if ($is_dir == '1' || $is_link) {
					$filename = $this->Html->link($filename, array(
						'action' => 'index', $safe,
					));
				}
				if ($is_dir != '1' && $is_link != '1') {
					$actions = $this->Html->link(__d('cakeftp', 'Download'), array(
						'action' => 'download', $safe,
					));
					$actions .= $this->Html->link(__d('cakeftp', 'Delete'), array(
						'action' => 'delete', $safe,
					), array(), __d('cakeftp', 'Are you sure you wish to delete that file?'));
				} else {
					$actions = '';
				}
				$cells[] = array(
					$filename,
					$mtime,
					$size,
					$chmod,
					array($actions, array('class' => 'actions', 'align' => 'right')),
				);
			}
		}
		$out .= $this->Html->tableCells($cells);
		$out = $this->Html->tag('table', $out, array(
			'cellpadding' => 0,
			'cellspacing' => 0,
			'width' => '100%',
			'border' => 0,
		));

		return $this->output($out);
	}

/**
 * uploadForm
 * Prints an upload form
 *
 * @param array $data
 * @return string
 */
	public function uploadForm($data = null) {
		$data = array_merge(array(
			'form' => array(
				'enctype' => 'multipart/form-data',
				'url' => array(
					'action' => 'upload',
				),
			),
			'path' => '.',
		), (array)$data);
		$out = '';
		$out .= $this->Form->create('File', $data['form']);
		$out .= $this->Form->hidden('path', array('value' => $data['path']));
		$out .= $this->Form->input('file', array(
			'type' => 'file',
			'label' => __d('cakeftp', 'Upload File'),
		));
		$out .= $this->Form->end(__d('cakeftp', 'Upload'));
		return $this->output($out);
	}

/**
 * loginForm
 * Prints a login form
 *
 * @param array $data
 * @return string
 * @deprecated Write your own, this will be removed soon.
 */
	public function loginForm($data = null) {
		$data = array_merge(array(
			'form' => array(
				'url' => array(
					'plugin' => 'ftp',
					'controller' => 'client',
					'action' => 'index',
				),
			),
			'defaultHost' => 'example.com',
			'defaultUsername' => 'user',
			'defaultPassword' => '',
			'defaultPath' => '.',
			'defaultType' => 'ftp',
		), (array)$data);
		$out = '';
		$out .= $this->Form->create('Ftp', $data['form']);
		$out .= $this->Form->input('host', array('default' => $data['defaultHost']));
		$out .= $this->Form->input('username', array('default' => $data['defaultUsername']));
		$out .= $this->Form->input('password', array('default' => $data['defaultPassword']));
		$out .= $this->Form->input('path', array('default' => $data['defaultPath']));
		$out .= $this->Form->input('type', array(
			'options' => array('ftp' => 'ftp', 'ssh' => 'ssh'),
			'default' => $data['defaultType'],
		));
		$out .= $this->Form->input('port');
		$out .= $this->Form->end(__d('cakeftp', 'Connect'));
		return $this->output($out);
	}
}
