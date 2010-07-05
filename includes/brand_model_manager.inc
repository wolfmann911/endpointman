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
$check_for_updates = FALSE;

if((isset($_REQUEST['button_install'])) OR (isset($_REQUEST['button_update']))) {
	if(isset($_REQUEST['product'])) {
		$installer = array("type" => 'product', "id" => $_REQUEST['product']);
	} elseif(isset($_REQUEST['brand'])) {
		$installer = array("type" => 'brand', "id" => $_REQUEST['brand']);
	}
} elseif((isset($_REQUEST['button_install_firmware'])) OR (isset($_REQUEST['button_update_firmware'])) AND (isset($_REQUEST['product']))) {
	$installer = array("type" => 'firmware', "id" => $_REQUEST['product']);
} elseif((isset($_REQUEST['button_remove_firmware'])) AND (isset($_REQUEST['product']))) {
	$endpoint->remove_firmware($_REQUEST['product']);
} elseif(isset($_REQUEST['button_disable'])) {
	if(isset($_REQUEST['model'])) {
		$sql = "UPDATE endpointman_model_list SET enabled = 0 WHERE id = ".$_REQUEST['model'];
	} elseif(isset($_REQUEST['brand'])) {
		$sql = "UPDATE endpointman_brand_list SET enabled = 0 WHERE id = ".$_REQUEST['model'];
	}
	mysql_query($sql);
} elseif(isset($_REQUEST['button_enable'])) {
	if(isset($_REQUEST['model'])) {
		$sql = "UPDATE endpointman_model_list SET enabled = 1 WHERE id = ".$_REQUEST['model'];
	} elseif(isset($_REQUEST['brand'])) {
		$sql = "UPDATE endpointman_brand_list SET enabled = 1 WHERE id = ".$_REQUEST['model'];
	}
	mysql_query($sql);
} elseif(isset($_REQUEST['button_uninstall'])) {
	if(isset($_REQUEST['product'])) {
		$endpoint->remove_product($_REQUEST['product']);
	} elseif(isset($_REQUEST['brand'])) {
		$endpoint->remove_brand($_REQUEST['brand']);
	}
} elseif(isset($_REQUEST['button_check_for_updates'])) {
	$brand_up = $endpoint->brand_update_check();
	$check_for_updates = TRUE;
}

$sql="SELECT * from endpointman_brand_list WHERE id > 0 AND hidden = 0 ORDER BY id ASC ";
$result=mysql_query($sql);
$i = 0;
$note = "";
while($row = mysql_fetch_assoc($result)) {
	$row_out[$i] = $row;
	$row_out[$i]['count'] = $i;
	if($check_for_updates) {
		$id = $endpoint->arraysearchrecursive($row['name'], $brand_up,'brand_name');
		$id = $id[0];
		if((isset($brand_up[$id]['update'])) AND ($row['installed'] == 1)) {
			$row_out[$i]['update'] = $brand_up[$id]['update'];
			$endpoint->add_freepbx_notification("PU_".$brand_up[$id]['brand_name'], '', "Brand \'".$brand_up[$id]['brand_name']."\' has updates available", "Old Version: ".$row['cfg_ver']." New Version: ".$brand_up[$id]['version']."<br/>"."Changes: ". $brand_up[$id]['changes'].$note, "");
		} else {
			$row_out[$i]['update'] = NULL;
		}
		if(isset($brand_up[$id]['update_vers'])) {
			$row_out[$i]['update_vers'] = $brand_up[$id]['update_vers'];
		} else {
			$row_out[$i]['update_vers'] = NULL;
		}
	}
	if($row['installed']){
		$j = 0;
		$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND brand = '.$row['id'].' ORDER BY long_name ASC';
		$result2=mysql_query($sql);
		while($row2 = mysql_fetch_assoc($result2)) {
			$row_out[$i]['products'][$j] = $row2;
			if($row_out[$i]['products'][$j]['installed']){
				$row_out[$i]['products'][$j]['installed_checked'] = 'checked';
			}
			if($check_for_updates) {
				if(($temp = $endpoint->product_update_check($row2['id'])) AND ($row2['installed'] == 1)) {
					$row_out[$i]['products'][$j]['update'] = 1;
					$row_out[$i]['products'][$j]['update_vers'] = $temp['data']['version'];
					$row_out[$i]['products'][$j]['update_desc'] = $temp['data']['description'];
					$endpoint->add_freepbx_notification("PU_".$row2['long_name'], '', "There is a Product update for ".$row2['long_name'], "Old Version: ".$row2['cfg_ver']." New Version: ".$temp['data']['version']."<br/>"."Changes: ". $temp['data']['description'].$note, "");
				} else {
					$row_out[$i]['products'][$j]['update'] = 0;
				}
				if(($temp = $endpoint->firmware_update_check($row2['id'])) AND (array_key_exists('firmware_vers', $row)) AND ($row['firmware_vers'] > 0)) {
					$row_out[$i]['products'][$j]['update_fw'] = 1;
					$row_out[$i]['products'][$j]['update_vers_fw'] = $temp['data']['version'];
					$endpoint->add_freepbx_notification("PU_".$row2['long_name'], '', "There is a firmware update for phone module ".$row2['long_name'], "Old Version: ".$row2['firmware_vers']." New Version: ".$temp['data']['version'].$note, "");	
				} else {
					$row_out[$i]['products'][$j]['update_fw'] = 0;
				}
				$xml_data = $row2['xml_data'];
			}
			
			$row_out[$i]['products'][$j]['fw_type'] = $endpoint->firmware_local_check($row2['id']);


			if($row2['installed']) {
				$sql = 'SELECT * FROM endpointman_model_list WHERE hidden = 0 AND product_id = '.$row2['id'];
				$result3=mysql_query($sql);
				$k = 0;
				while($row3 = mysql_fetch_assoc($result3)) {
					$row_out[$i]['products'][$j]['models'][$k] = $row3;
					if($row_out[$i]['products'][$j]['models'][$k]['enabled']){
						$row_out[$i]['products'][$j]['models'][$k]['enabled_checked'] = 'checked';
					}
					$k++;
				}
			}
			$j++;
		}
	}
	$i++;
}

//initialize a Rain TPL object
$tpl = new RainTPL( LOCAL_PATH.'templates' );

if (!isset($error)) {
	$error = "";
}
if (!isset($_POST['brand'])) {
	$brand = NULL;
} else {
	$brand = $_POST['brand'];
}
if (isset($installer)) {
	$data = base64_encode(serialize($installer));
	$amp_serial = base64_encode(serialize($amp_conf));
	$temp_amp['data'] = $data;
	$temp_amp['amp_serial'] = $amp_serial;
	$temp_amp = base64_encode(serialize($temp_amp));

	$outfile=LOCAL_PATH."/includes/amp.ini";
	$wfh=fopen($outfile,'w');
	fwrite($wfh, $temp_amp);
	fclose($wfh);
	$tpl->assign("installer_address", $_SERVER["HTTP_HOST"].WEB_PATH."includes/installer.php");
}

global $debug, $global_cfg;
if($global_cfg['debug']) {
	if(file_exists(LOCAL_PATH."debug_installer.log")) {
		$outfile=LOCAL_PATH."debug_installer.log";
		$wfh=fopen($outfile,'r');
		$contents = fread($wfh, filesize($outfile));
		fclose($wfh);
		unlink(LOCAL_PATH."debug_installer.log");
	} else {
		$contents = "";
	}
	$debug .= $contents;
	$tpl->assign("debug", $debug);
}
$tpl->assign("error", $error);
$tpl->assign("web_vars", WEB_VARS);
$tpl->assign("brand2_list", $row_out);
//draw the template	
echo $tpl->draw( 'brand_model_manager' );
?>