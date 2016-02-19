<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	FreePBX::Endpointman()->configmod->getConfigModuleSQL(false);
	
	$cfg_type = FreePBX::Endpointman()->configmod->get("cfg_type");
	if (($cfg_type == 'file') AND (FreePBX::Endpointman()->epm_advanced_config_loc_is_writable())) {
		FreePBX::Endpointman()->tftp_check();
	}
	if ($cfg_type == 'http') {
		//$endpoint->message['advanced_settings'] = "Updated! - Point your phones to: http://" . $_SERVER['SERVER_ADDR'] . "/provisioning/p.php/";
		echo "<br><br>Updated! - Point your phones to: http://" . $_SERVER['SERVER_ADDR'] . "/provisioning/p.php/";
	}
	unset($cfg_type);
	
	if (FreePBX::Endpointman()->configmod->get("use_repo") == "1") {
		if (FreePBX::Endpointman()->has_git()) {
			
			if (!file_exists(FreePBX::Endpointman()->PHONE_MODULES_PATH . '/.git')) {
				$o = getcwd();
				chdir(dirname(FreePBX::Endpointman()->PHONE_MODULES_PATH));
				FreePBX::Endpointman()->rmrf(FreePBX::Endpointman()->PHONE_MODULES_PATH);
				$path = FreePBX::Endpointman()->has_git();
				exec($path . ' clone https://github.com/provisioner/Provisioner.git _ep_phone_modules', $output);
				chdir($o);
			}
		} else {
			echo  _("Git not installed!");
		}
	} else {
		if (file_exists(FreePBX::Endpointman()->PHONE_MODULES_PATH . '/.git')) {
			FreePBX::Endpointman()->rmrf(FreePBX::Endpointman()->PHONE_MODULES_PATH);
			$sql = "SELECT * FROM  `endpointman_brand_list` WHERE  `installed` =1";
			$result = & sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
			foreach ($result as $row) {
				FreePBX::Endpointman()->remove_brand($row['id'], FALSE, TRUE);
			}
		}
	}
?>

<div class="section-title" data-for="setting_provision">
	<h3><i class="fa fa-minus"></i><?php echo _("Setting Provision") ?></h3>
</div>
<div class="section" data-id="setting_provision">

	<!--IP address of phone server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="srvip"><?php echo _("IP address of phone server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="srvip"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control localnet validate=ip" id="srvip" name="srvip" value="<?php echo FreePBX::Endpointman()->configmod->get("srvip"); ?>">
							<button class='btn btn-default' id='autodetect' onclick="epm_advanced_tab_setting_input_value_change_bt('#srvip', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?php echo _("Determine for Me")?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="srvip-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END IP address of phone server-->
	<!--Configuration Type-->
	<?php
		if (FreePBX::Endpointman()->configmod->get("server_type") == 'http') {
			$server_type_http = "yes";
			$server_type_file = "";
		} else {
			$server_type_http = "";
			$server_type_file = "yes";
		}
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="cfg_type"><?php echo _("Configuration Type")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="cfg_type"></i>
						</div>
						<div class="col-md-9">
							<select name="cfg_type" class="form-control" id="cfg_type">
								<option value="file" <?php echo ($server_type_file == "yes" ? "selected" : "") ?> ><?php echo _("File (TFTP/FTP)")?></option>
								<option value="http" <?php echo ($server_type_http == "yes "? "selected" : "") ?> ><?php echo _("Web (HTTP)")?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="cfg_type-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<?php
		unset($server_type_http);
		unset($server_type_file);
	?>
	<!--END Configuration Type-->
	<!--Global Final Config & Firmware Directory-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="config_loc"><?php echo _("Global Final Config & Firmware Directory")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="config_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="config_loc" name="config_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("config_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="config_loc-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END Global Final Config & Firmware Directory-->
</div>

<div class="section-title" data-for="setting_time">
	<h3><i class="fa fa-minus"></i><?php echo _("Time") ?></h3>
</div>
<div class="section" data-id="setting_time">
	<!--Time Zone-->
	<?php
		$list_tz = FreePBX::Endpointman()->listTZ(FreePBX::Endpointman()->configmod->get("tz"));
		$lnhtm = '';
		foreach ($list_tz as $row) {
			$lnhtm .= '<option value="'.$row['value'].'" '. ($row['selected'] == 1 ? 'selected="selected"' : '') .' > '.$row['text'].'</option>';
		}
		unset ($list_tz);
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="tz"><?php echo _("Time Zone")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tz"></i>
						</div>
						<div class="col-md-9">
							<select name="tz" class="form-control" id="tz">
								<?php echo $lnhtm; ?>
							</select>
							<button class='btn btn-default' id='tzphp' onclick="epm_advanced_tab_setting_input_value_change_bt('#tz', sValue = '<?php echo FreePBX::Endpointman()->config->get('PHPTIMEZONE'); ?>', bSaveChange = true);"><i class="fa fa-clock-o"></i> <?php echo _("TimeZone by PBX Setting")?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tz-help">Like England/London</span>
			</div>
		</div>
	</div>
	<?php unset($lnhtm); ?>
	<!--END Time Zone-->
	<!--Time Server - NTP Server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="ntp_server"><?php echo _("Time Server (NTP Server)")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="ntp_server"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="ntp_server" name="ntp_server" value="<?php echo FreePBX::Endpointman()->configmod->get("ntp"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="ntp_server-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END Time Server - NTP Server-->
</div>

<div class="section-title" data-for="setting_local_paths">
	<h3><i class="fa fa-minus"></i><?php echo _("Local Paths") ?></h3>
</div>
<div class="section" data-id="setting_local_paths">
	<!--NMAP Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="nmap_loc"><?php echo _("NMAP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="nmap_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="nmap_loc" name="nmap_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("nmap_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="nmap_loc-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END NMAP Executable Path-->
	<!--ARP Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="arp_loc"><?php echo _("ARP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="arp_loc" name="arp_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("arp_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="arp_loc-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END ARP Executable Path-->
	<!--Asterisk Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="asterisk_loc"><?php echo _("Asterisk Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="asterisk_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="asterisk_loc" name="asterisk_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("asterisk_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="asterisk_loc-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END Asterisk Executable Path-->
</div>

<div class="section-title" data-for="setting_web_directories">
	<h3><i class="fa fa-minus"></i><?php echo _("Web Directories") ?></h3>
</div>
<div class="section" data-id="setting_web_directories">
	<!--Package Server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="package_server"><?php echo _("Package Server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="package_server"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="package_server" name="package_server" value="<?php echo FreePBX::Endpointman()->configmod->get("update_server"); ?>">
							<button class='btn btn-default' id='default_package_server' onclick="epm_advanced_tab_setting_input_value_change_bt('#package_server', sValue = 'http://mirror.freepbx.org/provisioner/v3/', bSaveChange = true);"><i class='fa fa-search'></i> <?php echo _("Default Mirror FreePBX")?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="package_server-help">Texto ayuda!</span>
			</div>
		</div>
	</div>
	<!--END Package Server-->
</div>

<div class="section-title" data-for="setting_experimental">
	<h3><i class="fa fa-minus"></i><?php echo _("Experimental") ?></h3>
</div>
<div class="section" data-id="setting_experimental">
	<!--Enable FreePBX ARI Module-->
	<?php $ari_selected = FreePBX::Endpointman()->configmod->get("enable_ari"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="enable_ari"><?php echo _("Enable FreePBX ARI Module")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_ari"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="enable_ari" id="enable_ari_yes" value="Yes" <?php echo ($ari_selected  == 1 ? "CHECKED" : "") ?>>
							<label for="enable_ari_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="enable_ari" id="enable_ari_no" value="No" <?php echo ($ari_selected == 0 ? "CHECKED" : "") ?>>
							<label for="enable_ari_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_ari-help">Enable FreePBX ARI Module <a href="http://wiki.provisioner.net/index.php/Endpoint_manager_manual_ari" target="_blank">What?</a></span>
			</div>
		</div>
	</div>
	<?php unset($ari_selected); ?>
	<!--END Enable FreePBX ARI Module-->
	<!--Enable Debug Mode-->
	<?php 
		$debug_selected = FreePBX::Endpointman()->configmod->get("debug");
		if ($debug_selected) {
			//global $debug;
			//$debug = $debug . print_r($_REQUEST, true);
			//$endpoint->tpl->assign("debug", $debug);
		}
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="enable_debug"><?php echo _("Enable Debug Mode")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_debug"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="enable_debug" id="enable_debug_yes" value="Yes" <?php echo ($debug_selected  == 1 ? "CHECKED" : "") ?>>
							<label for="enable_debug_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="enable_debug" id="enable_debug_no" value="No" <?php echo ($debug_selected == 0 ? "CHECKED" : "") ?>>
							<label for="enable_debug_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_debug-help">Enable Advanced debugging mode for endpoint manager</span>
			</div>
		</div>
	</div>
	<?php unset($debug_selected); ?>
	<!--END Enable Debug Mode-->
	<!--Disable Tooltips-->
	<?php $help_selected = FreePBX::Endpointman()->configmod->get("disable_help"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="disable_help"><?php echo _("Disable Tooltips")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="disable_help"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="disable_help" id="disable_help_yes" value="Yes" <?php echo ($help_selected  == 1 ? "CHECKED" : "") ?>>
							<label for="disable_help_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="disable_help" id="disable_help_no" value="No" <?php echo ($help_selected == 0 ? "CHECKED" : "") ?>>
							<label for="disable_help_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="disable_help-help">Disable Tooltip popups</span>
			</div>
		</div>
	</div>
	<?php unset($help_selected); ?>
	<!--END Disable Tooltips-->
	<!--Allow Duplicate Extensions-->
	<?php $dupext_selected = FreePBX::Endpointman()->configmod->get("show_all_registrations"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="allow_dupext"><?php echo _("Allow Duplicate Extensions")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_dupext"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="allow_dupext" id="allow_dupext_yes" value="Yes" <?php echo ($dupext_selected  == 1 ? "CHECKED" : "") ?>>
							<label for="allow_dupext_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="allow_dupext" id="allow_dupext_no" value="No" <?php echo ($dupext_selected == 0 ? "CHECKED" : "") ?>>
							<label for="allow_dupext_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_dupext-help">Assign the same extension to multiple phones (Note: This is not supported by Asterisk)</span>
			</div>
		</div>
	</div>
	<?php unset($dupext_selected); ?>
	<!--END Allow Duplicate Extensions-->
	<!--Allow Saving Over Default Configuration Files-->
	<?php $allow_hdfiles = FreePBX::Endpointman()->configmod->get("allow_hdfiles"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="allow_hdfiles"><?php echo _("Allow Saving Over Default Configuration Files")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_hdfiles"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="allow_hdfiles" id="allow_hdfiles_yes" value="Yes" <?php echo ($allow_hdfiles  == 1 ? "CHECKED" : "") ?>>
							<label for="allow_hdfiles_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="allow_hdfiles" id="allow_hdfiles_no" value="No" <?php echo ($allow_hdfiles == 0 ? "CHECKED" : "") ?>>
							<label for="allow_hdfiles_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_hdfiles-help">When editing the configuration files allows one to save over the global template default instead of saving directly to the database. These types of changes can and will be overwritten when updating the brand packages from the configuration/installation page</span>
			</div>
		</div>
	</div>
	<?php unset($allow_hdfiles); ?>
	<!--END Allow Saving Over Default Configuration Files-->
	<!--Disable TFTP Server Check-->
	<?php $tftp_checked = FreePBX::Endpointman()->configmod->get("tftp_check"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="tftp_check"><?php echo _("Disable TFTP Server Check")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tftp_check"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="tftp_check" id="tftp_check_yes" value="Yes" <?php echo ($tftp_checked  == 1 ? "CHECKED" : "") ?>>
							<label for="tftp_check_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="tftp_check" id="tftp_check_no" value="No" <?php echo ($tftp_checked == 0 ? "CHECKED" : "") ?>>
							<label for="tftp_check_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tftp_check-help">Disable checking for a valid, working TFTP server which can sometimes cause Apache to crash</span>
			</div>
		</div>
	</div>
	<?php unset($tftp_checked); ?>
	<!--END Disable TFTP Server Check-->
	<!--Disable Configuration File Backups-->
	<?php $backup_checked = FreePBX::Endpointman()->configmod->get("backup_check"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="backup_check"><?php echo _("Disable Configuration File Backups")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="backup_check"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="backup_check" id="backup_check_yes" value="Yes" <?php echo ($backup_checked  == 1 ? "CHECKED" : "") ?>>
							<label for="backup_check_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="backup_check" id="backup_check_no" value="No" <?php echo ($backup_checked == 0 ? "CHECKED" : "") ?>>
							<label for="backup_check_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="backup_check-help">Disable backing up the tftboot directory on every phone rebuild or save</span>
			</div>
		</div>
	</div>
	<?php unset($backup_checked); ?>
	<!--END Disable Configuration File Backups-->
	<!--Use GITHUB Live Repo-->
	<?php $use_repo = FreePBX::Endpointman()->configmod->get("use_repo"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="use_repo"><?php echo _("Use GITHUB Live Repo")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="use_repo"></i>
						</div>
						<div class="col-md-9 radioset">
							<input type="radio" name="use_repo" id="use_repo_yes" value="Yes" <?php echo ($use_repo  == 1 ? "CHECKED" : "") ?>>
							<label for="use_repo_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
							<input type="radio" name="use_repo" id="use_repo_no" value="No" <?php echo ($use_repo == 0 ? "CHECKED" : "") ?>>
							<label for="use_repo_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="use_repo-help">Use the live github repository (Requires git to be installed), (WARN: Beta!)</span>
			</div>
		</div>
	</div>
	<?php unset($use_repo); ?>
	<!--END Use GITHUB Live Repo-->
	<!--GIT Branch-->
	<?php
	if (FreePBX::Endpointman()->configmod->get("use_repo")) {
		$path = $endpoint->has_git();
		$o = getcwd();
		chdir(PHONE_MODULES_PATH);
		
		if(isset($_REQUEST['git_branch'])) {
			if(preg_match('/remotes\/origin\/(.*)/i', $_REQUEST['git_branch'],$matches)) {
				//Pull from a remote.
				if(!exec($path.' checkout origin_'. $matches[1])) {
					//We must remote track this branch
					exec($path . ' branch --track origin_'.$matches[1].' origin/'.$matches[1]);
					exec($path . ' checkout origin_'.$matches[1]);
				}
				exec($path . ' pull origin ' . $matches[1]);
			} else {
				exec($path.' checkout '. $_REQUEST['git_branch'],$output);
			}
		}
		
		exec($path . ' fetch origin');
		exec($path . ' branch -a',$output);
		
		$lnhtm = '';
		foreach($output as $data) {
			if(!preg_match('/Your branch is ahead of/i', $data)) {
				$selected = preg_match('/\*/i', $data) ? TRUE : FALSE;
				$data = preg_replace('/\*/i', '', $data);
				
				$lnhtm .= '<option value="'.$data.'" '. ($selected === TRUE ? 'selected' : '') .' > '.$data.'</option>';
			}
		}
		chdir($o);
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="git_branch"><?php echo _("GIT Branch")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="git_branch"></i>
						</div>
						<div class="col-md-9">
							<select name="git_branch" class="form-control" id="git_branch">
								<?php echo $lnhtm; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="git_branch-help">Select the live github repository branch (WARN: Beta!)</span>
			</div>
		</div>
	</div>
	<?php 
		unset($lnhtm); 
	}
	?>
	<!--END GIT Branch-->
</div>