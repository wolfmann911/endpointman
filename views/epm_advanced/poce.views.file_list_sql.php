<?php
	echo "<h5>"._("User File Configs")."</h5>";
	foreach ($sql_file_list as $row) {
		echo '<a href="config.php?display=epm_advanced&subpage=poce&product_select='.$request['product_select'].'&sql='.$row['value'].'"><code style="font-size: 0.8em">'.$row['text'].'</code></a>';
		echo '<a href="config.php?display=epm_advanced&subpage=poce&product_select='.$request['product_select'].'&sql='.$row['value'].'&delete=yes"><i class=\'icon-remove red\' alt=\''._('Delete').'\'></i></a>';
		echo '<br />';
		echo '<font style="font-size:0.8em"> [ref: <code>'.$row['ref'].'</code>]</font><br />';
	}
?>