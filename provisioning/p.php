<?PHP
$bootstrap_settings['freepbx_auth'] = false;
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
    include_once('/etc/asterisk/freepbx.conf');
}

define('PROVISIONER_BASE', $amp_conf['AMPWEBROOT'].'/admin/modules/_ep_phone_modules/');
require($amp_conf['AMPWEBROOT'].'/admin/modules/endpointman/includes/functions.inc');
require($amp_conf['AMPWEBROOT'].'/admin/modules/endpointman/includes/timezone.inc');

$endpoint = new endpointmanager();

//Check if it's allowed in FreePBX through Endpoint Manager first
if ((!isset($endpoint->global_cfg['server_type'])) OR ($endpoint->global_cfg['server_type'] != 'http')) {
    header('HTTP/1.1 403 Forbidden');
    die();
}

$provis_ip = $endpoint->global_cfg['srvip'];

if(((getMethod() == 'PUT') OR (getMethod() == 'POST'))) {
    //write log files or other files to drive. not sussed out yet completely.

    /* PUT data comes in on the stdin stream */
    //$putdata = fopen("php://input", "r");

    /* Open a file for writing */
    //$fp = fopen($endpoint->global_cfg['config_location'] . $_SERVER['REDIRECT_URL'], "a");

    /* Read the data 1 KB at a time
        and write to the file */
    //while ($data = fread($putdata, 1024))
    //    fwrite($fp, $data);

    /* Close the streams */
    //fclose($fp);
    //fclose($putdata);
    header('HTTP/1.1 200 OK');
    die();
}

if(getMethod() == "GET") {
    # Workaround for SPAs that don't actually request their type of device
    # Assume they're 504G's. Faulty in firmware 7.4.3a
    $filename = basename($_SERVER["REQUEST_URI"]);

    $web_path = 'http://'.$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]).'/';
    /*
    if ($filename == "p.php") { 
            $filename = "spa502G.cfg";
            $_SERVER['REQUEST_URI']=$_SERVER['REQUEST_URI']."/spa502G.cfg";
            $web_path = $web_path."p.php/";
    }
     * 
     */

    # Firmware Linksys/SPA504G-7.4.3a is broken and MUST be upgraded.
    if (preg_match('/7.4.3a/', $_SERVER['HTTP_USER_AGENT'])) {
            $str = '<flat-profile><Upgrade_Enable group="Provisioning/Firmware_Upgrade">Yes</Upgrade_Enable>';
            $str .= '<Upgrade_Rule group="Provisioning/Firmware_Upgrade">http://'.$provis_ip.'/current.bin</Upgrade_Rule></flat-profile>';
            echo $str;
            exit;
    }

    $filename = str_replace('p.php/','', $filename);
    $strip = str_replace('spa', '', $filename);
    
    if(preg_match('/[0-9A-Fa-f]{12}/i', $strip, $matches) && !(preg_match('/[0]{10}[0-9]{2}/i',$strip))) {
        $mac_address = $matches[0];
        
        $sql = "SELECT id FROM `endpointman_mac_list` WHERE `mac` LIKE '%" . $mac_address . "%'";

        $mac_id = $endpoint->eda->sql($sql, 'getOne');
        $phone_info = $endpoint->get_phone_info($mac_id);

        $files = $endpoint->prepare_configs($phone_info,FALSE,FALSE);
        
        if(!$files) {
            header("HTTP/1.0 500 Internal Server Error");
            die();
        }
        
        if (array_key_exists($filename, $files)) {
            echo $files[$filename];
        } else {
            header("HTTP/1.0 404 Not Found");
            die();
        }

    } else {
        require_once (PROVISIONER_BASE.'endpoint/base.php');
        $data = Provisioner_Globals::dynamic_global_files($filename,$endpoint->global_cfg['config_location'],$web_path);
        if($data !== FALSE) {
            echo $data;
        } else {
            header("HTTP/1.0 404 Not Found");
        }
    }
} else {
    header('HTTP/1.1 403 Forbidden');
    die();
}


function getMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        $override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');
        if ($method == 'POST' && strtoupper($override) == 'PUT') {
                $method = 'PUT';
        } elseif ($method == 'POST' && strtoupper($override) == 'DELETE') {
                $method = 'DELETE';
        }
        return $method;
}