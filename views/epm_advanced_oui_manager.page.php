<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	$sql = 'SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY name ASC';
	$brands = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	
	$request = $_REQUEST;
	$content = load_view(__DIR__.'/epm_advanced/oui_manager.views.grid.php', array('request' => $request, 'brands' => $brands));
	echo $content;
	return;
?>