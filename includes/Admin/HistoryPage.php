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
            $args['date_from'] = $date_from;
        }
        
        if (!empty($date_to)) {
            $args['date_to'] = $date_to;
        }
        
        $sessions = $this->chat_service->get_sessions($args);
        $total_sessions = $this->chat_service->count_sessions($search, $date_from, $date_to);
        $total_pages = ceil($total_sessions / $per_page);
        
        wp_send_json_success([
            'sessions' => $sessions,
            'total' => $total_sessions,
            'total_pages' => $total_pages,
            'current_page' => $page
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
        $messages = $this->chat_service->get_messages_by_session_id($session_id);
        $session_info = $this->chat_service->get_session_by_id($session_id);
        
        wp_send_json_success([
            'messages' => $messages,
            'session_info' => $session_info
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
        
        $messages = $this->chat_service->get_messages_by_session_id($session_id);
        $session_info = $this->chat_service->get_session_by_id($session_id);
        
        if (empty($messages)) {
            wp_send_json_error(['message' => __('Brak wiadomości do eksportu.', 'ai-chat-assistant')]);
            return;
        }
        
        $export_data = [
            'session_info' => $session_info,
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
                $content = $this->format_conversation_as_text($session_info, $messages);
                $mime_type = 'text/plain';
                $extension = 'txt';
                break;
            case 'html':
                $content = $this->format_conversation_as_html($session_info, $messages);
                $mime_type = 'text/html';
                $extension = 'html';
                break;
            default:
                wp_send_json_error(['message' => __('Nieobsługiwany format eksportu.', 'ai-chat-assistant')]);
                return;
        }
        
        $filename = 'ai-chat-' . sanitize_title($session_info->title) . '-' . date('Y-m-d') . '.' . $extension;
        
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
    private function format_conversation_as_text($session_info, $messages) {
        $output = "Tytuł: " . $session_info->title . "\n";
        $output .= "Data utworzenia: " . $session_info->created_at . "\n";
        $output .= "Ostatnia aktualizacja: " . $session_info->updated_at . "\n\n";
        $output .= "Historia konwersacji:\n\n";
        
        foreach ($messages as $message) {
            $role = $message->role === 'user' ? 'Użytkownik' : 'Asystent';
            $output .= "[" . $message->created_at . "] " . $role . ":\n";
            $output .= $message->content . "\n\n";
        }
        
        return $output;
    }
    
    // Pomocnicza metoda do formatowania konwersacji jako HTML
    private function format_conversation_as_html($session_info, $messages) {
        $output = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Eksport konwersacji: ' . esc_html($session_info->title) . '</title>
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
        <h1>' . esc_html($session_info->title) . '</h1>
        <p>Data utworzenia: ' . esc_html($session_info->created_at) . '</p>
        <p>Ostatnia aktualizacja: ' . esc_html($session_info->updated_at) . '</p>
    </div>
    <div class="conversation">';
        
        foreach ($messages as $message) {
            $role = $message->role === 'user' ? 'Użytkownik' : 'Asystent';
            $class = $message->role === 'user' ? 'user' : 'assistant';
            
            $output .= '
        <div class="message ' . $class . '">
            <div class="message-time">[' . esc_html($message->created_at) . '] ' . esc_html($role) . ':</div>
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