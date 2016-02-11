<?php
namespace FreePBX\modules\Endpointman;

class Config {
	private $module_conf;
	
	public function __construct() 
	{
		$this->getConfigModuleSQL();
	}
	
	public function getConfigModuleSQL($clear = true)
	{
		if ($clear) { $this->module_conf = ""; }
		$sql = "SELECT var_name, value FROM endpointman_global_vars";
		foreach (sql($sql, 'getAll', DB_FETCHMODE_ASSOC) as $row) {
			$this->module_conf[$row['var_name']] = $row['value'];
		}
	}
	
	public function isExiste($var) 	{ return isset($this->module_conf[$var]); }
	public function get($var) 		{ if ($this->isExiste($var)) { return $this->module_conf[$var]; } }
	public function getall() 		{ return $this->module_conf; }
	public function set($var, $val) { $this->module_conf[$var] = $val; }
	public function del($var) 		{ if ($this->isExiste($var)) { unset($this->module_conf[$var]); } }
}

?>