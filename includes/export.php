<?php
/*
Endpoint Manager V2
Copyright (C) 2009-2010  Ed Macri, John Mullinix and Andrew Nagy 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
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

?>