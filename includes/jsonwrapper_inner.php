<?php
if(!function_exists('json_encode')){
	require_once 'JSON/JSON.php';
	function json_encode($arg)
	{
		global $services_json;
		if (!isset($services_json)) {
			$services_json = new Services_JSON();
		}
		return $services_json->encode($arg);
	}
}

if(!function_exists('json_decode')){
	require_once 'JSON/JSON.php';
	function json_decode($arg)
	{	
		global $services_json;
		if (!isset($services_json)) {
			$services_json = new Services_JSON();
		}
		return $services_json->decode($arg);
	}
}
