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
                'X-API-Key' => $this->api_key,
                'Anthropic-Version' => '2023-06-01',
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
                'X-API-Key' => $this->api_key,
                'Anthropic-Version' => '2023-06-01',
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
     */
    public function get_available_models() {
        $args = [
            'method' => 'GET',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'Anthropic-Version' => '2023-06-01',
            ]
        ];

        // Wysłanie żądania do endpointu modeli
        $response = wp_remote_get('https://api.anthropic.com/v1/models', $args);

        if (is_wp_error($response)) {
            aica_log('Błąd pobierania modeli Claude: ' . $response->get_error_message(), 'error');
            return [];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            aica_log('Błąd pobierania modeli Claude (HTTP ' . $status_code . ')', 'error');
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $models = [];

        if (isset($body['models']) && is_array($body['models'])) {
            foreach ($body['models'] as $model) {
                $models[$model['id']] = $model['name'];
            }
            aica_log('Pobrano modele Claude: ' . implode(', ', array_keys($models)));
        }

        // Jeśli API nie zwróciło listy modeli, zwróć domyślną listę
        if (empty($models)) {
            $models = [
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku'
            ];
            aica_log('Używam domyślnej listy modeli Claude', 'warning');
        }

        return $models;
    }
}