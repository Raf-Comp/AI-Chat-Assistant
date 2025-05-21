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
     * Sprawdza czy tabela repozytoriów istnieje i tworzy ją jeśli potrzeba
     */
    private function maybe_create_table() {
        $table_name = $this->db->prefix . 'aica_repositories';
        
        // Sprawdź czy tabela istnieje
        if ($this->db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log("AICA - Tworzenie tabeli repozytoriów");
            
            $charset_collate = $this->db->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
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
            
            error_log("AICA - Rezultat tworzenia tabeli: " . ($this->db->get_var("SHOW TABLES LIKE '$table_name'") == $table_name ? "Sukces" : "Niepowodzenie"));
        }
    }

    /**
     * Pobranie repozytoriów z wybranej platformy
     */
    public function get_repositories($type = 'github') {
        try {
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Zapisanie nowego repozytorium
     */
    public function save_repository($type, $name, $owner, $url, $repo_id = '', $description = '') {
        try {
            // Logowanie dla debugowania
            error_log("AICA Save Repository - Początek z typem: $type, nazwa: $name, właściciel: $owner");
            
            // Walidacja danych
            if (empty($type) || empty($name) || empty($owner) || empty($url)) {
                error_log("AICA Save Repository - Nieprawidłowe dane");
                return false;
            }
            
            $user_id = get_current_user_id();
            
            if (!$user_id) {
                error_log("AICA Save Repository - Nie znaleziono ID użytkownika");
                return false;
            }
            
            // Twórz tabelę jeśli nie istnieje
            $this->maybe_create_table();
            
            $now = current_time('mysql');
            
            // Sprawdź czy repozytorium już istnieje
            $exists = false;
            if (!empty($repo_id)) {
                $exists = $this->db->get_var(
                    $this->db->prepare(
                        "SELECT id FROM {$this->db->prefix}aica_repositories WHERE user_id = %d AND repo_external_id = %s AND repo_type = %s",
                        $user_id, $repo_id, $type
                    )
                );
            }
            
            if (!$exists && !empty($owner) && !empty($name)) {
                // Sprawdź czy istnieje repozytorium o tej samej nazwie i właścicielu
                $exists = $this->db->get_var(
                    $this->db->prepare(
                        "SELECT id FROM {$this->db->prefix}aica_repositories WHERE user_id = %d AND repo_type = %s AND repo_owner = %s AND repo_name = %s",
                        $user_id, $type, $owner, $name
                    )
                );
            }
            
            error_log("AICA Save Repository - Sprawdzenie istnienia repozytorium: " . ($exists ? "Tak, ID: $exists" : "Nie"));
            
            if ($exists) {
                // Aktualizuj istniejące repozytorium
                $result = $this->db->update(
                    $this->db->prefix . 'aica_repositories',
                    [
                        'repo_name' => $name,
                        'repo_owner' => $owner,
                        'repo_url' => $url,
                        'repo_description' => $description,
                        'updated_at' => $now
                    ],
                    ['id' => $exists],
                    ['%s', '%s', '%s', '%s', '%s'],
                    ['%d']
                );
                
                error_log("AICA Save Repository - Wynik aktualizacji: " . var_export($result, true));
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
                        'created_at' => $now,
                        'updated_at' => $now
                    ],
                    ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );
                
                error_log("AICA Save Repository - Wynik dodawania: " . var_export($result, true));
                error_log("AICA Save Repository - Ostatnie zapytanie: " . $this->db->last_query);
                error_log("AICA Save Repository - Ostatni błąd: " . $this->db->last_error);
                
                if ($result) {
                    return $this->db->insert_id;
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            error_log('AICA Save Repository Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Usunięcie repozytorium
     */
    public function delete_repository($repo_id) {
        try {
            $user_id = get_current_user_id();
            
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobranie zapisanych repozytoriów użytkownika
     */
    public function get_saved_repositories($user_id = null) {
        try {
            if ($user_id === null) {
                $user_id = get_current_user_id();
                if (!$user_id) {
                    return [];
                }
            }
            
            $table = $this->db->prefix . 'aica_repositories';
            
            // Sprawdź czy tabela istnieje
            $table_exists = $this->db->get_var($this->db->prepare(
                "SHOW TABLES LIKE %s",
                $table
            )) === $table;
            
            if (!$table_exists) {
                // Twórz tabelę jeśli nie istnieje
                $this->maybe_create_table();
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobiera szczegóły pojedynczego repozytorium
     */
    public function get_repository($repo_id) {
        try {
            $user_id = get_current_user_id();
            
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobiera pliki z repozytorium
     */
    public function get_repository_files($repo_id, $path = '', $branch = 'main') {
        try {
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pobiera zawartość pliku z repozytorium
     */
    public function get_file_content($repo_id, $path, $branch = 'main') {
        try {
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pobieranie dostępnych gałęzi repozytorium
     */
    public function get_repository_branches($repo_id) {
        try {
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
                    
                    $github_client = new GitHubClient($github_token);
                    $branches = $github_client->get_repository_branches($repo['repo_owner'], $repo['repo_name']);
                    return !empty($branches) ? $branches : $default_branches;
                    
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return ['main', 'master', 'develop'];
        }
    }

    /**
     * Odświeżenie metadanych repozytorium
     */
    public function refresh_repository_metadata($repo_id) {
        try {
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
                    $repo_info = $github_client->get_repository_info($repo['repo_owner'] . '/' . $repo['repo_name']);
                    
                    if ($repo_info) {
                        // Aktualizacja metadanych
                        $result = $this->db->update(
                            $this->db->prefix . 'aica_repositories',
                            [
                                'repo_name' => $repo_info['name'],
                                'repo_owner' => $repo_info['owner'],
                                'repo_url' => $repo_info['html_url'],
                                'repo_description' => $repo_info['description'],
                                'languages' => $repo_info['language'] ?? '',
                                'updated_at' => current_time('mysql')
                            ],
                            ['id' => $repo_id],
                            ['%s', '%s', '%s', '%s', '%s', '%s'],
                            ['%d']
                        );
                        
                        return $result !== false;
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
                            $result = $this->db->update(
                                $this->db->prefix . 'aica_repositories',
                                [
                                    'repo_name' => $gitlab_repo['name'],
                                    'repo_owner' => $gitlab_repo['owner'],
                                    'repo_url' => $gitlab_repo['url'],
                                    'repo_description' => $gitlab_repo['description'],
                                    'updated_at' => current_time('mysql')
                                ],
                                ['id' => $repo_id],
                                ['%s', '%s', '%s', '%s', '%s'],
                                ['%d']
                            );
                            
                            return $result !== false;
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
                            $result = $this->db->update(
                                $this->db->prefix . 'aica_repositories',
                                [
                                    'repo_name' => $bitbucket_repo['name'],
                                    'repo_owner' => $bitbucket_repo['owner'],
                                    'repo_url' => $bitbucket_repo['url'],
                                    'repo_description' => $bitbucket_repo['description'],
                                    'updated_at' => current_time('mysql')
                                ],
                                ['id' => $repo_id],
                                ['%s', '%s', '%s', '%s', '%s'],
                                ['%d']
                            );
                            
                            return $result !== false;
                        }
                    }
                    
                    return false;
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Wyszukiwanie w repozytorium
     */
    public function search_repository($repo_id, $query, $branch = 'main') {
        try {
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
        } catch (\Exception $e) {
            error_log('Repository Service Error: ' . $e->getMessage());
            return [];
        }
    }
}