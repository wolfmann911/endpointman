<?php
/**
 * Endpoint Manager Object Module - Sec Templates
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

class Endpointman_Templates
{
	public function __construct($freepbx = null, $cfgmod = null) 
	{
		$this->freepbx = $freepbx;
		$this->db = $freepbx->Database;
		$this->config = $freepbx->Config;
		$this->configmod = $cfgmod;			
	}

	public function myShowPage(&$pagedata) {
		if(empty($pagedata))
		{
			$pagedata['manager'] = array(
				"name" => _("Current Templates"),
				"page" => 'views/epm_templates_manager.page.php'
			);
			$pagedata['editor'] = array(
					"name" => _("Template Editor"),
					"page" => 'views/epm_templates_editor.page.php'
			);
		}
	}

	public function ajaxRequest($req, &$setting) {
		$arrVal = array("model_clone", "list_current_template", "add_template", "del_template");
		if (in_array($req, $arrVal)) {
			$setting['authenticate'] = true;
			$setting['allowremote'] = false;
			return true;
		}
		return false;
	}
	
    public function ajaxHandler($module_tab = "", $command = "") 
	{
		$retarr = "";
		if ($module_tab == "manager")
		{
			switch ($command)
			{
				case "list_current_template":
					$retarr = $this->epm_templates_list_current_templates();
					break;
					
				case "model_clone":
					$retarr = $this->epm_templates_model_clone();
					break;
					
				case "add_template":
					$retarr = $this->epm_templates_add_template();
					break;
					
				case "del_template":
					$retarr = $this->epm_templates_del_template();
					break;
					
				default:
					$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
					break;
			}
		}
		else {
			$retarr = array("status" => false, "message" => _("Tab not found!") . " [" .$module_tab. "]");
		}
		return $retarr;
	}
	
	public function doConfigPageInit($module_tab = "", $command = "") {
		
	}
	
	public function getRightNav($request) {
		if(isset($request['subpage']) && $request['subpage'] == "editor") {
			return load_view(__DIR__."/views/epm_templates/rnav.php",array());
		} else {
			return '';
		}
	}
	
	
	/**** FUNCIONES SEC MODULO "epm_template\manager" ****/
	public function epm_templates_del_template() 
	{
		if (! isset($_REQUEST['idsel'])) {
			$retarr = array("status" => false, "message" => _("No send ID!"));
		}
		elseif (! is_numeric($_REQUEST['idsel'])) {
			$retarr = array("status" => false, "message" => _("ID is not number!"));
		}
		elseif ($_REQUEST['idsel'] <= 0) {
			$retarr = array("status" => false, "message" => _("ID send is negative!"));
		}
		else {
			$dget['idsel'] = $_REQUEST['idsel'];
			
			$sql = "DELETE FROM endpointman_template_list WHERE id = ". $dget['idsel'];
			sql($sql);
			$sql = "UPDATE endpointman_mac_list SET template_id = 0 WHERE template_id = ".$dget['idsel'];
			sql($sql);
			
			$retarr = array("status" => true, "message" => _("Delete Template OK!"));
			unset($dget);
		}
		return $retarr;
	}
	
	public function epm_templates_add_template ()
	{
		$arrVal['VAR_REQUEST'] = array("newnametemplate", "newproductselec", "newclonemodel");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				return array("status" => false, "message" => _("No send value!")." [".$valor."]");
			}
		}
		
		$arrVal['VAR_IS_NUM'] = array("newproductselec", "newclonemodel");
		foreach ($arrVal['VAR_IS_NUM'] as $valor) {
			if (! is_numeric($_REQUEST[$valor])) {
				return array("status" => false, "message" => _("Value send is not number!")." [".$valor."]");
			}
		}
		
		if (empty($_REQUEST['newnametemplate'])) {
			$retarr = array("status" => false, "message" => _("Name is null!"));
		}
		elseif ($_REQUEST['newproductselec'] <= 0) {
			$retarr = array("status" => false, "message" => _("Product send is negative!"));
		}
		elseif ($_REQUEST['newclonemodel'] <= 0) {
			$retarr = array("status" => false, "message" => _("Clone Model send is negative!"));
		}
		else {
			$dget['newnametemplate'] = $_REQUEST['newnametemplate'];
			$dget['newproductselec'] = $_REQUEST['newproductselec'];
			$dget['newclonemodel'] = $_REQUEST['newclonemodel'];

			$db = $this->db;
			$sql = "INSERT INTO endpointman_template_list (product_id, name, model_id) VALUES (?,?,?)";
			$q = $db->prepare($sql);
			$ob = $q->execute(array($dget['newproductselec'], addslashes($dget['newnametemplate']), $dget['newclonemodel']));
			$newid = $db->lastInsertId();
			$this->edit_template_display($newid,0);
			
			$retarr = array("status" => true, "message" => _("Add New Template OK!"), "newid" => $newid);
			unset($dget);
		}
		return $retarr;
	}
	
	public function epm_templates_model_clone () 
	{
		if (! isset($_REQUEST['id'])) {
			$retarr = array("status" => false, "message" => _("No send ID!"));
		}
		elseif (! is_numeric($_REQUEST['id'])) {
			$retarr = array("status" => false, "message" => _("ID send is not number!"));
		}
		elseif ($_REQUEST['id'] <= 0) {
			$retarr = array("status" => false, "message" => _("ID send is number not valid!"));
		}
		else
		{
			$dget['id'] = $_REQUEST['id'];
			
			$i=0;
			$out = array();
			$sql = "SELECT endpointman_model_list.id, endpointman_model_list.model as model FROM endpointman_model_list, endpointman_product_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.enabled = 1 AND endpointman_model_list.hidden = 0 AND product_id = '". $dget['id']."'";
			$result = sql($sql,'getAll', DB_FETCHMODE_ASSOC);
			foreach($result as $row) {
				$out[$i]['optionValue'] = $row['id'];
				$out[$i]['optionDisplay'] = $row['model'];
				$i++;
			}
			$retarr = array("status" => true, "message" => _("Generate list Ok!"), "listopt" => $out);
			
			unset($dget);
		}
		return $retarr;
	}
	
	public function epm_templates_list_current_templates ()
	{
	
		$sql = 'SELECT endpointman_template_list.*, endpointman_product_list.short_name as model_class, endpointman_model_list.model as model_clone, endpointman_model_list.enabled FROM endpointman_template_list, endpointman_model_list, endpointman_product_list WHERE endpointman_model_list.hidden = 0 AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.product_id = endpointman_product_list.id';
		$template_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		$i = 0;
		$row_out = array();
		foreach($template_list as $row) {
			$row_out[$i] = $row;
			$row_out[$i]['custom'] = 0;
			if(!$row['enabled']) {
				$row_out[$i]['model_clone'] = $row_out[$i]['model_clone'];
			}
			$i++;
		}
		
		$sql = 'SELECT endpointman_mac_list.mac, endpointman_mac_list.id, endpointman_mac_list.model, endpointman_model_list.model as model_clone, endpointman_product_list.short_name as model_class FROM endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE  endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.global_custom_cfg_data IS NOT NULL AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_mac_list.template_id = 0';
		$template_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		foreach($template_list as $row) {
			$sql = 'SELECT  description , line FROM  endpointman_line_list WHERE  mac_id ='. $row['id'].' ORDER BY line ASC';
			$line_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
			$description = "";
			$c = 0;
			foreach($line_list as $line_row) {
				if($c > 0) {
					$description .= ", ";
				}
				$description .= $line_row['description'];
				$c++;
			}
			$row_out[$i] = $row;
			$row_out[$i]['custom'] = 1;
			$row_out[$i]['name'] = $row['mac'];
			$row_out[$i]['description'] = $description;
			$i++;
		}
		
	/*
		//$sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
		$sql = 'SELECT T1.id, T1.oui, T2.name, T1.custom FROM endpointman_oui_list as T1 , endpointman_brand_list as T2 WHERE T1.brand = T2.id ORDER BY T1.oui ASC';
		$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		$ret = array();
		foreach ($data as $item) {
			$ret[] = array('id' => $item['id'], 'oui' => $item['oui'], 'brand' => $item['name'], 'custom' => $item['custom']);
		}
		*/
		return $row_out;
	}
	
}