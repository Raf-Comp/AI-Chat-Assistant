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

<div class="wrap aica-admin-container">
    <div class="aica-header">
        <h1><?php _e('Diagnostyka AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
        <div class="aica-header-actions">
            <button id="refresh-all-diagnostics" class="button button-primary">
                <span class="dashicons dashicons-update"></span> <?php _e('Odśwież wszystko', 'ai-chat-assistant'); ?>
            </button>
        </div>
    </div>
    
    <?php if (!empty($recommendations)): ?>
    <div class="aica-recommendations-panel">
        <h2><span class="dashicons dashicons-lightbulb"></span> <?php _e('Zalecane działania', 'ai-chat-assistant'); ?></h2>
        <ul>
            <?php foreach ($recommendations as $recommendation): ?>
                <li><?php echo esc_html($recommendation); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="aica-dashboard">
        <div class="aica-dashboard-column">
            <!-- Karta statusu API -->
            <div class="aica-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-performance"></span> <?php _e('Status API', 'ai-chat-assistant'); ?></h2>
                    <div class="aica-card-header-actions">
                        <button id="test-all-apis" class="button button-small">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                </div>
                <div class="aica-card-body">
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('Claude API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-claude-api" class="button button-small aica-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>
                        
                        <?php if ($claude_api_status['valid']): ?>
                            <div class="aica-status aica-status-success">
                                <span class="aica-status-icon dashicons dashicons-yes-alt"></span>
                                <div>
                                    <p><?php _e('Połączono z API Claude', 'ai-chat-assistant'); ?></p>
                                </div>
                            </div>
                            
                            <table class="aica-status-details">
                                <tr>
                                    <th><?php _e('Status konta:', 'ai-chat-assistant'); ?></th>
                                    <td><?php echo isset($claude_api_status['details']['status']) ? esc_html($claude_api_status['details']['status']) : __('Aktywne', 'ai-chat-assistant'); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Aktualny model:', 'ai-chat-assistant'); ?></th>
                                    <td>
                                        <?php 
                                        if (isset($claude_api_status['details']['current_model'])) {
                                            echo esc_html($claude_api_status['details']['current_model']);
                                            if (isset($claude_api_status['details']['model_available'])) {
                                                if ($claude_api_status['details']['model_available']) {
                                                    echo ' <span class="aica-badge aica-badge-success">' . __('dostępny', 'ai-chat-assistant') . '</span>';
                                                } else {
                                                    echo ' <span class="aica-badge aica-badge-error">' . __('niedostępny', 'ai-chat-assistant') . '</span>';
                                                }
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Dostępne modele:', 'ai-chat-assistant'); ?></th>
                                    <td>
                                        <?php 
                                        if (isset($claude_api_status['details']['models']) && !empty($claude_api_status['details']['models'])) {
                                            echo '<div class="aica-models-list">';
                                            foreach ($claude_api_status['details']['models'] as $model) {
                                                echo '<span class="aica-model-badge">' . esc_html($model) . '</span>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <div class="aica-status aica-status-error">
                                <span class="aica-status-icon dashicons dashicons-warning"></span>
                                <div>
                                    <p><?php echo esc_html($claude_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aica-status-section">
                        <div class="aica-status-header">
                            <h3><?php _e('GitHub API', 'ai-chat-assistant'); ?></h3>
                            <button id="test-github-api" class="button button-small aica-button">
                                <span class="dashicons dashicons-update"></span> <?php _e('Test', 'ai-chat-assistant'); ?>
                            </button>
                        </div>
                        
                        <?php if ($github_api_status['valid']): ?>
                            <div class="aica-status aica-status-success">
                                <span class="aica-status-icon dashicons dashicons-yes-alt"></span>
                                <div>
                                    <p><?php _e('Połączono z API GitHub', 'ai-chat-assistant'); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($github_api_status['details'])): ?>
                                <table class="aica-status-details">
                                    <tr>
                                        <th><?php _e('Limit zapytań:', 'ai-chat-assistant'); ?></th>
                                        <td><?php echo esc_html($github_api_status['details']['limit']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Pozostało zapytań:', 'ai-chat-assistant'); ?></th>
                                        <td>
                                            <?php 
                                            $remaining = $github_api_status['details']['remaining'];
                                            $limit = $github_api_status['details']['limit'];
                                            $percent = $limit > 0 ? ($remaining / $limit) * 100 : 0;
                                            $bar_class = $percent > 50 ? 'success' : ($percent > 20 ? 'warning' : 'error');
                                            ?>
                                            <div class="aica-progress-container">
                                                <div class="aica-progress-text"><?php echo esc_html($remaining); ?></div>
                                                <div class="aica-progress-bar">
                                                    <div class="aica-progress-value aica-progress-<?php echo $bar_class; ?>" style="width: <?php echo $percent; ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Reset limitu:', 'ai-chat-assistant'); ?></th>
                                        <td><?php echo esc_html($github_api_status['details']['reset']); ?></td>
                                    </tr>
                                </table>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="aica-status aica-status-error">
                                <span class="aica-status-icon dashicons dashicons-warning"></span>
                                <div>
                                    <p><?php echo esc_html($github_api_status['message']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Karta informacji systemowych -->
            <div class="aica-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-info"></span> <?php _e('Informacje systemowe', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-system-info-grid">
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('Wersja PHP', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value"><?php echo esc_html(phpversion()); ?></div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('Wersja WordPress', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value"><?php echo esc_html(get_bloginfo('version')); ?></div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('Wersja wtyczki', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value"><?php echo defined('AICA_VERSION') ? esc_html(AICA_VERSION) : __('Nieznana', 'ai-chat-assistant'); ?></div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('Pamięć PHP', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value"><?php echo esc_html(ini_get('memory_limit')); ?></div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('Limit czasu wykonania', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value"><?php echo esc_html(ini_get('max_execution_time')) . ' ' . __('sekund', 'ai-chat-assistant'); ?></div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('cURL', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value">
                                <?php if (function_exists('curl_version')): ?>
                                    <span class="aica-badge aica-badge-success"><?php _e('Włączone', 'ai-chat-assistant'); ?></span>
                                <?php else: ?>
                                    <span class="aica-badge aica-badge-error"><?php _e('Wyłączone', 'ai-chat-assistant'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="aica-system-info-item">
                            <div class="aica-system-info-label"><?php _e('OpenSSL', 'ai-chat-assistant'); ?></div>
                            <div class="aica-system-info-value">
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
        
        <div class="aica-dashboard-column">
            <!-- Karta statusu bazy danych -->
            <div class="aica-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-database"></span> <?php _e('Status bazy danych', 'ai-chat-assistant'); ?></h2>
                    <div class="aica-card-header-actions">
                        <button id="repair-database" class="button button-small">
                            <span class="dashicons dashicons-hammer"></span> <?php _e('Napraw', 'ai-chat-assistant'); ?>
                        </button>
                    </div>
                </div>
                <div class="aica-card-body">
                    <div class="aica-database-grid">
                        <?php foreach ($database_status as $table => $status): ?>
                            <div class="aica-database-item">
                                <div class="aica-database-name">
                                    <?php echo esc_html($table); ?>
                                </div>
                                <div class="aica-database-status">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-success"><?php _e('Istnieje', 'ai-chat-assistant'); ?></span>
                                    <?php else: ?>
                                        <span class="aica-badge aica-badge-error"><?php _e('Brak', 'ai-chat-assistant'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="aica-database-records">
                                    <?php if ($status['exists']): ?>
                                        <span class="aica-badge aica-badge-info"><?php echo esc_html($status['records']); ?> <?php _e('rekordów', 'ai-chat-assistant'); ?></span>
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
            <div class="aica-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-plugins"></span> <?php _e('Uprawnienia plików', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <div class="aica-files-list">
                        <?php foreach ($files_permissions as $file => $status): ?>
                            <div class="aica-file-item <?php echo (!$status['exists'] || !$status['readable']) ? 'aica-file-problem' : ''; ?>">
                                <div class="aica-file-icon">
                                    <span class="dashicons <?php echo $status['exists'] ? 'dashicons-media-text' : 'dashicons-warning'; ?>"></span>
                                </div>
                                <div class="aica-file-details">
                                    <div class="aica-file-name"><?php echo esc_html($file); ?></div>
                                    <div class="aica-file-info">
                                        <?php if ($status['exists']): ?>
                                            <span class="aica-file-perm"><?php echo esc_html($status['permissions']); ?></span>
                                            <span class="aica-file-badges">
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
                                            </span>
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

        <!-- Trzecia kolumna -->
        <div class="aica-dashboard-column">
            <!-- Karta historii czatu -->
            <div class="aica-card">
                <div class="aica-card-header">
                    <h2><span class="dashicons dashicons-admin-comments"></span> <?php _e('Historia czatu', 'ai-chat-assistant'); ?></h2>
                </div>
                <div class="aica-card-body">
                    <?php
                    // Pobierz historię czatu
                    global $wpdb;
                    $user_id = get_current_user_id();
                    $table = $wpdb->prefix . 'aica_messages';
                    
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
                            <p><?php _e('Brak historii czatu.', 'ai-chat-assistant'); ?></p>
                            <p class="aica-empty-desc"><?php _e('Nie przeprowadziłeś jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="aica-chat-sessions">
                            <?php foreach ($sessions as $session): ?>
                                <div class="aica-chat-session">
                                    <div class="aica-session-info">
                                        <div class="aica-session-id">
                                            <span class="dashicons dashicons-admin-comments"></span>
                                            <?php echo esc_html(substr($session['session_id'], 0, 16) . '...'); ?>
                                        </div>
                                        <div class="aica-session-date">
                                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($session['created_at']))); ?>
                                        </div>
                                    </div>
                                    <div class="aica-session-actions">
                                        <button class="button button-small button-link-delete js-delete-session" data-session-id="<?php echo esc_attr($session['session_id']); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="aica-diagnostics-message" class="aica-message-container"></div>
</div>

<style>
/* Nowoczesny styl dla strony diagnostyki */
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

.aica-admin-container {
    max-width: 1600px;
    margin: 20px auto;
}

.aica-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.aica-header h1 {
    margin: 0;
    padding: 0;
    font-size: 24px;
    font-weight: 600;
}

.aica-header-actions {
    display: flex;
    gap: 10px;
}

/* Dashboard layout */
.aica-dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

@media (max-width: 1500px) {
    .aica-dashboard {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }
}

@media (max-width: 782px) {
    .aica-dashboard {
        grid-template-columns: 1fr;
    }
}

/* Cards */
.aica-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
    margin-bottom: 20px;
}

.aica-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.aica-card-header {
    padding: 16px 20px;
    background-color: #fff;
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
    display: flex;
    align-items: center;
}

.aica-card-header h2 .dashicons {
    margin-right: 8px;
    color: var(--aica-primary);
}

.aica-card-header-actions {
    display: flex;
    gap: 8px;
}

.aica-card-body {
    padding: 20px;
}

/* Status sections */
.aica-status-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
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
    margin-bottom: 12px;
}

.aica-status-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.aica-status {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
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
    margin-right: 12px;
    font-size: 18px;
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

/* Status details table */
.aica-status-details {
    width: 100%;
    margin-bottom: 15px;
    border-collapse: separate;
    border-spacing: 0;
}

.aica-status-details th {
    width: 180px;
    text-align: left;
    padding: 10px 5px;
    font-weight: 500;
    color: var(--aica-text-light);
    vertical-align: top;
}

.aica-status-details td {
    padding: 10px 5px;
    vertical-align: top;
}

.aica-status-details tr:not(:last-child) th,
.aica-status-details tr:not(:last-child) td {
    border-bottom: 1px solid var(--aica-border);
}

/* Badges */
.aica-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    margin-right: 5px;
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
    background-color: var(--aica-primary-light);
    color: var(--aica-primary-dark);
}

/* Models list */
.aica-models-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.aica-model-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    background-color: var(--aica-bg-light);
    border: 1px solid var(--aica-border);
}

/* Progress bars */
.aica-progress-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.aica-progress-text {
    font-weight: 500;
    min-width: 40px;
}

.aica-progress-bar {
    flex-grow: 1;
    height: 8px;
    background-color: var(--aica-bg-light);
    border-radius: 4px;
    overflow: hidden;
}

.aica-progress-value {
    height: 100%;
    border-radius: 4px;
}

.aica-progress-success {
    background-color: var(--aica-success);
}

.aica-progress-warning {
    background-color: var(--aica-warning);
}

.aica-progress-error {
    background-color: var(--aica-error);
}

/* System info grid */
.aica-system-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.aica-system-info-item {
    padding: 10px;
    border-radius: 6px;
    background-color: var(--aica-bg-light);
}

.aica-system-info-label {
    font-size: 12px;
    color: var(--aica-text-light);
    margin-bottom: 5px;
}

.aica-system-info-value {
    font-weight: 500;
}

/* Database grid */
.aica-database-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.aica-database-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 6px;
    background-color: var(--aica-bg-light);
}

.aica-database-name {
    flex: 1;
    font-weight: 500;
}

.aica-database-status {
    margin-right: 10px;
}

/* Files list */
.aica-files-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    max-height: 500px;
    overflow-y: auto;
}

.aica-file-item {
    display: flex;
    align-items: flex-start;
    padding: 12px;
    border-radius: 6px;
    background-color: var(--aica-bg-light);
    transition: background-color 0.2s ease;
}

.aica-file-item:hover {
    background-color: #f0f0f0;
}

.aica-file-problem {
    background-color: var(--aica-error-light);
}

.aica-file-icon {
    margin-right: 12px;
    font-size: 20px;
    color: var(--aica-primary);
}

.aica-file-problem .aica-file-icon {
    color: var(--aica-error);
}

.aica-file-details {
    flex: 1;
}

.aica-file-name {
    font-weight: 500;
    margin-bottom: 5px;
}

.aica-file-info {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
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
    gap: 5px;
}

/* Chat sessions */
.aica-chat-sessions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    max-height: 600px;
    overflow-y: auto;
}

.aica-chat-session {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-radius: 6px;
    background-color: var(--aica-bg-light);
    transition: background-color 0.2s ease;
}

.aica-chat-session:hover {
    background-color: #f0f0f0;
}

.aica-session-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.aica-session-id {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}

.aica-session-date {
    font-size: 12px;
    color: var(--aica-text-light);
}

.aica-session-actions button {
    color: var(--aica-text-light);
    transition: color 0.2s ease;
}

.aica-session-actions button:hover {
    color: var(--aica-error);
}

/* Empty state */
.aica-empty-state {
    text-align: center;
    padding: 40px 20px;
}

.aica-empty-icon {
    font-size: 48px;
    color: var(--aica-text-light);
    opacity: 0.5;
    margin-bottom: 15px;
}

.aica-empty-state p {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 500;
}

.aica-empty-desc {
    color: var(--aica-text-light);
    font-size: 14px;
}

/* Recommendations panel */
.aica-recommendations-panel {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
    margin-bottom: 25px;
    padding: 20px;
}

.aica-recommendations-panel h2 {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.aica-recommendations-panel h2 .dashicons {
    margin-right: 8px;
    color: var(--aica-warning);
}

.aica-recommendations-panel ul {
    margin: 0;
    padding-left: 30px;
}

.aica-recommendations-panel li {
    margin-bottom: 8px;
}

.aica-recommendations-panel li:last-child {
    margin-bottom: 0;
}

/* Message container */
.aica-message-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    max-width: 400px;
    z-index: 9999;
}

.aica-message {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    animation: aica-slide-in 0.3s ease forwards;
}

@keyframes aica-slide-in {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.aica-message-success {
    background-color: var(--aica-success-light);
    border-left: 4px solid var(--aica-success);
    color: var(--aica-text);
}

.aica-message-error {
    background-color: var(--aica-error-light);
    border-left: 4px solid var(--aica-error);
    color: var(--aica-text);
}

/* Button styles */
.aica-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.aica-button .dashicons {
    font-size: 16px;
}

.aica-spin {
    animation: aica-spin 1.5s linear infinite;
}

@keyframes aica-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Funkcja wyświetlająca komunikaty
    function showMessage(type, message) {
        var messageContainer = $('#aica-diagnostics-message');
        var messageElement = $('<div class="aica-message aica-message-' + type + '">' + message + '</div>');
        
        messageContainer.append(messageElement);
        
        // Ukryj wiadomość po 5 sekundach
        setTimeout(function() {
            messageElement.css('opacity', '0');
            setTimeout(function() {
                messageElement.remove();
            }, 300);
        }, 5000);
    }
    
    // Przycisk testowania API Claude
    $('#test-claude-api').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        button.html('<span class="dashicons dashicons-update aica-spin"></span> <?php _e('Testowanie...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_verify_plugin',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', '<?php _e('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant'); ?>');
                    // Odśwież stronę, aby zaktualizować informacje
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message || '<?php _e('Nie udało się połączyć z API Claude.', 'ai-chat-assistant'); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php _e('Wystąpił błąd podczas testowania połączenia.', 'ai-chat-assistant'); ?>');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Przycisk testowania API GitHub
    $('#test-github-api').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        button.html('<span class="dashicons dashicons-update aica-spin"></span> <?php _e('Testowanie...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_verify_plugin',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', '<?php _e('Połączenie z API GitHub działa poprawnie.', 'ai-chat-assistant'); ?>');
                    // Odśwież stronę, aby zaktualizować informacje
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message || '<?php _e('Nie udało się połączyć z API GitHub.', 'ai-chat-assistant'); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php _e('Wystąpił błąd podczas testowania połączenia.', 'ai-chat-assistant'); ?>');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Przycisk "Testuj wszystkie API"
    $('#test-all-apis').on('click', function() {
        $('#test-claude-api').trigger('click');
        setTimeout(function() {
            $('#test-github-api').trigger('click');
        }, 1000);
    });
    
    // Przycisk naprawy bazy danych
    $('#repair-database').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        button.html('<span class="dashicons dashicons-update aica-spin"></span> <?php _e('Naprawa...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_repair_database',
                nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message || '<?php _e('Tabele bazy danych zostały pomyślnie naprawione.', 'ai-chat-assistant'); ?>');
                    // Odśwież stronę, aby zaktualizować informacje
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage('error', response.data.message || '<?php _e('Nie udało się naprawić tabel bazy danych.', 'ai-chat-assistant'); ?>');
                }
            },
            error: function() {
                showMessage('error', '<?php _e('Wystąpił błąd podczas naprawy bazy danych.', 'ai-chat-assistant'); ?>');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Przycisk odświeżenia całej diagnostyki
    $('#refresh-all-diagnostics').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        button.html('<span class="dashicons dashicons-update aica-spin"></span> <?php _e('Odświeżam...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        // Symulujemy odświeżenie całej strony
        setTimeout(function() {
            location.reload();
        }, 1000);
    });
    
    // Obsługa usuwania sesji
    $('.js-delete-session').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php _e('Czy na pewno chcesz usunąć tę sesję?', 'ai-chat-assistant'); ?>')) {
            var button = $(this);
            var sessionId = button.data('session-id');
            var sessionElement = button.closest('.aica-chat-session');
            
            // Dodaj klasę ładowania
            sessionElement.css('opacity', '0.5');
            button.html('<span class="dashicons dashicons-update aica-spin"></span>');
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aica_delete_session',
                    nonce: '<?php echo wp_create_nonce('aica_diagnostics_nonce'); ?>',
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message || '<?php _e('Sesja została usunięta.', 'ai-chat-assistant'); ?>');
                        
                        // Animacja znikania elementu
                        sessionElement.slideUp(300, function() {
                            $(this).remove();
                            
                            // Sprawdź, czy to był ostatni element
                            if ($('.aica-chat-session').length === 0) {
                                // Pokaż komunikat o braku sesji
                                $('.aica-chat-sessions').html(`
                                    <div class="aica-empty-state">
                                        <div class="aica-empty-icon">
                                            <span class="dashicons dashicons-format-chat"></span>
                                        </div>
                                        <p><?php _e('Brak historii czatu.', 'ai-chat-assistant'); ?></p>
                                        <p class="aica-empty-desc"><?php _e('Nie przeprowadziłeś jeszcze żadnych rozmów z Claude.', 'ai-chat-assistant'); ?></p>
                                    </div>
                                `);
                            }
                        });
                    } else {
                        showMessage('error', response.data.message || '<?php _e('Nie udało się usunąć sesji.', 'ai-chat-assistant'); ?>');
                        sessionElement.css('opacity', '1');
                        button.html('<span class="dashicons dashicons-trash"></span>');
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    showMessage('error', '<?php _e('Wystąpił błąd podczas usuwania sesji.', 'ai-chat-assistant'); ?>');
                    sessionElement.css('opacity', '1');
                    button.html('<span class="dashicons dashicons-trash"></span>');
                    button.prop('disabled', false);
                }
            });
        }
    });
});
</script>