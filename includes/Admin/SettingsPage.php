<?php
namespace AICA\Admin;

class SettingsPage {
    public function __construct() {
        // Nie potrzebujemy już rejestrować ustawień, ponieważ używamy własnych funkcji
    }
    
    /**
     * Renderowanie strony ustawień
     */
    public function render() {
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Tworzenie instancji klienta Claude do pobrania dostępnych modeli
        $api_key = aica_get_option('claude_api_key', '');
        $claude_client = null;
        $available_models = [
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku'
        ];
        
        if (!empty($api_key)) {
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $models = $claude_client->get_available_models();
            if (!empty($models)) {
                $available_models = $models;
            }
        }
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/settings.php';
    }
}