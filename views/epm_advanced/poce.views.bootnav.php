<?php 
	foreach ($product_list as $srow) 
	{
		if ((isset($request['product_select'])) AND ($request['product_select'] == $srow['id'])) {
			$srow['tselected'] = 1;
		}
		echo '<a href="config.php?display=epm_advanced&amp;subpage=poce&amp;product_select='.$srow['id'].'" title="'.$srow['long_name'].'" class="list-group-item '.($srow['tselected'] == 1 ? "active" : "").'"><i class="fa fa-phone fa-fw"></i>&nbsp; '.substr($srow['long_name'], 0, 40).(strlen($srow['long_name']) > 40 ? "..." : "").'</a>';
	} 
?>