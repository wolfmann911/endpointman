"use strict";
var v_sTimerUpdateAjax = "";

function epm_config_document_ready () {
	
	//funcion prar  buscar en la lista modelos.
	$('#search').keyup(function(){	
		var current_query = $('#search').val().toLowerCase();
		if (current_query !== "") {
			$("#epm_config_manager_all_list_box ul.list-group li").not('.active').hide().removeClass("search_list_ok");
			$("#epm_config_manager_all_list_box ul.list-group li").not('.active').each(function(){
				var current_keyword = $(this).text().toLowerCase();
				if (current_keyword.indexOf(current_query) >=0) { $(this).show().addClass("search_list_ok"); }
			});
		} 
		$("#epm_config_manager_all_list_box .element-container").each(function(){
			if (current_query !== "") {
				$(this).find("ul.list-group").each(function(){
					$(this).show();
					if ($(this).find("li.search_list_ok").not('.active').length == 0) 	{ $(this).hide(); }
				});
				$(this).show();
				if ($(this).find("ul.list-group:visible").length == 0) 	{ $(this).hide(); }
			}
			else {
				$(this).show();
				$(this).find(".list-group").show();
				$(this).find(".list-group li").show();
			}
		});
	});
	
	
	$("#button_check_for_updates").on( "click", function(){ epm_config_tab_manager_bt_update_check_click(); });
	
}

function epm_config_windows_load (nTab = "") {
	epm_config_select_tab_ajax(nTab);
	$("#epm_config_" + nTab + "_all_list_box").children("div").hide("slow", function () {
		$(this).remove();
	});
}

function epm_config_change_tab (nTab = "") {
	epm_config_select_tab_ajax(nTab);
	$("#epm_config_" + nTab + "_all_list_box").children("div").hide("slow", function () {
		$(this).remove();
	});
}







//**** INI: FUNCTION GLOBAL SEC ****
function epm_config_select_tab_ajax(idtab = "")
{
	if (idtab == "") {
		fpbxToast('epm_config_select_tab_ajax -> id no send!','JS!','warning');
		return false;
	}
	clearTimeout(v_sTimerUpdateAjax);

	epm_global_html_find_show_hide("#epm_config_" + idtab + "_list_loading", true, 0, true);
	
	waitingDialog.show();
	var $tmp = epm_config_LoadContenidoAjax(idtab, "list_all_brand");
	if ($tmp = "false") {
		setTimeout(function () {waitingDialog.hide();}, 3000);
		epm_global_html_find_show_hide("#epm_config_" + idtab + "_list_loading", false, 3000, true);
	}
	else {
		epm_global_html_find_show_hide("#epm_config_" + idtab + "_list_loading", false, 1000, true);
	}
	return true;
}


function end_module_actions_epm_config(acctionname = "")
{
	var actTab = epm_global_get_tab_actual();
	switch(acctionname) {
		case "bt_update_chkeck":
			epm_config_select_tab_ajax(actTab);
			break;
	
		case "manager_bt":
			epm_config_select_tab_ajax(actTab);
			break;
		
		default:
			fpbxToast('end_module_actions_epm_config -> acctionname no send!','JS!','warning');
	}
}

function epm_config_LoadContenidoAjax(idtab, opt)
{
	clearTimeout(v_sTimerUpdateAjax);
	
	opt = opt.trim();
	idtab = idtab.trim();
	if ((idtab == "") || (opt == "")) { return false; }
	
	var statustab = $("#" + idtab).css('display').trim();
	if ((statustab == "") || (statustab == "none")) { return false; }
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			module_tab: idtab,
			command: opt
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX 1:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return;
		},
		success: function(data) {
			var tTimer = 20000;
			if (idtab == "editor") {
				switch(opt) {
					case "list_all_brand":
						epm_config_tab_editor_ajax_get_add_data(data, idtab);
						break;
					
					default:
						tTimer = 5000;
						fpbxToast(data.txt.opt_invalid, data.txt.error, 'error');
						break;
				}
			} 
			else if (idtab == "manager") {
				switch(opt) {
					case "check_for_updates":
						if (data.status == true) 
						{
							fpbxToast(data.txt.update_content, data.txt.title_update, 'info');
							epm_config_tab_manager_ajax_get_add_data(data, idtab);
							fpbxToast(data.txt.ready, '', 'success');
						} 
						else { fpbxToast(data.message, data.txt.error, 'error'); }
						break;
					
					case "list_all_brand":
						epm_config_tab_manager_ajax_get_add_data(data, idtab);
						break;
					
					default:
						tTimer = 5000;
						fpbxToast(data.txt.opt_invalid, data.txt.error, 'error');
						break;
				}
			}
			v_sTimerUpdateAjax = setTimeout(function () { epm_config_LoadContenidoAjax(idtab, "list_all_brand"); }, tTimer);
			setTimeout(function () {waitingDialog.hide();}, 3000);
		}
	});
	return true;
}

function ItemsLevel(idtab = "", prefijo = "", id = 0) 
{
	this.def_box = "_box";
	this.def_sel = "_box_select";
	this.def_sit = "_box_subitems";
	
	this.id = id;
	this.tab = idtab;
	this.prefijo = prefijo;
	
	this.prefijoid = this.tab + "_" + this.prefijo  + "_" + this.id;
	this.boxelemen = this.prefijoid + this.def_box;
	this.boxappend = this.prefijoid + this.def_sel;
	this.boxsubite = this.prefijoid + this.def_sit;
}
//**** END: FUNCTION GLOBAL SEC ****









//**** INI: TAB/MANAGER ****
function epm_config_tab_manager_bt_enable_disable_change(obt, idtype, idbt, iL2) 
{
	if ((idbt == "") || (idtype == "")) { return false; }
	
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val().toLowerCase();
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			module_tab: "manager",
			command: "saveconfig",
			name:  obt_name,
			value: obt_val,
			idtype: idtype,
			idbt: idbt
		},
		dataType: 'json',
		timeout: 60000,
		error: function(data) {
			fpbxToast('ERROR AJAX 2!', 'ERROR!', 'error');
			$("#" + obt_name + "_enable").attr("disabled", true).prop( "checked", false);
			$("#" + obt_name + "_disable").attr("disabled", true).prop( "checked", false);
			//epm_global_html_find_hide_and_remove('#' + obt_name + '_box_subitems');
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				//epm_global_html_find_hide_and_remove('#' + obt_name + '_box_subitems');
				fpbxToast(data.txt.save_changes_ok, '', 'success');
				epm_config_tab_manager_countlist(iL2);
				return true;
			} 
			else {
				$("#" + obt_name + "_enable").attr("disabled", true).prop( "checked", false);
				$("#" + obt_name + "_disable").attr("disabled", true).prop( "checked", false);
				fpbxToast(data.message, data.txt.error, 'error');
				return false;
			}
		},
	});	
}

function epm_config_tab_manager_bt_update_check_click() 
{
	clearTimeout(v_sTimerUpdateAjax);
	var urlStr = "config.php?display=epm_config&module_tab=manager&command=check_for_updates";
	epm_global_dialog_action("bt_update_chkeck", urlStr, null, "Update Package Info", "", { "Close": function() { $(this).dialog("close"); } });
}

function epm_config_tab_manager_bt(opt, idfw, command) 
{
	if ((opt == "") || (idfw == "") || (command == "")) { return false; }
	clearTimeout(v_sTimerUpdateAjax);
	
	var urlStr = "config.php?display=epm_config&module_tab=manager&command=" + command + "&command_sub=" + opt + "&idfw=" + idfw;
	epm_global_dialog_action("manager_bt", urlStr, null, "Status", 'epm_config_tab_manager_bt_dialog', { "Close": function() { $(this).dialog("close"); } });
}

function epm_config_tab_manager_countlist(iL0)
{
	$('#' + iL0.boxelemen )
	.each( function() {
		var num_chek = $(this).find('input:radio:checked[value="1"]').length;
		var num_all = $(this).find('input:radio[value="1"]').length;
		var num_txt = num_chek + "/" + num_all;
		if (num_chek > num_all) { num_txt = "?/?"; }
		
		$(this).find('.count-products-brand').text(num_txt);
	});
}

function epm_config_tab_manager_bt_enable_disable_ajustar(iL0, itemData, level) 
{
	if (level == "L1") 
	{
		$('#' + iL0.prefijoid + "_bt_brand_install").attr("disabled", 	((itemData.installed == "1") ? true : false));
		$('#' + iL0.prefijoid + "_bt_brand_uninstall").attr("disabled", ((itemData.installed == "1") ? false : true));
		$('#' + iL0.prefijoid + "_bt_brand_update").attr("disabled", 	((itemData.update == 1) ? false : true));
		epm_global_html_find_show_hide('#' + iL0.prefijoid + "_txt_update" , ((itemData.update == 1) ? true : false));
		return;
	}
	else if (level == "L2") 
	{
		if ((itemData.fw_type == "install") || (itemData.fw_type == "remove")) 
		{
			$('#' + iL0.prefijoid + "_bt_fw_install").attr("disabled", 	((itemData.fw_type == "install") ? false : true));
			$('#' + iL0.prefijoid + "_bt_fw_uninstall").attr("disabled",((itemData.fw_type == "install") ? true : false));
			$('#' + iL0.prefijoid + "_bt_fw_update").attr("disabled", 	((itemData.update_fw == 1) ? true : false));
		}
		else if (itemData.fw_type == "nothing") {
			$('#' + iL0.prefijoid + "_bt_fw_install").attr("disabled", true);
			$('#' + iL0.prefijoid + "_bt_fw_uninstall").attr("disabled", true);
			$('#' + iL0.prefijoid + "_bt_fw_update").attr("disabled", true);
		}
		return;
	}
	else if (level == "L3") 
	{
		//AJUSTAMOS BOTOSNES EN SU STATUS CORRECTO
		if (itemData.enabled == "") {
			$("#" + iL0.prefijoid + "_enable").attr("disabled", true).prop( "checked", false);
			$("#" + iL0.prefijoid + "_disable").attr("disabled", true).prop( "checked", false);
			epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
		}
		else {
			var temp_input = $('input[name = "'+ iL0.prefijoid + '"]:checked');
			if (temp_input.length == 0) {
				temp_input = "-1";
			}
			if (itemData.enabled !== temp_input) {
				$("#" + iL0.prefijoid + "_enable").attr("disabled", false).prop( "checked", ((itemData.enabled == "1") ? true : false));
				$("#" + iL0.prefijoid + "_disable").attr("disabled", false).prop( "checked", ((itemData.enabled == "0") ? true : false));
			}
		}
		return;
	}	
}







function epm_config_tab_manager_ajax_get_add_data(data, idtab)
{
	if ($('#button_check_for_updates').is(':disabled') == true) {
		//$("#button_check_for_updates").attr("disabled", false).on( "click", function(){ epm_config_tab_manager_bt_update_check_click(); });
		$("#button_check_for_updates").attr("disabled", false);
	}
	if (data.status == true) {
		var boxappendL0 = "#epm_config_manager_all_list_box";
		
		if ((data.datlist == null) || (data.datlist == ""))
		{
			//LISTA VACIA NO HAY NINGUNA MARCA.
			if ( $('#manager_alert_list_emtry').length == 0 ) 
			{
				$(boxappendL0).children("div").hide("slow", function () { $(this).remove(); });
				$(boxappendL0).append(
					$('<div/>', { 'class' : 'panel panel-warning', 'id' : 'manager_alert_list_emtry' })
					.append(
						$('<div/>', { 'class' : 'panel-heading' }).append( $('<h3/>', { 'class' : 'panel-title' }).text('LIST EMTRY!!!') ),
						$('<div/>', { 'class' : 'panel-body' }).text('Click the "CHECK FOR UPDATES" button to search for data on the server.')
					)
				);
			}
		}
		else 
		{
			epm_global_html_find_hide_and_remove('#manager_alert_list_emtry');
			epm_global_html_find_hide_and_remove('#manager_alert_list_hiden');
			
			//L1: INI loop marcas
			$(data.datlist).each(function(index, itemData) 
			{
				var iL1 = new ItemsLevel(idtab, "marca", itemData.id);
				if (itemData.hidden == 1) {
					epm_global_html_find_hide_and_remove('#' + iL1.boxelemen); 
					return true;
				}
				if ( $('#' + iL1.boxelemen).length == 0 ) 
				{
					$(boxappendL0).append(epm_config_tab_manager_html_L0(iL1));
					epm_config_tab_manager_html_L1(iL1, itemData, data.txt);
				}
				
				
				$('#' + iL1.prefijoid + "_txt_update").text(data.txt.new_pack_mod + ' [' + itemData.update_vers_txt + ']');
				$('#' + iL1.prefijoid + "_txt_last_update").text(data.txt.pack_last_mod + ' [' + itemData.cfg_ver_datetime_txt + ']');
				epm_config_tab_manager_bt_enable_disable_ajustar(iL1, itemData, "L1");
				
				
				//INI - EN CASO DE QUE NO ESTE INSTALADO LA MARCA MUESTAR UN RECUADRO DICIENDOLO
				if (itemData.installed == "0") {
					epm_global_html_find_hide_and_remove('#' + iL1.boxsubite);
					
					
					if ($('#' + iL1.boxappend).find('div.panel').length == 0)
					{
						$('#' + iL1.boxappend).children("div").hide("slow", function () { $(this).remove(); });
						$('#' + iL1.boxappend).append(
							$('<div/>', { 'class' : 'panel panel-warning' }).append(
								$('<div/>', { 'class' : 'panel-heading' }).append( $('<h3/>', { 'class' : 'panel-title' }).text('MARCA NO INSTALADA!') ),
								$('<div/>', { 'class' : 'panel-body' }).text('Esta marca no esta instalada, haz Click en el boton Instalar para instalar el paquete.')
							)
						);
					}
					return;
				}
				else {
					if ($('#' + iL1.boxappend).find('div.panel').length == 1)
					{
						$('#' + iL1.boxappend).children("div").hide("slow", function () { $(this).remove(); });
					}
				}
				//END - EN CASO DE QUE NO ESTE INSTALADO LA MARCA MUESTAR UN RECUADRO DICIENDOLO
				
				
				//L2: ini loop productos
				$(itemData.products).each(function(indexL2, itemDataL2) 
				{	
if (itemData.products.length == 0) { return false; }
					
					var iL2 = new ItemsLevel(idtab, "producto", itemDataL2.id);	
					if (itemDataL2.hidden == 1) { 
						epm_global_html_find_hide_and_remove('#' + iL2.boxelemen); 
						return true; 
					}
					if ( $('#' + iL2.boxelemen).length == 0 ) 
					{
						epm_config_tab_manager_html_L2(iL1, iL2, itemDataL2, data.txt);
					}
					epm_config_tab_manager_bt_enable_disable_ajustar(iL2, itemDataL2, "L2");
					
					
					//L3: Ini loop modelos
					$(itemDataL2.models).each(function(indexL3, itemDataL3) 
					{
if (itemDataL2.models.length == 0) { return false; }
						
						var iL3 = new ItemsLevel(idtab, "modelo", itemDataL3.id);
						if (itemDataL3.hidden == 1) { 
							epm_global_html_find_hide_and_remove('#' + iL3.boxelemen); 
							return true; 
						}
						if ( $('#' + iL3.boxelemen).length == 0 ) 
						{
							epm_config_tab_manager_html_L3(iL2, iL3, itemDataL3, data.txt, 0, 1);
						}
						epm_config_tab_manager_bt_enable_disable_ajustar(iL3, itemDataL3, "L3");
					});
					//L3: end loop modelos
					epm_config_tab_manager_countlist(iL2);
					
					
				});
				//L2: end loop productos
				
				
			});
			//L1: end loop marcas
			
			if ($(boxappendL0).children("div").length == 0)
			{
				if ( $('#manager_alert_list_hiden').length == 0 ) 
				{
					$(boxappendL0).append(
						$('<div/>', {
							'class' : 'alert alert-info',
							'role': 'alert',
							'id' : 'manager_alert_list_hiden'
						})
						.text("All brand's are hidden. Go to the Show/Hide tab shows you need.")
					);
				}
			}
		}
		return true;
	}
	else {
		fpbxToast(data.message,'ERROR!','error');
		return false;
	}
}

function epm_config_tab_manager_html_L0(iL1) 
{
	var htmlReturn = $('<div/>', { 'class' : 'element-container', 'id' : iL1.boxelemen })
		.append(
			$('<div/>', { 'class' : 'row' })
			.append(
				$('<div/>', { 'class' : 'col-md-12' })
				.append(
					$('<div/>', { 'class' : 'row' })
					.append(
						$('<div/>', { 'class' : 'form-group' })
						.append(
							$('<div/>', { 'class' : 'col-md-12' }),
							$('<div/>', { 'class' : 'col-md-2' }),
							$('<div/>', { 'class' : 'col-md-10', 'id' : iL1.boxappend })
						)
					)
				)
			)
		);
	return htmlReturn;
}

function epm_config_tab_manager_html_L1(iL1, itemDataL1, txt) 
{
	$('#' + iL1.boxelemen )
	.each( function() {
		$(this).find('.form-group').each( function() {
			
			$(this)
			.children('.col-md-12')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : iL1.prefijoid + '_label'
				}).html("<h2>" + itemDataL1.name + "</h2> "),
				
				$('<div/>', { 'class':'btn-group pull-xs-right', 'role':'group' })
				.append(
					$('<button/>', {
						'type'	: 'button',
						'id'	: iL1.prefijoid + '_bt_brand_install',
						'class'	: 'btn btn-success',
						'value'	: txt.install
					})
					.on( "click", function(){ epm_config_tab_manager_bt('brand_install', itemDataL1.id, 'brand'); })
					.append( 
						$('<i/>', { 'class' : 'fa fa-plus-square-o fa-lg' }),
						$('<span/>', {}).text(" " + txt.install)
					),
					$('<button/>', {
						'type'	: 'button',
						'id'	: iL1.prefijoid + '_bt_brand_uninstall',
						'class'	: 'btn btn-danger',
						'value'	: txt.uninstall
					})
					.on( "click", function(){ epm_config_tab_manager_bt('brand_uninstall', itemDataL1.id, 'brand'); })
					.append( 
						$('<i/>', { 'class' : 'fa fa-trash-o fa-lg' }),
						$('<span/>', {}).text(" " + txt.uninstall)
					),
					$('<button/>', {
						'type'	: 'button',
						'id'	: iL1.prefijoid + '_bt_brand_update',
						'class'	: 'btn btn-success ',
						'value'	: txt.update
					})
					.on( "click", function(){ epm_config_tab_manager_bt('brand_update', itemDataL1.id, 'brand'); })
					.append( 
						$('<i/>', { 'class' : 'fa fa-refresh fa-spin fa-lg' }),
						$('<span/>', {}).text(" " + txt.update)
					)
				)	
			);
			
			$(this)
			.children('.col-md-2')
			.append(
				$('<span/>', { 'id' : iL1.prefijoid + '_txt_last_update' }),
				$('<span/>', { 'id' : iL1.prefijoid + '_txt_update' })
			);
			
			
			
		});
	});
}

function epm_config_tab_manager_html_L2(iL1, iL2, itemDataL2, txt) 
{
	$('#' + iL1.boxappend).append(
		$("<div/>", { 'id' : iL2.boxelemen, 'class' : 'col-lg-12'})
		.append(
			$('<ul/>', { 'id' : iL2.prefijoid, 'class' : 'list-group' })
			.append(
				//LI -> LINE DEL TITULO
				$('<li/>', { 'class' : 'list-group-item active' })
				.append(
					$('<span/>', { 'class' : 'label label-default label-pill pull-xs-right count-products-brand' }).text("?/?"),
					$('<i/>',    { 'class' : 'fa fa-list-alt fa-lg' })
				).append( 
					$("<b/>", {}).text(" " + itemDataL2.long_name) 
				),
				
				//LI -> LINE DE LOS BOTONES GLOBALES
				$('<li/>', { 'class' : 'list-group-item' })
				.append(
					$('<div/>', { 'class' : 'btn-group btn-group-sm', 'role' : 'group' })
					.append(
						$('<button/>', {
							'type'	: 'button',
							'id'	: iL2.prefijoid + '_bt_fw_install',
							'class'	: 'btn btn-default btn-sm navbar-toggler hidden-sm-up'
						})
						.on( "click", function(){ epm_config_tab_manager_bt('fw_install', itemDataL2.id, 'firmware'); })
						.append( 
							$('<i/>', { 'class' : 'fa fa-plus-square-o fa-lg' }),
							$('<span/>', {}).text(" " + txt.fw_install)
						),
						$('<button/>', {
							'type'	: 'button',
							'id'	: iL2.prefijoid + '_bt_fw_uninstall',
							'class'	: 'btn btn-danger btn-sm'
						})
						.on( "click", function(){ epm_config_tab_manager_bt('fw_uninstall', itemDataL2.id, 'firmware'); })
						.append( 
							$('<i/>', { 'class' : 'fa fa-trash-o fa-lg' }),
							$('<span/>', {}).text(" " + txt.fw_uninstall)
						),
						$('<button/>', {
							'type'	: 'button',
							'id'	: iL2.prefijoid + '_bt_fw_update',
							'class'	: 'btn btn-default btn-sm'
						})
						.on( "click", function(){ epm_config_tab_manager_bt('fw_update', itemDataL2.id, 'firmware'); })
						.append( 
							$('<i/>', { 'class' : 'fa fa-refresh fa-spin fa-lg' }),
							$('<span/>', {}).text(" " + txt.fw_update)
						)
					) // END GRUPO BOTONES
				)  // END LI -> LINEA BOTONES GLOBLES
			)  // END UL
		) // END DIV GRUPO UL/LI
	);
}

function epm_config_tab_manager_html_L3(iL2, iL3, itemDataL3, txt, value_disable = 0, value_enable = 1) 
{
	$('#' + iL2.prefijoid)
	.append(
		$('<li/>', { 'class' : 'list-group-item radioset text-right', 'id' : iL3.boxelemen })
		.append(
			$('<span/>', { 'class' : 'pull-left' })
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : iL3.prefijoid
				}).text(" " + itemDataL3.model)
			),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL3.prefijoid,
				'id'		: iL3.prefijoid +'_disable',
				'value'		: value_disable
			})
			.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, iL3.prefijo, itemDataL3.id, iL2); }),
			$('<label/>', {
				'for'  		: iL3.prefijoid +'_disable',
				'data-for'	: iL3.prefijoid
			})
			.text(txt.disable + " ")
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-off' })
			),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL3.prefijoid,
				'id'		: iL3.prefijoid + '_enable',
				'value'		: value_enable
			})
			.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, iL3.prefijo, itemDataL3.id, iL2); }),
			$('<label/>', {
				'for'  	: iL3.prefijoid + '_enable',
				'data-for'	: iL3.prefijoid
			})
			.text(txt.enable + " ")
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-on' })
			)
		)
	);
}
//**** END: TAB/MANAGER ****






//**** INI: TAB/EDITOR ****
function epm_config_tab_editor_bt_show_hide_ajustar(iL0, itemData)
{
	//AJUSTAMOS BOTOSNES EN SU STATUS CORRECTO
	if (itemData.hidden == "") {
		$("#" + iL0.prefijoid + "_no").attr("disabled", true).prop( "checked", false);
		$("#" + iL0.prefijoid + "_yes").attr("disabled", true).prop( "checked", false);
		epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
	}
	else {
		var temp_input = $('input[name = "'+ iL0.prefijoid + '"]:checked');
		if (temp_input.length == 0) {
			temp_input = "-1";
		}
		if (itemData.hidden !== temp_input) {
			$("#" + iL0.prefijoid + "_no").attr("disabled", false).prop( "checked", (itemData.hidden == "0") ? true : false);
			$("#" + iL0.prefijoid + "_yes").attr("disabled", false).prop( "checked", (itemData.hidden == "1") ? true : false);
		}
	}
}

function epm_config_tab_editor_bt_show_hide_change(obt, idtype, idbt, brefreslist = true)
{
	if ((idbt == "") || (idtype == "")) { return false; }
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val().toLowerCase();
	
	var iL0 = new ItemsLevel("editor", idtype, idbt);
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			module_tab: "editor",
			command: "saveconfig",
			name:  obt_name,
			value: obt_val,
			idtype: idtype,
			idbt: idbt
		},
		dataType: 'json',
		timeout: 60000,
		error: function(data) {
			fpbxToast('ERROR AJAX 2!', 'ERROR!', 'error');
			$("#" + obt_name + "_no").attr("disabled", true).prop( "checked", false);
			$("#" + obt_name + "_yes").attr("disabled", true).prop( "checked", false);
			//epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				if (brefreslist == true) {
					var actTab = epm_global_get_tab_actual();
					epm_config_select_tab_ajax(actTab);
				}
				fpbxToast(data.txt.save_changes_ok, '', 'success');
				return true;
			} 
			else {
				$("#" + obt_name + "_no").attr("disabled", true).prop("checked", false);
				$("#" + obt_name + "_yes").attr("disabled", true).prop("checked", false);
				fpbxToast(data.message, data.txt.error, 'error');
				return false;
			}
		},
	});	
}

function epm_config_tab_editor_ajax_get_add_data (data, idtab)
{
	if (data.status == true) 
	{
		var boxappendL0 = "#epm_config_editor_all_list_box";
		if ((data.datlist == null) || (data.datlist == ""))
		{
			if ( $('#editor_alert_list_emtry').length == 0 ) 
			{
				$(boxappendL0).children("div").hide("slow", function () { $(this).remove(); });
				$(boxappendL0).append(
					$('<div/>', {
						'class' : 'alert alert-info',
						'role': 'alert',
						'id' : 'editor_alert_list_emtry'
					})
					.text('List empty. Click on the button "check for updates" on the Install/Uninstall tab to find data on the server.')
				);
			}
		}
		else 
		{
			epm_global_html_find_hide_and_remove('#editor_alert_list_emtry');
			
			//INI loop marcas
			$(data.datlist).each(function(index1, itemDataL1) 
			{
				var iL1 = new ItemsLevel(idtab, "marca", itemDataL1.id);
				if ( $('#' + iL1.boxelemen).length == 0 ) 
				{
					$(boxappendL0).append(epm_config_tab_editor_html_L0(iL1));
					epm_config_tab_editor_html_L1(iL1, itemDataL1, data.txt, 1, 0);
				}
				epm_config_tab_editor_bt_show_hide_ajustar(iL1, itemDataL1);
				
				
				//ini loop productos
				$(itemDataL1.products).each(function(indexL2, itemDataL2) 
				{
					var iL2 = new ItemsLevel(idtab, "producto", itemDataL2.id);
										
					if (itemDataL1.hidden == 1) { 
						epm_global_html_find_hide_and_remove('#' + iL2.boxelemen);
						return true; 
					}
					
					if ( $('#' + iL2.boxelemen).length == 0 ) 
					{
						epm_config_tab_editor_html_L2(iL1, iL2, itemDataL2, data.txt);
					}
					epm_config_tab_editor_bt_show_hide_ajustar(iL2, itemDataL2);
					
					
					//ini loop modelos
					$(itemDataL2.models).each(function(indexL3, itemDataL3) 
					{
						var iL3 = new ItemsLevel(idtab, "modelo", itemDataL3.id);
						if ( $('#' + iL3.boxelemen).length == 0 ) {
							epm_config_tab_editor_html_L3(iL2, iL3, itemDataL3, data.txt, "1", "0");
						}
						epm_config_tab_editor_bt_show_hide_ajustar(iL3, itemDataL3);
					});
					//end loop modelos
					
				});
				//end loop productos
				
			});
			//end loop marcas
		}
		return true;
	}
	else {
		fpbxToast(data.message,'ERROR!','error');
		return false;
	}
}


function epm_config_tab_editor_html_L0(iL1) 
{
	var htmlReturn = $('<div/>', { 'class' : 'element-container', 'id' : iL1.boxelemen })
		.append(
			$('<div/>', { 'class' : 'row' })
			.append(
				$('<div/>', { 'class' : 'col-md-12' })
				.append(
					$('<div/>', { 'class' : 'row' })
					.append(
						$('<div/>', { 'class' : 'form-group' })
						.append(
							$('<div/>', { 'class' : 'col-md-12' }),
							$('<div/>', { 'class' : 'col-md-2' }),
							$('<div/>', { 'class' : 'col-md-10', 'id' : iL1.boxappend })
						)
					)
				)
			)
		);
	return htmlReturn;
}

function epm_config_tab_editor_html_L1(iL1, itemData, txt, value_yes = 1, value_no = 0) 
{
	$('#' + iL1.boxelemen)
	.each( function() {
		$(this).find('.form-group').each( function() {
			$(this)
			.children('.col-md-12')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : iL1.prefijoid
				}).
				text(itemData.name),
				
				$('<div/>', { 'class' : 'radioset pull-xs-right' })
				.append(
					$('<input/>', {
						'type'		: 'radio',
						'name'		: iL1.prefijoid,
						'id'		: iL1.prefijoid +'_yes',
						'value'		: value_yes
					})
					.change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL1.prefijo, itemData.id, true); }),
					$('<label/>', {
						'for'  		: iL1.prefijoid +'_yes',
						'data-for'	: iL1.prefijoid
					})
					.text(txt.hide + " ")
					.append(
						$('<i/>', { 'class' : 'fa fa-toggle-off' })
					),
					$('<input/>', {
						'type'		: 'radio',
						'name'		: iL1.prefijoid,
						'id'		: iL1.prefijoid + '_no',
						'value'		: value_no
					}).change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL1.prefijo, itemData.id, true); }),
					$('<label/>', {
						'for'  		: iL1.prefijoid + '_no',
						'data-for'	: iL1.prefijoid
					})
					.text(txt.show + " ")
					.append(
						$('<i/>', { 'class' : 'fa fa-toggle-on' })
					)
				)
			);
			
		});
	});
}

function epm_config_tab_editor_html_L2(iL1, iL2, itemDataL2, txt)
{
	$('#' + iL1.boxappend).append(
		$("<div/>", { 'id' : iL2.boxelemen, 'class' : 'col-lg-12'})
		.append(
			$('<ul/>', { 'id' : iL2.prefijoid, 'class' : 'list-group' })
			.append(
				//LI -> LINE DEL TITULO
				$('<li/>', { 'class' : 'list-group-item active' })
				.append( 
					$("<b/>", {}).text(" " + itemDataL2.long_name) 
				)
			)  // END UL
		) // END DIV GRUPO UL/LI
	);
}

function epm_config_tab_editor_html_L3(iL2, iL3, itemDataL3, txt, value_yes = 1, value_no = 0)
{
	$('#' + iL2.prefijoid)
	.append(
		$('<li/>', { 'class' : 'list-group-item radioset text-right', 'id' : iL3.boxelemen })
		.append(
			$('<span/>', { 'class' : 'pull-left' })
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : iL3.prefijoid
				}).text(" " + itemDataL3.model)
			),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL3.prefijoid,
				'id'		: iL3.prefijoid +'_yes',
				'value'		: value_yes
			})
			.change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL3.prefijo, itemDataL3.id, false); }),
			$('<label/>', {
				'for'  		: iL3.prefijoid +'_yes',
				'data-for'	: iL3.prefijoid
			})
			.text(txt.hide + " ")
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-off' })
			),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL3.prefijoid,
				'id'		: iL3.prefijoid + '_no',
				'value'		: value_no
			})
			.change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL3.prefijo, itemDataL3.id, false); }),
			$('<label/>', {
				'for'  	: iL3.prefijoid + '_no',
				'data-for'	: iL3.prefijoid
			})
			.text(txt.show + " ")
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-on' })
			)
		)	
	);
}
//**** END: TAB/EDITOR ****