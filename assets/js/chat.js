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
        // Wczytanie listy sesji
        loadSessionList();
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
            loadSession(sessionId, 1);  // Załaduj pierwszą stronę
        });

        $(document).on('click', '.delete-session', function(e) {
            e.stopPropagation();
            const sessionId = $(this).parent().data('session-id');
            deleteSession(sessionId);
        });

        // Obsługa przycisku "załaduj więcej"
        $(document).on('click', '#load-more-messages', function() {
            if (!isLoadingMoreMessages) {
                loadSession(currentSessionId, currentPage + 1);
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
                loadSession(currentSessionId, currentPage + 1);
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
                url: '/api/chat',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    sessionId: currentSessionId,
                    message: message,
                    context: contextData
                }),
                success: function(response) {
                    resolve(response);
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
            loadSession(currentSessionId, 1);  // Załaduj pierwszą stronę
        }
    }

    function createNewSession() {
        const sessionId = 'session_' + Date.now();
        currentSessionId = sessionId;
        
        // Zapisanie nowej sesji
        const sessionData = {
            id: sessionId,
            name: 'Nowa konwersacja',
            created: new Date().toISOString(),
            messages: []
        };
        
        localStorage.setItem('session_' + sessionId, JSON.stringify(sessionData));
        localStorage.setItem('currentSessionId', sessionId);
        
        // Wyczyszczenie czatu
        $('#chat-messages').empty();
        
        // Aktualizacja listy sesji
        loadSessionList();

        // Resetowanie paginacji
        currentPage = 1;
        totalPages = 1;
    }

    function loadSession(sessionId, page = 1) {
        currentSessionId = sessionId;
        localStorage.setItem('currentSessionId', sessionId);
        
        // Wyczyszczenie czatu tylko jeśli ładujemy pierwszą stronę
        if (page === 1) {
            $('#chat-messages').empty();
            showLoadingIndicator();
        } else {
            // Pokaż wskaźnik ładowania dla kolejnych stron
            showLoadMoreIndicator();
        }
        
        isLoadingMoreMessages = true;
        
        // Wywołanie AJAX do pobrania historii czatu
        $.ajax({
            url: aica_data.ajax_url,
            method: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_data.nonce,
                session_id: sessionId,
                page: page,
                per_page: messagesPerPage
            },
            success: function(response) {
                if (response.success) {
                    const historyData = response.data;
                    
                    // Aktualizacja zmiennych paginacji
                    currentPage = historyData.pagination.current_page;
                    totalPages = historyData.pagination.total_pages;
                    
                    // Dodanie wiadomości do czatu
                    if (page === 1) {
                        // Nowe załadowanie
                        historyData.messages.forEach(msg => {
                            appendMessage(msg.type, msg.content, msg.time);
                        });
                    } else {
                        // Dodawanie starszych wiadomości na górze
                        const chatContainer = $('#chat-messages');
                        const scrollPos = chatContainer.scrollTop();
                        const scrollHeight = chatContainer[0].scrollHeight;
                        
                        // Dodawanie starszych wiadomości na początku
                        const tempContainer = $('<div></div>');
                        historyData.messages.forEach(msg => {
                            const messageElement = createMessageElement(msg.type, msg.content, msg.time);
                            tempContainer.append(messageElement);
                        });
                        
                        // Wstaw wiadomości na początku kontenera
                        chatContainer.prepend(tempContainer.html());
                        
                        // Zachowaj pozycję przewijania po dodaniu wiadomości
                        const newScrollHeight = chatContainer[0].scrollHeight;
                        chatContainer.scrollTop(scrollPos + (newScrollHeight - scrollHeight));
                    }
                    
                    // Jeśli są dostępne wcześniejsze strony, pokaż przycisk "załaduj więcej"
                    if (currentPage < totalPages) {
                        showLoadMoreButton();
                    } else {
                        hideLoadMoreButton();
                    }
                    
                    // Aktualizacja nazwy sesji (tylko dla pierwszej strony)
                    if (page === 1) {
                        const sessionData = JSON.parse(localStorage.getItem('session_' + sessionId));
                        if (sessionData) {
                            $('#current-session-name').text(sessionData.name);
                        }
                    }
                } else {
                    console.error('Błąd pobierania historii:', response.data.message);
                    appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
                }
                
                hideLoadingIndicator();
                hideLoadMoreIndicator();
                isLoadingMoreMessages = false;
            },
            error: function(xhr, status, error) {
                console.error('Błąd ładowania historii czatu:', error);
                hideLoadingIndicator();
                hideLoadMoreIndicator();
                appendMessage('system', 'Wystąpił błąd podczas ładowania historii czatu.');
                isLoadingMoreMessages = false;
            }
        });
    }

    function saveConversation(userMessage, assistantResponse) {
        const sessionData = JSON.parse(localStorage.getItem('session_' + currentSessionId));
        
        if (sessionData) {
            sessionData.messages.push({
                sender: 'user',
                content: userMessage,
                timestamp: new Date().toISOString()
            });
            
            sessionData.messages.push({
                () {
               sender: 'assistant',
               content: assistantResponse,
               timestamp: new Date().toISOString()
           });
           
           // Aktualizacja nazwy sesji, jeśli jest to pierwsza wiadomość
           if (sessionData.messages.length === 2) {
               const sessionName = userMessage.substring(0, 30) + (userMessage.length > 30 ? '...' : '');
               sessionData.name = sessionName;
               $('#current-session-name').text(sessionName);
           }
           
           localStorage.setItem('session_' + currentSessionId, JSON.stringify(sessionData));
           
           // Aktualizacja listy sesji
           loadSessionList();
       }
   }

   function deleteSession(sessionId) {
       localStorage.removeItem('session_' + sessionId);
       
       // Jeśli usunięto aktualną sesję, utwórz nową
       if (sessionId === currentSessionId) {
           createNewSession();
       }
       
       // Aktualizacja listy sesji
       loadSessionList();
   }

   function loadSessionList() {
       const sessionList = $('#sessions-list');
       sessionList.empty();
       
       // Zbieranie wszystkich sesji z localStorage
       const sessions = [];
       for (let i = 0; i < localStorage.length; i++) {
           const key = localStorage.key(i);
           if (key.startsWith('session_')) {
               const sessionData = JSON.parse(localStorage.getItem(key));
               sessions.push(sessionData);
           }
       }
       
       // Sortowanie sesji według daty utworzenia (od najnowszej)
       sessions.sort((a, b) => new Date(b.created) - new Date(a.created));
       
       // Dodanie sesji do listy
       sessions.forEach(session => {
           const sessionItem = $('<div class="session-item" data-session-id="' + session.id + '"></div>');
           sessionItem.text(session.name);
           
           // Dodanie przycisku usuwania
           const deleteButton = $('<button class="delete-session">Usuń</button>');
           sessionItem.append(deleteButton);
           
           // Podświetlenie aktualnej sesji
           if (session.id === currentSessionId) {
               sessionItem.addClass('active');
           }
           
           sessionList.append(sessionItem);
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
           loadMoreButton.on('click', function() {
               if (!isLoadingMoreMessages) {
                   loadSession(currentSessionId, currentPage + 1);
               }
           });
           $('#chat-messages').prepend(loadMoreButton);
       }
   }

   function hideLoadMoreButton() {
       $('#load-more-messages').hide();
   }

   function showLoadMoreIndicator() {
       if ($('#load-more-indicator').length) {
           $('#load-more-indicator').show();
       } else {
           const indicator = $('<div id="load-more-indicator" class="load-more-indicator"><span class="spinner is-active"></span>Ładowanie wcześniejszych wiadomości...</div>');
           $('#chat-messages').prepend(indicator);
       }
   }

   function hideLoadMoreIndicator() {
       $('#load-more-indicator').hide();
   }

   // Funkcje zarządzania repozytoriami
   function loadRepositories() {
       $.ajax({
           url: '/api/repositories',
           method: 'GET',
           success: function(response) {
               const repositoriesList = $('#repositories-list');
               repositoriesList.empty();
               
               response.repositories.forEach(repo => {
                   const repoItem = $('<div class="repository-item" data-repo-id="' + repo.id + '"></div>');
                   repoItem.text(repo.name);
                   repositoriesList.append(repoItem);
               });
           },
           error: function(xhr, status, error) {
               console.error('Błąd ładowania repozytoriów:', error);
           }
       });
   }

   function loadRepository(repoId) {
       $.ajax({
           url: '/api/repositories/' + repoId,
           method: 'GET',
           success: function(response) {
               currentRepositoryId = repoId;
               currentRepositoryPath = '';
               displayFileTree(response.files, '#repository-files');
               
               // Ukrycie listy repozytoriów i pokazanie struktury plików
               $('#repositories-container').hide();
               $('#repository-files-container').show();
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
           url: '/api/repositories/' + currentRepositoryId + '/file',
           method: 'GET',
           data: { path: filePath },
           success: function(response) {
               currentFilePath = filePath;
               
               // Dodanie informacji o kontekście do czatu
               appendMessage('system', 'Kontekst: plik ' + filePath);
               
               // Oznaczenie wybranego pliku
               $('.file-item').removeClass('active');
               $('.file-label:contains("' + filePath.split('/').pop() + '")').parent().addClass('active');
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