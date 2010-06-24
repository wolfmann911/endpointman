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
$tpl = new RainTPL( LOCAL_PATH.'templates' );
//Set the message variable to nothing, however we can set it later and it will show up on the page
$message = "";
$tpl->assign("message", $message);
$tpl->assign("web_vars", WEB_VARS);

echo $tpl->draw( 'global_header' );
echo $tpl->draw( 'global_links' );
echo $tpl->draw( 'advanced_subheader' );

if(!isset($_REQUEST['subpage'])) {
	$_REQUEST['subpage'] = "";
}

switch($_REQUEST['subpage']) {
	case "iedl":
		$amp_serial = base64_encode(serialize($amp_conf));
		$amp_serial = base64_encode(serialize($amp_conf));
		$temp_amp['amp_serial'] = $amp_serial;
		$temp_amp = base64_encode(serialize($temp_amp));

		$outfile=LOCAL_PATH."/includes/amp.ini";
		$wfh=fopen($outfile,'w');
		fwrite($wfh, $temp_amp);
		fclose($wfh);
		$tpl->assign("exporter_address", $_SERVER["HTTP_HOST"]."/admin/modules/endpointman/includes/export.php");
		//Dave B's Q&D file upload security code (http://us2.php.net/manual/en/features.file-upload.php) 
		if((isset($_REQUEST['action'])) AND (isset($_REQUEST['button_import'])) AND ($_REQUEST['action'] == "import")) {
			$allowedExtensions = array("csv","txt"); 
			foreach ($_FILES as $file) { 
				if ($file['tmp_name'] > '') { 
					if (!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) { 
						$tpl->assign("message", "We support only CVS and TXT files");
					} else {
						$uploaddir = LOCAL_PATH;
						$uploadfile = $uploaddir . basename($_FILES['import_csv']['name']);
						if (move_uploaded_file($_FILES['import_csv']['tmp_name'], $uploadfile)) {
							//Parse the uploaded file
							$handle = fopen(LOCAL_PATH.$_FILES['import_csv']['name'], "r");
							$i = 1;
						    while (($device = fgetcsv($handle, filesize(LOCAL_PATH.$_FILES['import_csv']['name']))) !== FALSE) {
								if($device[0] != "") {
									if($mac = $endpoint->mac_check_clean($device[0])) {
										$sql = "SELECT id FROM endpointman_brand_list WHERE name LIKE '%".$device[1]."%' LIMIT 1";
										$res_brand = mysql_query($sql);
										if(mysql_num_rows($res_brand)) {
											$brand_id = mysql_fetch_row($res_brand);
											$brand_id = $brand_id[0];
										} else {
											$brand_id = 0;
										}
										$sql = "SELECT id FROM endpointman_model_list WHERE brand = ".$brand_id." AND model LIKE '%".$device[2]."%' LIMIT 1";
										$res_model = mysql_query($sql);
										$sql = "SELECT extension, name FROM users WHERE extension LIKE '%".$device[3]."%' LIMIT 1";
										$res_ext = mysql_query($sql);
										if(mysql_num_rows($res_model)) {
											$model_id = mysql_fetch_row($res_model);
											$model_id = $model_id[0];
										} else {
											$model_id = 0;
										}
										if(mysql_num_rows($res_ext)) {
											$ext = mysql_fetch_row($res_ext);
											$description = $ext[1];
											$ext = $ext[0];
										} else {
											$ext = '';
											$description = '';
										}
										$sql = "INSERT INTO  endpointman_mac_list (mac, model, ext, description) VALUES ('".$mac."','".$model_id."','".$ext."','".$description."')";										
										mysql_query($sql);
										$message .= "Inserted Mac Address ".$mac."<br/>";
									} else {
										$message .= "Invalid Mac on line ". $i. "<br />";
									}
								}
								$i++;
							}
							fclose($handle);
							unlink(LOCAL_PATH.$_FILES['import_csv']['name']);
							$message .= "Please reboot & rebuild all imported phones<br />";
							$tpl->assign("message", $message);	
						} else {
							$tpl->assign("message", "Possible file upload attack!");
						
						}
					}
				} 
			}
		}
		echo $tpl->draw( 'advanced_settings_iedl' );
		break;
	case "sh_manager":
		if(isset($_REQUEST['button_hide'])) {
			if(isset($_REQUEST['model'])) {
				$sql = "UPDATE endpointman_model_list SET hidden = 1 WHERE id = ".$_REQUEST['model'];
			} elseif(isset($_REQUEST['brand'])) {
				$sql = "UPDATE endpointman_brand_list SET hidden = 1 WHERE id = ".$_REQUEST['brand'];
			} elseif(isset($_REQUEST['product'])) {
				$sql = "UPDATE endpointman_product_list SET hidden = 1 WHERE id = ".$_REQUEST['product'];
			}
			mysql_query($sql);
		}elseif(isset($_REQUEST['button_show'])) {			
			if(isset($_REQUEST['model'])) {
				$sql = "UPDATE endpointman_model_list SET hidden = 0 WHERE id = ".$_REQUEST['model'];
			} elseif(isset($_REQUEST['brand'])) {
				$sql = "UPDATE endpointman_brand_list SET hidden = 0 WHERE id = ".$_REQUEST['brand'];
			} elseif(isset($_REQUEST['product'])) {
				$sql = "UPDATE endpointman_product_list SET hidden = 0 WHERE id = ".$_REQUEST['product'];
			}
			mysql_query($sql);
		}
		$sql="SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY id ASC ";
		$result=mysql_query($sql);
		$i = 0;
		while($row = mysql_fetch_assoc($result)) {
			$row_out[$i] = $row;
			$row_out[$i]['count'] = $i;
			if($row['installed']){
				$j = 0;
				$sql = 'SELECT * FROM endpointman_product_list WHERE brand = '.$row['id'].' ORDER BY long_name ASC';
				$result2=mysql_query($sql);
				$xml_data = "";
				while($row2 = mysql_fetch_assoc($result2)) {
					$row_out[$i]['products'][$j] = $row2;
					if($row2['installed']) {
						$sql = 'SELECT * FROM endpointman_model_list WHERE product_id = '.$row2['id'];
						$result3=mysql_query($sql);
						$k = 0;
						while($row3 = mysql_fetch_assoc($result3)) {
							$row_out[$i]['products'][$j]['models'][$k] = $row3;
							$k++;
						}
					}
					$j++;
				}
			}
			$i++;
		}
		$tpl->assign("brand2_list", $row_out);
		echo $tpl->draw( 'advanced_settings_sh_manager' );
		break;
	case "oui_manager":
		if((isset($_REQUEST['oui_sub'])) AND ($_REQUEST['rb_brand'] > 0) AND ($_REQUEST['oui'] != "")) {
			$sql = "INSERT INTO  endpointman_oui_list (oui, brand, custom) VALUES ('".$_REQUEST['oui']."',  '".$_REQUEST['rb_brand']."',  '1')";
			mysql_query($sql);
		}
		if((isset($_REQUEST['delete'])) AND ($_REQUEST['id'] > 0)) {
			$sql = "DELETE FROM endpointman_oui_list WHERE id = ". $_REQUEST['id'];
			mysql_query($sql);
		}
		$result = mysql_query('SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id AND endpointman_oui_list.custom = 0');
		$i = 0;
		while($row = mysql_fetch_array($result)) {
			$row_out[$i] = $row;
			$i++;
		}
		$tpl->assign("oui_list", $row_out);
		$result = mysql_query('SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id AND endpointman_oui_list.custom = 1');
		if(mysql_num_rows($result) > 0) {
			$i = 0;
			while($row = mysql_fetch_array($result)) {
				$row_out_custom[$i] = $row;
				$i++;
			}
			$tpl->assign("oui_list_custom", $row_out_custom);
		}
		$tpl->assign("brand_ava", $endpoint->brandAvailable());
		echo $tpl->draw( 'advanced_settings_oui' );
		break;
	case "poce":
		$sql = 'SELECT * FROM `endpointman_product_list` WHERE `installed` = 1 AND `id` > 0';
		$result = mysql_query($sql);
		$i = 0;
		while($row = mysql_fetch_assoc($result)) {
			$product_list[$i]['value'] = $row['id'];
			$product_list[$i]['text'] = $row['long_name'];
			if((isset($_REQUEST['product_select'])) AND ($_REQUEST['product_select'] == $row['id'])) {
				$product_list[$i]['selected'] = 1;
			}
			$i++;
		}
		if(isset($_REQUEST['delete'])) {
			$sql = "DELETE FROM endpointman_custom_configs WHERE id =". $_REQUEST['sql'];
			mysql_query($sql);
		}
		if(isset($_REQUEST['file'])) {
			$sql = 'SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id =' . $_REQUEST['product_select'];
			$result = mysql_query($sql);
			$row = mysql_fetch_assoc($result);			
			$config_files = explode(",",$row['config_files']);
			$file=PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/".$config_files[$_REQUEST['file']];
			if(isset($_REQUEST['config_text'])) {
				if(isset($_REQUEST['button_save'])) {
					$wfh=fopen($file,'w');
					fwrite($wfh,$_REQUEST['config_text']);
					fclose($wfh);
				}elseif(isset($_REQUEST['button_save_as'])) {
					$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ("'.addslashes($_REQUEST['save_as_name']).'","'.addslashes($config_files[$_REQUEST['file']]).'",'.$_REQUEST['product_select'].',"'.addslashes($_REQUEST['config_text']).'")';
					mysql_query($sql);
				}
			}
			$handle = fopen($file, "rb");
			$contents = fread($handle, filesize($file));
			fclose($handle);
			$tpl->assign("save_as_name_value", $config_files[$_REQUEST['file']]);
			$tpl->assign("config_data", $contents);
		} elseif(isset($_REQUEST['sql'])) {
			if(isset($_REQUEST['config_text'])) {
				if(isset($_REQUEST['button_save'])) {
					$sql = "UPDATE endpointman_custom_configs SET data = '".addslashes($_REQUEST['config_text'])."' WHERE id = ".$_REQUEST['sql'];
					mysql_query($sql);
				}elseif(isset($_REQUEST['button_save_as'])) {
					$sql = 'SELECT original_name FROM endpointman_custom_configs WHERE id = 11';
					$res = mysql_query($sql);
					$file_name = mysql_fetch_array($res);
					
					$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ("'.addslashes($_REQUEST['save_as_name']).'","'.addslashes($file_name['original_name']).'",'.$_REQUEST['product_select'].',"'.addslashes($_REQUEST['config_text']).'")';
					mysql_query($sql);
				}
			}
			$sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $_REQUEST['sql'];
			$result = mysql_query($sql);
			$row = mysql_fetch_assoc($result);
			$tpl->assign("save_as_name_value", $row['name']);
			$tpl->assign("config_data", $row['data']);
		}
		if(isset($_REQUEST['product_select'])) {
			$endpoint->sync_product($_REQUEST['product_select']);
			$sql = 'SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id =' . $_REQUEST['product_select'];
			$result = mysql_query($sql);
			$row = mysql_fetch_assoc($result);			
			$config_files = explode(",",$row['config_files']);
			$i = 0;
			while($i < count($config_files)) {
				$file_list[$i]['value'] = $i;
				$file_list[$i]['text'] = $config_files[$i];
				$i++;
			}
			$sql = 'SELECT * FROM `endpointman_custom_configs` WHERE `product_id` = '. $_REQUEST['product_select'];
			$result = mysql_query($sql);
			$i = 0;
			if(mysql_num_rows($result)) {
				while($row2 = mysql_fetch_assoc($result)) {
					$sql_file_list[$i]['value'] = $row2['id'];
					$sql_file_list[$i]['text'] = $row2['name'];
					$sql_file_list[$i]['ref'] = $row2['original_name'];
					$i++;
				}
			} else {
				$sql_file_list = NULL;
			}
			require(PHONE_MODULES_PATH .$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');

			$phone_config = new $row['cfg_dir']();
			if((method_exists($phone_config,'display_options')) AND (method_exists($phone_config,'process_options'))) {
				if(isset($_REQUEST['phone_options'])) {
					$tpl->assign("options", $phone_config->process_options());
				} else {
					$tpl->assign("options", $phone_config->display_options());
				}
			}
			
			$tpl->assign("file_list", $file_list);
			$tpl->assign("sql_file_list", $sql_file_list);
			$tpl->assign("product_selected", $_REQUEST['product_select']);
		}
		$tpl->assign("product_list", $product_list);
		echo $tpl->draw( 'advanced_settings_poce' );
		break;
	case "settings":
	default:
		global $global_cfg;
		if(isset($_REQUEST['button_update_globals'])) {
			$_POST['srvip'] = trim($_POST['srvip']);  #trim whitespace from IP address

			$_POST['config_loc'] = trim($_POST['config_loc']);  #trim whitespace from Config Location

			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['srvip'] . "' WHERE var_name='srvip'";
			mysql_query($sql);
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['tz'] . "' WHERE var_name='tz'";
			mysql_query($sql);

			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['config_loc'] . "' WHERE var_name='config_location'";
			mysql_query($sql);

			if((isset($_POST['enable_ari'])) AND ($_POST['enable_ari'] == "on")) {
				$_POST['enable_ari'] = 1;
			} else {
				$_POST['enable_ari'] = 0;
			}
			if((isset($_POST['enable_debug'])) AND ($_POST['enable_debug'] == "on")) {
				$_POST['enable_debug'] = 1;
			} else {
				$_POST['enable_debug'] = 0;
			}
			
			if((isset($_POST['disable_htaccess'])) AND ($_POST['disable_htaccess'] == "on")) {
				$htaccess = "allow from all";

				$outfile = $amp_conf['AMPWEBROOT']."/admin/modules/endpointman/.htaccess";
				$wfh=fopen($outfile,'w');
				fwrite($wfh,$htaccess);
				fclose($wfh);
				$_POST['disable_htaccess'] = 1;
			} else {
				$htaccess = "allow from all
AuthName FreePBX-Admin-only
Require valid-user
AuthType Basic
AuthMySQLEnable\tOn
AuthMySQLHost\tlocalhost
AuthMySQLDB\tasterisk
AuthMySQLUserTable\tampusers
AuthMySQLUser\t".$amp_conf['AMPDBUSER']."
AuthMySQLPassword\t".$amp_conf['AMPDBPASS']."
AuthMySQLNameField\tusername
AuthMySQLPasswordField\tpassword
AuthMySQLAuthoritative\tOn
AuthMySQLPwEncryption\tnone
AuthMySQLUserCondition\t\"username = 'admin'\"

<Files .*>
  deny from all
</Files>
";

				$outfile = $amp_conf['AMPWEBROOT']."/admin/modules/endpointman/.htaccess";
				$wfh=fopen($outfile,'w');
				fwrite($wfh,$htaccess);
				fclose($wfh);
				$_POST['disable_htaccess'] = 0;
			}
			if((isset($_POST['enable_updates'])) AND ($_POST['enable_updates'] == "on")) {
				$_POST['enable_updates'] = 1;
				$sql = "INSERT INTO cronmanager (module, id, time, freq, lasttime, command) VALUES ('endpointman', 'UPDATES', '23', '24', '0', 'php ".$amp_conf['AMPWEBROOT']."/admin/modules/endpointman/includes/update_check.php')";
				mysql_query($sql);
			} else {
				$_POST['enable_updates'] = 0;
				$sql = "DELETE FROM `cronmanager` WHERE CONVERT(`cronmanager`.`module` USING utf8) = 'endpointman' AND CONVERT(`cronmanager`.`id` USING utf8) = 'UPDATES' LIMIT 1";
				mysql_query($sql);
			}
			
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['enable_ari'] . "' WHERE var_name='enable_ari'";
			mysql_query($sql);
	
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['enable_debug'] . "' WHERE var_name='debug'";
			mysql_query($sql);

			$sql="SELECT gmtoff,gmthr FROM endpointman_time_zones WHERE tz='" . $_POST['tz'] . "'";
			$result=mysql_query($sql);
			$row=mysql_fetch_array($result);

			$sql="UPDATE endpointman_global_vars SET value='" . $row['gmtoff'] . "' where var_name='gmtoff'";
			mysql_query($sql);
			$sql="UPDATE endpointman_global_vars SET value='" . $row['gmthr'] . "' where var_name='gmthr'";
			mysql_query($sql);
	
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['asterisk_loc'] . "' WHERE var_name='asterisk_location'";
			mysql_query($sql);
	
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['enable_updates'] . "' WHERE var_name='check_updates'";
			mysql_query($sql);
	
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['nmap_loc'] . "' WHERE var_name='nmap_location'";
			mysql_query($sql);
			
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['arp_loc'] . "' WHERE var_name='arp_location'";
			mysql_query($sql);
			
			$sql="UPDATE endpointman_global_vars SET value='" . $_POST['disable_htaccess'] . "' WHERE var_name='disable_htaccess'";
			mysql_query($sql);
	
			$message = "<h3><center>Updated!</center></h3>";
		}
		//Because we are working with global variables we probably updated them, so lets refresh those variables
		$sql = "SELECT * FROM  endpointman_global_vars";
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			$global_cfg[$row['var_name']] = $row['value'];
		}
		
		if($global_cfg['enable_ari']) {
			$ari_selected = "checked";
		} else {
			$ari_selected = "";
		}
		
		if($global_cfg['disable_htaccess']) {
			$htaccess_selected = "checked";
		} else {
			$htaccess_selected = "";
		}
		
		if($global_cfg['check_updates']) {
			$updates_selected = "checked";
		} else {
			$updates_selected = "";
		}
		if($global_cfg['debug']) {
			$debug_selected = "checked";
			global $debug;
			$debug = $debug . print_r($_REQUEST,true);
			$tpl->assign("debug", $debug);
		} else {
			$debug_selected = "";
		}
		$tpl->assign("htaccess_selected", $htaccess_selected);
		$tpl->assign("ari_selected", $ari_selected);
		$tpl->assign("updates_selected", $updates_selected);
		$tpl->assign("debug_selected", $debug_selected);
		$tpl->assign("message", $message);
		$tpl->assign("ip", $_SERVER["SERVER_ADDR"]);
		$tpl->assign("srvip", $global_cfg['srvip']);
		$tpl->assign("arp_location", $global_cfg['arp_location']);
		$tpl->assign("nmap_location", $global_cfg['nmap_location']);
		$tpl->assign("asterisk_location", $global_cfg['asterisk_location']);

		$tpl->assign("config_location", $global_cfg['config_location']);
		$tpl->assign("list_tz", $endpoint->listTZ($global_cfg['tz']));
		$tpl->assign("brand_list", $endpoint->brandAvailable());
		echo $tpl->draw( 'advanced_settings_settings' );
		break;

	
}
if($global_cfg['debug']) {
	global $debug;
	$tpl->assign("debug", $debug);
}
echo $tpl->draw( 'global_footer' );
?>