<?php if (empty($connected)): ?>

	<?php
	echo $this->Form->create('Ftp', array(
		'url' => array(
			'controller' => 'client',
			'action' => 'index',
		),
	));
	echo $this->Form->input('host', array('default' => 'example.com'));
	echo $this->Form->input('username', array('default' => 'user'));
	echo $this->Form->input('password');
	echo $this->Form->input('path');
	echo $this->Form->input('type', array(
		'options' => array('ftp' => 'ftp', 'ssh' => 'ssh'),
		'default' => 'ftp',
	));
	echo $this->Form->input('port');
	echo $this->Form->end(__d('cakeftp', 'Connect'));
	?>

<?php else: ?>

	<p style="text-align:right;"><?php echo $this->Html->link(__d('cakeftp', 'Logout', true), array(
		'plugin' => 'ftp',
		'controller' => 'client',
		'action' => 'logout',
	)); ?></p>

	<h3><?php echo $path; ?></h3>

	<?php
	echo $this->Ftp->uploadForm(array('path' => $path));
	echo $this->Ftp->listFiles(array(
		'files' => !empty($files) ? $files : array(),
		'parent' => isset($parent) ? $parent : '',
	));
	?>

<?php endif; ?>
