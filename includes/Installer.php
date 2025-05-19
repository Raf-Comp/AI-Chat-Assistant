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
        
        // Dodanie bieżącego użytkownika do tabeli użytkowników
        $this->add_current_user();
    }

    public function deactivate() {
        // Kod wykonywany podczas deaktywacji
    }

    /**
     * Tworzy tabele bazy danych
     * 
     * @return bool Czy operacja się powiodła
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $error = false;

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
            repo_description text DEFAULT '',
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
        
        // Tabela użytkowników wtyczki
        $table_users = $wpdb->prefix . 'aica_users';
        $sql_users = "CREATE TABLE $table_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) NOT NULL,
            username varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            role varchar(50) NOT NULL,
            last_login datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wp_user_id (wp_user_id),
            KEY email (email)
        ) $charset_collate;";
        
        // Tabela opcji wtyczki
        $table_options = $wpdb->prefix . 'aica_options';
        $sql_options = "CREATE TABLE $table_options (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_name varchar(255) NOT NULL,
            option_value longtext NOT NULL,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        try {
            dbDelta($sql_repos);
            dbDelta($sql_sessions);
            dbDelta($sql_messages);
            dbDelta($sql_users);
            dbDelta($sql_options);
        } catch (\Exception $e) {
            $error = true;
            if (function_exists('aica_log')) {
                aica_log('Błąd tworzenia tabel: ' . $e->getMessage(), 'error');
            }
        }
        
        return !$error;
    }

    /**
     * Naprawia tabele bazy danych
     * 
     * @return bool Czy operacja się powiodła
     */
    public function repair_database() {
        // Najpierw sprawdź, które tabele istnieją
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $prefix = $wpdb->prefix . 'aica_';
        $missing_tables = [];
        
        // Lista oczekiwanych tabel
        $expected_tables = [
            $wpdb->prefix . 'aica_repositories',
            $wpdb->prefix . 'aica_sessions',
            $wpdb->prefix . 'aica_messages',
            $wpdb->prefix . 'aica_users',
            $wpdb->prefix . 'aica_options'
        ];
        
        // Sprawdź brakujące tabele
        foreach ($expected_tables as $table) {
            if (!in_array($table, $tables)) {
                $missing_tables[] = $table;
            }
        }
        
        // Jeśli nie ma brakujących tabel, nie ma potrzeby naprawiać
        if (empty($missing_tables)) {
            return true;
        }
        
        // Utwórz brakujące tabele
        $result = $this->create_tables();
        
        // Po utworzeniu tabel, dodaj domyślne opcje jeśli tabela opcji była brakująca
        if (in_array($wpdb->prefix . 'aica_options', $missing_tables)) {
            $this->set_default_options();
        }
        
        // Po utworzeniu tabel, dodaj bieżącego użytkownika jeśli tabela użytkowników była brakująca
        if (in_array($wpdb->prefix . 'aica_users', $missing_tables)) {
            $this->add_current_user();
        }
        
        // Sprawdź, czy katalog uploads istnieje
        $this->create_upload_directory();
        
        return $result;
    }

    /**
     * Sprawdza stan bazy danych
     * 
     * @return array Status tabel bazy danych
     */
    public function check_database_status() {
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $status = [];
        
        // Lista oczekiwanych tabel
        $expected_tables = [
            $wpdb->prefix . 'aica_repositories' => 'Repozytoria',
            $wpdb->prefix . 'aica_sessions' => 'Sesje',
            $wpdb->prefix . 'aica_messages' => 'Wiadomości',
            $wpdb->prefix . 'aica_users' => 'Użytkownicy',
            $wpdb->prefix . 'aica_options' => 'Opcje'
        ];
        
        // Sprawdź każdą oczekiwaną tabelę
        foreach ($expected_tables as $table_name => $description) {
            $exists = in_array($table_name, $tables);
            $records = 0;
            
            if ($exists) {
                $records = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
            }
            
            $status[$table_name] = [
                'name' => $description,
                'exists' => $exists,
                'records' => (int) $records
            ];
        }
        
        return $status;
    }

    private function set_default_options() {
        // Dodanie domyślnych ustawień
        $default_options = [
            'claude_model' => 'claude-3-haiku-20240307',
            'max_tokens' => 4000,
            'allowed_file_extensions' => 'txt,pdf,php,js,css,html,json,md'
        ];

        foreach ($default_options as $option => $value) {
            aica_update_option($option, $value);
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
        
        return file_exists($aica_dir);
    }
    
    private function add_current_user() {
        // Pobierz informacje o bieżącym użytkowniku WP
        $current_wp_user_id = get_current_user_id();
        
        // Jeśli nie jesteśmy zalogowani, przerwij
        if ($current_wp_user_id === 0) {
            return false;
        }
        
        $user_info = get_userdata($current_wp_user_id);
        if ($user_info) {
            $now = current_time('mysql');
            
            // Sprawdź czy funkcja aica_get_highest_role istnieje
            $role = function_exists('aica_get_highest_role') 
                ? aica_get_highest_role($user_info->roles) 
                : $this->get_highest_role($user_info->roles);
            
            // Dodaj użytkownika do naszej tabeli
            $result = function_exists('aica_add_user') 
                ? aica_add_user($current_wp_user_id, $user_info->user_login, $user_info->user_email, $role, $now)
                : false;
                
            return $result !== false;
        }
        
        return false;
    }
    
    /**
     * Pobiera najwyższą rolę użytkownika
     * 
     * @param array $roles Tablica ról użytkownika
     * @return string Najwyższa rola
     */
    private function get_highest_role($roles) {
        // Hierarchia ról
        $role_hierarchy = [
            'administrator' => 5,
            'editor' => 4,
            'author' => 3,
            'contributor' => 2,
            'subscriber' => 1
        ];
        
        $highest_role = 'subscriber';
        $highest_rank = 0;
        
        foreach ($roles as $role) {
            $rank = isset($role_hierarchy[$role]) ? $role_hierarchy[$role] : 0;
            if ($rank > $highest_rank) {
                $highest_rank = $rank;
                $highest_role = $role;
            }
        }
        
        return $highest_role;
    }
}