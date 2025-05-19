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
        add_action('wp_ajax_aica_export_session', [$this, 'ajax_export_session']);
        add_action('wp_ajax_aica_duplicate_session', [$this, 'ajax_duplicate_session']);
    }
    
    // Pobieranie listy sesji
    public function ajax_get_sessions_list() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
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
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_sessions,
                'total_pages' => $total_pages
            ]
        ]);
    }
    
    // Pobieranie historii czatu
    public function ajax_get_chat_history() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID sesji.', 'ai-chat-assistant')]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;
        
        $messages = $this->chat_service->get_session_messages($session_id, $page, $per_page);
        $total_messages = $this->chat_service->count_session_messages($session_id);
        $total_pages = ceil($total_messages / $per_page);
        
        wp_send_json_success([
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_messages,
                'total_pages' => $total_pages
            ]
        ]);
    }
    
    // Usuwanie sesji
    public function ajax_delete_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID sesji.', 'ai-chat-assistant')]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $result = $this->chat_service->delete_session($session_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Sesja została pomyślnie usunięta.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas usuwania sesji.', 'ai-chat-assistant')]);
        }
    }
    
    // Eksport sesji
    public function ajax_export_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID sesji.', 'ai-chat-assistant')]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $session = $this->chat_service->get_session($session_id);
        $messages = $this->chat_service->get_all_session_messages($session_id);
        
        if (!$session) {
            wp_send_json_error(['message' => __('Nie znaleziono sesji.', 'ai-chat-assistant')]);
        }
        
        $filename = sanitize_file_name($session->title) . '-' . date('Y-m-d') . '.json';
        $content = json_encode([
            'session_id' => $session_id,
            'title' => $session->title,
            'created_at' => $session->created_at,
            'messages' => $messages
        ], JSON_PRETTY_PRINT);
        
        wp_send_json_success([
            'filename' => $filename,
            'content' => $content
        ]);
    }
    
    // Duplikowanie sesji
    public function ajax_duplicate_session() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID sesji.', 'ai-chat-assistant')]);
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $new_session_id = $this->chat_service->duplicate_session($session_id);
        
        if ($new_session_id) {
            wp_send_json_success([
                'message' => __('Rozmowa została pomyślnie zduplikowana.', 'ai-chat-assistant'),
                'new_id' => $new_session_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant')]);
        }
    }

    public function get_sessions_list() {
        check_ajax_referer('aica_history_nonce', 'nonce');

        $search = sanitize_text_field($_POST['search'] ?? '');
        $sessions = $this->chat_service->get_all_sessions();

        if (!empty($search)) {
            $sessions = array_filter($sessions, function($session) use ($search) {
                return stripos($session['title'], $search) !== false;
            });
        }

        wp_send_json_success(array_values($sessions));
    }

}