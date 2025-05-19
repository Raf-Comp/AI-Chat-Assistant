<?php
namespace AICA\Admin;

class DiagnosticsPage {
    /**
     * Renderowanie strony diagnostyki
     */
    public function render() {
        // Tutaj możesz pobrać dane diagnostyczne, które chcesz wyświetlić
        $claude_api_status = $this->check_claude_api();
        $github_api_status = $this->check_github_api();
        $database_status = $this->get_database_status();
        $files_permissions = $this->get_files_permissions();
        $recommendations = $this->get_recommendations($database_status, $files_permissions, $claude_api_status, $github_api_status);
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/diagnostics.php';
    }
    
    /**
     * Sprawdzenie statusu API Claude
     */
    private function check_claude_api() {
        $api_key = get_option('aica_claude_api_key', '');
        
        if (empty($api_key)) {
            return [
                'valid' => false,
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
            ];
        }
        
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $test_result = $claude_client->test_connection();
        
        if (!$test_result) {
            return [
                'valid' => false,
                'message' => __('Połączenie z API Claude nie działa.', 'ai-chat-assistant')
            ];
        }
        
        // Pobranie dostępnych modeli
        $models = $claude_client->get_available_models();
        $current_model = get_option('aica_claude_model', 'claude-3-haiku-20240307');
        
        return [
            'valid' => true,
            'details' => [
                'current_model' => $current_model,
                'model_available' => in_array($current_model, array_keys($models)),
                'models' => array_keys($models)
            ]
        ];
    }
    
    /**
     * Sprawdzenie statusu API GitHub
     */
    private function check_github_api() {
        $github_token = get_option('aica_github_token', '');
        
        if (empty($github_token)) {
            return [
                'valid' => false,
                'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
            ];
        }
        
        $github_client = new \AICA\API\GitHubClient($github_token);
        $test_result = $github_client->test_connection();
        
        if (!$test_result) {
            return [
                'valid' => false,
                'message' => __('Połączenie z API GitHub nie działa.', 'ai-chat-assistant')
            ];
        }
        
        return [
            'valid' => true
        ];
    }
    
    /**
     * Sprawdzenie statusu bazy danych
     */
    private function get_database_status() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'aica_sessions' => __('Sesje czatu', 'ai-chat-assistant'),
            $wpdb->prefix . 'aica_messages' => __('Wiadomości czatu', 'ai-chat-assistant'),
            $wpdb->prefix . 'aica_repositories' => __('Repozytoria', 'ai-chat-assistant')
        ];
        
        $status = [];
        
        foreach ($tables as $table => $name) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            
            $records = 0;
            if ($exists) {
                $records = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            }
            
            $status[$table] = [
                'name' => $name,
                'exists' => $exists,
                'records' => $records
            ];
        }
        
        return $status;
    }
    
    /**
     * Sprawdzenie uprawnień plików
     */
    private function get_files_permissions() {
        $files = [
            AICA_PLUGIN_DIR . 'ai-chat-assistant.php' => __('Plik główny wtyczki', 'ai-chat-assistant'),
            AICA_PLUGIN_DIR . 'includes/Main.php' => __('Klasa główna', 'ai-chat-assistant'),
            AICA_PLUGIN_DIR . 'includes/Installer.php' => __('Instalator', 'ai-chat-assistant'),
            AICA_PLUGIN_DIR . 'includes/API/ClaudeClient.php' => __('Klient Claude API', 'ai-chat-assistant')
        ];
        
        $status = [];
        
        foreach ($files as $file => $name) {
            $exists = file_exists($file);
            $readable = $exists ? is_readable($file) : false;
            $writable = $exists ? is_writable($file) : false;
            $permissions = $exists ? substr(sprintf('%o', fileperms($file)), -4) : '';
            
            $status[$file] = [
                'name' => $name,
                'exists' => $exists,
                'readable' => $readable,
                'writable' => $writable,
                'permissions' => $permissions
            ];
        }
        
        return $status;
    }
    
    /**
     * Generowanie rekomendacji na podstawie diagnostyki
     */
    private function get_recommendations($db_status, $files_status, $claude_api, $github_api) {
        $recommendations = [];
        
        // Sprawdzenie bazy danych
        $db_issues = false;
        foreach ($db_status as $table => $status) {
            if (!$status['exists']) {
                $db_issues = true;
                break;
            }
        }
        
        if ($db_issues) {
            $recommendations[] = __('Napraw tabele bazy danych, klikając przycisk "Napraw tabele bazy danych".', 'ai-chat-assistant');
        }
        
        // Sprawdzenie plików
        $file_issues = false;
        foreach ($files_status as $file => $status) {
            if (!$status['exists'] || !$status['readable']) {
                $file_issues = true;
                break;
            }
        }
        
        if ($file_issues) {
            $recommendations[] = __('Niektóre pliki wtyczki są niedostępne lub nieczytelne. Rozważ ponowną instalację wtyczki.', 'ai-chat-assistant');
        }
        
        // Sprawdzenie API Claude
        if (!$claude_api['valid']) {
            $recommendations[] = __('Skonfiguruj prawidłowy klucz API Claude w ustawieniach wtyczki.', 'ai-chat-assistant');
        } elseif (isset($claude_api['details']['model_available']) && !$claude_api['details']['model_available']) {
            $recommendations[] = __('Wybrany model Claude nie jest dostępny. Zmień model w ustawieniach wtyczki.', 'ai-chat-assistant');
        }
        
        // Sprawdzenie API GitHub (opcjonalne)
        if (!$github_api['valid']) {
            $recommendations[] = __('Rozważ skonfigurowanie tokena GitHub, aby zwiększyć limit zapytań do API GitHub.', 'ai-chat-assistant');
        }
        
        return $recommendations;
    }
}