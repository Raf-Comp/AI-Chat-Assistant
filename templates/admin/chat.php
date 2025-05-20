<?php
/**
 * Szablon strony czatu
 *
 * @package AI_Chat_Assistant
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="aica-chat-wrapper">
    <!-- Panel boczny -->
    <div class="aica-sidebar">
        <!-- Nagłówek panelu bocznego -->
        <div class="aica-sidebar-header">
            <div class="aica-branding">
                <div class="aica-logo">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <h1><?php _e('AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
            </div>
            <button type="button" class="aica-sidebar-toggle" aria-label="<?php _e('Zwiń panel', 'ai-chat-assistant'); ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </button>
        </div>
        
        <!-- Przyciski akcji -->
        <div class="aica-sidebar-actions">
            <button type="button" id="aica-new-chat" class="aica-action-button">
                <span class="dashicons dashicons-plus"></span>
                <span class="aica-button-text"><?php _e('Nowa rozmowa', 'ai-chat-assistant'); ?></span>
            </button>
        </div>
        
        <!-- Zakładki -->
        <div class="aica-tabs">
            <button type="button" class="aica-tab active" data-tab="chats">
                <span class="dashicons dashicons-admin-comments"></span>
                <span class="aica-tab-text"><?php _e('Rozmowy', 'ai-chat-assistant'); ?></span>
            </button>
            <button type="button" class="aica-tab" data-tab="repositories">
                <span class="dashicons dashicons-code-standards"></span>
                <span class="aica-tab-text"><?php _e('Repozytoria', 'ai-chat-assistant'); ?></span>
            </button>
            <button type="button" class="aica-tab" data-tab="settings">
                <span class="dashicons dashicons-admin-generic"></span>
                <span class="aica-tab-text"><?php _e('Ustawienia', 'ai-chat-assistant'); ?></span>
            </button>
        </div>
        
        <!-- Zawartość zakładki Rozmowy -->
        <div class="aica-tab-content active" id="chats-content">
            <div class="aica-search-container">
                <input type="text" class="aica-search-input" placeholder="<?php _e('Szukaj rozmów...', 'ai-chat-assistant'); ?>">
                <span class="aica-search-icon dashicons dashicons-search"></span>
            </div>
            
            <div class="aica-sessions-list" id="aica-sessions-list">
                <div class="aica-loading">
                    <div class="aica-spinner"></div>
                    <span><?php _e('Ładowanie rozmów...', 'ai-chat-assistant'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Zawartość zakładki Repozytoria -->
        <div class="aica-tab-content" id="repositories-content">
            <div class="aica-repositories-list" id="aica-repositories-list">
                <div class="aica-loading">
                    <div class="aica-spinner"></div>
                    <span><?php _e('Ładowanie repozytoriów...', 'ai-chat-assistant'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Zawartość zakładki Ustawienia -->
        <div class="aica-tab-content" id="settings-content">
            <!-- Informacje o aktualnie wybranym modelu -->
            <div class="aica-model-info-sidebar">
                <h4><?php _e('Informacje o modelu', 'ai-chat-assistant'); ?></h4>
                <p>
                    <?php _e('Aktualnie używany:', 'ai-chat-assistant'); ?> 
                    <strong id="aica-model-name"><?php echo esc_html(get_option('aica_claude_model', 'claude-3-haiku-20240307')); ?></strong>
                </p>
                <p class="aica-help-text"><?php _e('Model można zmienić w ustawieniach globalnych', 'ai-chat-assistant'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-button-sm">
                    <?php _e('Przejdź do ustawień', 'ai-chat-assistant'); ?>
                </a>
            </div>
            
            <div class="aica-setting-group">
                <h4><?php _e('Wygląd interfejsu', 'ai-chat-assistant'); ?></h4>
                <div class="aica-setting-field aica-checkbox-field">
                    <input type="checkbox" id="aica-dark-mode" name="aica-dark-mode" <?php echo get_option('aica_dark_mode') ? 'checked' : ''; ?>>
                    <label for="aica-dark-mode"><?php _e('Tryb ciemny', 'ai-chat-assistant'); ?></label>
                </div>
                
                <div class="aica-setting-field aica-checkbox-field">
                    <input type="checkbox" id="aica-compact-view" name="aica-compact-view" <?php echo get_option('aica_compact_view') ? 'checked' : ''; ?>>
                    <label for="aica-compact-view"><?php _e('Widok kompaktowy', 'ai-chat-assistant'); ?></label>
                </div>
            </div>
        </div>
        
        <!-- Stopka panelu bocznego -->
        <div class="aica-sidebar-footer">
            <span class="aica-version">v<?php echo AICA_VERSION; ?></span>
            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-settings-link" title="<?php _e('Ustawienia globalne', 'ai-chat-assistant'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </a>
        </div>
    </div>
    
    <!-- Panel główny -->
    <div class="aica-main-panel">
        <!-- Nagłówek panelu głównego -->
        <div class="aica-main-header">
            <div class="aica-conversation-info">
                <h2 class="aica-conversation-title" id="aica-conversation-title"><?php _e('Nowa rozmowa', 'ai-chat-assistant'); ?></h2>
                <span class="aica-conversation-date" id="aica-conversation-date"></span>
            </div>
            <div class="aica-main-actions">
                <button type="button" class="aica-toolbar-button" id="aica-export-chat" title="<?php _e('Eksportuj rozmowę', 'ai-chat-assistant'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
                <div class="aica-dropdown">
                    <button type="button" class="aica-toolbar-button aica-more-button" title="<?php _e('Więcej opcji', 'ai-chat-assistant'); ?>">
                        <span class="dashicons dashicons-ellipsis"></span>
                    </button>
                    <div class="aica-dropdown-menu">
                        <button type="button" class="aica-dropdown-item" id="aica-copy-conversation">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Kopiuj całą rozmowę', 'ai-chat-assistant'); ?>
                        </button>
                        <button type="button" class="aica-dropdown-item" id="aica-rename-conversation">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Zmień tytuł', 'ai-chat-assistant'); ?>
                        </button>
                        <div class="aica-dropdown-divider"></div>
                        <button type="button" class="aica-dropdown-item aica-delete-item" id="aica-delete-conversation">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Usuń rozmowę', 'ai-chat-assistant'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Obszar wiadomości -->
        <div class="aica-messages-container" id="aica-messages-container">
            <div class="aica-welcome-screen" id="aica-welcome-screen">
                <div class="aica-welcome-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <h2><?php _e('Witaj w AI Chat Assistant', 'ai-chat-assistant'); ?></h2>
                <p><?php _e('Jestem Claude, asystent AI stworzony przez Anthropic. Jak mogę Ci dzisiaj pomóc?', 'ai-chat-assistant'); ?></p>
                
                <div class="aica-example-prompts">
                    <h3><?php _e('Przykłady pytań:', 'ai-chat-assistant'); ?></h3>
                    <div class="aica-examples">
                        <button type="button" class="aica-example-prompt"><?php _e('Wyjaśnij, jak działa ten kod...', 'ai-chat-assistant'); ?></button>
                        <button type="button" class="aica-example-prompt"><?php _e('Znajdź błędy w tym kodzie...', 'ai-chat-assistant'); ?></button>
                        <button type="button" class="aica-example-prompt"><?php _e('Jak zoptymalizować tę funkcję?', 'ai-chat-assistant'); ?></button>
                        <button type="button" class="aica-example-prompt"><?php _e('Wygeneruj testy jednostkowe dla...', 'ai-chat-assistant'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="aica-messages" id="aica-messages">
                <!-- Wiadomości będą dodawane przez JavaScript -->
            </div>
        </div>
        
        <!-- Obszar wprowadzania wiadomości -->
        <div class="aica-input-container">
            <div class="aica-selected-file-info" id="aica-selected-file-info" style="display: none;">
                <span class="dashicons dashicons-media-code"></span>
                <span class="aica-selected-file-name" id="aica-selected-file-name"></span>
                <button type="button" class="aica-remove-file-button" title="<?php _e('Usuń plik', 'ai-chat-assistant'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <form id="chat-form">
                <div class="aica-input-wrapper">
                    <textarea id="aica-message-input" placeholder="<?php _e('Napisz wiadomość...', 'ai-chat-assistant'); ?>" rows="1"></textarea>
                    
                    <div class="aica-input-tools">
                        <button type="button" class="aica-tool-button aica-file-button" title="<?php _e('Dodaj plik', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-paperclip"></span>
                            <input type="file" id="aica-file-upload" class="aica-file-input" />
                        </button>
                        <button type="button" class="aica-tool-button" id="aica-theme-toggle" title="<?php _e('Przełącz motyw', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-admin-appearance"></span>
                        </button>
                    </div>
                    
                    <button type="button" id="aica-send-message" class="aica-send-button" disabled>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </form>
            
            <div class="aica-input-footer">
                <div class="aica-model-info">
                    <span class="aica-model-label"><?php _e('Model:', 'ai-chat-assistant'); ?></span>
                    <span class="aica-model-name" id="aica-model-name"><?php echo esc_html(get_option('aica_claude_model', 'claude-3-haiku-20240307')); ?></span>
                </div>
                <div class="aica-input-status">
                    <span class="aica-status-typing" style="display: none;"><?php _e('Claude pisze...', 'ai-chat-assistant'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal edycji tytułu -->
<div id="aica-rename-modal" class="aica-modal" style="display: none;">
    <div class="aica-modal-content">
        <div class="aica-modal-header">
            <h3><?php _e('Zmień tytuł rozmowy', 'ai-chat-assistant'); ?></h3>
            <button type="button" class="aica-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aica-modal-body">
            <div class="aica-form-field">
                <label for="aica-conversation-new-title"><?php _e('Nowy tytuł', 'ai-chat-assistant'); ?></label>
                <input type="text" id="aica-conversation-new-title">
            </div>
        </div>
        <div class="aica-modal-footer">
            <button type="button" class="aica-button aica-button-secondary aica-modal-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
            <button type="button" class="aica-button aica-button-primary aica-modal-save"><?php _e('Zapisz', 'ai-chat-assistant'); ?></button>
        </div>
    </div>
</div>

<!-- Dialog usuwania -->
<div id="aica-delete-dialog" class="aica-dialog" style="display: none;">
    <div class="aica-dialog-content">
        <div class="aica-dialog-header">
            <h3><?php _e('Potwierdź usunięcie', 'ai-chat-assistant'); ?></h3>
            <button type="button" class="aica-dialog-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="aica-dialog-body">
            <p><?php _e('Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.', 'ai-chat-assistant'); ?></p>
        </div>
        <div class="aica-dialog-footer">
            <button type="button" class="aica-button aica-button-secondary aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
            <button type="button" class="aica-button aica-button-danger aica-dialog-confirm"><?php _e('Usuń', 'ai-chat-assistant'); ?></button>
        </div>
    </div>
</div>