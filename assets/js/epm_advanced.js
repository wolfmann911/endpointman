var v_sTimerUpdateAjax = "";
var v_sTimerSelectTab = "";
var box;

$(document).ready(function() {
	$.getScript("modules/endpointman/assets/js/epm_global.js");
	$.getScript("modules/endpointman/assets/js/jquery-linedtextarea.js");
	$.getScript("modules/endpointman/assets/js/jquery.colorbox.js");
	
	
	//TAB SETTING
	$('#settings input[type=text]').change(function(){ epm_advanced_tab_setting_input_change(this) });
	$('#settings input[type=radio]').change(function(){ epm_advanced_tab_setting_input_change(this) });
	$('#settings select').change(function(){ epm_advanced_tab_setting_input_change(this) });
	
	
	//TAB OUT_MANAGER
	$('#AddDlgModal').on('show.bs.modal', function (event) {
		$(this).find('input, select').val("");
	});
	$('#AddDlgModal_bt_new').on("click", function(){ epm_advanced_tab_oui_manager_bt_new(); });
	
	
	//TAB POCE
	//Demo: http://files.aw20.net/jquery-linedtextarea/jquery-linedtextarea.html
	//http://alan.blog-city.com/jquerylinedtextarea.htm
	//$("#config_textarea").linedtextarea();
	//activar al serleccionar archivo, si se pone en el general javascript entra en bucle y no termina.
	
	
	//TAB IEDL
	
	
	//TAB MANUAL_UPLOAD
	
	
});


$(window).load(function() {
	epm_advanced_tab_check_activa();
});



/**** INI: FUNCTION GLOBAL SEC ****/
function epm_advanced_tab_check_activa(oldtab = "")
{
	clearTimeout(v_sTimerSelectTab);
	var actTab = epm_global_get_tab_actual();
	if (oldtab != actTab) 
	{
		epm_advanced_select_tab_ajax();
	}
	v_sTimerSelectTab = setTimeout(function () { epm_advanced_tab_check_activa(actTab); }, 1000);
}

function epm_advanced_select_tab_ajax()
{	
	clearTimeout(v_sTimerUpdateAjax);
	idtab = epm_global_get_tab_actual();
	if (idtab == "") {
		fpbxToast('epm_advanced_select_tab_ajax -> id invalid (' + idtab + ')!','JS!','warning');
		return false;
	}
	return true;
}

function close_module_actions(goback) 
{
	box.dialog("destroy").remove();
	if (goback) {
		location.reload();
	}
}
/**** END: FUNCTION GLOBAL SEC ****/




/**** INI: FUNCTION TAB UPLOAD_MANUAL ****/
function epm_config_tab_manual_upload_bt_explor_brand() 
{
	var packageid = $('#brand_export_pack_selected').val();
	
	if (packageid == "") {
		alert ("You have not selected a brand from the list!");
	}
	else if (packageid < 0) {
		alert ("The id of the selected mark is invalid!");
	}
	else {
		epm_config_tab_manual_upload_bt_upload("export_brands_availables&package="+packageid, iedl_form_import_cvs);
	}
}

function epm_config_tab_manual_upload_bt_upload(command, formname)
{
	if (command == "") { return; }
	if (formname == "") { return; }
	
	var urlStr = "config.php?display=epm_advanced&subpage=manual_upload&command="+command;
	box = $('<div id="moduledialogwrapper" ></div>')
	.dialog({
		title: 'Status',
		resizable: false,
		dialogClass: '',
		modal: true,
		width: 410,
		maxHeight: 410,
		height: 'auto',
		maxHeight: 350,
		scroll: true,
		position: { my: "top-175", at: "center", of: window },
		open: function (e) {
			$('#moduledialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
			
			var form = document.forms.namedItem(formname);
			var oData = new FormData(form);
			
			var xhr = new XMLHttpRequest(),
			timer = null;
			xhr.open('POST', urlStr, true);
			xhr.send(oData);
			timer = window.setInterval(function() {
				$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(this).height() });
				if (xhr.readyState == XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
				}
				if (xhr.responseText.length > 0) {
					if ($('#moduledialogwrapper').html().trim() != xhr.responseText.trim()) {
						$('#moduledialogwrapper').html(xhr.responseText);
						$('#moduleprogress').scrollTop(1E10);
					}
				}
				if (xhr.readyState == XMLHttpRequest.DONE) {
					$("#moduleprogress").css("overflow", "auto");
					$('#moduleprogress').scrollTop(1E10);
					$("#moduleBoxContents a").focus();
				}
			}, 500);
			
		},
		close: function(e) {
			close_module_actions(false);
			$(e.target).dialog("destroy").remove();
		}
	});
}
/**** END: FUNCTION TAB UPLOAD_MANUAL ****/




/**** INI: FUNCTION TAB IEDL ****/
function epm_config_tab_iedl_bt_import() 
{
	var urlStr = "config.php?display=epm_advanced&subpage=iedl&command=import";
	box = $('<div id="moduledialogwrapper" ></div>')
	.dialog({
		title: 'Status',
		resizable: false,
		dialogClass: '',
		modal: true,
		width: 410,
		maxHeight: 410,
		height: 'auto',
		maxHeight: 350,
		scroll: true,
		position: { my: "top-175", at: "center", of: window },
		open: function (e) {
			$('#moduledialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
			
			var form = document.forms.namedItem("iedl_form_import_cvs");
			var oData = new FormData(form);
			
			var xhr = new XMLHttpRequest(),
			timer = null;
			xhr.open('POST', urlStr, true);
			xhr.send(oData);
			timer = window.setInterval(function() {
				$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(this).height() });
				if (xhr.readyState == XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
					//epm_config_select_tab_ajax();
				}
				if (xhr.responseText.length > 0) {
					if ($('#moduledialogwrapper').html().trim() != xhr.responseText.trim()) {
						$('#moduledialogwrapper').html(xhr.responseText);
						$('#moduleprogress').scrollTop(1E10);
					}
				}
				if (xhr.readyState == XMLHttpRequest.DONE) {
					$("#moduleprogress").css("overflow", "auto");
					$('#moduleprogress').scrollTop(1E10);
					$("#moduleBoxContents a").focus();
				}
			}, 500);
			
			
			
		},
		close: function(e) {
			close_module_actions(false);
			$(e.target).dialog("destroy").remove();
		}
	});
}
/**** END: FUNCTION TAB IEDL ****/




/**** INI: FUNCTION TAB POCE ****/
/**** END: FUNCTION TAB POCE ****/




/**** INI: FUNCTION TAB OUI MANAGER ****/
function epm_advanced_tab_oui_manager_grid_actionFormatter(value, row, index){
	var html = '';
    if (row.custom == 1) {
    	html += '<a href="javascript:epm_advanced_tab_oui_manager_bt_del('+value+')" class="delAction"><i class="fa fa-trash"></i></a>';
	}
    else {
    	html += '<i class="fa fa-trash"></i>';
    }
    return html;
}

function epm_advanced_tab_oui_manager_grid_customFormatter(value, row, index){
	var html = '';
    if (value == 1) {
    	html += '<i class="fa fa-pencil-square-o"></i> Custom';
	}
    else {
    	html += '<i class="fa fa-lock"></i> Required';
    }
    return html;
}


function epm_advanced_tab_oui_manager_refresh_table(showmsg = true)
{
	$("#mygrid").bootstrapTable('refresh');
	if (showmsg == true) {
		fpbxToast("Table Refrash Ok!", '', 'success');
	}
}

function epm_advanced_tab_oui_manager_bt_new()
{
	var new_oui = $("#number_new_oui").val().trim();
	var new_brand = $("#brand_new_oui").val();
	
	if (new_oui.length < "6") {
		fpbxToast('New: Input OUI not valid!','Warning!','warning');
	}
	else if (new_brand == "") {
		fpbxToast('New: No select Brand!','Warning!','warning');
	}
	else {
		var data_ajax = { module: "endpointman", module_sec: "epm_advanced", module_tab: "oui_manager", command: "oui_add", number_new_oui: new_oui, brand_new_oui: new_brand };
		if (epm_advanced_tab_oui_manager_ajax(data_ajax) == true) {
			fpbxToast("New OUI add Ok!", '', 'success');
			$("#mygrid").bootstrapTable('refresh');
			$("#AddDlgModal").modal('hide');
		}

		//epm_advanced_tab_oui_manager_ajax("new", data_ajax, objbox);
	}
}

function epm_advanced_tab_oui_manager_bt_del(id_del)
{
	if (id_del == "") {
		fpbxToast('Delete: No ID set!','Warning!','warning');
	}
	else {
		var data_ajax = { module: "endpointman", module_sec: "epm_advanced", module_tab: "oui_manager", command: "oui_del", id_del: id_del };
		if (epm_advanced_tab_oui_manager_ajax(data_ajax) == true) {
			fpbxToast("OUI delete Ok!", '', 'success');	
			$("#mygrid").bootstrapTable('refresh');
		}
		
		//epm_advanced_tab_oui_manager_ajax("del", data_ajax);
	}
}

function epm_advanced_tab_oui_manager_ajax (data_ajax = ""){
	var response = false;
	if (data_ajax != "") { 
		$.ajax({
	        async: false,
			type: 'POST',
			url: "ajax.php",
			data:  data_ajax,
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				return false;
			},
			success: function(data) {
				if (data.status == true) {
					response  = true;
				} 
				else {
					fpbxToast(data.message, "Error!", 'error');
					response  = false;
				}
			}
		});
	}
	return response;
}
/**** END: FUNCTION TAB OUI MANAGER ****/




/**** INI: FUNCTION TAB SETTING ****/
function epm_advanced_tab_setting_input_value_change_bt(sNameID, sValue = "", bSaveChange = true, bSetFocus = false)
{
	$(sNameID).val(sValue);
	if (bSetFocus == true) { $(sNameID).focus(); }
	if (bSaveChange == true) {
		epm_advanced_tab_setting_input_change(sNameID);
		
	}
}

function epm_advanced_tab_setting_input_change(obt)
{
	idtab = epm_global_get_tab_actual();
	if (idtab == "") { return; }
	
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val().toLowerCase();
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_advanced",
			module_tab: idtab,
			command: "saveconfig",
			name:  obt_name,
			value: obt_val
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX 1:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			$("#" + obt_name + "_no").attr("disabled", true).prop( "checked", false);
			$("#" + obt_name + "_yes").attr("disabled", true).prop( "checked", false);
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				if (obt_val == "1")
				{
					//true
				}
				else 
				{
					//false
				}
				fpbxToast(data.txt.save_changes_ok, '', 'success');
				//if (data.reload == true) { location.reload(); }
				//if (data.name == "tftp_check") { location.reload(); }
				//if (data.name == "use_repo") { location.reload(); }
				
				
				return true;
			} 
			else {
				fpbxToast(data.message, data.txt.error, 'error');
				$("#" + obt_name + "_no").attr("disabled", true).prop("checked", false);
				$("#" + obt_name + "_yes").attr("disabled", true).prop("checked", false);
				return false;
			}
		},
	});	
}
/**** END: FUNCTION TAB SETTING ****/