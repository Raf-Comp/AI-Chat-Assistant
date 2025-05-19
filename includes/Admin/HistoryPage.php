<?php
namespace AICA\Admin;

use AICA\Services\ChatService;

class HistoryPage {
    private $chat_service;
    
    public function __construct() {
        $this->chat_service = new ChatService();
        
        // Dodanie skryptów i stylów specyficznych dla strony historii
        add_action('admin_enqueue_scripts', [$this, 'enqueue_history_assets']);
    }
    
    /**
     * Dodaje skrypty i style dla strony historii
     */
    public function enqueue_history_assets($hook) {
        if ($hook != 'ai-chat-assistant_page_ai-chat-assistant-history') {
            return;
        }
        
        // Lokalizacja skryptu - dodajemy dane JavaScript dla strony historii
        wp_localize_script('aica-admin', 'aica_history', [
            'nonce' => wp_create_nonce('aica_history_nonce'),
            'chat_url' => admin_url('admin.php?page=ai-chat-assistant'),
            'i18n' => [
                'load_more' => __('Pokaż więcej wiadomości', 'ai-chat-assistant'),
                'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                'load_error' => __('Wystąpił błąd podczas ładowania wiadomości.', 'ai-chat-assistant'),
                'no_messages' => __('Ta rozmowa nie zawiera żadnych wiadomości.', 'ai-chat-assistant'),
                'delete_error' => __('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant'),
                'no_conversations' => __('Nie znaleziono żadnych rozmów', 'ai-chat-assistant'),
                'no_conversations_desc' => __('Nie przeprowadzono jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'),
                'new_conversation' => __('Rozpocznij nową rozmowę', 'ai-chat-assistant'),
                'min_search_length' => __('Wyszukiwana fraza musi zawierać co najmniej 3 znaki.', 'ai-chat-assistant')
            ]
        ]);
    }
    
    /**
     * Renderowanie strony historii
     */
    public function render() {
        // Obsługa wyszukiwania
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Obsługa filtrowania
        $sort_order = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'newest';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Pobieranie sesji użytkownika z uwzględnieniem filtrów
        $user_id = get_current_user_id();
        $sessions = $this->chat_service->get_user_sessions(
            $user_id, 
            $search_term, 
            $sort_order, 
            $date_from, 
            $date_to
        );
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/history.php';
    }
}