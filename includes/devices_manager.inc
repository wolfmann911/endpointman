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
global $global_cfg, $amp_conf, $debug;

//Set opened variables
$message = NULL;
$no_add = FALSE;
$final = NULL;
$button = NULL;
$searched = NULL;
$edit = NULL;
$mode = NULL;

$sql = "SELECT * FROM endpointman_product_list WHERE id > 0";
$result = mysql_query($sql);
$sql2 = "SELECT * FROM endpointman_mac_list";
$result2 = mysql_query($sql2);
if((!mysql_num_rows($result)) && (!mysql_num_rows($result2))) {
	$message = "Welcome to Enpoint Manager!<br />You have no products (Modules) installed, click <a href=\"config.php".WEB_VARS."&page=brand_model_manager\">here</a> to install some";
	$no_add = TRUE;
} elseif(!mysql_num_rows($result)) {
	$message = "Thanks for upgrading to version 2.0! Please head on over to <a href=\"config.php".WEB_VARS."&page=brand_model_manager\">\"Brand Configurations/Setup\"</a> to setup and install phone configurations";
	$no_add = TRUE;
} elseif($global_cfg['srvip'] == "") {
	$message = "Your Global Variables are not set! Please head on over to <a href=\"config.php".WEB_VARS."&page=advanced\">\"Advanced Settings\"</a> to setup your configuration";
	$no_add = TRUE;
}
if(isset($_REQUEST['button_add'])) {
	if($mac = $endpoint->mac_check_clean($_REQUEST['mac'])) {
		$sql = 'SELECT `name` FROM `users` WHERE `extension` = '.$_REQUEST['ext_list'];
		$result=mysql_query($sql);
		$name=mysql_fetch_array($result);
	
		$sql = 'SELECT endpointman_product_list. * , endpointman_brand_list.directory FROM endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_model_list.id =  \''.$_REQUEST['model_list'].'\' AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id';
		$result=mysql_query($sql);
		$row=mysql_fetch_array($result);
	
		$cfg_data = unserialize($row['cfg_data']);
		$count = count($cfg_data);
		$i = 0;
		while($i < $count) {
			if(array_key_exists('variable',$cfg_data[$i])) {
				$temping = str_replace('$','',$cfg_data[$i]['variable']);
				if(is_array($cfg_data[$i]['default_value'])) {
					$custom_cfg_data[$temping]['value'] = '';
				} else {
					$custom_cfg_data[$temping]['value'] = $cfg_data[$i]['default_value'];
				}
			}
			$i++;
		}
		$custom_cfg_data = serialize($custom_cfg_data);
	
		$sql = "INSERT INTO `endpointman_mac_list` (`mac`, `model`, `ext`, `description`, `custom_cfg_data`, `custom_cfg_template`) VALUES ('".$mac."', '".$_REQUEST['model_list']."', '".$_REQUEST['ext_list']."', '".$name['name']."', '".$custom_cfg_data."', '".$_REQUEST['template_list']."')";
		mysql_query($sql);
			
		$sql = 'SELECT id FROM endpointman_mac_list WHERE ext ='. $_REQUEST['ext_list'];
		$result=mysql_query($sql);
		$ext_id=mysql_fetch_array($result);
	
		require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');

		$phone_config = new $row['cfg_dir']();
		$phone_config->generate_config($ext_id['id']);
		$message = _("Saved")."!";
	} else {
		$message = _("Invalid MAC Address")."!";
	}
} elseif(isset($_REQUEST['editready'])) {
	$edit_row=$endpoint->get_phone_info($_REQUEST['id']);
	$edit_row['id'] = $_REQUEST['id'];
	$mode = "EDIT";
} elseif(isset($_REQUEST['button_edit'])) {
	$template_editor = TRUE;
	if ($_REQUEST['template_list'] == 0) {
		$endpoint->edit_template_display($_REQUEST['edit_id'],1);
	} else {
		$endpoint->edit_template_display($_REQUEST['template_list'],0);
	}
} elseif(isset($_REQUEST['button_save'])) {
	$sql = "SELECT name FROM users WHERE extension = ".$_REQUEST['ext_list'];
	$result=mysql_query($sql);
	$name=mysql_fetch_array($result);

	$sql = "UPDATE endpointman_mac_list SET custom_cfg_template = '".$_REQUEST['template_list']."', model = ".$_REQUEST['model_list'].", ext = ".$_REQUEST['ext_list'].", description = '".$name['name']."' WHERE id =  ". $_REQUEST['edit_id'];
	$result=mysql_query($sql);		

	$row = $endpoint->get_phone_info($_REQUEST['edit_id']);

	require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
	$phone_config = new $row['cfg_dir']();

	$phone_config->generate_config($_REQUEST['edit_id']);
	$message = _("Saved")."!";
} elseif(isset($_REQUEST['deleteready'])) {
	$row = $endpoint->get_phone_info($_REQUEST['id']);

	if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
		require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');

		$phone_config = new $row['cfg_dir']();
		$phone_config->delete_config($_REQUEST['id']);
		unset($phone_config);
	}

	$sql = "DELETE FROM endpointman_mac_list WHERE id=".$_REQUEST['id'];
	$result=mysql_query($sql);
} elseif((isset($_REQUEST['button_add_selected_phones'])) AND (isset($_REQUEST['add']))) {
	$list_num = count($_REQUEST['add']);
	for($i=0;$i<$list_num;$i++) {
		$num = $_REQUEST['add'][$i];
		if($mac = $endpoint->mac_check_clean($_REQUEST['mac_'.$num])) {						
			$sql = "SELECT * FROM endpointman_mac_list WHERE ext = ".$_REQUEST['ext_list_'.$num];
			$result=mysql_query($sql);
			if(!mysql_num_rows($result)) {
				$sql = 'SELECT `name` FROM `users` WHERE `extension` = '.$_REQUEST['ext_list_'.$num];
				$result=mysql_query($sql);
				$name=mysql_fetch_array($result);

				$sql = 'SELECT endpointman_product_list. * , endpointman_brand_list.directory FROM endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_model_list.id =  \''.$_REQUEST['model_list_'.$num].'\' AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id';
				$result=mysql_query($sql);
				$row=mysql_fetch_array($result);

				$sql = "INSERT INTO `endpointman_mac_list` (`mac`, `model`, `ext`, `description`) VALUES ('".$mac."', '".$_REQUEST['model_list_'.$num]."', '".$_REQUEST['ext_list_'.$num]."', '".$name['name']."')";
				mysql_query($sql);

				$sql = 'SELECT id FROM endpointman_mac_list WHERE ext ='. $_REQUEST['ext_list_'.$num];
				$result=mysql_query($sql);
				$ext_id=mysql_fetch_array($result);
				
				if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
					require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
					$phone_config = new $row['cfg_dir']();
					$phone_config->generate_config($ext_id['id']);
					unset($phone_config);
				}
				$message = _("Saved")."!";			
			} else {
				$message = _("One or more selected phones had the same extension as a phone already added, these devices were not added");
			}
			
		} else {
			$message = _("Invalid MAC Address");
		}
	}
} elseif((isset($_REQUEST['button_update_phones'])) AND (isset($_REQUEST['selected']))) {
	$list_num = count($_REQUEST['selected']);
	for($i=0;$i<$list_num;$i++) {
		if(($_REQUEST['brand_list_selected'] > 0) AND ($_REQUEST['model_list_selected'] > 0)) {
			$sql = "UPDATE endpointman_mac_list SET custom_cfg_data = '', custom_cfg_template = '', user_cfg_data = '', config_files_override = '', model = ".$_REQUEST['model_list_selected']." WHERE id =  ". $_REQUEST['selected'][$i];
			$result=mysql_query($sql);		

			$row = $endpoint->get_phone_info($_REQUEST['selected'][$i]);

			require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
			$phone_config = new $row['cfg_dir']();

			$phone_config->generate_config($_REQUEST['selected'][$i]);
			$message = _("Saved")."!";			
		} else {
			$message = _("Please select a Brand and/or Model");
		}
	}
} elseif(isset($_REQUEST['button_delete_selected_phones'])) {
	if(isset($_REQUEST['selected'])) {
		$list_num = count($_REQUEST['selected']);
		for($i=0;$i<$list_num;$i++) {
			$row = $endpoint->get_phone_info($_REQUEST['selected'][$i]);

			if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
				require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');

				$phone_config = new $row['cfg_dir']();
				$phone_config->delete_config($_REQUEST['selected'][$i]);
				unset($phone_config);
			}

			$sql = "DELETE FROM endpointman_mac_list WHERE id=".$_REQUEST['selected'][$i];
			$result=mysql_query($sql);
		}
	} else {
		$message = _("No Phones Selected")."!";
	}
} elseif(isset($_REQUEST['button_go'])) {
	if ($_REQUEST['nmap'] == 1) {
		$temp = $endpoint->discover_new($_REQUEST['netmask']);
	} else {
		$temp = $endpoint->discover_new($_REQUEST['netmask'], FALSE);
	}
	$count = count($temp);
	$i = 0;
	while($i < $count) {
		if (!$temp[$i]['endpoint_managed']) {
			if ($temp[$i]['brand']) {
				$final[$i] = $temp[$i];
				$final[$i]['id'] = $i;
				$sqln = "SELECT * FROM endpointman_model_list WHERE enabled = 1 AND brand =".$temp[$i]['brand_id'];
				$model_list=mysql_query($sqln);
				$j = 0;
				while($row = mysql_fetch_assoc($model_list)) {
					$final[$i]['list'][$j] = $row;
					$j++;
				}
			}
		}
		$i++;
	}
	if($final) {
		$final = array_values($final);
	} else {
		$final = NULL;
		$message = _("No Devices Found");
	}
	$searched = 1;
} elseif(isset($_REQUEST['button_rebuild_configs_for_all_phones'])) {
	$sql = "SELECT endpointman_brand_list.directory, endpointman_mac_list.id, endpointman_product_list.cfg_dir FROM endpointman_mac_list, endpointman_brand_list, endpointman_product_list, endpointman_model_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.model = endpointman_model_list.id ORDER BY endpointman_product_list.cfg_dir ASC";
	$result = mysql_query($sql);
	while($row = mysql_fetch_assoc($result)) {
		if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
			require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
			$phone_config = new $row['cfg_dir']();
			$phone_config->generate_config($row['id']);
			unset($phone_config);
		}
	}
} elseif(isset($_REQUEST['button_rebuild_reboot'])) {
	if($_REQUEST['product_select'] == "") {
		$message = _("Please select a product");
	} elseif($_REQUEST['template_selector'] == "") {
		$message = _("Please select a template");
	} else {
		$sql = "SELECT endpointman_brand_list.directory, endpointman_mac_list.id, endpointman_product_list.cfg_dir FROM endpointman_mac_list, endpointman_brand_list, endpointman_product_list, endpointman_model_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_product_list.id = ".$_REQUEST['product_select'];
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			$sql = "UPDATE endpointman_mac_list SET custom_cfg_template = '".$_REQUEST['template_selector']."' WHERE id =  ". $row['id'];
			mysql_query($sql);
			if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
				require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
				$phone_config = new $row['cfg_dir']();
				$phone_config->generate_config($row['id']);
				unset($phone_config);
			}
		}
	}
} elseif(isset($_REQUEST['button_rebuild_selected'])) {
	if(isset($_REQUEST['selected'])) {
		$list_num = count($_REQUEST['selected']);
		for($i=0;$i<$list_num;$i++) {
			$row = $endpoint->get_phone_info($_REQUEST['selected'][$i]);
			if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
				require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
				$phone_config = new $row['cfg_dir']();
				$phone_config->generate_config($_REQUEST['selected'][$i]);
				unset($phone_config);
			}
		}
	} else {
		$message = _("No Phones Selected")."!";
	}
} elseif(isset($_REQUEST['button_reboot_this_brand'])) {
	if($_REQUEST['rb_brand'] != "") {
		$sql = 'SELECT endpointman_mac_list.id , endpointman_brand_list.directory , endpointman_product_list.cfg_dir FROM endpointman_mac_list , endpointman_model_list , endpointman_brand_list , endpointman_product_list WHERE endpointman_brand_list.id = endpointman_model_list.brand AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_brand_list.id = '.$_REQUEST['rb_brand'].' ORDER BY endpointman_product_list.cfg_dir ASC';
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			if(file_exists(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc')) {
				require_once(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
				$phone_config = new $row['cfg_dir']();
				$phone_config->reboot($row['id']);
				unset($phone_config);
			}
		}
	} else {
		$message = _("No Brand Selected for Reboot");
	}
}

$sql = 'SELECT endpointman_mac_list.id , endpointman_mac_list.mac , endpointman_model_list.model , endpointman_mac_list.ext , endpointman_mac_list.description , endpointman_brand_list.name, endpointman_mac_list.custom_cfg_data, endpointman_mac_list.custom_cfg_template FROM endpointman_mac_list , endpointman_model_list , endpointman_brand_list WHERE ( endpointman_model_list.id = endpointman_mac_list.model ) AND ( endpointman_model_list.brand = endpointman_brand_list.id )';
$result=mysql_query($sql);


$i = 0;
while ($row = mysql_fetch_assoc($result)) {	#Displays the phones in the database with edit and delete buttons
	$list[$i] = $row;
	if (($row['custom_cfg_template'] == 0) && (isset($row['custom_cfg_data'])) ) {
		$list[$i]['template_name'] = "Custom-".$row['mac'];
	} elseif((!isset($row['custom_cfg_data'])) && ($row['custom_cfg_template'] == 0)) {
		$list[$i]['template_name'] = "N/A";
	} else {
		$sql = "SELECT name FROM endpointman_template_list WHERE id =".$row['custom_cfg_template'];
		$result1=mysql_query($sql);
		$row2 = mysql_fetch_assoc($result1);
		$list[$i]['template_name'] = $row2['name'];
	}
	$i++;
}		# End while

$sql = 'SELECT * FROM  endpointman_mac_list WHERE  model =0';
$result=mysql_query($sql);

while ($row = mysql_fetch_assoc($result)) {	#Displays unknown phones in the database with edit and delete buttons
	$list[$i] = $row;
	$list[$i]['name'] = _("Unknown");
	$list[$i]['template_name'] = "N/A";
	$list[$i]['model'] = _("Unknown");
	$i++;
}

$amp_send['AMPDBUSER'] = $amp_conf['AMPDBUSER'];
$amp_send['AMPDBPASS'] = $amp_conf['AMPDBPASS'];
$amp_send['AMPDBNAME'] = $amp_conf['AMPDBNAME'];

$sql = "SELECT DISTINCT endpointman_product_list.id, endpointman_product_list.long_name  FROM endpointman_product_list, endpointman_template_list WHERE endpointman_product_list.installed = 1 AND endpointman_template_list.product_id = endpointman_product_list.id";
$result = mysql_query($sql);
$product_list[0]['value'] = "";
$product_list[0]['text'] = "";
$i = 1;
while($row = mysql_fetch_assoc($result)) {
	$product_list[$i]['value'] = $row['id'];
	$product_list[$i]['text'] = $row['long_name'];
	$i++;
}
//initialize a Rain TPL object
if (isset($template_editor)) {
	
} else {
	$tpl = new RainTPL( LOCAL_PATH.'templates' );
	$tpl->assign("list", $list);
	$tpl->assign("error", "");
	$tpl->assign("srvip", $_SERVER["SERVER_ADDR"]);
	$tpl->assign("web_vars", WEB_VARS);
	if($ma = $endpoint->modelsAvailable()) {
		$tpl->assign("models_ava", $ma);
	}
	$tpl->assign("product_list", $product_list);
	$tpl->assign("display_ext", $endpoint->displayExtension());
	$tpl->assign("brand_ava", $endpoint->brandAvailable());
	$tpl->assign("unmanaged", $final);
	$tpl->assign("button", $button);
	$tpl->assign("searched", $searched);
	$tpl->assign("edit", $edit);
	$tpl->assign("amp_conf_serial", base64_encode(serialize($amp_send)));
	$tpl->assign("no_add", $no_add);
	$tpl->assign("mode", $mode);
	
	if(isset($final)) {
		$tpl->assign("cached_devices", base64_encode(serialize($final)));
	}

	if (isset($mode) && ($mode == "EDIT")) {
		if($ma = $endpoint->modelsAvailable($edit_row['model_id'],NULL,$edit_row['brand_id'])) {
			$tpl->assign("mac", $edit_row['mac']);
			$tpl->assign("name", $edit_row['name']);	
			$tpl->assign("models_ava", $ma);
			$tpl->assign("display_ext", $endpoint->displayExtension($edit_row['ext']));
			$tpl->assign("display_templates", $endpoint->display_templates($edit_row['product_id'],$edit_row['custom_cfg_template']));
			$tpl->assign("edit_id", $edit_row['id']);			
		} else {
			$message = _("You have disabled/removed all models that correspond to this brand. Please enable them in 'Brand Configurations/Setup' before trying to edit this phone");
			$tpl->assign("mode", NULL);
		}
	}
	
	
	$tpl->assign("message", $message);
	$tpl->assign("debug", $debug);
	
	//draw the template
	echo $tpl->draw( 'devices_manager' );
}
?>