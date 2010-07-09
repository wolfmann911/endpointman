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
global $debug;
global $type;
$message = NULL;
$no_add = FALSE;
$default_display = FALSE;
$sql = "SELECT * FROM endpointman_product_list WHERE id > 0";
$result = mysql_query($sql);
$sql2 = "SELECT * FROM endpointman_mac_list";
$result2 = mysql_query($sql2);
if((!mysql_num_rows($result)) && (!mysql_num_rows($result2))) {
	$message = _("Welcome to Endpoint Manager")."!<br />"._("You have no products (Modules) installed, click")." <a href=\"config.php?display=epm_config&type=$type\">"._("here")."</a> "._("to install some");
	$no_add = TRUE;
} elseif(!mysql_num_rows($result)) {
	$message = "Thanks for upgrading to version 2.0! Please head on over to <a href=\"config.php?display=epm_config&type=$type\">\"Brand Configurations/Setup\"</a> to setup and install phone configurations";
	$no_add = TRUE;
}

if(isset($_REQUEST['edit_template'])) {
	$endpoint->edit_template_display($_REQUEST['id'],$_REQUEST['custom']);
} elseif(isset($_REQUEST['button_save'])) {
	$sql = "INSERT INTO endpointman_template_list (product_id, name) VALUES ('".$_REQUEST['model_class']."','".$_REQUEST['template_name']."')";
	mysql_query($sql);
	$sql = "SELECT id FROM endpointman_template_list WHERE product_id = '".$_REQUEST['model_class']."' AND name = '".$_REQUEST['template_name']."'";
	$result = mysql_query($sql);
	$row = mysql_fetch_assoc($result);
	$endpoint->edit_template_display($row['id'],0);
} elseif(isset($_REQUEST['delete_template'])) {
	$sql = "DELETE FROM endpointman_template_list WHERE id = ". $_REQUEST['id'];
	mysql_query($sql);
	$default_display = TRUE;
	$message = _('Deleted');
} elseif((isset($_REQUEST['button_save_template'])) AND (isset($_REQUEST['custom']))) {
	$endpoint->save_template($_REQUEST['id'],$_REQUEST['custom'],$_REQUEST);
	$default_display = TRUE;
	$message = _('Saved');
} else {
	$default_display = TRUE;
}

if($default_display) {
	$sql = 'SELECT endpointman_template_list.*, endpointman_product_list.long_name as model_class FROM endpointman_template_list, endpointman_product_list WHERE endpointman_template_list.product_id = endpointman_product_list.id';
	$result = mysql_query($sql);
	$i = 0;
	while($row = mysql_fetch_assoc($result)) {
		$row_out[$i] = $row;
		$row_out[$i]['custom'] = 0;
		$i++;
	}

	$sql = 'SELECT endpointman_mac_list.id,endpointman_mac_list.mac,endpointman_mac_list.model,endpointman_mac_list.description,endpointman_product_list.long_name as model_class FROM endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.custom_cfg_data IS NOT NULL AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_mac_list.custom_cfg_template = 0';
	$result = mysql_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		$row_out[$i] = $row;
		$row_out[$i]['custom'] = 1;
		$row_out[$i]['name'] = $row['mac'] . "-(" .$row['description'].")";
		$i++;
	}

	$sql = "SELECT * FROM  endpointman_product_list WHERE installed = 1 AND cfg_dir !=  ''";
	$result = mysql_query($sql);
	$i = 0;
	while($row = mysql_fetch_assoc($result)) {
		$class_row[$i]['value'] = $row['id'];
		$class_row[$i]['text'] = $row['long_name'];
		$i++;
	}
	$tpl = new RainTPL( LOCAL_PATH.'templates' );
	$tpl->assign("templates_list", $row_out);
	$tpl->assign("class_list", $class_row);
	$tpl->assign("no_add", $no_add);
	$tpl->assign("debug", $debug);
	$tpl->assign("message", $message);
	$tpl->assign("web_var", "?type=$type");
	echo $tpl->draw( 'template_manager' );
}
?>
