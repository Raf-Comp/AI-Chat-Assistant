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

// Wczytanie stylów i skryptów
wp_enqueue_style('aica-settings-styles', AICA_PLUGIN_URL . 'assets/css/settings.css', [], AICA_VERSION);
wp_enqueue_script('aica-settings-scripts', AICA_PLUGIN_URL . 'assets/js/settings.js', ['jquery'], AICA_VERSION, true);

// Przekazanie danych do skryptu
wp_localize_script('aica-settings-scripts', 'aica_data', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
    'i18n' => [
        'refreshing_models' => __('Odświeżanie...', 'ai-chat-assistant'),
        'loading' => __('Testowanie...', 'ai-chat-assistant')
    ]
]);
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
                                    <div class="aica-password-field">
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
                                    <div class="aica-password-field">
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
                                    <div class="aica-password-field">
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
                                    <div class="aica-password-field">
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