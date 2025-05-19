<?php
namespace AICA\Services;

class ChatService {
    /**
     * Tworzy nową sesję czatu
     */
    public function create_session($title = '') {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $session_id = wp_generate_uuid4();
        $user_id = get_current_user_id();
        
        if (empty($title)) {
            $title = __('Nowa rozmowa', 'ai-chat-assistant');
        }
        
        $wpdb->insert(
            $sessions_table,
            [
                'session_id' => $session_id,
                'user_id' => $user_id,
                'title' => $title,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
        
        if ($wpdb->last_error) {
            return false;
        }
        
        return $session_id;
    }
    
    /**
     * Aktualizuje tytuł sesji
     */
    public function update_session_title($session_id, $title) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $result = $wpdb->update(
            $sessions_table,
            [
                'title' => $title,
                'updated_at' => current_time('mysql')
            ],
            ['session_id' => $session_id]
        );
        
        return $result !== false;
    }
    
    /**
     * Wysyła wiadomość i zapisuje odpowiedź
     */
    public function send_message($session_id, $message, $model = 'claude-3-opus-20240229') {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_sessions_messages';
        
        // Najpierw zapisujemy wiadomość użytkownika
        $wpdb->insert(
            $messages_table,
            [
                'session_id' => $session_id,
                'message' => $message,
                'created_at' => current_time('mysql')
            ]
        );
        
        $message_id = $wpdb->insert_id;
        
        if (!$message_id) {
            return [
                'success' => false,
                'error' => __('Nie udało się zapisać wiadomości.', 'ai-chat-assistant')
            ];
        }
        
        // Aktualizacja czasu ostatniej modyfikacji sesji
        $this->update_session_time($session_id);
        
        // Pobierz poprzednie wiadomości, aby zbudować kontekst rozmowy
        $history = $this->get_conversation_history($session_id);
        
        // Wywołanie API Claude (przykładowa implementacja)
        try {
            $api_key = get_option('aica_api_key', '');
            if (empty($api_key)) {
                throw new \Exception(__('Klucz API nie został skonfigurowany.', 'ai-chat-assistant'));
            }
            
            $api_response = $this->call_claude_api($history, $message, $model, $api_key);
            
            // Zapisz odpowiedź w bazie danych
            $wpdb->update(
                $messages_table,
                ['response' => $api_response],
                ['id' => $message_id]
            );
            
            return [
                'success' => true,
                'response' => $api_response
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Aktualizuje czas ostatniej modyfikacji sesji
     */
    private function update_session_time($session_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        
        $wpdb->update(
            $sessions_table,
            ['updated_at' => current_time('mysql')],
            ['session_id' => $session_id]
        );
    }
    
    /**
     * Pobiera historię konwersacji dla sesji
     */
    private function get_conversation_history($session_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT message, response FROM $messages_table WHERE session_id = %s ORDER BY id ASC",
            $session_id
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Wywołuje API Claude
     */
    private function call_claude_api($history, $message, $model, $api_key) {
        // Implementacja wywołania API Claude
        // To jest przykładowa implementacja, którą należy dostosować do używanego API
        
        $messages = [];
        
        // Dodaj poprzednie wiadomości
        foreach ($history as $item) {
            if (!empty($item['message'])) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $item['message']
                ];
            }
            
            if (!empty($item['response'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $item['response']
                ];
            }
        }
        
        // Dodaj bieżącą wiadomość
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 4096
        ];
        
        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01'
                ],
                'body' => json_encode($data),
                'timeout' => 60
            ]
        );
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            throw new \Exception($body['error']['message']);
        }
        
        return $body['content'][0]['text'];
    }
    
    /**
     * Pobiera sesje z filtrowaniem
     */
    public function get_sessions($args = []) {
        $defaults = [
            'offset' => 0,
            'limit' => 10,
            'search' => '',
            'order' => 'DESC',
            'date_from' => '',
            'date_to' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $query = "SELECT * FROM $table WHERE 1=1";
        $query_args = [];
        
        if (!empty($args['search'])) {
            $query .= " AND title LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        $query .= " ORDER BY created_at " . $args['order'];
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['limit'];
        $query_args[] = $args['offset'];
        
        $prepared_query = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
        
        // Dodaj podglądy pierwszej wiadomości do każdej sesji
        foreach ($results as &$session) {
            $preview = $this->get_session_preview($session['session_id']);
            $session['preview'] = $preview ? $preview : '';
        }
        
        return $results;
    }
    
    /**
     * Liczy łączną liczbę sesji z filtrowaniem
     */
    public function count_sessions($search = '', $date_from = '', $date_to = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $query = "SELECT COUNT(*) FROM $table WHERE 1=1";
        $query_args = [];
        
        if (!empty($search)) {
            $query .= " AND title LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if (!empty($date_from)) {
            $query .= " AND created_at >= %s";
            $query_args[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $query .= " AND created_at <= %s";
            $query_args[] = $date_to . ' 23:59:59';
        }
        
        $prepared_query = empty($query_args) ? $query : $wpdb->prepare($query, $query_args);
        return intval($wpdb->get_var($prepared_query));
    }
    
    /**
     * Pobiera podgląd pierwszej wiadomości sesji
     */
    public function get_session_preview($session_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT message FROM $messages_table WHERE session_id = %s ORDER BY id ASC LIMIT 1",
            $session_id
        );
        
        $message = $wpdb->get_var($query);
        
        if ($message) {
            return wp_trim_words($message, 20, '...');
        }
        
        return '';
    }
    
    /**
     * Pobiera pojedynczą sesję po ID
     */
    public function get_session($session_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'aica_sessions';
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s",
            $session_id
        );
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Pobiera wiadomości sesji z paginacją
     */
    public function get_session_messages($session_id, $page = 1, $per_page = 5) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $offset = ($page - 1) * $per_page;
        
        $query = $wpdb->prepare(
            "SELECT id, message as content, 'user' as type, created_at as time FROM $messages_table WHERE session_id = %s
            UNION ALL
            SELECT id, response as content, 'ai' as type, created_at as time FROM $messages_table WHERE session_id = %s AND response IS NOT NULL
            ORDER BY time ASC, id ASC
            LIMIT %d OFFSET %d",
            $session_id, $session_id, $per_page, $offset
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Pobiera wszystkie wiadomości sesji
     */
    public function get_all_session_messages($session_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT id, message as content, 'user' as type, created_at as time FROM $messages_table WHERE session_id = %s
            UNION ALL
            SELECT id, response as content, 'ai' as type, created_at as time FROM $messages_table WHERE session_id = %s AND response IS NOT NULL
            ORDER BY time ASC, id ASC",
            $session_id, $session_id
        );
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * Liczy liczbę wiadomości w sesji
     */
    public function count_session_messages($session_id) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM (
                SELECT id FROM $messages_table WHERE session_id = %s
                UNION ALL
                SELECT id FROM $messages_table WHERE session_id = %s AND response IS NOT NULL
            ) as messages",
            $session_id, $session_id
        );
        
        return intval($wpdb->get_var($query));
    }
    
    /**
     * Usuwa sesję i jej wiadomości
     */
    public function delete_session($session_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Rozpoczęcie transakcji
        $wpdb->query('START TRANSACTION');
        
        // Usunięcie wiadomości
        $delete_messages = $wpdb->prepare(
            "DELETE FROM $messages_table WHERE session_id = %s",
            $session_id
        );
        $wpdb->query($delete_messages);
        
        // Usunięcie sesji
        $delete_session = $wpdb->prepare(
            "DELETE FROM $sessions_table WHERE session_id = %s",
            $session_id
        );
        $result = $wpdb->query($delete_session);
        
        // Zatwierdzenie transakcji
        if ($result !== false) {
            $wpdb->query('COMMIT');
            return true;
        } else {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }
    
    /**
     * Duplikuje sesję i jej wiadomości
     */
    public function duplicate_session($session_id) {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'aica_sessions';
        $messages_table = $wpdb->prefix . 'aica_messages';
        
        // Pobranie oryginalnej sesji
        $original_session = $this->get_session($session_id);
        if (!$original_session) {
            return false;
        }
        
        // Utworzenie nowego ID sesji
        $new_session_id = wp_generate_uuid4();
        
        // Kopia tytułu z informacją o duplikacie
        $new_title = sprintf(__('%s (kopia)', 'ai-chat-assistant'), $original_session->title);
        
        // Rozpoczęcie transakcji
        $wpdb->query('START TRANSACTION');
        
        // Utworzenie nowej sesji
        $insert_session = $wpdb->prepare(
            "INSERT INTO $sessions_table (session_id, user_id, title, created_at, updated_at) VALUES (%s, %d, %s, %s, %s)",
            $new_session_id,
            get_current_user_id(),
            $new_title,
            current_time('mysql'),
            current_time('mysql')
        );
        $wpdb->query($insert_session);
        
        // Skopiowanie wiadomości
        $original_messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message, response, created_at FROM $messages_table WHERE session_id = %s ORDER BY id ASC",
                $session_id
            ),
            ARRAY_A
        );
        
        foreach ($original_messages as $message) {
            $wpdb->insert(
                $messages_table,
                [
                    'session_id' => $new_session_id,
                    'message' => $message['message'],
                    'response' => $message['response'],
                    'created_at' => current_time('mysql')
                ]
            );
        }
        
        // Zatwierdzenie transakcji
        if ($wpdb->last_error) {
            $wpdb->query('ROLLBACK');
            return false;
        } else {
            $wpdb->query('COMMIT');
            return $new_session_id;
        }
    }
    
    /**
     * Pobiera wszystkie sesje dla bieżącego użytkownika
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function get_conversations($args = []) {
        return $this->get_sessions($args);
    }
    
    /**
     * Liczy sesje dla bieżącego użytkownika
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function count_conversations($search = '', $date_from = '', $date_to = '') {
        return $this->count_sessions($search, $date_from, $date_to);
    }
    
    /**
     * Pobiera pojedynczą rozmowę
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function get_conversation($conversation_id) {
        return $this->get_session($conversation_id);
    }
    
    /**
     * Pobiera wiadomości z rozmowy
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function get_messages($conversation_id, $page = 1, $per_page = 5) {
        return $this->get_session_messages($conversation_id, $page, $per_page);
    }
    
    /**
     * Pobiera wszystkie wiadomości z rozmowy
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function get_all_messages($conversation_id) {
        return $this->get_all_session_messages($conversation_id);
    }
    
    /**
     * Liczy wiadomości w rozmowie
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function count_messages($conversation_id) {
        return $this->count_session_messages($conversation_id);
    }
    
    /**
     * Usuwa rozmowę
     * Metoda pozostawiona dla kompatybilności z poprzednimi wersjami
     */
    public function delete_conversation($conversation_id) {
        return $this->delete_session($conversation_id);
    }

    public function get_all_sessions() {
        $sessions = get_option('aica_chat_sessions', []);
        return is_array($sessions) ? $sessions : [];
    }

}