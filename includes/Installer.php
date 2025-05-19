<?php
namespace AICA;

class Installer {
    public function activate() {
        // Utworzenie tabel w bazie danych
        $this->create_tables();
        
        // Domyślne ustawienia
        $this->set_default_options();
        
        // Utworzenie katalogu uploads jeśli nie istnieje
        $this->create_upload_directory();
    }

    public function deactivate() {
        // Kod wykonywany podczas deaktywacji
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

       // Tabela repozytoriów
    $table_repos = $wpdb->prefix . 'aica_repositories';
    $sql_repos = "CREATE TABLE $table_repos (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        repo_type varchar(20) NOT NULL,
        repo_name varchar(255) NOT NULL,
        repo_owner varchar(255) NOT NULL,
        repo_url varchar(255) NOT NULL,
        repo_external_id varchar(255) DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";


        // Tabela sesji czatu
        $table_sessions = $wpdb->prefix . 'aica_sessions';
        $sql_sessions = "CREATE TABLE $table_sessions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabela wiadomości
        $table_messages = $wpdb->prefix . 'aica_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(50) NOT NULL,
            message longtext NOT NULL,
            response longtext NOT NULL,
            tokens_used int(11) NOT NULL,
            model varchar(50) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        // Tabela repozytoriów
        $table_repos = $wpdb->prefix . 'aica_repositories';
        $sql_repos = "CREATE TABLE $table_repos (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            repo_type varchar(20) NOT NULL,
            repo_name varchar(255) NOT NULL,
            repo_owner varchar(255) NOT NULL,
            repo_url varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_sessions);
        dbDelta($sql_messages);
        dbDelta($sql_repos);
    }

    private function set_default_options() {
        // Dodanie domyślnych ustawień
        $default_options = [
            'claude_model' => 'claude-3-haiku-20240307',
            'max_tokens' => 4000,
            'allowed_file_extensions' => 'txt,pdf,php,js,css,html,json,md'
        ];

        foreach ($default_options as $option => $value) {
            if (get_option('aica_' . $option) === false) {
                add_option('aica_' . $option, $value);
            }
        }
    }

    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $aica_dir = $upload_dir['basedir'] . '/aica-uploads';
        
        if (!file_exists($aica_dir)) {
            wp_mkdir_p($aica_dir);
        }
        
        // Dodaj plik .htaccess dla bezpieczeństwa
        $htaccess_file = $aica_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Options -Indexes\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
}