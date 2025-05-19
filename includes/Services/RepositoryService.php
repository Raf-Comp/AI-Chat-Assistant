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
    public function save_repository($type, $name, $owner, $url, $repo_id = '', $description = '') {
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        $now = current_time('mysql');

        // Sprawdź czy repozytorium już istnieje
        $exists = $this->db->get_var(
            $this->db->prepare(
                "SELECT id FROM {$this->db->prefix}aica_repositories WHERE user_id = %d AND repo_external_id = %s AND repo_type = %s",
                $user_id, $repo_id, $type
            )
        );

        if ($exists) {
            // Aktualizuj istniejące repozytorium
            $result = $this->db->update(
                $this->db->prefix . 'aica_repositories',
                [
                    'repo_name' => $name,
                    'repo_owner' => $owner,
                    'repo_url' => $url,
                    'repo_description' => $description
                ],
                ['id' => $exists],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );
            return $exists;
        } else {
            // Dodaj nowe repozytorium
            $result = $this->db->insert(
                $this->db->prefix . 'aica_repositories',
                [
                    'user_id' => $user_id,
                    'repo_type' => $type,
                    'repo_name' => $name,
                    'repo_owner' => $owner,
                    'repo_url' => $url,
                    'repo_external_id' => $repo_id,
                    'repo_description' => $description,
                    'created_at' => $now
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            return $result ? $this->db->insert_id : false;
        }
    }

    /**
     * Usunięcie repozytorium
     */
    public function delete_repository($repo_id) {
        $user_id = aica_get_user_id();
        
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
    public function get_saved_repositories($user_id = null) {
        if ($user_id === null) {
            $user_id = aica_get_user_id();
            if (!$user_id) {
                return [];
            }
        } else {
            // Sprawdź czy podane user_id jest z naszej tabeli, jeśli nie, pobierz poprawne
            $plugin_user_id = aica_get_user_id($user_id);
            if ($plugin_user_id) {
                $user_id = $plugin_user_id;
            }
        }
        
        $table = $this->db->prefix . 'aica_repositories';
        
        // Sprawdź czy tabela istnieje
        $table_exists = $this->db->get_var($this->db->prepare(
            "SHOW TABLES LIKE %s",
            $table
        )) === $table;
        
        if (!$table_exists) {
            return [];
        }
        
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
     * Pobiera szczegóły pojedynczego repozytorium
     */
    public function get_repository($repo_id) {
        $user_id = aica_get_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        $repo = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}aica_repositories WHERE id = %d AND user_id = %d",
                $repo_id, $user_id
            ),
            ARRAY_A
        );
        
        return $repo;
    }

    /**
     * Pobiera pliki z repozytorium
     */
    public function get_repository_files($repo_id, $path = '', $branch = 'main') {
        $repo = $this->get_repository($repo_id);
        
        if (!$repo) {
            return [];
        }
        
        // Pobranie plików w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return [];
                }
                
                $github_client = new GitHubClient($github_token);
                return $github_client->get_directory_contents($repo['repo_owner'], $repo['repo_name'], $path, $branch);
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return [];
                }
                
                $gitlab_client = new GitLabClient($gitlab_token);
                return $gitlab_client->get_directory_contents($repo['repo_external_id'], $path, $branch);
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return [];
                }
                
                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                return $bitbucket_client->get_directory_contents($repo['repo_owner'] . '/' . $repo['repo_name'], $path, $branch);
                
            default:
                return [];
        }
    }

    /**
     * Pobiera zawartość pliku z repozytorium
     */
    public function get_file_content($repo_id, $path, $branch = 'main') {
        $repo = $this->get_repository($repo_id);
        
        if (!$repo) {
            return false;
        }
        
        // Pobranie zawartości pliku w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return false;
                }
                
                $github_client = new GitHubClient($github_token);
                return $github_client->get_file_content($repo['repo_owner'], $repo['repo_name'], $path, $branch);
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return false;
                }
                
                $gitlab_client = new GitLabClient($gitlab_token);
                return $gitlab_client->get_file_content($repo['repo_external_id'], $path, $branch);
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return false;
                }
                
                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                return $bitbucket_client->get_file_content($repo['repo_owner'] . '/' . $repo['repo_name'], $path, $branch);
                
            default:
                return false;
        }
    }

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($repo_id, $query, $branch = 'main') {
        $repo = $this->get_repository($repo_id);
        
        if (!$repo) {
            return [];
        }
        
        // Wyszukiwanie w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return [];
                }
                
                $github_client = new GitHubClient($github_token);
                return $github_client->search_repository($repo['repo_owner'], $repo['repo_name'], $query, $branch);
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return [];
                }
                
                $gitlab_client = new GitLabClient($gitlab_token);
                return $gitlab_client->search_repository($repo['repo_external_id'], $query, $branch);
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return [];
                }
                
                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                return $bitbucket_client->search_repository($repo['repo_owner'] . '/' . $repo['repo_name'], $query, $branch);
                
            default:
                return [];
        }
    }

    /**
     * Odświeżenie metadanych repozytorium
     */
    public function refresh_repository_metadata($repo_id) {
        $repo = $this->get_repository($repo_id);
        
        if (!$repo) {
            return false;
        }
        
        // Odświeżenie metadanych w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return false;
                }
                
                $github_client = new GitHubClient($github_token);
                $github_repos = $github_client->get_repositories();
                
                foreach ($github_repos as $github_repo) {
                    if ($github_repo['id'] == $repo['repo_external_id']) {
                        // Aktualizacja metadanych
                        $this->db->update(
                            $this->db->prefix . 'aica_repositories',
                            [
                                'repo_name' => $github_repo['name'],
                                'repo_owner' => $github_repo['owner'],
                                'repo_url' => $github_repo['url'],
                                'repo_description' => $github_repo['description']
                            ],
                            ['id' => $repo_id],
                            ['%s', '%s', '%s', '%s'],
                            ['%d']
                        );
                        
                        return true;
                    }
                }
                
                return false;
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return false;
                }
                
                $gitlab_client = new GitLabClient($gitlab_token);
                $gitlab_repos = $gitlab_client->get_repositories();
                
                foreach ($gitlab_repos as $gitlab_repo) {
                    if ($gitlab_repo['id'] == $repo['repo_external_id']) {
                        // Aktualizacja metadanych
                        $this->db->update(
                            $this->db->prefix . 'aica_repositories',
                            [
                                'repo_name' => $gitlab_repo['name'],
                                'repo_owner' => $gitlab_repo['owner'],
                                'repo_url' => $gitlab_repo['url'],
                                'repo_description' => $gitlab_repo['description']
                            ],
                            ['id' => $repo_id],
                            ['%s', '%s', '%s', '%s'],
                            ['%d']
                        );
                        
                        return true;
                    }
                }
                
                return false;
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return false;
                }
                
                $bitbucket_client = new BitbucketClient($bitbucket_username, $bitbucket_app_password);
                $bitbucket_repos = $bitbucket_client->get_repositories();
                
                foreach ($bitbucket_repos as $bitbucket_repo) {
                    if ($bitbucket_repo['id'] == $repo['repo_external_id']) {
                        // Aktualizacja metadanych
                        $this->db->update(
                            $this->db->prefix . 'aica_repositories',
                            [
                                'repo_name' => $bitbucket_repo['name'],
                                'repo_owner' => $bitbucket_repo['owner'],
                                'repo_url' => $bitbucket_repo['url'],
                                'repo_description' => $bitbucket_repo['description']
                            ],
                            ['id' => $repo_id],
                            ['%s', '%s', '%s', '%s'],
                            ['%d']
                        );
                        
                        return true;
                    }
                }
                
                return false;
                
            default:
                return false;
        }
    }

    /**
     * Pobieranie dostępnych gałęzi repozytorium
     */
    public function get_repository_branches($repo_id) {
        $repo = $this->get_repository($repo_id);
        
        if (!$repo) {
            return [];
        }
        
        // Domyślne gałęzie, jeśli nie uda się pobrać z API
        $default_branches = ['main', 'master', 'develop'];
        
        // Pobranie gałęzi w zależności od typu repozytorium
        switch ($repo['repo_type']) {
            case 'github':
                $github_token = aica_get_option('github_token', '');
                if (empty($github_token)) {
                    return $default_branches;
                }
                
                // Tutaj powinno być pobranie gałęzi z GitHub API
                // W tym przykładzie zwracamy domyślne gałęzie
                return $default_branches;
                
            case 'gitlab':
                $gitlab_token = aica_get_option('gitlab_token', '');
                if (empty($gitlab_token)) {
                    return $default_branches;
                }
                
                // Tutaj powinno być pobranie gałęzi z GitLab API
                // W tym przykładzie zwracamy domyślne gałęzie
                return $default_branches;
                
            case 'bitbucket':
                $bitbucket_username = aica_get_option('bitbucket_username', '');
                $bitbucket_app_password = aica_get_option('bitbucket_app_password', '');
                if (empty($bitbucket_username) || empty($bitbucket_app_password)) {
                    return $default_branches;
                }
                
                // Tutaj powinno być pobranie gałęzi z Bitbucket API
                // W tym przykładzie zwracamy domyślne gałęzie
                return $default_branches;
                
            default:
                return $default_branches;
        }
    }
}