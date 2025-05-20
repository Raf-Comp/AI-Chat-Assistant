<?php
namespace AICA;

class Main {
    private $ajax_handler;
    private $admin_pages;
    private $admin_init;

    public function __construct() {
        // Upewnij się, że te klasy istnieją w odpowiednich katalogach
        $this->ajax_handler = new \AICA\Ajax\Handler();
        $this->admin_pages = new \AICA\Admin\PageManager();
        $this->admin_init = new \AICA\Admin\Init();
        
        // Dodanie hook'a do odświeżania modeli
        add_action('admin_post_aica_refresh_models', [$this, 'handle_refresh_models']);
    }

    public function run() {
        // Rejestracja menu administratora
        add_action('admin_menu', [$this->admin_pages, 'register_menu']);
        
        // Inicjalizacja handlera AJAX
        // już zainicjalizowany w konstruktorze
        
        // Dodajemy hook inicjalizujący użytkownika przy pierwszym ładowaniu wtyczki
        add_action('init', [$this, 'initialize_current_user']);
        
        // Dodajemy hook do rejestracji styli i skryptów
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        
        // Dodajemy hook do rejestracji styli i skryptów historii
        add_action('admin_enqueue_scripts', [$this, 'enqueue_history_assets']);
        
        // Dodajemy hook do rejestracji styli i skryptów repozytoriów
        add_action('admin_enqueue_scripts', [$this, 'enqueue_repositories_assets']);
        
        // Dodajemy hook do zapisywania ustawień
        add_action('admin_post_save_aica_settings', [$this, 'save_settings']);
    }
    
    /**
     * Obsługa odświeżania modeli
     */
    public function handle_refresh_models() {
        // Sprawdzenie nonce
        if (!isset($_POST['aica_settings_nonce']) || !wp_verify_nonce($_POST['aica_settings_nonce'], 'aica_settings_nonce')) {
            wp_die(__('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant'));
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz uprawnień do wykonania tej operacji.', 'ai-chat-assistant'));
        }
        
        // Pobranie klucza API
        $api_key = aica_get_option('claude_api_key', '');
        
        if (empty($api_key)) {
            // Przekierowanie z komunikatem błędu
            wp_redirect(add_query_arg([
                'page' => 'ai-chat-assistant-settings',
                'aica_error' => 'no_api_key'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Pobranie dostępnych modeli
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $models = $claude_client->get_available_models();
        
        if (empty($models)) {
            // Przekierowanie z komunikatem błędu
            wp_redirect(add_query_arg([
                'page' => 'ai-chat-assistant-settings',
                'aica_error' => 'models_fetch_failed'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Aktualizacja czasu ostatniego odświeżenia
        aica_update_option('claude_models_last_update', current_time('mysql'));
        
        // Przekierowanie z komunikatem sukcesu
        wp_redirect(add_query_arg([
            'page' => 'ai-chat-assistant-settings',
            'aica_success' => 'models_refreshed'
        ], admin_url('admin.php')));
        exit;
    }
    
    /**
     * Zapisywanie ustawień
     */
    public function save_settings() {
        // Sprawdzenie nonce
        if (!isset($_POST['aica_settings_nonce']) || !wp_verify_nonce($_POST['aica_settings_nonce'], 'aica_settings_nonce')) {
            wp_die(__('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant'));
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz uprawnień do wykonania tej operacji.', 'ai-chat-assistant'));
        }
        
        // Zapisanie ustawień Claude API
        if (isset($_POST['aica_claude_api_key'])) {
            $api_key = sanitize_text_field($_POST['aica_claude_api_key']);
            aica_update_option('claude_api_key', $api_key);
        }
        
        if (isset($_POST['aica_claude_model'])) {
            $model = sanitize_text_field($_POST['aica_claude_model']);
            aica_update_option('claude_model', $model);
        }
        
        if (isset($_POST['aica_max_tokens'])) {
            $max_tokens = intval($_POST['aica_max_tokens']);
            aica_update_option('max_tokens', $max_tokens);
        }
        
        if (isset($_POST['aica_temperature'])) {
            $temperature = floatval($_POST['aica_temperature']);
            $temperature = max(0, min(1, $temperature)); // Ograniczenie do zakresu 0-1
            aica_update_option('temperature', $temperature);
        }
        
        // Zapisanie ustawień repozytoriów
        if (isset($_POST['aica_github_token'])) {
            $github_token = sanitize_text_field($_POST['aica_github_token']);
            aica_update_option('github_token', $github_token);
        }
        
        if (isset($_POST['aica_gitlab_token'])) {
            $gitlab_token = sanitize_text_field($_POST['aica_gitlab_token']);
            aica_update_option('gitlab_token', $gitlab_token);
        }
        
        if (isset($_POST['aica_bitbucket_username'])) {
            $bitbucket_username = sanitize_text_field($_POST['aica_bitbucket_username']);
            aica_update_option('bitbucket_username', $bitbucket_username);
        }
        
        if (isset($_POST['aica_bitbucket_app_password'])) {
            $bitbucket_password = sanitize_text_field($_POST['aica_bitbucket_app_password']);
            aica_update_option('bitbucket_app_password', $bitbucket_password);
        }
        
        // Zapisanie ustawień ogólnych
        if (isset($_POST['aica_allowed_file_extensions'])) {
            $extensions = sanitize_text_field($_POST['aica_allowed_file_extensions']);
            aica_update_option('allowed_file_extensions', $extensions);
        }
        
        if (isset($_POST['aica_debug_mode'])) {
            $debug_mode = $_POST['aica_debug_mode'] == '1';
            aica_update_option('debug_mode', $debug_mode);
        } else {
            aica_update_option('debug_mode', false);
        }
        
        if (isset($_POST['aica_auto_purge_enabled'])) {
            $auto_purge_enabled = $_POST['aica_auto_purge_enabled'] == '1';
            aica_update_option('auto_purge_enabled', $auto_purge_enabled);
        } else {
            aica_update_option('auto_purge_enabled', false);
        }
        
        if (isset($_POST['aica_auto_purge_days'])) {
            $auto_purge_days = intval($_POST['aica_auto_purge_days']);
            aica_update_option('auto_purge_days', $auto_purge_days);
        }
        
        // Zapisanie szablonów promptów
        if (isset($_POST['aica_prompt_templates']) && is_array($_POST['aica_prompt_templates'])) {
            $templates = [];
            
            foreach ($_POST['aica_prompt_templates'] as $template) {
                if (!empty($template['name']) && !empty($template['prompt'])) {
                    $templates[] = [
                        'name' => sanitize_text_field($template['name']),
                        'prompt' => sanitize_textarea_field($template['prompt'])
                    ];
                }
            }
            
            aica_update_option('prompt_templates', $templates);
        }
        
        // Przekierowanie z komunikatem sukcesu
        wp_redirect(add_query_arg([
            'page' => 'ai-chat-assistant-settings',
            'settings-updated' => 'true'
        ], admin_url('admin.php')));
        exit;
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
     * Rejestracja skryptów i stylów administracyjnych
     */
    public function register_admin_assets($hook) {
        // Ładuj assety tylko na stronach naszej wtyczki
        if (strpos($hook, 'ai-chat-assistant') === false) {
            return;
        }

        // Style CSS
        wp_enqueue_style(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AICA_VERSION
        );

        // Skrypty JavaScript
        wp_enqueue_script(
            'aica-admin',
            AICA_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            AICA_VERSION,
            true
        );

        // Przekaż dane do JS
        wp_localize_script('aica-admin', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url('admin-post.php'),
            'nonce' => wp_create_nonce('aica_nonce'),
            'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
            'i18n' => [
                'error' => __('Błąd', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                'saved' => __('Zapisano', 'ai-chat-assistant'),
                'save_error' => __('Błąd zapisywania', 'ai-chat-assistant'),
                'testing' => __('Testowanie...', 'ai-chat-assistant'),
                'connection_success' => __('Połączenie działa poprawnie', 'ai-chat-assistant'),
                'connection_error' => __('Błąd połączenia', 'ai-chat-assistant'),
                'refreshing_models' => __('Odświeżanie listy modeli...', 'ai-chat-assistant')
            ]
        ]);
    }

    /**
     * Rejestracja skryptów i stylów dla strony historii
     */
    public function enqueue_history_assets($hook) {
        // Ładuj assety tylko na stronie historii
        if ($hook != 'ai-chat-assistant_page_ai-chat-assistant-history') {
            return;
        }
        
        // Style CSS
        wp_enqueue_style(
            'aica-history',
            AICA_PLUGIN_URL . 'assets/css/history.css',
            [],
            AICA_VERSION
        );
        
        // Skrypty JavaScript
        wp_enqueue_script(
            'aica-history',
            AICA_PLUGIN_URL . 'assets/js/history.js',
            ['jquery'],
            AICA_VERSION,
            true
        );
        
        // Przekaż dane do JS
        wp_localize_script('aica-history', 'aica_history', [
            'nonce' => wp_create_nonce('aica_history_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => [
                'load_more' => __('Pokaż więcej wiadomości', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'loading_messages' => __('Ładowanie wiadomości...', 'ai-chat-assistant'),
                'load_error' => __('Wystąpił błąd podczas ładowania wiadomości.', 'ai-chat-assistant'),
                'no_messages' => __('Ta rozmowa nie zawiera żadnych wiadomości.', 'ai-chat-assistant'),
                'delete_error' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant'),
                'no_conversations' => __('Nie znaleziono żadnych rozmów', 'ai-chat-assistant'),
                'no_conversations_desc' => __('Nie przeprowadzono jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'),
                'new_conversation' => __('Rozpocznij nową rozmowę', 'ai-chat-assistant'),
                'min_search_length' => __('Wyszukiwana fraza musi zawierać co najmniej 3 znaki.', 'ai-chat-assistant'),
                'continue_conversation' => __('Kontynuuj rozmowę', 'ai-chat-assistant'),
                'duplicate' => __('Duplikuj', 'ai-chat-assistant'),
                'export' => __('Eksportuj', 'ai-chat-assistant'),
                'delete' => __('Usuń', 'ai-chat-assistant'),
                'expand' => __('Rozwiń', 'ai-chat-assistant'),
                'menu' => __('Menu', 'ai-chat-assistant'),
                'user' => __('Użytkownik', 'ai-chat-assistant'),
                'export_error' => __('Wystąpił błąd podczas eksportowania rozmowy.', 'ai-chat-assistant'),
                'duplicate_error' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant'),
                'duplicate_success' => __('Rozmowa została zduplikowana pomyślnie.', 'ai-chat-assistant'),
                'pagination_info' => __('Wyświetlanie %1$s do %2$s z %3$s rozmów', 'ai-chat-assistant'),
                'year' => __('rok', 'ai-chat-assistant'),
                'years' => __('lat', 'ai-chat-assistant'),
                'month' => __('miesiąc', 'ai-chat-assistant'),
                'months' => __('miesięcy', 'ai-chat-assistant'),
                'day' => __('dzień', 'ai-chat-assistant'),
                'days' => __('dni', 'ai-chat-assistant'),
                'hour' => __('godzina', 'ai-chat-assistant'),
                'hours' => __('godzin', 'ai-chat-assistant'),
                'minute' => __('minuta', 'ai-chat-assistant'),
                'minutes' => __('minut', 'ai-chat-assistant'),
                'second' => __('sekunda', 'ai-chat-assistant'),
                'seconds' => __('sekund', 'ai-chat-assistant'),
                'ago' => __('temu', 'ai-chat-assistant'),
                'just_now' => __('przed chwilą', 'ai-chat-assistant'),
                'confirm_delete' => __('Czy na pewno chcesz usunąć tę rozmowę?', 'ai-chat-assistant'),
                'confirm_delete_all' => __('Czy na pewno chcesz usunąć wszystkie wybrane rozmowy?', 'ai-chat-assistant'),
                'no_selected' => __('Nie wybrano żadnej rozmowy do usunięcia', 'ai-chat-assistant')
            ]
        ]);
    }
    
    /**
     * Rejestracja skryptów i stylów dla strony repozytoriów
     */
    public function enqueue_repositories_assets($hook) {
        // Ładuj assety tylko na stronie repozytoriów
        if ($hook != 'ai-chat-assistant_page_ai-chat-assistant-repositories') {
            return;
        }
        
        // Style CSS
        wp_enqueue_style(
            'aica-repositories',
            AICA_PLUGIN_URL . 'assets/css/repositories.css',
            [],
            AICA_VERSION
        );
        
        // Skrypty JavaScript
        wp_enqueue_script(
            'aica-repositories',
            AICA_PLUGIN_URL . 'assets/js/repositories.js',
            ['jquery'],
            AICA_VERSION,
            true
        );
        
        // Dodaj Prism.js dla podświetlania składni kodu
        wp_enqueue_style(
            'prism-css',
            AICA_PLUGIN_URL . 'assets/vendor/prism/prism.css',
            [],
            AICA_VERSION
        );
        
        wp_enqueue_script(
            'prism-js',
            AICA_PLUGIN_URL . 'assets/vendor/prism/prism.js',
            [],
            AICA_VERSION,
            true
        );
        
        // Przekaż dane do JS
        wp_localize_script('aica-repositories', 'aica_repos', [
            'nonce' => wp_create_nonce('aica_repository_nonce'),
            'settings_url' => admin_url('admin.php?page=ai-chat-assistant-settings'),
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => [
                'load_error' => __('Nie udało się załadować danych repozytorium.', 'ai-chat-assistant'),
                'load_file_error' => __('Nie udało się załadować zawartości pliku.', 'ai-chat-assistant'),
                'copy_success' => __('Zawartość pliku została skopiowana do schowka.', 'ai-chat-assistant'),
                'delete_error' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant'),
                'refresh_error' => __('Nie udało się odświeżyć metadanych repozytorium.', 'ai-chat-assistant'),
                'refresh_success' => __('Metadane repozytorium zostały zaktualizowane.', 'ai-chat-assistant'),
                'add_error' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant'),
                'no_sources_configured' => __('Nie skonfigurowano żadnego źródła repozytoriów. Przejdź do ustawień, aby dodać tokeny dostępu.', 'ai-chat-assistant'),
                'adding' => __('Dodawanie...', 'ai-chat-assistant'),
                'add' => __('Dodaj', 'ai-chat-assistant'),
                'no_repositories' => __('Nie masz zapisanych repozytoriów', 'ai-chat-assistant'),
                'no_repositories_desc' => __('Dodaj repozytoria z serwisów GitHub, GitLab lub Bitbucket, aby ułatwić sobie pracę z kodem podczas rozmów z Claude.', 'ai-chat-assistant'),
                'add_repository' => __('Dodaj repozytorium', 'ai-chat-assistant')
            ]
        ]);
    }
}