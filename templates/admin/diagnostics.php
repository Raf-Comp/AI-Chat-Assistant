<?php
/**
 * Szablon strony diagnostyki
 *
 * @package AI_Chat_Assistant
 */

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}
?>

<div class="wrap aica-diagnostics-container">
    <div class="aica-diagnostics-header">
        <div class="aica-header-title">
            <h1><?php _e('Diagnostyka AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
            <p class="aica-header-description"><?php _e('Narzędzie do monitorowania i rozwiązywania problemów z wtyczką', 'ai-chat-assistant'); ?></p>
        </div>
        <div class="aica-header-actions">
            <button id="refresh-all-diagnostics" class="button button-primary aica-button-with-icon">
                <span class="dashicons dashicons-update"></span> <?php _e('Odśwież wszystko', 'ai-chat-assistant'); ?>
            </button>
        </div>
    </div>
    
    <?php if (!empty($recommendations)): ?>
    <div class="aica-recommendations-panel">
        <div class="aica-recommendations-header">
            <span class="aica-recommendations-icon"><span class="dashicons dashicons-lightbulb"></span></span>
            <h2><?php _e('Zalecane działania', 'ai-chat-assistant'); ?></h2>
        </div>
        <div class="aica-recommendations-content">
            <ul>
                <?php foreach ($recommendations as $recommendation): ?>
                    <li class="aica-recommendation-item">
                        <span class="aica-recommendation-bullet"></span>
                        <?php echo esc_html($recommendation); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="aica-dashboard-grid">
        <!-- Kolumna 1 - Status API -->
        <div class="aica-dashboard-column">
            <div class="aica-card aica-api-status-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-rest-api"></span><?php _e('Status API', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <!-- Claude API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('Claude API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-claude-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($claude_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API Claude', 'ai-chat-assistant'); ?></h4>
                                    <?php if (isset($claude_api_status['details'])): ?>
                                    <div class="aica-status-details">
                                        <div class="aica-detail-item">
                                            <span class="aica-detail-label"><?php _e('Wybrany model:', 'ai-chat-assistant'); ?></span>
                                            <span class="aica-detail-value">
                                                <?php echo esc_html($claude_api_status['details']['current_model']); ?>
                                                <?php if ($claude_api_status['details']['model_available']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Dostępny', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-error"><?php _e('Niedostępny', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="aica-detail-item">
                                            <span class="aica-detail-label"><?php _e('Dostępne modele:', 'ai-chat-assistant'); ?></span>
                                            <div class="aica-models-list">
                                                <?php foreach ($claude_api_status['details']['models'] as $model): ?>
                                                    <span class="aica-model-badge"><?php echo esc_html($model); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API Claude', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($claude_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GitHub API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('GitHub API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-github-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($github_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API GitHub', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API GitHub', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($github_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- GitLab API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('GitLab API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-gitlab-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($gitlab_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API GitLab', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API GitLab', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($gitlab_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bitbucket API status -->
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('Bitbucket API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-bitbucket-api" class="button aica-button-small aica-test-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>

                        <?php if ($bitbucket_api_status['valid']): ?>
                            <div class="aica-status aica-status-card aica-status-success">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Połączono z API Bitbucket', 'ai-chat-assistant'); ?></h4>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="aica-status aica-status-card aica-status-error">
                                <div class="aica-status-icon">
                                    <span class="dashicons dashicons-warning"></span>
                                </div>
                                <div class="aica-status-content">
                                    <h4><?php _e('Problem z API Bitbucket', 'ai-chat-assistant'); ?></h4>
                                    <p><?php echo esc_html($bitbucket_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Karta informacji systemowych -->
            <div class="aica-card aica-system-info-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-info"></span> <?php _e('Informacje systemowe', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-system-info-grid">
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-php"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja PHP', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(phpversion()); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-wordpress"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja WordPress', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(get_bloginfo('version')); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-admin-plugins"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Wersja wtyczki', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo defined('AICA_VERSION') ? esc_html(AICA_VERSION) : __('Nieznana', 'ai-chat-assistant'); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-performance"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Pamięć PHP', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(ini_get('memory_limit')); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('Limit czasu wykonania', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value"><?php echo esc_html(ini_get('max_execution_time')) . ' ' . __('sekund', 'ai-chat-assistant'); ?></div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-admin-site"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('cURL', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value">
                                    <?php if (function_exists('curl_version')): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Włączone', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Wyłączone', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="aica-info-item">
                            <div class="aica-info-icon">
                                <span class="dashicons dashicons-shield"></span>
                            </div>
                            <div class="aica-info-content">
                                <h4><?php _e('OpenSSL', 'ai-chat-assistant'); ?></h4>
                                <div class="aica-info-value">
                                    <?php if (extension_loaded('openssl')): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Włączone', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Wyłączone', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kolumna 2 - Baza danych i pliki -->
        <div class="aica-dashboard-column">
            <!-- Karta statusu bazy danych -->
            <div class="aica-card aica-database-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-database"></span> <?php _e('Status bazy danych', 'ai-chat-assistant'); ?></h2>
                    <div class="aica-card-header-actions">
                        <button id="repair-database" class="button aica-button-small aica-repair-button">
                            <span class="dashicons dashicons-hammer"></span> <?php _e('Napraw', 'ai-chat-assistant'); ?>
                        </button>
                    </div>
                </div>
                <div class="aica-card-body">
                    <div class="aica-database-table">
                        <div class="aica-table-header">
                            <div class="aica-table-cell aica-table-cell-name"><?php _e('Tabela', 'ai-chat-assistant'); ?></div>
                            <div class="aica-table-cell aica-table-cell-status"><?php _e('Status', 'ai-chat-assistant'); ?></div>
                            <div class="aica-table-cell aica-table-cell-records"><?php _e('Rekordy', 'ai-chat-assistant'); ?></div>
                        </div>
                        <?php foreach ($database_status as $table => $status): ?>
                            <div class="aica-table-row <?php echo (!$status['exists']) ? 'aica-table-row-error' : ''; ?>">
                                <div class="aica-table-cell aica-table-cell-name"><?php echo esc_html($table); ?></div>
                                <div class="aica-table-cell aica-table-cell-status">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Istnieje', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Brak', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aica-table-cell aica-table-cell-records">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-info"><?php echo esc_html($status['records']); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-warning">-</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Karta uprawnień plików -->
            <div class="aica-card aica-files-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-page"></span> <?php _e('Uprawnienia plików', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-files-list">
                        <?php foreach ($files_permissions as $file => $status): ?>
                            <div class="aica-file-item <?php echo (!$status['exists'] || !$status['readable']) ? 'aica-file-problem' : ''; ?>">
                                <div class="aica-file-icon">
                                    <?php if (!$status['exists']): ?>
                                        <span class="dashicons dashicons-warning"></span>
                                    <?php elseif (!$status['readable']): ?>
                                        <span class="dashicons dashicons-lock"></span>
                                    <?php else: ?>
                                        <span class="dashicons dashicons-media-text"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aica-file-details">
                                    <div class="aica-file-name"><?php echo esc_html($file); ?></div>
                                    <div class="aica-file-path"><?php echo esc_html($status['path']); ?></div>
                                    <div class="aica-file-info">
                                        <?php if ($status['exists']): ?>
                                            <span class="aica-file-perm"><?php echo esc_html($status['permissions']); ?></span>
                                            <div class="aica-file-badges">
                                                <?php if ($status['readable']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Odczyt', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-error"><?php _e('Brak odczytu', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if ($status['writable']): ?>
                                                    <span class="aica-badge aica-badge-success"><?php _e('Zapis', 'ai-chat-assistant'); ?></span>
                                                <?php else: ?>
                                                    <span class="aica-badge aica-badge-warning"><?php _e('Brak zapisu', 'ai-chat-assistant'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="aica-badge aica-badge-error"><?php _e('Plik nie istnieje', 'ai-chat-assistant'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolumna 3 - Historia czatu -->
        <div class="aica-dashboard-column">
            <!-- Karta historii czatu -->
            <div class="aica-card aica-sessions-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-comments"></span> <?php _e('Historia czatu', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <?php
                    // Pobierz historię czatu
                    global $wpdb;
                    $user_id = get_current_user_id();
                    $sessions = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}aica_sessions WHERE user_id = %d ORDER BY created_at DESC LIMIT 15",
                            $user_id
                        ),
                        ARRAY_A
                    );
                    
                    if (empty($sessions)): ?>
                        <div class="aica-empty-state">
                            <div class="aica-empty-icon">
                                <span class="dashicons dashicons-format-chat"></span>
                            </div>
                            <h3><?php _e('Brak historii czatu', 'ai-chat-assistant'); ?></h3>
                            <p><?php _e('Nie przeprowadziłeś jeszcze żadnych rozmów z Claude. Rozpocznij rozmowę, aby zobaczyć ją tutaj.', 'ai-chat-assistant'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant'); ?>" class="button button-primary">
                                <span class="dashicons dashicons-plus"></span>
                                <?php _e('Rozpocznij rozmowę', 'ai-chat-assistant'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="aica-sessions-list">
                            <?php foreach ($sessions as $session): ?>
                                <div class="aica-session-item">
                                    <div class="aica-session-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <div class="aica-session-details">
                                        <div class="aica-session-title"><?php echo esc_html($session['title']); ?></div>
                                        <div class="aica-session-meta">
                                            <span class="aica-session-id"><?php echo esc_html(substr($session['session_id'], 0, 8) . '...'); ?></span>
                                            <span class="aica-session-date"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session['created_at']))); ?></span>
                                        </div>
                                    </div>
                                    <div class="aica-session-actions">
                                        <button class="aica-session-action js-delete-session" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                        <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant&session_id=' . esc_attr($session['session_id'])); ?>" class="aica-session-action">
                                            <span class="dashicons dashicons-arrow-right-alt"></span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="aica-card-footer">
                            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-history'); ?>" class="button aica-view-all-button">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php _e('Zobacz wszystkie rozmowy', 'ai-chat-assistant'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast notifications -->
    <div id="aica-notifications-container" class="aica-notifications-container"></div>
    
    <!-- Confirmation dialog -->
    <div id="aica-confirm-dialog" class="aica-dialog" style="display: none;">
        <div class="aica-dialog-overlay"></div>
        <div class="aica-dialog-content">
            <div class="aica-dialog-header">
                <h3 id="aica-dialog-title"><?php _e('Potwierdź operację', 'ai-chat-assistant'); ?></h3>
                <button type="button" class="aica-dialog-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aica-dialog-body">
                <p id="aica-dialog-message"><?php _e('Czy na pewno chcesz wykonać tę operację?', 'ai-chat-assistant'); ?></p>
            </div>
            <div class="aica-dialog-footer">
                <button type="button" class="button aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
                <button type="button" class="button button-primary aica-dialog-confirm"><?php _e('Potwierdź', 'ai-chat-assistant'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --aica-primary: #2271b1;
    --aica-primary-light: #72aee6;
    --aica-primary-dark: #135e96;
    --aica-primary-hover: #1a5b8e;
    --aica-success: #00a32a;
    --aica-success-light: #edfaef;
    --aica-success-hover: #008a23;
    --aica-warning: #dba617;
    --aica-warning-light: #fcf9e8;
    --aica-warning-hover: #c79200;
    --aica-error: #d63638;
    --aica-error-light: #fcf0f1;
    --aica-error-hover: #b32d2e;
    --aica-text: #1e1e1e;
    --aica-text-light: #757575;
    --aica-text-lighter: #a7aaad;
    --aica-border: #e0e0e0;
    --aica-bg-light: #f8f9fa;
    --aica-bg-lighter: #ffffff;
    --aica-card-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    --aica-radius-sm: 4px;
    --aica-radius-md: 8px;
    --aica-radius-lg: 12px;
    --aica-spacing-xs: 4px;
    --aica-spacing-sm: 8px;
    --aica-spacing-md: 16px;
    --aica-spacing-lg: 24px;
    --aica-spacing-xl: 32px;
    --aica-transition: all 0.2s ease-in-out;
}

/* Base Styles */
.aica-diagnostics-container {
    max-width: 1400px;
    margin: 20px auto;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    color: var(--aica-text);
}

/* Header Styles */
.aica-diagnostics-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--aica-spacing-lg);
}

.aica-header-title h1 {
    margin: 0 0 var(--aica-spacing-xs) 0;
    padding: 0;
    font-size: 28px;
    font-weight: 600;
    color: var(--aica-text);
    line-height: 1.3;
}

.aica-header-description {
    margin: 0;
    color: var(--aica-text-light);
    font-size: 14px;
}

.aica-header-actions {
    display: flex;
    gap: var(--aica-spacing-sm);
}

.aica-button-with-icon {
    display: flex !important;
    align-items: center;
    gap: var(--aica-spacing-xs);
    padding: 6px 12px !important;
    height: auto !important;
    min-height: 36px;
    border-radius: var(--aica-radius-sm);
    transition: var(--aica-transition);
}

.aica-button-with-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.aica-button-small {
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: var(--aica-spacing-xs);
    padding: 4px 8px !important;
    height: auto !important;
    font-size: 12px !important;
    border-radius: var(--aica-radius-sm);
    transition: var(--aica-transition);
}

.aica-button-small .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.aica-test-button {
    background-color: var(--aica-bg-light) !important;
    border: 1px solid var(--aica-border) !important;
    color: var(--aica-text) !important;
}

.aica-test-button:hover {
    background-color: #f0f0f0 !important;
}

.aica-repair-button {
    background-color: var(--aica-warning-light) !important;
    border: 1px solid var(--aica-warning) !important;
    color: var(--aica-warning) !important;
}

.aica-repair-button:hover {
    background-color: var(--aica-warning) !important;
    color: white !important;
}

/* Recommendations Panel */
.aica-recommendations-panel {
    background-color: var(--aica-bg-lighter);
    border-radius: var(--aica-radius-md);
    box-shadow: var(--aica-card-shadow);
    padding: 0;
    margin-bottom: var(--aica-spacing-lg);
    overflow: hidden;
    border-left: 4px solid var(--aica-warning);
}

.aica-recommendations-header {
    background-color: var(--aica-warning-light);
    padding: var(--aica-spacing-md);
    display: flex;
    align-items: center;
    gap: var(--aica-spacing-sm);
}

.aica-recommendations-header h2 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--aica-text);
}

.aica-recommendations-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--aica-warning);
    color: white;
}

.aica-recommendations-icon .dashicons {
    font-size: 18px;
}

.aica-recommendations-content {
    padding: var(--aica-spacing-md);
}

.aica-recommendations-content ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.aica-recommendation-item {
    position: relative;
    padding-left: 20px;
    margin-bottom: var(--aica-spacing-sm);
    font-size: 14px;
    line-height: 1.5;
}

.aica-recommendation-item:last-child {
    margin-bottom: 0;
}

.aica-recommendation-bullet {
    position: absolute;
    left: 0;
    top: 8px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--aica-warning);
}

/* Dashboard Grid */
.aica-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--aica-spacing-lg);
}

@media (max-width: 1200px) {
    .aica-dashboard-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 782px) {
    .aica-dashboard-grid {
        grid-template-columns: 1fr;
    }
}

/* Cards */
.aica-card {
    background-color: var(--aica-bg-lighter);
    border-radius: var(--aica-radius-md);
    box-shadow: var(--aica-card-shadow);
    margin-bottom: var(--aica-spacing-lg);
    overflow: hidden;
    transition: var(--aica-transition);
    border: 1px solid var(--aica-border);
}

.aica-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.aica-card-header {
    background-color: var(--aica-bg-light);
    padding: var(--aica-spacing-md);
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-card-header h2 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--aica-text);
    display: flex;
    align-items: center;
    gap: var(--aica-spacing-xs);
}

.aica-card-header .dashicons {
    color: var(--aica-primary);
}

.aica-card-body {
    padding: var(--aica-spacing-md);
}

.aica-card-footer {
    padding: var(--aica-spacing-md);
    border-top: 1px solid var(--aica-border);
    display: flex;
    justify-content: center;
}

.aica-view-all-button {
    display: flex !important;
    align-items: center;
    gap: var(--aica-spacing-xs);
    width: 100%;
    justify-content: center;
}

/* Status Cards */
.aica-status-section {
    margin-bottom: var(--aica-spacing-md);
    padding-bottom: var(--aica-spacing-md);
    border-bottom: 1px solid var(--aica-border);
}

.aica-status-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.aica-status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--aica-spacing-sm);
}

.aica-status-header h3 {
    margin: 0;
    padding: 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--aica-text);
}

.aica-status-card {
    display: flex;
    align-items: flex-start;
    padding: var(--aica-spacing-md);
    border-radius: var(--aica-radius-sm);
    transition: var(--aica-transition);
}

.aica-status-success {
    background-color: var(--aica-success-light);
    border-left: 3px solid var(--aica-success);
}

.aica-status-error {
    background-color: var(--aica-error-light);
    border-left: 3px solid var(--aica-error);
}

.aica-status-warning {
    background-color: var(--aica-warning-light);
    border-left: 3px solid var(--aica-warning);
}

.aica-status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    margin-right: var(--aica-spacing-sm);
    flex-shrink: 0;
}

.aica-status-success .aica-status-icon {
    color: var(--aica-success);
}

.aica-status-error .aica-status-icon {
    color: var(--aica-error);
}

.aica-status-warning .aica-status-icon {
    color: var(--aica-warning);
}

.aica-status-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.aica-status-content {
    flex: 1;
}

.aica-status-content h4 {
    margin: 0 0 var(--aica-spacing-xs) 0;
    padding: 0;
    font-size: 14px;
    font-weight: 600;
}

.aica-status-content p {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--aica-text-light);
}

.aica-status-details {
    margin-top: var(--aica-spacing-sm);
    padding-top: var(--aica-spacing-sm);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.aica-detail-item {
    display: flex;
    flex-direction: column;
    margin-bottom: var(--aica-spacing-xs);
}

.aica-detail-item:last-child {
    margin-bottom: 0;
}

.aica-detail-label {
    font-size: 12px;
    color: var(--aica-text-light);
    margin-bottom: 2px;
}

.aica-detail-value {
    font-size: 13px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--aica-spacing-xs);
}

.aica-models-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--aica-spacing-xs);
    margin-top: var(--aica-spacing-xs);
}

.aica-model-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: var(--aica-radius-sm);
    font-size: 12px;
    background-color: var(--aica-bg-light);
    border: 1px solid var(--aica-border);
}

/* Badges */
.aica-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    border-radius: var(--aica-radius-sm);
    font-size: 12px;
    font-weight: 500;
    line-height: 1.5;
}

.aica-badge-success {
    background-color: var(--aica-success-light);
    color: var(--aica-success);
}

.aica-badge-error {
    background-color: var(--aica-error-light);
    color: var(--aica-error);
}

.aica-badge-warning {
    background-color: var(--aica-warning-light);
    color: var(--aica-warning);
}

.aica-badge-info {
    background-color: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

/* System Info */
.aica-system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(calc(50% - var(--aica-spacing-sm)), 1fr));
    gap: var(--aica-spacing-sm);
}

.aica-info-item {
    display: flex;
    align-items: flex-start;
    padding: var(--aica-spacing-sm);
    border-radius: var(--aica-radius-sm);
    background-color: var(--aica-bg-light);
    transition: var(--aica-transition);
}

.aica-info-item:hover {
    background-color: #f0f0f0;
}

.aica-info-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin-right: var(--aica-spacing-sm);
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: var(--aica-radius-sm);
    color: var(--aica-primary);
}

.aica-info-icon .dashicons {
    font-size: 18px;
}

.aica-info-content {
    flex: 1;
}

.aica-info-content h4 {
    margin: 0 0 4px 0;
    padding: 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--aica-text-light);
}

.aica-info-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--aica-text);
}

/* Database Table */
.aica-database-table {
    border: 1px solid var(--aica-border);
    border-radius: var(--aica-radius-sm);
    overflow: hidden;
}

.aica-table-header {
    display: flex;
    background-color: var(--aica-bg-light);
    border-bottom: 1px solid var(--aica-border);
    font-weight: 600;
    font-size: 13px;
}

.aica-table-row {
    display: flex;
    border-bottom: 1px solid var(--aica-border);
    transition: var(--aica-transition);
}

.aica-table-row:last-child {
    border-bottom: none;
}

.aica-table-row:hover {
    background-color: rgba(0, 0, 0, 0.01);
}

.aica-table-row-error {
    background-color: var(--aica-error-light);
}

.aica-table-row-error:hover {
    background-color: rgba(214, 54, 56, 0.15);
}

.aica-table-cell {
    padding: var(--aica-spacing-sm);
    font-size: 13px;
    display: flex;
    align-items: center;
}

.aica-table-cell-name {
    flex: 1;
}

.aica-table-cell-status {
    width: 100px;
    justify-content: center;
}

.aica-table-cell-records {
    width: 80px;
    justify-content: center;
}

/* Files List */
.aica-files-list {
    display: flex;
    flex-direction: column;
    gap: var(--aica-spacing-sm);
    max-height: 400px;
    overflow-y: auto;
    padding-right: var(--aica-spacing-xs);
}

.aica-file-item {
    display: flex;
    align-items: flex-start;
    padding: var(--aica-spacing-sm);
    border-radius: var(--aica-radius-sm);
    background-color: var(--aica-bg-light);
    transition: var(--aica-transition);
}

.aica-file-item:hover {
    background-color: #f0f0f0;
}

.aica-file-problem {
    background-color: var(--aica-error-light);
}

.aica-file-problem:hover {
    background-color: rgba(214, 54, 56, 0.15);
}

.aica-file-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin-right: var(--aica-spacing-sm);
    border-radius: var(--aica-radius-sm);
    background-color: white;
    color: var(--aica-primary);
    flex-shrink: 0;
}

.aica-file-problem .aica-file-icon {
    color: var(--aica-error);
}

.aica-file-details {
    flex: 1;
}

.aica-file-name {
    font-weight: 600;
    margin-bottom: 2px;
    font-size: 13px;
}

.aica-file-path {
    font-size: 12px;
    color: var(--aica-text-light);
    margin-bottom: var(--aica-spacing-xs);
    word-break: break-all;
}

.aica-file-info {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--aica-spacing-xs);
}

.aica-file-perm {
    font-family: monospace;
    font-size: 12px;
    padding: 2px 5px;
    background-color: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.aica-file-badges {
    display: flex;
    gap: var(--aica-spacing-xs);
}

/* Sessions List */
.aica-sessions-list {
    display: flex;
    flex-direction: column;
    gap: var(--aica-spacing-sm);
    max-height: 400px;
    overflow-y: auto;
}

.aica-session-item {
    display: flex;
    align-items: center;
    padding: var(--aica-spacing-sm);
    border-radius: var(--aica-radius-sm);
    background-color: var(--aica-bg-light);
    transition: var(--aica-transition);
}

.aica-session-item:hover {
    background-color: #f0f0f0;
}

.aica-session-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin-right: var(--aica-spacing-sm);
    border-radius: var(--aica-radius-sm);
    background-color: var(--aica-primary-light);
    color: white;
}

.aica-session-details {
    flex: 1;
    min-width: 0; /* Prevent flexbox from growing beyond container */
}

.aica-session-title {
    font-weight: 600;
    margin-bottom: 2px;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.aica-session-meta {
    display: flex;
    align-items: center;
    gap: var(--aica-spacing-sm);
    font-size: 12px;
    color: var(--aica-text-light);
}

.aica-session-actions {
    display: flex;
    gap: var(--aica-spacing-xs);
}

.aica-session-action {
    display: flex !important;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: var(--aica-radius-sm);
    background-color: transparent;
    border: none;
    color: var(--aica-text-light);
    cursor: pointer;
    transition: var(--aica-transition);
}

.aica-session-action:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--aica-text);
}

.aica-session-action .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.js-delete-session:hover {
    color: var(--aica-error);
}

/* Empty State */
.aica-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--aica-spacing-xl) var(--aica-spacing-md);
    text-align: center;
}

.aica-empty-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--aica-bg-light);
    color: var(--aica-text-lighter);
    margin-bottom: var(--aica-spacing-md);
}

.aica-empty-icon .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
}

.aica-empty-state h3 {
    margin: 0 0 var(--aica-spacing-xs) 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
}

.aica-empty-state p {
    margin: 0 0 var(--aica-spacing-md) 0;
    color: var(--aica-text-light);
    font-size: 14px;
    max-width: 300px;
}

/* Notifications */
.aica-notifications-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: var(--aica-spacing-sm);
    max-width: 350px;
}

.aica-notification {
    display: flex;
    align-items: flex-start;
    padding: var(--aica-spacing-md);
    border-radius: var(--aica-radius-sm);
    background-color: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideIn 0.3s forwards;
    border-left: 4px solid transparent;
}

.aica-notification.success {
    border-left-color: var(--aica-success);
}

.aica-notification.error {
    border-left-color: var(--aica-error);
}

.aica-notification.warning {
    border-left-color: var(--aica-warning);
}

.aica-notification.info {
    border-left-color: var(--aica-primary);
}

.aica-notification-icon {
    margin-right: var(--aica-spacing-sm);
    flex-shrink: 0;
}

.aica-notification.success .aica-notification-icon {
    color: var(--aica-success);
}

.aica-notification.error .aica-notification-icon {
    color: var(--aica-error);
}

.aica-notification.warning .aica-notification-icon {
    color: var(--aica-warning);
}

.aica-notification.info .aica-notification-icon {
    color: var(--aica-primary);
}

.aica-notification-content {
    flex: 1;
}

.aica-notification-title {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
}

.aica-notification-message {
    font-size: 13px;
    color: var(--aica-text-light);
    margin: 0;
}

.aica-notification-close {
    margin-left: var(--aica-spacing-sm);
    border: none;
    background: none;
    cursor: pointer;
    color: var(--aica-text-lighter);
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--aica-transition);
}

.aica-notification-close:hover {
    color: var(--aica-text);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* Dialog */
.aica-dialog {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.aica-dialog-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.aica-dialog-content {
    background-color: white;
    border-radius: var(--aica-radius-md);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 1;
    animation: fadeIn 0.2s forwards;
}

.aica-dialog-header {
    padding: var(--aica-spacing-md);
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-dialog-header h3 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
}

.aica-dialog-close {
    border: none;
    background: none;
    cursor: pointer;
    color: var(--aica-text-light);
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--aica-transition);
}

.aica-dialog-close:hover {
    color: var(--aica-text);
}

.aica-dialog-body {
    padding: var(--aica-spacing-md);
}

.aica-dialog-body p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
}

.aica-dialog-footer {
    padding: var(--aica-spacing-md);
    border-top: 1px solid var(--aica-border);
    display: flex;
    justify-content: flex-end;
    gap: var(--aica-spacing-sm);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.aica-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-top: 2px solid var(--aica-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Custom Scrollbar */
.aica-files-list::-webkit-scrollbar,
.aica-sessions-list::-webkit-scrollbar {
    width: 8px;
}

.aica-files-list::-webkit-scrollbar-track,
.aica-sessions-list::-webkit-scrollbar-track {
    background: var(--aica-bg-light);
    border-radius: 4px;
}

.aica-files-list::-webkit-scrollbar-thumb,
.aica-sessions-list::-webkit-scrollbar-thumb {
    background-color: var(--aica-border);
    border-radius: 4px;
}

.aica-files-list::-webkit-scrollbar-thumb:hover,
.aica-sessions-list::-webkit-scrollbar-thumb:hover {
    background-color: var(--aica-text-lighter);
}

/* Responsive Adjustments */
@media screen and (max-width: 600px) {
    .aica-diagnostics-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--aica-spacing-md);
    }
    
    .aica-system-info-grid {
        grid-template-columns: 1fr;
    }
    
    .aica-header-actions {
        width: 100%;
    }
    
    .aica-button-with-icon {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Store configuration for dialog actions
    const dialogConfig = {
        action: '',
        params: {},
        callback: null
    };
    
    // Show notification function
    function showNotification(type, message, title = '') {
        const notificationsContainer = $('#aica-notifications-container');
        const icons = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-warning',
            warning: 'dashicons-info',
            info: 'dashicons-info'
        };
        
        // Create title if not provided
        if (!title) {
            switch(type) {
                case 'success': title = 'Sukces'; break;
                case 'error': title = 'Błąd'; break;
                case 'warning': title = 'Ostrzeżenie'; break;
                case 'info': title = 'Informacja'; break;
            }
        }
        
        // Create notification element
        const notification = $(`
            <div class="aica-notification ${type}">
                <div class="aica-notification-icon">
                    <span class="dashicons ${icons[type]}"></span>
                </div>
                <div class="aica-notification-content">
                    <div class="aica-notification-title">${title}</div>
                    <p class="aica-notification-message">${message}</p>
                </div>
                <button class="aica-notification-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `);
        
        // Add notification to container
        notificationsContainer.append(notification);
        
        // Setup close button
        notification.find('.aica-notification-close').on('click', function() {
            closeNotification(notification);
        });
        
        // Auto close after 5 seconds
        setTimeout(function() {
            closeNotification(notification);
        }, 5000);
    }
    
    // Close notification function
    function closeNotification(notification) {
        notification.css('animation', 'slideOut 0.3s forwards');
        setTimeout(function() {
            notification.remove();
        }, 300);
    }
    
    // Show confirmation dialog
    function showConfirmDialog(title, message, action, params = {}, callback = null) {
        const dialog = $('#aica-confirm-dialog');
        
        // Set dialog content
        $('#aica-dialog-title').text(title);
        $('#aica-dialog-message').text(message);
        
        // Store action details
        dialogConfig.action = action;
        dialogConfig.params = params;
        dialogConfig.callback = callback;
        
        // Show dialog
        dialog.fadeIn(200);
    }
    
    // Hide confirmation dialog
    function hideConfirmDialog() {
        const dialog = $('#aica-confirm-dialog');
        dialog.fadeOut(200);
    }
    
    // Dialog close button
    $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
        hideConfirmDialog();
    });
    
    // Dialog overlay click
    $(document).on('click', '.aica-dialog-overlay', function() {
        hideConfirmDialog();
    });
    
    // Dialog confirm button
    $('.aica-dialog-confirm').on('click', function() {
        // Execute the appropriate action based on dialogConfig
        switch(dialogConfig.action) {
            case 'delete_session':
                deleteSession(dialogConfig.params.sessionId);
                break;
            case 'repair_database':
                repairDatabase();
                break;
            default:
                // Execute callback if available
                if (typeof dialogConfig.callback === 'function') {
                    dialogConfig.callback();
                }
        }
        
        // Hide dialog after action
        hideConfirmDialog();
    });
    
    // API Tests
    
    // Claude API Test
    $('#test-claude-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request - WAŻNE: używamy klucza API z ustawień, a nie z formularza
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                api_type: 'claude',
                api_key: '<?php echo esc_js(aica_get_option('claude_api_key', '')); ?>'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API Claude działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API Claude.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // GitHub API Test
    $('#test-github-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request - WAŻNE: używamy tokenu z ustawień, a nie z formularza
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                api_type: 'github',
                api_key: '<?php echo esc_js(aica_get_option('github_token', '')); ?>'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API GitHub działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API GitHub.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // GitLab API Test
    $('#test-gitlab-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request - WAŻNE: używamy tokenu z ustawień, a nie z formularza
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                api_type: 'gitlab',
                api_key: '<?php echo esc_js(aica_get_option('gitlab_token', '')); ?>'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API GitLab działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API GitLab.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // Bitbucket API Test
    $('#test-bitbucket-api').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request - WAŻNE: używamy danych z ustawień, a nie z formularza
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                api_type: 'bitbucket',
                username: '<?php echo esc_js(aica_get_option('bitbucket_username', '')); ?>',
                password: '<?php echo esc_js(aica_get_option('bitbucket_app_password', '')); ?>'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Połączenie z API Bitbucket działa poprawnie.');
                    // Refresh section after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas testowania połączenia z API Bitbucket.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    });
    
    // Repair Database
    $('#repair-database').on('click', function() {
        // Pokaż okno dialogowe potwierdzenia
        showConfirmDialog(
            'Naprawa bazy danych', 
            'Czy na pewno chcesz naprawić tabele bazy danych? Ta operacja spróbuje utworzyć brakujące tabele.', 
            'repair_database'
        );
    });
    
    function repairDatabase() {
        const button = $('#repair-database');
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span>').prop('disabled', true);
        
        // Send AJAX request - WAŻNE: używamy poprawnej akcji AJAX 'repair_database' zamiast 'aica_install_tables'
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_repair_database',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>'
            },
            success: function(response) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                
                if (response.success) {
                    showNotification('success', response.data.message || 'Pomyślnie naprawiono tabele bazy danych.');
                    // Refresh page after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas naprawy bazy danych.');
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                button.html(originalHtml).prop('disabled', false);
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
                
                // Spróbujmy automatycznie odświeżyć stronę, być może tabele zostały naprawione pomimo błędu
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        });
    }
    
    // Delete session
    $('.js-delete-session').on('click', function() {
        const sessionId = $(this).data('session-id');
        
        showConfirmDialog(
            'Usuń sesję', 
            'Czy na pewno chcesz usunąć tę sesję czatu? Tej operacji nie można cofnąć.', 
            'delete_session',
            { sessionId: sessionId }
        );
    });
    
    function deleteSession(sessionId) {
        const sessionItem = $('.js-delete-session[data-session-id="' + sessionId + '"]').closest('.aica-session-item');
        
        // Add loading state
        sessionItem.css('opacity', '0.5');
        
        // Send AJAX request - WAŻNE: używamy poprawnego nonce
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_session',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                nonce_key: 'aica_diagnostics_nonce',
                session_id: sessionId
            },
            success: function(response) {
                if (response.success) {
                    // Remove session item with animation
                    sessionItem.slideUp(300, function() {
                        $(this).remove();
                        
                        // Check if there are no more sessions
                        if ($('.aica-session-item').length === 0) {
                            // Show empty state
                            $('.aica-sessions-list').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-format-chat"></span>
                                    </div>
                                    <h3>Brak historii czatu</h3>
                                    <p>Nie przeprowadziłeś jeszcze żadnych rozmów z Claude. Rozpocznij rozmowę, aby zobaczyć ją tutaj.</p>
                                    <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant'); ?>" class="button button-primary">
                                        <span class="dashicons dashicons-plus"></span>
                                        Rozpocznij rozmowę
                                    </a>
                                </div>
                            `);
                        }
                    });
                    
                    showNotification('success', 'Sesja została pomyślnie usunięta.');
                } else {
                    // Restore session item
                    sessionItem.css('opacity', '1');
                    showNotification('error', response.data.message || 'Wystąpił błąd podczas usuwania sesji.');
                }
            },
            error: function(xhr, status, error) {
                // Restore session item
                sessionItem.css('opacity', '1');
                showNotification('error', 'Wystąpił błąd podczas wykonywania żądania: ' + error);
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
    
    // Refresh All Diagnostics
    $('#refresh-all-diagnostics').on('click', function() {
        const button = $(this);
        const originalHtml = button.html();
        
        // Show loading state
        button.html('<span class="aica-spinner"></span> Odświeżanie...').prop('disabled', true);
        
        // Wait a bit to show animation, then refresh page
        setTimeout(function() {
            location.reload();
        }, 800);
    });
    
    // Initial notification to help users
    setTimeout(function() {
        showNotification(
            'info', 
            'Możesz przetestować połączenia API za pomocą przycisków "Test" przy każdym z nich.', 
            'Witaj w diagnostyce'
        );
    }, 1000);
});
</script>