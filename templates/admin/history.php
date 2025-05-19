<?php
/**
 * Szablon strony historii
 *
 * @package AI_Chat_Assistant
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap aica-admin-container">
    <div class="aica-header">
        <h1><?php _e('Historia rozmów', 'ai-chat-assistant'); ?></h1>
        <div class="aica-header-actions">
            <div class="aica-search-container">
                <input type="text" id="aica-search-input" placeholder="<?php _e('Szukaj rozmów...', 'ai-chat-assistant'); ?>" />
                <button type="button" id="aica-search-button" class="aica-search-button">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <div class="aica-filter-container">
                <button type="button" class="button aica-filter-button">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Filtry', 'ai-chat-assistant'); ?>
                </button>
                <div class="aica-filter-dropdown" style="display: none;">
                    <div class="aica-filter-group">
                        <h3><?php _e('Sortowanie', 'ai-chat-assistant'); ?></h3>
                        <label>
                            <input type="radio" name="sort" value="newest" checked />
                            <?php _e('Najnowsze', 'ai-chat-assistant'); ?>
                        </label>
                        <label>
                            <input type="radio" name="sort" value="oldest" />
                            <?php _e('Najstarsze', 'ai-chat-assistant'); ?>
                        </label>
                    </div>
                    <div class="aica-filter-group">
                        <h3><?php _e('Zakres dat', 'ai-chat-assistant'); ?></h3>
                        <div class="aica-date-range">
                            <input type="date" class="aica-date-from" placeholder="<?php _e('Od', 'ai-chat-assistant'); ?>" />
                            <span class="aica-date-separator">-</span>
                            <input type="date" class="aica-date-to" placeholder="<?php _e('Do', 'ai-chat-assistant'); ?>" />
                        </div>
                    </div>
                    <div class="aica-filter-actions">
                        <button type="button" class="button button-secondary aica-reset-filters"><?php _e('Reset', 'ai-chat-assistant'); ?></button>
                        <button type="button" class="button button-primary aica-apply-filters"><?php _e('Zastosuj', 'ai-chat-assistant'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="aica-history-container">
        <!-- Tu będzie wczytywana zawartość przez JavaScript -->
        <div class="aica-loading">
            <div class="aica-loading-spinner"></div>
            <p><?php _e('Ładowanie...', 'ai-chat-assistant'); ?></p>
        </div>
    </div>

    <!-- Dialog Potwierdzający Usunięcie -->
    <div id="aica-delete-dialog" class="aica-dialog" style="display: none;">
        <div class="aica-dialog-content">
            <div class="aica-dialog-header">
                <h3><?php _e('Potwierdź usunięcie', 'ai-chat-assistant'); ?></h3>
                <button type="button" class="aica-dialog-close" aria-label="<?php _e('Zamknij', 'ai-chat-assistant'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aica-dialog-body">
                <p><?php _e('Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.', 'ai-chat-assistant'); ?></p>
            </div>
            <div class="aica-dialog-footer">
                <button type="button" class="button button-secondary aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
                <button type="button" class="button button-primary aica-dialog-confirm aica-delete-confirm"><?php _e('Usuń', 'ai-chat-assistant'); ?></button>
            </div>
        </div>
    </div>
</div>