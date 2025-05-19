/**
 * JavaScript do obsługi strony repozytoriów
 */
jQuery(document).ready(function($) {
    /**
     * OBSŁUGA INTERFEJSU UŻYTKOWNIKA
     */
    
    // Obsługa pokazywania/ukrywania menu rozwijanego
    $(document).on('click', '.aica-dropdown-toggle', function(e) {
        e.stopPropagation();
        const dropdown = $(this).siblings('.aica-dropdown-menu');
        $('.aica-dropdown-menu').not(dropdown).hide();
        dropdown.toggle();
    });
    
    // Zamykanie menu rozwijanego po kliknięciu poza nim
    $(document).on('click', function() {
        $('.aica-dropdown-menu').hide();
    });
    
    // Przełączanie zakładek dla różnych źródeł repozytoriów
    $('.aica-source-item').on('click', function() {
        const source = $(this).data('source');
        
        // Aktywacja przycisku źródła
        $('.aica-source-item').removeClass('active');
        $(this).addClass('active');
        
        // Aktywacja odpowiedniej zakładki
        $('.aica-repos-tab').removeClass('active');
        $('#' + source + '-repositories').addClass('active');
        
        // Pokaż/ukryj filtry języka dla zakładki "saved"
        if (source === 'saved') {
            $('#aica-language-filter').show();
        } else {
            $('#aica-language-filter').hide();
        }
    });
    
    // Wyszukiwanie repozytoriów
    $('#aica-search-repositories').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        // Wyszukiwanie w aktywnej zakładce
        const activeTab = $('.aica-repos-tab.active');
        
        // Jeśli nie ma wyszukiwanego tekstu, pokaż wszystkie repozytoria
        if (searchTerm === '') {
            activeTab.find('.aica-repository-card').show();
            return;
        }
        
        // Przeszukaj karty repozytoriów
        activeTab.find('.aica-repository-card').each(function() {
            const repoName = $(this).find('.aica-repo-title h3').text().toLowerCase();
            const repoOwner = $(this).find('.aica-repo-owner').text().toLowerCase();
            const repoDescription = $(this).find('.aica-repo-description p').text().toLowerCase();
            
            // Sprawdź, czy tekst pasuje do nazwy, właściciela lub opisu
            if (repoName.includes(searchTerm) || repoOwner.includes(searchTerm) || repoDescription.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
   // Sortowanie repozytoriów
    $('.aica-sort-select').on('change', function() {
        const sortValue = $(this).val();
        const activeTab = $('.aica-repos-tab.active');
        const repoGrid = activeTab.find('.aica-repositories-grid');
        const repoCards = activeTab.find('.aica-repository-card').toArray();
        
        // Sortowanie kart repozytoriów
        repoCards.sort(function(a, b) {
            const aName = $(a).find('.aica-repo-title h3').text().toLowerCase();
            const bName = $(b).find('.aica-repo-title h3').text().toLowerCase();
            
            // Pobieranie daty dla różnych typów zakładek
            let aDate, bDate;
            
            if (activeTab.attr('id') === 'saved-repositories') {
                aDate = $(a).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
                bDate = $(b).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
            } else {
                aDate = $(a).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
                bDate = $(b).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
            }
            
            // Konwersja dat na format porównywalny
            const aDateObj = aDate ? new Date(aDate.split('.').reverse().join('-')) : new Date(0);
            const bDateObj = bDate ? new Date(bDate.split('.').reverse().join('-')) : new Date(0);
            
            switch (sortValue) {
                case 'name_asc':
                    return aName.localeCompare(bName);
                case 'name_desc':
                    return bName.localeCompare(aName);
                case 'date_desc':
                    return bDateObj > aDateObj ? -1 : bDateObj < aDateObj ? 1 : 0;
                case 'date_asc':
                    return aDateObj > bDateObj ? -1 : aDateObj < bDateObj ? 1 : 0;
                default:
                    return 0;
            }
        });
        
        // Wyczyść siatkę repozytoriów
        repoGrid.empty();
        
        // Dodaj posortowane karty z powrotem do kontenera
        $.each(repoCards, function(index, card) {
            repoGrid.append(card);
        });
    });
    
    // Filtrowanie po języku
    $('.aica-language-checkbox').on('change', function() {
        filterRepositoriesByLanguage();
    });
    
    function filterRepositoriesByLanguage() {
        const activeTab = $('.aica-repos-tab.active');
        const selectedLanguages = [];
        
        // Zbierz zaznaczone języki
        $('.aica-language-checkbox:checked').each(function() {
            selectedLanguages.push($(this).val());
        });
        
        // Jeśli nie ma zaznaczonych języków, pokaż wszystkie repozytoria
        if (selectedLanguages.length === 0) {
            activeTab.find('.aica-repository-card').show();
            return;
        }
        
        // Przeszukaj karty repozytoriów
        activeTab.find('.aica-repository-card').each(function() {
            const repoLanguages = $(this).data('languages') || '';
            const repoLanguagesArray = repoLanguages.split(',');
            
            // Sprawdź, czy repozytorium ma któryś z zaznaczonych języków
            let shouldShow = false;
            for (let i = 0; i < selectedLanguages.length; i++) {
                if (repoLanguagesArray.includes(selectedLanguages[i])) {
                    shouldShow = true;
                    break;
                }
            }
            
            if (shouldShow) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Otwieranie przeglądarki plików
    $(document).on('click', '.aica-browse-repo, .aica-browse-button', function(e) {
        e.preventDefault();
        const repoId = $(this).data('repo-id');
        
        // Pokaż modal przeglądarki plików
        $('#aica-file-browser-modal').show();
        
        // Załaduj repozytorium
        loadRepository(repoId);
    });
    
    // Zamykanie modalnego okna przeglądarki plików
    $('.aica-modal-close').on('click', function() {
        $('#aica-file-browser-modal').hide();
    });
    
    // Zamykanie modalnego okna po kliknięciu poza nim
    $('#aica-file-browser-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    /**
     * OBSŁUGA FORMULARZY I AJAX
     */
    
    // Obsługa formularzy dodawania repozytoriów - użyj AJAX zamiast zwykłego formularza
    $(document).on('submit', '.aica-add-repo-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[name="aica_add_repository"]');
        
        // Dezaktywuj przycisk, aby uniknąć wielokrotnego kliknięcia
        submitButton.prop('disabled', true);
        submitButton.html('<span class="dashicons dashicons-update" style="animation: aica-spin 1s linear infinite;"></span> ' + aica_repos.i18n.adding);
        
        // Przygotuj dane formularza
        const formData = new FormData();
        formData.append('action', 'aica_add_repository');
        formData.append('nonce', aica_repos.nonce);
        formData.append('repo_type', form.find('input[name="repo_type"]').val());
        formData.append('repo_name', form.find('input[name="repo_name"]').val());
        formData.append('repo_owner', form.find('input[name="repo_owner"]').val());
        formData.append('repo_url', form.find('input[name="repo_url"]').val());
        formData.append('repo_external_id', form.find('input[name="repo_external_id"]').val());
        formData.append('repo_description', form.find('input[name="repo_description"]').val());
        
        // Wyślij żądanie AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                if (response.success) {
                    // Pokaż komunikat o sukcesie
                    alert(response.data.message || aica_repos.i18n.add_success);
                    
                    // Odśwież stronę, aby pokazać nowe repozytorium
                    window.location.href = window.location.href.split('?')[0] + '?page=ai-chat-assistant-repositories&added=true';
                } else {
                    // Pokaż komunikat o błędzie
                    alert(response.data.message || aica_repos.i18n.add_error);
                }
            },
            error: function() {
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                // Pokaż komunikat o błędzie
                alert(aica_repos.i18n.add_error);
            }
        });
    });
    
    // Obsługa usuwania repozytorium
    $(document).on('click', '.aica-delete-repo, .aica-delete-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const repoId = $(this).data('repo-id');
        const card = $(this).closest('.aica-repository-card');
        
        // Pokaż dialog potwierdzający
        $('#aica-delete-dialog').data('repo-id', repoId).data('card', card).show();
    });
    
    // Zamknięcie dialogu potwierdzającego
    $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
        $('#aica-delete-dialog').hide();
    });
    
    // Potwierdzenie usunięcia repozytorium
    $('.aica-delete-confirm').on('click', function() {
        const dialog = $('#aica-delete-dialog');
        const repoId = dialog.data('repo-id');
        const card = dialog.data('card');
        
        // Ukryj dialog
        dialog.hide();
        
        // Wykonaj AJAX do usunięcia repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_repository',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj kartę za pomocą animacji i usuń ją po zakończeniu
                    card.slideUp(300, function() {
                        card.remove();
                        
                        // Sprawdź, czy to było ostatnie repozytorium
                        if ($('#saved-repositories .aica-repository-card').length === 0) {
                            // Pokaż komunikat o braku repozytoriów
                            $('#saved-repositories').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-code-standards"></span>
                                    </div>
                                    <h2>${aica_repos.i18n.no_repositories}</h2>
                                    <p>${aica_repos.i18n.no_repositories_desc}</p>
                                    <button type="button" class="button button-primary aica-add-repository-button">
                                        <span class="dashicons dashicons-plus"></span>
                                        ${aica_repos.i18n.add_repository}
                                    </button>
                                </div>
                            `);
                        }
                        
                        // Aktualizuj licznik
                        const count = $('#saved-repositories .aica-repository-card').length;
                        $('.aica-source-item[data-source="saved"] .aica-source-count').text(count);
                    });
                } else {
                    // Pokaż komunikat o błędzie
                    alert(response.data.message || aica_repos.i18n.delete_error);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                alert(aica_repos.i18n.delete_error);
            }
        });
    });
    
    // Odświeżanie metadanych repozytorium
    $(document).on('click', '.aica-refresh-repo', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const repoId = $(this).data('repo-id');
        const card = $(this).closest('.aica-repository-card');
        // Dodaj klasę ładowania
        card.addClass('aica-card-refreshing');
        
        // Wykonaj AJAX do odświeżenia repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_refresh_repository',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                // Usuń klasę ładowania
                card.removeClass('aica-card-refreshing');
                
                if (response.success) {
                    // Pokaż komunikat o sukcesie
                    alert(aica_repos.i18n.refresh_success);
                    
                    // Odśwież stronę
                    location.reload();
                } else {
                    // Pokaż komunikat o błędzie
                    alert(response.data.message || aica_repos.i18n.refresh_error);
                }
            },
            error: function() {
                // Usuń klasę ładowania
                card.removeClass('aica-card-refreshing');
                
                // Pokaż komunikat o błędzie
                alert(aica_repos.i18n.refresh_error);
            }
        });
    });
    
    // Dodawanie nowego repozytorium - przycisk "Dodaj repozytorium"
    $('.aica-add-repository-button').on('click', function() {
        // Przełącz na zakładkę GitHub, GitLab lub Bitbucket
        if ($('.aica-source-item[data-source="github"]').length > 0) {
            $('.aica-source-item[data-source="github"]').trigger('click');
        } else if ($('.aica-source-item[data-source="gitlab"]').length > 0) {
            $('.aica-source-item[data-source="gitlab"]').trigger('click');
        } else if ($('.aica-source-item[data-source="bitbucket"]').length > 0) {
            $('.aica-source-item[data-source="bitbucket"]').trigger('click');
        } else {
            // Jeśli nie ma żadnego źródła, przekieruj do ustawień
            alert(aica_repos.i18n.no_sources_configured);
            window.location.href = aica_repos.settings_url;
        }
    });
    
    /**
     * OBSŁUGA PRZEGLĄDARKI PLIKÓW
     */
    
    // Funkcja ładująca repozytorium w przeglądarce plików
    function loadRepository(repoId) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        $('#aica-file-tree').empty();
        
        // Pobierz dane repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_details',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    const repo = response.data.repository;
                    
                    // Ustaw informacje o repozytorium
                    $('.aica-repo-name').text(repo.repo_name);
                    
                    // Ustaw ikonę repozytorium
                    let iconClass = 'dashicons-code-standards';
                    switch (repo.repo_type) {
                        case 'github':
                            iconClass = 'dashicons-code-standards';
                            break;
                        case 'gitlab':
                            iconClass = 'dashicons-editor-code';
                            break;
                        case 'bitbucket':
                            iconClass = 'dashicons-cloud';
                            break;
                    }
                    $('.aica-repo-icon .dashicons').attr('class', 'dashicons ' + iconClass);
                    
                    // Załaduj listę gałęzi
                    const branchSelect = $('#aica-branch-select');
                    branchSelect.empty();
                    
                    if (response.data.branches && response.data.branches.length > 0) {
                        $.each(response.data.branches, function(index, branch) {
                            branchSelect.append($('<option>', {
                                value: branch,
                                text: branch
                            }));
                        });
                    } else {
                        // Dodaj domyślne gałęzie
                        branchSelect.append($('<option>', {
                            value: 'main',
                            text: 'main'
                        }));
                        branchSelect.append($('<option>', {
                            value: 'master',
                            text: 'master'
                        }));
                    }
                    
                    // Zapisz ID repozytorium w drzewie plików
                    $('#aica-file-tree').data('repo-id', repoId);
                    
                    // Załaduj strukturę plików
                    loadFileStructure(repoId);
                } else {
                    alert(response.data.message || aica_repos.i18n.load_error);
                    $('#aica-file-browser-modal').hide();
                }
            },
            error: function() {
                alert(aica_repos.i18n.load_error);
                $('#aica-file-browser-modal').hide();
            }
        });
    }
    
    // Funkcja ładująca strukturę plików
    function loadFileStructure(repoId, path = '', branch = 'main') {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        
        // Pobierz strukturę plików
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_files',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path,
                branch: branch
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj wskaźnik ładowania
                    $('.aica-loading-files').hide();
                    
                    // Budowanie drzewa plików
                    if (path === '') {
                        // Czyszczenie drzewa, jeśli ładujemy korzeń
                        $('#aica-file-tree').empty();
                        buildFileTree(response.data.files, $('#aica-file-tree'), repoId, branch);
                    } else {
                        // Dodawanie podfolderów do istniejącego drzewa
                        const folderItem = $('#aica-file-tree').find('[data-path="' + path + '"]').parent();
                        const childrenContainer = folderItem.children('.aica-file-tree-children');
                        
                        // Czyszczenie kontenera, jeśli już istnieje
                        childrenContainer.empty();
                        
                        // Budowanie poddrzewa
                        buildFileTree(response.data.files, childrenContainer, repoId, branch);
                        
                        // Rozwinięcie folderu
                        folderItem.children('.aica-file-tree-folder').addClass('expanded');
                        childrenContainer.show();
                    }
                } else {
                    alert(response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function() {
                $('.aica-loading-files').hide();
                alert(aica_repos.i18n.load_error);
            }
        });
    }
    
    // Funkcja budująca drzewo plików
    function buildFileTree(files, container, repoId, branch) {
        // Sortowanie: najpierw foldery, potem pliki, alfabetycznie
        files.sort(function(a, b) {
            if (a.type !== b.type) {
                return a.type === 'dir' || a.type === 'tree' ? -1 : 1;
            }
            return a.name.localeCompare(b.name);
        });
        
        // Dodawanie elementów do drzewa
        $.each(files, function(index, file) {
            if (file.type === 'dir' || file.type === 'tree' || file.type === 'commit_directory') {
                // Element folderu
                const folderItem = $('<div class="aica-file-tree-item"></div>');
                const folderHeader = $('<div class="aica-file-tree-folder" data-path="' + file.path + '"></div>');
                folderHeader.append('<span class="dashicons dashicons-category"></span>');
                folderHeader.append('<span class="aica-file-name">' + file.name + '</span>');
                
                // Dodanie kontenera dla dzieci
                const childrenContainer = $('<div class="aica-file-tree-children"></div>');
                
                // Dodanie elementów do kontenera
                folderItem.append(folderHeader);
                folderItem.append(childrenContainer);
                container.append(folderItem);
                
                // Obsługa kliknięcia folderu
                folderHeader.on('click', function() {
                    const path = $(this).data('path');
                    
                    // Jeśli folder ma już załadowane dzieci, po prostu rozwiń/zwiń
                    if (childrenContainer.children().length > 0) {
                        $(this).toggleClass('expanded');
                        childrenContainer.slideToggle(200);
                    } else {
                        // Załaduj zawartość folderu
                        loadFileStructure(repoId, path, branch);
                    }
                });
            } else {
                // Element pliku
                const fileItem = $('<div class="aica-file-tree-item"></div>');
                const fileLink = $('<div class="aica-file-tree-file" data-path="' + file.path + '"></div>');
                
                // Wybierz ikonę na podstawie rozszerzenia pliku
                let fileIcon = 'dashicons-media-default';
                const fileExt = file.name.split('.').pop().toLowerCase();
                
                switch (fileExt) {
                    case 'php':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'js':
                    case 'jsx':
                    case 'ts':
                    case 'tsx':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'css':
                    case 'scss':
                    case 'less':
                        fileIcon = 'dashicons-admin-customizer';
                        break;
                    case 'html':
                    case 'htm':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'md':
                    case 'txt':
                        fileIcon = 'dashicons-media-text';
                        break;
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif':
                    case 'svg':
                        fileIcon = 'dashicons-format-image';
                        break;
                    case 'json':
                    case 'xml':
                    case 'yml':
                    case 'yaml':
                        fileIcon = 'dashicons-media-code';
                        break;
                }
                
                fileLink.append('<span class="dashicons ' + fileIcon + '"></span>');
                fileLink.append('<span class="aica-file-name">' + file.name + '</span>');
                
                fileItem.append(fileLink);
                container.append(fileItem);
                
                // Obsługa kliknięcia pliku
                fileLink.on('click', function() {
                    const path = $(this).data('path');
                    loadFileContent(repoId, path, branch);
                    
                    // Zaznaczenie aktywnego pliku
                    $('.aica-file-tree-file').removeClass('active');
                    $(this).addClass('active');
                });
            }
        });
    }
    
    // Funkcja ładująca zawartość pliku
    function loadFileContent(repoId, path, branch = 'main') {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-content').show();
        
        // Ustaw ścieżkę pliku
        $('.aica-file-path').text(path);
        
        // Wyczyść zawartość
        $('#aica-file-content code').empty();
        
        // Pobierz zawartość pliku
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_file_content',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path,
                branch: branch
            },
            success: function(response) {
                // Ukryj wskaźnik ładowania
                $('.aica-loading-content').hide();
                
                if (response.success) {
                    // Wyświetl zawartość pliku
                    const content = response.data.content;
                    const language = response.data.language || '';
                    
                    // Aktualizacja zawartości
                    $('#aica-file-content code').text(content);
                    
                    // Dodaj klasę języka do elementu code, jeśli istnieje
                    if (language) {
                        $('#aica-file-content code').attr('class', 'language-' + language);
                    }
                    
                    // Podświetlanie składni, jeśli dostępne
                    if (typeof Prism !== 'undefined') {
                        Prism.highlightElement($('#aica-file-content code')[0]);
                    }
                    
                    // Zapisz aktualną ścieżkę i zawartość dla późniejszego użycia
                    $('#aica-file-content').data('path', path);
                    $('#aica-file-content').data('content', content);
                } else {
                    // Wyświetl komunikat o błędzie
                    $('#aica-file-content code').text(response.data.message || aica_repos.i18n.load_file_error);
                }
            },
            error: function() {
                // Ukryj wskaźnik ładowania
                $('.aica-loading-content').hide();
                
                // Wyświetl komunikat o błędzie
                $('#aica-file-content code').text(aica_repos.i18n.load_file_error);
            }
        });
    }
    
    // Kopiowanie zawartości pliku
    $('.aica-copy-file-button').on('click', function() {
        const content = $('#aica-file-content').data('content');
        
        if (content) {
            // Kopiowanie do schowka
            const tempTextarea = $('<textarea>').val(content).appendTo('body').select();
            document.execCommand('copy');
            tempTextarea.remove();
            
            // Informacja o skopiowaniu
            alert(aica_repos.i18n.copy_success);
        }
    });
    
    // Używanie pliku w czacie
    $('.aica-use-in-chat-button').on('click', function() {
        const path = $('#aica-file-content').data('path');
        const content = $('#aica-file-content').data('content');
        
        if (path && content) {
            // Przekierowanie do czatu z plikiem
            window.location.href = aica_repos.chat_url + '&file_path=' + encodeURIComponent(path) + '&file_content=' + encodeURIComponent(content);
        }
    });
    
    // Wyszukiwanie plików
    $('#aica-file-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm.length < 2) {
            // Zresetuj wszystkie podświetlenia jeśli wyszukiwanie jest puste
            $('.aica-file-tree-file').removeClass('aica-file-match');
            return;
        }
        
        // Podświetlanie pasujących plików
        $('.aica-file-tree-file').each(function() {
            const fileName = $(this).find('.aica-file-name').text().toLowerCase();
            
            if (fileName.includes(searchTerm)) {
                $(this).addClass('aica-file-match');
                
                // Rozwiń rodzica
                $(this).parents('.aica-file-tree-children').each(function() {
                    $(this).show();
                    $(this).prev('.aica-file-tree-folder').addClass('expanded');
                });
            } else {
                $(this).removeClass('aica-file-match');
            }
        });
    });
    
    // Zmiana gałęzi
    $('#aica-branch-select').on('change', function() {
        const branch = $(this).val();
        const repoId = $('#aica-file-tree').data('repo-id');
        
        if (repoId) {
            // Załaduj strukturę plików dla wybranej gałęzi
            loadFileStructure(repoId, '', branch);
        }
    });
    
    // Inicjalizacja strony
    function initPage() {
        // Sprawdź, czy w URL są parametry informujące o sukcesie
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('added') === 'true') {
            // Zwróć uwagę na zakładkę "Zapisane repozytoria"
            $('.aica-source-item[data-source="saved"]').trigger('click');
        }
        
        // Dodaj klasę wskazującą, że strona jest zainicjalizowana
        $('body').addClass('aica-initialized');
    }
    
    // Uruchom inicjalizację po załadowaniu strony
    initPage();
});