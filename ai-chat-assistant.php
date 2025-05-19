<?php
/**
 * AI Chat Assistant
 *
 * @package     AIChatAssistant
 * @author      Twoja Nazwa
 * @copyright   2023 Twoja Nazwa
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: AI Chat Assistant
 * Plugin URI:  https://example.com/plugin
 * Description: Integracja czatu z API Claude.ai dla programistów
 * Version:     1.0.0
 * Author:      Twoja Nazwa
 * Text Domain: ai-chat-assistant
 * Domain Path: /languages
 */

// Jeśli plik jest wywołany bezpośrednio, zakończ
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('AICA_PLUGIN_FILE', __FILE__);
define('AICA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICA_VERSION', '1.0.0');

// Załaduj funkcje pomocnicze
require_once AICA_PLUGIN_DIR . 'includes/Helpers.php';

// Autoloader klas
spl_autoload_register(function ($class) {
    // Prefiks naszej wtyczki
    $prefix = 'AICA\\';
    $base_dir = AICA_PLUGIN_DIR . 'includes/';

    // Jeśli klasa nie używa naszego prefiksu, przejdź dalej
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Ścieżka do pliku klasy
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Jeśli plik istnieje, załaduj go
    if (file_exists($file)) {
        require $file;
    }
});

// Inicjalizacja wtyczki
function aica_init() {
    $plugin = new AICA\Main();
    $plugin->run();
}
add_action('plugins_loaded', 'aica_init');

// Akcje aktywacji i deaktywacji
register_activation_hook(__FILE__, function() {
    $installer = new AICA\Installer();
    $installer->activate();
});

register_deactivation_hook(__FILE__, function() {
    $installer = new AICA\Installer();
    $installer->deactivate();
});

// Rejestracja punktów połączenia AJAX dla repozytoriów
function aica_ajax_repository_hooks() {
    // Rejestracja punktów połączenia AJAX
    add_action('wp_ajax_aica_add_repository', 'aica_ajax_add_repository');
    add_action('wp_ajax_aica_delete_repository', 'aica_ajax_delete_repository');
    add_action('wp_ajax_aica_refresh_repository', 'aica_ajax_refresh_repository');
    add_action('wp_ajax_aica_get_repository_details', 'aica_ajax_get_repository_details');
    add_action('wp_ajax_aica_get_repository_files', 'aica_ajax_get_repository_files');
    add_action('wp_ajax_aica_get_file_content', 'aica_ajax_get_file_content');
}
add_action('init', 'aica_ajax_repository_hooks');

// Funkcje AJAX dla repozytoriów
function aica_ajax_add_repository() {
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
    $repo_service = new \AICA\Services\RepositoryService();
    $result = $repo_service->save_repository($type, $name, $owner, $url, $repo_id, $description);
    
    if ($result) {
        wp_send_json_success(['message' => __('Repozytorium zostało dodane.', 'ai-chat-assistant'), 'repo_id' => $result]);
    } else {
        wp_send_json_error(['message' => __('Nie udało się dodać repozytorium.', 'ai-chat-assistant')]);
    }
}

function aica_ajax_delete_repository() {
    // Sprawdź nonce
    check_ajax_referer('aica_repository_nonce', 'nonce');
    
    // Pobierz dane
    $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
    
    if (empty($repo_id)) {
        wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
        return;
    }
    
    // Usuń repozytorium
    $repo_service = new \AICA\Services\RepositoryService();
    $result = $repo_service->delete_repository($repo_id);
    
    if ($result) {
        wp_send_json_success(['message' => __('Repozytorium zostało usunięte.', 'ai-chat-assistant')]);
    } else {
        wp_send_json_error(['message' => __('Nie udało się usunąć repozytorium.', 'ai-chat-assistant')]);
    }
}

function aica_ajax_refresh_repository() {
    // Sprawdź nonce
    check_ajax_referer('aica_repository_nonce', 'nonce');
    
    // Pobierz dane
    $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
    
    if (empty($repo_id)) {
        wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
        return;
    }
    
    // Odśwież metadane repozytorium
    $repo_service = new \AICA\Services\RepositoryService();
    $result = $repo_service->refresh_repository_metadata($repo_id);
    
    if ($result) {
        wp_send_json_success(['message' => __('Metadane repozytorium zostały zaktualizowane.', 'ai-chat-assistant')]);
    } else {
        wp_send_json_error(['message' => __('Nie udało się odświeżyć metadanych repozytorium.', 'ai-chat-assistant')]);
    }
}

function aica_ajax_get_repository_details() {
    // Sprawdź nonce
    check_ajax_referer('aica_repository_nonce', 'nonce');
    
    // Pobierz dane
    $repo_id = isset($_POST['repo_id']) ? intval($_POST['repo_id']) : 0;
    
    if (empty($repo_id)) {
        wp_send_json_error(['message' => __('Nieprawidłowe ID repozytorium.', 'ai-chat-assistant')]);
        return;
    }
    
    // Pobierz szczegóły repozytorium
    $repo_service = new \AICA\Services\RepositoryService();
    $repo = $repo_service->get_repository($repo_id);
    
    if ($repo) {
        // Pobierz dostępne gałęzie
        $branches = $repo_service->get_repository_branches($repo_id);
        
        wp_send_json_success([
            'repository' => $repo,
            'branches' => $branches
        ]);
    } else {
        wp_send_json_error(['message' => __('Nie znaleziono repozytorium.', 'ai-chat-assistant')]);
    }
}

function aica_ajax_get_repository_files() {
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
    $repo_service = new \AICA\Services\RepositoryService();
    $files = $repo_service->get_repository_files($repo_id, $path, $branch);
    
    if (is_array($files)) {
        wp_send_json_success(['files' => $files]);
    } else {
        wp_send_json_error(['message' => __('Nie udało się pobrać plików repozytorium.', 'ai-chat-assistant')]);
    }
}

function aica_ajax_get_file_content() {
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
    $repo_service = new \AICA\Services\RepositoryService();
    $file_content = $repo_service->get_file_content($repo_id, $path, $branch);
    
    if ($file_content) {
        wp_send_json_success($file_content);
    } else {
        wp_send_json_error(['message' => __('Nie udało się pobrać zawartości pliku.', 'ai-chat-assistant')]);
    }
}