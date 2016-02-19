<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-all">
				<a class='btn btn-primary' id="btdialognewouiopen" href="#box_new_oui"><i class='fa fa-plus'></i> <?php echo _('Add Custom OUI')?></a>
				<a class='btn btn-default' href="javascript:epm_advanced_tab_oui_manager_refresh_table();" ><i class='fa fa-refresh fa-spin'></i> <?php echo _('Refresh Table')?></a>
			</div>
			<table id="mygrid"
				data-url="ajax.php?module=endpointman&amp;module_sec=epm_advanced&amp;module_tab=oui_manager&amp;command=oui"
				data-cache="false"
				data-cookie="true"
				data-cookie-id-table="oui_manager-all"
				data-toolbar="#toolbar-all"
				data-maintain-selected="true"
				data-show-columns="true"
				data-show-toggle="true"
				data-toggle="table"
				data-pagination="true"
				data-search="true"
				class="table table-striped">
				<thead>
					<tr>
						<th data-field="oui" data-formatter="<code>%s</code>"><?php echo _("OUI")?></th>
						<th data-field="brand"><?php echo _("Brand")?></th>
						<th data-field="custom" data-formatter="epm_advanced_tab_oui_manager_grid_customFormatter"><?php echo _("Type")?></th>
						<th data-field="id" data-formatter="epm_advanced_tab_oui_manager_grid_actionFormatter"><?php echo _("Actions")?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>

<div style='display:none'>
	<div id="box_new_oui">
		<div id="box_new_oui_title"><h1>New OUI Custom</h1></div>
		<div id="box_new_oui_container">
			<div class="secoptA">
				<b>Numbrer OUI:</b>
				<div>
					<input class="form-control" type="text" maxlength="6" name="number_new_oui" id="number_new_oui" placeholder="New OUI">
				</div>
			</div>
			<div class="secoptB">
				<b>Brand:</b>
				<div class="styled-select">
		  			<select name="brand_new_oui" id="brand_new_oui" >
		  				<option value="">Select Brand:</option>
						<?php
						foreach ($brands as $row) {
							echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
						}
						?>
					</select>
				</div>
			</div>
		</div>
		<div id="box_new_oui_botones">
			<a class='btn btn-primary' href="javascript:epm_advanced_tab_oui_manager_bt_new();"><i class='fa fa-check'></i> <?php echo _('Add')?></a>
			<a class='btn btn-default' href="javascript:$.fn.colorbox.close();" ><i class='fa fa-times'></i> <?php echo _('Cancel')?></a>
		</div>
	</div>
</div>