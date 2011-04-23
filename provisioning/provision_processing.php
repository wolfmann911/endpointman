<?PHP

require('/etc/freepbx.conf');
require('/var/www/html/admin/modules/endpointman/includes/functions.inc');

$endpoint = new endpointmanager();

$path_parts = explode(".", $_REQUEST['request']);

$mac = $path_parts[0];

$sql = 'SELECT id FROM `endpointman_mac_list` WHERE `mac` LIKE CONVERT(_utf8 \'%'.$mac.'%\' USING latin1) COLLATE latin1_swedish_ci';

$mac_id = $endpoint->db->getOne($sql);

if(!$mac_id) {
	switch($_REQUEST['request']) {
		case "y000000000004.cfg":
			echo "#left blank";
			break;
		case "aastra.cfg"	:
			echo "#left blank";
			break;
	}
} else {
	$phone_info = $endpoint->get_phone_info($mac_id);
	$files = $endpoint->prepare_configs($phone_info,FALSE,FALSE);
	echo $files[$_REQUEST['request']];
}
?>