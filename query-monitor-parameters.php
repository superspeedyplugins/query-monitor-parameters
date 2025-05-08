<?php
/**
 * Plugin Name: Query Monitor Parameters
 * Description: Extends Query Monitor to show WP_Query parameters
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Plugin URI: https://example.com/plugins/query-monitor-parameters
 * Text Domain: query-monitor-parameters
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include activation check
require_once plugin_dir_path(__FILE__) . 'activation-check.php';

// Define constants
define('QMP_DEBUG', true); // Set to true to enable debug mode

// Store WP_Query parameters globally
global $qmp_query_params;
$qmp_query_params = array();

// Include admin page
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin.php';
}

// Only load if Query Monitor is active
add_action('plugins_loaded', function() {
    if (!class_exists('QueryMonitor')) {
        return;
    }
    
    // Tell Query Monitor to capture arguments for WP_Query->get_posts
    add_filter('qm/trace/show_args', function($show_args) {
        $show_args['WP_Query->get_posts'] = true;
        return $show_args;
    });
    
    // Hook into WP_Query to capture parameters
    add_action('pre_get_posts', 'qmp_capture_query_params');
    
    // Hook into the DB Queries output to add WP_Query parameters
    add_filter('qm/outputter/html', function($output, $collectors) {
        if (isset($output['db_queries'])) {
            // Store the original output_query_row method
            $original_output_query_row = [$output['db_queries'], 'output_query_row'];
            
            // Override the output_query_row method
            $output['db_queries']->output_query_row = function($row, $cols) use ($original_output_query_row) {
                // Call the original method
                call_user_func($original_output_query_row, $row, $cols);
                
                // Check if the caller is WP_Query->get_posts
                if (isset($row['caller']) && strpos($row['caller'], 'WP_Query->get_posts') !== false) {
                    // Get the backtrace data
                    if (isset($row['trace'])) {
                        // Get the full trace
                        $trace = $row['trace']->get_trace();
                        
                        // Find the WP_Query object and its parameters
                        $wp_query_found = false;
                        $query_vars = null;
                        
                        // First try to get the object directly
                        foreach ($trace as $frame) {
                            if (isset($frame['class']) && $frame['class'] === 'WP_Query' && 
                                $frame['function'] === 'get_posts') {
                                
                                // Try to get the WP_Query object
                                if (isset($frame['object']) && is_object($frame['object']) && 
                                    method_exists($frame['object'], 'get_posts')) {
                                    $wp_query = $frame['object'];
                                    $query_vars = isset($wp_query->query_vars) ? $wp_query->query_vars : null;
                                    $wp_query_found = true;
                                    break;
                                }
                                
                                // If we can't get the object, try to get the args
                                if (isset($frame['args']) && !empty($frame['args'])) {
                                    // The first argument to get_posts() is the query vars
                                    $query_vars = $frame['args'][0] ?? null;
                                    $wp_query_found = true;
                                    break;
                                }
                            }
                        }
                        
                        // If we found WP_Query parameters, display them
                        if ($wp_query_found && $query_vars) {
                            // Allow filtering of the query vars before display
                            $query_vars = apply_filters('qmp/query_vars', $query_vars, $row);
                            
                            // Allow complete customization of the output
                            $custom_output = apply_filters('qmp/custom_output', '', $query_vars, $row, $cols);
                            
                            if (!empty($custom_output)) {
                                echo $custom_output;
                            } else {
                                echo '<tr class="qm-wp-query-params">';
                                echo '<td colspan="' . count($cols) . '">';
                                echo '<details>';
                                echo '<summary><strong>WP_Query Parameters</strong></summary>';
                                echo '<pre>';
                                print_r($query_vars);
                                echo '</pre>';
                                echo '</details>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            // Display a message if we couldn't find the parameters
                            echo '<tr class="qm-wp-query-params">';
                            echo '<td colspan="' . count($cols) . '">';
                            echo '<details open>';
                            echo '<summary><strong>WP_Query Parameters</strong></summary>';
                            echo '<p>Could not retrieve WP_Query parameters for this query.</p>';
                            
                            // Add debugging information if debug mode is enabled
                            if (defined('QMP_DEBUG') && QMP_DEBUG) {
                                echo '<p><strong>Debug Info:</strong></p>';
                                echo '<pre>';
                                echo "Caller: " . esc_html($row['caller']) . "\n";
                                echo "Trace frames: " . count($trace) . "\n";
                                
                                // Show the first frame for debugging
                                if (!empty($trace)) {
                                    $first_frame = $trace[0];
                                    echo "First frame:\n";
                                    echo "Class: " . (isset($first_frame['class']) ? esc_html($first_frame['class']) : 'Not set') . "\n";
                                    echo "Function: " . (isset($first_frame['function']) ? esc_html($first_frame['function']) : 'Not set') . "\n";
                                    echo "Has object: " . (isset($first_frame['object']) ? 'Yes' : 'No') . "\n";
                                    echo "Has args: " . (isset($first_frame['args']) ? 'Yes (' . count($first_frame['args']) . ')' : 'No') . "\n";
                                }
                                
                                // Show all frames in trace for more detailed debugging
                                echo "\nAll frames:\n";
                                foreach ($trace as $i => $frame) {
                                    echo "Frame {$i}:\n";
                                    echo "  Class: " . (isset($frame['class']) ? esc_html($frame['class']) : 'Not set') . "\n";
                                    echo "  Function: " . (isset($frame['function']) ? esc_html($frame['function']) : 'Not set') . "\n";
                                    echo "  Has object: " . (isset($frame['object']) ? 'Yes' : 'No') . "\n";
                                    echo "  Has args: " . (isset($frame['args']) ? 'Yes (' . count($frame['args']) . ')' : 'No') . "\n";
                                    
                                    // If this is a WP_Query frame, show more details
                                    if (isset($frame['class']) && $frame['class'] === 'WP_Query') {
                                        echo "  WP_Query frame details:\n";
                                        if (isset($frame['object'])) {
                                            echo "    Object type: " . get_class($frame['object']) . "\n";
                                            echo "    Has query_vars: " . (isset($frame['object']->query_vars) ? 'Yes' : 'No') . "\n";
                                        }
                                        if (isset($frame['args'])) {
                                            echo "    Args count: " . count($frame['args']) . "\n";
                                            echo "    First arg type: " . (isset($frame['args'][0]) ? gettype($frame['args'][0]) : 'Not set') . "\n";
                                            
                                            // If the first arg is an array, show it
                                            if (isset($frame['args'][0]) && is_array($frame['args'][0])) {
                                                echo "    First arg value: " . print_r($frame['args'][0], true) . "\n";
                                            }
                                        }
                                    }
                                }
                                
                                echo '</pre>';
                            } else {
                                echo '<p><em>Enable debug mode in the plugin to see detailed debug information.</em></p>';
                            }
                            
                            echo '</details>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                }
            };
        }
        
        return $output;
    }, 20, 2);
    
    // Add CSS for styling the WP_Query parameters
    add_action('qm/output/styles', function() {
        ?>
        <style>
        /* Query Monitor Parameters Styles - v<?php echo time(); ?> */
        .qm-wp-query-params {
            background-color: #f7f7f7;
        }
        .qm-wp-query-params td {
            padding: 10px !important;
        }
        .qm-wp-query-params pre {
            margin: 0;
            white-space: pre-wrap;
        }
        .qm-wp-query-params details {
            margin-bottom: 0;
        }
        .qm-wp-query-params summary {
            cursor: pointer;
            padding: 5px;
            background-color: #e8e8e8;
            margin-bottom: 5px;
        }
        .qm-wp-query-params summary:hover {
            background-color: #e0e0e0;
        }
        </style>
        <?php
    });
});

/**
 * Capture WP_Query parameters
 *
 * @param WP_Query $query The WP_Query instance
 */
function qmp_capture_query_params($query) {
    global $qmp_query_params;
    
    // Generate a unique ID for this query
    $query_id = spl_object_hash($query);
    
    // Store the query parameters
    $qmp_query_params[$query_id] = array(
        'query_vars' => $query->query_vars,
        'query' => $query->query,
    );
    
    // Debug
    if (defined('QMP_DEBUG') && QMP_DEBUG) {
        error_log('QMP Debug - Captured query params for: ' . $query_id);
        error_log('QMP Debug - Query: ' . print_r($query->query, true));
    }
}