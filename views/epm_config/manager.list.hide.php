<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="alert alert-info" role="alert">
	<h3><?php echo _("Packages Hiden") ?></h3>
    <div class="input-group">
        <span class="input-group-addon" data-for="basic-addon1">Marcas/Modelos Ocultos:</span>
        <select 
			data-url="ajax.php?module=endpointman&amp;module_sec=epm_config&amp;module_tab=manager&amp;command=list_brand_model_hide"
            data-cache="false"
            data-id = ""
			data-label = ""
            data-selected-text-format="count > 4"
            data-size="10"
            data-style="btn-info" 
            data-live-search-placeholder="Buscar..." 
            data-live-search="true" 
            class="form-control selectpicker show-tick" 
            id="epm_config_manager_select_hidens" 
            name="epm_config_manager_select_hidens"
            multiple>
        </select>
    </div>
</div>