<?php
namespace AICA\Services;

use AICA\API\GitHubClient;
use AICA\API\GitLabClient;
use AICA\API\BitbucketClient;

class RepositoryService {
    private $db;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Pobranie repozytoriów z wybranej platformy
     */
    public function get_repositories($type = 'github') {
        switch ($type) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return [];
                }

                $github_client = new GitHubClient($github_token);
                return $github_client->get_repositories();
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return [];
                }

                $gitlab_client = new GitLabClient($gitlab_token);
                return $gitlab_client->get_repositories();
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return [];
                }

                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                return $bitbucket_client->get_repositories();
                
            default:
                return [];
        }
    }

    /**
     * Zapisanie nowego repozytorium
     */
    public function save_repository($type, $name, $owner, $url, $repo_id = '') {
        $user_id = aica_get_user_id(); // Użycie naszej funkcji zamiast get_current_user_id()
        
        if (!$user_id) {
            return false;
        }
        
        $now = current_time('mysql');

        $result = $this->db->insert(
            $this->db->prefix . 'aica_repositories',
            [
                'user_id' => $user_id,
                'repo_type' => $type,
                'repo_name' => $name,
                'repo_owner' => $owner,
                'repo_url' => $url,
                'repo_external_id' => $repo_id,
                'created_at' => $now
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $this->db->insert_id : false;
    }

    /**
     * Usunięcie repozytorium
     */
    public function delete_repository($repo_id) {
        $user_id = aica_get_user_id(); // Użycie naszej funkcji zamiast get_current_user_id()
        
        if (!$user_id) {
            return false;
        }

        $result = $this->db->delete(
            $this->db->prefix . 'aica_repositories',
            [
                'id' => $repo_id,
                'user_id' => $user_id
            ],
            ['%d', '%d']
        );

        return $result !== false;
    }

    /**
     * Pobranie zapisanych repozytoriów użytkownika
     */
    public function get_saved_repositories($user_id) {
        // Sprawdź czy podane user_id jest z naszej tabeli, jeśli nie, pobierz poprawne
        $is_wp_user_id = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}aica_repositories WHERE user_id = %d",
                $user_id
            )
        ) === '0';
        
        if ($is_wp_user_id) {
            // Konwersja z ID użytkownika WordPress do naszego ID użytkownika
            $plugin_user_id = aica_get_user_id($user_id);
            if ($plugin_user_id) {
                $user_id = $plugin_user_id;
            } else {
                return [];
            }
        }
        
        $table = $this->db->prefix . 'aica_repositories';
        
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY repo_type, repo_name",
                $user_id
            ),
            ARRAY_A
        );
        
        return $results ?: [];
    }

    /**
     * Pobranie zawartości pliku z repozytorium
     */
    public function get_file_content($repo_id, $path) {
        // Pobranie informacji o repozytorium
        $table = $this->db->prefix . 'aica_repositories';
        $user_id = aica_get_user_id(); // Użycie naszej funkcji zamiast get_current_user_id()
        
        if (!$user_id) {
            return [
                'success' => false,
                'message' => __('Nieautoryzowany dostęp.', 'ai-chat-assistant')
            ];
        }
        
        $repo = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                $repo_id, $user_id
            ),
            ARRAY_A
        );

        if (!$repo) {
            return [
                'success' => false,
                'message' => __('Repozytorium nie zostało znalezione.', 'ai-chat-assistant')
            ];
        }

        // Pobranie zawartości pliku w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return [
                        'success' => false,
                        'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                    ];
                }

                $github_client = new GitHubClient($github_token);
                $result = $github_client->get_file_content($repo['repo_owner'], $repo['repo_name'], $path);

                if (!$result) {
                    return [
                        'success' => false,
                        'message' => __('Nie udało się pobrać pliku.', 'ai-chat-assistant')
                    ];
                }

                return [
                    'success' => true,
                    'content' => $result['content'],
                    'path' => $path,
                    'language' => $result['language']
                ];
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return [
                        'success' => false,
                        'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                    ];
                }

                $gitlab_client = new GitLabClient($gitlab_token);
                $result = $gitlab_client->get_file_content($repo['repo_external_id'], $path);

                if (!$result) {
                    return [
                        'success' => false,
                        'message' => __('Nie udało się pobrać pliku.', 'ai-chat-assistant')
                    ];
                }

                return [
                    'success' => true,
                    'content' => $result['content'],
                    'path' => $path,
                    'language' => $result['language']
                ];
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return [
                        'success' => false,
                        'message' => __('Dane uwierzytelniające Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                    ];
                }

                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                $result = $bitbucket_client->get_file_content($repo['repo_owner'] . '/' . $repo['repo_name'], $path);

                if (!$result) {
                    return [
                        'success' => false,
                        'message' => __('Nie udało się pobrać pliku.', 'ai-chat-assistant')
                    ];
                }

                return [
                    'success' => true,
                    'content' => $result['content'],
                    'path' => $path,
                    'language' => $result['language']
                ];
                
            default:
                return [
                    'success' => false,
                    'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
                ];
        }
    }

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($repo_id, $query, $ref = null) {
        // Pobranie informacji o repozytorium
        $table = $this->db->prefix . 'aica_repositories';
        $user_id = aica_get_user_id(); // Użycie naszej funkcji zamiast get_current_user_id()
        
        if (!$user_id) {
            return [
                'success' => false,
                'message' => __('Nieautoryzowany dostęp.', 'ai-chat-assistant')
            ];
        }
        
        $repo = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM $table WHERE id = %d AND user_id = %d",
                $repo_id, $user_id
            ),
            ARRAY_A
        );

        if (!$repo) {
            return [
                'success' => false,
                'message' => __('Repozytorium nie zostało znalezione.', 'ai-chat-assistant')
            ];
        }

        // Jeśli nie podano gałęzi/tagu, użyj domyślnej głównej gałęzi
        if (empty($ref)) {
            $ref = ($repo['repo_type'] === 'bitbucket') ? 'master' : 'main';
        }

        // Wyszukiwanie w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return [
                        'success' => false,
                        'message' => __('Token GitHub nie jest skonfigurowany.', 'ai-chat-assistant')
                    ];
                }

                $github_client = new GitHubClient($github_token);
                $results = $github_client->search_repository($repo['repo_owner'], $repo['repo_name'], $query, $ref);

                return [
                    'success' => true,
                    'results' => $results,
                    'query' => $query,
                    'repo_name' => $repo['repo_name']
                ];
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return [
                        'success' => false,
                        'message' => __('Token GitLab nie jest skonfigurowany.', 'ai-chat-assistant')
                    ];
                }

                $gitlab_client = new GitLabClient($gitlab_token);
                $results = $gitlab_client->search_repository($repo['repo_external_id'], $query, $ref);

                return [
                    'success' => true,
                    'results' => $results,
                    'query' => $query,
                    'repo_name' => $repo['repo_name']
                ];
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return [
                        'success' => false,
                        'message' => __('Dane uwierzytelniające Bitbucket nie są skonfigurowane.', 'ai-chat-assistant')
                    ];
                }

                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                $results = $bitbucket_client->search_repository($repo['repo_owner'] . '/' . $repo['repo_name'], $query, $ref);

                return [
                    'success' => true,
                    'results' => $results,
                    'query' => $query,
                    'repo_name' => $repo['repo_name']
                ];
                
            default:
                return [
                    'success' => false,
                    'message' => __('Nieobsługiwany typ repozytorium.', 'ai-chat-assistant')
                ];
        }
    }
}