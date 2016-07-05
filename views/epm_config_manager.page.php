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
	<ul class="list-group" id="epm_config_manager_list_loading">
		<li class="list-group-item text-center bg-info">
			<i class="fa fa-spinner fa-pulse"></i>&nbsp; <?php echo _("Loading...")?>
		</li>
	</ul>
	<div id="epm_config_manager_all_list_box">
	</div>
</div>