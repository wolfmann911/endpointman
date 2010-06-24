<?php
/*
Endpoint Manager V2
Copyright (C) 2009-2010  Ed Macri, John Mullinix and Andrew Nagy 

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
require dirname($_SERVER["SCRIPT_FILENAME"]). "/modules/endpointman/includes/functions.inc";

global $endpoint;

$endpoint = new endpointmanager();


if (! function_exists("out")) {
	function out($text) {
		echo $text."<br />";
	}
}

if (! function_exists("outn")) {
	function outn($text) {
		echo $text;
	}
}

global $db;

out("Endpoint Manager Installer");
out("Creating New phone modules directory");

mkdir(PHONE_MODULES_PATH, 0777);
//Detect Version

if($endpoint->table_exists("endpointman_global_vars")) {
	$sql = "SELECT var_name, value FROM endpointman_global_vars";

	$result = $db->query($sql);

	//$result = mysql_query("SELECT var_name, value FROM endpointman_global_vars");
	while ($row =& $result->fetchRow(DB_FETCHMODE_ASSOC)) {
		$global_cfg[$row['var_name']] = $row['value'];
	}
} else {
	$global_cfg['version'] = '?';
}

if(!isset($global_cfg['version'])){
  $ver = "1.0.3";
} elseif($global_cfg['version'] == '2.0') {
  $ver = "1.9.0";
} elseif($global_cfg['version'] == '1.9.1') {
	$ver = "1.9.1";
} elseif($global_cfg['version'] == '1.9.2') {
	$ver = "1.9.2";
} elseif($global_cfg['version'] == '1.9.3') {
	$ver = "1.9.3";
} elseif($global_cfg['version'] == '1.9.4') {
	$ver = "1.9.4";
} elseif($global_cfg['version'] == '1.9.5') {
	$ver = "1.9.5";
} elseif($global_cfg['version'] == '1.9.6') {
	$ver = "1.9.6";
} elseif($global_cfg['version'] == '1.9.7') {
	$ver = "1.9.7";
} elseif($global_cfg['version'] == '1.9.8') {
	$ver = "1.9.8";
} elseif($global_cfg['version'] == '1.9.9') {
	$ver = "1.9.9";
} elseif($global_cfg['version'] == '2.0.0') {
	$ver = "2.0.0";
} else {
  $ver = "?";
}

out('Version Identified as '. $ver);

//Done, Run Update Scripts
If ($ver < "1.9.0") {
  	//Run updates from 1.x releases to 2.x releases
		out("Please Wait While we upgrade your old setup");
		//Expand the value option
		$sql = 'ALTER TABLE `endpointman_global_vars` CHANGE `value` `value` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT \'Data\'';
		$db->query($sql);
		
		out("Locating NMAP + ARP + ASTERISK Executables");
		
		$nmap = $endpoint->find_exec("nmap");
		$arp = $endpoint->find_exec("arp");
		$asterisk = $endpoint->find_exec("asterisk");
		
		out("Updating Global Variables table");
		//Add new Vars into database
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES
		(5, 'config_location', '/tftpboot/'),
		(6, 'update_server', 'http://www.the159.com/endpoint/'),
		(7, 'version', '1.9.9'),
		(8, 'enable_ari', '0'),
		(9, 'debug', '0'),
		(10, 'arp_location', '".$arp."'),
		(11, 'nmap_location', '".$nmap."'),
		(12, 'asterisk_location', '".$asterisk."')";
		$db->query($sql_update_vars);
		
		define("UPDATE_PATH", 'http://www.the159.com/endpoint/');
		define("VER", '1.9.9');
		
		out("Updating Mac List table");
		$sql = 'ALTER TABLE `endpointman_mac_list` DROP `map`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `custom_cfg_template` INT(11) NOT NULL AFTER `description`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `custom_cfg_data` TEXT NOT NULL AFTER `custom_cfg_template`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `user_cfg_data` TEXT NOT NULL AFTER `custom_cfg_data`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `config_files_override` TEXT NOT NULL AFTER `user_cfg_data`';
		$db->query($sql);	  
		
		out("Updating Brands table");
		$sql = 'DROP TABLE endpointman_brand_list';
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_brand_list` (
		  `id` int(11) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL,
		  `directory` varchar(255) NOT NULL,
		  `cfg_ver` varchar(255) NOT NULL,
		  `installed` int(1) NOT NULL default '0',
		  `hidden` int(1) NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22";
		$db->query($sql);
		
		out("Updating Models table");
		$sql = 'DROP TABLE endpointman_model_list';
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_model_list` (
		  `id` int(11) NOT NULL auto_increment COMMENT 'Key ',
		  `brand` int(11) NOT NULL COMMENT 'Brand',
		  `model` varchar(25) NOT NULL COMMENT 'Model',
		  `product_id` int(11) NOT NULL,
		  `enabled` int(1) NOT NULL default '0',
		  `hidden` int(1) NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=48";
		$db->query($sql);
		
		out("Updating OUI table");
		
		$sql = 'DROP TABLE endpointman_oui_list';
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_oui_list` (
		  `id` int(30) NOT NULL auto_increment,
		  `oui` varchar(30) default NULL,
		  `brand` int(11) default NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=57";
		$db->query($sql);
	
		out("Updating Products table");
		
		$sql = 'DROP TABLE IF EXISTS endpointman_product_list';
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_product_list` (
		  `id` int(11) NOT NULL auto_increment,
		  `brand` int(11) NOT NULL,
		  `long_name` varchar(255) NOT NULL,
		  `cfg_dir` varchar(255) NOT NULL,
		  `cfg_ver` varchar(255) NOT NULL,
		  `xml_data` varchar(255) NOT NULL,
		  `cfg_data` text NOT NULL,
		  `installed` int(1) NOT NULL default '0',
		  `hidden` int(1) NOT NULL default '0',
		  `firmware_vers` varchar(255) NOT NULL,
		  `firmware_files` text NOT NULL,
		  `config_files` text,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8";
		$db->query($sql);
		
		out("Updating templates table");
		
		$sql = 'DROP TABLE IF EXISTS endpointman_template_list';
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_template_list` (
		  `id` int(11) NOT NULL auto_increment,
		  `product_id` int(11) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  `custom_cfg_data` text,
		  `config_files_override` text,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8";
		$db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_custom_configs` (
		  `id` int(11) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL,
		  `original_name` varchar(255) NOT NULL,
		  `product_id` int(11) NOT NULL,
		  `data` longtext NOT NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11";
		$db->query($sql);
		
		$old_models = array(
			"57iCT" => array("brand" => 1, "model" => 2, "product" => 7), 
			"57i" => array("brand" => 1, "model" => 3, "product" => 7), 
			"330" => array("brand" => 4, "model" => 6, "product" => 4),
			"560" => array("brand" => 4, "model" => 7, "product" => 4),
			"300" => array("brand" => 6, "model" => 8, "product" => 8),
			"320" => array("brand" => 6, "model" => 9, "product" => 8),
			"360" => array("brand" => 6, "model" => 10, "product" => 8),
			"370" => array("brand" => 6, "model" => 11, "product" => 8),
			"820" => array("brand" => 6, "model" => 12, "product" => 8),
			"M3" => array("brand" => 6, "model" => 13, "product" => 8),
			"GXP-2000" => array("brand" => 2, "model" => 15, "product" => 1),
			"BT200_201" => array("brand" => 2, "model" => 27, "product" => 2),
			"spa941" => array("brand" => 0, "model" => 0, "product" => 0),
			"spa942" => array("brand" => 0, "model" => 0, "product" => 0),
			"spa962" => array("brand" => 0, "model" => 0, "product" => 0),
			"55i" => array("brand" => 1, "model" => 4, "product" => 7)
			);
		
		out("Migrating Old Devices");
		$sql = "SELECT * FROM endpointman_mac_list";
		$result = $db->query($sql);
		while($row =& $result->fetchRow(DB_FETCHMODE_ASSOC)) {
			$id = $row['model'];
			$new_model = $old_models[$id]['model'];
			$sql = "UPDATE endpointman_mac_list SET model = ".$new_model." WHERE id =" . $row['id'];
			$db->query($sql);
		}
		out("Old Devices Migrated, You must install the phone modules from within endpointmanager to see your old devices!");
			
		$sql = 'ALTER TABLE endpointman_mac_list CHANGE model model INT NOT NULL';
		$db->query($sql);
		
		$sql = "ALTER TABLE endpointman_mac_list CHANGE custom_cfg_data custom_cfg_data TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
		$db->query($sql);		
		
		out("DONE! You can now use endpoint manager!");
		
	  
} elseif ($ver == "1.9.0") {

		
		out("Locating NMAP + ARP + ASTERISK Executables");
		
		$nmap = $endpoint->find_exec("nmap");
		$arp = $endpoint->find_exec("arp");
		$asterisk = $endpoint->find_exec("asterisk");
		
		out("Updating Global Variables table");
		//Add new Vars into database
		
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (8, 'enable_ari', '0')";
		$db->query($sql_update_vars);
		
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (9, 'debug', '0')";
		$db->query($sql_update_vars);
		
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (10, 'arp_location', '".$arp."')";
		$db->query($sql_update_vars);
		
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (11, 'nmap_location', '".$nmap."')";
		$db->query($sql_update_vars);
		
		$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (12, 'asterisk_location', '".$asterisk."')";
		$db->query($sql_update_vars);
		
		out("Updating Mac List Table");
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `user_cfg_data` TEXT NOT NULL AFTER `custom_cfg_data`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_mac_list` ADD `config_files_override` TEXT NOT NULL AFTER `user_cfg_data`';
		$db->query($sql);
		
		out("Updating OUI Table");
		$sql = 'ALTER TABLE `endpointman_oui_list` DROP model';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_oui_list` CHANGE `brand` `brand` INT( 11 ) NULL DEFAULT NULL';
		$db->query($sql);
		
		out("Updating Product List");
		$sql = 'ALTER TABLE `endpointman_product_list` ADD `firmware_vers` TEXT NULL AFTER `hidden`';
		$db->query($sql);
		
		$sql = 'ALTER TABLE `endpointman_product_list` ADD `firmware_files` VARCHAR( 255 ) NOT NULL AFTER `firmware_vers`';
		$db->query($sql);

		$sql = 'ALTER TABLE `endpointman_product_list` ADD `config_files_override` TEXT NULL AFTER `firmware_files`';
		$db->query($sql);
		
		out("Updating Template List");
		$sql = 'ALTER TABLE `endpointman_template_list` ADD `config_files_override` TEXT NULL AFTER `custom_cfg_data`';
		
		out("Updating Version Number");
		$sql = "UPDATE  endpointman_global_vars SET  value =  '1.9.9' WHERE  var_name = 'version'";
		
		out("Creating Custom Configs Table");
		$sql = "CREATE TABLE IF NOT EXISTS `endpointman_custom_configs` (
		  `id` int(11) NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL,
		  `original_name` varchar(255) NOT NULL,
		  `product_id` int(11) NOT NULL,
		  `data` longtext NOT NULL,
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11";
		$db->query($sql);
		
		out('Alter custom_cfg_data');
		$sql = "ALTER TABLE endpointman_mac_list CHANGE custom_cfg_data custom_cfg_data TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
		$db->query($sql);
		
} elseif($ver == "1.9.1") {
	out("Create Custom Configs Table");		
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_custom_configs` (
	  `id` int(11) NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL,
	  `original_name` varchar(255) NOT NULL,
	  `product_id` int(11) NOT NULL,
	  `data` longtext NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11";
	$db->query($sql);
	
	out("Locating NMAP + ARP + ASTERISK Executables");
	
	$nmap = $endpoint->find_exec("nmap");
	$arp = $endpoint->find_exec("arp");
	$asterisk = $endpoint->find_exec("asterisk");
	
	out('Updating Global Variables');
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (8, 'enable_ari', '0')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (9, 'debug', '0')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (10, 'arp_location', '".$arp."')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (11, 'nmap_location', '".$nmap."')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (12, 'asterisk_location', '".$asterisk."')";
	$db->query($sql_update_vars);
	
	out("Update Mac List Table");	
	$sql = 'ALTER TABLE `endpointman_mac_list` ADD `config_files_override` TEXT NOT NULL AFTER `user_cfg_data`';
	$db->query($sql);
	
	out("Update Product List Table");	
	$sql = 'ALTER TABLE `endpointman_product_list` ADD `config_files` TEXT NOT NULL AFTER `firmware_files`';
	$db->query($sql);
	
	out("Update Template List Table");	
	$sql = 'ALTER TABLE `endpointman_template_list` ADD `config_files_override` TEXT NOT NULL AFTER `custom_cfg_data`';
	$db->query($sql);
	
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
	
	out('Alter custom_cfg_data');
	$sql = "ALTER TABLE endpointman_mac_list CHANGE custom_cfg_data custom_cfg_data TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
	$db->query($sql);
	
} elseif($ver == "1.9.2") {
	
	out("Locating NMAP + ARP + ASTERISK Executables");
	
	$nmap = $endpoint->find_exec("nmap");
	$arp = $endpoint->find_exec("arp");
	$asterisk = $endpoint->find_exec("asterisk");
	
	out('Updating Global Variables');
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (10, 'arp_location', '".$arp."')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (11, 'nmap_location', '".$nmap."')";
	$db->query($sql_update_vars);
	
	$sql_update_vars = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (12, 'asterisk_location', '".$asterisk."')";
	$db->query($sql_update_vars);
	
	out('Alter custom_cfg_data');
	$sql = "ALTER TABLE endpointman_mac_list CHANGE custom_cfg_data custom_cfg_data TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
	$db->query($sql);
	
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
	
} elseif($ver == "1.9.3") {
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.4") {
	out("Your Database is already up to date!");
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.5") {
	out("Your Database is already up to date!");
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.6") {
	out("Your Database is already up to date!");
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.7") {
	out("Your Database is already up to date!");
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.8") {
	out("Your Database is already up to date!");
	out("Update Version Number");	
	$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
	$db->query($sql);
} elseif($ver == "1.9.9") {
	out("Your Database is already up to date!");
} else {
	out("Creating Brand List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_brand_list` (
	  `id` int(11) NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL,
	  `directory` varchar(255) NOT NULL,
	  `cfg_ver` varchar(255) NOT NULL,
	  `installed` int(1) NOT NULL default '0',
	  `hidden` int(1) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=22";
	$db->query($sql);

	out("Creating Global Variables Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_global_vars` (
	  `idnum` int(11) NOT NULL auto_increment COMMENT 'Index',
	  `var_name` varchar(25) NOT NULL COMMENT 'Variable Name',
	  `value` varchar(100) NOT NULL COMMENT 'Data',
	  PRIMARY KEY  (`idnum`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10";
	$db->query($sql);

	out("Locating NMAP + ARP + ASTERISK Executables");
	$nmap = $endpoint->find_exec("nmap");
	$arp = $endpoint->find_exec("arp");
	$asterisk = $endpoint->find_exec("asterisk");

	out("Inserting data into the global vars Table");
	$sql = "INSERT INTO `endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES
	(1, 'srvip', ''),
	(2, 'tz', ''),
	(3, 'gmtoff', ''),
	(4, 'gmthr', ''),
	(5, 'config_location', '/tftpboot/'),
	(6, 'update_server', 'http://www.the159.com/endpoint/'),
	(7, 'version', '1.9.9'),
	(8, 'enable_ari', '0'),
	(9, 'debug', '0'),
	(10, 'arp_location', '".$arp."'),
	(11, 'nmap_location', '".$nmap."'),
	(12, 'asterisk_location', '".$asterisk."')";	
	$db->query($sql);

	out("Creating mac list Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_mac_list` (
	  `id` int(10) NOT NULL auto_increment,
	  `mac` varchar(12) default NULL,
	  `model` int(11) NOT NULL,
	  `ext` varchar(15) default 'Not Assigned',
	  `description` varchar(20) default NULL,
	  `custom_cfg_template` int(11) NOT NULL,
	  `custom_cfg_data` text NOT NULL,
	  `user_cfg_data` text NOT NULL,
	  `config_files_override` text NOT NULL,
	  PRIMARY KEY  (`id`),
	  UNIQUE KEY `mac` (`mac`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=324";
	$db->query($sql);

	out("Creating model List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_model_list` (
	  `id` int(11) NOT NULL auto_increment COMMENT 'Key ',
	  `brand` int(11) NOT NULL COMMENT 'Brand',
	  `model` varchar(25) NOT NULL COMMENT 'Model',
	  `product_id` int(11) NOT NULL,
	  `enabled` int(1) NOT NULL default '0',
	  `hidden` int(1) NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=60";
	$db->query($sql);

	out("Creating oui List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_oui_list` (
	  `id` int(30) NOT NULL auto_increment,
	  `oui` varchar(30) default NULL,
	  `brand` int(11) NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=82";
	$db->query($sql);

	out("Creating product List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_product_list` (
	  `id` int(11) NOT NULL auto_increment,
	  `brand` int(11) NOT NULL,
	  `long_name` varchar(255) NOT NULL,
	  `cfg_dir` varchar(255) NOT NULL,
	  `cfg_ver` varchar(255) NOT NULL,
	  `xml_data` varchar(255) NOT NULL,
	  `cfg_data` text NOT NULL,
	  `installed` int(1) NOT NULL default '0',
	  `hidden` int(1) NOT NULL default '0',
	  `firmware_vers` varchar(255) NOT NULL,
	  `firmware_files` text NOT NULL,
	  `config_files` text,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10";
	$db->query($sql);

	out("Creating Template List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_template_list` (
	  `id` int(11) NOT NULL auto_increment,
	  `product_id` int(11) NOT NULL,
	  `name` varchar(255) NOT NULL,
	  `custom_cfg_data` text,
	  `config_files_override` text,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23";
	$db->query($sql);

	out("Creating Time Zone List Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_time_zones` (
	  `idnum` int(11) NOT NULL auto_increment COMMENT 'Record Number',
	  `tz` varchar(10) NOT NULL COMMENT 'Time Zone',
	  `gmtoff` varchar(10) NOT NULL COMMENT 'Offset in Seconds',
	  `gmthr` varchar(10) NOT NULL,
	  PRIMARY KEY  (`idnum`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=116";
	$db->query($sql);

	out("Inserting Data into table Table");
	$sql = "INSERT INTO `endpointman_time_zones` (`idnum`, `tz`, `gmtoff`, `gmthr`) VALUES
	(1, 'USA-10', '-36000', 'GMT-10:00'),
	(2, 'USA-9', '-32400', 'GMT-09:00'),
	(3, 'CAN-8', '-28800', 'GMT-08:00'),
	(4, 'MEX-8', '-28800', 'GMT-08:00'),
	(5, 'USA-8', '-28800', 'GMT-08:00'),
	(6, 'CAN-7', '-25200', 'GMT-07:00'),
	(7, 'MEX-7', '-25200', 'GMT-07:00'),
	(8, 'USA2-7', '-25200', 'GMT-07:00'),
	(9, 'USA-7', '-25200', 'GMT-07:00'),
	(10, 'CAN-6', '-21600', 'GMT-06:00'),
	(11, 'CHL-6', '-21600', 'GMT-06:00'),
	(12, 'MEX-6', '-21600', 'GMT-06:00'),
	(13, 'USA-6', '-21600', 'GMT-06:00'),
	(14, 'BHS-5', '-18000', 'GMT-05:00'),
	(15, 'CAN-5', '-18000', 'GMT-05:00'),
	(16, 'CUB-5', '-18000', 'GMT-05:00'),
	(17, 'USA-5', '-18000', 'GMT-05:00'),
	(18, 'VEN-4.5', '-16200', 'GMT-04:00'),
	(19, 'CAN-4', '-14400', 'GMT-04:00'),
	(20, 'CHL-4', '-14400', 'GMT-04:00'),
	(21, 'PRY-4', '-14400', 'GMT-04:00'),
	(22, 'BMU-4', '-14400', 'GMT-04:00'),
	(23, 'FLK-4', '-14400', 'GMT-04:00'),
	(24, 'TTB-4', '-14400', 'GMT-04:00'),
	(25, 'CAN-3.5', '-12600', 'GMT-03:30'),
	(26, 'GRL-3', '-10800', 'GMT-03:00'),
	(27, 'ARG-3', '-10800', 'GMT-03:00'),
	(28, 'BRA2-3', '-10800', 'GMT-03:00'),
	(29, 'BRA1-3', '-10800', 'GMT-03:00'),
	(30, 'BRA-2', '-7200', 'GMT-02:00'),
	(31, 'PRT-1', '-3600', 'GMT-01:00'),
	(32, 'FRO-0', '0', 'GMT'),
	(33, 'IRL-0', '0', 'GMT'),
	(34, 'PRT-0', '0', 'GMT'),
	(35, 'ESP-0', '0', 'GMT'),
	(36, 'GBR-0', '0', 'GMT'),
	(37, 'ALB+1', '3600', 'GMT+01:00'),
	(38, 'AUT+1', '3600', 'GMT+01:00'),
	(39, 'BEL+1', '3600', 'GMT+01:00'),
	(40, 'CAI+1', '3600', 'GMT+01:00'),
	(41, 'CHA+1', '3600', 'GMT+01:00'),
	(42, 'HRV+1', '3600', 'GMT+01:00'),
	(43, 'CZE+1', '3600', 'GMT+01:00'),
	(44, 'DNK+1', '3600', 'GMT+01:00'),
	(45, 'FRA+1', '3600', 'GMT+01:00'),
	(46, 'GER+1', '3600', 'GMT+01:00'),
	(47, 'HUN+1', '3600', 'GMT+01:00'),
	(48, 'ITA+1', '3600', 'GMT+01:00'),
	(49, 'LUX+1', '3600', 'GMT+01:00'),
	(50, 'MAK+1', '3600', 'GMT+01:00'),
	(51, 'NLD+1', '3600', 'GMT+01:00'),
	(52, 'NAM+1', '3600', 'GMT+01:00'),
	(53, 'NOR+1', '3600', 'GMT+01:00'),
	(54, 'POL+1', '3600', 'GMT+01:00'),
	(55, 'SVK+1', '3600', 'GMT+01:00'),
	(56, 'ESP+1', '3600', 'GMT+01:00'),
	(57, 'SWE+1', '3600', 'GMT+01:00'),
	(58, 'CHE+1', '3600', 'GMT+01:00'),
	(59, 'GIB+1', '3600', 'GMT+01:00'),
	(60, 'YUG+1', '3600', 'GMT+01:00'),
	(61, 'WAT+1', '3600', 'GMT+01:00'),
	(62, 'BLR+2', '7200', 'GMT+02:00'),
	(63, 'BGR+2', '7200', 'GMT+02:00'),
	(64, 'CYP+2', '7200', 'GMT+02:00'),
	(65, 'CAT+2', '7200', 'GMT+02:00'),
	(66, 'EGY+2', '7200', 'GMT+02:00'),
	(67, 'EST+2', '7200', 'GMT+02:00'),
	(68, 'FIN+2', '7200', 'GMT+02:00'),
	(69, 'GAZ+2', '7200', 'GMT+02:00'),
	(70, 'GRC+2', '7200', 'GMT+02:00'),
	(71, 'ISR+2', '7200', 'GMT+02:00'),
	(72, 'JOR+2', '7200', 'GMT+02:00'),
	(73, 'LVA+2', '7200', 'GMT+02:00'),
	(74, 'LBN+2', '7200', 'GMT+02:00'),
	(75, 'MDA+2', '7200', 'GMT+02:00'),
	(76, 'RUS+2', '7200', 'GMT+02:00'),
	(77, 'ROU+2', '7200', 'GMT+02:00'),
	(78, 'SYR+2', '7200', 'GMT+02:00'),
	(79, 'TUR+2', '7200', 'GMT+02:00'),
	(80, 'UKR+2', '7200', 'GMT+02:00'),
	(81, 'EAT+3', '10800', 'GMT+03:00'),
	(82, 'IRQ+3', '10800', 'GMT+03:00'),
	(83, 'RUS+3', '10800', 'GMT+03:00'),
	(84, 'IRN+3.5', '12600', 'GMT+03:30'),
	(85, 'ARM+4', '14400', 'GMT+04:00'),
	(86, 'AZE+4', '14400', 'GMT+04:00'),
	(87, 'GEO+4', '14400', 'GMT+04:00'),
	(88, 'KAZ+4', '14400', 'GMT+04:00'),
	(89, 'RUS+4', '14400', 'GMT+04:00'),
	(90, 'KAZ+5', '18000', 'GMT+05:00'),
	(91, 'KGZ+5', '18000', 'GMT+05:00'),
	(92, 'PAK+5', '18000', 'GMT+05:00'),
	(93, 'RUS+5', '18000', 'GMT+05:00'),
	(94, 'IND+5.5', '19800', 'GMT+05:30'),
	(95, 'KAZ+6', '21600', 'GMT+06:00'),
	(96, 'RUS+6', '21600', 'GMT+06:00'),
	(97, 'RUS+7', '25200', 'GMT+07:00'),
	(98, 'THA+7', '25200', 'GMT+07:00'),
	(99, 'CHN+7', '25200', 'GMT+07:00'),
	(100, 'SGP+8', '28800', 'GMT+08:00'),
	(101, 'KOR+8', '28800', 'GMT+08:00'),
	(102, 'AUS+8', '28800', 'GMT+08:00'),
	(103, 'JPN+9', '32400', 'GMT+09:00'),
	(104, 'AUS+9.5', '34200', 'GMT+09:30'),
	(105, 'AUS2+9.5', '34200', 'GMT+09:30'),
	(106, 'AUS+10', '36000', 'GMT+10:00'),
	(107, 'AUS2+10', '36000', 'GMT+10:00'),
	(108, 'AUS3+10', '36000', 'GMT+10:00'),
	(109, 'RUS+10', '36000', 'GMT+10:00'),
	(110, 'AUS+10.5', '37800', 'GMT+10:30'),
	(111, 'NCL+11', '39600', 'GMT+11:00'),
	(112, 'NZL+12', '43200', 'GMT+12:00'),
	(113, 'RUS+12', '43200', 'GMT+12:00'),
	(114, 'NZL+12.75', '45900', 'GMT+12:00'),
	(115, 'TON+13', '46800', 'GMT+13:00')";
	$db->query($sql);
	
	out("Create Custom Configs Table");
	$sql = "CREATE TABLE IF NOT EXISTS `endpointman_custom_configs` (
	  `id` int(11) NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL,
	  `original_name` varchar(255) NOT NULL,
	  `product_id` int(11) NOT NULL,
	  `data` longtext NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11";
	$db->query($sql);
	
	out('Alter custom_cfg_data');
	$sql = "ALTER TABLE endpointman_mac_list CHANGE custom_cfg_data custom_cfg_data TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL";
	$db->query($sql);
}

out("Fixing .htaccess issues (#288)");
$htaccess = "allow from all
AuthName FreePBX-Admin-only
Require valid-user
AuthType Basic
AuthMySQLEnable\tOn
AuthMySQLHost\tlocalhost
AuthMySQLDB\tasterisk
AuthMySQLUserTable\tampusers
AuthMySQLUser\t".$amp_conf['AMPDBUSER']."
AuthMySQLPassword\t".$amp_conf['AMPDBPASS']."
AuthMySQLNameField\tusername
AuthMySQLPasswordField\tpassword
AuthMySQLAuthoritative\tOn
AuthMySQLPwEncryption\tnone
AuthMySQLUserCondition\t\"username = 'admin'\"

<Files .*>
  deny from all
</Files>
";

$outfile =LOCAL_PATH. ".htaccess";
$wfh=fopen($outfile,'w');
fwrite($wfh,$htaccess);
fclose($wfh);

out("Installing ARI Module");		
rename(LOCAL_PATH. "Install/phonesettings.module", $amp_conf['AMPWEBROOT']."/recordings/modules/phonesettings.module");
		
out("Fixing permissions on ARI module");
chmod($amp_conf['AMPWEBROOT']."/recordings/modules/phonesettings.module", 0664);

out("Adding Custom Field to OUI List");
$sql = 'ALTER TABLE `endpointman_oui_list` ADD `custom` INT(1) NOT NULL DEFAULT \'0\'';
$db->query($sql);

out("Increase value Size in global Variables Table");
$sql = 'ALTER TABLE `endpointman_global_vars` CHANGE `value` `value` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT \'Data\'';
$db->query($sql);

out("Update global variables to include future language support");
$sql = 'INSERT INTO `asterisk`.`endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (\'13\', \'temp_amp\', \'\');';
$db->query($sql);

$sql = "UPDATE endpointman_global_vars SET var_name = 'language' WHERE var_name = 'temp_amp'";
$db->query($sql);

out("Changing all 'LONG TEXT' or 'TEXT' to 'BLOB'");
$sql = 'ALTER TABLE `endpointman_product_list` CHANGE `cfg_data` `cfg_data` BLOB NOT NULL';
$db->query($sql);

$sql = 'ALTER TABLE `endpointman_template_list` CHANGE `custom_cfg_data` `custom_cfg_data` BLOB NULL DEFAULT NULL';
$db->query($sql);

$sql = 'ALTER TABLE `endpointman_mac_list` CHANGE `custom_cfg_data` `custom_cfg_data` BLOB NOT NULL, CHANGE `user_cfg_data` `user_cfg_data` BLOB NOT NULL';
$db->query($sql);

$sql = 'ALTER TABLE `endpointman_custom_configs` CHANGE `data` `data` BLOB NOT NULL';
$db->query($sql);

$sql = 'ALTER TABLE `endpointman_product_list` ADD `special_cfgs` BLOB NOT NULL;';
$db->query($sql);

out("Inserting Check for Updates Command");
$sql = 'INSERT INTO `asterisk`.`endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (\'14\', \'check_updates\', \'1\');';
$db->query($sql);

out("Inserting Disable .htaccess command");
$sql = 'INSERT INTO `asterisk`.`endpointman_global_vars` (`idnum`, `var_name`, `value`) VALUES (\'15\', \'disable_htaccess\', \'0\');';
$db->query($sql);

out("Add Automatic Update Check [Can be Disabled]");
$sql = "INSERT INTO cronmanager (module, id, time, freq, lasttime, command) VALUES ('endpointman', 'UPDATES', '23', '24', '0', 'php ".LOCAL_PATH "includes/update_check.php')";
$db->query($sql);

$arp = $endpoint->find_exec("arp");

out("Finding ARP again");
$sql = "UPDATE endpointman_global_vars SET value = '".$arp."' WHERE var_name = 'arp_location'";
$db->query($sql);

out("Update Version Number [Yes again, just in case]");	
$sql = 'UPDATE endpointman_global_vars SET value = \'1.9.9\' WHERE var_name = "version"';
$db->query($sql);

?>