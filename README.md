# Query Monitor Parameters

A WordPress plugin that extends Query Monitor to show WP_Query parameters when the caller is WP_Query->get_posts.

## Description

This plugin adds functionality to Query Monitor to display the parameters used in WP_Query calls. This is particularly useful for debugging and understanding how WordPress is building its database queries.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/query-monitor-parameters` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure Query Monitor is installed and activated

## How It Works

The plugin hooks into WordPress using the `pre_get_posts` action to capture WP_Query parameters as they're being used. It then extends the DB Queries output in Query Monitor to display these parameters alongside the database queries.

## Features

- Shows WP_Query parameters for database queries triggered by WP_Query->get_posts
- Displays both the original query args and the expanded query vars
- Shows parameters in a collapsible section below the query
- Debug mode for troubleshooting issues with parameter capture
- Filters for customizing the output

## Debug Mode

The plugin includes a debug mode that can be enabled by setting the `QMP_DEBUG` constant to `true` in the main plugin file. When debug mode is enabled, the plugin will display detailed debugging information when it can't retrieve WP_Query parameters.

## Filters

The plugin provides the following filters for customization:

- `qmp/query_vars` - Filter the query vars before display
- `qmp/query` - Filter the original query args before display
- `qmp/custom_output` - Completely customize the output of the WP_Query parameters

Example usage:

```php
// Filter the query vars to remove unnecessary parameters
add_filter('qmp/query_vars', function($query_vars, $row) {
    // Remove internal parameters
    unset($query_vars['suppress_filters']);
    unset($query_vars['cache_results']);
    return $query_vars;
}, 10, 2);

// Filter the original query args
add_filter('qmp/query', function($query, $row) {
    // Modify or enhance the query args
    return $query;
}, 10, 2);

// Completely customize the output
add_filter('qmp/custom_output', function($output, $query_vars, $query, $row, $cols) {
    if (empty($output)) {
        $output = '<tr class="qm-wp-query-params">';
        $output .= '<td colspan="' . count($cols) . '">';
        $output .= '<div class="my-custom-output">';
        $output .= '<h3>Custom WP_Query Parameters Display</h3>';
        $output .= '<h4>Original Query</h4>';
        $output .= '<pre>' . print_r($query, true) . '</pre>';
        $output .= '<h4>Expanded Query Vars</h4>';
        $output .= '<pre>' . print_r($query_vars, true) . '</pre>';
        $output .= '</div>';
        $output .= '</td>';
        $output .= '</tr>';
    }
    return $output;
}, 10, 5);
```

## Plugin Files

### query-monitor-parameters.php

```php
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
                    // Extract WP_Query parameters from the trace
                    if (isset($row['trace'])) {
                        $trace = $row['trace']->get_trace();
                        foreach ($trace as $frame) {
                            if (isset($frame['class']) && $frame['class'] === 'WP_Query' && 
                                $frame['function'] === 'get_posts' && isset($frame['object'])) {
                                
                                // Get the WP_Query object
                                $wp_query = $frame['object'];
                                
                                // Display the WP_Query parameters
                                echo '<tr class="qm-wp-query-params">';
                                echo '<td colspan="' . count($cols) . '">';
                                echo '<details>';
                                echo '<summary><strong>WP_Query Parameters</strong></summary>';
                                echo '<pre>';
                                print_r($wp_query->query_vars);
                                echo '</pre>';
                                echo '</details>';
                                echo '</td>';
                                echo '</tr>';
                                
                                break;
                            }
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
        </style>
        <?php
    });
});
```

## Next Steps

To implement this plugin:

1. Create a file named `query-monitor-parameters.php` in the plugin directory
2. Copy the PHP code above into the file
3. Activate the plugin through the WordPress admin interface

## License

GPL v2 or later
