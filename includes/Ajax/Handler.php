<?php
namespace AICA\Ajax;

use AICA\Services\ChatService;
use AICA\Services\RepositoryService;
use AICA\Services\FileService;

class Handler {
    public function init() {
        // Rejestracja punktów końcowych AJAX
        add_action('wp_ajax_aica_create_session', [$this, 'create_session']);
        add_action('wp_ajax_aica_send_message', [$this, 'send_message']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'get_chat_history']);
        add_action('wp_ajax_aica_upload_file', [$this, 'upload_file']);
        add_action('wp_ajax_aica_get_repositories', [$this, 'get_repositories']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'get_file_content']);
        add_action('wp_ajax_aica_search_repository', [$this, 'search_repository']);
        add_action('wp_ajax_aica_delete_session', [$this, 'delete_session']);
        add_action('wp_ajax_aica_test_claude_api', [$this, 'test_claude_api']);
    }

    /**
     * Testowanie połączenia z API Claude (AJAX)
     */
    public function test_claude_api() {
        // Weryfikacja nonce
        if (!check_ajax_referer('aica_settings_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa.', 'ai-chat-assistant')]);
            exit;
        }

        // Walidacja parametrów
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('Brakujący klucz API.', 'ai-chat-assistant')]);
            exit;
        }

        // Testowanie połączenia
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $test_result = $claude_client->test_connection();

        if ($test_result) {
            wp_send_json_success(['message' => __('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się połączyć z API Claude. Sprawdź klucz API.', 'ai-chat-assistant')]);
        }
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
}