<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	$request = $_REQUEST;
	
	if (isset($_REQUEST['delete'])) {
		$sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $_REQUEST['sql'];
		sql($sql);
echo "Deleted!";
	}
?>


<br/>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-9">
        	<div class="row">
		        <div class="col-xs-4 text-center" id="select_product_list_files_config">
					<div class="btn-group">
						<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						    <span class="label label-default label-pill">0</span>
							<?php echo _("Local File Configs"); ?> <i class="fa fa-chevron-down"></i>
						</button>
  						<div class="dropdown-menu">
    						<a class="dropdown-item disabled" href="#">Emtry1</a>
  						</div>
					</div>
					<br /><br /><hr>
				</div>
				
				<div class="col-xs-4 text-center" id="select_product_list_files_template_custom">
					<div class="btn-group">
						<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<span class="label label-default label-pill">0</span>
							<?php echo _("Custom Template Files"); ?> <i class="fa fa-chevron-down"></i>
						</button>
						<div class="dropdown-menu">
							<a class="dropdown-item disabled" href="#">Emtry</a>
					  </div>
					</div>
					<br /><br /><hr>
				</div>
				<div class="col-xs-4 text-center" id="select_product_list_files_user_config">
					<div class="btn-group">
						<button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<span class="label label-default label-pill">0</span>
							<?php echo _("User File Configs"); ?> <i class="fa fa-chevron-down"></i>
						</button>
  						<div class="dropdown-menu">
    						<a class="dropdown-item disabled" href="#">Emtry</a>
  						</div>
					</div>
		            <br /><br /><hr>
				</div>
			</div>
			
			<br />
        	<p><b>Product:</b> <code id="poce_NameProductSelect"><?php echo _("No Selected"); ?></code></p>
			<p><b>File:</b> <code class='inline' id='poce_file_name_path'><?php echo _("No Selected"); ?></code></p>
			<p><label><i class="fa fa-file-code-o"></i> Cantenido del archivo:</label></p>
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<form method="post" action="" name="config_text_sec_button">
							<input type="hidden" name="product_select" value="" />
							<input type="hidden" name="file_select" value="" />
						
          					<p><textarea name="config_text" id="config_textarea" rows="20" readonly></textarea></p>
		        			<p id="box_bt_save" class="" style="display: none;">
	        					<button type="button" class='btn btn-default' name="button_save" ><i class='fa fa-floppy-o'></i> <?php echo _('Save')?></button>
	        					<i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;">NOTE: File may be over-written during next package update. We suggest also using the <b>Share</b> button below to improve the next release.</font>
	        				</p>
							<p id="box_bt_save_as" class="" style="display: none;">
								<button type="button" class='btn btn-default' name="button_save_as" ><i class='fa fa-floppy-o'></i> <?php echo _('Save As...')?></button>
	          					<input type="text" name="save_as_name" id="save_as_name" value="">
	        					<i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;">NOTE: File is permanently saved and not over-written during next package update.</font>
	        				</p>
	        				<p id="box_bt_share" class="" style="display: none;">
	        				<?php
							/*
							 	<!-- if (isset($type)) { -->
								<a href="config.php?display=epm_advanced&amp;subpage=poce&sendid=<?php echo $sendidt; ?>&amp;filename=<?php echo $filename; ?>&amp;product_select=<?php echo $_REQUEST['product_select']; ?>&amp;<?php echo $type.'='.$sendidt; ?>">
	          					<button type="button" class="btn btn-default"><i class="fa fa-upload"></i> <?php echo _('Share')?></button></a> Upload this configuration file to the <b>Provisioner.net Team</b>. Files shared are confidential and help improve the quality of releases.
	          					*/
	          				 ?>
	          				</p>
          				</form>
					</div>
				</div>
			</div>
				
				
			
        </div>
        <div class="col-sm-3 bootnav">
			<div class="list-group">
			<?php
				$sql = 'SELECT * FROM `endpointman_product_list` WHERE (`hidden` = 0 AND `id` > 0) ORDER BY long_name ASC';
				$product_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
				
				$content = load_view(__DIR__.'/epm_advanced/poce.views.bootnav.php', array('request' => $request, 'product_list' => $product_list));
				
				echo $content;
				unset ($product_list);
				unset ($sql);
			?>
            </div>
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