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
    }
    
    /**
     * Obsługa akcji na stronie repozytoriów
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'ai-chat-assistant-repositories') {
            return;
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
        // Sprawdź nonce
        check_ajax_referer('aica_repository_nonce', 'nonce');
        
        // Pobierz dane
        $type = isset($_POST['repo_type']) ? sanitize_text_field($_POST['repo_type']) : '';
        $name = isset($_POST['repo_name']) ? sanitize_text_field($_POST['repo_name']) : '';
        $owner = isset($_POST['repo_owner']) ? sanitize_text_field($_POST['repo_owner']) : '';
        $url = isset($_POST['repo_url']) ? esc_url_raw($_POST['repo_url']) : '';
        $repo_id = isset($_POST['repo_external_id']) ? sanitize_text_field($_POST['repo_external_id']) : '';
        $description = isset($_POST['repo_description']) ? sanitize_text_field($_POST['repo_description']) : '';
        
        if (empty($type) || empty($name) || empty($owner) || empty($url)) {
            wp_send_json_error(['message' => __('Brakujące dane repozytorium.', 'ai-chat-assistant')]);
            return;
        }
        
        // Dodaj repozytorium
        $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Repozytorium zostało dodane.', 'ai-chat-assistant'),
                'repo_id' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant')]);
        }
    }
    
    /**
     * Obsługa AJAX - usuwanie repozytorium
     */
    public function ajax_delete_repository() {
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
    }
    
    /**
     * Obsługa AJAX - odświeżanie metadanych repozytorium
     */
    public function ajax_refresh_repository() {
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
    }
    
    /**
     * Obsługa AJAX - pobieranie szczegółów repozytorium
     */
    public function ajax_get_repository_details() {
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
    }
    
    /**
     * Obsługa AJAX - pobieranie plików repozytorium
     */
    public function ajax_get_repository_files() {
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
    }
    
    /**
     * Obsługa AJAX - pobieranie zawartości pliku
     */
    public function ajax_get_file_content() {
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
    }
    
    /**
     * Renderowanie strony repozytoriów
     */
    public function render() {
        // Pobieranie repozytoriów GitHub
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
    }
}