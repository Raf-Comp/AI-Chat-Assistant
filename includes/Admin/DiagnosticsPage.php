<?php
namespace AICA\Admin;

class DiagnosticsPage {
    public function render() {
        $claude_api_status = $this->check_claude_api();
        $github_api_status = $this->check_github_api();
        $gitlab_api_status = $this->check_gitlab_api();
        $bitbucket_api_status = $this->check_bitbucket_api();
        $database_status = $this->get_database_status();
        $files_permissions = $this->get_files_permissions();
        $recommendations = $this->get_recommendations($database_status, $files_permissions, $claude_api_status, $github_api_status, $gitlab_api_status, $bitbucket_api_status);

        include AICA_PLUGIN_DIR . 'templates/admin/diagnostics.php';
    }

    private function check_claude_api() {
        $api_key = get_option('aica_claude_api_key', '');
        if (empty($api_key)) {
            return ['valid' => false, 'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $test_result = $claude_client->test_connection();

        if (!$test_result) {
            return ['valid' => false, 'message' => __('Połączenie z API Claude nie działa.', 'ai-chat-assistant')];
        }

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

    private function check_github_api() {
        $token = get_option('aica_github_token', '');
        if (empty($token)) {
            return ['valid' => false, 'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        $client = new \AICA\API\GitHubClient($token);
        $result = $client->test_connection();

        return $result ? ['valid' => true] : ['valid' => false, 'message' => __('Połączenie z API GitHub nie działa.', 'ai-chat-assistant')];
    }

    private function check_gitlab_api() {
        $token = get_option('aica_gitlab_token', '');
        if (empty($token)) {
            return ['valid' => false, 'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        $response = wp_remote_get('https://gitlab.com/api/v4/user', [
            'headers' => ['PRIVATE-TOKEN' => $token],
            'timeout' => 10
        ]);

        $code = wp_remote_retrieve_response_code($response);
        return [
            'valid' => $code === 200,
            'message' => $code === 200 ? __('Połączenie z GitLab API działa.', 'ai-chat-assistant') : __('Błąd połączenia z GitLab API (kod: ', 'ai-chat-assistant') . $code . ')'
        ];
    }

    private function check_bitbucket_api() {
        $token = get_option('aica_bitbucket_token', '');
        if (empty($token)) {
            return ['valid' => false, 'message' => __('Token Bitbucket nie jest skonfigurowany.', 'ai-chat-assistant')];
        }

        $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 10
        ]);

        $code = wp_remote_retrieve_response_code($response);
        return [
            'valid' => $code === 200,
            'message' => $code === 200 ? __('Połączenie z Bitbucket API działa.', 'ai-chat-assistant') : __('Błąd połączenia z Bitbucket API (kod: ', 'ai-chat-assistant') . $code . ')'
        ];
    }

    private function get_database_status() {
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $prefix = $wpdb->prefix . 'aica_';
        $status = [];

        foreach ($tables as $table) {
            if (strpos($table, $prefix) === 0) {
                $records = $wpdb->get_var("SELECT COUNT(*) FROM `$table`");
                $status[$table] = [
                    'name' => str_replace($wpdb->prefix, '', $table),
                    'exists' => true,
                    'records' => (int) $records
                ];
            }
        }

        return $status;
    }

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

    private function get_recommendations($db_status, $files_status, $claude_api, $github_api, $gitlab_api, $bitbucket_api) {
        $recommendations = [];

        if (empty($db_status)) {
            $recommendations[] = __('Brakuje wymaganych tabel w bazie danych.', 'ai-chat-assistant');
        }

        foreach ($files_status as $file => $status) {
            if (!$status['exists'] || !$status['readable']) {
                $recommendations[] = __('Plik niedostępny lub nieczytelny: ', 'ai-chat-assistant') . $file;
            }
        }

        if (!$claude_api['valid']) {
            $recommendations[] = __('Skonfiguruj poprawnie klucz API Claude.', 'ai-chat-assistant');
        } elseif (!empty($claude_api['details']) && !$claude_api['details']['model_available']) {
            $recommendations[] = __('Wybrany model Claude nie jest dostępny. Zmień model w ustawieniach wtyczki.', 'ai-chat-assistant');
        }

        if (!$github_api['valid']) {
            $recommendations[] = __('Skonfiguruj token GitHub.', 'ai-chat-assistant');
        }

        if (!$gitlab_api['valid']) {
            $recommendations[] = __('Skonfiguruj token GitLab.', 'ai-chat-assistant');
        }

        if (!$bitbucket_api['valid']) {
            $recommendations[] = __('Skonfiguruj token Bitbucket.', 'ai-chat-assistant');
        }

        return $recommendations;
    }
}