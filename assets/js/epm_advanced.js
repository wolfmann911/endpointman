var v_sTimerUpdateAjax = "";
var v_sTimerSelectTab = "";
var box;

$(document).ready(function() {
	$.getScript("modules/endpointman/assets/js/epm_global.js");	
});

$(window).load(function() {
	epm_advanced_tab_check_activa();
});

function epm_advanced_tab_check_activa(oldtab = "")
{
	clearTimeout(v_sTimerSelectTab);
	var actTab = epm_global_get_tab_actual();
	if (oldtab != actTab) 
	{
		$("#epm_advanced_" + actTab + "_all_list_box").children("div").hide("slow", function () {
			$(this).remove();
		});
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
	
	
	$("#epm_advanced_"+ idtab +"_load_init").each(function() {
		if ($(this).css('display') == "none") 
		{
			$(this).show("slow", function() {
				//var $tmp = epm_config_LoadContenidoAjax(idtab, "list_all_brand");
			});
		}
		else {
			//var $tmp = epm_config_LoadContenidoAjax(idtab, "list_all_brand");
		}
	});
	return true;
}