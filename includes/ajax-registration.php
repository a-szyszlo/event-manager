<?php
/**
 * AJAX Registration implementation (kept separate from hook registration)
 * Mirrors structure of event-search feature for consistency.
 *
 * @package Event_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX Handler for event registration
 *
 * Endpoint: wp-admin/admin-ajax.php?action=register_event
 * Method: POST
 *
 * Required POST parameters:
 * - event_id: Event ID (integer)
 * - registration_name: Participant name (string)
 * - registration_email: Participant email (string)
 * - nonce: Security token
 */
function event_manager_ajax_register_event() {
    // 1. Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'event_registration_nonce' ) ) {
        event_manager_log_warning( 'Registration attempt failed: nonce verification failed' );
        // 403 Forbidden for failed CSRF check
        wp_send_json_error( array(
            'message' => __( 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'event-manager' ),
        ), 403 );
        wp_die();
    }

    // 2. Get and validate data
    $event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
    $name     = isset( $_POST['registration_name'] ) ? sanitize_text_field( wp_unslash( $_POST['registration_name'] ) ) : '';
    $email    = isset( $_POST['registration_email'] ) ? sanitize_email( wp_unslash( $_POST['registration_email'] ) ) : '';
    // Normalize email for duplicate detection and storage: trim + lowercase
    $email_normalized = strtolower( trim( $email ) );
    // Normalize name whitespace
    $name = trim( preg_replace( '/\s+/u', ' ', (string) $name ) );

    event_manager_log_info( 'Registration attempt started', array( 'event_id' => $event_id, 'email' => $email ) );

    // Validation: Is event_id correct?
    if ( ! $event_id || get_post_type( $event_id ) !== 'event' ) {
        event_manager_log_warning( 'Registration failed: invalid event_id', array( 'event_id' => $event_id ) );
        // 404 Not Found when event does not exist / wrong type
        wp_send_json_error( array(
            'message' => __( 'Nieprawidłowe wydarzenie.', 'event-manager' ),
        ), 404 );
        wp_die();
    }

    // Validation: Is name not empty?
    if ( empty( $name ) ) {
        event_manager_log_warning( 'Registration failed: empty name', array( 'event_id' => $event_id, 'email' => $email ) );
        // 400 Bad Request for validation errors
        wp_send_json_error( array(
            'message' => __( 'Imię jest wymagane.', 'event-manager' ),
        ), 400 );
        wp_die();
    }
    // Name length and characters constraints
    $name_len = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
    if ( $name_len < 2 || $name_len > 100 ) {
        event_manager_log_warning( 'Registration failed: name length out of bounds', array( 'len' => $name_len ) );
        wp_send_json_error( array(
            'message' => __( 'Imię musi mieć od 2 do 100 znaków.', 'event-manager' ),
        ), 400 );
        wp_die();
    }
    if ( preg_match( '/[<>]/', $name ) ) {
        event_manager_log_warning( 'Registration failed: invalid characters in name' );
        wp_send_json_error( array(
            'message' => __( 'Imię zawiera niedozwolone znaki.', 'event-manager' ),
        ), 400 );
        wp_die();
    }

    // Validation: Is email valid?
    if ( empty( $email ) || ! is_email( $email ) ) {
        event_manager_log_warning( 'Registration failed: invalid email', array( 'event_id' => $event_id, 'email' => $email ) );
        wp_send_json_error( array(
            'message' => __( 'Podaj prawidłowy adres e-mail.', 'event-manager' ),
        ), 400 );
        wp_die();
    }
    // Email length and characters constraints
    if ( strlen( $email_normalized ) > 254 ) {
        event_manager_log_warning( 'Registration failed: email too long' );
        wp_send_json_error( array(
            'message' => __( 'Adres e-mail jest zbyt długi (maks. 254 znaki).', 'event-manager' ),
        ), 400 );
        wp_die();
    }
    if ( strpos( $email_normalized, '<' ) !== false || strpos( $email_normalized, '>' ) !== false ) {
        event_manager_log_warning( 'Registration failed: invalid characters in email' );
        wp_send_json_error( array(
            'message' => __( 'Adres e-mail zawiera niedozwolone znaki.', 'event-manager' ),
        ), 400 );
        wp_die();
    }

    // 3. Check event status
    $post_status = get_post_status( $event_id );
    if ( $post_status !== 'publish' ) {
        // 404 Not Found – treat unpublished/trashed as not available
        wp_send_json_error( array(
            'message' => __( 'To wydarzenie nie jest już dostępne.', 'event-manager' ),
        ), 404 );
        wp_die();
    }

    // 3a. Optional: Block registrations for past events based on event_datetime (ACF or post meta).
    // Expecting a datetime string parsable by DateTime (e.g., 'Y-m-d H:i:s').
    $event_datetime_raw = function_exists( 'get_field' ) ? get_field( 'event_datetime', $event_id ) : get_post_meta( $event_id, 'event_datetime', true );
    if ( ! empty( $event_datetime_raw ) ) {
        try {
            $event_dt = new DateTime( $event_datetime_raw );
            $event_ts = $event_dt->getTimestamp();
            // Use WordPress time to respect site timezone settings
            $now_ts   = current_time( 'timestamp' );
            if ( $event_ts < $now_ts ) {
                event_manager_log_info( 'Registration blocked: event in the past', array( 'event_id' => $event_id, 'event_ts' => $event_ts, 'now' => $now_ts ) );
                // 400 Bad Request – business rule violation
                wp_send_json_error( array(
                    'message' => __( 'Rejestracja na to wydarzenie została zamknięta.', 'event-manager' ),
                ), 400 );
                wp_die();
            }
        } catch ( Exception $e ) {
            // If datetime is invalid, do not block – proceed silently
            event_manager_log_warning( 'Invalid event_datetime format; skipping past-date check', array( 'event_id' => $event_id ) );
        }
    }

    // 4. Get participant limit
    $participant_limit = get_field( 'event_participant_limit', $event_id );

    // 5. Get current registrations
    $registrations = get_post_meta( $event_id, 'event_registrations', true );
    if ( ! is_array( $registrations ) ) {
        $registrations = array();
    }

    // 6. Check if user already registered
    foreach ( $registrations as $registration ) {
        if ( isset( $registration['email'] ) ) {
            $stored_normalized = strtolower( trim( $registration['email'] ) );
            if ( $stored_normalized === $email_normalized ) {
                // 409 Conflict – duplicate registration
                wp_send_json_error( array(
                    'message' => __( 'Ten adres e-mail jest już zarejestrowany na to wydarzenie.', 'event-manager' ),
                ), 409 );
                wp_die();
            }
        }
    }

    // 7. Check participant limit
    $current_count = count( $registrations );
    if ( $participant_limit && $current_count >= $participant_limit ) {
        // 409 Conflict – capacity reached
        wp_send_json_error( array(
            'message' => __( 'Przepraszamy, wszystkie miejsca są już zajęte.', 'event-manager' ),
        ), 409 );
        wp_die();
    }

    // 8. Add new registration
    $new_registration = array(
        'name'           => $name,
        // Store normalized email for consistency (backward-compatible with existing records)
        'email'          => $email_normalized,
        'registered_at'  => current_time( 'mysql' ),
        'user_ip'        => event_manager_get_user_ip(),
    );

    $registrations[] = $new_registration;

    // 9. Save registration to post meta
    $update_result = update_post_meta( $event_id, 'event_registrations', $registrations );

    // 10. Check if save was successful
    if ( $update_result === false ) {
        event_manager_log_error( 'Failed to save registration to post meta', array( 'event_id' => $event_id, 'email' => $email ) );
        // 500 Internal Server Error – database/meta update failure
        wp_send_json_error( array(
            'message' => __( 'Wystąpił błąd podczas zapisywania. Spróbuj ponownie.', 'event-manager' ),
        ), 500 );
        wp_die();
    }

    // 11. Return success
    $new_count = count( $registrations );
    $places_left = $participant_limit ? ( $participant_limit - $new_count ) : null;

    event_manager_log_info( 'Registration successful', array( 'event_id' => $event_id, 'email' => $email, 'count' => $new_count ) );

    wp_send_json_success( array(
        'message'         => __( 'Dziękujemy! Rejestracja przebiegła pomyślnie.', 'event-manager' ),
        'registered_name' => esc_html( $name ),
        'current_count'   => $new_count,
        'places_left'     => $places_left,
        'is_full'         => ( $participant_limit && $new_count >= $participant_limit ),
    ) );

    wp_die();
}

