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
/*
Functions get_files_list, get_url_contents and html2text written by Jeremy Jacobs
Fitness Plus Equipment Data Sources, Inc.
http://www.FitnessRepairParts.com
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
*/
/*
Functions download_file_with_progress_bar', 'endpointmanager_read_header, 'endpointmanager_read_body' are Borrowed from pfSense
Authors of said functions: Keyvan Minoukadeh, Modified by Scott Ullrich
License for pfSense is as follows:

pfSense is Copyright 2004-2009 BSD Perimeter, LLC
Current logo is Copyright 2005-2009 BSD Perimeter, LLC

pfSense is a federally registered trademark of BSD Perimeter LLC. Any unauthorized use of this trademark is prohibited by state and federal law and international treaties. 

All rights reserved.

Portions from m0n0wall, Copyright 2002-2009 Manuel Kasper. Please refer to source code header for exact copyright information.

Source Code
pfSense source code is released under the BSD license, under terms as follows.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
class endpointmanager {
	//Load this class upon construction of the class
    function __construct() {
		global $amp_conf,$global_cfg, $endpoint_db; $type;
		//attached to the end of every link so we can flow through FreePBX
		//This is really hacky but I have to do it when people have different directories instead of admin
		if((!array_key_exists('PHONE_MODULES_PATH',$amp_conf)) AND (dirname($_SERVER['SCRIPT_NAME']) != "/recordings")) {
			//Define the location of phone modules, keeping it outside of the module directory so that when the user updates endpointmanager they don't lose all of their phones
			define("PHONE_MODULES_PATH", dirname($_SERVER["SCRIPT_FILENAME"])."/modules/_ep_phone_modules/");
			
			//Get local path information
			define("WEB_PATH", dirname($_SERVER['SCRIPT_NAME'])."/modules/endpointman/");
			define("LOCAL_PATH", dirname($_SERVER["SCRIPT_FILENAME"])."/modules/endpointman/");				
			
			//have to hack around freepbx....
			$amp_conf['PHONE_MODULES_PATH'] = PHONE_MODULES_PATH;
			$amp_conf['LOCAL_ENDPOINT_PATH'] = LOCAL_PATH;
		} elseif(dirname($_SERVER['SCRIPT_NAME']) == "/recordings") {
			//Define the location of phone modules, keeping it outside of the module directory so that when the user updates endpointmanager they don't lose all of their phones
			define("PHONE_MODULES_PATH", $amp_conf['AMPWEBROOT']."/admin/modules/_ep_phone_modules/");
			
			//Get local path information
			define("WEB_PATH", "/admin/modules/endpointman/");
			define("LOCAL_PATH", $amp_conf['AMPWEBROOT']."/admin/modules/endpointman/");				
			
			//have to hack around freepbx....
			$amp_conf['PHONE_MODULES_PATH'] = PHONE_MODULES_PATH;
			$amp_conf['LOCAL_ENDPOINT_PATH'] = LOCAL_PATH;
		} else {
			define("PHONE_MODULES_PATH", $amp_conf['PHONE_MODULES_PATH']);
			define("LOCAL_PATH", $amp_conf['LOCAL_ENDPOINT_PATH']);		
		}
				
		//Start Database Connections and keep them running this whoooooooole time
		$this->dblogin(); 

		//Set Global Variables
		if($this->table_exists("endpointman_global_vars")) {
			$result = mysql_query("SELECT var_name, value FROM endpointman_global_vars");
			while ($row=mysql_fetch_array($result)) {
				$global_cfg[$row['var_name']] = $row['value'];
			}
		
			define("UPDATE_PATH", $global_cfg['update_server']);
			define("VER", $global_cfg['version']);
		}
		
		//Define error reporting
		if($global_cfg['debug']) {
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		} else {
			ini_set('display_errors', 0);
		}
		//include the local template class
		include LOCAL_PATH."includes/rain.tpl.class.php";
	}
	
	function add_freepbx_notification($id, $type, $display_text, $text, $link) {
		$sql = "INSERT INTO notifications (module, id, level, display_text, link, candelete, timestamp, extended_text) VALUES ('endpointman', '".$id."', '500', '".$display_text."', '".$link."', '1', '".time()."', '".$text."')";
		mysql_query($sql);
	}
	
	//Send this function an ID from the mac devices list table and you'll get all the information we have on that particular phone
	function get_phone_info($mac_id=NULL) {
		//You could screw up a phone if the mac_id is blank
		if (!isset($mac_id)) {
			die('Can\'t get phone info!');
		}
		$sql = "SELECT id,mac,model FROM  endpointman_mac_list WHERE model > 0 AND id =".$mac_id;
		$result=mysql_query($sql);
		if(mysql_num_rows($result)) {
			//Returns Brand Name, Brand Directory, Model Name, Mac Address, Extension (FreePBX), Custom Configuration Template, Custom Configuration Data, Product Name, Product ID, Product Configuration Directory, Product Configuration Version, Product XML name,
			$result=mysql_query("SELECT endpointman_mac_list.config_files_override, endpointman_mac_list.user_cfg_data, endpointman_model_list.id as model_id, endpointman_brand_list.id as brand_id, endpointman_brand_list.name, endpointman_brand_list.directory, endpointman_model_list.model, endpointman_mac_list.mac, endpointman_mac_list.ext, endpointman_mac_list.custom_cfg_template, endpointman_mac_list.custom_cfg_data, endpointman_product_list.long_name, endpointman_product_list.id as product_id, endpointman_product_list.cfg_dir, endpointman_product_list.cfg_ver, endpointman_product_list.xml_data, endpointman_product_list.cfg_data,  endpointman_product_list.installed, endpointman_model_list.enabled, users.name as description, sip.data as secret FROM endpointman_mac_list, endpointman_model_list, endpointman_brand_list, endpointman_product_list, sip, users WHERE endpointman_mac_list.id = ".$mac_id." AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_brand_list.id = endpointman_model_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.ext = sip.id AND sip.keyword = 'secret' AND endpointman_mac_list.ext = users.extension");
			$phone_info=mysql_fetch_array($result);

			//If there is a template associated with this phone then pull that information and put it into the array
			if ($phone_info['custom_cfg_template'] > 0) {
				$result=mysql_query("SELECT name, custom_cfg_data, config_files_override FROM endpointman_template_list WHERE id = ".$phone_info['custom_cfg_template']);
				$phone_info['template_data'] = mysql_fetch_array($result);
	
			}
		} else {
			//Phone is unknown, we need to display this to the end user so that they can make corrections
			$row=mysql_fetch_array($result);
			$phone_info['id'] = $mac_id;
			$phone_info['model_id'] = 0;
			$phone_info['brand_id'] = 0;
			$phone_info['ext'] = $row['ext'];
			$phone_info['mac'] = $row['mac'];
			$phone_info['name'] = 'Unknown';
		}
		return $phone_info;
	}
	
	//Open configuration files and return the data from the file
	function open_config_file($infile,$phone_info,$config_files_override){
		//Sometimes periods turn to underscores when periods are submitted through a form as a variable, we need to account for this
		$key=str_replace('.', "_",$infile);
		//from phone info open the required file for reading
		$infile=PHONE_MODULES_PATH.$phone_info['directory']."/".$phone_info['cfg_dir']."/".$infile;
		//if there is no configuration file over ridding the default then load up $contents with the file's information, where $key is the name of the default configuration file
		if(!isset($config_files_override[$key])) {
			//always use 'rb' says php.net
			$handle = fopen($infile, "rb");
			$contents = fread($handle, filesize($infile));
			fclose($handle);
		} else {
			//$config_files_override has data in it so that means we have a configuration file over ride, lets pull the text out of the database
			$sql = 'SELECT data FROM endpointman_custom_configs WHERE id = ' . $config_files_override[$key];
			$result = mysql_query($sql);
			//$config_files_override said we had a file in the database but mySQL couldn't find anything so we default back to the local file
			//This usually happens when the user deletes a custome configuration file but doesn't update their templates
			if(mysql_num_rows($result) == 0) {
				$handle = fopen($infile, "rb");
				$contents = fread($handle, filesize($infile));
				fclose($handle);
			} else {
				$data = mysql_fetch_assoc($result);
				$contents = $data['data'];
			}
		}
		return($contents);
	}
	
	//Write configuration files
	function write_config_file($outfile,$phone_info,$contents,$custom_cfg_data){
		global $global_cfg;
		//Get End User (through ARI) set variables
		$user_cfg_data = unserialize($phone_info['user_cfg_data']);
		
		//Determine if ARI is enabled, if not ignore any values present in $user_cfg_data, keep unknown variables, this is what 'TRUE' means
		if(($global_cfg['enable_ari']) AND is_array($user_cfg_data)){
			$contents = $this->parse_config_values($user_cfg_data,$contents,TRUE,$phone_info);
		}
		
		//Parse normal config values and set all unknown variables in the file to nothing (Blanking them out), this is what 'FALSE' means
		$contents = $this->parse_config_values($custom_cfg_data,$contents,FALSE,$phone_info);
		
		$outfile=$global_cfg['config_location'].$outfile;
		$wfh=fopen($outfile,'w');
		fwrite($wfh,$contents);
		fclose($wfh);
	}

	/*
	-Send this function the contents of a text file to $file_contents and then send an array to $custom_cfg_data
	The key of the array is the name of the variable in the text file between "{$" and "}"
	Then return the contents of the file with the values merged into it
	-The $keep_unknown variable tells this function to ignore(TRUE) or blank out(FALSE) variables that it finds in $file_contents but can not find in $variables_array
	*/
	function parse_config_values($variables_array,$file_contents,$keep_unknown=FALSE, $phone_info=NULL) {
		global $global_cfg;
		//Find all matched variables in the text file between "{$" and "}"
		preg_match_all('/[{\$](.*?)[}]/i',$file_contents,$match);
		//Result without brackets (but with the $ variable identifier)
		$no_brackets = array_values(array_unique($match[1]));
		//Result with brackets
		$brackets = array_values(array_unique($match[0]));
		//Count how many variables were found in the file
		$count = count($brackets);
		$cfg_data = unserialize($phone_info['cfg_data']);
		
		//loop though each variable found in the text file
		for($i=0;$i<$count;$i++) {
			$key = str_replace('$','',$no_brackets[$i]);
			//If the variable we found in the text file exists in the variables array then replace the variable in the text file with the value under our key
			if (isset($variables_array[$key])) {
				$variables_array[$key]['value'] = htmlspecialchars($variables_array[$key]['value']);
				//Variables the user defined in their custom configs
				if(isset($phone_info)) {
					$variables_array[$key]['value']=str_replace('{$srvip}', $global_cfg['srvip'], $variables_array[$key]['value']);
					$variables_array[$key]['value']=str_replace('{$mac}', $phone_info['mac'], $variables_array[$key]['value']);
					$variables_array[$key]['value']=str_replace('{$ext}', $phone_info['ext'], $variables_array[$key]['value']);
					$variables_array[$key]['value']=str_replace('{$displayname}', $phone_info['description'], $variables_array[$key]['value']);
				}
				$file_contents=str_replace($brackets[$i], $variables_array[$key]['value'],$file_contents);
			} else {
				if(!$keep_unknown) {
					//read default template values here, blank unknowns or arrays (which are blanks anyways)
					$key1 = $this->arraysearchrecursive($no_brackets[$i], $cfg_data, 'variable');
					if(isset($phone_info)) {
						$file_contents=str_replace('{$srvip}', $global_cfg['srvip'], $file_contents);
						$file_contents=str_replace('{$mac}', $phone_info['mac'], $file_contents);
						$file_contents=str_replace('{$ext}', $phone_info['ext'], $file_contents);
						$file_contents=str_replace('{$displayname}', $phone_info['description'], $file_contents);
					}
					if((isset($cfg_data[$key1[0]]['default_value'])) AND (!is_array($cfg_data[$key1[0]]['default_value']))) {
						$file_contents=str_replace($brackets[$i], $cfg_data[$key1[0]]['default_value'],$file_contents);
					} else {
						$file_contents=str_replace($brackets[$i], "",$file_contents);
					}
				}
			}
		}
		
		if($global_cfg['debug']) {
			global $debug;
			$list = print_r($brackets, TRUE);
			$debug .= "\nVariables found in file:\n".$list;
		}
		
		return $file_contents;
	}

	//Custom Means specific to that MAC
	//id is either the mac ID (not address) or the template ID
	function edit_template_display($id, $custom) {
		global $global_cfg,$debug;
		$alt_configs = NULL;
		//Determine if we are dealing with a general template or a specific [for that phone only] template (custom =0 means general)
		if($custom == 0) {
			$sql = "SELECT endpointman_product_list.config_files, endpointman_template_list.*, endpointman_product_list.id as product_id, endpointman_product_list.long_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_template_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_template_list.id = ".$id." AND endpointman_template_list.product_id = endpointman_product_list.id AND endpointman_product_list.brand = endpointman_brand_list.id";
		} else {
			$sql = "SELECT endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_product_list.id as product_id, endpointman_product_list.long_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE endpointman_mac_list.id=".$id." AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
		}
		$result=mysql_query($sql);
		$row=mysql_fetch_array($result);
				
		//Make sure the model data from the local confg files are stored in the database and vice-versa. Serious errors will occur if the database is not in sync with the local file
		if(!$this->sync_product($row['product_id'])) {
			die("unable to sync local template files". $custom);
		}
		
		//Get default template config data from the database for this product
		$sql = "SELECT cfg_data FROM endpointman_product_list WHERE id=". $row['product_id'];
		$result=mysql_query($sql);
		$product_row=mysql_fetch_array($result);		
		
		//inset it into our usable row
		$row['cfg_data'] = $product_row['cfg_data'];
	
		$brand_name = $row['directory'];
		$model_name = $row['cfg_dir'];
		$tpl = new RainTPL( LOCAL_PATH.'templates' );
		$tpl->assign("template_editor_display", 1);
		
		echo $tpl->draw( 'global_header' );
		//Let the template system know if we are working with a general template or a specific [for that phone only] template
		$tpl->assign("custom", $custom);
		if($custom)
		{
			$tpl->assign("ext", $row['ext']);
		} else {
			$tpl->assign("template_name", $row['name']);
		}
		$tpl->assign("model", $row['long_name']);

		//Start the display of the html file in the product folder
		if($row['config_files_override'] == "") {
			$config_files_saved = "";
		} else {
			$config_files_saved = unserialize($row['config_files_override']);
		}
		$config_files = explode(",",$row['config_files']);
		$i = 0;
		$alt = 0;
		while($i < count($config_files)) {
			$sql = "SELECT * FROM  endpointman_custom_configs WHERE product_id = ".$row['product_id']." AND original_name = '".$config_files[$i]."'";
			$result = mysql_query($sql);
			if(mysql_num_rows($result) > 0) {
				$alt_configs .= $config_files[$i];
				$alt_configs .= '<select name="'.$config_files[$i].'">';
				$alt_configs .= '<option value="0">'.$config_files[$i].' (Original)</option>';
				$config_files[$i] = str_replace(".","_",$config_files[$i]);
				while ($ccf=mysql_fetch_array($result)) {
					$alt_configs .= '<option value="'.$ccf['id'].'" ';
					$cf_key = $config_files[$i];
					if((is_array($config_files_saved)) AND ($config_files_saved[$cf_key] == $ccf['id'])) {
						$alt_configs .= 'selected';
					}
					$alt_configs .= '>'.$ccf['name'].'</option>';
				}
				$alt_configs .= "</select>";
				$alt = 1;
			}
			$i++;			
		}
		$tpl->assign("alt_configs", $alt_configs);
		$tpl->assign("alt", $alt);
		
		if($row['cfg_data'] != "") {
			$out = $this->generate_gui_html($row['cfg_data'],$row['custom_cfg_data'],TRUE);
		} else {
			echo "No Template Data has been defined for this Product<br />";
		}
		
		$tpl->assign("template_editor", $out);
		$tpl->assign("hidden_id", $row['id']);
		$tpl->assign("hidden_custom", $custom);
		echo $tpl->draw( 'template_editor' );

		$tpl->assign("debug", $debug);
	
	}

	function generate_gui_html($cfg_data,$custom_cfg_data=NULL, $admin=FALSE, $user_cfg_data=NULL) {
		global $global_cfg;
		//take the data out of the database and turn it back into an array for use
		$cfg_data = unserialize($cfg_data);
		$count = count($cfg_data);

		//Check to see if there is a custom template for this phone already listed in the endpointman_mac_list database
		if (isset($custom_cfg_data)) {
			$custom_cfg_data = unserialize($custom_cfg_data);
		} else {
			//No custom template so let's pull the default values for this model into the custom_cfg_data array and populate it from there so that we won't have to make two completely different functions below
			for($i=0;$i<$count;$i++) {
				$key_default = str_replace('$','',$cfg_data[$i]['variable']);
				if(!is_array($cfg_data[$i]['default_value'])) {
					$custom_cfg_data[$key_default]['value'] = $cfg_data[$i]['default_value'];

				}
			}
		}
		if(isset($user_cfg_data)) {
			$user_cfg_data = unserialize($user_cfg_data);
		}
		
		$template_variables_array = array();
		
		$group_count = 0;
		//Fill the html form data with values from either the database or the default values to display to the end user
		for($i=0;$i<$count;$i++) {
			if(array_key_exists('variable',$cfg_data[$i])) {
				$key = str_replace('$','',$cfg_data[$i]['variable']);
			} else {
				$key = "";
			}			
			if(($admin) OR (isset($custom_cfg_data[$key]['ari']))) {
				//Checks to see if values are defined in the database, if not then we assume this is a new option and we need a default value here!
				if(!isset($custom_cfg_data[$key]['value'])) {
					//xml2array will take values that have no data and turn them into arrays, we want to avoid the word 'array' as a default value, so we blank it out here if we are an array
					if((array_key_exists('default_value',$cfg_data[$i])) AND (is_array($cfg_data[$i]['default_value']))) {
						$custom_cfg_data[$key]['value'] = "";
					} elseif((array_key_exists('default_value',$cfg_data[$i])) AND (!is_array($cfg_data[$i]['default_value']))) {
						$custom_cfg_data[$key]['value'] = $cfg_data[$i]['default_value'];
					}
				}
				if ($cfg_data[$i]['type'] == "group") {
					$group_count++;
					$template_variables_array[$group_count]['title'] = $cfg_data[$i]['description'];
					$variables_count = 0;
				} elseif ($cfg_data[$i]['type'] == "input") {
					if((!$admin) && (isset($user_cfg_data[$key]['value']))) {
						$custom_cfg_data[$key]['value'] = $user_cfg_data[$key]['value'];
					}
					$template_variables_array[$group_count]['data'][$variables_count]['type'] = "input";
					$template_variables_array[$group_count]['data'][$variables_count]['key'] = $key;
					$template_variables_array[$group_count]['data'][$variables_count]['value'] = $custom_cfg_data[$key]['value'];
					$template_variables_array[$group_count]['data'][$variables_count]['description'] = $cfg_data[$i]['description'];
				} elseif ($cfg_data[$i]['type'] == "radio") {
					if((!$admin) && (isset($user_cfg_data[$key]['value']))) {
						$custom_cfg_data[$key]['value'] = $user_cfg_data[$key]['value'];
					}
					$num = $custom_cfg_data[$key]['value'];	
					$template_variables_array[$group_count]['data'][$variables_count]['type'] = "radio";
					$template_variables_array[$group_count]['data'][$variables_count]['key'] = $key;
					$template_variables_array[$group_count]['data'][$variables_count]['description'] = $cfg_data[$i]['description'];
					$z = 0;
					while($z < count($cfg_data[$i]['data'])) {
						$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['key'] = $key;
						$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['value'] = $cfg_data[$i]['data'][$z]['value'];
						$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['description'] = $cfg_data[$i]['data'][$z]['text'];
						if ($cfg_data[$i]['data'][$z]['value'] == $num) {
							$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['checked'] = 'checked';
						}
						$z++;
					}
				} elseif ($cfg_data[$i]['type'] == "list") {
					if((!$admin) && (isset($user_cfg_data[$key]['value']))) {
						$custom_cfg_data[$key]['value'] = $user_cfg_data[$key]['value'];
					}
					$num = $custom_cfg_data[$key]['value'];
					$template_variables_array[$group_count]['data'][$variables_count]['type'] = "list";
					$template_variables_array[$group_count]['data'][$variables_count]['key'] = $key;
					$template_variables_array[$group_count]['data'][$variables_count]['description'] = $cfg_data[$i]['description'];
					$z = 0;
					while($z < count($cfg_data[$i]['data'])) {
						$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['value'] = $cfg_data[$i]['data'][$z]['value'];
						$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['description'] = $cfg_data[$i]['data'][$z]['text'];
						if ($cfg_data[$i]['data'][$z]['value'] == $num) {
							$template_variables_array[$group_count]['data'][$variables_count]['data'][$z]['selected'] = 'selected';
						}
						$z++;
					}
				} elseif ($cfg_data[$i]['type'] == "break") {
					$template_variables_array[$group_count]['data'][$variables_count]['type'] = "break";
				}
				if(($global_cfg['enable_ari']) AND ($admin) AND ($cfg_data[$i]['type'] != "break") AND ($cfg_data[$i]['type'] != "group")) {
					$template_variables_array[$group_count]['data'][$variables_count]['aried'] = 1;
					$template_variables_array[$group_count]['data'][$variables_count]['ari']['key'] = $key;
					if(isset($custom_cfg_data[$key]['ari'])) {
						$template_variables_array[$group_count]['data'][$variables_count]['ari']['checked'] = "checked";
					}
				}
				$variables_count++;
			}
		}
		return($template_variables_array);
	}

	function save_template($id, $custom, $variables) {
		//Custom Means specific to that MAC
		if($custom == "0") {
			$sql = "SELECT endpointman_product_list.config_files, endpointman_template_list.*, endpointman_product_list.long_name, endpointman_product_list.cfg_data, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_template_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_template_list.id = ".$id." AND endpointman_template_list.product_id = endpointman_product_list.id AND endpointman_product_list.brand = endpointman_brand_list.id";
		} else {
			$sql = "SELECT endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_product_list.id as product_id, endpointman_product_list.long_name, endpointman_product_list.cfg_data, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE endpointman_mac_list.id=".$id." AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
		}
		//Load template data
		$result=mysql_query($sql);
		$row=mysql_fetch_array($result);
		$cfg_data = unserialize($row['cfg_data']);
		$count = count($cfg_data);
		for($i=0;$i<$count;$i++) {
			if(array_key_exists('variable',$cfg_data[$i])) {
				$temping = str_replace('$','',$cfg_data[$i]['variable']);
				$temping_ari = "ari_" . $temping;
				if(array_key_exists($temping, $_REQUEST)) {
					$custom_cfg_data[$temping]['value'] = $_REQUEST[$temping];
					if(array_key_exists($temping_ari, $_REQUEST)) {
						if($_REQUEST[$temping_ari] == "on") {
							$custom_cfg_data[$temping]['ari'] = 1;
						}
					}
				}
			}
		}
		
		$config_files = explode(",",$row['config_files']);
		$i = 0;
		while($i < count($config_files)) {
			$config_files[$i] = str_replace(".","_",$config_files[$i]);
			if(isset($_REQUEST[$config_files[$i]])) {
				if($_REQUEST[$config_files[$i]] > 0) {
					$config_files_selected[$config_files[$i]] = $_REQUEST[$config_files[$i]];
				}
			}
			$i++;
		}
		if(!isset($config_files_selected)) {
			$config_files_selected = "";
		} else {
			$config_files_selected = serialize($config_files_selected);
		}
		$save = serialize($custom_cfg_data);
	
		if($custom == "0") {
			$sql = 'UPDATE endpointman_template_list SET config_files_override = \''.addslashes($config_files_selected).'\', custom_cfg_data = \''.addslashes($save).'\' WHERE id ='.$id;
			$location = "template_manager";
		} else {
			$sql = 'UPDATE endpointman_mac_list SET config_files_override = \''.addslashes($config_files_selected).'\', custom_cfg_template = 0, custom_cfg_data = \''.addslashes($save).'\' WHERE id ='.$id;
			$location = "devices_manager";
		}
		$result=mysql_query($sql);
		
		
		require(PHONE_MODULES_PATH .$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');

		$phone_config = new $row['cfg_dir']();
		if($custom != 0) {
			$phone_config->generate_config($id);
		} else {
			$sql = 'SELECT id FROM endpointman_mac_list WHERE custom_cfg_template = '.$id;
			$result=mysql_query($sql);
			while($row=mysql_fetch_array($result)) {
				$phone_config->generate_config($row['id']);
			}
		}
		return($location);
		
	}
	
	function firmware_update_check($id=NULL) {
		$sql = "SELECT * FROM  endpointman_product_list WHERE  id =". $id;
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
	
		$sql = "SELECT directory FROM  endpointman_brand_list WHERE id =". $row['brand'];
		$result = mysql_query($sql);
		if(mysql_num_rows($result)) {
			$brand_row = mysql_fetch_array($result);
		
			//config drive unknown!
			if ($row['cfg_dir'] == "") {	
				return FALSE;
			} else {
				$temp = $this->xml2array(PHONE_MODULES_PATH.$brand_row['directory']."/".$row['cfg_dir']."/template_data.xml");
				if((array_key_exists('data',$temp)) AND (!is_array($temp['data']['firmware_ver']))) {
					if($row['firmware_vers'] < $temp['data']['firmware_ver']) {	
						return $temp;
					} else {
						return FALSE;
					}
				} else {
					return FALSE;
				}
			}
		} else {
			return FALSE;
		}
	}
	
	function firmware_local_check($id=NULL) {
		$sql = "SELECT * FROM  endpointman_product_list WHERE installed = 1 AND id =". $id;
		$result = mysql_query($sql);
		if(mysql_num_rows($result)) {
			$row = mysql_fetch_array($result);
	
			$sql = "SELECT directory FROM  endpointman_brand_list WHERE installed = 1 AND id =". $row['brand'];
			$result = mysql_query($sql);
			$brand_row = mysql_fetch_array($result);
		
			//config drive unknown!
			if ($row['cfg_dir'] == "") {	
				return("nothing");
			} else {
				$temp = $this->xml2array(PHONE_MODULES_PATH.$brand_row['directory']."/".$row['cfg_dir']."/template_data.xml");
				if(!is_array($temp['data']['firmware_ver'])) {		
					if($row['firmware_vers'] == "") {			
						return("install");
					} else {
						return("remove");
					}
				} else {
					return("nothing");
				}
			}
		} else {
			return("nothing");
		}
	}
	
	function remove_firmware($id=NULL) {
		global $global_cfg;
		$sql = "SELECT * FROM  endpointman_product_list WHERE  id =". $id;
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
	
		$file_list = explode(",",$row['firmware_files']);
		$i = 0;
		while($i < count($file_list)){
			unlink($global_cfg['config_location'].$file_list[$i]);
			$i++;
		}
		$sql = 'UPDATE endpointman_product_list SET firmware_files = "", firmware_vers = "" WHERE id = '.$id;
		mysql_query($sql);
	}
	
	function install_firmware($product_id) {
		global $global_cfg, $debug;
		$sql = 'SELECT endpointman_product_list.*, endpointman_brand_list.directory FROM endpointman_product_list, endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '.$product_id;
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		$temp = $this->xml2array(PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/template_data.xml");
		if($temp['data']['firmware_ver'] > $row['firmware_vers']) {
			echo "<script>$('div.demo-container').text('Installing firmware');</script>";
			$this->download_file_with_progress_bar(UPDATE_PATH . $temp['data']['firmware_pkg'], PHONE_MODULES_PATH."temp/".$temp['data']['firmware_pkg']);
			global $global_cfg, $endpoint;
			if(!file_exists(PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/firmware")) {mkdir(PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/firmware");}
			exec("unzip ".PHONE_MODULES_PATH."temp/" . $temp['data']['firmware_pkg'] ." -d ".PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/firmware/", $debug_a = array());
			if ($handle = opendir(PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/firmware/")) {
			    while (false !== ($file = readdir($handle))) {
			        if ($file != "." && $file != "..") {
			            $list .= $file.",";
			        }
			    }
			    closedir($handle);
			}
			$list = substr_replace($list ,"",-1);
			exec("cp -r " . PHONE_MODULES_PATH.$row['directory']."/".$row['cfg_dir']."/firmware/*.* ". $global_cfg['config_location'], $debug_a2 = array());
			$sql = "UPDATE endpointman_product_list SET firmware_vers = '".$temp['data']['firmware_ver']."', firmware_files = '".$list."' WHERE id = ". $row['id'];
			mysql_query($sql);
			echo "<script>$('div.demo-container').text('Done!');</script>";
		} else {
			echo "<script>$('div.demo-container').text('Your Firmware is already up to date');</script>";
			
		}
		if($global_cfg['debug']) {
			$debug = array_merge($debug_a, $debug_a2);
			$outfile=LOCAL_PATH."debug_installer.log";
			$wfh=fopen($outfile,'a');
			fwrite($wfh,print_r($debug, TRUE));
			fclose($wfh);
		}
	}
	
	
	//Check for new packges for brands. These packages will include phone models and such which the user can remove if they want
	function brand_update_check() {
		$location = UPDATE_PATH . "brands.xml";
		$temp = $this->xml2array($location);
		
		if(!isset($temp['data']['brands'][0])) {
			$temp2['data']['brands'][0] = $temp['data']['brands'];
			$temp = $temp2;
		}
		
		$out = $temp['data']['brands'];
		$count = count($out);

		$sql = "SELECT * FROM  endpointman_brand_list WHERE id > 0";
		$result = mysql_query($sql);	
		while($row = mysql_fetch_assoc($result)){
			$key = $this->arraysearchrecursive($row['directory'], $out, 'directory');
			if($key === FALSE) {
				$this->remove_brand($row['id']);
			}
			
			$key = $key[0];
			
			if($row['cfg_ver'] < $out[$key]['version']) {
				$out[$key]['update'] = 1;
				$out[$key]['update_vers'] = $out[$key]['version'];
			} else {
				$out[$key]['update'] = NULL;
			}
			
		}
	
		for($i=0;$i<$count;$i++){
			if(!isset($out[$i]['update'])) {
				//insert row
				$sql = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver) VALUES ('".$out[$i]['brand_id']."', '".$out[$i]['brand_name']."', '".$out[$i]['directory']."', '".$out[$i]['version']."')";
				mysql_query($sql);
			}
		}

		return $out;
	}
	
	//This will update AND install brands!
	//id is brand id
	function update_brand($id = NULL) {
		//Get the brands run down from the update server
		$temp = $this->xml2array(UPDATE_PATH . "brands.xml");

		$key = $this->arraysearchrecursive($id, $temp['data']['brands'],'brand_id');
		
		
		$key = $key[0];
		
		if(!isset($temp['data']['brands'][$key]['product_list'][0])) {
			$temp2 = $temp['data']['brands'][$key]['product_list'];
			unset($temp['data']['brands'][$key]['product_list']);
			$temp['data']['brands'][$key]['product_list'][0] = $temp2;
		}
		
		//Count how many products are avalible for this brand
		$count2 = count($temp['data']['brands'][$key]['product_list']);
		
		//Get the name of the brand (Grandstream, Polycom, etc)
		$brand_name = $temp['data']['brands'][$key]['brand_name'];
		
		//Though we already have the brand ID lets just pull it again to be sure
		$brand_id = $temp['data']['brands'][$key]['brand_id'];
	
		//Brand directory listing. We'll use this to put the generic files into place
		$brand_directory = $temp['data']['brands'][$key]['directory'];
		
		//Get the brand's config version
		$cfg_ver = $temp['data']['brands'][$key]['version'];
		
		//Update config version to the newest number
		$sql = "UPDATE endpointman_brand_list SET name = '".$brand_name."', cfg_ver = '".$cfg_ver."', installed = 1 WHERE id = ". $brand_id;
		mysql_query($sql);

		//Run through the list of products supported under this brand by us. Then spit these values back out into an array where the array's key is equal to the phone's ID number
		$sql = "SELECT * FROM  endpointman_product_list WHERE  brand = ". $brand_id;
		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			$rid = $row['id'];
			$row_out[$rid] = $row;
		}
		
		for($i=0;$i<$count2;$i++) {
			$id = $temp['data']['brands'][$key]['product_list'][$i]['product_id'];
			$long_name = $temp['data']['brands'][$key]['product_list'][$i]['product_name'];
			$cfg_dir = $temp['data']['brands'][$key]['product_list'][$i]['directory'];
			$xml_data = $temp['data']['brands'][$key]['product_list'][$i]['xml_name'];
			
			if(!isset($row_out[$id])) {
				$sql = "INSERT INTO endpointman_product_list (id, brand, long_name, cfg_dir, xml_data) VALUES ('".$id."','".$brand_id."','".$long_name."','".$cfg_dir."','".$xml_data."')";
				mysql_query($sql);
			} else {
				$sql = "UPDATE endpointman_product_list SET long_name = '".$long_name."', cfg_dir= '".$cfg_dir."', xml_data='".$xml_data."' WHERE id =". $id;
				mysql_query($sql);
			}
		}
		//Now run through the OUI list and add it in!
		$result = mysql_query("SELECT * FROM  endpointman_oui_list");
		$i = 0;
		$oui_out = array();
		while($row = mysql_fetch_assoc($result)) {
			$oui_out[$i] = $row;
			$i++;
		}

		if(!is_array($temp['data']['brands'][$key]['oui_list']['oui'])) {
			$temp2 = $temp['data']['brands'][$key]['oui_list']['oui'];
			unset($temp['data']['brands'][$key]['oui_list']);
			$temp['data']['brands'][$key]['oui_list']['oui'][0] = $temp2;
		}
		
		$count = count($temp['data']['brands'][$key]['oui_list']['oui']);
		for($i=0;$i < $count;$i++) {
			if(!$this->arraysearchrecursive($temp['data']['brands'][$key]['oui_list']['oui'][$i],$oui_out)) {
				$sql = "INSERT INTO endpointman_oui_list (oui, brand) VALUES ('".$temp['data']['brands'][$key]['oui_list']['oui'][$i]."', '".$brand_id."')";
				mysql_query($sql);
			}
		}
		//Download the corresponding package and install it in the directory listed above
		echo "<script>$('div.demo-container').text('Installing Module....');</script>";
		if(!file_exists(PHONE_MODULES_PATH."temp")) {mkdir(PHONE_MODULES_PATH."temp");}
		if(!file_exists(PHONE_MODULES_PATH.$brand_directory)) {mkdir(PHONE_MODULES_PATH.$brand_directory);}
		echo "<script>$('div.demo-container').text('Downloading Module...');</script>";
		$this->download_file_with_progress_bar(UPDATE_PATH . $temp['data']['brands'][$key]['package'], PHONE_MODULES_PATH."temp/".$temp['data']['brands'][$key]['package']);
		echo "<script>$('div.demo-container').text('Installing Module...');</script>";
		exec("unzip ".PHONE_MODULES_PATH.'temp/'. $temp['data']['brands'][$key]['package'] ." -d ".PHONE_MODULES_PATH."temp/");
		exec("rm -R ".PHONE_MODULES_PATH."temp/__MACOSX");
		unlink(PHONE_MODULES_PATH.'temp/'. $temp['data']['brands'][$key]['package']);
		exec("cp -R ".PHONE_MODULES_PATH."temp/" .$brand_directory . " " . PHONE_MODULES_PATH);
		chmod(PHONE_MODULES_PATH.$brand_directory, 0777);
		$this->deltree(PHONE_MODULES_PATH."temp/" .$brand_directory);
		exec("chmod 777 -R ". PHONE_MODULES_PATH.$brand_directory);
		echo "<script>$('div.demo-container').text('Done!, Please refresh the page to see the installed options.');</script>";
	}
	
	function remove_brand($id=NULL) {
		mysql_query("DELETE FROM endpointman_brand_list WHERE id = ". $id);
	}
	
	function product_update_check($id=NULL) {
		$sql = "SELECT * FROM endpointman_product_list WHERE id =".$id;
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
		//config drive unknown!
		if ($row['cfg_dir'] == "") {	
			return FALSE;
		} else {
			$temp = $this->xml2array(UPDATE_PATH . $row['xml_data']);		
			if($row['cfg_ver'] < $temp['data']['version']) {			
				return $temp;
			} else {
				return FALSE;
			}
		}
	}

	function sync_product($product=NULL) {
		global $global_cfg, $debug;
		$sql = "SELECT * FROM  endpointman_product_list WHERE  id =". $product;
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result);
	
		$sql = "SELECT directory FROM  endpointman_brand_list WHERE id =". $row['brand'];
		$result = mysql_query($sql);
		$brand_row = mysql_fetch_array($result);	
		//config drive unknown!
		if ($row['cfg_dir'] == "") {
			if($global_cfg['debug']) {
				$debug .= "\nFunction sync_product XML Data:\n".$xml;
			}
			return FALSE;
		} else {
			//check for updates in template folder
			$temp = $this->xml2array(PHONE_MODULES_PATH.$brand_row['directory']."/".$row['cfg_dir']."/template_data.xml");
			if (($temp['data']['version'] >= $row['cfg_ver']) OR ($row['cfg_data'] == "")) {
				if(isset($temp['data']['item'])) {
					$cfg_data = serialize($temp['data']['item']);
				} else {
					$cfg_data = NULL;
				}
				$sql = "UPDATE endpointman_product_list SET config_files=\"".addslashes($temp['data']['configuration_files'])."\",cfg_data = \"".addslashes($cfg_data)."\", cfg_ver ='".$temp['data']['version']."' WHERE id = ". $product;
				mysql_query($sql);
				if($global_cfg['debug']) {
					$xml = print_r($temp, TRUE);
					$debug .= "\nFunction sync_product XML Data:\n".$xml;
				}
				return TRUE;
			} else {
				if($global_cfg['debug']) {
					$debug .= "\nFunction sync_product XML Data:Didn't run xml2array, local version less than sql version\n";
				}
				return TRUE;
			}
		}
	}
	
	//id is model id
	function install_product($id = NULL) {
		//Update SQL below to include the brand and then use the brand_xml with the data returned to get new models are remove old ones....etc.
		$sql = "SELECT endpointman_product_list.*, endpointman_brand_list.directory FROM  endpointman_product_list, endpointman_brand_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND  endpointman_product_list.id =". $id;
		$result = mysql_query($sql);
		$row = mysql_fetch_assoc($result);
		$cfg_dir = $row['cfg_dir'];
		$xml_data = $row['xml_data'];
		
		echo "<script>$('div.demo-container').text('Getting XML data...');</script>";
		
		$product_xml = $this->xml2array(UPDATE_PATH . $xml_data);
		$brand_xml = $this->xml2array(UPDATE_PATH . "brands.xml");
		
		$brand_id = $this->arraysearchrecursive($row['directory'], $brand_xml['data']['brands'], 'directory');
		$brand_id = $brand_id[0];
		

		if(!isset($brand_xml['data']['brands'][$brand_id]['product_list'][0])) {
			$temp2 = $brand_xml['data']['brands'][$brand_id]['product_list'];
			unset($brand_xml['data']['brands'][$brand_id]['product_list']);
			$brand_xml['data']['brands'][$brand_id]['product_list'][0] = $temp2;
		}
		
		$product_id = $this->arraysearchrecursive($row['cfg_dir'], $brand_xml['data']['brands'][$brand_id]['product_list'],'directory');
		$product_id = $product_id[0];
		$cfg_ver = $product_xml['data']['version'];
		$product_dir = $brand_xml['data']['brands'][$brand_id]['product_list'][$product_id]['directory'];

		if ($cfg_ver > $row['cfg_ver']) {
			$cfg_pkg = $temp['data']['package'];
			if(!file_exists(PHONE_MODULES_PATH."temp")) {mkdir(PHONE_MODULES_PATH."temp");}
			$this->download_file_with_progress_bar(UPDATE_PATH . $xml_data, PHONE_MODULES_PATH.'temp/'. $xml_data);
			echo "<script>$('div.demo-container').text('Downloading Module...');</script>";
			$this->download_file_with_progress_bar(UPDATE_PATH . $product_xml['data']['package'], PHONE_MODULES_PATH.'temp/'. $product_xml['data']['package']);	
			echo "<script>$('div.demo-container').text('Installing Module...');</script>";
			exec("rm -R ".PHONE_MODULES_PATH.$row['directory']."/" .$product_dir, $debug_a = array());
			exec("unzip ".PHONE_MODULES_PATH.'temp/'. $product_xml['data']['package']." -d ".PHONE_MODULES_PATH."temp/", $debug_a2 = array());
			exec("rm -R ".PHONE_MODULES_PATH."temp/__MACOSX" , $debug_a3 = array());
			unlink(PHONE_MODULES_PATH.'temp/'. $product_xml['data']['package']);			
			exec("cp -R ".PHONE_MODULES_PATH."temp/" .$product_dir . " " . PHONE_MODULES_PATH.$row['directory'], $debug_a4 = array());
			chmod(PHONE_MODULES_PATH.$row['directory']."/".$product_dir, 0777);
			rename(PHONE_MODULES_PATH."temp/" .$xml_data, PHONE_MODULES_PATH.$row['directory']."/".$product_dir."/template_data.xml");
			exec("rm -R ".PHONE_MODULES_PATH."temp/" .$product_dir, $debug_a5 = array());
			unlink(PHONE_MODULES_PATH."temp/" .$xml_data);
			exec("chmod 777 -R ". PHONE_MODULES_PATH.$row['directory']."/". $cfg_dir, $debug_a6 = array());
		}
		
		echo "<script>$('div.demo-container').text('Updating System...');</script>";

			
		$sql = 'SELECT * FROM endpointman_model_list WHERE product_id = '. $id;
		$result = mysql_query($sql);
		$i = 0;
		while($row = mysql_fetch_assoc($result)) {
			$row_o[$i] = $row;
			$i++;
		}
		
		$count = count($brand_xml['data']['brands'][$brand_id]['product_list'][$product_id]['model_list']);

		echo "<script>$('div.demo-container').text('Updating Models...');</script>";

		//Look Through remote values and compare them to local values, adding phones if needed and updating other phones if these exist already in our database
		for($i=0;$i<$count;$i++) {
			$model_name = $brand_xml['data']['brands'][$brand_id]['product_list'][$product_id]['model_list'][$i]['model'];
			$model_id = $brand_xml['data']['brands'][$brand_id]['product_list'][$product_id]['model_list'][$i]['id'];
			$brand_id2 = $brand_xml['data']['brands'][$brand_id]['brand_id'];
			$temping = $this->arraysearchrecursive($model_name, $row_o);
			if($temping === FALSE) {
				$sql = "INSERT INTO endpointman_model_list (id, brand, model, product_id) VALUES ('".$model_id."','".$brand_id2."','".$model_name."','".$id."')";
			} else {
				$sql = "UPDATE endpointman_model_list SET brand = '".$brand_id2."', model= '".$model_name."', product_id='".$id."' WHERE id =". $model_id;
			}
			mysql_query($sql);
		}
		
		//Look through the database and compare local values with remote values, removing those phones that are no longer supported.
		$count = count($row_o);
		for($i=0;$i<$count;$i++) {
			$temping = $this->arraysearchrecursive($row_o['id'], $brand_xml);
			if($temping === FALSE) {
				$sql = "DELETE FROM endpointman_model_list WHERE id = " . $row_o['id'];
				mysql_query("UPDATE endpointman_model_list SET model = 0 WHERE model = " . $row_o['id']);
			}
			mysql_query($sql);
		}
		$sql = "UPDATE endpointman_product_list SET installed = '1', cfg_ver= '".$cfg_ver."' WHERE id = " . $id;
		mysql_query($sql);
		
		echo "<script>$('div.demo-container').text('Syncing System...');</script>";
		
		$this->sync_product($id);
		
		echo "<script>$('div.demo-container').text('Done!, Please refresh the page to see the installed options.');</script>";
		
		if($global_cfg['debug']) {
			unlink(LOCAL_PATH."debug_installer.log");
			$debug = array_merge($debug_a, $debug_a2, $debug_a3, $debug_a4, $debug_a5, $debug_a6);
			$outfile=LOCAL_PATH."debug_installer.log";
			$wfh=fopen($outfile,'a');
			fwrite($wfh,print_r($debug, TRUE));
			fclose($wfh);
		}
	}
	
	function remove_product($id=NULL) {
		$sql = 'SELECT endpointman_model_list.*, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_model_list, endpointman_product_list, endpointman_brand_list WHERE endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = '.$id;

		$result = mysql_query($sql);
		while($row = mysql_fetch_assoc($result)) {
			$sql = "UPDATE endpointman_mac_list SET model = 0 WHERE model = ".$row['id'];
			mysql_query($sql);
			$row_out = $row;
		}
		
		$this->deltree(PHONE_MODULES_PATH.$row_out['directory']."/". $row_out['cfg_dir']);
		$sql = "UPDATE endpointman_product_list SET installed = 0, cfg_ver = '' WHERE id = ".$id;
		mysql_query($sql);
		$sql = "DELETE FROM endpointman_model_list WHERE product_id = ".$id;
		mysql_query($sql);	
	}

	function arraysearchrecursive($Needle,$Haystack,$NeedleKey="",$Strict=false,$Path=array()) {
	  if(!is_array($Haystack))
	    return false;
	  foreach($Haystack as $Key => $Val) {
	    if(is_array($Val)&&
	       $SubPath=$this->arraysearchrecursive($Needle,$Val,$NeedleKey,$Strict,$Path)) {
	      $Path=array_merge($Path,Array($Key),$SubPath);
	      return $Path;
	    }
	    elseif((!$Strict&&$Val==$Needle&&
	            $Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))||
	            ($Strict&&$Val===$Needle&&
	             $Key==(strlen($NeedleKey)>0?$NeedleKey:$Key))) {
	      $Path[]=$Key;
	      return $Path;
	    }
	  }
	  return false;
	}
	//Below is pfSense code
	function download_file_with_progress_bar($url_file, $destination_file) {
		global $ch, $fout, $file_size, $downloaded, $pkg_interface;
		set_time_limit(0);
		$file_size  = 1;
		$downloaded = 1;
		/* open destination file */
		$fout = fopen($destination_file, "wb");

		/*
		 *	Originally by Author: Keyvan Minoukadeh
		 *	Modified by Scott Ullrich to return Content-Length size
	     */

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_file);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'endpointmanager_read_header');
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'endpointmanager_read_body');
		curl_setopt($ch, CURLOPT_NOPROGRESS, '1');
		curl_setopt($ch, CURLOPT_TIMEOUT, 0); 

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($fout)
			fclose($fout);
		curl_close($ch);
		return ($http_code == 200) ? true : $http_code;
	}
	//end pfSense code

	//This function looks in common linux directories for system executable files. Like ARP & NMAP
	function find_exec($exec) {
		$usr_bin = glob("/usr/bin/".$exec);
		$usr_sbin = glob("/usr/sbin/".$exec);
		$sbin = glob("/sbin/".$exec);
		$bin = glob("/bin/".$exec);
		$etc = glob("/etc/".$exec);
		if(isset($usr_bin[0])) {
			return("/usr/bin/".$exec);
		} elseif(isset($usr_sbin[0])) {
			return("/usr/sbin/".$exec);
		} elseif(isset($sbin[0])) {
			return("/sbin/".$exec);
		} elseif(isset($bin[0])) {
			return("/bin/".$exec);
		} elseif(isset($etc[0])) {
			return("/etc/".$exec);
		} else {
			return($exec);
		}
	}

	//Delete Directory Tree, Doesn't work. though
	function deltree($dir) { 
		$this->rmrf($dir);
	}
	
	function rmrf($dir) {
	    foreach (glob($dir) as $file) {
	        if (is_dir($file)) { 
	            $this->rmrf("$file/*");
	            rmdir($file);
	        } else {
	            unlink($file);
	        }
	    }
	}

	
	function table_exists ($table) { 
		$sql = "SHOW TABLES FROM asterisk";
		$result = mysql_query($sql);

		while ($row = mysql_fetch_row($result)) {
			if ($row[0] == $table) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	Parse XML file into an array
	Taken from Sister Project SuperFecta
	*/
	function xml2array($url, $get_attributes = 1, $priority = 'tag')
	{
		$contents = "";
		if (!function_exists('xml_parser_create'))
		{
			return array ();
		}
		$parser = xml_parser_create('');
		if(!($fp = @ fopen($url, 'rb')))
		{
			return array ();
		}
		while(!feof($fp))
		{
			$contents .= fread($fp, 8192);
		}
		fclose($fp);
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);
		if(!$xml_values)
		{
			return; //Hmm...
		}
		$xml_array = array ();
		$parents = array ();
		$opened_tags = array ();
		$arr = array ();
		$current = & $xml_array;
		$repeated_tag_index = array (); 
		foreach ($xml_values as $data)
		{
			unset ($attributes, $value);
			extract($data);
			$result = array ();
			$attributes_data = array ();
			if (isset ($value))
			{
				if($priority == 'tag')
				{
					$result = $value;
				}
				else
				{
					$result['value'] = $value;
				}
			}
			if(isset($attributes) and $get_attributes)
			{
				foreach($attributes as $attr => $val)
				{
					if($priority == 'tag')
					{
						$attributes_data[$attr] = $val;
					}
					else
					{
						$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
					}
				}
			}
			if ($type == "open")
			{ 
				$parent[$level -1] = & $current;
				if(!is_array($current) or (!in_array($tag, array_keys($current))))
				{
					$current[$tag] = $result;
					if($attributes_data)
					{
						$current[$tag . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level] = 1;
					$current = & $current[$tag];
				}
				else
				{
					if (isset ($current[$tag][0]))
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{ 
						$current[$tag] = array($current[$tag],$result); 
						$repeated_tag_index[$tag . '_' . $level] = 2;
						if(isset($current[$tag . '_attr']))
						{
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current = & $current[$tag][$last_item_index];
				}
			}
			else if($type == "complete")
			{
				if(!isset ($current[$tag]))
				{
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if($priority == 'tag' and $attributes_data)
					{
						$current[$tag . '_attr'] = $attributes_data;
					}
				}
				else
				{
					if (isset ($current[$tag][0]) and is_array($current[$tag]))
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						if ($priority == 'tag' and $get_attributes and $attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{
						$current[$tag] = array($current[$tag],$result); 
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes)
						{
							if (isset ($current[$tag . '_attr']))
							{ 
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset ($current[$tag . '_attr']);
							}
							if ($attributes_data)
							{
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
					}
				}
			}
			else if($type == 'close')
			{
				$current = & $parent[$level -1];
			}
		}
		return ($xml_array);
	}

	//This function takes a string and tries to determine if it's a valid mac addess, return FALSE if invalid
	function mac_check_clean($mac) {
		if ((strlen($mac) == "17") OR (strlen($mac) == "12")) {
			//It might be better to use switch here instead of these IF statements...
		
			//Is the mac separated by colons(:)?
			if (preg_match("/[0-9a-f][0-9a-f][:-]".
	        				"[0-9a-f][0-9a-f][:-]".
	        				"[0-9a-f][0-9a-f][:-]".
	        				"[0-9a-f][0-9a-f][:-]".
	        				"[0-9a-f][0-9a-f][:-]".
	        				"[0-9a-f][0-9a-f]/i", $mac)) {
				return(strtoupper(str_replace(":", "", $mac)));
			//Is the string exactly 12 characters?
			} elseif(strlen($mac) == "12") {
				//Now is the string a valid HEX mac address?
				if (preg_match("/[0-9a-f][0-9a-f]".
		        				"[0-9a-f][0-9a-f]".
		        				"[0-9a-f][0-9a-f]".
		        				"[0-9a-f][0-9a-f]".
		        				"[0-9a-f][0-9a-f]".
		        				"[0-9a-f][0-9a-f]/i", $mac)) {
					return(strtoupper($mac));
				} else {
					return(FALSE);
				}
			//Is the mac separated by whitespaces?
			} elseif(preg_match("/[0-9a-f][0-9a-f][\s]".
								"[0-9a-f][0-9a-f][\s]".
								"[0-9a-f][0-9a-f][\s]".
								"[0-9a-f][0-9a-f][\s]".
								"[0-9a-f][0-9a-f][\s]".
								"[0-9a-f][0-9a-f]/i", $mac)) {
				return(strtoupper(str_replace(" ", "", $mac)));
			} else {
				return(FALSE);
			}
		} else {
			return(FALSE);
		}	
	}
	
	function validate_netmask($mask) {
		if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/", $mask)) {
			return(TRUE);
		} else {
			return(FALSE);
		}
	}

	//Discover New Device/Hardware
	function discover_new($netmask, $use_nmap=TRUE) {
		global $global_cfg, $debug, $error;
	    //Written by tm1000
	    //Version 1.0

	    //nmap will actually discover 'unseen' devices that the VoIP server hasn't heard from
	    //If the user just wishes to use the local arp cache they can tell the function to not use nmap
	    //This results in a speed increase from 60 seconds to less than one second.
	    if (($use_nmap) AND ($this->validate_netmask($netmask))) {
	        shell_exec($global_cfg['nmap_location'].' -v -sP '. $netmask);
	    } elseif(!$this->validate_netmask($netmask)) {
			return(FALSE);
		}
	    	//Get arp list
	    	$arp_list = shell_exec($global_cfg['arp_location'] . " -an");
			//$arp_list = shell_exec("arp -an");
    	if($global_cfg['debug']) {
			$debug .= "ARP LIST:\n".$arp_list;
		}
	    //Throw arp list into an array, break by new lines
	    $arp_array = explode("\n", $arp_list);
    
	    //Find all references to active computers by searching out mac addresses.
	    $temp = array_values(array_unique(preg_grep("/[0-9a-f][0-9a-f][:-]".
	        "[0-9a-f][0-9a-f][:-]".
	        "[0-9a-f][0-9a-f][:-]".
	        "[0-9a-f][0-9a-f][:-]".
	        "[0-9a-f][0-9a-f][:-]".
	        "[0-9a-f][0-9a-f]/i", $arp_array)));
    
    	if($global_cfg['debug']) {
			$arp_arry = print_r($temp, true);
			$debug .= "ARP Array:\n".$arp_arry;
		}
	    //Go through each row of valid arp entries and pull out the information and add it into a nice array!
	    foreach ($temp as $key => &$value) {
        
	        //Pull out the IP address from row. It's always the first entry in the row and it can only be a max of 15 characters with the delimiters 
	        $ip = trim(substr($value, 0, 15));
        
	        //Pull out the mac address by looking for the delimiter
	        $mac = substr($value, (strpos($value, ":") -2), 17);
        
	        //Get rid of the delimiter
	        $mac_strip = strtoupper(str_replace(":", "", $mac));
        
			//arp -n will return a MAC address of 000000000000 if no hardware was found, so we need to ignore it
			if($mac_strip != "000000000000") {
		        //only use the first 6 characters for the oui: http://en.wikipedia.org/wiki/Organizationally_Unique_Identifier
		        $oui = substr($mac_strip,0,6);
        
		        //Find the matching brand model to the oui
		        $oui_sql = "SELECT endpointman_brand_list.name, endpointman_brand_list.id FROM endpointman_oui_list, endpointman_brand_list WHERE oui LIKE '%". $oui ."%' AND endpointman_brand_list.id = endpointman_oui_list.brand AND endpointman_brand_list.installed = 1 LIMIT 1";


		        $brand_result = mysql_query($oui_sql);
		        $brand = mysql_fetch_row($brand_result);
		        if ($brand == FALSE) {
		            //oui doesn't have a matching mysql reference, probably a PC/router/wap/printer of some sort.
		            $brand[0] = FALSE;
					$brand[1] = NULL;
		        }
        
		        //Find out if endpoint has already been configured for this mac address
		        $epm_sql = "SELECT * FROM endpointman_mac_list WHERE mac LIKE  '%". $mac_strip ."%'";
		        $epm_result = mysql_query($epm_sql);
		        $epm_row = mysql_fetch_row($epm_result);
		        if ($epm_row == TRUE) {
		            $epm = TRUE;
		        } else {
		            $epm = FALSE;
		        }
        
		        //Add into a final array
		        $final[$key] = array("ip" => $ip, "mac" => $mac, "mac_strip" => $mac_strip, "oui" => $oui, "brand" => $brand[0], "brand_id" => $brand[1], "endpoint_managed" => $epm);
    		}
		}
		$final = array_values($final);
    	if($global_cfg['debug']) {
			$arp_arry = print_r($final, true);
			$debug .= "Final output from 'discover_new' function:\n".$arp_arry;
		}
		if(!is_array($final)) {
			return(FALSE);
		} else {
	    	return ($final);
		}
	}

	//Function Works
	function dblogin() {	//Database login
		global $amp_conf, $db, $endpoint_db, $ep_link;

		/* FUTURE
		if (is_object($db)) {
			//I 'think' we are freepbxv2
			include('databases/freepbxv2.inc');
		} else {
			//Standalone
			include('databases/mysql.inc');
		}
		*/
		
		//Re-written to get rid of duplicate references to one database

		//connect to the mysql system
		$ep_link=mysql_connect('localhost', $amp_conf['AMPDBUSER'], $amp_conf['AMPDBPASS']);
		if ($ep_link == FALSE) {
			die("Sorry, We can't connect to your database :-(\nTry checking /etc/amportal.conf for the correct settings");
		}
		//connect to the only useable asterisk database, need to look this up in the amportal.conf really because someone could change it
		$success = mysql_select_db($amp_conf['AMPDBNAME'],$ep_link);
		if ($success== FALSE) {
			die("Sorry, We can't FIND your database, but we COULD connect to MySQL :-/\nDoes Asterisk & FreePBX exist on this server?");
		}
	} 

	function modelsAvailable($model=NULL, $macAdd=NULL, $brand=NULL) {
		if (isset($macAdd)) {		
			$oui=substr($macAdd,0,6);
		}
		if ((!isset($oui)) && (!isset($brand)) && (!isset($model))) {		
			$sql="SELECT endpointman_model_list.* FROM endpointman_model_list, endpointman_product_list WHERE endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_model_list.enabled = 1 AND endpointman_product_list.installed = 1";
		}elseif(isset($brand)){
			$sql="SELECT endpointman_model_list.* FROM endpointman_model_list, endpointman_product_list WHERE endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_model_list.enabled = 1 AND endpointman_product_list.installed = 1 AND endpointman_model_list.brand = " . $brand;
		}
		$result1=mysql_query($sql);
		if(mysql_num_rows($result1)) {
			$i = 1;
			while ($row=mysql_fetch_array($result1)) {
				if ($row['id'] == $model) {
					$temp[$i]['value'] = $row['id'];
					$temp[$i]['text'] = $row['model'];
					$temp[$i]['selected'] = 'selected';
				}else{
					$temp[$i]['value'] = $row['id'];
					$temp[$i]['text'] = $row['model'];
					$temp[$i]['selected'] = 0;
				}
				$i++;
			}
		}
		if(!isset($temp)) {
			global $error, $global_cfg, $debug, $ep_link;
			if($global_cfg['debug']) {
				$debug .= "Error in function 'modelsAvailable'\nSQL:". $sql;
				$debug .= mysql_error($ep_link);
			}
			return(FALSE);
		} else {
			return($temp);
		}
	}	

	function displayExtension($ext = NULL) {
		if(!isset($ext)) {
			$not_added="SELECT devices.id, devices.description FROM devices WHERE devices.id not in (SELECT devices.id FROM devices, endpointman_mac_list WHERE tech='sip' AND devices.id = endpointman_mac_list.ext )";
		} else {
			$not_added="SELECT devices.id, devices.description FROM devices WHERE devices.id not in (SELECT devices.id FROM devices, endpointman_mac_list WHERE tech='sip' AND devices.id = endpointman_mac_list.ext AND endpointman_mac_list.ext !=".$ext." )";
		}
	
		$result=mysql_query($not_added);
	
		//$temp[0]['value'] = "";
		//$temp[0]['text'] = " --- ";
		//$temp[0]['selected'] = 1;
		$i = 1;
		while ($row=mysql_fetch_array($result)) {
			$temp[$i]['value'] = $row['id'];
			$temp[$i]['text'] = $row['id'] . " --- " . $row['description'];
			if ($row['id'] == $ext) {
				$temp[$i]['selected'] = "selected";
			}
			$i++;
		}
		return($temp);

	}   	

	function brandAvailable ($selected = NULL) {	
			$i = 1;
			$sql="SELECT name, id FROM  endpointman_brand_list WHERE installed = 1";
			$result=mysql_query($sql);
			$temp[0]['value'] = "";
			$temp[0]['text'] = "";
			while ($row=mysql_fetch_array($result)) {
				$temp[$i]['value'] = $row['id'];
				$temp[$i]['text'] = $row['name'];
				if ($row['id'] == $selected) {
					$temp[$i]['selected'] = TRUE;
				} else {
					$temp[$i]['selected'] = NULL;
				}
				$i++;
			}
			return($temp);	
	}

	function display_templates($product_id,$temp_select = NULL) {
		$i = 0;
		$sql="SELECT * FROM  endpointman_product_list WHERE endpointman_product_list.id =".$product_id;
		$result=mysql_query($sql);
		$row=mysql_fetch_array($result);
		$sql="SELECT * FROM  endpointman_template_list WHERE  product_id = '".$row['id']."'";
		$result=mysql_query($sql);
		while ($row=mysql_fetch_array($result)) {
			$temp[$i]['value'] = $row['id'];
			$temp[$i]['text'] = $row['name'];
			if ($row['id'] == $temp_select) {
				$temp[$i]['selected'] = "selected";
			}
			$i++;
		}
		$temp[$i]['value'] = 0;
		if ($temp_select == 0) {
			$temp[$i]['text'] = "Custom...";
			$temp[$i]['selected'] = "selected";
		} else {
			$temp[$i]['text'] = "Custom...";
		}
		return($temp);
	}

	function listTZ($selected) {
		$sql="SELECT tz from endpointman_time_zones";
		$result=mysql_query($sql);
		$i = 0;
		while ($row=mysql_fetch_array($result)) {
			if ($row['tz'] == $selected) {
				$temp[$i]['value'] = $row['tz'];
				$temp[$i]['text'] = $row['tz'];
				$temp[$i]['selected'] = 1;
			}else{
				$temp[$i]['value'] = $row['tz'];
				$temp[$i]['text'] = $row['tz'];
				$temp[$i]['selected'] = 0;	
			}
			$i++;
		}
		return($temp);
	}

	function rebootPhone($ext,$datav) {	
		if (is_numeric($ext)) {
		} elseif ($ext == 'all') {
			$sql = "SELECT endpointman_mac_list.id , endpointman_brand_list.directory , endpointman_product_list.cfg_dir FROM endpointman_mac_list , endpointman_model_list , endpointman_brand_list, endpointman_product_list WHERE endpointman_mac_list.model = endpointman_model_list.id AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.brand =endpointman_brand_list.id";
			$result = mysql_query($sql);
			while($row = mysql_fetch_assoc($result)) {
				if(($cfg_dir == $row['cfg_dir']) AND ($directory == $row['directory'])) {
					$phone_config->reboot($row['id']);
				} else {
					if(isset($phone_config)) {
						unset($phone_config);
					}
					$directory = $row['directory'];
					$cfg_dir = $row['cfg_dir'];
					require(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
					$phone_config = new $row['cfg_dir']();
					$phone_config->reboot($row['id']);
				}
			}		
		} else {
			$sql = "SELECT endpointman_brand_list.directory, endpointman_product_list.cfg_dir, endpointman_mac_list.id FROM endpointman_brand_list, endpointman_model_list, endpointman_mac_list, endpointman_product_list WHERE endpointman_brand_list.id = ".$datav['rb_brand']." AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.product_id = endpointman_product_list.id";
			$result = mysql_query($sql);
			while($row = mysql_fetch_assoc($result)) {
				if(($cfg_dir == $row['cfg_dir']) AND ($directory == $row['directory'])) {
					$phone_config->reboot($row['id']);
				} else {
					if(isset($phone_config)) {
						unset($phone_config);
					}
					$directory = $row['directory'];
					$cfg_dir = $row['cfg_dir'];
					require(PHONE_MODULES_PATH.$row['directory'].'/'.$row['cfg_dir'].'/functions.inc');
					$phone_config = new $row['cfg_dir']();
					$phone_config->reboot($row['id']);				
				}
			}
		}
	}
}	

function endpointman_update_progress_bar($out) {
	echo "\n<script type=\"text/javascript\" language=\"javascript\">";
	echo 'document.getElementById("tb").style.width = "'.$out.'%";';	
	echo "\n</script>";
}

function endpointmanager_read_header($ch, $string) {
	global $file_size, $fout;
	$length = strlen($string);
	$regs = "";
	ereg("(Content-Length:) (.*)", $string, $regs);
	if($regs[2] <> "") {
	$file_size = intval($regs[2]);
	}
	ob_flush();
	return $length;
}

function endpointmanager_read_body($ch, $string) {
	//Andrew Nagy added the below echo, otherwise the progress bar never updated until the download was complete
	echo "&nbsp;";
	//end Andrew code
	global $fout, $file_size, $downloaded, $lastseen;
	$length = strlen($string);
	$downloaded += intval($length);
	$downloadProgress = round(100 * (1 - $downloaded / $file_size), 0);
	$downloadProgress = 100 - $downloadProgress;
	if($lastseen <> $downloadProgress and $downloadProgress < 101) {
		endpointman_update_progress_bar($downloadProgress);
		$lastseen = $downloadProgress;
	}
	if($fout)
		fwrite($fout, $string);
	ob_flush();
	return $length;
}
?>
