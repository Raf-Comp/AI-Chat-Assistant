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

        // Skrypt czatu
        wp_enqueue_script(
            'aica-chat',
            AICA_PLUGIN_URL . 'assets/js/chat.js',
            ['jquery', 'prism'],
            AICA_VERSION,
            true
        );

        // Style czatu
        wp_enqueue_style(
            'aica-chat',
            AICA_PLUGIN_URL . 'assets/css/chat.css',
            ['prism'],
            AICA_VERSION
        );

        // Dane dla skryptu
        $current_user_id = get_current_user_id();
        $chat_sessions = $this->chat_service->get_user_sessions($current_user_id);
        $repositories = $this->repo_service->get_saved_repositories($current_user_id);

        wp_localize_script('aica-chat', 'aica_chat_data', [
            'sessions' => $chat_sessions,
            'repositories' => $repositories,
            'settings' => [
                'claude_model' => get_option('aica_claude_model', 'claude-3-haiku-20240307'),
                'max_tokens' => get_option('aica_max_tokens', 4000),
            ]
        ]);
    }

    public function render() {
        // Sprawdzenie, czy klucz API Claude jest skonfigurowany
        $claude_api_key = get_option('aica_claude_api_key', '');
        $api_configured = !empty($claude_api_key);

        // Języki programowania wspierane przez podświetlanie składni
        $supported_languages = [
            'php', 'javascript', 'css', 'html', 'json', 'python',
            'ruby', 'java', 'c', 'cpp', 'csharp', 'go', 'rust'
        ];

        include AICA_PLUGIN_DIR . 'templates/admin/chat.php';
    }
}