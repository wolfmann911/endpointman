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
include 'jsonwrapper.php';

$amp_conf = unserialize(base64_decode($_REQUEST['amp_conf']));

$link = mysql_connect('localhost', $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS']);
mysql_select_db($amp_conf['AMPDBNAME'], $link);


ini_set('display_errors', 1);
error_reporting(E_ALL);
if(($_REQUEST['id'] == "") OR ($_REQUEST['id'] == "0")) {
	$out[0]['optionValue'] = "";
	$out[0]['optionDisplay'] = "";
	echo json_encode($out);
	die();
}

if($_REQUEST['type'] == "model") {
	$sql = "SELECT * FROM endpointman_model_list WHERE enabled = 1 AND brand =". $_GET['id'];
} elseif ($_REQUEST['type'] == "template") {
	$sql = "SELECT id, name as model FROM  endpointman_template_list WHERE  product_id =". $_GET['id'];
} elseif ($_REQUEST['type'] == "template2") {
	$sql = "SELECT endpointman_template_list.id, endpointman_template_list.name as model FROM endpointman_template_list, endpointman_model_list, endpointman_product_list WHERE endpointman_template_list.product_id = endpointman_model_list.product_id AND endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_model_list.id = ". $_GET['id'];
}

$result = mysql_query($sql);

if (($_REQUEST['type'] == "template") OR ($_REQUEST['type'] == "template2")) {
	$out[0]['optionValue'] = 0;
	$out[0]['optionDisplay'] = "Custom...";
	$i=1;
} elseif ($_REQUEST['type'] == "model") {
	$out[0]['optionValue'] = 0;
	$out[0]['optionDisplay'] = "";
	$i=1;
} else {
	$i=0;
}


while ($row = mysql_fetch_assoc($result)) {
	$out[$i]['optionValue'] = $row['id'];
	$out[$i]['optionDisplay'] = $row['model'];
	$i++;
}

echo json_encode($out);
?>