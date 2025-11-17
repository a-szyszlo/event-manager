<?php
/**
 * Konfiguracja pól Advanced Custom Fields (ACF)
 *
 * @package Event_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rejestracja pól ACF dla typu "event"
 * 
 * Pola:
 * - Data i godzina rozpoczęcia
 * - Limit uczestników
 * - Opis/szczegóły wydarzenia
 */
function event_manager_register_acf_fields() {
    // Check if ACF function exists
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key'      => 'group_event_details',
        'title'    => __( 'Szczegóły wydarzenia', 'event-manager' ),
        'fields'   => array(
            // Start date and time
            array(
                'key'               => 'field_event_datetime',
                'label'             => __( 'Data i godzina rozpoczęcia', 'event-manager' ),
                'name'              => 'event_datetime',
                'type'              => 'date_time_picker',
                'instructions'      => __( 'Wybierz datę i godzinę rozpoczęcia wydarzenia', 'event-manager' ),
                'required'          => 1,
                'conditional_logic' => 0,
                'wrapper'           => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'display_format'    => 'd/m/Y g:i a',
                'return_format'     => 'Y-m-d H:i:s',
                'first_day'         => 1, 
            ),
            // Participant limit
            array(
                'key'               => 'field_event_participant_limit',
                'label'             => __( 'Limit uczestników', 'event-manager' ),
                'name'              => 'event_participant_limit',
                'type'              => 'number',
                'instructions'      => __( 'Maksymalna liczba uczestników (zostaw puste dla braku limitu)', 'event-manager' ),
                'required'          => 0,
                'conditional_logic' => 0,
                'wrapper'           => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'default_value'     => 50,
                'placeholder'       => '',
                'min'               => 1,
                'max'               => '',
                'step'              => 1,
            ),
            // Event description
            array(
                'key'               => 'field_event_description',
                'label'             => __( 'Szczegółowy opis', 'event-manager' ),
                'name'              => 'event_description',
                'type'              => 'wysiwyg',
                'instructions'      => __( 'Dodatkowe informacje i szczegóły wydarzenia', 'event-manager' ),
                'required'          => 0,
                'conditional_logic' => 0,
                'wrapper'           => array(
                    'width' => '',
                    'class' => '',
                    'id'    => '',
                ),
                'default_value'     => '',
                'tabs'              => 'all',
                'toolbar'           => 'full',
                'media_upload'      => 1,
                'delay'             => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'event',
                ),
            ),
        ),
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => true,
        'description'           => '',
    ) );
}
add_action( 'acf/init', 'event_manager_register_acf_fields' );

/**
 * Wyświetl powiadomienie jeśli ACF nie jest zainstalowany
 */
function event_manager_acf_admin_notice() {
    // Check if ACF is active
    if ( ! class_exists( 'ACF' ) ) {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Event Manager:', 'event-manager' ); ?></strong>
                <?php 
                echo sprintf(
                    /* translators: %s: Link to ACF plugin */
                    esc_html__( 'Wtyczka wymaga zainstalowania i aktywacji wtyczki %s.', 'event-manager' ),
                    '<a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'event_manager_acf_admin_notice' );
