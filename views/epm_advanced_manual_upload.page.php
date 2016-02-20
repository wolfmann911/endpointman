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
	$path_tmp_dir = FreePBX::Endpointman()->PHONE_MODULES_PATH."temp/export/";
	
	$array_list_files = array();
	$array_list_exception= array(".", "..", ".htaccess");
	if(file_exists($path_tmp_dir)) 
	{
		if(is_dir($path_tmp_dir)) 
		{
			$l_files = scandir($path_tmp_dir, 1);
			foreach ($l_files as $archivo) {
				if (in_array($archivo, $array_list_exception)) { continue; }
				$pathandfile = $path_tmp_dir.$archivo;
				$brand = substr(pathinfo($archivo, PATHINFO_FILENAME), 0, -11);
				$ftime = substr(pathinfo($archivo, PATHINFO_FILENAME), -10);
				
				$array_list_files[$brand][] = array("brand" => $brand,
											"pathall" => $pathandfile, 
											"path" => $path_tmp_dir, 
											"file" => $archivo, 
											"filename" => pathinfo($archivo, PATHINFO_FILENAME), 
											"extension" => pathinfo($archivo, PATHINFO_EXTENSION),
											"timer" => $ftime,
											"mime_type" => mime_content_type($pathandfile),
											"is_dir" => is_dir($pathandfile),
											"is_file" => is_file($pathandfile),
											"is_link" => is_link($pathandfile),
											"readlink" => (is_link($pathandfile) == true ? readlink ($pathandfile) : NULL));
			}
			unset ($l_files);
		}
	}
	natsort($array_list_files);
?>
<div class="section-title" data-for="ma_up_ex_brand_package">
	<h3><?php echo _("Export Brand Packages") ?></h3>
</div>
<div class="section" data-id="ma_up_ex_brand_package">
	<div class="alert alert-info" role="alert"><?php echo _("Learn how to create your own brand package at "); ?><a target="_blank" href="http://www.provisioner.net/adding_new_phones">http://www.provisioner.net/adding_new_phones <i class='icon-globe'></i></a></div>
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group custom_box_import">
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
							<ul class="list-group">
							<?php 
							if (count($array_list_files) == 0) {
								echo '<li class="list-group-item">';
								echo '<span class="label label-default label-pill pull-xs-right">0</span>';
								echo '<i class="fa fa-file-archive-o"></i>&nbsp; '._("Empty list.");
								echo '</li>';
							}
							else {
								foreach ($array_list_files as $k => $v) 
								{
									echo '<li class="list-group-item" id="list_export_files">';
									
									echo '	<a data-toggle="collapse" href="#brand_'.$k.'" aria-expanded="false" aria-controls="brand_'.$k.'" class="collapse-item list-group-item">';
									echo '		<span class="label label-default label-pill pull-xs-right">'.count($v).'</span>';
									echo '		<i class="fa fa-expand"></i>&nbsp; '. $k;
									echo '</a>';
									echo '<div class="list-group collapse" id="brand_'.$k.'">';
									foreach ($v as $itemlist) {
										echo '<a class="list-group-item" href="config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables_file&file_package='.$itemlist['file'].'" target="_blank">';
										echo '	<span class="label label-default label-pill pull-xs-right">'.strftime("[%Y-%m-%d %H:%M:%S]", $itemlist['timer']).'</span>';
										echo '	<i class="fa fa-file-archive-o"></i>&nbsp; '.$itemlist['pathall'];
										echo '</a>';
									}
									echo '</div>';
									echo '</li>';
								}
							}
							?>
							</ul>
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