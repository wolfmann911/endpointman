<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	
	$sql = "SELECT value FROM endpointman_global_vars WHERE var_name LIKE 'endpoint_vers'";
	$provisioner_ver = sql($sql, 'getOne');
	$provisioner_ver = date("d-M-Y", $provisioner_ver) . " at " . date("g:ia", $provisioner_ver);
	
	$brand_ava = FreePBX::Endpointman()->brands_available();
?>
<h4>Import Packages</h4>
<font style="font-size: 0.8em"><em>Download updated releases from </em><a href="http://wiki.provisioner.net/index.php/Releases" target="_blank">http://wiki.provisioner.net/index.php/Releases <i class='icon-globe'></i></a></font>
<br><font style="font-size: 0.8em">Local Date Last Modified: <?php echo $provisioner_ver; ?></font>
<p>
<table>
  <tr>
    <form action="" method="post" enctype="multipart/form-data" name="form1">
    <td>
      <label><b>Provisioner Package</b> (<code>.tgz</code>):
    </td><td>
      <input type="file" name="package" id="fileField">
      </label>
    </td><td>
    <button type="submit" name="upload_provisioner"><i class="icon-reply"></i> <?php echo _('Import')?></button>
    <td>
    </form>
  </tr>
  <tr>
    <form action="" method="post" enctype="multipart/form-data" name="form1">
    <td>
      <label><b>Brand Package</b> (<code>.tgz</code>):
    </td><td>
        <input type="file" name="package" id="fileField">
      </label>
    </td><td>
    <button type="submit" name="upload_brand"><i class="icon-reply"></i> <?php echo _('Import')?></button>
    </td>
    </form>
  </tr>
</table>
<br/>
<br/>

<h4>Export Brand Packages</h4>
<font style="font-size: 0.8em"><em>Learn how to create your own brand package at </em><a target="_blank" href="http://www.provisioner.net/adding_new_phones">http://www.provisioner.net/adding_new_phones <i class='icon-globe'></i></a></font>
<p>
<form action="" method="post" enctype="multipart/form-data" name="export">
    <select name="exp_brand">
	<?php
	foreach ($brand_ava as $row) {
		echo '<option value="'.$row['value'].'">'.$row['text'].'</option>';
	}
	?>
    </select>
    <button type="submit" name="export_brand"><i class='icon-share-alt'></i> <?php echo _('Export')?></button>
</form>
</p>




<?php

return;


        
		
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
?>















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