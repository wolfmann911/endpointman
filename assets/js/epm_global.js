var box = null;

function epm_global_html_find_hide_and_remove(name, tDelay = 1, bSlow = false)
{
	if ($(name).length > 0) {
		$(name).delay(tDelay).hide(((bSlow == true)  ? "slow" : ""), function () {
			$(this).remove();
		});
	}
}

function epm_global_html_find_show_hide(name, bShow, tDelay = 1, slow = false)
{
	if ($(name).length > 0) {
		if (bShow == true) 			{ $(name).delay(tDelay).show(((slow == true)  ? "slow" : "")); }
		else if (bShow == false)	{ $(name).delay(tDelay).hide(((slow == true)  ? "slow" : "")); }
		else if (bShow == "auto")	{
			if( $(name).is(":visible") ){
				$(name).delay(tDelay).hide(((slow == true)  ? "slow" : "")); 
			}else{
				$(name).delay(tDelay).show(((slow == true)  ? "slow" : ""));
			}
		}
	}
}

function epm_global_html_css_name(name, bStatus, classname)
{
	if ($(name).length > 0) {
		if (bStatus == true) 		{ $(name).addClass(classname); }
		else if (bStatus == false)	{ $(name).removeClass(classname); }
		else if (bStatus == "auto")	{
			if($(name).hasClass(classname)) { $(name).removeClass(classname); }
			else							{ $(name).addClass(classname); }
		}
	}
}

function epm_global_get_tab_actual()
{
	var sTab = "";
	$("#list-tabs-epm_config a").parents("ul").find("a").each(function( index ) 
	{
		var tabActualN = $(this).attr('aria-controls');
		if ($("#" + tabActualN).css('display') != "none") 
		{
			sTab = tabActualN;
		}
	});
	return sTab;
}

function epm_advanced_get_value_by_form(sform, snameopt, formtype = "name")
{
	var rdata = null;
	$('form['+formtype+'='+sform+']')
	.find("input, textarea, select")
	.each(function(index){  
		var input = $(this);
		if (snameopt == input.attr('name'))
		{
			rdata = input.val();
		}
		//alert('Type: ' + input.attr('type') + ' - Name: ' + input.attr('name') + ' - Value: ' + input.val());
	});
	return rdata;
}

function epm_config_dialog_action(actionname, urlStr, formname = null, titleStr = "Status", ClassDlg = "")
{
	if ((actionname == "") || (urlStr == "")) { return null; }
	
	var obox = $('<div id="moduledialogwrapper" ></div>')
	.dialog({
		title: titleStr,
		resizable: false,
		dialogClass: ClassDlg,
		modal: true,
		width: 410,
		maxHeight: 410,
		height: 'auto',
		maxHeight: 350,
		scroll: true,
		position: { my: "top-175", at: "center", of: window },
		open: function (e) {
			$('#moduledialogwrapper').html(_('Loading..' ) + '<i class="fa fa-spinner fa-spin fa-2x">');
			
			if (formname == null) {
				var oData = null;
			}
			else {
				var form = document.forms.namedItem(formname);
				var oData = new FormData(form);	
			}
			
			var xhr = new XMLHttpRequest(),
			timer = null;
			xhr.open('POST', urlStr, true);
			xhr.send(oData);
			timer = window.setInterval(function() {
				$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(this).height() });
				if (xhr.readyState == XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
					if (typeof end_module_actions == 'function') { 
						end_module_actions(actionname); 
					}
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
			if (typeof close_module_actions == 'function') { 
				close_module_actions(false, actionname); 
			}
			$(e.target).dialog("destroy").remove();
		}
	});
	
	return obox;
}

function close_module_actions(goback, acctionname = "") 
{
	var displayActual = $.getUrlVar('display');
	if (displayActual != "") {
		var func = 'close_module_actions_'+displayActual;
		if (typeof window[func] == 'function') { 
			window[func](goback, acctionname); 
		}
	}
	
	box.dialog("destroy").remove();
	if (goback) {
		location.reload();
	}		
}

function end_module_actions(acctionname = "") 
{
	var displayActual = $.getUrlVar('display');
	if (displayActual != "") {
		var func = 'end_module_actions_'+displayActual;
		if (typeof window[func] == 'function') { 
			window[func](acctionname); 
		}
	}
}











// **** INI: CODIGO DE FREEPBX ****
function epm_global_update_jquery_msg_help()
{
	if($(".fpbx-container").length>0){
		var loc=window.location.hash.replace("#","");
		if(loc!==""&&$(".fpbx-container li[data-name="+loc+"] a").length>0){
			$(".fpbx-container li[data-name="+loc+"] a").tab('show');
		}
		$(".fpbx-container i.fpbx-help-icon").on("mouseenter",function(){
			var id=$(this).data("for");
			var container=$(this).parents(".element-container");
			$(".fpbx-help-block").removeClass("active");
			$("#"+id+"-help").addClass("active");
			container.one("mouseleave",function(event){
				if(event.relatedTarget&&(event.relatedTarget.type=="submit"||event.relatedTarget.type=="button")){return;}
				var act=$("#"+id+"-help").data("activate");
				if(typeof act!=="undefined"&&act=="locked"){return;}
				$("#"+id+"-help").fadeOut("slow",function(){
					$(this).removeClass("active").css("display","");
				});
				$(this).off("mouseleave");
			});
		});
	}
}
// **** END: CODIGO DE FREEPBX ****