<?php 
	foreach ($product_list as $srow) 
	{
		if ((isset($request['product_select'])) AND ($request['product_select'] == $srow['id'])) {
			$srow['tselected'] = 1;
		}
		echo '<a href="config.php?display=epm_advanced&amp;subpage=poce&amp;product_select='.$srow['id'].'" class="list-group-item '.($srow['tselected'] == 1 ? "active" : "").'"><i class="fa fa-phone fa-fw"></i>&nbsp; '.$srow['short_name'].'</a>';
	} 
?>