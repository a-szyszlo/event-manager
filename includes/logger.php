<?php
/**
 * Event Manager - Logger Helper
 *
 * Provides consistent error logging to WordPress debug.log file.
 * All errors from this plugin are logged with [Event Manager] prefix.
 *
 * Enable logging in wp-config.php:
 *   define( 'WP_DEBUG', true );
 *   define( 'WP_DEBUG_LOG', true );
 *   define( 'WP_DEBUG_DISPLAY', false );
 *
 * Logs appear in: wp-content/debug.log
 *
 * @package Event_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Log a message or error to WordPress debug.log
 *
 * @param string $message The message to log
 * @param string $level   Log level (info, warning, error). Optional, defaults to 'info'.
 * @param array  $context Additional context data to log (optional)
 * @return void
 */
function event_manager_log( $message, $level = 'info', $context = array() ) {
    if ( ! WP_DEBUG_LOG ) {
        return;
    }

    $timestamp = current_time( 'Y-m-d H:i:s' );
    $level_upper = strtoupper( $level );
    $context_str = '';

    if ( ! empty( $context ) && is_array( $context ) ) {
        $context_str = ' | Context: ' . wp_json_encode( $context );
    }

    $log_message = "[{$timestamp}] [Event Manager] [{$level_upper}] {$message}{$context_str}";

    error_log( $log_message );
}

/**
 * Log an error (shortcut for event_manager_log with 'error' level)
 *
 * @param string $message The error message
 * @param array  $context Additional context data (optional)
 * @return void
 */
function event_manager_log_error( $message, $context = array() ) {
    event_manager_log( $message, 'error', $context );
}

/**
 * Log a warning (shortcut for event_manager_log with 'warning' level)
 *
 * @param string $message The warning message
 * @param array  $context Additional context data (optional)
 * @return void
 */
function event_manager_log_warning( $message, $context = array() ) {
    event_manager_log( $message, 'warning', $context );
}

/**
 * Log info (shortcut for event_manager_log with 'info' level)
 *
 * @param string $message The info message
 * @param array  $context Additional context data (optional)
 * @return void
 */
function event_manager_log_info( $message, $context = array() ) {
    event_manager_log( $message, 'info', $context );
}
?>
