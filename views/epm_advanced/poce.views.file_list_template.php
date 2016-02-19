<?php
	echo "<h5>"._("Custom Template Files")."</h5>";
	foreach ($template_file_list as $row) {
		echo '<a href="config.php?display=epm_advanced&subpage=poce&product_select='.$request['product_select'].'&temp_file='.$row['value'].'"><code style="font-size: 0.8em">'.$row['text'].'</code></a><br />';
	}
	echo "<br /><hr><br />";
?>