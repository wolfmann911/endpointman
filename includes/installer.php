<html>
<head>
	<title>PBX Endpoint Configuration Manager</title>	
	<script type="text/javascript" src="/admin/modules/endpointman/templates/javascript/jquery.js"></script> 
	<script type="text/javascript" src="/admin/modules/endpointman/templates/javascript/js/jquery-1.3.2.min.js"></script>
</head>
<body>
<table width="10%" id="tb" border="1" cellspacing="0" cellpadding="0" bgcolor="#FF0000">
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
<div class="demo-container"></div>
<?PHP
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
include 'functions.inc';

if(file_exists("amp.ini")) {
	$outfile="amp.ini";
	$wfh=fopen($outfile,'r');
	$contents = fread($wfh, filesize($outfile));
	fclose($wfh);
	unlink("amp.ini");
}

$temp_amp = unserialize(base64_decode($contents));
$amp_conf = unserialize(base64_decode($temp_amp['amp_serial']));
$data = unserialize(base64_decode($temp_amp['data']));
global $amp_conf;

global $endpoint;

$endpoint = new endpointmanager();

if($data['type'] == "product") {
	$endpoint->install_product($data['id']);
} elseif($data['type'] == "brand") {
	$endpoint->update_brand($data['id']);
} elseif($data['type'] == "firmware") {
	$endpoint->install_firmware($data['id']);
}

?>
</body>
</html>