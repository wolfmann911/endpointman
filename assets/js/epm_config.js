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
	
	
	
	
	$("#button_check_for_updates").on( "click", function(){ epm_config_bt_update_check_click(); });
	
	
	
		
	$('#epm_config_manager_select_hidens').on('changed.bs.select', function (e, clickedIndex, newValue, oldValue) {
		var select = $(this).find('option:eq(' + clickedIndex + ')');
		var value  = $(this).find('option:eq(' + clickedIndex + ')').attr('value').toLowerCase();
		var stype = value.substring(0, 1); 
		var id = value.substring(2, value.len); 
		var type_send = "";
		switch (stype) {
			case "b":
				type_send = "marca";
				break;
				
			case "p":
				type_send = "producto";
				break;
				
			case "m":
				type_send = "modelo";
				break;
				
			default:
				type_send = "";
		}
		var url = "ajax.php?module=endpointman&module_sec=epm_config&command=saveconfig&typesavecfg=hidden&idtype=" + type_send + "&value=0&idbt=" + id;
		$.getJSON(url, function(data)
		{
			if (data.status == true) {
				epm_config_select_tab_ajax();
				fpbxToast(data.txt.save_changes_ok, '', 'success');
			} 
			else {
				fpbxToast("Error to Save Change!", "Error!", 'error');
			}
		});
		
		$('#epm_config_manager_select_hidens').selectpicker('toggle');
		epm_config_list_brand_model_hide_ajax_load($(this));
	});
	
	$('#epm_config_manager_select_hidens').on('loaded.bs.select', function (e) {
		epm_config_list_brand_model_hide_ajax_load($(this));
	});
	
	
	
		
}


function epm_config_windows_load (nTab = "") {
	epm_config_select_tab_ajax();
	$("#epm_config_manager_all_list_box").children("div").hide("slow", function () {
		$(this).remove();
	});	
}














function epm_config_manager_ajax_hide (e) 
{
	var id = e.getAttribute('data-id');
	var idtype  = e.getAttribute('data-label');
	var url = "ajax.php?module=endpointman&module_sec=epm_config&command=saveconfig&typesavecfg=hidden&idtype=" + idtype + "&value=1&idbt=" + id;
	$.getJSON(url, function(data)
	{
		if (data.status == true) {
			epm_config_list_brand_model_hide_ajax_load($('#epm_config_manager_select_hidens'));
			epm_config_select_tab_ajax();
			fpbxToast(data.txt.save_changes_ok, '', 'success');
		} 
		else {
			fpbxToast("Error to Save Change!", "Error!", 'error');
		}
	});
}

function epm_config_list_brand_model_hide_ajax_load (e) 
{
	var select = e;
	var url    = e.attr('data-url');
	var id     = e.attr('data-id');
	var label  = e.attr('data-label');
	select.html('');
	select.append('<option data-icon="fa fa-refresh fa-spin fa-fw" value="" selected>Cargando...</option>');
	select.selectpicker('refresh');
	
	$.getJSON(url, function(data)
	{
		select.html('');
		$.each(data.datlist, function(keyL0, valL0)
		{
			if (valL0['hidden'] == "1") 
			{
				select.append('<option data-icon="fa fa-files-o" value="b_' + valL0['id'] + '" selected>' + valL0['name'] + '</option>');
			}
			else 
			{
				select.append('<option data-icon="fa fa-files-o" value="b_' + valL0['id'] + '" disabled>' + valL0['name'] + '</option>');
				
				select.append('<optgroup class="optgrp_b_' + valL0['id'] + '" label="Modelos ' + valL0['name'] + '"></optgroup>');
				var seloptgroup = select.find("optgroup.optgrp_b_" + valL0['id']);
				if (valL0.products == "") { return true; }
				$.each(valL0.products, function(keyL1, valL1)
				{
					if (valL1.models == "") { return true; }
					$.each(valL1.models, function(keyL2, valL2)
					{
						if (valL2['hidden'] == "1") {
							seloptgroup.append('<option data-icon="fa fa-pencil-square-o" data-subtext="' + valL1['short_name'] + '" value="m_' + valL2['id'] + '" selected>' + valL2['model'] + '</option>');
						}
					});
				});	
			}
		}); //END eachL0
		select.selectpicker('refresh');
	});	// END getJSON
}













//**** INI: FUNCTION GLOBAL SEC ****
function end_module_actions_epm_config(acctionname = "")
{
	switch(acctionname) {
		case "bt_update_chkeck":
		case "manager_bt":
			epm_config_select_tab_ajax();
			break;
		
		default:
			fpbxToast('end_module_actions_epm_config -> acctionname no send!','JS!','warning');
	}
}


function epm_config_select_tab_ajax()
{
	clearTimeout(v_sTimerUpdateAjax);

	epm_global_html_find_show_hide("#epm_config_manager_list_loading", true, 0, true);
	
	waitingDialog.show();
	var $tmp = epm_config_LoadContenidoAjax();
	if ($tmp = "false") {
		setTimeout(function () {waitingDialog.hide();}, 3000);
		epm_global_html_find_show_hide("#epm_config_manager_list_loading", false, 3000, true);
	}
	else {
		epm_global_html_find_show_hide("#epm_config_manager_list_loading", false, 1000, true);
	}
	return true;
}

function epm_config_LoadContenidoAjax()
{
	clearTimeout(v_sTimerUpdateAjax);
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			command: "list_all_brand"
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX 1:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return;
		},
		success: function(data) {
			var tTimer = 20000;
			epm_config_tab_manager_ajax_get_add_data(data);
			v_sTimerUpdateAjax = setTimeout(function () { epm_config_LoadContenidoAjax(); }, tTimer);
			setTimeout(function () {waitingDialog.hide();}, 3000);
		}
	});
	return true;
}

function ItemsLevel(prefijo = "", id = 0) 
{
	this.def_box = "_box";
	this.def_sel = "_box_select";
	this.def_sit = "_box_subitems";
	
	this.id = id;
	this.tab = "manager";
	this.prefijo = prefijo;
	
	this.prefijoid = this.tab + "_" + this.prefijo  + "_" + this.id;
	this.boxelemen = this.prefijoid + this.def_box;
	this.boxappend = this.prefijoid + this.def_sel;
	this.boxsubite = this.prefijoid + this.def_sit;
}


function epm_config_bt_update_check_click() 
{
	clearTimeout(v_sTimerUpdateAjax);
	var urlStr = "config.php?display=epm_config&command=check_for_updates";
	epm_global_dialog_action("bt_update_chkeck", urlStr, null, "Update Package Info", "", { "Close": function() { $(this).dialog("close"); } });
}

//**** END: FUNCTION GLOBAL SEC ****














function epm_config_tab_manager_ajax_get_add_data(data)
{
	var boxappendL0 = "#epm_config_manager_all_list_box";
	
	if ($('#button_check_for_updates').is(':disabled') == true) {
		$("#button_check_for_updates").attr("disabled", false);
	}
	
	if ((data == null) || (data == ""))
	{
		//ERROR AL OBTENER DATOS!!!
		if ( $('#alert_list_emtry_error').length == 0 ) 
		{
			$(boxappendL0).children("div").hide("slow", function () { $(this).remove(); });
			$(boxappendL0).append(
				$('<div/>', { 'class' : 'panel panel-danger', 'id' : 'alert_list_emtry_error' })
				.append(
					$('<div/>', { 'class' : 'panel-heading' }).append( $('<h3/>', { 'class' : 'panel-title' }).text('ERROR NOT GET DATA BY SERVERY!!!') ),
					$('<div/>', { 'class' : 'panel-body' }).text('The server no return any date!!!')
				)
			);
		}
		return false;
	}
	epm_global_html_find_hide_and_remove('#alert_list_emtry_error');
	
	
	
	if (data.status == false) 
	{
		//LISTA VACIA NO HAY NINGUNA MARCA.
		if ( $('#alert_list_emtry_error_ajax').length == 0 ) 
		{
			$(boxappendL0).children("div").hide("slow", function () { $(this).remove(); });
			$(boxappendL0).append(
				$('<div/>', { 'class' : 'panel panel-danger', 'id' : 'alert_list_emtry_error_ajax' })
				.append(
					$('<div/>', { 'class' : 'panel-heading' }).append( $('<h3/>', { 'class' : 'panel-title' }).text('ERROR AJAX BY SERVER!!!') ),
					$('<div/>', { 'class' : 'panel-body' }).text(data.message)
				)
			);
		}
		return false;
	}
	epm_global_html_find_hide_and_remove('#alert_list_emtry_error_ajax');
	
	
	
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
		return false;
	}
	epm_global_html_find_hide_and_remove('#manager_alert_list_emtry');
	
	
	
	//L1: INI loop marcas
	$(data.datlist).each(function(index, itemData) 
	{
		var iL1 = new ItemsLevel("marca", itemData.id);
		if (itemData.hidden == 1) {
			epm_global_html_find_hide_and_remove('#' + iL1.boxelemen); 
			return true;
		}
		
		if ( $('#' + iL1.boxelemen).length == 0 ) 
		{
			$(boxappendL0)
			.append(
				$('<div/>', { 'class' : 'panel panel-primary', 'id' : iL1.boxelemen })
				.append(
					$('<div/>', { 'class' : 'panel-heading' })
					.append( 
						$('<h3/>', { 'class' : 'panel-title' })
						.text(itemData.name)
						.append(
							$('<a/>', {
								'href'	 : '#',
								'id'	 : iL1.prefijoid + '_bt_brand_hidden',
								'class'	 : 'btn btn-default btn-sm pull-xs-right',
								'data-id': itemData.id,
								'data-label':'marca'
							})
							.on( "click", function(){ epm_config_manager_ajax_hide(this); })
							.append( $('<i/>', { 'class' : 'fa fa-times', 'aria-hidden':'true' }) )
						)
					),
					$('<div/>', { 'class' : 'panel-body'})
					.append(
						$('<div/>', { 'class' : 'col-md-2' })
						.append(
							$('<span/>', { 'id' : iL1.prefijoid + '_txt_last_update' }),
							$('<span/>', { 'id' : iL1.prefijoid + '_txt_update' }),
							
							
							$('<div/>', { 'class':'btn-group-vertical', 'role':'group' })
							.append(
								$('<button/>', {
									'type'	: 'button',
									'id'	: iL1.prefijoid + '_bt_brand_install',
									'class'	: 'btn btn-success',
									'value'	: data.txt.install
								})
								.on( "click", function(){ epm_config_tab_manager_bt('brand_install', itemData.id, 'brand'); })
								.append( 
									$('<i/>', { 'class' : 'fa fa-plus-square-o fa-lg' }),
									$('<span/>', {}).text(" " + data.txt.install)
								),
								$('<button/>', {
									'type'	: 'button',
									'id'	: iL1.prefijoid + '_bt_brand_uninstall',
									'class'	: 'btn btn-danger',
									'value'	: data.txt.uninstall
								})
								.on( "click", function(){ epm_config_tab_manager_bt('brand_uninstall', itemData.id, 'brand'); })
								.append( 
									$('<i/>', { 'class' : 'fa fa-trash-o fa-lg' }),
									$('<span/>', {}).text(" " + data.txt.uninstall)
								),
								$('<button/>', {
									'type'	: 'button',
									'id'	: iL1.prefijoid + '_bt_brand_update',
									'class'	: 'btn btn-success ',
									'value'	: data.txt.update
								})
								.on( "click", function(){ epm_config_tab_manager_bt('brand_update', itemData.id, 'brand'); })
								.append( 
									$('<i/>', { 'class' : 'fa fa-refresh fa-spin fa-lg' }),
									$('<span/>', {}).text(" " + data.txt.update)
								)
							)
							
							
						),
						$('<div/>', { 'class' : 'col-md-10', 'id' : iL1.boxappend })
					),
					$('<div/>', { 'class' : 'panel-footer'})
				)
			);
		
			//END CREAR OBJETO - LEVEL 1
		}
		
		
		$('#' + iL1.prefijoid + "_txt_update").text(data.txt.new_pack_mod + ' [' + itemData.update_vers_txt + ']');
		$('#' + iL1.prefijoid + "_txt_last_update").text(data.txt.pack_last_mod + ' [' + itemData.cfg_ver_datetime_txt + ']');
		epm_config_tab_manager_bt_enable_disable_ajustar(iL1, itemData, "L1");
		
		
		//INI - EN CASO DE QUE NO ESTE INSTALADO LA MARCA MUESTAR UN RECUADRO DICIENDOLO
		if (itemData.installed == "0") {
			//epm_global_html_find_hide_and_remove('#' + iL1.boxsubite);
			if ($('#info_brand_no_install' + itemData.id).length == 0)
			{
				$('#' + iL1.boxappend).children("div").hide("slow", function () { $(this).remove(); });
				$('#' + iL1.boxappend).append(
					$('<div/>', { 'class' : 'panel panel-warning' , 'id' : 'info_brand_no_install' + itemData.id }).append(
						$('<div/>', { 'class' : 'panel-heading' }).append( $('<h3/>', { 'class' : 'panel-title' }).text('MARCA NO INSTALADA!') ),
						$('<div/>', { 'class' : 'panel-body' }).text('Esta marca no esta instalada, haz Click en el boton Instalar para instalar el paquete.')
					)
				);
			}
			return true;
		}
		epm_global_html_find_hide_and_remove('#info_brand_no_install' + itemData.id);
		//END - EN CASO DE QUE NO ESTE INSTALADO LA MARCA MUESTAR UN RECUADRO DICIENDOLO
		
		
		
		
		//L2: ini loop productos
		if (itemData.products.length == 0) { return true; }
		$(itemData.products).each(function(indexL2, itemDataL2) 
		{	
			var iL2 = new ItemsLevel("producto", itemDataL2.id);
			if (itemDataL2.hidden == 1) {
				epm_global_html_find_hide_and_remove('#' + iL2.boxelemen); 
				return true; 
			}
			if ( $('#' + iL2.boxelemen).length == 0 ) 
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
							$('<li/>', { 'class' : 'list-group-item active' })
							.append(
								$('<div/>', { 'class' : 'btn-group btn-group-sm', 'role' : 'group' })
								.append(
									$('<button/>', {
										'type'	: 'button',
										'id'	: iL2.prefijoid + '_bt_fw_install',
										'class'	: 'btn btn-default btn-sm'
									})
									.on( "click", function(){ epm_config_tab_manager_bt('fw_install', itemDataL2.id, 'firmware'); })
									.append( 
										$('<i/>', { 'class' : 'fa fa-plus-square-o fa-lg' }),
										$('<span/>', {}).text(" " + data.txt.fw_install)
									),
									$('<button/>', {
										'type'	: 'button',
										'id'	: iL2.prefijoid + '_bt_fw_uninstall',
										'class'	: 'btn btn-danger btn-sm'
									})
									.on( "click", function(){ epm_config_tab_manager_bt('fw_uninstall', itemDataL2.id, 'firmware'); })
									.append( 
										$('<i/>', { 'class' : 'fa fa-trash-o fa-lg' }),
										$('<span/>', {}).text(" " + data.txt.fw_uninstall)
									),
									$('<button/>', {
										'type'	: 'button',
										'id'	: iL2.prefijoid + '_bt_fw_update',
										'class'	: 'btn btn-default btn-sm'
									})
									.on( "click", function(){ epm_config_tab_manager_bt('fw_update', itemDataL2.id, 'firmware'); })
									.append( 
										$('<i/>', { 'class' : 'fa fa-refresh fa-spin fa-lg' }),
										$('<span/>', {}).text(" " + data.txt.fw_update)
									)
								) // END GRUPO BOTONES
							)  // END LI -> LINEA BOTONES GLOBLES
						)  // END UL
					) // END DIV GRUPO UL/LI
				);	
			}
			epm_config_tab_manager_bt_enable_disable_ajustar(iL2, itemDataL2, "L2");
			
			
			
			
			
			
			//L3: Ini loop modelos
			if (itemDataL2.models.length == 0) { return true; }
			$(itemDataL2.models).each(function(indexL3, itemDataL3) 
			{
				var iL3 = new ItemsLevel("modelo", itemDataL3.id);
				if (itemDataL3.hidden == 1) { 
					epm_global_html_find_hide_and_remove('#' + iL3.boxelemen); 
					return true; 
				}
				if ( $('#' + iL3.boxelemen).length == 0 ) 
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
								})
								.text(" " + itemDataL3.model + " ")
								.append(
									$('<a/>', { 'class'	: '', 'href' : '#', 'onclick' : 'epm_config_manager_ajax_hide(this);', 'data-label' : 'modelo', 'data-id' : itemDataL3.id })
									.append( 
										$('<i/>', { 'class' : 'fa fa-times', 'aria-hidden':'true' })
									)
								)
							),
							$('<input/>', {
								'type'		: 'radio',
								'name'		: iL3.prefijoid,
								'id'		: iL3.prefijoid +'_disable',
								'value'		: 0
							})
							.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, iL3.prefijo, itemDataL3.id, iL2); }),
							$('<label/>', {
								'for'  		: iL3.prefijoid +'_disable',
								'data-for'	: iL3.prefijoid
							})
							.text(data.txt.disable + " ")
							.append(
								$('<i/>', { 'class' : 'fa fa-toggle-off' })
							),
							$('<input/>', {
								'type'		: 'radio',
								'name'		: iL3.prefijoid,
								'id'		: iL3.prefijoid + '_enable',
								'value'		: 1
							})
							.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, iL3.prefijo, itemDataL3.id, iL2); }),
							$('<label/>', {
								'for'  	: iL3.prefijoid + '_enable',
								'data-for'	: iL3.prefijoid
							})
							.text(data.txt.enable + " ")
							.append(
								$('<i/>', { 'class' : 'fa fa-toggle-on' })
							)
						)
					);
					
					
				}
				epm_config_tab_manager_bt_enable_disable_ajustar(iL3, itemDataL3, "L3");
			});
			//L3: end loop modelos
			epm_config_tab_manager_countlist(iL2);
			epm_config_html_ordenar_lista_L3(iL2.prefijoid, true);
			
			
		});
		//L2: end loop productos
		epm_config_html_ordenar_lista_L2(iL1.boxappend, true);
		
	});
	//L1: end loop marcas
	epm_config_html_ordenar_lista_L1(boxappendL0, true);
	
	
	
	if ($(boxappendL0).children("div.panel").length == 0)
	{
		if ( $('#manager_alert_list_hiden').length == 0 ) 
		{
			$(boxappendL0).append(
				$('<div/>', {
					'class' : 'alert alert-info',
					'role': 'alert',
					'id' : 'manager_alert_list_hiden'
				})
				.text("All brand's are hidden.")
			);
		}
		return true;
	}
	epm_global_html_find_hide_and_remove('#manager_alert_list_hiden');
	return true;
}









function epm_config_html_ordenar_lista_L1(box_root, orden)
{
	var elemLista = null;
	var lista = $(box_root);
	
	var vL0 = lista.children("div.panel").get().length;
	if (vL0 === 0 ) { return; }
	
	elemLista = lista.children("div.panel").get();
	elemLista.sort(function(a, b) {
		var compA = $(a).find('h3.panel-title').text().toUpperCase().trim();
		var compB = $(b).find('h3.panel-title').text().toUpperCase().trim();
		return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
	});
	
	if(orden) {
		$(elemLista).each( function(ind, elem) { $(lista).append(elem); });
	} else{
		$(elemLista).each( function(ind, elem) { $(lista).prepend(elem); });
	}
}

function epm_config_html_ordenar_lista_L2(box_root, orden)
{
	var elemLista = null;
	var lista = $('#' + box_root);
	
	var vL0 = lista.children("div").children("div > ul").get().length;
	if (vL0 === 0 ) { return; }

	elemLista = lista.children("div").children("div > ul").get();
	elemLista.sort(function(a, b) {
		var compA = $(a).find('li:first > b').text().toUpperCase().trim();
		var compB = $(b).find('li:first > b').text().toUpperCase().trim();
		return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
	});
	
	if(orden) {
		$(elemLista).each( function(ind, elem) { $(lista).append(elem); });
	} else{
		$(elemLista).each( function(ind, elem) { $(lista).prepend(elem); });
	}
	
}

function epm_config_html_ordenar_lista_L3(box_root, orden)
{
	var elemLista = null;
	var lista = $('#' + box_root);

	var vL0 = lista.children("li:not(.active)").get().length;
	if (vL0 === 0 ) { return; }

	elemLista = lista.children("li:not(.active)").get();
	elemLista.sort(function(a, b) {
		var compA = $(a).find('span  > label').text().toUpperCase().trim();
		var compB = $(b).find('span  > label').text().toUpperCase().trim();
		return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
	});
	
	if(orden) {
		$(elemLista).each( function(ind, elem) { $(lista).append(elem); });
	} else{
		$(elemLista).each( function(ind, elem) { $(lista).prepend(elem); });
	}
}






























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
			command: "saveconfig",
			typesavecfg:  "enabled",
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
//**** END: TAB/MANAGER ****
