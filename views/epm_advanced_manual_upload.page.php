<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	$sql = "SELECT value FROM endpointman_global_vars WHERE var_name LIKE 'endpoint_vers'";
	$provisioner_ver = sql($sql, 'getOne');
	$provisioner_ver = date("d-M-Y", $provisioner_ver) . " at " . date("g:ia", $provisioner_ver);
?>

<div class="section-title" data-for="ma_up_im_package">
	<h3><?php echo _("Import Packages") ?></h3>
</div>
<div class="section custom_box_import_csv" data-id="ma_up_im_package">
	<div class="alert alert-info" role="alert"><?php echo _("Download updated releases from "); ?><a href="http://wiki.provisioner.net/index.php/Releases" target="_blank">http://wiki.provisioner.net/index.php/Releases <i class='icon-globe'></i></a></div>
	<font style="font-size: 0.8em">Local Date Last Modified: <?php echo $provisioner_ver; ?></font>
	<br /><br />
	
	<form action="" method="post" enctype="multipart/form-data" name="form1">
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
								<input id="fileField" name="package" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_provisioner' name="upload_provisioner" href="javascript:epm_config_tab_();"><i class='fa fa-upload'></i> <?php echo _('Import')?></a>
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
	
	<form action="" method="post" enctype="multipart/form-data" name="form1">
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
								<input id="fileField" name="package" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_brand' name="upload_brand" href="javascript:epm_config_tab_();"><i class='fa fa-upload'></i> <?php echo _('Import')?></a>
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
	$brand_ava = FreePBX::Endpointman()->brands_available();
?>


<div class="section-title" data-for="ma_up_ex_brand_package">
	<h3><?php echo _("Export Brand Packages") ?></h3>
</div>
<div class="section custom_box_import_csv" data-id="ma_up_ex_brand_package">
	<div class="alert alert-info" role="alert"><?php echo _("Learn how to create your own brand package at "); ?><a target="_blank" href="http://www.provisioner.net/adding_new_phones">http://www.provisioner.net/adding_new_phones <i class='icon-globe'></i></a></div>
	
	<form action="" method="post" enctype="multipart/form-data" name="form_brand_export_pack">
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
						
								<a class='btn btn-default' id='brand_export_pack' name="brand_export_pack" href="config.php?display=epm_advanced&subpage=iedl&command=export" target="_blank"><i class='fa fa-download'></i> <?php echo _('Export')?></a>
								<div class="styled-select">
									<select name="exp_brand">
										<?php
										foreach ($brand_ava as $row) {
											echo '<option value="'.$row['value'].'">'.$row['text'].'</option>';
										}
										?>
									</select>
								</div>
								

						
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
return;


        /*
		
		$uploads_dir = PHONE_MODULES_PATH . "temp";
		
        if (isset($_REQUEST['upload_provisioner'])) {

            $extension = pathinfo($_FILES["package"]["name"], PATHINFO_EXTENSION);
            if ($extension == "tgz") {
                if ($_FILES['package']['error'] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES["package"]["tmp_name"];
                    $name = $_FILES["package"]["name"];
                    move_uploaded_file($tmp_name, "$uploads_dir/$name");
                    $endpoint->tpl->assign("show_installer", 1);
                    $endpoint->tpl->assign("package", $name);
                    $endpoint->tpl->assign("type", "upload_provisioner");
                    $endpoint->tpl->assign("xml", 0);
                } else {
                    $endpoint->error['manual_upload'] = $endpoint->file_upload_error_message($_FILES['package']['error']);
                }
            } else {
                $endpoint->error['manual_upload'] = "Invalid File Extension";
            }
			
			
			
        } elseif (isset($_REQUEST['upload_brand'])) {
            $error = FALSE;
            $files_list = array();
            $i = 0;
            foreach ($_FILES as $files) {
                $extension = pathinfo($files["name"], PATHINFO_EXTENSION);
                if ($extension == "tgz") {
                    if ($files['error'] == UPLOAD_ERR_OK) {
                        $tmp_name = $files["tmp_name"];
                        $name = $files["name"];
                        move_uploaded_file($tmp_name, "$uploads_dir/$name");
                        $files_list[$i] = $name;
                        $i++;
                    } else {
                        $endpoint->error['manual_upload'] = $endpoint->file_upload_error_message($files['error']);
                        $error = TRUE;
                    }
                } else {
                    $endpoint->error['manual_upload'] = "Invalid File Extension";
                    $error = TRUE;
                }
            }
            if (!$error) {
                $endpoint->tpl->assign("show_installer", 1);
                $endpoint->tpl->assign("package", $files_list[0]);
                $endpoint->tpl->assign("type", "upload_brand");
            }
			
			
        } elseif (isset($_REQUEST['export_brand'])) {
            $endpoint->tpl->assign("show_installer", 1);
            $endpoint->tpl->assign("type", "export_brand");
            $endpoint->tpl->assign("package", $_REQUEST['exp_brand']);
        }
		*/
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