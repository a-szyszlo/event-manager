<?php
// Partial template for event search form (used by [event_search] shortcode)
?>
<div class="event-search-wrapper">
    <form id="event-search-form" class="event-search-form">
        <!-- Hidden helpers for AJAX config -->
        <input type="hidden" id="search-ajaxurl" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" />
        <input type="hidden" id="search-nonce" value="<?php echo esc_attr( wp_create_nonce( 'event_search_nonce' ) ); ?>" />
        <div class="search-field">
            <label for="search-event">Szukaj:</label>
            <input type="text" id="search-event" name="s_event" placeholder="Wpisz nazwę wydarzenia..." />
        </div>

        <div class="search-field">
            <span class="search-label">Miasta:</span>
            <div class="search-checkboxes" id="search-city-group">
                <?php
                $cities = get_terms( array(
                    'taxonomy'   => 'city',
                    'hide_empty' => true,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ) );
                if ( ! is_wp_error( $cities ) && ! empty( $cities ) ) {
                    foreach ( $cities as $term ) {
                        echo '<label class="checkbox-item"><input type="checkbox" name="city[]" value="' . esc_attr( $term->slug ) . '" /> <span>' . esc_html( $term->name ) . '</span></label>';
                    }
                } else {
                    echo '<em>Brak dostępnych miast.</em>';
                }
                ?>
            </div>
        </div>

        <div class="search-field">
            <label for="search-date-from">Data od:</label>
            <input type="date" id="search-date-from" name="date_from" />
        </div>

        <div class="search-field">
            <label for="search-date-to">Data do:</label>
            <input type="date" id="search-date-to" name="date_to" />
        </div>

        <div class="search-actions">
            <button type="button" id="search-submit" class="search-btn">🔍 Szukaj</button>
            <button type="button" id="search-reset" class="search-btn reset">↺ Wyczyść</button>
        </div>
    </form>

    <div id="event-search-results" class="event-search-results"></div>
</div>
