<?php
/**
 * Szablon strony ustawień
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
        <h1><?php _e('Ustawienia AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
    </div>

    <?php 
    // Sprawdzenie czy wiadomość została zapisana
    if (isset($_GET['settings-updated'])) {
        echo '<div class="aica-notice aica-notice-success"><p>' . __('Ustawienia zostały zapisane.', 'ai-chat-assistant') . '</p></div>';
    }
    ?>

    <div class="aica-settings-container">
        <div class="aica-settings-sidebar">
            <ul class="aica-settings-tabs">
                <li class="aica-tab-item active" data-tab="claude-settings">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Claude API', 'ai-chat-assistant'); ?>
                </li>
                <li class="aica-tab-item" data-tab="repositories-settings">
                    <span class="dashicons dashicons-code-standards"></span>
                    <?php _e('Repozytoria', 'ai-chat-assistant'); ?>
                </li>
                <li class="aica-tab-item" data-tab="general-settings">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Ogólne', 'ai-chat-assistant'); ?>
                </li>
            </ul>
        </div>

        <div class="aica-settings-content">
            <form method="post" action="options.php">
                <?php wp_nonce_field('aica_settings_nonce'); ?>
                
                <div id="claude-settings" class="aica-tab-content active">
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Ustawienia Claude API', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Skonfiguruj połączenie z Anthropic Claude API do integracji czatu.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_claude_api_key"><?php _e('Klucz API Claude', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Klucz API do usługi Claude.ai. Możesz go uzyskać na stronie', 'ai-chat-assistant'); ?> 
                                        <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-api-key-field">
                                        <input type="password" id="aica_claude_api_key" name="aica_claude_api_key" 
                                            value="<?php echo esc_attr(get_option('aica_claude_api_key', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_claude_model"><?php _e('Model Claude', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Wybierz model Claude do wykorzystania w czacie.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <select id="aica_claude_model" name="aica_claude_model">
                                        <?php 
                                        $current_model = get_option('aica_claude_model', 'claude-3-haiku-20240307');
                                        foreach ($available_models as $model_id => $model_name) {
                                            echo '<option value="' . esc_attr($model_id) . '" ' . selected($current_model, $model_id, false) . '>' . esc_html($model_name) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_max_tokens"><?php _e('Maksymalna liczba tokenów', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Maksymalna liczba tokenów generowanych w odpowiedzi.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-range-field">
                                        <input type="range" id="aica_max_tokens_range" 
                                            min="1000" max="100000" step="1000" 
                                            value="<?php echo esc_attr(get_option('aica_max_tokens', 4000)); ?>" />
                                        <input type="number" id="aica_max_tokens" name="aica_max_tokens" 
                                            min="1000" max="100000" step="1000" 
                                            value="<?php echo esc_attr(get_option('aica_max_tokens', 4000)); ?>" />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-field-row aica-field-actions">
                                <button type="button" id="test-claude-api" class="button button-secondary aica-api-test-button">
                                    <span class="dashicons dashicons-marker"></span>
                                    <?php _e('Testuj połączenie z API', 'ai-chat-assistant'); ?>
                                </button>
                                <div id="api-test-result" class="aica-api-test-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="repositories-settings" class="aica-tab-content">
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Ustawienia GitHub', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Skonfiguruj dostęp do repozytoriów GitHub.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_github_token"><?php _e('Token dostępu GitHub', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Token dostępu osobistego z uprawnieniami do odczytu repozytoriów.', 'ai-chat-assistant'); ?>
                                        <a href="https://github.com/settings/tokens" target="_blank"><?php _e('Utwórz token', 'ai-chat-assistant'); ?></a>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-api-key-field">
                                        <input type="password" id="aica_github_token" name="aica_github_token" 
                                            value="<?php echo esc_attr(get_option('aica_github_token', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Ustawienia GitLab', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Skonfiguruj dostęp do repozytoriów GitLab.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_gitlab_token"><?php _e('Token dostępu GitLab', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Token dostępu osobistego z uprawnieniami do odczytu repozytoriów i kodu.', 'ai-chat-assistant'); ?>
                                        <a href="https://gitlab.com/-/profile/personal_access_tokens" target="_blank"><?php _e('Utwórz token', 'ai-chat-assistant'); ?></a>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-api-key-field">
                                        <input type="password" id="aica_gitlab_token" name="aica_gitlab_token" 
                                            value="<?php echo esc_attr(get_option('aica_gitlab_token', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Ustawienia Bitbucket', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Skonfiguruj dostęp do repozytoriów Bitbucket.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_bitbucket_username"><?php _e('Nazwa użytkownika Bitbucket', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Twoja nazwa użytkownika Bitbucket.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <input type="text" id="aica_bitbucket_username" name="aica_bitbucket_username" 
                                        value="<?php echo esc_attr(get_option('aica_bitbucket_username', '')); ?>" />
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_bitbucket_app_password"><?php _e('Hasło aplikacji Bitbucket', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Hasło aplikacji z uprawnieniami do odczytu repozytoriów.', 'ai-chat-assistant'); ?>
                                        <a href="https://bitbucket.org/account/settings/app-passwords/" target="_blank"><?php _e('Utwórz hasło aplikacji', 'ai-chat-assistant'); ?></a>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-api-key-field">
                                        <input type="password" id="aica_bitbucket_app_password" name="aica_bitbucket_app_password" 
                                            value="<?php echo esc_attr(get_option('aica_bitbucket_app_password', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="general-settings" class="aica-tab-content">
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Ustawienia ogólne', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Ogólne ustawienia wtyczki.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_allowed_file_extensions"><?php _e('Dozwolone rozszerzenia plików', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Lista rozszerzeń plików oddzielonych przecinkami, które można przesyłać.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-tags-input-container">
                                        <div class="aica-tags-input-field">
                                            <input type="text" id="aica_file_extension_input" placeholder="<?php _e('Dodaj rozszerzenie...', 'ai-chat-assistant'); ?>" />
                                            <button type="button" id="add-extension" class="button button-secondary">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                            </button>
                                        </div>
                                        <div class="aica-tags-container" id="extensions-container">
                                            <?php 
                                            $extensions = explode(',', get_option('aica_allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md'));
                                            foreach ($extensions as $ext) {
                                                $ext = trim($ext);
                                                if (!empty($ext)) {
                                                    echo '<span class="aica-tag">' . esc_html($ext) . '<button type="button" class="aica-remove-tag" data-value="' . esc_attr($ext) . '"><span class="dashicons dashicons-no-alt"></span></button></span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <input type="hidden" id="aica_allowed_file_extensions" name="aica_allowed_file_extensions" 
                                            value="<?php echo esc_attr(get_option('aica_allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md')); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="aica-form-actions">
                    <input type="submit" name="aica_save_settings" class="button button-primary aica-save-button" value="<?php _e('Zapisz ustawienia', 'ai-chat-assistant'); ?>" />
                </div>
            </form>
        </div>
    </div>
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

.aica-header {
    margin-bottom: 25px;
}

.aica-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    padding: 0;
}

/* Notices */
.aica-notice {
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 4px;
    border-left: 4px solid transparent;
}

.aica-notice p {
    margin: 0;
}

.aica-notice-success {
    background-color: var(--aica-success-light);
    border-left-color: var(--aica-success);
}

.aica-notice-warning {
    background-color: var(--aica-warning-light);
    border-left-color: var(--aica-warning);
}

.aica-notice-error {
    background-color: var(--aica-error-light);
    border-left-color: var(--aica-error);
}

/* Settings Container */
.aica-settings-container {
    display: flex;
    gap: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
    overflow: hidden;
}

/* Sidebar Tabs */
.aica-settings-sidebar {
    width: 220px;
    background-color: #f9f9f9;
    border-right: 1px solid var(--aica-border);
}

.aica-settings-tabs {
    margin: 0;
    padding: 0;
    list-style: none;
}

.aica-tab-item {
    padding: 14px 16px;
    cursor: pointer;
    border-bottom: 1px solid var(--aica-border);
    font-size: 14px;
    display: flex;
    align-items: center;
    transition: background-color 0.2s ease;
}

.aica-tab-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.aica-tab-item.active {
    background-color: #fff;
    color: var(--aica-primary);
    border-left: 3px solid var(--aica-primary);
    font-weight: 500;
}

.aica-tab-item .dashicons {
    margin-right: 8px;
    color: var(--aica-text-light);
}

.aica-tab-item.active .dashicons {
    color: var(--aica-primary);
}

/* Content Area */
.aica-settings-content {
    flex: 1;
    padding: 20px 25px;
}

.aica-tab-content {
    display: none;
}

.aica-tab-content.active {
    display: block;
}

/* Cards */
.aica-settings-card {
    margin-bottom: 25px;
    border: 1px solid var(--aica-border);
    border-radius: 6px;
    overflow: hidden;
}

.aica-card-header {
    padding: 16px 20px;
    background-color: #f9f9f9;
    border-bottom: 1px solid var(--aica-border);
}

.aica-card-header h2 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
}

.aica-card-description {
    margin: 5px 0 0 0;
    font-size: 13px;
    color: var(--aica-text-light);
}

.aica-card-body {
    padding: 20px;
}

/* Fields */
.aica-field-row {
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
}

.aica-field-row:last-child {
    margin-bottom: 0;
}

.aica-field-label {
    width: 33%;
    padding-right: 20px;
}

.aica-field-label label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
}

.aica-field-description {
    font-size: 13px;
    color: var(--aica-text-light);
    margin: 0;
}

.aica-field-input {
    width: 67%;
}

.aica-field-input input[type="text"],
.aica-field-input input[type="password"],
.aica-field-input input[type="number"],
.aica-field-input select,
.aica-field-input textarea {
    width: 100%;
    max-width: 400px;
    padding: 8px 12px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 14px;
}

.aica-field-input input[type="text"]:focus,
.aica-field-input input[type="password"]:focus,
.aica-field-input input[type="number"]:focus,
.aica-field-input select:focus,
.aica-field-input textarea:focus {
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 1px var(--aica-primary);
    outline: none;
}

/* API Key Field */
.aica-api-key-field {
    display: flex;
    max-width: 400px;
}

.aica-api-key-field input {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.aica-toggle-password {
    background: #f0f0f0;
    border: 1px solid var(--aica-border);
    border-left: none;
    padding: 0 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-top-right-radius: 4px;
    border-bottom-right-radius: 4px;
}

.aica-toggle-password:hover {
    background: #e5e5e5;
}

/* Range Field */
.aica-range-field {
    display: flex;
    align-items: center;
    gap: 15px;
    max-width: 400px;
}

.aica-range-field input[type="range"] {
    flex: 1;
}

.aica-range-field input[type="number"] {
    width: 90px !important;
}

/* API Test Button */
.aica-field-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.aica-api-test-button {
    display: flex !important;
    align-items: center;
    gap: 5px;
}

.aica-api-test-button .dashicons {
    font-size: 16px;
}

.aica-api-test-result {
    font-size: 14px;
}

/* Tags Input */
.aica-tags-input-container {
    max-width: 500px;
}

.aica-tags-input-field {
    display: flex;
    margin-bottom: 10px;
}

.aica-tags-input-field input {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.aica-tags-input-field button {
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    display: flex !important;
    align-items: center;
    justify-content: center;
}

.aica-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.aica-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background-color: var(--aica-bg-light);
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    padding: 5px 8px;
    font-size: 13px;
}

.aica-remove-tag {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: var(--aica-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
}

.aica-remove-tag:hover {
    color: var(--aica-error);
}

.aica-remove-tag .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Form Actions */
.aica-form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--aica-border);
    text-align: right;
}

.aica-save-button {
    padding: 6px 20px !important;
    font-size: 14px !important;
    height: auto !important;
    min-height: 36px;
}

@media screen and (max-width: 782px) {
    .aica-settings-container {
        flex-direction: column;
    }
    
    .aica-settings-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--aica-border);
    }
    
    .aica-tab-item {
        padding: 12px;
    }
    
    .aica-field-label,
    .aica-field-input {
        width: 100%;
    }
    
    .aica-field-label {
        margin-bottom: 10px;
        padding-right: 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Obsługa zakładek
    $('.aica-tab-item').on('click', function() {
        // Usunięcie aktywnej klasy z wszystkich zakładek
        $('.aica-tab-item').removeClass('active');
        // Dodanie aktywnej klasy do klikniętej zakładki
        $(this).addClass('active');
        
        // Ukrycie wszystkich zawartości zakładek
        $('.aica-tab-content').removeClass('active');
        
        // Pobranie id zawartości zakładki
        var tabId = $(this).data('tab');
        
        // Pokazanie zawartości aktywnej zakładki
        $('#' + tabId).addClass('active');
    });
    
    // Obsługa pola z zakresem
    $('#aica_max_tokens_range').on('input', function() {
        $('#aica_max_tokens').val($(this).val());
    });
    
    $('#aica_max_tokens').on('input', function() {
        $('#aica_max_tokens_range').val($(this).val());
    });
    
    // Obsługa przycisków pokaż/ukryj hasło
    $('.aica-toggle-password').on('click', function() {
        var input = $(this).siblings('input');
        var icon = $(this).find('.dashicons');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // Obsługa pola tagów z rozszerzeniami plików
    function updateExtensionsField() {
        var extensions = [];
        $('.aica-tag').each(function() {
            extensions.push($(this).text().trim());
        });
        $('#aica_allowed_file_extensions').val(extensions.join(','));
    }
    
    $('#add-extension').on('click', function() {
        var extension = $('#aica_file_extension_input').val().trim();
        
        if (extension !== '') {
            // Sprawdzenie czy rozszerzenie już istnieje
            var exists = false;
            $('.aica-tag').each(function() {
                if ($(this).text().trim() === extension) {
                    exists = true;
                    return false;
                }
                });
            
            if (!exists) {
                // Dodanie nowego tagu
                var tag = $('<span class="aica-tag">' + extension + '<button type="button" class="aica-remove-tag" data-value="' + extension + '"><span class="dashicons dashicons-no-alt"></span></button></span>');
                $('#extensions-container').append(tag);
                $('#aica_file_extension_input').val('');
                
                // Aktualizacja pola ukrytego
                updateExtensionsField();
            }
        }
    });
    
    // Obsługa wciśnięcia Enter w polu dodawania rozszerzeń
    $('#aica_file_extension_input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#add-extension').trigger('click');
        }
    });
    
    // Usuwanie tagów
    $(document).on('click', '.aica-remove-tag', function() {
        $(this).parent().remove();
        updateExtensionsField();
    });
    
    // Testowanie połączenia z API Claude
    $('#test-claude-api').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        var resultContainer = $('#api-test-result');
        
        // Ukrycie poprzedniego wyniku
        resultContainer.html('');
        
        // Zmiana tekstu przycisku
        button.html('<span class="dashicons dashicons-update aica-spin"></span> <?php _e('Testowanie...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        // Pobranie klucza API
        var apiKey = $('#aica_claude_api_key').val();
        
        if (apiKey === '') {
            resultContainer.html('<span class="aica-notice-error"><?php _e('Wprowadź klucz API.', 'ai-chat-assistant'); ?></span>');
            button.html(originalText);
            button.prop('disabled', false);
            return;
        }
        
        // Wywołanie AJAX do testowania połączenia
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_claude_api',
                nonce: '<?php echo wp_create_nonce('aica_settings_nonce'); ?>',
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    resultContainer.html('<span class="aica-notice-success"><?php _e('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant'); ?></span>');
                } else {
                    resultContainer.html('<span class="aica-notice-error">' + response.data.message + '</span>');
                }
            },
            error: function() {
                resultContainer.html('<span class="aica-notice-error"><?php _e('Wystąpił błąd podczas testowania połączenia.', 'ai-chat-assistant'); ?></span>');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
});
</script>