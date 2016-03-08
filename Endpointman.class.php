<?php
/**
 * Endpoint Manager Object Module
 *
 * @author Andrew Nagy
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

function format_txt($texto = "", $css_class = "", $remplace_txt = array())
{
	if (count($remplace_txt) > 0) 
	{
		foreach ($remplace_txt as $clave => $valor) {
			$texto = str_replace($clave, $valor, $texto);
		}
	}
	return '<p ' . ($css_class != '' ? 'class="' . $css_class . '"' : '') . '>'.$texto.'</p>';	
}

function generate_xml_from_array ($array, $node_name, &$tab = -1) 
{
	$tab++;
	$xml ="";
	if (is_array($array) || is_object($array)) {
		foreach ($array as $key=>$value) {
			if (is_numeric($key)) {
				$key = $node_name;
			}
			
			$xml .= str_repeat("	", $tab). '<' . $key . '>' . "\n";
			$xml .= generate_xml_from_array($value, $node_name, $tab);
			$xml .= str_repeat("	", $tab). '</' . $key . '>' . "\n";
			
		}
	} else {
		$xml = str_repeat("	", $tab) . htmlspecialchars($array, ENT_QUOTES) . "\n";
	}
	$tab--;
	return $xml;
}


class Endpointman implements \BMO {
	
	public $db; //Database from FreePBX
	public $eda; //endpoint data abstraction layer
	public $tpl; //Template System Object (RAIN TPL)
	//public $system;
	
    public $error; //error construct
    public $message; //message construct
	
	public $UPDATE_PATH;
    public $MODULES_PATH;
	public $LOCAL_PATH;
	public $PHONE_MODULES_PATH;
	public $PROVISIONER_BASE;
	
	
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		require_once('lib/json.class.php');
		require_once('lib/Config.class.php');
		require_once('lib/epm_system.class.php');
		require_once('lib/datetimezone.class.php');
		require_once('lib/epm_data_abstraction.class.php');
		//require_once("lib/RainTPL.class.php");
		
		$this->freepbx = $freepbx;
		$this->db = $freepbx->Database;
		$this->config = $freepbx->Config;
		$this->configmod = new Endpointman\Config();
		$this->system = new epm_system();
		$this->eda = new epm_data_abstraction($this->config, $this->configmod);
		
		
		$this->configmod->set('disable_epm', FALSE);
		$this->eda->global_cfg = $this->configmod->getall();
		
        //Generate empty array
        $this->error = array();
        $this->message = array();
		
		
		$this->configmod->set('tz', $this->config->get('PHPTIMEZONE'));
		date_default_timezone_set($this->configmod->get('tz'));
		
		$this->UPDATE_PATH = $this->configmod->get('update_server');
        $this->MODULES_PATH = $this->config->get('AMPWEBROOT') . '/admin/modules/';
        
define("UPDATE_PATH", $this->UPDATE_PATH);
define("MODULES_PATH", $this->MODULES_PATH);
        
		
        //Determine if local path is correct!
        if (file_exists($this->MODULES_PATH . "endpointman/")) {
            $this->LOCAL_PATH = $this->MODULES_PATH . "endpointman/";
define("LOCAL_PATH", $this->LOCAL_PATH);
        } else {
            die("Can't Load Local Endpoint Manager Directory!");
        }
		
        //Define the location of phone modules, keeping it outside of the module directory so that when the user updates endpointmanager they don't lose all of their phones
        if (file_exists($this->MODULES_PATH . "_ep_phone_modules/")) {
            $this->PHONE_MODULES_PATH = $this->MODULES_PATH . "_ep_phone_modules/";
        } else {
            $this->PHONE_MODULES_PATH = $this->MODULES_PATH . "_ep_phone_modules/";
            if (!file_exists($this->PHONE_MODULES_PATH)) {
                mkdir($this->PHONE_MODULES_PATH, 0775);
            }
            if (file_exists($this->PHONE_MODULES_PATH . "setup.php")) {
                unlink($this->PHONE_MODULES_PATH . "setup.php");
            }
            if (!file_exists($this->MODULES_PATH . "_ep_phone_modules/")) {
                die('Endpoint Manager can not create the modules folder!');
            }
        }
define("PHONE_MODULES_PATH", $this->PHONE_MODULES_PATH);
		
        //Define error reporting
        if (($this->configmod->get('debug')) AND (!isset($_REQUEST['quietmode']))) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            ini_set('display_errors', 0);
        }
		
        //Check if config location is writable and/or exists!
        if ($this->configmod->isExiste('config_location')) {
			$config_location = $this->configmod->get('config_location');
            if (is_dir($config_location)) {
                if (!is_writeable($config_location)) {
                    $user = exec('whoami');
                    $group = exec("groups");
                    $this->error['config_location'] = _("Configuration Directory is not writable!") . "<br />" .
                            _("Please change the location:") . "<a href='config.php?display=epm_advanced'>" . _("Here") . "</a><br />" .
                            _("Or run this command on SSH:") . "<br />" .
							"'chown -hR root: " . $group . " " . $config_location . "'<br />" .
							"'chmod g+w " . $config_location . "'";
					$this->configmod->set('disable_epm', TRUE);
                }
            } else {
                $this->error['config_location'] = _("Configuration Directory is not a directory or does not exist! Please change the location here:") . "<a href='config.php?display=epm_advanced'>" . _("Here") . "</a>";
				$this->configmod->set('disable_epm', TRUE);
            }
        }
        
        //$this->tpl = new RainTPL(LOCAL_PATH . '_old/templates/freepbx', LOCAL_PATH . '_old/templates/freepbx/compiled', '/admin/assets/endpointman/images');
		//$this->tpl = new RainTPL('/admin/assets/endpointman/images');
	}
	
	public function chownFreepbx() {
		$webroot = $this->config->get('AMPWEBROOT');
		$modulesdir = $webroot . '/admin/modules/';
		$files = array();
		$files[] = array('type' => 'dir',
						'path' => $modulesdir . '/_ep_phone_modules/',
						'perms' => 0755);
		$files[] = array('type' => 'file',
						'path' => $modulesdir . '/_ep_phone_modules/setup.php',
						'perms' => 0755);
		$files[] = array('type' => 'dir',
						'path' => '/tftpboot',
						'perms' => 0755);
		return $files;
	}
	
	public function ajaxRequest($req, &$setting) {
		$module_sec = isset($_REQUEST['module_sec'])? trim($_REQUEST['module_sec']) : '';
		if ($module_sec == "") { return false; }
		
		
		$arrVal['epm_devices']= array();
		$arrVal['epm_templates']= array("model_clone","list_current_template","add_template","del_template");
		$arrVal['epm_config']= array("saveconfig","list_all_brand");
		$arrVal['epm_advanced']= array("oui", "oui_add", "oui_del", "poce_select", "poce_select_file", "poce_save_file", "poce_save_as_file", "poce_sendid", "poce_delete_config_custom", "list_files_brands_export", "saveconfig");
		if (!isset($arrVal[$module_sec])) { return false; }
		
		if (in_array($req, $arrVal[$module_sec])) {
			$setting['authenticate'] = true;
			$setting['allowremote'] = false;
			return true;
		}
		
		//AVISO!!!!!!!!!!!!!!!!!!!!!!!!!!
		//PERMITE TODO!!!!!!!!!!!!!!!!!!!
		//$setting['authenticate'] = true;
		//$setting['allowremote'] = true;
		//return true;
		
        return false;
    }
	
    public function ajaxHandler() {
		$module_sec = isset($_REQUEST['module_sec'])? trim($_REQUEST['module_sec']) : '';
		$module_tab = isset($_REQUEST['module_tab'])? trim($_REQUEST['module_tab']) : '';
		$command = isset($_REQUEST['command'])? trim($_REQUEST['command']) : '';
		
		if ($command == "") {
			$retarr = array("status" => false, "message" => _("No command was sent!"));
			return $retarr;
		}
		
		$arrVal['mod_sec'] = array("epm_devices","epm_templates", "epm_config", "epm_advanced");
		if (! in_array($module_sec, $arrVal['mod_sec'])) {
			$retarr = array("status" => false, "message" => _("Invalid section module!"));
			return $retarr;
		}
		
		$txt = "";
		switch ($module_sec) 
		{
			case "epm_devices": 	break;
			case "epm_templates":
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
					return $retarr;
				}
				break;
				
			case "epm_config":
				$txt['manager'] = array(
					'ayuda_model' => _("If we can activate the model set terminals of the models.<br /> If this model is disabled will not appear in the list of models that can be configured for PBX."),
					'ayuda_producto' => _('The button "Install Firmware" installs the necessary files to the server for the terminal alone are updated via TFTP or HTTP.<br /> The button "Remove frimware" delete files server products.<br /> The button "Update frimware" appears if a newer frimware detected on the server and asks if you want to update.<br /> The "Update" button appears when a new version of this model pack is detected.'),
					'ayuda_marca' => _('The "Install" button installs the configuration package brand models we selected.<br /> The "Uninstall" button removes the package configuration models of the brand selected.<br /> The "Update" button appears if a new version of the package that is already installed to upgrade to the latest version is detected.'),
					'new_pack_mod' => _("New Package Modified"),
					'pack_last_mod' => _("Package Last Modified"),
					'check_update' => _("Check for Update "),
					'check_online' => _("Check Online "),
					'install' => _("Install"),
					'uninstall' => _("Uninstall"),
					'update' => _("Update"),
					'fw_install' => _('Install Firmware'), 
					'fw_uninstall' =>  _('Remove Firmware'),
					'fw_update' => _('Update Firmware'),
					'enable' => _('Enable'),
					'disable' => _('Disable'),
					'ready' => _("Ready!"),
					'error' => _("Error!"),
					'title_update' => _("Update!"),
					'save_changes' => _("Saving Changes..."),
					'save_changes_ok' => _("Saving Changes... Ok!"),
					'err_upload_content' => _("Upload Content!"),
					'check' => _("Check for Updates..."),
					'check_ok' => _("Check for Updates... Ok!"),
					'update_content' => _("Update Content..."),
					'opt_invalid' => _("Invalid Option!")
				);
				$txt['editor'] = array(
					'ayuda_marca' => _("If you select Hide this brand will disappear and all products and models on the list of Install/Uninstall."),
					'ayuda_producto' => _("If you select Hide disappear all models of the product selected from the Install/Uninstall list."),
					'ayuda_modelo' => _("If you select Hide disappear this model the Install/Uninstall list."),
					'show' => _("Show"),
					'hide' => _("Hide"),
					'ready'=> _("Ready!"),
					'error' => _("Error!"),
					'save_changes' => _("Saving Changes..."),
					'save_changes_ok' => _("Saving Changes... Ok!"),
					'ready' => _("Ready!"),
					'err_upload_content' => _("Upload Content!"),
					'opt_invalid' => _("Invalid Option!")
				);
				
				if ($module_tab == "manager") 
				{
					switch ($command)
					{
						case "saveconfig": 
							$retarr = $this->epm_config_manager_saveconfig();
							break;
						
						case "list_all_brand": 
							$retarr = array("status" => true, "message" => "OK", "datlist" => $this->epm_config_manager_hardware_get_list_all(false));
							break;
						
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					$retarr['txt'] = $txt['manager'];
				}
				elseif ($module_tab == "editor") 
				{
					switch ($command) 
					{
						case "saveconfig": 
							$retarr = $this->epm_config_editor_saveconfig();
							break;
							
						case "list_all_brand":
							$retarr = array("status" => true, "message" => "OK", "datlist" => $this->epm_config_editor_hardware_get_list_all());
							break;
							
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					$retarr['txt'] = $txt['editor'];
				}
				else {
					$retarr = array("status" => false, "message" => _("Tab is not valid!") . " [" .$module_tab. "]");
				}
				return $retarr;
				break;
				
			case "epm_advanced":
				$txt['settings'] = array(
					'error' => _("Error!"),
					'save_changes' => _("Saving Changes..."),
					'save_changes_ok' => _("Saving Changes... Ok!"),
					'opt_invalid' => _("Invalid Option!")
				);
			
				if ($module_tab == "settings")
				{
					switch ($command)
					{
						case "saveconfig":
							$retarr = $this->epm_advanced_settings_saveconfig();
							break;
							
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					$retarr['txt'] = $txt['settings'];
				}
				elseif ($module_tab == "oui_manager") {
					switch ($command)
					{
						case "oui":
							//$sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
							$sql = 'SELECT T1.id, T1.oui, T2.name, T1.custom FROM endpointman_oui_list as T1 , endpointman_brand_list as T2 WHERE T1.brand = T2.id ORDER BY T1.oui ASC';
							$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
							$ret = array();
							foreach ($data as $item) {
								$ret[] = array('id' => $item['id'], 'oui' => $item['oui'], 'brand' => $item['name'], 'custom' => $item['custom']);
							}
							return $ret;
							break;
						
						case "oui_add":
							$retarr = $this->epm_advanced_oui_add();
							break;
							
						case "oui_del":
							$retarr = $this->epm_advanced_oui_remove();
							break;
							
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					//$retarr['txt'] = $txt['settings'];
				}
				elseif ($module_tab == "iedl") {
					switch ($command)
					{
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					//$retarr['txt'] = $txt['settings'];
				}
				elseif ($module_tab == "poce") {
					switch ($command)
					{
						case "poce_select":
							$retarr = $this->epm_advanced_poce_select();
							break;
							
						case "poce_select_file":
							$retarr = $this->epm_advanced_poce_select_file();
							break;
							
						case "poce_save_file":
						case "poce_save_as_file":
							$retarr = $this->epm_advanced_poce_save_file();
							break;
						
						case "poce_sendid":
							$retarr = $this->epm_advanced_poce_sendid();
							break;
							
						case "poce_delete_config_custom":
							$retarr = $this->epm_advanced_poce_delete_config_custom();
							break;
								
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
					//$retarr['txt'] = $txt['settings'];
				}
				elseif ($module_tab == "manual_upload") {
					switch ($command)
					{
						case "list_files_brands_export":
							$retarr = $this->epm_advanced_manual_upload_list_files_brans_export();
							break;
							
						default:
							$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
							break;
					}
				}
				else {
					$retarr = array("status" => false, "message" => _("Tab is not valid!") . " [" .$module_tab. "]");
				}
				return $retarr;
				break;
		}
		return false;
    }
	
	public static function myDialplanHooks() {
		return true;
	}
	
	public function doConfigPageInit($page) {
		//TODO: Pendiente revisar y eliminar moule_tab.
		$module_tab = isset($_REQUEST['module_tab'])? trim($_REQUEST['module_tab']) : '';
		if ($module_tab == "") {
			$module_tab = isset($_REQUEST['subpage'])? trim($_REQUEST['subpage']) : '';
		}
		$command = isset($_REQUEST['command'])? trim($_REQUEST['command']) : '';
		
		
		$arrVal['mod_sec'] = array("epm_devices","epm_templates", "epm_config", "epm_advanced");
		if (! in_array($page, $arrVal['mod_sec'])) {
			die(_("Invalid section module!"));
		}
		
		switch ($page) 
		{
			case "epm_devices": 	break;
			case "epm_templates": 	break;
			case "epm_config":
				switch ($module_tab) 
				{
					case "manager":
						switch ($command) {
							case "check_for_updates":
								$this->epm_config_manager_check_for_updates();
								echo "<br /><hr><br />";
								exit;
								break;
								
							case "manual_install":
								$this->epm_config_manual_install();
								echo "<br /><hr><br />";
								exit;
								break;
								
							case "firmware":
								$this->epm_config_manager_firmware();
								echo "<br /><hr><br />";
								exit;
								break;
								
							case "brand":
								$this->epm_config_manager_brand();
								echo "<br /><hr><br />";
								exit;
								break;
						}
						break;
					
					case "editor":
						break;
				}
				break;
				
			case "epm_advanced":
				switch ($module_tab)
				{
					case "oui_manager":
						break;
							
					case "iedl":
						switch ($command) {
							case "export":
								$this->epm_advanced_iedl_export();
								break;
							case "import":
								$this->epm_advanced_iedl_import();
								echo "<br /><hr><br />";
								exit;
								break;
						}
						break;
						
					case "manual_upload":
						switch ($command) {
							case "export_brands_availables":
								$this->epm_advanced_manual_upload_export_brans_available();
								echo "<br /><hr><br />";
								exit;
								break;
								
							case "export_brands_availables_file":
								$this->epm_advanced_manual_upload_export_brans_available_file();
								exit;
								break;
								
							case "upload_brand":
								$this->epm_advanced_manual_upload_brand();
								echo "<br /><hr><br />";
								exit;
								break;
								
							case "upload_provisioner":
								$this->epm_advanced_manual_upload_provisioner();
								echo "<br /><hr><br />";
								exit;
								break;
						}
						break;
							break;
				}
				break;
		}
	}
	
	public function doGeneralPost() {
		if (!isset($_REQUEST['Submit'])) 	{ return; }
		if (!isset($_REQUEST['display'])) 	{ return; }
		
		needreload();
	}
	
	public function myShowPage() {
		if (! isset($_REQUEST['display']))
			return $this->pagedata;
		
		switch ($_REQUEST['display']) 
		{
			case "epm_devices":
				if(empty($this->pagedata))
				{
					$this->pagedata['main'] = array(
							"name" => _("Devices"),
							"page" => 'views/epm_devices_main.page.php'
					);
				}
				break;
				
			case "epm_templates":
				if(empty($this->pagedata))
				{
					$this->pagedata['manager'] = array(
						"name" => _("Current Templates"),
						"page" => 'views/epm_templates_manager.page.php'
					);
					$this->pagedata['editor'] = array(
							"name" => _("Template Editor"),
							"page" => 'views/epm_templates_editor.page.php'
					);
				}
				break;
				
			case "epm_config":
				if(empty($this->pagedata)) 
				{
					$this->pagedata['manager'] = array(
						"name" => _("Install/Unistall"),
						"page" => 'views/epm_config_manager.page.php'
					);
					$this->pagedata['editor'] = array(
						"name" => _("Show/Hide"),
						"page" => 'views/epm_config_editor.page.php'
					);
				}
				break;
				
			case "epm_advanced": 
				if(empty($this->pagedata)) 
				{
					$this->pagedata['settings'] = array(
						"name" => _("Settings"),
						"page" => 'views/epm_advanced_settings.page.php'
					);
					$this->pagedata['oui_manager'] = array(
						"name" => _("OUI Manager"),
						"page" => 'views/epm_advanced_oui_manager.page.php'
					);
					$this->pagedata['poce'] = array(
						"name" => _("Product Configuration Editor"),
						"page" => 'views/epm_advanced_poce.page.php'
					);
					$this->pagedata['iedl'] = array(
						"name" => _("Import/Export My Devices List"),
						"page" => 'views/epm_advanced_iedl.page.php'
					);
					$this->pagedata['manual_upload'] = array(
						"name" => _("Package Import/Export"),
						"page" => 'views/epm_advanced_manual_upload.page.php'
					);
				}
				break;
		}
		
		if(! empty($this->pagedata)) {
			foreach($this->pagedata as &$page) {
				ob_start();
				include($page['page']);
				$page['content'] = ob_get_contents();
				ob_end_clean();
			}
			return $this->pagedata;
		}
	}
	
	public function getActiveModules() {
	}
	
	//http://wiki.freepbx.org/display/FOP/Adding+Floating+Right+Nav+to+Your+Module
	public function getRightNav($request) {
		if(isset($request['subpage']) && $request['subpage'] == "editor") {
			return load_view(__DIR__."/views/epm_templates/rnav.php",array());
		} else {
			return '';
		}
		
	}
	
	//http://wiki.freepbx.org/pages/viewpage.action?pageId=29753755
	public function getActionBar($request) {
	}
	
	public function install() {
		
	}
	
    public function uninstall() {
    	out(_("Removing Phone Modules Directory"));
    	$this->system->rmrf($this->PHONE_MODULES_PATH);
    	exec("rm -R ". $this->PHONE_MODULES_PATH);
    	
    	out(_('Removing symlink to web provisioner'));
    	$provisioning_path = $this->config->get('AMPWEBROOT')."/provisioning";
    	if(is_link($provisioning_path)) { unlink($provisioning_path); }
    	
    	if(!is_link($this->config->get('AMPWEBROOT').'/admin/assets/endpointman')) {
    		$this->system->rmrf($this->config->get('AMPWEBROOT').'/admin/assets/endpointman');
    	}
    	
    	out(_("Dropping all relevant tables"));
    	$sql = "DROP TABLE `endpointman_brand_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_global_vars`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_mac_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_line_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_model_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_oui_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_product_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_template_list`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_time_zones`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	$sql = "DROP TABLE `endpointman_custom_configs`";
    	$sth = $this->db->prepare($sql);
    	$sth->execute();
    	return true;
	}
	
    public function backup() {
	}
	
    public function restore($backup) {
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
     * Get info models by product id selected.
     * @param int $id_product product ID
	 * @param bool $show_all True return all, False return hidden = 0
     * @return array
     */
	public function epm_config_hardware_get_list_models($id_product=NULL, $show_all = true) 
	{
		if(! is_numeric($id_product)) { throw new \Exception( _("ID Producto not is number")." (".$id_product.")"); }
		if($show_all == true) 	{ $sql = 'SELECT * FROM endpointman_model_list WHERE product_id = '.$id_product; }
		else 					{ $sql = 'SELECT * FROM endpointman_model_list WHERE hidden = 0 AND product_id = '.$id_product; }
		$result = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
     * Get info product by brand id selected.
     * @param int $id_brand brand ID
	 * @param bool $show_all True return all, FAlse return hidde = 0
     * @return array
     */
	public function epm_config_hardware_get_list_product($id_brand=NULL, $show_all = true) 
	{
		if(! is_numeric($id_brand)) { throw new \Exception(_("ID Brand not is numbre")." (".$id_brand.")"); }
		if ($show_all == true) 	{ $sql = 'SELECT * FROM endpointman_product_list WHERE brand = '.$id_brand.' ORDER BY long_name ASC'; }
		else 					{ $sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND brand = '.$id_brand.' ORDER BY long_name ASC'; }
		$result = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	/**
     * Get info all brands.
	 * @param bool $show_all True return all, False return hidde = 0
     * @return array
     */
	public function epm_config_hardware_get_list_brand($show_all = true) {
		if ($show_all == true) 	{ $sql = "SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY id ASC "; }
		else 					{ $sql = "SELECT * from endpointman_brand_list WHERE id > 0 AND hidden = 0 ORDER BY id ASC "; }
		$result = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		return $result;
	}
	
	
	
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	private function epm_config_manual_install($install_type = "", $package ="")
	{
		if ($install_type == "") {
			throw new \Exception("Not send install_type!");
		}
	
		switch($install_type) {
			case "export_brand":

				break;
	
			case "upload_master_xml":
				if (file_exists($this->PHONE_MODULES_PATH."temp/master.xml")) {
					$handle = fopen($this->PHONE_MODULES_PATH."temp/master.xml", "rb");
					$contents = stream_get_contents($handle);
					fclose($handle);
					@$a = simplexml_load_string($contents);
					if($a===FALSE) {
						echo "Not a valid xml file";
						break;
					} else {
						rename($this->PHONE_MODULES_PATH."temp/master.xml", $this->PHONE_MODULES_PATH."master.xml");
						echo "Move Successful<br />";
						$this->update_check();
						echo "Updating Brands<br />";
					}
				} else {
				}
				break;
	
			case "upload_provisioner":
				
				break;
	
			case "upload_brand":
			
				break;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/******************************************************
	 **** FUNCIONES SEC MODULO "epm_template\manager". ****
	 *****************************************************/
	
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
		if (! isset($_REQUEST['newnametemplate'])) {
			$retarr = array("status" => false, "message" => _("No send Name!"));
		}
		elseif (empty($_REQUEST['newnametemplate'])) {
			$retarr = array("status" => false, "message" => _("Name is null!"));
		}
		elseif (! isset($_REQUEST['newproductselec'])) {
			$retarr = array("status" => false, "message" => _("No send Product!"));
		}
		elseif (! is_numeric($_REQUEST['newproductselec'])) {
			$retarr = array("status" => false, "message" => _("Product is not number!"));
		}
		elseif ($_REQUEST['newproductselec'] <= 0) {
			$retarr = array("status" => false, "message" => _("Product send is negative!"));
		}
		elseif (! isset($_REQUEST['newclonemodel'])) {
			$retarr = array("status" => false, "message" => _("No send Clone Model!"));
		}
		elseif (! is_numeric($_REQUEST['newclonemodel'])) {
			$retarr = array("status" => false, "message" => _("Clone Model is not number!"));
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
	
	/********************
	 * END SEC FUNCTIONS *
	 ********************/
	
	
	
	
	

	/***************************************************
	 **** FUNCIONES SEC MODULO "epm_advanced\poce". ****
	 ***************************************************/
	
	public function epm_advanced_poce_select() 
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! is_numeric($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("Product Select send is not number!"));
		}
		elseif ($_REQUEST['product_select'] < 0) {
			$retarr = array("status" => false, "message" => _("Product Select send is number not valid!"));
		}
		else
		{
			$dget['product_select'] = $_REQUEST['product_select'];
			
			$sql = 'SELECT * FROM `endpointman_product_list` WHERE `hidden` = 0 AND `id` = '.$dget['product_select'];
			$product_select_info = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
			
			$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id ='" . $dget['product_select'] . "'";
			$row =  sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
			$config_files = explode(",", $row['config_files']);
			$i = 0;
			if (count($config_files)) {
				foreach ($config_files as $config_files_data) {
					//$file_list[$i]['value'] = $i;
					$file_list[$i]['value'] = $dget['product_select'];
					$file_list[$i]['text'] = $config_files_data;
					$i++;
				}
			} else { $file_list = NULL; }
			
			$sql = "SELECT * FROM endpointman_custom_configs WHERE product_id = '" . $dget['product_select'] . "'";
			$data = sql($sql,'getAll', DB_FETCHMODE_ASSOC);
			$i = 0;
			if (count($data)) {
				//$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
				foreach ($data as $row2) {
					$sql_file_list[$i]['value'] = $row2['id'];
					$sql_file_list[$i]['text'] = $row2['name'];
					$sql_file_list[$i]['ref'] = $row2['original_name'];
					$i++;
				}
			} else { $sql_file_list = NULL; }
			
			
			require_once($this->PHONE_MODULES_PATH . 'setup.php');
			$class = "endpoint_" . $row['directory'] . "_" . $row['cfg_dir'] . '_phone';
			$base_class = "endpoint_" . $row['directory'] . '_base';
			$master_class = "endpoint_base";
			
			/*********************************************************************************
			 *** Quick Fix for FreePBX Distro
			 *** I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
			 **********************************************************************************/
			if (!class_exists($master_class)) {
				ProvisionerConfig::endpointsAutoload($master_class);
			}
			if (!class_exists($base_class)) {
				ProvisionerConfig::endpointsAutoload($base_class);
			}
			if (!class_exists($class)) {
				ProvisionerConfig::endpointsAutoload($class);
			}
			//end quick fix
			$phone_config = new $class();
		
			//TODO: remove
			$template_file_list[0]['value'] = "template_data_custom.xml";
			$template_file_list[0]['text'] = "template_data_custom.xml";
		
			$sql = "SELECT id, model FROM endpointman_model_list WHERE product_id = '" . $dget['product_select'] . "' AND enabled = 1 AND hidden = 0";
			$data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
			$i = 1;
			foreach ($data as $list) {
				//$template_file_list[$i]['value'] = "template_data_" . $list['model'] . "_custom.xml";
				$template_file_list[$i]['value'] = $list['id'];
				$template_file_list[$i]['text'] = "template_data_" . $list['model'] . "_custom.xml";
			}
			
			$retarr = array("status" => true, 
							"message" => "OK", 
							"product_select" => $dget['product_select'], 
							"product_select_info" => $product_select_info, 
							"file_list" => $file_list, 
							"template_file_list" => $template_file_list, 
							"sql_file_list" => $sql_file_list);
			unset($dget);
		}
		return $retarr;
	}
	
	public function epm_advanced_poce_select_file()
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! is_numeric($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("Product Select send is not number!"));
		}
		elseif ($_REQUEST['product_select'] < 0) {
			$retarr = array("status" => false, "message" => _("Product Select send is number not valid!"));
		}
		elseif (! isset($_REQUEST['file_id'])) {
			$retarr = array("status" => false, "message" => _("No send File ID!"));
		}
		elseif (! isset($_REQUEST['file_name'])) {
			$retarr = array("status" => false, "message" => _("No send File Name!"));
		}
		elseif (! isset($_REQUEST['type_file'])) {
			$retarr = array("status" => false, "message" => _("No send Type File!"));
		}
		else
		{
			$dget['product_select'] = $_REQUEST['product_select'];
			$dget['file_name'] = $_REQUEST['file_name'];
			$dget['file_id'] = $_REQUEST['file_id'];
			$dget['type_file'] = $_REQUEST['type_file'];
			
			
			if ($dget['type_file'] == "sql") {
				$sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $dget['file_id'];
				$row = sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
				
				$type = $dget['type_file'];
				$sendidt = $row['id'];
				$product_select = $row['product_id'];
				$save_as_name_value = $row['name'];
				$original_name = $row['original_name'];
				$filename =  $row['name'];
				$location = "SQL: ". $row['name'];
				$config_data = $this->display_htmlspecialchars($row['data']);
				
			}
			elseif ($dget['type_file'] == "file") {
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
				
				$config_files = explode(",", $row['config_files']);
				//TODO: Añadir validacion para ver si $dget['file_name'] esta en el array $config_files
				
				$filename = $dget['file_name'];
				$pathfile = $this->PHONE_MODULES_PATH . 'endpoint/' . $row['directory'] . "/" . $row['cfg_dir'] . "/" . $filename; 
				
				
				if (is_readable($pathfile)) {
					if(filesize($pathfile)>0) {
						$handle = fopen($pathfile, "rb");
						$contents = fread($handle, filesize($pathfile));
						fclose($handle);
						$contents = $this->display_htmlspecialchars($contents);
					}
					else {
						$contents = "";
					}
					
					$type = $dget['type_file'];
					$sendidt = $dget['file_id'];
					$product_select = $dget['product_select'];
					$save_as_name_value = $filename;
					$original_name = $filename;
					$filename =  $filename;
					$location = $pathfile;
					$config_data = $contents;
				}
				else {
					$retarr = array("status" => false, "message" => _("File not readable, check the permission! ").$filename);
				}
			}
			elseif ($dget['type_file'] == "tfile") 
			{
				if ($dget['file_id'] == "template_data_custom.xml")
				{
					$sendidt = "";
					$original_name = $dget['file_name'];
					$config_data = "";
				}
				else {
					
					$sql = "SELECT * FROM endpointman_model_list WHERE id = '" . $dget['file_id'] . "'";
					$data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
			
					$sendidt = $data['id'];
					$original_name = $dget['file_name'];
					$config_data = unserialize($data['template_data']);
					$config_data = generate_xml_from_array ($config_data, 'node');
				}
				
				$type = $dget['type_file'];
				$product_select = $dget['product_select'];
				$save_as_name_value = $dget['file_name'];
				$filename = $dget['file_name'];
				$location = $dget['file_name'];
			}
			
			$retarr = array("status" => true, 
							"message" => "OK",
							"type" => $type,
							"sendidt" => $sendidt,
							"product_select" => $product_select,
							"save_as_name_value" => $save_as_name_value,
							"original_name" => $original_name,
							"filename" => $filename,
							"location" => $location,
							"config_data" => $config_data);
			unset($dget);
		}
		return $retarr;
	}
	
	
	
	
	
	
	
	//TODO: PENDIENTE REVISAR
	function epm_advanced_poce_sendid()
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! isset($_REQUEST['type_file'])) {
			$retarr = array("status" => false, "message" => _("No send Type File!"));
		}
		elseif (! isset($_REQUEST['sendid'])) {
			$retarr = array("status" => false, "message" => _("No send SendID!"));
		}
		else {
			$dget['product_select'] = $_REQUEST['product_select'];
			$dget['type_file'] = $_REQUEST['type_file'];
			$dget['sendid'] = $_REQUEST['sendid'];
			$dget['original_name'] = $_REQUEST['original_name'];
			$dget['config_text'] = $_REQUEST['config_text'];
			
			
			
			//DEBUGGGGGGGGGGGGG
			return;
			if ($dget['type_file'] == "sql") {
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
				$this->submit_config($row['directory'], $row['cfg_dir'], $dget['original_name'], $dget['config_text']);
				$retarr = array("status" => true, "message" => "Sent! Thanks :-)");
			}
			elseif ($dget['type_file'] == "file") {
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
				$error = $this->submit_config($row['directory'], $row['cfg_dir'], $dget['original_name'], $dget['config_text']);
				$retarr = array("status" => true, "message" => "Sent! Thanks :-)");
			}
			else {
				$retarr = array("status" => false, "message" => "Type not valid!");
			}
			unset ($dget);
		}
		return $retarr;
	}
	
	
	
	
	
	
	
	function epm_advanced_poce_save_file()
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! isset($_REQUEST['sendid'])) {
			$retarr = array("status" => false, "message" => _("No send SendID!"));
		}
		elseif (! isset($_REQUEST['type_file'])) {
			$retarr = array("status" => false, "message" => _("No send Type File!"));
		}
		elseif (! isset($_REQUEST['config_text'])) {
			$retarr = array("status" => false, "message" => _("No send Text File!"));
		}
		elseif (! isset($_REQUEST['save_as_name'])) {
			$retarr = array("status" => false, "message" => _("No send Save Name!"));
		}
		elseif (! isset($_REQUEST['file_name'])) {
			$retarr = array("status" => false, "message" => _("No send Name File!"));
		}
		elseif (! isset($_REQUEST['original_name'])) {
			$retarr = array("status" => false, "message" => _("No send Origianl Name File!"));
		}
		else
		{
			$dget['type_file'] = $_REQUEST['type_file'];
			$dget['sendid'] = $_REQUEST['sendid'];
			$dget['product_select'] = $_REQUEST['product_select'];
			$dget['save_as_name'] = $_REQUEST['save_as_name'];
			$dget['original_name'] = $_REQUEST['original_name'];
			$dget['file_name'] = $_REQUEST['file_name'];
			$dget['config_text'] = $_REQUEST['config_text'];
			
			
			if ($dget['type_file'] == "file")  
			{
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
				$config_files = explode(",", $row['config_files']);
				
				if ((is_array($config_files)) AND (in_array($dget['file_name'], $config_files)))
				{
					$pathdir = $this->PHONE_MODULES_PATH . 'endpoint/' . $row['directory'] . "/" . $row['cfg_dir'] . "/";
					$pathfile = $pathdir . $dget['file_name'];
					if ((! file_exists($pathfile)) AND (! is_writable($pathdir))) {
						$retarr = array("status" => false, "message" => "Directory is not Writable (".$pathdir.")!");
					}
					elseif (! is_writable($pathfile)) {
						$retarr = array("status" => false, "message" => "File is not Writable (".$pathfile.")!");
					}
					else 
					{
						$wfh = fopen($pathfile, 'w');
						fwrite($wfh, $dget['config_text']);
						fclose($wfh);
						$retarr = array("status" => true, "message" => "Saved to Hard Drive!");
					}
				}
				else {
					$retarr = array("status" => false, "message" => "The File no existe in the DataBase!");
				}
			}
			elseif ($dget['type_file'] == "sql") 
			{
				$sql = "UPDATE endpointman_custom_configs SET data = '" . addslashes($dget['config_text']) . "' WHERE id = " . $dget['sendid'];
				sql($sql);
				$retarr = array("status" => true, "message" => "Saved to Database!");
			}
			elseif ($dget['type_file'] == "tfile")
			{
				$db = $this->db;
				$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES (?,?,?,?)';
				$q = $db->prepare($sql);
				$ob = $q->execute(array(addslashes($dget['save_as_name']), addslashes($dget['original_name']), $dget['product_select'], addslashes($dget['config_text'])));
				$newidinsert = $db->lastInsertId();
				$retarr = array("status" => true, "message" => "Saved to Database!");
				
				$retarr['type_file'] = "sql";
				$retarr['location'] = "SQL: ". $dget['save_as_name'];
				$retarr['sendidt'] = $newidinsert;
			}
			else {
				$retarr = array("status" => false, "message" => "Type not valid!");
			}
			
			
			$retarr['original_name'] = $dget['original_name'];
			$retarr['file_name'] = $dget['file_name'];
			$retarr['save_as_name'] = $dget['save_as_name'];
			unset($dget);
		}
		return $retarr;
	}
	
	function epm_advanced_poce_delete_config_custom()
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! isset($_REQUEST['type_file'])) {
			$retarr = array("status" => false, "message" => _("No send Type File!"));
		}
		elseif (! isset($_REQUEST['sql_select'])) {
			$retarr = array("status" => false, "message" => _("No send SQL Select!"));
		}
		else {
			$dget['type_file'] = $_REQUEST['type_file'];
			$dget['product_select'] = $_REQUEST['product_select'];
			$dget['sql_select'] = $_REQUEST['sql_select'];
			
			if ($dget['type_file'] == "sql") {
				$sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $dget['sql_select'];
				sql($sql);
				unset ($sql);
				$retarr = array("status" => true, "message" => "File delete ok!");
			}
			else { $retarr = array("status" => false, "message" => _("Type File not valid!")); }
			
			unset($dget);
		}
		return $retarr;
	}
	/********************
	 * END SEC FUNCTIONS *
	 ********************/
	
	
	
	
	
	
	
	
	/************************************************************
	 **** FUNCIONES SEC MODULO "epm_advanced\manual_upload". ****
	 ***********************************************************/
	
	public function epm_advanced_manual_upload_list_files_brans_export()
	{
		$path_tmp_dir = $this->PHONE_MODULES_PATH."temp/export/";
		$array_list_files = array();
		$array_count_brand = array();
		
		
		$array_list_exception= array(".", "..", ".htaccess");
		if(file_exists($path_tmp_dir))
		{
			if(is_dir($path_tmp_dir))
			{
				$l_files = scandir($path_tmp_dir, 1);
				$i = 0;
				foreach ($l_files as $archivo) {
					if (in_array($archivo, $array_list_exception)) { continue; }
					
					$pathandfile = $path_tmp_dir.$archivo;
					$brand = substr(pathinfo($archivo, PATHINFO_FILENAME), 0, -11);
					$ftime = substr(pathinfo($archivo, PATHINFO_FILENAME), -10);
					
					$array_count_brand[] = $brand;
					$array_list_files[$i] = array("brand" => $brand,
							"pathall" => $pathandfile,
							"path" => $path_tmp_dir,
							"file" => $archivo,
							"filename" => pathinfo($archivo, PATHINFO_FILENAME),
							"extension" => pathinfo($archivo, PATHINFO_EXTENSION),
							"timer" => $ftime,
							"timestamp" => strftime("[%Y-%m-%d %H:%M:%S]", $ftime),
							"mime_type" => mime_content_type($pathandfile),
							"is_dir" => is_dir($pathandfile),
							"is_file" => is_file($pathandfile),
							"is_link" => is_link($pathandfile),
							"readlink" => (is_link($pathandfile) == true ? readlink ($pathandfile) : NULL));
					
					$i++;
				}
				unset ($l_files);
				
				$array_count_brand = array_count_values($array_count_brand);
				ksort ($array_count_brand);
				$array_count_brand_end = array();
				
				foreach($array_count_brand as $key => $value) {
					$array_count_brand_end[] = array('name' => $key , 'num' => $value);
				}
				
				$retarr = array("status" => true, "message" => _("List Done!"), "countlist" => count($array_list_files), "list_files" => $array_list_files, "list_brands" => $array_count_brand_end );
				unset ($array_count_brand_end);
				unset ($array_count_brand);
				unset ($array_list_files);
			}
			else {
				$retarr = array("status" => false, "message" => _("Not is directory: ") . $path_tmp_dir);
			}
		} else {
			$retarr = array("status" => false, "message" => _("Directory no exists: ") . $path_tmp_dir);
		}
		return $retarr;
	}
	
	public function epm_advanced_manual_upload_brand()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			echo format_txt(_("Can Not Find Uploaded Files!"), "error");
		}
		else
		{
			foreach ($_FILES["files"]["error"] as $key => $error) {
				echo format_txt(_("Importing brand file %_FILE_%..."), "", array("%_FILE_%" => $_FILES["files"]["name"][$key]));
			
				if ($error != UPLOAD_ERR_OK) {
					echo format_txt($this->file_upload_error_message($error), "error");
				}
				else 
				{
		 			
					$uploads_dir = $this->PHONE_MODULES_PATH . "temp";
					$name = $_FILES["files"]["name"][$key];
					$extension = pathinfo($name, PATHINFO_EXTENSION);
					if ($extension == "tgz") {
						
						$tmp_name = $_FILES["files"]["tmp_name"][$key];
						$uploads_dir_file = $uploads_dir."/".$name;
						move_uploaded_file($tmp_name, $uploads_dir_file);
						
						if (file_exists($uploads_dir_file)) 
						{
							$temp_directory = sys_get_temp_dir() . "/epm_temp/";
							if (!file_exists($temp_directory)) {
								echo format_txt(_("Creating EPM temp directory..."));
								if (mkdir($temp_directory) == true) {
									echo format_txt(_("Done!"), "done");
								}
								else {
									echo format_txt(_("Error!"), "error");
								}
							}
							if (file_exists($temp_directory)) 
							{
								if ($this->configmod->get('debug')) {
									echo format_txt(_("Extracting Tarball %_FILEPACKAGE_% to %_TEMPDIR_%........"),"",array("%_FILEPACKAGE_%" => $uploads_dir_file, "%_TEMPDIR_%" => $temp_directory));
								} else {
									echo format_txt(_("Extracting Tarball........ "));
								}
								exec("tar -xvf ".$uploads_dir_file." -C ".$temp_directory);
								echo format_txt(_("Done!"), "done");
								
								$package = basename($name, ".tgz");
								$package = explode("-",$package);
								
								if ($this->configmod->get('debug')) {
									echo format_txt(_("Looking for file %_FILEPACKAGE_% to pass on to update_brand()..."),"",array("%_FILEPACKAGE_%" => $temp_directory.$package[0]));
								} else {
									echo format_txt(_("Looking file and update brand's...."));
								}
								
								if(file_exists($temp_directory.$package[0])) {
									$this->update_brand($package[0],FALSE);
									//                  Note: no need to delete/unlink/rmdir as this is handled in update_brand()
								} else {
									echo format_txt(_("Please name the Package the same name as your brand!"));
								}
							}
						} 
						else {
							echo format_txt(_("No File Provided!"), "error");
							//echo "File ".$this->PHONE_MODULES_PATH."temp/".$_REQUEST['package']." not found. <br />";
						}
					} 
					else {
						echo format_txt(_("Invalid File Extension!"), "error");
					}
		 		}
			}
		}
	}
	
	public function epm_advanced_manual_upload_provisioner ()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			echo format_txt(_("Can Not Find Uploaded Files!"), "error");
		}
		else
		{
			foreach ($_FILES["files"]["error"] as $key => $error) {
				echo format_txt(_("Importing Provisioner file %_FILE_%..."), "", array("%_FILE_%" => $_FILES["files"]["name"][$key]));
			
				if ($error != UPLOAD_ERR_OK) {
					echo format_txt($this->file_upload_error_message($error), "error");
				}
				else 
				{
					$uploads_dir = $this->PHONE_MODULES_PATH . "temp";
					$name = $_FILES["files"]["name"][$key];
					$extension = pathinfo($name, PATHINFO_EXTENSION);
					if ($extension == "tgz") 
					{
						$tmp_name = $_FILES["files"]["tmp_name"][$key];
						$uploads_dir_file = $uploads_dir."/".$name;
						move_uploaded_file($tmp_name, $uploads_dir_file);
						
						if (file_exists($uploads_dir_file)) {
							echo format_txt(_("Extracting Provisioner Package..."));
							exec("tar -xvf ".$uploads_dir_file." -C ".$uploads_dir."/");
							echo format_txt(_("Done!"), "done");
							
							if(!file_exists($this->PHONE_MODULES_PATH."endpoint")) {
								echo format_txt(_("Creating Provisioner Directory..."));
								if (mkdir($this->PHONE_MODULES_PATH."endpoint") == true) {
									echo format_txt(_("Done!"), "done");
								}
								else {
									echo format_txt(_("Error!"), "error");
								}
							}
							
							if(file_exists($this->PHONE_MODULES_PATH."endpoint")) 
							{
								$endpoint_last_mod = filemtime($this->PHONE_MODULES_PATH."temp/endpoint/base.php");
								rename($this->PHONE_MODULES_PATH."temp/endpoint/base.php", $this->PHONE_MODULES_PATH."endpoint/base.php");
								
								echo format_txt(_("Updating Last Modified..."));
								$sql = "UPDATE endpointman_global_vars SET value = '".$endpoint_last_mod."' WHERE var_name = 'endpoint_vers'";
								sql($sql);
								echo format_txt(_("Done!"),"done");
							}
							
						} else { 
							echo format_txt(_("File Temp no Exists!"), "error");
						}
					} else {
						echo format_txt(_("Invalid File Extension!"), "error");
					}
				}
			}
		}
	}
	
	public function epm_advanced_manual_upload_export_brans_available_file()
	{
		if ((! isset($_REQUEST['file_package'])) OR ($_REQUEST['file_package'] == "")) {
			header('HTTP/1.0 404 Not Found', true, 404);
			echo "<h1>Error 404 Not Found</h1>";
			echo "No send name file!";
			die();
		}
		else {
			$dget['file_package'] = $_REQUEST['file_package'];
			$path_tmp_file = $this->PHONE_MODULES_PATH."/temp/export/".$_REQUEST['file_package'];
			
			if (! file_exists($path_tmp_file)) {
				header('HTTP/1.0 404 Not Found', true, 404);
				echo "<h1>Error 404 Not Found</h1>";
				echo "File no exist!";
				die();
			}
			else {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($dget['file_package']).'"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($path_tmp_file));
				readfile($path_tmp_file);
				exit;
			}
			unset ($path_tmp_file);
			unset ($dget);
		}
		exit;
	}
	
	public function epm_advanced_manual_upload_export_brans_available()
	{
		if ((! isset($_REQUEST['package'])) OR ($_REQUEST['package'] == "")) {
			echo format_txt(_("No package set!"), "error");
		}
		elseif ((! is_numeric($_REQUEST['package'])) OR ($_REQUEST['package'] < 0)) {
			echo format_txt(_("Package not valid!"), "error");
		}
		else {
			$dget['package'] = $_REQUEST['package'];
			
			$sql = 'SELECT `name`, `directory` FROM `endpointman_brand_list` WHERE `id` = '.$dget['package'].'';
			$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
			
			if ($row == "") {
				echo format_txt(_("ID Package send not valid, brand not exist!"), "error");
			}
			else {
				echo format_txt(_("Exporting %_NAME_%"), "", array("%_NAME_%" => $row['name']));
				
				if(!file_exists($this->PHONE_MODULES_PATH."/temp/export/")) {
					mkdir($this->PHONE_MODULES_PATH."/temp/export/");
				}
				$time = time();
				exec("tar zcf ".$this->PHONE_MODULES_PATH."temp/export/".$row['directory']."-".$time.".tgz --exclude .svn --exclude firmware -C ".$this->PHONE_MODULES_PATH."/endpoint ".$row['directory']);
				
				
				echo format_txt(_("Done!"), "done");
				echo format_txt(_("Click this link to download:"));
				echo "<a href='config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables_file&file_package=".$row['directory']."-".$time.".tgz' target='_blank'>";
				echo format_txt(_("Here"));
				echo "</a>";
				//echo "Done! Click this link to download:<a href='modules/_ep_phone_modules/temp/export/".$row['directory']."-".$time.".tgz' target='_blank'>Here</a>";
			}
			unset ($dget);
		}
	}
	
	/********************
	 * END SEC FUNCTIONS *
	 ********************/
	
	
	
	/***************************************************
	 **** FUNCIONES SEC MODULO "epm_advanced\iedl". ****
	 **************************************************/
	
	public function epm_advanced_iedl_export($sFileName = "devices_list.csv")
	{
		header("Content-type: text/csv");
		header('Content-Disposition: attachment; filename="'.$sFileName.'"');
		$outstream = fopen("php://output",'w');
		$sql = 'SELECT endpointman_mac_list.mac, endpointman_brand_list.name, endpointman_model_list.model, endpointman_line_list.ext,endpointman_line_list.line FROM endpointman_mac_list, endpointman_model_list, endpointman_brand_list, endpointman_line_list WHERE endpointman_line_list.mac_id = endpointman_mac_list.id AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.brand = endpointman_brand_list.id';
		$result = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
		foreach($result as $row) {
			fputcsv($outstream, $row);
		}
		fclose($outstream);
		exit;
	}
	
	//Dave B's Q&D file upload security code (http://us2.php.net/manual/en/features.file-upload.php)
	public function epm_advanced_iedl_import()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			echo format_txt(_("Can Not Find Uploaded Files!"), "error");
		}
		else 
		{
			$allowedExtensions = array("application/csv", "text/plain");
			
			foreach ($_FILES["files"]["error"] as $key => $error) {
				echo format_txt(_("Importing CVS file %_FILE_%..."), "", array("%_FILE_%" => $_FILES["files"]["name"][$key]));
				
				if ($error != UPLOAD_ERR_OK) {
					echo format_txt($this->file_upload_error_message($error), "error");
				}
				else
				{
					if (!in_array($_FILES["files"]["type"][$key], $allowedExtensions)) {
						echo format_txt(_("We support only CVS and TXT files, type file %_FILE_% no support!"), "error", array("%_FILE_%" => $_FILES["files"]["name"][$key]));
					}
					elseif ($_FILES["files"]["size"][$key] == 0) {
						echo format_txt(_("File %_FILE_% size is 0!"), "error", array("%_FILE_%" => $_FILES["files"]["name"][$key]));
					}
					else {
						$uploadfile = $this->LOCAL_PATH . basename($_FILES["files"]["name"][$key]);
						$uploadtemp = $_FILES["files"]["tmp_name"][$key];
						
						if (move_uploaded_file($uploadtemp, $uploadfile)) {
							//Parse the uploaded file
							$handle = fopen($uploadfile, "r");
							$i = 1;
							while (($device = fgetcsv($handle, filesize($uploadfile))) !== FALSE) {
								if ($device[0] != "") {
									if ($mac = $this->mac_check_clean($device[0])) {
										$sql = "SELECT id FROM endpointman_brand_list WHERE name LIKE '%" . $device[1] . "%' LIMIT 1";
										$res = sql($sql);
										
										if ($res->numRows() > 0) {
											$brand_id = sql($sql, 'getOne');
											$brand_id = $brand_id[0];
											
											$sql_model = "SELECT id FROM endpointman_model_list WHERE brand = " . $brand_id . " AND model LIKE '%" . $device[2] . "%' LIMIT 1";
											$sql_ext = "SELECT extension, name FROM users WHERE extension LIKE '%" . $device[3] . "%' LIMIT 1";
											
											$line_id = isset($device[4]) ? $device[4] : 1;
											
											$res_model = sql($sql_model);
											if ($res_model->numRows()) {
												$model_id = sql($sql_model, 'getRow', DB_FETCHMODE_ASSOC);
												$model_id = $model_id['id'];
												
												$res_ext = sql($sql_ext);
												if ($res_ext->numRows()) {
													$ext = sql($sql_ext, 'getRow', DB_FETCHMODE_ASSOC);
													$description = $ext['name'];
													$ext = $ext['extension'];
													
													$this->add_device($mac, $model_id, $ext, 0, $line_id, $description);
													
													echo format_txt(_("Done!"), "done");
												} else {
													echo format_txt(_("Invalid Extension Specified on line %_LINE_%!"), "error", array("%_LINE_%" => $i));
												}
											} else {
												echo format_txt(_("Invalid Model Specified on line %_LINE_%!"), "error", array("%_LINE_%" => $i));
											}
										} else {
											echo format_txt(_("Invalid Brand Specified on line %_LINE_%!"), "error", array("%_LINE_%" => $i));
										}
									} else {
										echo format_txt(_("Invalid Mac on line %_LINE_%!"), "error", array("%_LINE_%" => $i));
									}
								}
								$i++;
							}
							fclose($handle);
							unlink($uploadfile);
							echo format_txt(_("Please reboot & rebuild all imported phones"), "done");
						} else {
							echo format_txt(_("Possible file upload attack!"), "error");
						}
					}
				}
			}
		}
	}
	
	/********************
	 * END SEC FUNCTIONS *
	 ********************/
	
	
	
	/**********************************************************
	 **** FUNCIONES SEC MODULO "epm_advanced\oui_manager". ****
	 *********************************************************/
	
	private function epm_advanced_oui_remove()
	{
		//TODO: Añadir validacion de si es custom o no
		if ((! isset($_REQUEST['id_del'])) OR ($_REQUEST['id_del'] == "")) {
			$retarr = array("status" => false, "message" => _("No ID set!"));
		}
		elseif ((! is_numeric($_REQUEST['id_del'])) OR ($_REQUEST['id_del'] < 0)) {
			$retarr = array("status" => false, "message" => _("ID  not valid!"), "id" => $_REQUEST['id']);
		}
		else
		{
			$dget['id'] = $_REQUEST['id_del'];
			
			$sql = "DELETE FROM endpointman_oui_list WHERE id = " . $dget['id'];
			sql($sql);
			
			$retarr = array("status" => true, "message" => "OK", "id" => $dget['id']);
			unset($dget);
		}
		return $retarr;
	} 
	
	private function epm_advanced_oui_add() 
	{
		//TODO: Pendiente añadir isExiste datos.
		if ((! isset($_REQUEST['number_new_oui'])) OR ($_REQUEST['number_new_oui'] == "")) {
			$retarr = array("status" => false, "message" => _("No OUI set!"));
		}
		elseif ((! isset($_REQUEST['brand_new_oui'])) OR ($_REQUEST['brand_new_oui'] == "")) {
			$retarr = array("status" => false, "message" => _("No Brand set!"));
		}
		else {
			$dget['oui'] = $_REQUEST['number_new_oui'];
			$dget['brand'] = $_REQUEST['brand_new_oui'];
			
			$sql = "INSERT INTO  endpointman_oui_list (oui, brand, custom) VALUES ('" . $dget['oui'] . "',  '" . $dget['brand'] . "',  '1')";
			sql($sql);
			
			$retarr = array("status" => true, "message" => "OK", "oui" => $dget['oui'], "brand" => $dget['brand']);
			unset($dget);
		}
		return $retarr;
	}
	
	/********************
	 * END SEC FUNCTIONS *
	 ********************/
	
	
	
	/******************************************************
	**** FUNCIONES SEC MODULO "epm_advanced\settings". ****
	******************************************************/
	
	public function epm_advanced_config_loc_is_writable()
	{
		$config_loc = $this->configmod->get("config_loc");
		$tftp_writable = FALSE;
		if ((isset($config_loc)) AND ($config_loc != "")) {
			if ((file_exists($config_loc)) AND (is_dir($config_loc))) {
				if (is_writable($config_loc)) {
					$tftp_writable = TRUE;
				} 
			}
		} 
		return $tftp_writable;
	}
	
	private function epm_advanced_settings_saveconfig ()
	{
		if (! isset($_REQUEST['name'])) {
			$retarr = array("status" => false, "message" => _("No send name!"));
		}
		elseif (! isset($_REQUEST['value'])) {
			$retarr = array("status" => false, "message" => _("No send value!"));
		}
		else 
		{
			$dget['name'] = strtolower($_REQUEST['name']);
			$dget['value'] = $_REQUEST['value'];
			switch($dget['name']) {
				case "enable_ari":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='enable_ari'";
					break;
					
				case "enable_debug":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='debug'";
					break;
					
				case "disable_help":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='disable_help'";
					break;
					
				case "allow_dupext":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='show_all_registrations'";
					break;
					
				case "allow_hdfiles":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='allow_hdfiles'";
					break;
					
				case "tftp_check":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='tftp_check'";
					break;
				
				case "backup_check":
					$dget['value'] = strtolower($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='backup_check'";
					break;
				
				case "use_repo":
					$dget['value'] = strtolower($dget['value']);
					if (($dget['value'] == "yes") and (! $this->has_git())) {
						$retarr = array("status" => false, "message" => _("Git not installed!")); 
					}
					else {
						$sql = "UPDATE endpointman_global_vars SET value='" . ($dget['value'] == "yes" ? "1": "0") . "' WHERE var_name='use_repo'";
					}
					break;
					
				case "config_loc":
					$dget['value'] = trim($dget['value']);
					//No trailing slash. Help the user out and add one :-)
					if ($dget['value'][strlen($dget['value']) - 1] != "/") {
						$dget['value'] = $dget['value'] . "/";
					}
					if ($dget['value'] != "") {
						if ((file_exists($dget['value'] = $dget['value'])) AND (is_dir($dget['value'] = $dget['value']))) {
							if (is_writable($dget['value'] = $dget['value'])) {
								$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='config_location'";
							} else {
								$retarr = array("status" => false, "message" => _("Directory Not Writable!")); 
							}
						} else {
							$retarr = array("status" => false, "message" => _("Not a Vaild Directory.<br /> Try to run 'mkdir " . $_POST['config_loc'] . "' as root.")); 
						}
					} else {
						$retarr = array("status" => false, "message" => _("No Configuration Location Defined!")); 
					}
					break;
					
				case "srvip":
					$dget['value'] = trim($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='srvip'";
					break;
					
				case "tz":
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='tz'";
					break;
					
				case "ntp_server":
					$dget['value'] = trim($dget['value']);
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='ntp'";
					break;
					
				case "nmap_loc":
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='nmap_location'";
					break;
					
				case "arp_loc":
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='arp_location'";
					break;
					
				case "asterisk_loc":
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='asterisk_location'";
					break;
					
				case "package_server":
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='update_server'";
					break;
				
				case "cfg_type":
					if ($dget['value'] == 'http') {
						$symlink = $this->config->get('AMPWEBROOT') . "/provisioning";
						$reallink = $this->LOCAL_PATH . "provisioning";
						if ((!is_link($symlink)) OR (!readlink($symlink) == $reallink)) {
							if (!symlink($reallink, $symlink)) {
								$retarr = array("status" => false, "message" => _("Your permissions are wrong on " . $this->config->get('AMPWEBROOT') . ", web provisioning link not created!")); 
								//$dget['value'] = 'file';
								break;
							} else {
								$dget['value'] = 'http';
							}
						} else {
							$dget['value'] = 'http';
						}
					} else {
						$dget['value'] = 'file';
					}
					$sql = "UPDATE endpointman_global_vars SET value='" . $dget['value'] . "' WHERE var_name='server_type'";
					break;
					
				default:
					$retarr = array("status" => false, "message" => _("Name invalid: ") . $dget['name'] ); 
			}
			if (isset($sql)) {
				sql($sql);
				$retarr = array("status" => true, "message" => "OK", "name" => $dget['name'], "value" => $dget['value']);
				unset($sql);
			}
			unset($dget);
		}
		return $retarr;
	}
	
	/********************
	* END SEC FUNCTIONS *
	********************/
	
	
	
	/***************************************************
	**** FUNCIONES SEC MODULO "epm_config\manager". ****
	***************************************************/
	
	private function epm_config_manager_check_for_updates ()
	{
		out("<h3>Update data...</h3>");
		$this->update_check(true);
		out ("All Done!");
	}
	
	private function epm_config_manager_brand()
	{
		if (! isset($_REQUEST['command_sub'])) {
			out (_("Error: Not send command!"));
		}
		elseif (! isset($_REQUEST['idfw'])) { 
			out (_("Error: Not send ID!"));
		}
		else if (! is_numeric($_REQUEST['idfw'])) {
			out (_("Error: ID not is number!"));
		}
		else
		{
			$dget['command'] =  strtolower($_REQUEST['command_sub']);
			$dget['id'] = $_REQUEST['idfw'];
			
			switch($dget['command']) {
				case "brand_install":
				case "brand_update":
					$this->download_brand($dget['id']);
					break;
				
				case "brand_uninstall":
					$this->remove_brand($dget['id']);
					break;
					
				default:
					out (_("Error: Command not found!")." [" . $dget['command'] . "]");
			}
			$this->update_check();
			unset ($dget);
			
			return true;
		}
		return false;
	}
	
	private function epm_config_manager_firmware()
	{
		if (! isset($_REQUEST['command_sub'])) { 
			out (_("Error: Not send command!"));
		}
		elseif (! isset($_REQUEST['idfw'])) { 
			out (_("Error: Not send ID!"));
		}
		else if (! is_numeric($_REQUEST['idfw'])) { 
			out (_("Error: ID not is number!"));
		}
		else
		{
			$dget['command'] =  strtolower($_REQUEST['command_sub']);
			$dget['id'] = $_REQUEST['idfw'];
			
			switch($dget['command']) {
				case "fw_install":
				case "fw_update":
					$this->install_firmware($dget['id']);
					break;
					
				case "fw_uninstall":
					$this->remove_firmware($dget['id']);
					break;
					
				default:
					out (_("Error: Command not found!")." [" . $dget['command'] . "]");
			}
			unset ($dget);
			
			return true;
		}
		return false;
	}
	
	private function epm_config_manager_saveconfig()
	{
		if (! isset($_REQUEST['name'])) {
			$retarr = array("status" => false, "message" => _("No send name!"));
		}
		elseif (! isset($_REQUEST['value'])) {
			$retarr = array("status" => false, "message" => _("No send value!"));
		}
		elseif (! isset($_REQUEST['idbt'])) {
			$retarr = array("status" => false, "message" => _("No send id!"));
		}
		elseif (! isset($_REQUEST['idtype'])) {
			$retarr = array("status" => false, "message" => _("No send idtype!"));
		}
		elseif (! is_numeric($_REQUEST['idbt'])) {
			$retarr = array("status" => false, "message" => _("ID send is not number!"));
		}
		else 
		{
			$dget['name'] = strtolower($_REQUEST['name']);
			$dget['value'] = strtolower($_REQUEST['value']);
			$dget['idtype'] = strtolower($_REQUEST['idtype']);
			$dget['id'] = $_REQUEST['idbt'];
			
			switch($dget['idtype']) {
				case "marca":
					$sql = "UPDATE endpointman_brand_list SET enabled = '" .$dget['value']. "' WHERE id = '".$dget['id']."'";
					break;
					
				case "modelo":
					$sql = "UPDATE endpointman_model_list SET enabled = " .$dget['value']. " WHERE id = '".$dget['id']."'";
					break;
					
				default:
					$retarr = array("status" => false, "message" => _("IDType invalid: ") . $dget['idtype'] ); 
			}
			if (isset($sql)) {
				sql($sql);
				$retarr = array("status" => true, "message" => "OK", "name" => $dget['name'], "value" => $dget['value'], "idtype" => $dget['idtype'], "id" => $dget['id']);
				unset($sql);
			}
			unset($dget);
		}
		return $retarr;
	}
	
	public function epm_config_manager_hardware_get_list_all($check_for_updates = true)
	{
		$row_out = array();
		$i = 0;
		$brand_list = $this->epm_config_hardware_get_list_brand(true);
		//FIX: https://github.com/FreePBX-ContributedModules/endpointman/commit/2ad929d0b38f05c9da1b847426a4094c3314be3b
		if($check_for_updates) 	$brand_up = $this->update_check();
		foreach ($brand_list as $row) 
		{
			$row_out[$i] = $row;
			$row_out[$i]['count'] = $i;
			$row_out[$i]['cfg_ver_datetime'] = date("c",$row['cfg_ver']);
			
			if($check_for_updates) 
			{
				$id = $this->system->arraysearchrecursive($row['name'], $brand_up,'name');
				$id = $id[0];
				if((isset($brand_up[$id]['update'])) AND ($row['installed'] == 1)) {
					$row_out[$i]['update'] = $brand_up[$id]['update'];
				} else {
					$row_out[$i]['update'] = "";
				}
				if(isset($brand_up[$id]['update_vers'])) {
					//$row_out[$i]['update_vers'] = date("n-j-y",$brand_up[$id]['update_vers']) . " at " . date("g:ia",$brand_up[$id]['update_vers']);
					$row_out[$i]['update_vers'] = date("c",$brand_up[$id]['update_vers']);
				} else {
					$row_out[$i]['update_vers'] = "";
				}
			}
			else 
			{
				if (! isset($row_out[$i]['update'])) 		{ $row_out[$i]['update'] = ""; }
				if (! isset($row_out[$i]['update_vers'])) 	{ $row_out[$i]['update_vers'] = ""; }
			}
			if ($row['hidden'] == 1) { continue; }
			
			
			$j = 0;
			$product_list = $this->epm_config_hardware_get_list_product($row['id'], true);
			foreach($product_list as $row2) {
				$row_out[$i]['products'][$j] = $row2;
				
				if($check_for_updates) {
					if((array_key_exists('firmware_vers', $row2)) AND ($row2['firmware_vers'] > 0)) {
						$temp = $this->firmware_update_check($row2['id']);
						$row_out[$i]['products'][$j]['update_fw'] = 1;
						$row_out[$i]['products'][$j]['update_vers_fw'] = $temp['data']['version'];
					} else {
						$row_out[$i]['products'][$j]['update_fw'] = 0;
						$row_out[$i]['products'][$j]['update_vers_fw'] = "";
					}
				}
				else 
				{
					if (! isset($row_out[$i]['products'][$j]['update_fw'])) 		{ $row_out[$i]['products'][$j]['update_fw'] = 0; }
					if (! isset($row_out[$i]['products'][$j]['update_vers_fw'])) 	{ $row_out[$i]['products'][$j]['update_vers_fw'] = ""; }
				}
				$row_out[$i]['products'][$j]['fw_type'] = $this->firmware_local_check($row2['id']);
				if ($row2['hidden'] == 1) { continue; }
				
				$k = 0;
				$model_list = $this->epm_config_hardware_get_list_models($row2['id'], true);
				foreach($model_list as $row3) 
				{
					$row_out[$i]['products'][$j]['models'][$k] = $row3;
					if($row_out[$i]['products'][$j]['models'][$k]['enabled']){
						$row_out[$i]['products'][$j]['models'][$k]['enabled_checked'] = 'checked';
					}
					$k++;
				}
				$j++;
			}
			$i++;
		}
		return $row_out;
	}
	/********************
	* END SEC FUNCTIONS *
	********************/
	
	
	
	/**************************************************
	**** FUNCIONES SEC MODULO "epm_config\editor". ****
	**************************************************/
	private function epm_config_editor_saveconfig()
	{
		if (! isset($_REQUEST['name'])) {
			$retarr = array("status" => false, "message" => _("No send name!"));
		}
		elseif (! isset($_REQUEST['value'])) {
			$retarr = array("status" => false, "message" => _("No send value!"));
		}
		elseif (($_REQUEST['value'] > 1 ) and ($_REQUEST['value'] < 0))
		{
			$retarr = array("status" => false, "message" => _("Invalid Value!"));
		}
		elseif (! isset($_REQUEST['idbt'])) {
			$retarr = array("status" => false, "message" => _("No send id!"));
		}
		elseif (! isset($_REQUEST['idtype'])) {
			$retarr = array("status" => false, "message" => _("No send idtype!"));
		}
		elseif (! is_numeric($_REQUEST['idbt'])) {
			$retarr = array("status" => false, "message" => _("ID send is not number!"));
		}
		else 
		{
			$dget['name'] = strtolower($_REQUEST['name']);
			$dget['value'] = strtolower($_REQUEST['value']);
			$dget['idtype'] = strtolower($_REQUEST['idtype']);
			$dget['id'] = $_REQUEST['idbt'];
			
			switch($dget['idtype']) {
				case "marca":
					$sql = "UPDATE endpointman_brand_list SET hidden = '".$dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;
				
				case "producto":
					$sql = "UPDATE endpointman_product_list SET hidden = '". $dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;
						
				case "modelo":
					$sql = "UPDATE endpointman_model_list SET hidden = '". $dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;
					
				default:
					$retarr = array("status" => false, "message" => _("IDType invalid: ") . $dget['idtype'] ); 
			}
			if (isset($sql)) {
				sql($sql);
				$retarr = array("status" => true, "message" => "OK", "name" => $dget['name'], "value" => $dget['value'], "idtype" => $dget['idtype'], "id" => $dget['id']);
				unset($sql);
			}
			unset($dget);
		}
		return $retarr;
	}
	
	/**
     * Get info all brdans, prodics, models.
     * @return array
     */
	public function epm_config_editor_hardware_get_list_all () 
	{
		$row_out = array();
		$i = 0;
		foreach ($this->epm_config_hardware_get_list_brand(true) as $row) 
		{
			$row_out[$i] = $row;
			$row_out[$i]['count'] = $i;
			if ($row['installed']) 
			{
				$j = 0;
				foreach ($this->epm_config_hardware_get_list_product($row['id'], true) as $row2) 
				{
					$row_out[$i]['products'][$j] = $row2;
					$k = 0;
					foreach ($this->epm_config_hardware_get_list_models($row2['id'], true) as $row3) {
						$row_out[$i]['products'][$j]['models'][$k] = $row3;
						$k++;
					}
					$j++;
				}
			}
			$i++;
		}
		return $row_out;
	}
	/********************
	* END SEC FUNCTIONS *
	********************/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	/*****************************************
	****** CODIGO ANTIGUO -- REVISADO ********
	*****************************************/
	
	
	/**
     * Check for new firmware on the servers
     * @param int $id Product ID
     * @return bool True on yes False on no
     */
    function firmware_update_check($id=NULL) {
        $sql = "SELECT * FROM  endpointman_product_list WHERE  id ='" . $id . "'";
        $row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

        $sql = "SELECT directory FROM  endpointman_brand_list WHERE id ='" . $row['brand'] . "'";
        $brand_directory = sql($sql, 'getOne');

        //config drive unknown!
        if ($row['cfg_dir'] == "") {
            return FALSE;
        } else {
            $temp = $this->file2json($this->PHONE_MODULES_PATH . "endpoint/" . $brand_directory . "/" . $row['cfg_dir'] . "/family_data.json");
            if ((array_key_exists('data', $temp)) AND (!is_array($temp['data']['firmware_ver']))) {
                if ($row['firmware_vers'] < $temp['data']['firmware_ver']) {
                    return $temp;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        }
    }
	
	/**
     * Check to see the status of the firmware locally (installed or not)
     * @param int $id
     * @return string
     */
    function firmware_local_check($id=NULL) {
        $sql = "SELECT * FROM  endpointman_product_list WHERE hidden = 0 AND id ='" . $id . "'";
        $res = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
		
        if (count($res) > 0) {
            $row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
			
            $sql = "SELECT directory FROM  endpointman_brand_list WHERE hidden = 0 AND id ='" . $row['brand'] . "'";
            $brand_directory = sql($sql, 'getOne');

            //config drive unknown!
            if ($row['cfg_dir'] == "") {
				return("nothing");
            } else {
                $temp = $this->file2json($this->PHONE_MODULES_PATH . "endpoint/" . $brand_directory . "/" . $row['cfg_dir'] . "/family_data.json");
                if ( (isset($temp['data']['firmware_ver'])) AND (! empty ($temp['data']['firmware_ver'])) ) {
                    if ($row['firmware_vers'] == "") {
                        return("install");
                    } else {
                        return("remove");
                    }
                } else {
                    return("nothing");
                }
            }
        } else {
            return("nothing");
        }
    }
	
	/**
     * Reads a file. Json decodes it and will report any errors back
     * @param string $file location of file
     * @return mixed false on error, array on success
     * @version 2.11
     */
    function file2json($file) {
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $data = json_decode($json, TRUE);
            if(function_exists('json_last_error')) {
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        return($data);
                        break;
                    case JSON_ERROR_DEPTH:
                        $this->error['file2json'] = _('Maximum stack depth exceeded');
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $this->error['file2json'] = _('Underflow or the modes mismatch');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $this->error['file2json'] = _('Unexpected control character found');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $this->error['file2json'] = _('Syntax error, malformed JSON');
                        break;
                    case JSON_ERROR_UTF8:
                        $this->error['file2json'] = _('Malformed UTF-8 characters, possibly incorrectly encoded');
                        break;
                    default:
                        $this->error['file2json'] = _('Unknown error');
                        break;
                }
                return(false);
            } else {
                //Probably an older version of PHP. That's ok though
                return($data);
            }
        } else {
            $this->error['file2json'] = _('Cant find file:').' '.$file ;
            return(false);
        }
    }
	
	/**
     * Check for new packges for brands. These packages will include phone models and such which the user can remove if they want
     * This function will alos auto-update the provisioner.net library incase anything has changed
     * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
     */
    function update_check($echomsg = false, &$error=array()) {
        $temp_location = $this->sys_get_temp_dir() . "/epm_temp/";
        if (!$this->configmod->get('use_repo')) {
        	if ($echomsg == true) {
        		$master_result = $this->system->download_file_with_progress_bar($this->UPDATE_PATH . "master.json", $this->PHONE_MODULES_PATH . "endpoint/master.json");
        	} else {
        		$master_result = $this->system->download_file($this->UPDATE_PATH . "master.json", $this->PHONE_MODULES_PATH . "endpoint/master.json");
        	}

            if (!$master_result) {
            	$error['brand_update_check_master'] = _("Error: Not able to connect to repository. Using local master file instead.");
            	if ($echomsg == true ) {
            		out($error['brand_update_check_master']);
            	}
            }
            
            $temp = $this->file2json($this->PHONE_MODULES_PATH . 'endpoint/master.json');
            $endpoint_package = $temp['data']['package'];
            $endpoint_last_mod = $temp['data']['last_modified'];

            $sql = "SELECT value FROM endpointman_global_vars WHERE var_name LIKE 'endpoint_vers'";
            $data = sql($sql, 'getOne');

            $contents = file_get_contents($this->UPDATE_PATH . "/update_status");
            if ($contents != '1') {
                if (($data == "") OR ($data <= $endpoint_last_mod)) {
                    if ((!$master_result) OR (!$this->system->download_file($this->UPDATE_PATH . '/' . $endpoint_package, $temp_location . $endpoint_package))) 
                    {
                    	$error['brand_update_check_json'] = _("Not able to connect to repository. Using local Provisioner.net Package");
                    	if ($echomsg == true ) {
                    		out($error['brand_update_check_json']);
                    	}
                    } else {
                        exec("tar -xvf " . $temp_location . $endpoint_package . " -C " . $temp_location);
                        if (!file_exists($this->PHONE_MODULES_PATH . "endpoint")) {
                            mkdir($this->PHONE_MODULES_PATH . "endpoint");
                        }

                        //TODO: Automate this somehow...
                        rename($temp_location . "setup.php", $this->PHONE_MODULES_PATH . "autoload.php");
                        rename($temp_location . "endpoint/base.php", $this->PHONE_MODULES_PATH . "endpoint/base.php");
                        rename($temp_location . "endpoint/global_template_data.json", $this->PHONE_MODULES_PATH . "endpoint/global_template_data.json");
                        $sql = "UPDATE endpointman_global_vars SET value = '" . $endpoint_last_mod . "' WHERE var_name = 'endpoint_vers'";
                        sql($sql);
                    }
                }
				
                $out = $temp['data']['brands'];
                //Assume that if we can't connect and find the master.xml file then why should we try to find every other file.
                if ($master_result) {
                	$sql = 'SELECT * FROM  endpointman_brand_list WHERE id > 0';
                    $row = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
                    foreach ($out as $data) {

                        $local = sql("SELECT local FROM endpointman_brand_list WHERE  directory =  '" . $data['directory'] . "'", 'getOne');
                        if (!$local) {
                        	if ($echomsg == true) {
                        		out(sprintf(_("Update Brand (%s):"), $data['name']));
                        		$result = $this->system->download_file_with_progress_bar($this->UPDATE_PATH . $data['directory'] . "/" . $data['directory'] . ".json", $this->PHONE_MODULES_PATH . "endpoint/" . $data['directory'] . "/brand_data.json");
                        	} else {
                        		$result = $this->system->download_file($this->UPDATE_PATH . $data['directory'] . "/" . $data['directory'] . ".json", $this->PHONE_MODULES_PATH . "endpoint/" . $data['directory'] . "/brand_data.json");
                        	}
                            if (!$result) {
                            	$error['brand_update_check'] = sprintf(_("Not able to connect to repository. Using local brand [%s] file instead."), $data['name']);
                            	if ($echomsg == true ) {
                            		out($error['brand_update_check']);
                            	}
                            }
                        }

                        if (file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $data['directory'] . "/brand_data.json")) {
                            $temp = $this->file2json($this->PHONE_MODULES_PATH . "endpoint/" . $data['directory'] . "/brand_data.json");
                            $temp = $temp['data']['brands'];
							if (array_key_exists('oui_list', $temp)) {
                            	foreach ($temp['oui_list'] as $oui) {
									$sql = "REPLACE INTO endpointman_oui_list (`oui`, `brand`, `custom`) VALUES ('" . $oui . "', '" . $temp['brand_id'] . "', '0')";
                               		sql($sql);
								}
							}
                            $brand_name = $temp['directory'];
                            $version[$brand_name] = $temp['last_modified'];
                            $last_mod = "";
                            foreach ($temp['family_list'] as $list) {
                                $last_mod = max($last_mod, $list['last_modified']);
                            }
                            $last_mod = max($last_mod, $version[$brand_name]);
                            $version[$brand_name] = $last_mod;
                            
							if (!($this->system->arraysearchrecursive($brand_name, $row, 'directory'))) 
							{
								$sql = 'SELECT directory FROM endpointman_brand_list where id = "'.$temp['brand_id'].'"';
								$datoif = sql($sql, 'getOne');
								if ($datoif != "") {
									$error['brand_update_id_exist_other_brand'] = sprintf(_("You can not add the mark (%s) as the ID (%d) already exists in the database!"), $temp['name'], $temp['brand_id']);
									if ($echomsg == true ) {
										out($error['brand_update_id_exist_other_brand']);
									}
								}
								else {
                                	$sql = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver) VALUES ('" . $temp['brand_id'] . "', '" . $temp['name'] . "', '" . $temp['directory'] . "', '" . $version[$brand_name] . "')";
                                	sql($sql);
								}
                            } else {
                                //in database already!
                            }
                        } else {
                        	$error['brand_update_check_local_file'] = sprintf(_("Error: No Local File for %s !"), $data['name'])."<br />"._("Learn how to manually upload packages here (it's easy!):")."<a href='http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload' target='_blank'>"._("Click Here!")."</a>";
                        	if ($echomsg == true ) {
                        		out($error['brand_update_check_local_file']);
                        	}
                        }
                    }

                    foreach ($row as $ava_brands) {
						$key = $this->system->arraysearchrecursive($ava_brands['directory'], $out, 'directory');
                        if ($key === FALSE) {
							$this->remove_brand($ava_brands['id']);
                        } else {
                            $key = $key[0];
                            $brand_name = $ava_brands['directory'];
                            //TODO: This seems old
                            if ($ava_brands['cfg_ver'] < $version[$brand_name]) {
                                $out[$key]['update'] = 1;
                                $out[$key]['update_vers'] = $version[$brand_name];
                            } else {
                                $out[$key]['update'] = NULL;
                            }
                        }
                    }
                } else {
                	$error['brand_update_check_master_file'] = _("Error: Aborting Brand Downloads. Can't Get Master File, Assuming Timeout Issues!")."<br />"._("Learn how to manually upload packages here (it's easy!):")."<a href='http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload' target='_blank'>"._("Click Here!")."</a>";
                	if ($echomsg == true ) {
                		out($error['brand_update_check_local_file']);
                	}
                }
                return $out;
            } else {
            	$error['remote_server'] = _("Error: The Remote Server Is Currently Syncing With the Master Server, Please try again later");
            	if ($echomsg == true ) {
            		out($error['remote_server']);
            	}
            }
        } else {
            $o = getcwd();
            chdir(dirname($this->PHONE_MODULES_PATH));
            $path = $this->has_git();
            exec($path . ' git pull', $output);
            //exec($path . ' git checkout master', $output); //Why am I doing this?
            chdir($o);
            $temp = $this->file2json($this->PHONE_MODULES_PATH . 'endpoint/master.json');
            $endpoint_package = $temp['data']['package'];
            $endpoint_last_mod = $temp['data']['last_modified'];

            $sql = "UPDATE endpointman_global_vars SET value = '" . $endpoint_last_mod . "' WHERE var_name = 'endpoint_vers'";
            sql($sql);

            $out = $temp['data']['brands'];
            $row = sql('SELECT * FROM  endpointman_brand_list WHERE id > 0', 'getAll', DB_FETCHMODE_ASSOC);

            foreach ($out as $data) {
                $temp = $this->file2json($this->PHONE_MODULES_PATH . 'endpoint/' . $data['directory'] . '/brand_data.json');
                if (key_exists('directory', $temp['data']['brands'])) {

                    //Pull in all variables
                    $directory = $temp['data']['brands']['directory'];
                    $brand_name = $temp['data']['brands']['name'];
                    $brand_id = $temp['data']['brands']['brand_id'];
                    $brand_version = $temp['data']['brands']['last_modified'];

                    $b_data = sql("SELECT id FROM endpointman_brand_list WHERE id = '" . $brand_id . "'", 'getOne');
                    if ($b_data) {
                        $sql = "UPDATE endpointman_brand_list SET local = '1', name = '" . $brand_name . "', cfg_ver = '" . $brand_version . "', installed = 1, hidden = 0 WHERE id = " . $brand_id;
                        sql($sql);
                    } else {
                        $sql = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver, local, installed) VALUES ('" . $brand_id . "', '" . $brand_name . "', '" . $directory . "', '" . $brand_version . "', '1', '1')";
                        sql($sql);
                    }

                    $last_mod = "";
                    foreach ($temp['data']['brands']['family_list'] as $family_list) {
                        $last_mod = max($last_mod, $family_list['last_modified']);

                        $family_line_xml = $this->file2json($this->PHONE_MODULES_PATH . '/endpoint/' . $directory . '/' . $family_list['directory'] . '/family_data.json');
                        $family_line_xml['data']['last_modified'] = isset($family_line_xml['data']['last_modified']) ? $family_line_xml['data']['last_modified'] : '';

                        /* DONT DO THIS YET
                          $require_firmware = NULL;
                          if ((key_exists('require_firmware', $family_line_xml['data'])) && ($remote) && ($family_line_xml['data']['require_firmware'] == "TRUE")) {
                          echo "Firmware Requirment Detected!..........<br/>";
                          $this->install_firmware($family_line_xml['data']['id']);
                          }
                         *
                         */

                        $data = sql("SELECT id FROM endpointman_product_list WHERE id='" . $brand_id . $family_line_xml['data']['id'] . "'", 'getOne');
                        $short_name = preg_replace("/\[(.*?)\]/si", "", $family_line_xml['data']['name']);
                        if ($data) {
                            $sql = "UPDATE endpointman_product_list SET short_name = '" . $short_name . "', long_name = '" . $family_line_xml['data']['name'] . "', cfg_ver = '" . $family_line_xml['data']['version'] . "', config_files='" . $family_line_xml['data']['configuration_files'] . "' WHERE id = '" . $brand_id . $family_line_xml['data']['id'] . "'";
                        } else {
                            $sql = "INSERT INTO endpointman_product_list (`id`, `brand`, `short_name`, `long_name`, `cfg_dir`, `cfg_ver`, `config_files`, `hidden`) VALUES ('" . $brand_id . $family_line_xml['data']['id'] . "', '" . $brand_id . "', '" . $short_name . "', '" . $family_line_xml['data']['name'] . "', '" . $family_line_xml['data']['directory'] . "', '" . $family_line_xml['data']['last_modified'] . "','" . $family_line_xml['data']['configuration_files'] . "', '0')";
                        }
                        sql($sql);

                        foreach ($family_line_xml['data']['model_list'] as $model_list) {
                            $template_list = implode(",", $model_list['template_data']);

                            $m_data = sql("SELECT id FROM endpointman_model_list WHERE id='" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "'", 'getOne');
                            if ($m_data) {
                                $sql = "UPDATE endpointman_model_list SET max_lines = '" . $model_list['lines'] . "', model = '" . $model_list['model'] . "', template_list = '" . $template_list . "' WHERE id = '" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "'";
                            } else {
                                $sql = "INSERT INTO endpointman_model_list (`id`, `brand`, `model`, `max_lines`, `product_id`, `template_list`, `enabled`, `hidden`) VALUES ('" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "', '" . $brand_id . "', '" . $model_list['model'] . "', '" . $model_list['lines'] . "', '" . $brand_id . $family_line_xml['data']['id'] . "', '" . $template_list . "', '0', '0')";
                            }
                            sql($sql);

							if (!$this->sync_model($brand_id . $family_line_xml['data']['id'] . $model_list['id'], $errsync_modal)) {
								$error['sync_module_error'] = sprintf(_("Error: System Error in Sync Model [%s] Function, Load Failure!"), $model_list['model']);
								if ($echomsg == true ) {
									out($error['sync_module_error']);
									foreach ($errsync_modal as $v) {
										out($v);
									}
								}
                            }
                            unset($errsync_modal);
                        }
                        //Phone Models Move Here
                        $family_id = $brand_id . $family_line_xml['data']['id'];
                        $sql = "SELECT * FROM endpointman_model_list WHERE product_id = " . $family_id;
                        $products = sql($sql, 'getall', DB_FETCHMODE_ASSOC);
                        foreach ($products as $data) {
							if (!$this->system->arraysearchrecursive($data['model'], $family_line_xml['data']['model_list'], 'model')) {
								if ($echomsg == true ) {
									outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file......"), $data['model']));
								}
                                
                                $model_name = $data['model'];
                                $sql = 'DELETE FROM endpointman_model_list WHERE id = ' . $data['id'];
                                sql($sql);
                                $sql = "SELECT id FROM endpointman_model_list WHERE model LIKE '" . $model_name . "'";
                                $new_model_id = sql($sql, 'getOne');
                                if ($new_model_id) {
                                    $sql = "UPDATE  endpointman_mac_list SET  model =  '" . $new_model_id . "' WHERE  model = '" . $data['id'] . "'";
                                } else {
                                    $sql = "UPDATE  endpointman_mac_list SET  model =  '0' WHERE  model = '" . $data['id'] . "'";
                                }
                                sql($sql);
                                out (_("Done!"));
                            }
                        }
                    }
                    foreach ($temp['data']['brands']['oui_list'] as $oui) {
                        $sql = "REPLACE INTO endpointman_oui_list (`oui`, `brand`, `custom`) VALUES ('" . $oui . "', '" . $brand_id . "', '0')";
                        sql($sql);
                    }
                }
            }
        }
    }
	
	 /**
     * Sync the XML files (incuding all template files) from the hard drive with the database
     * @param int $model Model ID
     * @return boolean True on sync completed. False on sync failed
     */
    function sync_model($model, &$error = array()) {
        if ((!empty($model)) OR ($model > 0)) {
            $sql = "SELECT * FROM  endpointman_model_list WHERE id='" . $model . "'";
            $model_row = sql($sql, 'getrow', DB_FETCHMODE_ASSOC);

            $sql = "SELECT * FROM  endpointman_product_list WHERE id='" . $model_row['product_id'] . "'";
            $product_row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

            $sql = "SELECT * FROM  endpointman_brand_list WHERE id=" . $model_row['brand'];
            $brand_row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

            
            $path_brand_dir = $this->PHONE_MODULES_PATH . '/endpoint/' . $brand_row['directory'];
            $path_brand_dir_cfg = $path_brand_dir . '/' . $product_row['cfg_dir'];
            $path_brand_dir_cfg_json = $path_brand_dir_cfg . '/family_data.json';
            
            if (!file_exists($path_brand_dir)) {
            	$error['sync_model'] = sprintf(_("Brand Directory '%s' Doesn't Exist! (%s)"), $brand_row['directory'], $path_brand_dir);
                return(FALSE);
            }
            if (!file_exists($path_brand_dir_cfg)) {
            	$error['sync_model'] = sprintf(_("Product Directory '%s' Doesn't Exist! (%s)"), $product_row['cfg_dir'], $path_brand_dir_cfg);
                return(FALSE);
            }
            if (!file_exists($path_brand_dir_cfg_json)) {
                $error['sync_model'] = sprintf(_("File 'family_data.json' Doesn't exist in directory: %s"), $path_brand_dir_cfg);
                return(FALSE);
            }
            $family_line_json = $this->file2json($path_brand_dir_cfg_json);
            
            
            //TODO: Add local file checks to avoid slow reloading on PHP < 5.3
			$key = $this->system->arraysearchrecursive($model_row['model'], $family_line_json['data']['model_list'], 'model');
            if ($key === FALSE) {
                $error['sync_model'] = "Can't locate model in family JSON file";
                return(FALSE);
            } else {
                $template_list = implode(",", $family_line_json['data']['model_list'][$key[0]]['template_data']);
                $template_list_array = $family_line_json['data']['model_list'][$key[0]]['template_data'];
            }
            $maxlines = $family_line_json['data']['model_list'][$key[0]]['lines'];

            $sql = "UPDATE endpointman_model_list SET max_lines = '" . $maxlines . "', template_list = '" . $template_list . "' WHERE id = '" . $model . "'";
            sql($sql);

            $version = isset($family_line_json['data']['last_modified']) ? $family_line_json['data']['last_modified'] : '';
            $long_name = $family_line_json['data']['name'];
            $short_name = preg_replace("/\[(.*?)\]/si", "", $family_line_json['data']['name']);
            $configuration_files = $family_line_json['data']['configuration_files'];

            $sql = "UPDATE endpointman_product_list SET long_name = '" . str_replace("'", "''", $long_name) . "', short_name = '" . str_replace("'", "''", $short_name) . "' , cfg_ver = '" . $version . "' WHERE id = '" . $product_row['id'] . "'";
            sql($sql);

            $template_data_array = array();
            $template_data_array = $this->merge_data($this->PHONE_MODULES_PATH . '/endpoint/' . $brand_row['directory'] . '/' . $product_row['cfg_dir'] . '/', $template_list_array);

            $sql = "UPDATE endpointman_model_list SET template_data = '" . serialize($template_data_array) . "' WHERE id = '" . $model . "'";
            sql($sql);
            return(TRUE);
        } else {
            return(FALSE);
        }
    }
	
	function sys_get_temp_dir() {
        if (!empty($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        }
        if (!empty($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        }
        if (!empty($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }
        $tempfile = tempnam(uniqid(rand(), TRUE), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
            return realpath(dirname($tempfile));
        }
    }

    
    /**
     * Install Firmware for the specified Product Line
     * @param <type> $product_id Product ID
     */
    function install_firmware($product_id) {
    	out(_("Installa frimware... "));
    	
        $temp_directory = $this->sys_get_temp_dir() . "/epm_temp/";
        $sql = 'SELECT endpointman_product_list.*, endpointman_brand_list.directory FROM endpointman_product_list, endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = ' . $product_id;
        $row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
        $json_data = $this->file2json($this->PHONE_MODULES_PATH . "endpoint/" . $row['directory'] . "/" . $row['cfg_dir'] . "/family_data.json");
		
        if ((! isset($json_data['data']['firmware_ver'])) OR (empty($json_data['data']['firmware_ver']))) {
        	out (_("Error: The version of the firmware package is blank!"));
        	return false;
        }
        
        if ((! isset($json_data['data']['firmware_pkg'])) OR (empty($json_data['data']['firmware_pkg'])) OR ($json_data['data']['firmware_pkg'] == "NULL")) {
        	out (_("Error: The package name of the firmware to be downloaded is Null or blank!"));
        	return false;
        }
        
        if ($json_data['data']['firmware_ver'] > $row['firmware_vers']) {
            if (!file_exists($temp_directory)) {
                mkdir($temp_directory);
            }
            $md5_xml = $json_data['data']['firmware_md5sum'];
            $firmware_pkg = $json_data['data']['firmware_pkg'];
			
            if (file_exists($temp_directory . $firmware_pkg)) {
                $md5_pkg = md5_file($temp_directory . $firmware_pkg);
                if ($md5_xml == $md5_pkg) {
					out(_("Skipping download, updated local version..."));
                } else {
					out(_("Downloading firmware..."));
                    if (! $this->system->download_file_with_progress_bar($this->UPDATE_PATH . $row['directory'] . "/" . $firmware_pkg, $temp_directory . $firmware_pkg)) {
						out(_("Error download frimware package!"));
						return false;
					}
                    $md5_pkg = md5_file($temp_directory . $firmware_pkg);
                }
            } else {
				out(_("Downloading firmware..."));
                if (! $this->system->download_file_with_progress_bar($this->UPDATE_PATH . $row['directory'] . "/" . $firmware_pkg, $temp_directory . $firmware_pkg)) {
					out(_("Error download frimware package!"));
					return false;
				}
                $md5_pkg = md5_file($temp_directory . $firmware_pkg);
            }
			
			outn(_("Checking MD5sum of Package... "));
            if ($md5_xml == $md5_pkg) {
				out(_("Matches!"));
				
                if (file_exists($temp_directory . $row['directory'] . "/" . $row['cfg_dir'] . "/firmware")) {
                    $this->system->rmrf($temp_directory . $row['directory'] . "/" . $row['cfg_dir'] . "/firmware");
                }
                mkdir($temp_directory . $row['directory'] . "/" . $row['cfg_dir'] . "/firmware", 0777, TRUE);
				
				out(_("Installing Firmware..."));
				//TODO: AÑADIR VALIDACION EXTRACCION CORRECTA
                exec("tar -xvf " . $temp_directory . $firmware_pkg . " -C " . $temp_directory . $row['directory'] . "/" . $row['cfg_dir']);
                $i = 0;
                foreach (glob($temp_directory . $row['directory'] . "/" . $row['cfg_dir'] . "/firmware/*") as $filename) {
                    $file = basename($filename);
                    $list[$i] = $file;
                    if (!@copy($filename, $this->configmod->get('config_location') . $file)) {
                    	out(sprintf(_("- Failed To Copy %s!"), $file));
                        $copy_error = TRUE;
                    } elseif ($this->configmod->get('debug')) {
						out(sprintf(_("- Copied %s to %s."), $file, $this->configmod->get('config_location')));
                    }
                    $i++;
                }

                $this->system->rmrf($temp_directory . $row['directory']);
                $list = implode(",", $list);
                $sql = "UPDATE endpointman_product_list SET firmware_vers = '" . $json_data['data']['firmware_ver'] . "', firmware_files = '" . $list . "' WHERE id = " . $row['id'];
                sql($sql);
				
                if (isset($copy_error)) {
					out(_("Copy Error Detected! Aborting Install!"));
                    $this->remove_firmware($product_id);
					out(_("Info: Please Check Directory/Permissions!"));
                } 
				else {
					out(_("Done!"));
                }
            } 
			else {
				out(_("Firmware MD5 didn't match!"));
            }
        } 
		else {
			out(_("Your Firmware is already up to date."));
        }
    }
	
	/**
     * Remove firmware from the Hard Drive
     * @param int $id Product ID
     */
    function remove_firmware($id) {
		outn(_("Uninstalla frimware... "));
		
        $sql = "SELECT firmware_files FROM  endpointman_product_list WHERE  id ='" . $id . "'";
        $files = sql($sql, 'getOne');

        $file_list = explode(",", $files);
        $i = 0;
        foreach ($file_list as $file) {
			if (trim($file) == "") { continue; }
            if (! file_exists($this->configmod->get('config_location') . $file)) { continue; }
			if (! is_file($this->configmod->get('config_location') . $file)) { continue; }
					unlink($this->configmod->get('config_location') . $file);
        }
        $sql = "UPDATE endpointman_product_list SET firmware_files = '', firmware_vers = '' WHERE id = '" . $id . "'";
        sql($sql);
		
		out(_("Done!"));
    }
	
	/**
     * Remove the brand
     * @param int $id Brand ID
     */
    function remove_brand($id=NULL, $remove_configs=FALSE, $force=FALSE) {
		out(_("Uninstalla Brand..."));
		
        if (!$this->configmod->get('use_repo')) {
            $sql = "SELECT id, firmware_vers FROM endpointman_product_list WHERE brand = '" . $id . "'";
            $products = sql($sql, 'getall', DB_FETCHMODE_ASSOC);
			
            foreach ($products as $data) {
                if ($data['firmware_vers'] != "") {
                    $this->remove_firmware($data['id']);
                }
            }
			
			$sql = "SELECT directory FROM endpointman_brand_list WHERE id = '" . $id . "'";
            $brand_dir = sql($sql, 'getOne');
            $this->system->rmrf($this->PHONE_MODULES_PATH . "endpoint/" . $brand_dir);

            $sql = "DELETE FROM endpointman_model_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_product_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_oui_list WHERE brand = '" . $id . "'";
            sql($sql);

            $this->system->rmrf($this->PHONE_MODULES_PATH . $brand_dir);
            $sql = "DELETE FROM endpointman_brand_list WHERE id = '" . $id . "'";
            sql($sql);
			
			out(_("All Done!"));
        } 
		elseif ($force) {
			$sql = "SELECT directory FROM endpointman_brand_list WHERE id = '" . $id . "'";
            $brand_dir = sql($sql, 'getOne');

            $sql = "DELETE FROM endpointman_model_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_product_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_oui_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_brand_list WHERE id = '" . $id . "'";
            sql($sql);
			
			out(_("Done!"));
        } 
		else {
			out(_("Error: Not allowed in repo mode!!"));
        }
    }
	
	 /**
     * This will download the xml & brand package remotely
     * @param integer $id Brand ID
     */
    function download_brand($id) {
    	out(_("Install/Update Brand..."));
        if (!$this->configmod->get('use_repo')) {
            $temp_directory = $this->sys_get_temp_dir() . "/epm_temp/";
			
			if (!file_exists($temp_directory)) {
				out(_("Creating EPM temp directory"));
				if (! mkdir($temp_directory)) {
					out(sprintf(_("Error: Failed to create the directory '%s', please Check Permissions!"), $temp_directory));
					return false;
				}
			}
			
			outn(_("Downloading Brand JSON..... "));
            $row = sql('SELECT * FROM  endpointman_brand_list WHERE id =' . $id, 'getAll', DB_FETCHMODE_ASSOC);
            $result = $this->system->download_file($this->UPDATE_PATH . $row[0]['directory'] . "/" . $row[0]['directory'] . ".json", $this->PHONE_MODULES_PATH . "endpoint/" . $row[0]['directory'] . "/brand_data.json");
            if ($result) {
            	out(_("Done!"));

                $temp = $this->file2json($this->PHONE_MODULES_PATH . 'endpoint/' . $row[0]['directory'] . '/brand_data.json');
                $package = $temp['data']['brands']['package'];

				out(_("Downloading Brand Package..."));
                if ($this->system->download_file_with_progress_bar($this->UPDATE_PATH . $row[0]['directory'] . '/' . $package, $temp_directory . $package)) 
                {
					if (file_exists($temp_directory . $package)) {
						$md5_xml = $temp['data']['brands']['md5sum'];
						$md5_pkg = md5_file($temp_directory . $package);
						
						outn(_("Checking MD5sum of Package.... "));
						if ($md5_xml == $md5_pkg) {
							out(_("Done!"));
							
							outn(_("Extracting Tarball........ "));
							//TODO: PENDIENTE VALIDAR SI DA ERROR LA DESCOMPRESION
							exec("tar -xvf " . $temp_directory . $package . " -C " . $temp_directory);
							out(_("Done!"));
							
							//Update File in the temp directory
							copy($this->PHONE_MODULES_PATH . 'endpoint/' . $row[0]['directory'] . '/brand_data.json', $temp_directory . $row[0]['directory'] . '/brand_data.json');
							$this->update_brand($row[0]['directory'], TRUE);
						} else {
							out(_("MD5 Did not match!"));
							out(sprintf(_("MD5 XML: %s"), $md5_xml));
							out(sprintf(_("MD5 PKG: %s"), $md5_pkg));
						}
					} else {
						out(_("Error: Can't Find Downloaded File!"));
					}
				} else {
					out(_("Error download Brand package!"));
				}
            } else {
            	out(_("Error!"));
				out(_("Error Connecting to the Package Repository. Module not installed. Please Try again later."));
				out(_("You Can Also Manually Update The Repository By Downloading Files here: <a href='http://www.provisioner.net/releases3' target='_blank'> Release Repo </a>"));
				out(_("Then Use Manual Upload in Advanced Settings."));
            }
        } else {
			out(_("Error: Installing brands is disabled while in repo mode!"));
        }
    }

    /**
     * This will install or updated a brand package (which is the same thing to this)
     * Still needs way to determine when models move...perhaps another function?
     */
    function update_brand($package, $remote=TRUE) {
    	out(sprintf(_("Update Brand %s ... "), $package));
		
		$temp_directory = $this->sys_get_temp_dir() . "/epm_temp/";
if ($this->configmod->get('debug')) echo format_txt(_("Processing %_PATH_%/brand_data.json..."), "",array("%_PATH_%" => $temp_directory.$package));
		
        if (file_exists($temp_directory . $package . '/brand_data.json')) {
            $temp = $this->file2json($temp_directory . $package . '/brand_data.json');
            if (key_exists('directory', $temp['data']['brands'])) {
				out(_("Appears to be a valid Provisioner.net JSON file.....Continuing"));
                //Pull in all variables
                $directory = $temp['data']['brands']['directory'];
                $brand_name = $temp['data']['brands']['name'];
                $brand_id = $temp['data']['brands']['brand_id'];
                $brand_version = $temp['data']['brands']['last_modified'];

                //create directory structure and move files
                out(sprintf(_("Creating Directory Structure for Brand '%s' and Moving Files..."), $brand_name));
				
                if (!file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $directory)) {
                    mkdir($this->PHONE_MODULES_PATH . "endpoint/" . $directory);
                }

                $dir_iterator = new \RecursiveDirectoryIterator($temp_directory . $directory . "/");
                $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $file) {
                    if (is_dir($file)) {
                        $dir = str_replace($temp_directory . $directory . "/", "", $file);
                        if (!file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $directory . "/" . $dir)) {
                            mkdir($this->PHONE_MODULES_PATH . "endpoint/" . $directory . "/" . $dir, 0775, TRUE);
//echo ".";
                        }
                    } else {
                        if ((basename($file) != "brand_data.json") OR (!$remote)) {
                            $dir = str_replace($temp_directory . $directory . "/", "", $file);
                            $stats = rename($file, $this->PHONE_MODULES_PATH . "endpoint/" . $directory . "/" . $dir);
                            if ($stats === FALSE) {
                            	out(sprintf(_("- Error Moving %s!"), basename($file)));
                            }
                            chmod($this->PHONE_MODULES_PATH . "endpoint/" . $directory . "/" . $dir, 0775);
//echo ".";
                        }
                    }
                }
                out(_("All Done!"));

                if ($remote) {
                    $local = 0;
                } else {
                    $local = 1;
                }

                $b_data = sql("SELECT id FROM endpointman_brand_list WHERE id = '" . $brand_id . "'", 'getOne');
                if ($b_data) {
                	outn(sprintf(_("Updating %s brand data ..."), $brand_name));
                    $sql = "UPDATE endpointman_brand_list SET local = '" . $local . "', name = '" . $brand_name . "', cfg_ver = '" . $brand_version . "', installed = 1, hidden = 0 WHERE id = " . $brand_id;
                    sql($sql);
                    out(_("Done!"));
                } else {
                	outn(sprintf(_("Inserting %s brand data ..."), $brand_name));
					$sql = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver, local, installed) VALUES ('" . $brand_id . "', '" . $brand_name . "', '" . $directory . "', '" . $brand_version . "', '" . $local . "', '1')";
                    sql($sql);
                    out(_("Done!"));
                }

                $last_mod = "";
                foreach ($temp['data']['brands']['family_list'] as $family_list) {
					out(_("Updating Family Lines ..."));

                    $last_mod = max($last_mod, $family_list['last_modified']);

                    $family_line_xml = $this->file2json($this->PHONE_MODULES_PATH . '/endpoint/' . $directory . '/' . $family_list['directory'] . '/family_data.json');
                    $family_line_xml['data']['last_modified'] = isset($family_line_xml['data']['last_modified']) ? $family_line_xml['data']['last_modified'] : '';

                    $require_firmware = NULL;
                    if ((key_exists('require_firmware', $family_line_xml['data'])) && ($remote) && ($family_line_xml['data']['require_firmware'] == "TRUE")) {
						out(_("Firmware Requirment Detected!.........."));
						$this->install_firmware($family_line_xml['data']['id']);
                    }

                    $data = sql("SELECT id FROM endpointman_product_list WHERE id='" . $brand_id . $family_line_xml['data']['id'] . "'", 'getOne');
                    $short_name = preg_replace("/\[(.*?)\]/si", "", $family_line_xml['data']['name']);
					
					if ($data) {
						if ($this->configmod->get('debug')) echo "-Updating Family ".$short_name."<br/>";
                        $sql = "UPDATE endpointman_product_list SET short_name = '" . str_replace("'", "''", $short_name) . "', long_name = '" . str_replace("'", "''", $family_line_xml['data']['name']) . "', cfg_ver = '" . $family_line_xml['data']['version'] . "', config_files='" . $family_line_xml['data']['configuration_files'] . "' WHERE id = '" . $brand_id . $family_line_xml['data']['id'] . "'";
                    } 
					else {
						if ($this->configmod->get('debug')) echo "-Inserting Family ".$short_name."<br/>";
                        $sql = "INSERT INTO endpointman_product_list (`id`, `brand`, `short_name`, `long_name`, `cfg_dir`, `cfg_ver`, `config_files`, `hidden`) VALUES ('" . $brand_id . $family_line_xml['data']['id'] . "', '" . $brand_id . "', '" . str_replace("'", "''", $short_name) . "', '" . str_replace("'", "''", $family_line_xml['data']['name']) . "', '" . $family_line_xml['data']['directory'] . "', '" . $family_line_xml['data']['last_modified'] . "','" . $family_line_xml['data']['configuration_files'] . "', '0')";
                    }
					sql($sql);
					

					if (count($family_line_xml['data']['model_list']) > 0) {
						out(_("-- Updating Model Lines ... "));						
	                    foreach ($family_line_xml['data']['model_list'] as $model_list) {
	                        $template_list = implode(",", $model_list['template_data']);
	
	                        $model_final_id = $brand_id . $family_line_xml['data']['id'] . $model_list['id'];
	                        $sql = 'SELECT id, global_custom_cfg_data, global_user_cfg_data FROM endpointman_mac_list WHERE model = ' . $model_final_id;
	                        $old_data = NULL;
	                        $old_data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	                        foreach ($old_data as $data) {
	                            $global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
	                            if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) {
outn(_("----Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                $new_ari = array();
	                                foreach ($global_custom_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $new_data[$key] = $old_keys['value'];
	                                    } else {
	                                        $breaks = explode("_", $key);
	                                        $new_data["loop|" . $key] = $old_keys[$breaks[2]];
	                                    }
	                                    if (array_key_exists('ari', $old_keys)) {
	                                        $new_ari[$key] = 1;
	                                    }
	                                }
	                                $final_data = array();
	                                $final_data['data'] = $new_data;
	                                $final_data['ari'] = $new_ari;
	                                $final_data = serialize($final_data);
	                                $sql = "UPDATE endpointman_mac_list SET  global_custom_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }
	
	                            $global_user_cfg_data = unserialize($data['global_user_cfg_data']);
	                            $old_check = FALSE;
	                            if (is_array($global_user_cfg_data)) {
	                                foreach ($global_user_cfg_data as $stuff) {
	                                    if (is_array($stuff)) {
	                                        if (array_key_exists('value', $stuff)) {
	                                            $old_check = TRUE;
	                                            break;
	                                        } else {
	                                            break;
	                                        }
	                                    } else {
	                                        break;
	                                    }
	                                }
	                            }
	                            if ((is_array($global_user_cfg_data)) AND ($old_check)) {
outn(_("Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                foreach ($global_user_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $exploded = explode("_", $key);
	                                        $counted = count($exploded);
	                                        $counted = $counted - 1;
	                                        if (is_numeric($exploded[$counted])) {
	                                            $key = "loop|" . $key;
	                                        }
	                                        $new_data[$key] = $old_keys['value'];
	                                    }
	                                }
	                                $final_data = serialize($new_data);
	                                $sql = "UPDATE endpointman_mac_list SET  global_user_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }
	                        }
	                        $old_data = NULL;
	                        $sql = 'SELECT id, global_custom_cfg_data FROM endpointman_template_list WHERE model_id = ' . $model_final_id;
	                        $old_data = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
	                        foreach ($old_data as $data) {
	                            $global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
	                            if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) {
out(_("Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                $new_ari = array();
	                                foreach ($global_custom_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $new_data[$key] = $old_keys['value'];
	                                    } else {
	                                        $breaks = explode("_", $key);
	                                        $new_data["loop|" . $key] = $old_keys[$breaks[2]];
	                                    }
	                                    if (array_key_exists('ari', $old_keys)) {
	                                        $new_ari[$key] = 1;
	                                    }
	                                }
	                                $final_data = array();
	                                $final_data['data'] = $new_data;
	                                $final_data['ari'] = $new_ari;
	                                $final_data = serialize($final_data);
	                                $sql = "UPDATE endpointman_template_list SET  global_custom_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }
	                        }
	
	                        $m_data = sql("SELECT id FROM endpointman_model_list WHERE id='" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "'", 'getOne');
	                        if ($m_data) {
if ($this->configmod->get('debug')) echo format_txt(_("---Updating Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model']));
	                            $sql = "UPDATE endpointman_model_list SET max_lines = '" . $model_list['lines'] . "', model = '" . $model_list['model'] . "', template_list = '" . $template_list . "' WHERE id = '" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "'";
	                        } 
							else {
if ($this->configmod->get('debug')) echo format_txt(_("---Inserting Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model']));
	                            $sql = "INSERT INTO endpointman_model_list (`id`, `brand`, `model`, `max_lines`, `product_id`, `template_list`, `enabled`, `hidden`) VALUES ('" . $brand_id . $family_line_xml['data']['id'] . $model_list['id'] . "', '" . $brand_id . "', '" . $model_list['model'] . "', '" . $model_list['lines'] . "', '" . $brand_id . $family_line_xml['data']['id'] . "', '" . $template_list . "', '0', '0')";
	                        }
	                        sql($sql);
							
	                        //echo "brand_id:".$brand_id. " - family_line_xml:" . $family_line_xml['data']['id'] . "- model_list:" . $model_list['id']."<br>";
	                        if (!$this->sync_model($brand_id . $family_line_xml['data']['id'] . $model_list['id'], $errlog)) {
	                        	out(_("Error: System Error in Sync Model Function, Load Failure!"));
								out(_("Error: ").$errlog['sync_model']);
	                        }
	                        unset ($errlog);
	                    }
					}
                    //END Updating Model Lines................	
                    
                    //Phone Models Move Here
                    $family_id = $brand_id . $family_line_xml['data']['id'];
                    $sql = "SELECT * FROM endpointman_model_list WHERE product_id = " . $family_id;
                    $products = sql($sql, 'getall', DB_FETCHMODE_ASSOC);
                    foreach ($products as $data) {
                        if (!$this->system->arraysearchrecursive($data['model'], $family_line_xml['data']['model_list'], 'model')) {
							outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file ... "), $data['model']));
                            $model_name = $data['model'];
                            $sql = 'DELETE FROM endpointman_model_list WHERE id = ' . $data['id'];
                            sql($sql);
                            $sql = "SELECT id FROM endpointman_model_list WHERE model LIKE '" . $model_name . "'";
                            $new_model_id = sql($sql, 'getOne');
                            if ($new_model_id) {
                                $sql = "UPDATE  endpointman_mac_list SET  model =  '" . $new_model_id . "' WHERE  model = '" . $data['id'] . "'";
                            } else {
                                $sql = "UPDATE  endpointman_mac_list SET  model =  '0' WHERE  model = '" . $data['id'] . "'";
                            }
                            sql($sql);
                            out(_("Done!"));
                        }
                    }
                }
				out(_("All Done!"));
				//END Updating Family Lines
				
				outn(_("Updating OUI list in DB ... "));
				if ((isset($temp['data']['brands']['oui_list'])) AND (count($temp['data']['brands']['oui_list']) > 0))
				{
	                foreach ($temp['data']['brands']['oui_list'] as $oui) {
	                    $sql = "REPLACE INTO endpointman_oui_list (`oui`, `brand`, `custom`) VALUES ('" . $oui . "', '" . $brand_id . "', '0')";
	                    sql($sql);
	                }
				}
				out(_("Done!"));
            } else {
				outn(sprintf(_("Error: Invalid JSON Structure in %s/brand_data.json"), $temp_directory.$package));
            }
        } else {
			out(_("Error: No 'brand_data.xml' file exists!"));
        }

		outn(_("Removing Temporary Files... "));
        $this->system->rmrf($temp_directory . $package);
        out(_("Done!"));
    }
	
	
	/**
     * Returns list of Brands that are installed and not hidden and that have at least one model enabled under them
     * @param integer $selected ID Number of the brand that is supposed to be selected in a drop-down list box
     * @return array Number array used to generate a select box
     */
    function brands_available($selected = NULL, $show_blank=TRUE) {
        $data = $this->eda->all_active_brands();
        if ($show_blank) {
            $temp[0]['value'] = "";
            $temp[0]['text'] = "";
            $i = 1;
        } else {
            $i = 0;
        }
        foreach ($data as $row) {
            $temp[$i]['value'] = $row['id'];
            $temp[$i]['text'] = $row['name'];
            if ($row['id'] == $selected) {
                $temp[$i]['selected'] = TRUE;
            } else {
                $temp[$i]['selected'] = NULL;
            }
            $i++;
        }
        return($temp);
    }
	
	function listTZ($selected) {
        require_once('lib/datetimezone.class.php');
        $data = \DateTimeZone::listIdentifiers();
        $i = 0;
        foreach ($data as $key => $row) {
            $temp[$i]['value'] = $row;
            $temp[$i]['text'] = $row;
            if (strtoupper ($temp[$i]['value']) == strtoupper($selected)) {
                $temp[$i]['selected'] = 1;
            } else {
                $temp[$i]['selected'] = 0;
            }
            $i++;
        }

        return($temp);
    }
	
	function has_git() {
        exec('which git', $output);

        $git = file_exists($line = trim(current($output))) ? $line : 'git';

        unset($output);

        exec($git . ' --version', $output);

        preg_match('#^(git version)#', current($output), $matches);

        return!empty($matches[0]) ? $git : false;
        echo!empty($matches[0]) ? 'installed' : 'nope';
    }
	
	function tftp_check() {
        //create a simple block here incase people have strange issues going on as we will kill http
        //by running this if the server isn't really running!
        $sql = 'SELECT value FROM endpointman_global_vars WHERE var_name = \'tftp_check\'';
        if (sql($sql, 'getOne') != 1) {
            $sql = 'UPDATE endpointman_global_vars SET value = \'1\' WHERE var_name = \'tftp_check\'';
            sql($sql);
            $subject = shell_exec("netstat -luan --numeric-ports");
            if (preg_match('/:69\s/i', $subject)) {
                $rand = md5(rand(10, 2000));
                if (file_put_contents($this->configmod->get('config_location') . 'TEST', $rand)) {
                    if ($this->system->tftp_fetch('127.0.0.1', 'TEST') != $rand) {
                        $this->error['tftp_check'] = 'Local TFTP Server is not correctly configured';
echo 'Local TFTP Server is not correctly configured';
                    }
                    unlink($this->configmod->get('config_location') . 'TEST');
                } else {
                    $this->error['tftp_check'] = 'Unable to write to ' . $this->configmod->get('config_location');
echo 'Unable to write to ' . $this->configmod->get('config_location');
                }
            } else {
                $dis = FALSE;
                if (file_exists('/etc/xinetd.d/tftp')) {
                    $contents = file_get_contents('/etc/xinetd.d/tftp');
                    if (preg_match('/disable.*=.*yes/i', $contents)) {
                        $this->error['tftp_check'] = 'Disabled is set to "yes" in /etc/xinetd.d/tftp. Please fix <br />Then restart your TFTP service';
echo 'Disabled is set to "yes" in /etc/xinetd.d/tftp. Please fix <br />Then restart your TFTP service';
                        $dis = TRUE;
                    }
                }
                if (!$dis) {
                    $this->error['tftp_check'] = 'TFTP Server is not running. <br />See here for instructions on how to install one: <a href="http://wiki.provisioner.net/index.php/Tftp" target="_blank">http://wiki.provisioner.net/index.php/Tftp</a>';
echo 'TFTP Server is not running. <br />See here for instructions on how to install one: <a href="http://wiki.provisioner.net/index.php/Tftp" target="_blank">http://wiki.provisioner.net/index.php/Tftp</a>';
                }
            }
            $sql = 'UPDATE endpointman_global_vars SET value = \'0\' WHERE var_name = \'tftp_check\'';
            sql($sql);
        } else {
            $this->error['tftp_check'] = 'TFTP Server check failed on last past. Skipping';
echo 'TFTP Server check failed on last past. Skipping';
        }
    }
	
	
    /**
     * Used to send sample configurations to provisioner.net
     * NOTE: The user has to explicitly click a link that states they are sending the configuration to the project
     * We don't take configs on our own accord!!
     * @param <type> $brand Brand Directory
     * @param <type> $product Product Directory
     * @param <type> $orig_name The file's original name we are sending
     * @param <type> $data The config file's data
     */
    function submit_config($brand, $product, $orig_name, $data) {
    	$posturl = 'http://www.provisioner.net/submit_config.php';
    
    	$fp = fopen($this->LOCAL_PATH . 'data.txt', 'w');
    	fwrite($fp, $data);
    	fclose($fp);
    	$file_name_with_full_path = $this->LOCAL_PATH . "data.txt";
    
    	$postvars = array('brand' => $brand, 'product' => $product, 'origname' => htmlentities(addslashes($orig_name)), 'file_contents' => '@' . $file_name_with_full_path);
    
    	$ch = curl_init($posturl);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL, probably not needed
    	$Rec_Data = curl_exec($ch);
    
    	ob_start();
    	header("Content-Type: text/html");
    	$Final_Out = ob_get_clean();
    	curl_close($ch);
    	unlink($file_name_with_full_path);
    
    	return($Final_Out);
    }
	
    
    /**
     * Fixes the display are special strings so we can visible see them instead of them being transformed
     * @param string $contents a string of course
     * @return string fixed string
     */
    function display_htmlspecialchars($contents) {
    	$contents = str_replace("&amp;", "&amp;amp;", $contents);
    	$contents = str_replace("&lt;", "&amp;lt;", $contents);
    	$contents = str_replace("&gt;", "&amp;gt;", $contents);
    	$contents = str_replace("&quot;", "&amp;quot;", $contents);
    	$contents = str_replace("&#039;", "&amp;#039;", $contents);
    	return($contents);
    }
    
    
    
    function add_device($mac, $model, $ext, $template=NULL, $line=NULL, $displayname=NULL) {
    	$mac = $this->mac_check_clean($mac);
    	if ($mac) {
    		if (empty($model)) {
$this->error['add_device'] = _("You Must Select A Model From the Drop Down") . "!";
    			return(FALSE);
    		} elseif (empty($ext)) {
$this->error['add_device'] = _("You Must Select an Extension/Device From the Drop Down") . "!";
    			return(FALSE);
    		} else {
    			if ($this->sync_model($model)) {
    				$sql = "SELECT id,template_id FROM endpointman_mac_list WHERE mac = '" . $mac . "'";
    				$dup = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    
    				if ($dup) {
    					if (!isset($template)) {
    						$template = $dup['template_id'];
    					}
    
    					$sql = "UPDATE endpointman_mac_list SET model = " . $model . ", template_id =  " . $template . " WHERE id = " . $dup['id'];
    					sql($sql);
						$return = $this->add_line($dup['id'], $line, $ext);
    					if ($return) {
    						return($return);
    					} else {
    						return(FALSE);
    					}
    				} else {
    					if (!isset($template)) {
    						$template = 0;
    					}
    
    					$sql = "SELECT mac_id FROM endpointman_line_list WHERE ext = " . $ext;
    					$used = sql($sql, 'getOne');
    
						if (($used) AND (! $this->configmod->get('show_all_registrations'))) {
$this->error['add_device'] = "You can't assign the same user to multiple devices!";
    						return(FALSE);
    					}
    
    					if (!isset($displayname)) {
    						$sql = 'SELECT description FROM devices WHERE id = ' . $ext;
    						$name = & sql($sql, 'getOne');
    					} else {
    						$name = $displayname;
    					}
    
    					$sql = 'SELECT endpointman_product_list. * , endpointman_model_list.template_data, endpointman_brand_list.directory FROM endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_model_list.id =  \'' . $model . '\' AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id';
    					$row = & sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    
    					$sql = "INSERT INTO `endpointman_mac_list` (`mac`, `model`, `template_id`) VALUES ('" . $mac . "', '" . $model . "', '" . $template . "')";
    					sql($sql);
    
    					$sql = 'SELECT last_insert_id()';
    					$ext_id = & sql($sql, 'getOne');
    
    					if (empty($line)) {
    						$line = 1;
    					}
    
    					$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $ext_id . "', '" . $ext . "', '" . $line . "', '" . addslashes($name) . "')";
    					sql($sql);
    
$this->message['add_device'][] = "Added " . $name . " to line " . $line;
    					return($ext_id);
    				}
    			} else {
$this->error['Sync_Model'] = _("Invalid Model Selected, Can't Sync System") . "!";
    				return(FALSE);
    			}
    		}
    	} else {
$this->error['add_device'] = _("Invalid MAC Address") . "!";
    		return(FALSE);
    	}
    }
    
    
    function add_line($mac_id, $line=NULL, $ext=NULL, $displayname=NULL) {
    	if ((!isset($line)) AND (!isset($ext))) {
    		if ($this->linesAvailable(NULL, $mac_id)) {
    			if ($this->eda->all_unused_registrations()) {
    				$sql = 'SELECT * FROM endpointman_line_list WHERE mac_id = ' . $mac_id;
    				$lines_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
    
    				foreach ($lines_list as $row) {
    					$sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
    					$name = sql($sql, 'getOne');
    
    					$sql = "UPDATE endpointman_line_list SET line = '" . $row['line'] . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
    					sql($sql);
    				}
    
    				$reg = array_values($this->display_registration_list());
    				$lines = array_values($this->linesAvailable(NULL, $mac_id));
    
    				$sql = "SELECT description FROM devices WHERE id = " . $reg[0]['value'];
    				$name = $this->eda->sql($sql, 'getOne');
    
    				$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $reg[0]['value'] . "', '" . $lines[0]['value'] . "', '" . addslashes($name) . "')";
    				$this->eda->sql($sql);
    
$this->message['add_line'] = "Added '<i>" . $name . "</i>' to line '<i>" . $lines[0]['value'] . "</i>' on device '<i>" . $reg[0]['value'] . "</i>' <br/> Configuration Files will not be Generated until you click Save!";
    				return($mac_id);
    			} else {
$this->error['add_line'] = _("No Devices/Extensions Left to Add") . "!";
    				return(FALSE);
    			}
    		} else {
$this->error['add_line'] = _("No Lines Left to Add") . "!";
    			return(FALSE);
    		}
    	} elseif ((!isset($line)) AND (isset($ext))) {
    		if ($this->linesAvailable(NULL, $mac_id)) {
    			if ($this->eda->all_unused_registrations()) {
    				$lines = array_values($this->linesAvailable(NULL, $mac_id));
    
    				$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $ext . "', '" . $lines[0]['value'] . "', '" . addslashes($displayname) . "')";
    				sql($sql);
    
$this->message['add_line'] = "Added '<i>" . $name . "</i>' to line '<i>" . $lines[0]['value'] . "</i>' on device '<i>" . $reg[0]['value'] . "</i>' <br/> Configuration Files will not be Generated until you click Save!";
    				return($mac_id);
    			} else {
$this->error['add_line'] = _("No Devices/Extensions Left to Add") . "!";
    				return(FALSE);
    			}
    		} else {
$this->error['add_line'] = _("No Lines Left to Add") . "!";
    			return(FALSE);
    		}
    	} elseif ((isset($line)) AND (isset($ext))) {
    		$sql = "SELECT luid FROM endpointman_line_list WHERE line = '" . $line . "' AND mac_id = " . $mac_id;
    		$luid = sql($sql, 'getOne');
    		if ($luid) {
$this->error['add_line'] = "This line has already been assigned!";
    			return(FALSE);
    		} else {
    			if (!isset($displayname)) {
    				$sql = 'SELECT description FROM devices WHERE id = ' . $ext;
    				$name = & sql($sql, 'getOne');
    			} else {
    				$name = $displayname;
    			}
    
    			$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $ext . "', '" . $line . "', '" . addslashes($name) . "')";
    			sql($sql);
$this->message['add_line'] .= "Added " . $name . " to line " . $line . "<br/>";
    			return($mac_id);
    		}
    	}
    }

    
    function linesAvailable($lineid=NULL, $macid=NULL) {
    	if (isset($lineid)) {
    		$sql = "SELECT max_lines FROM endpointman_model_list WHERE id = (SELECT endpointman_mac_list.model FROM endpointman_mac_list, endpointman_line_list WHERE endpointman_line_list.luid = " . $lineid . " AND endpointman_line_list.mac_id = endpointman_mac_list.id)";
    
    		$sql_l = "SELECT line, mac_id FROM `endpointman_line_list` WHERE luid = " . $lineid;
    		$line = sql($sql_l, 'getRow', DB_FETCHMODE_ASSOC);
    
    		$sql_lu = "SELECT line FROM endpointman_line_list WHERE mac_id = " . $line['mac_id'];
    	} elseif (isset($macid)) {
    		$sql = "SELECT max_lines FROM endpointman_model_list WHERE id = (SELECT model FROM endpointman_mac_list WHERE id =" . $macid . ")";
    		$sql_lu = "SELECT line FROM endpointman_line_list WHERE mac_id = " . $macid;
    
    		$line['line'] = 0;
    	}
    
    	$max_lines = sql($sql, 'getOne');
    	$lines_used = sql($sql_lu, 'getAll');
    
    	for ($i = 1; $i <= $max_lines; $i++) {
    		if ($i == $line['line']) {
    			$temp[$i]['value'] = $i;
    			$temp[$i]['text'] = $i;
    			$temp[$i]['selected'] = "selected";
    		} else {
    			if (!$this->in_array_recursive($i, $lines_used)) {
    				$temp[$i]['value'] = $i;
    				$temp[$i]['text'] = $i;
    			}
    		}
    	}
    	if (isset($temp)) {
    		return($temp);
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * This function takes a string and tries to determine if it's a valid mac addess, return FALSE if invalid
     * @param string $mac The full mac address
     * @return mixed The cleaned up MAC is it was a MAC or False if not a mac
     */
    function mac_check_clean($mac) {
    	if ((strlen($mac) == "17") OR (strlen($mac) == "12")) {
    		//It might be better to use switch here instead of these IF statements...
    		//Is the mac separated by colons(:) or dashes(-)?
    		if (preg_match("/[0-9a-f][0-9a-f][:-]" .
    				"[0-9a-f][0-9a-f][:-]" .
    				"[0-9a-f][0-9a-f][:-]" .
    				"[0-9a-f][0-9a-f][:-]" .
    				"[0-9a-f][0-9a-f][:-]" .
    				"[0-9a-f][0-9a-f]/i", $mac)) {
    				return(strtoupper(str_replace(":", "", str_replace("-", "", $mac))));
    				//Is the string exactly 12 characters?
    		} elseif (strlen($mac) == "12") {
    			//Now is the string a valid HEX mac address?
    			if (preg_match("/[0-9a-f][0-9a-f]" .
    					"[0-9a-f][0-9a-f]" .
    					"[0-9a-f][0-9a-f]" .
    					"[0-9a-f][0-9a-f]" .
    					"[0-9a-f][0-9a-f]" .
    					"[0-9a-f][0-9a-f]/i", $mac)) {
    					return(strtoupper($mac));
    			} else {
    				return(FALSE);
    			}
    			//Is the mac separated by whitespaces?
    		} elseif (preg_match("/[0-9a-f][0-9a-f][\s]" .
    				"[0-9a-f][0-9a-f][\s]" .
    				"[0-9a-f][0-9a-f][\s]" .
    				"[0-9a-f][0-9a-f][\s]" .
    				"[0-9a-f][0-9a-f][\s]" .
    				"[0-9a-f][0-9a-f]/i", $mac)) {
    				return(strtoupper(str_replace(" ", "", $mac)));
    		} else {
    			return(FALSE);
    		}
    	} else {
    		return(FALSE);
    	}
    }
	
    /**
     * Display all unused registrations from whatever manager we are using!
     * @return <type>
     */
    function display_registration_list($line_id=NULL) {
    
    	if (isset($line_id)) {
    		$result = $this->eda->all_unused_registrations();
    		$line_data = $this->eda->get_line_information($line_id);
    	} else {
    		$result = $this->eda->all_unused_registrations();
    		$line_data = NULL;
    	}
    
    	$i = 1;
    	$temp = array();
    	foreach ($result as $row) {
    		$temp[$i]['value'] = $row['id'];
    		$temp[$i]['text'] = $row['id'] . " --- " . $row['description'];
    		$i++;
    	}
    
    	if (isset($line_data)) {
    		$temp[$i]['value'] = $line_data['ext'];
    		$temp[$i]['text'] = $line_data['ext'] . " --- " . $line_data['description'];
    		$temp[$i]['selected'] = "selected";
    	}
    
    	return($temp);
    }
	
    
    /**
     * Taken from PHP.net. A list of errors returned when uploading files.
     * @param <type> $error_code
     * @return string
     */
    function file_upload_error_message($error_code) {
    	switch ($error_code) {
    		case UPLOAD_ERR_INI_SIZE:
    			return _('The uploaded file exceeds the upload_max_filesize directive in php.ini');
    		case UPLOAD_ERR_FORM_SIZE:
    			return _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
    		case UPLOAD_ERR_PARTIAL:
    			return _('The uploaded file was only partially uploaded');
    		case UPLOAD_ERR_NO_FILE:
    			return _('No file was uploaded');
    		case UPLOAD_ERR_NO_TMP_DIR:
    			return _('Missing a temporary folder');
    		case UPLOAD_ERR_CANT_WRITE:
    			return _('Failed to write file to disk');
    		case UPLOAD_ERR_EXTENSION:
    			return _('File upload stopped by extension');
    		default:
    			return _('Unknown upload error');
    	}
    }
    
    /**
     * Send this function an ID from the mac devices list table and you'll get all the information we have on that particular phone
     * @param integer $mac_id ID number reference from the MySQL database referencing the table endpointman_mac_list
     * @return array
     * @example
     * Final Output will look something similar to this
     *  Array
     *       (
     *            [config_files_override] =>
     *            [global_user_cfg_data] => N;
     *            [model_id] => 213
     *            [brand_id] => 2
     *            [name] => Grandstream
     *            [directory] => grandstream
     *            [model] => GXP2000
     *            [mac] => 000B820D0050
     *            [template_id] => 0
     *            [global_custom_cfg_data] => Serialized Data (Changed Template Values)
     *            [long_name] => GXP Enterprise IP series [280,1200,2000,2010,2020]
     *            [product_id] => 21
     *            [cfg_dir] => gxp
     *            [cfg_ver] => 1.5
     *            [template_data] => Serialized Data (The default Template Values)
     *            [enabled] => 1
     *            [line] => Array
     *                (
     *                    [1] => Array
     *                        (
     *                            [luid] => 2
     *                            [mac_id] => 2
     *                            [line] => 1
     *                            [ext] => 1000
     *                            [description] => Description
     *                            [custom_cfg_data] =>
     *                            [user_cfg_data] =>
     *                            [secret] => secret
     *                            [id] => 1000
     *                            [tech] => sip
     *                            [dial] => SIP/1000
     *                            [devicetype] => fixed
     *                            [user] => 1000
     *                            [emergency_cid] =>
     *                        )
     *                )
     *         )
     */
    function get_phone_info($mac_id=NULL) {
    	//You could screw up a phone if the mac_id is blank
    	if (!isset($mac_id)) {
$this->error['get_phone_info'] = "Mac ID is not set";
    		return(FALSE);
    	}
    	$sql = "SELECT id FROM endpointman_mac_list WHERE model > 0 AND id =" . $mac_id;
    
    	$res = sql($sql);
    	if ($res->numRows()) {
    		//Returns Brand Name, Brand Directory, Model Name, Mac Address, Extension (FreePBX), Custom Configuration Template, Custom Configuration Data, Product Name, Product ID, Product Configuration Directory, Product Configuration Version, Product XML name,
    		$sql = "SELECT endpointman_mac_list.specific_settings, endpointman_mac_list.config_files_override, endpointman_mac_list.global_user_cfg_data, endpointman_model_list.id as model_id, endpointman_brand_list.id as brand_id, endpointman_brand_list.name, endpointman_brand_list.directory, endpointman_model_list.model, endpointman_mac_list.mac, endpointman_mac_list.template_id, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.long_name, endpointman_product_list.id as product_id, endpointman_product_list.cfg_dir, endpointman_product_list.cfg_ver, endpointman_model_list.template_data, endpointman_model_list.enabled, endpointman_mac_list.global_settings_override FROM endpointman_line_list, endpointman_mac_list, endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_mac_list.model = endpointman_model_list.id AND endpointman_brand_list.id = endpointman_model_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.id = " . $mac_id;
    		$phone_info = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    
    		if (!$phone_info) {
$this->error['get_phone_info'] = "Error with SQL Statement";
    		}
    
    		//If there is a template associated with this phone then pull that information and put it into the array
    		if ($phone_info['template_id'] > 0) {
    			$sql = "SELECT name, global_custom_cfg_data, config_files_override, global_settings_override FROM endpointman_template_list WHERE id = " . $phone_info['template_id'];
    			$phone_info['template_data_info'] = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    		}
    
    		$sql = "SELECT endpointman_line_list.*, sip.data as secret, devices.*, endpointman_line_list.description AS epm_description FROM endpointman_line_list, sip, devices WHERE endpointman_line_list.ext = devices.id AND endpointman_line_list.ext = sip.id AND sip.keyword = 'secret' AND mac_id = " . $mac_id . " ORDER BY endpointman_line_list.line ASC";
    		$lines_info = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
    		foreach ($lines_info as $line) {
    			$phone_info['line'][$line['line']] = $line;
    			$phone_info['line'][$line['line']]['description'] = $line['epm_description'];
    			$phone_info['line'][$line['line']]['user_extension'] = $line['user'];
    		}
    	} else {
    		$sql = "SELECT id, mac FROM endpointman_mac_list WHERE id =" . $mac_id;
    		//Phone is unknown, we need to display this to the end user so that they can make corrections
    		$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    
			$brand = $this->get_brand_from_mac($row['mac']);
    		if ($brand) {
    			$phone_info['brand_id'] = $brand['id'];
    			$phone_info['name'] = $brand['name'];
    		} else {
    			$phone_info['brand_id'] = 0;
    			$phone_info['name'] = 'Unknown';
    		}
    
    		$phone_info['id'] = $mac_id;
    		$phone_info['model_id'] = 0;
    		$phone_info['product_id'] = 0;
    		$phone_info['custom_cfg_template'] = 0;
    		$phone_info['mac'] = $row['mac'];
    		$sql = "SELECT endpointman_line_list.*, sip.data as secret, devices.* FROM endpointman_line_list, sip, devices WHERE endpointman_line_list.ext = devices.id AND endpointman_line_list.ext = sip.id AND sip.keyword = 'secret' AND mac_id = " . $mac_id;
    		$lines_info = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
    		foreach ($lines_info as $line) {
    			$phone_info['line'][$line['line']] = $line;
    		}
    	}
    	return $phone_info;
    }
	
    /**
     * Get the brand from any mac sent to this function
     * @param string $mac
     * @return array
     */
    function get_brand_from_mac($mac) {
    	//Check for valid mac address first
		if (!$this->mac_check_clean($mac)) {
    		return(FALSE);
    	}
    
    	//Get the OUI only
    	$oui = substr($this->mac_check_clean($mac), 0, 6);
    	//Find the matching brand model to the oui
    	$oui_sql = "SELECT endpointman_brand_list.name, endpointman_brand_list.id FROM endpointman_oui_list, endpointman_brand_list WHERE oui LIKE '%" . $oui . "%' AND endpointman_brand_list.id = endpointman_oui_list.brand AND endpointman_brand_list.installed = 1 LIMIT 1";
    	$brand = sql($oui_sql, 'getRow', DB_FETCHMODE_ASSOC);
    
    	$res = sql($oui_sql);
    	$brand_count = $res->numRows();
    
    	if (!$brand_count) {
    		//oui doesn't have a matching mysql reference, probably a PC/router/wap/printer of some sort.
    		$phone_info['id'] = 0;
    		$phone_info['name'] = _("Unknown");
    	} else {
    		$phone_info['id'] = $brand['id'];
    		$phone_info['name'] = $brand['name'];
    	}
    
    	return($phone_info);
    }
	
	
	
    /**
     * Prepare and then send the data that Provisioner expects, then take what provisioner gives us and do what it says
     * @param array $phone_info Everything from get_phone_info
     * @param bool  $reboot Reboot the Phone after write
     * @param bool  $write  Write out Directory structure.
     */
    function prepare_configs($phone_info, $reboot=TRUE, $write=TRUE) 
    {
    	$this->PROVISIONER_BASE = $this->PHONE_MODULES_PATH;
//define('PROVISIONER_BASE', $this->PROVISIONER_BASE);
    	if (file_exists($this->PHONE_MODULES_PATH . 'autoload.php')) {
    		if (!class_exists('ProvisionerConfig')) {
    			require($this->PHONE_MODULES_PATH . 'autoload.php');
    		}
    
    		//Load Provisioner
    		$class = "endpoint_" . $phone_info['directory'] . "_" . $phone_info['cfg_dir'] . '_phone';
    		$base_class = "endpoint_" . $phone_info['directory'] . '_base';
    		$master_class = "endpoint_base";
    		if (!class_exists($master_class)) {
    			ProvisionerConfig::endpointsAutoload($master_class);
    		}
    		if (!class_exists($base_class)) {
    			ProvisionerConfig::endpointsAutoload($base_class);
    		}
    		if (!class_exists($class)) {
    			ProvisionerConfig::endpointsAutoload($class);
    		}
    
    		if (class_exists($class)) {
				$provisioner_lib = new $class();
    
    			//Determine if global settings have been overridden
    			if ($phone_info['template_id'] > 0) {
    				if (isset($phone_info['template_data_info']['global_settings_override'])) {
    					$settings = unserialize($phone_info['template_data_info']['global_settings_override']);
    				} else {
    					$settings['srvip'] = $this->configmod->get('srvip');
    					$settings['ntp'] = $this->configmod->get('ntp');
    					$settings['config_location'] = $this->configmod->get('config_location');
    					$settings['tz'] = $this->configmod->get('tz');
    				}
    			} else {
    				if (isset($phone_info['global_settings_override'])) {
    					$settings = unserialize($phone_info['global_settings_override']);
    				} else {
    					$settings['srvip'] = $this->configmod->get('srvip');
    					$settings['ntp'] = $this->configmod->get('ntp');
    					$settings['config_location'] = $this->configmod->get('config_location');
    					$settings['tz'] = $this->configmod->get('tz');
    				}
    			}
    
    
    
    			//Tell the system who we are and were to find the data.
    			$provisioner_lib->root_dir = $this->PHONE_MODULES_PATH;
    			$provisioner_lib->engine = 'asterisk';
    			$provisioner_lib->engine_location = $this->configmod->get('asterisk_location','asterisk');
    			$provisioner_lib->system = 'unix';
    
    			//have to because of versions less than php5.3
    			$provisioner_lib->brand_name = $phone_info['directory'];
    			$provisioner_lib->family_line = $phone_info['cfg_dir'];
    
    
    
    			//Phone Model (Please reference family_data.xml in the family directory for a list of recognized models)
    			//This has to match word for word. I really need to fix this....
    			$provisioner_lib->model = $phone_info['model'];
    
    			//Timezone
    			try {
    				$provisioner_lib->DateTimeZone = new DateTimeZone($settings['tz']);
    			} catch (Exception $e) {
$this->error['parse_configs'] = 'Error Returned From Timezone Library: ' . $e->getMessage();
    				return(FALSE);
    			}
    
    			$temp = "";
    			$template_data = unserialize($phone_info['template_data']);
    			$global_user_cfg_data = unserialize($phone_info['global_user_cfg_data']);
    			if ($phone_info['template_id'] > 0) {
    				$global_custom_cfg_data = unserialize($phone_info['template_data_info']['global_custom_cfg_data']);
    				//Provide alternate Configuration file instead of the one from the hard drive
    				if (!empty($phone_info['template_data_info']['config_files_override'])) {
    					$temp = unserialize($phone_info['template_data_info']['config_files_override']);
    					foreach ($temp as $list) {
    						$sql = "SELECT original_name,data FROM endpointman_custom_configs WHERE id = " . $list;
    						$res = sql($sql);
    						if ($res->numRows()) {
    							$data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    							$provisioner_lib->config_files_override[$data['original_name']] = $data['data'];
    						}
    					}
    				}
    			} else {
    				$global_custom_cfg_data = unserialize($phone_info['global_custom_cfg_data']);
    				//Provide alternate Configuration file instead of the one from the hard drive
    				if (!empty($phone_info['config_files_override'])) {
    					$temp = unserialize($phone_info['config_files_override']);
    					foreach ($temp as $list) {
    						$sql = "SELECT original_name,data FROM endpointman_custom_configs WHERE id = " . $list;
    						$res = sql($sql);
    						if ($res->numRows()) {
    							$data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    							$provisioner_lib->config_files_override[$data['original_name']] = $data['data'];
    						}
    					}
    				}
    			}
    
    			if (!empty($global_custom_cfg_data)) {
    				if (array_key_exists('data', $global_custom_cfg_data)) {
    					$global_custom_cfg_ari = $global_custom_cfg_data['ari'];
    					$global_custom_cfg_data = $global_custom_cfg_data['data'];
    				} else {
    					$global_custom_cfg_data = array();
    					$global_custom_cfg_ari = array();
    				}
    			}
    
    			$new_template_data = array();
    			$line_ops = array();
    			if (is_array($global_custom_cfg_data)) {
    				foreach ($global_custom_cfg_data as $key => $data) {
    					//TODO: clean up with reg-exp
    					$full_key = $key;
    					$key = explode('|', $key);
    					$count = count($key);
    					switch ($count) {
    						case 1:
    							if (($this->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$new_template_data[$full_key] = $global_user_cfg_data[$full_key];
    							} else {
    								$new_template_data[$full_key] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    						case 2:
    							$breaks = explode('_', $key[1]);
    							if (($this->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$new_template_data['loops'][$breaks[0]][$breaks[2]][$breaks[1]] = $global_user_cfg_data[$full_key];
    							} else {
    								$new_template_data['loops'][$breaks[0]][$breaks[2]][$breaks[1]] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    						case 3:
    							if (($this->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$line_ops[$key[1]][$key[2]] = $global_user_cfg_data[$full_key];
    							} else {
    								$line_ops[$key[1]][$key[2]] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    					}
    				}
    			}
    
    			if (!$write) {
    				$new_template_data['provision']['type'] = 'dynamic';
    				$new_template_data['provision']['protocol'] = 'http';
    				$new_template_data['provision']['path'] =  rtrim($settings['srvip'] . dirname($_SERVER['REQUEST_URI']) . '/', '/');
    				$new_template_data['provision']['encryption'] = FALSE;
    			} else {
    				$new_template_data['provision']['type'] = 'file';
    				$new_template_data['provision']['protocol'] = 'tftp';
    				$new_template_data['provision']['path'] = $settings['srvip'];
    				$new_template_data['provision']['encryption'] = FALSE;
    			}
    
    			$new_template_data['ntp'] = $settings['ntp'];
    
    			//Overwrite all specific settings variables now
    			if (!empty($phone_info['specific_settings'])) {
    				$specific_settings = unserialize($phone_info['specific_settings']);
    				$specific_settings = is_array($specific_settings) ? $specific_settings : array();
    			} else {
    				$specific_settings = array();
    			}
    
    			//Set Variables according to the template_data files included. We can include different template.xml files within family_data.xml also one can create
    			//template_data_custom.xml which will get included or template_data_<model_name>_custom.xml which will also get included
    			//line 'global' will set variables that aren't line dependant
    
    
    			$provisioner_lib->settings = $new_template_data;
    
    			//Loop through Lines!
    			$li = 0;
    			foreach ($phone_info['line'] as $line) {
    				$line_options = is_array($line_ops[$line['line']]) ? $line_ops[$line['line']] : array();
    				$line_statics = array('line' => $line['line'], 'username' => $line['ext'], 'authname' => $line['ext'], 'secret' => $line['secret'], 'displayname' => $line['description'], 'server_host' => $this->global_cfg['srvip'], 'server_port' => '5060', 'user_extension' => $line['user_extension']);
    
    				$provisioner_lib->settings['line'][$li] = array_merge($line_options, $line_statics);
    				$li++;
    			}
    
    			if (array_key_exists('data', $specific_settings)) {
    				foreach ($specific_settings['data'] as $key => $data) {
    					$default_exp = preg_split("/\|/i", $key);
    					if (isset($default_exp[2])) {
    						//lineloop
    						$var = $default_exp[2];
    						$line = $default_exp[1];
    						$loc = $this->system->arraysearchrecursive($line, $provisioner_lib->settings['line'], 'line');
    						if ($loc !== FALSE) {
    							$k = $loc[0];
    							$provisioner_lib->settings['line'][$k][$var] = $data;
    						} else {
    							//Adding a new line-ish type options
    							if (isset($specific_settings['data']['line|' . $line . '|line_enabled'])) {
    								$lastkey = array_pop(array_keys($provisioner_lib->settings['line']));
    								$lastkey++;
    								$provisioner_lib->settings['line'][$lastkey]['line'] = $line;
    								$provisioner_lib->settings['line'][$lastkey][$var] = $data;
    							}
    						}
    					} else {
    						switch ($key) {
    							case "connection_type":
    								$provisioner_lib->settings['network'][$key] = $data;
    								break;
    							case "ip4_address":
    								$provisioner_lib->settings['network']['ipv4'] = $data;
    								break;
    							case "ip6_address":
    								$provisioner_lib->settings['network']['ipv6'] = $data;
    								break;
    							case "subnet_mask":
    								$provisioner_lib->settings['network']['subnet'] = $data;
    								break;
    							case "gateway_address":
    								$provisioner_lib->settings['network']['gateway'] = $data;
    								break;
    							case "primary_dns":
    								$provisioner_lib->settings['network'][$key] = $data;
    								break;
    							default:
    								$provisioner_lib->settings[$key] = $data;
    								break;
    						}
    					}
    				}
    			}
    
    			$provisioner_lib->settings['mac'] = $phone_info['mac'];
    			$provisioner_lib->mac = $phone_info['mac'];
    
    			//Setting a line variable here...these aren't defined in the template_data.xml file yet. however they will still be parsed
    			//and if they have defaults assigned in a future template_data.xml or in the config file using pipes (|) those will be used, pipes take precedence
    			$provisioner_lib->processor_info = "EndPoint Manager Version " . $this->global_cfg['version'];
    
    			// Because every brand is an extension (eventually) of endpoint, you know this function will exist regardless of who it is
    			//Start timer
    			$time_start = microtime(true);
    
    			$provisioner_lib->debug = TRUE;
    
    			try {
    				$returned_data = $provisioner_lib->generate_all_files();
    			} catch (Exception $e) {
$this->error['prepare_configs'] = 'Error Returned From Provisioner Library: ' . $e->getMessage();
    				return(FALSE);
    			}
    			//print_r($provisioner_lib->debug_return);
    			//End timer
    			$time_end = microtime(true);
    			$time = $time_end - $time_start;
    			if ($time > 360) {
$this->error['generate_time'] = "It took an awfully long time to generate configs...(" . round($time, 2) . " seconds)";
    			}
    			if ($write) {
    				$this->write_configs($provisioner_lib, $reboot, $settings['config_location'], $phone_info, $returned_data);
    			} else {
    				return ($returned_data);
    			}
    			return(TRUE);
    		} else {
$this->error['parse_configs'] = "Can't Load \"" . $class . "\" Class!";
    			return(FALSE);
    		}
    	} else {
$this->error['parse_configs'] = "Can't Load the Autoloader!";
    		return(FALSE);
    	}
    }
	
    function write_configs($provisioner_lib, $reboot, $write_path, $phone_info, $returned_data) {
    	//Create Directory Structure (If needed)
    	if (isset($provisioner_lib->directory_structure)) {
    		foreach ($provisioner_lib->directory_structure as $data) {
    			if (file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    				$dir_iterator = new \RecursiveDirectoryIterator($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data . "/");
    				$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
    				// could use CHILD_FIRST if you so wish
    				foreach ($iterator as $file) {
    					$dir = $write_path . str_replace($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/", "", dirname($file));
    					if (!file_exists($dir)) {
    						if (!@mkdir($dir, 0775, TRUE)) {
$this->error['parse_configs'] = "Could Not Create Directory: " . $data;
    							return(FALSE);
    						}
    					}
    				}
    			} else {
    				$dir = $write_path . $data;
    				if (!file_exists($dir)) {
    					if (!@mkdir($dir, 0775)) {
$this->error['parse_configs'] = "Could Not Create Directory: " . $data;
    						return(FALSE);
    					}
    				}
    			}
    		}
    	}
    
    	//Copy Files (If needed)
    	if (isset($provisioner_lib->copy_files)) {
    		foreach ($provisioner_lib->copy_files as $data) {
    			if (file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    				$file = $write_path . $data;
    				$orig = $this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data;
    				if (!file_exists($file)) {
    					if (!@copy($orig, $file)) {
$this->error['parse_configs'] = "Could Not Create File: " . $data;
    						return(FALSE);
    					}
    				} else {
    					if (file_exists($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    						if (!file_exists(dirname($write_path . $data))) {
    							!@mkdir(dirname($write_path . $data), 0775);
    						}
    						copy($this->PHONE_MODULES_PATH . "endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data, $write_path . $data);
    						chmod($write_path . $data, 0775);
    					}
    				}
    			}
    		}
    	}
    
    	foreach ($returned_data as $file => $data) {
    		if (((file_exists($write_path . $file)) AND (is_writable($write_path . $file)) AND (!in_array($file, $provisioner_lib->protected_files))) OR (!file_exists($write_path . $file))) {
    			//Move old file to backup
    			if (!$this->global_cfg['backup_check']) {
    				if (!file_exists($write_path . 'config_bkup')) {
    					if (!@mkdir($write_path . 'config_bkup', 0775)) {
$this->error['parse_configs'] = "Could Not Create Backup Directory";
    						return(FALSE);
    					}
    				}
    				if (file_exists($write_path . $file)) {
    					copy($write_path . $file, $write_path . 'config_bkup/' . $file . '.' . time());
    				}
    			}
    			file_put_contents($write_path . $file, $data);
    			chmod($write_path . $file, 0775);
    			if (!file_exists($write_path . $file)) {
$this->error['parse_configs'] = "File (" . $file . ") not written to hard drive!";
    				return(FALSE);
    			}
    		} elseif (!in_array($file, $provisioner_lib->protected_files)) {
$this->error['parse_configs'] = "File not written to hard drive!";
    			return(FALSE);
    		}
    	}
    
    	if ($reboot) {
    		$provisioner_lib->reboot();
    	}
    }
	
    /**
     * Custom Means specific to that MAC
     * id is either the mac ID (not address) or the template ID
     * @param integer $id
     * @param integer $custom
     */
    function edit_template_display($id, $custom) {
    	//endpointman_flush_buffers();
    	
    	$alt_configs = NULL;
    
    	if ($custom == 0) {
    		$sql = "SELECT model_id FROM endpointman_template_list WHERE id=" . $id;
    	} else {
    		$sql = "SELECT model FROM endpointman_mac_list WHERE id=" . $id;
    	}
    
    	$model_id = sql($sql, 'getOne');
    
    	//Make sure the model data from the local confg files are stored in the database and vice-versa. Serious errors will occur if the database is not in sync with the local file
    	if (!$this->sync_model($model_id)) {
    		die("unable to sync local template files - TYPE:" . $custom);
    	}
   
    	
    	
    	
    	
    	
    	
    	
    	$dReturn = array();

    	
		//Determine if we are dealing with a general template or a specific [for that phone only] template (custom =0 means general)
		if ($custom == 0) {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_template_list.global_custom_cfg_data,  endpointman_product_list.config_files, endpointman_product_list.short_name, endpointman_product_list.id as product_id, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = " . $id;
		} else {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_line_list.*, endpointman_model_list.id as model_id, endpointman_model_list.template_data, endpointman_product_list.id as product_id, endpointman_product_list.short_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list, endpointman_line_list WHERE endpointman_mac_list.id=" . $id . " AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
		}
		$row = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
		
		
		$dReturn['template_editor_display'] = 1;
		
		//Let the template system know if we are working with a general template or a specific [for that phone only] template
		$dReturn['custom'] = $custom;
    	 if ($custom) 
    	 {
			$dReturn['ext'] = $row['ext'];
    	 } 
    	 else 
    	 {
    	 	$dReturn['template_name'] = $row['name'];
    	 }
		$dReturn['product'] = $row['short_name'];
		$dReturn['model'] = $row['model_name'];
		
		if ($ma = $this->models_available($row['model_id'], NULL, $row['product_id'])) {
			$dReturn['models_ava'] = $ma;
		}
    	
		if (isset($_REQUEST['maxlines'])) {
			$areas = $this->areaAvailable($row['model_id'], $_REQUEST['maxlines']);
		} else {
			$areas = $this->areaAvailable($row['model_id'], 1);
		}
		$dReturn['area_ava'] = $areas;
    	
    	 
		//Start the display of the html file in the product folder
		if ($row['config_files_override'] == "") {
			$config_files_saved = "";
		} else {
			$config_files_saved = unserialize($row['config_files_override']);
		}
		$config_files_list = explode(",", $row['config_files']);
		$i = 0;
		$alt = 0;
		
		$i = 0;
		$b = 0;
		$only_configs = array();
		foreach ($config_files_list as $files) {
			$sql = "SELECT * FROM  endpointman_custom_configs WHERE product_id = '" . $row['product_id'] . "' AND original_name = '" . $files . "'";
			$alt_configs_list_count = sql($sql);
			if (! empty($alt_configs_list_count)) {
				$alt_configs_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
				$alt_configs[$i]['name'] = $files;
				$files = str_replace(".", "_", $files);
				$h = 0;
				foreach ($alt_configs_list as $ccf) {
					$alt_configs[$i]['list'][$h]['id'] = $ccf['id'];
					$cf_key = $files;
					if ((isset($config_files_saved[$cf_key])) AND (is_array($config_files_saved)) AND ($config_files_saved[$cf_key] == $ccf['id'])) {
						$alt_configs[$i]['list'][$h]['selected'] = 'selected';
					}
					$alt_configs[$i]['list'][$h]['name'] = $ccf['name'];
					$h++;
				}
				$alt = 1;
			} 
			else {
				$only_configs[$b]['name'] = $files;
				$b++;
			}
			$i++;
		}
		
		$dReturn['only_configs'] = $only_configs;
		$dReturn['alt_configs'] = $alt_configs;
		$dReturn['alt'] = $alt;
		
		if (!isset($_REQUEST['maxlines'])) {
			$maxlines = 1;
		} else {
			$maxlines = $_REQUEST['maxlines'];
		}
		if ($row['template_data'] != "") {
			$out = $this->generate_gui_html($row['template_data'], $row['global_custom_cfg_data'], TRUE, NULL, $maxlines);
		} else {
			$out = "No Template Data has been defined for this Product<br />";
		}
		
		$dReturn['template_editor'] = $out;
		$dReturn['hidden_id'] = $row['id'];
		$dReturn['hidden_custom'] = $custom;

    	return $dReturn;
    }
    
    function merge_data($path, $template_list, $maxlines = 12) {
    	//TODO: fix
    	foreach ($template_list as $files_data) {
    		$full_path = $path . $files_data;
    		if (file_exists($full_path)) {
    			$temp_files_data = $this->file2json($full_path);
    			foreach ($temp_files_data['template_data']['category'] as $category) {
    				$category_name = $category['name'];
    				foreach ($category['subcategory'] as $subcategory) {
    					$subcategory_name = $subcategory['name'];
    					$items_fin = array();
    					$items_loop = array();
    					$break_count = 0;
    					foreach ($subcategory['item'] as $item) {
    						switch ($item['type']) {
    							case 'loop_line_options':
    								for ($i = 1; $i <= $maxlines; $i++) {
    									$var_nam = "lineloop|line_" . $i;
    									foreach ($item['data']['item'] as $item_loop) {
    										if ($item_loop['type'] != 'break') {
    											$z = str_replace("\$", "", $item_loop['variable']);
    											$items_loop[$var_nam][$z] = $item_loop;
    											$items_loop[$var_nam][$z]['description'] = str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
    											$items_loop[$var_nam][$z]['default_value'] = $items_loop[$var_nam][$z]['default_value'];
    											$items_loop[$var_nam][$z]['default_value'] = str_replace('{$count}', $i, $items_loop[$var_nam][$z]['default_value']);
    											$items_loop[$var_nam][$z]['line_loop'] = TRUE;
    											$items_loop[$var_nam][$z]['line_count'] = $i;
    										} elseif ($item_loop['type'] == 'break') {
    											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
    											$break_count++;
    										}
    									}
    								}
    								$items_fin = array_merge($items_fin, $items_loop);
    								break;
    							case 'loop':
    								for ($i = $item['loop_start']; $i <= $item['loop_end']; $i++) {
    									$name = explode("_", $item['data']['item'][0]['variable']);
    									$var_nam = "loop|" . str_replace("\$", "", $name[0]) . "_" . $i;
    									foreach ($item['data']['item'] as $item_loop) {
    										if ($item_loop['type'] != 'break') {
    											$z_tmp = explode("_", $item_loop['variable']);
    											$z = $z_tmp[1];
    											$items_loop[$var_nam][$z] = $item_loop;
    											$items_loop[$var_nam][$z]['description'] = str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
    											$items_loop[$var_nam][$z]['variable'] = str_replace('_', '_' . $i . '_', $items_loop[$var_nam][$z]['variable']);
    											$items_loop[$var_nam][$z]['default_value'] = isset($items_loop[$var_nam][$z]['default_value']) ? $items_loop[$var_nam][$z]['default_value'] : '';
    											$items_loop[$var_nam][$z]['loop'] = TRUE;
    											$items_loop[$var_nam][$z]['loop_count'] = $i;
    										} elseif ($item_loop['type'] == 'break') {
    											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
    											$break_count++;
    										}
    									}
    								}
    								$items_fin = array_merge($items_fin, $items_loop);
    								break;
    							case 'break':
    								$items_fin['break|' . $break_count]['type'] = 'break';
    								$break_count++;
    								break;
    							default:
    								$var_nam = "option|" . str_replace("\$", "", (isset($item['variable'])? $item['variable'] : ""));
    								$items_fin[$var_nam] = $item;
    								break;
    						}
    					}
    					if (isset($data['data'][$category_name][$subcategory_name])) {
    						$old_sc = $data['data'][$category_name][$subcategory_name];
    						$sub_cat_data[$category_name][$subcategory_name] = array();
    						$sub_cat_data[$category_name][$subcategory_name] = array_merge($old_sc, $items_fin);
    					} else {
    						$sub_cat_data[$category_name][$subcategory_name] = $items_fin;
    					}
    				}
    				if (isset($data['data'][$category_name])) {
    					$old_c = $data['data'][$category_name];
    					$new_c = $sub_cat_data[$category_name];
    					$sub_cat_data[$category_name] = array();
    					$data['data'][$category_name] = array_merge($old_c, $new_c);
    				} else {
    					$data['data'][$category_name] = $sub_cat_data[$category_name];
    				}
    			}
    		}
    	}
    	return($data);
    }
    
    /**
     *
     * @param integer $model model ID
     * @param integer $brand brand ID
     * @param integer $product product ID
     * @return array
     */
    function models_available($model=NULL, $brand=NULL, $product=NULL) {
    
    	if ((!isset($oui)) && (!isset($brand)) && (!isset($model))) {
    		$result1 = $this->eda->all_models();
    	} elseif ((isset($brand)) && ($brand != 0)) {
    		$result1 = $this->eda->all_models_by_brand($brand);
    	} elseif ((isset($product)) && ($product != 0)) {
    		$result1 = $this->eda->all_models_by_product($product);
    	} else {
    		$result1 = $this->eda->all_models();
    	}
    
    	$i = 1;
    	foreach ($result1 as $row) {
    		if ($row['id'] == $model) {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 'selected';
    		} else {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 0;
    		}
    		$i++;
    	}
    
    	if (!isset($temp)) {
    		if (! $this->configmod->isExiste('new')) {
    			$this->error['modelsAvailable'] = "You need to enable at least ONE model";
    		}
    		return(FALSE);
    	} else {
    		return($temp);
    	}
    }
    
    
    function areaAvailable($model, $area=NULL) {
    	$sql = "SELECT max_lines FROM endpointman_model_list WHERE id = '" . $model . "'";
    	$count = sql($sql, 'getOne');
    
    	for ($z = 0; $z < $count; $z++) {
    		$result[$z]['id'] = $z + 1;
    		$result[$z]['model'] = $z + 1;
    	}
    
    	$i = 1;
    	foreach ($result as $row) {
    		if ($row['id'] == $area) {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 'selected';
    		} else {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 0;
    		}
    		$i++;
    	}
    
    	return($temp);
    }
    
    /**
     * Generates the Visual Display for the end user
     * @param <type> $cfg_data
     * @param <type> $custom_cfg_data
     * @param <type> $admin
     * @param <type> $user_cfg_data
     * @return <type>
     */
    function generate_gui_html($cfg_data, $custom_cfg_data=NULL, $admin=FALSE, $user_cfg_data=NULL, $max_lines=3, $ext=NULL) {
    	//take the data out of the database and turn it back into an array for use
    	$cfg_data = unserialize($cfg_data);
    	$template_type = 'GENERAL';
    	//Check to see if there is a custom template for this phone already listed in the endpointman_mac_list database
    	if (!empty($custom_cfg_data)) {
    		$custom_cfg_data = unserialize($custom_cfg_data);
    		if (array_key_exists('data', $custom_cfg_data)) {
    			if (array_key_exists('ari', $custom_cfg_data)) {
    				$extra_data = $custom_cfg_data['ari'];
    			} else {
    				$template_type = 'GLOBAL';
    				$extra_data = $custom_cfg_data['freepbx'];
    			}
    			$custom_cfg_data = $custom_cfg_data['data'];
    		} else {
    			$extra_data = array();
    		}
    	} else {
    		$custom_cfg_data = array();
    		$extra_data = array();
    	}
    	if (isset($user_cfg_data)) {
    		$user_cfg_data = unserialize($user_cfg_data);
    	}
    
    	$template_variables_array = array();
    	$group_count = 0;
    	$variables_count = 0;
    
    	foreach ($cfg_data['data'] as $cats_name => $cats) {
    		if ($admin) {
    			$group_count++;
    			$template_variables_array[$group_count]['title'] = $cats_name;
    		} else {
    			//Group all ARI stuff into one tab
    			$template_variables_array[$group_count]['title'] = "Your Phone Settings";
    		}
    		foreach ($cats as $subcat_name => $subcats) {
    			foreach ($subcats as $item_var => $config_options) {
    				if (preg_match('/(.*)\|(.*)/i', $item_var, $matches)) {
    					$type = $matches[1];
    					$variable = $matches[2];
    				} else {
    					die('no matches!');
    				}
    				if ($admin) {
    					//Administration View Only
    					switch ($type) {
    						case "lineloop":
    							//line|1|display_name
    							foreach ($config_options as $var_name => $var_items) {
    								$lcount = isset($var_items['line_count']) ? $var_items['line_count'] : $lcount;
    								$key = "line|" . $lcount . "|" . $var_name;
    								$items[$variables_count] = $items;
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    								$variables_count++;
    							}
    
    							if ($lcount <= $max_lines) {
    								$template_variables_array[$group_count]['title'] = "Line Options for Line " . $lcount;
    								$group_count++;
    							} else {
    								unset($template_variables_array[$group_count]);
    							}
    
    							continue 2;
    						case "loop":
    							foreach ($config_options as $var_name => $var_items) {
    								//loop|remotephonebook_url_0
    								$tv = explode('_', $variable);
    								$key = "loop|" . $tv[0] . "_" . $var_name . "_" . $var_items['loop_count'];
    								$items[$variables_count] = $var_items;
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    								$variables_count++;
    							}
    							continue 2;
    					}
    				} else {
    					//ARI View Only
    					switch ($type) {
    						case "loop_line_options":
    							//$a is the line number
    							$sql = "SELECT line FROM endpointman_line_list WHERE  ext = " . $ext;
    							$a = $this->eda->sql($sql, 'getOne');
    							//TODO: fix this area
    							$template_variables_array[$group_count]['data'][$variables_count]['type'] = "break";
    							$variables_count++;
    							continue 2;
    						case "loop":
    							foreach ($config_options as $var_name => $var_items) {
    								$tv = explode('_', $variable);
    								$key = "loop|" . $tv[0] . "_" . $var_name . "_" . $var_items['loop_count'];
    								if (isset($extra_data[$key])) {
    									$items[$variables_count] = $var_items;
    									$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    									$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    									$variables_count++;
    								}
    							}
    							continue 2;
    					}
    				}
    				//Both Views
    				switch ($config_options['type']) {
    					case "break":
    						$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    						$variables_count++;
    						break;
    					default:
    						if (array_key_exists('variable', $config_options)) {
    							$key = str_replace('$', '', $config_options['variable']);
    							//TODO: Move this into the sync function
    							//Checks to see if values are defined in the database, if not then we assume this is a new option and we need a default value here!
    							if (!isset($custom_cfg_data[$key])) {
    								//xml2array will take values that have no data and turn them into arrays, we want to avoid the word 'array' as a default value, so we blank it out here if we are an array
    								if ((array_key_exists('default_value', $config_options)) AND (is_array($config_options['default_value']))) {
    									$custom_cfg_data[$key] = "";
    								} elseif ((array_key_exists('default_value', $config_options)) AND (!is_array($config_options['default_value']))) {
    									$custom_cfg_data[$key] = $config_options['default_value'];
    								}
    							}
    							if ((!$admin) AND (isset($extra_data[$key]))) {
    								$custom_cfg_data[$key] = $user_cfg_data[$key];
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$variables_count++;
    							} elseif ($admin) {
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$variables_count++;
    							}
    						}
    						break;
    				}
    				continue;
    			}
    		}
    	}
    
    	return($template_variables_array);
    }
    
    /**
     * Generate an array that will get parsed as HTML from an array of values from XML
     * @param int $i
     * @param array $cfg_data
     * @param string $key
     * @param array $custom_cfg_data
     * @return array
     */
    function generate_form_data($i, $cfg_data, $key=NULL, $custom_cfg_data=NULL, $admin=FALSE, $user_cfg_data=NULL, $extra_data=NULL, $template_type='GENERAL') {
    	switch ($cfg_data['type']) {
    		case "input":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$template_variables_array['type'] = "input";
    			if (isset($cfg_data['max_chars'])) {
    				$template_variables_array['max_chars'] = $cfg_data['max_chars'];
    			}
    			$template_variables_array['key'] = $key;
    			$template_variables_array['value'] = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "radio":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "radio";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$z = 0;
    			while ($z < count($cfg_data['data'])) {
    				$template_variables_array['data'][$z]['key'] = $key;
    				$template_variables_array['data'][$z]['value'] = $cfg_data['data'][$z]['value'];
    				$template_variables_array['data'][$z]['description'] = $cfg_data['data'][$z]['text'];
    				if ($cfg_data['data'][$z]['value'] == $num) {
    					$template_variables_array['data'][$z]['checked'] = 'checked';
    				}
    				$z++;
    			}
    			break;
    			
    		case "list":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "list";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$z = 0;
    			while ($z < count($cfg_data['data'])) {
    				$template_variables_array['data'][$z]['value'] = $cfg_data['data'][$z]['value'];
    				$template_variables_array['data'][$z]['description'] = $cfg_data['data'][$z]['text'];
    				if (isset($cfg_data['data'][$z]['disable'])) {
    					$cfg_data['data'][$z]['disable'] = str_replace('{$count}', $z, $cfg_data['data'][$z]['disable']);
    					$template_variables_array['data'][$z]['disables'] = explode(",", $cfg_data['data'][$z]['disable']);
    				}
    				if (isset($cfg_data['data'][$z]['enable'])) {
    					$cfg_data['data'][$z]['enable'] = str_replace('{$count}', $z, $cfg_data['data'][$z]['enable']);
    					$template_variables_array['data'][$z]['enables'] = explode(",", $cfg_data['data'][$z]['enable']);
    				}
    				if ($cfg_data['data'][$z]['value'] == $num) {
    					$template_variables_array['data'][$z]['selected'] = 'selected';
    				}
    				$z++;
    			}
    			break;
    			
    		case "checkbox":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "checkbox";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$template_variables_array['checked'] = $custom_cfg_data[$key] ? TRUE : NULL;
    			$template_variables_array['value'] = $key;
    			break;
    			
    		case "group";
    			$template_variables_array['type'] = "group";
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "header";
    			$template_variables_array['type'] = "header";
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "textarea":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$template_variables_array['type'] = "textarea";
    			if (isset($cfg_data['rows'])) {
    				$template_variables_array['rows'] = $cfg_data['rows'];
    			}
    			if (isset($cfg_data['cols'])) {
    				$template_variables_array['cols'] = $cfg_data['cols'];
    			}
    			$template_variables_array['key'] = $key;
    			$template_variables_array['value'] = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "break":
    			if ($admin) {
    				$template_variables_array['type'] = "break";
    			} else {
    				$template_variables_array['type'] = "NA";
    			}
    			break;
    			
    		default:
    			$template_variables_array['type'] = "NA";
    			break;
    	}
    
    	if (isset($cfg_data['tooltip'])) {
    		$template_variables_array['tooltip'] = htmlentities($cfg_data['tooltip']);
    	}
    
    	if (($this->configmod->get('enable_ari')) AND ($admin) AND ($cfg_data['type'] != "break") AND ($cfg_data['type'] != "group") AND ($template_type == 'GENERAL')) {
    
    		$template_variables_array['aried'] = 1;
    		$template_variables_array['ari']['key'] = $key;
    
    		if (isset($extra_data[$key])) {
    			$template_variables_array['ari']['checked'] = "checked";
    		}
    	}
    
    	if ($template_type == 'GLOBAL') {
    		$template_variables_array['freepbxed'] = 1;
    		$template_variables_array['freepbx']['key'] = $key;
    		if (empty($extra_data)) {
    			$template_variables_array['freepbx']['checked'] = TRUE;
    		} elseif (isset($extra_data[$key])) {
    			$template_variables_array['freepbx']['checked'] = TRUE;
    		}
    	}
    	return($template_variables_array);
    }
    
    
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
	
	
	
	
	
	
	
	/*********************************************
	****** CODIGO ANTIGUO -- SIN REVISADO ********
	*********************************************/
	
	
	
	
	
	
	
	

    

    function download_json($location, $directory=NULL) {
        $temp_directory = $this->sys_get_temp_dir() . "/epm_temp/";
        if (!isset($directory)) {
            $destination_file = $this->PHONE_MODULES_PATH . 'endpoint/master.json';
            $directory = "master";
        } else {
            if (!file_exists($this->PHONE_MODULES_PATH . '/' . $directory)) {
                mkdir($this->PHONE_MODULES_PATH . '/' . $directory, 0775, TRUE);
            }
            $destination_file = $this->PHONE_MODULES_PATH . '/' . $directory . '/brand_data.json';
        }
        $temp_file = $temp_directory . $directory . '.json';
        file_exists(dirname($temp_file)) ? '' : mkdir(dirname($temp_file));

        if ($this->system->download_file($location, $temp_file)) {
            $handle = fopen($temp_file, "rb");
            $contents = fread($handle, filesize($temp_file));
            fclose($handle);

            $a = $this->validate_json($contents);
            if ($a === FALSE) {
                //Error with the internet....ABORRRTTTT THEEEEE DOWNLOAAAAADDDDDDDD! SCOTTYYYY!;
                unlink($temp_file);
                return(FALSE);
            } else {
                rename($temp_file, $destination_file);
                chmod($destination_file, 0775);
                return(TRUE);
            }
        } else {
            return(FALSE);
        }
    }

   

	

    /**
    * Send process to run in background
    * @version 2.11
    * @param string $command the command to run
    * @param integer $Priority the Priority of the command to run
    * @return int $PID process id
    * @package epm_system
    */
    function run_in_background($Command, $Priority = 0) {
        return($Priority ? shell_exec("nohup nice -n $Priority $Command 2> /dev/null & echo $!") : shell_exec("nohup $Command > /dev/null 2> /dev/null & echo $!"));
    }

    /**
    * Check if process is running in background
    * @version 2.11
    * @param string $PID proccess ID
    * @return bool true or false
    * @package epm_system
    */
    function is_process_running($PID) {
        exec("ps $PID", $ProcessState);
        return(count($ProcessState) >= 2);
    }

    

    /**
    * Uses which to find executables that asterisk can run/use
    * @version 2.11
    * @param string $exec Executable to find
    * @package epm_system
    */
    function find_exec($exec) {
        $o = exec('which '.$exec);
        if($o) {
            if(file_exists($o) && is_executable($o)) {
                return($o);
            } else {
                return('');
            }
        } else {
            return('');
        }
    }


    /**
     * Only used once in all of Endpoint Manager to determine if a table exists
     * @param string $table Table to look for
     * @return bool
     */
    function table_exists($table) {
        $sql = "SHOW TABLES FROM " . $this->config->get('AMPDBNAME');
        $result = $this->eda->sql($sql, 'getAll');
        foreach ($result as $row) {
            if ($row[0] == $table) {
                return TRUE;
            }
        }
        return FALSE;
    }

    

    

    /**
     * Check for valid netmast to avoid security issues
     * @param string $mask the complete netmask, eg [1.1.1.1/24]
     * @return boolean True if valid, False if not
     * @version 2.11
     */
    function validate_netmask($mask) {
        return preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/", $mask) ? TRUE : FALSE;
    }

    /**
     * Discover New Device/Hardware
     * nmap will actually discover 'unseen' devices that the VoIP server hasn't heard from
     * If the user just wishes to use the local arp cache they can tell the function to not use nmap
     * This results in a speed increase from 60 seconds to less than one second.
     *
     * This is the original function that started it all
     * http://www.pbxinaflash.com/community/index.php?threads/end-point-configuration-manager-module-for-freepbx-part-1.4514/page-4#post-37671
     *
     * @version 2.11
     * @param mixed $netmask The netmask, eg [1.1.1.1/24]
     * @param boolean $use_nmap True use nmap, false don't use it
     * @return array List of devices found on the network
     */
    function discover_new($netmask, $use_nmap=TRUE) {
        if (($use_nmap) AND (file_exists($this->global_cfg['nmap_location'])) AND ($this->validate_netmask($netmask))) {
            shell_exec($this->global_cfg['nmap_location'] . ' -v -sP ' . $netmask);
        } elseif (!$this->validate_netmask($netmask)) {
            $this->error['discover_new'] = "Invalid Netmask";
            return(FALSE);
        } elseif (!file_exists($this->global_cfg['nmap_location'])) {
            $this->error['discover_new'] = "Could Not Find NMAP, Using ARP Only";
            //return(FALSE);
        }
        //Get arp list
        $arp_list = shell_exec($this->global_cfg['arp_location'] . " -an");

        //Throw arp list into an array, break by new lines
        $arp_array = explode("\n", $arp_list);

        //Find all references to active computers by searching out mac addresses.
        $temp = array_values(array_unique(preg_grep("/[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f]/i", $arp_array)));

        //Go through each row of valid arp entries and pull out the information and add it into a nice array!
        $z = 0;
        foreach ($temp as $key => &$value) {

            //Pull out the IP address from row. It's always the first entry in the row and it can only be a max of 15 characters with the delimiters
            preg_match_all("/\((.*?)\)/", $value, $matches);
            $ip = $matches[1];
            $ip = $ip[0];

            //Pull out the mac address by looking for the delimiter
            $mac = substr($value, (strpos($value, ":") - 2), 17);

            //Get rid of the delimiter
            $mac_strip = strtoupper(str_replace(":", "", $mac));

            //arp -n will return a MAC address of 000000000000 if no hardware was found, so we need to ignore it
            if ($mac_strip != "000000000000") {
                //only use the first 6 characters for the oui: http://en.wikipedia.org/wiki/Organizationally_Unique_Identifier
                $oui = substr($mac_strip, 0, 6);

                //Find the matching brand model to the oui
                $oui_sql = "SELECT endpointman_brand_list.name, endpointman_brand_list.id FROM endpointman_oui_list, endpointman_brand_list WHERE oui LIKE '%" . $oui . "%' AND endpointman_brand_list.id = endpointman_oui_list.brand AND endpointman_brand_list.installed = 1 LIMIT 1";

                $brand = $this->eda->sql($oui_sql, 'getRow', DB_FETCHMODE_ASSOC);

                $res = $this->eda->sql($oui_sql);
                $brand_count = $res->numRows();

                if (!$brand_count) {
                    //oui doesn't have a matching mysql reference, probably a PC/router/wap/printer of some sort.
                    $brand['name'] = FALSE;
                    $brand['id'] = NULL;
                }

                //Find out if endpoint has already been configured for this mac address
                $epm_sql = "SELECT * FROM endpointman_mac_list WHERE mac LIKE  '%" . $mac_strip . "%'";
                $epm_row = $this->eda->sql($epm_sql, 'getRow', DB_FETCHMODE_ASSOC);

                $res = $this->eda->sql($epm_sql);

                $epm = $res->numRows() ? TRUE : FALSE;

                //Add into a final array
                $final[$z] = array("ip" => $ip, "mac" => $mac, "mac_strip" => $mac_strip, "oui" => $oui, "brand" => $brand['name'], "brand_id" => $brand['id'], "endpoint_managed" => $epm);
                $z++;
            }
        }
        return !is_array($final) ? FALSE : $final;
    }

    

    function in_array_recursive($needle, $haystack) {

        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

        foreach ($it AS $element) {
            if ($element == $needle) {
                return TRUE;
            }
        }
        return FALSE;
    }

    
    

    

    function display_templates($product_id, $temp_select = NULL) {
        $i = 0;
        $sql = "SELECT id FROM  endpointman_product_list WHERE endpointman_product_list.id ='" . $product_id . "'";

        $id = $this->eda->sql($sql, 'getOne');

        $sql = "SELECT * FROM  endpointman_template_list WHERE  product_id = '" . $id . "'";

        $data = $this->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        foreach ($data as $row) {
            $temp[$i]['value'] = $row['id'];
            $temp[$i]['text'] = $row['name'];
            if ($row['id'] == $temp_select) {
                $temp[$i]['selected'] = "selected";
            }
            $i++;
        }
        $temp[$i]['value'] = 0;
        if ($temp_select == 0) {
            $temp[$i]['text'] = "Custom...";
            $temp[$i]['selected'] = "selected";
        } else {
            $temp[$i]['text'] = "Custom...";
        }

        return($temp);
    }

    function validate_json($json) {
        return(TRUE);
    }

	
	
	
	
	
    

    

    function prepare_message_box() {
        $error_message = NULL;
        foreach ($this->error as $key => $error) {
            $error_message .= $error;
            if ($this->global_cfg['debug']) {
                $error_message .= " Function: [" . $key . "]";
            }
            $error_message .= "<br />";
        }
        $message = NULL;
        foreach ($this->message as $key => $error) {
            if (is_array($error)) {
                foreach ($error as $sub_error) {
                    $message .= $sub_error;
                    if ($this->global_cfg['debug']) {
                        $message .= " Function: [" . $key . "]";
                    }
                    $message .= "<br />";
                }
            } else {
                $message .= $error;
                if ($this->global_cfg['debug']) {
                    $message .= " Function: [" . $key . "]";
                }
                $message .= "<br />";
            }
        }

        if (isset($message)) {
            $this->display_message_box($message, 0);
        }

        if (isset($error_message)) {
            $this->display_message_box($error_message, 1);
        }
    }

   

    
   



    

    function update_device($macid, $model, $template, $luid=NULL, $name=NULL, $line=NULL, $update_lines=TRUE) {
        $sql = "UPDATE endpointman_mac_list SET model = " . $model . ", template_id =  " . $template . " WHERE id = " . $macid;
        $this->eda->sql($sql);

        if ($update_lines) {
            if (isset($luid)) {
                $this->update_line($luid, NULL, $name, $line);
                return(TRUE);
            } else {
                $this->update_line(NULL, $macid);
                return(TRUE);
            }
        }
    }

    function update_line($luid=NULL, $macid=NULL, $name=NULL, $line=NULL) {
        if (isset($luid)) {
            $sql = "SELECT * FROM endpointman_line_list WHERE luid = " . $luid;
            $row = $this->eda->sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

            if (!isset($name)) {
                $sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
                $name = $this->eda->sql($sql, 'getOne');
            }

            if (!isset($line)) {
                $line = $row['line'];
            }
            $sql = "UPDATE endpointman_line_list SET line = '" . $line . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
            $this->eda->sql($sql);
            return(TRUE);
        } else {
            $sql = "SELECT * FROM endpointman_line_list WHERE mac_id = " . $macid;
            $lines_info = $this->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
            foreach ($lines_info as $row) {
                $sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
                $name = $this->eda->sql($sql, 'getOne');

                $sql = "UPDATE endpointman_line_list SET line = '" . $row['line'] . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
                $this->eda->sql($sql);
            }
            return(TRUE);
        }
    }

    /**
     * This will either a. delete said line or b. delete said device from line
     * @param <type> $line
     * @return <type>
     */
    function delete_line($lineid, $allow_device_remove=FALSE) {
        $sql = 'SELECT mac_id FROM endpointman_line_list WHERE luid = ' . $lineid;
        $mac_id = $this->eda->sql($sql, 'getOne');
        $row = $this->get_phone_info($mac_id);

        $sql = 'SELECT COUNT(*) FROM endpointman_line_list WHERE mac_id = ' . $mac_id;
        $num_lines = $this->eda->sql($sql, 'getOne');
        if ($num_lines > 1) {
            $sql = "DELETE FROM endpointman_line_list WHERE luid=" . $lineid;
            $this->eda->sql($sql);
            $this->message['delete_line'] = "Deleted!";
            return(TRUE);
        } else {
            if ($allow_device_remove) {
                $sql = "DELETE FROM endpointman_line_list WHERE luid=" . $lineid;
                $this->eda->sql($sql);

                $sql = "DELETE FROM endpointman_mac_list WHERE id=" . $mac_id;
                $this->eda->sql($sql);
                $this->message['delete_line'] = "Deleted!";
                return(TRUE);
            } else {
                $this->error['delete_line'] = _("You can't remove the only line left") . "!";
                return(FALSE);
            }
        }
    }

    function delete_device($mac_id) {
        $sql = "DELETE FROM endpointman_mac_list WHERE id=" . $mac_id;
        $this->eda->sql($sql);

        $sql = "DELETE FROM endpointman_line_list WHERE mac_id=" . $mac_id;
        $this->eda->sql($sql);
        $this->message['delete_device'] = "Deleted!";
        return(TRUE);
    }

    function get_message($function_name) {
        if (isset($this->message[$function_name])) {
            return($this->message[$function_name]);
        } else {
            return("Unknown Message");
        }
    }


    

    

    

    /**
     * Save template from the template view pain
     * @param int $id Either the MAC ID or Template ID
     * @param int $custom Either 0 or 1, it determines if $id is MAC ID or Template ID
     * @param array $variables The variables sent from the form. usually everything in $_REQUEST[]
     * @return string Location of area to return to in Endpoint Manager
     */
    function save_template($id, $custom, $variables) {
        //Custom Means specific to that MAC
        //This function is reversed. Not sure why
        if ($custom != "0") {
            $sql = "SELECT endpointman_model_list.max_lines, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_product_list.id as product_id, endpointman_product_list.long_name, endpointman_model_list.template_data, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE endpointman_mac_list.id=" . $id . " AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
        } else {
            $sql = "SELECT endpointman_model_list.max_lines, endpointman_brand_list.directory, endpointman_product_list.cfg_dir, endpointman_product_list.config_files, endpointman_product_list.long_name, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_brand_list, endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = " . $id;
        }

        //Load template data
        $row = $this->eda->sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

        $cfg_data = unserialize($row['template_data']);
        $count = count($cfg_data);

        $custom_cfg_data_ari = array();

        foreach ($cfg_data['data'] as $cats) {
            foreach ($cats as $items) {
                foreach ($items as $key_name => $config_options) {
                    if (preg_match('/(.*)\|(.*)/i', $key_name, $matches)) {
                        $type = $matches[1];
                        $key = $matches[2];
                    } else {
                        die('invalid');
                    }
                    switch ($type) {
                        case "loop":
                            $stuffing = explode("_", $key);
                            $key2 = $stuffing[0];
                            foreach ($config_options as $item_key => $item_data) {
                                $lc = isset($item_data['loop_count']) ? $item_data['loop_count'] : '';
                                $key = 'loop|' . $key2 . '_' . $item_key . '_' . $lc;
                                if ((isset($item_data['loop_count'])) AND (isset($_REQUEST[$key]))) {
                                    $custom_cfg_data[$key] = $_REQUEST[$key];
                                    $ari_key = "ari_" . $key;
                                    if (isset($_REQUEST[$ari_key])) {
                                        if ($_REQUEST[$ari_key] == "on") {
                                            $custom_cfg_data_ari[$key] = 1;
                                        }
                                    }
                                }
                            }
                            break;
                        case "lineloop":
                            foreach ($config_options as $item_key => $item_data) {
                                $lc = isset($item_data['line_count']) ? $item_data['line_count'] : '';
                                $key = 'line|' . $lc . '|' . $item_key;
                                if ((isset($item_data['line_count'])) AND (isset($_REQUEST[$key]))) {
                                    $custom_cfg_data[$key] = $_REQUEST[$key];
                                    $ari_key = "ari_" . $key;
                                    if (isset($_REQUEST[$ari_key])) {
                                        if ($_REQUEST[$ari_key] == "on") {
                                            $custom_cfg_data_ari[$key] = 1;
                                        }
                                    }
                                }
                            }
                            break;
                        case "option":
                            if (isset($_REQUEST[$key])) {
                                $custom_cfg_data[$key] = $_REQUEST[$key];
                                $ari_key = "ari_" . $key;
                                if (isset($_REQUEST[$ari_key])) {
                                    if ($_REQUEST[$ari_key] == "on") {
                                        $custom_cfg_data_ari[$key] = 1;
                                    }
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $config_files = explode(",", $row['config_files']);

        $i = 0;
        while ($i < count($config_files)) {
            $config_files[$i] = str_replace(".", "_", $config_files[$i]);
            if (isset($_REQUEST[$config_files[$i]])) {
                $_REQUEST[$config_files[$i]] = explode("_", $_REQUEST[$config_files[$i]], 2);
                $_REQUEST[$config_files[$i]] = $_REQUEST[$config_files[$i]][0];
                if ($_REQUEST[$config_files[$i]] > 0) {
                    $config_files_selected[$config_files[$i]] = $_REQUEST[$config_files[$i]];
                }
            }
            $i++;
        }

        if (!isset($config_files_selected)) {
            $config_files_selected = "";
        } else {
            $config_files_selected = serialize($config_files_selected);
        }
        $custom_cfg_data_temp['data'] = $custom_cfg_data;
        $custom_cfg_data_temp['ari'] = $custom_cfg_data_ari;

        $save = serialize($custom_cfg_data_temp);

        if ($custom == "0") {
            $sql = 'UPDATE endpointman_template_list SET config_files_override = \'' . addslashes($config_files_selected) . '\', global_custom_cfg_data = \'' . addslashes($save) . '\' WHERE id =' . $id;
            $location = "template_manager";
        } else {
            $sql = 'UPDATE endpointman_mac_list SET config_files_override = \'' . addslashes($config_files_selected) . '\', template_id = 0, global_custom_cfg_data = \'' . addslashes($save) . '\' WHERE id =' . $id;
            $location = "devices_manager";
        }

        $this->eda->sql($sql);

        $phone_info = array();

        if ($custom != 0) {
            $phone_info = $this->get_phone_info($id);
            if (isset($_REQUEST['epm_reboot'])) {
                $this->prepare_configs($phone_info);
            } else {
                $this->prepare_configs($phone_info, FALSE);
            }
        } else {
            $sql = 'SELECT id FROM endpointman_mac_list WHERE template_id = ' . $id;
            $phones = $this->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
            foreach ($phones as $data) {
                $phone_info = $this->get_phone_info($data['id']);
                if (isset($_REQUEST['epm_reboot'])) {
                    $this->prepare_configs($phone_info);
                } else {
                    $this->prepare_configs($phone_info, FALSE);
                }
            }
        }

        if (isset($_REQUEST['silent_mode'])) {
            echo '<script language="javascript" type="text/javascript">window.close();</script>';
        } else {
            return($location);
        }
    }

    

    function display_configs() {

    }

   

}
?>