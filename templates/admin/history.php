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

// Obsługa paginacji
$per_page = 10;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Pobranie łącznej liczby sesji
$total_sessions = count($sessions);
$total_pages = ceil($total_sessions / $per_page);

// Ograniczenie sesji do bieżącej strony
$sessions_paged = array_slice($sessions, $offset, $per_page);
?>

<div class="wrap aica-admin-container">
    <div class="aica-header">
        <h1><?php _e('Historia rozmów', 'ai-chat-assistant'); ?></h1>
        <div class="aica-header-actions">
            <div class="aica-search-container">
                <input type="text" id="aica-search-conversations" placeholder="<?php _e('Szukaj rozmów...', 'ai-chat-assistant'); ?>" />
                <button type="button" class="aica-search-button">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <div class="aica-filter-container">
                <button type="button" class="button aica-filter-button">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Filtry', 'ai-chat-assistant'); ?>
                </button>
                <div class="aica-filter-dropdown">
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

    <?php if (empty($sessions)): ?>
        <div class="aica-empty-state">
            <div class="aica-empty-icon">
                <span class="dashicons dashicons-format-chat"></span>
            </div>
            <h2><?php _e('Nie znaleziono żadnych rozmów', 'ai-chat-assistant'); ?></h2>
            <p><?php _e('Nie przeprowadzono jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant'); ?>" class="button button-primary"><?php _e('Rozpocznij nową rozmowę', 'ai-chat-assistant'); ?></a>
        </div>
    <?php else: ?>
        <div class="aica-history-container">
            <?php foreach ($sessions_paged as $index => $session): ?>
                <div class="aica-history-card" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                    <div class="aica-card-header">
                        <div class="aica-card-title">
                            <h3><?php echo esc_html($session['title']); ?></h3>
                            <span class="aica-session-id"><?php echo esc_html(substr($session['session_id'], 0, 12)); ?>...</span>
                        </div>
                        <div class="aica-card-actions">
                            <button type="button" class="aica-card-expand" aria-label="<?php _e('Rozwiń', 'ai-chat-assistant'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <div class="aica-dropdown">
                                <button type="button" class="aica-dropdown-toggle" aria-label="<?php _e('Menu', 'ai-chat-assistant'); ?>">
                                    <span class="dashicons dashicons-ellipsis"></span>
                                </button>
                                <div class="aica-dropdown-menu">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-chat-assistant&session_id=' . $session['session_id'])); ?>" class="aica-dropdown-item">
                                        <span class="dashicons dashicons-format-chat"></span>
                                        <?php _e('Kontynuuj rozmowę', 'ai-chat-assistant'); ?>
                                    </a>
                                    <a href="#" class="aica-dropdown-item aica-copy-session" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        <?php _e('Duplikuj', 'ai-chat-assistant'); ?>
                                    </a>
                                    <a href="#" class="aica-dropdown-item aica-export-session" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php _e('Eksportuj', 'ai-chat-assistant'); ?>
                                    </a>
                                    <div class="aica-dropdown-divider"></div>
                                    <a href="#" class="aica-dropdown-item aica-delete-session text-danger" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Usuń', 'ai-chat-assistant'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="aica-card-body">
                        <div class="aica-meta-info">
                            <div class="aica-meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <span class="aica-meta-text"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($session['created_at']))); ?></span>
                            </div>
                            <div class="aica-meta-item">
                                <span class="dashicons dashicons-clock"></span>
                                <span class="aica-meta-text"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($session['created_at']))); ?></span>
                            </div>
                            <div class="aica-meta-item">
                                <span class="dashicons dashicons-update"></span>
                                <span class="aica-meta-text"><?php echo esc_html(human_time_diff(strtotime($session['updated_at']), current_time('timestamp'))); ?> <?php _e('temu', 'ai-chat-assistant'); ?></span>
                            </div>
                        </div>
                        <div class="aica-conversation-preview">
                            <?php 
                            // Pobierz pierwsze kilka wiadomości z rozmowy
                            global $wpdb;
                            $table = $wpdb->prefix . 'aica_messages';
                            $messages = $wpdb->get_results(
                                $wpdb->prepare(
                                    "SELECT message, response FROM $table WHERE session_id = %s ORDER BY id ASC LIMIT 1",
                                    $session['session_id']
                                ),
                                ARRAY_A
                            );
                            
                            if (!empty($messages)) {
                                $first_message = $messages[0];
                                $truncated_message = wp_trim_words($first_message['message'], 20, '...');
                                echo '<div class="aica-message-preview">';
                                echo '<span class="aica-message-preview-label">' . __('Użytkownik:', 'ai-chat-assistant') . '</span>';
                                echo '<span class="aica-message-preview-content">' . esc_html($truncated_message) . '</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="aica-card-footer">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ai-chat-assistant&session_id=' . $session['session_id'])); ?>" class="button button-primary aica-continue-button">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php _e('Kontynuuj rozmowę', 'ai-chat-assistant'); ?>
                        </a>
                    </div>
                    <div class="aica-card-expanded" style="display: none;">
                        <div class="aica-loading-messages">
                            <div class="aica-loading-spinner"></div>
                            <p><?php _e('Ładowanie wiadomości...', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-messages-container"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div class="aica-pagination">
                    <?php
                    $links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span>',
                        'next_text' => '<span class="dashicons dashicons-arrow-right-alt2"></span>',
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'array'
                    ));
                    
                    if (is_array($links)) {
                        echo '<div class="aica-pagination-links">';
                        foreach ($links as $link) {
                            echo str_replace('page-numbers', 'aica-page-link', $link);
                        }
                        echo '</div>';
                        
                        echo '<div class="aica-pagination-info">';
                        $from = (($current_page - 1) * $per_page) + 1;
                        $to = min($current_page * $per_page, $total_sessions);
                        printf(__('Wyświetlanie %1$s do %2$s z %3$s rozmów', 'ai-chat-assistant'), $from, $to, $total_sessions);
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php endif; ?>
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
    <?php endif; ?>
</div>

<style>
:root {
    --aica-primary: #2271b1;
    --aica-primary-light: #72aee6;
    --aica-primary-dark: #135e96;
    --aica-success: #00a32a;
    --aica-success-light: #edfaef;
    --aica-warning: #dba617;
    --aica-warning-light: #fcf9e8;
    --aica-error: #d63638;
    --aica-error-light: #fcf0f1;
    --aica-text: #1e1e1e;
    --aica-text-light: #757575;
    --aica-border: #e0e0e0;
    --aica-bg-light: #f9f9f9;
    --aica-card-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Admin Container */
.aica-admin-container {
    max-width: 1200px;
    margin: 20px auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Header */
.aica-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.aica-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    padding: 0;
}

.aica-header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

/* Search container */
.aica-search-container {
    position: relative;
    max-width: 300px;
    width: 100%;
}

.aica-search-container input {
    width: 100%;
    padding: 8px 36px 8px 12px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 14px;
}

.aica-search-button {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 10px;
    color: var(--aica-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
}

.aica-search-button:hover {
    color: var(--aica-primary);
}

/* Filter container */
.aica-filter-container {
    position: relative;
}

.aica-filter-button {
    display: flex !important;
    align-items: center;
    gap: 5px;
}

.aica-filter-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 300px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 16px;
    z-index: 100;
    display: none;
    margin-top: 5px;
}

.aica-filter-group {
    margin-bottom: 15px;
}

.aica-filter-group h3 {
    font-size: 14px;
    margin: 0 0 10px 0;
    padding: 0;
    font-weight: 600;
}

.aica-filter-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
}

.aica-date-range {
    display: flex;
    align-items: center;
    gap: 5px;
}

.aica-date-range input {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 13px;
}

.aica-filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 15px;
}

/* Empty state */
.aica-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
}

.aica-empty-icon {
    font-size: 48px;
    color: var(--aica-text-light);
    opacity: 0.5;
    margin-bottom: 15px;
}

.aica-empty-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}

.aica-empty-state h2 {
    font-size: 20px;
    margin: 0 0 10px 0;
    padding: 0;
}

.aica-empty-state p {
    color: var(--aica-text-light);
    margin: 0 0 20px 0;
}

/* History container */
.aica-history-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* History Cards */
.aica-history-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.aica-history-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.aica-card-header {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--aica-border);
}

.aica-card-title {
    display: flex;
    flex-direction: column;
}

.aica-card-title h3 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
}

.aica-session-id {
    font-size: 12px;
    color: var(--aica-text-light);
    margin-top: 3px;
}

.aica-card-actions {
    display: flex;
    align-items: center;
    gap: 5px;
}

.aica-card-expand,
.aica-dropdown-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    border-radius: 3px;
    color: var(--aica-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-card-expand:hover,
.aica-dropdown-toggle:hover {
    background-color: #f0f0f0;
    color: var(--aica-text);
}

/* Dropdown Menu */
.aica-dropdown {
    position: relative;
}

.aica-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 8px 0;
    z-index: 100;
    display: none;
    min-width: 200px;
    margin-top: 5px;
}

.aica-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    color: var(--aica-text);
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.aica-dropdown-item:hover {
    background-color: #f0f0f0;
    color: var(--aica-text);
}

.aica-dropdown-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.aica-dropdown-divider {
    height: 1px;
    background-color: var(--aica-border);
    margin: 6px 0;
}

.text-danger {
    color: var(--aica-error);
}

.text-danger:hover {
    background-color: var(--aica-error-light);
    color: var(--aica-error);
}

/* Card Body */
.aica-card-body {
    padding: 15px 20px;
}

.aica-meta-info {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.aica-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--aica-text-light);
}

.aica-meta-item .dashicons {
    font-size: 15px;
    width: 15px;
    height: 15px;
}

.aica-conversation-preview {
    padding: 10px 15px;
    background-color: var(--aica-bg-light);
    border-radius: 6px;
    border-left: 3px solid var(--aica-primary-light);
}

.aica-message-preview {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.aica-message-preview-label {
    font-weight: 600;
    font-size: 13px;
}

.aica-message-preview-content {
    font-size: 14px;
    color: var(--aica-text);
}

/* Card Footer */
.aica-card-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--aica-border);
    display: flex;
    justify-content: flex-end;
}

.aica-continue-button {
    display: flex !important;
    align-items: center;
    gap: 6px;
}

.aica-continue-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Expanded Card */
.aica-card-expanded {
    padding: 20px;
    border-top: 1px solid var(--aica-border);
    background-color: var(--aica-bg-light);
    max-height: 500px;
    overflow-y: auto;
}

.aica-loading-messages {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 0;
}

.aica-loading-spinner {
    border: 3px solid rgba(0, 0, 0, 0.1);
    border-top: 3px solid var(--aica-primary);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: aica-spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes aica-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.aica-messages-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.aica-message {
    display: flex;
    gap: 10px;
}

.aica-message-user {
    align-self: flex-end;
    flex-direction: row-reverse;
    max-width: 80%;
}

.aica-message-ai {
    align-self: flex-start;
    max-width: 80%;
}

.aica-message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--aica-bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.aica-message-user .aica-message-avatar {
    background-color: var(--aica-primary-light);
    color: #fff;
}

.aica-message-ai .aica-message-avatar {
    background-color: var(--aica-success-light);
    color: var(--aica-success);
}

.aica-message-content {
    background-color: #fff;
    padding: 12px 15px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    flex-grow: 1;
}

.aica-message-user .aica-message-content {
    background-color: var(--aica-primary-light);
    color: #fff;
}

.aica-message-time {
    font-size: 12px;
    color: var(--aica-text-light);
    margin-top: 5px;
}

.aica-message-user .aica-message-time {
    text-align: right;
    color: rgba(255, 255, 255, 0.8);
}

/* Pagination */
.aica-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 25px;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
}

.aica-pagination-links {
    display: flex;
    gap: 5px;
    align-items: center;
}

.aica-page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    border: 1px solid var(--aica-border);
    color: var(--aica-text);
    transition: all 0.2s ease;
}

.aica-page-link:hover {
    background-color: #f0f0f0;
}

.aica-page-link.current {
    background-color: var(--aica-primary);
    border-color: var(--aica-primary);
    color: #fff;
}

.aica-pagination-info {
    font-size: 13px;
    color: var(--aica-text-light);
}

/* Dialog */
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
    z-index: 9999;
}

.aica-dialog-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    width: 100%;
    max-width: 500px;
    overflow: hidden;
}

.aica-dialog-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-dialog-header h3 {
    margin: 0;
    padding: 0;
    font-size: 18px;
    font-weight: 600;
}

.aica-dialog-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    color: var(--aica-text-light);
}

.aica-dialog-body {
    padding: 20px;
}

.aica-dialog-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--aica-border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

@media screen and (max-width: 782px) {
    .aica-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .aica-header-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .aica-search-container {
        max-width: 100%;
    }
    
    .aica-card-title h3 {
        font-size: 15px;
    }
    
    .aica-message-user,
    .aica-message-ai {
        max-width: 90%;
    }
    
    .aica-pagination {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Obsługa pokazywania/ukrywania menu rozwijanego
    $(document).on('click', '.aica-dropdown-toggle', function(e) {
        e.stopPropagation();
        const dropdown = $(this).siblings('.aica-dropdown-menu');
        $('.aica-dropdown-menu').not(dropdown).hide();
        dropdown.toggle();
    });
    
    // Zamykanie menu rozwijanego po kliknięciu poza nim
    $(document).on('click', function() {
        $('.aica-dropdown-menu').hide();
    });
    
    // Obsługa pokazywania/ukrywania filtru
    $('.aica-filter-button').on('click', function(e) {
        e.stopPropagation();
        $('.aica-filter-dropdown').toggle();
    });
    
    // Zamykanie filtru po kliknięciu poza nim
    $(document).on('click', function() {
        $('.aica-filter-dropdown').hide();
    });
    
    // Zapobieganie zamykaniu filtru po kliknięciu wewnątrz niego
    $('.aica-filter-dropdown').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Resetowanie filtrów
    $('.aica-reset-filters').on('click', function() {
        $('input[name="sort"][value="newest"]').prop('checked', true);
        $('.aica-date-from, .aica-date-to').val('');
    });
    
    // Obsługa rozwijania karty
    $(document).on('click', '.aica-card-expand', function() {
        const card = $(this).closest('.aica-history-card');
        const expandedSection = card.find('.aica-card-expanded');
        const icon = $(this).find('.dashicons');
        
        // Zmiana ikony
        if (icon.hasClass('dashicons-arrow-down-alt2')) {
            icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        } else {
            icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        }
        
        // Jeśli sekcja jest już widoczna, ukryj ją
        if (expandedSection.is(':visible')) {
            expandedSection.slideUp(200);
            return;
        }
        
        // Pokaż sekcję i załaduj wiadomości
        expandedSection.slideDown(200);
        
        // Jeśli wiadomości zostały już załadowane, nie ładuj ich ponownie
        if (expandedSection.find('.aica-messages-container').children().length > 0) {
            return;
        }
        
        // Pokaż wskaźnik ładowania
        expandedSection.find('.aica-loading-messages').show();
        expandedSection.find('.aica-messages-container').empty();
        
        // Pobierz wiadomości z AJAX
        const sessionId = card.data('session-id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_history.nonce,
                session_id: sessionId,
                page: 1,
                per_page: 5
            },
            success: function(response) {
                if (response.success) {
                    const messagesContainer = expandedSection.find('.aica-messages-container');
                    const messages = response.data.messages;
                    
                    // Ukryj wskaźnik ładowania
                    expandedSection.find('.aica-loading-messages').hide();
                    
                    // Iteruj przez wiadomości i dodaj je do kontenera
                    $.each(messages, function(index, item) {
                        const messageClass = item.type === 'user' ? 'aica-message-user' : 'aica-message-ai';
                        const avatarIcon = item.type === 'user' ? 'dashicons-admin-users' : 'dashicons-format-chat';
                        
                        const messageElement = `
                            <div class="aica-message ${messageClass}">
                                <div class="aica-message-avatar">
                                    <span class="dashicons ${avatarIcon}"></span>
                                </div>
                                <div class="aica-message-content">
                                    <div class="aica-message-text">${escapeHTML(item.content)}</div>
                                    <div class="aica-message-time">${formatTimestamp(item.time)}</div>
                                </div>
                            </div>
                        `;
                        
                        messagesContainer.append(messageElement);
                    });
                    
                    // Jeśli nie ma wiadomości, wyświetl komunikat
                    if (messages.length === 0) {
                        messagesContainer.append(`
                            <div class="aica-empty-messages">
                                <p>${aica_history.i18n.no_messages}</p>
                            </div>
                        `);
                    }
                    
                    // Jeśli jest więcej stron, dodaj przycisk "Pokaż więcej"
                    if (response.data.pagination.current_page < response.data.pagination.total_pages) {
                        messagesContainer.append(`
                            <div class="aica-load-more">
                                <button type="button" class="button aica-load-more-button" data-session-id="${sessionId}" data-page="2">
                                    ${aica_history.i18n.load_more}
                                </button>
                            </div>
                        `);
                    }
                } else {
                    // Pokaż komunikat o błędzie
                    expandedSection.find('.aica-loading-messages').hide();
                    expandedSection.find('.aica-messages-container').html(`
                        <div class="aica-error-message">
                            <p>${response.data.message || aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                expandedSection.find('.aica-loading-messages').hide();
                expandedSection.find('.aica-messages-container').html(`
                    <div class="aica-error-message">
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
            }
        });
    });
    
    // Obsługa przycisku "Pokaż więcej"
    $(document).on('click', '.aica-load-more-button', function() {
        const button = $(this);
        const sessionId = button.data('session-id');
        const page = button.data('page');
        const messagesContainer = button.closest('.aica-messages-container');
        
        // Zmień tekst przycisku na "Ładowanie..."
        button.text(aica_history.i18n.loading);
        button.prop('disabled', true);
        
        // Pobierz więcej wiadomości z AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_chat_history',
                nonce: aica_history.nonce,
                session_id: sessionId,
                page: page,
                per_page: 5
            },
            success: function(response) {
                if (response.success) {
                    const messages = response.data.messages;
                    
                    // Usuń przycisk "Pokaż więcej"
                    button.closest('.aica-load-more').remove();
                    
                    // Iteruj przez wiadomości i dodaj je do kontenera
                    $.each(messages, function(index, item) {
                        const messageClass = item.type === 'user' ? 'aica-message-user' : 'aica-message-ai';
                        const avatarIcon = item.type === 'user' ? 'dashicons-admin-users' : 'dashicons-format-chat';
                        
                        const messageElement = `
                            <div class="aica-message ${messageClass}">
                                <div class="aica-message-avatar">
                                    <span class="dashicons ${avatarIcon}"></span>
                                </div>
                                <div class="aica-message-content">
                                    <div class="aica-message-text">${escapeHTML(item.content)}</div>
                                    <div class="aica-message-time">${formatTimestamp(item.time)}</div>
                                </div>
                            </div>
                        `;
                        
                        messagesContainer.append(messageElement);
                    });
                    
                    // Jeśli jest więcej stron, dodaj nowy przycisk "Pokaż więcej"
                    if (response.data.pagination.current_page < response.data.pagination.total_pages) {
                        messagesContainer.append(`
                            <div class="aica-load-more">
                                <button type="button" class="button aica-load-more-button" data-session-id="${sessionId}" data-page="${page + 1}">
                                    ${aica_history.i18n.load_more}
                                </button>
                            </div>
                        `);
                    }
                } else {
                    // Pokaż komunikat o błędzie
                    messagesContainer.append(`
                        <div class="aica-error-message">
                            <p>${response.data.message || aica_history.i18n.load_error}</p>
                        </div>
                    `);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                messagesContainer.append(`
                    <div class="aica-error-message">
                        <p>${aica_history.i18n.load_error}</p>
                    </div>
                `);
                
                // Przywróć tekst przycisku
                button.text(aica_history.i18n.load_more);
                button.prop('disabled', false);
            }
        });
    });
    
    // Obsługa usuwania sesji
    $(document).on('click', '.aica-delete-session', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const sessionId = $(this).data('session-id');
        const card = $(this).closest('.aica-history-card');
        
        // Pokaż dialog potwierdzający
        $('#aica-delete-dialog').data('session-id', sessionId).data('card', card).show();
    });
    
    // Zamknięcie dialogu potwierdzającego
    $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
        $('#aica-delete-dialog').hide();
    });
    
    // Potwierdzenie usunięcia sesji
    $('.aica-delete-confirm').on('click', function() {
        const dialog = $('#aica-delete-dialog');
        const sessionId = dialog.data('session-id');
        const card = dialog.data('card');
        
        // Ukryj dialog
        dialog.hide();
        
        // Pokaż wskaźnik ładowania na karcie
        card.addClass('aica-card-deleting');
        
        // Wykonaj AJAX do usunięcia sesji
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: aica_history.nonce,
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj kartę za pomocą animacji i usuń ją po zakończeniu
                    card.slideUp(300, function() {
                        card.remove();
                        
                        // Sprawdź, czy to była ostatnia sesja
                        if ($('.aica-history-card').length === 0) {
                            // Pokaż komunikat o braku sesji
                            $('.aica-history-container').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <h2>${aica_history.i18n.no_conversations}</h2>
                                    <p>${aica_history.i18n.no_conversations_desc}</p>
                                    <a href="${aica_history.chat_url}" class="button button-primary">${aica_history.i18n.new_conversation}</a>
                                </div>
                            `);
                        }
                    });
                } else {
                    // Pokaż komunikat o błędzie
                    card.removeClass('aica-card-deleting');
                    alert(response.data.message || aica_history.i18n.delete_error);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                card.removeClass('aica-card-deleting');
                alert(aica_history.i18n.delete_error);
            }
        });
    });
    
    // Obsługa wyszukiwania
    $('#aica-search-conversations').on('keyup', function(e) {
        if (e.keyCode === 13) {
            // Wykonaj wyszukiwanie po wciśnięciu Enter
            const searchTerm = $(this).val().trim();
            if (searchTerm.length < 3 && searchTerm.length > 0) {
                alert(aica_history.i18n.min_search_length);
                return;
            }
            
            // Dodanie parametru wyszukiwania do URL
            const currentUrl = new URL(window.location.href);
            if (searchTerm) {
                currentUrl.searchParams.set('s', searchTerm);
            } else {
                currentUrl.searchParams.delete('s');
            }
            
            // Przeładowanie strony
            window.location.href = currentUrl.toString();
        }
    });
    
    // Obsługa filtrowania
    $('.aica-apply-filters').on('click', function() {
        const sort = $('input[name="sort"]:checked').val();
        const dateFrom = $('.aica-date-from').val();
        const dateTo = $('.aica-date-to').val();
        
        // Dodanie parametrów filtrowania do URL
        const currentUrl = new URL(window.location.href);
        
        if (sort) {
            currentUrl.searchParams.set('sort', sort);
        }
        
        if (dateFrom) {
            currentUrl.searchParams.set('date_from', dateFrom);
        } else {
            currentUrl.searchParams.delete('date_from');
        }
        
        if (dateTo) {
            currentUrl.searchParams.set('date_to', dateTo);
        } else {
            currentUrl.searchParams.delete('date_to');
        }
        
        // Przeładowanie strony
        window.location.href = currentUrl.toString();
    });
    
    // Funkcja bezpiecznego wstawiania HTML
    function escapeHTML(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Funkcja formatowania znacznika czasu
    function formatTimestamp(timestamp) {
        if (!timestamp) return '';
        
        const date = new Date(timestamp);
        
        // Jeśli data jest nieprawidłowa, zwróć surowy znacznik czasu
        if (isNaN(date.getTime())) {
            return timestamp;
        }
        
        return date.toLocaleString();
    }
});