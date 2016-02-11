<?PHP
/**
 * Endpoint Manager Uninstaller
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Endpoint Manager
 */
$aridir = $amp_conf['AMPWEBROOT'].'/recordings/modules';


global $endpoint;
$endpoint = new endpointmanager();
global $db;

out("Removing Phone Modules Directory");
$endpoint->rmrf(PHONE_MODULES_PATH);
exec("rm -R ". PHONE_MODULES_PATH);

out("Dropping all relevant tables");
$sql = "DROP TABLE `endpointman_brand_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_global_vars`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_mac_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_line_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_model_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_oui_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_product_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_template_list`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_time_zones`";
$result = $db->query($sql);

$sql = "DROP TABLE `endpointman_custom_configs`";
$result = $db->query($sql);


//Do unlinks ourself because retrieve_conf doesn't always remove stuff...



out('Removing symlink to web provisioner');
if(is_link($amp_conf['AMPWEBROOT']."/provisioning")) {
    unlink($amp_conf['AMPWEBROOT']."/provisioning");
}

if(!is_link($amp_conf['AMPWEBROOT'].'/admin/assets/endpointman')) {
    $endpoint->rmrf($amp_conf['AMPWEBROOT'].'/admin/assets/endpointman');
}