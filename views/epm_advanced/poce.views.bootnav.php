<div class="list-group">
<?php 
	foreach ($product_list as $srow) 
	{
		echo '<a id="list_product_'.$srow['id'].'" href="javascript:epm_advanced_tab_poce_select_product('.$srow['id'].');" title="'.$srow['long_name'].'" class="list-group-item"><i class="fa fa-phone fa-fw"></i>&nbsp; '.substr($srow['long_name'], 0, 40).(strlen($srow['long_name']) > 40 ? "..." : "").'</a>';
	} 
?>
</div>