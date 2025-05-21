/**
 * JavaScript do obsługi strony repozytoriów
 */
jQuery(document).ready(function($) {
    /**
     * INICJALIZACJA I FUNKCJE POMOCNICZE
     */
    
    // Funkcja inicjalizująca stronę
    function inicjalizacja() {
        try {
            // Sprawdź czy tabela repozytoriów istnieje
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_test_db'
                },
                success: function(response) {
                    if (response.success && !response.data.table_exists) {
                        // Utwórz tabelę, jeśli nie istnieje
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aica_activate_plugin'
                            },
                            success: function() {
                                console.log('Tabela repozytoriów została utworzona');
                            }
                        });
                    }
                }
            });
            
            // Aktywacja domyślnej zakładki po załadowaniu strony
            if ($('.aica-source-item').length > 0) {
                $('.aica-source-item').first().addClass('active');
                const defaultSource = $('.aica-source-item').first().data('source');
                $('#' + defaultSource + '-repositories').addClass('active');
            }
            
            // Sprawdzenie parametrów URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('added') === 'true') {
                $('.aica-source-item[data-source="saved"]').trigger('click');
                pokazPowiadomienie('success', aica_repos.i18n.add_success);
            } else if (urlParams.get('deleted') === 'true') {
                $('.aica-source-item[data-source="saved"]').trigger('click');
                pokazPowiadomienie('success', aica_repos.i18n.delete_success);
            }
            
            // Dodaj klasę wskazującą, że strona jest zainicjalizowana
            $('body').addClass('aica-initialized');
            console.log('Menedżer repozytoriów zainicjalizowany pomyślnie');
        } catch (error) {
            console.error('Błąd podczas inicjalizacji:', error);
        }
    }
    
    // Funkcja pokazująca powiadomienie
    function pokazPowiadomienie(typ, wiadomosc) {
        const klasaPowiadomienia = typ === 'success' ? 'aica-notice-success' : 'aica-notice-error';
        const klasaIkony = typ === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
        
        const powiadomienie = $(`
            <div class="aica-notice ${klasaPowiadomienia}">
                <span class="dashicons ${klasaIkony}"></span>
                <p>${wiadomosc}</p>
            </div>
        `);
        
        $('.aica-repositories-container').before(powiadomienie);
        
        // Automatyczne ukrycie po 5 sekundach
        setTimeout(function() {
            powiadomienie.fadeOut(300, function() {
                powiadomienie.remove();
            });
        }, 5000);
    }
    
    /**
     * OBSŁUGA INTERFEJSU UŻYTKOWNIKA
     */
    
    // Obsługa pokazywania/ukrywania menu rozwijanego
    $(document).on('click', '.aica-dropdown-toggle', function(e) {
        e.preventDefault();
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
    
    // Wyszukiwanie repozytoriów z opóźnieniem (debouncing)
    let czasWyszukiwania;
    $('#aica-search-repositories').on('keyup', function() {
        clearTimeout(czasWyszukiwania);
        
        const poleWyszukiwania = $(this);
        
        czasWyszukiwania = setTimeout(function() {
            const fraza = poleWyszukiwania.val().toLowerCase();
            const aktywnaZakladka = $('.aica-repos-tab.active');
            
            if (fraza === '') {
                aktywnaZakladka.find('.aica-repository-card').show();
                aktywnaZakladka.find('.aica-no-results').remove();
                return;
            }
            
            // Dodaj stan ładowania
            poleWyszukiwania.addClass('aica-searching');
            
            // Przeszukaj karty repozytoriów
            let znaleziono = false;
            aktywnaZakladka.find('.aica-repository-card').each(function() {
                const nazwaRepo = $(this).find('.aica-repo-title h3').text().toLowerCase();
                const wlascicielRepo = $(this).find('.aica-repo-owner').text().toLowerCase();
                const opisRepo = $(this).find('.aica-repo-description p').text().toLowerCase();
                
                if (nazwaRepo.includes(fraza) || wlascicielRepo.includes(fraza) || opisRepo.includes(fraza)) {
                    $(this).show();
                    znaleziono = true;
                } else {
                    $(this).hide();
                }
            });
            
            // Usuń stan ładowania
            poleWyszukiwania.removeClass('aica-searching');
            
            // Pokaż komunikat jeśli nie znaleziono wyników
            if (!znaleziono) {
                if (aktywnaZakladka.find('.aica-no-results').length === 0) {
                    aktywnaZakladka.append(`
                        <div class="aica-no-results">
                            <p>${aica_repos.i18n.no_search_results}</p>
                        </div>
                    `);
                }
            } else {
                aktywnaZakladka.find('.aica-no-results').remove();
            }
        }, 300); // 300ms opóźnienia
    });
    
    // Sortowanie repozytoriów
    $('.aica-sort-select').on('change', function() {
        const opcjaSortowania = $(this).val();
        const aktywnaZakladka = $('.aica-repos-tab.active');
        const siatkaRepo = aktywnaZakladka.find('.aica-repositories-grid');
        const kartyRepo = aktywnaZakladka.find('.aica-repository-card').toArray();
        
        // Sortowanie kart repozytoriów
        kartyRepo.sort(function(a, b) {
            const nazwaA = $(a).find('.aica-repo-title h3').text().toLowerCase();
            const nazwaB = $(b).find('.aica-repo-title h3').text().toLowerCase();
            
            // Pobieranie daty dla różnych typów zakładek
            let dataA, dataB;
            
            if (aktywnaZakladka.attr('id') === 'saved-repositories') {
                dataA = $(a).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
                dataB = $(b).find('.aica-meta-item:contains("Dodano:")').find('.aica-meta-value').text();
            } else {
                dataA = $(a).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
                dataB = $(b).find('.aica-meta-item:contains("Aktualizacja:")').find('.aica-meta-value').text();
            }
            
            // Konwersja dat na format porównywalny
            const dataAObj = dataA ? new Date(dataA.split('.').reverse().join('-')) : new Date(0);
            const dataBObj = dataB ? new Date(dataB.split('.').reverse().join('-')) : new Date(0);
            
            switch (opcjaSortowania) {
                case 'name_asc':
                    return nazwaA.localeCompare(nazwaB);
                case 'name_desc':
                    return nazwaB.localeCompare(nazwaA);
                case 'date_desc':
                    return dataBObj > dataAObj ? 1 : dataBObj < dataAObj ? -1 : 0;
                case 'date_asc':
                    return dataAObj > dataBObj ? 1 : dataAObj < dataBObj ? -1 : 0;
                default:
                    return 0;
            }
        });
        
        // Wyczyść siatkę repozytoriów
        siatkaRepo.empty();
        
        // Dodaj posortowane karty z powrotem do kontenera
        $.each(kartyRepo, function(index, card) {
            siatkaRepo.append(card);
        });
    });
    
    // Filtrowanie po języku
    $('.aica-language-checkbox').on('change', function() {
        filtrujRepozytorium();
    });
    
    function filtrujRepozytorium() {
        const aktywnaZakladka = $('.aica-repos-tab.active');
        const wybraneJezyki = [];
        
        // Zbierz zaznaczone języki
        $('.aica-language-checkbox:checked').each(function() {
            wybraneJezyki.push($(this).val());
        });
        
        // Jeśli nie ma zaznaczonych języków, pokaż wszystkie repozytoria
        if (wybraneJezyki.length === 0) {
            aktywnaZakladka.find('.aica-repository-card').show();
            return;
        }
        
        // Przeszukaj karty repozytoriów
        aktywnaZakladka.find('.aica-repository-card').each(function() {
            const jezykiRepo = $(this).data('languages') || '';
            const jezykiRepoTablica = jezykiRepo.split(',');
            
            // Sprawdź, czy repozytorium ma któryś z zaznaczonych języków
            let czyPokazac = false;
            for (let i = 0; i < wybraneJezyki.length; i++) {
                if (jezykiRepoTablica.includes(wybraneJezyki[i])) {
                    czyPokazac = true;
                    break;
                }
            }
            
            if (czyPokazac) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    /**
     * OBSŁUGA PRZEGLĄDARKI PLIKÓW
     */
    
    // Otwieranie przeglądarki plików
    $(document).on('click', '.aica-browse-repo, .aica-browse-button', function(e) {
        e.preventDefault();
        const repoId = $(this).data('repo-id');
        
        // Pokaż modal przeglądarki plików
        $('#aica-file-browser-modal').show();
        
        // Załaduj repozytorium
        zaladujRepozytorium(repoId);
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
    
    // Funkcja ładująca repozytorium w przeglądarce plików
    function zaladujRepozytorium(repoId) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        $('#aica-file-tree').empty();
        $('#aica-file-content code').empty();
        $('.aica-file-path').text('');
        
        // Zapisz ID repozytorium w drzewie plików
        $('#aica-file-tree').data('repo-id', repoId);
        
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
                    if (repo.repo_type === 'gitlab') {
                        iconClass = 'dashicons-editor-code';
                    } else if (repo.repo_type === 'bitbucket') {
                        iconClass = 'dashicons-cloud';
                    }
                    $('.aica-repo-icon .dashicons').attr('class', 'dashicons ' + iconClass);
                    
                    // Załaduj listę gałęzi
                    const listaGalezi = $('#aica-branch-select');
                    listaGalezi.empty();
                    
                    if (response.data.branches && response.data.branches.length > 0) {
                        $.each(response.data.branches, function(index, branch) {
                            listaGalezi.append($('<option>', {
                                value: branch,
                                text: branch
                            }));
                        });
                    } else {
                        // Dodaj domyślne gałęzie
                        const domyslneGalezie = ['main', 'master', 'develop'];
                        $.each(domyslneGalezie, function(index, branch) {
                            listaGalezi.append($('<option>', {
                                value: branch,
                                text: branch
                            }));
                        });
                    }
                    
                    // Załaduj strukturę plików
                    zaladujStrukturePlikow(repoId, '', listaGalezi.val());
                } else {
                    $('.aica-loading-files').hide();
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function(xhr, status, error) {
                $('.aica-loading-files').hide();
                pokazPowiadomienie('error', aica_repos.i18n.load_error);
                console.error('Błąd AJAX:', status, error);
            }
        });
    }
    
    // Funkcja ładująca strukturę plików
    function zaladujStrukturePlikow(repoId, path = '', branch = 'main', container = null) {
        // Pokaż wskaźnik ładowania
        if (!container) {
            $('.aica-loading-files').show();
        } else {
            container.parent().find('.aica-file-tree-folder').addClass('aica-loading');
        }
        
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
                    if (container) {
                        container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                    }
                    
                    // Budowanie drzewa plików
                    if (!container) {
                        // Czyszczenie drzewa, jeśli ładujemy korzeń
                        $('#aica-file-tree').empty();
                        zbudujDrzewoPlikow(response.data.files, $('#aica-file-tree'), repoId, branch);
                    } else {
                        // Budowanie poddrzewa
                        zbudujDrzewoPlikow(response.data.files, container, repoId, branch);
                        
                        // Rozwinięcie folderu
                        container.parent().find('.aica-file-tree-folder').addClass('expanded');
                        container.show();
                    }
                } else {
                    $('.aica-loading-files').hide();
                    if (container) {
                        container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                    }
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function(xhr, status, error) {
                $('.aica-loading-files').hide();
                if (container) {
                    container.parent().find('.aica-file-tree-folder').removeClass('aica-loading');
                }
                pokazPowiadomienie('error', aica_repos.i18n.load_error);
                console.error('Błąd AJAX:', status, error);
            }
        });
    }
    
    // Funkcja budująca drzewo plików
    function zbudujDrzewoPlikow(pliki, kontener, repoId, branch) {
        // Sortowanie: najpierw foldery, potem pliki, alfabetycznie
        pliki.sort(function(a, b) {
            if (a.type !== b.type) {
                return a.type === 'dir' || a.type === 'tree' || a.type === 'commit_directory' ? -1 : 1;
            }
            return a.name.localeCompare(b.name);
        });
        
        // Grupowanie plików według rozszerzenia
        const plikiWgRozszerzenia = {};
        const katalogi = [];
        
        pliki.forEach(function(plik) {
            if (plik.type === 'dir' || plik.type === 'tree' || plik.type === 'commit_directory') {
                katalogi.push(plik);
            } else {
                const rozszerzenie = plik.name.split('.').pop().toLowerCase() || 'inne';
                if (!plikiWgRozszerzenia[rozszerzenie]) {
                    plikiWgRozszerzenia[rozszerzenie] = [];
                }
                plikiWgRozszerzenia[rozszerzenie].push(plik);
            }
        });
        
        // Dodaj katalogi
        katalogi.forEach(function(katalog) {
            zbudujElementDrzewaKatalogu(katalog, kontener, repoId, branch);
        });
        
        // Dodaj pliki pogrupowane według rozszerzenia
        for (const rozszerzenie in plikiWgRozszerzenia) {
            plikiWgRozszerzenia[rozszerzenie].forEach(function(plik) {
                zbudujElementDrzewaPliku(plik, kontener, repoId, branch);
            });
        }
    }
    
    // Funkcja budująca element drzewa dla katalogu
    function zbudujElementDrzewaKatalogu(katalog, kontener, repoId, branch) {
        const elementKatalogu = $('<div class="aica-file-tree-item"></div>');
        const naglowekKatalogu = $('<div class="aica-file-tree-folder" data-path="' + katalog.path + '"></div>');
        naglowekKatalogu.append('<span class="dashicons dashicons-category"></span>');
        naglowekKatalogu.append('<span class="aica-file-name">' + katalog.name + '</span>');
        
        // Dodaj licznik jeśli katalog jest duży
        if (katalog.size && katalog.size > 20) {
            naglowekKatalogu.append('<span class="aica-file-count">' + katalog.size + '</span>');
        }
        
        // Dodanie kontenera dla dzieci
        const kontenerDzieci = $('<div class="aica-file-tree-children"></div>').hide();
        
        // Dodanie elementów do kontenera
        elementKatalogu.append(naglowekKatalogu);
        elementKatalogu.append(kontenerDzieci);
        kontener.append(elementKatalogu);
        
        // Obsługa kliknięcia folderu
        naglowekKatalogu.on('click', function() {
            const sciezka = $(this).data('path');
            $(this).toggleClass('expanded');
            
            // Jeśli folder ma już załadowane dzieci, po prostu rozwiń/zwiń
            if (kontenerDzieci.children().length > 0) {
                kontenerDzieci.slideToggle(200);
            } else {
                // Załaduj zawartość folderu
                zaladujStrukturePlikow(repoId, sciezka, branch, kontenerDzieci);
            }
        });
    }
    
    // Funkcja budująca element drzewa dla pliku
    function zbudujElementDrzewaPliku(plik, kontener, repoId, branch) {
        const elementPliku = $('<div class="aica-file-tree-item"></div>');
        const linkPliku = $('<div class="aica-file-tree-file" data-path="' + plik.path + '"></div>');
        
        // Wybierz ikonę na podstawie rozszerzenia pliku
        let ikonaPliku = 'dashicons-media-default';
        const rozszerzenie = plik.name.split('.').pop().toLowerCase();
        
        // Mapa rozszerzeń do ikon
        const mapaIkon = {
            'php': 'dashicons-editor-code',
            'js': 'dashicons-editor-code',
            'jsx': 'dashicons-editor-code',
            'ts': 'dashicons-editor-code',
            'tsx': 'dashicons-editor-code',
            'css': 'dashicons-admin-customizer',
            'scss': 'dashicons-admin-customizer',
            'less': 'dashicons-admin-customizer',
            'html': 'dashicons-editor-code',
            'htm': 'dashicons-editor-code',
            'md': 'dashicons-media-text',
            'txt': 'dashicons-media-text',
            'jpg': 'dashicons-format-image',
            'jpeg': 'dashicons-format-image',
            'png': 'dashicons-format-image',
            'gif': 'dashicons-format-image',
            'svg': 'dashicons-format-image',
            'json': 'dashicons-media-code',
            'xml': 'dashicons-media-code',
            'yml': 'dashicons-media-code',
            'yaml': 'dashicons-media-code'
        };
        
        ikonaPliku = mapaIkon[rozszerzenie] || 'dashicons-media-default';
        
        linkPliku.append('<span class="dashicons ' + ikonaPliku + '"></span>');
        linkPliku.append('<span class="aica-file-name">' + plik.name + '</span>');
        
        elementPliku.append(linkPliku);
        kontener.append(elementPliku);
        
        // Obsługa kliknięcia pliku
        linkPliku.on('click', function() {
            const sciezka = $(this).data('path');
            
            // Dodaj klasę ładowania
            $(this).addClass('aica-loading');
            
            // Zaznaczenie aktywnego pliku
            $('.aica-file-tree-file').removeClass('active');
            $(this).addClass('active');
            
            // Załaduj zawartość pliku
            zaladujZawartoscPliku(repoId, sciezka, branch, $(this));
        });
    }
    
    // Funkcja ładująca zawartość pliku
    function zaladujZawartoscPliku(repoId, path, branch = 'main', elementPliku = null) {
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
                if (elementPliku) {
                    elementPliku.removeClass('aica-loading');
                }
                
                if (response.success) {
                    // Wyświetl zawartość pliku
                    const zawartosc = response.data.content;
                    const jezyk = response.data.language || '';
                    
                    // Aktualizacja zawartości
                    $('#aica-file-content code').text(zawartosc);
                    
                    // Dodaj klasę języka do elementu code, jeśli istnieje
                    if (jezyk) {
                        $('#aica-file-content code').attr('class', 'language-' + jezyk);
                    }
                    
                    // Podświetlanie składni, jeśli dostępne
                    if (typeof Prism !== 'undefined') {
                        Prism.highlightElement($('#aica-file-content code')[0]);
                    }
                    
                    // Zapisz aktualną ścieżkę i zawartość dla późniejszego użycia
                    $('#aica-file-content').data('path', path);
                    $('#aica-file-content').data('content', zawartosc);
                } else {
                    // Wyświetl komunikat o błędzie
                    $('#aica-file-content code').text(response.data.message || aica_repos.i18n.load_file_error);
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.load_file_error);
                }
            },
            error: function(xhr, status, error) {
                // Ukryj wskaźnik ładowania
                $('.aica-loading-content').hide();
                if (elementPliku) {
                    elementPliku.removeClass('aica-loading');
                }
                
                // Wyświetl komunikat o błędzie
                $('#aica-file-content code').text(aica_repos.i18n.load_file_error);
                pokazPowiadomienie('error', aica_repos.i18n.load_file_error);
                console.error('Błąd AJAX:', status, error);
            }
        });
    }
    
    // Kopiowanie zawartości pliku
    $('.aica-copy-file-button').on('click', function() {
        const zawartosc = $('#aica-file-content').data('content');
        
        if (zawartosc) {
            try {
                // Użycie API schowka, jeśli dostępne
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(zawartosc).then(function() {
                        pokazPowiadomienie('success', aica_repos.i18n.copy_success);
                    }).catch(function() {
                        // Fallback
                        kopiujFallback(zawartosc);
                    });
                } else {
                    // Fallback dla starszych przeglądarek
                    kopiujFallback(zawartosc);
                }
            } catch (e) {
                console.error('Błąd podczas kopiowania:', e);
                alert(aica_repos.i18n.copy_error);
            }
        }
    });
    
    // Funkcja kopiowania dla starszych przeglądarek
    function kopiujFallback(tekst) {
        const tempTextarea = $('<textarea>').val(tekst).appendTo('body').select();
        document.execCommand('copy');
        tempTextarea.remove();
        pokazPowiadomienie('success', aica_repos.i18n.copy_success);
    }
    
    // Używanie pliku w czacie
    $('.aica-use-in-chat-button').on('click', function() {
        const sciezka = $('#aica-file-content').data('path');
        const zawartosc = $('#aica-file-content').data('content');
        
        if (sciezka && zawartosc) {
            // Przekierowanie do czatu z plikiem
            window.location.href = aica_repos.chat_url + '&file_path=' + encodeURIComponent(sciezka) + '&file_content=' + encodeURIComponent(zawartosc);
        } else {
            pokazPowiadomienie('error', aica_repos.i18n.no_file_selected);
        }
    });
    
    // Wyszukiwanie plików
    let wyszukiwanieTimeout;
    $('#aica-file-search').on('keyup', function() {
        clearTimeout(wyszukiwanieTimeout);
        
        const wyszukiwaniePole = $(this);
        
        wyszukiwanieTimeout = setTimeout(function() {
            const fraza = wyszukiwaniePole.val().toLowerCase();
            
            if (fraza.length < 2) {
                // Zresetuj wszystkie podświetlenia jeśli wyszukiwanie jest puste
                $('.aica-file-tree-file').removeClass('aica-file-match');
                $('.aica-file-tree-folder').removeClass('expanded');
                $('.aica-file-tree-children').hide();
                return;
            }
            
            // Przeszukaj drzewo plików
            let znaleziono = false;
            
            // Podświetlanie pasujących plików
            $('.aica-file-tree-file').each(function() {
                const nazwaPliku = $(this).find('.aica-file-name').text().toLowerCase();
                
                if (nazwaPliku.includes(fraza)) {
                    $(this).addClass('aica-file-match');
                    znaleziono = true;
                    
                    // Rozwiń rodzica
                    $(this).parents('.aica-file-tree-children').each(function() {
                        $(this).show();
                        $(this).prev('.aica-file-tree-folder').addClass('expanded');
                    });
                } else {
                    $(this).removeClass('aica-file-match');
                }
            });
            
            // Pokaż komunikat jeśli nie znaleziono wyników
            if (!znaleziono && fraza.length > 0) {
                if ($('.aica-file-search-no-results').length === 0) {
                    $('#aica-file-tree').append(`
                        <div class="aica-file-search-no-results">
                            <p>${aica_repos.i18n.no_files_found}</p>
                        </div>
                    `);
                }
            } else {
                $('.aica-file-search-no-results').remove();
            }
        }, 300); // 300ms opóźnienia
    });
    
    // Zmiana gałęzi
    $('#aica-branch-select').on('change', function() {
        const branch = $(this).val();
        const repoId = $('#aica-file-tree').data('repo-id');
        
        if (repoId) {
            // Wyczyść zawartość pliku
            $('#aica-file-content code').empty();
            $('.aica-file-path').text('');
            
            // Załaduj strukturę plików dla wybranej gałęzi
            zaladujStrukturePlikow(repoId, '', branch);
        }
    });
    
    // Tryb ciemny
    $('.aica-dark-mode-toggle').on('click', function() {
        $('body').toggleClass('aica-dark-mode');
        
        // Zapisz preferencję
        const trybCiemny = $('body').hasClass('aica-dark-mode');
        localStorage.setItem('aica_dark_mode', trybCiemny ? 'true' : 'false');
    });
    
    // Sprawdź zapisaną preferencję
    if (localStorage.getItem('aica_dark_mode') === 'true') {
        $('body').addClass('aica-dark-mode');
    }
    
    // Sprawdź preferencję systemu, jeśli nie ma zapisanej preferencji
    if (localStorage.getItem('aica_dark_mode') === null) {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $('body').addClass('aica-dark-mode');
        }
    }
    
    /**
     * OBSŁUGA FORMULARZY I AJAX
     */
    
    // Obsługa formularzy dodawania repozytoriów - użyj AJAX zamiast zwykłego formularza
    $(document).on('submit', '.aica-add-repo-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[name="aica_add_repository"]');
        const formError = form.find('.aica-form-error');
        
        // Usuń poprzednie błędy
        formError.empty().hide();
        
        // Sprawdź czy wszystkie wymagane pola są wypełnione
        const requiredFields = ['repo_type', 'repo_name', 'repo_owner', 'repo_url'];
        let hasErrors = false;
        
        requiredFields.forEach(function(field) {
            const fieldElement = form.find(`input[name="${field}"]`);
            const fieldValue = fieldElement.val().trim();
            
            if (fieldValue === '') {
                fieldElement.addClass('aica-field-error');
                hasErrors = true;
            } else {
                fieldElement.removeClass('aica-field-error');
            }
        });
        
        if (hasErrors) {
            formError.html('<p>' + aica_repos.i18n.fill_required_fields + '</p>').show();
            return;
        }
        
        // Dezaktywuj przycisk, aby uniknąć wielokrotnego kliknięcia
        submitButton.prop('disabled', true);
        submitButton.html('<span class="dashicons dashicons-update" style="animation: aica-spin 1s linear infinite;"></span> ' + aica_repos.i18n.adding);
        
        // Przygotuj dane formularza
        const formData = new FormData();
        formData.append('action', 'aica_add_repository');
        formData.append('nonce', aica_repos.nonce);
        formData.append('repo_type', form.find('input[name="repo_type"]').val().trim());
        formData.append('repo_name', form.find('input[name="repo_name"]').val().trim());
        formData.append('repo_owner', form.find('input[name="repo_owner"]').val().trim());
        formData.append('repo_url', form.find('input[name="repo_url"]').val().trim());
        formData.append('repo_external_id', form.find('input[name="repo_external_id"]').val().trim());
        formData.append('repo_description', form.find('input[name="repo_description"]').val().trim());
        
        console.log('Wysyłanie danych repozytorium:', {
            action: 'aica_add_repository',
            nonce: aica_repos.nonce,
            repo_type: form.find('input[name="repo_type"]').val().trim(),
            repo_name: form.find('input[name="repo_name"]').val().trim(),
            repo_owner: form.find('input[name="repo_owner"]').val().trim(),
            repo_url: form.find('input[name="repo_url"]').val().trim(),
            repo_external_id: form.find('input[name="repo_external_id"]').val().trim(),
            repo_description: form.find('input[name="repo_description"]').val().trim()
        });
        
        // Wyślij żądanie AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Odpowiedź dodawania repozytorium:', response);
                
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                if (response.success) {
                    // Pokaż komunikat o sukcesie
                    pokazPowiadomienie('success', response.data.message || aica_repos.i18n.add_success);
                    
                    // Odśwież stronę, aby pokazać nowe repozytorium
                    window.location.href = window.location.href.split('?')[0] + '?page=ai-chat-assistant-repositories&added=true';
                } else {
                    // Pokaż komunikat o błędzie
                    const errorMessage = response.data && response.data.message 
                        ? response.data.message 
                        : aica_repos.i18n.add_error;
                    
                    formError.html('<p>' + errorMessage + '</p>').show();
                    pokazPowiadomienie('error', errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Błąd AJAX:', xhr, status, error);
                
                // Przywróć przycisk do oryginalnego stanu
                submitButton.prop('disabled', false);
                submitButton.html('<span class="dashicons dashicons-plus"></span> ' + aica_repos.i18n.add);
                
                // Pokaż komunikat o błędzie
                formError.html('<p>' + aica_repos.i18n.add_error + '</p>').show();
                pokazPowiadomienie('error', aica_repos.i18n.add_error);
            }
        });
    });
    
    // Obsługa usuwania repozytorium
    $(document).on('click', '.aica-delete-repo, .aica-delete-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const repoId = $(this).data('repo-id');
        const repoName = $(this).closest('.aica-repository-card').find('.aica-repo-title h3').text();
        const card = $(this).closest('.aica-repository-card');
        
        // Pokaż dialog potwierdzający
        $('#aica-delete-dialog').data('repo-id', repoId).data('card', card).show();
        $('#aica-repo-name-confirm').text(repoName);
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
        
        // Dodaj klasę ładowania do przycisku
        $(this).addClass('aica-loading').prop('disabled', true);
        
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
                // Przywróć przycisk do oryginalnego stanu
                $('.aica-delete-confirm').removeClass('aica-loading').prop('disabled', false);
                
                if (response.success) {
                    // Pokaż powiadomienie o sukcesie
                    pokazPowiadomienie('success', response.data.message || aica_repos.i18n.delete_success);
                    
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
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.delete_error);
                }
            },
            error: function(xhr, status, error) {
                // Przywróć przycisk do oryginalnego stanu
                $('.aica-delete-confirm').removeClass('aica-loading').prop('disabled', false);
                
                // Pokaż komunikat o błędzie
                pokazPowiadomienie('error', aica_repos.i18n.delete_error);
                console.error('Błąd AJAX:', status, error);
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
                    pokazPowiadomienie('success', aica_repos.i18n.refresh_success);
                    
                    // Odśwież stronę
                    location.reload();
                } else {
                    // Pokaż komunikat o błędzie
                    pokazPowiadomienie('error', response.data.message || aica_repos.i18n.refresh_error);
                }
            },
            error: function(xhr, status, error) {
                // Usuń klasę ładowania
                card.removeClass('aica-card-refreshing');
                
                // Pokaż komunikat o błędzie
                pokazPowiadomienie('error', aica_repos.i18n.refresh_error);
                console.error('Błąd AJAX:', status, error);
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
            pokazPowiadomienie('error', aica_repos.i18n.no_sources_configured);
            window.location.href = aica_repos.settings_url;
        }
    });
    
    /**
     * FUNKCJE TESTUJĄCE DOSTĘPNOŚĆ TABELI REPOZYTORIÓW
     */
    // Dodaj obsługę akcji AJAX dla testów
    $(document).on('aica_test_db', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_db'
            },
            success: function(response) {
                console.log('Test DB Results:', response);
                if (response.success && !response.data.table_exists) {
                    // Utwórz tabelę, jeśli nie istnieje
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aica_activate_plugin'
                        },
                        success: function() {
                            console.log('Tabela repozytoriów została utworzona');
                        }
                    });
                }
            }
        });
    });
    
    // Inicjalizacja strony
    inicjalizacja();
    
    // Odpal test tabeli
    $(document).trigger('aica_test_db');
});