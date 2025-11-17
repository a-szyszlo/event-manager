<?php
/**
 * Event Manager – Uninstall cleanup
 *
 * This file is executed when the plugin is uninstalled via WordPress.
 * Keep cleanup minimal and safe.
 *
 * @package Event_Manager
 */

// Exit if accessed directly or if not called by WordPress uninstall routine
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin options
 */
delete_option( 'event_manager_events_page_id' );


