<?php

/**
 * Endpoint Manager config File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
// Check for safe mode

if (ini_get('safe_mode')) {
    die(_('Turn Off Safe Mode'));
}

if (PHP_VERSION < '5.3.0') {
    die(_('PHP Version MUST be greater than') . ' 5.3.0!');
}

include 'includes/functions.inc';

$debug = NULL;

$endpoint = new endpointmanager();

if (!is_writeable(LOCAL_PATH)) {
    if (!chmod(LOCAL_PATH, 0764)) {
        die('My own path is not writable (' . LOCAL_PATH . ')');
    }
}

if (!is_writeable(PHONE_MODULES_PATH)) {
    chmod(PHONE_MODULES_PATH, 0764);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PHONE_MODULES_PATH), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        chmod($item, 0764);
    }
}

if ($amp_conf['AMPENGINE'] != 'asterisk') {
    die(_("Sorry, Only Asterisk is supported currently"));
}

if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
} else {
    $page = "";
}


if ($endpoint->global_cfg['debug']) {
    $debug .= "Request Variables: \n" . print_r($_REQUEST, TRUE);
}