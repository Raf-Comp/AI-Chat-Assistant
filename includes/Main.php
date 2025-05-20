<?php
namespace AICA;

class Main {
    private $admin_pages;
    private $admin_init;

    public function __construct() {
        // Upewnij się, że te klasy istnieją w odpowiednich katalogach
        $this->admin_pages = new \AICA\Admin\PageManager();
        $this->admin_init = new \AICA\Admin\Init();
    }

    public function run() {
        // Rejestracja menu administratora
        add_action('admin_menu', [$this->admin_pages, 'register_menu']);
        
        // Inicjalizacja managera AJAX
        $this->init_ajax();
        
        // Dodajemy hook inicjalizujący użytkownika przy pierwszym ładowaniu wtyczki
        add_action('init', [$this, 'initialize_current_user']);
        
        // Dodajemy hook do rejestracji styli i skryptów
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        
        // Dodajemy hook do zapisywania ustawień interfejsu użytkownika
        add_action('admin_post_save_aica_interface_settings', [$this, 'save_interface_settings']);
    }
    
    /**
     * Inicjalizacja AJAX
     */
    public function init_ajax() {
        $ajax_manager = new \AICA\Ajax\AjaxManager();
    }
    
    /**
     * Inicjalizacja bieżącego użytkownika
     */
    public function initialize_current_user() {
        // Jeśli aktualny użytkownik jest zalogowany
        $current_wp_user_id = get_current_user_id();
        if ($current_wp_user_id > 0) {
            // Sprawdź czy użytkownik istnieje w naszej tabeli
            $aica_user_id = aica_get_user_id($current_wp_user_id);
            
            if (!$aica_user_id) {
                // Jeśli nie, dodaj go
                $user_data = get_userdata($current_wp_user_id);
                if ($user_data) {
                    // Pobierz najwyższą rolę
                    $roles = $user_data->roles;
                    $role = 'subscriber';
                    
                    // Hierarchia ról
                    $role_hierarchy = [
                        'administrator' => 5,
                        'editor' => 4,
                        'author' => 3,
                        'contributor' => 2,
                        'subscriber' => 1
                    ];
                    
                    $highest_rank = 0;
                    foreach ($roles as $r) {
                        $rank = isset($role_hierarchy[$r]) ? $role_hierarchy[$r] : 0;
                        if ($rank > $highest_rank) {
                            $highest_rank = $rank;
                            $role = $r;
                        }
                    }
                    
                   // Dodaj użytkownika
                    aica_add_user(
                        $current_wp_user_id,
                        $user_data->user_login,
                        $user_data->user_email,
                        $role,
                        current_time('mysql')
                    );
                    
                    aica_log('Inicjalizowano użytkownika: ' . $user_data->user_login . ' (ID: ' . $current_wp_user_id . ')');
                }
            }
        }
    }
    
    /**
     * Zapisywanie ustawień interfejsu użytkownika
     */
    public function save_interface_settings() {
        // Sprawdzenie nonce
        if (!isset($_POST['aica_interface_nonce']) || !wp_verify_nonce($_POST['aica_interface_nonce'], 'aica_interface_settings')) {
            wp_die(__('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant'));
        }
        
        // Zapisz ustawienia interfejsu
        $dark_mode = isset($_POST['aica_dark_mode']) ? 1 : 0;
        $compact_view = isset($_POST['aica_compact_view']) ? 1 : 0;
        
        update_option('aica_dark_mode', $dark_mode);
        update_option('aica_compact_view', $compact_view);
        
        // Zapisz ustawienia w localStorage przez JavaScript
        ?>
        <script type="text/javascript">
            localStorage.setItem('aica_dark_mode', '<?php echo $dark_mode ? 'true' : 'false'; ?>');
            localStorage.setItem('aica_compact_mode', '<?php echo $compact_view ? 'true' : 'false'; ?>');
            window.location.href = '<?php echo admin_url('admin.php?page=ai-chat-assistant&settings-updated=1'); ?>';
        </script>
        <?php
        exit;
    }
    
    /**
     * Rejestracja skryptów i stylów administracyjnych
     */
    public function register_admin_assets($hook) {
        // Pobranie informacji o stronie
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Globalne style i skrypty dla stron naszej wtyczki
        if (strpos($hook, 'ai-chat-assistant') !== false) {
            // Style CSS
            wp_enqueue_style(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/css/admin.css',
                [],
                AICA_VERSION
            );
        }

        // Style i skrypty dla konkretnych stron
        if ($page === 'ai-chat-assistant-settings') {
            // Strona ustawień
            // Korzystamy z oddzielnych plików zamiast rejestrować je tutaj
            // Pliki te są ładowane bezpośrednio w szablonie settings.php
            
            // Skrypty ogólne dla administratora
            wp_enqueue_script(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AICA_VERSION,
                true
            );

            // Przekazanie danych do skryptu
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'interface_nonce' => wp_create_nonce('aica_interface_settings'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant'),
                    'testing' => __('Testowanie...', 'ai-chat-assistant'),
                    'connection_success' => __('Połączenie działa poprawnie', 'ai-chat-assistant'),
                    'connection_error' => __('Błąd połączenia', 'ai-chat-assistant'),
                    'refreshing_models' => __('Odświeżanie listy modeli...', 'ai-chat-assistant')
                ]
            ]);
        } elseif ($page === 'ai-chat-assistant-diagnostics') {
            // Strona diagnostyki
            wp_enqueue_style('aica-diagnostics-style', AICA_PLUGIN_URL . 'assets/css/diagnostics.css', [], AICA_VERSION);
            wp_enqueue_script('aica-diagnostics-script', AICA_PLUGIN_URL . 'assets/js/diagnostics.js', ['jquery'], AICA_VERSION, true);
            
            // Skrypty ogólne dla administratora
            wp_enqueue_script(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AICA_VERSION,
                true
            );
            
            wp_localize_script('aica-diagnostics-script', 'aica_diagnostics_data', [
                'nonce' => wp_create_nonce('aica_diagnostics_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'chat_url' => admin_url('admin.php?page=ai-chat-assistant')
            ]);
            
            // Przekazanie danych do skryptu admin
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
                ]
            ]);
        } elseif ($page === 'ai-chat-assistant-repositories') {
            // Strona repozytoriów
            wp_enqueue_style('aica-repositories-style', AICA_PLUGIN_URL . 'assets/css/repositories.css', [], AICA_VERSION);
            wp_enqueue_script('aica-repositories-script', AICA_PLUGIN_URL . 'assets/js/repositories.js', ['jquery'], AICA_VERSION, true);
            
            // Skrypty ogólne dla administratora
            wp_enqueue_script(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AICA_VERSION,
                true
            );
            
            // Dodaj Prism.js dla podświetlania składni kodu
            wp_enqueue_style('prism-css', AICA_PLUGIN_URL . 'assets/vendor/prism/prism.css', [], AICA_VERSION);
            wp_enqueue_script('prism-js', AICA_PLUGIN_URL . 'assets/vendor/prism/prism.js', [], AICA_VERSION, true);
            
            wp_localize_script('aica-repositories-script', 'aica_repos', [
                'nonce' => wp_create_nonce('aica_repository_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'settings_url' => admin_url('admin.php?page=ai-chat-assistant-settings'),
                'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
                'i18n' => [
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'adding' => __('Dodawanie...', 'ai-chat-assistant'),
                    'refreshing' => __('Odświeżanie...', 'ai-chat-assistant'),
                    'add' => __('Dodaj', 'ai-chat-assistant'),
                    'add_success' => __('Repozytorium zostało dodane pomyślnie.', 'ai-chat-assistant'),
                    'add_error' => __('Wystąpił błąd podczas dodawania repozytorium.', 'ai-chat-assistant'),
                    'delete_error' => __('Wystąpił błąd podczas usuwania repozytorium.', 'ai-chat-assistant'),
                    'refresh_success' => __('Repozytorium zostało odświeżone pomyślnie.', 'ai-chat-assistant'),
                    'refresh_error' => __('Wystąpił błąd podczas odświeżania repozytorium.', 'ai-chat-assistant'),
                    'no_repositories' => __('Brak repozytoriów', 'ai-chat-assistant'),
                    'no_repositories_desc' => __('Nie masz jeszcze żadnych repozytoriów. Dodaj pierwsze repozytorium, aby zacząć.', 'ai-chat-assistant'),
                    'no_sources_configured' => __('Nie skonfigurowano żadnych źródeł repozytoriów. Przejdź do ustawień, aby skonfigurować źródła.', 'ai-chat-assistant'),
                    'add_repository' => __('Dodaj repozytorium', 'ai-chat-assistant'),
                    'load_error' => __('Wystąpił błąd podczas ładowania danych.', 'ai-chat-assistant'),
                    'load_file_error' => __('Wystąpił błąd podczas ładowania pliku.', 'ai-chat-assistant'),
                    'copy_success' => __('Zawartość pliku została skopiowana do schowka.', 'ai-chat-assistant')
                ]
            ]);
            
            // Przekazanie danych do skryptu admin
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
                ]
            ]);
        } elseif ($page === 'ai-chat-assistant-history') {
            // Strona historii
            wp_enqueue_style('aica-history-style', AICA_PLUGIN_URL . 'assets/css/history.css', [], AICA_VERSION);
            wp_enqueue_script('aica-history-script', AICA_PLUGIN_URL . 'assets/js/history.js', ['jquery'], AICA_VERSION, true);
            
            // Skrypty ogólne dla administratora
            wp_enqueue_script(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AICA_VERSION,
                true
            );
            
            wp_localize_script('aica-history-script', 'aica_history', [
                'nonce' => wp_create_nonce('aica_history_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
                'i18n' => [
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'loading_messages' => __('Ładowanie wiadomości...', 'ai-chat-assistant'),
                    'load_error' => __('Wystąpił błąd podczas ładowania danych.', 'ai-chat-assistant'),
                    'no_conversations' => __('Brak rozmów', 'ai-chat-assistant'),
                    'no_conversations_desc' => __('Nie masz jeszcze żadnych rozmów. Rozpocznij nową rozmowę, aby zobaczyć ją tutaj.', 'ai-chat-assistant'),
                    'no_messages' => __('Ta rozmowa nie zawiera żadnych wiadomości.', 'ai-chat-assistant'),
                    'new_conversation' => __('Nowa rozmowa', 'ai-chat-assistant'),
                    'continue_conversation' => __('Kontynuuj rozmowę', 'ai-chat-assistant'),
                    'duplicate' => __('Duplikuj', 'ai-chat-assistant'),
                    'export' => __('Eksportuj', 'ai-chat-assistant'),
                    'delete' => __('Usuń', 'ai-chat-assistant'),
                    'duplicate_success' => __('Rozmowa została zduplikowana.', 'ai-chat-assistant'),
                    'duplicate_error' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant'),
                    'delete_error' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant'),
                    'confirm_delete' => __('Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.', 'ai-chat-assistant'),
                    'min_search_length' => __('Wyszukiwanie musi zawierać co najmniej 3 znaki.', 'ai-chat-assistant'),
                    'pagination_info' => __('Pokazuję %1$s - %2$s z %3$s', 'ai-chat-assistant'),
                    'second' => __('sekunda', 'ai-chat-assistant'),
                    'seconds' => __('sekund', 'ai-chat-assistant'),
                    'minute' => __('minuta', 'ai-chat-assistant'),
                    'minutes' => __('minut', 'ai-chat-assistant'),
                    'hour' => __('godzina', 'ai-chat-assistant'),
                    'hours' => __('godzin', 'ai-chat-assistant'),
                    'day' => __('dzień', 'ai-chat-assistant'),
                    'days' => __('dni', 'ai-chat-assistant'),
                    'month' => __('miesiąc', 'ai-chat-assistant'),
                    'months' => __('miesięcy', 'ai-chat-assistant'),
                    'year' => __('rok', 'ai-chat-assistant'),
                    'years' => __('lat', 'ai-chat-assistant'),
                    'ago' => __('temu', 'ai-chat-assistant'),
                    'just_now' => __('przed chwilą', 'ai-chat-assistant')
                ]
            ]);
            
            // Przekazanie danych do skryptu admin
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
                ]
            ]);
        } elseif ($page === 'ai-chat-assistant') {
            // Strona główna czatu
            // ChatPage::enqueue_chat_assets zajmuje się już rejestracją skryptów i stylów
            // Sprawdzamy tylko czy ustawienia UI zostały zapisane
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 1) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Ustawienia interfejsu zostały zapisane.', 'ai-chat-assistant') . '</p></div>';
                });
            }
        } else if (strpos($hook, 'ai-chat-assistant') !== false) {
            // Dla pozostałych stron wtyczki, które nie są obsługiwane powyżej
            // Ładujemy tylko podstawowe skrypty administracyjne
            wp_enqueue_script(
                'aica-admin',
                AICA_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AICA_VERSION,
                true
            );
            
            // Przekazanie danych do skryptu admin
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'interface_nonce' => wp_create_nonce('aica_interface_settings'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
                ]
            ]);
        }
    }
}