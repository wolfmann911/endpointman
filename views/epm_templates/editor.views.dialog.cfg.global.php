<?php

/*

    if(isset($_REQUEST['button_update_globals'])) {
        $_POST['srvip'] = trim($_POST['srvip']);  #trim whitespace from IP address

        $_POST['config_loc'] = trim($_POST['config_loc']);  #trim whitespace from Config Location

        //No trailing slash. Help the user out and add one :-)
        if($_POST['config_loc'][strlen($_POST['config_loc'])-1] != "/") {
            $_POST['config_loc'] = $_POST['config_loc'] ."/";
        }

        if((isset($_POST['config_loc'])) AND ($_POST['config_loc'] != "")) {
            if((file_exists($_POST['config_loc'])) AND (is_dir($_POST['config_loc']))) {
                if(is_writable($_POST['config_loc'])) {
                    $settings['config_location'] = $_POST['config_loc'];
                } else {
                    $endpoint->error['config_dir'] = "Directory Not Writable!";
                    $settings['config_location'] = $endpoint->global_cfg['config_location'];
                }
            } else {
                $endpoint->error['config_dir'] = "Not a Vaild Directory";
                $settings['config_location'] = $endpoint->global_cfg['config_location'];
            }
        } else {
            $endpoint->error['config_dir'] = "No Configuration Location Defined!";
            $settings['config_location'] = $endpoint->global_cfg['config_location'];
        }

        $settings['srvip'] = $_POST['srvip'];
        $settings['ntp'] = $_POST['ntp_server'];
        $settings['tz'] = $_POST['tz'];

        $settings_ser = serialize($settings);
        if($_REQUEST['custom'] == 0) {
            //This is a group template
            $sql = "UPDATE endpointman_template_list SET global_settings_override = '".addslashes($settings_ser)."' WHERE id = ".$_REQUEST['tid'];
            $endpoint->eda->sql($sql);
        } else {
            //This is an individual template
            $sql = "UPDATE endpointman_mac_list SET global_settings_override = '".addslashes($settings_ser)."' WHERE id = ".$_REQUEST['tid'];
            $endpoint->eda->sql($sql);
        }

        $endpoint->message['advanced_settings'] = "Updated!";
    }
	
	
	
	
	
    if(isset($_REQUEST['button_reset_globals'])) {
        if($_REQUEST['custom'] == 0) {
            //This is a group template
            $sql = "UPDATE endpointman_template_list SET global_settings_override = NULL WHERE id = ".$_REQUEST['tid'];
            $endpoint->eda->sql($sql);
        } else {
            //This is an individual template
            $sql = "UPDATE endpointman_mac_list SET global_settings_override = NULL WHERE id = ".$_REQUEST['tid'];
            $endpoint->eda->sql($sql);
        }
        $endpoint->message['advanced_settings'] = "Globals Reset to Default!";
    }
	
	*/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*
	$product_list = "SELECT * FROM endpointman_product_list WHERE id > 0";
	$product_list =& sql($product_list,'getAll', DB_FETCHMODE_ASSOC);
	*/
	
    if($_REQUEST['custom'] == 0) {
        //This is a group template
        $sql = 'SELECT global_settings_override FROM endpointman_template_list WHERE id = '.$_REQUEST['idsel'];
		//$settings = $endpoint->eda->sql($sql,'getOne');

    } else {
        //This is an individual template
        $sql = 'SELECT global_settings_override FROM endpointman_mac_list WHERE id = '.$_REQUEST['idsel'];
        //$settings = $endpoint->eda->sql($sql,'getOne');
    }
	$settings =& sql($sql, 'getOne');
	
	
    if(isset($settings)) {
        $settings = unserialize($settings);
        //$settings['tz'] = FreePBX::Endpointman()->listTZ(FreePBX::Endpointman()->configmod->get("tz"));
    } else {
        $settings['srvip'] = FreePBX::Endpointman()->configmod->get("srvip");
        $settings['ntp'] = FreePBX::Endpointman()->configmod->get("ntp");
        $settings['config_location'] = FreePBX::Endpointman()->configmod->get("config_location");
        $settings['tz'] = FreePBX::Endpointman()->configmod->get("tz");
    }
    //Because we are working with global variables we probably updated them, so lets refresh those variables
    //$endpoint->global_cfg = $endpoint->eda->sql("SELECT var_name, value FROM endpointman_global_vars",'getAssoc');
	

	//$settings['srvip']
	//$settings['ntp']
	//$settings['config_location']
	//$settings['tz']
	
	
	
	
	//onclick="return popitup3('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=global_over')"
?>

<div class="modal fade" id="CfgGlobalTemplate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo _('End Point Configuration Manager')?></h4>
			</div>
			<div class="modal-body">


           
           
           
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
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server PBX..." id="srvip" name="srvip" value="<?php echo $settings['srvip']; ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_global_input_value_change_bt('#srvip', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>');"><i class='fa fa-search'></i> <?php echo _("Determine for Me")?></button>
      							</span>
    						</div>
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
		$server_type = FreePBX::Endpointman()->configmod->get("server_type");
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
								<option value="file" <?php echo ($server_type == "file" ? "selected" : "") ?> ><?php echo _("File (TFTP/FTP)")?></option>
								<option value="http" <?php echo ($server_type == "http"? "selected" : "") ?> ><?php echo _("Web (HTTP)")?></option>
							</select>
							<div class="alert alert-info" role="alert" id="cfg_type_alert">
								<strong><?php echo _("Updated!"); ?></strong><?php echo _(" - Point your phones to: "); ?><a href="http://<?php echo $_SERVER['SERVER_ADDR']; ?>/provisioning/p.php/" class="alert-link" target="_blank">http://<?php echo $_SERVER['SERVER_ADDR']; ?>/provisioning/p.php/</a>.
							</div>
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
		unset($server_type);
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
							<input type="text" class="form-control" id="config_loc" name="config_loc" value="<?php echo $settings['config_location']; ?>">
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
		$list_tz = FreePBX::Endpointman()->listTZ($settings['tz']);
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
							<div class="input-group">
      							<select name="tz" class="form-control" id="tz">
								<?php echo $lnhtm; ?>
								</select>
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='tzphp' onclick="epm_global_input_value_change_bt('#tz', sValue = '<?php echo FreePBX::Endpointman()->config->get('PHPTIMEZONE'); ?>');"><i class="fa fa-clock-o"></i> <?php echo _("TimeZone by PBX Setting")?></button>
      							</span>
    						</div>
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
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server NTP..." id="ntp_server" name="ntp_server" value="<?php echo $settings['ntp']; ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetectntp' onclick="epm_global_input_value_change_bt('#ntp_server', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>');"><i class='fa fa-search'></i> <?php echo _("Determine for Me")?></button>
      							</span>
    						</div>
							
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











                
                
			</div>
			<div class="modal-footer">
                <button type="button" class="btn btn-success" name="button_update_globals"><i class="fa fa-floppy-o" aria-hidden="true"></i> <?php echo _('Update Global Overrides')?></button>
				<button type="button" class="btn btn-danger" name="button_reset_globals"><i class="fa fa-refresh" aria-hidden="true"></i> <?php echo _('Reset Global Overrides to Default')?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->








<?php


/*
<button type="button" class="btn btn-danger" name="button_cancel_globals" data-dismiss="modal"><i class="fa fa-times" aria-hidden="true"></i> <?php echo _('Cancel')?></button>
*/



/*

<tr>
<td width='50%' align='right'><?php echo _("IP address of phone server")?>:</td>
<td width='50%' align='left'><input type='text' id='srvip' name='srvip' value='{$srvip}'><a href='#' onclick="document.getElementById('srvip').value = '{$ip}'; "><?php echo _("Determine for me")?></a></td>
</tr>


<tr>
  <td align='right'><?php echo _("Configuration Type")?></td>
  <td align='left'>
      <select name="cfg_type" id="cfg_type" disabled>
            <option value="file">File (TFTP/FTP)</option>
            <option value="web">Web (HTTP)</option>
        </select>
  </td>
</tr>


<tr>
  <td align='right'><?php echo _("Global Final Config & Firmware Directory")?></td>
  <td align='left'><label>
    <input type="text" name="config_loc" value="{$config_location}">
  </label></td>
</tr>


<tr>
  <td align='right'><br/></td>
  <td align='left'></td>
</tr>


<tr>
<td width='50%' align='right'><?php echo _("Time Zone")?> (<?php echo _('like')?> USA-5)</td>
<td width='50%' align='left'><select name="tz" id="tz">
	{loop name="list_tz"}
	<option value="{$value.value}" {if condition="$value.selected == 1"}selected='selected'{/if}>{$value.text}</option>
	{/loop}
</select>
</td
</tr>



<tr>
<td width='50%' align='right'><?php echo _("Time Server (NTP Server)")?></td>
  <td align='left'><label>
    <input type="text" name="ntp_server" value="{$ntp_server}">
  </label></td>
</tr>
<tr>
            

            
            
            
            
            
            
            
            
            
            
            
            
            
            
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="number_new_oui"><?php echo _("OUI")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="number_new_oui"></i>
									</div>
									<div class="col-md-9">
										<input type="text" maxlength="6" class="form-control" id="number_new_oui" name="number_new_oui" value="" placeholder="<?php echo _("OUI Brand")?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="number_new_oui-help"><?php echo _("They are the first 6 characters of the MAC device that identifies the brand (manufacturer).")?></span>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="brand_new_oui"><?php echo _("Brand")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_new_oui"></i>
									</div>
									<div class="col-md-9">
			      						<select class="form-control" id="brand_new_oui" name="brand_new_oui">
			      							<option value=""><?php echo _("Select Brand:")?></option>
											<?php
											foreach ($brands as $row) {
												echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="brand_new_oui-help"><?php echo _("It is the brand of OUI we specified.")?></span>
						</div>
					</div>
				</div>


*/
?>