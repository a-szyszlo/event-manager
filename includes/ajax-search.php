<?php
/**
 * AJAX Search implementation (kept separate from hook registration)
 * Mirrors structure of ajax-registration for consistency.
 *
 * @package Event_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler: Event search
 *
 * Endpoint: wp-admin/admin-ajax.php?action=event_search_ajax
 * Method: POST
 */
function event_manager_ajax_search() {
    // Nonce verification
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'event_search_nonce' ) ) {
        wp_send_json_error( array(
            'message' => __( 'Błąd bezpieczeństwa.', 'event-manager' ),
            'code'    => 'invalid_nonce',
        ) );
    }

    // Sanitize input
    $search_term = isset( $_POST['s_event'] ) ? sanitize_text_field( wp_unslash( $_POST['s_event'] ) ) : '';
    $city       = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
    $date_from  = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
    $date_to    = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';
    $paged      = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;

    // Validate dates
    if ( ! empty( $date_from ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
        wp_send_json_error( array( 'message' => __( 'Nieprawidłowy format daty.', 'event-manager' ) ) );
    }

    if ( ! empty( $date_to ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
        wp_send_json_error( array( 'message' => __( 'Nieprawidłowy format daty.', 'event-manager' ) ) );
    }

    if ( ! empty( $date_from ) && ! empty( $date_to ) && $date_from > $date_to ) {
        wp_send_json_error( array( 'message' => __( 'Data początkowa nie może być późniejsza.', 'event-manager' ) ) );
    }

    // Build query
    $args = array(
        'post_type'      => 'event',
        'posts_per_page' => 10,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value',
        'meta_key'       => 'event_datetime',
        'order'          => 'ASC',
    );

    if ( ! empty( $search_term ) ) {
        $args['s'] = $search_term;
    }

    // Meta query for date
    $meta_query = array();
    if ( ! empty( $date_from ) ) {
        $meta_query[] = array(
            'key'     => 'event_datetime',
            'value'   => $date_from . ' 00:00:00',
            'compare' => '>=',
            'type'    => 'DATETIME',
        );
    }
    if ( ! empty( $date_to ) ) {
        $meta_query[] = array(
            'key'     => 'event_datetime',
            'value'   => $date_to . ' 23:59:59',
            'compare' => '<=',
            'type'    => 'DATETIME',
        );
    }
    if ( ! empty( $meta_query ) ) {
        $args['meta_query'] = $meta_query;
    }

    // Tax query for city (accepts slugs)
    if ( ! empty( $city ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'city',
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_title', explode( ',', $city ) ),
                'operator' => 'IN',
            ),
        );
    }

    // Execute query
    $query = new WP_Query( $args );

    // Render results
    ob_start();

    if ( $query->have_posts() ) {
        echo '<div class="event-search-results">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $event_id       = get_the_ID();
            $event_datetime = function_exists( 'get_field' ) ? get_field( 'event_datetime', $event_id ) : get_post_meta( $event_id, 'event_datetime', true );
            $event_url      = get_permalink( $event_id );
            $has_thumb      = has_post_thumbnail( $event_id );
            // Build a short excerpt from post excerpt or ACF description
            $raw_excerpt = get_the_excerpt( $event_id );
            if ( empty( $raw_excerpt ) ) {
                $acf_desc    = function_exists( 'get_field' ) ? get_field( 'event_description', $event_id ) : '';
                $raw_excerpt = is_string( $acf_desc ) ? $acf_desc : '';
            }
            $length  = apply_filters( 'event_manager_search_excerpt_length', 160 );
            $clean   = wp_strip_all_tags( (string) $raw_excerpt );
            $excerpt = wp_html_excerpt( $clean, absint( $length ), '…' );
            ?>
            <div class="event-result-item">
                <?php if ( $has_thumb ) : ?>
                    <div class="event-result-thumb">
                        <a href="<?php echo esc_url( $event_url ); ?>" aria-label="<?php echo esc_attr( get_the_title( $event_id ) ); ?>">
                            <div class="event-result-img-wrapper"><?php echo get_the_post_thumbnail( $event_id, 'medium' ); ?></div>
                        </a>
                    </div>
                <?php endif; ?>
                <div class="event-result-content">

                    <h3 class="event-result-title"><a href="<?php echo esc_url( $event_url ); ?>"><?php the_title(); ?></a></h3>
                    <?php if ( $event_datetime ) : ?>
                        <p class="event-result-date">📅 <?php echo esc_html( $event_datetime ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $excerpt ) ) : ?>
                        <p class="event-result-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        echo '</div>';

        // Pagination (basic)
        if ( $query->max_num_pages > 1 ) {
            echo '<div class="event-search-pagination">';
            for ( $i = 1; $i <= $query->max_num_pages; $i++ ) {
                $class = ( $i === $paged ) ? 'active' : '';
                echo '<button class="page-btn ' . esc_attr( $class ) . '" data-page="' . intval( $i ) . '">' . intval( $i ) . '</button>';
            }
            echo '</div>';
        }

        wp_reset_postdata();
    } else {
        echo '<div class="event-search-no-results"><p>' . esc_html__( 'Brak wyników.', 'event-manager' ) . '</p></div>';
    }

    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'         => $html,
        'total'        => $query->found_posts,
        'max_pages'    => $query->max_num_pages,
        'current_page' => $paged,
    ) );
}

/**
 * AJAX: return fresh nonce for search
 */
function event_manager_ajax_search_nonce() {
    wp_send_json_success( array(
        'nonce' => wp_create_nonce( 'event_search_nonce' ),
    ) );
}
