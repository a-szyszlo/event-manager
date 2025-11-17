<?php
/**
 * Utility helpers for Event Manager
 *
 * @package Event_Manager\Utils
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'event_manager_get_user_ip' ) ) {
    /**
     * Get the best-guess client IP address.
     * Note: Values from X-Forwarded-* headers can be spoofed; this is used only for basic logging/auditing,
     * not for security decisions. Prefer server-configured trusted proxy headers if available.
     *
     * @return string Client IP address or '0.0.0.0' when unknown.
     */
    function event_manager_get_user_ip() {
        // Prefer Cloudflare header when present (common on many WP hosts)
        $candidates = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $candidates as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                // Some headers can contain multiple IPs. Take the first valid one.
                $parts = explode( ',', (string) $_SERVER[ $key ] );
                foreach ( $parts as $raw ) {
                    $ip = trim( $raw );
                    // Validate IPv4/IPv6
                    if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }
}
