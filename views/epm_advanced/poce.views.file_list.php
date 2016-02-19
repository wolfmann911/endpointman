<?php 
	echo "<h5>"._("Local File Configs")."</h5>";
	foreach ($file_list as $row) {
		echo '<a href="config.php?display=epm_advanced&subpage=poce&product_select='.$request['product_select'].'&file='.$row['value'].'"><code style="font-size: 0.8em">'.$row['text'].'</code></a><br />';
	}
	echo "<br /><hr><br />";
?>