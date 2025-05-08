<?php
/**
 * Admin page for Query Monitor Parameters
 * 
 * This file adds an admin page to test the Query Monitor Parameters plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add the admin menu
add_action('admin_menu', 'qmp_add_admin_menu');

/**
 * Add the admin menu
 */
function qmp_add_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Query Monitor Parameters',
        'QM Parameters',
        'manage_options',
        'query-monitor-parameters',
        'qmp_admin_page'
    );
}

/**
 * Display the admin page
 */
function qmp_admin_page() {
    ?>
    <div class="wrap">
        <h1>Query Monitor Parameters</h1>
        <p>This page helps test the Query Monitor Parameters plugin.</p>
        
        <div class="card">
            <h2>Run Test</h2>
            <p>Click the button below to run a test query and check if Query Monitor Parameters is working.</p>
            <p>
                <a href="<?php echo esc_url(admin_url('tools.php?page=query-monitor-parameters&action=test')); ?>" class="button button-primary">Run Test</a>
            </p>
        </div>
        
        <?php if (isset($_GET['action']) && $_GET['action'] === 'test') : ?>
            <div class="card" style="margin-top: 20px;">
                <h2>Test Results</h2>
                <?php include_once plugin_dir_path(__FILE__) . 'test.php'; ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Debug Information</h2>
            <p>Here's some debug information about the plugin:</p>
            
            <h3>Plugin Status</h3>
            <ul>
                <li>Query Monitor active: <?php echo class_exists('QueryMonitor') ? 'Yes' : 'No'; ?></li>
                <li>QMP_DEBUG enabled: <?php echo defined('QMP_DEBUG') && QMP_DEBUG ? 'Yes' : 'No'; ?></li>
            </ul>
            
            <h3>Captured Queries</h3>
            <?php
            global $qmp_query_params;
            if (!empty($qmp_query_params)) {
                echo '<p>Number of captured queries: ' . count($qmp_query_params) . '</p>';
                echo '<pre>';
                print_r($qmp_query_params);
                echo '</pre>';
            } else {
                echo '<p>No queries captured yet.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

// No need to include this file again, it's already included in the main plugin file