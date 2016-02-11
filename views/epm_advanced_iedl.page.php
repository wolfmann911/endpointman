<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<h4><?php echo _('Export CSV')?></h4>
<p><?php echo _('Export CSV file of devices')?>.</p>
<a href="{$exporter_address}"><button type="button" name="button_export"><i class='icon-mail-forward'></i> <?php echo _('Export')?></a></button>

<h4><?php echo _('Import CSV')?></h4>
<form name="form1" enctype="multipart/form-data" method="post" action="config.php?type=tool&display=epm_advanced&subpage=iedl&action=import">
  <p><?php echo _('Import CSV file of devices')?>.</p>
  <button type="submit" name="button_import"><i class='icon-mail-reply'></i> <?php echo _('Import')?></a></button>
  <label>
	<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
    <input type="file" name="import_csv" id="fileField">
  </label>
</form>
<font style="font-size: 0.8em"><i class='icon-warning-sign'></i><strong>Warning:</strong> The extensions need to be added into FreePBX before you import.</font></p>

<h5><?php echo _('CSV File Format')?></h5>
<code class='inline'>&lt;<?php echo _('MAC Address')?>&gt;,&lt;<?php echo _('Brand')?>&gt;,&lt;<?php echo _('Model')?>&gt;,&lt;<?php echo _('Extension')?>&gt;,&lt;<?php echo _('Line')?>&gt;</code>
<ul class='nobullets'>
<li><code class='inline'><?php echo _('MAC Address')?></code> - <?php echo _('is required')?></li>
<li><code class='inline'><?php echo _('Brand')?></code> - <?php echo _('can be blank')?></li>
<li><code class='inline'><?php echo _('Model')?></code> - <?php echo _('can be blank')?></li>
<li><code class='inline'><?php echo _('Extension')?></code> - <?php echo _('can be blank')?></li>
<li><code class='inline'><?php echo _('Line')?></code> - <?php echo _('can be blank')?></li>
</ul>
<h6><?php echo _('Examples')?>:</h6>
<code class='inline'>001122334455,Cisco/Linksys,7940,4321,1</code><br/>
<code class='inline'>112233445566,Cisco/Linksys,7945,,</code>


<?php
return;

$endpoint->tpl->assign("exporter_address", "config.php?type=tool&amp;display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=export.html.php&amp;module=endpointman&amp;rand=" . rand());
//Dave B's Q&D file upload security code (http://us2.php.net/manual/en/features.file-upload.php)
if ((isset($_REQUEST['button_import'])) AND ($action == "import")) {
	$allowedExtensions = array("csv", "txt");
	foreach ($_FILES as $file) {
		if ($file['tmp_name'] > '') {
			if (!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
				$endpoint->message['iedl'] = "We support only CVS and TXT files";
			} else {
				$uploaddir = LOCAL_PATH;
				$uploadfile = $uploaddir . basename($_FILES['import_csv']['name']);
				if (move_uploaded_file($_FILES['import_csv']['tmp_name'], $uploadfile)) {
					//Parse the uploaded file
					$handle = fopen(LOCAL_PATH . $_FILES['import_csv']['name'], "r");
					$i = 1;
					while (($device = fgetcsv($handle, filesize(LOCAL_PATH . $_FILES['import_csv']['name']))) !== FALSE) {
						if ($device[0] != "") {
							if ($mac = $endpoint->mac_check_clean($device[0])) {
								$sql = "SELECT id FROM endpointman_brand_list WHERE name LIKE '%" . $device[1] . "%' LIMIT 1";
								$res = $endpoint->eda->sql($sql);
								if ($res->numRows() > 0) {
									$brand_id = $endpoint->eda->sql($sql, 'getOne');
									$brand_id = $brand_id[0];

									$sql_model = "SELECT id FROM endpointman_model_list WHERE brand = " . $brand_id . " AND model LIKE '%" . $device[2] . "%' LIMIT 1";
									$sql_ext = "SELECT extension, name FROM users WHERE extension LIKE '%" . $device[3] . "%' LIMIT 1";

									$line_id = isset($device[4]) ? $device[4] : 1;

									$res_model = $endpoint->eda->sql($sql_model);
									if ($res_model->numRows()) {
										$model_id = $endpoint->eda->sql($sql_model, 'getRow', DB_FETCHMODE_ASSOC);
										$model_id = $model_id['id'];

										$res_ext = $endpoint->eda->sql($sql_ext);
										if ($res_ext->numRows()) {
											$ext = $endpoint->eda->sql($sql_ext, 'getRow', DB_FETCHMODE_ASSOC);
											$description = $ext['name'];
											$ext = $ext['extension'];

											$endpoint->add_device($mac, $model_id, $ext, 0, $line_id, $description);
										} else {
											$endpoint->error['csv_upload'] .= "Invalid Extension Specified on line " . $i . "<br />";
										}
									} else {
										$endpoint->error['csv_upload'] .= "Invalid Model Specified on line " . $i . "<br />";
									}
								} else {
									$endpoint->error['csv_upload'] .= "Invalid Brand Specified on line " . $i . "<br />";
								}
							} else {
								$endpoint->error['csv_upload'] .= "Invalid Mac on line " . $i . "<br />";
							}
						}
						$i++;
					}
					fclose($handle);
					unlink(LOCAL_PATH . $_FILES['import_csv']['name']);
					$endpoint->message['file_upload'] = "Please reboot & rebuild all imported phones<br />";
				} else {
					$endpoint->error['file_upload'] = "Possible file upload attack!";
				}
			}
		}
	}
} elseif (isset($_REQUEST['action'])) {
	$endpoint->error['iedl'] = "No File uploaded";
}	
?>