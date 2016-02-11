<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	FreePBX::Endpointman()->configmod->getConfigModuleSQL(false);
	
	if (FreePBX::Endpointman()->configmod->get("server_type") == 'http') {
		$server_type_http = "yes";
		$server_type_file = "";
	} else {
		$server_type_http = "";
		$server_type_file = "yes";
	}
	
	if (FreePBX::Endpointman()->configmod->get("show_all_registrations")) {
		$dupext_selected = "checked";
	} else {
		$dupext_selected = "";
	}
	if (FreePBX::Endpointman()->configmod->get("enable_ari")) {
		$ari_selected = "checked";
	} else {
		$ari_selected = "";
	}
	if (FreePBX::Endpointman()->configmod->get("disable_help")) {
		$help_selected = "checked";
	} else {
		$help_selected = "";
	}
	if (FreePBX::Endpointman()->configmod->get("allow_hdfiles")) {
		$allow_hdfiles = "checked";
	} else {
		$allow_hdfiles = "";
	}
	if (FreePBX::Endpointman()->configmod->get("tftp_check")) {
		$tftp_checked = "checked";
	} else {
		$tftp_checked = "";
	}
	if (FreePBX::Endpointman()->configmod->get("backup_check")) {
		$backup_checked = "checked";
	} else {
		$backup_checked = "";
	}
	
	
	if (FreePBX::Endpointman()->configmod->get("use_repo")) {
		$git_enabled = true;
		$use_repo = "checked";
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
		
		foreach($output as $data) {
			if(!preg_match('/Your branch is ahead of/i', $data)) {
				$selected = preg_match('/\*/i', $data) ? TRUE : FALSE;
				$data = preg_replace('/\*/i', '', $data);
				$branchoutput[] = array(
					'name' => $data,
					'selected' => $selected
				);
			}
		}
		chdir($o);
	} else {
		$use_repo = "";
	}
	
	
	if (FreePBX::Endpointman()->configmod->get("debug")) {
		$debug_selected = "checked";
		global $debug;
		$debug = $debug . print_r($_REQUEST, true);
//$endpoint->tpl->assign("debug", $debug);
	} else {
		$debug_selected = "";
	}
	
	
	$list_tz = FreePBX::Endpointman()->listTZ(FreePBX::Endpointman()->configmod->get("tz"));
	    //$endpoint->tpl->assign("brand_list", $endpoint->brands_available());
?>

<form action='config.php?type=tool&amp;display=epm_advanced&amp;subpage=settings' method='POST'>

<table width='800px' class='alt_table'>
<tr>
<td width='50%'><?php echo _("IP address of phone server")?>:</td>
<td width='50%'><input type='text' id='srvip' name='srvip' value='<?php echo FreePBX::Endpointman()->configmod->get("srvip"); ?>'><a href='#' onclick="document.getElementById('srvip').value = '<?php echo $_SERVER["SERVER_ADDR"]; ?>'; "><font style="font-size: 0.8em"><i class='icon-search'></i> <?php echo _("Determine for me")?></font></a></td>
</tr>
<tr>
  <td><?php echo _("Configuration Type")?></td>
  <td>
        <select name="cfg_type" id="cfg_type">
            <option value="file" <?php ($server_type_file == yes ? "selected='selected'" : ""); ?> >File (TFTP/FTP)</option>
            <option value="http" <?php ($server_type_http == yes ? "selected='selected'" : ""); ?> >Web (HTTP)</option>
        </select>
  </td>
</tr>
<tr>
  <td><?php echo _("Global Final Config & Firmware Directory")?></td>
  <td><label>
    <input type="text" name="config_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("config_location"); ?>">
  </label></td>
</tr>
</table>

<h5>Time</h5>
<table width='800px' class='alt_table'>
<tr>
<td width='50%'><?php echo _("Time Zone")?> (<font style="font-size: 0.8em"><?php echo _('like')?> <b>England/London</b></font>)</td>
<td width='50%'>
<select name="tz" id="tz">
	<?php 
	foreach ($list_tz as $row) {
		echo '<option value="'.$row['value'].'" '. ($row['selected'] == 1 ? 'selected="selected"' : '') .' > '.$row['text'].'</option>';
	}
	?>
</select>
</td>
</tr>
<tr>
<td width='50%'><?php echo _("Time Server (NTP Server)")?></td>
  <td><label>
    <input type="text" name="ntp_server" value="<?php echo FreePBX::Endpointman()->configmod->get("ntp"); ?>">
  </label></td>
</tr>
</table>

<h5>Local Paths</h5>
<table width='800px' class='alt_table'>
<tr>
  <td width="50%">NMAP <?php echo _("executable path")?>:</td>
  <td width="50%"><label>
    <input type="text" name="nmap_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("nmap_location"); ?>">
  </label></td>
</tr>
<tr>
  <td>ARP <?php echo _("executable path")?>:</td>
  <td><label>
    <input type="text" name="arp_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("arp_location"); ?>">
  </label></td>
</tr>
<tr>
  <td>Asterisk <?php echo _("executable path")?>:</td>
  <td><label>
    <input type="text" name="asterisk_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("asterisk_location"); ?>">
  </label></td>
</tr>
</table>

<h5>Web Directories</h5>
<table width='800px' class='alt_table'>
<tr>
  <td width="50%">Package Server:</td>
  <td width="50%"><label>
    <input type="text" name="package_server" value="<?php echo FreePBX::Endpointman()->configmod->get("update_server"); ?>" size="50">
  </label></td>
</tr>
</table>

<h5>Experimental</h5>
<table width='800px' class='alt_table'>
<tr>
  <td width="50%"><?php echo _("Enable FreePBX ARI Module")?> (<a href="http://wiki.provisioner.net/index.php/Endpoint_manager_manual_ari" target="_blank">What?</a>)</td>
  <td><label>
    <input type=checkbox name="enable_ari" <?php echo $ari_selected; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Enable Debug Mode")?> <a href="#" class="info"><span>Enable Advanced debugging mode for endpoint manager</span></a></td>
  <td><label>
    <input type=checkbox name="enable_debug" <?php echo $debug_selected; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Disable Tooltips")?> <a href="#" class="info"><span>Disable Tooltip popups</span></a></td>
  <td><label>
    <input type=checkbox name="disable_help" <?php echo $help_selected; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Allow Duplicate Extensions")?> <a href="#" class="info"><span>Assign the same extension to multiple phones (Note: This is not supported by Asterisk)</span></a></td>
  <td><label>
    <input type=checkbox name="allow_dupext" <?php echo $dupext_selected; ?>>
  </label></td>
</tr>
<tr>
  <td><?php echo _("Allow Saving Over Default Configuration Files")?> <a href="#" class="info"><span>When editing the configuration files allows one to save over the global template default instead of saving directly to the database. These types of changes can and will be overwritten when updating the brand packages from the configuration/installation page</span></a></td>
  <td><label>
    <input type=checkbox name="allow_hdfiles" <?php echo $allow_hdfiles; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Disable TFTP Server Check")?> <a href="#" class="info"><span>Disable checking for a valid, working TFTP server which can sometimes cause Apache to crash</span></a></td>
  <td><label>
    <input type=checkbox name="tftp_check" <?php echo $tftp_checked; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Disable Configuration File Backups")?> <a href="#" class="info"><span>Disable backing up the tftboot directory on every phone rebuild or save</span></a></td>
  <td><label>
    <input type=checkbox name="backup_check" <?php echo $backup_checked; ?> >
  </label></td>
</tr>
<tr>
  <td><?php echo _("Use GITHUB Live Repo (Requires git to be installed)")?> <a href="#" class="info"><span>Use the live github repository (WARN: Beta!)</span></a></td>
  <td><label>
    <input type=checkbox name="use_repo" <?php echo $use_repo; ?> >
  </label></td>
</tr>

	<?php if ($git_enabled == true) { ?>
	<tr>
	  <td>GIT Branch <a href="#" class="info"><span>Select the live github repository branch (WARN: Beta!)</span></a></td>
	  <td>
		  <select name="git_branch" id="git_branch">
			<?php 
			 foreach ($branchoutput as $row) {
				echo '<option value="'.$row['name'].'" '. ($row['selected'] === TRUE ? 'selected' : '') .' > '.$row['name'].'</option>';
			}
			?>
		  </select>
	  </td>
	</tr>
	<?php } ?>

<tr>
<td colspan='2' align='center'><button type='Submit' name='button_update_globals'><i class='icon-refresh blue'></i> <?php echo _('Update Globals')?></button></td>
</tr>
</table>
</form>










<?php        







return;


		if (isset($_REQUEST['button_update_globals'])) {
            $_POST['srvip'] = trim($_POST['srvip']);  #trim whitespace from IP address

            $_POST['config_loc'] = trim($_POST['config_loc']);  #trim whitespace from Config Location

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['srvip'] . "' WHERE var_name='srvip'";
            $endpoint->eda->sql($sql);
            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['tz'] . "' WHERE var_name='tz'";
            $endpoint->eda->sql($sql);

            if ($_POST['cfg_type'] == 'http') {
                $symlink = $amp_conf['AMPWEBROOT'] . "/provisioning";
                $reallink = LOCAL_PATH . "provisioning";
                if ((!is_link($symlink)) OR (!readlink($symlink) == $reallink)) {
                    if (!symlink($reallink, $symlink)) {
                        $endpoint->error['config_dir'] = "Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web provisioning link not created!";
                        $_POST['cfg_type'] = 'file';
                    } else {
                        $_POST['cfg_type'] = 'http';
                    }
                } else {
                    $_POST['cfg_type'] = 'http';
                }
            } else {
                $_POST['cfg_type'] = 'file';
            }
            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['cfg_type'] . "' WHERE var_name='server_type'";
            $endpoint->eda->sql($sql);

            //No trailing slash. Help the user out and add one :-)
            if ($_POST['config_loc'][strlen($_POST['config_loc']) - 1] != "/") {
                $_POST['config_loc'] = $_POST['config_loc'] . "/";
            }

            $tftp_writable = FALSE;
            if ((isset($_POST['config_loc'])) AND ($_POST['config_loc'] != "")) {
                if ((file_exists($_POST['config_loc'])) AND (is_dir($_POST['config_loc']))) {
                    if (is_writable($_POST['config_loc'])) {
                        $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['config_loc'] . "' WHERE var_name='config_location'";
                        $endpoint->eda->sql($sql);
                        $tftp_writable = TRUE;
                    } else {
                        $endpoint->error['config_dir'] = "Directory Not Writable!";
                    }
                } else {
                    $endpoint->error['config_dir'] = "Not a Vaild Directory <br /> Try to run 'mkdir " . $_POST['config_loc'] . "' as root";
                }
            } else {
                $endpoint->error['config_dir'] = "No Configuration Location Defined!";
            }

            if ((isset($_POST['enable_ari'])) AND ($_POST['enable_ari'] == "on")) {
                $_POST['enable_ari'] = 1;
            } else {
                $_POST['enable_ari'] = 0;
            }
            if ((isset($_POST['enable_debug'])) AND ($_POST['enable_debug'] == "on")) {
                $_POST['enable_debug'] = 1;
            } else {
                $_POST['enable_debug'] = 0;
            }
            if ((isset($_POST['disable_help'])) AND ($_POST['disable_help'] == "on")) {
                $_POST['disable_help'] = 1;
            } else {
                $_POST['disable_help'] = 0;
            }
            if ((isset($_POST['allow_dupext'])) AND ($_POST['allow_dupext'] == "on")) {
                $_POST['allow_dupext'] = 1;
            } else {
                $_POST['allow_dupext'] = 0;
            }
            if ((isset($_POST['allow_hdfiles'])) AND ($_POST['allow_hdfiles'] == "on")) {
                $_POST['allow_hdfiles'] = 1;
            } else {
                $_POST['allow_hdfiles'] = 0;
            }
            if ((isset($_POST['tftp_check'])) AND ($_POST['tftp_check'] == "on")) {
                $_POST['tftp_check'] = 1;
            } else {
                $_POST['tftp_check'] = 0;
            }
            if ((isset($_POST['backup_check'])) AND ($_POST['backup_check'] == "on")) {
                $_POST['backup_check'] = 1;
            } else {
                $_POST['backup_check'] = 0;
            }
            if ((isset($_POST['use_repo'])) AND ($_POST['use_repo'] == "on")) {
                if ($endpoint->has_git()) {
                    $_POST['use_repo'] = 1;
                    if (!file_exists(PHONE_MODULES_PATH . '/.git')) {
                        $o = getcwd();
                        chdir(dirname(PHONE_MODULES_PATH));
                        $endpoint->rmrf(PHONE_MODULES_PATH);
                        $path = $endpoint->has_git();
                        exec($path . ' clone https://github.com/provisioner/Provisioner.git _ep_phone_modules', $output);
                        chdir($o);
                    }
                } else {
                    $endpoint->error['use_repo'] = 'Git not installed!';
                }
            } else {
                $_POST['use_repo'] = 0;
                if (file_exists(PHONE_MODULES_PATH . '/.git')) {
                    $endpoint->rmrf(PHONE_MODULES_PATH);
                    $sql = "SELECT * FROM  `endpointman_brand_list` WHERE  `installed` =1";
                    $result = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
                    foreach ($result as $row) {
                        $endpoint->remove_brand($row['id'], FALSE, TRUE);
                    }
                }
            }

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['tftp_check'] . "' WHERE var_name='tftp_check'";
            $endpoint->eda->sql($sql);

            //Check tftp server to make sure it's functioning if we are using it
            if (($_POST['cfg_type'] == 'file') AND ($tftp_writable)) {
                $endpoint->tftp_check();
            }

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['package_server'] . "' WHERE var_name='update_server'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['allow_hdfiles'] . "' WHERE var_name='allow_hdfiles'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['allow_dupext'] . "' WHERE var_name='show_all_registrations'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_ari'] . "' WHERE var_name='enable_ari'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_debug'] . "' WHERE var_name='debug'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_debug'] . "' WHERE var_name='debug'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['ntp_server'] . "' WHERE var_name='ntp'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['nmap_loc'] . "' WHERE var_name='nmap_location'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['arp_loc'] . "' WHERE var_name='arp_location'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['disable_help'] . "' WHERE var_name='disable_help'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['backup_check'] . "' WHERE var_name='backup_check'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['use_repo'] . "' WHERE var_name='use_repo'";
            $endpoint->eda->sql($sql);

            if ($_POST['cfg_type'] == 'http') {
                $endpoint->message['advanced_settings'] = "Updated! - Point your phones to: http://" . $_SERVER['SERVER_ADDR'] . "/provisioning/p.php/";
            } else {
                $endpoint->message['advanced_settings'] = "Updated!";
            }
        }
?>