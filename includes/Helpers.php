<?php
/**
 * Funkcje pomocnicze dla wtyczki AI Chat Assistant
 *
 * @package AIChatAssistant
 */

if (!defined('ABSPATH')) {
    exit; // Bezpośredni dostęp zabroniony
}

/**
 * Pobiera wartość opcji z własnej tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $default Domyślna wartość, jeśli opcja nie istnieje
 * @return mixed Wartość opcji lub wartość domyślna
 */
function aica_get_option($option_name, $default = false) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_options';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return $default;
    }
    
    $value = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM $table WHERE option_name = %s",
        $option_name
    ));
    
    if ($value === null) {
        return $default;
    }
    
    return maybe_unserialize($value);
}

/**
 * Aktualizuje lub dodaje opcję w tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @param mixed $option_value Wartość opcji
 * @return bool Czy operacja się powiodła
 */
function aica_update_option($option_name, $option_value) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_options';
    $now = current_time('mysql');
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    // Sprawdź czy opcja już istnieje
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE option_name = %s",
        $option_name
    ));
    
    $value = maybe_serialize($option_value);
    
    if ($exists) {
        // Aktualizuj istniejącą opcję
        $result = $wpdb->update(
            $table,
            [
                'option_value' => $value,
                'updated_at' => $now
            ],
            ['option_name' => $option_name],
            ['%s', '%s'],
            ['%s']
        );
    } else {
        // Dodaj nową opcję
        $result = $wpdb->insert(
            $table,
            [
                'option_name' => $option_name,
                'option_value' => $value,
                'autoload' => 'yes',
                'created_at' => $now,
                'updated_at' => $now
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }
    
    return $result !== false;
}

/**
 * Usuwa opcję z tabeli opcji wtyczki
 *
 * @param string $option_name Nazwa opcji
 * @return bool Czy operacja się powiodła
 */
function aica_delete_option($option_name) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_options';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    $result = $wpdb->delete(
        $table,
        ['option_name' => $option_name],
        ['%s']
    );
    
    return $result !== false;
}

/**
 * Pobiera ID użytkownika wtyczki na podstawie ID użytkownika WordPressa
 *
 * @param int $wp_user_id ID użytkownika WordPressa
 * @return int|false ID użytkownika wtyczki lub false jeśli nie znaleziono
 */
function aica_get_user_id($wp_user_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    
    // Jeśli nie podano ID, użyj aktualnie zalogowanego użytkownika
    if ($wp_user_id === null) {
        $wp_user_id = get_current_user_id();
    }
    
    // Jeśli ID użytkownika to 0, oznacza to, że użytkownik nie jest zalogowany
    if ($wp_user_id === 0) {
        return false;
    }
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    return $user_id ? (int) $user_id : false;
}

/**
 * Dodaje nowego użytkownika do tabeli użytkowników wtyczki
 *
 * @param int $wp_user_id ID użytkownika WordPressa
 * @param string $username Nazwa użytkownika
 * @param string $email Adres email
 * @param string $role Rola użytkownika
 * @param string $created_at Data utworzenia
 * @return int|false ID dodanego użytkownika lub false w przypadku błędu
 */
function aica_add_user($wp_user_id, $username, $email, $role, $created_at = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    // Sprawdź czy użytkownik już istnieje
    $user_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE wp_user_id = %d",
        $wp_user_id
    ));
    
    if ($user_exists) {
        // Zwróć istniejący ID użytkownika
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE wp_user_id = %d",
            $wp_user_id
        ));
    }
    
    // Użyj aktualnej daty, jeśli nie podano
    if ($created_at === null) {
        $created_at = current_time('mysql');
    }
    
    // Dodaj nowego użytkownika
    $result = $wpdb->insert(
        $table,
        [
            'wp_user_id' => $wp_user_id,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'created_at' => $created_at
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );
    
    if ($result === false) {
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * Aktualizuje ostatnie logowanie użytkownika
 *
 * @param int $user_id ID użytkownika wtyczki
 * @return bool Czy operacja się powiodła
 */
function aica_update_user_last_login($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    $now = current_time('mysql');
    
    $result = $wpdb->update(
        $table,
        ['last_login' => $now],
        ['id' => $user_id],
        ['%s'],
        ['%d']
    );
    
    return $result !== false;
}

/**
 * Pobiera wszystkich użytkowników wtyczki
 *
 * @return array|false Tablica użytkowników lub false w przypadku błędu
 */
function aica_get_users() {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    $users = $wpdb->get_results(
        "SELECT * FROM $table ORDER BY username ASC",
        ARRAY_A
    );
    
    return $users;
}

/**
 * Pobiera pojedynczy rekord użytkownika na podstawie ID
 *
 * @param int $user_id ID użytkownika wtyczki
 * @return array|false Dane użytkownika lub false jeśli nie znaleziono
 */
function aica_get_user($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $user_id
    ), ARRAY_A);
    
    return $user;
}

/**
 * Usuwa użytkownika wtyczki
 *
 * @param int $user_id ID użytkownika wtyczki
 * @return bool Czy operacja się powiodła
 */
function aica_delete_user($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'aica_users';
    
    // Sprawdź czy tabela istnieje
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
    )) === $table;
    
    if (!$table_exists) {
        return false;
    }
    
    $result = $wpdb->delete(
        $table,
        ['id' => $user_id],
        ['%d']
    );
    
    return $result !== false;
}

/**
 * Funkcja bezpieczeństwa do szyfrowania danych
 *
 * @param string $data Dane do zaszyfrowania
 * @return string Zaszyfrowane dane
 */
function aica_encrypt($data) {
    // Generowanie unikalnego klucza szyfrowania na podstawie AUTH_KEY z wp-config.php
    $encryption_key = hash('sha256', AUTH_KEY);
    
    // Generowanie losowego IV (Initialization Vector)
    $iv_size = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($iv_size);
    
    // Szyfrowanie danych
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $encryption_key, 0, $iv);
    
    // Łączenie IV i zaszyfrowanych danych
    $encrypted_data = base64_encode($iv . $encrypted);
    
    return $encrypted_data;
}

/**
 * Funkcja bezpieczeństwa do deszyfrowania danych
 *
 * @param string $encrypted_data Zaszyfrowane dane
 * @return string|false Odszyfrowane dane lub false w przypadku błędu
 */
function aica_decrypt($encrypted_data) {
    // Generowanie klucza deszyfrowania na podstawie AUTH_KEY z wp-config.php
    $encryption_key = hash('sha256', AUTH_KEY);
    
    // Dekodowanie danych z base64
    $data = base64_decode($encrypted_data);
    
    // Pobranie rozmiaru IV
    $iv_size = openssl_cipher_iv_length('AES-256-CBC');
    
    // Ekstrakcja IV i zaszyfrowanych danych
    $iv = substr($data, 0, $iv_size);
    $encrypted = substr($data, $iv_size);
    
    // Deszyfrowanie danych
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $encryption_key, 0, $iv);
    
    return $decrypted;
}

/**
 * Funkcja bezpieczeństwa do sanityzacji danych wejściowych
 *
 * @param mixed $data Dane do sanityzacji
 * @param string $type Typ danych (text, textarea, email, int, float, url)
 * @return mixed Sanityzowane dane
 */
function aica_sanitize_input($data, $type = 'text') {
    switch ($type) {
        case 'text':
            return sanitize_text_field($data);
        case 'textarea':
            return sanitize_textarea_field($data);
        case 'email':
            return sanitize_email($data);
        case 'int':
            return intval($data);
        case 'float':
            return floatval($data);
        case 'url':
            return esc_url_raw($data);
        default:
            return sanitize_text_field($data);
    }
}

/**
 * Funkcja bezpiecznego logowania
 *
 * @param string $message Treść komunikatu
 * @param string $level Poziom komunikatu (info, warning, error)
 */
function aica_log($message, $level = 'info') {
    if (!is_string($message)) {
        $message = print_r($message, true);
    }
    
    $log_file = WP_CONTENT_DIR . '/aica-logs/debug.log';
    $log_dir = dirname($log_file);
    
    // Utwórz katalog jeśli nie istnieje
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
        // Zabezpiecz katalog przed przeglądaniem
        file_put_contents($log_dir . '/.htaccess', 'Deny from all');
    }
    
    $timestamp = current_time('mysql');
    $formatted_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    error_log($formatted_message, 3, $log_file);
}