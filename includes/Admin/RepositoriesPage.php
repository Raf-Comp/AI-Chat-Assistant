<?php
namespace AICA\Admin;

use AICA\Services\RepositoryService;

class RepositoriesPage {
    private $repo_service;
    
    public function __construct() {
        $this->repo_service = new RepositoryService();
        
        // Obsługa akcji
        add_action('admin_init', [$this, 'handle_actions']);
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
            
            if (!empty($type) && !empty($name) && !empty($owner) && !empty($url)) {
                $result = $this->repo_service->save_repository($type, $name, $owner, $url, $repo_id);
                
                if ($result) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Repozytorium zostało dodane.', 'ai-chat-assistant') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Nie udało się dodać repozytorium.', 'ai-chat-assistant') . '</p></div>';
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
                    echo '<div class="notice notice-success"><p>' . __('Repozytorium zostało usunięte.', 'ai-chat-assistant') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>' . __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant') . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Renderowanie strony repozytoriów
     */
    public function render() {
        // Pobieranie repozytoriów GitHub
        $github_token = get_option('aica_github_token', '');
        $github_repos = [];
        
        if (!empty($github_token)) {
            $github_client = new \AICA\API\GitHubClient($github_token);
            $github_repos = $github_client->get_repositories();
        }
        
        // Pobieranie repozytoriów GitLab
        $gitlab_token = get_option('aica_gitlab_token', '');
        $gitlab_repos = [];
        
        if (!empty($gitlab_token)) {
            $gitlab_client = new \AICA\API\GitLabClient($gitlab_token);
            $gitlab_repos = $gitlab_client->get_repositories();
        }
        
        // Pobieranie repozytoriów Bitbucket
        $bitbucket_username = get_option('aica_bitbucket_username', '');
        $bitbucket_app_password = get_option('aica_bitbucket_app_password', '');
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