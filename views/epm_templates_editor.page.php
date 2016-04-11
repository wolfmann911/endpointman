<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	if ((! isset($_REQUEST['idsel'])) || (! isset($_REQUEST['custom'])))
	{
		echo '<div class="alert alert-warning" role="alert">';
		echo '<strong>'._("Warning!").'</strong>'.(" No select ID o Custom!");
		echo '</div>';
		return;
	}
	$dtemplate = FreePBX::Endpointman()->epm_templates->edit_template_display($_REQUEST['idsel'],$_REQUEST['custom']);
	$request = $_REQUEST;
?>


	<!--
	if ($request['custom'] != 0): 
	<form action="config.php?type=tool&display=epm_templates" method="post">
	{if condition="isset($silent_mode)"}
	<input name="silent_mode" id="silent_mode" type="hidden" value="1">
	{/if}
	-->




	<div class="">
		<div class="row">
			<div class="col-sm-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="fa  fa-info-circle fa-lg"></i> <?php echo _("Info Template")?></h3>
					</div>
					<div class="panel-body">
					
					
						<table class="table">
							<tr>
								<td><b><?php echo _("Template Name:")?></b></td>
								<td><?php if ($request['custom'] != 0): ?>Custom Template: Extension <?php echo $dtemplate['ext']; ?><?php else: ?><?php echo $dtemplate['template_name']; ?><?php endif; ?></td>
							</tr>
							<tr>
								<td><b><?php echo _("Product Line:")?></b></td>
								<td><?php echo $dtemplate['product']; ?></td>
							</tr>
							<tr>
								<td><b><?php echo _("Clone of Model:")?></b></td>
								<?php if ($request['custom'] != 0): ?>
								<td><?php echo $dtemplate['model'] ?></td>
								<?php else: ?>
								<td>
								<select class="form-control" name="model_list" id="model_list" disabled>
									<?php
									foreach($dtemplate['models_ava'] as $row) {
										echo '<option value="'.$row['value'].'" '.(!empty($row['selected']) ? "selected" : "").'>'.$row['text'].'</option>';
									}
									?>
								</select>
								</td>
								<?php endif; ?>
							</tr>
							<tr>
								<td><b><?php echo _("Display:")?></b></td>
								<td>
									<!-- 
									{if condition="isset($silent_mode)"}
									onchange="window.location.href='config.php?display=epm_config&quietmode=1&handler=file&file=popup.html.php&module=endpointman&pop_type=edit_template&edit_id={$hidden_id}&model_list=126&template_list=0&rand='+ new Date().getTime() + '&maxlines='+this.options[this.selectedIndex].value"
									{else}
									onchange="window.location.href='config.php?type=tool&edit_template=true&display=epm_templates&custom='+ document.getElementById('custom').value +'&id='+ document.getElementById('id').value +'&maxlines='+this.options[this.selectedIndex].value"
									{/if}
							 		-->
									<select class="form-control" name="area_list" id="area_list">
										<option value=""></option>
										<?php 	
										foreach($dtemplate['area_ava'] as $row) {
											echo '<option value="'.$row['value'].'" '.(!empty($row['selected']) ? "selected" : "").'>'.$row['text'].'</option>';
										}
										?>
									</select>
									<strong><?php echo _('Line settings on this page')?></strong><i><font size="-2"> (Note: This is NOT the number of supported lines on the phone(s))</font></i>
								</td>
							</tr>
						</table>
						
						
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="fa  fa-file-code-o fa-lg"></i> <?php echo _("Settings")?></h3>
					</div>
					<div class="panel-body">
					
					
						<table class="table">
							<tr>
								<td colspan="2">
									<!-- 
									onclick="return popitup3('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=global_over')"
									 -->
									<a class="btn btn-default form-control" href="#" ><i class='fa fa-pencil-square-o'></i> <?php echo _('Edit Global Settings Overrides')?></a>
								</td>
							</tr>
							
							
							
							<?php 
							if ($dtemplate['area_ava'] != 0) {
						    	foreach($dtemplate['alt_configs'] as $row): ?>
						    		<tr>
										<td><b><?php echo _("Edit File Configurations for:")?></b></td>
										<td>
											<a href="#" onclick="return popitup('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit', '<?php echo $row['name']; ?>')">
							            		<code><?php echo $row['name']; ?></code> <i class='fa fa-pencil fa-lg' ALT='<?php echo _('Edit')?> <?php echo $row['name']; ?>'></i>
							            	</a>
										</td>
									</tr>
									
									<tr>
										<td><b><?php echo _("Select Alternative File Configurations for ")?></b><code><?php echo $row['name']; ?></code></td>
										<td>
											<select class="form-control" name="<?php echo $row['name']; ?>" id="altconfig_<?php echo $row['name']; ?>">';
							            		<option value="0_<?php echo $row['name']; ?>"><?php echo $row['name']; ?> (No Change)</option>';
							            		<?php
							            		if (isset($row['list'])) {
								            		foreach($row['list'] as $srow) {
								                		echo '<option value="'.$srow['id'].'_'.$srow['name'].'" '.(isset($srow['selected']) ? "selected" : "" ).'>'.$srow['name'].'</option>';
								            		}
							            		}
												?>
							            	</select>
										</td>
									</tr>
								<?php 
							   	endforeach;
		    				}
		    				
		    				foreach($dtemplate['only_configs'] as $row): ?>
								<tr>
									<td><b><?php echo _("Edit File Configurations for:")?></b></td>
									<td>
										<a href='#' onclick='return popitup2("config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit", "<?php echo $row['name']?>")'>
											<code><?php echo $row['name']?></code>&nbsp;<i class='fa fa-pencil fa-lg'></i>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
						
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="">
		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="fa  fa-code"></i> <?php echo _("Example of Variables allowed in boxes")?></h3>
					</div>
					<div class="panel-body">
						<ul class='nobullets'>
							<li><code class='inline'>{$username.line.1}</code> <?php echo _('Device\'s Username (usually the extension)')?></li>
							<li><code class='inline'>{$displayname.line.1}</code> <?php echo _('Device\'s Description in FreePBX (Usually the Full Name)')?></li>
							<li><code class='inline'>{$server_host.line.1}</code> <?php echo _('Server IP For Line 1')?></li>
							<li><code class='inline'>{$server_port.line.1}</code> <?php echo _('Server Port For Line 1')?></li>
							<li><code class='inline'>{$mac}</code> <?php echo _('Device\'s Mac Address')?></li>
							<li><code class='inline'>{$model}</code> <?php echo _('Device\'s Model')?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	
	<div class="coda-slider-wrapper">
		<div class="coda-slider preload" id="coda-slider-9">
		<?php 
		foreach($dtemplate['template_editor'] as $row) {
		?>
			<div class="panel">
				<div class="panel-wrapper">
					<h2 class="title"><?php echo $row['title'];?></h2>
					
					
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<?php
						foreach($row['data'] as $srow) {
							echo "<tr>";
							switch ($srow['type']) {
								case 'input':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><input type="text" name="'.$srow['key'].'" id="'.$srow['key'].'" value="'.$srow['value'].'" size="'.(isset($srow['max_chars']) ? $srow['max_chars'] : "90" ).'">';
									break;
									
								case 'textarea':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><textarea rows="'.(isset($srow['rows']) ? $srow['rows'] : "2" ).'" cols="'.(isset($srow['cols']) ? $srow['cols'] : "20" ).'" name="'.$srow['key'].'" id="'.$srow['key'].'">'.$srow['value'].'</textarea>';
									break;
									
								case 'radio':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap>';
									foreach($srow['data'] as $lrow) {
										echo '[<label>';
										echo (! isset($lrow['tooltip']) ? $lrow['description'] : '<a href="#" class="info">'.$lrow['description'].'<span>'.$lrow['tooltip'].'</span></a>').':';
										//echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" '.(array_key_exists('checked', $lrow['value']) ? $lrow['checked'] : '').' >';
										echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" >';
										echo '</label>]';
									}
									break;
									
								case 'list':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap>';
									echo '<select name="'.$srow['key'].'" id="'.$srow['key'].'">';
									foreach($srow['data'] as $lrow) 
									{
										//echo '<option value="'.$lrow['value'].'" '.(array_key_exists('selected',$lrow['selected'])? $lrow['value'] : '').' >'.$lrow['description'].'</option>';
										echo '<option value="'.$lrow['value'].'" >'.$lrow['description'].'</option>';
									}
									echo '</select>';
									break;
									
									
								case 'checkbox':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><input type="checkbox" name="'.$srow['key'].'" id="'.$srow['key'].'" value="'.$srow['value'].'">';
									break;
									
								case 'break':
									echo '<td nowrap colspan="2">&nbsp;';
									break;
										
								case 'group':
									echo '<td nowrap colspan="2"><hr><h3>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</H3>';
									break;
											
								case 'header':
									echo '<td nowrap colspan="2"><strong>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</strong>';
									break;
							}
							
							if (isset($srow['aried'])) {
								echo '<label><input type="checkbox" name="ari_'.$srow['ari']['key'].'" '.(isset($srow['ari']['checked']) ? $srow['ari']['checked'] : '' ).' >End User Editable (<a href="http://projects.colsolgrp.net/documents/29" target="_blank">Through ARI Module</a>)</label></td>';
							}
							else {
								echo "</td>";
							}
							echo "</tr>";
						}
						?>
					</table>
					
					
				</div>
			</div>
		<?php	
		}
		?>
		</div><!-- .coda-slider -->
	</div><!-- .coda-slider-wrapper -->
	<input name="id" id="id" type="hidden" value="<?php echo $request['idsel'] ?>">
	<input name="custom" id="custom" type="hidden" value="<?php echo $request['custom'] ?>">
	
	<label>Reboot Phone(s) <input type='checkbox' name='epm_reboot'></label>
	<br />
	<button type="submit" name="button_save_template"><i class='icon-save blue'></i> <?php echo _('Save Template');?></button>


<?php 

return;














/*
 elseif((isset($_REQUEST['button_save_template'])) AND (isset($_REQUEST['custom']))) {
 	$endpoint->save_template($_REQUEST['id'],$_REQUEST['custom'],$_REQUEST);
 	$default_display = TRUE;
 	if(empty($endpoint->error)) {
 		$endpoint->message['general'] = _('Saved');
 	}
 }


if($default_display) {
	$sql = 'SELECT endpointman_template_list.*, endpointman_product_list.short_name as model_class, endpointman_model_list.model as model_clone, endpointman_model_list.enabled FROM endpointman_template_list, endpointman_model_list, endpointman_product_list WHERE endpointman_model_list.hidden = 0 AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.product_id = endpointman_product_list.id';
 	$template_list =& $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
 	$i = 0;
 	$row_out = array();
 	foreach($template_list as $row) {
 		$row_out[$i] = $row;
 		$row_out[$i]['custom'] = 0;
 		if(!$row['enabled']) {
 			$row_out[$i]['model_clone'] = $row_out[$i]['model_clone']."<i>(Disabled)</i>";
 		}
 		$i++;
	}
	$sql = 'SELECT endpointman_mac_list.mac, endpointman_mac_list.id, endpointman_mac_list.model, endpointman_model_list.model as model_clone, endpointman_product_list.short_name as model_class FROM endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE  endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.global_custom_cfg_data IS NOT NULL AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_mac_list.template_id = 0';
	$template_list =& $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
	foreach($template_list as $row) {
		$sql = 'SELECT  description , line FROM  endpointman_line_list WHERE  mac_id ='. $row['id'].' ORDER BY line ASC';
		$line_list =& $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
		$description = "";
		$c = 0;
		foreach($line_list as $line_row) {
			if($c > 0) {
				$description .= ", ";
			}
			$description .= $line_row['description'];
			$c++;
		}
		$row_out[$i] = $row;
		$row_out[$i]['custom'] = 1;
		$row_out[$i]['name'] = $row['mac'] . "-(" .$description.")";
		$i++;
	}
	
 	$sql = "SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";
 	$template_list =& $db->getAll($sql, array(), DB_FETCHMODE_ASSOC);
 	$i = 1;
 	$class_row[0]['value'] = 0;
 	$class_row[0]['text'] = "";
 	foreach($template_list as $row) {
 		$class_row[$i]['value'] = $row['id'];
 		$class_row[$i]['text'] = $row['short_name'];
 		$i++;
 	}
 	$endpoint->tpl->assign("amp_conf_serial", "1");
 	$endpoint->tpl->assign("templates_list", $row_out);
 	$endpoint->tpl->assign("class_list", $class_row);
 	$endpoint->tpl->assign("no_add", $no_add);
 	$endpoint->tpl->assign("debug", $debug);
 }
 */
?>

<script language="javascript" type="text/javascript">
    $().ready(function() {
        $('#coda-slider-9').codaSlider({
            dynamicArrows: false,
            continuous: false
        });
    });
    function Reload() {
        window.location.reload();
    }
        function popitup(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=' + document.getElementById('altconfig_'+ name).value + '&rand=' + new Date().getTime(),'name','height=710,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
        function popitup2(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
        function popitup3(url) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
</script>