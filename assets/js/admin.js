/**
 * Skrypt administracyjny dla wtyczki AI Chat Assistant
 */
(function($) {
    'use strict';
    
    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function() {
        // Inicjalizacja strony ustawień
        if ($('.aica-settings-container').length) {
            initSettingsPage();
        }
    });
    
    /**
     * Inicjalizacja funkcji strony ustawień
     */
    function initSettingsPage() {
        // Obsługa zakładek
        $('.aica-tab-item').on('click', function() {
            // Usunięcie aktywnej klasy z wszystkich zakładek
            $('.aica-tab-item').removeClass('active');
            // Dodanie aktywnej klasy do klikniętej zakładki
            $(this).addClass('active');
            
            // Ukrycie wszystkich zawartości zakładek
            $('.aica-tab-content').removeClass('active');
            
            // Pobranie id zawartości zakładki
            var tabId = $(this).data('tab');
            
            // Pokazanie zawartości aktywnej zakładki
            $('#' + tabId).addClass('active');
            
            // Zapisanie aktywnej zakładki w sessionStorage
            sessionStorage.setItem('aica_active_tab', tabId);
        });
        
        // Przywrócenie aktywnej zakładki z sessionStorage
        var activeTab = sessionStorage.getItem('aica_active_tab');
        if (activeTab) {
            $('.aica-tab-item[data-tab="' + activeTab + '"]').trigger('click');
        }
        
        // Obsługa pola z zakresem
        $('#aica_max_tokens_range').on('input', function() {
            $('#aica_max_tokens').val($(this).val());
        });
        
        $('#aica_max_tokens').on('input', function() {
            $('#aica_max_tokens_range').val($(this).val());
        });
        
        // Obsługa przycisków pokaż/ukryj hasło
        $('.aica-toggle-password').on('click', function() {
            var input = $(this).siblings('input');
            var icon = $(this).find('.dashicons');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                input.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });
        
        // Obsługa pola tagów z rozszerzeniami plików
        function updateExtensionsField() {
            var extensions = [];
            $('.aica-tag').each(function() {
                var text = $(this).contents().filter(function() {
                    return this.nodeType === 3; // node tekstowy
                }).text().trim();
                
                if (text) {
                    extensions.push(text);
                }
            });
            $('#aica_allowed_file_extensions').val(extensions.join(','));
        }
        
        $('#add-extension').on('click', function() {
            var extension = $('#aica_file_extension_input').val().trim();
            
            if (extension !== '') {
                // Sprawdzenie czy rozszerzenie już istnieje
                var exists = false;
                $('.aica-tag').each(function() {
                    var text = $(this).contents().filter(function() {
                        return this.nodeType === 3; // node tekstowy
                    }).text().trim();
                    
                    if (text === extension) {
                        exists = true;
                        return false;
                    }
                });
                
                if (!exists) {
                    // Dodanie nowego tagu
                    var tag = $('<span class="aica-tag">' + extension + '<button type="button" class="aica-remove-tag" data-value="' + extension + '"><span class="dashicons dashicons-no-alt"></span></button></span>');
                    $('#extensions-container').append(tag);
                    $('#aica_file_extension_input').val('');
                    
                    // Aktualizacja pola ukrytego
                    updateExtensionsField();
                }
            }
        });
        
        // Obsługa wciśnięcia Enter w polu dodawania rozszerzeń
        $('#aica_file_extension_input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#add-extension').trigger('click');
            }
        });
        
        // Usuwanie tagów
        $(document).on('click', '.aica-remove-tag', function() {
            $(this).parent().remove();
            updateExtensionsField();
        });
        
        // Testowanie połączenia z API Claude
        $('#test-claude-api').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#api-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + aica_data.i18n.loading);
            
            // Pobranie klucza API
            var apiKey = $('#aica_claude_api_key').val();
            
            if (apiKey === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź klucz API.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce,
                    api_type: 'claude',
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API Claude.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API GitHub
        $('#test-github-api').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#github-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + aica_data.i18n.loading);
            
            // Pobranie tokenu
            var token = $('#aica_github_token').val();
            
            if (token === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź token GitHub.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce,
                    api_type: 'github',
                    api_key: token
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API GitHub.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API GitLab
        $('#test-gitlab-api').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#gitlab-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + aica_data.i18n.loading);
            
            // Pobranie tokenu
            var token = $('#aica_gitlab_token').val();
            
            if (token === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź token GitLab.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce,
                    api_type: 'gitlab',
                    api_key: token
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API GitLab.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Testowanie połączenia z API Bitbucket
        $('#test-bitbucket-api').on('click', function() {
            var button = $(this);
            var originalText = button.html();
            var resultContainer = $('#bitbucket-test-result');
            
            // Ukrycie poprzedniego wyniku
            resultContainer.removeClass('success error').addClass('loading');
            resultContainer.html('<span class="aica-spinner"></span> ' + aica_data.i18n.loading);
            
            // Pobranie danych dostępowych
            var username = $('#aica_bitbucket_username').val();
            var password = $('#aica_bitbucket_app_password').val();
            
            if (username === '' || password === '') {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wprowadź nazwę użytkownika i hasło aplikacji Bitbucket.');
                return;
            }
            
            // Wywołanie AJAX do testowania połączenia
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_test_api_connection',
                    nonce: aica_data.settings_nonce,
                    api_type: 'bitbucket',
                    username: username,
                    password: password
                },
                success: function(response) {
                    if (response.success) {
                        resultContainer.removeClass('loading').addClass('success');
                        resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> ' + response.data.message);
                    } else {
                        resultContainer.removeClass('loading').addClass('error');
                        resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || 'Nie udało się połączyć z API Bitbucket.'));
                    }
                },
                error: function() {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + 'Wystąpił błąd podczas testowania połączenia.');
                }
            });
        });
        
        // Walidacja formularza przed wysłaniem
        $('#aica-settings-form').on('submit', function(e) {
            var valid = true;
            var firstError = null;
            
            // Walidacja pól wg aktywnej zakładki
            var activeTab = $('.aica-tab-content.active').attr('id');
            
            if (activeTab === 'claude-settings') {
                // Walidacja klucza API Claude
                if ($('#aica_claude_api_key').val().trim() === '') {
                    $('#aica_claude_api_key').addClass('error');
                    valid = false;
                    if (!firstError) firstError = $('#aica_claude_api_key');
                } else {
                    $('#aica_claude_api_key').removeClass('error');
                }
            }
            
            // W przypadku błędów, przerwij wysyłanie i przewiń do pierwszego błędu
            if (!valid) {
                e.preventDefault();
                if (firstError) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                    firstError.focus();
                }
            }
        });
    }
})(jQuery);