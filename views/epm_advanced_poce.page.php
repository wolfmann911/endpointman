<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	$request = $_REQUEST;
	
	if (isset($_REQUEST['product_select'])) {
		$product_select = $_REQUEST['product_select'];
		
		$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id ='" . $product_select . "'";
		$row =  sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
		$config_files = explode(",", $row['config_files']);
		$i = 0;
		foreach ($config_files as $config_files_data) {
			$file_list[$i]['value'] = $i;
			$file_list[$i]['text'] = $config_files_data;
			$i++;
		}
		
		$sql = "SELECT * FROM endpointman_custom_configs WHERE product_id = '" . $product_select . "'";
		$res = sql($sql,'getAll', DB_FETCHMODE_ASSOC);
		$i = 0;
		if (count($res)) {
			$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
			foreach ($data as $row2) {
				$sql_file_list[$i]['value'] = $row2['id'];
				$sql_file_list[$i]['text'] = $row2['name'];
				$sql_file_list[$i]['ref'] = $row2['original_name'];
				$i++;
			}
		} else {
			$sql_file_list = NULL;
		}
		require_once(FreePBX::Endpointman()->PHONE_MODULES_PATH . 'setup.php');

		$class = "endpoint_" . $row['directory'] . "_" . $row['cfg_dir'] . '_phone';
		$base_class = "endpoint_" . $row['directory'] . '_base';
		$master_class = "endpoint_base";
		
		/*********************************************************************************
		*** Quick Fix for FreePBX Distro
		*** I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
		**********************************************************************************/
		if (!class_exists($master_class)) {
			ProvisionerConfig::endpointsAutoload($master_class);
		}
		if (!class_exists($base_class)) {
			ProvisionerConfig::endpointsAutoload($base_class);
		}
		if (!class_exists($class)) {
			ProvisionerConfig::endpointsAutoload($class);
		}
		//end quick fix

		$phone_config = new $class();
		
		//TODO: remove
		$template_file_list[0]['value'] = "template_data_custom.xml";
		$template_file_list[0]['text'] = "template_data_custom.xml";

		$sql = "SELECT model FROM `endpointman_model_list` WHERE `product_id` LIKE '1-2' AND `enabled` = 1 AND `hidden` = 0";
		$data = sql($sql, 'getall', DB_FETCHMODE_ASSOC);
		$i = 1;
		foreach ($data as $list) {
			$template_file_list[$i]['value'] = "template_data_" . $list['model'] . "_custom.xml";
			$template_file_list[$i]['text'] = "template_data_" . $list['model'] . "_custom.xml";
		}
			
		if (isset($_REQUEST['temp_file'])) {
			$temp_file_tpl = 1;
		} else {
			$temp_file_tpl = NULL;
		}
	}
	
	
	
	
	
	
	if (isset($_REQUEST['file'])) {
		$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
		$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

		$config_files = explode(",", $row['config_files']);
		$file = FreePBX::Endpointman()->PHONE_MODULES_PATH . 'endpoint/' . $row['directory'] . "/" . $row['cfg_dir'] . "/" . $config_files[$_REQUEST['file']];
		if (isset($_REQUEST['config_text'])) {
			if (isset($_REQUEST['button_save'])) {
				$wfh = fopen($file, 'w');
                fwrite($wfh, $_REQUEST['config_text']);
                fclose($wfh);
                //$endpoint->message['poce'] = "Saved to Hard Drive!";
echo "Saved to Hard Drive!";
			} elseif (isset($_REQUEST['button_save_as'])) {
				$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ("' . addslashes($_REQUEST['save_as_name']) . '","' . addslashes($config_files[$_REQUEST['file']]) . '","' . $_REQUEST['product_select'] . '","' . addslashes($_REQUEST['config_text']) . '")';
                sql($sql);
                //$endpoint->message['poce'] = "Saved to Database!";
echo "Saved to Database!";
			}
		}
		
        $handle = fopen($file, "rb");
        $contents = fread($handle, filesize($file));
        fclose($handle);

        if (isset($_REQUEST['sendid'])) {
			$error = FreePBX::Endpointman()->submit_config($row['directory'], $row['cfg_dir'], $config_files[$_REQUEST['file']], $contents);
			//$endpoint->message['poce'] = 'Sent! Thanks :-)';
echo 'Sent! Thanks :-)';
		}
			
		$contents = FreePBX::Endpointman()->display_htmlspecialchars($contents);
		$config_data =  $contents;
        //$endpoint->tpl->assign("config_data", $contents);
            
		$save_as_name_value = $config_files[$_REQUEST['file']];
		//$endpoint->tpl->assign("save_as_name_value", $config_files[$_REQUEST['file']]);
		
		$filename = $config_files[$_REQUEST['file']];
		//$endpoint->tpl->assign("filename", $config_files[$_REQUEST['file']]);
		
		$sendidt = $_REQUEST['file'];
		//$endpoint->tpl->assign('sendid', $_REQUEST['file']);
		
		$type = 'file';
		//$endpoint->tpl->assign("type", 'file');
		
		$location = $file;
		//$endpoint->tpl->assign("location", $file);
		
		
		
		
	} elseif (isset($_REQUEST['sql'])) {
		if (isset($_REQUEST['config_text'])) {
			if (isset($_REQUEST['button_save'])) {
				$sql = "UPDATE endpointman_custom_configs SET data = '" . addslashes($_REQUEST['config_text']) . "' WHERE id = " . $_REQUEST['sql'];
                sql($sql);
                //$endpoint->message['poce'] = "Saved to Database!";
echo "Saved to Database!";
			} elseif (isset($_REQUEST['button_save_as'])) {
				$sql = 'SELECT original_name FROM endpointman_custom_configs WHERE id = ' . $_REQUEST['sql'];
                $file_name = sql($sql, 'getOne');

				$sql = "INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ('" . addslashes($_REQUEST['save_as_name']) . "','" . addslashes($file_name) . "','" . $_REQUEST['product_select'] . "','" . addslashes($_REQUEST['config_text']) . "')";
                sql($sql);
                //$endpoint->message['poce'] = "Saved to Database!";
echo "Saved to Database!";
			}
		}
        $sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $_REQUEST['sql'];
        $row = sql($sql, 'getrow', DB_FETCHMODE_ASSOC);

        if (isset($_REQUEST['sendid'])) {
			$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
            $row22 = sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
            FreePBX::Endpointman()->submit_config($row22['directory'], $row22['cfg_dir'], $row['original_name'], $row['data']);
            //$endpoint->message['poce'] = 'Sent! Thanks! :-)';
echo 'Sent! Thanks! :-)';
		}

        $row['data'] = FreePBX::Endpointman()->display_htmlspecialchars($row['data']);
        $config_data = $row['data'];
        //$endpoint->tpl->assign("config_data", $row['data']);
            
        $save_as_name_value = $row['name'];
        //$endpoint->tpl->assign("save_as_name_value", $row['name']);
        
        $filename =  $row['original_name'];
        //$endpoint->tpl->assign("filename", $row['original_name']);
        
        $sendidt = $_REQUEST['sql'];
        //$endpoint->tpl->assign('sendid', $_REQUEST['sql']);
        
        $type = 'sql';
        //$endpoint->tpl->assign("type", 'sql');
	}
	
	
	
	if (isset($_REQUEST['delete'])) {
		$sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $_REQUEST['sql'];
		sql($sql);
echo "Deleted!";
	}
?>



<div class="container-fluid">
    <div class="row">
        <div class="col-sm-9">
        	<br />
			<p><b>File:</b><code class='inline' id='poce_file_name_path'><?php echo (isset($location) ? $location : "No selected") ?></code></p>
			<br />
				
				<form method="post" action="">
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
        					<p><label><i class="fa fa-file-code-o"></i> Cantenido del archivo:</label></p>
          					<p><textarea name="config_text" id="config_textarea" rows="20"><?php echo (isset($config_data)? $config_data : ""); ?></textarea></p>
	        				<p>
	        					<button type="submit" class='btn btn-default' name="button_save" ><i class='fa fa-floppy-o'></i> <?php echo _('Save')?></button>
	        					<i class='icon-warning-sign'></i><font style="font-size: 0.8em; font-style: italic;">NOTE: File may be over-written during next package update. We suggest also using the <b>Share</b> button below to improve the next release.</font>
	        				</p>
							<?php if ($temp_file_tpl) { ?>
							<p>
								<button type="submit" class='btn btn-default' name="button_save_as" ><i class='fa fa-floppy-o'></i> <?php echo _('Save As...')?></button>
	          					<input type="text" name="save_as_name" id="save_as_name" value="<?php echo (isset($save_as_name_value)? $save_as_name_value : ""); ?>">
	        					<i class='icon-warning-sign'></i><font style="font-size: 0.8em; font-style: italic;">NOTE: File is permanently saved and not over-written during next package update.</font>
	        				</p>
							<?php } ?>
							<?php if (isset($type)) { ?>
	        				<p>
								<a href="config.php?display=epm_advanced&amp;subpage=poce&sendid=<?php echo $sendidt; ?>&amp;filename=<?php echo $filename; ?>&amp;product_select=<?php echo $_REQUEST['product_select']; ?>&amp;<?php echo $type.'='.$sendidt; ?>">
	          					<button type="button" class="btn btn-default"><i class="fa fa-upload"></i> <?php echo _('Share')?></button></a> Upload this configuration file to the <b>Provisioner.net Team</b>. Files shared are confidential and help improve the quality of releases.
	          				</p>
							<?php } ?>
						</div>
					</div>
				</div>
				</form>
				
			
        </div>
        <div class="col-sm-3 bootnav">
        	<br />
			<div class="list-group">
			<?php
				$sql = 'SELECT * FROM `endpointman_product_list` WHERE `hidden` = 0 AND `id` > 0';
				$product_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
				
				$content = load_view(__DIR__.'/epm_advanced/poce.views.bootnav.php', array('request' => $request, 'product_list' => $product_list));
				
				echo $content;
				unset ($product_list);
				unset ($sql);
			?>
            </div>
            
            <?php
            if ((count($file_list) > 0) or (count($template_file_list) > 0) or (count($sql_file_list) > 0)) { echo "<hr><br />"; }
            if (count($file_list) > 0) {
            	echo load_view(__DIR__.'/epm_advanced/poce.views.file_list.php', array('request' => $request, 'file_list' => $file_list));
            }
            if (count($template_file_list) > 0) {
            	echo load_view(__DIR__.'/epm_advanced/poce.views.file_list_template.php', array('request' => $request, 'template_file_list' => $template_file_list));
            }
            if (count($sql_file_list) > 0) {
            	echo load_view(__DIR__.'/epm_advanced/poce.views.file_list_sql.php', array('request' => $request, 'sql_file_list' => $sql_file_list));
            }
		?>
        </div>
    </div>
</div>











<!--
<form method="post" action="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&amp;product_select={$product_selected}&amp;phone_options=true">
{if condition="isset($options)"}	
{$options}
{/if}
</form>
-->