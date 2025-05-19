<?php
namespace AICA\Admin;
use AICA\Services\ChatService;
class HistoryPage {
    private $chat_service;
    
    public function __construct() {
        $this->chat_service = new ChatService();
        add_action('admin_enqueue_scripts', [$this, 'enqueue_history_assets']);
        $this->init_ajax_handlers();
    }
    
    public function enqueue_history_assets($hook) {
        if ($hook != 'ai-chat-assistant_page_ai-chat-assistant-history') return;
        
        wp_enqueue_style('aica-history', AICA_PLUGIN_URL . 'assets/css/history.css', [], AICA_VERSION);
        wp_enqueue_script('aica-history', AICA_PLUGIN_URL . 'assets/js/history.js', ['jquery'], AICA_VERSION, true);
        
        wp_localize_script('aica-history', 'aica_history', [
            'nonce' => wp_create_nonce('aica_history_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => [
                'load_more' => __('Pokaż więcej wiadomości', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'loading_messages' => __('Ładowanie wiadomości...', 'ai-chat-assistant'),
                'load_error' => __('Wystąpił błąd podczas ładowania wiadomości.', 'ai-chat-assistant'),
                'no_messages' => __('Ta rozmowa nie zawiera żadnych wiadomości.', 'ai-chat-assistant'),
                'delete_error' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant'),
                'no_conversations' => __('Nie znaleziono żadnych rozmów', 'ai-chat-assistant'),
                'no_conversations_desc' => __('Nie przeprowadzono jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'),
                'new_conversation' => __('Rozpocznij nową rozmowę', 'ai-chat-assistant'),
                'min_search_length' => __('Wyszukiwana fraza musi zawierać co najmniej 3 znaki.', 'ai-chat-assistant'),
                'continue_conversation' => __('Kontynuuj rozmowę', 'ai-chat-assistant'),
                'duplicate' => __('Duplikuj', 'ai-chat-assistant'),
                'export' => __('Eksportuj', 'ai-chat-assistant'),
                'delete' => __('Usuń', 'ai-chat-assistant'),
                'expand' => __('Rozwiń', 'ai-chat-assistant'),
                'menu' => __('Menu', 'ai-chat-assistant'),
                'user' => __('Użytkownik', 'ai-chat-assistant'),
                'export_error' => __('Wystąpił błąd podczas eksportowania rozmowy.', 'ai-chat-assistant'),
                'duplicate_error' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant'),
                'duplicate_success' => __('Rozmowa została zduplikowana pomyślnie.', 'ai-chat-assistant'),
                'pagination_info' => __('Wyświetlanie %1$s do %2$s z %3$s rozmów', 'ai-chat-assistant'),
                'year' => __('rok', 'ai-chat-assistant'),
                'years' => __('lat', 'ai-chat-assistant'),
                'month' => __('miesiąc', 'ai-chat-assistant'),
                'months' => __('miesięcy', 'ai-chat-assistant'),
                'day' => __('dzień', 'ai-chat-assistant'),
                'days' => __('dni', 'ai-chat-assistant'),
                'hour' => __('godzina', 'ai-chat-assistant'),
                'hours' => __('godzin', 'ai-chat-assistant'),
                'minute' => __('minuta', 'ai-chat-assistant'),
                'minutes' => __('minut', 'ai-chat-assistant'),
                'second' => __('sekunda', 'ai-chat-assistant'),
                'seconds' => __('sekund', 'ai-chat-assistant'),
                'ago' => __('temu', 'ai-chat-assistant'),
                'confirm_delete' => __('Czy na pewno chcesz usunąć tę rozmowę?', 'ai-chat-assistant'),
                'confirm_delete_all' => __('Czy na pewno chcesz usunąć wszystkie wybrane rozmowy?', 'ai-chat-assistant'),
                'no_selected' => __('Nie wybrano żadnej rozmowy do usunięcia', 'ai-chat-assistant')
            ]
        ]);
    }
    
    // Alias dla render_page wywoływany przez PageManager
    public function render() {
        $this->render_page();
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nie masz wystarczających uprawnień, aby uzyskać dostęp do tej strony.', 'ai-chat-assistant'));
        }
        
        $page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $conversations = $this->chat_service->get_conversations([
            'offset' => $offset,
            'limit' => $per_page,
            'search' => $search_query
        ]);
        
        $total_conversations = $this->chat_service->count_conversations($search_query);
        $total_pages = ceil($total_conversations / $per_page);
        
        include_once AICA_PLUGIN_DIR . 'templates/admin/history-page.php';
    }
    
    public function ajax_load_messages() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID rozmowy.', 'ai-chat-assistant')]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 10;
        
        $messages = $this->chat_service->get_messages($conversation_id, $page, $per_page);
        $total_messages = $this->chat_service->count_messages($conversation_id);
        
        wp_send_json_success([
            'messages' => $messages,
            'total' => $total_messages,
            'has_more' => ($page * $per_page) < $total_messages
        ]);
    }
    
    public function ajax_delete_conversation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID rozmowy.', 'ai-chat-assistant')]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $result = $this->chat_service->delete_conversation($conversation_id);
        
        if ($result) {
            wp_send_json_success(['message' => __('Rozmowa została pomyślnie usunięta.', 'ai-chat-assistant')]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant')]);
        }
    }
    
    public function ajax_export_conversation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID rozmowy.', 'ai-chat-assistant')]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $messages = $this->chat_service->get_all_messages($conversation_id);
        $conversation = $this->chat_service->get_conversation($conversation_id);
        
        if (!$conversation) {
            wp_send_json_error(['message' => __('Nie znaleziono rozmowy.', 'ai-chat-assistant')]);
        }
        
        $export_data = [
            'title' => $conversation->title,
            'created_at' => $conversation->created_at,
            'messages' => $messages
        ];
        
        wp_send_json_success([
            'data' => $export_data,
            'filename' => sanitize_file_name($conversation->title) . '-' . date('Y-m-d') . '.json'
        ]);
    }
    
    public function ajax_duplicate_conversation() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['conversation_id']) || empty($_POST['conversation_id'])) {
            wp_send_json_error(['message' => __('Nieprawidłowe ID rozmowy.', 'ai-chat-assistant')]);
        }
        
        $conversation_id = intval($_POST['conversation_id']);
        $new_conversation_id = $this->chat_service->duplicate_conversation($conversation_id);
        
        if ($new_conversation_id) {
            wp_send_json_success([
                'message' => __('Rozmowa została pomyślnie zduplikowana.', 'ai-chat-assistant'),
                'new_id' => $new_conversation_id
            ]);
        } else {
            wp_send_json_error(['message' => __('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant')]);
        }
    }
    
    public function ajax_bulk_delete_conversations() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_history_nonce')) {
            wp_send_json_error(['message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Nie masz wystarczających uprawnień.', 'ai-chat-assistant')]);
        }
        
        if (!isset($_POST['conversation_ids']) || empty($_POST['conversation_ids'])) {
            wp_send_json_error(['message' => __('Nie wybrano żadnej rozmowy do usunięcia.', 'ai-chat-assistant')]);
        }
        
        $conversation_ids = array_map('intval', (array) $_POST['conversation_ids']);
        $deleted = 0;
        
        foreach ($conversation_ids as $id) {
            if ($this->chat_service->delete_conversation($id)) {
                $deleted++;
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(
                __('Pomyślnie usunięto %d z %d wybranych rozmów.', 'ai-chat-assistant'),
                $deleted,
                count($conversation_ids)
            )
        ]);
    }
    
    public function init_ajax_handlers() {
        add_action('wp_ajax_aica_load_messages', [$this, 'ajax_load_messages']);
        add_action('wp_ajax_aica_delete_conversation', [$this, 'ajax_delete_conversation']);
        add_action('wp_ajax_aica_export_conversation', [$this, 'ajax_export_conversation']);
        add_action('wp_ajax_aica_duplicate_conversation', [$this, 'ajax_duplicate_conversation']);
        add_action('wp_ajax_aica_bulk_delete_conversations', [$this, 'ajax_bulk_delete_conversations']);
    }
}