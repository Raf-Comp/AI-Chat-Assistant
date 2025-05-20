/**
 * Modern Chat JS 2025 - AI Chat Assistant
 * JavaScript dla interaktywnego czatu z Claude.ai
 */
(function($) {
    'use strict';
    
    // Zmienne globalne
    let currentSessionId = null;
    let currentFilePath = null;
    let currentRepositoryId = null;
    let currentRepositoryPath = '';
    let isProcessing = false;
    let currentPage = 1;
    let totalPages = 1;
    let messagesPerPage = 20;
    let isLoadingMoreMessages = false;
    let isDarkMode = localStorage.getItem('aica_dark_mode') === 'true';
    let isCompactMode = localStorage.getItem('aica_compact_mode') === 'true';
    
    // Główna funkcja inicjalizująca
    function init() {
        // Inicjalizacja motywu
        updateTheme();
        
        // Inicjalizacja eventów
        setupEvents();
        
        // Wczytanie sesji z localStorage lub utworzenie nowej
        loadOrCreateSession();
        
        // Wczytanie listy repozytoriów
        loadRepositories();
        
        console.log('AI Chat Assistant zainicjalizowany');
    }
    
    // Aktualizacja motywu strony
    function updateTheme() {
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
        
        if (isCompactMode) {
            document.body.classList.add('aica-compact-view');
        } else {
            document.body.classList.remove('aica-compact-view');
        }
    }
    
    // Ustawienie obsługi eventów
    function setupEvents() {
        // Obsługa wysyłania wiadomości
        $('#chat-form').on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Obsługa kliknięcia przycisku wysyłania
        $('#aica-send-message').on('click', function() {
            sendMessage();
        });

        // Dynamiczne dostosowanie wysokości pola tekstowego
        $('#aica-message-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            updateSendButtonState();
        });

        // Obsługa przycisków nawigacyjnych
        $('#aica-new-chat').on('click', createNewSession);
        
        // Obsługa przełącznika motywu
        $('#aica-theme-toggle').on('click', toggleDarkMode);
        
        // Obsługa przycisków zarządzania sesją
        $(document).on('click', '.aica-session-item', function() {
            const sessionId = $(this).data('session-id');
            loadSession(sessionId);
        });

        $(document).on('click', '.aica-delete-session', function(e) {
            e.stopPropagation();
            const sessionId = $(this).parent().data('session-id');
            showDeleteConfirmation(sessionId);
        });
        
        // Obsługa zakładek
        $('.aica-tab').on('click', function() {
            const tabId = $(this).data('tab');
            activateTab(tabId);
        });

        // Obsługa przycisku "załaduj więcej"
        $(document).on('click', '#aica-load-more', function() {
            if (!isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });

        // Obsługa przycisków zarządzania repozytorium
        $(document).on('click', '.aica-repository-item', function() {
            const repoId = $(this).data('repo-id');
            loadRepository(repoId);
        });
        
        // Obsługa dropdown menu
        $('.aica-more-button').on('click', function(e) {
            e.stopPropagation();
            $('.aica-dropdown').toggleClass('open');
        });
        
        // Zamykanie dropdown menu po kliknięciu poza
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.aica-dropdown').length) {
                $('.aica-dropdown').removeClass('open');
            }
        });
        
        // Obsługa przykładowych promptów
        $('.aica-example-prompt').on('click', function() {
            $('#aica-message-input').val($(this).text().trim());
            $('#aica-message-input').trigger('input');
            $('#aica-welcome-screen').hide();
            $('#aica-messages').show();
        });
        
        // Obsługa akcji
        $('#aica-export-chat').on('click', exportConversation);
        $('#aica-copy-conversation').on('click', copyConversation);
        $('#aica-rename-conversation').on('click', showRenameModal);
        $('#aica-delete-conversation').on('click', showDeleteConfirmation);
        
        // Obsługa modalu zmiany nazwy
        $('.aica-modal-save').on('click', renameConversation);
        $('.aica-modal-close, .aica-modal-cancel').on('click', hideModal);
        
        // Obsługa dialogu usuwania
        $('.aica-dialog-confirm').on('click', confirmDelete);
        $('.aica-dialog-close, .aica-dialog-cancel').on('click', hideDialog);
        
        // Obsługa przełącznika panelu bocznego
        $('.aica-sidebar-toggle').on('click', toggleSidebar);
        
        // Obsługa przesyłania plików
        $('#aica-file-upload').on('change', handleFileUpload);
        $('.aica-remove-file-button').on('click', removeSelectedFile);
        
        // Obsługa klawiszy
        $('#aica-message-input').on('keydown', function(e) {
            // Wysłanie wiadomości na Ctrl+Enter
            if (e.ctrlKey && e.keyCode === 13) {
                sendMessage();
            }
        });

        // Obsługa przewijania - wykrywanie, gdy użytkownik przewinie do góry
        $('#aica-messages-container').on('scroll', function() {
            if ($(this).scrollTop() < 50 && currentPage < totalPages && !isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });
        
        // Obsługa responsywności na mobile
        setupMobileEvents();
    }
    
    // Obsługa wydarzeń mobilnych
    function setupMobileEvents() {
        // Dodajemy przycisk mobile toggle, jeśli jest potrzebny
        if (window.innerWidth <= 992 && !$('.aica-mobile-toggle').length) {
            $('body').append('<button class="aica-mobile-toggle"><span class="dashicons dashicons-menu-alt"></span></button>');
            $('body').append('<div class="aica-mobile-backdrop"></div>');
            
            $('.aica-mobile-toggle').on('click', function() {
                $('.aica-sidebar').toggleClass('open');
            });
            
            $('.aica-mobile-backdrop').on('click', function() {
                $('.aica-sidebar').removeClass('open');
            });
        }
        
        // Na resize okna
        $(window).on('resize', function() {
            if (window.innerWidth <= 992 && !$('.aica-mobile-toggle').length) {
                $('body').append('<button class="aica-mobile-toggle"><span class="dashicons dashicons-menu-alt"></span></button>');
                $('body').append('<div class="aica-mobile-backdrop"></div>');
                
                $('.aica-mobile-toggle').on('click', function() {
                    $('.aica-sidebar').toggleClass('open');
                });
                
                $('.aica-mobile-backdrop').on('click', function() {
                    $('.aica-sidebar').removeClass('open');
                });
            } else if (window.innerWidth > 992) {
                $('.aica-mobile-toggle').remove();
                $('.aica-mobile-backdrop').remove();
                $('.aica-sidebar').removeClass('open');
            }
        });
    }
    
    // Ustawienie stanu przycisku wysyłania
    function updateSendButtonState() {
        const messageInput = $('#aica-message-input');
        
        if (messageInput.val().trim() === '') {
            $('#aica-send-message').prop('disabled', true);
        } else {
            $('#aica-send-message').prop('disabled', false);
        }
    }
    
    // Aktywacja zakładki
    function activateTab(tabId) {
        $('.aica-tab').removeClass('active');
        $(`.aica-tab[data-tab="${tabId}"]`).addClass('active');
        
        $('.aica-tab-content').removeClass('active');
        $(`#${tabId}-content`).addClass('active');
    }
    
    // Przełączanie sidebar
    function toggleSidebar() {
        $('.aica-sidebar').toggleClass('collapsed');
    }
    
    // Przełączanie trybu ciemnego
    function toggleDarkMode() {
        isDarkMode = !isDarkMode;
        localStorage.setItem('aica_dark_mode', isDarkMode);
        updateTheme();
    }
    
    // Funkcja do wysyłania wiadomości
    function sendMessage() {
        const messageInput = $('#aica-message-input');
        const message = messageInput.val().trim();
        
        if (message === '' || isProcessing) {
            return;
        }

        isProcessing = true;
        
        // Jeśli to pierwszy komunikat, ukryjmy ekran powitalny
        if ($('#aica-welcome-screen').is(':visible')) {
            $('#aica-welcome-screen').hide();
            $('#aica-messages').show();
        }
        
        // Dodanie wiadomości użytkownika do czatu
        appendMessage('user', message);
        
        // Wyczyszczenie pola wprowadzania
        messageInput.val('');
        messageInput.css('height', 'auto');
        updateSendButtonState();
        
        // Pokazanie wskaźnika ładowania
        showTypingIndicator();
        
        // Wywołanie API Claude.ai
        callClaudeAPI(message)
            .then(response => {
                // Ukrycie wskaźnika ładowania
                hideTypingIndicator();
                
                // Dodanie odpowiedzi do czatu
                appendMessage('assistant', response.content);
                
                // Zapisanie konwersacji
                saveConversation(message, response.content);
                
                isProcessing = false;
            })
            .catch(error => {
                console.error('Błąd komunikacji z API:', error);
                hideTypingIndicator();
                appendMessage('system', 'Wystąpił błąd podczas komunikacji z API. Spróbuj ponownie.');
                isProcessing = false;
            });
    }

    // Funkcja wywołująca API Claude.ai
    function callClaudeAPI(message) {
        return new Promise((resolve, reject) => {
            const contextData = currentRepositoryId ? {
                repositoryId: currentRepositoryId,
                filePath: currentFilePath
            } : null;
            
            $.ajax({
                url: aica_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aica_send_message',
                    nonce: aica_data.nonce,
                    session_id: currentSessionId,
                    message: message,
                    context: contextData
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data.message || 'Błąd API');
                    }
                },
                error: function(xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    // Funkcja dodająca wiadomość do czatu
    function appendMessage(sender, content, timestamp = null) {
        const messagesContainer = $('#aica-messages');
        
        if (messagesContainer.length === 0) {
            return;
        }
        
        // Jeśli nie ma timestampu, używamy bieżącej daty
        if (!timestamp) {
            const now = new Date();
            timestamp = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Formatowanie kodu markdown, jeśli treść zawiera kod
        if (sender === 'assistant' || sender === 'system') {
            content = formatMarkdown(content);
        }
        
        // Tworzenie elementu wiadomości
        const messageClass = sender === 'user' ? 'aica-user-message' : 
                           (sender === 'assistant' ? 'aica-ai-message' : 'aica-system-message');
        
        const messageElement = $(`<div class="aica-message ${messageClass}"></div>`);
        const messageBubble = $('<div class="aica-message-bubble"></div>');
        messageBubble.html(content);
        messageElement.append(messageBubble);
        
        // Dodanie znacznika czasu
        const timeElement = $('<div class="aica-message-info"></div>');
        timeElement.text(timestamp);
        messageElement.append(timeElement);
        
        messagesContainer.append(messageElement);
        
        // Przewinięcie do najnowszej wiadomości
        scrollToBottom();
        
        // Inicjalizacja podświetlania składni, jeśli jest dostępne
        if (window.Prism) {
            Prism.highlightAll();
        }
    }

    // Funkcja do formatowania markdown w odpowiedzi
    function formatMarkdown(content) {
        // Obsługa bloków kodu
        content = content.replace(/```([a-z]*)\n([\s\S]*?)```/g, function(match, language, code) {
            return `<pre><code class="language-${language}">${escapeHTML(code)}</code></pre>`;
        });
        
        // Obsługa pojedynczych linii kodu
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Obsługa pogrubienia
        content = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // Obsługa kursywy
        content = content.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        // Obsługa list nieuporządkowanych
        content = content.replace(/^\s*-\s+(.+)$/gm, '<li>$1</li>');
        content = content.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
        
        // Obsługa list uporządkowanych
        content = content.replace(/^\s*(\d+)\.\s+(.+)$/gm, '<li>$2</li>');
        content = content.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');
        
        // Obsługa linków
        content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');
        
        return content;
    }

    // Funkcja do escapowania HTML
    function escapeHTML(html) {
        return html
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Funkcja przewijająca czat do dołu
    function scrollToBottom() {
        const container = $('#aica-messages-container');
        container.scrollTop(container.prop('scrollHeight'));
    }
    
    // Pokazanie wskaźnika pisania
    function showTypingIndicator() {
        // Usuń wcześniejszy wskaźnik, jeśli istnieje
        $('.aica-typing-message').remove();
        
        // Stwórz nowy wskaźnik
        const typingMessage = $(`
            <div class="aica-message aica-ai-message aica-typing-message">
                <div class="aica-message-bubble">
                    <div class="aica-typing-indicator">
                        <span class="aica-typing-dot"></span>
                        <span class="aica-typing-dot"></span>
                        <span class="aica-typing-dot"></span>
                    </div>
                </div>
            </div>
        `);
        
        $('#aica-messages').append(typingMessage);
        scrollToBottom();
        
        // Pokaż status w stopce
        $('.aica-status-typing').show();
    }
    
    // Ukrycie wskaźnika pisania
    function hideTypingIndicator() {
        $('.aica-typing-message').remove();
        $('.aica-status-typing').hide();
    }

    // Funkcje zarządzania sesją
    function loadOrCreateSession() {
        currentSessionId = localStorage.getItem('currentSessionId');
        
        if (!currentSessionId) {
            createNewSession();
        } else {
            loadSession(currentSessionId);
        }
    }

    function createNewSession() {
        // Wywołanie AJAX do utworzenia nowej sesji
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_create_session',
                nonce: aica_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentSessionId = response.data.session_id;
                    localStorage.setItem('currentSessionId', currentSessionId);
                    
                    // Wyczyszczenie czatu
                    $('#aica-messages').empty();
                    
                    // Pokazanie powitania
                    $('#aica-messages').hide();
                    $('#aica-welcome-screen').show();
                    
                    // Aktualizacja tytułu
                    $('#aica-conversation-title').text('Nowa rozmowa');
                    $('#aica-conversation-date').text(new Date().toLocaleDateString());
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                } else {
                    console.error('Nie udało się utworzyć sesji:', response.data.message);
                    showNotification('error', 'Nie udało się utworzyć nowej sesji.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas tworzenia sesji:', error);
                showNotification('error', 'Wystąpił błąd podczas tworzenia sesji.');
            }
        });
    }

    function loadSession(sessionId) {
        currentSessionId = sessionId;
        localStorage.setItem('currentSessionId', sessionId);
        
        // Wyczyszczenie czatu
        $('#aica-messages').empty();
        showLoadingSpinner();
        
        // Ukrycie ekranu powitalnego, pokazanie czatu
        $('#aica-welcome-screen').hide();
        $('#aica-messages').show();
        
        // Wywołanie AJAX do pobrania historii czatu
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_data.nonce,
                session_id: sessionId,
                page: 1,
                per_page: messagesPerPage
            },
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodanie wiadomości do czatu
                    historyData.messages.forEach(msg => {
                        appendMessage(msg.type, msg.content, formatTimestamp(msg.time));
                    });
                    
                    // Jeśli są dostępne wcześniejsze strony, pokaż przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        showLoadMoreButton();
                    }
                    
                    // Aktualizacja tytułu sesji
                    $('#aica-conversation-title').text(historyData.title);
                    
                    // Pokaż datę jako LocaleDateString
                    const date = new Date(historyData.messages[0]?.time || Date.now());
                    $('#aica-conversation-date').text(date.toLocaleDateString());
                    
                    // Oznaczenie aktywnej sesji na liście
                    $('.aica-session-item').removeClass('active');
                    $(`.aica-session-item[data-session-id="${sessionId}"]`).addClass('active');
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                    appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingSpinner();
                console.error('Błąd ładowania historii czatu:', error);
                appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
            }
        });
        
        // Schowanie panelu bocznego na mobilce po wybraniu sesji
        if (window.innerWidth <= 992) {
            $('.aica-sidebar').removeClass('open');
        }
    }
    
    // Formatowanie znacznika czasowego dla wyświetlania
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Funkcja ładująca więcej wiadomości (paginacja)
    function loadMoreMessages() {
        if (isLoadingMoreMessages || currentPage >= totalPages) {
            return;
        }
        
        isLoadingMoreMessages = true;
        
        // Zmiana tekstu przycisku "załaduj więcej"
        $('#aica-load-more').html('<div class="aica-spinner"></div>');
        
        // Wywołanie AJAX do pobrania kolejnej strony wiadomości
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                page: currentPage + 1,
                per_page: messagesPerPage
            },
            success: function(response) {
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodawanie starszych wiadomości na górze
                    const chatContainer = $('#aica-messages');
                    const scrollPos = $('#aica-messages-container').scrollTop();
                    const scrollHeight = $('#aica-messages-container').prop('scrollHeight');
                    
                    // Dodawanie starszych wiadomości na początku
                    historyData.messages.forEach(msg => {
                        const messageElement = createMessageElement(msg.type, msg.content, formatTimestamp(msg.time));
                        chatContainer.prepend(messageElement);
                    });
                    
                    // Inicjalizacja podświetlania składni, jeśli jest dostępne
                    if (window.Prism) {
                        Prism.highlightAll();
                    }
                    
                    // Zachowanie pozycji przewijania
                    const newScrollHeight = $('#aica-messages-container').prop('scrollHeight');
                    $('#aica-messages-container').scrollTop(scrollPos + (newScrollHeight - scrollHeight));
                    
                    // Jeśli są dostępne wcześniejsze strony, zaktualizuj przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        $('#aica-load-more').text('Załaduj wcześniejsze wiadomości');
                    } else {
                        $('#aica-load-more').remove();
                    }
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                    showNotification('error', 'Nie udało się załadować wcześniejszych wiadomości.');
                }
                
                isLoadingMoreMessages = false;
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania historii czatu:', error);
                $('#aica-load-more').text('Załaduj wcześniejsze wiadomości');
                isLoadingMoreMessages = false;
                showNotification('error', 'Wystąpił błąd podczas ładowania wiadomości.');
            }
        });
    }
    
    // Tworzenie elementu wiadomości
    function createMessageElement(sender, content, timestamp) {
        const messageClass = sender === 'user' ? 'aica-user-message' : 
                           (sender === 'assistant' ? 'aica-ai-message' : 'aica-system-message');
        
        // Formatowanie kodu markdown, jeśli treść zawiera kod
        if (sender === 'assistant' || sender === 'system') {
            content = formatMarkdown(content);
        }
        
        const messageElement = $(`<div class="aica-message ${messageClass}"></div>`);
        const messageBubble = $('<div class="aica-message-bubble"></div>');
        messageBubble.html(content);
        messageElement.append(messageBubble);
        
        // Dodanie znacznika czasu
        const timeElement = $('<div class="aica-message-info"></div>');
        timeElement.text(timestamp);
        messageElement.append(timeElement);
        
        return messageElement;
    }
    
    // Pokazanie przycisku "załaduj więcej"
    function showLoadMoreButton() {
        // Jeśli przycisk już istnieje, tylko go pokaż
        if ($('#aica-load-more').length) {
            $('#aica-load-more').show();
        } else {
            // Utwórz przycisk i dodaj go na początku kontenera czatu
            const loadMoreButton = $('<div id="aica-load-more" class="aica-load-more">Załaduj wcześniejsze wiadomości</div>');
            $('#aica-messages').prepend(loadMoreButton);
        }
    }
    
    // Funkcja zapisująca konwersację
    function saveConversation(userMessage, assistantResponse) {
        // Jeśli nie mamy ID sesji, najpierw ją utwórzmy
        if (!currentSessionId) {
            createNewSession().then(() => {
                saveMessage(userMessage, assistantResponse);
            });
        } else {
            saveMessage(userMessage, assistantResponse);
        }
    }
    
    // Zapisanie wiadomości do bazy danych
    function saveMessage(userMessage, assistantResponse) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_save_message',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                user_message: userMessage,
                assistant_response: assistantResponse
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Błąd zapisywania wiadomości:', response.data.message);
                }
                
                // Aktualizacja listy sesji
                loadSessionsList();
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas zapisywania wiadomości:', error);
            }
        });
    }
    
    // Ładowanie listy sesji
    function loadSessionsList() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_sessions_list',
                nonce: aica_data.nonce,
                per_page: 10  // Pobierz 10 najnowszych sesji
            },
            success: function(response) {
                if (response.success) {
                    renderSessionsList(response.data.sessions);
                } else {
                    console.error('Błąd pobierania listy sesji:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas pobierania listy sesji:', error);
            }
        });
    }
    
    // Renderowanie listy sesji
    function renderSessionsList(sessions) {
        const sessionsList = $('#aica-sessions-list');
        
        // Jeśli nie ma sesji, pokaż komunikat
        if (sessions.length === 0) {
            sessionsList.html('<div class="aica-empty-message">Brak zapisanych rozmów</div>');
            return;
        }
        
        sessionsList.empty();
        
        sessions.forEach(session => {
            const sessionDate = new Date(session.updated_at);
            const formattedDate = sessionDate.toLocaleDateString();
            
            const sessionItem = $(`
                <div class="aica-session-item ${session.session_id === currentSessionId ? 'active' : ''}" data-session-id="${session.session_id}">
                    <div class="aica-session-item-header">
                        <div class="aica-session-title">${session.title}</div>
                        <div class="aica-session-date">${formattedDate}</div>
                    </div>
                    <div class="aica-session-preview">${session.preview || 'Nowa rozmowa'}</div>
                    <div class="aica-session-actions">
                        <button class="aica-delete-session" title="Usuń rozmowę">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `);
            
            sessionsList.append(sessionItem);
        });
    }
    
    // Obsługa repozytoriów
    function loadRepositories() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_repositories',
                nonce: aica_data.nonce
            },
            success: function(response) {
                const repositoriesList = $('#aica-repositories-list');
                
                if (!response.success || !response.data.repositories || response.data.repositories.length === 0) {
                    repositoriesList.html('<div class="aica-empty-message">Brak dostępnych repozytoriów</div>');
                    return;
                }
                
                repositoriesList.empty();
                
                response.data.repositories.forEach(repo => {
                    const repoItem = $(`
                        <div class="aica-repository-item" data-repo-id="${repo.id}">
                            <div class="aica-repo-icon">
                                <span class="dashicons dashicons-code-standards"></span>
                            </div>
                            <div class="aica-repo-info">
                                <div class="aica-repo-title">${repo.name}</div>
                                <div class="aica-repo-description">${repo.description || 'Brak opisu'}</div>
                            </div>
                        </div>
                    `);
                    
                    repositoriesList.append(repoItem);
                });
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania repozytoriów:', error);
            }
        });
    }
    
    // Obsługa plików
    function handleFileUpload(event) {
        const file = event.target.files[0];
        
        if (!file) {
            return;
        }
        
        // Pokazanie wskaźnika ładowania
        showLoadingSpinner();
        
        // Utworzenie obiektu FormData do przesłania pliku
        const formData = new FormData();
        formData.append('action', 'aica_upload_file');
        formData.append('nonce', aica_data.nonce);
        formData.append('file', file);
        
        // Wysłanie pliku na serwer
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    // Aktualizacja UI z informacją o wybranym pliku
                    $('#aica-selected-file-name').text(file.name);
                    $('#aica-selected-file-info').show();
                    
                    // Dodanie treści pliku do inputu
                    const currentText = $('#aica-message-input').val();
                    const fileQuestion = `Proszę przeanalizować ten kod:\n\n${response.data.file_content}`;
                    
                    $('#aica-message-input').val(currentText ? `${currentText}\n\n${fileQuestion}` : fileQuestion);
                    $('#aica-message-input').trigger('input');
                } else {
                    showNotification('error', response.data.message || 'Nie udało się przesłać pliku.');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingSpinner();
                console.error('Błąd podczas przesyłania pliku:', error);
                showNotification('error', 'Wystąpił błąd podczas przesyłania pliku.');
            }
        });
    }
    
    // Usuwanie wybranego pliku
    function removeSelectedFile() {
        $('#aica-selected-file-info').hide();
        $('#aica-file-upload').val('');
    }
    
    // Eksportowanie rozmowy
    function exportConversation() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do eksportu.');
            return;
        }
        
        // Pobieranie wiadomości z czatu
        const messages = [];
        $('#aica-messages .aica-message').each(function() {
            const sender = $(this).hasClass('aica-user-message') ? 'Użytkownik' : 'Claude';
            const content = $(this).find('.aica-message-bubble').html();
            const time = $(this).find('.aica-message-info').text();
            
            messages.push(`[${time}] ${sender}:\n${$(this).find('.aica-message-bubble').text()}`);
        });
        
        // Tworzenie zawartości pliku
        const title = $('#aica-conversation-title').text();
        const date = $('#aica-conversation-date').text();
        const content = `# ${title}\n${date}\n\n${messages.join('\n\n')}`;
        
        // Tworzenie i pobieranie pliku
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_${Date.now()}.md`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showNotification('success', 'Rozmowa została wyeksportowana.');
    }
    
    // Kopiowanie całej rozmowy
    function copyConversation() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do skopiowania.');
            return;
        }
        
        // Pobieranie wiadomości z czatu
        const messages = [];
        $('#aica-messages .aica-message').each(function() {
            const sender = $(this).hasClass('aica-user-message') ? 'Użytkownik' : 'Claude';
            
            messages.push(`${sender}: ${$(this).find('.aica-message-bubble').text()}`);
        });
        
        // Kopiowanie do schowka
        const content = messages.join('\n\n');
        navigator.clipboard.writeText(content).then(() => {
            showNotification('success', 'Rozmowa została skopiowana do schowka.');
        }).catch(err => {
            console.error('Nie udało się skopiować tekstu: ', err);
            showNotification('error', 'Nie udało się skopiować rozmowy.');
        });
    }
    
    // Pokazanie modalu zmiany nazwy
    function showRenameModal() {
        if (!currentSessionId) {
            showNotification('error', 'Brak aktywnej rozmowy do zmiany nazwy.');
            return;
        }
        
        $('#aica-conversation-new-title').val($('#aica-conversation-title').text());
        $('#aica-rename-modal').css('display', 'flex');
        $('#aica-conversation-new-title').focus();
    }
    
    // Zmiana nazwy rozmowy
    function renameConversation() {
        const newTitle = $('#aica-conversation-new-title').val().trim();
        
        if (!newTitle) {
            showNotification('error', 'Nazwa rozmowy nie może być pusta.');
            return;
        }
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_rename_session',
                nonce: aica_data.nonce,
                session_id: currentSessionId,
                title: newTitle
            },
            success: function(response) {
                if (response.success) {
                    // Aktualizacja UI
                    $('#aica-conversation-title').text(newTitle);
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                    
                    hideModal();
                    showNotification('success', 'Nazwa rozmowy została zmieniona.');
                } else {
                    console.error('Błąd zmiany nazwy:', response.data.message);
                    showNotification('error', 'Nie udało się zmienić nazwy rozmowy.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas zmiany nazwy rozmowy:', error);
                showNotification('error', 'Wystąpił błąd podczas zmiany nazwy rozmowy.');
            }
        });
    }
    
    // Ukrycie modalu
    function hideModal() {
        $('#aica-rename-modal').hide();
    }
    
    // Pokazanie dialogu potwierdzenia usunięcia
    function showDeleteConfirmation(sessionId = null) {
        // Jeśli podano ID sesji, zapisz je tymczasowo
        if (sessionId) {
            $('#aica-delete-dialog').data('session-id', sessionId);
        } else if (currentSessionId) {
            $('#aica-delete-dialog').data('session-id', currentSessionId);
        } else {
            showNotification('error', 'Brak rozmowy do usunięcia.');
            return;
        }
        
        $('#aica-delete-dialog').css('display', 'flex');
    }
    
    // Usunięcie rozmowy po potwierdzeniu
    function confirmDelete() {
        const sessionId = $('#aica-delete-dialog').data('session-id');
        
        if (!sessionId) {
            hideDialog();
            return;
        }
        
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_data.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Jeśli usuwamy aktualną sesję, stwórz nową
                    if (sessionId === currentSessionId) {
                        createNewSession();
                    }
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                    
                    hideDialog();
                    showNotification('success', 'Rozmowa została usunięta.');
                } else {
                    console.error('Błąd usuwania sesji:', response.data.message);
                    showNotification('error', 'Nie udało się usunąć rozmowy.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas usuwania sesji:', error);
                showNotification('error', 'Wystąpił błąd podczas usuwania rozmowy.');
            }
        });
    }
    
    // Ukrycie dialogu
    function hideDialog() {
        $('#aica-delete-dialog').hide();
        $('#aica-delete-dialog').removeData('session-id');
    }
    
    // Pokazanie powiadomienia
    function showNotification(type, message) {
        // Usuń istniejące powiadomienia
        $('.aica-notification').remove();
        
        // Utwórz nowe powiadomienie
        const notification = $(`
            <div class="aica-notification aica-notification-${type}">
                <div class="aica-notification-content">
                    <span class="aica-notification-message">${message}</span>
                    <button class="aica-notification-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        `);
        
        // Dodaj powiadomienie do dokumentu
        $('body').append(notification);
        
        // Ustaw event na przycisk zamknięcia
        notification.find('.aica-notification-close').on('click', function() {
            notification.remove();
        });
        
        // Automatyczne zamknięcie po 5 sekundach
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Pokazanie spinnera ładowania
    function showLoadingSpinner() {
        // Jeśli już istnieje, nie twórz nowego
        if ($('.aica-loading').length) {
            return;
        }
        
        const spinner = $(`
            <div class="aica-loading">
                <div class="aica-spinner"></div>
                <span>Ładowanie...</span>
            </div>
        `);
        
        $('#aica-messages').append(spinner);
    }
    
    // Ukrycie spinnera ładowania
    function hideLoadingSpinner() {
        $('.aica-loading').remove();
    }
    
    // Inicjalizacja
    $(document).ready(function() {
        init();
    });

})(jQuery);