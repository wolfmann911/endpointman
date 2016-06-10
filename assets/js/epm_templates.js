function epm_templates_document_ready () {
	
	$('#AddDlgModal').on('show.bs.modal', function (event) {
		$(this).find('input, select').val("");
	});
	
	$('#AddDlgModal_bt_new').on("click", function() { epm_tamplates_grid_add(); });
	
	$('#NewProductSelect').on('change', function() { epm_templates_add_NewProductSelect_Change (this); });

	//http://kevinbatdorf.github.io/liquidslider/examples/page1.html#right
	$('#main-slider').liquidSlider({
		includeTitle:false,
		continuous:false,
		slideEaseFunction: "easeInOutCubic",
		preloader:true,
		onload: function() {
			this.alignNavigation();
			$('.liquid-slider').css('visibility', 'visible');
		}
	});
		
	//Al iniciar la apertura de la ventana
	$('#CfgGlobalTemplate').on('show.bs.modal', function (e) {
		epm_template_custom_config_get_global(e);	
	});
	
	//Al finalizar la apertura de la ventana	$('#CfgGlobalTemplate').on('shown.bs.modal', function (e) { });
	//Antes de iniciar el cierre de la ventana	$('#CfgGlobalTemplate').on('hide.bs.modal', function (e) { });
	//Despues de Cerrar la ventana				$('#CfgGlobalTemplate').on('hidden.bs.modal', function (e) { });
	
}

function epm_templates_windows_load (nTab = "") {
	
}

function epm_templates_change_tab (nTab = "") {

}






$("#table-all-side").on('click-row.bs.table',function(e,row,elem){
	window.location = '?display=epm_templates&subpage=editor&custom='+row['custom']+'&idsel='+row['id'];
})

function epm_templates_grid_FormatThEnabled(value, row, index){
	var html = '';
    if (value == 1) {
    	html += '<i class="fa fa-check-square-o fa-lg"></i> Enabled';
	}
    else {
    	html += '<i class="fa fa-square-o fa-lg"></i> Disabled';
    }
    return html;
}

function epm_templates_grid_FormatThAction(value, row, index){
	var html = '';
	html += '<a href="?display=epm_templates&subpage=editor&custom='+row.custom+'&idsel='+value+'">';
	html += '<i class="fa fa-edit"></i>';
	html += '</a>&nbsp;';
	if (row.custom == "0"){
		html += '<a class="delAction" href="javascript:epm_templates_grid_del('+value+')">';
		html += '<i class="fa fa-trash"></i>';
		html += '</a>&nbsp;';
	}
    return html;
}





function epm_templates_add_NewProductSelect_Change (obj)
{
	if ($(obj).val() != "") {
		$.ajax({
			type: 'POST',
			url: "ajax.php",
			data: {
				module: "endpointman",
				module_sec: "epm_templates",
				module_tab: "manager",
				command: "model_clone",
				id : $(obj).val()
			},
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				return false;
			},
			success: function(data) 
			{
				var options = '';
				if (data.status == true) 
				{
					$(data.listopt).each(function(index, itemData) 
					{
						options += '<option value="' + itemData.optionValue + '">' + itemData.optionDisplay + '</option>';	
					});
					$("#NewCloneModel").html(options);
				} 
				else {
					options = '<option value="">'+ data.message +'</option>';
					fpbxToast(data.message, "Error!", 'error');
				}
				$("#NewCloneModel").html(options);
				$('#NewCloneModel option:first').attr('selected', 'selected');
			}
		});
	}
	else { $("#NewCloneModel").html(''); }
}

function epm_templates_grid_del (iddel)
{
	if (iddel == "")
	{
		fpbxToast("ID no send!", "Error!", 'error');
	}
	else {
		$.ajax({
	        cache: false,
			type: 'POST',
			url: "ajax.php",
			data: {
				module: "endpointman",
				module_sec: "epm_templates",
				module_tab: "manager",
				command: "del_template",
				idsel : iddel
			},
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				return false;
			},
			success: function(data) {
				if (data.status == true) { fpbxToast(data.message, '', 'success'); }
				else { fpbxToast(data.message, "Error!", 'error'); }
				$("#mygrid").bootstrapTable('refresh');
			}
		});
	}
}

function epm_tamplates_grid_add() 
{
	var NameTemplate = $('#NewTemplateName').val();
	var ProductSelec = $('#NewProductSelect').val();
	var CloneModel = $('#NewCloneModel').val();
	
	if ((NameTemplate == "") || (ProductSelec == "") || (CloneModel == ""))
	{
		fpbxToast("Faltan Datos!", "Error!", 'error');
	}
	else {
		$.ajax({
			type: 'POST',
			url: "ajax.php",
			data: {
				module: "endpointman",
				module_sec: "epm_templates",
				module_tab: "manager",
				command: "add_template",
				newnametemplate : NameTemplate,
				newproductselec : ProductSelec,
				newclonemodel : CloneModel
			},
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				return false;
			},
			success: function(data) {
				if (data.status == true) 
				{
					fpbxToast(data.message, '', 'success');
					setTimeout (function () { window.location.href = "config.php?display=epm_templates&subpage=editor&custom=0&idsel="+data.newid; }, 500); 
				} 
				else { fpbxToast(data.message, "Error!", 'error'); }
			}
		});
	}
}







function epm_template_custom_config_get_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "editor",
			command: "custom_config_get_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel')
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				$("#srvip").val( data.settings.srvip );
				$("#server_type").val( data.settings.server_type );
				$("#config_loc").val( data.settings.config_location );
				$("#tz").val( data.settings.tz );
				$("#ntp_server").val( data.settings.ntp );
				
				if (elmnt.name == "button_undo_globals") {
					fpbxToast(data.message, '', 'success');
				}
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});
}

function epm_template_custom_config_update_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "editor",
			command: "custom_config_update_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel'),
			tz: epm_global_get_value_by_form("FormCfgGlobalTemplate","tz"),
			ntp_server: epm_global_get_value_by_form("FormCfgGlobalTemplate","ntp_server"),
			srvip: epm_global_get_value_by_form("FormCfgGlobalTemplate","srvip"),			
			config_loc: epm_global_get_value_by_form("FormCfgGlobalTemplate","config_loc"),
			server_type: epm_global_get_value_by_form("FormCfgGlobalTemplate","server_type")
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				fpbxToast(data.message, '', 'success');
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});	
}

function epm_template_custom_config_reset_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "editor",
			command: "custom_config_reset_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel')
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				fpbxToast(data.message, '', 'success');
				epm_template_custom_config_get_global(elmnt);
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});
}











	
	/*
		//edit
		//<a href="#" onclick="return popitup('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit', '<?php echo $row['name']; ?>')">
		function popitup(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=' + document.getElementById('altconfig_'+ name).value + '&rand=' + new Date().getTime(),'name','height=710,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
		//edit
		//<a href='#' onclick='return popitup2("config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit", "<?php echo $row['name']?>")'>
        function popitup2(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
		

*/	