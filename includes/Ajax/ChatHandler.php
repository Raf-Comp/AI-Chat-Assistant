<?php
namespace AICA\Ajax;

class ChatHandler {
    public function __construct() {
        // Rejestracja akcji AJAX dla czatu
        add_action('wp_ajax_aica_send_message', [$this, 'send_message']);
        add_action('wp_ajax_aica_create_session', [$this, 'create_session']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'get_chat_history']);
        add_action('wp_ajax_aica_get_sessions_list', [$this, 'get_sessions_list']);
        add_action('wp_ajax_aica_get_session_title', [$this, 'get_session_title']);
        add_action('wp_ajax_aica_delete_session', [$this, 'delete_session']);
        add_action('wp_ajax_aica_rename_session', [$this, 'rename_session']);
        add_action('wp_ajax_aica_upload_file', [$this, 'upload_file']);
        add_action('wp_ajax_aica_create_conversation', [$this, 'create_conversation']);
        add_action('wp_ajax_aica_duplicate_conversation', [$this, 'duplicate_conversation']);
        add_action('wp_ajax_aica_export_conversation', [$this, 'export_conversation']);
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
     * Tworzenie nowej sesji
     */
    public function create_session() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
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
        
        // Utwórz nową sesję
        $session_id = uniqid('session_');
        
        // Zapisz sesję do bazy danych
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        $title = __('Nowa rozmowa', 'ai-chat-assistant');
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'user_id' => $user_id,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );
        
        if ($result) {
            wp_send_json_success([
                'session_id' => $session_id,
                'title' => $title
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Pobieranie historii czatu
     */
    public function get_chat_history() {
        // Sprawdzenie nonce
        $nonce_key = isset($_POST['nonce_key']) ? sanitize_text_field($_POST['nonce_key']) : 'aica_nonce';
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_key)) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie sesji
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz informacje o sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE session_id = %s AND user_id = %d",
            $session_id, $user_id
        ), ARRAY_A);
        
        if (!$session) {
            wp_send_json_error([
                'message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz wiadomości
        $messages_table = $wpdb->prefix . 'aica_messages';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $offset = ($page - 1) * $per_page;
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE session_id = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $session_id, $per_page, $offset
        ), ARRAY_A);
        
        // Pobierz całkowitą liczbę wiadomości
        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE session_id = %s",
            $session_id
        ));
        
        // Odwróć kolejność, aby były od najstarszej
        $messages = array_reverse($messages);
        
        wp_send_json_success([
            'title' => $session['title'],
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total_messages / $per_page),
                'total_items' => $total_messages,
                'per_page' => $per_page
            ]
        ]);
    }
    
    /**
     * Pobieranie listy sesji
     */
    public function get_sessions_list() {
        // Sprawdzenie nonce
        $nonce_key = isset($_POST['nonce_key']) ? sanitize_text_field($_POST['nonce_key']) : 'aica_nonce';
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_key)) {
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
        
        // Pobierz listę sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        
        // Pobierz sesje z liczbą wiadomości
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, COUNT(m.id) AS message_count 
             FROM $sessions_table AS s 
             LEFT JOIN $messages_table AS m ON s.session_id = m.session_id 
             WHERE s.user_id = %d 
             GROUP BY s.session_id 
             ORDER BY s.updated_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ), ARRAY_A);
        
        // Pobierz całkowitą liczbę sesji
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE user_id = %d",
            $user_id
        ));
        
        wp_send_json_success([
            'sessions' => $sessions,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total_sessions / $per_page),
                'total_items' => $total_sessions,
                'per_page' => $per_page
            ]
        ]);
    }
    
    /**
     * Pobieranie tytułu sesji
     */
    public function get_session_title() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie sesji
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz informacje o sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $title = $wpdb->get_var($wpdb->prepare(
            "SELECT title FROM $sessions_table WHERE session_id = %s AND user_id = %d",
            $session_id, $user_id
        ));
        
        if ($title === null) {
            wp_send_json_error([
                'message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'title' => $title
        ]);
    }
    
    /**
     * Usuwanie sesji
     */
    public function delete_session() {
        // Sprawdzenie nonce
        $nonce_key = isset($_POST['nonce_key']) ? sanitize_text_field($_POST['nonce_key']) : 'aica_nonce';
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_key)) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie sesji
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Usuń sesję i jej wiadomości
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Sprawdź, czy sesja należy do użytkownika
        $session_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE session_id = %s AND user_id = %d",
            $session_id, $user_id
        ));
        
        if (!$session_exists) {
            wp_send_json_error([
                'message' => __('Nie masz uprawnień do usunięcia tej sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Usuń wiadomości
        $wpdb->delete(
            $messages_table,
            ['session_id' => $session_id],
            ['%s']
        );
        
        // Usuń sesję
        $result = $wpdb->delete(
            $sessions_table,
            ['session_id' => $session_id, 'user_id' => $user_id],
            ['%s', '%d']
        );
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Sesja została usunięta.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się usunąć sesji.', 'ai-chat-assistant')
            ]);
        }
    }
    
    /**
     * Zmiana nazwy sesji
     */
    public function rename_session() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie danych
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            wp_send_json_error([
                'message' => __('Nie podano tytułu.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $title = sanitize_text_field($_POST['title']);
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizuj tytuł sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        // Sprawdź, czy sesja należy do użytkownika
        $session_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE session_id = %s AND user_id = %d",
            $session_id, $user_id
        ));
        
        if (!$session_exists) {
            wp_send_json_error([
                'message' => __('Nie masz uprawnień do zmiany nazwy tej sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Aktualizuj tytuł
        $result = $wpdb->update(
            $sessions_table,
            ['title' => $title],
            ['session_id' => $session_id, 'user_id' => $user_id],
            ['%s'],
            ['%s', '%d']
        );
        
        if ($result !== false) {
            wp_send_json_success([
                'message' => __('Nazwa sesji została zaktualizowana.', 'ai-chat-assistant')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Nie udało się zaktualizować nazwy sesji.', 'ai-chat-assistant')
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
    
    // Metody pomocnicze
    
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