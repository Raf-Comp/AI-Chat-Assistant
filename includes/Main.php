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
    }

    public function run() {
        // Rejestracja menu administratora
        add_action('admin_menu', [$this->admin_pages, 'register_menu']);
        
        // Inicjalizacja handlera AJAX
        $this->ajax_handler->init();
        
        // Dodajemy hook inicjalizujący użytkownika przy pierwszym ładowaniu wtyczki
        add_action('init', [$this, 'initialize_current_user']);
        
        // Dodajemy hook do rejestracji styli i skryptów
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
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
                'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
            ]
        ]);
    }
}