<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<div class="alert alert-info" role="alert"><?php echo _("Select brands, products or models that do not need to hide from the list."); ?></div>

<div class="section-title" data-for="epm_edit_show_hide">
	<h3><i class="fa fa-minus"></i><?php echo _("Brands/Modules") ?></h3>
</div>
<div class="section" data-id="epm_edit_show_hide">
	<div class="alert alert-info" role="alert" id="epm_config_editor_load_init">
		<center><h2><?php echo _("Loading data...."); ?></h2></center>
		<center><img src="assets/endpointman/images/ajax-loader-bar.gif" alt="" /></center>
	</div>
	
	<div id="epm_config_editor_all_list_box">
	</div>
</div>