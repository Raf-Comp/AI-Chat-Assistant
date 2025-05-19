<?php
namespace AICA\Admin;

class SettingsPage {
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Rejestracja ustawień
     */
    public function register_settings() {
        // Ustawienia Claude API
        register_setting('aica_options', 'aica_claude_api_key');
        register_setting('aica_options', 'aica_claude_model');
        register_setting('aica_options', 'aica_max_tokens', 'intval');
        
        // Ustawienia GitHub
        register_setting('aica_options', 'aica_github_token');
        
        // Ustawienia GitLab
        register_setting('aica_options', 'aica_gitlab_token');
        
        // Ustawienia Bitbucket
        register_setting('aica_options', 'aica_bitbucket_username');
        register_setting('aica_options', 'aica_bitbucket_app_password');
        
        // Inne ustawienia
        register_setting('aica_options', 'aica_allowed_file_extensions');
    }
    
    /**
     * Renderowanie strony ustawień
     */
    public function render() {
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Zapisywanie ustawień
        if (isset($_POST['aica_save_settings']) && check_admin_referer('aica_settings_nonce')) {
            // Ustawienia są zapisywane przez WordPress API
            echo '<div class="notice notice-success"><p>' . __('Ustawienia zostały zapisane.', 'ai-chat-assistant') . '</p></div>';
        }
        
        // Testowanie połączenia z API
        if (isset($_POST['aica_test_claude_api']) && check_admin_referer('aica_settings_nonce')) {
            $api_key = get_option('aica_claude_api_key', '');
            $client = new \AICA\API\ClaudeClient($api_key);
            $test_result = $client->test_connection();
            
            if ($test_result) {
                echo '<div class="notice notice-success"><p>' . __('Połączenie z API Claude jest poprawne.', 'ai-chat-assistant') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Nie można połączyć się z API Claude. Sprawdź klucz API.', 'ai-chat-assistant') . '</p></div>';
            }
        }
        
        // Tworzenie instancji klienta Claude do pobrania dostępnych modeli
        $api_key = get_option('aica_claude_api_key', '');
        $claude_client = null;
        $available_models = [
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku'
        ];
        
        if (!empty($api_key)) {
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $models = $claude_client->get_available_models();
            if (!empty($models)) {
                $available_models = $models;
            }
        }
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}