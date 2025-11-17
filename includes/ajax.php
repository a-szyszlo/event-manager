<?php
/**
 * Central AJAX hooks registrar
 *
 * Keeps add_action calls in one place for better discoverability.
 * Implementations live in their feature files.
 *
 * @package Event_Manager
 */

// Security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Registration endpoint
add_action( 'wp_ajax_register_event', 'event_manager_ajax_register_event' );
add_action( 'wp_ajax_nopriv_register_event', 'event_manager_ajax_register_event' );

// Search endpoint
add_action( 'wp_ajax_event_search_ajax', 'event_manager_ajax_search' );
add_action( 'wp_ajax_nopriv_event_search_ajax', 'event_manager_ajax_search' );

// Search nonce refresh
add_action( 'wp_ajax_event_search_nonce', 'event_manager_ajax_search_nonce' );
add_action( 'wp_ajax_nopriv_event_search_nonce', 'event_manager_ajax_search_nonce' );
