<?php

/**
 * Event Search – Shortcode only
 *
 * AJAX implementations moved to includes/ajax-search.php.
 * Shortcode: [event_search]
 *
 * @package Event_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode [event_search]
 * Displays the search form and results container
 */
function event_manager_search_shortcode() {
    $template = EVENT_MANAGER_PLUGIN_DIR . 'templates/search-form.php';
    if ( ! file_exists( $template ) ) {
        return '';
    }

    ob_start();
    include $template;
    return ob_get_clean();
}

// Register shortcode
add_shortcode( 'event_search', 'event_manager_search_shortcode' );
