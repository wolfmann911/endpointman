function epm_templates_document_ready () {
	
	$('#AddDlgModal').on('show.bs.modal', function (event) {
		$(this).find('input, select').val("");
	});
	
	$('#AddDlgModal_bt_new').on("click", function() { epm_tamplates_grid_add(); });
	
	$('#NewProductSelect').on('change', function() { epm_templates_add_NewProductSelect_Change (this); });
 
	$('#coda-slider-9').codaSlider({
		dynamicArrows: false,
		continuous: false
	}); 
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




