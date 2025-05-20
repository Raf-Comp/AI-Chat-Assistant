<?php
namespace AICA\Ajax;

class SettingsHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla ustawień
        add_action('wp_ajax_aica_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_aica_test_api_connection', [$this, 'test_api_connection']);
        add_action('wp_ajax_aica_refresh_models', [$this, 'refresh_models']);
    }
    
    /**
     * Zapisywanie ustawień
     */
    public function save_settings() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_settings_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')
            ]);
        }
        
        // Przygotowanie opcji do zapisania
        $options = [
            'claude_api_key' => sanitize_text_field($_POST['aica_claude_api_key'] ?? ''),
            'claude_model' => sanitize_text_field($_POST['aica_claude_model'] ?? 'claude-3-haiku-20240307'),
            'max_tokens' => intval($_POST['aica_max_tokens'] ?? 4000),
            'temperature' => floatval($_POST['aica_temperature'] ?? 0.7),
            
            'github_token' => sanitize_text_field($_POST['aica_github_token'] ?? ''),
            'gitlab_token' => sanitize_text_field($_POST['aica_gitlab_token'] ?? ''),
            
            'bitbucket_username' => sanitize_text_field($_POST['aica_bitbucket_username'] ?? ''),
            'bitbucket_app_password' => sanitize_text_field($_POST['aica_bitbucket_app_password'] ?? ''),
            
            'allowed_file_extensions' => sanitize_text_field($_POST['aica_allowed_file_extensions'] ?? 'txt,pdf,php,js,css,html,json,md'),
            
            'auto_purge_enabled' => isset($_POST['aica_auto_purge_enabled']) ? 1 : 0,
            'auto_purge_days' => intval($_POST['aica_auto_purge_days'] ?? 30)
        ];
        
        // Zapisz opcje
        foreach ($options as $key => $value) {
            aica_update_option($key, $value);
        }
        
        // Zapisz szablony promptów
        if (isset($_POST['aica_templates']) && is_array($_POST['aica_templates'])) {
            $templates = [];
            
            foreach ($_POST['aica_templates'] as $template) {
                if (!empty($template['name']) && !empty($template['prompt'])) {
                    $templates[] = [
                        'name' => sanitize_text_field($template['name']),
                        'prompt' => sanitize_textarea_field($template['prompt'])
                    ];
                }
            }
            
            aica_update_option('templates', $templates);
        }
        
        wp_send_json_success([
            'message' => __('Ustawienia zostały zapisane.', 'ai-chat-assistant')
        ]);
    }
    
    /**
     * Testowanie połączenia z API
     */
    public function test_api_connection() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_settings_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie typu API
        if (!isset($_POST['api_type'])) {
            wp_send_json_error([
                'message' => __('Nie określono typu API.', 'ai-chat-assistant')
            ]);
        }
        
        $api_type = sanitize_text_field($_POST['api_type']);
        
        switch ($api_type) {
            case 'claude':
                $this->test_claude_connection();
                break;
            case 'github':
                $this->test_github_connection();
                break;
            case 'gitlab':
                $this->test_gitlab_connection();
                break;
            case 'bitbucket':
                $this->test_bitbucket_connection();
                break;
            default:
                wp_send_json_error([
                    'message' => __('Nieznany typ API.', 'ai-chat-assistant')
                ]);
        }
    }
    
    /**
     * Odświeżanie listy modeli Claude
     */
    public function refresh_models() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_settings_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz klucz API
        $api_key = aica_get_option('claude_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
        // Utwórz klienta Claude
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        
        // Pobierz modele
        $models = $claude_client->get_available_models();
        
        if (empty($models)) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać listy modeli Claude.', 'ai-chat-assistant')
            ]);
        }
        
        // Zapisz modele w opcjach
        aica_update_option('claude_available_models', $models);
        aica_update_option('claude_models_last_update', current_time('mysql'));
        
        wp_send_json_success([
            'message' => __('Lista modeli została zaktualizowana.', 'ai-chat-assistant'),
            'models' => $models
        ]);
    }
    
    /**
     * Testowanie połączenia z API Claude
     */
    private function test_claude_connection() {
        if (!isset($_POST['api_key']) || empty($_POST['api_key'])) {
            wp_send_json_error([
                'message' => __('Nie podano klucza API.', 'ai-chat-assistant')
            ]);
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        
        $result = $claude_client->test_connection();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API GitHub
     */
    private function test_github_connection() {
        if (!isset($_POST['api_key']) || empty($_POST['api_key'])) {
            wp_send_json_error([
                'message' => __('Nie podano tokenu GitHub.', 'ai-chat-assistant')
            ]);
        }
        
        $token = sanitize_text_field($_POST['api_key']);
        $github_client = new \AICA\API\GitHubClient($token);
        
        $result = $github_client->test_connection();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Połączenie z API GitHub działa poprawnie.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się połączyć z API GitHub. Sprawdź token i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API GitLab
     */
    private function test_gitlab_connection() {
        if (!isset($_POST['api_key']) || empty($_POST['api_key'])) {
            wp_send_json_error([
                'message' => __('Nie podano tokenu GitLab.', 'ai-chat-assistant')
            ]);
        }
        
        $token = sanitize_text_field($_POST['api_key']);
        $gitlab_client = new \AICA\API\GitLabClient($token);
        
        $result = $gitlab_client->test_connection();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Połączenie z API GitLab działa poprawnie.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się połączyć z API GitLab. Sprawdź token i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API Bitbucket
     */
    private function test_bitbucket_connection() {
        if (!isset($_POST['username']) || empty($_POST['username']) || 
            !isset($_POST['password']) || empty($_POST['password'])) {
            wp_send_json_error([
                'message' => __('Nie podano nazwy użytkownika lub hasła aplikacji Bitbucket.', 'ai-chat-assistant')
            ]);
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        
        $bitbucket_client = new \AICA\API\BitbucketClient($username, $password);
        
        $result = $bitbucket_client->test_connection();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Połączenie z API Bitbucket działa poprawnie.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się połączyć z API Bitbucket. Sprawdź dane logowania i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
    }
}