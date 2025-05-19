/**
 * Skrypt obsługujący czat z Claude.ai
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

    // Główna funkcja inicjalizująca
    function init() {
        // Inicjalizacja eventów
        setupEvents();
        // Wczytanie sesji z localStorage lub utworzenie nowej
        loadOrCreateSession();
        // Wczytanie listy repozytoriów
        loadRepositories();
    }
    
    // Ustawienie obsługi eventów
    function setupEvents() {
        // Obsługa wysyłania wiadomości
        $('#chat-form').on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Obsługa kliknięcia przycisku wysyłania
        $('#send-button').on('click', function() {
            sendMessage();
        });

        // Dynamiczne dostosowanie wysokości pola tekstowego
        $('#message-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Obsługa przycisków nawigacyjnych
        $('#new-chat-button').on('click', createNewSession);
        $('#sessions-button').on('click', toggleSessionsList);
        $('#repositories-button').on('click', toggleRepositoriesList);

        // Obsługa przycisków zarządzania sesją
        $(document).on('click', '.session-item', function() {
            const sessionId = $(this).data('session-id');
            loadSession(sessionId);
        });

        $(document).on('click', '.delete-session', function(e) {
            e.stopPropagation();
            const sessionId = $(this).parent().data('session-id');
            deleteSession(sessionId);
        });

        // Obsługa przycisku "załaduj więcej"
        $(document).on('click', '#load-more-messages', function() {
            if (!isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });

        // Obsługa przycisków zarządzania repozytorium
        $(document).on('click', '.repository-item', function() {
            const repoId = $(this).data('repo-id');
            loadRepository(repoId);
        });

        // Obsługa klawiszy
        $('#message-input').on('keydown', function(e) {
            // Wysłanie wiadomości na Ctrl+Enter
            if (e.ctrlKey && e.keyCode === 13) {
                sendMessage();
            }
        });

        // Obsługa przewijania - wykrywanie, gdy użytkownik przewinie do góry
        $('#chat-messages').on('scroll', function() {
            if ($(this).scrollTop() === 0 && currentPage < totalPages && !isLoadingMoreMessages) {
                loadMoreMessages();
            }
        });
    }

    // Funkcja do wysyłania wiadomości
    function sendMessage() {
        const messageInput = $('#message-input');
        const message = messageInput.val().trim();
        
        if (message === '' || isProcessing) {
            return;
        }

        isProcessing = true;
        
        // Dodanie wiadomości użytkownika do czatu
        appendMessage('user', message);
        
        // Wyczyszczenie pola wprowadzania
        messageInput.val('');
        messageInput.css('height', 'auto');
        
        // Pokazanie wskaźnika ładowania
        showLoadingIndicator();
        
        // Wywołanie API Claude.ai
        callClaudeAPI(message)
            .then(response => {
                // Ukrycie wskaźnika ładowania
                hideLoadingIndicator();
                
                // Dodanie odpowiedzi do czatu
                appendMessage('assistant', response.content);
                
                // Zapisanie konwersacji
                saveConversation(message, response.content);
                
                isProcessing = false;
            })
            .catch(error => {
                console.error('Błąd komunikacji z API:', error);
                hideLoadingIndicator();
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
    function appendMessage(sender, content, timestamp) {
        const messageContainer = $('#chat-messages');
        const messageElement = createMessageElement(sender, content, timestamp);
        messageContainer.append(messageElement);
        
        // Przewinięcie do najnowszej wiadomości
        messageContainer.scrollTop(messageContainer[0].scrollHeight);
    }

    // Funkcja tworząca element wiadomości
    function createMessageElement(sender, content, timestamp) {
        const messageClass = sender === 'user' ? 'user-message' : 
                           (sender === 'assistant' ? 'assistant-message' : 'system-message');
        
        // Formatowanie kodu markdown, jeśli treść zawiera kod
        if (sender === 'assistant') {
            content = formatMarkdown(content);
        }
        
        const messageElement = $('<div class="message ' + messageClass + '"></div>');
        messageElement.html(content);
        
        // Dodanie znacznika czasu (opcjonalnie)
        if (timestamp) {
            const timeElement = $('<div class="message-time"></div>');
            timeElement.text(formatTimestamp(timestamp));
            messageElement.append(timeElement);
        }
        
        return messageElement;
    }

    // Funkcja do formatowania markdown w odpowiedzi
    function formatMarkdown(content) {
        // Obsługa bloków kodu
        content = content.replace(/```([a-z]*)\n([\s\S]*?)```/g, function(match, language, code) {
            return '<pre><code class="language-' + language + '">' + 
                escapeHTML(code) + '</code></pre>';
        });
        
        // Obsługa pojedynczych linii kodu
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Obsługa pogrubienia
        content = content.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        
        // Obsługa kursywy
        content = content.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        
        // Obsługa list
        content = content.replace(/^\s*-\s+(.+)$/gm, '<li>$1</li>');
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

    // Formatowanie znacznika czasowego dla wyświetlania
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
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
                    $('#chat-messages').empty();
                    
                    // Pokazanie powitania
                    showWelcomeMessage();
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                } else {
                    console.error('Nie udało się utworzyć sesji:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas tworzenia sesji:', error);
            }
        });
    }

    function loadSession(sessionId) {
        currentSessionId = sessionId;
        localStorage.setItem('currentSessionId', sessionId);
        
        // Wyczyszczenie czatu
        $('#chat-messages').empty();
        showLoadingIndicator();
        
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
                hideLoadingIndicator();
                
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodanie wiadomości do czatu
                    historyData.messages.forEach(msg => {
                        appendMessage(msg.type, msg.content, msg.time);
                    });
                    
                    // Jeśli są dostępne wcześniejsze strony, pokaż przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        showLoadMoreButton();
                    }
                    
                    // Aktualizacja nazwy sesji
                    updateSessionTitle(sessionId);
                    
                    // Oznaczenie aktywnej sesji na liście
                    updateActiveSession(sessionId);
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                    appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingIndicator();
                console.error('Błąd ładowania historii czatu:', error);
                appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
            }
        });
    }

    // Funkcja pokazująca komunikat powitalny
    function showWelcomeMessage() {
        const welcomeMessage = `<div class="welcome-message">
            <h2>Witaj w AI Chat Assistant</h2>
            <p>Jestem Claude, asystent AI stworzony przez Anthropic. Jak mogę Ci dzisiaj pomóc?</p>
        </div>`;
        
        $('#chat-messages').html(welcomeMessage);
    }

    // Funkcja aktualizująca tytuł sesji
    function updateSessionTitle(sessionId) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_session_title',
                nonce: aica_data.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    $('#current-session-name').text(response.data.title);
                }
            }
        });
    }

    // Funkcja oznaczająca aktywną sesję na liście
    function updateActiveSession(sessionId) {
        $('.session-item').removeClass('active');
        $('.session-item[data-session-id="' + sessionId + '"]').addClass('active');
    }

    // Funkcja ładująca więcej wiadomości (paginacja)
    function loadMoreMessages() {
        if (isLoadingMoreMessages || currentPage >= totalPages) {
            return;
        }
        
        isLoadingMoreMessages = true;
        
        // Zmiana tekstu przycisku "załaduj więcej"
        $('#load-more-messages').text('Ładowanie...');
        
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
                    const chatContainer = $('#chat-messages');
                    const scrollPos = chatContainer.scrollTop();
                    const scrollHeight = chatContainer[0].scrollHeight;
                    
                    // Dodawanie starszych wiadomości na początku
                    const messagesHtml = historyData.messages.map(msg => {
                        return createMessageElement(msg.type, msg.content, msg.time)[0].outerHTML;
                    }).join('');
                    
                    // Wstaw wiadomości na początku kontenera
                    chatContainer.prepend(messagesHtml);
                    
                    // Zachowaj pozycję przewijania po dodaniu wiadomości
                    const newScrollHeight = chatContainer[0].scrollHeight;
                    chatContainer.scrollTop(scrollPos + (newScrollHeight - scrollHeight));
                    
                    // Jeśli są dostępne wcześniejsze strony, zaktualizuj przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        $('#load-more-messages').text('Załaduj wcześniejsze wiadomości');
                    } else {
                        hideLoadMoreButton();
                    }
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                }
                
                isLoadingMoreMessages = false;
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania historii czatu:', error);
                $('#load-more-messages').text('Załaduj wcześniejsze wiadomości');
                isLoadingMoreMessages = false;
            }
        });
    }

    function saveConversation(userMessage, assistantResponse) {
        // Nie potrzebujemy już ręcznie zapisywać w localStorage
        // Zamiast tego, aktualizujemy listę sesji
        loadSessionsList();
    }

    function deleteSession(sessionId) {
        // Wywołanie AJAX do usunięcia sesji
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
                    // Jeśli usunięto aktualną sesję, utwórz nową
                    if (sessionId === currentSessionId) {
                        createNewSession();
                    }
                    
                    // Aktualizacja listy sesji
                    loadSessionsList();
                } else {
                    console.error('Nie udało się usunąć sesji:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd podczas usuwania sesji:', error);
            }
        });
    }

    function loadSessionsList() {
        // Wywołanie AJAX do pobrania listy sesji
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
        const sessionsList = $('#sessions-list');
        sessionsList.empty();
        
        sessions.forEach(session => {
            const sessionItem = $('<div class="session-item" data-session-id="' + session.session_id + '"></div>');
            sessionItem.text(session.title);
            
            // Dodanie przycisku usuwania
            const deleteButton = $('<button class="delete-session">Usuń</button>');
            sessionItem.append(deleteButton);
            
            // Podświetlenie aktualnej sesji
            if (session.session_id === currentSessionId) {
                sessionItem.addClass('active');
            }
            
            sessionsList.append(sessionItem);
        });
    }

    // Funkcje dla przycisku "załaduj więcej"
    function showLoadMoreButton() {
        // Jeśli przycisk już istnieje, tylko go pokaż
        if ($('#load-more-messages').length) {
            $('#load-more-messages').show();
        } else {
            // Utwórz przycisk i dodaj go na początku kontenera czatu
            const loadMoreButton = $('<div id="load-more-messages" class="load-more-messages"></div>');
            loadMoreButton.text('Załaduj wcześniejsze wiadomości');
            $('#chat-messages').prepend(loadMoreButton);
        }
    }

    function hideLoadMoreButton() {
        $('#load-more-messages').hide();
    }

    // Funkcje zarządzania repozytoriami
    function loadRepositories() {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_repositories',
                nonce: aica_data.nonce
            },
            success: function(response) {
                const repositoriesList = $('#repositories-list');
                repositoriesList.empty();
                
                if (response.success && response.data.repositories.length > 0) {
                    response.data.repositories.forEach(repo => {
                        const repoItem = $('<div class="repository-item" data-repo-id="' + repo.id + '"></div>');
                        repoItem.text(repo.name);
                        repositoriesList.append(repoItem);
                    });
                } else {
                    repositoriesList.html(`
                        <div class="empty-repositories">
                            <p>Nie znaleziono repozytoriów. Dodaj repozytorium w ustawieniach.</p>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania repozytoriów:', error);
            }
        });
    }

    function loadRepository(repoId) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_repository_files',
                nonce: aica_data.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    currentRepositoryId = repoId;
                    currentRepositoryPath = '';
                    displayFileTree(response.data.files, '#repository-files');
                    
                    // Ukrycie listy repozytoriów i pokazanie struktury plików
                    $('#repositories-container').hide();
                    $('#repository-files-container').show();
                } else {
                    console.error('Błąd ładowania repozytorium:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania repozytorium:', error);
            }
        });
    }

    function displayFileTree(files, containerId) {
        const container = $(containerId);
        container.empty();
        
        // Utworzenie drzewa plików
        const fileTree = buildFileTree(files);
        renderFileTree(fileTree, container);
    }

    function buildFileTree(files) {
        const root = { name: 'root', type: 'directory', children: {} };
        
        files.forEach(file => {
            const parts = file.path.split('/');
            let currentLevel = root;
            
            for (let i = 0; i < parts.length; i++) {
                const part = parts[i];
                const isFile = i === parts.length - 1;
                
                if (!currentLevel.children[part]) {
                    currentLevel.children[part] = {
                        name: part,
                        type: isFile ? 'file' : 'directory',
                        children: {},
                        path: parts.slice(0, i + 1).join('/')
                    };
                }
                
                currentLevel = currentLevel.children[part];
            }
        });
        
        return root;
    }

    function renderFileTree(node, container) {
        if (node.type === 'directory' && Object.keys(node.children).length > 0) {
            const list = $('<ul class="file-tree"></ul>');
            
            // Sortowanie: najpierw katalogi, potem pliki, alfabetycznie
            const sortedChildren = Object.values(node.children).sort((a, b) => {
                if (a.type !== b.type) {
                    return a.type === 'directory' ? -1 : 1;
                }
                return a.name.localeCompare(b.name);
            });
            
            sortedChildren.forEach(child => {
                const item = $('<li class="' + child.type + '-item"></li>');
                const label = $('<span class="file-label">' + child.name + '</span>');
                
                if (child.type === 'file') {
                    label.on('click', function() {
                        loadFile(child.path);
                    });
                } else {
                    label.on('click', function() {
                        $(this).parent().toggleClass('expanded');
                    });
                }
                
                item.append(label);
                
                if (child.type === 'directory') {
                    renderFileTree(child, item);
                }
                
                list.append(item);
            });
            
            container.append(list);
        }
    }

    function loadFile(filePath) {
        $.ajax({
            url: aica_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aica_get_file_content',
                nonce: aica_data.nonce,
                repo_id: currentRepositoryId,
                path: filePath
            },
            success: function(response) {
                if (response.success) {
                    currentFilePath = filePath;
                    
                    // Dodanie informacji o kontekście do czatu
                    appendMessage('system', 'Kontekst: plik ' + filePath);
                    
                    // Oznaczenie wybranego pliku
                    $('.file-item').removeClass('active');
                    $('.file-label:contains("' + filePath.split('/').pop() + '")').parent().addClass('active');
                } else {
                    console.error('Błąd ładowania pliku:', response.data.message);
                    appendMessage('system', 'Wystąpił błąd podczas ładowania pliku.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania pliku:', error);
                appendMessage('system', 'Wystąpił błąd podczas ładowania pliku.');
            }
        });
    }

    // Funkcje pomocnicze UI
    function showLoadingIndicator() {
        $('#loading-indicator').show();
    }

    function hideLoadingIndicator() {
        $('#loading-indicator').hide();
    }

    function toggleSessionsList() {
        $('#sessions-container').toggle();
        $('#repositories-container').hide();
        $('#repository-files-container').hide();
    }

    function toggleRepositoriesList() {
        $('#repositories-container').toggle();
        $('#sessions-container').hide();
        $('#repository-files-container').hide();
    }

    // Inicjalizacja po załadowaniu dokumentu
    $(document).ready(function() {
        init();
    });

})(jQuery);