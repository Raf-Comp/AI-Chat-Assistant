<?php
namespace AICA\Ajax;

class Handler {
    private $models_handler;
    
    public function __construct() {
        $this->models_handler = new ModelsHandler();
        
        // Rejestracja akcji AJAX dla testowania połączenia z API
        add_action('wp_ajax_aica_test_api_connection', [$this, 'test_api_connection']);
        
        // Rejestracja akcji AJAX dla odświeżania modeli
        add_action('wp_ajax_aica_refresh_models', [$this->models_handler, 'refresh_models']);
        
        // Rejestracja akcji AJAX dla historii rozmów
        add_action('wp_ajax_aica_get_conversations', [$this, 'get_conversations']);
        add_action('wp_ajax_aica_get_conversation_messages', [$this, 'get_conversation_messages']);
        add_action('wp_ajax_aica_delete_conversation', [$this, 'delete_conversation']);
        add_action('wp_ajax_aica_export_conversation', [$this, 'export_conversation']);
        add_action('wp_ajax_aica_duplicate_conversation', [$this, 'duplicate_conversation']);
        add_action('wp_ajax_aica_search_conversations', [$this, 'search_conversations']);
        add_action('wp_ajax_aica_delete_selected_conversations', [$this, 'delete_selected_conversations']);
        
        // Rejestracja akcji AJAX dla czatu
        add_action('wp_ajax_aica_send_message', [$this, 'send_message']);
        add_action('wp_ajax_aica_create_conversation', [$this, 'create_conversation']);
        add_action('wp_ajax_aica_rename_conversation', [$this, 'rename_conversation']);
        add_action('wp_ajax_aica_upload_file', [$this, 'upload_file']);
        
        // Rejestracja akcji AJAX dla repozytoriów
        add_action('wp_ajax_aica_get_repositories', [$this, 'get_repositories']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'get_file_content']);
        add_action('wp_ajax_aica_add_repository', [$this, 'add_repository']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'delete_repository']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'refresh_repository']);
    }
    
    /**
     * Testowanie połączenia z różnymi API
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
    
    /**
     * Pobieranie listy rozmów
     */
    public function get_conversations() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobranie aktualnego użytkownika
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Parametry paginacji
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        // Pobranie rozmów z bazy danych
        $conversations = $this->get_user_conversations($user_id, $page, $per_page);
        $total_conversations = $this->count_user_conversations($user_id);
        
        wp_send_json_success([
            'conversations' => $conversations,
            'total' => $total_conversations,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_conversations / $per_page)
        ]);
    }
    
    /**
     * Pobieranie wiadomości rozmowy
     */
    public function get_conversation_messages() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID rozmowy
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej rozmowy
        if (!$this->user_owns_conversation($user_id, $conversation_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobranie wiadomości
        $messages = $this->get_conversation_messages_by_id($conversation_id);
        
        wp_send_json_success([
            'messages' => $messages
        ]);
    }
    
    /**
     * Usuwanie rozmowy
     */
    public function delete_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID rozmowy
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej rozmowy
        if (!$this->user_owns_conversation($user_id, $conversation_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Usuń rozmowę
        $result = $this->delete_conversation_by_id($conversation_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Rozmowa została usunięta.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się usunąć rozmowy.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Eksportowanie rozmowy do pliku
     */
    public function export_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID rozmowy
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej rozmowy
        if (!$this->user_owns_conversation($user_id, $conversation_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane rozmowy
        $conversation = $this->get_conversation_by_id($conversation_id);
        $messages = $this->get_conversation_messages_by_id($conversation_id);
        
        if (!$conversation) {
            wp_send_json_error([
                'message' => __('Nie znaleziono rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Przygotuj dane do eksportu
        $export_data = [
            'conversation' => $conversation,
            'messages' => $messages
        ];
        
        // Wygeneruj plik w zależności od formatu
        $file_content = '';
        $file_name = sanitize_title($conversation['title']) . '_' . date('Y-m-d');
        
        switch ($format) {
            case 'json':
                $file_content = json_encode($export_data, JSON_PRETTY_PRINT);
                $file_name .= '.json';
                $content_type = 'application/json';
                break;
                
            case 'txt':
                $file_content = $this->format_conversation_as_text($conversation, $messages);
                $file_name .= '.txt';
                $content_type = 'text/plain';
                break;
                
            case 'html':
                $file_content = $this->format_conversation_as_html($conversation, $messages);
                $file_name .= '.html';
                $content_type = 'text/html';
                break;
                
            case 'markdown':
            case 'md':
                $file_content = $this->format_conversation_as_markdown($conversation, $messages);
                $file_name .= '.md';
                $content_type = 'text/markdown';
                break;
                
            default:
                wp_send_json_error([
                    'message' => __('Nieobsługiwany format eksportu.', 'ai-chat-assistant')
                ]);
        }
        
        // Wyślij dane pliku
        wp_send_json_success([
            'file_name' => $file_name,
            'file_content' => base64_encode($file_content),
            'content_type' => $content_type
        ]);
    }
    
    /**
     * Duplikowanie rozmowy
     */
    public function duplicate_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID rozmowy
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej rozmowy
        if (!$this->user_owns_conversation($user_id, $conversation_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane rozmowy
        $conversation = $this->get_conversation_by_id($conversation_id);
        $messages = $this->get_conversation_messages_by_id($conversation_id);
        
        if (!$conversation) {
            wp_send_json_error([
                'message' => __('Nie znaleziono rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Duplikowanie rozmowy
        $new_title = sprintf(__('Kopia: %s', 'ai-chat-assistant'), $conversation['title']);
        $new_conversation_id = $this->create_new_conversation($user_id, $new_title);
        
        if (!$new_conversation_id) {
            wp_send_json_error([
                'message' => __('Nie udało się zduplikować rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Duplikowanie wiadomości
        foreach ($messages as $message) {
            $this->add_message_to_conversation(
                $new_conversation_id,
                $message['role'],
                $message['content'],
                $message['model'],
                isset($message['tokens']) ? $message['tokens'] : 0
            );
        }
        
        wp_send_json_success([
            'message' => __('Rozmowa została zduplikowana.', 'ai-chat-assistant'),
            'conversation_id' => $new_conversation_id,
            'title' => $new_title
        ]);
    }
    
    /**
     * Wyszukiwanie rozmów
     */
    public function search_conversations() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie frazy wyszukiwania
        if (!isset($_POST['search']) || strlen($_POST['search']) < 3) {
            wp_send_json_error([
                'message' => __('Fraza wyszukiwania musi zawierać co najmniej 3 znaki.', 'ai-chat-assistant')
            ]);
        }
        
        $search = sanitize_text_field($_POST['search']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Parametry paginacji
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        
        // Wykonaj wyszukiwanie
        $results = $this->search_user_conversations($user_id, $search, $page, $per_page);
        $total_results = $this->count_search_results($user_id, $search);
        
        wp_send_json_success([
            'conversations' => $results,
            'total' => $total_results,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_results / $per_page)
        ]);
    }
    
    /**
     * Usuwanie wybranych rozmów
     */
    public function delete_selected_conversations() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie listy ID rozmów
        if (!isset($_POST['conversation_ids']) || !is_array($_POST['conversation_ids']) || empty($_POST['conversation_ids'])) {
            wp_send_json_error([
                'message' => __('Nie wybrano żadnych rozmów do usunięcia.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_ids = array_map('intval', $_POST['conversation_ids']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tych rozmów
        foreach ($conversation_ids as $id) {
            if (!$this->user_owns_conversation($user_id, $id)) {
                wp_send_json_error([
                    'message' => __('Nie masz dostępu do jednej lub więcej wybranych rozmów.', 'ai-chat-assistant')
                ]);
            }
        }
        
        // Usunięcie rozmów
        $success_count = 0;
        
        foreach ($conversation_ids as $id) {
            if ($this->delete_conversation_by_id($id)) {
                $success_count++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Usunięto %d z %d wybranych rozmów.', 'ai-chat-assistant'), $success_count, count($conversation_ids)),
            'deleted_count' => $success_count
        ]);
    }
    
    /**
     * Wysyłanie wiadomości do Claude
     */
    public function send_message() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie treści wiadomości
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            wp_send_json_error([
                'message' => __('Wiadomość nie może być pusta.', 'ai-chat-assistant')
            ]);
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy rozmowa istnieje i należy do użytkownika
        if ($conversation_id > 0) {
            if (!$this->user_owns_conversation($user_id, $conversation_id)) {
                wp_send_json_error([
                    'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
                ]);
            }
        } else {
            // Utwórz nową rozmowę
            $title = $this->generate_conversation_title($message);
            $conversation_id = $this->create_new_conversation($user_id, $title);
            
            if (!$conversation_id) {
                wp_send_json_error([
                    'message' => __('Nie udało się utworzyć nowej rozmowy.', 'ai-chat-assistant')
                ]);
            }
        }
        
        // Dodanie wiadomości użytkownika do rozmowy
        $message_id = $this->add_message_to_conversation($conversation_id, 'user', $message);
        
        if (!$message_id) {
            wp_send_json_error([
                'message' => __('Nie udało się zapisać wiadomości.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz historię konwersacji
        $history = $this->get_conversation_messages_by_id($conversation_id);
        $messages_for_api = [];
        
        foreach ($history as $msg) {
            $messages_for_api[] = [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }
        
        // Pobranie ustawień Claude
        $api_key = aica_get_option('claude_api_key', '');
        $model = aica_get_option('claude_model', 'claude-3-haiku-20240307');
        $max_tokens = intval(aica_get_option('max_tokens', 4000));
        $temperature = floatval(aica_get_option('temperature', 0.7));
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
            ]);
        }
        
        // Wysłanie wiadomości do Claude
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $response = $claude_client->send_message($message, $messages_for_api, $model, $max_tokens, $temperature);
        
        if (!$response['success']) {
            wp_send_json_error([
                'message' => $response['message'] ?? __('Wystąpił błąd podczas komunikacji z Claude API.', 'ai-chat-assistant')
            ]);
        }
        
        // Dodanie odpowiedzi Claude do rozmowy
        $assistant_message_id = $this->add_message_to_conversation(
            $conversation_id,
            'assistant',
            $response['message'],
            $model,
            $response['tokens_used'] ?? 0
        );
        
        if (!$assistant_message_id) {
            wp_send_json_error([
                'message' => __('Nie udało się zapisać odpowiedzi asystenta.', 'ai-chat-assistant')
            ]);
        }
        
        // Jeżeli to nowa rozmowa, aktualizuj tytuł na podstawie pierwszej odpowiedzi
        if (count($messages_for_api) <= 2) {
            $new_title = $this->generate_conversation_title($message, $response['message']);
            $this->update_conversation_title($conversation_id, $new_title);
        }
        
        // Zwróć odpowiedź
        wp_send_json_success([
            'message' => $response['message'],
            'conversation_id' => $conversation_id,
            'model' => $model,
            'tokens_used' => $response['tokens_used'] ?? 0
        ]);
    }
    
    /**
     * Tworzenie nowej rozmowy
     */
    public function create_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : __('Nowa rozmowa', 'ai-chat-assistant');
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Utworzenie nowej rozmowy
        $conversation_id = $this->create_new_conversation($user_id, $title);
        
        if (!$conversation_id) {
            wp_send_json_error([
                'message' => __('Nie udało się utworzyć nowej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'conversation_id' => $conversation_id,
            'title' => $title
        ]);
    }
    
    /**
     * Zmiana nazwy rozmowy
     */
    public function rename_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            wp_send_json_error([
                'message' => __('Nowa nazwa nie może być pusta.', 'ai-chat-assistant')
            ]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $title = sanitize_text_field($_POST['title']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej rozmowy
        if (!$this->user_owns_conversation($user_id, $conversation_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizacja tytułu
        $result = $this->update_conversation_title($conversation_id, $title);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Nazwa rozmowy została zaktualizowana.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się zaktualizować nazwy rozmowy.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Przesyłanie pliku
     */
    public function upload_file() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie pliku
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
            wp_send_json_error([
                'message' => $error_message
            ]);
        }
        
        $file = $_FILES['file'];
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie rozszerzenia pliku
        $allowed_extensions = explode(',', aica_get_option('allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md'));
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            wp_send_json_error([
                'message' => sprintf(__('Niedozwolony typ pliku. Dozwolone rozszerzenia: %s', 'ai-chat-assistant'), implode(', ', $allowed_extensions))
            ]);
        }
        
        // Utwórz katalog uploads, jeśli nie istnieje
        $upload_dir = WP_CONTENT_DIR . '/uploads/aica-files/' . $user_id;
        
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            // Zabezpiecz katalog przed przeglądaniem
            file_put_contents($upload_dir . '/.htaccess', 'Deny from all');
        }
        
        // Generowanie unikalnej nazwy pliku
        $file_name = uniqid() . '-' . sanitize_file_name($file['name']);
        $file_path = $upload_dir . '/' . $file_name;
        
        // Przeniesienie pliku
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error([
                'message' => __('Nie udało się przesłać pliku.', 'ai-chat-assistant')
            ]);
        }
        
        // Wczytanie zawartości pliku
        $file_content = file_get_contents($file_path);
        
        if ($file_content === false) {
            wp_send_json_error([
                'message' => __('Nie udało się odczytać zawartości pliku.', 'ai-chat-assistant')
            ]);
        }
        
        // Przygotowanie odpowiedzi
        wp_send_json_success([
            'message' => __('Plik został przesłany pomyślnie.', 'ai-chat-assistant'),
            'file_name' => $file['name'],
            'file_content' => $file_content,
            'file_size' => $file['size'],
            'file_type' => $file['type']
        ]);
    }
    
    /**
     * Pobieranie listy repozytoriów
     */
    public function get_repositories() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobieranie repozytoriów
        $repositories = $this->get_user_repositories($user_id);
        
        wp_send_json_success([
            'repositories' => $repositories
        ]);
    }
    
    /**
     * Pobieranie plików repozytorium
     */
    public function get_repository_files() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repository_id']) || empty($_POST['repository_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repository_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz pliki repozytorium
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = null;
        
        switch ($repository['type']) {
            case 'github':
                $token = aica_get_option('github_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitHubClient($token);
                break;
                
            case 'gitlab':
                $token = aica_get_option('gitlab_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitLabClient($token);
                break;
                
            case 'bitbucket':
                $username = aica_get_option('bitbucket_username', '');
                $password = aica_get_option('bitbucket_app_password', '');
                if (empty($username) || empty($password)) {
                    wp_send_json_error([
                        'message' => __('Dane logowania Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\BitbucketClient($username, $password);
                break;
                
            default:
                wp_send_json_error([
                    'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
                ]);
        }
        
        $files = $client->get_repository_files($repository['full_name'], $repository['default_branch'], $path);
        
        if ($files === false) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać plików repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'files' => $files,
            'repository' => $repository
        ]);
    }
    
    /**
     * Pobieranie zawartości pliku
     */
    public function get_file_content() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['repository_id']) || empty($_POST['repository_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        if (!isset($_POST['path']) || empty($_POST['path'])) {
            wp_send_json_error([
                'message' => __('Nie podano ścieżki pliku.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repository_id']);
        $path = sanitize_text_field($_POST['path']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = null;
        
        switch ($repository['type']) {
            case 'github':
                $token = aica_get_option('github_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitHubClient($token);
                break;
                
            case 'gitlab':
                $token = aica_get_option('gitlab_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitLabClient($token);
                break;
                
            case 'bitbucket':
                $username = aica_get_option('bitbucket_username', '');
                $password = aica_get_option('bitbucket_app_password', '');
                if (empty($username) || empty($password)) {
                    wp_send_json_error([
                        'message' => __('Dane logowania Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\BitbucketClient($username, $password);
                break;
                
            default:
                wp_send_json_error([
                    'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
                ]);
        }
        
        $content = $client->get_file_content($repository['full_name'], $repository['default_branch'], $path);
        
        if ($content === false) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać zawartości pliku.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'content' => $content,
            'path' => $path,
            'repository' => $repository
        ]);
    }
    
    /**
     * Dodawanie repozytorium
     */
    public function add_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['url']) || empty($_POST['url'])) {
            wp_send_json_error([
                'message' => __('Nie podano URL repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $url = esc_url_raw($_POST['url']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Wykryj typ repozytorium na podstawie URL
        $repository_type = '';
        $repository_info = null;
        
        if (strpos($url, 'github.com') !== false) {
            $repository_type = 'github';
            $token = aica_get_option('github_token', '');
            if (empty($token)) {
                wp_send_json_error([
                    'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                ]);
            }
            $client = new \AICA\API\GitHubClient($token);
        } elseif (strpos($url, 'gitlab.com') !== false) {
            $repository_type = 'gitlab';
            $token = aica_get_option('gitlab_token', '');
            if (empty($token)) {
                wp_send_json_error([
                    'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                ]);
            }
            $client = new \AICA\API\GitLabClient($token);
        } elseif (strpos($url, 'bitbucket.org') !== false) {
            $repository_type = 'bitbucket';
            $username = aica_get_option('bitbucket_username', '');
            $password = aica_get_option('bitbucket_app_password', '');
            if (empty($username) || empty($password)) {
                wp_send_json_error([
                    'message' => __('Dane logowania Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                ]);
            }
            $client = new \AICA\API\BitbucketClient($username, $password);
        } else {
            wp_send_json_error([
                'message' => __('Nieobsługiwany adres URL repozytorium. Obsługiwane serwisy: GitHub, GitLab, Bitbucket.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz informacje o repozytorium
        $repository_info = $client->get_repository_info_from_url($url);
        
        if (!$repository_info) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać informacji o repozytorium. Sprawdź URL i upewnij się, że masz dostęp do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy repozytorium już istnieje
        if ($this->repository_exists($user_id, $repository_type, $repository_info['full_name'])) {
            wp_send_json_error([
                'message' => __('To repozytorium zostało już dodane.', 'ai-chat-assistant')
            ]);
        }
        
        // Dodaj repozytorium
        $repository_id = $this->add_repository_to_user(
            $user_id,
            $repository_type,
            $repository_info['name'],
            $repository_info['full_name'],
            $repository_info['description'] ?? '',
            $repository_info['default_branch'] ?? 'main',
            $repository_info['avatar_url'] ?? '',
            $repository_info['html_url'] ?? $url
        );
        
        if (!$repository_id) {
            wp_send_json_error([
                'message' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Repozytorium zostało dodane pomyślnie.', 'ai-chat-assistant'),
            'repository_id' => $repository_id,
            'repository' => $this->get_repository_by_id($repository_id)
        ]);
    }
    
    /**
     * Usuwanie repozytorium
     */
    public function delete_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repository_id']) || empty($_POST['repository_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repository_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Usuń repozytorium
        $result = $this->delete_repository_by_id($repository_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Odświeżanie repozytorium
     */
    public function refresh_repository() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID repozytorium
        if (!isset($_POST['repository_id']) || empty($_POST['repository_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        $repository_id = intval($_POST['repository_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tego repozytorium
        if (!$this->user_owns_repository($user_id, $repository_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane repozytorium
        $repository = $this->get_repository_by_id($repository_id);
        
        if (!$repository) {
            wp_send_json_error([
                'message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Wybierz odpowiedniego klienta w zależności od typu repozytorium
        $client = null;
        
        switch ($repository['type']) {
            case 'github':
                $token = aica_get_option('github_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitHubClient($token);
                break;
                
            case 'gitlab':
                $token = aica_get_option('gitlab_token', '');
                if (empty($token)) {
                    wp_send_json_error([
                        'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\GitLabClient($token);
                break;
                
            case 'bitbucket':
                $username = aica_get_option('bitbucket_username', '');
                $password = aica_get_option('bitbucket_app_password', '');
                if (empty($username) || empty($password)) {
                    wp_send_json_error([
                        'message' => __('Dane logowania Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                    ]);
                }
                $client = new \AICA\API\BitbucketClient($username, $password);
                break;
                
            default:
                wp_send_json_error([
                    'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
                ]);
        }
        
        // Pobierz aktualne informacje o repozytorium
        $repository_info = $client->get_repository_info($repository['full_name']);
        
        if (!$repository_info) {
            wp_send_json_error([
                'message' => __('Nie udało się pobrać informacji o repozytorium. Sprawdź czy masz dostęp do tego repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizuj dane repozytorium
        $result = $this->update_repository(
            $repository_id,
            $repository_info['name'],
            $repository_info['description'] ?? $repository['description'],
            $repository_info['default_branch'] ?? $repository['default_branch'],
            $repository_info['avatar_url'] ?? $repository['avatar_url'],
            $repository_info['html_url'] ?? $repository['html_url']
        );
        
        if (!$result) {
            wp_send_json_error([
                'message' => __('Nie udało się zaktualizować danych repozytorium.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'message' => __('Dane repozytorium zostały zaktualizowane.', 'ai-chat-assistant'),
            'repository' => $this->get_repository_by_id($repository_id)
        ]);
    }
    
    // Metody pomocnicze do obsługi bazy danych
    
    /**
     * Sprawdza czy użytkownik ma dostęp do danej rozmowy
     */
    private function user_owns_conversation($user_id, $conversation_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE id = %d AND user_id = %d",
            $conversation_id, $user_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Sprawdza czy użytkownik ma dostęp do danego repozytorium
     */
    private function user_owns_repository($user_id, $repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE id = %d AND user_id = %d",
            $repository_id, $user_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Pobiera rozmowy użytkownika z paginacją
     */
    private function get_user_conversations($user_id, $page = 1, $per_page = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        
        $offset = ($page - 1) * $per_page;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ), ARRAY_A);
    }
    
    /**
     * Pobiera repozytoria użytkownika
     */
    private function get_user_repositories($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY name ASC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Pobiera dane repozytorium na podstawie ID
     */
    private function get_repository_by_id($repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $repository_id
        ), ARRAY_A);
    }
    
    /**
     * Zlicza rozmowy użytkownika
     */
    private function count_user_conversations($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Pobiera wiadomości rozmowy
     */
    private function get_conversation_messages_by_id($conversation_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_id = %d ORDER BY created_at ASC",
            $conversation_id
        ), ARRAY_A);
    }
    
    /**
     * Pobiera dane rozmowy na podstawie ID
     */
    private function get_conversation_by_id($conversation_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $conversation_id
        ), ARRAY_A);
    }
    
    /**
     * Tworzy nową rozmowę
     */
    private function create_new_conversation($user_id, $title) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Dodaje wiadomość do rozmowy
     */
    private function add_message_to_conversation($conversation_id, $role, $content, $model = '', $tokens = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_messages';
        $now = current_time('mysql');
        
        // Aktualizacja czasu ostatniej modyfikacji rozmowy
        $conversations_table = $wpdb->prefix . 'aica_conversations';
        $wpdb->update(
            $conversations_table,
            ['updated_at' => $now],
            ['id' => $conversation_id],
            ['%s'],
            ['%d']
        );
        
        $result = $wpdb->insert(
            $table,
            [
                'conversation_id' => $conversation_id,
                'role' => $role,
                'content' => $content,
                'model' => $model,
                'tokens' => $tokens,
                'created_at' => $now
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Usuwa rozmowę wraz z jej wiadomościami
     */
    private function delete_conversation_by_id($conversation_id) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'aica_conversations';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Usunięcie wiadomości
        $wpdb->delete(
            $messages_table,
            ['conversation_id' => $conversation_id],
            ['%d']
        );
        
        // Usunięcie rozmowy
        $result = $wpdb->delete(
            $conversations_table,
            ['id' => $conversation_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Aktualizuje tytuł rozmowy
     */
    private function update_conversation_title($conversation_id, $title) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_conversations';
        
        $result = $wpdb->update(
            $table,
            ['title' => $title],
            ['id' => $conversation_id],
            ['%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Generuje tytuł rozmowy na podstawie pierwszej wiadomości i odpowiedzi
     */
    private function generate_conversation_title($message, $response = '') {
        // Skrócenie wiadomości do pierwszych 50 znaków
        $title = substr($message, 0, 50);
        
        // Usunięcie znaków nowej linii
        $title = str_replace(["\r", "\n"], ' ', $title);
        
        // Dodanie wielokropka, jeśli wiadomość była dłuższa
        if (strlen($message) > 50) {
            $title .= '...';
        }
        
        return $title;
    }
    
    /**
     * Wyszukiwanie rozmów użytkownika
     */
    private function search_user_conversations($user_id, $search, $page = 1, $per_page = 10) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'aica_conversations';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $offset = ($page - 1) * $per_page;
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT c.* FROM $conversations_table AS c
            LEFT JOIN $messages_table AS m ON c.id = m.conversation_id
            WHERE c.user_id = %d
            AND (c.title LIKE %s OR m.content LIKE %s)
            ORDER BY c.updated_at DESC
            LIMIT %d OFFSET %d",
            $user_id, $search_term, $search_term, $per_page, $offset
        ), ARRAY_A);
    }
    
    /**
     * Zlicza wyniki wyszukiwania
     */
    private function count_search_results($user_id, $search) {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'aica_conversations';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT c.id) FROM $conversations_table AS c
            LEFT JOIN $messages_table AS m ON c.id = m.conversation_id
            WHERE c.user_id = %d
            AND (c.title LIKE %s OR m.content LIKE %s)",
            $user_id, $search_term, $search_term
        ));
    }
    
    /**
     * Sprawdza czy repozytorium już istnieje
     */
    private function repository_exists($user_id, $type, $full_name) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND type = %s AND full_name = %s",
            $user_id, $type, $full_name
        ));
        
        return $result > 0;
    }
    
    /**
     * Dodaje repozytorium do użytkownika
     */
    private function add_repository_to_user($user_id, $type, $name, $full_name, $description, $default_branch, $avatar_url, $html_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'type' => $type,
                'name' => $name,
                'full_name' => $full_name,
                'description' => $description,
                'default_branch' => $default_branch,
                'avatar_url' => $avatar_url,
                'html_url' => $html_url,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Aktualizuje dane repozytorium
     */
    private function update_repository($repository_id, $name, $description, $default_branch, $avatar_url, $html_url) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        $now = current_time('mysql');
        
        $result = $wpdb->update(
            $table,
            [
                'name' => $name,
                'description' => $description,
                'default_branch' => $default_branch,
                'avatar_url' => $avatar_url,
                'html_url' => $html_url,
                'updated_at' => $now
            ],
            ['id' => $repository_id],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Usuwa repozytorium
     */
    private function delete_repository_by_id($repository_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_repositories';
        
        $result = $wpdb->delete(
            $table,
            ['id' => $repository_id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Formatuje rozmowę jako tekst
     */
    private function format_conversation_as_text($conversation, $messages) {
        $output = "Tytuł: " . $conversation['title'] . "\n";
        $output .= "Data: " . $conversation['created_at'] . "\n\n";
        
        foreach ($messages as $message) {
            $role = $message['role'] === 'user' ? 'Użytkownik' : 'Claude';
            $output .= "[$role]:\n" . $message['content'] . "\n\n";
        }
        
        return $output;
    }
    
    /**
     * Formatuje rozmowę jako HTML
     */
    private function format_conversation_as_html($conversation, $messages) {
        $output = "<!DOCTYPE html>\n<html>\n<head>\n";
        $output .= "<meta charset=\"UTF-8\">\n";
        $output .= "<title>" . esc_html($conversation['title']) . "</title>\n";
        $output .= "<style>\n";
        $output .= "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n";
        $output .= "h1 { color: #2271b1; }\n";
        $output .= ".message { margin-bottom: 20px; padding: 15px; border-radius: 8px; }\n";
        $output .= ".user { background-color: #f0f0f0; }\n";
        $output .= ".assistant { background-color: #e6f3ff; }\n";
        $output .= ".role { font-weight: bold; margin-bottom: 5px; }\n";
        $output .= ".content { white-space: pre-wrap; }\n";
        $output .= ".meta { color: #666; font-size: 14px; margin-top: 15px; }\n";
        $output .= "</style>\n";
        $output .= "</head>\n<body>\n";
        
        $output .= "<h1>" . esc_html($conversation['title']) . "</h1>\n";
        $output .= "<p class=\"meta\">Data utworzenia: " . esc_html($conversation['created_at']) . "</p>\n";
        
        foreach ($messages as $message) {
            $role_class = $message['role'] === 'user' ? 'user' : 'assistant';
            $role_name = $message['role'] === 'user' ? 'Użytkownik' : 'Claude';
            
            $output .= "<div class=\"message {$role_class}\">\n";
            $output .= "<div class=\"role\">{$role_name}</div>\n";
            $output .= "<div class=\"content\">" . nl2br(esc_html($message['content'])) . "</div>\n";
            
            if (!empty($message['model']) && $message['role'] === 'assistant') {
                $output .= "<div class=\"meta\">Model: " . esc_html($message['model']) . "</div>\n";
            }
            
            $output .= "</div>\n";
        }
        
        $output .= "</body>\n</html>";
        
        return $output;
    }
    
    /**
     * Formatuje rozmowę jako Markdown
     */
    private function format_conversation_as_markdown($conversation, $messages) {
        $output = "# " . $conversation['title'] . "\n\n";
        $output .= "Data utworzenia: " . $conversation['created_at'] . "\n\n";
        
        foreach ($messages as $message) {
            $role = $message['role'] === 'user' ? 'Użytkownik' : 'Claude';
            $output .= "## " . $role . "\n\n";
            $output .= $message['content'] . "\n\n";
            
            if (!empty($message['model']) && $message['role'] === 'assistant') {
                $output .= "*Model: " . $message['model'] . "*\n\n";
            }
        }
        
        return $output;
    }
    
    /**
     * Zwraca komunikat błędu przesyłania pliku
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('Przesłany plik przekracza limit określony w dyrektywie upload_max_filesize w php.ini.', 'ai-chat-assistant');
            case UPLOAD_ERR_FORM_SIZE:
                return __('Przesłany plik przekracza limit określony w formularzu HTML.', 'ai-chat-assistant');
            case UPLOAD_ERR_PARTIAL:
                return __('Plik został przesłany tylko częściowo.', 'ai-chat-assistant');
            case UPLOAD_ERR_NO_FILE:
                return __('Żaden plik nie został przesłany.', 'ai-chat-assistant');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Brak katalogu tymczasowego.', 'ai-chat-assistant');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Nie udało się zapisać pliku na dysku.', 'ai-chat-assistant');
            case UPLOAD_ERR_EXTENSION:
                return __('Przesyłanie pliku zostało zatrzymane przez rozszerzenie PHP.', 'ai-chat-assistant');
            default:
                return __('Wystąpił nieznany błąd podczas przesyłania pliku.', 'ai-chat-assistant');
        }
    }
}