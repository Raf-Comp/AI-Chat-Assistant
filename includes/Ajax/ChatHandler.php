<?php
namespace AICA\Ajax;

class ChatHandler {
    public function send_message() {
        // Sprawdzenie nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_nonce')) {
            wp_send_json_error([
                'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdzenie treści wiadomości
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            wp_send_json_error([
                'message' => __('Wiadomość nie może być pusta.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Nie znaleziono użytkownika.', 'ai-chat-assistant')
            ]);
            return;
        }
        
        // Sprawdź czy sesja istnieje i należy do użytkownika
        if (!empty($session_id)) {
            if (!$this->user_owns_session($user_id, $session_id)) {
                // Utwórz nową sesję, jeśli obecna nie należy do użytkownika
                $title = __('Nowa rozmowa', 'ai-chat-assistant');
                $session_id = $this->create_new_session($user_id, $title);
                
                if (!$session_id) {
                    wp_send_json_error([
                        'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
                    ]);
                    return;
                }
            }
        } else {
            // Utwórz nową sesję
            $title = __('Nowa rozmowa', 'ai-chat-assistant');
            $session_id = $this->create_new_session($user_id, $title);
            
            if (!$session_id) {
                wp_send_json_error([
                    'message' => __('Nie udało się utworzyć nowej sesji.', 'ai-chat-assistant')
                ]);
                return;
            }
        }
        
        // Pobranie ustawień Claude - korzystamy z głównych, globalnych ustawień
$api_key = aica_get_option('claude_api_key', '');
$model = aica_get_option('claude_model', 'claude-3-haiku-20240307');
$max_tokens = intval(aica_get_option('max_tokens', 4000));
$temperature = floatval(aica_get_option('temperature', 0.7));

if (empty($api_key)) {
    wp_send_json_error([
        'message' => __('Klucz API Claude nie jest skonfigurowany.', 'ai-chat-assistant')
    ]);
    return;
}

// Dodanie logowania w trybie debugowania
if (aica_get_option('debug_mode', false)) {
    aica_log('Wysyłanie zapytania do Claude API. Model: ' . $model);
}

try {
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
        return;
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
} catch (\Exception $e) {
    if (aica_get_option('debug_mode', false)) {
        aica_log('Błąd API: ' . $e->getMessage(), 'error');
    }
    
    wp_send_json_error([
        'message' => $e->getMessage()
    ]);
}
    }
}