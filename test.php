<?php
/**
 * Test script for Query Monitor Parameters
 * 
 * This script creates a WP_Query and outputs debug information to help troubleshoot
 * the Query Monitor Parameters plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(__FILE__))) . '/');
    require_once(ABSPATH . 'wp-load.php');
}

// Create a WP_Query
$args = array(
    'post_type' => 'page',
    'posts_per_page' => 5,
    'orderby' => 'title',
    'order' => 'ASC',
);

echo '<h1>Testing WP_Query Parameters</h1>';
echo '<p>Creating a WP_Query with the following parameters:</p>';
echo '<pre>';
print_r($args);
echo '</pre>';

// Create the query
$query = new WP_Query($args);

// Output the query
echo '<h2>Query Results</h2>';
echo '<p>Found ' . $query->found_posts . ' posts</p>';

// Output the SQL
echo '<h2>SQL Query</h2>';
echo '<pre>';
echo $query->request;
echo '</pre>';

// Output debug information
echo '<h2>Debug Information</h2>';
echo '<p>Query Monitor Parameters should capture this query and display its parameters.</p>';
echo '<p>Check the Query Monitor panel for details.</p>';

// Output the object hash
echo '<h3>Object Hash</h3>';
echo '<p>WP_Query object hash: ' . spl_object_hash($query) . '</p>';

// Output the backtrace
echo '<h3>Backtrace</h3>';
echo '<pre>';
debug_print_backtrace();
echo '</pre>';

// Done
echo '<p>Test complete. Please check the Query Monitor panel.</p>';