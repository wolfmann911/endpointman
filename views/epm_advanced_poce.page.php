<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>


<h4><?php echo _("File Configuration Editor")?></h4>
<form method="post" action="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce">
  <button type="submit" name="button_select"><i class="icon-retweet"></i> <?php echo _('Select')?></button>
  <select name="product_select" id="product_select">
	{loop name="product_list"}
	<option value="{$value.value}" {if condition="isset($value.selected)"}selected='selected'{/if}>{$value.text}</option>
	{/loop}
  </select>
</form>
<form method="post" action="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&amp;product_select={$product_selected}&amp;phone_options=true">
{if condition="isset($options)"}	
{$options}
{/if}
</form>
<table width="97%" border="0" cellspacing="4" cellpadding="4">
  <tr>
    <td width="150px" align='right'>{if condition="isset($location)"}<b>File:</b>{/if}</td>
    <td>{if condition="isset($location)"}<code class='inline'>{$location}</code>{/if}</td>
  </tr>
  <tr>
    <td valign="top" width="150px">
        <!--
        <u><?php echo _("Custom Template Files")?></u><br />
	{loop name="template_file_list"}
	<a href="config.php{$web_vars}&display=epm_advanced&subpage=poce&product_select={$product_selected}&temp_file={$value.value}">{$value.text}</a><br />
	{/loop}
	<hr>
        -->
	<h5><?php echo _("Local File Configs")?></h5>
	{loop name="file_list"}
	<a href="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&amp;product_select={$product_selected}&amp;file={$value.value}"><code style="font-size: 0.8em">{$value.text}</code></a><br />
	{/loop}
	<h5><?php echo _("User File Configs")?></h5>
	{if condition="isset($sql_file_list)"}
		{loop name="sql_file_list"}
		<a href="config.php?type=tool&display=epm_advanced&subpage=poce&product_select={$product_selected}&sql={$value.value}"><code style="font-size: 0.8em">{$value.text}</code></a> <a href="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&product_select={$product_selected}&amp;sql={$value.value}&amp;delete=yes"><i class='icon-remove red' alt='<?php echo _('Delete')?>'></i></a>
        <br><font style="font-size:0.8em"> [ref: <code>{$value.ref}</code>]</font>
		<br />
		{/loop}
	{/if}
	</td>
    <td>
      <form method="post" action="">
        <label>
          <textarea name="config_text" id="textarea" cols="100" rows="30" wrap="off">{if condition="isset($config_data)"}{$config_data}{/if}</textarea>
        </label>
        <p>
          <label>
            <button type="submit" name="button_save"><i class="icon-save blue"></i> <?php echo _('Save')?></button> <i class='icon-warning-sign'></i><font style="font-size: 0.8em; font-style: italic;">NOTE: File may be over-written during next package update. We suggest also using the <b>Share</b> button below to improve the next release.</font>
          </label>
        </p><p>
        {if condition="!isset($temp_file)"}
        <label>
          <button type="submit" name="button_save_as"><i class="icon-save blue"></i> <?php echo _('Save As')?>...</button>
        </label>
        <label>
          <input type="text" name="save_as_name" id="save_as_name" value="{if condition="isset($save_as_name_value)"}{$save_as_name_value}{/if}">
        </label> <i class='icon-warning-sign'></i><font style="font-size: 0.8em; font-style: italic;">NOTE: File is permanently saved and not over-written during next package update.</font>
        {/if}
        {if condition="isset($type)"}
        </p><p>
          <a href="config.php?type=tool&amp;display=epm_advanced&amp;subpage=poce&sendid={$sendid}&amp;filename={$filename}&amp;product_select={$product_selected}&amp;{if condition="$type == 'sql'"}sql={$sendid}{else}file={$sendid}{/if}">
          <button type="button" class="button_Enable"><i class="icon-upload-alt green"></i> Share</button></a> Upload this configuration file to the <b>Provisioner.net Team</b>. Files shared are confidential and help improve the quality of releases.
        {/if}
        </p>
      </form>
    </td>
  </tr>
</table>
<script>
    $(document).ready(function() {
        var editor = CodeMirror.fromTextArea(document.getElementById("textarea"), {lineWrapping: true, lineNumbers: true, mode: {name: "xml", htmlMode: true}});
    });
</script>










<?php

return;





  $sql = 'SELECT * FROM `endpointman_product_list` WHERE `hidden` = 0 AND `id` > 0';
        $data = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        $i = 0;
        foreach ($data as $row) {
            $product_list[$i]['value'] = $row['id'];
            $product_list[$i]['text'] = $row['long_name'];
            if ((isset($_REQUEST['product_select'])) AND ($_REQUEST['product_select'] == $row['id'])) {
                $product_list[$i]['selected'] = 1;
            }
            $i++;
        }
        if (isset($_REQUEST['delete'])) {
            $sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $_REQUEST['sql'];
            $endpoint->eda->sql($sql);
            $endpoint->message['poce'] = "Deleted!";
        }
        if (isset($_REQUEST['file'])) {
            $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
            $row = $endpoint->eda->sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

            $config_files = explode(",", $row['config_files']);
            $file = PHONE_MODULES_PATH . 'endpoint/' . $row['directory'] . "/" . $row['cfg_dir'] . "/" . $config_files[$_REQUEST['file']];
            if (isset($_REQUEST['config_text'])) {
                if (isset($_REQUEST['button_save'])) {
                    $wfh = fopen($file, 'w');
                    fwrite($wfh, $_REQUEST['config_text']);
                    fclose($wfh);
                    $endpoint->message['poce'] = "Saved to Hard Drive!";
                } elseif (isset($_REQUEST['button_save_as'])) {
                    $sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ("' . addslashes($_REQUEST['save_as_name']) . '","' . addslashes($config_files[$_REQUEST['file']]) . '","' . $_REQUEST['product_select'] . '","' . addslashes($_REQUEST['config_text']) . '")';
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                }
            }

            $handle = fopen($file, "rb");
            $contents = fread($handle, filesize($file));
            fclose($handle);

            if (isset($_REQUEST['sendid'])) {
                $error = $endpoint->submit_config($row['directory'], $row['cfg_dir'], $config_files[$_REQUEST['file']], $contents);
                $endpoint->message['poce'] = 'Sent! Thanks :-)';
            }

            $endpoint->tpl->assign("save_as_name_value", $config_files[$_REQUEST['file']]);

            $contents = $endpoint->display_htmlspecialchars($contents);

            $endpoint->tpl->assign("config_data", $contents);
            $endpoint->tpl->assign("filename", $config_files[$_REQUEST['file']]);
            $endpoint->tpl->assign('sendid', $_REQUEST['file']);
            $endpoint->tpl->assign("type", 'file');
            $endpoint->tpl->assign("location", $file);
        } elseif (isset($_REQUEST['sql'])) {
            if (isset($_REQUEST['config_text'])) {
                if (isset($_REQUEST['button_save'])) {
                    $sql = "UPDATE endpointman_custom_configs SET data = '" . addslashes($_REQUEST['config_text']) . "' WHERE id = " . $_REQUEST['sql'];
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                } elseif (isset($_REQUEST['button_save_as'])) {
                    $sql = 'SELECT original_name FROM endpointman_custom_configs WHERE id = ' . $_REQUEST['sql'];
                    $file_name = $endpoint->eda->sql($sql, 'getOne');

                    $sql = "INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ('" . addslashes($_REQUEST['save_as_name']) . "','" . addslashes($file_name) . "','" . $_REQUEST['product_select'] . "','" . addslashes($_REQUEST['config_text']) . "')";
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                }
            }
            $sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $_REQUEST['sql'];
            $row = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);

            if (isset($_REQUEST['sendid'])) {
                $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
                $row22 = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
                $endpoint->submit_config($row22['directory'], $row22['cfg_dir'], $row['original_name'], $row['data']);
                $endpoint->message['poce'] = 'Sent! Thanks! :-)';
            }

            $row['data'] = $endpoint->display_htmlspecialchars($row['data']);

            $endpoint->tpl->assign("save_as_name_value", $row['name']);
            $endpoint->tpl->assign("filename", $row['original_name']);
            $endpoint->tpl->assign('sendid', $_REQUEST['sql']);
            $endpoint->tpl->assign("type", 'sql');
            $endpoint->tpl->assign("config_data", $row['data']);
        }
        if (isset($_REQUEST['product_select'])) {
            $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id ='" . $_REQUEST['product_select'] . "'";

            $row = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
            $config_files = explode(",", $row['config_files']);
            $i = 0;
            foreach ($config_files as $config_files_data) {
                $file_list[$i]['value'] = $i;
                $file_list[$i]['text'] = $config_files_data;
                $i++;
            }
            $sql = "SELECT * FROM endpointman_custom_configs WHERE product_id = '" . $_REQUEST['product_select'] . "'";
            $res = & $endpoint->eda->sql($sql);
            $i = 0;
            if ($res->numRows()) {
                $data = & $endpoint->eda->sql($sql, 'getall', DB_FETCHMODE_ASSOC);
                foreach ($data as $row2) {
                    $sql_file_list[$i]['value'] = $row2['id'];
                    $sql_file_list[$i]['text'] = $row2['name'];
                    $sql_file_list[$i]['ref'] = $row2['original_name'];
                    $i++;
                }
            } else {
                $sql_file_list = NULL;
            }
            require(PHONE_MODULES_PATH . 'setup.php');

            $class = "endpoint_" . $row['directory'] . "_" . $row['cfg_dir'] . '_phone';
            $base_class = "endpoint_" . $row['directory'] . '_base';
            $master_class = "endpoint_base";
            /*             * Quick Fix for FreePBX Distro
             * I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
             * */
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
            $data = & $endpoint->eda->sql($sql, 'getall', DB_FETCHMODE_ASSOC);
            $i = 1;
            foreach ($data as $list) {
                $template_file_list[$i]['value'] = "template_data_" . $list['model'] . "_custom.xml";
                $template_file_list[$i]['text'] = "template_data_" . $list['model'] . "_custom.xml";
            }

            $endpoint->tpl->assign("template_file_list", $template_file_list);
            if (isset($_REQUEST['temp_file'])) {
                $endpoint->tpl->assign("temp_file", 1);
            } else {
                $endpoint->tpl->assign("temp_file", NULL);
            }

            $endpoint->tpl->assign("file_list", $file_list);
            $endpoint->tpl->assign("sql_file_list", $sql_file_list);
            $endpoint->tpl->assign("product_selected", $_REQUEST['product_select']);
        }
        $endpoint->tpl->assign("product_list", $product_list);
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('advanced_settings_poce');

?>