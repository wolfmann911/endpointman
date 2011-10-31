<?php if(!defined('IN_RAINTPL')){exit('Hacker attempt');}?><?php
	$tpl = new RainTPL( RainTPL::$tpl_dir . dirname("global_header"));
	$tpl->assign( $var );
		$tpl->draw(basename("global_header"));
?>
<?php
	if( isset($var["show_error_box"]) ){
?>
    <?php
		$tpl = new RainTPL( RainTPL::$tpl_dir . dirname("message_box"));
		$tpl->assign( $var );
				$tpl->draw(basename("message_box"));
?>
<?php
	}
?>
<style type="text/css">
<!--
.brand {
	border: thin dotted #000;
}
.product {
	border: thin dotted #000;
}
.model {
	border: thin dotted #000;
}
.button_Enable {
	background-color: #3C0;
}
.button_Disable {
	background-color: #F00;
}
-->
</style>
<script type="text/javascript" charset="utf-8">
    function check() {
        $('#spinner').toggle();
        document.check.submit();
    }
</script>
<center>
<form id='check' action='config.php?type=tool&amp;display=epm_config' method='POST'>
	<input type="submit" name="button_check_for_updates" onclick="check();" value="<?php echo _('Check for Updates')?>">
</form>
</center>

<br><br>

<?php
	if( 1 == 0 ){
?>
<script type="text/javascript" src="/admin/modules/endpointman/templates/javascript/jquery.jstree.js"></script>
<script type="text/javascript" class="source">
$(function () {
	$("#demo1").jstree({
                 "core" : { "initially_open" : [ <?php
		if( isset( $var["brand2_list"] ) && is_array( $var["brand2_list"] ) ){
			$counter1 = 0;
			foreach( $var["brand2_list"] as $key1 => $value1 ){ 
?>"brand_<?php echo $value1["id"];?>",<?php
			if( isset( $value1["products"] ) && is_array( $value1["products"] ) ){
				$counter2 = 0;
				foreach( $value1["products"] as $key2 => $value2 ){ 
?>"products_<?php echo $value2["id"];?>",<?php
					$counter2++;
				}
			}
?><?php
				$counter1++;
			}
		}
?> ] },

		"plugins" : [ "themes", "html_data", "checkbox" ]
	});
         $("#frmTree").submit(function () { generateHiddenFieldsForTree("demo1"); });

});
</script>
<script>
function generateHiddenFieldsForTree(treeId) {
    var checked_ids = [];

    $("#demo1").jstree("get_checked").each(function () {
        var checkedId = this.id;
        $("<input>").attr("type", "hidden").attr("name", checkedId).val("on").appendTo("#" + treeId);
        checked_ids.push(this.id);
    });
    $("#hidden").val(checked_ids.join(","));
}

</script>
<form id="frmTree" action="#" method="POST">
<?php
		if( !isset($var["update_check"]) ){
?>
    <div id="demo1" class="demo">
        <ul>
            <?php
			if( isset( $var["brand2_list"] ) && is_array( $var["brand2_list"] ) ){
				$counter1 = 0;
				foreach( $var["brand2_list"] as $key1 => $value1 ){ 
?>
            <li id="brand_<?php echo $value1["id"];?>">
                <a href="#"><?php echo $value1["name"];?></a>
                <ul>
                    <?php
				if( isset( $value1["products"] ) && is_array( $value1["products"] ) ){
					$counter2 = 0;
					foreach( $value1["products"] as $key2 => $value2 ){ 
?>
                    <li id="products_<?php echo $value2["id"];?>">
                        <a href="#"><?php echo $value2["long_name"];?></a>
                        <ul>
                            <?php
					if( isset( $value2["models"] ) && is_array( $value2["models"] ) ){
						$counter3 = 0;
						foreach( $value2["models"] as $key3 => $value3 ){ 
?>
                            <li id="models_<?php echo $value3["id"];?>" <?php
						if( $value3["enabled"] == '1' ){
?>class="jstree-checked"<?php
						}
?>>
                                <a href="#"><?php echo $value3["model"];?></a>

                            </li>
                            <?php
							$counter3++;
						}
					}
?>
                        </ul>
                    </li>
                    <?php
						$counter2++;
					}
				}
?>
                </ul>
            </li>
            <?php
					$counter1++;
				}
			}
?>
        </ul>
    </div>
    <input type="hidden" name="hidden" id="hidden" value="English">
    <input type="submit" name="install-jstree" value="Install/Uninstall">
<?php
		}
		else{
?>
    <div id="demo1" class="demo">
            <ul>
                <?php
			if( isset( $var["brand2_list"] ) && is_array( $var["brand2_list"] ) ){
				$counter1 = 0;
				foreach( $var["brand2_list"] as $key1 => $value1 ){ 
?>
                <li id="brand_<?php echo $value1["id"];?>">
                    <a href="#"><?php echo $value1["name"];?> <i>(<?php echo _('Package Last Modified')?> [<?php echo $value1["cfg_ver"];?>])</i></a>
                    <ul>
                        <?php
				if( isset( $value1["products"] ) && is_array( $value1["products"] ) ){
					$counter2 = 0;
					foreach( $value1["products"] as $key2 => $value2 ){ 
?>
                        <li id="products_<?php echo $value2["id"];?>">
                            <a href="#"><?php echo $value2["long_name"];?></a>
                        </li>
                        <?php
						$counter2++;
					}
				}
?>
                    </ul>
                </li>
                <?php
					$counter1++;
				}
			}
?>
            </ul>
        </div>
        <input type="hidden" name="hidden" id="hidden" value="English">
        <input type="submit" name="install-jstree" value="Update Checked">
<?php
		}
?>

</form>
<?php
	}
?>
<?php
	if( isset($var["installer"]) ){
?>
<script>
var box;
function process_module_actions(actions) {
    $(document).ready(function() {
	urlStr = "config.php?display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=installer.html.php&amp;module=endpointman&amp;type=<?php echo $var["installer"]["type"];?>&amp;id=<?php echo $var["installer"]["id"];?>";
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
            location.href = 'config.php?display=epm_config';
        }
}
process_module_actions();
</script>
<div id="moduleBox" style="display:none;"></div> 
<?php
	}
?>
<?php
	if( 1 == 1 ){
?>
    <?php
		if( isset( $var["brand2_list"] ) && is_array( $var["brand2_list"] ) ){
			$counter1 = 0;
			foreach( $var["brand2_list"] as $key1 => $value1 ){ 
?>
        <table width="100%" class="brand" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td colspan="2"><?php echo $value1["name"];?> <?php
			if( $value1["installed"] == '1' ){
?>(<?php echo _('Package Last Modified')?> [<?php echo $value1["cfg_ver"];?>]) <?php
			}
?><?php
			if( $value1["installed"] == '1' ){
?><input type="checkbox" name="vehicle" value="Bike" disabled <?php
				if( $value1["local"] == 0 ){
?>checked<?php
				}
?>/>Check Online<?php
			}
?><form action='config.php?type=tool&amp;display=epm_config' method='POST'><input type="hidden" name="brand" value="<?php echo $value1["id"];?>"><input type="submit" name="button_<?php
			if( $value1["installed"] == '1' ){
?>uninstall<?php
			}
			else{
?>install<?php
			}
?>" class="button_<?php
			if( $value1["installed"] == '1' ){
?>Disable<?php
			}
			else{
?>Enable<?php
			}
?>" value="<?php
			if( $value1["installed"] == '1' ){
?><?php echo _('Uninstall')?><?php
			}
			else{
?><?php echo _('Install')?><?php
			}
?>"><?php
			if( array_key_exists('update',$value1) ){
?><?php
				if( $value1["update"] == 1 ){
?>New Package Modified [<?php echo $value1["update_vers"];?>]<input type="submit" name="button_update" class="button_update" value="<?php echo _('Update')?>"><?php
				}
?><?php
			}
?></form></td>
            </tr>
            <tr>
                <td width="3%">&nbsp;</td>
                <td width="97%"><?php
			if( isset( $value1["products"] ) && is_array( $value1["products"] ) ){
				$counter2 = 0;
				foreach( $value1["products"] as $key2 => $value2 ){ 
?>
                    <table width="100%" class="product" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="2"><?php echo $value2["long_name"];?> <?php
				if( $value2["cfg_ver"] != '' ){
?><?php
				}
?> <form action='config.php?type=tool&amp;display=epm_config' method='POST'><input type="hidden" name="product" value="<?php echo $value2["id"];?>"><?php
				if( array_key_exists('update',$value2) ){
?><?php
					if( $value2["update"] == 1 ){
?><input type="submit" name="button_update" class="button_update" value="Update"><?php
					}
?><?php
				}
?><?php
				if( $value2["fw_type"] == 'install' ){
?><input type="submit" name="button_install_firmware" class="button_update" value="<?php echo _('Install Firmware')?>"><?php
				}
					elseif( $value2["fw_type"] == 'remove' ){
?><input type="submit" name="button_remove_firmware" class="button_update" value="<?php echo _('Remove Firmware')?>"><?php
					}
?><?php
					if( array_key_exists('update_fw',$value2) ){
?><?php
						if( $value2["update_fw"] == 1 ){
?><input type="submit" name="button_update_firmware" class="button_update" value="<?php echo _('Update Firmware')?>"><?php
						}
?><?php
					}
?></form></td>
                        </tr>
                        <tr>
                            <td width="4%">&nbsp;</td>
                            <td width="96%"><?php
					if( isset( $value2["models"] ) && is_array( $value2["models"] ) ){
						$counter3 = 0;
						foreach( $value2["models"] as $key3 => $value3 ){ 
?>
                                <table width="100%" class="model" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td><?php echo $value3["model"];?> <form action='config.php?type=tool&amp;display=epm_config' method='POST'><input type="hidden" name="model" value="<?php echo $value3["id"];?>"><input type="submit" name="button_<?php
						if( $value3["enabled"] == '1' ){
?>disable<?php
						}
						else{
?>enable<?php
						}
?>" class="button_<?php
						if( $value3["enabled"] == '1' ){
?>Disable<?php
						}
						else{
?>Enable<?php
						}
?>" value="<?php
						if( $value3["enabled"] == '1' ){
?><?php echo _('Disable')?><?php
						}
						else{
?><?php echo _('Enable')?><?php
						}
?>"></form></td>
                                    </tr>
                                </table><?php
							$counter3++;
						}
					}
?></td>
                        </tr>
                    </table><?php
						$counter2++;
					}
				}
?></td>
            </tr>
        </table>
        <br />
        <br />
    <?php
					$counter1++;
				}
			}
?>
<?php
		}
?>
<hr>
<h6 align='center'>Want to participate? Add new phones? Join us at <a href="http://provisioner.net" target="_blank">http://www.provisioner.net</a></h6>
<?php
		$tpl = new RainTPL( RainTPL::$tpl_dir . dirname("global_footer"));
		$tpl->assign( $var );
				$tpl->draw(basename("global_footer"));
?>

  


  
