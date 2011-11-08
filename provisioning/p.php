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

if((isset($_REQUEST['putfile'])) && ((getMethod() == 'PUT') OR (getMethod() == 'POST'))) {
    //write log files or other files to drive. not sussed out yet completely.
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

        $sql = 'SELECT id FROM `endpointman_mac_list` WHERE `mac` LIKE CONVERT(_utf8 \'%' . $mac_address . '%\' USING latin1) COLLATE latin1_swedish_ci';

        $mac_id = $endpoint->db->getOne($sql);
        $phone_info = $endpoint->get_phone_info($mac_id);

        if (file_exists(PROVISIONER_BASE . 'setup.php')) {
            if (!class_exists('ProvisionerConfig')) {
                require(PROVISIONER_BASE . 'setup.php');
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
            //end quick fix

            if (class_exists($class)) {
                $provisioner_libary = new $class();
                    //Determine if global settings have been overridden
                $settings = '';
                if ($phone_info['template_id'] > 0) {
                    if (isset($phone_info['template_data_info']['global_settings_override'])) {
                        $settings = unserialize($phone_info['template_data_info']['global_settings_override']);
                    } else {
                        $settings['srvip'] = $endpoint->global_cfg['srvip'];
                        $settings['ntp'] = $endpoint->global_cfg['ntp'];
                        $settings['config_location'] = $endpoint->global_cfg['config_location'];
                        $settings['tz'] = $endpoint->global_cfg['tz'];
                    }
                } else {
                    if (isset($phone_info['global_settings_override'])) {
                        $settings = unserialize($phone_info['global_settings_override']);
                    } else {
                        $settings['srvip'] = $endpoint->global_cfg['srvip'];
                        $settings['ntp'] = $endpoint->global_cfg['ntp'];
                        $settings['config_location'] = $endpoint->global_cfg['config_location'];
                        $settings['tz'] = $endpoint->global_cfg['tz'];
                    }
                }

                //Tell the system who we are and were to find the data.
                $provisioner_libary->root_dir = PROVISIONER_BASE;
                $provisioner_libary->engine = 'asterisk';
                $provisioner_libary->engine_location = $endpoint->global_cfg['asterisk_location'];
                $provisioner_libary->system = 'unix';

                //have to because of versions less than php5.3
                $provisioner_libary->brand_name = $phone_info['directory'];
                $provisioner_libary->family_line = $phone_info['cfg_dir'];

                //Mac Address
                $provisioner_libary->mac = $phone_info['mac'];

                //Phone Model (Please reference family_data.xml in the family directory for a list of recognized models)
                //This has to match word for word. I really need to fix this....
                $provisioner_libary->model = $phone_info['model'];

                //Timezone
                $http_provisioner->DateTimeZone = new DateTimeZone($settings['tz']);


                //Network Time Server
                $provisioner_libary->ntp = $settings['ntp'];

                //Server IP
                $provisioner_libary->server[1]['ip'] = $settings['srvip'];
                $provisioner_libary->server[1]['port'] = 5060;

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
                            $res = $endpoint->db->query($sql);
                            if ($res->numRows()) {
                                $data = $endpoint->db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
                                $provisioner_libary->config_files_override[$data['original_name']] = $data['data'];
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
                            $res = $endpoint->db->query($sql);
                            if ($res->numRows()) {
                                $data = $endpoint->db->getRow($sql, array(), DB_FETCHMODE_ASSOC);
                                $provisioner_libary->config_files_override[$data['original_name']] = $data['data'];
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
                        $full_key = $key;
                        $key = explode('|', $key);
                        $count = count($key);
                        switch ($count) {
                            case 1:
                                if (($endpoint->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
                                    $new_template_data[$full_key] = $global_user_cfg_data[$full_key];
                                } else {
                                    $new_template_data[$full_key] = $global_custom_cfg_data[$full_key];
                                }
                                break;
                            case 2:
                                $breaks = explode('_', $key[1]);
                                if (($endpoint->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
                                    $new_template_data[$breaks[0]][$breaks[2]][$breaks[1]] = $global_user_cfg_data[$full_key];
                                } else {
                                    $new_template_data[$breaks[0]][$breaks[2]][$breaks[1]] = $global_custom_cfg_data[$full_key];
                                }
                                break;
                            case 3:
                                if (($endpoint->global_cfg['enable_ari'] == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
                                    $line_ops[$key[1]][$key[2]] = $global_user_cfg_data[$full_key];
                                } else {
                                    $line_ops[$key[1]][$key[2]] = $global_custom_cfg_data[$full_key];
                                }
                                break;
                        }
                    }
                }

                //Loop through Lines!
                foreach ($phone_info['line'] as $line) {
                    $provisioner_libary->lines[$line['line']] = array('ext' => $line['ext'], 'secret' => $line['secret'], 'displayname' => $line['description']);
                }

                //testing this out
                foreach ($line_ops as $key => $data) {
                    if (isset($line_ops[$key])) {
                        $provisioner_libary->lines[$key]['options'] = $line_ops[$key];
                    }
                }

                $provisioner_libary->server_type = 'dynamic';
                $provisioner_libary->provisioning_type = 'http';
                $provisioner_libary->provisioning_path = $provis_ip.dirname($_SERVER['REQUEST_URI']);

                //Set Variables according to the template_data files included. We can include different template.xml files within family_data.xml also one can create
                //template_data_custom.xml which will get included or template_data_<model_name>_custom.xml which will also get included
                //line 'global' will set variables that aren't line dependant
                $provisioner_libary->options = $new_template_data;

                //Setting a line variable here...these aren't defined in the template_data.xml file yet. however they will still be parsed
                //and if they have defaults assigned in a future template_data.xml or in the config file using pipes (|) those will be used, pipes take precedence
                $provisioner_libary->processor_info = "EndPoint Manager Version " . $endpoint->global_cfg['version'];

                $files = $provisioner_libary->generate_config();

                if(array_key_exists($filename, $files)) {
                    echo $files[$filename];
                } else {
                    header("HTTP/1.0 404 Not Found");
                    die();
                }
            } else {
                header("HTTP/1.0 500 Internal Server Error");
                die();
            }
        } else {
            header("HTTP/1.0 500 Internal Server Error");
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