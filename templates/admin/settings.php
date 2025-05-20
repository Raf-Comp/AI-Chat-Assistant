<?php
/**
 * Szablon strony ustawień - nowoczesny interfejs
 *
 * @package AI_Chat_Assistant
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}

// Pobranie czasu ostatniej aktualizacji modeli
$last_update = aica_get_option('claude_models_last_update', '');
$last_update_display = !empty($last_update) ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_update)) : __('Nigdy', 'ai-chat-assistant');
?>

<div class="aica-admin-container">
    <div class="aica-header">
        <div class="aica-header-content">
            <h1><?php _e('AI Chat Assistant', 'ai-chat-assistant'); ?></h1>
            <p class="aica-version"><?php echo 'v' . AICA_VERSION; ?></p>
        </div>
        <div class="aica-header-actions">
            <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant'); ?>" class="aica-button aica-button-outlined">
                <span class="dashicons dashicons-format-chat"></span>
                <?php _e('Otwórz czat', 'ai-chat-assistant'); ?>
            </a>
            <a href="https://anthropic.com/claude" target="_blank" class="aica-button aica-button-text">
                <span class="dashicons dashicons-external"></span>
                <?php _e('O Claude AI', 'ai-chat-assistant'); ?>
            </a>
        </div>
    </div>

    <?php 
    // Sprawdzenie czy wiadomość została zapisana
    if (isset($_GET['settings-updated'])) {
        echo '<div class="aica-notice aica-notice-success">
            <span class="aica-notice-icon dashicons dashicons-yes-alt"></span>
            <div class="aica-notice-content">
                <p class="aica-notice-title">' . __('Ustawienia zapisane pomyślnie', 'ai-chat-assistant') . '</p>
                <p class="aica-notice-message">' . __('Wszystkie zmiany zostały zapisane.', 'ai-chat-assistant') . '</p>
            </div>
            <button type="button" class="aica-notice-dismiss"><span class="dashicons dashicons-no-alt"></span></button>
        </div>';
    }
    ?>

    <div class="aica-settings-container">
        <div class="aica-settings-sidebar">
            <div class="aica-sidebar-header">
                <div class="aica-sidebar-avatar">
                    <img src="<?php echo AICA_PLUGIN_URL . 'assets/images/claude-avatar.png'; ?>" alt="Claude AI">
                </div>
                <div class="aica-sidebar-info">
                    <p class="aica-sidebar-title"><?php _e('Claude AI', 'ai-chat-assistant'); ?></p>
                    <p class="aica-sidebar-desc"><?php _e('by Anthropic', 'ai-chat-assistant'); ?></p>
                </div>
            </div>
            <ul class="aica-settings-tabs">
                <li class="aica-tab-item active" data-tab="claude-settings">
                    <span class="aica-tab-icon dashicons dashicons-admin-generic"></span>
                    <span class="aica-tab-text"><?php _e('Claude API', 'ai-chat-assistant'); ?></span>
                </li>
                <li class="aica-tab-item" data-tab="models-settings">
                    <span class="aica-tab-icon dashicons dashicons-superhero"></span>
                    <span class="aica-tab-text"><?php _e('Modele AI', 'ai-chat-assistant'); ?></span>
                </li>
                <li class="aica-tab-item" data-tab="repositories-settings">
                    <span class="aica-tab-icon dashicons dashicons-code-standards"></span>
                    <span class="aica-tab-text"><?php _e('Repozytoria', 'ai-chat-assistant'); ?></span>
                </li>
                <li class="aica-tab-item" data-tab="general-settings">
                    <span class="aica-tab-icon dashicons dashicons-admin-settings"></span>
                    <span class="aica-tab-text"><?php _e('Ogólne', 'ai-chat-assistant'); ?></span>
                </li>
            </ul>
            <div class="aica-sidebar-footer">
                <p><?php _e('Ostatnia aktualizacja modeli:', 'ai-chat-assistant'); ?><br><strong><?php echo $last_update_display; ?></strong></p>
                <button type="button" id="refresh-models" class="aica-button aica-button-sm">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Odśwież', 'ai-chat-assistant'); ?>
                </button>
            </div>
        </div>

        <div class="aica-settings-content">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="aica-settings-form">
                <?php wp_nonce_field('aica_settings_nonce', 'aica_settings_nonce'); ?>
                <input type="hidden" name="action" value="save_aica_settings">
                
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
                                            value="<?php echo esc_attr(aica_get_option('claude_api_key', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-field-row aica-field-actions">
                                <button type="button" id="test-claude-api" class="aica-button aica-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z API', 'ai-chat-assistant'); ?>
                                </button>
                                <div id="api-test-result" class="aica-api-test-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="models-settings" class="aica-tab-content">
                    <div class="aica-settings-card">
                        <div class="aica-card-header">
                            <h2><?php _e('Modele Claude AI', 'ai-chat-assistant'); ?></h2>
                            <p class="aica-card-description"><?php _e('Wybierz model Claude AI, który ma być używany w czacie.', 'ai-chat-assistant'); ?></p>
                        </div>
                        <div class="aica-card-body">
                            <div class="aica-models-grid">
                                <?php 
                                $current_model = aica_get_option('claude_model', 'claude-3-haiku-20240307');
                                foreach ($available_models as $model_id => $model_name) :
                                    // Określenie klasy dla karty modelu
                                    $is_selected = ($current_model === $model_id) ? 'selected' : '';
                                    $model_type = '';
                                    
                                    if (strpos($model_id, 'opus') !== false) {
                                        $model_type = 'opus';
                                    } elseif (strpos($model_id, 'sonnet') !== false) {
                                        $model_type = 'sonnet';
                                    } elseif (strpos($model_id, 'haiku') !== false) {
                                        $model_type = 'haiku';
                                    } elseif (strpos($model_id, 'instant') !== false) {
                                        $model_type = 'instant';
                                    } else {
                                        $model_type = 'claude';
                                    }
                                ?>
                                <div class="aica-model-card <?php echo $is_selected . ' ' . $model_type; ?>">
                                    <input type="radio" id="model_<?php echo esc_attr($model_id); ?>" 
                                        name="aica_claude_model" value="<?php echo esc_attr($model_id); ?>"
                                        <?php checked($current_model, $model_id); ?>>
                                    <label for="model_<?php echo esc_attr($model_id); ?>" class="aica-model-content">
                                        <div class="aica-model-header">
                                            <div class="aica-model-icon">
                                                <span class="dashicons dashicons-superhero"></span>
                                            </div>
                                            <div class="aica-model-badge"><?php echo $this->get_model_badge_text($model_id); ?></div>
                                        </div>
                                        <h3 class="aica-model-name"><?php echo esc_html($model_name); ?></h3>
                                        <div class="aica-model-desc"><?php echo $this->get_model_description($model_id); ?></div>
                                        <div class="aica-model-specs">
                                            <div class="aica-model-spec">
                                                <span class="aica-spec-label"><?php _e('Moc', 'ai-chat-assistant'); ?></span>
                                                <div class="aica-spec-value">
                                                    <div class="aica-rating-dots">
                                                        <?php echo $this->get_model_power_rating($model_id); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="aica-model-spec">
                                                <span class="aica-spec-label"><?php _e('Szybkość', 'ai-chat-assistant'); ?></span>
                                                <div class="aica-spec-value">
                                                    <div class="aica-rating-dots">
                                                        <?php echo $this->get_model_speed_rating($model_id); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_max_tokens"><?php _e('Maksymalna liczba tokenów', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Ustaw maksymalną liczbę tokenów generowanych w odpowiedzi. Większe wartości dają dłuższe odpowiedzi, ale zwiększają koszt zapytania.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-range-field">
                                        <input type="range" id="aica_max_tokens_range" 
                                            min="1000" max="100000" step="1000" 
                                            value="<?php echo esc_attr(aica_get_option('max_tokens', 4000)); ?>" />
                                        <div class="aica-range-value-container">
                                            <input type="number" id="aica_max_tokens" name="aica_max_tokens" 
                                                min="1000" max="100000" step="1000" 
                                                value="<?php echo esc_attr(aica_get_option('max_tokens', 4000)); ?>" />
                                            <span class="aica-range-unit"><?php _e('tokenów', 'ai-chat-assistant'); ?></span>
                                        </div>
                                    </div>
                                    <div class="aica-range-labels">
                                        <span><?php _e('Krótkie', 'ai-chat-assistant'); ?></span>
                                        <span><?php _e('Standardowe', 'ai-chat-assistant'); ?></span>
                                        <span><?php _e('Długie', 'ai-chat-assistant'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_temperature"><?php _e('Temperatura', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Kontroluje kreatywność i losowość odpowiedzi. Niższe wartości dają bardziej przewidywalne odpowiedzi, wyższe - bardziej kreatywne.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-range-field">
                                        <input type="range" id="aica_temperature_range" 
                                            min="0" max="1" step="0.1" 
                                            value="<?php echo esc_attr(aica_get_option('temperature', 0.7)); ?>" />
                                        <div class="aica-range-value-container">
                                            <input type="number" id="aica_temperature" name="aica_temperature" 
                                                min="0" max="1" step="0.1" 
                                                value="<?php echo esc_attr(aica_get_option('temperature', 0.7)); ?>" />
                                        </div>
                                    </div>
                                    <div class="aica-range-labels">
                                        <span><?php _e('Precyzyjne', 'ai-chat-assistant'); ?></span>
                                        <span><?php _e('Zbalansowane', 'ai-chat-assistant'); ?></span>
                                        <span><?php _e('Kreatywne', 'ai-chat-assistant'); ?></span>
                                    </div>
                                </div>
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
                                            value="<?php echo esc_attr(aica_get_option('github_token', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="aica-field-row aica-field-actions">
                                <button type="button" id="test-github-api" class="aica-button aica-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z GitHub', 'ai-chat-assistant'); ?>
                                </button>
                                <div id="github-test-result" class="aica-api-test-result"></div>
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
                                            value="<?php echo esc_attr(aica_get_option('gitlab_token', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="aica-field-row aica-field-actions">
                                <button type="button" id="test-gitlab-api" class="aica-button aica-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z GitLab', 'ai-chat-assistant'); ?>
                                </button>
                                <div id="gitlab-test-result" class="aica-api-test-result"></div>
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
                                        value="<?php echo esc_attr(aica_get_option('bitbucket_username', '')); ?>" />
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
                                            value="<?php echo esc_attr(aica_get_option('bitbucket_app_password', '')); ?>" />
                                        <button type="button" class="aica-toggle-password" aria-label="<?php _e('Pokaż/ukryj hasło', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="aica-field-row aica-field-actions">
                                <button type="button" id="test-bitbucket-api" class="aica-button aica-button-secondary">
                                    <span class="dashicons dashicons-database-view"></span>
                                    <?php _e('Testuj połączenie z Bitbucket', 'ai-chat-assistant'); ?>
                                </button>
                                <div id="bitbucket-test-result" class="aica-api-test-result"></div>
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
                                            <button type="button" id="add-extension" class="aica-button aica-button-icon">
                                                <span class="dashicons dashicons-plus-alt"></span>
                                            </button>
                                        </div>
                                        <div class="aica-tags-container" id="extensions-container">
                                            <?php 
                                            $extensions = explode(',', aica_get_option('allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md'));
                                            foreach ($extensions as $ext) {
                                                $ext = trim($ext);
                                                if (!empty($ext)) {
                                                    echo '<span class="aica-tag">' . esc_html($ext) . '<button type="button" class="aica-remove-tag" data-value="' . esc_attr($ext) . '"><span class="dashicons dashicons-no-alt"></span></button></span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <input type="hidden" id="aica_allowed_file_extensions" name="aica_allowed_file_extensions" 
                                            value="<?php echo esc_attr(aica_get_option('allowed_file_extensions', 'txt,pdf,php,js,css,html,json,md')); ?>" />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_prompt_templates"><?php _e('Szablony promptów', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Dodaj predefiniowane szablony promptów, które użytkownicy mogą szybko wybrać w czacie.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div id="aica-templates-container">
                                        <?php
                                        $templates = aica_get_option('prompt_templates', [
                                            [
                                                'name' => 'Analiza kodu',
                                                'prompt' => 'Przeanalizuj ten kod i opisz co robi. Wskaż potencjalne problemy lub obszary do optymalizacji.'
                                            ],
                                            [
                                                'name' => 'Wyjaśnienie technicznej koncepcji',
                                                'prompt' => 'Wyjaśnij następującą koncepcję w prosty sposób: [TEMAT]'
                                            ]
                                        ]);
                                        
                                        foreach ($templates as $index => $template) :
                                        ?>
                                        <div class="aica-template-item">
                                            <div class="aica-template-header">
                                                <input type="text" name="aica_prompt_templates[<?php echo $index; ?>][name]" 
                                                    value="<?php echo esc_attr($template['name']); ?>" 
                                                    placeholder="<?php _e('Nazwa szablonu', 'ai-chat-assistant'); ?>" />
                                                <div class="aica-template-actions">
                                                    <button type="button" class="aica-template-delete aica-button aica-button-icon">
                                                        <span class="dashicons dashicons-trash"></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea name="aica_prompt_templates[<?php echo $index; ?>][prompt]" 
                                                placeholder="<?php _e('Treść szablonu promptu...', 'ai-chat-assistant'); ?>"><?php echo esc_textarea($template['prompt']); ?></textarea>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" id="add-template" class="aica-button aica-button-secondary">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Dodaj nowy szablon', 'ai-chat-assistant'); ?>
                                    </button>
                                    <template id="template-item-template">
                                        <div class="aica-template-item">
                                            <div class="aica-template-header">
                                                <input type="text" name="aica_prompt_templates[__INDEX__][name]" 
                                                    placeholder="<?php _e('Nazwa szablonu', 'ai-chat-assistant'); ?>" />
                                                <div class="aica-template-actions">
                                                    <button type="button" class="aica-template-delete aica-button aica-button-icon">
                                                        <span class="dashicons dashicons-trash"></span>
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea name="aica_prompt_templates[__INDEX__][prompt]" 
                                                placeholder="<?php _e('Treść szablonu promptu...', 'ai-chat-assistant'); ?>"></textarea>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_debug_mode"><?php _e('Tryb debugowania', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Włącz rejestrowanie zdarzeń w celach diagnostycznych.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <label class="aica-toggle-switch">
                                        <input type="checkbox" id="aica_debug_mode" name="aica_debug_mode" value="1" <?php checked(aica_get_option('debug_mode', false)); ?> />
                                        <span class="aica-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="aica-field-row">
                                <div class="aica-field-label">
                                    <label for="aica_auto_purge_history"><?php _e('Automatyczne czyszczenie historii', 'ai-chat-assistant'); ?></label>
                                    <p class="aica-field-description">
                                        <?php _e('Automatycznie usuwaj stare rozmowy po określonej liczbie dni.', 'ai-chat-assistant'); ?>
                                    </p>
                                </div>
                                <div class="aica-field-input">
                                    <div class="aica-auto-purge-container">
                                        <label class="aica-toggle-switch">
                                            <input type="checkbox" id="aica_auto_purge_enabled" name="aica_auto_purge_enabled" value="1" <?php checked(aica_get_option('auto_purge_enabled', false)); ?> />
                                            <span class="aica-toggle-slider"></span>
                                        </label>
                                        <div class="aica-auto-purge-days">
                                            <input type="number" id="aica_auto_purge_days" name="aica_auto_purge_days" 
                                                min="1" max="365" 
                                                value="<?php echo esc_attr(aica_get_option('auto_purge_days', 30)); ?>" 
                                                <?php echo aica_get_option('auto_purge_enabled', false) ? '' : 'disabled'; ?> />
                                            <span class="aica-days-label"><?php _e('dni', 'ai-chat-assistant'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="aica-form-actions">
                    <button type="submit" name="aica_save_settings" class="aica-button aica-button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Zapisz ustawienia', 'ai-chat-assistant'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --aica-primary: #6366f1;
    --aica-primary-light: #a5b4fc;
    --aica-primary-dark: #4f46e5;
    --aica-success: #22c55e;
    --aica-success-light: #dcfce7;
    --aica-warning: #f59e0b;
    --aica-warning-light: #fef3c7;
    --aica-error: #ef4444;
    --aica-error-light: #fee2e2;
    --aica-text: #1e293b;
    --aica-text-light: #64748b;
    --aica-text-lighter: #94a3b8;
    --aica-border: #e2e8f0;
    --aica-bg-light: #f8fafc;
    --aica-card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --aica-transition: all 0.2s ease;
    --aica-border-radius: 8px;
    --aica-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Reset and base styles */
.aica-admin-container {
    max-width: 1280px;
    margin: 32px auto 0;
    font-family: var(--aica-font-family);
    color: var(--aica-text);
    line-height: 1.5;
}

.aica-admin-container * {
    box-sizing: border-box;
}

.aica-admin-container a {
    color: var(--aica-primary);
    text-decoration: none;
    transition: var(--aica-transition);
}

.aica-admin-container a:hover {
    color: var(--aica-primary-dark);
    text-decoration: underline;
}

/* Header */
.aica-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
}

.aica-header-content h1 {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 4px;
    color: var(--aica-text);
}

.aica-version {
    margin: 0;
    font-size: 14px;
    color: var(--aica-text-light);
}

.aica-header-actions {
    display: flex;
    gap: 12px;
}

/* Buttons */
.aica-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    border-radius: var(--aica-border-radius);
    cursor: pointer;
    transition: var(--aica-transition);
    border: none;
    background: none;
    line-height: 1.5;
}

.aica-button:focus {
    outline: none;
    box-shadow: 0 0 0 2px var(--aica-primary-light);
}

.aica-button-primary {
    background-color: var(--aica-primary);
    color: white;
}

.aica-button-primary:hover {
    background-color: var(--aica-primary-dark);
}

.aica-button-secondary {
    background-color: white;
    color: var(--aica-text);
    border: 1px solid var(--aica-border);
}

.aica-button-secondary:hover {
    background-color: var(--aica-bg-light);
}

.aica-button-outlined {
    border: 1px solid var(--aica-primary);
    color: var(--aica-primary);
}

.aica-button-outlined:hover {
    background-color: rgba(99, 102, 241, 0.05);
}

.aica-button-text {
    color: var(--aica-text-light);
}

.aica-button-text:hover {
    color: var(--aica-text);
}

.aica-button-icon {
    padding: 8px;
    min-height: 40px;
}

.aica-button-sm {
    padding: 4px 12px;
    font-size: 13px;
}

.aica-button .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.aica-button-sm .dashicons,
.aica-button-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Notices */
.aica-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    margin-bottom: 32px;
    border-radius: var(--aica-border-radius);
    position: relative;
}

.aica-notice-success {
    background-color: var(--aica-success-light);
    border-left: 4px solid var(--aica-success);
}

.aica-notice-warning {
    background-color: var(--aica-warning-light);
    border-left: 4px solid var(--aica-warning);
}

.aica-notice-error {
    background-color: var(--aica-error-light);
    border-left: 4px solid var(--aica-error);
}

.aica-notice-icon {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.aica-notice-success .aica-notice-icon {
    color: var(--aica-success);
}

.aica-notice-warning .aica-notice-icon {
    color: var(--aica-warning);
}

.aica-notice-error .aica-notice-icon {
    color: var(--aica-error);
}

.aica-notice-content {
    flex: 1;
}

.aica-notice-title {
    font-weight: 600;
    margin: 0 0 4px 0;
}

.aica-notice-message {
    margin: 0;
    color: var(--aica-text-light);
}

.aica-notice-dismiss {
    position: absolute;
    top: 12px;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    color: var(--aica-text-light);
}

.aica-notice-dismiss:hover {
    color: var(--aica-text);
}

/* Settings Container */
.aica-settings-container {
    display: flex;
    gap: 24px;
    background: white;
    border-radius: var(--aica-border-radius);
    box-shadow: var(--aica-card-shadow);
    overflow: hidden;
}

/* Sidebar */
.aica-settings-sidebar {
    width: 260px;
    background-color: #f8fafc;
    border-right: 1px solid var(--aica-border);
    display: flex;
    flex-direction: column;
}

.aica-sidebar-header {
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--aica-border);
}

.aica-sidebar-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    background-color: var(--aica-primary-light);
}

.aica-sidebar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.aica-sidebar-title {
    font-weight: 600;
    margin: 0;
    font-size: 16px;
}

.aica-sidebar-desc {
    margin: 0;
    font-size: 13px;
    color: var(--aica-text-light);
}

.aica-settings-tabs {
    margin: 0;
    padding: 0;
    list-style: none;
    flex: 1;
}

.aica-tab-item {
    padding: 14px 24px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: var(--aica-transition);
    border-left: 4px solid transparent;
}

.aica-tab-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.aica-tab-item.active {
    background-color: rgba(99, 102, 241, 0.05);
    color: var(--aica-primary);
    border-left-color: var(--aica-primary);
    font-weight: 500;
}

.aica-tab-icon {
    color: var(--aica-text-light);
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.aica-tab-item.active .aica-tab-icon {
    color: var(--aica-primary);
}

.aica-sidebar-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--aica-border);
    font-size: 13px;
    color: var(--aica-text-light);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.aica-sidebar-footer p {
    margin: 0;
}

/* Content Area */
.aica-settings-content {
    flex: 1;
    padding: 32px;
    max-width: calc(100% - 260px);
}

.aica-tab-content {
    display: none;
}

.aica-tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Cards */
.aica-settings-card {
    margin-bottom: 32px;
    border: 1px solid var(--aica-border);
    border-radius: var(--aica-border-radius);
    overflow: hidden;
    background-color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.aica-card-header {
    padding: 20px 24px;
    background-color: #f8fafc;
    border-bottom: 1px solid var(--aica-border);
}

.aica-card-header h2 {
    margin: 0;
    padding: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--aica-text);
}

.aica-card-description {
    margin: 6px 0 0 0;
    font-size: 14px;
    color: var(--aica-text-light);
}

.aica-card-body {
    padding: 24px;
}

/* Fields */
.aica-field-row {
    margin-bottom: 32px;
}

.aica-field-row:last-child {
    margin-bottom: 0;
}

.aica-field-label {
    margin-bottom: 12px;
}

.aica-field-label label {
    display: block;
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 6px;
    color: var(--aica-text);
}

.aica-field-description {
    font-size: 13px;
    color: var(--aica-text-light);
    margin: 0;
}

.aica-field-input input[type="text"],
.aica-field-input input[type="password"],
.aica-field-input input[type="number"],
.aica-field-input select,
.aica-field-input textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--aica-border);
    border-radius: var(--aica-border-radius);
    font-size: 14px;
    transition: var(--aica-transition);
    color: var(--aica-text);
    background-color: white;
}

.aica-field-input input[type="text"]:focus,
.aica-field-input input[type="password"]:focus,
.aica-field-input input[type="number"]:focus,
.aica-field-input select:focus,
.aica-field-input textarea:focus {
    border-color: var(--aica-primary);
    outline: none;
    box-shadow: 0 0 0 3px var(--aica-primary-light);
}

.aica-field-input textarea {
    min-height: 100px;
    resize: vertical;
}

/* API Key Field */
.aica-api-key-field {
    display: flex;
    position: relative;
}

.aica-api-key-field input {
    padding-right: 40px;
}

.aica-toggle-password {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 40px;
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-text-light);
    transition: var(--aica-transition);
}

.aica-toggle-password:hover {
    color: var(--aica-text);
}

/* Range Field */
.aica-range-field {
    display: flex;
    align-items: center;
    gap: 16px;
}

.aica-range-field input[type="range"] {
    flex: 1;
    height: 6px;
    -webkit-appearance: none;
    background: #e2e8f0;
    border-radius: 3px;
    outline: none;
}

.aica-range-field input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--aica-primary);
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: var(--aica-transition);
}

.aica-range-field input[type="range"]::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--aica-primary);
    cursor: pointer;
    border: 2px solid white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: var(--aica-transition);
}

.aica-range-field input[type="range"]::-webkit-slider-thumb:hover {
    background: var(--aica-primary-dark);
}

.aica-range-value-container {
    display: flex;
    align-items: center;
    min-width: 100px;
}

.aica-range-field input[type="number"] {
    width: 80px;
    text-align: right;
}

.aica-range-unit {
    margin-left: 8px;
    color: var(--aica-text-light);
    font-size: 14px;
}

.aica-range-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 12px;
    color: var(--aica-text-light);
}

/* Toggle Switch */
.aica-toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.aica-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.aica-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #e2e8f0;
    transition: .4s;
    border-radius: 24px;
}

.aica-toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

input:checked + .aica-toggle-slider {
    background-color: var(--aica-primary);
}

input:checked + .aica-toggle-slider:before {
    transform: translateX(24px);
}

/* Tags Input */
.aica-tags-input-container {
    margin-bottom: 12px;
}

.aica-tags-input-field {
    display: flex;
    margin-bottom: 12px;
}

.aica-tags-input-field input {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-right: none;
}

.aica-tags-input-field button {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.aica-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.aica-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: var(--aica-border-radius);
    padding: 6px 10px;
    font-size: 13px;
    transition: var(--aica-transition);
}

.aica-tag:hover {
    background-color: #e2e8f0;
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
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid var(--aica-border);
    text-align: right;
}

/* Models Grid */
.aica-models-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.aica-model-card {
    position: relative;
    border-radius: var(--aica-border-radius);
    overflow: hidden;
    transition: var(--aica-transition);
    border: 2px solid transparent;
}

.aica-model-card.selected {
    border-color: var(--aica-primary);
}

.aica-model-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.aica-model-content {
    display: block;
    cursor: pointer;
    padding: 20px;
    background-color: white;
    border: 1px solid var(--aica-border);
    border-radius: var(--aica-border-radius);
    transition: var(--aica-transition);
    height: 100%;
}

.aica-model-card:hover .aica-model-content {
    border-color: var(--aica-primary-light);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.aica-model-card.selected .aica-model-content {
    border-color: var(--aica-primary);
    box-shadow: 0 0 0 1px var(--aica-primary);
}

.aica-model-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.aica-model-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--aica-text-light);
}

.aica-model-card.opus .aica-model-icon {
    background-color: #818cf8;
    color: white;
}

.aica-model-card.sonnet .aica-model-icon {
    background-color: #60a5fa;
    color: white;
}

.aica-model-card.haiku .aica-model-icon {
    background-color: #34d399;
    color: white;
}

.aica-model-card.instant .aica-model-icon {
    background-color: #fcd34d;
    color: #92400e;
}

.aica-model-badge {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 24px;
    font-weight: 500;
    background-color: #f1f5f9;
    color: var(--aica-text-light);
}

.aica-model-card.opus .aica-model-badge {
    background-color: #e0e7ff;
    color: #4338ca;
}

.aica-model-card.sonnet .aica-model-badge {
    background-color: #dbeafe;
    color: #1e40af;
}

.aica-model-card.haiku .aica-model-badge {
    background-color: #d1fae5;
    color: #065f46;
}

.aica-model-card.instant .aica-model-badge {
    background-color: #fef3c7;
    color: #92400e;
}

.aica-model-name {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: var(--aica-text);
}

.aica-model-desc {
    font-size: 13px;
    color: var(--aica-text-light);
    margin-bottom: 16px;
    height: 60px;
    overflow: hidden;
}

.aica-model-specs {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.aica-model-spec {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-spec-label {
    font-size: 13px;
    color: var(--aica-text-light);
}

.aica-rating-dots {
    display: flex;
    gap: 3px;
}

.aica-rating-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #e2e8f0;
}

.aica-rating-dot.filled {
    background-color: var(--aica-primary);
}

/* Template Items */
.aica-template-item {
    margin-bottom: 16px;
    border: 1px solid var(--aica-border);
    border-radius: var(--aica-border-radius);
    overflow: hidden;
}

.aica-template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background-color: #f8fafc;
    border-bottom: 1px solid var(--aica-border);
}

.aica-template-header input {
    border: none;
    background: none;
    padding: 4px 8px;
    font-weight: 500;
    width: 100%;
}

.aica-template-header input:focus {
    box-shadow: none;
    outline: none;
}

.aica-template-actions {
    display: flex;
    gap: 4px;
}

.aica-template-item textarea {
    border: none;
    border-radius: 0;
    resize: vertical;
    min-height: 80px;
}

.aica-template-item textarea:focus {
    box-shadow: none;
    outline: none;
}

/* Auto Purge Container */
.aica-auto-purge-container {
    display: flex;
    align-items: center;
    gap: 16px;
}

.aica-auto-purge-days {
    display: flex;
    align-items: center;
    gap: 8px;
}

.aica-auto-purge-days input {
    width: 80px;
    text-align: center;
}

.aica-days-label {
    color: var(--aica-text-light);
    font-size: 14px;
}

/* Field Actions */
.aica-field-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* API Test Result */
.aica-api-test-result {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.aica-api-test-result.loading {
    color: var(--aica-text-light);
}

.aica-api-test-result.success {
    color: var(--aica-success);
}

.aica-api-test-result.error {
    color: var(--aica-error);
}

.aica-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-top: 2px solid var(--aica-primary);
    border-radius: 50%;
    animation: aica-spin 1s linear infinite;
}

@keyframes aica-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media screen and (max-width: 992px) {
    .aica-settings-container {
        flex-direction: column;
    }
    
    .aica-settings-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--aica-border);
    }
    
    .aica-settings-content {
        max-width: 100%;
    }
    
    .aica-settings-tabs {
        display: flex;
        flex-wrap: wrap;
    }
    
    .aica-tab-item {
        flex: 1;
        min-width: 120px;
        justify-content: center;
        text-align: center;
        border-left: none;
        border-bottom: 3px solid transparent;
    }
    
    .aica-tab-item.active {
        border-left-color: transparent;
        border-bottom-color: var(--aica-primary);
    }
    
    .aica-tab-text {
        display: none;
    }
    
    .aica-field-label, 
    .aica-field-input {
        width: 100%;
    }
}

@media screen and (max-width: 600px) {
    .aica-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .aica-header-actions {
        width: 100%;
    }
    
    .aica-button {
        flex: 1;
        justify-content: center;
    }
    
    .aica-models-grid {
        grid-template-columns: 1fr;
    }
}

<script>
jQuery(document).ready(function($) {
    // Obsługa zakładek
    $('.aica-tab-item').on('click', function() {
        $('.aica-tab-item').removeClass('active');
        $(this).addClass('active');
        
        var tabId = $(this).data('tab');
        $('.aica-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Obsługa pola z zakresem dla tokenów
    $('#aica_max_tokens_range').on('input', function() {
        $('#aica_max_tokens').val($(this).val());
    });
    
    $('#aica_max_tokens').on('input', function() {
        $('#aica_max_tokens_range').val($(this).val());
    });
    
    // Obsługa pola z zakresem dla temperatury
    $('#aica_temperature_range').on('input', function() {
        $('#aica_temperature').val($(this).val());
    });
    
    $('#aica_temperature').on('input', function() {
        $('#aica_temperature_range').val($(this).val());
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
    
    // Obsługa zamykania powiadomień
    $('.aica-notice-dismiss').on('click', function() {
        $(this).closest('.aica-notice').slideUp(300, function() {
            $(this).remove();
        });
    });
    
    // Obsługa pola tagów z rozszerzeniami plików
    function updateExtensionsField() {
        var extensions = [];
        $('.aica-tag').each(function() {
            var ext = $(this).text().trim();
            // Usunięcie symbolu x z przycisku usuwania
            ext = ext.replace(/×|\u00D7/g, '').trim();
            extensions.push(ext);
        });
        $('#aica_allowed_file_extensions').val(extensions.join(','));
    }
    
    $('#add-extension').on('click', function() {
        var extension = $('#aica_file_extension_input').val().trim();
        
        if (extension !== '') {
            // Sprawdzenie czy rozszerzenie już istnieje
            var exists = false;
            $('.aica-tag').each(function() {
                var text = $(this).text().trim();
                text = text.replace(/×|\u00D7/g, '').trim();
                if (text === extension) {
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
    
    // Obsługa szablonów promptów
    var templateCount = $('.aica-template-item').length;
    
    $('#add-template').on('click', function() {
        var template = $('#template-item-template').html();
        template = template.replace(/__INDEX__/g, templateCount);
        
        $('#aica-templates-container').append(template);
        templateCount++;
    });
    
    $(document).on('click', '.aica-template-delete', function() {
        $(this).closest('.aica-template-item').slideUp(300, function() {
            $(this).remove();
        });
    });
    
    // Obsługa automatycznego czyszczenia historii
    $('#aica_auto_purge_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#aica_auto_purge_days').prop('disabled', false);
        } else {
            $('#aica_auto_purge_days').prop('disabled', true);
        }
    });
    
    // Testowanie połączenia z API Claude
    $('#test-claude-api').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        var resultContainer = $('#api-test-result');
        
        // Ukrycie poprzedniego wyniku
        resultContainer.removeClass('success error').addClass('loading');
        resultContainer.html('<span class="aica-spinner"></span> <?php _e('Testowanie...', 'ai-chat-assistant'); ?>');
        
        // Pobranie klucza API
        var apiKey = $('#aica_claude_api_key').val();
        
        if (apiKey === '') {
            resultContainer.removeClass('loading').addClass('error');
            resultContainer.html('<span class="dashicons dashicons-no-alt"></span> <?php _e('Wprowadź klucz API.', 'ai-chat-assistant'); ?>');
            return;
        }
        
        // Wywołanie AJAX do testowania połączenia
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: $('#aica_settings_nonce').val(),
                api_type: 'claude',
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    resultContainer.removeClass('loading').addClass('success');
                    resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> <?php _e('Połączenie z API Claude działa poprawnie.', 'ai-chat-assistant'); ?>');
                } else {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || '<?php _e('Nie udało się połączyć z API Claude.', 'ai-chat-assistant'); ?>'));
                }
            },
            error: function() {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> <?php _e('Wystąpił błąd podczas testowania połączenia.', 'ai-chat-assistant'); ?>');
            }
        });
    });
    
    // Testowanie połączenia z API GitHub
    $('#test-github-api').on('click', function() {
        var button = $(this);
        var resultContainer = $('#github-test-result');
        
        resultContainer.removeClass('success error').addClass('loading');
        resultContainer.html('<span class="aica-spinner"></span> <?php _e('Testowanie...', 'ai-chat-assistant'); ?>');
        
        var token = $('#aica_github_token').val();
        
        if (token === '') {
            resultContainer.removeClass('loading').addClass('error');
            resultContainer.html('<span class="dashicons dashicons-no-alt"></span> <?php _e('Wprowadź token GitHub.', 'ai-chat-assistant'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_test_api_connection',
                nonce: $('#aica_settings_nonce').val(),
                api_type: 'github',
                api_key: token
            },
            success: function(response) {
                if (response.success) {
                    resultContainer.removeClass('loading').addClass('success');
                    resultContainer.html('<span class="dashicons dashicons-yes-alt"></span> <?php _e('Połączenie z API GitHub działa poprawnie.', 'ai-chat-assistant'); ?>');
                } else {
                    resultContainer.removeClass('loading').addClass('error');
                    resultContainer.html('<span class="dashicons dashicons-no-alt"></span> ' + (response.data.message || '<?php _e('Nie udało się połączyć z API GitHub.', 'ai-chat-assistant'); ?>'));
                }
            },
            error: function() {
                resultContainer.removeClass('loading').addClass('error');
                resultContainer.html('<span class="dashicons dashicons-no-alt"></span> <?php _e('Wystąpił błąd podczas testowania połączenia.', 'ai-chat-assistant'); ?>');
            }
        });
    });
    
    // Testowanie GitLab i Bitbucket - podobne funkcje jak dla GitHub
    
    // Odświeżanie listy modeli
    $('#refresh-models').on('click', function() {
        var button = $(this);
        var originalText = button.html();
        
        button.html('<span class="aica-spinner"></span> <?php _e('Odświeżanie...', 'ai-chat-assistant'); ?>');
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_refresh_models',
                nonce: $('#aica_settings_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    // Odświeżenie strony, aby pokazać zaktualizowane modele
                    location.reload();
                } else {
                    button.html(originalText);
                    button.prop('disabled', false);
                    alert(response.data.message || '<?php _e('Nie udało się odświeżyć modeli.', 'ai-chat-assistant'); ?>');
                }
            },
            error: function() {
                button.html(originalText);
                button.prop('disabled', false);
                alert('<?php _e('Wystąpił błąd podczas odświeżania modeli.', 'ai-chat-assistant'); ?>');
            }
        });
    });
    
    // Walidacja formularza przed wysłaniem
    $('#aica-settings-form').on('submit', function(e) {
        var valid = true;
        var firstError = null;
        
        // Walidacja pól wg aktywnej zakładki
        var activeTab = $('.aica-tab-content.active').attr('id');
        
        if (activeTab === 'claude-settings') {
            // Walidacja klucza API Claude
            if ($('#aica_claude_api_key').val().trim() === '') {
                $('#aica_claude_api_key').addClass('error');
                valid = false;
                if (!firstError) firstError = $('#aica_claude_api_key');
            } else {
                $('#aica_claude_api_key').removeClass('error');
            }
        }
        
        // W przypadku błędów, przerwij wysyłanie i przewiń do pierwszego błędu
        if (!valid) {
            e.preventDefault();
            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 300);
                firstError.focus();
            }
        }
    });
});
</script>
