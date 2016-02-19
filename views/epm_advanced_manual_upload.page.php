<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	$sql = "SELECT value FROM endpointman_global_vars WHERE var_name LIKE 'endpoint_vers'";
	$provisioner_ver = sql($sql, 'getOne');
	$provisioner_ver = date("d-M-Y", $provisioner_ver) . " at " . date("g:ia", $provisioner_ver);
?>

<div class="section-title" data-for="ma_up_im_package">
	<h3><?php echo _("Import Packages") ?></h3>
</div>
<div class="section custom_box_import" data-id="ma_up_im_package">
	<div class="alert alert-info" role="alert"><?php echo _("Download updated releases from "); ?><a href="http://wiki.provisioner.net/index.php/Releases" target="_blank">http://wiki.provisioner.net/index.php/Releases <i class='icon-globe'></i></a></div>
	<font style="font-size: 0.8em">Local Date Last Modified: <?php echo $provisioner_ver; ?></font>
	<br /><br />
	
	<form name="manual_upload_form_import_provisioner" enctype="multipart/form-data" method="post">
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="provisioner_pack"><?php echo _("Provisioner Package")?> (<code>.tgz</code>)</label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="provisioner_pack"></i>
						</div>
						<div class="col-md-9 text-right">
							
							<span>
								<input id="fileField" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_provisioner' name="upload_provisioner" href="javascript:epm_config_tab_manual_upload_bt_upload('upload_provisioner', 'manual_upload_form_import_provisioner');"><i class='fa fa-upload'></i> <?php echo _('Import')?></a>
							</span>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="provisioner_pack-help"><?php echo _("Import a package Provisioner in manual mode.")?></span>
			</div>
		</div>
	</div>
	</form>
	
	<form name="manual_upload_form_import_brand" enctype="multipart/form-data" method="post">
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_pack"><?php echo _("Brand Package")?> (<code>.tgz</code>)</label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_pack"></i>
						</div>
						<div class="col-md-9 text-right">
							<span>
								<input id="fileField" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_brand' name="upload_brand" href="javascript:epm_config_tab_manual_upload_bt_upload('upload_brand', 'manual_upload_form_import_brand');"><i class='fa fa-upload'></i> <?php echo _('Import')?></a>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_pack-help"><?php echo _("Import a package brand in manual mode.")?></span>
			</div>
		</div>
	</div>
	</form>
</div>



<?php
	$brand_ava = FreePBX::Endpointman()->brands_available("",false);
	$path_tmp_dir = FreePBX::Endpointman()->PHONE_MODULES_PATH."/temp/export/";
	
	$array_list_files = array();
	$array_list_exception= array(".", "..", ".htaccess");
	if(file_exists($path_tmp_dir)) 
	{
		if(is_dir($path_tmp_dir)) 
		{
			if($dir = opendir($path_tmp_dir))
			{
				while(($archivo = readdir($dir)) !== false)
				{
					if (in_array($archivo, $array_list_exception)) { continue; }
					$pathandfile = $path_tmp_dir.$archivo;
					$array_list_files[] = array("pathall" => $pathandfile, 
												"path" => $path_tmp_dir, 
												"file" => $archivo, 
												"filename" => pathinfo($archivo, PATHINFO_FILENAME), 
												"extension" => pathinfo($archivo, PATHINFO_EXTENSION),
												"mime_type" => mime_content_type($pathandfile),
												"is_dir" => is_dir($pathandfile),
												"is_file" => is_file($pathandfile),
												"is_link" => is_link($pathandfile),
												"readlink" => (is_link($pathandfile) == true ? readlink ($pathandfile) : NULL));
				}
				closedir($dir);
			}
		}
	}
?>
<div class="section-title" data-for="ma_up_ex_brand_package">
	<h3><?php echo _("Export Brand Packages") ?></h3>
</div>
<div class="section custom_box_import" data-id="ma_up_ex_brand_package">
	<div class="alert alert-info" role="alert"><?php echo _("Learn how to create your own brand package at "); ?><a target="_blank" href="http://www.provisioner.net/adding_new_phones">http://www.provisioner.net/adding_new_phones <i class='icon-globe'></i></a></div>
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_export_pack"><?php echo _("Brand's Available")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_export_pack"></i>
						</div>
						<div class="col-md-9 text-right">
						
							<?php if ($brand_ava == "") : ?>
								<div class="alert alert-info text-left" role="alert"> <strong><?php echo _("Heads up!"); ?></strong> <?php echo _("List Bran's Availables emtry."); ?> <i class='icon-globe'></i></div>
							<?php else: ?>
							<span>
								<div class="styled-select">
									<select name="brand_export_pack_selected" id="brand_export_pack_selected">
										<?php
											echo '<option value="">'._('Select Brand:').'</option>';
											foreach ($brand_ava as $row) {
												echo '<option value="'.$row['value'].'">'.$row['text'].'</option>';
											}
										?>
									</select>
								</div>
							</span>
							<span>
								<a class='btn btn-default' id='brand_export_pack' name="brand_export_pack" href="javascript:epm_config_tab_manual_upload_bt_explor_brand();"><i class='fa fa-download'></i> <?php echo _('Export')?></a>
							</span>
							<?php endif ?>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_export_pack-help"><?php echo _("Explor a package brand's availables.")?></span>
			</div>
		</div>
	</div>
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_export_pack_list"><?php echo _("List of other exports")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_export_pack_list"></i>
						</div>
						<div class="col-md-9">
							<div class="list-group">
							<?php
								
								if (count($array_list_files) == 0) {
									echo '<div class="list-group-item"><i class="fa fa-file-archive-o"></i>&nbsp; '._("Empty list.").'</div>';
								}
								else {
									foreach ($array_list_files as $itemlist) {
										echo '<a class="list-group-item" href="config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables_file&file_package='.$itemlist['file'].'" target="_blank"><i class="fa fa-file-archive-o"></i>&nbsp; '.$itemlist['pathall'].'</a>';
									}
								}
							?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_export_pack_list-help"><?php echo _("List packages generated in other exports.")?></span>
			</div>
		</div>
	</div>
	
</div>
<?php
	unset ($brand_ava);
	unset ($path_tmp_dir);
	unset ($array_list_files);
?>




    


<?php
return;
?>











<!--

{if condition="isset($show_installer)"}
<script>
var box;
function process_module_actions(actions) {
    $(document).ready(function() {
	urlStr = "config.php?display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=installer.html.php&amp;module=endpointman&amp;type=manual_install&amp;package={$package}&amp;xml={$xml}&amp;install_type={$type}";
	urlStr += "&amp;rand="+Math.random ( );
        for (var i in actions) {
            urlStr += "&amp;moduleaction["+i+"]="+actions[i];
        }
        box = $('<div></div>')
        .html('<iframe height="100%" frameBorder="0" src="'+urlStr+'"></iframe>')
        .dialog({
            title: 'Status - Please Wait',
            resizable: false,
            modal: true,
            position: ['center', 50],
            width: '400px',
            height: 230,
            close: function (e) {
                close_module_actions(true);
                $(e.target).dialog("destroy").remove();
            }
        });
    });
}
function close_module_actions(goback) {
        box.dialog("destroy").remove();
        if (goback) {
            location.href = 'config.php?type=tool&display=epm_advanced&subpage=manual_upload';
        }
}
process_module_actions();
</script>

<div id="moduleBox" style="display:none;"></div>
{/if}

-->