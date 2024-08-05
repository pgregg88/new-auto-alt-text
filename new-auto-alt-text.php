<?php
/*
Plugin Name: New Auto Alt Text
Description: A simple plugin to update image alt text with logging.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Activation hook to set up initial options and create log file
function naat_activate() {
    if (get_option('naat_logging_enabled') === false) {
        add_option('naat_logging_enabled', '1');
    }

    // Create a log file upon activation
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $time = current_time('Y-m-d H:i:s');
    $log_entry = "{$time} - Plugin activated\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
register_activation_hook(__FILE__, 'naat_activate');

// Include necessary files
include_once plugin_dir_path(__FILE__) . 'includes/log-functions.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';

// Test function to log a message
function naat_test_logging() {
    naat_log('This is a test log entry.');
    echo '<div class="notice notice-success is-dismissible"><p>Test log entry added.</p></div>';
}
add_action('admin_notices', 'naat_test_logging');
