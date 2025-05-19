<?php
namespace AICA\Ajax;

use AICA\Services\ChatService;
use AICA\Services\RepositoryService;
use AICA\Services\FileService;
use AICA\API\ClaudeClient;
use AICA\API\GitHubClient;
use AICA\API\GitLabClient;
use AICA\API\BitbucketClient;

class Handler {
    public function init() {
        // Rejestracja punktów końcowych AJAX
        add_action('wp_ajax_aica_create_session', [$this, 'create_session']);
        add_action('wp_ajax_aica_send_message', [$this, 'send_message']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'get_chat_history']);
        add_action('wp_ajax_aica_upload_file', [$this, 'upload_file']);
        add_action('wp_ajax_aica_get_repositories', [$this, 'get_repositories']);
        add_action('wp_ajax_aica_get_repository_details', [$this, 'get_repository_details']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'get_repository_files']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'refresh_repository']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'get_file_content']);
        add_action('wp_ajax_aica_search_repository', [$this, 'search_repository']);
        add_action('wp_ajax_aica_delete_session', [$this, 'delete_session']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'delete_repository']);
        add_action('wp_ajax_aica_test_api_connection', [$this, 'test_api_connection']);
        add_action('wp_ajax_aica_test_gitlab_api', [$this, 'test_gitlab_api']);
        add_action('wp_ajax_aica_test_bitbucket_api', [$this, 'test_bitbucket_api']);

        
        // Obsługa zapisu ustawień
        add_action('admin_post_save_aica_settings', [$this, 'save_settings']);
    }

    /**
     * Testowanie połączenia z API (AJAX)
     */
    public function test_api_connection() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_settings_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : '';
        
        if (empty($api_type)) {
            wp_send_json_error(['message' => __('Brakujący typ API.', 'ai-chat-assistant')]);
            exit;
        }
        
        // Testowanie odpowiedniego API
        switch ($api_type) {
            case 'claude':
                $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
                
                if (empty($api_key)) {
                    wp_send_json_error(['message' => __('Brakujący klucz API.', 'ai-chat-assistant')]);
                    exit;
                }

                // Testowanie połączenia
                $claude_client = new ClaudeClient($api_key);
                $test_result = $claude_client->test_connection();

                if ($test_result) {
                    wp_send_json_success(['message' => __('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant')]);
                } else {
                    wp_send_json_error(['message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API.', 'ai-chat-assistant')]);
                }
                break;
                
            case 'github':
                $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
                
                if (empty($api_key)) {
                    wp_send_json_error(['message' => __('Brakujący token GitHub.', 'ai-chat-assistant')]);
                    exit;
                }

                // Testowanie połączenia
                $github_client = new GitHubClient($api_key);
                $test_result = $github_client->test_connection();

                if ($test_result) {
                    wp_send_json_success(['message' => __('Połączenie z API GitHub działa poprawnie.', 'ai-chat-assistant')]);
                } else {
                    wp_send_json_error(['message' => __('Nie udało się połączyć z API GitHub. Sprawdź token.', 'ai-chat-assistant')]);
                }
                break;
                
            case 'gitlab':
                $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
                
                if (empty($api_key)) {
                    wp_send_json_error(['message' => __('Brakujący token GitLab.', 'ai-chat-assistant')]);
                    exit;
                }

                // Testowanie połączenia
                $gitlab_client = new GitLabClient($api_key);
                $test_result = $gitlab_client->test_connection();

                if ($test_result) {
                    wp_send_json_success(['message' => __('Połączenie z API GitLab działa poprawnie.', 'ai-chat-assistant')]);
                } else {
                    wp_send_json_error(['message' => __('Nie udało się połączyć z API GitLab. Sprawdź token.', 'ai-chat-assistant')]);
                }
                break;
                
            case 'bitbucket':
                $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
                $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
                
                if (empty($username) || empty($password)) {
                    wp_send_json_error(['message' => __('Brakujące dane uwierzytelniające Bitbucket.', 'ai-chat-assistant')]);
                    exit;
                }

                // Testowanie połączenia
                $bitbucket_client = new BitbucketClient($username, $password);
                $test_result = $bitbucket_client->test_connection();

                if ($test_result) {
                    wp_send_json_success(['message' => __('Połączenie z API Bitbucket działa poprawnie.', 'ai-chat-assistant')]);
                } else {
                    wp_send_json_error(['message' => __('Nie udało się połączyć z API Bitbucket. Sprawdź dane.', 'ai-chat-assistant')]);
                }
                break;
                
            default:
                wp_send_json_error(['message' => __('Nieznany typ API.', 'ai-chat-assistant')]);
                break;
        }
        
        exit;
    }
    
    /**
     * Zapisywanie ustawień
     */
    public function save_settings() {
        // Weryfikacja nonce
        if (!isset($_POST['aica_settings_nonce']) || !wp_verify_nonce($_POST['aica_settings_nonce'], 'aica_settings_nonce')) {
            wp_die(__('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant'));
        }
        
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz uprawnień do zmiany ustawień.', 'ai-chat-assistant'));
        }
        
        // Pobieranie i sanityzacja danych z formularza
        $claude_api_key = isset($_POST['aica_claude_api_key']) ? sanitize_text_field($_POST['aica_claude_api_key']) : '';
        $claude_model = isset($_POST['aica_claude_model']) ? sanitize_text_field($_POST['aica_claude_model']) : 'claude-3-haiku-20240307';
        $max_tokens = isset($_POST['aica_max_tokens']) ? (int)$_POST['aica_max_tokens'] : 4000;
        $github_token = isset($_POST['aica_github_token']) ? sanitize_text_field($_POST['aica_github_token']) : '';
        $gitlab_token = isset($_POST['aica_gitlab_token']) ? sanitize_text_field($_POST['aica_gitlab_token']) : '';
        $bitbucket_username = isset($_POST['aica_bitbucket_username']) ? sanitize_text_field($_POST['aica_bitbucket_username']) : '';
        $bitbucket_app_password = isset($_POST['aica_bitbucket_app_password']) ? sanitize_text_field($_POST['aica_bitbucket_app_password']) : '';
        $allowed_file_extensions = isset($_POST['aica_allowed_file_extensions']) ? sanitize_text_field($_POST['aica_allowed_file_extensions']) : '';
        $debug_mode = isset($_POST['aica_debug_mode']) ? (bool)$_POST['aica_debug_mode'] : false;
        
        // Walidacja danych
        if ($max_tokens < 1000) {
            $max_tokens = 1000;
        } elseif ($max_tokens > 100000) {
            $max_tokens = 100000;
        }
        
        // Zapisywanie opcji
        aica_update_option('claude_api_key', $claude_api_key);
        aica_update_option('claude_model', $claude_model);
        aica_update_option('max_tokens', $max_tokens);
        aica_update_option('github_token', $github_token);
        aica_update_option('gitlab_token', $gitlab_token);
        aica_update_option('bitbucket_username', $bitbucket_username);
        aica_update_option('bitbucket_app_password', $bitbucket_app_password);
        aica_update_option('allowed_file_extensions', $allowed_file_extensions);
        aica_update_option('debug_mode', $debug_mode);
        
        // Logowanie informacji o zapisie ustawień
        aica_log('Zapisano ustawienia wtyczki');
        
        // Przekierowanie z powrotem do strony ustawień
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=ai-chat-assistant-settings')));
        exit;
    }

    public function create_session() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Utworzenie nowej sesji
        $chat_service = new ChatService();
        $session_id = $chat_service->create_session();

        if ($session_id) {
            wp_send_json_success(['session_id' => $session_id]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się utworzyć sesji.', 'ai-chat-assistant')]);
        }
        exit;
    }

    public function send_message() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        
        if (empty($session_id) || empty($message)) {
            wp_send_json_error(['message' => __('Brakujące parametry.', 'ai-chat-assistant')]);
            exit;
        }

        // Wysłanie wiadomości do Claude.ai
        $chat_service = new ChatService();
        $result = $chat_service->send_message($session_id, $message, $file_path);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        exit;
    }

    public function get_chat_history() {
        // Weryfikacja nonce
        $nonceKey = isset($_POST['nonce_key']) ? $_POST['nonce_key'] : 'aica_nonce';
        if (!check_ajax_referer($nonceKey, 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator sesji.', 'ai-chat-assistant')]);
            exit;
        }

        // Pobranie historii czatu
        $chat_service = new ChatService();
        $history_data = $chat_service->get_chat_history($session_id, $page, $per_page);

        wp_send_json_success($history_data);
        exit;
    }

    public function upload_file() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Sprawdzenie czy plik został przesłany
        if (empty($_FILES['file'])) {
            wp_send_json_error(['message' => __('Nie przesłano pliku.', 'ai-chat-assistant')]);
            exit;
        }

        // Obsługa przesłanego pliku
        $file_service = new FileService();
        $result = $file_service->handle_upload($_FILES['file']);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        exit;
    }

    public function get_repositories() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'github';
        
        // Pobranie repozytoriów
        $repo_service = new RepositoryService();
        $repositories = $repo_service->get_repositories($type);

        wp_send_json_success(['repositories' => $repositories]);
        exit;
    }

    /**
     * Pobieranie szczegółów repozytorium
     */
    public function get_repository_details() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator repozytorium.', 'ai-chat-assistant')]);
            exit;
        }

        // Pobranie szczegółów repozytorium
        $repo_service = new RepositoryService();
        $repository = $repo_service->get_repository($repo_id);

        if ($repository) {
            wp_send_json_success(['repository' => $repository]);
        } else {
            wp_send_json_error(['message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')]);
        }
        exit;
    }

    /**
     * Pobieranie plików z repozytorium
     */
    public function get_repository_files() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'main';
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator repozytorium.', 'ai-chat-assistant')]);
            exit;
        }

        // Pobranie plików
        $repo_service = new RepositoryService();
        $files = $repo_service->get_repository_files($repo_id, $path, $branch);

        wp_send_json_success(['files' => $files]);
        exit;
    }

    /**
     * Odświeżanie metadanych repozytorium
     */
    public function refresh_repository() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator repozytorium.', 'ai-chat-assistant')]);
            exit;
        }

        // Odświeżanie metadanych
        $repo_service = new RepositoryService();
        $result = $repo_service->refresh_repository($repo_id);

        if ($result) {
            wp_send_json_success(['message' => __('Metadane repozytorium zostały odświeżone.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się odświeżyć metadanych repozytorium.', 'ai-chat-assistant')]);
        }
        exit;
    }

    public function get_file_content() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        
        if (empty($repo_id) || empty($path)) {
            wp_send_json_error(['message' => __('Brakujące parametry.', 'ai-chat-assistant')]);
            exit;
        }

        // Pobranie zawartości pliku
        $repo_service = new RepositoryService();
        $result = $repo_service->get_file_content($repo_id, $path);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        exit;
    }

    public function search_repository() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : '';
        
        if (empty($repo_id) || empty($query)) {
            wp_send_json_error(['message' => __('Brakujące parametry.', 'ai-chat-assistant')]);
            exit;
        }

        // Wyszukiwanie w repozytorium
        $repo_service = new RepositoryService();
        $result = $repo_service->search_repository($repo_id, $query, $branch);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
        exit;
    }

    public function delete_session() {
        // Weryfikacja nonce
        $nonceKey = isset($_POST['nonce_key']) ? $_POST['nonce_key'] : 'aica_nonce';
        if (!check_ajax_referer($nonceKey, 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        
        if (empty($session_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator sesji.', 'ai-chat-assistant')]);
            exit;
        }

        // Usunięcie sesji
        $chat_service = new ChatService();
        $result = $chat_service->delete_session($session_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Sesja została usunięta.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się usunąć sesji.', 'ai-chat-assistant')]);
        }
        exit;
    }

    /**
     * Usuwanie repozytorium
     */
    public function delete_repository() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
        
        if (empty($repo_id)) {
            wp_send_json_error(['message' => __('Brakujący identyfikator repozytorium.', 'ai-chat-assistant')]);
            exit;
        }

        // Usunięcie repozytorium
        $repo_service = new RepositoryService();
        $result = $repo_service->delete_repository($repo_id);

        if ($result) {
            wp_send_json_success(['message' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant')]);
        }
        exit;
    }

    /**
 * Testowanie połączenia z GitLab API
 */
public function test_gitlab_api() {
    // Weryfikacja nonce
    if (!check_ajax_referer('aica_diagnostics_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
        exit;
    }
    
    $token = aica_get_option('gitlab_token', '');
    if (empty($token)) {
        wp_send_json_error(['message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')]);
        exit;
    }
    
    $response = wp_remote_get('https://gitlab.com/api/v4/user', [
        'headers' => ['PRIVATE-TOKEN' => $token],
        'timeout' => 10
    ]);
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        wp_send_json_success(['message' => __('Połączenie z GitLab API działa poprawnie.', 'ai-chat-assistant')]);
    } else {
        wp_send_json_error(['message' => __('Błąd połączenia z GitLab API (kod: ' . $code . ').', 'ai-chat-assistant')]);
    }
    exit;
}

/**
 * Testowanie połączenia z Bitbucket API
 */
public function test_bitbucket_api() {
    // Weryfikacja nonce
    if (!check_ajax_referer('aica_diagnostics_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
        exit;
    }
    
    $username = aica_get_option('bitbucket_username', '');
    $app_password = aica_get_option('bitbucket_app_password', '');
    
    if (empty($username) || empty($app_password)) {
        wp_send_json_error(['message' => __('Dane dostępowe Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')]);
        exit;
    }
    
    $auth = base64_encode($username . ':' . $app_password);
    $response = wp_remote_get('https://api.bitbucket.org/2.0/user', [
        'headers' => ['Authorization' => 'Basic ' . $auth],
        'timeout' => 10
    ]);
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code === 200) {
        wp_send_json_success(['message' => __('Połączenie z Bitbucket API działa poprawnie.', 'ai-chat-assistant')]);
    } else {
        wp_send_json_error(['message' => __('Błąd połączenia z Bitbucket API (kod: ' . $code . ').', 'ai-chat-assistant')]);
    }
    exit;
}

/**
 * Naprawa bazy danych
 */
public function repair_database() {
    // Weryfikacja nonce
    if (!check_ajax_referer('aica_diagnostics_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
        exit;
    }
    
    // Sprawdź uprawnienia
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień do wykonania tej operacji.', 'ai-chat-assistant')]);
        exit;
    }
    
    // Uruchom instalację tabel
    $installer = new \AICA\Installer();
    $result = $installer->create_tables();
    
    if ($result) {
        wp_send_json_success(['message' => __('Tabele bazy danych zostały pomyślnie naprawione.', 'ai-chat-assistant')]);
    } else {
        wp_send_json_error(['message' => __('Nie udało się naprawić tabel bazy danych.', 'ai-chat-assistant')]);
    }
    exit;
}
	


}