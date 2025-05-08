# Implementation Plan for Query Monitor Parameters

## Overview

This plugin extends Query Monitor to show WP_Query parameters when database queries are triggered by WP_Query->get_posts. This provides valuable insight into how WordPress is building its queries.

## Implementation Steps

1. **Create the main plugin file**
   - Create `query-monitor-parameters.php` in the plugin directory
   - Add the plugin header information
   - Check if Query Monitor is active before loading the plugin functionality
   - Define debug mode constant

2. **Hook into WordPress**
   - Use the `pre_get_posts` action to capture WP_Query parameters
   - Store the parameters in a global variable with a unique ID for each query
   - Extend the DB Queries output to display WP_Query parameters
   - Add filters for customization

3. **Add styling**
   - Add CSS to style the WP_Query parameters display
   - Include timestamp to prevent caching

4. **Add supporting files**
   - Create activation check to ensure Query Monitor is active
   - Add index.php to prevent directory listing
   - Add uninstall.php for clean uninstallation

## Code Explanation

### 1. Debug Mode

```php
// Define constants
define('QMP_DEBUG', true); // Set to true to enable debug mode
```

The debug mode constant allows developers to enable detailed debugging information when troubleshooting issues with the plugin.

### 2. Capturing WP_Query Parameters

```php
// Store WP_Query parameters globally
global $qmp_query_params;
$qmp_query_params = array();

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
        'request' => null, // Will be filled later
        'caller' => null,  // Will be filled later
    );
    
    // Debug
    if (defined('QMP_DEBUG') && QMP_DEBUG) {
        error_log('QMP Debug - Captured query params for: ' . $query_id);
        error_log('QMP Debug - Query: ' . print_r($query->query, true));
    }
}

// Hook into WP_Query to capture parameters
add_action('pre_get_posts', 'qmp_capture_query_params');
```

This code:
1. Sets up a global variable to store WP_Query parameters
2. Creates a function to capture parameters from each WP_Query instance
3. Uses `spl_object_hash()` to generate a unique ID for each query object
4. Stores both the original query args and the expanded query vars
5. Hooks into the `pre_get_posts` action to capture parameters as they're being used

### 3. Extending the DB Queries Output

```php
function qmp_register_output($output, $collectors) {
    // Get the db_queries collector
    $collector = QM_Collectors::get('db_queries');
    
    // Make sure we have the collector and the output
    if ($collector && isset($output['db_queries'])) {
        // Store the original output_query_row method
        $original_output_query_row = [$output['db_queries'], 'output_query_row'];
        
        // Override the output_query_row method
        $output['db_queries']->output_query_row = function($row, $cols) use ($original_output_query_row) {
            // Call the original method
            call_user_func($original_output_query_row, $row, $cols);
            
            // Now add our custom output for WP_Query parameters
            // Check if the caller is WP_Query->get_posts
            if (isset($row['caller']) && strpos($row['caller'], 'WP_Query->get_posts') !== false) {
                global $qmp_query_params;
                
                // Get the backtrace data
                if (isset($row['trace'])) {
                    $trace = $row['trace']->get_trace();
                    
                    // Find the WP_Query object
                    $wp_query_found = false;
                    $query_vars = null;
                    $query_id = null;
                    
                    // Look through the trace for WP_Query objects
                    foreach ($trace as $frame) {
                        if (isset($frame['class']) && $frame['class'] === 'WP_Query' &&
                            $frame['function'] === 'get_posts') {
                            
                            // If we have the object, get its ID
                            if (isset($frame['object']) && is_object($frame['object']) &&
                                method_exists($frame['object'], 'get_posts')) {
                                $query_id = spl_object_hash($frame['object']);
                                $wp_query_found = true;
                                break;
                            }
                        }
                    }
                    
                    // If we found a WP_Query object and have its parameters
                    if ($wp_query_found && $query_id && isset($qmp_query_params[$query_id])) {
                        $query_vars = $qmp_query_params[$query_id]['query_vars'];
                        $query = $qmp_query_params[$query_id]['query'];
                        
                        // Allow filtering of the query vars before display
                        $query_vars = apply_filters('qmp/query_vars', $query_vars, $row);
                        $query = apply_filters('qmp/query', $query, $row);
                        
                        // Allow complete customization of the output
                        $custom_output = apply_filters('qmp/custom_output', '', $query_vars, $query, $row, $cols);
                        
                        if (!empty($custom_output)) {
                            echo $custom_output;
                        } else {
                            echo '<tr class="qm-wp-query-params">';
                            echo '<td colspan="' . count($cols) . '">';
                            echo '<details>';
                            echo '<summary><strong>WP_Query Parameters</strong></summary>';
                            
                            // Show the original query args (more concise)
                            echo '<h3>Query</h3>';
                            echo '<pre>';
                            print_r($query);
                            echo '</pre>';
                            
                            // Show the expanded query vars
                            echo '<h3>Query Vars</h3>';
                            echo '<pre>';
                            print_r($query_vars);
                            echo '</pre>';
                            
                            echo '</details>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        // Display debug information if debug mode is enabled
                        if (defined('QMP_DEBUG') && QMP_DEBUG) {
                            // Debug output code...
                        }
                    }
                }
            }
        };
    }
    
    return $output;
}

// Register our custom outputter
add_filter('qm/outputter/html', 'qmp_register_output', 20, 2);
```

This code:
1. Hooks into the `qm/outputter/html` filter to modify the HTML output of Query Monitor
2. Stores the original `output_query_row` method so we can call it first
3. Overrides the `output_query_row` method to add our custom functionality
4. Checks if the current query was called by WP_Query->get_posts
5. If it was, looks for the WP_Query object in the trace and gets its unique ID
6. Uses the ID to retrieve the stored parameters from our global variable
7. Provides filters for customizing the output:
   - `qmp/query_vars` - Filter the query vars before display
   - `qmp/query` - Filter the original query args before display
   - `qmp/custom_output` - Completely customize the output
8. Displays both the original query args and the expanded query vars
9. Shows debug information if debug mode is enabled and parameters couldn't be found

### 4. Adding CSS Styling with Cache Busting

```php
function qmp_add_styles() {
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
    .qm-wp-query-params h3 {
        margin: 10px 0 5px;
        font-size: 14px;
        font-weight: bold;
    }
    </style>
    <?php
}

// Add CSS for styling the WP_Query parameters
add_action('qm/output/styles', 'qmp_add_styles');
```

This adds CSS styling with a timestamp to prevent caching, ensuring that style changes are immediately visible.

## Implemented Files

1. **query-monitor-parameters.php** - Main plugin file
2. **activation-check.php** - Ensures Query Monitor is active
3. **index.php** - Prevents directory listing
4. **uninstall.php** - Handles plugin uninstallation
5. **README.md** - Documentation
6. **IMPLEMENTATION.md** - Implementation details

## Advantages of This Approach

1. **Direct Parameter Capture**: By hooking into `pre_get_posts`, we capture the WP_Query parameters directly from the source, before they're processed or modified.

2. **Object Identification**: Using `spl_object_hash()` gives us a reliable way to identify and track each WP_Query instance.

3. **Complete Parameter Set**: We capture both the original query args and the expanded query vars, providing a complete picture of the query.

4. **No SQL Matching Required**: We don't need to match SQL queries with parameters, which can be error-prone due to variations in SQL formatting.

5. **Minimal Performance Impact**: The approach has minimal performance impact since we're only storing the parameters we need.

## Customization Options

The plugin provides several ways to customize its behavior:

1. **Debug Mode** - Enable/disable debug mode by changing the QMP_DEBUG constant
2. **Query Vars Filter** - Use the qmp/query_vars filter to modify the displayed parameters
3. **Query Filter** - Use the qmp/query filter to modify the displayed original query args
4. **Custom Output Filter** - Use the qmp/custom_output filter to completely customize the output

## Future Enhancements

Potential future enhancements for the plugin:

1. **Admin Settings Page** - Add a settings page to configure the plugin through the admin interface
2. **Additional Parameter Sources** - Capture parameters from other sources besides WP_Query
3. **Export Functionality** - Allow exporting of captured parameters for further analysis
4. **Parameter Comparison** - Add ability to compare original query args with final query vars to see how WordPress modifies them
5. **Performance Optimization** - Add options to control when parameter capture is enabled to minimize performance impact