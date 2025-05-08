<?php
/**
 * Uninstall Query Monitor Parameters
 *
 * This file runs when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Currently, this plugin doesn't store any data in the database,
// so there's nothing to clean up on uninstall.
// This file is included for future use if database options are added.