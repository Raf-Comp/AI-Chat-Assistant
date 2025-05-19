/**
 * Skrypt do obsługi historii rozmów w AI Chat Assistant
 */
(function($) {
    'use strict';
    
    // Zmienne globalne
    let currentPage = 1;
    let totalPages = 1;
    let itemsPerPage = 20;
    let currentFilters = {
        search: '',
        sortOrder: 'newest',
        dateFrom: '',
        dateTo: ''
    };
    
    // Główna funkcja inicjalizująca
    function init() {
        // Inicjalizacja eventów
        setupEvents();
        
        // Inicjalizacja filtrów z URL
        initFiltersFromUrl();
        
        // Pierwsza paginacja z aktualnymi filtrami
        loadSessionsList(1);
    }
    
    // Ustawienie obsługi eventów
    function setupEvents() {
        // Obsługa wyszukiwania
        $('#aica-search-input').on('keyup', function(e) {
            if (e.keyCode === 13) {
                currentFilters.search = $(this).val().trim();
                loadSessionsList(1);
            }
        });
        
        $('#aica-search-button').on('click', function() {
            currentFilters.search = $('#aica-search-input').val().trim();
            loadSessionsList(1);
        });
        
        // Obsługa filtrowania
        $('.aica-filter-button').on('click', function() {
            $('.aica-filter-dropdown').toggle();
        });
        
        $('.aica-apply-filters').on('click', function() {
            currentFilters.sortOrder = $('input[name="sort"]:checked').val();
            currentFilters.dateFrom = $('.aica-date-from').val();
            currentFilters.dateTo = $('.aica-date-to').val();
            
            loadSessionsList(1);
            $('.aica-filter-dropdown').hide();
        });
        
        $('.aica-reset-filters').on('click', function() {
            // Reset pól formularza
            $('input[name="sort"][value="newest"]').prop('checked', true);
            $('.aica-date-from, .aica-date-to').val('');
            
            // Reset filtrów
            currentFilters = {
                search: '',
                sortOrder: 'newest',
                dateFrom: '',
                dateTo: ''
            };
            
            // Odświeżenie listy
            loadSessionsList(1);
            $('.aica-filter-dropdown').hide();
        });
        
        // Zamykanie filtrów po kliknięciu poza nimi
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.aica-filter-container').length) {
                $('.aica-filter-dropdown').hide();
            }
        });
        
        // Obsługa paginacji
        $(document).on('click', '.aica-page-link:not(.current)', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'), 10);
            if (page) {
                loadSessionsList(page);
            }
        });
        
        // Obsługa rozwijania karty sesji
        $(document).on('click', '.aica-card-expand', function() {
            const card = $(this).closest('.aica-history-card');
            const expandedSection = card.find('.aica-card-expanded');
            const icon = $(this).find('.dashicons');
            
            // Zmiana ikony
            if (icon.hasClass('dashicons-arrow-down-alt2')) {
                icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
            } else {
                icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
            }
            
            // Jeśli sekcja jest już widoczna, ukryj ją
            if (expandedSection.is(':visible')) {
                expandedSection.slideUp(200);
                return;
            }
            
            // Pokaż sekcję i załaduj wiadomości
            expandedSection.slideDown(200);
            
            // Jeśli wiadomości zostały już załadowane, nie ładuj ich ponownie
            if (expandedSection.find('.aica-messages-container').children().length > 0) {
                return;
            }
            
            // Załaduj wiadomości dla sesji
            loadSessionMessages(card.data('session-id'), expandedSection);
        });
        
        // Obsługa przycisków usuwania
        $(document).on('click', '.aica-delete-session', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sessionId = $(this).data('session-id');
            const card = $(this).closest('.aica-history-card');
            
            // Pokaż dialog potwierdzający
            showDeleteDialog(sessionId, card);
        });
        
        // Potwierdzenie usunięcia sesji
        $('.aica-delete-confirm').on('click', function() {
            const dialog = $('#aica-delete-dialog');
            const sessionId = dialog.data('session-id');
            const card = dialog.data('card');
            
            deleteSession(sessionId, card);
            hideDeleteDialog();
        });
        
        // Zamknięcie dialogu potwierdzającego
        $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
            hideDeleteDialog();
        });
        
        // Obsługa eksportu i kopiowania
        $(document).on('click', '.aica-export-session', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sessionId = $(this).data('session-id');
            exportSession(sessionId);
        });
        
        $(document).on('click', '.aica-copy-session', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sessionId = $(this).data('session-id');
            duplicateSession(sessionId);
        });
        
        // Obsługa przycisku "Pokaż więcej"
        $(document).on('click', '.aica-load-more-button', function() {
            const button = $(this);
            const sessionId = button.data('session-id');
            const page = button.data('page');
            const messagesContainer = button.closest('.aica-messages-container');
            
            loadMoreMessages(sessionId, page, messagesContainer, button);
        });
    }
    
    // Inicjalizacja filtrów z URL
    function initFiltersFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Wyszukiwanie
        const searchParam = urlParams.get('s');
        if (searchParam) {
            currentFilters.search = searchParam;
            $('#aica-search-input').val(searchParam);
        }
        
        // Sortowanie
        const sortParam = urlParams.get('sort');
        if (sortParam) {
            currentFilters.sortOrder = sortParam;
            $('input[name="sort"][value="' + sortParam + '"]').prop('checked', true);
        }
        
        // Daty
        const dateFromParam = urlParams.get('date_from');
        if (dateFromParam) {
            currentFilters.dateFrom = dateFromParam;
            $('.aica-date-from').val(dateFromParam);
        }
        
        const dateToParam = urlParams.get('date_to');
        if (dateToParam) {
            currentFilters.dateTo = dateToParam;
            $('.aica-date-to').val(dateToParam);
        }
    }
    
    // Ładowanie listy sesji
    function loadSessionsList(page) {
        // Pokaż wskaźnik ładowania
        showLoadingIndicator();
        
        // Aktualizacja URL z filtrami
        updateUrlWithFilters(page);
        
        // Wywołanie AJAX do pobrania listy sesji
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_sessions_list',
                nonce: aica_history.nonce,
                page: page,
                per_page: itemsPerPage,
                search: currentFilters.search,
                sort: currentFilters.sortOrder,
                date_from: currentFilters.dateFrom,
                date_to: currentFilters.dateTo
            },
            success: function(response) {
                hideLoadingIndicator();
                
                if (response.success) {
                    renderSessionsList(response.data);
                } else {
                    showErrorMessage(response.data.message || aica_history.i18n.load_error);
                }
            },
            error: function() {
                hideLoadingIndicator();
                showErrorMessage(aica_history.i18n.load_error);
            }
        });
    }
    
    // Renderowanie listy sesji
    function renderSessionsList(data) {
        // Aktualizacja zmiennych paginacji
        currentPage = data.pagination.current_page;
        totalPages = data.pagination.total_pages;
        
        // Wyczyszczenie kontenera
        $('.aica-history-container').empty();
        
        // Jeśli nie ma sesji, pokaż pusty stan
        if (data.sessions.length === 0) {
            $('.aica-history-container').html(`
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-format-chat"></span>
                    </div>
                    <h2>${aica_history.i18n.no_conversations}</h2>
                    <p>${aica_history.i18n.no_conversations_desc}</p>
                    <a href="${aica_history.chat_url}" class="button button-primary">${aica_history.i18n.new_conversation}</a>
                </div>
            `);
            return;
        }
        
        // Generowanie kart sesji
        const sessionsHtml = data.sessions.map(session => {
            return generateSessionCard(session);
        }).join('');
        
        // Dodanie paginacji
        const paginationHtml = generatePagination(data.pagination);
        
        // Dodanie do kontenera
        $('.aica-history-container').append(sessionsHtml);
        $('.aica-history-container').append(paginationHtml);
    }
    
    // Generowanie HTML karty sesji
    function generateSessionCard(session) {
        const date = new Date(session.created_at);
        const formattedDate = date.toLocaleDateString();
        const updatedDate = new Date(session.updated_at);
        const timeAgo = getTimeAgo(updatedDate);
        
        return `
            <div class="aica-history-card" data-session-id="${session.session_id}">
                <div class="aica-card-header">
                    <div class="aica-card-title">
                        <h3>${escapeHtml(session.title)}</h3>
                        <span class="aica-session-id">${session.session_id.substring(0, 12)}...</span>
                    </div>
                    <div class="aica-card-actions">
                        <button type="button" class="aica-card-expand" aria-label="${aica_history.i18n.expand}">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="aica-dropdown">
                            <button type="button" class="aica-dropdown-toggle" aria-label="${aica_history.i18n.menu}">
                                <span class="dashicons dashicons-ellipsis"></span>
                            </button>
                            <div class="aica-dropdown-menu">
                                <a href="${aica_history.chat_url}&session_id=${session.session_id}" class="aica-dropdown-item">
                                    <span class="dashicons dashicons-format-chat"></span>
                                    ${aica_history.i18n.continue_conversation}
                                </a>
                                <a href="#" class="aica-dropdown-item aica-copy-session" data-session-id="${session.session_id}">
                                    <span class="dashicons dashicons-admin-page"></span>
                                    ${aica_history.i18n.duplicate}
                                </a>
                                <a href="#" class="aica-dropdown-item aica-export-session" data-session-id="${session.session_id}">
                                    <span class="dashicons dashicons-download"></span>
                                    ${aica_history.i18n.export}
                                </a>
                                <div class="aica-dropdown-divider"></div>
                                <a href="#" class="aica-dropdown-item aica-delete-session text-danger" data-session-id="${session.session_id}">
                                    <span class="dashicons dashicons-trash"></span>
                                    ${aica_history.i18n.delete}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="aica-card-body">
                    <div class="aica-meta-info">
                        <div class="aica-meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <span class="aica-meta-text">${formattedDate}</span>
                        </div>
                        <div class="aica-meta-item">
                            <span class="dashicons dashicons-clock"></span>
                            <span class="aica-meta-text">${date.toLocaleTimeString()}</span>
                        </div>
                        <div class="aica-meta-item">
                            <span class="dashicons dashicons-update"></span>
                            <span class="aica-meta-text">${timeAgo}</span>
                        </div>
                    </div>
                    ${session.preview ? `
                    <div class="aica-conversation-preview">
                        <div class="aica-message-preview">
                            <span class="aica-message-preview-label">${aica_history.i18n.user}:</span>
                            <span class="aica-message-preview-content">${escapeHtml(session.preview)}</span>
                        </div>
                    </div>
                    ` : ''}
                </div>
                <div class="aica-card-footer">
                    <a href="${aica_history.chat_url}&session_id=${session.session_id}" class="button button-primary aica-continue-button">
                        <span class="dashicons dashicons-format-chat"></span>
                        ${aica_history.i18n.continue_conversation}
                    </a>
                </div>
                <div class="aica-card-expanded" style="display: none;">
                    <div class="aica-loading-messages">
                        <div class="aica-loading-spinner"></div>
                        <p>${aica_history.i18n.loading_messages}</p>
                    </div>
                    <div class="aica-messages-container"></div>
                </div>
            </div>
        `;
    }
    
    // Generowanie HTML paginacji
    function generatePagination(pagination) {
        if (pagination.total_pages <= 1) {
            return '';
        }
        
        let paginationLinks = '';
        
        // Przycisk poprzedniej strony
        if (pagination.current_page > 1) {
            paginationLinks += `<a href="#" class="aica-page-link" data-page="${pagination.current_page - 1}">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>`;
        }
        
        // Linki do stron
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (
                i === 1 || 
                i === pagination.total_pages || 
                (i >= pagination.current_page - 1 && i <= pagination.current_page + 1)
            ) {
                paginationLinks += `<a href="#" class="aica-page-link ${i === pagination.current_page ? 'current' : ''}" data-page="${i}">${i}</a>`;
            } else if (
                i === pagination.current_page - 2 || 
                i === pagination.current_page + 2
            ) {
                paginationLinks += `<span class="aica-page-dots">...</span>`;
            }
        }
        
        // Przycisk następnej strony
        if (pagination.current_page < pagination.total_pages) {
            paginationLinks += `<a href="#" class="aica-page-link" data-page="${pagination.current_page + 1}">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>`;
        }
        
        // Informacja o paginacji
        const from = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const to = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
        const paginationInfo = aica_history.i18n.pagination_info
            .replace('%1$s', from)
            .replace('%2$s', to)
            .replace('%3$s', pagination.total_items);
        
        return `
            <div class="aica-pagination">
                <div class="aica-pagination-links">
                    ${paginationLinks}
                </div>
                <div class="aica-pagination-info">
                    ${paginationInfo}
                </div>
            </div>
        `;
    }
    
    // Ładowanie wiadomości dla sesji
    function loadSessionMessages(sessionId, expandedSection) {
        // Pokaż wskaźnik ładowania
        expandedSection.find('.aica-loading-messages').show();
        expandedSection.find('.aica-messages-container').empty();
        
        // Wywołanie AJAX do pobrania wiadomości
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_history.nonce,
                session_id: sessionId,
                page: 1,
                per_page: 5
            },
            success: function(response) {
                expandedSection.find('.aica-loading-messages').hide();
                
                if (response.success) {
                    renderSessionMessages(response.data, expandedSection);
                } else {
                    expandedSection.find('.aica-messages-container').html(`
                        <div class="aica-error-message">
                            <p>${response.data.message || aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                expandedSection.find('.aica-loading-messages').hide();
                expandedSection.find('.aica-messages-container').html(`
                    <div class="aica-error-message">
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
            }
        });
    }
    
    // Renderowanie wiadomości dla sesji
    function renderSessionMessages(data, expandedSection) {
        const messagesContainer = expandedSection.find('.aica-messages-container');
        const messages = data.messages;
        
        // Jeśli nie ma wiadomości, wyświetl komunikat
        if (messages.length === 0) {
            messagesContainer.html(`
                <div class="aica-empty-messages">
                    <p>${aica_history.i18n.no_messages}</p>
                </div>
            `);
            return;
        }
        
        // Generowanie HTML wiadomości
        const messagesHtml = messages.map(message => {
            return generateMessageHtml(message);
        }).join('');
        
        messagesContainer.html(messagesHtml);
        
        // Jeśli jest więcej stron, dodaj przycisk "Pokaż więcej"
        if (data.pagination.current_page < data.pagination.total_pages) {
            messagesContainer.append(`
                <div class="aica-load-more">
                    <button type="button" class="button aica-load-more-button" data-session-id="${expandedSection.closest('.aica-history-card').data('session-id')}" data-page="2">
                        ${aica_history.i18n.load_more}
                    </button>
                </div>
            `);
        }
    }
    
    // Generowanie HTML wiadomości
    function generateMessageHtml(message) {
        const messageClass = message.type === 'user' ? 'aica-message-user' : 'aica-message-ai';
        const avatarIcon = message.type === 'user' ? 'dashicons-admin-users' : 'dashicons-format-chat';
        
        return `
            <div class="aica-message ${messageClass}">
                <div class="aica-message-avatar">
                    <span class="dashicons ${avatarIcon}"></span>
                </div>
                <div class="aica-message-content">
                    <div class="aica-message-text">${formatMessageContent(message.content)}</div>
                    <div class="aica-message-time">${formatTimestamp(message.time)}</div>
                </div>
            </div>
        `;
    }
    
    // Ładowanie większej liczby wiadomości
    function loadMoreMessages(sessionId, page, messagesContainer, button) {
        // Zmień tekst przycisku na "Ładowanie..."
        button.text(aica_history.i18n.loading);
        button.prop('disabled', true);
        
        // Wywołanie AJAX do pobrania kolejnych wiadomości
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_history.nonce,
                session_id: sessionId,
                page: page,
                per_page: 5
            },
            success: function(response) {
                // Usuń przycisk "Pokaż więcej"
                button.closest('.aica-load-more').remove();
                
                if (response.success) {
                    // Dodanie nowych wiadomości
                    const messagesHtml = response.data.messages.map(message => {
                        return generateMessageHtml(message);
                    }).join('');
                    
                    messagesContainer.append(messagesHtml);
                    
                    // Jeśli jest więcej stron, dodaj nowy przycisk "Pokaż więcej"
                    if (response.data.pagination.current_page < response.data.pagination.total_pages) {
                        messagesContainer.append(`
                            <div class="aica-load-more">
                                <button type="button" class="button aica-load-more-button" data-session-id="${sessionId}" data-page="${parseInt(page) + 1}">
                                    ${aica_history.i18n.load_more}
                                </button>
                            </div>
                        `);
                    }
                } else {
                    messagesContainer.append(`
                        <div class="aica-error-message">
                            <p>${response.data.message || aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                button.text(aica_history.i18n.load_more);
                button.prop('disabled', false);
                
                messagesContainer.append(`
                    <div class="aica-error-message">
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
            }
        });
    }
    
    // Usuwanie sesji
    function deleteSession(sessionId, card) {
        // Dodaj klasę ładowania
        card.addClass('aica-card-deleting');
        
        // Wywołanie AJAX do usunięcia sesji
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj kartę za pomocą animacji i usuń ją po zakończeniu
                    card.slideUp(300, function() {
                        card.remove();
                        
                        // Sprawdź, czy to była ostatnia sesja
                        if ($('.aica-history-card').length === 0) {
                            // Pokaż komunikat o braku sesji
                            $('.aica-history-container').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <h2>${aica_history.i18n.no_conversations}</h2>
                                    <p>${aica_history.i18n.no_conversations_desc}</p>
                                    <a href="${aica_history.chat_url}" class="button button-primary">${aica_history.i18n.new_conversation}</a>
                                </div>
                            `);
                        }
                    });
                } else {
                    // Usuń klasę ładowania
                    card.removeClass('aica-card-deleting');
                    
                    // Pokaż komunikat o błędzie
                    showErrorMessage(response.data.message || aica_history.i18n.delete_error);
                }
            },
            error: function() {
                // Usuń klasę ładowania
                card.removeClass('aica-card-deleting');
                
                // Pokaż komunikat o błędzie
                showErrorMessage(aica_history.i18n.delete_error);
            }
        });
    }
    
    // Eksport sesji
    function exportSession(sessionId) {
        // Wywołanie AJAX do pobrania pełnej historii sesji
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_export_session',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Tworzenie pliku do pobrania
                    const blob = new Blob([response.data.content], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    showErrorMessage(response.data.message || aica_history.i18n.export_error);
                }
            },
            error: function() {
                showErrorMessage(aica_history.i18n.export_error);
            }
        });
    }
    
    // Duplikowanie sesji
    function duplicateSession(sessionId) {
        // Wywołanie AJAX do duplikowania sesji
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_duplicate_session',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Odświeżenie listy sesji
                    loadSessionsList(1);
                    showSuccessMessage(aica_history.i18n.duplicate_success);
                } else {
                    showErrorMessage(response.data.message || aica_history.i18n.duplicate_error);
                }
            },
            error: function() {
                showErrorMessage(aica_history.i18n.duplicate_error);
            }
        });
    }
    
    // Wyświetlanie komunikatu o błędzie
    function showErrorMessage(message) {
        const alertHtml = `
            <div class="aica-alert aica-alert-error">
                <span class="dashicons dashicons-warning"></span>
                <span class="aica-alert-message">${message}</span>
                <button type="button" class="aica-alert-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        
        showAlert(alertHtml);
    }
    
    // Wyświetlanie komunikatu o sukcesie
    function showSuccessMessage(message) {
        const alertHtml = `
            <div class="aica-alert aica-alert-success">
                <span class="dashicons dashicons-yes"></span>
                <span class="aica-alert-message">${message}</span>
                <button type="button" class="aica-alert-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        
        showAlert(alertHtml);
    }
    
    // Wyświetlanie alertu
    function showAlert(alertHtml) {
        const alertContainer = $('.aica-alert-container');
        if (alertContainer.length === 0) {
            $('body').append('<div class="aica-alert-container"></div>');
        }
        
        const alert = $(alertHtml);
        $('.aica-alert-container').append(alert);
        
        // Zamykanie alertu po kliknięciu przycisku zamknięcia
        alert.find('.aica-alert-close').on('click', function() {
            alert.fadeOut(300, function() {
                alert.remove();
            });
        });
        
        // Automatyczne ukrycie alertu po 5 sekundach
        setTimeout(function() {
            alert.fadeOut(300, function() {
                alert.remove();
            });
        }, 5000);
    }
    
    // Pokazywanie dialogu potwierdzającego usunięcie
    function showDeleteDialog(sessionId, card) {
        const dialog = $('#aica-delete-dialog');
        dialog.data('session-id', sessionId);
        dialog.data('card', card);
        dialog.show();
    }
    
    // Ukrywanie dialogu potwierdzającego usunięcie
    function hideDeleteDialog() {
        $('#aica-delete-dialog').hide();
    }
    
    // Pokazywanie wskaźnika ładowania
    function showLoadingIndicator() {
        $('.aica-history-container').html(`
            <div class="aica-loading">
               <div class="aica-loading-spinner"></div>
                <p>${aica_history.i18n.loading}</p>
            </div>
        `);
    }
    
    // Ukrywanie wskaźnika ładowania
    function hideLoadingIndicator() {
        $('.aica-loading').remove();
    }
    
    // Aktualizacja URL z filtrami
    function updateUrlWithFilters(page) {
        const url = new URL(window.location.href);
        
        // Usunięcie wszystkich parametrów
        url.searchParams.delete('s');
        url.searchParams.delete('sort');
        url.searchParams.delete('date_from');
        url.searchParams.delete('date_to');
        url.searchParams.delete('paged');
        
        // Dodanie aktualnych filtrów
        if (currentFilters.search) {
            url.searchParams.set('s', currentFilters.search);
        }
        
        if (currentFilters.sortOrder && currentFilters.sortOrder !== 'newest') {
            url.searchParams.set('sort', currentFilters.sortOrder);
        }
        
        if (currentFilters.dateFrom) {
            url.searchParams.set('date_from', currentFilters.dateFrom);
        }
        
        if (currentFilters.dateTo) {
            url.searchParams.set('date_to', currentFilters.dateTo);
        }
        
        // Dodanie numeru strony
        if (page > 1) {
            url.searchParams.set('paged', page);
        }
        
        // Aktualizacja URL bez przeładowania strony
        window.history.pushState({}, '', url.toString());
    }
    
    // Formatowanie zawartości wiadomości
    function formatMessageContent(content) {
        // Obsługa markdown
        content = escapeHtml(content);
        
        // Obsługa bloków kodu
        content = content.replace(/```([a-z]*)\n([\s\S]*?)```/g, function(match, language, code) {
            return '<pre><code class="language-' + language + '">' + code + '</code></pre>';
        });
        
        // Obsługa pojedynczych linii kodu
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Obsługa pogrubienia
        content = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // Obsługa kursywy
        content = content.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        // Obsługa linków
        content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        return content;
    }
    
    // Formatowanie znacznika czasowego
    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        
        const date = new Date(timestamp);
        
        // Jeśli data jest nieprawidłowa, zwróć surowy znacznik czasu
        if (isNaN(date.getTime())) {
            return timestamp;
        }
        
        return date.toLocaleString();
    }
    
    // Uzyskanie czasu "temu"
    function getTimeAgo(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        const diffMonth = Math.floor(diffDay / 30);
        const diffYear = Math.floor(diffMonth / 12);
        
        if (diffYear > 0) {
            return `${diffYear} ${diffYear === 1 ? aica_history.i18n.year : aica_history.i18n.years} ${aica_history.i18n.ago}`;
        } else if (diffMonth > 0) {
            return `${diffMonth} ${diffMonth === 1 ? aica_history.i18n.month : aica_history.i18n.months} ${aica_history.i18n.ago}`;
        } else if (diffDay > 0) {
            return `${diffDay} ${diffDay === 1 ? aica_history.i18n.day : aica_history.i18n.days} ${aica_history.i18n.ago}`;
        } else if (diffHour > 0) {
            return `${diffHour} ${diffHour === 1 ? aica_history.i18n.hour : aica_history.i18n.hours} ${aica_history.i18n.ago}`;
        } else if (diffMin > 0) {
            return `${diffMin} ${diffMin === 1 ? aica_history.i18n.minute : aica_history.i18n.minutes} ${aica_history.i18n.ago}`;
        } else {
            return aica_history.i18n.just_now;
        }
    }
    
    // Escapowanie HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function() {
        init();
        
        // Zamykanie modali po kliknięciu poza nimi
        $(window).on('click', function(e) {
            if ($(e.target).is('#aica-delete-dialog')) {
                hideDeleteDialog();
            }
        });
    });
    
})(jQuery);