<?php
/**
 * Plugin Name: AI Chat Assistant
 * Plugin URI: https://twoja-domena.pl/plugins/ai-chat-assistant
 * Description: Asystent AI oparty na Claude.ai od Anthropic dla WordPressa
 * Version: 1.0.0
 * Author: Twoje Imię
 * Author URI: https://twoja-domena.pl
 * License: GPL-2.0+
 * Text Domain: ai-chat-assistant
 * Domain Path: /languages
 */

// Bezpośredni dostęp do pliku jest zabroniony
if (!defined('ABSPATH')) {
    exit;
}

// Definicje stałych
define('AICA_VERSION', '1.0.0');
define('AICA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Ładowanie funkcji pomocniczych
require_once AICA_PLUGIN_DIR . 'includes/helpers.php';

// Autoloader dla klas
spl_autoload_register(function ($class) {
    // Przestrzeń nazw wtyczki
    $prefix = 'AICA\\';
    $base_dir = AICA_PLUGIN_DIR . 'includes/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Główna klasa wtyczki
class AI_Chat_Assistant {
    private static $instance;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Inicjalizacja wtyczki
        add_action('plugins_loaded', [$this, 'init']);
        
        // Aktywacja i deaktywacja wtyczki
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }
    
    public function init() {
        // Załadowanie tłumaczeń
        load_plugin_textdomain('ai-chat-assistant', false, dirname(AICA_PLUGIN_BASENAME) . '/languages');
        
        // Inicjalizacja klasy Admin
        if (is_admin()) {
            $admin = new AICA\Main();
            $admin->run();
        }
        
        // Inicjalizacja obsługi AJAX
        $ajax_handler = new AICA\Ajax\AjaxManager();
        
        // Dodanie skrótów [aica_chat] i [aica_assistant]
        add_shortcode('aica_chat', [$this, 'render_chat_shortcode']);
        add_shortcode('aica_assistant', [$this, 'render_assistant_shortcode']);
    }
    
    /**
     * Aktywacja wtyczki
     */
    public function activate() {
        // Utworzenie tabel bazy danych
        $db_setup = new AICA\Installer();
        $db_setup->create_tables();
        
        // Domyślne ustawienia
        if (!aica_get_option('claude_model')) {
            aica_update_option('claude_model', 'claude-3-haiku-20240307');
        }
        
        if (!aica_get_option('max_tokens')) {
            aica_update_option('max_tokens', 4000);
        }
        
        if (!aica_get_option('temperature')) {
            aica_update_option('temperature', 0.7);
        }
        
        // Utworzenie katalogów
        $upload_dir = WP_CONTENT_DIR . '/uploads/aica-files';
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            // Zabezpiecz katalog przed dostępem z zewnątrz
            file_put_contents($upload_dir . '/.htaccess', 'Deny from all');
        }
        
        // Oznacz, że wtyczka została aktywowana
        aica_update_option('activated', true);
    }
    
    /**
     * Deaktywacja wtyczki
     */
    public function deactivate() {
        // Oznacz, że wtyczka została dezaktywowana
        aica_update_option('activated', false);
    }
    
    /**
     * Rendering skrótu [aica_chat]
     */
    public function render_chat_shortcode($atts) {
        $atts = shortcode_atts([
            'theme' => 'light',
            'height' => '500px',
            'width' => '100%'
        ], $atts);
        
        // Załadowanie potrzebnych skryptów i stylów
        wp_enqueue_style('aica-front-chat', AICA_PLUGIN_URL . 'assets/css/front-chat.css', [], AICA_VERSION);
        wp_enqueue_script('aica-front-chat', AICA_PLUGIN_URL . 'assets/js/front-chat.js', ['jquery'], AICA_VERSION, true);
        
        // Przekazanie danych do skryptu
        wp_localize_script('aica-front-chat', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_front_nonce'),
            'settings' => [
                'claude_model' => aica_get_option('claude_model', 'claude-3-haiku-20240307'),
                'claude_api_key' => !empty(aica_get_option('claude_api_key', ''))
            ]
        ]);
        
        // Wczytanie szablonu
        ob_start();
        include AICA_PLUGIN_DIR . 'templates/front/chat.php';
        return ob_get_clean();
    }
    
    /**
     * Rendering skrótu [aica_assistant]
     */
    public function render_assistant_shortcode($atts) {
        $atts = shortcode_atts([
            'position' => 'right',
            'welcome_message' => __('Witaj! Jak mogę Ci pomóc?', 'ai-chat-assistant')
        ], $atts);
        
        // Załadowanie potrzebnych skryptów i stylów
        wp_enqueue_style('aica-front-assistant', AICA_PLUGIN_URL . 'assets/css/front-assistant.css', [], AICA_VERSION);
        wp_enqueue_script('aica-front-assistant', AICA_PLUGIN_URL . 'assets/js/front-assistant.js', ['jquery'], AICA_VERSION, true);
        
        // Przekazanie danych do skryptu
        wp_localize_script('aica-front-assistant', 'aica_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aica_front_nonce'),
            'settings' => [
                'claude_model' => aica_get_option('claude_model', 'claude-3-haiku-20240307'),
                'claude_api_key' => !empty(aica_get_option('claude_api_key', ''))
            ],
            'welcome_message' => $atts['welcome_message']
        ]);
        
        // Wczytanie szablonu
        ob_start();
        include AICA_PLUGIN_DIR . 'templates/front/assistant.php';
        return ob_get_clean();
    }
}

// Inicjalizacja wtyczki
AI_Chat_Assistant::get_instance();

function aica_enqueue_frontend_assets() {
    if (!is_admin()) {
        wp_enqueue_style('aica-modern-chat', plugin_dir_url(__FILE__) . 'assets/css/modern-chat.css', [], '1.0');
        wp_enqueue_script('aica-modern-chat', plugin_dir_url(__FILE__) . 'assets/js/modern-chat.js', ['jquery'], '1.0', true);
    }
}
add_action('wp_enqueue_scripts', 'aica_enqueue_frontend_assets');
