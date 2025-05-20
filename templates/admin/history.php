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
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-chat-assistant')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Nowa rozmowa', 'ai-chat-assistant'); ?>
            </a>
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
    <div id="aica-delete-dialog" class="aica-dialog" style="display: none;" data-session-id="">
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

<script>
    // Przekazanie nonce do skryptu
    var aica_history = {
        nonce: '<?php echo esc_js($history_nonce); ?>',
        chat_url: '<?php echo esc_js(admin_url('admin.php?page=ai-chat-assistant')); ?>',
        i18n: {
            loading: '<?php echo esc_js(__('Ładowanie...', 'ai-chat-assistant')); ?>',
            loading_messages: '<?php echo esc_js(__('Ładowanie wiadomości...', 'ai-chat-assistant')); ?>',
            no_conversations: '<?php echo esc_js(__('Brak rozmów', 'ai-chat-assistant')); ?>',
            no_conversations_desc: '<?php echo esc_js(__('Nie masz jeszcze żadnych rozmów.', 'ai-chat-assistant')); ?>',
            new_conversation: '<?php echo esc_js(__('Nowa rozmowa', 'ai-chat-assistant')); ?>',
            no_messages: '<?php echo esc_js(__('Brak wiadomości w tej rozmowie.', 'ai-chat-assistant')); ?>',
            load_error: '<?php echo esc_js(__('Wystąpił błąd podczas ładowania danych.', 'ai-chat-assistant')); ?>',
            confirm_delete: '<?php echo esc_js(__('Czy na pewno chcesz usunąć tę rozmowę? Tej operacji nie można cofnąć.', 'ai-chat-assistant')); ?>',
            delete_error: '<?php echo esc_js(__('Wystąpił błąd podczas usuwania rozmowy.', 'ai-chat-assistant')); ?>',
            duplicate_success: '<?php echo esc_js(__('Rozmowa została pomyślnie zduplikowana.', 'ai-chat-assistant')); ?>',
            duplicate_error: '<?php echo esc_js(__('Wystąpił błąd podczas duplikowania rozmowy.', 'ai-chat-assistant')); ?>',
            min_search_length: '<?php echo esc_js(__('Wprowadź co najmniej 3 znaki, aby wyszukać.', 'ai-chat-assistant')); ?>',
            pagination_info: '<?php echo esc_js(__('Wyniki %1$s - %2$s z %3$s', 'ai-chat-assistant')); ?>',
            user: '<?php echo esc_js(__('Użytkownik', 'ai-chat-assistant')); ?>',
            continue_conversation: '<?php echo esc_js(__('Kontynuuj rozmowę', 'ai-chat-assistant')); ?>',
            duplicate: '<?php echo esc_js(__('Duplikuj', 'ai-chat-assistant')); ?>',
            export: '<?php echo esc_js(__('Eksportuj', 'ai-chat-assistant')); ?>',
            delete: '<?php echo esc_js(__('Usuń', 'ai-chat-assistant')); ?>',
            just_now: '<?php echo esc_js(__('Przed chwilą', 'ai-chat-assistant')); ?>',
            second: '<?php echo esc_js(__('sekunda', 'ai-chat-assistant')); ?>',
            seconds: '<?php echo esc_js(__('sekund', 'ai-chat-assistant')); ?>',
            minute: '<?php echo esc_js(__('minuta', 'ai-chat-assistant')); ?>',
            minutes: '<?php echo esc_js(__('minut', 'ai-chat-assistant')); ?>',
            hour: '<?php echo esc_js(__('godzina', 'ai-chat-assistant')); ?>',
            hours: '<?php echo esc_js(__('godzin', 'ai-chat-assistant')); ?>',
            day: '<?php echo esc_js(__('dzień', 'ai-chat-assistant')); ?>',
            days: '<?php echo esc_js(__('dni', 'ai-chat-assistant')); ?>',
            month: '<?php echo esc_js(__('miesiąc', 'ai-chat-assistant')); ?>',
            months: '<?php echo esc_js(__('miesięcy', 'ai-chat-assistant')); ?>',
            year: '<?php echo esc_js(__('rok', 'ai-chat-assistant')); ?>',
            years: '<?php echo esc_js(__('lat', 'ai-chat-assistant')); ?>',
            ago: '<?php echo esc_js(__('temu', 'ai-chat-assistant')); ?>'
        }
    };
</script>