<?php
// Function to log events
function naat_log($message) {
    error_log("NAAT: naat_log function called.");

    $logging_enabled = get_option('naat_logging_enabled', false);
    error_log("NAAT: Logging enabled status - " . ($logging_enabled ? "true" : "false"));

    if (!$logging_enabled) {
        error_log("NAAT: Logging is disabled.");
        return;
    }
    error_log("NAAT: Logging is enabled.");

    $upload_dir = wp_upload_dir();
    error_log("NAAT: Upload directory - " . print_r($upload_dir, true));
    $log_file = $upload_dir['basedir'] . '/naat_log.txt';
    error_log("NAAT: Log file path - " . $log_file);

    // Check if log file is writable or can be created
    if (!file_exists($log_file)) {
        error_log("NAAT: Log file does not exist.");
        if (!is_writable($upload_dir['basedir'])) {
            error_log("NAAT: Log directory is not writable.");
            return;
        }
        if (!touch($log_file)) {
            error_log("NAAT: Log file cannot be created.");
            return;
        } else {
            error_log("NAAT: Log file created successfully.");
        }
    } else {
        error_log("NAAT: Log file exists.");
    }

    if (!is_writable($log_file)) {
        error_log("NAAT: Log file is not writable.");
        return;
    } else {
        error_log("NAAT: Log file is writable.");
    }

    $time = current_time('Y-m-d H:i:s');
    $log_entry = "{$time} - {$message}\n";

    if (file_put_contents($log_file, $log_entry, FILE_APPEND) === false) {
        error_log("NAAT: Failed to write to log file.");
    } else {
        error_log("NAAT: Successfully wrote to log file.");
    }
}
