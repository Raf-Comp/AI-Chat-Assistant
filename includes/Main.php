<?php
namespace AICA;

class Main {
    private $ajax_handler;
    private $admin_pages;

    public function __construct() {
        // Upewnij się, że te klasy istnieją w odpowiednich katalogach
        $this->ajax_handler = new \AICA\Ajax\Handler();
        $this->admin_pages = new \AICA\Admin\PageManager();
    }

    public function run() {
        // Rejestracja menu administratora
        add_action('admin_menu', [$this->admin_pages, 'register_menu']);
        
        // Rejestracja zasobów (CSS, JS)
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        
        // Inicjalizacja handlera AJAX
        $this->ajax_handler->init();
    }

    public function register_assets($hook) {
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
            'nonce' => wp_create_nonce('aica_nonce'),
            'i18n' => [
                'error' => __('Błąd', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'sending' => __('Wysyłanie...', 'ai-chat-assistant')
            ]
        ]);
    }
}