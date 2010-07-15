<?PHP
function endpointman_get_config($engine) {
  global $db;
  global $ext; 
  global $core_conf;
  switch($engine) {
    case "asterisk":
	    if (isset($core_conf) && is_a($core_conf, "core_conf")) {
        $core_conf->addSipNotify('polycom-check-cfg',array('Event' => 'check-sync','Content-Length' => '0'));
        $core_conf->addSipNotify('polycom-reboot',array('Event' => 'check-sync','Content-Length' => '0'));
        $core_conf->addSipNotify('sipura-check-cfg',array('Event' => 'resync','Content-Length' => '0'));
        $core_conf->addSipNotify('grandstream-check-cfg',array('Event' => 'sys-control'));
        $core_conf->addSipNotify('cisco-check-cfg',array('Event' => 'check-sync','Content-Length' => '0'));
        $core_conf->addSipNotify('reboot-snom',array('Event' => 'reboot','Content-Length' => '0'));
        $core_conf->addSipNotify('aastra-check-cfg',array('Event' => 'check-sync','Content-Length' => '0'));
        $core_conf->addSipNotify('linksys-cold-restart',array('Event' => 'reboot_now','Content-Length' => '0'));
        $core_conf->addSipNotify('linksys-warm-restart',array('Event' => 'restart_now','Content-Length' => '0'));
        $core_conf->addSipNotify('spa-reboot',array('Event' => 'reboot','Content-Length' => '0'));
      }
    break;
  }
}
