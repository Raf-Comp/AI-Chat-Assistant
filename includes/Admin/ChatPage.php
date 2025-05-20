<?php
namespace AICA\Admin;

use AICA\Services\ChatService;
use AICA\Services\RepositoryService;

class ChatPage {
    private $chat_service;
    private $repo_service;

    public function __construct() {
        $this->chat_service = new ChatService();
        $this->repo_service = new RepositoryService();
        
        // Dodanie stylów i skryptów specyficznych dla strony czatu
        add_action('admin_enqueue_scripts', [$this, 'enqueue_chat_assets']);
    }

    public function enqueue_chat_assets($hook) {
        if ($hook != 'toplevel_page_ai-chat-assistant') {
            return;
        }

        // Załadowanie Prism.js do podświetlania składni
        wp_enqueue_style(
            'prism',
            AICA_PLUGIN_URL . 'assets/css/vendor/prism.min.css',
            [],
            AICA_VERSION
        );

        wp_enqueue_script(
            'prism',
            AICA_PLUGIN_URL . 'assets/js/vendor/prism.min.js',
            [],
            AICA_VERSION,
            true
        );

        // Nowoczesny styl czatu
        wp_enqueue_style(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/css/modern-chat.css',
            ['prism'],
            AICA_VERSION
        );

        // Nowoczesny skrypt czatu
        wp_enqueue_script(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/js/modern-chat.js',
            ['jquery', 'prism'],
            AICA_VERSION,
            true
        );

        // Załadowanie dashicons z WP
        wp_enqueue_style('dashicons');

        // Pobierz dostępne modele z ustawień
        $available_models = aica_get_option('claude_available_models', []);
        if (empty($available_models)) {
            // Domyślna lista modeli, jeśli nie ma zapisanych
            $available_models = [
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
            ];
        }

        // Dane dla skryptu
        $current_user_id = get_current_user_id();
        $chat_sessions = $this->chat_service->get_user_sessions($current_user_id);
        $repositories = $this->repo_service->get_saved_repositories($current_user_id);

        wp_localize_script('aica-modern-chat', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_nonce'),
            'sessions' => $chat_sessions,
            'repositories' => $repositories,
            'settings' => [
                'claude_model' => get_option('aica_claude_model', 'claude-3-haiku-20240307'),
                'max_tokens' => get_option('aica_max_tokens', 4000),
                'available_models' => $available_models
            ]
        ]);
    }

    public function render() {
        // Sprawdzenie, czy klucz API Claude jest skonfigurowany
        $claude_api_key = aica_get_option('claude_api_key', '');
        $api_configured = !empty($claude_api_key);

        // Pobierz aktualnie wybrany model
        $current_model = aica_get_option('claude_model', 'claude-3-haiku-20240307');
        
        // Pobierz dostępne modele z ustawień
        $available_models = aica_get_option('claude_available_models', []);
        if (empty($available_models)) {
            // Domyślna lista modeli, jeśli nie ma zapisanych
            $available_models = [
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
            ];
        }

        // Języki programowania wspierane przez podświetlanie składni
        $supported_languages = [
            'php', 'javascript', 'css', 'html', 'json', 'python',
            'ruby', 'java', 'c', 'cpp', 'csharp', 'go', 'rust'
        ];

        include AICA_PLUGIN_DIR . 'templates/admin/chat.php';
    }
}