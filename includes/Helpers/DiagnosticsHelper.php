<?php
/**
 * Pomocnicze funkcje dla diagnostyki
 *
 * @package AI_Chat_Assistant
 */

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

/**
 * Pobiera wartość opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $default Domyślna wartość zwracana, jeśli opcja nie istnieje
 * @return mixed Wartość opcji lub wartość domyślna
 */
function aica_get_option($option_name, $default = null) {
    // Najpierw sprawdź, czy opcja jest w cache
    static $options_cache = array();
    
    if (isset($options_cache[$option_name])) {
        return $options_cache[$option_name];
    }
    
    // Jeśli nie ma w cache, pobierz z bazy danych
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        // Pobierz wartość z tabeli wtyczki
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM $table_name WHERE option_name = %s LIMIT 1",
            $option_name
        ));
        
        if ($value !== null) {
            // Zdekoduj wartość JSON
            $decoded_value = json_decode($value, true);
            // Jeśli dekodowanie się powiedzie, zwróć zdekodowaną wartość, w przeciwnym razie zwróć oryginalną
            $result = (json_last_error() === JSON_ERROR_NONE) ? $decoded_value : $value;
            $options_cache[$option_name] = $result;
            return $result;
        }
    }
    
    // Jeśli nie znaleziono w tabeli wtyczki, spróbuj pobrać z opcji WordPress
    $wp_option_name = 'aica_' . $option_name;
    $value = get_option($wp_option_name, $default);
    
    // Zapisz w cache i zwróć
    $options_cache[$option_name] = $value;
    return $value;
}

/**
 * Aktualizuje wartość opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $option_value Wartość opcji
 * @return bool Czy operacja się powiodła
 */
function aica_update_option($option_name, $option_value) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Jeśli tabela nie istnieje, użyj standardowej funkcji WordPress
        $wp_option_name = 'aica_' . $option_name;
        return update_option($wp_option_name, $option_value);
    }
    
    // Przygotuj wartość do zapisania w bazie danych
    $value_to_save = is_array($option_value) || is_object($option_value) 
        ? json_encode($option_value) 
        : $option_value;
    
    $now = current_time('mysql');
    
    // Sprawdź, czy opcja już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE option_name = %s",
        $option_name
    ));
    
    if ($exists) {
        // Aktualizuj istniejącą opcję
        $result = $wpdb->update(
            $table_name,
            array(
                'option_value' => $value_to_save,
                'updated_at' => $now
            ),
            array('option_name' => $option_name),
            array('%s', '%s'),
            array('%s')
        );
    } else {
        // Dodaj nową opcję
        $result = $wpdb->insert(
            $table_name,
            array(
                'option_name' => $option_name,
                'option_value' => $value_to_save,
                'autoload' => 'yes',
                'created_at' => $now,
                'updated_at' => $now
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    // Aktualizuj cache
    if ($result !== false) {
        static $options_cache = array();
        $options_cache[$option_name] = $option_value;
    }
    
    return $result !== false;
}

/**
 * Usuwa opcję wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @return bool Czy operacja się powiodła
 */
function aica_delete_option($option_name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_options';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Jeśli tabela nie istnieje, użyj standardowej funkcji WordPress
        $wp_option_name = 'aica_' . $option_name;
        return delete_option($wp_option_name);
    }
    
    // Usuń opcję z tabeli
    $result = $wpdb->delete(
        $table_name,
        array('option_name' => $option_name),
        array('%s')
    );
    
    // Aktualizuj cache
    if ($result !== false) {
        static $options_cache = array();
        unset($options_cache[$option_name]);
    }
    
    return $result !== false;
}

/**
 * Dodaje wpis do dziennika wtyczki
 *
 * @param string $message Wiadomość do zapisania
 * @param string $level Poziom logowania (info, warning, error, debug)
 * @return bool Czy operacja się powiodła
 */
function aica_log($message, $level = 'info') {
    // Sprawdź, czy logowanie jest włączone
    $logging_enabled = aica_get_option('enable_logging', false);
    
    if (!$logging_enabled && $level !== 'error') {
        return false;
    }
    
    // Określ plik dziennika
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/aica-logs';
    
    // Utwórz katalog logów, jeśli nie istnieje
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        
        // Dodaj plik .htaccess dla bezpieczeństwa
        $htaccess_file = $log_dir . '/.htaccess';
        $htaccess_content = "Options -Indexes\nDeny from all";
        file_put_contents($htaccess_file, $htaccess_content);
    }
    
    // Utwórz nazwę pliku dziennika bazując na bieżącej dacie
    $date = date('Y-m-d');
    $log_file = $log_dir . '/aica-' . $date . '.log';
    
    // Formatuj wpis dziennika
    $time = date('Y-m-d H:i:s');
    $level_uppercase = strtoupper($level);
    $log_entry = "[{$time}] [{$level_uppercase}] {$message}" . PHP_EOL;
    
    // Zapisz wpis do pliku
    $result = file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    return $result !== false;
}

/**
 * Pobiera identyfikator użytkownika wtyczki na podstawie ID użytkownika WordPress
 *
 * @param int $wp_user_id ID użytkownika WordPress
 * @return int|bool ID użytkownika wtyczki lub false w przypadku niepowodzenia
 */
function aica_get_user_id($wp_user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_users';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        return false;
    }
    
    // Pobierz ID użytkownika wtyczki
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    return $user_id ? (int) $user_id : false;
}

/**
 * Dodaje nowego użytkownika do tabeli użytkowników wtyczki
 *
 * @param int $wp_user_id ID użytkownika WordPress
 * @param string $username Nazwa użytkownika
 * @param string $email Adres e-mail użytkownika
 * @param string $role Rola użytkownika
 * @param string $created_at Data utworzenia użytkownika (format MySQL)
 * @return int|bool ID dodanego użytkownika lub false w przypadku niepowodzenia
 */
function aica_add_user($wp_user_id, $username, $email, $role, $created_at) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aica_users';
    
    // Sprawdź, czy tabela istnieje
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        return false;
    }
    
    // Sprawdź, czy użytkownik już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    if ($exists) {
        // Użytkownik już istnieje, zwróć jego ID
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE wp_user_id = %d",
            $wp_user_id
        ));
        
        return (int) $user_id;
    }
    
    // Dodaj nowego użytkownika
    $result = $wpdb->insert(
        $table_name,
        array(
            'wp_user_id' => $wp_user_id,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'created_at' => $created_at
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );
    
    if (!$result) {
        return false;
    }
    
    // Zwróć ID dodanego użytkownika
    return (int) $wpdb->insert_id;
}