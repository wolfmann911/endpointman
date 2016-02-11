<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<div class = "display no-border">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display no-border">
					<div id="toolbar-all">
						<button type="button" id="button_check_for_updates" class="btn btn-primary" disabled="true"><i class="fa fa-refresh"></i> <?php echo _("Check for Update"); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="section-title" data-for="epm_manager_list">
	<h3><i class="fa fa-minus"></i><?php echo _("List Packages Manager") ?></h3>
</div>
<div class="section" data-id="epm_manager_list">
	<div class="alert alert-info" role="alert" id="epm_config_manager_load_init">
		<center><h2><?php echo _("Loading data...."); ?></h2></center>
		<center><img src="assets/endpointman/images/ajax-loader-bar.gif" alt="" /></center>
	</div>
	<div id="epm_config_manager_all_list_box">
	</div>
</div>