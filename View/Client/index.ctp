<?php if (empty($connected)): ?>
	
	<?php echo $this->Ftp->loginForm(); ?>
	
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
		'files' => $files,
		'parent' => isset($parent) ? $parent : '',
	));
	?>
	
<?php endif; ?>