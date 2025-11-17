<?php
// Partial template for single event content injected via the_content
// Expected variables (from filter scope):
// $event_id_local, $formatted_date_local, $cities_local, $participant_limit_local,
// $current_registrations_local, $is_full_local, $additional_desc_local, $original_content
?>

<div class="event-manager-wrapper">
    <div class="event-meta">
        <?php if ( ! empty( $formatted_date_local ) ) : ?>
            <div class="event-meta-item">
                <span class="event-meta-label">📅 Data i godzina:</span>
                <span class="event-meta-value"><?php echo esc_html( $formatted_date_local ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $cities_local ) && ! is_wp_error( $cities_local ) ) : ?>
            <div class="event-meta-item">
                <span class="event-meta-label">📍 Miasto:</span>
                <span class="event-meta-value">
                    <?php
                    $city_names = array();
                    foreach ( $cities_local as $city ) {
                        $city_names[] = esc_html( $city->name );
                    }
                    echo implode( ', ', $city_names );
                    ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="event-meta-item">
            <span class="event-meta-label">👥 Liczba uczestników:</span>
            <span class="event-meta-value">
                <?php
                echo esc_html( (string) $current_registrations_local );
                if ( ! empty( $participant_limit_local ) ) {
                    echo ' / ' . esc_html( (string) $participant_limit_local );
                }
                ?>
            </span>
        </div>

        <?php if ( ! empty( $is_full_local ) ) : ?>
            <div class="event-status event-full">
                <span class="event-status-badge">❌ Wydarzenie pełne</span>
            </div>
        <?php else : ?>
            <div class="event-status event-available">
                <span class="event-status-badge">✅ Dostępne miejsca</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="event-description">
        <?php echo $original_content; // already filtered content ?>
    </div>

    <?php
    // Show additional section if we have either featured image or additional description
    $has_thumb_local = function_exists( 'has_post_thumbnail' ) ? has_post_thumbnail( $event_id_local ) : false;
    if ( $has_thumb_local || ! empty( $additional_desc_local ) ) : ?>
        <div class="event-additional-description">
            <?php if ( ! empty( $additional_desc_local ) ) : ?>
                <h2>Dodatkowe informacje</h2>
                <?php echo wp_kses_post( $additional_desc_local ); ?>
            <?php endif; ?>

            <?php if ( $has_thumb_local ) : ?>
                <div class="event-thumbnail">
                    <?php echo get_the_post_thumbnail( $event_id_local, 'large', array( 'loading' => 'lazy' ) ); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="event-registration-section">
        <h2>Zapisz się na wydarzenie</h2>

        <?php if ( ! empty( $is_full_local ) ) : ?>
            <div class="registration-message error">
                <p>Przepraszamy, wszystkie miejsca zostały zajęte.</p>
            </div>
        <?php else : ?>
            <?php
            // Compute places left for JS hints
            $places_left_local = null;
            if ( ! empty( $participant_limit_local ) ) {
                $places_left_local = max( (int) $participant_limit_local - (int) $current_registrations_local, 0 );
            }
            ?>
            <div id="registration-message" class="registration-message" style="display: none;"></div>
            <form
                id="event-registration-form"
                class="event-registration-form"
                method="post"
                data-is-full="<?php echo ! empty( $is_full_local ) ? 'true' : 'false'; ?>"
                <?php if ( $places_left_local !== null ) : ?>
                    data-places-left="<?php echo esc_attr( (string) $places_left_local ); ?>"
                <?php endif; ?>
            >
                <div class="form-group">
                    <label for="registration-name">Imię: <span class="required">*</span></label>
                    <input
                        type="text"
                        id="registration-name"
                        name="registration_name"
                        required
                        placeholder="Wpisz swoje imię"
                        minlength="2"
                        maxlength="100"
                        autocomplete="name"
                        inputmode="text"
                    />
                </div>

                <div class="form-group">
                    <label for="registration-email">Email: <span class="required">*</span></label>
                    <input
                        type="email"
                        id="registration-email"
                        name="registration_email"
                        required
                        placeholder="Wpisz swój email"
                        maxlength="254"
                        autocomplete="email"
                        inputmode="email"
                    />
                </div>

                <input type="hidden" name="event_id" value="<?php echo esc_attr( (string) $event_id_local ); ?>" />

                <div class="form-group">
                    <button type="submit" class="btn-submit">Zapisz się</button>
                    <span class="form-loading" style="display: none;">⏳ Trwa zapisywanie...</span>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
