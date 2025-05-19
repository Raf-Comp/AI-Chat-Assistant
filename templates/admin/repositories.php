<?php
/**
 * Szablon strony repozytoriów
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
        <h1><?php _e('Zarządzanie repozytoriami', 'ai-chat-assistant'); ?></h1>
        <div class="aica-header-actions">
            <div class="aica-search-container">
                <input type="text" id="aica-search-repositories" placeholder="<?php _e('Szukaj repozytoriów...', 'ai-chat-assistant'); ?>" />
                <button type="button" class="aica-search-button">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>
            <button type="button" class="button button-primary aica-add-repository-button">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Dodaj repozytorium', 'ai-chat-assistant'); ?>
            </button>
        </div>
    </div>

    <div class="aica-repositories-container">
        <div class="aica-sidebar">
            <div class="aica-repo-sources">
                <h3><?php _e('Źródła', 'ai-chat-assistant'); ?></h3>
                <ul class="aica-sources-list">
                    <li class="aica-source-item active" data-source="saved">
                        <span class="dashicons dashicons-admin-home"></span>
                        <span class="aica-source-name"><?php _e('Zapisane repozytoria', 'ai-chat-assistant'); ?></span>
                        <span class="aica-source-count"><?php echo count($saved_repositories); ?></span>
                    </li>
                    <?php if (!empty($github_token)): ?>
                    <li class="aica-source-item" data-source="github">
                        <span class="dashicons dashicons-code-standards"></span>
                        <span class="aica-source-name">GitHub</span>
                        <span class="aica-source-count"><?php echo count($github_repos); ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($gitlab_token)): ?>
                    <li class="aica-source-item" data-source="gitlab">
                        <span class="dashicons dashicons-editor-code"></span>
                        <span class="aica-source-name">GitLab</span>
                        <span class="aica-source-count"><?php echo count($gitlab_repos); ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($bitbucket_username) && !empty($bitbucket_app_password)): ?>
                    <li class="aica-source-item" data-source="bitbucket">
                        <span class="dashicons dashicons-cloud"></span>
                        <span class="aica-source-name">Bitbucket</span>
                        <span class="aica-source-count"><?php echo count($bitbucket_repos); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="aica-repo-filters">
                <h3><?php _e('Filtry', 'ai-chat-assistant'); ?></h3>
                <div class="aica-filter-group">
                    <h4><?php _e('Sortowanie', 'ai-chat-assistant'); ?></h4>
                    <select class="aica-sort-select">
                        <option value="name_asc"><?php _e('Nazwa (A-Z)', 'ai-chat-assistant'); ?></option>
                        <option value="name_desc"><?php _e('Nazwa (Z-A)', 'ai-chat-assistant'); ?></option>
                        <option value="date_desc"><?php _e('Najnowsze', 'ai-chat-assistant'); ?></option>
                        <option value="date_asc"><?php _e('Najstarsze', 'ai-chat-assistant'); ?></option>
                    </select>
                </div>
                
                <div class="aica-filter-group" id="aica-language-filter" style="display: none;">
                    <h4><?php _e('Język', 'ai-chat-assistant'); ?></h4>
                    <div class="aica-filter-items">
                        <label class="aica-filter-item">
                            <input type="checkbox" value="php" class="aica-language-checkbox"> PHP
                        </label>
                        <label class="aica-filter-item">
                            <input type="checkbox" value="javascript" class="aica-language-checkbox"> JavaScript
                        </label>
                        <label class="aica-filter-item">
                            <input type="checkbox" value="python" class="aica-language-checkbox"> Python
                        </label>
                        <label class="aica-filter-item">
                            <input type="checkbox" value="ruby" class="aica-language-checkbox"> Ruby
                        </label>
                        <label class="aica-filter-item">
                            <input type="checkbox" value="java" class="aica-language-checkbox"> Java
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="aica-content">
            <!-- Saved Repositories tab -->
            <div class="aica-repos-tab active" id="saved-repositories">
                <?php if (empty($saved_repositories)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-code-standards"></span>
                    </div>
                    <h2><?php _e('Nie masz zapisanych repozytoriów', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Dodaj repozytoria z serwisów GitHub, GitLab lub Bitbucket, aby ułatwić sobie pracę z kodem podczas rozmów z Claude.', 'ai-chat-assistant'); ?></p>
                    <button type="button" class="button button-primary aica-add-repository-button">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Dodaj repozytorium', 'ai-chat-assistant'); ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="aica-repositories-grid">
                    <?php foreach ($saved_repositories as $repo): ?>
                        <div class="aica-repository-card" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <?php
                                    $icon_class = 'dashicons-code-standards';
                                    switch ($repo['repo_type']) {
                                        case 'github':
                                            $icon_class = 'dashicons-code-standards';
                                            break;
                                        case 'gitlab':
                                            $icon_class = 'dashicons-editor-code';
                                            break;
                                        case 'bitbucket':
                                            $icon_class = 'dashicons-cloud';
                                            break;
                                    }
                                    ?>
                                    <span class="dashicons <?php echo $icon_class; ?>"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['repo_name']); ?></h3>
                                    <span class="aica-repo-owner"><?php echo esc_html($repo['repo_owner']); ?></span>
                                </div>
                                <div class="aica-repo-actions">
                                    <div class="aica-dropdown">
                                        <button type="button" class="aica-dropdown-toggle" aria-label="<?php _e('Menu', 'ai-chat-assistant'); ?>">
                                            <span class="dashicons dashicons-ellipsis"></span>
                                        </button>
                                        <div class="aica-dropdown-menu">
                                            <a href="<?php echo esc_url($repo['repo_url']); ?>" target="_blank" class="aica-dropdown-item">
                                                <span class="dashicons dashicons-external"></span>
                                                <?php _e('Otwórz w przeglądarce', 'ai-chat-assistant'); ?>
                                            </a>
                                            <a href="#" class="aica-dropdown-item aica-browse-repo" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-search"></span>
                                                <?php _e('Przeglądaj pliki', 'ai-chat-assistant'); ?>
                                            </a>
                                            <a href="#" class="aica-dropdown-item aica-refresh-repo" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-update"></span>
                                                <?php _e('Odśwież metadane', 'ai-chat-assistant'); ?>
                                            </a>
                                            <div class="aica-dropdown-divider"></div>
                                            <a href="#" class="aica-dropdown-item aica-delete-repo text-danger" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                                <?php _e('Usuń', 'ai-chat-assistant'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="aica-repo-body">
                                <div class="aica-repo-meta">
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="aica-meta-label"><?php _e('Typ:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value">
                                            <?php 
                                            switch ($repo['repo_type']) {
                                                case 'github':
                                                    echo 'GitHub';
                                                    break;
                                                case 'gitlab':
                                                    echo 'GitLab';
                                                    break;
                                                case 'bitbucket':
                                                    echo 'Bitbucket';
                                                    break;
                                                default:
                                                    echo esc_html($repo['repo_type']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span class="aica-meta-label"><?php _e('Dodano:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($repo['created_at']))); ?></span>
                                    </div>
                                </div>
                                <div class="aica-repo-description">
                                    <?php if (!empty($repo['repo_description'])): ?>
                                        <p><?php echo esc_html($repo['repo_description']); ?></p>
                                    <?php else: ?>
                                        <p class="aica-no-description"><?php _e('Brak opisu', 'ai-chat-assistant'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="aica-repo-footer">
                                <button type="button" class="button aica-browse-button" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                    <span class="dashicons dashicons-search"></span>
                                    <?php _e('Przeglądaj pliki', 'ai-chat-assistant'); ?>
                                </button>
                                <?php 
                                $delete_url = add_query_arg([
                                    'action' => 'delete',
                                    'repo_id' => $repo['id'],
                                    '_wpnonce' => wp_create_nonce('delete_repository')
                                ]);
                                ?>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-link-delete aica-delete-button" data-repo-id="<?php echo esc_attr($repo['id']); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- GitHub tab -->
            <div class="aica-repos-tab" id="github-repositories">
                <?php if (empty($github_token)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-code-standards"></span>
                    </div>
                    <h2><?php _e('Połączenie z GitHub nie jest skonfigurowane', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Aby korzystać z repozytoriów GitHub, skonfiguruj token dostępu w ustawieniach.', 'ai-chat-assistant'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Przejdź do ustawień', 'ai-chat-assistant'); ?>
                    </a>
                </div>
                <?php elseif (empty($github_repos)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-code-standards"></span>
                    </div>
                    <h2><?php _e('Nie znaleziono repozytoriów GitHub', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Nie znaleziono repozytoriów GitHub dla skonfigurowanego tokenu dostępu.', 'ai-chat-assistant'); ?></p>
                </div>
                <?php else: ?>
                <div class="aica-repositories-grid">
                    <?php foreach ($github_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-code-standards"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <span class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></span>
                                </div>
                            </div>
                            <div class="aica-repo-body">
                                <div class="aica-repo-meta">
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="aica-meta-label"><?php _e('Typ:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value">GitHub</span>
                                    </div>
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($repo['updated_at']))); ?></span>
                                    </div>
                                </div>
                                <div class="aica-repo-description">
                                    <?php if (!empty($repo['description'])): ?>
                                        <p><?php echo esc_html($repo['description']); ?></p>
                                    <?php else: ?>
                                        <p class="aica-no-description"><?php _e('Brak opisu', 'ai-chat-assistant'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="aica-repo-footer">
                                <form method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="github">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    <button type="submit" name="aica_add_repository" class="button button-primary aica-add-button">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                    </button>
                                    <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="button aica-external-button">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- GitLab tab -->
            <div class="aica-repos-tab" id="gitlab-repositories">
                <?php if (empty($gitlab_token)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-editor-code"></span>
                    </div>
                    <h2><?php _e('Połączenie z GitLab nie jest skonfigurowane', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Aby korzystać z repozytoriów GitLab, skonfiguruj token dostępu w ustawieniach.', 'ai-chat-assistant'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Przejdź do ustawień', 'ai-chat-assistant'); ?>
                    </a>
                </div>
                <?php elseif (empty($gitlab_repos)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-editor-code"></span>
                    </div>
                    <h2><?php _e('Nie znaleziono repozytoriów GitLab', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Nie znaleziono repozytoriów GitLab dla skonfigurowanego tokenu dostępu.', 'ai-chat-assistant'); ?></p>
                </div>
                <?php else: ?>
                <div class="aica-repositories-grid">
                    <?php foreach ($gitlab_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-editor-code"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <span class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></span>
                                </div>
                            </div>
                            <div class="aica-repo-body">
                                <div class="aica-repo-meta">
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="aica-meta-label"><?php _e('Typ:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value">GitLab</span>
                                    </div>
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($repo['updated_at']))); ?></span>
                                    </div>
                                </div>
                                <div class="aica-repo-description">
                                    <?php if (!empty($repo['description'])): ?>
                                        <p><?php echo esc_html($repo['description']); ?></p>
                                    <?php else: ?>
                                        <p class="aica-no-description"><?php _e('Brak opisu', 'ai-chat-assistant'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="aica-repo-footer">
                                <form method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="gitlab">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    <button type="submit" name="aica_add_repository" class="button button-primary aica-add-button">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                    </button>
                                    <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="button aica-external-button">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Bitbucket tab -->
            <div class="aica-repos-tab" id="bitbucket-repositories">
                <?php if (empty($bitbucket_username) || empty($bitbucket_app_password)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <h2><?php _e('Połączenie z Bitbucket nie jest skonfigurowane', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Aby korzystać z repozytoriów Bitbucket, skonfiguruj dane dostępowe w ustawieniach.', 'ai-chat-assistant'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-chat-assistant-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php _e('Przejdź do ustawień', 'ai-chat-assistant'); ?>
                    </a>
                </div>
                <?php elseif (empty($bitbucket_repos)): ?>
                <div class="aica-empty-state">
                    <div class="aica-empty-icon">
                        <span class="dashicons dashicons-cloud"></span>
                    </div>
                    <h2><?php _e('Nie znaleziono repozytoriów Bitbucket', 'ai-chat-assistant'); ?></h2>
                    <p><?php _e('Nie znaleziono repozytoriów Bitbucket dla skonfigurowanych danych dostępowych.', 'ai-chat-assistant'); ?></p>
                </div>
                <?php else: ?>
                <div class="aica-repositories-grid">
                    <?php foreach ($bitbucket_repos as $repo): ?>
                        <div class="aica-repository-card">
                            <div class="aica-repo-header">
                                <div class="aica-repo-icon">
                                    <span class="dashicons dashicons-cloud"></span>
                                </div>
                                <div class="aica-repo-title">
                                    <h3><?php echo esc_html($repo['name']); ?></h3>
                                    <span class="aica-repo-owner"><?php echo esc_html($repo['owner']); ?></span>
                                </div>
                            </div>
                            <div class="aica-repo-body">
                                <div class="aica-repo-meta">
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="aica-meta-label"><?php _e('Typ:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value">Bitbucket</span>
                                    </div>
                                    <div class="aica-meta-item">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <span class="aica-meta-label"><?php _e('Aktualizacja:', 'ai-chat-assistant'); ?></span>
                                        <span class="aica-meta-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($repo['updated_at']))); ?></span>
                                    </div>
                                </div>
                                <div class="aica-repo-description">
                                    <?php if (!empty($repo['description'])): ?>
                                        <p><?php echo esc_html($repo['description']); ?></p>
                                    <?php else: ?>
                                        <p class="aica-no-description"><?php _e('Brak opisu', 'ai-chat-assistant'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="aica-repo-footer">
                                <form method="post" action="">
                                    <?php wp_nonce_field('aica_repository_nonce'); ?>
                                    <input type="hidden" name="repo_type" value="bitbucket">
                                    <input type="hidden" name="repo_name" value="<?php echo esc_attr($repo['name']); ?>">
                                    <input type="hidden" name="repo_owner" value="<?php echo esc_attr($repo['owner']); ?>">
                                    <input type="hidden" name="repo_url" value="<?php echo esc_attr($repo['url']); ?>">
                                    <input type="hidden" name="repo_external_id" value="<?php echo esc_attr($repo['id']); ?>">
                                    <input type="hidden" name="repo_description" value="<?php echo esc_attr($repo['description']); ?>">
                                    <button type="submit" name="aica_add_repository" class="button button-primary aica-add-button">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e('Dodaj', 'ai-chat-assistant'); ?>
                                    </button>
                                    <a href="<?php echo esc_url($repo['url']); ?>" target="_blank" class="button aica-external-button">
                                        <span class="dashicons dashicons-external"></span>
                                    </a>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- File Browser Modal -->
    <div id="aica-file-browser-modal" class="aica-modal" style="display: none;">
        <div class="aica-modal-content">
            <div class="aica-modal-header">
                <h2><?php _e('Przeglądarka plików', 'ai-chat-assistant'); ?></h2>
                <button type="button" class="aica-modal-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="aica-modal-body">
                <div class="aica-file-browser-container">
                    <div class="aica-file-browser-sidebar">
                        <div class="aica-file-browser-header">
                            <div class="aica-repo-info">
                                <span class="aica-repo-icon"><span class="dashicons dashicons-code-standards"></span></span>
                                <span class="aica-repo-name"></span>
                            </div>
                            <div class="aica-branch-select-container">
                                <select id="aica-branch-select">
                                    <option value="main"><?php _e('main', 'ai-chat-assistant'); ?></option>
                                    <option value="master"><?php _e('master', 'ai-chat-assistant'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="aica-file-search-container">
                            <input type="text" id="aica-file-search" placeholder="<?php _e('Szukaj plików...', 'ai-chat-assistant'); ?>" />
                            <button type="button" class="aica-file-search-button">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                        <div class="aica-file-tree-container">
                            <div class="aica-loading-files">
                                <div class="aica-loading-spinner"></div>
                                <p><?php _e('Ładowanie plików...', 'ai-chat-assistant'); ?></p>
                            </div>
                            <div id="aica-file-tree"></div>
                        </div>
                    </div>
                    <div class="aica-file-content-container">
                        <div class="aica-file-content-header">
                            <div class="aica-file-path-container">
                                <span class="aica-file-path"></span>
                            </div>
                            <div class="aica-file-actions">
                                <button type="button" class="button aica-copy-file-button" title="<?php _e('Kopiuj zawartość', 'ai-chat-assistant'); ?>">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </button>
                                <button type="button" class="button aica-use-in-chat-button" title="<?php _e('Użyj w czacie', 'ai-chat-assistant'); ?>">
                                    <span class="dashicons dashicons-format-chat"></span>
                                </button>
                            </div>
                        </div>
                        <div class="aica-file-content-body">
                            <div class="aica-loading-content">
                                <div class="aica-loading-spinner"></div>
                                <p><?php _e('Ładowanie zawartości...', 'ai-chat-assistant'); ?></p>
                            </div>
                            <pre id="aica-file-content"><code></code></pre>
                        </div>
                    </div>
                </div>
            </div>
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
                <p><?php _e('Czy na pewno chcesz usunąć to repozytorium? Tej operacji nie można cofnąć.', 'ai-chat-assistant'); ?></p>
            </div>
            <div class="aica-dialog-footer">
                <button type="button" class="button button-secondary aica-dialog-cancel"><?php _e('Anuluj', 'ai-chat-assistant'); ?></button>
                <button type="button" class="button button-primary aica-dialog-confirm aica-delete-confirm"><?php _e('Usuń', 'ai-chat-assistant'); ?></button>
            </div>
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

.aica-add-repository-button {
    display: flex !important;
    align-items: center;
    gap: 5px;
}

/* Repositories Container */
.aica-repositories-container {
    display: flex;
    background: #fff;
    border-radius: 8px;
    box-shadow: var(--aica-card-shadow);
    overflow: hidden;
}

/* Sidebar */
.aica-sidebar {
    width: 250px;
    background-color: var(--aica-bg-light);
    border-right: 1px solid var(--aica-border);
    padding: 20px 0;
}

.aica-repo-sources,
.aica-repo-filters {
    padding: 0 20px;
    margin-bottom: 25px;
}

.aica-repo-sources h3,
.aica-repo-filters h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 15px 0;
    padding: 0;
    text-transform: uppercase;
    color: var(--aica-text-light);
}

.aica-filter-group {
    margin-bottom: 20px;
}

.aica-filter-group h4 {
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 10px 0;
    padding: 0;
}

.aica-sort-select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 13px;
}

.aica-filter-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.aica-filter-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

/* Source List */
.aica-sources-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.aica-source-item {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    margin-bottom: 5px;
}

.aica-source-item:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.aica-source-item.active {
    background-color: var(--aica-primary-light);
    color: #fff;
}

.aica-source-item .dashicons {
    margin-right: 10px;
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.aica-source-name {
    flex: 1;
    font-size: 14px;
}

.aica-source-count {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 12px;
    min-width: 25px;
    text-align: center;
}

.aica-source-item.active .aica-source-count {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Content */
.aica-content {
    flex: 1;
    padding: 20px;
    overflow: auto;
}

.aica-repos-tab {
    display: none;
}

.aica-repos-tab.active {
    display: block;
}

/* Empty state */
.aica-empty-state {
    text-align: center;
    padding: 60px 20px;
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

/* Repository Grid */
.aica-repositories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

/* Repository Card */
.aica-repository-card {
    background: #fff;
    border: 1px solid var(--aica-border);
    border-radius: 6px;
    overflow: hidden;
    transition: box-shadow 0.3s ease, transform 0.2s ease;
}

.aica-repository-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.aica-repo-header {
    padding: 16px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--aica-border);
}

.aica-repo-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--aica-bg-light);
    border-radius: 6px;
    margin-right: 12px;
}

.aica-repo-icon .dashicons {
    font-size: 20px;
    color: var(--aica-primary);
}

.aica-repo-title {
    flex: 1;
}

.aica-repo-title h3 {
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
}

.aica-repo-owner {
    font-size: 12px;
    color: var(--aica-text-light);
}

.aica-repo-actions {
    margin-left: 10px;
}

/* Dropdown Menu */
.aica-dropdown {
    position: relative;
}

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

.aica-dropdown-toggle:hover {
    background-color: #f0f0f0;
    color: var(--aica-text);
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

/* Repository Body */
.aica-repo-body {
    padding: 16px;
}

.aica-repo-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 12px;
}

.aica-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--aica-text-light);
}

.aica-meta-label {
    font-weight: 600;
}

.aica-repo-description {
    font-size: 14px;
    color: var(--aica-text);
    line-height: 1.5;
    margin-bottom: 0;
}

.aica-repo-description p {
    margin: 0;
}

.aica-no-description {
    color: var(--aica-text-light);
    font-style: italic;
}

/* Repository Footer */
.aica-repo-footer {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-top: 1px solid var(--aica-border);
    background-color: var(--aica-bg-light);
}

.aica-browse-button {
    display: flex !important;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.aica-delete-button {
    margin-left: 8px;
    color: var(--aica-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 5px;
    border-radius: 3px;
    transition: color 0.2s ease, background-color 0.2s ease;
}

.aica-delete-button:hover {
    color: var(--aica-error);
    background-color: var(--aica-error-light);
}

.aica-add-button {
    display: flex !important;
    align-items: center;
    gap: 6px;
    flex: 1;
}

.aica-external-button {
    margin-left: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 5px;
}

/* Modal */
.aica-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.aica-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    width: calc(100% - 40px);
    max-width: 1200px;
    max-height: calc(100vh - 40px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.aica-modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-modal-header h2 {
    margin: 0;
    padding: 0;
    font-size: 18px;
    font-weight: 600;
}

.aica-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    color: var(--aica-text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.aica-modal-close:hover {
    background-color: #f0f0f0;
    color: var(--aica-text);
}

.aica-modal-body {
    flex: 1;
    overflow: auto;
}

/* File Browser */
.aica-file-browser-container {
    display: flex;
    height: 70vh;
}

.aica-file-browser-sidebar {
    width: 300px;
    border-right: 1px solid var(--aica-border);
    display: flex;
    flex-direction: column;
}

.aica-file-browser-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.aica-repo-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.aica-repo-name {
    font-weight: 600;
    font-size: 14px;
}

.aica-branch-select-container {
    width: 100%;
}

#aica-branch-select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 13px;
}

.aica-file-search-container {
    padding: 12px 16px;
    border-bottom: 1px solid var(--aica-border);
    position: relative;
}

#aica-file-search {
    width: 100%;
    padding: 8px 36px 8px 12px;
    border: 1px solid var(--aica-border);
    border-radius: 4px;
    font-size: 13px;
}

.aica-file-search-button {
    position: absolute;
    right: 16px;
    top: 12px;
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

.aica-file-tree-container {
    flex: 1;
    overflow: auto;
    padding: 16px;
}

.aica-loading-files {
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
    width: 24px;
    height: 24px;
    animation: aica-spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes aica-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* File Tree */
#aica-file-tree {
    font-size: 13px;
}

.aica-file-tree-item {
    margin-bottom: 5px;
}

.aica-file-tree-folder {
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.2s ease;
}

.aica-file-tree-folder:hover {
    background-color: #f0f0f0;
}

.aica-file-tree-file {
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background-color 0.2s ease;
}

.aica-file-tree-file:hover {
    background-color: #f0f0f0;
}

.aica-file-tree-children {
    margin-left: 20px;
    display: none;
}

.aica-file-tree-folder.expanded .aica-file-tree-children {
    display: block;
}

/* File Content */
.aica-file-content-container {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.aica-file-content-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--aica-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aica-file-path {
    font-family: monospace;
    font-size: 14px;
}

.aica-file-actions {
    display: flex;
    gap: 8px;
}

.aica-file-content-body {
    flex: 1;
    overflow: auto;
    padding: 0;
    position: relative;
}

.aica-loading-content {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

#aica-file-content {
    margin: 0;
    padding: 16px;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
    max-height: 100%;
    overflow: auto;
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

/* Responsive Design */
@media screen and (max-width: 992px) {
    .aica-repositories-container {
        flex-direction: column;
    }
    
    .aica-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--aica-border);
        padding: 15px;
    }
    
    .aica-sources-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .aica-source-item {
        flex: 1;
        min-width: 120px;
    }
    
    .aica-repositories-grid {
        grid-template-columns: 1fr;
    }
    
    .aica-file-browser-container {
        flex-direction: column;
        height: 80vh;
    }
    
    .aica-file-browser-sidebar {
        width: 100%;
        height: 40%;
        border-right: none;
        border-bottom: 1px solid var(--aica-border);
    }
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
    
    // Przełączanie zakładek dla różnych źródeł repozytoriów
    $('.aica-source-item').on('click', function() {
        const source = $(this).data('source');
        
        // Aktywacja przycisku źródła
        $('.aica-source-item').removeClass('active');
        $(this).addClass('active');
        
        // Aktywacja odpowiedniej zakładki
        $('.aica-repos-tab').removeClass('active');
        $('#' + source + '-repositories').addClass('active');
        
        // Pokaż/ukryj filtry języka dla zakładki "saved"
        if (source === 'saved') {
            $('#aica-language-filter').show();
        } else {
            $('#aica-language-filter').hide();
        }
    });
    
    // Wyszukiwanie repozytoriów
    $('#aica-search-repositories').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        // Wyszukiwanie w aktywnej zakładce
        const activeTab = $('.aica-repos-tab.active');
        
        // Jeśli nie ma wyszukiwanego tekstu, pokaż wszystkie repozytoria
        if (searchTerm === '') {
            activeTab.find('.aica-repository-card').show();
            return;
        }
        
        // Przeszukaj karty repozytoriów
        activeTab.find('.aica-repository-card').each(function() {
            const repoName = $(this).find('.aica-repo-title h3').text().toLowerCase();
            const repoOwner = $(this).find('.aica-repo-owner').text().toLowerCase();
            const repoDescription = $(this).find('.aica-repo-description p').text().toLowerCase();
            
            // Sprawdź, czy tekst pasuje do nazwy, właściciela lub opisu
            if (repoName.includes(searchTerm) || repoOwner.includes(searchTerm) || repoDescription.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Sortowanie repozytoriów
    $('.aica-sort-select').on('change', function() {
        const sortValue = $(this).val();
        const activeTab = $('.aica-repos-tab.active');
        const repoCards = activeTab.find('.aica-repository-card').toArray();
        
        // Sortowanie kart repozytoriów
        repoCards.sort(function(a, b) {
            const aName = $(a).find('.aica-repo-title h3').text();
            const bName = $(b).find('.aica-repo-title h3').text();
            const aDate = $(a).find('.aica-meta-value:contains("Dodano")').next().text();
            const bDate = $(b).find('.aica-meta-value:contains("Dodano")').next().text();
            
            switch (sortValue) {
                case 'name_asc':
                    return aName.localeCompare(bName);
                case 'name_desc':
                    return bName.localeCompare(aName);
                case 'date_desc':
                    return new Date(bDate) - new Date(aDate);
                case 'date_asc':
                    return new Date(aDate) - new Date(bDate);
                default:
                    return 0;
            }
        });
        
        // Dodaj posortowane karty z powrotem do kontenera
        const repoGrid = activeTab.find('.aica-repositories-grid');
        repoGrid.empty();
        $.each(repoCards, function(index, card) {
            repoGrid.append(card);
        });
    });
    
    // Filtrowanie po języku
    $('.aica-language-checkbox').on('change', function() {
        filterRepositoriesByLanguage();
    });
    
    function filterRepositoriesByLanguage() {
        const activeTab = $('.aica-repos-tab.active');
        const selectedLanguages = [];
        
        // Zbierz zaznaczone języki
        $('.aica-language-checkbox:checked').each(function() {
            selectedLanguages.push($(this).val());
        });
        
        // Jeśli nie ma zaznaczonych języków, pokaż wszystkie repozytoria
        if (selectedLanguages.length === 0) {
            activeTab.find('.aica-repository-card').show();
            return;
        }
        
        // Przeszukaj karty repozytoriów
        activeTab.find('.aica-repository-card').each(function() {
            const repoLanguages = $(this).data('languages') || '';
            const repoLanguagesArray = repoLanguages.split(',');
            
            // Sprawdź, czy repozytorium ma któryś z zaznaczonych języków
            let shouldShow = false;
            for (let i = 0; i < selectedLanguages.length; i++) {
                if (repoLanguagesArray.includes(selectedLanguages[i])) {
                    shouldShow = true;
                    break;
                }
            }
            
            if (shouldShow) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Otwieranie przeglądarki plików
    $(document).on('click', '.aica-browse-repo, .aica-browse-button', function(e) {
        e.preventDefault();
        const repoId = $(this).data('repo-id');
        
        // Pokaż modal przeglądarki plików
        $('#aica-file-browser-modal').show();
        
        // Załaduj repozytorium
        loadRepository(repoId);
    });
    
    // Zamykanie modalnego okna przeglądarki plików
    $('.aica-modal-close').on('click', function() {
        $('#aica-file-browser-modal').hide();
    });
    
    // Zamykanie modalnego okna po kliknięciu poza nim
    $('#aica-file-browser-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Funkcja ładująca repozytorium w przeglądarce plików
    function loadRepository(repoId) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        $('#aica-file-tree').empty();
        
        // Pobierz dane repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_details',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    const repo = response.data.repository;
                    
                    // Ustaw informacje o repozytorium
                    $('.aica-repo-name').text(repo.repo_name);
                    
                    // Ustaw ikonę repozytorium
                    let iconClass = 'dashicons-code-standards';
                    switch (repo.repo_type) {
                        case 'github':
                            iconClass = 'dashicons-code-standards';
                            break;
                        case 'gitlab':
                            iconClass = 'dashicons-editor-code';
                            break;
                        case 'bitbucket':
                            iconClass = 'dashicons-cloud';
                            break;
                    }
                    $('.aica-repo-icon .dashicons').attr('class', 'dashicons ' + iconClass);
                    
                    // Załaduj strukturę plików
                    loadFileStructure(repoId);
                } else {
                    alert(response.data.message || aica_repos.i18n.load_error);
                    $('#aica-file-browser-modal').hide();
                }
            },
            error: function() {
                alert(aica_repos.i18n.load_error);
                $('#aica-file-browser-modal').hide();
            }
        });
    }
    
    // Funkcja ładująca strukturę plików
    function loadFileStructure(repoId, path = '') {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-files').show();
        
        // Pobierz strukturę plików
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_repository_files',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj wskaźnik ładowania
                    $('.aica-loading-files').hide();
                    
                    // Budowanie drzewa plików
                    if (path === '') {
                        // Czyszczenie drzewa, jeśli ładujemy korzeń
                        $('#aica-file-tree').empty();
                        buildFileTree(response.data.files, $('#aica-file-tree'), repoId);
                    } else {
                        // Dodawanie podfolderów do istniejącego drzewa
                        const folderItem = $('#aica-file-tree').find('[data-path="' + path + '"]').parent();
                        const childrenContainer = folderItem.children('.aica-file-tree-children');
                        
                        // Czyszczenie kontenera, jeśli już istnieje
                        childrenContainer.empty();
                        
                        // Budowanie poddrzewa
                        buildFileTree(response.data.files, childrenContainer, repoId);
                        
                        // Rozwinięcie folderu
                        folderItem.children('.aica-file-tree-folder').addClass('expanded');
                        childrenContainer.show();
                    }
                } else {
                    alert(response.data.message || aica_repos.i18n.load_error);
                }
            },
            error: function() {
                $('.aica-loading-files').hide();
                alert(aica_repos.i18n.load_error);
            }
        });
    }
    
    // Funkcja budująca drzewo plików
    function buildFileTree(files, container, repoId) {
        // Sortowanie: najpierw foldery, potem pliki, alfabetycznie
        files.sort(function(a, b) {
            if (a.type !== b.type) {
                return a.type === 'dir' ? -1 : 1;
            }
            return a.name.localeCompare(b.name);
        });
        
        // Dodawanie elementów do drzewa
        $.each(files, function(index, file) {
            if (file.type === 'dir') {
                // Element folderu
                const folderItem = $('<div class="aica-file-tree-item"></div>');
                const folderHeader = $('<div class="aica-file-tree-folder" data-path="' + file.path + '"></div>');
                folderHeader.append('<span class="dashicons dashicons-category"></span>');
                folderHeader.append('<span class="aica-file-name">' + file.name + '</span>');
                
                // Dodanie kontenera dla dzieci
                const childrenContainer = $('<div class="aica-file-tree-children"></div>');
                
                // Dodanie elementów do kontenera
                folderItem.append(folderHeader);
                folderItem.append(childrenContainer);
                container.append(folderItem);
                
                // Obsługa kliknięcia folderu
                folderHeader.on('click', function() {
                    const path = $(this).data('path');
                    
                    // Jeśli folder ma już załadowane dzieci, po prostu rozwiń/zwiń
                    if (childrenContainer.children().length > 0) {
                        $(this).toggleClass('expanded');
                        childrenContainer.slideToggle(200);
                    } else {
                        // Załaduj zawartość folderu
                        loadFileStructure(repoId, path);
                    }
                });
            } else {
                // Element pliku
                const fileItem = $('<div class="aica-file-tree-item"></div>');
                const fileLink = $('<div class="aica-file-tree-file" data-path="' + file.path + '"></div>');
                
                // Wybierz ikonę na podstawie rozszerzenia pliku
                let fileIcon = 'dashicons-media-default';
                const fileExt = file.name.split('.').pop().toLowerCase();
                
                switch (fileExt) {
                    case 'php':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'js':
                    case 'jsx':
                    case 'ts':
                    case 'tsx':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'css':
                    case 'scss':
                    case 'less':
                        fileIcon = 'dashicons-admin-customizer';
                        break;
                    case 'html':
                    case 'htm':
                        fileIcon = 'dashicons-editor-code';
                        break;
                    case 'md':
                    case 'txt':
                        fileIcon = 'dashicons-media-text';
                        break;
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif':
                    case 'svg':
                        fileIcon = 'dashicons-format-image';
                        break;
                    case 'json':
                    case 'xml':
                    case 'yml':
                    case 'yaml':
                        fileIcon = 'dashicons-media-code';
                        break;
                }
                
                fileLink.append('<span class="dashicons ' + fileIcon + '"></span>');
                fileLink.append('<span class="aica-file-name">' + file.name + '</span>');
                
                fileItem.append(fileLink);
                container.append(fileItem);
                
                // Obsługa kliknięcia pliku
                fileLink.on('click', function() {
                    const path = $(this).data('path');
                    loadFileContent(repoId, path);
                    
                    // Zaznaczenie aktywnego pliku
                    $('.aica-file-tree-file').removeClass('active');
                    $(this).addClass('active');
                });
            }
        });
    }
    
    // Funkcja ładująca zawartość pliku
    function loadFileContent(repoId, path) {
        // Pokaż wskaźnik ładowania
        $('.aica-loading-content').show();
        
        // Ustaw ścieżkę pliku
        $('.aica-file-path').text(path);
        
        // Wyczyść zawartość
        $('#aica-file-content code').empty();
        
        // Pobierz zawartość pliku
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_get_file_content',
                nonce: aica_repos.nonce,
                repo_id: repoId,
                path: path
            },
            success: function(response) {
                // Ukryj wskaźnik ładowania
                $('.aica-loading-content').hide();
                
                if (response.success) {
                    // Wyświetl zawartość pliku
                    const content = response.data.content;
                    const language = response.data.language || '';
                    
                    // Aktualizacja zawartości
                    $('#aica-file-content code').text(content);
                    
                    // Dodaj klasę języka do elementu code, jeśli istnieje
                    if (language) {
                        $('#aica-file-content code').attr('class', 'language-' + language);
                    }
                    
                    // Podświetlanie składni, jeśli dostępne
                    if (typeof Prism !== 'undefined') {
                        Prism.highlightElement($('#aica-file-content code')[0]);
                    }
                    
                    // Zapisz aktualną ścieżkę i zawartość dla późniejszego użycia
                    $('#aica-file-content').data('path', path);
                    $('#aica-file-content').data('content', content);
                } else {
                    // Wyświetl komunikat o błędzie
                    $('#aica-file-content code').text(response.data.message || aica_repos.i18n.load_file_error);
                }
            },
            error: function() {
                // Ukryj wskaźnik ładowania
                $('.aica-loading-content').hide();
                
                // Wyświetl komunikat o błędzie
                $('#aica-file-content code').text(aica_repos.i18n.load_file_error);
            }
        });
    }
    
    // Kopiowanie zawartości pliku
    $('.aica-copy-file-button').on('click', function() {
        const content = $('#aica-file-content').data('content');
        
        if (content) {
            // Kopiowanie do schowka
            const tempTextarea = $('<textarea>').val(content).appendTo('body').select();
            document.execCommand('copy');
            tempTextarea.remove();
            
            // Informacja o skopiowaniu
            alert(aica_repos.i18n.copy_success);
        }
    });
    
    // Używanie pliku w czacie
    $('.aica-use-in-chat-button').on('click', function() {
        const path = $('#aica-file-content').data('path');
        const content = $('#aica-file-content').data('content');
        
        if (path && content) {
            // Przekierowanie do czatu z plikiem
            window.location.href = aica_repos.chat_url + '&file_path=' + encodeURIComponent(path) + '&file_content=' + encodeURIComponent(content);
        }
    });
    
    // Wyszukiwanie plików
    $('#aica-file-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm.length < 2) {
            return;
        }
        
        // Podświetlanie pasujących plików
        $('.aica-file-tree-file').each(function() {
            const fileName = $(this).find('.aica-file-name').text().toLowerCase();
            
            if (fileName.includes(searchTerm)) {
                $(this).addClass('aica-file-match');
                
                // Rozwiń rodzica
                $(this).parents('.aica-file-tree-children').each(function() {
                    $(this).show();
                    $(this).prev('.aica-file-tree-folder').addClass('expanded');
                });
            } else {
                $(this).removeClass('aica-file-match');
            }
        });
    });
    
    // Zmiana gałęzi
    $('#aica-branch-select').on('change', function() {
        const branch = $(this).val();
        const repoId = $('#aica-file-tree').data('repo-id');
        
        if (repoId) {
            // Załaduj strukturę plików dla wybranej gałęzi
            loadFileStructure(repoId, '', branch);
        }
    });
    
    // Obsługa usuwania repozytorium
    $(document).on('click', '.aica-delete-repo, .aica-delete-button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const repoId = $(this).data('repo-id');
        const card = $(this).closest('.aica-repository-card');
        
        // Pokaż dialog potwierdzający
        $('#aica-delete-dialog').data('repo-id', repoId).data('card', card).show();
    });
    
    // Zamknięcie dialogu potwierdzającego
    $('.aica-dialog-close, .aica-dialog-cancel').on('click', function() {
        $('#aica-delete-dialog').hide();
    });
    
    // Potwierdzenie usunięcia repozytorium
    $('.aica-delete-confirm').on('click', function() {
        const dialog = $('#aica-delete-dialog');
        const repoId = dialog.data('repo-id');
        const card = dialog.data('card');
        
        // Ukryj dialog
        dialog.hide();
        
        // Wykonaj AJAX do usunięcia repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_delete_repository',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                if (response.success) {
                    // Ukryj kartę za pomocą animacji i usuń ją po zakończeniu
                    card.slideUp(300, function() {
                        card.remove();
                        
                        // Sprawdź, czy to było ostatnie repozytorium
                        if ($('#saved-repositories .aica-repository-card').length === 0) {
                            // Pokaż komunikat o braku repozytoriów
                            $('#saved-repositories').html(`
                                <div class="aica-empty-state">
                                    <div class="aica-empty-icon">
                                        <span class="dashicons dashicons-code-standards"></span>
                                    </div>
                                    <h2>${aica_repos.i18n.no_repositories}</h2>
                                    <p>${aica_repos.i18n.no_repositories_desc}</p>
                                    <button type="button" class="button button-primary aica-add-repository-button">
                                        <span class="dashicons dashicons-plus"></span>
                                        ${aica_repos.i18n.add_repository}
                                    </button>
                                </div>
                            `);
                        }
                        
                        // Aktualizuj licznik
                        const count = $('#saved-repositories .aica-repository-card').length;
                        $('.aica-source-item[data-source="saved"] .aica-source-count').text(count);
                    });
                } else {
                    // Pokaż komunikat o błędzie
                    alert(response.data.message || aica_repos.i18n.delete_error);
                }
            },
            error: function() {
                // Pokaż komunikat o błędzie
                alert(aica_repos.i18n.delete_error);
            }
        });
    });
    
    // Odświeżanie metadanych repozytorium
    $(document).on('click', '.aica-refresh-repo', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const repoId = $(this).data('repo-id');
        const card = $(this).closest('.aica-repository-card');
        
        // Dodaj klasę ładowania
        card.addClass('aica-card-refreshing');
        
        // Wykonaj AJAX do odświeżenia repozytorium
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aica_refresh_repository',
                nonce: aica_repos.nonce,
                repo_id: repoId
            },
            success: function(response) {
                // Usuń klasę ładowania
                card.removeClass('aica-card-refreshing');
                
                if (response.success) {
                    // Pokaż komunikat o sukcesie
                    alert(aica_repos.i18n.refresh_success);
                    
                    // Odśwież stronę
                    location.reload();
                } else {
                    // Pokaż komunikat o błędzie
                    alert(response.data.message || aica_repos.i18n.refresh_error);
                }
            },
            error: function() {
                // Usuń klasę ładowania
                card.removeClass('aica-card-refreshing');
                
                // Pokaż komunikat o błędzie
                alert(aica_repos.i18n.refresh_error);
            }
        });
    });
    
    // Dodawanie nowego repozytorium
    $('.aica-add-repository-button').on('click', function() {
        // Przełącz na zakładkę GitHub, GitLab lub Bitbucket
        if ($('.aica-source-item[data-source="github"]').length > 0) {
            $('.aica-source-item[data-source="github"]').trigger('click');
        } else if ($('.aica-source-item[data-source="gitlab"]').length > 0) {
            $('.aica-source-item[data-source="gitlab"]').trigger('click');
        } else if ($('.aica-source-item[data-source="bitbucket"]').length > 0) {
            $('.aica-source-item[data-source="bitbucket"]').trigger('click');
        } else {
            // Jeśli nie ma żadnego źródła, przekieruj do ustawień
            window.location.href = aica_repos.settings_url;
        }
    });
});
</script>