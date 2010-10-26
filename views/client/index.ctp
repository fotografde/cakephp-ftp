<?php if (empty($connected)): ?>
	
	<?php
	echo $this->Form->create('Ftp', array('url' => array(
		'plugin' => 'ftp',
		'controller' => 'client',
		'action' => 'index',
	)));
	echo $this->Form->input('host', array('default' => 'example.com'));
	echo $this->Form->input('username', array('default' => 'user'));
	echo $this->Form->input('password', array());
	echo $this->Form->input('path', array('default' => '.'));
	echo $this->Form->input('type', array(
		'options' => array('ftp' => 'ftp', 'ssh' => 'ssh'),
	));
	echo $this->Form->end(__d('cakeftp', 'Connect', true));
	?>
	
<?php else: ?>
	
	<p style="text-align:right;"><?php echo $this->Html->link('Logout', array(
		'plugin' => 'ftp',
		'controller' => 'client',
		'action' => 'logout',
	)); ?></p>
	<h3><?php echo $path; ?></h3>
	
	<?php
	echo $this->Form->create('File', array(
		'enctype' => 'multipart/form-data',
		'url' => array(
			'plugin' => 'ftp',
			'controller' => 'client',
			'action' => 'upload',
		)
	));
	echo $this->Form->hidden('path', array('value' => $path));
	echo $this->Form->input('file', array(
		'type' => 'file',
		'label' => 'Upload File',
	));
	echo $this->Form->end(__d('cakeftp', 'Upload', true));
	?>
	
	<table cellpadding="0" cellspacing="0">
		<tr>
			<?php if (!empty($parent)): ?>
				<th><?php
				echo $this->Html->link(__d('cakeftp', 'Up Folder', true), array(
					'plugin' => 'ftp',
					'controller' => 'client',
					'action' => 'index', $parent
				));
				?></th>
			<?php else: ?>
				<th>&nbsp;</th>
			<?php endif; ?>
			<th>Last Modified</th>
			<th>Size</th>
			<th>Permissions</th>
			<th>&nbsp;</th>
		</tr>
		<?php if (!empty($files)): ?>
			<?php foreach ($files as $file): ?>
				<?php
				extract($file['Ftp']);
				if ($is_link == '1') {
					$safe = substr($filename, strpos($filename, '-> ')+3);
				} else {
					$safe = $path.$filename;
				}
				$safe = urlencode(base64_encode($safe));
				?>
				<tr>
					<td><?php
					if ($is_dir == '1' || $is_link) {
						echo $this->Html->link($filename, array(
							'plugin' => 'ftp',
							'controller' => 'client',
							'action' => 'index', $safe,
						));
					} else {
						echo $filename;
					}
					?></td>
					<td><?php echo $mtime; ?></td>
					<td><?php echo $size; ?></td>
					<td><?php echo $chmod; ?></td>
					<td class="actions" align="right"><?php
					if ($is_dir != '1' && $is_link != '1') {
						echo $this->Html->link(__d('cakeftp', 'Download', true), array(
							'plugin' => 'ftp',
							'controller' => 'client',
							'action' => 'download', $safe,
						));
						echo $this->Html->link(__d('cakeftp', 'Delete', true), array(
							'plugin' => 'ftp',
							'controller' => 'client',
							'action' => 'delete', $safe,
						), array(), __d('cakeftp', 'Are you sure you wish to delete that file?', true));
					}
					?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</table>
	
<?php endif; ?>