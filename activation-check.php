<?php
/**
 * Activation check for Query Monitor Parameters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if Query Monitor is active when this plugin is activated
 */
function qmp_activation_check() {
    if (!class_exists('QueryMonitor')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<p>' . 
            __('Query Monitor Parameters requires Query Monitor to be installed and activated.', 'query-monitor-parameters') . 
            '</p><p>' . 
            __('Please install and activate Query Monitor first.', 'query-monitor-parameters') . 
            '</p><p><a href="' . admin_url('plugins.php') . '">' . 
            __('Back to Plugins', 'query-monitor-parameters') . 
            '</a></p>'
        );
    }
}

// Hook into plugin activation
register_activation_hook(plugin_basename(dirname(__FILE__) . '/query-monitor-parameters.php'), 'qmp_activation_check');

/**
 * Display admin notice if Query Monitor is not active
 */
function qmp_admin_notice() {
    if (!class_exists('QueryMonitor')) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Query Monitor Parameters requires Query Monitor to be installed and activated.', 'query-monitor-parameters'); ?></p>
        </div>
        <?php
    }
}

// Hook into admin notices
add_action('admin_notices', 'qmp_admin_notice');