/**
 * Event Manager - AJAX Registration Handler
 * 
 * Handles event registration form submission without page reload
 */

(function($) {
    'use strict';

    /**
     * Initialize after DOM is loaded
     */
    $(document).ready(function() {
        
        // Check if form exists
        var $form = $('#event-registration-form');
        if (!$form.length) {
            return;
        }

        /**
         * Handle form submission
         */
        $form.on('submit', function(e) {
            e.preventDefault();

            // Form elements
            var $submitBtn = $form.find('.btn-submit');
            var $loading = $form.find('.form-loading');
            var $messageBox = $('#registration-message');

            // Get form data
            var formData = {
                action: 'register_event',
                nonce: eventManagerAjax.nonce,
                event_id: $form.find('input[name="event_id"]').val(),
                registration_name: $form.find('input[name="registration_name"]').val().trim(),
                registration_email: $form.find('input[name="registration_email"]').val().trim()
            };

            // Simple frontend validation (length/pattern)
            if (!formData.registration_name) {
                showMessage('Proszę podać swoje imię.', 'error');
                return;
            }
            // Normalize whitespace in name
            formData.registration_name = formData.registration_name.replace(/\s+/g, ' ').trim();
            if (formData.registration_name.length < 2 || formData.registration_name.length > 100) {
                showMessage('Nazwa musi mieć od 2 do 100 znaków.', 'error');
                return;
            }
            if (/[<>]/.test(formData.registration_name)) {
                showMessage('Nieprawidłowe znaki w nazwie.', 'error');
                return;
            }

            if (!formData.registration_email) {
                showMessage('Podaj swój adres e-mail.', 'error');
                return;
            }
            if (formData.registration_email.length > 254) {
                showMessage('Adres e-mail jest za długi (maks. 254 znaki).', 'error');
                return;
            }
            if (!isValidEmail(formData.registration_email)) {
                showMessage('Podaj prawidłowy adres e-mail.', 'error');
                return;
            }

            // Disable button during submission
            $submitBtn.prop('disabled', true);
            $loading.show();
            $messageBox.hide();

            /**
             * AJAX submission
             */
            $.ajax({
                url: eventManagerAjax.ajaxurl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Handle response
                    if (response.success) {
                        // Success
                        showMessage(response.data.message, 'success');
                        
                        // Clear form
                        $form[0].reset();

                        // Optionally: update participant counter
                        if (response.data.current_count) {
                            updateParticipantCount(response.data.current_count);
                        }

                        // If event is full, hide form
                        if (response.data.is_full) {
                            setTimeout(function() {
                                $form.fadeOut();
                                showMessage('Wydarzenie jest pełne. Wszystkie miejsca są zajęte.', 'info');
                            }, 3000);
                        }

                    } else {
                        // Error
                        showMessage(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Try to extract meaningful error message from server response
                    var message = '';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    } else if (xhr && typeof xhr.responseText === 'string' && xhr.responseText.trim().length) {
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed && parsed.data && parsed.data.message) {
                                message = parsed.data.message;
                            }
                        } catch (e) {
                            // Ignore JSON parse errors
                        }
                    }

                    if (!message) {
                        // Fallback generic message (Polish UI)
                        message = 'Wystąpił błąd połączenia. Spróbuj ponownie później.';
                    }

                    console.error('AJAX Error:', status, error, 'HTTP', xhr ? xhr.status : '');
                    showMessage(message, 'error');
                },
                complete: function() {
                    // Enable button
                    $submitBtn.prop('disabled', false);
                    $loading.hide();
                }
            });
        });

        /**
         * Display message
         * 
         * @param {string} message - Message content
         * @param {string} type - Type: success, error, info
         */
        function showMessage(message, type) {
            var $messageBox = $('#registration-message');
            
            // Remove previous classes
            $messageBox.removeClass('success error info');
            
            // Add new class
            $messageBox.addClass(type);
            
            // Set content and show
            $messageBox.html('<p>' + escapeHtml(message) + '</p>').fadeIn();

            // Auto-hide message after 5 seconds (for success)
            if (type === 'success') {
                setTimeout(function() {
                    $messageBox.fadeOut();
                }, 5000);
            }
        }

        /**
         * Validate email address
         * 
         * @param {string} email - Email address
         * @return {boolean}
         */
        function isValidEmail(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        /**
         * Escape HTML (XSS protection)
         * 
         * @param {string} text - Text to escape
         * @return {string}
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        /**
         * Update participant counter
         * 
         * @param {number} count - New participant count
         */
        function updateParticipantCount(count) {
            var $metaValue = $('.event-meta-item').find('.event-meta-value');
            
            // Find element with participant count
            $metaValue.each(function() {
                var $this = $(this);
                var text = $this.text();
                
                // Check if this is the participant counter (contains "👥" in previous element)
                if ($this.prev('.event-meta-label').text().indexOf('👥') !== -1) {
                    // Update counter
                    var parts = text.split('/');
                    if (parts.length === 2) {
                        $this.text(count + ' / ' + parts[1].trim());
                    } else {
                        $this.text(count);
                    }
                }
            });
        }

    });

})(jQuery);
