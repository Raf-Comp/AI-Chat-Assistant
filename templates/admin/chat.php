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
    <div class="aica-chat-container">
        <!-- Panel boczny -->
        <div class="aica-sidebar">
            <!-- Nagłówek panelu bocznego -->
            <div class="aica-sidebar-header">
                <div class="aica-branding">
                    <span class="aica-logo">
                        <span class="dashicons dashicons-format-chat"></span>
                    </span>
                    <h1><?php _e('AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
                </div>
                <button type="button" class="aica-sidebar-toggle" aria-label="<?php _e('Zwiń panel', 'ai-chat-assistant'); ?>">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
            </div>
            
            <!-- Przyciski akcji -->
            <div class="aica-sidebar-actions">
                <button type="button" id="aica-new-chat" class="aica-action-button aica-new-chat-button">
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
                    <!-- Lista rozmów zostanie wypełniona przez JavaScript -->
                </div>
            </div>
            
            <!-- Zawartość zakładki Repozytoria -->
            <div class="aica-tab-content" id="repositories-content">
                <div class="aica-repo-list" id="aica-repo-list">
                    <div class="aica-loading">
                        <div class="aica-spinner"></div>
                        <span><?php _e('Ładowanie repozytoriów...', 'ai-chat-assistant'); ?></span>
                    </div>
                    <!-- Lista repozytoriów zostanie wypełniona przez JavaScript -->
                </div>
                
                <div class="aica-repo-browse" style="display: none;">
                    <div class="aica-repo-browser-header">
                        <button type="button" class="aica-back-button">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <span class="aica-button-text"><?php _e('Powrót', 'ai-chat-assistant'); ?></span>
                        </button>
                        <h3 class="aica-repo-name"></h3>
                    </div>
                    
                    <div class="aica-file-tree" id="aica-file-tree">
                        <!-- Drzewo plików zostanie wypełnione przez JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Zawartość zakładki Ustawienia -->
            <div class="aica-tab-content" id="settings-content">
                <div class="aica-settings-form">
                    <div class="aica-setting-group">
                        <h3><?php _e('Model AI', 'ai-chat-assistant'); ?></h3>
                        <div class="aica-setting-field">
                            <label for="aica-model"><?php _e('Wybierz model', 'ai-chat-assistant'); ?></label>
                            <select id="aica-model" name="aica-model">
                                <?php 
                                $current_model = get_option('aica_claude_model', 'claude-3-haiku-20240307');
                                foreach ($available_models as $model_id => $model_name) {
                                    echo '<option value="' . esc_attr($model_id) . '" ' . 
                                        selected($current_model, $model_id, false) . '>' . 
                                        esc_html($model_name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="aica-setting-group">
                        <h3><?php _e('Interfejs', 'ai-chat-assistant'); ?></h3>
                        <div class="aica-setting-field aica-checkbox-field">
                            <input type="checkbox" id="aica-dark-mode" name="aica-dark-mode">
                            <label for="aica-dark-mode"><?php _e('Tryb ciemny', 'ai-chat-assistant'); ?></label>
                        </div>
                        
                        <div class="aica-setting-field aica-checkbox-field">
                            <input type="checkbox" id="aica-compact-view" name="aica-compact-view">
                            <label for="aica-compact-view"><?php _e('Widok kompaktowy', 'ai-chat-assistant'); ?></label>
                        </div>
                        
                        <div class="aica-setting-field aica-checkbox-field">
                            <input type="checkbox" id="aica-code-highlighting" name="aica-code-highlighting" checked>
                            <label for="aica-code-highlighting"><?php _e('Podświetlanie składni kodu', 'ai-chat-assistant'); ?></label>
                        </div>
                    </div>
                    
                    <div class="aica-setting-group">
                        <h3><?php _e('Zaawansowane', 'ai-chat-assistant'); ?></h3>
                        <div class="aica-setting-field">
                            <label for="aica-max-tokens"><?php _e('Maksymalna długość odpowiedzi', 'ai-chat-assistant'); ?></label>
                            <div class="aica-slider-container">
                                <input type="range" id="aica-max-tokens" name="aica-max-tokens" 
                                    min="1000" max="10000" step="1000" 
                                    value="<?php echo esc_attr(get_option('aica_max_tokens', 4000)); ?>">
                                <span class="aica-slider-value" id="aica-max-tokens-value"><?php echo esc_html(get_option('aica_max_tokens', 4000)); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="aica-settings-actions">
                        <button type="button" id="aica-save-settings" class="button button-primary"><?php _e('Zapisz ustawienia', 'ai-chat-assistant'); ?></button>
                    </div>
                </div>
            </div>
            
            <!-- Stopka panelu bocznego -->
            <div class="aica-sidebar-footer">
                <span class="aica-version">AI Chat Assistant v<?php echo AICA_VERSION; ?></span>
                <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="aica-settings-link">
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
                    <button type="button" class="aica-action-button aica-export-button" id="aica-export-chat" title="<?php _e('Eksportuj rozmowę', 'ai-chat-assistant'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <div class="aica-dropdown">
                        <button type="button" class="aica-action-button aica-more-button" title="<?php _e('Więcej opcji', 'ai-chat-assistant'); ?>">
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
                    <p><?php _e('Zacznij rozmowę z Claude - zadaj pytanie lub poproś o pomoc.', 'ai-chat-assistant'); ?></p>
                    
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
            
            <!-- Podgląd pliku -->
            <div class="aica-file-preview" id="aica-file-preview" style="display: none;">
                <div class="aica-file-preview-header">
                    <div class="aica-file-info">
                        <span class="aica-file-icon">
                            <span class="dashicons dashicons-media-code"></span>
                        </span>
                        <h3 class="aica-file-path" id="aica-file-path"></h3>
                    </div>
                    <div class="aica-file-actions">
                        <button type="button" class="aica-action-button aica-copy-file-button" title="<?php _e('Kopiuj zawartość', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>
                        <button type="button" class="aica-action-button aica-close-preview-button" title="<?php _e('Zamknij podgląd', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
                <div class="aica-file-preview-content">
                    <pre><code class="aica-file-content"></code></pre>
                </div>
                <div class="aica-file-preview-footer">
                    <button type="button" class="aica-action-button aica-analyze-button">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Analizuj z Claude', 'ai-chat-assistant'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Obszar wprowadzania wiadomości -->
            <div class="aica-input-container">
                <div class="aica-selected-file-info" id="aica-selected-file-info" style="display: none;">
                    <span class="aica-selected-file-icon">
                        <span class="dashicons dashicons-media-code"></span>
                    </span>
                    <span class="aica-selected-file-name" id="aica-selected-file-name"></span>
                    <button type="button" class="aica-remove-file-button" title="<?php _e('Usuń plik', 'ai-chat-assistant'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                
                <div class="aica-input-wrapper">
                    <textarea id="aica-message-input" placeholder="<?php _e('Wpisz wiadomość lub @wzmiankuj plik...', 'ai-chat-assistant'); ?>" rows="1"></textarea>
                    
                    <div class="aica-input-tools">
                        <button type="button" class="aica-tool-button aica-file-button" title="<?php _e('Dodaj plik', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-paperclip"></span>
                            <input type="file" id="aica-file-upload" class="aica-file-input" />
                        </button>
                        <button type="button" class="aica-tool-button aica-repo-button" title="<?php _e('Dodaj plik z repozytorium', 'ai-chat-assistant'); ?>">
                            <span class="dashicons dashicons-code-standards"></span>
                        </button>
                    </div>
                    
                    <button type="button" id="aica-send-message" class="aica-send-button" disabled>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
                
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
            <button type="button" class="button button-secondary aica-modal-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
            <button type="button" class="button button-primary aica-modal-save"><?php _e('Zapisz', 'ai-chat-assistant'); ?></button>
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
            <button type="button" class="button button-secondary aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
            <button type="button" class="button button-primary aica-dialog-confirm"><?php _e('Usuń', 'ai-chat-assistant'); ?></button>
        </div>
    </div>
</div>

<style>
:root {
    --aica-primary: #3B82F6;
    --aica-primary-light: #60A5FA;
    --aica-primary-dark: #2563EB;
    --aica-secondary: #0F172A;
    --aica-secondary-light: #1E293B;
    --aica-gray-50: #F9FAFB;
    --aica-gray-100: #F3F4F6;
    --aica-gray-200: #E5E7EB;
    --aica-gray-300: #D1D5DB;
    --aica-gray-400: #9CA3AF;
    --aica-gray-500: #6B7280;
    --aica-gray-600: #4B5563;
    --aica-gray-700: #374151;
    --aica-gray-800: #1F2937;
    --aica-gray-900: #111827;
    --aica-success: #10B981;
    --aica-success-light: #D1FAE5;
    --aica-warning: #F59E0B;
    --aica-warning-light: #FEF3C7;
    --aica-error: #EF4444;
    --aica-error-light: #FEE2E2;
    --aica-user-bubble: #3B82F6;
    --aica-ai-bubble: #F3F4F6;
    --aica-ai-text: #374151;
    --aica-card-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    --aica-sidebar-width: 300px;
    --aica-sidebar-collapsed-width: 80px;
    --aica-border-radius: 12px;
    --aica-spacing: 16px;
}

/* Theme Variables - Ustaw dla trybu ciemnego */
body.aica-dark-mode {
    --aica-primary: #60A5FA;
    --aica-primary-light: #93C5FD;
    --aica-primary-dark: #3B82F6;
    --aica-secondary: #1E293B;
    --aica-secondary-light: #334155;
    --aica-gray-50: #1F2937;
    --aica-gray-100: #374151;
    --aica-gray-200: #4B5563;
    --aica-gray-300: #6B7280;
    --aica-gray-400: #9CA3AF;
    --aica-gray-500: #D1D5DB;
    --aica-gray-600: #E5E7EB;
    --aica-gray-700: #F3F4F6;
    --aica-gray-800: #F9FAFB;
    --aica-gray-900: #FFFFFF;
    --aica-user-bubble: #3B82F6;
    --aica-ai-bubble: #1F2937;
    --aica-ai-text: #E5E7EB;
}

/* Resetowanie */
.aica-chat-wrapper * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Główny kontener */
.aica-chat-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: var(--aica-gray-900);
    background-color: #FFFFFF;
    height: calc(100vh - 32px); /* Wysokość bez górnego paska WP Admin */
    margin: 0;
    overflow: hidden;
}

body.aica-dark-mode .aica-chat-wrapper {
    background-color: var(--aica-secondary);
    color: var(--aica-gray-900);
}

.aica-chat-container {
    display: flex;
    height: 100%;
    position: relative;
}

/* Panel boczny */
.aica-sidebar {
    width: var(--aica-sidebar-width);
    background-color: var(--aica-gray-50);
    border-right: 1px solid var(--aica-gray-200);
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    overflow: hidden;
}

body.aica-dark-mode .aica-sidebar {
    background-color: var(--aica-secondary);
    border-right-color: var(--aica-gray-100);
}

.aica-sidebar.collapsed {
    width: var(--aica-sidebar-collapsed-width);
}

.aica-sidebar-header {
    padding: var(--aica-spacing);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-sidebar-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-branding {
    display: flex;
    align-items: center;
    gap: 10px;
}

.aica-logo {
    width: 32px;
    height: 32px;
    background-color: var(--aica-primary);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aica-logo .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.aica-branding h1 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aica-sidebar.collapsed .aica-branding h1 {
    display: none;
}

.aica-sidebar-toggle {
    background: none;
    border: none;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-sidebar-toggle:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-sidebar-toggle:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

.aica-sidebar.collapsed .aica-sidebar-toggle .dashicons {
    transform: rotate(180deg);
}

/* Przyciski akcji w panelu bocznym */
.aica-sidebar-actions {
    padding: var(--aica-spacing);
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-sidebar-actions {
    border-bottom-color: var(--aica-gray-100);
}

.aica-action-button {
    background-color: var(--aica-primary);
    color: white;
    border: none;
    border-radius: var(--aica-border-radius);
    cursor: pointer;
    padding: 10px 16px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    transition: background-color 0.2s ease;
}

.aica-action-button:hover {
    background-color: var(--aica-primary-dark);
}

.aica-action-button:disabled {
    background-color: var(--aica-gray-300);
    cursor: not-allowed;
}

.aica-sidebar.collapsed .aica-button-text {
    display: none;
}

.aica-sidebar.collapsed .aica-action-button {
    width: 48px;
    height: 48px;
    padding: 0;
    margin: 0 auto;
    border-radius: 50%;
}

/* Zakładki */
.aica-tabs {
    display: flex;
    border-bottom: 1px solid var(--aica-gray-200);
    padding: 0 var(--aica-spacing);
}

body.aica-dark-mode .aica-tabs {
    border-bottom-color: var(--aica-gray-100);
}

.aica-sidebar.collapsed .aica-tabs {
    flex-direction: column;
    border-bottom: none;
    padding-top: var(--aica-spacing);
    padding-bottom: var(--aica-spacing);
}

.aica-tab {
    flex: 1;
    padding: 12px 16px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--aica-gray-500);
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: color 0.2s ease, border-color 0.2s ease;
}

.aica-sidebar.collapsed .aica-tab {
    justify-content: center;
    padding: 12px 0;
    margin-bottom: 8px;
    border-bottom: none;
    border-left: 2px solid transparent;
}

.aica-tab:hover {
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-tab:hover {
    color: var(--aica-gray-400);
}

.aica-tab.active {
    color: var(--aica-primary);
    border-bottom-color: var(--aica-primary);
}

.aica-sidebar.collapsed .aica-tab.active {
    border-bottom-color: transparent;
    border-left-color: var(--aica-primary);
    background-color: var(--aica-gray-100);
    border-radius: 0 var(--aica-border-radius) var(--aica-border-radius) 0;
}

body.aica-dark-mode .aica-tab.active {
    color: var(--aica-primary);
    border-bottom-color: var(--aica-primary);
    background-color: var(--aica-gray-100);
}

.aica-sidebar.collapsed .aica-tab-text {
    display: none;
}

/* Zawartość zakładek */
.aica-tab-content {
    display: none;
    flex-direction: column;
    flex: 1;
    overflow-y: auto;
}

.aica-tab-content.active {
    display: flex;
}

/* Wyszukiwarka rozmów */
.aica-search-container {
    padding: var(--aica-spacing);
    position: relative;
}

.aica-search-input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border-radius: var(--aica-border-radius);
    border: 1px solid var(--aica-gray-200);
    background-color: var(--aica-gray-50);
    font-size: 14px;
    color: var(--aica-gray-900);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

body.aica-dark-mode .aica-search-input {
    background-color: var(--aica-secondary-light);
    border-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-search-input:focus {
    outline: none;
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.aica-search-icon {
    position: absolute;
    left: 28px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--aica-gray-500);
    pointer-events: none;
}

.aica-sidebar.collapsed .aica-search-container {
    display: none;
}

/* Lista rozmów */
.aica-sessions-list {
    overflow-y: auto;
    flex: 1;
}

.aica-session-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--aica-gray-200);
    cursor: pointer;
    transition: background-color 0.2s ease;
}

body.aica-dark-mode .aica-session-item {
    border-bottom-color: var(--aica-gray-100);
}

.aica-session-item:hover {
    background-color: var(--aica-gray-100);
}

body.aica-dark-mode .aica-session-item:hover {
    background-color: var(--aica-secondary-light);
}

.aica-session-item.active {
    background-color: var(--aica-primary-light);
    color: white;
}

body.aica-dark-mode .aica-session-item.active {
    background-color: var(--aica-primary-dark);
}

.aica-session-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.aica-session-title {
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aica-session-date {
    font-size: 12px;
    color: var(--aica-gray-500);
}

.aica-session-item.active .aica-session-date {
    color: rgba(255, 255, 255, 0.8);
}

.aica-session-preview {
    font-size: 13px;
    color: var(--aica-gray-600);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-height: 36px;
}

.aica-session-item.active .aica-session-preview {
    color: rgba(255, 255, 255, 0.9);
}

/* Lista repozytoriów */
.aica-repo-list {
    flex: 1;
    overflow-y: auto;
}

.aica-repo-item {
    padding: 12px 16px;
    border-bottom: 1px solid var(--aica-gray-200);
    cursor: pointer;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

body.aica-dark-mode .aica-repo-item {
    border-bottom-color: var(--aica-gray-100);
}

.aica-repo-item:hover {
    background-color: var(--aica-gray-100);
}

body.aica-dark-mode .aica-repo-item:hover {
    background-color: var(--aica-secondary-light);
}

.aica-repo-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-repo-icon {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-500);
}

.aica-repo-info {
    flex: 1;
}

.aica-repo-title {
    font-weight: 500;
    font-size: 14px;
    margin-bottom: 4px;
}

.aica-repo-description {
    font-size: 13px;
    color: var(--aica-gray-600);
}

body.aica-dark-mode .aica-repo-description {
    color: var(--aica-gray-400);
}

/* Przeglądarka repozytorium */
.aica-repo-browse {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.aica-repo-browser-header {
    padding: var(--aica-spacing);
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-repo-browser-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-back-button {
    background: none;
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--aica-gray-600);
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-back-button:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-back-button:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-repo-name {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.aica-file-tree {
    flex: 1;
    overflow-y: auto;
    padding: 0 0 var(--aica-spacing) 0;
}

.aica-file-item {
    padding: 8px 16px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.aica-file-item:hover {
    background-color: var(--aica-gray-100);
}

body.aica-dark-mode .aica-file-item:hover {
    background-color: var(--aica-secondary-light);
}

.aica-file-item-icon {
    color: var(--aica-gray-500);
}

.aica-file-item-name {
    font-size: 14px;
}

.aica-folder-item .aica-file-item-name {
    font-weight: 500;
}

.aica-file-children {
    padding-left: 16px;
    border-left: 1px dashed var(--aica-gray-200);
    margin-left: 16px;
}

body.aica-dark-mode .aica-file-children {
    border-left-color: var(--aica-gray-300);
}

/* Formularz ustawień */
.aica-settings-form {
    padding: var(--aica-spacing);
}

.aica-setting-group {
    margin-bottom: 24px;
}

.aica-setting-group h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: var(--aica-gray-800);
}

body.aica-dark-mode .aica-setting-group h3 {
    color: var(--aica-gray-600);
}

.aica-setting-field {
    margin-bottom: 16px;
}

.aica-setting-field label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-setting-field label {
    color: var(--aica-gray-500);
}

.aica-setting-field select,
.aica-setting-field input[type="text"],
.aica-setting-field input[type="number"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--aica-gray-200);
    font-size: 14px;
    background-color: white;
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-setting-field select,
body.aica-dark-mode .aica-setting-field input[type="text"],
body.aica-dark-mode .aica-setting-field input[type="number"] {
    background-color: var(--aica-secondary-light);
    border-color: var(--aica-gray-200);
    color: var(--aica-gray-600);
}

.aica-setting-field select:focus,
.aica-setting-field input[type="text"]:focus,
.aica-setting-field input[type="number"]:focus {
    outline: none;
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.aica-checkbox-field {
    display: flex;
    align-items: center;
    gap: 10px;
}

.aica-checkbox-field label {
    margin-bottom: 0;
    cursor: pointer;
}

.aica-checkbox-field input[type="checkbox"] {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    cursor: pointer;
}

.aica-slider-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.aica-slider-container input[type="range"] {
    flex: 1;
    height: 8px;
    border-radius: 4px;
    background-color: var(--aica-gray-200);
    -webkit-appearance: none;
    appearance: none;
}

body.aica-dark-mode .aica-slider-container input[type="range"] {
    background-color: var(--aica-gray-100);
}

.aica-slider-container input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: var(--aica-primary);
    cursor: pointer;
}

.aica-slider-container input[type="range"]::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: var(--aica-primary);
    cursor: pointer;
    border: none;
}

.aica-slider-value {
    min-width: 60px;
    text-align: right;
    font-size: 14px;
    font-weight: 500;
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-slider-value {
    color: var(--aica-gray-500);
}

.aica-settings-actions {
    margin-top: 24px;
    display: flex;
    justify-content: flex-end;
}

/* Stopka panelu bocznego */
.aica-sidebar-footer {
    padding: var(--aica-spacing);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid var(--aica-gray-200);
    margin-top: auto;
}

body.aica-dark-mode .aica-sidebar-footer {
    border-top-color: var(--aica-gray-100);
}

.aica-sidebar.collapsed .aica-sidebar-footer {
    flex-direction: column;
    gap: 12px;
}

.aica-version {
    font-size: 12px;
    color: var(--aica-gray-500);
}

.aica-settings-link {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    transition: background-color 0.2s ease, color 0.2s ease;
    text-decoration: none;
}

.aica-settings-link:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-settings-link:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

/* Panel główny */
.aica-main-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: white;
    overflow: hidden;
}

body.aica-dark-mode .aica-main-panel {
    background-color: var(--aica-secondary);
}

.aica-main-header {
    padding: var(--aica-spacing);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-main-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-conversation-info {
    display: flex;
    flex-direction: column;
}

.aica-conversation-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.aica-conversation-date {
    font-size: 13px;
    color: var(--aica-gray-500);
}

.aica-main-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.aica-main-actions .aica-action-button {
    width: 36px;
    height: 36px;
    padding: 0;
    border-radius: 8px;
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-main-actions .aica-action-button {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-500);
}

.aica-main-actions .aica-action-button:hover {
    background-color: var(--aica-gray-200);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-main-actions .aica-action-button:hover {
    background-color: var(--aica-gray-200);
    color: var(--aica-gray-700);
}

/* Dropdown menu */
.aica-dropdown {
    position: relative;
}

.aica-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background-color: white;
    border-radius: var(--aica-border-radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    min-width: 200px;
    z-index: 100;
    padding: 8px;
    display: none;
}

body.aica-dark-mode .aica-dropdown-menu {
    background-color: var(--aica-secondary-light);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.aica-dropdown.open .aica-dropdown-menu {
    display: block;
}

.aica-dropdown-item {
    padding: 8px 12px;
    font-size: 14px;
    background: none;
    border: none;
    cursor: pointer;
    width: 100%;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px;
    color: var(--aica-gray-700);
    transition: background-color 0.2s ease;
}

body.aica-dark-mode .aica-dropdown-item {
    color: var(--aica-gray-500);
}

.aica-dropdown-item:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-dropdown-item:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-dropdown-item.aica-delete-item {
    color: var(--aica-error);
}

.aica-dropdown-item.aica-delete-item:hover {
    background-color: var(--aica-error-light);
    color: var(--aica-error);
}

.aica-dropdown-divider {
    height: 1px;
    margin: 6px 0;
    background-color: var(--aica-gray-200);
}

body.aica-dark-mode .aica-dropdown-divider {
    background-color: var(--aica-gray-100);
}

/* Obszar wiadomości */
.aica-messages-container {
    flex: 1;
    overflow-y: auto;
    padding: var(--aica-spacing);
    background-color: var(--aica-gray-50);
}

body.aica-dark-mode .aica-messages-container {
    background-color: var(--aica-secondary);
}

/* Ekran powitalny */
.aica-welcome-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 100%;
    padding: 0 var(--aica-spacing);
}

.aica-welcome-icon {
    width: 60px;
    height: 60px;
    background-color: var(--aica-primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

.aica-welcome-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: white;
}

.aica-welcome-screen h2 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 12px;
}

.aica-welcome-screen p {
    font-size: 16px;
    color: var(--aica-gray-600);
    margin-bottom: 32px;
    max-width: 600px;
}

body.aica-dark-mode .aica-welcome-screen p {
    color: var(--aica-gray-400);
}

.aica-example-prompts {
    width: 100%;
    max-width: 600px;
}

.aica-example-prompts h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
}

.aica-examples {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 12px;
}

.aica-example-prompt {
    background-color: white;
    border: 1px solid var(--aica-gray-200);
    border-radius: var(--aica-border-radius);
    padding: 12px 16px;
    text-align: left;
    cursor: pointer;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    font-size: 14px;
    color: var(--aica-gray-800);
}

body.aica-dark-mode .aica-example-prompt {
    background-color: var(--aica-secondary-light);
    border-color: var(--aica-gray-100);
    color: var(--aica-gray-500);
}

.aica-example-prompt:hover {
    border-color: var(--aica-primary);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

body.aica-dark-mode .aica-example-prompt:hover {
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
}

/* Wiadomości */
.aica-messages {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.aica-message {
    display: flex;
    flex-direction: column;
    max-width: 80%;
}

.aica-message.aica-user-message {
    align-self: flex-end;
}

.aica-message.aica-ai-message {
    align-self: flex-start;
}

.aica-message-bubble {
    padding: 12px 16px;
    border-radius: var(--aica-border-radius);
    font-size: 15px;
    line-height: 1.5;
}

.aica-user-message .aica-message-bubble {
    background-color: var(--aica-user-bubble);
    color: white;
    border-radius: var(--aica-border-radius) var(--aica-border-radius) 0 var(--aica-border-radius);
}

.aica-ai-message .aica-message-bubble {
    background-color: var(--aica-ai-bubble);
    color: var(--aica-ai-text);
    border-radius: 0 var(--aica-border-radius) var(--aica-border-radius) var(--aica-border-radius);
}

.aica-message-info {
    font-size: 12px;
    margin-top: 4px;
    display: flex;
    align-items: center;
}

.aica-user-message .aica-message-info {
    justify-content: flex-end;
    color: var(--aica-gray-500);
}

.aica-ai-message .aica-message-info {
    color: var(--aica-gray-500);
}

/* Kod w wiadomościach */
.aica-message pre {
    overflow-x: auto;
    background-color: var(--aica-gray-100);
    border-radius: 8px;
    padding: 12px;
    margin: 8px 0;
}

body.aica-dark-mode .aica-message pre {
    background-color: var(--aica-gray-900);
}

.aica-message code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 14px;
}

.aica-message pre code {
    display: block;
    line-height: 1.5;
}

.aica-message p code {
    background-color: var(--aica-gray-100);
    padding: 2px 4px;
    border-radius: 4px;
}

body.aica-dark-mode .aica-message p code {
    background-color: var(--aica-gray-100);
}

/* Podgląd pliku */
.aica-file-preview {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: white;
}

body.aica-dark-mode .aica-file-preview {
    background-color: var(--aica-secondary);
}

.aica-file-preview-header {
    padding: var(--aica-spacing);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-file-preview-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-file-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.aica-file-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-file-icon {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-500);
}

.aica-file-path {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.aica-file-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.aica-file-preview-content {
    flex: 1;
    overflow: auto;
    padding: var(--aica-spacing);
}

.aica-file-preview-content pre {
    margin: 0;
    overflow-x: auto;
}

.aica-file-preview-content code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 14px;
    line-height: 1.5;
}

.aica-file-preview-footer {
    padding: var(--aica-spacing);
    border-top: 1px solid var(--aica-gray-200);
    display: flex;
    justify-content: flex-end;
}

body.aica-dark-mode .aica-file-preview-footer {
    border-top-color: var(--aica-gray-100);
}

.aica-analyze-button {
    background-color: var(--aica-primary);
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
}

.aica-analyze-button:hover {
    background-color: var(--aica-primary-dark);
}

/* Obszar wprowadzania wiadomości */
.aica-input-container {
    padding: var(--aica-spacing);
    border-top: 1px solid var(--aica-gray-200);
    background-color: white;
}

body.aica-dark-mode .aica-input-container {
    background-color: var(--aica-secondary);
    border-top-color: var(--aica-gray-100);
}

.aica-selected-file-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background-color: var(--aica-gray-50);
    border-radius: 8px;
    margin-bottom: 12px;
}

body.aica-dark-mode .aica-selected-file-info {
    background-color: var(--aica-secondary-light);
}

.aica-selected-file-icon {
    color: var(--aica-gray-600);
}

.aica-selected-file-name {
    font-size: 14px;
    font-weight: 500;
    flex: 1;
}

.aica-remove-file-button {
    background: none;
    border: none;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-remove-file-button:hover {
    background-color: var(--aica-gray-200);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-remove-file-button:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    background-color: var(--aica-gray-50);
    border-radius: var(--aica-border-radius);
    padding: 8px 12px;
    border: 1px solid var(--aica-gray-200);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

body.aica-dark-mode .aica-input-wrapper {
    background-color: var(--aica-secondary-light);
    border-color: var(--aica-gray-100);
}

.aica-input-wrapper:focus-within {
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

#aica-message-input {
    flex: 1;
    border: none;
    background: none;
    resize: none;
    outline: none;
    font-size: 15px;
    min-height: 24px;
    max-height: 200px;
    line-height: 1.5;
    padding: 8px 0;
    color: var(--aica-gray-900);
}

body.aica-dark-mode #aica-message-input {
    color: var(--aica-gray-600);
}

#aica-message-input::placeholder {
    color: var(--aica-gray-500);
}

.aica-input-tools {
    display: flex;
    gap: 8px;
}

.aica-tool-button {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
    position: relative;
}

.aica-tool-button:hover {
    background-color: var(--aica-gray-200);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-tool-button:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-file-input {
    position: absolute;
    width: 0;
    height: 0;
    opacity: 0;
}

.aica-send-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background-color: var(--aica-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.aica-send-button:hover {
    background-color: var(--aica-primary-dark);
}

.aica-send-button:disabled {
    background-color: var(--aica-gray-300);
    cursor: not-allowed;
}

.aica-input-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 12px;
    color: var(--aica-gray-500);
}

.aica-model-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.aica-model-name {
    font-weight: 500;
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-model-name {
    color: var(--aica-gray-400);
}

/* Modal edycji tytułu */
.aica-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.aica-modal-content {
    background-color: white;
    border-radius: var(--aica-border-radius);
    width: 100%;
    max-width: 500px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

body.aica-dark-mode .aica-modal-content {
    background-color: var(--aica-secondary);
}

.aica-modal-header {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-modal-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.aica-modal-close {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-modal-close:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-modal-close:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-modal-body {
    padding: 16px;
}

.aica-form-field {
    margin-bottom: 16px;
}

.aica-form-field label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-form-field label {
    color: var(--aica-gray-500);
}

.aica-form-field input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--aica-gray-200);
    font-size: 14px;
    background-color: white;
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-form-field input {
    background-color: var(--aica-secondary-light);
    border-color: var(--aica-gray-100);
    color: var(--aica-gray-600);
}

.aica-form-field input:focus {
    outline: none;
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
}

.aica-modal-footer {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-modal-footer {
    border-top-color: var(--aica-gray-100);
}

/* Dialog potwierdzenia */
.aica-dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.aica-dialog-content {
    background-color: white;
    border-radius: var(--aica-border-radius);
    width: 100%;
    max-width: 400px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

body.aica-dark-mode .aica-dialog-content {
    background-color: var(--aica-secondary);
}

.aica-dialog-header {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-dialog-header {
    border-bottom-color: var(--aica-gray-100);
}

.aica-dialog-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.aica-dialog-close {
    background: none;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-gray-500);
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-dialog-close:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-900);
}

body.aica-dark-mode .aica-dialog-close:hover {
    background-color: var(--aica-gray-100);
    color: var(--aica-gray-700);
}

.aica-dialog-body {
    padding: 16px;
}

.aica-dialog-body p {
    margin: 0;
    font-size: 15px;
    line-height: 1.5;
    color: var(--aica-gray-700);
}

body.aica-dark-mode .aica-dialog-body p {
    color: var(--aica-gray-400);
}

.aica-dialog-footer {
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid var(--aica-gray-200);
}

body.aica-dark-mode .aica-dialog-footer {
    border-top-color: var(--aica-gray-100);
}

/* Stany ładowania */
.aica-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    text-align: center;
    color: var(--aica-gray-600);
}

.aica-spinner {
    width: 36px;
    height: 36px;
    border: 3px solid rgba(59, 130, 246, 0.2);
    border-radius: 50%;
    border-top-color: var(--aica-primary);
    animation: aica-spin 1s linear infinite;
    margin-bottom: 12px;
}

@keyframes aica-spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

/* Responsywność */
@media (max-width: 992px) {
    .aica-sidebar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 10;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .aica-sidebar.open {
        transform: translateX(0);
    }
    
    .aica-sidebar-toggle {
        display: none;
    }
    
    .aica-main-panel {
        width: 100%;
    }
    
    .aica-main-header {
        position: relative;
    }
    
    .aica-main-header::before {
        content: '';
        width: 32px;
        height: 32px;
        background-color: var(--aica-gray-100);
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--aica-gray-700);
        cursor: pointer;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="18" height="12" viewBox="0 0 18 12" fill="none"><path d="M1 1H17M1 6H17M1 11H17" stroke="%236B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>');
        background-position: center;
        background-repeat: no-repeat;
    }
    
    .aica-conversation-info {
        margin-left: 40px;
    }
}

@media (max-width: 576px) {
    .aica-main-actions .aica-action-button {
        width: 32px;
        height: 32px;
    }
    
    .aica-message {
        max-width: 90%;
    }
    
    .aica-input-container {
        padding: 12px;
    }
    
    .aica-examples {
        grid-template-columns: 1fr;
    }
    
    .aica-modal-content,
    .aica-dialog-content {
        max-width: 90%;
    }
}

/* Util classes */
.aica-hidden {
    display: none !important;
}

.aica-compact-view .aica-message-bubble {
    padding: 8px 12px;
    font-size: 14px;
}

.aica-compact-view .aica-input-wrapper {
    padding: 6px 10px;
}

.aica-compact-view #aica-message-input {
    padding: 6px 0;
    min-height: 20px;
}

.aica-compact-view .aica-welcome-screen h2 {
    font-size: 20px;
}

.aica-compact-view .aica-welcome-screen p {
    font-size: 14px;
}

/* Animacje */
@keyframes aica-fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes aica-slide-in {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.aica-message {
    animation: aica-slide-in 0.3s ease;
}

.aica-welcome-screen {
    animation: aica-fade-in 0.5s ease;
}

/* Oznaczanie tekstu kodem */
.aica-mention {
    background-color: rgba(59, 130, 246, 0.1);
    color: var(--aica-primary);
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 500;
}

body.aica-dark-mode .aica-mention {
    background-color: rgba(96, 165, 250, 0.2);
}
</style>

<!-- Skrypty JavaScript (dodajemy na końcu) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementy interfejsu
    const sidebar = document.querySelector('.aica-sidebar');
    const sidebarToggle = document.querySelector('.aica-sidebar-toggle');
    const tabs = document.querySelectorAll('.aica-tab');
    const tabContents = document.querySelectorAll('.aica-tab-content');
    const sessionsListEl = document.getElementById('aica-sessions-list');
    const messagesContainerEl = document.getElementById('aica-messages-container');
    const messagesEl = document.getElementById('aica-messages');
    const welcomeScreenEl = document.getElementById('aica-welcome-screen');
    const messageInputEl = document.getElementById('aica-message-input');
    const sendButton = document.getElementById('aica-send-message');
    const newChatButton = document.getElementById('aica-new-chat');
    const searchInput = document.querySelector('.aica-search-input');
    const darkModeToggle = document.getElementById('aica-dark-mode');
    const compactViewToggle = document.getElementById('aica-compact-view');
    const conversationTitleEl = document.getElementById('aica-conversation-title');
    const conversationDateEl = document.getElementById('aica-conversation-date');
    const exportButton = document.getElementById('aica-export-chat');
    const copyConversationButton = document.getElementById('aica-copy-conversation');
    const renameConversationButton = document.getElementById('aica-rename-conversation');
    const deleteConversationButton = document.getElementById('aica-delete-conversation');
    const moreButton = document.querySelector('.aica-more-button');
    const dropdown = document.querySelector('.aica-dropdown');
    const examplePrompts = document.querySelectorAll('.aica-example-prompt');
    const fileUploadEl = document.getElementById('aica-file-upload');
    const repoButton = document.querySelector('.aica-repo-button');
    const filePreviewEl = document.getElementById('aica-file-preview');
    const closePreviewButton = document.querySelector('.aica-close-preview-button');
    const analyzeButton = document.querySelector('.aica-analyze-button');
    const selectedFileInfoEl = document.getElementById('aica-selected-file-info');
    const selectedFileNameEl = document.getElementById('aica-selected-file-name');
    const removeFileButton = document.querySelector('.aica-remove-file-button');
    const saveSettingsButton = document.getElementById('aica-save-settings');
    const renameModal = document.getElementById('aica-rename-modal');
    const newTitleInput = document.getElementById('aica-conversation-new-title');
    const modalSaveButton = document.querySelector('.aica-modal-save');
    const modalCancelButton = document.querySelector('.aica-modal-cancel');
    const modalCloseButton = document.querySelector('.aica-modal-close');
    const deleteDialog = document.getElementById('aica-delete-dialog');
    const dialogConfirmButton = document.querySelector('.aica-dialog-confirm');
    const dialogCancelButton = document.querySelector('.aica-dialog-cancel');
    const dialogCloseButton = document.querySelector('.aica-dialog-close');
    const maxTokensSlider = document.getElementById('aica-max-tokens');
    const maxTokensValue = document.getElementById('aica-max-tokens-value');
    const modelSelect = document.getElementById('aica-model');
    const modelNameEl = document.getElementById('aica-model-name');
    const typingIndicator = document.querySelector('.aica-status-typing');
    
    // Zmienne stanu
    let currentSessionId = null;
    let isTyping = false;
    let selectedFile = null;
    let darkMode = localStorage.getItem('aica_dark_mode') === 'true';
    let compactView = localStorage.getItem('aica_compact_view') === 'true';
    
    // Inicjalizacja interfejsu
    function initUI() {
        updateDarkMode();
        updateCompactView();
        loadSessions();
        
        // Sprawdzamy, czy jest zapisana ostatnia sesja
        const lastSessionId = localStorage.getItem('aica_last_session');
        if (lastSessionId) {
            loadSession(lastSessionId);
        } else {
            showWelcomeScreen();
        }
        
        // Nasłuchiwanie zmiany wysokości pola tekstowego
        messageInputEl.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            updateSendButtonState();
        });
    }
    
    // Przełączanie trybu ciemnego
    function updateDarkMode() {
        if (darkMode) {
            document.body.classList.add('aica-dark-mode');
            if (darkModeToggle) darkModeToggle.checked = true;
        } else {
            document.body.classList.remove('aica-dark-mode');
            if (darkModeToggle) darkModeToggle.checked = false;
        }
        localStorage.setItem('aica_dark_mode', darkMode);
    }
    
    // Przełączanie widoku kompaktowego
    function updateCompactView() {
        if (compactView) {
            document.body.classList.add('aica-compact-view');
            if (compactViewToggle) compactViewToggle.checked = true;
        } else {
            document.body.classList.remove('aica-compact-view');
            if (compactViewToggle) compactViewToggle.checked = false;
        }
        localStorage.setItem('aica_compact_view', compactView);
    }
    
    // Pokazywanie ekranu powitalnego
    function showWelcomeScreen() {
        welcomeScreenEl.style.display = 'flex';
        messagesEl.style.display = 'none';
        filePreviewEl.style.display = 'none';
        currentSessionId = null;
        conversationTitleEl.textContent = '<?php _e('Nowa rozmowa', 'ai-chat-assistant'); ?>';
        conversationDateEl.textContent = '';
    }
    
    // Pokazywanie obszaru wiadomości
    function showMessagesArea() {
        welcomeScreenEl.style.display = 'none';
        messagesEl.style.display = 'flex';
        filePreviewEl.style.display = 'none';
    }
    
    // Pokazywanie podglądu pliku
    function showFilePreview() {
        welcomeScreenEl.style.display = 'none';
        messagesEl.style.display = 'none';
        filePreviewEl.style.display = 'flex';
    }
    
    // Ładowanie sesji czatu
    function loadSessions() {
        // Symulacja ładowania sesji z API
        setTimeout(function() {
            // Przykładowe dane
            const sessions = [
                {
                    id: 'session1',
                    title: 'Optymalizacja kodu WordPress',
                    date: '2025-05-15',
                    preview: 'Jak mogę zoptymalizować ten kod zapytania do bazy danych?'
                },
                {
                    id: 'session2',
                    title: 'Debugowanie błędu w wtyczce',
                    date: '2025-05-14',
                    preview: 'Mam problem z wtyczką WooCommerce.'
                },
                {
                    id: 'session3',
                    title: 'Implementacja REST API',
                    date: '2025-05-10',
                    preview: 'Jak mogę zaimplementować własny endpoint REST API?'
                }
            ];
            
            // Renderowanie listy sesji
            sessionsListEl.innerHTML = '';
            sessions.forEach(session => {
                const sessionItem = document.createElement('div');
                sessionItem.className = 'aica-session-item';
                sessionItem.dataset.id = session.id;
                
                const date = new Date(session.date);
                const formattedDate = date.toLocaleDateString();
                
                sessionItem.innerHTML = `
                    <div class="aica-session-item-header">
                        <div class="aica-session-title">${session.title}</div>
                        <div class="aica-session-date">${formattedDate}</div>
                    </div>
                    <div class="aica-session-preview">${session.preview}</div>
                `;
                
                sessionItem.addEventListener('click', function() {
                    loadSession(session.id);
                });
                
                sessionsListEl.appendChild(sessionItem);
            });
        }, 500);
    }
    
    // Ładowanie konkretnej sesji
    function loadSession(sessionId) {
        // Symulacja ładowania sesji z API
        setTimeout(function() {
            // Przykładowe dane
            const session = {
                id: sessionId,
                title: sessionId === 'session1' ? 'Optymalizacja kodu WordPress' :
                       sessionId === 'session2' ? 'Debugowanie błędu w wtyczce' :
                       'Implementacja REST API',
                date: sessionId === 'session1' ? '2025-05-15' :
                      sessionId === 'session2' ? '2025-05-14' :
                      '2025-05-10',
                messages: [
                    {
                        role: 'user',
                        content: sessionId === 'session1' ? 'Jak mogę zoptymalizować ten kod zapytania do bazy danych?' :
                                sessionId === 'session2' ? 'Mam problem z wtyczką WooCommerce.' :
                                'Jak mogę zaimplementować własny endpoint REST API?',
                        time: '10:25'
                    },
                    {
                        role: 'assistant',
                        content: sessionId === 'session1' ? 'Aby zoptymalizować zapytanie do bazy danych, warto zastosować kilka praktyk:<ul><li>Używaj indeksów dla kolumn, po których często wyszukujesz</li><li>Unikaj używania SELECT * i zamiast tego wybieraj tylko potrzebne kolumny</li><li>Używaj LIMIT, aby ograniczyć ilość zwracanych wyników</li><li>Stosuj JOIN zamiast zagnieżdżonych zapytań</li></ul>Możesz również skorzystać z narzędzi do analizy zapytań, takich jak EXPLAIN, aby sprawdzić, jak MySQL przetwarza twoje zapytanie.' :
                                sessionId === 'session2' ? 'Aby zdiagnozować problem z wtyczką WooCommerce, wykonaj następujące kroki:<ol><li>Wyłącz wszystkie inne wtyczki i sprawdź, czy problem nadal występuje</li><li>Włącz tryb debugowania WP przez dodanie <code>define(\'WP_DEBUG\', true);</code> do pliku wp-config.php</li><li>Sprawdź logi błędów</li><li>Zastosuj domyślny motyw i sprawdź, czy problem nadal występuje</li></ol>' :
                                'Aby zaimplementować własny endpoint REST API w WordPress, należy użyć funkcji <code>register_rest_route()</code>. Oto przykład:<pre><code>add_action(\'rest_api_init\', function() {\n  register_rest_route(\'moja-wtyczka/v1\', \'/dane\', [\n    \'methods\' => \'GET\',\n    \'callback\' => \'moja_funkcja_callback\',\n    \'permission_callback\' => function() {\n      return current_user_can(\'edit_posts\');\n    }\n  ]);\n});</code></pre>',
                        time: '10:26'
                    }
                ]
            };
            
            // Aktualizacja UI
            currentSessionId = sessionId;
            localStorage.setItem('aica_last_session', sessionId);
            
            conversationTitleEl.textContent = session.title;
            const date = new Date(session.date);
            conversationDateEl.textContent = date.toLocaleDateString();
            
            // Renderowanie wiadomości
            messagesEl.innerHTML = '';
            session.messages.forEach(message => {
                const messageEl = document.createElement('div');
                messageEl.className = `aica-message aica-${message.role}-message`;
                
                messageEl.innerHTML = `
                    <div class="aica-message-bubble">${message.content}</div>
                    <div class="aica-message-info">${message.time}</div>
                `;
                
                messagesEl.appendChild(messageEl);
            });
            
            // Oznaczenie aktywnej sesji na liście
            document.querySelectorAll('.aica-session-item').forEach(item => {
                if (item.dataset.id === sessionId) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
            
            showMessagesArea();
            scrollToBottom();
        }, 300);
    }
    
    // Tworzenie nowej sesji
    function createNewSession() {
        showWelcomeScreen();
        messagesEl.innerHTML = '';
        currentSessionId = null;
        localStorage.removeItem('aica_last_session');
        
        // Odznaczenie wszystkich sesji na liście
        document.querySelectorAll('.aica-session-item').forEach(item => {
            item.classList.remove('active');
        });
    }
    
    // Wysyłanie wiadomości
    function sendMessage() {
        if (messageInputEl.value.trim() === '' && !selectedFile) return;
        
        const userMessage = messageInputEl.value.trim();
        messageInputEl.value = '';
        messageInputEl.style.height = 'auto';
        updateSendButtonState();
        
        // Jeśli jest to nowa rozmowa, ukryj ekran powitalny
        if (!currentSessionId) {
            showMessagesArea();
        }
        
        // Dodaj wiadomość użytkownika
        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        let messageContent = userMessage;
        
        if (selectedFile) {
            messageContent += `<div class="aica-file-reference">
                <span class="dashicons dashicons-media-code"></span>
                <span>${selectedFile.name}</span>
            </div>`;
        }
        
        const messageEl = document.createElement('div');
        messageEl.className = 'aica-message aica-user-message';
        messageEl.innerHTML = `
            <div class="aica-message-bubble">${messageContent}</div>
            <div class="aica-message-info">${time}</div>
        `;
        
        messagesEl.appendChild(messageEl);
        scrollToBottom();
        
        // Resetuj informacje o wybranym pliku
        selectedFile = null;
        selectedFileInfoEl.style.display = 'none';
        
        // Symulacja odpowiedzi asystenta
        setTyping(true);
        setTimeout(() => {
            // Przykładowa odpowiedź asystenta
            const assistantMessageEl = document.createElement('div');
            assistantMessageEl.className = 'aica-message aica-ai-message';
            
            let responseContent = '';
            
            if (userMessage.toLowerCase().includes('wordpress') || userMessage.toLowerCase().includes('wtyczka')) {
                responseContent = `WordPress to popularny system zarządzania treścią (CMS) oparty na PHP. Jeśli masz konkretne pytanie dotyczące WordPressa lub wtyczek, chętnie pomogę. Mogę wyjaśnić:
                <ul>
                    <li>Jak tworzyć własne wtyczki</li>
                    <li>Jak używać hooks (actions i filters)</li>
                    <li>Jak pracować z bazą danych WordPress</li>
                    <li>Jak tworzyć własne bloki Gutenberg</li>
                </ul>
                Powiedz, co dokładnie Cię interesuje.`;
            } else if (userMessage.toLowerCase().includes('kod') || userMessage.toLowerCase().includes('function')) {
                responseContent = `Analiza kodu i optymalizacja to ważne aspekty programowania. Oto kilka ogólnych wskazówek:
                <ol>
                    <li>Używaj odpowiednich algorytmów i struktur danych</li>
                    <li>Unikaj zbędnych operacji i powtarzania kodu</li>
                    <li>Stosuj odpowiednie nazewnictwo zmiennych i funkcji</li>
                    <li>Dodawaj komentarze w kluczowych miejscach</li>
                </ol>
                Jeśli chcesz, mogę przeanalizować konkretny fragment kodu.`;
            } else {
                responseContent = `Dziękuję za wiadomość. Jak mogę Ci pomóc? Jestem Claude, asystent AI, który może pomóc w kwestiach związanych z programowaniem, WordPress, tworzeniem stron internetowych i wieloma innymi tematami.`;
            }
            
            assistantMessageEl.innerHTML = `
                <div class="aica-message-bubble">${responseContent}</div>
                <div class="aica-message-info">${time}</div>
            `;
            
            messagesEl.appendChild(assistantMessageEl);
            scrollToBottom();
            setTyping(false);
            
            // Jeśli jest to pierwsza wiadomość w nowej rozmowie, nadaj jej tytuł
            if (!currentSessionId) {
                const title = userMessage.length > 30 ? userMessage.substring(0, 30) + '...' : userMessage;
                conversationTitleEl.textContent = title;
                conversationDateEl.textContent = now.toLocaleDateString();
                
                // Symulacja zapisania nowej sesji
                currentSessionId = 'session' + Math.floor(Math.random() * 1000);
                localStorage.setItem('aica_last_session', currentSessionId);
                
                // Dodanie nowej sesji do listy
                const sessionItem = document.createElement('div');
                sessionItem.className = 'aica-session-item active';
                sessionItem.dataset.id = currentSessionId;
                
                sessionItem.innerHTML = `
                    <div class="aica-session-item-header">
                        <div class="aica-session-title">${title}</div>
                        <div class="aica-session-date">${now.toLocaleDateString()}</div>
                    </div>
                    <div class="aica-session-preview">${userMessage}</div>
                `;
                
                sessionItem.addEventListener('click', function() {
                    loadSession(currentSessionId);
                });
                
                sessionsListEl.insertBefore(sessionItem, sessionsListEl.firstChild);
            }
        }, 1500);
    }
    
    // Stan wpisywania
    function setTyping(typing) {
        isTyping = typing;
        if (typing) {
            typingIndicator.style.display = 'block';
        } else {
            typingIndicator.style.display = 'none';
        }
    }
    
    // Przewijanie do dołu
    function scrollToBottom() {
        messagesContainerEl.scrollTop = messagesContainerEl.scrollHeight;
    }
    
    // Aktualizacja stanu przycisku wysyłania
    function updateSendButtonState() {
        if (messageInputEl.value.trim() !== '' || selectedFile) {
            sendButton.disabled = false;
        } else {
            sendButton.disabled = true;
        }
    }
    
    // Eksportowanie rozmowy
    function exportConversation() {
        if (!currentSessionId) return;
        
        // Zbieranie wiadomości
        const messages = [];
        document.querySelectorAll('.aica-message').forEach(messageEl => {
            const role = messageEl.classList.contains('aica-user-message') ? 'Użytkownik' : 'Claude';
            const content = messageEl.querySelector('.aica-message-bubble').innerHTML;
            const time = messageEl.querySelector('.aica-message-info').textContent;
            
            messages.push(`[${time}] ${role}: ${content}`);
        });
        
        // Tworzenie zawartości pliku
        const title = conversationTitleEl.textContent;
        const date = conversationDateEl.textContent;
        const content = `# ${title}\n${date}\n\n${messages.join('\n\n')}`;
        
        // Tworzenie i pobieranie pliku
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_${Date.now()}.md`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Kopiowanie całej rozmowy
    function copyConversation() {
        if (!currentSessionId) return;
        
        // Zbieranie wiadomości
        const messages = [];
        document.querySelectorAll('.aica-message').forEach(messageEl => {
            const role = messageEl.classList.contains('aica-user-message') ? 'Użytkownik' : 'Claude';
            const content = messageEl.querySelector('.aica-message-bubble').textContent;
            
            messages.push(`${role}: ${content}`);
        });
        
        // Kopiowanie do schowka
        const content = messages.join('\n\n');
        navigator.clipboard.writeText(content).then(() => {
            alert('Rozmowa skopiowana do schowka');
        }).catch(err => {
            console.error('Nie udało się skopiować tekstu: ', err);
        });
    }
    
    // Zmiana tytułu rozmowy
    function showRenameModal() {
        if (!currentSessionId) return;
        
        newTitleInput.value = conversationTitleEl.textContent;
        renameModal.style.display = 'flex';
        newTitleInput.focus();
    }
    
    function renameConversation() {
        const newTitle = newTitleInput.value.trim();
        if (newTitle === '') return;
        
        conversationTitleEl.textContent = newTitle;
        
        // Aktualizacja tytułu na liście sesji
        const sessionItem = document.querySelector(`.aica-session-item[data-id="${currentSessionId}"]`);
        if (sessionItem) {
            sessionItem.querySelector('.aica-session-title').textContent = newTitle;
        }
        
        renameModal.style.display = 'none';
    }
    
    // Usuwanie rozmowy
    function showDeleteDialog() {
        if (!currentSessionId) return;
        
        deleteDialog.style.display = 'flex';
    }
    
    function deleteConversation() {
        // Usunięcie z listy
        const sessionItem = document.querySelector(`.aica-session-item[data-id="${currentSessionId}"]`);
        if (sessionItem) {
            sessionItem.remove();
        }
        
        // Wyczyszczenie localStorage jeśli to była aktywna sesja
        if (localStorage.getItem('aica_last_session') === currentSessionId) {
            localStorage.removeItem('aica_last_session');
        }
        
        // Powrót do ekranu powitalnego
        createNewSession();
        
        deleteDialog.style.display = 'none';
    }
    
    // Obsługa plików
    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        selectedFile = file;
        selectedFileNameEl.textContent = file.name;
        selectedFileInfoEl.style.display = 'flex';
        updateSendButtonState();
    }
    
    function removeSelectedFile() {
        selectedFile = null;
        selectedFileInfoEl.style.display = 'none';
        fileUploadEl.value = '';
        updateSendButtonState();
    }
    
    // Obsługa repozytoriów
    function loadRepositories() {
        // Przełączenie na zakładkę repozytoriów
        tabs.forEach(tab => {
            if (tab.dataset.tab === 'repositories') {
                tab.click();
            }
        });
    }
    
    // Zapisywanie ustawień
    function saveSettings() {
        darkMode = darkModeToggle.checked;
        compactView = compactViewToggle.checked;
        const maxTokens = maxTokensSlider.value;
        const model = modelSelect.value;
        
        updateDarkMode();
        updateCompactView();
        modelNameEl.textContent = model;
        
        // Symulacja zapisania ustawień w bazie danych
        console.log('Zapisano ustawienia:', {
            darkMode,
            compactView,
            maxTokens,
            model
        });
        
        alert('Ustawienia zostały zapisane');
    }
    
    // Event listeners
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Aktywacja zakładki
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Pokazanie zawartości zakładki
            tabContents.forEach(content => {
                if (content.id === `${tabName}-content`) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });
    
    sendButton.addEventListener('click', sendMessage);
    
    messageInputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendButton.disabled) {
                sendMessage();
            }
        }
    });
    
    newChatButton.addEventListener('click', createNewSession);
    
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        
        document.querySelectorAll('.aica-session-item').forEach(item => {
            const title = item.querySelector('.aica-session-title').textContent.toLowerCase();
            const preview = item.querySelector('.aica-session-preview').textContent.toLowerCase();
            
            if (title.includes(query) || preview.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    darkModeToggle?.addEventListener('change', function() {
        darkMode = this.checked;
        updateDarkMode();
    });
    
    compactViewToggle?.addEventListener('change', function() {
        compactView = this.checked;
        updateCompactView();
    });
    
    exportButton.addEventListener('click', exportConversation);
    copyConversationButton.addEventListener('click', copyConversation);
    renameConversationButton.addEventListener('click', showRenameModal);
    deleteConversationButton.addEventListener('click', showDeleteDialog);
    
    moreButton.addEventListener('click', function() {
        dropdown.classList.toggle('open');
    });
    
    // Zamykanie dropdown po kliknięciu poza nim
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.aica-dropdown') && dropdown.classList.contains('open')) {
            dropdown.classList.remove('open');
        }
    });
    
    examplePrompts.forEach(prompt => {
        prompt.addEventListener('click', function() {
            messageInputEl.value = this.textContent;
            messageInputEl.style.height = 'auto';
            messageInputEl.style.height = (messageInputEl.scrollHeight) + 'px';
            updateSendButtonState();
            messageInputEl.focus();
        });
    });
    
    fileUploadEl.addEventListener('change', handleFileSelect);
    removeFileButton.addEventListener('click', removeSelectedFile);
    
    repoButton.addEventListener('click', loadRepositories);
    
    closePreviewButton.addEventListener('click', function() {
        showMessagesArea();
    });
    
    analyzeButton.addEventListener('click', function() {
        showMessagesArea();
        messageInputEl.value = 'Przeanalizuj ten plik: [nazwa_pliku]';
        messageInputEl.style.height = 'auto';
        messageInputEl.style.height = (messageInputEl.scrollHeight) + 'px';
        updateSendButtonState();
        messageInputEl.focus();
    });
    
    saveSettingsButton.addEventListener('click', saveSettings);
    
    modalSaveButton.addEventListener('click', renameConversation);
    modalCancelButton.addEventListener('click', function() {
        renameModal.style.display = 'none';
    });
    modalCloseButton.addEventListener('click', function() {
        renameModal.style.display = 'none';
    });
    
    dialogConfirmButton.addEventListener('click', deleteConversation);
    dialogCancelButton.addEventListener('click', function() {
        deleteDialog.style.display = 'none';
    });
    dialogCloseButton.addEventListener('click', function() {
        deleteDialog.style.display = 'none';
    });
    
    maxTokensSlider?.addEventListener('input', function() {
        maxTokensValue.textContent = this.value;
    });
    
    // Inicjalizacja interfejsu po załadowaniu dokumentu
    initUI();
    
    // Automatyczne zamykanie modali po kliknięciu poza nimi
    window.addEventListener('click', function(event) {
        if (event.target === renameModal) {
            renameModal.style.display = 'none';
        }
        if (event.target === deleteDialog) {
            deleteDialog.style.display = 'none';
        }
    });
    
    // Obsługa podświetlania składni kodu
    const codeHighlightingToggle = document.getElementById('aica-code-highlighting');
    
    // Funkcja do inicjalizacji Prism.js (jeśli dostępny)
    function initCodeHighlighting() {
        if (window.Prism && codeHighlightingToggle?.checked) {
            Prism.highlightAll();
        }
    }
    
    // Obserwator do wykrywania nowych bloków kodu
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                initCodeHighlighting();
            }
        });
    });
    
    // Obserwacja kontenera wiadomości
    observer.observe(messagesEl, { childList: true, subtree: true });
    
    // Przełączanie podświetlania kodu
    codeHighlightingToggle?.addEventListener('change', initCodeHighlighting);
    
    // Obsługa automatycznego podpowiadania wzmianek plików
    messageInputEl.addEventListener('input', function(e) {
        const text = this.value;
        const lastWord = text.split(/\s/).pop();
        
        if (lastWord.startsWith('@')) {
            // Tu można dodać logikę wyświetlania podpowiedzi plików
            console.log('Wpisywanie wzmianki: ' + lastWord);
        }
    });
});
</script>