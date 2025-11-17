/**
 * Event Manager - AJAX Search Handler
 * Live event search without page reload
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $form = $('#event-search-form');
        var $resultsContainer = $('#event-search-results');
        var debounceTimer;
        // Read config from localized data or hidden inputs in the form
        var ajaxurl = (typeof eventManagerSearch !== 'undefined' && eventManagerSearch.ajaxurl)
            ? eventManagerSearch.ajaxurl
            : ($('#search-ajaxurl').val() || '/wp-admin/admin-ajax.php');
        var nonce = (typeof eventManagerSearch !== 'undefined' && eventManagerSearch.nonce)
            ? eventManagerSearch.nonce
            : ($('#search-nonce').val() || '');

        if (!$form.length) {
            return;
        }

        /**
         * Perform search
         */
        function getSelectedCities() {
            var values = [];
            $('#search-city-group input[name="city[]"]:checked').each(function() {
                var v = $(this).val();
                if (v) { values.push(v); }
            });
            return values;
        }

        function refreshNonce(callback) {
            var currentAjaxUrl = $('#search-ajaxurl').val() || ajaxurl;
            $.ajax({
                url: currentAjaxUrl,
                type: 'POST',
                dataType: 'json',
                data: { action: 'event_search_nonce' },
                success: function(r) {
                    if (r && r.success && r.data && r.data.nonce) {
                        $('#search-nonce').val(r.data.nonce);
                        if (typeof callback === 'function') callback(true);
                    } else {
                        if (typeof callback === 'function') callback(false);
                    }
                },
                error: function() {
                    if (typeof callback === 'function') callback(false);
                }
            });
        }

        function performSearch(page, hasRetried) {
            page = page || 1;
            hasRetried = !!hasRetried;
            
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                var cities = getSelectedCities();
                // Always re-read nonce and ajaxurl from the DOM in case of bfcache/back nav
                var currentNonce = $('#search-nonce').val() || nonce;
                var currentAjaxUrl = $('#search-ajaxurl').val() || ajaxurl;
                var data = {
                    action: 'event_search_ajax',
                    nonce: currentNonce,
                    s_event: $('#search-event').val() || '',
                    city: cities.join(',') || '',
                    date_from: $('#search-date-from').val() || '',
                    date_to: $('#search-date-to').val() || '',
                    paged: page,
                };

                // Persist current filters in the URL (query string)
                var params = new URLSearchParams(window.location.search);
                params.set('s_event', data.s_event);
                params.set('city', data.city);
                params.set('date_from', data.date_from);
                params.set('date_to', data.date_to);
                params.set('paged', String(page));
                var newUrl = window.location.pathname + '?' + params.toString();
                window.history.replaceState({ emSearch: true }, '', newUrl);

                $resultsContainer.html('<div class="event-search-loading">⏳ Ładowanie...</div>');

                $.ajax({
                    url: currentAjaxUrl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        if (response.success) {
                            $resultsContainer.html(response.data.html);
                        } else {
                            var msg = (response.data && response.data.message) ? response.data.message : 'Wystąpił błąd wyszukiwania.';
                            var code = (response.data && response.data.code) ? String(response.data.code) : '';
                            var isNonceError = code === 'invalid_nonce' || /Błąd bezpieczeństwa|security|nonce/i.test(msg);
                            if (!hasRetried && isNonceError) {
                                return refreshNonce(function(ok){
                                    if (ok) {
                                        performSearch(page, true);
                                    } else {
                                        $resultsContainer.html('<div class="event-search-error">❌ ' + msg + '</div>');
                                    }
                                });
                            }
                            $resultsContainer.html('<div class="event-search-error">❌ ' + msg + '</div>');
                        }
                    },
                    error: function(xhr, status) {
                        var msg = 'Wystąpił błąd wyszukiwania.';
                        if (status === 'timeout') {
                            msg = '⏱️ Wyszukiwanie trwało zbyt długo.';
                        }
                        if (!hasRetried && (xhr && (xhr.status === 403 || xhr.status === 400))) {
                            return refreshNonce(function(ok){
                                if (ok) {
                                    performSearch(page, true);
                                } else {
                                    $resultsContainer.html('<div class="event-search-error">' + msg + '</div>');
                                }
                            });
                        }
                        $resultsContainer.html('<div class="event-search-error">' + msg + '</div>');
                    }
                });
            }, 500);
        }

        // Event listeners - search on each change
        // Debounced: waits 500ms from last change
        $('#search-event, #search-city, #search-date-from, #search-date-to').on('change keyup', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                performSearch(1);
            }, 500);
        });

        // Search button - immediate search
        $('#search-submit').on('click', function(e) {
            e.preventDefault();
            clearTimeout(debounceTimer);
            performSearch(1);
        });

        // Reset button - clear fields and URL params, then search
        $('#search-reset').on('click', function(e) {
            e.preventDefault();
            $('#search-event').val('');
            $('#search-city-group input[name="city[]"]').prop('checked', false);
            $('#search-date-from').val('');
            $('#search-date-to').val('');

            var params = new URLSearchParams(window.location.search);
            params.delete('s_event');
            params.delete('city');
            params.delete('date_from');
            params.delete('date_to');
            params.delete('paged');
            var newUrl = window.location.pathname;
            window.history.replaceState({ emSearch: true }, '', newUrl);

            clearTimeout(debounceTimer);
            performSearch(1);
        });

        // Pagination - delegated events for dynamically loaded buttons
        $(document).on('click', '.event-search-pagination .page-btn', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            performSearch(page);

            // Scroll to form
            $('html, body').animate({
                scrollTop: $form.offset().top - 100
            }, 300);
        });

        /**
         * Event listeners
         */
        
        // Input fields - trigger search on every key
        $('#search-event, #search-date-from, #search-date-to').on('input change', function() {
            performSearch(1);
        });

        $('#search-city-group').on('change', 'input[name="city[]"]', function() {
            performSearch(1);
        });

        // Search button
        $('#search-submit').on('click', function(e) {
            e.preventDefault();
            performSearch(1);
        });

        // Pagination buttons
        $(document).on('click', '.page-btn', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            performSearch(page);
        });

        // Restore state from URL on load (so Back brings back previous filters)
        (function restoreFromUrl() {
            var params = new URLSearchParams(window.location.search);
            var s_event = params.get('s_event') || '';
            var city = params.get('city') || '';
            var date_from = params.get('date_from') || '';
            var date_to = params.get('date_to') || '';
            var paged = parseInt(params.get('paged') || '1', 10);

            if (s_event) $('#search-event').val(s_event);
            if (city) {
                var parts = city.split(',');
                $('#search-city-group input[name="city[]"]').each(function() {
                    var val = $(this).val();
                    $(this).prop('checked', parts.indexOf(val) !== -1);
                });
            }
            if (date_from) $('#search-date-from').val(date_from);
            if (date_to) $('#search-date-to').val(date_to);

            // Ensure nonce exists before first request; if missing, fetch then search
            var initialGo = function(){ performSearch(isNaN(paged) ? 1 : paged); };
            if (!($('#search-nonce').val() || nonce)) {
                return refreshNonce(function(){ initialGo(); });
            }
            initialGo();
        })();

        // Handle bfcache restore (Safari/Firefox) to ensure filters and nonce are valid
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                // Re-read hidden config values
                ajaxurl = $('#search-ajaxurl').val() || ajaxurl;
                nonce = $('#search-nonce').val() || nonce;
                // Re-run restore to ensure state matches URL
                var params = new URLSearchParams(window.location.search);
                $('#search-event').val(params.get('s_event') || '');
                (function(){
                    var city = params.get('city') || '';
                    var parts = city ? city.split(',') : [];
                    $('#search-city-group input[name="city[]"]').each(function() {
                        var val = $(this).val();
                        $(this).prop('checked', parts.indexOf(val) !== -1);
                    });
                })();
                $('#search-date-from').val(params.get('date_from') || '');
                $('#search-date-to').val(params.get('date_to') || '');
                var paged = parseInt(params.get('paged') || '1', 10);
                performSearch(isNaN(paged) ? 1 : paged);
            }
        });

        // Handle browser back/forward navigation restoring results
        window.addEventListener('popstate', function() {
            var params = new URLSearchParams(window.location.search);
            $('#search-event').val(params.get('s_event') || '');
            (function(){
                var city = params.get('city') || '';
                var parts = city ? city.split(',') : [];
                $('#search-city-group input[name="city[]"]').each(function() {
                    var val = $(this).val();
                    $(this).prop('checked', parts.indexOf(val) !== -1);
                });
            })();
            $('#search-date-from').val(params.get('date_from') || '');
            $('#search-date-to').val(params.get('date_to') || '');
            var paged = parseInt(params.get('paged') || '1', 10);
            performSearch(isNaN(paged) ? 1 : paged);
        });
    });

})(jQuery);
