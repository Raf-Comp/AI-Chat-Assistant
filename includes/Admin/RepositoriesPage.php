<?php
namespace AICA\Admin;

use AICA\Services\RepositoryService;

class RepositoriesPage {
    private $repo_service;
    
    public function __construct() {
        $this->repo_service = new RepositoryService();
        
        // Obsługa akcji
        add_action('admin_init', [$this, 'handle_actions']);
        
        // Rejestracja akcji AJAX
        add_action('wp_ajax_aica_add_repository', [$this, 'ajax_add_repository']);
        add_action('wp_ajax_aica_delete_repository', [$this, 'ajax_delete_repository']);
        add_action('wp_ajax_aica_refresh_repository', [$this, 'ajax_refresh_repository']);
        add_action('wp_ajax_aica_get_repository_details', [$this, 'ajax_get_repository_details']);
        add_action('wp_ajax_aica_get_repository_files', [$this, 'ajax_get_repository_files']);
        add_action('wp_ajax_aica_get_file_content', [$this, 'ajax_get_file_content']);
        
        // Diagnostyczne punkty AJAX
        add_action('wp_ajax_aica_test_db', [$this, 'ajax_test_db']);
        add_action('wp_ajax_aica_activate_plugin', [$this, 'ajax_activate_plugin']);
    }
    
    /**
     * Testowanie tabeli bazy danych
     */
    public function ajax_test_db() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aica_repositories';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        wp_send_json_success([
            'table_exists' => $table_exists,
            'table_name' => $table_name,
            'current_user_id' => get_current_user_id(),
            'wp_version' => get_bloginfo('version')
        ]);
    }
    
    /**
     * Aktywacja pluginu - tworzenie tabel 
     */
    public function ajax_activate_plugin() {
        global $wpdb;
        
        // Tworzenie tabeli repozytoriów, jeśli nie istnieje
        $repositories_table = $wpdb->prefix . 'aica_repositories';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $repositories_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            repo_type varchar(20) NOT NULL,
            repo_name varchar(255) NOT NULL,
            repo_owner varchar(255) NOT NULL,
            repo_url varchar(255) NOT NULL,
            repo_external_id varchar(255) DEFAULT '',
            repo_description text DEFAULT '',
            languages varchar(255) DEFAULT '',
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $success = $wpdb->get_var("SHOW TABLES LIKE '$repositories_table'") === $repositories_table;
        
        wp_send_json_success([
            'message' => 'Aktywacja zakończona',
            'table_created' => $success
        ]);
    }
    
    /**
     * Obsługa akcji na stronie repozytoriów
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'ai-chat-assistant-repositories') {
            return;
        }
        
        // Sprawdź czy tabela repozytoriów istnieje
        global $wpdb;
        $table_name = $wpdb->prefix . 'aica_repositories';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->ajax_activate_plugin();
        }
        
        // Dodawanie repozytorium
        if (isset($_POST['aica_add_repository']) && check_admin_referer('aica_repository_nonce')) {
            $type = isset($_POST['repo_type']) ? sanitize_text_field($_POST['repo_type']) : '';
            $name = isset($_POST['repo_name']) ? sanitize_text_field($_POST['repo_name']) : '';
            $owner = isset($_POST['repo_owner']) ? sanitize_text_field($_POST['repo_owner']) : '';
            $url = isset($_POST['repo_url']) ? esc_url_raw($_POST['repo_url']) : '';
            $repo_id = isset($_POST['repo_external_id']) ? sanitize_text_field($_POST['repo_external_id']) : '';
            $description = isset($_POST['repo_description']) ? sanitize_text_field($_POST['repo_description']) : '';
            
            if (!empty($type) && !empty($name) && !empty($owner) && !empty($url)) {
                $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
                
                if ($result) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success is-dismissible"><p>' . __('Repozytorium zostało dodane.', 'ai-chat-assistant') . '</p></div>';
                    });
                    
                    // Przekieruj, aby uniknąć ponownego wysłania formularza po odświeżeniu
                    wp_redirect(admin_url('admin.php?page=ai-chat-assistant-repositories&added=true'));
                    exit;
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Nie udało się dodać repozytorium.', 'ai-chat-assistant') . '</p></div>';
                    });
                }
            }
        }
        
        // Usuwanie repozytorium
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['repo_id']) && check_admin_referer('delete_repository')) {
            $repo_id = intval($_GET['repo_id']);
            $result = $this->repo_service->delete_repository($repo_id);
            
            if ($result) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Repozytorium zostało usunięte.', 'ai-chat-assistant') . '</p></div>';
                });
                // Przekieruj po usunięciu, aby odświeżyć listę
                wp_redirect(admin_url('admin.php?page=ai-chat-assistant-repositories&deleted=true'));
                exit;
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant') . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Obsługa AJAX - dodawanie repozytorium
     */
    public function ajax_add_repository() {
        // Włącz szczegółowe logowanie błędów dla debugowania
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        try {
            // Sprawdź nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aica_repository_nonce')) {
                wp_send_json_error([
                    'message' => __('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant'),
                    'error_code' => 'invalid_nonce'
                ]);
                return;
            }
            
            // Debugowanie - sprawdź otrzymane dane
            error_log('AICA Add Repository - Received data: ' . print_r($_POST, true));
            
            // Pobierz dane z żądania
            $type = isset($_POST['repo_type']) ? sanitize_text_field($_POST['repo_type']) : '';
            $name = isset($_POST['repo_name']) ? sanitize_text_field($_POST['repo_name']) : '';
            $owner = isset($_POST['repo_owner']) ? sanitize_text_field($_POST['repo_owner']) : '';
            $url = isset($_POST['repo_url']) ? esc_url_raw($_POST['repo_url']) : '';
            $repo_id = isset($_POST['repo_external_id']) ? sanitize_text_field($_POST['repo_external_id']) : '';
            $description = isset($_POST['repo_description']) ? sanitize_text_field($_POST['repo_description']) : '';
            
            // Sprawdź wymagane dane
            if (empty($type)) {
                wp_send_json_error([
                    'message' => __('Wybierz typ repozytorium.', 'ai-chat-assistant'),
                    'error_code' => 'missing_type'
                ]);
                return;
            }
            
            if (empty($name)) {
                wp_send_json_error([
                    'message' => __('Podaj nazwę repozytorium.', 'ai-chat-assistant'),
                    'error_code' => 'missing_name'
                ]);
                return;
            }
            
            if (empty($owner)) {
                wp_send_json_error([
                    'message' => __('Podaj właściciela repozytorium.', 'ai-chat-assistant'),
                    'error_code' => 'missing_owner'
                ]);
                return;
            }
            
            if (empty($url)) {
                wp_send_json_error([
                    'message' => __('Podaj URL repozytorium.', 'ai-chat-assistant'),
                    'error_code' => 'missing_url'
                ]);
                return;
            }
            
            // Dodaj repozytorium
            $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
            
            // Debugowanie - sprawdź wynik operacji
            error_log('AICA Add Repository - Result: ' . var_export($result, true));
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Repozytorium zostało dodane.', 'ai-chat-assistant'),
                    'repo_id' => $result
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant'),
                    'error_code' => 'save_failed'
                ]);
            }
        } catch (\Exception $e) {
            error_log('AICA Add Repository Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Wystąpił błąd podczas dodawania repozytorium: ', 'ai-chat-assistant') . $e->getMessage(),
                'error_code' => 'exception'
            ]);
        }
    }
    
    /**
     * Obsługa AJAX - usuwanie repozytorium
     */
    public function ajax_delete_repository() {
        try {
            // Sprawdź nonce
            check_ajax_referer('aica_repository_nonce', 'nonce');
            
            // Pobierz dane
            $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
            
            if (empty($repo_id)) {
                wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
                return;
            }
            
            // Usuń repozytorium
            $result = $this->repo_service->delete_repository($repo_id);
            
            if ($result) {
                wp_send_json_success(['message' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant')]);
            } else {
                wp_send_json_error(['message' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant')]);
            }
        } catch (\Exception $e) {
            error_log('RepositoriesPage AJAX Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Wystąpił błąd podczas usuwania repozytorium.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Obsługa AJAX - odświeżanie metadanych repozytorium
     */
    public function ajax_refresh_repository() {
        try {
            // Sprawdź nonce
            check_ajax_referer('aica_repository_nonce', 'nonce');
            
            // Pobierz dane
            $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
            
            if (empty($repo_id)) {
                wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
                return;
            }
            
            // Odśwież metadane repozytorium
            $result = $this->repo_service->refresh_repository_metadata($repo_id);
            
            if ($result) {
                wp_send_json_success(['message' => __('Metadane repozytorium zostały zaktualizowane.', 'ai-chat-assistant')]);
            } else {
                wp_send_json_error(['message' => __('Nie udało się odświeżyć metadanych repozytorium.', 'ai-chat-assistant')]);
            }
        } catch (\Exception $e) {
            error_log('RepositoriesPage AJAX Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Wystąpił błąd podczas odświeżania metadanych.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie szczegółów repozytorium
     */
    public function ajax_get_repository_details() {
        try {
            // Sprawdź nonce
            check_ajax_referer('aica_repository_nonce', 'nonce');
            
            // Pobierz dane
            $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
            
            if (empty($repo_id)) {
                wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
                return;
            }
            
            // Pobierz szczegóły repozytorium
            $repo = $this->repo_service->get_repository($repo_id);
            
            if ($repo) {
                // Pobierz dostępne gałęzie
                $branches = $this->repo_service->get_repository_branches($repo_id);
                
                wp_send_json_success([
                    'repository' => $repo,
                    'branches' => $branches
                ]);
            } else {
                wp_send_json_error(['message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')]);
            }
        } catch (\Exception $e) {
            error_log('RepositoriesPage AJAX Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Wystąpił błąd podczas pobierania szczegółów repozytorium.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie plików repozytorium
     */
    public function ajax_get_repository_files() {
        try {
            // Sprawdź nonce
            check_ajax_referer('aica_repository_nonce', 'nonce');
            
            // Pobierz dane
            $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
            $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
            $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'main';
            
            if (empty($repo_id)) {
                wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
                return;
            }
            
            // Pobierz pliki repozytorium
            $files = $this->repo_service->get_repository_files($repo_id, $path, $branch);
            
            if (is_array($files)) {
                wp_send_json_success(['files' => $files]);
            } else {
                wp_send_json_error(['message' => __('Nie udało się pobrać plików repozytorium.', 'ai-chat-assistant')]);
            }
        } catch (\Exception $e) {
            error_log('RepositoriesPage AJAX Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Wystąpił błąd podczas pobierania plików.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Obsługa AJAX - pobieranie zawartości pliku
     */
    public function ajax_get_file_content() {
        try {
            // Sprawdź nonce
            check_ajax_referer('aica_repository_nonce', 'nonce');
            
            // Pobierz dane
            $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
            $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
            $branch = isset($_POST['branch']) ? sanitize_text_field($_POST['branch']) : 'main';
            
            if (empty($repo_id) || empty($path)) {
                wp_send_json_error(['message' => __('Nieprawidłowe dane pliku.', 'ai-chat-assistant')]);
                return;
            }
            
            // Pobierz zawartość pliku
            $file_content = $this->repo_service->get_file_content($repo_id, $path, $branch);
            
            if ($file_content) {
                wp_send_json_success($file_content);
            } else {
                wp_send_json_error(['message' => __('Nie udało się pobrać zawartości pliku.', 'ai-chat-assistant')]);
            }
        } catch (\Exception $e) {
            error_log('RepositoriesPage AJAX Error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('Wystąpił błąd podczas pobierania zawartości pliku.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Renderowanie strony repozytoriów
     */
    public function render() {
        try {
            // Pobieranie repozytoriów GitHub
            $github_token = aica_get_option('github_token', '');
            $github_token = aica_get_option('github_token', '');
            $github_repos = [];
            
            if (!empty($github_token)) {
                $github_client = new \AICA\API\GitHubClient($github_token);
                $github_repos = $github_client->get_repositories();
            }
            
            // Pobieranie repozytoriów GitLab
            $gitlab_token = aica_get_option('gitlab_token', '');
            $gitlab_repos = [];
            
            if (!empty($gitlab_token)) {
                $gitlab_client = new \AICA\API\GitLabClient($gitlab_token);
                $gitlab_repos = $gitlab_client->get_repositories();
            }
            
            // Pobieranie repozytoriów Bitbucket
            $bitbucket_username = aica_get_option('bitbucket_username', '');
            $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
            $bitbucket_repos = [];
            
            if (!empty($bitbucket_username) && !empty($bitbucket_app_password)) {
                $bitbucket_client = new \AICA\API\BitbucketClient($bitbucket_username, $bitbucket_app_password);
                $bitbucket_repos = $bitbucket_client->get_repositories();
            }
            
            // Pobieranie zapisanych repozytoriów
            $user_id = get_current_user_id();
            $saved_repositories = $this->repo_service->get_saved_repositories($user_id);
            
            // Renderowanie szablonu
            include AICA_PLUGIN_DIR . 'templates/admin/repositories.php';
        } catch (\Exception $e) {
            error_log('RepositoriesPage Render Error: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>' . __('Wystąpił błąd podczas renderowania strony repozytoriów.', 'ai-chat-assistant') . '</p></div>';
        }
    }
}

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'ai-chat-assistant_page_ai-chat-assistant-repositories') {
        wp_enqueue_style('aica-style-repositories', plugin_dir_url(__DIR__) . '../../assets/css/repositories.css');
    }
});
