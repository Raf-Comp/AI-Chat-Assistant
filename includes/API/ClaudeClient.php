<?php
namespace AICA\API;

class ClaudeClient {
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Testowanie połączenia z API
     */
    public function test_connection() {
        // Przygotowanie prostej wiadomości testowej
        $args = [
            'method' => 'POST',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 10,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello, this is a test. Please respond with "Test successful."'
                    ]
                ]
            ])
        ];

        // Wysłanie żądania
        $response = wp_remote_post($this->api_url, $args);

        // Sprawdzenie odpowiedzi
        if (is_wp_error($response)) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code >= 200 && $status_code < 300;
    }

    /**
     * Wysłanie wiadomości do Claude.ai
     */
    public function send_message($message, $history = [], $model = 'claude-3-haiku-20240307', $max_tokens = 4000) {
        // Połączenie historii i nowej wiadomości
        $messages = $history;
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Usunięcie duplikatów ról (dwa user messages pod rząd)
        $cleaned_messages = [];
        $last_role = null;
        
        foreach ($messages as $msg) {
            if ($last_role === $msg['role']) {
                // Jeśli poprzednia wiadomość była tej samej roli, połącz je
                $last_index = count($cleaned_messages) - 1;
                $cleaned_messages[$last_index]['content'] .= "\n\n" . $msg['content'];
            } else {
                // Dodaj nową wiadomość
                $cleaned_messages[] = $msg;
                $last_role = $msg['role'];
            }
        }

        // Parametry żądania
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => $model,
                'max_tokens' => $max_tokens,
                'messages' => $cleaned_messages
            ])
        ];

        // Zapisanie loga przed wysłaniem żądania
        aica_log('Wysyłanie żądania do Claude API: ' . json_encode([
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages_count' => count($cleaned_messages)
        ]));

        // Wysłanie żądania
        $response = wp_remote_post($this->api_url, $args);

        // Obsługa błędów
        if (is_wp_error($response)) {
            aica_log('Błąd Claude API: ' . $response->get_error_message(), 'error');
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status_code < 200 || $status_code >= 300) {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Błąd API Claude', 'ai-chat-assistant');
            aica_log('Błąd Claude API (HTTP ' . $status_code . '): ' . $error_message, 'error');
            return [
                'success' => false,
                'message' => $error_message,
                'status_code' => $status_code
            ];
        }

        // Ekstrakcja odpowiedzi
        $assistant_message = isset($body['content'][0]['text']) ? $body['content'][0]['text'] : '';
        $tokens_used = isset($body['usage']['output_tokens']) ? $body['usage']['output_tokens'] : 0;

        aica_log('Odpowiedź Claude API: Otrzymano ' . $tokens_used . ' tokenów');

        return [
            'success' => true,
            'message' => $assistant_message,
            'tokens_used' => $tokens_used,
            'model' => $model
        ];
    }

    /**
     * Pobranie dostępnych modeli
     * Zaktualizowana metoda pobierania modeli z API Anthropic
     */
    public function get_available_models() {
        $args = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ]
        ];

        // Wysłanie żądania do endpointu modeli
        $response = wp_remote_get('https://api.anthropic.com/v1/models', $args);

        if (is_wp_error($response)) {
            aica_log('Błąd pobierania modeli Claude: ' . $response->get_error_message(), 'error');
            return $this->get_default_models();
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            aica_log('Błąd pobierania modeli Claude (HTTP ' . $status_code . ')', 'error');
            return $this->get_default_models();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $models = [];

        if (isset($body['data']) && is_array($body['data'])) {
            foreach ($body['data'] as $model) {
                if (isset($model['id'])) {
                    // Formatowanie przyjaznej nazwy modelu
                    $name = $this->format_model_name($model['id']);
                    $models[$model['id']] = $name;
                }
            }
            
            if (!empty($models)) {
                aica_log('Pobrano modele Claude: ' . implode(', ', array_keys($models)));
                
                // Zapisanie pobranych modeli w opcjach wtyczki
                aica_update_option('claude_available_models', $models);
                aica_update_option('claude_models_last_update', current_time('mysql'));
                
                return $models;
            }
        }

        // Jeśli API nie zwróciło listy modeli lub lista jest pusta, sprawdz cache
        $cached_models = aica_get_option('claude_available_models', []);
        if (!empty($cached_models)) {
            aica_log('Używam zapisanych modeli Claude z cache', 'info');
            return $cached_models;
        }
        
        // W ostateczności zwróć domyślną listę
        return $this->get_default_models();
    }
    
    /**
     * Formatuje nazwę modelu na bardziej przyjazną dla użytkownika
     */
    private function format_model_name($model_id) {
        // Usuń datę z ID modelu
        $base_name = preg_replace('/-\d{8}$/', '', $model_id);
        
        // Zamień myślniki na spacje i zastosuj wielkie litery
        $friendly_name = str_replace('-', ' ', $base_name);
        $friendly_name = ucwords($friendly_name);
        
        // Specjalne przypadki
        if (strpos($model_id, 'claude-3') !== false) {
            // Dla modeli Claude 3, dodaj wersję z daty
            $date_part = substr($model_id, -8);
            $year = substr($date_part, 0, 4);
            $month = substr($date_part, 4, 2);
            $day = substr($date_part, 6, 2);
            
            return $friendly_name . " ($year-$month-$day)";
        }
        
        return $friendly_name;
    }
    
    /**
     * Zwraca domyślną listę modeli Claude
     */
    private function get_default_models() {
        $default_models = [
            'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
            'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
            'claude-2.1' => 'Claude 2.1',
            'claude-2.0' => 'Claude 2.0',
            'claude-instant-1.2' => 'Claude Instant 1.2'
        ];
        
        aica_log('Używam domyślnej listy modeli Claude', 'warning');
        return $default_models;
    }
}