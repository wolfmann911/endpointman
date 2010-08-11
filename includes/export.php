<?php
/**
 * Endpoint Manager Export File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

if(file_exists("amp.ini")) {
	$outfile="amp.ini";
	$wfh=fopen($outfile,'r');
	$contents = fread($wfh, filesize($outfile));
	fclose($wfh);
	unlink("amp.ini");
}

$temp_amp = unserialize(base64_decode($contents));
$amp_conf = unserialize(base64_decode($temp_amp['amp_serial']));

$link = mysql_connect('localhost', $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS']);
mysql_select_db($amp_conf['AMPDBNAME'], $link);

header("Content-type: text/csv");
header('Content-Disposition: attachment; filename="devices_list.csv"');

$outstream = fopen("php://output",'w');

$sql = 'SELECT endpointman_mac_list.mac, endpointman_brand_list.name, endpointman_model_list.model, endpointman_mac_list.ext FROM endpointman_mac_list, endpointman_model_list, endpointman_brand_list WHERE endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.brand = endpointman_brand_list.id';

$result = mysql_query($sql);

while($row = mysql_fetch_assoc($result)) {
	fputcsv($outstream, $row);
    
}
fclose($outstream);