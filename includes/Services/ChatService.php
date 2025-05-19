<?php
namespace AICA\Services;

use AICA\API\ClaudeClient;

class ChatService {
    private $db;
    private $claude_client;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        // Inicjalizacja klienta Claude
        $api_key = get_option('aica_claude_api_key', '');
        if (!empty($api_key)) {
            $this->claude_client = new ClaudeClient($api_key);
        }
    }

    /**
     * Tworzenie nowej sesji czatu
     */
    public function create_session() {
        $session_id = 'sess_' . uniqid() . '_' . wp_generate_password(8, false, false);
        $user_id = get_current_user_id();
        $title = __('Nowa rozmowa', 'ai-chat-assistant');
        $now = current_time('mysql');

        $result = $this->db->insert(
            $this->db->prefix . 'aica_sessions',
            [
                'session_id' => $session_id,
                'user_id' => $user_id,
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%s', '%d', '%s', '%s', '%s']
        );

        return $result ? $session_id : false;
    }

    /**
     * Wysyłanie wiadomości do Claude i zapisywanie odpowiedzi
     */
    public function send_message($session_id, $message, $file_path = '') {
        // Sprawdzenie czy klient API jest zainicjalizowany
        if (!$this->claude_client) {
            return [
                'success' => false,
                'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
            ];
        }

        // Pobranie modelu z ustawień
        $model = get_option('aica_claude_model', 'claude-3-haiku-20240307');
        $max_tokens = intval(get_option('aica_max_tokens', 4000));

        // Przygotowanie wiadomości z załącznikiem pliku, jeśli istnieje
        $content = $message;
        
        if (!empty($file_path) && file_exists($file_path)) {
            $file_content = file_get_contents($file_path);
            $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            $language = $this->get_language_from_extension($file_extension);
            
            $content .= "\n\n```$language\n$file_content\n```";
        }

        // Pobranie historii rozmowy dla kontekstu
        $history = $this->get_raw_chat_history($session_id, 10);
        
        // Wysłanie wiadomości do Claude
        $response = $this->claude_client->send_message($content, $history, $model, $max_tokens);
        
        if (!$response['success']) {
            return $response;
        }
        
        // Zapisanie wiadomości i odpowiedzi w bazie danych
        $this->save_message($session_id, $message, $response['message'], $response['tokens_used'], $model);
        
        // Aktualizacja czasu ostatniej aktywności sesji
        $this->update_session_timestamp($session_id);
        
        // Aktualizacja tytułu sesji, jeśli to pierwsza wiadomość
        $this->update_session_title($session_id, $message);
        
        return $response;
    }

    /**
     * Zapisanie wiadomości i odpowiedzi w bazie danych
     */
    private function save_message($session_id, $message, $response, $tokens_used, $model) {
        $now = current_time('mysql');
        
        $this->db->insert(
            $this->db->prefix . 'aica_messages',
            [
                'session_id' => $session_id,
                'message' => $message,
                'response' => $response,
                'tokens_used' => $tokens_used,
                'model' => $model,
                'created_at' => $now
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Aktualizacja tytułu sesji na podstawie pierwszej wiadomości
     */
    private function update_session_title($session_id, $message) {
        // Sprawdź, czy to pierwsza wiadomość w sesji
        $table = $this->db->prefix . 'aica_messages';
        $count = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM $table WHERE session_id = %s",
                $session_id
            )
        );
        
        // Jeśli to pierwsza wiadomość, zaktualizuj tytuł sesji
        if ($count <= 1) {
            // Przygotowanie tytułu (pierwsze 30 znaków wiadomości)
            $title = wp_trim_words($message, 5, '...');
            if (strlen($title) > 50) {
                $title = substr($title, 0, 47) . '...';
            }
            
            // Aktualizacja tytułu
            $this->db->update(
                $this->db->prefix . 'aica_sessions',
                ['title' => $title],
                ['session_id' => $session_id],
                ['%s'],
                ['%s']
            );
        }
    }

    /**
     * Pobranie historii czatu dla sesji - sformatowanej do wyświetlenia z paginacją
     */
    public function get_chat_history($session_id, $page = 1, $per_page = 20) {
        $table = $this->db->prefix . 'aica_messages';
        $offset = ($page - 1) * $per_page;
        
        // Najpierw pobierz całkowitą liczbę wiadomości dla tej sesji
        $total_count = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM $table WHERE session_id = %s",
                $session_id
            )
        );
        
        // Pobierz wiadomości dla bieżącej strony
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT message, response, created_at FROM $table 
                 WHERE session_id = %s 
                 ORDER BY created_at DESC 
                 LIMIT %d OFFSET %d",
                $session_id, $per_page, $offset
            ),
            ARRAY_A
        );
        
        // Odwróć kolejność, aby wiadomości były w kolejności chronologicznej
        $results = array_reverse($results);
        
        $formatted_history = [];
        foreach ($results as $row) {
            $formatted_history[] = [
                'type' => 'user',
                'content' => $row['message'],
                'time' => $row['created_at']
            ];
            
            $formatted_history[] = [
                'type' => 'assistant',
                'content' => $row['response'],
                'time' => $row['created_at']
            ];
        }
        
        // Informacje o paginacji
        $total_pages = ceil($total_count / $per_page);
        
        return [
            'messages' => $formatted_history,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => (int)$total_pages,
                'total_messages' => (int)$total_count,
                'per_page' => (int)$per_page
            ]
        ];
    }

    /**
     * Pobranie surowej historii czatu dla kontekstu Claude.ai
     */
    private function get_raw_chat_history($session_id, $limit = 10) {
        $table = $this->db->prefix . 'aica_messages';
        
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT message, response FROM $table WHERE session_id = %s ORDER BY created_at DESC LIMIT %d",
                $session_id, $limit
            ),
            ARRAY_A
        );
        
        // Odwrócenie, aby najstarsze były pierwsze
        $results = array_reverse($results);
        
        // Przygotowanie historii w formacie wymaganym przez Claude API
        $history = [];
        foreach ($results as $row) {
            $history[] = [
                'role' => 'user',
                'content' => $row['message']
            ];
            
            $history[] = [
                'role' => 'assistant',
                'content' => $row['response']
            ];
        }
        
        return $history;
    }

    /**
     * Aktualizacja czasu ostatniej aktywności sesji
     */
    private function update_session_timestamp($session_id) {
        $table = $this->db->prefix . 'aica_sessions';
        $now = current_time('mysql');
        
        $this->db->update(
            $table,
            ['updated_at' => $now],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
    }

    /**
     * Pobranie sesji użytkownika z uwzględnieniem filtrów
     */
    public function get_user_sessions($user_id, $search_term = '', $sort_order = 'newest', $date_from = '', $date_to = '') {
        $table = $this->db->prefix . 'aica_sessions';
        $messages_table = $this->db->prefix . 'aica_messages';
        
        // Podstawowe zapytanie
        $query = "SELECT s.session_id, s.title, s.created_at, s.updated_at FROM $table s WHERE s.user_id = %d";
        $params = [$user_id];
        
        // Dodanie wyszukiwania
        if (!empty($search_term)) {
            $query .= " AND (s.title LIKE %s OR s.session_id IN (
                SELECT DISTINCT m.session_id FROM $messages_table m 
                WHERE m.session_id = s.session_id AND (m.message LIKE %s OR m.response LIKE %s)
            ))";
            $search_pattern = '%' . $this->db->esc_like($search_term) . '%';
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $params[] = $search_pattern;
        }
        
        // Dodanie filtru daty
        if (!empty($date_from)) {
            $query .= " AND s.created_at >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND s.created_at <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        // Dodanie sortowania
        if ($sort_order == 'oldest') {
            $query .= " ORDER BY s.created_at ASC";
        } else {
            $query .= " ORDER BY s.updated_at DESC";
        }
        
        // Przygotowanie i wykonanie zapytania
        $prepared_query = $this->db->prepare($query, $params);
        $results = $this->db->get_results($prepared_query, ARRAY_A);
        
        return $results ?: [];
    }

    /**
     * Ustalenie języka programowania na podstawie rozszerzenia pliku
     */
    private function get_language_from_extension($extension) {
        $language_map = [
            'php' => 'php',
            'js' => 'javascript',
            'jsx' => 'jsx',
            'ts' => 'typescript',
            'tsx' => 'tsx',
            'html' => 'html',
            'css' => 'css',
            'scss' => 'scss',
            'json' => 'json',
            'py' => 'python',
            'rb' => 'ruby',
            'java' => 'java',
            'c' => 'c',
            'cpp' => 'cpp',
            'cs' => 'csharp',
            'go' => 'go',
            'rs' => 'rust',
            'swift' => 'swift',
            'md' => 'markdown',
            'txt' => 'text'
        ];
        
        return isset($language_map[$extension]) ? $language_map[$extension] : '';
    }

    /**
     * Usunięcie sesji czatu
     */
    public function delete_session($session_id) {
        $user_id = get_current_user_id();
        
        // Najpierw sprawdź, czy sesja należy do użytkownika
        $table_sessions = $this->db->prefix . 'aica_sessions';
        $session_exists = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM $table_sessions WHERE session_id = %s AND user_id = %d",
                $session_id, $user_id
            )
        );
        
        if (!$session_exists) {
            return false;
        }
        
        // Usuń wiadomości
        $table_messages = $this->db->prefix . 'aica_messages';
        $this->db->delete(
            $table_messages,
            ['session_id' => $session_id],
            ['%s']
        );
        
        // Usuń sesję
        $result = $this->db->delete(
            $table_sessions,
            ['session_id' => $session_id, 'user_id' => $user_id],
            ['%s', '%d']
        );
        
        return $result !== false;
    }
}