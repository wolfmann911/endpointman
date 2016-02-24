var v_sTimerUpdateAjax = "";

function epm_config_document_ready () {
	
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


/**** INI: FUNCTION GLOBAL SEC ****/

function epm_config_select_tab_ajax(idtab)
{	
	clearTimeout(v_sTimerUpdateAjax);
	if (idtab == "") {
		fpbxToast('epm_config_select_tab_ajax -> id invalid (' + idtab + ')!','JS!','warning');
		return false;
	}
	
	clearTimeout(v_sTimerUpdateAjax);
	$("#epm_config_"+ idtab +"_load_init").each(function() {
		if ($(this).css('display') == "none") 
		{
			$(this).show("slow", function() {
				var $tmp = epm_config_LoadContenidoAjax(idtab, "list_all_brand");
			});
		}
		else {
			var $tmp = epm_config_LoadContenidoAjax(idtab, "list_all_brand");
		}
	});
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
	}
}

function epm_config_LoadContenidoAjax(idtab, opt)
{
	clearTimeout(v_sTimerUpdateAjax);
	opt = opt.trim();
	idtab = idtab.trim();
	statustab = $("#" + idtab).css('display').trim();
	
	if ((idtab == "") || (statustab == "") || (opt == "")) { return false; }
	if (statustab == "none") { return false; }
	
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
			epm_global_html_find_show_hide("#epm_config_"+ idtab +"_load_init", false, 800, true);
		}
	});
	return true;
}

function epm_config_html_ordenar_lista(box_root, orden)
{
	var lista = $(box_root);
	
	var vL0 = lista.children("div.element-container").get().length
	var vL1 = lista.children("div.element-container").children("div.section-title").get().length
	var vLT = vL0 - vL1
	
	if (vL0 == 0 ) { return; }
	if (vLT == 0) 
	{
		var elemLista =  lista.children("div.element-container").get();
		elemLista.sort(function(a, b) {
			var compA = $(a).children("div.section-title").find('h3').text().toUpperCase().trim();
			var compB = $(b).children("div.section-title").find('h3').text().toUpperCase().trim();
			return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
	}
	else if (vLT > 0) 
	{
		var elemLista =  lista.children("div.element-container").get();
		elemLista.sort(function(a, b) {
			var compA = $(a).find('.form-group').find('div.col-md-3 > label').first().text().toUpperCase().trim();
			var compB = $(b).find('.form-group').find('div.col-md-3 > label').first().text().toUpperCase().trim();
			return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
	}
	else { return; }
	if(orden){
		$(elemLista).each( function(ind, elem) { $(lista).append(elem); });
	}else{
		$(elemLista).each( function(ind, elem) { $(lista).prepend(elem); });
	}
}

function epm_config_tab_html_L0(prefijoid, txt_ayuda) 
{
	var htmlReturn = $('<div/>', {
			'class' : 'element-container',
			'id'    : prefijoid + '_box'
		})
		.append(
			$('<div/>', { 'class' : 'row' })
			.append(
				$('<div/>', { 'class' : 'col-md-12' })
				.append(
					$('<div/>', { 'class' : 'row' })
					.append(
						$('<div/>', { 'class' : 'form-group' })
						.append(
							$('<div/>', { 
								'class' : 'col-md-3'
							}),
							$('<div/>', { 
								'class' : 'col-md-9',
								'id'	: prefijoid + '_box_select'
							})
						)
					)
				)
			),
			$('<div/>', { 'class' : 'row' })
			.append(
				$('<div/>', { 'class' : 'col-md-12' })
				.append(
					$('<span/>', { 
						'class' : 'help-block fpbx-help-block', 
						'id' 	: prefijoid + '-help' 
					}).text(txt_ayuda)
				)
			)
		);
	return htmlReturn;
}

function epm_config_tab_html_L1(boxpadre, prefijoid, titulo, txt_ayuda) 
{
	$(boxpadre)
	.append(
		$('<div/>', {
			'class' : 'element-container',
			'id'    : prefijoid + '_box'
		})
		.append(
			$('<div/>', {
				'class' 	: 'section-title',
				'data-for' 	: prefijoid + '_section_title'
			})
			.append( 
				$('<h3/>', {  })
				.text(titulo)
				.on( "click", function(){ epm_global_html_find_show_hide('#' + prefijoid + '_section_box', 'auto' , 0, true); })
			),
			$('<div/>', {
				'class' 	: 'section',
				'data-id' 	: prefijoid + '_section_title',
				'id'    	: prefijoid + '_section_box',
			})
			.append(
			
			
				$('<div/>', { 'class' : 'row' })
				.append(
					$('<div/>', { 'class' : 'col-md-12' })
					.append(
						$('<div/>', { 'class' : 'row' })
						.append(
							$('<div/>', { 'class' : 'form-group' })
							.append(
								$('<div/>', { 
									'class' : 'col-md-12',
									'id'	: prefijoid + '_box_select'
								})
							)
						)
					)
				),
				$('<div/>', { 'class' : 'row' })
				.append(
					$('<div/>', { 'class' : 'col-md-12' })
					.append(
						$('<span/>', { 
							'class' : 'help-block fpbx-help-block', 
							'id' 	: prefijoid + '-help' 
						}).text(txt_ayuda)
					)
				)
				
				
			)
		)
	);
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

function CrearSubListItem(iL0)
{
	if ( $('#' + iL0.boxsubite).length == 0 ) {
		$('#' + iL0.boxappend).append( $('<div/>', { 'class' : 'sortable', 'id' : iL0.boxsubite }) )
	}
}
/**** END: FUNCTION GLOBAL SEC ****/









/**** INI: TAB/MANAGER ****/
function epm_config_tab_manager_ajax_get_add_data(data, idtab)
{
	if ($('#button_check_for_updates').is(':disabled') ==  true) {
		$("#button_check_for_updates").attr("disabled", false).on( "click", function(){ epm_config_tab_manager_bt_update_check_click(); });
	}
	if (data.status == true) {
		var boxappendL0 = "#epm_config_manager_all_list_box";
		if ((data.datlist == null) || (data.datlist == ""))
		{
			if ( $('#manager_alert_list_emtry').length == 0 ) 
			{
				$(boxappendL0).children("div").hide("slow", function () { $(this).remove(); });
				$(boxappendL0).append(
					$('<div/>', {
						'class' : 'alert alert-info',
						'role': 'alert',
						'id' : 'manager_alert_list_emtry'
					})
					.text('List empty. Click the "check for updates" button to search for data on the server.')
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
				if (itemData.hidden == 1) { epm_global_html_find_hide_and_remove('#' + iL1.boxelemen); return; }
				if ( $('#' + iL1.boxelemen).length == 0 ) 
				{
					$(boxappendL0).append(epm_config_tab_html_L0(iL1.prefijoid, data.txt.ayuda_marca));
					epm_config_tab_manager_html_L1(itemData, iL1.prefijoid, data.txt, idtab);
				}
				
				$('#' + iL1.prefijoid + "_txt_update").text(data.txt.new_pack_mod + ' [' + itemData.update_vers + ']');
				$('#' + iL1.prefijoid + "_txt_last_update").text(data.txt.pack_last_mod + ' [' + itemData.cfg_ver_datetime + ']');
				$('#' + iL1.prefijoid + "_checkbox_install").prop({'checked' : (itemData.local == 0 ? true : false)});
				epm_config_tab_manager_bt_enable_disable_ajustar(iL1, itemData, "L1");
				if (itemData.installed == 0) { epm_global_html_find_hide_and_remove('#' + iL1.boxsubite); return; }
				
				//L2: ini loop productos
				$(itemData.products).each(function(indexL2, itemDataL2) 
				{
					var iL2 = new ItemsLevel(idtab, "producto", itemDataL2.id);
					if (itemDataL2.hidden == 1) { epm_global_html_find_hide_and_remove('#' + iL2.boxelemen); return; }
					if ( $('#' + iL2.boxelemen).length == 0 ) 
					{
						CrearSubListItem(iL1);
						$('#' + iL1.boxsubite).append(epm_config_tab_html_L0(iL2.prefijoid, data.txt.ayuda_producto));
						epm_config_tab_manager_html_L2(itemDataL2, iL2.prefijoid, data.txt, idtab);
					}
					epm_config_tab_manager_bt_enable_disable_ajustar(iL2, itemDataL2, "L2");
					
					//L3: Ini loop modelos
					$(itemDataL2.models).each(function(indexL3, itemDataL3) 
					{
						var iL3 = new ItemsLevel(idtab, "modelo", itemDataL3.id);
						if (itemDataL3.hidden == 1) { epm_global_html_find_hide_and_remove('#' + iL3.boxelemen); return; }
						if ( $('#' + iL3.boxelemen).length == 0 ) 
						{
							CrearSubListItem(iL2);
							$('#' + iL2.boxsubite).append(epm_config_tab_html_L0(iL3.prefijoid, data.txt.ayuda_model));
							epm_config_tab_manager_html_L3(itemDataL3, iL3.prefijo, iL3.prefijoid, itemDataL3.model, "0", data.txt.disable + " ", "1", data.txt.enable + " ", idtab);
						}
						epm_config_tab_manager_bt_enable_disable_ajustar(iL3, itemDataL3, "L3");
					});
					//L3: end loop modelos
					epm_config_html_ordenar_lista(iL2.boxappend, true);
					
				});
				//L2: end loop productos
				epm_config_html_ordenar_lista(iL1.boxappend, true);
				
			});
			//L1: end loop marcas
			epm_config_html_ordenar_lista(boxappendL0, true);
			
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
			epm_global_update_jquery_msg_help();
		}
		return true;
	}
	else {
		fpbxToast(data.message,'ERROR!','error');
		return false;
	}
}

function epm_config_tab_manager_bt_update_check_click() 
{
	var urlStr = "config.php?display=epm_config&quietmode=1&module_tab=manager&command=check_for_updates";
	box = epm_global_dialog_action("bt_update_chkeck", urlStr);
}

function epm_config_tab_manager_bt(opt, idfw, command) 
{
	if ((opt == "") || (idfw == "") || (command == "")) { return false; }
	clearTimeout(v_sTimerUpdateAjax);
	
	var urlStr = "config.php?display=epm_config&quietmode=1&module_tab=manager&command=" + command + "&command_sub=" + opt + "&idfw=" + idfw;
	box = epm_global_dialog_action("manager_bt", urlStr, null, "Status", 'epm_config_tab_manager_bt_dialog');
}

function epm_config_tab_manager_bt_enable_disable_ajustar(iL0, itemData, level) 
{
	if (level == "L1") 
	{
		epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_brand_install", ((itemData.installed == 1) ? false : true));
		epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_brand_uninstall" , ((itemData.installed == 1) ? true : false));
		epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_brand_update", ((itemData.update == 0) ? false : true));
		epm_global_html_find_show_hide('#' + iL0.prefijoid + "_txt_update" , ((itemData.update == 0) ? false : true));
		return;
	}
	else if (level == "L2") 
	{
		if ((itemData.fw_type == "install") || (itemData.fw_type == "uninstall")) 
		{
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_install", 		((itemData.fw_type == "install") ? true : false));
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_uninstall" , 	((itemData.fw_type == "install") ? false : true));
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_update" , 		((itemData.update_fw == 0) ? false : true));
			//epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt-pr-update" , 		((itemData.update == 0) ? false : true));
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt-pr-update", false);
		}
		else if (itemData.fw_type == "nothing") {
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_install", false);
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_uninstall", false);
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt_fw_update", false);
			epm_global_html_find_show_hide('#' + iL0.prefijoid + "_bt-pr-update", false);
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
			if (itemData.enabled != temp_input) {
				$("#" + iL0.prefijoid + "_enable").attr("disabled", false).prop( "checked", ((itemData.enabled == 1) ? true : false));
				$("#" + iL0.prefijoid + "_disable").attr("disabled", false).prop( "checked", ((itemData.enabled == 0) ? true : false));
			}
		}
		return;
	}	
}

function epm_config_tab_manager_bt_enable_disable_change(obt, idtab, idtype, idbt) 
{
	if ((idtab == "") || (idbt == "") || (idtype == "")) { return false; }
	
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val().toLowerCase();
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			module_tab: idtab,
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
			epm_global_html_find_hide_and_remove('#' + obt_name + '_box_subitems');
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				epm_global_html_find_hide_and_remove('#' + obt_name + '_box_subitems');
				fpbxToast(data.txt.save_changes_ok, '', 'success');
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

function epm_config_tab_manager_html_L1(data, prefijoid, txt, idtab) 
{
	$('#' + prefijoid + '_box' )
	.each( function() {
		$(this).find('.form-group').each( function() {
			$(this)
			.children('.col-md-3')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : prefijoid + '_label'
				}).text(data.name),
				$('<i/>', {
					'class'  	: 'fa fa-question-circle fpbx-help-icon',
					'data-for'	: prefijoid + '_label'
				}),
				$('<br/>'),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_brand_install',
					'class'	: 'btn btn-default',
					'value'		: txt.install
				})
				.on( "click", function(){ epm_config_tab_manager_bt('brand_install', data.id, 'brand'); }),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_brand_uninstall',
					'class'	: 'btn btn-default',
					'value'		: txt.uninstall
				})
				.on( "click", function(){ epm_config_tab_manager_bt('brand_uninstall', data.id, 'brand'); }),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_brand_update',
					'class'	: 'btn btn-default',
					'value'	: txt.update
				})
				.on( "click", function(){ epm_config_tab_manager_bt('brand_update', data.id, 'brand'); })
				
			);
			
			$(this)
			.children('.col-md-9')
			.append(
				$('<p/>', { 'id' : prefijoid + '_txt_last_update' }),
				$('<p/>', {	'id' : prefijoid + '_txt_update' })
			);
		});
	});
}

function epm_config_tab_manager_html_L2(data, prefijoid, txt, idtab) 
{
	$('#' + prefijoid + '_box' )
	.addClass( "L2" )
	.each( function() {
		$(this).find('.form-group').each( function() {
			$(this)
			.children('.col-md-3')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : prefijoid
				}).text(data.short_name),
				$('<i/>', {
					'class'  	: 'fa fa-question-circle fpbx-help-icon',
					'data-for'	: prefijoid
				})
			);
			
			$(this)
			.children('.col-md-9')
			.append(
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt-pr-update',
					'class'	: 'btn btn-default',
					'value'	: txt.update
				})
				.on( "click", function(){ epm_config_tab_manager_bt('pr_update', data.id, 'firmware'); }),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_fw_install',
					'class'	: 'btn btn-default',
					'value'	: txt.fw_install
				})
				.on( "click", function(){ epm_config_tab_manager_bt('fw_install', data.id, 'firmware'); }),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_fw_uninstall',
					'class'	: 'btn btn-default',
					'value'	: txt.fw_uninstall
				})
				.on( "click", function(){ epm_config_tab_manager_bt('fw_uninstall', data.id, 'firmware'); }),
				$('<input/>', {
					'type'	: 'button',
					'id'	: prefijoid + '_bt_fw_update',
					'class'	: 'btn btn-default',
					'value'	: txt.fw_update
				})
				.on( "click", function(){ epm_config_tab_manager_bt('fw_update', data.id, 'firmware'); })
			);
		});
	});
}

function epm_config_tab_manager_html_L3(data, prefijo, prefijoid, name, value_disable, txt_bt_disable, value_enable, txt_bt_enable, idtab) 
{
	$('#' + prefijoid + '_box' )
	.addClass("L3")
	.each( function() {
		$(this).find('.form-group').each( function() {
			$(this)
			.children('.col-md-3')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : prefijoid
				}).text(name),
				$('<i/>', {
					'class'  	: 'fa fa-question-circle fpbx-help-icon',
					'data-for'	: prefijoid
				})
			);
			
			$(this)
			.children('.col-md-9')
			.addClass("radioset")
			.append(
				$('<input/>', {
					'type'		: 'radio',
					'name'		: prefijoid,
					'id'		: prefijoid +'_disable',
					'value'		: value_disable
				})
				.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, idtab, prefijo, data.id); }),
				$('<label/>', {
					'for'  		: prefijoid +'_disable',
					'data-for'	: prefijoid
				})
				.text(txt_bt_disable)
				.append(
					$('<i/>', { 'class' : 'fa fa-toggle-off' })
				),
				$('<input/>', {
					'type'		: 'radio',
					'name'		: prefijoid,
					'id'		: prefijoid + '_enable',
					'value'		: value_enable
				})
				.change(function(){ epm_config_tab_manager_bt_enable_disable_change(this, idtab, prefijo, data.id); }),
				$('<label/>', {
					'for'  	: prefijoid + '_enable',
					'data-for'	: prefijoid
				})
				.text(txt_bt_enable)
				.append(
					$('<i/>', { 'class' : 'fa fa-toggle-on' })
				)
			);
		});
	});
}
/**** END: TAB/MANAGER ****/
















/**** INI: TAB/EDITOR ****/
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
					epm_config_tab_html_L1(boxappendL0, iL1.prefijoid, itemDataL1.name, data.txt.ayuda_marca);
					epm_config_tab_editor_html_L2(itemDataL1, iL1, itemDataL1.name, "1", data.txt.hide, "0", data.txt.show);
				}
				epm_config_tab_editor_bt_show_hide_ajustar(iL1, itemDataL1);
				if (itemDataL1.hidden == 1) { 
					epm_global_html_find_hide_and_remove('#' + iL1.boxsubite);
					return; 
				}
				
				//ini loop productos
				$(itemDataL1.products).each(function(indexL2, itemDataL2) 
				{
					var iL2 = new ItemsLevel(idtab, "producto", itemDataL2.id);
					if ( $('#' + iL2.boxelemen).length == 0 ) 
					{
						CrearSubListItem(iL1);
						$('#' + iL1.boxsubite).append(epm_config_tab_html_L0(iL2.prefijoid, data.txt.ayuda_producto));
						epm_config_tab_editor_html_L1(itemDataL2, iL2, itemDataL2.short_name, "1", data.txt.hide, "0", data.txt.show);
					}
					
					epm_config_tab_editor_bt_show_hide_ajustar(iL2, itemDataL2);
					if (itemDataL2.hidden == 1) {
						epm_global_html_find_hide_and_remove('#' + iL2.boxsubite);
						return; 
					}
					
					//ini loop modelos
					$(itemDataL2.models).each(function(indexL3, itemDataL3) 
					{
						var iL3 = new ItemsLevel(idtab, "modelo", itemDataL3.id);
						if ( $('#' + iL3.boxelemen).length == 0 ) {
							CrearSubListItem(iL2);
							$('#' + iL2.boxsubite).append(epm_config_tab_html_L0(iL3.prefijoid, data.txt.ayuda_modelo));
							epm_config_tab_editor_html_L1(itemDataL3, iL3, itemDataL3.model, "1", data.txt.hide, "0", data.txt.show);
						}
						epm_config_tab_editor_bt_show_hide_ajustar(iL3, itemDataL3);
					});
					//end loop modelos
					epm_config_html_ordenar_lista(iL2.boxappend, true);
				});
				//end loop productos
				epm_config_html_ordenar_lista(iL1.boxappend, true);
			});
			//end loop marcas
			epm_config_html_ordenar_lista(boxappendL0, true);
			
			epm_global_update_jquery_msg_help();
		}
		return true;
	}
	else {
		fpbxToast(data.message,'ERROR!','error');
		return false;
	}
}

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
		if (itemData.hidden != temp_input) {
			$("#" + iL0.prefijoid + "_no").attr("disabled", false).prop( "checked", (itemData.hidden == "0") ? true : false);
			$("#" + iL0.prefijoid + "_yes").attr("disabled", false).prop( "checked", (itemData.hidden == "1") ? true : false);
		}
	}
}

function epm_config_tab_editor_bt_show_hide_change(obt, idtab, idtype, idbt)
{
	if ((idtab == "") || (idbt == "") || (idtype == "")) { return false; }
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val().toLowerCase();
	
	var iL0 = new ItemsLevel(idtab, idtype, idbt);
	
	$.ajax({
		type: 'POST',
		url: "ajax.php",
		data: {
			module: "endpointman",
			module_sec: "epm_config",
			module_tab: idtab,
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
			epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				if (obt_val == "1")
				{
					epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
				}
				else 
				{
					epm_config_LoadContenidoAjax(idtab, "list_all_brand");
				}
				fpbxToast(data.txt.save_changes_ok, '', 'success');
				return true;
			} 
			else {
				fpbxToast(data.message, data.txt.error, 'error');
				$("#" + obt_name + "_no").attr("disabled", true).prop("checked", false);
				$("#" + obt_name + "_yes").attr("disabled", true).prop("checked", false);
				epm_global_html_find_hide_and_remove('#' + iL0.boxsubite);
				return false;
			}
		},
	});	
}

function epm_config_tab_editor_html_L1(data, iL0, name, value_yes, txt_bt_yes, value_no, txt_bt_no) 
{
	$('#' + iL0.prefijoid + '_box' ).each( function() {
		$(this).find('.form-group').each( function() {
			$(this)
			.children('.col-md-3')
			.append(
				$('<label/>', {
					'class' : 'control-label',
					'for'   : iL0.prefijoid
				}).text(name),
				$('<i/>', {
					'class'  	: 'fa fa-question-circle fpbx-help-icon',
					'data-for'	: iL0.prefijoid
				})
			);
			
			$(this)
			.children('.col-md-9')
			.addClass("radioset")
			.append(
				$('<input/>', {
					'type'		: 'radio',
					'name'		: iL0.prefijoid,
					'id'		: iL0.prefijoid +'_yes',
					'value'		: value_yes
				})
				.change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL0.tab, iL0.prefijo, data.id); }),
				$('<label/>', {
					'for'  		: iL0.prefijoid +'_yes',
					'data-for'	: iL0.prefijoid
				})
				.text(txt_bt_yes)
				.append(
					$('<i/>', { 'class' : 'fa fa-toggle-off' })
				),
				$('<input/>', {
					'type'		: 'radio',
					'name'		: iL0.prefijoid,
					'id'		: iL0.prefijoid + '_no',
					'value'		: value_no
				}).change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL0.tab, iL0.prefijo, data.id); }),
				$('<label/>', {
					'for'  	: iL0.prefijoid + '_no',
					'data-for'	: iL0.prefijoid
				})
				.text(txt_bt_no)
				.append(
					$('<i/>', { 'class' : 'fa fa-toggle-on' })
				)
			);
		});
	});
}

function epm_config_tab_editor_html_L2(data, iL0, name, value_yes, txt_bt_yes, value_no, txt_bt_no)
{
	$('#' + iL0.prefijoid + '_box' )
	.each( function() {
		$(this)
		.find('.form-group > div.col-md-12')
		.addClass("radioset")
		.append(
			$('<i/>', {
				'class'  	: 'fa fa-question-circle fpbx-help-icon',
				'data-for'	: iL0.prefijoid
			}),
			$('<label/>', {
				'class' : 'control-label',
				'for'   : iL0.prefijoid
			}).text("Acction?"),
			
			$('<br />'),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL0.prefijoid,
				'id'		: iL0.prefijoid +'_yes',
				'value'		: value_yes
			})
			.change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL0.tab, iL0.prefijo, data.id); }),
			$('<label/>', {
				'for'  		: iL0.prefijoid +'_yes',
				'data-for'	: iL0.prefijoid
			})
			.text(txt_bt_yes)
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-off' })
			),
			$('<input/>', {
				'type'		: 'radio',
				'name'		: iL0.prefijoid,
				'id'		: iL0.prefijoid + '_no',
				'value'		: value_no
			}).change(function(){ epm_config_tab_editor_bt_show_hide_change(this, iL0.tab, iL0.prefijo, data.id); }),
			$('<label/>', {
				'for'  	: iL0.prefijoid + '_no',
				'data-for'	: iL0.prefijoid
			})
			.text(txt_bt_no)
			.append(
				$('<i/>', { 'class' : 'fa fa-toggle-on' })
			)
		);
		
	});
}
/**** END: TAB/EDITOR ****/