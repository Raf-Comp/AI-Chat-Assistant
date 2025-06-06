<?php
namespace AICA\Admin;
use AICA\Services\ChatService;

class HistoryPage {
    private $chat_service;

    public function __construct() {
        $this->chat_service = new ChatService();
        $this->init_ajax_handlers();
    }

    // Alias dla render_page wywoływany przez PageManager
    public function render() {
        $this->render_page();
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'ai-chat-assistant'));
        }

        // Przekazanie nonce do szablonu
        $history_nonce = wp_create_nonce('aica_history_nonce');
        
        // Dodanie skryptów i stylów
        wp_enqueue_style('aica-history-css', AICA_PLUGIN_URL . 'assets/css/history.css', array(), AICA_VERSION);
        wp_enqueue_script('aica-history-js', AICA_PLUGIN_URL . 'assets/js/history.js', array('jquery'), AICA_VERSION, true);
        
        // Przekazanie danych do skryptu
        wp_localize_script('aica-history-js', 'aica_history', array(
            'nonce' => $history_nonce,
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => array(
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'loading_messages' => __('Ładowanie wiadomości...', 'ai-chat-assistant'),
                'no_conversations' => __('Brak rozmów', 'ai-chat-assistant'),
                'no_conversations_desc' => __('Nie masz jeszcze żadnych rozmów.', 'ai-chat-assistant'),
                'new_conversation' => __('Nowa rozmowa', 'ai-chat-assistant'),
                'no_messages' => __('Brak wiadomości w tej rozmowie.', 'ai-chat-assistant'),
                'load_error' => __('Wystąpił błąd podczas ładowania danych.', 'ai-chat-assistant'),
                'confirm_delete' => __('Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.', 'ai-chat-assistant'),
                'delete_error' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant'),
                'duplicate_success' => __('Rozmowa została pomyślnie zduplikowana.', 'ai-chat-assistant'),
                'duplicate_error' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant'),
                'min_search_length' => __('Wprowadź co najmniej 3 znaki, aby wyszukać.', 'ai-chat-assistant'),
                'pagination_info' => __('Wyniki %1$s - %2$s z %3$s', 'ai-chat-assistant'),
                'user' => __('Użytkownik', 'ai-chat-assistant'),
                'continue_conversation' => __('Kontynuuj rozmowę', 'ai-chat-assistant'),
                'duplicate' => __('Duplikuj', 'ai-chat-assistant'),
                'export' => __('Eksportuj', 'ai-chat-assistant'),
                'delete' => __('Usuń', 'ai-chat-assistant'),
                'just_now' => __('Przed chwilą', 'ai-chat-assistant'),
                'second' => __('sekunda', 'ai-chat-assistant'),
                'seconds' => __('sekund', 'ai-chat-assistant'),
                'minute' => __('minuta', 'ai-chat-assistant'),
                'minutes' => __('minut', 'ai-chat-assistant'),
                'hour' => __('godzina', 'ai-chat-assistant'),
                'hours' => __('godzin', 'ai-chat-assistant'),
                'day' => __('dzień', 'ai-chat-assistant'),
                'days' => __('dni', 'ai-chat-assistant'),
                'month' => __('miesiąc', 'ai-chat-assistant'),
                'months' => __('miesięcy', 'ai-chat-assistant'),
                'year' => __('rok', 'ai-chat-assistant'),
                'years' => __('lat', 'ai-chat-assistant'),
                'ago' => __('temu', 'ai-chat-assistant')
            )
        ));
        
        include_once AICA_PLUGIN_DIR . 'templates/admin/history.php';
    }

    public function init_ajax_handlers() {
        add_action('wp_ajax_aica_get_sessions_list', [$this, 'ajax_get_sessions_list']);
        add_action('wp_ajax_aica_get_chat_history', [$this, 'ajax_get_chat_history']);
        add_action('wp_ajax_aica_delete_session', [$this, 'ajax_delete_session']);
        add_action('wp_ajax_aica_export_conversation', [$this, 'ajax_export_conversation']);
        add_action('wp_ajax_aica_duplicate_conversation', [$this, 'ajax_duplicate_conversation']);
    }
    
    // Pobieranie listy sesji
    public function ajax_get_sessions_list() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
            return;
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'newest';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        $offset = ($page - 1) * $per_page;
        
        $args = [
            'offset' => $offset,
            'limit' => $per_page,
            'search' => $search,
            'order' => $sort === 'oldest' ? 'ASC' : 'DESC',
        ];
        
        if (!empty($date_from)) {
            $args['date_from'] = $date_from;}
        
        if (!empty($date_to)) {
            $args['date_to'] = $date_to;
        }
        
        $sessions = $this->chat_service->get_sessions($args);
        $total_sessions = $this->chat_service->count_sessions($search, $date_from, $date_to);
        $total_pages = ceil($total_sessions / $per_page);
        
        wp_send_json_success([
            'sessions' => $sessions,
            'pagination' => [
                'total_items' => $total_sessions,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page
            ]
        ]);
    }
    
    // Pobieranie historii czatu dla konkretnej sesji
    public function ajax_get_chat_history() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak identyfikatora sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $session = $this->chat_service->get_session($session_id);
        
        if (!$session) {
            wp_send_json_error(['message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $messages = $this->chat_service->get_all_session_messages($session_id);
        
        wp_send_json_success([
            'messages' => $messages,
            'title' => $session->title,
            'session' => $session,
            'pagination' => [
                'total_items' => count($messages),
                'current_page' => 1,
            ]
        ]);
    }
    
    // Usuwanie sesji czatu
    public function ajax_delete_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak identyfikatora sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $result = $this->chat_service->delete_session($session_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Sesja została pomyślnie usunięta.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas usuwania sesji.', 'ai-chat-assistant')]);
        }
    }
    
    // Eksportowanie konwersacji
    public function ajax_export_conversation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak identyfikatora sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        
        $messages = $this->chat_service->get_all_session_messages($session_id);
        $session = $this->chat_service->get_session($session_id);
        
        if (empty($messages)) {
            wp_send_json_error(['message' => __('Brak wiadomości do eksportu.', 'ai-chat-assistant')]);
            return;
        }
        
        $export_data = [
            'session' => $session,
            'messages' => $messages,
            'export_date' => current_time('mysql')
        ];
        
        switch ($format) {
            case 'json':
                $content = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $mime_type = 'application/json';
                $extension = 'json';
                break;
            case 'txt':
                $content = $this->format_conversation_as_text($session, $messages);
                $mime_type = 'text/plain';
                $extension = 'txt';
                break;
            case 'html':
                $content = $this->format_conversation_as_html($session, $messages);
                $mime_type = 'text/html';
                $extension = 'html';
                break;
            default:
                wp_send_json_error(['message' => __('Nieobsługiwany format eksportu.', 'ai-chat-assistant')]);
                return;
        }
        
        $filename = 'ai-chat-' . sanitize_title($session->title) . '-' . date('Y-m-d') . '.' . $extension;
        
        wp_send_json_success([
            'content' => $content,
            'filename' => $filename,
            'mime_type' => $mime_type
        ]);
    }
    
    // Duplikowanie konwersacji
    public function ajax_duplicate_conversation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
            return;
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Brak identyfikatora sesji.', 'ai-chat-assistant')]);
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $result = $this->chat_service->duplicate_session($session_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Konwersacja została pomyślnie zduplikowana.', 'ai-chat-assistant'),
                'new_session_id' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas duplikowania konwersacji.', 'ai-chat-assistant')]);
        }
    }
    
    // Pomocnicza metoda do formatowania konwersacji jako tekst
    private function format_conversation_as_text($session, $messages) {
        $output = "Tytuł: " . $session->title . "\n";
        $output .= "Data utworzenia: " . $session->created_at . "\n";
        $output .= "Ostatnia aktualizacja: " . $session->updated_at . "\n\n";
        $output .= "Historia konwersacji:\n\n";
        
        foreach ($messages as $message) {
            $role = $message->type === 'user' ? 'Użytkownik' : 'Asystent';
            $output .= "[" . $message->time . "] " . $role . ":\n";
            $output .= $message->content . "\n\n";
        }
        
        return $output;
    }
    
    // Pomocnicza metoda do formatowania konwersacji jako HTML
    private function format_conversation_as_html($session, $messages) {
        $output = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Eksport konwersacji: ' . esc_html($session->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .session-info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message { margin-bottom: 15px; padding: 10px 15px; border-radius: 5px; }
        .user { background: #e3f2fd; border-left: 4px solid #2196F3; }
        .assistant { background: #f1f8e9; border-left: 4px solid #8bc34a; }
        .message-time { font-size: 12px; color: #666; margin-bottom: 5px; }
        .message-content { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="session-info">
        <h1>' . esc_html($session->title) . '</h1>
        <p>Data utworzenia: ' . esc_html($session->created_at) . '</p>
        <p>Ostatnia aktualizacja: ' . esc_html($session->updated_at) . '</p>
    </div>
    <div class="conversation">';
        
        foreach ($messages as $message) {
            $role = $message->type === 'user' ? 'Użytkownik' : 'Asystent';
            $class = $message->type === 'user' ? 'user' : 'assistant';
            
            $output .= '
        <div class="message ' . $class . '">
            <div class="message-time">[' . esc_html($message->time) . '] ' . esc_html($role) . ':</div>
            <div class="message-content">' . nl2br(esc_html($message->content)) . '</div>
        </div>';
        }
        
        $output .= '
    </div>
    <div class="export-info">
        <p>Wyeksportowano: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
        
        return $output;
    }
}