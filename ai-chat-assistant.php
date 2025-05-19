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