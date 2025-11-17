<?php

/**
 * Plugin Name: Event Manager
 * Plugin URI: https://github.com/a-szyszlo/event-manager
 * Description: Wtyczka do zarządzania wydarzeniami i rejestracją uczestników
 * Version: 1.0.0
 * Author: Aleksandra Szyszło
 * Author URI: https://github.com/a-szyszlo
 * Text Domain: event-manager
 * Requires at least: 6.6
 * Requires PHP: 8.0
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('EVENT_MANAGER_VERSION', '1.0.0');
define('EVENT_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Event Manager plugin class
 */
class Event_Manager
{

    /**
     * Singleton instance
     */
    private static $instance = null;
    /**
     * Flag to ensure we print the search form only once per request
     */
    private $search_output_printed = false;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize plugin
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load dependency files
     */
    private function load_dependencies()
    {
        // Logger - load first to log remaining processes
        require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/logger.php';

        // Shared utilities (IP helper, etc.)
        $utils_file = EVENT_MANAGER_PLUGIN_DIR . 'includes/utils.php';
        if ( file_exists( $utils_file ) ) {
            require_once $utils_file;
        }

        // Register Custom Post Type and Taxonomies
        require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/cpt-registration.php';

        // Configure ACF fields
        require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/acf-fields.php';

        // AJAX registration implementation (mirrors search structure)
        $ajax_registration = EVENT_MANAGER_PLUGIN_DIR . 'includes/ajax-registration.php';
        if ( file_exists( $ajax_registration ) ) {
            require_once $ajax_registration;
        } else {
            // Log instead of falling back to legacy file
            if ( function_exists( 'event_manager_log_error' ) ) {
                event_manager_log_error( 'Missing includes/ajax-registration.php – registration endpoint will be unavailable.' );
            }
        }

        // Event search shortcode
        require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/event-search.php';

        // AJAX search implementation (mirrors ajax-registration structure)
        $ajax_search = EVENT_MANAGER_PLUGIN_DIR . 'includes/ajax-search.php';
        if ( file_exists( $ajax_search ) ) {
            require_once $ajax_search;
        }

        // Central AJAX hooks registrar (keeps add_action in one place)
        $ajax_registrar = EVENT_MANAGER_PLUGIN_DIR . 'includes/ajax.php';
        if ( file_exists( $ajax_registrar ) ) {
            require_once $ajax_registrar;
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        // Hook to enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        // Conditional assets loader: enqueue search CSS/JS when needed by theme
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_search_assets'));

        // Inject event content (details + registration form) within theme layout
        add_filter('the_content', array($this, 'inject_single_event_content'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets()
    {
        // Load only on single event page
        if (is_singular('event')) {
            // JavaScript
            wp_enqueue_script(
                'event-manager-js',
                EVENT_MANAGER_PLUGIN_URL . 'assets/js/event-register.js',
                array('jquery'),
                EVENT_MANAGER_VERSION,
                true
            );

            // Pass variables to JavaScript (AJAX URL and nonce)
            wp_localize_script(
                'event-manager-js',
                'eventManagerAjax',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('event_registration_nonce'),
                )
            );
        }
    }

    // Removed custom single template override to keep theme in control

    /**
     * Conditionally enqueue search assets when the current request needs them.
     * Loads CSS + frontend search JS when on event archive, single event,
     * or when the current post content contains the [event_search] shortcode.
     */
    public function maybe_enqueue_search_assets()
    {
        $load_search_ui = false;
        $load_styles    = false;

        // On archive -> load styles + search UI
        if ( function_exists( 'is_post_type_archive' ) && is_post_type_archive( 'event' ) ) {
            $load_styles    = true;
            $load_search_ui = true;
        }

        // If page contains shortcode -> load styles + search UI
        if ( ! $load_search_ui ) {
            global $post;
            if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'event_search' ) ) {
                $load_styles    = true;
                $load_search_ui = true;
            }
        }

        // On single event -> load only styles for registration section
        if ( function_exists( 'is_singular' ) && is_singular( 'event' ) ) {
            $load_styles = true;
        }

        if ( $load_styles ) {
            wp_enqueue_style(
                'event-manager-style',
                EVENT_MANAGER_PLUGIN_URL . 'assets/css/style.css',
                array(),
                EVENT_MANAGER_VERSION
            );
        }

        if ( $load_search_ui ) {
            wp_enqueue_script(
                'event-manager-search',
                EVENT_MANAGER_PLUGIN_URL . 'assets/js/event-search.js',
                array( 'jquery' ),
                EVENT_MANAGER_VERSION,
                true
            );

            wp_localize_script(
                'event-manager-search',
                'eventManagerSearch',
                array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'event_search_nonce' ),
                )
            );
        }
    }

    // Removed loop_start fallback injection – keep archive content controlled by theme/shortcode

    /**
     * Append event details and registration form to the content on single event pages.
     * Keeps theme layout intact while enhancing content.
     *
     * @param string $content
     * @return string
     */
    public function inject_single_event_content( $content )
    {
        if ( ! function_exists('is_singular') || ! is_singular('event') ) {
            return $content;
        }

        // Append only in main loop to avoid duplicates
        if ( ! in_the_loop() || ( function_exists('is_main_query') && ! is_main_query() ) ) {
            return $content;
        }

        $event_id = get_the_ID();

        // Fields (ACF first, fallback to post meta)
        $event_datetime     = function_exists('get_field') ? get_field('event_datetime') : get_post_meta( $event_id, 'event_datetime', true );
        $participant_limit  = function_exists('get_field') ? get_field('event_participant_limit') : get_post_meta( $event_id, 'event_participant_limit', true );
        $additional_desc    = function_exists('get_field') ? get_field('event_description') : get_post_meta( $event_id, 'event_description', true );

        $cities = get_the_terms( $event_id, 'city' );

        $registrations = get_post_meta( $event_id, 'event_registrations', true );
        if ( ! is_array( $registrations ) ) {
            $registrations = array();
        }
        $current_registrations = count( $registrations );

        $is_full = false;
        if ( $participant_limit && intval($participant_limit) > 0 && $current_registrations >= intval($participant_limit) ) {
            $is_full = true;
        }

        // Format date (expects Y-m-d H:i:s)
        $formatted_date = '';
        if ( ! empty( $event_datetime ) ) {
            try {
                $dt = new DateTime( $event_datetime );
                $formatted_date = $dt->format( 'd.m.Y, H:i' );
            } catch ( Exception $e ) {
                $formatted_date = '';
            }
        }

        // Render via template file to keep template separated
        $template_file = EVENT_MANAGER_PLUGIN_DIR . 'templates/single-event.php';
        if ( ! file_exists( $template_file ) ) {
            return $content; // Safety: if template missing, return original content
        }

        // Make variables available to the template scope
        $event_id_local            = $event_id;
        $formatted_date_local      = $formatted_date;
        $cities_local              = $cities;
        $participant_limit_local   = $participant_limit;
        $current_registrations_local = $current_registrations;
        $is_full_local             = $is_full;
        $additional_desc_local     = $additional_desc;
        $original_content          = $content;

        ob_start();
        include $template_file;
        $html = ob_get_clean();

        return $html;
    }
}

/**
 * Plugin activation hook
 */
function event_manager_activate()
{
    // Load logger first - needed for event_manager_log_info()
    $logger_file = EVENT_MANAGER_PLUGIN_DIR . 'includes/logger.php';
    if ( file_exists( $logger_file ) && ! function_exists( 'event_manager_log_info' ) ) {
        require_once $logger_file;
    }

    event_manager_log_info( 'Plugin activation started' );

    // Register CPT (for flush_rewrite_rules to work properly)
    $cpt_file = EVENT_MANAGER_PLUGIN_DIR . 'includes/cpt-registration.php';
    if ( file_exists( $cpt_file ) ) {
        require_once $cpt_file;
    }

    if ( function_exists( 'event_manager_register_post_type' ) ) {
        event_manager_register_post_type();
        event_manager_log_info( 'CPT registered successfully' );
    } else {
        event_manager_log_error( 'Failed to register CPT: function not found' );
    }

    if ( function_exists( 'event_manager_register_taxonomy' ) ) {
        event_manager_register_taxonomy();
        event_manager_log_info( 'Taxonomy registered successfully' );
    } else {
        event_manager_log_error( 'Failed to register taxonomy: function not found' );
    }

    // Optionally create a page with event list and search form (can be disabled via filter)
    $page_id = 0;
    if ( apply_filters( 'event_manager_create_page_on_activate', true ) ) {
        // Use a dedicated page for the search to avoid conflicting with CPT archive at /wydarzenia
        $page_slug    = 'eventy';
        $page_title   = 'Wydarzenia';
        $page_content = '[event_search]';

        // (Optional migration) If previously created page existed under 'wydarzenia' and CPT archive is enabled,
        // move it to a safe slug 'eventy' to avoid conflicts.
        $legacy = get_page_by_path( 'wydarzenia' );
        if ( $legacy && isset( $legacy->ID ) ) {
            // If legacy page belongs to us (contains shortcode), rename it
            $legacy_post = get_post( $legacy->ID );
            if ( $legacy_post && strpos( $legacy_post->post_content, '[event_search]' ) !== false ) {
                wp_update_post( array(
                    'ID'        => $legacy->ID,
                    'post_name' => $page_slug,
                ) );
            }
        }

        // Check if page with this slug already exists
        $existing = get_page_by_path( $page_slug );

        if ( $existing && isset( $existing->ID ) ) {
            $page_id = $existing->ID;

            // Check if it contains shortcode, if not - add it
            $post = get_post( $page_id );
            if ( $post && strpos( $post->post_content, '[event_search]' ) === false ) {
                wp_update_post( array(
                    'ID'           => $page_id,
                    'post_content' => $page_content,
                ) );
                event_manager_log_info( 'Updated existing events page with shortcode', array( 'page_id' => $page_id ) );
            } else {
                event_manager_log_info( 'Events page exists and has shortcode', array( 'page_id' => $page_id ) );
            }
        } else {
            // Create a new page
            $page_data = array(
                'post_title'   => $page_title,
                'post_name'    => $page_slug,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );

            $page_id = wp_insert_post( $page_data );
            if ( is_wp_error( $page_id ) ) {
                event_manager_log_error( 'Failed to create events page', array( 'error' => $page_id->get_error_message() ) );
                $page_id = 0;
            } else {
                event_manager_log_info( 'Events page created successfully', array( 'page_id' => $page_id ) );
            }
        }

        // Save page ID
        if ( $page_id && ! is_wp_error( $page_id ) ) {
            update_option( 'event_manager_events_page_id', $page_id );
        }
    }

    // Odśwież reguły rewrite
    flush_rewrite_rules();
    event_manager_log_info( 'Plugin activation completed' );
}
register_activation_hook(__FILE__, 'event_manager_activate');

/**
 * Plugin deactivation hook
 */
function event_manager_deactivate()
{
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'event_manager_deactivate');

/**
 * Plugin initialization
 */
function event_manager_init()
{
    Event_Manager::get_instance();
}
add_action('plugins_loaded', 'event_manager_init');
