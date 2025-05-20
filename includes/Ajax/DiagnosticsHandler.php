<?php
namespace AICA\Ajax;

class DiagnosticsHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla diagnostyki
        add_action('wp_ajax_aica_repair_database', [$this, 'repair_database']);
        add_action('wp_ajax_aica_test_api_connection_diagnostics', [$this, 'test_api_connection_diagnostics']);
    }
    
    /**
     * Naprawa bazy danych
     */
    public function repair_database() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_diagnostics_nonce')) {
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
        
        // Wywołanie funkcji instalującej tabele
        require_once AICA_PLUGIN_DIR . 'includes/Installer.php';
        $installer = new \AICA\Installer();
        $result = $installer->install_tables();
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Pomyślnie naprawiono tabele bazy danych.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Wystąpił błąd podczas naprawy bazy danych.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API dla diagnostyki
     */
    public function test_api_connection_diagnostics() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_diagnostics_nonce')) {
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
                $this->test_claude_connection_diagnostics();
                break;
            case 'github':
                $this->test_github_connection_diagnostics();
                break;
            case 'gitlab':
                $this->test_gitlab_connection_diagnostics();
                break;
            case 'bitbucket':
                $this->test_bitbucket_connection_diagnostics();
                break;
            default:
                wp_send_json_error([
                    'message' => __('Nieznany typ API.', 'ai-chat-assistant')
                ]);
        }
    }
    
    /**
     * Testowanie połączenia z API Claude dla diagnostyki
     */
    private function test_claude_connection_diagnostics() {
        // Używamy klucza API z opcji wtyczki
        $api_key = aica_get_option('claude_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $result = $claude_client->test_connection();
        
        if ($result) {
            // Pobierz modele Claude
            $models = $claude_client->get_available_models();
            
            wp_send_json_success([
                'message' => __('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant'),
                'models' => $models
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Testowanie połączenia z API GitHub dla diagnostyki
     */
    private function test_github_connection_diagnostics() {
        // Używamy tokenu GitHub z opcji wtyczki
        $token = aica_get_option('github_token', '');
        
        if (empty($token)) {
            wp_send_json_error([
                'message' => __('Token GitHub nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
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
     * Testowanie połączenia z API GitLab dla diagnostyki
     */
    private function test_gitlab_connection_diagnostics() {
        // Używamy tokenu GitLab z opcji wtyczki
        $token = aica_get_option('gitlab_token', '');
        
        if (empty($token)) {
            wp_send_json_error([
                'message' => __('Token GitLab nie jest ustawiony.', 'ai-chat-assistant')
            ]);
        }
        
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
     * Testowanie połączenia z API Bitbucket dla diagnostyki
     */
    private function test_bitbucket_connection_diagnostics() {
        // Używamy danych Bitbucket z opcji wtyczki
        $username = aica_get_option('bitbucket_username', '');
        $password = aica_get_option('bitbucket_app_password', '');
        
        if (empty($username) || empty($password)) {
            wp_send_json_error([
                'message' => __('Dane logowania Bitbucket nie są ustawione.', 'ai-chat-assistant')
            ]);
        }
        
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