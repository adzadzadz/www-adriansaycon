<?php

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

delete_option("swcfpc_config");
delete_option("swcfpc_version");
