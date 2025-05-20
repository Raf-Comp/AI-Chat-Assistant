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
        add_action('wp_ajax_aica_save_message', [$this, 'save_message']);
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
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy sesja istnieje i należy do użytkownika
        if (!empty($session_id)) {
            if (!$this->user_owns_session($user_id, $session_id)) {
                wp_send_json_error([
                    'message' => __('Nie masz dostępu do tej sesji.', 'ai-chat-assistant')
                ]);
            }
        } else {
            // Utwórz nową sesję
            $title = __('Nowa rozmowa', 'ai-chat-assistant');
            $session_id = $this->create_new_session($user_id, $title);
            
            if (!$session_id) {
                wp_send_json_error([
                    'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
                ]);
            }
        }
        
        // Pobierz informacje o kontekście
        $context = isset($_POST['context']) ? $_POST['context'] : null;
        $context_info = '';
        
        if ($context && isset($context['repositoryId']) && isset($context['filePath'])) {
            $repo_id = intval($context['repositoryId']);
            $file_path = sanitize_text_field($context['filePath']);
            
            // Tutaj możesz dodać kod do pobierania zawartości pliku z repozytorium
            $repo_service = new \AICA\Services\RepositoryService();
            $file_content = $repo_service->get_file_content($repo_id, $file_path);
            
            if ($file_content) {
                $context_info = sprintf(
                    __('Kontekst: Plik %s z repozytorium.', 'ai-chat-assistant'),
                    $file_path
                );
                
                // Dodaj zawartość pliku do wiadomości
                $message = $context_info . "\n\n" . $message;
            }
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
        
        // Pobierz historię rozmowy
        $history = $this->get_session_messages($session_id);
        $messages_for_api = [];
        
        foreach ($history as $msg) {
            if ($msg['type'] === 'user') {
                $messages_for_api[] = [
                    'role' => 'user',
                    'content' => $msg['content']
                ];
            } elseif ($msg['type'] === 'assistant') {
                $messages_for_api[] = [
                    'role' => 'assistant',
                    'content' => $msg['content']
                ];
            }
        }
        
        // Dodanie aktualnej wiadomości użytkownika
        $messages_for_api[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        // Wysłanie wiadomości do Claude
        $claude_client = new \AICA\API\ClaudeClient($api_key);
        $response = $claude_client->send_message($messages_for_api, $model, $max_tokens, $temperature);
        
        if (!$response['success']) {
            wp_send_json_error([
                'message' => $response['message'] ?? __('Wystąpił błąd podczas komunikacji z Claude API.', 'ai-chat-assistant')
            ]);
        }
        
        // Zapisanie wiadomości do bazy danych
        $this->add_message_to_session($session_id, 'user', $message);
        $this->add_message_to_session($session_id, 'assistant', $response['message']);
        
        // Jeżeli to nowa rozmowa, zaktualizuj tytuł na podstawie pierwszej wymiany wiadomości
        if (count($history) <= 2) {
            $new_title = $this->generate_session_title($message, $response['message']);
            $this->update_session_title($session_id, $new_title);
        }
        
        // Aktualizuj czas ostatniej modyfikacji sesji
        $this->update_session_time($session_id);
        
        // Zwróć odpowiedź
        wp_send_json_success([
            'content' => $response['message'],
            'session_id' => $session_id,
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
        
        $user_id = get_current_user_id();
        
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
        $user_id = get_current_user_id();
        
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        $user_id = get_current_user_id();
        
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
            "SELECT s.*, COUNT(m.id) AS message_count, 
            (SELECT content FROM $messages_table WHERE session_id = s.session_id AND type = 'user' ORDER BY created_at ASC LIMIT 1) AS preview
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
        
        // Przetwórz podglądy, aby nie były za długie
        foreach ($sessions as &$session) {
            if (!empty($session['preview'])) {
                $session['preview'] = wp_trim_words($session['preview'], 10, '...');
            } else {
                $session['preview'] = __('Nowa rozmowa', 'ai-chat-assistant');
            }
        }
        
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
        $user_id = get_current_user_id();
        
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
        $user_id = get_current_user_id();
        
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
        $user_id = get_current_user_id();
        
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
        $user_id = get_current_user_id();
        
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
     * Zapisywanie wiadomości po stronie klienta
     */
    public function save_message() {
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
        
        if (!isset($_POST['user_message']) || empty($_POST['user_message'])) {
            wp_send_json_error([
                'message' => __('Wiadomość użytkownika nie może być pusta.', 'ai-chat-assistant')
            ]);
        }
        
        if (!isset($_POST['assistant_response']) || empty($_POST['assistant_response'])) {
            wp_send_json_error([
                'message' => __('Odpowiedź asystenta nie może być pusta.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_message = sanitize_textarea_field($_POST['user_message']);
        $assistant_response = wp_kses_post($_POST['assistant_response']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź, czy sesja należy do użytkownika
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Dodanie wiadomości do bazy danych
        $this->add_message_to_session($session_id, 'user', $user_message);
        $this->add_message_to_session($session_id, 'assistant', $assistant_response);
        
        // Aktualizuj czas ostatniej modyfikacji sesji
        $this->update_session_time($session_id);
        
        // Sprawdzenie czy session_id ma już tytuł różny od "Nowa rozmowa"
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $session_title = $wpdb->get_var($wpdb->prepare(
            "SELECT title FROM $sessions_table WHERE session_id = %s",
            $session_id
        ));
        
        // Jeśli tytuł to "Nowa rozmowa", wygeneruj nowy tytuł z pierwszej wiadomości
        if ($session_title === __('Nowa rozmowa', 'ai-chat-assistant')) {
            $new_title = $this->generate_session_title($user_message);
            $this->update_session_title($session_id, $new_title);
        }
        
        wp_send_json_success([
            'message' => __('Wiadomość została zapisana.', 'ai-chat-assistant')
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
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Utworzenie nowej rozmowy/sesji
        $session_id = $this->create_new_session($user_id, $title);
        
        if (!$session_id) {
            wp_send_json_error([
                'message' => __('Nie udało się utworzyć nowej rozmowy.', 'ai-chat-assistant')
            ]);
        }
        
        wp_send_json_success([
            'session_id' => $session_id,
            'title' => $title
        ]);
    }
    
    /**
     * Duplikowanie rozmowy
     */
    public function duplicate_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID sesji
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej sesji
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE session_id = %s",
            $session_id
        ), ARRAY_A);
        
        if (!$session) {
            wp_send_json_error([
                'message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Duplikowanie sesji
        $new_title = sprintf(__('Kopia: %s', 'ai-chat-assistant'), $session['title']);
        $new_session_id = uniqid('session_');
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $sessions_table,
            [
                'session_id' => $new_session_id,
                'user_id' => $user_id,
                'title' => $new_title,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );
        
        if (!$result) {
            wp_send_json_error([
                'message' => __('Nie udało się zduplikować sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Duplikowanie wiadomości
        $messages_table = $wpdb->prefix . 'aica_messages';
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A);
        
        foreach ($messages as $message) {
            $wpdb->insert(
                $messages_table,
                [
                    'session_id' => $new_session_id,
                    'type' => $message['type'],
                    'content' => $message['content'],
                    'created_at' => $now
                ],
                ['%s', '%s', '%s', '%s']
            );
        }
        
        wp_send_json_success([
            'message' => __('Sesja została zduplikowana.', 'ai-chat-assistant'),
            'session_id' => $new_session_id,
            'title' => $new_title
        ]);
    }
    
    /**
     * Eksportowanie rozmowy do pliku
     */
    public function export_conversation() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdzenie ID sesji
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error([
                'message' => __('Nie podano ID sesji.', 'ai-chat-assistant')
            ]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'markdown';
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
        }
        
        // Sprawdź czy użytkownik ma dostęp do tej sesji
        if (!$this->user_owns_session($user_id, $session_id)) {
            wp_send_json_error([
                'message' => __('Nie masz dostępu do tej sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz dane sesji
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $sessions_table WHERE session_id = %s",
            $session_id
        ), ARRAY_A);
        
        if (!$session) {
            wp_send_json_error([
                'message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')
            ]);
        }
        
        // Pobierz wiadomości
        $messages_table = $wpdb->prefix . 'aica_messages';
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A);
        
        // Wygeneruj odpowiedni format
        $content = '';
        $file_name = sanitize_title($session['title']) . '_' . date('Y-m-d');
        $mime_type = '';
        
        switch ($format) {
            case 'markdown':
            case 'md':
                $content = $this->format_conversation_as_markdown($session, $messages);
                $file_name .= '.md';
                $mime_type = 'text/markdown';
                break;
                
            case 'html':
                $content = $this->format_conversation_as_html($session, $messages);
                $file_name .= '.html';
                $mime_type = 'text/html';
                break;
                
            case 'json':
                $data = [
                    'title' => $session['title'],
                    'created_at' => $session['created_at'],
                    'updated_at' => $session['updated_at'],
                    'messages' => []
                ];
                
                foreach ($messages as $message) {
                    $data['messages'][] = [
                        'type' => $message['type'],
                        'content' => $message['content'],
                        'created_at' => $message['created_at']
                    ];
                }
                
                $content = json_encode($data, JSON_PRETTY_PRINT);
                $file_name .= '.json';
                $mime_type = 'application/json';
                break;
                
            case 'txt':
            default:
                $content = $this->format_conversation_as_text($session, $messages);
                $file_name .= '.txt';
                $mime_type = 'text/plain';
                break;
        }
        
        wp_send_json_success([
            'file_name' => $file_name,
            'content' => base64_encode($content),
            'mime_type' => $mime_type
        ]);
    }
    
    /**
     * Sprawdza czy użytkownik jest właścicielem sesji
     */
    private function user_owns_session($user_id, $session_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $sessions_table WHERE session_id = %s AND user_id = %d",
            $session_id, $user_id
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * Tworzy nową sesję
     */
    private function create_new_session($user_id, $title) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $now = current_time('mysql');
        
        $session_id = uniqid('session_');
        
        $result = $wpdb->insert(
            $sessions_table,
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
            return $session_id;
        }
        
        return false;
    }
    
    /**
     * Dodaje wiadomość do sesji
     */
    private function add_message_to_session($session_id, $type, $content) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        $now = current_time('mysql');
        
        $result = $wpdb->insert(
            $messages_table,
            [
                'session_id' => $session_id,
                'type' => $type,
                'content' => $content,
                'created_at' => $now
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Pobiera wiadomości z sesji
     */
    private function get_session_messages($session_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE session_id = %s ORDER BY created_at ASC",
            $session_id
        ), ARRAY_A);
        
        return $messages;
    }
    
    /**
     * Aktualizuje tytuł sesji
     */
    private function update_session_title($session_id, $title) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $result = $wpdb->update(
            $sessions_table,
            ['title' => $title],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Aktualizuje czas ostatniej modyfikacji sesji
     */
    private function update_session_time($session_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $now = current_time('mysql');
        
        $wpdb->update(
            $sessions_table,
            ['updated_at' => $now],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
    }
    
    /**
     * Generuje tytuł sesji na podstawie wiadomości
     */
    private function generate_session_title($message, $response = '') {
        // Skrócenie wiadomości do pierwszych 50 znaków
        $title = substr($message, 0, 50);
        
        // Usunięcie znaków nowej linii i HTML
        $title = strip_tags($title);
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
    private function format_conversation_as_text($session, $messages) {
        $output = "Tytuł: " . $session['title'] . "\n";
        $output .= "Data: " . $session['created_at'] . "\n\n";
        
        foreach ($messages as $message) {
            $role = $message['type'] === 'user' ? 'Użytkownik' : 'Claude';
            $output .= "[$role]:\n" . strip_tags($message['content']) . "\n\n";
        }
        
        return $output;
    }
    
    /**
     * Formatuje rozmowę jako HTML
     */
    private function format_conversation_as_html($session, $messages) {
        $output = "<!DOCTYPE html>\n<html>\n<head>\n";
        $output .= "<meta charset=\"UTF-8\">\n";
        $output .= "<title>" . esc_html($session['title']) . "</title>\n";
        $output .= "<style>\n";
        $output .= "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n";
        $output .= "h1 { color: #6366F1; }\n";
        $output .= ".message { margin-bottom: 20px; padding: 15px; border-radius: 12px; }\n";
        $output .= ".user { background-color: #EEF2FF; }\n";
        $output .= ".assistant { background-color: #F8FAFC; }\n";
        $output .= ".role { font-weight: bold; margin-bottom: 5px; }\n";
        $output .= ".content { white-space: pre-wrap; }\n";
        $output .= ".meta { color: #64748B; font-size: 14px; margin-top: 15px; }\n";
        $output .= "pre { background-color: #1E293B; color: #F1F5F9; padding: 15px; border-radius: 8px; overflow-x: auto; }\n";
        $output .= "code { font-family: 'Courier New', monospace; }\n";
        $output .= "</style>\n";
        $output .= "</head>\n<body>\n";
        
        $output .= "<h1>" . esc_html($session['title']) . "</h1>\n";
        $output .= "<p class=\"meta\">Data utworzenia: " . esc_html($session['created_at']) . "</p>\n";
        
        foreach ($messages as $message) {
            $role_class = $message['type'] === 'user' ? 'user' : 'assistant';
            $role_name = $message['type'] === 'user' ? 'Użytkownik' : 'Claude';
            
            $output .= "<div class=\"message {$role_class}\">\n";
            $output .= "<div class=\"role\">{$role_name}</div>\n";
            $output .= "<div class=\"content\">" . $message['content'] . "</div>\n";
            $output .= "<div class=\"meta\">Czas: " . esc_html($message['created_at']) . "</div>\n";
            $output .= "</div>\n";
        }
        
        $output .= "</body>\n</html>";
        
        return $output;
    }
    
    /**
     * Formatuje rozmowę jako Markdown
     */
    private function format_conversation_as_markdown($session, $messages) {
        $output = "# " . $session['title'] . "\n\n";
        $output .= "Data utworzenia: " . $session['created_at'] . "\n\n";
        
        foreach ($messages as $message) {
            $role = $message['type'] === 'user' ? 'Użytkownik' : 'Claude';
            $output .= "## " . $role . "\n\n";
            
            // Konwertuj proste tagi HTML na markdown
            $content = preg_replace('/<pre><code.*?>(.*?)<\/code><\/pre>/s', "```\n$1\n```", $message['content']);
            $content = preg_replace('/<code>(.*?)<\/code>/s', '`$1`', $content);
            $content = preg_replace('/<strong>(.*?)<\/strong>/s', '**$1**', $content);
            $content = preg_replace('/<em>(.*?)<\/em>/s', '*$1*', $content);
            $content = preg_replace('/<ul>(.*?)<\/ul>/s', "$1", $content);
            $content = preg_replace('/<ol>(.*?)<\/ol>/s', "$1", $content);
            $content = preg_replace('/<li>(.*?)<\/li>/s', "- $1\n", $content);
            $content = preg_replace('/<a href="(.*?)".*?>(.*?)<\/a>/s', '[$2]($1)', $content);
            $content = preg_replace('/<p>(.*?)<\/p>/s', "$1\n\n", $content);
            $content = preg_replace('/<br\s*\/?>/s', "\n", $content);
            
            // Usuń inne tagi HTML
            $content = strip_tags($content);
            
            $output .= $content . "\n\n";
            $output .= "*Czas: " . $message['created_at'] . "*\n\n";
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