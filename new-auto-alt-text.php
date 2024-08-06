<?php
/*
Plugin Name: New Auto Alt Text
Description: Automatically generates alt text for images using OpenAI API.
Version: 1.0
Author: Preston Gregg
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

include_once dirname(__FILE__) . '/includes/admin-settings.php';
include_once dirname(__FILE__) . '/includes/update-alt-text.php';

// Log the settings update
function naat_log_settings_update() {
    $upload_dir = wp_upload_dir();
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    $time = current_time('Y-m-d H:i:s');
    $version = '1.0'; // Update this to your plugin version
    $log_entry = "{$time} - Plugin updated to version {$version}. Settings saved:\n";
    $log_entry .= "Logging Enabled: " . get_option('naat_logging_enabled') . "\n";
    $log_entry .= "OpenAI API Token: " . get_option('naat_openai_api_token') . "\n";
    $log_entry .= "Authorized Post Types: " . get_option('naat_authorized_post_types') . "\n";
    $log_entry .= "Single Post: " . get_option('naat_single_post') . "\n";
    $log_entry .= "Multi Post: " . get_option('naat_multi_post') . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
add_action('update_option_naat_logging_enabled', 'naat_log_settings_update');
add_action('update_option_naat_openai_api_token', 'naat_log_settings_update');
add_action('update_option_naat_authorized_post_types', 'naat_log_settings_update');
add_action('update_option_naat_single_post', 'naat_log_settings_update');
add_action('update_option_naat_multi_post', 'naat_log_settings_update');
